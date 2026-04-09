import React, { useEffect, useMemo, useRef, useState } from "react";
import { Helmet } from "react-helmet-async";
import { useLocation, useNavigate } from "react-router-dom";
import { Button } from "../../components/Button";
import { PET_FLOW_STEPS, ProgressBar } from "../../components/Sharedcomponents";
import { apiBaseUrl } from "../../lib/api";
import {
  Activity,
  AlertCircle,
  BadgeCheck,
  Calendar,
  Camera,
  Cat,
  CheckCircle2,
  ChevronDown,
  Coffee,
  Dog,
  FileText,
  Heart,
  Image,
  Lock,
  MapPin,
  PawPrint,
  Phone,
  Rabbit,
  Scale,
  Shield,
  Upload,
  User,
} from "lucide-react";

const FLOW_STORAGE_KEY = "snoutiq-video-call-copied-flow";
const PAYMENT_ROUTE = "/video-call-payment";
const PET_FORM_SUBMIT_TAG_ID = "AW-107928384221313";
const PET_FORM_SUBMIT_EVENT_NAME = "pet_form_submit";

const ENERGY_OPTIONS = [
  { label: "Normal", value: "normal" },
  { label: "Lower than usual", value: "low" },
  { label: "Very low", value: "very_low" },
  { label: "Hyperactive", value: "high" },
];

const APPETITE_OPTIONS = [
  { label: "Normal", value: "normal" },
  { label: "Eating less", value: "less" },
  { label: "Not eating", value: "none" },
  { label: "Eating more", value: "more" },
];

const MOOD_OPTIONS = [
  { label: "Calm", value: "calm" },
  { label: "Restless", value: "restless" },
  { label: "Anxious", value: "anxious" },
  { label: "Aggressive", value: "aggressive" },
  { label: "Playful", value: "playful" },
];

const GENDER_OPTIONS = [
  { label: "Male", value: "male" },
  { label: "Female", value: "female" },
];

const YES_NO_OPTIONS = [
  { label: "Yes", value: "1" },
  { label: "No", value: "0" },
];

const fieldBase =
  "w-full rounded-xl border border-[#d6e3ff] bg-[#fbfdff] px-3 py-2.5 text-sm text-[#0f172a] placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-[#4f6bff]/12 focus:border-[#4f6bff] hover:border-[#bfd0ff] disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed md:px-3.5 md:py-3";
const selectBase = `${fieldBase} appearance-none pr-12`;
const textareaBase = `${fieldBase} resize-none min-h-[104px]`;
const cardBase =
  "overflow-hidden rounded-[24px] border border-[#d6e3ff] bg-white/95 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)] backdrop-blur";
const cardHeaderBase =
  "flex items-center gap-3 border-b border-[#e7efff] bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] px-4 py-3.5";
const cardBodyBase = "px-4 py-4 space-y-3.5";

const pickValue = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    if (typeof value === "string") {
      const trimmed = value.trim();
      if (trimmed) return trimmed;
      continue;
    }
    return value;
  }
  return undefined;
};

const toNumber = (value) => {
  if (value === undefined || value === null || value === "") return undefined;
  const n = Number(value);
  return Number.isFinite(n) ? n : undefined;
};

const stripEmpty = (payload) =>
  Object.fromEntries(
    Object.entries(payload).filter(
      ([, value]) => value !== undefined && value !== null && value !== ""
    )
  );

const formatPhone = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  if (digits.startsWith("91") && digits.length > 10) return digits;
  return `91${digits.slice(-10)}`;
};

const normalizePhoneInput = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  return digits.length > 10 ? digits.slice(-10) : digits;
};

const formatBreedName = (breedKey, subBreed = null) => {
  const cap = (input) =>
    String(input || "")
      .split(/[-_\s/]+/)
      .filter(Boolean)
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");

  const base = cap(breedKey);
  return subBreed ? `${cap(subBreed)} ${base}` : base;
};

const todayISO = () => new Date().toISOString().slice(0, 10);

const calcAgeFromDob = (dob) => {
  if (!dob) return "";
  const birth = new Date(dob);
  if (Number.isNaN(birth.getTime())) return "";
  const today = new Date();
  if (birth > today) return "";
  let years = today.getFullYear() - birth.getFullYear();
  let months = today.getMonth() - birth.getMonth();
  const days = today.getDate() - birth.getDate();
  if (days < 0) months -= 1;
  if (months < 0) {
    years -= 1;
    months += 12;
  }
  if (years <= 0 && months <= 0) return "Less than 1 month";
  if (years <= 0) return `${months} mo${months === 1 ? "" : "s"}`;
  if (months === 0) return `${years} yr${years === 1 ? "" : "s"}`;
  return `${years} yr${years === 1 ? "" : "s"} ${months} mo${months === 1 ? "" : "s"}`;
};

const normalizePetType = (value) => {
  const raw = String(value || "").trim();
  const lower = raw.toLowerCase();
  if (!lower) return { type: "", exoticType: "" };
  if (lower === "dog" || lower === "dogs") return { type: "dog", exoticType: "" };
  if (lower === "cat" || lower === "cats") return { type: "cat", exoticType: "" };
  if (lower === "exotic" || lower === "other") return { type: "exotic", exoticType: "" };
  return {
    type: "exotic",
    exoticType: raw.charAt(0).toUpperCase() + raw.slice(1),
  };
};

