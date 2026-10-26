@extends('marketing.layout')

{{--
    Homepage (Daylight Track, docs/design/daylight-track/Main.dc.html). Bands in order:
      hero (3D track stage) · sports (marketing/01–03 photos) · how-it-works (four stops on one lane)
      · templates (3D fan; the full gallery stays on /templates) · pricing (race bibs) · cta (one button).
    Every price comes from config/plans.php.
--}}

@php
    use App\Saas\Plans;

    $selfServe = array_filter($plans, fn ($p) => $p['self_serve']);
    $custom = collect($plans)->first(fn ($p) => ! $p['self_serve']);
    $eventAddons = Plans::eventAddons();
    $sports = [
        ['Running', __('5K to 42.2K'), 'linear-gradient(160deg,#8C86F0 0%,#4B3FE0 55%,#2B2390 100%)', 'text-white', 0],
        ['Cycling', __('Hill climbs and night rides'), 'linear-gradient(160deg,#FFD9A0 0%,#F2A93B 55%,#B8761A 100%)', 'text-ink', 1],
        ['Skating', __('Under-8 to senior'), 'linear-gradient(160deg,#C9EEDF 0%,#6FC3A3 55%,#1E7A5C 100%)', 'text-white', 2],
    ];
    $steps = [
        [__('Publish your race page'), __('Pick a template, add distances, prices and the run sheet, and go live on your own link.')],
        [__('Take entries and payments'), __('Runners pay by UPI or card. The money goes to your Razorpay or Cashfree account, not ours.')],
        [__('Send every runner a pass'), __('Each paid entry gets an EF- pass with a QR code, as an image and a PDF, by email.')],
        [__('Check runners in on race day'), __('Volunteers scan passes at the gate from any phone. No app to install.')],
    ];
    // Template card palettes for the fan (sample race, house colours).
    $fan = [
        'gun-lap' => ['#FFFFFF', '#4B3FE0', '#FFFFFF', '#121A33', '#F2A93B', '#121A33', '#F3F5FA', 'left'],
        'stadium-split' => ['#F3F5FA', '#F2A93B', '#121A33', '#121A33', '#4B3FE0', '#FFFFFF', '#FFFFFF', 'left'],
        'equipment-close' => ['#FFFFFF', '#DDF2EA', '#0B4A38', '#0B4A38', '#0B4A38', '#FFFFFF', '#EEF8F4', 'left'],
        'night-circuit' => ['#1B2244', '#283263', '#FFFFFF', '#FFFFFF', '#F2A93B', '#121A33', '#232C57', 'left'],
        'heritage-crest' => ['#FBFAF6', '#FBFAF6', '#121A33', '#121A33', '#7A1F2B', '#FFFFFF', '#F1EEE6', 'center'],
    ];
    $templates = collect(config('eventflow.landing_templates'))->only(array_keys($fan));
@endphp

