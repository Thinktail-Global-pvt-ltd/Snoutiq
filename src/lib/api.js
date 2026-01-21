// Hardcoded API base to match backend dev pages (/backend prefix)
const API_BASE = '/backend';

const jsonHeaders = {
  Accept: 'application/json',
  'Content-Type': 'application/json',
};

async function handleResponse(res) {
  let data = null;
  try {
    data = await res.json();
  } catch {
    data = null;
  }
  if (!res.ok) {
    const message = (data && (data.message || data.error)) || `HTTP ${res.status}`;
    throw new Error(message);
  }
  return data ?? {};
}

export async function apiPost(path, body = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: 'POST',
    headers: jsonHeaders,
    body: JSON.stringify(body),
  });
  return handleResponse(res);
}

export function apiBaseUrl() {
  return API_BASE;
}

export default {
  post: apiPost,
  baseUrl: apiBaseUrl,
};
