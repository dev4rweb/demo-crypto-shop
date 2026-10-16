<script setup>
import ShopLayout from '@/Layouts/ShopLayout.vue';
</script>

<template>
    <ShopLayout>
        <div class="mx-auto max-w-4xl px-4 py-10">
            <header v-if="$slots.header" class="mb-6 font-display text-2xl">
                <slot name="header" />
            </header>
            <slot />
        </div>
    </ShopLayout>
</template>
