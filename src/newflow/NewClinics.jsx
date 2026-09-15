import React, { useEffect, useMemo, useRef, useState } from "react";
import { Link, useNavigate, useParams, useSearchParams } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  Check,
  Clock,
  CalendarDays,
  IndianRupee,
  Loader2,
  MapPin,
  Search,
  Stethoscope,
  Video,
  Wrench,
  Star,
  ShieldCheck,
  ChevronLeft,
  ChevronRight,
  Navigation,
  Building2,
  Sparkles,
  Award,
  ExternalLink,
  Share2,
  CheckCircle2,
  HeartHandshake,
  Activity,
  BadgeCheck,
  Camera,
  X,
} from "lucide-react";

import axiosClient from "../axios";
import clinicFallbackImage from "../assets/images/clinic.png";
import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter";
import { Button } from "../newflow/NewButton";
import ModernDoctorBooking, { isDayTimeNow, useIsDayTime } from "./ModernDoctorBooking";
import { extractPackageItems, PACKAGE_DETAILS } from "./packageHelpers";

const CLINIC_FORM_API_URL = "https://snoutiq.com/backend/api/demo-website-form";
const DIRECT_CONSULT_PATH = "/20+vetsonline?start=details";

const valueOrDash = (value) => {
  const text = String(value ?? "").trim();
  return text || "-";
};

const hasValue = (value) => String(value ?? "").trim() !== "";

const plural = (count, label) => `${count} ${label}${count === 1 ? "" : "s"}`;

const formatMoney = (value) => {
  if (value === null || value === undefined || value === "") return null;
  const amount = Number(value);
  if (!Number.isFinite(amount) || amount <= 0) return null;
  return `₹${amount.toLocaleString("en-IN")}`;
};

export const getClinicVisitFee = (entryOrClinic) => {
  if (!entryOrClinic) return null;
  const clinic = entryOrClinic.clinic || entryOrClinic;
  
  // 1. Check services array for "In Clinic Consultation" or first active service
  const services = entryOrClinic.services || clinic.clinic_services || clinic.services;
  if (Array.isArray(services) && services.length > 0) {
    const consultService = services.find(s => 
      (s.name && /consult/i.test(s.name)) || 
      s.main_service === "vet"
    ) || services[0];
    if (consultService && consultService.price !== null && consultService.price !== undefined && consultService.price !== "" && !isNaN(Number(consultService.price)) && Number(consultService.price) > 0) {
      return formatMoney(consultService.price);
    }
  }

  // 2. Doctor's consultation price
  const doctors = entryOrClinic.doctors || clinic.doctors;
  const firstDoc = Array.isArray(doctors) && doctors.length > 0 ? doctors[0] : null;
  if (firstDoc?.doctors_price && !isNaN(Number(firstDoc.doctors_price)) && Number(firstDoc.doctors_price) > 0) {
    return formatMoney(firstDoc.doctors_price);
  }
  if (clinic.doctors_price && !isNaN(Number(clinic.doctors_price)) && Number(clinic.doctors_price) > 0) {
    return formatMoney(clinic.doctors_price);
  }

  // 3. clinic_day_fee / clinic_fee / clinic_night_fee
  const fee = clinic.clinic_day_fee || clinic.clinic_fee || clinic.fee || clinic.clinic_night_fee;
  if (fee && !isNaN(Number(fee)) && Number(fee) > 0) {
    return formatMoney(fee);
  }

  return "₹500";
};

const extractClinics = (payload) => {
  const page = payload?.data?.data || payload?.data || payload;
  if (Array.isArray(page)) return page;
  if (Array.isArray(page?.data)) return page.data;
  return [];
};

const normalizeImage = (value) => {
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
};

const getClinicImage = (clinic) => {
  const candidate = clinic?.clinic_image_url || clinic?.clinic_image || clinic?.image;
  return candidate ? normalizeImage(candidate) : clinicFallbackImage;
};

const getClinicSummary = (clinic) =>
  clinic?.website_subtitle ||
  clinic?.clinic_profile ||
  clinic?.hospital_profile ||
  clinic?.bio ||
  clinic?.address ||
  "";

const isClinicEntryUsable = (entry) => {
  const clinic = entry?.clinic || {};
  return Boolean(
    hasValue(clinic.name) ||
      hasValue(clinic.website_title) ||
      hasValue(clinic.clinic_profile) ||
      hasValue(clinic.hospital_profile) ||
      hasValue(clinic.address) ||
      hasValue(clinic.formatted_address) ||
      (Array.isArray(entry?.doctors) && entry.doctors.length > 0)
  );
};

const doctorImageUrl = (doctor) => {
  if (!doctor) return "";
  const candidate =
    doctor.doctor_image_blob_url ||
    doctor.doctor_blob_url ||
    doctor.doctor_blob ||
    doctor.doctor_image_url ||
    doctor.doctor_image ||
    doctor.image ||
    "";
  return candidate ? normalizeImage(candidate) : "";
};

const doctorToClinicFallbackEntry = (doctor, clinicKey) => {
  if (!doctor) return null;

  const doctorName = doctor.doctor_name || doctor.name || "Veterinary Doctor";
  const clinicObj = doctor.clinic || {};
  const clinicId =
    doctor.vet_registeration_id ||
    doctor.clinic_id ||
    clinicObj.id ||
    doctor.vet_id ||
    (String(clinicKey || "").match(/^\d+$/) ? Number(clinicKey) : clinicKey);
  const image = doctorImageUrl(doctor);
  const clinicName =
    clinicObj.name ||
    doctor.clinic_name ||
    `${formatDoctorName(doctorName)}'s consultation profile`;

  const clinicImg =
    normalizeImage(clinicObj.clinic_image_url || clinicObj.clinic_image || clinicObj.image) ||
    image ||
    clinicFallbackImage;

  return {
    clinic: {
      id: clinicId,
      slug: clinicObj.slug || doctor.clinic_slug || doctor.vet_slug || String(clinicKey || clinicId || ""),
      name: clinicName,
      city: clinicObj.city || doctor.clinic_city || doctor.city || "",
      pincode: clinicObj.pincode || doctor.clinic_pincode || doctor.pincode || "",
      address: clinicObj.address || doctor.clinic_address || doctor.address || "",
      formatted_address:
        clinicObj.formatted_address ||
        clinicObj.address ||
        doctor.clinic_formatted_address ||
        doctor.clinic_address ||
        doctor.formatted_address ||
        doctor.address ||
        "",
      lat: clinicObj.lat || doctor.clinic_lat || doctor.lat || null,
      lng: clinicObj.lng || doctor.clinic_lng || doctor.lng || null,
      rating: clinicObj.rating || doctor.google_rating || doctor.rating || null,
      user_ratings_total:
        clinicObj.user_ratings_total || doctor.google_user_ratings_total || doctor.user_ratings_total || null,
      mobile: clinicObj.mobile || doctor.clinic_mobile || doctor.doctor_mobile || doctor.phone || "",
      clinic_image_url: clinicImg,
      clinic_video_url: clinicObj.clinic_video_url || clinicObj.clinic_video || null,
      clinic_profile:
        clinicObj.clinic_profile ||
        clinicObj.hospital_profile ||
        doctor.bio ||
        doctor.clinic_profile ||
        "Verified SnoutIQ veterinarian available for online consultation.",
      hospital_profile: clinicObj.hospital_profile || doctor.bio || "",
      website_subtitle:
        clinicObj.website_subtitle ||
        clinicObj.website_about ||
        doctor.bio ||
        "Book a video consultation with a verified SnoutIQ veterinarian.",
      clinic_day_fee: clinicObj.clinic_day_fee || doctor.doctors_price || doctor.video_day_rate || null,
      clinic_night_fee: clinicObj.clinic_night_fee || doctor.video_night_rate || null,
    },
    doctors: [
      {
        ...doctor,
        id: doctor.id || doctor.doctor_id,
        doctor_name: doctorName,
        vet_registeration_id: clinicId,
        doctor_mobile: doctor.doctor_mobile || doctor.phone || "",
        doctor_image_blob_url: image,
        doctor_image: image,
      },
    ],
    services: Array.isArray(doctor.clinic_services)
      ? doctor.clinic_services
      : Array.isArray(clinicObj.clinic_services)
        ? clinicObj.clinic_services
        : [],
    machinery: [],
    specialized_packages: [],
    vet_at_home_services: [],
    clinic_availability: [],
    video_schedules: [],
    is_doctor_fallback: true,
  };
};

const DAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const formatTime12h = (value) => {
  if (!value) return "-";
  const text = String(value);
  const [hourStr = "", minuteStr = "00"] = text.split(":");
  const hour = parseInt(hourStr, 10);
  if (isNaN(hour)) return text;
  const ampm = hour >= 12 ? "PM" : "AM";
  const hour12 = hour % 12 || 12;
  return `${hour12}:${minuteStr.padStart(2, "0")} ${ampm}`;
};

const formatTime = (value) => formatTime12h(value);

const formatSlot = (slot) =>
  `${DAYS[Number(slot?.day_of_week)] || "Day"} ${formatTime12h(slot?.start_time)} – ${formatTime12h(slot?.end_time)}`;

const formatDoctorName = (name) => {
  const clean = String(name ?? "").trim();
  if (!clean || clean === "-") return "Doctor";
  if (/^dr\.?\s+/i.test(clean)) return clean;
  return `Dr. ${clean}`;
};

