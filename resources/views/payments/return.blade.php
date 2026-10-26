{{--
    After payment (docs/design/daylight-track/PassReady.dc.html): paid shows the pass with its QR,
    Download your pass (PDF), Add to calendar and Share; otherwise a calm "being processed" card.
--}}
@php
    $r = $registration->loadMissing('event.org');
    $event = $r->event;
    $paid = $r->status === \App\Payments\PaymentStatus::Paid;
    $first = \Illuminate\Support\Str::before($r->participant_name, ' ');
    $eventUrl = $event && $event->org ? route('landing.show', [$event->org->slug, $event->slug]) : null;
    $calendar = $event ? 'https://calendar.google.com/calendar/render?'.http_build_query([
        'action' => 'TEMPLATE',
        'text' => $event->name,
        'dates' => $event->date->utc()->format('Ymd\THis\Z').'/'.$event->date->utc()->addHours(4)->format('Ymd\THis\Z'),
        'location' => $event->venue,
        'details' => $r->pass_no,
    ]) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $paid ? __('Your pass') : __('Payment status') }} · {{ config('app.name', 'EventFlow') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @media (prefers-reduced-motion: no-preference) { .ef-pop { animation: ef-pop .6s cubic-bezier(.2,.9,.3,1.3) both; } }
            @keyframes ef-pop { from { transform: scale(.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        </style>
    </head>
    <body class="min-h-screen bg-track font-sans text-white antialiased">
        <main class="mx-auto flex max-w-[430px] flex-col items-center gap-5 px-5 py-10">
            @if ($paid)
                <span class="ef-pop flex h-16 w-16 items-center justify-center rounded-full bg-heat" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#121A33" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span>
                <h1 class="ri-headline text-center text-[34px]">{{ __('You’re in, :name.', ['name' => $first]) }}</h1>
                <p class="-mt-1.5 text-center text-[15px] text-[#E4E1FF]">{{ __(':amount paid. Your pass is also on its way to :email.', ['amount' => \App\View\LandingPage::inr(intdiv($r->amount_paise, 100)), 'email' => $r->email]) }}</p>

                <x-ri.pass-card :registration="$r" />

                {{-- Always offered, so participants have their pass even when the organiser's email is on hold. --}}
                <a href="{{ \App\Notifications\EmailBrand::passUrl($r) }}" class="inline-flex min-h-[52px] w-full items-center justify-center rounded-full bg-heat px-5 font-bold text-on-heat hover:brightness-95">{{ __('Download your pass (PDF)') }}</a>
                <div class="grid w-full grid-cols-2 gap-2.5">
                    @if ($calendar)
                        <a href="{{ $calendar }}" target="_blank" rel="noopener" class="inline-flex min-h-[48px] items-center justify-center rounded-full bg-white/[.14] px-3 text-sm font-semibold hover:bg-white/20">{{ __('Add to calendar') }}</a>
                    @endif
                    @if ($eventUrl)
                        <a href="https://wa.me/?text={{ rawurlencode(__('I’m running :event. Join me: :url', ['event' => $event->name, 'url' => $eventUrl])) }}" target="_blank" rel="noopener" class="inline-flex min-h-[48px] items-center justify-center rounded-full bg-white/[.14] px-3 text-sm font-semibold hover:bg-white/20">{{ __('Share with friends') }}</a>
                    @endif
                </div>
            @else
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/[.14]" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <h1 class="ri-headline text-center text-[34px]">{{ __('Payment is being processed') }}</h1>
                <div class="w-full rounded-[28px] bg-white p-6 text-ink" role="status">
                    <p class="text-[15px]">{{ __('You will get your pass by email as soon as the payment is confirmed. You can close this page.') }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-muted">{{ __('Pass') }}</dt><dd class="font-bold">{{ $r->pass_no }}</dd></div>
                        <div><dt class="text-xs text-muted">{{ __('Status') }}</dt><dd><x-ri.chip :tone="$r->status === \App\Payments\PaymentStatus::Failed ? 'suspended' : 'unpaid'">{{ __(ucfirst($r->status->value)) }}</x-ri.chip></dd></div>
                    </dl>
                </div>
            @endif
            @if ($eventUrl)
                <a href="{{ $eventUrl }}" class="inline-flex min-h-[44px] items-center text-sm font-semibold text-white underline-offset-4 hover:underline">{{ __('Back to event page') }}</a>
            @endif
        </main>
    </body>
</html>
