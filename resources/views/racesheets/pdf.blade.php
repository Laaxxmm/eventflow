<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #17213a; font-size: 9.5pt; }
        h1 { font-size: 15pt; margin: 0 0 1mm; }
        .meta { color: #5b6478; margin-bottom: 6mm; }
        .heat { page-break-inside: avoid; margin-bottom: 7mm; }
        h2 { font-size: 11pt; margin: 0 0 2mm; padding: 1.5mm 2mm; background: #eef0f6; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #c9cedb; padding: 1.6mm 2mm; text-align: left; }
        th { background: #f6f7fb; font-weight: bold; }
        .n { width: 9mm; }
        .pass { width: 34mm; font-family: DejaVu Sans Mono, monospace; }
        .age { width: 12mm; }
        .blank { width: 26mm; }
    </style>
</head>
<body>
    <h1>{{ $event->name }} &mdash; {{ $isStartList ? 'Start lists' : 'Race sheet' }}</h1>
    <div class="meta">
        {{ $event->date?->timezone('Asia/Kolkata')->format('d M Y') }} &middot; {{ $event->venue }}
        @unless ($isStartList) &middot; heats of up to {{ $sheet->config->heatSize }} @endunless
    </div>

    @forelse ($sheet->heats as $heat)
        <div class="heat">
            <h2>{{ implode(' · ', $heat->group) }}@unless ($isStartList) &mdash; Heat {{ $heat->number }}@endunless</h2>
            <table>
                <thead>
                    <tr>
                        <th class="n">{{ $isStartList ? 'No.' : 'Lane' }}</th>
                        <th class="pass">Pass no</th>
                        <th>Name</th>
                        <th class="age">Age</th>
                        <th class="blank">Time</th>
                        <th class="blank">Place</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($heat->registrationIds as $i => $id)
                        @php($r = $entrants[$id])
                        <tr>
                            <td class="n">{{ $i + 1 }}</td>
                            <td class="pass">{{ $r->pass_no }}</td>
                            <td>{{ $r->participant_name }}</td>
                            <td class="age">{{ $r->answers['age'] ?? '' }}</td>
                            <td class="blank"></td>
                            <td class="blank"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p>No paid entrants yet.</p>
    @endforelse
</body>
</html>
