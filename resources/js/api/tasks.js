import { api } from './client';

export async function fetchTasks(params = {}) {
  const response = await api().get('/api/tasks', { params });
  return response.data;
}

export async function fetchDashboardTasks(params = {}) {
  const response = await api().get('/api/dashboard/tasks', { params });
  return response.data;
}

export async function fetchTask(taskId) {
  const response = await api().get(`/api/tasks/${taskId}`);
  return response.data;
}

export async function createTaskRequest(payload) {
  const response = await api().post('/api/tasks', payload);
  return response.data;
}

export async function updateTaskRequest(taskId, payload) {
  const response = await api().put(`/api/tasks/${taskId}`, payload);
  return response.data;
}

export async function completeTaskRequest(taskId, payload) {
  const response = await api().post(`/api/tasks/${taskId}/complete`, payload);
  return response.data;
}

export async function fetchTaskMessages(taskId) {
  const response = await api().get(`/api/tasks/${taskId}/messages`);
  return response.data;
}

export async function sendTaskMessage(taskId, payload) {
  const response = await api().post(`/api/tasks/${taskId}/messages`, payload);
  return response.data;
}
