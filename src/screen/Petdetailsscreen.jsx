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
  Video,
  Star,
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
  const d = new Date(dob);
  if (Number.isNaN(d.getTime())) return "";
  const now = new Date();
  if (d > now) return "";
  let years = now.getFullYear() - d.getFullYear();
  const m = now.getMonth() - d.getMonth();
  if (m < 0 || (m === 0 && now.getDate() < d.getDate())) years -= 1;
  if (years < 0) return "";
  return `${years} yr${years === 1 ? "" : "s"}`;
};

const fieldBase =
  "w-full rounded-xl border border-stone-200 bg-white p-3 text-stone-900 placeholder:text-stone-400 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] disabled:bg-stone-100 disabled:text-stone-400 disabled:cursor-not-allowed md:rounded-2xl md:p-4 md:text-base";
const selectBase = `${fieldBase} appearance-none pr-10`;
const textareaBase = `${fieldBase} resize-none`;

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
 * - Only compresses images (jpeg/png/webp). Video/PDF are sent as-is.
 * - Returns a NEW File (compressed) to append in FormData.
 */
const compressImageFile = async (
  file,
  {
    maxWidth = 1280,
    maxHeight = 1280,
    quality = 0.72, // 0..1 (jpeg/webp)
    outputMime = "image/jpeg", // "image/jpeg" | "image/webp"
  } = {}
) => {
  if (!file) return null;

  const isImage = file.type?.startsWith("image/");
  if (!isImage) return file;

  // If browser doesn't support canvas conversion for some formats, fallback to original
  const bitmap = await createImageBitmap(file).catch(() => null);
  if (!bitmap) return file;

  let { width, height } = bitmap;

  // Keep aspect ratio
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

  // If compression didn't reduce size, keep original
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
  const [uploadMeta, setUploadMeta] = useState(null); // {name,size,type,compressedSize?}
  const [isDragging, setIsDragging] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");

  const [dogBreeds, setDogBreeds] = useState([]);
  const [loadingBreeds, setLoadingBreeds] = useState(false);
  const [breedError, setBreedError] = useState("");

  const applyUploadFile = async (file) => {
    if (!file) return;

    setSubmitError("");

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
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
    if (uploadFile.type.startsWith("video/")) return "video";
    if (uploadFile.type === "application/pdf") return "pdf";
    return "file";
  }, [uploadFile]);
  const uploadIcon = useMemo(() => {
    if (uploadKind === "image") return <Image className="w-4 h-4" />;
    if (uploadKind === "video") return <Video className="w-4 h-4" />;
    return <FileText className="w-4 h-4" />;
  }, [uploadKind]);
  const uploadLabel = useMemo(() => {
    if (uploadKind === "image") return "Image";
    if (uploadKind === "video") return "Video";
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
    if (!details.ownerName.trim()) return "Enter owner name";
    if (details.ownerMobile.replace(/\D/g, "").length !== 10) return "Enter 10-digit mobile";
    if (!details.name.trim()) return "Enter pet name";
    if (!details.type) return "Select pet type";
    if (isExotic && !details.exoticType.trim()) return "Tell us which exotic pet";
    if (showBreed && !details.breed) return "Select breed";
    if (!details.petDob) return "Select pet DOB";
    if (details.problemText.trim().length <= 10) return "Describe the problem (min 10+ chars)";
    if (!details.lastDaysEnergy) return "Select energy";
    if (!details.lastDaysAppetite) return "Select appetite";
    if (!details.mood) return "Select mood";
    if (!details.hasPhoto || !uploadFile) return "Upload photo/video";
    return "";
  };

  const submitObservation = async () => {
    setSubmitError("");
    setSubmitting(true);

    try {
      // ✅ Compress ONLY image before sending (doctor ko compressed image jayegi)
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

      // ✅ file attach
      if (fileToSend) {
        fd.append("file", fileToSend);
      }

      const res = await fetch("https://snoutiq.com/backend/api/user-pet-observation", {
        method: "POST",
        body: fd,
        // ❌ Content-Type header mat set karo
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
      setSubmitError(e?.message || "Something went wrong");
    } finally {
      setSubmitting(false);
    }
  };

  // Cleanup preview URL
  useEffect(() => {
    return () => {
      if (uploadPreviewUrl) URL.revokeObjectURL(uploadPreviewUrl);
    };
  }, [uploadPreviewUrl]);

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <Header onBack={onBack} title="Tell us about your pet" />

      <div className="w-full">
        <div className="flex-1 px-6 py-6 pb-32 overflow-y-auto no-scrollbar md:px-12 lg:px-20 md:py-12">
          <div className="md:max-w-none">
            <div className="md:flex md:items-center md:justify-between md:gap-6">
              <ProgressBar current={1} total={3} />
              <div className="hidden md:block text-sm text-stone-500">
                Fill all details to match the best vet faster.
              </div>
            </div>

            <div className="mt-6 md:grid md:grid-cols-12 md:gap-10 lg:gap-14">
              {/* LEFT */}
              <div className="md:col-span-7 lg:col-span-7">
                <div className="space-y-8">
                  {/* Owner details */}
                  <section className="bg-white p-5 rounded-2xl shadow-sm space-y-4 md:p-9 md:rounded-3xl md:border md:border-stone-100 md:shadow-[0_8px_30px_rgba(0,0,0,0.05)]">
                    <div className="md:flex md:items-center md:justify-between">
                      <div className="text-base font-bold text-stone-900 md:text-xl">
                        Owner details
                      </div>
                      <div className="hidden md:block text-sm text-stone-500">
                        Used only for appointment updates
                      </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6">
                      <div>
                        <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                          Pet Owner Name <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          value={details.ownerName}
                          onChange={(e) =>
                            setDetails((p) => ({ ...p, ownerName: e.target.value }))
                          }
                          placeholder="e.g. Rahul Sharma"
                          className={fieldBase}
                        />
                      </div>

                      <div>
                        <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                          Pet Owner Mobile <span className="text-red-500">*</span>
                        </label>
                        <div className="flex items-center rounded-xl border border-stone-200 bg-white px-3 shadow-sm transition-all focus-within:ring-2 focus-within:ring-[#3998de]/30 focus-within:border-[#3998de] md:px-4 md:rounded-2xl">
                          <span className="text-stone-600 font-semibold border-r border-stone-200 pr-3 mr-3 md:text-base">
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
                            placeholder="9876543210"
                            className="flex-1 py-3 bg-transparent outline-none font-medium text-stone-900 md:py-4 md:text-base"
                          />
                        </div>
                        <p className="mt-1 text-xs text-stone-400 md:text-sm md:text-stone-500">
                          No spam. Updates only.
                        </p>
                      </div>
                    </div>
                  </section>

                  {/* Pet details */}
                  <section className="bg-white p-5 rounded-2xl shadow-sm space-y-4 md:p-9 md:rounded-3xl md:border md:border-stone-100 md:shadow-[0_8px_30px_rgba(0,0,0,0.05)]">
                    <div className="text-base font-bold text-stone-900 md:text-xl">
                      Pet details
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                        Pet&apos;s Name <span className="text-red-500">*</span>
                      </label>
                        <input
                          type="text"
                          value={details.name}
                          onChange={(e) =>
                            setDetails((p) => ({ ...p, name: e.target.value }))
                          }
                          placeholder="e.g. Buster"
                          className={fieldBase}
                        />
                      </div>

                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
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
                              "p-3 rounded-xl border flex flex-col items-center gap-2 transition-all",
                              "md:p-5 md:flex-row md:justify-center md:gap-3 md:rounded-2xl",
                              details.type === type
                                ? "bg-gradient-to-r from-[#3998de] to-[#3998de] hover:from-[#3998de] hover:to-[#3998de] text-white ring-1 ring-[#3998de]"
                                : "border-stone-200 text-stone-600 hover:bg-stone-50 md:bg-white",
                            ].join(" ")}
                          >
                            <div
                              className={
                                details.type === type ? "text-white" : "text-stone-400"
                              }
                            >
                              {SPECIALTY_ICONS[type] || <Star />}
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
                      <div>
                        <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
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
                            className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400"
                          />
                        </div>

                        {breedError ? (
                          <p className="mt-2 text-xs text-amber-600 md:text-sm">
                            {breedError}
                          </p>
                        ) : null}
                      </div>
                    )}

                    {/* Exotic detail mandatory */}
                    {isExotic && (
                      <div>
                        <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                          Which exotic pet? <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          value={details.exoticType}
                          onChange={(e) =>
                            setDetails((p) => ({ ...p, exoticType: e.target.value }))
                          }
                          placeholder="e.g. Parrot, Rabbit, Turtle, Guinea pig"
                          className={fieldBase}
                        />
                        <p className="mt-1 text-xs text-stone-400 md:text-sm md:text-stone-500">
                          This helps us match the right vet speciality.
                        </p>
                      </div>
                    )}

                    {/* DOB */}
                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                        Pet DOB <span className="text-red-500">*</span>
                      </label>

                      <div className="grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-6">
                        <input
                          type="date"
                          value={details.petDob}
                          max={todayISO()}
                          onChange={(e) =>
                            setDetails((p) => ({ ...p, petDob: e.target.value }))
                          }
                          className={fieldBase}
                        />

                        <div className="hidden md:flex items-center justify-between rounded-2xl border border-stone-200 bg-white px-4 py-3">
                          <div>
                            <div className="text-sm font-semibold text-stone-800">
                              Approx age
                            </div>
                            <div className="text-sm text-stone-500">
                              Auto-derived from DOB
                            </div>
                          </div>
                          <div className="text-lg font-bold text-stone-900">
                            {calcAgeFromDob(details.petDob) || "—"}
                          </div>
                        </div>
                      </div>

                      <p className="mt-2 text-xs text-stone-400 md:text-sm md:text-stone-500">
                        DOB helps the vet understand age-specific risks.
                      </p>
                    </div>
                  </section>

                  {/* Describe problem */}
                  <section className="bg-white p-5 rounded-2xl shadow-sm space-y-4 md:p-9 md:rounded-3xl md:border md:border-stone-100 md:shadow-[0_8px_30px_rgba(0,0,0,0.05)]">
                    <div className="text-base font-bold text-stone-900 md:text-xl">
                      Describe the problem
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                        What&apos;s happening? <span className="text-red-500">*</span>
                      </label>

                      <textarea
                        value={details.problemText}
                        onChange={(e) =>
                          setDetails((p) => ({ ...p, problemText: e.target.value }))
                        }
                        placeholder="Example: My dog is limping since yesterday, not putting weight on front leg, and crying when touched..."
                        rows={4}
                        className={textareaBase}
                      />

                      <div className="mt-2 flex items-center justify-between text-xs md:text-sm">
                        <span className="text-stone-400 md:text-stone-500">
                          Please add duration + severity (min 10 characters).
                        </span>
                        <span
                          className={
                            details.problemText.trim().length > 10
                              ? "text-emerald-600 font-semibold"
                              : "text-stone-400"
                          }
                        >
                          {details.problemText.trim().length}/10+
                        </span>
                      </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
                      <div>
                        <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                          Last few days: Energy <span className="text-red-500">*</span>
                        </label>
                        <div className="relative">
                          <select
                            value={details.lastDaysEnergy}
                            onChange={(e) =>
                              setDetails((p) => ({
                                ...p,
                                lastDaysEnergy: e.target.value,
                              }))
                            }
                            className={selectBase}
                          >
                            <option value="">Select</option>
                            {ENERGY_OPTIONS.map((o) => (
                              <option key={o.value} value={o.value}>
                                {o.label}
                              </option>
                            ))}
                          </select>
                          <ChevronDown className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                        </div>
                      </div>

                      <div>
                        <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                          Last few days: Appetite{" "}
                          <span className="text-red-500">*</span>
                        </label>
                        <div className="relative">
                          <select
                            value={details.lastDaysAppetite}
                            onChange={(e) =>
                              setDetails((p) => ({
                                ...p,
                                lastDaysAppetite: e.target.value,
                              }))
                            }
                            className={selectBase}
                          >
                            <option value="">Select</option>
                            {APPETITE_OPTIONS.map((o) => (
                              <option key={o.value} value={o.value}>
                                {o.label}
                              </option>
                            ))}
                          </select>
                          <ChevronDown className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                        </div>
                      </div>

                      <div>
                        <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                          Mood <span className="text-red-500">*</span>
                        </label>
                        <div className="relative">
                          <select
                            value={details.mood}
                            onChange={(e) =>
                              setDetails((p) => ({ ...p, mood: e.target.value }))
                            }
                            className={selectBase}
                          >
                            <option value="">Select</option>
                            {MOOD_OPTIONS.map((o) => (
                              <option key={o.value} value={o.value}>
                                {o.label}
                              </option>
                            ))}
                          </select>
                          <ChevronDown className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                        </div>
                      </div>
                    </div>
                  </section>

                  {/* Upload */}
                  <section className="bg-white p-5 rounded-2xl shadow-sm md:p-9 md:rounded-3xl md:border md:border-stone-100 md:shadow-[0_8px_30px_rgba(0,0,0,0.05)]">
                    <div className="text-base font-bold text-stone-900 md:text-xl mb-4">
                      Photo / Video <span className="text-red-500">*</span>
                    </div>

                    <label
                      className={[
                        "flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer transition-colors md:h-48 md:rounded-2xl",
                        isDragging
                          ? "border-[#3998de] bg-[#3998de]/10 ring-2 ring-[#3998de]/30"
                          : "border-[#3998de]/30 bg-[#3998de]/10 hover:bg-[#3998de]/15",
                      ].join(" ")}
                      onDragEnter={handleDragEnter}
                      onDragOver={handleDragOver}
                      onDragLeave={handleDragLeave}
                      onDrop={handleDrop}
                    >
                      <div className="flex flex-col items-center justify-center pt-5 pb-6">
                        {details.hasPhoto ? (
                          <CheckCircle2 className="w-8 h-8 text-emerald-500 mb-2 md:w-11 md:h-11" />
                        ) : (
                          <Upload className="w-8 h-8 text-[#3998de] mb-2 md:w-11 md:h-11" />
                        )}
                        <p className="mb-1 text-sm text-stone-700 font-semibold md:text-base">
                          {isDragging
                            ? "Drop to upload"
                            : details.hasPhoto
                            ? "File added"
                            : "Upload photo/video"}
                        </p>
                        <p className="text-xs text-stone-500 md:text-sm">
                          Drag & drop or click to upload (jpg, png, mp4, pdf)
                        </p>
                      </div>

                      <input
                        type="file"
                        className="hidden"
                        onChange={handlePhotoUpload}
                        accept="image/*,video/*,.pdf"
                      />
                    </label>

                    {/* Preview + meta */}
                    {uploadFile ? (
                      <div className="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div className="flex items-start gap-3 min-w-0">
                          <div className="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg bg-stone-100 text-stone-600">
                            {uploadIcon}
                          </div>
                          <div className="min-w-0">
                            <div className="text-sm font-semibold text-stone-800 truncate">
                              {uploadFile.name}
                            </div>
                            <div className="text-xs text-stone-500">
                              {uploadLabel} -{" "}
                              {(uploadFile.size / 1024 / 1024).toFixed(2)} MB
                              {uploadMeta?.compressedSize ? (
                                <>
                                  {" "}{" "}
                                  <span className="font-semibold text-emerald-700">
                                    {(uploadMeta.compressedSize / 1024 / 1024).toFixed(2)} MB
                                  </span>{" "}
                                  (compressed)
                                </>
                              ) : null}
                            </div>
                          </div>
                        </div>
                        <button
                          type="button"
                          onClick={() => {
                            setUploadFile(null);
                            setUploadPreviewUrl("");
                            setUploadMeta(null);
                            setDetails((p) => ({ ...p, hasPhoto: false }));
                          }}
                          className="text-xs font-semibold text-red-600 hover:underline self-start md:self-auto"
                        >
                          Remove
                        </button>
                      </div>
                    ) : null}

                    {uploadPreviewUrl ? (
                      <div className="mt-4">
                        <img
                          src={uploadPreviewUrl}
                          alt="Upload preview"
                          className="w-full max-h-56 object-contain rounded-xl border border-stone-100 bg-white"
                        />
                      </div>
                    ) : null}
                    <p className="hidden md:block mt-3 text-sm text-stone-500">
                      Tip: A short 5–10 sec video works great for movement issues.
                    </p>
                  </section>

                  {submitError ? (
                    <div className="bg-red-50 border border-red-100 text-red-700 rounded-2xl p-4 text-sm">
                      {submitError}
                    </div>
                  ) : null}
                </div>

                <div className="h-24 md:hidden" />
              </div>

              {/* RIGHT */}
              <div className="hidden md:block md:col-span-5 lg:col-span-5">
                <div className="sticky top-24 space-y-6">
                  <div className="bg-white rounded-3xl border border-stone-100 shadow-[0_10px_30px_rgba(0,0,0,0.06)] p-8">
                    <div className="text-lg font-bold text-stone-900 mb-1">
                      Quick summary
                    </div>
                    <p className="text-sm text-stone-500 mb-5">
                      All required fields must be filled to proceed.
                    </p>

                    <div className="space-y-3 text-base">
                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Owner</span>
                        <span className="font-semibold text-stone-900">
                          {details.ownerName || "—"}
                        </span>
                      </div>

                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Mobile</span>
                        <span className="font-semibold text-stone-900">
                          {details.ownerMobile ? `+91 ${details.ownerMobile}` : "—"}
                        </span>
                      </div>

                      <div className="pt-4 border-t border-stone-100" />

                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Pet</span>
                        <span className="font-semibold text-stone-900">
                          {details.name || "—"}
                        </span>
                      </div>

                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Type</span>
                        <span className="font-semibold text-stone-900 capitalize">
                          {details.type || "—"}
                        </span>
                      </div>

                      {showBreed && (
                        <div className="flex items-center justify-between">
                          <span className="text-stone-500">Breed</span>
                          <span className="font-semibold text-stone-900 capitalize">
                            {details.breed || "—"}
                          </span>
                        </div>
                      )}

                      {isExotic && (
                        <div className="flex items-center justify-between">
                          <span className="text-stone-500">Exotic</span>
                          <span className="font-semibold text-stone-900">
                            {details.exoticType || "—"}
                          </span>
                        </div>
                      )}

                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">DOB</span>
                        <span className="font-semibold text-stone-900">
                          {details.petDob || "—"}
                        </span>
                      </div>

                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Approx age</span>
                        <span className="font-semibold text-stone-900">
                          {approxAge || "—"}
                        </span>
                      </div>

                      <div className="pt-4 border-t border-stone-100">
                        <div className="text-stone-500 text-sm mb-2">Problem</div>
                        <div className="text-sm text-stone-800 leading-relaxed">
                          {details.problemText?.trim() ? (
                            <span className="line-clamp-4">{details.problemText}</span>
                          ) : (
                            <span className="text-stone-400">—</span>
                          )}
                        </div>
                      </div>

                      <div className="pt-4 border-t border-stone-100">
                        <div className="text-stone-500 text-sm mb-2">Last few days</div>
                        <div className="flex flex-wrap gap-2">
                          <span className="text-sm bg-stone-50 border border-stone-200 px-3 py-1.5 rounded-full text-stone-700">
                            Energy: {details.lastDaysEnergy || "—"}
                          </span>
                          <span className="text-sm bg-stone-50 border border-stone-200 px-3 py-1.5 rounded-full text-stone-700">
                            Appetite: {details.lastDaysAppetite || "—"}
                          </span>
                          <span className="text-sm bg-stone-50 border border-stone-200 px-3 py-1.5 rounded-full text-stone-700">
                            Mood: {details.mood || "N/A"}
                          </span>
                        </div>
                      </div>

                      <div className="pt-4 border-t border-stone-100 flex items-center justify-between">
                        <span className="text-stone-500">Photo/Video</span>
                        <span
                          className={[
                            "text-sm font-bold px-3 py-1.5 rounded-full border",
                            details.hasPhoto && uploadFile
                              ? "text-emerald-700 bg-emerald-50 border-emerald-100"
                              : "text-red-700 bg-red-50 border-red-100",
                          ].join(" ")}
                        >
                          {details.hasPhoto && uploadFile ? "Added" : "Required"}
                        </span>
                      </div>
                      {details.petDoc2 ? (
                        <div className="pt-4 border-t border-stone-100">
                          <div className="text-stone-500 text-sm mb-2">Report link</div>
                          <div className="text-sm text-stone-800 break-all">
                            {details.petDoc2}
                          </div>
                        </div>
                      ) : null}

                    </div>
                  </div>

                  <div className="bg-white rounded-3xl border border-stone-100 shadow-[0_10px_30px_rgba(0,0,0,0.06)] p-8">
                    <Button
                      onClick={submitObservation}
                      disabled={!isValid || submitting}
                      title={!isValid ? getSubmitTooltip() : undefined}
                      className={`w-full md:text-xl md:py-4 md:rounded-2xl ${
                        !isValid || submitting
                          ? "opacity-50 cursor-not-allowed bg-stone-300 shadow-none"
                          : "bg-gradient-to-r from-[#3998de] to-[#3998de] hover:from-[#3998de] hover:to-[#3998de]"
                      }`}
                    >
                      {submitting ? "Submitting..." : "See available vets"}
                    </Button>

                    {!isValid ? (
                      <p className="text-sm text-red-600 mt-3">{getSubmitTooltip()}</p>
                    ) : submitting ? (
                      <p className="text-sm text-stone-500 mt-3">
                        Compressing & uploading...
                      </p>
                    ) : (
                      <p className="text-sm text-stone-500 mt-3">
                        Takes less than a minute.
                      </p>
                    )}

                    {submitError ? (
                      <p className="text-sm text-red-600 mt-3">{submitError}</p>
                    ) : null}
                  </div>
                </div>
              </div>
            </div>

            <div className="hidden md:block h-28" />
          </div>
        </div>
      </div>

      {/* Mobile CTA */}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button
          onClick={submitObservation}
          fullWidth
          disabled={!isValid || submitting}
          className={
            !isValid || submitting
              ? "opacity-50 cursor-not-allowed bg-stone-300 shadow-none"
              : "bg-gradient-to-r from-[#3998de] to-[#3998de] hover:from-[#3998de] hover:to-[#3998de]"
          }
        >
          {submitting ? "Submitting..." : "See available vets"}
        </Button>
      </div>
    </div>
  );
};

export default PetDetailsScreen;
