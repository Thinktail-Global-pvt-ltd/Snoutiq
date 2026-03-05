import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { Helmet } from "react-helmet-async";
import { LPNavbar } from "../newflow/LPNavbar";
import { apiPost, apiBaseUrl } from "../lib/api";
import {
  ArrowRight,
  CheckCircle2,
  ChevronDown,
  Upload,
  Clock,
  ShieldCheck,
  Star,
  Zap,
  Camera,
  FileText,
  Image as ImageIcon,
  User,
  MessageCircle,
  Calendar,
  Activity,
  Coffee,
  Heart,
  PawPrint,
  AlertCircle,
  Dog,
  Cat,
  Rabbit,
  Shield,
  MapPin,
  Scale,
  Lightbulb,
} from "lucide-react";

// ─── SEO constants ───────────────────────────────────────────────────────────
const TITLE = "Online Vet Consultation in India | Talk to Vet Online 24/7";
const DESCRIPTION =
  "Get instant online vet consultation in India. Talk to vet online via WhatsApp video within 15 minutes. Consult vet online India with licensed veterinarians. 24/7 online pet consultation.";
const CANONICAL = "https://www.snoutiq.com/online-vet-consultation-india";
const KEYWORDS = [
  "online vet consultation",
  "talk to vet online",
  "online veterinarian",
  "consult vet online",
  "online pet consultation",
  "best online vet consultation india",
  "vet online",
  "consult vet online india",
  "talk to vet",
].join(", ");

// ─── Helpers ─────────────────────────────────────────────────────────────────
const cn = (...v) => v.filter(Boolean).join(" ");

function getCurrentPrice() {
  const h = new Date().getHours();
  const isDay = h >= 8 && h < 22; // Day rate 8AM–10PM
  return isDay
    ? { price: "₹399", label: "Day rate · 8AM–10PM", rateType: "day" }
    : { price: "₹549", label: "Night rate · 10PM–8AM", rateType: "night" };
}

const todayISO = () => new Date().toISOString().slice(0, 10);

const calcAgeFromDob = (dob) => {
  if (!dob) return "";
  const birth = new Date(dob);
  if (Number.isNaN(birth.getTime())) return "";
  const today = new Date();
  if (birth > today) return "";

  let years = today.getFullYear() - birth.getFullYear();
  let months = today.getMonth() - birth.getMonth();
  let days = today.getDate() - birth.getDate();

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

const formatPhone = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  if (digits.startsWith("91")) return digits;
  return `91${digits}`;
};

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

const normalizeDisplayText = (value) => {
  if (value === undefined || value === null) return "";
  const text = String(value).trim();
  if (!text) return "";
  const lower = text.toLowerCase();
  if (lower === "null" || lower === "undefined" || lower === "[]" || lower === "na" || lower === "n/a") return "";
  return text;
};

const listToDisplayText = (value) => {
  if (Array.isArray(value)) return value.map((i) => normalizeDisplayText(i)).filter(Boolean).join(", ");
  const text = normalizeDisplayText(value);
  if (!text) return "";
  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) return parsed.map((i) => normalizeDisplayText(i)).filter(Boolean).join(", ");
    } catch {
      return text.replace(/^\[|\]$/g, "").replace(/["']/g, "").trim();
    }
  }
  return text;
};

const formatBreedName = (breedKey, subBreed = null) => {
  const cap = (s) =>
    String(s)
      .split(/[-_\s]/)
      .filter(Boolean)
      .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
      .join(" ");

  const base = cap(breedKey);
  if (!subBreed) return base;
  return `${cap(subBreed)} ${base}`;
};

/**
 * ✅ Client-side image compression (web)
 * - Only compresses images (jpeg/png/webp). PDFs are sent as-is.
 * - Returns a NEW File (compressed) to append in FormData.
 */
const compressImageFile = async (
  file,
  { maxWidth = 1280, maxHeight = 1280, quality = 0.72, outputMime = "image/jpeg" } = {}
) => {
  if (!file) return null;
  const isImage = file.type?.startsWith("image/");
  if (!isImage) return file;

  // Some browsers may not support createImageBitmap fully
  const bitmap = await createImageBitmap(file).catch(() => null);
  if (!bitmap) return file;

  let { width, height } = bitmap;
  const ratio = Math.min(maxWidth / width, maxHeight / height, 1);
  const targetW = Math.round(width * ratio);
  const targetH = Math.round(height * ratio);

  const canvas = document.createElement("canvas");
  canvas.width = targetW;
  canvas.height = targetH;

  const ctx = canvas.getContext("2d");
  if (!ctx) return file;

  ctx.drawImage(bitmap, 0, 0, targetW, targetH);

  const blob = await new Promise((resolve) => {
    canvas.toBlob(
      (b) => resolve(b),
      outputMime,
      outputMime === "image/png" ? undefined : quality
    );
  });

  if (!blob) return file;
  if (blob.size >= file.size) return file;

  const ext = outputMime === "image/webp" ? "webp" : "jpg";
  const safeName = (file.name?.replace(/\.[^/.]+$/, "") || "upload") + `_compressed.${ext}`;
  return new File([blob], safeName, { type: outputMime });
};

// ─── Form constants (same as PetDetailsScreen) ───────────────────────────────
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

// Professional input styling (same idea as PetDetailsScreen)
const fieldBase =
  "w-full rounded-xl border border-gray-200 bg-white p-3 text-gray-900 placeholder:text-gray-400 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand hover:border-gray-300 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed md:rounded-2xl md:p-3.5 md:text-[15px]";
const selectBase = `${fieldBase} appearance-none pr-12`;
const textareaBase = `${fieldBase} resize-none min-h-[120px]`;
const cardBase = "rounded-2xl border border-gray-200 bg-white overflow-hidden";
const cardHeaderBase = "flex items-center gap-3 border-b border-gray-100 px-4 py-3.5";
const cardBodyBase = "px-4 py-4 space-y-3.5";

