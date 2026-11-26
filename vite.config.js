import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public',
        emptyOutDir: false,
        rollupOptions: {
            input: path.resolve(__dirname, 'resources/js/app.js'),
            output: {
                entryFileNames: 'js/app.js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name || '';

                    if(name.endsWith('.css'))
                    {
                        return 'css/app.css';
                    }

                    return 'assets/[name][extname]';
                },
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                loadPaths: ['node_modules'],
                silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function', 'abs-percent'],
            },
        },
    },
});
