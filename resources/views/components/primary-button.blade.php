<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 bg-ink px-5 py-2.5 font-display text-lg font-bold uppercase tracking-[0.06em] text-paper transition hover:bg-ink/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-heat focus-visible:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
