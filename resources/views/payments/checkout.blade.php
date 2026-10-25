<x-guest-layout>
    <div class="max-w-md mx-auto mt-10 p-6 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">Complete Payment</h1>
        <p class="mb-2">Registration: <strong>{{ $registration->pass_no }}</strong></p>
        <p class="mb-4">Amount: ₹{{ number_format($order->amountPaise / 100, 2) }}</p>

        @if($order->provider === 'razorpay')
            <button id="rzp-button" class="w-full bg-blue-600 text-white py-2 rounded">Pay with Razorpay</button>
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            <script>
                const options = @json($order->checkout);
                options.handler = function (response) {
                    window.location = '{{ route('payments.return', $registration) }}?razorpay_payment_id=' + response.razorpay_payment_id;
                };
                const rzp = new Razorpay(options);
                document.getElementById('rzp-button').onclick = function(e) {
                    rzp.open();
                    e.preventDefault();
                };
            </script>
        @elseif($order->provider === 'cashfree')
            <div id="cf-checkout"></div>
            <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
            <script>
                const cashfree = Cashfree({
                    mode: "{{ $order->checkout['env'] === 'production' ? 'production' : 'sandbox' }}"
                });
                cashfree.checkout({
                    paymentSessionId: "{{ $order->checkout['payment_session_id'] }}",
                    redirectTarget: "_self"
                });
            </script>
        @endif

        <p class="mt-4 text-sm text-gray-500">You will be redirected after payment. Status is confirmed via webhook, not this page.</p>
    </div>
</x-guest-layout>
