<script setup>
import { useI18n } from '@/composables/useI18n';
import { useTheme } from '@/composables/useTheme';

const { theme, toggle } = useTheme();
const { t } = useI18n();
</script>

<template>
    <button
        type="button"
        class="ghost-btn !px-3 !py-2"
        :aria-label="theme === 'dark' ? t('theme.to_light') : t('theme.to_dark')"
        @click="toggle"
    >
        <span class="font-mono text-xs">{{ theme === 'dark' ? 'LT' : 'DK' }}</span>
    </button>
</template>
