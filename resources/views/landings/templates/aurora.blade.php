{{-- Aurora: light athletic. Airy two-column hero over soft brand gradients, photo card, fact chips,
     distance cards, timeline schedule, form in a white card, FAQ accordion. --}}
@extends('landings.layout')

@section('body-class', 'bg-[#fbfcff] text-slate-900')

@section('content')
    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-40 -left-32 h-[28rem] w-[28rem] rounded-full bg-[var(--brand-primary)] opacity-25 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-24 right-[-10rem] h-[30rem] w-[30rem] rounded-full bg-[var(--brand-secondary)] opacity-20 blur-3xl" aria-hidden="true"></div>

        <header class="relative mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6">
            <a href="{{ $page->preview ? '#' : route('landing.org', $page->orgSlug) }}" class="flex items-center gap-3">
                @include('landings.partials.logo', ['class' => 'h-10', 'textClass' => 'text-lg font-bold'])
            </a>
            @if ($page->open)
                <a href="#register" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Register') }}</a>
            @endif
        </header>

        <section class="relative mx-auto grid max-w-6xl gap-10 px-4 pb-16 pt-6 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:pb-24">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-slate-600 ring-1 ring-slate-200">
                    <span class="h-2 w-2 rounded-full bg-[var(--brand-secondary)]"></span>
                    {{ $page->date->format('j M Y') }}
                </p>
                <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">{{ $page->config->hero->title }}</h1>
                @if ($page->config->hero->subtitle)
                    <p class="mt-5 max-w-xl text-lg text-slate-600">{{ $page->config->hero->subtitle }}</p>
                @endif
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @if ($page->open)
                        <a href="#register" class="rounded-full bg-[var(--brand-primary)] px-7 py-3.5 font-bold text-[var(--brand-on-primary)] shadow-lg shadow-black/5 hover:opacity-90">{{ __('Register from :price', ['price' => $page->fromPrice()]) }}</a>
                    @else
                        <span class="rounded-full bg-slate-200 px-7 py-3.5 font-bold text-slate-600">{{ $page->closedLabel() }}</span>
                    @endif
                    <a href="#distances" class="rounded-full px-5 py-3.5 font-semibold text-slate-700 hover:bg-white">{{ __('See distances') }} →</a>
                </div>
                <ul class="mt-10 flex flex-wrap gap-3 text-sm">
                    <li class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100"><span class="block text-xs text-slate-500">{{ __('Start') }}</span><span class="font-semibold">{{ $page->date->format('D j M · g:i A') }}</span></li>
                    <li class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100"><span class="block text-xs text-slate-500">{{ __('Venue') }}</span><a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="font-semibold hover:underline">{{ $page->config->venue }}</a></li>
                    <li class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100"><span class="block text-xs text-slate-500">{{ __('Distances') }}</span><span class="font-semibold">{{ count($page->distances) }}</span></li>
                </ul>
            </div>

            <div class="relative">
                <div class="aspect-[4/5] overflow-hidden rounded-[2rem] bg-gradient-to-br from-[var(--brand-primary)] to-[var(--brand-secondary)] shadow-2xl shadow-slate-900/10">
                    @if ($page->heroUrl())
                        <img src="{{ $page->heroUrl() }}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                    @else
                        {{-- No photo yet: running-track lanes --}}
                        <svg class="h-full w-full text-white/40" viewBox="0 0 400 500" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                            @foreach ([60, 110, 160, 210] as $r)
                                <rect x="{{ 200 - $r }}" y="{{ 250 - $r * 1.6 }}" width="{{ $r * 2 }}" height="{{ $r * 3.2 }}" rx="{{ $r }}" />
                            @endforeach
                            <text x="200" y="262" text-anchor="middle" fill="currentColor" stroke="none" font-size="44" font-weight="900" font-family="inherit">{{ $page->date->format('j M') }}</text>
                        </svg>
                    @endif
                </div>
                <div class="absolute -bottom-6 -left-4 rounded-2xl bg-white px-5 py-4 shadow-xl ring-1 ring-slate-100 sm:-left-8">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $page->orgName }}</p>
                    <p class="mt-1 text-lg font-extrabold">{{ $page->config->name }}</p>
                </div>
            </div>
        </section>
    </div>

    <main class="mx-auto max-w-6xl space-y-24 px-4 pb-16 sm:px-6">
        <section id="distances" class="scroll-mt-6">
            <h2 class="text-3xl font-extrabold tracking-tight">{{ __('Pick your distance') }}</h2>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($page->distances as $row)
                    <article @class(['rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100', 'opacity-60' => $row['full']])>
                        <span class="inline-flex rounded-full bg-[var(--brand-secondary)] px-3 py-1 text-xs font-bold text-[var(--brand-on-secondary)]">{{ $row['distance']->code }}</span>
                        <h3 class="mt-4 text-lg font-bold">{{ $row['distance']->label }}</h3>
                        <p class="mt-6 text-3xl font-black">{{ $row['price'] }}</p>
                        <p @class(['mt-1 text-sm', 'font-semibold text-red-600' => $row['full'], 'text-slate-500' => ! $row['full']])>{{ $page->placesLabel($row['left']) }}</p>
                    </article>
                @endforeach
            </div>
            @if ($page->ageCategories())
                <p class="mt-5 text-sm text-slate-500">{{ __('Age on race day') }}: {{ $page->ageCategories() }}</p>
            @endif
        </section>

        <section class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight">{{ __('Race day') }}</h2>
                <ol class="mt-8 space-y-6 border-l-2 border-[var(--brand-primary)] pl-6">
                    <li><p class="text-sm text-slate-500">{{ $page->date->copy()->subMinutes(60)->format('g:i A') }}</p><p class="font-semibold">{{ __('Reporting and bib collection') }}</p></li>
                    <li><p class="text-sm text-slate-500">{{ $page->date->format('g:i A') }}</p><p class="font-semibold">{{ __('Start') }} · {{ $page->config->venue }}</p></li>
                    <li><p class="text-sm text-slate-500">{{ __('After the finish') }}</p><p class="font-semibold">{{ __('Results and certificates') }}</p></li>
                </ol>
            </div>
            <div id="register" class="scroll-mt-6">
                <h2 class="text-3xl font-extrabold tracking-tight">{{ __('Register') }}</h2>
                @include('landings.partials.register')
            </div>
        </section>

        @if ($page->config->faq)
            <section class="mx-auto max-w-3xl">
                <h2 class="text-center text-3xl font-extrabold tracking-tight">{{ __('Questions') }}</h2>
                <div class="mt-8 space-y-3">
                    @foreach ($page->config->faq as $item)
                        <details class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">{{ $item->q }}<span class="text-[var(--brand-secondary)] transition group-open:rotate-45" aria-hidden="true">+</span></summary>
                            <p class="mt-3 text-slate-600">{{ $item->a }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection
