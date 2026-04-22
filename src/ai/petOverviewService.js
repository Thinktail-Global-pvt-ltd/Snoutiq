const API_BASE_URL =
  String(import.meta.env.VITE_API_BASE_URL || "").trim() ||
  "https://snoutiq.com/backend/api";

const CACHE_PREFIX = "ai.petOverview";

const getCacheKey = (petId) => `${CACHE_PREFIX}:${petId}`;

const readCachedOverview = (petId) => {
  try {
    const raw = localStorage.getItem(getCacheKey(petId));
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === "object" ? parsed : null;
  } catch {
    return null;
  }
};

const writeCachedOverview = (petId, data) => {
  try {
    localStorage.setItem(getCacheKey(petId), JSON.stringify(data));
  } catch {
    // ignore storage failures
  }
};

export async function fetchPetOverview(petId, options = {}) {
  const normalizedPetId = String(petId ?? "").trim();
  const forceRefresh = Boolean(options?.forceRefresh);

  if (!normalizedPetId) {
    throw new Error("Pet ID nahi mila.");
  }

  if (!forceRefresh) {
    const cached = readCachedOverview(normalizedPetId);
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
    const cached = readCachedOverview(normalizedPetId);
    if (cached) {
      return cached;
    }
    throw error;
  }
}
