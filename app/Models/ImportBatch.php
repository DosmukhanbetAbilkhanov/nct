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
        }
    }
}
