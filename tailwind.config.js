import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                        'tech-dark': '#0a0a0a',
                        'tech-panel': '#111111',
                        'tech-border': '#1a1a1a',
                        'tech-green': '#22c55e',
                        'tech-red': '#ef4444',
                        'tech-blue': '#3b82f6',
                        'tech-yellow': '#eab308',
                    }
        },
    },

    plugins: [forms, typography],
};
