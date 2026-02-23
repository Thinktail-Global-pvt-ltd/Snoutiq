const API_PATH = "/backend/api/exported_from_excell_doctors";
const DEFAULT_BACKEND_ORIGIN = "https://snoutiq.com";

const getSafeOrigin = () => {
  if (typeof window === "undefined") return DEFAULT_BACKEND_ORIGIN;
  const origin = window.location.origin;
  if (origin.includes("localhost") || origin.includes("127.0.0.1")) {
    return DEFAULT_BACKEND_ORIGIN;
  }
  return origin;
};

const BACKEND_BASE = `${getSafeOrigin()}/backend`;

const buildApiCandidates = () => {
  const origin = getSafeOrigin();
  return Array.from(
    new Set([
      `${origin}${API_PATH}`,
      `https://snoutiq.com${API_PATH}`,
      `https://www.snoutiq.com${API_PATH}`,
    ])
  );
};

const fetchJsonStrict = async (url, { timeoutMs = 15000 } = {}) => {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const res = await fetch(url, {
      method: "GET",
      signal: controller.signal,
      cache: "no-store",
      headers: { Accept: "application/json" },
    });

    const contentType = res.headers.get("content-type") || "";
    const text = await res.text();

    if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 140)}`);
    if (!contentType.includes("application/json")) {
      throw new Error(
        `Non-JSON response (${contentType || "unknown"}): ${text.slice(0, 140)}`
      );
    }

    return JSON.parse(text);
  } catch (e) {
    if (e?.name === "AbortError") {
      throw new Error("Request timed out. Please try again.");
    }
    throw e;
  } finally {
    clearTimeout(timer);
  }
};

export const loadVetsWithFallback = async () => {
  const candidates = buildApiCandidates();
  let lastErr = null;

  for (const url of candidates) {
    try {
      const json = await fetchJsonStrict(url);
      if (json?.success && Array.isArray(json?.data)) return json.data;
      lastErr = new Error("Invalid API response shape");
    } catch (e) {
      lastErr = e;
    }
  }

  throw lastErr || new Error("Network error while loading vets.");
};

const normalizeImageUrl = (value) => {
  if (!value) return "";
  const trimmed = String(value).trim();
  if (!trimmed) return "";

  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined") return "";

  if (lower.includes("https://snoutiq.com/https://snoutiq.com/")) {
    return trimmed.replace(
      "https://snoutiq.com/https://snoutiq.com/",
      "https://snoutiq.com/"
    );
  }

  if (
    lower.startsWith("http://") ||
    lower.startsWith("https://") ||
    lower.startsWith("data:")
  ) {
    return trimmed;
  }

  let cleaned = trimmed.replace(/^\/+/, "");
  if (cleaned.toLowerCase().startsWith("backend/")) {
    cleaned = cleaned.slice("backend/".length);
  }

  return `${BACKEND_BASE}/${cleaned}`;
};

const getDoctorImageSource = (doc) =>
  doc?.doctor_image_blob_url || doc?.doctor_image_url || doc?.doctor_image || "";

const parseListField = (value) => {
  if (!value) return [];
  if (Array.isArray(value)) {
    return value.map((x) => String(x).trim()).filter(Boolean);
  }

  if (typeof value === "string") {
    const trimmed = value.trim();
    if (!trimmed) return [];
    if (trimmed.startsWith("[") && trimmed.endsWith("]")) {
      try {
        const parsed = JSON.parse(trimmed);
        if (Array.isArray(parsed)) {
          return parsed.map((x) => String(x).trim()).filter(Boolean);
        }
      } catch {
        // ignore
      }
    }
    return trimmed
      .split(",")
      .map((x) => x.trim())
      .filter(Boolean);
  }

  return [String(value).trim()].filter(Boolean);
};

const normalizeText = (value) => {
  if (value === undefined || value === null) return "";
  const trimmed = String(value).trim();
  if (!trimmed) return "";
  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined" || lower === "[]") return "";
  return trimmed;
};

const buildSpecializationData = (value) => {
  const list = parseListField(value);
  let text = list.length ? list.join(", ") : "";

  if (!text) {
    const raw = String(value || "").trim();
    const lower = raw.toLowerCase();
    if (raw && lower !== "null" && lower !== "undefined" && lower !== "[]") {
      if (raw.startsWith("[") && raw.endsWith("]")) {
        text = raw.slice(1, -1).replace(/["']/g, "").trim();
      } else {
        text = raw;
      }
    }
  }

  return { list, text };
};

const buildDegreeData = (value) => {
  const list = parseListField(value);
  let text = list.length ? list.join(", ") : "";

  if (!text) {
    const raw = String(value || "").trim();
    const lower = raw.toLowerCase();
    if (raw && lower !== "null" && lower !== "undefined" && lower !== "[]") {
      if (raw.startsWith("[") && raw.endsWith("]")) {
        text = raw.slice(1, -1).replace(/["']/g, "").trim();
      } else {
        text = raw;
      }
    }
  }

  return { list, text };
};

const normalizeBreakTimes = (value) => {
  const list = parseListField(value);
  return list.filter((item) => {
    const cleaned = String(item).toLowerCase().replace(/[^a-z0-9]/g, "");
    if (!cleaned) return false;
    if (
      ["no", "none", "nil", "na", "n/a", "noany", "notavailable"].includes(
        cleaned
      )
    ) {
      return false;
    }
    if (cleaned.startsWith("no")) return false;
    return true;
  });
};

const normalizeSpecialties = (specializationText = "") => {
  const raw = parseListField(specializationText)
    .map((s) => String(s).toLowerCase())
    .filter(Boolean);

  const mapped = new Set();
  raw.forEach((t) => {
    if (t.includes("dog")) mapped.add("dog");
    if (t.includes("cat")) mapped.add("cat");
    if (
      t.includes("exotic") ||
      t.includes("bird") ||
      t.includes("rabbit") ||
      t.includes("turtle")
    ) {
      mapped.add("exotic");
    }
  });
  return Array.from(mapped);
};

const toOptionalNumber = (value) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
};

const toNumber = (v, fallback = 0) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : fallback;
};

const seedFromValue = (value) => {
  const str = String(value ?? "");
  let hash = 0;
  for (let i = 0; i < str.length; i += 1) {
    hash = (hash * 31 + str.charCodeAt(i)) % 1000000007;
  }
  return hash;
};

const seededRandom = (seed) => {
  const x = Math.sin(seed) * 10000;
  return x - Math.floor(x);
};

const getSeededNumber = (seed, min, max, decimals = 0) => {
  const raw = min + seededRandom(seed) * (max - min);
  const factor = 10 ** decimals;
  return Math.round(raw * factor) / factor;
};

export const buildVetsFromApi = (apiData = []) => {
  const list = [];

  apiData.forEach((clinic) => {
    const clinicName = normalizeText(clinic?.name);

    (clinic?.doctors || []).forEach((doc) => {
      const specializationData = buildSpecializationData(
        doc?.specialization_select_all_that_apply
      );
      const degreeData = buildDegreeData(doc?.degree);
      const breakTimes = normalizeBreakTimes(
        doc?.break_do_not_disturb_time_example_2_4_pm
      );

      const seedBase = seedFromValue(
        doc?.id ||
          doc?.doctor_email ||
          doc?.doctor_mobile ||
          doc?.doctor_name ||
          `${clinicName}-${list.length}`
      );

      const priceDay = toNumber(doc?.video_day_rate, 0);
      const priceNight = toNumber(doc?.video_night_rate, 0);

      // Hide demo or invalid profiles
      if (priceDay <= 2 || priceNight <= 2) return;

      list.push({
        id: doc?.id,
        clinicName,
        name: normalizeText(doc?.doctor_name) || "Vet",
        qualification: normalizeText(degreeData.text),
        degreeList: degreeData.list,
        experience: toOptionalNumber(doc?.years_of_experience),
        image: normalizeImageUrl(getDoctorImageSource(doc)),
        priceDay,
        priceNight,
        rating: getSeededNumber(seedBase + 11, 4.0, 4.9, 1),
        reviews: Math.round(getSeededNumber(seedBase + 23, 30, 220)),
        consultations: Math.round(getSeededNumber(seedBase + 37, 20, 180)),
        specialties: normalizeSpecialties(specializationData.list),
        specializationList: specializationData.list,
        specializationText: normalizeText(specializationData.text),
        responseDay: normalizeText(doc?.response_time_for_online_consults_day),
        responseNight: normalizeText(
          doc?.response_time_for_online_consults_night
        ),
        breakTimes,
        followUp: normalizeText(
          doc?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta
        ),
        bio: normalizeText(doc?.bio),
        raw: doc,
      });
    });
  });

  return list;
};

