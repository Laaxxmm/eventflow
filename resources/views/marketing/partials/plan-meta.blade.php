{{-- Price block + limits for one plan. $plan, $onDark --}}
@if ($plan['season_pass_inr'] !== null)
    <p class="font-display text-6xl font-extrabold leading-none tracking-tight">{{ \App\Saas\Plans::inr($plan['season_pass_inr']) }}</p>
    <p class="mt-2 text-sm {{ $onDark ? 'opacity-75' : 'text-ink/60' }}">{{ __($plan['term']) }} · {{ __('no ticket %') }}</p>
@else
    <p class="font-display text-3xl font-extrabold uppercase leading-none tracking-tight">{{ __('Quoted') }}</p>
    <p class="mt-2 text-sm {{ $onDark ? 'opacity-75' : 'text-ink/60' }}">{{ __('Quoted for your series') }}</p>
@endif
<p class="mt-4 text-xs font-semibold uppercase tracking-[0.16em]">@include('marketing.partials.plan-limits')</p>
