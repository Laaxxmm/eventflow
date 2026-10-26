{{--
    Plan and billing (Daylight Track, docs/design/daylight-track/Billing.dc.html): indigo plan card with
    usage bars, next-plan card, participant pack request, add-ons, payments to Indefine.
    Prices always from config/plans.php via App\Saas\Plans.
--}}
@php
    use App\Saas\Plans;
    use App\Saas\PlanLimits;

    $ist = fn ($t) => $t?->setTimezone('Asia/Kolkata')->format('j M Y');
    $nextKey = PlanLimits::upgradeTarget($org);
    $nextPlan = $nextKey ? (Plans::all()[$nextKey] ?? null) : null;

    // Biggest race this term against its participant cap (plan cap + packs).
    $biggest = null;
    if ($pass && $pass->max_registrations !== null) {
        $biggest = \App\Models\Event::withoutGlobalScope(\App\Models\Tenancy\OrgScope::class)->where('org_id', $org->id)
            ->where('date', '>=', $pass->starts_on)->with('org')->get()
            ->map(fn ($e) => ['taken' => PlanLimits::participantsTaken($e), 'cap' => (int) PlanLimits::participantCap($e)])
            ->sortByDesc('taken')->first();
    }
    $pct = fn (int $n, ?int $of) => $of ? min(100, (int) round(100 * $n / $of)) : 0;

    $canBuy = auth()->user()->can('view-dashboard');
    $events = $canBuy ? \App\Models\Event::query()->orderByDesc('date')->get(['id', 'name']) : collect();
    $sizes = \App\Billing\Catalog::packSizes();
    $packOnline = \App\Billing\Checkout::ready() && collect($sizes)->contains(fn ($s) => $s['price_inr'] !== null);
    $purchases = $canBuy ? \App\Models\Purchase::query()->with('event')->latest('id')->limit(10)->get() : collect();
    $eventAddons = Plans::eventAddons();
    $planModules = $plan['modules'] ?? [];
    $statusLabel = ['requested' => __('Request sent'), 'pending' => __('Checkout started'), 'paid' => __('Paid'), 'failed' => __('Not completed')];
    $statusTone = ['requested' => 'trial', 'pending' => 'unpaid', 'paid' => 'paid', 'failed' => 'suspended'];
    $field = 'mt-1.5 block w-full rounded-[14px] border-[#C5CBDB] bg-white text-[15px] font-normal text-ink focus:border-track focus:ring-track';
    $row = 'flex items-center justify-between gap-4 border-t border-[#EEF0F6] py-3';
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Plan and billing') }}</x-slot>
    <x-slot name="header">
        <h2>{{ __('Plan and billing') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        @if ($limitHit === 'races')
            <div class="rounded-[18px] bg-[#FBE4E4] px-4 py-4 text-sm text-[#6E1414] sm:px-5" role="alert">
                <p class="font-semibold">{{ __('You have used all :n races on your plan for this term.', ['n' => $racesAllowed]) }}</p>
                <p class="mt-1">{{ __('Unused races do not roll over, and a new term starts with a fresh count.') }}</p>
                <div class="mt-3">@include('saas.overage-actions', ['org' => $org])</div>
            </div>
        @endif

        @foreach ($pressure as $p)
            <div @class(['rounded-[18px] px-4 py-4 text-sm sm:px-5', 'bg-[#FBE4E4] text-[#6E1414]' => $p['full'], 'bg-[#FDF0DA] text-[#6B4A12]' => ! $p['full']]) role="status">
                <p class="font-semibold">
                    {{ $p['full']
                        ? __(':race has reached its plan limit (:taken of :cap participants). New registrations are paused.', ['race' => $p['event']->name, 'taken' => number_format($p['taken']), 'cap' => number_format($p['cap'])])
                        : __(':race is close to its plan limit: :taken of :cap participants.', ['race' => $p['event']->name, 'taken' => number_format($p['taken']), 'cap' => number_format($p['cap'])]) }}
                </p>
                <div class="mt-3">@include('saas.overage-actions', ['org' => $org, 'event' => $p['event']])</div>
            </div>
        @endforeach

        @if ($welcome)
            <section class="flex flex-wrap items-center justify-between gap-5 rounded-[28px] bg-lilac p-[22px] text-track-deep sm:p-7">
                <div class="min-w-0 max-w-2xl">
                    <h3 class="ri-headline text-[26px] text-ink">{{ __('Welcome to EventFlow, :org!', ['org' => $org->name]) }}</h3>
                    <p class="mt-1.5 text-[15px]">{{ __('Your free trial has started. Verify your email, then publish your race site — no call with us first.') }}</p>
                    @unless (auth()->user()->hasVerifiedEmail())
                        <p class="mt-2 text-sm font-semibold">{{ __('We sent a verification link to :email.', ['email' => auth()->user()->email]) }}</p>
                    @endunless
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ri.button variant="primary" size="md" :href="route('organiser.events.create', array_filter(['template' => $template]))">{{ $template ? __('Create your event with :name', ['name' => config("eventflow.landing_templates.{$template}.label")]) : __('Create your first event') }}</x-ri.button>
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-[44px] items-center px-2 text-sm font-semibold text-track-deep underline-offset-4 hover:underline">{{ __('Go to dashboard') }}</a>
                </div>
            </section>
        @endif

        <div class="flex flex-wrap gap-5">
            {{-- Your plan --}}
            <section class="flex min-w-0 flex-[2_1_460px] flex-col gap-5 rounded-[28px] bg-track p-[22px] text-white sm:p-7" aria-labelledby="your-plan">
                <div class="flex flex-wrap justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-[#E4E1FF]">{{ __('Your plan') }}</p>
                        <h3 id="your-plan" class="ri-headline mt-1 text-[30px] sm:text-[34px]">{{ $plan['name'] ?? __('No plan') }}</h3>
                        <p class="mt-2 flex flex-wrap gap-2">
                            @if ($org->isSuspended())
                                <x-ri.chip tone="suspended">{{ __('Suspended') }}</x-ri.chip>
                            @elseif ($org->subscription_status === 'trialing')
                                <x-ri.chip tone="trial">{{ $org->trial_ends_at ? __('Free trial until :date', ['date' => $ist($org->trial_ends_at)]) : __('Free trial') }}</x-ri.chip>
                            @else
                                <x-ri.chip class="bg-white/15 text-white ring-0">{{ __(ucfirst(str_replace('_', ' ', $org->subscription_status))) }}</x-ri.chip>
                            @endif
                        </p>
                    </div>
                    @if ($plan)
                        <div class="sm:text-right">
                            <p class="ri-headline text-[30px]">{{ Plans::priceLabel($plan['price_inr']) }}</p>
                            <p class="mt-0.5 text-[13px] text-[#E4E1FF]">
                                @if ($plan['term_label'])
                                    {{ __($plan['term_label']) }} · {{ $plan['renews'] ? __('renews yearly') : __('one-off, does not auto-renew') }}, {{ __('+ GST') }}
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                @if ($pass || $trial)
                    <div class="grid gap-4 sm:grid-cols-2">
                        @if ($pass)
                            <div>
                                <div class="mb-2 flex justify-between gap-3 text-sm"><span>{{ __('Races this term') }}</span><strong>{{ $racesAllowed === null ? __(':used (not capped)', ['used' => $racesUsed]) : __(':used of :allowed', ['used' => $racesUsed, 'allowed' => $racesAllowed]) }}</strong></div>
                                @if ($racesAllowed !== null)
                                    <div class="h-2 rounded-full bg-white/20" aria-hidden="true"><div class="h-full rounded-full bg-heat" style="width: {{ $pct($racesUsed, $racesAllowed) }}%"></div></div>
                                @endif
                            </div>
                        @endif
                        @if ($biggest)
                            <div>
                                <div class="mb-2 flex justify-between gap-3 text-sm"><span>{{ __('Biggest race') }}</span><strong>{{ __(':used of :allowed', ['used' => number_format($biggest['taken']), 'allowed' => number_format($biggest['cap'])]) }}</strong></div>
                                <div class="h-2 rounded-full bg-white/20" aria-hidden="true"><div class="h-full rounded-full bg-white" style="width: {{ $pct($biggest['taken'], $biggest['cap']) }}%"></div></div>
                            </div>
                        @endif
                        @if ($trial)
                            <p class="text-sm sm:col-span-2"><span class="text-[#E4E1FF]">{{ __('Trial use') }}:</span> <strong>{{ __(':e of :el live events · :r of :rl registrations', ['e' => $trial['events'], 'el' => $trial['event_limit'], 'r' => $trial['registrations'], 'rl' => $trial['registration_limit']]) }}</strong></p>
                        @endif
                    </div>
                @endif

                <p class="mt-auto text-sm text-[#E4E1FF]">
                    @if ($pass)
                        {{ __('Current term') }}: {{ $pass->starts_on->format('j M Y') }} – {{ $pass->ends_on->format('j M Y') }}.
                        {{ __('Participants per race') }}: {{ $pass->max_registrations === null ? __('Not capped') : number_format($pass->max_registrations).' '.__('(plus any participant packs)') }}.
                        {{ __('Unused races do not roll over.') }}
                    @else
                        {{ $plan['tagline'] ?? '' }}
                    @endif
                </p>
            </section>

            {{-- Next plan up --}}
            @if ($nextPlan)
                <section class="flex min-w-0 flex-[1_1_280px] flex-col gap-3 rounded-[28px] bg-white p-[22px] sm:p-7">
                    <p class="text-sm text-muted">{{ __('Running more races?') }}</p>
                    <h3 class="ri-headline text-[26px]">{{ $nextPlan['name'] }}</h3>
                    <p class="text-[15px] leading-relaxed text-muted">{{ Plans::limitsLabel($nextPlan) }}{{ $nextPlan['support'] ? ', '.mb_strtolower(__(':support support', ['support' => $nextPlan['support']])) : '' }}.</p>
                    <p class="ri-headline text-[26px]">{{ Plans::priceLabel($nextPlan['price_inr']) }}
                        @if ($nextPlan['term_label'])
                            <span class="font-sans text-sm font-medium tracking-normal text-muted [font-variation-settings:normal]">{{ __($nextPlan['term_label']) }}</span>
                        @endif
                    </p>
                    @if ($nextPlan['self_serve'])
                        <x-ri.button variant="ink" size="md" class="mt-auto w-full" :href="\App\Support\Contact::url(__('Upgrade :org to :plan', ['org' => $org->name, 'plan' => $nextPlan['name']]))" :aria-label="__('Upgrade to :plan (:price)', ['plan' => $nextPlan['name'], 'price' => Plans::priceLabel($nextPlan['price_inr'])])" data-upgrade>{{ __('Upgrade') }}</x-ri.button>
                    @else
                        <x-ri.button variant="ink" size="md" class="mt-auto w-full" :href="\App\Support\Contact::url(__('EventFlow plan for :org', ['org' => $org->name]))" data-contact-indefine>{{ __('Contact Indefine') }}</x-ri.button>
                    @endif
                    <a href="{{ route('pricing') }}" class="self-center text-sm font-semibold text-track underline-offset-4 hover:underline">{{ __('Compare plans') }}</a>
                </section>
            @endif
        </div>

        <div class="flex flex-wrap items-start gap-5">
            {{-- Participant pack --}}
            <section class="flex min-w-0 flex-[1_1_420px] flex-col gap-4 rounded-[28px] bg-white p-[22px] sm:p-[26px]">
                <div>
                    <h3 class="text-lg font-bold">{{ __('Buy a participant pack') }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ __('Raise one race’s cap when entries go past your plan.') }}</p>
                </div>
                @if ($canBuy && $events->isNotEmpty() && $sizes)
                    <form method="POST" action="{{ route('billing.store', \App\Billing\Catalog::PACK) }}" class="flex flex-col gap-4">
                        @csrf
                        <label class="ri-label">{{ __('Event') }}
                            <select name="event" class="{{ $field }}">
                                @foreach ($events as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="ri-label">{{ __('Extra places') }}
                            <select name="participants" class="{{ $field }}">
                                @foreach ($sizes as $size)
                                    <option value="{{ $size['participants'] }}">{{ __(':n more', ['n' => number_format($size['participants'])]) }}{{ $size['price_inr'] !== null ? ' · '.Plans::inr($size['price_inr']) : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="ri-label">{{ __('Anything we should know? (optional)') }}
                            <textarea name="note" rows="3" maxlength="500" class="{{ $field }}" placeholder="{{ __('e.g. we expect about 300 more runners after the weekend') }}"></textarea>
                        </label>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-sm text-muted">{{ $packOnline ? __('Priced sizes check out online') : __('Quoted by Indefine') }}</span>
                            <x-ri.button type="submit" variant="primary" size="md">{{ $packOnline ? __('Continue') : __('Send request') }}</x-ri.button>
                        </div>
                    </form>
                @elseif ($canBuy)
                    <p class="text-sm text-muted">{{ __('Create an event first, then you can add places to it here.') }}</p>
                @endif
            </section>

            {{-- Add-ons --}}
            <section class="flex min-w-0 flex-[1_1_380px] flex-col rounded-[28px] bg-white p-[22px] sm:p-[26px]" aria-labelledby="addons">
                <h3 id="addons" class="mb-2.5 text-lg font-bold">{{ __('Add-ons') }}</h3>
                @foreach ($eventAddons as $key => $addon)
                    @php
                        $included = $key === 'custom_domain' ? $domainIncluded : in_array($addon['module'], $planModules, true);
                    @endphp
                    <div class="{{ $row }}" @if ($key === 'custom_domain') data-plan-custom-domain="{{ $domainIncluded ? 'included' : 'addon' }}" @endif>
                        <span class="min-w-0">
                            <strong class="block text-[15px]">{{ __($addon['short'] ?? $addon['name']) }}</strong>
                            <span class="text-[13px] text-muted">{{ $included ? __('Included') : __('Add-on · :price per event', ['price' => Plans::inr($addon['price_inr'])]) }}</span>
                        </span>
                        @if ($key === 'custom_domain')
                            @can('manage-org')
                                <a href="{{ route('settings.domain.show') }}" class="inline-flex min-h-[44px] shrink-0 items-center text-sm font-bold text-track hover:text-track-deep">{{ $domainIncluded ? __('Connect') : __('Add') }}</a>
                            @else
                                @if ($included)<span class="shrink-0 text-[13px] font-semibold text-success">{{ __('In your plan') }}</span>@endif
                            @endcan
                        @elseif ($included)
                            <span class="shrink-0 text-[13px] font-semibold text-success">{{ __('In your plan') }}</span>
                        @elseif ($canBuy)
                            <a href="{{ route('billing.show', $key) }}" class="inline-flex min-h-[44px] shrink-0 items-center text-sm font-bold text-track hover:text-track-deep">{{ __('Add') }}</a>
                        @endif
                    </div>
                @endforeach
                <div class="{{ $row }}">
                    <span class="min-w-0">
                        <strong class="block text-[15px]">{{ __('Participant email') }}</strong>
                        <span class="text-[13px] {{ $mail === 'held' ? 'font-semibold text-warning' : 'text-muted' }}">{{ ['smtp' => __('Your SMTP (free)'), 'platform' => __('Platform mail (add-on)'), 'held' => __('On hold: set up email')][$mail] }}</span>
                    </span>
                    @can('manage-org')
                        <a href="{{ route('settings.notifications.show') }}" class="inline-flex min-h-[44px] shrink-0 items-center text-sm font-bold text-track hover:text-track-deep">{{ __('Settings') }}</a>
                    @endcan
                </div>
                @foreach (Plans::addons() as $key => $addon)
                    <div class="{{ $row }}">
                        <span class="min-w-0">
                            <strong class="block text-[15px]">{{ __($addon['name']) }}</strong>
                            <span class="line-clamp-2 text-[13px] text-muted">{{ __($addon['description']) }}</span>
                        </span>
                        @if (isset($addons[$key]))
                            <span class="shrink-0 text-[13px] font-semibold text-success">{{ __('Active') }}</span>
                        @else
                            <a href="{{ \App\Support\Contact::url(__(':addon for :org', ['addon' => $addon['name'], 'org' => $org->name])) }}" class="inline-flex min-h-[44px] shrink-0 items-center text-sm font-bold text-track hover:text-track-deep">{{ __('Add') }}</a>
                        @endif
                    </div>
                @endforeach
            </section>
        </div>

        {{-- Payments to Indefine --}}
        <section class="rounded-[28px] bg-white px-[22px] pb-4 pt-2.5 sm:px-[26px]" aria-labelledby="payments">
            <h3 id="payments" class="py-4 text-lg font-bold">{{ __('Payments to Indefine') }}</h3>
            @if ($purchases->isNotEmpty())
                <div class="relative overflow-x-auto">
                    <table class="w-full min-w-[560px] text-[15px]">
                        <thead>
                            <tr class="text-left text-[13px] text-muted">
                                <th scope="col" class="py-2.5 font-semibold">{{ __('Date') }}</th>
                                <th scope="col" class="font-semibold">{{ __('Item') }}</th>
                                <th scope="col" class="font-semibold">{{ __('Status') }}</th>
                                <th scope="col" class="text-right font-semibold">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchases as $purchase)
                                <tr class="border-t border-[#EEF0F6]" data-purchase="{{ $purchase->id }}">
                                    <td class="whitespace-nowrap py-3.5 pe-4">{{ $purchase->created_at->setTimezone('Asia/Kolkata')->format('d-M-Y') }}</td>
                                    <td class="pe-4">
                                        {{ $purchase->sku === \App\Billing\Catalog::PACK ? __(':n-place participant pack', ['n' => number_format($purchase->quantity)]) : __($eventAddons[$purchase->sku]['name'] ?? $purchase->sku) }}
                                        @if ($purchase->event)<span class="block text-[13px] text-muted">{{ $purchase->event->name }}</span>@endif
                                    </td>
                                    <td class="pe-4"><x-ri.chip :tone="$statusTone[$purchase->status] ?? 'neutral'">{{ $statusLabel[$purchase->status] ?? $purchase->status }}</x-ri.chip></td>
                                    <td class="whitespace-nowrap text-right">{{ $purchase->amount_paise !== null ? Plans::inr(intdiv($purchase->amount_paise, 100)) : __('Quote') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="border-t border-[#EEF0F6] py-4 text-sm text-muted">{{ __('No payments or requests yet.') }}</p>
            @endif
            <p class="mt-2 rounded-[18px] bg-paper px-4 py-3 text-sm text-ink">
                {{ $checkoutReady
                    ? __('Participant packs and add-ons with a price check out online and switch on as soon as the payment is confirmed. Plan changes go to Indefine; nothing renews automatically.')
                    : __('Plan changes, participant packs and add-ons go to Indefine as a request and are switched on for you. Nothing is charged automatically.') }}
            </p>
        </section>
    </div>
</x-app-layout>
