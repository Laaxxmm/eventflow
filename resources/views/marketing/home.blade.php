@extends('marketing.layout')

{{--
    Self-serve SaaS homepage (Session UX-H). Order: hero → price line → 3-step time to value →
    template teaser → product proof → pricing strip → self-serve trust line → CTA band.
    Every number comes from config/plans.php; nothing here is hard-coded pricing.
--}}

@php
    use App\Saas\Plans;
    use App\View\SportImagery;

    $selfServe = array_filter($plans, fn ($p) => $p['self_serve']);
    $proof = [
        [__('QR pass on every entry'), __('A branded EF- pass by email the moment an entry is paid. Signed QR codes cannot be forged.')],
        [__('Gate check-in'), __('Volunteers scan passes on any phone. Duplicates are flagged with the first check-in time.')],
        [__('Distance pricing'), __('5K, 10K, half, full — each with its own price, age bands and form fields.')],
        [__('Soft pax caps'), __('Set places per distance. Near your plan cap you get a nudge, not a locked door.')],
        [__('Finisher certificates'), __('Print-ready A4 designs with an EF-CERT number and a verify QR on each.')],
        [__('Your logo, your colours'), __('Upload a logo and pick brand colours; every template tints itself, contrast-checked.')],
    ];
    $steps = [
        [__('Sign up'), __('2 minutes'), __('Name, email, password. No card, no call.')],
        [__('Pick a template, add your race'), __('20 minutes'), __('Distances, prices, logo and colours in a five-step wizard.')],
        [__('Publish and share'), __('Under an hour'), __('Your race site is live. Share the link; entries, passes and check-in run themselves.')],
    ];
@endphp

