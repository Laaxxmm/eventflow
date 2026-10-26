<div class="flex flex-col gap-4">
    <header>
        <h3 class="text-lg font-bold text-ink">{{ __('Your details') }}</h3>
        <p class="mt-1 text-sm text-muted">{{ __('Your name and the email you log in with.') }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-4">
        @csrf
        @method('patch')

        <div class="grid gap-3.5 sm:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-[18px] bg-[#FDF0DA] px-4 py-3 text-sm text-[#6B4A12]">
                {{ __('Your email address is unverified.') }}
                <button form="send-verification" class="font-semibold underline underline-offset-2 hover:text-ink">
                    {{ __('Click here to re-send the verification email.') }}
                </button>
            </div>
            @if (session('status') === 'verification-link-sent')
                <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm font-semibold text-[#0B4A38]" role="status">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
            @endif
        @endif

        <div class="flex items-center justify-end gap-4">
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm font-semibold text-success" role="status">{{ __('Saved.') }}</p>
            @endif
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </div>
    </form>
</div>
