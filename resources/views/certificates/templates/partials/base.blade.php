{{--
    Shared head for every certificate: bundled OFL fonts (resources/certificates/fonts),
    zero page margins and a fixed A4 page box. DomPDF: CSS 2.1, absolute positioning, no gradients.
--}}
@php($fonts = resource_path('certificates/fonts'))
@php($w = $c->portrait() ? 210 : 297)
@php($h = $c->portrait() ? 297 : 210)
<style>
    @font-face { font-family: 'EF Playfair'; font-weight: 700; src: url('{{ $fonts }}/PlayfairDisplay-700.ttf'); }
    @font-face { font-family: 'EF Playfair'; font-style: italic; font-weight: 400; src: url('{{ $fonts }}/PlayfairDisplay-400Italic.ttf'); }
    @font-face { font-family: 'EF Cormorant'; font-weight: 700; src: url('{{ $fonts }}/CormorantGaramond-700.ttf'); }
    @font-face { font-family: 'EF Cormorant'; font-style: italic; font-weight: 500; src: url('{{ $fonts }}/CormorantGaramond-500Italic.ttf'); }
    @font-face { font-family: 'EF Cinzel'; font-weight: 600; src: url('{{ $fonts }}/Cinzel-600.ttf'); }
    @font-face { font-family: 'EF Inter'; font-weight: 400; src: url('{{ $fonts }}/Inter-400.ttf'); }
    @font-face { font-family: 'EF Inter'; font-weight: 500; src: url('{{ $fonts }}/Inter-500.ttf'); }
    @font-face { font-family: 'EF Inter'; font-weight: 700; src: url('{{ $fonts }}/Inter-700.ttf'); }
    @font-face { font-family: 'EF Montserrat'; font-weight: 800; src: url('{{ $fonts }}/Montserrat-800.ttf'); }

    @page { margin: 0; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'EF Inter', sans-serif; font-weight: 400; color: {{ $p->ink }}; }
    .page { position: relative; width: {{ $w }}mm; height: {{ $h }}mm; overflow: hidden; }
    .abs { position: absolute; }
    .bg { position: absolute; top: 0; left: 0; width: {{ $w }}mm; height: {{ $h }}mm; }
    .center { text-align: center; }
    .caps { text-transform: uppercase; }
    .display { font-family: 'EF Playfair', serif; font-weight: 700; }
    .italic { font-family: 'EF Playfair', serif; font-style: italic; font-weight: 400; }
    .classic { font-family: 'EF Cormorant', serif; font-weight: 700; }
    .roman { font-family: 'EF Cinzel', serif; font-weight: 600; }
    .heavy { font-family: 'EF Montserrat', sans-serif; font-weight: 800; }
    .medium { font-weight: 500; }
    .bold { font-weight: 700; }
    .sig { position: absolute; text-align: center; }
    .sig .who { font-family: 'EF Playfair', serif; font-style: italic; font-size: 12.5pt; padding-bottom: 1.8mm; }
    .sig .line { height: 0; border-top: 0.3mm solid; margin: 0 4mm; }
    .sig .role { font-size: 7pt; letter-spacing: 1.6pt; text-transform: uppercase; font-weight: 700; padding-top: 2mm; }
    .verify { position: absolute; text-align: center; }
    .verify img { display: block; margin: 0 auto; }
    .verify .no { font-size: 6.2pt; letter-spacing: 0.6pt; padding-top: 1mm; white-space: nowrap; }
</style>
