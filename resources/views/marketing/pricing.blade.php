@extends('marketing.layout')

@section('title', __('Pricing · :brand', ['brand' => \App\Saas\Plans::brandName()]))
@section('description', __('Single Event, Club Season and Circuit Season passes with no ticket percentage, plus Custom for federations. Start a free trial.'))

{{-- /pricing, Daylight Track (Pricing.dc.html). Every price, cap and row value comes from config/plans.php. --}}
@php
    use App\Saas\Plans;

    $quote = __('Quoted');
    $eventAddons = Plans::eventAddons();
    $addOn = fn (string $key) => [__('Add-on, :price per event', ['price' => Plans::inr($eventAddons[$key]['price_inr'])]), 'muted'];
    $included = [__('Included'), null];
    $has = fn (array $p, string $module) => in_array($module, $p['modules'], true);
    $term = function (array $p) use ($quote) {
        if (! $p['term']) {
            return $quote;
        }
        $length = isset($p['term']['days'])
            ? __(':count days', ['count' => $p['term']['days']])
            : __(':count months', ['count' => $p['term']['months']]);

        return $length.($p['renews'] ? __(', renews yearly') : __(', one-off'));
    };

    // Comparison rows: label => fn (plan) => [text, tone]. tone: null | 'muted' | 'strong'.
    $rows = [
        __('Races') => fn ($p) => [match (true) {
            $p['max_events'] === null => __('To suit you'),
            $p['max_events'] === 1 => '1',
            default => __('Up to :count', ['count' => number_format($p['max_events'])]),
        }, null],
        __('Duration') => fn ($p) => [$term($p), null],
        __('Participants per race') => fn ($p) => [$p['max_participants_per_event'] === null ? $quote : number_format($p['max_participants_per_event']), null],
        __('Ticket percentage') => fn ($p) => [__('None'), 'strong'],
        __('Event page, payments, QR passes') => fn ($p) => $included,
        __('Volunteer QR check-in') => fn ($p) => $has($p, 'checkin') ? $included : [__('Not included'), 'muted'],
        __('Race sheets and heats') => fn ($p) => $has($p, 'race_sheets') ? $included : $addOn('race_sheet'),
        __('Finisher certificates') => fn ($p) => match (true) {
            ! $has($p, 'certificates') => $addOn('certificates'),
            ! $p['self_serve'] => [__('Custom layouts by Indefine'), null],
            default => $included,
        },
        __('Custom domain') => fn ($p) => ($p['custom_domain'] ?? false) ? $included : $addOn('custom_domain'),
        __('Email with your own SMTP') => fn ($p) => [__('Free'), null],
        __('Platform mail and WhatsApp') => fn ($p) => $has($p, 'email_branding') && $has($p, 'sms_whatsapp') ? $included : [__('Add-ons'), 'muted'],
        __('Support') => fn ($p) => [__($p['support'] ?? 'Email'), null],
    ];

    $includedIn = fn (string $module) => collect($plans)->filter(fn ($p) => in_array($module, $p['modules'], true))->pluck('name')->implode(', ');
    $domainIncludedIn = collect($plans)->filter(fn ($p) => $p['custom_domain'] ?? false)->pluck('name')->implode(', ');
    $addonRows = [];
    foreach ($eventAddons as $key => $addon) {
        $addonRows[] = ['key' => $key, 'name' => $addon['name'], 'price' => Plans::inr($addon['price_inr']).' '.__('per event'), 'what' => $addon['description'], 'note' => __('Included in :plans', ['plans' => $addon['module'] ? $includedIn($addon['module']) : $domainIncludedIn])];
    }
    foreach ($addons as $key => $addon) {
        $addonRows[] = ['key' => $key, 'name' => $addon['name'], 'price' => Plans::inr($addon['price_inr']) ?? __('Quoted by Indefine'), 'what' => $addon['description'], 'note' => null];
    }
    $pack = config('plans.overage.participant_pack');
@endphp

