import { ref, computed } from 'vue';

const currentUser = ref(null);
const userLoaded = ref(false);

export function useAuthState() {
    const isAdmin = computed(() => {
        return Array.isArray(currentUser.value?.roles) &&
            currentUser.value.roles.some(role => role.name === 'admin');
    });

    return {
        currentUser,
        userLoaded,
        isAdmin,
    };
}

export async function loadCurrentUser() {
    try {
        const response = await window.axios.get('/api/me');
        currentUser.value = response.data;
    } catch (e) {
        currentUser.value = null;
    } finally {
        userLoaded.value = true;
    }
}

export function clearCurrentUser() {
    currentUser.value = null;
    userLoaded.value = false;
}