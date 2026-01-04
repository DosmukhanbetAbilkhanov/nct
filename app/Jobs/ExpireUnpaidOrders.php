<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        Log::info('Running ExpireUnpaidOrders job');

        $expiredCount = $paymentService->cancelExpiredOrders();

        Log::info('ExpireUnpaidOrders job completed', [
            'expired_count' => $expiredCount,
        ]);
    }
}
