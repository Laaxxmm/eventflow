{{--
    Race pass card (docs/design/daylight-track/PassReady.dc.html): event, runner, distance, race day,
    category, the signed check-in QR and the EF- number. $registration (App\Models\Registration, paid).
--}}
@props(['registration'])
@php
    $r = $registration->loadMissing('event');
    $event = $r->event;
    $distance = collect($event?->config['distances'] ?? [])->firstWhere('code', $r->distance_code)['label'] ?? $r->distance_code;
    $qr = 'data:image/png;base64,'.base64_encode(app(\App\Passes\LocalQr::class)->png(\App\Passes\SignedPassQr::payload((string) $r->pass_no), 340));
    $tz = \App\Landing\ConfigCompiler::TIMEZONE;
@endphp
<div {{ $attributes->merge(['class' => 'ri-float w-full overflow-hidden rounded-[28px] bg-white text-ink']) }} data-pass-card>
    <div class="flex flex-col gap-1 border-b border-dashed border-mist px-[22px] py-5">
        <span class="text-[13px] text-muted">{{ __('Race pass') }}</span>
        <span class="ri-headline text-[22px] leading-tight">{{ $event?->name }}</span>
    </div>
    <dl class="grid grid-cols-2 gap-3.5 px-[22px] py-5 text-[15px]">
        <div><dt class="text-xs text-muted">{{ __('Runner') }}</dt><dd class="font-bold">{{ $r->participant_name }}</dd></div>
        <div><dt class="text-xs text-muted">{{ __('Distance') }}</dt><dd class="font-bold">{{ $distance }}</dd></div>
        @if ($event?->date)
            <div><dt class="text-xs text-muted">{{ __('Race day') }}</dt><dd class="font-bold">{{ $event->date->setTimezone($tz)->format('D j M, g:i a') }}</dd></div>
        @endif
        @if (isset($r->answers['age_band']))
            <div><dt class="text-xs text-muted">{{ __('Category') }}</dt><dd class="font-bold">{{ $r->answers['age_band'] }}</dd></div>
        @endif
    </dl>
    <div class="flex justify-center px-5 pb-[18px] pt-1">
        <img src="{{ $qr }}" alt="{{ __('Pass QR code') }}" width="190" height="190" class="rounded-2xl border border-mist p-2">
    </div>
    <p class="bg-paper p-3.5 text-center text-sm font-bold tracking-[.04em]">{{ $r->pass_no }}</p>
</div>
