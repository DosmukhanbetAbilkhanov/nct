<?php

namespace App\Services\Contracts;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment for an order.
     *
     * @return array{payment_id: string, redirect_url: string, status: string}
     */
    public function initializePayment(Order $order, string $returnUrl): array;

    /**
     * Verify the status of a payment.
     *
     * @return array{status: string, payment_id: string, ...}
     */
    public function verifyPayment(string $paymentId): array;

    /**
     * Validate webhook signature to ensure authenticity.
     */
    public function validateWebhookSignature(array $payload, string $signature): bool;
}
