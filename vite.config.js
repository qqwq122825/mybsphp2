import { readFileSync, writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

let outputDirectory;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin-ui.js', 'resources/js/agent-ui.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        vue(),
        {
            name: 'software-ui-build-contract',
            apply: 'build',
            configResolved(config) { outputDirectory = resolve(config.root, config.build.outDir); },
            closeBundle() {
                const contract = JSON.parse(readFileSync(new URL('./resources/js/admin-ui/build-contract.json', import.meta.url), 'utf8'));
                const manifest = readFileSync(resolve(outputDirectory, 'manifest.json'));
                writeFileSync(resolve(outputDirectory, 'software-ui-version.json'), JSON.stringify({
                    ...contract, manifestSha256: createHash('sha256').update(manifest).digest('hex'),
                }));
            },
        },
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
