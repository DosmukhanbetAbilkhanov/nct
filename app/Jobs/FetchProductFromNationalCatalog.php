<?php

namespace App\Jobs;

use App\Exceptions\ApiException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\RateLimitException;
use App\Models\ImportBatchItem;
use App\Models\Product;
use App\Services\NationalCatalogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchProductFromNationalCatalog implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ImportBatchItem $batchItem,
    ) {
        $this->onQueue('national-catalog');
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new RateLimited('national-catalog-api')];
    }

    /**
     * Execute the job.
     */
    public function handle(NationalCatalogService $service): void
    {
        $gtin = $this->batchItem->gtin;

        Log::info('Processing GTIN from queue', [
            'gtin' => $gtin,
            'batch_item_id' => $this->batchItem->id,
            'batch_id' => $this->batchItem->import_batch_id,
        ]);

        // Mark item as processing
        $this->batchItem->markAsProcessing();

        try {
            // Validate GTIN format (13 digits, numeric)
            if (! Product::isValidGtin($gtin)) {
                throw new \InvalidArgumentException("Invalid GTIN format: {$gtin}. Must be 13 digits.");
            }

            // Normalize GTIN
            $normalizedGtin = Product::normalizeGtin($gtin);

            // Check if product already exists in database
            $product = Product::where('gtin', $normalizedGtin)->first();

            if ($product) {
                Log::info('Product already exists in database', [
                    'gtin' => $normalizedGtin,
                    'product_id' => $product->id,
                ]);

                // Link batch item to existing product and mark as success
                $this->batchItem->markAsSuccess($product->id);
                $this->batchItem->batch->incrementProcessed(success: true);

                return;
            }

            // Product doesn't exist, fetch from National Catalog API
            $productData = $service->fetchProductByGtin($normalizedGtin);

            // Save product to database
            DB::transaction(function () use ($productData, $normalizedGtin) {
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

                Log::info('Product saved to database', [
                    'gtin' => $normalizedGtin,
                    'product_id' => $product->id,
                ]);

                // Update batch item with success and product reference
                $this->batchItem->markAsSuccess($product->id);
                $this->batchItem->batch->incrementProcessed(success: true);
            });

        } catch (ProductNotFoundException $e) {
            // Product not found in API - mark as failed (non-retryable)
            Log::warning('Product not found in National Catalog', [
                'gtin' => $gtin,
                'batch_item_id' => $this->batchItem->id,
            ]);

            DB::transaction(function () use ($e) {
                $this->batchItem->markAsFailed($e->getMessage());
                $this->batchItem->batch->incrementProcessed(success: false);
            });

        } catch (RateLimitException $e) {
            // Rate limit hit - release job back to queue
            Log::warning('Rate limit exceeded, releasing job back to queue', [
                'gtin' => $gtin,
                'batch_item_id' => $this->batchItem->id,
                'retry_after' => $e->retryAfter,
            ]);

            // Release back to queue with delay from API response
            $this->release($e->retryAfter);

        } catch (ApiException $e) {
            // API error - log and mark as failed
            Log::error('National Catalog API error', [
                'gtin' => $gtin,
                'batch_item_id' => $this->batchItem->id,
                'status_code' => $e->statusCode,
                'error' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($e) {
                $this->batchItem->markAsFailed("API error: {$e->getMessage()}");
                $this->batchItem->batch->incrementProcessed(success: false);
            });

        } catch (\InvalidArgumentException $e) {
            // Validation error - mark as failed (non-retryable)
            Log::error('GTIN validation error', [
                'gtin' => $gtin,
                'batch_item_id' => $this->batchItem->id,
                'error' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($e) {
                $this->batchItem->markAsFailed($e->getMessage());
                $this->batchItem->batch->incrementProcessed(success: false);
            });

        } catch (\Exception $e) {
            // Unexpected error - log and rethrow to trigger retry
            Log::error('Unexpected error processing GTIN', [
                'gtin' => $gtin,
                'batch_item_id' => $this->batchItem->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure after all retry attempts.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Job permanently failed after all retries', [
            'gtin' => $this->batchItem->gtin,
            'batch_item_id' => $this->batchItem->id,
            'batch_id' => $this->batchItem->import_batch_id,
            'exception' => $exception?->getMessage(),
            'exception_type' => $exception ? get_class($exception) : null,
        ]);

        // Mark as failed with exception message (atomic transaction)
        $errorMessage = $exception
            ? "Job failed after all retries: {$exception->getMessage()}"
            : 'Job failed after all retries';

        DB::transaction(function () use ($errorMessage) {
            $this->batchItem->markAsFailed($errorMessage);
            $this->batchItem->batch->incrementProcessed(success: false);
        });
    }
}
