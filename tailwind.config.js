const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'bg-slate-500',
        'bg-red-500',
        'bg-blue-500',
        'bg-green-500',
        'toggle',
        'h-96',
        'box-border',
        'h-32',
        'w-32',
        'p-4',
        'border-4',
        'flex-none',
        'flex-1',
        'justify-self-start',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [require("daisyui"), require('@tailwindcss/typography')],
};