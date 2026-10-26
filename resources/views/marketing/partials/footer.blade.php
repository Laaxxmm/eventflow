<footer class="bg-ink text-paper/70">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr] lg:px-8">
        <div>
            @include('marketing.partials.wordmark', ['class' => 'text-4xl text-paper'])
            <p class="mt-4 max-w-sm">{{ __('Race registration, passes, check-in and certificates for Indian running, cycling and skating events.') }}</p>
            @if (config('plans.brand.by'))
                <p class="mt-2 text-sm">{{ __('A product by :by.', ['by' => config('plans.brand.by')]) }}</p>
            @endif
        </div>
        <ul class="space-y-3 text-sm">
            <li><a href="{{ route('pricing') }}" class="hover:text-paper">{{ __('Pricing') }}</a></li>
            <li><a href="{{ route('home') }}#race-day" class="hover:text-paper">{{ __('Race-day checklist') }}</a></li>
            <li><a href="{{ route('home') }}#pages" class="hover:text-paper">{{ __('Event page designs') }}</a></li>
            <li><a href="{{ route('login') }}" class="hover:text-paper">{{ __('Organiser log in') }}</a></li>
        </ul>
        <ul class="space-y-3 text-sm">
            <li><a href="mailto:{{ config('plans.brand.contact_email') }}" class="hover:text-paper">{{ config('plans.brand.contact_email') }}</a></li>
            <li>{{ __('Payments by Razorpay or Cashfree, into your own account') }}</li>
            <li>{{ __('Made in India') }}</li>
        </ul>
    </div>
    <div class="border-t border-paper/10">
        <p class="mx-auto max-w-7xl px-4 py-6 text-xs text-paper/45 sm:px-6 lg:px-8">© {{ now()->year }} {{ \App\Saas\Plans::brandName() }}</p>
    </div>
</footer>
