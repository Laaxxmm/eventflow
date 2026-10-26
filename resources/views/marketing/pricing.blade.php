@extends('marketing.layout')

@section('title', __('Pricing · :brand', ['brand' => \App\Saas\Plans::brandName()]))
@section('description', __('Single Event, Club Season and Circuit Season passes with no ticket percentage, plus Custom for federations. Start a free trial.'))

@php
    use App\Saas\Plans;
    $quote = __('Quoted');
    $paid = fn (array $p, string $module) => in_array($module, $p['modules'], true) ?: __('Paid add-on');
    $eventAddons = Plans::eventAddons();
    $perEvent = fn (string $key) => __('Add-on :price / event', ['price' => Plans::inr($eventAddons[$key]['price_inr'])]);
    // Plans that include a module for every event (so its per-event add-on is not needed).
    $includedIn = fn (string $module) => collect($plans)->filter(fn ($p) => in_array($module, $p['modules'], true))->pluck('name')->implode(', ');
    $rows = [
        __('Price (+ GST)') => fn ($p) => Plans::inr($p['price_inr']) ?? $quote,
        __('Races') => fn ($p) => $p['max_events'] === null ? $quote : number_format($p['max_events']),
        __('Duration') => fn ($p) => $p['term_label'] ? __($p['term_label']).($p['renews'] ? __(', renews yearly') : __(', one-off')) : $quote,
        __('Participants per race') => fn ($p) => $p['max_participants_per_event'] === null ? $quote : number_format($p['max_participants_per_event']),
        __('Ticket percentage') => fn ($p) => __('None'),
        __('Event page, payments, QR passes') => fn ($p) => true,
        __('Volunteer QR check-in') => fn ($p) => in_array('checkin', $p['modules'], true),
        __('Race sheets and heats') => fn ($p) => in_array('race_sheets', $p['modules'], true) ?: $perEvent('race_sheet'),
        __('Finisher certificates') => fn ($p) => in_array('certificates', $p['modules'], true) ?: $perEvent('certificates'),
        __('Email · own SMTP') => fn ($p) => __('Included · free'),
        __('Platform mail') => fn ($p) => $paid($p, 'email_branding'),
        __('WhatsApp') => fn ($p) => $paid($p, 'sms_whatsapp'),
        __('Support') => fn ($p) => __($p['support'] ?? 'Email'),
    ];
    $addonRows = [];
    foreach ($eventAddons as $key => $addon) {
        $addonRows[] = ['key' => $key, 'name' => $addon['name'], 'price' => Plans::inr($addon['price_inr']).' '.__('per event'), 'what' => $addon['description'], 'note' => __('Included in :plans', ['plans' => $includedIn($addon['module'])])];
    }
    foreach ($addons as $key => $addon) {
        $addonRows[] = ['key' => $key, 'name' => $addon['name'], 'price' => Plans::inr($addon['price_inr']) ?? __('Paid add-on'), 'what' => $addon['description'], 'note' => null];
    }
    $pack = config('plans.overage.participant_pack');
@endphp

