{{--
    Heritage Crest (docs/design/daylight-track/HeritageCrest.dc.html): club-formal and centred. A crest
    (the organiser's logo in a double ring, else drawn laurels and initials), Cormorant Garamond headings,
    a framed venue photo (slot 04), ruled frames for the distances and the order of events, notices,
    and a band with the form. Ivory page; house colour burgundy unless the organiser set their own.
--}}
@extends('landings.layout')

@section('body-class', 'bg-[#FBFAF6] text-[#231A14]')

@push('head')
    @if (strtolower($page->config->branding->primary) === \App\Landing\Theme::RI_TRACK)
        <style>body[data-template] { --ri-track: 122 31 43 !important; --ri-track-text: 122 31 43 !important; --ri-track-deep: 79 18 27 !important; --ri-on-track: 255 255 255 !important; }</style>
    @endif
@endpush

@section('content')
    @php
        $frame = 'border border-[#C9B993]';
        $sub = 'text-[#6B5B4A]';
        $initials = \Illuminate\Support\Str::of($page->orgName)->explode(' ')->filter()->take(3)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    @endphp
    <header class="mx-auto flex max-w-[1200px] items-center justify-between gap-4 border-b border-[#E4DCCB] px-5 py-5 sm:px-10">
        <span class="ri-serif truncate text-2xl font-bold">{{ $page->orgName }}</span>
        @if ($page->open)
            <a href="{{ $page->registerUrl() }}" class="inline-flex min-h-[44px] shrink-0 items-center rounded-full bg-track px-[22px] font-semibold text-on-track hover:brightness-110">{{ __('Register') }}</a>
        @endif
    </header>

    <section id="top" class="mx-auto flex max-w-[1200px] flex-col items-center gap-[22px] px-5 pb-10 pt-12 text-center sm:px-10 sm:pt-14">
        @if ($page->logoUrl())
            <div class="flex h-[150px] w-[150px] items-center justify-center rounded-full bg-white p-6 ring-2 ring-track ring-offset-4 ring-offset-[#FBFAF6]">
                @include('landings.partials.logo', ['class' => 'max-h-20', 'textClass' => 'ri-serif text-xl font-bold'])
            </div>
        @else
            <svg role="img" aria-label="{{ __(':org crest', ['org' => $page->orgName]) }}" width="150" height="150" viewBox="0 0 150 150" class="text-track-text">
                <circle cx="75" cy="75" r="70" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="75" cy="75" r="62" fill="none" stroke="currentColor" stroke-width="1"/>
                <path d="M40 100 C 30 80, 32 58, 46 42 M110 100 C 120 80, 118 58, 104 42" stroke="#B8761A" stroke-width="3" fill="none" stroke-linecap="round"/>
                <path d="M44 92 l -8 -4 M40 78 l -8 -2 M41 64 l -8 0 M45 51 l -7 2 M106 92 l 8 -4 M110 78 l 8 -2 M109 64 l 8 0 M105 51 l 7 2" stroke="#B8761A" stroke-width="3" stroke-linecap="round"/>
                <text x="75" y="84" text-anchor="middle" font-family="Cormorant Garamond, Georgia, serif" font-size="30" font-weight="700" fill="currentColor">{{ $initials }}</text>
            </svg>
        @endif
        <p class="text-[15px] {{ $sub }}">{{ $page->date->format('l j F Y') }}, {{ $page->config->venue }}</p>
        <h1 class="ri-serif max-w-[52rem] text-[clamp(52px,7vw,104px)] font-bold leading-[.95] tracking-[-0.01em]">{{ $page->config->name }}</h1>
        <p class="max-w-[36rem] text-lg leading-relaxed {{ $sub }}">{{ $page->config->hero->subtitle ?? $page->config->hero->title }}</p>
        @if ($page->open)
            <a href="{{ $page->registerUrl() }}" data-hero-cta class="mt-1.5 inline-flex min-h-[56px] items-center rounded-full bg-track px-[30px] text-[17px] font-semibold text-on-track hover:brightness-110">{{ __('Register from :price', ['price' => $page->fromPrice()]) }}</a>
        @else
            <p class="mt-1.5 text-lg font-semibold {{ $sub }}">{{ $page->closedLabel() }}</p>
        @endif
    </section>

    @include('landings.partials.countdown')

    {{-- Framed photograph (slot 04) --}}
    <section class="mx-auto max-w-[1200px] px-5 pb-14 pt-6 sm:px-10">
        <figure class="{{ $frame }} p-2.5">
            <div class="{{ $frame }} relative h-[260px] overflow-hidden bg-[linear-gradient(170deg,#E9DFC8_0%,#B79E73_55%,#6E5634_100%)] sm:h-[460px]">
                @if ($venue = $page->heroUrl() ?? $page->photo(3))
                    <img src="{{ $venue }}" alt="{{ $page->config->hero->title }}" class="ri-grade absolute inset-0 h-full w-full object-cover" style="object-position: center 60%" onerror="this.remove()">
                @endif
            </div>
        </figure>
    </section>

    <section class="mx-auto grid max-w-[1200px] gap-10 px-5 pb-14 pt-2 sm:px-10 sm:[grid-template-columns:repeat(auto-fit,minmax(380px,1fr))]">
        <div class="{{ $frame }} p-2.5"><div class="{{ $frame }} h-full p-6 sm:p-8">
            <h2 class="ri-serif mb-5 text-center text-4xl font-bold">{{ __('The distances') }}</h2>
            @foreach ($page->distances as $row)
                @php
                    $low = ! $row['full'] && $row['left'] !== null && $row['left'] <= 10;
                    $tag = $row['full'] || ! $page->open ? 'div' : 'a';
                @endphp
                <{{ $tag }} @if ($tag === 'a') href="{{ $page->registerUrl() }}" @endif @class(['flex items-baseline justify-between gap-4 border-b border-[#E4DCCB] py-4 last:border-b-0', 'opacity-60' => $row['full']])>
                    <span><span class="ri-serif text-[26px] font-bold">{{ $row['distance']->label }}</span><span @class(['block text-sm', 'font-semibold text-track-text' => $low, $sub => ! $low])>{{ $row['full'] ? __('Sold out') : ($low ? __('Only :n left', ['n' => $page->placesLabel($row['left'])]) : $page->placesLabel($row['left'])) }}</span></span>
                    <span @class(['ri-serif text-[26px] font-bold', 'line-through' => $row['full']])>{{ $row['price'] }}</span>
                </{{ $tag }}>
            @endforeach
            @if ($page->ageCategories())
                <p class="mt-4 text-sm {{ $sub }}">{{ __('Age categories: :list.', ['list' => $page->ageCategories()]) }}</p>
            @endif
        </div></div>
        <div class="{{ $frame }} p-2.5"><div class="{{ $frame }} h-full p-6 sm:p-8">
            <h2 class="ri-serif mb-5 text-center text-4xl font-bold">{{ __('Order of events') }}</h2>
            <ol class="text-base">
                @foreach ($page->schedule() as $row)
                    <li class="flex gap-5 border-b border-[#E4DCCB] py-3 last:border-b-0"><span class="ri-serif w-[70px] shrink-0 text-[22px] font-bold text-track-text">{{ $row['time'] }}</span>{{ $row['label'] }}</li>
                @endforeach
            </ol>
            <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="mt-4 inline-flex min-h-[44px] items-center text-sm font-semibold text-track-text hover:underline">{{ __('Open in Maps') }}</a>
        </div></div>
    </section>

    @if ($page->config->faq)
        <section class="mx-auto max-w-[1200px] px-5 pb-14 sm:px-10">
            <h2 class="ri-serif mb-[18px] text-center text-4xl font-bold">{{ __('Notices') }}</h2>
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr))]">
                @foreach ($page->config->faq as $item)
                    <div class="bg-[#F3EEE3] p-[22px] leading-relaxed"><strong class="mb-1 block">{{ $item->q }}</strong>{{ $item->a }}</div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-[1200px] px-5 pb-14 sm:px-10">
        @if ($page->config->charity)
            <p class="ri-serif mx-auto mb-8 max-w-2xl text-center text-2xl italic">{{ $page->config->charity }}</p>
        @endif
        @include('landings.partials.sponsors', ['dark' => false, 'center' => true, 'cardClass' => 'bg-[#F3EEE3] text-[#231A14]'])
    </section>

    {{-- Register --}}
    <section id="register" class="scroll-mt-10 bg-track text-on-track">
        <div class="mx-auto flex max-w-[1200px] flex-col items-center gap-5 px-3 py-12 text-center sm:px-10 sm:py-14">
            <h2 class="ri-serif text-[40px] font-bold sm:text-5xl">{{ __('Run with us.') }}</h2>
            <p class="max-w-md opacity-85">{{ __('Your EF- pass with a QR code arrives by email as soon as payment is confirmed. Show it at bib collection.') }}</p>
            <div class="w-full max-w-3xl">@include('landings.partials.register')</div>
        </div>
    </section>
@endsection
