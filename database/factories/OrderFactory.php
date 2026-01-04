<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gtinCount = fake()->numberBetween(1, 100);
        $pricePerGtin = 10;

        return [
            'order_number' => Order::generateOrderNumber(),
            'gtin_count' => $gtinCount,
            'chargeable_gtins' => $gtinCount,
            'amount' => $gtinCount * $pricePerGtin,
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Card,
            'expires_at' => now()->addMinutes(30),
        ];
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Paid,
            ];
        });
    }

    /**
     * Indicate that the order is expired.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Expired,
                'expires_at' => now()->subMinutes(30),
            ];
        });
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Completed,
            ];
        });
    }

    /**
     * Indicate that payment has been initiated.
     */
    public function paymentInitiated(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::PaymentInitiated,
            ];
        });
    }
}
