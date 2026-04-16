<template>
  <div class="login-page">
    <div class="login-panel">
      <section class="login-showcase">
        <div class="showcase-badge">Task Tracker</div>
        <h1 class="showcase-title">Понятные задачи, спокойный дашборд и единый рабочий поток.</h1>
        <p class="showcase-copy">
          Войдите, чтобы управлять задачами, отслеживать статусы и оставаться в одном ритме с командой.
        </p>

        <div class="showcase-points">
          <div class="showcase-point">
            <span class="showcase-point-title">Работа от доски</span>
            <span class="showcase-point-copy">Быстрая смена статусов, компактные карточки и обсуждение в одном месте.</span>
          </div>

          <div class="showcase-point">
            <span class="showcase-point-title">Светлая и тёмная темы</span>
            <span class="showcase-point-copy">Комфортный интерфейс и днём в офисе, и вечером при длительной работе.</span>
          </div>
        </div>
      </section>

      <section class="login-card">
        <div class="login-card-top">
          <div>
            <div class="login-kicker">С возвращением</div>
            <h2 class="login-title">Вход</h2>
          </div>

          <button class="theme-switcher" type="button" @click="toggleTheme">
            {{ isDark ? 'Светлая' : 'Тёмная' }}
          </button>
        </div>

        <form @submit.prevent="login" class="login-form">
          <div class="field">
            <label>Эл. почта</label>
            <input v-model="form.email" type="email" autocomplete="username" required />
          </div>

          <div class="field">
            <label>Пароль</label>
            <input v-model="form.password" type="password" autocomplete="current-password" required />
          </div>

          <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

          <button :disabled="loading" type="submit" class="submit-button">
            {{ loading ? 'Входим...' : 'Открыть рабочее пространство' }}
          </button>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { setToken } from '../services/auth';
import { loadCurrentUser } from '../services/authState';
import { useTheme } from '../services/theme';

const router = useRouter();
const { isDark, toggleTheme } = useTheme();

const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  email: 'admin@test.com',
  password: '123456789',
});

const login = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await window.axios.post('/api/login', form);
    const token = response.data.token;

    setToken(token);
    window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;

    await loadCurrentUser();

    router.push('/dashboard');
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      error?.response?.data?.errors?.email?.[0] ||
      'Ошибка входа';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background:
    radial-gradient(circle at top left, rgba(38, 103, 255, 0.18), transparent 28%),
    radial-gradient(circle at 90% 20%, rgba(31, 181, 157, 0.18), transparent 24%),
    var(--app-bg);
}

.login-panel {
  width: min(1120px, 100%);
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  border-radius: 32px;
  border: 1px solid var(--border-soft);
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(18px);
  overflow: hidden;
  box-shadow: var(--shadow-card);
}

.login-showcase {
  padding: 48px;
  background:
    radial-gradient(circle at top left, rgba(255, 255, 255, 0.22), transparent 26%),
    linear-gradient(145deg, rgba(38, 103, 255, 0.92), rgba(31, 181, 157, 0.88));
  color: #fff;
}

.showcase-badge {
  display: inline-flex;
  padding: 7px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-weight: 700;
}

.showcase-title {
  margin: 22px 0 12px;
  font-size: clamp(2rem, 3vw, 3.4rem);
  line-height: 1.02;
  font-weight: 800;
  max-width: 10ch;
}

.showcase-copy {
  max-width: 44ch;
  line-height: 1.6;
  opacity: 0.92;
}

.showcase-points {
  display: grid;
  gap: 14px;
  margin-top: 30px;
}

.showcase-point {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 16px 18px;
  border-radius: 20px;
  background: rgba(7, 22, 43, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.16);
}

.showcase-point-title {
  font-weight: 700;
}

.showcase-point-copy {
  font-size: 0.9rem;
  opacity: 0.88;
}

.login-card {
  padding: 38px;
  background: var(--surface-2);
}

.login-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 26px;
}

.login-kicker {
  color: var(--text-muted);
  font-size: 0.8rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-weight: 700;
}

.login-title {
  margin: 6px 0 0;
  color: var(--text-1);
  font-size: 2rem;
  font-weight: 800;
}

.theme-switcher {
  padding: 10px 14px;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  background: var(--surface-1);
  color: var(--text-1);
  font-weight: 700;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.field label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-2);
  font-weight: 600;
}

.field input {
  width: 100%;
  padding: 13px 14px;
  box-sizing: border-box;
  border-radius: 16px;
  border: 1px solid var(--border-soft);
  background: var(--surface-1);
  color: var(--text-1);
}

.field input:focus {
  outline: none;
  border-color: var(--accent-1);
  box-shadow: 0 0 0 4px rgba(38, 103, 255, 0.12);
}

.submit-button {
  width: 100%;
  padding: 14px 16px;
  border: none;
  border-radius: 16px;
  color: #fff;
  background: linear-gradient(145deg, var(--accent-1), var(--accent-2));
  font-weight: 800;
  box-shadow: var(--shadow-accent);
}

.submit-button:disabled {
  opacity: 0.7;
  cursor: wait;
}

.error {
  margin: 0;
  padding: 11px 12px;
  border-radius: 14px;
  background: var(--danger-soft);
  color: var(--text-1);
}

@media (max-width: 960px) {
  .login-panel {
    grid-template-columns: 1fr;
  }

  .login-showcase {
    padding: 30px;
  }

  .showcase-title {
    max-width: none;
  }

  .login-card {
    padding: 26px;
  }
}
</style>
