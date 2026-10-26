@extends('marketing.layout')

@php
    $starter = $plans[config('plans.default')];
    $faq = [
        __('How does pricing work?') => __('Single Event is :single for one race (up to 500 participants) in a 90-day window. Club Season is :club for up to 5 races a year, Circuit Season :circuit for up to 25. Larger series get a Custom quote. Prices exclude GST.', ['single' => \App\Saas\Plans::inr($plans['single_event']['price_inr']), 'club' => \App\Saas\Plans::inr($plans['club_season']['price_inr']), 'circuit' => \App\Saas\Plans::inr($plans['circuit_season']['price_inr'])]),
        __('What if a race fills up my plan?') => __('New registrations pause at your plan\'s participant limit, never silently going over. Upgrade to the next plan, buy a participant pack for that race, or talk to Indefine.'),
        __('Is email included?') => __('Yes. Connect your own SMTP (Google Workspace, Gmail app password or your domain\'s mail server) at no extra charge. No mail server? Platform mail is a paid add-on.'),
        __('Who collects the entry fees?') => __('You do. Connect your own Razorpay or Cashfree account and participants pay you directly. EventFlow never holds your money.'),
        __('Which sports does it support?') => __('Running, skating and cycling come with ready sport packs (distances, age bands, form fields and race sheets). Any other event works with the generic pack.'),
        __('Do I need a developer?') => __('No. Set up an event in the editor, or describe it in a short YAML file if you prefer. Your event page is live at your own link straight away.'),
        __('What happens after the free trial?') => __(':days days free, no card needed. Online plan billing is being connected; until it is live Indefine contacts you before anything is charged.', ['days' => $trialDays]),
        __('Can participants get passes on WhatsApp?') => __('Yes, with the paid WhatsApp add-on on your own WhatsApp Business number. Participants also get the pass by email with the PDF attached.'),
    ];
    $features = [
        ['title' => __('Registration that converts'), 'body' => __('Mobile-first event pages in your colours, with distances, prices, age categories and your own questions. Age is checked from date of birth.'), 'icon' => 'M4 6h16M4 12h16M4 18h10'],
        ['title' => __('Razorpay or Cashfree'), 'body' => __('Participants pay by UPI, card or net banking into your own gateway account. Webhooks confirm payment, retries are safe.'), 'icon' => 'M3 7h18v10H3zM3 11h18'],
        ['title' => __('EF- passes with QR'), 'body' => __('Every paid entry gets a branded pass (PNG + PDF) by email with a signed QR code that cannot be forged.'), 'icon' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2z'],
        ['title' => __('Volunteer QR check-in'), 'body' => __('Volunteers scan passes on their phones. Duplicate scans are flagged with the time of the first check-in.'), 'icon' => 'M5 12l5 5L20 7'],
        ['title' => __('Race sheets and certificates'), 'body' => __('Heats by distance, age band and gender as CSV or print-ready PDF. Finisher certificates from a template gallery at A4.'), 'icon' => 'M6 3h9l3 3v15H6zM9 12h6M9 16h6'],
        ['title' => __('WhatsApp notifications'), 'body' => __('Add-on: confirmations with the pass link and race-day reminders on your WhatsApp Business number.'), 'icon' => 'M4 20l1.5-4A8 8 0 1 1 8 18.5z'],
        ['title' => __('Sport packs'), 'body' => __('Skating, running, cycling or generic: sensible distances, age bands, fields and heat sizes out of the box.'), 'icon' => 'M12 3l9 5-9 5-9-5zM3 13l9 5 9-5'],
        ['title' => __('Your brand, your sender'), 'body' => __('Emails carry your logo and colours. Add your own sending domain with the custom sender add-on.'), 'icon' => 'M4 6l8 6 8-6M4 6h16v12H4z'],
    ];
@endphp

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-[#0b1020] text-white">
        <div class="pointer-events-none absolute -right-40 -top-40 h-[32rem] w-[32rem] rounded-full bg-[#ff6e61]/25 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -left-32 bottom-0 h-80 w-80 rounded-full bg-[#d9ff45]/15 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-6xl px-4 sm:px-6 pt-14 pb-20 sm:pt-20 sm:pb-28 grid gap-12 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-[#d9ff45]">
                    {{ __('Race registration for India') }}
                </p>
                <h1 class="mt-6 text-4xl sm:text-6xl font-black leading-[1.05] tracking-tight">
                    {{ __('Fill your start line.') }}<br>
                    <span class="text-[#d9ff45]">{{ __('Keep your margins.') }}</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-white/75">
                    {{ __('Branded registration pages, payments straight to your Razorpay or Cashfree account, EF- passes with QR, and volunteer check-in.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('pricing') }}" class="inline-flex justify-center rounded-full bg-[#d9ff45] px-7 py-3.5 font-bold text-[#0b1020] hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#d9ff45] focus:ring-offset-2 focus:ring-offset-[#0b1020]">
                        {{ __('Start :days-day free trial', ['days' => $trialDays]) }}
                    </a>
                    <a href="#how-it-works" class="inline-flex justify-center rounded-full border border-white/25 px-7 py-3.5 font-semibold hover:bg-white/10">
                        {{ __('See how it works') }}
                    </a>
                </div>
                <p class="mt-4 text-sm text-white/60">
                    <span class="font-semibold text-white">{{ __(':plan :price', ['plan' => $starter['name'], 'price' => \App\Saas\Plans::inr($starter['price_inr'])]) }}</span>
                    · {{ \App\Saas\Plans::limitsLabel($starter) }} · {{ __($starter['term_label']) }} · {{ __('no card for the trial') }}
                </p>
            </div>

            {{-- Pass mock-up --}}
            <div class="relative mx-auto w-full max-w-sm" aria-hidden="true">
                <div class="absolute inset-0 translate-x-4 translate-y-4 rotate-3 rounded-3xl bg-[#ff6e61]"></div>
                <div class="relative rounded-3xl bg-white p-6 text-[#0b1020] shadow-2xl">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-500">{{ __('Participant pass') }}</p>
                        <span class="rounded-full bg-[#d9ff45] px-2.5 py-0.5 text-xs font-bold">{{ __('Paid') }}</span>
                    </div>
                    <p class="mt-4 text-2xl font-black leading-tight">Bengaluru City Marathon</p>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Sun 17 Jan · 5:30 AM · Half Marathon') }}</p>
                    <div class="mt-6 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Pass no') }}</p>
                            <p class="mt-1 font-mono text-lg font-bold">EF-7Q2K9M4X1B8C</p>
                            <p class="mt-3 text-sm font-semibold">Asha Rao</p>
                        </div>
                        <div class="grid h-24 w-24 grid-cols-6 gap-0.5 rounded-lg bg-white p-1.5 ring-1 ring-gray-200">
                            @foreach ([1,0,1,1,0,1, 0,1,0,1,1,0, 1,1,0,0,1,1, 0,1,1,0,1,0, 1,0,0,1,0,1, 1,1,0,1,1,1] as $cell)
                                <span class="{{ $cell ? 'bg-[#0b1020]' : 'bg-transparent' }} rounded-[1px]"></span>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-6 rounded-2xl bg-[#f7f6f1] px-4 py-3 text-sm">
                        <span class="font-semibold">{{ __('Checked in') }}</span>
                        <span class="text-gray-600">· {{ __('Gate 2 · 5:02 AM') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="scroll-mt-16 mx-auto max-w-6xl px-4 sm:px-6 py-20">
        <div class="text-center">
            <p class="text-sm font-bold uppercase tracking-widest text-[#c2410c]">{{ __('Pricing') }}</p>
            <h2 class="mt-3 text-3xl sm:text-4xl font-black tracking-tight">{{ __('One race from :price. A season when you need it.', ['price' => \App\Saas\Plans::inr($starter['price_inr'])]) }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-gray-600">{{ __('Every price shows the races and participants it covers. Email is included; prices exclude GST. Start with a :days-day trial.', ['days' => $trialDays]) }}</p>
        </div>
        <div class="mt-14 grid gap-6 md:grid-cols-2 xl:grid-cols-4 xl:items-stretch">
            @foreach ($plans as $key => $plan)
                @include('marketing.partials.plan-card')
            @endforeach
        </div>
        <p class="mt-8 text-center text-sm"><a href="{{ route('pricing') }}" class="font-semibold underline underline-offset-4">{{ __('Compare plans and add-ons') }}</a></p>
    </section>

    {{-- Social proof (placeholders until real customers agree to be named) --}}
    <section class="border-b border-black/5 bg-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-10">
            <p class="text-center text-sm font-semibold uppercase tracking-widest text-gray-500">{{ __('Built for clubs, academies and race organisers across India') }}</p>
            <ul class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-center text-sm font-bold text-gray-400">
                @foreach ([__('Your running club'), __('Your skating academy'), __('Your cycling series'), __('Your city marathon')] as $placeholder)
                    <li class="rounded-2xl border border-dashed border-gray-300 px-4 py-5">{{ $placeholder }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="scroll-mt-16 mx-auto max-w-6xl px-4 sm:px-6 py-20">
        <p class="text-sm font-bold uppercase tracking-widest text-[#c2410c]">{{ __('How it works') }}</p>
        <h2 class="mt-3 max-w-2xl text-3xl sm:text-4xl font-black tracking-tight">{{ __('From announcement to finish line in four steps.') }}</h2>
        <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                [__('Pick a sport pack'), __('Start from running, skating, cycling or generic defaults: distances, age bands and form fields.')],
                [__('Publish your page'), __('Add prices, dates and your colours. Share your link: :link', ['link' => (parse_url(config('app.url'), PHP_URL_HOST) ?: 'eventflow').'/your-club/your-race'])],
                [__('Collect entries'), __('Participants register and pay you through Razorpay or Cashfree. Passes go out automatically.')],
                [__('Run race day'), __('Volunteers scan QR passes, you print race sheets, finishers get certificates.')],
            ] as $i => [$title, $body])
                <li class="rounded-3xl bg-white p-6 ring-1 ring-black/5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#0b1020] font-black text-[#d9ff45]">{{ $i + 1 }}</span>
                    <h3 class="mt-5 text-lg font-extrabold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ $body }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Features --}}
    <section id="features" class="scroll-mt-16 bg-[#0b1020] text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-20">
            <p class="text-sm font-bold uppercase tracking-widest text-[#d9ff45]">{{ __('Features') }}</p>
            <h2 class="mt-3 max-w-2xl text-3xl sm:text-4xl font-black tracking-tight">{{ __('Everything an organiser needs. Nothing participants have to install.') }}</h2>
            <div class="mt-12 grid gap-px overflow-hidden rounded-3xl bg-white/10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $feature)
                    <div class="bg-[#0b1020] p-6">
                        <svg class="h-7 w-7 text-[#d9ff45]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $feature['icon'] }}"/></svg>
                        <h3 class="mt-4 font-extrabold">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm text-white/70">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>


    {{-- Testimonials (placeholders) --}}
    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-20 grid gap-6 md:grid-cols-2">
            @foreach ([
                [__('“Registrations, payments and check-in in one place. Our volunteers learnt the scanner in five minutes.”'), __('Race director, running club (placeholder)')],
                [__('“Parents got passes on WhatsApp and heats were ready the night before. No more spreadsheets.”'), __('Coach, skating academy (placeholder)')],
            ] as [$quote, $who])
                <figure class="rounded-3xl bg-[#f7f6f1] p-8">
                    <blockquote class="text-xl font-semibold leading-relaxed">{{ $quote }}</blockquote>
                    <figcaption class="mt-4 text-sm text-gray-600">{{ $who }}</figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="scroll-mt-16 mx-auto max-w-3xl px-4 sm:px-6 py-20">
        <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-center">{{ __('Questions organisers ask') }}</h2>
        <div class="mt-10 divide-y divide-black/10 rounded-3xl bg-white ring-1 ring-black/5">
            @foreach ($faq as $q => $a)
                <details class="group p-6">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold">
                        {{ $q }}
                        <span class="text-xl text-[#c2410c] transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-gray-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="mx-auto max-w-6xl px-4 sm:px-6 pb-20">
        <div class="relative overflow-hidden rounded-[2rem] bg-[#ff6e61] px-6 py-14 sm:px-14 text-[#0b1020]">
            <div class="pointer-events-none absolute -right-10 -top-10 h-64 w-64 rounded-full bg-[#d9ff45]/60 blur-2xl" aria-hidden="true"></div>
            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight">{{ __('Open registrations this week.') }}</h2>
                    <p class="mt-3 max-w-xl font-medium">{{ __('Set up your first event during the free trial. Talk to us if you run a federation or a multi-city series.') }}</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('pricing') }}" class="inline-flex justify-center rounded-full bg-[#0b1020] px-7 py-3.5 font-bold text-white hover:bg-[#1f2a4a]">{{ __('Choose a plan') }}</a>
                    <a href="mailto:{{ $contactEmail }}" class="inline-flex justify-center rounded-full border-2 border-[#0b1020] px-7 py-3.5 font-bold hover:bg-[#0b1020] hover:text-white">{{ __('Talk to Indefine') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
