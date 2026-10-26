<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ri-glow inline-flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-track px-6 text-[15px] font-bold text-white transition duration-200 ease-track hover:bg-track-deep disabled:opacity-50']) }}>
    {{ $slot }}
</button>
