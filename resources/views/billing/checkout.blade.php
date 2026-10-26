{{-- Platform checkout for an organiser purchase (Session P1). $purchase, $order, $item, $event --}}
<x-app-layout>
    <x-slot name="title">{{ __('Checkout') }}</x-slot>
    <x-slot name="header"><h2>{{ __('Checkout') }}</h2></x-slot>

    <div class="mx-auto max-w-md px-4 py-10">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70" data-billing-checkout>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $event->name }}</p>
            <h3 class="mt-1 text-xl font-bold">{{ $item['name'] }}</h3>
            <p class="mt-4 text-3xl font-extrabold">₹{{ number_format($order->amountPaise / 100) }}</p>

            @if ($order->provider === 'razorpay')
                <button id="rzp-button" class="mt-6 w-full rounded-xl bg-[#0b1020] py-3 font-semibold text-white">{{ __('Pay with Razorpay') }}</button>
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

            <p class="mt-4 text-xs text-slate-500">{{ __('You come back here after paying. The unlock is confirmed by the payment provider.') }}</p>
        </div>
    </div>
</x-app-layout>
