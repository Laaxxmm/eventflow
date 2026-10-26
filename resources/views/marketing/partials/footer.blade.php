<footer class="bg-ink text-paper/70">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr_1fr] lg:px-8">
        <div>
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
            <li><a href="{{ route('home') }}#start" class="hover:text-paper">{{ __('Getting started') }}</a></li>
            <li><a href="{{ config('plans.links.status') }}" class="hover:text-paper">{{ __('Status') }}</a></li>
            <li><a href="mailto:{{ config('plans.brand.contact_email') }}" class="hover:text-paper">{{ config('plans.brand.contact_email') }}</a></li>
        </ul>
        <ul class="space-y-3 text-sm">
            <li class="text-xs font-semibold uppercase tracking-[0.16em] text-paper/45">{{ __('Company') }}</li>
            <li><a href="{{ config('plans.links.indefine') }}" class="hover:text-paper">{{ __('Indefine') }}</a></li>
            @if (config('plans.links.terms') || config('plans.links.privacy'))
                @if (config('plans.links.terms'))<li><a href="{{ config('plans.links.terms') }}" class="hover:text-paper">{{ __('Terms') }}</a></li>@endif
                @if (config('plans.links.privacy'))<li><a href="{{ config('plans.links.privacy') }}" class="hover:text-paper">{{ __('Privacy') }}</a></li>@endif
            @else
                <li><a href="mailto:{{ config('plans.brand.contact_email') }}?subject={{ rawurlencode(__('Terms and privacy')) }}" class="hover:text-paper">{{ __('Terms & privacy') }}</a></li>
            @endif
        </ul>
    </div>
    <div class="border-t border-paper/10">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-xs text-paper/45 sm:px-6 lg:px-8">
            <p>© {{ now()->year }} {{ \App\Saas\Plans::brandName() }} · {{ __('Made in India') }}</p>
            <a href="mailto:{{ config('plans.brand.contact_email') }}?subject={{ rawurlencode(__('Managed race ops')) }}" class="hover:text-paper/80">{{ __('Managed race ops by Indefine') }}</a>
        </div>
    </div>
</footer>
