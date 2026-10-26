@extends('marketing.layout')

@php
    use App\Saas\Plans;
    use App\View\SportImagery;

    $entryPlan = $entry['plan'] ?? null;
    $entryHref = $entry ? route('register', ['plan' => $entry['key']]) : route('pricing');
    // "Start with one race" only when the cheapest plan is a single event (Session M2 pricing).
    $entryLabel = match (true) {
        $entryPlan === null => __('Start free trial'),
        ($entryPlan['max_events'] ?? null) === 1 => __('Start with one race — :price', ['price' => Plans::inr($entryPlan['price_inr'])]),
        default => __('Start your season — :price', ['price' => Plans::inr($entryPlan['price_inr'])]),
    };
    $checklist = [
        [__('A race page that sells'), __('Your logo and colours on one of five sports page designs. Distances, prices, schedule, FAQ, sponsors.')],
        [__('Money into your account'), __('UPI, cards and net banking through your own Razorpay or Cashfree. EventFlow never holds entry fees.')],
        [__('EF- pass with signed QR'), __('Every paid entry gets a branded pass by email (and WhatsApp with the add-on). QR codes cannot be forged.')],
        [__('Volunteer check-in'), __('Scan passes on any phone at the gate. Duplicates are flagged with the first check-in time.')],
        [__('Heats and race sheets'), __('By distance, age band and gender, as CSV or print-ready PDF, the night before.')],
        [__('Finisher certificates'), __('Five print-ready A4 designs with an EF-CERT number and a verify QR on each.')],
    ];
    $sports = [
        ['running', __('Running'), __('5K to full marathon, age bands, bib collection.')],
        ['cycling', __('Cycling'), __('Criteriums and gran fondos, category waves.')],
        ['skating', __('Skating'), __('Rink races by age group, heats of 6, 8 or 10.')],
    ];
    $designs = collect(config('eventflow.landing_templates'));
    $faq = [
        ['title' => __('Who collects the entry fees?'), 'body' => __('You do. Connect your own Razorpay or Cashfree account in Settings → Payments and participants pay you directly.')],
        ['title' => __('How is EventFlow priced?'), 'body' => __('Single Event is ₹3,999 for one race (up to 500 participants) in a 90-day window. Club Season is ₹12,999 for up to 5 races a year, Circuit Season ₹29,999 for up to 25. Bigger series get a Custom quote. Prices exclude GST.')],
        ['title' => __('What if a race fills up my plan?'), 'body' => __('New registrations pause at your plan\'s participant limit, never silently going over. Upgrade to the next plan, buy a participant pack for that race, or talk to Indefine.')],
        ['title' => __('Is email included?'), 'body' => __('Yes. Connect your own SMTP (Google Workspace, a Gmail app password or your domain\'s mail server) at no extra charge. No mail server? Platform mail is a paid add-on, and so is WhatsApp.')],
        ['title' => __('Which sports work?'), 'body' => __('Running, cycling and skating have sport packs with distances, age bands, form fields and heat sizes. Anything else runs on the generic pack.')],
        ['title' => __('Do I need a developer or designer?'), 'body' => __('No. A five-step wizard creates the event and publishes its page. Pick one of five designs; for a fully custom design, talk to Indefine.')],
        ['title' => __('What happens on race day?'), 'body' => __('Volunteers scan QR passes on their phones, you print heats, and finishers get certificates with a verify QR.')],
    ];
@endphp

