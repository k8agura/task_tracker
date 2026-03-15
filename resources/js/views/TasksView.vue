<template>
  <AppLayout>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
      <div>
        <div class="text-muted small mb-1">Раздел</div>
        <h1 class="h4 mb-0">Задачи</h1>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary" @click="resetFilters">
          Сбросить
        </button>
        <button class="btn btn-outline-secondary" @click="loadTasks">
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
              <td class="fw-semibold">#{{ task.id }}</td>
              <td>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="fw-semibold">{{ task.title }}</span>
                  <span
                    v-if="isOverdue(task)"
                    class="badge text-bg-danger"
                  >
                    Просрочено
                  </span>
                </div>

                <div class="small text-muted text-truncate-cell">
                  {{ task.description || 'Без описания' }}
                </div>
              </td>
              <td>
                <span class="badge text-bg-primary">
                  {{ task.status?.name || '—' }}
                </span>
              </td>
              <td>
                <span class="badge" :class="priorityClass(task.priority)">
                  {{ priorityLabel(task.priority) }}
                </span>
              </td>
              <td>
                <span
                  v-if="isDueToday(task)"
                  class="badge due-today-badge"
                >
                  🔥 {{ formatShortDate(task.due_date) }}
                </span>

                <span
                  v-else-if="isOverdue(task)"
                  class="badge text-bg-danger"
                >
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
          Показано {{ pagination.from }}–{{ pagination.to }} из {{ pagination.total }}
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

    <div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title">Создать задачу</h5>
              <div class="text-muted small">Заполните основные параметры задачи</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
          </div>

          <div class="modal-body pt-3">
            <div v-if="createError" class="alert alert-danger py-2">
              {{ createError }}
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Название</label>
                <input v-model="createForm.title" type="text" class="form-control" />
              </div>

              <div class="col-12">
                <label class="form-label">Описание</label>
                <textarea v-model="createForm.description" rows="4" class="form-control"></textarea>
              </div>

              <div class="col-md-4">
                <label class="form-label">Приоритет</label>
                <select v-model="createForm.priority" class="form-select">
                  <option value="low">Низкий</option>
                  <option value="medium">Средний</option>
                  <option value="high">Высокий</option>
                  <option value="critical">Критический</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select v-model="createForm.status_id" class="form-select">
                  <option value="">Выберите статус</option>
                  <option v-for="status in statuses" :key="status.id" :value="status.id">
                    {{ status.name }}
                  </option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Срок</label>
                <input v-model="createForm.due_date" type="date" class="form-control" />
              </div>
            </div>

            <div class="mt-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">Исполнители</label>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary"
                  @click="addCreatePerformer"
                >
                  Добавить
                </button>
              </div>

              <div v-if="createForm.performers.length === 0" class="text-muted small">
                Исполнители не выбраны
              </div>

              <div
                v-for="(performer, index) in createForm.performers"
                :key="`${performer.user_id}-${index}`"
                class="row g-2 align-items-center mb-2"
              >
                <div class="col-md-7">
                  <select v-model="performer.user_id" class="form-select">
                    <option
                      v-for="user in users"
                      :key="user.id"
                      :value="user.id"
                    >
                      {{ fullName(user) }}
                    </option>
                  </select>
                </div>

                <div class="col-md-3">
                  <select v-model="performer.role" class="form-select">
                    <option value="executor">Исполнитель</option>
                    <option value="observer">Наблюдатель</option>
                    <option value="reviewer">Проверяющий</option>
                  </select>
                </div>

                <div class="col-md-2">
                  <button
                    type="button"
                    class="btn btn-outline-danger w-100"
                    @click="removeCreatePerformer(index)"
                  >
                    Убрать
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Отмена
            </button>
            <button type="button" class="btn btn-theme" :disabled="creating" @click="createTask">
              {{ creating ? 'Создание...' : 'Создать задачу' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Modal } from 'bootstrap';
import AppLayout from '../layouts/AppLayout.vue';

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

const formatShortDate = (value) => {
  if (!value) return '';

  return new Date(value).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

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

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

const fullName = (user) => {
  if (!user) return '';
  return [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ');
};

const loadStatuses = async () => {
  const response = await window.axios.get('/api/task-statuses');
  statuses.value = response.data;

  if (!createForm.status_id && statuses.value.length) {
    createForm.status_id = statuses.value[0].id;
  }
};

const loadUsers = async () => {
  try {
    const response = await window.axios.get('/api/users');
    users.value = response.data.data || response.data || [];
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

    const response = await window.axios.get('/api/tasks', { params });
    const payload = response.data;

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

const priorityLabel = (priority) => {
  const map = {
    low: 'Низкий',
    medium: 'Средний',
    high: 'Высокий',
    critical: 'Критический',
  };

  return map[priority] || priority;
};

const priorityClass = (priority) => {
  const map = {
    low: 'text-bg-secondary',
    medium: 'text-bg-info',
    high: 'text-bg-warning',
    critical: 'text-bg-danger',
  };

  return map[priority] || 'text-bg-secondary';
};

const isOverdue = (task) => {
  if (!task?.due_date) return false;
  if (task?.status?.code === 'done') return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueDate = new Date(task.due_date);
  dueDate.setHours(0, 0, 0, 0);

  return dueDate < today;
};

const isDueToday = (task) => {
  if (!task?.due_date) return false;
  if (task?.status?.code === 'done') return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueDate = new Date(task.due_date);
  dueDate.setHours(0, 0, 0, 0);

  return dueDate.getTime() === today.getTime();
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

  const selectedIds = createForm.performers.map(item => item.user_id);
  const firstAvailable = users.value.find(user => !selectedIds.includes(user.id));

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
    await window.axios.post('/api/tasks', {
      title: createForm.title,
      description: createForm.description,
      priority: createForm.priority,
      status_id: createForm.status_id,
      due_date: createForm.due_date || null,
      performers: createForm.performers,
    });

    await loadTasks(1);
    resetCreateForm();

    if (createTaskModalInstance) {
      createTaskModalInstance.hide();
    }
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
  if (modalEl) {
    createTaskModalInstance = new Modal(modalEl);

    modalEl.addEventListener('hidden.bs.modal', () => {
      resetCreateForm();
    });
  }
});
</script>

<style scoped>
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
</style>