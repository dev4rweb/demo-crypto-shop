<script setup>
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({ order: Object });
const { t, locRoute } = useI18n();
const form = useForm({ fulfillment_status: props.order.fulfillment_status });
</script>

<template>
    <AdminLayout>
        <SeoHead />
        <h1 class="font-display text-2xl">{{ order.uuid }}</h1>
        <p class="mt-2 text-muted">{{ order.email }} · {{ order.status }}</p>
        <form class="mt-6 flex gap-2" @submit.prevent="form.patch(locRoute('admin.orders.fulfillment', { order: order.id }))">
            <select v-model="form.fulfillment_status" class="border-line bg-canvas">
                <option value="pending">pending</option>
                <option value="delivered">delivered</option>
                <option value="refunded">refunded</option>
            </select>
            <button class="neon-btn" type="submit">{{ t('common.save') }}</button>
        </form>
        <h2 class="mt-8 font-display text-xl">{{ t('admin.events') }}</h2>
        <ul class="mt-3 space-y-2 font-mono text-xs">
            <li v-for="event in order.events" :key="event.id" class="glass p-3">
                {{ event.created_at }} · {{ event.type }} · sig {{ event.signature_ok ? 'ok' : 'no' }}
            </li>
        </ul>
    </AdminLayout>
</template>
