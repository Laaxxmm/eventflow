{{-- Race-day schedule: time in the brand colour, plain label. $page, $dark (light text on a dark panel) --}}
@php
    $rows = $page->schedule();
@endphp
<ol class="flex flex-col gap-3.5 text-base">
    @foreach ($rows as $row)
        <li class="flex gap-5">
            <span @class(['ri-headline w-16 shrink-0 tabular-nums', 'text-heat' => $dark ?? false, 'text-track-text' => ! ($dark ?? false)])>{{ $row['time'] }}</span>
            <span>{{ $row['label'] }}</span>
        </li>
    @endforeach
</ol>
@if ($rows && $rows[0]['indicative'])
    <p @class(['mt-4 text-[13px]', 'text-white/70' => $dark ?? false, 'text-muted' => ! ($dark ?? false)])>{{ __('Indicative times; the organiser confirms start waves.') }}</p>
@endif
