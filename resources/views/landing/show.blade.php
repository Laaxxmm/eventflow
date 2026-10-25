@extends('landing.layout')

@php
    /** @var \App\Landing\EventConfig $config */
    $logo = \App\Landing\Theme::url($config->branding->logo);
    $heroImage = \App\Landing\Theme::url($config->hero->image);
    $date = $config->date->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE);
@endphp

@section('title', $config->name)
@section('description', $config->hero->subtitle ?? $config->name)

@section('content')
    <header class="bg-[var(--brand-secondary)] text-[var(--brand-on-secondary)]">
        <nav class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <a href="{{ route('landing.org', $config->org) }}" class="flex items-center gap-2 font-semibold min-w-0">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="h-8 w-auto" onerror="this.remove()">
                @endif
                <span class="truncate">{{ $event->org->name }}</span>
            </a>
            @if ($open)
                <a href="#register" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold bg-[var(--brand-primary)] text-[var(--brand-on-primary)] hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-primary)]">
                    {{ __('Register') }}
                </a>
            @endif
        </nav>

        <section class="relative overflow-hidden">
            @if ($heroImage)
                <img src="{{ $heroImage }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30" onerror="this.remove()">
            @endif
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 py-16 sm:py-24">
                <p class="text-sm font-semibold uppercase tracking-widest opacity-75">{{ $config->name }}</p>
                <h1 class="mt-3 text-4xl sm:text-6xl font-extrabold leading-tight">{{ $config->hero->title }}</h1>
                @if ($config->hero->subtitle)
                    <p class="mt-4 max-w-2xl text-lg opacity-90">{{ $config->hero->subtitle }}</p>
                @endif
                <p class="mt-6 text-sm sm:text-base font-medium">
                    {{ $date->format('l, j F Y') }} · {{ $config->venue }}
                </p>
                @if ($open)
                    <a href="#register" class="mt-8 inline-flex rounded-full px-6 py-3 font-semibold bg-[var(--brand-primary)] text-[var(--brand-on-primary)] hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-primary)]">
                        {{ __('Register now') }}
                    </a>
                @else
                    <p class="mt-8 inline-flex rounded-full px-6 py-3 font-semibold bg-white/10">{{ __('Registration closed') }}</p>
                @endif
            </div>
        </section>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-12 space-y-16">
        <section aria-labelledby="schedule-heading">
            <h2 id="schedule-heading" class="text-2xl font-bold">{{ __('Schedule') }}</h2>
            <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm border-t-4 border-[var(--brand-primary)]">
                    <dt class="text-sm text-gray-600">{{ __('Race day') }}</dt>
                    <dd class="mt-1 font-semibold">{{ $date->format('D, j M Y') }}</dd>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm border-t-4 border-[var(--brand-primary)]">
                    <dt class="text-sm text-gray-600">{{ __('Start time') }}</dt>
                    <dd class="mt-1 font-semibold">{{ $date->format('g:i A') }} IST</dd>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm border-t-4 border-[var(--brand-primary)]">
                    <dt class="text-sm text-gray-600">{{ __('Venue') }}</dt>
                    <dd class="mt-1 font-semibold">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($config->venue) }}" class="underline decoration-[var(--brand-primary)] underline-offset-4" rel="noopener" target="_blank">{{ $config->venue }}</a>
                    </dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="distances-heading">
            <h2 id="distances-heading" class="text-2xl font-bold">{{ __('Distances & prices') }}</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($config->distances as $distance)
                    @php($left = $remaining[$distance->code] ?? null)
                    <li class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-600">{{ $distance->code }}</p>
                        <p class="mt-1 text-lg font-bold">{{ $distance->label }}</p>
                        <p class="mt-3 text-2xl font-extrabold">₹{{ number_format($distance->price_inr) }}</p>
                        <p class="mt-2 text-sm {{ $left === 0 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                            @if ($left === null)
                                {{ __('Open entry') }}
                            @elseif ($left === 0)
                                {{ __('Full') }}
                            @else
                                {{ trans_choice(':count place left|:count places left', $left, ['count' => number_format($left)]) }}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
            @if ($config->ageBands)
                <p class="mt-4 text-sm text-gray-600">
                    {{ __('Age categories (age on race day):') }}
                    {{ collect($config->ageBands)->map(fn ($b) => "{$b->label} {$b->min}–{$b->max}")->implode(' · ') }}
                </p>
            @endif
        </section>

        <section id="register" aria-labelledby="register-heading" class="scroll-mt-4">
            <h2 id="register-heading" class="text-2xl font-bold">{{ __('Register') }}</h2>
            @if ($open)
                @include('landing.partials.form')
            @else
                <p class="mt-4 text-gray-600">{{ __('Registration for this event is closed.') }}</p>
            @endif
        </section>

        @if ($config->faq)
            <section aria-labelledby="faq-heading">
                <h2 id="faq-heading" class="text-2xl font-bold">{{ __('FAQ') }}</h2>
                <div class="mt-6 divide-y divide-gray-200 rounded-lg bg-white shadow-sm">
                    @foreach ($config->faq as $item)
                        <details class="group p-5">
                            <summary class="cursor-pointer list-none font-semibold flex justify-between gap-4">
                                {{ $item->q }}
                                <span class="text-[var(--brand-secondary)] group-open:rotate-45 transition" aria-hidden="true">+</span>
                            </summary>
                            <p class="mt-3 text-gray-600">{{ $item->a }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection
