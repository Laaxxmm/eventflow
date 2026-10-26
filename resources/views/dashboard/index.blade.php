{{--
    Organiser home (Daylight Track, docs/design/daylight-track/Dashboard.dc.html): next-race card, four
    totals, 30-day registration bars, "Worth doing next", upcoming races table.
--}}
@php
    $inr = fn (int $paise) => \Illuminate\Support\Number::currency($paise / 100, in: 'INR', locale: 'en_IN', precision: 0);
    $user = auth()->user();
    $org = $user->org;
    $upcoming = $events->filter(fn ($e) => $e->date->isAfter(now()->subDay()))->sortBy('date');
    $past = $events->reject(fn ($e) => $e->date->isAfter(now()->subDay()));
    $firstName = \Illuminate\Support\Str::of($user->name)->before(' ');
    $trial = \App\Saas\Trial::onTrial($org) ? \App\Saas\Trial::status($org) : null;
    $ist = fn ($d) => $d->timezone('Asia/Kolkata');
    $maxDay = max(1, collect($daily)->max('count'));
    $card = 'rounded-[24px] bg-white';
    $hasDomain = $org && \App\Models\CustomDomain::query()->exists();
    $domainIncluded = $org && \App\Domains\DomainAccess::planIncludes($org);
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Home') }}</x-slot>
    <x-slot name="header">
        <div>
            <p class="text-sm text-muted">{{ now('Asia/Kolkata')->format('l j F') }}</p>
            <h2 class="mt-1">{{ __('Hi :name', ['name' => $firstName]) }}</h2>
        </div>
        @if ($events->isNotEmpty())
            <x-ri.button variant="secondary" size="md" :href="route('dashboard.participants')">{{ __('All participants') }}</x-ri.button>
        @endif
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-6 px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        @if ($trial)
            <div data-trial-banner class="flex flex-wrap items-center justify-between gap-4 rounded-[22px] bg-lilac px-5 py-4 text-sm text-track-deep">
                <p>
                    <span class="font-semibold">{{ $trial['expired'] ? __('Your free trial has ended') : trans_choice('{1} Free trial · 1 day left|[2,*] Free trial · :count days left|[0] Free trial · last day', $trial['days_left']) }}</span>
                    <span>· {{ __(':e/:el live event · :r/:rl test registrations', ['e' => $trial['events'], 'el' => $trial['event_limit'], 'r' => $trial['registrations'], 'rl' => $trial['registration_limit']]) }}</span>
                </p>
                <x-ri.button variant="primary" size="sm" :href="route('account.plan')">{{ __('Plans from :price', ['price' => \App\Saas\Plans::inr(\App\Saas\Plans::entry()['plan']['price_inr'] ?? null)]) }}</x-ri.button>
            </div>
        @endif

        @if ($org)
            <div class="space-y-3 empty:hidden">@include('saas.plan-banner', ['org' => $org])</div>
        @endif

        @if ($events->isEmpty())
            <section class="overflow-hidden rounded-[28px] bg-white">
                <div class="grid items-center gap-8 p-8 sm:p-12 lg:grid-cols-[1.1fr_0.9fr]">
                    <div>
                        <h3 class="ri-headline text-3xl sm:text-4xl">{{ __('Publish your race site in under an hour') }}</h3>
                        <p class="mt-4 max-w-lg text-muted">{{ __('Five quick steps: the basics, a sport pack, a page design and your branding. Your runners land on a sports page, not a form — with QR passes, check-in and certificates ready.') }}</p>
                        <x-ri.button variant="primary" class="mt-8" :href="route('organiser.events.create')">{{ __('Create your first event') }}</x-ri.button>
                    </div>
                    <ol class="space-y-2.5 text-sm">
                        @foreach ([__('Basics: name, date, venue'), __('Sport pack and prices'), __('Pick a page design'), __('Logo, photo and colours'), __('Review and publish')] as $i => $step)
                            <li class="flex items-center gap-3 rounded-2xl bg-paper px-4 py-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-lilac text-xs font-bold text-track-deep">{{ $i + 1 }}</span>
                                {{ $step }}
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @else
            {{-- Next race --}}
            @if ($next)
                @php
                    $nextLive = $next->status === \App\Landing\EventStatus::Published;
                    $nextUrl = $org ? route('landing.show', [$org->slug, $next->slug]) : null;
                    $days = max(0, (int) now('Asia/Kolkata')->startOfDay()->diffInDays($ist($next->date)->startOfDay(), false));
                    $pct = $nextPlaces['capacity'] ? min(100, (int) round(100 * $nextPlaces['paid'] / $nextPlaces['capacity'])) : null;
                    $hero = \App\Landing\Theme::url($next->config['hero']['image'] ?? null) ?? \App\View\SportImagery::pick($next->sport?->value);
                @endphp
                <section class="flex flex-wrap gap-6 rounded-[28px] bg-white p-3.5" data-next-race>
                    <x-ri.photo :src="$hero" :sport="$next->sport?->value ?? 'running'" class="min-h-[230px] w-full rounded-[20px] sm:max-w-[340px] sm:flex-[0_1_340px]">
                        @if ($nextLive)
                            <x-ri.chip tone="live" class="absolute left-3.5 top-3.5">{{ __('Live') }}</x-ri.chip>
                        @else
                            <x-ri.chip tone="ended" class="absolute left-3.5 top-3.5">{{ __(ucfirst($next->status)) }}</x-ri.chip>
                        @endif
                    </x-ri.photo>
                    <div class="flex min-w-0 flex-[1_1_360px] flex-col gap-3.5 py-2.5 pe-2.5 ps-1 sm:ps-0">
                        <div class="flex flex-wrap justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm text-muted">{{ __('Next race, :date', ['date' => $ist($next->date)->format('D j M Y')]) }}</p>
                                <h3 class="ri-headline mt-1 text-[30px] leading-[1.05]">{{ $next->name }}</h3>
                            </div>
                            <span class="self-start rounded-[18px] bg-lilac px-4 py-2.5 text-center text-track-deep">
                                <span class="ri-headline block text-[28px] leading-none">{{ $days }}</span>
                                <span class="text-xs font-semibold">{{ trans_choice('day to go|days to go', $days) }}</span>
                            </span>
                        </div>
                        @if ($pct !== null)
                            <div>
                                <div class="mb-2 flex justify-between text-sm">
                                    <span>{!! __(':paid of :cap places paid for', ['paid' => '<strong>'.number_format($nextPlaces['paid']).'</strong>', 'cap' => number_format($nextPlaces['capacity'])]) !!}</span>
                                    <span class="text-muted">{{ $pct }}%</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-lilac" aria-hidden="true"><div class="h-full rounded-full bg-track" style="width: {{ $pct }}%"></div></div>
                            </div>
                        @else
                            <p class="text-sm"><strong>{{ number_format($nextPlaces['paid']) }}</strong> {{ __('paid entries so far') }}</p>
                        @endif
                        <div class="mt-auto flex flex-wrap gap-2.5" x-data="{ copied: false }">
                            <x-ri.button variant="ink" size="sm" :href="route('organiser.events.show', $next)">{{ __('Open overview') }}</x-ri.button>
                            @if ($nextLive && $nextUrl)
                                <button type="button" class="inline-flex min-h-[44px] items-center rounded-full bg-paper px-4 text-sm font-semibold text-ink hover:bg-mist"
                                        @click="navigator.clipboard?.writeText(@js($nextUrl)); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-text="copied ? @js(__('Copied')) : @js(__('Copy public link'))">{{ __('Copy public link') }}</span>
                                </button>
                                <a href="{{ $nextUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center rounded-full bg-paper px-4 text-sm font-semibold text-ink hover:bg-mist">{{ __('View page') }}</a>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            {{-- Totals --}}
            <dl class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" aria-label="{{ __('Totals') }}">
                <div class="{{ $card }} flex min-w-0 flex-col gap-1.5 p-[18px] sm:p-[22px]">
                    <dt class="text-sm text-muted">{{ __('Registrations') }}</dt>
                    <dd class="ri-headline text-[26px] leading-tight sm:text-[34px]">{{ number_format($totals['registrations']) }}</dd>
                    <dd class="text-[13px] font-semibold text-success">{{ __(':n this week', ['n' => number_format($thisWeek)]) }}</dd>
                </div>
                <div class="{{ $card }} flex min-w-0 flex-col gap-1.5 p-[18px] sm:p-[22px]">
                    <dt class="text-sm text-muted">{{ __('Paid') }}</dt>
                    <dd class="ri-headline text-[26px] leading-tight sm:text-[34px]">{{ number_format($totals['paid']) }}</dd>
                    <dd class="text-[13px] font-semibold {{ $unpaid ? 'text-warning' : 'text-muted' }}">{{ $unpaid ? __(':n waiting to pay', ['n' => number_format($unpaid)]) : __('Nobody waiting to pay') }}</dd>
                </div>
                <div class="flex min-w-0 flex-col gap-1.5 rounded-[24px] bg-track p-[18px] text-white sm:p-[22px]">
                    <dt class="text-sm text-[#E4E1FF]">{{ __('Collected') }}</dt>
                    <dd class="ri-headline text-[26px] leading-tight sm:text-[34px]">{{ $inr($totals['paid_total_paise']) }}</dd>
                    <dd class="text-[13px] text-[#E4E1FF]">{{ $org?->payment_provider ? __('Paid into your :provider', ['provider' => ucfirst($org->payment_provider)]) : __('Paid into your gateway') }}</dd>
                </div>
                <div class="{{ $card }} flex min-w-0 flex-col gap-1.5 p-[18px] sm:p-[22px]">
                    <dt class="text-sm text-muted">{{ __('Checked in') }}</dt>
                    <dd class="ri-headline text-[26px] leading-tight sm:text-[34px]">{{ number_format($totals['checked_in']) }}</dd>
                    <dd class="text-[13px] text-muted">{{ $totals['checked_in'] ? __('Across all races') : __('Opens on race day') }}</dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-4">
                {{-- 30-day bars (CSS only) --}}
                <section class="min-w-0 flex-[2_1_480px] rounded-[28px] bg-white p-[26px]" data-daily-bars>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">{{ __('Registrations, last 30 days') }}</h3>
                        <span class="text-[13px] text-muted">{{ __('All events') }}</span>
                    </div>
                    <div role="img" aria-label="{{ __('Daily registrations for the last 30 days: :total in total', ['total' => number_format(collect($daily)->sum('count'))]) }}" class="mt-[22px] flex h-[200px] items-end gap-1 sm:gap-1.5">
                        @foreach ($daily as $i => $day)
                            <span class="flex-1 rounded-t-md rounded-b-sm {{ $i >= count($daily) - 7 ? 'bg-track' : 'bg-[#C9C5FA]' }}" style="height: {{ max(2, (int) round(100 * $day['count'] / $maxDay)) }}%" title="{{ $day['date']->format('j M') }}: {{ $day['count'] }}"></span>
                        @endforeach
                    </div>
                    <div class="mt-2.5 flex justify-between text-xs text-muted"><span>{{ $daily[0]['date']->format('j M') }}</span><span>{{ __('Today') }}</span></div>
                </section>

                {{-- Worth doing next --}}
                <section class="flex min-w-0 flex-[1_1_300px] flex-col gap-3 rounded-[28px] bg-white p-[26px]" data-worth-doing>
                    <h3 class="text-lg font-bold">{{ __('Worth doing next') }}</h3>
                    @if ($unpaid)
                        <a href="{{ route('dashboard.participants', ['status' => 'unpaid']) }}" class="flex flex-col gap-1 rounded-[18px] bg-[#FDF0DA] p-4 text-ink hover:brightness-[.98]">
                            <strong>{{ trans_choice('Remind :n runner to pay|Remind :n runners to pay', $unpaid, ['n' => number_format($unpaid)]) }}</strong>
                            <span class="text-[13px] text-[#6B4A12]">{{ __('Their places are held but not paid.') }}</span>
                        </a>
                    @endif
                    @if ($org && ! $hasDomain && $user->can('manage-org') && Route::has('settings.domain.show'))
                        <a href="{{ route('settings.domain.show') }}" class="flex flex-col gap-1 rounded-[18px] bg-paper p-4 text-ink hover:bg-mist">
                            <strong>{{ __('Connect your own domain') }}</strong>
                            <span class="text-[13px] text-muted">{{ $domainIncluded ? __('Included in :plan.', ['plan' => __(\App\Saas\Plans::all()[$org->plan]['name'] ?? '')]) : __('Add-on, :price per event.', ['price' => \App\Saas\Plans::inr(\App\Saas\Plans::customDomainPrice())]) }}</span>
                        </a>
                    @endif
                    @if ($next)
                        <a href="{{ route('reports.certificates', $next) }}" class="flex flex-col gap-1 rounded-[18px] bg-paper p-4 text-ink hover:bg-mist">
                            <strong>{{ __('Pick a certificate design') }}</strong>
                            <span class="text-[13px] text-muted">{{ __('Ready to send after race day.') }}</span>
                        </a>
                    @else
                        <a href="{{ route('organiser.events.create') }}" class="flex flex-col gap-1 rounded-[18px] bg-paper p-4 text-ink hover:bg-mist">
                            <strong>{{ __('Plan your next race') }}</strong>
                            <span class="text-[13px] text-muted">{{ __('Copy a past event or start fresh.') }}</span>
                        </a>
                    @endif
                </section>
            </div>

            {{-- Races --}}
            @foreach (array_filter([__('Upcoming') => $upcoming, __('Past races') => $past], fn ($rows) => $rows->isNotEmpty()) as $heading => $rows)
                <section class="rounded-[28px] bg-white px-[26px] pb-4 pt-2.5">
                    <div class="flex items-center justify-between py-4">
                        <h3 class="text-lg font-bold">{{ $heading }}</h3>
                        <a href="{{ route('organiser.events.index') }}" class="text-sm font-semibold text-track hover:text-track-deep">{{ __('All events') }}</a>
                    </div>
                    <div class="relative overflow-x-auto">
                        <table class="w-full min-w-[640px] border-collapse text-[15px]">
                            <thead>
                                <tr class="text-left text-[13px] text-muted">
                                    <th scope="col" class="py-2.5 font-semibold">{{ __('Event') }}</th>
                                    <th scope="col" class="font-semibold">{{ __('Date') }}</th>
                                    <th scope="col" class="font-semibold">{{ __('Entries') }}</th>
                                    <th scope="col" class="font-semibold">{{ __('Paid') }}</th>
                                    <th scope="col" class="text-right font-semibold">{{ __('Collected') }}</th>
                                    <th scope="col" class="w-0"><span class="sr-only">{{ __('Shortcuts') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $event)
                                    @php($draft = $event->status !== \App\Landing\EventStatus::Published)
                                    <tr class="border-t border-[#EEF0F6]">
                                        <td class="py-3.5 pe-4"><a href="{{ route('organiser.events.show', $event) }}" class="font-semibold text-ink hover:text-track">{{ $event->name }}</a></td>
                                        <td class="whitespace-nowrap pe-4">{{ $ist($event->date)->format('j M Y') }}</td>
                                        @if ($draft && ! $event->registrations_count)
                                            <td class="text-muted">{{ __(ucfirst($event->status)) }}</td><td class="text-muted">–</td><td class="text-right text-muted">–</td>
                                        @else
                                            <td>{{ number_format($event->registrations_count) }}</td>
                                            <td>{{ number_format($event->paid_count) }}</td>
                                            <td class="whitespace-nowrap text-right">{{ $inr((int) $event->paid_total_paise) }}</td>
                                        @endif
                                        <td class="whitespace-nowrap ps-4 text-right text-sm">
                                            <a href="{{ route('dashboard.participants', ['event' => $event->id]) }}" class="font-medium text-track hover:text-track-deep">{{ __('Participants') }}</a>
                                            <a href="{{ route('reports.race-sheet', $event) }}" class="ms-3 font-medium text-track hover:text-track-deep">{{ __('Race sheet') }}</a>
                                            <a href="{{ route('reports.certificates', $event) }}" class="ms-3 font-medium text-track hover:text-track-deep">{{ __('Certificates') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>
