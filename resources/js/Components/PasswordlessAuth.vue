<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    compact: { type: Boolean, default: false },
});

const page = usePage();
const { t } = useI18n();
const googleEnabled = computed(() => Boolean(page.props.googleEnabled));
const googleClientId = computed(() => page.props.googleClientId || '');
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const magic = useForm({
    email: '',
    intended: typeof window !== 'undefined' ? window.location.href : '',
});

const googleHref = computed(() => {
    const base = route('auth.google.redirect');
    const intended = typeof window !== 'undefined' ? window.location.href : '';
    return intended ? `${base}?intended=${encodeURIComponent(intended)}` : base;
});

const sendMagic = () => {
    magic.intended = typeof window !== 'undefined' ? window.location.href : '';
    magic.post(route('auth.magic.send'), { preserveScroll: true });
};

onMounted(() => {
    if (!googleClientId.value || page.props.auth?.user) {
        return;
    }

    const existing = document.querySelector('script[data-google-gsi="1"]');
    const start = () => {
        if (!window.google?.accounts?.id) {
            return;
        }
        window.google.accounts.id.initialize({
            client_id: googleClientId.value,
            callback: (response) => {
                if (!response?.credential) {
                    return;
                }
                router.post(
                    route('auth.google.one-tap'),
                    {
                        credential: response.credential,
                        intended: typeof window !== 'undefined' ? window.location.href : '',
                    },
                    { preserveScroll: true },
                );
            },
            auto_select: false,
            cancel_on_tap_outside: true,
            context: 'signin',
            itp_support: true,
        });
        window.google.accounts.id.prompt();
    };

    if (existing) {
        start();
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.dataset.googleGsi = '1';
    script.onload = start;
    document.head.appendChild(script);
});
</script>

<template>
    <div class="space-y-3">
        <p v-if="!compact" class="text-sm text-muted">{{ t('auth.passwordless_hint') }}</p>
        <a
            v-if="googleEnabled"
            :href="googleHref"
            class="ghost-btn flex w-full items-center justify-center gap-2"
        >
            {{ t('auth.google_continue') }}
        </a>
        <form class="space-y-3" @submit.prevent="sendMagic">
            <input
                v-model="magic.email"
                type="email"
                required
                class="w-full border-line bg-transparent"
                placeholder="name@example.com"
            />
            <p v-if="magic.errors.email" class="text-sm text-danger">{{ magic.errors.email }}</p>
            <button class="ghost-btn w-full" type="submit" :disabled="magic.processing">
                {{ t('auth.magic_send') }}
            </button>
        </form>
        <p v-if="flashSuccess" class="text-sm text-lime">{{ flashSuccess }}</p>
        <p v-if="flashError" class="text-sm text-danger">{{ flashError }}</p>
    </div>
</template>
