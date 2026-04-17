<template>
  <AppLayout>
    <section class="dashboard-page">
      <div v-if="errorMessage" class="alert alert-danger py-2 mb-4">
        {{ errorMessage }}
      </div>

      <div class="row g-2 mb-2 metrics-row">
        <div class="col-12 col-md-6 col-xl-3" v-for="metric in metrics" :key="metric.label">
          <DashboardMetricCard :metric="metric" />
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

          <div ref="boardScrollRef" class="board-scroll">
            <div class="board-grid" :style="boardGridStyle">
              <article
                v-for="column in displayColumns"
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
                  <DashboardTaskCard
                    v-for="task in column.tasks"
                    :key="task.id"
                    :can-change-status="canChangeStatus"
                    :column-id="column.id"
                    :due-label="dueLabel"
                    :is-dragging="dragState.taskId === task.id"
                    :is-updating="updatingTaskIds.includes(task.id)"
                    :show-author="activeBoardTab === 'all'"
                    :statuses="statuses"
                    :task="task"
                    @open="openTask"
                    @drag-start="handleDragStart"
                    @drag-end="handleDragEnd"
                    @status-select="handleStatusSelect"
                  />

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
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import DashboardMetricCard from '../components/dashboard/DashboardMetricCard.vue';
import DashboardTaskCard from '../components/dashboard/DashboardTaskCard.vue';
import AppLayout from '../layouts/AppLayout.vue';
import { fetchTaskStatuses } from '../api/lookups';
import { fetchDashboardTasks, updateTaskRequest } from '../api/tasks';
import { loadCurrentUser, useAuthState } from '../services/authState';
import { useTaskPresentation } from '../composables/useTaskPresentation';

const router = useRouter();
const { currentUser, userLoaded, isAdmin } = useAuthState();

const loading = ref(false);
const errorMessage = ref('');
const statuses = ref([]);
const tasks = ref([]);
const activeBoardTab = ref('mine');
const isCompactBoard = ref(false);
const boardScrollRef = ref(null);
let boardWheelTarget = null;
let pageScrollContainer = null;

const updatingTaskIds = ref([]);
const dragState = reactive({
  taskId: null,
  fromId: null,
  overId: null,
  suppressClickForTaskId: null,
});

const {
  formatShortDate,
  fullName,
  isDueToday,
  isOverdue,
  priorityLabel,
} = useTaskPresentation();

const resetDragState = () => {
  dragState.taskId = null;
  dragState.fromId = null;
  dragState.overId = null;
};

const boardGridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${Math.max(statuses.value.length, 1)}, minmax(300px, 1fr))`,
}));

const isDone = (task) => ['done', 'cancelled'].includes(task?.status?.code);

const dueLabel = (task) => {
  if (!task?.due_date) return 'Без срока';
  if (isDueToday(task)) return `Сегодня · ${formatShortDate(task.due_date)}`;
  if (isOverdue(task)) return `Просрочено · ${formatShortDate(task.due_date)}`;
  return formatShortDate(task.due_date);
};

const canChangeStatus = (task) => {
  if (!currentUser.value || !task) return false;
  const isCreator = currentUser.value.id === task.creator_id;
  const isPerformer = Array.isArray(task.performers) && task.performers.some((user) => user.id === currentUser.value.id);
  return isAdmin.value || isCreator || isPerformer;
};

const sortColumnTasks = (columnTasks) => {
  return [...columnTasks].sort((left, right) => {
    const leftDue = left.due_date ? new Date(left.due_date).getTime() : Number.MAX_SAFE_INTEGER;
    const rightDue = right.due_date ? new Date(right.due_date).getTime() : Number.MAX_SAFE_INTEGER;

    if (leftDue !== rightDue) {
      return leftDue - rightDue;
    }

    return right.id - left.id;
  });
};

const normalizeColumns = (sourceTasks) => {
  return statuses.value.map((status) => ({
    ...status,
    tasks: sortColumnTasks(sourceTasks.filter((task) => task.status_id === status.id)),
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

const displayColumns = computed(() => {
  if (!isCompactBoard.value) return activeColumns.value;

  const nonEmptyColumns = activeColumns.value.filter((column) => column.tasks.length > 0);
  return nonEmptyColumns.length ? nonEmptyColumns : activeColumns.value;
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

const loadAllTasks = async () => {
  tasks.value = await fetchDashboardTasks({
    sort: 'due_asc',
  });
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
    const response = await updateTaskRequest(task.id, { status_id: statusId });
    replaceTask(response);
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
  if (!dragState.taskId) return;

  dragState.overId = columnId;

  if (event?.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
};

const handleColumnDragLeave = (columnId) => {
  if (dragState.overId === columnId) {
    dragState.overId = null;
  }
};

const handleDragEnd = () => {
  if (!dragState.taskId) return;

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
};

const handleDrop = async (statusId) => {
  if (!dragState.taskId) return;

  const task = tasks.value.find((item) => item.id === dragState.taskId);
  resetDragState();

  if (!task) return;
  await updateTaskStatus(task, statusId);
};

const handleBoardWheel = (event) => {
  if (window.innerWidth <= 992) return;
  if (Math.abs(event.deltaY) < Math.abs(event.deltaX)) return;

  const scrollContainer = pageScrollContainer || boardScrollRef.value?.closest('.content-area');
  if (!scrollContainer) return;

  event.preventDefault();
  scrollContainer.scrollTop += event.deltaY;
};

const updateBoardMode = () => {
  isCompactBoard.value = window.innerWidth <= 992;
};

const attachBoardWheelForwarding = async () => {
  await nextTick();

  const container = boardScrollRef.value;
  if (!container || boardWheelTarget === container) return;

  pageScrollContainer = container.closest('.content-area');

  if (boardWheelTarget) {
    boardWheelTarget.removeEventListener('wheel', handleBoardWheel);
  }

  container.addEventListener('wheel', handleBoardWheel, { passive: false });
  boardWheelTarget = container;
};

const loadDashboard = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    if (!userLoaded.value) {
      await loadCurrentUser();
    }

    const [statusResponse] = await Promise.all([
      fetchTaskStatuses(),
      loadAllTasks(),
    ]);

    statuses.value = statusResponse || [];
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
  updateBoardMode();
  window.addEventListener('resize', updateBoardMode);
  await loadDashboard();
  await attachBoardWheelForwarding();
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateBoardMode);

  if (boardWheelTarget) {
    boardWheelTarget.removeEventListener('wheel', handleBoardWheel);
    boardWheelTarget = null;
  }

  pageScrollContainer = null;
});
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  min-height: 0;
  overflow: visible;
}

.board-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.board-tabs {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 8px;
}

.board-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  border-radius: 999px;
  padding: 10px 14px;
  background: var(--surface-1);
  color: var(--text-2);
  font-weight: 700;
}

.board-tab.active {
  background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
  color: var(--accent-contrast);
  box-shadow: var(--shadow-soft);
}

.board-tab-count {
  display: inline-flex;
  min-width: 26px;
  justify-content: center;
  padding: 2px 7px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  font-size: 0.78rem;
}

.board-scroll {
  overflow-x: auto;
  overflow-y: visible;
  padding-bottom: 6px;
}

.board-grid {
  display: grid;
  gap: 14px;
  align-items: start;
}

.board-column {
  min-width: 0;
  padding: 12px;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.column-title {
  font-size: 0.98rem;
  font-weight: 800;
  color: var(--text-1);
}

.column-subtitle {
  color: var(--text-muted);
  font-size: 0.78rem;
  margin-top: 2px;
}

.column-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 34px;
  padding: 0 10px;
  border-radius: 999px;
  background: var(--surface-2);
  font-weight: 800;
}

.column-body {
  display: grid;
  gap: 12px;
  min-height: 140px;
}

.stack-line {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 0.8rem;
}

.empty-column {
  display: grid;
  place-items: center;
  min-height: 120px;
  padding: 16px;
  border: 1px dashed var(--border-soft);
  border-radius: 16px;
  color: var(--text-muted);
  text-align: center;
}

.column-drop-active {
  outline: 2px solid color-mix(in srgb, var(--accent) 45%, transparent);
}

@media (max-width: 992px) {
  .board-scroll {
    overflow: visible;
  }

  .board-grid {
    grid-template-columns: 1fr !important;
  }

  .board-column {
    padding: 14px;
  }

  .column-header {
    margin-bottom: 10px;
  }

  .column-title {
    font-size: 1rem;
  }

  .column-subtitle {
    font-size: 0.82rem;
  }

  .column-count {
    min-width: 36px;
    height: 36px;
    font-size: 0.82rem;
  }

  .column-body {
    gap: 10px;
  }

  .empty-column {
    min-height: 88px;
  }
}

@media (max-width: 640px) {
  .board-tab {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
