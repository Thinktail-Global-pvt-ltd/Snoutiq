import React, { useEffect, useMemo, useRef, useState } from "react";
import { Link, useParams } from "react-router-dom";
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
  Phone,
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
} from "lucide-react";

import axiosClient from "../axios";
import clinicFallbackImage from "../assets/images/clinic.png";
import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter";
import { Button } from "../newflow/NewButton";
import ModernDoctorBooking from "./ModernDoctorBooking";

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

const extractClinics = (payload) => {
  const page = payload?.data?.data || payload?.data || payload;
  if (Array.isArray(page)) return page;
  if (Array.isArray(page?.data)) return page.data;
  return [];
};

const getClinicImage = (clinic) =>
  clinic?.clinic_image_url || clinic?.image || clinicFallbackImage;

const getClinicSummary = (clinic) =>
  clinic?.website_subtitle ||
  clinic?.clinic_profile ||
  clinic?.hospital_profile ||
  clinic?.bio ||
  clinic?.address ||
  "";

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

const PACKAGE_DETAILS = {
  puppy_vaccination_package: {
    title: "Puppy Complete Vaccination Pack",
    category: "Vaccination",
    petType: "Dog",
    badge: "Most Popular",
    description: "Essential primary immunization shots against canine distemper, parvovirus & rabies with health card.",
    inclusions: [
      "DHPPiL Polyvalent Core Vaccine",
      "Anti-Rabies Primary Immunization",
      "Complete Physical & Vitals Check",
      "Deworming Protocol & Pet Record Card",
    ],
  },
  adult_dog_vaccination_package: {
    title: "Adult Dog Annual Health & Booster",
    category: "Vaccination",
    petType: "Dog",
    badge: "Annual Protection",
    description: "Yearly polyvalent booster and anti-rabies defense to ensure uninterrupted canine immunity.",
    inclusions: [
      "Annual DHPPiL 9-in-1 Booster",
      "Anti-Rabies Annual Shot",
      "Full Clinical Body & Dental Exam",
      "Preventive Health & Diet Consultation",
    ],
  },
  kitten_vaccination_package: {
    title: "Kitten Starter Vaccination Pack",
    category: "Vaccination",
    petType: "Cat",
    badge: "Kitten Starter",
    description: "Core Tricat (FVRCP) protection against feline panleukopenia, herpes & calici viruses plus rabies.",
    inclusions: [
      "FVRCP Tricat Core Vaccine",
      "Feline Anti-Rabies Shot",
      "Pediatric Vitals & Growth Check",
      "Deworming Dose & Kitten Booklet",
    ],
  },
  adult_cat_vaccination_package: {
    title: "Adult Cat Annual Booster Pack",
    category: "Vaccination",
    petType: "Cat",
    badge: "Annual Protection",
    description: "Yearly feline booster maintaining immunity against common infectious respiratory and viral pathogens.",
    inclusions: [
      "Annual FVRCP Booster Shot",
      "Anti-Rabies Booster",
      "Coat, Weight & Dental Checkup",
      "Nutrition & Wellness Guidance",
    ],
  },
  dog_neutering_female: {
    title: "Female Dog Spaying (Sterilization)",
    category: "Surgery & Neutering",
    petType: "Dog",
    badge: "Safe Surgery",
    description: "Advanced surgical ovariohysterectomy preventing heat cycles, pyometra (uterine infection) & mammary tumors.",
    inclusions: [
      "Pre-Surgical Clinical Evaluation",
      "Safe Anesthesia & Vitals Monitoring",
      "Sterile Surgical Procedure by Senior Vet",
      "Post-Op Pain Relief & Recovery Dressing",
    ],
  },
  dog_neutering_male: {
    title: "Male Dog Castration / Neutering",
    category: "Surgery & Neutering",
    petType: "Dog",
    badge: "Sterilization",
    description: "Safe surgical castration reducing testicular cancer risks, territorial marking & roaming tendencies.",
    inclusions: [
      "Pre-Operative Vitals Screening",
      "Surgical Castration by Experienced Surgeon",
      "Post-Operative Antibiotics & Pain Control",
      "Wound Care & Suture Removal Guidance",
    ],
  },
  cat_neutering_female: {
    title: "Female Cat Spaying (Sterilization)",
    category: "Surgery & Neutering",
    petType: "Cat",
    badge: "Safe Surgery",
    description: "Minimally invasive spaying procedure eliminating loud heat calling, uterine infections & pregnancy.",
    inclusions: [
      "Pre-Op Clinical Health Check",
      "Safe Feline Anesthesia Protocol",
      "Precision Sterile Spaying Surgery",
      "Post-Op Antibiotics & Recovery Kit",
    ],
  },
  cat_neutering_male: {
    title: "Male Cat Castration / Neutering",
    category: "Surgery & Neutering",
    petType: "Cat",
    badge: "Sterilization",
    description: "Gentle sterilization for tomcats that stops pungent urine spraying, fighting, and wanderlust.",
    inclusions: [
      "Pre-Procedure Health Screening",
      "Quick & Gentle Castration Procedure",
      "Pain Relief & Recovery Injections",
      "Post-Surgical Care Instructions",
    ],
  },
};

const extractPackageItems = (packages = []) => {
  const items = [];

  packages.forEach((pack, packIndex) => {
    Object.entries(pack).forEach(([key, value]) => {
      if (!key.endsWith("_price")) return;
      const formatted = formatMoney(value);
      if (!formatted) return;

      const baseKey = key.replace("_price", "");
      const meta = PACKAGE_DETAILS[baseKey] || {
        title: formatLabel(baseKey),
        category: baseKey.includes("vaccination")
          ? "Vaccination"
          : baseKey.includes("neutering") || baseKey.includes("surgery")
          ? "Surgery & Neutering"
          : "Health Care",
        petType: baseKey.includes("dog") || baseKey.includes("puppy")
          ? "Dog"
          : baseKey.includes("cat") || baseKey.includes("kitten")
          ? "Cat"
          : "Pet",
        badge: "Specialized Plan",
        description: "Comprehensive veterinary care package designed for optimum pet wellness.",
        inclusions: [
          "Complete Physical Examination",
          "Dedicated Doctor Consultation",
          "Health Records Update",
        ],
      };

      items.push({
        id: `${pack.id || packIndex}-${key}`,
        key: baseKey,
        rawPrice: value,
        formattedPrice: formatted,
        doctorName: pack.doctor_name,
        ...meta,
      });
    });
  });

  return items;
};

