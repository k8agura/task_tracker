import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('vue-router')) {
                            return 'vendor-vue';
                        }

                        if (id.includes('bootstrap')) {
                            return 'vendor-bootstrap';
                        }

                        if (id.includes('axios') || id.includes('laravel-echo') || id.includes('pusher-js')) {
                            return 'vendor-network';
                        }
                    }
                    if (id.includes('/resources/js/features/reports/')) {
                        return 'reports-feature';
                    }
                },
            },
        },
    },
    plugins: [
        laravel([
            'resources/js/app.js',
        ]),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
