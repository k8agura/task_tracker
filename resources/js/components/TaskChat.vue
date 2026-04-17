<template>
  <div class="chat-card card-soft d-flex flex-column h-100">
    <div class="chat-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
      <div class="d-flex flex-column">
        <span class="chat-title">Чат</span>
        <span class="chat-subtitle">Онлайн-обсуждение</span>
      </div>

      <button class="btn btn-sm btn-outline-secondary chat-refresh-btn" @click="loadMessages">
        ↻
      </button>
    </div>

    <div
      ref="messagesContainer"
      class="chat-messages flex-grow-1 px-3 px-md-4 py-3"
    >
      <div v-if="loading" class="text-center text-muted py-4">
        Загрузка сообщений...
      </div>

      <div v-else-if="messages.length === 0" class="text-center text-muted py-4">
        Сообщений пока нет
      </div>

      <div
        v-for="item in messages"
        :key="item.id"
        class="d-flex mb-3"
        :class="isOwnMessage(item) ? 'justify-content-end' : 'justify-content-start'"
      >
        <div
          class="message-bubble"
          :class="isOwnMessage(item) ? 'message-own' : 'message-other'"
        >
          <div v-if="!isOwnMessage(item)" class="message-author">
            {{ userFullName(item.user) }}
          </div>

          <div class="message-text">
            {{ item.message }}
          </div>

          <div
            v-if="item.attachments && item.attachments.length"
            class="mt-2"
          >
            <div class="small opacity-75 mb-1">Вложения:</div>
            <ul class="list-unstyled mb-0">
              <li
                v-for="attachment in item.attachments"
                :key="attachment.id"
                class="mb-1"
              >
                <a
                  href="#"
                  class="message-link"
                  @click.prevent="downloadAttachment(attachment)"
                >
                  {{ attachment.original_name }}
                </a>
              </li>
            </ul>
          </div>

          <div class="message-meta mt-2">
            {{ formatDate(item.created_at) }}
          </div>
        </div>
      </div>
    </div>

    <div class="chat-footer border-top px-3 px-md-4 py-3 bg-white">
      <form @submit.prevent="sendMessage">
        <div v-if="errorMessage" class="alert alert-danger py-2 mb-3">
          {{ errorMessage }}
        </div>

        <div class="input-group">
          <textarea
            v-model="newMessage"
            rows="2"
            class="form-control chat-input"
            placeholder="Введите сообщение..."
            @keydown.enter.exact.prevent="sendMessage"
          ></textarea>

          <button
            type="submit"
            class="btn btn-theme px-4"
            :disabled="sending || !newMessage.trim()"
          >
            {{ sending ? '...' : 'Отправить' }}
          </button>
        </div>

        <div class="form-text chat-hint mt-2">
          Enter — отправить, Shift+Enter — новая строка
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { fetchCurrentUser } from '../api/me';
import { fetchTaskMessages, sendTaskMessage } from '../api/tasks';

