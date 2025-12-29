<?php

namespace App\Services;

use App\Models\SmsVerificationCode;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class SmsVerificationService
{
    public function __construct(
        protected SmsServiceInterface $smsService
    ) {}

    public function sendVerificationCode(string $phoneNumber, string $ipAddress): array
    {
        $key = 'sms-verification:'.$phoneNumber;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);

            return [
                'success' => false,
                'message' => "Please wait {$seconds} seconds before requesting another code.",
            ];
        }

        $dailyKey = 'sms-verification-daily:'.$phoneNumber;
        if (RateLimiter::tooManyAttempts($dailyKey, 5)) {
            return [
                'success' => false,
                'message' => 'Daily SMS limit reached. Please try again tomorrow.',
            ];
        }

        $code = SmsVerificationCode::generateCode();
        $codeHash = Hash::make($code);

        $verificationCode = SmsVerificationCode::create([
            'phone_number' => $phoneNumber,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $ipAddress,
        ]);

        $message = "Your verification code is: {$code}. Valid for 10 minutes.";
        $sent = $this->smsService->send($phoneNumber, $message);

        if ($sent) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($dailyKey, 86400);

            return [
                'success' => true,
                'message' => 'Verification code sent successfully.',
                'verification_id' => $verificationCode->id,
            ];
        }

        $verificationCode->delete();

        return [
            'success' => false,
            'message' => 'Failed to send verification code. Please try again.',
        ];
    }

    public function verifyCode(string $phoneNumber, string $code): bool
    {
        $key = 'sms-verify-attempt:'.$phoneNumber;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        $verificationCode = SmsVerificationCode::where('phone_number', $phoneNumber)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $verificationCode) {
            return false;
        }

        return $verificationCode->verify($code);
    }

    public function cleanupExpiredCodes(): int
    {
        return SmsVerificationCode::where('expires_at', '<', now())->delete();
    }
}
