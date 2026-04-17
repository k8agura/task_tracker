<template>
  <div class="card border-0 shadow-sm history-panel">
    <div class="card-header bg-white py-2">
      <h6 class="mb-0">История изменений</h6>
    </div>
    <div class="card-body history-scroll py-2">
      <div v-if="!histories?.length" class="text-muted">
        История изменений отсутствует
      </div>

      <div
        v-for="item in histories"
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
</template>

<script setup>
import { useTaskPresentation } from '../../composables/useTaskPresentation';

defineProps({
  histories: {
    type: Array,
    default: () => [],
  },
});

const { formatDate, historyLabel } = useTaskPresentation();
</script>

<style scoped>
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
</style>
