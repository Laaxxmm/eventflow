{{-- Festival: bold colour blocks. Split hero (primary block with title, secondary block with date
     and venue stickers), marquee of distances, big tilted tiles, register on a colour band with FAQ
     cards beside it. Energetic, still tidy. --}}
@extends('landings.layout')

@section('body-class', 'bg-[#fffaf2] text-gray-900')

@push('head')
    <style>
        @keyframes ef-marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .ef-marquee { animation: ef-marquee 28s linear infinite; }
        @media (prefers-reduced-motion: reduce) { .ef-marquee { animation: none; } }
    </style>
@endpush

@section('content')
    <section class="grid lg:min-h-[80vh] lg:grid-cols-[1.4fr_1fr]">
        <div class="relative flex flex-col justify-between bg-[var(--brand-primary)] p-6 text-[var(--brand-on-primary)] sm:p-10 lg:p-14">
            <div class="flex items-center justify-between gap-4">
                <span class="inline-flex rounded-2xl bg-white px-3 py-2 text-gray-900 shadow">
                    @include('landings.partials.logo', ['class' => 'h-8', 'textClass' => 'text-sm font-black'])
                </span>
                @if ($page->open)
                    <a href="#register" class="rounded-full border-2 border-current px-5 py-2 text-sm font-black uppercase">{{ __('Register') }}</a>
                @endif
            </div>
            <div class="py-16 lg:py-0">
                <p class="text-sm font-black uppercase tracking-widest opacity-80">{{ $page->config->name }}</p>
                <h1 class="mt-4 text-5xl font-black leading-[0.95] tracking-tight sm:text-7xl">{{ $page->config->hero->title }}</h1>
                @if ($page->config->hero->subtitle)
                    <p class="mt-6 max-w-xl text-lg font-medium opacity-90">{{ $page->config->hero->subtitle }}</p>
                @endif
            </div>
            @if ($page->open)
                <a href="#register" class="inline-flex w-fit items-center gap-3 rounded-2xl bg-gray-900 px-7 py-4 text-lg font-black text-white shadow-[6px_6px_0_rgba(0,0,0,0.25)] hover:-translate-y-0.5">{{ __('I\'m in!') }} <span aria-hidden="true">→</span></a>
            @else
                <p class="font-black uppercase">{{ $page->closedLabel() }}</p>
            @endif
        </div>

        <div class="relative overflow-hidden bg-[var(--brand-secondary)] p-6 text-[var(--brand-on-secondary)] sm:p-10">
            @if ($page->heroUrl())
                <img src="{{ $page->heroUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover mix-blend-multiply opacity-60" onerror="this.remove()">
            @else
                <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full border-[28px] border-white/25" aria-hidden="true"></div>
                <div class="pointer-events-none absolute right-24 top-40 h-24 w-24 rounded-full bg-[var(--brand-primary)]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -left-10 top-10 h-40 w-40 rotate-12 rounded-[2rem] border-8 border-gray-900/80" aria-hidden="true"></div>
            @endif
            <div class="relative flex h-full flex-col items-start justify-end gap-4 py-10 lg:py-0">
                <div class="-rotate-3 rounded-3xl bg-white px-6 py-4 text-gray-900 shadow-xl">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-500">{{ $page->date->format('l') }}</p>
                    <p class="text-4xl font-black">{{ $page->date->format('j M') }}</p>
                    <p class="text-sm font-bold">{{ $page->date->format('g:i A') }} {{ __('start') }}</p>
                </div>
                <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="rotate-2 rounded-3xl bg-gray-900 px-6 py-4 text-white shadow-xl">
                    <p class="text-xs font-black uppercase tracking-widest text-white/60">{{ __('Where') }}</p>
                    <p class="text-lg font-bold">{{ $page->config->venue }} ↗</p>
                </a>
            </div>
        </div>
    </section>

    <div class="overflow-hidden border-y-4 border-gray-900 bg-white py-3" aria-hidden="true">
        <div class="ef-marquee flex w-max gap-10 whitespace-nowrap text-2xl font-black uppercase">
            @foreach (array_merge($page->distances, $page->distances, $page->distances, $page->distances) as $row)
                <span>{{ $row['distance']->label }} <span class="text-[var(--brand-secondary)]">✦</span></span>
            @endforeach
        </div>
    </div>

    <main class="mx-auto max-w-6xl space-y-20 px-4 py-20 sm:px-6">
        <section>
            <h2 class="text-4xl font-black tracking-tight sm:text-5xl">{{ __('Pick your challenge') }}</h2>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($page->distances as $i => $row)
                    <article @class([
                        'rounded-3xl border-4 border-gray-900 p-6 shadow-[8px_8px_0_#111827] transition hover:-translate-y-1',
                        'bg-[var(--brand-primary)] text-[var(--brand-on-primary)]' => $i % 2 === 0,
                        'bg-white' => $i % 2 === 1,
                        'opacity-50' => $row['full'],
                        $i % 2 === 0 ? '-rotate-1' : 'rotate-1',
                    ])>
                        <p class="text-sm font-black uppercase">{{ $row['distance']->code }}</p>
                        <h3 class="mt-2 text-2xl font-black">{{ $row['distance']->label }}</h3>
                        <p class="mt-8 text-4xl font-black">{{ $row['price'] }}</p>
                        <p class="mt-1 text-sm font-bold">{{ $page->placesLabel($row['left']) }}</p>
                    </article>
                @endforeach
            </div>
            @if ($page->ageCategories())
                <p class="mt-8 font-semibold">{{ __('Age categories') }}: {{ $page->ageCategories() }}</p>
            @endif
        </section>
    </main>

    <section id="register" class="scroll-mt-4 bg-[var(--brand-secondary)] py-20 text-[var(--brand-on-secondary)]">
        <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div>
                <h2 class="text-4xl font-black tracking-tight sm:text-5xl">{{ __('Grab your spot') }}</h2>
                @include('landings.partials.register')
            </div>
            @if ($page->config->faq)
                <div class="space-y-4 lg:pt-20">
                    @foreach ($page->config->faq as $i => $item)
                        <div class="rounded-3xl border-4 border-gray-900 bg-white p-6 text-gray-900 {{ $i % 2 ? 'rotate-1' : '-rotate-1' }}">
                            <h3 class="font-black">{{ $item->q }}</h3>
                            <p class="mt-2 text-gray-700">{{ $item->a }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
