<template>
  <AppLayout>
    <div class="page-actions d-flex flex-column flex-lg-row justify-content-lg-end align-items-lg-center gap-3 mb-3">
      <div class="d-flex gap-2 flex-wrap page-actions-group">
        <button class="btn btn-outline-secondary" @click="resetFilters">
          Сбросить
        </button>
        <button class="btn btn-outline-secondary" @click="loadTasks()">
          Обновить
        </button>
        <button class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#createTaskModal">
          Создать задачу
        </button>
      </div>
    </div>

    <div class="card-soft bg-white p-3 p-md-4 mb-3">
      <div class="row g-3">
        <div class="col-12 col-xl-4">
          <label class="form-label">Быстрый поиск</label>
          <input
            v-model="filters.search"
            class="form-control"
            placeholder="По названию или описанию"
            @input="handleQuickSearch"
          />
        </div>

        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label">Статус</label>
          <select v-model="filters.status_id" class="form-select">
            <option value="">Все статусы</option>
            <option v-for="status in statuses" :key="status.id" :value="status.id">
              {{ status.name }}
            </option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label">Приоритет</label>
          <select v-model="filters.priority" class="form-select">
            <option value="">Все</option>
            <option value="low">Низкий</option>
            <option value="medium">Средний</option>
            <option value="high">Высокий</option>
            <option value="critical">Критический</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label">Сортировка</label>
          <select v-model="filters.sort" class="form-select">
            <option value="newest">Сначала новые</option>
            <option value="oldest">Сначала старые</option>
            <option value="due_asc">Срок ↑</option>
            <option value="due_desc">Срок ↓</option>
            <option value="title_asc">Название А-Я</option>
            <option value="title_desc">Название Я-А</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-xl-2 d-flex align-items-end">
          <button class="btn btn-theme w-100" @click="applyFilters">
            Применить
          </button>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger py-2">
      {{ errorMessage }}
    </div>

    <div class="card-soft bg-white p-2 p-md-3">
      <div v-if="loading" class="text-center py-4 text-muted">
        Загрузка задач...
      </div>

      <div v-else-if="tasks.length === 0" class="text-center py-4 text-muted">
        Задачи не найдены
      </div>

      <div v-else class="table-responsive">
        <table class="table align-middle mb-0 task-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Название</th>
              <th>Статус</th>
              <th>Приоритет</th>
              <th>Срок</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="task in tasks"
              :key="task.id"
              class="task-row"
              @click="openTask(task.id)"
            >
              <td class="fw-semibold" data-label="ID">#{{ task.id }}</td>
              <td data-label="Название">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="fw-semibold">{{ task.title }}</span>
                  <span v-if="isOverdue(task)" class="badge text-bg-danger">
                    Просрочено
                  </span>
                </div>

                <div class="small text-muted text-truncate-cell">
                  {{ task.description || 'Без описания' }}
                </div>
              </td>
              <td data-label="Статус">
                <span class="badge text-bg-primary">
                  {{ task.status?.name || '—' }}
                </span>
              </td>
              <td data-label="Приоритет">
                <span class="badge" :class="priorityClass(task.priority)">
                  {{ priorityLabel(task.priority) }}
                </span>
              </td>
              <td data-label="Срок">
                <span v-if="isDueToday(task)" class="badge due-today-badge">
                  Сегодня · {{ formatShortDate(task.due_date) }}
                </span>

                <span v-else-if="isOverdue(task)" class="badge text-bg-danger">
                  Просрочено · {{ formatShortDate(task.due_date) }}
                </span>

                <span v-else>
                  {{ formatShortDate(task.due_date) || '—' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="pagination.last_page > 1"
        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3 px-2"
      >
        <div class="small text-muted">
          Показано {{ pagination.from }}-{{ pagination.to }} из {{ pagination.total }}
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)"
          >
            Назад
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            class="btn btn-sm"
            :class="page === pagination.current_page ? 'btn-theme' : 'btn-outline-secondary'"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>

          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goToPage(pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </div>
    </div>

    <TaskCreateModal
      :form="createForm"
      :statuses="statuses"
      :users="users"
      :creating="creating"
      :create-error="createError"
      @add-performer="addCreatePerformer"
      @remove-performer="removeCreatePerformer"
      @submit="createTask"
    />
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Modal } from 'bootstrap';
import AppLayout from '../layouts/AppLayout.vue';
import TaskCreateModal from '../components/tasks/TaskCreateModal.vue';
import { fetchTaskStatuses } from '../api/lookups';
import { fetchTasks, createTaskRequest } from '../api/tasks';
import { fetchUsers } from '../api/users';
import { useTaskPresentation } from '../composables/useTaskPresentation';

const router = useRouter();

const loading = ref(false);
const creating = ref(false);
const errorMessage = ref('');
const createError = ref('');
const tasks = ref([]);
const statuses = ref([]);
const users = ref([]);
const searchDebounce = ref(null);
let createTaskModalInstance = null;

const { formatShortDate, isDueToday, isOverdue, priorityClass, priorityLabel } = useTaskPresentation();

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
});

const filters = reactive({
  search: '',
  status_id: '',
  priority: '',
  sort: 'newest',
});

const createForm = reactive({
  title: '',
  description: '',
  priority: 'medium',
  status_id: '',
  due_date: '',
  performers: [],
});

const visiblePages = computed(() => {
  const pages = [];
  const start = Math.max(1, pagination.current_page - 2);
  const end = Math.min(pagination.last_page, pagination.current_page + 2);

  for (let index = start; index <= end; index += 1) {
    pages.push(index);
  }

  return pages;
});

