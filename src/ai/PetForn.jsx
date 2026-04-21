import React, { useEffect, useMemo, useState } from "react";
import {
  clearAiPetFormDraft,
  readAiAuthState,
  readAiPetFormDraft,
  updateAiUserData,
  writeAiPetFormDraft,
} from "./AiAuth";
import logo from "../assets/images/logo.png";

const API_BASE_URL = "https://snoutiq.com/backend/api";
const DOG_BREEDS_URL = `${API_BASE_URL}/dog-breeds/all`;
const CAT_BREEDS_URL = `${API_BASE_URL}/cat-breeds/with-indian`;

const CHECK_OPTIONS = [
  { key: "neutered", label: "Neutered/Spayed" },
  { key: "vaccinated", label: "Vaccinated" },
  { key: "dewormed", label: "Dewormed" },
];

const DEFAULT_FORM = {
  pet_name: "",
  pet_type: "dog",
  breed: "",
  pet_dob: "",
  sex: "male",
  neutered: false,
  vaccinated: false,
  dewormed: false,
  last_deworming_date: "",
  latitude: null,
  longitude: null,
  owner_name: "",
  owner_phone: "",
};

const normalizeBoolean = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;
  const normalized = String(value ?? "")
    .trim()
    .toLowerCase();
  return normalized === "1" || normalized === "true" || normalized === "yes";
};

const normalizeSex = (value) => {
  const normalized = String(value ?? "")
    .trim()
    .toLowerCase();
  return normalized === "female" ? "female" : "male";
};

const normalizePhone = (value) => {
  const digits = String(value ?? "").replace(/[^\d]/g, "");
  if (digits.length === 10) return `91${digits}`;
  return digits;
};

const normalizePetType = (value) => {
  const normalized = String(value ?? "")
    .trim()
    .toLowerCase();
  return normalized === "cat" ? "cat" : "dog";
};

const normalizeDateValue = (value) => {
  const normalized = String(value ?? "").trim();
  return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : "";
};

const toNullableNumber = (value) => {
  const parsed = Number.parseFloat(value);
  return Number.isFinite(parsed) ? parsed : null;
};

const isPhoneLikeName = (value) => {
  const normalized = String(value ?? "").trim();
  if (!normalized) return false;
  const digits = normalized.replace(/[^\d]/g, "");
  const letters = normalized.replace(/[^a-z]/gi, "");
  return digits.length >= 7 && letters.length === 0;
};

const sanitizeOwnerName = (value) =>
  isPhoneLikeName(value) ? "" : String(value ?? "").trim();

const formatBreedLabel = (breedKey, subBreed = "") =>
  [breedKey, subBreed]
    .filter(Boolean)
    .join(" ")
    .replace(/[/-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (char) => char.toUpperCase());

const getPrimaryPet = (user) => {
  if (user?.pet && typeof user.pet === "object") return user.pet;
  if (Array.isArray(user?.pets) && user.pets.length > 0) return user.pets[0];
  return null;
};

const getStoredCoordinates = (user = {}) => {
  const latitude =
    toNullableNumber(user?.latitude) ??
    toNullableNumber(user?.userLatitude) ??
    toNullableNumber(localStorage.getItem("userLatitude"));
  const longitude =
    toNullableNumber(user?.longitude) ??
    toNullableNumber(user?.userLongitude) ??
    toNullableNumber(localStorage.getItem("userLongitude"));

  return {
    latitude,
    longitude,
  };
};

const calculateAgeFromDob = (dobValue) => {
  const normalizedDob = normalizeDateValue(dobValue);
  if (!normalizedDob) return "";

  const dob = new Date(`${normalizedDob}T00:00:00`);
  if (Number.isNaN(dob.getTime())) return "";

  const today = new Date();
  let years = today.getFullYear() - dob.getFullYear();
  const monthDiff = today.getMonth() - dob.getMonth();
  const dayDiff = today.getDate() - dob.getDate();

  if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
    years -= 1;
  }

  return String(Math.max(years, 0));
};

