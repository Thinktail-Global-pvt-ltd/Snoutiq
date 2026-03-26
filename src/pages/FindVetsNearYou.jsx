import React, { useDeferredValue, useEffect, useMemo, useState } from "react";
import { Helmet } from "react-helmet-async";
import { useSearchParams } from "react-router-dom";
import {
  Building2,
  CalendarDays,
  ChevronRight,
  Clock3,
  Crosshair,
  IndianRupee,
  MapPin,
  Phone,
  Search,
  ShieldCheck,
  Sparkles,
  Star,
  Stethoscope,
  UserRound,
  X,
} from "lucide-react";
import { apiBaseUrl } from "../lib/api";
import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter";

const PAGE_TITLE = "Find Vets Near You | SnoutIQ";
const PAGE_DESCRIPTION =
  "Browse nearby veterinary doctors and featured clinic profiles from SnoutIQ's nearby-plus-featured API.";
const API_ENDPOINT = "/api/nearby-plus-featured";
const OFFER_CODE = "SNOUT100";
const GENERAL_FILTERS = [
  { key: "all", label: "All" },
  { key: "open_now", label: "Open Now" },
  { key: "top_rated", label: "Top Rated" },
  { key: "dogs_cats", label: "Dogs & Cats" },
  { key: "exotic", label: "Exotic Pets" },
];

const getBackendBase = () => apiBaseUrl().replace(/\/+$/, "");

const normalizeImageUrl = (value) => {
  if (!value) return "";
  const raw = String(value).trim();
  if (!raw) return "";

  const fixed = raw.replace(
    "https://snoutiq.com/https://snoutiq.com/",
    "https://snoutiq.com/"
  );
  const lower = fixed.toLowerCase();

  if (
    lower.startsWith("http://") ||
    lower.startsWith("https://") ||
    lower.startsWith("data:")
  ) {
    return fixed;
  }

  let cleaned = fixed.replace(/^\/+/, "");
  if (cleaned.toLowerCase().startsWith("backend/")) {
    cleaned = cleaned.slice("backend/".length);
  }

  return `${getBackendBase()}/${cleaned}`;
};

const normalizeText = (value) => {
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

const hasDisplayValue = (value) => normalizeText(value) !== "";

const toOptionalNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
};

const parseBoolean = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;

  const normalized = String(value ?? "")
    .trim()
    .toLowerCase();

  if (!normalized) return null;
  if (["1", "true", "yes", "open", "active"].includes(normalized)) return true;
  if (["0", "false", "no", "closed", "inactive"].includes(normalized)) {
    return false;
  }
  return null;
};

const parseListField = (value) => {
  if (!value) return [];
  if (Array.isArray(value)) {
    return value.map((item) => normalizeText(item)).filter(Boolean);
  }

  const text = normalizeText(value);
  if (!text) return [];

  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) {
        return parsed.map((item) => normalizeText(item)).filter(Boolean);
      }
    } catch {
      return text
        .replace(/^\[|\]$/g, "")
        .replace(/["']/g, "")
        .split(",")
        .map((item) => normalizeText(item))
        .filter(Boolean);
    }
  }

  return text
    .split(",")
    .map((item) => normalizeText(item))
    .filter(Boolean);
};

const dedupeList = (items = []) => {
  const seen = new Set();
  return items.filter((item) => {
    const key = String(item || "").toLowerCase();
    if (!key || seen.has(key)) return false;
    seen.add(key);
    return true;
  });
};

const normalizeBreakTimes = (value) =>
  parseListField(value).filter((item) => {
    const key = item.toLowerCase().replace(/[^a-z0-9]/g, "");
    if (!key) return false;
    if (["no", "none", "nil", "na", "n/a", "notavailable"].includes(key)) {
      return false;
    }
    return !key.startsWith("no");
  });

const listToDisplayText = (value) => dedupeList(parseListField(value)).join(", ");

const formatInr = (value) => {
  const parsed = toOptionalNumber(value);
  if (!Number.isFinite(parsed)) return "NA";
  return `₹${parsed.toLocaleString("en-IN", {
    minimumFractionDigits: Number.isInteger(parsed) ? 0 : 2,
    maximumFractionDigits: 2,
  })}`;
};

const formatDistance = (value) => {
  const parsed = toOptionalNumber(value);
  if (!Number.isFinite(parsed)) return "";
  if (parsed < 1) return `${Math.round(parsed * 1000)} m`;
  return `${parsed.toFixed(parsed >= 10 ? 0 : 1)} km`;
};

const getInitials = (value) => {
  const text = normalizeText(value);
  if (!text) return "DR";
  const parts = text.split(/\s+/).filter(Boolean);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return `${parts[0][0] || ""}${parts[1][0] || ""}`.toUpperCase();
};

const getCareFocus = (doctor) => {
  const source = [
    doctor.specializationText,
    ...(doctor.specializationList || []),
  ]
    .join(" ")
    .toLowerCase();

  return {
    dogsCats: /dog|canine|cat|feline/.test(source),
    exotic: /exotic|avian|bird|rabbit|turtle|guinea|hamster/.test(source),
  };
};

