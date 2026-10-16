<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>

<template>
    <ShopLayout>
        <SeoHead />
        <article class="mx-auto max-w-3xl px-4 py-12">
            <p class="font-mono text-[11px] uppercase tracking-[0.28em] text-cyan">{{ t('about.kicker') }}</p>
            <h1 class="mt-3 font-display text-4xl md:text-5xl">{{ t('about.title') }}</h1>
            <p class="mt-6 text-lg text-muted">{{ t('about.lead') }}</p>

            <h2 class="mt-12 font-display text-2xl">{{ t('about.for_title') }}</h2>
            <p class="mt-3 text-muted">{{ t('about.for_body') }}</p>

            <h2 class="mt-12 font-display text-2xl">{{ t('about.scenes_title') }}</h2>
            <ul class="mt-4 space-y-3">
                <li v-for="(scene, i) in t('about.scenes')" :key="i" class="glass p-4">{{ scene }}</li>
            </ul>

            <h2 class="mt-12 font-display text-2xl">{{ t('about.roles_title') }}</h2>
            <dl class="mt-4 space-y-4">
                <div v-for="(body, role) in t('about.roles')" :key="role" class="glass p-4">
                    <dt class="font-mono text-xs uppercase tracking-widest text-lime">{{ role }}</dt>
                    <dd class="mt-2 text-muted">{{ body }}</dd>
                </div>
            </dl>

            <h2 class="mt-12 font-display text-2xl">{{ t('about.try_title') }}</h2>
            <ol class="mt-4 list-decimal space-y-2 ps-5 text-muted">
                <li v-for="(step, i) in t('about.try')" :key="i">{{ step }}</li>
            </ol>

            <p class="mt-16 border-t border-line pt-6 font-mono text-xs text-muted">
                <strong class="block uppercase tracking-widest text-cyan">{{ t('about.stack_title') }}</strong>
                {{ t('about.stack') }}
            </p>
        </article>
    </ShopLayout>
</template>