@section('content')
    {{-- Hero --}}
    <section id="top" class="mx-auto flex max-w-[1280px] flex-wrap items-center gap-8 overflow-hidden px-5 pb-14 pt-6 sm:px-10 sm:pt-10" data-band="hero">
        <div class="flex min-w-0 flex-[1_1_420px] flex-col gap-7">
            <p class="inline-flex items-center gap-2.5 self-start rounded-full bg-white py-2 pe-3.5 ps-2 text-sm text-muted ring-1 ring-inset ring-mist">
                <span class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-lilac"><svg aria-hidden="true" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#4B3FE0" stroke-width="2.5" stroke-linecap="round"><path d="M4 12h16M14 6l6 6-6 6"/></svg></span>
                {{ __('Race sites for running, cycling and skating organisers in India') }}
            </p>
            <h1 class="ri-headline text-[clamp(46px,5.4vw,80px)] leading-none">{{ __('Put your race online in an hour.') }}</h1>
            <p class="max-w-[34rem] text-[19px] leading-relaxed text-muted">{{ __('A race page runners trust, entries paid straight into your Razorpay or Cashfree account, and a QR pass in every runner’s inbox.') }}</p>
            <div class="flex flex-wrap items-center gap-6">
                <a href="{{ route('register') }}" class="ri-glow inline-flex min-h-[58px] items-center rounded-full bg-track px-[30px] text-[17px] font-semibold text-white hover:bg-track-deep">{{ __('Start your free trial') }}</a>
                <a href="#templates" class="inline-flex min-h-[44px] items-center font-semibold underline decoration-heat decoration-2 underline-offset-[6px] hover:text-track">{{ __('See the templates') }}</a>
            </div>
            <p class="text-sm text-muted" data-proof-line>{{ __(':days days free. No card, no call, and we never take a cut of your entry fees.', ['days' => config('plans.trial_days')]) }}</p>
        </div>
        <x-ri.track-stage class="flex-[1_1_600px]" />
    </section>

    {{-- Sports --}}
    <section class="mx-auto max-w-[1280px] px-5 pb-6 pt-10 sm:px-10" data-band="sports">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <h2 class="ri-headline max-w-[36rem] text-[clamp(34px,3.6vw,52px)] leading-[1.05]">{{ __('Made for race mornings') }}</h2>
            <p class="max-w-[26rem] text-[17px] leading-relaxed text-muted">{{ __('Running, cycling and skating packs. Your page looks like your sport, and your own photos always come first.') }}</p>
        </div>
        <div class="mt-10 grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr))]">
            @foreach ($sports as [$sport, $chip, $gradient, $ink, $i])
                <figure class="relative h-[300px] overflow-hidden rounded-[28px] sm:h-[420px]" style="background: {{ $gradient }}">
                    @if ($photo = \App\View\SportImagery::pick('marketing', $i))
                        <img src="{{ $photo }}" alt="" loading="lazy" class="ri-grade absolute inset-0 h-full w-full object-cover" style="object-position: {{ $i === 2 ? 'center bottom' : 'center' }}" onerror="this.remove()">
                        <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-[rgb(18_26_51/.55)] to-transparent" aria-hidden="true"></div>
                    @endif
                    <figcaption @class(['absolute inset-x-5 bottom-5 flex items-end justify-between gap-3', $photo ? 'text-white' : $ink])>
                        <span class="ri-headline text-[28px]">{{ __($sport) }}</span>
                        <span class="rounded-full bg-white/20 px-2.5 py-1.5 text-xs font-semibold backdrop-blur">{{ $chip }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto max-w-[1280px] px-5 pb-24 pt-[72px] sm:px-10" data-band="how-it-works">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <h2 class="ri-headline max-w-[40rem] text-[clamp(34px,3.6vw,52px)] leading-[1.05]">{{ __('What happens after you press Publish') }}</h2>
            <p class="max-w-[26rem] text-[17px] leading-relaxed text-muted">{{ __('Four stops on one lane. You set it up once; EventFlow runs the rest until race morning.') }}</p>
        </div>
        <div aria-hidden="true" class="relative mt-12 h-[72px] overflow-hidden rounded-[36px] bg-track">
            <div class="absolute inset-x-0 top-[23px] h-[1.4px] bg-white/50"></div>
            <div class="absolute inset-x-0 top-[47px] h-[1.4px] bg-white/50"></div>
            <div class="absolute inset-y-0 right-16 w-1.5 bg-white"></div>
            <div class="absolute inset-y-0 right-[57px] w-1 bg-heat"></div>
            <div class="absolute inset-0 grid grid-cols-4 items-center">
                @foreach ($steps as $i => $step)
                    <div @class(['flex h-10 w-10 items-center justify-center justify-self-center rounded-full font-bold', 'bg-white text-track' => $i < 3, 'bg-heat text-on-heat' => $i === 3])>{{ $i + 1 }}</div>
                @endforeach
            </div>
        </div>
        <ol class="mt-7 grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr))]">
            @foreach ($steps as $i => [$title, $text])
                <li class="flex flex-col gap-2.5 rounded-[22px] bg-white p-[26px]">
                    <h3 class="text-[19px] font-bold"><span class="sr-only">{{ $i + 1 }}. </span>{{ $title }}</h3>
                    <p class="text-[15px] leading-relaxed text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Templates: a 3D fan of the five race pages; the full gallery is on /templates --}}
    <section id="templates" class="scroll-mt-4 overflow-hidden bg-white" data-band="templates" x-data="{ picked: 1 }">
        <div class="mx-auto flex max-w-[1280px] flex-wrap items-center gap-12 px-5 py-20 sm:px-10 sm:py-[104px]">
            <div class="flex min-w-0 flex-[1_1_360px] flex-col gap-6">
                <h2 class="ri-headline text-[clamp(34px,3.6vw,52px)] leading-[1.05]">{{ __('Five race pages, each with its own character') }}</h2>
                <p class="text-[17px] leading-relaxed text-muted">{{ __('Every template takes your logo and colours, and checks the contrast for you so the Register button always reads.') }}</p>
                <div class="flex flex-col gap-2" role="group" aria-label="{{ __('Templates') }}">
                    @foreach ($templates->keys() as $i => $key)
                        <button type="button" @click="picked = {{ $i }}" :aria-pressed="(picked === {{ $i }}).toString()" aria-pressed="{{ $i === 1 ? 'true' : 'false' }}"
                                class="flex flex-col gap-1 rounded-2xl border px-[18px] py-3.5 text-left transition duration-200 ease-track"
                                :class="picked === {{ $i }} ? 'border-track bg-lilac' : 'border-mist bg-white hover:border-[#C5CBDB]'">
                            <span class="text-base font-bold">{{ $templates[$key]['label'] }}</span>
                            <span class="text-sm text-muted">{{ __($templates[$key]['description']) }}</span>
                        </button>
                    @endforeach
                </div>
                <a href="{{ route('templates.index') }}" class="self-start font-semibold text-track hover:text-track-deep">{{ __('Browse all templates') }}</a>
            </div>
            <div class="flex h-[440px] min-w-0 flex-[1_1_560px] items-center justify-center sm:h-[520px]" aria-hidden="true">
                <div class="relative h-[420px] w-[300px] scale-[.7] [perspective:1400px] [transform-style:preserve-3d] sm:scale-100">
                    @foreach ($templates->keys() as $i => $key)
                        @php
                            [$bg, $panel, $titleInk, $ink, $accent, $onAccent, $row, $align] = $fan[$key];
                        @endphp
                        <a href="{{ route('templates.show', $key) }}" tabindex="-1" class="absolute inset-0 transition-[transform,opacity] duration-700 ease-track"
                           :style="(() => { const o = {{ $i }} - picked, a = Math.abs(o); return `transform: translateX(${o * 150}px) translateZ(${-a * 140}px) rotateY(${o * -24}deg); z-index: ${10 - a}; opacity: ${a > 2 ? 0 : 1 - a * .12}` })()"
                           style="transform: translateX({{ ($i - 1) * 150 }}px) translateZ({{ -abs($i - 1) * 140 }}px) rotateY({{ ($i - 1) * -24 }}deg); z-index: {{ 10 - abs($i - 1) }}">
                            <div class="flex h-[420px] w-[300px] flex-col overflow-hidden rounded-[22px] shadow-[0_40px_70px_-34px_rgb(18_26_51/.55),0_0_0_1px_rgb(18_26_51/.08)]" style="background: {{ $bg }}; color: {{ $ink }}">
                                <div class="flex items-center justify-between px-4 py-3 text-[11px] font-bold"><span>Demo Runners</span><span class="rounded-full px-2.5 py-1" style="background: {{ $accent }}; color: {{ $onAccent }}">{{ __('Register') }}</span></div>
                                <div class="mx-3 flex flex-col gap-2.5 rounded-[14px] px-[18px] py-[22px]" style="background: {{ $panel }}; color: {{ $titleInk }}; text-align: {{ $align }}">
                                    <span class="text-[11px] opacity-80">Sat 26 Dec 2026</span>
                                    <span @class(['text-[26px] leading-[1.02]', 'ri-serif font-bold' => $key === 'heritage-crest', 'ri-headline' => $key !== 'heritage-crest'])>Bengaluru City Marathon 2027</span>
                                    <span class="text-[11px] opacity-80">5K, 10K, half and full marathon</span>
                                </div>
                                <div class="flex flex-col gap-2 px-3 py-3.5">
                                    @foreach ([['5K fun run', '₹599'], ['10K run', '₹899'], ['Half marathon', '₹1,499']] as [$d, $p])
                                        <div class="flex justify-between rounded-[10px] px-3 py-2.5 text-xs font-semibold" style="background: {{ $row }}"><span>{{ $d }}</span><span>{{ $p }}</span></div>
                                    @endforeach
                                </div>
                                <div class="mt-auto px-4 pb-3.5 text-[13px] font-bold">{{ $templates[$key]['label'] }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing: one race bib per plan --}}
    <section id="pricing" class="mx-auto max-w-[1280px] scroll-mt-4 px-5 pb-24 pt-20 sm:px-10 sm:pt-[104px]" data-band="pricing">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <h2 class="ri-headline text-[clamp(34px,3.6vw,52px)] leading-[1.05]">{{ __('Pay per race or per season') }}</h2>
            <p class="max-w-[26rem] text-[17px] leading-relaxed text-muted">{{ __('Flat prices in rupees, exclusive of GST. Nothing taken from your runners’ entry fees.') }}</p>
        </div>
        <div class="mt-14 grid gap-8 [grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))]">
            @foreach ($selfServe as $key => $plan)
                @php
                    $hi = ! empty($plan['highlight']);
                    $tilt = ['-rotate-[1.5deg]', 'rotate-1', '-rotate-[.5deg]'][$loop->index % 3];
                @endphp
                <a href="{{ route('register', ['plan' => $key]) }}" @class([
                    'relative block rounded-[18px] pb-7 transition duration-300 ease-track hover:-translate-y-2 hover:rotate-0',
                    $tilt,
                    'bg-track text-white shadow-[0_30px_60px_-30px_rgb(75_63_224/.7)]' => $hi,
                    'bg-white text-ink shadow-[0_24px_48px_-32px_rgb(18_26_51/.4)]' => ! $hi,
                ]) data-plan="{{ $key }}">
                    <span aria-hidden="true" class="absolute left-4 top-4 h-2.5 w-2.5 rounded-full bg-paper shadow-[inset_0_1px_2px_rgb(18_26_51/.25)]"></span>
                    <span aria-hidden="true" class="absolute right-4 top-4 h-2.5 w-2.5 rounded-full bg-paper shadow-[inset_0_1px_2px_rgb(18_26_51/.25)]"></span>
                    <div class="flex flex-col gap-1.5 px-8 pt-9">
                        <h3 class="text-[17px] font-bold">{{ $plan['name'] }}</h3>
                        <p class="ri-headline mt-2 text-6xl">{{ Plans::inr($plan['price_inr']) }}</p>
                    </div>
                    <div @class(['mx-8 mt-6 h-2 rounded', 'bg-heat' => $hi, 'bg-lilac' => ! $hi])></div>
                    <p @class(['mx-8 mt-5 text-[15px] leading-relaxed', 'text-[#E4E1FF]' => $hi, 'text-muted' => ! $hi])>{{ __($plan['term_label']) }}, {{ lcfirst(str_replace(' · ', ', ', Plans::limitsLabel($plan))) }}.</p>
                </a>
            @endforeach
        </div>
        <div class="mt-10 flex flex-wrap justify-between gap-4 text-[15px] text-muted">
            <p data-addons-line>{{ __('Add-ons when you need them:') }} {{ collect($eventAddons)->map(fn ($a) => __($a['short']).' '.Plans::inr($a['price_inr']))->push(__('platform mail'), __('WhatsApp'))->implode(', ') }}.</p>
            @if ($custom)
                <a href="{{ route('pricing') }}#custom" class="font-semibold text-track hover:text-track-deep">{{ __(':plan plans for federations and series', ['plan' => $custom['name']]) }}</a>
            @endif
        </div>
    </section>

    @include('marketing.partials.cta-band')
@endsection
