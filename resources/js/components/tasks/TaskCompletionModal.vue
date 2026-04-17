<template>
  <div class="modal fade" :id="modalId" tabindex="-1" aria-hidden="true">
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
          <div v-if="error" class="alert alert-danger py-2">
            {{ error }}
          </div>

          <div class="mb-3">
            <label class="form-label">Итоговый статус</label>
            <select v-model="form.status_code" class="form-select">
              <option value="done">Завершена</option>
              <option value="cancelled">Отменена</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Отчёт по задаче</label>
            <textarea
              v-model="form.completion_report"
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
          <button type="button" class="btn btn-outline-secondary" @click="$emit('close')">
            Отмена
          </button>
          <button type="button" class="btn btn-success" :disabled="submitting" @click="$emit('submit')">
            {{ submitting ? 'Сохранение...' : 'Подтвердить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  error: {
    type: String,
    default: '',
  },
  form: {
    type: Object,
    required: true,
  },
  modalId: {
    type: String,
    default: 'completeTaskModal',
  },
  submitting: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['close', 'submit']);
</script>
