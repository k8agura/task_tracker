import { api } from './client';

export async function fetchUsers(params = {}) {
  const response = await api().get('/api/users', { params });
  return response.data;
}

export async function fetchUser(userId) {
  const response = await api().get(`/api/users/${userId}`);
  return response.data;
}

export async function createUserRequest(payload) {
  const response = await api().post('/api/users', payload);
  return response.data;
}

export async function updateUserRequest(userId, payload) {
  const response = await api().put(`/api/users/${userId}`, payload);
  return response.data;
}

export async function deleteUserRequest(userId) {
  const response = await api().delete(`/api/users/${userId}`);
  return response.data;
}

