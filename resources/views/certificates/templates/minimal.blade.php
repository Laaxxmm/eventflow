<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        .content { position: absolute; top: {{ $orientation === 'portrait' ? '95mm' : '55mm' }}; left: 30mm; right: 30mm; }
        .rule { width: 40mm; height: 1px; background: {{ $colours['primary'] }}; margin: 8mm auto; }
    </style>
</head>
<body>
    <div class="page certificate certificate--minimal">
        <div class="content center">
            <div class="label">{{ $eventName }}</div>
            <div class="name">{{ $participant }}</div>
            <div class="line">{{ $distance }}</div>
            <div class="rule"></div>
            <div class="line">{{ $date }} &middot; {{ $orgName }}</div>
        </div>
        <div class="foot">{{ $passNo }}</div>
    </div>
</body>
</html>
