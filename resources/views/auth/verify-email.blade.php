<x-guest-layout>
    <div class="flex flex-col gap-2">
        <h1 class="ri-headline text-[44px]">{{ __('Check your email') }}</h1>
        <p class="text-base leading-relaxed text-muted">{{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <p class="mt-5 rounded-[14px] bg-[#E3F4EE] px-4 py-3 text-sm font-medium text-[#0B4A38]" role="status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </p>
    @endif

    <div class="mt-6 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="min-h-[56px] w-full text-[17px]">{{ __('Resend verification email') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="inline-flex min-h-[44px] items-center text-sm font-semibold text-muted hover:text-ink">{{ __('Log out') }}</button>
        </form>
    </div>
</x-guest-layout>
