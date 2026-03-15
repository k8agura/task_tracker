<template>
  <div class="app-shell">
    <aside class="sidebar d-flex flex-column">
      <div class="sidebar-brand">
        <div class="brand-icon">TM</div>
        <div>
          <div class="brand-title">Task Manager</div>
          <div class="brand-subtitle">Система управления задачами</div>
        </div>
      </div>
        <nav class="nav flex-column sidebar-nav">
          <RouterLink to="/profile" class="sidebar-link">
            <span>Профиль</span>
          </RouterLink>

          <RouterLink to="/tasks" class="sidebar-link">
            <span>Задачи</span>
          </RouterLink>

          <RouterLink v-if="isAdmin" to="/users" class="sidebar-link">
            <span>Пользователи</span>
          </RouterLink>

          <RouterLink to="/reports" class="sidebar-link">
            <span>Отчёты</span>
          </RouterLink>
        </nav>

      <div class="mt-auto pt-3">
        <button class="btn logout-btn w-100" @click="logout">
          Выйти
        </button>
      </div>
    </aside>

    <div class="content-wrapper">
      <main class="content-area">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { clearToken } from '../services/auth';
import { clearCurrentUser, loadCurrentUser, useAuthState } from '../services/authState';

const router = useRouter();
const { isAdmin, userLoaded } = useAuthState();

const logout = async () => {
  try {
    await window.axios.post('/api/logout');
  } catch (e) {
    //
  }

  clearToken();
  clearCurrentUser();
  delete window.axios.defaults.headers.common.Authorization;
  router.push('/login');
};

onMounted(async () => {
  if (!userLoaded.value) {
    await loadCurrentUser();
  }
});
</script>


<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
  background: linear-gradient(135deg, #eef9f6 0%, #f4fbff 100%);
}

.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #dff4ee 0%, #d8eef7 100%);
  border-right: 1px solid rgba(120, 170, 180, 0.18);
  padding: 24px 18px;
  box-shadow: 4px 0 18px rgba(70, 120, 130, 0.08);
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 28px;
  padding: 10px 8px 18px;
  border-bottom: 1px solid rgba(90, 140, 150, 0.14);
}

.brand-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-weight: 700;
  color: white;
  background: linear-gradient(135deg, #58b7a7 0%, #5d8df6 100%);
  box-shadow: 0 8px 18px rgba(93, 141, 246, 0.22);
}

.brand-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #23414b;
}

.brand-subtitle {
  font-size: 0.82rem;
  color: #5b7b84;
}

.sidebar-nav {
  gap: 10px;
}

.sidebar-link {
  display: flex;
  align-items: center;
  padding: 12px 14px;
  border-radius: 14px;
  text-decoration: none;
  color: #31545f;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.45);
  transition: 0.2s ease;
}

.sidebar-link:hover {
  background: rgba(255, 255, 255, 0.8);
  color: #23414b;
}

.sidebar-link.router-link-active {
  background: linear-gradient(135deg, #68c7b7 0%, #6b96ff 100%);
  color: white;
  box-shadow: 0 10px 20px rgba(90, 140, 210, 0.2);
}

.logout-btn {
  border: none;
  border-radius: 14px;
  padding: 12px 14px;
  font-weight: 600;
  color: white;
  background: linear-gradient(135deg, #5db7a7 0%, #6a90f0 100%);
}

.logout-btn:hover {
  color: white;
  opacity: 0.95;
}

.content-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.content-area {
  padding: 28px;
}

@media (max-width: 992px) {
  .app-shell {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid rgba(120, 170, 180, 0.18);
  }

  .content-area {
    padding: 18px;
  }
}
</style>