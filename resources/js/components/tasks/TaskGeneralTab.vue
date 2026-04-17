<template>
  <div class="row g-3 detail-tab-panel detail-tab-scroll">
    <div class="col-12 col-xl-7">
      <div class="card border-0 shadow-sm h-100 info-card">
        <div class="card-header bg-white py-3 info-card-header">
          <div>
            <div class="section-eyebrow">Основа</div>
            <h6 class="mb-0">Карточка задачи</h6>
          </div>
        </div>

        <div class="card-body">
          <div v-if="errorMessage" class="alert alert-danger py-2">
            {{ errorMessage }}
          </div>

          <div v-if="successMessage" class="alert alert-success py-2">
            {{ successMessage }}
          </div>

          <template v-if="canEdit">
            <div class="mb-3">
              <label class="form-label">Название</label>
              <input
                v-model="editForm.title"
                type="text"
                class="form-control"
                @blur="$emit('commit')"
              />
            </div>

            <div class="mb-3">
              <label class="form-label">Описание</label>
              <textarea
                v-model="editForm.description"
                rows="5"
                class="form-control"
                @blur="$emit('commit')"
              ></textarea>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Приоритет</label>
                <select v-model="editForm.priority" class="form-select" @change="$emit('commit')">
                  <option value="low">Низкий</option>
                  <option value="medium">Средний</option>
                  <option value="high">Высокий</option>
                  <option value="critical">Критический</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select v-model="editForm.status_id" class="form-select" @change="$emit('commit')">
                  <option v-for="status in statuses" :key="status.id" :value="status.id">
                    {{ status.name }}
                  </option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Срок</label>
                <input v-model="editForm.due_date" type="date" class="form-control" @change="$emit('commit')" />
              </div>
            </div>

            <div class="mt-4">
              <PerformerFields
                label="Исполнители"
                add-button-text="Добавить исполнителя"
                :add-disabled="availableUsers.length === 0"
                :performers="editForm.performers"
                :users="users"
                @add="$emit('add-performer')"
                @remove="$emit('remove-performer', $event)"
              />
            </div>
          </template>

          <template v-else>
            <div class="description-panel">
              <div class="section-label">Описание</div>
              <div class="description-copy">
                {{ task.description || 'Описание пока не добавлено' }}
              </div>
            </div>

            <div class="info-grid">
              <div class="info-tile">
                <div class="info-tile-label">Постановщик</div>
                <div class="info-tile-value">{{ fullName(task.creator) || '—' }}</div>
              </div>

              <div class="info-tile">
                <div class="info-tile-label">Подразделение</div>
                <div class="info-tile-value">{{ task.creator?.department?.name || '—' }}</div>
              </div>

              <div class="info-tile">
                <div class="info-tile-label">Статус</div>
                <div class="info-tile-value">
                  <span class="badge text-bg-primary">{{ task.status?.name || '—' }}</span>
                </div>
              </div>

              <div class="info-tile">
                <div class="info-tile-label">Приоритет</div>
                <div class="info-tile-value priority-value">
                  {{ priorityLabel(task.priority) }}
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div v-if="task.completion_report" class="completion-card card-soft mb-3">
        <div class="section-label">Итоговый отчёт</div>
        <div class="completion-copy">
          {{ task.completion_report }}
        </div>
      </div>

      <div v-if="task.completed_at" class="row g-3 mb-3">
        <div class="col-md-6">
          <div class="info-tile compact-tile">
            <div class="info-tile-label">Завершена</div>
            <div class="info-tile-value">{{ formatDate(task.completed_at) }}</div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="info-tile compact-tile">
            <div class="info-tile-label">Завершил</div>
            <div class="info-tile-value">{{ fullName(task.completed_by) || '—' }}</div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm performers-card">
        <div class="card-header bg-white py-3 info-card-header">
          <div>
            <div class="section-eyebrow">Команда</div>
            <h6 class="mb-0">Исполнители</h6>
          </div>
        </div>
        <div class="card-body py-3">
          <div v-if="!task.performers?.length" class="text-muted py-2">
            Исполнители не назначены
          </div>

          <div class="performer-list" v-else>
            <div
              v-for="performer in task.performers"
              :key="performer.id"
              class="performer-item"
            >
              <div class="performer-avatar">
                {{ initials(performer) }}
              </div>

              <div class="performer-main">
                <RouterLink class="performer-link fw-semibold" :to="`/team/${performer.id}`">
                  {{ fullName(performer) }}
                </RouterLink>
                <div class="small text-muted">
                  {{ performer.position || 'Без должности' }}
                </div>
              </div>

              <div class="performer-role">
                {{ roleLabel(performer.pivot?.role || 'executor') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import PerformerFields from './PerformerFields.vue';
import { useTaskPresentation } from '../../composables/useTaskPresentation';

defineProps({
  availableUsers: {
    type: Array,
    required: true,
  },
  editForm: {
    type: Object,
    required: true,
  },
  errorMessage: {
    type: String,
    default: '',
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  statuses: {
    type: Array,
    required: true,
  },
  successMessage: {
    type: String,
    default: '',
  },
  task: {
    type: Object,
    required: true,
  },
  users: {
    type: Array,
    required: true,
  },
});

defineEmits(['add-performer', 'commit', 'remove-performer']);

const { formatDate, fullName, priorityLabel } = useTaskPresentation();

const initials = (user) => {
  return [user?.first_name?.[0], user?.last_name?.[0]].filter(Boolean).join('').toUpperCase() || 'U';
};

const roleLabel = (role) => {
  const map = {
    executor: 'Исполнитель',
    observer: 'Наблюдатель',
    reviewer: 'Проверяющий',
  };

  return map[role] || role;
};
</script>

<style scoped>
.info-card,
.performers-card {
  border-radius: 22px;
  overflow: hidden;
}

.info-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-eyebrow {
  color: var(--text-muted);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 0.35rem;
}

.section-label {
  color: var(--text-muted);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 0.6rem;
}

.description-panel {
  padding: 1rem 1.05rem;
  border-radius: 18px;
  background:
    radial-gradient(circle at top right, color-mix(in srgb, var(--accent-1) 12%, transparent), transparent 32%),
    linear-gradient(180deg, var(--surface-1) 0%, var(--surface-2) 100%);
  border: 1px solid var(--border-soft);
}

.description-copy {
  color: var(--text-1);
  line-height: 1.65;
  white-space: pre-wrap;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
  margin-top: 1rem;
}

.info-tile {
  padding: 0.95rem 1rem;
  border-radius: 18px;
  background: var(--surface-1);
  border: 1px solid var(--border-soft);
}

.compact-tile {
  height: 100%;
}

.info-tile-label {
  color: var(--text-muted);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 0.45rem;
}

.info-tile-value {
  color: var(--text-1);
  font-weight: 700;
  line-height: 1.45;
}

.priority-value {
  color: color-mix(in srgb, var(--accent-1) 72%, var(--text-1));
}

.completion-card {
  padding: 1rem 1.05rem;
  border-radius: 20px;
}

.completion-copy {
  line-height: 1.65;
  white-space: pre-wrap;
}

.performer-list {
  display: grid;
  gap: 0.85rem;
}

.performer-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.85rem;
  align-items: center;
  padding: 0.9rem 0.95rem;
  border-radius: 18px;
  background: var(--surface-1);
  border: 1px solid var(--border-soft);
}

.performer-avatar {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: color-mix(in srgb, var(--accent-2) 16%, var(--surface-2));
  color: var(--text-1);
  font-weight: 800;
  font-size: 0.82rem;
}

.performer-main {
  min-width: 0;
}

.performer-link {
  color: var(--accent-1);
  text-decoration: none;
}

.performer-link:hover {
  text-decoration: underline;
}

.performer-role {
  padding: 0.35rem 0.6rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--text-muted) 12%, var(--surface-2));
  color: var(--text-2);
  font-size: 0.76rem;
  font-weight: 700;
  white-space: nowrap;
}

@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
  }

  .performer-item {
    grid-template-columns: auto 1fr;
  }

  .performer-role {
    grid-column: 1 / -1;
    width: fit-content;
  }
}
</style>
