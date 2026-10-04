import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/js/app.js', 'resources/css/app.css'], refresh: true }),
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            manifest: {
                name: 'Ingastro.pl',
                short_name: 'ingastro',
                description: 'Zamawiaj ulubione dania z ingastro',
                theme_color: '#e11d48',
                background_color: '#ffffff',
                display: 'standalone',
                icons: [
                    { src: '/images/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/images/icon-512.png', sizes: '512x512', type: 'image/png' }
                ]
            }
        })
    ],
});