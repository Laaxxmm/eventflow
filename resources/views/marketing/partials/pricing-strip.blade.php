{{--
    First-class pricing strip: every plan from config/plans.php, cheapest first, custom last.
    $plans (ordered), $trialDays, $contactEmail
--}}
@php($cols = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][count($plans)] ?? 'lg:grid-cols-4')
<div class="grid border-t-2 border-ink sm:grid-cols-2 {{ $cols }}">
    @foreach ($plans as $key => $plan)
        @php($featured = $plan['highlight'] ?? false)
        <article data-reveal @class([
            'flex flex-col border-b-2 border-ink p-6 sm:p-8 lg:border-b-0',
            'lg:border-l-2' => ! $loop->first,
            'bg-track text-on-track' => $featured,
        ])>
            <div class="flex items-start justify-between gap-3">
                <h3 class="font-display text-3xl font-extrabold uppercase tracking-wide">{{ $plan['name'] }}</h3>
                @if ($featured)
                    <x-ri.chip tone="heat">{{ __('Most picked') }}</x-ri.chip>
                @endif
            </div>
            <p @class(['mt-1 min-h-[2.5rem] text-sm', 'text-ink/65' => ! $featured, 'opacity-80' => $featured])>{{ $plan['tagline'] }}</p>
            <div class="mt-6">
                @include('marketing.partials.plan-meta', ['onDark' => $featured])
            </div>
            <div class="mt-8 flex-1"></div>
            @if ($plan['self_serve'])
                <x-ri.button :variant="$featured ? 'heat' : 'ink'" size="md" class="w-full" :href="route('register', ['plan' => $key])">{{ __('Start with :plan', ['plan' => $plan['name']]) }}</x-ri.button>
                <p @class(['mt-2 text-center text-xs', 'text-ink/55' => ! $featured, 'opacity-70' => $featured])>{{ __(':days-day free trial · no card', ['days' => $trialDays]) }}</p>
            @else
                <x-ri.button variant="ghost" size="md" class="w-full {{ $featured ? '!ring-current !text-current' : '' }}" href="mailto:{{ $contactEmail }}?subject={{ rawurlencode(__('EventFlow :plan', ['plan' => $plan['name']])) }}">{{ __('Contact Indefine') }}</x-ri.button>
                <p class="mt-2 text-center text-xs text-ink/55">{{ $contactEmail }}</p>
            @endif
        </article>
    @endforeach
</div>
