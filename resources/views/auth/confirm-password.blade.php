<x-guest-layout>
    <div class="flex flex-col gap-2">
        <h1 class="ri-headline text-[44px]">{{ __('Confirm your password') }}</h1>
        <p class="text-base leading-relaxed text-muted">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 flex flex-col gap-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5 block w-full px-4 py-3.5 text-base" type="password" name="password" required autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="min-h-[56px] w-full text-[17px]">{{ __('Confirm') }}</x-primary-button>
    </form>
</x-guest-layout>
