{{-- Classic: cream paper, gold double border with corner diamonds, laurel wreath around the
     organiser logo, serif type, gold seal flanked by date and BIB signature lines. --}}
@php
    $art = \App\View\CertificateArt::class;
    $portrait = $orientation === 'portrait';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        body { background: #fbf8f1; }
        .serif { font-family: 'DejaVu Serif', serif; }
        .frame { position: absolute; top: 8mm; left: 8mm; right: 8mm; bottom: 8mm; border: 2.2mm double {{ $art::GOLD }}; }
        .inner { position: absolute; top: 14mm; left: 14mm; right: 14mm; bottom: 14mm; border: 0.4mm solid {{ $colours['primaryText'] }}; }
        .corner { position: absolute; width: 5mm; height: 5mm; background: {{ $art::GOLD }}; }
        .laurel { position: absolute; top: {{ $portrait ? '26mm' : '16mm' }}; left: 0; right: 0; text-align: center; }
        .laurel img.wreath { width: {{ $portrait ? '70mm' : '58mm' }}; }
        .logo { position: absolute; top: {{ $portrait ? '40mm' : '27mm' }}; left: 0; right: 0; text-align: center; }
        .logo img { max-height: 17mm; max-width: 34mm; }
        .logo .org { font-size: 10pt; font-weight: bold; color: {{ $colours['primaryText'] }}; padding-top: 6mm; }
        .content { position: absolute; top: {{ $portrait ? '112mm' : '66mm' }}; left: 28mm; right: 28mm; }
        .title { font-size: 30pt; letter-spacing: 6pt; color: #2b2416; }
        .subtitle { font-size: 14pt; font-style: italic; color: {{ $art::GOLD_DARK }}; margin-top: 1mm; }
        .presented { font-size: 10pt; letter-spacing: 3pt; text-transform: uppercase; color: #6b5d3f; margin-top: 8mm; }
        .name { font-size: 32pt; color: {{ $colours['primaryText'] }}; margin: 4mm 0 0; }
        .rule { width: 120mm; height: 0.5mm; background: {{ $art::GOLD }}; margin: 3mm auto 4mm; }
        .line { font-size: 12.5pt; color: #2b2416; }
        .bottom { position: absolute; bottom: {{ $portrait ? '30mm' : '22mm' }}; left: 34mm; right: 34mm; height: 40mm; }
        .sig { position: absolute; bottom: 4mm; width: 60mm; text-align: center; font-size: 10pt; color: #2b2416; }
        .sig .val { font-size: 12pt; font-weight: bold; padding-bottom: 1.5mm; border-bottom: 0.3mm solid #2b2416; }
        .sig .lbl { font-size: 8pt; letter-spacing: 2pt; text-transform: uppercase; color: #6b5d3f; padding-top: 1.5mm; }
        .seal { position: absolute; left: 0; right: 0; bottom: 0; text-align: center; }
        .seal img { width: 30mm; }
    </style>
</head>
<body>
    <div class="page certificate certificate--classic">
        <div class="frame"></div>
        <div class="inner"></div>
        <div class="corner" style="top: 11.5mm; left: 11.5mm;"></div>
        <div class="corner" style="top: 11.5mm; right: 11.5mm;"></div>
        <div class="corner" style="bottom: 11.5mm; left: 11.5mm;"></div>
        <div class="corner" style="bottom: 11.5mm; right: 11.5mm;"></div>

        <div class="laurel"><img class="wreath" src="{{ $art::laurel($art::GOLD) }}" alt=""></div>
        <div class="logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="">
            @else
                <div class="org serif">{{ $orgName }}</div>
            @endif
        </div>

        <div class="content center serif">
            <div class="title">CERTIFICATE</div>
            <div class="subtitle">of Completion</div>
            <div class="presented">{{ __('Proudly presented to') }}</div>
            <div class="name">{{ $participant }}</div>
            <div class="rule"></div>
            <div class="line">{{ __('for completing the') }} <strong>{{ $distance }}</strong>@if ($time) {{ __('in') }} <strong>{{ $time }}</strong>@endif</div>
            <div class="line">{{ __('at') }} <strong>{{ $eventName }}</strong>@if ($category) &middot; {{ $category }}@endif</div>
        </div>

        <div class="bottom serif">
            <div class="sig" style="left: 0;"><div class="val">{{ $date }}</div><div class="lbl">{{ __('Date') }}</div></div>
            <div class="seal"><img src="{{ $art::seal($art::GOLD, $colours['secondary']) }}" alt=""></div>
            <div class="sig" style="right: 0;"><div class="val">{{ $bib }}</div><div class="lbl">{{ __('Bib / Pass') }}</div></div>
        </div>
        <div class="foot">{{ $orgName }} &middot; {{ $venue }}</div>
    </div>
</body>
</html>