@section('content')
    <section class="mx-auto flex max-w-[1280px] flex-col items-center gap-[18px] px-5 pb-10 pt-8 text-center sm:px-10 sm:pt-12">
        <h1 class="ri-headline max-w-[52rem] text-[clamp(2.4rem,5vw,4.5rem)]">{{ __('A flat price per race or per season') }}</h1>
        <p class="max-w-[40rem] text-lg leading-relaxed text-muted sm:text-[19px]">{{ __('Every plan starts with a :days-day free trial. Prices are in rupees and exclusive of GST. We never take a percentage of your entry fees.', ['days' => $trialDays]) }}</p>
    </section>

    <section class="mx-auto grid max-w-[1280px] gap-5 px-5 pb-16 pt-6 sm:grid-cols-2 sm:px-10 sm:pb-20 xl:grid-cols-4">
        @foreach ($plans as $key => $plan)
            @include('marketing.partials.plan-card')
        @endforeach
    </section>

    <section class="mx-auto max-w-[1280px] px-5 pb-16 sm:px-10 sm:pb-20">
        <h2 class="ri-headline mb-6 text-3xl sm:text-4xl">{{ __('Compare plans') }}</h2>

        {{-- Phones: one plan at a time. --}}
        <div class="md:hidden" x-data="{ plan: @js(array_key_first($plans)) }" data-compare-mobile>
            <div class="grid grid-cols-2 gap-1 rounded-[26px] bg-white p-1" role="tablist" aria-label="{{ __('Plan') }}">
                @foreach ($plans as $key => $plan)
                    <button type="button" role="tab" id="compare-tab-{{ $key }}" aria-controls="compare-{{ $key }}"
                            class="min-h-[44px] rounded-full px-3 text-sm font-semibold transition duration-200 ease-track"
                            :class="plan === @js($key) ? 'bg-track text-white' : 'text-muted hover:text-ink'"
                            :aria-selected="(plan === @js($key)).toString()" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            @click="plan = @js($key)">{{ $plan['name'] }}</button>
                @endforeach
            </div>
            @foreach ($plans as $key => $plan)
                <dl id="compare-{{ $key }}" role="tabpanel" aria-labelledby="compare-tab-{{ $key }}" class="mt-3 divide-y divide-[#EEF0F6] rounded-[24px] bg-white px-5" x-show="plan === @js($key)" @unless ($loop->first) x-cloak @endunless>
                    @foreach ($rows as $label => $value)
                        @php
                            [$text, $tone] = $value($plan);
                        @endphp
                        <div class="flex items-start justify-between gap-6 py-3.5 text-[15px]">
                            <dt class="font-semibold">{{ $label }}</dt>
                            <dd @class(['text-right', 'text-muted' => $tone === 'muted', 'font-bold' => $tone === 'strong'])>{{ $text }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endforeach
        </div>

        <div class="hidden overflow-x-auto rounded-[28px] bg-white md:block">
            <table class="w-full min-w-[860px] border-collapse text-[15px]" data-compare>
                <thead>
                    <tr class="text-left">
                        <th scope="col" class="w-[28%] px-7 py-[22px] font-semibold text-muted">{{ __('Prices exclude GST') }}</th>
                        @foreach ($plans as $plan)
                            <th scope="col" @class(['px-4 py-[22px] font-bold', 'bg-lilac text-track-deep' => $plan['highlight'] ?? false])>{{ $plan['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $label => $value)
                        <tr class="border-t border-[#EEF0F6]">
                            <th scope="row" class="px-7 py-[18px] text-left font-semibold">{{ $label }}</th>
                            @foreach ($plans as $plan)
                                @php
                                    [$text, $tone] = $value($plan);
                                @endphp
                                <td @class(['px-4 py-[18px]', 'bg-[#F6F5FF]' => $plan['highlight'] ?? false, 'text-muted' => $tone === 'muted', 'font-bold' => $tone === 'strong'])>{{ $text }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mx-auto max-w-[1280px] px-5 pb-16 sm:px-10 sm:pb-20">
        <h2 id="add-ons" class="ri-headline scroll-mt-24 text-3xl sm:text-4xl">{{ __('Add-ons') }}</h2>
        <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-muted">{{ __('Attach what an event needs. Buy them from the event\'s Overview in your dashboard; until an add-on is active, its downloads stay locked with an unlock button.') }}</p>
        <ul class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($addonRows as $row)
                <li class="flex flex-col gap-2 rounded-[24px] bg-white p-[26px]" data-addon="{{ $row['key'] }}">
                    <h3 class="text-lg font-bold">{{ __($row['name']) }}</h3>
                    <p class="ri-headline text-2xl text-track">{{ $row['price'] }}</p>
                    <p class="text-[15px] leading-relaxed text-muted">{{ __($row['what']) }}</p>
                    @if ($row['note'])
                        <p class="text-sm font-semibold text-track-deep">{{ $row['note'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mx-auto grid max-w-[1280px] gap-5 px-5 pb-20 sm:px-10 md:grid-cols-2">
        <div class="flex flex-col gap-2 rounded-[24px] bg-white p-[26px]" data-overage>
            <h3 class="text-lg font-bold">{{ __('Need more places?') }}</h3>
            <p class="text-[15px] leading-relaxed text-muted">{{ __('Buy a participant pack for one race when entries go past your plan’s cap. Your page keeps taking entries.') }}</p>
            @if ($pack)
                <p class="text-[15px] leading-relaxed text-muted">{{ __('Or upgrade to a season plan for more races and bigger caps, or ask Indefine for a Custom quote.') }}</p>
            @endif
        </div>
        <div class="flex flex-col gap-2 rounded-[24px] bg-white p-[26px]">
            <h3 class="text-lg font-bold">{{ __('What happens after the trial?') }}</h3>
            <p class="text-[15px] leading-relaxed text-muted">{{ __('Pick a plan and pay once. Nothing is charged during the trial, and you don’t need a card to start.') }}</p>
            @if (\App\Saas\Trial::stubbed())
                <p class="text-[15px] leading-relaxed text-muted">{{ __('Plan upgrades go to Indefine as a request, and nothing is charged automatically. Participant packs and add-ons are bought from Plan & billing.') }}</p>
            @endif
        </div>
    </section>
@endsection
