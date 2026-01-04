<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asiapay_order_id' => null,
            'asiapay_payment_id' => null,
            'payment_method' => PaymentMethod::Card,
            'amount' => fake()->randomFloat(2, 10, 1000),
            'status' => PaymentStatus::Pending,
            'asiapay_status_code' => null,
            'asiapay_response' => null,
            'redirect_url' => null,
            'error_message' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the payment was initiated.
     */
    public function initiated(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PaymentStatus::Initiated,
                'asiapay_payment_id' => 'pay_'.fake()->uuid(),
                'asiapay_order_id' => 'ord_'.fake()->uuid(),
                'redirect_url' => 'https://apitest.asiapay.kz/payment/'.fake()->uuid(),
            ];
        });
    }

    /**
     * Indicate that the payment was successful.
     */
    public function successful(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PaymentStatus::Success,
                'asiapay_payment_id' => 'pay_'.fake()->uuid(),
                'asiapay_order_id' => 'ord_'.fake()->uuid(),
                'asiapay_status_code' => '00',
                'completed_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PaymentStatus::Failed,
                'asiapay_payment_id' => 'pay_'.fake()->uuid(),
                'asiapay_order_id' => 'ord_'.fake()->uuid(),
                'asiapay_status_code' => '05',
                'error_message' => 'Payment declined by bank',
            ];
        });
    }

    /**
     * Indicate that the payment is processing.
     */
    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PaymentStatus::Processing,
                'asiapay_payment_id' => 'pay_'.fake()->uuid(),
                'asiapay_order_id' => 'ord_'.fake()->uuid(),
            ];
        });
    }
}
