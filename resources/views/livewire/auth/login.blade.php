<div>
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Login</h2>

    <form wire:submit="authenticate">
        <div class="mb-4">
            <label for="login" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email or Phone Number</label>
            <input wire:model="login" type="text" id="login" placeholder="email@example.com or +7 777 123 4567" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @error('login') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
            <input wire:model="password" type="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="flex items-center">
                <input wire:model="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
            </label>
        </div>

        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 cursor-pointer" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="authenticate">Login</span>
            <span wire:loading wire:target="authenticate">Logging in...</span>
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('register') }}" class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
            Don't have an account? Register
        </a>
    </div>
</div>
