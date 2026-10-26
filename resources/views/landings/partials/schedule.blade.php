{{-- Race-day schedule as a start list. $page, $dark --}}
@php($rows = $page->schedule())
<ol @class(['divide-y border-y', 'divide-paper/15 border-paper/15' => $dark ?? false, 'divide-ink/15 border-ink/15' => ! ($dark ?? false)])>
    @foreach ($rows as $row)
        <li class="grid grid-cols-[5.5rem_1fr] items-baseline gap-4 py-4">
            <span class="font-display text-3xl font-extrabold tabular-nums">{{ $row['time'] }}</span>
            <span class="text-base">{{ $row['label'] }}</span>
        </li>
    @endforeach
</ol>
@if ($rows && $rows[0]['indicative'])
    <p @class(['mt-3 text-xs', 'text-paper/50' => $dark ?? false, 'text-ink/50' => ! ($dark ?? false)])>{{ __('Indicative times; the organiser confirms start waves.') }}</p>
@endif
