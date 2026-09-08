const configuredUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1';
export const apiUrl = configuredUrl.replace(/\/+$/, '');
export const mediaUrl = (path) => !path ? '' : (/^https?:\/\//.test(path) || path.startsWith('/')) ? path : `${apiUrl.replace(/\/api\/v1$/, '')}/storage/${path.replace(/^\//, '')}`;
const tokenKey = 'educity_admin_token';
const userKey = 'educity_admin_user';
export const auth = { token: () => localStorage.getItem(tokenKey), user: () => { try { return JSON.parse(localStorage.getItem(userKey) || 'null'); } catch { return null; } }, set: ({ token, user }) => { localStorage.setItem(tokenKey, token); localStorage.setItem(userKey, JSON.stringify(user)); }, clear: () => { localStorage.removeItem(tokenKey); localStorage.removeItem(userKey); } };
export async function api(path, options = {}) {
  const headers = new Headers(options.headers || {}); headers.set('Accept', 'application/json');
  if (auth.token()) headers.set('Authorization', `Bearer ${auth.token()}`);
  if (options.body && !(options.body instanceof FormData) && !headers.has('Content-Type')) headers.set('Content-Type', 'application/json');
  const response = await fetch(`${apiUrl}${path.startsWith('/') ? path : `/${path}`}`, { ...options, headers });
  if (response.status === 204) return { success: true, data: null };
  const body = await response.json().catch(() => ({ message: 'Unexpected server response.' }));
  if (!response.ok) { if (response.status === 401) auth.clear(); const error = Object.assign(new Error(body.message || 'Request failed.'), { status: response.status, errors: body.errors || {} }); throw error; }
  return body;
}
export async function download(path, filename) { const headers = { Accept: 'text/csv' }; if (auth.token()) headers.Authorization = `Bearer ${auth.token()}`; const response = await fetch(`${apiUrl}${path}`, { headers }); if (!response.ok) throw new Error('Unable to export records.'); const url = URL.createObjectURL(await response.blob()); const link = document.createElement('a'); link.href = url; link.download = filename; link.click(); URL.revokeObjectURL(url); }