const formatLabel = (value) =>
  String(value || "")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

const parseList = (value) => {
  if (Array.isArray(value)) return value.filter(Boolean);
  if (!value) return [];

  try {
    const parsed = JSON.parse(value);
    if (Array.isArray(parsed)) return parsed.filter(Boolean);
  } catch {
    return String(value)
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean);
  }

  return [];
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
    <section className="min-w-0 max-w-full rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs sm:p-6 transition-all hover:border-slate-300">
      <div className="flex items-center justify-between border-b border-slate-100 pb-4 mb-5 gap-3">
        <div className="flex items-center gap-3 min-w-0">
          {Icon && (
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100/60 shadow-xs shrink-0">
              <Icon className="h-5 w-5" />
            </div>
          )}
          <div className="min-w-0">
            <h2 className="text-xl font-bold tracking-tight text-slate-900 truncate">{title}</h2>
            {subtitle && <p className="text-xs text-slate-500 mt-0.5">{subtitle}</p>}
          </div>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          {action}
          {badge && (
            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 shrink-0">
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
            className={`relative rounded-xl border p-4 transition-all ${
              isToday
                ? "border-blue-300 bg-blue-50/50 ring-1 ring-blue-400/30 shadow-xs"
                : "border-slate-200/90 bg-slate-50/60 hover:bg-slate-50 hover:border-slate-300"
            }`}
          >
            <div className="flex items-center justify-between">
              <p className="font-bold text-slate-900 text-sm flex items-center gap-2">
                <Clock className={`h-4 w-4 ${isToday ? "text-blue-600" : "text-slate-400"}`} />
                {formatSlot(slot)}
              </p>
              {isToday && (
                <span className="rounded-full bg-blue-600 px-2.5 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider">
                  Today
                </span>
              )}
            </div>
            <p className="mt-1.5 text-xs font-medium text-slate-600 flex items-center gap-1.5">
              <span className="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              {formatLabel(slot.service_type || "in_clinic")}
              {slot.doctor_name ? ` · ${formatDoctorName(slot.doctor_name)}` : ""}
            </p>
            {slot.break_start && slot.break_end ? (
              <p className="mt-1 text-[11px] text-slate-400">
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
    <section id="clinic-onboarding-form" className="bg-slate-50 py-14 sm:py-16">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
        <div>
          <p className="text-sm font-semibold uppercase tracking-wide text-blue-700">
            For clinics
          </p>
          <h2 className="mt-3 text-3xl font-bold text-slate-950 sm:text-4xl">
            Bring your clinic into SnoutIQ.
          </h2>
          <p className="mt-4 text-lg text-slate-600">
            Add verified doctors, services, appointment hours, and video consult
            availability from the onboarding panel.
          </p>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          {isSubmitted ? (
            <div className="py-8 text-center">
              <div className="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <Check className="h-7 w-7" />
              </div>
              <h3 className="text-xl font-bold text-slate-950">
                Thank you. We will reach out shortly.
              </h3>
              <p className="mt-2 text-slate-600">
                Our team will contact you to discuss onboarding for your clinic.
              </p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-5">
              {[
                ["clinicName", "Clinic Name", "e.g. City Pet Care Clinic"],
                ["contactName", "Contact Person", "e.g. Dr. Aditi Sharma"],
                ["mobile", "Mobile Number", "+91 98765 43210"],
                ["city", "City", "e.g. Bengaluru"],
              ].map(([id, label, placeholder]) => (
                <div key={id}>
                  <label
                    htmlFor={id}
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    {label}
                  </label>
                  <input
                    id={id}
                    required
                    type={id === "mobile" ? "tel" : "text"}
                    value={formData[id]}
                    onChange={handleChange}
                    className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder={placeholder}
                  />
                </div>
              ))}

              {submitError ? (
                <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {submitError}
                </p>
              ) : null}

              <Button
                type="submit"
                size="lg"
                disabled={isSubmitting}
                className="w-full bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700"
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
  const [clinics, setClinics] = useState([]);
  const [query, setQuery] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [bookingModal, setBookingModal] = useState({
    isOpen: false,
    orderType: "appointment",
    clinic: null,
    doctor: null,
  });

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

  return (
    <>
      <section className="border-b border-slate-200 bg-white py-8 sm:py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid gap-6 lg:grid-cols-[0.95fr_0.5fr] lg:items-end">
            <div>
              <p className="inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                {plural(clinics.length, "clinic")} listed
              </p>
              <h1 className="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                Find a SnoutIQ clinic near you
              </h1>
              <p className="mt-3 max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">
                Browse clinics with doctors, consultation fees, services,
                machinery, location, and appointment hours from verified
                onboarding data.
              </p>
            </div>

            <label className="relative block">
              <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
              <input
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                className="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                placeholder="Search clinic, city, or slug"
              />
            </label>
          </div>
        </div>
      </section>

      <section className="bg-slate-50 py-8 sm:py-10">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          {isLoading ? (
            <div className="flex min-h-72 items-center justify-center rounded-2xl border border-slate-200 bg-white">
              <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
            </div>
          ) : error ? (
            <div className="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700">
              <AlertCircle className="h-5 w-5" />
              <p>{error}</p>
            </div>
          ) : filteredClinics.length === 0 ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center">
              <h2 className="text-xl font-semibold text-slate-950">
                No clinics found
              </h2>
              <p className="mt-2 text-slate-600">
                Add a clinic name in full onboarding and the API will generate
                its slug.
              </p>
            </div>
          ) : (
            <div className="grid gap-5 lg:grid-cols-2">
              {filteredClinics.map((entry) => {
                const clinic = entry?.clinic || {};
                const { serviceRows, machineRows } = splitByMainService(
                  entry?.services || [],
                  entry?.machinery || []
                );
                const doctors = entry?.doctors?.length || 0;
                const packages = entry?.specialized_packages?.length || 0;
                const videoSchedules = entry?.video_schedules?.length || 0;
                const dayFee = formatMoney(clinic.clinic_day_fee);
                const nightFee = formatMoney(clinic.clinic_night_fee);
                const summary = getClinicSummary(clinic);
                const quickStats = [
                  [doctors, plural(doctors, "doctor"), Stethoscope],
                  [serviceRows.length, plural(serviceRows.length, "service"), Check],
                  [machineRows.length, plural(machineRows.length, "machine"), Wrench],
                  [packages, plural(packages, "package"), IndianRupee],
                  [videoSchedules, plural(videoSchedules, "video slot"), Video],
                ].filter(([count]) => count > 0);
                const hasFees = dayFee || nightFee;

                return (
                  <Link
                    key={clinic.id || clinic.slug}
                    to={`/clinics/${clinic.slug || clinic.id}`}
                    className="group grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md sm:grid-cols-[240px_1fr]"
                  >
                    <div className="relative min-h-[220px] overflow-hidden bg-slate-100 sm:min-h-full">
                      <img
                        src={getClinicImage(clinic)}
                        alt={clinic.name || "Veterinary clinic"}
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        loading="lazy"
                        onError={(event) => {
                          event.currentTarget.onerror = null;
                          event.currentTarget.src = clinicFallbackImage;
                        }}
                      />
                      {clinic.city ? (
                        <span className="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1 text-sm font-bold text-blue-700 shadow-sm">
                          {clinic.city}
                        </span>
                      ) : null}
                    </div>

                    <div className="flex min-h-[300px] flex-col p-5 sm:p-6">
                      <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                          <h2 className="text-2xl font-bold leading-tight text-slate-950">
                            {valueOrDash(clinic.name)}
                          </h2>
                          <p className="mt-2 flex items-center gap-1.5 text-sm font-medium text-slate-500">
                            <MapPin className="h-4 w-4" />
                            {valueOrDash([clinic.city, clinic.pincode].filter(Boolean).join(" - "))}
                          </p>
                        </div>
                        <span className="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                          Verified
                        </span>
                      </div>

                      {summary ? (
                        <p className="mt-4 line-clamp-2 text-sm leading-6 text-slate-600">
                          {summary}
                        </p>
                      ) : null}

                      {clinic.mobile || hasFees ? (
                        <div className="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                          {clinic.mobile ? (
                            <span className="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
                              <Phone className="h-4 w-4 text-blue-600" />
                              {clinic.mobile}
                            </span>
                          ) : null}
                          {hasFees ? (
                            <span className="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
                              <CalendarDays className="h-4 w-4 text-blue-600" />
                              {dayFee ? `Day ${dayFee}` : ""}
                              {dayFee && nightFee ? " · " : ""}
                              {nightFee ? `Night ${nightFee}` : ""}
                            </span>
                          ) : null}
                        </div>
                      ) : null}

                      {quickStats.length ? (
                        <div className="mt-4 flex flex-wrap gap-2">
                          {quickStats.map(([, item, Icon]) => (
                          <span
                            key={item}
                            className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600"
                          >
                            <Icon className="h-3.5 w-3.5 text-blue-600" />
                            {item}
                          </span>
                          ))}
                        </div>
                      ) : null}

                      <div className="mt-auto flex items-center justify-between gap-3 pt-5">
                        <span className="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 group-hover:text-blue-800 transition-colors">
                          View clinic
                          <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                        </span>
                        <button
                          type="button"
                          onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            setBookingModal({
                              isOpen: true,
                              orderType: "appointment",
                              clinic: { ...clinic, doctors: entry.doctors || [] },
                              doctor: Array.isArray(entry.doctors) && entry.doctors[0] ? entry.doctors[0] : null,
                            });
                          }}
                          className="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 active:scale-95 transition-all"
                        >
                          <CalendarDays className="h-3.5 w-3.5" />
                          Book Visit
                        </button>
                      </div>
                    </div>
                  </Link>
                );
              })}
            </div>
          )}
        </div>
      </section>

      <ClinicLeadForm />

      {bookingModal.isOpen && (
        <ModernDoctorBooking
          onClose={() => setBookingModal({ isOpen: false, orderType: "appointment", clinic: null, doctor: null })}
          orderType={bookingModal.orderType}
          initialClinic={bookingModal.clinic}
          initialDoctor={bookingModal.doctor}
        />
      )}
    </>
  );
}

