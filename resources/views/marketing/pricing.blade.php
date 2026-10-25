@extends('marketing.layout')

@section('title', __('Pricing · :brand', ['brand' => \App\Saas\Plans::brandName()]))

@section('content')
    <section class="bg-[#0b1020] text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-16 pb-36 text-center">
            <p class="text-sm font-bold uppercase tracking-widest text-[#d9ff45]">{{ __('Pricing') }}</p>
            <h1 class="mt-3 text-4xl sm:text-5xl font-black tracking-tight">{{ __('Pay for the season, not per event.') }}</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-white/75">
                {{ __('Every plan is a Season Pass (12 months of events) plus a small percentage on paid registrations. Entry fees go straight to your own Razorpay or Cashfree account.') }}
            </p>
            <p class="mt-4 text-sm text-white/60">{{ __(':days-day free trial on Starter and Growth · no card needed · prices exclude GST', ['days' => $trialDays]) }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6 -mt-24">
        <div class="grid gap-6 lg:grid-cols-3 lg:items-center">
            @foreach ($plans as $key => $plan)
                @include('marketing.partials.plan-card')
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6 py-20">
        <h2 class="text-2xl sm:text-3xl font-black tracking-tight">{{ __('Add-ons') }}</h2>
        <p class="mt-2 text-gray-600">{{ __('Switch them on when you sign up, or later from your settings. Billed per season.') }}</p>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ($addons as $addon)
                <div class="rounded-3xl bg-white p-6 ring-1 ring-black/5">
                    <p class="flex items-baseline justify-between gap-4">
                        <span class="font-extrabold">{{ __($addon['name']) }}</span>
                        <span class="whitespace-nowrap font-bold">{{ \App\Saas\Plans::inr($addon['season_inr']) }}</span>
                    </p>
                    <p class="mt-2 text-sm text-gray-600">{{ __($addon['description']) }}</p>
                </div>
            @endforeach
        </div>

        <h2 class="mt-20 text-2xl sm:text-3xl font-black tracking-tight">{{ __('Compare plans') }}</h2>
        <div class="mt-8 overflow-x-auto rounded-3xl bg-white ring-1 ring-black/5">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-black/5 text-left">
                        <th scope="col" class="p-4 font-semibold text-gray-600">{{ __('Included') }}</th>
                        @foreach ($plans as $plan)
                            <th scope="col" class="p-4 font-extrabold">{{ $plan['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5">
                    @php
                        $limit = fn ($n) => $n === null ? __('Unlimited') : number_format($n);
                        $rows = [
                            __('Season Pass') => fn ($p) => \App\Saas\Plans::inr($p['season_pass_inr']) ?? __('Custom'),
                            __('Fee on paid registrations') => fn ($p) => $p['participant_fee_percent'] === null ? __('Custom') : rtrim(rtrim(number_format($p['participant_fee_percent'], 1), '0'), '.').'%',
                            __('Events per season') => fn ($p) => $limit($p['max_events']),
                            __('Registrations per season') => fn ($p) => $limit($p['max_registrations']),
                            __('Event pages, payments, passes') => fn ($p) => true,
                            __('QR check-in') => fn ($p) => in_array('checkin', $p['modules'], true),
                            __('Race sheets') => fn ($p) => in_array('race_sheets', $p['modules'], true),
                            __('Certificates') => fn ($p) => in_array('certificates', $p['modules'], true),
                            __('WhatsApp notifications') => fn ($p) => in_array('sms_whatsapp', $p['modules'], true) ?: __('Add-on'),
                            __('Custom email sender') => fn ($p) => in_array('email_branding', $p['modules'], true) ?: __('Add-on'),
                        ];
                    @endphp
                    @foreach ($rows as $label => $value)
                        <tr>
                            <th scope="row" class="p-4 text-left font-medium text-gray-700">{{ $label }}</th>
                            @foreach ($plans as $plan)
                                @php($v = $value($plan))
                                <td class="p-4">
                                    @if ($v === true)
                                        <span class="font-bold text-[#16a34a]" aria-label="{{ __('Included') }}">✓</span>
                                    @elseif ($v === false)
                                        <span class="text-gray-400" aria-label="{{ __('Not included') }}">—</span>
                                    @else
                                        {{ $v }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-20 grid gap-6 md:grid-cols-2">
            <div class="rounded-3xl bg-white p-8 ring-1 ring-black/5">
                <h3 class="text-lg font-extrabold">{{ __('How the participant fee works') }}</h3>
                <p class="mt-3 text-gray-600">@php($starter = $plans[config('plans.default')])
                    {{ __('A ₹1,000 entry on :plan carries a :fee platform fee. Free registrations carry none. You choose whether to absorb it or add it to the entry price. Gateway charges are between you and Razorpay or Cashfree.', ['plan' => $starter['name'], 'fee' => \App\Saas\Plans::inr((int) round(1000 * $starter['participant_fee_percent'] / 100))]) }}</p>
            </div>
            <div class="rounded-3xl bg-white p-8 ring-1 ring-black/5">
                <h3 class="text-lg font-extrabold">{{ __('After the trial') }}</h3>
                <p class="mt-3 text-gray-600">{{ __('Online Season Pass billing with Cashfree or Razorpay is being connected. Until it is live, nothing is charged automatically: Indefine contacts you to activate your season.') }}</p>
            </div>
        </div>
    </section>
@endsection
