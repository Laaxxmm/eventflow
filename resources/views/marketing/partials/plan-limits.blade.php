{{-- "1 race · 90-day window · 500 pax" for a plan. $plan. --}}
@if ($plan['max_events'] === null)
    {{ __('Unlimited races · custom term') }}
@else
    {{ trans_choice(':count race|:count races', $plan['max_events']) }} · {{ __($plan['term']) }} · {{ $plan['max_events'] === 1 ? __(':n pax', ['n' => number_format($plan['pax_per_event'])]) : __(':n pax/event', ['n' => number_format($plan['pax_per_event'])]) }}
@endif
