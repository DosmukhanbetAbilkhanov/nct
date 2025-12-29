<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'filename',
        'total_gtins',
        'processed_count',
        'success_count',
        'failed_count',
        'status',
        'started_at',
        'completed_at',
        'success_file_path',
        'failed_file_path',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'total_gtins' => 'integer',
            'processed_count' => 'integer',
            'success_count' => 'integer',
            'failed_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get all items belonging to this batch.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ImportBatchItem::class);
    }

    /**
     * Get only failed items.
     */
    public function failedItems(): HasMany
    {
        return $this->hasMany(ImportBatchItem::class)->where('status', 'failed');
    }

    /**
     * Get only successful items.
     */
    public function successfulItems(): HasMany
    {
        return $this->hasMany(ImportBatchItem::class)->where('status', 'success');
    }

    /**
     * Get only pending items.
     */
    public function pendingItems(): HasMany
    {
        return $this->hasMany(ImportBatchItem::class)->where('status', 'pending');
    }

    /**
     * Calculate progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_gtins === 0) {
            return 0;
        }

        return round(($this->processed_count / $this->total_gtins) * 100, 2);
    }

    /**
     * Check if batch is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed' || $this->status === 'failed';
    }

    /**
     * Check if batch is still processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Increment processed count.
     */
    public function incrementProcessed(bool $success = true): void
    {
        $this->increment('processed_count');

        if ($success) {
            $this->increment('success_count');
        } else {
            $this->increment('failed_count');
        }

        // Mark as completed if all items processed
        if ($this->processed_count >= $this->total_gtins) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Generate export files automatically when batch completes
            $this->generateExportFiles();
        }
    }

    /**
     * Generate export files for this batch.
     */
    public function generateExportFiles(): void
    {
        $directory = "imports/batch-{$this->id}";

        // Generate successful products file
        if ($this->success_count > 0) {
            $successFilename = "successful-products-batch-{$this->id}.xlsx";
            $successPath = "{$directory}/{$successFilename}";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\SuccessfulProductsExport($this),
                $successPath,
                'local'
            );

            $this->update(['success_file_path' => $successPath]);

            \Illuminate\Support\Facades\Log::info('✅ Generated successful products export', [
                'batch_id' => $this->id,
                'file_path' => $successPath,
                'product_count' => $this->success_count,
            ]);
        }

        // Generate failed GTINs file
        if ($this->failed_count > 0) {
            $failedFilename = "failed-gtins-batch-{$this->id}.xlsx";
            $failedPath = "{$directory}/{$failedFilename}";

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Exports\FailedGtinsExport($this),
                $failedPath,
                'local'
            );

            $this->update(['failed_file_path' => $failedPath]);

            \Illuminate\Support\Facades\Log::info('❌ Generated failed GTINs export', [
                'batch_id' => $this->id,
                'file_path' => $failedPath,
                'failed_count' => $this->failed_count,
            ]);
        }
    }

    /**
     * Get download URL for successful products file.
     */
    public function getSuccessFileUrlAttribute(): ?string
    {
        if (! $this->success_file_path) {
            return null;
        }

        return route('import.download', [
            'batch' => $this->id,
            'type' => 'success',
        ]);
    }

    /**
     * Get download URL for failed GTINs file.
     */
    public function getFailedFileUrlAttribute(): ?string
    {
        if (! $this->failed_file_path) {
            return null;
        }

        return route('import.download', [
            'batch' => $this->id,
            'type' => 'failed',
        ]);
    }
}
