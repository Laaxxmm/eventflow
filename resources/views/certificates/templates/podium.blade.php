<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        .band { position: absolute; top: 0; left: 0; right: 0; height: 52mm; background: {{ $colours['primary'] }}; }
        .band .org { color: {{ $colours['onPrimary'] }}; font-weight: bold; font-size: 13pt; padding-top: 14mm; }
        .band .title { color: {{ $colours['onPrimary'] }}; font-size: 28pt; font-weight: bold; margin-top: 3mm; }
        .stripe { position: absolute; top: 52mm; left: 0; right: 0; height: 4mm; background: {{ $colours['secondary'] }}; }
        .content { position: absolute; top: 78mm; left: 25mm; right: 25mm; }
        .name { color: {{ $colours['primaryText'] }}; }
    </style>
</head>
<body>
    <div class="page certificate certificate--podium">
        <div class="band center">
            <div class="org">{{ $orgName }}</div>
            <div class="title">Finisher</div>
        </div>
        <div class="stripe"></div>
        <div class="content center">
            <div class="label">Awarded to</div>
            <div class="name">{{ $participant }}</div>
            <div class="line">{{ $distance }} &middot; {{ $eventName }}</div>
            <div class="line">{{ $date }} &middot; {{ $venue }}</div>
        </div>
        <div class="foot">{{ $passNo }}</div>
    </div>
</body>
</html>
