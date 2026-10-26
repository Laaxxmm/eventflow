<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full bg-white px-5 text-sm font-semibold text-ink ring-1 ring-inset ring-[#C5CBDB] transition duration-200 ease-track hover:ring-ink/40 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