const resolveDoctorPayload = (rawDoctor, clinicFields) => {
  if (!rawDoctor) return null;

  const specializationList = dedupeList(
    parseListField(
      rawDoctor?.specialization_select_all_that_apply ||
        rawDoctor?.specialization ||
        rawDoctor?.specializationText
    )
  );

  const doctorRating =
    toOptionalNumber(rawDoctor?.average_review_points) ??
    toOptionalNumber(rawDoctor?.rating);
  const clinicRating = toOptionalNumber(clinicFields?.clinicRating);
  const doctorReviewCount =
    toOptionalNumber(rawDoctor?.reviews_count) ??
    toOptionalNumber(rawDoctor?.reviews);
  const clinicReviewCount = toOptionalNumber(clinicFields?.clinicReviewCount);

  return {
    id:
      clinicFields?.entryId ||
      rawDoctor?.id ||
      `${clinicFields?.source || "doctor"}-${clinicFields?.clinicId || "x"}`,
    source: clinicFields?.source || "nearby",
    clinicId: clinicFields?.clinicId || rawDoctor?.vet_registeration_id || null,
    clinicName: normalizeText(clinicFields?.clinicName),
    clinicCity: normalizeText(clinicFields?.clinicCity),
    clinicAddress: normalizeText(clinicFields?.clinicAddress),
    clinicPhone: normalizeText(clinicFields?.clinicPhone),
    clinicImage: normalizeImageUrl(clinicFields?.clinicImage),
    clinicDistance: toOptionalNumber(clinicFields?.clinicDistance),
    clinicOpenNow: parseBoolean(clinicFields?.clinicOpenNow),
    referralCode: normalizeText(clinicFields?.referralCode),
    doctorName:
      normalizeText(rawDoctor?.doctor_name) ||
      normalizeText(rawDoctor?.name) ||
      "Veterinary Doctor",
    doctorPhone: normalizeText(rawDoctor?.doctor_mobile || rawDoctor?.mobile),
    doctorLicense: normalizeText(rawDoctor?.doctor_license || rawDoctor?.license),
    doctorImage: normalizeImageUrl(
      rawDoctor?.doctor_image_blob_url ||
        rawDoctor?.doctor_image_url ||
        rawDoctor?.doctor_image ||
        rawDoctor?.image
    ),
    degree: listToDisplayText(rawDoctor?.degree),
    experience:
      normalizeText(rawDoctor?.years_of_experience) ||
      normalizeText(rawDoctor?.experience),
    specializationList,
    specializationText:
      specializationList.join(", ") ||
      listToDisplayText(rawDoctor?.specialization_select_all_that_apply),
    languages: dedupeList(parseListField(rawDoctor?.languages_spoken)),
    responseDay: normalizeText(rawDoctor?.response_time_for_online_consults_day),
    responseNight: normalizeText(
      rawDoctor?.response_time_for_online_consults_night
    ),
    freeFollowUp: normalizeText(
      rawDoctor?.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta
    ),
    breakTimes: normalizeBreakTimes(
      rawDoctor?.break_do_not_disturb_time_example_2_4_pm
    ),
    bio: normalizeText(rawDoctor?.bio),
    priceDay:
      toOptionalNumber(rawDoctor?.video_day_rate) ??
      toOptionalNumber(rawDoctor?.doctors_price),
    priceNight: toOptionalNumber(rawDoctor?.video_night_rate),
    rating: doctorRating ?? clinicRating,
    reviewCount: doctorReviewCount ?? clinicReviewCount ?? 0,
  };
};

const normalizeNearbyDoctors = (response) =>
  (response?.nearby?.data || [])
    .map((entry, index) =>
      resolveDoctorPayload(entry?.doctor || entry, {
        entryId: `nearby-${entry?.clinic_id || entry?.id || index}-${
          entry?.doctor?.id || entry?.id || index
        }`,
        source: "nearby",
        clinicId: entry?.clinic_id || entry?.vet_registeration_id || null,
        clinicName: entry?.name,
        clinicCity: entry?.city,
        clinicAddress: entry?.formatted_address || entry?.address,
        clinicPhone: entry?.mobile,
        clinicImage: entry?.image,
        clinicDistance: entry?.distance,
        clinicOpenNow: entry?.open_now,
        clinicRating: entry?.rating,
        clinicReviewCount: entry?.user_ratings_total,
        referralCode: entry?.referral_code,
      })
    )
    .filter(Boolean);

const normalizeFeaturedDoctors = (response) => {
  const featuredData = response?.featured?.data;
  const clinic = featuredData?.clinic || null;
  const doctors = Array.isArray(featuredData?.doctors) ? featuredData.doctors : [];

  return doctors
    .map((doctor, index) =>
      resolveDoctorPayload(doctor, {
        entryId: `featured-${clinic?.id || "x"}-${doctor?.id || index}`,
        source: "featured",
        clinicId: clinic?.id || null,
        clinicName: clinic?.name,
        clinicCity: clinic?.city,
        clinicAddress: clinic?.address,
        clinicPhone: clinic?.phone,
        clinicImage: clinic?.image,
      })
    )
    .filter(Boolean);
};

