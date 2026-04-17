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
              Основной контекст, статусы, файлы и обсуждение собраны в одном месте, чтобы по задаче
              было легко ориентироваться.
            </p>

            <div class="d-flex flex-wrap gap-2 task-badges">
              <span class="badge text-bg-primary">
                {{ task.status?.name || '—' }}
              </span>

              <span class="badge" :class="priorityClass(task.priority)">
                {{ priorityLabel(task.priority) }}
              </span>

              <span v-if="isDueToday(task)" class="badge due-today-badge">
                Сегодня · {{ formatShortDate(task.due_date) }}
              </span>

              <span v-else-if="isOverdue(task)" class="badge text-bg-danger">
                Просрочено · {{ formatShortDate(task.due_date) }}
              </span>

              <span v-else class="badge text-bg-light">
                Срок: {{ formatShortDate(task.due_date) || 'не указан' }}
              </span>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 task-summary-actions">
            <select
              v-if="canChangeStatus && !canEdit"
              class="form-select form-select-sm task-status-select"
              :value="task.status_id"
              @change="changeStatus($event)"
            >
              <option v-for="status in statuses" :key="status.id" :value="status.id">
                {{ status.name }}
              </option>
            </select>

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
              Карточка
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

        <TaskGeneralTab
          v-if="activeTab === 'general'"
          :available-users="availableUsers"
          :can-edit="canEdit"
          :edit-form="editForm"
          :error-message="errorMessage"
          :statuses="statuses"
          :success-message="successMessage"
          :task="task"
          :users="users"
          @add-performer="addPerformer"
          @commit="saveTask"
          @remove-performer="removePerformer"
        />

        <div v-if="activeTab === 'discussion'" class="row g-3 detail-tab-panel discussion-layout">
          <div class="col-12 col-xl-8 discussion-column">
            <TaskChat :task-id="Number(id)" compact />
          </div>

          <div class="col-12 col-xl-4 discussion-column">
            <TaskHistoryPanel :histories="task.histories || []" />
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

    <TaskCompletionModal
      :form="completeForm"
      :error="completeError"
      :submitting="completing"
      @close="closeCompleteModal"
      @submit="completeTask"
    />
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Modal } from 'bootstrap';
import AppLayout from '../layouts/AppLayout.vue';
import TaskChat from '../components/TaskChat.vue';
import TaskFiles from '../components/TaskFiles.vue';
import TaskCompletionModal from '../components/tasks/TaskCompletionModal.vue';
import TaskGeneralTab from '../components/tasks/TaskGeneralTab.vue';
import TaskHistoryPanel from '../components/tasks/TaskHistoryPanel.vue';
import { fetchTaskStatuses } from '../api/lookups';
import { completeTaskRequest, fetchTask, updateTaskRequest } from '../api/tasks';
import { fetchUsers } from '../api/users';
import { ensureCurrentUserLoaded } from '../services/authState';
import { useTaskPresentation } from '../composables/useTaskPresentation';

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

let completeModalInstance = null;
const completing = ref(false);
const completeError = ref('');

const completeForm = reactive({
  status_code: 'done',
  completion_report: '',
});

const {
  formatShortDate,
  isDueToday,
  isOverdue,
  priorityClass,
  priorityLabel,
} = useTaskPresentation();

const hasAdminRole = (user) => {
  return Array.isArray(user?.roles) && user.roles.some((role) => role.name === 'admin');
};

const canEdit = computed(() => {
  if (!task.value || !currentUser.value) return false;

  return currentUser.value.id === task.value.creator_id || hasAdminRole(currentUser.value);
});

const canChangeStatus = computed(() => {
  if (!task.value || !currentUser.value) return false;

  const isAdmin = hasAdminRole(currentUser.value);
  const isCreator = currentUser.value.id === task.value.creator_id;
  const isPerformer = Array.isArray(task.value.performers)
    && task.value.performers.some((user) => user.id === currentUser.value.id);

  return isAdmin || isCreator || isPerformer;
});

const canCompleteTask = computed(() => {
  if (!task.value || !currentUser.value) return false;
  if (['done', 'cancelled'].includes(task.value.status?.code)) return false;

  const isAdmin = hasAdminRole(currentUser.value);
  const isCreator = currentUser.value.id === task.value.creator_id;
  const isPerformer = Array.isArray(task.value.performers)
    && task.value.performers.some((user) => user.id === currentUser.value.id);

  return isAdmin || isCreator || isPerformer;
});

const availableUsers = computed(() => {
  const selectedIds = editForm.performers.map((item) => item.user_id);
  return users.value.filter((user) => !selectedIds.includes(user.id));
});

const loadCurrentUser = async () => {
  currentUser.value = await ensureCurrentUserLoaded();
};

const loadStatuses = async () => {
  statuses.value = await fetchTaskStatuses();
};

const loadUsers = async () => {
  try {
    const response = await fetchUsers();
    users.value = response.data || response || [];
  } catch {
    users.value = [];
  }
};

const loadTask = async () => {
  loading.value = true;

  try {
    task.value = await fetchTask(props.id);
    fillEditForm();
  } catch {
    task.value = null;
  } finally {
    loading.value = false;
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

const addPerformer = () => {
  const firstAvailable = availableUsers.value[0];
  if (!firstAvailable) return;

  editForm.performers.push({
    user_id: firstAvailable.id,
    role: 'executor',
  });

  saveTask();
};

const removePerformer = (index) => {
  editForm.performers.splice(index, 1);
  saveTask();
};

const saveTask = async () => {
  if (!canEdit.value || !task.value || saving.value) return;

  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await updateTaskRequest(props.id, {
      title: editForm.title,
      description: editForm.description,
      priority: editForm.priority,
      status_id: editForm.status_id,
      due_date: editForm.due_date || null,
      performers: editForm.performers,
    });

    await loadTask();
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
    await updateTaskRequest(props.id, { status_id: statusId });
    await loadTask();
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || 'Не удалось обновить статус задачи';
  }
};

const resetCompletionForm = () => {
  completeError.value = '';
  completeForm.status_code = 'done';
  completeForm.completion_report = '';
};

const openCompleteModal = () => {
  resetCompletionForm();
  completeModalInstance?.show();
};

const closeCompleteModal = () => {
  completeModalInstance?.hide();
  resetCompletionForm();
};

const completeTask = async () => {
  completing.value = true;
  completeError.value = '';

  try {
    const response = await completeTaskRequest(props.id, {
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

.discussion-layout {
  align-items: stretch;
  display: flex;
  min-height: 0;
  height: min(820px, calc(100dvh - 250px));
  max-height: calc(100dvh - 250px);
  overflow: hidden;
}

.discussion-column {
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.due-today-badge {
  background: #8f000e;
  color: #fff;
  font-weight: 700;
}

@media (max-width: 1199px) {
  .discussion-layout {
    height: auto;
    max-height: none;
    flex-direction: column;
    overflow: visible;
  }

  .discussion-column {
    height: auto;
  }
}

@media (max-width: 992px) {
  .task-summary-actions {
    width: 100%;
  }

  .task-status-select {
    min-width: 100%;
  }

  .detail-tabs {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    width: 100%;
  }
}

@media (max-width: 640px) {
  .task-summary-card,
  .detail-panel {
    border-radius: 18px;
  }

  .task-summary-actions {
    width: 100%;
  }

  .task-summary-actions > * {
    flex: 1 1 100%;
  }

  .detail-tabs {
    grid-template-columns: 1fr;
  }

  .nav-tabs .nav-link {
    width: 100%;
  }
}
</style>
