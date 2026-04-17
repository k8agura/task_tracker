<template>
  <AppLayout>
    <div v-if="loading" class="text-center py-4 text-muted">
      Загрузка профиля...
    </div>

    <div v-else-if="profile" class="row g-3">
      <div class="col-12 col-xl-5">
        <div class="card-soft bg-white p-3 p-md-4 h-100">
          <h5 class="mb-3">Основная информация</h5>

          <div class="mb-3">
            <div class="text-muted small mb-1">ФИО</div>
            <div class="fw-semibold">{{ fullName(profile) || '—' }}</div>
          </div>

          <div class="mb-3">
            <div class="text-muted small mb-1">Должность</div>
            <div>{{ profile.position || '—' }}</div>
          </div>

          <div class="mb-3">
            <div class="text-muted small mb-1">Подразделение</div>
            <div>{{ profile.department?.name || '—' }}</div>
          </div>

          <div class="mb-3">
            <div class="text-muted small mb-1">Роли</div>
            <div class="d-flex flex-wrap gap-2">
              <span
                v-for="role in profile.roles"
                :key="role.id"
                class="badge text-bg-info"
              >
                {{ role.name }}
              </span>
            </div>
          </div>

          <div>
            <div class="text-muted small mb-1">Статус</div>
            <span class="badge" :class="profile.is_active ? 'text-bg-success' : 'text-bg-secondary'">
              {{ profile.is_active ? 'Активен' : 'Неактивен' }}
            </span>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-7">
        <div class="card-soft bg-white p-3 p-md-4 mb-3">
          <h5 class="mb-3">Настройки учётной записи</h5>

          <div v-if="errorMessage" class="alert alert-danger py-2">
            {{ errorMessage }}
          </div>

          <div v-if="successMessage" class="alert alert-success py-2">
            {{ successMessage }}
          </div>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Email</label>
              <input v-model="form.email" type="email" class="form-control" />
            </div>

            <div class="col-md-6">
              <label class="form-label">Новый пароль</label>
              <input
                v-model="form.password"
                type="password"
                class="form-control"
                placeholder="Оставьте пустым, если не меняете"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Подтверждение пароля</label>
              <input v-model="form.password_confirmation" type="password" class="form-control" />
            </div>
          </div>

          <div class="profile-form-actions d-flex justify-content-end gap-2 mt-4">
            <button class="btn btn-outline-secondary" @click="resetForm">
              Сбросить
            </button>
            <button class="btn btn-theme" :disabled="saving" @click="saveProfile">
              {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
            </button>
          </div>
        </div>

        <div class="card-soft bg-white p-3 p-md-4">
        <div class="profile-task-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Мои задачи</h5>
            <button class="btn btn-sm btn-outline-secondary" @click="loadMyTasks">
            Обновить
            </button>
        </div>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
            <button
                class="nav-link"
                :class="{ active: activeTaskTab === 'assigned' }"
                type="button"
                @click="activeTaskTab = 'assigned'"
            >
                Я исполнитель
            </button>
            </li>
            <li class="nav-item">
            <button
                class="nav-link"
                :class="{ active: activeTaskTab === 'created' }"
                type="button"
                @click="activeTaskTab = 'created'"
            >
                Я создатель
            </button>
            </li>
        </ul>

        <div v-if="tasksLoading" class="text-muted">
            Загрузка задач...
        </div>

        <template v-else>
            <div v-if="activeTaskTab === 'assigned'">
            <div v-if="assignedTasks.length === 0" class="text-muted">
                У вас нет назначенных задач
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 profile-task-table">
                <thead>
                    <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Статус</th>
                    <th>Срок</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                    v-for="task in assignedTasks"
                    :key="task.id"
                    class="profile-task-row"
                    @click="openTask(task.id)"
                    >
                    <td class="fw-semibold" data-label="ID">#{{ task.id }}</td>
                    <td data-label="Название">
                        <div class="fw-semibold">{{ task.title }}</div>
                        <div class="small text-muted text-truncate-cell">
                        {{ task.description || 'Без описания' }}
                        </div>
                    </td>
                    <td data-label="Статус">
                        <span class="badge text-bg-primary">
                        {{ task.status?.name || '—' }}
                        </span>
                    </td>
                    <td data-label="Срок">{{ formatShortDate(task.due_date) || '—' }}</td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>

            <div v-if="activeTaskTab === 'created'">
            <div v-if="createdTasks.length === 0" class="text-muted">
                У вас нет созданных задач
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 profile-task-table">
                <thead>
                    <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Статус</th>
                    <th>Срок</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                    v-for="task in createdTasks"
                    :key="task.id"
                    class="profile-task-row"
                    @click="openTask(task.id)"
                    >
                    <td class="fw-semibold" data-label="ID">#{{ task.id }}</td>
                    <td data-label="Название">
                        <div class="fw-semibold">{{ task.title }}</div>
                        <div class="small text-muted text-truncate-cell">
                        {{ task.description || 'Без описания' }}
                        </div>
                    </td>
                    <td data-label="Статус">
                        <span class="badge text-bg-primary">
                        {{ task.status?.name || '—' }}
                        </span>
                    </td>
                    <td data-label="Срок">{{ formatShortDate(task.due_date) || '—' }}</td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>
        </template>
        </div>
      </div>
    </div>

    <div v-else class="alert alert-warning">
      Не удалось загрузить профиль
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import { fetchProfile, updateProfileRequest } from '../api/profile';
import { fetchTasks } from '../api/tasks';

const router = useRouter();

const profile = ref(null);
const loading = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const assignedTasks = ref([]);
const createdTasks = ref([]);
const tasksLoading = ref(false);
const activeTaskTab = ref('assigned');

const form = reactive({
  email: '',
  password: '',
  password_confirmation: '',
});

const fullName = (user) => {
  return [user?.last_name, user?.first_name, user?.middle_name].filter(Boolean).join(' ');
};

const formatShortDate = (value) => {
  if (!value) return '';

  return new Date(value).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const fillForm = () => {
  form.email = profile.value?.email || '';
  form.password = '';
  form.password_confirmation = '';
};

const loadProfile = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    profile.value = await fetchProfile();
    fillForm();
  } catch (error) {
    profile.value = null;
    errorMessage.value = 'Не удалось загрузить профиль';
  } finally {
    loading.value = false;
  }
};

