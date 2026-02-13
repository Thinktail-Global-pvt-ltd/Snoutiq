import React, { useEffect, useMemo, useState } from "react";
import { SPECIALTY_ICONS } from "../../constants";
import { Button } from "../components/Button";
import { Header, ProgressBar } from "../components/Sharedcomponents";
import {
  CheckCircle2,
  ChevronDown,
  FileText,
  Image,
  Upload,
  Star,
  User,
  Phone,
  Calendar,
  Activity,
  Coffee,
  Heart,
  PawPrint,
  AlertCircle,
  Camera,
  Dog,
  Cat,
  Rabbit,
  Shield,
  Clock,
} from "lucide-react";

const CAT_BREEDS = [
  { label: "Indian Street Cat", value: "indian_street_cat" },
  { label: "Persian", value: "persian" },
  { label: "Siamese", value: "siamese" },
  { label: "Maine Coon", value: "maine_coon" },
  { label: "Bengal", value: "bengal" },
  { label: "Ragdoll", value: "ragdoll" },
  { label: "British Shorthair", value: "british_shorthair" },
  { label: "Sphynx", value: "sphynx" },
  { label: "Mixed / Other", value: "other" },
];

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

  if (days < 0) {
    months -= 1;
  }

  if (months < 0) {
    years -= 1;
    months += 12;
  }

  if (years <= 0 && months <= 0) {
    return "Less than 1 month";
  }

  if (years <= 0) {
    return `${months} mo${months === 1 ? "" : "s"}`;
  }

  if (months === 0) {
    return `${years} yr${years === 1 ? "" : "s"}`;
  }

  return `${years} yr${years === 1 ? "" : "s"} ${months} mo${
    months === 1 ? "" : "s"
  }`;
};

// Enhanced input styling with professional placeholders
const fieldBase =
  "w-full rounded-xl border border-gray-200 bg-white p-3.5 text-gray-900 placeholder:text-gray-400 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] focus:bg-white hover:border-gray-300 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed md:rounded-2xl md:p-4 md:text-base";
const selectBase = `${fieldBase} appearance-none pr-12`;
const textareaBase = `${fieldBase} resize-none min-h-[120px]`;

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

