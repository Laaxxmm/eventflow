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
        <a href="{{ \App\Support\Contact::url(__('Upgrade :org to :plan', ['org' => $org->name, 'plan' => $nextPlan['name']])) }}" class="inline-flex min-h-[44px] items-center rounded-full bg-ink px-5 text-sm font-semibold text-white hover:bg-ink/90" data-upgrade>
            {{ __('Upgrade to :plan (:price)', ['plan' => $nextPlan['name'], 'price' => \App\Saas\Plans::priceLabel($nextPlan['price_inr'])]) }}
        </a>
    @endif
    <a href="{{ route('billing.show', array_filter(['sku' => \App\Billing\Catalog::PACK, 'event' => isset($event) ? $event->id : null])) }}" class="inline-flex min-h-[44px] items-center rounded-full bg-white px-5 text-sm font-semibold text-ink ring-1 ring-inset ring-[#C5CBDB] hover:ring-ink/40" data-buy-pack>
        {{ __('Buy a participant pack') }}
    </a>
    <a href="{{ \App\Support\Contact::url(__('EventFlow plan for :org', ['org' => $org->name])) }}" class="inline-flex min-h-[44px] items-center px-2 text-sm font-semibold text-track underline-offset-4 hover:underline" data-contact-indefine>
        {{ __('Contact Indefine') }}
    </a>
</div>
