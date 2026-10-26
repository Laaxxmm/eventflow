<div class="flex flex-col gap-4">
    <header>
        <h3 class="text-lg font-bold text-ink">{{ __('Password') }}</h3>
        <p class="mt-1 text-sm text-muted">{{ __('Use a long password you don’t use anywhere else.') }}</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-4">
        @csrf
        @method('put')

        <div class="grid gap-3.5 md:grid-cols-3">
            <div>
                <x-input-label for="update_password_current_password" :value="__('Current password')" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1.5 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password" :value="__('New password')" />
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1.5 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm new password')" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm font-semibold text-success" role="status">{{ __('Saved.') }}</p>
            @endif
            <x-secondary-button type="submit">{{ __('Change password') }}</x-secondary-button>
        </div>
    </form>
</div>
