<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useI18n } from '@/composables/useI18n';

const { t, locRoute } = useI18n();
const logout = useForm({});
const links = [
    ['admin.dashboard', 'admin.nav_dash'],
    ['admin.orders', 'admin.nav_orders'],
    ['admin.products', 'admin.nav_products'],
    ['admin.promos', 'admin.nav_promos'],
    ['admin.settings', 'admin.nav_settings'],
    ['admin.telegram', 'admin.nav_telegram'],
];
</script>

<template>
    <div class="min-h-screen md:flex">
        <aside class="border-b border-line bg-surface md:w-56 md:border-b-0 md:border-e">
            <Link :href="locRoute('welcome')" class="block px-5 py-4 font-display tracking-[0.28em] uppercase text-lime">
                Nexus
            </Link>
            <nav class="flex flex-wrap gap-2 px-4 pb-4 md:flex-col">
                <Link
                    v-for="[name, key] in links"
                    :key="name"
                    :href="locRoute(name)"
                    class="px-2 py-1 font-mono text-xs uppercase tracking-widest hover:text-lime"
                >
                    {{ t(key) }}
                </Link>
                <Link :href="locRoute('welcome')" class="px-2 py-1 font-mono text-xs uppercase tracking-widest text-muted">
                    {{ t('nav.home') }}
                </Link>
                <button class="px-2 py-1 text-start font-mono text-xs uppercase tracking-widest text-danger" @click="logout.post(locRoute('logout'))">
                    {{ t('nav.logout') }}
                </button>
            </nav>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="flex justify-end gap-2 border-b border-line px-4 py-3">
                <LocaleSwitcher />
                <ThemeToggle />
            </header>
            <div class="p-4 md:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
