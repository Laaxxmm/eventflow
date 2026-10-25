{{-- Clean: minimal editorial. One narrow column, big type, thin rules, photo set below the title,
     details as a definition list, distances as a price list with the CTA inline, FAQ as plain text
     before the form. Brand colour only as an accent. --}}
@extends('landings.layout')

@section('body-class', 'bg-white text-neutral-900')

@section('content')
    <header class="mx-auto flex max-w-3xl items-center justify-between px-5 py-8">
        @include('landings.partials.logo', ['class' => 'h-8', 'textClass' => 'font-semibold tracking-tight'])
        @if ($page->logoUrl())
            <span class="text-xs uppercase tracking-[0.25em] text-neutral-400">{{ $page->orgName }}</span>
        @endif
    </header>

    <main class="mx-auto max-w-3xl px-5">
        <section class="pt-10 pb-12">
            <p class="text-sm text-neutral-500">{{ $page->date->format('l, j F Y') }}</p>
            <h1 class="mt-4 text-5xl font-semibold leading-[1.02] tracking-tight sm:text-7xl">{{ $page->config->hero->title }}</h1>
            @if ($page->config->hero->subtitle)
                <p class="mt-6 text-xl leading-relaxed text-neutral-600">{{ $page->config->hero->subtitle }}</p>
            @endif
            <div class="mt-8 h-1 w-16 bg-[var(--brand-secondary)]" aria-hidden="true"></div>
        </section>
    </main>

    @if ($page->heroUrl())
        <figure class="mx-auto max-w-5xl px-5">
            <img src="{{ $page->heroUrl() }}" alt="" class="aspect-[21/9] w-full rounded-sm object-cover grayscale-[20%]" onerror="this.parentElement.remove()">
        </figure>
    @endif

    <main class="mx-auto max-w-3xl space-y-20 px-5 py-16">
        <dl class="divide-y divide-neutral-200 border-y border-neutral-200 text-lg">
            <div class="flex justify-between gap-6 py-4"><dt class="text-neutral-500">{{ __('When') }}</dt><dd class="text-right">{{ $page->date->format('D j M Y, g:i A') }} IST</dd></div>
            <div class="flex justify-between gap-6 py-4"><dt class="text-neutral-500">{{ __('Where') }}</dt><dd class="text-right"><a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="underline decoration-neutral-300 underline-offset-4 hover:decoration-neutral-900">{{ $page->config->venue }}</a></dd></div>
            <div class="flex justify-between gap-6 py-4"><dt class="text-neutral-500">{{ __('Organiser') }}</dt><dd class="text-right">{{ $page->orgName }}</dd></div>
            @if ($page->ageCategories())
                <div class="flex justify-between gap-6 py-4"><dt class="text-neutral-500">{{ __('Categories') }}</dt><dd class="text-right text-base">{{ $page->ageCategories() }}</dd></div>
            @endif
        </dl>

        <section>
            <h2 class="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">{{ __('Distances') }}</h2>
            <ul class="mt-6">
                @foreach ($page->distances as $row)
                    <li @class(['flex items-baseline gap-4 py-3', 'text-neutral-400' => $row['full']])>
                        <span class="text-2xl font-medium">{{ $row['distance']->label }}</span>
                        <span class="flex-1 border-b border-dotted border-neutral-300" aria-hidden="true"></span>
                        <span class="text-2xl font-semibold">{{ $row['full'] ? __('Full') : $row['price'] }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($page->open)
                <a href="#register" class="mt-10 inline-flex items-center gap-2 border-b-2 border-[var(--brand-secondary)] pb-1 text-lg font-semibold hover:gap-3">{{ __('Register now') }} <span aria-hidden="true">→</span></a>
            @endif
        </section>

        @if ($page->config->faq)
            <section>
                <h2 class="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">{{ __('Good to know') }}</h2>
                <div class="mt-6 space-y-8">
                    @foreach ($page->config->faq as $item)
                        <div>
                            <h3 class="text-lg font-semibold">{{ $item->q }}</h3>
                            <p class="mt-2 leading-relaxed text-neutral-600">{{ $item->a }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section id="register" class="scroll-mt-6">
            <h2 class="text-3xl font-semibold tracking-tight">{{ __('Register') }}</h2>
            @include('landings.partials.register')
        </section>
    </main>
@endsection
