<script setup>
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({ order: Object });
const { t, locRoute } = useI18n();
const money = (cents) => `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <section class="mx-auto max-w-3xl px-4 py-10">
            <Link :href="locRoute('welcome')" class="font-mono text-xs uppercase text-cyan">{{ t('pay.back_store') }}</Link>
            <h1 class="mt-3 font-display text-3xl">{{ t('guest_order.title') }}</h1>
            <p class="mt-2 font-mono text-sm text-muted">{{ order.uuid }}</p>
            <p class="mt-2 text-muted">{{ order.status }} · {{ money(order.total_usd_cents) }}</p>
            <ul class="mt-6 space-y-3">
                <li v-for="(item, i) in order.items" :key="i" class="glass p-4">
                    <p>{{ item.name }} × {{ item.quantity }}</p>
                    <p v-if="item.delivery_payload?.key" class="mt-2 font-mono text-lime">{{ t('pay.key') }}: {{ item.delivery_payload.key }}</p>
                    <a v-if="item.delivery_payload?.url" :href="item.delivery_payload.url" class="text-cyan">{{ item.delivery_payload.url }}</a>
                </li>
            </ul>
            <Link v-if="order.payment" :href="locRoute('checkout.pay', { payment: order.payment.uuid })" class="neon-btn mt-6 inline-flex">
                {{ t('account.open_invoice') }}
            </Link>
        </section>
    </ShopLayout>
</template>