function ClinicDetail() {
  const { clinicSlug } = useParams();
  const [entry, setEntry] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState("");
  const [bookingModal, setBookingModal] = useState({
    isOpen: false,
    orderType: "appointment",
    clinic: null,
    doctor: null,
  });
  const [copiedLink, setCopiedLink] = useState(false);
  const [packageCategory, setPackageCategory] = useState("all");
  const [packagePet, setPackagePet] = useState("all");

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

  const openBookingModal = ({ orderType, doctor = null, clinic: targetClinic = null }) => {
    const currentClinic = entry?.clinic || {};
    const currentDoctors = entry?.doctors || [];
    const fullClinic = targetClinic || { ...currentClinic, doctors: currentDoctors };
    setBookingModal({
      isOpen: true,
      orderType: orderType || "appointment",
      clinic: fullClinic,
      doctor: doctor || (currentDoctors.length > 0 ? currentDoctors[0] : null),
    });
  };

  useEffect(() => {
    let cancelled = false;

    const loadClinic = async () => {
      setIsLoading(true);
      setError("");

      try {
        const { data } = await axiosClient.get(
          `/clinic-pages/${encodeURIComponent(clinicSlug)}`
        );

        if (!cancelled) setEntry(data?.data || null);
      } catch (err) {
        if (!cancelled) {
          setError(
            err?.response?.data?.message ||
              "Unable to load this clinic right now."
          );
        }
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    };

    loadClinic();
    return () => {
      cancelled = true;
    };
  }, [clinicSlug]);

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
          <Link
            to="/clinics"
            className="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-800"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to clinics
          </Link>
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
  const dayFee = formatMoney(clinic.clinic_day_fee);
  const nightFee = formatMoney(clinic.clinic_night_fee);
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
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
          <nav aria-label="Breadcrumb" className="flex items-center gap-2 text-xs sm:text-sm text-slate-500 overflow-x-auto py-0.5">
            <Link
              to="/clinics"
              className="inline-flex items-center gap-1.5 font-medium text-slate-600 hover:text-blue-600 transition-colors shrink-0"
            >
              <ArrowLeft className="h-3.5 w-3.5" />
              <span>All Clinics</span>
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

          <div className="flex items-center gap-2 sm:gap-3 shrink-0">
            <span className="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200/60">
              <ShieldCheck className="h-3.5 w-3.5 text-emerald-600" />
              Verified Partner
            </span>
            <button
              type="button"
              onClick={handleShare}
              className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-all shadow-2xs"
            >
              <Share2 className="h-3.5 w-3.5" />
              <span>{copiedLink ? "Link Copied!" : "Share"}</span>
            </button>
          </div>
        </div>
      </div>

      {/* Hero Section */}
      <section className="relative border-b border-slate-200/80 bg-gradient-to-b from-blue-50/40 via-white to-slate-50/50 py-8 lg:py-12">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
            {/* Left Column: Clinic Header, Meta & CTAs */}
            <div>
              {/* Trust Badges Pill Strip */}
              <div className="flex flex-wrap items-center gap-2">
                <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-900 border border-amber-200/70 shadow-2xs">
                  <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                  {clinic.rating ? `${clinic.rating} Rating` : "5.0 Rating"}
                  {clinic.user_ratings_total ? ` (${clinic.user_ratings_total}+ reviews)` : " (50+ reviews)"}
                </span>
                <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200/60">
                  <span className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                  Open Today
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 border border-blue-200/60">
                  <Building2 className="h-3.5 w-3.5 text-blue-600" />
                  Pet Healthcare Facility
                </span>
              </div>

              {/* Clinic Name */}
              <h1 className="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                {valueOrDash(clinic.name)}
              </h1>

              {/* Subtitle / Bio */}
              {detailSummary ? (
                <p className="mt-3.5 text-base sm:text-lg text-slate-600 leading-relaxed font-normal max-w-2xl">
                  {detailSummary}
                </p>
              ) : (
                <p className="mt-3.5 text-base sm:text-lg text-slate-600 leading-relaxed font-normal max-w-2xl">
                  Providing professional diagnostic, surgical, and compassionate veterinary healthcare services for pets.
                </p>
              )}

              {/* Quick Info Strip */}
              <div className="mt-5 flex flex-wrap items-center gap-2.5 sm:gap-3 text-xs sm:text-sm text-slate-600">
                {clinic.city || clinic.pincode ? (
                  <div className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100/90 px-3 py-1.5 font-medium text-slate-700">
                    <MapPin className="h-4 w-4 text-blue-600 shrink-0" />
                    <span>{[clinic.city, clinic.pincode].filter(Boolean).join(" - ")}</span>
                  </div>
                ) : null}
                {clinic.mobile ? (
                  <a
                    href={`tel:${clinic.mobile}`}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100/90 px-3 py-1.5 font-medium text-slate-700 hover:text-blue-700 hover:bg-blue-50 transition-colors"
                  >
                    <Phone className="h-4 w-4 text-blue-600 shrink-0" />
                    <span>{clinic.mobile}</span>
                  </a>
                ) : null}
                {dayFee || nightFee ? (
                  <div className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-800 border border-emerald-200/50">
                    <IndianRupee className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                    <span>Visit fee from {dayFee || nightFee}</span>
                  </div>
                ) : null}
              </div>

              {/* High-Conversion Primary CTAs */}
              <div className="mt-7 space-y-3.5">
                <div className="flex flex-wrap items-stretch gap-3">
                  <button
                    type="button"
                    onClick={() => openBookingModal({ orderType: "appointment" })}
                    className="group relative inline-flex items-center justify-center gap-2.5 rounded-xl bg-blue-600 px-6 py-3.5 sm:py-4 text-sm sm:text-base font-bold text-white shadow-lg shadow-blue-600/25 hover:bg-blue-700 active:scale-98 transition-all sm:flex-initial flex-1"
                  >
                    <CalendarDays className="h-5 w-5 transition-transform group-hover:scale-110" />
                    <span>Book In-Clinic Appointment</span>
                  </button>

                  {doctors.length > 0 && (
                    <button
                      type="button"
                      onClick={() => openBookingModal({ orderType: "video_consult", doctor: doctors[0] })}
                      className="group inline-flex items-center justify-center gap-2.5 rounded-xl border-2 border-blue-600/25 bg-white px-5 py-3.5 sm:py-4 text-sm sm:text-base font-bold text-blue-700 shadow-xs hover:bg-blue-50 hover:border-blue-600/40 active:scale-98 transition-all sm:flex-initial flex-1"
                    >
                      <Video className="h-5 w-5 text-blue-600 transition-transform group-hover:scale-110" />
                      <span>Book Video Calling</span>
                    </button>
                  )}
                </div>

                {/* Trust Guarantee Checklist */}
                <div className="flex flex-wrap items-center gap-x-5 gap-y-1.5 pt-1 text-xs font-medium text-slate-500">
                  <span className="flex items-center gap-1.5">
                    <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                    Instant Slot Confirmation
                  </span>
                  <span className="flex items-center gap-1.5">
                    <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                    Zero Convenience Fee
                  </span>
                  <span className="flex items-center gap-1.5">
                    <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                    Verified Licensed Veterinarians
                  </span>
                </div>
              </div>
            </div>

            {/* Right Column: Bento Media Showcase (Both Video & Image) */}
            <div className="relative">
              {hasClinicVideo ? (
                <div className="space-y-3.5">
                  {/* Clinic Facility Video Tour */}
                  <div className="overflow-hidden rounded-3xl border border-slate-200/90 bg-slate-950 shadow-xl transition-all">
                    <div className="relative aspect-[16/10] w-full bg-black">
                      <video
                        src={clinic.clinic_video_url}
                        poster={getClinicImage(clinic)}
                        className="h-full w-full object-cover"
                        autoPlay
                        muted
                        loop
                        playsInline
                        controls
                      />
                      <div className="absolute top-3 left-3 rounded-full bg-black/60 backdrop-blur-md px-3 py-1 text-xs font-semibold text-white border border-white/20 flex items-center gap-1.5 shadow-sm">
                        <Video className="h-3.5 w-3.5 text-blue-400" />
                        <span>Facility Video Tour</span>
                      </div>
                    </div>
                  </div>

                  {/* Clinic Facility Image Card */}
                  <div className="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-slate-950 shadow-lg transition-all hover:shadow-xl">
                    <div className="relative aspect-[16/7] w-full overflow-hidden">
                      <img
                        src={getClinicImage(clinic)}
                        alt={clinic.name || "Veterinary clinic"}
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        onError={(event) => {
                          event.currentTarget.onerror = null;
                          event.currentTarget.src = clinicFallbackImage;
                        }}
                      />
                      <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
                      <div className="absolute bottom-3 left-3.5 right-3.5 flex items-center justify-between text-white">
                        <div className="flex items-center gap-2">
                          <Camera className="h-4 w-4 text-blue-300" />
                          <span className="text-xs sm:text-sm font-bold drop-shadow-sm">
                            Clinic Facility & Campus
                          </span>
                        </div>
                        <span className="rounded-full bg-white/20 backdrop-blur-md border border-white/30 px-2.5 py-0.5 text-[10px] sm:text-xs font-semibold text-white">
                          Verified Photo
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              ) : (
                <div className="overflow-hidden rounded-3xl border border-slate-200/90 bg-slate-950 shadow-xl transition-all">
                  <div className="relative aspect-[16/11] w-full overflow-hidden group">
                    <img
                      src={getClinicImage(clinic)}
                      alt={clinic.name || "Veterinary clinic"}
                      className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                      onError={(event) => {
                        event.currentTarget.onerror = null;
                        event.currentTarget.src = clinicFallbackImage;
                      }}
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-transparent" />
                    <div className="absolute bottom-4 left-4 right-4 flex items-end justify-between text-white">
                      <div>
                        <p className="text-base font-bold drop-shadow-sm">{clinic.name}</p>
                        <p className="text-xs text-white/80 drop-shadow-sm">{clinic.city || "Verified Pet Clinic"}</p>
                      </div>
                      <span className="rounded-full bg-white/20 backdrop-blur-md border border-white/30 px-3 py-1 text-xs font-semibold text-white">
                        Verified Facility
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </section>



      {/* Main Details Section */}
      <section className="bg-slate-50/70 py-10 sm:py-12">
        <div className="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.25fr_0.75fr] lg:px-8">
          {/* Main Left Content */}
          <div className="min-w-0 space-y-8">
            {/* Consultation Modes Comparison (In-Clinic vs Video Consult) */}
            <DetailSection
              title="Consultation Options & Fees"
              subtitle="Choose between in-person clinic visit or instant tele-consultation"
              icon={Building2}
            >
              <div className="grid gap-4 sm:grid-cols-2">
                {/* In-Clinic Card */}
                <div className="relative flex flex-col justify-between rounded-2xl border-2 border-blue-600/20 bg-gradient-to-b from-blue-50/30 to-white p-5 shadow-xs transition-all hover:border-blue-600/40">
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-bold text-blue-800 uppercase tracking-wider">
                        In-Person Visit
                      </span>
                      <Building2 className="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-slate-900">In-Clinic Consultation</h3>
                      <p className="text-xs text-slate-500 mt-1">
                        Detailed physical checkup, emergency care, pathology tests & immediate treatment at clinic.
                      </p>
                    </div>
                    <div className="pt-2">
                      <p className="text-2xl font-black text-slate-900">
                        {dayFee || nightFee || "₹499"}
                      </p>
                      {dayFee && nightFee ? (
                        <p className="text-xs font-medium text-slate-500">
                          Day: {dayFee} · Night / Emergency: {nightFee}
                        </p>
                      ) : null}
                    </div>
                    <ul className="space-y-1.5 pt-2 text-xs text-slate-600">
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Comprehensive physical vitals examination</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>On-site medication, injections & diagnostic tests</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Reserved timeslot with zero clinic waiting</span>
                      </li>
                    </ul>
                  </div>

                  <div className="pt-5">
                    <button
                      type="button"
                      onClick={() => openBookingModal({ orderType: "appointment" })}
                      className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-blue-700 active:scale-98 transition-all"
                    >
                      <CalendarDays className="h-4 w-4" />
                      Book In-Clinic Visit
                    </button>
                  </div>
                </div>

                {/* Online Video Consult Card */}
                <div className="relative flex flex-col justify-between rounded-2xl border-2 border-slate-200 bg-white p-5 shadow-xs transition-all hover:border-slate-300">
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 uppercase tracking-wider">
                        Fastest · 15 Mins
                      </span>
                      <Video className="h-5 w-5 text-emerald-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-slate-900">Live Video Consultation</h3>
                      <p className="text-xs text-slate-500 mt-1">
                        Connect directly with licensed veterinarians via secure video call from home.
                      </p>
                    </div>
                    <div className="pt-2">
                      <p className="text-2xl font-black text-slate-900">
                        {doctors[0]?.video_day_rate ? formatMoney(doctors[0].video_day_rate) : "₹499"}
                      </p>
                      <p className="text-xs font-medium text-slate-500">
                        Instant 15-20 min video session
                      </p>
                    </div>
                    <ul className="space-y-1.5 pt-2 text-xs text-slate-600">
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Instant connect from home in 15 mins</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Official digital prescription on WhatsApp & App</span>
                      </li>
                      <li className="flex items-center gap-2">
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Ideal for diet, skin allergies & second opinions</span>
                      </li>
                    </ul>
                  </div>

                  <div className="pt-5">
                    <button
                      type="button"
                      onClick={() => openBookingModal({ orderType: "video_consult", doctor: doctors[0] || null })}
                      className="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-50 py-3 text-xs sm:text-sm font-bold text-slate-800 hover:bg-slate-100 active:scale-98 transition-all"
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
                <div className="space-y-4">
                  {doctors.map((doctor) => {
                    const specializations = parseList(
                      doctor.specialization_select_all_that_apply
                    );
                    const languages = parseList(doctor.languages_spoken);
                    const doctorFees = [
                      ["Clinic Visit", formatMoney(doctor.doctors_price) || dayFee],
                      ["Video Call", formatMoney(doctor.video_day_rate)],
                      ["Night Video", formatMoney(doctor.video_night_rate)],
                    ].filter(([, value]) => value);

                    const doctorInitials = getDoctorInitials(doctor.doctor_name);

                    return (
                      <div
                        key={doctor.id}
                        className="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs transition-all hover:border-blue-200 hover:shadow-sm"
                      >
                        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                          <div className="flex items-start gap-4">
                            {/* Doctor Avatar */}
                            <div className="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 font-bold text-white shadow-sm text-lg">
                              {doctorInitials}
                              <div className="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white text-white">
                                <Check className="h-3 w-3 stroke-[3]" />
                              </div>
                            </div>

                            {/* Doctor Info */}
                            <div>
                              <div className="flex items-center gap-2">
                                <h3 className="text-lg font-bold text-slate-950">
                                  {formatDoctorName(doctor.doctor_name)}
                                </h3>
                                <BadgeCheck className="h-4 w-4 text-blue-600" />
                              </div>

                              {doctor.degree || doctor.years_of_experience ? (
                                <p className="mt-0.5 text-xs font-semibold text-blue-700">
                                  {doctor.degree || "Veterinary Surgeon"}
                                  {doctor.degree && doctor.years_of_experience ? " · " : ""}
                                  {doctor.years_of_experience
                                    ? `${doctor.years_of_experience} Years Exp`
                                    : ""}
                                </p>
                              ) : null}

                              {/* Specialization Chips */}
                              {specializations.length ? (
                                <div className="mt-2.5 flex flex-wrap gap-1.5">
                                  {specializations.map((item) => (
                                    <span
                                      key={item}
                                      className="rounded-lg bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-700"
                                    >
                                      {item}
                                    </span>
                                  ))}
                                </div>
                              ) : null}

                              {/* Spoken Languages */}
                              {languages.length ? (
                                <p className="mt-2 text-xs text-slate-500">
                                  <span className="font-semibold text-slate-600">Languages:</span>{" "}
                                  {languages.join(", ")}
                                </p>
                              ) : null}
                            </div>
                          </div>

                          {/* Fee Badges */}
                          {doctorFees.length ? (
                            <div className="flex sm:flex-col flex-wrap gap-2 shrink-0 sm:text-right">
                              {doctorFees.map(([label, value]) => (
                                <div key={label} className="rounded-lg bg-slate-50 border border-slate-100 px-2.5 py-1 text-xs">
                                  <span className="text-slate-500">{label}: </span>
                                  <span className="font-bold text-slate-900">{value}</span>
                                </div>
                              ))}
                            </div>
                          ) : null}
                        </div>

                        {/* Direct Dual CTAs for Doctor */}
                        <div className="mt-4 flex flex-wrap items-center gap-2.5 border-t border-slate-100 pt-4">
                          <button
                            type="button"
                            onClick={() => openBookingModal({ orderType: "video_consult", doctor })}
                            className="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 active:scale-95 transition-all"
                          >
                            <Video className="h-3.5 w-3.5" />
                            <span>Book Video Calling</span>
                          </button>
                          <button
                            type="button"
                            onClick={() => openBookingModal({ orderType: "appointment", doctor })}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:border-blue-600 hover:text-blue-600 active:scale-95 transition-all shadow-2xs"
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
                <div className="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-200/80 bg-gradient-to-r from-blue-50/90 to-indigo-50/60 p-4 sm:p-5">
                  <div>
                    <p className="text-sm font-bold text-blue-950">Reserve Your Appointment Today</p>
                    <p className="text-xs text-blue-700 mt-0.5">
                      Select your preferred date & time slot with instant WhatsApp confirmation
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => openBookingModal({ orderType: "appointment" })}
                    className="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700 transition-all active:scale-95"
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
                <div className="grid gap-3.5 sm:grid-cols-2">
                  {serviceRows.map((service) => (
                    <div
                      key={service.id}
                      className="flex flex-col justify-between rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs transition-all hover:border-blue-200 hover:shadow-xs"
                    >
                      <div>
                        <div className="flex items-start justify-between gap-2">
                          <h3 className="font-bold text-slate-900 text-sm">
                            {valueOrDash(service.name)}
                          </h3>
                          {formatMoney(service.price) ? (
                            <span className="rounded-lg bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-200/60 shrink-0">
                              {formatMoney(service.price)}
                            </span>
                          ) : null}
                        </div>
                        {service.description || service.pet_type ? (
                          <p className="mt-1.5 text-xs text-slate-500 leading-relaxed">
                            {service.description || service.pet_type}
                          </p>
                        ) : null}
                      </div>
                      {service.duration ? (
                        <div className="mt-3 flex items-center gap-1 text-[11px] font-medium text-slate-400">
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
                <div className="grid gap-3.5 sm:grid-cols-2">
                  {machineRows.map((machine) => (
                    <div
                      key={machine.id}
                      className="flex flex-col justify-between rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs transition-all hover:border-blue-200 hover:shadow-xs"
                    >
                      <div>
                        <div className="flex items-start justify-between gap-2">
                          <h3 className="font-bold text-slate-900 text-sm">
                            {valueOrDash(machine.name)}
                          </h3>
                          {formatMoney(machine.price) ? (
                            <span className="rounded-lg bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700 border border-blue-200/60 shrink-0">
                              {formatMoney(machine.price)}
                            </span>
                          ) : null}
                        </div>
                        {machine.description || machine.pet_type ? (
                          <p className="mt-1.5 text-xs text-slate-500 leading-relaxed">
                            {machine.description || machine.pet_type}
                          </p>
                        ) : null}
                      </div>
                    </div>
                  ))}
                </div>
              </DetailSection>
            ) : null}

            {/* Specialized Health Packages */}
            {packageItems.length ? (
              <DetailSection
                title="Specialized Health Packages"
                subtitle="Complete preventive health, immunization & surgical care plans"
                icon={Sparkles}
                badge={`${packageItems.length} Packages Available`}
              >
                {/* Category & Pet Filter Tabs */}
                <div className="flex flex-wrap items-center justify-between gap-3 mb-5 border-b border-slate-100 pb-4">
                  <div className="flex flex-wrap gap-1.5">
                    <button
                      type="button"
                      onClick={() => setPackageCategory("all")}
                      className={`rounded-xl px-3 py-1.5 text-xs font-bold transition-all ${
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
                          className={`rounded-xl px-3 py-1.5 text-xs font-bold transition-all ${
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

                  <div className="flex items-center gap-1.5">
                    {["all", "Dog", "Cat"].map((pet) => (
                      <button
                        key={pet}
                        type="button"
                        onClick={() => setPackagePet(pet)}
                        className={`rounded-lg px-2.5 py-1 text-xs font-semibold transition-all ${
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
                    className="flex gap-4 overflow-x-auto pb-4 pt-1 focus:outline-none [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden cursor-grab active:cursor-grabbing"
                  >
                    {displayPackages.map((pkg, idx) => (
                      <div
                        key={`${pkg.id}-${idx}`}
                        className="group relative flex w-[280px] sm:w-[320px] shrink-0 flex-col justify-between rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs transition-all hover:border-blue-300 hover:shadow-md"
                      >
                        <div>
                          {/* Tags Header */}
                          <div className="flex items-center justify-between gap-2 mb-3">
                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">
                              {pkg.petType === "Dog" ? "🐶 Dog Care" : pkg.petType === "Cat" ? "🐱 Cat Care" : "🐾 Pet Care"}
                            </span>
                            <span
                              className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ${
                                pkg.category === "Vaccination"
                                  ? "bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                                  : "bg-purple-50 text-purple-700 border border-purple-200/60"
                              }`}
                            >
                              {pkg.badge || pkg.category}
                            </span>
                          </div>

                          {/* Title & Description */}
                          <h3 className="text-base font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                            {pkg.title}
                          </h3>
                          <p className="mt-1.5 text-xs text-slate-500 leading-relaxed font-normal line-clamp-2">
                            {pkg.description}
                          </p>

                          {/* Inclusions list */}
                          {pkg.inclusions && pkg.inclusions.length > 0 && (
                            <div className="mt-3.5 space-y-1.5 rounded-xl bg-slate-50/80 p-3 text-xs text-slate-600">
                              <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                Package Inclusions
                              </p>
                              {pkg.inclusions.map((item, i) => (
                                <div key={i} className="flex items-start gap-2">
                                  <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                                  <span className="text-[11px] leading-tight text-slate-600">{item}</span>
                                </div>
                              ))}
                            </div>
                          )}
                        </div>

                        {/* Pricing & CTA */}
                        <div className="mt-5 border-t border-slate-100 pt-4 flex items-center justify-between gap-3">
                          <div>
                            <p className="text-xl font-black text-slate-900">{pkg.formattedPrice}</p>
                            <p className="text-[10px] font-medium text-slate-400">All-Inclusive Fee</p>
                          </div>
                          <button
                            type="button"
                            onClick={() =>
                              openBookingModal({
                                orderType: "appointment",
                                doctor: doctors.length > 0 ? doctors[0] : null,
                              })
                            }
                            className="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 active:scale-95 transition-all shrink-0"
                          >
                            <CalendarDays className="h-3.5 w-3.5" />
                            <span>Book Package</span>
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Horizontal Scroll Navigation Hint */}
                <div className="mt-2.5 flex items-center justify-between text-[11px] text-slate-400 font-medium px-1">
                  <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block h-2 w-2 rounded-full bg-blue-500 animate-pulse" />
                    Auto-scrolling sideways · Hover or tap to pause
                  </span>
                  <span className="hidden sm:inline">Swipe or scroll anytime</span>
                </div>

                {/* Sterile Clinic Guarantee */}
                <div className="mt-4 flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 text-xs text-slate-600">
                  <ShieldCheck className="h-5 w-5 text-blue-600 shrink-0" />
                  <p>
                    <span className="font-semibold text-slate-800">SnoutIQ Quality Assurance: </span>
                    All packages and procedures are administered by verified veterinary doctors using medical-grade sterilization and cold-chain vaccines.
                  </p>
                </div>
              </DetailSection>
            ) : null}
          </div>

          {/* Right Sidebar */}
          <aside className="space-y-6">
            {/* Location & Directions Card */}
            {fullAddress || clinic.city || clinic.pincode || clinic.lat || clinic.rating ? (
              <section className="rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
                <div className="flex items-center gap-3 border-b border-slate-100 pb-4">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100/60 shrink-0">
                    <MapPin className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-lg font-bold text-slate-900">Clinic Location</h2>
                    <p className="text-xs text-slate-500">Address & directions</p>
                  </div>
                </div>

                {fullAddress ? (
                  <p className="mt-4 text-sm text-slate-700 leading-relaxed font-medium">
                    {fullAddress}
                  </p>
                ) : null}

                <div className="mt-4 space-y-2 rounded-xl bg-slate-50 p-3.5 text-xs text-slate-600">
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

                <div className="mt-5 space-y-2.5">
                  {locationUrl ? (
                    <a
                      href={locationUrl}
                      target="_blank"
                      rel="noreferrer"
                      className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs sm:text-sm font-bold text-white hover:bg-blue-700 shadow-xs transition-all"
                    >
                      <Navigation className="h-4 w-4" />
                      <span>Get Directions on Google Maps</span>
                      <ExternalLink className="h-3.5 w-3.5 opacity-70" />
                    </a>
                  ) : null}

                  {clinic.mobile ? (
                    <a
                      href={`tel:${clinic.mobile}`}
                      className="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                    >
                      <Phone className="h-4 w-4 text-blue-600" />
                      <span>Call Clinic ({clinic.mobile})</span>
                    </a>
                  ) : null}
                </div>
              </section>
            ) : null}

            {/* Video Hours Card */}
            {videoSchedules.length ? (
              <section className="rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
                <div className="flex items-center gap-3 border-b border-slate-100 pb-4">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 border border-sky-100/60 shrink-0">
                    <Video className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-lg font-bold text-slate-900">Online Video Hours</h2>
                    <p className="text-xs text-slate-500">Live consultation availability</p>
                  </div>
                </div>

                <div className="mt-4 space-y-4">
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
                        className="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 transition-all"
                      >
                        {/* Doctor Name & Online Status */}
                        <div className="flex items-start justify-between gap-2">
                          <div>
                            <p className="font-bold text-slate-950 text-base">
                              {formatDoctorName(schedule.doctor_name)}
                            </p>
                            <p className="text-xs text-blue-700 font-medium">
                              Tele-Consultation Specialist
                            </p>
                          </div>

                          <span
                            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold ${
                              isLiveNow
                                ? "bg-emerald-100 text-emerald-800"
                                : "bg-blue-100 text-blue-800"
                            }`}
                          >
                            <span
                              className={`h-2 w-2 rounded-full ${
                                isLiveNow ? "bg-emerald-500 animate-pulse" : "bg-blue-500"
                              }`}
                            />
                            {isLiveNow ? "Online Now" : "Available Today"}
                          </span>
                        </div>

                        {/* Rates display */}
                        {hasVideoRates ? (
                          <div className="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                            {formatMoney(schedule.day_rate) ? (
                              <span className="inline-flex items-center gap-1 rounded-lg bg-white border border-slate-200 px-2.5 py-1 text-slate-800 shadow-2xs">
                                ☀️ Day: <span className="font-bold text-blue-700">{formatMoney(schedule.day_rate)}</span>
                              </span>
                            ) : null}
                            {formatMoney(schedule.night_rate) ? (
                              <span className="inline-flex items-center gap-1 rounded-lg bg-white border border-slate-200 px-2.5 py-1 text-slate-800 shadow-2xs">
                                🌙 Night: <span className="font-bold text-indigo-700">{formatMoney(schedule.night_rate)}</span>
                              </span>
                            ) : null}
                          </div>
                        ) : null}

                        {/* Operating Hours Box */}
                        {allSameHours ? (
                          <div className="mt-3.5 space-y-2.5">
                            <div className="rounded-xl border border-blue-200/60 bg-white p-3 shadow-2xs">
                              <div className="flex items-center justify-between text-xs">
                                <span className="font-bold text-slate-900 flex items-center gap-1.5">
                                  <Clock className="h-4 w-4 text-blue-600" />
                                  Daily Consultation Hours
                                </span>
                                <span className="font-bold text-blue-700">
                                  {formatTime12h(firstSlot.start_time)} – {formatTime12h(firstSlot.end_time)}
                                </span>
                              </div>
                              <p className="mt-1 text-[11px] text-slate-500">
                                Doctor is available for video call every day (Monday to Sunday)
                              </p>
                            </div>

                            {/* Weekly Day Pill Bar */}
                            <div className="grid grid-cols-7 gap-1 text-center">
                              {DAYS.map((dayName, idx) => {
                                const isToday = idx === todayIndex;
                                return (
                                  <div
                                    key={dayName}
                                    className={`rounded-lg py-1.5 text-[11px] font-bold transition-all ${
                                      isToday
                                        ? "bg-blue-600 text-white shadow-xs ring-2 ring-blue-600/30"
                                        : "bg-white text-slate-700 border border-slate-200/80"
                                    }`}
                                  >
                                    <div>{dayName}</div>
                                    <div
                                      className={`text-[9px] font-normal ${
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
                          <div className="mt-3.5 space-y-1.5">
                            {availability.map((slot) => {
                              const isToday = Number(slot.day_of_week) === todayIndex;
                              return (
                                <div
                                  key={`${schedule.doctor_id}-${slot.day_of_week}-${slot.start_time}-${slot.end_time}`}
                                  className={`flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs transition-all ${
                                    isToday
                                      ? "bg-blue-50 text-blue-900 font-bold border border-blue-200"
                                      : "bg-white text-slate-700 border border-slate-100"
                                  }`}
                                >
                                  <span className="flex items-center gap-1.5">
                                    <Clock className="h-3.5 w-3.5 text-slate-400" />
                                    <span>{DAYS[Number(slot.day_of_week)] || "Day"}</span>
                                    {isToday && (
                                      <span className="rounded bg-blue-600 px-1.5 py-0.2 text-[9px] text-white">
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
                          className="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-blue-700 transition-all active:scale-95"
                        >
                          <Video className="h-4 w-4" />
                          <span>Book Video Calling</span>
                        </button>
                        <p className="mt-2 text-center text-[11px] text-slate-400">
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
              <section className="rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
                <div className="flex items-center gap-3 border-b border-slate-100 pb-4">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 border border-violet-100/60 shrink-0">
                    <Building2 className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-lg font-bold text-slate-900">Vet At Home</h2>
                    <p className="text-xs text-slate-500">Doorstep veterinary visit</p>
                  </div>
                </div>
                <div className="mt-4 space-y-3">
                  {vetAtHomeServices.map((service) => (
                    <div
                      key={service.id}
                      className="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5"
                    >
                      <p className="font-bold text-slate-950 text-sm">
                        {service.doctor_name ? formatDoctorName(service.doctor_name) : "Home Visit Consultation"}
                      </p>
                      <div className="mt-2 space-y-1 text-xs text-slate-600">
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
            <section className="rounded-2xl border border-blue-200/80 bg-gradient-to-b from-blue-50/60 to-white p-5 sm:p-6 shadow-xs">
              <div className="flex items-center gap-2.5">
                <ShieldCheck className="h-6 w-6 text-blue-600 shrink-0" />
                <h3 className="font-bold text-slate-900 text-sm">SnoutIQ Healthcare Guarantee</h3>
              </div>
              <ul className="mt-4 space-y-2.5 text-xs text-slate-600">
                <li className="flex items-start gap-2">
                  <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
                  <span>100% Certified and registered veterinary doctors</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
                  <span>Instant slot confirmation with zero booking fee</span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
                  <span>Digital prescription delivery & post-care support</span>
                </li>
              </ul>
            </section>
          </aside>
        </div>
      </section>

      {/* Sticky Mobile Bottom Booking Bar */}
      <div className="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-2xl backdrop-blur-md sm:hidden">
        <div className="flex items-center justify-between gap-3">
          <div className="min-w-0">
            <p className="font-bold text-slate-900 text-sm truncate">{clinic.name || "Clinic"}</p>
            <p className="text-xs font-semibold text-emerald-700">
              Visit from {dayFee || nightFee || "₹499"}
            </p>
          </div>
          <button
            type="button"
            onClick={() => openBookingModal({ orderType: "appointment" })}
            className="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-blue-700 active:scale-95 transition-all shrink-0"
          >
            Book Visit
          </button>
        </div>
      </div>

      {bookingModal.isOpen && (
        <ModernDoctorBooking
          onClose={() => setBookingModal({ isOpen: false, orderType: "appointment", clinic: null, doctor: null })}
          orderType={bookingModal.orderType}
          initialClinic={bookingModal.clinic}
          initialDoctor={bookingModal.doctor}
        />
      )}
    </>
  );
}

export default function NewClinics() {
  const { clinicSlug } = useParams();

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
