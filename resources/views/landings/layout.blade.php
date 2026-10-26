{{--
    Shell for event landing templates (resources/views/landings/templates/*).
    $page: App\View\LandingPage. Sections: body-class, content. Stack: head.
    Race Ink tokens are tinted per event by App\Landing\Theme::style() (contrast-checked).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $page->config->name }} · {{ $page->orgName }}</title>
        <meta name="description" content="{{ $page->config->hero->subtitle ?? $page->config->name }}">
        <meta property="og:title" content="{{ $page->config->name }}">
        <meta property="og:description" content="{{ $page->date->format('j M Y') }} · {{ $page->config->venue }}">
        @if ($page->heroUrl())
            <meta property="og:image" content="{{ $page->heroUrl() }}">
        @endif
        @if ($page->preview)
            <meta name="robots" content="noindex">
        @endif
        @stack('head')

        <script>document.documentElement.classList.add('js')</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-template="{{ $page->config->template }}" class="font-sans antialiased @yield('body-class', 'bg-paper text-ink')" style="{{ $page->theme->style() }}">
        @if ($page->preview)
            <div class="relative z-50 bg-volt px-4 py-1.5 text-center text-xs font-semibold uppercase tracking-[0.14em] text-midnight">
                {{ __(':template · preview', ['template' => config("eventflow.landing_templates.{$page->config->template}.label")]) }}
            </div>
        @endif

        @yield('content')

        @include('landings.partials.sticky-register')

        <footer class="pb-28 pt-8 text-center text-xs opacity-60 sm:pb-8">
            {{ __('Registrations powered by :app', ['app' => config('app.name', 'EventFlow')]) }}
        </footer>
    </body>
</html>
