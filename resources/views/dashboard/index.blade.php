@php
    $inr = fn (int $paise) => \Illuminate\Support\Number::currency($paise / 100, in: 'INR', locale: 'en_IN');
    $org = auth()->user()->org;
    $on = fn (\App\Enums\Module $m) => ! app()->bound(\App\Contracts\Entitlements::class) || app(\App\Contracts\Entitlements::class)->enabled($org, $m);
    $raceSheets = Route::has('reports.race-sheet') && $on(\App\Enums\Module::RaceSheets);
    $certificates = Route::has('reports.certificates') && $on(\App\Enums\Module::Certificates);
    $upcoming = $events->filter(fn ($e) => $e->date->isAfter(now()->subDay()))->sortBy('date');
    $past = $events->reject(fn ($e) => $e->date->isAfter(now()->subDay()));
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->before(' ');
    $trial = \App\Saas\Trial::onTrial($org) ? \App\Saas\Trial::status($org) : null;
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Home') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2>{{ __('Hi :name', ['name' => $firstName]) }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $org?->name }}</p>
        </div>
        @if ($events->isNotEmpty())
            <a href="{{ route('organiser.events.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0b1020] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                {{ __('New event') }}
            </a>
        @endif
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-10">
        @if ($trial)
            {{-- Trial banner: the same limits the homepage states. --}}
            <div data-trial-banner class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-[#0b1020] px-5 py-4 text-sm text-white">
                <p>
                    <span class="font-semibold">{{ $trial['expired'] ? __('Your free trial has ended') : trans_choice('{1} Free trial · 1 day left|[2,*] Free trial · :count days left|[0] Free trial · last day', $trial['days_left']) }}</span>
                    <span class="text-white/70">· {{ __(':e/:el live event · :r/:rl test registrations', ['e' => $trial['events'], 'el' => $trial['event_limit'], 'r' => $trial['registrations'], 'rl' => $trial['registration_limit']]) }}</span>
                </p>
                <a href="{{ route('account.plan') }}" class="rounded-lg bg-[#ff5a3c] px-4 py-2 font-semibold text-[#0b1020] hover:brightness-95">{{ __('Plans from :price', ['price' => \App\Saas\Plans::inr(\App\Saas\Plans::entry()['plan']['season_pass_inr'] ?? null)]) }}</a>
            </div>
        @endif

        @if ($events->isEmpty())
            {{-- Empty state: one primary action --}}
            <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
                <div class="grid items-center gap-8 p-8 sm:p-12 lg:grid-cols-[1.1fr_0.9fr]">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-[#c2410c]">{{ __('Let\'s get started') }}</p>
                        <h3 class="mt-3 text-3xl font-extrabold tracking-tight">{{ __('Publish your race site in under an hour') }}</h3>
                        <p class="mt-3 max-w-lg text-slate-600">{{ __('Five quick steps: the basics, a sport pack, a page design and your branding. Your runners land on a sports page, not a form — with QR passes, check-in and certificates ready.') }}</p>
                        <a href="{{ route('organiser.events.create') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-[#0b1020] px-6 py-3.5 font-semibold text-white hover:bg-[#1f2a4a]">
                            {{ __('Create your first event') }} <span aria-hidden="true">→</span>
                        </a>
                    </div>
                    <ol class="space-y-3 text-sm">
                        @foreach ([__('Basics: name, date, venue'), __('Sport pack and prices'), __('Pick a page design'), __('Logo, photo and colours'), __('Review and publish')] as $i => $step)
                            <li class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#d9ff45] text-xs font-bold text-[#0b1020]">{{ $i + 1 }}</span>
                                {{ $step }}
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @else
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    __('Registrations') => number_format($totals['registrations']),
                    __('Paid') => number_format($totals['paid']),
                    __('Collected') => $inr($totals['paid_total_paise']),
                    __('Checked in') => number_format($totals['checked_in']),
                ] as $label => $value)
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <dt class="text-sm text-slate-500">{{ $label }}</dt>
                        <dd class="mt-2 text-2xl font-bold tracking-tight">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            <section>
                <div class="flex items-end justify-between gap-4">
                    <h3 class="text-lg font-bold">{{ __('Upcoming') }}</h3>
                    <a href="{{ route('dashboard.participants') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">{{ __('All participants') }} →</a>
                </div>
                @if ($upcoming->isEmpty())
                    <p class="mt-4 rounded-2xl bg-white p-6 text-sm text-slate-600 ring-1 ring-slate-200/70">{{ __('No upcoming events. Create one when you are ready.') }}</p>
                @else
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach ($upcoming as $event)
                            @php($colours = $event->colours ?? [])
                            <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
                                <div class="h-2" style="background: linear-gradient(90deg, {{ $colours['primary'] ?? '#d9ff45' }}, {{ $colours['secondary'] ?? '#ff6e61' }})"></div>
                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $event->date->timezone('Asia/Kolkata')->format('D j M Y') }}</p>
                                            <h4 class="mt-1 truncate text-lg font-bold">{{ $event->name }}</h4>
                                            <p class="truncate text-sm text-slate-500">{{ $event->venue }}</p>
                                        </div>
                                        <span @class([
                                            'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-green-100 text-green-800' => $event->status === 'published',
                                            'bg-slate-100 text-slate-600' => $event->status !== 'published',
                                        ])>{{ __(ucfirst($event->status)) }}</span>
                                    </div>
                                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                                        <div class="rounded-xl bg-slate-50 py-2"><dt class="text-xs text-slate-500">{{ __('Entries') }}</dt><dd class="font-bold">{{ number_format($event->registrations_count) }}</dd></div>
                                        <div class="rounded-xl bg-slate-50 py-2"><dt class="text-xs text-slate-500">{{ __('Paid') }}</dt><dd class="font-bold">{{ number_format($event->paid_count) }}</dd></div>
                                        <div class="rounded-xl bg-slate-50 py-2"><dt class="text-xs text-slate-500">{{ __('Collected') }}</dt><dd class="font-bold">{{ $inr((int) $event->paid_total_paise) }}</dd></div>
                                    </dl>
                                    <div class="mt-5 flex flex-wrap gap-2 text-sm">
                                        @if ($event->status === 'published' && $org)
                                            <a href="{{ route('landing.show', [$org->slug, $event->slug]) }}" target="_blank" class="rounded-lg bg-[#0b1020] px-3 py-1.5 font-medium text-white hover:bg-[#1f2a4a]">{{ __('View page') }} ↗</a>
                                        @endif
                                        <a href="{{ route('dashboard.participants', ['event' => $event->id]) }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Participants') }}</a>
                                        <a href="{{ route('organiser.events.edit', $event) }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Edit') }}</a>
                                        @if ($raceSheets)
                                            <a href="{{ route('reports.race-sheet', $event) }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Race sheet') }}</a>
                                        @endif
                                        @if ($certificates)
                                            <a href="{{ route('reports.certificates', $event) }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Certificates') }}</a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($past->isNotEmpty())
                <section>
                    <h3 class="text-lg font-bold">{{ __('Past events') }}</h3>
                    <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
                        @foreach ($past as $event)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $event->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $event->date->timezone('Asia/Kolkata')->format('j M Y') }} · {{ number_format($event->paid_count) }} {{ __('paid') }} · {{ number_format($event->checked_in_count) }} {{ __('checked in') }}</p>
                                </div>
                                <div class="flex gap-2 text-sm">
                                    <a href="{{ route('dashboard.participants', ['event' => $event->id]) }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Participants') }}</a>
                                    @if ($certificates)
                                        <a href="{{ route('reports.certificates', $event) }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Certificates') }}</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endif
    </div>
</x-app-layout>
