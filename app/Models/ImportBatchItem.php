<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchItem extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'import_batch_id',
        'gtin',
        'status',
        'error_message',
        'product_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'import_batch_id' => 'integer',
            'product_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the batch that owns this item.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    /**
     * Get the product associated with this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Mark item as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark item as successful and link to product.
     */
    public function markAsSuccess(?int $productId = null): void
    {
        $this->update([
            'status' => 'success',
            'product_id' => $productId,
            'error_message' => null,
        ]);
    }

    /**
     * Mark item as failed with error message.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
