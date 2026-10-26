{{-- Mobile: Register is always one thumb away. Hidden on wide screens (each template has its own). --}}
@if ($page->open)
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-ink/10 bg-paper/95 p-3 backdrop-blur sm:hidden">
        <a href="{{ $page->registerUrl() }}" class="flex items-center justify-between gap-3 bg-heat px-5 py-3.5 font-display text-xl font-bold uppercase tracking-[0.06em] text-on-heat">
            <span>{{ __('Register') }}</span>
            <span class="text-base">{{ __('from :price', ['price' => $page->fromPrice()]) }} →</span>
        </a>
    </div>
@endif
