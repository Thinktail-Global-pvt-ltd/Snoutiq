import React, { useEffect, useRef, useState } from "react";
import { Helmet } from "react-helmet-async";
import { useNavigate } from "react-router-dom";
import { X } from "lucide-react";
import { apiBaseUrl, apiPost } from "../lib/api";
import logo from "../assets/images/logo.webp";
import avatarLogo from "../assets/images/snoutiq app.png";
import VetNearPetDetails from "./askBooking/VetNearPetDetails";
import VetNearPayment from "./askBooking/VetNearPayment";
import VideoCallPetDetails from "./askBooking/VideoCallPetDetails";
import { VideoCallPayment } from "./askBooking/VideoCallPayment";
import "./AskPage.css";

const ASK_TITLE = "Snoutiq - Is My Pet Okay? Free AI Pet Health Check";
const ASK_DESCRIPTION =
  "Free AI pet symptom checker for Indian pet parents. Get expert triage guidance in seconds. No signup needed.";
const ASK_CANONICAL = "https://snoutiq.com/ask";
const ASK_STORAGE_KEY = "snoutiq-ask-state-v1";
const ASK_PROFILE_KEY = "snoutiq-ask-profile-v1";
const ASK_UI_STORAGE_KEY = "snoutiq-ask-ui-v1";
const ASK_DAILY_USAGE_KEY = "snoutiq-ask-daily-usage-v1";
const ASK_VET_NEAR_STANDALONE_KEY = "snoutiq-vet-near-me-standalone";
const ASK_VIDEO_CALL_STANDALONE_KEY = "snoutiq-video-call-copied-flow";
const FREE_CHECK_LIMIT = 3;
const GAUGE_CIRCUMFERENCE = 201.1;
const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;
const DEFAULT_ASK_PROFILE = {
  ownerName: "",
  phone: "",
  petName: "",
  breed: "",
  dob: "",
  location: "",
  lat: "",
  long: "",
};
const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || "";
const CURRENT_LOCATION_FALLBACK_LABEL = "Current location";

const QUICK_SYMPTOMS = [
  {
    key: "not-eating",
    emoji: "🍽",
    title: "Not Eating",
    subtitle: "Appetite loss, skipping meals",
    species: "dog",
    message: "My dog has not eaten for 2 days and is very lethargic",
  },
  {
    key: "vomiting",
    emoji: "🤢",
    title: "Vomiting",
    subtitle: "Throwing up, retching",
    species: "dog",
    message: "My dog has been vomiting repeatedly since this morning",
  },
  {
    key: "limping",
    emoji: "🦮",
    title: "Limping",
    subtitle: "Lameness, joint pain",
    species: "dog",
    message:
      "My dog is limping and putting no weight on one leg, leg is swollen",
  },
  {
    key: "diarrhea",
    emoji: "💧",
    title: "Diarrhea",
    subtitle: "Loose stools, stomach",
    species: "dog",
    message: "My pet has loose stools or diarrhea since yesterday",
  },
  {
    key: "skin-itching",
    emoji: "🐱",
    title: "Skin / Itching",
    subtitle: "Hair loss, scratching",
    species: "cat",
    message:
      "My cat has circular patches of hair loss and is scratching a lot",
  },
  {
    key: "lethargy",
    emoji: "😴",
    title: "Lethargy",
    subtitle: "Weak, dull, low energy",
    species: "dog",
    message:
      "My pet seems very tired, lethargic and not interested in anything",
  },
];

const SPECIES_OPTIONS = [
  { value: "dog", label: "Dog", emoji: "🐕" },
  { value: "cat", label: "Cat", emoji: "🐈" },
  { value: "rabbit", label: "Rabbit", emoji: "🐇" },
  { value: "bird", label: "Bird", emoji: "🐦" },
];

const CTA_ROUTE_MAP = {
  video_consult: "/video-call-pet-details",
  clinic: "/vet-near-me-pet-details",
  vet_at_home: "/vet-near-me-pet-details",
  emergency: "/vet-near-me-pet-details",
  govt: "/vet-near-me-pet-details",
};

const DEEPLINK_ROUTE_MAP = {
  "snoutiq://video-consult": "/video-call-pet-details",
  "snoutiq://vet-at-home": "/vet-near-me-pet-details",
  "snoutiq://clinic-booking": "/vet-near-me-pet-details",
  "snoutiq://find-clinic": "/vet-near-me-pet-details",
  "snoutiq://emergency": "/vet-near-me-pet-details",
  "snoutiq://govt-hospitals": "/vet-near-me-pet-details",
};

const ASK_FLOW_MODAL_KIND_MAP = {
  "/video-call-pet-details": "video-pet-details",
  "/video-call-payment": "video-payment",
  "/vet-near-me-pet-details": "vet-pet-details",
  "/vet-near-me-payment": "vet-payment",
};

const getTodayKey = () => new Date().toISOString().slice(0, 10);

const getTimeLabel = (value = new Date()) =>
  new Intl.DateTimeFormat("en-IN", {
    hour: "numeric",
    minute: "2-digit",
  }).format(value);

const safeParse = (value, fallback) => {
  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
};

const formatBytes = (bytes) => {
  const value = Number(bytes || 0);
  if (!Number.isFinite(value) || value <= 0) return "";
  if (value >= 1024 * 1024) {
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
  }
  return `${Math.max(1, Math.round(value / 1024))} KB`;
};

const readFileAsDataUrl = (file) =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ""));
    reader.onerror = () =>
      reject(reader.error || new Error("Unable to read file."));
    reader.readAsDataURL(file);
  });

const buildImagePayload = (attachment) => {
  if (!attachment?.base64) return {};
  return {
    image_base64: attachment.base64,
    image_mime: attachment.mime || "image/jpeg",
  };
};

const defaultImageMessage = (isFollowup = false) =>
  isFollowup
    ? "Please review this new image and update the assessment."
    : "Please review this image and tell me what you see.";

const defaultImageVisualMessage = "Image uploaded for review.";

const serializeEntriesForStorage = (entries) =>
  Array.isArray(entries)
    ? entries.map((entry) => {
        if (!entry || typeof entry !== "object") return entry;
        const nextEntry = { ...entry };
        delete nextEntry.attachment;
        return nextEntry;
      })
    : [];

const revokeBlobPreviewUrl = (previewUrl) => {
  const value = String(previewUrl || "").trim();
  if (!value || !value.startsWith("blob:") || typeof URL === "undefined") return;
  try {
    URL.revokeObjectURL(value);
  } catch {
    // Ignore stale blob URLs.
  }
};

const readDailyUsage = () => {
  if (typeof window === "undefined") return 0;
  const raw = safeParse(window.localStorage.getItem(ASK_DAILY_USAGE_KEY), null);
  if (!raw || raw.date !== getTodayKey()) return 0;
  return Math.max(0, Number(raw.count) || 0);
};

const writeDailyUsage = (count) => {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(
    ASK_DAILY_USAGE_KEY,
    JSON.stringify({
      date: getTodayKey(),
      count: Math.max(0, Number(count) || 0),
    })
  );
};

const parseCoordinate = (value) => {
  if (value === undefined || value === null) return null;
  if (typeof value === "string" && !value.trim()) return null;
  const numberValue = Number(value);
  return Number.isFinite(numberValue)
    ? Number(numberValue.toFixed(6))
    : null;
};

const hasProfileCoordinates = (value) =>
  parseCoordinate(value?.lat) !== null && parseCoordinate(value?.long) !== null;

const shouldAutoFillDetectedLocation = (value) => {
  const text = String(value || "").trim();
  return (
    !text ||
    text === DEFAULT_ASK_PROFILE.location ||
    text === CURRENT_LOCATION_FALLBACK_LABEL
  );
};

const buildGoogleLocationLabel = (result) => {
  const components = Array.isArray(result?.address_components)
    ? result.address_components
    : [];
  const labels = [];
  const pushLabel = (value) => {
    const text = String(value || "").trim();
    if (text && !labels.includes(text)) {
      labels.push(text);
    }
  };

  components.forEach((component) => {
    const types = Array.isArray(component?.types) ? component.types : [];
    if (
      types.includes("sublocality_level_1") ||
      types.includes("sublocality") ||
      types.includes("neighborhood") ||
      types.includes("locality") ||
      types.includes("administrative_area_level_2") ||
      types.includes("administrative_area_level_1")
    ) {
      pushLabel(component.long_name);
    }
  });

  if (labels.length > 0) {
    return labels.slice(0, 2).join(", ");
  }

  return String(result?.formatted_address || "")
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean)
    .slice(0, 2)
    .join(", ");
};

const normalizePhoneInput = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  return digits.length > 10 ? digits.slice(-10) : digits;
};

const resolveDetectedLocationLabel = async (latitude, longitude) => {
  if (!GOOGLE_MAPS_API_KEY) return "";

  try {
    const response = await fetch(
      `https://maps.googleapis.com/maps/api/geocode/json?latlng=${latitude},${longitude}&key=${GOOGLE_MAPS_API_KEY}`
    );
    const data = await response.json();
    if (!Array.isArray(data?.results) || data.results.length === 0) return "";
    return buildGoogleLocationLabel(data.results[0]);
  } catch {
    return "";
  }
};

const sanitizeAskProfile = (value) => {
  const raw = value && typeof value === "object" ? value : {};
  return {
    ownerName: String(raw.ownerName || "").trim(),
    phone: normalizePhoneInput(raw.phone),
    petName: String(raw.petName || "").trim(),
    breed: String(raw.breed || "").trim(),
    dob: String(raw.dob || "").trim(),
    location: String(raw.location ?? DEFAULT_ASK_PROFILE.location).trim(),
    lat: parseCoordinate(raw.lat ?? raw.latitude) ?? "",
    long: parseCoordinate(raw.long ?? raw.longitude) ?? "",
  };
};

const readStoredProfile = () => {
  if (typeof window === "undefined") return DEFAULT_ASK_PROFILE;
  const raw = safeParse(window.localStorage.getItem(ASK_PROFILE_KEY), null);
  return sanitizeAskProfile(raw);
};

const writeStoredProfile = (profile) => {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(
    ASK_PROFILE_KEY,
    JSON.stringify(sanitizeAskProfile(profile))
  );
};

const clearStoredProfile = () => {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(ASK_PROFILE_KEY);
};

const readStoredUiState = () => {
  if (typeof window === "undefined") return null;
  const raw = safeParse(window.sessionStorage.getItem(ASK_UI_STORAGE_KEY), null);
  if (!raw || typeof raw !== "object") return null;
  return {
    inputValue: typeof raw.inputValue === "string" ? raw.inputValue : "",
    intakeOpen:
      Boolean(raw.intakeOpen) &&
      Boolean(
        raw.pendingInitialRequest &&
          typeof raw.pendingInitialRequest.message === "string" &&
          raw.pendingInitialRequest.message.trim()
      ),
    pendingInitialRequest:
      raw.pendingInitialRequest && typeof raw.pendingInitialRequest === "object"
        ? {
            message:
              typeof raw.pendingInitialRequest.message === "string"
                ? raw.pendingInitialRequest.message
                : "",
            species:
              typeof raw.pendingInitialRequest.species === "string"
                ? raw.pendingInitialRequest.species
                : "",
          }
        : null,
  };
};

