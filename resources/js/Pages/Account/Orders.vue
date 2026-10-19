<script setup>
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({ orders: Array });
const { t, locRoute } = useI18n();
const money = (cents) => `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <section class="mx-auto max-w-3xl px-4 py-10">
            <h1 class="font-display text-4xl">{{ t('account.title') }}</h1>
            <p v-if="!orders.length" class="mt-6 text-muted">{{ t('account.empty') }}</p>
            <ul class="mt-6 space-y-3">
                <li v-for="order in orders" :key="order.uuid" class="glass flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <Link :href="locRoute('account.orders.show', { uuid: order.uuid })" class="font-mono text-sm">
                            {{ order.uuid.slice(0, 8) }}
                        </Link>
                        <p class="text-muted">{{ order.status }} · {{ order.coin }}</p>
                    </div>
                    <p class="font-mono tabular text-lime">{{ money(order.total_usd_cents) }}</p>
                    <Link v-if="order.payment_id" :href="locRoute('checkout.pay', { payment: order.payment_id })" class="ghost-btn !py-1">
                        {{ t('account.open_invoice') }}
                    </Link>
                </li>
            </ul>
        </section>
    </ShopLayout>
</template>
