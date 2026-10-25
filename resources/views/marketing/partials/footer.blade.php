<footer class="bg-[#0b1020] text-white/70">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 py-12 grid gap-8 sm:grid-cols-3 text-sm">
        <div>
            <p class="text-lg font-extrabold text-white">Event<span class="text-[#d9ff45]">Flow</span></p>
            <p class="mt-2">{{ __('Race registration, passes and check-in for Indian sports events.') }}</p>
            @if (config('plans.brand.by'))
                <p class="mt-2">{{ __('A product by :by.', ['by' => config('plans.brand.by')]) }}</p>
            @endif
        </div>
        <ul class="space-y-2">
            <li><a href="{{ route('pricing') }}" class="hover:text-white">{{ __('Pricing') }}</a></li>
            <li><a href="{{ route('home') }}#features" class="hover:text-white">{{ __('Features') }}</a></li>
            <li><a href="{{ route('login') }}" class="hover:text-white">{{ __('Organiser log in') }}</a></li>
        </ul>
        <ul class="space-y-2">
            <li><a href="mailto:{{ config('plans.brand.contact_email') }}" class="hover:text-white">{{ config('plans.brand.contact_email') }}</a></li>
            <li>{{ __('Made in India · Payments by Razorpay or Cashfree') }}</li>
        </ul>
    </div>
    <p class="border-t border-white/10 py-6 text-center text-xs text-white/50">© {{ now()->year }} {{ \App\Saas\Plans::brandName() }}</p>
</footer>
