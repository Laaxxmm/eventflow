{{--
    Top bar with logo slot and Register. $page, $dark (light text over a photo / dark hero).
    Becomes a solid white sticky bar on scroll (Alpine) so Register stays reachable on desktop.
--}}
@php
    $dark = $dark ?? true;
@endphp
<header x-data="{ solid: false }" x-init="solid = window.scrollY > 40" @scroll.window="solid = window.scrollY > 40"
        @class(['fixed inset-x-0 z-30 transition-colors duration-300 ease-track', isset($useTemplateUrl) ? 'top-14' : ($page->preview ? 'top-7' : 'top-0')])
        :class="solid ? 'bg-white/95 text-ink shadow-[0_1px_0_#DDE2EE] backdrop-blur-md' : '{{ $dark ? 'text-white' : 'text-ink' }}'">
    <div class="mx-auto flex h-16 max-w-[1280px] items-center justify-between gap-4 px-4 sm:h-20 sm:px-10">
        <a href="#top" class="flex min-w-0 items-center gap-2.5">
            @if ($page->logoUrl())
                <span class="inline-flex items-center rounded-[12px] bg-white px-2.5 py-1.5">@include('landings.partials.logo', ['class' => 'h-8 sm:h-9', 'textClass' => 'ri-headline text-lg text-ink'])</span>
            @else
                <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-white text-sm font-extrabold text-track-text ring-1 ring-inset ring-mist">{{ \Illuminate\Support\Str::of($page->orgName)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</span>
                <span class="ri-headline truncate text-lg sm:text-xl">{{ $page->orgName }}</span>
            @endif
        </a>
        <div class="flex items-center gap-5">
            <span class="hidden text-sm md:inline">{{ $page->date->format('D j M Y') }}</span>
            @if ($page->open)
                <a href="{{ $page->registerUrl() }}" class="inline-flex min-h-[44px] items-center rounded-full bg-heat px-5 text-sm font-bold text-on-heat transition duration-200 ease-track hover:brightness-95">{{ __('Register') }}</a>
            @endif
        </div>
    </div>
</header>
