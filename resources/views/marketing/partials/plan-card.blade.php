{{-- One plan on /pricing. Self-serve plans start a trial; Custom is contact-only. $key, $plan, $trialDays, $contactEmail --}}
@php($featured = $plan['highlight'] ?? false)
<article id="{{ $key }}" data-reveal data-plan="{{ $key }}" @class([
    'relative flex scroll-mt-24 flex-col p-6 sm:p-8',
    'bg-ink text-paper shadow-[12px_12px_0_0_rgb(var(--ri-heat))]' => $featured,
    'bg-white text-ink ring-2 ring-ink' => ! $featured && $plan['self_serve'],
    'bg-mist text-ink/80 ring-1 ring-ink/20' => ! $plan['self_serve'],
])>
    <div class="flex items-start justify-between gap-3">
        <h3 @class(['font-display font-extrabold uppercase tracking-wide', 'text-4xl' => $plan['self_serve'], 'text-3xl text-ink' => ! $plan['self_serve']])>{{ $plan['name'] }}</h3>
        @if ($featured)
            <x-ri.chip tone="heat">{{ __('Start here') }}</x-ri.chip>
        @endif
    </div>
    <p @class(['mt-1 text-sm', 'text-ink/65' => ! $featured, 'opacity-80' => $featured])>{{ $plan['tagline'] }}</p>

    <div class="mt-6">@include('marketing.partials.plan-meta', ['onDark' => $featured])</div>

    <ul class="mt-6 flex-1 space-y-3 text-sm">
        @foreach ($plan['features'] as $feature)
            <li class="flex gap-3">@include('marketing.partials.tick')<span>{{ __($feature) }}</span></li>
        @endforeach
    </ul>

    @if ($plan['self_serve'])
        <x-ri.button :variant="$featured ? 'heat' : 'ink'" size="md" class="mt-8 w-full" :href="route('register', ['plan' => $key])">{{ __('Start free trial') }}</x-ri.button>
        <p @class(['mt-2 text-center text-xs', 'text-ink/55' => ! $featured, 'opacity-70' => $featured])>{{ __(':days days free · no card', ['days' => $trialDays]) }}</p>
    @else
        <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode(__('EventFlow :plan plan', ['plan' => $plan['name']])) }}" class="mt-8 text-sm font-semibold text-ink underline decoration-heat decoration-2 underline-offset-4">{{ __('Contact Indefine') }} →</a>
    @endif
</article>