// ─── Main ────────────────────────────────────────────────────────────────────
export default function VideoConsultLP() {
  const { price, label: priceLabel, rateType } = getCurrentPrice();
  const scrollToConsultForm = useCallback(() => {
    const formNode = document.getElementById("consult-form");
    if (!formNode) return;

    const topOffset = 96;
    const targetTop = formNode.getBoundingClientRect().top + window.scrollY - topOffset;
    window.scrollTo({ top: Math.max(0, targetTop), behavior: "smooth" });

    if (window.location.hash !== "#consult-form") {
      const nextUrl = `${window.location.pathname}${window.location.search}#consult-form`;
      window.history.replaceState(window.history.state, "", nextUrl);
    }
  }, []);

  const goToPetDetails = useCallback(() => {
    window.location.assign("/20+vetsonline?start=details");
  }, []);

  // JSON-LD Schemas
  const serviceSchema = useMemo(
    () => ({
      "@context": "https://schema.org",
      "@type": "MedicalService",
      name: "Online Vet Consultation India",
      serviceType: "Veterinary Telemedicine",
      provider: {
        "@type": "Organization",
        name: "SnoutiQ",
        url: "https://www.snoutiq.com",
      },
      areaServed: { "@type": "Country", name: "India" },
      availableChannel: {
        "@type": "ServiceChannel",
        serviceLocation: {
          "@type": "VirtualLocation",
          url: CANONICAL,
        },
      },
      offers: {
        "@type": "Offer",
        priceCurrency: "INR",
        price: "399",
        availability: "https://schema.org/InStock",
        description: "Day ₹399 (8AM–10PM), Night ₹549 (10PM–8AM)",
      },
    }),
    []
  );

  const faqSchema = useMemo(
    () => ({
      "@context": "https://schema.org",
      "@type": "FAQPage",
      mainEntity: [
        {
          "@type": "Question",
          name: "How does online vet consultation work?",
          acceptedAnswer: {
            "@type": "Answer",
            text: "You receive a WhatsApp video call within 15 minutes after payment. The vet reviews your pet details before calling.",
          },
        },
        {
          "@type": "Question",
          name: "Is online vet consultation available across India?",
          acceptedAnswer: {
            "@type": "Answer",
            text: "Yes, SnoutiQ provides 24/7 online vet consultation across India.",
          },
        },
      ],
    }),
    []
  );

  // ✅ NEW FORM (fields + functionality from PetDetailsScreen)
  const [step, setStep] = useState(1); // 1 Owner | 2 Pet | 3 Problem+Upload
  const [submitted, setSubmitted] = useState(false);
  const [submitPayload, setSubmitPayload] = useState(null);

  const [details, setDetails] = useState({
    ownerName: "",
    ownerMobile: "",
    city: "",
    name: "",
    type: null, // dog | cat | exotic
    breed: "",
    petDob: "",
    gender: "",
    problemText: "",
    mood: "calm",
    petDoc2: "",
    exoticType: "",
    lastDaysEnergy: "",
    lastDaysAppetite: "",
    hasPhoto: false,
    isNeutered: "",
    vaccinatedYesNo: "",
    dewormingYesNo: "",
    weightKg: "",
  });

  // Upload
  const [uploadFile, setUploadFile] = useState(null);
  const [uploadPreviewUrl, setUploadPreviewUrl] = useState("");
  const [uploadMeta, setUploadMeta] = useState(null);
  const [isDragging, setIsDragging] = useState(false);

  // Breed
  const [dogBreeds, setDogBreeds] = useState([]);
  const [catBreeds, setCatBreeds] = useState([]);
  const [breedSearch, setBreedSearch] = useState("");
  const [breedDropdownOpen, setBreedDropdownOpen] = useState(false);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [breedError, setBreedError] = useState("");
  const breedDropdownRef = useRef(null);

  // OTP
  const [otpToken, setOtpToken] = useState("");
  const [otpValue, setOtpValue] = useState("");
  const [otpStatus, setOtpStatus] = useState("idle"); // idle | sending | sent | verifying | verified | error
  const [otpMessage, setOtpMessage] = useState("");
  const [otpError, setOtpError] = useState("");
  const [otpCooldown, setOtpCooldown] = useState(0);
  const [otpPhone, setOtpPhone] = useState("");
  const otpInputRef = useRef(null);

  // Live status (same endpoint as PetDetailsScreen)
  const [liveDoctorCount, setLiveDoctorCount] = useState(null);

  const resetOtpState = useCallback(() => {
    setOtpToken("");
    setOtpValue("");
    setOtpStatus("idle");
    setOtpMessage("");
    setOtpError("");
    setOtpCooldown(0);
    setOtpPhone("");
  }, []);

  const ownerPhoneDigits = details.ownerMobile.replace(/\D/g, "");
  const otpVerified = otpStatus === "verified";
  const showOtpSection = ownerPhoneDigits.length === 10 || otpStatus !== "idle";

  // OTP cooldown timer
  useEffect(() => {
    if (otpCooldown <= 0) return;
    const timer = window.setTimeout(() => {
      setOtpCooldown((prev) => Math.max(0, prev - 1));
    }, 1000);
    return () => window.clearTimeout(timer);
  }, [otpCooldown]);

  // Reset OTP if phone changes
  useEffect(() => {
    const digits = ownerPhoneDigits;
    if (digits.length !== 10) {
      if (otpStatus !== "idle") resetOtpState();
      return;
    }
    if (otpPhone && digits !== otpPhone) resetOtpState();
  }, [ownerPhoneDigits, otpPhone, otpStatus, resetOtpState]);

  // Outside click for breed dropdown
  useEffect(() => {
    if (!breedDropdownOpen) return;
    const handleClick = (event) => {
      if (breedDropdownRef.current && !breedDropdownRef.current.contains(event.target)) {
        setBreedDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, [breedDropdownOpen]);

  // Live vets count
  useEffect(() => {
    let active = true;
    const abortController = new AbortController();
    let idleHandle = null;

    const fetchLiveStatus = async () => {
      try {
        const baseUrl = apiBaseUrl();
        const res = await fetch(`${baseUrl}/api/doctors/availability-status`, {
          method: "GET",
          signal: abortController.signal,
        });
        const data = await res.json();
        if (!active) return;

        const onlineFromCounts = Number.isFinite(data?.counts?.online_doctors) ? data.counts.online_doctors : null;
        const onlineFromList = Array.isArray(data?.online_doctors) ? data.online_doctors.length : null;
        const onlineValue = onlineFromCounts !== null ? onlineFromCounts : onlineFromList;

        setLiveDoctorCount(onlineValue);
      } catch {
        if (active) setLiveDoctorCount(null);
      }
    };

    const runDeferredFetch = () => {
      void fetchLiveStatus();
    };

    if (typeof window !== "undefined" && "requestIdleCallback" in window) {
      idleHandle = window.requestIdleCallback(runDeferredFetch, { timeout: 1200 });
    } else {
      idleHandle = window.setTimeout(runDeferredFetch, 350);
    }

    return () => {
      active = false;
      abortController.abort();
      if (typeof window !== "undefined") {
        if ("cancelIdleCallback" in window && idleHandle !== null) {
          window.cancelIdleCallback(idleHandle);
        } else if (idleHandle !== null) {
          window.clearTimeout(idleHandle);
        }
      }
    };
  }, []);

  // Breed fetch (dog/cat) on type change
  useEffect(() => {
    const abortController = new AbortController();

    const fetchDogBreeds = async () => {
      setBreedError("");
      setLoadingBreeds(true);

      try {
        const baseUrl = apiBaseUrl();
        const res = await fetch(`${baseUrl}/api/dog-breeds/all`, {
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
            } else {
              list.push({ label: formatBreedName(breedKey), value: breedKey });
              subBreeds.forEach((sub) => {
                list.push({ label: formatBreedName(breedKey, sub), value: `${breedKey}/${sub}` });
              });
            }
          });

          list.sort((a, b) => a.label.localeCompare(b.label));
          list.push({ label: "Mixed Breed", value: "mixed_breed" }, { label: "Other", value: "other" });
          setDogBreeds(list);
        } else {
          setDogBreeds([{ label: "Mixed Breed", value: "mixed_breed" }, { label: "Other", value: "other" }]);
          setBreedError("Could not load breeds (using defaults).");
        }
      } catch (error) {
        if (error?.name === "AbortError") return;
        setDogBreeds([{ label: "Mixed Breed", value: "mixed_breed" }, { label: "Other", value: "other" }]);
        setBreedError("Network error while loading breeds.");
      } finally {
        setLoadingBreeds(false);
      }
    };

    const fetchCatBreeds = async () => {
      setBreedError("");
      setLoadingBreeds(true);

      try {
        const baseUrl = apiBaseUrl();
        const res = await fetch(`${baseUrl}/api/cat-breeds/with-indian`, {
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

          list.sort((a, b) => a.label.localeCompare(b.label));
          list.push({ label: "Mixed / Other", value: "other" });
          setCatBreeds(list);
        } else {
          setCatBreeds([{ label: "Mixed / Other", value: "other" }]);
          setBreedError("Could not load cat breeds (using defaults).");
        }
      } catch (error) {
        if (error?.name === "AbortError") return;
        setCatBreeds([{ label: "Mixed / Other", value: "other" }]);
        setBreedError("Network error while loading cat breeds.");
      } finally {
        setLoadingBreeds(false);
      }
    };

    if (details.type === "dog") fetchDogBreeds();
    else if (details.type === "cat") fetchCatBreeds();
    else {
      setBreedError("");
      setLoadingBreeds(false);
    }

    if (details.type !== "dog") setDogBreeds([]);
    if (details.type !== "cat") setCatBreeds([]);

    setBreedSearch("");
    setBreedDropdownOpen(false);

    if (details.type === "exotic") setDetails((p) => ({ ...p, breed: "" }));
    else setDetails((p) => ({ ...p, exoticType: "" }));

    return () => {
      abortController.abort();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [details.type]);

  // Upload preview cleanup
  useEffect(() => {
    return () => {
      if (uploadPreviewUrl) {
        try {
          URL.revokeObjectURL(uploadPreviewUrl);
        } catch {}
      }
    };
  }, [uploadPreviewUrl]);

  const breedOptions = useMemo(() => {
    if (details.type === "dog") return dogBreeds;
    if (details.type === "cat") return catBreeds;
    return [];
  }, [details.type, dogBreeds, catBreeds]);

  const filteredBreedOptions = useMemo(() => {
    const term = breedSearch.trim().toLowerCase();
    if (!term) return breedOptions;
    const filtered = breedOptions.filter((b) => String(b?.label || "").toLowerCase().includes(term));
    if (details.breed && !filtered.some((b) => b.value === details.breed)) {
      const selected = breedOptions.find((b) => b.value === details.breed);
      if (selected) return [selected, ...filtered];
    }
    return filtered;
  }, [breedOptions, breedSearch, details.breed]);

  const selectedBreedLabel = useMemo(() => {
    if (!details.breed) return "";
    return breedOptions.find((b) => b.value === details.breed)?.label || "";
  }, [details.breed, breedOptions]);

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
    if (uploadKind === "image") return <ImageIcon className="w-4 h-4" />;
    return <FileText className="w-4 h-4" />;
  }, [uploadKind]);

  const uploadLabel = useMemo(() => {
    if (uploadKind === "image") return "Image";
    if (uploadKind === "pdf") return "PDF";
    return "File";
  }, [uploadKind]);

  const applyUploadFile = useCallback(
    async (file) => {
      if (!file) return;

      // cleanup old preview
      if (uploadPreviewUrl) {
        try {
          URL.revokeObjectURL(uploadPreviewUrl);
        } catch {}
      }

      const lowerName = file.name?.toLowerCase() || "";
      const isVideo = file.type?.startsWith("video/") || /\.(mp4|mov|avi|mkv|webm)$/i.test(lowerName);
      if (isVideo) {
        alert("Video uploads are not supported. Please upload a photo or PDF.");
        return;
      }

      const isImage = file.type?.startsWith("image/");
      const isPdf = file.type === "application/pdf" || lowerName.endsWith(".pdf");
      if (!isImage && !isPdf) {
        alert("Please upload a JPG, PNG, or PDF file.");
        return;
      }

      if (isImage) {
        const url = URL.createObjectURL(file);
        setUploadPreviewUrl(url);
      } else {
        setUploadPreviewUrl("");
      }

      setUploadFile(file);
      setDetails((prev) => ({ ...prev, hasPhoto: true }));
      setUploadMeta({ name: file.name, size: file.size, type: file.type, compressedSize: null });
    },
    [uploadPreviewUrl]
  );

  const handlePhotoUpload = async (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    await applyUploadFile(f);
    // reset input value so same file can be re-selected
    e.target.value = "";
  };

  const handleDrop = async (e) => {
    e.preventDefault();
    setIsDragging(false);
    const f = e.dataTransfer?.files?.[0];
    if (!f) return;
    await applyUploadFile(f);
  };

  const handleDragOver = (e) => e.preventDefault();
  const handleDragEnter = (e) => {
    e.preventDefault();
    setIsDragging(true);
  };
  const handleDragLeave = () => setIsDragging(false);

  const fetchGeo = () =>
    new Promise((resolve) => {
      if (!navigator.geolocation) return resolve(null);
      navigator.geolocation.getCurrentPosition(
        (pos) =>
          resolve({
            lat: pos.coords.latitude,
            lang: pos.coords.longitude,
          }),
        () => resolve(null),
        { timeout: 4000 }
      );
    });

  const sendOtp = async () => {
    const phone = ownerPhoneDigits;
    if (phone.length !== 10) {
      setOtpError("Enter a valid 10-digit mobile number first.");
      return;
    }

    setOtpError("");
    setOtpMessage("");
    setOtpStatus("sending");

    try {
      const data = await apiPost("/api/send-otp", { type: "whatsapp", value: phone });
      if (!data?.token) throw new Error("OTP token missing. Please try again.");

      setOtpToken(data.token);
      setOtpPhone(phone);
      setOtpStatus("sent");
      setOtpMessage(data?.message || "OTP sent to your WhatsApp.");
      setOtpCooldown(30);
      setOtpValue("");
      window.setTimeout(() => otpInputRef.current?.focus(), 100);
    } catch (err) {
      setOtpStatus("error");
      setOtpError(err?.message || "Failed to send OTP.");
    }
  };

  const verifyOtp = async () => {
    if (!otpToken) {
      setOtpError("Send OTP first.");
      return;
    }
    if (otpValue.trim().length < 4) {
      setOtpError("Enter the 4-digit OTP.");
      return;
    }

    setOtpError("");
    setOtpMessage("");
    setOtpStatus("verifying");

    try {
      const location = await fetchGeo();
      const payload = { token: otpToken, otp: otpValue.trim(), phone: ownerPhoneDigits };
      if (location) {
        payload.lat = location.lat;
        payload.lang = location.lang;
      }

      const data = await apiPost("/api/verify-otp", payload);
      if (data?.success === false) throw new Error(data?.error || data?.message || "OTP verification failed.");

      setOtpStatus("verified");
      setOtpMessage("Mobile number verified.");
      setOtpError("");
    } catch (err) {
      setOtpStatus("error");
      setOtpError(err?.message || "OTP verification failed.");
    }
  };

  const otpSendDisabled =
    otpStatus === "sending" ||
    otpStatus === "verifying" ||
    otpCooldown > 0 ||
    ownerPhoneDigits.length !== 10;

  const otpVerifyDisabled =
    otpStatus === "verifying" ||
    otpValue.trim().length < 4 ||
    !otpToken ||
    ownerPhoneDigits.length !== 10;

  // Validation per step
  const step1Valid =
    details.ownerName.trim().length > 0 &&
    ownerPhoneDigits.length === 10 &&
    details.city.trim().length > 1 &&
    otpVerified;

  const step2Valid =
    details.name.trim().length > 0 &&
    details.type !== null &&
    details.petDob &&
    details.gender &&
    (!showBreed || details.breed) &&
    (!isExotic || details.exoticType.trim().length > 0);

  const step3Valid =
    details.problemText.trim().length > 10 &&
    details.lastDaysEnergy &&
    details.lastDaysAppetite &&
    details.mood &&
    details.hasPhoto &&
    !!uploadFile;

  const isValidAll = step1Valid && step2Valid && step3Valid;

  const getSubmitTooltip = () => {
    if (!details.ownerName.trim()) return "Please enter owner name";
    if (ownerPhoneDigits.length !== 10) return "Please enter 10-digit mobile number";
    if (!details.city.trim()) return "Please enter city name";
    if (!otpVerified) return "Please verify mobile number with OTP";
    if (!details.name.trim()) return "Please enter your pet's name";
    if (!details.type) return "Please select pet type";
    if (!details.gender) return "Please select pet gender";
    if (isExotic && !details.exoticType.trim()) return "Please specify your exotic pet type";
    if (showBreed && !details.breed) return "Please select breed";
    if (!details.petDob) return "Please select pet's date of birth";
    if (details.problemText.trim().length <= 10) return "Please describe the problem in detail (minimum 10 characters)";
    if (!details.lastDaysEnergy) return "Please select energy level";
    if (!details.lastDaysAppetite) return "Please select appetite level";
    if (!details.mood) return "Please select mood";
    if (!details.hasPhoto || !uploadFile) return "Please upload a photo or PDF";
    return "";
  };

  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");

  const submitObservation = async () => {
    setSubmitError("");

    if (!isValidAll) {
      setSubmitError(getSubmitTooltip() || "Please complete all fields.");
      return;
    }

    setSubmitting(true);

    try {
      let fileToSend = uploadFile;

      if (uploadFile?.type?.startsWith("image/")) {
        const compressed = await compressImageFile(uploadFile, {
          maxWidth: 1280,
          maxHeight: 1280,
          quality: 0.72,
          outputMime: "image/jpeg",
        });

        fileToSend = compressed;

        setUploadMeta((prev) => (prev ? { ...prev, compressedSize: compressed?.size ?? null } : prev));
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

      const breedValue = details.type === "exotic" ? details.exoticType.trim() : details.breed || "";
      fd.append("breed", breedValue);

      fd.append("reported_symptom", details.problemText || "");
      fd.append("appetite", details.lastDaysAppetite || "");
      fd.append("energy", details.lastDaysEnergy || "");
      fd.append("mood", details.mood || "calm");

      if (details.isNeutered !== "") fd.append("is_neutered", details.isNeutered);
      if (details.vaccinatedYesNo !== "") fd.append("vaccenated_yes_no", details.vaccinatedYesNo);
      if (details.dewormingYesNo !== "") fd.append("deworming_yes_no", details.dewormingYesNo);
      if (details.petDoc2?.trim()) fd.append("pet_doc2", details.petDoc2.trim());
      if (fileToSend) fd.append("file", fileToSend);

      const baseUrl = apiBaseUrl();
      const res = await fetch(`${baseUrl}/api/user-pet-observation`, { method: "POST", body: fd });

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
          data?.data?.user?.id
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
          data?.data?.pet?.id
        )
      );

      const nextPayload = { ...details, observation, observationResponse: data, user_id: userId, pet_id: petId };
      setSubmitPayload(nextPayload);
      setSubmitted(true);

      // Optional: scroll to top so user sees success immediately
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (e) {
      setSubmitError(e?.message || "Something went wrong. Please try again.");
    } finally {
      setSubmitting(false);
    }
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

  return (
    <div className="min-h-screen bg-white flex flex-col">
      <Helmet>
        <title>{TITLE}</title>
        <meta name="description" content={DESCRIPTION} />
        <meta name="keywords" content={KEYWORDS} />
        <link rel="canonical" href={CANONICAL} />

        {/* OpenGraph */}
        <meta property="og:type" content="website" />
        <meta property="og:title" content={TITLE} />
        <meta property="og:description" content={DESCRIPTION} />
        <meta property="og:url" content={CANONICAL} />
        <meta property="og:site_name" content="SnoutiQ" />

        {/* Twitter */}
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content={TITLE} />
        <meta name="twitter:description" content={DESCRIPTION} />

        {/* JSON-LD */}
        <script type="application/ld+json">{JSON.stringify(serviceSchema)}</script>
        <script type="application/ld+json">{JSON.stringify(faqSchema)}</script>
      </Helmet>

      <LPNavbar consultPath="#consult-form" onConsultClick={scrollToConsultForm} />

      {/* ── LIVE STATUS BAR ─────────────────────────────────────────────── */}
      <div className="bg-gradient-to-r from-green-50 to-emerald-50 border-b border-green-200 py-2 px-4">
        <p className="text-xs font-bold text-green-700 flex items-center justify-center gap-2 flex-wrap">
          <span className="flex items-center gap-1.5">
            <span className="h-2 w-2 rounded-full bg-green-500 animate-pulse" />
            {liveDoctorCount === null
              ? "Checking vets online…"
              : `${liveDoctorCount} ${liveDoctorCount === 1 ? "vet is" : "vets are"} online right now`}
          </span>
          <span className="text-green-400">·</span>
          <span>Average wait: 15 minutes</span>
          <span className="text-green-400">·</span>
          <span>Available 24/7 · All India</span>
        </p>
      </div>

      <main className="flex-1 pb-24 md:pb-0">
        {/* ══ HERO + FORM ═══════════════════════════════════════════════════ */}
        <section className="relative overflow-hidden bg-gradient-to-b from-[#f0f7ff] to-white px-4 pt-8 pb-14 sm:px-6 lg:px-8">
          <div className="pointer-events-none absolute inset-0 overflow-hidden">
            <div className="absolute top-0 right-0 h-72 w-72 rounded-full bg-brand/5 blur-3xl -translate-y-1/3 translate-x-1/4" />
            <div className="absolute bottom-0 left-0 h-48 w-48 rounded-full bg-orange-100/40 blur-2xl" />
          </div>

          <div className="relative max-w-6xl mx-auto">
            <div className="flex justify-center mb-5">
              <span className="inline-flex items-center gap-2 bg-white border border-brand/20 text-brand text-xs font-extrabold px-4 py-1.5 rounded-full shadow-sm">
                <Zap className="h-3 w-3" />
                Online Vet Consultation · All India · 24/7
              </span>
            </div>

            <h1 className="text-[2.1rem] sm:text-[2.8rem] lg:text-[3.4rem] font-extrabold text-slate-900 leading-[1.1] text-center mb-3">
              Online Vet Consultation – Talk to a Verified Veterinarian in 15 Minutes
            </h1>

            <p className="text-slate-500 text-center text-base mb-7 max-w-3xl mx-auto leading-relaxed">
              Get instant <strong>online vet consultation</strong> anywhere in India. Talk to vet online via secure WhatsApp
              video call and connect with a licensed <strong>online veterinarian</strong> within 15 minutes. Ideal for
              emergency advice, second opinions and routine <strong>online pet consultation</strong>.
            </p>

            <div className="mt-8 text-center">
              <p className="text-sm text-slate-500">
                Want to understand the complete process, pricing and service details?{" "}
                <a href="/video-consultation-india" className="text-brand font-semibold hover:underline">
                  Visit our detailed Video Consultation page
                </a>
                .
              </p>
            </div>

            {/* ── FORM CARD (UPDATED) ─────────────────────────────────────── */}
            <div id="consult-form" className="scroll-mt-28">
              {!submitted ? (
                <div className="bg-white rounded-3xl shadow-2xl shadow-slate-200/60 border border-slate-100 overflow-hidden mt-6 max-w-5xl mx-auto">
                {/* Step progress strip */}
                <div className="flex border-b border-slate-100">
                  {["Owner", "Pet", "Problem"].map((s, i) => {
                    const n = i + 1;
                    const isActive = step === n;
                    const isDone = step > n;
                    return (
                      <div
                        key={s}
                        className={cn(
                          "flex-1 py-3 text-center text-xs font-extrabold transition-colors border-b-2",
                          isActive
                            ? "text-brand border-brand bg-brand-light/30"
                            : isDone
                            ? "text-green-600 border-green-400 bg-green-50/30"
                            : "text-slate-300 border-transparent"
                        )}
                      >
                        {isDone ? "✓ " : `${n}. `}
                        {s}
                      </div>
                    );
                  })}
                </div>

                <div className="p-5 sm:p-6">
                  {/* STEP 1: OWNER */}
                  {step === 1 && (
                    <div className="space-y-5">
                      <div className={cardBase}>
                        <div className={cardHeaderBase}>
                          <div className="h-9 w-9 rounded-lg bg-brand/10 flex items-center justify-center">
                            <User size={20} className="text-brand" />
                          </div>
                          <div>
                            <h3 className="font-semibold text-gray-900 text-base">Owner details</h3>
                            <p className="text-xs text-gray-500">Used only for appointment updates</p>
                          </div>
                        </div>

                        <div className={cardBodyBase}>
                          <div className="flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                            <Shield size={14} className="mt-0.5 text-blue-600" />
                            <p>Your details are only shared with your assigned vet. We do not use them for marketing.</p>
                          </div>

                          <div className="grid grid-cols-1 gap-5 md:grid-cols-2 md:gap-6">
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Pet Owner Name <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <User
                                  size={18}
                                  className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                />
                                <input
                                  type="text"
                                  value={details.ownerName}
                                  onChange={(e) => setDetails((p) => ({ ...p, ownerName: e.target.value }))}
                                  placeholder="Enter your full name"
                                  className={cn(fieldBase, "pl-12 md:pl-12")}
                                />
                              </div>
                            </div>

                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Pet Owner WhatsApp Mobile <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <MessageCircle
                                  size={18}
                                  className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 z-10 pointer-events-none"
                                />
                                <div className="flex items-center rounded-xl border border-gray-200 bg-white pl-12 shadow-sm transition-all focus-within:ring-2 focus-within:ring-brand/20 focus-within:border-brand">
                                  <span className="text-gray-500 font-medium pr-3 mr-3 border-r border-gray-200 py-3.5 text-sm">
                                    +91
                                  </span>
                                  <input
                                    type="tel"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    value={details.ownerMobile}
                                    onChange={(e) =>
                                      setDetails((p) => ({
                                        ...p,
                                        ownerMobile: e.target.value.replace(/\D/g, "").slice(0, 10),
                                      }))
                                    }
                                    placeholder="Enter mobile number"
                                    className="flex-1 py-3.5 bg-transparent outline-none font-medium text-gray-900 placeholder:text-gray-400"
                                  />
                                </div>
                              </div>

                              <p className="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                <Shield size={12} className="text-brand" />
                                No spam. Only consultation updates.
                              </p>

                              {showOtpSection && (
                                <div className="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                                  <div className="flex items-start justify-between gap-3">
                                    <div className="flex items-start gap-3">
                                      <div
                                        className={cn(
                                          "w-9 h-9 rounded-full flex items-center justify-center",
                                          otpVerified ? "bg-emerald-100 text-emerald-600" : "bg-blue-100 text-blue-600"
                                        )}
                                      >
                                        <Shield size={16} />
                                      </div>
                                      <div>
                                        <p className="text-sm font-semibold text-gray-900">Verify mobile number</p>
                                        <p className="text-xs text-gray-500">
                                          OTP will be sent on WhatsApp to +91 {ownerPhoneDigits}
                                        </p>
                                      </div>
                                    </div>
                                    {otpVerified ? (
                                      <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Verified
                                      </span>
                                    ) : null}
                                  </div>

                                  {!otpVerified && (
                                    <div className="space-y-3">
                                      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <button
                                          type="button"
                                          onClick={sendOtp}
                                          disabled={otpSendDisabled}
                                          className={cn(
                                            "px-4 py-2 rounded-xl text-sm font-semibold transition-colors",
                                            otpSendDisabled
                                              ? "bg-gray-200 text-gray-500 cursor-not-allowed"
                                              : "bg-brand text-white hover:bg-brand/90"
                                          )}
                                        >
                                          {otpStatus === "sending"
                                            ? "Sending..."
                                            : otpCooldown > 0
                                            ? "Resend OTP"
                                            : "Send OTP"}
                                        </button>

                                        <div className="text-xs text-gray-500 flex items-center gap-1">
                                          <Clock size={12} className="text-gray-400" />
                                          {otpCooldown > 0 ? `Resend in ${otpCooldown}s` : "OTP valid for 10 minutes"}
                                        </div>
                                      </div>

                                      <div className="flex flex-col gap-3 md:flex-row md:items-center">
                                        <input
                                          ref={otpInputRef}
                                          type="text"
                                          inputMode="numeric"
                                          pattern="[0-9]*"
                                          value={otpValue}
                                          onChange={(e) => setOtpValue(e.target.value.replace(/\D/g, "").slice(0, 4))}
                                          placeholder="Enter OTP"
                                          className={cn(fieldBase, "md:flex-1 text-center tracking-widest")}
                                        />
                                        <button
                                          type="button"
                                          onClick={verifyOtp}
                                          disabled={otpVerifyDisabled}
                                          className={cn(
                                            "px-4 py-2 rounded-xl text-sm font-semibold transition-colors",
                                            otpVerifyDisabled
                                              ? "bg-gray-200 text-gray-500 cursor-not-allowed"
                                              : "bg-emerald-600 text-white hover:bg-emerald-700"
                                          )}
                                        >
                                          {otpStatus === "verifying" ? "Verifying..." : "Verify OTP"}
                                        </button>
                                      </div>
                                    </div>
                                  )}

                                  {otpMessage ? <p className="text-xs text-emerald-600">{otpMessage}</p> : null}
                                  {otpError ? (
                                    <p className="text-xs text-red-600 flex items-center gap-1">
                                      <AlertCircle size={12} />
                                      {otpError}
                                    </p>
                                  ) : null}
                                </div>
                              )}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                              <label className="block text-sm font-medium text-gray-700">
                                City <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <MapPin
                                  size={18}
                                  className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                />
                                <input
                                  type="text"
                                  value={details.city}
                                  onChange={(e) => setDetails((p) => ({ ...p, city: e.target.value }))}
                                  placeholder="Enter city (e.g. Gurugram)"
                                  className={cn(fieldBase, "pl-12 md:pl-12")}
                                />
                              </div>
                              <p className="text-xs text-gray-500">Helps us route your case faster to nearby vets</p>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
                          className="px-4 py-3.5 rounded-2xl border border-slate-200 text-slate-500 font-semibold text-sm hover:bg-slate-50 shrink-0"
                        >
                          ↑ Top
                        </button>
                        <button
                          type="button"
                          disabled={!step1Valid}
                          onClick={() => setStep(2)}
                          className={cn(
                            "flex-1 flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-extrabold text-base py-4 rounded-2xl transition-all shadow-lg shadow-orange-200/50 active:scale-[0.99]"
                          )}
                        >
                          Continue <ArrowRight className="h-4 w-4" />
                        </button>
                      </div>

                      {!step1Valid ? (
                        <p className="text-center text-xs text-amber-600">
                          <span className="inline-flex items-center gap-1">
                            <AlertCircle className="h-4 w-4" />
                            {getSubmitTooltip()}
                          </span>
                        </p>
                      ) : (
                        <p className="text-center text-xs text-slate-400">Takes 60 seconds · No spam · OTP verification required</p>
                      )}
                    </div>
                  )}

                  {/* STEP 2: PET */}
                  {step === 2 && (
                    <div className="space-y-5">
                      <div className={cardBase}>
                        <div className={cardHeaderBase}>
                          <div className="h-9 w-9 rounded-lg bg-brand/10 flex items-center justify-center">
                            <PawPrint size={20} className="text-brand" />
                          </div>
                          <div>
                            <h3 className="font-semibold text-gray-900 text-base">Pet details</h3>
                            <p className="text-xs text-gray-500">Tell us about your pet</p>
                          </div>
                        </div>

                        <div className={cardBodyBase}>
                          <div className="space-y-5">
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Pet&apos;s Name <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <PawPrint
                                  size={18}
                                  className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                />
                                <input
                                  type="text"
                                  value={details.name}
                                  onChange={(e) => setDetails((p) => ({ ...p, name: e.target.value }))}
                                  placeholder="Enter your pet's name"
                                  className={cn(fieldBase, "pl-12 md:pl-12")}
                                />
                              </div>
                            </div>

                            <div className="space-y-3">
                              <label className="block text-sm font-medium text-gray-700">
                                Pet Type <span className="text-red-500">*</span>
                              </label>

                              <div className="grid grid-cols-3 gap-3 md:gap-4">
                                {["dog", "cat", "exotic"].map((type) => (
                                  <button
                                    key={type}
                                    type="button"
                                    onClick={() =>
                                      setDetails((p) => ({
                                        ...p,
                                        type,
                                        breed: "",
                                        exoticType: "",
                                      }))
                                    }
                                    className={cn(
                                      "p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all duration-200",
                                      "md:p-5 md:flex-row md:justify-center md:gap-3 md:rounded-2xl",
                                      details.type === type
                                        ? "border-brand bg-brand/5 text-brand"
                                        : "border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100"
                                    )}
                                  >
                                    <div className={details.type === type ? "text-brand" : "text-gray-500"}>
                                      {getPetTypeIcon(type)}
                                    </div>
                                    <span className="capitalize text-sm font-medium md:text-base">{type}</span>
                                  </button>
                                ))}
                              </div>
                            </div>

                            {/* Gender */}
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Gender <span className="text-red-500">*</span>
                              </label>
                              <div className="relative">
                                <Heart
                                  size={18}
                                  className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                />
                                <select
                                  value={details.gender}
                                  onChange={(e) => setDetails((p) => ({ ...p, gender: e.target.value }))}
                                  className={cn(selectBase, "pl-12 md:pl-12")}
                                >
                                  <option value="">Select gender</option>
                                  {GENDER_OPTIONS.map((g) => (
                                    <option key={g.value} value={g.value}>
                                      {g.label}
                                    </option>
                                  ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                              </div>
                            </div>

                            {/* Breed for dog/cat */}
                            {showBreed && (
                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                  Breed <span className="text-red-500">*</span>
                                </label>

                                <div className="relative" ref={breedDropdownRef}>
                                  <button
                                    type="button"
                                    onClick={() =>
                                      !loadingBreeds && breedOptions.length ? setBreedDropdownOpen((prev) => !prev) : null
                                    }
                                    className={cn(selectBase, "text-left")}
                                    disabled={loadingBreeds || breedOptions.length === 0}
                                  >
                                    {loadingBreeds
                                      ? `Loading ${details.type} breeds...`
                                      : selectedBreedLabel || `Select ${details.type} breed`}
                                  </button>
                                  <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                                  {breedDropdownOpen ? (
                                    <div className="absolute z-20 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg">
                                      <div className="p-2 border-b border-gray-100">
                                        <input
                                          type="text"
                                          value={breedSearch}
                                          onChange={(e) => setBreedSearch(e.target.value)}
                                          placeholder={`Search ${details.type} breeds`}
                                          className={fieldBase}
                                          autoFocus
                                        />
                                      </div>
                                      <div className="max-h-56 overflow-auto">
                                        {filteredBreedOptions.length ? (
                                          filteredBreedOptions.map((b) => (
                                            <button
                                              key={b.value}
                                              type="button"
                                              onClick={() => {
                                                setDetails((p) => ({ ...p, breed: b.value }));
                                                setBreedDropdownOpen(false);
                                                setBreedSearch("");
                                              }}
                                              className={cn(
                                                "w-full px-4 py-2 text-left text-sm hover:bg-gray-50",
                                                details.breed === b.value
                                                  ? "bg-gray-50 font-semibold text-gray-900"
                                                  : "text-gray-700"
                                              )}
                                            >
                                              {b.label}
                                            </button>
                                          ))
                                        ) : (
                                          <div className="px-4 py-2 text-sm text-gray-500">No breeds found</div>
                                        )}
                                      </div>
                                    </div>
                                  ) : null}
                                </div>

                                {breedError && (
                                  <p className="text-xs text-amber-600 flex items-center gap-1 mt-1">
                                    <AlertCircle size={12} />
                                    {breedError}
                                  </p>
                                )}
                              </div>
                            )}

                            {/* Exotic type */}
                            {isExotic && (
                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                  Which exotic pet? <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                  <Rabbit
                                    size={18}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  />
                                  <input
                                    type="text"
                                    value={details.exoticType}
                                    onChange={(e) => setDetails((p) => ({ ...p, exoticType: e.target.value }))}
                                    placeholder="e.g. Parrot, Rabbit, Turtle, Guinea pig"
                                    className={cn(fieldBase, "pl-12 md:pl-12")}
                                  />
                                </div>
                                <p className="text-xs text-gray-500">This helps us match the right vet specialist</p>
                              </div>
                            )}

                            {/* DOB + Age + Weight */}
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                Pet&apos;s Date of Birth <span className="text-red-500">*</span>
                              </label>

                              <div className="grid grid-cols-1 gap-3 md:grid-cols-3 md:items-start md:gap-4">
                                <div className="space-y-1.5">
                                  <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">DOB</p>
                                  <div className="relative">
                                    <Calendar
                                      size={18}
                                      className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                    />
                                    <input
                                      type="date"
                                      value={details.petDob}
                                      max={todayISO()}
                                      onChange={(e) => setDetails((p) => ({ ...p, petDob: e.target.value }))}
                                      className={cn(fieldBase, "pl-12 md:pl-12")}
                                    />
                                  </div>
                                </div>

                                <div className="hidden md:block space-y-1.5">
                                  <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Approximate age
                                  </p>
                                  <div className="flex h-[46px] items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-3.5">
                                    <p className="text-xs text-gray-500">Auto-calculated</p>
                                    <p className="text-sm font-bold text-brand">{approxAge || "--"}</p>
                                  </div>
                                </div>

                                <div className="space-y-1.5">
                                  <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Current Weight (kg)
                                  </p>
                                  <div className="relative">
                                    <Scale
                                      size={17}
                                      className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                    />
                                    <input
                                      type="number"
                                      min="0"
                                      step="0.1"
                                      inputMode="decimal"
                                      value={details.weightKg}
                                      onChange={(e) => setDetails((p) => ({ ...p, weightKg: e.target.value }))}
                                      placeholder="e.g. 12.5"
                                      className={cn(fieldBase, "pl-11 md:pl-11")}
                                    />
                                  </div>
                                  <p className="text-[11px] text-gray-500">Optional</p>
                                </div>
                              </div>

                              <p className="text-xs text-gray-500 flex items-center gap-1">
                                <Clock size={12} className="text-brand" />
                                DOB helps the vet understand age-specific health risks
                              </p>
                            </div>

                            {/* Neutered/Vaccinated/Deworming */}
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">Is your pet neutered?</label>
                                <div className="relative">
                                  <CheckCircle2
                                    size={18}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  />
                                  <select
                                    value={details.isNeutered}
                                    onChange={(e) => setDetails((p) => ({ ...p, isNeutered: e.target.value }))}
                                    className={cn(selectBase, "pl-12 md:pl-12")}
                                  >
                                    <option value="">Select</option>
                                    {YES_NO_OPTIONS.map((o) => (
                                      <option key={o.value} value={o.value}>
                                        {o.label}
                                      </option>
                                    ))}
                                  </select>
                                  <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                </div>
                              </div>

                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">Vaccinated?</label>
                                <div className="relative">
                                  <Shield
                                    size={18}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  />
                                  <select
                                    value={details.vaccinatedYesNo}
                                    onChange={(e) => setDetails((p) => ({ ...p, vaccinatedYesNo: e.target.value }))}
                                    className={cn(selectBase, "pl-12 md:pl-12")}
                                  >
                                    <option value="">Select</option>
                                    {YES_NO_OPTIONS.map((o) => (
                                      <option key={o.value} value={o.value}>
                                        {o.label}
                                      </option>
                                    ))}
                                  </select>
                                  <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                </div>
                              </div>

                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">Deworming done recently?</label>
                                <div className="relative">
                                  <Activity
                                    size={18}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  />
                                  <select
                                    value={details.dewormingYesNo}
                                    onChange={(e) => setDetails((p) => ({ ...p, dewormingYesNo: e.target.value }))}
                                    className={cn(selectBase, "pl-12 md:pl-12")}
                                  >
                                    <option value="">Select</option>
                                    {YES_NO_OPTIONS.map((o) => (
                                      <option key={o.value} value={o.value}>
                                        {o.label}
                                      </option>
                                    ))}
                                  </select>
                                  <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() => setStep(1)}
                          className="px-4 py-3.5 rounded-2xl border border-slate-200 text-slate-500 font-semibold text-sm hover:bg-slate-50 shrink-0"
                        >
                          ← Back
                        </button>
                        <button
                          type="button"
                          disabled={!step2Valid}
                          onClick={() => setStep(3)}
                          className={cn(
                            "flex-1 flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-extrabold text-base py-4 rounded-2xl transition-all shadow-lg shadow-orange-200/50 active:scale-[0.99]"
                          )}
                        >
                          Continue <ArrowRight className="h-4 w-4" />
                        </button>
                      </div>

                      {!step2Valid ? (
                        <p className="text-center text-xs text-amber-600">
                          <span className="inline-flex items-center gap-1">
                            <AlertCircle className="h-4 w-4" />
                            {getSubmitTooltip()}
                          </span>
                        </p>
                      ) : (
                        <p className="text-center text-xs text-slate-400">Next: describe symptoms + upload photo/PDF</p>
                      )}
                    </div>
                  )}

                  {/* STEP 3: PROBLEM + UPLOAD + SUBMIT */}
                  {step === 3 && (
                    <div className="space-y-5">
                      <div className={cardBase}>
                        <div className={cardHeaderBase}>
                          <div className="h-9 w-9 rounded-lg bg-brand/10 flex items-center justify-center">
                            <FileText size={20} className="text-brand" />
                          </div>
                          <div>
                            <h3 className="font-semibold text-gray-900 text-base">Describe the problem</h3>
                            <p className="text-xs text-gray-500">Help us understand what&apos;s happening</p>
                          </div>
                        </div>

                        <div className={cardBodyBase}>
                          <div className="flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                            <Lightbulb size={14} className="mt-0.5 text-blue-600" />
                            <p>
                              The more detail you share, the faster the vet can help. Include when it started and any
                              changes in eating or behavior.
                            </p>
                          </div>

                          <div className="space-y-5">
                            <div className="space-y-2">
                              <label className="block text-sm font-medium text-gray-700">
                                What symptoms are you noticing? <span className="text-red-500">*</span>
                              </label>

                              <textarea
                                value={details.problemText}
                                onChange={(e) => setDetails((p) => ({ ...p, problemText: e.target.value }))}
                                placeholder="Example: My dog has been limping since yesterday, not putting weight on front leg, and cries when touched. He's also less active than usual..."
                                rows={4}
                                className={textareaBase}
                              />

                              <div className="flex items-center justify-between text-xs">
                                <span className="text-gray-500">Please include duration and severity</span>
                                <span
                                  className={
                                    details.problemText.trim().length > 10
                                      ? "text-emerald-600 font-semibold"
                                      : "text-gray-400"
                                  }
                                >
                                  {details.problemText.trim().length}/10+ characters
                                </span>
                              </div>
                            </div>

                            <div className="grid grid-cols-1 gap-5 md:grid-cols-3 md:gap-6">
                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                  Energy Level <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                  <Activity
                                    size={18}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  />
                                  <select
                                    value={details.lastDaysEnergy}
                                    onChange={(e) => setDetails((p) => ({ ...p, lastDaysEnergy: e.target.value }))}
                                    className={cn(selectBase, "pl-12 md:pl-12")}
                                  >
                                    <option value="">Select energy level</option>
                                    {ENERGY_OPTIONS.map((o) => (
                                      <option key={o.value} value={o.value}>
                                        {o.label}
                                      </option>
                                    ))}
                                  </select>
                                  <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                </div>
                              </div>

                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                  Appetite <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                  <Coffee
                                    size={18}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  />
                                  <select
                                    value={details.lastDaysAppetite}
                                    onChange={(e) =>
                                      setDetails((p) => ({ ...p, lastDaysAppetite: e.target.value }))
                                    }
                                    className={cn(selectBase, "pl-12 md:pl-12")}
                                  >
                                    <option value="">Select appetite</option>
                                    {APPETITE_OPTIONS.map((o) => (
                                      <option key={o.value} value={o.value}>
                                        {o.label}
                                      </option>
                                    ))}
                                  </select>
                                  <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                </div>
                              </div>

                              <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                  Mood <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                  <Heart
                                    size={18}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  />
                                  <select
                                    value={details.mood}
                                    onChange={(e) => setDetails((p) => ({ ...p, mood: e.target.value }))}
                                    className={cn(selectBase, "pl-12 md:pl-12")}
                                  >
                                    <option value="">Select mood</option>
                                    {MOOD_OPTIONS.map((o) => (
                                      <option key={o.value} value={o.value}>
                                        {o.label}
                                      </option>
                                    ))}
                                  </select>
                                  <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      {/* Upload */}
                      <div className={cardBase}>
                        <div className={cardHeaderBase}>
                          <div className="h-9 w-9 rounded-lg bg-brand/10 flex items-center justify-center">
                            <Camera size={20} className="text-brand" />
                          </div>
                          <div>
                            <h3 className="font-semibold text-gray-900 text-base">Photo or Document</h3>
                            <p className="text-xs text-gray-500">Required (photo or PDF)</p>
                          </div>
                        </div>

                        <div className={cardBodyBase}>
                          <div className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900">
                            <CheckCircle2 size={14} className="mt-0.5 text-emerald-600" />
                            <p>A clear photo helps the vet assess faster. For wounds/rashes/swelling, one photo saves time.</p>
                          </div>

                          <label
                            htmlFor="petUploadGallery"
                            className={cn(
                              "flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200 md:h-48 md:rounded-2xl",
                              isDragging
                                ? "border-brand bg-brand/5 ring-4 ring-brand/10"
                                : "border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-gray-400",
                              details.hasPhoto && uploadFile ? "bg-emerald-50/30 border-emerald-300" : ""
                            )}
                            onDragEnter={handleDragEnter}
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onDrop={handleDrop}
                          >
                            <div className="flex flex-col items-center justify-center pt-5 pb-6">
                              {details.hasPhoto ? (
                                <>
                                  <CheckCircle2 className="w-10 h-10 text-emerald-500 mb-3 md:w-12 md:h-12" />
                                  <p className="mb-1 text-sm text-gray-700 font-medium md:text-base">File ready to upload</p>
                                </>
                              ) : (
                                <>
                                  <Upload className="w-10 h-10 text-brand mb-3 md:w-12 md:h-12" />
                                  <p className="mb-1 text-sm text-gray-700 font-medium md:text-base">
                                    {isDragging ? "Drop to upload" : "Upload photo or document"}
                                  </p>
                                </>
                              )}
                              <p className="text-xs text-gray-500 md:text-sm">
                                {isDragging ? "Release to start upload" : "Drag & drop or click to browse"}
                              </p>
                              <p className="text-xs text-gray-400 mt-1">Supports JPG, PNG, PDF (max 50MB)</p>
                            </div>
                          </label>

                          <div className="mt-3 flex flex-wrap items-center gap-2">
                            <input
                              id="petUploadCamera"
                              type="file"
                              className="hidden"
                              onChange={handlePhotoUpload}
                              accept="image/*"
                              capture="environment"
                            />
                            <label
                              htmlFor="petUploadCamera"
                              className="inline-flex items-center gap-2 rounded-full border border-brand/30 bg-white px-3 py-1.5 text-xs font-semibold text-brand shadow-sm transition hover:border-brand/60"
                            >
                              <Camera className="h-4 w-4" />
                              Camera
                            </label>

                            <input
                              id="petUploadGallery"
                              type="file"
                              className="hidden"
                              onChange={handlePhotoUpload}
                              accept="image/*,.pdf"
                            />
                            <label
                              htmlFor="petUploadGallery"
                              className="inline-flex items-center gap-2 rounded-full border border-brand/30 bg-white px-3 py-1.5 text-xs font-semibold text-brand shadow-sm transition hover:border-brand/60"
                            >
                              <Upload className="h-4 w-4" />
                              Gallery
                            </label>
                          </div>

                          <div className="mt-4 space-y-2">
                            <label className="block text-sm font-medium text-gray-700">Additional document URL (optional)</label>
                            <div className="relative">
                              <FileText
                                size={18}
                                className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                              />
                              <input
                                type="url"
                                value={details.petDoc2}
                                onChange={(e) => setDetails((p) => ({ ...p, petDoc2: e.target.value }))}
                                placeholder="https://example.com/report.png"
                                className={cn(fieldBase, "pl-12 md:pl-12")}
                              />
                            </div>
                            <p className="text-xs text-gray-500">Paste a report link if you already have one</p>
                          </div>

                          {uploadFile && (
                            <div className="mt-4 bg-gray-50 rounded-xl p-4 border border-gray-200">
                              <div className="flex items-start gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-brand shadow-sm">
                                  {uploadIcon}
                                </div>
                                <div className="flex-1 min-w-0">
                                  <div className="flex items-start justify-between">
                                    <div>
                                      <p className="text-sm font-semibold text-gray-900 truncate max-w-[200px] md:max-w-xs">
                                        {uploadFile.name}
                                      </p>
                                      <p className="text-xs text-gray-500 mt-0.5">
                                        {uploadLabel} • {(uploadFile.size / 1024 / 1024).toFixed(2)} MB
                                        {uploadMeta?.compressedSize && (
                                          <span className="text-emerald-600 ml-1">
                                            → {(uploadMeta.compressedSize / 1024 / 1024).toFixed(2)} MB (compressed)
                                          </span>
                                        )}
                                      </p>
                                    </div>
                                    <button
                                      type="button"
                                      onClick={() => {
                                        setUploadFile(null);
                                        if (uploadPreviewUrl) {
                                          try {
                                            URL.revokeObjectURL(uploadPreviewUrl);
                                          } catch {}
                                        }
                                        setUploadPreviewUrl("");
                                        setUploadMeta(null);
                                        setDetails((p) => ({ ...p, hasPhoto: false }));
                                      }}
                                      className="text-xs font-medium text-red-600 hover:text-red-700 hover:underline"
                                    >
                                      Remove
                                    </button>
                                  </div>
                                </div>
                              </div>

                              {uploadPreviewUrl && uploadKind === "image" && (
                                <div className="mt-3">
                                  <img
                                    src={uploadPreviewUrl}
                                    alt="Upload preview"
                                    className="w-full max-h-48 object-contain rounded-lg border border-gray-200 bg-white"
                                  />
                                </div>
                              )}
                            </div>
                          )}

                          <p className="text-xs text-gray-500 flex items-center gap-2 bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <ImageIcon size={14} className="text-brand" />
                            Clear, well-lit photos help vets assess faster.
                          </p>
                        </div>
                      </div>

                      {submitError ? (
                        <div className="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4">
                          <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
                          <p className="text-sm">{submitError}</p>
                        </div>
                      ) : null}

                      <div className="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p className="text-[10px] font-extrabold text-slate-400 tracking-widest uppercase mb-2.5">
                          Consultation fee
                        </p>
                        <div className="flex items-center justify-between">
                          <div className="text-xs text-slate-500">
                            {rateType === "day" ? "Day consult" : "Night consult"} · {priceLabel}
                          </div>
                          <div className="text-right">
                            <span className="text-2xl font-extrabold text-brand">{price}</span>
                          </div>
                        </div>
                      </div>

                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() => setStep(2)}
                          className="px-4 py-3.5 rounded-2xl border border-slate-200 text-slate-500 font-semibold text-sm hover:bg-slate-50 shrink-0"
                        >
                          ← Back
                        </button>

                        <button
                          type="button"
                          disabled={!isValidAll || submitting}
                          title={!isValidAll ? getSubmitTooltip() : undefined}
                          onClick={submitObservation}
                          className={cn(
                            "flex-1 flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-extrabold text-base py-4 rounded-2xl transition-all shadow-lg shadow-orange-200/50 active:scale-[0.99]"
                          )}
                        >
                          {submitting ? (
                            <>
                              <span className="h-4 w-4 rounded-full border-2 border-white border-t-transparent animate-spin" />
                              Submitting...
                            </>
                          ) : (
                            <>
                              Continue to Payment <ArrowRight className="h-4 w-4" />
                            </>
                          )}
                        </button>
                      </div>

                      {!isValidAll ? (
                        <p className="text-center text-xs text-amber-600">
                          <span className="inline-flex items-center gap-1">
                            <AlertCircle className="h-4 w-4" />
                            {getSubmitTooltip()}
                          </span>
                        </p>
                      ) : (
                        <div className="flex items-center justify-center gap-4 text-xs text-slate-400 flex-wrap">
                          <span>🔒 Secure · UPI / Cards / Netbanking</span>
                          <span>📱 No app download</span>
                          <span>⚡ Vet responds in 8–15 minutes</span>
                        </div>
                      )}
                    </div>
                  )}
                </div>
                </div>
              ) : (
                <div className="bg-white rounded-3xl shadow-2xl shadow-slate-200/60 border border-slate-100 p-8 text-center mt-6 max-w-2xl mx-auto">
                <div className="text-5xl mb-4">✅</div>
                <h3 className="text-2xl font-extrabold text-slate-900 mb-2">Details submitted!</h3>

                <p className="text-slate-500 text-sm mb-5">
                  Your pet details + symptoms + upload are received. Next step is payment, then you&apos;ll get a WhatsApp
                  call from the vet.
                </p>

                <div className="bg-green-50 border border-green-200 rounded-2xl p-4 text-left mb-5 space-y-2">
                  {[
                    "We notified our vet team with your case details",
                    "After payment, you’ll get a WhatsApp call within ~15 minutes",
                    "Prescription shared on WhatsApp after call (if needed)",
                    "Follow-up support via WhatsApp for 24 hours",
                  ].map((s, i) => (
                    <p key={i} className="text-xs text-slate-700 flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4 text-green-500 shrink-0" /> {s}
                    </p>
                  ))}
                </div>

                <button
                  type="button"
                  onClick={goToPetDetails}
                  className="w-full flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover text-white font-extrabold text-base py-4 rounded-2xl transition-all shadow-lg shadow-orange-200/50"
                >
                  Proceed to Payment — {price} <ArrowRight className="h-4 w-4" />
                </button>

                <p className="text-xs text-slate-400 mt-4">
                  Need help?{" "}
                  <a href="https://wa.me/919999999999" className="text-brand font-semibold">
                    WhatsApp us
                  </a>
                </p>

                {/* Debug (optional) */}
                {submitPayload ? (
                  <details className="mt-6 text-left text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-2xl p-4">
                    <summary className="cursor-pointer font-semibold">Submitted payload (debug)</summary>
                    <pre className="mt-2 whitespace-pre-wrap break-words">{JSON.stringify(submitPayload, null, 2)}</pre>
                  </details>
                ) : null}
                </div>
              )}
            </div>

            <div className="flex items-center justify-center gap-5 mt-5 text-xs text-slate-400 flex-wrap">
              <span className="flex items-center gap-1.5">
                <ShieldCheck className="h-3.5 w-3.5 text-brand" />
                Verified vets
              </span>
              <span className="flex items-center gap-1.5">
                <Clock className="h-3.5 w-3.5 text-brand" />
                Under 15 min wait
              </span>
              <span className="flex items-center gap-1.5">
                <Star className="h-3.5 w-3.5 text-brand" />
                4.8★ from 200+ pet parents
              </span>
            </div>
          </div>
        </section>

        {/* SOCIAL PROOF */}
        <section className="bg-slate-900 py-7 px-4">
          <div className="max-w-6xl mx-auto grid grid-cols-3 gap-4 text-center">
            {[
              { v: "200+", l: "Consultations done" },
              { v: "4.8 ★", l: "Average rating" },
              { v: "< 15 min", l: "Avg. wait time" },
            ].map((s) => (
              <div key={s.l}>
                <p className="text-xl sm:text-2xl font-extrabold text-white">{s.v}</p>
                <p className="text-slate-400 text-xs mt-0.5">{s.l}</p>
              </div>
            ))}
          </div>
        </section>

        {/* HOW IT WORKS */}
        <section className="py-14 px-4 bg-white">
          <div className="max-w-4xl mx-auto">
            <p className="text-xs font-extrabold text-brand text-center tracking-widest mb-2">HOW IT WORKS</p>
            <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-8">From worried to sorted in 15 minutes</h2>

            <div className="relative space-y-3">
              <div className="absolute left-[22px] top-10 bottom-10 w-px bg-slate-100" />
              {[
                {
                  n: "01",
                  title: "Tell us about your pet & issue",
                  desc:
                    "Fill in your pet's details and describe what's wrong. Attach a photo or report — it helps the vet prepare before the call.",
                },
                {
                  n: "02",
                  title: "Pay securely (₹399 / ₹549)",
                  desc: "One-time fee. UPI, cards, or netbanking. No subscription, no hidden charges.",
                },
                {
                  n: "03",
                  title: "Vet calls you within 15 minutes",
                  desc: "A verified vet calls on WhatsApp video or your number. They review your case before calling.",
                },
                {
                  n: "04",
                  title: "Get diagnosis, advice & prescription",
                  desc: "The vet assesses, advises, and sends a digital prescription to WhatsApp if medication is needed.",
                },
              ].map((s, i) => (
                <div key={i} className="flex gap-4 bg-slate-50 rounded-2xl p-4 border border-slate-100 relative">
                  <div className="h-11 w-11 rounded-2xl bg-brand text-white flex items-center justify-center font-extrabold text-xs shrink-0 relative z-10">
                    {s.n}
                  </div>
                  <div>
                    <p className="font-extrabold text-slate-900 text-sm mb-1">{s.title}</p>
                    <p className="text-slate-500 text-xs leading-relaxed">{s.desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* CTA BREAK */}
        <section className="px-4 py-6 bg-white">
          <div className="max-w-3xl mx-auto">
            <button
              type="button"
              onClick={scrollToConsultForm}
              className="w-full flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover text-white font-extrabold text-base py-4 rounded-2xl shadow-lg shadow-orange-200/50 transition-all"
            >
              <Zap className="h-4 w-4" /> Talk to a Vet Now — {price}
            </button>
          </div>
        </section>

        {/* PRICING */}
        <section className="py-14 px-4 bg-slate-50">
          <div className="max-w-6xl mx-auto">
            <p className="text-xs font-extrabold text-brand text-center tracking-widest mb-2">PRICING</p>
            <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-2">One flat fee. Everything included.</h2>
            <p className="text-slate-500 text-sm text-center mb-8">No subscription. No extra charges for prescription or follow-up.</p>

            <div className="grid sm:grid-cols-2 gap-4 mb-5">
              <div className="bg-white rounded-2xl border-2 border-brand p-5 relative shadow-lg shadow-brand/10">
                <span className="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand text-white text-[10px] font-extrabold px-3 py-1 rounded-full whitespace-nowrap">
                  8 AM – 10 PM
                </span>
                <p className="text-sm text-slate-500 font-semibold mt-1 mb-1">Day consult</p>
                <p className="text-4xl font-extrabold text-brand mb-4">₹399</p>
                <ul className="space-y-2 text-xs text-slate-600">
                  {[
                    "Video / WhatsApp call with vet",
                    "Vet reviews your case before calling",
                    "Digital prescription if needed",
                    "WhatsApp follow-up for 24 hours",
                  ].map((f) => (
                    <li key={f} className="flex items-center gap-2">
                      <CheckCircle2 className="h-3.5 w-3.5 text-brand shrink-0" />
                      {f}
                    </li>
                  ))}
                </ul>
              </div>

              <div className="bg-white rounded-2xl border border-slate-200 p-5 relative">
                <span className="absolute -top-3 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] font-extrabold px-3 py-1 rounded-full whitespace-nowrap">
                  10 PM – 8 AM
                </span>
                <p className="text-sm text-slate-500 font-semibold mt-1 mb-1">Night consult</p>
                <p className="text-4xl font-extrabold text-slate-900 mb-4">₹549</p>
                <ul className="space-y-2 text-xs text-slate-600">
                  {[
                    "Video / WhatsApp call with vet",
                    "Vet reviews your case before calling",
                    "Digital prescription if needed",
                    "WhatsApp follow-up for 24 hours",
                  ].map((f) => (
                    <li key={f} className="flex items-center gap-2">
                      <CheckCircle2 className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                      {f}
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            <p className="text-xs text-slate-400 text-center">
              Same quality vets. Same service. Night rate reflects 24/7 availability.
            </p>
          </div>
        </section>

        {/* FAQ */}
        <section className="py-14 px-4 bg-white">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-8">Questions before you book</h2>
            <div className="space-y-3">
              {[
                {
                  q: "How does online vet consultation work?",
                  a: "You'll receive a WhatsApp video call on the number you provide. No app download required — the call comes to you.",
                },
                {
                  q: "Does the vet see my photos before the call?",
                  a: "Yes. The vet reviews your pet details, symptoms, and uploaded photo/PDF before calling — so they come prepared.",
                },
                {
                  q: "Can I really get a prescription online?",
                  a: "Yes. VCI-registered vets can issue digital prescriptions for non-restricted medications. Sent on WhatsApp and valid at pharmacies in India.",
                },
                {
                  q: "What if my pet won’t sit still for the video?",
                  a: "Normal. The vet will guide you; your description + photos are usually enough. They may request follow-up photos after the call.",
                },
                {
                  q: "What if I’m not happy with the consultation?",
                  a: "Message support within 24 hours. If the call dropped or there’s a genuine issue, we can reschedule or refund based on review.",
                },
                {
                  q: "Is this available outside Delhi NCR?",
                  a: "Online consultations are pan-India 24/7. Clinic services (vaccination/neuter) are currently Delhi NCR only.",
                },
              ].map((f, i) => (
                <FaqItem key={i} q={f.q} a={f.a} />
              ))}
            </div>
          </div>
        </section>

        {/* FINAL CTA */}
        <section className="py-14 px-4 bg-slate-900 relative overflow-hidden">
          <div className="pointer-events-none absolute inset-0">
            <div className="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-brand/10 blur-3xl" />
            <div className="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-accent/5 blur-3xl" />
          </div>

          <div className="relative max-w-3xl mx-auto text-center">
            <p className="text-3xl mb-3">🐾</p>
            <h2 className="text-2xl sm:text-3xl font-extrabold text-white mb-3 leading-tight">
              Your pet needs you to act.<br />
              <span className="text-brand">A vet is standing by.</span>
            </h2>
            <p className="text-slate-400 text-sm mb-7">
              Fill in your pet&apos;s details, describe the issue, upload photo/PDF and a vet calls you on WhatsApp within 15 minutes after payment.
            </p>

            <button
              type="button"
              onClick={scrollToConsultForm}
              className="w-full bg-accent hover:bg-accent-hover text-white font-extrabold text-lg py-4 rounded-2xl shadow-xl shadow-orange-900/30 transition-all mb-3"
            >
              Talk to a Vet — {price}
            </button>
            <p className="text-slate-500 text-xs">{priceLabel} · No appointment · No app · Secure payment</p>
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer className="bg-white border-t border-slate-100 py-4 px-4 pb-28 md:pb-4">
        <p className="text-xs text-slate-400 text-center">
          © {new Date().getFullYear()} SnoutiQ ·{" "}
          <a href="/" className="hover:text-brand font-semibold">
            Home
          </a>{" "}
          · <a href="/privacy-policy" className="hover:text-brand">Privacy Policy</a> ·{" "}
          {/* <a href="https://wa.me/919999999999" className="hover:text-brand">Support</a> */}
        </p>
      </footer>

      {/* Sticky Mobile CTA */}
      <div className="fixed bottom-0 inset-x-0 z-50 md:hidden bg-white/98 backdrop-blur-sm border-t border-slate-200 p-3 shadow-2xl">
        <div className="flex gap-2.5 items-center max-w-lg mx-auto">
          {/* <a
            href="https://wa.me/919999999999?text=Hi%2C%20I%20need%20help%20with%20my%20pet"
            className="flex items-center justify-center gap-1.5 border-2 border-[#25D366] text-[#25D366] font-extrabold py-3 px-3.5 rounded-xl text-xs shrink-0"
          >
            <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
            Chat
          </a> */}

          <button
            type="button"
            onClick={scrollToConsultForm}
            className="flex-1 bg-accent hover:bg-accent-hover text-white font-extrabold py-3.5 rounded-xl text-sm transition-colors"
          >
            Talk to a Vet — {price}
          </button>
        </div>
      </div>
    </div>
  );
}

// ─── FAQ item (same as your original) ────────────────────────────────────────
function FaqItem({ q, a }) {
  const [open, setOpen] = useState(false);
  return (
    <div
      className={cn(
        "border rounded-2xl overflow-hidden transition-all",
        open ? "border-brand/30 bg-brand-light/10" : "border-slate-200 bg-white"
      )}
    >
      <button
        type="button"
        onClick={() => setOpen(!open)}
        className="w-full flex items-center justify-between gap-4 px-5 py-4 text-left"
      >
        <span className="font-semibold text-slate-900 text-sm leading-snug">{q}</span>
        <ChevronDown className={cn("h-4 w-4 text-brand shrink-0 transition-transform", open ? "rotate-180" : "")} />
      </button>
      {open && (
        <p className="px-5 pb-4 text-sm text-slate-500 leading-relaxed border-t border-brand/10 pt-3">{a}</p>
      )}
    </div>
  );
}
