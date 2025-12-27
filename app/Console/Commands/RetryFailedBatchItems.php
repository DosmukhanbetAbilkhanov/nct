<?php

namespace App\Console\Commands;

use App\Jobs\FetchProductFromNationalCatalog;
use App\Models\ImportBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryFailedBatchItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:retry-failed {batch_id : The ID of the import batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed items from an import batch';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchId = $this->argument('batch_id');

        $batch = ImportBatch::find($batchId);

        if (! $batch) {
            $this->error("Import batch {$batchId} not found.");

            return self::FAILURE;
        }

        $failedItems = $batch->items()->where('status', 'failed')->get();

        if ($failedItems->isEmpty()) {
            $this->info("No failed items found in batch {$batchId}.");

            return self::SUCCESS;
        }

        $this->info("Found {$failedItems->count()} failed items in batch {$batchId}.");

        if (! $this->confirm('Do you want to retry these items?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $retryCount = 0;

        foreach ($failedItems as $item) {
            // Reset item status to pending
            $item->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

            // Dispatch job again
            FetchProductFromNationalCatalog::dispatch($item);

            $retryCount++;
        }

        // Update batch counts (decrement failed, increment processing)
        $batch->update([
            'failed_count' => $batch->failed_count - $retryCount,
            'processed_count' => $batch->processed_count - $retryCount,
            'status' => 'processing',
        ]);

        Log::info('Retrying failed batch items', [
            'batch_id' => $batchId,
            'retry_count' => $retryCount,
        ]);

        $this->success("Successfully queued {$retryCount} items for retry.");

        return self::SUCCESS;
    }
}
