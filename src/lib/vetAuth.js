const VET_AUTH_KEY = "snoutiq_vet_auth";
const COOKIE_MAX_AGE_DAYS = 30;

const safeStorageList = () => {
  if (typeof window === "undefined") return [];
  const storages = [];
  try {
    if (window.localStorage) storages.push(window.localStorage);
  } catch {
    // ignore blocked storage
  }
  try {
    if (window.sessionStorage) storages.push(window.sessionStorage);
  } catch {
    // ignore blocked storage
  }
  return storages;
};

const readCookie = (key) => {
  if (typeof document === "undefined") return "";
  const match = document.cookie
    .split(";")
    .map((chunk) => chunk.trim())
    .find((chunk) => chunk.startsWith(`${key}=`));
  if (!match) return "";
  return decodeURIComponent(match.split("=").slice(1).join("="));
};

const writeCookie = (key, value, maxAgeDays = COOKIE_MAX_AGE_DAYS) => {
  if (typeof document === "undefined") return;
  const maxAge = Math.max(0, Math.floor(maxAgeDays * 24 * 60 * 60));
  document.cookie = `${key}=${encodeURIComponent(
    value
  )}; max-age=${maxAge}; path=/; samesite=lax`;
};

const clearCookie = (key) => {
  if (typeof document === "undefined") return;
  document.cookie = `${key}=; max-age=0; path=/; samesite=lax`;
};

export const loadVetAuth = () => {
  for (const storage of safeStorageList()) {
    try {
      const raw = storage.getItem(VET_AUTH_KEY);
      if (raw) return JSON.parse(raw);
    } catch {
      // ignore blocked storage
    }
  }

  try {
    const cookieValue = readCookie(VET_AUTH_KEY);
    return cookieValue ? JSON.parse(cookieValue) : null;
  } catch {
    return null;
  }
};

export const saveVetAuth = (payload) => {
  const serialized = JSON.stringify(payload);
  for (const storage of safeStorageList()) {
    try {
      storage.setItem(VET_AUTH_KEY, serialized);
    } catch {
      // ignore blocked storage
    }
  }
  writeCookie(VET_AUTH_KEY, serialized);
};

export const clearVetAuth = () => {
  for (const storage of safeStorageList()) {
    try {
      storage.removeItem(VET_AUTH_KEY);
    } catch {
      // ignore blocked storage
    }
  }
  clearCookie(VET_AUTH_KEY);
};

export const vetAuthKey = VET_AUTH_KEY;