const readStoredFlow = () => {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.sessionStorage.getItem(FLOW_STORAGE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
};

const writeStoredFlow = (value) => {
  if (typeof window === "undefined") return;
  window.sessionStorage.setItem(FLOW_STORAGE_KEY, JSON.stringify(value));
};

const extractPaymentMeta = (petDetails, paymentMeta) => {
  const userId = toNumber(
    pickValue(
      paymentMeta?.user_id,
      paymentMeta?.userId,
      petDetails?.user_id,
      petDetails?.userId,
      petDetails?.observation?.user_id,
      petDetails?.observation?.userId,
      petDetails?.observation?.user?.id,
      petDetails?.observationResponse?.user_id,
      petDetails?.observationResponse?.userId,
      petDetails?.observationResponse?.user?.id,
      petDetails?.observationResponse?.data?.user_id,
      petDetails?.observationResponse?.data?.userId,
      petDetails?.observationResponse?.data?.user?.id,
      petDetails?.observationResponse?.data?.data?.user_id,
      petDetails?.observationResponse?.data?.data?.userId,
      petDetails?.observationResponse?.data?.data?.user?.id
    )
  );

  const petId = toNumber(
    pickValue(
      paymentMeta?.pet_id,
      paymentMeta?.petId,
      petDetails?.pet_id,
      petDetails?.petId,
      petDetails?.observation?.pet_id,
      petDetails?.observation?.petId,
      petDetails?.observation?.pet?.id,
      petDetails?.observationResponse?.pet_id,
      petDetails?.observationResponse?.petId,
      petDetails?.observationResponse?.pet?.id,
      petDetails?.observationResponse?.data?.pet_id,
      petDetails?.observationResponse?.data?.petId,
      petDetails?.observationResponse?.data?.pet?.id,
      petDetails?.observationResponse?.data?.data?.pet_id,
      petDetails?.observationResponse?.data?.data?.petId,
      petDetails?.observationResponse?.data?.data?.pet?.id
    )
  );

  return stripEmpty({
    user_id: userId,
    pet_id: petId,
    order_type: pickValue(
      paymentMeta?.order_type,
      paymentMeta?.orderType,
      petDetails?.order_type,
      petDetails?.orderType,
      petDetails?.observation?.order_type,
      petDetails?.observation?.orderType,
      petDetails?.observationResponse?.order_type,
      petDetails?.observationResponse?.orderType,
      petDetails?.observationResponse?.data?.order_type,
      petDetails?.observationResponse?.data?.orderType
    ),
    call_session_id: pickValue(
      paymentMeta?.call_session_id,
      paymentMeta?.callSessionId,
      petDetails?.call_session_id,
      petDetails?.callSessionId,
      petDetails?.observation?.call_session_id,
      petDetails?.observation?.callSessionId,
      petDetails?.observationResponse?.call_session_id,
      petDetails?.observationResponse?.callSessionId,
      petDetails?.observationResponse?.data?.call_session_id,
      petDetails?.observationResponse?.data?.callSessionId
    ),
    gst_number: pickValue(
      paymentMeta?.gst_number,
      paymentMeta?.gstNumber,
      petDetails?.gst_number,
      petDetails?.gstNumber
    ),
  });
};

const buildInitialDetails = (source) => {
  const raw = source && typeof source === "object" ? source : {};
  const normalizedType = normalizePetType(
    pickValue(raw.type, raw.species, raw.petType, raw.pet_type)
  );

  return {
    ownerName: pickValue(raw.ownerName, raw.owner_name) || "",
    ownerMobile: normalizePhoneInput(
      pickValue(raw.ownerMobile, raw.phone, raw.owner_phone)
    ),
    city: pickValue(raw.city, raw.location, raw.area) || "",
    name: pickValue(raw.name, raw.pet_name, raw.petName) || "",
    type: normalizedType.type,
    breed:
      normalizedType.type === "dog" || normalizedType.type === "cat"
        ? pickValue(raw.breed) || ""
        : "",
    petDob: pickValue(raw.petDob, raw.dob) || "",
    gender: pickValue(raw.gender, raw.sex) || "",
    problemText:
      pickValue(
        raw.problemText,
        raw.reported_symptom,
        raw.reason,
        raw.description
      ) || "",
    mood: pickValue(raw.mood) || "",
    petDoc2: pickValue(raw.petDoc2, raw.pet_doc2) || "",
    exoticType:
      normalizedType.type === "exotic"
        ? pickValue(raw.exoticType, raw.otherPetType, normalizedType.exoticType) || ""
        : "",
    lastDaysEnergy: pickValue(raw.lastDaysEnergy, raw.energy) || "",
    lastDaysAppetite: pickValue(raw.lastDaysAppetite, raw.appetite) || "",
    hasPhoto: false,
    isNeutered:
      pickValue(raw.isNeutered, raw.is_neutered) !== undefined
        ? String(pickValue(raw.isNeutered, raw.is_neutered))
        : "",
    vaccinatedYesNo:
      pickValue(raw.vaccinatedYesNo, raw.vaccenated_yes_no, raw.vaccinated_yes_no) !== undefined
        ? String(
            pickValue(
              raw.vaccinatedYesNo,
              raw.vaccenated_yes_no,
              raw.vaccinated_yes_no
            )
          )
        : "",
    dewormingYesNo:
      pickValue(raw.dewormingYesNo, raw.deworming_yes_no) !== undefined
        ? String(pickValue(raw.dewormingYesNo, raw.deworming_yes_no))
        : "",
    weightKg:
      pickValue(raw.weightKg, raw.weight) !== undefined
        ? String(pickValue(raw.weightKg, raw.weight))
        : "",
  };
};

const buildDraftDetails = (source) => ({
  ...buildInitialDetails(source),
  hasPhoto: false,
});

const buildHiddenPrefillFields = (source) => {
  const prepared = buildInitialDetails(source);
  return {
    ownerName: Boolean(prepared.ownerName),
    ownerMobile: Boolean(prepared.ownerMobile),
    city: Boolean(prepared.city),
    name: Boolean(prepared.name),
    type: Boolean(prepared.type),
    breed: Boolean(prepared.breed),
    petDob: Boolean(prepared.petDob),
    gender: Boolean(prepared.gender),
    problemText: Boolean(prepared.problemText),
    exoticType: Boolean(prepared.exoticType),
    lastDaysEnergy: Boolean(prepared.lastDaysEnergy),
    lastDaysAppetite: Boolean(prepared.lastDaysAppetite),
    mood: Boolean(prepared.mood),
    isNeutered: Boolean(prepared.isNeutered),
    vaccinatedYesNo: Boolean(prepared.vaccinatedYesNo),
    dewormingYesNo: Boolean(prepared.dewormingYesNo),
    weightKg: Boolean(prepared.weightKg),
  };
};

const areDraftDetailsEqual = (left, right) =>
  JSON.stringify(buildDraftDetails(left)) === JSON.stringify(buildDraftDetails(right));

const compressImageFile = async (
  file,
  { maxWidth = 1280, maxHeight = 1280, quality = 0.72, outputMime = "image/jpeg" } = {}
) => {
  if (!file || !file.type?.startsWith("image/")) return file;
  const bitmap = await createImageBitmap(file).catch(() => null);
  if (!bitmap) return file;
  const ratio = Math.min(maxWidth / bitmap.width, maxHeight / bitmap.height, 1);
  const canvas = document.createElement("canvas");
  canvas.width = Math.round(bitmap.width * ratio);
  canvas.height = Math.round(bitmap.height * ratio);
  const ctx = canvas.getContext("2d");
  if (!ctx) return file;
  ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
  const blob = await new Promise((resolve) => {
    canvas.toBlob((result) => resolve(result), outputMime, quality);
  });
  if (!blob || blob.size >= file.size) return file;
  return new File(
    [blob],
    `${file.name?.replace(/\.[^/.]+$/, "") || "upload"}_compressed.jpg`,
    { type: outputMime }
  );
};

const getPetTypeIcon = (type) => {
  switch (type) {
    case "dog":
      return <Dog size={20} />;
    case "cat":
      return <Cat size={20} />;
    case "exotic":
      return <Rabbit size={20} />;
    default:
      return <PawPrint size={20} />;
  }
};

const getPetTypeLabel = (type) => {
  switch (type) {
    case "dog":
      return "Dog";
    case "cat":
      return "Cat";
    case "exotic":
      return "Exotic";
    default:
      return "";
  }
};

export default function VideoCallPetDetails({ initialState, onSubmit, vet }) {
  void vet;
  const location = useLocation();
  const navigate = useNavigate();
  const storedFlow = useMemo(() => readStoredFlow(), []);
  const routeState =
    initialState && typeof initialState === "object"
      ? initialState
      : location.state && typeof location.state === "object"
        ? location.state
        : {};
  const routePrefill =
    routeState?.prefill && typeof routeState.prefill === "object"
      ? routeState.prefill
      : null;
  const storedPetDetails = routeState?.petDetails || storedFlow?.petDetails || null;
  const storedPaymentMeta = routeState?.paymentMeta || storedFlow?.paymentMeta || null;
  const storedDraft = routeState?.draft || storedFlow?.draft || null;
  const prefillSource = {
    ...(routePrefill || {}),
    ...(storedPetDetails && typeof storedPetDetails === "object" ? storedPetDetails : {}),
    ...(storedDraft && typeof storedDraft === "object" ? storedDraft : {}),
  };
  const initialHasChangesSinceSubmit = Boolean(
    storedDraft && !areDraftDetailsEqual(storedDraft, storedPetDetails)
  );

  const [details, setDetails] = useState(() => buildInitialDetails(prefillSource));
  const [uploadFile, setUploadFile] = useState(null);
  const [uploadPreviewUrl, setUploadPreviewUrl] = useState("");
  const [uploadMeta, setUploadMeta] = useState(null);
  const [isDragging, setIsDragging] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [dogBreeds, setDogBreeds] = useState([]);
  const [catBreeds, setCatBreeds] = useState([]);
  const [breedSearch, setBreedSearch] = useState("");
  const [breedDropdownOpen, setBreedDropdownOpen] = useState(false);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [breedError, setBreedError] = useState("");
  const [hasChangesSinceSubmit, setHasChangesSinceSubmit] = useState(
    initialHasChangesSinceSubmit
  );
  const [hiddenPrefillFields, setHiddenPrefillFields] = useState(() =>
    routePrefill && !storedPetDetails && !storedDraft
      ? buildHiddenPrefillFields(routePrefill)
      : {}
  );
  const breedDropdownRef = useRef(null);

  const existingPaymentMeta = useMemo(
    () => extractPaymentMeta(storedPetDetails, storedPaymentMeta),
    [storedPaymentMeta, storedPetDetails]
  );
  const hasExistingSubmission = Boolean(
    existingPaymentMeta?.user_id && existingPaymentMeta?.pet_id && storedPetDetails
  );
  const isPrefilled = (field) => Boolean(hiddenPrefillFields[field]);
  const shouldShowField = (field) => !isPrefilled(field);
  const revealField = (field) => {
    setHiddenPrefillFields((current) =>
      current[field] ? { ...current, [field]: false } : current
    );
  };

  const revealAllPrefilledFields = () => {
    setHiddenPrefillFields((current) =>
      Object.fromEntries(Object.keys(current).map((key) => [key, false]))
    );
  };

  const updateField = (field, value) => {
    setHasChangesSinceSubmit(true);
    setSubmitError("");
    revealField(field);
    setDetails((current) => ({ ...current, [field]: value }));
  };

  useEffect(() => {
    const currentStoredFlow = readStoredFlow() || {};
    writeStoredFlow({
      ...currentStoredFlow,
      petDetails: currentStoredFlow.petDetails || storedPetDetails || null,
      paymentMeta: currentStoredFlow.paymentMeta || storedPaymentMeta || null,
      draft: buildDraftDetails(details),
    });
  }, [details, storedPaymentMeta, storedPetDetails]);

  useEffect(() => {
    if (!breedDropdownOpen) return undefined;
    const handleClick = (event) => {
      if (breedDropdownRef.current?.contains(event.target)) return;
      setBreedDropdownOpen(false);
    };
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, [breedDropdownOpen]);

  useEffect(() => {
    const abortController = new AbortController();

    const fetchDogBreeds = async () => {
      setBreedError("");
      setLoadingBreeds(true);
      try {
        const res = await fetch(`${apiBaseUrl()}/api/dog-breeds/all`, {
          method: "GET",
          signal: abortController.signal,
        });
        const data = await res.json();
        if (data?.status === "success" && data?.breeds) {
          const list = [];
          Object.keys(data.breeds).forEach((breedKey) => {
            const subBreeds = data.breeds[breedKey];
            if (!subBreeds || subBreeds.length === 0) {
              list.push({ label: formatBreedName(breedKey), value: breedKey });
              return;
            }
            list.push({ label: formatBreedName(breedKey), value: breedKey });
            subBreeds.forEach((subBreed) => {
              list.push({
                label: formatBreedName(breedKey, subBreed),
                value: `${breedKey}/${subBreed}`,
              });
            });
          });
          list.sort((left, right) => left.label.localeCompare(right.label));
          list.push(
            { label: "Mixed Breed", value: "mixed_breed" },
            { label: "Other", value: "other" }
          );
          setDogBreeds(list);
        } else {
          setDogBreeds([
            { label: "Mixed Breed", value: "mixed_breed" },
            { label: "Other", value: "other" },
          ]);
          setBreedError("Could not load breeds. You can still continue.");
        }
      } catch (error) {
        if (error?.name === "AbortError") return;
        setDogBreeds([
          { label: "Mixed Breed", value: "mixed_breed" },
          { label: "Other", value: "other" },
        ]);
        setBreedError("Could not load breeds. You can still continue.");
      } finally {
        setLoadingBreeds(false);
      }
    };

    const fetchCatBreeds = async () => {
      setBreedError("");
      setLoadingBreeds(true);
      try {
        const res = await fetch(`${apiBaseUrl()}/api/cat-breeds/with-indian`, {
          method: "GET",
          signal: abortController.signal,
        });
        const data = await res.json();
        if (data?.success && Array.isArray(data?.data)) {
          const list = data.data
            .map((breed) => ({
              label: breed?.name || breed?.id || "Unknown",
              value: breed?.name || breed?.id || "unknown",
            }))
            .filter((item) => item.label);
          list.sort((left, right) => left.label.localeCompare(right.label));
          list.push({ label: "Mixed / Other", value: "other" });
          setCatBreeds(list);
        } else {
          setCatBreeds([{ label: "Mixed / Other", value: "other" }]);
          setBreedError("Could not load breeds. You can still continue.");
        }
      } catch (error) {
        if (error?.name === "AbortError") return;
        setCatBreeds([{ label: "Mixed / Other", value: "other" }]);
        setBreedError("Could not load breeds. You can still continue.");
      } finally {
        setLoadingBreeds(false);
      }
    };

    if (details.type === "dog") {
      fetchDogBreeds();
    } else if (details.type === "cat") {
      fetchCatBreeds();
    } else {
      setDogBreeds([]);
      setCatBreeds([]);
      setBreedError("");
      setLoadingBreeds(false);
    }

    setBreedSearch("");
    setBreedDropdownOpen(false);
    return () => abortController.abort();
  }, [details.type]);

  useEffect(() => {
    return () => {
      if (uploadPreviewUrl) URL.revokeObjectURL(uploadPreviewUrl);
    };
  }, [uploadPreviewUrl]);

  const breedOptions = useMemo(() => {
    if (details.type === "dog") return dogBreeds;
    if (details.type === "cat") return catBreeds;
    return [];
  }, [catBreeds, details.type, dogBreeds]);

  const filteredBreedOptions = useMemo(() => {
    const term = breedSearch.trim().toLowerCase();
    if (!term) return breedOptions;
    return breedOptions.filter((option) =>
      String(option?.label || "").toLowerCase().includes(term)
    );
  }, [breedOptions, breedSearch]);

  const selectedBreedLabel = useMemo(() => {
    if (!details.breed) return "";
    return breedOptions.find((option) => option.value === details.breed)?.label || "";
  }, [breedOptions, details.breed]);

  const showBreed = details.type === "dog" || details.type === "cat";
  const isExotic = details.type === "exotic";
  const approxAge = useMemo(() => calcAgeFromDob(details.petDob), [details.petDob]);
  const uploadKind = useMemo(() => {
    if (!uploadFile?.type) return "file";
    if (uploadFile.type.startsWith("image/")) return "image";
    if (uploadFile.type === "application/pdf") return "pdf";
    return "file";
  }, [uploadFile]);
  const uploadIcon = useMemo(() => {
    if (uploadKind === "image") return <Image className="h-4 w-4" />;
    return <FileText className="h-4 w-4" />;
  }, [uploadKind]);
  const canReuseExistingSubmission = hasExistingSubmission && !hasChangesSinceSubmit;
  const phoneDigits = details.ownerMobile.replace(/\D/g, "");
  const visibleProblemTextLength = details.problemText.trim().length;
  const showOwnerNameField = shouldShowField("ownerName");
  const showOwnerMobileField = shouldShowField("ownerMobile");
  const showCityField = shouldShowField("city");
  const showOwnerSection =
    showOwnerNameField || showOwnerMobileField || showCityField;
  const showPetNameField = shouldShowField("name");
  const showGenderField = shouldShowField("gender");
  const showTypeField = shouldShowField("type");
  const showBreedField = showBreed && shouldShowField("breed");
  const showExoticTypeField = isExotic && shouldShowField("exoticType");
  const showPetDobField = shouldShowField("petDob");
  const showWeightField = shouldShowField("weightKg");
  const showPetSection =
    showPetNameField ||
    showGenderField ||
    showTypeField ||
    showBreedField ||
    showExoticTypeField ||
    showPetDobField ||
    showWeightField;
  const showProblemTextField = shouldShowField("problemText");
  const showEnergyField = shouldShowField("lastDaysEnergy");
  const showAppetiteField = shouldShowField("lastDaysAppetite");
  const showMoodField = shouldShowField("mood");
  const showConcernSection =
    showProblemTextField || showEnergyField || showAppetiteField || showMoodField;
  const showIsNeuteredField = shouldShowField("isNeutered");
  const showVaccinatedField = shouldShowField("vaccinatedYesNo");
  const showDewormingField = shouldShowField("dewormingYesNo");
  const showMedicalSection =
    showIsNeuteredField || showVaccinatedField || showDewormingField;
  const hiddenSavedCount = Object.values(hiddenPrefillFields).filter(Boolean).length;
  const hasHiddenSavedDetails = hiddenSavedCount > 0;
  const hiddenSavedSummaryRows = [
    {
      label: "Parent",
      value: [details.ownerName, details.ownerMobile].filter(Boolean).join(" • "),
    },
    {
      label: "Pet",
      value: [
        details.name,
        getPetTypeLabel(details.type),
        details.type === "exotic" ? details.exoticType : selectedBreedLabel || details.breed,
      ]
        .filter(Boolean)
        .join(" • "),
    },
    {
      label: "Location",
      value: details.city,
    },
  ].filter((item) => item.value);

  const getStepOneTooltip = () => {
    if (!details.ownerName.trim()) return "Please enter owner name";
    if (phoneDigits.length !== 10) return "Please enter 10-digit mobile number";
    if (!details.city.trim()) return "Please enter city name";
    if (!details.name.trim()) return "Please enter your pet's name";
    if (!details.type) return "Please select pet type";
    if (!details.gender) return "Please select pet gender";
    if (isExotic && !details.exoticType.trim()) return "Please specify your pet type";
    if (showBreed && !details.breed) return "Please select breed";
    if (!details.petDob) return "Please select pet's date of birth";
    return "";
  };

  const getStepTwoTooltip = () => {
    if (details.problemText.trim().length <= 10) {
      return "Please describe the problem in detail (minimum 10 characters)";
    }
    if (!details.lastDaysEnergy) return "Please select energy level";
    if (!details.lastDaysAppetite) return "Please select appetite";
    if (!details.mood) return "Please select mood";
    if (details.isNeutered === "") return "Please select neutered status";
    if (details.vaccinatedYesNo === "") return "Please select vaccination status";
    if (details.dewormingYesNo === "") return "Please select deworming status";
    return "";
  };

  const getStepThreeTooltip = () => {
    if (!canReuseExistingSubmission && (!details.hasPhoto || !uploadFile)) {
      return "Please upload a photo or PDF";
    }
    return "";
  };

  const stepOneReady = !getStepOneTooltip();
  const stepTwoReady = !getStepTwoTooltip();
  const stepThreeReady = !getStepThreeTooltip();
  const isValid = stepOneReady && stepTwoReady && stepThreeReady;

  const getSubmitTooltip = () => {
    const stepOneTooltip = getStepOneTooltip();
    if (stepOneTooltip) return stepOneTooltip;
    const stepTwoTooltip = getStepTwoTooltip();
    if (stepTwoTooltip) return stepTwoTooltip;
    return getStepThreeTooltip();
  };
  const submitTooltip = getSubmitTooltip();
  const primaryButtonLabel = "Continue to Payment";

  const continueToPayment = (petPayload, paymentPayload) => {
    const nextPaymentMeta = stripEmpty({
      ...paymentPayload,
      gst_number: pickValue(
        paymentPayload?.gst_number,
        storedPaymentMeta?.gst_number,
        storedFlow?.paymentMeta?.gst_number
      ),
    });

    setHasChangesSinceSubmit(false);
    writeStoredFlow({
      petDetails: petPayload,
      paymentMeta: nextPaymentMeta,
      draft: buildDraftDetails(petPayload),
    });

    if (onSubmit) {
      onSubmit(petPayload);
      return;
    }

    navigate(PAYMENT_ROUTE, {
      state: { petDetails: petPayload, paymentMeta: nextPaymentMeta },
    });
  };

  const applyUploadFile = async (file) => {
    if (!file) return;
    const lowerName = file.name?.toLowerCase() || "";
    const isVideo =
      file.type?.startsWith("video/") || /\.(mp4|mov|avi|mkv|webm)$/i.test(lowerName);
    if (isVideo) {
      setSubmitError("Video uploads are not supported. Please upload a photo or PDF.");
      return;
    }
    const isImage = file.type?.startsWith("image/");
    const isPdf = file.type === "application/pdf" || lowerName.endsWith(".pdf");
    if (!isImage && !isPdf) {
      setSubmitError("Please upload a JPG, PNG, or PDF file.");
      return;
    }
    if (uploadPreviewUrl) URL.revokeObjectURL(uploadPreviewUrl);
    setUploadPreviewUrl(isImage ? URL.createObjectURL(file) : "");
    setUploadFile(file);
    setUploadMeta({ name: file.name, size: file.size, type: file.type, compressedSize: null });
    setHasChangesSinceSubmit(true);
    setSubmitError("");
    setDetails((current) => ({ ...current, hasPhoto: true }));
  };

  const submitObservation = async () => {
    if (!isValid || submitting) return;

    if (canReuseExistingSubmission && storedPetDetails) {
      continueToPayment(storedPetDetails, existingPaymentMeta);
      return;
    }

    setSubmitError("");
    setSubmitting(true);

    try {
      let fileToSend = uploadFile;
      if (uploadFile?.type?.startsWith("image/")) {
        const compressed = await compressImageFile(uploadFile);
        fileToSend = compressed;
        setUploadMeta((current) =>
          current ? { ...current, compressedSize: compressed?.size ?? null } : current
        );
      }

      const fd = new FormData();
      fd.append("name", details.ownerName);
      fd.append("phone", formatPhone(details.ownerMobile));
      fd.append("city", details.city.trim());
      fd.append("type", details.type || "");
      fd.append("dob", details.petDob || "");
      fd.append("pet_name", details.name || "");
      if (details.weightKg !== "") fd.append("weight", details.weightKg);
      fd.append("gender", details.gender || "");
      fd.append(
        "breed",
        details.type === "exotic" ? details.exoticType.trim() : details.breed || ""
      );
      fd.append("reported_symptom", details.problemText || "");
      if (details.lastDaysAppetite) fd.append("appetite", details.lastDaysAppetite);
      if (details.lastDaysEnergy) fd.append("energy", details.lastDaysEnergy);
      if (details.mood) fd.append("mood", details.mood);
      if (details.isNeutered !== "") fd.append("is_neutered", details.isNeutered);
      if (details.vaccinatedYesNo !== "") {
        fd.append("vaccenated_yes_no", details.vaccinatedYesNo);
      }
      if (details.dewormingYesNo !== "") {
        fd.append("deworming_yes_no", details.dewormingYesNo);
      }
      if (details.petDoc2?.trim()) fd.append("pet_doc2", details.petDoc2.trim());
      if (fileToSend) fd.append("file", fileToSend);

      const res = await fetch(`${apiBaseUrl()}/api/user-pet-observation`, {
        method: "POST",
        body: fd,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data?.message || "Failed to submit observation");

      const observation = data?.data ?? data ?? {};
      const userId = toNumber(
        pickValue(
          observation?.user_id,
          observation?.userId,
          observation?.user?.id,
          data?.user_id,
          data?.userId,
          data?.user?.id,
          data?.data?.user_id,
          data?.data?.userId,
          data?.data?.user?.id,
          data?.data?.data?.user_id,
          data?.data?.data?.userId,
          data?.data?.data?.user?.id
        )
      );
      const petId = toNumber(
        pickValue(
          observation?.pet_id,
          observation?.petId,
          observation?.pet?.id,
          data?.pet_id,
          data?.petId,
          data?.pet?.id,
          data?.data?.pet_id,
          data?.data?.petId,
          data?.data?.pet?.id,
          data?.data?.data?.pet_id,
          data?.data?.data?.petId,
          data?.data?.data?.pet?.id
        )
      );

      const nextPaymentMeta = stripEmpty({
        user_id: userId,
        pet_id: petId,
        order_type: pickValue(
          observation?.order_type,
          observation?.orderType,
          data?.order_type,
          data?.orderType,
          data?.data?.order_type,
          data?.data?.orderType,
          existingPaymentMeta?.order_type
        ),
        call_session_id: pickValue(
          observation?.call_session_id,
          observation?.callSessionId,
          data?.call_session_id,
          data?.callSessionId,
          data?.data?.call_session_id,
          data?.data?.callSessionId,
          existingPaymentMeta?.call_session_id
        ),
        gst_number: pickValue(
          storedPaymentMeta?.gst_number,
          storedFlow?.paymentMeta?.gst_number
        ),
      });

      const nextPayload = stripEmpty({
        ...details,
        observation,
        observationResponse: data,
        user_id: userId,
        pet_id: petId,
        order_type: nextPaymentMeta.order_type,
        call_session_id: nextPaymentMeta.call_session_id,
      });

      continueToPayment(nextPayload, nextPaymentMeta);
    } catch (error) {
      setSubmitError(error?.message || "Something went wrong. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  const handleSubmitClick = () => {
    if (typeof window !== "undefined") {
      if (typeof window.gtagSendEvent === "function") {
        window.gtagSendEvent();
      } else if (typeof window.gtag === "function") {
        window.gtag("event", PET_FORM_SUBMIT_EVENT_NAME, { event_timeout: 2000 });
      }
    }
    submitObservation();
  };

  return (
    <div>
      <Helmet>
        <script>
          {`
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
            window.gtag("config", "${PET_FORM_SUBMIT_TAG_ID}");
            window.gtagSendEvent = function(url) {
              var callback = function () {
                if (typeof url === "string") {
                  window.location = url;
                }
              };
              window.gtag("event", "${PET_FORM_SUBMIT_EVENT_NAME}", {
                event_callback: callback,
                event_timeout: 2000
              });
              return false;
            };
          `}
        </script>
      </Helmet>

      <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.14),_transparent_30%),linear-gradient(180deg,#f8fbff_0%,#eef4ff_100%)] flex flex-col">
        <div className="sticky top-0 z-40 border-b border-[#dbe5ff] bg-white/90 backdrop-blur">
          <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6">
            <div>
              <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#4f6bff]">
                Snoutiq
              </div>
              <div className="text-base font-semibold text-slate-900 md:text-lg">
                Video consultation request
              </div>
            </div>
          </div>
        </div>

        <div className="w-full">
          <div className="flex-1 px-4 pb-28 pt-4 md:px-6 md:pb-20 md:pt-8">
            <div className="mx-auto w-full max-w-6xl">
              <div className="mt-6 space-y-6">
                {hasHiddenSavedDetails ? (
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <BadgeCheck size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">Personal Details</h3>
                      </div>
                    </div>
                    <div className={cardBodyBase}>
                      <div className="grid gap-3 md:grid-cols-3">
                        {hiddenSavedSummaryRows.map((item) => (
                          <div
                            key={item.label}
                            className="rounded-xl border border-[#e5ecff] bg-[#f8fbff] px-3.5 py-2.5"
                          >
                            <div className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                              {item.label}
                            </div>
                            <div className="mt-1 text-sm font-medium text-slate-900">
                              {item.value}
                            </div>
                          </div>
                        ))}
                      </div>
                      <button
                        type="button"
                        onClick={revealAllPrefilledFields}
                        className="inline-flex items-center justify-center rounded-full border border-[#c8d7ff] bg-white px-3.5 py-1.5 text-sm font-semibold text-[#2457ff] transition hover:border-[#9fb8ff] hover:bg-[#f8fbff]"
                      >
                        Edit saved details
                      </button>
                    </div>
                  </section>
                ) : null}

                {showOwnerSection ? (
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <User size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">Personal details</h3>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      <div className="grid grid-cols-1 gap-5 md:grid-cols-2 md:gap-6">
                        {showOwnerNameField ? (
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-gray-700">
                              Pet Owner Name <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                              <User size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                              <input
                                type="text"
                                value={details.ownerName}
                                onChange={(event) => updateField("ownerName", event.target.value)}
                                placeholder="Enter your full name"
                                className={`${fieldBase} pl-12 md:pl-12`}
                              />
                            </div>
                          </div>
                        ) : null}

                        {showOwnerMobileField ? (
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-gray-700">
                              Mobile Number <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                              <Phone size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                              <input
                                type="tel"
                                value={details.ownerMobile}
                                onChange={(event) => updateField("ownerMobile", normalizePhoneInput(event.target.value))}
                                placeholder="10-digit mobile number"
                                className={`${fieldBase} pl-12 md:pl-12`}
                                inputMode="numeric"
                              />
                            </div>
                          </div>
                        ) : null}

                        {showCityField ? (
                          <div className="space-y-2 md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700">
                              City <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                              <MapPin size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                              <input
                                type="text"
                                value={details.city}
                                onChange={(event) => updateField("city", event.target.value)}
                                placeholder="Enter city (e.g. Gurugram)"
                                className={`${fieldBase} pl-12 md:pl-12`}
                              />
                            </div>
                          </div>
                        ) : null}
                      </div>
                    </div>
                  </section>
                ) : null}

                {showPetSection ? (
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <PawPrint size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">Pet details</h3>
                        <p className="text-xs text-gray-500">Basic details for the request</p>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      {showPetNameField || showGenderField ? (
                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2 md:gap-6">
                          {showPetNameField ? (
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Pet's Name <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <PawPrint size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                <input
                                  type="text"
                                  value={details.name}
                                  onChange={(event) => updateField("name", event.target.value)}
                                  placeholder="Enter your pet's name"
                                  className={`${fieldBase} pl-12 md:pl-12`}
                                />
                              </div>
                            </div>
                          ) : null}

                          {showGenderField ? (
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Gender <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <Heart size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                <select
                                  value={details.gender}
                                  onChange={(event) => updateField("gender", event.target.value)}
                                  className={`${selectBase} pl-12 md:pl-12`}
                                >
                                  <option value="">Select gender</option>
                                  {GENDER_OPTIONS.map((option) => (
                                    <option key={option.value} value={option.value}>
                                      {option.label}
                                    </option>
                                  ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                              </div>
                            </div>
                          ) : null}
                        </div>
                      ) : null}

                      {showTypeField ? (
                        <div className="space-y-3">
                          <label className="block text-sm font-medium text-gray-700">
                            Pet Type <span className="text-red-500">*</span>
                          </label>
                          <div className="grid grid-cols-3 gap-3 md:gap-4">
                            {["dog", "cat", "exotic"].map((type) => (
                              <button
                                key={type}
                                type="button"
                                onClick={() => {
                                  setHasChangesSinceSubmit(true);
                                  setSubmitError("");
                                  revealField("breed");
                                  revealField("exoticType");
                                  setDetails((current) => ({
                                    ...current,
                                    type,
                                    breed: "",
                                    exoticType: "",
                                  }));
                                }}
                                className={[
                                  "rounded-xl border-2 px-3 py-3 flex flex-col items-center gap-2 transition-all duration-200",
                                  "md:flex-row md:justify-center md:gap-2.5 md:px-3.5 md:py-3.5",
                                  details.type === type
                                    ? "border-[#3998de] bg-[#3998de]/5 text-[#3998de]"
                                    : "border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100",
                                ].join(" ")}
                              >
                                <div className={details.type === type ? "text-[#3998de]" : "text-gray-500"}>
                                  {getPetTypeIcon(type)}
                                </div>
                                <span className="capitalize text-sm font-medium">{type}</span>
                              </button>
                            ))}
                          </div>
                        </div>
                      ) : null}

                      {showBreedField ? (
                        <div className="space-y-2">
                          <label className="block text-sm font-medium text-gray-700">
                            Breed <span className="text-red-500">*</span>
                          </label>
                          <div className="relative" ref={breedDropdownRef}>
                            <button
                              type="button"
                              onClick={() =>
                                !loadingBreeds && breedOptions.length
                                  ? setBreedDropdownOpen((current) => !current)
                                  : null
                              }
                              className={`${selectBase} text-left`}
                              disabled={loadingBreeds || breedOptions.length === 0}
                            >
                              {loadingBreeds
                                ? `Loading ${details.type || "pet"} breeds...`
                                : selectedBreedLabel || `Select ${details.type || "pet"} breed`}
                            </button>
                            <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                            {breedDropdownOpen ? (
                              <div className="absolute z-20 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg">
                                <div className="p-2 border-b border-gray-100">
                                  <input
                                    type="text"
                                    value={breedSearch}
                                    onChange={(event) => setBreedSearch(event.target.value)}
                                    placeholder={`Search ${details.type || "pet"} breeds`}
                                    className={fieldBase}
                                    autoFocus
                                  />
                                </div>
                                <div className="max-h-56 overflow-auto">
                                  {filteredBreedOptions.length ? (
                                    filteredBreedOptions.map((option) => (
                                      <button
                                        key={option.value}
                                        type="button"
                                        onClick={() => {
                                          updateField("breed", option.value);
                                          setBreedDropdownOpen(false);
                                          setBreedSearch("");
                                        }}
                                        className={`w-full px-4 py-2 text-left text-sm hover:bg-gray-50 ${
                                          details.breed === option.value
                                            ? "bg-gray-50 font-semibold text-gray-900"
                                            : "text-gray-700"
                                        }`}
                                      >
                                        {option.label}
                                      </button>
                                    ))
                                  ) : (
                                    <div className="px-4 py-2 text-sm text-gray-500">No breeds found</div>
                                  )}
                                </div>
                              </div>
                            ) : null}
                          </div>
                          {breedError ? (
                            <p className="text-xs text-amber-600 flex items-center gap-1 mt-1">
                              <AlertCircle size={12} />
                              {breedError}
                            </p>
                          ) : null}
                        </div>
                      ) : null}

                      {showExoticTypeField ? (
                        <div className="space-y-2">
                          <label className="block text-sm font-medium text-gray-700">
                            Which pet? <span className="text-red-500">*</span>
                          </label>
                          <div className="relative">
                            <Rabbit size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input
                              type="text"
                              value={details.exoticType}
                              onChange={(event) => updateField("exoticType", event.target.value)}
                              placeholder="e.g. Parrot, Rabbit, Turtle"
                              className={`${fieldBase} pl-12 md:pl-12`}
                            />
                          </div>
                        </div>
                      ) : null}

                      {showPetDobField || showWeightField ? (
                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2 md:gap-6">
                          {showPetDobField ? (
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Pet's Date of Birth <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <Calendar size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                <input
                                  type="date"
                                  max={todayISO()}
                                  value={details.petDob}
                                  onChange={(event) => updateField("petDob", event.target.value)}
                                  className={`${fieldBase} pl-12 md:pl-12`}
                                />
                              </div>
                              {approxAge ? <p className="text-xs text-gray-500">Approx age: {approxAge}</p> : null}
                            </div>
                          ) : null}

                          {showWeightField ? (
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">Weight (kg)</label>
                              <div className="relative">
                                <Scale size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                <input
                                  type="number"
                                  min="0"
                                  step="0.1"
                                  value={details.weightKg}
                                  onChange={(event) => updateField("weightKg", event.target.value)}
                                  placeholder="Optional"
                                  className={`${fieldBase} pl-12 md:pl-12`}
                                />
                              </div>
                            </div>
                          ) : null}
                        </div>
                      ) : null}
                    </div>
                  </section>
                ) : null}

                {showConcernSection ? (
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <Activity size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">Current concern</h3>
                        <p className="text-xs text-gray-500">Share what your pet is experiencing today</p>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      {showProblemTextField ? (
                        <div className="space-y-2">
                          <label className="block text-sm font-medium text-gray-700">
                            Describe the issue <span className="text-red-500">*</span>
                          </label>
                          <textarea
                            value={details.problemText}
                            onChange={(event) => updateField("problemText", event.target.value)}
                            placeholder="Tell us what symptoms you noticed, when they started, and anything important that changed."
                            className={textareaBase}
                          />
                          <div className="flex items-center justify-between text-xs">
                            <span className="text-gray-500">More detail helps us submit the request correctly.</span>
                            <span className={visibleProblemTextLength > 10 ? "text-emerald-600" : "text-gray-400"}>
                              {visibleProblemTextLength}/10+ characters
                            </span>
                          </div>
                        </div>
                      ) : null}

                      {showEnergyField || showAppetiteField || showMoodField ? (
                        <div className="grid grid-cols-1 gap-5 md:grid-cols-3 md:gap-6">
                          {showEnergyField ? (
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">Energy <span className="text-red-500">*</span></label>
                              <div className="relative">
                                <Activity size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                <select value={details.lastDaysEnergy} onChange={(event) => updateField("lastDaysEnergy", event.target.value)} className={`${selectBase} pl-12 md:pl-12`}>
                                  <option value="">Select energy level</option>
                                  {ENERGY_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                              </div>
                            </div>
                          ) : null}

                          {showAppetiteField ? (
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">Appetite <span className="text-red-500">*</span></label>
                              <div className="relative">
                                <Coffee size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                <select value={details.lastDaysAppetite} onChange={(event) => updateField("lastDaysAppetite", event.target.value)} className={`${selectBase} pl-12 md:pl-12`}>
                                  <option value="">Select appetite</option>
                                  {APPETITE_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                              </div>
                            </div>
                          ) : null}

                          {showMoodField ? (
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">Mood <span className="text-red-500">*</span></label>
                              <div className="relative">
                                <Heart size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                <select value={details.mood} onChange={(event) => updateField("mood", event.target.value)} className={`${selectBase} pl-12 md:pl-12`}>
                                  <option value="">Select mood</option>
                                  {MOOD_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                              </div>
                            </div>
                          ) : null}
                        </div>
                      ) : null}
                    </div>
                  </section>
                ) : null}

                {showMedicalSection ? (
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <Shield size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">Medical history</h3>
                        <p className="text-xs text-gray-500">Quick health checks before submission</p>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      <div className="grid grid-cols-1 gap-5 md:grid-cols-3 md:gap-6">
                        {showIsNeuteredField ? (
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-gray-700">Is your pet neutered? <span className="text-red-500">*</span></label>
                            <div className="relative">
                              <select value={details.isNeutered} onChange={(event) => updateField("isNeutered", event.target.value)} className={selectBase}>
                                <option value="">Select</option>
                                {YES_NO_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                              </select>
                              <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            </div>
                          </div>
                        ) : null}

                        {showVaccinatedField ? (
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-gray-700">Vaccinated? <span className="text-red-500">*</span></label>
                            <div className="relative">
                              <select value={details.vaccinatedYesNo} onChange={(event) => updateField("vaccinatedYesNo", event.target.value)} className={selectBase}>
                                <option value="">Select</option>
                                {YES_NO_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                              </select>
                              <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            </div>
                          </div>
                        ) : null}

                        {showDewormingField ? (
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-gray-700">Dewormed? <span className="text-red-500">*</span></label>
                            <div className="relative">
                              <select value={details.dewormingYesNo} onChange={(event) => updateField("dewormingYesNo", event.target.value)} className={selectBase}>
                                <option value="">Select</option>
                                {YES_NO_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                              </select>
                              <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            </div>
                          </div>
                        ) : null}
                      </div>
                    </div>
                  </section>
                ) : null}

                <section className={cardBase}>
                  <div className={cardHeaderBase}>
                    <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                      <Camera size={20} className="text-[#3998de]" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900 text-base">Photo or document</h3>
                      <p className="text-xs text-gray-500">Add a clear photo or report before payment</p>
                    </div>
                  </div>

                  <div className={cardBodyBase}>
                    <div className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900">
                      <CheckCircle2 size={14} className="mt-0.5 text-emerald-600" />
                      <p>A clear photo helps the request get reviewed faster.</p>
                    </div>

                    {canReuseExistingSubmission && !uploadFile ? (
                      <div className="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-900">
                        A previously submitted file is already attached. Upload again only if you want to replace it.
                      </div>
                    ) : null}

                    <label
                      htmlFor="petUploadGallery"
                      className={[
                        "flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200 md:h-44",
                        isDragging ? "border-[#3998de] bg-[#3998de]/5 ring-4 ring-[#3998de]/10" : "border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-gray-400",
                        details.hasPhoto && uploadFile ? "bg-emerald-50/30 border-emerald-300" : "",
                      ].join(" ")}
                      onDragEnter={(event) => {
                        event.preventDefault();
                        setIsDragging(true);
                      }}
                      onDragOver={(event) => event.preventDefault()}
                      onDragLeave={() => setIsDragging(false)}
                      onDrop={async (event) => {
                        event.preventDefault();
                        setIsDragging(false);
                        const file = event.dataTransfer?.files?.[0];
                        if (file) await applyUploadFile(file);
                      }}
                    >
                      <div className="flex flex-col items-center justify-center pt-5 pb-6">
                        {details.hasPhoto && uploadFile ? (
                          <>
                            <CheckCircle2 className="w-10 h-10 text-emerald-500 mb-3 md:w-12 md:h-12" />
                            <p className="mb-1 text-sm text-gray-700 font-medium md:text-base">1 file attached successfully</p>
      </>
                        ) : (
                          <>
                            <Upload className="w-10 h-10 text-[#3998de] mb-3 md:w-12 md:h-12" />
                            <p className="mb-1 text-sm text-gray-700 font-medium md:text-base">{isDragging ? "Drop to upload" : "Upload photo or document"}</p>
                          </>
                        )}
                        <p className="text-xs text-gray-500 md:text-sm">{isDragging ? "Release to start upload" : "Drag & drop or click to browse"}</p>
                        <p className="text-xs text-gray-400 mt-1">Supports JPG, PNG, PDF (max 50MB)</p>
                      </div>
                    </label>

                    <div className="mt-3 flex flex-wrap items-center gap-2">
                      <input id="petUploadCamera" type="file" className="hidden" onChange={async (event) => { const file = event.target.files?.[0]; if (file) await applyUploadFile(file); }} accept="image/*" capture="environment" />
                      <label htmlFor="petUploadCamera" className="inline-flex items-center gap-2 rounded-full border border-[#3998de]/30 bg-white px-3 py-1.5 text-xs font-semibold text-[#3998de] shadow-sm transition hover:border-[#3998de]/60">
                        <Camera className="h-4 w-4" />
                        Camera
                      </label>

                      <input id="petUploadGallery" type="file" className="hidden" onChange={async (event) => { const file = event.target.files?.[0]; if (file) await applyUploadFile(file); }} accept="image/*,.pdf" />
                      <label htmlFor="petUploadGallery" className="inline-flex items-center gap-2 rounded-full border border-[#3998de]/30 bg-white px-3 py-1.5 text-xs font-semibold text-[#3998de] shadow-sm transition hover:border-[#3998de]/60">
                        <Upload className="h-4 w-4" />
                        Gallery
                      </label>
                    </div>

                    {uploadFile ? (
                      <div className="mt-4 bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div className="flex items-start gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-[#3998de] shadow-sm">{uploadIcon}</div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-start justify-between gap-3">
                              <div>
                                <p className="text-sm font-semibold text-gray-900 truncate max-w-[200px] md:max-w-xs">{uploadFile.name}</p>
                                <p className="text-xs text-gray-500 mt-0.5">
                                  {uploadKind === "image" ? "Image" : uploadKind === "pdf" ? "PDF" : "File"} - {(uploadFile.size / 1024 / 1024).toFixed(2)} MB
                                  {uploadMeta?.compressedSize ? <span className="text-emerald-600 ml-1">{" -> "}{(uploadMeta.compressedSize / 1024 / 1024).toFixed(2)} MB</span> : null}
                                </p>
                              </div>
                              <button
                                type="button"
                                onClick={() => {
                                  if (uploadPreviewUrl) URL.revokeObjectURL(uploadPreviewUrl);
                                  setUploadFile(null);
                                  setUploadPreviewUrl("");
                                  setUploadMeta(null);
                                  setHasChangesSinceSubmit(true);
                                  setDetails((current) => ({ ...current, hasPhoto: false }));
                                }}
                                className="text-xs font-medium text-red-600 hover:text-red-700 hover:underline"
                              >
                                Replace file
                              </button>
                            </div>
                          </div>
                        </div>

                        {uploadPreviewUrl && uploadKind === "image" ? (
                          <div className="mt-3">
                            <img src={uploadPreviewUrl} alt="Upload preview" className="w-full max-h-48 object-contain rounded-lg border border-gray-200 bg-white" />
                          </div>
                        ) : null}
                      </div>
                    ) : null}
                    
                  </div>
                </section>

                <div>

                  {submitError ? (
                    <div className="mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700">
                      <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
                      <p className="text-sm">{submitError}</p>
                    </div>
                  ) : null}

                  <Button
                    onClick={handleSubmitClick}
                    disabled={!isValid || submitting}
                    title={!isValid ? submitTooltip : undefined}
                    className={`hidden w-full rounded-xl text-sm font-semibold md:inline-flex md:py-3.5 ${
                      !isValid || submitting
                        ? "cursor-not-allowed bg-slate-300 text-white opacity-50 hover:bg-slate-300"
                        : "bg-[linear-gradient(135deg,#1457ff_0%,#2563eb_55%,#5b8dff_100%)] text-white shadow-[0_20px_45px_-22px_rgba(20,87,255,0.7)]"
                    }`}
                  >
                    {submitting ? (
                      <span className="flex items-center justify-center gap-2">
                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        Submitting...
                      </span>
                    ) : (
                      primaryButtonLabel
                    )}
                  </Button>

                  <div className="mt-4 hidden flex-wrap items-center justify-center gap-2 text-[11px] text-slate-500 md:flex">
                    <div className="flex items-center gap-1 rounded-full border border-[#e4ecff] bg-[#f8fbff] px-3 py-1">
                      <Shield size={12} className="text-[#2457ff]" />
                      SSL secured
                    </div>
                    <div className="rounded-full border border-[#e4ecff] bg-[#f8fbff] px-3 py-1">
                      Razorpay
                    </div>
                    <div className="rounded-full border border-[#e4ecff] bg-[#f8fbff] px-3 py-1">
                      UPI / Card / Net Banking
                    </div>
                  </div>

                  {!isValid ? (
                    <div className="mt-4 hidden rounded-2xl border border-amber-200 bg-amber-50 p-3 md:block">
                      <p className="text-xs text-amber-700 flex items-center gap-1.5">
                        <AlertCircle size={14} />
                        {submitTooltip}
                      </p>
                    </div>
                  ) : submitting ? (
                    <p className="mt-4 hidden text-center text-sm text-gray-500 md:block">Uploading your files...</p>
                  ) : (
                    <p className="mt-4 hidden text-center text-sm text-slate-500 md:block">
                      All details are ready. Continue to payment.
                    </p>
                  )}
                </div>

                <div className="h-24 md:hidden" />
              </div>
            </div>
          </div>
        </div>

        <div className="fixed bottom-0 left-0 right-0 z-20 mx-auto max-w-md border-t border-[#d6e3ff] bg-white/95 p-4 shadow-[0_-18px_45px_-30px_rgba(37,99,235,0.35)] backdrop-blur safe-area-pb md:hidden">
          <div className="space-y-2">
            <Button
              onClick={handleSubmitClick}
              fullWidth
              disabled={!isValid || submitting}
              className={
                !isValid || submitting
                  ? "cursor-not-allowed bg-slate-300 text-white opacity-50"
                  : "bg-[linear-gradient(135deg,#1457ff_0%,#2563eb_55%,#5b8dff_100%)] text-white shadow-[0_20px_45px_-22px_rgba(20,87,255,0.7)]"
              }
            >
              {submitting ? "Submitting..." : primaryButtonLabel}
            </Button>
            {!isValid ? (
              <p className="text-xs text-red-600 text-center px-2">
                {submitTooltip}
              </p>
            ) : null}
          </div>
        </div>
      </div>
    </div>
  );
}
