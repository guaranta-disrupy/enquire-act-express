const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require('tailwindcss/colors')
const svgToDataUri = require('mini-svg-data-uri')

module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    safelist: [
        'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4', 'grid-cols-5', 'grid-cols-6', 'grid-cols-7', 'grid-cols-8', 'grid-cols-9', 'grid-cols-10', 'grid-cols-11', 'grid-cols-12', 
        'pb-*', 'pt-*',
        // Toggle/Switch component classes
        'w-[36px]', 'left-[2px]', 'top-[2px]', 'w-4', 'h-4', 'h-5',
        'bg-gray-300', 'bg-white', 'rounded-full', 'shadow-inner', 'shadow-md',
        'absolute', 'relative', 'transition', 'sr-only', 'cursor-pointer',
        // Pattern for arbitrary values
        { pattern: /left-\[.+\]/ },
        { pattern: /top-\[.+\]/ },
        { pattern: /w-\[.+\]/ },
        { pattern: /h-\[.+\]/ },
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                orange: colors.orange,
                ssr: {
                    blue1: '#00519e',
                    blue2: '#1e71b8',
                    blue3: '#759fd1',
                    blue4: '#ceeaff',
                    bluehover: '#1e71b890',
                    orange1: '#e94f1d',
                    orange2: '#e28733',
                    orange3: '#fdeac6',
                    orangehover: '#e17127',
                    yellow1: '#ebc246',
                    red1: '#c13128',
                    red2: '#c33b27',
                    transparent: '#F3F4F6',
                },
            },
            backgroundImage: (theme) => ({
                'multiselect-caret': `url("${svgToDataUri(
                    `<svg viewBox="0 0 320 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"></path></svg>`,
                )}")`,
                'multiselect-spinner': `url("${svgToDataUri(
                    `<svg viewBox="0 0 512 512" fill="${theme('colors.green.500')}" xmlns="http://www.w3.org/2000/svg"><path d="M456.433 371.72l-27.79-16.045c-7.192-4.152-10.052-13.136-6.487-20.636 25.82-54.328 23.566-118.602-6.768-171.03-30.265-52.529-84.802-86.621-144.76-91.424C262.35 71.922 256 64.953 256 56.649V24.56c0-9.31 7.916-16.609 17.204-15.96 81.795 5.717 156.412 51.902 197.611 123.408 41.301 71.385 43.99 159.096 8.042 232.792-4.082 8.369-14.361 11.575-22.424 6.92z"></path></svg>`,
                )}")`,
                'multiselect-remove': `url("${svgToDataUri(
                    `<svg viewBox="0 0 320 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z"></path></svg>`,
                )}")`,
            })
        },

    },

    variants: {
        extend: {
            opacity: ['disabled'],
            cursor: ['disabled'],
        },
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
