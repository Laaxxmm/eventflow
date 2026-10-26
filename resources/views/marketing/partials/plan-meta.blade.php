{{-- Price block + caps for one plan. $plan, $onDark --}}
@php
    $pct = $plan['participant_fee_percent'] === null ? null : rtrim(rtrim(number_format($plan['participant_fee_percent'], 1), '0'), '.');
    $events = $plan['max_events'] === null ? __('Unlimited events') : trans_choice(':count event|:count events', $plan['max_events']);
    $regs = $plan['max_registrations'] === null ? __('unlimited entries') : __('up to :n entries', ['n' => number_format($plan['max_registrations'])]);
@endphp
@if ($plan['season_pass_inr'] !== null)
    <p class="font-display text-6xl font-extrabold leading-none tracking-tight">{{ \App\Saas\Plans::inr($plan['season_pass_inr']) }}</p>
    <p class="mt-2 text-sm {{ $onDark ? 'opacity-75' : 'text-ink/60' }}">{{ __('per season') }}@if ($pct) · {{ __('+ :pct% on paid entries', ['pct' => $pct]) }}@endif</p>
@else
    <p class="font-display text-6xl font-extrabold uppercase leading-none tracking-tight">{{ __('Custom') }}</p>
    <p class="mt-2 text-sm {{ $onDark ? 'opacity-75' : 'text-ink/60' }}">{{ __('Priced for your series') }}</p>
@endif
<p class="mt-4 text-xs font-semibold uppercase tracking-[0.16em]">{{ $events }} · {{ $regs }}</p>
