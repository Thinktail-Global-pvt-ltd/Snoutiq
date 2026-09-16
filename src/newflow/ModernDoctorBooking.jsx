import React, { useEffect, useState, useMemo, useRef, useCallback } from "react";
import { X, ChevronLeft, ChevronRight, Search, Shield, CreditCard, CheckCircle, CheckCircle2, Users, User, Calendar, Clock, Loader2, Filter, Star, MapPin, Award, Check, Sparkles, Video, Navigation, RefreshCw, Phone, Zap, Building2, Package, Camera, AlertCircle, HeartHandshake } from "lucide-react";
import { useNavigate, useSearchParams, useLocation } from "react-router-dom";
import { readAiAuthState } from "../ai/AiAuth";
import { confirmPaymentStart } from "../ai/booking/bookingAlerts";
import PhoneVerifyGate from "../ai/PhoneVerifyGate";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";
import clinicDefaultImg from "../assets/images/clinic.png";
import { extractPackageItems } from "./packageHelpers";

const API_BASE = "https://snoutiq.com/backend/api";

function loadRazorpayScript() {
  return new Promise((resolve) => {
    if (window.Razorpay) {
      resolve(true);
      return;
    }
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
}

function normalizePhone(v) {
  return String(v || "").replace(/\D/g, "").slice(-10);
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0,
  }).format(amount || 0);
}

function normalizeImage(value) {
  if (!value) return "";
  const text = String(value).trim();
  if (!text || text === "null" || text === "undefined") return "";
  if (/^https?:\/\//i.test(text)) {
    return text.replace("https://snoutiq.com/https://snoutiq.com", "https://snoutiq.com");
  }
  const cleanPath = text.startsWith("/") ? text.slice(1) : text;
  if (cleanPath.startsWith("backend/")) {
    return `https://snoutiq.com/${cleanPath}`;
  }
  return `https://snoutiq.com/backend/${cleanPath}`;
}

const DEFAULT_CLINIC_FALLBACK = "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=400&q=80";

function resolveClinicProfileKey(doc) {
  return String(
    doc?.clinicSlug ||
      doc?.clinic_slug ||
      doc?.vet_slug ||
      doc?.clinic?.slug ||
      doc?.clinicId ||
      doc?.clinic_id ||
      doc?.vet_registeration_id ||
      "",
  ).trim();
}

function resolveDoctorProfileKey(doc) {
  return String(doc?.doctor_id || doc?.id || "").trim();
}

function resolveClinicImage(clinic) {
  if (!clinic) return DEFAULT_CLINIC_FALLBACK;

  const candidate = clinic.clinic_image_url || 
                    clinic.clinic_image || 
                    clinic.image || 
                    clinic.clinic_image_blob ||
                    clinic.photo || 
                    clinic.banner || 
                    clinic.clinic_photo ||
                    (Array.isArray(clinic.clinic_photos) && clinic.clinic_photos[0]) ||
                    (clinic.media && (clinic.media.image_url || clinic.media.image)) ||
                    (Array.isArray(clinic.clinic_services) && clinic.clinic_services[0]?.service_pic) ||
                    null;

  if (candidate && typeof candidate === "string" && candidate.trim() !== "" && candidate !== "null" && candidate !== "undefined") {
    const norm = normalizeImage(candidate);
    if (norm) return norm;
  }

  if (Array.isArray(clinic.doctors) && clinic.doctors.length > 0) {
    const docImg = clinic.doctors[0]?.doctor_image_blob_url || 
                   clinic.doctors[0]?.doctor_image_url || 
                   clinic.doctors[0]?.doctor_image || 
                   clinic.doctors[0]?.image;
    if (docImg && typeof docImg === "string" && docImg.trim() !== "" && docImg !== "null" && docImg !== "undefined") {
      const norm = normalizeImage(docImg);
      if (norm) return norm;
    }
  }

  return DEFAULT_CLINIC_FALLBACK;
}

