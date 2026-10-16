import { defineStore } from 'pinia';

const STORAGE = 'nexus-cart';

const load = () => {
    try {
        return JSON.parse(localStorage.getItem(STORAGE) || '[]');
    } catch {
        return [];
    }
};

export const useCart = defineStore('cart', {
    state: () => ({
        items: typeof window === 'undefined' ? [] : load(),
        open: false,
    }),
    getters: {
        count: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
        subtotal: (state) =>
            state.items.reduce((sum, item) => sum + item.price_usd_cents * item.quantity, 0),
    },
    actions: {
        persist() {
            try {
                localStorage.setItem(STORAGE, JSON.stringify(this.items));
            } catch {
                // ignore
            }
        },
        add(product, variant = null, quantity = 1) {
            const variantId = variant?.id ?? null;
            const price = variant?.price_usd_cents ?? product.price_usd_cents;
            const name = variant ? `${product.name} — ${variant.name}` : product.name;
            const existing = this.items.find(
                (row) => row.product_id === product.id && row.variant_id === variantId,
            );

            if (existing) {
                existing.quantity += quantity;
            } else {
                this.items.push({
                    product_id: product.id,
                    variant_id: variantId,
                    slug: product.slug,
                    name,
                    image: product.image,
                    price_usd_cents: price,
                    quantity,
                });
            }

            this.open = true;
            this.persist();
        },
        setQty(index, quantity) {
            if (quantity < 1) {
                this.items.splice(index, 1);
            } else {
                this.items[index].quantity = quantity;
            }
            this.persist();
        },
        remove(index) {
            this.items.splice(index, 1);
            this.persist();
        },
        clear() {
            this.items = [];
            this.persist();
        },
        toggle(force) {
            this.open = typeof force === 'boolean' ? force : !this.open;
        },
        payload() {
            return this.items.map((item) => ({
                product_id: item.product_id,
                variant_id: item.variant_id,
                quantity: item.quantity,
            }));
        },
    },
});
