{{-- Distances with prices and places, as a compact list. $page --}}
<ul class="divide-y divide-mist">
    @foreach ($page->distances as $row)
        <li @class(['flex items-baseline justify-between gap-4 py-3', 'text-muted' => $row['full']])>
            <span><span class="font-semibold">{{ $row['distance']->label }}</span> <span class="text-sm text-muted">· {{ $page->placesLabel($row['left']) }}</span></span>
            <span @class(['ri-headline text-2xl', 'line-through' => $row['full']])>{{ $row['price'] }}</span>
        </li>
    @endforeach
</ul>
