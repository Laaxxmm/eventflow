{{-- Price block + caps for one plan. $plan, $onDark --}}
@if ($plan['price_inr'] !== null)
    <p class="font-display text-6xl font-extrabold leading-none tracking-tight">{{ \App\Saas\Plans::inr($plan['price_inr']) }}</p>
    <p class="mt-2 text-sm {{ $onDark ? 'opacity-75' : 'text-ink/60' }}">{{ __($plan['term_label']) }} · {{ $plan['renews'] ? __('renews yearly') : __('one-off') }} · {{ __('no ticket %') }} · {{ __('+ GST') }}</p>
@else
    <p class="font-display text-3xl font-extrabold uppercase leading-none tracking-tight">{{ __('Quoted') }}</p>
    <p class="mt-2 text-sm {{ $onDark ? 'opacity-75' : 'text-ink/60' }}">{{ __('Quoted for your series') }}</p>
@endif
<p class="mt-4 text-xs font-semibold uppercase tracking-[0.16em]">{{ \App\Saas\Plans::limitsLabel($plan) }}</p>
