<script setup>
import { Link } from '@inertiajs/vue3';
import { useCart } from '@/stores/cart';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    product: { type: Object, required: true },
    featured: { type: Boolean, default: false },
});

const cart = useCart();
const { t, locRoute } = useI18n();

const money = (cents) =>
    `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;

const span = () => {
    if (props.product.bento_size === 'lg') return 'md:col-span-2 md:row-span-2';
    if (props.product.bento_size === 'md') return 'md:col-span-2';
    return '';
};
</script>

<template>
    <article class="tilt-card glass group relative flex h-full flex-col overflow-hidden" :class="span()">
        <Link :href="locRoute('catalog.show', { slug: product.slug })" class="block">
            <img
                :src="product.image"
                :alt="product.name"
                class="h-40 w-full object-cover md:h-48"
                :class="{ 'md:h-72': product.bento_size === 'lg' }"
            />
        </Link>
        <div class="flex flex-1 flex-col gap-3 p-4">
            <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-cyan">
                {{ product.category_name }}
            </p>
            <Link :href="locRoute('catalog.show', { slug: product.slug })">
                <h3 class="font-display text-xl leading-tight">{{ product.name }}</h3>
            </Link>
            <p class="line-clamp-2 text-sm text-muted">{{ product.description }}</p>
            <div class="mt-auto flex items-end justify-between gap-3">
                <p class="font-mono tabular text-2xl text-lime">{{ money(product.price_usd_cents) }}</p>
                <button type="button" class="neon-btn !px-3 !py-2 text-[11px]" @click="cart.add(product)">
                    {{ t('catalog.add') }}
                </button>
            </div>
        </div>
    </article>
</template>
