<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useCart } from '@/stores/cart';
import { useI18n } from '@/composables/useI18n';

const cart = useCart();
const page = usePage();
const { t, locRoute } = useI18n();
const coins = computed(() => page.props.coins || ['USDT', 'BTC', 'ETH']);
const user = computed(() => page.props.auth?.user);

const form = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
    phone: '',
    address: '',
    delivery_type: 'digital',
    coin: coins.value[0] || 'USDT',
    promo: '',
    items: cart.payload(),
});

const quote = ref(null);

const money = (cents) => `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;

const refreshQuote = async () => {
    form.items = cart.payload();
    if (!form.items.length) {
        return;
    }
    const res = await fetch(locRoute('checkout.quote'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || page.props.csrf_token,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ items: form.items, promo: form.promo || null }),
    });
    if (res.ok) {
        quote.value = await res.json();
    }
};

const submit = () => {
    form.items = cart.payload();
    form.post(locRoute('checkout.store'), {
        onSuccess: () => cart.clear(),
    });
};
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <section class="mx-auto max-w-3xl px-4 py-10">
            <h1 class="font-display text-4xl">{{ t('checkout.title') }}</h1>
            <p v-if="!cart.items.length" class="mt-6 text-muted">
                {{ t('checkout.empty') }}
                <Link :href="locRoute('welcome')" class="text-lime">{{ t('nav.catalog') }}</Link>
            </p>
            <form v-else class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="glass space-y-4 p-5">
                    <h2 class="font-mono text-xs uppercase tracking-widest text-cyan">{{ t('checkout.contact') }}</h2>
                    <input v-model="form.name" required class="w-full border-line bg-transparent" :placeholder="t('common.name')" />
                    <input v-model="form.email" type="email" required class="w-full border-line bg-transparent" placeholder="name@example.com" />
                    <input v-model="form.phone" class="w-full border-line bg-transparent" :placeholder="t('checkout.phone_hint')" />
                    <p class="text-xs text-muted">{{ t('checkout.phone_hint') }}</p>
                    <select v-model="form.delivery_type" class="w-full border-line bg-canvas">
                        <option value="digital">{{ t('checkout.digital') }}</option>
                        <option value="physical">{{ t('checkout.physical') }}</option>
                    </select>
                    <textarea
                        v-if="form.delivery_type === 'physical'"
                        v-model="form.address"
                        class="w-full border-line bg-transparent"
                        :placeholder="t('checkout.address')"
                    />
                </div>
                <div class="glass space-y-3 p-5">
                    <h2 class="font-mono text-xs uppercase tracking-widest text-cyan">{{ t('checkout.coin') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="coin in coins"
                            :key="coin"
                            type="button"
                            class="ghost-btn"
                            :class="{ 'border-lime text-lime': form.coin === coin }"
                            @click="form.coin = coin"
                        >
                            {{ coin }}
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="form.promo" class="flex-1 border-line bg-transparent" :placeholder="t('checkout.promo')" />
                        <button type="button" class="ghost-btn" @click="refreshQuote">{{ t('checkout.apply') }}</button>
                    </div>
                </div>
                <div class="flex items-center justify-between font-mono tabular text-xl">
                    <span>{{ t('common.total') }}</span>
                    <span class="text-lime">{{ money(quote?.total_usd_cents ?? cart.subtotal) }}</span>
                </div>
                <p v-if="form.errors.items" class="text-danger">{{ form.errors.items }}</p>
                <p v-if="form.errors.promo" class="text-danger">{{ form.errors.promo }}</p>
                <button class="neon-btn w-full" :disabled="form.processing">{{ t('checkout.pay') }}</button>
            </form>
        </section>
    </ShopLayout>
</template>