const formatSpecialization = (val) => {
  if (!val) return "General Vet";
  let parsed = val;
  if (typeof val === "string") {
    const trimmed = val.trim();
    if (trimmed.startsWith("[") && trimmed.endsWith("]")) {
      try {
        parsed = JSON.parse(trimmed);
      } catch (e) {
        parsed = trimmed.replace(/[\[\]\\"]/g, "").split(",").map(s => s.trim());
      }
    }
  }
  if (Array.isArray(parsed)) {
    const cleaned = parsed.map(s => String(s).replace(/[\[\]\\"]/g, "").trim()).filter(Boolean);
    return cleaned.length > 0 ? cleaned.join(", ") : "General Vet";
  }
  return String(val).replace(/[\[\]\\"]/g, "").trim() || "General Vet";
};

export const normalizeAppointmentTimeForApi = (slot) => {
  if (!slot) return "";
  const raw = typeof slot === "object" ? (slot.start || slot.value || slot.time || slot.label || "") : String(slot);
  const firstPart = raw.split("-")[0]?.trim() || raw;
  const amPmMatch = firstPart.match(/^(\d{1,2})(?::(\d{2}))?\s*(AM|PM)$/i);
  if (amPmMatch) {
    let hours = Number(amPmMatch[1]);
    const minutes = Number(amPmMatch[2] || 0);
    const meridian = amPmMatch[3].toUpperCase();
    if (meridian === "AM") {
      if (hours === 12) hours = 0;
    } else if (hours !== 12) {
      hours += 12;
    }
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:00`;
  }
  const match = String(firstPart || "").match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/);
  if (match) {
    return `${String(match[1]).padStart(2, "0")}:${String(match[2]).padStart(2, "0")}:${String(match[3] || 0).padStart(2, "0")}`;
  }
  return firstPart;
};

// LIVE Real-Time Pricing Evaluation (Day: 8:00 AM - 8:00 PM [08:00 - 19:59:59], Night: 8:00 PM - 8:00 AM [20:00 - 07:59:59])
export function isDayTimeNow() {
  const now = new Date();
  const hour = now.getHours();
  // 8:00 AM (8) to 7:59:59 PM (19). 20:00 (8:00 PM) to 7:59:59 AM is night time.
  return hour >= 8 && hour < 20;
}

export function useIsDayTime() {
  const [isDay, setIsDay] = useState(() => isDayTimeNow());

  useEffect(() => {
    const updateDayTime = () => {
      const current = isDayTimeNow();
      setIsDay((prev) => (prev !== current ? current : prev));
    };

    // Check periodically (every 5 seconds) to catch transitions (8:00 AM & 8:00 PM) instantly
    const interval = setInterval(updateDayTime, 5000);
    return () => clearInterval(interval);
  }, []);

  return isDay;
}

export function getDoctorCurrentPrice(doc, isDay = isDayTimeNow()) {
  if (!doc) return 499;
  const dayRate = Number(doc.feeDay || doc.video_day_rate || doc.doctors_price || doc.clinic_day_fee || 499);
  const nightRate = Number(doc.feeNight || doc.video_night_rate || doc.video_day_rate || doc.clinic_night_fee || doc.doctors_price || 650);
  return isDay ? dayRate : nightRate;
}

export function getClinicCurrentPrice(clinic) {
  if (!clinic) return "499";
  
  // 1. Extract from clinic_services first (e.g. In Clinic Consultation price)
  const services = clinic.clinic_services || clinic.services;
  if (Array.isArray(services) && services.length > 0) {
    const consultService = services.find(s => 
      (s.name && /consult/i.test(s.name)) || 
      s.main_service === "vet"
    ) || services[0];
    if (consultService && consultService.price !== null && consultService.price !== undefined && consultService.price !== "" && !isNaN(Number(consultService.price)) && Number(consultService.price) > 0) {
      return String(Math.round(Number(consultService.price)));
    }
  }

  // 2. Doctor's consultation price
  const firstDoc = (clinic.doctors && Array.isArray(clinic.doctors) && clinic.doctors[0]) || clinic.doctor;
  if (firstDoc?.doctors_price && !isNaN(Number(firstDoc.doctors_price)) && Number(firstDoc.doctors_price) > 0) {
    return String(Math.round(Number(firstDoc.doctors_price)));
  }
  if (clinic.doctors_price && !isNaN(Number(clinic.doctors_price)) && Number(clinic.doctors_price) > 0) {
    return String(Math.round(Number(clinic.doctors_price)));
  }

  // 3. clinic_day_fee / clinic_fee / clinic_night_fee
  const fee = clinic.clinic_day_fee || clinic.clinic_fee || clinic.fee || clinic.clinic_night_fee;
  if (fee && !isNaN(Number(fee)) && Number(fee) > 0) {
    return String(Math.round(Number(fee)));
  }

  // Fallback to static 499 if empty/null
  return "499";
}

function formatInitialDoctor(doc, clinic = null) {
  if (!doc) return null;
  const dayRate = Number(doc.video_day_rate || doc.feeDay || doc.doctors_price || 499);
  const nightRate = Number(doc.video_night_rate || doc.feeNight || doc.video_day_rate || 650);

  return {
    id: doc.id || doc.doctor_id,
    name: doc.doctor_name || doc.name || "Doctor",
    doctor_name: doc.doctor_name || doc.name || "Doctor",
    degree: doc.degree || "BVSc",
    experience: Number(doc.years_of_experience || doc.experience || 5),
    specialization: formatSpecialization(doc.specialization_select_all_that_apply || doc.specialization),
    feeDay: dayRate,
    feeNight: nightRate,
    image: normalizeImage(doc.doctor_blob_url || doc.doctor_image_blob_url || doc.doctor_image_url || doc.doctor_image || doc.image),
    status: doc.doctor_status || doc.status || "available",
    available: true,
    responseTimeDay: "0 To 15 Mins",
    clinicId: doc.vet_registeration_id || clinic?.id || clinic?.clinic_id || doc.clinicId,
    clinicSlug: doc.clinicSlug || doc.clinic_slug || doc.vet_slug || clinic?.slug || "",
    clinicName: clinic?.name || doc.clinicName || "",
    clinicAddress: clinic?.address || clinic?.formatted_address || doc.clinicAddress || "",
    clinicCity: clinic?.city || doc.clinicCity || "Gurugram",
    googleRating: clinic?.rating ?? clinic?.google_rating ?? doc.googleRating ?? 5.0,
    googleReviewCount: clinic?.user_ratings_total ?? clinic?.google_user_ratings_total ?? doc.googleReviewCount ?? 50,
  };
}

function formatInitialClinic(c) {
  if (!c) return null;
  return {
    ...c,
    id: c.id || c.clinic_id,
    name: c.name || "Veterinary Clinic",
    city: c.city || "Gurugram",
    address: c.address || c.formatted_address || "",
    google_rating: c.rating ?? c.google_rating ?? 5.0,
    google_user_ratings_total: c.user_ratings_total ?? c.google_user_ratings_total ?? 50,
    clinic_day_fee: c.clinic_day_fee,
    clinic_night_fee: c.clinic_night_fee,
  };
}

function enrichDoctorObject(doc, clinicMap = new Map()) {
  if (!doc) return null;
  const regId = String(doc.vet_registeration_id || doc.clinic_id || doc.clinicId || "");
  const clinic = clinicMap.get(regId);
  const expYears = parseInt(doc.years_of_experience || doc.experience || 0);

  const rawRating = doc.google_rating ?? doc.clinic?.rating ?? clinic?.google_rating ?? clinic?.rating;
  const parsedRating = (rawRating !== null && rawRating !== undefined && rawRating !== "" && !isNaN(Number(rawRating)))
    ? Number(rawRating) 
    : 5.0;

  const reviewCount = doc.google_user_ratings_total ?? doc.clinic?.user_ratings_total ?? clinic?.google_user_ratings_total ?? clinic?.user_ratings_total ?? 50;

  const imgUrl = doc.doctor_image_blob_url || normalizeImage(doc.doctor_image || doc.doctor_blob_url || doc.doctor_image_url || doc.image);

  const rawDistance = doc.distance_km ?? doc.clinic?.distance_km ?? clinic?.distance_km ?? doc.distance;
  const parsedDistance = (rawDistance !== null && rawDistance !== undefined && rawDistance !== "" && !isNaN(Number(rawDistance)))
    ? Number(rawDistance)
    : null;

  return {
    ...doc,
    id: doc.id || doc.doctor_id,
    doctor_id: doc.doctor_id || doc.id,
    name: doc.doctor_name || doc.name || "Doctor",
    image: imgUrl,
    degree: doc.degree || "BVSc",
    experience: expYears,
    years_of_experience: String(expYears),
    specialization: formatSpecialization(doc.specialization_select_all_that_apply || doc.specialization),
    feeDay: Number(doc.video_day_rate || doc.clinic_day_fee || 499),
    feeNight: Number(doc.video_night_rate || doc.video_day_rate || doc.clinic_night_fee || 650),
    bio: doc.bio || "",
    status: doc.doctor_status || "available",
    responseTimeDay: doc.response_time_for_online_consults_day || "0 To 15 Mins",
    responseTimeNight: doc.response_time_for_online_consults_night || "15 To 20 Mins",
    followUpPolicy: doc.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta || "",
    googleRating: parsedRating,
    googleReviewCount: Number(reviewCount),
    distance_km: parsedDistance,
    clinicCity: doc.clinic?.city || doc.clinic_address || clinic?.city || "",
    clinicName: doc.clinic_name || doc.clinic?.name || clinic?.name || "",
    clinicSlug: doc.clinic_slug || doc.vet_slug || doc.clinic?.slug || clinic?.slug || "",
    clinicId: regId,
    vet_registeration_id: regId
  };
}

function extractClinicsList(clinicRes) {
  if (!clinicRes) return [];
  const raw = Array.isArray(clinicRes?.data?.data)
    ? clinicRes.data.data
    : (Array.isArray(clinicRes?.data) ? clinicRes.data : (Array.isArray(clinicRes?.clinics) ? clinicRes.clinics : (Array.isArray(clinicRes) ? clinicRes : [])));
  return raw.filter(Boolean);
}

function extractDoctorsList(docRes, clinicMap = new Map()) {
  if (!docRes) return [];
  const entries = docRes?.doctors || docRes?.data?.doctors || docRes?.data || docRes || [];
  if (!Array.isArray(entries)) return [];

  const rawDocs = [];
  entries.forEach(item => {
    if (!item) return;
    if (Array.isArray(item.doctors) && item.doctors.length > 0) {
      item.doctors.forEach(d => {
        if (d) {
          rawDocs.push({
            ...d,
            clinic_id: d.clinic_id || item.id || item.clinic_id,
            clinic_name: d.clinic_name || item.name || item.clinic_name,
            clinic_city: d.clinic_city || item.city || item.clinic_city,
            clinic_address: d.clinic_address || item.address || item.clinic_address,
            distance_km: d.distance_km ?? item.distance_km ?? d.distance ?? item.distance
          });
        }
      });
    } else if (item.doctor_name || item.name || item.doctor_id || item.years_of_experience || item.specialization) {
      rawDocs.push(item);
    }
  });

  return rawDocs.map(doc => enrichDoctorObject(doc, clinicMap)).filter(Boolean);
}

function isSlotAfterCurrentTime(slotTimeStr, selectedDateStr) {
  if (!slotTimeStr) return false;
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  const todayStr = `${year}-${month}-${day}`;
  
  if (selectedDateStr && selectedDateStr > todayStr) {
    return true;
  }
  if (selectedDateStr && selectedDateStr < todayStr) {
    return false;
  }

  const currentMinutes = now.getHours() * 60 + now.getMinutes();

  const match = String(slotTimeStr).trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/i);
  if (!match) return true;

  let hours = parseInt(match, 10);
  const mins = parseInt(match[2], 10);
  const ampm = match[3] ? match[3].toUpperCase() : null;

  if (ampm === "PM" && hours < 12) hours += 12;
  if (ampm === "AM" && hours === 12) hours = 0;

  const slotMinutes = hours * 60 + mins;
  return slotMinutes > currentMinutes;
}

function getUpcomingDates(count = 7) {
  const dates = [];
  const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  
  for (let i = 0; i < count; i++) {
    const d = new Date();
    d.setDate(d.getDate() + i);
    
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    const dateStr = `${year}-${month}-${day}`;
    
    let label = `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]}`;
    if (i === 0) label = `Today (${d.getDate()} ${months[d.getMonth()]})`;
    if (i === 1) label = `Tomorrow (${d.getDate()} ${months[d.getMonth()]})`;
    
    dates.push({ dateStr, label, dayName: days[d.getDay()], dateNum: d.getDate(), monthName: months[d.getMonth()] });
  }
  return dates;
}

function buildStaticSlots(dateValue) {
  const selected = dateValue ? new Date(dateValue) : new Date();
  const now = new Date();
  const isToday = selected.toDateString() === now.toDateString();
  const minTodayTime = new Date(now.getTime() + 90 * 60 * 1000);

  return Array.from({ length: 19 }, (_, index) => {
    const hour = 10 + Math.floor(index / 2);
    const minute = index % 2 === 0 ? 0 : 30;
    return { hour, minute };
  })
    .filter(({ hour, minute }) => hour < 19 || (hour === 19 && minute === 0))
    .filter(({ hour, minute }) => {
      if (!isToday) return true;
      const slotDate = new Date(now);
      slotDate.setHours(hour, minute, 0, 0);
      return slotDate >= minTodayTime;
    })
    .map(({ hour, minute }) => {
      const label = new Date(1970, 0, 1, hour, minute).toLocaleTimeString("en-IN", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      });
      return {
        id: `fallback-${hour}-${minute}`,
        start: label,
        label,
        value: label,
        isBooked: false,
        available: true,
        fallback: true,
      };
    });
}

function sortByNearbyDistance(list) {
  if (!Array.isArray(list)) return [];
  return [...list].sort((a, b) => {
    const distA = a.distance_km != null && !isNaN(Number(a.distance_km)) ? Number(a.distance_km) : null;
    const distB = b.distance_km != null && !isNaN(Number(b.distance_km)) ? Number(b.distance_km) : null;
    if (distA !== null && distB !== null) return distA - distB;
    if (distA !== null) return -1;
    if (distB !== null) return 1;
    const ratingA = Number(a.google_rating || a.googleRating || a.rating || 5.0);
    const ratingB = Number(b.google_rating || b.googleRating || b.rating || 5.0);
    if (ratingB !== ratingA) return ratingB - ratingA;
    const expA = Number(a.experience || a.years_of_experience || 0);
    const expB = Number(b.experience || b.years_of_experience || 0);
    return expB - expA;
  });
}

export default function ModernDoctorBooking({ 
  onClose, 
  symptomText, 
  preSelectedPet, 
  orderType = "video_consult",
  initialDoctor = null,
  initialClinic = null,
  initialPackage = null,
}) {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams, setSearchParams] = useSearchParams();
  const isDay = useIsDayTime();

  const urlType = searchParams.get("type") || searchParams.get("orderType");
  const currentOrderType = urlType ? (urlType === "appointment" || urlType === "in_clinic" ? "appointment" : "video_consult") : orderType;
  const urlStep = searchParams.get("step");

  // Doctor States
  const [lastVetDoctors, setLastVetDoctors] = useState([]);
  const [hasLastVet, setHasLastVet] = useState(false);
  const [showAllVets, setShowAllVets] = useState(false);
  const [allVetsLoading, setAllVetsLoading] = useState(false);
  const [allVetsLoaded, setAllVetsLoaded] = useState(false);
  const [otherDoctors, setOtherDoctors] = useState([]);

  // Clinic States (Book Visit Flow)
  const [lastVetClinics, setLastVetClinics] = useState([]);
  const [hasLastClinic, setHasLastClinic] = useState(false);
  const [showAllClinics, setShowAllClinics] = useState(false);
  const [allClinicsLoading, setAllClinicsLoading] = useState(false);
  const [allClinicsLoaded, setAllClinicsLoaded] = useState(false);
  const [otherClinics, setOtherClinics] = useState([]);

  const [selectedClinic, setSelectedClinic] = useState(() => formatInitialClinic(initialClinic));
  const [loading, setLoading] = useState(true);
  const [selectedDoctor, setSelectedDoctor] = useState(() => formatInitialDoctor(initialDoctor, initialClinic));
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearchQuery, setDebouncedSearchQuery] = useState("");

  // Debounce search input by 300ms for smooth performance
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchQuery(searchQuery.trim());
    }, 300);
    return () => clearTimeout(timer);
  }, [searchQuery]);
  
  // Package States
  const [selectedPackage, setSelectedPackage] = useState(() => initialPackage || null);
  const [clinicPackages, setClinicPackages] = useState(() => {
    if (initialClinic?.specialized_packages && Array.isArray(initialClinic.specialized_packages)) {
      return extractPackageItems(initialClinic.specialized_packages);
    }
    return [];
  });
  const [loadingPackages, setLoadingPackages] = useState(false);

  // Filters
  const [selectedExpFilter, setSelectedExpFilter] = useState("any"); // "any" | "1" | "3" | "5" | "10"
  const [selectedSpecialtyFilter, setSelectedSpecialtyFilter] = useState("all");
  const [selectedPriceFilter, setSelectedPriceFilter] = useState("any"); // "any" | "0-500" | "500-1000" | "1000+"
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [viewProfileDoctor, setViewProfileDoctor] = useState(null);

  // Location / Geolocation state
  const [userCoords, setUserCoords] = useState(() => {
    const lat = localStorage.getItem("user_lat");
    const lng = localStorage.getItem("user_lng");
    if (lat && lng && !isNaN(Number(lat)) && !isNaN(Number(lng))) {
      return { lat: Number(lat), lng: Number(lng) };
    }
    return null;
  });
  const [requestingLocation, setRequestingLocation] = useState(false);

  const [flowStep, setFlowStep] = useState(() => urlStep || ((initialDoctor || initialClinic || initialPackage) ? "describe" : "list"));
  const [issueText, setIssueText] = useState(() => symptomText || localStorage.getItem("symptom_description") || "");
  const [attachedImages, setAttachedImages] = useState([]);
  const [consentGiven, setConsentGiven] = useState(true);
  const [disclaimerAccepted, setDisclaimerAccepted] = useState(false);

  // Prevent double scrollbar by locking body scroll while booking modal is open
  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = "";
    };
  }, []);

  // Sync flowStep when urlStep in searchParams changes (e.g. browser back/forward buttons)
  useEffect(() => {
    const s = searchParams.get("step");
    if (s && ["list", "describe", "checkout"].includes(s)) {
      setFlowStep(s);
    }
  }, [searchParams]);

  // Sync flowStep with URL searchParams (use replace so back button doesn't create history loops)
  const updateFlowStep = useCallback((newStep) => {
    setFlowStep(newStep);
    const newParams = new URLSearchParams(window.location.search);
    newParams.set("step", newStep);
    if (!newParams.get("type")) {
      newParams.set("type", currentOrderType);
    }
    setSearchParams(newParams, { replace: true });
  }, [currentOrderType, setSearchParams]);

  // Sync / Fetch Clinic Packages
  useEffect(() => {
    let isMounted = true;
    const targetClinic = selectedClinic || initialClinic;
    const clinicId = targetClinic?.id || targetClinic?.clinic_id;
    const clinicSlug = targetClinic?.slug || targetClinic?.clinicSlug;

    if (targetClinic?.specialized_packages && Array.isArray(targetClinic.specialized_packages) && targetClinic.specialized_packages.length > 0) {
      const items = extractPackageItems(targetClinic.specialized_packages);
      setClinicPackages(items);
      return;
    }

    const idOrSlug = clinicId || clinicSlug;
    if (!idOrSlug) return;

    const fetchPackages = async () => {
      setLoadingPackages(true);
      try {
        const res = await fetch(`${API_BASE}/clinics/${encodeURIComponent(idOrSlug)}/packages`);
        if (res.ok) {
          const data = await res.json();
          const raw = data?.packages || data?.specialized_packages || [];
          if (Array.isArray(raw) && raw.length > 0) {
            if (isMounted) setClinicPackages(extractPackageItems(raw));
            return;
          }
        }

        if (clinicSlug) {
          const pageRes = await fetch(`${API_BASE}/clinic-pages/${encodeURIComponent(clinicSlug)}`);
          if (pageRes.ok) {
            const pageData = await pageRes.json();
            const raw = pageData?.data?.specialized_packages || [];
            if (Array.isArray(raw) && raw.length > 0) {
              if (isMounted) setClinicPackages(extractPackageItems(raw));
            }
          }
        }
      } catch (e) {
        console.warn("Could not fetch clinic packages:", e);
      } finally {
        if (isMounted) setLoadingPackages(false);
      }
    };

    fetchPackages();
    return () => { isMounted = false; };
  }, [selectedClinic?.id, selectedClinic?.slug, initialClinic?.id, initialClinic?.slug]);

  // Sync initialPackage when prop changes or packages load
  useEffect(() => {
    if (initialPackage) {
      if (typeof initialPackage === "object" && (initialPackage.title || initialPackage.key)) {
        setSelectedPackage(initialPackage);
      } else if (typeof initialPackage === "string") {
        const found = clinicPackages.find(p => p.key === initialPackage || p.id === initialPackage);
        if (found) setSelectedPackage(found);
      }
    }
  }, [initialPackage, clinicPackages]);

  const handleImageUpload = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = (event) => {
        setAttachedImages(prev => [...prev, { id: Date.now() + Math.random(), src: event.target.result, file }]);
      };
      reader.readAsDataURL(file);
    });
  };

  const removeAttachedImage = (idToRemove) => {
    setAttachedImages(prev => prev.filter(img => img.id !== idToRemove));
  };

  const scrollContainerRef = useRef(null);

  // ALWAYS scroll container to top when step changes (removed viewProfileDoctor so modal view details doesn't jump scroll)
  useEffect(() => {
    if (scrollContainerRef.current) {
      scrollContainerRef.current.scrollTop = 0;
    }
    window.scrollTo(0, 0);
  }, [flowStep]);

  // Sync symptomText dynamically when passed down asynchronously
  useEffect(() => {
    if (symptomText) {
      setIssueText(symptomText);
    } else if (!issueText) {
      const stored = localStorage.getItem("symptom_description");
      if (stored) setIssueText(stored);
    }
  }, [symptomText]);
  
  // Appointment Flow Specific States
  const [selectedDate, setSelectedDate] = useState("");
  const [selectedTimeSlot, setSelectedTimeSlot] = useState("");
  const [resolvedDoctorId, setResolvedDoctorId] = useState(null);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [dateAvailError, setDateAvailError] = useState("");
  const [lockId, setLockId] = useState(null);

  const [paymentPreference, setPaymentPreference] = useState("pay_at_clinic"); // "pay_at_clinic" | "pay_online"
  const [gstInvoiceChecked, setGstInvoiceChecked] = useState(false);
  const [gstNumber, setGstNumber] = useState("");

  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);
  const [bookingSuccessData, setBookingSuccessData] = useState(null);

  const [authState, setAuthState] = useState(() => readAiAuthState());

  useEffect(() => {
    const handleAuthChange = () => {
      setAuthState(readAiAuthState());
    };
    window.addEventListener("snoutiq_auth_changed", handleAuthChange);
    window.addEventListener("snoutiq_pet_changed", handleAuthChange);
    window.addEventListener("storage", handleAuthChange);
    return () => {
      window.removeEventListener("snoutiq_auth_changed", handleAuthChange);
      window.removeEventListener("snoutiq_pet_changed", handleAuthChange);
      window.removeEventListener("storage", handleAuthChange);
    };
  }, []);

  const token = authState?.token;
  const user = authState?.user || {};
  const userId = user.id || user.user_id || authState?.user_id || authState?.userId || 1179;

  const [selectedPetId, setSelectedPetId] = useState(null);

  const availablePets = useMemo(() => {
    const list = Array.isArray(user.pets) && user.pets.length > 0
      ? user.pets
      : (user.pet ? [user.pet] : []);
    const map = new Map();
    list.forEach((p) => {
      if (!p) return;
      const key = p.id || p.pet_id || p.name || p.pet_name;
      if (key && !map.has(key)) map.set(key, p);
    });
    return Array.from(map.values());
  }, [user.pets, user.pet]);

  let rawPet = (selectedPetId && availablePets.find(p => String(p.id || p.pet_id) === String(selectedPetId)))
    || preSelectedPet
    || availablePets[0]
    || user.pet
    || authState?.pet
    || {};

  if (!rawPet || Object.keys(rawPet).length === 0) {
    try {
      const storedPet = localStorage.getItem("selected_pet_data") || localStorage.getItem("current_pet");
      if (storedPet) rawPet = JSON.parse(storedPet);
    } catch (e) {}
  }
  const pet = rawPet;

  const [showPhoneGate, setShowPhoneGate] = useState(false);
  const [verifiedPhone, setVerifiedPhone] = useState(null);

  const displayUserName = user.name || user.owner_name || user.first_name || user.user_name || user.full_name || localStorage.getItem("user_name") || "Pet Parent";
  const rawMobile = user.mobile || user.phone || user.phone_number || user.user_mobile || user.contact || localStorage.getItem("user_mobile") || "";
  const displayUserMobile = rawMobile ? normalizePhone(rawMobile) : "N/A";
  const effectiveUserMobile = (verifiedPhone && normalizePhone(verifiedPhone)) || (displayUserMobile !== "N/A" ? normalizePhone(displayUserMobile) : null);
  
  const displayPetName = pet.name || pet.pet_name || pet.title || localStorage.getItem("pet_name") || "Pet";
  const displayPetBreed = pet.breed || pet.pet_breed || pet.species || pet.pet_species || pet.pet_type || pet.type || pet.category || localStorage.getItem("pet_breed") || "Dog/Cat";

  const [packagePetTab, setPackagePetTab] = useState("all");

  useEffect(() => {
    if (selectedPackage?.petType && selectedPackage.petType !== "Pet") {
      setPackagePetTab(selectedPackage.petType);
    } else {
      const b = (displayPetBreed || "").toLowerCase();
      if (b.includes("cat") || b.includes("feline") || b.includes("kitten")) setPackagePetTab("Cat");
      else if (b.includes("dog") || b.includes("canine") || b.includes("puppy")) setPackagePetTab("Dog");
    }
  }, [displayPetBreed, selectedPackage]);

  const visibleClinicPackages = useMemo(() => {
    if (packagePetTab === "all") return clinicPackages;
    return clinicPackages.filter(p => p.petType === packagePetTab);
  }, [clinicPackages, packagePetTab]);

  const unlockCurrentSlot = async (lockIdToUnlock) => {
    const targetLockId = lockIdToUnlock || lockId;
    if (!targetLockId) return;
    try {
      await fetch(`${API_BASE}/doctors/slots/unlock`, {
        method: "POST",
        headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        body: JSON.stringify({ lock_id: targetLockId })
      });
    } catch (err) {
      console.warn("Slot unlock error:", err);
    } finally {
      setLockId(null);
    }
  };

  const handleModalClose = () => {
    document.body.style.overflow = "";
    sessionStorage.removeItem("snoutiq_modal_open");
    sessionStorage.removeItem("snoutiq_modal_order_type");
    if (lockId) {
      unlockCurrentSlot(lockId);
    }

    const params = new URLSearchParams(window.location.search);
    if (params.has("step") || params.has("type") || params.has("orderType")) {
      params.delete("step");
      params.delete("type");
      params.delete("orderType");
      const cleanSearch = params.toString() ? `?${params.toString()}` : "";
      navigate({
        pathname: location.pathname,
        search: cleanSearch,
      }, { replace: true });
    }

    if (onClose) {
      onClose();
    } else if (location.pathname === "/doctor-booking") {
      if (window.history.length > 1) {
        navigate(-1);
      } else {
        navigate("/");
      }
    }
  };

  // Helper to fetch all doctors
  const fetchAllDoctors = useCallback(async (overrideLat, overrideLng) => {
    setAllVetsLoading(true);
    const activeLat = overrideLat ?? userCoords?.lat;
    const activeLng = overrideLng ?? userCoords?.lng;
    const locQuery = activeLat && activeLng ? `&lat=${activeLat}&lng=${activeLng}` : '';

    try {
      const [docRes, clinicRes] = await Promise.all([
        fetch(`${API_BASE}/exported_from_excell_doctors?${userId ? `user_id=${userId}` : ''}${locQuery}`, {
          headers: { Accept: "application/json" }
        }).then(r => r.ok ? r.json() : null).catch(() => null),
        fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?${userId ? `user_id=${userId}` : ''}${locQuery}`, {
          headers: { Accept: "application/json" }
        }).then(r => r.ok ? r.json() : null).catch(() => null)
      ]);

      const rawClinics = extractClinicsList(clinicRes);
      const clinicMap = new Map();
      rawClinics.forEach(c => {
        const key = String(c.id || c.clinic_id || "");
        if (key) clinicMap.set(key, c);
      });

      const enrichedDoctors = extractDoctorsList(docRes, clinicMap);
      setOtherDoctors(enrichedDoctors);
      setAllVetsLoaded(true);
    } catch (err) {
      console.error("Failed to load all doctors", err);
    } finally {
      setAllVetsLoading(false);
      setLoading(false);
    }
  }, [userId, userCoords]);

  // Helper to fetch all clinics (Book Visit Flow)
  const fetchAllClinics = useCallback(async (overrideLat, overrideLng) => {
    setAllClinicsLoading(true);
    const activeLat = overrideLat ?? userCoords?.lat;
    const activeLng = overrideLng ?? userCoords?.lng;
    const locQuery = activeLat && activeLng ? `&lat=${activeLat}&lng=${activeLng}` : '';

    try {
      let inclinicRes = await fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?${userId ? `user_id=${userId}` : ''}${locQuery}`, {
        headers: { Accept: "application/json" }
      }).then(r => r.ok ? r.json() : null).catch(() => null);

      if (!inclinicRes) {
        inclinicRes = await fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations${locQuery ? `?${locQuery.slice(1)}` : ''}`, {
          headers: { Accept: "application/json" }
        }).then(r => r.ok ? r.json() : null).catch(() => null);
      }

      const rawClinicsData = extractClinicsList(inclinicRes);
      setOtherClinics(rawClinicsData);
      setAllClinicsLoaded(true);
    } catch (e) {
      console.error("Failed to load inclinic list:", e);
    } finally {
      setAllClinicsLoading(false);
      setLoading(false);
    }
  }, [userId, userCoords]);

  // Switch between Talk to Vet (video_consult) and Book Visit (appointment)
  const handleSwitchOrderType = useCallback((newType) => {
    const newParams = new URLSearchParams(window.location.search);
    newParams.set("type", newType);
    newParams.set("step", "list");
    setSearchParams(newParams, { replace: true });
    setFlowStep("list");
    setSearchQuery("");
    setSelectedDoctor(null);
    setSelectedClinic(null);
    setSelectedPackage(null);
    if (newType === "appointment") {
      if (!allClinicsLoaded) fetchAllClinics();
    } else {
      if (!allVetsLoaded) fetchAllDoctors();
    }
  }, [allClinicsLoaded, allVetsLoaded, fetchAllClinics, fetchAllDoctors, setSearchParams]);

  // Geolocation Handler
  const handleRequestLocation = useCallback(() => {
    if (!navigator.geolocation) {
      alert("Geolocation is not supported by your browser");
      return;
    }
    setRequestingLocation(true);
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        setUserCoords({ lat, lng });
        localStorage.setItem("user_lat", String(lat));
        localStorage.setItem("user_lng", String(lng));
        try {
          await fetch(`${API_BASE}/users/location`, {
            method: "POST",
            headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
            body: JSON.stringify({ user_id: userId, lat, lng })
          });
        } catch (e) {}
        setRequestingLocation(false);
        if (currentOrderType === "appointment") {
          fetchAllClinics(lat, lng);
        } else {
          fetchAllDoctors(lat, lng);
        }
      },
      (err) => {
        console.warn("Geolocation error:", err);
        setRequestingLocation(false);
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
  }, [userId, token, currentOrderType, fetchAllClinics, fetchAllDoctors]);

  // STEP 1 — Initial Data Fetching for both Talk to Vet & Book Visit
  useEffect(() => {
    async function loadInitialData() {
      setLoading(true);
      const activeLat = userCoords?.lat;
      const activeLng = userCoords?.lng;
      const locQuery = activeLat && activeLng ? `&lat=${activeLat}&lng=${activeLng}` : '';

      const fetchLastVetData = async () => {
        if (!userId) return null;
        try {
          const r = await fetch(`${API_BASE}/users/last-vet-details?user_id=${userId}${locQuery}`, {
            headers: token ? { Authorization: `Bearer ${token}` } : {}
          });
          if (r.ok) return await r.json();
        } catch (e) {
          // ignore
        }
        try {
          const r = await fetch(`${API_BASE}/users/last-vet-details?user_id=${userId}${locQuery}`);
          if (r.ok) return await r.json();
        } catch (e) {
          // ignore
        }
        return null;
      };

      if (currentOrderType === "appointment") {
        // Book Visit Flow: Check last-vet-details and inclinic-lists in parallel for distance
        try {
          const [res, inclinicRes] = await Promise.all([
            fetchLastVetData(),
            fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?${userId ? `user_id=${userId}` : ''}${locQuery}`, {
              headers: { Accept: "application/json" }
            }).then(r => r.ok ? r.json() : null).catch(() => null)
          ]);

          const rawClinicsData = extractClinicsList(inclinicRes);

          if (rawClinicsData.length > 0) {
            setOtherClinics(rawClinicsData);
            setAllClinicsLoaded(true);
          }
          
          let extractedClinics = [];
          if (res?.data?.clinic && typeof res.data.clinic === 'object' && res.data.clinic.name) {
            extractedClinics.push(res.data.clinic);
          } else if (res?.clinic && typeof res.clinic === 'object' && res.clinic.name) {
            extractedClinics.push(res.clinic);
          } else if (Array.isArray(res?.data?.clinics)) {
            extractedClinics.push(...res.data.clinics);
          } else if (Array.isArray(res?.clinics)) {
            extractedClinics.push(...res.clinics);
          } else if (Array.isArray(res?.data)) {
            extractedClinics.push(...res.data.filter(x => x && (x.clinic_name || x.name || x.clinic)));
          }

          const docs = res?.data?.doctors || res?.doctors || [];
          if (extractedClinics.length === 0 && docs.length > 0) {
            docs.forEach(d => {
              if (d.clinic) {
                extractedClinics.push({
                  ...d.clinic,
                  distance_km: d.clinic.distance_km ?? d.distance_km ?? d.distance
                });
              } else if (d.clinic_name || d.clinic_id) {
                extractedClinics.push({
                  id: d.clinic_id || d.id,
                  name: d.clinic_name || "Veterinary Clinic",
                  city: d.clinic_address || d.city || "Gurugram",
                  address: d.clinic_address || "",
                  google_rating: d.google_rating || 5.0,
                  google_user_ratings_total: d.google_user_ratings_total || 50,
                  distance_km: d.distance_km ?? d.distance,
                  doctors: [d]
                });
              }
            });
          }

          const uniqueLastClinics = [];
          const seenIds = new Set();
          extractedClinics.forEach(c => {
            const cId = String(c.id || c.clinic_id || c.name);
            if (cId && !seenIds.has(cId)) {
              seenIds.add(cId);

              // If distance_km is missing in last-vet-details response, retrieve from inclinic API list
              let dist = c.distance_km ?? c.distance;
              if (dist == null && rawClinicsData.length > 0) {
                const match = rawClinicsData.find(rc => 
                  String(rc.id) === String(c.id || c.clinic_id) ||
                  (rc.name && c.name && rc.name.toLowerCase().trim() === c.name.toLowerCase().trim())
                );
                if (match && match.distance_km != null) {
                  dist = match.distance_km;
                }
              }

              uniqueLastClinics.push({ ...c, distance_km: dist });
            }
          });

          const hasClinics = res?.success === true && uniqueLastClinics.length > 0;

          if (hasClinics) {
            setLastVetClinics(uniqueLastClinics);
            setHasLastClinic(true);
            setLoading(false);
          } else {
            setHasLastClinic(false);
            if (!rawClinicsData.length) {
              await fetchAllClinics();
            } else {
              setLoading(false);
            }
          }
        } catch (err) {
          console.warn("last-vet-details clinic check failed, loading all clinics as fallback:", err);
          setHasLastClinic(false);
          await fetchAllClinics();
        }
        return;
      }

      // Talk to Vet Flow: Check last-vet-details and exported_from_excell_doctors in parallel
      try {
        const [res, docRes, clinicRes] = await Promise.all([
          fetchLastVetData(),
          fetch(`${API_BASE}/exported_from_excell_doctors?${userId ? `user_id=${userId}` : ''}${locQuery}`, {
            headers: { Accept: "application/json" }
          }).then(r => r.ok ? r.json() : null).catch(() => null),
          fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?${userId ? `user_id=${userId}` : ''}${locQuery}`, {
            headers: { Accept: "application/json" }
          }).then(r => r.ok ? r.json() : null).catch(() => null)
        ]);

        const rawClinics = extractClinicsList(clinicRes);
        const clinicMap = new Map();
        rawClinics.forEach(c => {
          const key = String(c.id || c.clinic_id || "");
          if (key) clinicMap.set(key, c);
        });

        const allDocs = extractDoctorsList(docRes, clinicMap);

        if (allDocs.length > 0) {
          setOtherDoctors(allDocs);
          setAllVetsLoaded(true);
        }

        const lastDocs = res?.data?.doctors || res?.doctors || (Array.isArray(res?.data) ? res.data : []);
        const hasVet = res?.success === true && Array.isArray(lastDocs) && lastDocs.length > 0;

        if (hasVet) {
          const enrichedLastVet = lastDocs.map(d => {
            // If distance_km is missing in last-vet-details, match from allDocs
            let dist = d.distance_km ?? d.distance;
            if (dist == null && allDocs.length > 0) {
              const match = allDocs.find(rd => 
                String(rd.id || rd.doctor_id) === String(d.id || d.doctor_id) || 
                (rd.name && d.doctor_name && rd.name.toLowerCase().trim() === d.doctor_name.toLowerCase().trim()) ||
                (rd.doctor_name && d.doctor_name && rd.doctor_name.toLowerCase().trim() === d.doctor_name.toLowerCase().trim()) ||
                (rd.doctor_mobile && d.doctor_mobile && rd.doctor_mobile === d.doctor_mobile)
              );
              if (match && match.distance_km != null) {
                dist = match.distance_km;
              }
            }
            return enrichDoctorObject({ ...d, distance_km: dist }, clinicMap);
          }).filter(Boolean);

          setLastVetDoctors(enrichedLastVet);
          setHasLastVet(true);
          setLoading(false);
        } else {
          setHasLastVet(false);
          if (!allDocs.length) {
            await fetchAllDoctors();
          } else {
            setLoading(false);
          }
        }
      } catch (err) {
        console.warn("last-vet-details check failed, loading all doctors as fallback:", err);
        setHasLastVet(false);
        await fetchAllDoctors();
      }
    }

    loadInitialData();
  }, [token, currentOrderType, userId, fetchAllDoctors, fetchAllClinics, userCoords]);

  const handleToggleVets = () => {
    if (!showAllVets && !allVetsLoaded) {
      fetchAllDoctors();
    }
    setShowAllVets(prev => !prev);
  };

  const handleToggleClinics = () => {
    if (!showAllClinics && !allClinicsLoaded) {
      fetchAllClinics();
    }
    setShowAllClinics(prev => !prev);
  };

  // Available Specialties strictly matching the Registration Form options (no extra fabricated fields)
  const availableSpecialties = useMemo(() => [
    "General Practice",
    "Dogs",
    "Cats",
    "Surgery",
    "Skin / Dermatology",
    "Exotic Pet",
    "Livestock",
  ], []);

  const matchesDoctorSpecialty = (docSpec, filter) => {
    if (!filter || filter === "all") return true;
    const target = filter.toLowerCase().trim();
    const specText = (docSpec || "").toLowerCase();

    if (target === "general practice" || target === "general vet") {
      return specText.includes("general");
    }
    if (target.includes("skin") || target.includes("dermatology")) {
      return specText.includes("skin") || specText.includes("dermatology");
    }
    if (target === "dogs") {
      return specText.includes("dog");
    }
    if (target === "cats") {
      return specText.includes("cat");
    }
    if (target === "exotic pet") {
      return specText.includes("exotic");
    }
    if (target === "livestock") {
      return specText.includes("livestock");
    }
    if (target === "surgery") {
      return specText.includes("surgery") || specText.includes("surgeon");
    }
    return specText.includes(target);
  };

  // Filter Last Vet Doctors by Search (debounced), Experience, Specialty & Price
  const filteredLastVetDoctors = useMemo(() => {
    const q = (debouncedSearchQuery || "").toLowerCase();
    const minYears = parseInt(selectedExpFilter) || 0;
    const filtered = lastVetDoctors.filter(doc => {
      const matchesSearch = !q || 
        (doc.name || "").toLowerCase().includes(q) || 
        (doc.doctor_name || "").toLowerCase().includes(q) || 
        (doc.specialization || "").toLowerCase().includes(q) || 
        (doc.degree || "").toLowerCase().includes(q) || 
        (doc.clinicCity || "").toLowerCase().includes(q) ||
        (doc.clinicName || "").toLowerCase().includes(q) ||
        (doc.clinic_address || "").toLowerCase().includes(q);
      const matchesExp = (doc.experience || 0) >= minYears;
      const matchesSpecialty = matchesDoctorSpecialty(doc.specialization, selectedSpecialtyFilter);
      const docPrice = getDoctorCurrentPrice(doc, isDay);
      let matchesPrice = true;
      if (selectedPriceFilter === "0-500") matchesPrice = docPrice <= 500;
      else if (selectedPriceFilter === "500-1000") matchesPrice = docPrice > 500 && docPrice <= 1000;
      else if (selectedPriceFilter === "1000+") matchesPrice = docPrice > 1000;
      return matchesSearch && matchesExp && matchesSpecialty && matchesPrice;
    });
    return sortByNearbyDistance(filtered);
  }, [lastVetDoctors, debouncedSearchQuery, selectedExpFilter, selectedSpecialtyFilter, selectedPriceFilter, isDay]);

  // Filter Other Doctors by Search (debounced), Experience, Specialty & Price, EXCLUDING duplicates from lastVetDoctors
  const filteredOtherDoctors = useMemo(() => {
    const q = (debouncedSearchQuery || "").toLowerCase();
    const minYears = parseInt(selectedExpFilter) || 0;
    const deduplicated = otherDoctors.filter(
      doc => !lastVetDoctors.some(lv => String(lv.doctor_id || lv.id) === String(doc.id || doc.doctor_id))
    );

    const filtered = deduplicated.filter(doc => {
      const matchesSearch = !q || 
        (doc.name || "").toLowerCase().includes(q) || 
        (doc.doctor_name || "").toLowerCase().includes(q) || 
        (doc.specialization || "").toLowerCase().includes(q) || 
        (doc.degree || "").toLowerCase().includes(q) || 
        (doc.clinicCity || "").toLowerCase().includes(q) ||
        (doc.clinicName || "").toLowerCase().includes(q) ||
        (doc.clinic_address || "").toLowerCase().includes(q);
      const matchesExp = (doc.experience || 0) >= minYears;
      const matchesSpecialty = matchesDoctorSpecialty(doc.specialization, selectedSpecialtyFilter);
      const docPrice = getDoctorCurrentPrice(doc, isDay);
      let matchesPrice = true;
      if (selectedPriceFilter === "0-500") matchesPrice = docPrice <= 500;
      else if (selectedPriceFilter === "500-1000") matchesPrice = docPrice > 500 && docPrice <= 1000;
      else if (selectedPriceFilter === "1000+") matchesPrice = docPrice > 1000;
      return matchesSearch && matchesExp && matchesSpecialty && matchesPrice;
    });
    return sortByNearbyDistance(filtered);
  }, [otherDoctors, lastVetDoctors, debouncedSearchQuery, selectedExpFilter, selectedSpecialtyFilter, selectedPriceFilter, isDay]);

  // Filter Last Vet Clinics by Search (debounced)
  const filteredLastVetClinics = useMemo(() => {
    const q = (debouncedSearchQuery || "").toLowerCase();
    const filtered = lastVetClinics.filter(c => {
      if (!q) return true;
      const inDocs = Array.isArray(c.doctors) && c.doctors.some(d => 
        (d.name || "").toLowerCase().includes(q) || 
        (d.doctor_name || "").toLowerCase().includes(q) || 
        (d.specialization_select_all_that_apply || "").toLowerCase().includes(q)
      );
      const inServices = Array.isArray(c.clinic_services) && c.clinic_services.some(s => 
        (s.name || "").toLowerCase().includes(q) || 
        (s.description || "").toLowerCase().includes(q)
      );
      return (
        (c.name || "").toLowerCase().includes(q) || 
        (c.city || "").toLowerCase().includes(q) ||
        (c.address || "").toLowerCase().includes(q) ||
        (c.pincode || "").toLowerCase().includes(q) ||
        inDocs || inServices
      );
    });
    return sortByNearbyDistance(filtered);
  }, [lastVetClinics, debouncedSearchQuery]);

  // Filter Other Clinics by Search (debounced), EXCLUDING duplicates from lastVetClinics
  const filteredOtherClinics = useMemo(() => {
    const q = (debouncedSearchQuery || "").toLowerCase();
    const deduplicated = otherClinics.filter(
      c => !lastVetClinics.some(lc => String(lc.id || lc.clinic_id) === String(c.id || c.clinic_id))
    );

    const filtered = deduplicated.filter(c => {
      if (!q) return true;
      const inDocs = Array.isArray(c.doctors) && c.doctors.some(d => 
        (d.name || "").toLowerCase().includes(q) || 
        (d.doctor_name || "").toLowerCase().includes(q) || 
        (d.specialization_select_all_that_apply || "").toLowerCase().includes(q)
      );
      const inServices = Array.isArray(c.clinic_services) && c.clinic_services.some(s => 
        (s.name || "").toLowerCase().includes(q) || 
        (s.description || "").toLowerCase().includes(q)
      );
      return (
        (c.name || "").toLowerCase().includes(q) || 
        (c.city || "").toLowerCase().includes(q) ||
        (c.address || "").toLowerCase().includes(q) ||
        (c.pincode || "").toLowerCase().includes(q) ||
        inDocs || inServices
      );
    });
    return sortByNearbyDistance(filtered);
  }, [otherClinics, lastVetClinics, debouncedSearchQuery]);


  // Live Current Fee calculation
  const consultationBaseFee = currentOrderType === "appointment"
    ? (selectedClinic ? Number(getClinicCurrentPrice(selectedClinic)) : (selectedDoctor ? Number(getClinicCurrentPrice({ doctors: [selectedDoctor], clinic_day_fee: selectedDoctor.doctors_price })) : 499))
    : (selectedDoctor ? getDoctorCurrentPrice(selectedDoctor, isDay) : (selectedClinic ? Number(getClinicCurrentPrice(selectedClinic)) : 499));
  const packagePrice = selectedPackage ? Number(selectedPackage.price ?? selectedPackage.rawPrice ?? 0) : null;
  const isPackageSelected = Boolean(selectedPackage && packagePrice && packagePrice > 0);

  // When a package is selected, its fee is the all-inclusive package price
  const currentFee = isPackageSelected ? packagePrice : consultationBaseFee;
  const GST_RATE = 0.18;
  const gstAmount = isPackageSelected ? 0 : Math.round(currentFee * GST_RATE);
  const totalAmount = isPackageSelected ? currentFee : (currentFee + gstAmount);

  // Fetch slots for In-Clinic Appointment
  const fetchDateAvailabilityAndSlots = async (dateStr, targetDoc, targetClinic) => {
    const docToUse = targetDoc || selectedDoctor;
    const clinicObj = targetClinic || selectedClinic;
    const clinicId = clinicObj?.id || clinicObj?.clinic_id || docToUse?.clinicId || docToUse?.vet_registeration_id || docToUse?.id;

    if (!clinicId) return;

    setSelectedDate(dateStr);
    setSelectedTimeSlot("");
    setResolvedDoctorId(null);
    setAvailableSlots([]);
    setDateAvailError("");
    setLoadingSlots(true);

    try {
      let docIdToUse = docToUse?.id;
      try {
        const availRes = await fetch(`${API_BASE}/clinics/${clinicId}/doctor-availability?service_type=in_clinic&date=${dateStr}`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        });
        if (availRes.ok) {
          const availData = await availRes.json();
          if (availData.available_doctor_id || availData.doctor_id) {
            docIdToUse = availData.available_doctor_id || availData.doctor_id;
          } else if (Array.isArray(availData.doctor_ids) && availData.doctor_ids.length > 0) {
            docIdToUse = availData.doctor_ids[0];
          }
        }
      } catch (e) {}

      setResolvedDoctorId(docIdToUse);

      let slotsData = null;
      try {
        const slotsRes = await fetch(`${API_BASE}/doctors/${docIdToUse}/slots/summary?date=${dateStr}&service_type=in_clinic`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        });
        if (slotsRes.ok) slotsData = await slotsRes.json();
      } catch (e) {}

      if (!slotsData || !slotsData.success || !Array.isArray(slotsData.slots) || slotsData.slots.length === 0) {
        try {
          const altRes = await fetch(`${API_BASE}/doctors/active-slots?doctor_id=${docIdToUse}&date=${dateStr}`, {
            headers: token ? { Authorization: `Bearer ${token}` } : {}
          });
          if (altRes.ok) {
            const altData = await altRes.json();
            if (altData.success && Array.isArray(altData.active_hours)) {
              const extracted = [];
              altData.active_hours.forEach(ah => {
                if (Array.isArray(ah.slots)) {
                  ah.slots.forEach(s => extracted.push(typeof s === "string" ? { start: s, label: s, isBooked: false } : s));
                }
              });
              if (extracted.length > 0) slotsData = { success: true, slots: extracted };
            }
          }
        } catch (e) {}
      }

      const rawSlotsList = slotsData?.slots || slotsData?.data?.slots || [];
      let allSlots = [];
      if (Array.isArray(rawSlotsList) && rawSlotsList.length > 0) {
        allSlots = rawSlotsList.map(s => {
          if (typeof s === "string") return { start: s, label: s, isBooked: false };
          return { start: s.start || s.time || "", label: s.label || s.time || s.start || "", isBooked: s.is_booked === true || s.booked === true };
        }).filter(s => s.start);
      }

      let unbooked = allSlots.filter(s => !s.isBooked && isSlotAfterCurrentTime(s.start || s.label, dateStr));
      if (unbooked.length === 0) {
        // Fallback to static slots (10 AM to 7 PM with 30 min intervals)
        unbooked = buildStaticSlots(dateStr);
      }

      const upcomingSlots = unbooked.slice(0, 8);
      if (upcomingSlots.length > 0) {
        setAvailableSlots(upcomingSlots);
      } else {
        setDateAvailError("No upcoming slots left for today. Please select another date.");
      }
    } catch (err) {
      const fallback = buildStaticSlots(dateStr);
      if (fallback.length > 0) {
        setAvailableSlots(fallback.slice(0, 8));
      } else {
        setDateAvailError("Error loading slots.");
      }
    } finally {
      setLoadingSlots(false);
    }
  };

  const handleSelectClinic = (clinic) => {
    setSelectedClinic(clinic);
    const clinicPrice = Number(getClinicCurrentPrice(clinic, isDay));
    const clinicDocs = Array.isArray(clinic.doctors) && clinic.doctors.length > 0 ? clinic.doctors : [];
    const firstDoc = clinicDocs[0] ? {
      id: clinicDocs[0].id || clinicDocs[0].doctor_id,
      name: clinicDocs[0].doctor_name || clinicDocs[0].name || "Doctor",
      image: normalizeImage(clinicDocs[0].doctor_blob_url || clinicDocs[0].doctor_image_blob_url || clinicDocs[0].doctor_image),
      specialization: formatSpecialization(clinicDocs[0].specialization_select_all_that_apply),
      degree: clinicDocs[0].degree || "BVSc",
      experience: clinicDocs[0].years_of_experience || 5,
      feeDay: clinicPrice,
      feeNight: clinicPrice,
      clinicId: clinic.id || clinic.clinic_id,
      clinicName: clinic.name
    } : {
      id: `clinic-vet-${clinic.id}`,
      name: clinic.name || "Clinic Vet",
      degree: "BVSc",
      experience: 5,
      clinicId: clinic.id || clinic.clinic_id,
      clinicName: clinic.name,
      feeDay: clinicPrice,
      feeNight: clinicPrice
    };

    setSelectedDoctor(firstDoc);
    const todayStr = getUpcomingDates(7)[0].dateStr;
    fetchDateAvailabilityAndSlots(todayStr, firstDoc, clinic);
    updateFlowStep("describe");
  };

  const handleBookNowClick = (doc) => {
    setSelectedDoctor(doc);
    if (currentOrderType === "appointment") {
      fetchDateAvailabilityAndSlots(getUpcomingDates(7)[0].dateStr, doc);
    }
    updateFlowStep("describe");
  };

  const handleViewProfileClick = (doc) => {
    const clinicKey = resolveClinicProfileKey(doc);
    if (!clinicKey) {
      setViewProfileDoctor(doc);
      return;
    }

    const doctorKey = resolveDoctorProfileKey(doc);
    const query = doctorKey ? `?doctor_id=${encodeURIComponent(doctorKey)}` : "";

    document.body.style.overflow = "";
    sessionStorage.removeItem("snoutiq_modal_open");
    sessionStorage.removeItem("snoutiq_modal_order_type");
    navigate(`/clinics/${encodeURIComponent(clinicKey)}${query}`);
    onClose?.();
  };

  const handleHeaderBack = () => {
    if (flowStep === "checkout") {
      updateFlowStep("describe");
      return;
    }
    if (flowStep === "describe" && (initialDoctor || initialClinic || initialPackage)) {
      handleModalClose();
      return;
    }
    if (flowStep === "describe") {
      updateFlowStep("list");
      return;
    }
    handleModalClose();
  };

  // Handle initialClinic / initialDoctor props passed from external pages (like clinic slug pages)
  useEffect(() => {
    if (currentOrderType === "appointment" && initialClinic) {
      const c = formatInitialClinic(initialClinic);
      setSelectedClinic(c);
      if (initialDoctor) {
        const d = formatInitialDoctor(initialDoctor, c);
        setSelectedDoctor(d);
        const todayStr = getUpcomingDates(7)[0].dateStr;
        fetchDateAvailabilityAndSlots(todayStr, d, c);
        setFlowStep("describe");
      } else {
        handleSelectClinic(c);
      }
    } else if (currentOrderType === "video_consult" && initialDoctor) {
      const d = formatInitialDoctor(initialDoctor, initialClinic);
      setSelectedDoctor(d);
      setFlowStep("describe");
    }
  }, [initialClinic, initialDoctor, currentOrderType]);

  const handleLockSlotAndCheckout = async () => {
    const docIdToUse = resolvedDoctorId || selectedDoctor?.id;
    if (currentOrderType === "appointment" && (!selectedDate || !selectedTimeSlot || !docIdToUse)) return;

    // Talk to Vet requires at least 1 photo attachment and disclaimer acceptance
    if (currentOrderType !== "appointment") {
      if (attachedImages.length === 0) {
        setError("At least one image is required to continue.");
        return;
      }
      if (!disclaimerAccepted) {
        setError("Please check the agreement box before continuing to payment.");
        return;
      }
    }

    setProcessing(true);
    setError("");

    try {
      if (currentOrderType === "appointment") {
        const res = await fetch(`${API_BASE}/doctors/${docIdToUse}/slots/lock`, {
          method: "POST",
          headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
          body: JSON.stringify({ date: selectedDate, time_slot: selectedTimeSlot })
        });
        if (res.ok) {
          const data = await res.json();
          setLockId(data.lockId || data.data?.lockId || data.lock_id);
        }
      }
      updateFlowStep("checkout");
    } catch (err) {
      updateFlowStep("checkout");
    } finally {
      setProcessing(false);
    }
  };

  const handlePayment = async (chosenMethod = null, phoneOverride = null) => {
    const methodToUse = chosenMethod || paymentPreference || (currentOrderType === "appointment" ? "pay_at_clinic" : "pay_online");
    setPaymentPreference(methodToUse);

    const phoneToUse = (phoneOverride && normalizePhone(phoneOverride)) || effectiveUserMobile;
    if (!phoneToUse) {
      setShowPhoneGate(true);
      return;
    }

    const userId = user.id || user.user_id || authState?.user_id || authState?.userId || 1179;
    const petId = pet?.id || pet?.pet_id || 0;
    const docIdToUse = resolvedDoctorId || selectedDoctor?.id;
    const clinicIdToUse = selectedClinic?.id || selectedDoctor?.clinicId || docIdToUse;
    
    // Live Time-based Price calculation at payment instant
    const livePackagePrice = selectedPackage ? Number(selectedPackage.price ?? selectedPackage.rawPrice ?? 0) : null;
    const isPkg = Boolean(selectedPackage && livePackagePrice && livePackagePrice > 0);
    const liveFee = isPkg
      ? livePackagePrice
      : (currentOrderType === "appointment"
          ? (selectedClinic ? Number(getClinicCurrentPrice(selectedClinic)) : (selectedDoctor ? Number(getClinicCurrentPrice({ doctors: [selectedDoctor], clinic_day_fee: selectedDoctor.doctors_price })) : 499))
          : (selectedDoctor ? getDoctorCurrentPrice(selectedDoctor, isDay) : (selectedClinic ? Number(getClinicCurrentPrice(selectedClinic)) : 499)));
    const liveGst = isPkg ? 0 : Math.round(liveFee * GST_RATE);
    const liveTotal = isPkg ? liveFee : (liveFee + liveGst);

    const packageNote = selectedPackage ? `[Package: ${selectedPackage.title} (₹${liveTotal})]` : "";
    const fullNotes = [packageNote, issueText].filter(Boolean).join(" ");

    const appointmentSubmitPayload = {
      user_id: userId,
      clinic_id: clinicIdToUse,
      doctor_id: docIdToUse,
      pet_id: petId ? Number(petId) : undefined,
      patient_name: displayUserName,
      patient_phone: phoneToUse || "",
      patient_email: user.email || user.user_email || "",
      pet_name: displayPetName,
      appointment_type: "in_clinic",
      appointment_date: selectedDate,
      appointment_time: normalizeAppointmentTimeForApi(selectedTimeSlot),
      date: selectedDate,
      time_slot: selectedTimeSlot,
      base_amount: liveFee,
      gst_amount: liveGst,
      gst_percent: 18,
      gst_enabled: gstInvoiceChecked ? 1 : 0,
      gst_number: gstInvoiceChecked ? gstNumber : "",
      amount: liveTotal,
      amount_paise: liveTotal * 100,
      notes: fullNotes,
      lock_id: lockId,
      ...(selectedPackage ? {
        service_id: selectedPackage.id,
        service_name: selectedPackage.title,
        clinic_service_id: selectedPackage.id
      } : {})
    };

    if (currentOrderType === "appointment" && (!selectedDate || !selectedTimeSlot)) {
      setError("Please select date and time slot first.");
      return;
    }

    const isPayAtClinic = currentOrderType === "appointment" && methodToUse === "pay_at_clinic";
    const clinicOrDocName = selectedClinic?.name || selectedDoctor?.clinicName || selectedDoctor?.name;
    const confirmation = await confirmPaymentStart({
      amount: liveTotal,
      title: isPayAtClinic
        ? "Confirm Clinic Appointment"
        : (currentOrderType === "appointment" ? "Confirm Clinic Appointment" : "Confirm Vet Consultation"),
      text: isPayAtClinic
        ? (clinicOrDocName
            ? `Confirm appointment at ${clinicOrDocName}. Pay ₹${liveTotal} at clinic reception.`
            : `Confirm clinic appointment. Pay ₹${liveTotal} at clinic reception.`)
        : (selectedDoctor?.name
            ? `Pay ₹${liveTotal} (incl. 18% GST) to confirm consultation with ${selectedDoctor.name}.`
            : `Pay ₹${liveTotal} (incl. 18% GST) to confirm your booking.`),
      confirmButtonText: isPayAtClinic ? "Confirm Visit" : "Pay now",
    });

    if (!confirmation || !confirmation.isConfirmed) {
      return;
    }

    setProcessing(true);
    setError("");

    // 1. IN-CLINIC FLOW WITH "PAY AT CLINIC"
    if (currentOrderType === "appointment" && methodToUse === "pay_at_clinic") {
      try {
        let submitRes = await fetch(`${API_BASE}/create-appointment-in-clinic-without-payment`, {
          method: "POST",
          headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
          body: JSON.stringify({ ...appointmentSubmitPayload, payment_method: "pay_at_clinic" })
        }).catch(() => null);

        if (!submitRes || !submitRes.ok) {
          submitRes = await fetch(`${API_BASE}/appointments/submit`, {
            method: "POST",
            headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
            body: JSON.stringify({ ...appointmentSubmitPayload, payment_method: "pay_at_clinic" })
          });
        }

        if (lockId) unlockCurrentSlot(lockId);
        setBookingSuccessData({
          orderType: "appointment",
          paymentMethod: "pay_at_clinic",
          amount: liveTotal,
          date: selectedDate,
          timeSlot: selectedTimeSlot,
          clinicName: selectedClinic?.name || selectedDoctor?.clinicName || "Veterinary Clinic",
          doctorName: selectedDoctor?.name || "Veterinary Doctor",
          petName: displayPetName,
        });
        setSuccess(true);
      } catch (err) {
        setError("Booking failed. Please try again.");
      } finally {
        setProcessing(false);
      }
      return;
    }

    // 2. ONLINE PAYMENT FLOW (Razorpay Gateway for Video Consult or Online In-Clinic)
    try {
      const orderPayload = {
        amount: liveTotal,
        order_type: currentOrderType || "video_consult",
        user_id: userId,
        doctor_id: docIdToUse,
        clinic_id: clinicIdToUse,
        pet_id: petId,
        gst_enabled: gstInvoiceChecked ? 1 : 0,
        gst_amount: liveGst,
        base_amount: liveFee,
        gst_number: gstInvoiceChecked ? gstNumber : "",
        package_name: selectedPackage?.title || undefined,
        package_id: selectedPackage?.id || undefined,
        package_key: selectedPackage?.key || undefined,
      };

      const orderRes = await fetch(`${API_BASE}/create-order`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify(orderPayload)
      });
      const orderData = await orderRes.json();
      if (!orderRes.ok) throw new Error(orderData.message || "Failed to create order");
      
      const razorpayKey = orderData?.key || orderData?.data?.key;
      const orderId = orderData?.order?.id || orderData?.order_id || orderData?.data?.order_id;
      
      const isLoaded = await loadRazorpayScript();
      if (!isLoaded) throw new Error("Could not load payment gateway.");

      const paymentResult = await new Promise((resolve, reject) => {
        const rzp = new window.Razorpay({
          key: razorpayKey,
          amount: liveTotal * 100,
          currency: "INR",
          name: "SnoutIQ",
          description: `${selectedPackage ? selectedPackage.title : (currentOrderType === "appointment" ? "Clinic Visit" : "Video Consult")} with ${selectedDoctor?.name || selectedClinic?.name || "Doctor"}`,
          order_id: orderId,
          prefill: { name: user.name || user.owner_name, contact: phoneToUse || user.mobile || user.phone },
          theme: { color: "#309BD8" },
          modal: {
            ondismiss: () => {
              reject(new Error("Payment cancelled by user"));
            },
          },
          handler: (response) => resolve(response),
        });
        rzp.on("payment.failed", (response) => {
          reject(new Error(response?.error?.description || "Payment failed"));
        });
        rzp.open();
      });

      const verifyRes = await fetch(`${API_BASE}/rzp/verify`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify({
          ...paymentResult,
          user_id: userId,
          clinic_id: clinicIdToUse,
          doctor_id: docIdToUse,
          pet_id: petId,
          order_type: currentOrderType || "video_consult",
          date: selectedDate,
          time_slot: selectedTimeSlot,
          summary: fullNotes,
          package_name: selectedPackage?.title || undefined,
          package_id: selectedPackage?.id || undefined,
        })
      });
      const verifyData = await verifyRes.json().catch(() => null);
      if (!verifyRes.ok || verifyData?.success === false) {
        throw new Error(verifyData?.message || verifyData?.error || "Payment verification failed");
      }

      if (currentOrderType === "appointment") {
        const appointmentRes = await fetch(`${API_BASE}/appointments/submit`, {
          method: "POST",
          headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
          body: JSON.stringify({ ...appointmentSubmitPayload, ...paymentResult, payment_method: "razorpay" })
        });
        const appointmentData = await appointmentRes.json().catch(() => null);
        if (!appointmentRes.ok || appointmentData?.success === false) {
          throw new Error(appointmentData?.message || appointmentData?.error || "Appointment confirmation failed");
        }
      }

      if (lockId) unlockCurrentSlot(lockId);
      setBookingSuccessData({
        orderType: currentOrderType || "video_consult",
        paymentMethod: "online",
        amount: liveTotal,
        date: selectedDate,
        timeSlot: selectedTimeSlot,
        clinicName: selectedClinic?.name || selectedDoctor?.clinicName || "Veterinary Clinic",
        doctorName: selectedDoctor?.name || "Veterinary Doctor",
        petName: displayPetName,
      });
      setSuccess(true);
    } catch (err) {
      console.error("Payment error", err);
      const isCancelled = err?.message?.toLowerCase().includes("cancelled") || err?.message?.toLowerCase().includes("dismiss");
      if (!isCancelled) {
        setError(err.message || "Payment failed");
      } else {
        setError("");
      }
    } finally {
      setProcessing(false);
    }
  };

  const renderDoctorCard = (doc, isTrusted = false) => {
    const isOnline = doc.status === "available" || doc.available;
    const displayPrice = getDoctorCurrentPrice(doc, isDay);

    return (
      <div key={doc.id || doc.doctor_id} className={`bg-white border rounded-2xl p-3 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-2 relative ${isTrusted ? 'border-emerald-300 ring-1 ring-emerald-400/40 bg-emerald-50/10' : 'border-slate-200/90'}`}>
        <div className="flex items-start gap-2.5">
          {/* Left Doctor Avatar */}
          <div className="relative w-14 h-14 rounded-2xl bg-[#e0f2fe] flex-shrink-0 border border-slate-200/80">
            {doc.image ? (
              <img src={doc.image} alt={doc.name} className="w-full h-full object-cover rounded-2xl" />
            ) : (
              <div className="w-full h-full bg-[#081037] text-white font-extrabold flex items-center justify-center text-xs rounded-2xl">
                DR
              </div>
            )}
            {isOnline && (
              <span className="absolute bottom-0 right-0 w-3 h-3 bg-[#00c853] rounded-full border-2 border-white" title="Online now" />
            )}
          </div>

          {/* Right Details */}
          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between gap-1 flex-wrap">
              <div>
                <div className="flex items-center gap-1.5">
                  <h3 className="font-bold text-[#081037] text-xs leading-tight truncate">{doc.name}</h3>
                  {isTrusted && (
                    <span className="bg-emerald-100 text-emerald-800 text-[9px] font-extrabold px-1.5 py-0.2 rounded-full border border-emerald-200">
                      Trusted
                    </span>
                  )}
                </div>
                
                {/* Experience & Distance Badges */}
                <div className="flex items-center gap-1 mt-0.5 flex-wrap">
                  <span className="text-[10px] font-semibold text-[#309BD8] bg-[#f0f9ff] border border-[#bae6fd] px-1.5 py-0.5 rounded-md inline-block">
                    {doc.degree || "BVSc"} · {doc.experience || 5} yrs exp
                  </span>
                  {doc.distance_km != null && !isNaN(Number(doc.distance_km)) && (
                    <span className="text-[10px] font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded-md inline-flex items-center gap-1">
                      <MapPin size={10} className="text-slate-500 shrink-0" />
                      <span>{Number(doc.distance_km).toFixed(1)} km</span>
                    </span>
                  )}
                </div>
              </div>
              
              {/* Amber Google Rating Badge */}
              <span className="bg-amber-50 text-amber-900 font-bold text-[10px] px-1.5 py-0.5 rounded-md border border-amber-200/70 flex items-center gap-1 flex-shrink-0">
                <Star size={10} className="fill-amber-400 text-amber-500 shrink-0" />
                <span>{doc.googleRating || 5.0}</span>
                <span className="text-amber-700 font-medium">({doc.googleReviewCount || 50})</span>
              </span>
            </div>

            {/* Specialization Line */}
            <p className="text-[10px] text-slate-500 line-clamp-1 mt-0.5">
              {doc.specialization}
            </p>

            {/* Online Status Line */}
            <p className="text-[10px] font-medium text-emerald-600 mt-0.5 flex items-center gap-1">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
              <span>{isOnline ? "Online now" : "Available"} - Connects in {doc.responseTimeDay || "0 To 15 Mins"}</span>
            </p>
          </div>
        </div>

        {/* Bottom Row */}
        <div className="flex items-center justify-between border-t border-slate-100 pt-2">
          <div className="flex items-center gap-1.5">
            <div className="text-[#081037] font-extrabold text-xs">
              ₹{displayPrice}<span className="text-[10px] font-normal text-slate-400">/Consult</span>
            </div>
            <span className={`text-[9px] font-bold px-1.5 py-0.2 rounded ${isDay ? 'bg-amber-50 text-amber-800 border border-amber-200/60' : 'bg-indigo-50 text-indigo-800 border border-indigo-200/60'}`}>
              {isDay ? "Day" : "Night"}
            </span>
          </div>

          <div className="flex items-center gap-1.5">
            <button
              onClick={() => handleViewProfileClick(doc)}
              className="px-3 py-1 border border-[#bae6fd] text-[#309BD8] hover:bg-[#f0f9ff] text-[11px] font-bold rounded-full transition-all cursor-pointer"
            >
              View Profile
            </button>
            <button
              onClick={() => handleBookNowClick(doc)}
              className="px-3.5 py-1 bg-[#309BD8] hover:bg-[#2887bc] text-white text-[11px] font-bold rounded-full transition-all shadow-xs cursor-pointer"
            >
              Talk to Vet
            </button>
          </div>
        </div>
      </div>
    );
  };

  const renderClinicCard = (clinic, isTrustedClinic = false) => {
    const feeVal = getClinicCurrentPrice(clinic);
    const imgUrl = resolveClinicImage(clinic);
    const doctorsCount = clinic.doctors_count || (Array.isArray(clinic.doctors) ? clinic.doctors.length : 1);
    const isTrusted = isTrustedClinic || (clinic.google_rating || 5.0) >= 4.5;

    return (
      <div key={clinic.id || clinic.name} className={`bg-white border rounded-2xl p-3 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-2 relative ${isTrustedClinic ? 'border-emerald-300 ring-1 ring-emerald-400/40 bg-emerald-50/10' : 'border-slate-200/90'}`}>
        <div className="flex items-start gap-2.5">
          {/* Left Clinic Image / Thumbnail */}
          <div className="relative w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-slate-100 flex-shrink-0 border border-slate-200/80 overflow-hidden">
            <img 
              src={imgUrl} 
              alt={clinic.name} 
              onError={(e) => {
                e.currentTarget.onerror = null;
                e.currentTarget.src = DEFAULT_CLINIC_FALLBACK;
              }}
              className="w-full h-full object-cover rounded-2xl" 
            />
            {isTrusted && (
              <span className="absolute bottom-1 right-1 bg-emerald-500 text-white text-[8px] font-extrabold px-1 py-0.5 rounded shadow-xs flex items-center gap-0.5">
                <Star size={8} className="fill-white text-white" />
              </span>
            )}
          </div>

          {/* Right Details */}
          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between gap-1 flex-wrap">
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-1.5">
                  <h3 className="font-bold text-[#081037] text-xs leading-tight line-clamp-1">{clinic.name}</h3>
                  {isTrustedClinic && (
                    <span className="bg-emerald-100 text-emerald-800 text-[9px] font-extrabold px-1.5 py-0.2 rounded-full border border-emerald-200 shrink-0">
                      Your Clinic
                    </span>
                  )}
                </div>

                <p className="text-[10px] text-slate-500 mt-0.5 line-clamp-1 flex items-center gap-1">
                  <MapPin size={10} className="text-slate-400 shrink-0 inline" />
                  <span>{clinic.address || clinic.city || "Gurugram"}{clinic.pincode ? `, ${clinic.pincode}` : ""}</span>
                </p>

                {/* Badges: Rating, Vets, Distance */}
                <div className="flex items-center gap-1 mt-1 text-[10px] flex-wrap">
                  <span className="bg-amber-50 text-amber-900 border border-amber-200/80 font-bold px-1.5 py-0.2 rounded-md flex items-center gap-1 shrink-0">
                    <Star size={10} className="fill-amber-400 text-amber-500 shrink-0" />
                    <span>{clinic.google_rating || 5.0}</span>
                    <span className="text-amber-700 font-medium">({clinic.google_user_ratings_total || 50})</span>
                  </span>
                  <span className="bg-[#f0f9ff] text-[#309BD8] font-semibold px-1.5 py-0.2 rounded-md shrink-0 border border-[#bae6fd] flex items-center gap-1">
                    <Users size={10} className="text-[#309BD8] shrink-0" />
                    <span>{doctorsCount} Vet{doctorsCount > 1 ? "s" : ""}</span>
                  </span>
                  {clinic.distance_km != null && !isNaN(Number(clinic.distance_km)) && (
                    <span className="bg-slate-100 text-slate-700 font-semibold px-1.5 py-0.2 rounded-md border border-slate-200/80 inline-flex items-center gap-1 shrink-0">
                      <MapPin size={10} className="text-slate-500 shrink-0" />
                      <span>{Number(clinic.distance_km).toFixed(1)} km</span>
                    </span>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Row */}
        <div className="flex items-center justify-between border-t border-slate-100 pt-2">
          <div className="text-emerald-700 font-bold text-xs flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
            <span>Pay at Clinic · ₹{feeVal}</span>
          </div>

          <button 
            onClick={() => handleSelectClinic(clinic)}
            className="bg-[#309BD8] hover:bg-[#2887bc] text-white font-bold text-[11px] px-4 py-1.5 rounded-full transition-all shadow-xs flex items-center gap-1 shrink-0 cursor-pointer"
          >
            Book Visit →
          </button>
        </div>
      </div>
    );
  };

  // Reusable Search + Filter Bar (positioned below Other Available Vets / Other Clinics)
  const isAnyFilterActive = selectedExpFilter !== "any" || selectedSpecialtyFilter !== "all" || selectedPriceFilter !== "any";

  const renderSearchAndFilterBar = () => (
    <div className="flex items-center gap-2 pt-1 pb-1">
      <div className="relative flex-1">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
        <input 
          type="text" 
          placeholder={currentOrderType === "appointment" ? "Search clinics by name, city, doctor..." : "Search doctors by name, specialty, city..."}
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-8 text-[11px] outline-none focus:border-[#309BD8] transition-colors shadow-xs"
        />
        {searchQuery && (
          <button 
            type="button"
            onClick={() => setSearchQuery("")}
            className="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600 rounded-full cursor-pointer"
            title="Clear search"
          >
            <X className="w-3.5 h-3.5" />
          </button>
        )}
      </div>

      {/* GPS Location Auto-detect / Refresh Button */}
      <button
        type="button"
        onClick={handleRequestLocation}
        disabled={requestingLocation}
        title={userCoords ? "Location active (click to update)" : "Use current GPS location"}
        className={`px-2.5 py-2 rounded-xl border text-[11px] font-bold flex items-center gap-1.5 transition-all shadow-xs cursor-pointer shrink-0 disabled:opacity-60 ${
          userCoords
            ? "bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100"
            : "bg-white text-slate-700 border-slate-200 hover:bg-slate-50"
        }`}
      >
        {requestingLocation ? (
          <Loader2 className="w-3.5 h-3.5 animate-spin text-[#309BD8]" />
        ) : (
          <Navigation className={`w-3.5 h-3.5 ${userCoords ? "text-emerald-600 fill-emerald-600" : "text-[#309BD8]"}`} />
        )}
        <span className="hidden sm:inline">
          {requestingLocation ? "Locating..." : userCoords ? "Nearby" : "Use Location"}
        </span>
      </button>

      {currentOrderType !== "appointment" && (
        <button
          type="button"
          onClick={() => setShowFilterModal(true)}
          className={`px-3 py-2 rounded-xl border text-[11px] font-bold flex items-center gap-1.5 transition-all shadow-xs cursor-pointer shrink-0 ${
            isAnyFilterActive
              ? "bg-[#309BD8] text-white border-[#309BD8]" 
              : "bg-white text-slate-700 border-slate-200 hover:bg-slate-50"
          }`}
        >
          <Filter className="w-3.5 h-3.5" />
          <span>
            {selectedExpFilter !== "any" 
              ? `${selectedExpFilter}+ Yrs` 
              : selectedSpecialtyFilter !== "all" 
                ? selectedSpecialtyFilter 
                : selectedPriceFilter !== "any"
                  ? (selectedPriceFilter === "0-500" ? "≤₹500" : selectedPriceFilter === "500-1000" ? "₹500-1k" : ">₹1k")
                  : "Filter"}
          </span>
          {isAnyFilterActive && (
            <span className="w-2 h-2 rounded-full bg-white inline-block"></span>
          )}
        </button>
      )}
    </div>
  );

  return (
    <div className="fixed inset-0 z-[100] flex flex-col bg-slate-100 w-full min-h-screen overflow-hidden animate-">
      
      {/* Full Page Mobile / App Style Top Header Bar */}
      <div className="sticky top-0 z-30 flex items-center justify-between px-3.5 py-2.5 bg-white border-b border-slate-200 shadow-xs">
        <div className="flex items-center gap-2.5">
          <button 
            onClick={handleHeaderBack} 
            className="p-1.5 -ml-1 text-[#081037] hover:text-black bg-slate-100 hover:bg-slate-200 rounded-full transition-colors cursor-pointer"
          >
            <ChevronLeft className="w-4 h-4" />
          </button>
          <div>
            <h1 className="text-sm font-bold text-[#081037] leading-tight">
              {flowStep === "checkout" 
                ? 'Confirm Consultation' 
                : flowStep === "describe" 
                  ? (currentOrderType === "appointment" ? 'Clinic Visit Details' : 'Describe Pet Symptoms')
                  : (currentOrderType === "appointment" ? 'Trusted Veterinary Clinics' : 'Talk to Verified Vets')}
            </h1>
            <p className="text-[10px] text-slate-500 font-medium">
              {currentOrderType === "appointment" ? "In-clinic appointment booking" : "Online video consultation"}
            </p>
          </div>
        </div>

        <button onClick={handleModalClose} className="p-1.5 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors text-slate-600 cursor-pointer">
          <X className="w-4 h-4" />
        </button>
      </div>

      {/* Main Content View Body */}
      <div ref={scrollContainerRef} className="flex-1 overflow-y-auto bg-slate-50 w-full">
        <div className="p-3 md:p-4 max-w-4xl mx-auto w-full space-y-3">
        
        {/* STEP 0: List View */}
        {flowStep === "list" && (
          <div className="space-y-3">
            
            {/* Geolocation Permission / Location Status Card */}
            {!userCoords ? (
              <div className="bg-[#f0f9ff] border border-[#bae6fd] rounded-2xl p-2.5 flex items-center justify-between gap-2 shadow-2xs">
                <div className="flex items-center gap-2 min-w-0">
                  <div className="p-1.5 rounded-lg bg-[#e0f2fe] text-[#309BD8] shrink-0">
                    <MapPin className="w-3.5 h-3.5" />
                  </div>
                  <p className="text-[11px] font-medium text-[#081037] truncate">
                    Enable location to sort nearby {currentOrderType === "appointment" ? "clinics" : "vets"} first
                  </p>
                </div>
                <button
                  type="button"
                  onClick={handleRequestLocation}
                  disabled={requestingLocation}
                  className="px-2.5 py-1.5 bg-[#309BD8] hover:bg-[#2887bc] text-white text-[10.5px] font-bold rounded-lg transition-all shrink-0 cursor-pointer disabled:opacity-50 flex items-center gap-1.5 shadow-xs"
                >
                  {requestingLocation ? (
                    <>
                      <Loader2 className="w-3 h-3 animate-spin" />
                      <span>Locating...</span>
                    </>
                  ) : (
                    <>
                      <Navigation className="w-3 h-3 fill-white" />
                      <span>Use Location</span>
                    </>
                  )}
                </button>
              </div>
            ) : (
              <div className="bg-emerald-50/80 border border-emerald-200/90 rounded-2xl p-2.5 flex items-center justify-between gap-2 shadow-2xs">
                <div className="flex items-center gap-2 min-w-0">
                  <div className="p-1 rounded-lg bg-emerald-100 text-emerald-700 shrink-0">
                    <MapPin className="w-3.5 h-3.5" />
                  </div>
                  <p className="text-[11px] font-semibold text-emerald-900 truncate">
                    Location Active · Sorted by nearest {currentOrderType === "appointment" ? "clinics" : "vets"} (km)
                  </p>
                </div>
                <button
                  type="button"
                  onClick={handleRequestLocation}
                  disabled={requestingLocation}
                  className="px-2.5 py-1 bg-white hover:bg-emerald-100 text-emerald-700 border border-emerald-300 text-[10px] font-bold rounded-lg transition-all shrink-0 cursor-pointer disabled:opacity-50 flex items-center gap-1 shadow-2xs"
                >
                  {requestingLocation ? (
                    <>
                      <Loader2 className="w-2.5 h-2.5 animate-spin text-emerald-600" />
                      <span>Updating...</span>
                    </>
                  ) : (
                    <>
                      <RefreshCw className="w-2.5 h-2.5 text-emerald-600" />
                      <span>Update</span>
                    </>
                  )}
                </button>
              </div>
            )}

            {/* AI Assessment Alert Card (Only in Talk to Vet Flow) */}
            {currentOrderType !== "appointment" && (
              <div className="bg-[#f0f9ff] border border-[#bae6fd] rounded-2xl p-3 flex items-center gap-2.5 shadow-2xs">
                <div className="p-1 rounded-lg bg-[#e0f2fe] text-[#309BD8] flex-shrink-0">
                  <Sparkles className="w-4 h-4 fill-[#309BD8] text-[#309BD8]" />
                </div>
                <p className="text-xs font-semibold text-[#081037] leading-snug">
                  <span className="font-bold">{displayPetName}</span>&apos;s AI assessment is ready to share with your veterinarian.
                </p>
              </div>
            )}

            {/* Content List: 2-COLUMN GRID */}
            {loading ? (
              <div className="py-12 text-center text-xs text-slate-500 flex flex-col items-center justify-center gap-2">
                <Loader2 className="w-4 h-4 animate-spin text-[#309BD8]" />
                <span>Loading verified vets & clinics...</span>
              </div>
            ) : currentOrderType === "appointment" ? (
              /* PART 2: CLINICS LIST WITH LAST-CLINIC AND LAZY LOADING */
              hasLastClinic ? (
                <div className="space-y-3">
                  {/* ⭐ Your Trusted Clinic Section */}
                  <div className="space-y-2">
                    <div className="flex items-center justify-between px-0.5">
                      <h3 className="text-xs font-extrabold text-[#081037] flex items-center gap-1.5">
                        <span>⭐ Your Trusted Clinic</span>
                        <span className="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">
                          Recommended for {displayPetName}
                        </span>
                      </h3>
                    </div>

                    {filteredLastVetClinics.length === 0 ? (
                      <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                        No trusted clinics match your search.
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {filteredLastVetClinics.map(clinic => renderClinicCard(clinic, true))}
                      </div>
                    )}
                  </div>

                  {/* View More / Show Less Clinics Toggle Button */}
                  <div className="pt-1 text-center">
                    <button
                      onClick={handleToggleClinics}
                      disabled={allClinicsLoading}
                      className="w-full py-2.5 bg-[#f0f9ff] hover:bg-[#e0f2fe] text-[#309BD8] font-bold text-xs rounded-xl border border-[#bae6fd] transition-all flex items-center justify-center gap-2 shadow-xs cursor-pointer"
                    >
                      {allClinicsLoading ? (
                        <>
                          <Loader2 className="w-3.5 h-3.5 animate-spin text-[#309BD8]" />
                          <span>Loading more clinics for {displayPetName}...</span>
                        </>
                      ) : showAllClinics ? (
                        <span>Show less ↑</span>
                      ) : (
                        <span>View more clinics for {displayPetName} ↓</span>
                      )}
                    </button>
                  </div>

                  {/* Other Available Clinics Section */}
                  {showAllClinics && (
                    <div className="space-y-2 pt-2 border-t border-slate-200/80">
                      <h3 className="text-xs font-bold text-[#081037] px-0.5">Other Nearby Clinics</h3>
                      {renderSearchAndFilterBar()}
                      {allClinicsLoading ? (
                        <div className="py-8 text-center text-xs text-slate-500 flex flex-col items-center justify-center gap-2">
                          <Loader2 className="w-4 h-4 animate-spin text-[#309BD8]" />
                          <span>Loading available clinics...</span>
                        </div>
                      ) : filteredOtherClinics.length === 0 ? (
                        <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                          No other clinics found matching your search.
                        </div>
                      ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          {filteredOtherClinics.map(clinic => renderClinicCard(clinic, false))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              ) : (
                /* CASE B: hasLastClinic === false -> DIRECT FULL CLINIC LIST */
                <div className="space-y-3">
                  {renderSearchAndFilterBar()}
                  {filteredOtherClinics.length === 0 ? (
                    <div className="p-5 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">No clinics found matching your search.</div>
                  ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                      {filteredOtherClinics.map(clinic => renderClinicCard(clinic, false))}
                    </div>
                  )}
                </div>
              )
            ) : (
              /* PART 1: DOCTORS LIST WITH LAST-VET AND LAZY LOADING */
              hasLastVet ? (
                <div className="space-y-3">
                  {/* ⭐ Your Trusted Vet Section */}
                  <div className="space-y-2">
                    <div className="flex items-center justify-between px-0.5">
                      <h3 className="text-xs font-extrabold text-[#081037] flex items-center gap-1.5">
                        <span>⭐ Your Trusted Vet</span>
                        <span className="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">
                          Recommended for {displayPetName}
                        </span>
                      </h3>
                    </div>

                    {filteredLastVetDoctors.length === 0 ? (
                      <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                        No trusted vets match your search query.
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                        {filteredLastVetDoctors.map(doc => renderDoctorCard(doc, true))}
                      </div>
                    )}
                  </div>

                  {/* View More / Show Less Doctors Toggle Button */}
                  <div className="pt-1 text-center">
                    <button
                      onClick={handleToggleVets}
                      disabled={allVetsLoading}
                      className="w-full py-2.5 bg-[#f0f9ff] hover:bg-[#e0f2fe] text-[#309BD8] font-bold text-xs rounded-xl border border-[#bae6fd] transition-all flex items-center justify-center gap-2 shadow-xs cursor-pointer"
                    >
                      {allVetsLoading ? (
                        <>
                          <Loader2 className="w-3.5 h-3.5 animate-spin text-[#309BD8]" />
                          <span>Loading more doctors for {displayPetName}...</span>
                        </>
                      ) : showAllVets ? (
                        <span>Show less ↑</span>
                      ) : (
                        <span>View more verified vets for {displayPetName} ↓</span>
                      )}
                    </button>
                  </div>

                  {/* Other Available Vets Section */}
                  {showAllVets && (
                    <div className="space-y-2 pt-2 border-t border-slate-200/80">
                      <div className="flex items-center justify-between px-0.5">
                        <h3 className="text-xs font-bold text-[#081037]">Other Available Vets</h3>
                      </div>
                      {renderSearchAndFilterBar()}
                      {allVetsLoading ? (
                        <div className="py-8 text-center text-xs text-slate-500 flex flex-col items-center justify-center gap-2">
                          <Loader2 className="w-4 h-4 animate-spin text-[#309BD8]" />
                          <span>Loading available vets...</span>
                        </div>
                      ) : filteredOtherDoctors.length === 0 ? (
                        <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                          No other doctors found matching filters.
                        </div>
                      ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                          {filteredOtherDoctors.map(doc => renderDoctorCard(doc, false))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              ) : (
                /* CASE B: hasLastVet === false -> DIRECT NORMAL DOCTOR LIST */
                <div className="space-y-3">
                  {renderSearchAndFilterBar()}
                  {filteredOtherDoctors.length === 0 ? (
                    <div className="p-5 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">No doctors found matching filters.</div>
                  ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                      {filteredOtherDoctors.map(doc => renderDoctorCard(doc, false))}
                    </div>
                  )}
                </div>
              )
            )}
          </div>
        )}

        {/* STEP 1: Describe Issue / Date Slots */}
        {flowStep === "describe" && (
          <div className="space-y-2.5 max-w-xl mx-auto">
            
            {/* Header info */}
            {currentOrderType === "appointment" ? (
              <div className="bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between shadow-xs gap-3">
                <div className="flex items-center gap-3">
                  <img 
                    src={resolveClinicImage(selectedClinic)} 
                    alt={selectedClinic?.name} 
                    onError={(e) => {
                      e.currentTarget.onerror = null;
                      e.currentTarget.src = clinicDefaultImg;
                    }}
                    className="w-12 h-12 rounded-xl object-cover border border-slate-200 flex-shrink-0" 
                  />
                  <div>
                    <h3 className="font-bold text-[#081037] text-xs">{selectedClinic?.name || selectedDoctor?.clinicName || "Clinic"}</h3>
                    <p className="text-[11px] text-slate-500 mt-0.5">{selectedClinic?.city || "Gurugram"}{selectedClinic?.pincode ? `, ${selectedClinic.pincode}` : ""}</p>
                    <div className="flex items-center gap-1.5 mt-1 flex-wrap">
                      <span className="bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px] px-2 py-0.5 rounded-md flex items-center gap-1">
                        <Star size={10} className="fill-amber-500 text-amber-500 shrink-0" />
                        <span>{selectedClinic?.google_rating || 5.0} ({selectedClinic?.google_user_ratings_total || 78})</span>
                      </span>
                      {selectedClinic?.distance_km != null && !isNaN(Number(selectedClinic.distance_km)) && (
                        <span className="bg-slate-100 text-slate-700 border border-slate-200 font-bold text-[10px] px-2 py-0.5 rounded-md inline-flex items-center gap-1">
                          <MapPin size={10} className="text-slate-500 shrink-0" />
                          <span>{Number(selectedClinic.distance_km).toFixed(1)} km</span>
                        </span>
                      )}
                    </div>
                  </div>
                </div>
                <div className="w-10 h-10 bg-[#f0f9ff] border border-[#bae6fd] rounded-xl flex items-center justify-center text-[#309BD8] flex-shrink-0">
                  <Building2 size={20} className="text-[#309BD8]" />
                </div>
              </div>
            ) : (
              <div className="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3 shadow-xs">
                {selectedDoctor?.image ? (
                  <img src={selectedDoctor.image} alt={selectedDoctor.name} className="w-12 h-12 rounded-xl object-cover border border-slate-200 flex-shrink-0" />
                ) : (
                  <div className="w-12 h-12 rounded-xl bg-[#081037] text-white font-bold flex items-center justify-center text-sm flex-shrink-0">
                    {selectedDoctor?.name?.charAt(0)}
                  </div>
                )}
                <div>
                  <h3 className="font-bold text-[#081037] text-xs">{selectedDoctor?.name}</h3>
                  <div className="flex items-center gap-1.5 mt-0.5 flex-wrap">
                    <p className="text-[11px] font-semibold text-[#309BD8]">{selectedDoctor?.degree} · {selectedDoctor?.experience} Yrs Exp</p>
                    {selectedDoctor?.distance_km != null && !isNaN(Number(selectedDoctor.distance_km)) && (
                      <span className="text-[10px] font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded-md inline-flex items-center gap-1">
                        <MapPin size={10} className="text-slate-500 shrink-0" />
                        <span>{Number(selectedDoctor.distance_km).toFixed(1)} km</span>
                      </span>
                    )}
                  </div>
                  <p className="text-[10px] text-slate-500 mt-0.5">{selectedDoctor?.specialization}</p>
                </div>
              </div>
            )}

            {/* Symptom Input Textarea Card */}
            <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-2 shadow-xs">
              <div className="flex items-center justify-between">
                <label className="block text-[11px] font-bold text-[#081037]">What issue is your pet facing?</label>
                <span className="text-[10px] text-slate-400 font-medium">Keep it short and clear</span>
              </div>

              <textarea
                value={issueText}
                onChange={(e) => setIssueText(e.target.value)}
                maxLength={500}
                placeholder="Example: Vomiting since morning, not eating, low energy..."
                className="w-full h-20 bg-slate-50/40 border border-slate-200 rounded-xl p-2.5 text-[11px] outline-none focus:border-[#309BD8] shadow-xs resize-none"
              />
              <p className="text-[10px] text-slate-400 text-left">{issueText.length}/500</p>
            </div>

            {/* Photo Upload Attachment Card */}
            <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-2 shadow-xs">
              <div className="flex items-center justify-between gap-2">
                <div>
                  <h4 className="text-[11px] font-bold text-[#081037]">Add a photo</h4>
                  <p className="text-[10px] text-slate-400">Upload a clear image of the issue.</p>
                </div>
                
                <label className="cursor-pointer bg-[#309BD8] hover:bg-[#2887bc] text-white font-bold text-[11px] px-3 py-1.5 rounded-lg shadow-xs transition-all inline-flex items-center gap-1.5 flex-shrink-0">
                  <Camera size={13} className="shrink-0" />
                  <span>Add Photo</span>
                  <input 
                    type="file" 
                    accept="image/*" 
                    multiple
                    onChange={handleImageUpload} 
                    className="hidden" 
                  />
                </label>
              </div>

              <p className="text-[10px] text-slate-400">
                {currentOrderType !== "appointment" ? "At least one image is required to continue." : "Photo attachment is optional for clinic visit."}
              </p>

              {attachedImages.length > 0 && (
                <div className="flex gap-2 overflow-x-auto no-scrollbar pt-1">
                  {attachedImages.map(img => (
                    <div key={img.id} className="relative w-12 h-12 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0 group">
                      <img src={img.src} alt="Preview" className="w-full h-full object-cover" />
                      <button
                        type="button"
                        onClick={() => removeAttachedImage(img.id)}
                        className="absolute top-0.5 right-0.5 bg-black/70 hover:bg-black text-white p-0.5 rounded-full transition-colors cursor-pointer"
                      >
                        <X size={10} />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Red Light Guidance Alert Box (Only for Talk to Vet) */}
            {currentOrderType !== "appointment" && (
              <div className="bg-red-50/80 border border-red-100 rounded-xl p-3 space-y-1.5 text-[11px] text-red-800 shadow-xs">
                <div className="flex items-start gap-2">
                  <span className="text-red-500 font-bold text-xs leading-none mt-0.5">•</span>
                  <span>Share clear symptoms and at least one photo for faster review.</span>
                </div>
                <div className="flex items-start gap-2">
                  <span className="text-red-500 font-bold text-xs leading-none mt-0.5">•</span>
                  <span>Online consultation is for guidance. Emergency cases may still need a clinic visit.</span>
                </div>
                <div className="flex items-start gap-2">
                  <span className="text-red-500 font-bold text-xs leading-none mt-0.5">•</span>
                  <span>Consultation starts after payment confirmation and doctor assignment.</span>
                </div>
              </div>
            )}

            {/* Agreement Checkbox Container (Only for Talk to Vet) */}
            {currentOrderType !== "appointment" && (
              <label className="flex items-start gap-2.5 bg-white border border-slate-200 rounded-xl p-3 cursor-pointer hover:border-slate-300 transition-all shadow-xs">
                <input 
                  type="checkbox"
                  checked={disclaimerAccepted}
                  onChange={(e) => setDisclaimerAccepted(e.target.checked)}
                  className="w-4 h-4 rounded border-slate-300 text-[#309BD8] focus:ring-[#309BD8] mt-0.5 flex-shrink-0"
                />
                <span className="text-[11px] text-slate-700 font-medium leading-tight">
                  I understand online consultation is for guidance. Emergency cases may need a clinic visit.
                </span>
              </label>
            )}

            {/* SERVICE / PACKAGE SELECTION (In-Clinic Flow) */}
            {currentOrderType === "appointment" && (clinicPackages.length > 0 || selectedPackage || loadingPackages) && (
              <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-2.5 shadow-xs">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-1.5">
                    <Sparkles className="w-3.5 h-3.5 text-[#309BD8]" />
                    <span className="text-[11px] font-bold text-[#081037]">Select Service or Package</span>
                  </div>
                  {loadingPackages ? (
                    <span className="text-[10px] text-slate-400 flex items-center gap-1">
                      <Loader2 className="w-3 h-3 animate-spin text-[#309BD8]" /> Fetching packages...
                    </span>
                  ) : (
                    <span className="text-[10px] text-slate-400 font-medium">
                      {clinicPackages.length} Packages Available
                    </span>
                  )}
                </div>

                {clinicPackages.length > 2 && (
                  <div className="flex items-center gap-1.5 border-b border-slate-100 pb-2">
                    {["all", "Dog", "Cat"].map(t => (
                      <button
                        key={t}
                        type="button"
                        onClick={() => setPackagePetTab(t)}
                        className={`text-[10px] px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer ${
                          packagePetTab === t
                            ? "bg-[#081037] text-white shadow-xs"
                            : "bg-slate-100 text-slate-600 hover:bg-slate-200"
                        }`}
                      >
                        {t === "all" ? `All (${clinicPackages.length})` : t === "Dog" ? `Dogs (${clinicPackages.filter(p => p.petType === "Dog").length})` : `Cats (${clinicPackages.filter(p => p.petType === "Cat").length})`}
                      </button>
                    ))}
                  </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                  {/* Standard Consultation Option */}
                  <button
                    type="button"
                    onClick={() => setSelectedPackage(null)}
                    className={`text-left p-2.5 rounded-xl border transition-all cursor-pointer ${
                      !selectedPackage
                        ? "border-[#309BD8] bg-[#f0f9ff] ring-1 ring-[#309BD8] shadow-xs"
                        : "border-slate-200 bg-white hover:border-slate-300"
                    }`}
                  >
                    <div className="flex items-start justify-between gap-1">
                      <div>
                        <span className="text-[9px] font-bold uppercase tracking-wider text-[#309BD8] bg-[#e0f2fe] px-1.5 py-0.5 rounded">
                          Standard
                        </span>
                        <p className="text-xs font-bold text-[#081037] mt-1">General Consultation</p>
                        <p className="text-[10px] text-slate-500 mt-0.5">Doctor physical examination & prescription</p>
                      </div>
                      <div className="text-right shrink-0">
                        <p className="text-xs font-bold text-emerald-700">Pay at Clinic</p>
                        <p className="text-[9px] text-slate-400">At Reception</p>
                      </div>
                    </div>
                  </button>

                  {/* Package Options */}
                  {visibleClinicPackages.map((pkg) => {
                    const isSelected = selectedPackage?.id === pkg.id || selectedPackage?.key === pkg.key;
                    return (
                      <button
                        key={pkg.id}
                        type="button"
                        onClick={() => setSelectedPackage(pkg)}
                        className={`text-left p-2.5 rounded-xl border transition-all cursor-pointer ${
                          isSelected
                            ? "border-[#309BD8] bg-[#f0f9ff] ring-1 ring-[#309BD8] shadow-xs"
                            : "border-slate-200 bg-white hover:border-slate-300"
                        }`}
                      >
                        <div className="flex items-start justify-between gap-1">
                          <div className="min-w-0 pr-1">
                            <span className={`text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded ${
                              pkg.category === "Vaccination" ? "bg-emerald-100 text-emerald-800" : "bg-purple-100 text-purple-800"
                            }`}>
                              {pkg.badge || pkg.category}
                            </span>
                            <p className="text-xs font-bold text-[#081037] mt-1 truncate">{pkg.title}</p>
                            <p className="text-[10px] text-slate-500 mt-0.5 line-clamp-1">{pkg.description}</p>
                          </div>
                          <div className="text-right shrink-0">
                            <p className="text-xs font-black text-[#309BD8]">{pkg.formattedPrice}</p>
                            <p className="text-[9px] text-emerald-600 font-semibold">All-Inclusive</p>
                          </div>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>
            )}

            {/* APPOINTMENT DATE/SLOTS */}
            {currentOrderType === "appointment" && (
              <>
                {/* STATIONED MEDICAL STAFF */}
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">STATIONED MEDICAL STAFF</span>
                  <div className="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                    {(selectedClinic?.doctors && selectedClinic.doctors.length > 0 ? selectedClinic.doctors : [selectedDoctor]).map(doc => {
                      const dName = doc.doctor_name || doc.name || "Doctor";
                      const isSel = selectedDoctor?.id === doc.id || selectedDoctor?.id === doc.doctor_id;
                      const avatarUrl = normalizeImage(doc.doctor_blob_url || doc.doctor_image_blob_url || doc.doctor_image_url || doc.image);

                      return (
                        <div 
                          key={doc.id || doc.doctor_id} 
                          onClick={() => {
                            const formattedDoc = {
                              id: doc.id || doc.doctor_id,
                              name: dName,
                              degree: doc.degree || "BVSc",
                              experience: doc.years_of_experience || 5,
                              clinicId: selectedClinic?.id,
                              clinicName: selectedClinic?.name
                            };
                            setSelectedDoctor(formattedDoc);
                            fetchDateAvailabilityAndSlots(selectedDate || getUpcomingDates(7)[0].dateStr, formattedDoc, selectedClinic);
                          }}
                          className={`p-2 rounded-xl border text-center cursor-pointer transition-all flex flex-col items-center justify-center min-w-[96px] ${
                            isSel ? "border-[#309BD8] bg-[#f0f9ff] shadow-xs" : "border-slate-200 bg-white hover:border-slate-300"
                          }`}
                        >
                          {avatarUrl ? (
                            <img src={avatarUrl} alt={dName} className="w-10 h-10 rounded-full object-cover mb-1 border border-slate-200" />
                          ) : (
                            <div className="w-10 h-10 rounded-full bg-[#081037] text-white font-bold text-xs flex items-center justify-center mb-1">
                              {dName.charAt(0)}
                            </div>
                          )}
                          <p className="text-[11px] font-bold text-[#081037] leading-tight truncate max-w-[85px]">{dName}</p>
                          <p className="text-[9px] text-slate-400 mt-0.5">Doctor</p>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* VISIT DATE */}
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">VISIT DATE</span>
                  <div className="flex gap-2 overflow-x-auto no-scrollbar pb-1 snap-x snap-mandatory">
                    {getUpcomingDates(30).slice(0, 5).map(d => {
                      const isSel = selectedDate === d.dateStr;
                      const today = new Date(); today.setHours(0,0,0,0);
                      const dDate = new Date(d.dateStr); dDate.setHours(0,0,0,0);
                      const isPast = dDate < today;
                      return (
                        <button
                          key={d.dateStr}
                          disabled={isPast}
                          onClick={() => !isPast && fetchDateAvailabilityAndSlots(d.dateStr, selectedDoctor, selectedClinic)}
                          className={`flex-shrink-0 snap-start w-14 py-2 px-1.5 rounded-xl border text-center transition-all cursor-pointer ${
                            isPast
                              ? "border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed opacity-50"
                              : isSel
                                ? "border-[#309BD8] bg-[#309BD8] text-white font-bold shadow-xs"
                                : "border-slate-200 bg-white text-slate-700 hover:border-[#bae6fd]"
                          }`}
                        >
                          <p className={`text-[9px] uppercase font-semibold ${isSel ? "text-blue-100" : isPast ? "text-slate-300" : "text-slate-400"}`}>{d.dayName}</p>
                          <p className="text-xs font-bold mt-0.5">{d.dateNum}</p>
                          <p className={`text-[9px] ${isSel ? "text-blue-100" : isPast ? "text-slate-300" : "text-slate-400"}`}>{d.monthName}</p>
                        </button>
                      );
                    })}

                    {/* More Dates Button */}
                    <button
                      onClick={() => {
                        const picker = document.getElementById("hidden-date-picker");
                        if (picker) picker.showPicker ? picker.showPicker() : picker.click();
                      }}
                      className="flex-shrink-0 w-14 py-2 px-1 rounded-xl border border-dashed border-[#bae6fd] bg-[#f0f9ff] text-[#309BD8] text-center hover:bg-[#e0f2fe] transition-all snap-start relative cursor-pointer"
                    >
                      <p className="text-[9px] font-bold leading-tight">More</p>
                      <p className="text-[9px] font-bold">Dates</p>
                      <Calendar size={13} className="mx-auto mt-0.5 text-[#309BD8]" />
                      <input
                        id="hidden-date-picker"
                        type="date"
                        min={new Date().toISOString().split("T")[0]}
                        value={selectedDate && !getUpcomingDates(5).some(d => d.dateStr === selectedDate) ? selectedDate : ""}
                        onChange={(e) => {
                          const val = e.target.value;
                          if (val) fetchDateAvailabilityAndSlots(val, selectedDoctor, selectedClinic);
                        }}
                        className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                      />
                    </button>
                  </div>

                  {/* Show selected date if it's beyond first 5 */}
                  {selectedDate && !getUpcomingDates(5).some(d => d.dateStr === selectedDate) && (
                    <div className="mt-1.5 flex items-center gap-1.5">
                      <span className="text-[10px] text-slate-500">Selected:</span>
                      <span className="text-[10px] font-bold text-[#309BD8] bg-[#f0f9ff] border border-[#bae6fd] px-2 py-0.5 rounded-md">
                        {new Date(selectedDate).toLocaleDateString("en-IN", { weekday: "short", day: "numeric", month: "short" })}
                      </span>
                      <button onClick={() => { setSelectedDate(""); setSelectedTimeSlot(""); setAvailableSlots([]); }} className="text-red-400 hover:text-red-600 cursor-pointer p-0.5" aria-label="Clear date">
                        <X size={11} />
                      </button>
                    </div>
                  )}
                </div>

                {/* AVAILABLE SLOTS */}
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">AVAILABLE SLOTS</span>
                  {loadingSlots ? (
                    <div className="py-4 text-center text-xs text-slate-500 flex items-center justify-center gap-1.5">
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-[#309BD8]" />
                      Loading slots...
                    </div>
                  ) : availableSlots.length === 0 ? (
                    <div className="p-3 text-center bg-white border border-slate-200 rounded-xl text-xs text-slate-500">
                      {dateAvailError || "No active slots available for this date. Please select another date."}
                    </div>
                  ) : (
                    <div className="grid grid-cols-3 gap-1.5 max-h-40 overflow-y-auto pr-1">
                      {availableSlots.map((s, idx) => {
                        const slotLabel = s.label || s.start;
                        const isSel = selectedTimeSlot === slotLabel;
                        return (
                          <button
                            key={idx}
                            onClick={() => setSelectedTimeSlot(slotLabel)}
                            className={`py-1.5 px-2 rounded-lg text-[11px] font-semibold border transition-all cursor-pointer ${
                              isSel ? "border-[#309BD8] bg-[#f0f9ff] text-[#309BD8] font-bold ring-1 ring-[#309BD8]" : "border-slate-200 bg-white text-slate-700 hover:border-slate-300"
                            }`}
                          >
                            {slotLabel}
                          </button>
                        );
                      })}
                    </div>
                  )}
                </div>
              </>
            )}

            {error && (
              <div className="p-2.5 bg-red-50 border border-red-200 text-red-600 text-xs font-semibold rounded-xl flex items-center gap-1.5">
                <AlertCircle size={14} className="text-red-500 shrink-0" />
                <span>{error}</span>
              </div>
            )}

            {/* STEP 1 NEXT BUTTON */}
            <button
              disabled={
                processing || 
                (currentOrderType === "appointment" && (!selectedDate || !selectedTimeSlot)) ||
                (currentOrderType !== "appointment" && (!disclaimerAccepted || attachedImages.length === 0))
              }
              onClick={handleLockSlotAndCheckout}
              className="w-full py-3 bg-[#309BD8] hover:bg-[#2887bc] text-white font-extrabold text-xs rounded-xl disabled:opacity-40 disabled:bg-slate-200 disabled:text-slate-400 shadow-sm transition-all flex items-center justify-center gap-1.5 mt-3 cursor-pointer"
            >
              {processing ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : "Continue to payment >"}
            </button>

          </div>
        )}

        {/* STEP 2: Checkout */}
        {flowStep === "checkout" && (
          <div className="space-y-2.5 max-w-xl mx-auto">

            {/* COMPACT POINT-WISE SUMMARY CARD */}
            <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xs space-y-2 text-xs">
              <div className="flex items-center justify-between border-b border-slate-100 pb-1.5">
                <h3 className="font-extrabold text-[#309BD8] text-[11px] uppercase tracking-wider">Booking Summary</h3>
                <span className="bg-[#f0f9ff] text-[#309BD8] font-bold px-2 py-0.5 rounded-full text-[10px] border border-[#bae6fd]">
                  {currentOrderType === "appointment" ? "In-Clinic Visit" : "Video Consultation"}
                </span>
              </div>

              {/* Compact 2-Column Point Grid */}
              <div className="grid grid-cols-2 gap-2">
                {/* Doctor Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <p className="text-[9px] uppercase font-bold text-slate-400">Doctor</p>
                  <p className="font-extrabold text-[#081037] text-xs truncate">{selectedDoctor?.name || "Vet Doctor"}</p>
                  <p className="text-[10px] text-slate-500 truncate">{selectedDoctor?.degree || "BVSc"}</p>
                </div>

                {/* Pet Parent (User) Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <p className="text-[9px] uppercase font-bold text-slate-400">Pet Parent</p>
                  <p className="font-extrabold text-[#081037] text-xs truncate">{displayUserName}</p>
                  <p className="text-[10px] text-slate-500 truncate flex items-center gap-1">
                    <Phone size={10} className="text-slate-400 shrink-0" />
                    <span>{effectiveUserMobile || displayUserMobile}</span>
                  </p>
                </div>

                {/* Pet Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <div className="flex items-center justify-between mb-0.5">
                    <p className="text-[9px] uppercase font-bold text-slate-400">Pet</p>
                    {availablePets.length > 1 && (
                      <span className="text-[9px] font-bold text-[#309BD8] bg-blue-50 px-1.5 py-0.2 rounded">
                        {availablePets.length} Pets
                      </span>
                    )}
                  </div>
                  {availablePets.length > 1 ? (
                    <select
                      value={String(pet?.id || pet?.pet_id || "")}
                      onChange={(e) => setSelectedPetId(e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded px-1.5 py-0.5 text-xs font-bold text-[#081037] outline-none focus:border-[#309BD8]"
                    >
                      {availablePets.map((p) => (
                        <option key={p.id || p.pet_id} value={String(p.id || p.pet_id)}>
                          {p.name || p.pet_name} ({p.breed || p.pet_type || "Pet"})
                        </option>
                      ))}
                    </select>
                  ) : (
                    <p className="font-extrabold text-[#081037] text-xs truncate flex items-center gap-1">
                      <HeartHandshake size={11} className="text-[#309BD8] shrink-0" />
                      <span>{displayPetName}</span>
                    </p>
                  )}
                  <p className="text-[10px] text-slate-500 truncate mt-0.5">{displayPetBreed}</p>
                </div>

                {/* Schedule / Consult Mode Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <p className="text-[9px] uppercase font-bold text-slate-400">
                    {currentOrderType === "appointment" ? "Visit Schedule" : "Consult Mode"}
                  </p>
                  {currentOrderType === "appointment" ? (
                    <>
                      <p className="font-extrabold text-[#081037] text-xs truncate flex items-center gap-1">
                        <Calendar size={10} className="text-slate-400 shrink-0" />
                        <span>{selectedDate}</span>
                      </p>
                      <p className="text-[10px] text-[#309BD8] font-bold truncate flex items-center gap-1">
                        <Clock size={10} className="text-[#309BD8] shrink-0" />
                        <span>{selectedTimeSlot}</span>
                      </p>
                    </>
                  ) : (
                    <>
                      <p className="font-extrabold text-[#081037] text-xs truncate flex items-center gap-1">
                        <Zap size={11} className="text-amber-500 fill-amber-500 shrink-0" />
                        <span>Instant Video</span>
                      </p>
                      <p className="text-[10px] text-emerald-700 font-bold truncate">Connects in 0-15m</p>
                    </>
                  )}
                </div>
              </div>

              {/* Selected Package or Service Highlight Card */}
              {selectedPackage ? (
                <div className="bg-[#f0f9ff] border border-[#bae6fd] rounded-lg p-2.5 flex items-center justify-between gap-2 mt-1">
                  <div className="min-w-0">
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <span className="text-[9px] font-extrabold uppercase tracking-wider bg-[#309BD8] text-white px-1.5 py-0.5 rounded shadow-2xs flex items-center gap-1">
                        <Package size={10} className="shrink-0" />
                        <span>Package Selected</span>
                      </span>
                      <span className="text-[10px] font-bold text-[#081037]">
                        {selectedPackage.badge || selectedPackage.category || "Specialized Plan"}
                      </span>
                    </div>
                    <p className="font-extrabold text-[#081037] text-xs mt-1 truncate">{selectedPackage.title}</p>
                    <p className="text-[10px] text-slate-500 truncate mt-0.5">
                      {Array.isArray(selectedPackage.inclusions) && selectedPackage.inclusions.length > 0 
                        ? selectedPackage.inclusions.join(" • ") 
                        : (selectedPackage.description || "All-inclusive preventive & health plan")}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => updateFlowStep("describe")}
                    className="text-[10px] font-bold text-[#309BD8] hover:text-[#2887bc] hover:underline shrink-0 bg-white px-2 py-1 rounded-md border border-[#bae6fd] shadow-2xs cursor-pointer"
                  >
                    Change
                  </button>
                </div>
              ) : clinicPackages.length > 0 && currentOrderType === "appointment" ? (
                <div className="bg-slate-50 rounded-lg p-2 border border-slate-100 flex items-center justify-between text-xs mt-1">
                  <div>
                    <p className="text-[10px] text-slate-500 font-medium">Service: <span className="font-bold text-[#081037]">General Consultation</span></p>
                  </div>
                  <button
                    type="button"
                    onClick={() => updateFlowStep("describe")}
                    className="text-[10px] font-bold text-[#309BD8] hover:underline cursor-pointer"
                  >
                    Choose Package ({clinicPackages.length} available)
                  </button>
                </div>
              ) : null}
            </div>

            {/* CHECKOUT / PAYMENT SUMMARY */}
            {currentOrderType === "appointment" ? (
              <div className="space-y-3">
                {/* IN-CLINIC FEE BREAKDOWN */}
                <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-1.5 shadow-xs text-xs">
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">VISIT CONSULTATION SUMMARY</span>
                  <div className="flex justify-between py-0.5 text-slate-700">
                    <span>{selectedPackage ? `${selectedPackage.title} (Package)` : "In-Clinic Consultation Fee"}</span>
                    <span className="font-bold text-[#081037]">₹{currentFee}</span>
                  </div>
                  <div className="flex justify-between py-0.5 text-slate-700">
                    <span>Taxes & GST (18%)</span>
                    <span className="font-bold text-[#081037]">{isPackageSelected ? "₹0 (Included)" : `₹${gstAmount}`}</span>
                  </div>
                  {isPackageSelected ? null : (
                    <div className="flex justify-between py-0.5 text-slate-700">
                      <span>Clinic Facility & Service Charges</span>
                      <span className="font-bold text-emerald-600">₹0 (Free)</span>
                    </div>
                  )}
                  <div className="flex justify-between py-1.5 border-t border-slate-100 font-extrabold text-xs text-[#081037]">
                    <span>Total Consultation Amount</span>
                    <span className="text-[#309BD8] text-sm font-extrabold">₹{totalAmount}</span>
                  </div>
                </div>

                {/* GST Invoice (For Business Billing) */}
                <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs">
                  <label className="flex items-start gap-2 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={gstInvoiceChecked}
                      onChange={(e) => setGstInvoiceChecked(e.target.checked)}
                      className="w-3.5 h-3.5 rounded border-slate-300 text-[#309BD8] focus:ring-[#309BD8] mt-0.5" 
                    />
                    <div>
                      <p className="text-xs font-bold text-[#081037]">Need GST Invoice?</p>
                      <p className="text-[10px] text-slate-400">Add 15-digit GSTIN details for business billing</p>
                    </div>
                  </label>
                  {gstInvoiceChecked && (
                    <input
                      type="text"
                      maxLength={15}
                      value={gstNumber}
                      onChange={(e) => setGstNumber(e.target.value.toUpperCase().replace(/\s+/g, ""))}
                      placeholder="Enter 15-digit GST number"
                      className="mt-2 w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs uppercase focus:border-[#309BD8] outline-none"
                    />
                  )}
                </div>

                {/* PAYMENT MODE SELECTOR (Pay Online vs Pay at Clinic) */}
                <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xs space-y-2.5">
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">CHOOSE PAYMENT OPTION</span>
                    <span className="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                      2 Modes Available
                    </span>
                  </div>
                  
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {/* Mode 1: Pay at Clinic */}
                    <div 
                      onClick={() => setPaymentPreference("pay_at_clinic")}
                      className={`p-3 rounded-xl border-2 transition-all cursor-pointer flex flex-col justify-between space-y-1.5 ${
                        paymentPreference === "pay_at_clinic"
                          ? "border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-400/20"
                          : "border-slate-200 hover:border-slate-300 bg-white"
                      }`}
                    >
                      <div className="flex items-start justify-between gap-1.5">
                        <div className="flex items-center gap-2">
                          <div className="w-7 h-7 rounded-lg bg-emerald-100/80 text-emerald-700 flex items-center justify-center shrink-0">
                            <Building2 size={14} />
                          </div>
                          <div>
                            <p className="text-xs font-bold text-[#081037]">Pay at Clinic</p>
                            <p className="text-[10px] text-emerald-700 font-semibold">Cash / UPI / Cards</p>
                          </div>
                        </div>
                        <div className={`w-4 h-4 rounded-full border flex items-center justify-center text-[10px] shrink-0 ${
                          paymentPreference === "pay_at_clinic" ? "border-emerald-600 bg-emerald-600 text-white font-bold" : "border-slate-300"
                        }`}>
                          {paymentPreference === "pay_at_clinic" && <Check size={10} />}
                        </div>
                      </div>
                      <p className="text-[10px] text-slate-500 leading-snug">
                        Pay ₹{totalAmount} at reception upon arrival. No advance payment required.
                      </p>
                    </div>

                    {/* Mode 2: Pay Online */}
                    <div 
                      onClick={() => setPaymentPreference("pay_online")}
                      className={`p-3 rounded-xl border-2 transition-all cursor-pointer flex flex-col justify-between space-y-1.5 ${
                        paymentPreference === "pay_online"
                          ? "border-[#309BD8] bg-[#f0f9ff]/60 ring-2 ring-blue-400/20"
                          : "border-slate-200 hover:border-slate-300 bg-white"
                      }`}
                    >
                      <div className="flex items-start justify-between gap-1.5">
                        <div className="flex items-center gap-2">
                          <div className="w-7 h-7 rounded-lg bg-blue-100/80 text-[#309BD8] flex items-center justify-center shrink-0">
                            <Zap size={14} className="fill-[#309BD8]" />
                          </div>
                          <div>
                            <p className="text-xs font-bold text-[#081037]">Pay Online Now</p>
                            <p className="text-[10px] text-[#309BD8] font-semibold">UPI / Netbanking / Cards</p>
                          </div>
                        </div>
                        <div className={`w-4 h-4 rounded-full border flex items-center justify-center text-[10px] shrink-0 ${
                          paymentPreference === "pay_online" ? "border-[#309BD8] bg-[#309BD8] text-white font-bold" : "border-slate-300"
                        }`}>
                          {paymentPreference === "pay_online" && <Check size={10} />}
                        </div>
                      </div>
                      <p className="text-[10px] text-slate-500 leading-snug">
                        Pay ₹{totalAmount} online now for express contactless check-in.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              <div className="space-y-3">
                <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-1.5 shadow-xs text-xs">
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">SECURE CHECKOUT</span>
                  <div className="flex justify-between py-0.5 text-slate-700">
                    <span>{selectedPackage ? `${selectedPackage.title} (All-Inclusive Package)` : `Consultation Fee (${isDay ? "Day Rate" : "Night Rate"})`}</span>
                    <span className="font-bold text-[#081037]">₹{currentFee}</span>
                  </div>
                  <div className="flex justify-between py-0.5 text-slate-700">
                    <span>Taxes & GST (18%)</span>
                    <span className="font-bold text-[#081037]">{isPackageSelected ? "₹0 (Included)" : `₹${gstAmount}`}</span>
                  </div>
                  <div className="flex justify-between py-1.5 border-t border-slate-100 font-extrabold text-xs text-[#081037]">
                    <span>Total payable</span>
                    <span className="text-[#309BD8] text-sm font-extrabold">₹{totalAmount}</span>
                  </div>
                </div>

                {/* GST Invoice (Only for Video Consult / Online Payment) */}
                <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs">
                  <label className="flex items-start gap-2 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={gstInvoiceChecked}
                      onChange={(e) => setGstInvoiceChecked(e.target.checked)}
                      className="w-3.5 h-3.5 rounded border-slate-300 text-[#309BD8] focus:ring-[#309BD8] mt-0.5" 
                    />
                    <div>
                      <p className="text-xs font-bold text-[#081037]">GST Invoice</p>
                      <p className="text-[10px] text-slate-400">Need GST invoice - Add GST details for business billing</p>
                    </div>
                  </label>
                  {gstInvoiceChecked && (
                    <input
                      type="text"
                      maxLength={15}
                      value={gstNumber}
                      onChange={(e) => setGstNumber(e.target.value.toUpperCase().replace(/\s+/g, ""))}
                      placeholder="Enter 15-digit GST number"
                      className="mt-2 w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs uppercase focus:border-[#309BD8] outline-none"
                    />
                  )}
                </div>
              </div>
            )}

            {error && (
              <div className="p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600">
                {error}
              </div>
            )}

            {/* FINAL ACTION BUTTONS */}
            {currentOrderType === "appointment" ? (
              <div className="space-y-2 pt-1">
                {paymentPreference === "pay_at_clinic" ? (
                  <button
                    type="button"
                    disabled={processing}
                    onClick={() => handlePayment("pay_at_clinic")}
                    className="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer shadow-md shadow-emerald-600/20 active:scale-[0.99]"
                  >
                    {processing ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <>
                        <Building2 size={16} />
                        <span>Confirm Visit · Pay at Clinic →</span>
                      </>
                    )}
                  </button>
                ) : (
                  <button
                    type="button"
                    disabled={processing}
                    onClick={() => handlePayment("pay_online")}
                    className="w-full py-3.5 px-4 bg-[#309BD8] hover:bg-[#2887bc] text-white rounded-xl font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99]"
                  >
                    {processing ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <>
                        <Zap size={16} className="fill-white" />
                        <span>Pay ₹{totalAmount} & Confirm Booking →</span>
                      </>
                    )}
                  </button>
                )}
                
                <p className="text-[10px] text-center text-slate-400 font-medium">
                  {paymentPreference === "pay_at_clinic" 
                    ? "No advance payment required · Pay directly at clinic reception upon arrival" 
                    : "Instant booking confirmation with 100% verified veterinary doctors"}
                </p>
              </div>
            ) : (
              <button
                disabled={processing}
                onClick={() => handlePayment("pay_online")}
                className="w-full py-3 bg-[#309BD8] hover:bg-[#2887bc] text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              >
                {processing ? (
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                ) : (
                  <>
                    <Zap size={14} className="fill-white" />
                    <span>Pay ₹{totalAmount} & Book Consultation →</span>
                  </>
                )}
              </button>
            )}

          </div>
        )}

        </div>
      </div>

      {/* THANK YOU / BOOKING SUCCESS MODAL */}
      {success && bookingSuccessData && (
        <div className="fixed inset-0 z-[300] flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-[fadeIn_0.15s_ease-out]">
          <div className="bg-white w-full max-w-md rounded-3xl p-5 sm:p-6 shadow-2xl text-center space-y-4 animate-[scaleInUp_0.2s_ease-out]">
            {/* Animated Success Check Icon */}
            <div className="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-inner border-2 border-emerald-200">
              <CheckCircle2 className="w-8 h-8 text-emerald-600" />
            </div>

            <div>
              <span className="text-[10px] font-extrabold uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 inline-flex items-center gap-1">
                <CheckCircle2 size={12} className="text-emerald-600" />
                <span>Booking Confirmed</span>
              </span>
              <h2 className="text-base sm:text-lg font-extrabold text-[#081037] mt-2">
                Thank You for Choosing SnoutIQ!
              </h2>
              <p className="text-xs text-slate-500 mt-1 leading-relaxed">
                {bookingSuccessData.orderType === "appointment"
                  ? (bookingSuccessData.paymentMethod === "pay_at_clinic"
                      ? `Your clinic appointment is booked. Please pay ₹${bookingSuccessData.amount} directly at the clinic reception upon arrival.`
                      : `Your clinic appointment and online payment of ₹${bookingSuccessData.amount} have been confirmed.`)
                  : `Your online video consultation has been booked. A verified vet will connect with you within 15 minutes.`}
              </p>
            </div>

            {/* Booking Summary Card */}
            <div className="bg-slate-50 border border-slate-200/90 rounded-2xl p-3.5 text-left text-xs space-y-2">
              <div className="flex items-center justify-between py-0.5 border-b border-slate-100">
                <span className="text-slate-500 font-medium flex items-center gap-1">
                  <HeartHandshake size={12} className="text-[#309BD8]" />
                  <span>Patient (Pet)</span>
                </span>
                <span className="font-bold text-[#081037]">{bookingSuccessData.petName}</span>
              </div>
              <div className="flex items-center justify-between py-0.5 border-b border-slate-100">
                <span className="text-slate-500 font-medium flex items-center gap-1">
                  {bookingSuccessData.orderType === "appointment" ? (
                    <>
                      <Building2 size={12} className="text-emerald-600" />
                      <span>Clinic / Hospital</span>
                    </>
                  ) : (
                    <>
                      <User size={12} className="text-[#309BD8]" />
                      <span>Veterinarian</span>
                    </>
                  )}
                </span>
                <span className="font-bold text-[#081037] truncate max-w-[200px]">
                  {bookingSuccessData.orderType === "appointment" ? bookingSuccessData.clinicName : bookingSuccessData.doctorName}
                </span>
              </div>
              <div className="flex items-center justify-between py-0.5 border-b border-slate-100">
                <span className="text-slate-500 font-medium flex items-center gap-1">
                  <Calendar size={12} className="text-slate-400" />
                  <span>Schedule</span>
                </span>
                <span className="font-bold text-[#081037]">
                  {bookingSuccessData.orderType === "appointment"
                    ? `${bookingSuccessData.date} at ${bookingSuccessData.timeSlot}`
                    : "Instant Video (0-15m)"}
                </span>
              </div>
              <div className="flex items-center justify-between py-0.5">
                <span className="text-slate-500 font-medium flex items-center gap-1">
                  <CreditCard size={12} className="text-slate-400" />
                  <span>Payment Method</span>
                </span>
                <span className={`font-bold px-2 py-0.5 rounded-full text-[10px] ${
                  bookingSuccessData.paymentMethod === "pay_at_clinic"
                    ? "bg-amber-100 text-amber-900 border border-amber-200"
                    : "bg-emerald-100 text-emerald-900 border border-emerald-200"
                }`}>
                  {bookingSuccessData.paymentMethod === "pay_at_clinic"
                    ? `Pay ₹${bookingSuccessData.amount} at Clinic`
                    : `Paid ₹${bookingSuccessData.amount} (Online)`}
                </span>
              </div>
            </div>

            {/* Actions */}
            <div className="pt-2 space-y-2">
              <button
                type="button"
                onClick={() => {
                  const tabParam = bookingSuccessData.orderType === "appointment" ? "in_clinic" : "video_call";
                  if (onClose) onClose();
                  navigate(`/my-appointments?tab=${tabParam}`);
                }}
                className="w-full py-3 bg-[#309BD8] hover:bg-[#2887bc] text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>OK · View My Appointments →</span>
              </button>

              <button
                type="button"
                onClick={() => {
                  if (onClose) onClose();
                  navigate("/");
                }}
                className="w-full py-1.5 text-slate-500 hover:text-slate-700 font-semibold text-xs transition-colors cursor-pointer"
              >
                Back to Home
              </button>
            </div>
          </div>
        </div>
      )}

      {/* FILTER MODAL — MOBILE BOTTOM SHEET & PILL STYLE */}
      {showFilterModal && (
        <div className="fixed inset-0 z-[200] flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-xs p-0 sm:p-4 animate-[fadeIn_0.15s_ease-out]" onClick={() => setShowFilterModal(false)}>
          <div className="bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl p-4 sm:p-5 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto animate-[scaleInUp_0.2s_ease-out]" onClick={e => e.stopPropagation()}>
            
            <div className="flex items-center justify-between border-b border-slate-100 pb-2.5">
              <div>
                <h3 className="text-xs font-extrabold text-[#081037]">Filter Verified Doctors</h3>
                <p className="text-[10px] text-slate-500">Refine by experience, specialty, and price</p>
              </div>
              <button onClick={() => setShowFilterModal(false)} className="p-1.5 bg-slate-100 hover:bg-slate-200 rounded-full text-slate-600 cursor-pointer">
                <X size={14} />
              </button>
            </div>

            {/* 1. Experience Filter (Pills Row) */}
            <div className="space-y-1.5">
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Experience</span>
              <div className="flex flex-wrap gap-1.5">
                {[
                  { label: "Any", val: "any" },
                  { label: "1+ Yrs", val: "1" },
                  { label: "3+ Yrs", val: "3" },
                  { label: "5+ Yrs", val: "5" },
                  { label: "10+ Yrs", val: "10" }
                ].map(opt => (
                  <button
                    key={opt.val}
                    type="button"
                    onClick={() => setSelectedExpFilter(opt.val)}
                    className={`px-3 py-1.5 rounded-full text-[11px] font-bold transition-all border cursor-pointer ${
                      selectedExpFilter === opt.val
                        ? "border-[#309BD8] bg-[#309BD8] text-white shadow-xs"
                        : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                    }`}
                  >
                    {opt.label}
                  </button>
                ))}
              </div>
            </div>

            {/* 2. Specialty Filter (Scrollable Pill Box with min 4+ visible pills) */}
            <div className="space-y-1.5">
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Specialty</span>
              <div className="max-h-36 overflow-y-auto flex flex-wrap gap-1.5 p-1 border border-slate-100 rounded-xl bg-slate-50/50">
                <button
                  type="button"
                  onClick={() => setSelectedSpecialtyFilter("all")}
                  className={`px-2.5 py-1 rounded-full text-[10px] font-bold transition-all border cursor-pointer ${
                    selectedSpecialtyFilter === "all"
                      ? "border-[#309BD8] bg-[#309BD8] text-white shadow-xs"
                      : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                  }`}
                >
                  All Specialties
                </button>
                {availableSpecialties.map(spec => (
                  <button
                    key={spec}
                    type="button"
                    onClick={() => setSelectedSpecialtyFilter(spec)}
                    className={`px-2.5 py-1 rounded-full text-[10px] font-bold transition-all border cursor-pointer ${
                      selectedSpecialtyFilter === spec
                        ? "border-[#309BD8] bg-[#309BD8] text-white shadow-xs"
                        : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                    }`}
                  >
                    {spec}
                  </button>
                ))}
              </div>
            </div>

            {/* 3. Price Range Filter (Pills Row) */}
            <div className="space-y-1.5">
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Consultation Fee</span>
              <div className="flex flex-wrap gap-1.5">
                {[
                  { label: "Any Price", val: "any" },
                  { label: "Under ₹500", val: "0-500" },
                  { label: "₹500 – ₹1000", val: "500-1000" },
                  { label: "₹1000+", val: "1000+" }
                ].map(opt => (
                  <button
                    key={opt.val}
                    type="button"
                    onClick={() => setSelectedPriceFilter(opt.val)}
                    className={`px-3 py-1.5 rounded-full text-[11px] font-bold transition-all border cursor-pointer ${
                      selectedPriceFilter === opt.val
                        ? "border-[#309BD8] bg-[#309BD8] text-white shadow-xs"
                        : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                    }`}
                  >
                    {opt.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Footer Buttons */}
            <div className="flex items-center gap-2 pt-2 border-t border-slate-100">
              <button
                type="button"
                onClick={() => {
                  setSelectedExpFilter("any");
                  setSelectedSpecialtyFilter("all");
                  setSelectedPriceFilter("any");
                }}
                className="flex-1 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all cursor-pointer"
              >
                Reset
              </button>
              <button
                type="button"
                onClick={() => setShowFilterModal(false)}
                className="flex-1 py-2 text-xs font-bold text-white bg-[#309BD8] hover:bg-[#2887bc] rounded-xl transition-all shadow-xs cursor-pointer"
              >
                Apply Filters
              </button>
            </div>

          </div>
        </div>
      )}

      {/* VIEW PROFILE MODAL */}
      {viewProfileDoctor && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 animate-[fadeIn_0.15s_ease-out]" onClick={() => setViewProfileDoctor(null)}>
          <div className="bg-white w-full max-w-sm rounded-2xl overflow-hidden shadow-2xl border border-slate-200 animate-[scaleInUp_0.2s_ease-out]" onClick={e => e.stopPropagation()}>
            
            {/* Dark Navy Top Banner Header */}
            <div className="bg-[#081037] text-white px-4 py-3 flex items-center justify-between">
              <div className="flex items-center gap-2">
                <div className="w-6 h-6 rounded-md bg-[#309BD8]/20 border border-[#309BD8]/40 flex items-center justify-center">
                  <Shield className="w-3.5 h-3.5 text-[#309BD8]" />
                </div>
                <div>
                  <h3 className="text-[10px] font-extrabold uppercase tracking-wider text-slate-300">Verified Doctor Profile</h3>
                  <p className="text-xs font-bold text-white leading-tight">{viewProfileDoctor.name}</p>
                </div>
              </div>

              <button onClick={() => setViewProfileDoctor(null)} className="p-1 bg-white/10 hover:bg-white/20 rounded-full transition-colors text-white cursor-pointer">
                <X className="w-3.5 h-3.5" />
              </button>
            </div>

            {/* Body Info */}
            <div className="p-3 max-h-[70vh] overflow-y-auto space-y-2.5 bg-slate-50/50">
              
              {/* Doctor Avatar Card */}
              <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xs flex items-center gap-3">
                <div className="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                  {viewProfileDoctor.image ? (
                    <img src={viewProfileDoctor.image} alt={viewProfileDoctor.name} className="w-full h-full object-cover" />
                  ) : (
                    <div className="w-full h-full bg-[#081037] text-white font-bold flex items-center justify-center text-sm">
                      {viewProfileDoctor.name.charAt(0)}
                    </div>
                  )}
                </div>
                <div>
                  <div className="flex items-center gap-1">
                    <h4 className="font-extrabold text-[#081037] text-xs">{viewProfileDoctor.name}</h4>
                    <span className="bg-[#f0f9ff] text-[#309BD8] text-[9px] font-extrabold px-1.5 py-0.5 rounded-full flex items-center gap-0.5 border border-[#bae6fd]">
                      <Check size={8} /> Verified
                    </span>
                  </div>
                  <div className="flex items-center gap-1.5 mt-0.5 flex-wrap">
                    <p className="text-[11px] font-semibold text-[#309BD8]">
                      {viewProfileDoctor.degree} · {viewProfileDoctor.experience} Yrs Exp
                    </p>
                    {viewProfileDoctor.distance_km != null && !isNaN(Number(viewProfileDoctor.distance_km)) && (
                      <span className="text-[10px] font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded-md inline-flex items-center gap-1">
                        <MapPin size={10} className="text-slate-500 shrink-0" />
                        <span>{Number(viewProfileDoctor.distance_km).toFixed(1)} km away</span>
                      </span>
                    )}
                  </div>
                  {viewProfileDoctor.googleRating !== null && (
                    <span className="inline-flex items-center gap-1 mt-1 bg-amber-50 text-amber-900 border border-amber-200 px-1.5 py-0.5 rounded-md text-[10px] font-bold">
                      <Star size={10} className="fill-amber-400 text-amber-500 shrink-0" />
                      <span>{viewProfileDoctor.googleRating} ({viewProfileDoctor.googleReviewCount})</span>
                    </span>
                  )}
                </div>
              </div>

              {/* LIVE PRICING BREAKDOWN CARD */}
              <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs space-y-1 text-xs">
                <div className="flex items-center justify-between">
                  <span className="text-slate-500 font-medium text-[11px]">Consultation Mode</span>
                  <span className="font-bold text-[#081037] text-[11px]">Video Consultation</span>
                </div>
                <div className="flex items-center justify-between border-t border-slate-100 pt-1">
                  <span className="text-slate-500 font-medium text-[10px]">
                    Current Rate ({isDay ? "Day Rate" : "Night Rate"})
                  </span>
                  <span className="font-extrabold text-emerald-700 text-xs">
                    ₹{getDoctorCurrentPrice(viewProfileDoctor, isDay)}
                  </span>
                </div>
              </div>

              {/* Specialization */}
              <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs space-y-1.5">
                <h5 className="text-[9px] font-bold uppercase tracking-wider text-slate-400">SPECIALIZATIONS</h5>
                <div className="flex flex-wrap gap-1">
                  {viewProfileDoctor.specialization.split(",").map((s, idx) => (
                    <span key={idx} className="bg-slate-100 text-slate-700 text-[10px] font-medium px-2 py-0.5 rounded-md">
                      {s.trim()}
                    </span>
                  ))}
                </div>
              </div>

              {/* Doctor Bio */}
              {viewProfileDoctor.bio && (
                <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs space-y-1">
                  <h5 className="text-[9px] font-bold uppercase tracking-wider text-slate-400">ABOUT DOCTOR</h5>
                  <p className="text-[11px] text-slate-700 leading-snug whitespace-pre-line">
                    {viewProfileDoctor.bio}
                  </p>
                </div>
              )}

              {/* Response Time & Follow-up */}
              <div className="grid grid-cols-2 gap-2 text-xs">
                <div className="bg-emerald-50/70 border border-emerald-200/70 rounded-lg p-2">
                  <p className="text-[9px] font-bold text-emerald-800 uppercase tracking-wider">Day Response</p>
                  <p className="font-extrabold text-emerald-900 mt-0.5 text-[11px]">{viewProfileDoctor.responseTimeDay || "0 To 15 Mins"}</p>
                </div>
                <div className="bg-[#f0f9ff] border border-[#bae6fd] rounded-lg p-2">
                  <p className="text-[9px] font-bold text-[#309BD8] uppercase tracking-wider">Night Response</p>
                  <p className="font-extrabold text-[#081037] mt-0.5 text-[11px]">{viewProfileDoctor.responseTimeNight || "15 To 20 Mins"}</p>
                </div>
              </div>

              {viewProfileDoctor.followUpPolicy && (
                <div className="bg-purple-50/70 border border-purple-200/70 rounded-lg p-2 text-[11px] text-purple-900 font-semibold flex items-center gap-1.5">
                  <Shield className="w-3.5 h-3.5 text-purple-600 flex-shrink-0" />
                  <span>{viewProfileDoctor.followUpPolicy}</span>
                </div>
              )}

            </div>

            {/* Bottom CTA Button */}
            <div className="p-3 bg-white border-t border-slate-200">
              <button
                onClick={() => {
                  const doc = viewProfileDoctor;
                  setViewProfileDoctor(null);
                  handleBookNowClick(doc);
                }}
                className="w-full py-2.5 bg-[#309BD8] hover:bg-[#2887bc] text-white font-bold text-xs rounded-xl transition-all shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
              >
                Proceed to Book (₹{getDoctorCurrentPrice(viewProfileDoctor, isDay)}) →
              </button>
            </div>

          </div>
        </div>
      )}

      {showPhoneGate && (
        <PhoneVerifyGate
          onVerified={(newPhone) => {
            const clean = normalizePhone(newPhone);
            setVerifiedPhone(clean);
            setShowPhoneGate(false);
            setTimeout(() => {
              handlePayment(paymentPreference, clean);
            }, 50);
          }}
          onClose={() => setShowPhoneGate(false)}
        />
      )}

    </div>
  );
}
