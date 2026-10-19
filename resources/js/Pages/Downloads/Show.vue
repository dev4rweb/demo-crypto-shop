<script setup>
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    name: String,
    fileName: String,
    downloadUrl: String,
});
const { t, locRoute } = useI18n();
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <section class="mx-auto max-w-xl px-4 py-16 text-center">
            <p class="font-mono text-[11px] uppercase tracking-[0.28em] text-cyan">{{ t('common.demo') }}</p>
            <h1 class="mt-3 font-display text-4xl">{{ t('downloads.title') }}</h1>
            <p class="mt-4 text-muted">{{ name }}</p>
            <p class="mt-2 text-sm text-muted">{{ t('downloads.body') }}</p>
            <a :href="downloadUrl" class="neon-btn mt-8 inline-flex">{{ t('downloads.file') }} · {{ fileName }}</a>
            <div class="mt-6">
                <Link :href="locRoute('welcome')" class="ghost-btn">{{ t('pay.back_store') }}</Link>
            </div>
        </section>
    </ShopLayout>
</template>
