/* BrigadaMedica — Cliente de la API REST.
   Mismo contrato (Bearer token, /api/v1, mensajes de error) que usará la app Android:
   el panel web no tiene ninguna vía de autenticación especial, habla con el backend
   exactamente igual que cualquier otro cliente externo. */

const API_BASE = '/api/v1';
const TOKEN_KEY = 'bs_token';
const USER_KEY = 'bs_user';

function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

function getUser() {
  const raw = localStorage.getItem(USER_KEY);
  try { return raw ? JSON.parse(raw) : null; } catch (_) { return null; }
}

function setSession(token, user) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

function hasPermission(nombre) {
  return !!getUser()?.permissions?.includes(nombre);
}

// Redirige a /login si no hay sesión guardada. Se llama al cargar cada página protegida.
function requireAuth() {
  if (!getToken()) {
    window.location.href = '/login';
    return false;
  }
  return true;
}

// Corta el acceso a una sección si el usuario no tiene el permiso, con el mismo
// mensaje que usaría el backend si igual se intentara la petición.
function requirePermission(nombre) {
  if (!hasPermission(nombre)) {
    showToast('No tienes permiso para realizar esta acción.', 'error');
    setTimeout(() => { window.location.href = '/dashboard'; }, 1200);
    return false;
  }
  return true;
}

async function apiRequest(method, path, body) {
  const headers = { Accept: 'application/json' };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  let response;
  try {
    response = await fetch(API_BASE + path, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch (_networkError) {
    return { ok: false, status: 0, message: 'No se pudo conectar con el servidor. Revisa tu conexión.' };
  }

  let payload = null;
  try { payload = await response.json(); } catch (_sinCuerpo) { /* ej. 204 No Content */ }

  if (response.status === 401) {
    clearSession();
    if (!window.location.pathname.startsWith('/login')) {
      window.location.href = '/login';
    }
  }

  if (!response.ok) {
    return {
      ok: false,
      status: response.status,
      message: payload?.message || 'Ocurrió un error inesperado.',
      errors: payload?.errors || null,
    };
  }

  return {
    ok: true,
    status: response.status,
    data: payload && Object.prototype.hasOwnProperty.call(payload, 'data') ? payload.data : payload,
    meta: payload?.meta || null,
    extra: payload, // por si hace falta un campo fuera de "data", ej. "advertencia" en Turnos
  };
}

const Api = {
  get: (path) => apiRequest('GET', path),
  post: (path, body) => apiRequest('POST', path, body),
  put: (path, body) => apiRequest('PUT', path, body),
  delete: (path) => apiRequest('DELETE', path),

  async login(login, password) {
    // "login" acepta correo electrónico o cédula ecuatoriana; el backend distingue cuál es.
    const resultado = await apiRequest('POST', '/login', { login, password });
    if (resultado.ok) {
      // /login no envuelve en "data": devuelve {user, token, token_type} directo.
      setSession(resultado.extra.token, resultado.extra.user);
    }
    return resultado;
  },

  async logout() {
    await apiRequest('POST', '/logout');
    clearSession();
    window.location.href = '/login';
  },
};
