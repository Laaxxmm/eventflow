<x-guest-layout>
    <div class="flex flex-col gap-2">
        <h1 class="ri-headline text-[44px]">{{ __('Reset your password') }}</h1>
        <p class="text-base leading-relaxed text-muted">{{ __('Enter the email you log in with and we will send you a link to choose a new password.') }}</p>
    </div>

    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@organization.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="min-h-[56px] w-full text-[17px]">{{ __('Email me a reset link') }}</x-primary-button>

        <p class="text-center text-sm text-muted">
            {{ __('Remembered it?') }}
            <a href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center font-semibold text-track hover:text-track-deep">{{ __('Log in') }}</a>
        </p>
    </form>
</x-guest-layout>
