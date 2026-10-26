{{--
    Check-in (Daylight Track, docs/design/daylight-track/Checkin.dc.html): Scan QR / Type pass number tabs, a dark
    camera view with a marigold frame and scan line (still under reduced motion), and three result cards:
    checked in (green), already checked in (amber, first time), not recognised (red). resources/js/checkin.js
    fills the same cards after a camera scan.
--}}
@php
    $current = null;
    if ($result) {
        $current = ! ($result['ok'] ?? false) ? 'bad' : (($result['duplicate'] ?? false) ? 'dup' : 'ok');
    }
    $name = $result['participant_name'] ?? '';
    $pass = $result['pass_no'] ?? '';
    $time = $result['checked_in_at'] ?? '';
    $states = [
        'ok' => [
            'box' => 'bg-[#E3F4EE]', 'dot' => 'bg-success', 'title' => 'text-[#0B4A38]', 'text' => 'text-[#2D5A4C]',
            'icon' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>',
            'tTitle' => __('Checked in: :name'), 'tLine1' => ':pass', 'tLine2' => __(':time IST'),
        ],
        'dup' => [
            'box' => 'bg-[#FDF0DA]', 'dot' => 'bg-heat', 'title' => 'text-[#5B3A08]', 'text' => 'text-[#6B4A12]',
            'icon' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#121A33" stroke-width="3" stroke-linecap="round"><path d="M12 7v6M12 17v.5"/></svg>',
            'tTitle' => __('Already checked in'), 'tLine1' => __(':name, :pass'), 'tLine2' => __('First scanned at :time IST. Not stamped again.'),
        ],
        'bad' => [
            'box' => 'bg-[#FBE4E4]', 'dot' => 'bg-danger', 'title' => 'text-[#6E1414]', 'text' => 'text-[#7A2222]',
            'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
            'tTitle' => __('Pass not recognised'), 'tLine1' => ':message', 'tLine2' => '',
        ],
    ];
    $fill = fn (string $t) => strtr($t, [':name' => $name, ':pass' => $pass, ':time' => $time, ':message' => $result['message'] ?? '']);
    $tab = 'min-h-[44px] rounded-full text-sm font-semibold transition duration-200 ease-track';
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Check-in') }}</x-slot>
    <x-slot name="header">
        <div class="max-w-xl">
            <h2>{{ __('Check-in') }}</h2>
            <p class="mt-1.5 text-[15px] text-muted">{{ __('Scan the QR on a runner’s pass, or type their EF- number. A second scan shows the first check-in time and does not stamp again.') }}</p>
        </div>
    </x-slot>

    <style>
        @keyframes ci-sweep { 0%, 100% { top: 12%; } 50% { top: 84%; } }
        [data-scanner] [data-sweep] { display: none; }
        [data-scanner][data-scanning] [data-sweep] { display: block; animation: ci-sweep 2.4s cubic-bezier(.2,.7,.2,1) infinite; }
        @media (prefers-reduced-motion: reduce) { [data-scanner][data-scanning] [data-sweep] { animation: none; top: 50%; } }
        #qr-reader video { width: 100% !important; height: 100% !important; object-fit: cover; }
        #qr-reader #qr-shaded-region { display: none; }
    </style>

    <div class="mx-auto max-w-[1180px] space-y-[14px] px-4 pb-12 pt-4 sm:px-6 lg:px-10 [&>*]:max-w-[560px]"
         x-data="{ mode: @js($current ? 'type' : 'scan') }" data-checkin
         data-camera-title="{{ __('Camera not available') }}" data-camera-error="{{ __('Could not start the camera. Type the pass number instead.') }}" data-failed="{{ __('Check-in failed. Try again.') }}">
        <div role="tablist" aria-label="{{ __('Check-in method') }}" class="grid grid-cols-2 rounded-full bg-white p-1">
            <button type="button" role="tab" id="tab-scan" aria-controls="panel-scan" class="{{ $tab }}" data-tab="scan"
                    @click="mode = 'scan'" :aria-selected="(mode === 'scan').toString()" :class="mode === 'scan' ? 'bg-ink text-white' : 'text-ink hover:bg-paper'">{{ __('Scan QR') }}</button>
            <button type="button" role="tab" id="tab-type" aria-controls="panel-type" class="{{ $tab }}" data-tab="type"
                    @click="mode = 'type'; $nextTick(() => $refs.pass.focus())" :aria-selected="(mode === 'type').toString()" :class="mode === 'type' ? 'bg-ink text-white' : 'text-ink hover:bg-paper'">{{ __('Type pass number') }}</button>
        </div>

        {{-- Camera: allowed to be dark. --}}
        <div id="panel-scan" role="tabpanel" aria-labelledby="tab-scan" x-show="mode === 'scan'">
            <div class="relative h-[330px] overflow-hidden rounded-[28px] bg-[linear-gradient(160deg,#6C7493,#3B4463)]" data-scanner id="scanner-box">
                <div id="qr-reader" class="absolute inset-0"></div>
                <span class="absolute left-3.5 top-3.5 z-10 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-ink">{{ __('Camera view') }}</span>
                <button type="button" id="stop-scan" class="absolute right-2 top-2 z-10 hidden min-h-[44px] rounded-full bg-white/90 px-4 text-[13px] font-semibold text-ink hover:bg-white">{{ __('Stop camera') }}</button>
                <div aria-hidden="true" class="pointer-events-none absolute left-1/2 top-1/2 h-[210px] w-[210px] -translate-x-1/2 -translate-y-1/2 rounded-3xl shadow-[0_0_0_999px_rgb(18_26_51/.35)]">
                    <span class="absolute -left-0.5 -top-0.5 h-10 w-10 rounded-tl-[22px] border-l-[5px] border-t-[5px] border-heat"></span>
                    <span class="absolute -right-0.5 -top-0.5 h-10 w-10 rounded-tr-[22px] border-r-[5px] border-t-[5px] border-heat"></span>
                    <span class="absolute -bottom-0.5 -left-0.5 h-10 w-10 rounded-bl-[22px] border-b-[5px] border-l-[5px] border-heat"></span>
                    <span class="absolute -bottom-0.5 -right-0.5 h-10 w-10 rounded-br-[22px] border-b-[5px] border-r-[5px] border-heat"></span>
                    <span data-sweep class="absolute left-3 right-3 h-0.5 bg-heat shadow-[0_0_12px_#F2A93B]"></span>
                </div>
                <button type="button" id="start-scan" class="absolute left-1/2 top-1/2 z-10 min-h-[44px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-white px-5 text-sm font-bold text-ink hover:bg-paper">{{ __('Start camera') }}</button>
                <p class="absolute inset-x-0 bottom-4 z-10 text-center text-sm text-white">{{ __('Hold the runner’s pass inside the frame') }}</p>
            </div>
        </div>

        <form id="panel-type" role="tabpanel" aria-labelledby="tab-type" x-show="mode === 'type'" x-cloak
              method="POST" action="{{ route('checkin.store') }}" class="space-y-3 rounded-[28px] bg-white p-[22px]" data-checkin-form>
            @csrf
            <div>
                <label for="pass_no" class="ri-label">{{ __('Pass number') }}</label>
                <input id="pass_no" name="pass_no" x-ref="pass" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="EF-1A2B3C4D5E6F"
                       class="ri-input min-h-[52px] text-lg uppercase tracking-wide" @if ($current) autofocus @endif>
            </div>
            <x-ri.button type="submit" variant="ink" size="md" class="w-full">{{ __('Confirm check-in') }}</x-ri.button>
        </form>

        {{-- Result: the server fills it after a typed pass, checkin.js after a scan. --}}
        <div role="status" aria-live="polite" id="checkin-result">
            @foreach ($states as $key => $s)
                <div data-result="{{ $key }}" data-t-title="{{ $s['tTitle'] }}" data-t-line1="{{ $s['tLine1'] }}" data-t-line2="{{ $s['tLine2'] }}"
                     @class(['flex items-center gap-3.5 rounded-3xl p-[18px]', $s['box'], 'hidden' => $current !== $key])>
                    <span aria-hidden="true" class="flex h-[52px] w-[52px] flex-none items-center justify-center rounded-full {{ $s['dot'] }}">{!! $s['icon'] !!}</span>
                    <span class="flex min-w-0 flex-col gap-0.5">
                        <strong class="text-lg {{ $s['title'] }}" data-field="title">{{ $current === $key ? $fill($s['tTitle']) : '' }}</strong>
                        <span class="break-words text-sm {{ $s['text'] }}" data-field="line1">{{ $current === $key ? $fill($s['tLine1']) : '' }}</span>
                        <span class="text-[13px] {{ $s['text'] }}" data-field="line2">{{ $current === $key ? $fill($s['tLine2']) : '' }}</span>
                    </span>
                </div>
            @endforeach
        </div>

        <x-ri.button variant="primary" size="lg" class="hidden w-full" id="scan-next" x-show="mode === 'scan'">{{ __('Scan next runner') }}</x-ri.button>
    </div>

    @vite('resources/js/checkin.js')
</x-app-layout>
