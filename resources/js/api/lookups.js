import { api } from './client';

export async function fetchDepartments() {
  const response = await api().get('/api/departments');
  return response.data || [];
}

export async function fetchTaskStatuses() {
  const response = await api().get('/api/task-statuses');
  return response.data || [];
}

