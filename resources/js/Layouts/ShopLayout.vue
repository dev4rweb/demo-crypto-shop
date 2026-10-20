<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CartDrawer from '@/Components/CartDrawer.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import RateTicker from '@/Components/RateTicker.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useCart } from '@/stores/cart';
import { useI18n } from '@/composables/useI18n';

const page = usePage();
const cart = useCart();
const { t, locRoute } = useI18n();
const menu = ref(false);
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <div class="min-h-screen">
        <RateTicker />
        <header class="sticky top-0 z-40 border-b border-line bg-canvas/80 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                <Link :href="locRoute('welcome')" class="font-display text-lg tracking-[0.28em] uppercase text-lime">
                    Nexus
                </Link>
                <nav class="hidden items-center gap-5 text-sm uppercase tracking-[0.18em] md:flex">
                    <Link :href="locRoute('welcome')">{{ t('nav.catalog') }}</Link>
                    <Link :href="locRoute('about')">{{ t('nav.about') }}</Link>
                    <Link v-if="user?.is_staff" :href="locRoute('admin.dashboard')">{{ t('nav.admin') }}</Link>
                    <Link v-else-if="user" :href="locRoute('account.orders')">{{ t('nav.account') }}</Link>
                    <Link v-else :href="locRoute('login')">{{ t('nav.login') }}</Link>
                </nav>
                <div class="flex items-center gap-2">
                    <LocaleSwitcher />
                    <ThemeToggle />
                    <button type="button" class="ghost-btn !px-3 !py-2 font-mono" @click="cart.toggle(true)">
                        {{ t('nav.cart') }} {{ cart.count }}
                    </button>
                    <button type="button" class="ghost-btn md:hidden !px-3 !py-2" @click="menu = !menu">
                        {{ t('nav.menu') }}
                    </button>
                </div>
            </div>
            <div v-if="menu" class="space-y-3 border-t border-line px-4 py-4 md:hidden">
                <Link class="block" :href="locRoute('welcome')" @click="menu = false">{{ t('nav.catalog') }}</Link>
                <Link class="block" :href="locRoute('about')" @click="menu = false">{{ t('nav.about') }}</Link>
                <Link v-if="user?.is_staff" class="block" :href="locRoute('admin.dashboard')" @click="menu = false">{{ t('nav.admin') }}</Link>
                <Link v-else-if="user" class="block" :href="locRoute('account.orders')" @click="menu = false">{{ t('nav.account') }}</Link>
                <Link v-else class="block" :href="locRoute('login')" @click="menu = false">{{ t('nav.login') }}</Link>
            </div>
        </header>
        <main>
            <slot />
        </main>
        <CartDrawer />
        <footer class="mt-16 border-t border-line px-4 py-8 text-center font-mono text-[11px] uppercase tracking-[0.2em] text-muted">
            <p>Nexus · demo-crypto-shop.dev4rweb.com</p>
            <p class="mt-3 flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                <Link :href="locRoute('privacy')" class="text-cyan">{{ t('nav.privacy') }}</Link>
                <Link :href="locRoute('terms')" class="text-cyan">{{ t('nav.terms') }}</Link>
            </p>
        </footer>
    </div>
</template>
