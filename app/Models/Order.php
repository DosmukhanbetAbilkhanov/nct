<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'import_batch_id',
        'order_number',
        'gtin_count',
        'chargeable_gtins',
        'amount',
        'status',
        'payment_method',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'gtin_count' => 'integer',
            'chargeable_gtins' => 'integer',
            'amount' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the import batch for this order.
     */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /**
     * Get all payments for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the latest payment for this order.
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Scope query to only pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::Pending);
    }

    /**
     * Scope query to only expired orders.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', OrderStatus::Expired);
    }

    /**
     * Scope query to orders for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Check if order has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now() && $this->status === OrderStatus::Pending;
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return in_array($this->status, [
            OrderStatus::Paid,
            OrderStatus::Processing,
            OrderStatus::Completed,
        ]);
    }

    /**
     * Check if order can be paid.
     */
    public function canBePaid(): bool
    {
        return $this->status === OrderStatus::Pending && ! $this->isExpired();
    }

    /**
     * Mark order as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => OrderStatus::Paid,
        ]);
    }

    /**
     * Mark order as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => OrderStatus::Expired,
        ]);
    }

    /**
     * Generate unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));

        return "ORD-{$date}-{$random}";
    }
}
