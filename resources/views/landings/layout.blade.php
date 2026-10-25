{{--
    Shell for event landing templates (resources/views/landings/templates/*).
    $page: App\View\LandingPage. Sections: body-class, content. Stack: head (fonts etc.).
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

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
        @stack('head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-template="{{ $page->config->template }}" class="font-sans antialiased @yield('body-class', 'bg-white text-gray-900')" style="{{ $page->theme->style() }}">
        @if ($page->preview)
            <div class="sticky top-0 z-50 bg-gray-900 px-4 py-2 text-center text-xs font-semibold text-white">
                {{ __(':template template preview · sample content', ['template' => config("eventflow.landing_templates.{$page->config->template}.label")]) }}
            </div>
        @endif

        @yield('content')

        <footer class="py-8 text-center text-xs opacity-60">
            {{ __('Registrations powered by :app', ['app' => config('app.name', 'EventFlow')]) }}
        </footer>
    </body>
</html>
