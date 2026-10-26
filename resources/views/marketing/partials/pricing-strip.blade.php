{{--
    Pricing strip, Single first: self-serve plans from config/plans.php cheapest first, then
    Custom de-emphasised as a slim column. $plans (ordered), $trialDays, $contactEmail
--}}
@php
    $selfServe = array_filter($plans, fn ($p) => $p['self_serve']);
    $custom = array_filter($plans, fn ($p) => ! $p['self_serve']);
@endphp
<div class="grid border-y-2 border-ink lg:grid-cols-[repeat(3,minmax(0,1fr))_minmax(0,0.75fr)]">
    @foreach ($selfServe as $key => $plan)
        @php($featured = $plan['highlight'] ?? false)
        <article data-reveal data-plan="{{ $key }}" @class([
            'flex flex-col border-b-2 border-ink p-6 sm:p-8 lg:border-b-0',
            'lg:border-l-2' => ! $loop->first,
            'bg-ink text-paper' => $featured,
        ])>
            <div class="flex items-start justify-between gap-3">
                <h3 class="font-display text-3xl font-extrabold uppercase tracking-wide">{{ $plan['name'] }}</h3>
                @if ($featured)
                    <x-ri.chip tone="heat">{{ __('Start here') }}</x-ri.chip>
                @endif
            </div>
            <p @class(['mt-1 text-sm', 'text-ink/65' => ! $featured, 'opacity-80' => $featured])>{{ $plan['tagline'] }}</p>
            <div class="mt-6 flex-1">
                @include('marketing.partials.plan-meta', ['onDark' => $featured])
            </div>
            <x-ri.button :variant="$featured ? 'heat' : 'ink'" size="md" class="mt-8 w-full" :href="route('register', ['plan' => $key])">{{ __('Start free trial') }}</x-ri.button>
            <p @class(['mt-2 text-center text-xs', 'text-ink/55' => ! $featured, 'opacity-70' => $featured])>{{ __(':days days free · no card', ['days' => $trialDays]) }}</p>
        </article>
    @endforeach
    @foreach ($custom as $key => $plan)
        <article data-plan="{{ $key }}" class="flex flex-col justify-between gap-6 bg-mist p-6 text-ink/75 sm:p-8 lg:border-l-2 lg:border-ink">
            <div>
                <h3 class="font-display text-2xl font-bold uppercase tracking-wide text-ink">{{ $plan['name'] }}</h3>
                <p class="mt-2 text-sm">{{ $plan['tagline'] }}</p>
            </div>
            <a href="{{ \App\Support\Contact::url(__('EventFlow :plan', ['plan' => $plan['name']])) }}" data-contact-indefine class="text-sm font-semibold text-ink underline decoration-heat decoration-2 underline-offset-4">{{ __('Contact Indefine') }} →</a>
        </article>
    @endforeach
</div>
