{{-- Sponsor / partner strip. $page, $dark (bool), $center (bool). Falls back to "Organised by". --}}
@php($sponsors = $page->sponsors())
<div @class(['flex flex-wrap items-center gap-x-10 gap-y-6', 'justify-center text-center' => $center ?? false])>
    @forelse ($sponsors as $sponsor)
        @php($tag = $sponsor['url'] ? 'a' : 'div')
        <{{ $tag }} @if ($sponsor['url']) href="{{ $sponsor['url'] }}" rel="noopener" target="_blank" @endif class="group">
            @if ($sponsor['tier'])
                <p @class(['text-[0.65rem] font-semibold uppercase tracking-[0.18em]', 'text-paper/50' => $dark ?? false, 'text-ink/50' => ! ($dark ?? false)])>{{ $sponsor['tier'] }}</p>
            @endif
            @if ($sponsor['logo'])
                <img src="{{ $sponsor['logo'] }}" alt="{{ $sponsor['name'] }}" class="mt-1 h-8 w-auto {{ ($dark ?? false) ? 'brightness-0 invert' : 'grayscale' }} opacity-80 transition group-hover:opacity-100">
            @else
                <p class="mt-1 font-display text-2xl font-bold uppercase tracking-wide">{{ $sponsor['name'] }}</p>
            @endif
        </{{ $tag }}>
    @empty
        <div>
            <p @class(['text-[0.65rem] font-semibold uppercase tracking-[0.18em]', 'text-paper/50' => $dark ?? false, 'text-ink/50' => ! ($dark ?? false)])>{{ __('Organised by') }}</p>
            <p class="mt-1 font-display text-2xl font-bold uppercase tracking-wide">{{ $page->orgName }}</p>
        </div>
    @endforelse
</div>
