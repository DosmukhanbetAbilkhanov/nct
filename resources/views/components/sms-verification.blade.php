@props([
    'phoneModel',
    'codeModel',
    'codeSent' => false,
    'codeVerified' => false,
    'remainingSeconds' => 0,
    'phoneLabel' => null,
    'phonePlaceholder' => null,
    'codeLabel' => null,
    'sendButtonText' => null,
])

@php
    $phoneLabel = $phoneLabel ?? __('auth.phone_number');
    $phonePlaceholder = $phonePlaceholder ?? __('auth.phone_placeholder');
    $codeLabel = $codeLabel ?? __('auth.verification_code');
    $sendButtonText = $sendButtonText ?? __('auth.send_code');
@endphp

<div x-data="{
    remainingSeconds: {{ $remainingSeconds }},
    countdownInterval: null,

    init() {
        @if($codeSent && !$codeVerified && $remainingSeconds > 0)
            this.remainingSeconds = {{ $remainingSeconds }};
            this.startCountdown();
        @endif
    },

    startCountdown() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }

        this.countdownInterval = setInterval(() => {
            if (this.remainingSeconds > 0) {
                this.remainingSeconds--;
            } else {
                clearInterval(this.countdownInterval);
                @this.call('resetVerificationCode');
            }
        }, 1000);
    },

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}">
    <div>
        <label for="{{ $phoneModel }}" class="block text-sm/6 font-semibold text-gray-900">{{ $phoneLabel }}</label>
        <div class="mt-2.5 flex gap-2">
            <input type="text"
                   id="{{ $phoneModel }}"
                   wire:model="{{ $phoneModel }}"
                   @if($codeVerified) disabled @endif
                   class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 @if($codeVerified) bg-gray-100 cursor-not-allowed @endif"
                   placeholder="{{ $phonePlaceholder }}"
                   required>

            <!-- Send Code Button -->
            @if(!$codeSent && !$codeVerified)
                <button type="button"
                        wire:click="sendVerificationCode"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-500 whitespace-nowrap cursor-pointer transition-colors">
                    <span wire:loading.remove>{{ $sendButtonText }}</span>
                    <span wire:loading>{{ __('auth.sending') }}</span>
                </button>
            @endif

            <!-- Countdown Timer -->
            @if($codeSent && !$codeVerified)
                <div class="px-4 py-2 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-md whitespace-nowrap flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="formatTime(remainingSeconds)"></span>
                </div>
            @endif

            <!-- Verified Badge -->
            @if($codeVerified)
                <div class="px-4 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-md whitespace-nowrap flex items-center gap-2 border border-green-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ __('auth.verified') }}</span>
                </div>
            @endif
        </div>
        @error($phoneModel) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <!-- Verification Code Input -->
    @if($codeSent)
        <div>
            <label for="{{ $codeModel }}" class="block text-sm/6 font-semibold text-gray-900">{{ $codeLabel }}</label>
            <div class="relative">
                <input type="text"
                       id="{{ $codeModel }}"
                       wire:model.live="{{ $codeModel }}"
                       @if($codeVerified) disabled @endif
                       class="mt-2.5 block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 @if($codeVerified) bg-gray-100 cursor-not-allowed @endif"
                       maxlength="6"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       autocomplete="one-time-code"
                       required>

                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <!-- Verified Checkmark -->
                    @if($codeVerified)
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <!-- Loading Spinner -->
                        <div wire:loading wire:target="{{ $codeModel }}">
                            <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    @endif
                </div>
            </div>

            @error($codeModel) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

            <!-- Help Text -->
            @if(!$codeVerified)
                <p class="mt-2 text-sm text-gray-600">
                    {!! __('auth.enter_code_help', ['time' => '<span x-text="formatTime(remainingSeconds)"></span>']) !!}
                </p>
            @endif

            <!-- Success Message -->
            @if($codeVerified)
                <p class="mt-2 text-sm text-green-600 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ __('auth.phone_verified_success') }}
                </p>
            @endif
        </div>
    @endif
</div>
