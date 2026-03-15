<template>
  <div class="card-soft bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="mb-0">Файлы задачи</h5>
        <small class="text-muted">Загрузка и скачивание вложений</small>
      </div>
      <button class="btn btn-outline-secondary btn-sm" @click="loadAttachments">
        Обновить
      </button>
    </div>

    <div v-if="errorMessage" class="alert alert-danger py-2">
      {{ errorMessage }}
    </div>

    <form class="mb-4" @submit.prevent="uploadFile">
      <div class="row g-3 align-items-end">
        <div class="col-md-9">
          <label class="form-label">Выберите файл</label>
          <input ref="fileInput" type="file" class="form-control" @change="onFileChange" />
        </div>
        <div class="col-md-3">
          <button class="btn btn-theme w-100" type="submit" :disabled="uploading || !selectedFile">
            {{ uploading ? 'Загрузка...' : 'Загрузить' }}
          </button>
        </div>
      </div>
    </form>

    <div v-if="loading" class="text-muted">Загрузка файлов...</div>

    <div v-else-if="attachments.length === 0" class="text-muted">
      Вложений пока нет
    </div>

    <div v-else class="list-group list-group-flush">
      <div
        v-for="attachment in attachments"
        :key="attachment.id"
        class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3"
      >
        <div>
          <div class="fw-semibold">{{ attachment.original_name }}</div>
          <div class="small text-muted">
            {{ formatSize(attachment.file_size) }} · {{ formatDate(attachment.created_at) }}
          </div>
          <div class="small text-muted">
            Загрузил: {{ fullName(attachment.user) || 'Пользователь' }}
          </div>
        </div>

        <div class="d-flex gap-2">
          <a
            class="btn btn-sm btn-outline-primary"
            :href="`/api/attachments/${attachment.id}/download`"
            target="_blank"
          >
            Скачать
          </a>
          <button class="btn btn-sm btn-outline-danger" @click="removeAttachment(attachment.id)">
            Удалить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
  taskId: {
    type: Number,
    required: true,
  },
});

const attachments = ref([]);
const loading = ref(false);
const uploading = ref(false);
const errorMessage = ref('');
const selectedFile = ref(null);
const fileInput = ref(null);

const fullName = (user) => {
  if (!user) return '';
  return [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ');
};

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ru-RU');
};

const formatSize = (bytes) => {
  if (!bytes && bytes !== 0) return '—';
  if (bytes < 1024) return `${bytes} Б`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} КБ`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} МБ`;
};

const loadAttachments = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await window.axios.get(`/api/tasks/${props.taskId}/attachments`);
    attachments.value = response.data;
  } catch (error) {
    errorMessage.value = 'Не удалось загрузить вложения';
  } finally {
    loading.value = false;
  }
};

const onFileChange = (event) => {
  selectedFile.value = event.target.files?.[0] || null;
};

const uploadFile = async () => {
  if (!selectedFile.value) return;

  uploading.value = true;
  errorMessage.value = '';

  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('task_id', props.taskId);

    await window.axios.post('/api/attachments', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    selectedFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
    await loadAttachments();
  } catch (error) {
    errorMessage.value = 'Не удалось загрузить файл';
  } finally {
    uploading.value = false;
  }
};

const removeAttachment = async (id) => {
  try {
    await window.axios.delete(`/api/attachments/${id}`);
    await loadAttachments();
  } catch (error) {
    errorMessage.value = 'Не удалось удалить файл';
  }
};

onMounted(loadAttachments);
</script>