{{-- Back from the gateway (Session P1, Daylight Track). $purchase, $event --}}
@php
    $paid = $purchase->status === \App\Models\Purchase::PAID;
    $failed = $purchase->status === \App\Models\Purchase::FAILED;
    $isPack = $purchase->sku === \App\Billing\Catalog::PACK;
@endphp
<x-app-layout>
    <x-slot name="title">{{ $paid ? __('Payment received') : __('Payment status') }}</x-slot>
    <x-slot name="header"><h2>{{ $paid ? __('Payment received') : __('Payment status') }}</h2></x-slot>

    <div class="mx-auto max-w-[1180px] px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        <section class="flex max-w-md flex-col gap-3 rounded-[28px] bg-white p-[22px] sm:p-7" data-billing-return="{{ $purchase->status }}">
            <span @class([
                'flex h-12 w-12 items-center justify-center rounded-full text-xl font-bold',
                'bg-[#E3F4EE] text-[#0B4A38]' => $paid,
                'bg-[#FBE4E4] text-[#6E1414]' => $failed,
                'bg-lilac text-track-deep' => ! $paid && ! $failed,
            ]) aria-hidden="true">{{ $paid ? '✓' : ($failed ? '!' : '…') }}</span>
            @if ($paid)
                <h3 class="ri-headline text-[26px]">{{ __('Thank you — it is on.') }}</h3>
                <p class="text-[15px] text-muted">
                    {{ $isPack
                        ? __(':n more places for :event. New registrations can continue.', ['n' => number_format($purchase->quantity), 'event' => $event?->name])
                        : __(':addon is unlocked for :event.', ['addon' => __(\App\Saas\Plans::eventAddons()[$purchase->sku]['name'] ?? $purchase->sku), 'event' => $event?->name]) }}
                </p>
                <div class="mt-3">
                    @if ($purchase->sku === 'custom_domain')
                        <x-ri.button variant="primary" size="md" :href="route('settings.domain.show', ['event' => $purchase->event_id])">{{ __('Connect your domain') }}</x-ri.button>
                    @elseif ($event)
                        <x-ri.button variant="primary" size="md" :href="route('organiser.events.show', $event)">{{ __('Back to the event') }}</x-ri.button>
                    @endif
                </div>
            @elseif ($failed)
                <h3 class="ri-headline text-[26px]">{{ __('The payment did not go through.') }}</h3>
                <p class="text-[15px] text-muted">{{ __('You have not been charged.') }}</p>
                <div class="mt-3"><x-ri.button variant="primary" size="md" :href="route('billing.show', ['sku' => $purchase->sku, 'event' => $purchase->event_id])">{{ __('Try again') }}</x-ri.button></div>
            @else
                <h3 class="ri-headline text-[26px]">{{ __('Waiting for the payment confirmation…') }}</h3>
                <p class="text-[15px] text-muted">{{ __('This usually takes a few seconds. Refresh this page, or come back later: it unlocks automatically once the provider confirms.') }}</p>
                <div class="mt-3"><x-ri.button variant="secondary" size="md" :href="route('billing.return', $purchase)">{{ __('Refresh') }}</x-ri.button></div>
            @endif
        </section>
    </div>
</x-app-layout>
