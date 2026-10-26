{{--
    Equipment Close (docs/design/daylight-track/EquipmentClose.dc.html): the sport's gear up close in an
    arch (slot 03) with a turning "Limited bibs" seal, category cards with places left, two photos
    (slots 06, 05) beside "What to bring", questions and a deep band with the form. Mint page.
    House colours are rink green; an organiser's own brand colour replaces the green.
--}}
@extends('landings.layout')

@section('body-class', 'bg-[#F4FAF7] text-[#0E2A22]')

@push('head')
    @if (strtolower($page->config->branding->primary) === \App\Landing\Theme::RI_TRACK)
        <style>body[data-template] { --ri-track: 11 74 56 !important; --ri-track-text: 11 74 56 !important; --ri-track-deep: 6 46 35 !important; --ri-on-track: 255 255 255 !important; }</style>
    @endif
@endpush

@section('content')
    @php
        $gear = $page->heroPhoto(2);
        $left = $page->placesLeft();
        $sub = 'text-[#3D5A51]';
    @endphp
    @include('landings.partials.header', ['dark' => false, 'registerTone' => 'track'])

    <section id="top" class="mx-auto flex max-w-[1280px] flex-wrap items-center gap-12 px-5 pb-16 pt-24 sm:px-10 sm:pt-28">
        <div class="flex flex-[1_1_440px] flex-col gap-[22px]">
            <p class="self-start rounded-full bg-[#DDF2EA] px-3.5 py-2 text-sm font-bold text-[#0B4A38]">{{ $page->date->format('l j F Y') }}</p>
            <h1 class="ri-headline text-[clamp(48px,6vw,92px)] leading-[.96]">{{ $page->config->name }}</h1>
            <p class="max-w-[32rem] text-lg leading-relaxed {{ $sub }}">{{ $page->config->hero->subtitle ?? $page->config->hero->title }}</p>
            <div class="flex flex-wrap items-center gap-5">
                @if ($page->open)
                    <a href="{{ $page->registerUrl() }}" data-hero-cta class="inline-flex min-h-[56px] items-center rounded-full bg-track px-7 text-[17px] font-bold text-on-track hover:brightness-110">{{ __('Register from :price', ['price' => $page->fromPrice()]) }}</a>
                @else
                    <p class="text-lg font-bold">{{ $page->closedLabel() }}</p>
                @endif
                <span class="text-[15px] {{ $sub }}">{{ $page->config->venue }}</span>
            </div>
        </div>
        <div class="relative h-[400px] flex-[1_1_520px] sm:h-[560px]">
            <div class="absolute inset-y-0 left-6 right-0 overflow-hidden rounded-[200px_200px_32px_32px] bg-[linear-gradient(170deg,#C9EEDF_0%,#6FC3A3_50%,#1E7A5C_100%)] sm:left-10 sm:rounded-[280px_280px_40px_40px]">
                <svg aria-hidden="true" width="100%" height="100%" viewBox="0 0 500 560" preserveAspectRatio="xMidYMid slice" class="absolute inset-0"><circle cx="160" cy="420" r="70" stroke="#fff" stroke-opacity=".5" stroke-width="10" fill="none"/><circle cx="340" cy="420" r="70" stroke="#fff" stroke-opacity=".5" stroke-width="10" fill="none"/><path d="M90 330 H 420" stroke="#fff" stroke-opacity=".5" stroke-width="12" stroke-linecap="round"/></svg>
                @if ($gear)
                    <img src="{{ $gear }}" alt="{{ $page->config->hero->title }}" class="ri-grade absolute inset-0 h-full w-full object-cover" style="object-position: center 65%" onerror="this.remove()">
                @endif
            </div>
            @if ($page->limited())
                <div class="absolute left-0 top-10 h-[120px] w-[120px] sm:top-[60px] sm:h-[132px] sm:w-[132px]">
                    <div class="ef-seal h-full w-full rounded-full bg-heat" aria-hidden="true" data-motion>
                        <svg viewBox="0 0 132 132" class="h-full w-full"><defs><path id="ec-seal" d="M66 66 m -48 0 a 48 48 0 1 1 96 0 a 48 48 0 1 1 -96 0"/></defs><text font-family="Inter, sans-serif" font-size="12" font-weight="700" letter-spacing="3" fill="#121A33"><textPath href="#ec-seal">{{ mb_strtoupper(__('Limited bibs')) }} · {{ mb_strtoupper(__('Limited bibs')) }} ·</textPath></text></svg>
                    </div>
                    <p class="absolute inset-0 flex items-center justify-center text-center text-[13px] font-extrabold leading-tight text-on-heat">{{ __(':n left', ['n' => number_format($left)]) }}</p>
                </div>
            @endif
        </div>
    </section>

    @include('landings.partials.countdown')

    {{-- Categories --}}
    <section id="categories" class="bg-white">
        <div class="mx-auto max-w-[1280px] px-5 py-14 sm:px-10 sm:py-20">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
                <h2 class="ri-headline text-[32px] sm:text-[44px]">{{ __('Categories') }}</h2>
                @if ($page->ageCategories())
                    <p class="text-[15px] {{ $sub }}">{{ __('Age on race day decides the category: :list.', ['list' => $page->ageCategories()]) }}</p>
                @endif
            </div>
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))]">
                @foreach ($page->distances as $row)
                    @php
                        $tag = $row['full'] || ! $page->open ? 'div' : 'a';
                    @endphp
                    <{{ $tag }} @if ($tag === 'a') href="{{ $page->registerUrl() }}" @endif @class(['flex flex-col gap-3.5 rounded-[24px] bg-[#F4FAF7] p-6', 'opacity-70' => $row['full']])>
                        <span class="flex items-baseline justify-between gap-3"><span class="ri-headline text-[26px]">{{ \App\View\LandingPage::numeral($row['distance']) }}</span><span @class(['ri-headline text-xl', 'line-through' => $row['full']])>{{ $row['price'] }}</span></span>
                        <span class="text-sm {{ $sub }}">{{ \App\View\LandingPage::title($row['distance']) }}</span>
                        <x-ri.places-bar :left="$row['left']" :capacity="$row['distance']->capacity" class="[&_[role=progressbar]]:bg-[#DDF2EA]" />
                    </{{ $tag }}>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Photos (slots 06, 05) + what to bring --}}
    <section class="mx-auto grid max-w-[1280px] gap-4 px-5 py-14 sm:px-10 sm:py-[72px] sm:[grid-template-columns:repeat(auto-fit,minmax(300px,1fr))]">
        <x-ri.photo :src="$page->photo(5)" :sport="$page->config->sport->value" class="h-[260px] rounded-[28px] sm:h-[300px]" alt="" />
        <x-ri.photo :src="$page->photo(4)" :sport="$page->config->sport->value" class="h-[260px] rounded-[28px] sm:h-[300px]" alt="" />
        <div class="flex flex-col gap-3 rounded-[28px] bg-track p-7 text-on-track">
            <h2 class="ri-headline text-[28px]">{{ __('Race day') }}</h2>
            <div class="[&_.text-track-text]:text-heat">@include('landings.partials.schedule', ['dark' => true])</div>
            @if ($page->config->charity)
                <p class="text-[15px] leading-relaxed opacity-85">{{ $page->config->charity }}</p>
            @endif
        </div>
    </section>

    @if ($page->config->faq)
        <section class="mx-auto max-w-[880px] px-5 pb-16 pt-4 sm:px-10">
            <h2 class="ri-headline mb-4 text-[28px] sm:text-[32px]">{{ __('Questions') }}</h2>
            @include('landings.partials.faq', ['dark' => false])
        </section>
    @endif

    <section class="mx-auto max-w-[1280px] px-5 pb-12 sm:px-10">
        @include('landings.partials.sponsors', ['dark' => false, 'cardClass' => 'bg-white text-ink'])
    </section>

    {{-- Register --}}
    <section id="register" class="scroll-mt-20 bg-track text-on-track">
        <div class="mx-auto grid max-w-[1280px] gap-8 px-3 py-12 sm:px-10 sm:py-14 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="px-2 sm:px-0">
                <h2 class="ri-headline text-[32px] sm:text-[40px]">{{ $left !== null ? __('Lace up. :n bibs left.', ['n' => number_format($left)]) : __('Lace up.') }}</h2>
                <p class="mt-4 max-w-md opacity-85">{{ __('Your EF- pass with a QR code arrives by email as soon as payment is confirmed. Show it at bib collection.') }}</p>
            </div>
            <div>@include('landings.partials.register')</div>
        </div>
    </section>
@endsection