const loadMyTasks = async () => {
  if (!profile.value?.id) return;

  tasksLoading.value = true;

  try {
    const [assignedResponse, createdResponse] = await Promise.all([
      fetchTasks({
        performer_id: profile.value.id,
      }),
      fetchTasks({
        creator_id: profile.value.id,
      }),
    ]);

    assignedTasks.value = assignedResponse.data || [];
    createdTasks.value = createdResponse.data || [];
  } catch (error) {
    assignedTasks.value = [];
    createdTasks.value = [];
  } finally {
    tasksLoading.value = false;
  }
};

const resetForm = () => {
  fillForm();
  errorMessage.value = '';
  successMessage.value = '';
};

const saveProfile = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await updateProfileRequest({
      email: form.email,
      password: form.password || null,
      password_confirmation: form.password_confirmation || null,
    });

    profile.value = response.data.user;
    fillForm();
    successMessage.value = 'Профиль обновлён';
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      error?.response?.data?.errors?.email?.[0] ||
      error?.response?.data?.errors?.password?.[0] ||
      'Не удалось сохранить профиль';
  } finally {
    saving.value = false;
  }
};

const openTask = (id) => {
  router.push(`/tasks/${id}`);
};

onMounted(async () => {
  await loadProfile();
  await loadMyTasks();
});
</script>

<style scoped>
.profile-task-table th {
  font-size: 0.9rem;
  color: #5b7b84;
  font-weight: 700;
  white-space: nowrap;
}

.profile-task-row {
  cursor: pointer;
}

.profile-task-row td {
  padding-top: 14px;
  padding-bottom: 14px;
  transition: background-color 0.18s ease;
}

.profile-task-row:hover td {
  background-color: rgba(70, 125, 135, 0.18) !important;
}

.text-truncate-cell {
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.nav-tabs .nav-link {
  border-radius: 10px 10px 0 0;
  color: #40606a;
  font-weight: 600;
}

.nav-tabs .nav-link.active {
  color: #23414b;
  background: #f8fffd;
}

@media (max-width: 768px) {
  .profile-form-actions {
    flex-direction: column;
  }

  .profile-form-actions > .btn {
    width: 100%;
  }

  .profile-task-header {
    flex-direction: column;
    align-items: stretch !important;
    gap: 0.75rem;
  }

  .text-truncate-cell {
    max-width: none;
    white-space: normal;
  }
}

@media (max-width: 640px) {
  .profile-task-table thead {
    display: none;
  }

  .profile-task-table,
  .profile-task-table tbody,
  .profile-task-table tr,
  .profile-task-table td {
    display: block;
    width: 100%;
  }

  .profile-task-row {
    display: grid;
    gap: 0.4rem;
    padding: 0.85rem 0.95rem;
    border: 1px solid var(--border-soft);
    border-radius: 18px;
    background: var(--surface-1);
    margin-bottom: 0.75rem;
    box-shadow: var(--shadow-soft);
  }

  .profile-task-row td {
    padding: 0;
    border: none;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
  }

  .profile-task-row td::before {
    content: attr(data-label);
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    flex: 0 0 6.5rem;
  }

  .profile-task-row:hover td {
    background-color: transparent !important;
  }
}
</style>
