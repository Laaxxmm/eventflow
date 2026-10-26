{{--
    Gun Lap (docs/design/daylight-track/GunLap.dc.html): a full-width race photo (indigo track graphic
    without one) with huge two-tone type and a white entry card; the start list right below; race
    morning beside the venue; a photo gallery; questions and partners; a brand band with the form.
    Phones (Event.dc.html): a brand card with the turning finisher medal, then cards on chalk.
--}}
@extends('landings.layout')

@section('body-class', 'bg-paper text-ink sm:bg-white')

@push('head')
    <style>
        .gl-outline { -webkit-text-stroke: 2px #fff; color: transparent; }
    </style>
@endpush

@section('content')
    @php
        $words = preg_split('/\s+/', trim($page->config->name)) ?: [$page->config->name];
        $split = max(1, (int) ceil(count($words) / 2));
        $hero = $page->heroPhoto(0);
        $days = $page->daysToGo();
        $short = \Illuminate\Support\Str::of($page->config->name)->replaceMatches('/\d+/', '')->explode(' ')->filter()->take(3)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    @endphp
    @include('landings.partials.header', ['dark' => true])

    {{-- Phones: brand card with the medal. --}}
    <section class="px-3 pt-[72px] sm:hidden" aria-label="{{ $page->config->name }}">
        <div class="relative overflow-hidden rounded-[28px] bg-track px-[22px] pb-[30px] pt-7 text-on-track">
            <svg aria-hidden="true" width="520" height="300" viewBox="0 0 520 300" class="absolute -bottom-[150px] -left-[60px]">
                @foreach ([0, 18, 36, 54] as $o)
                    <rect x="{{ $o }}" y="{{ $o }}" width="{{ 520 - 2 * $o }}" height="{{ 300 - 2 * $o }}" rx="{{ 150 - $o }}" fill="none" stroke="currentColor" stroke-opacity=".18" stroke-width="1.5"/>
                @endforeach
            </svg>
            <x-ri.medal :short="$short ?: 'EF'" :year="$page->date->format('Y')" />
            <div class="relative flex flex-col gap-3">
                <p class="text-sm opacity-85" data-hero-meta>{{ $page->date->format('l j F Y, g:i a') }}@if ($page->limited()) · <span class="font-semibold">{{ __('Limited bibs') }}</span>@endif</p>
                <h1 class="ri-headline text-[40px]">{{ $page->config->name }}</h1>
                <p class="text-[15px] leading-normal opacity-85">{{ $page->config->hero->subtitle ?? $page->config->hero->title }} {{ $page->config->venue }}.</p>
                <div class="mt-1.5 flex gap-2.5">
                    <div class="flex-1 rounded-2xl bg-white/[.12] px-3.5 py-3"><span class="ri-headline block text-[28px]">{{ $days }}</span><span class="text-[13px] opacity-85">{{ __('days to go') }}</span></div>
                    <div class="flex-1 rounded-2xl bg-white/[.12] px-3.5 py-3"><span class="ri-headline block text-[28px]">{{ $page->fromPrice() }}</span><span class="text-[13px] opacity-85">{{ __('entries from') }}</span></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Desktop: full-width photo hero. --}}
    <section id="top" class="relative isolate hidden min-h-[720px] flex-col overflow-hidden text-white sm:flex">
        <div class="absolute inset-0 -z-20 bg-[linear-gradient(160deg,#8C86F0_0%,rgb(var(--ri-track))_50%,#241D7A_100%)]" aria-hidden="true">
            <svg width="100%" height="100%" viewBox="0 0 1440 720" preserveAspectRatio="xMidYMid slice" class="absolute inset-0"><path d="M-40 600 C 300 520, 700 560, 1480 420" stroke="#fff" stroke-opacity=".18" stroke-width="2" fill="none"/><path d="M-40 640 C 320 560, 720 600, 1480 460" stroke="#fff" stroke-opacity=".14" stroke-width="2" fill="none"/><circle cx="1160" cy="180" r="120" fill="#F2A93B" fill-opacity=".45"/></svg>
        </div>
        @if ($hero)
            <img src="{{ $hero }}" alt="{{ $page->config->hero->title }}" class="ri-grade absolute inset-0 -z-20 h-full w-full object-cover" style="object-position: 70% 50%" onerror="this.remove()">
        @endif
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgb(18_26_51/.25)_0%,rgb(18_26_51/0)_30%,rgb(18_26_51/.55)_100%)]" aria-hidden="true"></div>

        <div class="mx-auto mt-auto flex w-full max-w-[1280px] flex-wrap items-end justify-between gap-8 px-10 pb-14 pt-32">
            <div class="flex max-w-[54rem] flex-col gap-[18px]">
                <p class="text-base font-semibold">{{ $page->config->venue }}. {{ __('Flag-off :time.', ['time' => $page->date->format('g:i a')]) }}</p>
                <div class="hidden flex-wrap gap-2 sm:flex">
                    <x-ri.chip tone="live">{{ $page->date->format('j M Y') }}</x-ri.chip>
                    @if ($page->limited())
                        <x-ri.chip tone="neutral">{{ __('Limited bibs') }}</x-ri.chip>
                    @endif
                </div>
                <h1 class="ri-headline text-[clamp(56px,8vw,128px)] leading-[.92]">
                    <span class="gl-outline block">{{ implode(' ', array_slice($words, 0, $split)) }}</span>
                    <span class="block">{{ implode(' ', array_slice($words, $split)) ?: $page->config->hero->title }}</span>
                </h1>
            </div>
            <div class="ri-float flex min-w-[260px] flex-col gap-3 rounded-[24px] bg-white p-[22px] text-ink">
                <span class="text-sm text-muted">{{ __('Entries from') }}</span>
                <span class="ri-headline text-[40px] leading-none">{{ $page->fromPrice() }}</span>
                @if ($page->open)
                    <a href="{{ $page->registerUrl() }}" data-hero-cta class="ri-glow inline-flex min-h-[52px] items-center justify-center rounded-full bg-track px-6 font-bold text-on-track hover:brightness-95">{{ __('Register now') }}</a>
                @else
                    <p class="font-bold text-muted">{{ $page->closedLabel() }}</p>
                @endif
                <span class="text-[13px] text-muted">{{ trans_choice('{0} Race day today|{1} 1 day to go|[2,*] :count days to go', $days) }}</span>
            </div>
        </div>
    </section>

    @include('landings.partials.countdown')

    {{-- Start list --}}
    <section class="mx-auto max-w-[1280px] px-5 pb-2 pt-10 sm:px-10 sm:pb-10 sm:pt-[88px]">
        <div class="flex flex-wrap items-end justify-between gap-2 sm:gap-5">
            <h2 class="ri-headline text-[28px] sm:text-5xl"><span class="sm:hidden">{{ __('Pick your distance') }}</span><span class="hidden sm:inline">{{ __('Start list') }}</span></h2>
            @if ($page->ageCategories())
                <p class="text-sm text-muted sm:text-[15px]">{{ __('Age categories: :list.', ['list' => $page->ageCategories()]) }}</p>
            @endif
        </div>
        <div class="mt-5 flex flex-col gap-2.5 sm:mt-8 sm:gap-0">
            @foreach ($page->distances as $i => $row)
                @php
                    $low = ! $row['full'] && $row['left'] !== null && $row['left'] <= 10;
                    $tag = $row['full'] || ! $page->open ? 'div' : 'a';
                @endphp
                <{{ $tag }} @if ($tag === 'a') href="{{ $page->registerUrl() }}" @endif @class([
                    'grid grid-cols-[40px_1fr_auto] items-center gap-3.5 rounded-[20px] p-4 sm:grid-cols-[220px_1fr_auto_auto] sm:gap-6 sm:rounded-none sm:border-t sm:border-mist sm:px-0 sm:py-[26px] sm:last:border-b',
                    'bg-white text-ink sm:bg-transparent' => ! $row['full'],
                    'bg-[#E9ECF4] text-muted sm:bg-transparent' => $row['full'],
                    'ring-2 ring-heat sm:ring-0' => $low,
                ])>
                    <span aria-hidden="true" @class(['flex h-10 w-10 items-center justify-center rounded-full text-[13px] font-bold sm:hidden', 'bg-heat text-on-heat' => $low, 'bg-lilac text-track-deep' => ! $low && ! $row['full'], 'bg-mist text-muted' => $row['full']])>L{{ $i + 1 }}</span>
                    <span @class(['ri-headline hidden text-[64px] leading-none sm:block', 'text-track-text' => ! $row['full'], 'text-[#B4BBCF]' => $row['full']])>{{ \App\View\LandingPage::numeral($row['distance']) }}</span>
                    <span class="flex min-w-0 flex-col gap-0.5">
                        <span class="text-base font-bold sm:text-lg sm:font-semibold">{{ \App\View\LandingPage::title($row['distance']) }}</span>
                        <span @class(['text-[13px] sm:hidden', 'font-semibold text-warning' => $low])>{{ $row['full'] ? __('Sold out') : ($low ? __('Only :n left', ['n' => $page->placesLabel($row['left'])]) : $page->placesLabel($row['left'])) }}</span>
                    </span>
                    <span @class(['hidden text-[15px] sm:inline', 'rounded-full bg-[#FDF0DA] px-3 py-1.5 font-bold text-warning' => $low, 'text-muted' => ! $low])>{{ $row['full'] ? __('Sold out') : $page->placesLabel($row['left']) }}</span>
                    <span @class(['text-right sm:min-w-[120px]', 'ri-headline text-xl sm:text-[28px]' => ! $row['full'], 'text-[15px] font-semibold line-through sm:text-xl' => $row['full']])>{{ $row['price'] }}</span>
                </{{ $tag }}>
            @endforeach
        </div>
    </section>

    {{-- Race morning + venue --}}
    <section class="mx-auto grid max-w-[1280px] gap-7 px-5 pt-10 sm:px-10 sm:py-14 sm:[grid-template-columns:repeat(auto-fit,minmax(380px,1fr))]">
        <div>
            <h2 class="ri-headline mb-5 text-[28px] sm:hidden">{{ __('Race morning') }}</h2>
            <div class="rounded-[22px] bg-white p-[18px] sm:rounded-[28px] sm:bg-paper sm:p-8">
                <h2 class="ri-headline mb-5 hidden text-[32px] sm:block">{{ __('Race morning') }}</h2>
                @include('landings.partials.schedule', ['dark' => false])
            </div>
            @if ($page->config->charity)
                <p class="mt-4 px-1 text-sm leading-relaxed text-muted sm:text-base">{{ $page->config->charity }}</p>
            @endif
        </div>
        @include('landings.partials.venue-map', ['class' => 'sm:min-h-[360px]'])
    </section>

    {{-- Gallery: slots 05, 06, 04, 03 --}}
    <section class="mx-auto hidden max-w-[1280px] px-10 pb-14 pt-6 sm:block">
        <h2 class="ri-headline mb-6 text-[32px]">{{ __('On the course') }}</h2>
        <div class="grid auto-rows-[200px] grid-cols-[2fr_1fr_1fr] gap-4">
            <x-ri.photo :src="$page->photo(4)" :sport="$page->config->sport->value" class="row-span-2 rounded-[24px]" focus="center 35%" alt="" />
            <x-ri.photo :src="$page->photo(5)" :sport="$page->config->sport->value" class="rounded-[24px]" alt="" />
            <x-ri.photo :src="$page->photo(3)" :sport="$page->config->sport->value" class="rounded-[24px]" alt="" />
            <x-ri.photo :src="$page->photo(2)" :sport="$page->config->sport->value" class="col-span-2 rounded-[24px]" alt="" />
        </div>
    </section>

    {{-- Questions + partners --}}
    <section class="mx-auto grid max-w-[1280px] gap-10 px-5 pb-10 pt-10 sm:px-10 sm:pb-[72px] sm:pt-8 sm:[grid-template-columns:repeat(auto-fit,minmax(380px,1fr))]">
        @if ($page->config->faq)
            <div>
                <h2 class="ri-headline mb-4 text-[28px] sm:text-[32px]">{{ __('Questions') }}</h2>
                <div class="sm:hidden">@include('landings.partials.faq', ['dark' => false, 'cards' => true])</div>
                <div class="hidden sm:block">@include('landings.partials.faq', ['dark' => false])</div>
            </div>
        @endif
        <div>
            <h2 class="mb-3.5 text-[15px] font-bold text-muted sm:ri-headline sm:mb-4 sm:text-[32px] sm:text-ink">{{ __('Race partners') }}</h2>
            @include('landings.partials.sponsors', ['dark' => false, 'cardClass' => 'bg-white text-ink sm:bg-paper'])
        </div>
    </section>

    {{-- Register --}}
    <section id="register" class="scroll-mt-20 bg-track text-on-track">
        <div class="mx-auto grid max-w-[1280px] gap-8 px-3 py-12 sm:px-10 sm:py-14 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="px-2 sm:px-0">
                <h2 class="ri-headline text-[34px] sm:text-[44px]">{{ __('Your bib is waiting.') }}</h2>
                <p class="mt-4 max-w-md opacity-85">{{ __('Your EF- pass with a QR code arrives by email as soon as payment is confirmed. Show it at bib collection.') }}</p>
            </div>
            <div>@include('landings.partials.register')</div>
        </div>
    </section>
@endsection
