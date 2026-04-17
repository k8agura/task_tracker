<template>
  <div
    class="task-card"
    :class="{
      'task-card-draggable': canChangeStatus(task),
      'task-card-ghost': isDragging,
    }"
    :draggable="canChangeStatus(task)"
    @click="$emit('open', task.id)"
    @dragstart="$emit('drag-start', task, columnId, $event)"
    @dragend="$emit('drag-end')"
  >
    <div class="task-card-top">
      <span class="task-id">#{{ task.id }}</span>
      <span class="priority-pill" :class="`priority-${task.priority || 'medium'}`">
        {{ priorityLabel(task.priority) }}
      </span>
    </div>

    <div class="task-title">{{ task.title }}</div>
    <div class="task-desc">{{ task.description || 'Без описания' }}</div>

    <div v-if="showAuthor && task.creator" class="task-stack compact-stack">
      <div class="stack-line">
        <span class="stack-label">Автор</span>
        <RouterLink
          class="person-link"
          :to="`/team/${task.creator.id}`"
          @click.stop
        >
          {{ fullName(task.creator) || '—' }}
        </RouterLink>
      </div>
    </div>

    <div class="performer-block">
      <div class="performer-block-label">Исполнители</div>

      <div v-if="task.performers?.length" class="performer-list" @click.stop>
        <RouterLink
          v-for="performer in visiblePerformers"
          :key="performer.id"
          class="performer-chip"
          :to="`/team/${performer.id}`"
          @click.stop
        >
          <span class="performer-chip-dot">{{ initials(performer) }}</span>
          <span class="performer-chip-name">{{ shortName(performer) }}</span>
        </RouterLink>

        <span v-if="hiddenPerformersCount > 0" class="performer-more">
          +{{ hiddenPerformersCount }}
        </span>
      </div>

      <div v-else class="performer-empty">
        Не назначены
      </div>
    </div>

    <div class="task-meta">
      <span class="task-meta-chip" :class="{ overdue: isOverdue(task), today: isDueToday(task) }">
        {{ dueLabel(task) }}
      </span>
    </div>

    <div class="task-actions" @click.stop>
      <select
        class="form-select form-select-sm"
        :value="String(task.status_id)"
        :disabled="!canChangeStatus(task) || isUpdating"
        @change="$emit('status-select', task, $event)"
      >
        <option v-for="status in statuses" :key="`status-${task.id}-${status.id}`" :value="String(status.id)">
          {{ status.name }}
        </option>
      </select>

      <button class="btn btn-sm btn-outline-secondary" @click="$emit('open', task.id)">
        Открыть
      </button>
    </div>

    <div v-if="isUpdating" class="task-inline-note">
      Сохраняем статус...
    </div>
    <div v-else-if="!canChangeStatus(task)" class="task-inline-note muted">
      Только просмотр
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTaskPresentation } from '../../composables/useTaskPresentation';

const props = defineProps({
  canChangeStatus: {
    type: Function,
    required: true,
  },
  columnId: {
    type: Number,
    required: true,
  },
  dueLabel: {
    type: Function,
    required: true,
  },
  isDragging: {
    type: Boolean,
    default: false,
  },
  isUpdating: {
    type: Boolean,
    default: false,
  },
  showAuthor: {
    type: Boolean,
    default: false,
  },
  statuses: {
    type: Array,
    required: true,
  },
  task: {
    type: Object,
    required: true,
  },
});

defineEmits(['drag-end', 'drag-start', 'open', 'status-select']);

const { fullName, isDueToday, isOverdue, priorityLabel } = useTaskPresentation();

const visiblePerformers = computed(() => (props.task.performers || []).slice(0, 2));
const hiddenPerformersCount = computed(() => Math.max((props.task.performers?.length || 0) - visiblePerformers.value.length, 0));

const initials = (user) => {
  return [user?.first_name?.[0], user?.last_name?.[0]].filter(Boolean).join('').toUpperCase() || 'U';
};

