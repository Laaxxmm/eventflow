{{-- Platform checkout for an organiser purchase (Session P1, Daylight Track). $purchase, $order, $item, $event --}}
<x-app-layout>
    <x-slot name="title">{{ __('Checkout') }}</x-slot>
    <x-slot name="header"><h2>{{ __('Checkout') }}</h2></x-slot>

    <div class="mx-auto max-w-[1180px] px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        <section class="flex max-w-md flex-col gap-4 rounded-[28px] bg-white p-[22px] sm:p-7" data-billing-checkout>
            <div>
                <p class="text-sm text-muted">{{ $event->name }}</p>
                <h3 class="ri-headline mt-1 text-[26px]">{{ $item['name'] }}</h3>
            </div>
            <p class="ri-headline text-[34px]">₹{{ number_format($order->amountPaise / 100) }}</p>

            @if ($order->provider === 'razorpay')
                <x-ri.button id="rzp-button" variant="primary" size="md" class="w-full">{{ __('Pay with Razorpay') }}</x-ri.button>
                <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                <script>
                    const options = @json($order->checkout);
                    options.handler = () => { window.location = @js(route('billing.return', $purchase)); };
                    const rzp = new Razorpay(options);
                    document.getElementById('rzp-button').onclick = (e) => { rzp.open(); e.preventDefault(); };
                </script>
            @elseif ($order->provider === 'cashfree')
                <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
                <script>
                    Cashfree({ mode: @js(($order->checkout['env'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox') })
                        .checkout({ paymentSessionId: @js($order->checkout['payment_session_id'] ?? ''), redirectTarget: '_self' });
                </script>
            @endif

            <p class="text-sm text-muted">{{ __('You come back here after paying. The unlock is confirmed by the payment provider.') }}</p>
        </section>
    </div>
</x-app-layout>
