{{-- One plan on /pricing. Self-serve plans start a trial; Custom is contact-only. $key, $plan, $trialDays, $contactEmail --}}
@php($featured = $plan['highlight'] ?? false)
<article id="{{ $key }}" data-reveal data-plan="{{ $key }}" @class([
    'relative flex h-full scroll-mt-24 flex-col p-6 sm:p-8',
    'bg-ink text-paper shadow-[8px_8px_0_0_rgb(var(--ri-heat))] md:shadow-[12px_12px_0_0_rgb(var(--ri-heat))]' => $featured,
    'bg-white text-ink ring-2 ring-ink' => ! $featured && $plan['self_serve'],
    'bg-mist text-ink/80 ring-1 ring-ink/20' => ! $plan['self_serve'],
])>
    @if ($featured)
        <x-ri.chip tone="heat" class="mb-3 self-start">{{ __('Start here') }}</x-ri.chip>
    @endif
    <h3 @class(['font-display font-extrabold uppercase leading-none tracking-wide', 'text-4xl' => $plan['self_serve'], 'text-3xl text-ink' => ! $plan['self_serve']])>{{ $plan['name'] }}</h3>
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
