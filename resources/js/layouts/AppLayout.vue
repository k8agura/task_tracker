<template>
  <div class="app-shell">
    <div
      v-if="isMobileNavOpen"
      class="sidebar-overlay"
      @click="closeMobileNav"
    ></div>

    <aside class="sidebar d-flex flex-column" :class="{ 'sidebar-open': isMobileNavOpen }">
      <div class="sidebar-brand">
        <div class="brand-mark">
          <span class="brand-mark-core">T</span>
        </div>
        <div class="brand-copy">
          <div class="brand-kicker">Рабочее пространство</div>
          <div class="brand-title">Task Tracker</div>
          <div class="brand-subtitle">Задачи, обсуждение и отчеты в одном потоке</div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <RouterLink to="/dashboard" class="sidebar-link">
          <span class="sidebar-link-label">Дашборд</span>
          <span class="sidebar-link-note">Доска и текущий фокус</span>
        </RouterLink>

        <RouterLink to="/tasks" class="sidebar-link">
          <span class="sidebar-link-label">Задачи</span>
          <span class="sidebar-link-note">Полный список и поиск</span>
        </RouterLink>

        <RouterLink v-if="isAdmin" to="/users" class="sidebar-link">
          <span class="sidebar-link-label">Пользователи</span>
          <span class="sidebar-link-note">Роли команды и доступ</span>
        </RouterLink>

        <RouterLink to="/reports" class="sidebar-link">
          <span class="sidebar-link-label">Отчеты</span>
          <span class="sidebar-link-note">Нагрузка и сводки</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer mt-auto">
        <button class="profile-chip" type="button" @click="router.push('/profile')">
          <div class="profile-avatar">{{ userInitials }}</div>
          <div class="profile-copy">
            <div class="profile-name">{{ userName }}</div>
            <div class="profile-role">{{ isAdmin ? 'Администратор' : 'Участник команды' }}</div>
          </div>
        </button>

        <div class="sidebar-actions">
          <button class="theme-toggle" type="button" @click="toggleTheme">
            <span>{{ isDark ? 'Светлая тема' : 'Темная тема' }}</span>
            <span class="theme-toggle-state">{{ isDark ? 'Вкл' : 'Выкл' }}</span>
          </button>

          <button class="btn logout-btn w-100" @click="logout">
            Выйти
          </button>
        </div>
      </div>
    </aside>

    <div class="content-wrapper">
      <header class="topbar">
        <div class="topbar-title-group">
          <button class="mobile-nav-toggle" type="button" @click="toggleMobileNav">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <div class="topbar-copy">
            <div class="topbar-eyebrow">{{ routeMeta.eyebrow }}</div>
            <div class="topbar-title">{{ routeMeta.title }}</div>
          </div>
        </div>

        <div class="topbar-actions">
          <button class="topbar-theme-button" type="button" @click="toggleTheme">
            {{ isDark ? 'Светлая' : 'Темная' }}
          </button>
          <div class="topbar-user">{{ userName }}</div>
        </div>
      </header>

      <main class="content-area">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { clearToken } from '../services/auth';
import { clearCurrentUser, loadCurrentUser, useAuthState } from '../services/authState';
import { useTheme } from '../services/theme';

const router = useRouter();
const route = useRoute();
const { isAdmin, userLoaded, currentUser } = useAuthState();
const { isDark, toggleTheme } = useTheme();
const isMobileNavOpen = ref(false);

const routeMeta = computed(() => {
  const map = {
    dashboard: { eyebrow: 'Рабочее пространство', title: 'Дашборд' },
    tasks: { eyebrow: 'Задачи', title: 'Список задач' },
    'task-details': { eyebrow: 'Задача', title: 'Карточка задачи' },
    profile: { eyebrow: 'Профиль', title: 'Профиль и настройки' },
    users: { eyebrow: 'Администрирование', title: 'Управление командой' },
    reports: { eyebrow: 'Отчеты', title: 'Отчеты и аналитика' },
  };

  return map[route.name] || { eyebrow: 'Рабочее пространство', title: 'Task Tracker' };
});

const userName = computed(() => {
  const user = currentUser.value;
  if (!user) return 'Пользователь';

  return [user.last_name, user.first_name].filter(Boolean).join(' ') || user.email || 'Пользователь';
});

const userInitials = computed(() => {
  const user = currentUser.value;
  if (!user) return 'U';

  const parts = [user.first_name, user.last_name].filter(Boolean);
  return parts.map((part) => part[0]).join('').slice(0, 2).toUpperCase() || 'U';
});

const closeMobileNav = () => {
  isMobileNavOpen.value = false;
};

const toggleMobileNav = () => {
  isMobileNavOpen.value = !isMobileNavOpen.value;
};

const logout = async () => {
  try {
    await window.axios.post('/api/logout');
  } catch (e) {
    //
  }

  clearToken();
  clearCurrentUser();
  delete window.axios.defaults.headers.common.Authorization;
  closeMobileNav();
  router.push('/login');
};

watch(
  () => route.fullPath,
  () => {
    closeMobileNav();
  }
);

onMounted(async () => {
  if (!userLoaded.value) {
    await loadCurrentUser();
  }
});
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  height: 100vh;
  display: grid;
  grid-template-columns: 292px minmax(0, 1fr);
  background:
    radial-gradient(circle at top left, var(--app-accent-soft), transparent 24%),
    radial-gradient(circle at 85% 12%, var(--app-accent-soft-2), transparent 22%),
    var(--app-bg);
  overflow: hidden;
}

.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(8, 15, 24, 0.42);
  backdrop-filter: blur(6px);
  z-index: 40;
}

