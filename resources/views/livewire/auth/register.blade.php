<div>
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Register</h2>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="register">
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input wire:model="name" type="text" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input wire:model="email" type="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <x-sms-verification
                phone-model="phone_number"
                code-model="verification_code"
                :code-sent="$codeSent"
                :code-verified="$codeVerified"
                :remaining-seconds="$this->getRemainingSeconds()"
                phone-placeholder="+7 777 123 4567"
            />
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
            <input wire:model="password" type="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
            <input wire:model="password_confirmation" type="password" id="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 cursor-pointer" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">Register</span>
            <span wire:loading wire:target="register">Registering...</span>
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
            Already have an account? Login
        </a>
    </div>
</div>
