<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\RateLimitException;
use App\Imports\GtinsImport;
use App\Jobs\FetchProductFromNationalCatalog;
use App\Models\ImportBatch;
use App\Models\ImportBatchItem;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

class GtinImportService
{
    /**
     * Threshold for switching from sync to async processing.
     */
    public const ASYNC_THRESHOLD = 50;

    /**
     * Number of GTINs to process per chunk in async mode.
     */
    public const CHUNK_SIZE = 10;

    /**
     * Process an uploaded Excel/CSV file and create import batch.
     */
    public function processUpload(UploadedFile $file, ?int $userId = null, ?string $sessionId = null): ImportBatch
    {
        $filename = $file->getClientOriginalName();

        Log::info('Starting GTIN import process', ['filename' => $filename]);

        // Create ImportBatch record with pending status
        $batch = ImportBatch::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'filename' => $filename,
            'total_gtins' => 0,
            'processed_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'status' => 'pending',
        ]);

        try {
            // Parse Excel file using GtinsImport
            $import = new GtinsImport;

            try {
                Excel::import($import, $file);
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                throw new \InvalidArgumentException('File validation failed: '.$e->getMessage());
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                throw new \InvalidArgumentException('Unable to read file. Please ensure it is a valid Excel or CSV file.');
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Error parsing file: '.$e->getMessage());
            }

            $gtins = $import->getGtins();

            if ($gtins->isEmpty()) {
                throw new \InvalidArgumentException('No valid GTINs found in file. Please ensure column A contains 13-digit numeric GTINs.');
            }

            Log::info('Extracted GTINs from file', [
                'batch_id' => $batch->id,
                'total_gtins' => $gtins->count(),
                'filename' => $filename,
            ]);

            // Update batch with total GTINs
            $batch->update([
                'total_gtins' => $gtins->count(),
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Decide processing strategy based on batch size
            $totalGtins = $gtins->count();

            if ($totalGtins < self::ASYNC_THRESHOLD) {
                Log::info("🚀 Using SYNCHRONOUS processing (batch size: {$totalGtins})", [
                    'batch_id' => $batch->id,
                    'total_gtins' => $totalGtins,
                ]);

                $this->processSynchronously($batch, $gtins);
            } else {
                Log::info("🚀 Using ASYNCHRONOUS processing with CHUNKING (batch size: {$totalGtins})", [
                    'batch_id' => $batch->id,
                    'total_gtins' => $totalGtins,
                    'chunk_size' => self::CHUNK_SIZE,
                    'total_chunks' => ceil($totalGtins / self::CHUNK_SIZE),
                ]);

                $this->processAsynchronously($batch, $gtins);
            }

            Log::info('Import batch created and jobs dispatched', [
                'batch_id' => $batch->id,
                'total_gtins' => $gtins->count(),
                'filename' => $filename,
            ]);

            return $batch;

        } catch (\InvalidArgumentException $e) {
            // Mark batch as failed with user-friendly error message
            $batch->update([
                'status' => 'failed',
            ]);

            Log::warning('Import batch validation failed', [
                'batch_id' => $batch->id,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            // Mark batch as failed for unexpected errors
            $batch->update([
                'status' => 'failed',
            ]);

            Log::error('Import batch failed with unexpected error', [
                'batch_id' => $batch->id,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('An unexpected error occurred while processing the file. Please try again.');
        }
    }

    /**
     * Process a single GTIN with detailed logging.
     */
    protected function processGtin(ImportBatchItem $batchItem, NationalCatalogService $service, ImportBatch $batch): void
    {
        $gtin = $batchItem->gtin;

        Log::info('🔍 Step 1: Validating GTIN format', ['gtin' => $gtin]);

        // Validate GTIN format (13 digits, numeric)
        if (! Product::isValidGtin($gtin)) {
            throw new InvalidArgumentException("Invalid GTIN format: {$gtin}. Must be 13 digits.");
        }

        Log::info('✅ Step 1: GTIN format is valid', ['gtin' => $gtin]);

        // Normalize GTIN
        $normalizedGtin = Product::normalizeGtin($gtin);
        Log::info('🔧 Step 2: GTIN normalized', [
            'original' => $gtin,
            'normalized' => $normalizedGtin,
        ]);

        // Check if product already exists in database
        Log::info('🔍 Step 3: Checking if product exists in database', ['gtin' => $normalizedGtin]);
        $product = Product::where('gtin', $normalizedGtin)->first();

        if ($product) {
            Log::info('✅ Step 3: Product already exists in database', [
                'gtin' => $normalizedGtin,
                'product_id' => $product->id,
                'has_complete_data' => ! empty($product->ntin) && ! empty($product->nameRu),
            ]);

            // Link batch item to existing product and mark as success
            $batchItem->update([
                'status' => 'success',
                'product_id' => $product->id,
            ]);

            $batch->increment('processed_count');
            $batch->increment('success_count');

            return;
        }

        Log::info('📭 Step 3: Product does not exist in database', ['gtin' => $normalizedGtin]);

        // Product doesn't exist, fetch from National Catalog API
        Log::info('🌐 Step 4: Fetching product from National Catalog API', ['gtin' => $normalizedGtin]);

        try {
            $productData = $service->fetchProductByGtin($normalizedGtin);

            Log::info('✅ Step 4: Successfully fetched product from API', [
                'gtin' => $normalizedGtin,
                'data_keys' => array_keys($productData),
                'has_ntin' => isset($productData['ntin']),
                'has_nameRu' => isset($productData['nameRu']),
                'has_nameKk' => isset($productData['nameKk']),
            ]);

            Log::info('📦 Step 5: Full API Response', [
                'gtin' => $normalizedGtin,
                'full_data' => $productData,
            ]);

        } catch (ProductNotFoundException $e) {
            Log::warning('❌ Step 4: Product not found in National Catalog', [
                'gtin' => $normalizedGtin,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (RateLimitException $e) {
            Log::warning('⏸️ Step 4: Rate limit exceeded', [
                'gtin' => $normalizedGtin,
                'retry_after' => $e->retryAfter,
            ]);

            throw $e;
        } catch (ApiException $e) {
            Log::error('❌ Step 4: API error occurred', [
                'gtin' => $normalizedGtin,
                'status_code' => $e->statusCode,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        // Save product to database
        Log::info('💾 Step 6: Saving product to database', [
            'gtin' => $normalizedGtin,
            'ntin' => $productData['ntin'] ?? null,
            'nameRu' => $productData['nameRu'] ?? null,
            'nameKk' => $productData['nameKk'] ?? null,
        ]);

        DB::transaction(function () use ($productData, $normalizedGtin, $batchItem, $batch) {
            $product = Product::create([
                'gtin' => $normalizedGtin,
                'ntin' => $productData['ntin'] ?? null,
                'nameKk' => $productData['nameKk'] ?? null,
                'nameRu' => $productData['nameRu'] ?? null,
                'nameEn' => $productData['nameEn'] ?? null,
                'shortNameKk' => $productData['shortNameKk'] ?? null,
                'shortNameRu' => $productData['shortNameRu'] ?? null,
                'shortNameEn' => $productData['shortNameEn'] ?? null,
                'createdDate' => $productData['createdDate'] ?? null,
                'updatedDate' => $productData['updatedDate'] ?? null,
            ]);

            Log::info('✅ Step 6: Product saved to database', [
                'gtin' => $normalizedGtin,
                'product_id' => $product->id,
                'saved_ntin' => $product->ntin,
                'saved_nameRu' => $product->nameRu,
                'saved_nameKk' => $product->nameKk,
            ]);

            // Update batch item with success and product reference
            $batchItem->update([
                'status' => 'success',
                'product_id' => $product->id,
            ]);

            $batch->increment('processed_count');
            $batch->increment('success_count');

            Log::info('🎉 Successfully processed GTIN', [
                'gtin' => $normalizedGtin,
                'product_id' => $product->id,
                'batch_progress' => "{$batch->processed_count}/{$batch->total_gtins}",
            ]);
        });
    }

    /**
     * Process GTINs synchronously (for small batches).
     */
    protected function processSynchronously(ImportBatch $batch, $gtins): void
    {
        $service = NationalCatalogService::fromConfig();

        foreach ($gtins as $index => $gtin) {
            Log::info("📋 Processing GTIN {$index}/{$gtins->count()}", [
                'gtin' => $gtin,
                'batch_id' => $batch->id,
                'progress' => ($index + 1).'/'.$gtins->count(),
            ]);

            // Create batch item
            $batchItem = ImportBatchItem::create([
                'import_batch_id' => $batch->id,
                'gtin' => $gtin,
                'status' => 'processing',
            ]);

            try {
                $this->processGtin($batchItem, $service, $batch);
            } catch (\Exception $e) {
                Log::error('❌ Failed to process GTIN', [
                    'gtin' => $gtin,
                    'error' => $e->getMessage(),
                ]);

                $batchItem->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $batch->increment('processed_count');
                $batch->increment('failed_count');
            }
        }

        // Mark batch as completed
        $batch->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Generate export files
        $batch->generateExportFiles();

        Log::info('✅ Synchronous batch processing completed', [
            'batch_id' => $batch->id,
            'total_gtins' => $gtins->count(),
            'success_count' => $batch->success_count,
            'failed_count' => $batch->failed_count,
        ]);
    }

    /**
     * Process GTINs asynchronously using queues (for large batches).
     */
    protected function processAsynchronously(ImportBatch $batch, $gtins): void
    {
        // Process GTINs in chunks to manage queue load
        $chunks = $gtins->chunk(self::CHUNK_SIZE);
        $delaySeconds = 0;

        foreach ($chunks as $chunkIndex => $chunk) {
            Log::info("📦 Dispatching chunk {$chunkIndex} to queue", [
                'batch_id' => $batch->id,
                'chunk_index' => $chunkIndex + 1,
                'chunk_size' => $chunk->count(),
                'delay_seconds' => $delaySeconds,
            ]);

            foreach ($chunk as $gtin) {
                // Create batch item
                $batchItem = ImportBatchItem::create([
                    'import_batch_id' => $batch->id,
                    'gtin' => $gtin,
                    'status' => 'pending',
                ]);

                // Dispatch job with incremental delay to avoid overwhelming the API
                FetchProductFromNationalCatalog::dispatch($batchItem)
                    ->delay(now()->addSeconds($delaySeconds));

                // Add 2 seconds delay between each job (30 requests per minute = 1 per 2 seconds)
                $delaySeconds += 2;
            }
        }

        Log::info('✅ All chunks dispatched to queue', [
            'batch_id' => $batch->id,
            'total_chunks' => $chunks->count(),
            'total_jobs' => $gtins->count(),
            'total_delay_minutes' => round($delaySeconds / 60, 2),
        ]);
    }
}
