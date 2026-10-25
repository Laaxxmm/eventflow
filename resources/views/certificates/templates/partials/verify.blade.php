{{-- Verify QR (points at the public verify page) and the EF-CERT number. $box: CSS position; $size: QR mm; $text: colour; $plate: white backing on dark fields. --}}
<div class="verify" style="{{ $box }}">
    @if ($c->qr)
        <div style="{{ ($plate ?? false) ? 'background: #ffffff; padding: 1.4mm; ' : '' }}width: {{ $size }}mm; margin: 0 auto;">
            <img src="{{ $c->qr }}" alt="" style="width: {{ $size }}mm; height: {{ $size }}mm;">
        </div>
    @endif
    <div class="no" style="color: {{ $text }};">{{ $c->certNo }}</div>
</div>
