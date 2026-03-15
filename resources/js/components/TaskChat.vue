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
                  class="message-link"
                  :href="`/api/attachments/${attachment.id}/download`"
                  target="_blank"
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

        <div class="form-text mt-2">
          Enter — отправить, Shift+Enter — новая строка
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
  const response = await window.axios.get('/api/me');
  currentUser.value = response.data;
};

const loadMessages = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await window.axios.get(`/api/tasks/${props.taskId}/messages`);
    messages.value = response.data;
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
    const response = await window.axios.post(`/api/tasks/${props.taskId}/messages`, {
      message: newMessage.value,
    });

    messages.value.push(response.data);
    newMessage.value = '';
    await scrollToBottom();
  } catch (error) {
    errorMessage.value = 'Не удалось отправить сообщение';
  } finally {
    sending.value = false;
  }
};

onMounted(async () => {
  await loadCurrentUser();
  await loadMessages();

  window.Echo.private(`task.${props.taskId}`)
    .listen('.chat.message.sent', async (event) => {
      const exists = messages.value.some((item) => item.id === event.id);
      if (!exists) {
        messages.value.push(event);
        await scrollToBottom();
      }
    });
});

onBeforeUnmount(() => {
  window.Echo.leave(`private-task.${props.taskId}`);
});
</script>

<style scoped>
.chat-card {
  height: 520px;
  min-height: 520px;
  max-height: 520px;
  background: #ffffff;
  border-radius: 22px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.chat-header {
  background: linear-gradient(180deg, #f9ffff 0%, #f4fbff 100%);
  flex: 0 0 auto;
  min-height: 54px;
}

.chat-title {
  font-size: 0.98rem;
  font-weight: 700;
  line-height: 1.1;
  color: #23414b;
}

.chat-subtitle {
  font-size: 0.72rem;
  line-height: 1.1;
  color: #6b8b95;
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
    radial-gradient(circle at top left, rgba(104, 199, 183, 0.06), transparent 28%),
    radial-gradient(circle at bottom right, rgba(107, 150, 255, 0.07), transparent 30%),
    #f7fcfb;
}

.chat-footer {
  flex: 0 0 auto;
  background: #ffffff;
}

.chat-input {
  resize: none;
  border-radius: 14px 0 0 14px;
}

.message-bubble {
  max-width: min(78%, 560px);
  border-radius: 18px;
  padding: 12px 14px 10px;
  box-shadow: 0 8px 20px rgba(90, 130, 140, 0.08);
}

.message-own {
  background: linear-gradient(135deg, #67c7b7 0%, #6e98ff 100%);
  color: white;
  border-bottom-right-radius: 6px;
}

.message-other {
  background: white;
  color: #243943;
  border: 1px solid rgba(110, 150, 160, 0.12);
  border-bottom-left-radius: 6px;
}

.message-author {
  font-weight: 700;
  font-size: 0.92rem;
  margin-bottom: 6px;
  color: #2d5a63;
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
    height: 440px;
    min-height: 440px;
    max-height: 440px;
  }
}

@media (max-width: 992px) {
  .chat-card {
    height: 420px;
    min-height: 420px;
    max-height: 420px;
  }

  .message-bubble {
    max-width: 88%;
  }
}
</style>