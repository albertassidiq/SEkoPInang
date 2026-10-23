<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SEkoPInang') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/livewire.js'])

    <!-- Styles -->
    @livewireStyles

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .coffee-pattern {
            background-image:
                radial-gradient(circle at 20% 80%, rgba(244, 64, 18, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(245, 135, 65, 0.05) 0%, transparent 50%);
        }

        .navbar-shadow {
            box-shadow: 0 4px 6px -1px rgba(63, 16, 0, 0.1), 0 2px 4px -1px rgba(63, 16, 0, 0.06);
        }
    </style>
</head>

<body class="antialiased font-poppins">
    <x-banner />

    <div class="min-h-screen bg-gradient-to-br from-cream-yellow/20 via-white to-light-brown/10 coffee-pattern">
        @livewire('navigation-menu')

        <!-- Page Heading -->
        @if (isset($header))
            <header
                class="border-b bg-gradient-to-r from-white/95 to-cream-yellow/20 backdrop-blur-sm border-primary-orange/10 navbar-shadow">
                <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="flex items-center space-x-3">
                        <div class="w-1 h-8 rounded-full bg-gradient-to-b from-primary-orange to-bright-orange"></div>
                        <div class="text-2xl font-bold font-poppins text-dark-brown">
                            {{ $header }}
                        </div>
                    </div>
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="relative">
            {{ $slot }}
        </main>
    </div>

    @stack('modals')

    @livewireScriptConfig
</body>

</html>
