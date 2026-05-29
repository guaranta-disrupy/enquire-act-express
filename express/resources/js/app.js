import './bootstrap';
import '../css/app.css';
import "vue3-colorpicker/style.css";

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import Vue3ColorPicker from "vue3-colorpicker";



import VueFullscreen from 'vue-fullscreen';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            // .use(VueFullscreen)
            .use(Vue3ColorPicker)
            .mount(el);
    },
    progress: false,
});