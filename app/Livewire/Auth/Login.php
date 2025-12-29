<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(): void
    {
        $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        $credentials = $this->getCredentials();

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // IMPORTANT: Associate guest batches BEFORE regenerating session
        $oldSessionId = session()->getId();

        // Get intended URL BEFORE regenerating session (regenerate clears session data)
        $intended = session()->get('url.intended', route('gtin-import'));

        // Associate any guest batches from the previous session with the logged-in user
        \App\Models\ImportBatch::whereNull('user_id')
            ->where('session_id', $oldSessionId)
            ->update([
                'user_id' => auth()->id(),
                'session_id' => null, // Clear session_id since batch now belongs to user
            ]);

        session()->regenerate();

        // Redirect to intended URL or home page
        $this->redirect($intended);
    }

    protected function getCredentials(): array
    {
        if (str_contains($this->login, '@')) {
            return [
                'email' => $this->login,
                'password' => $this->password,
            ];
        }

        $cleanedPhone = $this->cleanPhoneNumber($this->login);

        return [
            'phone_number' => $cleanedPhone,
            'password' => $this->password,
        ];
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

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return strtolower($this->login).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest');
    }
}
