{{-- One plan. $key, $plan, $addons, $trialDays, $contactEmail. Self-serve plans post add-ons to signup. --}}
@php($featured = $plan['highlight'] ?? false)
<article @class([
    'relative flex flex-col rounded-3xl p-6 sm:p-8',
    'bg-[#0b1020] text-white shadow-2xl lg:-my-4' => $featured,
    'bg-white text-[#0b1020] ring-1 ring-black/5 shadow-sm' => ! $featured,
])>
    @if ($featured)
        <p class="absolute -top-3 left-6 rounded-full bg-[#d9ff45] px-3 py-1 text-xs font-bold uppercase tracking-wider text-[#0b1020]">{{ __('Most popular') }}</p>
    @endif

    <h3 class="text-xl font-extrabold">{{ $plan['name'] }}</h3>
    <p @class(['mt-1 text-sm', 'text-white/70' => $featured, 'text-gray-600' => ! $featured])>{{ $plan['tagline'] }}</p>

    <div class="mt-6">
        @if ($plan['season_pass_inr'] !== null)
            <p class="flex items-baseline gap-2">
                <span class="text-4xl font-black tracking-tight">{{ \App\Saas\Plans::inr($plan['season_pass_inr']) }}</span>
                <span @class(['text-sm', 'text-white/70' => $featured, 'text-gray-600' => ! $featured])>{{ __('/ season') }}</span>
            </p>
            <p @class(['mt-1 text-sm font-semibold', 'text-[#d9ff45]' => $featured, 'text-[#c2410c]' => ! $featured])>
                {{ __('+ :pct% on paid registrations', ['pct' => rtrim(rtrim(number_format($plan['participant_fee_percent'], 1), '0'), '.')]) }}
            </p>
        @else
            <p class="text-4xl font-black tracking-tight">{{ __('Custom') }}</p>
            <p @class(['mt-1 text-sm font-semibold', 'text-[#d9ff45]' => $featured, 'text-[#c2410c]' => ! $featured])>{{ __('Season Pass and fee to suit your series') }}</p>
        @endif
    </div>

    <ul class="mt-6 space-y-3 text-sm flex-1">
        @foreach ($plan['features'] as $feature)
            <li class="flex gap-3">
                <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $featured ? 'text-[#d9ff45]' : 'text-[#16a34a]' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                <span>{{ __($feature) }}</span>
            </li>
        @endforeach
    </ul>

    @if ($plan['self_serve'])
        <form method="get" action="{{ route('register') }}" class="mt-8">
            <input type="hidden" name="plan" value="{{ $key }}">
            <fieldset @class(['rounded-2xl p-4 text-sm', 'bg-white/5' => $featured, 'bg-[#f7f6f1]' => ! $featured])>
                <legend class="sr-only">{{ __('Add-ons') }}</legend>
                <p class="font-semibold">{{ __('Add-ons') }}</p>
                @foreach ($addons as $addonKey => $addon)
                    <label class="mt-2 flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" name="addons[]" value="{{ $addonKey }}" class="mt-0.5 rounded border-gray-300 text-[#0b1020] focus:ring-[#d9ff45]">
                        <span class="flex-1">{{ __($addon['name']) }}</span>
                        <span @class(['whitespace-nowrap', 'text-white/70' => $featured, 'text-gray-600' => ! $featured])>+{{ \App\Saas\Plans::inr($addon['season_inr']) }}</span>
                    </label>
                @endforeach
            </fieldset>
            <button type="submit" @class([
                'mt-4 w-full rounded-full px-6 py-3 font-bold focus:outline-none focus:ring-2 focus:ring-offset-2',
                'bg-[#d9ff45] text-[#0b1020] hover:bg-white focus:ring-[#d9ff45] focus:ring-offset-[#0b1020]' => $featured,
                'bg-[#0b1020] text-white hover:bg-[#1f2a4a] focus:ring-[#0b1020]' => ! $featured,
            ])>{{ __('Start :days-day free trial', ['days' => $trialDays]) }}</button>
            <p @class(['mt-2 text-center text-xs', 'text-white/60' => $featured, 'text-gray-500' => ! $featured])>{{ __('No card needed.') }}</p>
        </form>
    @else
        <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode(__('EventFlow :plan plan', ['plan' => $plan['name']])) }}" @class([
            'mt-8 block w-full rounded-full px-6 py-3 text-center font-bold focus:outline-none focus:ring-2 focus:ring-offset-2',
            'bg-[#d9ff45] text-[#0b1020] hover:bg-white' => $featured,
            'border-2 border-[#0b1020] hover:bg-[#0b1020] hover:text-white focus:ring-[#0b1020]' => ! $featured,
        ])>{{ __('Talk to Indefine') }}</a>
        <p class="mt-2 text-center text-xs text-gray-500">{{ $contactEmail }}</p>
    @endif
</article>