const writeStoredUiState = (state) => {
  if (typeof window === "undefined") return;
  window.sessionStorage.setItem(
    ASK_UI_STORAGE_KEY,
    JSON.stringify({
      inputValue: typeof state?.inputValue === "string" ? state.inputValue : "",
      intakeOpen:
        Boolean(state?.intakeOpen) &&
        Boolean(
          state?.pendingInitialRequest &&
            typeof state.pendingInitialRequest.message === "string" &&
            state.pendingInitialRequest.message.trim()
        ),
      pendingInitialRequest:
        state?.pendingInitialRequest &&
        typeof state.pendingInitialRequest === "object"
          ? {
              message:
                typeof state.pendingInitialRequest.message === "string"
                  ? state.pendingInitialRequest.message
                  : "",
              species:
                typeof state.pendingInitialRequest.species === "string"
                  ? state.pendingInitialRequest.species
                  : "",
            }
          : null,
    })
  );
};

const clearStoredUiState = () => {
  if (typeof window === "undefined") return;
  window.sessionStorage.removeItem(ASK_UI_STORAGE_KEY);
};

const readStoredState = () => {
  if (typeof window === "undefined") return null;
  const raw = safeParse(window.localStorage.getItem(ASK_STORAGE_KEY), null);
  if (!raw || typeof raw !== "object") return null;
  return {
    species: typeof raw.species === "string" ? raw.species : "",
    sessionId: typeof raw.sessionId === "string" ? raw.sessionId : "",
    entries: Array.isArray(raw.entries) ? raw.entries : [],
  };
};

const writeStoredState = (state) => {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(ASK_STORAGE_KEY, JSON.stringify(state));
};

const clearStoredState = () => {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(ASK_STORAGE_KEY);
};

const readStoredFlowSession = (storageKey) => {
  if (typeof window === "undefined") return null;
  return safeParse(window.sessionStorage.getItem(storageKey), null);
};

const clearStoredFlowSession = (storageKey) => {
  if (typeof window === "undefined") return;
  window.sessionStorage.removeItem(storageKey);
};

const toResumeText = (...values) => {
  for (const value of values) {
    const text = String(value || "").trim();
    if (text) return text;
  }
  return "";
};

const formatResumePetType = (value) => {
  const text = String(value || "").trim();
  if (!text) return "";
  return text.charAt(0).toUpperCase() + text.slice(1);
};

const buildResumeCards = () => {
  const cards = [];
  const storedVetNearFlow = readStoredFlowSession(ASK_VET_NEAR_STANDALONE_KEY);
  const storedVideoFlow = readStoredFlowSession(ASK_VIDEO_CALL_STANDALONE_KEY);

  const vetLead = storedVetNearFlow?.lead || {};
  const vetPet = storedVetNearFlow?.pet || {};
  const hasVetResume = Boolean(
    toResumeText(
      vetLead.ownerName,
      vetLead.phone,
      vetLead.species,
      vetLead.area,
      vetLead.reason,
      vetPet.petName,
      vetPet.issue,
      vetPet.breed,
      vetPet.otherPetType
    ) ||
      storedVetNearFlow?.booking?.bookingId ||
      storedVetNearFlow?.booking?.petId ||
      storedVetNearFlow?.progress?.petDetailsSubmitted ||
      storedVetNearFlow?.progress?.paymentCompleted
  );

  if (hasVetResume) {
    cards.push({
      key: ASK_VET_NEAR_STANDALONE_KEY,
      route: "/vet-near-me-pet-details",
      title: "Previous booking found",
      subtitle: isVetNearPaymentReady(storedVetNearFlow)
        ? "Home vet visit is ready to resume from payment."
        : "Home vet visit details are saved and ready to continue.",
      summary: [
        toResumeText(vetPet.petName),
        toResumeText(vetLead.species, vetPet.otherPetType),
        toResumeText(vetLead.area),
      ]
        .filter(Boolean)
        .join(" • "),
    });
  }

  const videoSource = {
    ...(storedVideoFlow?.petDetails &&
    typeof storedVideoFlow.petDetails === "object"
      ? storedVideoFlow.petDetails
      : {}),
    ...(storedVideoFlow?.draft && typeof storedVideoFlow.draft === "object"
      ? storedVideoFlow.draft
      : {}),
  };
  const hasVideoResume = Boolean(
    toResumeText(
      videoSource.ownerName,
      videoSource.ownerMobile,
      videoSource.phone,
      videoSource.city,
      videoSource.name,
      videoSource.petName,
      videoSource.type,
      videoSource.species,
      videoSource.breed,
      videoSource.problemText
    ) ||
      storedVideoFlow?.paymentMeta?.user_id ||
      storedVideoFlow?.paymentMeta?.pet_id
  );

  if (hasVideoResume) {
    cards.push({
      key: ASK_VIDEO_CALL_STANDALONE_KEY,
      route: "/video-call-pet-details",
      title: "Previous booking found",
      subtitle: isVideoCallPaymentReady(storedVideoFlow)
        ? "Video consultation is ready to resume from payment."
        : "Video consultation details are saved and ready to continue.",
      summary: [
        toResumeText(videoSource.name, videoSource.petName),
        formatResumePetType(
          toResumeText(videoSource.type, videoSource.species, videoSource.petType)
        ),
        toResumeText(videoSource.city, videoSource.location),
      ]
        .filter(Boolean)
        .join(" • "),
    });
  }

  return cards;
};

const isVetNearPaymentReady = (value) =>
  Boolean(
    value?.progress?.petDetailsSubmitted &&
      value?.booking?.bookingId &&
      value?.booking?.userId &&
      value?.booking?.petId
  );

const isVideoCallPaymentReady = (value) =>
  Boolean(
    value?.petDetails &&
      (value?.paymentMeta?.user_id || value?.petDetails?.user_id) &&
      (value?.paymentMeta?.pet_id || value?.petDetails?.pet_id)
  );

const getSpeciesMeta = (species) => {
  const normalized = String(species || "")
    .trim()
    .toLowerCase();
  return (
    SPECIES_OPTIONS.find((option) => option.value === normalized) || {
      value: normalized || "pet",
      label: normalized ? normalized[0].toUpperCase() + normalized.slice(1) : "Pet",
      emoji: "🐾",
    }
  );
};

const getArcOffset = (value) => {
  const score = Math.max(0, Math.min(100, Number(value) || 0));
  return GAUGE_CIRCUMFERENCE - (score / 100) * GAUGE_CIRCUMFERENCE;
};

const extractErrorMessage = (error) => {
  const text = String(error?.message || "").trim();
  if (!text) {
    return "We could not complete the symptom check right now. Please try again.";
  }
  if (/failed to fetch/i.test(text)) {
    return "Network issue while contacting Snoutiq AI. Please check your connection and try again.";
  }
  return text;
};

const getThemeClass = (theme = "video") => `ask-theme-${theme}`;

const buildGoogleSearchUrl = (query) =>
  `https://www.google.com/search?q=${encodeURIComponent(query)}`;

const getPhoneDigits = (value) => String(value || "").replace(/\D/g, "");
const formatBackendPhone = (value) => {
  const digits = getPhoneDigits(value).slice(-10);
  return digits ? `+91${digits}` : "";
};

const formatBreedName = (breedKey, subBreed = null) => {
  const cap = (input) =>
    String(input || "")
      .split(/[-_\s/]+/)
      .filter(Boolean)
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");

  const base = cap(breedKey);
  if (!subBreed) return base;
  return `${cap(subBreed)} ${base}`;
};

