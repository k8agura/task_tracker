<template>
  <AppLayout>
    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2 text-muted">Загрузка задачи...</div>
    </div>

    <div v-else-if="task" class="task-details-page">
      <div class="card-soft p-3 task-summary-card mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
          <div class="task-summary-main">
            <div class="text-muted small mb-1">Задача #{{ task.id }}</div>
            <h1 class="task-summary-title mb-2">{{ task.title }}</h1>
            <p class="task-summary-copy mb-0">
              Основной контекст, статусы, файлы и обсуждение собраны в одном месте, чтобы по задаче было легко ориентироваться.
            </p>

            <div class="d-flex flex-wrap gap-2 task-badges">
              <span class="badge text-bg-primary">
                {{ task.status?.name || '—' }}
              </span>

              <span class="badge" :class="priorityClass(task.priority)">
                {{ priorityLabel(task.priority) }}
              </span>
              <span
                v-if="isDueToday(task)"
                class="badge due-today-badge"
              >
                🔥 Срок: {{ formatShortDate(task.due_date) }}
              </span>

              <span
                v-else-if="isOverdue(task)"
                class="badge text-bg-danger"
              >
                Просрочено · {{ formatShortDate(task.due_date) }}
              </span>

              <span
                v-else
                class="badge text-bg-light"
              >
                Срок: {{ formatShortDate(task.due_date) || 'не указан' }}
              </span>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 task-summary-actions">
            <select
              v-if="canChangeStatus && !isEditing"
              class="form-select form-select-sm task-status-select"
              :value="task.status_id"
              @change="changeStatus($event)"
            >
              <option v-for="status in statuses" :key="status.id" :value="status.id">
                {{ status.name }}
              </option>
            </select>

            <button
              v-if="canEdit && activeTab === 'general' && !isEditing"
              class="btn btn-theme btn-sm"
              @click="startEdit"
            >
              Редактировать
            </button>

            <button
              v-if="canEdit && activeTab === 'general' && isEditing"
              class="btn btn-success btn-sm"
              :disabled="saving"
              @click="saveTask"
            >
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>

            <button
              v-if="canEdit && activeTab === 'general' && isEditing"
              class="btn btn-outline-secondary btn-sm"
              @click="cancelEdit"
            >
              Отмена
            </button>

            <button
                v-if="canCompleteTask"
                class="btn btn-success btn-sm"
                @click="openCompleteModal"
              >
                Завершить работу
              </button>
            <RouterLink to="/tasks" class="btn btn-outline-secondary btn-sm">
              Назад
            </RouterLink>
          </div>
        </div>
      </div>

      <div class="card-soft p-3 detail-panel">
        <ul class="nav nav-tabs detail-tabs mb-3" role="tablist">
          <li class="nav-item">
            <button
              class="nav-link"
              :class="{ active: activeTab === 'general' }"
              type="button"
              @click="activeTab = 'general'"
            >
              Общее
            </button>
          </li>

          <li class="nav-item">
            <button
              class="nav-link"
              :class="{ active: activeTab === 'discussion' }"
              type="button"
              @click="activeTab = 'discussion'"
            >
              Обсуждение
            </button>
          </li>

          <li class="nav-item">
            <button
              class="nav-link"
              :class="{ active: activeTab === 'files' }"
              type="button"
              @click="activeTab = 'files'"
            >
              Файлы
            </button>
          </li>
        </ul>

        <div v-if="activeTab === 'general'" class="row g-3 detail-tab-panel detail-tab-scroll">
          <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-header bg-white py-2">
                <h6 class="mb-0">Основная информация</h6>
              </div>

              <div class="card-body">
                <div v-if="errorMessage" class="alert alert-danger py-2">
                  {{ errorMessage }}
                </div>

                <div v-if="successMessage" class="alert alert-success py-2">
                  {{ successMessage }}
                </div>

                <template v-if="isEditing">
                  <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input v-model="editForm.title" type="text" class="form-control" />
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Описание</label>
                    <textarea v-model="editForm.description" rows="4" class="form-control"></textarea>
                  </div>

                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Приоритет</label>
                      <select v-model="editForm.priority" class="form-select">
                        <option value="low">Низкий</option>
                        <option value="medium">Средний</option>
                        <option value="high">Высокий</option>
                        <option value="critical">Критический</option>
                      </select>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label">Статус</label>
                      <select v-model="editForm.status_id" class="form-select">
                        <option v-for="status in statuses" :key="status.id" :value="status.id">
                          {{ status.name }}
                        </option>
                      </select>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label">Срок</label>
                      <input v-model="editForm.due_date" type="date" class="form-control" />
                    </div>
                  </div>
                  <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="form-label mb-0">Исполнители</label>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        @click="addPerformer"
                        :disabled="availableUsers.length === 0"
                      >
                        Добавить исполнителя
                      </button>
                    </div>

                    <div v-if="editForm.performers.length === 0" class="text-muted small">
                      Исполнители не выбраны
                    </div>

                    <div
                      v-for="(performer, index) in editForm.performers"
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
                          @click="removePerformer(index)"
                        >
                          Убрать
                        </button>
                      </div>
                    </div>
                  </div>
                </template>

                <template v-else>
                  <div class="mb-3">
                    <div class="text-muted small mb-1">Описание</div>
                    <div>{{ task.description || '—' }}</div>
                  </div>

                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-muted small mb-1">Постановщик</div>
                      <div>{{ fullName(task.creator) || '—' }}</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-muted small mb-1">Подразделение</div>
                      <div>{{ task.creator?.department?.name || '—' }}</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-muted small mb-1">Статус</div>
                      <div>{{ task.status?.name || '—' }}</div>
                    </div>

                    <div class="col-md-6">
                      <div class="text-muted small mb-1">Приоритет</div>
                      <div>{{ priorityLabel(task.priority) }}</div>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
          <div v-if="task.completion_report" class="mb-3">
            <div class="text-muted small mb-1">Итоговый отчёт</div>
            <div class="p-3 rounded bg-light border">
              {{ task.completion_report }}
            </div>
          </div>

          <div v-if="task.completed_at" class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="text-muted small mb-1">Завершена</div>
              <div>{{ formatDate(task.completed_at) }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small mb-1">Завершил</div>
              <div>{{ fullName(task.completed_by) || '—' }}</div>
            </div>
          </div>
          <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white py-2">
                <h6 class="mb-0">Исполнители</h6>
              </div>
              <div class="card-body py-2">
                <div v-if="!task.performers?.length" class="text-muted py-2">
                  Исполнители не назначены
                </div>

                <div
                  v-for="performer in task.performers"
                  :key="performer.id"
                  class="performer-item"
                >
                  <div class="fw-semibold">{{ fullName(performer) }}</div>
                  <div class="small text-muted">
                    {{ performer.position || 'Без должности' }}
                  </div>
                  <div class="small text-muted">
                    Роль в задаче: {{ performer.pivot?.role || 'executor' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'discussion'" class="row g-3 detail-tab-panel discussion-layout">
          <div class="col-12 col-xl-8 discussion-column">
            <TaskChat :task-id="Number(id)" compact />
          </div>

          <div class="col-12 col-xl-4 discussion-column">
            <div class="card border-0 shadow-sm history-panel">
              <div class="card-header bg-white py-2">
                <h6 class="mb-0">История изменений</h6>
              </div>
              <div class="card-body history-scroll py-2">
                <div v-if="!task.histories?.length" class="text-muted">
                  История изменений отсутствует
                </div>

                <div
                  v-for="item in task.histories"
                  :key="item.id"
                  class="history-item"
                >
                  <div class="fw-semibold">{{ historyLabel(item.action) }}</div>
                  <div class="small text-muted">
                    {{ item.comment || 'Без комментария' }}
                  </div>
                  <div class="small text-muted">
                    {{ formatDate(item.created_at) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'files'" class="detail-tab-panel detail-tab-scroll">
          <TaskFiles :task-id="Number(id)" />
        </div>
      </div>
    </div>

    <div v-else class="alert alert-warning">
      Задача не найдена
    </div>
    <div class="modal fade" id="completeTaskModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title">Завершение задачи</h5>
              <div class="text-muted small">Выберите итоговый статус и заполните отчёт</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body pt-3">
            <div v-if="completeError" class="alert alert-danger py-2">
              {{ completeError }}
            </div>

            <div class="mb-3">
              <label class="form-label">Итоговый статус</label>
              <select v-model="completeForm.status_code" class="form-select">
                <option value="done">Завершена</option>
                <option value="cancelled">Отменена</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Отчёт по задаче</label>
              <textarea
                v-model="completeForm.completion_report"
                rows="6"
                class="form-control"
                placeholder="Опишите результат выполнения задачи"
              ></textarea>
              <div class="form-text">
                Поле обязательно для заполнения
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary" @click="closeCompleteModal">
              Отмена
            </button>
            <button type="button" class="btn btn-success" :disabled="completing" @click="completeTask">
              {{ completing ? 'Сохранение...' : 'Подтвердить' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Modal } from 'bootstrap';
import AppLayout from '../layouts/AppLayout.vue';
import TaskChat from '../components/TaskChat.vue';
import TaskFiles from '../components/TaskFiles.vue';

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
});

const task = ref(null);
const currentUser = ref(null);
const statuses = ref([]);
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('general');
const isEditing = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const users = ref([]);

const editForm = reactive({
  title: '',
  description: '',
  priority: 'medium',
  status_id: '',
  due_date: '',
  performers: [],
});

const completeModal = ref(null);
let completeModalInstance = null;

const completing = ref(false);
const completeError = ref('');

const completeForm = reactive({
  status_code: 'done',
  completion_report: '',
});

const formatShortDate = (value) => {
  if (!value) return '';

  return new Date(value).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const canEdit = computed(() => {
  if (!task.value || !currentUser.value) return false;
  return currentUser.value.id === task.value.creator_id || hasAdminRole(currentUser.value);
});

const canChangeStatus = computed(() => {
  if (!task.value || !currentUser.value) return false;

  const isAdmin = hasAdminRole(currentUser.value);
  const isCreator = currentUser.value.id === task.value.creator_id;
  const isPerformer = Array.isArray(task.value.performers) &&
    task.value.performers.some(user => user.id === currentUser.value.id);

  return isAdmin || isCreator || isPerformer;
});

const hasAdminRole = (user) => {
  return Array.isArray(user?.roles) && user.roles.some(role => role.name === 'admin');
};

const loadCurrentUser = async () => {
  const response = await window.axios.get('/api/me');
  currentUser.value = response.data;
};

const loadStatuses = async () => {
  const response = await window.axios.get('/api/task-statuses');
  statuses.value = response.data;
};

const loadTask = async () => {
  loading.value = true;

  try {
    const response = await window.axios.get(`/api/tasks/${props.id}`);
    task.value = response.data;
  } catch (e) {
    task.value = null;
  } finally {
    loading.value = false;
  }
};

const loadUsers = async () => {
  try {
    const response = await window.axios.get('/api/users');
    users.value = response.data.data || response.data || [];
  } catch (e) {
    users.value = [];
  }
};

const fillEditForm = () => {
  if (!task.value) return;

  editForm.title = task.value.title || '';
  editForm.description = task.value.description || '';
  editForm.priority = task.value.priority || 'medium';
  editForm.status_id = task.value.status_id || '';
  editForm.due_date = task.value.due_date || '';
  editForm.performers = (task.value.performers || []).map((performer) => ({
    user_id: performer.id,
    role: performer.pivot?.role || 'executor',
  }));
};

const availableUsers = computed(() => {
  const selectedIds = editForm.performers.map(item => item.user_id);
  return users.value.filter(user => !selectedIds.includes(user.id));
});

const addPerformer = () => {
  const firstAvailable = availableUsers.value[0];
  if (!firstAvailable) return;

  editForm.performers.push({
    user_id: firstAvailable.id,
    role: 'executor',
  });
};

const removePerformer = (index) => {
  editForm.performers.splice(index, 1);
};

const getUserNameById = (id) => {
  const user = users.value.find(u => u.id === id);
  return user ? fullName(user) : `Пользователь #${id}`;
};

const startEdit = () => {
  errorMessage.value = '';
  successMessage.value = '';
  fillEditForm();
  isEditing.value = true;
};

const cancelEdit = () => {
  isEditing.value = false;
  errorMessage.value = '';
  successMessage.value = '';
};

const saveTask = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await window.axios.put(`/api/tasks/${props.id}`, {
      title: editForm.title,
      description: editForm.description,
      priority: editForm.priority,
      status_id: editForm.status_id,
      due_date: editForm.due_date || null,
      performers: editForm.performers,
    });

    await loadTask();
    isEditing.value = false;
    successMessage.value = 'Задача успешно обновлена';
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || 'Не удалось сохранить изменения';
  } finally {
    saving.value = false;
  }
};

const changeStatus = async (event) => {
  const statusId = Number(event.target.value);

  try {
    await window.axios.put(`/api/tasks/${props.id}`, {
      status_id: statusId,
    });

    await loadTask();
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || 'Не удалось обновить статус задачи';
  }
};

const fullName = (user) => {
  if (!user) return '';
  return [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ');
};

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ru-RU');
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

const isDueToday = (task) => {
  if (!task?.due_date) return false;
  if (task?.status?.code === 'done') return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueDate = new Date(task.due_date);
  dueDate.setHours(0, 0, 0, 0);

  return dueDate.getTime() === today.getTime();
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

const historyLabel = (action) => {
  const map = {
    created: 'Создание задачи',
    updated: 'Обновление задачи',
    deleted: 'Удаление задачи',
    message_created: 'Добавлено сообщение',
    message_deleted: 'Удалено сообщение',
    attachment_uploaded: 'Загружен файл к задаче',
    attachment_uploaded_to_message: 'Загружен файл к сообщению',
    attachment_deleted: 'Удалён файл',
  };

  return map[action] || action;
};

const openCompleteModal = () => {
  completeError.value = '';
  completeForm.status_code = 'done';
  completeForm.completion_report = '';
  completeModalInstance?.show();
};

const closeCompleteModal = () => {
  completeModalInstance?.hide();
  completeError.value = '';
  completeForm.status_code = 'done';
  completeForm.completion_report = '';
};

const completeTask = async () => {
  completing.value = true;
  completeError.value = '';

  try {
    const response = await window.axios.post(`/api/tasks/${props.id}/complete`, {
      status_code: completeForm.status_code,
      completion_report: completeForm.completion_report,
    });

    task.value = response.data.task;
    closeCompleteModal();
  } catch (error) {
    completeError.value =
      error?.response?.data?.message ||
      error?.response?.data?.errors?.completion_report?.[0] ||
      'Не удалось завершить задачу';
  } finally {
    completing.value = false;
  }
};

const canCompleteTask = computed(() => {
  if (!task.value || !currentUser.value) return false;

  if (['done', 'cancelled'].includes(task.value.status?.code)) {
    return false;
  }

  const isAdmin = hasAdminRole(currentUser.value);
  const isCreator = currentUser.value.id === task.value.creator_id;
  const isPerformer = Array.isArray(task.value.performers) &&
    task.value.performers.some(user => user.id === currentUser.value.id);

  return isAdmin || isCreator || isPerformer;
});

onMounted(async () => {
  await loadCurrentUser();
  await loadStatuses();
  await loadUsers();
  await loadTask();
  const modalEl = document.getElementById('completeTaskModal');
  if (modalEl) {
    completeModalInstance = new Modal(modalEl);
  }
});
</script>

<style scoped>
.task-details-page {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.task-summary-card {
  border-radius: 20px;
  flex: 0 0 auto;
}

.task-summary-main {
  min-width: 0;
}

.task-summary-title {
  font-size: clamp(1.2rem, 2vw, 1.55rem);
  line-height: 1.25;
  font-weight: 800;
  color: var(--text-1);
}

.task-summary-copy {
  max-width: 64ch;
  color: var(--text-muted);
  font-size: 0.92rem;
}

.task-badges {
  margin-top: 12px;
}

.task-summary-actions {
  align-items: flex-start;
  justify-content: flex-end;
}

.task-status-select {
  min-width: 240px;
  height: 44px;
  border-radius: 999px;
  border: 1px solid var(--border-strong);
  background:
    radial-gradient(circle at left center, rgba(38, 103, 255, 0.08), transparent 26%),
    linear-gradient(180deg, var(--surface-2) 0%, var(--surface-1) 100%);
  font-weight: 700;
  padding-left: 14px;
  padding-right: 36px;
  box-shadow: var(--shadow-soft);
}

.detail-panel {
  border-radius: 20px;
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  min-height: 0;
  overflow: hidden;
}

.detail-tabs {
  display: inline-flex;
  gap: 8px;
  padding: 4px;
  border: none;
  border-radius: 16px;
  background: var(--surface-1);
  flex: 0 0 auto;
}

.nav-tabs .nav-link {
  padding: 0.5rem 0.85rem;
  border-radius: 12px;
  color: var(--text-2);
  font-weight: 600;
  border: none;
  font-size: 0.9rem;
}

.nav-tabs .nav-link.active {
  color: var(--text-1);
  background: var(--surface-2);
  box-shadow: var(--shadow-soft);
}

.detail-tab-panel {
  flex: 1 1 auto;
  min-height: 0;
}

.detail-tabs.mb-3 {
  margin-bottom: 0.75rem !important;
}

.detail-tab-scroll {
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 4px;
}

.performer-item {
  padding: 10px 0;
  border-bottom: 1px solid var(--border-soft);
}

.performer-item:last-child {
  border-bottom: none;
}

.discussion-layout {
  align-items: stretch;
  display: flex;
  min-height: 0;
  height: 100%;
  max-height: 100%;
  overflow: hidden;
}

.discussion-column {
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.history-panel {
  flex: 1 1 auto;
  min-height: 0;
  max-height: 100%;
}

.history-scroll {
  flex: 1 1 auto;
  min-height: 0;
  max-height: none;
  overflow-y: auto;
}

.history-item {
  padding: 0 0 12px;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--border-soft);
}

.history-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.due-today-badge {
  background: linear-gradient(135deg, #ff4d4f 0%, #c81e1e 100%);
  color: #fff;
  font-weight: 700;
  box-shadow: 0 6px 14px rgba(220, 53, 69, 0.25);
}

@media (max-width: 1199px) {
  .discussion-layout {
    height: auto;
    max-height: none;
    overflow: visible;
  }

  .history-panel {
    min-height: 420px;
    max-height: 500px;
  }

  .history-scroll {
    max-height: 440px;
  }
}

@media (max-width: 992px) {
  .task-details-page {
    height: auto;
    overflow: visible;
  }

  .detail-panel {
    overflow: visible;
  }

  .detail-tab-panel,
  .detail-tab-scroll {
    overflow: visible;
  }

  .task-summary-actions {
    justify-content: flex-start;
  }

  .task-status-select {
    min-width: 100%;
  }
}
</style>
