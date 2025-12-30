<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 antialiased">
    <div class="min-h-screen">
        <nav class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ config('app.name', 'NCT') }}
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        @auth
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ auth()->user()->name }}
                            </span>
                            @livewire('auth.logout')
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors cursor-pointer">
                                Login
                            </a>
                            <a href="{{ route('register') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition-colors cursor-pointer">
                                Register
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-12">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
