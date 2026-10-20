<script setup>
import { computed } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    page: { type: String, required: true },
});

const { t } = useI18n();
const key = computed(() => `legal.${props.page}`);
const sections = computed(() => {
    const value = t(`${key.value}.sections`);
    return Array.isArray(value) ? value : [];
});
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <article class="mx-auto max-w-3xl px-4 py-12">
            <p class="font-mono text-[11px] uppercase tracking-[0.28em] text-cyan">{{ t(`${key}.kicker`) }}</p>
            <h1 class="mt-3 font-display text-4xl md:text-5xl">{{ t(`${key}.title`) }}</h1>
            <p class="mt-3 font-mono text-[11px] uppercase tracking-widest text-muted">{{ t(`${key}.updated`) }}</p>
            <p class="mt-6 text-lg text-muted">{{ t(`${key}.lead`) }}</p>

            <section v-for="(block, i) in sections" :key="i" class="mt-10">
                <h2 class="font-display text-2xl">{{ block.title }}</h2>
                <p class="mt-3 whitespace-pre-line text-muted">{{ block.body }}</p>
            </section>

            <p class="mt-12 glass p-5 text-muted">
                {{ t(`${key}.contact`) }}
                <a href="mailto:dev4rweb@gmail.com" class="text-cyan">dev4rweb@gmail.com</a>
            </p>
        </article>
    </ShopLayout>
</template>