.sidebar {
  padding: 22px 18px 18px;
  background: var(--sidebar-bg);
  border-right: 1px solid var(--border-soft);
  backdrop-filter: blur(20px);
  min-height: 0;
  overflow-y: auto;
  z-index: 50;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 4px 6px 22px;
}

.brand-mark {
  width: 52px;
  height: 52px;
  border-radius: 18px;
  display: grid;
  place-items: center;
  background: linear-gradient(145deg, var(--accent-1), var(--accent-2));
  box-shadow: 0 20px 34px rgba(39, 90, 145, 0.22);
}

.brand-mark-core {
  color: #fff;
  font-weight: 800;
  font-size: 1.2rem;
}

.brand-kicker {
  color: var(--text-muted);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 700;
}

.brand-title {
  color: var(--text-1);
  font-size: 1.15rem;
  font-weight: 800;
}

.brand-subtitle {
  color: var(--text-muted);
  font-size: 0.82rem;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.sidebar-link {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 13px 14px;
  border: 1px solid transparent;
  border-radius: 18px;
  background: var(--surface-1);
  color: var(--text-2);
  text-decoration: none;
  transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.sidebar-link:hover {
  transform: translateY(-1px);
  border-color: var(--border-strong);
  box-shadow: var(--shadow-soft);
}

.sidebar-link.router-link-active {
  background: linear-gradient(145deg, var(--accent-1), var(--accent-2));
  color: #fff;
  box-shadow: var(--shadow-accent);
}

.sidebar-link-label {
  font-size: 0.96rem;
  font-weight: 700;
}

.sidebar-link-note {
  font-size: 0.76rem;
  opacity: 0.78;
}

.sidebar-footer {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-top: 18px;
}

.profile-chip {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px;
  border-radius: 20px;
  background: var(--surface-1);
  border: 1px solid var(--border-soft);
  text-align: left;
  cursor: pointer;
}

.profile-chip:hover {
  border-color: var(--border-strong);
  box-shadow: var(--shadow-soft);
  transform: translateY(-1px);
}

.profile-avatar {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: linear-gradient(145deg, var(--accent-1), var(--accent-2));
  color: #fff;
  font-weight: 800;
}

.profile-name {
  color: var(--text-1);
  font-weight: 700;
}

.profile-role {
  color: var(--text-muted);
  font-size: 0.8rem;
}

.sidebar-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.theme-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  padding: 12px 14px;
  border: 1px solid var(--border-soft);
  border-radius: 16px;
  background: var(--surface-1);
  color: var(--text-1);
  font-weight: 600;
}

.theme-toggle-state {
  padding: 4px 8px;
  border-radius: 999px;
  background: var(--surface-3);
  color: var(--text-muted);
  font-size: 0.75rem;
}

.logout-btn {
  border: none;
  border-radius: 16px;
  padding: 12px 14px;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(145deg, var(--accent-1), var(--accent-2));
}

.content-wrapper {
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 28px 12px;
  min-width: 0;
}

.topbar-title-group {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.topbar-copy {
  min-width: 0;
}

.topbar-eyebrow {
  color: var(--text-muted);
  font-size: 0.74rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-weight: 700;
}

.topbar-title {
  color: var(--text-1);
  font-size: 1.35rem;
  font-weight: 800;
}

.mobile-nav-toggle {
  display: none;
  width: 46px;
  height: 46px;
  border: 1px solid var(--border-soft);
  border-radius: 16px;
  background: var(--surface-1);
  color: var(--text-1);
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 4px;
  flex: 0 0 auto;
  box-shadow: var(--shadow-soft);
}

.mobile-nav-toggle span {
  width: 18px;
  height: 2px;
  border-radius: 999px;
  background: currentColor;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.topbar-theme-button,
.topbar-user {
  padding: 9px 12px;
  border-radius: 14px;
  background: var(--surface-1);
  border: 1px solid var(--border-soft);
  color: var(--text-1);
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.content-area {
  flex: 1;
  padding: 0 28px 28px;
  min-width: 0;
  min-height: 0;
  overflow: auto;
}

@media (max-width: 1100px) {
  .app-shell {
    grid-template-columns: 1fr;
    height: auto;
    min-height: 100vh;
    overflow: visible;
  }

  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: min(86vw, 360px);
    max-width: 360px;
    border-right: 1px solid var(--border-soft);
    border-bottom: none;
    transform: translateX(-100%);
    transition: transform 0.22s ease;
    box-shadow: 0 24px 60px rgba(4, 12, 20, 0.2);
  }

  .sidebar.sidebar-open {
    transform: translateX(0);
  }

  .sidebar-nav {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .mobile-nav-toggle {
    display: inline-flex;
  }

  .topbar {
    position: sticky;
    top: 0;
    z-index: 20;
    padding: 14px 18px 10px;
    backdrop-filter: blur(18px);
    background: var(--topbar-mobile-bg);
  }

  .content-area {
    padding: 0 18px 18px;
  }
}

@media (max-width: 768px) {
  .sidebar {
    width: min(92vw, 340px);
    padding: 18px 14px 16px;
  }

  .sidebar-nav {
    grid-template-columns: 1fr;
  }

  .topbar {
    flex-direction: column;
    align-items: flex-start;
    padding: 14px 16px 10px;
  }

  .topbar-title-group {
    width: 100%;
  }

  .topbar-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .topbar-theme-button,
  .topbar-user {
    flex: 1 1 180px;
    text-align: center;
  }

  .topbar-title {
    font-size: 1.15rem;
  }

  .content-area {
    padding: 0 16px 16px;
  }
}

@media (max-width: 560px) {
  .topbar-actions {
    gap: 8px;
  }

  .topbar-theme-button,
  .topbar-user {
    width: 100%;
  }

  .brand-subtitle,
  .sidebar-link-note,
  .profile-role {
    font-size: 0.78rem;
  }
}
</style>
