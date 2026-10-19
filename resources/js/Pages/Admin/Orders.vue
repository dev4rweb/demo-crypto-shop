<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({ orders: Object, filters: Object });
const { t, locRoute } = useI18n();
const q = ref(props.filters?.q || '');
const money = (cents) => `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
const go = (status) => router.get(locRoute('admin.orders'), { status, q: q.value }, { preserveState: true });
</script>

<template>
    <AdminLayout>
        <SeoHead />
        <h1 class="font-display text-3xl">{{ t('admin.orders_title') }}</h1>
        <div class="mt-4 flex flex-wrap gap-2">
            <input v-model="q" class="border-line bg-transparent" @keyup.enter="go(filters.status)" />
            <button class="ghost-btn !py-1" @click="go(null)">{{ t('common.any') }}</button>
            <button v-for="st in ['awaiting_payment','paid','expired','underpaid','overpaid','refunded']" :key="st" class="ghost-btn !py-1" @click="go(st)">{{ st }}</button>
        </div>
        <ul class="mt-6 space-y-2">
            <li v-for="order in orders.data" :key="order.id" class="glass flex flex-wrap justify-between gap-3 p-4">
                <Link :href="locRoute('admin.orders.show', { order: order.id })" class="font-mono">{{ order.uuid.slice(0,8) }} · {{ order.email }}</Link>
                <span>{{ order.status }} / {{ order.payment_status }}</span>
                <span class="font-mono text-lime">{{ money(order.total_usd_cents) }}</span>
            </li>
        </ul>
    </AdminLayout>
</template>
