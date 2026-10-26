<x-guest-layout>
    <h1 class="ri-headline mb-3 text-6xl">{{ __('Start your free trial') }}</h1>
    <p class="mb-6 text-sm text-ink/70">
        {{ __(':days days · :events event you can publish · up to :regs test registrations · no card.', ['days' => $trial['days'], 'events' => $trial['events'], 'regs' => $trial['registrations']]) }}
        {{ __('Verify your email, then publish — no call with us first.') }}
    </p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        @if ($template)
            <input type="hidden" name="template" value="{{ $template }}">
            <p class="mb-6 border-l-4 border-heat bg-mist px-4 py-3 text-sm">
                {{ __('Template: :name. It will be preselected when you create your event.', ['name' => config("eventflow.landing_templates.{$template}.label")]) }}
            </p>
        @endif

        <!-- The plan the trial converts to. Changeable later in Plan & billing. -->
        <fieldset class="mb-6">
            <legend class="block text-sm font-medium text-ink">{{ __('Plan after your trial') }}</legend>
            <div class="mt-2 space-y-2">
                @foreach ($plans as $key => $plan)
                    <label class="flex cursor-pointer items-start gap-3 border-2 border-ink/15 p-3 has-[:checked]:border-ink">
                        <input type="radio" name="plan" value="{{ $key }}" class="mt-1 text-ink focus:ring-ink" @checked(old('plan', $selectedPlan) === $key)>
                        <span class="flex-1 text-sm">
                            <span class="flex justify-between gap-3 font-semibold text-ink"><span>{{ $plan['name'] }}</span><span>{{ \App\Saas\Plans::inr($plan['price_inr']) }}</span></span>
                            <span class="block text-ink/60">{{ \App\Saas\Plans::limitsLabel($plan) }} · {{ __($plan['term_label']) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('plan')" class="mt-2" />
            <p class="mt-3 text-xs text-ink/55">
                {{ __('No ticket percentage on any plan. Nothing is charged during the trial.') }}
                <a href="{{ route('pricing') }}" class="underline">{{ __('Compare plans') }}</a>
            </p>
        </fieldset>

        <!-- Organization -->
        <div>
            <x-input-label for="organization_name" :value="__('Organization name')" />
            <x-text-input id="organization_name" class="block mt-1 w-full" type="text" name="organization_name" :value="old('organization_name')" required autofocus autocomplete="organization" placeholder="{{ __('e.g. Sunrise Sports Club') }}" />
            <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
        </div>

        <!-- Name -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Your name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Work email address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@organization.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone (optional)')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" autocomplete="tel" placeholder="{{ __('Enter your phone number') }}" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Create password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="{{ __('At least 8 characters') }}" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password"
                            placeholder="{{ __('Enter the password again') }}" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Create account') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
