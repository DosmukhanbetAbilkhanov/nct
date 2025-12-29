<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\SmsVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone_number = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $verification_code = '';

    public bool $codeSent = false;

    public bool $isRegistering = false;

    public function sendVerificationCode(SmsVerificationService $smsService): void
    {
        $this->validate([
            'phone_number' => ['required', 'string', 'regex:/^(\+7|7|8)?[0-9]{10}$/', 'unique:users'],
        ]);

        $result = $smsService->sendVerificationCode(
            $this->cleanPhoneNumber($this->phone_number),
            request()->ip()
        );

        if ($result['success']) {
            $this->codeSent = true;
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
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

        $cleanedPhone = $this->cleanPhoneNumber($this->phone_number);

        if (! $smsService->verifyCode($cleanedPhone, $this->verification_code)) {
            $this->addError('verification_code', 'Invalid or expired verification code.');
            $this->isRegistering = false;

            return;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $cleanedPhone,
            'password' => Hash::make($validated['password']),
            'phone_verified_at' => now(),
        ]);

        // IMPORTANT: Associate guest batches BEFORE logging in and regenerating session
        $oldSessionId = session()->getId();

        // Get intended URL BEFORE logging in and regenerating session
        $intended = session()->get('url.intended', '/');

        // Associate any guest batches from the previous session with the newly registered user
        \App\Models\ImportBatch::whereNull('user_id')
            ->where('session_id', $oldSessionId)
            ->update([
                'user_id' => $user->id,
                'session_id' => null, // Clear session_id since batch now belongs to user
            ]);

        Auth::login($user);

        session()->regenerate();

        // Redirect to intended URL or home page
        $this->redirect($intended);
    }

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

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.guest');
    }
}
