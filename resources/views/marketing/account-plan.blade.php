<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Plan and billing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($limitHit === 'races')
                <div class="p-4 sm:p-6 rounded-lg bg-red-50 text-sm text-red-900 ring-1 ring-red-200" role="alert">
                    <p class="font-semibold">{{ __('You have used all :n races on your plan for this term.', ['n' => $racesAllowed]) }}</p>
                    <p class="mt-1">{{ __('Unused races do not roll over, and a new term starts with a fresh count.') }}</p>
                    <div class="mt-3">@include('saas.overage-actions', ['org' => $org])</div>
                </div>
            @endif

            @foreach ($pressure as $row)
                <div @class(['p-4 sm:p-6 rounded-lg text-sm ring-1', 'bg-red-50 text-red-900 ring-red-200' => $row['full'], 'bg-amber-50 text-amber-900 ring-amber-200' => ! $row['full']]) role="status">
                    <p class="font-semibold">
                        {{ $row['full']
                            ? __(':race has reached its plan limit (:taken of :cap participants). New registrations are paused.', ['race' => $row['event']->name, 'taken' => number_format($row['taken']), 'cap' => number_format($row['cap'])])
                            : __(':race is close to its plan limit: :taken of :cap participants.', ['race' => $row['event']->name, 'taken' => number_format($row['taken']), 'cap' => number_format($row['cap'])]) }}
                    </p>
                    <div class="mt-3">@include('saas.overage-actions', ['org' => $org, 'event' => $row['event']])</div>
                </div>
            @endforeach

            @if ($welcome)
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Welcome to EventFlow, :org!', ['org' => $org->name]) }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Your free trial has started. Verify your email, then publish your race site — no call with us first.') }}</p>
                    @unless (auth()->user()->hasVerifiedEmail())
                        <p class="mt-2 text-sm font-medium text-gray-900">{{ __('We sent a verification link to :email.', ['email' => auth()->user()->email]) }}</p>
                    @endunless
                    <div class="mt-4 flex flex-wrap gap-4">
                        <a href="{{ route('organiser.events.create', array_filter(['template' => $template])) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ $template ? __('Create your event with :name', ['name' => config("eventflow.landing_templates.{$template}.label")]) : __('Create your first event') }}</a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-gray-600 underline hover:text-gray-900">{{ __('Go to dashboard') }}</a>
                    </div>
                </div>
            @endif

            <section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">{{ $plan['name'] ?? __('No plan') }}</h2>
                        @if ($plan)
                            <p class="mt-1 text-sm text-gray-600">{{ $plan['tagline'] }}</p>
                        @endif
                    </header>

                    <dl class="mt-6 divide-y divide-gray-100 text-sm">
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-gray-600">{{ __('Status') }}</dt>
                            <dd class="font-medium text-gray-900">
                                @if ($org->isSuspended())
                                    {{ __('Suspended') }}
                                @elseif ($org->subscription_status === 'trialing')
                                    {{ __('Free trial until :date', ['date' => $org->trial_ends_at?->setTimezone('Asia/Kolkata')->format('j M Y')]) }}
                                @else
                                    {{ __(ucfirst(str_replace('_', ' ', $org->subscription_status))) }}
                                @endif
                            </dd>
                        </div>
                        @if ($plan)
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-gray-600">{{ __('Price') }}</dt>
                                <dd class="font-medium text-gray-900 text-right">
                                    {{ \App\Saas\Plans::priceLabel($plan['price_inr']) }}
                                    @if ($plan['term_label'])
                                        / {{ __($plan['term_label']) }} · {{ $plan['renews'] ? __('renews yearly') : __('one-off, does not auto-renew') }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if ($trial)
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-gray-600">{{ __('Trial use') }}</dt>
                                <dd class="font-medium text-gray-900 text-right">{{ __(':e of :el live events · :r of :rl registrations', ['e' => $trial['events'], 'el' => $trial['event_limit'], 'r' => $trial['registrations'], 'rl' => $trial['registration_limit']]) }}</dd>
                            </div>
                        @endif
                        @if ($pass)
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-gray-600">{{ __('Current term') }}</dt>
                                <dd class="font-medium text-gray-900">{{ $pass->starts_on->format('j M Y') }} – {{ $pass->ends_on->format('j M Y') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-gray-600">{{ __('Races this term') }}</dt>
                                <dd class="font-medium text-gray-900">{{ $racesAllowed === null ? __(':used (not capped)', ['used' => $racesUsed]) : __(':used of :allowed', ['used' => $racesUsed, 'allowed' => $racesAllowed]) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-gray-600">{{ __('Participants per race') }}</dt>
                                <dd class="font-medium text-gray-900">{{ $pass->max_registrations === null ? __('Not capped') : number_format($pass->max_registrations).' '.__('(plus any participant packs)') }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-gray-600">{{ __('Participant email') }}</dt>
                            <dd class="font-medium text-gray-900 text-right">
                                {{ ['smtp' => __('Your SMTP (free)'), 'platform' => __('Platform mail (add-on)'), 'held' => __('On hold: set up email')][$mail] }}
                                @can('manage-org')
                                    · <a href="{{ route('settings.notifications.show') }}" class="underline hover:text-gray-900">{{ __('Settings') }}</a>
                                @endcan
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-3" data-plan-custom-domain="{{ $domainIncluded ? 'included' : 'addon' }}">
                            <dt class="text-gray-600">{{ __('Custom domain') }}</dt>
                            <dd class="font-medium text-gray-900 text-right">
                                {{ $domainIncluded ? __('Included') : __('Add-on · :price per event', ['price' => \App\Saas\Plans::inr(\App\Saas\Plans::customDomainPrice())]) }}
                                @can('manage-org')
                                    · <a href="{{ route('settings.domain.show') }}" class="underline hover:text-gray-900">{{ $domainIncluded ? __('Connect') : __('Add') }}</a>
                                @endcan
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-gray-600">{{ __('Add-ons') }}</dt>
                            <dd class="font-medium text-gray-900 text-right">{{ collect($addons)->pluck('name')->map(fn ($n) => __($n))->implode(', ') ?: __('None') }}</dd>
                        </div>
                    </dl>

                    <p class="mt-6 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                        {{ $checkoutReady
                            ? __('Participant packs and add-ons with a price check out online and switch on as soon as the payment is confirmed. Plan changes go to Indefine; nothing renews automatically.')
                            : __('Plan changes, participant packs and add-ons go to Indefine as a request and are switched on for you. Nothing is charged automatically.') }}
                    </p>
                    <div class="mt-4">@include('saas.overage-actions', ['org' => $org])</div>
                    <p class="mt-4 text-sm text-gray-600"><a href="{{ route('pricing') }}" class="underline hover:text-gray-900">{{ __('Compare plans') }}</a></p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
