{{--
    Pill tab group (Desktop / Mobile, Test / Live, Email / WhatsApp).
    $options: [value => label]; $model: Alpine variable to bind (client-side), or
    $name for a form radio group, or $links: [value => url] for navigation; $value: the selected one.
--}}
@props(['options' => [], 'value' => null, 'model' => null, 'name' => null, 'links' => null, 'label' => null])
<div role="{{ $name ? 'radiogroup' : 'tablist' }}" @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->merge(['class' => 'inline-flex rounded-full bg-paper p-1 ring-1 ring-inset ring-mist']) }} data-segmented>
    @foreach ($options as $key => $text)
        @php($base = 'inline-flex min-h-[40px] items-center rounded-full px-4 text-sm font-semibold transition duration-200 ease-track')
        @if ($name)
            <label class="cursor-pointer">
                <input type="radio" name="{{ $name }}" value="{{ $key }}" class="peer sr-only" @checked((string) $value === (string) $key)>
                <span class="{{ $base }} text-muted peer-checked:bg-white peer-checked:text-ink peer-checked:shadow-sm peer-focus-visible:outline peer-focus-visible:outline-[3px] peer-focus-visible:outline-[#F2A93B]">{{ $text }}</span>
            </label>
        @elseif ($links)
            <a href="{{ $links[$key] }}" role="tab" aria-selected="{{ (string) $value === (string) $key ? 'true' : 'false' }}" @class([$base, 'bg-white text-ink shadow-sm' => (string) $value === (string) $key, 'text-muted hover:text-ink' => (string) $value !== (string) $key])>{{ $text }}</a>
        @else
            <button type="button" role="tab" class="{{ $base }}"
                    @if ($model) @click="{{ $model }} = @js((string) $key)" :aria-selected="{{ $model }} === @js((string) $key) ? 'true' : 'false'" :class="{{ $model }} === @js((string) $key) ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'" @endif
                    aria-selected="{{ (string) $value === (string) $key ? 'true' : 'false' }}">{{ $text }}</button>
        @endif
    @endforeach
</div>
