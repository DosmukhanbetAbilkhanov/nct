<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    /**
     * Generate a unique order number.
     */
    public function generateUniqueOrderNumber(): string
    {
        do {
            $orderNumber = Order::generateOrderNumber();
            $exists = Order::where('order_number', $orderNumber)->exists();
        } while ($exists);

        return $orderNumber;
    }

    /**
     * Validate that the user has no pending orders.
     *
     * @throws PaymentException
     */
    public function validateNoPendingOrders(User $user): void
    {
        $pendingOrder = Order::where('user_id', $user->id)
            ->where('status', OrderStatus::Pending)
            ->where('expires_at', '>', now())
            ->first();

        if ($pendingOrder) {
            throw new PaymentException(
                "You already have a pending order (#{$pendingOrder->order_number}). Please complete or wait for it to expire before creating a new order."
            );
        }
    }

    /**
     * Mark an order as expired.
     */
    public function expireOrder(Order $order): void
    {
        if ($order->status === OrderStatus::Pending && $order->expires_at < now()) {
            $order->markAsExpired();
        }
    }
}
