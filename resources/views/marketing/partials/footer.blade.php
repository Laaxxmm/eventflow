<footer class="bg-ink text-paper/70">
    {{-- Phones: brand on top, then the three link lists side by side in two columns. --}}
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-x-6 gap-y-10 px-5 py-14 sm:px-6 sm:py-16 lg:grid-cols-[1.4fr_1fr_1fr_1fr] lg:px-8">
        <div class="col-span-2 lg:col-span-1">
            @include('marketing.partials.wordmark', ['class' => 'text-4xl text-paper'])
            <p class="mt-4 max-w-sm">{{ __('Race sites, registrations, QR passes, check-in and certificates for Indian running, cycling and skating organisers.') }}</p>
        </div>
        <ul class="space-y-3 text-sm">
            <li class="text-xs font-semibold uppercase tracking-[0.16em] text-paper/45">{{ __('Product') }}</li>
            <li><a href="{{ route('templates.index') }}" class="hover:text-paper">{{ __('Templates') }}</a></li>
            <li><a href="{{ route('pricing') }}" class="hover:text-paper">{{ __('Pricing') }}</a></li>
            <li><a href="{{ route('register') }}" class="hover:text-paper">{{ __('Start free trial') }}</a></li>
            <li><a href="{{ route('login') }}" class="hover:text-paper">{{ __('Login') }}</a></li>
        </ul>
        <ul class="space-y-3 text-sm">
            <li class="text-xs font-semibold uppercase tracking-[0.16em] text-paper/45">{{ __('Resources') }}</li>
            @if (config('plans.links.docs'))
                <li><a href="{{ config('plans.links.docs') }}" class="hover:text-paper">{{ __('Docs') }}</a></li>
            @endif
            <li><a href="{{ config('plans.links.status') }}" class="hover:text-paper">{{ __('Status') }}</a></li>
            <li><a href="{{ \App\Support\Contact::mailto() }}" class="hover:text-paper">{{ \App\Support\Contact::email() }}</a></li>
        </ul>
        <ul class="space-y-3 text-sm">
            <li class="text-xs font-semibold uppercase tracking-[0.16em] text-paper/45">{{ __('Company') }}</li>
            <li><a href="{{ config('plans.links.indefine') }}" class="hover:text-paper">{{ __('Indefine') }}</a></li>
            @if (config('plans.links.terms') || config('plans.links.privacy'))
                @if (config('plans.links.terms'))<li><a href="{{ config('plans.links.terms') }}" class="hover:text-paper">{{ __('Terms') }}</a></li>@endif
                @if (config('plans.links.privacy'))<li><a href="{{ config('plans.links.privacy') }}" class="hover:text-paper">{{ __('Privacy') }}</a></li>@endif
            @else
                <li><a href="{{ \App\Support\Contact::mailto(__('Terms and privacy')) }}" class="hover:text-paper">{{ __('Terms & privacy') }}</a></li>
            @endif
        </ul>
    </div>
    <div class="border-t border-paper/10">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-xs text-paper/45 sm:px-6 lg:px-8">
            <p>© {{ now()->year }} {{ \App\Saas\Plans::brandName() }} · {{ __('Made in India') }}</p>
            <a href="{{ \App\Support\Contact::url(__('Managed race ops')) }}" data-contact-indefine class="hover:text-paper/80">{{ __('Managed race ops by Indefine') }}</a>
        </div>
    </div>
</footer>
