{{--
    Places left on a distance. $left (null = open entry), $capacity (null = no bar).
    8px lilac track, indigo fill; marigold fill + warning text at ≤ 10 left; grey when full.
--}}
@props(['left' => null, 'capacity' => null, 'label' => true])
@php
    $full = $left === 0;
    $low = $left !== null && $left > 0 && $left <= 10;
    $pct = $capacity && $left !== null ? max(0, min(100, (int) round(100 * ($capacity - $left) / $capacity))) : null;
    $fill = $full ? 'bg-[#9AA2BA]' : ($low ? 'bg-heat' : 'bg-track');
@endphp
<div {{ $attributes->merge(['class' => 'w-full']) }} data-places-bar data-state="{{ $full ? 'full' : ($low ? 'low' : 'open') }}">
    @if ($pct !== null)
        <div class="h-2 overflow-hidden rounded-full bg-lilac" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="{{ __('Places taken') }}">
            <div class="h-2 rounded-full {{ $fill }}" style="width: {{ $pct }}%"></div>
        </div>
    @endif
    @if ($label)
        <p @class(['mt-2 text-xs font-medium', 'text-warning' => $low, 'text-muted' => ! $low])>
            {{ match (true) {
                $left === null => __('Open entry'),
                $full => __('Full'),
                default => trans_choice(':count place left|:count places left', $left, ['count' => number_format($left)]),
            } }}
        </p>
    @endif
</div>