const buildDogBreedOptions = (payload) => {
  const options = [];
  const breeds =
    payload?.breeds &&
    typeof payload.breeds === "object" &&
    !Array.isArray(payload.breeds)
      ? payload.breeds
      : {};

  Object.keys(breeds).forEach((breedKey) => {
    const label = formatBreedLabel(breedKey);
    if (label) {
      options.push(label);
    }

    const subBreeds = Array.isArray(breeds[breedKey]) ? breeds[breedKey] : [];
    subBreeds.forEach((subBreed) => {
      const formatted = formatBreedLabel(breedKey, subBreed);
      if (formatted) {
        options.push(formatted);
      }
    });
  });

  return Array.from(new Set([...options, "Mixed Breed", "Other"]))
    .filter(Boolean)
    .sort((left, right) => left.localeCompare(right));
};

const buildCatBreedOptions = (payload) =>
  Array.from(
    new Set(
      [
        ...(Array.isArray(payload?.data) ? payload.data : []).map((item) =>
          String(item?.name || item?.id || "").trim(),
        ),
        "Indian Cat",
        "Mixed / Other",
      ].filter(Boolean),
    ),
  ).sort((left, right) => left.localeCompare(right));

const hasUsablePetProfile = (authState) => {
  const user = authState?.user || {};
  const primaryPet = getPrimaryPet(user);

  const registrationFlag =
    normalizeBoolean(authState?.registrationComplete) ||
    normalizeBoolean(user?.registrationComplete) ||
    normalizeBoolean(user?.registration_complete) ||
    normalizeBoolean(user?.profileCompleted);

  if (registrationFlag) return true;

  const petName = String(
    primaryPet?.name ?? primaryPet?.pet_name ?? user?.pet_name ?? "",
  ).trim();

  const ownerName = sanitizeOwnerName(
    user?.pet_owner_name ?? user?.owner_name ?? user?.name ?? "",
  );

  return Boolean(petName && ownerName);
};

const buildInitialForm = () => {
  const draft = readAiPetFormDraft();
  const authState = readAiAuthState();
  const user = authState.user || {};
  const primaryPet = getPrimaryPet(user);
  const storedCoordinates = getStoredCoordinates(user);

  return {
    ...DEFAULT_FORM,
    pet_name:
      draft?.pet_name ??
      primaryPet?.name ??
      primaryPet?.pet_name ??
      user?.pet_name ??
      "",
    pet_type: normalizePetType(
      draft?.pet_type ??
        primaryPet?.pet_type ??
        primaryPet?.species ??
        user?.pet_type ??
        "dog",
    ),
    breed: String(
      draft?.breed ?? primaryPet?.breed ?? user?.breed ?? "",
    ).trim(),
    pet_dob: normalizeDateValue(
      draft?.pet_dob ??
        primaryPet?.pet_dob ??
        primaryPet?.dob ??
        user?.pet_dob ??
        "",
    ),
    sex: normalizeSex(
      draft?.sex ??
        primaryPet?.pet_gender ??
        primaryPet?.gender ??
        user?.pet_gender ??
        "male",
    ),
    neutered:
      draft?.neutered ??
      normalizeBoolean(
        primaryPet?.is_nuetered ??
          primaryPet?.is_neutered ??
          user?.is_nuetered ??
          user?.is_neutered,
      ),
    vaccinated:
      draft?.vaccinated ??
      normalizeBoolean(
        primaryPet?.vaccenated_yes_no ?? user?.vaccenated_yes_no,
      ),
    dewormed:
      draft?.dewormed ??
      normalizeBoolean(primaryPet?.deworming_yes_no ?? user?.deworming_yes_no),
    last_deworming_date: normalizeDateValue(
      draft?.last_deworming_date ??
        primaryPet?.last_deworming_date ??
        user?.last_deworming_date ??
        "",
    ),
    latitude:
      toNullableNumber(draft?.latitude) ?? storedCoordinates.latitude ?? null,
    longitude:
      toNullableNumber(draft?.longitude) ?? storedCoordinates.longitude ?? null,
    owner_name: sanitizeOwnerName(
      draft?.owner_name ??
        user?.pet_owner_name ??
        user?.owner_name ??
        user?.name ??
        "",
    ),
    owner_phone: draft?.owner_phone ?? user?.phone ?? user?.mobileNumber ?? "",
  };
};

