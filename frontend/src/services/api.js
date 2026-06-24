const API_BASE = process.env.REACT_APP_API_URL || '';

async function request(path, options = {}) {
  const token = localStorage.getItem('bbbms_token');
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  });

  let data = null;
  const contentType = response.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    data = await response.json();
  } else {
    const text = await response.text();
    data = text ? { error: text } : null;
  }

  if (!response.ok) {
    const error = new Error(data?.error || 'Request failed');
    error.response = { status: response.status, data };
    throw error;
  }

  return { data };
}

export const healthCheck = () => request('/api/health');

export const login = (email, password) =>
  request('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

export const register = (payload) =>
  request('/api/auth/register', {
    method: 'POST',
    body: JSON.stringify(payload),
  });

export const getInventory = () => request('/api/inventory');

export const addInventory = (payload) =>
  request('/api/inventory', {
    method: 'POST',
    body: JSON.stringify(payload),
  });

export const traceUnit = (unitId) => request(`/api/inventory/${encodeURIComponent(unitId)}/trace`);

export const getRequests = () => request('/api/requests');

export const createRequest = (payload) =>
  request('/api/requests', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
