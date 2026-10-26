import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const token = (name) => `rgb(var(--ri-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/View/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Barlow Condensed"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            // Race Ink tokens (resources/css/app.css). Landings override them per event (App\Landing\Theme).
            colors: {
                ink: token('ink'),
                track: token('track'),
                'on-track': token('on-track'),
                'track-text': token('track-text'),
                heat: token('heat'),
                'on-heat': token('on-heat'),
                paper: token('paper'),
                mist: token('mist'),
                midnight: token('midnight'),
                volt: token('volt'),
            },
        },
    },

    plugins: [forms],
};
