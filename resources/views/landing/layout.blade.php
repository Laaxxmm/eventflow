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

        <!-- Scripts -->
        <script>document.documentElement.classList.add('js')</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased bg-paper" style="{{ $theme->style() }}">
        @yield('content')

        <footer class="pb-32 pt-8 text-center text-xs text-muted">
            {{ __('Registrations powered by :app', ['app' => config('app.name', 'EventFlow')]) }}
        </footer>
    </body>
</html>
