{{-- Minimal: editorial. Hairline frame, logo top-left, a very large name set left, thin rules and
     a labelled detail row. Landscape and portrait. --}}
@php
    $portrait = $orientation === 'portrait';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        body { background: #ffffff; }
        .frame { position: absolute; top: 12mm; left: 12mm; right: 12mm; bottom: 12mm; border: 0.3mm solid #c8ccd6; }
        .top { position: absolute; top: 24mm; left: 28mm; right: 28mm; height: 16mm; }
        .top img { max-height: 14mm; max-width: 50mm; }
        .top .org { font-size: 11pt; font-weight: bold; color: #17213a; }
        .top .kind { position: absolute; right: 0; top: 2mm; font-size: 9pt; letter-spacing: 3pt; color: #6b7590; }
        .accent { position: absolute; left: 28mm; top: {{ $portrait ? '104mm' : '64mm' }}; width: 18mm; height: 1.4mm; background: {{ $colours['primary'] }}; }
        .content { position: absolute; top: {{ $portrait ? '112mm' : '72mm' }}; left: 28mm; right: 28mm; }
        .event { font-size: 12pt; letter-spacing: 1pt; color: #6b7590; text-transform: uppercase; }
        .name { font-size: {{ $portrait ? '34pt' : '44pt' }}; line-height: 1.05; margin: 4mm 0 0; color: #0b1020; }
        .details { position: absolute; left: 28mm; top: {{ $portrait ? '244mm' : '157mm' }}; border-collapse: collapse; width: {{ $portrait ? '154mm' : '241mm' }}; }
        .details td { border-top: 0.3mm solid #17213a; padding-top: 3mm; vertical-align: top; }
        .details .k { font-size: 7.5pt; letter-spacing: 2pt; text-transform: uppercase; color: #6b7590; }
        .details .v { font-size: 12pt; color: #0b1020; padding-top: 1mm; }
        .foot { bottom: 17mm; }
    </style>
</head>
<body>
    <div class="page certificate certificate--minimal">
        <div class="frame"></div>
        <div class="top">
            @if ($logo)
                <img src="{{ $logo }}" alt="">
            @else
                <div class="org">{{ $orgName }}</div>
            @endif
            <div class="kind">{{ __('CERTIFICATE OF COMPLETION') }}</div>
        </div>
        <div class="accent"></div>
        <div class="content">
            <div class="event">{{ $eventName }}</div>
            <div class="name">{{ $participant }}</div>
        </div>
        <table class="details">
            <tr>
                <td><div class="k">{{ __('Distance') }}</div><div class="v">{{ $distance }}</div></td>
                @if ($time)<td><div class="k">{{ __('Time') }}</div><div class="v">{{ $time }}</div></td>@endif
                @if ($category && ! $portrait)<td><div class="k">{{ __('Category') }}</div><div class="v">{{ $category }}</div></td>@endif
                <td><div class="k">{{ __('Date') }}</div><div class="v">{{ $date }}</div></td>
                <td><div class="k">{{ __('Bib') }}</div><div class="v">{{ $bib }}</div></td>
            </tr>
        </table>
        <div class="foot">{{ $orgName }} &middot; {{ $venue }}</div>
    </div>
</body>
</html>
