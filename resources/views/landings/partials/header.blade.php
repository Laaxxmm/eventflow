{{--
    Top bar with logo slot and Register. $page, $dark (light text over dark/photo), $overlay (absolute over the hero).
    Becomes a solid sticky bar on scroll (Alpine) so Register stays reachable on desktop.
--}}
@php($dark = $dark ?? true)
<header x-data="{ solid: false }" x-init="solid = window.scrollY > 40" @scroll.window="solid = window.scrollY > 40"
        @class(['fixed inset-x-0 z-30 transition-colors duration-300', $page->preview ? 'top-7' : 'top-0'])
        :class="solid ? '{{ $dark ? 'bg-ink/90 text-paper' : 'bg-paper/95 text-ink shadow-sm' }} backdrop-blur' : '{{ $dark ? 'text-paper' : 'text-ink' }}'">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:h-20 sm:px-6 lg:px-8">
        <a href="#top" class="flex items-center">
            @if ($page->logoUrl())
                <span @class(['inline-flex items-center px-2.5 py-1.5', 'bg-paper' => $plate ?? $dark])>@include('landings.partials.logo', ['class' => 'h-8 sm:h-10', 'textClass' => 'font-display text-xl font-bold uppercase text-ink'])</span>
            @else
                <span class="font-display text-2xl font-extrabold uppercase tracking-wide">{{ $page->orgName }}</span>
            @endif
        </a>
        <div class="flex items-center gap-5">
            <span class="hidden text-sm font-medium opacity-80 md:inline">{{ $page->date->format('D j M Y') }}</span>
            @if ($page->open)
                <a href="{{ $page->registerUrl() }}" class="hidden bg-heat px-5 py-2.5 font-display text-lg font-bold uppercase tracking-[0.06em] text-on-heat transition hover:brightness-95 sm:inline-flex">{{ __('Register') }}</a>
            @endif
        </div>
    </div>
</header>
