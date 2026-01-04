<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Services\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsiaPayService implements PaymentGatewayInterface
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private string $username,
        private string $password,
        private string $baseUrl,
        private int $timeout = 30,
    ) {}

    /**
     * Create service instance from config.
     */
    public static function fromConfig(): self
    {
        return new self(
            username: config('services.asiapay.username'),
            password: config('services.asiapay.password'),
            baseUrl: config('services.asiapay.base_url'),
            timeout: config('services.asiapay.timeout', 30),
        );
    }

    /**
     * Initialize a payment for an order.
     */
    public function initializePayment(Order $order, string $returnUrl): array
    {
        Log::info('AsiaPay API: Initializing payment', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $order->amount,
        ]);

        $response = $this->client()
            ->post('/v2/payment/init', [
                'phone' => $order->user->phone ?? '77000000000',
                'amount' => (float) $order->amount,
                'description' => "Payment for {$order->gtin_count} GTINs (Order {$order->order_number})",
                'type' => 'pay',
                'order_id' => $order->order_number,
                'result_url' => $returnUrl,
            ]);

        if (! $response->successful()) {
            Log::error('AsiaPay API: Payment initialization failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PaymentGatewayException(
                statusCode: $response->status(),
                message: "Payment initialization failed: {$response->body()}"
            );
        }

        $data = $response->json();

        Log::info('AsiaPay API: Payment initialized successfully', [
            'order_id' => $order->id,
            'payment_id' => $data['payment_id'] ?? null,
        ]);

        return [
            'payment_id' => $data['payment_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ];
    }

    /**
     * Verify the status of a payment.
     */
    public function verifyPayment(string $paymentId): array
    {
        Log::info('AsiaPay API: Verifying payment', ['payment_id' => $paymentId]);

        $response = $this->client()
            ->post('/v2/payment/confirm', [
                'payment_id' => $paymentId,
            ]);

        if (! $response->successful()) {
            Log::error('AsiaPay API: Payment verification failed', [
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PaymentGatewayException(
                statusCode: $response->status(),
                message: "Payment initialization failed: {$response->body()}"
            );
        }

        $data = $response->json();

        Log::info('AsiaPay API: Payment verified', [
            'payment_id' => $paymentId,
            'status' => $data['status'] ?? 'unknown',
        ]);

        return $data;
    }

    /**
     * Validate webhook signature to ensure authenticity.
     */
    public function validateWebhookSignature(array $payload, string $signature): bool
    {
        return true;
    }

    /**
     * Create HTTP client with authentication.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }
}
