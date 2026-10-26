{{-- SaaS nav (Daylight Track): wordmark · Templates · Pricing · Log in · Start free trial (navy pill). --}}
<header class="relative z-40" x-data="{ open: false }">
    <nav class="mx-auto flex max-w-[1280px] flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-10 sm:py-6" aria-label="{{ __('Main') }}">
        <a href="{{ route('home') }}" class="flex items-baseline gap-2.5">
            @include('marketing.partials.wordmark')
            @if (config('plans.brand.by'))
                <span class="hidden text-[13px] text-muted sm:inline">{{ __('by :by', ['by' => config('plans.brand.by')]) }}</span>
            @endif
        </a>

        <div class="flex items-center gap-7 text-[15px] font-medium">
            <a href="{{ route('templates.index') }}" @class(['hidden hover:text-track md:inline', 'text-track' => request()->routeIs('templates.*')]) @if (request()->routeIs('templates.*')) aria-current="page" @endif>{{ __('Templates') }}</a>
            <a href="{{ route('pricing') }}" @class(['hidden hover:text-track md:inline', 'text-track' => request()->routeIs('pricing')]) @if (request()->routeIs('pricing')) aria-current="page" @endif>{{ __('Pricing') }}</a>
            @auth
                <x-ri.button variant="ink" size="sm" :href="route('app')">{{ __('Go to app') }}</x-ri.button>
            @else
                <a href="{{ route('login') }}" class="hidden hover:text-track sm:inline">{{ __('Log in') }}</a>
                <x-ri.button variant="ink" size="sm" class="whitespace-nowrap" :href="route('register')">{{ __('Start free trial') }}</x-ri.button>
            @endauth
            <button type="button" class="-me-2 flex h-11 w-11 items-center justify-center md:hidden" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="mobile-menu" aria-label="{{ __('Menu') }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </nav>
    <div id="mobile-menu" x-show="open" x-cloak class="mx-4 rounded-[22px] bg-white p-3 md:hidden">
        @foreach ([[route('templates.index'), __('Templates')], [route('pricing'), __('Pricing')], [route('login'), __('Log in')]] as [$href, $label])
            <a href="{{ $href }}" class="flex min-h-[48px] items-center rounded-2xl px-4 text-lg font-semibold hover:bg-paper" @click="open = false">{{ $label }}</a>
        @endforeach
    </div>
</header>
