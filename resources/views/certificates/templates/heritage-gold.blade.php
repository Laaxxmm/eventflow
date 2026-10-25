{{--
    Heritage Gold: classic medal certificate. Ivory paper with a warm centre glow, metallic foil
    frame with a hairline inner rule and corner ornaments, crest (logo inside a laurel), Cinzel
    credential, Cormorant name, foil rules, two signatures either side of a guilloché medallion.
--}}
@php
    use App\Certificates\Palette;
    $P = $c->portrait();
    $W = $P ? 210 : 297;
    $H = $P ? 297 : 210;
    $foil = $art::foilColour($p->foil);
    $gold = Palette::mix($foil, '#000000', 0.28);
    $title = $p->key === 'brand' ? $p->accent : $gold;
    $paper = $art::paper($p->paper, [[0.5, 0.42, 0.62, Palette::mix($foil, $p->paper, 0.55), 0.55], [0.0, 1.0, 0.45, Palette::mix($foil, $p->paper, 0.7), 0.5], [1.0, 0.0, 0.4, Palette::mix($foil, $p->paper, 0.75), 0.45]], $P);
    $y = fn (float $land, float $port) => $P ? $port : $land;
    $gap = $P ? 6 : 3.5;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('certificates.templates.partials.base')
    <style>
        .rule-h { position: absolute; height: 1.5mm; }
        .rule-v { position: absolute; width: 1.5mm; }
        .inner { position: absolute; top: 13.5mm; left: 13.5mm; right: 13.5mm; bottom: 13.5mm; border: 0.3mm solid {{ Palette::mix($foil, $p->paper, 0.25) }}; }
        .corner { position: absolute; width: 15mm; height: 15mm; }
        .credential { font-size: {{ $P ? 13.5 : 15 }}pt; letter-spacing: 6pt; color: {{ $title }}; }
        .presented { font-size: 12pt; color: {{ $p->muted }}; }
        .name { font-size: {{ $c->nameSize($P ? 44 : 50) }}pt; color: {{ $p->ink }}; line-height: 1.15; }
        .body { font-size: 11pt; color: {{ $p->ink }}; line-height: 1.55; }
        .event { font-size: 16pt; color: {{ $p->ink }}; }
        .facts { font-size: 7.6pt; letter-spacing: 1.6pt; color: {{ $title }}; font-weight: 700; }
        .facts span.v { color: {{ $p->ink }}; letter-spacing: 0.6pt; }
        .meta { font-size: 9pt; color: {{ $p->muted }}; letter-spacing: 0.4pt; }
    </style>
