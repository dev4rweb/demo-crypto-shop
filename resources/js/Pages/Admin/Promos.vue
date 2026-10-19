<script setup>
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({ promos: Array });
const { t, locRoute } = useI18n();
const form = useForm({ code: '', type: 'percent', value: 10, max_uses: 100, is_active: true });
</script>

<template>
    <AdminLayout>
        <SeoHead />
        <h1 class="font-display text-3xl">{{ t('admin.promos_title') }}</h1>
        <form class="glass mt-6 flex flex-wrap gap-3 p-4" @submit.prevent="form.post(locRoute('admin.promos.store'))">
            <input v-model="form.code" class="border-line bg-transparent" placeholder="CODE" />
            <select v-model="form.type" class="border-line bg-canvas">
                <option value="percent">percent</option>
                <option value="fixed">fixed ¢</option>
            </select>
            <input v-model.number="form.value" type="number" class="w-24 border-line bg-transparent" />
            <button class="neon-btn" type="submit">{{ t('common.add') }}</button>
        </form>
        <ul class="mt-4 space-y-2">
            <li v-for="promo in promos" :key="promo.id" class="glass p-3 font-mono">
                {{ promo.code }} · {{ promo.type }} {{ promo.value }} · {{ promo.uses }}/{{ promo.max_uses ?? '∞' }}
            </li>
        </ul>
    </AdminLayout>
</template>
