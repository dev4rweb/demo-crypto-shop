import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Nexus';

const applyDocumentLocale = (props) => {
    const locale = props?.locale || 'en';
    const dir = props?.dir || (locale === 'ar' ? 'rtl' : 'ltr');
    document.documentElement.lang = locale;
    document.documentElement.dir = dir;
};

createInertiaApp({
    title: (title) => {
        if (!title) {
            return appName;
        }

        return title.includes(appName) ? title : `${title} · ${appName}`;
    },
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        applyDocumentLocale(props.initialPage?.props);

        router.on('navigate', (event) => {
            applyDocumentLocale(event.detail.page.props);
        });

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#c6ff3d',
    },
});
