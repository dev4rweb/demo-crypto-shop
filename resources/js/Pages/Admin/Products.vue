<script setup>
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({ products: Array, categories: Array });
const { t, locRoute } = useI18n();
const form = useForm({
    name: '',
    slug: '',
    category_id: props.categories[0]?.id,
    type: 'template',
    price_usd_cents: 4900,
    stock: 10,
    is_digital: true,
    is_featured: false,
    is_active: true,
    bento_size: 'sm',
    image: '/images/products/neon-storefront.svg',
});
</script>

<template>
    <AdminLayout>
        <SeoHead />
        <h1 class="font-display text-3xl">{{ t('admin.products_title') }}</h1>
        <form class="glass mt-6 grid gap-3 p-4 md:grid-cols-2" @submit.prevent="form.post(locRoute('admin.products.store'))">
            <input v-model="form.name" class="border-line bg-transparent" placeholder="name" />
            <input v-model="form.slug" class="border-line bg-transparent" placeholder="slug" />
            <select v-model="form.category_id" class="border-line bg-canvas">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.slug }}</option>
            </select>
            <select v-model="form.type" class="border-line bg-canvas">
                <option value="template">template</option>
                <option value="license">license</option>
                <option value="course">course</option>
                <option value="gift_card">gift_card</option>
            </select>
            <input v-model.number="form.price_usd_cents" type="number" class="border-line bg-transparent" />
            <input v-model.number="form.stock" type="number" class="border-line bg-transparent" />
            <button class="neon-btn md:col-span-2" type="submit">{{ t('common.add') }}</button>
        </form>
        <ul class="mt-6 space-y-2">
            <li v-for="product in products" :key="product.id" class="glass flex justify-between p-3">
                <span>{{ product.slug }} · {{ product.price_usd_cents }}¢ · stock {{ product.stock }}</span>
                <button class="text-danger" type="button" @click="useForm({}).delete(locRoute('admin.products.destroy', { product: product.id }))">
                    {{ t('common.delete') }}
                </button>
            </li>
        </ul>
    </AdminLayout>
</template>
