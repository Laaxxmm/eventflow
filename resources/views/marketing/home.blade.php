@extends('marketing.layout')

{{--
    Slim SaaS homepage (Session UX-H2). Four bands below the nav, nothing else:
      1. Hero: one headline, one sub, Start free trial + "See templates →", one proof line, one device mock.
      2. One featured template, linking to the full gallery on /templates.
      3. Quiet pricing strip: Single / Club / Circuit, Custom as a text link, one add-ons line.
      4. Final CTA: one Heat button.
    The template grid, checklists and trust bars live on /templates and /pricing, not here.
    Every number comes from config/plans.php.
--}}

@php
    use App\Saas\Plans;

    $selfServe = array_filter($plans, fn ($p) => $p['self_serve']);
    $custom = collect($plans)->first(fn ($p) => ! $p['self_serve']);
    $featuredKey = 'stadium-split';
    $featured = config("eventflow.landing_templates.{$featuredKey}");
    $eventAddons = Plans::eventAddons();
@endphp

@section('content')
    {{-- 1 · Hero --}}
    <section class="bg-paper text-ink" data-band="hero">
        <div class="mx-auto grid max-w-7xl items-center gap-16 px-4 pb-24 pt-16 sm:px-6 sm:pt-24 lg:grid-cols-[1.25fr_0.75fr] lg:gap-20 lg:px-8 lg:pb-32 lg:pt-28">
            <div>
                <p class="ri-kicker text-heat" data-reveal>{{ __('Race sites for Indian organisers') }}</p>
                <h1 class="ri-headline mt-6 text-[3.25rem] leading-[0.95] sm:text-[5rem] lg:text-[6.25rem]" data-reveal>
                    {{ __('Your race,') }}<br>{{ __('online in') }} <span class="whitespace-nowrap text-heat">{{ __('an hour.') }}</span>
                </h1>
                <p class="mt-8 max-w-xl text-lg text-ink/70 sm:text-xl" data-reveal>
                    {{ __('A race page runners trust, entries paid straight to your Razorpay or Cashfree, and a QR pass for every runner.') }}
                </p>
                <div class="mt-10 flex flex-col gap-5 sm:flex-row sm:items-center sm:gap-8" data-reveal>
                    <x-ri.button variant="heat" size="lg" :href="route('register')">{{ __('Start free trial') }}</x-ri.button>
                    <a href="{{ route('templates.index') }}" class="font-semibold text-ink underline decoration-heat decoration-2 underline-offset-[6px] hover:text-ink/70">{{ __('See templates') }} →</a>
                </div>
                <p class="mt-8 text-sm text-ink/55" data-reveal data-proof-line>{{ __('No ticket % · Own SMTP free · Razorpay-ready') }}</p>
            </div>

            {{-- One device: a Race Ink landing on a phone. --}}
            <div class="relative mx-auto w-full max-w-[15.5rem] sm:max-w-[18rem] lg:max-w-[19rem]" data-reveal data-device-mock>
                <div class="relative rounded-[2.75rem] bg-ink p-2.5 shadow-[0_40px_80px_-30px_rgb(var(--ri-ink)/0.6)]">
                    <div class="overflow-hidden rounded-[2.25rem] bg-midnight">
                        <img src="{{ asset('images/templates/mobile/gun-lap.jpg') }}" width="390" height="780" alt="{{ __('A race page made with the Gun Lap template, on a phone') }}" class="block h-auto w-full" fetchpriority="high">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2 · One featured template --}}
    <section class="bg-ink text-paper" data-band="featured-template">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
            <div class="grid items-end gap-10 lg:grid-cols-[1fr_auto]">
                <div>
                    <p class="ri-kicker text-heat">{{ __('Featured template') }}</p>
                    <h2 class="ri-headline mt-5 text-6xl sm:text-7xl">{{ $featured['label'] }}</h2>
                    <p class="mt-5 max-w-xl text-lg text-paper/70">{{ $featured['description'] }}</p>
                </div>
                <a href="{{ route('templates.index') }}" class="font-semibold text-paper underline decoration-heat decoration-2 underline-offset-[6px] hover:text-paper/75">{{ __('Browse all templates') }} →</a>
            </div>
            <a href="{{ route('templates.show', $featuredKey) }}" class="group mt-14 block" data-featured-template="{{ $featuredKey }}">
                <div class="overflow-hidden bg-paper ring-1 ring-paper/15">
                    <div class="flex items-center gap-1.5 bg-paper/95 px-4 py-3" aria-hidden="true">
                        <span class="h-2.5 w-2.5 rounded-full bg-ink/20"></span><span class="h-2.5 w-2.5 rounded-full bg-ink/20"></span><span class="h-2.5 w-2.5 rounded-full bg-ink/20"></span>
                    </div>
                    <img src="{{ asset("images/templates/{$featuredKey}.jpg") }}" width="1280" height="800" loading="lazy" decoding="async" alt="{{ __(':name template preview', ['name' => $featured['label']]) }}" class="block h-auto w-full transition duration-700 group-hover:scale-[1.01]">
                </div>
            </a>
        </div>
    </section>

    {{-- 3 · Quiet pricing --}}
    <section id="pricing" class="scroll-mt-16 bg-paper text-ink" data-band="pricing">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                <h2 class="ri-headline text-5xl sm:text-6xl">{{ __('Simple pricing.') }}</h2>
                <a href="{{ route('pricing') }}" class="font-semibold underline decoration-heat decoration-2 underline-offset-[6px] hover:text-ink/70">{{ __('Full pricing') }} →</a>
            </div>
            <dl class="mt-14 grid border-t border-ink/15 sm:grid-cols-3">
                @foreach ($selfServe as $key => $plan)
                    <div @class(['py-8 sm:px-8', 'sm:pl-0' => $loop->first, 'border-b border-ink/15 sm:border-b-0 sm:border-l' => ! $loop->first, 'border-b border-ink/15 sm:border-b-0' => $loop->first]) data-plan="{{ $key }}">
                        <dt class="text-sm font-semibold uppercase tracking-[0.16em] text-ink/60">{{ $plan['name'] }}</dt>
                        <dd class="mt-3 font-display text-5xl font-extrabold leading-none">{{ Plans::inr($plan['price_inr']) }}</dd>
                        <dd class="mt-3 text-sm text-ink/60">{{ __($plan['term_label']) }} · {{ Plans::limitsLabel($plan) }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-8 flex flex-col gap-3 border-t border-ink/15 pt-6 text-sm text-ink/60 sm:flex-row sm:items-center sm:justify-between">
                <p data-addons-line>
                    <span class="font-semibold text-ink">{{ __('Add-ons:') }}</span>
                    @foreach ($eventAddons as $addon)
                        {{ __($addon['short']) }} {{ Plans::inr($addon['price_inr']) }} ·
                    @endforeach
                    {{ __('Platform mail') }} · {{ __('WhatsApp') }}
                </p>
                @if ($custom)
                    <a href="{{ route('pricing') }}#custom" class="hover:text-ink">{{ __(':plan for federations and series', ['plan' => $custom['name']]) }} →</a>
                @endif
            </div>
        </div>
    </section>

    {{-- 4 · Final CTA, one button --}}
    @include('marketing.partials.cta-band')
@endsection
