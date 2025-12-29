import { defineConfig } from 'vitest/config';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    test: {
        globals: true,
        environment: 'happy-dom',
        setupFiles: ['./resources/js/tests/setup.js'],
        include: ['resources/js/**/*.{test,spec}.{js,ts,jsx,tsx}'],
        exclude: ['node_modules', 'storage', 'tmp', 'bootstrap/cache'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html', 'lcov', 'clover'],
            exclude: [
                'node_modules/',
                'resources/js/tests/',
                '**/*.d.ts',
                '**/*.config.*',
                '**/dist/',
                '**/build/',
                '**/coverage/',
            ],
            reportsDirectory: './coverage/js',
            thresholds: {
                lines: 80,
                functions: 80,
                branches: 80,
                statements: 80,
            },
        },
    },
});
