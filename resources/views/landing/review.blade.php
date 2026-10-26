@extends('landing.layout')

@section('title', __('Review and pay'))

@section('content')
    <main class="max-w-xl mx-auto px-4 sm:px-6 py-12">
        <p class="text-sm font-semibold uppercase tracking-widest text-muted">{{ $config->name }}</p>
        <h1 class="mt-2 text-3xl font-extrabold">{{ __('Review and pay') }}</h1>

        <div class="mt-6 rounded-lg bg-white p-4 sm:p-8 shadow-sm border-t-4 border-[var(--brand-primary)]">
            <dl class="divide-y divide-[#EEF0F6] text-sm">
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">{{ __('Registration') }}</dt>
                    <dd class="font-mono font-semibold">{{ $registration->pass_no }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">{{ __('Participant') }}</dt>
                    <dd class="font-semibold text-right">{{ $registration->participant_name }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">{{ __('Distance') }}</dt>
                    <dd class="font-semibold text-right">{{ $distance->label }}</dd>
                </div>
                @if (isset($registration->answers['age_band']))
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-muted">{{ __('Category') }}</dt>
                        <dd class="font-semibold text-right">{{ $registration->answers['age_band'] }} ({{ __('age :age', ['age' => $registration->answers['age']]) }})</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">{{ __('Race day') }}</dt>
                    <dd class="font-semibold text-right">{{ $config->date->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE)->format('D, j M Y, g:i A') }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3 text-base">
                    <dt class="font-semibold">{{ __('Amount') }}</dt>
                    <dd class="font-extrabold">₹{{ number_format($registration->amount_paise / 100, 2) }}</dd>
                </div>
            </dl>

            @if ($registration->status === \App\Payments\PaymentStatus::Pending)
                <form method="POST" action="{{ route('payments.create', $registration) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="w-full inline-flex justify-center rounded-full px-6 py-3 font-semibold bg-[var(--brand-primary)] text-[var(--brand-on-primary)] hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-primary)]">
                        {{ __('Pay ₹:amount', ['amount' => number_format($registration->amount_paise / 100, 2)]) }}
                    </button>
                </form>
                <p class="mt-3 text-xs text-muted">{{ __('Your EF- pass is emailed once payment is confirmed.') }}</p>
            @else
                <p class="mt-6 text-sm font-semibold text-ink">{{ __('Payment status: :status', ['status' => $registration->status->value]) }}</p>
            @endif
        </div>

        <a href="{{ route('landing.show', [$config->org, $config->slug]) }}" class="mt-6 inline-block text-sm text-muted underline hover:text-ink">{{ __('Back to event page') }}</a>
    </main>
@endsection
