{{-- Fixed A4 page for dompdf: 297 x 210 mm landscape, 210 x 297 mm portrait. --}}
@php($w = $orientation === 'portrait' ? 210 : 297)
@php($h = $orientation === 'portrait' ? 297 : 210)
<style>
    @page { margin: 0; }
    html, body { margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; color: #17213a; }
    .page { position: relative; width: {{ $w }}mm; height: {{ $h }}mm; overflow: hidden; }
    .center { text-align: center; }
    .label { font-size: 11pt; letter-spacing: 2px; text-transform: uppercase; }
    .name { font-size: 30pt; font-weight: bold; margin: 6mm 0; }
    .line { font-size: 13pt; line-height: 1.6; }
    .foot { position: absolute; left: 0; right: 0; bottom: 12mm; font-size: 8pt; color: #79849a; text-align: center; }
</style>
