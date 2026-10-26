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
        @if ($og = $page->ogImageUrl())
            <meta property="og:image" content="{{ $og }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif
        @if ($page->preview)
            <meta name="robots" content="noindex">
        @endif
        @stack('head')

        <script>document.documentElement.classList.add('js')</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-template="{{ $page->config->template }}" class="font-sans antialiased @yield('body-class', 'bg-paper text-ink')" style="{{ $page->theme->style() }}">
        @isset($useTemplateUrl)
            {{-- Public gallery preview (/templates/{key}): the template on sample data, one CTA. --}}
            <div data-gallery-bar class="fixed inset-x-0 top-0 z-50 flex h-14 items-center justify-between gap-3 bg-ink px-4 text-paper sm:px-6">
                <a href="{{ route('templates.index') }}" class="truncate text-sm font-medium text-paper/70 hover:text-paper">← <span class="hidden sm:inline">{{ __('All templates') }} · </span><span class="font-display text-lg font-bold uppercase text-paper">{{ config("eventflow.landing_templates.{$page->config->template}.label") }}</span></a>
                <a href="{{ $useTemplateUrl }}" class="shrink-0 bg-heat px-4 py-2 font-display text-lg font-bold uppercase tracking-[0.06em] text-on-heat hover:brightness-95">{{ __('Use this template') }}</a>
            </div>
            <div class="h-14" aria-hidden="true"></div>
        @elseif ($page->preview)
            <div class="relative z-50 bg-volt px-4 py-1.5 text-center text-xs font-semibold uppercase tracking-[0.14em] text-midnight">
                {{ __(':template · preview', ['template' => config("eventflow.landing_templates.{$page->config->template}.label")]) }}
            </div>
        @endif

        @yield('content')

        @include('landings.partials.sticky-register')

        {{-- Branded footer (Session P1); its bottom space clears the mobile sticky Register bar. --}}
        @include('landings.partials.footer')
    </body>
</html>
