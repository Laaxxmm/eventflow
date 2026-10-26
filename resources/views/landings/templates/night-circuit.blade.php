{{--
    Night Circuit: midnight canvas, volt accents used sparingly, an oversized date as the hero
    and one bright Register. Distances as lit lanes; countdown; schedule; registration on dark.
--}}
@extends('landings.layout')

@section('body-class', 'bg-midnight text-paper')

@section('content')
    @include('landings.partials.header', ['dark' => true])

    <section id="top" class="relative isolate overflow-hidden">
        <x-ri.photo :src="$page->heroPhoto(1)" :sport="$page->config->sport->value" class="absolute inset-y-0 right-0 -z-20 w-full opacity-60 lg:w-3/5" :alt="$page->config->hero->title" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-midnight via-midnight/85 to-midnight/30" aria-hidden="true"></div>

        <div class="mx-auto grid min-h-[100svh] max-w-7xl items-end gap-10 px-4 pb-28 pt-28 sm:px-6 sm:pb-20 lg:grid-cols-[auto_1fr] lg:items-center lg:px-8">
            <div class="font-display font-extrabold uppercase leading-[0.8] text-volt" data-reveal aria-hidden="true">
                <p class="text-[34vw] sm:text-[18rem]">{{ $page->date->format('d') }}</p>
                <p class="text-[17vw] sm:text-[8.5rem]">{{ $page->date->format('M') }}</p>
            </div>
            <div>
                <p class="ri-kicker text-volt" data-reveal>{{ $page->date->format('l · g:i A') }}</p>
                <h1 class="ri-headline mt-5 text-6xl sm:text-8xl" data-reveal>{{ $page->config->name }}</h1>
                <p class="mt-6 max-w-lg text-lg text-paper/75" data-reveal>{{ $page->config->hero->subtitle ?? $page->config->hero->title }}</p>
                <p class="mt-3 text-sm text-paper/55" data-reveal>{{ $page->config->venue }}</p>
                @if ($page->open)
                    <a href="{{ $page->registerUrl() }}" data-reveal class="mt-10 inline-flex items-center gap-3 bg-volt px-9 py-4 font-display text-2xl font-bold uppercase tracking-[0.06em] text-midnight shadow-[0_0_40px_rgb(var(--ri-volt)/0.35)] hover:brightness-105">{{ $page->registerLabel() }} <span aria-hidden="true">→</span></a>
                @else
                    <p class="mt-10 font-display text-2xl font-bold uppercase text-paper/50">{{ __('Entries closed') }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Lanes --}}
    <section class="border-t border-paper/10">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="ri-kicker text-volt">{{ __('Distances') }}</p>
                    <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Find your lane.') }}</h2>
                </div>
                @if ($page->daysToGo() > 0)
                    <p class="font-display text-5xl font-extrabold uppercase">{{ $page->daysToGo() }} <span class="text-2xl text-paper/55">{{ __('days to go') }}</span></p>
                @endif
            </div>
            <ol class="mt-10 space-y-3">
                @foreach ($page->distances as $i => $row)
                    <li data-reveal @class(['grid grid-cols-[3rem_1fr_auto] items-center gap-4 border-l-4 bg-paper/[0.04] px-5 py-5 sm:grid-cols-[5rem_8rem_1fr_auto] sm:gap-8', 'border-volt' => ! $row['full'], 'border-paper/20 opacity-50' => $row['full']])>
                        <span class="font-display text-sm font-bold uppercase text-paper/45">{{ __('Lane') }} {{ $i + 1 }}</span>
                        <span class="hidden font-display text-5xl font-extrabold text-volt sm:block">{{ \App\View\LandingPage::numeral($row['distance']) }}</span>
                        <span>
                            <span class="block font-display text-2xl font-bold uppercase">{{ $row['distance']->label }}</span>
                            <span class="block text-sm text-paper/55">{{ $page->placesLabel($row['left']) }}</span>
                        </span>
                        <span class="font-display text-3xl font-extrabold">{{ $row['full'] ? __('Full') : $row['price'] }}</span>
                    </li>
                @endforeach
            </ol>
            @if ($page->ageCategories())
                <p class="mt-6 text-sm text-paper/55"><span class="font-semibold text-paper">{{ __('Categories') }}:</span> {{ $page->ageCategories() }}</p>
            @endif
        </div>
    </section>

    {{-- Schedule --}}
    <section class="border-t border-paper/10">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <p class="ri-kicker text-volt">{{ __('Race night') }}</p>
                <h2 class="ri-headline mt-4 text-6xl">{{ __('The run sheet.') }}</h2>
                <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="mt-6 inline-block border-b-2 border-volt pb-0.5 text-sm font-semibold">{{ $page->config->venue }} ↗</a>
                @if ($page->config->charity)
                    <p class="mt-8 border-l-4 border-volt pl-4 text-paper/70">{{ $page->config->charity }}</p>
                @endif
            </div>
            @include('landings.partials.schedule', ['dark' => true])
        </div>
    </section>

    <section id="register" class="scroll-mt-20 border-t border-paper/10">
        <div class="mx-auto max-w-4xl px-4 py-20 sm:px-6">
            <p class="ri-kicker text-volt">{{ __('Entries') }}</p>
            <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Line up.') }}</h2>
            @include('landings.partials.register')
        </div>
    </section>

    @if ($page->config->faq)
        <section class="border-t border-paper/10">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
                <h2 class="ri-headline text-6xl">{{ __('Questions.') }}</h2>
                @include('landings.partials.faq', ['dark' => true])
            </div>
        </section>
    @endif

    <section class="border-t border-paper/10">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">@include('landings.partials.sponsors', ['dark' => true])</div>
    </section>
@endsection
