import "../css/app.css";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "primeicons/primeicons.css";
import "../../public/assets/css/app.min.css";

import $ from "jquery";
window.$ = window.jQuery = $;

import "bootstrap-table/dist/bootstrap-table.min.css";
import "bootstrap-table/dist/bootstrap-table.min.js";

import Waves from "node-waves";
import "node-waves/dist/waves.min.css";
window.Waves = Waves;

import toastr from "toastr";
import "toastr/build/toastr.min.css";
window.toastr = toastr;

toastr.options = {
    positionClass: "toast-top-center",
    timeOut: 2500,
    progressBar: true,
    closeButton: true,
    newestOnTop: true,
    preventDuplicates: true,
};

// import '../../public/assets/libs/simplebar/simplebar.min.js';
// import '../../public/assets/libs/metismenu/metisMenu.min.js';

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h, nextTick } from "vue";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import { ZiggyVue } from "../../vendor/tightenco/ziggy/src/js";
import theme from "tailwindcss/defaultTheme";
// import Swal from "@/plugins/swal";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

async function initLegacyScripts() {
    // await nextTick();

    console.log("window.jQuery:", typeof window.jQuery);

    await import("../../public/assets/libs/simplebar/simplebar.min.js");
    await import("../../public/assets/libs/metismenu/metisMenu.min.js");
    await import("../../public/assets/js/appl.js");

    if (window.Waves) {
        window.Waves.init();
        window.Waves.attach(".waves-effect");
    }

    $("#vertical-menu-btn")
        .off("click")
        .on("click", function (e) {
            e.preventDefault();

            $("body").toggleClass("sidebar-enable");

            if ($(window).width() >= 992) {
                $("body").toggleClass("vertical-collpsed");
            } else {
                $("body").removeClass("vertical-collpsed");
            }
        });

    if ($("#side-menu").length) {
        $("#side-menu").removeData("metisMenu");
        $("#side-menu").find("ul").removeAttr("style");
        $("#side-menu").metisMenu();
    }
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                ripple: true,
                inputStyle: "outlined",
            })
            // .use(Swal)
            .mount(el);

        nextTick(() => {
            initLegacyScripts();
        });

        return app;
    },
    progress: {
        color: "#4B5563",
    },
});

router.on("finish", () => {
    nextTick(() => {
        initLegacyScripts();
    });
});
