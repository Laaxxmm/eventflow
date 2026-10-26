{{-- Distances with prices and places, as a compact table. $page --}}
<ul class="divide-y divide-ink/10">
    @foreach ($page->distances as $row)
        <li @class(['flex items-baseline justify-between gap-4 py-3', 'opacity-50' => $row['full']])>
            <span><span class="font-semibold text-ink">{{ $row['distance']->label }}</span> <span class="text-sm">· {{ $page->placesLabel($row['left']) }}</span></span>
            <span class="font-display text-2xl font-extrabold text-ink">{{ $row['full'] ? __('Full') : $row['price'] }}</span>
        </li>
    @endforeach
</ul>
