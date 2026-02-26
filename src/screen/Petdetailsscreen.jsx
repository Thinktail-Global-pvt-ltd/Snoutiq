import React, { useEffect, useMemo, useRef, useState } from "react";
import { SPECIALTY_ICONS } from "../../constants";
import { Button } from "../components/Button";
import { PET_FLOW_STEPS, ProgressBar } from "../components/Sharedcomponents";
import { apiPost, apiBaseUrl } from "../lib/api";
import {
  CheckCircle2,
  ChevronDown,
  ChevronLeft,
  Star,
  FileText,
  Image,
  Upload,
  User,
  Phone,
  Calendar,
  Activity,
  Coffee,
  Heart,
  PawPrint,
  AlertCircle,
  Camera,
  Lightbulb,
  Dog,
  Cat,
  Rabbit,
  Shield,
  Clock,
  MapPin,
  Scale,
} from "lucide-react";
import { FaWhatsapp } from "react-icons/fa";

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

// ✅ NEW: Gender options
const GENDER_OPTIONS = [
  { label: "Male", value: "male" },
  { label: "Female", value: "female" },
];
const YES_NO_OPTIONS = [
  { label: "Yes", value: "1" },
  { label: "No", value: "0" },
];

const SIDEBAR_REVIEWS = [
  {
    id: "r1",
    author: "Priya M",
    text: "Fast response and clear guidance. Consultation felt very professional.",
  },
  {
    id: "r2",
    author: "Aamir S",
    text: "Helpful advice for my rabbit. Vet explained next steps very clearly.",
  },
  {
    id: "r3",
    author: "Neha K",
    text: "Quick support at night and practical recommendations for home care.",
  },
];

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

  return `${years} yr${years === 1 ? "" : "s"} ${months} mo${
    months === 1 ? "" : "s"
  }`;
};

// Enhanced input styling with professional placeholders
const fieldBase =
  "w-full rounded-xl border border-gray-200 bg-white p-3 text-gray-900 placeholder:text-gray-400 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] focus:bg-white hover:border-gray-300 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed md:rounded-2xl md:p-3.5 md:text-[15px]";
const selectBase = `${fieldBase} appearance-none pr-12`;
const textareaBase = `${fieldBase} resize-none min-h-[120px]`;
const cardBase = "rounded-xl border border-gray-200 bg-white overflow-hidden";
const cardHeaderBase =
  "flex items-center gap-3 border-b border-gray-100 px-4 py-3.5";
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

const getInitials = (name = "") => {
  const trimmed = String(name || "").trim();
  if (!trimmed) return "V";
  const parts = trimmed.split(" ").filter(Boolean);
  if (parts.length === 1) return parts[0][0]?.toUpperCase() || "V";
  return `${parts[0][0] || ""}${parts[1][0] || ""}`.toUpperCase();
};

const formatExperience = (value) => {
  const years = Number(value);
  if (!Number.isFinite(years) || years <= 0) return "";
  return `${years}+ yrs experience`;
};

const normalizeNameKey = (value = "") =>
  String(value || "")
    .toLowerCase()
    .replace(/[^a-z]/g, "");

const isDrShashankVet = (value) => {
  const key = normalizeNameKey(value);
  if (!key) return false;
  return key.includes("shash") && key.includes("goyal");
};

const formatPhone = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  if (digits.startsWith("91")) return digits;
  return `91${digits}`;
};

const normalizeDisplayText = (value) => {
  if (value === undefined || value === null) return "";
  const text = String(value).trim();
  if (!text) return "";
  const lower = text.toLowerCase();
  if (
    lower === "null" ||
    lower === "undefined" ||
    lower === "[]" ||
    lower === "na" ||
    lower === "n/a"
  ) {
    return "";
  }
  return text;
};

const listToDisplayText = (value) => {
  if (Array.isArray(value)) {
    return value.map((item) => normalizeDisplayText(item)).filter(Boolean).join(", ");
  }
  const text = normalizeDisplayText(value);
  if (!text) return "";
  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) {
        return parsed.map((item) => normalizeDisplayText(item)).filter(Boolean).join(", ");
      }
    } catch {
      return text.replace(/^\[|\]$/g, "").replace(/["']/g, "").trim();
    }
  }
  return text;
};

/**
 * ✅ Client-side image compression (web)
 * - Only compresses images (jpeg/png/webp). PDFs are sent as-is.
 * - Returns a NEW File (compressed) to append in FormData.
 */
const compressImageFile = async (
  file,
  {
    maxWidth = 1280,
    maxHeight = 1280,
    quality = 0.72,
    outputMime = "image/jpeg",
  } = {}
) => {
  if (!file) return null;

  const isImage = file.type?.startsWith("image/");
  if (!isImage) return file;

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
  const safeName =
    (file.name?.replace(/\.[^/.]+$/, "") || "upload") + `_compressed.${ext}`;

  return new File([blob], safeName, { type: outputMime });
};

