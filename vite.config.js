import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({ reactivityTransform: true }),
    ],
    base: './',
    resolve: {
        alias: {
            '@': '/resources/js',
            '@public': '/public'
        }
    },
    define: {
        'process.env': process.env
    }
});
