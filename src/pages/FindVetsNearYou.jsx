import React, { useEffect, useMemo, useState } from "react";
import { Helmet } from "react-helmet-async";
import {
  AlertCircle,
  Building2,
  CalendarDays,
  CheckCircle2,
  ChevronRight,
  Clock3,
  Crosshair,
  Filter,
  IndianRupee,
  Languages,
  MapPin,
  RefreshCw,
  Search,
  ShieldCheck,
  Sparkles,
  Stethoscope,
  Video,
  X,
} from "lucide-react";
import { apiBaseUrl } from "../lib/api";
import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter";
import ModernDoctorBooking from "../newflow/ModernDoctorBooking";

const PAGE_TITLE = "Find Vets Near You | SnoutIQ Verified Doctors";
const PAGE_DESCRIPTION =
  "Find verified veterinary doctors and clinics near your location. Filter by distance, specialization, and book video consults or clinic visits on SnoutIQ.";

const SESSION_COORDS_KEY = "snoutiq_vets_coords";

const FILTER_TAGS = [
  { id: "all", label: "All Vets" },
  { id: "dogs_cats", label: "Dogs & Cats" },
  { id: "exotic", label: "Exotic Pets" },
  { id: "surgery", label: "Surgery" },
  { id: "dermatology", label: "Dermatology" },
  { id: "vaccination", label: "Vaccination" },
];

const RADIUS_OPTIONS = [
  { id: "all", label: "Any Distance" },
  { id: "5", label: "< 5 km", max: 5 },
  { id: "10", label: "< 10 km", max: 10 },
  { id: "25", label: "< 25 km", max: 25 },
];

const getBackendBase = () => apiBaseUrl().replace(/\/+$/, "").replace(/\/api$/i, "");

const buildDoctorImageUrl = (doc) => {
  const path =
    doc?.doctor_image_blob_url ||
    doc?.doctor_image_url ||
    doc?.doctor_image ||
    doc?.image ||
    "";

  if (!path || typeof path !== "string") return "";
  const trimmed = path.trim();
  if (!trimmed) return "";
  if (/^(https?:|data:)/i.test(trimmed)) return trimmed;

  let cleaned = trimmed.replace(/^\/+/, "");
  if (cleaned.toLowerCase().startsWith("backend/")) {
    cleaned = cleaned.slice("backend/".length);
  }

  const base = getBackendBase();
  return `${base}/${cleaned}`;
};

const calculateDistanceKm = (lat1, lon1, lat2, lon2) => {
  if (!lat1 || !lon1 || !lat2 || !lon2) return null;
  const numLat1 = Number(lat1);
  const numLon1 = Number(lon1);
  const numLat2 = Number(lat2);
  const numLon2 = Number(lon2);
  if (isNaN(numLat1) || isNaN(numLon1) || isNaN(numLat2) || isNaN(numLon2)) return null;

  const R = 6371; // km
  const dLat = (numLat2 - numLat1) * (Math.PI / 180);
  const dLon = (numLon2 - numLon1) * (Math.PI / 180);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(numLat1 * (Math.PI / 180)) *
      Math.cos(numLat2 * (Math.PI / 180)) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
};

const extractCoords = (item) => {
  if (!item) return null;
  if (item.lat && item.lng && !isNaN(Number(item.lat)) && !isNaN(Number(item.lng))) {
    return { lat: Number(item.lat), lng: Number(item.lng) };
  }
  if (Array.isArray(item.coordinates) && item.coordinates.length >= 2) {
    const lat = Number(item.coordinates[0]);
    const lng = Number(item.coordinates[1]);
    if (!isNaN(lat) && !isNaN(lng)) return { lat, lng };
  }
  return null;
};

const formatDistance = (dist) => {
  if (typeof dist !== "number" || !Number.isFinite(dist)) return "";
  if (dist < 1) return `${Math.round(dist * 1000)} m away`;
  return `${dist.toFixed(dist >= 10 ? 0 : 1)} km away`;
};

const parseSpecializations = (val) => {
  if (!val) return [];
  if (Array.isArray(val)) return val.map((s) => String(s).trim()).filter(Boolean);
  const text = String(val).trim();
  if (!text) return [];

  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) {
        return parsed.map((s) => String(s).trim()).filter(Boolean);
      }
    } catch {
      // fallback
    }
  }

  return text
    .replace(/^\[|\]$/g, "")
    .replace(/["']/g, "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean);
};

const parseAvailability = (val) => {
  if (val === undefined || val === null) return true;
  if (typeof val === "boolean") return val;
  if (typeof val === "number") return val === 1;
  const str = String(val).trim().toLowerCase();
  if (["1", "true", "active", "available", "open", "yes"].includes(str)) return true;
  if (["0", "false", "inactive", "unavailable", "closed", "no"].includes(str)) return false;
  return true;
};

const getInitials = (name) => {
  const clean = String(name || "").trim();
  if (!clean) return "DR";
  const parts = clean.split(/\s+/).filter(Boolean);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return `${parts[0][0] || ""}${parts[1][0] || ""}`.toUpperCase();
};

