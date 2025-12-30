<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\HandlesSmsVerification;
use App\Models\User;
use App\Services\SmsVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    use HandlesSmsVerification;

    public string $name = '';

    public string $email = '';

    public string $phone_number = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $verification_code = '';

    public bool $isRegistering = false;

    /**
     * Auto-verify code when user types 6 digits.
     */
    public function updatedVerificationCode(): void
    {
        $this->verifyEnteredCode();
    }

    /**
     * Trait method implementations.
     */
    protected function getPhoneFieldName(): string
    {
        return 'phone_number';
    }

    protected function getPhoneNumber(): string
    {
        return $this->phone_number;
    }

    protected function getVerificationCodeFieldName(): string
    {
        return 'verification_code';
    }

    protected function getVerificationCode(): string
    {
        return $this->verification_code;
    }

    public function register(SmsVerificationService $smsService): void
    {
        $this->isRegistering = true;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'regex:/^(\+7|7|8)?[0-9]{10}$/', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        // Check if code has been verified
        if (! $this->codeVerified) {
            $this->addError('verification_code', 'Please verify your phone number first.');
            $this->isRegistering = false;

            return;
        }

        $cleanedPhone = $this->cleanPhoneNumber($this->phone_number);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $cleanedPhone,
            'password' => Hash::make($validated['password']),
            'phone_verified_at' => now(),
        ]);

        // Get intended URL BEFORE logging in and regenerating session
        $intended = session()->get('url.intended', route('gtin-import'));

        // If intended URL is a download route, redirect to import page instead
        // This prevents the user from ending up on a blank download page after registration
        if (str_contains($intended, '/import/download/')) {
            $intended = route('gtin-import');
        }

        Auth::login($user);

        session()->regenerate();

        // Redirect to intended URL or home page
        $this->redirect($intended);
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.guest');
    }
}
