<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        .frame { position: absolute; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm; border: 3px solid {{ $colours['primary'] }}; }
        .inner { position: absolute; top: 13mm; left: 13mm; right: 13mm; bottom: 13mm; border: 1px solid {{ $colours['secondary'] }}; }
        .content { position: absolute; top: {{ $orientation === 'portrait' ? '70mm' : '38mm' }}; left: 25mm; right: 25mm; }
        .org { color: {{ $colours['secondaryText'] }}; font-weight: bold; font-size: 12pt; }
        .title { color: {{ $colours['primaryText'] }}; font-size: 26pt; font-weight: bold; margin: 4mm 0 8mm; }
    </style>
</head>
<body>
    <div class="page certificate certificate--classic">
        <div class="frame"></div>
        <div class="inner"></div>
        <div class="content center">
            <div class="org">{{ $orgName }}</div>
            <div class="title">Certificate of Completion</div>
            <div class="label">This is to certify that</div>
            <div class="name">{{ $participant }}</div>
            <div class="line">completed the <strong>{{ $distance }}</strong> at <strong>{{ $eventName }}</strong></div>
            <div class="line">on {{ $date }} &middot; {{ $venue }}</div>
        </div>
        <div class="foot">{{ $passNo }}</div>
    </div>
</body>
</html>
