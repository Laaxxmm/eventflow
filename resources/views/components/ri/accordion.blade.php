{{--
    Accordion: native <details>, keyboard and screen-reader friendly without JS.
    $items: [['title' => ..., 'body' => ...]] (body may be an HtmlString).
    dark: light text on a dark panel. cards: one white card per question (phone race page style).
--}}
@props(['items' => [], 'dark' => false, 'open' => null, 'cards' => false])
<div {{ $attributes->merge(['class' => $cards ? 'flex flex-col gap-2.5' : 'border-b '.($dark ? 'border-white/20' : 'border-mist')]) }}>
    @foreach ($items as $i => $item)
        <details @class(['group', 'rounded-[18px] bg-white px-[18px] py-4' => $cards, 'border-t py-[18px] '.($dark ? 'border-white/20' : 'border-mist') => ! $cards]) @if ($open === $i) open @endif>
            <summary class="flex min-h-[28px] cursor-pointer list-none items-center justify-between gap-6 text-base font-bold [&::-webkit-details-marker]:hidden">
                <span>{{ $item['title'] }}</span>
                <span class="relative h-3.5 w-3.5 shrink-0" aria-hidden="true">
                    <span class="absolute left-0 top-1/2 h-[2px] w-3.5 -translate-y-1/2 rounded bg-current"></span>
                    <span class="absolute left-1/2 top-0 h-3.5 w-[2px] -translate-x-1/2 rounded bg-current transition group-open:rotate-90 group-open:opacity-0"></span>
                </span>
            </summary>
            <div class="mt-2.5 max-w-3xl leading-relaxed {{ $dark ? 'text-white/80' : 'text-muted' }}">{{ $item['body'] }}</div>
        </details>
    @endforeach
</div>