const matchesSearch = (doctor, query) => {
  const normalizedQuery = normalizeText(query).toLowerCase();
  if (!normalizedQuery) return true;

  return [
    doctor.doctorName,
    doctor.clinicName,
    doctor.clinicCity,
    doctor.clinicAddress,
    doctor.specializationText,
    doctor.degree,
    doctor.doctorLicense,
  ]
    .join(" ")
    .toLowerCase()
    .includes(normalizedQuery);
};

const SummaryStat = ({ value, label }) => (
  <div className="rounded-[20px] bg-white/95 px-4 py-3 text-center shadow-[0_10px_30px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
    <div className="font-display text-xl font-extrabold text-sky-600">{value}</div>
    <div className="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
      {label}
    </div>
  </div>
);

const FilterChip = ({ active, children, onClick }) => (
  <button
    type="button"
    onClick={onClick}
    className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
      active
        ? "border-sky-500 bg-sky-500 text-white shadow-[0_10px_25px_rgba(42,140,251,0.25)]"
        : "border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900"
    }`}
  >
    {children}
  </button>
);

const ProfileMetric = ({ icon: Icon, label, value, emphasize = false }) => {
  if (!hasDisplayValue(value)) return null;

  return (
    <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
      <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
        <Icon className="h-3.5 w-3.5" />
        {label}
      </div>
      <div
        className={`mt-2 text-sm font-bold ${
          emphasize ? "text-sky-700" : "text-slate-900"
        }`}
      >
        {value}
      </div>
    </div>
  );
};

const DoctorCard = ({
  doctor,
  brokenImages,
  onImageError,
  onViewProfile,
}) => {
  const showDoctorImage =
    hasDisplayValue(doctor.doctorImage) && !brokenImages.has(`${doctor.id}-doctor`);
  const showClinicImage =
    hasDisplayValue(doctor.clinicImage) && !brokenImages.has(`${doctor.id}-clinic`);
  const careFocus = getCareFocus(doctor);

  const headerBadges = [
    doctor.source === "featured" ? "Featured" : null,
    doctor.clinicOpenNow === true ? "Open Now" : null,
    hasDisplayValue(doctor.doctorLicense) ? "Verified" : null,
  ].filter(Boolean);

  return (
    <article className="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_12px_40px_rgba(15,23,42,0.08)] transition-transform duration-200 hover:-translate-y-1 hover:shadow-[0_18px_48px_rgba(15,23,42,0.12)]">
      <div className="relative h-48 overflow-hidden">
        {showClinicImage ? (
          <img
            src={doctor.clinicImage}
            alt={doctor.clinicName || doctor.doctorName}
            className="h-full w-full object-cover"
            loading="lazy"
            decoding="async"
            onError={() => onImageError(`${doctor.id}-clinic`)}
          />
        ) : (
          <div className="h-full w-full bg-[linear-gradient(135deg,#082f49_0%,#0f172a_50%,#1d4ed8_100%)]" />
        )}

        <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(15,23,42,0.08)_0%,rgba(15,23,42,0.55)_100%)]" />

        <div className="absolute left-4 right-4 top-4 flex flex-wrap items-center gap-2">
          {headerBadges.map((badge) => (
            <span
              key={`${doctor.id}-${badge}`}
              className={`rounded-full px-3 py-1 text-[11px] font-bold ${
                badge === "Featured"
                  ? "bg-amber-400 text-slate-950"
                  : badge === "Open Now"
                  ? "bg-emerald-500 text-white"
                  : "bg-white/90 text-slate-900"
              }`}
            >
              {badge}
            </span>
          ))}
        </div>

        <div className="absolute bottom-4 left-4 right-4 flex items-end justify-between gap-3">
          <div className="flex min-w-0 items-center gap-3">
            {showDoctorImage ? (
              <img
                src={doctor.doctorImage}
                alt={doctor.doctorName}
                className="h-14 w-14 rounded-2xl border-2 border-white/80 object-cover shadow-lg"
                loading="lazy"
                decoding="async"
                onError={() => onImageError(`${doctor.id}-doctor`)}
              />
            ) : (
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl border-2 border-white/70 bg-white/15 text-base font-extrabold text-white shadow-lg backdrop-blur">
                {getInitials(doctor.doctorName)}
              </div>
            )}

            <div className="min-w-0">
              <h3 className="truncate font-display text-lg font-extrabold text-white">
                {doctor.doctorName}
              </h3>
              <p className="truncate text-sm text-white/80">
                {doctor.clinicName || "SnoutIQ Partner Clinic"}
              </p>
            </div>
          </div>

          <div className="rounded-full bg-white/95 px-3 py-1.5 text-right shadow-lg">
            <div className="flex items-center gap-1 text-sm font-extrabold text-slate-900">
              <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
              {Number.isFinite(doctor.rating) ? doctor.rating.toFixed(1) : "New"}
            </div>
            <div className="text-[11px] font-semibold text-slate-500">
              {doctor.reviewCount || 0} reviews
            </div>
          </div>
        </div>
      </div>

      <div className="space-y-5 p-5">
        <div className="flex flex-wrap items-center gap-2 text-sm text-slate-600">
          <span className="inline-flex items-center gap-1.5">
            <MapPin className="h-4 w-4 text-sky-600" />
            {doctor.clinicCity || doctor.clinicAddress || "Nearby clinic"}
          </span>
          {hasDisplayValue(formatDistance(doctor.clinicDistance)) ? (
            <span className="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700">
              {formatDistance(doctor.clinicDistance)}
            </span>
          ) : null}
        </div>

        <div className="flex flex-wrap gap-2">
          {doctor.specializationList.slice(0, 4).map((specialization) => (
            <span
              key={`${doctor.id}-${specialization}`}
              className={`rounded-full px-3 py-1 text-xs font-semibold ${
                careFocus.exotic &&
                /exotic|avian|bird|rabbit|turtle|guinea|hamster/i.test(
                  specialization
                )
                  ? "bg-amber-50 text-amber-800"
                  : "bg-slate-100 text-slate-700"
              }`}
            >
              {specialization}
            </span>
          ))}
          {!doctor.specializationList.length &&
          hasDisplayValue(doctor.specializationText) ? (
            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
              {doctor.specializationText}
            </span>
          ) : null}
        </div>

        <div className="grid grid-cols-3 gap-3">
          <div className="rounded-2xl bg-slate-50 px-3 py-3 text-center">
            <div className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
              Day
            </div>
            <div className="mt-2 text-sm font-extrabold text-sky-700">
              {formatInr(doctor.priceDay)}
            </div>
          </div>
          <div className="rounded-2xl bg-slate-50 px-3 py-3 text-center">
            <div className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
              Night
            </div>
            <div className="mt-2 text-sm font-extrabold text-slate-900">
              {formatInr(doctor.priceNight)}
            </div>
          </div>
          <div className="rounded-2xl bg-slate-50 px-3 py-3 text-center">
            <div className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
              Response
            </div>
            <div className="mt-2 text-sm font-extrabold text-slate-900">
              {doctor.responseDay || doctor.responseNight || "Fast"}
            </div>
          </div>
        </div>

        <div className="grid gap-3 sm:grid-cols-2">
          <ProfileMetric
            icon={UserRound}
            label="Degree"
            value={doctor.degree || "Veterinary Doctor"}
          />
          <ProfileMetric
            icon={CalendarDays}
            label="Experience"
            value={doctor.experience ? `${doctor.experience} years` : ""}
          />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <a
            href={
              doctor.clinicPhone || doctor.doctorPhone
                ? `tel:${doctor.clinicPhone || doctor.doctorPhone}`
                : "#"
            }
            onClick={(event) => {
              if (!doctor.clinicPhone && !doctor.doctorPhone) {
                event.preventDefault();
              }
            }}
            className={`inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-bold transition ${
              doctor.clinicPhone || doctor.doctorPhone
                ? "bg-[linear-gradient(135deg,#2a8cfb_0%,#1d4ed8_100%)] text-white shadow-[0_14px_30px_rgba(42,140,251,0.28)]"
                : "cursor-not-allowed bg-slate-100 text-slate-400"
            }`}
          >
            <Phone className="h-4 w-4" />
            Book Visit
          </a>

          <button
            type="button"
            onClick={onViewProfile}
            className="inline-flex items-center justify-center gap-2 rounded-2xl border border-sky-300 bg-sky-50 px-4 py-3 text-sm font-bold text-sky-700 transition hover:border-sky-400 hover:bg-sky-100"
          >
            View Profile
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      </div>
    </article>
  );
};

const ProfileModal = ({ doctor, onClose, brokenImages, onImageError }) => {
  useEffect(() => {
    if (!doctor) return undefined;

    const handleEscape = (event) => {
      if (event.key === "Escape") onClose();
    };

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", handleEscape);

    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", handleEscape);
    };
  }, [doctor, onClose]);

  if (!doctor) return null;

  const showDoctorImage =
    hasDisplayValue(doctor.doctorImage) &&
    !brokenImages.has(`${doctor.id}-doctor-modal`);

  return (
    <div
      className="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/70 p-2 backdrop-blur-sm md:items-center md:p-6"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="relative max-h-[92vh] w-full max-w-3xl overflow-hidden rounded-[30px] bg-white shadow-[0_30px_100px_rgba(15,23,42,0.4)]"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-label={`${doctor.doctorName} profile`}
      >
        <div className="relative overflow-hidden bg-[linear-gradient(135deg,#082f49_0%,#0f172a_45%,#1d4ed8_100%)] px-5 pb-6 pt-5 text-white md:px-8">
          <button
            type="button"
            onClick={onClose}
            className="absolute right-4 top-4 z-10 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
            aria-label="Close profile"
          >
            <X className="h-5 w-5" />
          </button>

          <div className="relative z-10 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div className="flex items-center gap-4">
              {showDoctorImage ? (
                <img
                  src={doctor.doctorImage}
                  alt={doctor.doctorName}
                  className="h-20 w-20 rounded-[24px] border-2 border-white/70 object-cover shadow-xl"
                  loading="lazy"
                  decoding="async"
                  onError={() => onImageError(`${doctor.id}-doctor-modal`)}
                />
              ) : (
                <div className="flex h-20 w-20 items-center justify-center rounded-[24px] border-2 border-white/40 bg-white/15 text-2xl font-extrabold text-white">
                  {getInitials(doctor.doctorName)}
                </div>
              )}

              <div className="min-w-0">
                <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/90 backdrop-blur">
                  <Sparkles className="h-3.5 w-3.5" />
                  Professional Profile
                </div>
                <h2 className="mt-3 font-display text-2xl font-extrabold md:text-3xl">
                  {doctor.doctorName}
                </h2>
                <p className="mt-1 text-sm text-white/80">
                  {doctor.degree || "Veterinary Doctor"}
                  {doctor.experience
                    ? ` | ${doctor.experience} years experience`
                    : ""}
                </p>
                <p className="mt-1 text-sm text-white/70">
                  {doctor.clinicName || "SnoutIQ Partner Clinic"}
                </p>
              </div>
            </div>

            <div className="rounded-[22px] bg-white/12 px-4 py-3 text-sm backdrop-blur">
              <div className="flex items-center gap-2 font-extrabold">
                <Star className="h-4 w-4 fill-amber-300 text-amber-300" />
                {Number.isFinite(doctor.rating) ? doctor.rating.toFixed(1) : "New"}
              </div>
              <div className="mt-1 text-white/75">
                {doctor.reviewCount || 0} review
                {doctor.reviewCount === 1 ? "" : "s"}
              </div>
            </div>
          </div>
        </div>

        <div className="max-h-[calc(92vh-210px)] overflow-y-auto px-5 py-5 md:px-8 md:py-7">
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <ProfileMetric
              icon={IndianRupee}
              label="Day Consult"
              value={formatInr(doctor.priceDay)}
              emphasize
            />
            <ProfileMetric
              icon={IndianRupee}
              label="Night Consult"
              value={formatInr(doctor.priceNight)}
            />
            <ProfileMetric
              icon={Clock3}
              label="Response Day"
              value={doctor.responseDay || doctor.responseNight || ""}
            />
            <ProfileMetric
              icon={ShieldCheck}
              label="License"
              value={doctor.doctorLicense}
            />
          </div>

          <div className="mt-6 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <div className="space-y-6">
              {(doctor.specializationList.length > 0 ||
                hasDisplayValue(doctor.specializationText)) && (
                <section className="rounded-[26px] border border-slate-200 bg-slate-50 p-5">
                  <div className="flex items-center gap-2 text-sm font-extrabold text-slate-900">
                    <Stethoscope className="h-4 w-4 text-sky-600" />
                    Specialization
                  </div>
                  <div className="mt-4 flex flex-wrap gap-2">
                    {(doctor.specializationList.length
                      ? doctor.specializationList
                      : [doctor.specializationText]
                    ).map((specialization) => (
                      <span
                        key={`${doctor.id}-specialization-${specialization}`}
                        className="rounded-full bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200"
                      >
                        {specialization}
                      </span>
                    ))}
                  </div>
                </section>
              )}

              {hasDisplayValue(doctor.bio) ? (
                <section className="rounded-[26px] border border-slate-200 bg-white p-5">
                  <div className="flex items-center gap-2 text-sm font-extrabold text-slate-900">
                    <Sparkles className="h-4 w-4 text-amber-500" />
                    About the Doctor
                  </div>
                  <p className="mt-4 text-sm leading-7 text-slate-600">
                    {doctor.bio}
                  </p>
                </section>
              ) : null}
            </div>

            <div className="space-y-6">
              <section className="rounded-[26px] border border-slate-200 bg-slate-50 p-5">
                <div className="text-sm font-extrabold text-slate-900">
                  Professional Snapshot
                </div>
                <div className="mt-4 space-y-3">
                  <ProfileMetric
                    icon={UserRound}
                    label="Languages"
                    value={doctor.languages.join(", ")}
                  />
                  <ProfileMetric
                    icon={CalendarDays}
                    label="Free Follow-up"
                    value={doctor.freeFollowUp}
                  />
                  <ProfileMetric
                    icon={Clock3}
                    label="Do Not Disturb"
                    value={doctor.breakTimes.join(", ")}
                  />
                </div>
              </section>

              <section className="rounded-[26px] border border-slate-200 bg-[linear-gradient(135deg,#eff6ff_0%,#ffffff_100%)] p-5">
                <div className="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-amber-800">
                  <Sparkles className="h-3.5 w-3.5" />
                  Book Directly
                </div>
                <h3 className="mt-4 font-display text-xl font-extrabold text-slate-900">
                  View profile first, then book with confidence.
                </h3>
                <div className="mt-5 grid gap-3">
                  <a
                    href={
                      doctor.clinicPhone || doctor.doctorPhone
                        ? `tel:${doctor.clinicPhone || doctor.doctorPhone}`
                        : "#"
                    }
                    onClick={(event) => {
                      if (!doctor.clinicPhone && !doctor.doctorPhone) {
                        event.preventDefault();
                      }
                    }}
                    className={`inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-bold transition ${
                      doctor.clinicPhone || doctor.doctorPhone
                        ? "bg-[linear-gradient(135deg,#2a8cfb_0%,#1d4ed8_100%)] text-white shadow-[0_16px_30px_rgba(42,140,251,0.3)]"
                        : "cursor-not-allowed bg-slate-200 text-slate-500"
                    }`}
                  >
                    <Phone className="h-4 w-4" />
                    Book Visit
                  </a>
                  <button
                    type="button"
                    onClick={onClose}
                    className="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                  >
                    Close
                  </button>
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default function FindVetsNearYou() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [userIdInput, setUserIdInput] = useState(
    searchParams.get("user_id") || searchParams.get("userId") || ""
  );
  const [searchTerm, setSearchTerm] = useState("");
  const deferredSearch = useDeferredValue(searchTerm);
  const [activeFilter, setActiveFilter] = useState("all");
  const [refreshNonce, setRefreshNonce] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [payload, setPayload] = useState(null);
  const [nearbyDoctors, setNearbyDoctors] = useState([]);
  const [featuredDoctors, setFeaturedDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [brokenImages, setBrokenImages] = useState(() => new Set());

  const resolvedUserId =
    searchParams.get("user_id") || searchParams.get("userId") || "";
  const queryDate = searchParams.get("date") || "";
  const queryDay = searchParams.get("day") || "";

  useEffect(() => {
    setUserIdInput(resolvedUserId);
  }, [resolvedUserId]);

  useEffect(() => {
    if (!resolvedUserId) {
      setPayload(null);
      setNearbyDoctors([]);
      setFeaturedDoctors([]);
      setError("");
      setLoading(false);
      return undefined;
    }

    const controller = new AbortController();

    const loadDoctors = async () => {
      setLoading(true);
      setError("");

      try {
        const params = new URLSearchParams();
        params.set("user_id", resolvedUserId);
        if (queryDate) params.set("date", queryDate);
        if (queryDay) params.set("day", queryDay);

        const response = await fetch(
          `${getBackendBase()}${API_ENDPOINT}?${params.toString()}`,
          {
            method: "GET",
            signal: controller.signal,
            headers: { Accept: "application/json" },
          }
        );

        const data = await response.json().catch(() => null);
        if (!response.ok) {
          throw new Error(
            data?.message || data?.error || `HTTP ${response.status}`
          );
        }

        setPayload(data);
        setNearbyDoctors(normalizeNearbyDoctors(data));
        setFeaturedDoctors(normalizeFeaturedDoctors(data));
      } catch (fetchError) {
        if (fetchError?.name === "AbortError") return;
        setPayload(null);
        setNearbyDoctors([]);
        setFeaturedDoctors([]);
        setError(
          fetchError?.message ||
            "Nearby doctors could not be loaded right now."
        );
      } finally {
        if (!controller.signal.aborted) setLoading(false);
      }
    };

    void loadDoctors();

    return () => controller.abort();
  }, [resolvedUserId, queryDate, queryDay, refreshNonce]);

  const featuredClinic = payload?.featured?.data?.clinic || null;

  const cityFilters = useMemo(() => {
    const cities = dedupeList(
      nearbyDoctors.map((doctor) => doctor.clinicCity).filter(Boolean)
    );
    return cities.slice(0, 6).map((city) => ({
      key: `city:${city}`,
      label: city,
    }));
  }, [nearbyDoctors]);

  const coverageLabel = useMemo(() => {
    const cities = dedupeList(
      [...nearbyDoctors, ...featuredDoctors]
        .map((doctor) => doctor.clinicCity)
        .filter(Boolean)
    );
    if (!cities.length) return "Nearby verified clinics";
    if (cities.length === 1) return cities[0];
    if (cities.length === 2) return `${cities[0]} & ${cities[1]}`;
    return `${cities[0]}, ${cities[1]} +${cities.length - 2} more`;
  }, [featuredDoctors, nearbyDoctors]);

  const filteredDoctors = useMemo(
    () =>
      nearbyDoctors.filter((doctor) => {
        if (!matchesSearch(doctor, deferredSearch)) return false;
        if (activeFilter === "open_now" && doctor.clinicOpenNow !== true) {
          return false;
        }
        if (
          activeFilter === "top_rated" &&
          (!Number.isFinite(doctor.rating) || doctor.rating < 4.5)
        ) {
          return false;
        }
        if (activeFilter === "dogs_cats" && !getCareFocus(doctor).dogsCats) {
          return false;
        }
        if (activeFilter === "exotic" && !getCareFocus(doctor).exotic) {
          return false;
        }
        if (activeFilter.startsWith("city:")) {
          return doctor.clinicCity === activeFilter.replace(/^city:/, "");
        }
        return true;
      }),
    [activeFilter, deferredSearch, nearbyDoctors]
  );

  const stats = useMemo(() => {
    const uniqueClinics = new Set(
      nearbyDoctors.map((doctor) => doctor.clinicId || doctor.clinicName)
    );
    const ratings = nearbyDoctors
      .map((doctor) => doctor.rating)
      .filter((value) => Number.isFinite(value));

    return {
      doctors: nearbyDoctors.length,
      clinics: uniqueClinics.size,
      rating: ratings.length
        ? (
            ratings.reduce((sum, value) => sum + value, 0) / ratings.length
          ).toFixed(1)
        : "0.0",
      featured: featuredDoctors.length,
    };
  }, [featuredDoctors.length, nearbyDoctors]);

  const responseDateLabel = useMemo(() => {
    if (!payload?.date && !payload?.day) return "";
    const parts = [];
    if (payload?.date) parts.push(payload.date);
    if (payload?.day) parts.push(payload.day);
    return parts.join(" • ");
  }, [payload?.date, payload?.day]);

  const handleLoadUser = () => {
    const next = new URLSearchParams(searchParams);
    if (userIdInput) next.set("user_id", userIdInput.trim());
    else next.delete("user_id");
    setSearchParams(next);
  };

  const markImageBroken = (key) => {
    setBrokenImages((previous) => {
      if (previous.has(key)) return previous;
      const next = new Set(previous);
      next.add(key);
      return next;
    });
  };

  return (
    <>
      <Helmet>
        <title>{PAGE_TITLE}</title>
        <meta name="description" content={PAGE_DESCRIPTION} />
        <link rel="canonical" href="https://snoutiq.com/find-vets-near-you" />
      </Helmet>

      <div className="min-h-screen bg-[linear-gradient(180deg,#eff6ff_0%,#f8fafc_20%,#f8fafc_100%)]">
        <Navbar consultPath="/20+vetsonline?start=details" />

        <main className="pb-16">
          <section className="relative overflow-hidden px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
              <div className="relative overflow-hidden rounded-[34px] bg-[linear-gradient(135deg,#082f49_0%,#0f172a_45%,#1d4ed8_100%)] px-5 pb-8 pt-6 text-white shadow-[0_24px_70px_rgba(15,23,42,0.22)] sm:px-7 md:px-10">
                <div className="absolute right-[-120px] top-[-100px] h-72 w-72 rounded-full bg-sky-400/20 blur-3xl" />
                <div className="absolute bottom-[-140px] left-[-60px] h-64 w-64 rounded-full bg-amber-400/15 blur-3xl" />

                <div className="relative z-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-end">
                  <div>
                    <div className="inline-flex items-center gap-2 rounded-full border border-amber-300/25 bg-amber-400/10 px-4 py-2 text-sm font-semibold text-amber-200">
                      <MapPin className="h-4 w-4" />
                      {coverageLabel}
                    </div>

                    <h1 className="mt-5 font-display text-3xl font-extrabold leading-tight sm:text-4xl md:text-5xl">
                      Find the best{" "}
                      <span className="text-amber-300">vet doctor</span> near
                      you
                    </h1>

                    <p className="mt-4 max-w-2xl text-sm leading-7 text-white/75 sm:text-base">
                      Nearby doctors come from `nearby-plus-featured`. The page
                      uses backend-provided clinic distance, doctor profile,
                      pricing, response time, and featured clinic history.
                    </p>

                    <div className="mt-6 inline-flex items-center gap-3 rounded-full bg-[linear-gradient(135deg,#f97316_0%,#f59e0b_100%)] px-5 py-3 text-sm font-bold text-white shadow-[0_16px_35px_rgba(249,115,22,0.3)]">
                      <Sparkles className="h-4 w-4" />
                      First visit? Get ₹100 OFF with {OFFER_CODE}
                    </div>
                  </div>

                  <div className="rounded-[28px] bg-white/10 p-4 backdrop-blur md:p-5">
                    <div className="relative">
                      <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-white/60" />
                      <input
                        value={searchTerm}
                        onChange={(event) => setSearchTerm(event.target.value)}
                        placeholder="Search doctor, clinic, city or specialization..."
                        className="w-full rounded-[20px] border border-white/10 bg-white/95 py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none ring-0 placeholder:text-slate-400"
                      />
                    </div>

                    <div className="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                      <input
                        value={userIdInput}
                        onChange={(event) => setUserIdInput(event.target.value)}
                        placeholder="Enter backend user_id to load nearby doctors"
                        className="w-full rounded-[18px] border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white outline-none placeholder:text-white/45"
                      />
                      <div className="grid grid-cols-2 gap-3 sm:flex">
                        <button
                          type="button"
                          onClick={handleLoadUser}
                          className="rounded-[18px] bg-white px-4 py-3 text-sm font-bold text-slate-900 transition hover:bg-slate-100"
                        >
                          Load
                        </button>
                        <button
                          type="button"
                          onClick={() => setRefreshNonce((value) => value + 1)}
                          className="inline-flex items-center justify-center gap-2 rounded-[18px] border border-white/20 bg-white/10 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/15"
                        >
                          <Crosshair className="h-4 w-4" />
                          Near Me
                        </button>
                      </div>
                    </div>

                    <div className="mt-3 text-xs leading-6 text-white/65">
                      Backend rule: this endpoint requires exact `user_id`, and
                      that user must already have latitude/longitude saved.
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <SummaryStat value={stats.doctors} label="Doctors" />
                <SummaryStat value={stats.clinics} label="Clinics" />
                <SummaryStat value={`${stats.rating} ★`} label="Avg Rating" />
                <SummaryStat value={stats.featured} label="Featured" />
              </div>
            </div>
          </section>

          <section className="px-4 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
              <div className="flex flex-wrap gap-2 rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm">
                {GENERAL_FILTERS.map((filter) => (
                  <FilterChip
                    key={filter.key}
                    active={activeFilter === filter.key}
                    onClick={() => setActiveFilter(filter.key)}
                  >
                    {filter.label}
                  </FilterChip>
                ))}

                {cityFilters.map((filter) => (
                  <FilterChip
                    key={filter.key}
                    active={activeFilter === filter.key}
                    onClick={() => setActiveFilter(filter.key)}
                  >
                    {filter.label}
                  </FilterChip>
                ))}
              </div>
            </div>
          </section>

          <section className="px-4 pt-6 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
              {featuredClinic || featuredDoctors.length ? (
                <div className="overflow-hidden rounded-[30px] border border-amber-200 bg-[linear-gradient(135deg,#fff7ed_0%,#ffffff_100%)] shadow-sm">
                  <div className="flex flex-col gap-5 p-5 md:flex-row md:items-start md:justify-between md:p-6">
                    <div className="max-w-2xl">
                      <div className="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-amber-800">
                        <Sparkles className="h-3.5 w-3.5" />
                        Featured from your last clinic
                      </div>
                      <h2 className="mt-4 font-display text-2xl font-extrabold text-slate-900">
                        {featuredClinic?.name || "Previously visited clinic"}
                      </h2>
                    </div>

                    <div className="rounded-[22px] bg-white px-4 py-3 shadow-sm ring-1 ring-amber-100">
                      <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                        Featured doctors
                      </div>
                      <div className="mt-2 font-display text-3xl font-extrabold text-amber-600">
                        {featuredDoctors.length}
                      </div>
                    </div>
                  </div>

                  {featuredDoctors.length ? (
                    <div className="grid gap-4 border-t border-amber-100 p-5 md:grid-cols-2 md:p-6 xl:grid-cols-3">
                      {featuredDoctors.map((doctor) => (
                        <DoctorCard
                          key={doctor.id}
                          doctor={doctor}
                          brokenImages={brokenImages}
                          onImageError={markImageBroken}
                          onViewProfile={() => setSelectedDoctor(doctor)}
                        />
                      ))}
                    </div>
                  ) : null}
                </div>
              ) : null}
            </div>
          </section>

          <section className="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
              <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                  <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                    Nearby doctors
                  </div>
                  <h2 className="mt-2 font-display text-2xl font-extrabold text-slate-900">
                    Doctors matched from backend availability
                  </h2>
                </div>

                <div className="text-sm text-slate-500">
                  {responseDateLabel
                    ? `Response window: ${responseDateLabel}`
                    : null}
                  {responseDateLabel && filteredDoctors.length ? " | " : null}
                  {filteredDoctors.length} result
                  {filteredDoctors.length === 1 ? "" : "s"}
                </div>
              </div>

              {loading ? (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                  {Array.from({ length: 6 }).map((_, index) => (
                    <div
                      key={`skeleton-${index}`}
                      className="h-[420px] animate-pulse rounded-[28px] border border-slate-200 bg-white shadow-sm"
                    />
                  ))}
                </div>
              ) : error ? (
                <div className="rounded-[28px] border border-rose-200 bg-rose-50 p-6 text-rose-700 shadow-sm">
                  <div className="font-display text-xl font-extrabold">
                    Doctors could not be loaded
                  </div>
                  <p className="mt-2 text-sm leading-6">{error}</p>
                </div>
              ) : !resolvedUserId ? (
                <div className="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                  <div className="font-display text-xl font-extrabold text-slate-900">
                    Add `user_id` to load nearby results
                  </div>
                  <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    `nearby-plus-featured` does not work without `user_id`. You
                    can either enter it in the hero input or open this page with
                    a query like `/find-vets-near-you?user_id=123`.
                  </p>
                </div>
              ) : filteredDoctors.length === 0 ? (
                <div className="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                  <div className="font-display text-xl font-extrabold text-slate-900">
                    No nearby doctors matched this filter
                  </div>
                  <p className="mt-2 text-sm leading-6 text-slate-600">
                    Try removing city or specialization filters, or refresh with
                    another backend `user_id`.
                  </p>
                </div>
              ) : (
                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                  {filteredDoctors.map((doctor) => (
                    <DoctorCard
                      key={doctor.id}
                      doctor={doctor}
                      brokenImages={brokenImages}
                      onImageError={markImageBroken}
                      onViewProfile={() => setSelectedDoctor(doctor)}
                    />
                  ))}
                </div>
              )}
            </div>
          </section>
        </main>

        <Footer />
      </div>

      <ProfileModal
        doctor={selectedDoctor}
        onClose={() => setSelectedDoctor(null)}
        brokenImages={brokenImages}
        onImageError={markImageBroken}
      />
    </>
  );
}
