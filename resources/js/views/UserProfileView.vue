<template>
  <AppLayout>
    <div v-if="loading" class="text-center py-4 text-muted">
      Загрузка профиля...
    </div>

    <div v-else-if="profile" class="row g-3">
      <div class="col-12 col-xl-5">
        <div class="card-soft profile-hero p-3 p-md-4 h-100">
          <div class="profile-avatar">
            {{ initials }}
          </div>

          <div class="profile-name">{{ fullName(profile) || '—' }}</div>
          <div class="profile-position">{{ profile.position || 'Должность не указана' }}</div>

          <div class="profile-grid mt-4">
            <div class="profile-fact">
              <div class="profile-fact-label">Email</div>
              <div class="profile-fact-value">{{ profile.email || '—' }}</div>
            </div>

            <div class="profile-fact">
              <div class="profile-fact-label">Подразделение</div>
              <div class="profile-fact-value">{{ profile.department?.name || '—' }}</div>
            </div>

            <div class="profile-fact">
              <div class="profile-fact-label">Статус</div>
              <div class="profile-fact-value">
                <span class="badge" :class="profile.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                  {{ profile.is_active ? 'Активен' : 'Неактивен' }}
                </span>
              </div>
            </div>

            <div class="profile-fact">
              <div class="profile-fact-label">Роли</div>
              <div class="d-flex flex-wrap gap-2">
                <span
                  v-for="role in profile.roles || []"
                  :key="role.id"
                  class="badge text-bg-info"
                >
                  {{ role.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-7">
        <div class="card-soft bg-white p-3 p-md-4">
          <div class="profile-task-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Задачи сотрудника</h5>
            <button class="btn btn-sm btn-outline-secondary" @click="loadUserTasks">
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
                Исполнитель
              </button>
            </li>
            <li class="nav-item">
              <button
                class="nav-link"
                :class="{ active: activeTaskTab === 'created' }"
                type="button"
                @click="activeTaskTab = 'created'"
              >
                Создатель
              </button>
            </li>
          </ul>

          <div v-if="tasksLoading" class="text-muted">
            Загрузка задач...
          </div>

          <template v-else>
            <div v-if="activeTaskTab === 'assigned'">
              <div v-if="assignedTasks.length === 0" class="text-muted">
                У сотрудника нет назначенных задач
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
                У сотрудника нет созданных задач
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
      {{ errorMessage || 'Не удалось загрузить профиль сотрудника' }}
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import { fetchProfile, fetchUserProfile } from '../api/profile';
import { fetchTasks } from '../api/tasks';
import { useTaskPresentation } from '../composables/useTaskPresentation';

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
});

const router = useRouter();
const { formatShortDate, fullName } = useTaskPresentation();

const profile = ref(null);
const viewer = ref(null);
const loading = ref(false);
const errorMessage = ref('');
const tasksLoading = ref(false);
const assignedTasks = ref([]);
const createdTasks = ref([]);
const activeTaskTab = ref('assigned');

const initials = computed(() => {
  const user = profile.value;
  return [user?.first_name?.[0], user?.last_name?.[0]].filter(Boolean).join('').toUpperCase() || 'U';
});

const loadProfileData = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const [viewerResponse, profileResponse] = await Promise.all([
      fetchProfile(),
      fetchUserProfile(props.id),
    ]);

    viewer.value = viewerResponse;
    profile.value = profileResponse;
  } catch (error) {
    profile.value = null;
    errorMessage.value = error?.response?.data?.message || 'Не удалось загрузить профиль сотрудника';
  } finally {
    loading.value = false;
  }
};

const loadUserTasks = async () => {
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
  } catch {
    assignedTasks.value = [];
    createdTasks.value = [];
  } finally {
    tasksLoading.value = false;
  }
};

const openTask = (id) => {
  router.push(`/tasks/${id}`);
};

onMounted(async () => {
  await loadProfileData();
  await loadUserTasks();
});
</script>

<style scoped>
.profile-hero {
  background:
    radial-gradient(circle at top left, rgba(104, 199, 183, 0.18), transparent 30%),
    radial-gradient(circle at right center, rgba(106, 144, 240, 0.16), transparent 32%),
    linear-gradient(135deg, #eefaf7 0%, #f7fbff 100%);
}

.profile-avatar {
  width: 72px;
  height: 72px;
  border-radius: 22px;
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, 0.82);
  color: #244c55;
  font-size: 1.5rem;
  font-weight: 800;
  box-shadow: var(--shadow-soft);
}

.profile-name {
  margin-top: 1rem;
  font-size: clamp(1.25rem, 2vw, 1.7rem);
  font-weight: 800;
  color: var(--text-1);
}

.profile-position {
  margin-top: 0.35rem;
  color: var(--text-muted);
}

.profile-grid {
  display: grid;
  gap: 1rem;
}

.profile-fact-label {
  color: var(--text-muted);
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.25rem;
}

.profile-fact-value {
  color: var(--text-1);
}

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
