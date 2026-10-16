import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                sans: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                canvas: 'rgb(var(--canvas) / <alpha-value>)',
                surface: 'rgb(var(--surface) / <alpha-value>)',
                panel: 'rgb(var(--panel) / <alpha-value>)',
                ink: 'rgb(var(--ink) / <alpha-value>)',
                muted: 'rgb(var(--muted) / <alpha-value>)',
                lime: 'rgb(var(--lime) / <alpha-value>)',
                cyan: 'rgb(var(--cyan) / <alpha-value>)',
                line: 'rgb(var(--line) / <alpha-value>)',
                danger: 'rgb(var(--danger) / <alpha-value>)',
            },
            boxShadow: {
                glow: '0 0 40px rgb(var(--lime) / 0.18), 0 0 80px rgb(var(--cyan) / 0.08)',
                neon: '0 0 0 1px rgb(var(--lime) / 0.45), 0 0 24px rgb(var(--lime) / 0.2)',
            },
            keyframes: {
                ticker: {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                pulseRing: {
                    '0%, 100%': { boxShadow: '0 0 0 0 rgb(var(--lime) / 0.45)' },
                    '50%': { boxShadow: '0 0 0 8px rgb(var(--lime) / 0)' },
                },
            },
            animation: {
                ticker: 'ticker 28s linear infinite',
                pulseRing: 'pulseRing 1.8s ease-out infinite',
            },
        },
    },

    plugins: [forms],
};
