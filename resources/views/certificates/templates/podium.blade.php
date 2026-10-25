{{-- Podium: brand-colour band with a gold medal hanging from it, bold FINISHER title, the name on
     a clean white field and a stat strip (distance, time, date, BIB). Landscape only. --}}
@php
    $art = \App\View\CertificateArt::class;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        body { background: #ffffff; }
        .band { position: absolute; top: 0; left: 0; right: 0; height: 58mm; background: {{ $colours['primary'] }}; }
        .band .org { position: absolute; top: 12mm; left: 22mm; color: {{ $colours['onPrimary'] }}; font-weight: bold; font-size: 12pt; letter-spacing: 1pt; }
        .band .org img { max-height: 16mm; max-width: 50mm; }
        .band .title { color: {{ $colours['onPrimary'] }}; position: absolute; top: 30mm; left: 22mm; font-size: 40pt; font-weight: bold; letter-spacing: 4pt; }
        .stripe { position: absolute; top: 58mm; left: 0; right: 0; height: 4mm; background: {{ $colours['secondary'] }}; }
        .medal { position: absolute; top: 0; right: 30mm; width: 56mm; }
        .content { position: absolute; top: 80mm; left: 22mm; right: 100mm; }
        .label { color: #6b7590; font-size: 10pt; letter-spacing: 3pt; }
        .name { color: {{ $colours['primaryText'] }}; }
        .name { font-size: 36pt; margin: 3mm 0 2mm; line-height: 1.1; }
        .event { font-size: 14pt; color: #17213a; }
        .stats { position: absolute; left: 22mm; top: 158mm; border-collapse: collapse; width: 253mm; }
        .stats td { border-top: 0.6mm solid #17213a; padding: 3mm 4mm 0 0; vertical-align: top; width: 25%; }
        .stats .k { font-size: 8pt; letter-spacing: 2pt; text-transform: uppercase; color: #6b7590; }
        .stats .v { font-size: 15pt; font-weight: bold; color: #17213a; padding-top: 1mm; }
        .foot { bottom: 9mm; }
    </style>
</head>
<body>
    <div class="page certificate certificate--podium">
        <div class="band">
            <div class="org">
                @if ($logo)
                    <img src="{{ $logo }}" alt="">
                @else
                    {{ $orgName }}
                @endif
            </div>
            <div class="title">FINISHER</div>
        </div>
        <div class="stripe"></div>
        <img class="medal" src="{{ $art::medal($colours['secondary'], $colours['primaryText']) }}" alt="">

        <div class="content">
            <div class="label">{{ __('AWARDED TO') }}</div>
            <div class="name">{{ $participant }}</div>
            <div class="event">{{ $eventName }}@if ($category) &middot; {{ $category }}@endif</div>
        </div>

        <table class="stats">
            <tr>
                <td><div class="k">{{ __('Distance') }}</div><div class="v">{{ $distance }}</div></td>
                <td><div class="k">{{ __('Time') }}</div><div class="v">{{ $time ?: '—' }}</div></td>
                <td><div class="k">{{ __('Date') }}</div><div class="v">{{ $date }}</div></td>
                <td><div class="k">{{ __('Bib') }}</div><div class="v">{{ $bib }}</div></td>
            </tr>
        </table>
        <div class="foot">{{ $orgName }} &middot; {{ $venue }} &middot; {{ $passNo }}</div>
    </div>
</body>
</html>
