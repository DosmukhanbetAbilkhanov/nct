<?php

namespace App\Listeners;

use App\Events\PaymentSuccessful;
use App\Notifications\PaymentSuccessful as PaymentSuccessfulNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentSuccessNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessful $event): void
    {
        $event->order->user->notify(new PaymentSuccessfulNotification($event->order, $event->payment));
    }
}
