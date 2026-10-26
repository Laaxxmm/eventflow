<x-guest-layout>
    <h1 class="ri-headline mb-8 text-6xl">{{ __('Start your season') }}</h1>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Create your organiser account. We will email you a link to verify your address before you can publish events.') }}
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Plan (free trial; billing is not connected yet) -->
        <fieldset class="mb-6">
            <legend class="block font-medium text-sm text-gray-700">{{ __('Plan') }}</legend>
            <div class="mt-2 space-y-2">
                @foreach ($plans as $key => $plan)
                    <label class="flex items-start gap-3 rounded-md border border-gray-300 p-3 cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                        <input type="radio" name="plan" value="{{ $key }}" class="mt-1 text-indigo-600 focus:ring-indigo-500" @checked(old('plan', $selectedPlan) === $key)>
                        <span class="flex-1 text-sm">
                            <span class="block font-semibold text-gray-900">{{ $plan['name'] }}</span>
                            <span class="block text-gray-600">{{ \App\Saas\Plans::inr($plan['price_inr']) }} / {{ __($plan['term_label']) }} · {{ \App\Saas\Plans::limitsLabel($plan) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('plan')" class="mt-2" />

            <p class="mt-4 text-sm font-medium text-gray-700">{{ __('Add-ons (optional)') }}</p>
            <div class="mt-2 space-y-1">
                @foreach ($addons as $key => $addon)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="addons[]" value="{{ $key }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(in_array($key, old('addons', $selectedAddons), true))>
                        <span class="flex-1">{{ __($addon['name']) }}</span>
                        <span class="text-gray-500">{{ \App\Saas\Plans::inr($addon['price_inr']) ? '+'.\App\Saas\Plans::inr($addon['price_inr']) : __('Paid add-on') }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('addons.*')" class="mt-2" />

            <p class="mt-3 text-xs text-gray-500">
                {{ __(':days-day free trial, no card needed. Billing connects when Cashfree/Razorpay SaaS subscription is live.', ['days' => config('plans.trial_days')]) }}
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
