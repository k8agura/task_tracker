import { createRouter, createWebHistory } from 'vue-router';
import { isAuthenticated } from '../services/auth';
import { ensureCurrentUserLoaded, useAuthState } from '../services/authState';
import { setAuthToken } from '../api/client';

const LoginView = () => import('../views/LoginView.vue');
const DashboardView = () => import('../views/DashboardView.vue');
const TasksView = () => import('../views/TasksView.vue');
const TaskDetailsView = () => import('../views/TaskDetailsView.vue');
const UsersView = () => import('../views/UsersView.vue');
const ReportsView = () => import('../views/ReportsView.vue');
const ProfileView = () => import('../views/ProfileView.vue');
const UserProfileView = () => import('../views/UserProfileView.vue');

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guest: true },
    },
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardView,
        meta: { requiresAuth: true },
    },
    {
        path: '/tasks',
        name: 'tasks',
        component: TasksView,
        meta: { requiresAuth: true },
    },
    {
        path: '/tasks/:id',
        name: 'task-details',
        component: TaskDetailsView,
        meta: { requiresAuth: true },
        props: true,
    },
    {
        path: '/users',
        name: 'users',
        component: UsersView,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/reports',
        name: 'reports',
        component: ReportsView,
        meta: { requiresAuth: true },
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfileView,
        meta: { requiresAuth: true },
    },
    {
        path: '/team/:id',
        name: 'user-profile',
        component: UserProfileView,
        meta: { requiresAuth: true },
        props: true,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    const auth = isAuthenticated();
    const token = localStorage.getItem('token');

    setAuthToken(token);

    if (to.meta.requiresAuth && !auth) {
        return '/login';
    }

    if (to.meta.guest && auth) {
        return '/dashboard';
    }

    if (to.meta.requiresAdmin) {
        try {
            const { currentUser } = useAuthState();
            const user = await ensureCurrentUserLoaded();
            const isAdmin = Array.isArray((currentUser.value || user)?.roles) &&
                (currentUser.value || user).roles.some(role => role.name === 'admin');

            if (!isAdmin) {
                return '/dashboard';
            }
        } catch (e) {
            return '/dashboard';
        }
    }
});

export default router;