@section('content')
    {{-- 1 · Hero --}}
    <section class="relative isolate overflow-hidden bg-midnight text-paper" style="--ri-track: var(--ri-volt)">{{-- photo fallback glows volt + heat, not violet --}}
        <x-ri.photo :src="SportImagery::pick('marketing', 0)" sport="running" kenburns class="absolute inset-0 -z-10" alt="" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink via-ink/80 to-ink/20" aria-hidden="true"></div>

        <div class="mx-auto max-w-7xl px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-24 lg:px-8 lg:pt-28">
            <p class="ri-kicker text-heat" data-reveal>{{ __('Race sites for Indian organisers') }}</p>
            <h1 class="ri-headline mt-6 max-w-6xl text-[3.2rem] sm:text-[5.5rem] lg:text-[7.5rem]" data-reveal>
                {{ __('Publish a race site you’re proud of') }} <span class="text-heat">{{ __('— in under an hour.') }}</span>
            </h1>
            <p class="mt-6 max-w-2xl text-lg text-paper/80 sm:mt-8 sm:text-xl" data-reveal>
                {{ __('Registrations, QR passes, gate check-in and finisher certificates in one workspace. Entry fees go straight to your own Razorpay or Cashfree.') }}
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:mt-10 sm:flex-row" data-reveal>
                <x-ri.button variant="heat" size="lg" :href="route('register')">{{ __('Start free trial') }}</x-ri.button>
                <x-ri.button variant="ghost-light" size="lg" :href="route('templates.index')">{{ __('See live templates') }}</x-ri.button>
            </div>
            <p class="mt-6 inline-flex flex-wrap items-center gap-x-3 gap-y-1 bg-paper/10 px-4 py-2 text-sm font-medium text-paper ring-1 ring-inset ring-paper/20" data-reveal data-proof-chip>
                <span>{{ __('No ticket % required') }}</span><span class="text-paper/40" aria-hidden="true">·</span>
                <span>{{ __('Own SMTP free') }}</span><span class="text-paper/40" aria-hidden="true">·</span>
                <span>{{ __('India Razorpay-ready') }}</span>
            </p>
        </div>

        {{-- Price line: Single is visible without scrolling. --}}
        <div class="border-t border-paper/15 bg-ink/80">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-8 gap-y-2 px-4 py-4 text-sm sm:px-6 lg:px-8" data-price-line>
                @foreach ($selfServe as $key => $plan)
                    <a href="{{ route('pricing') }}#{{ $key }}" class="flex items-baseline gap-2 hover:text-paper">
                        <span class="font-display text-lg font-bold uppercase text-paper/70">{{ $plan['name'] }}</span>
                        <span class="font-display text-2xl font-extrabold {{ $loop->first ? 'text-heat' : 'text-paper' }}">{{ Plans::inr($plan['season_pass_inr']) }}</span>
                    </a>
                @endforeach
                <a href="{{ route('pricing') }}" class="ml-auto font-semibold text-paper/75 underline decoration-heat decoration-2 underline-offset-4 hover:text-paper">{{ __('Full pricing') }}</a>
            </div>
        </div>
    </section>

    {{-- 2 · Time to value --}}
    <section id="start" class="scroll-mt-16 border-b-2 border-ink bg-paper">
        <ol class="mx-auto grid max-w-7xl md:grid-cols-3">
            @foreach ($steps as $i => [$title, $time, $body])
                <li data-reveal @class(['flex gap-5 px-4 py-10 sm:px-6 lg:px-8', 'border-t-2 border-ink md:border-l-2 md:border-t-0' => ! $loop->first])>
                    <span class="font-display text-7xl font-extrabold leading-none text-heat">{{ $i + 1 }}</span>
                    <div>
                        <p class="ri-kicker text-heat">{{ $time }}</p>
                        <h2 class="mt-2 font-display text-3xl font-bold uppercase leading-none">{{ $title }}</h2>
                        <p class="mt-3 text-ink/70">{{ $body }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- 3 · Template teaser --}}
    <section id="templates" class="scroll-mt-16 bg-mist">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="ri-kicker text-heat">{{ __('Templates') }}</p>
                    <h2 class="ri-headline mt-4 max-w-4xl text-6xl sm:text-7xl">{{ __('Your runners land on a sports page, not a form.') }}</h2>
                </div>
                <x-ri.button variant="ink" size="md" :href="route('templates.index')">{{ __('See all templates') }}</x-ri.button>
            </div>
            <div class="mt-12 grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-5">
                @foreach (config('eventflow.landing_templates') as $key => $tpl)
                    @include('marketing.partials.template-card', ['index' => $loop->iteration])
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4 · Product proof --}}
    <section id="product" class="scroll-mt-16 bg-ink text-paper">
        <div class="mx-auto grid max-w-7xl gap-14 px-4 py-24 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
            <div class="lg:sticky lg:top-28 lg:self-start">
                <p class="ri-kicker text-heat">{{ __('Product') }}</p>
                <h2 class="ri-headline mt-4 text-6xl sm:text-8xl">{{ __('Race day,') }} <span class="text-heat">{{ __('ticked off.') }}</span></h2>
                <p class="mt-6 max-w-md text-paper/70">{{ __('Every plan, including the trial, has all of it. You only choose how many races and runners.') }}</p>
            </div>
            <ul class="grid gap-px bg-paper/15 sm:grid-cols-2">
                @foreach ($proof as [$title, $body])
                    <li class="bg-ink p-6" data-reveal>
                        <h3 class="flex items-center gap-3 font-display text-2xl font-bold uppercase tracking-wide">@include('marketing.partials.tick'){{ $title }}</h3>
                        <p class="mt-3 text-sm text-paper/70">{{ $body }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 5 · Pricing strip, Single first --}}
    <section id="pricing" class="scroll-mt-16 bg-paper">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="ri-kicker text-heat">{{ __('Pricing') }}</p>
                    <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('One race or a season.') }}</h2>
                </div>
                <p class="max-w-md text-ink/70">{{ __('One flat price per pass. No percentage on tickets; entry fees go straight to your gateway. Prices exclude GST.') }}</p>
            </div>
            <div class="mt-12">
                @include('marketing.partials.pricing-strip')
            </div>
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                @include('marketing.partials.messaging-line', ['class' => 'text-ink/75'])
                <a href="{{ route('pricing') }}" class="text-sm font-semibold underline decoration-heat decoration-2 underline-offset-4">{{ __('Full pricing') }} →</a>
            </div>
        </div>
    </section>

    {{-- 6 · Self-serve trust line --}}
    <section class="border-y-2 border-ink bg-volt text-midnight">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="font-display text-3xl font-bold uppercase leading-tight sm:text-4xl" data-trust-line>{{ __('Upgrade, participant packs, and SMTP — all in settings. No waiting on a quote to go live.') }}</p>
            @if (\App\Saas\Trial::stubbed())
                <p class="mt-3 text-sm opacity-80">{{ __('Online checkout for upgrades and packs is rolling out. Until it is live, your trial is never blocked at its limits.') }}</p>
            @endif
        </div>
    </section>

    {{-- 7 · Final CTA --}}
    @include('marketing.partials.cta-band')
@endsection
