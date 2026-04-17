import { api } from './client';

export async function fetchProfile() {
  const response = await api().get('/api/profile');
  return response.data;
}

export async function fetchUserProfile(userId) {
  const response = await api().get(`/api/profiles/${userId}`);
  return response.data;
}

export async function updateProfileRequest(payload) {
  const response = await api().put('/api/profile', payload);
  return response.data;
}