const buildUpdatedPet = (currentUser, form) => {
  const currentPet = getPrimaryPet(currentUser) || {};
  const sexLabel = form.sex.charAt(0).toUpperCase() + form.sex.slice(1);
  const resolvedPetDob =
    normalizeDateValue(form.pet_dob) ||
    normalizeDateValue(currentPet?.pet_dob) ||
    normalizeDateValue(currentUser?.pet_dob);
  const resolvedPetAge =
    calculateAgeFromDob(resolvedPetDob) ||
    String(currentPet?.pet_age ?? currentUser?.pet_age ?? "").trim();
  const resolvedLatitude =
    toNullableNumber(form.latitude) ??
    toNullableNumber(currentUser?.latitude) ??
    null;
  const resolvedLongitude =
    toNullableNumber(form.longitude) ??
    toNullableNumber(currentUser?.longitude) ??
    null;

  return {
    ...currentPet,
   id:
  currentPet?.id ??
  currentPet?.pet_id ??
  currentUser?.pet_id ??
  null,
pet_id:
  currentPet?.pet_id ??
  currentPet?.id ??
  currentUser?.pet_id ??
  null,
    name:
      form.pet_name.trim() || currentPet?.name || currentUser?.pet_name || "",
    pet_name:
      form.pet_name.trim() || currentPet?.pet_name || currentPet?.name || "",
    breed: form.breed.trim() || currentPet?.breed || currentUser?.breed || "",
    pet_age: resolvedPetAge,
    pet_gender: sexLabel,
    gender: sexLabel,
    is_nuetered: form.neutered,
    is_neutered: form.neutered,
    vaccenated_yes_no: form.vaccinated,
    deworming_yes_no: form.dewormed,
    last_deworming_date: form.dewormed
      ? normalizeDateValue(form.last_deworming_date) ||
        normalizeDateValue(currentPet?.last_deworming_date) ||
        normalizeDateValue(currentUser?.last_deworming_date)
      : "",
    pet_type:
      normalizePetType(form.pet_type) ||
      normalizePetType(currentPet?.pet_type) ||
      normalizePetType(currentUser?.pet_type),
    pet_dob: resolvedPetDob,
    pet_doc1: currentPet?.pet_doc1 || currentUser?.pet_doc1 || "",
    pet_doc2: currentPet?.pet_doc2 || currentUser?.pet_doc2 || "",
    weight:
      currentPet?.weight ||
      currentUser?.weight ||
      currentUser?.pet_weight ||
      "",
    pet_weight:
      currentPet?.pet_weight ||
      currentUser?.pet_weight ||
      currentUser?.weight ||
      "",
    latitude: resolvedLatitude,
    longitude: resolvedLongitude,
  };
};

const dedupePets = (currentPets, updatedPet) => {
  const pets = Array.isArray(currentPets) ? currentPets : [];
  const updatedPetId = String(updatedPet?.id ?? updatedPet?.pet_id ?? "").trim();
  const updatedPetName = String(
    updatedPet?.name ?? updatedPet?.pet_name ?? ""
  )
    .trim()
    .toLowerCase();
  const updatedPetType = String(
    updatedPet?.pet_type ?? updatedPet?.species ?? updatedPet?.type ?? ""
  )
    .trim()
    .toLowerCase();

  return [
    updatedPet,
    ...pets.filter((item) => {
      if (!item || typeof item !== "object") return false;

      const itemId = String(item?.id ?? item?.pet_id ?? "").trim();
      if (updatedPetId && itemId && itemId === updatedPetId) return false;

      const itemName = String(item?.name ?? item?.pet_name ?? "")
        .trim()
        .toLowerCase();
      const itemType = String(item?.pet_type ?? item?.species ?? item?.type ?? "")
        .trim()
        .toLowerCase();

      if (
        !itemId &&
        updatedPetName &&
        itemName === updatedPetName &&
        (!updatedPetType || !itemType || itemType === updatedPetType)
      ) {
        return false;
      }

      return true;
    }),
  ];
};

