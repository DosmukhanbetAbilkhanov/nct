<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentException;
use App\Models\ImportBatch;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private ReceiptService $receiptService,
    ) {}

    /**
     * Initiate payment for an import batch.
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'import_batch_id' => 'required|exists:import_batches,id',
        ]);

        $batch = ImportBatch::findOrFail($request->import_batch_id);

        if ($batch->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this batch');
        }

        try {
            $order = $this->paymentService->createOrderForBatch($batch, auth()->user());

            $payment = $this->paymentService->initiatePayment($order);

            if ($payment->redirect_url) {
                return redirect($payment->redirect_url);
            }

            return redirect()->route('batches.show', $batch)
                ->with('error', 'Failed to initiate payment. Please try again.');

        } catch (PaymentException $e) {
            Log::error('Payment initiation failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Handle return from payment gateway.
     */
    public function handleReturn(Request $request)
    {
        $paymentId = $request->get('payment_id');
        $status = $request->get('status');

        Log::info('Payment return', [
            'payment_id' => $paymentId,
            'status' => $status,
            'params' => $request->all(),
        ]);

        if ($status === 'success') {
            return redirect()->route('batches.index')
                ->with('success', 'Payment successful! Your batch is being processed.');
        }

        if ($status === 'cancelled') {
            return redirect()->route('batches.index')
                ->with('warning', 'Payment was cancelled. You can retry from your requests page.');
        }

        return redirect()->route('batches.index')
            ->with('error', 'Payment failed. Please try again or contact support.');
    }

    /**
     * Retry payment for a failed order.
     */
    public function handleRetry(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        if ($order->isExpired()) {
            return redirect()->back()
                ->with('error', 'This order has expired. Please create a new order.');
        }

        if (! $order->canBePaid()) {
            return redirect()->back()
                ->with('error', 'This order cannot be paid.');
        }

        try {
            $payment = $this->paymentService->initiatePayment($order);

            if ($payment->redirect_url) {
                return redirect($payment->redirect_url);
            }

            return redirect()->back()
                ->with('error', 'Failed to initiate payment. Please try again.');

        } catch (PaymentException $e) {
            Log::error('Payment retry failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Download payment receipt.
     */
    public function downloadReceipt(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        if (! $order->isPaid()) {
            abort(404, 'Receipt not available for unpaid orders');
        }

        return $this->receiptService->downloadPaymentReceipt($order);
    }
}
