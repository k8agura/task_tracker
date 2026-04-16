import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import TasksView from '../views/TasksView.vue';
import TaskDetailsView from '../views/TaskDetailsView.vue';
import UsersView from '../views/UsersView.vue';
import ReportsView from '../views/ReportsView.vue';
import { isAuthenticated } from '../services/auth';
import ProfileView from '../views/ProfileView.vue';

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

    if (to.meta.requiresAuth && !auth) {
        return '/login';
    }

    if (to.meta.guest && auth) {
        return '/dashboard';
    }

    if (to.meta.requiresAdmin) {
        try {
            const token = localStorage.getItem('token');
            if (token) {
                window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
            }

            const response = await window.axios.get('/api/me');
            const user = response.data;
            const isAdmin = Array.isArray(user?.roles) && user.roles.some(role => role.name === 'admin');

            if (!isAdmin) {
                return '/dashboard';
            }
        } catch (e) {
            return '/dashboard';
        }
    }
});

export default router;