const formatPhone = (value) => {
  const digits = String(value || "").replace(/\D/g, "");
  if (!digits) return "";
  if (digits.startsWith("91")) return digits;
  return `91${digits}`;
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

const PetDetailsScreen = ({ onSubmit, onBack }) => {
  const [details, setDetails] = useState({
    ownerName: "",
    ownerMobile: "",
    name: "",
    type: null,
    breed: "",
    petDob: "",
    problemText: "",
    mood: "calm",
    petDoc2: "",
    exoticType: "",
    lastDaysEnergy: "",
    lastDaysAppetite: "",
    hasPhoto: false,
  });

  const [uploadFile, setUploadFile] = useState(null);
  const [uploadPreviewUrl, setUploadPreviewUrl] = useState("");
  const [uploadMeta, setUploadMeta] = useState(null);
  const [isDragging, setIsDragging] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");

  const [dogBreeds, setDogBreeds] = useState([]);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [breedError, setBreedError] = useState("");

  const applyUploadFile = async (file) => {
    if (!file) return;

    setSubmitError("");

    const lowerName = file.name?.toLowerCase() || "";
    const isVideo =
      file.type?.startsWith("video/") ||
      /\.(mp4|mov|avi|mkv|webm)$/i.test(lowerName);
    if (isVideo) {
      setSubmitError("Video uploads are not supported. Please upload a photo or PDF.");
      return;
    }

    const isImage = file.type?.startsWith("image/");
    const isPdf =
      file.type === "application/pdf" || lowerName.endsWith(".pdf");
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

  const handleDragOver = (e) => {
    e.preventDefault();
  };

  const handleDragEnter = (e) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  useEffect(() => {
    const fetchDogBreeds = async () => {
      setBreedError("");
      setLoadingBreeds(true);

      try {
        const res = await fetch("https://snoutiq.com/backend/api/dog-breeds/all", {
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
          list.push({ label: "Mixed Breed", value: "mixed_breed" }, { label: "Other", value: "other" });

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

    if (details.type === "dog") fetchDogBreeds();

    if (details.type !== "dog") {
      setDogBreeds([]);
      setBreedError("");
    }

    if (details.type === "exotic") {
      setDetails((p) => ({ ...p, breed: "" }));
    } else {
      setDetails((p) => ({ ...p, exoticType: "" }));
    }
  }, [details.type]);

  const breedOptions = useMemo(() => {
    if (details.type === "dog") return dogBreeds;
    if (details.type === "cat") return CAT_BREEDS;
    return [];
  }, [details.type, dogBreeds]);

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

  const isValid =
    details.ownerName.trim().length > 0 &&
    details.ownerMobile.replace(/\D/g, "").length === 10 &&
    details.name.trim().length > 0 &&
    details.type !== null &&
    details.petDob &&
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
    if (details.ownerMobile.replace(/\D/g, "").length !== 10) return "Please enter 10-digit mobile number";
    if (!details.name.trim()) return "Please enter your pet's name";
    if (!details.type) return "Please select pet type";
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
          prev
            ? { ...prev, compressedSize: compressed?.size ?? null }
            : prev
        );
      }

      const fd = new FormData();
      fd.append("name", details.ownerName);
      fd.append("phone", formatPhone(details.ownerMobile));
      fd.append("type", details.type || "");
      fd.append("dob", details.petDob || "");
      fd.append("pet_name", details.name || "");

      const breedValue =
        details.type === "exotic"
          ? details.exoticType.trim()
          : details.breed || "";
      fd.append("breed", breedValue);

      fd.append("reported_symptom", details.problemText || "");
      fd.append("appetite", details.lastDaysAppetite || "");
      fd.append("energy", details.lastDaysEnergy || "");

      fd.append("mood", details.mood || "calm");

      if (details.petDoc2?.trim()) {
        fd.append("pet_doc2", details.petDoc2.trim());
      }

      if (fileToSend) {
        fd.append("file", fileToSend);
      }

      const res = await fetch("https://snoutiq.com/backend/api/user-pet-observation", {
        method: "POST",
        body: fd,
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(data?.message || "Failed to submit observation");
      }

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

  // Get icon for pet type
  const getPetTypeIcon = (type) => {
    switch(type) {
      case 'dog': return <Dog size={20} />;
      case 'cat': return <Cat size={20} />;
      case 'exotic': return <Rabbit size={20} />;
      default: return <PawPrint size={20} />;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white flex flex-col">
      <Header onBack={onBack} title="Tell us about your pet" subtitle="Help us understand your pet's needs" />

      <div className="w-full">
        <div className="flex-1 px-6 py-6 pb-32 overflow-y-auto no-scrollbar md:px-12 lg:px-20 md:py-12">
          <div className="md:max-w-none">
            <div className="md:flex md:items-center md:justify-between md:gap-6">
              <ProgressBar current={1} total={3} />
              <div className="hidden md:block text-sm text-gray-500 bg-white px-4 py-2 rounded-full border border-gray-100">
                ⏱️ Takes less than 2 minutes
              </div>
            </div>

            <div className="mt-8 md:grid md:grid-cols-12 md:gap-10 lg:gap-14">
              {/* LEFT COLUMN - Main Form */}
              <div className="md:col-span-7 lg:col-span-7">
                <div className="space-y-8">
                  {/* Owner details - Enhanced */}
                  <section className="bg-white rounded-2xl border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.04)] p-6 md:p-8 space-y-6">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-[#3998de]/10 flex items-center justify-center">
                        <User size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-lg">Owner details</h3>
                        <p className="text-xs text-gray-500">Used only for appointment updates</p>
                      </div>
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
                              setDetails((p) => ({ ...p, ownerName: e.target.value }))
                            }
                            placeholder="Enter your full name"
                            className={`${fieldBase} pl-12 md:pl-12`}
                          />
                        </div>
                      </div>

                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700">
                          Pet Owner Mobile <span className="text-red-500">*</span>
                        </label>
                        <div className="relative">
                          <Phone
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
                      </div>
                    </div>
                  </section>

                  {/* Pet details - Enhanced */}
                  <section className="bg-white rounded-2xl border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.04)] p-6 md:p-8 space-y-6">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-[#3998de]/10 flex items-center justify-center">
                        <PawPrint size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-lg">Pet details</h3>
                        <p className="text-xs text-gray-500">Tell us about your furry friend</p>
                      </div>
                    </div>

                    <div className="space-y-5">
                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700">
                          Pet's Name <span className="text-red-500">*</span>
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
                              <div className={details.type === type ? "text-[#3998de]" : "text-gray-500"}>
                                {getPetTypeIcon(type)}
                              </div>
                              <span className="capitalize text-sm font-medium md:text-base">
                                {type}
                              </span>
                            </button>
                          ))}
                        </div>
                      </div>

                      {/* Breed for dog/cat */}
                      {showBreed && (
                        <div className="space-y-2">
                          <label className="block text-sm font-medium text-gray-700">
                            Breed <span className="text-red-500">*</span>
                          </label>

                          <div className="relative">
                            <select
                              value={details.breed}
                              onChange={(e) =>
                                setDetails((p) => ({ ...p, breed: e.target.value }))
                              }
                              disabled={
                                (details.type === "dog" && loadingBreeds) ||
                                breedOptions.length === 0
                              }
                              className={selectBase}
                            >
                              <option value="">
                                {details.type === "dog" && loadingBreeds
                                  ? "Loading dog breeds..."
                                  : `Select ${details.type} breed`}
                              </option>
                              {breedOptions.map((b) => (
                                <option key={b.value} value={b.value}>
                                  {b.label}
                                </option>
                              ))}
                            </select>
                            <ChevronDown
                              className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                            />
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
                              onChange={(e) =>
                                setDetails((p) => ({ ...p, exoticType: e.target.value }))
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
                          Pet's Date of Birth <span className="text-red-500">*</span>
                        </label>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6">
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

                          <div className="hidden md:flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-5 py-3">
                            <div>
                              <div className="text-sm font-medium text-gray-700">Approximate age</div>
                              <div className="text-xs text-gray-500">Auto-calculated from DOB</div>
                            </div>
                            <div className="text-lg font-bold text-[#3998de]">
                              {calcAgeFromDob(details.petDob) || "—"}
                            </div>
                          </div>
                        </div>

                        <p className="text-xs text-gray-500 flex items-center gap-1">
                          <Clock size={12} className="text-[#3998de]" />
                          DOB helps the vet understand age-specific health risks
                        </p>
                      </div>
                    </div>
                  </section>

                  {/* Describe problem - Enhanced */}
                  <section className="bg-white rounded-2xl border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.04)] p-6 md:p-8 space-y-6">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-[#3998de]/10 flex items-center justify-center">
                        <FileText size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-lg">Describe the problem</h3>
                        <p className="text-xs text-gray-500">Help us understand what's happening</p>
                      </div>
                    </div>

                    <div className="space-y-5">
                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700">
                          What symptoms are you noticing? <span className="text-red-500">*</span>
                        </label>

                        <textarea
                          value={details.problemText}
                          onChange={(e) =>
                            setDetails((p) => ({ ...p, problemText: e.target.value }))
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
                                setDetails((p) => ({ ...p, mood: e.target.value }))
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
                  </section>

                  {/* Upload - Enhanced */}
                  <section className="bg-white rounded-2xl border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.04)] p-6 md:p-8 space-y-5">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-[#3998de]/10 flex items-center justify-center">
                        <Camera size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-lg">Photo or Document</h3>
                        <p className="text-xs text-gray-500">Show us what's happening</p>
                      </div>
                    </div>

                    <label
                      htmlFor="petUploadGallery"
                      className={[
                        "flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl cursor-pointer transition-all duration-200 md:h-48 md:rounded-2xl",
                        isDragging
                          ? "border-[#3998de] bg-[#3998de]/5 ring-4 ring-[#3998de]/10"
                          : "border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-gray-400",
                        details.hasPhoto && uploadFile ? "bg-emerald-50/30 border-emerald-300" : "",
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
                              {isDragging ? "Drop to upload" : "Upload photo or document"}
                            </p>
                          </>
                        )}
                        <p className="text-xs text-gray-500 md:text-sm">
                          {isDragging ? "Release to start upload" : "Drag & drop or click to browse"}
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

                    {/* Preview + meta */}
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

                    <p className="text-sm text-gray-500 flex items-center gap-2 bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                      <Image size={16} className="text-[#3998de]" />
                      <span className="text-xs">💡 Tip: Clear, well-lit photos help vets assess faster</span>
                    </p>
                  </section>

                  {submitError && (
                    <div className="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4">
                      <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
                      <p className="text-sm">{submitError}</p>
                    </div>
                  )}
                </div>

                <div className="h-24 md:hidden" />
              </div>

              {/* RIGHT COLUMN - Summary & CTA */}
              <div className="hidden md:block md:col-span-5 lg:col-span-5">
                <div className="sticky top-24 space-y-6">
                  <div className="bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.06)] p-8">
                    <div className="flex items-center gap-3 mb-5">
                      <div className="w-10 h-10 rounded-full bg-[#3998de]/10 flex items-center justify-center">
                        <FileText size={20} className="text-[#3998de]" />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-900 text-lg">Quick Summary</h3>
                        <p className="text-xs text-gray-500">Review your information</p>
                      </div>
                    </div>

                    <div className="space-y-4">
                      {/* Owner Info */}
                      <div className="bg-gray-50 rounded-xl p-4">
                        <div className="flex items-center gap-2 mb-2">
                          <User size={14} className="text-gray-500" />
                          <span className="text-xs font-medium text-gray-500 uppercase">Owner</span>
                        </div>
                        <div className="space-y-1.5">
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Name</span>
                            <span className="text-sm font-medium text-gray-900">
                              {details.ownerName || "—"}
                            </span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Mobile</span>
                            <span className="text-sm font-medium text-gray-900">
                              {details.ownerMobile ? `+91 ${details.ownerMobile}` : "—"}
                            </span>
                          </div>
                        </div>
                      </div>

                      {/* Pet Info */}
                      <div className="bg-gray-50 rounded-xl p-4">
                        <div className="flex items-center gap-2 mb-2">
                          <PawPrint size={14} className="text-gray-500" />
                          <span className="text-xs font-medium text-gray-500 uppercase">Pet</span>
                        </div>
                        <div className="space-y-1.5">
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Name</span>
                            <span className="text-sm font-medium text-gray-900 capitalize">
                              {details.name || "—"}
                            </span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Type</span>
                            <span className="text-sm font-medium text-gray-900 capitalize">
                              {details.type || "—"}
                            </span>
                          </div>
                          {showBreed && (
                            <div className="flex justify-between">
                              <span className="text-xs text-gray-500">Breed</span>
                              <span className="text-sm font-medium text-gray-900 capitalize">
                                {details.breed?.replace(/_/g, ' ') || "—"}
                              </span>
                            </div>
                          )}
                          {isExotic && (
                            <div className="flex justify-between">
                              <span className="text-xs text-gray-500">Exotic</span>
                              <span className="text-sm font-medium text-gray-900">
                                {details.exoticType || "—"}
                              </span>
                            </div>
                          )}
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Age</span>
                            <span className="text-sm font-medium text-gray-900">
                              {approxAge || "—"}
                            </span>
                          </div>
                        </div>
                      </div>

                      {/* Health Status */}
                      <div className="bg-gray-50 rounded-xl p-4">
                        <div className="flex items-center gap-2 mb-2">
                          <Activity size={14} className="text-gray-500" />
                          <span className="text-xs font-medium text-gray-500 uppercase">Health Status</span>
                        </div>
                        <div className="space-y-1.5">
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Energy</span>
                            <span className="text-sm font-medium text-gray-900 capitalize">
                              {details.lastDaysEnergy?.replace(/_/g, ' ') || "—"}
                            </span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Appetite</span>
                            <span className="text-sm font-medium text-gray-900 capitalize">
                              {details.lastDaysAppetite?.replace(/_/g, ' ') || "—"}
                            </span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-xs text-gray-500">Mood</span>
                            <span className="text-sm font-medium text-gray-900 capitalize">
                              {details.mood || "—"}
                            </span>
                          </div>
                        </div>
                      </div>

                      {/* Problem Summary */}
                      {details.problemText && (
                        <div className="bg-gray-50 rounded-xl p-4">
                          <div className="flex items-center gap-2 mb-2">
                            <FileText size={14} className="text-gray-500" />
                            <span className="text-xs font-medium text-gray-500 uppercase">Problem</span>
                          </div>
                          <p className="text-sm text-gray-700 line-clamp-3">
                            {details.problemText}
                          </p>
                        </div>
                      )}

                      {/* Photo Status */}
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
                            {details.hasPhoto && uploadFile ? "✓ Added" : "Required"}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* CTA Section */}
                  <div className="bg-white rounded-3xl border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.06)] p-8">
                    <Button
                      onClick={submitObservation}
                      disabled={!isValid || submitting}
                      title={!isValid ? getSubmitTooltip() : undefined}
                      className={`w-full md:text-lg md:py-4 md:rounded-xl font-semibold ${
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
                        "See Available Vets"
                      )}
                    </Button>

                    {!isValid ? (
                      <div className="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <p className="text-xs text-amber-700 flex items-center gap-1.5">
                          <AlertCircle size={14} />
                          {getSubmitTooltip()}
                        </p>
                      </div>
                    ) : submitting ? (
                      <p className="text-sm text-gray-500 mt-4 text-center">
                        ⚡ Compressing and uploading your files...
                      </p>
                    ) : (
                      <p className="text-sm text-gray-500 mt-4 text-center">
                        ✓ All fields completed. Ready to find your vet!
                      </p>
                    )}
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
            {submitting ? "Submitting..." : "See available vets"}
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
