{{-- Heritage: classic sports meet. Cream paper, centred crest (logo in a ring), serif headings with
     ornamental rules, "Order of events" table, entry fees with dotted leaders, register in a double-
     ruled panel, FAQ as numbered notices. --}}
@extends('landings.layout')

@section('body-class', 'bg-[#f6f1e7] text-[#2b2416]')

@push('head')
    <link href="https://fonts.bunny.net/css?family=playfair-display:500,700,800&display=swap" rel="stylesheet" />
    <style>.ef-serif { font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; }</style>
@endpush

@section('content')
    @php($rule = '<div class="mx-auto flex max-w-xs items-center gap-3 text-[var(--brand-secondary)]" aria-hidden="true"><span class="h-px flex-1 bg-current"></span><span class="text-xs">◆</span><span class="h-px flex-1 bg-current"></span></div>')

    <div class="h-2 bg-[var(--brand-primary)]"></div>
    <header class="border-b border-[#2b2416]/15 bg-[#fbf8f1]">
        <div class="mx-auto max-w-5xl px-4 py-14 text-center sm:px-6">
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-white p-4 ring-4 ring-[var(--brand-secondary)] ring-offset-4 ring-offset-[#fbf8f1]">
                @include('landings.partials.logo', ['class' => 'max-h-16', 'textClass' => 'ef-serif text-sm font-bold leading-tight'])
            </div>
            <p class="mt-8 text-xs font-semibold uppercase tracking-[0.35em] text-[#2b2416]/60">{{ $page->orgName }} {{ __('presents') }}</p>
            <h1 class="ef-serif mx-auto mt-4 max-w-3xl text-4xl font-bold leading-tight sm:text-6xl">{{ $page->config->name }}</h1>
            <div class="mt-6">{!! $rule !!}</div>
            @if ($page->config->hero->title !== $page->config->name)
                <p class="ef-serif mt-6 text-xl italic">{{ $page->config->hero->title }}</p>
            @endif
            @if ($page->config->hero->subtitle)
                <p class="mx-auto mt-3 max-w-2xl text-[#2b2416]/70">{{ $page->config->hero->subtitle }}</p>
            @endif
            <p class="mt-8 text-sm font-semibold uppercase tracking-[0.2em]">{{ $page->date->format('l, j F Y') }} · {{ $page->config->venue }}</p>
            @if ($page->open)
                <a href="#register" class="mt-10 inline-block border-2 border-[#2b2416] px-10 py-3 text-sm font-bold uppercase tracking-[0.25em] hover:bg-[#2b2416] hover:text-[#fbf8f1]">{{ __('Enter the meet') }}</a>
            @else
                <p class="mt-10 text-sm font-bold uppercase tracking-[0.25em] text-[#2b2416]/60">{{ __('Entries closed') }}</p>
            @endif
        </div>
    </header>

    @if ($page->heroUrl())
        <div class="mx-auto max-w-5xl px-4 pt-12 sm:px-6">
            <img src="{{ $page->heroUrl() }}" alt="" class="aspect-[16/7] w-full object-cover sepia-[15%] ring-1 ring-[#2b2416]/20" onerror="this.parentElement.remove()">
        </div>
    @endif

    <main class="mx-auto max-w-5xl space-y-20 px-4 py-16 sm:px-6">
        <section class="grid gap-12 md:grid-cols-2">
            <div>
                <h2 class="ef-serif text-3xl font-bold">{{ __('Order of events') }}</h2>
                <table class="mt-6 w-full text-left">
                    <tbody class="divide-y divide-[#2b2416]/15 border-y border-[#2b2416]/30">
                        <tr><td class="py-3 pr-4 font-mono text-sm">{{ $page->date->copy()->subMinutes(60)->format('g:i A') }}</td><td class="py-3">{{ __('Reporting and bib collection') }}</td></tr>
                        @foreach ($page->distances as $i => $row)
                            <tr><td class="py-3 pr-4 font-mono text-sm">{{ $page->date->copy()->addMinutes($i * 15)->format('g:i A') }}</td><td class="py-3">{{ $row['distance']->label }}</td></tr>
                        @endforeach
                        <tr><td class="py-3 pr-4 font-mono text-sm">{{ __('After') }}</td><td class="py-3">{{ __('Results and presentation') }}</td></tr>
                    </tbody>
                </table>
                <p class="mt-3 text-xs text-[#2b2416]/60">{{ __('Indicative times; the organiser confirms start waves.') }}</p>
            </div>
            <div>
                <h2 class="ef-serif text-3xl font-bold">{{ __('Entry fees') }}</h2>
                <ul class="mt-6 space-y-4">
                    @foreach ($page->distances as $row)
                        <li @class(['flex items-baseline gap-3', 'opacity-50' => $row['full']])>
                            <span class="font-semibold">{{ $row['distance']->label }}</span>
                            <span class="flex-1 border-b border-dotted border-[#2b2416]/40" aria-hidden="true"></span>
                            <span class="ef-serif text-xl font-bold">{{ $row['full'] ? __('Full') : $row['price'] }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($page->ageCategories())
                    <p class="mt-6 text-sm text-[#2b2416]/70"><span class="font-semibold">{{ __('Age categories') }}:</span> {{ $page->ageCategories() }}</p>
                @endif
            </div>
        </section>

        {!! $rule !!}

        <section id="register" class="scroll-mt-6">
            <div class="border-4 border-double border-[#2b2416]/40 bg-[#fbf8f1] p-4 sm:p-10">
                <h2 class="ef-serif text-center text-3xl font-bold">{{ __('Entry form') }}</h2>
                @include('landings.partials.register')
            </div>
        </section>

        @if ($page->config->faq)
            <section>
                <h2 class="ef-serif text-center text-3xl font-bold">{{ __('Notices to competitors') }}</h2>
                <ol class="mx-auto mt-8 max-w-3xl space-y-6">
                    @foreach ($page->config->faq as $i => $item)
                        <li class="grid grid-cols-[2.5rem_1fr] gap-4">
                            <span class="ef-serif text-2xl font-bold text-[var(--brand-secondary)]">{{ $i + 1 }}.</span>
                            <div><p class="font-semibold">{{ $item->q }}</p><p class="mt-1 text-[#2b2416]/75">{{ $item->a }}</p></div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    </main>
@endsection