const props = defineProps({
  taskId: {
    type: Number,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const messages = ref([]);
const newMessage = ref('');
const loading = ref(false);
const sending = ref(false);
const errorMessage = ref('');
const messagesContainer = ref(null);
const currentUser = ref(null);

const userFullName = (user) => {
  if (!user) return 'Неизвестный пользователь';
  return [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ');
};

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const isOwnMessage = (item) => {
  return currentUser.value && item.user && item.user.id === currentUser.value.id;
};

const scrollToBottom = async () => {
  await nextTick();

  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const loadCurrentUser = async () => {
  currentUser.value = await fetchCurrentUser();
};

const loadMessages = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    messages.value = await fetchTaskMessages(props.taskId);
    await scrollToBottom();
  } catch (error) {
    errorMessage.value = 'Не удалось загрузить сообщения';
  } finally {
    loading.value = false;
  }
};

const sendMessage = async () => {
  if (!newMessage.value.trim()) return;

  sending.value = true;
  errorMessage.value = '';

  try {
    const response = await sendTaskMessage(props.taskId, {
      message: newMessage.value,
    });

    messages.value.push(response);
    newMessage.value = '';
    await scrollToBottom();
  } catch (error) {
    errorMessage.value = 'Не удалось отправить сообщение';
  } finally {
    sending.value = false;
  }
};

const downloadAttachment = async (attachment) => {
  try {
    const response = await window.axios.get(`/api/attachments/${attachment.id}/download`, {
      responseType: 'blob',
    });

    const blobUrl = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = attachment.original_name || `attachment-${attachment.id}`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(blobUrl);
  } catch (error) {
    errorMessage.value = 'Не удалось скачать файл';
  }
};

onMounted(async () => {
  await loadCurrentUser();
  await loadMessages();

  if (window.Echo) {
    window.Echo.private(`task.${props.taskId}`)
      .listen('.chat.message.sent', async (event) => {
        const exists = messages.value.some((item) => item.id === event.id);
        if (!exists) {
          messages.value.push(event);
          await scrollToBottom();
        }
      });
  }
});

onBeforeUnmount(() => {
  if (window.Echo) {
    window.Echo.leave(`private-task.${props.taskId}`);
  }
});
</script>

<style scoped>
.chat-card {
  flex: 1 1 auto;
  width: 100%;
  max-width: 100%;
  height: min(820px, calc(100dvh - 220px));
  min-height: clamp(460px, 62dvh, 620px);
  max-height: calc(100dvh - 220px);
  background: var(--surface-2);
  border-radius: 22px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.chat-header {
  background: linear-gradient(180deg, var(--surface-2) 0%, var(--surface-1) 100%);
  flex: 0 0 auto;
  min-height: 54px;
}

.chat-title {
  font-size: 0.98rem;
  font-weight: 700;
  line-height: 1.1;
  color: var(--text-1);
}

.chat-subtitle {
  font-size: 0.72rem;
  line-height: 1.1;
  color: var(--text-muted);
  margin-top: 2px;
}

.chat-refresh-btn {
  width: 34px;
  height: 34px;
  padding: 0;
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 1rem;
  line-height: 1;
}

.chat-messages {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  background:
    radial-gradient(circle at top left, rgba(104, 199, 183, 0.08), transparent 28%),
    radial-gradient(circle at bottom right, rgba(107, 150, 255, 0.09), transparent 30%),
    var(--surface-1);
}

.chat-footer {
  flex: 0 0 auto;
  background: var(--surface-2);
  border-color: var(--border-soft) !important;
  overflow: visible;
}

.chat-input {
  resize: none;
  border-radius: 14px 0 0 14px;
}

.chat-hint {
  display: block;
  color: var(--text-muted) !important;
  font-size: 0.78rem;
  line-height: 1.35;
  opacity: 1;
}

.message-bubble {
  max-width: min(78%, 560px);
  border-radius: 18px;
  padding: 12px 14px 10px;
  box-shadow: var(--shadow-soft);
}

.message-own {
  background: linear-gradient(135deg, #67c7b7 0%, #6e98ff 100%);
  color: white;
  border-bottom-right-radius: 6px;
}

.message-other {
  background: var(--surface-2);
  color: var(--text-1);
  border: 1px solid var(--border-soft);
  border-bottom-left-radius: 6px;
}

.message-author {
  font-weight: 700;
  font-size: 0.92rem;
  margin-bottom: 6px;
  color: var(--text-2);
}

.message-text {
  white-space: pre-wrap;
  word-break: break-word;
}

.message-meta {
  font-size: 0.75rem;
  opacity: 0.75;
  text-align: right;
}

.message-link {
  color: inherit;
  text-decoration: underline;
}

@media (max-width: 1199px) {
  .chat-card {
    height: min(720px, calc(100dvh - 240px));
    min-height: clamp(440px, 58dvh, 560px);
    max-height: calc(100dvh - 240px);
  }
}

@media (max-width: 992px) {
  .chat-card {
    height: min(680px, calc(100dvh - 260px));
    min-height: clamp(420px, 56dvh, 520px);
    max-height: calc(100dvh - 260px);
  }

  .message-bubble {
    max-width: 88%;
  }
}

@media (max-width: 640px) {
  .chat-card {
    height: min(620px, calc(100dvh - 230px));
    min-height: clamp(400px, 54dvh, 500px);
    max-height: calc(100dvh - 230px);
    border-radius: 18px;
  }
}
</style>
