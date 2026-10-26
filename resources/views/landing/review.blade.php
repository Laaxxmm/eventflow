@extends('landing.layout')

@section('title', __('Review and pay'))

{{--
    Review and pay (docs/design/daylight-track/Review.dc.html): back to the event, a three-step bar on
    "Pay", the distance tile, the runner's details in one card and a pay bar fixed to the bottom.
--}}
@section('content')
    @php
        $amount = number_format($registration->amount_paise / 100, 2);
        $pending = $registration->status === \App\Payments\PaymentStatus::Pending;
        $eventUrl = route('landing.show', [$config->org, $config->slug]);
    @endphp
    <main class="mx-auto max-w-xl pb-36">
        <header class="flex items-center gap-3 px-5 py-4">
            <a href="{{ $eventUrl }}" aria-label="{{ __('Back to event page') }}" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></a>
            <span class="flex min-w-0 flex-col"><span class="truncate text-[13px] text-muted">{{ $config->name }}</span><h1 class="font-bold">{{ __('Review and pay') }}</h1></span>
        </header>

        <ol aria-label="{{ __('Steps') }}" class="mx-5 grid grid-cols-3 gap-1.5 text-xs font-semibold">
            @foreach ([__('Distance'), __('Your details'), __('Pay')] as $i => $step)
                <li @if ($i === 2) aria-current="step" @endif class="flex flex-col gap-1.5 text-track-deep"><span class="h-1 rounded-full bg-track"></span>{{ $step }}</li>
            @endforeach
        </ol>

        <div class="mx-3 mt-5 flex items-center gap-3.5 rounded-[24px] bg-white p-[18px]">
            <span class="ri-headline flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-heat text-base text-on-heat">{{ \Illuminate\Support\Str::limit(\App\View\LandingPage::numeral($distance), 5, '') }}</span>
            <span class="flex min-w-0 flex-1 flex-col gap-0.5"><strong>{{ $distance->label }}</strong><span class="text-[13px] text-muted">{{ __('Your place is held for :minutes minutes', ['minutes' => \App\Landing\Capacity::PENDING_HOLD_MINUTES]) }}</span></span>
        </div>

        <div class="m-3 rounded-[24px] bg-white px-[18px] py-5">
            <h2 class="ri-headline text-2xl">{{ __('Your details') }}</h2>
            <dl class="mt-3 divide-y divide-[#EEF0F6] text-[15px]">
                <div class="flex justify-between gap-4 py-3"><dt class="text-muted">{{ __('Registration') }}</dt><dd class="font-semibold tracking-[.02em]">{{ $registration->pass_no }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-muted">{{ __('Participant') }}</dt><dd class="text-right font-semibold">{{ $registration->participant_name }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-muted">{{ __('Distance') }}</dt><dd class="text-right font-semibold">{{ $distance->label }}</dd></div>
                @if (isset($registration->answers['age_band']))
                    <div class="flex justify-between gap-4 py-3"><dt class="text-muted">{{ __('Category') }}</dt><dd class="text-right font-semibold">{{ $registration->answers['age_band'] }} ({{ __('age :age', ['age' => $registration->answers['age']]) }})</dd></div>
                @endif
                <div class="flex justify-between gap-4 py-3"><dt class="text-muted">{{ __('Race day') }}</dt><dd class="text-right font-semibold">{{ $config->date->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE)->format('D, j M Y, g:i A') }}</dd></div>
            </dl>
            @unless ($pending)
                <p class="mt-3 rounded-[12px] bg-lilac px-3 py-2.5 text-[13px] text-track-deep">{{ __('Payment status: :status', ['status' => $registration->status->value]) }}</p>
            @endunless
        </div>
        <p class="px-5 text-[13px] text-muted">{{ __('Your EF- pass is emailed once payment is confirmed.') }}</p>
    </main>

    @if ($pending)
        <form method="POST" action="{{ route('payments.create', $registration) }}" class="fixed inset-x-0 bottom-0 z-20 border-t border-mist bg-white" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom))">
            @csrf
            <div class="mx-auto flex max-w-xl items-center justify-between gap-3 px-4 pt-3.5">
                <span class="flex flex-col"><span class="text-[13px] text-muted">{{ __('To pay') }}</span><span class="ri-headline text-2xl">₹{{ $amount }}</span></span>
                <button type="submit" class="ri-glow inline-flex min-h-[52px] items-center rounded-full bg-track px-[22px] font-bold text-on-track hover:brightness-95">{{ __('Pay ₹:amount', ['amount' => $amount]) }}</button>
            </div>
        </form>
    @endif
@endsection
