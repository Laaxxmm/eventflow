@extends('landing.layout')

@section('title', $org->name)

@section('content')
    <header class="bg-[var(--brand-secondary)] text-[var(--brand-on-secondary)]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <h1 class="text-3xl sm:text-5xl font-extrabold">{{ $org->name }}</h1>
            <p class="mt-3 opacity-90">{{ __('Upcoming events') }}</p>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        @if ($events->isEmpty())
            <p class="text-muted">{{ __('No events are open right now. Check back soon.') }}</p>
        @else
            <ul class="grid gap-6 sm:grid-cols-2">
                @foreach ($events as $event)
                    @php($primary = $event->colours['primary'] ?? null)
                    <li>
                        <a href="{{ route('landing.show', [$org->slug, $event->slug]) }}" class="block h-full rounded-lg bg-white p-6 shadow-sm border-t-4 border-[var(--brand-primary)] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)]" @if ($primary) style="border-top-color: {{ $primary }}" @endif>
                            <p class="text-sm font-semibold text-muted">{{ $event->date->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE)->format('D, j M Y') }}</p>
                            <h2 class="mt-1 text-xl font-bold">{{ $event->name }}</h2>
                            <p class="mt-1 text-muted">{{ $event->venue }}</p>
                            <p class="mt-4 text-sm font-semibold">
                                {{ collect($event->distances)->pluck('label')->implode(' · ') }}
                            </p>
                            <p class="mt-1 text-sm text-muted">{{ __('From ₹:price', ['price' => number_format($event->price_paise / 100)]) }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </main>
@endsection
