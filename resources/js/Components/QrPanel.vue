<script setup>
import { onMounted, ref, watch } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
    payload: { type: String, required: true },
});

const src = ref('');

const render = async () => {
    if (!props.payload) {
        return;
    }
    src.value = await QRCode.toDataURL(props.payload, {
        margin: 1,
        width: 360,
        color: { dark: '#030508', light: '#c6ff3d' },
    });
};

watch(() => props.payload, render);
onMounted(render);
</script>

<template>
    <img
        v-if="src"
        :src="src"
        alt="QR"
        class="mx-auto aspect-square w-full max-w-sm border border-lime bg-lime p-3 shadow-neon"
    />
</template>
