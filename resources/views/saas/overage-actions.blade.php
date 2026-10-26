{{--
    Ways out when a plan cap is hit. $org, optional $event.
    Buy a participant pack opens the Buy page (online checkout, or a request to Indefine while
    packs have no online price). Upgrade and Contact Indefine open a real contact link. Never "#".
--}}
@php
    $next = \App\Saas\PlanLimits::upgradeTarget($org);
    $nextPlan = $next ? \App\Saas\Plans::all()[$next] ?? null : null;
@endphp
<div class="flex flex-wrap gap-3" data-overage-actions>
    @if ($nextPlan && $nextPlan['self_serve'])
        <a href="{{ \App\Support\Contact::url(__('Upgrade :org to :plan', ['org' => $org->name, 'plan' => $nextPlan['name']])) }}" class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700" data-upgrade>
            {{ __('Upgrade to :plan (:price)', ['plan' => $nextPlan['name'], 'price' => \App\Saas\Plans::priceLabel($nextPlan['price_inr'])]) }}
        </a>
    @endif
    <a href="{{ route('billing.show', array_filter(['sku' => \App\Billing\Catalog::PACK, 'event' => isset($event) ? $event->id : null])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-50" data-buy-pack>
        {{ __('Buy a participant pack') }}
    </a>
    <a href="{{ \App\Support\Contact::url(__('EventFlow plan for :org', ['org' => $org->name])) }}" class="inline-flex items-center px-2 py-2 text-sm font-semibold text-gray-700 underline hover:text-gray-900" data-contact-indefine>
        {{ __('Contact Indefine') }}
    </a>
</div>
