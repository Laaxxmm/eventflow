{{-- Back from the gateway (Session P1). $purchase, $event --}}
@php
    $paid = $purchase->status === \App\Models\Purchase::PAID;
    $isPack = $purchase->sku === \App\Billing\Catalog::PACK;
@endphp
<x-app-layout>
    <x-slot name="title">{{ $paid ? __('Payment received') : __('Payment status') }}</x-slot>
    <x-slot name="header"><h2>{{ $paid ? __('Payment received') : __('Payment status') }}</h2></x-slot>

    <div class="mx-auto max-w-md px-4 py-10">
        <div class="rounded-[28px] bg-white p-6" data-billing-return="{{ $purchase->status }}">
            @if ($paid)
                <p class="text-lg font-bold">{{ __('Thank you — it is on.') }}</p>
                <p class="mt-2 text-sm text-muted">
                    {{ $isPack
                        ? __(':n more places for :event. New registrations can continue.', ['n' => number_format($purchase->quantity), 'event' => $event?->name])
                        : __(':addon is unlocked for :event.', ['addon' => __(\App\Saas\Plans::eventAddons()[$purchase->sku]['name'] ?? $purchase->sku), 'event' => $event?->name]) }}
                </p>
                <div class="mt-6">
                    @if ($purchase->sku === 'custom_domain')
                        <a href="{{ route('settings.domain.show', ['event' => $purchase->event_id]) }}" class="inline-flex rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-white">{{ __('Connect your domain') }} →</a>
                    @elseif ($event)
                        <a href="{{ route('organiser.events.show', $event) }}" class="inline-flex rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-white">{{ __('Back to the event') }} →</a>
                    @endif
                </div>
            @elseif ($purchase->status === \App\Models\Purchase::FAILED)
                <p class="text-lg font-bold">{{ __('The payment did not go through.') }}</p>
                <p class="mt-2 text-sm text-muted">{{ __('You have not been charged.') }}</p>
                <a href="{{ route('billing.show', ['sku' => $purchase->sku, 'event' => $purchase->event_id]) }}" class="mt-6 inline-flex rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-white">{{ __('Try again') }}</a>
            @else
                <p class="text-lg font-bold">{{ __('Waiting for the payment confirmation…') }}</p>
                <p class="mt-2 text-sm text-muted">{{ __('This usually takes a few seconds. Refresh this page, or come back later: it unlocks automatically once the provider confirms.') }}</p>
                <a href="{{ route('billing.return', $purchase) }}" class="mt-6 inline-flex rounded-full px-5 py-3 text-sm font-semibold ring-1 ring-mist">{{ __('Refresh') }}</a>
            @endif
        </div>
    </div>
</x-app-layout>