</head>
<body>
<div class="page certificate certificate--heritage-gold">
    <img class="bg" src="{{ $paper }}" alt="">

    {{-- Foil frame --}}
    <img class="rule-h" src="{{ $art::foil($p->foil) }}" style="top: 9mm; left: 9mm; width: {{ $W - 18 }}mm;" alt="">
    <img class="rule-h" src="{{ $art::foil($p->foil) }}" style="bottom: 9mm; left: 9mm; width: {{ $W - 18 }}mm;" alt="">
    <img class="rule-v" src="{{ $art::foil($p->foil, true) }}" style="top: 9mm; left: 9mm; height: {{ $H - 18 }}mm;" alt="">
    <img class="rule-v" src="{{ $art::foil($p->foil, true) }}" style="top: 9mm; right: 9mm; height: {{ $H - 18 }}mm;" alt="">
    <div class="inner"></div>
    <img class="corner" src="{{ $art::corner($foil) }}" style="top: 16mm; left: 16mm;" alt="">
    <img class="corner" src="{{ $art::corner($foil, true) }}" style="top: 16mm; right: 16mm;" alt="">
    <img class="corner" src="{{ $art::corner($foil, false, true) }}" style="bottom: 16mm; left: 16mm;" alt="">
    <img class="corner" src="{{ $art::corner($foil, true, true) }}" style="bottom: 16mm; right: 16mm;" alt="">

    {{-- Crest: logo (or initials) inside a laurel --}}
    <div class="abs center" style="top: {{ $y(19, 26) }}mm; left: 0; width: {{ $W }}mm;">
        <img src="{{ $art::laurel($foil) }}" style="width: 46mm;" alt="">
    </div>
    <table class="abs" style="top: {{ $y(26, 33) }}mm; left: {{ $W / 2 - 15 }}mm; width: 30mm; height: 24mm; border-collapse: collapse;">
        <tr><td style="height: 24mm; text-align: center; vertical-align: middle; padding: 0;">
            @if ($c->logo)
                <img src="{{ $c->logo }}" alt="" style="max-height: 16mm; max-width: 26mm;">
            @else
                <span class="roman" style="font-size: 17pt; color: {{ $title }};">{{ $c->initials() }}</span>
            @endif
        </td></tr>
    </table>

    <div class="abs center" style="top: {{ $y(60, 84) }}mm; left: 24mm; width: {{ $W - 48 }}mm;">
        <div class="roman credential caps">{{ $c->credential }}</div>
        <div style="padding-top: {{ $gap }}mm;">
            <img src="{{ $art::foil($p->foil) }}" style="width: 16mm; height: 0.5mm;" alt="">
            <span style="font-size: 7pt; color: {{ $foil }};">&#9670;</span>
            <img src="{{ $art::foil($p->foil) }}" style="width: 16mm; height: 0.5mm;" alt="">
        </div>
        <div class="italic presented" style="padding-top: {{ $gap }}mm;">{{ __('This certificate is proudly presented to') }}</div>
        <div class="classic name" style="padding-top: 1mm;">{{ $c->participant }}</div>
        <img src="{{ $art::foil($p->foil) }}" style="width: {{ $P ? 120 : 150 }}mm; height: 0.7mm;" alt="">
        <div class="body" style="padding-top: {{ $gap }}mm;">{{ $c->achievement() }}</div>
        <div class="italic event">{{ $c->eventName }}</div>
        @php($facts = array_filter([__('Category') => $c->category, __('Time') => $c->time, __('Rank') => $c->rank, __('Bib') => $c->bib]))
        @if ($facts)
            <div class="facts caps" style="padding-top: {{ $gap }}mm;">
                @foreach ($facts as $label => $value)
                    {{ $label }} <span class="v">{{ $value }}</span>@if (! $loop->last) &nbsp;&middot;&nbsp; @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Signatures either side of the seal --}}
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[0], 'box' => 'left: '.($P ? 22 : 40).'mm; bottom: '.($P ? 46 : 31).'mm; width: '.($P ? 58 : 70).'mm;', 'line' => $p->ink, 'name' => $p->ink, 'role' => $title])
    @include('certificates.templates.partials.signature', ['s' => $c->signatories[1], 'box' => 'right: '.($P ? 22 : 40).'mm; bottom: '.($P ? 46 : 31).'mm; width: '.($P ? 58 : 70).'mm;', 'line' => $p->ink, 'name' => $p->ink, 'role' => $title])

    @if ($c->stamp)
        <div class="abs center" style="bottom: {{ $y(30, 50) }}mm; left: 0; width: {{ $W }}mm;">
            <img data-part="seal" src="{{ $art::medallion($foil, $p->paper, Palette::mix($foil, $p->paper, 0.35), $p->key === 'brand' ? $p->accentFill : $p->accent2) }}" style="width: {{ $P ? 30 : 27 }}mm;" alt="">
        </div>
    @endif
    <div class="abs center meta" style="bottom: {{ $y(22, 38) }}mm; left: 0; width: {{ $W }}mm;">
        {{ $c->date }}@if ($c->city) &nbsp;&middot;&nbsp; {{ $c->city }}@endif &nbsp;&middot;&nbsp; {{ $c->orgName }}
    </div>

    @include('certificates.templates.partials.verify', ['box' => 'right: 19mm; bottom: 18.5mm; width: 18mm;', 'size' => 12.5, 'text' => $p->muted])
</div>
</body>
</html>
