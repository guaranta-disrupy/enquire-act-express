import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default({ mode }) => {
    process.env = { ...process.env, ...loadEnv(mode, process.cwd()) };
    return defineConfig({
        plugins: [
            laravel([
                'resources/css/app.css',
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
        resolve: {
            alias: {
                '@': '/resources/js'
            }
        },
        server: {
			cors: true,
            host: 'express.test',
            port: 5173
        },
        build: {
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        // Create a 'vendor' chunk for all node_modules dependencies
                        if (id.includes('node_modules')) {
                            return 'vendor';
                        }
                        // Example: Group common UI components if you have many
                        if (id.includes('resources/js/S4/')) {
                            return 's4-components';
                        }
                        // Keep Jetstream components in the main bundle to avoid initialization issues
                        // if (id.includes('resources/js/Jetstream/')) {
                        //     return 'jetstream';
                        // }
                        
                        // Group Analysis, Questions, and Gallery together to prevent circular dependencies
                        if (id.includes('resources/js/Pages/Questions/') || id.includes('resources/js/Pages/Analysis/') || id.includes('resources/js/Pages/Gallery/')) {
                            return 'questions-analysis-gallery';
                        }
                        // if (id.includes('resources/js/Pages/Questionnaires/')) {
                        //     return 'questionnaires';
                        // }
                        if (id.includes('resources/js/Pages/Auth/')) {
                            return 'auth';
                        }
                        if (id.includes('resources/js/Pages/Teams2/')) {
                            return 'teams2';
                        }
                        if (id.includes('resources/js/Pages/Teams/')) {
                            return 'teams';
                        }
                        if (id.includes('resources/js/Pages/Users/')) {
                            return 'users';
                        }
                        if (id.includes('resources/js/Pages/Trash/')) {
                            return 'trash';
                        }
                        if (id.includes('resources/js/Pages/Projects/')) {
                            return 'projects';
                        }
                        
                    },
                    // Configure naming for your output chunks
                    entryFileNames: 'assets/[name].[hash].js',
                    chunkFileNames: 'assets/[name].[hash].js',
                    assetFileNames: 'assets/[name].[hash].[ext]',
                }
            }
        }
    });
}
