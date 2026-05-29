import "./bootstrap";
import "../css/app.css";
import "../css/style.css";

import "vue3-colorpicker/style.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { createPinia } from "pinia"; // Import Pinia
import Helpers from "./Mixins/Helpers_old";
import VueClipboard from "vue3-clipboard";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

const appName =
    window.document.getElementsByTagName("title")[0]?.innerText || "Laravel";

import moment from "moment-timezone";
window.moment = moment;

window.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
window.time_format = "MMM D, YYYY h:mm A";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        const pinia = createPinia(); // Create Pinia instance

        app.use(plugin)
            .use(ZiggyVue)
            .use(pinia) // Register Pinia before other plugins
            .mixin(Helpers)
            .use(VueClipboard, {
                autoSetContainer: true,
                appendToBody: true,
            })
            .mount(el);

        return app;
    },
    progress: {
        color: "#08c6d4",
        showSpinner: true,
    },
});
