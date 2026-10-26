@extends('marketing.layout')

@section('title', __('Pricing · :brand', ['brand' => \App\Saas\Plans::brandName()]))

@section('content')
    <section class="bg-[#0b1020] text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-16 pb-36 text-center">
            <p class="text-sm font-bold uppercase tracking-widest text-[#d9ff45]">{{ __('Pricing') }}</p>
            @php
                $single = $plans['single_event'];
            @endphp
            <h1 class="mt-3 text-4xl sm:text-5xl font-black tracking-tight">{{ __('One race from :price.', ['price' => \App\Saas\Plans::inr($single['price_inr'])]) }}</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-white/75">
                {{ __(':plan covers one race with up to :pax participants in a 90-day window. Running a calendar? Club and Circuit Season cover a year of races. Entry fees go straight to your own Razorpay or Cashfree account.', ['plan' => $single['name'], 'pax' => number_format($single['max_participants_per_event'])]) }}
            </p>
            <p class="mt-4 text-sm text-white/60">{{ __(':days-day free trial · no card needed · prices exclude GST', ['days' => $trialDays]) }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6 -mt-24">
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4 xl:items-stretch">
            @foreach ($plans as $key => $plan)
                @include('marketing.partials.plan-card')
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6 py-20">
        <h2 class="text-2xl sm:text-3xl font-black tracking-tight">{{ __('Add-ons') }}</h2>
        <p class="mt-2 text-gray-600">{{ __('Email with your own SMTP is included in every plan. These are paid extras, quoted by Indefine.') }}</p>
        <div class="mt-8 grid gap-4 md:grid-cols-2">
            @foreach ($addons as $addon)
                <div class="rounded-3xl bg-white p-6 ring-1 ring-black/5">
                    <p class="flex items-baseline justify-between gap-4">
                        <span class="font-extrabold">{{ __($addon['name']) }}</span>
                        <span class="whitespace-nowrap font-bold">{{ \App\Saas\Plans::inr($addon['price_inr']) ?? __('Paid add-on') }}</span>
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
                        $quote = __('Quote');
                        $rows = [
                            __('Price (+ GST)') => fn ($p) => \App\Saas\Plans::inr($p['price_inr']) ?? __('Contact Indefine'),
                            __('Term') => fn ($p) => $p['term_label'] ? __($p['term_label']).($p['renews'] ? __(', renews yearly') : __(', one-off')) : $quote,
                            __('Races') => fn ($p) => $p['max_events'] === null ? $quote : ($p['max_events'] === 1 ? '1' : __('Up to :n', ['n' => $p['max_events']])),
                            __('Participants per race') => fn ($p) => $p['max_participants_per_event'] === null ? $quote : number_format($p['max_participants_per_event']),
                            __('Event pages, payments, passes') => fn ($p) => true,
                            __('QR check-in') => fn ($p) => in_array('checkin', $p['modules'], true),
                            __('Race sheets') => fn ($p) => in_array('race_sheets', $p['modules'], true),
                            __('Certificates') => fn ($p) => in_array('certificates', $p['modules'], true),
                            __('Email') => fn ($p) => true,
                            __('Own SMTP') => fn ($p) => true,
                            __('Platform mail') => fn ($p) => __('Add-on'),
                            __('WhatsApp') => fn ($p) => __('Add-on'),
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
                <h3 class="text-lg font-extrabold">{{ __('When a race reaches its limit') }}</h3>
                <p class="mt-3 text-gray-600">{{ __('New registrations pause at your plan\'s participant limit, with a clear message for runners and for you. Upgrade to the next plan, buy a participant pack for that race, or contact Indefine. A capped plan never quietly goes unlimited. Gateway charges are between you and Razorpay or Cashfree.') }}</p>
            </div>
            <div class="rounded-3xl bg-white p-8 ring-1 ring-black/5">
                <h3 class="text-lg font-extrabold">{{ __('After the trial') }}</h3>
                <p class="mt-3 text-gray-600">{{ __('Online Season Pass billing with Cashfree or Razorpay is being connected. Until it is live, nothing is charged automatically: Indefine contacts you to activate your season.') }}</p>
            </div>
        </div>
    </section>
@endsection