const PetDetailsScreen = ({ onSubmit, onBack, vet }) => {
  const [details, setDetails] = useState({
    ownerName: "",
    ownerMobile: "",
    city: "",
    name: "",
    type: null,
    breed: "",
    petDob: "",
    // ✅ NEW: gender (required)
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
  const breedDropdownRef = useRef(null);
  const [otpToken, setOtpToken] = useState("");
  const [otpValue, setOtpValue] = useState("");
  const [otpStatus, setOtpStatus] = useState("idle");
  const [otpMessage, setOtpMessage] = useState("");
  const [otpError, setOtpError] = useState("");
  const [otpCooldown, setOtpCooldown] = useState(0);
  const [otpPhone, setOtpPhone] = useState("");
  const otpInputRef = useRef(null);
  const [liveDoctorCount, setLiveDoctorCount] = useState(null);

  const resetOtpState = () => {
    setOtpToken("");
    setOtpValue("");
    setOtpStatus("idle");
    setOtpMessage("");
    setOtpError("");
    setOtpCooldown(0);
    setOtpPhone("");
  };

  const applyUploadFile = async (file) => {
    if (!file) return;

    setSubmitError("");

    const lowerName = file.name?.toLowerCase() || "";
    const isVideo =
      file.type?.startsWith("video/") ||
      /\.(mp4|mov|avi|mkv|webm)$/i.test(lowerName);
    if (isVideo) {
      setSubmitError(
        "Video uploads are not supported. Please upload a photo or PDF."
      );
      return;
    }

    const isImage = file.type?.startsWith("image/");
    const isPdf = file.type === "application/pdf" || lowerName.endsWith(".pdf");
    if (!isImage && !isPdf) {
      setSubmitError("Please upload a JPG, PNG, or PDF file.");
      return;
    }

    if (file.type?.startsWith("image/")) {
      const url = URL.createObjectURL(file);
      setUploadPreviewUrl(url);
    } else {
      setUploadPreviewUrl("");
    }

    setUploadFile(file);
    setDetails((prev) => ({ ...prev, hasPhoto: true }));

    setUploadMeta({
      name: file.name,
      size: file.size,
      type: file.type,
      compressedSize: null,
    });
  };

  const handlePhotoUpload = async (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    await applyUploadFile(f);
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

  useEffect(() => {
    const fetchDogBreeds = async () => {
      setBreedError("");
      setLoadingBreeds(true);

      try {
        const baseUrl = apiBaseUrl();
        const res = await fetch(`${baseUrl}/api/dog-breeds/all`, {
          method: "GET",
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
                list.push({
                  label: formatBreedName(breedKey, sub),
                  value: `${breedKey}/${sub}`,
                });
              });
            }
          });

          list.sort((a, b) => a.label.localeCompare(b.label));
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
          setBreedError("Could not load breeds (using defaults).");
        }
      } catch (err) {
        setDogBreeds([
          { label: "Mixed Breed", value: "mixed_breed" },
          { label: "Other", value: "other" },
        ]);
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
      } catch (err) {
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
  }, [details.type]);

  useEffect(() => {
    if (otpCooldown <= 0) return;
    const timer = window.setTimeout(() => {
      setOtpCooldown((prev) => Math.max(0, prev - 1));
    }, 1000);
    return () => window.clearTimeout(timer);
  }, [otpCooldown]);

  useEffect(() => {
    let active = true;

    const fetchLiveStatus = async () => {
      try {
        const baseUrl = apiBaseUrl();
        const res = await fetch(`${baseUrl}/api/doctors/availability-status`, {
          method: "GET",
        });
        const data = await res.json();
        if (!active) return;

        const onlineFromCounts = Number.isFinite(data?.counts?.online_doctors)
          ? data.counts.online_doctors
          : null;
        const onlineFromList = Array.isArray(data?.online_doctors)
          ? data.online_doctors.length
          : null;
        const onlineValue =
          onlineFromCounts !== null ? onlineFromCounts : onlineFromList;

        setLiveDoctorCount(onlineValue);
      } catch (err) {
        if (active) {
          setLiveDoctorCount(null);
        }
      }
    };

    fetchLiveStatus();

    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    const digits = details.ownerMobile.replace(/\D/g, "");
    if (digits.length !== 10) {
      if (otpStatus !== "idle") resetOtpState();
      return;
    }
    if (otpPhone && digits !== otpPhone) {
      resetOtpState();
    }
  }, [details.ownerMobile, otpPhone, otpStatus]);

  useEffect(() => {
    if (!breedDropdownOpen) return;
    const handleClick = (event) => {
      if (
        breedDropdownRef.current &&
        !breedDropdownRef.current.contains(event.target)
      ) {
        setBreedDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, [breedDropdownOpen]);

  const breedOptions = useMemo(() => {
    if (details.type === "dog") return dogBreeds;
    if (details.type === "cat") return catBreeds;
    return [];
  }, [details.type, dogBreeds, catBreeds]);

  const filteredBreedOptions = useMemo(() => {
    const term = breedSearch.trim().toLowerCase();
    if (!term) return breedOptions;
    const filtered = breedOptions.filter((b) =>
      String(b?.label || "").toLowerCase().includes(term)
    );
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
    if (uploadKind === "image") return <Image className="w-4 h-4" />;
    return <FileText className="w-4 h-4" />;
  }, [uploadKind]);

  const uploadLabel = useMemo(() => {
    if (uploadKind === "image") return "Image";
    if (uploadKind === "pdf") return "PDF";
    return "File";
  }, [uploadKind]);

  const ownerPhoneDigits = details.ownerMobile.replace(/\D/g, "");
  const otpVerified = otpStatus === "verified";
  const showOtpSection = ownerPhoneDigits.length === 10 || otpStatus !== "idle";

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
      const data = await apiPost("/api/send-otp", {
        type: "whatsapp",
        value: phone,
      });
      if (!data?.token) {
        throw new Error("OTP token missing. Please try again.");
      }

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
      const payload = {
        token: otpToken,
        otp: otpValue.trim(),
        phone: ownerPhoneDigits,
      };
      if (location) {
        payload.lat = location.lat;
        payload.lang = location.lang;
      }
      const data = await apiPost("/api/verify-otp", payload);
      if (data?.success === false) {
        throw new Error(data?.error || data?.message || "OTP verification failed.");
      }

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

  // ✅ UPDATED: gender required
  const isValid =
    details.ownerName.trim().length > 0 &&
    details.ownerMobile.replace(/\D/g, "").length === 10 &&
    details.city.trim().length > 1 &&
    otpVerified &&
    details.name.trim().length > 0 &&
    details.type !== null &&
    details.petDob &&
    details.gender && // ✅ required
    (!showBreed || details.breed) &&
    (!isExotic || details.exoticType.trim().length > 0) &&
    details.problemText.trim().length > 10 &&
    details.lastDaysEnergy &&
    details.lastDaysAppetite &&
    details.mood &&
    details.hasPhoto &&
    !!uploadFile;

  const getSubmitTooltip = () => {
    if (!details.ownerName.trim()) return "Please enter owner name";
    if (details.ownerMobile.replace(/\D/g, "").length !== 10)
      return "Please enter 10-digit mobile number";
    if (!details.city.trim()) return "Please enter city name";
    if (!otpVerified) return "Please verify mobile number with OTP";
    if (!details.name.trim()) return "Please enter your pet's name";
    if (!details.type) return "Please select pet type";
    if (!details.gender) return "Please select pet gender"; // ✅ NEW
    if (isExotic && !details.exoticType.trim())
      return "Please specify your exotic pet type";
    if (showBreed && !details.breed) return "Please select breed";
    if (!details.petDob) return "Please select pet's date of birth";
    if (details.problemText.trim().length <= 10)
      return "Please describe the problem in detail (minimum 10 characters)";
    if (!details.lastDaysEnergy) return "Please select energy level";
    if (!details.lastDaysAppetite) return "Please select appetite level";
    if (!details.mood) return "Please select mood";
    if (!details.hasPhoto || !uploadFile) return "Please upload a photo or PDF";
    return "";
  };

  const submitObservation = async () => {
    setSubmitError("");
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

        setUploadMeta((prev) =>
          prev ? { ...prev, compressedSize: compressed?.size ?? null } : prev
        );
      }

      const fd = new FormData();
      fd.append("name", details.ownerName);
      fd.append("phone", formatPhone(details.ownerMobile));
      fd.append("city", details.city.trim());
      fd.append("type", details.type || "");
      fd.append("dob", details.petDob || "");
      fd.append("pet_name", details.name || "");
      if (details.weightKg !== "") {
        fd.append("weight", details.weightKg);
      }

      // ✅ NEW: send to backend with key "gender"
      fd.append("gender", details.gender || "");

      const breedValue =
        details.type === "exotic"
          ? details.exoticType.trim()
          : details.breed || "";
      fd.append("breed", breedValue);

      fd.append("reported_symptom", details.problemText || "");
      fd.append("appetite", details.lastDaysAppetite || "");
      fd.append("energy", details.lastDaysEnergy || "");
      fd.append("mood", details.mood || "calm");
      if (details.isNeutered !== "") {
        fd.append("is_neutered", details.isNeutered);
      }
      if (details.vaccinatedYesNo !== "") {
        fd.append("vaccenated_yes_no", details.vaccinatedYesNo);
      }
      if (details.dewormingYesNo !== "") {
        fd.append("deworming_yes_no", details.dewormingYesNo);
      }

      if (details.petDoc2?.trim()) fd.append("pet_doc2", details.petDoc2.trim());
      if (fileToSend) fd.append("file", fileToSend);

      const baseUrl = apiBaseUrl();
      const res = await fetch(`${baseUrl}/api/user-pet-observation`, {
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

      const nextPayload = {
        ...details,
        observation,
        observationResponse: data,
        user_id: userId,
        pet_id: petId,
      };

      onSubmit?.(nextPayload);
    } catch (e) {
      setSubmitError(e?.message || "Something went wrong. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  useEffect(() => {
    return () => {
      if (uploadPreviewUrl) URL.revokeObjectURL(uploadPreviewUrl);
    };
  }, [uploadPreviewUrl]);

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

  const rawVet = vet?.raw && typeof vet.raw === "object" ? vet.raw : null;

  const vetName = vet?.name || rawVet?.doctor_name || "Selected vet";
  const vetQualification = vet?.qualification || normalizeDisplayText(rawVet?.degree);
  const vetExperience = formatExperience(vet?.experience);
  const isSnoutiqAssignedVet = Boolean(
    vet?.isSnoutiqAssigned || vet?.autoAssigned || vet?.assignedBy === "snoutiq"
  );
  const isDrShashankSelected = isDrShashankVet(vetName) || isDrShashankVet(vet?.doctor_name);
  const showSnoutiqHighlight = isDrShashankSelected || isSnoutiqAssignedVet;
  const vetMetaLine =
    vetQualification && vetExperience
      ? `${vetQualification} - ${vetExperience}`
      : vetQualification || vetExperience || "Details shown after payment";

  const backendRating = toNumber(
    pickValue(rawVet?.average_review_points, rawVet?.average_rating)
  );
  const vetRating =
    Number.isFinite(backendRating) && backendRating > 0
      ? backendRating.toFixed(1)
      : Number.isFinite(Number(vet?.rating))
      ? Number(vet?.rating).toFixed(1)
      : "--";

  const backendConsultations = toNumber(
    pickValue(
      rawVet?.consultations_count,
      rawVet?.total_consultations,
      rawVet?.total_consultation_count
    )
  );
  const backendReviews = toNumber(pickValue(rawVet?.reviews_count, vet?.reviews));
  const vetConsultations =
    Number.isFinite(backendConsultations) && backendConsultations >= 0
      ? Math.round(backendConsultations)
      : Number.isFinite(Number(vet?.consultations))
      ? Math.round(Number(vet?.consultations))
      : "--";
  const vetReviewCount =
    Number.isFinite(backendReviews) && backendReviews >= 0
      ? Math.round(backendReviews)
      : "--";
  const vetConsultationDisplay =
    typeof vetConsultations === "number"
      ? vetConsultations
      : typeof vetReviewCount === "number"
      ? vetReviewCount
      : "--";
  const vetConsultationLabel =
    typeof vetConsultations === "number"
      ? "Consultations"
      : typeof vetReviewCount === "number"
      ? "Reviews"
      : "Consultations";

  const vetResponse =
    (vet?.bookingRateType === "night" ? vet?.responseNight : vet?.responseDay) ||
    vet?.responseDay ||
    vet?.responseNight ||
    "";
  const vetResponseText = showSnoutiqHighlight
    ? "Priority response in 7-8 minutes after payment"
    : vetResponse
    ? `Responds in ${vetResponse}`
    : "Responds quickly after payment";

  const vetDoctorMobile = normalizeDisplayText(
    pickValue(vet?.doctor_mobile, rawVet?.doctor_mobile, rawVet?.mobile)
  );
  const vetSpecialization = listToDisplayText(
    pickValue(vet?.specializationText, rawVet?.specialization_select_all_that_apply)
  );
  const vetDayResponse = normalizeDisplayText(
    pickValue(vet?.responseDay, rawVet?.response_time_for_online_consults_day)
  );
  const vetNightResponse = normalizeDisplayText(
    pickValue(vet?.responseNight, rawVet?.response_time_for_online_consults_night)
  );
  const vetFollowUp = normalizeDisplayText(
    pickValue(
      vet?.followUp,
      rawVet?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta
    )
  );
  const vetLanguages = listToDisplayText(
    pickValue(vet?.languages_spoken, rawVet?.languages_spoken)
  );
  const vetBio = normalizeDisplayText(pickValue(vet?.bio, rawVet?.bio));

  const vetProfileItems = [
    { key: "specialization", label: "Specialization", value: vetSpecialization },
    { key: "dayResponse", label: "Day response", value: vetDayResponse },
    { key: "nightResponse", label: "Night response", value: vetNightResponse },
    { key: "followUp", label: "Follow-up", value: vetFollowUp },
    { key: "mobile", label: "Mobile", value: vetDoctorMobile },
    { key: "languages", label: "Languages", value: vetLanguages },
  ].filter((item) => Boolean(item.value));

  const sidebarRating = vetRating === "--" ? "4.8" : vetRating;
  const sidebarConsultations =
    typeof vetConsultations === "number"
      ? vetConsultations
      : typeof vetReviewCount === "number"
      ? vetReviewCount
      : null;

  return (
    <div className="min-h-screen bg-[#f0f4f8] flex flex-col">
      <div className="sticky top-0 z-40 border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-5xl items-center gap-4 px-4 py-3 md:px-6">
          <button
            type="button"
            onClick={onBack}
            className="h-8 w-8 rounded-full border border-gray-200 text-gray-600 flex items-center justify-center transition hover:bg-gray-50"
            aria-label="Go back"
          >
            <ChevronLeft size={18} />
          </button>
          <div className="flex-1 text-center text-base font-semibold text-gray-900 md:text-lg">
            Tell us about your pet
          </div>
          <div className="h-8 w-8" />
        </div>
      </div>

      <div className="w-full">
        <div className="flex-1 px-4 pb-28 pt-4 md:px-6 md:pb-20 md:pt-8">
          <div className="mx-auto w-full max-w-5xl">
            <div className="md:flex md:items-center md:justify-between md:gap-6">
              <ProgressBar current={2} steps={PET_FLOW_STEPS} />
              <div className="hidden text-xs font-semibold text-gray-500 bg-white px-4 py-2 rounded-full border border-gray-200">
                Takes less than 2 minutes
              </div>
            </div>

            <div className="mt-4 rounded-xl bg-gradient-to-r from-[#1d4ed8] to-[#2563eb] px-5 py-4 text-white shadow-sm">
              <div className="flex items-center gap-3">
                <span className="h-2 w-2 rounded-full bg-emerald-400 animate-pulse" />
                <div className="flex-1">
                  <div className="text-sm font-semibold">
                    {liveDoctorCount === null
                      ? "Checking live vets..."
                      : `${liveDoctorCount} ${
                          liveDoctorCount === 1 ? "vet is" : "vets are"
                        } online right now`}
                  </div>
                <div className="text-xs text-white/80">
                    {showSnoutiqHighlight
                      ? "SnoutIQ selected your doctor for faster care. Expected response: 7-8 minutes."
                      : "Average response after payment: under 15 minutes"}
                </div>
              </div>
              <div className="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold">
                Live
              </div>
            </div>
          </div>

          {showSnoutiqHighlight ? (
            <div className="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
              <div className="flex items-start gap-2">
                <Shield size={14} className="mt-0.5 text-emerald-700" />
                <div>
                  <div className="text-sm font-semibold text-emerald-900">
                    SnoutIQ Selected Best-Match Doctor
                  </div>
                  <p className="mt-1 text-xs text-emerald-800">
                    For your consultation,{" "}
                    <span className="font-semibold">{vetName}</span> is assigned by
                    SnoutIQ as a best-fit doctor. Typical first response is within
                    7-8 minutes.
                  </p>
                </div>
              </div>
            </div>
          ) : null}

          <div className="mt-6 grid gap-6 md:grid-cols-[minmax(0,1fr)_320px]">
              {/* LEFT COLUMN - Main Form */}
              <div className="space-y-6">
                  {/* Owner details */}
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <User size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">
                          Owner details
                        </h3>
                        <p className="text-xs text-gray-500">
                          Used only for appointment updates
                        </p>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      <div className="flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                        <Shield size={14} className="mt-0.5 text-blue-600" />
                        <p>
                          Your details are only shared with your assigned vet.
                          We do not use them for marketing.
                        </p>
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
                            onChange={(e) =>
                              setDetails((p) => ({
                                ...p,
                                ownerName: e.target.value,
                              }))
                            }
                            placeholder="Enter your full name"
                            className={`${fieldBase} pl-12 md:pl-12`}
                          />
                        </div>
                      </div>

                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700">
                          Pet Owner WhatsApp Mobile{" "}
                          <span className="text-red-500">*</span>
                        </label>
                        <div className="relative">
                          <FaWhatsapp
                            size={18}
                            className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 z-10 pointer-events-none"
                          />
                          <div className="flex items-center rounded-xl border border-gray-200 bg-white pl-12 shadow-sm transition-all focus-within:ring-2 focus-within:ring-[#3998de]/30 focus-within:border-[#3998de]">
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
                                  ownerMobile: e.target.value
                                    .replace(/\D/g, "")
                                    .slice(0, 10),
                                }))
                              }
                              placeholder="Enter mobile number"
                              className="flex-1 py-3.5 bg-transparent outline-none font-medium text-gray-900 placeholder:text-gray-400"
                            />
                          </div>
                        </div>
                        <p className="text-xs text-gray-500 flex items-center gap-1 mt-1">
                          <Shield size={12} className="text-[#3998de]" />
                          No spam. Only consultation updates.
                        </p>

                        {showOtpSection && (
                          <div className="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                            <div className="flex items-start justify-between gap-3">
                              <div className="flex items-start gap-3">
                                <div
                                  className={[
                                    "w-9 h-9 rounded-full flex items-center justify-center",
                                    otpVerified
                                      ? "bg-emerald-100 text-emerald-600"
                                      : "bg-blue-100 text-blue-600",
                                  ].join(" ")}
                                >
                                  <Shield size={16} />
                                </div>
                                <div>
                                  <p className="text-sm font-semibold text-gray-900">
                                    Verify mobile number
                                  </p>
                                  <p className="text-xs text-gray-500">
                                    OTP will be sent on WhatsApp to +91{" "}
                                    {ownerPhoneDigits}
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
                                    className={[
                                      "px-4 py-2 rounded-xl text-sm font-semibold transition-colors",
                                      otpSendDisabled
                                        ? "bg-gray-200 text-gray-500 cursor-not-allowed"
                                        : "bg-[#3998de] text-white hover:bg-[#2f86c3]",
                                    ].join(" ")}
                                  >
                                    {otpStatus === "sending"
                                      ? "Sending..."
                                      : otpCooldown > 0
                                      ? "Resend OTP"
                                      : "Send OTP"}
                                  </button>
                                  <div className="text-xs text-gray-500 flex items-center gap-1">
                                    <Clock size={12} className="text-gray-400" />
                                    {otpCooldown > 0
                                      ? `Resend in ${otpCooldown}s`
                                      : "OTP valid for 10 minutes"}
                                  </div>
                                </div>

                                <div className="flex flex-col gap-3 md:flex-row md:items-center">
                                  <input
                                    ref={otpInputRef}
                                    type="text"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    value={otpValue}
                                    onChange={(e) =>
                                      setOtpValue(
                                        e.target.value.replace(/\D/g, "").slice(0, 4)
                                      )
                                    }
                                    placeholder="Enter OTP"
                                    className={`${fieldBase} md:flex-1 text-center tracking-widest`}
                                  />
                                  <button
                                    type="button"
                                    onClick={verifyOtp}
                                    disabled={otpVerifyDisabled}
                                    className={[
                                      "px-4 py-2 rounded-xl text-sm font-semibold transition-colors",
                                      otpVerifyDisabled
                                        ? "bg-gray-200 text-gray-500 cursor-not-allowed"
                                        : "bg-emerald-600 text-white hover:bg-emerald-700",
                                    ].join(" ")}
                                  >
                                    {otpStatus === "verifying" ? "Verifying..." : "Verify OTP"}
                                  </button>
                                </div>
                              </div>
                            )}

                            {otpMessage ? (
                              <p className="text-xs text-emerald-600">{otpMessage}</p>
                            ) : null}
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
                            onChange={(e) =>
                              setDetails((p) => ({
                                ...p,
                                city: e.target.value,
                              }))
                            }
                            placeholder="Enter city (e.g. Gurugram)"
                            className={`${fieldBase} pl-12 md:pl-12`}
                          />
                        </div>
                        <p className="text-xs text-gray-500">
                          Helps us route your case faster to nearby vets
                        </p>
                      </div>
                    </div>
                  </div>
                </section>

                  {/* Pet details */}
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <PawPrint size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">
                          Pet details
                        </h3>
                        <p className="text-xs text-gray-500">
                          Tell us about your furry friend
                        </p>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      <div className="space-y-5">
                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700">
                          Pet&apos;s Name{" "}
                          <span className="text-red-500">*</span>
                        </label>
                        <div className="relative">
                          <PawPrint
                            size={18}
                            className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                          />
                          <input
                            type="text"
                            value={details.name}
                            onChange={(e) =>
                              setDetails((p) => ({ ...p, name: e.target.value }))
                            }
                            placeholder="Enter your pet's name"
                            className={`${fieldBase} pl-12 md:pl-12`}
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
                              className={[
                                "p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all duration-200",
                                "md:p-5 md:flex-row md:justify-center md:gap-3 md:rounded-2xl",
                                details.type === type
                                  ? "border-[#3998de] bg-[#3998de]/5 text-[#3998de]"
                                  : "border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:bg-gray-100",
                              ].join(" ")}
                            >
                              <div
                                className={
                                  details.type === type
                                    ? "text-[#3998de]"
                                    : "text-gray-500"
                                }
                              >
                                {getPetTypeIcon(type)}
                              </div>
                              <span className="capitalize text-sm font-medium md:text-base">
                                {type}
                              </span>
                            </button>
                          ))}
                        </div>
                      </div>

                      {/* ✅ NEW: Gender (required) */}
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
                            onChange={(e) =>
                              setDetails((p) => ({ ...p, gender: e.target.value }))
                            }
                            className={`${selectBase} pl-12 md:pl-12`}
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
                                !loadingBreeds && breedOptions.length
                                  ? setBreedDropdownOpen((prev) => !prev)
                                  : null
                              }
                              className={`${selectBase} text-left`}
                              disabled={loadingBreeds || breedOptions.length === 0}
                            >
                              {loadingBreeds
                                ? `Loading ${details.type} breeds...`
                                : selectedBreedLabel ||
                                  `Select ${details.type} breed`}
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
                                        className={`w-full px-4 py-2 text-left text-sm hover:bg-gray-50 ${
                                          details.breed === b.value
                                            ? "bg-gray-50 font-semibold text-gray-900"
                                            : "text-gray-700"
                                        }`}
                                      >
                                        {b.label}
                                      </button>
                                    ))
                                  ) : (
                                    <div className="px-4 py-2 text-sm text-gray-500">
                                      No breeds found
                                    </div>
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

                      {/* Exotic detail mandatory */}
                      {isExotic && (
                        <div className="space-y-2">
                          <label className="block text-sm font-medium text-gray-700">
                            Which exotic pet?{" "}
                            <span className="text-red-500">*</span>
                          </label>
                          <div className="relative">
                            <Rabbit
                              size={18}
                              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                            />
                            <input
                              type="text"
                              value={details.exoticType}
                              onChange={(e) =>
                                setDetails((p) => ({
                                  ...p,
                                  exoticType: e.target.value,
                                }))
                              }
                              placeholder="e.g. Parrot, Rabbit, Turtle, Guinea pig"
                              className={`${fieldBase} pl-12 md:pl-12`}
                            />
                          </div>
                          <p className="text-xs text-gray-500">
                            This helps us match the right vet specialist
                          </p>
                        </div>
                      )}

                      {/* DOB */}
                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700">
                          Pet&apos;s Date of Birth{" "}
                          <span className="text-red-500">*</span>
                        </label>

                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3 md:items-start md:gap-4">
                          <div className="space-y-1.5">
                            <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                              DOB
                            </p>
                            <div className="relative">
                              <Calendar
                                size={18}
                                className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                              />
                              <input
                                type="date"
                                value={details.petDob}
                                max={todayISO()}
                                onChange={(e) =>
                                  setDetails((p) => ({ ...p, petDob: e.target.value }))
                                }
                                className={`${fieldBase} pl-12 md:pl-12`}
                              />
                            </div>
                          </div>

                          <div className="hidden md:block space-y-1.5">
                            <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                              Approximate age
                            </p>
                            <div className="flex h-[46px] items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-3.5">
                              <p className="text-xs text-gray-500">
                                Auto-calculated
                              </p>
                              <p className="text-sm font-bold text-[#3998de]">
                                {calcAgeFromDob(details.petDob) || "--"}
                              </p>
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
                                onChange={(e) =>
                                  setDetails((p) => ({
                                    ...p,
                                    weightKg: e.target.value,
                                  }))
                                }
                                placeholder="e.g. 12.5"
                                className={`${fieldBase} pl-11 md:pl-11`}
                              />
                            </div>
                            <p className="text-[11px] text-gray-500">
                              Optional
                            </p>
                          </div>
                        </div>

                        <p className="text-xs text-gray-500 flex items-center gap-1">
                          <Clock size={12} className="text-[#3998de]" />
                          DOB helps the vet understand age-specific health risks
                        </p>
                      </div>

                      <div className="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
                        <div className="space-y-2">
                          <label className="block text-sm font-medium text-gray-700">
                            Is your pet neutered?
                          </label>
                          <div className="relative">
                            <CheckCircle2
                              size={18}
                              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                            />
                            <select
                              value={details.isNeutered}
                              onChange={(e) =>
                                setDetails((p) => ({
                                  ...p,
                                  isNeutered: e.target.value,
                                }))
                              }
                              className={`${selectBase} pl-12 md:pl-12`}
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
                          <label className="block text-sm font-medium text-gray-700">
                            Vaccinated?
                          </label>
                          <div className="relative">
                            <Shield
                              size={18}
                              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                            />
                            <select
                              value={details.vaccinatedYesNo}
                              onChange={(e) =>
                                setDetails((p) => ({
                                  ...p,
                                  vaccinatedYesNo: e.target.value,
                                }))
                              }
                              className={`${selectBase} pl-12 md:pl-12`}
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
                          <label className="block text-sm font-medium text-gray-700">
                            Deworming done recently?
                          </label>
                          <div className="relative">
                            <Activity
                              size={18}
                              className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                            />
                            <select
                              value={details.dewormingYesNo}
                              onChange={(e) =>
                                setDetails((p) => ({
                                  ...p,
                                  dewormingYesNo: e.target.value,
                                }))
                              }
                              className={`${selectBase} pl-12 md:pl-12`}
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
                </section>

                  {/* Describe problem */}
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <FileText size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">
                          Describe the problem
                        </h3>
                        <p className="text-xs text-gray-500">
                          Help us understand what&apos;s happening
                        </p>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      <div className="flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                        <Lightbulb size={14} className="mt-0.5 text-blue-600" />
                        <p>
                          The more detail you share, the faster the vet can help.
                          Include when it started and any changes in eating or
                          behavior.
                        </p>
                      </div>

                      <div className="space-y-5">
                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700">
                          What symptoms are you noticing?{" "}
                          <span className="text-red-500">*</span>
                        </label>

                        <textarea
                          value={details.problemText}
                          onChange={(e) =>
                            setDetails((p) => ({
                              ...p,
                              problemText: e.target.value,
                            }))
                          }
                          placeholder="Example: My dog has been limping since yesterday, not putting weight on front leg, and cries when touched. He's also less active than usual..."
                          rows={4}
                          className={textareaBase}
                        />

                        <div className="flex items-center justify-between text-xs">
                          <span className="text-gray-500">
                            Please include duration and severity
                          </span>
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
                              onChange={(e) =>
                                setDetails((p) => ({
                                  ...p,
                                  lastDaysEnergy: e.target.value,
                                }))
                              }
                              className={`${selectBase} pl-12 md:pl-12`}
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
                                setDetails((p) => ({
                                  ...p,
                                  lastDaysAppetite: e.target.value,
                                }))
                              }
                              className={`${selectBase} pl-12 md:pl-12`}
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
                              onChange={(e) =>
                                setDetails((p) => ({
                                  ...p,
                                  mood: e.target.value,
                                }))
                              }
                              className={`${selectBase} pl-12 md:pl-12`}
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
                </section>

                  {/* Upload */}
                  <section className={cardBase}>
                    <div className={cardHeaderBase}>
                      <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                        <Camera size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-base">
                          Photo or Document
                        </h3>
                        <p className="text-xs text-gray-500">
                          Show us what&apos;s happening
                        </p>
                      </div>
                    </div>

                    <div className={cardBodyBase}>
                      <div className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900">
                        <CheckCircle2 size={14} className="mt-0.5 text-emerald-600" />
                        <p>
                          A clear photo helps the vet assess faster. For wounds,
                          swelling, or rashes, one photo can reduce back and forth.
                        </p>
                      </div>

                    <label
                      htmlFor="petUploadGallery"
                      className={[
                        "flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200 md:h-48 md:rounded-2xl",
                        isDragging
                          ? "border-[#3998de] bg-[#3998de]/5 ring-4 ring-[#3998de]/10"
                          : "border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-gray-400",
                        details.hasPhoto && uploadFile
                          ? "bg-emerald-50/30 border-emerald-300"
                          : "",
                      ].join(" ")}
                      onDragEnter={handleDragEnter}
                      onDragOver={handleDragOver}
                      onDragLeave={handleDragLeave}
                      onDrop={handleDrop}
                    >
                      <div className="flex flex-col items-center justify-center pt-5 pb-6">
                        {details.hasPhoto ? (
                          <>
                            <CheckCircle2 className="w-10 h-10 text-emerald-500 mb-3 md:w-12 md:h-12" />
                            <p className="mb-1 text-sm text-gray-700 font-medium md:text-base">
                              File ready to upload
                            </p>
                          </>
                        ) : (
                          <>
                            <Upload className="w-10 h-10 text-[#3998de] mb-3 md:w-12 md:h-12" />
                            <p className="mb-1 text-sm text-gray-700 font-medium md:text-base">
                              {isDragging
                                ? "Drop to upload"
                                : "Upload photo or document"}
                            </p>
                          </>
                        )}
                        <p className="text-xs text-gray-500 md:text-sm">
                          {isDragging
                            ? "Release to start upload"
                            : "Drag & drop or click to browse"}
                        </p>
                        <p className="text-xs text-gray-400 mt-1">
                          Supports JPG, PNG, PDF (max 50MB)
                        </p>
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
                        className="inline-flex items-center gap-2 rounded-full border border-[#3998de]/30 bg-white px-3 py-1.5 text-xs font-semibold text-[#3998de] shadow-sm transition hover:border-[#3998de]/60"
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
                        className="inline-flex items-center gap-2 rounded-full border border-[#3998de]/30 bg-white px-3 py-1.5 text-xs font-semibold text-[#3998de] shadow-sm transition hover:border-[#3998de]/60"
                      >
                        <Upload className="h-4 w-4" />
                        Gallery
                      </label>
                    </div>

                    <div className="mt-4 space-y-2">
                      <label className="block text-sm font-medium text-gray-700">
                        Additional document URL (optional)
                      </label>
                      <div className="relative">
                        <FileText
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                        />
                        <input
                          type="url"
                          value={details.petDoc2}
                          onChange={(e) =>
                            setDetails((p) => ({
                              ...p,
                              petDoc2: e.target.value,
                            }))
                          }
                          placeholder="https://example.com/report.png"
                          className={`${fieldBase} pl-12 md:pl-12`}
                        />
                      </div>
                      <p className="text-xs text-gray-500">
                        Paste a report link if you already have one
                      </p>
                    </div>

                    {uploadFile && (
                      <div className="mt-4 bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div className="flex items-start gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-[#3998de] shadow-sm">
                            {uploadIcon}
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-start justify-between">
                              <div>
                                <p className="text-sm font-semibold text-gray-900 truncate max-w-[200px] md:max-w-xs">
                                  {uploadFile.name}
                                </p>
                                <p className="text-xs text-gray-500 mt-0.5">
                                  {uploadLabel} •{" "}
                                  {(uploadFile.size / 1024 / 1024).toFixed(2)} MB
                                  {uploadMeta?.compressedSize && (
                                    <span className="text-emerald-600 ml-1">
                                      →{" "}
                                      {(
                                        uploadMeta.compressedSize /
                                        1024 /
                                        1024
                                      ).toFixed(2)}{" "}
                                      MB (compressed)
                                    </span>
                                  )}
                                </p>
                              </div>
                              <button
                                type="button"
                                onClick={() => {
                                  setUploadFile(null);
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
                      <Image size={14} className="text-[#3998de]" />
                      Clear, well-lit photos help vets assess faster.
                    </p>
                  </div>
                </section>

                <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                  <div className="text-xs font-semibold text-emerald-700 flex items-center gap-2">
                    <CheckCircle2 size={14} className="text-emerald-600" />
                    What happens after you tap Continue
                  </div>
                  <div className="mt-3 grid grid-cols-3 gap-2 text-center text-[11px] text-gray-600">
                    <div className="space-y-1">
                      <div className="mx-auto flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white">
                        1
                      </div>
                      <div className="font-semibold text-gray-800">Review and Pay</div>
                      <div>See your total before confirming</div>
                    </div>
                    <div className="space-y-1">
                      <div className="mx-auto flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white">
                        2
                      </div>
                      <div className="font-semibold text-gray-800">Vet Notified</div>
                      <div>Instantly sees your case</div>
                    </div>
                    <div className="space-y-1">
                      <div className="mx-auto flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white">
                        3
                      </div>
                      <div className="font-semibold text-gray-800">Video Call</div>
                      <div>Usually within 8 to 15 minutes</div>
                    </div>
                  </div>
                </div>

                {submitError && (
                  <div className="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4">
                    <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
                    <p className="text-sm">{submitError}</p>
                  </div>
                )}

                <div className="rounded-xl border border-gray-200 bg-white p-5">
                  <Button
                    onClick={submitObservation}
                    disabled={!isValid || submitting}
                    title={!isValid ? getSubmitTooltip() : undefined}
                    className={`w-full text-base font-semibold md:py-4 md:rounded-xl ${
                      !isValid || submitting
                        ? "opacity-50 cursor-not-allowed bg-gray-300 hover:bg-gray-300"
                        : "bg-[#3998de] hover:bg-[#3998de]/90 text-white shadow-lg shadow-[#3998de]/30"
                    }`}
                  >
                    {submitting ? (
                      <span className="flex items-center justify-center gap-2">
                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        Submitting...
                      </span>
                    ) : (
                      "Continue to Payment"
                    )}
                  </Button>

                  <div className="mt-3 flex flex-wrap items-center justify-center gap-3 text-[11px] text-gray-500">
                    <div className="flex items-center gap-1">
                      <Shield size={12} className="text-[#3998de]" />
                      SSL secured
                    </div>
                    <span className="text-gray-300">|</span>
                    <div>Razorpay</div>
                    <span className="text-gray-300">|</span>
                    <div>UPI / Card / Net Banking</div>
                  </div>

                  {!isValid ? (
                    <div className="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                      <p className="text-xs text-amber-700 flex items-center gap-1.5">
                        <AlertCircle size={14} />
                        {getSubmitTooltip()}
                      </p>
                    </div>
                  ) : submitting ? (
                    <p className="text-sm text-gray-500 mt-4 text-center">
                      Uploading your files...
                    </p>
                  ) : (
                    <p className="text-sm text-gray-500 mt-4 text-center">
                      All fields completed. Ready for payment.
                    </p>
                  )}
                </div>

                <div className="h-24 md:hidden" />
              </div>

              {/* RIGHT COLUMN */}
              <div className="space-y-6 md:sticky md:top-24 md:self-start">
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                  <div className="flex items-center gap-3 bg-[#2563eb] px-5 py-4 text-white">
                    {vet?.image ? (
                      <img
                        src={vet.image}
                        alt={vetName}
                        className="h-10 w-10 rounded-full object-cover border border-white/30"
                        loading="lazy"
                      />
                    ) : (
                      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-xs font-semibold">
                        {getInitials(vetName)}
                      </div>
                    )}
                    <div>
                      <div className="text-sm font-semibold">{vetName}</div>
                      <div className="text-xs text-white/80">{vetMetaLine}</div>
                      <div className="mt-1 inline-flex items-center gap-1 rounded-full border border-amber-200/50 bg-white/15 px-2 py-0.5 text-[10px] font-semibold text-amber-100">
                        Verified
                      </div>
                      {showSnoutiqHighlight ? (
                        <div className="mt-1 inline-flex items-center gap-1 rounded-full border border-emerald-200/50 bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-100">
                          SnoutIQ Best Match
                        </div>
                      ) : null}
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-px bg-gray-100">
                    <div className="bg-white p-3 text-center">
                      <div className="text-base font-semibold text-[#2563eb]">
                        {vetConsultationDisplay}
                      </div>
                      <div className="text-[10px] text-gray-500">{vetConsultationLabel}</div>
                    </div>
                    <div className="bg-white p-3 text-center">
                      <div className="text-base font-semibold text-[#2563eb]">
                        {vetRating}
                      </div>
                      <div className="text-[10px] text-gray-500">Avg rating</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 border-t border-gray-100 px-5 py-3 text-xs font-semibold text-emerald-700">
                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                    {vetResponseText}
                  </div>

                  {vetProfileItems.length ? (
                    <div className="border-t border-gray-100 px-5 py-3">
                      <div className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                        Doctor Profile
                      </div>
                      <div className="mt-2 space-y-1.5">
                        {vetProfileItems.map((item) => (
                          <div
                            key={item.key}
                            className="flex items-start justify-between gap-3 text-[11px]"
                          >
                            <span className="shrink-0 text-gray-500">{item.label}</span>
                            <span className="text-right font-medium text-gray-800">
                              {item.value}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  ) : null}

                  {vetBio ? (
                    <div className="border-t border-gray-100 px-5 py-3">
                      <div className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                        About Doctor
                      </div>
                      <p className="mt-2 max-h-28 overflow-y-auto whitespace-pre-line pr-1 text-xs leading-5 text-gray-700">
                        {vetBio}
                      </p>
                    </div>
                  ) : null}
                </div>

                <div className="hidden md:block rounded-xl border border-gray-200 bg-white p-5">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="h-9 w-9 rounded-lg bg-[#3998de]/10 flex items-center justify-center">
                      <FileText size={20} className="text-[#3998de]" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900 text-sm">Quick Summary</h3>
                      <p className="text-xs text-gray-500">Review your information</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <div className="bg-gray-50 rounded-xl p-4">
                      <div className="flex items-center gap-2 mb-2">
                        <User size={14} className="text-gray-500" />
                        <span className="text-xs font-medium text-gray-500 uppercase">Owner</span>
                      </div>
                      <div className="space-y-1.5">
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Name</span>
                          <span className="text-sm font-medium text-gray-900">{details.ownerName || "-"}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Mobile</span>
                          <span className="text-sm font-medium text-gray-900">
                            {details.ownerMobile ? `+91 ${details.ownerMobile}` : "-"}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="bg-gray-50 rounded-xl p-4">
                      <div className="flex items-center gap-2 mb-2">
                        <PawPrint size={14} className="text-gray-500" />
                        <span className="text-xs font-medium text-gray-500 uppercase">Pet</span>
                      </div>
                      <div className="space-y-1.5">
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Name</span>
                          <span className="text-sm font-medium text-gray-900 capitalize">{details.name || "-"}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Type</span>
                          <span className="text-sm font-medium text-gray-900 capitalize">{details.type || "-"}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Gender</span>
                          <span className="text-sm font-medium text-gray-900 capitalize">{details.gender || "-"}</span>
                        </div>
                        {showBreed && (
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Breed</span>
                            <span className="text-sm font-medium text-gray-900 capitalize">
                              {details.breed?.replace(/_/g, " ") || "-"}
                            </span>
                          </div>
                        )}
                        {isExotic && (
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Exotic</span>
                            <span className="text-sm font-medium text-gray-900">{details.exoticType || "-"}</span>
                          </div>
                        )}
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Age</span>
                          <span className="text-sm font-medium text-gray-900">{approxAge || "-"}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Neutered</span>
                          <span className="text-sm font-medium text-gray-900">
                            {details.isNeutered === "1"
                              ? "Yes"
                              : details.isNeutered === "0"
                              ? "No"
                              : "-"}
                          </span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Vaccinated</span>
                          <span className="text-sm font-medium text-gray-900">
                            {details.vaccinatedYesNo === "1"
                              ? "Yes"
                              : details.vaccinatedYesNo === "0"
                              ? "No"
                              : "-"}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="bg-gray-50 rounded-xl p-4">
                      <div className="flex items-center gap-2 mb-2">
                        <Activity size={14} className="text-gray-500" />
                        <span className="text-xs font-medium text-gray-500 uppercase">Health Status</span>
                      </div>
                      <div className="space-y-1.5">
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Energy</span>
                          <span className="text-sm font-medium text-gray-900 capitalize">
                            {details.lastDaysEnergy?.replace(/_/g, " ") || "-"}
                          </span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Appetite</span>
                          <span className="text-sm font-medium text-gray-900 capitalize">
                            {details.lastDaysAppetite?.replace(/_/g, " ") || "-"}
                          </span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-xs text-gray-500">Mood</span>
                          <span className="text-sm font-medium text-gray-900 capitalize">{details.mood || "-"}</span>
                        </div>
                      </div>
                    </div>

                    {details.problemText && (
                      <div className="bg-gray-50 rounded-xl p-4">
                        <div className="flex items-center gap-2 mb-2">
                          <FileText size={14} className="text-gray-500" />
                          <span className="text-xs font-medium text-gray-500 uppercase">Problem</span>
                        </div>
                        <p className="text-sm text-gray-700 line-clamp-3">{details.problemText}</p>
                      </div>
                    )}

                    <div className="bg-gray-50 rounded-xl p-4">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <Camera size={14} className="text-gray-500" />
                          <span className="text-xs font-medium text-gray-500 uppercase">Photo/Document</span>
                        </div>
                        <span
                          className={[
                            "text-xs font-medium px-3 py-1.5 rounded-full border",
                            details.hasPhoto && uploadFile
                              ? "text-emerald-700 bg-emerald-50 border-emerald-200"
                              : "text-red-700 bg-red-50 border-red-200",
                          ].join(" ")}
                        >
                          {details.hasPhoto && uploadFile ? "Added" : "Required"}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-5">
                  <div className="flex items-start justify-between">
                    <div>
                      <div className="text-lg font-semibold text-gray-900">{sidebarRating}</div>
                      <div className="text-xs text-gray-500">
                        {sidebarConsultations === null
                          ? "Doctor profile verified"
                          : `from ${sidebarConsultations} consultations`}
                      </div>
                    </div>
                    <div className="flex items-center gap-0.5 text-amber-400">
                      {Array.from({ length: 5 }).map((_, idx) => (
                        <Star key={`sidebar-star-${idx}`} size={14} className="fill-current" />
                      ))}
                    </div>
                  </div>

                  <div className="mt-4 border-t border-gray-100 pt-4">
                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                      Reviews
                    </div>
                    <div className="mt-3 space-y-3">
                      {SIDEBAR_REVIEWS.map((review) => (
                        <div key={review.id} className="rounded-lg bg-gray-50 p-3">
                          <div className="flex items-center justify-between gap-2">
                            <div className="text-xs font-semibold text-gray-800">{review.author}</div>
                            <div className="flex items-center gap-0.5 text-amber-400">
                              {Array.from({ length: 5 }).map((_, idx) => (
                                <Star
                                  key={`${review.id}-star-${idx}`}
                                  size={12}
                                  className="fill-current"
                                />
                              ))}
                            </div>
                          </div>
                          <p className="mt-1 text-xs leading-5 text-gray-600">{review.text}</p>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="hidden md:block h-28" />
          </div>
        </div>
      </div>

      {/* Mobile CTA */}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 safe-area-pb max-w-md mx-auto z-20 md:hidden shadow-lg">
        <div className="space-y-2">
          <Button
            onClick={submitObservation}
            fullWidth
            disabled={!isValid || submitting}
            className={
              !isValid || submitting
                ? "opacity-50 cursor-not-allowed bg-gray-300"
                : "bg-[#3998de] hover:bg-[#3998de]/90 text-white shadow-lg"
            }
          >
            {submitting ? "Submitting..." : "Continue to Payment"}
          </Button>
          {!isValid && (
            <p className="text-xs text-red-600 text-center px-2">
              {getSubmitTooltip()}
            </p>
          )}
        </div>
      </div>
    </div>
  );
};

export default PetDetailsScreen;





