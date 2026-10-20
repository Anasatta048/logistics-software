import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // 1.1 Brand
                'brand-primary': 'rgb(var(--brand-primary-rgb) / <alpha-value>)',
                'brand-navy': 'rgb(var(--brand-navy-rgb) / <alpha-value>)',
                'brand-gold': '#C9972E',
                'brand-navy-light': '#3A5A8C',
                // 1.2 Semantic / status
                'status-booked': '#1B2A4A',
                'status-loaded': '#3A5A8C',
                'status-dispatched': '#C9972E',
                'status-paid': '#2E7D32',
                'status-cancelled': '#C0392B',
                warning: '#D97706',
                danger: '#C0392B',
                // 1.3 Neutrals
                surface: {
                    DEFAULT: '#FFFFFF',
                    dark: '#111827',
                },
                bg: {
                    DEFAULT: '#F7F7F9',
                    dark: '#0B0F19',
                },
                border: {
                    DEFAULT: '#E5E7EB',
                },
                'text-primary': '#1F2333',
                'text-muted': '#6B7280',
            },
            // Minimal / square: every corner is sharp except true circles (avatars, dots, toggles).
            borderRadius: {
                none: '0', sm: '0', DEFAULT: '0', md: '0', lg: '0', xl: '0', '2xl': '0', '3xl': '0',
                card: 'var(--radius-card)',
                button: 'var(--radius-button)',
                input: 'var(--radius-input)',
            },
            boxShadow: {
                card: 'var(--shadow-card)',
            },
        },
    },

    plugins: [forms],
};
