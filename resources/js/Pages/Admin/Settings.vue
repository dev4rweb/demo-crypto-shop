<script setup>
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({ settings: Object });
const { t, locRoute } = useI18n();
const form = useForm({ ...props.settings });
</script>

<template>
    <AdminLayout>
        <SeoHead />
        <h1 class="font-display text-3xl">{{ t('admin.settings_title') }}</h1>
        <form class="glass mt-6 max-w-lg space-y-4 p-5" @submit.prevent="form.post(locRoute('admin.settings.update'))">
            <label class="block">
                <span class="font-mono text-xs uppercase">{{ t('admin.coins') }}</span>
                <input v-model="form.coins" class="mt-1 w-full border-line bg-transparent" />
            </label>
            <label class="block">
                <span class="font-mono text-xs uppercase">{{ t('admin.ttl') }}</span>
                <input v-model.number="form.invoice_ttl_minutes" type="number" class="mt-1 w-full border-line bg-transparent" />
            </label>
            <label class="block">
                <span class="font-mono text-xs uppercase">{{ t('admin.tolerance') }}</span>
                <input v-model.number="form.underpayment_tolerance_percent" type="number" step="0.1" class="mt-1 w-full border-line bg-transparent" />
            </label>
            <label class="block">
                <span class="font-mono text-xs uppercase">{{ t('admin.driver') }}</span>
                <select v-model="form.payment_driver" class="mt-1 w-full border-line bg-canvas">
                    <option value="testnet">testnet</option>
                    <option value="nowpayments">nowpayments</option>
                    <option value="cryptomus">cryptomus</option>
                </select>
            </label>
            <button class="neon-btn" type="submit">{{ t('common.save') }}</button>
        </form>
    </AdminLayout>
</template>
