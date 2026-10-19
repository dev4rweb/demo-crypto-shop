<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const page = usePage();
const { t, locRoute } = useI18n();
const demoLogins = computed(() => page.props.demoLogins || []);
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const fillDemo = (account) => {
    form.email = account.email;
    form.password = account.password;
};

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
        <div v-if="demoLogins.length" class="mt-4 space-y-2">
            <button
                v-for="account in demoLogins"
                :key="account.email"
                type="button"
                class="ghost-btn w-full justify-between text-start"
                @click="fillDemo(account)"
            >
                <span class="font-mono text-[10px] uppercase tracking-widest text-lime">{{ t('welcome.' + account.role) }}</span>
                <span class="font-mono text-xs">{{ account.email }}</span>
                <span class="text-xs text-muted">{{ t('auth.fill') }}</span>
            </button>
            <p class="font-mono text-[11px] text-muted">{{ t('common.password') }}: demo123456</p>
        </div>
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
