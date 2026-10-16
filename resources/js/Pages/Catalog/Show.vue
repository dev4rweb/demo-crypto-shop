<script setup>
import { ref } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useCart } from '@/stores/cart';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({ product: Object });
const cart = useCart();
const { t } = useI18n();
const variantId = ref(props.product.variants?.[0]?.id ?? null);

const selected = () => props.product.variants?.find((v) => v.id === variantId.value) || null;
const price = () => selected()?.price_usd_cents ?? props.product.price_usd_cents;
const money = (cents) => `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <ShopLayout>
        <SeoHead :title="product.name" :description="product.description" />
        <section class="mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-2">
            <div class="glass overflow-hidden">
                <img :src="product.image" :alt="product.name" class="w-full object-cover" />
            </div>
            <div>
                <p class="font-mono text-[11px] uppercase tracking-[0.22em] text-cyan">{{ product.category_name }}</p>
                <h1 class="mt-2 font-display text-4xl">{{ product.name }}</h1>
                <p class="mt-4 text-muted">{{ product.description }}</p>
                <p class="mt-6 font-mono tabular text-4xl text-lime">{{ money(price()) }}</p>
                <p class="mt-2 font-mono text-xs uppercase text-muted">
                    {{ product.stock > 0 ? t('common.stock') : t('common.out') }} · {{ t('catalog.digital') }}
                </p>
                <div v-if="product.variants.length" class="mt-6">
                    <p class="mb-2 font-mono text-[11px] uppercase tracking-widest">{{ t('catalog.variants') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            class="ghost-btn !px-3 !py-1"
                            :class="{ 'border-lime text-lime': variantId === variant.id }"
                            @click="variantId = variant.id"
                        >
                            {{ variant.name }}
                        </button>
                    </div>
                </div>
                <button class="neon-btn mt-8" type="button" @click="cart.add(product, selected())">
                    {{ t('catalog.add') }}
                </button>
            </div>
        </section>
    </ShopLayout>
</template>
