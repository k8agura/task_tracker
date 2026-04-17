export function api() {
  return window.axios;
}

export function setAuthToken(token) {
  if (token) {
    api().defaults.headers.common.Authorization = `Bearer ${token}`;
    return;
  }

  delete api().defaults.headers.common.Authorization;
}

