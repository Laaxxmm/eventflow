{{-- Race partners as small cards. $page, $dark (cards on a dark panel), $center. Falls back to "Organised by". --}}
@php
    $sponsors = $page->sponsors();
    $card = $cardClass ?? (($dark ?? false) ? 'bg-white/10 text-white' : 'bg-paper text-ink');
    $sub = ($dark ?? false) ? 'text-white/70' : 'text-muted';
@endphp
<div @class(['grid grid-cols-2 gap-3 sm:grid-cols-[repeat(auto-fill,minmax(200px,1fr))]', 'text-center' => $center ?? false])>
    @forelse ($sponsors as $sponsor)
        @php
            $tag = $sponsor['url'] ? 'a' : 'div';
        @endphp
        <{{ $tag }} @if ($sponsor['url']) href="{{ $sponsor['url'] }}" rel="noopener" target="_blank" @endif class="flex flex-col gap-1 rounded-[18px] p-[18px] {{ $card }}">
            @if ($sponsor['tier'])
                <span class="text-[13px] {{ $sub }}">{{ $sponsor['tier'] }}</span>
            @endif
            @if ($sponsor['logo'])
                <img src="{{ $sponsor['logo'] }}" alt="{{ $sponsor['name'] }}" @class(['mt-1 h-8 w-auto self-start object-contain', 'self-center' => $center ?? false, 'brightness-0 invert' => $dark ?? false])>
            @else
                <strong>{{ $sponsor['name'] }}</strong>
            @endif
        </{{ $tag }}>
    @empty
        <div class="flex flex-col gap-1 rounded-[18px] p-[18px] {{ $card }}">
            <span class="text-[13px] {{ $sub }}">{{ __('Organised by') }}</span>
            <strong>{{ $page->orgName }}</strong>
        </div>
    @endforelse
</div>
