{{--
    Buy page (Session P1): a participant pack or a per-event add-on for one event. Pays online on
    the platform gateway when $online, else sends a request to Indefine (stored as a purchase).
    $org, $event, $events, $sku, $item, $sizes, $owned, $online, $history, $includedIn
--}}
@php
    use App\Saas\Plans;
    $flash = session('billing');
    $isPack = $sku === \App\Billing\Catalog::PACK;
    $price = $item['price_inr'] !== null ? Plans::inr($item['price_inr']) : null;
    $statusLabel = ['requested' => __('Request sent'), 'pending' => __('Checkout started'), 'paid' => __('Paid'), 'failed' => __('Not completed')];
@endphp

<x-app-layout>
    <x-slot name="title">{{ $item['name'] }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-sm text-muted">{{ __('Plan & billing') }}</p>
            <h2 class="truncate">{{ $isPack ? __('Buy a participant pack') : __('Add :addon', ['addon' => $item['name']]) }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8" data-billing-page="{{ $sku }}">
        @if ($flash === 'requested')
            <div class="rounded-2xl bg-[#E3F4EE] p-5 text-sm text-[#0B4A38] ring-1 ring-[#BFE5D6]" role="status" data-billing-requested>
                <p class="font-semibold">{{ __('Request sent to Indefine.') }}</p>
                <p class="mt-1">{{ $isPack
                    ? __('We will confirm the price and add the places to :event, usually within one working day. Nothing is charged until you agree.', ['event' => $event->name])
                    : __('We will switch :addon on for :event and send the invoice, usually within one working day.', ['addon' => $item['name'], 'event' => $event->name]) }}</p>
                <p class="mt-2">{{ __('In a hurry?') }} <a href="{{ \App\Support\Contact::url(__(':item for :event (:org)', ['item' => $item['name'], 'event' => $event->name, 'org' => $org->name])) }}" class="font-semibold underline" data-contact-indefine>{{ __('Contact Indefine') }}</a></p>
            </div>
        @elseif ($flash === 'gateway-error')
            <div class="rounded-2xl bg-[#FBE4E4] p-5 text-sm text-[#6E1414] ring-1 ring-[#F2C4C4]" role="alert">
                <p class="font-semibold">{{ __('Checkout could not start. You have not been charged.') }}</p>
                <p class="mt-1">{{ __('Try again in a minute, or send a request and we will invoice you.') }}</p>
            </div>
        @elseif ($flash === 'owned')
            <div class="rounded-2xl bg-paper p-5 text-sm ring-1 ring-mist" role="status">{{ __('This event already has it. Nothing was charged.') }}</div>
        @endif

        <section class="rounded-[28px] bg-white p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $event->name }}</p>
                    <h3 class="mt-1 text-2xl font-bold tracking-tight">{{ $item['name'] }}</h3>
                    <p class="mt-2 max-w-xl text-sm text-muted">{{ $item['description'] }}</p>
                </div>
                <p class="text-right">
                    @if ($price)
                        <span class="block text-3xl font-extrabold tracking-tight" data-billing-price>{{ $price }}</span>
                        <span class="text-xs text-muted">{{ $isPack ? __('one race, + GST') : __('this event, + GST') }}</span>
                    @else
                        <span class="block text-lg font-bold">{{ __('Quoted by Indefine') }}</span>
                    @endif
                </p>
            </div>

            @if ($owned)
                <div class="mt-6 rounded-2xl bg-[#E3F4EE] p-4 text-sm text-[#0B4A38] ring-1 ring-[#BFE5D6]" data-billing-owned>
                    {{ \App\Saas\EventAddons::keyInPlan($event, $sku) ? __('Included in your plan for every event.') : __('Already added to this event.') }}
                    @if ($sku === 'custom_domain')
                        <a href="{{ route('settings.domain.show', ['event' => $event->id]) }}" class="ms-1 font-semibold underline">{{ __('Connect your domain') }} →</a>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('billing.store', $sku) }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="event" class="block text-sm font-semibold text-ink">{{ __('Event') }}</label>
                        <select id="event" name="event" class="mt-1 block w-full rounded-xl border-mist text-sm shadow-sm focus:border-ink focus:ring-ink" onchange="window.location = @js(route('billing.show', $sku)) + '?event=' + this.value">
                            @foreach ($events as $option)
                                <option value="{{ $option->id }}" @selected($option->is($event))>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($isPack)
                        <fieldset>
                            <legend class="text-sm font-semibold text-ink">{{ __('Extra places') }}</legend>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                @foreach ($sizes as $size)
                                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl p-4 ring-1 ring-mist has-[:checked]:bg-ink has-[:checked]:text-white has-[:checked]:ring-ink">
                                        <input type="radio" name="participants" value="{{ $size['participants'] }}" class="sr-only" @checked($item['quantity'] === $size['participants'])>
                                        <span class="font-bold">+{{ number_format($size['participants']) }}</span>
                                        <span class="text-sm opacity-75">{{ $size['price_inr'] !== null ? Plans::inr($size['price_inr']) : __('Quote') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    @unless ($online)
                        <div>
                            <label for="note" class="block text-sm font-semibold text-ink">{{ __('Anything we should know? (optional)') }}</label>
                            <textarea id="note" name="note" rows="2" maxlength="500" class="mt-1 block w-full rounded-xl border-mist text-sm shadow-sm focus:border-ink focus:ring-ink" placeholder="{{ $isPack ? __('e.g. we expect about 300 more runners after the weekend') : '' }}"></textarea>
                        </div>
                    @endunless

                    <div class="flex flex-col gap-3 border-t border-[#EEF0F6] pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-muted">
                            @if ($online)
                                {{ __('Secure checkout with :provider. Unlocks as soon as the payment is confirmed.', ['provider' => ucfirst(\App\Billing\Checkout::provider())]) }}
                            @else
                                {{ __('Online checkout is not on for this yet: we send your request to Indefine. Nothing is charged automatically.') }}
                            @endif
                        </p>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-white hover:bg-ink/90 sm:w-auto" data-billing-submit="{{ $online ? 'pay' : 'request' }}">
                            {{ $online ? __('Pay :price', ['price' => $price]) : __('Send request to Indefine') }} →
                        </button>
                    </div>
                </form>
            @endif

            @if ($includedIn && ! $owned)
                <p class="mt-5 text-sm text-muted">{{ __('Or move to :plans, which include it for every event.', ['plans' => implode(' / ', $includedIn)]) }} <a href="{{ route('pricing') }}" class="font-semibold underline">{{ __('Compare plans') }}</a></p>
            @endif
        </section>

        @if ($history->isNotEmpty())
            <section class="rounded-[28px] bg-white p-6">
                <h3 class="text-sm font-semibold">{{ __('Recent requests and payments') }}</h3>
                <ul class="mt-3 divide-y divide-[#EEF0F6] text-sm">
                    @foreach ($history as $purchase)
                        <li class="flex justify-between gap-4 py-2.5" data-purchase="{{ $purchase->id }}">
                            <span>#{{ $purchase->id }} · {{ $isPack ? '+'.number_format($purchase->quantity) : $item['name'] }} · {{ $purchase->created_at->setTimezone('Asia/Kolkata')->format('j M, g:i A') }}</span>
                            <span class="font-medium">{{ $statusLabel[$purchase->status] ?? $purchase->status }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="text-sm text-muted"><a href="{{ route('account.plan') }}" class="underline hover:text-ink">← {{ __('Plan & billing') }}</a></p>
    </div>
</x-app-layout>
