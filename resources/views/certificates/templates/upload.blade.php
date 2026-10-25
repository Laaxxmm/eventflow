<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.page')
    <style>
        .bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        {{-- Fixed text area over the organiser's A4 artwork; leave this band clear in the design. --}}
        .content { position: absolute; top: {{ $orientation === 'portrait' ? '120mm' : '80mm' }}; left: 25mm; right: 25mm; }
    </style>
</head>
<body>
    <div class="page certificate certificate--upload">
        <img class="bg" src="{{ $background }}" alt="">
        <div class="content center">
            <div class="name">{{ $participant }}</div>
            <div class="line">{{ $distance }} &middot; {{ $eventName }}</div>
            <div class="line">{{ $date }}</div>
        </div>
        <div class="foot">{{ $passNo }}</div>
    </div>
</body>
</html>
