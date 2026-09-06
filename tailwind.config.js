import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {

        extend: {

            colors: {

                oneshop: {

                    primary: '#0057B8',

                    dark: '#003B7A',

                    light: '#EAF3FF',

                    soft: '#F5F9FF',

                },

            },


            fontFamily: {

                sans: [
                    'Inter',
                    ...defaultTheme.fontFamily.sans,
                ],

            },


            boxShadow: {

                'oneshop':
                    '0 10px 30px rgba(0, 87, 184, 0.08)',

            },

        },

    },


    plugins: [
        forms
    ],

};