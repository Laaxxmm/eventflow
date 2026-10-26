{{-- SaaS nav: Product · Templates · Pricing · Login · Start free trial. --}}
<header class="sticky top-0 z-40 bg-ink/95 text-paper backdrop-blur" x-data="{ open: false }">
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8" aria-label="{{ __('Main') }}">
        <a href="{{ route('home') }}" class="flex items-baseline gap-2">
            @include('marketing.partials.wordmark')
            @if (config('plans.brand.by'))
                <span class="hidden text-xs uppercase tracking-[0.2em] text-paper/50 sm:inline">{{ __('by :by', ['by' => config('plans.brand.by')]) }}</span>
            @endif
        </a>

        <div class="hidden items-center gap-8 text-sm font-medium text-paper/75 md:flex">
            <a href="{{ route('home') }}#product" class="hover:text-paper">{{ __('Product') }}</a>
            <a href="{{ route('templates.index') }}" @class(['hover:text-paper', 'text-paper' => request()->routeIs('templates.*')])>{{ __('Templates') }}</a>
            <a href="{{ route('pricing') }}" @class(['hover:text-paper', 'text-paper' => request()->routeIs('pricing')])>{{ __('Pricing') }}</a>
        </div>

        <div class="flex items-center gap-4 text-sm">
            @auth
                <x-ri.button variant="heat" size="md" :href="route('app')">{{ __('Go to app') }}</x-ri.button>
            @else
                <a href="{{ route('login') }}" class="hidden font-medium text-paper/75 hover:text-paper sm:inline">{{ __('Login') }}</a>
                <x-ri.button variant="heat" size="md" class="whitespace-nowrap" :href="route('register')"><span class="sm:hidden">{{ __('Free trial') }}</span><span class="hidden sm:inline">{{ __('Start free trial') }}</span></x-ri.button>
            @endauth
            <button type="button" class="p-2 md:hidden" @click="open = ! open" :aria-expanded="open" aria-controls="mobile-menu" aria-label="{{ __('Menu') }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </nav>
    <div id="mobile-menu" x-show="open" x-cloak class="border-t border-paper/10 px-4 pb-6 pt-2 md:hidden">
        @foreach ([[route('home').'#product', __('Product')], [route('templates.index'), __('Templates')], [route('pricing'), __('Pricing')], [route('login'), __('Login')]] as [$href, $label])
            <a href="{{ $href }}" class="block py-3 font-display text-2xl font-bold uppercase" @click="open = false">{{ $label }}</a>
        @endforeach
    </div>
</header>
