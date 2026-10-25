{{-- Velocity: diagonal speed stripes down the right edge, chequered finish-line band, heavy italic
     type and a big distance figure. Running and cycling packs default to it. Landscape only. --}}
@php
    $art = \App\View\CertificateArt::class;
    $kind = match ($sport ?? null) {
        'cycling' => __('RIDER'),
        'skating' => __('SKATER'),
        default => __('FINISHER'),
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        body { background: #ffffff; }
        .stripes { position: absolute; top: 0; right: 0; width: 105mm; height: 210mm; }
        .checkers { position: absolute; left: 0; bottom: 0; width: 297mm; height: 9.9mm; }
        .logo { position: absolute; top: 18mm; left: 22mm; }
        .logo img { max-height: 15mm; max-width: 50mm; }
        .logo .org { font-size: 12pt; font-weight: bold; color: #0b1020; }
        .kind { position: absolute; top: 44mm; left: 22mm; font-size: 46pt; font-weight: bold; font-style: italic; color: #0b1020; letter-spacing: 1pt; }
        .bar { position: absolute; top: 70mm; left: 22mm; width: 40mm; height: 2mm; background: {{ $colours['secondary'] }}; }
        .content { position: absolute; top: 80mm; left: 22mm; right: 110mm; }
        .label { color: #6b7590; font-size: 9pt; letter-spacing: 3pt; }
        .name { font-size: 32pt; margin: 2mm 0 3mm; color: {{ $colours['primaryText'] }}; line-height: 1.1; }
        .event { font-size: 13pt; color: #17213a; }
        .figures { position: absolute; left: 22mm; top: 136mm; border-collapse: collapse; }
        .meta { position: absolute; left: 22mm; top: 166mm; font-size: 10pt; color: #17213a; }
        .meta span { font-size: 7.5pt; letter-spacing: 2pt; color: #6b7590; }
        .figures td { padding-right: 12mm; vertical-align: bottom; }
        .figures .big { font-size: 26pt; font-weight: bold; font-style: italic; color: #0b1020; }
        .figures .k { font-size: 7.5pt; letter-spacing: 2pt; text-transform: uppercase; color: #6b7590; }
        .figures .v { font-size: 12pt; font-weight: bold; color: #0b1020; }
        .foot { bottom: 16mm; left: 22mm; right: 110mm; text-align: left; }
    </style>
</head>
<body>
    <div class="page certificate certificate--velocity">
        <img class="stripes" src="{{ $art::stripes($colours['primary'], $colours['secondary']) }}" alt="">
        <img class="checkers" src="{{ $art::checkers('#0b1020', 60, 2) }}" alt="">
        <div class="logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="">
            @else
                <div class="org">{{ $orgName }}</div>
            @endif
        </div>
        <div class="kind">{{ $kind }}</div>
        <div class="bar"></div>
        <div class="content">
            <div class="label">{{ __('THIS CERTIFIES THAT') }}</div>
            <div class="name">{{ $participant }}</div>
            <div class="event">{{ __('completed') }} {{ $eventName }}@if ($category) &middot; {{ $category }}@endif</div>
        </div>
        <table class="figures">
            <tr>
                <td><div class="k">{{ __('Distance') }}</div><div class="big">{{ $distance }}</div></td>
                @if ($time)<td><div class="k">{{ __('Time') }}</div><div class="big">{{ $time }}</div></td>@endif
            </tr>
        </table>
        <div class="meta"><span>{{ __('DATE') }}</span>&nbsp; {{ $date }} &nbsp;&nbsp;&nbsp; <span>{{ __('BIB') }}</span>&nbsp; {{ $bib }}</div>
        <div class="foot">{{ $orgName }} &middot; {{ $venue }}</div>
    </div>
</body>
</html>
