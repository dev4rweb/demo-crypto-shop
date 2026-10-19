<script setup>
import { Link } from '@inertiajs/vue3';
import { useCart } from '@/stores/cart';
import { useI18n } from '@/composables/useI18n';

const cart = useCart();
const { t, locRoute } = useI18n();

const money = (cents) =>
    `$${(cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <div v-if="cart.open" class="fixed inset-0 z-50">
        <button class="absolute inset-0 bg-black/70" :aria-label="t('common.close')" @click="cart.toggle(false)" />
        <aside
            class="absolute inset-y-0 end-0 flex w-full max-w-md flex-col border-s border-lime/40 bg-canvas shadow-glow"
        >
            <header class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-display text-lg tracking-wide">{{ t('cart.title') }}</h2>
                <button class="ghost-btn !px-3 !py-1" @click="cart.toggle(false)">{{ t('common.close') }}</button>
            </header>
            <div class="flex-1 space-y-4 overflow-y-auto p-5">
                <p v-if="!cart.items.length" class="text-muted">{{ t('cart.empty') }}</p>
                <article v-for="(item, index) in cart.items" :key="index" class="glass flex gap-3 p-3">
                    <img :src="item.image" :alt="item.name" class="h-16 w-16 object-cover" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ item.name }}</p>
                        <p class="font-mono tabular text-lime">{{ money(item.price_usd_cents) }}</p>
                        <div class="mt-2 flex items-center gap-2 font-mono text-sm">
                            <button type="button" @click="cart.setQty(index, item.quantity - 1)">−</button>
                            <span>{{ item.quantity }}</span>
                            <button type="button" @click="cart.setQty(index, item.quantity + 1)">+</button>
                            <button class="ms-auto text-danger" type="button" @click="cart.remove(index)">
                                {{ t('cart.remove') }}
                            </button>
                        </div>
                    </div>
                </article>
            </div>
            <footer class="border-t border-line p-5">
                <p class="mb-4 flex justify-between font-mono tabular text-lg">
                    <span>{{ t('common.total') }}</span>
                    <span class="text-lime">{{ money(cart.subtotal) }}</span>
                </p>
                <Link
                    :href="locRoute('checkout')"
                    class="neon-btn w-full"
                    :class="{ 'pointer-events-none opacity-40': !cart.items.length }"
                    @click="cart.toggle(false)"
                >
                    {{ t('cart.checkout') }}
                </Link>
            </footer>
        </aside>
    </div>
</template>
