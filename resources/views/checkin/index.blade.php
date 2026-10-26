<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">Attendance check-in</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4 px-4">
            <p class="text-sm text-muted">Scan a participant QR on your phone, or type the EF- pass number. A second scan shows the original check-in time and does not stamp again.</p>

            @if($result)
                <div class="rounded-lg p-4 text-sm {{ ($result['ok'] ?? false) ? (($result['duplicate'] ?? false) ? 'bg-[#FDF0DA] text-[#6B4A12]' : 'bg-green-50 text-green-900') : 'bg-[#FBE4E4] text-[#6E1414]' }}">
                    <p class="font-semibold">{{ $result['message'] ?? '' }}</p>
                    @if(!empty($result['participant_name']))
                        <p class="mt-1">{{ $result['participant_name'] }} · {{ $result['pass_no'] }}</p>
                        <p>Check-in: {{ $result['checked_in_at'] }} IST</p>
                    @endif
                </div>
            @endif

            <div id="checkin-live" class="hidden rounded-lg p-4 text-sm"></div>

            <form id="checkin-form" method="POST" action="{{ route('checkin.store') }}" class="bg-white shadow-sm rounded-lg p-5 space-y-4">
                @csrf
                <div>
                    <label for="pass_no" class="block text-sm font-semibold text-ink">Pass number</label>
                    <input id="pass_no" name="pass_no" inputmode="text" autocomplete="off" placeholder="EF-1A2B3C4D5E6F" class="mt-1 block w-full rounded-md border-mist shadow-sm text-lg tracking-wide uppercase">
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" class="inline-flex justify-center items-center px-4 py-3 bg-ink text-white rounded-md font-semibold">Confirm check-in</button>
                    <button type="button" id="start-scan" class="inline-flex justify-center items-center px-4 py-3 bg-white border border-mist rounded-md font-semibold">Scan QR with camera</button>
                </div>
                <div id="scanner-box" class="hidden">
                    <div id="qr-reader" class="overflow-hidden rounded-md"></div>
                    <button type="button" id="stop-scan" class="mt-3 px-4 py-2 border rounded-md">Stop camera</button>
                </div>
            </form>
        </div>
    </div>

    @vite('resources/js/checkin.js')
</x-app-layout>
