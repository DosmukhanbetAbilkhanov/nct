<div class="isolate bg-white px-6 py-10 sm:py-12 lg:px-8">
    {{-- Decorative background gradient --}}
    {{-- <div aria-hidden="true" class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
        <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" class="relative left-1/2 -z-10 aspect-[1155/678] w-[36.125rem] max-w-none -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-40rem)] sm:w-[72.1875rem]"></div>
    </div> --}}

    {{-- Header --}}
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-2xl font-semibold tracking-tight text-balance text-gray-900 sm:text-3xl">{{ __('auth.register') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ __('auth.register_description') }}</p>
    </div>

    {{-- <div class="px-6 pt-8 pb-4 text-center">
        <h3 class="text-2xl font-semibold text-gray-900">{{ __('import.authentication_required') }}</h3>
        <p class="mt-2 text-sm text-gray-600">{{ __('import.auth_description') }}</p>
    </div> --}}


    {{-- Success/Error Messages --}}
    @if (session('success'))
        <div class="mx-auto mt-8 max-w-xl">
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mx-auto mt-8 max-w-xl">
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Registration Form --}}
    <form wire:submit="register" class="mx-auto mt-8 max-w-xl sm:mt-12">
        <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
            {{-- Name --}}
            <div class="sm:col-span-2">
                <label for="name" class="block text-sm/6 font-semibold text-gray-900">{{ __('auth.name') }}</label>
                <div class="mt-2.5">
                    <input
                        wire:model="name"
                        type="text"
                        id="name"
                        class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                    />
                </div>
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="sm:col-span-2">
                <label for="email" class="block text-sm/6 font-semibold text-gray-900">{{ __('auth.email') }}</label>
                <div class="mt-2.5">
                    <input
                        wire:model="email"
                        type="email"
                        id="email"
                        class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                    />
                </div>
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- SMS Verification --}}
            <div class="sm:col-span-2">
                <x-sms-verification
                    phone-model="phone_number"
                    code-model="verification_code"
                    :code-sent="$codeSent"
                    :code-verified="$codeVerified"
                    :remaining-seconds="$this->getRemainingSeconds()"
                    :phone-placeholder="__('auth.phone_placeholder')"
                    :phone-label="__('auth.phone_number')"
                    :code-label="__('auth.verification_code')"
                    :send-button-text="__('auth.send_code')"
                />
            </div>

            {{-- Password --}}
            <div class="sm:col-span-2">
                <label for="password" class="block text-sm/6 font-semibold text-gray-900">{{ __('auth.password_label') }}</label>
                <div class="mt-2.5">
                    <input
                        wire:model="password"
                        type="password"
                        id="password"
                        class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                    />
                </div>
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Confirmation --}}
            <div class="sm:col-span-2">
                <label for="password_confirmation" class="block text-sm/6 font-semibold text-gray-900">{{ __('auth.password_confirmation') }}</label>
                <div class="mt-2.5">
                    <input
                        wire:model="password_confirmation"
                        type="password"
                        id="password_confirmation"
                        class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                    />
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="mt-10">
            <button
                type="submit"
                class="block w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="register">{{ __('auth.register') }}</span>
                <span wire:loading wire:target="register" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('auth.registering') }}
                </span>
            </button>
        </div>

        {{-- Login Link --}}
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                {{ __('auth.have_account') }}
            </a>
        </div>
    </form>
</div>
