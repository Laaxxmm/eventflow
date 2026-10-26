{{--
    Stadium Split: a solid brand (Track) panel with the event name, date/time chip and Register
    beside a large action photo; sponsor strip; accordions for categories, distances, schedule,
    venue and charity; registration and FAQ on paper. Light tone.
--}}
@extends('landings.layout')

@section('body-class', 'bg-paper text-ink')

@section('content')
    @include('landings.partials.header', ['dark' => false, 'plate' => false])

    <section id="top" class="pt-16 sm:pt-20">
        <div class="grid lg:min-h-[calc(100svh-5rem)] lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
            <x-ri.photo :src="$page->heroPhoto(0)" :sport="$page->config->sport->value" class="order-1 aspect-[4/3] lg:order-2 lg:aspect-auto" :alt="$page->config->hero->title" />

            <div class="order-2 flex flex-col justify-between gap-12 bg-track p-6 text-on-track sm:p-10 lg:order-1 lg:p-14">
                <div>
                    <p class="ri-kicker opacity-80" data-reveal>{{ __(':org presents', ['org' => $page->orgName]) }}</p>
                    <h1 class="ri-headline mt-6 text-6xl sm:text-8xl xl:text-9xl" data-reveal>{{ $page->config->name }}</h1>
                    <p class="mt-6 max-w-md text-lg opacity-85" data-reveal>{{ $page->config->hero->subtitle ?? $page->config->hero->title }}</p>
                </div>
                <div data-reveal>
                    <div class="flex flex-wrap gap-2">
                        <x-ri.chip tone="ink">{{ $page->date->format('D j M Y') }} · {{ $page->date->format('g:i A') }}</x-ri.chip>
                        <x-ri.chip tone="paper">{{ $page->config->venue }}</x-ri.chip>
                    </div>
                    @if ($page->open)
                        <a href="{{ $page->registerUrl() }}" class="mt-6 inline-flex w-full items-center justify-between gap-4 bg-heat px-7 py-4 font-display text-2xl font-bold uppercase tracking-[0.06em] text-on-heat hover:brightness-95 sm:w-auto">{{ __('Register') }} <span class="text-lg">{{ __('from :price', ['price' => $page->fromPrice()]) }} →</span></a>
                    @else
                        <p class="mt-6 font-display text-2xl font-bold uppercase opacity-70">{{ $page->closedLabel() }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Sponsor strip --}}
    <section class="border-y-2 border-ink bg-mist">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('landings.partials.sponsors', ['dark' => false])
        </div>
    </section>

    {{-- Distances + accordions --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-[0.85fr_1.15fr]">
            <div>
                <p class="ri-kicker text-heat">{{ __('The event') }}</p>
                <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Everything you need to know.') }}</h2>
                <dl class="mt-10 grid grid-cols-2 gap-px border-2 border-ink bg-ink">
                    @foreach ([
                        __('Date') => $page->date->format('j M Y'),
                        __('Start') => $page->date->format('g:i A'),
                        __('Distances') => count($page->distances),
                        __('From') => $page->fromPrice(),
                    ] as $label => $value)
                        <div class="bg-paper p-5">
                            <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-ink/55">{{ $label }}</dt>
                            <dd class="mt-1 font-display text-4xl font-extrabold">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            @php
                $items = [];
                $items[] = ['title' => __('Distances and entry fees'), 'body' => new \Illuminate\Support\HtmlString(view('landings.partials.price-list', ['page' => $page])->render())];
                if ($page->ageCategories()) {
                    $items[] = ['title' => __('Categories'), 'body' => $page->ageCategories()];
                }
                $items[] = ['title' => __('Race-day schedule'), 'body' => new \Illuminate\Support\HtmlString(view('landings.partials.schedule', ['page' => $page, 'dark' => false])->render())];
                $items[] = ['title' => __('Venue'), 'body' => new \Illuminate\Support\HtmlString(e($page->config->venue).' · <a class="font-semibold underline decoration-heat decoration-2 underline-offset-4" target="_blank" rel="noopener" href="'.e($page->mapUrl()).'">'.e(__('Open map')).'</a>')];
                if ($page->config->charity) {
                    $items[] = ['title' => __('Charity'), 'body' => $page->config->charity];
                }
            @endphp
            <x-ri.accordion :items="$items" :open="0" />
        </div>
    </section>

    {{-- Register --}}
    <section id="register" class="scroll-mt-20 border-t-2 border-ink bg-mist">
        <div class="mx-auto max-w-4xl px-4 py-20 sm:px-6">
            <p class="ri-kicker text-heat">{{ __('Entries') }}</p>
            <h2 class="ri-headline mt-4 text-6xl sm:text-7xl">{{ __('Register now.') }}</h2>
            @include('landings.partials.register')
        </div>
    </section>

    @if ($page->config->faq)
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr]">
                <h2 class="ri-headline text-6xl">{{ __('FAQ') }}</h2>
                @include('landings.partials.faq', ['dark' => false])
            </div>
        </section>
    @endif
@endsection
