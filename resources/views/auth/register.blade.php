{{-- Sign up (Signup.dc.html). The plan picker sits in the indigo panel; its radios join the form via form="register-form". --}}
<x-guest-layout>
    <div class="flex flex-col gap-2.5">
        <h1 class="ri-headline text-[44px]">{{ __('Start your free trial') }}</h1>
        <p class="text-base leading-relaxed text-muted">{{ __('Verify your email, then publish. No call with us first, and no card.') }}</p>
        <p class="text-sm text-muted">{{ __('Your :days-day trial includes :events event you can publish and up to :regs test registrations.', ['days' => $trial['days'], 'events' => $trial['events'], 'regs' => $trial['registrations']]) }}</p>
    </div>

    <form id="register-form" method="POST" action="{{ route('register') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        @if ($template)
            <input type="hidden" name="template" value="{{ $template }}">
        @endif

        <div>
            <x-input-label for="organization_name" :value="__('Organisation name')" />
            <x-text-input id="organization_name" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="text" name="organization_name" :value="old('organization_name')" required autofocus autocomplete="organization" placeholder="{{ __('e.g. Sunrise Sports Club') }}" />
            <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2 sm:gap-3.5">
            <div>
                <x-input-label for="name" :value="__('Your name')" />
                <x-text-input id="name" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="text" name="name" :value="old('name')" required autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" :value="__('Phone (optional)')" />
                <x-text-input id="phone" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="tel" name="phone" :value="old('phone')" autocomplete="tel" placeholder="+91" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Work email address')" />
            <x-text-input id="email" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@organization.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2 sm:gap-3.5">
            <div>
                <x-input-label for="password" :value="__('Create password')" />
                <x-text-input id="password" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="password" name="password" required autocomplete="new-password" placeholder="{{ __('At least 8 characters') }}" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                <x-text-input id="password_confirmation" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-primary-button class="min-h-[56px] w-full text-[17px]">{{ __('Create account') }}</x-primary-button>

        <p class="text-center text-sm text-muted">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center font-semibold text-track hover:text-track-deep">{{ __('Log in') }}</a>
        </p>
    </form>

    <x-slot:aside>
        @if ($template)
            @php
                $tpl = config("eventflow.landing_templates.{$template}");
            @endphp
            <div class="flex flex-col gap-2.5">
                <p class="text-[15px] text-[#E4E1FF]">{{ __('Your chosen template') }}</p>
                <div class="relative isolate flex min-h-[220px] flex-col justify-end gap-2.5 overflow-hidden rounded-[24px] p-[26px] text-white shadow-[0_40px_70px_-30px_rgb(18_26_51/.6)]" style="background: {{ $tpl['swatch'] }}" aria-hidden="true">
                    <span class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgb(18_26_51/0)_35%,rgb(18_26_51/.55)_100%)]"></span>
                    <span class="text-xs font-semibold">{{ now()->addMonths(2)->isoFormat('ddd D MMM YYYY') }}</span>
                    <span class="ri-headline text-[30px] leading-none">{{ __('Your race name here') }}</span>
                </div>
                <p class="text-[15px] text-[#E4E1FF]">
                    {{ __(':name will be ready when you create your first event.', ['name' => $tpl['label']]) }}
                    <a href="{{ route('templates.index') }}" class="inline-flex min-h-[44px] items-center font-semibold text-white underline-offset-4 hover:underline">{{ __('Change') }}</a>
                </p>
            </div>
        @else
            <p class="ri-headline max-w-[26rem] text-4xl sm:text-5xl">{{ __('Race morning starts here.') }}</p>
        @endif

        {{-- The plan the trial converts to. Changeable later in Plan & billing. --}}
        <fieldset class="flex flex-col gap-2.5">
            <legend class="mb-2.5 text-[15px] text-[#E4E1FF]">{{ __('Plan after your trial') }}</legend>
            @foreach ($plans as $key => $plan)
                <label class="flex min-h-[56px] cursor-pointer items-center gap-3 rounded-[18px] bg-white/[.12] px-[18px] py-4 transition duration-200 ease-track hover:bg-white/20 has-[:checked]:bg-white has-[:checked]:text-ink">
                    <input type="radio" name="plan" value="{{ $key }}" form="register-form" class="h-[18px] w-[18px] border-white/60 bg-transparent text-track focus:ring-heat" @checked(old('plan', $selectedPlan) === $key)>
                    <span class="flex-1 font-bold">{{ $plan['name'] }}</span>
                    <span>{{ \App\Saas\Plans::inr($plan['price_inr']) }}</span>
                </label>
            @endforeach
            <x-input-error :messages="$errors->get('plan')" class="rounded-xl bg-white px-3 py-2" />
            <p class="mt-1.5 text-sm text-[#E4E1FF]">
                {{ __('No ticket percentage on any plan. Nothing is charged during the trial.') }}
                <a href="{{ route('pricing') }}" class="inline-flex min-h-[44px] items-center font-semibold text-white underline-offset-4 hover:underline">{{ __('Compare plans') }}</a>
            </p>
        </fieldset>
    </x-slot:aside>
</x-guest-layout>
