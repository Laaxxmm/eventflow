{{--
    Heritage Crest: federation-formal. Centred crest (logo in a double ring), widely tracked caps,
    paper background, double-ruled framed sections (order of events, entry fees, categories),
    a framed photograph, centred sponsor row and numbered notices. Light tone.
--}}
@extends('landings.layout')

@section('body-class', 'bg-paper text-ink')

@section('content')
    @php($rule = '<div class="mx-auto flex max-w-xs items-center gap-3 text-track-text" aria-hidden="true"><span class="h-px flex-1 bg-current"></span><span class="h-2 w-2 rotate-45 bg-current"></span><span class="h-px flex-1 bg-current"></span></div>')
    <div class="h-2 bg-track"></div>

    <header id="top" class="border-b-4 border-double border-ink/40">
        <div class="mx-auto max-w-5xl px-4 pb-16 pt-14 text-center sm:px-6">
            <div class="mx-auto flex h-32 w-32 items-center justify-center rounded-full bg-white p-5 ring-2 ring-ink ring-offset-4 ring-offset-paper" data-reveal>
                @include('landings.partials.logo', ['class' => 'max-h-16', 'textClass' => 'font-display text-xl font-extrabold uppercase leading-tight'])
            </div>
            <p class="mt-10 text-xs font-semibold uppercase tracking-[0.4em] text-ink/60" data-reveal>{{ __(':org presents', ['org' => $page->orgName]) }}</p>
            <h1 class="mx-auto mt-5 max-w-4xl font-display text-6xl font-extrabold uppercase leading-[0.9] tracking-[0.02em] sm:text-8xl" data-reveal>{{ $page->config->name }}</h1>
            <div class="mt-8" data-reveal>{!! $rule !!}</div>
            <p class="mt-8 font-display text-2xl font-bold uppercase tracking-[0.18em]" data-reveal>{{ $page->date->format('l, j F Y') }}</p>
            <p class="mt-2 text-ink/65" data-reveal>{{ $page->config->venue }}</p>
            @if ($page->open)
                <a href="{{ $page->registerUrl() }}" data-hero-cta data-reveal class="mt-10 inline-flex items-center gap-3 bg-heat px-10 py-4 font-display text-2xl font-bold uppercase tracking-[0.12em] text-on-heat hover:brightness-95">{{ __('Enter the meet') }}</a>
            @else
                <p class="mt-10 font-display text-2xl font-bold uppercase tracking-[0.12em] text-ink/55">{{ $page->closedLabel() }}</p>
            @endif
        </div>
    </header>

    @include('landings.partials.countdown')

    <main class="mx-auto max-w-5xl space-y-16 px-4 py-16 sm:px-6">
        <figure class="border-[10px] border-white shadow-[0_0_0_2px_rgb(var(--ri-ink))]" data-reveal>
            <x-ri.photo :src="$page->heroPhoto(0)" :sport="$page->config->sport->value" class="aspect-[16/7]" :alt="$page->config->hero->title" />
            @if ($page->config->hero->subtitle)
                <figcaption class="bg-white px-2 pt-3 text-center text-sm italic text-ink/65">{{ $page->config->hero->subtitle }}</figcaption>
            @endif
        </figure>

        <div class="grid gap-8 md:grid-cols-2">
            <section class="border-4 border-double border-ink/50 bg-white p-6 sm:p-8" data-reveal>
                <h2 class="text-center font-display text-3xl font-extrabold uppercase tracking-[0.12em]">{{ __('Order of events') }}</h2>
                <div class="mt-4">{!! $rule !!}</div>
                <div class="mt-6">@include('landings.partials.schedule', ['dark' => false])</div>
            </section>
            <section class="border-4 border-double border-ink/50 bg-white p-6 sm:p-8" data-reveal>
                <h2 class="text-center font-display text-3xl font-extrabold uppercase tracking-[0.12em]">{{ __('Entry fees') }}</h2>
                <div class="mt-4">{!! $rule !!}</div>
                <ul class="mt-6 space-y-4">
                    @foreach ($page->distances as $row)
                        <li @class(['flex items-baseline gap-3', 'opacity-50' => $row['full']])>
                            <span class="font-semibold">{{ $row['distance']->label }}</span>
                            <span class="flex-1 border-b-2 border-dotted border-ink/30" aria-hidden="true"></span>
                            <span class="font-display text-2xl font-extrabold">{{ $row['full'] ? __('Full') : $row['price'] }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($page->ageCategories())
                    <p class="mt-6 border-t border-ink/15 pt-4 text-sm text-ink/70"><span class="font-semibold text-ink">{{ __('Age categories') }}:</span> {{ $page->ageCategories() }}</p>
                @endif
            </section>
        </div>

        @if ($page->config->charity)
            <p class="mx-auto max-w-2xl text-center text-lg italic text-ink/75" data-reveal>{{ $page->config->charity }}</p>
        @endif

        <section id="register" class="scroll-mt-10 border-4 border-double border-ink/50 bg-white p-4 sm:p-10">
            <h2 class="text-center font-display text-4xl font-extrabold uppercase tracking-[0.12em]">{{ __('Entry form') }}</h2>
            <div class="mt-4">{!! $rule !!}</div>
            @include('landings.partials.register')
        </section>

        @if ($page->config->faq)
            <section>
                <h2 class="text-center font-display text-4xl font-extrabold uppercase tracking-[0.12em]">{{ __('Notices to competitors') }}</h2>
                <div class="mt-4">{!! $rule !!}</div>
                <ol class="mx-auto mt-8 max-w-3xl space-y-6">
                    @foreach ($page->config->faq as $i => $item)
                        <li class="grid grid-cols-[3rem_1fr] gap-4" data-reveal>
                            <span class="font-display text-4xl font-extrabold leading-none text-track-text">{{ $i + 1 }}.</span>
                            <div><p class="font-semibold">{{ $item->q }}</p><p class="mt-1 text-ink/70">{{ $item->a }}</p></div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <section class="border-t-4 border-double border-ink/40 pt-12">
            <p class="text-center text-xs font-semibold uppercase tracking-[0.4em] text-ink/55">{{ __('With thanks to') }}</p>
            <div class="mt-8">@include('landings.partials.sponsors', ['dark' => false, 'center' => true])</div>
        </section>
    </main>
@endsection
