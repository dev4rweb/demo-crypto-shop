<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProductCard from '@/Components/ProductCard.vue';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    products: Array,
    categories: Array,
    filters: Object,
    demoLogins: Array,
});

const { t, locRoute } = useI18n();
const q = ref(props.filters?.q || '');

const apply = (extra = {}) => {
    router.get(
        locRoute('welcome'),
        { ...props.filters, q: q.value, ...extra },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <section class="mx-auto max-w-6xl px-4 py-10 md:py-16">
            <p class="font-mono text-[11px] uppercase tracking-[0.28em] text-cyan">{{ t('welcome.kicker') }}</p>
            <h1 class="mt-3 max-w-4xl font-display text-4xl leading-[0.95] md:text-6xl">
                {{ t('welcome.title') }}
            </h1>
            <p class="mt-5 max-w-2xl text-muted md:text-lg">{{ t('welcome.tagline') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#catalog" class="neon-btn">{{ t('welcome.cta') }}</a>
                <Link :href="locRoute('about')" class="ghost-btn">{{ t('welcome.about_cta') }}</Link>
            </div>

            <div class="mt-12 grid gap-3 md:grid-cols-3">
                <article
                    v-for="login in demoLogins"
                    :key="login.email"
                    class="glass p-4"
                >
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-lime">{{ t('welcome.' + login.role) }}</p>
                    <p class="mt-2 font-mono text-sm">{{ login.email }}</p>
                    <p class="font-mono text-sm text-muted">{{ login.password }}</p>
                </article>
            </div>
            <p class="mt-3 text-sm text-muted">{{ t('welcome.demo_body') }}</p>
        </section>

        <section id="catalog" class="mx-auto max-w-6xl px-4 pb-16">
            <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center">
                <input
                    v-model="q"
                    class="w-full border-line bg-transparent font-mono text-sm focus:border-lime focus:ring-0 md:max-w-sm"
                    :placeholder="t('catalog.search_placeholder')"
                    @keyup.enter="apply()"
                />
                <div class="flex flex-wrap gap-2">
                    <button class="ghost-btn !px-3 !py-1 text-[11px]" :class="{ 'border-lime text-lime': !filters.category }" @click="apply({ category: null })">
                        {{ t('common.any') }}
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.slug"
                        class="ghost-btn !px-3 !py-1 text-[11px]"
                        :class="{ 'border-lime text-lime': filters.category === cat.slug }"
                        @click="apply({ category: cat.slug })"
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <ProductCard v-for="product in products" :key="product.id" :product="product" />
            </div>
        </section>
    </ShopLayout>
</template>
