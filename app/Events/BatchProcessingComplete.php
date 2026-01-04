<?php

namespace App\Events;

use App\Models\ImportBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchProcessingComplete
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ImportBatch $batch
    ) {}
}