const formatInr = (val) => {
  const num = Number(val);
  if (!Number.isFinite(num) || num <= 0) return null;
  return `₹${num.toLocaleString("en-IN")}`;
};

/* ---------------- Doctor Card (Aligned with NewClinics styling) ---------------- */
const DoctorCard = ({ doctor, onSelect, onBook, onImageError, isImageBroken }) => {
  const [imgSrc, setImgSrc] = useState(doctor.doctorImage);
  const [triedStorageFallback, setTriedStorageFallback] = useState(false);

  const handleImageError = () => {
    // If first attempt failed and didn't include /storage/, try storage fallback once
    if (!triedStorageFallback && doctor.rawImagePath && !doctor.rawImagePath.startsWith("http")) {
      setTriedStorageFallback(true);
      const cleaned = doctor.rawImagePath.replace(/^\/+/, "").replace(/^backend\//i, "");
      setImgSrc(`${getBackendBase()}/storage/${cleaned}`);
      return;
    }
    onImageError(doctor.id);
  };

  const hasValidImage = Boolean(imgSrc) && !isImageBroken;
  const dayRate = formatInr(doctor.videoDayRate);

  return (
    <article className="group flex flex-col overflow-hidden rounded-[24px] border border-slate-200/90 bg-white shadow-[0_4px_20px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 hover:border-blue-300/80 hover:shadow-[0_16px_36px_rgba(30,58,138,0.12)]">
      {/* Card Header Banner */}
      <div className="relative flex h-28 items-end bg-gradient-to-br from-slate-900 via-sky-950 to-blue-900 p-3.5 text-white">
        <div className="flex items-center gap-3 min-w-0">
          {hasValidImage ? (
            <img
              src={imgSrc}
              alt={doctor.doctorName}
              className="h-12 w-12 shrink-0 rounded-2xl border-2 border-white/80 object-cover shadow-md"
              loading="lazy"
              onError={handleImageError}
            />
          ) : (
            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 border-white/60 bg-white/20 text-xs font-black text-white shadow-md backdrop-blur">
              {getInitials(doctor.doctorName)}
            </div>
          )}

          <div className="min-w-0 pr-16">
            <h3 className="truncate font-display text-sm font-extrabold text-white">
              {doctor.doctorName}
            </h3>
            <p className="truncate text-xs text-sky-200 font-medium">
              {[doctor.degree, doctor.experience ? `${doctor.experience} yrs exp` : null]
                .filter(Boolean)
                .join(" • ") || "Veterinary Doctor"}
            </p>
          </div>
        </div>

        {/* Top Badges */}
        <div className="absolute right-3 top-3 flex items-center gap-1.5">
          {doctor.isAvailable ? (
            <span className="flex items-center gap-1 rounded-full bg-emerald-500/90 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm backdrop-blur">
              <span className="h-1.5 w-1.5 rounded-full bg-white animate-pulse" />
              Available
            </span>
          ) : (
            <span className="rounded-full bg-slate-600/90 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm backdrop-blur">
              Offline
            </span>
          )}
        </div>
      </div>

      {/* Card Body */}
      <div className="flex flex-1 flex-col justify-between p-3.5 space-y-3">
        <div className="space-y-2.5">
          {/* Clinic & Distance */}
          <div className="flex items-start justify-between gap-1.5 text-xs">
            <div className="min-w-0">
              <p className="truncate font-semibold text-slate-800 flex items-center gap-1">
                <Building2 className="h-3.5 w-3.5 shrink-0 text-blue-600" />
                <span className="truncate">{doctor.clinicName}</span>
              </p>
              <p className="truncate text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                <MapPin className="h-3 w-3 shrink-0 text-slate-400" />
                <span className="truncate">{doctor.clinicCity || doctor.clinicAddress || "Nearby"}</span>
              </p>
            </div>
            {doctor.distance !== null && (
              <span className="shrink-0 rounded-full bg-blue-50 border border-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                {formatDistance(doctor.distance)}
              </span>
            )}
          </div>

          {/* Fee & Consult Availability Pill */}
          <div className="flex items-center justify-between rounded-xl bg-slate-50 px-2.5 py-1.5 border border-slate-100 text-xs">
            <span className="text-[11px] font-medium text-slate-600 flex items-center gap-1">
              <Stethoscope className="h-3.5 w-3.5 text-blue-600" />
              Consult Fee
            </span>
            <span className="font-extrabold text-blue-700 text-xs">
              {dayRate ? `${dayRate}` : "₹499"}
            </span>
          </div>

          {/* Specialization Chips (max 2) */}
          {doctor.specializations.length > 0 && (
            <div className="flex flex-wrap gap-1">
              {doctor.specializations.slice(0, 2).map((spec, i) => (
                <span
                  key={`${doctor.id}-spec-${i}`}
                  className="rounded-md border border-slate-100 bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-600"
                >
                  {spec}
                </span>
              ))}
              {doctor.specializations.length > 2 && (
                <span className="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">
                  +{doctor.specializations.length - 2}
                </span>
              )}
            </div>
          )}
        </div>

        {/* Action Buttons */}
        <div className="space-y-2 pt-1 border-t border-slate-100">
          <div className="grid grid-cols-2 gap-2">
            {/* Talk to Vet Button (Video Consult) */}
            <button
              type="button"
              onClick={() => onBook(doctor, "video_consult")}
              className="inline-flex items-center justify-center gap-1 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 py-2 px-2 text-xs font-bold text-white shadow-xs transition hover:from-emerald-700 hover:to-teal-700 active:scale-95 cursor-pointer"
            >
              <Video className="h-3.5 w-3.5 shrink-0" />
              <span className="truncate">Talk to Vet</span>
            </button>

            {/* Book Visit Button (In-clinic) */}
            <button
              type="button"
              onClick={() => onBook(doctor, "appointment")}
              className="inline-flex items-center justify-center gap-1 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 py-2 px-2 text-xs font-bold text-white shadow-xs transition hover:from-blue-700 hover:to-blue-800 active:scale-95 cursor-pointer"
            >
              <CalendarDays className="h-3.5 w-3.5 shrink-0" />
              <span className="truncate">Book Visit</span>
            </button>
          </div>

          {/* View Profile Link */}
          <button
            type="button"
            onClick={() => onSelect(doctor)}
            className="flex w-full items-center justify-center gap-1 rounded-lg py-1 text-[11px] font-semibold text-slate-500 transition hover:text-blue-700 hover:bg-slate-50 cursor-pointer"
          >
            <span>View Full Profile</span>
            <ChevronRight className="h-3 w-3" />
          </button>
        </div>
      </div>
    </article>
  );
};

/* ---------------- Profile Modal ---------------- */
const ProfileModal = ({ doctor, onClose, onBook, isImageBroken, onImageError }) => {
  useEffect(() => {
    if (!doctor) return;
    const handleKey = (e) => {
      if (e.key === "Escape") onClose();
    };
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", handleKey);
    return () => {
      document.body.style.overflow = prev;
      window.removeEventListener("keydown", handleKey);
    };
  }, [doctor, onClose]);

  if (!doctor) return null;

  const hasImage = Boolean(doctor.doctorImage) && !isImageBroken;
  const dayRate = formatInr(doctor.videoDayRate);
  const nightRate = formatInr(doctor.videoNightRate);

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm animate-in fade-in duration-150"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="relative max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-[28px] bg-white shadow-2xl flex flex-col"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-doc-title"
      >
        {/* Modal Header */}
        <div className="relative bg-gradient-to-br from-slate-900 via-sky-950 to-blue-900 p-6 text-white shrink-0">
          <button
            type="button"
            onClick={onClose}
            className="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/25 cursor-pointer"
            aria-label="Close"
          >
            <X className="h-5 w-5" />
          </button>

          <div className="flex items-center gap-4">
            {hasImage ? (
              <img
                src={doctor.doctorImage}
                alt={doctor.doctorName}
                className="h-16 w-16 rounded-2xl border-2 border-white/80 object-cover shadow-lg"
                onError={() => onImageError(doctor.id)}
              />
            ) : (
              <div className="flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-white/60 bg-white/20 text-xl font-extrabold text-white shadow backdrop-blur">
                {getInitials(doctor.doctorName)}
              </div>
            )}

            <div className="min-w-0 pr-8">
              <div className="flex items-center gap-2">
                <h2 id="modal-doc-title" className="truncate font-display text-xl font-extrabold">
                  {doctor.doctorName}
                </h2>
                {doctor.doctorLicense && (
                  <span className="inline-flex items-center gap-0.5 rounded-full bg-emerald-500/80 px-2 py-0.5 text-[10px] font-bold text-white">
                    <CheckCircle2 className="h-3 w-3" />
                    Verified
                  </span>
                )}
              </div>
              <p className="text-xs text-white/80 mt-0.5">
                {[doctor.degree, doctor.experience ? `${doctor.experience} yrs exp` : null]
                  .filter(Boolean)
                  .join(" • ") || "Veterinary Doctor"}
              </p>
              <p className="text-xs text-white/70 truncate mt-0.5">
                {doctor.clinicName}
                {doctor.clinicCity ? ` (${doctor.clinicCity})` : ""}
              </p>
            </div>
          </div>
        </div>

        {/* Modal Scrollable Body */}
        <div className="overflow-y-auto p-6 space-y-5 text-slate-700">
          {/* License & Rates Overview */}
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {doctor.doctorLicense && (
              <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 text-center">
                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">License No.</span>
                <p className="text-xs font-bold text-slate-800 mt-1 flex items-center justify-center gap-1">
                  <ShieldCheck className="h-3.5 w-3.5 text-emerald-600" />
                  {doctor.doctorLicense}
                </p>
              </div>
            )}
            {dayRate && (
              <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 text-center">
                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Day Consult</span>
                <p className="text-xs font-extrabold text-blue-700 mt-1 flex items-center justify-center gap-0.5">
                  <IndianRupee className="h-3 w-3" />
                  {dayRate.replace("₹", "")}
                </p>
              </div>
            )}
            {nightRate && (
              <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 text-center">
                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Night Consult</span>
                <p className="text-xs font-extrabold text-slate-800 mt-1 flex items-center justify-center gap-0.5">
                  <IndianRupee className="h-3 w-3" />
                  {nightRate.replace("₹", "")}
                </p>
              </div>
            )}
          </div>

          {/* Specializations */}
          {doctor.specializations.length > 0 && (
            <div>
              <h4 className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                <Stethoscope className="h-3.5 w-3.5 text-blue-600" />
                Specializations
              </h4>
              <div className="mt-2 flex flex-wrap gap-1.5">
                {doctor.specializations.map((spec, i) => (
                  <span
                    key={`modal-spec-${i}`}
                    className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800 border border-blue-100"
                  >
                    {spec}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Bio */}
          {doctor.bio && (
            <div>
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                About the Doctor
              </h4>
              <p className="mt-1.5 text-xs leading-relaxed text-slate-600 whitespace-pre-line">
                {doctor.bio}
              </p>
            </div>
          )}

          {/* Additional details */}
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-2.5 text-xs">
            {doctor.clinicAddress && (
              <div className="flex items-start gap-2">
                <MapPin className="h-4 w-4 shrink-0 text-slate-400 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-700">Clinic Address: </span>
                  <span className="text-slate-600">{doctor.clinicAddress}</span>
                </div>
              </div>
            )}
            {doctor.languagesSpoken && (
              <div className="flex items-start gap-2">
                <Languages className="h-4 w-4 shrink-0 text-slate-400 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-700">Languages Spoken: </span>
                  <span className="text-slate-600">{doctor.languagesSpoken}</span>
                </div>
              </div>
            )}
            {doctor.responseTimeDay && (
              <div className="flex items-start gap-2">
                <Clock3 className="h-4 w-4 shrink-0 text-slate-400 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-700">Online Response Time: </span>
                  <span className="text-slate-600">{doctor.responseTimeDay}</span>
                </div>
              </div>
            )}
            {doctor.freeFollowUp && (
              <div className="flex items-start gap-2">
                <Sparkles className="h-4 w-4 shrink-0 text-amber-500 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-700">Free Follow-up Policy: </span>
                  <span className="text-slate-600">{doctor.freeFollowUp}</span>
                </div>
              </div>
            )}
            {doctor.breakTime && (
              <div className="flex items-start gap-2">
                <Clock3 className="h-4 w-4 shrink-0 text-slate-400 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-700">Break / DND Hours: </span>
                  <span className="text-slate-600">{doctor.breakTime}</span>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Modal Footer with CTAs */}
        <div className="border-t border-slate-100 bg-slate-50 p-4 shrink-0 space-y-2">
          <div className="grid grid-cols-2 gap-2.5">
            <button
              type="button"
              onClick={() => {
                onClose();
                onBook(doctor, "video_consult");
              }}
              className="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 py-2.5 text-xs font-bold text-white shadow-sm transition hover:from-emerald-700 hover:to-teal-700 active:scale-95 cursor-pointer"
            >
              <Video className="h-4 w-4" />
              <span>Talk to Vet</span>
            </button>
            <button
              type="button"
              onClick={() => {
                onClose();
                onBook(doctor, "appointment");
              }}
              className="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 py-2.5 text-xs font-bold text-white shadow-sm transition hover:from-blue-700 hover:to-blue-800 active:scale-95 cursor-pointer"
            >
              <CalendarDays className="h-4 w-4" />
              <span>Book Visit</span>
            </button>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="w-full rounded-xl border border-slate-300 bg-white py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 active:scale-95 cursor-pointer"
          >
            Close Profile
          </button>
        </div>
      </div>
    </div>
  );
};

/* ---------------- Main Page Component ---------------- */
export default function FindVetsNearYou() {
  const [coords, setCoords] = useState(() => {
    if (typeof window !== "undefined") {
      try {
        const saved = sessionStorage.getItem(SESSION_COORDS_KEY);
        if (saved) {
          const parsed = JSON.parse(saved);
          if (parsed?.lat && parsed?.lng) return parsed;
        }
      } catch {}
    }
    return null;
  });

  const [locationStatus, setLocationStatus] = useState(() => (coords ? "granted" : "requesting"));
  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedTag, setSelectedTag] = useState("all");
  const [selectedRadius, setSelectedRadius] = useState("all");
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [brokenImages, setBrokenImages] = useState(() => new Set());

  // Booking Modal State
  const [bookingModal, setBookingModal] = useState({
    isOpen: false,
    orderType: "video_consult",
    doctor: null,
    clinic: null,
  });

  const markImageBroken = (id) => {
    setBrokenImages((prev) => new Set(prev).add(id));
  };

  const handleBookAction = (doctor, orderType) => {
    const formattedDoctor = {
      id: doctor.id,
      doctor_id: doctor.id,
      name: doctor.doctorName,
      doctor_name: doctor.doctorName,
      degree: doctor.degree || "BVSc",
      years_of_experience: doctor.experience || 5,
      experience: doctor.experience || 5,
      specialization_select_all_that_apply: doctor.specializations,
      specialization: doctor.specializations.join(", "),
      video_day_rate: doctor.videoDayRate || 499,
      video_night_rate: doctor.videoNightRate || 650,
      doctors_price: doctor.videoDayRate || 499,
      feeDay: Number(doctor.videoDayRate) || 499,
      feeNight: Number(doctor.videoNightRate) || 650,
      doctor_image: doctor.doctorImage,
      doctor_image_blob_url: doctor.doctorImage,
      doctor_blob_url: doctor.doctorImage,
      image: doctor.doctorImage,
      doctor_status: doctor.isAvailable ? "available" : "offline",
      status: doctor.isAvailable ? "available" : "offline",
      bio: doctor.bio,
      vet_registeration_id: doctor.clinicId,
      clinicId: doctor.clinicId,
      clinic_id: doctor.clinicId,
      clinicName: doctor.clinicName,
      clinic_name: doctor.clinicName,
      clinicCity: doctor.clinicCity,
      clinic_city: doctor.clinicCity,
      clinicAddress: doctor.clinicAddress,
      clinic_address: doctor.clinicAddress,
      distance_km: doctor.distance,
    };

    const formattedClinic = {
      id: doctor.clinicId || `clinic-${doctor.id}`,
      clinic_id: doctor.clinicId || `clinic-${doctor.id}`,
      name: doctor.clinicName || "SnoutIQ Partner Clinic",
      city: doctor.clinicCity || "Gurugram",
      address: doctor.clinicAddress || "",
      formatted_address: doctor.clinicAddress || "",
      doctors: [formattedDoctor],
      clinic_day_fee: doctor.videoDayRate || 499,
      clinic_night_fee: doctor.videoNightRate || 650,
      doctors_price: doctor.videoDayRate || 499,
    };

    setBookingModal({
      isOpen: true,
      orderType,
      doctor: formattedDoctor,
      clinic: formattedClinic,
    });
  };

  const closeBookingModal = () => {
    setBookingModal({
      isOpen: false,
      orderType: "video_consult",
      doctor: null,
      clinic: null,
    });
  };

  // Immediate location request
  const requestLocation = (force = false) => {
    if (typeof window === "undefined" || !navigator?.geolocation) {
      setLocationStatus("unsupported");
      return;
    }

    setLocationStatus("requesting");
    setError("");

    const onSuccess = (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      const newCoords = { lat, lng };
      setCoords(newCoords);
      try {
        sessionStorage.setItem(SESSION_COORDS_KEY, JSON.stringify(newCoords));
      } catch {}
      setLocationStatus("granted");
    };

    const onError = (err) => {
      // Retry once without high accuracy if timeout
      if (err?.code === 3) {
        navigator.geolocation.getCurrentPosition(
          onSuccess,
          () => setLocationStatus("denied"),
          { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
        );
        return;
      }
      setLocationStatus("denied");
    };

    navigator.geolocation.getCurrentPosition(onSuccess, onError, {
      enableHighAccuracy: true,
      timeout: 8000,
      maximumAge: force ? 0 : 60000,
    });
  };

  // Trigger on mount if no cached coords
  useEffect(() => {
    if (!coords) {
      requestLocation();
    }
  }, []);

  // Fetch doctors using 10-may inclinic registrations & excel doctors APIs
  useEffect(() => {
    let isMounted = true;
    setLoading(true);
    setError("");

    const locQuery = coords?.lat && coords?.lng ? `?lat=${coords.lat}&lng=${coords.lng}` : "";
    const base = getBackendBase();

    Promise.all([
      fetch(`${base}/api/inclinic-lists-new-after-10th-may-registerations${locQuery}`, {
        headers: { Accept: "application/json" },
      })
        .then((res) => (res.ok ? res.json() : null))
        .catch(() => null),
      fetch(`${base}/api/exported_from_excell_doctors${locQuery}`, {
        headers: { Accept: "application/json" },
      })
        .then((res) => (res.ok ? res.json() : null))
        .catch(() => null),
    ])
      .then(([clinicRes, docRes]) => {
        if (!isMounted) return;

        const rawClinics = Array.isArray(clinicRes?.data?.data)
          ? clinicRes.data.data
          : Array.isArray(clinicRes?.data)
          ? clinicRes.data
          : Array.isArray(clinicRes)
          ? clinicRes
          : [];

        const clinicMap = new Map();
        rawClinics.forEach((c) => {
          if (c?.id) clinicMap.set(String(c.id), c);
        });

        const rawDocs = Array.isArray(docRes?.doctors)
          ? docRes.doctors
          : Array.isArray(docRes?.data?.doctors)
          ? docRes.data.doctors
          : Array.isArray(docRes?.data)
          ? docRes.data
          : Array.isArray(docRes)
          ? docRes
          : [];

        const docMap = new Map();

        // 1. Add doctors registered with clinics from 10-may list
        rawClinics.forEach((clinic) => {
          const cCoords = extractCoords(clinic);
          const cDist =
            coords && cCoords
              ? calculateDistanceKm(coords.lat, coords.lng, cCoords.lat, cCoords.lng)
              : clinic.distance_km != null
              ? Number(clinic.distance_km)
              : null;

          (clinic.doctors || []).forEach((doc) => {
            if (!doc) return;
            const docId = String(doc.id || doc.doctor_id || "");
            if (!docId) return;

            const rawDist = doc.distance_km ?? doc.distance ?? cDist;
            const finalDist =
              rawDist != null && !isNaN(Number(rawDist))
                ? Number(rawDist)
                : coords && cCoords
                ? calculateDistanceKm(coords.lat, coords.lng, cCoords.lat, cCoords.lng)
                : null;

            docMap.set(docId, {
              id: doc.id || doc.doctor_id,
              clinicId: clinic.id,
              clinicName: clinic.name || "SnoutIQ Partner Clinic",
              clinicCity: clinic.city || "",
              clinicAddress: clinic.address || clinic.formatted_address || "",
              clinicCoords: cCoords,
              distance: finalDist,
              doctorName: doc.doctor_name || doc.name || "Veterinary Doctor",
              rawImagePath:
                doc.doctor_image_blob_url ||
                doc.doctor_image_url ||
                doc.doctor_image ||
                doc.doctor_blob_url ||
                doc.image ||
                "",
              doctorImage: buildDoctorImageUrl(doc),
              degree: doc.degree || "BVSc",
              experience: doc.years_of_experience || doc.experience || "",
              specializations: parseSpecializations(
                doc.specialization_select_all_that_apply || doc.specialization
              ),
              isAvailable: parseAvailability(
                doc.toggle_availability ?? doc.doctor_status ?? doc.status ?? 1
              ),
              videoDayRate: doc.video_day_rate || doc.doctors_price || clinic.clinic_day_fee || "499",
              videoNightRate: doc.video_night_rate || clinic.clinic_night_fee || "650",
              doctorLicense: doc.doctor_license || doc.license || "",
              bio: doc.bio || "",
              languagesSpoken: doc.languages_spoken || "",
              responseTimeDay: doc.response_time_for_online_consults_day || "0 To 15 Mins",
              breakTime: doc.break_do_not_disturb_time_example_2_4_pm || "",
              freeFollowUp:
                doc.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta || "",
            });
          });
        });

        // 2. Add remaining doctors from exported_from_excell_doctors
        rawDocs.forEach((doc) => {
          if (!doc) return;
          const docId = String(doc.id || doc.doctor_id || "");
          if (!docId || docMap.has(docId)) return;

          const regId = String(doc.vet_registeration_id || doc.clinic_id || doc.clinicId || "");
          const clinic = clinicMap.get(regId) || null;
          const cCoords = extractCoords(clinic);

          const rawDist = doc.distance_km ?? doc.distance ?? clinic?.distance_km;
          const finalDist =
            rawDist != null && !isNaN(Number(rawDist))
              ? Number(rawDist)
              : coords && cCoords
              ? calculateDistanceKm(coords.lat, coords.lng, cCoords.lat, cCoords.lng)
              : null;

          docMap.set(docId, {
            id: doc.id || doc.doctor_id,
            clinicId: clinic?.id || (regId ? Number(regId) : null),
            clinicName: clinic?.name || doc.clinic_name || "SnoutIQ Partner Clinic",
            clinicCity: clinic?.city || doc.clinic_city || doc.city || "",
            clinicAddress: clinic?.address || clinic?.formatted_address || doc.clinic_address || "",
            clinicCoords: cCoords,
            distance: finalDist,
            doctorName: doc.doctor_name || doc.name || "Veterinary Doctor",
            rawImagePath:
              doc.doctor_image_blob_url ||
              doc.doctor_image_url ||
              doc.doctor_image ||
              doc.doctor_blob_url ||
              doc.image ||
              "",
            doctorImage: buildDoctorImageUrl(doc),
            degree: doc.degree || "BVSc",
            experience: doc.years_of_experience || doc.experience || "",
            specializations: parseSpecializations(
              doc.specialization_select_all_that_apply || doc.specialization
            ),
            isAvailable: parseAvailability(
              doc.toggle_availability ?? doc.doctor_status ?? doc.status ?? 1
            ),
            videoDayRate: doc.video_day_rate || doc.doctors_price || clinic?.clinic_day_fee || "499",
            videoNightRate: doc.video_night_rate || clinic?.clinic_night_fee || "650",
            doctorLicense: doc.doctor_license || doc.license || "",
            bio: doc.bio || "",
            languagesSpoken: doc.languages_spoken || "",
            responseTimeDay: doc.response_time_for_online_consults_day || "0 To 15 Mins",
            breakTime: doc.break_do_not_disturb_time_example_2_4_pm || "",
            freeFollowUp:
              doc.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta || "",
          });
        });

        const normalizedList = Array.from(docMap.values());
        setDoctors(normalizedList);
      })
      .catch((err) => {
        if (!isMounted) return;
        console.error("Error fetching 10-may vets list:", err);
        setError("Unable to load doctors. Please check your connection or retry.");
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [coords]);

  // Client-side filtering & distance sorting (nearest first)
  const filteredDoctors = useMemo(() => {
    let result = [...doctors];

    // 1. Search filter
    const q = searchTerm.trim().toLowerCase();
    if (q) {
      result = result.filter(
        (doc) =>
          doc.doctorName.toLowerCase().includes(q) ||
          doc.clinicName.toLowerCase().includes(q) ||
          doc.clinicCity.toLowerCase().includes(q) ||
          doc.specializations.some((s) => s.toLowerCase().includes(q))
      );
    }

    // 2. Specialization tag filter
    if (selectedTag !== "all") {
      result = result.filter((doc) => {
        const specStr = doc.specializations.join(" ").toLowerCase();
        if (selectedTag === "dogs_cats") return /dog|canine|cat|feline/i.test(specStr);
        if (selectedTag === "exotic") return /exotic|avian|bird|rabbit|turtle|guinea|hamster/i.test(specStr);
        if (selectedTag === "surgery") return /surg/i.test(specStr);
        if (selectedTag === "dermatology") return /derma|skin/i.test(specStr);
        if (selectedTag === "vaccination") return /vaccin|prevent/i.test(specStr);
        return true;
      });
    }

    // 3. Radius filter
    if (selectedRadius !== "all") {
      const maxDist = Number(selectedRadius);
      result = result.filter((doc) => doc.distance !== null && doc.distance <= maxDist);
    }

    // 4. Sort ascending by distance (nearest first)
    result.sort((a, b) => {
      if (a.distance === null) return 1;
      if (b.distance === null) return -1;
      return a.distance - b.distance;
    });

    return result;
  }, [doctors, searchTerm, selectedTag, selectedRadius]);

  // Structured schema for SEO
  const jsonLdSchema = useMemo(() => {
    return {
      "@context": "https://schema.org",
      "@type": "MedicalOrganization",
      name: "SnoutIQ Nearby Veterinary Network",
      url: "https://snoutiq.com/find-vets-near-you",
      description: PAGE_DESCRIPTION,
      medicalSpecialty: "VeterinaryCare",
    };
  }, []);

  return (
    <>
      <Helmet>
        <title>{PAGE_TITLE}</title>
        <meta name="description" content={PAGE_DESCRIPTION} />
        <script type="application/ld+json">{JSON.stringify(jsonLdSchema)}</script>
      </Helmet>

      <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-between">
        <Navbar />

        <main className="flex-1">
          {/* Hero Section */}
          <section className="bg-gradient-to-b from-sky-100/70 via-sky-50/40 to-slate-50 px-4 py-8 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
              <div className="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white/80 px-3 py-1 text-xs font-semibold text-sky-800 backdrop-blur shadow-sm">
                <Crosshair className="h-3.5 w-3.5 text-sky-600" />
                Realtime Geolocation Matching
              </div>

              <h1 className="mt-3 font-display text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                Find Vets Near You
              </h1>
              <p className="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-600">
                Verified veterinary doctors sorted by proximity to your current device location.
              </p>

              {/* Search Bar + Location Refresh */}
              <div className="mt-5 flex flex-col gap-2.5 sm:flex-row sm:items-center">
                <div className="relative flex-1">
                  <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                  <input
                    type="text"
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    placeholder="Search doctor, clinic, city or specialization..."
                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20 shadow-sm"
                  />
                  {searchTerm && (
                    <button
                      type="button"
                      onClick={() => setSearchTerm("")}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                      <X className="h-3.5 w-3.5" />
                    </button>
                  )}
                </div>

                <button
                  type="button"
                  onClick={() => requestLocation(true)}
                  disabled={locationStatus === "requesting"}
                  className="inline-flex items-center justify-center gap-1.5 rounded-xl border border-sky-300 bg-white px-4 py-2.5 text-xs font-bold text-sky-700 shadow-sm transition hover:bg-sky-50 disabled:opacity-60 cursor-pointer"
                >
                  <RefreshCw
                    className={`h-3.5 w-3.5 ${
                      locationStatus === "requesting" ? "animate-spin" : ""
                    }`}
                  />
                  {locationStatus === "requesting" ? "Locating..." : "Update Location"}
                </button>
              </div>

              {/* Quick Filter Chips */}
              <div className="mt-4 flex flex-wrap items-center gap-2">
                <div className="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                  {FILTER_TAGS.map((tag) => (
                    <button
                      key={tag.id}
                      type="button"
                      onClick={() => setSelectedTag(tag.id)}
                      className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold transition cursor-pointer ${
                        selectedTag === tag.id
                          ? "bg-sky-600 text-white shadow-sm"
                          : "border border-slate-200 bg-white text-slate-600 hover:bg-slate-100"
                      }`}
                    >
                      {tag.label}
                    </button>
                  ))}
                </div>

                {/* Radius Filter */}
                <div className="ml-auto flex items-center gap-1.5 text-xs text-slate-500">
                  <Filter className="h-3.5 w-3.5 text-slate-400" />
                  <select
                    value={selectedRadius}
                    onChange={(e) => setSelectedRadius(e.target.value)}
                    className="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-sky-500 cursor-pointer"
                  >
                    {RADIUS_OPTIONS.map((opt) => (
                      <option key={opt.id} value={opt.id}>
                        {opt.label}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>
          </section>

          {/* Results Grid Section */}
          <section className="px-4 pb-12 pt-4 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
              <div className="mb-4 flex items-center justify-between">
                <h2 className="font-display text-2xl font-extrabold text-slate-900">
                  Nearby Doctors
                </h2>
                {filteredDoctors.length > 0 && (
                  <span className="text-xs font-semibold text-slate-500">
                    {filteredDoctors.length} {filteredDoctors.length === 1 ? "doctor" : "doctors"} nearby (sorted by distance)
                  </span>
                )}
              </div>

              {/* State 1: Location requested or Loading -> Skeleton */}
              {locationStatus === "requesting" || loading ? (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                  {Array.from({ length: 8 }).map((_, i) => (
                    <div
                      key={`skeleton-${i}`}
                      className="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm"
                    >
                      <div className="h-32 animate-pulse bg-gradient-to-br from-slate-200 via-slate-100 to-slate-200" />
                      <div className="space-y-3 p-3">
                        <div className="flex items-center gap-2">
                          <div className="h-3 w-3 animate-pulse rounded-full bg-slate-200" />
                          <div className="h-3 w-28 animate-pulse rounded-full bg-slate-200" />
                        </div>
                        <div className="flex gap-1.5">
                          <div className="h-4 w-16 animate-pulse rounded-full bg-slate-100" />
                          <div className="h-4 w-20 animate-pulse rounded-full bg-slate-100" />
                        </div>
                        <div className="h-8 w-full animate-pulse rounded-xl bg-sky-50" />
                      </div>
                    </div>
                  ))}
                </div>
              ) : locationStatus === "denied" || locationStatus === "unsupported" ? (
                /* State 2: Permission denied / unsupported */
                <div className="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm text-center sm:text-left">
                  <div className="flex items-center gap-2 text-amber-600">
                    <AlertCircle className="h-5 w-5" />
                    <h3 className="font-display text-lg font-extrabold text-slate-900">
                      Location access required
                    </h3>
                  </div>
                  <p className="mt-2 text-xs leading-relaxed text-slate-600 max-w-xl">
                    Please enable device location access in your browser to view verified veterinary doctors sorted by proximity to your current location.
                  </p>
                  <button
                    type="button"
                    onClick={() => requestLocation(true)}
                    className="mt-4 inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700 active:scale-95 cursor-pointer"
                  >
                    <Crosshair className="h-4 w-4" />
                    Retry Location Access
                  </button>
                </div>
              ) : error ? (
                /* State 3: API Error */
                <div className="rounded-[24px] border border-rose-200 bg-rose-50 p-6 text-rose-800 shadow-sm">
                  <h3 className="font-display text-lg font-extrabold">
                    Could not load doctors
                  </h3>
                  <p className="mt-1 text-xs">{error}</p>
                  <button
                    type="button"
                    onClick={() => requestLocation(true)}
                    className="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-rose-700 cursor-pointer"
                  >
                    Try Again
                  </button>
                </div>
              ) : filteredDoctors.length === 0 ? (
                /* State 4: Empty results */
                <div className="rounded-[24px] border border-slate-200 bg-white p-8 text-center shadow-sm">
                  <h3 className="font-display text-base font-extrabold text-slate-900">
                    No doctors found
                  </h3>
                  <p className="mt-1 text-xs text-slate-500">
                    {searchTerm || selectedTag !== "all" || selectedRadius !== "all"
                      ? "No doctors matched your selected filters. Try clearing your search or expanding the radius."
                      : "No verified veterinary clinics found within your location area."}
                  </p>
                  {(searchTerm || selectedTag !== "all" || selectedRadius !== "all") && (
                    <button
                      type="button"
                      onClick={() => {
                        setSearchTerm("");
                        setSelectedTag("all");
                        setSelectedRadius("all");
                      }}
                      className="mt-3 text-xs font-bold text-sky-600 hover:underline cursor-pointer"
                    >
                      Reset All Filters
                    </button>
                  )}
                </div>
              ) : (
                /* State 5: Active Doctor Cards (4 per row) */
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                  {filteredDoctors.map((doctor) => (
                    <DoctorCard
                      key={doctor.id}
                      doctor={doctor}
                      onSelect={setSelectedDoctor}
                      onBook={handleBookAction}
                      onImageError={markImageBroken}
                      isImageBroken={brokenImages.has(doctor.id)}
                    />
                  ))}
                </div>
              )}
            </div>
          </section>
        </main>

        <Footer />
      </div>

      {/* Doctor Profile Modal */}
      <ProfileModal
        doctor={selectedDoctor}
        onClose={() => setSelectedDoctor(null)}
        onBook={handleBookAction}
        isImageBroken={selectedDoctor ? brokenImages.has(selectedDoctor.id) : false}
        onImageError={markImageBroken}
      />

      {/* Modern Doctor Booking Modal (Starts at Describe step) */}
      {bookingModal.isOpen && (
        <ModernDoctorBooking
          onClose={closeBookingModal}
          orderType={bookingModal.orderType}
          initialClinic={bookingModal.clinic}
          initialDoctor={bookingModal.doctor}
          initialPackage={null}
        />
      )}
    </>
  );
}
