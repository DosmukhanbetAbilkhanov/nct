<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Notifications\PaymentFailed as PaymentFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentFailedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        $event->payment->order->user->notify(new PaymentFailedNotification($event->payment, $event->reason));
    }
}
