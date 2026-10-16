<script setup>
import ShopLayout from '@/Layouts/ShopLayout.vue';
</script>

<template>
    <ShopLayout>
        <div class="mx-auto max-w-md px-4 py-16">
            <div class="glass p-6">
                <slot />
            </div>
        </div>
    </ShopLayout>
</template>
