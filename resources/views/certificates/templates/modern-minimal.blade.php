{{--
    Modern Minimal: asymmetric editorial layout. Full-height accent bar with a foil edge on the
    left, soft tinted corner wash and fine concentric rings on the right, bold Montserrat name,
    a labelled facts grid with hairline dividers, signatures and verify QR along the foot.
--}}
@php
    use App\Certificates\Palette;
    $P = $c->portrait();
    $W = $P ? 210 : 297;
    $H = $P ? 297 : 210;
    $tint = Palette::mix($p->accentFill, '#ffffff', 0.86);
    $paper = $art::paper('#fdfdfc', [[1.0, 0.0, 0.62, $tint, 0.95], [0.0, 1.0, 0.35, Palette::mix($p->accent2Fill, '#ffffff', 0.9), 0.6]], $P, 2);
    $hair = Palette::mix($p->ink, '#ffffff', 0.82);
    $x = 32; // content left edge (mm)
    $facts = array_filter([__('Distance') => $c->distance, __('Category') => $c->category, __('Time') => $c->time, __('Rank') => $c->rank, __('Bib') => $c->bib]);
    $rows = array_chunk($facts, $P ? 3 : 5, true);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.base')
    <style>
        .bar { position: absolute; top: 0; left: 0; width: 10mm; height: {{ $H }}mm; background: {{ $p->accentFill }}; }
        .kicker { font-size: 7.5pt; letter-spacing: 3pt; font-weight: 700; color: {{ $p->muted }}; }
        .label { font-size: 10pt; font-weight: 500; color: {{ $p->accent }}; }
        .name { font-size: {{ $c->nameSize($P ? 42 : 50) }}pt; color: {{ $p->ink }}; line-height: 1.02; letter-spacing: -0.6pt; }
        .event { font-size: {{ $P ? 14 : 16 }}pt; font-weight: 700; color: {{ $p->ink }}; }
        .sub { font-size: 10pt; color: {{ $p->muted }}; }
        .sig .line { margin: 0 8mm 0 0; }
        table.facts { border-collapse: collapse; }
        table.facts td { border-left: 0.3mm solid {{ $hair }}; padding: 0 9mm 0 4mm; vertical-align: top; }
        table.facts td.first { border-left: 0; padding-left: 0; }
        .k { font-size: 6.8pt; letter-spacing: 2pt; font-weight: 700; color: {{ $p->muted }}; text-transform: uppercase; }
        .v { font-size: 13pt; font-weight: 500; color: {{ $p->ink }}; padding-top: 1.2mm; white-space: nowrap; }
    </style>
</head>
<body>
<div class="page certificate certificate--modern-minimal">
    <img class="bg" src="{{ $paper }}" alt="">
    <img class="abs" src="{{ $art::rings(Palette::mix($p->accentFill, '#ffffff', 0.78)) }}" style="top: {{ $P ? 0 : -10 }}mm; right: -20mm; width: {{ $P ? 150 : 170 }}mm;" alt="">
    <div class="bar"></div>
    <img class="abs" src="{{ $art::foil($p->foil, true) }}" style="top: 0; left: 10mm; width: 0.9mm; height: {{ $H }}mm;" alt="">

    {{-- Header: logo left, credential right --}}
    <div class="abs" style="top: 20mm; left: {{ $x }}mm; height: 16mm;">
        @if ($c->logo)
            <img src="{{ $c->logo }}" alt="" style="max-height: 15mm; max-width: 55mm;">
        @else
            <div class="bold" style="font-size: 12pt; color: {{ $p->ink }}; padding-top: 4mm;">{{ $c->orgName }}</div>
        @endif
    </div>
    <div class="abs kicker caps" style="top: 25mm; right: 22mm; text-align: right;">{{ $c->credential }}</div>

    {{-- Recipient --}}
    <div class="abs" style="top: {{ $P ? 96 : 60 }}mm; left: {{ $x }}mm; width: {{ $W - $x - 26 }}mm;">
        <div class="label">{{ __('Awarded to') }}</div>
        <div class="heavy name" style="padding-top: 2.5mm;">{{ $c->participant }}</div>
        <div style="width: 24mm; height: 1.6mm; background: {{ $p->accentFill }}; margin-top: 6mm;"></div>
        <div class="event" style="padding-top: 6mm;">{{ $c->eventName }}</div>
        <div class="sub" style="padding-top: 1.5mm;">{{ $c->date }}@if ($c->venue) &nbsp;&middot;&nbsp; {{ $c->venue }}@endif</div>
    </div>

    @if ($facts)
        <table class="abs facts" style="top: {{ $P ? 206 : 140 }}mm; left: {{ $x }}mm;">
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $label => $value)
                        <td @class(['first' => $loop->first]) style="padding-bottom: 4mm;"><div class="k">{{ $label }}</div><div class="v">{{ $value }}</div></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Foot: signatures, optional stamp, verify --}}
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[0], 'box' => "left: {$x}mm; bottom: 20mm; width: 56mm; text-align: left;", 'line' => $p->ink, 'name' => $p->ink, 'role' => $p->muted])
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[1], 'box' => 'left: '.($x + ($P ? 66 : 72)).'mm; bottom: 20mm; width: 56mm; text-align: left;', 'line' => $p->ink, 'name' => $p->ink, 'role' => $p->muted])
    @if ($c->stamp && ! $P)
        <img class="abs" data-part="stamp" src="{{ $art::stamp(Palette::mix($p->accent, '#ffffff', 0.25)) }}" style="left: {{ $x + 140 }}mm; bottom: 16mm; width: 22mm;" alt="">
        <div class="abs center roman" style="left: {{ $x + 140 }}mm; bottom: 24.4mm; width: 22mm; font-size: 9pt; color: {{ Palette::mix($p->accent, '#ffffff', 0.25) }};">{{ $c->initials() }}</div>
    @endif
    @include('certificates.templates.partials.verify', ['box' => 'right: 20mm; bottom: 17mm; width: 30mm;', 'size' => 17, 'text' => $p->muted])
</div>
</body>
</html>
