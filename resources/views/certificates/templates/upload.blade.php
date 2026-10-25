{{-- Upload: the organiser's full-bleed A4 artwork with text stamped at fixed coordinates from
     config('eventflow.certificates.upload_layout.<orientation>') (mm from the top-left corner;
     x is the horizontal centre of each line, y its vertical centre). Keep those spots clear. --}}
@php
    $slot = function (string $key, float $boxWidth = 200) use ($layout): string {
        $s = $layout[$key];
        $lineMm = ($s['size'] ?? 12) * 0.3528 * 1.25;

        return sprintf('left: %.2fmm; top: %.2fmm; width: %.2fmm; font-size: %spt; line-height: %.2fmm;',
            $s['x'] - $boxWidth / 2, $s['y'] - $lineMm / 2, $boxWidth, $s['size'] ?? 12, $lineMm);
    };
    $logoSlot = $layout['logo'] ?? null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        .bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .stamp { position: absolute; text-align: center; color: #17213a; white-space: nowrap; }
        .stamp.name { font-weight: bold; margin: 0; }
        .logo { position: absolute; text-align: center; }
    </style>
</head>
<body>
    <div class="page certificate certificate--upload">
        <img class="bg" src="{{ $background }}" alt="">
        @if ($logo && $logoSlot)
            <div class="logo" style="left: {{ $logoSlot['x'] - 40 }}mm; top: {{ $logoSlot['y'] - $logoSlot['height'] / 2 }}mm; width: 80mm;">
                <img src="{{ $logo }}" alt="" style="height: {{ $logoSlot['height'] }}mm;">
            </div>
        @endif
        <div class="stamp name" style="{{ $slot('name', $orientation === 'portrait' ? 190 : 260) }}">{{ $participant }}</div>
        <div class="stamp" style="{{ $slot('event') }}">{{ $eventName }}</div>
        <div class="stamp" style="{{ $slot('distance') }}">{{ $distance }}@if ($category) &middot; {{ $category }}@endif</div>
        @if ($time)
            <div class="stamp" style="{{ $slot('time') }}">{{ $time }}</div>
        @endif
        <div class="stamp" style="{{ $slot('date', 80) }}">{{ $date }}</div>
        <div class="stamp" style="{{ $slot('bib', 80) }}">{{ $bib }}</div>
    </div>
</body>
</html>
