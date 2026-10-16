<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const page = usePage();
const { t } = useI18n();

const rows = computed(() => {
    const rates = page.props.rates || {};
    return ['USDT', 'BTC', 'ETH'].map((coin) => ({
        coin,
        usd: Number(rates[coin] || 0),
    }));
});

const loop = computed(() => [...rows.value, ...rows.value, ...rows.value, ...rows.value]);
</script>

<template>
    <div class="overflow-hidden border-b border-line bg-black/40 font-mono text-[11px] uppercase tracking-[0.22em] text-cyan">
        <div class="flex w-max animate-ticker gap-10 py-2 whitespace-nowrap">
            <span v-for="(row, i) in loop" :key="i" class="tabular">
                {{ t('ticker.label') }} · {{ row.coin }} {{ row.usd.toLocaleString('en-US', { maximumFractionDigits: row.coin === 'USDT' ? 4 : 0 }) }}
            </span>
        </div>
    </div>
</template>
