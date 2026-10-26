{{--
    Gun Lap: full-bleed race photo, giant two-tone type (outlined line over a solid line) set
    low across the athletes, Heat Register in the sticky header and the mobile bar. Distances
    read like a start list; schedule and venue side by side; registration on ink.
--}}
@extends('landings.layout')

@section('body-class', 'bg-ink text-paper')

@push('head')
    <style>
        .gl-outline { -webkit-text-stroke: 2px rgb(var(--ri-paper)); color: transparent; }
        @media (min-width: 640px) { .gl-outline { -webkit-text-stroke-width: 3px; } }
    </style>
@endpush

@section('content')
    @php($words = preg_split('/\s+/', trim($page->config->name)) ?: [$page->config->name])
    @php($split = max(1, (int) ceil(count($words) / 2)))
    @include('landings.partials.header', ['dark' => true])

    <section id="top" class="relative isolate flex min-h-[100svh] items-end overflow-hidden">
        <x-ri.photo :src="$page->heroPhoto(0)" :sport="$page->config->sport->value" kenburns class="absolute inset-0 -z-20" :alt="$page->config->hero->title" position="center 30%" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/40 to-ink/10" aria-hidden="true"></div>

        <div class="mx-auto w-full max-w-7xl px-4 pb-28 pt-32 sm:px-6 sm:pb-16 lg:px-8">
            <div class="flex flex-wrap gap-2" data-reveal>
                <x-ri.chip tone="heat">{{ $page->date->format('j M Y') }}</x-ri.chip>
                <x-ri.chip tone="outline-light">{{ $page->config->venue }}</x-ri.chip>
                @if ($page->limited())
                    <x-ri.chip tone="paper">{{ __('Limited bibs') }}</x-ri.chip>
                @endif
            </div>
            <h1 class="ri-headline mt-6 text-[17vw] sm:text-[12vw] lg:text-[10.5rem]" data-reveal>
                <span class="gl-outline block">{{ implode(' ', array_slice($words, 0, $split)) }}</span>
                <span class="block">{{ implode(' ', array_slice($words, $split)) ?: $page->config->hero->title }}</span>
            </h1>
            <div class="mt-8 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                <p class="max-w-xl text-lg text-paper/80">{{ $page->config->hero->subtitle ?? $page->config->hero->title }}</p>
                @if ($page->open)
                    <a href="{{ $page->registerUrl() }}" class="inline-flex items-center justify-center gap-3 bg-heat px-9 py-4 font-display text-2xl font-bold uppercase tracking-[0.06em] text-on-heat hover:brightness-95">{{ $page->registerLabel() }} <span aria-hidden="true">→</span></a>
                @else
                    <p class="font-display text-2xl font-bold uppercase text-paper/60">{{ $page->closedLabel() }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Start list --}}
    <section class="bg-ink">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <p class="ri-kicker text-heat">{{ __('Distances') }}</p>
            <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Choose your race.') }}</h2>
            <ul class="mt-10 border-t border-paper/15">
                @foreach ($page->distances as $row)
                    <li data-reveal @class(['grid grid-cols-[auto_1fr] items-center gap-x-6 gap-y-2 border-b border-paper/15 py-6 sm:grid-cols-[10rem_1fr_auto_auto] sm:gap-8', 'opacity-50' => $row['full']])>
                        <span class="row-span-2 font-display text-6xl font-extrabold leading-none text-heat sm:row-span-1 sm:text-7xl">{{ \App\View\LandingPage::numeral($row['distance']) }}</span>
                        <span class="font-display text-2xl font-bold uppercase">{{ \App\View\LandingPage::title($row['distance']) }}</span>
                        <span class="text-sm text-paper/60 sm:text-right">{{ $row['full'] ? '' : $page->placesLabel($row['left']) }}</span>
                        <span class="flex items-center gap-4 sm:justify-end">
                            <span class="font-display text-3xl font-extrabold">{{ $row['full'] ? __('Full') : $row['price'] }}</span>
                            @unless ($row['full'] || ! $page->open)
                                <a href="{{ $page->registerUrl() }}" class="hidden bg-paper px-4 py-2 font-display text-lg font-bold uppercase text-ink hover:bg-heat hover:text-on-heat sm:inline-flex">{{ __('Enter') }}</a>
                            @endunless
                        </span>
                    </li>
                @endforeach
            </ul>
            @if ($page->ageCategories())
                <p class="mt-6 text-sm text-paper/60"><span class="font-semibold text-paper">{{ __('Categories') }}:</span> {{ $page->ageCategories() }}</p>
            @endif
        </div>
    </section>

    {{-- Schedule + venue --}}
    <section class="bg-paper text-ink">
        <div class="mx-auto grid max-w-7xl gap-14 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <p class="ri-kicker text-heat">{{ __('Race day') }}</p>
                <h2 class="ri-headline mt-4 text-6xl">{{ $page->date->format('l j F') }}</h2>
                <div class="mt-8">@include('landings.partials.schedule', ['dark' => false])</div>
            </div>
            <div class="flex flex-col">
                <x-ri.photo :src="$page->photo(1)" :sport="$page->config->sport->value" class="aspect-[4/3]" alt="" />
                <div class="mt-6 flex items-start justify-between gap-6">
                    <div>
                        <p class="ri-kicker text-ink/60">{{ __('Venue') }}</p>
                        <p class="mt-2 font-display text-3xl font-bold uppercase">{{ $page->config->venue }}</p>
                    </div>
                    <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="shrink-0 border-b-2 border-heat pb-0.5 text-sm font-semibold">{{ __('Open map') }} ↗</a>
                </div>
                @if ($page->config->charity)
                    <p class="mt-8 border-l-4 border-heat pl-4 text-ink/75">{{ $page->config->charity }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Register --}}
    <section id="register" class="scroll-mt-20 bg-ink">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
            <div>
                <p class="ri-kicker text-heat">{{ __('Entries') }}</p>
                <h2 class="ri-headline mt-4 text-7xl">{{ __('Get your bib.') }}</h2>
                <p class="mt-6 text-paper/70">{{ __('Your EF- pass with a QR code arrives by email as soon as payment is confirmed. Show it at bib collection.') }}</p>
            </div>
            <div>@include('landings.partials.register')</div>
        </div>
    </section>

    @if ($page->config->faq)
        <section class="bg-paper text-ink">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
                <h2 class="ri-headline text-6xl">{{ __('Before you run.') }}</h2>
                @include('landings.partials.faq', ['dark' => false])
            </div>
        </section>
    @endif

    <section class="border-t border-paper/15 bg-ink">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('landings.partials.sponsors', ['dark' => true])
        </div>
    </section>
@endsection
