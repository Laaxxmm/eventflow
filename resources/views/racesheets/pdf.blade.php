<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #121a33; font-size: 9.5pt; }
        h1 { font-size: 16pt; margin: 0 0 1mm; color: #121a33; }
        .meta { color: #4a5372; margin-bottom: 6mm; padding-bottom: 3mm; border-bottom: 0.6mm solid #4b3fe0; }
        .heat { page-break-inside: avoid; margin-bottom: 7mm; }
        h2 { font-size: 10.5pt; margin: 0 0 2mm; padding: 1.8mm 2.5mm; background: #eceaff; color: #2b2390; border-left: 1.2mm solid #4b3fe0; }
        h2 .no { color: #4a5372; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #dde2ee; padding: 1.8mm 2mm; text-align: left; }
        th { color: #4a5372; font-size: 8pt; font-weight: bold; border-bottom: 1px solid #c5cbdb; }
        .n { width: 9mm; color: #4b3fe0; font-weight: bold; }
        .pass { width: 34mm; font-family: DejaVu Sans Mono, monospace; color: #3b4463; }
        .age { width: 12mm; }
        .blank { width: 26mm; border: 1px solid #dde2ee; }
        th.blank { border: 0; border-bottom: 1px solid #c5cbdb; }
    </style>
</head>
<body>
    <h1>{{ $isStartList ? 'Start lists' : 'Race sheet' }}: {{ $event->name }}</h1>
    <div class="meta">
        {{ $event->date?->timezone('Asia/Kolkata')->format('d M Y') }} &middot; {{ $event->venue }}
        @unless ($isStartList) &middot; heats of up to {{ $sheet->config->heatSize }} @endunless
    </div>

    @forelse ($sheet->heats as $heat)
        <div class="heat">
            <h2>{{ implode(' · ', $heat->group) }}@unless ($isStartList) <span class="no">&nbsp; Heat {{ $heat->number }}</span>@endunless</h2>
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