const styles = {
  overlay: {
    minHeight: "100vh",
    display: "flex",
    alignItems: "flex-start",
    justifyContent: "center",
    background: "linear-gradient(135deg, #e8f5e9 0%, #f3e5f5 100%)",
    padding: "clamp(12px, 3vw, 24px)",
    boxSizing: "border-box",
    overflowY: "auto",
    WebkitOverflowScrolling: "touch",
  },
  card: {
    background: "#fff",
    borderRadius: "clamp(14px, 2vw, 18px)",
    padding: "clamp(18px, 4vw, 36px)",
    width: "min(100%, 520px)",
    maxWidth: "100%",
    boxShadow: "0 4px 24px rgba(0,0,0,0.10)",
    boxSizing: "border-box",
    margin: "0 auto",
  },
  logo: {
    textAlign: "center",
    marginBottom: "8px",
    fontSize: "clamp(2rem, 5vw, 2.25rem)",
    fontWeight: "700",
    color: "#2e7d32",
  },
  subtitle: {
    textAlign: "center",
    color: "#666",
    fontSize: "clamp(13px, 3.3vw, 14px)",
    lineHeight: 1.5,
    marginBottom: "clamp(20px, 4vw, 28px)",
    maxWidth: "28rem",
    marginInline: "auto",
  },
  sectionTitle: {
    fontSize: "clamp(11px, 2.6vw, 12px)",
    fontWeight: "600",
    color: "#888",
    textTransform: "uppercase",
    letterSpacing: "0.5px",
    marginTop: "clamp(18px, 4vw, 20px)",
    marginBottom: "12px",
  },
  row: {
    display: "flex",
    gap: "12px",
    flexWrap: "wrap",
  },
  field: {
    display: "flex",
    flexDirection: "column",
    marginBottom: "14px",
    flex: "1 1 180px",
    minWidth: 0,
  },
  label: {
    fontSize: "clamp(12px, 3vw, 13px)",
    fontWeight: "500",
    color: "#444",
    marginBottom: "5px",
  },
  input: {
    border: "1.5px solid #e0e0e0",
    borderRadius: "8px",
    padding: "11px 12px",
    fontSize: "16px",
    color: "#222",
    outline: "none",
    transition: "border-color 0.2s",
    background: "#fafafa",
    width: "100%",
    minWidth: 0,
    boxSizing: "border-box",
  },
  select: {
    border: "1.5px solid #e0e0e0",
    borderRadius: "8px",
    padding: "11px 12px",
    fontSize: "16px",
    color: "#222",
    outline: "none",
    background: "#fafafa",
    cursor: "pointer",
    width: "100%",
    minWidth: 0,
    boxSizing: "border-box",
  },
  checkRow: {
    display: "flex",
    gap: "16px",
    flexWrap: "wrap",
    marginBottom: "14px",
  },
  checkLabel: {
    display: "flex",
    alignItems: "center",
    gap: "6px",
    fontSize: "14px",
    color: "#333",
    cursor: "pointer",
    flex: "1 1 140px",
    minWidth: 0,
  },
  helperText: {
    marginTop: "6px",
    fontSize: "12px",
    lineHeight: 1.5,
    color: "#6b7280",
  },
  helperTextError: {
    color: "#b91c1c",
  },
  locationCard: {
    border: "1px solid #dbe7dc",
    borderRadius: "12px",
    padding: "12px 14px",
    background: "#f8fff8",
    marginBottom: "14px",
  },
  locationCardHeader: {
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
    gap: "12px",
    flexWrap: "wrap",
  },
  locationTitle: {
    fontSize: "14px",
    fontWeight: "600",
    color: "#1f2937",
  },
  locationButton: {
    border: "1px solid #2e7d32",
    borderRadius: "999px",
    padding: "8px 14px",
    background: "#ffffff",
    color: "#2e7d32",
    fontSize: "13px",
    fontWeight: "600",
    cursor: "pointer",
  },
  locationMeta: {
    marginTop: "8px",
    fontSize: "12px",
    lineHeight: 1.5,
    color: "#4b5563",
  },
  button: {
    width: "100%",
    padding: "14px",
    background: "#2e7d32",
    color: "#fff",
    border: "none",
    borderRadius: "10px",
    fontSize: "16px",
    fontWeight: "600",
    cursor: "pointer",
    marginTop: "8px",
    transition: "background 0.2s",
    boxSizing: "border-box",
  },
  error: {
    background: "#ffeaea",
    border: "1px solid #f44336",
    borderRadius: "8px",
    padding: "10px 14px",
    color: "#c62828",
    fontSize: "clamp(12px, 3vw, 13px)",
    marginBottom: "14px",
    wordBreak: "break-word",
  },
  locationBadge: {
  display: "inline-flex",
  alignItems: "center",
  gap: "8px",
  marginTop: "10px",
  padding: "8px 12px",
  borderRadius: "999px",
  background: "#ecfdf3",
  border: "1px solid #a7f3d0",
  color: "#047857",
  fontSize: "12px",
  fontWeight: "600",
},
locationBadgeDot: {
  width: "8px",
  height: "8px",
  borderRadius: "999px",
  background: "#10b981",
},
locationCoords: {
  marginTop: "8px",
  fontSize: "12px",
  lineHeight: 1.5,
  color: "#374151",
},
};

