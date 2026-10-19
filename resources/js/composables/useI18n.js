import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useI18n() {
    const page = usePage();

    const t = (key, fallback = key) => {
        const dict = page.props.translations || {};
        const value = key.split('.').reduce((carry, part) => {
            if (carry && typeof carry === 'object' && part in carry) {
                return carry[part];
            }
            return null;
        }, dict);

        return value ?? fallback;
    };

    const locale = computed(() => page.props.locale || 'en');
    const routeLocale = computed(() => page.props.routeLocale || null);
    const dir = computed(() => page.props.dir || 'ltr');
    const locales = computed(() => page.props.locales || []);

    const locRoute = (name, params = {}, absolute) => {
        if (routeLocale.value) {
            return route(`locale.${name}`, { locale: routeLocale.value, ...params }, absolute);
        }

        return route(name, params, absolute);
    };

    const switchLocale = (code) => {
        let path = window.location.pathname;
        const match = path.match(/^\/(en|zh|fr|es|ar|pt|ru)(?=\/|$)/);

        if (match) {
            path = path.slice(match[0].length) || '/';
        }

        const next = `/${code}${path === '/' ? '' : path}${window.location.search}`;
        router.visit(next);
    };

    return { t, locale, routeLocale, dir, locales, locRoute, switchLocale };
}
