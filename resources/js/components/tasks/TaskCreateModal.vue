<template>
  <div class="modal fade" :id="modalId" tabindex="-1" aria-hidden="true">
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
              <input v-model="form.title" type="text" class="form-control" />
            </div>

            <div class="col-12">
              <label class="form-label">Описание</label>
              <textarea v-model="form.description" rows="4" class="form-control"></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Приоритет</label>
              <select v-model="form.priority" class="form-select">
                <option value="low">Низкий</option>
                <option value="medium">Средний</option>
                <option value="high">Высокий</option>
                <option value="critical">Критический</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Статус</label>
              <select v-model="form.status_id" class="form-select">
                <option value="">Выберите статус</option>
                <option v-for="status in statuses" :key="status.id" :value="status.id">
                  {{ status.name }}
                </option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Срок</label>
              <input v-model="form.due_date" type="date" class="form-control" />
            </div>
          </div>

          <div class="mt-4">
            <PerformerFields
              :performers="form.performers"
              :users="users"
              @add="$emit('add-performer')"
              @remove="$emit('remove-performer', $event)"
            />
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Отмена
          </button>
          <button type="button" class="btn btn-theme" :disabled="creating" @click="$emit('submit')">
            {{ creating ? 'Создание...' : 'Создать задачу' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import PerformerFields from './PerformerFields.vue';

defineProps({
  createError: {
    type: String,
    default: '',
  },
  creating: {
    type: Boolean,
    default: false,
  },
  form: {
    type: Object,
    required: true,
  },
  modalId: {
    type: String,
    default: 'createTaskModal',
  },
  statuses: {
    type: Array,
    required: true,
  },
  users: {
    type: Array,
    required: true,
  },
});

defineEmits(['add-performer', 'remove-performer', 'submit']);
</script>
