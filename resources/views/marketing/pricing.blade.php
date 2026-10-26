@extends('marketing.layout')

@section('title', __('Pricing · :brand', ['brand' => \App\Saas\Plans::brandName()]))

@php
    use App\Saas\Plans;
    $pct = fn ($p) => $p['participant_fee_percent'] === null ? null : rtrim(rtrim(number_format($p['participant_fee_percent'], 1), '0'), '.').'%';
    $limit = fn ($n) => $n === null ? __('Unlimited') : number_format($n);
    $rows = [
        __('Season Pass') => fn ($p) => Plans::inr($p['season_pass_inr']) ?? __('Custom'),
        __('Fee on paid entries') => fn ($p) => $pct($p) ?? __('Custom'),
        __('Events per season') => fn ($p) => $limit($p['max_events']),
        __('Entries per season') => fn ($p) => $limit($p['max_registrations']),
        __('Event page, payments, QR passes') => fn ($p) => true,
        __('Volunteer QR check-in') => fn ($p) => in_array('checkin', $p['modules'], true),
        __('Race sheets and heats') => fn ($p) => in_array('race_sheets', $p['modules'], true),
        __('Finisher certificates') => fn ($p) => in_array('certificates', $p['modules'], true),
        __('WhatsApp notifications') => fn ($p) => in_array('sms_whatsapp', $p['modules'], true) ?: __('Add-on'),
        __('Custom email sender') => fn ($p) => in_array('email_branding', $p['modules'], true) ?: __('Add-on'),
    ];
    $example = $entry['plan'] ?? null;
@endphp

@section('content')
    <section class="bg-ink text-paper">
        <div class="mx-auto max-w-7xl px-4 pb-40 pt-20 sm:px-6 lg:px-8">
            <p class="ri-kicker text-heat">{{ __('Pricing') }}</p>
            <h1 class="ri-headline mt-5 max-w-5xl text-7xl sm:text-9xl">{{ __('One race or') }} <span class="text-heat">{{ __('a whole season.') }}</span></h1>
            <p class="mt-8 max-w-2xl text-lg text-paper/75">{{ __('Every plan includes the event page, payments into your own Razorpay or Cashfree account and QR passes. Pick the size of your calendar.') }}</p>
            <p class="mt-4 text-sm text-paper/55">{{ __(':days-day free trial on self-serve plans · no card · prices exclude GST', ['days' => $trialDays]) }}</p>
        </div>
    </section>

    <section class="mx-auto -mt-28 max-w-7xl px-4 sm:px-6 lg:px-8">
        @php($cols = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][count($plans)] ?? 'lg:grid-cols-4')
        <div class="grid gap-6 md:grid-cols-2 {{ $cols }} lg:items-stretch">
            @foreach ($plans as $key => $plan)
                @include('marketing.partials.plan-card')
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
        <p class="ri-kicker text-heat">{{ __('Add-ons') }}</p>
        <h2 class="ri-headline mt-4 text-6xl">{{ __('Switch on what you need.') }}</h2>
        <div class="mt-10 grid gap-px border-2 border-ink bg-ink md:grid-cols-3">
            @foreach ($addons as $addon)
                <div class="bg-paper p-6">
                    <p class="flex items-baseline justify-between gap-4">
                        <span class="font-display text-2xl font-bold uppercase">{{ __($addon['name']) }}</span>
                        <span class="whitespace-nowrap font-display text-2xl font-extrabold text-heat">{{ Plans::inr($addon['season_inr']) }}</span>
                    </p>
                    <p class="mt-2 text-sm text-ink/70">{{ __($addon['description']) }}</p>
                </div>
            @endforeach
        </div>

        <h2 class="ri-headline mt-24 text-6xl">{{ __('Compare plans.') }}</h2>
        <div class="mt-10 overflow-x-auto border-2 border-ink bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-ink text-left">
                        <th scope="col" class="p-4 text-xs font-semibold uppercase tracking-[0.16em] text-ink/60">{{ __('Included') }}</th>
                        @foreach ($plans as $plan)
                            <th scope="col" class="p-4 font-display text-2xl font-extrabold uppercase">{{ $plan['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/10">
                    @foreach ($rows as $label => $value)
                        <tr>
                            <th scope="row" class="p-4 text-left font-medium">{{ $label }}</th>
                            @foreach ($plans as $plan)
                                @php($v = $value($plan))
                                <td class="p-4">
                                    @if ($v === true)
                                        <span class="inline-flex" aria-label="{{ __('Included') }}">@include('marketing.partials.tick')</span>
                                    @elseif ($v === false)
                                        <span class="text-ink/30" aria-label="{{ __('Not included') }}">—</span>
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

        <div class="mt-16 grid gap-6 md:grid-cols-2">
            @if ($example && $example['participant_fee_percent'])
                <div class="border-l-4 border-heat bg-white p-8">
                    <h3 class="font-display text-3xl font-bold uppercase">{{ __('How the entry fee share works') }}</h3>
                    <p class="mt-3 text-ink/70">{{ __('A ₹1,000 entry on :plan carries a :fee platform fee. Free entries carry none. Absorb it or add it to the entry price. Gateway charges are between you and Razorpay or Cashfree.', ['plan' => $example['name'], 'fee' => Plans::inr((int) round(1000 * $example['participant_fee_percent'] / 100))]) }}</p>
                </div>
            @endif
            <div class="border-l-4 border-track bg-white p-8">
                <h3 class="font-display text-3xl font-bold uppercase">{{ __('After the trial') }}</h3>
                <p class="mt-3 text-ink/70">{{ __('Online Season Pass billing is being connected. Until it is live nothing is charged automatically: Indefine contacts you to activate your season.') }}</p>
            </div>
        </div>
    </section>
@endsection
