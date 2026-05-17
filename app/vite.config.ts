import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'node:path';

// Spec 2026-05-17 — hai entry tách hoàn toàn: `app.tsx` (user SPA) và
// `admin.tsx` (admin SPA dưới `/admin/*`). Laravel route `/admin/{any?}` trả
// Blade `admin.blade.php` nạp bundle admin; mọi path khác trả `app.blade.php`.

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.tsx', 'resources/js/admin.tsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(process.cwd(), 'resources/js'),
            '@admin': path.resolve(process.cwd(), 'resources/js/admin'),
        },
    },
    server: { host: '0.0.0.0', port: 5173, hmr: { host: 'localhost' } },
});
