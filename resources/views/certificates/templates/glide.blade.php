{{-- Glide: ice-blue paper with faint rink lines, a star badge in the secondary colour, serif italic
     name and soft rounded detail chips. Skating pack default. Landscape and portrait. --}}
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
        body { background: #eef6fb; }
        .rink { position: absolute; left: {{ $portrait ? '-45mm' : '28mm' }}; top: {{ $portrait ? '70mm' : '18mm' }}; width: {{ $portrait ? '300mm' : '240mm' }}; }
        .edge { position: absolute; top: 0; left: 0; right: 0; height: 3mm; background: {{ $colours['primary'] }}; }
        .edge2 { position: absolute; bottom: 0; left: 0; right: 0; height: 3mm; background: {{ $colours['secondary'] }}; }
        .logo { position: absolute; top: 14mm; left: 0; right: 0; text-align: center; }
        .logo img { max-height: 14mm; max-width: 48mm; }
        .logo .org { font-size: 11pt; font-weight: bold; color: #17213a; }
        .badge { position: absolute; top: {{ $portrait ? '44mm' : '34mm' }}; left: 0; right: 0; text-align: center; }
        .badge img { width: {{ $portrait ? '36mm' : '30mm' }}; }
        .content { position: absolute; top: {{ $portrait ? '96mm' : '74mm' }}; left: 20mm; right: 20mm; }
        .label { color: #4b6b85; font-size: 9pt; letter-spacing: 3pt; }
        .name { font-family: 'DejaVu Serif', serif; font-style: italic; font-weight: bold; font-size: {{ $portrait ? '30pt' : '36pt' }}; color: {{ $colours['primaryText'] }}; margin: 3mm 0; }
        .event { font-size: 13pt; color: #17213a; }
        .chips { position: absolute; left: 0; right: 0; top: {{ $portrait ? '232mm' : '160mm' }}; text-align: center; }
        .chip { display: inline-block; margin: 0 1.5mm 2.5mm; padding: 2mm 5mm; background: #ffffff; border: 0.3mm solid #b9d3e4; border-radius: 4mm; font-size: 10.5pt; color: #17213a; }
        .chip span { font-size: 7pt; letter-spacing: 1.5pt; color: #4b6b85; text-transform: uppercase; }
        .foot { bottom: 10mm; color: #4b6b85; }
    </style>
</head>
<body>
    <div class="page certificate certificate--glide">
        <img class="rink" src="{{ $art::rink('#d3e5f0') }}" alt="">
        <div class="edge"></div>
        <div class="edge2"></div>
        <div class="logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="">
            @else
                <div class="org">{{ $orgName }}</div>
            @endif
        </div>
        <div class="badge"><img src="{{ $art::star($colours['secondary']) }}" alt=""></div>
        <div class="content center">
            <div class="label">{{ __('CERTIFICATE OF ACHIEVEMENT') }}</div>
            <div class="name">{{ $participant }}</div>
            <div class="event">{{ $eventName }}</div>
        </div>
        <div class="chips">
            <span class="chip"><span>{{ __('Distance') }}</span>&nbsp; {{ $distance }}</span>
            @if ($category)<span class="chip"><span>{{ __('Category') }}</span>&nbsp; {{ $category }}</span>@endif
            @if ($time)<span class="chip"><span>{{ __('Time') }}</span>&nbsp; {{ $time }}</span>@endif
            <span class="chip"><span>{{ __('Date') }}</span>&nbsp; {{ $date }}</span>
            <span class="chip"><span>{{ __('Bib') }}</span>&nbsp; {{ $bib }}</span>
        </div>
        <div class="foot">{{ $orgName }} &middot; {{ $venue }}</div>
    </div>
</body>
</html>
