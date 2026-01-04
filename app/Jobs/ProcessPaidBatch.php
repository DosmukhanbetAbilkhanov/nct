<?php

namespace App\Jobs;

use App\Events\BatchProcessingComplete;
use App\Models\ImportBatch;
use App\Services\GtinImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPaidBatch implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [60, 300, 600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ImportBatch $batch
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GtinImportService $gtinImportService): void
    {
        Log::info('Processing paid batch', [
            'batch_id' => $this->batch->id,
            'total_gtins' => $this->batch->total_gtins,
        ]);

        try {
            // Update batch status to processing
            $this->batch->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Get GTINs from batch items
            $gtins = $this->batch->items()->pluck('gtin');

            if ($gtins->isEmpty()) {
                throw new \RuntimeException('No GTINs found in batch');
            }

            // Process batch based on size
            if ($gtins->count() < GtinImportService::ASYNC_THRESHOLD) {
                // Use reflection to call protected method
                $reflection = new \ReflectionClass($gtinImportService);
                $method = $reflection->getMethod('processSynchronously');
                $method->setAccessible(true);
                $method->invoke($gtinImportService, $this->batch, $gtins);
            } else {
                // Use reflection to call protected method
                $reflection = new \ReflectionClass($gtinImportService);
                $method = $reflection->getMethod('processAsynchronously');
                $method->setAccessible(true);
                $method->invoke($gtinImportService, $this->batch, $gtins);
            }

            // Fire batch processing complete event
            BatchProcessingComplete::dispatch($this->batch->fresh());

            Log::info('Paid batch processing completed successfully', [
                'batch_id' => $this->batch->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process paid batch', [
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update batch status to failed
            $this->batch->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPaidBatch job failed after all retries', [
            'batch_id' => $this->batch->id,
            'error' => $exception->getMessage(),
        ]);

        // Update batch status to failed
        $this->batch->update([
            'status' => 'failed',
        ]);
    }
}
