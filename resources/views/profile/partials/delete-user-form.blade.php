<div class="flex flex-wrap items-center justify-between gap-4">
    <header class="max-w-xl">
        <h3 class="text-lg font-bold text-ink">{{ __('Delete your account') }}</h3>
        <p class="mt-1 text-sm leading-relaxed text-muted">{{ __('This permanently removes your account and can’t be undone. You’ll be asked for your password to confirm.') }}</p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-7">
            @csrf
            @method('delete')

            <h2 class="ri-headline text-[26px] text-ink">{{ __('Delete your account?') }}</h2>

            <p class="mt-2 text-sm leading-relaxed text-muted">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-5">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
                <x-text-input id="password" name="password" type="password" class="block w-full sm:w-3/4" placeholder="{{ __('Password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
                <x-danger-button>{{ __('Delete account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</div>
