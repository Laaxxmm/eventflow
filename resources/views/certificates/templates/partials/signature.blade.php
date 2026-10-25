{{-- One signature slot. $s: ['name' => ?string, 'role' => string]; $box: CSS position/width; $line, $name, $role: colours. --}}
<div class="sig" style="{{ $box }}">
    <div class="who" style="color: {{ $name }};">{!! filled($s['name'] ?? null) ? e($s['name']) : '&nbsp;' !!}</div>
    <div class="line" style="border-color: {{ $line }};"></div>
    <div class="role" style="color: {{ $role }};">{{ $s['role'] ?: '' }}</div>
</div>
