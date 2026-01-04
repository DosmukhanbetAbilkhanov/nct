<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'asiapay_order_id',
        'asiapay_payment_id',
        'payment_method',
        'amount',
        'status',
        'asiapay_status_code',
        'asiapay_response',
        'redirect_url',
        'error_message',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the order that owns this payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment is successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === PaymentStatus::Success;
    }

    /**
     * Check if payment has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            PaymentStatus::Pending,
            PaymentStatus::Initiated,
            PaymentStatus::Processing,
        ]);
    }

    /**
     * Mark payment as successful.
     */
    public function markAsSuccess(): void
    {
        $this->update([
            'status' => PaymentStatus::Success,
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => PaymentStatus::Failed,
            'error_message' => $reason,
        ]);
    }
}
