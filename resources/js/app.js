import "../css/app.css";
import "vue-sonner/style.css";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { Toaster } from "vue-sonner";
import { InertiaProgress } from "@inertiajs/progress";

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),

    setup({ el, App, props, plugin }) {
        createApp({
            render: () =>
                h("div", [
                    h(App, props),
                    h(Toaster, {
                        position: "top-right",
                        closeButton: true,
                        expand: true,
                    }),
                ]),
        })
            .use(plugin)
            .mount(el);
    },
});

InertiaProgress.init({
    color: "#3b82f6",
    showSpinner: false,
});
