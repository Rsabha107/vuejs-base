import '../css/app.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import '../../public/assets/css/app.min.css';

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

// import * as bootstrap from 'bootstrap';
// window.bootstrap = bootstrap;


import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, nextTick } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// import WaveUI from 'wave-ui';
// import 'wave-ui/dist/wave-ui.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

async function initLegacyScripts() {
    await nextTick();

    console.log('window.jQuery:', typeof window.jQuery);

    await import('../../public/assets/libs/simplebar/simplebar.min.js');
    await import('../../public/assets/libs/metismenu/metisMenu.min.js');
    await import('../../public/assets/js/appl.js');

    window.initThemePlugin();
    console.log('Legacy scripts initialized.');
}

// async function initLegacyScripts() {
//     // await nextTick();

//     console.log("window.jQuery:", typeof window.jQuery);

//     await import("../../public/assets/libs/simplebar/simplebar.min.js");
//     await import("../../public/assets/libs/metismenu/metisMenu.min.js");
//     await import("../../public/assets/js/appl.js");

//     if (window.Waves) {
//         window.Waves.init();
//         window.Waves.attach(".waves-effect");
//     }

//     $("#vertical-menu-btn")
//         .off("click")
//         .on("click", function (e) {
//             e.preventDefault();

//             $("body").toggleClass("sidebar-enable");

//             if ($(window).width() >= 992) {
//                 $("body").toggleClass("vertical-collpsed");
//             } else {
//                 $("body").removeClass("vertical-collpsed");
//             }
//         });

//     if ($("#side-menu").length) {
//         $("#side-menu").removeData("metisMenu");
//         $("#side-menu").find("ul").removeAttr("style");
//         $("#side-menu").metisMenu();
//     }
// }

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        console.log('Inertia app setup called with props:', props);

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            // .use(WaveUI, {
            //     // Global options for WaveUI components
            //     // For example, you can set a default color scheme:
            //     colorScheme: 'ligh', // or 'dark'
            //     display: 'default', // or 'minimal'
            // })
            .mount(el);

        initLegacyScripts();

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// router.on('finish', () => {
//     console.log('Inertia navigation finished. Initializing legacy scripts...');
//     initLegacyScripts();
// });

router.on('finish', () => {
    if (window.initThemePlugin) {
        window.initThemePlugin();
    }
});