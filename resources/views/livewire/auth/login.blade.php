<div class="isolate bg-white px-6 py-10 sm:py-12 lg:px-8">
    {{-- Decorative background gradient --}}
    {{-- <div aria-hidden="true" class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
        <div style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" class="relative left-1/2 -z-10 aspect-[1155/678] w-[36.125rem] max-w-none -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-40rem)] sm:w-[72.1875rem]"></div>
    </div> --}}

    {{-- Header --}}
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-2xl font-semibold tracking-tight text-balance text-gray-900 sm:text-3xl">{{ __('auth.login') }}</h2>
        <p class="mt-2 text-lg/8 text-gray-600">{{ __('auth.login_description') }}</p>
    </div>

    {{-- Login Form --}}
    <form wire:submit="authenticate" class="mx-auto mt-4 max-w-xl sm:mt-20">
        <div class="grid grid-cols-1 gap-x-8 gap-y-6">
            {{-- Email or Phone --}}
            <div>
                <label for="login" class="block text-sm/6 font-semibold text-gray-900">{{ __('auth.email_or_phone') }}</label>
                <div class="mt-2.5">
                    <input
                        wire:model="login"
                        type="text"
                        id="login"
                        placeholder="{{ __('auth.email_or_phone_placeholder') }}"
                        class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                    />
                </div>
                @error('login')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
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

            {{-- Remember Me --}}
            <div class="flex gap-x-4">
                <div class="flex h-6 items-center">
                    <input
                        wire:model="remember"
                        type="checkbox"
                        id="remember"
                        class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                    />
                </div>
                <label for="remember" class="text-sm/6 text-gray-600">
                    {{ __('auth.remember_me') }}
                </label>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="mt-10">
            <button
                type="submit"
                class="block w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="authenticate">{{ __('auth.login') }}</span>
                <span wire:loading wire:target="authenticate" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('auth.logging_in') }}
                </span>
            </button>
        </div>

        {{-- Register Link --}}
        <div class="mt-6 text-center">
            <a href="{{ route('register') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                {{ __('auth.no_account') }}
            </a>
        </div>
    </form>
</div>
