{{-- EventFlow SaaS marketing shell (/, /pricing, /templates), Daylight Track. Not used by per-event landings. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', \App\Saas\Plans::brandName().' · '.__('Race registration and race-day ops for India'))</title>
        <meta name="description" content="@yield('description', __('Sports-grade event pages, payments into your own account, QR passes, volunteer check-in and finisher certificates for Indian running, cycling and skating events.'))">
        <meta name="theme-color" content="#F3F5FA">
        <script>document.documentElement.classList.add('js')</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-paper font-sans text-ink antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-ink">{{ __('Skip to content') }}</a>
        @include('marketing.partials.nav')

        <main id="main">
            @yield('content')
        </main>

        @include('marketing.partials.footer')
    </body>
</html>
