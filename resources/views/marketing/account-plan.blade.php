<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Plan and billing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                        @if ($plan && $plan['season_pass_inr'] !== null)
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-gray-600">{{ __('Price') }}</dt>
                                <dd class="font-medium text-gray-900 text-right">{{ \App\Saas\Plans::inr($plan['season_pass_inr']) }} · @include('marketing.partials.plan-limits', ['plan' => $plan])</dd>
                            </div>
                        @endif
                        @if ($trial)
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-gray-600">{{ __('Trial use') }}</dt>
                                <dd class="font-medium text-gray-900 text-right">{{ __(':e of :el live events · :r of :rl registrations', ['e' => $trial['events'], 'el' => $trial['event_limit'], 'r' => $trial['registrations'], 'rl' => $trial['registration_limit']]) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-gray-600">{{ __('Add-ons') }}</dt>
                            <dd class="font-medium text-gray-900 text-right">{{ collect($addons)->pluck('name')->map(fn ($n) => __($n))->implode(', ') ?: __('None') }}</dd>
                        </div>
                    </dl>

                    <p class="mt-6 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                        {{ __('Self-serve checkout for Single Event, Club and Circuit is coming to this page. Until it is live, nothing is charged and your trial is not blocked at its limits.') }}
                    </p>
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('Want to go live on a paid plan now, or need Custom? Email') }} <a href="mailto:{{ $contactEmail }}" class="underline hover:text-gray-900">{{ $contactEmail }}</a>.
                        <a href="{{ route('pricing') }}" class="underline hover:text-gray-900">{{ __('Compare plans') }}</a>
                    </p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
