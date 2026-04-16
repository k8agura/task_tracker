import { computed, ref } from 'vue';

const STORAGE_KEY = 'theme-preference';
const theme = ref('light');

const getPreferredTheme = () => {
    const stored = window.localStorage.getItem(STORAGE_KEY);
    if (stored === 'light' || stored === 'dark') {
        return stored;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const applyTheme = (value) => {
    theme.value = value;
    document.documentElement.setAttribute('data-theme', value);
    window.localStorage.setItem(STORAGE_KEY, value);
};

export const initializeTheme = () => {
    applyTheme(getPreferredTheme());
};

export const useTheme = () => {
    const isDark = computed(() => theme.value === 'dark');

    const toggleTheme = () => {
        applyTheme(isDark.value ? 'light' : 'dark');
    };

    return {
        theme,
        isDark,
        toggleTheme,
    };
};
