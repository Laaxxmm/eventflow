{{--
    Night Circuit (docs/design/daylight-track/NightCircuit.dc.html): a rounded dusk hero (slot 01, dusk
    shot preferred) with moving light lanes and an oversized date, lane cards for the distances, race
    night on a white card, a course photo (slot 04) beside the questions, and a dusk band with the form.
    The page itself stays light; only the hero and the register band are dusk.
--}}
@extends('landings.layout')

@section('body-class', 'bg-[#F1F2F9] text-ink')

@section('content')
    @php
        $photo = $page->heroPhoto(0);
        $last = array_key_last($page->distances);
    @endphp

    <section id="top" class="relative isolate m-2 flex min-h-[640px] flex-col overflow-hidden rounded-[28px] bg-[linear-gradient(180deg,#3B4A8C_0%,rgb(var(--ri-midnight))_70%)] text-white sm:m-4 sm:min-h-[700px] sm:rounded-[40px]">
        @if ($photo)
            <img src="{{ $photo }}" alt="{{ $page->config->hero->title }}" class="ri-grade absolute inset-0 -z-10 h-full w-full object-cover opacity-55" style="object-position: center 60%" onerror="this.remove()">
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-[rgb(var(--ri-midnight))] via-[rgb(var(--ri-midnight)/.6)] to-transparent" aria-hidden="true"></div>
        @endif
        <svg aria-hidden="true" width="100%" height="100%" viewBox="0 0 1400 700" preserveAspectRatio="xMidYMid slice" class="absolute inset-0 -z-10" data-motion>
            <path class="ef-lane" d="M-50 640 C 400 560, 800 600, 1450 380" stroke="rgb(var(--ri-heat))" stroke-width="3" fill="none" stroke-dasharray="120 120"/>
            <path class="ef-lane" d="M-50 680 C 420 600, 820 640, 1450 420" stroke="#fff" stroke-opacity=".5" stroke-width="2" fill="none" stroke-dasharray="80 160"/>
            <path class="ef-lane" d="M-50 600 C 380 520, 780 560, 1450 340" stroke="#8C86F0" stroke-width="2" fill="none" stroke-dasharray="60 180"/>
        </svg>

        <div class="flex items-center justify-between gap-4 px-5 py-5 sm:px-10 sm:py-6">
            <a href="#top" class="flex min-w-0 items-center gap-2.5">
                @if ($page->logoUrl())
                    <span class="inline-flex items-center rounded-[12px] bg-white px-2.5 py-1.5">@include('landings.partials.logo', ['class' => 'h-8', 'textClass' => 'ri-headline text-lg text-ink'])</span>
                @else
                    <span class="ri-headline truncate text-xl">{{ $page->orgName }}</span>
                @endif
            </a>
            @if ($page->open)
                <a href="{{ $page->registerUrl() }}" class="inline-flex min-h-[44px] items-center rounded-full bg-heat px-5 text-sm font-bold text-on-heat hover:brightness-95">{{ __('Register') }}</a>
            @endif
        </div>

        <div class="mt-auto flex flex-wrap items-end gap-8 px-5 pb-10 sm:gap-12 sm:px-10 sm:pb-[60px]">
            <div class="ri-headline text-[clamp(110px,15vw,220px)] leading-[.8] text-heat" aria-hidden="true">{{ $page->date->format('j') }}<span class="block text-[.42em] text-white">{{ $page->date->format('F') }}</span></div>
            <div class="flex flex-[1_1_380px] flex-col gap-[18px] pb-3">
                <p class="text-base text-[#C9CDE0]">{{ $page->date->format('l') }}, {{ __('flag-off :time', ['time' => $page->date->format('g:i a')]) }}. {{ $page->config->venue }}</p>
                <h1 class="ri-headline text-[clamp(40px,5vw,76px)] leading-[.98]">{{ $page->config->name }}</h1>
                <div class="flex flex-wrap items-center gap-4">
                    @if ($page->open)
                        <a href="{{ $page->registerUrl() }}" data-hero-cta class="inline-flex min-h-[56px] items-center rounded-full bg-heat px-7 text-[17px] font-bold text-on-heat hover:brightness-95">{{ __('Register from :price', ['price' => $page->fromPrice()]) }}</a>
                    @else
                        <p class="text-lg font-bold text-[#C9CDE0]">{{ $page->closedLabel() }}</p>
                    @endif
                    <span class="text-[15px] text-[#C9CDE0]">{{ trans_choice('{0} Race day today|{1} 1 day to go|[2,*] :count days to go', $page->daysToGo()) }}</span>
                </div>
            </div>
        </div>
    </section>

    @include('landings.partials.countdown')

    {{-- Lanes --}}
    <section class="mx-auto max-w-[1280px] px-5 pb-8 pt-12 sm:px-10 sm:pt-16">
        <h2 class="ri-headline mb-7 text-[32px] sm:text-[44px]">{{ __('Find your lane') }}</h2>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr))]">
            @foreach ($page->distances as $i => $row)
                @php
                    $dark = $i === $last && ! $row['full'];
                    $tag = $row['full'] || ! $page->open ? 'div' : 'a';
                @endphp
                <{{ $tag }} @if ($tag === 'a') href="{{ $page->registerUrl() }}" @endif @class([
                    'flex flex-col gap-2.5 rounded-[28px] p-7',
                    'bg-[rgb(var(--ri-midnight))] text-white' => $dark,
                    'bg-white text-ink' => ! $dark && ! $row['full'],
                    'bg-[#E9ECF4] text-muted' => $row['full'],
                ])>
                    <span @class(['text-sm', 'text-[#C9CDE0]' => $dark, 'text-muted' => ! $dark])>{{ __('Lane :n', ['n' => $i + 1]) }}</span>
                    <span @class(['ri-headline text-[56px] leading-none', 'text-heat' => $dark, 'text-[#9AA2BA]' => $row['full']])>{{ \App\View\LandingPage::numeral($row['distance']) }}</span>
                    <span class="font-semibold">{{ \App\View\LandingPage::title($row['distance']) }}</span>
                    <span class="mt-3 flex items-center justify-between gap-2">
                        <span @class(['ri-headline text-2xl' => ! $row['full'], 'text-lg line-through' => $row['full']])>{{ $row['price'] }}</span>
                        <span @class(['text-sm', 'text-[#C9CDE0]' => $dark, 'text-muted' => ! $dark, '!font-semibold !text-warning' => ! $dark && ! $row['full'] && $row['left'] !== null && $row['left'] <= 10])>{{ $row['full'] ? __('Sold out') : $page->placesLabel($row['left']) }}</span>
                    </span>
                </{{ $tag }}>
            @endforeach
        </div>
        @if ($page->ageCategories())
            <p class="mt-5 text-sm text-muted">{{ __('Categories: :list.', ['list' => $page->ageCategories()]) }}</p>
        @endif
    </section>

    {{-- Race night --}}
    <section class="mx-auto max-w-[1280px] px-5 py-8 sm:px-10 sm:py-10">
        <div class="flex flex-col gap-5 rounded-[32px] bg-white p-6 sm:p-9">
            <div class="flex flex-wrap justify-between gap-4"><h2 class="ri-headline text-[28px] sm:text-[32px]">{{ __('Race night') }}</h2><a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center text-[15px] font-semibold text-track-text hover:underline">{{ $page->config->venue }}</a></div>
            <svg aria-hidden="true" width="100%" height="120" viewBox="0 0 1200 200" preserveAspectRatio="none" class="block">
                <path d="M0 180 L 120 170 L 220 60 L 300 150 L 460 140 L 560 40 L 640 150 L 820 160 L 920 30 L 1000 140 L 1200 175 L 1200 200 L 0 200 Z" fill="rgb(var(--ri-lilac))"/>
                <path d="M0 180 L 120 170 L 220 60 L 300 150 L 460 140 L 560 40 L 640 150 L 820 160 L 920 30 L 1000 140 L 1200 175" fill="none" stroke="rgb(var(--ri-track))" stroke-width="3"/>
                <circle cx="920" cy="30" r="7" fill="rgb(var(--ri-heat))"/>
            </svg>
            <ol class="grid gap-3 text-[15px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                @foreach ($page->schedule() as $row)
                    <li><strong class="ri-headline block text-xl">{{ $row['time'] }}</strong>{{ $row['label'] }}</li>
                @endforeach
            </ol>
            @if ($page->config->charity)
                <p class="text-sm text-muted">{{ $page->config->charity }}</p>
            @endif
        </div>
    </section>

    {{-- Course photo + questions --}}
    <section class="mx-auto grid max-w-[1280px] gap-6 px-5 pb-16 pt-8 sm:px-10 sm:[grid-template-columns:repeat(auto-fit,minmax(380px,1fr))]">
        <div class="relative min-h-[280px] overflow-hidden rounded-[32px] bg-[linear-gradient(160deg,#6E78C8,rgb(var(--ri-midnight)))] sm:min-h-[320px]">
            @if ($course = $page->photo(3))
                <img src="{{ $course }}" alt="" class="ri-grade absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
            @endif
        </div>
        <div class="flex flex-col gap-2.5">
            <h2 class="ri-headline mb-1.5 text-[28px] sm:text-[32px]">{{ __('Before you ride') }}</h2>
            @include('landings.partials.faq', ['dark' => false, 'cards' => true])
            <div class="mt-4">@include('landings.partials.sponsors', ['dark' => false, 'cardClass' => 'bg-white text-ink'])</div>
        </div>
    </section>

    {{-- Register --}}
    <section id="register" class="m-2 scroll-mt-20 rounded-[28px] bg-[rgb(var(--ri-midnight))] text-white sm:m-4 sm:rounded-[40px]">
        <div class="mx-auto grid max-w-[1280px] gap-8 px-3 py-12 sm:px-10 sm:py-14 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="px-2 sm:px-0">
                <h2 class="ri-headline text-[32px] sm:text-[40px]">{{ __('Lights on. See you at the start.') }}</h2>
                <p class="mt-4 max-w-md text-[#C9CDE0]">{{ __('Your EF- pass with a QR code arrives by email as soon as payment is confirmed. Show it at bib collection.') }}</p>
            </div>
            <div>@include('landings.partials.register')</div>
        </div>
    </section>
@endsection