export default function PetForn({ onComplete, submitIntake }) {
  const [form, setForm] = useState(buildInitialForm);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [breedOptions, setBreedOptions] = useState([]);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [breedLoadError, setBreedLoadError] = useState("");
  const [breedError, setBreedError] = useState("");
  const [locationStatus, setLocationStatus] = useState(() =>
    form.latitude != null && form.longitude != null ? "ready" : "idle",
  );
  const [locationError, setLocationError] = useState("");
  const [locationSuccessMessage, setLocationSuccessMessage] = useState(
  form.latitude != null && form.longitude != null
    ? "Location selected successfully."
    : ""
);
const [locationUpdatedAt, setLocationUpdatedAt] = useState(
  form.latitude != null && form.longitude != null ? new Date() : null
);

  const setField = (key, value) =>
    setForm((prev) => ({
      ...prev,
      [key]: value,
    }));

  const agePreview = useMemo(() => {
    const normalizedDob = normalizeDateValue(form.pet_dob);
    if (!normalizedDob) return "";

    const calculatedAge = calculateAgeFromDob(normalizedDob);
    if (calculatedAge === "") return "";

    return `Age will be calculated automatically from DOB${calculatedAge ? ` (${calculatedAge} year${calculatedAge === "1" ? "" : "s"})` : ""}.`;
  }, [form.pet_dob]);

  const resolvedBreedOptions = useMemo(() => {
    if (!form.breed || breedOptions.includes(form.breed)) {
      return breedOptions;
    }

    return [form.breed, ...breedOptions];
  }, [breedOptions, form.breed]);

  useEffect(() => {
    writeAiPetFormDraft(form);
  }, [form]);

  useEffect(() => {
    const authState = readAiAuthState();

    if (hasUsablePetProfile(authState)) {
      const user = authState?.user || {};
      const primaryPet = getPrimaryPet(user);

      if (typeof onComplete === "function") {
        onComplete(
          primaryPet?.id ?? user?.pet_id ?? null,
          primaryPet?.name ?? primaryPet?.pet_name ?? user?.pet_name ?? "",
          user,
        );
      }
    }
  }, [onComplete]);

useEffect(() => {
  const authState = readAiAuthState();
  const storedCoordinates = getStoredCoordinates(authState?.user || {});

  if (storedCoordinates.latitude == null || storedCoordinates.longitude == null) {
    return;
  }

  setForm((prev) => {
    if (prev.latitude != null && prev.longitude != null) {
      return prev;
    }

    return {
      ...prev,
      latitude: storedCoordinates.latitude,
      longitude: storedCoordinates.longitude,
    };
  });

  setLocationStatus("ready");
  setLocationError("");
  setLocationSuccessMessage("Location already selected from your saved device location.");
  setLocationUpdatedAt(new Date());
}, []);

  useEffect(() => {
    let active = true;
    const controller = new AbortController();

    const fetchBreeds = async () => {
      setLoadingBreeds(true);
      setBreedLoadError("");
      setBreedError("");

      try {
        const response = await fetch(
          form.pet_type === "cat" ? CAT_BREEDS_URL : DOG_BREEDS_URL,
          {
            signal: controller.signal,
            headers: {
              Accept: "application/json",
            },
          },
        );

        const data = await response.json().catch(() => ({}));
        if (!active) return;

        const options =
          form.pet_type === "cat"
            ? buildCatBreedOptions(data)
            : buildDogBreedOptions(data);

        setBreedOptions(options);
      } catch (fetchError) {
        if (fetchError?.name === "AbortError" || !active) return;

        const fallback =
          form.pet_type === "cat"
            ? ["Indian Cat", "Mixed / Other"]
            : ["Mixed Breed", "Other"];

        setBreedOptions(fallback);
        setBreedLoadError(
          form.pet_type === "cat"
            ? "Cat breeds could not be loaded right now."
            : "Dog breeds could not be loaded right now.",
        );
      } finally {
        if (active) {
          setLoadingBreeds(false);
        }
      }
    };

    fetchBreeds();

    return () => {
      active = false;
      controller.abort();
    };
  }, [form.pet_type]);

  useEffect(() => {
    if (!form.breed) {
      setBreedError("");
      return;
    }

    if (breedOptions.length > 0 && !breedOptions.includes(form.breed)) {
      setBreedError(
        "Saved breed is not in the current list. Please pick the closest match.",
      );
      return;
    }

    setBreedError("");
  }, [breedOptions, form.breed]);

 const captureCurrentLocation = () => {
  if (typeof navigator === "undefined" || !navigator.geolocation) {
    setLocationStatus("error");
    setLocationError("Location is not available on this device.");
    setLocationSuccessMessage("");
    return;
  }

  setLocationStatus("loading");
  setLocationError("");
  setLocationSuccessMessage("");

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const nextLatitude = toNullableNumber(position?.coords?.latitude);
      const nextLongitude = toNullableNumber(position?.coords?.longitude);

      if (nextLatitude == null || nextLongitude == null) {
        setLocationStatus("error");
        setLocationError("Could not read your current location.");
        setLocationSuccessMessage("");
        return;
      }

      localStorage.setItem("userLatitude", String(nextLatitude));
      localStorage.setItem("userLongitude", String(nextLongitude));

      setForm((prev) => ({
        ...prev,
        latitude: nextLatitude,
        longitude: nextLongitude,
      }));

      setLocationStatus("ready");
      setLocationError("");
      setLocationSuccessMessage("Location selected successfully.");
      setLocationUpdatedAt(new Date());
    },
    (geoError) => {
      setLocationStatus("error");
      setLocationError(
        geoError?.code === 1
          ? "Location permission was denied."
          : "Could not fetch your current location."
      );
      setLocationSuccessMessage("");
    },
    {
      enableHighAccuracy: true,
      timeout: 12000,
      maximumAge: 300000,
    }
  );
};

  const handleSubmit = async () => {
    const sanitizedOwnerName = sanitizeOwnerName(form.owner_name);

    if (!form.pet_name.trim()) {
      setError("Please fill in your pet's name.");
      return;
    }

    if (!sanitizedOwnerName) {
      setError("Please enter your name, not a phone number.");
      return;
    }

    if (!normalizeDateValue(form.pet_dob)) {
      setError("Please select your pet's date of birth.");
      return;
    }

    if (form.dewormed && !normalizeDateValue(form.last_deworming_date)) {
      setError("Please add the last deworming date.");
      return;
    }

    setError("");
    setLoading(true);

    try {
      const authState = readAiAuthState();
      const currentUser = authState.user || {};
      const storedCoordinates = getStoredCoordinates(currentUser);
      const enrichedForm = {
        ...form,
        owner_name: sanitizedOwnerName,
        latitude: toNullableNumber(form.latitude) ?? storedCoordinates.latitude,
        longitude:
          toNullableNumber(form.longitude) ?? storedCoordinates.longitude,
      };
      const updatedPet = buildUpdatedPet(currentUser, enrichedForm);
      const normalizedPhone =
        normalizePhone(form.owner_phone) ||
        currentUser?.phone ||
        currentUser?.mobileNumber ||
        "";


let result = null;
if (typeof submitIntake === "function") {
  result = await submitIntake(enrichedForm);
}

const savedPetId =
  result?.pet_id ??
  result?.data?.pet_id ??
  result?.pet?.id ??
  result?.data?.pet?.id ??
  updatedPet?.id ??
  currentUser?.pet_id ??
  null;

const updatedPetWithId = {
  ...updatedPet,
  id: savedPetId,
  pet_id: savedPetId,
};

const nextPets = dedupePets(currentUser?.pets, updatedPetWithId);

const nextState = updateAiUserData({
  name: sanitizedOwnerName,
  owner_name: sanitizedOwnerName,
  pet_owner_name: sanitizedOwnerName,
  phone: normalizedPhone,
  mobileNumber: normalizedPhone,
  city: currentUser?.city || "",
  location: currentUser?.location || "",
  latitude: updatedPetWithId.latitude,
  longitude: updatedPetWithId.longitude,

  pet_id: savedPetId,

  pet_name: updatedPetWithId.name,
  breed: updatedPetWithId.breed,
  pet_age: updatedPetWithId.pet_age,
  pet_gender: updatedPetWithId.pet_gender,
  pet_type: updatedPetWithId.pet_type,
  pet_dob: updatedPetWithId.pet_dob,
  pet_doc1: updatedPetWithId.pet_doc1,
  pet_doc2: updatedPetWithId.pet_doc2,
  pet_weight: updatedPetWithId.pet_weight,
  weight: updatedPetWithId.weight,
  is_nuetered: updatedPetWithId.is_nuetered,
  deworming_yes_no: updatedPetWithId.deworming_yes_no,
  last_deworming_date: updatedPetWithId.last_deworming_date,
  vaccenated_yes_no: updatedPetWithId.vaccenated_yes_no,

  pet: updatedPetWithId,
  pets: nextPets,

  registrationComplete: true,
  registration_complete: true,
  profileCompleted: true,
});

clearAiPetFormDraft();

if (typeof onComplete === "function") {
  await onComplete(
    savedPetId,
    updatedPetWithId.name,
    nextState?.user || null
  );
}
    } catch (err) {
      setError(
        err?.response?.data?.error ||
          err?.response?.data?.message ||
          err?.message ||
          "Something went wrong. Please try again.",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={styles.overlay}>
      <div style={styles.card}>
        <div style={styles.logo}>
          <img
            src={logo}
            alt="SnoutIQ"
            style={{ height: "20px", verticalAlign: "middle" }}
          />
        </div>

        <div style={styles.sectionTitle}>About Your Pet</div>

        <div style={styles.row}>
          <div style={styles.field}>
            <label style={styles.label}>Pet Name *</label>
            <input
              style={styles.input}
              value={form.pet_name}
              onChange={(event) => setField("pet_name", event.target.value)}
              placeholder="e.g. Bruno"
            />
          </div>

          <div style={styles.field}>
            <label style={styles.label}>Pet Type</label>
            <select
              style={styles.select}
              value={form.pet_type}
              onChange={(event) =>
                setForm((prev) => ({
                  ...prev,
                  pet_type: normalizePetType(event.target.value),
                  breed: "",
                }))
              }
            >
              <option value="dog">Dog</option>
              <option value="cat">Cat</option>
            </select>
          </div>
        </div>

        <div style={styles.row}>
          <div style={styles.field}>
            <label style={styles.label}>Breed</label>
            <select
              style={styles.select}
              value={form.breed}
              onChange={(event) => setField("breed", event.target.value)}
              disabled={loadingBreeds}
            >
              <option value="">
                {loadingBreeds ? "Loading breeds..." : "Select breed"}
              </option>
              {resolvedBreedOptions.map((option) => (
                <option key={option} value={option}>
                  {option}
                </option>
              ))}
            </select>
            {breedLoadError || breedError ? (
              <div style={{ ...styles.helperText, ...styles.helperTextError }}>
                {breedLoadError || breedError}
              </div>
            ) : (
              <div style={styles.helperText}>
              </div>
            )}
          </div>

          <div style={styles.field}>
            <label style={styles.label}>Sex</label>
            <select
              style={styles.select}
              value={form.sex}
              onChange={(event) => setField("sex", event.target.value)}
            >
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>
        </div>

        <div style={styles.row}>
          <div style={styles.field}>
            <label style={styles.label}>Date of Birth *</label>
            <input
              style={styles.input}
              type="date"
              value={form.pet_dob}
              onChange={(event) => setField("pet_dob", event.target.value)}
              max={new Date().toISOString().split("T")[0]}
            />
            {agePreview ? (
              <div style={styles.helperText}>{agePreview}</div>
            ) : null}
          </div>
        </div>

        <div style={styles.checkRow}>
          {CHECK_OPTIONS.map(({ key, label }) => (
            <label key={key} style={styles.checkLabel}>
              <input
                type="checkbox"
                checked={form[key]}
                onChange={(event) => {
                  const checked = event.target.checked;
                  setForm((prev) => ({
                    ...prev,
                    [key]: checked,
                    ...(key === "dewormed" && !checked
                      ? { last_deworming_date: "" }
                      : {}),
                  }));
                }}
              />
              {label}
            </label>
          ))}
        </div>

        {form.dewormed ? (
          <div style={styles.field}>
            <label style={styles.label}>Last Deworming Date *</label>
            <input
              style={styles.input}
              type="date"
              value={form.last_deworming_date}
              onChange={(event) =>
                setField("last_deworming_date", event.target.value)
              }
              max={new Date().toISOString().split("T")[0]}
            />
          </div>
        ) : null}

        <div style={styles.sectionTitle}>About You</div>

        <div style={styles.field}>
          <label style={styles.label}>Your Name *</label>
          <input
            style={styles.input}
            value={form.owner_name}
            onChange={(event) => setField("owner_name", event.target.value)}
            placeholder="Your full name"
          />
        </div>

        {/* <div style={styles.field}>
          <label style={styles.label}>Phone (WhatsApp)</label>
          <input
            style={styles.input}
            value={form.owner_phone}
            onChange={(event) => setField("owner_phone", event.target.value)}
            placeholder="+91 XXXXX XXXXX"
          />
        </div> */}

        <div style={styles.locationCard}>
          <div style={styles.locationCardHeader}>
            <div style={styles.locationTitle}>Current location</div>
            <button
              type="button"
              style={styles.locationButton}
              onClick={captureCurrentLocation}
              disabled={locationStatus === "loading"}
            >
              {locationStatus === "loading"
                ? "Checking..."
                : locationStatus === "ready"
                  ? "Update location"
                  : "Use current location"}
            </button>
          </div>

          <div style={styles.locationMeta}>
            {locationStatus === "ready"
              ? "Your current location has been captured and will be used automatically."
              : "Allow browser location to save latitude and longitude without showing extra fields."}
          </div>

          {locationStatus === "ready" && !locationError ? (
            <>
              <div style={styles.locationBadge}>
                <span style={styles.locationBadgeDot} />
                {locationSuccessMessage || "Location selected successfully."}
              </div>
            </>
          ) : null}

          {locationError ? (
            <div style={{ ...styles.helperText, ...styles.helperTextError }}>
              {locationError}
            </div>
          ) : null}
        </div>
              {error ? <div style={styles.error}>{error}</div> : null}

        <button style={styles.button} onClick={handleSubmit} disabled={loading}>
          {loading ? "Setting up..." : "Start Chat with AI"}
        </button>
        
      </div>
    </div>
  );
}
