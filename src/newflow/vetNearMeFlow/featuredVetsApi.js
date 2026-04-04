const API_PATH = "/backend/api/exported_from_excell_doctors";
const DEFAULT_BACKEND_ORIGIN = "https://snoutiq.com";

const normalizeText = (value, fallback = "") => {
  if (value === undefined || value === null) return fallback;

  const trimmed = String(value).trim();
  if (!trimmed) return fallback;

  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined" || lower === "[]") {
    return fallback;
  }

  return trimmed;
};

const parseListField = (value) => {
  if (!value) return [];

  if (Array.isArray(value)) {
    return value.map((item) => normalizeText(item)).filter(Boolean);
  }

  const text = normalizeText(value);
  if (!text) return [];

  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) {
        return parsed.map((item) => normalizeText(item)).filter(Boolean);
      }
    } catch {
      // Ignore malformed JSON-like strings and fall back to comma parsing.
    }
  }

  return text
    .split(",")
    .map((item) => normalizeText(item))
    .filter(Boolean);
};

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
      `${DEFAULT_BACKEND_ORIGIN}${API_PATH}`,
      `https://www.snoutiq.com${API_PATH}`,
    ])
  );
};

const fetchJsonStrict = async (url, { timeoutMs = 15000 } = {}) => {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const response = await fetch(url, {
      method: "GET",
      signal: controller.signal,
      cache: "no-store",
      headers: { Accept: "application/json" },
    });

    const contentType = response.headers.get("content-type") || "";
    const text = await response.text();

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${text.slice(0, 140)}`);
    }

    if (!contentType.includes("application/json")) {
      throw new Error(
        `Non-JSON response (${contentType || "unknown"}): ${text.slice(0, 140)}`
      );
    }

    return JSON.parse(text);
  } catch (error) {
    if (error?.name === "AbortError") {
      throw new Error("Request timed out while loading vets.");
    }

    throw error;
  } finally {
    clearTimeout(timer);
  }
};

const normalizeImageUrl = (value) => {
  const text = normalizeText(value);
  if (!text) return "";

  const lower = text.toLowerCase();

  if (lower.includes("https://snoutiq.com/https://snoutiq.com/")) {
    return text.replace(
      "https://snoutiq.com/https://snoutiq.com/",
      "https://snoutiq.com/"
    );
  }

  if (
    lower.startsWith("http://") ||
    lower.startsWith("https://") ||
    lower.startsWith("data:")
  ) {
    return text;
  }

  let cleaned = text.replace(/^\/+/, "");

  if (cleaned.toLowerCase().startsWith("backend/")) {
    cleaned = cleaned.slice("backend/".length);
  }

  return `${BACKEND_BASE}/${cleaned}`;
};

const getDoctorImageSource = (doctor) =>
  doctor?.doctor_image_blob_url || doctor?.doctor_image_url || doctor?.doctor_image || "";

const ensureDoctorName = (value) => {
  const text = normalizeText(value, "Vet").replace(/\s+/g, " ");
  if (!text) return "Dr. Vet";

  return /^dr\.?\s/i.test(text)
    ? text.replace(/^dr\.?\s*/i, "Dr. ")
    : `Dr. ${text}`;
};

const getInitials = (name = "") => {
  const parts = String(name)
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) return "V";
  if (parts.length === 1) return parts[0][0]?.toUpperCase() || "V";

  return `${parts[0][0] || ""}${parts[1][0] || ""}`.toUpperCase();
};

const normalizeKey = (value) =>
  normalizeText(value)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, " ")
    .trim();

const dedupeList = (list = []) => {
  const seen = new Set();

  return list.filter((item) => {
    const key = normalizeKey(item);
    if (!key || seen.has(key)) return false;
    seen.add(key);
    return true;
  });
};

const toTitleCase = (value) =>
  normalizeText(value)
    .toLowerCase()
    .replace(/\b[a-z]/g, (letter) => letter.toUpperCase());

const isPetTypeSpecialization = (value) => {
  const key = normalizeKey(value);

  return (
    key.includes("dog") ||
    key.includes("cat") ||
    key.includes("exotic") ||
    key.includes("livestock") ||
    key.includes("avian") ||
    key.includes("bird") ||
    key.includes("rabbit") ||
    key.includes("turtle")
  );
};

const isGenericSpecialization = (value) => {
  const key = normalizeKey(value);

  return (
    key === "general practice" ||
    key === "general medicine" ||
    key === "medicine"
  );
};

const buildPetTypeTag = (specializations = []) => {
  const keys = specializations.map((item) => normalizeKey(item));
  const hasDogs = keys.some((item) => item.includes("dog"));
  const hasCats = keys.some((item) => item.includes("cat"));
  const hasExotic = keys.some((item) => item.includes("exotic"));
  const hasLivestock = keys.some((item) => item.includes("livestock"));

  if (hasDogs && hasCats) return "Dogs & cats";
  if (hasDogs) return "Dogs";
  if (hasCats) return "Cats";
  if (hasExotic) return "Exotic pets";
  if (hasLivestock) return "Livestock";

  return "";
};

const buildExperienceText = (value) => {
  const years = Number(value);

  if (!Number.isFinite(years) || years <= 0) return "";

  return `${Number.isInteger(years) ? years : years.toFixed(1)} yrs`;
};

const buildPrimaryDegree = (value) => {
  const degrees = parseListField(value);
  if (degrees.length) return normalizeText(degrees[0]);

  const raw = normalizeText(value);
  if (!raw) return "";

  return normalizeText(raw.split(",")[0]);
};

const buildResponseTime = (doctor) => {
  const value =
    normalizeText(doctor?.response_time_for_online_consults_day) ||
    normalizeText(doctor?.response_time_for_online_consults_night);

  if (!value) return "";

  return value
    .replace(/\s+to\s+/gi, "-")
    .replace(/\s*mins?\b/gi, " mins")
    .replace(/\s+/g, " ")
    .trim();
};

const hasFreeFollowUp = (value) => normalizeKey(value).includes("yes");

const buildDoctorTags = (doctor) => {
  const specializations = dedupeList(
    parseListField(doctor?.specialization_select_all_that_apply).map((item) =>
      toTitleCase(item.replace(/\s*\/\s*/g, " / "))
    )
  );

  const focusedTags = specializations.filter(
    (item) => !isPetTypeSpecialization(item) && !isGenericSpecialization(item)
  );

  const tags = [];
  const petTypeTag = buildPetTypeTag(specializations);
  const responseTime = buildResponseTime(doctor);

  if (petTypeTag) tags.push(petTypeTag);
  focusedTags.slice(0, 2).forEach((tag) => tags.push(tag));

  if (
    tags.length < 3 &&
    hasFreeFollowUp(
      doctor?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta
    )
  ) {
    tags.push("Free follow-up");
  }

  if (tags.length < 3 && responseTime) {
    tags.push(responseTime);
  }

  if (!tags.length) tags.push("General practice");

  return dedupeList(tags).slice(0, 3);
};

const normalizeDoctorsPayload = (data = []) => {
  if (!Array.isArray(data)) return [];

  return data.flatMap((item) => {
    if (Array.isArray(item?.doctors)) return item.doctors;
    return item && typeof item === "object" ? [item] : [];
  });
};

const buildFeaturedVetCard = (doctor) => {
  const name = ensureDoctorName(doctor?.doctor_name);
  const degree = buildPrimaryDegree(doctor?.degree);
  const experience = buildExperienceText(doctor?.years_of_experience);
  const responseTime = buildResponseTime(doctor);
  const languages = parseListField(doctor?.languages_spoken).map((item) =>
    toTitleCase(item)
  );
  const followUp = hasFreeFollowUp(
    doctor?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta
  );

  return {
    id: doctor?.id || name,
    initials: getInitials(name.replace(/^Dr\.\s*/i, "")),
    image: normalizeImageUrl(getDoctorImageSource(doctor)),
    name,
    credentials:
      [degree, experience ? `${experience} experience` : ""]
        .filter(Boolean)
        .join(" · ") || "Experienced veterinarian",
    tags: buildDoctorTags(doctor),
    statLine1: responseTime || experience || "Available",
    statLabel1: responseTime ? "response" : experience ? "experience" : "status",
    statLine2: followUp ? "Free" : languages[0] || "Available",
    statLabel2: followUp ? "follow-up" : languages[0] ? "spoken" : "support",
  };
};

export const loadFeaturedVetsFromApi = async () => {
  const candidates = buildApiCandidates();
  let lastError = null;

  for (const url of candidates) {
    try {
      const json = await fetchJsonStrict(url);
      const doctors = normalizeDoctorsPayload(json?.data)
        .filter((doctor) => {
          const role = normalizeKey(doctor?.staff_role);
          const status = normalizeKey(doctor?.doctor_status);

          if (role && role !== "doctor") return false;
          if (status && status !== "available") return false;

          return Boolean(normalizeText(doctor?.doctor_name));
        })
        .map((doctor) => buildFeaturedVetCard(doctor));

      if (json?.success && doctors.length) {
        return doctors.slice(0, 8);
      }

      lastError = new Error("Invalid featured vet response shape");
    } catch (error) {
      lastError = error;
    }
  }

  throw lastError || new Error("Unable to load featured vets.");
};
