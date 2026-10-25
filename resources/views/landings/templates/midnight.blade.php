{{-- Midnight: race night. Full-bleed dark photo hero with giant uppercase title and a date block,
     logo on a light chip (dark logos stay visible), distances as track "lanes", register on a light
     card over dark, two-column FAQ. --}}
@extends('landings.layout')

@section('body-class', 'bg-[#07090f] text-white')

@section('content')
    <section class="relative min-h-[88vh] overflow-hidden">
        @if ($page->heroUrl())
            <img src="{{ $page->heroUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40" onerror="this.remove()">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-[#07090f] via-[#07090f]/70 to-transparent" aria-hidden="true"></div>
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-[var(--brand-primary)]" aria-hidden="true"></div>

        <header class="relative mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-8">
            <span class="inline-flex items-center rounded-full bg-white px-4 py-2 text-gray-900 shadow-lg">
                @include('landings.partials.logo', ['class' => 'h-7', 'textClass' => 'text-sm font-bold'])
            </span>
            <span class="hidden text-xs font-semibold uppercase tracking-[0.3em] text-white/60 sm:inline">{{ $page->config->venue }}</span>
        </header>

        <div class="relative mx-auto flex max-w-7xl flex-col justify-end px-4 pb-20 pt-24 sm:px-8 lg:pt-40">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-4xl">
                    <p class="text-sm font-bold uppercase tracking-[0.4em] text-[var(--brand-accent-dark)]">{{ $page->config->name }}</p>
                    <h1 class="mt-4 text-5xl font-black uppercase leading-[0.9] tracking-tight sm:text-7xl lg:text-8xl">{{ $page->config->hero->title }}</h1>
                    @if ($page->config->hero->subtitle)
                        <p class="mt-6 max-w-2xl text-lg text-white/70">{{ $page->config->hero->subtitle }}</p>
                    @endif
                </div>
                <div class="flex w-fit shrink-0 items-stretch overflow-hidden rounded-2xl ring-1 ring-white/15">
                    <div class="bg-[var(--brand-primary)] px-6 py-5 text-center text-[var(--brand-on-primary)]">
                        <p class="text-5xl font-black leading-none">{{ $page->date->format('d') }}</p>
                        <p class="mt-1 text-xs font-bold uppercase tracking-widest">{{ $page->date->format('M Y') }}</p>
                    </div>
                    <div class="bg-white/5 px-6 py-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-widest text-white/50">{{ __('Flag off') }}</p>
                        <p class="mt-1 text-2xl font-bold">{{ $page->date->format('g:i A') }}</p>
                        <p class="text-xs text-white/50">{{ $page->date->format('l') }} · IST</p>
                    </div>
                </div>
            </div>
            @if ($page->open)
                <a href="#register" class="mt-12 inline-flex w-fit items-center gap-3 rounded-full bg-[var(--brand-primary)] px-8 py-4 text-lg font-black uppercase tracking-wider text-[var(--brand-on-primary)] hover:opacity-90">{{ __('Claim your bib') }} <span aria-hidden="true">→</span></a>
            @else
                <p class="mt-12 text-lg font-bold uppercase tracking-wider text-white/60">{{ __('Registration closed') }}</p>
            @endif
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 sm:px-8">
        <section class="py-20">
            <div class="flex items-end justify-between gap-6">
                <h2 class="text-3xl font-black uppercase tracking-tight sm:text-4xl">{{ __('Choose your lane') }}</h2>
                <p class="hidden text-sm text-white/50 sm:block">{{ __('From :price', ['price' => $page->fromPrice()]) }}</p>
            </div>
            <ol class="mt-10 divide-y divide-white/10 border-y border-white/10">
                @foreach ($page->distances as $i => $row)
                    <li @class(['grid grid-cols-[auto_1fr_auto] items-center gap-6 py-6', 'opacity-50' => $row['full']])>
                        <span class="font-mono text-sm text-white/40">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <p class="text-2xl font-bold sm:text-3xl">{{ $row['distance']->label }}</p>
                            <p @class(['mt-1 text-sm', 'font-semibold text-red-400' => $row['full'], 'text-white/50' => ! $row['full']])>{{ $page->placesLabel($row['left']) }}</p>
                        </div>
                        <p class="text-right text-2xl font-black text-[var(--brand-accent-dark)] sm:text-3xl">{{ $row['price'] }}</p>
                    </li>
                @endforeach
            </ol>
            @if ($page->ageCategories())
                <p class="mt-6 text-sm text-white/50">{{ __('Age on race day') }}: {{ $page->ageCategories() }}</p>
            @endif
        </section>

        <section id="register" class="scroll-mt-6 grid gap-12 pb-20 lg:grid-cols-[0.7fr_1.3fr]">
            <div>
                <h2 class="text-3xl font-black uppercase tracking-tight sm:text-4xl">{{ __('Register') }}</h2>
                <p class="mt-4 text-white/60">{{ __('Your EF- pass with QR is emailed as soon as payment is confirmed.') }}</p>
                <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="mt-8 block rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 hover:bg-white/10">
                    <span class="text-xs uppercase tracking-widest text-white/50">{{ __('Venue') }}</span>
                    <span class="mt-1 block font-semibold">{{ $page->config->venue }} ↗</span>
                </a>
            </div>
            <div>@include('landings.partials.register')</div>
        </section>

        @if ($page->config->faq)
            <section class="border-t border-white/10 py-20">
                <h2 class="text-3xl font-black uppercase tracking-tight">{{ __('Need to know') }}</h2>
                <dl class="mt-10 grid gap-x-12 gap-y-8 md:grid-cols-2">
                    @foreach ($page->config->faq as $item)
                        <div>
                            <dt class="font-bold text-[var(--brand-accent-dark)]">{{ $item->q }}</dt>
                            <dd class="mt-2 text-white/70">{{ $item->a }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif
    </main>
@endsection
