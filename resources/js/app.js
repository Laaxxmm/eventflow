import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Race Ink: reveal [data-reveal] blocks as they scroll in. CSS only animates when
// prefers-reduced-motion allows it; otherwise they are simply visible.
const reveal = document.querySelectorAll('[data-reveal]');
if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    reveal.forEach((el) => io.observe(el));
} else {
    reveal.forEach((el) => el.classList.add('is-visible'));
}
