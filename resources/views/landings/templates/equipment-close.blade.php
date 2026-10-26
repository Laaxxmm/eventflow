{{--
    Equipment Close: the sport's gear up close (shoes, bike, skates) as the hero, "Limited bibs"
    urgency, an ink stats bar and category cards with live availability. Light tone, paper.
--}}
@extends('landings.layout')

@section('body-class', 'bg-paper text-ink')

@section('content')
    @include('landings.partials.header', ['dark' => false, 'plate' => false])

    <section id="top" class="mx-auto max-w-7xl px-4 pb-16 pt-24 sm:px-6 sm:pt-32 lg:px-8">
        <div class="grid items-center gap-12 lg:grid-cols-[1fr_1fr]">
            <div>
                <div class="flex flex-wrap gap-2" data-reveal>
                    @if ($page->limited())
                        <x-ri.chip tone="heat">{{ __('Limited bibs') }}</x-ri.chip>
                        <x-ri.chip tone="outline">{{ trans_choice(':count bib left|:count bibs left', $page->placesLeft(), ['count' => number_format($page->placesLeft())]) }}</x-ri.chip>
                    @else
                        <x-ri.chip tone="heat">{{ $page->open ? __('Entries open') : $page->closedLabel() }}</x-ri.chip>
                    @endif
                </div>
                <h1 class="ri-headline mt-6 text-7xl sm:text-8xl xl:text-[8.5rem]" data-reveal>{{ $page->config->name }}</h1>
                <p class="mt-6 max-w-lg text-lg text-ink/70" data-reveal>{{ $page->config->hero->subtitle ?? $page->config->hero->title }}</p>
                <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center" data-reveal>
                    @if ($page->open)
                        <a href="{{ $page->registerUrl() }}" class="inline-flex items-center justify-center gap-3 bg-heat px-8 py-4 font-display text-2xl font-bold uppercase tracking-[0.06em] text-on-heat hover:brightness-95">{{ __('Grab your bib') }} <span aria-hidden="true">→</span></a>
                    @endif
                    <p class="text-sm text-ink/60">{{ __('From :price · :date', ['price' => $page->fromPrice(), 'date' => $page->date->format('j M Y')]) }}</p>
                </div>
            </div>
            <div class="relative" data-reveal>
                <div class="absolute -bottom-4 -right-4 h-full w-full bg-heat sm:-bottom-6 sm:-right-6" aria-hidden="true"></div>
                <x-ri.photo :src="$page->heroPhoto(2)" :sport="$page->config->sport->value" class="relative aspect-[4/5] sm:aspect-square" :alt="$page->config->hero->title" />
                <span class="absolute left-4 top-4 bg-paper px-3 py-1 font-display text-lg font-bold uppercase">{{ __(ucfirst($page->config->sport->value)) }}</span>
            </div>
        </div>
    </section>

    {{-- Stats bar --}}
    <section class="bg-ink text-paper">
        <dl class="mx-auto grid max-w-7xl grid-cols-2 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach ([
                __('Race day') => $page->date->format('D j M'),
                __('Flag-off') => $page->date->format('g:i A'),
                __('Categories') => count($page->distances),
                __('Venue') => \Illuminate\Support\Str::before($page->config->venue, ','),
            ] as $label => $value)
                <div class="border-paper/15 py-8 pr-4 [&:not(:first-child)]:lg:border-l [&:not(:first-child)]:lg:pl-8">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-paper/55">{{ $label }}</dt>
                    <dd class="mt-2 font-display text-3xl font-extrabold uppercase leading-none sm:text-4xl">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Category cards --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <p class="ri-kicker text-heat">{{ __('Categories') }}</p>
        <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Pick your race.') }}</h2>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 {{ count($page->distances) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
            @foreach ($page->distances as $row)
                @php($capacity = $row['distance']->capacity)
                @php($filled = $capacity && $row['left'] !== null ? max(0, min(100, (int) round(100 * ($capacity - $row['left']) / $capacity))) : null)
                <article data-reveal @class(['flex flex-col bg-white p-6 ring-2 ring-ink', 'opacity-60' => $row['full']])>
                    <p class="font-display text-7xl font-extrabold leading-none text-track-text">{{ \App\View\LandingPage::numeral($row['distance']) }}</p>
                    <h3 class="mt-3 font-display text-2xl font-bold uppercase">{{ \App\View\LandingPage::title($row['distance']) }}</h3>
                    <p class="mt-4 font-display text-4xl font-extrabold">{{ $row['full'] ? __('Full') : $row['price'] }}</p>
                    @if ($filled !== null)
                        <div class="mt-5">
                            <div class="h-2 bg-mist" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $filled }}" aria-label="{{ __('Bibs taken') }}">
                                <div class="h-2 bg-heat" style="width: {{ $filled }}%"></div>
                            </div>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.14em] text-ink/60">{{ $page->placesLabel($row['left']) }}</p>
                        </div>
                    @endif
                    <div class="flex-1"></div>
                    @unless ($row['full'] || ! $page->open)
                        <a href="{{ $page->registerUrl() }}" class="mt-6 inline-flex justify-center bg-ink px-5 py-3 font-display text-lg font-bold uppercase tracking-[0.06em] text-paper hover:bg-heat hover:text-on-heat">{{ __('Enter :distance', ['distance' => \App\View\LandingPage::numeral($row['distance'])]) }}</a>
                    @endunless
                </article>
            @endforeach
        </div>
        @if ($page->ageCategories())
            <p class="mt-6 text-sm text-ink/60"><span class="font-semibold text-ink">{{ __('Age groups') }}:</span> {{ $page->ageCategories() }}</p>
        @endif
    </section>

    {{-- Schedule + photo --}}
    <section class="border-y-2 border-ink bg-mist">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
            <x-ri.photo :src="$page->photo(3)" :sport="$page->config->sport->value" class="aspect-[4/3]" alt="" />
            <div>
                <p class="ri-kicker text-heat">{{ __('Schedule') }}</p>
                <h2 class="ri-headline mt-4 text-6xl">{{ $page->date->format('j F') }}</h2>
                <div class="mt-8">@include('landings.partials.schedule', ['dark' => false])</div>
                <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="mt-6 inline-block border-b-2 border-heat pb-0.5 text-sm font-semibold">{{ $page->config->venue }} ↗</a>
            </div>
        </div>
    </section>

    <section id="register" class="scroll-mt-20 mx-auto max-w-4xl px-4 py-20 sm:px-6">
        <p class="ri-kicker text-heat">{{ __('Entries') }}</p>
        <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Secure your bib.') }}</h2>
        @if ($page->config->charity)
            <p class="mt-4 border-l-4 border-heat pl-4 text-ink/70">{{ $page->config->charity }}</p>
        @endif
        @include('landings.partials.register')
    </section>

    @if ($page->config->faq)
        <section class="border-t-2 border-ink">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
                <h2 class="ri-headline text-6xl">{{ __('Good to know.') }}</h2>
                @include('landings.partials.faq', ['dark' => false])
            </div>
        </section>
    @endif

    <section class="bg-ink text-paper">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">@include('landings.partials.sponsors', ['dark' => true])</div>
    </section>
@endsection
