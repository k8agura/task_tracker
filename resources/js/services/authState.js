import { computed, ref } from 'vue';
import { fetchCurrentUser } from '../api/me';

const currentUser = ref(null);
const userLoaded = ref(false);
let currentUserRequest = null;

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
    if (currentUserRequest) {
        return currentUserRequest;
    }

    currentUserRequest = (async () => {
        try {
            currentUser.value = await fetchCurrentUser();
        } catch (e) {
            currentUser.value = null;
        } finally {
            userLoaded.value = true;
            currentUserRequest = null;
        }
    })();

    return currentUserRequest;
}

export async function ensureCurrentUserLoaded() {
    if (userLoaded.value) {
        return currentUser.value;
    }

    try {
        await loadCurrentUser();
    } catch (e) {
        //
    }

    return currentUser.value;
}

export function clearCurrentUser() {
    currentUser.value = null;
    userLoaded.value = false;
    currentUserRequest = null;
}
