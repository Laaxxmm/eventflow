<header class="sticky top-0 z-30 bg-[#0b1020]/95 backdrop-blur text-white">
    <nav class="mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center justify-between gap-4" aria-label="{{ __('Main') }}">
        <a href="{{ route('home') }}" class="flex items-baseline gap-2">
            <span class="text-xl font-extrabold tracking-tight">Event<span class="text-[#d9ff45]">Flow</span></span>
            @if (config('plans.brand.by'))
                <span class="hidden sm:inline text-xs text-white/60">{{ __('by :by', ['by' => config('plans.brand.by')]) }}</span>
            @endif
        </a>

        <div class="hidden md:flex items-center gap-7 text-sm text-white/80">
            <a href="{{ route('home') }}#features" class="hover:text-white">{{ __('Features') }}</a>
            <a href="{{ route('home') }}#how-it-works" class="hover:text-white">{{ __('How it works') }}</a>
            <a href="{{ route('pricing') }}" class="hover:text-white">{{ __('Pricing') }}</a>
            <a href="{{ route('home') }}#faq" class="hover:text-white">{{ __('FAQ') }}</a>
        </div>

        <div class="flex items-center gap-3 text-sm">
            @auth
                <a href="{{ route('app') }}" class="rounded-full bg-[#d9ff45] px-4 py-2 font-bold text-[#0b1020] hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#d9ff45] focus:ring-offset-2 focus:ring-offset-[#0b1020]">{{ __('Go to app') }}</a>
            @else
                <a href="{{ route('login') }}" class="text-white/80 hover:text-white">{{ __('Log in') }}</a>
                <a href="{{ route('pricing') }}" class="rounded-full bg-[#d9ff45] px-4 py-2 font-bold text-[#0b1020] hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#d9ff45] focus:ring-offset-2 focus:ring-offset-[#0b1020]">{{ __('Start free trial') }}</a>
            @endauth
        </div>
    </nav>
</header>
