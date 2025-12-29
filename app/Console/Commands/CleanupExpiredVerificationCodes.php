<?php

namespace App\Console\Commands;

use App\Services\SmsVerificationService;
use Illuminate\Console\Command;

class CleanupExpiredVerificationCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:cleanup-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired SMS verification codes';

    /**
     * Execute the console command.
     */
    public function handle(SmsVerificationService $service): int
    {
        $deleted = $service->cleanupExpiredCodes();

        $this->info("Cleaned up {$deleted} expired verification codes.");

        return self::SUCCESS;
    }
}
