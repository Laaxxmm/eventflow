{{-- One plan on /pricing, with add-ons that carry into signup. $key, $plan, $addons, $trialDays, $contactEmail. --}}
@php($featured = $plan['highlight'] ?? false)
<article data-reveal @class([
    'relative flex flex-col p-6 sm:p-8',
    'bg-track text-on-track shadow-[12px_12px_0_0_rgb(var(--ri-ink))]' => $featured,
    'bg-white text-ink ring-2 ring-ink' => ! $featured,
])>
    <div class="flex items-start justify-between gap-3">
        <h3 class="font-display text-4xl font-extrabold uppercase tracking-wide">{{ $plan['name'] }}</h3>
        @if ($featured)
            <x-ri.chip tone="heat">{{ __('Most picked') }}</x-ri.chip>
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
        <form method="get" action="{{ route('register') }}" class="mt-8">
            <input type="hidden" name="plan" value="{{ $key }}">
            <fieldset @class(['p-4 text-sm', 'bg-paper' => ! $featured, 'bg-black/15' => $featured])>
                <legend class="sr-only">{{ __('Add-ons') }}</legend>
                <p class="text-xs font-semibold uppercase tracking-[0.16em]">{{ __('Add-ons') }}</p>
                <p class="mt-1 text-xs opacity-70">{{ __('Free during the trial; Indefine quotes them when you activate.') }}</p>
                @foreach ($addons as $addonKey => $addon)
                    <label class="mt-2 flex cursor-pointer items-start gap-2">
                        <input type="checkbox" name="addons[]" value="{{ $addonKey }}" class="mt-0.5 rounded-none border-ink/40 text-heat focus:ring-heat">
                        <span class="flex-1">{{ __($addon['name']) }}</span>
                        <span class="whitespace-nowrap opacity-70">{{ \App\Saas\Plans::inr($addon['price_inr']) ? '+'.\App\Saas\Plans::inr($addon['price_inr']) : __('Paid add-on') }}</span>
                    </label>
                @endforeach
            </fieldset>
            <x-ri.button type="submit" :variant="$featured ? 'heat' : 'ink'" size="md" class="mt-4 w-full">{{ __('Start :days-day free trial', ['days' => $trialDays]) }}</x-ri.button>
            <p @class(['mt-2 text-center text-xs', 'text-ink/55' => ! $featured, 'opacity-70' => $featured])>{{ __('No card needed.') }}</p>
        </form>
    @else
        <x-ri.button variant="ghost" size="md" class="mt-8 w-full" href="mailto:{{ $contactEmail }}?subject={{ rawurlencode(__('EventFlow :plan plan', ['plan' => $plan['name']])) }}">{{ __('Contact Indefine') }}</x-ri.button>
        <p class="mt-2 text-center text-xs text-ink/55">{{ $contactEmail }}</p>
    @endif
</article>
