{{--
    Events (Daylight Track, docs/design/daylight-track/Events.dc.html): photo cards with status,
    template, registrations against places, and Overview / View page / Edit. Filter pills are
    client-side (Alpine) and every card renders without JavaScript.
--}}
@php
    $tz = \App\Landing\ConfigCompiler::TIMEZONE;
    $state = fn ($e) => match (true) {
        $e->date->isBefore(now()->subDay()) => 'past',
        $e->status === \App\Landing\EventStatus::Published => 'live',
        default => 'draft',
    };
    $counts = $events->groupBy($state)->map->count();
    $pass = \App\Saas\PlanLimits::pass($org);
    $plan = $org->plan ? (\App\Saas\Plans::all()[$org->plan] ?? null) : null;
    $allowed = \App\Saas\PlanLimits::racesAllowed($org);
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Events') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2>{{ __('Events') }}</h2>
            <p class="mt-1.5 text-[15px] text-muted">
                @if ($plan && $allowed !== null && $pass)
                    {{ __(':used of :allowed races used in your :plan, until :date.', ['used' => \App\Saas\PlanLimits::racesUsed($org), 'allowed' => $allowed, 'plan' => __($plan['name']), 'date' => $pass->ends_on->format('j M Y')]) }}
                @else
                    {{ __('Public page:') }} <a href="{{ route('landing.org', $org->slug) }}" class="font-medium text-track hover:text-track-deep">{{ route('landing.org', $org->slug) }}</a>
                @endif
            </p>
        </div>
        <x-ri.button variant="primary" size="md" :href="route('organiser.events.create')">{{ __('New event') }}</x-ri.button>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-6 px-4 pb-12 pt-4 sm:px-6 lg:px-10" x-data="{ show: 'all' }">
        @if (session('status') === 'event-deleted')
            <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm font-medium text-[#0B4A38]" role="status">{{ __('Event deleted.') }}</p>
        @endif

        @if ($events->isEmpty())
            <section class="rounded-[28px] bg-white p-8 text-center sm:p-12">
                <h3 class="ri-headline text-2xl">{{ __('No events yet') }}</h3>
                <p class="mt-2 text-muted">{{ __('Create your first event to get a shareable race page.') }}</p>
                <x-ri.button variant="primary" class="mt-6" :href="route('organiser.events.create')">{{ __('New event') }}</x-ri.button>
            </section>
        @else
            <div role="tablist" aria-label="{{ __('Filter events') }}" class="flex flex-wrap gap-2">
                @foreach (['all' => __('All'), 'live' => __('Live'), 'draft' => __('Drafts'), 'past' => __('Past')] as $key => $label)
                    @php
                        $n = $key === 'all' ? $events->count() : ($counts[$key] ?? 0);
                    @endphp
                    <button type="button" role="tab" @click="show = '{{ $key }}'" :aria-selected="(show === '{{ $key }}').toString()" aria-selected="{{ $key === 'all' ? 'true' : 'false' }}"
                            :class="show === '{{ $key }}' ? 'bg-ink text-white ring-ink' : 'bg-white text-ink ring-mist hover:ring-ink/30'"
                            class="{{ $key === 'all' ? 'bg-ink text-white ring-ink' : 'bg-white text-ink ring-mist' }} min-h-[44px] rounded-full px-4 text-sm font-semibold ring-1 ring-inset">{{ $label }} {{ $n }}</button>
                @endforeach
            </div>

            <div class="grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,340px),1fr))]">
                @foreach ($events as $event)
                    @php
                        $s = $state($event);
                        $thumb = \App\Landing\Theme::url($event->config['hero']['image'] ?? null) ?? \App\View\SportImagery::pick($event->sport?->value);
                        $template = config('eventflow.landing_templates.'.\App\Landing\EventConfig::resolveTemplate($event->config['template'] ?? $event->template_id).'.label');
                        $capacity = $event->capacity ?: (collect($event->config['distances'] ?? [])->every(fn ($d) => ! empty($d['capacity'])) ? (int) collect($event->config['distances'] ?? [])->sum('capacity') : null);
                        $fill = $capacity ? min(100, (int) round(100 * $event->registrations_count / $capacity)) : null;
                    @endphp
                    <article class="flex flex-col gap-3.5 rounded-[28px] bg-white p-3" x-show="show === 'all' || show === '{{ $s }}'" data-event-card="{{ $s }}">
                        <x-ri.photo :src="$thumb" :sport="$event->sport?->value ?? 'running'" class="h-[170px] rounded-[20px]" data-event-thumb>
                            <x-ri.chip :tone="['live' => 'live', 'draft' => 'neutral', 'past' => 'ended'][$s]" class="absolute left-3 top-3">{{ ['live' => __('Live'), 'draft' => __(ucfirst($event->status)), 'past' => __('Ended')][$s] }}</x-ri.chip>
                            @if ($template)
                                <span class="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-ink">{{ $template }}</span>
                            @endif
                        </x-ri.photo>
                        <div class="flex flex-col gap-1 px-2">
                            <h3 class="text-[19px] font-bold">{{ $event->name }}</h3>
                            <p class="text-sm text-muted">{{ $event->date->setTimezone($tz)->format('D j M Y') }}, {{ $s === 'past' ? __('finished') : \Illuminate\Support\Str::before($event->venue, ',') }}</p>
                        </div>
                        <div class="px-2">
                            <div class="mb-1.5 flex justify-between text-[13px]">
                                <span>{!! trans_choice(':n registration|:n registrations', $event->registrations_count, ['n' => '<strong>'.number_format($event->registrations_count).'</strong>']) !!}</span>
                                <span class="text-muted">{{ $s === 'draft' ? __('not published') : ($capacity ? __('of :n', ['n' => number_format($capacity)]) : __('no limit')) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-lilac" aria-hidden="true"><div class="h-full rounded-full bg-track" style="width: {{ $fill ?? 0 }}%"></div></div>
                        </div>
                        <div class="flex gap-2 px-2 pb-2 pt-1 text-sm font-semibold">
                            <a href="{{ route('organiser.events.show', $event) }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-full bg-ink text-white hover:bg-ink/90">{{ __('Overview') }}</a>
                            @if ($event->status === \App\Landing\EventStatus::Published)
                                <a href="{{ route('landing.show', [$org->slug, $event->slug]) }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-full bg-paper text-ink hover:bg-mist">{{ __('View page') }}</a>
                            @endif
                            <a href="{{ route('organiser.events.edit', $event) }}" class="flex min-h-[44px] flex-1 items-center justify-center rounded-full bg-paper text-ink hover:bg-mist">{{ __('Edit') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