@section('content')
    {{-- Hero: full-bleed race photo, oversized editorial type --}}
    <section class="relative isolate overflow-hidden bg-midnight text-paper">
        <x-ri.photo :src="SportImagery::pick('marketing', 0)" sport="running" kenburns class="absolute inset-0 -z-10" alt="" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink via-ink/75 to-ink/10" aria-hidden="true"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-ink to-transparent" aria-hidden="true"></div>

        <div class="mx-auto max-w-7xl px-4 pb-16 pt-20 sm:px-6 sm:pb-24 sm:pt-28 lg:px-8 lg:pt-36">
            <p class="ri-kicker text-heat" data-reveal>{{ __('Race registration · race-day ops · India') }}</p>
            <h1 class="ri-headline mt-6 max-w-5xl text-[3.6rem] sm:text-[6.5rem] lg:text-[9rem]" data-reveal>
                {{ __('Sell out') }}<br>{{ __('your start') }} <span class="text-heat">{{ __('line.') }}</span>
            </h1>
            <p class="mt-8 max-w-xl text-lg text-paper/80 sm:text-xl" data-reveal>
                {{ __('Sports-grade event pages, payments straight into your account, QR passes and volunteer check-in. Built for Indian running, cycling and skating organisers.') }}
            </p>
            <div class="mt-10 flex flex-col gap-3 sm:flex-row" data-reveal>
                <x-ri.button variant="heat" size="lg" :href="$entryHref">{{ $entryLabel }}</x-ri.button>
                <x-ri.button variant="ghost-light" size="lg" :href="route('pricing')">{{ __('See season packs') }}</x-ri.button>
            </div>
            <ul class="mt-12 flex flex-wrap gap-x-8 gap-y-3 text-sm text-paper/75" data-reveal>
                @foreach ([__('QR pass on every entry'), __('Check-in on any phone'), __('Certificates with verify QR')] as $item)
                    <li class="flex items-center gap-3">@include('marketing.partials.tick'){{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Pricing strip, first-class --}}
    <section id="pricing" class="scroll-mt-16 bg-paper">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="ri-kicker text-heat">{{ __('Pricing') }}</p>
                    <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Pick your distance.') }}</h2>
                </div>
                <p class="max-w-md text-ink/70">{{ __('Start with one race, grow into a season. Every price shows the races and participants it covers. Email is included; prices exclude GST.') }}</p>
            </div>
            <div class="mt-12">
                @include('marketing.partials.pricing-strip')
            </div>
            <p class="mt-6 text-sm"><a href="{{ route('pricing') }}" class="font-semibold underline decoration-heat decoration-2 underline-offset-4">{{ __('Compare plans and add-ons') }}</a></p>
        </div>
    </section>

    {{-- Race-day checklist --}}
    <section id="race-day" class="scroll-mt-16 bg-ink text-paper">
        <div class="mx-auto grid max-w-7xl gap-14 px-4 py-24 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div class="lg:sticky lg:top-28 lg:self-start">
                <p class="ri-kicker text-heat">{{ __('Race-day checklist') }}</p>
                <h2 class="ri-headline mt-4 text-6xl sm:text-8xl">{{ __('Everything ticked') }} <span class="text-heat">{{ __('before the gun.') }}</span></h2>
                <p class="mt-6 max-w-md text-paper/70">{{ __('From the announcement post to the last finisher certificate, one organiser account runs it.') }}</p>
            </div>
            <ol class="divide-y divide-paper/15 border-y border-paper/15">
                @foreach ($checklist as $i => [$title, $body])
                    <li class="grid grid-cols-[3.5rem_1fr] gap-4 py-7" data-reveal>
                        <span class="font-display text-4xl font-extrabold text-paper/30">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <h3 class="flex items-center gap-3 font-display text-3xl font-bold uppercase tracking-wide">@include('marketing.partials.tick'){{ $title }}</h3>
                            <p class="mt-2 text-paper/70">{{ $body }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Sports --}}
    <section class="bg-paper">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <p class="ri-kicker text-heat">{{ __('Sport packs') }}</p>
            <h2 class="ri-headline mt-4 max-w-3xl text-6xl sm:text-7xl">{{ __('Made for clubs that race.') }}</h2>
            <div class="mt-12 grid gap-4 md:grid-cols-3">
                @foreach ($sports as $i => [$sport, $name, $line])
                    <x-ri.photo :src="SportImagery::pick($sport, 1)" :sport="$sport" class="aspect-[4/5] text-paper" :alt="$name" data-reveal>
                        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/20 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6">
                            <p class="font-display text-6xl font-extrabold uppercase leading-none">{{ $name }}</p>
                            <p class="mt-2 text-sm text-paper/80">{{ $line }}</p>
                        </div>
                    </x-ri.photo>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Event page designs --}}
    <section id="pages" class="scroll-mt-16 border-t-2 border-ink bg-mist">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="ri-kicker text-heat">{{ __('Event pages') }}</p>
                    <h2 class="ri-headline mt-4 max-w-3xl text-6xl sm:text-7xl">{{ __('Five race-day designs. Your colours.') }}</h2>
                </div>
                <p class="max-w-md text-ink/70">{{ __('Every event gets its own page the moment you publish. Your logo, brand colours and race photos; Register is never more than a thumb away.') }}</p>
            </div>
            <ul class="mt-12 grid gap-px border-2 border-ink bg-ink sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($designs as $key => $design)
                    <li class="flex flex-col bg-paper p-6" data-reveal>
                        <span class="font-display text-sm font-bold text-heat">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <p class="mt-3 font-display text-3xl font-extrabold uppercase leading-none">{{ $design['label'] }}</p>
                        <p class="mt-3 text-sm text-ink/65">{{ $design['description'] }}</p>
                    </li>
                @endforeach
            </ul>
            <p class="mt-6 text-sm text-ink/65">{{ __('Need a one-off design for a federation or city marathon?') }} <a href="mailto:{{ $contactEmail }}" class="font-semibold text-ink underline decoration-heat decoration-2 underline-offset-4">{{ __('Contact Indefine') }}</a></p>
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-paper">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <p class="ri-kicker text-heat">{{ __('How it works') }}</p>
            <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Four steps to the start line.') }}</h2>
            <ol class="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    [__('Pick a sport pack'), __('Distances, age bands and form fields for running, cycling, skating or anything else.')],
                    [__('Publish your page'), __('Five steps in the wizard: basics, sport, design, branding, publish.')],
                    [__('Collect entries'), __('Participants pay into your gateway; passes go out on their own.')],
                    [__('Run race day'), __('Scan passes, print heats, issue certificates.')],
                ] as $i => [$title, $body])
                    <li data-reveal>
                        <span class="font-display text-8xl font-extrabold leading-none text-track-text">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-display text-3xl font-bold uppercase">{{ $title }}</h3>
                        <p class="mt-2 text-ink/70">{{ $body }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="scroll-mt-16 border-t-2 border-ink bg-paper">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-24 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
            <h2 class="ri-headline text-6xl sm:text-7xl">{{ __('Organiser questions.') }}</h2>
            <x-ri.accordion :items="$faq" :open="0" />
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="bg-heat text-on-heat">
        <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4 py-20 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8">
            <h2 class="ri-headline text-7xl sm:text-9xl">{{ __('Your next') }}<br>{{ __('start line.') }}</h2>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ri.button variant="ink" size="lg" :href="$entryHref">{{ $entryLabel }}</x-ri.button>
                <x-ri.button variant="ghost" size="lg" href="mailto:{{ $contactEmail }}">{{ __('Talk to Indefine') }}</x-ri.button>
            </div>
        </div>
    </section>
@endsection
