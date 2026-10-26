{{-- Ways out when a plan cap is hit. $org, optional $event. Billing is not online yet, so each path opens an email to Indefine. --}}
@php
    $contactEmail = config('plans.brand.contact_email');
    $next = \App\Saas\PlanLimits::upgradeTarget($org);
    $nextPlan = $next ? \App\Saas\Plans::all()[$next] ?? null : null;
    $mail = fn (string $subject) => 'mailto:'.$contactEmail.'?subject='.rawurlencode($subject);
    $race = isset($event) ? $event->name : null;
@endphp
<div class="flex flex-wrap gap-3">
    @if ($nextPlan && $nextPlan['self_serve'])
        <a href="{{ $mail(__('Upgrade :org to :plan', ['org' => $org->name, 'plan' => $nextPlan['name']])) }}" class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
            {{ __('Upgrade to :plan (:price)', ['plan' => $nextPlan['name'], 'price' => \App\Saas\Plans::priceLabel($nextPlan['price_inr'])]) }}
        </a>
    @endif
    <a href="{{ $mail($race ? __('Participant pack for :race (:org)', ['race' => $race, 'org' => $org->name]) : __('Participant pack for :org', ['org' => $org->name])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-50">
        {{ __('Buy a participant pack') }}
    </a>
    <a href="{{ $mail(__('EventFlow plan for :org', ['org' => $org->name])) }}" class="inline-flex items-center px-2 py-2 text-sm font-semibold text-gray-700 underline hover:text-gray-900">
        {{ __('Contact Indefine') }}
    </a>
</div>
