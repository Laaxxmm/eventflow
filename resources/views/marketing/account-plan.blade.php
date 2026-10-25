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
                    <p class="mt-1 text-sm text-gray-600">{{ __('Your free trial has started. Verify your email, then create your first event.') }}</p>
                    @unless (auth()->user()->hasVerifiedEmail())
                        <p class="mt-2 text-sm font-medium text-gray-900">{{ __('We sent a verification link to :email.', ['email' => auth()->user()->email]) }}</p>
                    @endunless
                    <div class="mt-4 flex flex-wrap gap-4">
                        <a href="{{ route('organiser.events.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">{{ __('Create an event') }}</a>
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
                                <dt class="text-gray-600">{{ __('Season Pass') }}</dt>
                                <dd class="font-medium text-gray-900">{{ \App\Saas\Plans::inr($plan['season_pass_inr']) }} {{ __('+ :pct% on paid registrations', ['pct' => rtrim(rtrim(number_format($plan['participant_fee_percent'], 1), '0'), '.')]) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-gray-600">{{ __('Add-ons') }}</dt>
                            <dd class="font-medium text-gray-900 text-right">{{ collect($addons)->pluck('name')->map(fn ($n) => __($n))->implode(', ') ?: __('None') }}</dd>
                        </div>
                    </dl>

                    <p class="mt-6 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                        {{ __('Billing connects when Cashfree/Razorpay SaaS subscription is live. Nothing is charged during or after your trial until then; Indefine will contact you to activate your season.') }}
                    </p>
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('To change plan or add-ons, email') }} <a href="mailto:{{ $contactEmail }}" class="underline hover:text-gray-900">{{ $contactEmail }}</a>.
                        <a href="{{ route('pricing') }}" class="underline hover:text-gray-900">{{ __('Compare plans') }}</a>
                    </p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
