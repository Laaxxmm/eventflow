{{-- One landing template as a card linking to its public preview. $key, $tpl, $index, $large --}}
@php
    $thumb = file_exists(public_path("images/templates/{$key}.jpg")) ? asset("images/templates/{$key}.jpg") : null;
    $large = $large ?? false;
@endphp
<a href="{{ route('templates.show', $key) }}" data-template-card="{{ $key }}" data-reveal class="group flex flex-col bg-paper text-ink">
    <div @class(['relative overflow-hidden ring-2 ring-ink', 'aspect-[16/10]' => $large, 'aspect-[4/3]' => ! $large])>
        @if ($thumb)
            <img src="{{ $thumb }}" alt="{{ __(':name template preview', ['name' => $tpl['label']]) }}" loading="{{ $large ? 'eager' : 'lazy' }}" decoding="async" class="absolute inset-0 h-full w-full object-cover object-left-top transition duration-500 group-hover:scale-[1.03]">
        @else
            <div @class(['absolute inset-0', 'bg-midnight' => $tpl['tone'] === 'dark', 'bg-mist' => $tpl['tone'] !== 'dark'])></div>
        @endif
        <span class="absolute left-3 top-3 bg-ink px-2 py-1 font-display text-sm font-bold text-paper">{{ str_pad($index, 2, '0', STR_PAD_LEFT) }}</span>
    </div>
    <div class="flex items-baseline justify-between gap-3 pt-4">
        <p @class(['font-display font-extrabold uppercase leading-none', 'text-4xl' => $large, 'text-2xl' => ! $large])>{{ $tpl['label'] }}</p>
        <span class="text-sm font-semibold text-ink/60 group-hover:text-ink">{{ __('Preview') }} →</span>
    </div>
    @if ($large)
        <p class="mt-2 text-ink/70">{{ $tpl['description'] }}</p>
    @endif
</a>
