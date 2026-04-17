<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <label class="form-label mb-0">{{ label }}</label>
      <button
        type="button"
        class="btn btn-sm btn-outline-primary"
        :disabled="addDisabled"
        @click="$emit('add')"
      >
        {{ addButtonText }}
      </button>
    </div>

    <div v-if="performers.length === 0" class="text-muted small">
      {{ emptyText }}
    </div>

    <div
      v-for="(performer, index) in performers"
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
          @click="$emit('remove', index)"
        >
          Убрать
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useTaskPresentation } from '../../composables/useTaskPresentation';

defineProps({
  addButtonText: {
    type: String,
    default: 'Добавить',
  },
  addDisabled: {
    type: Boolean,
    default: false,
  },
  emptyText: {
    type: String,
    default: 'Исполнители не выбраны',
  },
  label: {
    type: String,
    default: 'Исполнители',
  },
  performers: {
    type: Array,
    required: true,
  },
  users: {
    type: Array,
    required: true,
  },
});

defineEmits(['add', 'remove']);

const { fullName } = useTaskPresentation();
</script>
