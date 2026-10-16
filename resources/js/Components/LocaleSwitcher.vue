<script setup>
import { useI18n } from '@/composables/useI18n';

const { t, locale, locales, switchLocale } = useI18n();
</script>

<template>
    <label class="sr-only" for="locale-switch">{{ t('theme.language') }}</label>
    <select
        id="locale-switch"
        class="border-line bg-transparent font-mono text-xs uppercase tracking-widest text-ink focus:border-lime focus:ring-0"
        :value="locale"
        @change="switchLocale($event.target.value)"
    >
        <option v-for="item in locales" :key="item.code" :value="item.code">
            {{ item.code }}
        </option>
    </select>
</template>
