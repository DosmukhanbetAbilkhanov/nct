<?php

namespace App\Listeners;

use App\Events\BatchProcessingComplete;
use App\Notifications\BatchProcessingComplete as BatchProcessingCompleteNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBatchCompleteNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(BatchProcessingComplete $event): void
    {
        $event->batch->user->notify(new BatchProcessingCompleteNotification($event->batch));
    }
}
