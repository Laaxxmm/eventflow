<footer class="border-t border-mist">
    <div class="mx-auto grid max-w-[1280px] grid-cols-2 gap-8 px-5 pb-10 pt-14 text-[15px] sm:px-10 lg:grid-cols-4">
        <div class="col-span-2 flex flex-col gap-2.5 lg:col-span-1">
            @include('marketing.partials.wordmark', ['class' => '!text-[22px]'])
            <p class="leading-relaxed text-muted">{{ __('Race sites, registrations, QR passes, check-in and certificates for Indian organisers.') }}</p>
        </div>
        <nav aria-label="{{ __('Product') }}" class="flex flex-col gap-2.5">
            <span class="font-bold">{{ __('Product') }}</span>
            <a href="{{ route('templates.index') }}" class="text-muted hover:text-ink">{{ __('Templates') }}</a>
            <a href="{{ route('pricing') }}" class="text-muted hover:text-ink">{{ __('Pricing') }}</a>
            <a href="{{ route('register') }}" class="text-muted hover:text-ink">{{ __('Start free trial') }}</a>
            <a href="{{ route('login') }}" class="text-muted hover:text-ink">{{ __('Log in') }}</a>
        </nav>
        <nav aria-label="{{ __('Resources') }}" class="flex flex-col gap-2.5">
            <span class="font-bold">{{ __('Resources') }}</span>
            @if (config('plans.links.docs'))
                <a href="{{ config('plans.links.docs') }}" class="text-muted hover:text-ink">{{ __('Docs') }}</a>
            @endif
            <a href="{{ config('plans.links.status') }}" class="text-muted hover:text-ink">{{ __('Status') }}</a>
            <a href="{{ \App\Support\Contact::mailto() }}" class="break-all text-muted hover:text-ink">{{ \App\Support\Contact::email() }}</a>
        </nav>
        <nav aria-label="{{ __('Company') }}" class="flex flex-col gap-2.5">
            <span class="font-bold">{{ __('Company') }}</span>
            <a href="{{ config('plans.links.indefine') }}" class="text-muted hover:text-ink">{{ __('Indefine') }}</a>
            @if (config('plans.links.terms'))<a href="{{ config('plans.links.terms') }}" class="text-muted hover:text-ink">{{ __('Terms') }}</a>@endif
            @if (config('plans.links.privacy'))<a href="{{ config('plans.links.privacy') }}" class="text-muted hover:text-ink">{{ __('Privacy') }}</a>@endif
            @unless (config('plans.links.terms') || config('plans.links.privacy'))
                <a href="{{ \App\Support\Contact::mailto(__('Terms and privacy')) }}" class="text-muted hover:text-ink">{{ __('Terms and privacy') }}</a>
            @endunless
            <a href="{{ \App\Support\Contact::url(__('Managed race ops')) }}" data-contact-indefine class="text-muted hover:text-ink">{{ __('Managed race ops') }}</a>
        </nav>
    </div>
    <p class="mx-auto max-w-[1280px] px-5 pb-10 text-[13px] text-muted sm:px-10">© {{ now()->year }} {{ \App\Saas\Plans::brandName() }}. {{ __('Made in India.') }}</p>
</footer>
