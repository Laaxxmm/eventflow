{{--
    On/off switch: a native checkbox with role="switch" (keyboard + forms work as normal).
    Props: name, checked, value (default 1), id, label, hint, disabled. Sends 0 when off via a hidden input.
--}}
@props(['name', 'checked' => false, 'value' => '1', 'id' => null, 'label' => null, 'hint' => null, 'disabled' => false, 'hidden' => true])
@php($id ??= str_replace(['[', ']', '.'], ['_', '', '_'], $name))
<label for="{{ $id }}" {{ $attributes->merge(['class' => 'group flex min-h-[44px] cursor-pointer items-center justify-between gap-4']) }}>
    @if ($label || $hint)
        <span class="min-w-0">
            @if ($label)<span class="block text-sm font-semibold text-ink">{{ $label }}</span>@endif
            @if ($hint)<span class="mt-0.5 block text-sm text-muted">{{ $hint }}</span>@endif
        </span>
    @endif
    @if ($hidden)<input type="hidden" name="{{ $name }}" value="0">@endif
    <input id="{{ $id }}" type="checkbox" role="switch" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" @checked($checked) @disabled($disabled)>
    <span aria-hidden="true" class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full bg-mist transition-colors duration-200 ease-track peer-checked:bg-track peer-focus-visible:outline peer-focus-visible:outline-[3px] peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#F2A93B] peer-disabled:opacity-50 peer-checked:[&>span]:translate-x-5">
        <span class="ms-1 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 ease-track"></span>
    </span>
</label>
