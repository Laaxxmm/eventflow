<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full bg-danger px-5 text-sm font-semibold text-white transition duration-200 ease-track hover:bg-[#8A2222] disabled:opacity-50']) }}>
    {{ $slot }}
</button>
