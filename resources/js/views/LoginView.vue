<template>
  <div class="login-page">
    <div class="card">
      <h1>Вход</h1>

      <form @submit.prevent="login">
        <div class="field">
          <label>Email</label>
          <input v-model="form.email" type="email" required />
        </div>

        <div class="field">
          <label>Пароль</label>
          <input v-model="form.password" type="password" required />
        </div>

        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

        <button :disabled="loading" type="submit">
          {{ loading ? 'Вход...' : 'Войти' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { setToken } from '../services/auth';
import { loadCurrentUser } from '../services/authState';

const router = useRouter();

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

    router.push('/tasks');
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
  background: #f5f7fb;
  font-family: Arial, sans-serif;
}
.card {
  width: 360px;
  background: white;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}
.field {
  margin-bottom: 16px;
}
label {
  display: block;
  margin-bottom: 6px;
}
input {
  width: 100%;
  padding: 10px 12px;
  box-sizing: border-box;
}
button {
  width: 100%;
  padding: 12px;
  background: #7298FF;
  border: none;
  color: white;
  cursor: pointer;
  border-radius: 10px;
}
.error {
  color: #dc2626;
}
</style>