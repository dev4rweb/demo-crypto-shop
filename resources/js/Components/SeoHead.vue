<script setup>
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
});

const page = usePage();
</script>

<template>
    <Head :title="title || page.props.seo?.title">
        <meta
            head-key="description"
            name="description"
            :content="description || page.props.seo?.description"
        />
    </Head>
</template>
