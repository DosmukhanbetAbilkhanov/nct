<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\ImportBatch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private OrderService $orderService,
    ) {}

    /**
     * Create an order for an import batch.
     *
     * @throws PaymentException
     */
    public function createOrderForBatch(ImportBatch $batch, User $user): Order
    {
        if ($batch->total_gtins <= 0) {
            throw new PaymentException('Cannot create order for batch with no valid GTINs');
        }

        $this->orderService->validateNoPendingOrders($user);

        $pricePerGtin = config('services.asiapay.price_per_gtin', 10);
        $amount = $batch->total_gtins * $pricePerGtin;

        $order = Order::create([
            'user_id' => $user->id,
            'import_batch_id' => $batch->id,
            'order_number' => $this->orderService->generateUniqueOrderNumber(),
            'gtin_count' => $batch->total_gtins,
            'chargeable_gtins' => $batch->total_gtins,
            'amount' => $amount,
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Card,
            'expires_at' => now()->addMinutes(config('services.asiapay.payment_timeout', 30)),
        ]);

        $batch->update([
            'order_id' => $order->id,
            'requires_payment' => true,
        ]);

        Log::info('Order created for batch', [
            'order_id' => $order->id,
            'batch_id' => $batch->id,
            'amount' => $amount,
        ]);

        return $order;
    }

    /**
     * Initiate payment for an order.
     */
    public function initiatePayment(Order $order): Payment
    {
        if (! $order->canBePaid()) {
            throw new PaymentException('Order cannot be paid');
        }

        $returnUrl = route('payments.webhook');

        $paymentData = $this->paymentGateway->initializePayment($order, $returnUrl);

        $payment = Payment::create([
            'order_id' => $order->id,
            'asiapay_order_id' => $paymentData['order_id'] ?? null,
            'asiapay_payment_id' => $paymentData['payment_id'] ?? null,
            'payment_method' => PaymentMethod::Card,
            'amount' => $order->amount,
            'status' => PaymentStatus::Initiated,
            'redirect_url' => $paymentData['redirect_url'] ?? null,
        ]);

        $order->update(['status' => OrderStatus::PaymentInitiated]);

        Log::info('Payment initiated', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'asiapay_payment_id' => $payment->asiapay_payment_id,
        ]);

        return $payment;
    }

    /**
     * Handle webhook notification from payment gateway.
     */
    public function handleWebhookNotification(array $payload): void
    {
        Log::info('Webhook received', ['payload' => $payload]);

        $paymentId = $payload['payment_id'] ?? null;

        if (! $paymentId) {
            Log::error('Webhook missing payment_id', ['payload' => $payload]);

            return;
        }

        $payment = Payment::where('asiapay_payment_id', $paymentId)->first();

        if (! $payment) {
            Log::error('Payment not found for webhook', ['payment_id' => $paymentId]);

            return;
        }

        if ($payment->isSuccess()) {
            Log::info('Payment already processed', ['payment_id' => $payment->id]);

            return;
        }

        $status = $payload['status'] ?? 'unknown';
        $statusCode = $payload['code'] ?? null;

        $payment->update([
            'asiapay_status_code' => $statusCode,
            'asiapay_response' => json_encode($payload),
        ]);

        if ($status === 'success' || $statusCode === '00') {
            $this->processSuccessfulPayment($payment);
        } else {
            $errorMessage = $payload['message'] ?? 'Payment failed';
            $this->handleFailedPayment($payment, $errorMessage);
        }
    }

    /**
     * Process a successful payment.
     */
    public function processSuccessfulPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => PaymentStatus::Success,
                'completed_at' => now(),
            ]);

            $order = $payment->order;
            $order->markAsPaid();

            $batch = $order->importBatch;
            $batch->markPaymentAsCompleted();

            Log::info('Payment processed successfully', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'batch_id' => $batch->id,
            ]);

            event(new \App\Events\PaymentSuccessful($order, $payment));

            dispatch(new \App\Jobs\ProcessPaidBatch($batch));
        });
    }

    /**
     * Handle a failed payment.
     */
    public function handleFailedPayment(Payment $payment, string $reason): void
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'error_message' => $reason,
        ]);

        Log::warning('Payment failed', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'reason' => $reason,
        ]);

        event(new \App\Events\PaymentFailed($payment, $reason));
    }

    /**
     * Cancel expired orders.
     */
    public function cancelExpiredOrders(): int
    {
        $expiredOrders = Order::where('status', OrderStatus::Pending)
            ->where('expires_at', '<', now())
            ->get();

        $count = $expiredOrders->count();

        foreach ($expiredOrders as $order) {
            $this->orderService->expireOrder($order);
        }

        if ($count > 0) {
            Log::info("Expired {$count} orders");
        }

        return $count;
    }

    /**
     * Check if user has a pending order.
     */
    public function checkPendingOrderForUser(User $user): ?Order
    {
        return Order::where('user_id', $user->id)
            ->where('status', OrderStatus::Pending)
            ->where('expires_at', '>', now())
            ->first();
    }
}
