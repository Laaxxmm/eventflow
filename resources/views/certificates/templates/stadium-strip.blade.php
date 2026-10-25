{{--
    Stadium Strip: sport energy without the clipart. A diagonal brand-colour panel on the right
    (accent stripe, foil edge, fine speed lines) carries the big result numbers and the verify QR;
    the left side holds the logo, FINISHER credential, uppercase name and event. Landscape only.
--}}
@php
    use App\Certificates\Palette;
    $foil = $art::foilColour($p->foil);
    $paper = $art::paper('#fdfdfc', [[0.0, 1.0, 0.55, Palette::mix($p->accentFill, '#ffffff', 0.9), 0.8], [0.45, 0.0, 0.4, Palette::mix($p->accent2Fill, '#ffffff', 0.93), 0.6]], false, 2);
    $panel = $art::panel($p->accentFill, $p->accent2Fill, $foil, Palette::mix($p->accentFill, $p->onAccent, 0.14));
    $on = $p->onAccent;
    $onSoft = Palette::mix($on, $p->accentFill, 0.3);
    $kicker = match (true) {
        str_contains(strtolower($c->credential), 'participation') => __('Participant'),
        str_contains(strtolower($c->credential), 'merit') => __('Merit'),
        default => __('Finisher'),
    };
    $big = array_filter([
        __('Distance') => $c->shortDistance() ?? $c->distance,
        __('Finish time') => $c->time,
        __('Rank') => $c->rank ? '#'.ltrim($c->rank, '#') : null,
    ]);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.base')
    <style>
        .kicker { font-size: 10pt; letter-spacing: 4pt; color: {{ $p->accent }}; }
        .cred { font-size: 8pt; letter-spacing: 2.4pt; font-weight: 700; color: {{ $p->muted }}; }
        .name { font-size: {{ $c->nameSize(40) }}pt; line-height: 1.04; color: {{ $p->ink }}; letter-spacing: 0.3pt; }
        .event { font-size: 15pt; font-weight: 700; color: {{ $p->ink }}; }
        .sub { font-size: 10pt; color: {{ $p->muted }}; }
        .chip { font-size: 7.5pt; letter-spacing: 1.8pt; font-weight: 700; color: {{ $p->accent }}; border: 0.35mm solid {{ Palette::mix($p->accent, '#ffffff', 0.55) }}; padding: 1.2mm 3mm; border-radius: 3mm; }
        .big { text-align: right; }
        .big .v { font-size: 30pt; line-height: 1.05; color: {{ $on }}; white-space: nowrap; }
        .big .k { font-size: 7pt; letter-spacing: 2.4pt; font-weight: 700; color: {{ $onSoft }}; text-transform: uppercase; padding-bottom: 6mm; }
        .sig .line { margin: 0 6mm 0 0; }
    </style>
</head>
<body>
<div class="page certificate certificate--stadium-strip">
    <img class="bg" src="{{ $paper }}" alt="">
    <img class="abs" src="{{ $panel }}" style="top: 0; right: 0; width: 130mm; height: 210mm;" alt="">

    {{-- Left: identity --}}
    <div class="abs" style="top: 18mm; left: 22mm; height: 15mm;">
        @if ($c->logo)
            <img src="{{ $c->logo }}" alt="" style="max-height: 14mm; max-width: 52mm;">
        @else
            <div class="bold" style="font-size: 12pt; color: {{ $p->ink }}; padding-top: 4mm;">{{ $c->orgName }}</div>
        @endif
    </div>

    <div class="abs" style="top: 52mm; left: 22mm; width: 170mm;">
        <div><span class="heavy kicker caps">{{ $kicker }}</span> &nbsp;<span class="cred caps">{{ $c->credential }}</span></div>
        <div class="heavy name caps" style="padding-top: 5mm;">{{ $c->participant }}</div>
        <div style="padding-top: 5mm;">
            <span style="display: inline-block; width: 18mm; height: 1.6mm; background: {{ $p->accentFill }};"></span><span style="display: inline-block; width: 6mm; height: 1.6mm; background: {{ $p->accent2Fill }}; margin-left: 1.5mm;"></span>
        </div>
        <div class="event" style="padding-top: 5mm;">{{ $c->eventName }}</div>
        <div class="sub" style="padding-top: 1.5mm;">{{ $c->date }}@if ($c->venue) &nbsp;&middot;&nbsp; {{ $c->venue }}@endif</div>
        @if ($c->category || $c->bib)
            <div style="padding-top: 6mm;">
                @if ($c->category)<span class="chip caps">{{ $c->category }}</span>&nbsp;&nbsp;@endif
                @if ($c->bib)<span class="chip caps">{{ __('Bib') }} {{ $c->bib }}</span>@endif
            </div>
        @endif
    </div>

    @include('certificates.templates.partials.signature', ['s' => $c->signatories[0], 'box' => 'left: 22mm; bottom: 20mm; width: 54mm; text-align: left;', 'line' => $p->ink, 'name' => $p->ink, 'role' => $p->muted])
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[1], 'box' => 'left: 84mm; bottom: 20mm; width: 54mm; text-align: left;', 'line' => $p->ink, 'name' => $p->ink, 'role' => $p->muted])

    {{-- Right: results on the panel --}}
    <div class="abs big" style="top: 30mm; right: 16mm; width: 70mm;">
        @foreach ($big as $label => $value)
            <div class="heavy v">{{ $value }}</div>
            <div class="k">{{ $label }}</div>
        @endforeach
    </div>
    @if ($c->stamp)
        <img class="abs" data-part="stamp" src="{{ $art::stamp($onSoft) }}" style="right: 46mm; bottom: 17mm; width: 21mm;" alt="">
        <div class="abs center roman" style="right: 46mm; bottom: 24.6mm; width: 21mm; font-size: 8.5pt; color: {{ $onSoft }};">{{ $c->initials() }}</div>
    @endif
    @include('certificates.templates.partials.verify', ['box' => 'right: 14mm; bottom: 15mm; width: 28mm;', 'size' => 16, 'text' => $onSoft, 'plate' => true])
</div>
</body>
</html>
