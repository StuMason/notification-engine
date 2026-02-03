<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Notification Engine') }} — @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <nav class="border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <div class="flex h-14 items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('notifications.index') }}" class="text-sm font-semibold tracking-tight text-gray-900">
                        {{ config('app.name') }}
                    </a>
                    @auth
                        <span class="hidden text-xs text-gray-400 sm:inline">{{ auth()->user()->hotel?->name }}</span>
                    @endauth
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <span class="hidden sm:inline">Notifications</span>
                            <x-unread-badge />
                        </a>
                        <span class="text-xs text-gray-400">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        @yield('content')
    </main>
</body>
</html>
