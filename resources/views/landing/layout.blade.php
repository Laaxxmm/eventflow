{{-- Branded public pages (event landing, org page, review). $theme: App\Landing\Theme --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title') · {{ config('app.name', 'EventFlow') }}</title>
        @hasSection('description')
            <meta name="description" content="@yield('description')">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50" style="{{ $theme->style() }}">
        @yield('content')

        <footer class="py-8 text-center text-xs text-gray-500">
            {{ __('Registrations powered by :app', ['app' => config('app.name', 'EventFlow')]) }}
        </footer>
    </body>
</html>
