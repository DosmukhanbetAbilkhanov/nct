<?php

namespace App\Services;

use App\Imports\GtinsImport;
use App\Jobs\FetchProductFromNationalCatalog;
use App\Models\ImportBatch;
use App\Models\ImportBatchItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GtinImportService
{
    /**
     * Process an uploaded Excel/CSV file and create import batch.
     */
    public function processUpload(UploadedFile $file): ImportBatch
    {
        $filename = $file->getClientOriginalName();

        Log::info('Starting GTIN import process', ['filename' => $filename]);

        // Create ImportBatch record with pending status
        $batch = ImportBatch::create([
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

            // Create ImportBatchItem for each unique GTIN and dispatch jobs
            DB::transaction(function () use ($batch, $gtins) {
                foreach ($gtins as $gtin) {
                    $batchItem = ImportBatchItem::create([
                        'import_batch_id' => $batch->id,
                        'gtin' => $gtin,
                        'status' => 'pending',
                    ]);

                    // Dispatch job to queue
                    FetchProductFromNationalCatalog::dispatch($batchItem);
                }

                // Update batch with total GTINs and set status to processing
                $batch->update([
                    'total_gtins' => $gtins->count(),
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
            });

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
}
