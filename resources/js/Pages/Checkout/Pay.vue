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
const busy = ref(false);
let timer;
let pollMs = 2000;

const statusLabel = computed(() => t(`pay.${current.value.status}`) || current.value.status);
const paid = computed(() => ['confirmed', 'overpaid'].includes(current.value.status));
const mmss = computed(() => {
    const s = Math.max(0, current.value.seconds_left || 0);
    const m = Math.floor(s / 60);
    const r = s % 60;
    return `${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
});

const paymentKey = () => {
    if (current.value?.uuid) {
        return current.value.uuid;
    }

    const parts = window.location.pathname.split('/').filter(Boolean);
    const payAt = parts.lastIndexOf('pay');

    return payAt >= 0 ? parts[payAt + 1] : parts.at(-1);
};

const payActionUrl = (action) => {
    const key = paymentKey();
    const name = action === 'simulate' ? 'checkout.simulate' : 'checkout.status';

    if (key) {
        try {
            return locRoute(name, { payment: key }, false);
        } catch {
            // Same-origin fallback when Ziggy rejects the UUID or locale.
        }
    }

    return `${window.location.pathname.replace(/\/$/, '')}/${action}`;
};

const csrfHeaders = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
        || page.props.csrf_token
        || '';
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (token) {
        headers['X-CSRF-TOKEN'] = token;
    }

    return { headers, token };
};

const copy = async (value) => {
    try {
        await navigator.clipboard.writeText(value);
    } catch {
        // ignore
    }
};

const refresh = async () => {
    try {
        const res = await fetch(payActionUrl('status'), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            current.value = await res.json();
        }
    } catch {
        // keep the last known invoice
    }
};

const restartTimer = () => {
    clearInterval(timer);
    timer = setInterval(() => {
        if (['waiting', 'detected', 'confirming', 'underpaid'].includes(current.value.status)) {
            refresh();
            if (current.value.seconds_left > 0) {
                current.value.seconds_left -= pollMs / 1000;
            }
        }
    }, pollMs);
};

const simulate = async () => {
    if (busy.value || paid.value || current.value.status === 'expired') {
        return;
    }

    busy.value = true;

    try {
        const { headers, token } = csrfHeaders();
        const res = await fetch(payActionUrl('simulate'), {
            method: 'POST',
            headers: {
                ...headers,
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(token ? { _token: token } : {}),
        });
        if (res.ok) {
            current.value = await res.json();
            pollMs = 1000;
            restartTimer();
        }
    } catch {
        // next status poll still advances a started simulation
    } finally {
        busy.value = false;
    }
};

onMounted(() => {
    restartTimer();
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
                :disabled="busy"
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
