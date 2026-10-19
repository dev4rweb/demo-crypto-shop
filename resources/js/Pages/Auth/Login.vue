<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const { t, locRoute } = useI18n();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(locRoute('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.login')" />
        <h1 class="font-display text-2xl">{{ t('auth.login') }}</h1>
        <p class="mt-2 text-sm text-muted">{{ t('auth.demo_hint') }}</p>
        <p v-if="status" class="mt-3 text-lime">{{ status }}</p>
        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <input v-model="form.email" type="email" required class="w-full border-line bg-transparent" placeholder="name@example.com" />
            <p v-if="form.errors.email" class="text-sm text-danger">{{ form.errors.email }}</p>
            <input v-model="form.password" type="password" required class="w-full border-line bg-transparent" :placeholder="t('common.password')" />
            <p v-if="form.errors.password" class="text-sm text-danger">{{ form.errors.password }}</p>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.remember" type="checkbox" />
                {{ t('auth.remember') }}
            </label>
            <button class="neon-btn w-full" :disabled="form.processing">{{ t('auth.login') }}</button>
        </form>
        <p class="mt-4 text-sm">
            <Link :href="locRoute('register')" class="text-cyan">{{ t('auth.no_account') }}</Link>
        </p>
    </GuestLayout>
</template>
