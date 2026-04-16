<template>
  <AppLayout>
    <section class="dashboard-page">

      <div v-if="errorMessage" class="alert alert-danger py-2 mb-4">
        {{ errorMessage }}
      </div>

      <div class="row g-2 mb-2 metrics-row">
        <div class="col-12 col-md-6 col-xl-3" v-for="metric in metrics" :key="metric.label">
          <div class="metric-card card-soft p-2 h-100">
            <div class="metric-label">{{ metric.label }}</div>
            <div class="metric-value">{{ metric.value }}</div>
            <div class="metric-note">{{ metric.note }}</div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="board-loading card-soft bg-white p-5 text-center text-muted">
        Загружаем задачи и собираем доску...
      </div>

      <template v-else>
        <section class="board-section">
          <div class="board-header card-soft p-2 mb-2">
            <div class="board-tabs">
              <button
                type="button"
                class="board-tab"
                :class="{ active: activeBoardTab === 'mine' }"
                @click="activeBoardTab = 'mine'"
              >
                Мои задачи
                <span class="board-tab-count">{{ myTasks.length }}</span>
              </button>

              <button
                type="button"
                class="board-tab"
                :class="{ active: activeBoardTab === 'all' }"
                @click="activeBoardTab = 'all'"
              >
                Все задачи
                <span class="board-tab-count">{{ filteredTasks.length }}</span>
              </button>
            </div>
          </div>

          <div class="board-scroll">
            <div class="board-grid" :style="boardGridStyle">
              <article
                v-for="column in activeColumns"
                :key="`${activeBoardTab}-${column.id}`"
                class="board-column card-soft"
                :class="{ 'column-drop-active': dragState.overId === column.id }"
              >
                <header class="column-header">
                  <div>
                    <div class="column-title">{{ column.name }}</div>
                    <div class="column-subtitle">
                      {{ activeBoardTab === 'mine' ? (column.code || 'status') : `${column.tasks.length} карточек` }}
                    </div>
                  </div>
                  <span class="column-count">{{ column.tasks.length }}</span>
                </header>

                <div
                  class="column-body"
                  @dragenter.prevent="handleColumnDragOver(column.id)"
                  @dragover.prevent="handleColumnDragOver(column.id, $event)"
                  @dragleave="handleColumnDragLeave(column.id)"
                  @drop.prevent="handleDrop(column.id)"
                >
                  <div
                    v-for="task in column.tasks"
                    :key="task.id"
                    class="task-card"
                    :class="{
                      'task-card-draggable': canChangeStatus(task),
                      'task-card-ghost': dragState.taskId === task.id,
                    }"
                    :draggable="canChangeStatus(task)"
                    @click="openTask(task.id)"
                    @dragstart="handleDragStart(task, column.id, $event)"
                    @dragend="handleDragEnd"
                  >
                    <div class="task-card-top">
                      <span class="task-id">#{{ task.id }}</span>
                      <span class="priority-pill" :class="`priority-${task.priority || 'medium'}`">
                        {{ priorityLabel(task.priority) }}
                      </span>
                    </div>

                    <div class="task-title">{{ task.title }}</div>
                    <div class="task-desc">{{ task.description || 'Без описания' }}</div>

                    <div v-if="activeBoardTab === 'all'" class="task-stack compact-stack">
                      <div class="stack-line">
                        <span class="stack-label">Автор</span>
                        <span>{{ fullName(task.creator) || '—' }}</span>
                      </div>
                    </div>

                    <div class="task-meta">
                      <span class="task-meta-chip">{{ performerSummary(task) }}</span>
                      <span :class="{ overdue: isOverdue(task), today: isDueToday(task) }">
                        {{ dueLabel(task) }}
                      </span>
                    </div>

                    <div class="task-actions" @click.stop>
                      <select
                        class="form-select form-select-sm"
                        :value="String(task.status_id)"
                        :disabled="!canChangeStatus(task) || updatingTaskIds.includes(task.id)"
                        @change="handleStatusSelect(task, $event)"
                      >
                        <option v-for="status in statuses" :key="`${activeBoardTab}-status-${task.id}-${status.id}`" :value="String(status.id)">
                          {{ status.name }}
                        </option>
                      </select>

                      <button class="btn btn-sm btn-outline-secondary" @click="openTask(task.id)">
                        Открыть
                      </button>
                    </div>

                    <div v-if="updatingTaskIds.includes(task.id)" class="task-inline-note">
                      Сохраняем статус...
                    </div>
                    <div v-else-if="!canChangeStatus(task)" class="task-inline-note muted">
                      Только просмотр
                    </div>
                  </div>

                  <div v-if="column.tasks.length === 0" class="empty-column">
                    {{ activeBoardTab === 'mine' ? 'В этой колонке пока пусто' : 'Нет задач с этим статусом' }}
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>
      </template>
    </section>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import { loadCurrentUser, useAuthState } from '../services/authState';

