{{--
    Academic Seal: formal, for coaching clinics, workshops and federations. Cool ivory paper with
    a faint guilloché rosette watermark, a double rule framing a guilloché wave band, Cinzel
    credential, Playfair name, a formal statement line and a foil medallion seal (logo at its
    centre) between two signatures. Landscape and portrait.
--}}
@php
    use App\Certificates\Palette;
    $P = $c->portrait();
    $W = $P ? 210 : 297;
    $H = $P ? 297 : 210;
    $foil = $art::foilColour($p->foil);
    $base = $p->key === 'slate' ? '#f6f7f8' : '#f8f6f0';
    $paper = $art::paper($base, [[0.5, 0.5, 0.6, '#ffffff', 0.7], [0.5, 1.0, 0.5, Palette::mix($p->accent, $base, 0.85), 0.5]], $P, 2);
    $rule = $p->accent;
    $statement = $c->achievement().' <strong>'.e($c->eventName).'</strong>'
        .($c->venue ? ', '.e(__('held at :venue', ['venue' => $c->venue])) : '')
        .($c->date ? ' '.e(__('on :date', ['date' => $c->date])) : '').'.';
    $facts = array_filter([__('Category') => $c->category, __('Time') => $c->time, __('Rank') => $c->rank, __('Bib') => $c->bib]);
    $sealY = $P ? 42 : 20;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.base')
    <style>
        .outer { position: absolute; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm; border: 0.8mm solid {{ $rule }}; }
        .inner { position: absolute; top: 17mm; left: 17mm; right: 17mm; bottom: 17mm; border: 0.25mm solid {{ Palette::mix($rule, $base, 0.35) }}; }
        .credential { font-size: {{ $P ? 19 : 23 }}pt; letter-spacing: 3pt; color: {{ $p->ink }}; line-height: 1.25; }
        .awarded { font-size: 12.5pt; color: {{ $p->muted }}; }
        .name { font-size: {{ $c->nameSize($P ? 38 : 42) }}pt; color: {{ $p->accent }}; line-height: 1.15; }
        .statement { font-size: 11pt; line-height: 1.6; color: {{ $p->ink }}; }
        .facts { font-size: 7.6pt; letter-spacing: 1.6pt; font-weight: 700; color: {{ $p->accent }}; }
        .facts span { color: {{ $p->ink }}; letter-spacing: 0.5pt; }
    </style>
</head>
<body>
<div class="page certificate certificate--academic-seal">
    <img class="bg" src="{{ $paper }}" alt="">
    <img class="abs" src="{{ $art::rosette(Palette::mix($p->accent, $base, 0.93), 600, 0.8, 4) }}" style="top: {{ $P ? 80 : 30 }}mm; left: {{ $W / 2 - ($P ? 80 : 75) }}mm; width: {{ $P ? 160 : 150 }}mm;" alt="">
    <div class="outer"></div>
    <img class="abs" src="{{ $art::waves(Palette::mix($rule, $base, 0.45)) }}" style="top: 12mm; left: 12.5mm; width: {{ $W - 25 }}mm; height: 3.4mm;" alt="">
    <img class="abs" src="{{ $art::waves(Palette::mix($rule, $base, 0.45)) }}" style="bottom: 12mm; left: 12.5mm; width: {{ $W - 25 }}mm; height: 3.4mm;" alt="">
    <div class="inner"></div>

    <div class="abs center" style="top: {{ $P ? 30 : 25 }}mm; left: 0; width: {{ $W }}mm; height: 16mm;">
        @if ($c->logo)
            <img src="{{ $c->logo }}" alt="" style="max-height: 15mm; max-width: 50mm;">
        @else
            <div class="roman" style="font-size: 11pt; letter-spacing: 3pt; color: {{ $p->accent }}; padding-top: 4mm;">{{ mb_strtoupper($c->orgName) }}</div>
        @endif
    </div>

    <div class="abs center" style="top: {{ $P ? 64 : 46 }}mm; left: 28mm; width: {{ $W - 56 }}mm;">
        <div class="roman credential caps">{{ $c->credential }}</div>
        <div class="italic awarded" style="padding-top: {{ $P ? 7 : 4 }}mm;">{{ __('is hereby awarded to') }}</div>
        <div class="display name" style="padding-top: 2mm;">{{ $c->participant }}</div>
        <div style="width: {{ $P ? 90 : 120 }}mm; height: 0; border-top: 0.3mm solid {{ $p->accent }}; margin: 2.5mm auto 0;"></div>
        <div class="statement" style="padding: {{ $P ? 7 : 4.5 }}mm {{ $P ? 4 : 18 }}mm 0;">{!! $statement !!}</div>
        @if ($facts)
            <div class="facts caps" style="padding-top: 3.5mm;">
                @foreach ($facts as $label => $value){{ $label }} <span>{{ $value }}</span>@if (! $loop->last) &nbsp;&middot;&nbsp; @endif @endforeach
            </div>
        @endif
    </div>

    {{-- Seal between the signatures; the logo sits in the medallion when there is one --}}
    <div class="abs center" style="bottom: {{ $sealY }}mm; left: 0; width: {{ $W }}mm;">
        <img data-part="seal" src="{{ $art::medallion($foil, $base, Palette::mix($foil, $base, 0.45), $p->accent2Fill, ! $c->logo) }}" style="width: {{ $P ? 38 : 34 }}mm;" alt="">
    </div>
    @if ($c->logo)
        <table class="abs" style="bottom: {{ $sealY + ($P ? 16.7 : 15) }}mm; left: {{ $W / 2 - 5 }}mm; width: 10mm; height: 10mm; border-collapse: collapse;">
            <tr><td style="height: 10mm; text-align: center; vertical-align: middle; padding: 0;"><img src="{{ $c->logo }}" alt="" style="max-width: 9.5mm; max-height: 9.5mm;"></td></tr>
        </table>
    @endif
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[0], 'box' => 'left: '.($P ? 22 : 46).'mm; bottom: '.($P ? 52 : 30).'mm; width: '.($P ? 56 : 66).'mm;', 'line' => $p->ink, 'name' => $p->ink, 'role' => $p->accent])
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[1], 'box' => 'right: '.($P ? 22 : 46).'mm; bottom: '.($P ? 52 : 30).'mm; width: '.($P ? 56 : 72).'mm;', 'line' => $p->ink, 'name' => $p->ink, 'role' => $p->accent])

    @include('certificates.templates.partials.verify', ['box' => 'right: 22mm; bottom: 20.5mm; width: 24mm;', 'size' => 12.5, 'text' => $p->muted])
    <div class="abs" style="left: 22mm; bottom: 21.5mm; font-size: 7pt; color: {{ $p->muted }}; letter-spacing: 0.4pt;">{{ $c->orgName }}</div>
</div>
</body>
</html>