const loadStatuses = async () => {
  statuses.value = await fetchTaskStatuses();

  if (!createForm.status_id && statuses.value.length) {
    createForm.status_id = statuses.value[0].id;
  }
};

const loadUsers = async () => {
  try {
    const response = await fetchUsers();
    users.value = response.data || response || [];
  } catch (error) {
    users.value = [];
  }
};

const loadTasks = async (page = 1) => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const params = {
      page,
      sort: filters.sort,
    };

    if (filters.search) params.search = filters.search;
    if (filters.status_id) params.status_id = filters.status_id;
    if (filters.priority) params.priority = filters.priority;

    const payload = await fetchTasks(params);

    tasks.value = payload.data || [];
    pagination.current_page = payload.current_page || 1;
    pagination.last_page = payload.last_page || 1;
    pagination.per_page = payload.per_page || 15;
    pagination.total = payload.total || 0;
    pagination.from = payload.from || 0;
    pagination.to = payload.to || 0;
  } catch (error) {
    errorMessage.value = 'Не удалось загрузить задачи';
  } finally {
    loading.value = false;
  }
};

const handleQuickSearch = () => {
  clearTimeout(searchDebounce.value);

  searchDebounce.value = setTimeout(() => {
    loadTasks(1);
  }, 350);
};

const applyFilters = async () => {
  await loadTasks(1);
};

const resetFilters = async () => {
  filters.search = '';
  filters.status_id = '';
  filters.priority = '';
  filters.sort = 'newest';
  await loadTasks(1);
};

const goToPage = async (page) => {
  if (page < 1 || page > pagination.last_page) return;
  await loadTasks(page);
};

const openTask = (id) => {
  router.push(`/tasks/${id}`);
};

const resetCreateForm = () => {
  createForm.title = '';
  createForm.description = '';
  createForm.priority = 'medium';
  createForm.status_id = statuses.value.length ? statuses.value[0].id : '';
  createForm.due_date = '';
  createForm.performers = [];
  createError.value = '';
};

const addCreatePerformer = () => {
  if (!users.value.length) return;

  const selectedIds = createForm.performers.map((item) => item.user_id);
  const firstAvailable = users.value.find((user) => !selectedIds.includes(user.id));

  if (!firstAvailable) return;

  createForm.performers.push({
    user_id: firstAvailable.id,
    role: 'executor',
  });
};

const removeCreatePerformer = (index) => {
  createForm.performers.splice(index, 1);
};

const createTask = async () => {
  creating.value = true;
  createError.value = '';

  try {
    await createTaskRequest({
      title: createForm.title,
      description: createForm.description,
      priority: createForm.priority,
      status_id: createForm.status_id,
      due_date: createForm.due_date || null,
      performers: createForm.performers,
    });

    await loadTasks(1);
    resetCreateForm();
    createTaskModalInstance?.hide();
  } catch (error) {
    createError.value =
      error?.response?.data?.message ||
      'Не удалось создать задачу';
  } finally {
    creating.value = false;
  }
};

onMounted(async () => {
  await loadStatuses();
  await loadUsers();
  await loadTasks(1);

  const modalEl = document.getElementById('createTaskModal');
  if (!modalEl) return;

  createTaskModalInstance = new Modal(modalEl);
  modalEl.addEventListener('hidden.bs.modal', resetCreateForm);
});
</script>

<style scoped>
.page-actions-group > .btn {
  min-width: 140px;
}

.task-table th {
  font-size: 0.9rem;
  color: #5b7b84;
  font-weight: 700;
  white-space: nowrap;
}

.task-row {
  cursor: pointer;
  transition: transform 0.18s ease;
}

.task-row td {
  padding-top: 14px;
  padding-bottom: 14px;
  background-color: transparent;
  transition: background-color 0.18s ease;
}

.task-row:hover td {
  background-color: rgba(70, 125, 135, 0.18) !important;
}

.task-row:hover td:first-child {
  border-top-left-radius: 12px;
  border-bottom-left-radius: 12px;
}

.task-row:hover td:last-child {
  border-top-right-radius: 12px;
  border-bottom-right-radius: 12px;
}

.text-truncate-cell {
  max-width: 340px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.due-today-badge {
  background: #8f000e;
  color: #fff;
  font-weight: 700;
}

@media (max-width: 768px) {
  .page-actions-group {
    width: 100%;
  }

  .page-actions-group > .btn {
    flex: 1 1 calc(50% - 0.5rem);
    min-width: 0;
  }

  .text-truncate-cell {
    max-width: none;
    white-space: normal;
  }
}

@media (max-width: 640px) {
  .task-table thead {
    display: none;
  }

  .task-table,
  .task-table tbody,
  .task-table tr,
  .task-table td {
    display: block;
    width: 100%;
  }

  .task-row {
    display: grid;
    gap: 0.4rem;
    padding: 0.85rem 0.95rem;
    border: 1px solid var(--border-soft);
    border-radius: 18px;
    background: var(--surface-1);
    margin-bottom: 0.75rem;
    box-shadow: var(--shadow-soft);
  }

  .task-row td {
    padding: 0;
    border: none;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
  }

  .task-row td::before {
    content: attr(data-label);
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    flex: 0 0 6.5rem;
  }

  .task-row:hover td {
    background-color: transparent !important;
  }

  .task-row:hover td:first-child,
  .task-row:hover td:last-child {
    border-radius: 0;
  }
}
</style>
