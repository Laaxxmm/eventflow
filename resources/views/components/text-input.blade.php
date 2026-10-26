@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-[14px] border-[#C5CBDB] bg-white text-ink shadow-none placeholder:text-[#8A92AB] focus:border-track focus:ring-track disabled:bg-paper']) }}>
