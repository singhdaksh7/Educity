const configuredUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1';
export const apiUrl = configuredUrl.replace(/\/+$/, '');
export const mediaUrl = (path) => !path ? '' : (/^https?:\/\//.test(path) || path.startsWith('/')) ? path : `${apiUrl.replace(/\/api\/v1$/, '')}/storage/${path.replace(/^\//, '')}`;

function createAuthStore(role) {
  const tokenKey = `educity_${role}_token`;
  const userKey = `educity_${role}_user`;
  return {
    token: () => localStorage.getItem(tokenKey),
    user: () => { try { return JSON.parse(localStorage.getItem(userKey) || 'null'); } catch { return null; } },
    set: ({ token, user }) => { localStorage.setItem(tokenKey, token); localStorage.setItem(userKey, JSON.stringify(user)); },
    clear: () => { localStorage.removeItem(tokenKey); localStorage.removeItem(userKey); },
  };
}

export const adminAuth = createAuthStore('admin');
export const studentAuth = createAuthStore('student');
// Kept as an alias so existing admin call sites (Admin.jsx) need no changes.
export const auth = adminAuth;

function roleFor(path) {
  return path.replace(/^\//, '').startsWith('student/') ? 'student' : 'admin';
}

export async function api(path, options = {}) {
  const store = roleFor(path) === 'student' ? studentAuth : adminAuth;
  const headers = new Headers(options.headers || {}); headers.set('Accept', 'application/json');
  if (store.token()) headers.set('Authorization', `Bearer ${store.token()}`);
  if (options.body && !(options.body instanceof FormData) && !headers.has('Content-Type')) headers.set('Content-Type', 'application/json');
  const response = await fetch(`${apiUrl}${path.startsWith('/') ? path : `/${path}`}`, { ...options, headers });
  if (response.status === 204) return { success: true, data: null };
  const body = await response.json().catch(() => ({ message: 'Unexpected server response.' }));
  if (!response.ok) { if (response.status === 401) store.clear(); const error = Object.assign(new Error(body.message || 'Request failed.'), { status: response.status, errors: body.errors || {} }); throw error; }
  return body;
}

export async function download(path, filename) { const headers = { Accept: 'text/csv' }; if (adminAuth.token()) headers.Authorization = `Bearer ${adminAuth.token()}`; const response = await fetch(`${apiUrl}${path}`, { headers }); if (!response.ok) throw new Error('Unable to export records.'); const url = URL.createObjectURL(await response.blob()); const link = document.createElement('a'); link.href = url; link.download = filename; link.click(); URL.revokeObjectURL(url); }
