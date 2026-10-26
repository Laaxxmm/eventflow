{{--
    Race Ink accordion: native <details>, keyboard and screen-reader friendly without JS.
    $items: [['title' => ..., 'body' => ...]] (body may be an HtmlString). dark: light text on dark.
--}}
@props(['items' => [], 'dark' => false, 'open' => null])
<div {{ $attributes->merge(['class' => 'divide-y '.($dark ? 'divide-paper/15 border-y border-paper/15' : 'divide-ink/15 border-y border-ink/15')]) }}>
    @foreach ($items as $i => $item)
        <details class="group" @if ($open === $i) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 font-display text-2xl font-bold uppercase tracking-wide [&::-webkit-details-marker]:hidden">
                <span>{{ $item['title'] }}</span>
                <span class="relative h-4 w-4 shrink-0" aria-hidden="true">
                    <span class="absolute left-0 top-1/2 h-[2px] w-4 -translate-y-1/2 bg-current"></span>
                    <span class="absolute left-1/2 top-0 h-4 w-[2px] -translate-x-1/2 bg-current transition group-open:rotate-90 group-open:opacity-0"></span>
                </span>
            </summary>
            <div class="max-w-3xl pb-6 {{ $dark ? 'text-paper/75' : 'text-ink/75' }}">{{ $item['body'] }}</div>
        </details>
    @endforeach
</div>