const getDoctorInitials = (name) => {
  const clean = String(name ?? "")
    .replace(/^dr\.?\s+/i, "")
    .trim();
  const parts = clean.split(/\s+/).filter(Boolean);
  if (!parts.length) return "DR";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const formatLabel = (value) =>
  String(value || "")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

const parseList = (value) => {
  if (Array.isArray(value)) return value.filter(Boolean);
  if (!value) return [];

  const text = String(value).trim();
  if (!text) return [];

  try {
    const parsed = JSON.parse(text);
    if (Array.isArray(parsed)) return parsed.filter(Boolean);
    if (typeof parsed === "string") {
      return parsed.split(",").map((s) => s.trim()).filter(Boolean);
    }
  } catch {
    // String split fallback
  }

  return text
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
};

const splitByMainService = (services = [], machinery = []) => {
  const machineRows = machinery.length
    ? machinery
    : services.filter((service) => service?.main_service === "machinery");

  const serviceRows = services.filter(
    (service) => service?.main_service !== "machinery"
  );

  return { serviceRows, machineRows };
};

const isNightSlot = (slot) => {
  const hour = Number(String(slot?.start_time || "").slice(0, 2));
  return Number.isFinite(hour) && hour >= 18;
};

const mapUrlForClinic = (clinic) => {
  if (clinic?.lat && clinic?.lng) {
    return `https://www.google.com/maps/search/?api=1&query=${clinic.lat},${clinic.lng}`;
  }

  const query = [clinic?.formatted_address, clinic?.address, clinic?.city]
    .filter(Boolean)
    .join(", ");

  return query
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`
    : null;
};

function DetailSection({ title, subtitle, icon: Icon, badge, action, children }) {
  return (
    <section className="min-w-0 max-w-full rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs transition-all hover:border-slate-300">
      <div className="flex items-center justify-between border-b border-slate-100 pb-4 mb-4 gap-3">
        <div className="flex items-center gap-3 min-w-0">
          {Icon && (
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100/60 shadow-xs shrink-0">
              <Icon className="h-5 w-5" />
            </div>
          )}
          <div className="min-w-0">
            <h2 className="text-base sm:text-lg font-bold tracking-tight text-slate-900 truncate">{title}</h2>
            {subtitle && <p className="text-[11px] sm:text-xs text-slate-500 mt-0.5">{subtitle}</p>}
          </div>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          {action}
          {badge && (
            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 shrink-0">
              {badge}
            </span>
          )}
        </div>
      </div>
      <div>{children}</div>
    </section>
  );
}

function SlotList({ slots }) {
  const todayDayIndex = new Date().getDay();

  return (
    <div className="grid gap-3 sm:grid-cols-2">
      {slots.map((slot) => {
        const isToday = Number(slot?.day_of_week) === todayDayIndex;
        return (
          <div
            key={`${slot.doctor_id}-${slot.service_type}-${slot.day_of_week}-${slot.start_time}-${slot.end_time}`}
            className={`relative rounded-xl border p-3.5 transition-all ${
              isToday
                ? "border-blue-300 bg-blue-50/50 ring-1 ring-blue-400/30 shadow-xs"
                : "border-slate-200/90 bg-slate-50/60 hover:bg-slate-50 hover:border-slate-300"
            }`}
          >
            <div className="flex items-center justify-between">
              <p className="font-bold text-slate-900 text-xs sm:text-sm flex items-center gap-1.5">
                <Clock className={`h-3.5 w-3.5 ${isToday ? "text-blue-600" : "text-slate-400"}`} />
                {formatSlot(slot)}
              </p>
              {isToday && (
                <span className="rounded-full bg-blue-600 px-2 py-0.5 text-[9px] font-bold text-white uppercase tracking-wider">
                  Today
                </span>
              )}
            </div>
            <p className="mt-1 text-[11px] sm:text-xs font-medium text-slate-600 flex items-center gap-1.5">
              <span className="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              {formatLabel(slot.service_type || "in_clinic")}
              {slot.doctor_name ? ` · ${formatDoctorName(slot.doctor_name)}` : ""}
            </p>
            {slot.break_start && slot.break_end ? (
              <p className="mt-1 text-[10px] sm:text-[11px] text-slate-400">
                Break: {formatTime(slot.break_start)} – {formatTime(slot.break_end)}
              </p>
            ) : null}
          </div>
        );
      })}
    </div>
  );
}

function ClinicLeadForm() {
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [formData, setFormData] = useState({
    clinicName: "",
    contactName: "",
    mobile: "",
    city: "",
  });

  const handleChange = (e) => {
    const { id, value } = e.target;
    setFormData((prev) => ({ ...prev, [id]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitError("");
    setIsSubmitting(true);

    const payload = {
      name: formData.contactName.trim(),
      clinic_name: formData.clinicName.trim(),
      contact_name: formData.contactName.trim(),
      mobile: formData.mobile.trim(),
      city: formData.city.trim(),
      source: "newflow_clinics_page",
    };

    try {
      const response = await fetch(CLINIC_FORM_API_URL, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const raw = await response.text();
      let data = null;
      try {
        data = raw ? JSON.parse(raw) : null;
      } catch {
        data = null;
      }

      if (!response.ok) {
        const message =
          data?.message ||
          (data?.errors && Object.values(data.errors).flat()[0]) ||
          "Unable to submit request right now. Please try again.";
        throw new Error(message);
      }

      setIsSubmitted(true);
      setFormData({ clinicName: "", contactName: "", mobile: "", city: "" });
    } catch (error) {
      setSubmitError(
        error?.message || "Unable to submit request right now. Please try again."
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section id="clinic-onboarding-form" className="bg-slate-50 py-12 sm:py-14">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wider text-blue-700">
            For clinics
          </p>
          <h2 className="mt-2.5 text-2xl font-bold text-slate-950 sm:text-3xl">
            Bring your clinic into SnoutIQ.
          </h2>
          <p className="mt-3 text-sm sm:text-base leading-relaxed text-slate-600">
            Add verified doctors, services, appointment hours, and video consult
            availability from the onboarding panel.
          </p>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-7">
          {isSubmitted ? (
            <div className="py-6 text-center">
              <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <Check className="h-6 w-6" />
              </div>
              <h3 className="text-lg font-bold text-slate-950">
                Thank you. We will reach out shortly.
              </h3>
              <p className="mt-1.5 text-xs sm:text-sm text-slate-600">
                Our team will contact you to discuss onboarding for your clinic.
              </p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              {[
                ["clinicName", "Clinic Name", "e.g. City Pet Care Clinic"],
                ["contactName", "Contact Person", "e.g. Dr. Aditi Sharma"],
                ["mobile", "Mobile Number", "+91 98765 43210"],
                ["city", "City", "e.g. Bengaluru"],
              ].map(([id, label, placeholder]) => (
                <div key={id}>
                  <label
                    htmlFor={id}
                    className="mb-1.5 block text-xs font-medium text-slate-700"
                  >
                    {label}
                  </label>
                  <input
                    id={id}
                    required
                    type={id === "mobile" ? "tel" : "text"}
                    value={formData[id]}
                    onChange={handleChange}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder={placeholder}
                  />
                </div>
              ))}

              {submitError ? (
                <p className="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs text-red-700">
                  {submitError}
                </p>
              ) : null}

              <Button
                type="submit"
                size="default"
                disabled={isSubmitting}
                className="w-full bg-blue-600 text-white shadow-md shadow-blue-600/20 hover:bg-blue-700 text-xs sm:text-sm font-semibold py-2.5"
              >
                {isSubmitting ? "Submitting..." : "Submit Request"}
              </Button>
            </form>
          )}
        </div>
      </div>
    </section>
  );
}

function ClinicDirectory() {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const isDay = useIsDayTime();
  const [clinics, setClinics] = useState([]);
  const [query, setQuery] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [bookingModal, setBookingModal] = useState({
    isOpen: false,
    orderType: "appointment",
    clinic: null,
    doctor: null,
    package: null,
  });

  const openBookingModal = ({ orderType, clinic: targetClinic = null, doctor = null, package: selectedPackage = null }) => {
    const currentParams = new URLSearchParams(searchParams);
    currentParams.set("type", orderType || "appointment");
    currentParams.set("step", "describe");
    setSearchParams(currentParams);

    setBookingModal({
      isOpen: true,
      orderType: orderType || "appointment",
      clinic: targetClinic,
      doctor: doctor,
      package: selectedPackage,
    });
  };

  const closeBookingModal = () => {
    document.body.style.overflow = "";
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.delete("step");
    currentParams.delete("type");
    currentParams.delete("orderType");
    setSearchParams(currentParams, { replace: true });
    setBookingModal({
      isOpen: false,
      orderType: "appointment",
      clinic: null,
      doctor: null,
      package: null,
    });
  };

  // Sync bookingModal state with URL search params (e.g. browser back button)
  useEffect(() => {
    const step = searchParams.get("step");
    if (!step && bookingModal.isOpen) {
      setBookingModal((prev) => ({ ...prev, isOpen: false }));
    }
  }, [searchParams]);

  useEffect(() => {
    let cancelled = false;

    const loadClinics = async () => {
      setIsLoading(true);
      setError("");

      try {
        const { data } = await axiosClient.get("/clinic-pages", {
          params: { per_page: 100 },
        });

        if (!cancelled) {
          setClinics(extractClinics(data));
        }
      } catch (err) {
        if (!cancelled) {
          setError(
            err?.response?.data?.message ||
              "Unable to load clinics right now."
          );
        }
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    loadClinics();
    return () => {
      cancelled = true;
    };
  }, []);

  const filteredClinics = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) return clinics;

    return clinics.filter((entry) => {
      const clinic = entry?.clinic || {};
      return [clinic.name, clinic.city, clinic.address, clinic.slug]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(term));
    });
  }, [clinics, query]);

  const containerVariants = {
    hidden: { opacity: 0 },
    show: {
      opacity: 1,
      transition: {
        staggerChildren: 0.08,
        delayChildren: 0.05,
      },
    },
  };

  const cardVariants = {
    hidden: { opacity: 0, y: 28, scale: 0.98 },
    show: {
      opacity: 1,
      y: 0,
      scale: 1,
      transition: {
        duration: 0.45,
        ease: [0.16, 1, 0.3, 1],
      },
    },
  };

  return (
    <>
      <section className="relative overflow-hidden border-b border-slate-200 bg-gradient-to-b from-blue-50/40 via-white to-white py-8 sm:py-12">
        {/* Ambient background decoration */}
        <div className="pointer-events-none absolute -left-32 -top-32 h-80 w-80 rounded-full bg-blue-200/30 blur-3xl" />
        <div className="pointer-events-none absolute right-0 top-1/2 h-72 w-72 -translate-y-1/2 rounded-full bg-sky-100/40 blur-3xl" />

        <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-3">
          <button
            type="button"
            onClick={() => {
              if (window.history.length > 1) {
                navigate(-1);
              } else {
                navigate("/");
              }
            }}
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-600 bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-2xs transition-all cursor-pointer"
          >
            <ArrowLeft className="h-3.5 w-3.5" />
            <span>Back</span>
          </button>
        </div>

        <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid gap-6 lg:grid-cols-[1fr_0.42fr] lg:items-end">
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, ease: "easeOut" }}
            >
              <motion.div
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ duration: 0.4, delay: 0.1 }}
                className="inline-flex items-center gap-1.5 rounded-full border border-blue-200/80 bg-blue-50/90 px-3 py-0.5 text-[11px] font-semibold text-blue-700 shadow-2xs"
              >
                <span className="relative flex h-2 w-2">
                  <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                  <span className="relative inline-flex h-2 w-2 rounded-full bg-blue-600"></span>
                </span>
                {plural(clinics.length, "clinic")} listed across India
              </motion.div>

              <h1 className="mt-3 text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">
                Find a SnoutIQ clinic near you
              </h1>
              <p className="mt-2.5 max-w-2xl text-xs sm:text-sm leading-relaxed text-slate-600">
                Browse verified veterinary hospitals with on-site doctors, consultation
                fees, facilities, diagnostics, and direct appointment booking.
              </p>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.5, delay: 0.2, ease: "easeOut" }}
              className="relative"
            >
              <label className="relative block">
                <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-blue-600" />
                <input
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  className="h-11 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-xs sm:text-sm font-medium text-slate-900 shadow-xs outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-3 focus:ring-blue-100"
                  placeholder="Search clinic, city, or slug..."
                />
                <AnimatePresence>
                  {query && (
                    <motion.button
                      type="button"
                      initial={{ opacity: 0, scale: 0.8 }}
                      animate={{ opacity: 1, scale: 1 }}
                      exit={{ opacity: 0, scale: 0.8 }}
                      onClick={() => setQuery("")}
                      className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 cursor-pointer"
                      title="Clear search"
                    >
                      <X className="h-3.5 w-3.5" />
                    </motion.button>
                  )}
                </AnimatePresence>
              </label>
            </motion.div>
          </div>
        </div>
      </section>

      <section className="bg-slate-50/70 py-8 sm:py-12 min-h-[500px]">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {isLoading ? (
            /* Animated Loading Skeletons */
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {[1, 2, 3, 4, 5, 6].map((idx) => (
                <div
                  key={idx}
                  className="animate-pulse overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs"
                >
                  <div className="aspect-[16/10] w-full bg-slate-200" />
                  <div className="p-4 space-y-2.5">
                    <div className="h-3.5 w-1/3 rounded bg-slate-200" />
                    <div className="h-3 w-4/5 rounded bg-slate-200" />
                    <div className="h-3 w-3/5 rounded bg-slate-200" />
                    <div className="flex gap-1.5 pt-1.5">
                      <div className="h-5 w-14 rounded-md bg-slate-200" />
                      <div className="h-5 w-16 rounded-md bg-slate-200" />
                    </div>
                    <div className="flex justify-between items-center pt-3.5 border-t border-slate-100">
                      <div className="h-3.5 w-16 rounded bg-slate-200" />
                      <div className="h-7 w-20 rounded-xl bg-slate-200" />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : error ? (
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              className="flex items-center gap-2.5 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm text-red-700"
            >
              <AlertCircle className="h-4 w-4 shrink-0" />
              <p>{error}</p>
            </motion.div>
          ) : filteredClinics.length === 0 ? (
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.3 }}
              className="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-xs"
            >
              <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <Search className="h-7 w-7" />
              </div>
              <h2 className="mt-3.5 text-lg font-bold text-slate-950">
                No clinics matching "{query}"
              </h2>
              <p className="mt-1.5 text-xs text-slate-500">
                Try searching for another city, clinic name, or clear the search query.
              </p>
              {query && (
                <button
                  type="button"
                  onClick={() => setQuery("")}
                  className="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 cursor-pointer transition-all active:scale-95"
                >
                  Clear search
                </button>
              )}
            </motion.div>
          ) : (
            <motion.div
              variants={containerVariants}
              initial="hidden"
              animate="show"
              className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
            >
              {filteredClinics.map((entry) => {
                const clinic = entry?.clinic || {};
                const { serviceRows, machineRows } = splitByMainService(
                  entry?.services || [],
                  entry?.machinery || []
                );
                const doctors = entry?.doctors?.length || 0;
                const packages = entry?.specialized_packages?.length || 0;
                const clinicFee = getClinicVisitFee(entry);
                const summary = getClinicSummary(clinic);
                const quickStats = [
                  [doctors, plural(doctors, "doctor"), Stethoscope],
                  [serviceRows.length, plural(serviceRows.length, "service"), CheckCircle2],
                  [machineRows.length, plural(machineRows.length, "machine"), Wrench],
                  [packages, plural(packages, "package"), Sparkles],
                ].filter(([count]) => count > 0);

                return (
                  <motion.div
                    key={clinic.id || clinic.slug}
                    variants={cardVariants}
                    whileHover={{ y: -5, transition: { duration: 0.25, ease: "easeOut" } }}
                    className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs transition-shadow duration-300 hover:border-blue-300/80 hover:shadow-xl"
                  >
                    {/* Top Image Banner - Fixed 16:10 Aspect Ratio with smooth zoom */}
                    <Link
                      to={`/clinics/${clinic.slug || clinic.id}`}
                      className="relative block aspect-[16/10] w-full overflow-hidden bg-slate-100"
                    >
                      <img
                        src={getClinicImage(clinic)}
                        alt={clinic.name || "Veterinary clinic"}
                        className="h-full w-full object-cover object-center transition-transform duration-700 ease-out group-hover:scale-106"
                        loading="lazy"
                        onError={(event) => {
                          event.currentTarget.onerror = null;
                          event.currentTarget.src = clinicFallbackImage;
                        }}
                      />
                      <div className="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent pointer-events-none" />

                      {/* Top floating badges */}
                      <div className="absolute inset-x-3 top-3 flex items-center justify-between gap-2 pointer-events-none">
                        {clinic.city ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-white/95 backdrop-blur-md px-2.5 py-0.5 text-[11px] font-semibold text-slate-800 shadow-xs transition-transform duration-300 group-hover:-translate-y-0.5">
                            <MapPin className="h-3 w-3 text-blue-600" />
                            {clinic.city}
                          </span>
                        ) : <span />}
                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-600/95 backdrop-blur-md px-2.5 py-0.5 text-[10px] font-bold text-white shadow-xs transition-transform duration-300 group-hover:-translate-y-0.5">
                          <span className="relative flex h-1.5 w-1.5">
                            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-200 opacity-75"></span>
                            <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-white"></span>
                          </span>
                          Verified
                        </span>
                      </div>

                      {/* Clinic name and location preview overlaid at bottom */}
                      <div className="absolute inset-x-3 bottom-2.5 text-white pointer-events-none">
                        <h2 className="text-base sm:text-lg font-bold leading-snug drop-shadow-sm line-clamp-1 transition-colors group-hover:text-blue-200">
                          {valueOrDash(clinic.name)}
                        </h2>
                        <p className="mt-0.5 flex items-center gap-1 text-[11px] text-white/90 drop-shadow-xs line-clamp-1">
                          <MapPin className="h-3 w-3 shrink-0 text-blue-300" />
                          {[clinic.address, clinic.city, clinic.pincode].filter(Boolean).join(", ") || "View location details"}
                        </p>
                      </div>
                    </Link>

                    {/* Card Body */}
                    <div className="flex flex-1 flex-col p-4">
                      {/* Fees strip */}
                      <div className="flex flex-wrap items-center gap-1.5 text-[11px]">
                        {clinicFee ? (
                          <span className="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 font-semibold text-blue-700 transition-colors group-hover:bg-blue-100/70">
                            <IndianRupee className="h-3 w-3 text-blue-600" />
                            <span>Consultation: {clinicFee}</span>
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 font-medium text-slate-600">
                            Consultation available
                          </span>
                        )}
                      </div>

                      {/* Summary */}
                      {summary ? (
                        <p className="mt-2.5 line-clamp-2 text-xs leading-relaxed text-slate-500">
                          {summary}
                        </p>
                      ) : (
                        <p className="mt-2.5 line-clamp-2 text-xs text-slate-400 italic">
                          Verified veterinary clinic providing comprehensive pet healthcare and specialized diagnostics.
                        </p>
                      )}

                      {/* Feature Pills (NO VIDEO SLOT) */}
                      {quickStats.length ? (
                        <div className="mt-3 flex flex-wrap gap-1.5">
                          {quickStats.map(([count, label, Icon]) => (
                            <span
                              key={label}
                              className="inline-flex items-center gap-1 rounded-md border border-slate-100 bg-slate-50 px-2 py-0.5 text-[10px] sm:text-[11px] font-medium text-slate-600 transition-all duration-200 hover:bg-blue-50/70 hover:border-blue-200 hover:text-blue-700"
                            >
                              <Icon className="h-3 w-3 text-blue-600 shrink-0" />
                              <span className="font-bold text-slate-900">{count}</span> {label.replace(/^\d+\s*/, '')}
                            </span>
                          ))}
                        </div>
                      ) : null}

                      {/* Action Footer */}
                      <div className="mt-auto flex items-center justify-between gap-3 pt-3.5 border-t border-slate-100">
                        <Link
                          to={`/clinics/${clinic.slug || clinic.id}`}
                          className="inline-flex items-center gap-1 text-xs font-bold text-blue-700 transition-colors hover:text-blue-800"
                        >
                          View Details
                          <ArrowRight className="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-1" />
                        </Link>

                        <motion.button
                          type="button"
                          whileHover={{ scale: 1.03 }}
                          whileTap={{ scale: 0.96 }}
                          onClick={() => {
                            openBookingModal({
                              orderType: "appointment",
                              clinic: { ...clinic, doctors: entry.doctors || [], specialized_packages: entry.specialized_packages || [] },
                              doctor: Array.isArray(entry.doctors) && entry.doctors[0] ? entry.doctors[0] : null,
                              package: null,
                            });
                          }}
                          className="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-blue-500/20 transition-all hover:from-blue-700 hover:to-blue-800 cursor-pointer"
                        >
                          <CalendarDays className="h-3.5 w-3.5" />
                          Book Visit
                        </motion.button>
                      </div>
                    </div>
                  </motion.div>
                );
              })}
            </motion.div>
          )}
        </div>
      </section>

      <ClinicLeadForm />

      {bookingModal.isOpen && (
        <ModernDoctorBooking
          onClose={closeBookingModal}
          orderType={bookingModal.orderType}
          initialClinic={bookingModal.clinic}
          initialDoctor={bookingModal.doctor}
          initialPackage={bookingModal.package}
        />
      )}
    </>
  );
}

function ClinicDetail() {
  const navigate = useNavigate();
  const { clinicSlug } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();
  const isDay = useIsDayTime();
  const requestedDoctorId = searchParams.get("doctor_id");
  const [entry, setEntry] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [bookingModal, setBookingModal] = useState({
    isOpen: false,
    orderType: "appointment",
    clinic: null,
    doctor: null,
    package: null,
  });
  const [copiedLink, setCopiedLink] = useState(false);
  const [packageCategory, setPackageCategory] = useState("all");
  const [packagePet, setPackagePet] = useState("all");
  const [heroMediaType, setHeroMediaType] = useState("image");
  const [hasUserManuallySwitched, setHasUserManuallySwitched] = useState(false);
  const heroVideoRef = useRef(null);

  useEffect(() => {
    setHeroMediaType("image");
    setHasUserManuallySwitched(false);
  }, [clinicSlug]);

  useEffect(() => {
    const videoUrl = entry?.clinic?.clinic_video_url;
    if (!videoUrl || hasUserManuallySwitched) return;

    const timer = setTimeout(() => {
      setHeroMediaType("video");
      if (heroVideoRef.current) {
        heroVideoRef.current.play().catch(() => {});
      }
    }, 2000);

    return () => clearTimeout(timer);
  }, [entry?.clinic?.clinic_video_url, hasUserManuallySwitched, clinicSlug]);

  useEffect(() => {
    if (heroMediaType === "video") {
      heroVideoRef.current?.play().catch(() => {});
    } else {
      heroVideoRef.current?.pause();
    }
  }, [heroMediaType]);

  const handleSelectMedia = (type) => {
    setHasUserManuallySwitched(true);
    setHeroMediaType(type);
  };

  const rawPackages = entry?.specialized_packages || [];
  const packageItems = useMemo(() => extractPackageItems(rawPackages), [rawPackages]);
  const packageCategories = useMemo(() => {
    const cats = new Set(packageItems.map((p) => p.category));
    return Array.from(cats);
  }, [packageItems]);
  const filteredPackages = useMemo(() => {
    return packageItems.filter((item) => {
      if (packageCategory !== "all" && item.category !== packageCategory) return false;
      if (packagePet !== "all" && item.petType !== packagePet) return false;
      return true;
    });
  }, [packageItems, packageCategory, packagePet]);

  const displayPackages = useMemo(() => {
    if (!filteredPackages.length) return [];
    if (filteredPackages.length < 4) {
      return [
        ...filteredPackages,
        ...filteredPackages,
        ...filteredPackages,
        ...filteredPackages,
      ];
    }
    return [...filteredPackages, ...filteredPackages];
  }, [filteredPackages]);

  const packageScrollRef = useRef(null);

  useEffect(() => {
    const el = packageScrollRef.current;
    if (!el || !filteredPackages.length) return;

    let animationFrameId;
    let isPaused = false;

    const onEnter = () => {
      isPaused = true;
    };
    const onLeave = () => {
      isPaused = false;
    };
    const onTouchStart = () => {
      isPaused = true;
    };
    const onTouchEnd = () => {
      isPaused = false;
    };

    el.addEventListener("mouseenter", onEnter);
    el.addEventListener("mouseleave", onLeave);
    el.addEventListener("touchstart", onTouchStart, { passive: true });
    el.addEventListener("touchend", onTouchEnd, { passive: true });

    let lastTime = null;
    const pixelsPerSecond = 35; // gentle, readable auto-drift speed

    const step = (timestamp) => {
      if (!lastTime) lastTime = timestamp;
      const delta = (timestamp - lastTime) / 1000;
      lastTime = timestamp;

      if (!isPaused && el) {
        const halfWidth = el.scrollWidth / 2;
        if (halfWidth > 0 && el.scrollLeft >= halfWidth) {
          el.scrollLeft -= halfWidth;
        } else {
          el.scrollLeft += pixelsPerSecond * Math.min(delta, 0.1);
        }
      }
      animationFrameId = requestAnimationFrame(step);
    };

    animationFrameId = requestAnimationFrame(step);

    return () => {
      cancelAnimationFrame(animationFrameId);
      el.removeEventListener("mouseenter", onEnter);
      el.removeEventListener("mouseleave", onLeave);
      el.removeEventListener("touchstart", onTouchStart);
      el.removeEventListener("touchend", onTouchEnd);
    };
  }, [filteredPackages]);

  const handleShare = () => {
    if (navigator.share) {
      navigator
        .share({
          title: entry?.clinic?.name || "Veterinary Clinic",
          text: `Book an appointment at ${entry?.clinic?.name || "this clinic"} on SnoutIQ`,
          url: window.location.href,
        })
        .catch(() => {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href);
      setCopiedLink(true);
      setTimeout(() => setCopiedLink(false), 2000);
    }
  };

  const openBookingModal = ({ orderType, doctor = null, clinic: targetClinic = null, package: selectedPackage = null }) => {
    const currentClinic = entry?.clinic || {};
    const currentDoctors = entry?.doctors || [];
    const fullClinic = targetClinic || {
      ...currentClinic,
      doctors: currentDoctors,
      specialized_packages: entry?.specialized_packages || [],
    };

    const currentParams = new URLSearchParams(searchParams);
    currentParams.set("type", orderType || "appointment");
    currentParams.set("step", "describe");
    setSearchParams(currentParams);

    setBookingModal({
      isOpen: true,
      orderType: orderType || "appointment",
      clinic: fullClinic,
      doctor: doctor || (currentDoctors.length > 0 ? currentDoctors[0] : null),
      package: selectedPackage,
    });
  };

  const closeBookingModal = () => {
    document.body.style.overflow = "";
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.delete("step");
    currentParams.delete("type");
    currentParams.delete("orderType");
    setSearchParams(currentParams, { replace: true });
    setBookingModal({
      isOpen: false,
      orderType: "appointment",
      clinic: null,
      doctor: null,
      package: null,
    });
  };

  // Sync bookingModal state with URL search params (e.g. browser back button)
  useEffect(() => {
    const step = searchParams.get("step");
    if (!step && bookingModal.isOpen) {
      setBookingModal((prev) => ({ ...prev, isOpen: false }));
    }
  }, [searchParams]);

  const loadDoctorFallbackEntry = async () => {
    if (requestedDoctorId) {
      const { data } = await axiosClient.get("/exported_from_excell_doctors", {
        params: { doctor_id: requestedDoctorId },
      });
      const doctor = data?.data;
      if (doctor) {
        return doctorToClinicFallbackEntry(doctor, clinicSlug);
      }
    }

    if (/^\d+$/.test(String(clinicSlug || ""))) {
      try {
        const { data } = await axiosClient.get(`/clinics/${clinicSlug}/doctors`);
        const doctors = Array.isArray(data?.doctors) ? data.doctors : [];
        if (doctors.length > 0) {
          const doctor = doctors[0];
          return doctorToClinicFallbackEntry(
            {
              ...doctor,
              doctor_name: doctor.doctor_name || doctor.name,
              vet_registeration_id: clinicSlug,
              clinic_name: data?.clinic?.name,
              clinic_address: data?.clinic?.address,
            },
            clinicSlug
          );
        }
      } catch {
        // Try the public exported doctor feed below.
      }

      const { data } = await axiosClient.get("/exported_from_excell_doctors");
      const doctors = Array.isArray(data?.data) ? data.data : [];
      const doctor = doctors.find(
        (item) =>
          String(item?.vet_registeration_id || item?.clinic_id || "") ===
          String(clinicSlug)
      );
      if (doctor) {
        return doctorToClinicFallbackEntry(doctor, clinicSlug);
      }
    }

    return null;
  };

  useEffect(() => {
    let cancelled = false;

    const loadClinic = async () => {
      setIsLoading(true);
      setError("");
      setEntry(null);

      try {
        const { data } = await axiosClient.get(
          `/clinic-pages/${encodeURIComponent(clinicSlug)}`
        );

        const nextEntry = data?.data || null;
        if (isClinicEntryUsable(nextEntry)) {
          if (!cancelled) setEntry(nextEntry);
          return;
        }

        const fallbackEntry = await loadDoctorFallbackEntry();
        if (!cancelled) {
          if (fallbackEntry) {
            setEntry(fallbackEntry);
          } else {
            setError("Clinic profile is incomplete and no connected doctor was found.");
          }
        }
      } catch (err) {
        let fallbackEntry = null;
        try {
          fallbackEntry = await loadDoctorFallbackEntry();
        } catch {
          fallbackEntry = null;
        }

        if (!cancelled) {
          if (fallbackEntry) {
            setEntry(fallbackEntry);
          } else {
            setError(
              err?.response?.data?.message ||
                "Unable to load this clinic right now."
            );
          }
        }
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    loadClinic();
    return () => {
      cancelled = true;
    };
  }, [clinicSlug, requestedDoctorId]);

  if (isLoading) {
    return (
      <section className="flex min-h-[70vh] items-center justify-center bg-slate-50">
        <div className="flex flex-col items-center gap-3 text-center">
          <Loader2 className="h-10 w-10 animate-spin text-blue-600" />
          <p className="text-sm font-medium text-slate-500">Loading clinic details...</p>
        </div>
      </section>
    );
  }

  if (error || !entry) {
    return (
      <section className="bg-slate-50 py-16">
        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
          <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700 shadow-xs">
            <p className="font-semibold text-red-900">Clinic Unavailable</p>
            <p className="mt-1 text-sm">{error || "The requested clinic could not be found."}</p>
          </div>
        </div>
      </section>
    );
  }

  const clinic = entry.clinic || {};
  const doctors = entry.doctors || [];
  const { serviceRows, machineRows } = splitByMainService(
    entry.services || [],
    entry.machinery || []
  );
  const packages = entry.specialized_packages || [];
  const vetAtHomeServices = entry.vet_at_home_services || [];
  const clinicAvailability = entry.clinic_availability || [];
  const videoSchedules = entry.video_schedules || [];
  const firstDoc = doctors.length > 0 ? doctors[0] : null;
  const clinicFee = getClinicVisitFee(entry);
  const daySlots = clinicAvailability.filter((slot) => !isNightSlot(slot));
  const nightSlots = clinicAvailability.filter(isNightSlot);
  const locationUrl = mapUrlForClinic(clinic);
  const fullAddress =
    clinic.formatted_address || clinic.address || [clinic.city, clinic.pincode].filter(Boolean).join(" ");
  const hasClinicImage = Boolean(clinic.clinic_image_url || clinic.image);
  const hasClinicVideo = Boolean(clinic.clinic_video_url);
  const detailSummary = getClinicSummary(clinic);

  return (
    <>
      {/* Top Navigation & Breadcrumbs Bar */}
      <div className="border-b border-slate-200/80 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-2.5 sm:px-6 lg:px-8">
          <nav aria-label="Breadcrumb" className="flex items-center gap-1.5 text-xs text-slate-500 overflow-x-auto py-0.5">
            <button
              type="button"
              onClick={() => {
                if (window.history.length > 1) {
                  navigate(-1);
                } else {
                  navigate("/clinics");
                }
              }}
              className="inline-flex items-center gap-1 font-medium text-slate-600 hover:text-blue-600 transition-colors shrink-0 cursor-pointer"
            >
              <ArrowLeft className="h-3.5 w-3.5" />
              <span>Back</span>
            </button>
            <ChevronRight className="h-3.5 w-3.5 text-slate-300 shrink-0" />
            <Link
              to="/clinics"
              className="font-medium text-slate-600 hover:text-blue-600 transition-colors shrink-0"
            >
              All Clinics
            </Link>
            <ChevronRight className="h-3.5 w-3.5 text-slate-300 shrink-0" />
            {clinic.city && (
              <>
                <span className="text-slate-600 shrink-0">{clinic.city}</span>
                <ChevronRight className="h-3.5 w-3.5 text-slate-300 shrink-0" />
              </>
            )}
            <span className="font-semibold text-slate-900 truncate max-w-[180px] sm:max-w-none">
              {valueOrDash(clinic.name)}
            </span>
          </nav>

          <div className="flex items-center gap-2 sm:gap-2.5 shrink-0">
            <span className="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200/60">
              <ShieldCheck className="h-3.5 w-3.5 text-emerald-600" />
              Verified Partner
            </span>
            <button
              type="button"
              onClick={handleShare}
              className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-all shadow-2xs"
            >
              <Share2 className="h-3.5 w-3.5" />
              <span>{copiedLink ? "Link Copied!" : "Share"}</span>
            </button>
          </div>
        </div>
      </div>

      {/* Hero Section */}
      <section className="relative border-b border-slate-200/80 bg-gradient-to-b from-blue-50/40 via-white to-slate-50/50 py-6 lg:py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
            {/* Left Column: Clinic Header, Meta & CTAs */}
            <div>
              {/* Trust Badges Pill Strip */}
              <div className="flex flex-wrap items-center gap-1.5">
                <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-900 border border-amber-200/70 shadow-2xs">
                  <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                  {clinic.rating ? `${clinic.rating} Rating` : "5.0 Rating"}
                  {clinic.user_ratings_total ? ` (${clinic.user_ratings_total}+ reviews)` : " (50+ reviews)"}
                </span>
                <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200/60">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                  Open Today
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 border border-blue-200/60">
                  <Building2 className="h-3 w-3 text-blue-600" />
                  Pet Healthcare Facility
                </span>
              </div>

              {/* Clinic Name */}
              <h1 className="mt-3 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">
                {valueOrDash(clinic.name)}
              </h1>

              {/* Subtitle / Bio */}
              {detailSummary ? (
                <p className="mt-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed font-normal max-w-2xl">
                  {detailSummary}
                </p>
              ) : (
                <p className="mt-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed font-normal max-w-2xl">
                  Providing professional diagnostic, surgical, and compassionate veterinary healthcare services for pets.
                </p>
              )}

              {/* Quick Info Strip */}
              <div className="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                {clinic.city || clinic.pincode ? (
                  <div className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100/90 px-2.5 py-1 font-medium text-slate-700">
                    <MapPin className="h-3.5 w-3.5 text-blue-600 shrink-0" />
                    <span>{[clinic.city, clinic.pincode].filter(Boolean).join(" - ")}</span>
                  </div>
                ) : null}
                {clinicFee ? (
                  <div className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-800 border border-emerald-200/50">
                    <IndianRupee className="h-3 w-3 text-emerald-600 shrink-0" />
                    <span>Visit fee {clinicFee}</span>
                  </div>
                ) : null}
              </div>

              {/* High-Conversion Primary CTAs */}
              <div className="mt-6 space-y-3">
                <div className="flex flex-wrap items-stretch gap-2.5">
                  <button
                    type="button"
                    onClick={() => openBookingModal({ orderType: "appointment" })}
                    className="group relative inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-xs sm:text-sm font-bold text-white shadow-md shadow-blue-600/25 hover:bg-blue-700 active:scale-98 transition-all sm:flex-initial flex-1"
                  >
                    <CalendarDays className="h-4 w-4 transition-transform group-hover:scale-110" />
                    <span>Book In-Clinic Appointment</span>
                  </button>

                  {doctors.length > 0 && (
                    <button
                      type="button"
                      onClick={() => openBookingModal({ orderType: "video_consult", doctor: doctors[0] })}
                      className="group inline-flex items-center justify-center gap-2 rounded-xl border-2 border-blue-600/25 bg-white px-4.5 py-3 text-xs sm:text-sm font-bold text-blue-700 shadow-2xs hover:bg-blue-50 hover:border-blue-600/40 active:scale-98 transition-all sm:flex-initial flex-1 p-2"
                    >
                      <Video className="h-4 w-4 text-blue-600 transition-transform group-hover:scale-110" />
                      <span>Book Video Calling</span>
                    </button>
                  )}
                </div>

                {/* Trust Guarantee Checklist */}
                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 pt-0.5 text-[11px] font-medium text-slate-500">
                  <span className="flex items-center gap-1">
                    <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                    Instant Slot Confirmation
                  </span>
                  <span className="flex items-center gap-1">
                    <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                    Zero Convenience Fee
                  </span>
                  <span className="flex items-center gap-1">
                    <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                    Verified Licensed Veterinarians
                  </span>
                </div>
              </div>
            </div>

            {/* Right Column: Interactive Media Showcase (Auto-sliding Photo & Video Tour) */}
            <div className="relative">
              <div className="relative overflow-hidden rounded-2xl sm:rounded-3xl border border-slate-200/90 bg-slate-950 shadow-lg transition-all">
                {/* Floating Media Switcher Tabs (Only if video exists) */}
                {hasClinicVideo && (
                  <div className="absolute top-3 right-3 z-20 flex items-center rounded-full bg-black/65 p-0.5 backdrop-blur-md border border-white/20 shadow-md">
                    <button
                      type="button"
                      onClick={() => handleSelectMedia("image")}
                      className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold transition-all cursor-pointer ${
                        heroMediaType === "image"
                          ? "bg-white text-slate-950 shadow-xs"
                          : "text-white/80 hover:text-white"
                      }`}
                    >
                      <Camera className="h-3 w-3" />
                      <span>Photo</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => handleSelectMedia("video")}
                      className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold transition-all cursor-pointer ${
                        heroMediaType === "video"
                          ? "bg-white text-slate-950 shadow-xs"
                          : "text-white/80 hover:text-white"
                      }`}
                    >
                      <Video className="h-3 w-3" />
                      <span>Video Tour</span>
                    </button>
                  </div>
                )}

                {/* Sliding Horizontal Track */}
                <div className="relative aspect-[16/11] w-full overflow-hidden">
                  <div
                    className="flex h-full w-full transition-transform duration-700 ease-in-out"
                    style={{
                      transform: heroMediaType === "video" && hasClinicVideo ? "translateX(-100%)" : "translateX(0%)",
                    }}
                  >
                    {/* Slide 1: Photo View */}
                    <div className="relative h-full w-full shrink-0 overflow-hidden group">
                      <img
                        src={getClinicImage(clinic)}
                        alt={clinic.name || "Veterinary clinic"}
                        className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                        onError={(event) => {
                          event.currentTarget.onerror = null;
                          event.currentTarget.src = clinicFallbackImage;
                        }}
                      />
                      <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent pointer-events-none" />

                      {/* Top Left Badge */}
                      <div className="absolute top-3 left-3 rounded-full bg-black/60 backdrop-blur-md px-2.5 py-0.5 text-[11px] font-semibold text-white border border-white/20 flex items-center gap-1 shadow-xs">
                        <Camera className="h-3 w-3 text-blue-300" />
                        <span>Clinic Facility</span>
                      </div>

                      {/* Bottom Caption & Quick Watch Video CTA */}
                      <div className="absolute bottom-3.5 left-3.5 right-3.5 flex items-end justify-between text-white">
                        <div>
                          <p className="text-sm font-bold drop-shadow-sm line-clamp-1">{clinic.name}</p>
                          <p className="text-[11px] text-white/80 drop-shadow-sm line-clamp-1">{clinic.city || "Verified Pet Clinic"}</p>
                        </div>

                        {hasClinicVideo && (
                          <button
                            type="button"
                            onClick={() => handleSelectMedia("video")}
                            className="inline-flex items-center gap-1 rounded-full bg-white/25 backdrop-blur-md border border-white/35 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-white hover:text-slate-950 transition-all shadow-xs active:scale-95 cursor-pointer"
                          >
                            <Video className="h-3 w-3 text-blue-300" />
                            <span>Watch Video Tour</span>
                          </button>
                        )}
                      </div>
                    </div>

                    {/* Slide 2: Video Tour View */}
                    {hasClinicVideo && (
                      <div className="relative h-full w-full shrink-0 bg-black">
                        <video
                          ref={heroVideoRef}
                          src={clinic.clinic_video_url}
                          poster={getClinicImage(clinic)}
                          className="h-full w-full object-cover"
                          autoPlay
                          muted
                          loop
                          playsInline
                          controls
                        />
                        <div className="absolute top-3 left-3 rounded-full bg-black/60 backdrop-blur-md px-2.5 py-0.5 text-[11px] font-semibold text-white border border-white/20 flex items-center gap-1 shadow-xs pointer-events-none">
                          <Video className="h-3 w-3 text-blue-400" />
                          <span>Facility Video Tour</span>
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {/* Bottom Slide Indicators (Only if video exists) */}
                {hasClinicVideo && (
                  <div className="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 rounded-full bg-black/50 px-2 py-0.5 backdrop-blur-md border border-white/15 pointer-events-auto">
                    <button
                      type="button"
                      onClick={() => handleSelectMedia("image")}
                      className={`h-1.5 rounded-full transition-all cursor-pointer ${
                        heroMediaType === "image" ? "w-4 bg-white shadow-xs" : "w-1.5 bg-white/50 hover:bg-white/80"
                      }`}
                      aria-label="View clinic photo"
                    />
                    <button
                      type="button"
                      onClick={() => handleSelectMedia("video")}
                      className={`h-1.5 rounded-full transition-all cursor-pointer ${
                        heroMediaType === "video" ? "w-4 bg-white shadow-xs" : "w-1.5 bg-white/50 hover:bg-white/80"
                      }`}
                      aria-label="View clinic video tour"
                    />
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Main Details Section */}
      <section className="bg-slate-50/70 py-8 sm:py-10">
        <div className="mx-auto grid max-w-7xl gap-6 sm:gap-8 px-4 sm:px-6 lg:grid-cols-[1.25fr_0.75fr] lg:px-8">
          {/* Main Left Content */}
          <div className="min-w-0 space-y-6 sm:space-y-8">
            {/* Consultation Modes Comparison (In-Clinic vs Video Consult) */}
            <DetailSection
              title="Consultation Options & Fees"
              subtitle="Choose between in-person clinic visit or instant tele-consultation"
              icon={Building2}
            >
              <div className="grid gap-4 sm:grid-cols-2">
                {/* In-Clinic Card */}
                <div className="relative flex flex-col justify-between rounded-2xl border-2 border-blue-600/20 bg-gradient-to-b from-blue-50/30 to-white p-5 sm:p-6 shadow-xs transition-all hover:border-blue-600/40">
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-bold text-blue-800 uppercase tracking-wider">
                        In-Person Visit
                      </span>
                      <Building2 className="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                      <h3 className="text-base font-bold text-slate-900">In-Clinic Consultation</h3>
                      <p className="text-xs text-slate-500 mt-1 leading-relaxed">
                        Detailed physical checkup, emergency care, pathology tests & immediate treatment at clinic.
                      </p>
                    </div>
                    <div className="pt-2">
                      <div className="flex items-center gap-2">
                        <p className="text-xl sm:text-2xl font-black text-slate-900">
                          {clinicFee || "Fee on request"}
                        </p>
                      </div>
                      <p className="text-xs font-medium text-slate-500 mt-1">
                        Pay directly at clinic reception during consultation
                      </p>
                    </div>
                    <ul className="space-y-1.5 pt-2 text-xs text-slate-600">
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>Comprehensive physical vitals examination</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>On-site medication, injections & diagnostic tests</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>Reserved timeslot with zero clinic waiting</span>
                      </li>
                    </ul>
                  </div>

                  <div className="pt-5">
                    <button
                      type="button"
                      onClick={() => openBookingModal({ orderType: "appointment" })}
                      className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-blue-700 active:scale-98 transition-all cursor-pointer"
                    >
                      <CalendarDays className="h-4 w-4" />
                      Book In-Clinic Visit
                    </button>
                  </div>
                </div>

                {/* Online Video Consult Card */}
                <div className="relative flex flex-col justify-between rounded-2xl border-2 border-slate-200 bg-white p-5 sm:p-6 shadow-xs transition-all hover:border-slate-300">
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-800 uppercase tracking-wider">
                        Fastest · 15 Mins
                      </span>
                      <Video className="h-5 w-5 text-emerald-600" />
                    </div>
                    <div>
                      <h3 className="text-base font-bold text-slate-900">Live Video Consultation</h3>
                      <p className="text-xs text-slate-500 mt-1 leading-relaxed">
                        Connect directly with licensed veterinarians via secure video call from home.
                      </p>
                    </div>
                    <div className="pt-2">
                      <div className="flex items-center gap-2">
                        <p className="text-xl sm:text-2xl font-black text-slate-900">
                          {isDay
                            ? (formatMoney(firstDoc?.video_day_rate) || "₹499")
                            : (formatMoney(firstDoc?.video_night_rate) || formatMoney(firstDoc?.video_day_rate) || "₹650")}
                        </p>
                        <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${isDay ? 'bg-amber-100 text-amber-800 border border-amber-200/70' : 'bg-indigo-100 text-indigo-800 border border-indigo-200/70'}`}>
                          {isDay ? "☀️ Day (8 AM - 8 PM)" : "🌙 Night (8 PM - 8 AM)"}
                        </span>
                      </div>
                      {firstDoc?.video_day_rate && firstDoc?.video_night_rate ? (
                        <p className="text-xs font-medium text-slate-500 mt-1">
                          Day: {formatMoney(firstDoc.video_day_rate)} · Night: {formatMoney(firstDoc.video_night_rate)}
                        </p>
                      ) : (
                        <p className="text-xs font-medium text-slate-500 mt-1">
                          Instant 15-20 min video session
                        </p>
                      )}
                    </div>
                    <ul className="space-y-1.5 pt-2 text-xs text-slate-600">
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>Instant connect from home in 15 mins</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>Official digital prescription on WhatsApp & App</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>Ideal for diet, skin allergies & second opinions</span>
                      </li>
                    </ul>
                  </div>

                  <div className="pt-5">
                    <button
                      type="button"
                      onClick={() => openBookingModal({ orderType: "video_consult", doctor: doctors[0] || null })}
                      className="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-xs sm:text-sm font-bold text-slate-800 hover:bg-slate-100 hover:border-slate-400 active:scale-98 transition-all cursor-pointer"
                    >
                      <Video className="h-4 w-4 text-blue-600" />
                      Book Video Calling
                    </button>
                  </div>
                </div>
              </div>
            </DetailSection>

            {/* Doctors Section */}
            {doctors.length ? (
              <DetailSection
                title="Veterinary Doctors"
                subtitle="Stationed practitioners available at this clinic"
                icon={Stethoscope}
                badge={`${doctors.length} Specialist${doctors.length > 1 ? "s" : ""}`}
              >
                <div className="space-y-3.5">
                  {doctors.map((doctor) => {
                    const specializations = parseList(
                      doctor.specialization_select_all_that_apply
                    );
                    const languages = parseList(doctor.languages_spoken);
                    const activeDocVideo = isDay
                      ? formatMoney(doctor.video_day_rate || doctor.doctors_price || "499")
                      : formatMoney(doctor.video_night_rate || doctor.video_day_rate || doctor.doctors_price || "649");
                    const activeDocClinic = formatMoney(doctor.doctors_price) || clinicFee || "₹500";

                    const doctorFees = [
                      ["Clinic Visit", activeDocClinic],
                      ["Video Call", activeDocVideo],
                      isDay && doctor.video_night_rate ? ["Night Video", formatMoney(doctor.video_night_rate)] : null,
                      !isDay && doctor.video_day_rate ? ["Day Video", formatMoney(doctor.video_day_rate)] : null,
                    ].filter(Boolean).filter(([, value]) => value);

                    const doctorInitials = getDoctorInitials(doctor.doctor_name);
                    const docImg = doctorImageUrl(doctor);

                    return (
                      <div
                        key={doctor.id}
                        className="rounded-xl sm:rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-xs transition-all hover:border-blue-200 hover:shadow-sm"
                      >
                        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3.5">
                          <div className="flex items-start gap-3.5">
                            {/* Doctor Avatar */}
                            {docImg ? (
                              <div className="relative flex h-12 w-12 sm:h-13 sm:w-13 shrink-0 rounded-xl sm:rounded-2xl overflow-hidden border border-slate-200 shadow-xs bg-slate-100">
                                <img
                                  src={docImg}
                                  alt={formatDoctorName(doctor.doctor_name)}
                                  className="h-full w-full object-cover"
                                  onError={(e) => {
                                    e.currentTarget.style.display = "none";
                                  }}
                                />
                                <div className="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 sm:h-4.5 sm:w-4.5 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white text-white">
                                  <Check className="h-2.5 w-2.5 stroke-[3]" />
                                </div>
                              </div>
                            ) : (
                              <div className="relative flex h-12 w-12 sm:h-13 sm:w-13 shrink-0 items-center justify-center rounded-xl sm:rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 font-bold text-white shadow-xs text-base sm:text-lg">
                                {doctorInitials}
                                <div className="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 sm:h-4.5 sm:w-4.5 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white text-white">
                                  <Check className="h-2.5 w-2.5 stroke-[3]" />
                                </div>
                              </div>
                            )}

                            {/* Doctor Info */}
                            <div>
                              <div className="flex items-center gap-1.5">
                                <h3 className="text-base font-bold text-slate-950">
                                  {formatDoctorName(doctor.doctor_name)}
                                </h3>
                                <BadgeCheck className="h-4 w-4 text-blue-600" />
                              </div>

                              {doctor.degree || doctor.years_of_experience ? (
                                <p className="mt-0.5 text-[11px] font-semibold text-blue-700">
                                  {doctor.degree || "Veterinary Surgeon"}
                                  {doctor.degree && doctor.years_of_experience ? " · " : ""}
                                  {doctor.years_of_experience
                                    ? `${doctor.years_of_experience} Years Exp`
                                    : ""}
                                </p>
                              ) : null}

                              {/* Specialization Chips */}
                              {specializations.length ? (
                                <div className="mt-2 flex flex-wrap gap-1">
                                  {specializations.map((item) => (
                                    <span
                                      key={item}
                                      className="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] sm:text-[11px] font-medium text-slate-700"
                                    >
                                      {item}
                                    </span>
                                  ))}
                                </div>
                              ) : null}

                              {/* Spoken Languages */}
                              {languages.length ? (
                                <p className="mt-1.5 text-[11px] text-slate-500">
                                  <span className="font-semibold text-slate-600">Languages:</span>{" "}
                                  {languages.join(", ")}
                                </p>
                              ) : null}
                            </div>
                          </div>

                          {/* Fee Badges */}
                          {doctorFees.length ? (
                            <div className="flex sm:flex-col flex-wrap gap-1.5 shrink-0 sm:text-right">
                              {doctorFees.map(([label, value]) => (
                                <div key={label} className="rounded-lg bg-slate-50 border border-slate-100 px-2 py-0.5 text-[11px]">
                                  <span className="text-slate-500">{label}: </span>
                                  <span className="font-bold text-slate-900">{value}</span>
                                </div>
                              ))}
                            </div>
                          ) : null}
                        </div>

                        {/* Direct Dual CTAs for Doctor */}
                        <div className="mt-3.5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3.5">
                          <button
                            type="button"
                            onClick={() => openBookingModal({ orderType: "video_consult", doctor })}
                            className="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 active:scale-95 transition-all"
                          >
                            <Video className="h-3.5 w-3.5" />
                            <span>Book Video Calling</span>
                          </button>
                          <button
                            type="button"
                            onClick={() => openBookingModal({ orderType: "appointment", doctor })}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:border-blue-600 hover:text-blue-600 active:scale-95 transition-all shadow-2xs"
                          >
                            <CalendarDays className="h-3.5 w-3.5 text-blue-600" />
                            <span>Book In-Clinic Visit</span>
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </DetailSection>
            ) : null}

            {/* Clinic Available Slots */}
            {daySlots.length ? (
              <DetailSection
                title="Clinic Operating Hours & Slots"
                subtitle="Day-wise scheduled consultation slots"
                icon={Clock}
              >
                <SlotList slots={daySlots} />
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl sm:rounded-2xl border border-blue-200/80 bg-gradient-to-r from-blue-50/90 to-indigo-50/60 p-4 sm:p-5">
                  <div>
                    <p className="text-xs sm:text-sm font-bold text-blue-950">Reserve Your Appointment Today</p>
                    <p className="text-[11px] text-blue-700 mt-0.5">
                      Select your preferred date & time slot with instant WhatsApp confirmation
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => openBookingModal({ orderType: "appointment" })}
                    className="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition-all active:scale-95 cursor-pointer"
                  >
                    <CalendarDays className="h-3.5 w-3.5" />
                    <span>Book In-Clinic Appointment</span>
                  </button>
                </div>
              </DetailSection>
            ) : null}

            {nightSlots.length ? (
              <DetailSection
                title="Emergency & Night Slots"
                subtitle="After-hours emergency veterinary care availability"
                icon={Clock}
              >
                <SlotList slots={nightSlots} />
              </DetailSection>
            ) : null}

            {/* Clinical Services */}
            {serviceRows.length ? (
              <DetailSection
                title="Clinical Services"
                subtitle="Diagnostic, medical, and care treatments available at this clinic"
                icon={Activity}
                badge={`${serviceRows.length} Services`}
              >
                <div className="grid gap-3 sm:grid-cols-2">
                  {serviceRows.map((service) => (
                    <div
                      key={service.id}
                      className="flex flex-col justify-between rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs transition-all hover:border-blue-200 hover:shadow-xs"
                    >
                      <div>
                        <div className="flex items-start justify-between gap-2">
                          <h3 className="font-bold text-slate-900 text-xs sm:text-sm">
                            {valueOrDash(service.name)}
                          </h3>
                          {formatMoney(service.price) ? (
                            <span className="rounded-lg bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-200/60 shrink-0">
                              {formatMoney(service.price)}
                            </span>
                          ) : null}
                        </div>
                        {service.description || service.pet_type ? (
                          <p className="mt-1 text-[11px] text-slate-500 leading-relaxed">
                            {service.description || service.pet_type}
                          </p>
                        ) : null}
                      </div>
                      {service.duration ? (
                        <div className="mt-2.5 flex items-center gap-1 text-[10px] sm:text-[11px] font-medium text-slate-400">
                          <Clock className="h-3 w-3" />
                          <span>Duration: {service.duration} mins</span>
                        </div>
                      ) : null}
                    </div>
                  ))}
                </div>
              </DetailSection>
            ) : null}

            {/* Diagnostic Machinery & Labs */}
            {machineRows.length ? (
              <DetailSection
                title="Machinery & Diagnostic Equipment"
                subtitle="Advanced in-house lab instruments used for pet diagnosis"
                icon={Wrench}
                badge={`${machineRows.length} Units`}
              >
                <div className="grid gap-3 sm:grid-cols-2">
                  {machineRows.map((machine) => (
                    <div
                      key={machine.id}
                      className="flex flex-col justify-between rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs transition-all hover:border-blue-200 hover:shadow-xs"
                    >
                      <div>
                        <div className="flex items-start justify-between gap-2">
                          <h3 className="font-bold text-slate-900 text-xs sm:text-sm">
                            {valueOrDash(machine.name)}
                          </h3>
                          {formatMoney(machine.price) ? (
                            <span className="rounded-lg bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700 border border-blue-200/60 shrink-0">
                              {formatMoney(machine.price)}
                            </span>
                          ) : null}
                        </div>
                        {machine.description || machine.pet_type ? (
                          <p className="mt-1 text-[11px] text-slate-500 leading-relaxed">
                            {machine.description || machine.pet_type}
                          </p>
                        ) : null}
                      </div>
                    </div>
                  ))}
                </div>
              </DetailSection>
            ) : null}
          </div>

          {/* Right Sidebar */}
          <aside className="space-y-5">
            {/* Location & Directions Card */}
            {fullAddress || clinic.city || clinic.pincode || clinic.lat || clinic.rating ? (
              <section className="rounded-xl sm:rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
                <div className="flex items-center gap-2.5 border-b border-slate-100 pb-3.5">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100/60 shrink-0">
                    <MapPin className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-base font-bold text-slate-900">Clinic Location</h2>
                    <p className="text-[11px] text-slate-500">Address & directions</p>
                  </div>
                </div>

                {fullAddress ? (
                  <p className="mt-3.5 text-xs sm:text-sm text-slate-700 leading-relaxed font-medium">
                    {fullAddress}
                  </p>
                ) : null}

                <div className="mt-3.5 space-y-1.5 rounded-xl bg-slate-50 p-3 text-[11px] text-slate-600">
                  {clinic.city ? (
                    <div className="flex justify-between">
                      <span className="text-slate-400">City:</span>
                      <span className="font-semibold text-slate-800">{clinic.city}</span>
                    </div>
                  ) : null}
                  {clinic.pincode ? (
                    <div className="flex justify-between">
                      <span className="text-slate-400">Pincode:</span>
                      <span className="font-semibold text-slate-800">{clinic.pincode}</span>
                    </div>
                  ) : null}
                  {clinic.rating ? (
                    <div className="flex justify-between">
                      <span className="text-slate-400">Verified Rating:</span>
                      <span className="font-semibold text-amber-600 flex items-center gap-1">
                        <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                        {clinic.rating} {clinic.user_ratings_total ? `(${clinic.user_ratings_total} reviews)` : ""}
                      </span>
                    </div>
                  ) : null}
                </div>

                <div className="mt-4 space-y-2">
                  {locationUrl ? (
                    <a
                      href={locationUrl}
                      target="_blank"
                      rel="noreferrer"
                      className="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2.5 text-xs font-semibold text-white hover:bg-blue-700 shadow-xs transition-all"
                    >
                      <Navigation className="h-3.5 w-3.5" />
                      <span>Get Directions on Google Maps</span>
                      <ExternalLink className="h-3 w-3 opacity-70" />
                    </a>
                  ) : null}
                </div>
              </section>
            ) : null}

            {/* Video Hours Card */}
            {videoSchedules.length ? (
              <section className="rounded-xl sm:rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
                <div className="flex items-center gap-2.5 border-b border-slate-100 pb-3.5">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 border border-sky-100/60 shrink-0">
                    <Video className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-base font-bold text-slate-900">Online Video Hours</h2>
                    <p className="text-[11px] text-slate-500">Live consultation availability</p>
                  </div>
                </div>

                <div className="mt-3.5 space-y-3.5">
                  {videoSchedules.map((schedule) => {
                    const availability = schedule.availability || [];
                    const daysMap = {};
                    availability.forEach((s) => {
                      daysMap[Number(s.day_of_week)] = s;
                    });

                    const firstSlot = availability[0];
                    const allSameHours =
                      availability.length === 7 &&
                      availability.every(
                        (s) =>
                          s.start_time === firstSlot?.start_time &&
                          s.end_time === firstSlot?.end_time
                      );

                    const todayIndex = new Date().getDay();
                    const todaySlot = daysMap[todayIndex];

                    const now = new Date();
                    const currentMinutes = now.getHours() * 60 + now.getMinutes();
                    let isLiveNow = false;
                    if (todaySlot) {
                      const [sh = 0, sm = 0] = (todaySlot.start_time || "").split(":").map(Number);
                      const [eh = 0, em = 0] = (todaySlot.end_time || "").split(":").map(Number);
                      const startMin = sh * 60 + sm;
                      const endMin = eh * 60 + em;
                      isLiveNow = currentMinutes >= startMin && currentMinutes <= endMin;
                    }

                    const hasVideoRates =
                      formatMoney(schedule.day_rate) || formatMoney(schedule.night_rate);

                    return (
                      <div
                        key={schedule.doctor_id}
                        className="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 transition-all"
                      >
                        {/* Doctor Name & Online Status */}
                        <div className="flex items-start justify-between gap-2">
                          <div>
                            <p className="font-bold text-slate-950 text-sm">
                              {formatDoctorName(schedule.doctor_name)}
                            </p>
                            <p className="text-[11px] text-blue-700 font-medium">
                              Tele-Consultation Specialist
                            </p>
                          </div>

                          <span
                            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ${
                              isLiveNow
                                ? "bg-emerald-100 text-emerald-800"
                                : "bg-blue-100 text-blue-800"
                            }`}
                          >
                            <span
                              className={`h-1.5 w-1.5 rounded-full ${
                                isLiveNow ? "bg-emerald-500 animate-pulse" : "bg-blue-500"
                              }`}
                            />
                            {isLiveNow ? "Online Now" : "Available Today"}
                          </span>
                        </div>

                        {/* Rates display */}
                        {hasVideoRates ? (
                          <div className="mt-2.5 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                            {formatMoney(schedule.day_rate) ? (
                              <span className="inline-flex items-center gap-1 rounded-lg bg-white border border-slate-200 px-2 py-0.5 text-slate-800 shadow-2xs">
                                ☀️ Day: <span className="font-bold text-blue-700">{formatMoney(schedule.day_rate)}</span>
                              </span>
                            ) : null}
                            {formatMoney(schedule.night_rate) ? (
                              <span className="inline-flex items-center gap-1 rounded-lg bg-white border border-slate-200 px-2 py-0.5 text-slate-800 shadow-2xs">
                                🌙 Night: <span className="font-bold text-indigo-700">{formatMoney(schedule.night_rate)}</span>
                              </span>
                            ) : null}
                          </div>
                        ) : null}

                        {/* Operating Hours Box */}
                        {allSameHours ? (
                          <div className="mt-3 space-y-2">
                            <div className="rounded-xl border border-blue-200/70 bg-gradient-to-r from-blue-50/50 to-indigo-50/30 p-3 shadow-2xs">
                              <div className="flex items-center justify-between gap-2 flex-wrap">
                                <span className="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                  <Clock className="h-3.5 w-3.5 text-blue-600 shrink-0" />
                                  Daily Video Hours
                                </span>
                                <span className="font-bold text-blue-700 bg-white border border-blue-200/80 px-2.5 py-0.5 rounded-lg text-xs shadow-2xs">
                                  {formatTime12h(firstSlot.start_time)} – {formatTime12h(firstSlot.end_time)}
                                </span>
                              </div>
                              <p className="mt-1 text-[11px] text-slate-500 leading-normal font-medium">
                                Available for video calls Monday to Sunday (7 days a week)
                              </p>
                            </div>

                            {/* Weekly Day Pill Bar */}
                            <div className="grid grid-cols-7 gap-1 text-center">
                              {DAYS.map((dayName, idx) => {
                                const isToday = idx === todayIndex;
                                return (
                                  <div
                                    key={dayName}
                                    className={`rounded-lg py-1 text-[10px] font-bold transition-all ${
                                      isToday
                                        ? "bg-blue-600 text-white shadow-xs ring-1 ring-blue-600/30"
                                        : "bg-white text-slate-700 border border-slate-200/80"
                                    }`}
                                  >
                                    <div>{dayName}</div>
                                    <div
                                      className={`text-[8px] font-normal ${
                                        isToday ? "text-blue-100" : "text-emerald-600"
                                      }`}
                                    >
                                      {isToday ? "Today" : "Open"}
                                    </div>
                                  </div>
                                );
                              })}
                            </div>
                          </div>
                        ) : availability.length ? (
                          <div className="mt-3 space-y-1">
                            {availability.map((slot) => {
                              const isToday = Number(slot.day_of_week) === todayIndex;
                              return (
                                <div
                                  key={`${schedule.doctor_id}-${slot.day_of_week}-${slot.start_time}-${slot.end_time}`}
                                  className={`flex items-center justify-between rounded-lg px-2 py-1 text-[11px] transition-all ${
                                    isToday
                                      ? "bg-blue-50 text-blue-900 font-bold border border-blue-200"
                                      : "bg-white text-slate-700 border border-slate-100"
                                  }`}
                                >
                                  <span className="flex items-center gap-1">
                                    <Clock className="h-3 w-3 text-slate-400" />
                                    <span>{DAYS[Number(slot.day_of_week)] || "Day"}</span>
                                    {isToday && (
                                      <span className="rounded bg-blue-600 px-1 py-0.2 text-[8px] text-white">
                                        Today
                                      </span>
                                    )}
                                  </span>
                                  <span className="font-semibold">
                                    {formatTime12h(slot.start_time)} – {formatTime12h(slot.end_time)}
                                  </span>
                                </div>
                              );
                            })}
                          </div>
                        ) : null}

                        {/* CTA Button */}
                        <button
                          type="button"
                          onClick={() => {
                            const docMatch = doctors.find(
                              (d) => String(d.id) === String(schedule.doctor_id)
                            ) || {
                              id: schedule.doctor_id,
                              doctor_name: schedule.doctor_name,
                              video_day_rate: schedule.day_rate,
                              video_night_rate: schedule.night_rate,
                            };
                            openBookingModal({ orderType: "video_consult", doctor: docMatch });
                          }}
                          className="mt-3.5 w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition-all active:scale-95 cursor-pointer"
                        >
                          <Video className="h-3.5 w-3.5" />
                          <span>Book Video Calling</span>
                        </button>
                        <p className="mt-1.5 text-center text-[10px] text-slate-400">
                          Instant connect in 15 mins · Digital prescription on WhatsApp
                        </p>
                      </div>
                    );
                  })}
                </div>
              </section>
            ) : null}

            {/* Vet at Home */}
            {vetAtHomeServices.length ? (
              <section className="rounded-xl sm:rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
                <div className="flex items-center gap-2.5 border-b border-slate-100 pb-3.5">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 border border-violet-100/60 shrink-0">
                    <Building2 className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-base font-bold text-slate-900">Vet At Home</h2>
                    <p className="text-[11px] text-slate-500">Doorstep veterinary visit</p>
                  </div>
                </div>
                <div className="mt-3.5 space-y-2.5">
                  {vetAtHomeServices.map((service) => (
                    <div
                      key={service.id}
                      className="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5"
                    >
                      <p className="font-bold text-slate-950 text-xs sm:text-sm">
                        {service.doctor_name ? formatDoctorName(service.doctor_name) : "Home Visit Consultation"}
                      </p>
                      <div className="mt-1.5 space-y-1 text-[11px] text-slate-600">
                        {service.is_enabled !== null ? (
                          <p>
                            <span className="text-slate-400">Status: </span>
                            <span className="font-semibold text-emerald-700">
                              {service.is_enabled ? "Available" : "Temporarily Unavailable"}
                            </span>
                          </p>
                        ) : null}
                        {service.service_hours ? <p>Hours: {service.service_hours}</p> : null}
                        {service.response_time ? <p>Response: {service.response_time}</p> : null}
                        {formatMoney(service.base_payout) ? (
                          <p className="font-bold text-slate-900">
                            Base: {formatMoney(service.base_payout)}
                          </p>
                        ) : null}
                      </div>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}

            {/* SnoutIQ Trust & Healthcare Guarantee Card */}
            <section className="rounded-xl sm:rounded-2xl border border-blue-200/80 bg-gradient-to-b from-blue-50/60 to-white p-5 sm:p-6 shadow-xs">
              <div className="flex items-center gap-2.5">
                <ShieldCheck className="h-5 w-5 text-blue-600 shrink-0" />
                <h3 className="font-bold text-slate-900 text-xs sm:text-sm">SnoutIQ Healthcare Guarantee</h3>
              </div>
              <ul className="mt-3 space-y-2 text-[11px] text-slate-600">
                <li className="flex items-start gap-1.5">
                  <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                  <span>100% Certified and registered veterinary doctors</span>
                </li>
                <li className="flex items-start gap-1.5">
                  <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                  <span>Instant slot confirmation with zero booking fee</span>
                </li>
                <li className="flex items-start gap-1.5">
                  <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                  <span>Digital prescription delivery & post-care support</span>
                </li>
              </ul>
            </section>
          </aside>
        </div>

        {/* Specialized Health Packages - Full Horizontal Width Showcase */}
        {packageItems.length ? (
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-8 sm:mt-10">
            <DetailSection
              title="Specialized Health Packages"
              subtitle="Complete preventive health, immunization & surgical care plans"
              icon={Sparkles}
              badge={`${packageItems.length} Packages Available`}
            >
              {/* Category & Pet Filter Tabs */}
              <div className="flex flex-wrap items-center justify-between gap-2.5 mb-4 border-b border-slate-100 pb-3.5">
                <div className="flex flex-wrap gap-1">
                  <button
                    type="button"
                    onClick={() => setPackageCategory("all")}
                    className={`rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-all ${
                      packageCategory === "all"
                        ? "bg-blue-600 text-white shadow-xs"
                        : "bg-slate-100 text-slate-600 hover:bg-slate-200"
                    }`}
                  >
                    All Packages ({packageItems.length})
                  </button>
                  {packageCategories.map((cat) => {
                    const count = packageItems.filter((p) => p.category === cat).length;
                    return (
                      <button
                        key={cat}
                        type="button"
                        onClick={() => setPackageCategory(cat)}
                        className={`rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-all ${
                          packageCategory === cat
                            ? "bg-blue-600 text-white shadow-xs"
                            : "bg-slate-100 text-slate-600 hover:bg-slate-200"
                        }`}
                      >
                        {cat === "Vaccination" ? "💉 " : "⚕️ "}
                        {cat} ({count})
                      </button>
                    );
                  })}
                </div>

                <div className="flex items-center gap-1">
                  {["all", "Dog", "Cat"].map((pet) => (
                    <button
                      key={pet}
                      type="button"
                      onClick={() => setPackagePet(pet)}
                      className={`rounded-lg px-2 py-0.5 text-[11px] font-medium transition-all ${
                        packagePet === pet
                          ? "bg-slate-900 text-white shadow-xs"
                          : "bg-slate-100 text-slate-500 hover:bg-slate-200"
                      }`}
                    >
                      {pet === "all" ? "All Pets" : pet === "Dog" ? "🐶 Dogs" : "🐱 Cats"}
                    </button>
                  ))}
                </div>
              </div>

              {/* Continuous Auto-Scrolling Sideways Cards Track */}
              <div className="w-full min-w-0 overflow-hidden">
                <div
                  ref={packageScrollRef}
                  className="flex gap-3.5 overflow-x-auto pb-3 pt-1 focus:outline-none [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden cursor-grab active:cursor-grabbing"
                >
                  {displayPackages.map((pkg, idx) => (
                    <div
                      key={`${pkg.id}-${idx}`}
                      className="group relative flex w-[270px] sm:w-[300px] shrink-0 flex-col justify-between rounded-xl sm:rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs transition-all hover:border-blue-300 hover:shadow-md"
                    >
                      <div>
                        {/* Tags Header */}
                        <div className="flex items-center justify-between gap-2 mb-2.5">
                          <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                            {pkg.petType === "Dog" ? "🐶 Dog Care" : pkg.petType === "Cat" ? "🐱 Cat Care" : "🐾 Pet Care"}
                          </span>
                          <span
                            className={`rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider ${
                              pkg.category === "Vaccination"
                                ? "bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                                : "bg-purple-50 text-purple-700 border border-purple-200/60"
                            }`}
                          >
                            {pkg.badge || pkg.category}
                          </span>
                        </div>

                        {/* Title & Description */}
                        <h3 className="text-sm font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                          {pkg.title}
                        </h3>
                        <p className="mt-1 text-[11px] text-slate-500 leading-relaxed font-normal line-clamp-2">
                          {pkg.description}
                        </p>

                        {/* Inclusions list */}
                        {pkg.inclusions && pkg.inclusions.length > 0 && (
                          <div className="mt-3 space-y-1 rounded-xl bg-slate-50/80 p-2.5 text-[11px] text-slate-600">
                            <p className="text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                              Package Inclusions
                            </p>
                            {pkg.inclusions.map((item, i) => (
                              <div key={i} className="flex items-start gap-1.5">
                                <CheckCircle2 className="h-3 w-3 text-emerald-600 shrink-0 mt-0.5" />
                                <span className="text-[10px] sm:text-[11px] leading-tight text-slate-600">{item}</span>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>

                      {/* Pricing & CTA */}
                      <div className="mt-4 border-t border-slate-100 pt-3 flex items-center justify-between gap-2.5">
                        <div>
                          <p className="text-lg font-bold text-slate-900">{pkg.formattedPrice}</p>
                          <p className="text-[9px] font-medium text-slate-400">All-Inclusive Fee</p>
                        </div>
                        <button
                          type="button"
                          onClick={() => {
                            const matchedDoctor = doctors.find(
                              (d) => d.name === pkg.doctorName || d.doctor_name === pkg.doctorName
                            ) || (doctors.length > 0 ? doctors[0] : null);
                            openBookingModal({
                              orderType: "appointment",
                              doctor: matchedDoctor,
                              package: pkg,
                            });
                          }}
                          className="inline-flex items-center gap-1 rounded-xl bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 active:scale-95 transition-all shrink-0 cursor-pointer"
                        >
                          <CalendarDays className="h-3 w-3" />
                          <span>Book Package</span>
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Horizontal Scroll Navigation Hint */}
              <div className="mt-2 flex items-center justify-between text-[10px] sm:text-[11px] text-slate-400 font-medium px-1">
                <span className="inline-flex items-center gap-1.5">
                  <span className="inline-block h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse" />
                  Auto-scrolling sideways · Hover or tap to pause
                </span>
                <span className="hidden sm:inline">Swipe or scroll anytime</span>
              </div>

              {/* Sterile Clinic Guarantee */}
              <div className="mt-3.5 flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-[11px] text-slate-600">
                <ShieldCheck className="h-4.5 w-4.5 text-blue-600 shrink-0" />
                <p>
                  <span className="font-semibold text-slate-800">SnoutIQ Quality Assurance: </span>
                  All packages and procedures are administered by verified veterinary doctors using medical-grade sterilization and cold-chain vaccines.
                </p>
              </div>
            </DetailSection>
          </div>
        ) : null}
      </section>

      {/* Sticky Mobile Bottom Booking Bar */}
      <div className="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-2.5 shadow-2xl backdrop-blur-md sm:hidden">
        <div className="flex items-center justify-between gap-3">
          <div className="min-w-0">
            <p className="font-bold text-slate-900 text-xs sm:text-sm truncate">{clinic.name || "Clinic"}</p>
            <p className="text-[11px] font-semibold text-emerald-700">
              {clinicFee ? `Visit from ${clinicFee}` : "Book appointment online"}
            </p>
          </div>
          <button
            type="button"
            onClick={() => openBookingModal({ orderType: "appointment" })}
            className="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-blue-700 active:scale-95 transition-all shrink-0"
          >
            Book Visit
          </button>
        </div>
      </div>

      {bookingModal.isOpen && (
        <ModernDoctorBooking
          onClose={closeBookingModal}
          orderType={bookingModal.orderType}
          initialClinic={bookingModal.clinic}
          initialDoctor={bookingModal.doctor}
          initialPackage={bookingModal.package}
        />
      )}
    </>
  );
}

export default function NewClinics() {
  const { clinicSlug } = useParams();

  // Ensure scroll is fully restored and page is scrolled to top on mount/route change
  useEffect(() => {
    document.body.style.overflow = "";
    window.scrollTo(0, 0);
  }, [clinicSlug]);

  return (
    <div className="flex min-h-screen flex-col bg-white">
      <Navbar consultPath={DIRECT_CONSULT_PATH} />
      <main className="flex-1">
        {clinicSlug ? <ClinicDetail /> : <ClinicDirectory />}
      </main>
      <Footer />
    </div>
  );
}
