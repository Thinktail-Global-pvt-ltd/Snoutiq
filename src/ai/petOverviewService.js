const API_BASE_URL =
  String(import.meta.env.VITE_API_BASE_URL || "").trim() ||
  "https://snoutiq.com/backend/api";

const CACHE_PREFIX = "ai.petOverview";
const DEFAULT_CACHE_TTL_MS = 60 * 1000;

const getCacheKey = (petId) => `${CACHE_PREFIX}:${petId}`;

const readCachedOverview = (petId, maxAgeMs = DEFAULT_CACHE_TTL_MS) => {
  try {
    const raw = localStorage.getItem(getCacheKey(petId));
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!parsed || typeof parsed !== "object") return null;

    if ("savedAt" in parsed && "data" in parsed) {
      const age = Date.now() - Number(parsed.savedAt || 0);
      return age <= maxAgeMs ? parsed.data : null;
    }

    localStorage.removeItem(getCacheKey(petId));
    return null;
  } catch {
    return null;
  }
};

const writeCachedOverview = (petId, data) => {
  try {
    localStorage.setItem(
      getCacheKey(petId),
      JSON.stringify({ savedAt: Date.now(), data }),
    );
  } catch {
    // ignore storage failures
  }
};

export async function fetchPetOverview(petId, options = {}) {
  const normalizedPetId = String(petId ?? "").trim();
  const forceRefresh = Boolean(options?.forceRefresh);
  const maxAgeMs = Number(options?.maxAgeMs) > 0 ? Number(options.maxAgeMs) : DEFAULT_CACHE_TTL_MS;

  if (!normalizedPetId) {
    throw new Error("Pet ID nahi mila.");
  }

  if (!forceRefresh) {
    const cached = readCachedOverview(normalizedPetId, maxAgeMs);
    if (cached) {
      return cached;
    }
  }

  try {
    const response = await fetch(
      `${API_BASE_URL}/pets/${encodeURIComponent(normalizedPetId)}/overview`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
      },
    );

    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload?.success === false) {
      throw new Error(payload?.message || "Overview load nahi hua.");
    }

    const overview = payload?.data ?? null;
    if (!overview || typeof overview !== "object") {
      throw new Error("Overview data missing hai.");
    }

    writeCachedOverview(normalizedPetId, overview);
    return overview;
  } catch (error) {
    const cached = readCachedOverview(normalizedPetId, maxAgeMs);
    if (cached) {
      return cached;
    }
    throw error;
  }
}
