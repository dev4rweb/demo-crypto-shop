import { ref } from 'vue';

const theme = ref(
    typeof document !== 'undefined' && document.documentElement.classList.contains('light')
        ? 'light'
        : 'dark',
);

const apply = (value) => {
    theme.value = value === 'light' ? 'light' : 'dark';

    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark', 'light');
    document.documentElement.classList.add(theme.value);
    document.documentElement.style.colorScheme = theme.value;

    try {
        localStorage.setItem('theme', theme.value);
    } catch (e) {
        // ignore
    }
};

const toggle = () => apply(theme.value === 'dark' ? 'light' : 'dark');

export function useTheme() {
    return { theme, toggle, apply };
}
