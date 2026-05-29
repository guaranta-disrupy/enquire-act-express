import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    // server: {
    //     host: "0.0.0.0",
    //     port: 5174,
    //     strictPort: true,
    //     headers: {
    //         "Access-Control-Allow-Origin": "*",
    //     },
    //     hmr: {
    //         // host: "192.168.5.235", // Mesh
    //         // host: "192.168.0.146", // Starlink
    //         // host: "192.168.1.18", // S42G|S45G
    //         host: "192.168.5.235",
    //         protocol: "ws",
    //         clientPort: 5174,
    //     },
    //     cors: {
    //         origin: "*",
    //     },
    // },
    server: {
        host: "act.test",
        port: 5173,
    },
});