const shortName = (user) => {
  const first = user?.first_name || '';
  const last = user?.last_name || '';
  return [first, last].filter(Boolean).join(' ') || fullName(user) || 'Сотрудник';
};
</script>

<style scoped>
.task-card {
  padding: 14px;
  border-radius: 20px;
  background:
    radial-gradient(circle at top right, color-mix(in srgb, var(--accent-1) 18%, transparent), transparent 30%),
    linear-gradient(180deg, var(--surface-2) 0%, var(--surface-1) 100%);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-soft);
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-card);
  border-color: var(--border-strong);
}

.task-card-draggable {
  cursor: grab;
}

.task-card-ghost {
  opacity: 0.6;
}

.task-card-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.task-id {
  color: var(--text-muted);
  font-size: 0.78rem;
  font-weight: 700;
}

.priority-pill {
  padding: 0.3rem 0.55rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
}

.priority-low {
  background: rgba(108, 117, 125, 0.14);
  color: #58616b;
}

.priority-medium {
  background: rgba(13, 202, 240, 0.14);
  color: #0b7285;
}

.priority-high {
  background: rgba(255, 193, 7, 0.18);
  color: #8a5a00;
}

.priority-critical {
  background: rgba(220, 53, 69, 0.16);
  color: #9f1239;
}

.task-title {
  margin-top: 10px;
  font-weight: 800;
  color: var(--text-1);
  line-height: 1.35;
}

.task-desc {
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 0.86rem;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.task-stack {
  margin-top: 10px;
}

.stack-line {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 0.8rem;
}

.stack-label,
.performer-block-label {
  color: var(--text-muted);
  font-weight: 700;
}

.person-link {
  color: var(--accent-1);
  font-weight: 700;
  text-decoration: none;
}

.person-link:hover {
  text-decoration: underline;
}

.performer-block {
  margin-top: 12px;
}

.performer-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.performer-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0.38rem 0.65rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--accent-2) 14%, var(--surface-2));
  color: var(--text-1);
  text-decoration: none;
  transition: background-color 0.18s ease, transform 0.18s ease;
}

.performer-chip:hover {
  background: color-mix(in srgb, var(--accent-2) 22%, var(--surface-2));
  transform: translateY(-1px);
}

.performer-chip-dot {
  width: 24px;
  height: 24px;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: var(--surface-2);
  color: var(--text-1);
  font-size: 0.7rem;
  font-weight: 800;
}

.performer-chip-name {
  font-size: 0.78rem;
  font-weight: 700;
}

.performer-more {
  display: inline-flex;
  align-items: center;
  padding: 0.38rem 0.65rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--text-muted) 12%, var(--surface-2));
  color: var(--text-muted);
  font-size: 0.78rem;
  font-weight: 700;
}

.performer-empty {
  margin-top: 8px;
  color: var(--text-muted);
  font-size: 0.82rem;
}

.task-meta {
  display: flex;
  margin-top: 12px;
}

.task-meta-chip {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 0.32rem 0.6rem;
  border-radius: 999px;
  background: var(--surface-2);
  color: var(--text-2);
  font-size: 0.78rem;
  font-weight: 700;
}

.task-meta-chip.today {
  background: color-mix(in srgb, #ffc107 18%, var(--surface-2));
  color: color-mix(in srgb, #ffc107 72%, var(--text-1));
}

.task-meta-chip.overdue {
  background: color-mix(in srgb, #dc3545 16%, var(--surface-2));
  color: color-mix(in srgb, #dc3545 76%, var(--text-1));
}

.task-actions {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px;
  margin-top: 14px;
}

.task-inline-note {
  margin-top: 8px;
  font-size: 0.76rem;
  color: var(--accent);
  font-weight: 700;
}

.task-inline-note.muted {
  color: var(--text-muted);
}

@media (max-width: 640px) {
  .task-card {
    padding: 13px;
    border-radius: 18px;
  }

  .task-actions {
    grid-template-columns: 1fr;
  }

  .performer-chip {
    max-width: 100%;
  }

  .performer-chip-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}
</style>
