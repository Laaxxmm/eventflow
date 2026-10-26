<x-guest-layout>
    <div class="flex flex-col gap-2">
        <h1 class="ri-headline text-[44px]">{{ __('Choose a new password') }}</h1>
        <p class="text-base leading-relaxed text-muted">{{ __('Use at least 8 characters, then log in with the new password.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="password" name="password" required autocomplete="new-password" placeholder="{{ __('At least 8 characters') }}" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-text-input id="password_confirmation" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="min-h-[56px] w-full text-[17px]">{{ __('Save new password') }}</x-primary-button>
    </form>
</x-guest-layout>