function SearchableBreedField({
  value,
  options,
  loading,
  onChange,
  placeholder,
}) {
  const wrapperRef = useRef(null);
  const [open, setOpen] = useState(false);
  const normalizedValue = String(value || "").trim().toLowerCase();
  const filteredOptions = (normalizedValue
    ? options.filter((option) => option.toLowerCase().includes(normalizedValue))
    : options
  ).slice(0, 12);
  const showMenu = open && !loading && filteredOptions.length > 0;
  const showEmptyState =
    open && !loading && normalizedValue.length > 0 && filteredOptions.length === 0;

  useEffect(() => {
    if (!open) return undefined;

    const handlePointerDown = (event) => {
      if (wrapperRef.current?.contains(event.target)) return;
      setOpen(false);
    };

    document.addEventListener("mousedown", handlePointerDown);
    document.addEventListener("touchstart", handlePointerDown);

    return () => {
      document.removeEventListener("mousedown", handlePointerDown);
      document.removeEventListener("touchstart", handlePointerDown);
    };
  }, [open]);

  return (
    <div className="ask-search-select" ref={wrapperRef}>
      <div className={`ask-search-select-control${open ? " is-open" : ""}`}>
        <input
          type="text"
          value={value}
          onChange={(event) => {
            onChange(event.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
          onKeyDown={(event) => {
            if (event.key === "Escape") {
              setOpen(false);
            }

            if (
              event.key === "Enter" &&
              open &&
              filteredOptions.length === 1 &&
              filteredOptions[0].toLowerCase() === normalizedValue
            ) {
              setOpen(false);
            }
          }}
          placeholder={placeholder}
          autoComplete="off"
          role="combobox"
          aria-autocomplete="list"
          aria-expanded={showMenu || showEmptyState}
          aria-haspopup="listbox"
          aria-controls="ask-breed-search-results"
        />
        <button
          type="button"
          className="ask-search-select-toggle"
          onClick={() => setOpen((current) => !current)}
          aria-label={open ? "Hide breed suggestions" : "Show breed suggestions"}
        >
          <span className="ask-search-select-caret" aria-hidden="true" />
        </button>
      </div>

      {showMenu ? (
        <div
          className="ask-search-select-menu"
          id="ask-breed-search-results"
          role="listbox"
        >
          {filteredOptions.map((option) => (
            <button
              key={option}
              type="button"
              className={`ask-search-select-option${
                option.toLowerCase() === normalizedValue ? " is-selected" : ""
              }`}
              role="option"
              aria-selected={option.toLowerCase() === normalizedValue}
              onMouseDown={(event) => event.preventDefault()}
              onClick={() => {
                onChange(option);
                setOpen(false);
              }}
            >
              {option}
            </button>
          ))}
        </div>
      ) : null}

      {showEmptyState ? (
        <div className="ask-search-select-empty">
          No breed match found. You can keep typing manually.
        </div>
      ) : null}
    </div>
  );
}

const validateAskProfile = (profile, species) => {
  const next = sanitizeAskProfile(profile);
  const normalizedSpecies = String(species || "")
    .trim()
    .toLowerCase();
  const errors = {};

  if (!next.ownerName) {
    errors.ownerName = "Enter your name";
  }
  if (getPhoneDigits(next.phone).length < 10) {
    errors.phone = "Enter a valid phone number";
  }
  if (!next.petName) {
    errors.petName = "Enter your pet's name";
  }
  if (!normalizedSpecies) {
    errors.species = "Select pet type";
  }
  if (!next.breed) {
    errors.breed = "Enter your pet's breed";
  }
  if (!next.dob) {
    errors.dob = "Select date of birth";
  }
  if (!next.location || !hasProfileCoordinates(next)) {
    errors.location = "Tap 'Use current' to attach your location";
  }

  return errors;
};

const normalizeQuestionKey = (value) =>
  String(value || "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, " ");

const getAssessmentResponse = (assessment) =>
  assessment && typeof assessment === "object" && assessment.response
    ? assessment.response
    : {};

const resolveAssessmentMessage = (assessment) => {
  const response = getAssessmentResponse(assessment);
  return (
    response?.what_we_think_is_happening ||
    response?.message ||
    assessment?.message ||
    ""
  );
};

const resolveWatchItems = (assessment) => {
  const response = getAssessmentResponse(assessment);
  const watchItems = assessment?.what_to_watch ?? response?.what_to_watch;
  return Array.isArray(watchItems) ? watchItems.filter(Boolean) : [];
};

const resolveSafeToDoWhileWaiting = (assessment) => {
  const response = getAssessmentResponse(assessment);
  const safeItems =
    response?.safe_to_do_while_waiting ??
    assessment?.safe_to_do_while_waiting;
  return Array.isArray(safeItems) ? safeItems.filter(Boolean) : [];
};

const resolveFollowUpQuestion = (assessment) => {
  const raw =
    assessment?.follow_up_question ??
    getAssessmentResponse(assessment)?.follow_up_question;

  if (!raw || typeof raw !== "object") return null;

  const question = String(raw.question || "").trim();
  const options = Array.isArray(raw.options)
    ? raw.options
        .map((option) => String(option || "").trim())
        .filter(Boolean)
    : [];

  if (!question || options.length === 0) return null;

  return {
    label: String(raw.label || "One question to narrow this down").trim(),
    question,
    options,
  };
};

const resolveBeReadyToTellVet = (assessment) => {
  const response = getAssessmentResponse(assessment);
  return String(
    assessment?.be_ready_to_tell_vet || response?.be_ready_to_tell_vet || ""
  ).trim();
};

const resolveIndiaContext = (assessment) =>
  String(
    assessment?.triage_detail?.india_context || assessment?.india_context || ""
  ).trim();

const resolveImageObservation = (assessment) =>
  String(
    assessment?.triage_detail?.image_observation ||
      assessment?.image_observation ||
      ""
  ).trim();

const navigateToAskTarget = (navigate, target, options = {}) => {
  const route = String(target || "").trim();
  if (!route) return;

  if (route.includes("start=details")) {
    window.location.assign(route);
    return;
  }

  const [pathname, query = ""] = route.split("?");
  navigate(
    {
      pathname: pathname || "/",
      search: query ? `?${query}` : "",
    },
    options
  );
};

const buildAskPrefillState = ({ askProfile, species, entries, inputValue }) => {
  const profile = sanitizeAskProfile(askProfile);
  const locationLabel =
    profile.location ||
    (hasProfileCoordinates(profile) ? CURRENT_LOCATION_FALLBACK_LABEL : "");
  const lastUserMessage = [...(Array.isArray(entries) ? entries : [])]
    .reverse()
    .find(
      (entry) =>
        entry?.kind === "user" &&
        typeof entry?.message === "string" &&
        entry.message.trim()
    )?.message;
  const concern = String(lastUserMessage || inputValue || "").trim();

  return {
    ownerName: profile.ownerName,
    ownerMobile: profile.phone,
    phone: profile.phone,
    lat: profile.lat,
    long: profile.long,
    latitude: profile.lat,
    longitude: profile.long,
    petName: profile.petName,
    name: profile.petName,
    breed: profile.breed,
    dob: profile.dob,
    petDob: profile.dob,
    location: locationLabel,
    city: locationLabel,
    area: locationLabel,
    species,
    type: species,
    reason: concern,
    issue: concern,
    problemText: concern,
    dateOfBirth: profile.dob,
    birthDate: profile.dob,
    lead: {
      ownerName: profile.ownerName,
      phone: profile.phone,
      species,
      area: locationLabel,
      reason: concern,
      lat: profile.lat,
      long: profile.long,
    },
    pet: {
      petName: profile.petName,
      breed: profile.breed,
      dob: profile.dob,
      petDob: profile.dob,
      dateOfBirth: profile.dob,
      birthDate: profile.dob,
      species,
      type: species,
      issue: concern,
    },
  };
};

const buildAssessmentShareText = (assessment) =>
  assessment?.ui?.health_score?.share?.whatsapp_text ||
  "Check your pet on Snoutiq AI: https://snoutiq.com/ask";

const derivePossibleCauses = (assessment) => {
  const causes =
    assessment?.triage_detail?.possible_causes || assessment?.possible_causes;
  if (Array.isArray(causes) && causes.length > 0) {
    return causes.filter(Boolean);
  }

  const summary = String(
    getAssessmentResponse(assessment)?.diagnosis_summary || ""
  ).trim();
  const match = summary.match(/possible causes include (.+?)\.?$/i);
  if (!match) return [];

  return match[1]
    .split(/,| or /i)
    .map((value) => value.trim())
    .filter(Boolean);
};

const buildAssessmentCopy = (assessment) => {
  const title = assessment?.ui?.banner?.title || "Snoutiq AI assessment";
  const score = assessment?.health_score || assessment?.ui?.health_score?.value;
  const label = assessment?.ui?.health_score?.label || "";
  const message = resolveAssessmentMessage(assessment);
  const doNow = getAssessmentResponse(assessment)?.do_now || "";
  const safeToDoWhileWaiting = resolveSafeToDoWhileWaiting(assessment);
  const watch = resolveWatchItems(assessment);
  const beReadyToTellVet = resolveBeReadyToTellVet(assessment);

  return [
    title,
    score ? `Pet Health Score: ${score}/100${label ? ` (${label})` : ""}` : "",
    message,
    doNow ? `Do now: ${doNow}` : "",
    safeToDoWhileWaiting.length
      ? `Safe to do while waiting: ${safeToDoWhileWaiting.join(" | ")}`
      : "",
    watch.length ? `Watch for: ${watch.join(" | ")}` : "",
    beReadyToTellVet ? `Be ready to tell the vet: ${beReadyToTellVet}` : "",
    "https://snoutiq.com/ask",
  ]
    .filter(Boolean)
    .join("\n\n");
};

const buildFallbackSessionEntries = (payload) => {
  const history = payload?.state?.history;
  const pet = payload?.state?.pet || {};
  if (!Array.isArray(history) || history.length === 0) {
    return [];
  }

  return history.flatMap((turn, index) => [
    {
      id: `history-user-${index}`,
      kind: "user",
      message: turn?.user || "",
      time: turn?.ts || getTimeLabel(),
      species: pet?.species || "dog",
    },
    {
      id: `history-assistant-${index}`,
      kind: "note",
      message: turn?.assistant || "",
      time: turn?.ts || getTimeLabel(),
      routing: turn?.routing || "video_consult",
    },
  ]);
};

async function apiGetJson(path) {
  const res = await fetch(`${apiBaseUrl()}${path}`, {
    headers: { Accept: "application/json" },
  });
  let data = null;
  try {
    data = await res.json();
  } catch {
    data = null;
  }
  if (!res.ok) {
    const message =
      (data && (data.message || data.error)) || `HTTP ${res.status}`;
    throw new Error(message);
  }
  return data ?? {};
}

async function apiPostJson(path, body = {}) {
  return apiPost(path, body);
}

function IdleScreen({ species, onSpeciesSelect, onQuickStart }) {
  return (
    <div className="ask-idle">
      <div className="ask-idle-hero">
        <div className="ask-idle-icon">🐾</div>
        <h1 className="ask-idle-title">
          What&apos;s worrying
          <br />
          <em>your pet</em> today?
        </h1>
        <p className="ask-idle-subtitle">
          Describe symptoms in plain words. Takes 30 seconds. Free for all pet
          parents.
        </p>
        <div className="ask-trust-row">
          <span>100% free</span>
          <span>India-trained AI</span>
          <span>Vet-reviewed</span>
          <span>No signup</span>
        </div>
      </div>

      <div className="ask-section-label">Common symptoms - tap to start</div>
      <div className="ask-quick-grid">
        {QUICK_SYMPTOMS.map((item) => (
          <button
            key={item.key}
            type="button"
            className="ask-quick-button"
            onClick={() => onQuickStart(item)}
          >
            <span className="ask-quick-icon">{item.emoji}</span>
            <span className="ask-quick-copy">
              <strong>{item.title}</strong>
              <span>{item.subtitle}</span>
            </span>
          </button>
        ))}
      </div>

    </div>
  );
}

function IntakeModal({
  open,
  profile,
  species,
  pendingMessage,
  errors,
  submitting,
  breedOptions,
  breedLoading,
  breedError,
  locating,
  locationStatus,
  locationStatusType,
  onClose,
  onProfileChange,
  onSpeciesChange,
  onUseCurrentLocation,
  onSubmit,
}) {
  if (!open) return null;

  const speciesMeta = getSpeciesMeta(species);
  const petLabel = profile?.petName?.trim() || "your pet";
  const showBreedSuggestions = species === "dog" || species === "cat";
  const coordinatesReady = hasProfileCoordinates(profile);

  return (
    <div className="ask-modal-backdrop" role="presentation">
      <div
        className="ask-intake-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ask-intake-title"
      >
        <div className="ask-intake-header">
          <div className="ask-intake-brand">
            <img
              src={logo}
              alt="SnoutIQ"
              className="ask-intake-logo"
              width={110}
              height={20}
              loading="eager"
              decoding="async"
            />
            <span className="ask-intake-secure">Private • personalized answer</span>
          </div>
          <button
            type="button"
            className="ask-intake-close"
            onClick={onClose}
            aria-label="Close details form"
          >
            ×
          </button>
        </div>

        <div className="ask-intake-summary">
          <div className="ask-intake-summary-eyebrow">Before we answer</div>
          <h3 id="ask-intake-title">
            Tell us about {petLabel} so the first AI answer is more specific
          </h3>
          <p>{pendingMessage}</p>
        </div>

        <form className="ask-intake-body" onSubmit={onSubmit}>
          <div className="ask-intake-section-label">Pet parent details</div>
          <div className="ask-intake-grid ask-intake-grid-two">
            <label className="ask-intake-field">
              <span>Your name</span>
              <input
                type="text"
                value={profile.ownerName}
                onChange={(event) =>
                  onProfileChange("ownerName", event.target.value)
                }
                placeholder="Enter your name"
                autoComplete="name"
              />
              {errors.ownerName ? (
                <small className="ask-intake-error">{errors.ownerName}</small>
              ) : null}
            </label>

            <label className="ask-intake-field">
              <span>Phone number</span>
              <input
                type="tel"
                value={profile.phone}
                onChange={(event) => onProfileChange("phone", event.target.value)}
                placeholder="Enter your phone number"
                autoComplete="tel"
                inputMode="numeric"
                maxLength={10}
              />
              {errors.phone ? (
                <small className="ask-intake-error">{errors.phone}</small>
              ) : null}
            </label>
          </div>

          <div className="ask-intake-section-label">Pet details</div>
          <div className="ask-intake-grid ask-intake-grid-two">
            <label className="ask-intake-field">
              <span>Pet name</span>
              <input
                type="text"
                value={profile.petName}
                onChange={(event) => onProfileChange("petName", event.target.value)}
                placeholder="Enter your pet's name"
              />
              {errors.petName ? (
                <small className="ask-intake-error">{errors.petName}</small>
              ) : null}
            </label>

            <label className="ask-intake-field">
              <span>Pet type</span>
              <select
                value={species}
                onChange={(event) => onSpeciesChange(event.target.value)}
              >
                <option value="">Select pet type</option>
                {SPECIES_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              {errors.species ? (
                <small className="ask-intake-error">{errors.species}</small>
              ) : null}
            </label>
          </div>

          <div className="ask-intake-grid ask-intake-grid-two">
            <label className="ask-intake-field">
              <span>Breed</span>
              {showBreedSuggestions ? (
                <SearchableBreedField
                  key={`breed-${species || "empty"}`}
                  value={profile.breed}
                  options={breedOptions}
                  loading={breedLoading}
                  onChange={(nextValue) => onProfileChange("breed", nextValue)}
                  placeholder={
                    species === "dog"
                      ? "Search dog breed"
                      : species === "cat"
                        ? "Search cat breed"
                        : "Enter breed"
                  }
                />
              ) : (
                <input
                  type="text"
                  value={profile.breed}
                  onChange={(event) => onProfileChange("breed", event.target.value)}
                  placeholder="Enter breed"
                />
              )}
              {errors.breed ? (
                <small className="ask-intake-error">{errors.breed}</small>
              ) : null}
              {showBreedSuggestions ? (
                <small
                  className={`ask-intake-hint${
                    breedError ? " is-error" : ""
                  }`}
                >
                  {breedLoading
                    ? "Loading breeds..."
                    : breedError ||
                      "Search breeds or type manually if you do not see a match."}
                </small>
              ) : null}
            </label>

            <label className="ask-intake-field">
              <span>Date of birth</span>
              <input
                type="date"
                value={profile.dob}
                onChange={(event) => onProfileChange("dob", event.target.value)}
              />
              {errors.dob ? (
                <small className="ask-intake-error">{errors.dob}</small>
              ) : null}
            </label>
          </div>

          <label className="ask-intake-field">
            <span>Location</span>
            <div className="ask-intake-location-row">
              <input
                type="text"
                value={profile.location}
                placeholder="Tap 'Use current' to fetch location"
                autoComplete="off"
                readOnly
                disabled={submitting || locating}
              />
              <button
                type="button"
                className="ask-intake-location-button"
                onClick={onUseCurrentLocation}
                disabled={submitting || locating}
              >
                {locating ? "Locating..." : "Use current"}
              </button>
            </div>
            {errors.location ? (
              <small className="ask-intake-error">{errors.location}</small>
            ) : null}
            {locationStatus ? (
              <small
                className={`ask-intake-hint${
                  locationStatusType === "error"
                    ? " is-error"
                    : locationStatusType === "success"
                      ? " is-success"
                      : ""
                }`}
              >
                {locationStatus}
              </small>
            ) : coordinatesReady ? (
              <small className="ask-intake-hint is-success">
                Current location selected.
              </small>
            ) : (
              <small className="ask-intake-hint">
                Tap &quot;Use current&quot; to attach your location.
              </small>
            )}
          </label>

          <div className="ask-intake-note">
            <strong>{speciesMeta.label}</strong> profile is used to personalize the
            first answer, save the session, and make follow-up guidance more useful.
          </div>

          <div className="ask-intake-actions">
            <button
              type="button"
              className="ask-intake-secondary"
              onClick={onClose}
              disabled={submitting}
            >
              Not now
            </button>
            <button
              type="submit"
              className="ask-intake-primary"
              disabled={submitting}
            >
              {submitting ? "Checking symptoms..." : "Continue to AI answer"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function UserMessage({ entry }) {
  const speciesMeta = getSpeciesMeta(entry?.species);
  const attachment = entry?.attachment || null;
  const hasAttachmentPreview = Boolean(String(attachment?.previewUrl || "").trim());

  return (
    <div className="ask-message-group">
      <div className="ask-message-row ask-message-row-user">
        <div>
          <div className="ask-pet-pill">
            {speciesMeta.emoji} {speciesMeta.label} · India
          </div>
          <div className="ask-message-bubble ask-message-bubble-user">
            {entry?.message}
          </div>
          {hasAttachmentPreview ? (
            <div
              style={{
                marginTop: 8,
                display: "inline-flex",
                flexDirection: "column",
                gap: 6,
                alignItems: "flex-end",
              }}
            >
              <img
                src={attachment.previewUrl}
                alt={attachment?.name || "Uploaded pet image"}
                style={{
                  width: "min(220px, 62vw)",
                  maxHeight: 220,
                  borderRadius: 16,
                  border: "1px solid rgba(255,255,255,0.24)",
                  objectFit: "cover",
                  boxShadow: "0 6px 18px rgba(15, 23, 42, 0.18)",
                  background: "#fff",
                }}
              />
              <div
                style={{
                  color: "rgba(17, 24, 39, 0.56)",
                  fontSize: 11,
                  fontWeight: 600,
                }}
              >
                {[attachment?.name, formatBytes(attachment?.size)]
                  .filter(Boolean)
                  .join(" · ")}
              </div>
            </div>
          ) : null}
          <div className="ask-message-time">{entry?.time}</div>
        </div>
      </div>
    </div>
  );
}

function NoteMessage({ entry }) {
  return (
    <div className="ask-message-group">
      <div className="ask-message-row">
        <div className="ask-note-card">
          <div className="ask-note-eyebrow">
            Previous assessment · {String(entry?.routing || "video_consult").replace(/_/g, " ")}
          </div>
          <p>{entry?.message}</p>
        </div>
      </div>
    </div>
  );
}

function AssessmentCard({
  entry,
  entryAnchorId,
  onAction,
  onShare,
  onCopyAssessment,
  onCopyLink,
  onFollowUpAnswer,
  followUpPending,
}) {
  const assessment = entry?.payload || {};
  const ui = assessment?.ui || {};
  const banner = ui?.banner || {};
  const healthScore = ui?.health_score || {};
  const buttons = assessment?.buttons || {};
  const serviceCards = Array.isArray(ui?.service_cards) ? ui.service_cards : [];
  const theme = ui?.theme || "video";
  const possibleCauses = derivePossibleCauses(assessment);
  const watchItems = resolveWatchItems(assessment);
  const safeToDoWhileWaiting = resolveSafeToDoWhileWaiting(assessment);
  const redFlags = Array.isArray(assessment?.triage_detail?.red_flags_found)
    ? assessment.triage_detail.red_flags_found
    : [];
  const assessmentMessage = resolveAssessmentMessage(assessment);
  const indiaContext = resolveIndiaContext(assessment);
  const imageObservation = resolveImageObservation(assessment);
  const followUpQuestion = resolveFollowUpQuestion(assessment);
  const beReadyToTellVet = resolveBeReadyToTellVet(assessment);
  const answeredFollowUp = entry?.answeredFollowUp || null;
  const currentQuestionKey = normalizeQuestionKey(followUpQuestion?.question);
  const answeredQuestionKey = normalizeQuestionKey(answeredFollowUp?.question);
  const hasAnsweredCurrentQuestion = Boolean(
    currentQuestionKey &&
      answeredQuestionKey &&
      currentQuestionKey === answeredQuestionKey
  );
  const selectedFollowUpAnswer = String(
    followUpPending?.answer ||
      (hasAnsweredCurrentQuestion ? answeredFollowUp?.answer : "") ||
      ""
  ).trim();

  return (
    <div className="ask-message-group" id={entryAnchorId}>
      <div className="ask-message-row">
        <article className="ask-result-card">
          <div className={`ask-banner ${getThemeClass(theme)}`}>
            <div className="ask-banner-eyebrow">{banner?.eyebrow}</div>
            <h2 className="ask-banner-title">{banner?.title}</h2>
            <p className="ask-banner-subtitle">{banner?.subtitle}</p>
            {banner?.time_badge ? (
              <div className="ask-banner-badge">{banner.time_badge}</div>
            ) : null}
          </div>

          <div className="ask-health-block">
            <div className="ask-health-top">
              <div>
                <div className="ask-health-eyebrow">Pet Health Score</div>
                <div className="ask-health-score-row">
                  <div
                    className="ask-health-score"
                    style={{ color: healthScore?.color || "#1e88e5" }}
                  >
                    {healthScore?.value ?? assessment?.health_score ?? "--"}
                  </div>
                  <div className="ask-health-denom">/100</div>
                </div>
                <div
                  className="ask-health-label"
                  style={{ color: healthScore?.color || "#1e88e5" }}
                >
                  {healthScore?.label}
                </div>
                <div className="ask-health-subtitle">{healthScore?.subtitle}</div>
              </div>

              <div className="ask-gauge">
                <svg viewBox="0 0 80 80" aria-hidden="true">
                  <circle
                    cx="40"
                    cy="40"
                    r="32"
                    fill="none"
                    stroke="#f1f5f9"
                    strokeWidth="8"
                  />
                  <circle
                    cx="40"
                    cy="40"
                    r="32"
                    fill="none"
                    stroke={healthScore?.color || "#1e88e5"}
                    strokeWidth="8"
                    strokeDasharray={GAUGE_CIRCUMFERENCE}
                    strokeDashoffset={getArcOffset(healthScore?.value)}
                    strokeLinecap="round"
                    transform="rotate(-90 40 40)"
                  />
                  <text
                    x="40"
                    y="44"
                    textAnchor="middle"
                    fontSize="14"
                    fontWeight="900"
                    fill={healthScore?.color || "#1e88e5"}
                    fontFamily="Fraunces, serif"
                  >
                    {healthScore?.value ?? "--"}
                  </text>
                </svg>
              </div>
            </div>

            <div className="ask-share-row">
              <div className="ask-share-copy">
                <strong>{healthScore?.share?.title || "Share this score"}</strong>
                <span>
                  {healthScore?.share?.helper ||
                    "Help other pet parents find Snoutiq"}
                </span>
              </div>
              <button
                type="button"
                className="ask-whatsapp-button"
                onClick={() => onShare(assessment)}
              >
                Share on WhatsApp
              </button>
              <button
                type="button"
                className="ask-copy-link-button"
                onClick={onCopyLink}
              >
                Copy link
              </button>
            </div>
          </div>

          <div className="ask-result-body">
            <section className="ask-body-section">
              <div className="ask-body-label">What we think is happening</div>
              <div className="ask-assessment-block">
                <div className="ask-assessment-icon">🩺</div>
                <p>{assessmentMessage}</p>
              </div>
            </section>

            {getAssessmentResponse(assessment)?.do_now ? (
              <section className="ask-body-section">
                <div className="ask-do-now">
                  <div className="ask-do-now-icon">⚡</div>
                  <div>
                    <div className="ask-body-label ask-body-label-orange">
                      Do this right now
                    </div>
                    <p>{getAssessmentResponse(assessment).do_now}</p>
                  </div>
                </div>
              </section>
            ) : null}

            {indiaContext ? (
              <section className="ask-body-section">
                <div className="ask-india-note">
                  <span>🇮🇳</span>
                  <span>{indiaContext}</span>
                </div>
              </section>
            ) : null}

            {safeToDoWhileWaiting.length > 0 ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Safe to do while waiting</div>
                <div className="ask-waiting-list">
                  {safeToDoWhileWaiting.map((item, index) => (
                    <div
                      key={`${item}-${index}`}
                      className="ask-waiting-item"
                    >
                      <div className="ask-waiting-index">{index + 1}</div>
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}

            {imageObservation ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Image observation</div>
                <div className="ask-note-card">
                  <p>{imageObservation}</p>
                </div>
              </section>
            ) : null}

            {followUpQuestion ? (
              <section className="ask-body-section">
                <div className="ask-follow-card">
                  <div className="ask-body-label ask-body-label-purple">
                    {followUpQuestion.label}
                  </div>
                  <div className="ask-follow-question">
                    {followUpQuestion.question}
                  </div>
                  <div className="ask-follow-options">
                    {followUpQuestion.options.map((option) => (
                      <button
                        key={option}
                        type="button"
                        className={`ask-follow-option${
                          selectedFollowUpAnswer === option ? " is-selected" : ""
                        }`}
                        onClick={() =>
                          onFollowUpAnswer(entry, followUpQuestion, option)
                        }
                        disabled={
                          Boolean(followUpPending) || hasAnsweredCurrentQuestion
                        }
                      >
                        {option}
                      </button>
                    ))}
                  </div>

                  {followUpPending ? (
                    <div className="ask-follow-updating">
                      <div className="ask-typing-bubble">
                        <span />
                        <span />
                        <span />
                      </div>
                      <span>Updating assessment with your answer...</span>
                    </div>
                  ) : null}

                  {hasAnsweredCurrentQuestion ? (
                    <>
                      {entry?.revisedAssessment || assessment?.revised_assessment ? (
                        <div className="ask-revised-badge">
                          Assessment updated based on your answer
                        </div>
                      ) : null}
                      <div className="ask-follow-answer">
                        Selected answer: <strong>{answeredFollowUp.answer}</strong>
                      </div>
                    </>
                  ) : null}
                </div>
              </section>
            ) : null}

            {watchItems.length > 0 ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Watch for</div>
                <div className="ask-watch-list">
                  {watchItems.map((item, index) => (
                    <div
                      key={`${item}-${index}`}
                      className={`ask-watch-item${
                        index === watchItems.length - 1 &&
                        assessment?.routing === "emergency"
                          ? " is-danger"
                          : index === 0
                            ? " is-warning"
                            : ""
                      }`}
                    >
                      <span className="ask-watch-icon">
                        {index === watchItems.length - 1 &&
                        assessment?.routing === "emergency"
                          ? "🔴"
                          : index === 0
                            ? "🟡"
                            : "⚪"}
                      </span>
                      <p>{item}</p>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}

            {possibleCauses.length > 0 ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Most likely causes</div>
                <div className="ask-pill-row">
                  {possibleCauses.map((cause, index) => (
                    <span key={`${cause}-${index}`} className="ask-info-pill">
                      {cause}
                    </span>
                  ))}
                </div>
              </section>
            ) : null}

            {redFlags.length > 0 ? (
              <section className="ask-body-section">
                <div className="ask-body-label">Red flags found</div>
                <div className="ask-pill-row">
                  {redFlags.map((flag, index) => (
                    <span
                      key={`${flag}-${index}`}
                      className="ask-info-pill ask-info-pill-danger"
                    >
                      {flag}
                    </span>
                  ))}
                </div>
              </section>
            ) : null}

            {beReadyToTellVet ? (
              <section className="ask-body-section">
                <div className="ask-vet-ready">
                  <div>
                    <div className="ask-body-label ask-body-label-blue">
                      Be ready to tell the vet
                    </div>
                    <p>{beReadyToTellVet}</p>
                  </div>
                </div>
              </section>
            ) : null}

            {assessment?.vet_summary ? (
              <section className="ask-body-section">
                <div className="ask-vet-summary">
                  <div>
                    <div className="ask-body-label ask-body-label-blue">
                      Vet handover summary
                    </div>
                    <p>This is the summary a vet can skim quickly if you share it.</p>
                  </div>
                  <button
                    type="button"
                    className="ask-copy-summary-button"
                    onClick={() => onCopyAssessment(assessment)}
                  >
                    Copy summary
                  </button>
                </div>
              </section>
            ) : null}
          </div>

          <div className="ask-disclaimer">
            <div className="ask-disclaimer-icon">🤖</div>
            <div>
              <div className="ask-disclaimer-label">Snoutiq AI - triage only</div>
              <p>
                AI-generated guidance trained on veterinary cases across India,
                reviewed for clinical accuracy. Not a diagnosis. Always follow a
                licensed vet&apos;s advice.
              </p>
            </div>
          </div>
              {serviceCards.length > 0 ? (
            <div className="ask-service-list">
              {serviceCards.map((card, index) => (
                <article
                  key={`${card?.title || "card"}-${index}`}
                  className={`ask-service-card${
                    card?.featured ? " is-featured" : ""
                  }`}
                >
                  <div className="ask-service-header">
                    {card?.badge ? (
                      <div
                        className={`ask-service-badge ask-service-badge-${
                          card?.badge_variant || "default"
                        }`}
                      >
                        {card.badge}
                      </div>
                    ) : (
                      <span />
                    )}
                  </div>
                  <div className="ask-service-title">{card?.title}</div>
                  <div className="ask-service-price-row">
                    <div className={`ask-service-price ask-service-price-${card?.theme || "video"}`}>
                      {card?.price}
                    </div>
                    {card?.orig_price ? (
                      <div className="ask-service-orig-price">{card.orig_price}</div>
                    ) : null}
                  </div>
                  {card?.guarantee ? (
                    <div className="ask-service-guarantee">{card.guarantee}</div>
                  ) : null}
                  <div className="ask-service-bullets">
                    {(card?.bullets || []).map((bullet, bulletIndex) => (
                      <div
                        key={`${bullet}-${bulletIndex}`}
                        className="ask-service-bullet"
                      >
                        {bullet}
                      </div>
                    ))}
                  </div>
                  <button
                    type="button"
                    className={`ask-service-button ask-service-button-${card?.theme || "video"}`}
                    onClick={() => onAction(card?.cta || {}, assessment)}
                  >
                    {card?.cta?.label || "Continue"}
                  </button>
                </article>
              ))}
            </div>
          ) : null}
        </article>
      </div>
    </div>
  );
}

export default function AskPage() {
  const navigate = useNavigate();
  const textareaRef = useRef(null);
  const attachmentInputRef = useRef(null);
  const attachmentUrlsRef = useRef(new Set());
  const hydratedRef = useRef(false);
  const breedCacheRef = useRef({});
  const locationLookupRef = useRef(0);
  const pendingScrollEntryIdRef = useRef(null);
  const [species, setSpecies] = useState("");
  const [sessionId, setSessionId] = useState("");
  const [entries, setEntries] = useState([]);
  const [inputValue, setInputValue] = useState("");
  const [pendingAttachment, setPendingAttachment] = useState(null);
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");
  const [toastMessage, setToastMessage] = useState("");
  const [checksToday, setChecksToday] = useState(0);
  const [followUpPending, setFollowUpPending] = useState(null);
  const [askProfile, setAskProfile] = useState(DEFAULT_ASK_PROFILE);
  const [intakeOpen, setIntakeOpen] = useState(false);
  const [intakeErrors, setIntakeErrors] = useState({});
  const [pendingInitialRequest, setPendingInitialRequest] = useState(null);
  const [breedOptions, setBreedOptions] = useState([]);
  const [breedLoading, setBreedLoading] = useState(false);
  const [breedError, setBreedError] = useState("");
  const [flowModal, setFlowModal] = useState(null);
  const [resumeCards, setResumeCards] = useState([]);
  const [locating, setLocating] = useState(false);
  const [locationStatus, setLocationStatus] = useState({
    type: "",
    text: "",
  });

  useEffect(() => {
    const storedState = readStoredState();
    const storedUiState = readStoredUiState();
    const hasStoredConversation = Boolean(
      storedState?.sessionId || storedState?.entries?.length
    );
    if (hasStoredConversation) {
      setSpecies(storedState.species || "");
      setSessionId(storedState.sessionId || "");
      setEntries(storedState.entries || []);
    }
    setAskProfile(hasStoredConversation ? readStoredProfile() : DEFAULT_ASK_PROFILE);
    if (storedUiState) {
      setInputValue(storedUiState.inputValue || "");
      setPendingInitialRequest(storedUiState.pendingInitialRequest || null);
      setIntakeOpen(Boolean(storedUiState.intakeOpen));
      if (!hasStoredConversation && storedUiState.pendingInitialRequest?.species) {
        setSpecies(storedUiState.pendingInitialRequest.species);
      }
    }
    setChecksToday(readDailyUsage());
    hydratedRef.current = true;
  }, []);

  useEffect(() => {
    if (!hydratedRef.current) return;
    writeStoredState({
      species,
      sessionId,
      entries: serializeEntriesForStorage(entries),
    });
  }, [entries, sessionId, species]);

  useEffect(() => {
    writeDailyUsage(checksToday);
  }, [checksToday]);

  useEffect(() => {
    if (!hydratedRef.current) return;
    writeStoredProfile(askProfile);
  }, [askProfile]);

  useEffect(() => {
    if (!hydratedRef.current) return;
    writeStoredUiState({ inputValue, intakeOpen, pendingInitialRequest });
  }, [inputValue, intakeOpen, pendingInitialRequest]);

  useEffect(() => {
    setResumeCards(buildResumeCards());
  }, [flowModal]);

  useEffect(() => {
    if (!toastMessage) return undefined;
    const timer = window.setTimeout(() => setToastMessage(""), 2200);
    return () => window.clearTimeout(timer);
  }, [toastMessage]);

  useEffect(() => {
    const entryId = pendingScrollEntryIdRef.current;
    if (!entryId) return undefined;

    const run = () => {
      const node = document.getElementById(`ask-entry-${entryId}`);
      if (!node) return;

      node.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });

      pendingScrollEntryIdRef.current = null;
    };

    const frame = window.requestAnimationFrame(run);
    return () => window.cancelAnimationFrame(frame);
  }, [entries]);

  useEffect(() => {
    if (typeof document === "undefined" || (!intakeOpen && !flowModal)) return undefined;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [flowModal, intakeOpen]);

  useEffect(() => {
    if (!flowModal) return undefined;
    const handleEscape = (event) => {
      if (event.key === "Escape") {
        setFlowModal(null);
      }
    };
    window.addEventListener("keydown", handleEscape);
    return () => window.removeEventListener("keydown", handleEscape);
  }, [flowModal]);

  useEffect(
    () => () => {
      attachmentUrlsRef.current.forEach((previewUrl) =>
        revokeBlobPreviewUrl(previewUrl)
      );
      attachmentUrlsRef.current.clear();
    },
    []
  );

  useEffect(() => {
    if (!intakeOpen) return undefined;
    if (species !== "dog" && species !== "cat") {
      setBreedOptions([]);
      setBreedLoading(false);
      setBreedError("");
      return undefined;
    }

    const cached = breedCacheRef.current[species];
    if (cached) {
      setBreedOptions(cached.options || []);
      setBreedError(cached.error || "");
      setBreedLoading(false);
      return undefined;
    }

    let active = true;
    const fetchBreedOptions = async () => {
      setBreedLoading(true);
      setBreedError("");

      try {
        if (species === "dog") {
          const response = await apiGetJson("/api/dog-breeds/all");
          const list = [];

          Object.keys(response?.breeds || {}).forEach((breedKey) => {
            const subBreeds = Array.isArray(response?.breeds?.[breedKey])
              ? response.breeds[breedKey]
              : [];

            if (subBreeds.length === 0) {
              list.push(formatBreedName(breedKey));
              return;
            }

            list.push(formatBreedName(breedKey));
            subBreeds.forEach((subBreed) => {
              list.push(formatBreedName(breedKey, subBreed));
            });
          });

          const options = Array.from(
            new Set([...list.filter(Boolean), "Mixed Breed", "Other"])
          ).sort((left, right) => left.localeCompare(right));

          if (!active) return;
          breedCacheRef.current[species] = { options, error: "" };
          setBreedOptions(options);
          setBreedError("");
          return;
        }

        const response = await apiGetJson("/api/cat-breeds/with-indian");
        const options = Array.from(
          new Set(
            [
              ...(Array.isArray(response?.data) ? response.data : []).map(
                (item) => item?.name || item?.id || ""
              ),
              "Mixed / Other",
            ].filter(Boolean)
          )
        ).sort((left, right) => left.localeCompare(right));

        if (!active) return;
        breedCacheRef.current[species] = { options, error: "" };
        setBreedOptions(options);
        setBreedError("");
      } catch {
        if (!active) return;
        const fallback =
          species === "dog"
            ? ["Mixed Breed", "Other"]
            : ["Indian Cat", "Mixed / Other"];
        const errorText =
          species === "dog"
            ? "Could not load dog breeds. You can still type manually."
            : "Could not load cat breeds. You can still type manually.";

        breedCacheRef.current[species] = { options: fallback, error: errorText };
        setBreedOptions(fallback);
        setBreedError(errorText);
      } finally {
        if (active) {
          setBreedLoading(false);
        }
      }
    };

    fetchBreedOptions();

    return () => {
      active = false;
    };
  }, [intakeOpen, species]);

  useEffect(() => {
    if (!sessionId || entries.length > 0 || !hydratedRef.current) return;

    let active = true;

    apiGetJson(`/api/symptom-session/${encodeURIComponent(sessionId)}`)
      .then((payload) => {
        if (!active) return;
        const fallbackEntries = buildFallbackSessionEntries(payload);
        if (fallbackEntries.length > 0) {
          setEntries(fallbackEntries);
        }
      })
      .catch(() => {
        if (!active) return;
        setSessionId("");
        clearStoredState();
      });

    return () => {
      active = false;
    };
  }, [entries.length, sessionId]);

  const freeChecksLeft = Math.max(0, FREE_CHECK_LIMIT - checksToday);

  const resizeTextarea = () => {
    const node = textareaRef.current;
    if (!node) return;
    node.style.height = "auto";
    node.style.height = `${Math.min(node.scrollHeight, 100)}px`;
  };

  useEffect(() => {
    resizeTextarea();
  }, [inputValue]);

  const clearPendingAttachment = ({ revokePreview = true } = {}) => {
    setPendingAttachment((current) => {
      if (revokePreview && current?.previewUrl) {
        revokeBlobPreviewUrl(current.previewUrl);
        attachmentUrlsRef.current.delete(current.previewUrl);
      }
      return null;
    });
    if (attachmentInputRef.current) {
      attachmentInputRef.current.value = "";
    }
  };

  const revokeAllAttachmentPreviews = () => {
    attachmentUrlsRef.current.forEach((previewUrl) =>
      revokeBlobPreviewUrl(previewUrl)
    );
    attachmentUrlsRef.current.clear();
    if (attachmentInputRef.current) {
      attachmentInputRef.current.value = "";
    }
  };

  const handleOpenAttachmentPicker = () => {
    if (loading || intakeOpen) return;
    attachmentInputRef.current?.click();
  };

  const handleAttachmentChange = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = "";
    if (!file) return;

    if (!String(file.type || "").startsWith("image/")) {
      setToastMessage("Please choose an image file.");
      return;
    }

    if (file.size > MAX_ATTACHMENT_BYTES) {
      setToastMessage("Please choose an image under 5 MB.");
      return;
    }

    try {
      const dataUrl = await readFileAsDataUrl(file);
      const [, base64 = ""] = dataUrl.split(",", 2);
      if (!base64) {
        throw new Error("Empty image payload.");
      }

      const previewUrl =
        typeof URL !== "undefined" ? URL.createObjectURL(file) : "";
      if (previewUrl) {
        attachmentUrlsRef.current.add(previewUrl);
      }

      clearPendingAttachment({ revokePreview: true });
      setPendingAttachment({
        base64,
        mime: file.type || "image/jpeg",
        name: file.name || "Attached image",
        size: file.size || 0,
        previewUrl,
      });
      setErrorMessage("");
      setToastMessage("Image attached");
    } catch {
      setToastMessage("Unable to read this image. Please try a different file.");
    }
  };

  const pushUserEntry = (messageText, nextSpecies, attachment = null) => ({
    id: `user-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    kind: "user",
    message: messageText,
    time: getTimeLabel(),
    species: nextSpecies,
    attachment: attachment
      ? {
          name: attachment.name || "Attached image",
          mime: attachment.mime || "image/jpeg",
          size: attachment.size || 0,
          previewUrl: attachment.previewUrl || "",
        }
      : null,
  });

  const pushAssessmentEntry = (payload, overrides = {}) => ({
    id:
      overrides.id ||
      `assessment-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    kind: "assessment",
    payload,
    time: overrides.time || getTimeLabel(),
    answeredFollowUp: overrides.answeredFollowUp || null,
    revisedAssessment: Boolean(
      overrides.revisedAssessment ?? payload?.revised_assessment
    ),
  });

  const buildInitialSymptomPayload = ({
    messageText,
    speciesValue,
    profileValue,
  }) => {
    const nextProfile = sanitizeAskProfile(profileValue);
    const latitude = parseCoordinate(nextProfile.lat);
    const longitude = parseCoordinate(nextProfile.long);
    const hasCoordinates = latitude !== null && longitude !== null;
    const backendPhone = formatBackendPhone(nextProfile.phone);
    const user = {};
    const pets = {};
    const locationValue =
      nextProfile.location ||
      (hasCoordinates ? CURRENT_LOCATION_FALLBACK_LABEL : undefined);
    const payload = {
      message: messageText,
      species: speciesValue,
      type: speciesValue,
      phone: backendPhone || undefined,
      location: locationValue,
      owner_name: nextProfile.ownerName || undefined,
      pet_name: nextProfile.petName || undefined,
      breed: nextProfile.breed || undefined,
      dob: nextProfile.dob || undefined,
      lat: latitude ?? undefined,
      long: longitude ?? undefined,
    };

    if (nextProfile.ownerName) {
      user.name = nextProfile.ownerName;
    }
    if (backendPhone) {
      user.phone = backendPhone;
    }
    if (latitude !== null) {
      user.lat = latitude;
    }
    if (longitude !== null) {
      user.long = longitude;
    }
    if (nextProfile.petName) {
      pets.pet_name = nextProfile.petName;
      pets.name = nextProfile.petName;
    }
    if (nextProfile.breed) {
      pets.breed = nextProfile.breed;
    }
    if (nextProfile.dob) {
      pets.dob = nextProfile.dob;
    }
    if (speciesValue) {
      pets.type = speciesValue;
      pets.species = speciesValue;
    }

    if (Object.keys(user).length > 0) {
      payload.user = user;
    }
    if (Object.keys(pets).length > 0) {
      payload.pets = pets;
    }

    return payload;
  };

  const sendAssessmentRequest = async ({
    messageText,
    displayText = messageText,
    speciesValue,
    profileValue = askProfile,
    attachment = pendingAttachment,
  }) => {
    const isFollowup = Boolean(sessionId);

    if ((!messageText && !attachment) || loading) return;

    setErrorMessage("");
    setToastMessage("");
    setFollowUpPending(null);
    const entryAttachment = attachment
      ? {
          name: attachment.name,
          mime: attachment.mime,
          size: attachment.size,
          previewUrl: attachment.previewUrl,
        }
      : null;
    setEntries((current) => [
      ...current,
      pushUserEntry(displayText, speciesValue, entryAttachment),
    ]);
    setInputValue("");
    if (attachment) {
      clearPendingAttachment({ revokePreview: false });
    }
    setLoading(true);

    try {
      const imagePayload = buildImagePayload(attachment);
      const payload = isFollowup
        ? await apiPostJson("/api/symptom-followup", {
            session_id: sessionId,
            message: messageText,
            ...imagePayload,
          })
        : await apiPostJson(
            "/api/symptom-check",
            {
              ...buildInitialSymptomPayload({
                messageText,
                speciesValue,
                profileValue,
              }),
              ...imagePayload,
            }
          );

      setSessionId(payload?.session_id || "");
      const nextAssessmentEntry = pushAssessmentEntry(payload);
      pendingScrollEntryIdRef.current = nextAssessmentEntry.id;
      setEntries((current) => [...current, nextAssessmentEntry]);

      if (!isFollowup) {
        setChecksToday((count) => count + 1);
      }
    } catch (error) {
      setErrorMessage(extractErrorMessage(error));
    } finally {
      setLoading(false);
    }
  };

  const handleSend = async ({ message, nextSpecies } = {}) => {
    const rawMessageText = String(message ?? inputValue).trim();
    const speciesValue = String(nextSpecies || species || "")
      .trim()
      .toLowerCase();
    const attachment = pendingAttachment;
    const hasAttachment = Boolean(attachment?.base64);
    const isFollowup = Boolean(sessionId);
    const messageText =
      rawMessageText || (hasAttachment ? defaultImageMessage(isFollowup) : "");
    const displayText = rawMessageText || defaultImageVisualMessage;

    if ((!messageText && !hasAttachment) || loading) return;

    if (!sessionId) {
      setSpecies(speciesValue);
      setInputValue(rawMessageText);
      setPendingInitialRequest({
        message: messageText,
        displayText,
        species: speciesValue,
      });
      setIntakeErrors({});
      setIntakeOpen(true);
      return;
    }

    await sendAssessmentRequest({
      messageText,
      displayText,
      speciesValue,
      attachment,
    });
  };

  const handleQuickStart = (item) => {
    setSpecies(item.species);
    handleSend({ message: item.message, nextSpecies: item.species });
  };

  const handleIntakeProfileChange = (field, value) => {
    if (field === "location") {
      locationLookupRef.current += 1;
    }
    const nextValue =
      field === "phone" ? normalizePhoneInput(value) : value;
    setAskProfile((current) => ({
      ...current,
      [field]: nextValue,
    }));
    setIntakeErrors((current) => {
      if (!current[field]) return current;
      const next = { ...current };
      delete next[field];
      return next;
    });
    if (field === "location") {
      setLocationStatus({ type: "", text: "" });
    }
  };

  const handleIntakeSpeciesChange = (value) => {
    const nextSpecies = String(value || "").trim().toLowerCase();

    setSpecies(nextSpecies);

    setAskProfile((current) => ({
      ...current,
      breed: "",
    }));

    setBreedOptions([]);
    setBreedError("");
    setBreedLoading(false);

    setIntakeErrors((current) => {
      const next = { ...current };
      delete next.species;
      delete next.breed;
      return next;
    });
  };

  const handleUseCurrentLocation = () => {
    if (loading || locating) return;

    if (
      typeof window === "undefined" ||
      !window.navigator ||
      !window.navigator.geolocation
    ) {
      setLocationStatus({
        type: "error",
        text: "Current location is not supported on this device.",
      });
      return;
    }

    setLocating(true);
    setLocationStatus({
      type: "info",
      text: "Fetching current location...",
    });

    window.navigator.geolocation.getCurrentPosition(
      (position) => {
        const latitude = parseCoordinate(position?.coords?.latitude);
        const longitude = parseCoordinate(position?.coords?.longitude);
        const lookupId = Date.now();

        if (latitude === null || longitude === null) {
          setLocationStatus({
            type: "error",
            text: "Could not read current location. Please tap 'Use current' again.",
          });
          setLocating(false);
          return;
        }

        locationLookupRef.current = lookupId;
        setAskProfile((current) => {
          return {
            ...current,
            lat: latitude,
            long: longitude,
            location: shouldAutoFillDetectedLocation(current?.location)
              ? CURRENT_LOCATION_FALLBACK_LABEL
              : current.location,
          };
        });
        setIntakeErrors((current) => {
          if (!current.location) return current;
          const next = { ...current };
          delete next.location;
          return next;
        });
        setLocationStatus({
          type: "success",
          text: "Current location selected.",
        });
        setLocating(false);

        resolveDetectedLocationLabel(latitude, longitude)
          .then((resolvedLocation) => {
            if (!resolvedLocation || locationLookupRef.current !== lookupId) {
              return;
            }

            setAskProfile((current) => {
              const currentLat = parseCoordinate(current?.lat);
              const currentLong = parseCoordinate(current?.long);
              if (currentLat !== latitude || currentLong !== longitude) {
                return current;
              }
              if (!shouldAutoFillDetectedLocation(current?.location)) {
                return current;
              }

              return {
                ...current,
                location: resolvedLocation,
              };
            });
            setLocationStatus({
              type: "success",
              text: "Current location selected.",
            });
          })
          .catch(() => {});
      },
      () => {
        setLocationStatus({
          type: "error",
          text: "Could not read current location. Please tap 'Use current' again.",
        });
        setLocating(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 300000,
      }
    );
  };

  const handleCloseIntake = () => {
    if (loading) return;
    locationLookupRef.current += 1;
    setIntakeOpen(false);
    setPendingInitialRequest(null);
    setIntakeErrors({});
    setLocationStatus({ type: "", text: "" });
  };

  const handleIntakeSubmit = async (event) => {
    event.preventDefault();

    if (!pendingInitialRequest || loading) return;

    const nextErrors = validateAskProfile(askProfile, species);
    if (Object.keys(nextErrors).length > 0) {
      setIntakeErrors(nextErrors);
      return;
    }

    const request = pendingInitialRequest;
    setIntakeErrors({});
    setIntakeOpen(false);
    setPendingInitialRequest(null);

    await sendAssessmentRequest({
      messageText: request.message,
      displayText: request.displayText || request.message,
      speciesValue: species,
      profileValue: askProfile,
    });
  };

  const handleFollowUpAnswer = async (entry, questionConfig, answer) => {
    const question = String(questionConfig?.question || "").trim();
    const answerText = String(answer || "").trim();
    const attachment = pendingAttachment;

    if (!entry?.id || !sessionId || !question || !answerText || loading) return;

    setErrorMessage("");
    setToastMessage("");
    setFollowUpPending({
      entryId: entry.id,
      question,
      answer: answerText,
    });
    setLoading(true);

    try {
      const payload = await apiPostJson("/api/symptom-answer", {
        session_id: sessionId,
        question,
        answer: answerText,
        ...buildImagePayload(attachment),
      });

      if (attachment) {
        clearPendingAttachment({ revokePreview: true });
      }
      setSessionId(payload?.session_id || sessionId);
      pendingScrollEntryIdRef.current = entry.id;
      setEntries((current) =>
        current.map((currentEntry) =>
          currentEntry.id === entry.id
            ? pushAssessmentEntry(payload, {
                id: currentEntry.id,
                time: getTimeLabel(),
                answeredFollowUp: { question, answer: answerText },
                revisedAssessment: payload?.revised_assessment,
              })
            : currentEntry
        )
      );
    } catch (error) {
      setErrorMessage(extractErrorMessage(error));
    } finally {
      setLoading(false);
      setFollowUpPending(null);
    }
  };

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(ASK_CANONICAL);
      setToastMessage("Link copied");
    } catch {
      setToastMessage("Copy failed");
    }
  };

  const handleCopyAssessment = async (assessment) => {
    try {
      await navigator.clipboard.writeText(buildAssessmentCopy(assessment));
      setToastMessage("Summary copied");
    } catch {
      setToastMessage("Copy failed");
    }
  };

  const handleShare = (assessment) => {
    const text = encodeURIComponent(buildAssessmentShareText(assessment));
    window.open(`https://wa.me/?text=${text}`, "_blank", "noopener,noreferrer");
  };

  const getAskRouteOptions = (target) => {
    const route = String(target || "").trim();
    if (!route.endsWith("-pet-details")) return undefined;
    return {
      state: {
        prefill: buildAskPrefillState({
          askProfile,
          species,
          entries,
          inputValue,
        }),
      },
    };
  };

  const openAskFlowModal = (target) => {
    const route = String(target || "").trim();
    const modalKind = ASK_FLOW_MODAL_KIND_MAP[route];
    if (!modalKind) return false;

    const prefill = buildAskPrefillState({
      askProfile,
      species,
      entries,
      inputValue,
    });

    if (route === "/vet-near-me-pet-details") {
      const storedVetNearFlow = readStoredFlowSession(ASK_VET_NEAR_STANDALONE_KEY);
      setFlowModal({
        kind: isVetNearPaymentReady(storedVetNearFlow)
          ? "vet-payment"
          : "vet-pet-details",
        payload: isVetNearPaymentReady(storedVetNearFlow) ? null : { prefill },
      });
      return true;
    }

    if (route === "/video-call-pet-details") {
      const storedVideoCallFlow = readStoredFlowSession(
        ASK_VIDEO_CALL_STANDALONE_KEY
      );
      setFlowModal({
        kind: isVideoCallPaymentReady(storedVideoCallFlow)
          ? "video-payment"
          : "video-pet-details",
        payload: isVideoCallPaymentReady(storedVideoCallFlow)
          ? null
          : { prefill },
      });
      return true;
    }

    setFlowModal({ kind: modalKind, payload: null });
    return true;
  };

  const handleResumeBooking = (targetRoute) => {
    openAskFlowModal(targetRoute);
  };

  const handleClearResumeBooking = (storageKey) => {
    clearStoredFlowSession(storageKey);
    setResumeCards(buildResumeCards());
    setToastMessage("Saved booking cleared");
  };

  const handleAction = (action, assessment) => {
    const type = String(action?.type || "").trim();
    const deeplink = String(action?.deeplink || "").trim();

    if (type === "info") {
      handleCopyAssessment(assessment);
      return;
    }

    const route = CTA_ROUTE_MAP[type];
    if (route) {
      if (openAskFlowModal(route)) return;
      navigateToAskTarget(navigate, route, getAskRouteOptions(route));
      return;
    }

    const deeplinkRoute = DEEPLINK_ROUTE_MAP[deeplink];
    if (deeplinkRoute) {
      if (openAskFlowModal(deeplinkRoute)) return;
      navigateToAskTarget(navigate, deeplinkRoute, getAskRouteOptions(deeplinkRoute));
      return;
    }

    if (deeplink.startsWith("snoutiq://")) {
      window.location.assign(deeplink);
    }
  };

  const handleReset = async () => {
    const confirmed = window.confirm(
      "Start a fresh symptom check? This will clear the current conversation."
    );
    if (!confirmed) return;

    if (sessionId) {
      try {
        await apiPostJson(
          `/api/symptom-session/${encodeURIComponent(sessionId)}/reset`
        );
      } catch {
        // Clear local state even if the backend reset call fails.
      }
    }

    setEntries([]);
    clearPendingAttachment({ revokePreview: true });
    revokeAllAttachmentPreviews();
    setSessionId("");
    setSpecies("");
    setInputValue("");
    setErrorMessage("");
    setFollowUpPending(null);
    setAskProfile(DEFAULT_ASK_PROFILE);
    setPendingInitialRequest(null);
    setIntakeOpen(false);
    setIntakeErrors({});
    setLocating(false);
    locationLookupRef.current += 1;
    setLocationStatus({ type: "", text: "" });
    clearStoredState();
    clearStoredProfile();
    clearStoredUiState();
    setToastMessage("Started fresh");
  };

  const navBadgeText =
    freeChecksLeft > 0
      ? `${freeChecksLeft} free checks left`
      : "Free checks used today";
  const canSendMessage = Boolean(String(inputValue || "").trim() || pendingAttachment);

  let flowModalContent = null;
  if (flowModal?.kind === "vet-pet-details") {
    flowModalContent = (
      <VetNearPetDetails
        initialState={flowModal.payload}
        onBack={() => setFlowModal(null)}
        onContinue={() =>
          setFlowModal({
            kind: "vet-payment",
            payload: null,
          })
        }
      />
    );
  } else if (flowModal?.kind === "vet-payment") {
    flowModalContent = (
      <VetNearPayment
        onBack={() =>
          setFlowModal({
            kind: "vet-pet-details",
            payload: null,
          })
        }
        onPay={() => {
          clearStoredFlowSession(ASK_VET_NEAR_STANDALONE_KEY);
          setResumeCards(buildResumeCards());
          setFlowModal(null);
          setToastMessage("Payment successful");
        }}
      />
    );
  } else if (flowModal?.kind === "video-pet-details") {
    flowModalContent = (
      <VideoCallPetDetails
        initialState={flowModal.payload}
        onSubmit={() =>
          setFlowModal({
            kind: "video-payment",
            payload: null,
          })
        }
      />
    );
  } else if (flowModal?.kind === "video-payment") {
    flowModalContent = (
      <VideoCallPayment
        onBack={() =>
          setFlowModal({
            kind: "video-pet-details",
            payload: null,
          })
        }
        onPay={() => {
          clearStoredFlowSession(ASK_VIDEO_CALL_STANDALONE_KEY);
          setResumeCards(buildResumeCards());
          setFlowModal(null);
          setToastMessage("Payment successful");
        }}
      />
    );
  }

  return (
    <div className="ask-root">
      <Helmet>
        <html lang="en" />
        <title>{ASK_TITLE}</title>
        <meta name="description" content={ASK_DESCRIPTION} />
        <meta property="og:type" content="website" />
        <meta property="og:url" content={ASK_CANONICAL} />
        <meta property="og:title" content={ASK_TITLE} />
        <meta property="og:description" content={ASK_DESCRIPTION} />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content={ASK_TITLE} />
        <meta name="twitter:description" content={ASK_DESCRIPTION} />
        <link rel="canonical" href={ASK_CANONICAL} />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;0,9..144,900;1,9..144,600&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap"
          rel="stylesheet"
        />
        <script type="application/ld+json">
          {JSON.stringify({
            "@context": "https://schema.org",
            "@type": "MedicalWebPage",
            name: "Snoutiq Ask - Free AI Pet Health Check",
            description: ASK_DESCRIPTION,
            url: ASK_CANONICAL,
            inLanguage: "en-IN",
            about: {
              "@type": "Thing",
              name: "Pet symptom checker",
            },
          })}
        </script>
      </Helmet>

      <div className="ask-announcement">
        🐾 Free AI Pet Health Check · snoutiq.com/ask · No signup needed
      </div>

        <div className="ask-nav">
          <div className="ask-logo">
            <img
              src={logo}
            alt="SnoutIQ"
            className="ask-logo-image"
            width={130}
            height={24}
            loading="eager"
            decoding="async"
          />
        </div>
        <div className="ask-nav-actions">
          <div className="ask-badge">{navBadgeText}</div>
          <button
            type="button"
            className="ask-nav-button"
            onClick={() => {
              if (openAskFlowModal("/video-call-pet-details")) return;
              navigateToAskTarget(navigate, "/video-call-pet-details");
            }}
          >
            Consult ₹499
          </button>
        </div>
      </div>

      <div className="ask-page-shell">
        <main className="ask-page">
          <div className="ask-chat-header">
            <div className="ask-avatar">
              <img
                src={avatarLogo}
                alt="SnoutIQ"
                className="ask-avatar-image"
                width={28}
                height={28}
                loading="eager"
                decoding="async"
              />
            </div>
            <div className="ask-chat-meta">
              <h2>Snoutiq AI</h2>
              <p>
                <span className="ask-live-dot" />
                AI triage · Vet-reviewed · Free
              </p>
            </div>
            {sessionId ? (
              <button
                type="button"
                className="ask-reset-button"
                onClick={handleReset}
              >
                Start over
              </button>
            ) : null}
          </div>

          <div className="ask-chat-body">
            {entries.length === 0 ? (
              <IdleScreen
                species={species}
                onSpeciesSelect={setSpecies}
                onQuickStart={handleQuickStart}
              />
            ) : null}

            {entries.map((entry) => {
              if (entry.kind === "user") {
                return <UserMessage key={entry.id} entry={entry} />;
              }
              if (entry.kind === "note") {
                return <NoteMessage key={entry.id} entry={entry} />;
              }
              if (entry.kind === "assessment") {
                return (
                  <AssessmentCard
                    key={entry.id}
                    entry={entry}
                    entryAnchorId={`ask-entry-${entry.id}`}
                    onAction={handleAction}
                    onShare={handleShare}
                    onCopyAssessment={handleCopyAssessment}
                    onCopyLink={handleCopyLink}
                    onFollowUpAnswer={handleFollowUpAnswer}
                    followUpPending={
                      followUpPending?.entryId === entry.id ? followUpPending : null
                    }
                  />
                );
              }
              return null;
            })}

            {loading && !followUpPending ? (
              <div className="ask-message-group">
                <div className="ask-message-row">
                  <div className="ask-typing-bubble">
                    <span />
                    <span />
                    <span />
                  </div>
                </div>
              </div>
            ) : null}

            {errorMessage ? (
              <div className="ask-error-card" role="status">
                {errorMessage}
              </div>
            ) : null}
          </div>

          <div
            className="ask-input-bar"
            style={{
              flexDirection: "column",
              alignItems: "stretch",
            }}
          >
            {pendingAttachment ? (
              <div
                className="ask-note-card"
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: 10,
                  padding: "8px 10px",
                }}
              >
                <img
                  src={pendingAttachment.previewUrl}
                  alt={pendingAttachment?.name || "Pet image ready to send"}
                  style={{
                    width: 54,
                    height: 54,
                    borderRadius: 12,
                    objectFit: "cover",
                    flexShrink: 0,
                    border: "1px solid rgba(148, 163, 184, 0.22)",
                    background: "#fff",
                  }}
                />
                <div style={{ minWidth: 0, flex: 1 }}>
                  <div
                    style={{
                      color: "#111827",
                      fontSize: 13,
                      fontWeight: 700,
                    }}
                  >
                    Image ready to send
                  </div>
                  <div
                    style={{
                      color: "#6b7280",
                      fontSize: 11.5,
                      overflow: "hidden",
                      textOverflow: "ellipsis",
                      whiteSpace: "nowrap",
                    }}
                  >
                    {[pendingAttachment?.name, formatBytes(pendingAttachment?.size)]
                      .filter(Boolean)
                      .join(" · ")}
                  </div>
                </div>
                <button
                  type="button"
                  className="ask-reset-button"
                  style={{
                    height: 34,
                    width: 34,
                    padding: 0,
                    display: "inline-flex",
                    alignItems: "center",
                    justifyContent: "center",
                  }}
                  onClick={() => clearPendingAttachment({ revokePreview: true })}
                  disabled={loading}
                  aria-label="Remove attached image"
                >
                  <X size={14} />
                </button>
              </div>
            ) : null}

            <div
              style={{
                display: "flex",
                alignItems: "flex-end",
                gap: 8,
              }}
            >
              <input
                ref={attachmentInputRef}
                type="file"
                accept="image/*"
                onChange={handleAttachmentChange}
                style={{ display: "none" }}
              />
              <button
                type="button"
                className="ask-reset-button"
                style={{
                  height: 42,
                  width: 42,
                  padding: 0,
                  display: "inline-flex",
                  alignItems: "center",
                  justifyContent: "center",
                  borderColor: pendingAttachment ? "#93c5fd" : undefined,
                  background: pendingAttachment ? "#e8f1fb" : undefined,
                  color: pendingAttachment ? "#1565c0" : undefined,
                }}
                onClick={handleOpenAttachmentPicker}
                disabled={loading || intakeOpen}
                aria-label="Attach pet image"
              >
                <svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18">
                  <path
                    d="M16.5 6.5v8.75a4.25 4.25 0 1 1-8.5 0V5.75a2.75 2.75 0 1 1 5.5 0v8.5a1.25 1.25 0 1 1-2.5 0V7.5"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.9"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                </svg>
              </button>
              <textarea
                ref={textareaRef}
                className="ask-input"
                rows={1}
                value={inputValue}
                onChange={(event) => setInputValue(event.target.value)}
                placeholder={
                  pendingAttachment
                    ? "Add details, or send this image directly..."
                    : "Describe your pet's symptoms..."
                }
                onKeyDown={(event) => {
                  if (event.key === "Enter" && !event.shiftKey) {
                    event.preventDefault();
                    handleSend();
                  }
                }}
              />
              <button
                type="button"
                className="ask-send-button"
                onClick={() => handleSend()}
                disabled={loading || intakeOpen || !canSendMessage}
                aria-label="Send symptom message"
              >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z" />
                </svg>
              </button>
            </div>
          </div>
        </main>
      </div>

      <IntakeModal
        open={intakeOpen}
        profile={askProfile}
        species={species}
        pendingMessage={pendingInitialRequest?.message || inputValue}
        errors={intakeErrors}
        submitting={loading}
        breedOptions={breedOptions}
        breedLoading={breedLoading}
        breedError={breedError}
        locating={locating}
        locationStatus={locationStatus.text}
        locationStatusType={locationStatus.type}
        onClose={handleCloseIntake}
        onProfileChange={handleIntakeProfileChange}
        onSpeciesChange={handleIntakeSpeciesChange}
        onUseCurrentLocation={handleUseCurrentLocation}
        onSubmit={handleIntakeSubmit}
      />

      {flowModalContent ? (
        <div
          className="fixed inset-0 z-[120] bg-slate-950/55 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
        >
          <div className="absolute inset-0 overflow-y-auto overscroll-contain">
            <button
              type="button"
              onClick={() => setFlowModal(null)}
              className="fixed right-4 top-4 z-[130] inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-slate-950/70 text-white shadow-lg backdrop-blur transition hover:bg-slate-950/85"
              aria-label="Close checkout"
            >
              <X size={18} />
            </button>
            {flowModalContent}
          </div>
        </div>
      ) : null}

      {toastMessage ? <div className="ask-toast">{toastMessage}</div> : null}
    </div>
  );
}
