<script setup>
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({ connected: Boolean, connectUrl: String, enabled: Boolean });
const { t, locRoute } = useI18n();
const form = useForm({});
</script>

<template>
    <AdminLayout>
        <SeoHead />
        <h1 class="font-display text-3xl">{{ t('telegram.title') }}</h1>
        <p v-if="!enabled" class="mt-4 text-muted">{{ t('telegram.disabled') }}</p>
        <div v-else class="mt-6 space-y-4">
            <p v-if="connected" class="text-lime">{{ t('telegram.connected') }}</p>
            <p v-else class="text-muted">{{ t('telegram.hint') }}</p>
            <a v-if="connectUrl && !connected" :href="connectUrl" class="neon-btn inline-flex" target="_blank" rel="noreferrer">
                {{ t('telegram.connect') }}
            </a>
            <button v-if="connected" class="ghost-btn" type="button" @click="form.post(locRoute('admin.telegram.disconnect'))">
                {{ t('telegram.disconnect') }}
            </button>
        </div>
    </AdminLayout>
</template>
