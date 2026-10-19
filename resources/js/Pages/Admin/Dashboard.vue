<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({ stats: Object, byCoin: Object, chart: Array, from: String, to: String });
const { t } = useI18n();
const money = (cents) => `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <AdminLayout>
        <SeoHead />
        <h1 class="font-display text-3xl">{{ t('admin.dashboard_title') }}</h1>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="glass p-4">
                <p class="font-mono text-[10px] uppercase text-muted">{{ t('admin.revenue') }}</p>
                <p class="mt-2 font-mono tabular text-2xl text-lime">{{ money(stats.revenue_usd_cents) }}</p>
            </article>
            <article class="glass p-4">
                <p class="font-mono text-[10px] uppercase text-muted">{{ t('admin.orders') }}</p>
                <p class="mt-2 font-mono tabular text-2xl">{{ stats.orders }}</p>
            </article>
            <article class="glass p-4">
                <p class="font-mono text-[10px] uppercase text-muted">{{ t('admin.paid') }}</p>
                <p class="mt-2 font-mono tabular text-2xl">{{ stats.paid }}</p>
            </article>
            <article class="glass p-4">
                <p class="font-mono text-[10px] uppercase text-muted">{{ t('admin.conversion') }}</p>
                <p class="mt-2 font-mono tabular text-2xl text-cyan">{{ stats.conversion }}%</p>
            </article>
        </div>
        <h2 class="mt-10 font-display text-xl">{{ t('admin.by_coin') }}</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-3">
            <article v-for="(row, coin) in byCoin" :key="coin" class="glass p-4">
                <p class="font-mono text-cyan">{{ coin }}</p>
                <p class="font-mono tabular text-lime">{{ money(row.usd_cents) }}</p>
                <p class="text-sm text-muted">{{ row.count }}</p>
            </article>
        </div>
        <h2 class="mt-10 font-display text-xl">{{ t('admin.chart') }}</h2>
        <div class="mt-3 flex h-40 items-end gap-1 border-b border-line pb-1">
            <div
                v-for="day in chart"
                :key="day.date"
                class="flex-1 bg-lime/70"
                :style="{ height: Math.max(4, (day.paid_usd_cents / 20000) * 100) + '%' }"
                :title="day.date"
            />
        </div>
    </AdminLayout>
</template>
