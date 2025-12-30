<?php

namespace App\Livewire\Concerns;

use App\Services\SmsVerificationService;

trait HandlesSmsVerification
{
    public ?int $codeSentAt = null;

    public bool $codeSent = false;

    public bool $codeVerified = false;

    /**
     * Get the phone number field name for this component.
     */
    abstract protected function getPhoneFieldName(): string;

    /**
     * Get the phone number value.
     */
    abstract protected function getPhoneNumber(): string;

    /**
     * Get the verification code field name for this component.
     */
    abstract protected function getVerificationCodeFieldName(): string;

    /**
     * Get the verification code value.
     */
    abstract protected function getVerificationCode(): string;

    /**
     * Send SMS verification code.
     */
    public function sendVerificationCode(): void
    {
        $phoneFieldName = $this->getPhoneFieldName();

        $this->validate([
            $phoneFieldName => ['required', 'string', 'regex:/^(\+7|7|8)?[0-9]{10}$/', 'unique:users,phone_number'],
        ]);

        $smsService = app(SmsVerificationService::class);
        $result = $smsService->sendVerificationCode(
            $this->cleanPhoneNumber($this->getPhoneNumber()),
            request()->ip()
        );

        if ($result['success']) {
            $this->codeSent = true;
            $this->codeSentAt = now()->timestamp;
            session()->flash('success', $result['message']);
        } else {
            $this->addError($phoneFieldName, $result['message']);
        }
    }

    /**
     * Check if the verification code has expired.
     */
    public function isCodeExpired(): bool
    {
        if (! $this->codeSentAt) {
            return false;
        }

        return now()->timestamp - $this->codeSentAt > 120; // 2 minutes = 120 seconds
    }

    /**
     * Get remaining seconds until code expires.
     */
    public function getRemainingSeconds(): int
    {
        if (! $this->codeSentAt) {
            return 0;
        }

        $elapsed = now()->timestamp - $this->codeSentAt;
        $remaining = 120 - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Verify the entered code automatically.
     */
    public function verifyEnteredCode(): void
    {
        $code = $this->getVerificationCode();

        // Only verify if we have exactly 6 digits
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return;
        }

        // Check if code has expired
        if ($this->isCodeExpired()) {
            $this->addError($this->getVerificationCodeFieldName(), 'Verification code has expired. Please request a new code.');
            $this->resetVerificationCode();

            return;
        }

        $smsService = app(SmsVerificationService::class);
        $cleanedPhone = $this->cleanPhoneNumber($this->getPhoneNumber());

        if ($smsService->verifyCode($cleanedPhone, $code)) {
            $this->codeVerified = true;
            $this->resetErrorBag($this->getVerificationCodeFieldName());
            session()->flash('success', 'Phone number verified successfully!');
        } else {
            $this->addError($this->getVerificationCodeFieldName(), 'Invalid verification code.');
        }
    }

    /**
     * Reset verification code state (called when timer expires).
     */
    public function resetVerificationCode(): void
    {
        $this->codeSent = false;
        $this->codeSentAt = null;
        $this->codeVerified = false;
        $this->{$this->getVerificationCodeFieldName()} = '';
    }

    /**
     * Clean phone number to standard format.
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        if (str_starts_with($cleaned, '8')) {
            $cleaned = '7'.substr($cleaned, 1);
        } elseif (! str_starts_with($cleaned, '7')) {
            $cleaned = '7'.$cleaned;
        }

        return $cleaned;
    }
}