const router = useRouter();
const { currentUser, userLoaded, isAdmin } = useAuthState();

const loading = ref(false);
const errorMessage = ref('');
const statuses = ref([]);
const tasks = ref([]);
const activeBoardTab = ref('mine');

const updatingTaskIds = ref([]);
const dragState = reactive({
  taskId: null,
  fromId: null,
  overId: null,
  suppressClickForTaskId: null,
});

const resetDragState = () => {
  dragState.taskId = null;
  dragState.fromId = null;
  dragState.overId = null;
};

const boardGridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${Math.max(statuses.value.length, 1)}, minmax(300px, 1fr))`,
}));

const fullName = (user) => {
  if (!user) return '';
  return [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ');
};

const priorityLabel = (priority) => {
  const map = {
    low: 'Низкий',
    medium: 'Средний',
    high: 'Высокий',
    critical: 'Критический',
  };

  return map[priority] || 'Средний';
};

const formatDate = (value) => {
  if (!value) return 'Без срока';

  return new Date(value).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const isDone = (task) => ['done', 'cancelled'].includes(task?.status?.code);

const isOverdue = (task) => {
  if (!task?.due_date || isDone(task)) return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueDate = new Date(task.due_date);
  dueDate.setHours(0, 0, 0, 0);

  return dueDate < today;
};

const isDueToday = (task) => {
  if (!task?.due_date || isDone(task)) return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dueDate = new Date(task.due_date);
  dueDate.setHours(0, 0, 0, 0);

  return dueDate.getTime() === today.getTime();
};

const dueLabel = (task) => {
  if (!task?.due_date) return 'Без срока';
  if (isDueToday(task)) return `Сегодня · ${formatDate(task.due_date)}`;
  if (isOverdue(task)) return `Просрочено · ${formatDate(task.due_date)}`;
  return formatDate(task.due_date);
};

const performerSummary = (task) => {
  if (!Array.isArray(task?.performers) || task.performers.length === 0) {
    return 'Исполнители не назначены';
  }

  const names = task.performers.map((item) => fullName(item)).filter(Boolean);

  if (names.length <= 2) {
    return names.join(', ');
  }

  return `${names.slice(0, 2).join(', ')} +${names.length - 2}`;
};

const canChangeStatus = (task) => {
  if (!currentUser.value || !task) return false;
  const isCreator = currentUser.value.id === task.creator_id;
  const isPerformer = Array.isArray(task.performers) && task.performers.some((user) => user.id === currentUser.value.id);
  return isAdmin.value || isCreator || isPerformer;
};

const normalizeColumns = (sourceTasks) => {
  return statuses.value.map((status) => ({
    ...status,
    tasks: sourceTasks
      .filter((task) => task.status_id === status.id)
      .sort((left, right) => {
        const leftDue = left.due_date ? new Date(left.due_date).getTime() : Number.MAX_SAFE_INTEGER;
        const rightDue = right.due_date ? new Date(right.due_date).getTime() : Number.MAX_SAFE_INTEGER;

        if (leftDue !== rightDue) {
          return leftDue - rightDue;
        }

        return right.id - left.id;
      }),
  }));
};

const filteredTasks = computed(() => tasks.value);

const myTasks = computed(() => {
  const userId = currentUser.value?.id;
  if (!userId) return [];

  return filteredTasks.value.filter((task) => {
    const isCreator = task.creator_id === userId;
    const isPerformer = Array.isArray(task.performers) && task.performers.some((user) => user.id === userId);
    return isCreator || isPerformer;
  });
});

const myTaskColumns = computed(() => normalizeColumns(myTasks.value));
const allTaskColumns = computed(() => normalizeColumns(filteredTasks.value));
const activeColumns = computed(() => (activeBoardTab.value === 'mine' ? myTaskColumns.value : allTaskColumns.value));
const activeBoardMeta = computed(() => {
  return activeBoardTab.value === 'mine'
    ? `${myTasks.value.length} задач в личном фокусе`
    : `${filteredTasks.value.length} задач после фильтров`;
});

const metrics = computed(() => {
  const openTasks = filteredTasks.value.filter((task) => !isDone(task));
  const myOpenTasks = myTasks.value.filter((task) => !isDone(task));
  const overdueTasks = filteredTasks.value.filter((task) => isOverdue(task));
  const doneTasks = filteredTasks.value.filter((task) => task?.status?.code === 'done');

  return [
    {
      label: 'Всего задач',
      value: filteredTasks.value.length,
      note: 'Видимые задачи после фильтров',
    },
    {
      label: 'Мои активные',
      value: myOpenTasks.length,
      note: 'Созданные мной или назначенные мне',
    },
    {
      label: 'Просрочено',
      value: overdueTasks.length,
      note: 'Нуждаются в ближайшем внимании',
    },
    {
      label: 'Завершено',
      value: doneTasks.length,
      note: `Открытых задач: ${openTasks.length}`,
    },
  ];
});

const fetchTaskPage = async (page) => {
  const response = await window.axios.get('/api/tasks', {
    params: {
      page,
      sort: 'due_asc',
    },
  });

  return response.data;
};

const loadAllTasks = async () => {
  const firstPage = await fetchTaskPage(1);
  const collected = [...(firstPage.data || [])];

  for (let page = 2; page <= (firstPage.last_page || 1); page += 1) {
    const payload = await fetchTaskPage(page);
    collected.push(...(payload.data || []));
  }

  tasks.value = collected;
};

const replaceTask = (nextTask) => {
  tasks.value = tasks.value.map((task) => (task.id === nextTask.id ? nextTask : task));
};

const snapshotTask = (task) => ({
  ...task,
  status: task.status ? { ...task.status } : null,
  creator: task.creator ? { ...task.creator } : null,
  performers: Array.isArray(task.performers)
    ? task.performers.map((performer) => ({
        ...performer,
        pivot: performer.pivot ? { ...performer.pivot } : undefined,
      }))
    : [],
  chat: task.chat ? { ...task.chat } : null,
});

const updateTaskStatus = async (task, statusId) => {
  if (!canChangeStatus(task) || task.status_id === statusId || updatingTaskIds.value.includes(task.id)) {
    return;
  }

  const previousTask = snapshotTask(task);
  const nextStatus = statuses.value.find((status) => status.id === statusId);
  const optimisticTask = {
    ...snapshotTask(task),
    status_id: statusId,
    status: nextStatus || task.status,
  };

  replaceTask(optimisticTask);
  updatingTaskIds.value = [...updatingTaskIds.value, task.id];
  errorMessage.value = '';

  try {
    const response = await window.axios.put(`/api/tasks/${task.id}`, {
      status_id: statusId,
    });

    replaceTask(response.data);
  } catch (error) {
    replaceTask(previousTask);
    errorMessage.value = error?.response?.data?.message || 'Не удалось обновить статус задачи';
  } finally {
    updatingTaskIds.value = updatingTaskIds.value.filter((id) => id !== task.id);
  }
};

const handleStatusSelect = async (task, event) => {
  const statusId = Number(event.target.value);
  await updateTaskStatus(task, statusId);
};

const handleDragStart = (task, columnId, event) => {
  if (!canChangeStatus(task)) {
    event.preventDefault();
    return;
  }

  dragState.taskId = task.id;
  dragState.fromId = columnId;
  dragState.overId = columnId;

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(task.id));
  }
};

const handleColumnDragOver = (columnId, event) => {
  if (dragState.taskId) {
    dragState.overId = columnId;

    if (event?.dataTransfer) {
      event.dataTransfer.dropEffect = 'move';
    }
  }
};

const handleColumnDragLeave = (columnId) => {
  if (dragState.overId === columnId) {
    dragState.overId = null;
  }
};

const handleDragEnd = () => {
  if (dragState.taskId) {
    const draggedTaskId = dragState.taskId;
    dragState.suppressClickForTaskId = draggedTaskId;

    window.setTimeout(() => {
      if (dragState.suppressClickForTaskId === draggedTaskId) {
        dragState.suppressClickForTaskId = null;
      }
    }, 150);

    window.setTimeout(() => {
      if (dragState.taskId === draggedTaskId) {
        resetDragState();
      }
    }, 0);
  }
};

const handleDrop = async (statusId) => {
  if (!dragState.taskId) return;

  const task = tasks.value.find((item) => item.id === dragState.taskId);
  resetDragState();

  if (!task) return;
  await updateTaskStatus(task, statusId);
};

const loadDashboard = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    if (!userLoaded.value) {
      await loadCurrentUser();
    }

    const [statusResponse] = await Promise.all([
      window.axios.get('/api/task-statuses'),
      loadAllTasks(),
    ]);

    statuses.value = statusResponse.data || [];
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Не удалось собрать дашборд';
  } finally {
    loading.value = false;
  }
};

const openTask = (id) => {
  if (dragState.suppressClickForTaskId === id) {
    dragState.suppressClickForTaskId = null;
    return;
  }

  router.push(`/tasks/${id}`);
};

onMounted(async () => {
  await loadDashboard();
});
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.hero {
  background:
    radial-gradient(circle at top left, rgba(104, 199, 183, 0.24), transparent 30%),
    radial-gradient(circle at right center, rgba(106, 144, 240, 0.22), transparent 32%),
    linear-gradient(135deg, #eefaf7 0%, #f7fbff 100%);
}

.hero-topline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 6px;
}

.hero-stats {
  color: var(--text-muted);
  font-size: 0.8rem;
  font-weight: 700;
}

.hero-topline > .hero-stats {
  display: none;
}

.hero-stats-readable {
  display: block;
  margin-bottom: 4px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.72);
  color: #44636d;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-title {
  font-size: clamp(1.25rem, 1.7vw, 1.75rem);
  font-weight: 800;
  color: var(--text-1);
}

.hero-copy {
  max-width: 720px;
}

.hero-copy-text {
  margin-top: 8px;
  color: var(--text-muted);
  font-size: 0.9rem;
  max-width: 60ch;
}

.metric-card {
  background: linear-gradient(180deg, var(--surface-2) 0%, var(--surface-1) 100%);
  border-radius: 18px;
}

.metric-label {
  color: var(--text-muted);
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
  margin-bottom: 4px;
}

.metric-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--text-1);
  line-height: 1;
  margin-bottom: 4px;
}

.metric-note {
  color: var(--text-muted);
  font-size: 0.76rem;
  line-height: 1.25;
}

.section-meta {
  color: #67848d;
  font-weight: 600;
  font-size: 0.88rem;
}

.metrics-row {
  flex: 0 0 auto;
}

.board-loading {
  flex: 1;
}

.board-section {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.board-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 10px;
  flex: 0 0 auto;
}

.board-tabs {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.board-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--border-soft);
  background: var(--surface-1);
  color: var(--text-2);
  border-radius: 999px;
  padding: 7px 12px;
  font-size: 0.84rem;
  font-weight: 700;
}

.board-tab.active {
  background: linear-gradient(135deg, #68c7b7 0%, #6b96ff 100%);
  color: #fff;
  box-shadow: 0 10px 22px rgba(90, 140, 210, 0.18);
}

.board-tab-count {
  display: inline-grid;
  place-items: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.7);
  color: #274c56;
  font-size: 0.76rem;
}

.board-tab.active .board-tab-count {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}

.board-scroll {
  flex: 1;
  overflow-x: auto;
  overflow-y: auto;
  padding: 0 2px 8px 0;
  min-height: 0;
  overscroll-behavior: contain;
}

.board-grid {
  display: flex;
  flex-wrap: nowrap;
  align-items: flex-start;
  gap: 14px;
  min-width: max-content;
}

.board-column {
  flex: 0 0 300px;
  width: 300px;
  height: fit-content;
  max-height: 100%;
  background: linear-gradient(180deg, var(--surface-2) 0%, var(--surface-1) 100%);
  padding: 10px;
  transition: box-shadow 0.18s ease, transform 0.18s ease;
  border-radius: 18px;
}

.column-drop-active {
  box-shadow: 0 0 0 2px rgba(106, 144, 240, 0.32), 0 16px 34px rgba(92, 128, 138, 0.14);
  transform: translateY(-2px);
}

.column-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(112, 149, 160, 0.14);
}

.column-title {
  font-weight: 700;
  color: var(--text-1);
  font-size: 0.96rem;
}

.column-subtitle {
  color: var(--text-muted);
  font-size: 0.74rem;
}

.column-count {
  min-width: 30px;
  height: 30px;
  display: inline-grid;
  place-items: center;
  border-radius: 10px;
  background: var(--surface-3);
  color: var(--text-1);
  font-weight: 800;
  font-size: 0.82rem;
}

.column-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 12px;
  min-height: 180px;
}

.task-card {
  border: 1px solid var(--border-soft);
  border-radius: 16px;
  background: var(--surface-2);
  padding: 11px 11px 10px;
  text-align: left;
  box-shadow: var(--shadow-soft);
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, opacity 0.18s ease;
  cursor: pointer;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 28px rgba(92, 128, 138, 0.14);
  border-color: rgba(102, 145, 244, 0.32);
}

.task-card-draggable {
  cursor: grab;
}

.task-card-draggable:active {
  cursor: grabbing;
}

.task-card-ghost {
  opacity: 0.45;
}

.task-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.task-id {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-weight: 700;
}

.priority-pill {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 700;
}

.priority-low {
  background: #edf2f4;
  color: #566973;
}

.priority-medium {
  background: #e7f4ff;
  color: #25638c;
}

.priority-high {
  background: #fff1de;
  color: #9c5a14;
}

.priority-critical {
  background: #ffe1e1;
  color: #aa2525;
}

.task-title {
  color: var(--text-1);
  font-weight: 700;
  line-height: 1.35;
  margin-bottom: 6px;
  font-size: 0.95rem;
}

.task-desc {
  color: var(--text-muted);
  font-size: 0.84rem;
  line-height: 1.45;
  margin-bottom: 9px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.task-meta,
.task-stack {
  display: flex;
  flex-direction: column;
  gap: 4px;
  color: var(--text-2);
  font-size: 0.79rem;
}

.task-meta-chip {
  display: inline-flex;
  align-self: flex-start;
  padding: 4px 8px;
  border-radius: 999px;
  background: var(--surface-3);
}

.compact-stack {
  margin-bottom: 8px;
}

.stack-line {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.stack-label {
  color: var(--text-muted);
}

.task-actions {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 6px;
  margin-top: 10px;
}

.task-inline-note {
  margin-top: 6px;
  font-size: 0.72rem;
  color: #376d79;
  font-weight: 600;
}

.task-inline-note.muted {
  color: #7b9097;
  font-weight: 500;
}

.empty-column {
  min-height: 96px;
  display: grid;
  place-items: center;
  color: var(--text-muted);
  background: var(--surface-1);
  border: 1px dashed var(--border-soft);
  border-radius: 16px;
  font-size: 0.84rem;
}

.overdue {
  color: #b3261e;
  font-weight: 700;
}

.today {
  color: #925400;
  font-weight: 700;
}

@media (max-width: 992px) {
  .hero-topline {
    flex-direction: column;
    align-items: flex-start;
  }

  .board-header {
    flex-direction: column;
    align-items: stretch;
  }

  .task-actions {
    grid-template-columns: 1fr;
  }
}
</style>
