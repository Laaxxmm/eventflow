{{--
    Buy page (Session P1, Daylight Track styling from Billing.dc.html): a participant pack or a per-event
    add-on for one event. Pays online on the platform gateway when $online, else sends a request to
    Indefine (stored as a purchase).
    $org, $event, $events, $sku, $item, $sizes, $owned, $online, $history, $includedIn
--}}
@php
    use App\Saas\Plans;
    $flash = session('billing');
    $isPack = $sku === \App\Billing\Catalog::PACK;
    $price = $item['price_inr'] !== null ? Plans::inr($item['price_inr']) : null;
    $statusLabel = ['requested' => __('Request sent'), 'pending' => __('Checkout started'), 'paid' => __('Paid'), 'failed' => __('Not completed')];
    $statusTone = ['requested' => 'trial', 'pending' => 'unpaid', 'paid' => 'paid', 'failed' => 'suspended'];
    $field = 'mt-1.5 block w-full rounded-[14px] border-[#C5CBDB] bg-white text-[15px] font-normal text-ink focus:border-track focus:ring-track';
@endphp

<x-app-layout>
    <x-slot name="title">{{ $item['name'] }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0">
            <a href="{{ route('account.plan') }}" class="text-sm font-semibold text-track underline-offset-4 hover:underline">{{ __('Plan & billing') }}</a>
            <h2 class="mt-1">{{ $isPack ? __('Buy a participant pack') : __('Add :addon', ['addon' => $item['name']]) }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10" data-billing-page="{{ $sku }}">
        @if ($flash === 'requested')
            <div class="rounded-[18px] bg-[#E3F4EE] px-4 py-4 text-sm text-[#0B4A38] sm:px-5" role="status" data-billing-requested>
                <p class="font-semibold">{{ __('Request sent to Indefine.') }}</p>
                <p class="mt-1">{{ $isPack
                    ? __('We will confirm the price and add the places to :event, usually within one working day. Nothing is charged until you agree.', ['event' => $event->name])
                    : __('We will switch :addon on for :event and send the invoice, usually within one working day.', ['addon' => $item['name'], 'event' => $event->name]) }}</p>
                <p class="mt-2">{{ __('In a hurry?') }} <a href="{{ \App\Support\Contact::url(__(':item for :event (:org)', ['item' => $item['name'], 'event' => $event->name, 'org' => $org->name])) }}" class="font-semibold underline" data-contact-indefine>{{ __('Contact Indefine') }}</a></p>
            </div>
        @elseif ($flash === 'gateway-error')
            <div class="rounded-[18px] bg-[#FBE4E4] px-4 py-4 text-sm text-[#6E1414] sm:px-5" role="alert">
                <p class="font-semibold">{{ __('Checkout could not start. You have not been charged.') }}</p>
                <p class="mt-1">{{ __('Try again in a minute, or send a request and we will invoice you.') }}</p>
            </div>
        @elseif ($flash === 'owned')
            <div class="rounded-[18px] bg-lilac px-4 py-3 text-sm text-track-deep" role="status">{{ __('This event already has it. Nothing was charged.') }}</div>
        @endif

        <div class="flex flex-wrap items-start gap-5">
            <section class="flex min-w-0 flex-[1_1_460px] flex-col gap-4 rounded-[28px] bg-white p-[22px] sm:p-[26px]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm text-muted">{{ $event->name }}</p>
                        <h3 class="ri-headline mt-1 text-[26px]">{{ $item['name'] }}</h3>
                        <p class="mt-1.5 max-w-xl text-[15px] leading-relaxed text-muted">{{ $item['description'] }}</p>
                    </div>
                    <p class="sm:text-right">
                        @if ($price)
                            <span class="ri-headline block text-[30px]" data-billing-price>{{ $price }}</span>
                            <span class="text-[13px] text-muted">{{ $isPack ? __('one race, + GST') : __('this event, + GST') }}</span>
                        @else
                            <span class="block text-[15px] font-semibold text-muted">{{ __('Quoted by Indefine') }}</span>
                        @endif
                    </p>
                </div>

                @if ($owned)
                    <div class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" data-billing-owned>
                        {{ \App\Saas\EventAddons::keyInPlan($event, $sku) ? __('Included in your plan for every event.') : __('Already added to this event.') }}
                        @if ($sku === 'custom_domain')
                            <a href="{{ route('settings.domain.show', ['event' => $event->id]) }}" class="ms-1 font-semibold underline">{{ __('Connect your domain') }}</a>
                        @endif
                    </div>
                @else
                    <form method="POST" action="{{ route('billing.store', $sku) }}" class="flex flex-col gap-4">
                        @csrf
                        <label for="event" class="ri-label">{{ __('Event') }}
                            <select id="event" name="event" class="{{ $field }}" onchange="window.location = @js(route('billing.show', $sku)) + '?event=' + this.value">
                                @foreach ($events as $option)
                                    <option value="{{ $option->id }}" @selected($option->is($event))>{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        @if ($isPack)
                            <fieldset>
                                <legend class="ri-label">{{ __('Extra places') }}</legend>
                                <div class="mt-1.5 grid gap-2 sm:grid-cols-3">
                                    @foreach ($sizes as $size)
                                        <label class="flex min-h-[56px] cursor-pointer items-center justify-between gap-3 rounded-[18px] px-4 py-3 ring-1 ring-inset ring-[#C5CBDB] transition duration-200 ease-track hover:ring-track has-[:checked]:bg-lilac has-[:checked]:text-track-deep has-[:checked]:ring-2 has-[:checked]:ring-track has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-heat">
                                            <input type="radio" name="participants" value="{{ $size['participants'] }}" class="sr-only" @checked($item['quantity'] === $size['participants'])>
                                            <span class="font-bold">+{{ number_format($size['participants']) }}</span>
                                            <span class="text-sm">{{ $size['price_inr'] !== null ? Plans::inr($size['price_inr']) : __('Quote') }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        @unless ($online)
                            <label for="note" class="ri-label">{{ __('Anything we should know? (optional)') }}
                                <textarea id="note" name="note" rows="3" maxlength="500" class="{{ $field }}" placeholder="{{ $isPack ? __('e.g. we expect about 300 more runners after the weekend') : '' }}"></textarea>
                            </label>
                        @endunless

                        <div class="flex flex-col gap-3 border-t border-[#EEF0F6] pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-muted">
                                @if ($online)
                                    {{ __('Secure checkout with :provider. Unlocks as soon as the payment is confirmed.', ['provider' => ucfirst(\App\Billing\Checkout::provider())]) }}
                                @else
                                    {{ __('Online checkout is not on for this yet: we send your request to Indefine. Nothing is charged automatically.') }}
                                @endif
                            </p>
                            <x-ri.button type="submit" variant="primary" size="md" class="w-full shrink-0 sm:w-auto" data-billing-submit="{{ $online ? 'pay' : 'request' }}">
                                {{ $online ? __('Pay :price', ['price' => $price]) : __('Send request to Indefine') }}
                            </x-ri.button>
                        </div>
                    </form>
                @endif

                @if ($includedIn && ! $owned)
                    <p class="text-sm text-muted">{{ __('Or move to :plans, which include it for every event.', ['plans' => implode(' / ', $includedIn)]) }} <a href="{{ route('pricing') }}" class="font-semibold text-track underline-offset-4 hover:underline">{{ __('Compare plans') }}</a></p>
                @endif
            </section>

            <section class="flex min-w-0 flex-[1_1_340px] flex-col rounded-[28px] bg-white px-[22px] pb-3 pt-2.5 sm:px-[26px]" aria-labelledby="history">
                <h3 id="history" class="py-4 text-lg font-bold">{{ __('Recent requests and payments') }}</h3>
                @forelse ($history as $purchase)
                    <div class="flex items-center justify-between gap-4 border-t border-[#EEF0F6] py-3 text-sm" data-purchase="{{ $purchase->id }}">
                        <span class="min-w-0">
                            <strong class="block text-[15px]">{{ $isPack ? '+'.number_format($purchase->quantity) : $item['name'] }}</strong>
                            <span class="text-[13px] text-muted">#{{ $purchase->id }} · {{ $purchase->created_at->setTimezone('Asia/Kolkata')->format('j M, g:i A') }}</span>
                        </span>
                        <x-ri.chip :tone="$statusTone[$purchase->status] ?? 'neutral'" class="shrink-0">{{ $statusLabel[$purchase->status] ?? $purchase->status }}</x-ri.chip>
                    </div>
                @empty
                    <p class="border-t border-[#EEF0F6] py-4 text-sm text-muted">{{ __('Nothing for this event yet.') }}</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
