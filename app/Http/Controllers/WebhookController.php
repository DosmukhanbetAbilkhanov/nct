<?php

namespace App\Http\Controllers;

use App\Services\Contracts\PaymentGatewayInterface;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PaymentGatewayInterface $paymentGateway,
    ) {}

    /**
     * Handle AsiaPay webhook notifications.
     */
    public function asiapayWebhook(Request $request)
    {
        Log::info('AsiaPay webhook received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        $signature = $request->header('X-Signature', '');
        $payload = $request->all();

        if (! $this->paymentGateway->validateWebhookSignature($payload, $signature)) {
            Log::warning('Invalid webhook signature', [
                'signature' => $signature,
                'payload' => $payload,
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        try {
            $this->paymentService->handleWebhookNotification($payload);

            return response()->json([], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
