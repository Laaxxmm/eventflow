{{--
    Midnight Elite: deep navy field with a soft brand-colour glow and fine gold line work, light
    type, foil rules. Variant "print" (certificate.variant) keeps the layout on an ivory field with
    navy type, for office printers that band large dark areas. Landscape only.
--}}
@php
    use App\Certificates\Palette;
    $dark = $c->variant !== 'print';
    $foil = $art::foilColour($p->foil);
    $field = $dark ? '#0c1424' : '#f9f7f1';
    $text = $dark ? '#f5f1e6' : '#0c1424';
    $soft = $dark ? '#a9b1c2' : '#4c5568';
    $gold = $dark ? Palette::mix($foil, '#ffffff', 0.25) : Palette::mix($foil, '#000000', 0.3);
    $glow = $dark ? Palette::mix($p->accentFill, $field, 0.35) : Palette::mix($p->accentFill, $field, 0.82);
    $paper = $art::paper($field, [[0.1, 0.05, 0.7, $glow, $dark ? 0.55 : 0.8], [0.95, 1.0, 0.55, Palette::mix($foil, $field, $dark ? 0.6 : 0.8), 0.5]], false, 2);
    $lines = $art::lineWork(Palette::mix($foil, $field, $dark ? 0.72 : 0.78), Palette::mix($foil, $field, $dark ? 0.45 : 0.55));
    $facts = array_filter([__('Distance') => $c->distance, __('Category') => $c->category, __('Time') => $c->time, __('Rank') => $c->rank, __('Bib') => $c->bib]);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.base')
    <style>
        body { color: {{ $text }}; }
        .frame { position: absolute; top: 11mm; left: 11mm; right: 11mm; bottom: 11mm; border: 0.3mm solid {{ Palette::mix($foil, $field, 0.45) }}; }
        .credential { font-size: 12.5pt; letter-spacing: 7pt; color: {{ $gold }}; }
        .presented { font-size: 8pt; letter-spacing: 3.2pt; color: {{ $soft }}; font-weight: 500; }
        .name { font-size: {{ $c->nameSize(50) }}pt; color: {{ $text }}; line-height: 1.12; }
        .event { font-size: 16pt; color: {{ $text }}; }
        .body { font-size: 10pt; color: {{ $soft }}; }
        table.facts { border-collapse: collapse; margin: 0 auto; }
        table.facts td { padding: 0 6mm; text-align: center; border-left: 0.3mm solid {{ Palette::mix($foil, $field, 0.6) }}; }
        table.facts td.first { border-left: 0; }
        .k { font-size: 6.6pt; letter-spacing: 2pt; color: {{ $gold }}; font-weight: 700; text-transform: uppercase; }
        .v { font-size: 12pt; color: {{ $text }}; font-weight: 500; padding-top: 1mm; white-space: nowrap; }
        .plate { background: #ffffff; padding: 2mm 3.5mm; border-radius: 2mm; }
    </style>
</head>
<body>
<div class="page certificate certificate--midnight-elite certificate--{{ $dark ? 'screen' : 'print' }}" style="background: {{ $field }};">
    <img class="bg" src="{{ $paper }}" alt="">
    <img class="bg" src="{{ $lines }}" alt="">
    <div class="frame"></div>
    <img class="abs" src="{{ $art::corner($foil) }}" style="top: 7mm; left: 7mm; width: 13mm;" alt="">
    <img class="abs" src="{{ $art::corner($foil, true) }}" style="top: 7mm; right: 7mm; width: 13mm;" alt="">
    <img class="abs" src="{{ $art::corner($foil, false, true) }}" style="bottom: 7mm; left: 7mm; width: 13mm;" alt="">
    <img class="abs" src="{{ $art::corner($foil, true, true) }}" style="bottom: 7mm; right: 7mm; width: 13mm;" alt="">

    {{-- Logo on a light plate so dark logos stay legible --}}
    <div class="abs center" style="top: 20mm; left: 0; width: 297mm;">
        @if ($c->logo)
            <span @class(['plate' => $dark])><img src="{{ $c->logo }}" alt="" style="max-height: 13mm; max-width: 46mm;"></span>
        @else
            <div class="roman" style="font-size: 11pt; letter-spacing: 3pt; color: {{ $gold }};">{{ mb_strtoupper($c->orgName) }}</div>
        @endif
    </div>

    <div class="abs center" style="top: 46mm; left: 30mm; width: 237mm;">
        <div class="roman credential caps">{{ $c->credential }}</div>
        <div style="padding-top: 4mm;"><img src="{{ $art::foil($p->foil) }}" style="width: 22mm; height: 0.5mm;" alt=""></div>
        <div class="presented caps" style="padding-top: 6mm;">{{ __('Presented to') }}</div>
        <div class="display name" style="padding-top: 2mm;">{{ $c->participant }}</div>
        <img src="{{ $art::foil($p->foil) }}" style="width: 130mm; height: 0.6mm;" alt="">
        <div class="body" style="padding-top: 5mm;">{{ $c->achievement() }}</div>
        <div class="italic event" style="padding-top: 1mm;">{{ $c->eventName }}</div>
        <div class="body" style="padding-top: 1mm;">{{ $c->date }}@if ($c->venue) &nbsp;&middot;&nbsp; {{ $c->venue }}@endif</div>
    </div>

    @if ($facts)
        <div class="abs" style="top: 142mm; left: 0; width: 297mm;">
            <table class="facts">
                <tr>
                    @foreach ($facts as $label => $value)
                        <td @class(['first' => $loop->first])><div class="k">{{ $label }}</div><div class="v">{{ $value }}</div></td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    @include('certificates.templates.partials.signature', ['s' => $c->signatories[0], 'box' => 'left: 34mm; bottom: 22mm; width: 64mm;', 'line' => Palette::mix($text, $field, 0.5), 'name' => $text, 'role' => $gold])
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[1], 'box' => 'left: 108mm; bottom: 22mm; width: 64mm;', 'line' => Palette::mix($text, $field, 0.5), 'name' => $text, 'role' => $gold])
    @if ($c->stamp)
        <img class="abs" data-part="stamp" src="{{ $art::stamp($gold) }}" style="left: 190mm; bottom: 17mm; width: 24mm;" alt="">
        <div class="abs center roman" style="left: 190mm; bottom: 26.2mm; width: 24mm; font-size: 9.5pt; color: {{ $gold }};">{{ $c->initials() }}</div>
    @endif
    @include('certificates.templates.partials.verify', ['box' => 'right: 26mm; bottom: 18mm; width: 30mm;', 'size' => 16, 'text' => $soft, 'plate' => $dark])
</div>
</body>
</html>