@section('content')
    <section class="bg-ink text-paper">
        <div class="mx-auto max-w-7xl px-5 pb-36 pt-14 sm:px-6 sm:pb-40 sm:pt-20 lg:px-8">
            <p class="ri-kicker text-heat">{{ __('Pricing') }}</p>
            <h1 class="ri-headline mt-5 max-w-5xl text-[3.4rem] leading-[0.92] sm:text-9xl">{{ __('One race or') }} <span class="text-heat">{{ __('a whole season.') }}</span></h1>
            <p class="mt-6 max-w-2xl text-base text-paper/75 sm:mt-8 sm:text-lg">{{ __('A flat price per pass and no percentage on tickets. Entry fees go straight to your own Razorpay or Cashfree account.') }}</p>
            <p class="mt-4 text-sm text-paper/55">{{ __(':days-day free trial · no card · prices exclude GST', ['days' => $trialDays]) }}</p>
        </div>
    </section>

    <section class="mx-auto -mt-28 max-w-7xl sm:px-6 lg:px-8">
        {{-- Phones: swipe between plans (next card peeks); tablet and up: grid. --}}
        <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-5 px-5 pb-6 pt-2 [scrollbar-width:none] md:grid md:snap-none md:grid-cols-2 md:gap-6 md:overflow-visible md:px-0 md:pb-0 lg:grid-cols-[repeat(3,minmax(0,1fr))_minmax(0,0.8fr)] lg:items-stretch" data-plan-swipe>
            @foreach ($plans as $key => $plan)
                <div class="flex w-[84%] shrink-0 snap-start flex-col md:w-auto">@include('marketing.partials.plan-card')</div>
            @endforeach
        </div>
        <p class="px-5 text-center text-xs font-semibold uppercase tracking-[0.18em] text-ink/45 md:hidden" aria-hidden="true">{{ __('Swipe to compare plans') }} →</p>
        <div class="px-5 md:px-0">@include('marketing.partials.messaging-line', ['class' => 'mt-8 text-ink/75'])</div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 sm:px-6 sm:py-24 lg:px-8">
        <h2 class="ri-headline text-5xl sm:text-6xl">{{ __('Compare plans.') }}</h2>

        <div class="mt-8 md:hidden" x-data="{ plan: @js(array_key_first($plans)) }" data-compare-mobile>
            <div class="flex gap-1 overflow-x-auto bg-mist p-1 [scrollbar-width:none]" role="tablist" aria-label="{{ __('Plan') }}">
                @foreach ($plans as $key => $plan)
                    <button type="button" role="tab" class="shrink-0 px-3.5 py-2 text-sm font-semibold transition" :class="plan === @js($key) ? 'bg-ink text-paper' : 'text-ink/60'" :aria-selected="plan === @js($key)" @click="plan = @js($key)">{{ $plan['name'] }}</button>
                @endforeach
            </div>
            @foreach ($plans as $key => $plan)
                <dl class="mt-4 divide-y divide-ink/10 bg-white px-4 ring-1 ring-ink/10" x-show="plan === @js($key)" @unless ($loop->first) x-cloak @endunless>
                    @foreach ($rows as $label => $value)
                        @php($v = $value($plan))
                        <div class="flex items-start justify-between gap-6 py-3.5 text-sm">
                            <dt class="text-ink/65">{{ $label }}</dt>
                            <dd class="text-right font-semibold">
                                @if ($v === true)
                                    <span class="inline-flex" aria-label="{{ __('Included') }}">@include('marketing.partials.tick')</span>
                                @elseif ($v === false)
                                    <span class="text-ink/30" aria-label="{{ __('Not included') }}">—</span>
                                @else
                                    {{ $v }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endforeach
        </div>

        <div class="mt-10 hidden overflow-x-auto border-2 border-ink bg-white md:block">
            <table class="min-w-full text-sm" data-compare>
                <thead>
                    <tr class="border-b-2 border-ink text-left">
                        <th scope="col" class="p-4 text-xs font-semibold uppercase tracking-[0.16em] text-ink/60">{{ __('Per pass') }}</th>
                        @foreach ($plans as $plan)
                            <th scope="col" @class(['p-4 font-display text-2xl font-extrabold uppercase', 'text-ink/55' => ! $plan['self_serve']])>{{ $plan['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/10">
                    @foreach ($rows as $label => $value)
                        <tr>
                            <th scope="row" class="p-4 text-left font-medium">{{ $label }}</th>
                            @foreach ($plans as $plan)
                                @php($v = $value($plan))
                                <td @class(['p-4', 'text-ink/60' => ! $plan['self_serve']])>
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

        {{-- Overage: soft-block, three ways forward --}}
        <div class="mt-24 grid gap-10 lg:grid-cols-[0.8fr_1.2fr]" data-overage>
            <div>
                <p class="ri-kicker text-heat">{{ __('Outgrowing your plan') }}</p>
                <h2 class="ri-headline mt-4 text-5xl sm:text-6xl">{{ __('A nudge, not a locked door.') }}</h2>
                <p class="mt-6 text-ink/70">{{ __('When a race reaches its plan\'s participant limit, new registrations pause with a clear message for runners and for you — never silently going over. Then you choose how to carry on from Plan & billing.') }}</p>
            </div>
            <ol class="grid gap-px border-2 border-ink bg-ink sm:grid-cols-3">
                @foreach ([
                    [__('Upgrade'), __('Move to Club or Circuit for more races and bigger caps per race.')],
                    [__('Buy a participant pack'), __('Add places to just the race that sold out, without changing plan.')],
                    [__('Contact Indefine'), __('For a federation series or a city marathon, we will quote a Custom plan.')],
                ] as $i => [$title, $body])
                    <li class="bg-paper p-6">
                        <span class="font-display text-5xl font-extrabold text-heat">{{ $i + 1 }}</span>
                        <h3 class="mt-3 font-display text-2xl font-bold uppercase">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-ink/70">{{ $body }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
        @if (\App\Saas\Trial::stubbed())
            <p class="mt-6 border-l-4 border-heat bg-white p-4 text-sm text-ink/70">{{ __('Online checkout is rolling out. Until it is live, Upgrade and Buy a participant pack send your request to Indefine, and nothing is charged automatically.') }}</p>
        @endif

        <h2 id="add-ons" class="ri-headline mt-24 scroll-mt-20 text-5xl sm:text-6xl">{{ __('Add-ons.') }}</h2>
        <p class="mt-4 max-w-2xl text-ink/70">{{ __('Attach what an event needs. Buy them from the event\'s Overview in your dashboard; until an add-on is active, its downloads stay locked with an unlock button.') }}</p>
        {{-- Phones: one card per add-on. --}}
        <ul class="mt-8 space-y-3 md:hidden" data-addons-mobile>
            @foreach ($addonRows as $row)
                <li class="bg-white p-5 ring-1 ring-ink/10">
                    <div class="flex items-baseline justify-between gap-4">
                        <p class="font-display text-xl font-bold uppercase leading-tight">{{ __($row['name']) }}</p>
                        <p class="shrink-0 font-display text-lg font-extrabold text-heat">{{ $row['price'] }}</p>
                    </div>
                    <p class="mt-2 text-sm text-ink/70">{{ __($row['what']) }}</p>
                    @if ($row['note'])
                        <p class="mt-2 text-xs text-ink/50">{{ $row['note'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-10 hidden overflow-x-auto border-2 border-ink bg-white md:block">
            <table class="min-w-full text-sm" data-addons-table>
                <thead>
                    <tr class="border-b-2 border-ink text-left text-xs font-semibold uppercase tracking-[0.16em] text-ink/60">
                        <th scope="col" class="p-4">{{ __('Add-on') }}</th>
                        <th scope="col" class="p-4">{{ __('Price') }}</th>
                        <th scope="col" class="p-4">{{ __('What you get') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/10">
                    @foreach ($addonRows as $row)
                        <tr data-addon="{{ $row['key'] }}">
                            <th scope="row" class="p-4 text-left font-display text-xl font-bold uppercase">{{ __($row['name']) }}</th>
                            <td class="whitespace-nowrap p-4 font-display text-xl font-extrabold text-heat">{{ $row['price'] }}</td>
                            <td class="p-4 text-ink/75">
                                {{ __($row['what']) }}
                                @if ($row['note'])
                                    <span class="mt-1 block text-xs text-ink/50">{{ $row['note'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($pack)
            <p class="mt-4 text-sm text-ink/60">{{ __(':name: :what Quoted by Indefine.', ['name' => __($pack['name']), 'what' => __($pack['description'])]) }}</p>
        @endif
    </section>

    @include('marketing.partials.cta-band')
@endsection
