{{--
    Stadium Split (docs/design/daylight-track/StadiumSplit.dc.html): a marigold panel with the event
    name and Register beside a big action photo (slot 02), a partner line, distance cards, a venue photo
    (slot 04) beside race morning, question cards and a navy band with the form. Light, chalk.
--}}
@extends('landings.layout')

@section('body-class', 'bg-paper text-ink')

@section('content')
    @php
        $action = $page->heroUrl() ?? $page->photo(1);
    @endphp
    @include('landings.partials.header', ['dark' => false, 'nav' => ['#distances' => __('Distances'), '#schedule' => __('Schedule')] + ($page->config->faq ? ['#faq' => __('FAQ')] : []), 'registerTone' => 'track', 'solidLogo' => true])

    <section id="top" class="mx-auto flex max-w-[1360px] flex-wrap gap-4 px-3 pt-[72px] sm:px-6 sm:pt-24">
        <div class="flex min-h-[460px] flex-[1_1_520px] flex-col justify-between gap-8 rounded-[28px] bg-heat p-7 text-on-heat sm:min-h-[600px] sm:rounded-[36px] sm:p-12">
            <p class="text-base font-semibold">{{ __(':org presents', ['org' => $page->orgName]) }}</p>
            <div class="flex flex-col gap-5">
                <h1 class="ri-headline text-[clamp(44px,6vw,96px)] leading-[.95]">{{ $page->config->name }}</h1>
                <p class="max-w-[30rem] text-lg leading-normal">{{ $page->config->hero->subtitle ?? $page->config->hero->title }} {{ $page->date->format('l j F Y') }} {{ __('at :venue.', ['venue' => \Illuminate\Support\Str::before($page->config->venue, ',')]) }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-5">
                @if ($page->open)
                    <a href="{{ $page->registerUrl() }}" data-hero-cta class="inline-flex min-h-[56px] items-center rounded-full bg-ink px-7 text-[17px] font-bold text-white hover:bg-ink/90">{{ __('Register from :price', ['price' => $page->fromPrice()]) }}</a>
                @else
                    <p class="text-lg font-bold">{{ $page->closedLabel() }}</p>
                @endif
                <span class="text-[15px] font-semibold">{{ trans_choice('{0} Race day today|{1} 1 day to go|[2,*] :count days to go', $page->daysToGo()) }}</span>
            </div>
        </div>
        <div class="relative min-h-[320px] flex-[1_1_520px] overflow-hidden rounded-[28px] bg-[linear-gradient(200deg,#B3AEF5_0%,rgb(var(--ri-track))_55%,#241D7A_100%)] sm:min-h-[600px] sm:rounded-[36px]">
            <svg aria-hidden="true" width="100%" height="100%" viewBox="0 0 600 600" preserveAspectRatio="xMidYMid slice" class="absolute inset-0"><path d="M80 600 L 300 120 L 520 600" stroke="#fff" stroke-opacity=".14" stroke-width="2" fill="none"/><path d="M180 600 L 300 120 L 420 600" stroke="#fff" stroke-opacity=".14" stroke-width="2" fill="none"/></svg>
            @if ($action)
                <img src="{{ $action }}" alt="{{ $page->config->hero->title }}" class="ri-grade absolute inset-0 h-full w-full object-cover" style="object-position: center 30%" onerror="this.remove()">
            @endif
        </div>
    </section>

    @include('landings.partials.countdown')

    {{-- Partner line --}}
    <section aria-label="{{ __('Race partners') }}" class="mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-9 text-[15px] text-muted sm:px-10">
        @forelse ($page->sponsors() as $sponsor)
            <span>{{ $sponsor['tier'] }} @if ($sponsor['logo'])<img src="{{ $sponsor['logo'] }}" alt="{{ $sponsor['name'] }}" class="ms-1.5 inline h-6 w-auto align-middle">@else<strong class="ms-1.5 text-ink">{{ $sponsor['name'] }}</strong>@endif</span>
        @empty
            <span>{{ __('Organised by') }} <strong class="ms-1.5 text-ink">{{ $page->orgName }}</strong></span>
        @endforelse
    </section>

    {{-- Distances --}}
    <section id="distances" class="mx-auto max-w-[1280px] scroll-mt-20 px-5 py-8 sm:px-10 sm:py-12">
        <h2 class="ri-headline mb-7 text-[32px] sm:text-[44px]">{{ __('Choose your distance') }}</h2>
        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr))]">
            @foreach ($page->distances as $row)
                @php
                    $low = ! $row['full'] && $row['left'] !== null && $row['left'] <= 10;
                    $tag = $row['full'] || ! $page->open ? 'div' : 'a';
                @endphp
                <{{ $tag }} @if ($tag === 'a') href="{{ $page->registerUrl() }}" @endif @class([
                    'flex flex-col gap-[18px] rounded-[28px] p-[26px] transition duration-200 ease-track',
                    'bg-white text-ink hover:-translate-y-0.5' => ! $low && ! $row['full'],
                    'bg-track text-on-track hover:-translate-y-0.5' => $low,
                    'bg-[#E9ECF4] text-muted' => $row['full'],
                ])>
                    <span @class(['ri-headline text-[52px] leading-none', 'text-[#9AA2BA]' => $row['full']])>{{ \App\View\LandingPage::numeral($row['distance']) }}</span>
                    <span class="font-semibold">{{ \App\View\LandingPage::title($row['distance']) }}</span>
                    <span class="mt-auto flex items-center justify-between gap-2">
                        <span @class(['ri-headline text-2xl' => ! $row['full'], 'text-lg line-through' => $row['full']])>{{ $row['price'] }}</span>
                        <span @class(['text-[13px]', 'rounded-full bg-heat px-2.5 py-1 font-bold text-on-heat' => $low, 'opacity-80' => ! $low])>{{ $row['full'] ? __('Sold out') : $page->placesLabel($row['left']) }}</span>
                    </span>
                </{{ $tag }}>
            @endforeach
        </div>
        @if ($page->ageCategories())
            <p class="mt-5 text-sm text-muted">{{ __('Age categories: :list.', ['list' => $page->ageCategories()]) }}</p>
        @endif
    </section>

    {{-- Venue photo + race morning --}}
    <section id="schedule" class="mx-auto grid max-w-[1280px] scroll-mt-20 gap-6 px-5 py-8 sm:px-10 sm:py-10 sm:[grid-template-columns:repeat(auto-fit,minmax(380px,1fr))]">
        <x-ri.photo :src="$page->photo(3)" :sport="$page->config->sport->value" class="min-h-[260px] rounded-[32px] sm:min-h-[400px]" :alt="$page->config->venue" />
        <div class="rounded-[32px] bg-white p-6 sm:p-9">
            <h2 class="ri-headline mb-5 text-[28px] sm:text-[32px]">{{ __('Race morning') }}</h2>
            @include('landings.partials.schedule', ['dark' => false])
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-[#EEF0F6] pt-5 text-sm">
                <span class="text-muted">{{ $page->config->venue }}</span>
                <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center font-semibold text-track-text hover:underline">{{ __('Open in Maps') }}</a>
            </div>
            @if ($page->config->charity)
                <p class="mt-3 text-sm text-muted">{{ $page->config->charity }}</p>
            @endif
        </div>
    </section>

    @if ($page->config->faq)
        <section id="faq" class="mx-auto max-w-[880px] scroll-mt-20 px-5 pb-16 pt-10 sm:px-10 sm:pb-[72px] sm:pt-12">
            <h2 class="ri-headline mb-5 text-center text-[28px] sm:text-[32px]">{{ __('Questions') }}</h2>
            @include('landings.partials.faq', ['dark' => false, 'cards' => true])
        </section>
    @endif

    {{-- Register --}}
    <section id="register" class="mx-auto max-w-[1360px] scroll-mt-20 px-3 pb-6 sm:px-6">
        <div class="grid gap-8 rounded-[28px] bg-ink p-4 pt-8 text-white sm:rounded-[36px] sm:p-12 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="px-2 sm:px-0">
                <h2 class="ri-headline text-[32px] sm:text-[40px]">{{ __('See you at the start line.') }}</h2>
                <p class="mt-4 max-w-md text-white/80">{{ __('Your EF- pass with a QR code arrives by email as soon as payment is confirmed. Show it at bib collection.') }}</p>
            </div>
            <div>@include('landings.partials.register')</div>
        </div>
    </section>
@endsection
