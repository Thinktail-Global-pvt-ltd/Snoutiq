export const ASK_PROFILE_KEY = "snoutiq-ask-profile-v1";

export const DEFAULT_ASK_PROFILE = Object.freeze({
  ownerName: "",
  phone: "",
  email: "",
  petName: "",
  petType: "",
  breed: "",
  dob: "",
  location: "",
  lat: "",
  long: "",
  lastProblemText: "",
  userId: "",
  petId: "",
  gender: "",
  weightKg: "",
  lastDaysEnergy: "",
  lastDaysAppetite: "",
  mood: "",
  vaccinatedYesNo: "",
  dewormingYesNo: "",
  isNeutered: "",
});

const normalizePhoneInput = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  return digits.length > 10 ? digits.slice(-10) : digits;
};

const parseCoordinate = (value) => {
  if (value === undefined || value === null) return null;
  if (typeof value === "string" && !value.trim()) return null;
  const numberValue = Number(value);
  return Number.isFinite(numberValue) ? Number(numberValue.toFixed(6)) : null;
};

const normalizeIdentifier = (value) => {
  if (value === undefined || value === null) return "";
  return String(value).trim();
};

export const sanitizeAskProfile = (value) => {
  const raw = value && typeof value === "object" ? value : {};
  return {
    ownerName: String(raw.ownerName || "").trim(),
    phone: normalizePhoneInput(raw.phone),
    email: String(raw.email || raw.patientEmail || "").trim().toLowerCase(),
    petName: String(raw.petName || "").trim(),
    petType: String(raw.petType || raw.species || "").trim().toLowerCase(),
    breed: String(raw.breed || "").trim(),
    dob: String(raw.dob || "").trim(),
    location: String(raw.location ?? DEFAULT_ASK_PROFILE.location).trim(),
    lat: parseCoordinate(raw.lat ?? raw.latitude) ?? "",
    long: parseCoordinate(raw.long ?? raw.longitude) ?? "",
    lastProblemText: String(raw.lastProblemText || "").trim(),
    userId: normalizeIdentifier(raw.userId ?? raw.user_id),
    petId: normalizeIdentifier(raw.petId ?? raw.pet_id),
    gender: String(raw.gender || raw.sex || "").trim().toLowerCase(),
    weightKg: String(raw.weightKg ?? raw.weight ?? "").trim(),
    lastDaysEnergy: String(raw.lastDaysEnergy || raw.energy || "").trim(),
    lastDaysAppetite: String(raw.lastDaysAppetite || raw.appetite || "").trim(),
    mood: String(raw.mood || "").trim(),
    vaccinatedYesNo: String(
      raw.vaccinatedYesNo ?? raw.vaccenated_yes_no ?? raw.vaccinated_yes_no ?? "",
    ).trim(),
    dewormingYesNo: String(raw.dewormingYesNo ?? raw.deworming_yes_no ?? "").trim(),
    isNeutered: String(raw.isNeutered ?? raw.is_neutered ?? "").trim(),
  };
};

export const getAskProfile = () => {
  if (typeof window === "undefined") return { ...DEFAULT_ASK_PROFILE };
  try {
    const raw = window.localStorage.getItem(ASK_PROFILE_KEY);
    return sanitizeAskProfile(raw ? JSON.parse(raw) : null);
  } catch {
    return { ...DEFAULT_ASK_PROFILE };
  }
};

/**
 * Save profile only after intake submit succeeds validation.
 * Merges with the existing profile so partial updates do not wipe stored data.
 */
export const saveAskProfile = (incoming) => {
  if (typeof window === "undefined") return;
  const existing = getAskProfile();
  const next = sanitizeAskProfile({
    ...existing,
    ...incoming,
  });
  try {
    window.localStorage.setItem(ASK_PROFILE_KEY, JSON.stringify(next));
  } catch {
    // Ignore quota failures.
  }
};

export const hasAskProfile = () => {
  const profile = getAskProfile();
  return Boolean(profile.ownerName || profile.phone || profile.petName);
};
