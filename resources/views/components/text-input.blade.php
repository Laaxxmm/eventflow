@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-ink/20 focus:border-ink focus:ring-ink rounded-md shadow-sm']) }}>
