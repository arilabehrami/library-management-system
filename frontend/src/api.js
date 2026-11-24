const TOKEN_KEY = 'lms_token';
export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const setToken = (token) => localStorage.setItem(TOKEN_KEY, token);
export function clearToken() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem('lms_user');
}
export async function api(path, { method = 'GET', body } = {}) {
  const headers = { 'Content-Type': 'application/json' };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  const res = await fetch(`/api${path}`, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 401 && path !== '/auth/login') {
    clearToken();
    window.location.assign('/login?expired=1');
  }
  if (!res.ok) {
    const err = new Error(data.error || `Gabim ${res.status}`);
    err.status = res.status;
    throw err;
  }
  return data;
}
