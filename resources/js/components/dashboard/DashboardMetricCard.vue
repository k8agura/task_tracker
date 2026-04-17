<template>
  <div class="metric-card card-soft p-2 h-100">
    <div class="metric-label">{{ metric.label }}</div>
    <div class="metric-value">{{ metric.value }}</div>
    <div class="metric-note">{{ metric.note }}</div>
  </div>
</template>

<script setup>
defineProps({
  metric: {
    type: Object,
    required: true,
  },
});
</script>

<style scoped>
.metric-card {
  background: linear-gradient(180deg, var(--surface-2) 0%, var(--surface-1) 100%);
  border-radius: 18px;
}

.metric-label {
  color: var(--text-muted);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.metric-value {
  font-size: clamp(1.35rem, 2vw, 1.9rem);
  font-weight: 800;
  color: var(--text-1);
  line-height: 1.1;
  margin-top: 6px;
}

.metric-note {
  color: var(--text-muted);
  font-size: 0.83rem;
  margin-top: 6px;
}
</style>
