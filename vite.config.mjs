import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import statamic from '@statamic/cms/vite-plugin';

export default defineConfig({
    server: {
        host: 'localhost',
        port: 5178,
        strictPort: true,
    },
    plugins: [
        laravel({
            input: ['resources/js/addon.js'],
            publicDirectory: '../../../public/vendor/song-search',
            buildDirectory: 'build',
            hotFile: '../../../public/vendor/song-search/hot',
        }),
        statamic(),
    ],
});
