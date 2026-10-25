<x-guest-layout>
    <div class="max-w-md mx-auto mt-10 p-6 bg-white rounded-lg shadow text-center">
        <h1 class="text-2xl font-bold mb-4">Payment Status</h1>
        <p class="mb-2">Pass: <strong>{{ $registration->pass_no }}</strong></p>
        <p class="mb-4 text-lg">
            Status:
            <span class="font-semibold {{ $registration->status === \App\Payments\PaymentStatus::Paid ? 'text-green-600' : 'text-yellow-600' }}">
                {{ strtoupper($registration->status->value) }}
            </span>
        </p>
        @if($registration->status === \App\Payments\PaymentStatus::Paid)
            <p class="text-green-700">Thank you! Your registration is confirmed.</p>
        @else
            <p class="text-gray-600">Payment is being processed. You will receive confirmation shortly.</p>
        @endif
    </div>
</x-guest-layout>
