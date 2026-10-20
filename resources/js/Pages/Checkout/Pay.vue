<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import QrPanel from '@/Components/QrPanel.vue';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({ payment: Object });
const page = usePage();
const { t, locRoute } = useI18n();
const current = ref(props.payment);
const user = computed(() => page.props.auth?.user);
let timer;

const statusLabel = computed(() => t(`pay.${current.value.status}`) || current.value.status);
const paid = computed(() => ['confirmed', 'overpaid'].includes(current.value.status));
const mmss = computed(() => {
    const s = Math.max(0, current.value.seconds_left || 0);
    const m = Math.floor(s / 60);
    const r = s % 60;
    return `${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
});

const copy = async (value) => {
    try {
        await navigator.clipboard.writeText(value);
    } catch {
        // ignore
    }
};

const refresh = async () => {
    const res = await fetch(locRoute('checkout.status', { payment: current.value.uuid }), {
        headers: { Accept: 'application/json' },
    });
    if (res.ok) {
        current.value = await res.json();
    }
};

const simulate = async () => {
    const token = document.querySelector('meta[name=csrf-token]')?.content;
    const res = await fetch(locRoute('checkout.simulate', { payment: current.value.uuid }), {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    if (res.ok) {
        current.value = await res.json();
    }
};

onMounted(() => {
    timer = setInterval(() => {
        if (['waiting', 'detected', 'confirming', 'underpaid'].includes(current.value.status)) {
            refresh();
            if (current.value.seconds_left > 0) {
                current.value.seconds_left -= 2;
            }
        }
    }, 2000);
});

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <section class="mx-auto max-w-3xl px-4 py-8 text-center">
            <p class="font-mono text-[11px] uppercase tracking-[0.28em] text-cyan">{{ current.coin }} · {{ current.network }}</p>
            <h1 class="mt-3 font-display text-3xl md:text-5xl">{{ t('pay.title') }}</h1>
            <p class="mt-3 font-mono text-sm uppercase tracking-widest" :class="paid ? 'text-lime' : 'text-cyan'">
                <span class="inline-block h-2 w-2 animate-pulseRing rounded-full bg-lime align-middle" />
                {{ statusLabel }}
                <template v-if="current.status === 'confirming'">
                    {{ current.confirmations }}/{{ current.required_confirmations }}
                </template>
            </p>
            <p class="mt-2 font-mono tabular text-lime">{{ t('pay.expires') }} {{ mmss }}</p>

            <div class="mt-8">
                <QrPanel :payload="current.qr_payload" />
            </div>

            <div class="mt-8 space-y-3 text-start">
                <div class="glass flex items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-mono text-[10px] uppercase text-muted">{{ t('pay.amount') }}</p>
                        <p class="font-mono tabular text-2xl text-lime">{{ current.amount_crypto }} {{ current.coin }}</p>
                    </div>
                    <button class="ghost-btn" type="button" @click="copy(current.amount_crypto)">{{ t('pay.copy_amount') }}</button>
                </div>
                <div class="glass p-4">
                    <p class="font-mono text-[10px] uppercase text-muted">{{ t('pay.address') }}</p>
                    <p class="break-all font-mono text-sm">{{ current.address }}</p>
                    <button class="ghost-btn mt-3" type="button" @click="copy(current.address)">{{ t('pay.copy_address') }}</button>
                </div>
            </div>

            <button
                v-if="!paid && current.status !== 'expired'"
                class="neon-btn mt-8"
                type="button"
                @click="simulate"
            >
                {{ t('pay.simulate') }}
            </button>
            <p class="mt-3 text-xs text-muted">{{ t('pay.simulate_hint') }}</p>

            <div v-if="paid" class="glass mt-10 space-y-4 p-5 text-start">
                <h2 class="font-display text-2xl text-lime">{{ t('pay.delivered') }}</h2>
                <article v-for="(item, i) in current.order.items" :key="i" class="border border-line p-3">
                    <p>{{ item.name }}</p>
                    <p v-if="item.delivery_payload?.key" class="mt-2 font-mono text-lime">{{ t('pay.key') }}: {{ item.delivery_payload.key }}</p>
                    <a v-if="item.delivery_payload?.url" :href="item.delivery_payload.url" class="text-cyan">{{ item.delivery_payload.url }}</a>
                </article>
            </div>

            <p v-if="paid && current.order_url && !user" class="mt-6 text-sm">
                <a :href="current.order_url" class="text-cyan">{{ t('pay.open_order') }}</a>
            </p>
            <div class="mt-8 flex justify-center gap-4">
                <Link :href="locRoute('welcome')" class="ghost-btn">{{ t('pay.back_store') }}</Link>
                <Link v-if="user" :href="locRoute('account.orders')" class="ghost-btn">{{ t('pay.orders') }}</Link>
                <a v-else-if="current.order_url" :href="current.order_url" class="ghost-btn">{{ t('pay.open_order') }}</a>
            </div>
        </section>
    </ShopLayout>
</template>
