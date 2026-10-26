<x-guest-layout>
    <div class="flex flex-col gap-2">
        <h1 class="ri-headline text-[44px]">{{ __('Welcome back') }}</h1>
        <p class="text-base text-muted">{{ __('Organisers, staff and check-in volunteers all log in here.') }}</p>
    </div>

    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 flex flex-col gap-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@organization.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-4">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="-my-3 py-3 text-sm font-medium text-track hover:text-track-deep">{{ __('Forgot password?') }}</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex min-h-[44px] cursor-pointer items-center gap-2.5 text-sm">
            <input id="remember_me" type="checkbox" name="remember" class="h-[18px] w-[18px] rounded border-[#C5CBDB] text-track focus:ring-track">
            {{ __('Keep me logged in on this device') }}
        </label>

        <x-primary-button class="min-h-[56px] w-full text-[17px]">{{ __('Log in') }}</x-primary-button>

        <p class="text-center text-sm text-muted">
            {{ __('New to :brand?', ['brand' => config('plans.brand.name')]) }}
            <a href="{{ route('register') }}" class="inline-flex min-h-[44px] items-center font-semibold text-track hover:text-track-deep">{{ __('Start a free trial') }}</a>
        </p>
    </form>
</x-guest-layout>
