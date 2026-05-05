import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { readAiAuthState } from "./AiAuth";
import { IN_CLINIC_PRICING } from "../newflow/askBooking/inClinicFlowShared";
import { confirmPaymentStart, showBookingError, showBookingWarning } from "./booking/bookingAlerts";
import { fetchPetOverview } from "./petOverviewService";

const API_BASE = "https://snoutiq.com/backend/api";

const DEFAULT_ENDPOINTS = {
  clinics: "/users/nearby-clinics",
  clinicFallback: "/nearby-plus-featured",
  clinicDoctors: null,
  clinicAvailability: "/clinics/:clinicId/doctor-availability?service_type=in_clinic&date=:date",
  doctorSlotsSummary: "/doctors/:doctorId/slots/summary?date=:date&service_type=in_clinic",
  serviceBookings: "/chat/service-bookings",
  createOrder: "/create-order",
  verifyPayment: "/rzp/verify",
  submitAppointment: "/appointments/submit",
  checkByPayment: "/appointments/check-by-payment",
  mediaQuestion: "/chat/dog-disease/question",
};

const PAYMENT_PHASES = Object.freeze({
  idle: "idle",
  submitting: "submitting",
  success_processing: "success_processing",
  redirecting: "redirecting",
});

function normalizeText(value) {
  return String(value || "").trim();
}

function normalizePhone(value) {
  return String(value || "").replace(/[^\d+]/g, "").trim();
}

function normalizeImage(value) {
  const text = normalizeText(value);
  if (!text) return "";
  if (/^https?:\/\//i.test(text)) return text;
  if (text.startsWith("/")) return `https://snoutiq.com${text}`;
  return `https://snoutiq.com/${text}`;
}

function parseDate(value) {
  if (!value) return null;
  if (value instanceof Date) return value;
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function toDateOnly(value) {
  const date = parseDate(value);
  if (!date) return "";
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function formatDateLabel(value) {
  const date = parseDate(value);
  if (!date) return normalizeText(value);
  return date.toLocaleDateString("en-IN", {
    weekday: "short",
    day: "numeric",
    month: "short",
  });
}

function formatCurrency(value) {
  const amount = Number(value || 0);
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0,
  }).format(Number.isFinite(amount) ? amount : 0);
}

function toInteger(value, fallback = 0) {
  const amount = Math.round(Number(value));
  return Number.isFinite(amount) ? amount : fallback;
}

function parsePositiveId(...values) {
  for (const value of values) {
    const text = normalizeText(value);
    if (!text) continue;
    const parsed = Number.parseInt(text, 10);
    if (Number.isFinite(parsed) && parsed > 0) return parsed;
  }
  return 0;
}

function pickFirst(...values) {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    if (typeof value === "string") {
      const text = value.trim();
      if (text) return text;
      continue;
    }
    return value;
  }
  return "";
}

function parseTimeForApi(value) {
  const text = normalizeText(value).toUpperCase();
  if (!text) return "";
  if (/^\d{2}:\d{2}$/.test(text)) return `${text}:00`;
  if (/^\d{1,2}:\d{2}\s?(AM|PM)$/.test(text)) {
    const parsed = new Date(`1970-01-01 ${text}`);
    if (!Number.isNaN(parsed.getTime())) {
      return `${String(parsed.getHours()).padStart(2, "0")}:${String(parsed.getMinutes()).padStart(2, "0")}:00`;
    }
  }
  return text;
}

async function readApiBody(response) {
  const text = await response.text();
  if (!text) return {};
  try {
    return JSON.parse(text);
  } catch {
    return { raw: text };
  }
}

function stripEmpty(payload) {
  return Object.fromEntries(
    Object.entries(payload).filter(
      ([, value]) => value !== undefined && value !== null && value !== "",
    ),
  );
}

function getHeaders(token, extra = {}) {
  const headers = { ...extra };
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

function parseSpecializations(value) {
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
    } catch (_) {
      // fallback
    }
  }

  return text
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function resolveClinicId(clinic) {
  return normalizeText(
    clinic?.clinic_id || clinic?.clinicId || clinic?.id || clinic?.vet_id || clinic?.vetId || ""
  );
}

function resolveDoctorId(doctor) {
  return normalizeText(
    doctor?.doctor_id ||
      doctor?.doctorId ||
      doctor?.id ||
      doctor?.doctor?.doctor_id ||
      doctor?.doctor?.doctorId ||
      doctor?.doctor?.id ||
      doctor?.doctor_user_id ||
      doctor?.doctorUserId ||
      ""
  );
}

function resolveDoctorName(doctor) {
  return normalizeText(
    doctor?.doctor_name || doctor?.doctorName || doctor?.name || doctor?.doctor?.name || "Doctor"
  );
}

function resolveDoctorPrice(doctor) {
  const raw =
    doctor?.doctors_price ??
    doctor?.doctorsPrice ??
    doctor?.video_day_rate ??
    doctor?.video_night_rate ??
    doctor?.price ??
    doctor?.fee ??
    doctor?.consult_fee ??
    doctor?.doctor?.doctors_price ??
    doctor?.doctor?.doctorsPrice ??
    doctor?.doctor?.video_day_rate ??
    doctor?.doctor?.video_night_rate ??
    doctor?.doctor?.price ??
    doctor?.doctor?.fee ??
    0;

  const parsed = Number(String(raw).replace(/[^\d.]/g, ""));
  return Number.isFinite(parsed) ? parsed : 0;
}

function resolveDoctorImage(doctor) {
  return normalizeImage(
    doctor?.doctor_image_url ||
      doctor?.doctor_image_blob_url ||
      doctor?.doctor_image ||
      doctor?.image ||
      doctor?.photo ||
      doctor?.avatar ||
      doctor?.doctor?.doctor_image_url ||
      doctor?.doctor?.doctor_image_blob_url
  );
}

function resolveDoctorExperience(doctor) {
  const value = Number(
    doctor?.years_of_experience || doctor?.experience || doctor?.exp_years || doctor?.doctor?.experience || 0
  );
  return Number.isFinite(value) ? value : 0;
}

function resolveDoctorRating(doctor) {
  const value = Number(
    doctor?.average_review_points || doctor?.avg_review_points || doctor?.rating || doctor?.avg_rating || 0
  );
  return Number.isFinite(value) ? value : 0;
}

function resolveClinicName(clinic) {
  return normalizeText(clinic?.clinic_name || clinic?.clinicName || clinic?.name || clinic?.vet_name || "Clinic");
}

function resolveClinicAddress(clinic) {
  return normalizeText(
    clinic?.address || clinic?.location || clinic?.clinic_address || clinic?.clinicAddress || clinic?.city || ""
  );
}

function resolveDistance(clinic) {
  const value = Number(clinic?.distance || clinic?.distance_km || clinic?.distanceKm || 0);
  if (!Number.isFinite(value) || value <= 0) return "";
  return `${value.toFixed(value < 10 ? 1 : 0)} km away`;
}

function normalizeClinicRecord(clinic) {
  if (!clinic || typeof clinic !== "object") return null;
  const nested = clinic?.clinic && typeof clinic.clinic === "object" ? clinic.clinic : null;
  const merged = nested ? { ...clinic, ...nested } : clinic;
  const doctors = Array.isArray(merged?.doctors)
    ? merged.doctors
    : merged?.doctor && typeof merged.doctor === "object"
      ? [merged.doctor]
      : [];
  return {
    raw: clinic,
    id: resolveClinicId(merged),
    name: resolveClinicName(merged),
    address: resolveClinicAddress(merged),
    distance: resolveDistance(merged),
    image: normalizeImage(merged?.image || merged?.clinic_image || merged?.photo || merged?.banner),
    mobile: normalizePhone(merged?.clinic_mobile || merged?.mobile || merged?.phone || ""),
    email: normalizeText(merged?.clinic_email || merged?.email || ""),
    city: normalizeText(merged?.clinic_city || merged?.city || ""),
    pincode: normalizeText(merged?.clinic_pincode || merged?.pincode || ""),
    lat: pickFirst(merged?.clinic_lat, merged?.lat, merged?.latitude),
    lng: pickFirst(merged?.clinic_lng, merged?.lng, merged?.longitude),
    placeId: normalizeText(merged?.clinic_place_id || merged?.place_id || merged?.placeId || ""),
    doctors,
  };
}

function normalizeDoctorRecord(doctor, clinic = null) {
  if (!doctor || typeof doctor !== "object") return null;
  return {
    raw: doctor,
    id: resolveDoctorId(doctor),
    name: resolveDoctorName(doctor),
    price: resolveDoctorPrice(doctor),
    image: resolveDoctorImage(doctor),
    experience: resolveDoctorExperience(doctor),
    rating: resolveDoctorRating(doctor),
    mobile: normalizePhone(doctor?.mobile || doctor?.doctor_mobile || doctor?.phone || ""),
    specializations: parseSpecializations(
      doctor?.specialization_select_all_that_apply || doctor?.specialization || doctor?.speciality || doctor?.specialty
    ),
    clinicId: resolveClinicId(clinic || doctor?.clinic || {}),
    clinicName: resolveClinicName(clinic || doctor?.clinic || {}),
  };
}

function flattenClinics(payload) {
  const candidates = [
    ...(Array.isArray(payload?.nearby?.data) ? payload.nearby.data : []),
    ...(Array.isArray(payload?.featured?.data) ? payload.featured.data : []),
    ...(Array.isArray(payload?.data?.data) ? payload.data.data : []),
    ...(Array.isArray(payload?.data) ? payload.data : []),
    ...(Array.isArray(payload) ? payload : []),
  ];

  const seen = new Set();
  const clinics = [];

  candidates.forEach((item) => {
    const clinic = normalizeClinicRecord(item);
    if (!clinic || !clinic.id || seen.has(clinic.id)) return;
    seen.add(clinic.id);
    clinics.push(clinic);
  });

  return clinics;
}

function flattenDoctorsFromClinic(clinic) {
  const doctors = Array.isArray(clinic?.raw?.doctors)
    ? clinic.raw.doctors
    : clinic?.raw?.doctor && typeof clinic.raw.doctor === "object"
      ? [clinic.raw.doctor]
    : Array.isArray(clinic?.doctors)
      ? clinic.doctors
      : clinic?.doctor && typeof clinic.doctor === "object"
        ? [clinic.doctor]
      : [];

  const seen = new Set();
  const items = [];

  doctors.forEach((doctor) => {
    const normalized = normalizeDoctorRecord(doctor, clinic);
    if (!normalized || !normalized.id || seen.has(normalized.id)) return;
    seen.add(normalized.id);
    items.push(normalized);
  });

  return items;
}

function buildDateStrip(total = 14) {
  const dates = [];
  const today = new Date();
  for (let i = 0; i < total; i += 1) {
    const next = new Date(today);
    next.setDate(today.getDate() + i);
    dates.push({
      iso: toDateOnly(next),
      label: formatDateLabel(next),
    });
  }
  return dates;
}

function normalizeSlots(payload) {
  const raw = Array.isArray(payload?.data?.slots)
    ? payload.data.slots
    : Array.isArray(payload?.data?.available_slots)
      ? payload.data.available_slots
      : Array.isArray(payload?.data?.summary?.slots)
        ? payload.data.summary.slots
        : Array.isArray(payload?.available_slots)
          ? payload.available_slots
    : Array.isArray(payload?.slots)
      ? payload.slots
      : Array.isArray(payload?.data)
        ? payload.data
        : Array.isArray(payload)
          ? payload
          : [];

  return raw
    .map((slot, index) => {
      if (typeof slot === "string") {
        return {
          id: `${slot}-${index}`,
          label: slot,
          value: slot,
          available: true,
        };
      }

      const label = normalizeText(slot?.slot || slot?.time || slot?.label || slot?.start_time || slot?.startTime);
      if (!label) return null;
      return {
        id: normalizeText(slot?.id || slot?.slot_id || `${label}-${index}`),
        label,
        value: label,
        available: slot?.available !== false && slot?.is_available !== false,
      };
    })
    .filter(Boolean);
}

function normalizeDoctorsFromAvailability(payload, clinic) {
  const raw = Array.isArray(payload?.data?.doctors)
    ? payload.data.doctors
    : Array.isArray(payload?.doctors)
      ? payload.doctors
      : Array.isArray(payload?.data?.availability)
        ? payload.data.availability
        : Array.isArray(payload?.availability)
          ? payload.availability
          : Array.isArray(payload?.data)
            ? payload.data
            : [];

  const seen = new Set();
  return raw
    .map((item) => normalizeDoctorRecord(item?.doctor || item, clinic))
    .filter((doctor) => {
      if (!doctor?.id || seen.has(doctor.id)) return false;
      seen.add(doctor.id);
      return true;
    });
}

function buildStaticSlots(dateValue) {
  const selected = parseDate(dateValue);
  const todayIso = toDateOnly(new Date());
  const selectedIso = toDateOnly(selected || new Date());
  const now = new Date();
  const minTodayTime = new Date(now.getTime() + 90 * 60 * 1000);

  return Array.from({ length: 19 }, (_, index) => {
    const hour = 10 + Math.floor(index / 2);
    const minute = index % 2 === 0 ? 0 : 30;
    return { hour, minute };
  })
    .filter(({ hour, minute }) => hour < 19 || (hour === 19 && minute === 0))
    .filter(({ hour, minute }) => {
      if (selectedIso !== todayIso) return true;
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
        label,
        value: label,
        available: true,
        fallback: true,
      };
    });
}

function StepCard({ step, title, subtitle, open, onEdit, summary, children }) {
  return (
    <section className="rounded-[28px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-start gap-3">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-sm font-bold text-white">
            {step}
          </div>
          <div>
            <h3 className="text-base font-semibold text-slate-900 sm:text-lg">{title}</h3>
            {subtitle ? <p className="mt-1 text-sm text-slate-500">{subtitle}</p> : null}
          </div>
        </div>
        {!open && onEdit ? (
          <button
            type="button"
            onClick={onEdit}
            className="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
          >
            Edit
          </button>
        ) : null}
      </div>

      {open ? (
        <div className="mt-4">{children}</div>
      ) : (
        <button
          type="button"
          onClick={onEdit}
          className="mt-4 flex w-full items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 text-left hover:bg-slate-100"
        >
          <span className="truncate text-sm font-medium text-slate-700">{summary || "Completed"}</span>
          <span className="text-xs font-semibold text-blue-600">Open</span>
        </button>
      )}
    </section>
  );
}

function UploadPreview({ files, onRemove }) {
  if (!files.length) {
    return <p className="text-sm text-slate-500">Optional: photo ya report upload kar sakte ho.</p>;
  }

  return (
    <div className="mt-3 flex flex-wrap gap-3">
      {files.map((file) => {
        const url = URL.createObjectURL(file);
        return (
          <div key={`${file.name}-${file.size}`} className="relative h-20 w-20 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
            {file.type.startsWith("image/") ? (
              <img src={url} alt={file.name} className="h-full w-full object-cover" />
            ) : (
              <div className="flex h-full w-full items-center justify-center px-2 text-center text-[11px] font-medium text-slate-500">
                {file.name}
              </div>
            )}
            <button
              type="button"
              onClick={() => onRemove(file)}
              className="absolute right-1 top-1 rounded-full bg-slate-900/80 px-1.5 py-0.5 text-[10px] font-bold text-white"
            >
              ×
            </button>
          </div>
        );
      })}
    </div>
  );
}

function buildAvailabilityUrl(template, clinicId, date) {
  return template.replace(":clinicId", encodeURIComponent(clinicId)).replace(":date", encodeURIComponent(date));
}

function buildDoctorSlotsUrl(template, doctorId, date) {
  return template.replace(":doctorId", encodeURIComponent(doctorId)).replace(":date", encodeURIComponent(date));
}

function loadRazorpayScript() {
  return new Promise((resolve) => {
    if (window.Razorpay) {
      resolve(true);
      return;
    }

    const existing = document.querySelector('script[data-razorpay="true"]');
    if (existing) {
      existing.addEventListener("load", () => resolve(true));
      existing.addEventListener("error", () => resolve(false));
      return;
    }

    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.dataset.razorpay = "true";
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
}

export default function InClinicFastBookingFlow({
  user = {},
  token = "",
  apiBase = API_BASE,
  endpoints = DEFAULT_ENDPOINTS,
  onSuccess,
}) {
  const location = useLocation();
  const navigate = useNavigate();
  const paymentInFlightRef = useRef(false);
  const effectiveEndpoints = useMemo(
    () => ({ ...DEFAULT_ENDPOINTS, ...(endpoints || {}) }),
    [endpoints],
  );
  const routeState =
    location?.state && typeof location.state === "object" ? location.state : {};
  const authState = useMemo(() => readAiAuthState(), []);
  const authUser =
    authState?.user && typeof authState.user === "object" ? authState.user : {};
  const routeUser =
    routeState?.user && typeof routeState.user === "object" ? routeState.user : {};
  const resolvedUser = useMemo(
    () => ({
      ...authUser,
      ...(user && typeof user === "object" ? user : {}),
      ...routeUser,
    }),
    [authUser, routeUser, user],
  );
  const resolvedToken = useMemo(
    () => normalizeText(pickFirst(token, routeState?.token, authState?.token)),
    [authState?.token, routeState?.token, token],
  );
  const resolvedUserId = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.userId,
          routeState?.user_id,
          resolvedUser?.id,
          resolvedUser?.user_id,
        ),
      ),
    [resolvedUser, routeState?.userId, routeState?.user_id],
  );
  const resolvedPetId = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.petId,
          routeState?.pet_id,
          routeState?.prescriptionPrefill?.petId,
          resolvedUser?.pet_id,
          resolvedUser?.pet?.id,
          resolvedUser?.pet?.pet_id,
        ),
      ),
    [
      resolvedUser,
      routeState?.petId,
      routeState?.pet_id,
      routeState?.prescriptionPrefill?.petId,
    ],
  );
  const [fallbackPet, setFallbackPet] = useState(null);
  const effectivePetId = useMemo(
    () =>
      normalizeText(
        pickFirst(
          resolvedPetId,
          fallbackPet?.id,
          fallbackPet?.pet_id,
        ),
      ),
    [fallbackPet, resolvedPetId],
  );
  const requestedClinicId = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.clinicId,
          routeState?.clinic_id,
          routeState?.prescriptionPrefill?.clinicId,
        ),
      ),
    [routeState?.clinicId, routeState?.clinic_id, routeState?.prescriptionPrefill?.clinicId],
  );
  const requestedDoctorId = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.doctorId,
          routeState?.doctor_id,
          routeState?.prescriptionPrefill?.doctorId,
        ),
      ),
    [routeState?.doctorId, routeState?.doctor_id, routeState?.prescriptionPrefill?.doctorId],
  );
  const chatRoomToken = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.chat_room_token,
          routeState?.chatRoomToken,
          authState?.chatRoomToken,
        ),
      ),
    [authState?.chatRoomToken, routeState?.chatRoomToken, routeState?.chat_room_token],
  );
  const contextToken = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.context_token,
          routeState?.contextToken,
          chatRoomToken,
        ),
      ),
    [chatRoomToken, routeState?.contextToken, routeState?.context_token],
  );
  const suggestedClinicFromRoute =
    routeState?.suggestedClinic && typeof routeState.suggestedClinic === "object"
      ? routeState.suggestedClinic
      : null;
  const symptomPrefillText = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.symptomText,
          routeState?.symptom_text,
          routeState?.prescriptionPrefill?.symptomText,
        ),
      ),
    [
      routeState?.prescriptionPrefill?.symptomText,
      routeState?.symptomText,
      routeState?.symptom_text,
    ],
  );

  const initialDates = useMemo(() => buildDateStrip(14), []);

  const [clinics, setClinics] = useState([]);
  const [loadingClinics, setLoadingClinics] = useState(true);
  const [clinicError, setClinicError] = useState("");
  const [locationUpdating, setLocationUpdating] = useState(false);

  const [selectedClinic, setSelectedClinic] = useState(null);
  const [doctors, setDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(null);

  const [selectedDate, setSelectedDate] = useState(initialDates[0]?.iso || "");
  const [slots, setSlots] = useState([]);
  const [slotsLoading, setSlotsLoading] = useState(false);
  const [selectedTime, setSelectedTime] = useState("");

  const [expandedStep, setExpandedStep] = useState(1);
  const [paymentPhase, setPaymentPhase] = useState(PAYMENT_PHASES.idle);
  const [localError, setLocalError] = useState("");
  const [statusMessage, setStatusMessage] = useState("");
  const [successState, setSuccessState] = useState(null);
  const [, setApiResponses] = useState({
    reportedSymptom: null,
    serviceBooking: null,
    createOrder: null,
    verifyPayment: null,
    appointmentSubmit: null,
    mediaUpload: null,
    recoveryCheck: null,
  });

  const [form, setForm] = useState({
    ownerName: normalizeText(
      pickFirst(
        resolvedUser?.pet_owner_name,
        resolvedUser?.owner_name,
        resolvedUser?.name,
      ),
    ),
    phone: normalizePhone(
      pickFirst(
        resolvedUser?.phone,
        resolvedUser?.mobile,
        resolvedUser?.mobileNumber,
      ),
    ),
    email: normalizeText(pickFirst(resolvedUser?.email)),
    petName: normalizeText(
      pickFirst(
        routeState?.petName,
        routeState?.pet_name,
        resolvedUser?.pet_name,
        resolvedUser?.pet?.name,
        resolvedUser?.pet?.pet_name,
      ),
    ),
    petType:
      normalizeText(
        pickFirst(
          routeState?.petType,
          routeState?.pet_type,
          resolvedUser?.pet_type,
          resolvedUser?.pet?.pet_type,
        ),
      ) || "dog",
    symptoms: symptomPrefillText,
    consentGiven: false,
  });
  const [mediaFiles, setMediaFiles] = useState([]);

  const payableAmount = IN_CLINIC_PRICING.totalAmount;
  const paymentBusy = paymentPhase !== PAYMENT_PHASES.idle;

  useEffect(() => {
    if (!resolvedUserId || effectivePetId) return;
    let active = true;

    async function fetchPets() {
      try {
        const response = await fetch(`${apiBase}/users/${encodeURIComponent(resolvedUserId)}/pets`, {
          method: "GET",
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
          }),
        });
        const data = await readApiBody(response);
        if (!response.ok) return;
        const pets = Array.isArray(data?.data)
          ? data.data
          : Array.isArray(data?.pets)
            ? data.pets
            : Array.isArray(data)
              ? data
              : [];
        const pet = pets[0] || null;
        if (!active || !pet) return;
        setFallbackPet(pet);
        setForm((current) => ({
          ...current,
          petName:
            current.petName ||
            normalizeText(pickFirst(pet?.name, pet?.pet_name)),
          petType:
            current.petType ||
            normalizeText(pickFirst(pet?.pet_type, pet?.species, pet?.type)) ||
            "dog",
        }));
      } catch (_) {
        // Validation will surface missing pet context before payment.
      }
    }

    fetchPets();
    return () => {
      active = false;
    };
  }, [apiBase, effectivePetId, resolvedToken, resolvedUserId]);

  useEffect(() => {
    let active = true;

    async function fetchClinics() {
      setLoadingClinics(true);
      setClinicError("");
      setStatusMessage("");

      try {
        const fetchClinicList = async (path) => {
          const clinicsBasePath = `${apiBase}${path}`;
          const querySeparator = clinicsBasePath.includes("?") ? "&" : "?";
          const clinicsUrl = resolvedUserId
            ? `${clinicsBasePath}${querySeparator}user_id=${encodeURIComponent(resolvedUserId)}`
            : clinicsBasePath;

          const response = await fetch(clinicsUrl, {
            method: "GET",
            headers: getHeaders(resolvedToken, {
              Accept: "application/json",
            }),
          });
          const data = await readApiBody(response);
          return { response, data };
        };

        let { response, data } = await fetchClinicList(effectiveEndpoints.clinics);
        if (!response.ok && effectiveEndpoints.clinicFallback) {
          ({ response, data } = await fetchClinicList(effectiveEndpoints.clinicFallback));
        }

        if (!response.ok) {
          throw new Error(data?.message || "Clinics load nahi hue.");
        }

        const normalized = flattenClinics(data);
        if (!active) return;
        setClinics(normalized);

        if (normalized.length) {
          const preferredClinic =
            normalized.find((item) => resolveClinicId(item) === requestedClinicId) ||
            normalized.find(
              (item) =>
                suggestedClinicFromRoute &&
                normalizeText(item?.name).toLowerCase() ===
                  normalizeText(suggestedClinicFromRoute?.name).toLowerCase(),
            ) ||
            normalized[0];
          setSelectedClinic(preferredClinic);
          setDoctors(flattenDoctorsFromClinic(preferredClinic));
        }
      } catch (error) {
        if (!active) return;
        setClinicError(error?.message || "Clinics load nahi hue.");
      } finally {
        if (active) setLoadingClinics(false);
      }
    }

    fetchClinics();

    return () => {
      active = false;
    };
  }, [
    apiBase,
    effectiveEndpoints.clinicFallback,
    effectiveEndpoints.clinics,
    requestedClinicId,
    resolvedToken,
    resolvedUserId,
    suggestedClinicFromRoute,
  ]);

  async function refreshClinics() {
    setLoadingClinics(true);
    setClinicError("");
    try {
      const clinicsBasePath = `${apiBase}${effectiveEndpoints.clinics}`;
      const querySeparator = clinicsBasePath.includes("?") ? "&" : "?";
      const clinicsUrl = resolvedUserId
        ? `${clinicsBasePath}${querySeparator}user_id=${encodeURIComponent(resolvedUserId)}`
        : clinicsBasePath;
      const response = await fetch(clinicsUrl, {
          method: "GET",
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
          }),
        });
      const data = await readApiBody(response);
      if (!response.ok) throw new Error(data?.message || "Clinics load nahi hue.");
      const normalized = flattenClinics(data);
      setClinics(normalized);
      if (normalized.length && !selectedClinic) setSelectedClinic(normalized[0]);
    } catch (error) {
      setClinicError(error?.message || "Clinics load nahi hue.");
    } finally {
      setLoadingClinics(false);
    }
  }

  async function handleUseCurrentLocation() {
    if (!resolvedUserId || !navigator.geolocation || locationUpdating) return;
    setLocationUpdating(true);
    setLocalError("");

    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 5 * 60 * 1000,
        });
      });

      const lat = Number(position.coords.latitude.toFixed(6));
      const lng = Number(position.coords.longitude.toFixed(6));
      const response = await fetch(`${apiBase}/users/location`, {
        method: "POST",
        headers: getHeaders(resolvedToken, {
          Accept: "application/json",
          "Content-Type": "application/json",
        }),
        body: JSON.stringify({
          user_id: resolvedUserId,
          location: "Current location",
          lat,
          lng,
        }),
      });
      const data = await readApiBody(response);
      if (!response.ok) throw new Error(data?.message || "Location update failed.");
      await refreshClinics();
      setStatusMessage("Current location updated.");
    } catch (error) {
      setLocalError(error?.message || "Location permission is off.");
    } finally {
      setLocationUpdating(false);
    }
  }

  useEffect(() => {
    if (!selectedClinic) return;

    async function fetchDoctorsIfNeeded() {
      if (!effectiveEndpoints.clinicDoctors) {
        const doctorList = flattenDoctorsFromClinic(selectedClinic);
        setDoctors(doctorList);
        if (requestedDoctorId) {
          const matchedDoctor = doctorList.find(
            (item) => resolveDoctorId(item) === requestedDoctorId,
          );
          if (matchedDoctor) {
            setSelectedDoctor(matchedDoctor);
            setExpandedStep(3);
          }
        }
        return;
      }

      try {
        const clinicId = resolveClinicId(selectedClinic);
        const url = `${apiBase}${effectiveEndpoints.clinicDoctors.replace(":clinicId", clinicId)}`;
        const response = await fetch(url, {
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
          }),
        });
        const data = await readApiBody(response);
        if (!response.ok) {
          throw new Error(data?.message || "Doctors load nahi hue.");
        }

        const list = Array.isArray(data?.data?.doctors)
          ? data.data.doctors
          : Array.isArray(data?.doctors)
            ? data.doctors
            : Array.isArray(data?.data)
              ? data.data
              : [];

        const normalized = list
          .map((doctor) => normalizeDoctorRecord(doctor, selectedClinic))
          .filter(Boolean);
        setDoctors(normalized);
        if (requestedDoctorId) {
          const matchedDoctor = normalized.find(
            (item) => resolveDoctorId(item) === requestedDoctorId,
          );
          if (matchedDoctor) {
            setSelectedDoctor(matchedDoctor);
            setExpandedStep(3);
          }
        }
      } catch (error) {
        setLocalError(error?.message || "Doctors load nahi hue.");
      }
    }

    setSelectedDoctor(null);
    setSelectedTime("");
    setSlots([]);
    fetchDoctorsIfNeeded();
  }, [apiBase, effectiveEndpoints.clinicDoctors, requestedDoctorId, resolvedToken, selectedClinic]);

  useEffect(() => {
    if (!selectedClinic || !selectedDate) return;
    const clinicId = resolveClinicId(selectedClinic);
    if (!clinicId) return;

    let active = true;

    async function fetchSlots() {
      setSlotsLoading(true);
      setSelectedTime("");
      try {
        const url = `${apiBase}${buildAvailabilityUrl(effectiveEndpoints.clinicAvailability, clinicId, selectedDate)}`;
        const response = await fetch(url, {
          method: "GET",
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
          }),
        });
        const data = await readApiBody(response);

        if (!response.ok) {
          throw new Error(data?.message || "Slots load nahi hue.");
        }

        const availabilityDoctors = normalizeDoctorsFromAvailability(data, selectedClinic);
        if (availabilityDoctors.length) {
          setDoctors(availabilityDoctors);
          setSelectedDoctor((current) =>
            current && availabilityDoctors.some((doctor) => doctor.id === current.id)
              ? current
              : availabilityDoctors[0],
          );
        }

        const normalizedSlots = normalizeSlots(data);
        if (!active) return;
        setSlots(normalizedSlots.length ? normalizedSlots : buildStaticSlots(selectedDate));
      } catch (error) {
        if (!active) return;
        setLocalError(error?.message || "Slots load nahi hue.");
        setSlots(buildStaticSlots(selectedDate));
      } finally {
        if (active) setSlotsLoading(false);
      }
    }

    fetchSlots();

    return () => {
      active = false;
    };
  }, [apiBase, effectiveEndpoints.clinicAvailability, resolvedToken, selectedClinic, selectedDate]);

  useEffect(() => {
    if (!selectedDoctor || !selectedDate || !effectiveEndpoints.doctorSlotsSummary) return;
    const doctorId = resolveDoctorId(selectedDoctor);
    if (!doctorId) return;

    let active = true;

    async function fetchDoctorSummary() {
      try {
        const url = `${apiBase}${buildDoctorSlotsUrl(effectiveEndpoints.doctorSlotsSummary, doctorId, selectedDate)}`;
        const response = await fetch(url, {
          method: "GET",
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
          }),
        });
        const data = await readApiBody(response);
        if (!response.ok) return;
        const summarySlots = normalizeSlots(data);
        if (active && summarySlots.length) setSlots(summarySlots);
      } catch (_) {
        // Clinic availability remains the source of truth if summary is unavailable.
      }
    }

    fetchDoctorSummary();
    return () => {
      active = false;
    };
  }, [apiBase, effectiveEndpoints.doctorSlotsSummary, resolvedToken, selectedDate, selectedDoctor]);

  const clinicSummary = useMemo(() => {
    if (!selectedClinic) return "No clinic selected";
    return selectedClinic.name;
  }, [selectedClinic]);

  const doctorSummary = useMemo(() => {
    if (!selectedDoctor) return "No doctor selected";
    return `${selectedDoctor.name} • ${formatCurrency(IN_CLINIC_PRICING.totalAmount)} payable`;
  }, [selectedDoctor]);

  const slotSummary = useMemo(() => {
    if (!selectedDate || !selectedTime) return "Date and slot pending";
    return `${formatDateLabel(selectedDate)} • ${selectedTime}`;
  }, [selectedDate, selectedTime]);

  const detailsSummary = useMemo(() => {
    const symptomText = normalizeText(form.symptoms);
    if (!symptomText) return "Symptoms pending";
    return `${form.petName || "Pet"} • ${symptomText.slice(0, 42)}${symptomText.length > 42 ? "..." : ""}`;
  }, [form.petName, form.symptoms]);

  function updateForm(key, value) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  function handleClinicSelect(clinic) {
    setSelectedClinic(clinic);
    setExpandedStep(2);
    setLocalError("");
    setStatusMessage("");
  }

  function handleDoctorSelect(doctor) {
    setSelectedDoctor(doctor);
    setExpandedStep(3);
    setLocalError("");
    setStatusMessage("");
  }

  function handleMediaChange(event) {
    const files = Array.from(event.target.files || []);
    setMediaFiles((current) => [...current, ...files]);
  }

  function removeFile(targetFile) {
    setMediaFiles((current) => current.filter((file) => file !== targetFile));
  }

  function recordApiResponse(key, payload) {
    setApiResponses((current) => ({
      ...current,
      [key]: payload,
    }));
  }

  function validateBeforePayment() {
    if (!resolvedUserId || !effectivePetId) {
      return "User/Pet context missing hai. Ask flow se dubara open kijiye.";
    }
    if (!selectedClinic) return "Please select a clinic.";
    if (!selectedDoctor) return "Please select a doctor.";
    if (!selectedDate || !selectedTime) return "Please select date and slot.";
    if (!normalizeText(form.symptoms)) return "Please describe your pet symptoms.";
    if (!form.consentGiven) return "Please acknowledge the consent checkbox before payment.";
    return "";
  }

  async function openRazorpay({ key, orderId, amountInPaise }) {
    const loaded = await loadRazorpayScript();
    if (!loaded) {
      throw new Error("Razorpay SDK load nahi hua.");
    }

    return new Promise((resolve, reject) => {
      const instance = new window.Razorpay({
        key,
        amount: String(amountInPaise),
        currency: "INR",
        order_id: orderId,
        name: "Snoutiq",
        description: `In-clinic booking with ${selectedDoctor?.name || "Doctor"}`,
        prefill: {
          name: form.ownerName,
          email: form.email,
          contact: form.phone,
        },
        theme: { color: "#2563eb" },
        modal: {
          ondismiss: () => reject(new Error("Payment cancelled by user.")),
        },
        handler: (response) => resolve(response),
      });
      instance.open();
    });
  }

  async function handlePayNow() {
    if (paymentInFlightRef.current) return;

    const validationError = validateBeforePayment();
    if (validationError) {
      setLocalError(validationError);
      void showBookingError(validationError, "Check booking details");
      setExpandedStep(4);
      return;
    }

    const confirmation = await confirmPaymentStart({
      amount: payableAmount,
      title: "Continue to payment",
      text: `Pay ${formatCurrency(payableAmount)} for clinic visit.`,
    });
    if (!confirmation.isConfirmed) return;

    paymentInFlightRef.current = true;
    setLocalError("");
    setStatusMessage("");
    setApiResponses({
      reportedSymptom: null,
      serviceBooking: null,
      createOrder: null,
      verifyPayment: null,
      appointmentSubmit: null,
      mediaUpload: null,
      recoveryCheck: null,
    });
    setPaymentPhase(PAYMENT_PHASES.submitting);

    let paidRazorpayResponse = null;
    let paidOrderId = "";

    try {
      const userIdNumber = toInteger(resolvedUserId, 0);
      const petIdNumber = toInteger(effectivePetId, 0);
      const symptomText = normalizeText(form.symptoms);

      const symptomRes = await fetch(
        `${apiBase}/users/${encodeURIComponent(resolvedUserId)}/pets/${encodeURIComponent(effectivePetId)}/reported-symptom`,
        {
          method: "PUT",
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
            "Content-Type": "application/json",
          }),
          body: JSON.stringify({
            reported_symptom: symptomText,
          }),
        },
      );
      const symptomData = await readApiBody(symptomRes);
      recordApiResponse("reportedSymptom", symptomData);
      if (
        !symptomRes.ok ||
        (symptomData &&
          typeof symptomData === "object" &&
          symptomData.success === false)
      ) {
        setStatusMessage(
          normalizeText(
            symptomData?.message ||
              symptomData?.error ||
              "Reported symptom update failed.",
          ) || "Reported symptom update failed.",
        );
      } else {
        setStatusMessage("Reported symptom updated successfully.");
      }

      const serviceBookingPayload = stripEmpty({
        user_id: userIdNumber || resolvedUserId,
        pet_id: petIdNumber || effectivePetId,
        clinic_name: normalizeText(
          pickFirst(
            selectedClinic?.name,
            suggestedClinicFromRoute?.name,
            selectedDoctor?.clinicName,
          ),
        ),
        clinic_mobile: normalizePhone(
          pickFirst(
            suggestedClinicFromRoute?.phone,
            selectedClinic?.raw?.clinic_mobile,
            selectedClinic?.raw?.mobile,
            selectedDoctor?.raw?.clinic_mobile,
            selectedDoctor?.mobile,
          ),
        ),
        clinic_email: normalizeText(
          pickFirst(
            selectedClinic?.raw?.clinic_email,
            selectedClinic?.raw?.email,
            suggestedClinicFromRoute?.email,
          ),
        ),
        clinic_city: normalizeText(
          pickFirst(
            selectedClinic?.raw?.clinic_city,
            selectedClinic?.raw?.city,
            suggestedClinicFromRoute?.city,
          ),
        ),
        clinic_pincode: normalizeText(
          pickFirst(
            selectedClinic?.raw?.clinic_pincode,
            selectedClinic?.raw?.pincode,
            suggestedClinicFromRoute?.pincode,
          ),
        ),
        clinic_address: normalizeText(
          pickFirst(
            selectedClinic?.raw?.clinic_address,
            selectedClinic?.address,
            selectedClinic?.raw?.address,
            suggestedClinicFromRoute?.address,
          ),
        ),
        clinic_lat: pickFirst(
          selectedClinic?.raw?.lat,
          selectedClinic?.raw?.latitude,
          suggestedClinicFromRoute?.latitude,
        ),
        clinic_lng: pickFirst(
          selectedClinic?.raw?.lng,
          selectedClinic?.raw?.longitude,
          suggestedClinicFromRoute?.longitude,
        ),
        clinic_place_id: normalizeText(
          pickFirst(
            selectedClinic?.raw?.place_id,
            suggestedClinicFromRoute?.place_id,
          ),
        ),
        doctor_name: normalizeText(
          pickFirst(
            selectedDoctor?.name,
            selectedDoctor?.raw?.doctor_name,
          ),
        ),
        doctor_mobile: normalizePhone(
          pickFirst(
            selectedDoctor?.mobile,
            selectedDoctor?.raw?.doctor_mobile,
            selectedDoctor?.raw?.mobile,
          ),
        ),
        doctor_email: normalizeText(
          pickFirst(
            selectedDoctor?.raw?.doctor_email,
            selectedDoctor?.raw?.email,
          ),
        ),
        doctor_license: normalizeText(
          pickFirst(
            selectedDoctor?.raw?.doctor_license,
            selectedDoctor?.raw?.license_number,
            selectedDoctor?.raw?.license,
          ),
        ),
        doctor_price: toInteger(
          pickFirst(
            selectedDoctor?.price,
            selectedDoctor?.raw?.doctors_price,
            selectedDoctor?.raw?.price,
            IN_CLINIC_PRICING.discountedAmount,
          ),
          IN_CLINIC_PRICING.discountedAmount,
        ),
        chat_room_token: chatRoomToken || undefined,
        context_token: contextToken || undefined,
        service_type: "in_clinic",
        appointment_date: selectedDate || toDateOnly(new Date()),
        appointment_time: parseTimeForApi(selectedTime),
        notes: symptomText,
      });

      let serviceRes = await fetch(
        `${apiBase}${effectiveEndpoints.serviceBookings || DEFAULT_ENDPOINTS.serviceBookings}`,
        {
          method: "POST",
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
            "Content-Type": "application/json",
          }),
          body: JSON.stringify(serviceBookingPayload),
        },
      );
      let serviceData = await readApiBody(serviceRes);

      const duplicatePlaceIdError =
        normalizeText(serviceData?.message).toLowerCase().includes("duplicate entry") &&
        normalizeText(serviceData?.message).toLowerCase().includes("place_id");

      if ((!serviceRes.ok || serviceData?.success === false) && duplicatePlaceIdError) {
        const retryPayload = { ...serviceBookingPayload };
        delete retryPayload.clinic_place_id;
        const retryRes = await fetch(
          `${apiBase}${effectiveEndpoints.serviceBookings || DEFAULT_ENDPOINTS.serviceBookings}`,
          {
            method: "POST",
            headers: getHeaders(resolvedToken, {
              Accept: "application/json",
              "Content-Type": "application/json",
            }),
            body: JSON.stringify(retryPayload),
          },
        );
        const retryData = await readApiBody(retryRes);
        recordApiResponse("serviceBooking", {
          firstAttempt: serviceData,
          retryAttempt: retryData,
        });
        serviceRes = retryRes;
        serviceData = retryData;
      } else {
        recordApiResponse("serviceBooking", serviceData);
      }

      if (
        !serviceRes.ok ||
        (serviceData &&
          typeof serviceData === "object" &&
          serviceData.success === false)
      ) {
        throw new Error(
          serviceData?.message ||
            serviceData?.error ||
            "Service booking failed. Payment cannot continue.",
        );
      }

      const createdClinicId = parsePositiveId(
        serviceData?.data?.clinic_id,
        serviceData?.clinic_id,
        serviceData?.booking?.clinic_id,
        serviceData?.data?.booking?.clinic_id,
      );
      const createdDoctorId = parsePositiveId(
        serviceData?.data?.doctor_id,
        serviceData?.doctor_id,
        serviceData?.booking?.doctor_id,
        serviceData?.data?.booking?.doctor_id,
      );

      if (!createdClinicId || !createdDoctorId) {
        throw new Error("Service booking response me clinic_id/doctor_id missing hai.");
      }

      setStatusMessage("Service booking created. Opening payment...");

      const createPayload = stripEmpty({
        amount: IN_CLINIC_PRICING.totalAmount,
        order_type: "appointment",
        user_id: userIdNumber || resolvedUserId,
        doctor_id: createdDoctorId,
        clinic_id: createdClinicId,
        pet_id: petIdNumber || effectivePetId,
      });

      const createRes = await fetch(`${apiBase}${effectiveEndpoints.createOrder}`, {
        method: "POST",
        headers: getHeaders(resolvedToken, {
          Accept: "application/json",
          "Content-Type": "application/json",
        }),
        body: JSON.stringify(createPayload),
      });
      const createData = await readApiBody(createRes);
      recordApiResponse("createOrder", createData);

      if (!createRes.ok) {
        throw new Error(createData?.message || createData?.error || "Order create nahi hua.");
      }

      const order = createData?.order || createData?.data?.order || createData?.data || {};
      const razorpayKey = normalizeText(createData?.key || createData?.data?.key || "");
      const orderId = normalizeText(order?.id || order?.order_id || createData?.order_id || createData?.data?.order_id);
      const amountInPaise = Number(order?.amount || IN_CLINIC_PRICING.totalAmount * 100);

      if (!razorpayKey) throw new Error("Razorpay key missing hai.");
      if (!orderId) throw new Error("Order ID missing hai.");

      setPaymentPhase(PAYMENT_PHASES.success_processing);
      const razorpayResponse = await openRazorpay({
        key: razorpayKey,
        orderId,
        amountInPaise,
      });
      paidRazorpayResponse = razorpayResponse;
      paidOrderId = orderId;

      setPaymentPhase(PAYMENT_PHASES.redirecting);

      const verifyPayload = {
        razorpay_order_id: razorpayResponse?.razorpay_order_id || orderId,
        razorpay_payment_id: razorpayResponse?.razorpay_payment_id,
        razorpay_signature: razorpayResponse?.razorpay_signature,
        order_type: "appointment",
        user_id: userIdNumber || resolvedUserId,
        pet_id: petIdNumber || effectivePetId,
        clinic_id: createdClinicId,
        doctor_id: createdDoctorId,
        appointment_date: selectedDate,
        appointment_time: parseTimeForApi(selectedTime),
      };
      const finalVerifyPayload = stripEmpty(verifyPayload);

      const verifyRes = await fetch(`${apiBase}${effectiveEndpoints.verifyPayment}`, {
        method: "POST",
        headers: getHeaders(resolvedToken, {
          Accept: "application/json",
          "Content-Type": "application/json",
        }),
        body: JSON.stringify(finalVerifyPayload),
      });
      const verifyData = await readApiBody(verifyRes);
      recordApiResponse("verifyPayment", verifyData);

      if (!verifyRes.ok || !verifyData?.success) {
        throw new Error(verifyData?.message || verifyData?.error || "Payment verify nahi hua.");
      }

      const appointmentPayload = stripEmpty({
        user_id: userIdNumber || resolvedUserId,
        pet_id: petIdNumber || effectivePetId,
        clinic_id: createdClinicId,
        doctor_id: createdDoctorId,
        service_type: "in_clinic",
        patient_name: normalizeText(form.ownerName),
        patient_phone: normalizePhone(form.phone).replace(/\D/g, "").slice(-10),
        patient_email: normalizeText(form.email),
        pet_name: normalizeText(form.petName),
        pet_type: normalizeText(form.petType),
        date: selectedDate,
        appointment_date: selectedDate,
        time_slot: parseTimeForApi(selectedTime),
        appointment_time: parseTimeForApi(selectedTime),
        amount: IN_CLINIC_PRICING.totalAmount,
        currency: IN_CLINIC_PRICING.currency || "INR",
        order_type: "appointment",
        payment_status: "paid",
        razorpay_order_id: razorpayResponse?.razorpay_order_id || orderId,
        razorpay_payment_id: razorpayResponse?.razorpay_payment_id,
        razorpay_signature: razorpayResponse?.razorpay_signature,
        payment_id: razorpayResponse?.razorpay_payment_id,
        notes: symptomText,
        symptoms: symptomText,
        pricing_original_amount: IN_CLINIC_PRICING.originalAmount,
        pricing_discount_amount: IN_CLINIC_PRICING.discountAmount,
        pricing_discounted_amount: IN_CLINIC_PRICING.discountedAmount,
        pricing_gst_amount: IN_CLINIC_PRICING.gstAmount,
        pricing_total_amount: IN_CLINIC_PRICING.totalAmount,
        payment_verified: true,
      });

      let appointmentData = null;
      try {
        const appointmentRes = await fetch(`${apiBase}${effectiveEndpoints.submitAppointment}`, {
          method: "POST",
          headers: getHeaders(resolvedToken, {
            Accept: "application/json",
            "Content-Type": "application/json",
          }),
          body: JSON.stringify(appointmentPayload),
        });
        appointmentData = await readApiBody(appointmentRes);
        recordApiResponse("appointmentSubmit", appointmentData);

        if (
          !appointmentRes.ok ||
          (appointmentData &&
            typeof appointmentData === "object" &&
            appointmentData.success === false &&
            appointmentData.status !== "success")
        ) {
          throw new Error(
            appointmentData?.message ||
              appointmentData?.error ||
              "Appointment submit nahi hua.",
          );
        }
      } catch (appointmentError) {
        const recoveryPayload = stripEmpty({
          payment_id: razorpayResponse?.razorpay_payment_id,
          order_id: razorpayResponse?.razorpay_order_id || orderId,
          user_id: userIdNumber || resolvedUserId,
          pet_id: petIdNumber || effectivePetId,
          clinic_id: createdClinicId,
          doctor_id: createdDoctorId,
          appointment_date: selectedDate,
          appointment_time: parseTimeForApi(selectedTime),
          amount: IN_CLINIC_PRICING.totalAmount,
          timestamp: new Date().toISOString(),
        });

        try {
          window.localStorage.setItem(
            "snoutiq_pending_inclinic_appointment_recovery",
            JSON.stringify(recoveryPayload),
          );
        } catch (_) {
          // Recovery API still runs if storage is unavailable.
        }

        try {
          const recoveryRes = await fetch(`${apiBase}${effectiveEndpoints.checkByPayment}`, {
            method: "POST",
            headers: getHeaders(resolvedToken, {
              Accept: "application/json",
              "Content-Type": "application/json",
            }),
            body: JSON.stringify(recoveryPayload),
          });
          const recoveryData = await readApiBody(recoveryRes);
          recordApiResponse("recoveryCheck", recoveryData);
        } catch (_) {
          // User-facing error below includes payment ID for manual recovery.
        }

        throw new Error(
          `Payment successful, appointment verification pending. Payment ID: ${razorpayResponse?.razorpay_payment_id || "N/A"}. We are verifying your appointment.`,
        );
      }

      let mediaUploadData = null;
      if (mediaFiles.length) {
        try {
          const mediaForm = new FormData();
          mediaForm.append("user_id", String(userIdNumber || resolvedUserId));
          mediaForm.append("pet_id", String(petIdNumber || effectivePetId));
          mediaForm.append("question", symptomText);
          mediaForm.append("service_type", "in_clinic");
          mediaForm.append("appointment_date", selectedDate);
          mediaForm.append("appointment_time", parseTimeForApi(selectedTime));
          mediaForm.append("file", mediaFiles[0]);
          const mediaRes = await fetch(`${apiBase}${effectiveEndpoints.mediaQuestion}`, {
            method: "POST",
            headers: getHeaders(resolvedToken, {
              Accept: "application/json",
            }),
            body: mediaForm,
          });
          mediaUploadData = await readApiBody(mediaRes);
          recordApiResponse("mediaUpload", mediaUploadData);
          if (!mediaRes.ok) {
            setStatusMessage("Appointment booked. Media upload will be synced later.");
          }
        } catch (_) {
          setStatusMessage("Appointment booked. Media upload will be synced later.");
        }
      }

      const successPayload = {
        bookingType: "in_clinic",
        bookingId:
          appointmentData?.data?.appointment?.id ||
          appointmentData?.data?.appointment_id ||
          appointmentData?.appointment_id ||
          appointmentData?.id ||
          verifyData?.appointment?.id ||
          "N/A",
        clinicName: selectedClinic.name,
        doctorName: selectedDoctor.name,
        appointmentDate: selectedDate,
        appointmentTime: selectedTime,
        paymentId: razorpayResponse?.razorpay_payment_id,
        amount: payableAmount,
        clinicId: createdClinicId,
        doctorId: createdDoctorId,
        petId: effectivePetId,
        petName: form.petName,
        date: selectedDate,
        time: selectedTime,
        responses: {
          reportedSymptom: symptomData,
          serviceBooking: serviceData,
          createOrder: createData,
          verifyPayment: verifyData,
          appointmentSubmit: appointmentData,
          mediaUpload: mediaUploadData,
        },
      };

      setSuccessState(successPayload);
      setPaymentPhase(PAYMENT_PHASES.idle);

      try {
        window.localStorage.setItem("snoutiq.lastBookingSuccess", JSON.stringify(successPayload));
      } catch (_) {
        // Success navigation does not depend on storage.
      }
      try {
        await fetchPetOverview(effectivePetId, { forceRefresh: true });
      } catch (_) {
        // Timeline refresh is best effort.
      }

      if (typeof onSuccess === "function") {
        onSuccess(successPayload);
      }
      navigate("/appointment-thank-you", { replace: true, state: successPayload });
    } catch (error) {
      setPaymentPhase(PAYMENT_PHASES.idle);
      const message = error?.message || "Payment failed. Please try again.";
      setLocalError(message);
      if (paidRazorpayResponse?.razorpay_payment_id) {
        try {
          window.localStorage.setItem(
            "snoutiq.pendingInClinicPayment",
            JSON.stringify({
              user_id: resolvedUserId,
              pet_id: effectivePetId,
              order_id: paidRazorpayResponse?.razorpay_order_id || paidOrderId,
              payment_id: paidRazorpayResponse?.razorpay_payment_id,
              amount: IN_CLINIC_PRICING.totalAmount,
              timestamp: new Date().toISOString(),
            }),
          );
        } catch (_) {
          // Alert still includes payment id.
        }
        void showBookingWarning(message);
      } else {
        void showBookingError(message);
      }
    } finally {
      paymentInFlightRef.current = false;
    }
  }

  return (
    <div className="min-h-screen bg-slate-50 pb-28 text-slate-900 lg:pb-0">
      <div className="mx-auto max-w-5xl px-3 py-4 sm:px-5 lg:px-6">
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div className="border-b border-slate-200 bg-white px-4 py-4 sm:px-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h1 className="text-xl font-bold sm:text-2xl">Book Clinic Visit</h1>
                <p className="mt-1 text-sm text-slate-500">
                  Snoutiq AI recommends an in-clinic visit for this case.
                </p>
              </div>
              <div className="hidden">
                {form.petName || "Selected pet"}
              </div>
            </div>
          </div>

          <div className="grid gap-4 px-3 py-4 lg:grid-cols-[1.3fr_0.7fr] sm:px-5 lg:px-6">
            <div className="space-y-4">
              <StepCard
                step={1}
                title="Select clinic"
                subtitle=""
                open={expandedStep === 1}
                onEdit={() => setExpandedStep(1)}
                summary={clinicSummary}
              >
                <div className="hidden">
                  <button
                    type="button"
                    onClick={handleUseCurrentLocation}
                    disabled={locationUpdating || !resolvedUserId}
                    className="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                  >
                    {locationUpdating ? "Updating..." : "Use current location"}
                  </button>
                </div>
                {loadingClinics ? (
                  <div className="grid gap-3 md:grid-cols-2">
                    {Array.from({ length: 4 }).map((_, index) => (
                      <div key={index} className="animate-pulse rounded-[24px] border border-slate-200 p-4">
                        <div className="h-4 w-28 rounded bg-slate-200" />
                        <div className="mt-3 h-3 w-40 rounded bg-slate-100" />
                        <div className="mt-2 h-3 w-24 rounded bg-slate-100" />
                        <div className="mt-4 h-10 rounded-2xl bg-slate-200" />
                      </div>
                    ))}
                  </div>
                ) : clinicError ? (
                  <div className="rounded-[24px] border border-red-200 bg-red-50 p-4 text-sm text-red-700">{clinicError}</div>
                ) : (
                  <div className="grid gap-3 md:grid-cols-2">
                    {clinics.map((clinic) => {
                      const active = selectedClinic?.id === clinic.id;
                      return (
                        <button
                          key={clinic.id}
                          type="button"
                          onClick={() => handleClinicSelect(clinic)}
                          className={`rounded-[24px] border p-4 text-left transition ${
                            active
                              ? "border-emerald-500 bg-emerald-50 shadow-sm"
                              : "border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm"
                          }`}
                        >
                          <div className="flex items-start justify-between gap-3">
                            <div>
                              <div className="text-base font-semibold text-slate-900">{clinic.name}</div>
                            </div>
                          </div>
                          <div className="mt-4 text-xs font-semibold text-emerald-700">Select clinic</div>
                        </button>
                      );
                    })}
                  </div>
                )}
              </StepCard>

              <StepCard
                step={2}
                title="Choose doctor"
                subtitle=""
                open={expandedStep === 2}
                onEdit={() => setExpandedStep(2)}
                summary={doctorSummary}
              >
                {!selectedClinic ? (
                  <div className="rounded-[24px] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Pehle clinic choose karo.
                  </div>
                ) : doctors.length === 0 ? (
                  <div className="rounded-[24px] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Is clinic ke doctors available nahi mile.
                  </div>
                ) : (
                  <div className="grid gap-3 md:grid-cols-2">
                    {doctors.map((doctor) => {
                      const active = selectedDoctor?.id === doctor.id;
                      return (
                        <button
                          key={doctor.id}
                          type="button"
                          onClick={() => handleDoctorSelect(doctor)}
                          className={`rounded-[24px] border p-4 text-left transition ${
                            active
                              ? "border-blue-500 bg-blue-50 shadow-sm"
                              : "border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm"
                          }`}
                        >
                          <div className="flex items-start gap-3">
                            {doctor.image ? (
                              <img src={doctor.image} alt={doctor.name} className="h-14 w-14 rounded-2xl object-cover" />
                            ) : (
                              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 font-bold text-blue-700">
                                {doctor.name.charAt(0).toUpperCase()}
                              </div>
                            )}
                            <div className="min-w-0 flex-1">
                              <div className="text-base font-semibold text-slate-900">{doctor.name}</div>
                              <div className="mt-1 flex flex-wrap gap-2">
                                {doctor.specializations[0] ? (
                                  <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                                    {doctor.specializations[0]}
                                  </span>
                                ) : null}
                                {doctor.experience > 0 ? (
                                  <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                                    {doctor.experience}+ yrs
                                  </span>
                                ) : null}
                                {doctor.rating > 0 ? (
                                  <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                                    {doctor.rating.toFixed(1)} ★
                                  </span>
                                ) : null}
                              </div>
                              <div className="mt-3 text-sm font-semibold text-slate-900">{formatCurrency(doctor.price)}</div>
                            </div>
                          </div>
                        </button>
                      );
                    })}
                  </div>
                )}
              </StepCard>

              <StepCard
                step={3}
                title="Select date & slot"
                subtitle=""
                open={expandedStep === 3}
                onEdit={() => setExpandedStep(3)}
                summary={slotSummary}
              >
                {!selectedDoctor ? (
                  <div className="rounded-[24px] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Pehle doctor choose karo.
                  </div>
                ) : (
                  <div>
                    <div className="overflow-x-auto pb-2">
                      <div className="flex min-w-max gap-3">
                        {initialDates.map((item) => {
                          const active = selectedDate === item.iso;
                          return (
                            <button
                              key={item.iso}
                              type="button"
                              onClick={() => {
                                setSelectedDate(item.iso);
                                setSelectedTime("");
                                setExpandedStep(3);
                              }}
                              className={`rounded-[22px] border px-4 py-3 text-sm font-semibold transition ${
                                active
                                  ? "border-blue-600 bg-blue-600 text-white"
                                  : "border-slate-200 bg-white text-slate-700 hover:border-slate-300"
                              }`}
                            >
                              {item.label}
                            </button>
                          );
                        })}
                      </div>
                    </div>

                    <div className="mt-4">
                      {slotsLoading ? (
                        <div className="rounded-[24px] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Loading slots...</div>
                      ) : slots.length === 0 ? (
                        <div className="rounded-[24px] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                          Is date ke liye slots available nahi mile.
                        </div>
                      ) : (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                          {slots.map((slot) => {
                            const active = selectedTime === slot.value;
                            return (
                              <button
                                key={slot.id}
                                type="button"
                                disabled={!slot.available}
                                onClick={() => {
                                  setSelectedTime(slot.value);
                                  setExpandedStep(4);
                                }}
                                className={`rounded-2xl border px-4 py-3 text-sm font-semibold transition ${
                                  !slot.available
                                    ? "cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400"
                                    : active
                                      ? "border-emerald-600 bg-emerald-600 text-white"
                                      : "border-slate-200 bg-white text-slate-700 hover:border-slate-300"
                                }`}
                              >
                                {slot.label}
                              </button>
                            );
                          })}
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </StepCard>

              <StepCard
                step={4}
                title="Describe symptoms"
                subtitle=""
                open={expandedStep === 4}
                onEdit={() => setExpandedStep(4)}
                summary={detailsSummary}
              >
                <div className="grid gap-4">
                  <div className="hidden">
                    <label className="block">
                      <span className="mb-2 block text-sm font-medium text-slate-700">Owner name</span>
                      <input
                        className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                        value={form.ownerName}
                        onChange={(e) => updateForm("ownerName", e.target.value)}
                        placeholder="Enter owner name"
                      />
                    </label>
                    <label className="block">
                      <span className="mb-2 block text-sm font-medium text-slate-700">Phone</span>
                      <input
                        className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                        value={form.phone}
                        onChange={(e) => updateForm("phone", e.target.value)}
                        placeholder="Enter phone number"
                      />
                    </label>
                  </div>

                  <div className="hidden">
                    <label className="block">
                      <span className="mb-2 block text-sm font-medium text-slate-700">Pet name</span>
                      <input
                        className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                        value={form.petName}
                        onChange={(e) => updateForm("petName", e.target.value)}
                        placeholder="Enter pet name"
                      />
                    </label>
                    <label className="block">
                      <span className="mb-2 block text-sm font-medium text-slate-700">Pet type</span>
                      <select
                        className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                        value={form.petType}
                        onChange={(e) => updateForm("petType", e.target.value)}
                      >
                        <option value="dog">Dog</option>
                        <option value="cat">Cat</option>
                        <option value="other">Other</option>
                      </select>
                    </label>
                  </div>

                  <label className="block">
                    <span className="mb-2 block text-sm font-medium text-slate-700">Describe symptoms</span>
                    <textarea
                      rows={5}
                      className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                      value={form.symptoms}
                      onChange={(e) => updateForm("symptoms", e.target.value)}
                      placeholder="Pet ko kya problem hai, clearly likho"
                    />
                  </label>

                  <label className="block">
                    <span className="mb-2 block text-sm font-medium text-slate-700">Upload images / reports</span>
                    <input
                      type="file"
                      multiple
                      accept="image/*,.pdf,.doc,.docx"
                      onChange={handleMediaChange}
                      className="block w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                    />
                    <UploadPreview files={mediaFiles} onRemove={removeFile} />
                  </label>

                  <label className="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <input
                      type="checkbox"
                      checked={form.consentGiven}
                      onChange={(e) => updateForm("consentGiven", e.target.checked)}
                      className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600"
                    />
                    <span className="text-sm text-slate-700">
                      I acknowledge and agree to proceed with this appointment booking.
                    </span>
                  </label>
                </div>
              </StepCard>

              {localError ? (
                <div className="rounded-[24px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{localError}</div>
              ) : null}

              {statusMessage ? (
                <div className="rounded-[24px] border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                  {statusMessage}
                </div>
              ) : null}

              {successState ? (
                <div className="rounded-[28px] border border-emerald-200 bg-emerald-50 p-5">
                  <div className="text-sm font-semibold uppercase tracking-wide text-emerald-700">Appointment booked successfully</div>
                  <h2 className="mt-2 text-xl font-bold text-slate-900">Appointment booked successfully</h2>
                  <div className="mt-4 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                    <div><span className="font-semibold">Booking ID:</span> {successState.bookingId}</div>
                    <div><span className="font-semibold">Payment ID:</span> {successState.paymentId}</div>
                    <div><span className="font-semibold">Clinic:</span> {successState.clinicName}</div>
                    <div><span className="font-semibold">Doctor:</span> {successState.doctorName}</div>
                    <div><span className="font-semibold">Date:</span> {formatDateLabel(successState.appointmentDate)}</div>
                    <div><span className="font-semibold">Time:</span> {successState.appointmentTime}</div>
                  </div>
                </div>
              ) : null}
            </div>

            <aside className="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white p-3 shadow-[0_-12px_30px_rgba(15,23,42,0.12)] lg:sticky lg:inset-auto lg:top-6 lg:h-fit lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
              <div className="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                <div className="text-sm font-semibold uppercase tracking-wide text-slate-500">Booking summary</div>
                <div className="mt-4 rounded-[24px] bg-slate-900 p-4 text-white">
                  <div className="text-sm text-slate-300">Payable amount</div>
                  <div className="mt-1 text-3xl font-bold">{formatCurrency(payableAmount)}</div>
                  <div className="mt-2 text-sm text-slate-300">In-clinic consultation</div>
                </div>

                <div className="mt-4 divide-y divide-slate-100 text-sm">
                  <div className="flex items-center justify-between py-3"><span className="text-slate-500">Clinic</span><span className="max-w-[55%] truncate font-medium text-slate-900">{selectedClinic?.name || "Select clinic"}</span></div>
                  <div className="flex items-center justify-between py-3"><span className="text-slate-500">Doctor</span><span className="max-w-[55%] truncate font-medium text-slate-900">{selectedDoctor?.name || "Select doctor"}</span></div>
                  <div className="flex items-center justify-between py-3"><span className="text-slate-500">Date</span><span className="font-medium text-slate-900">{selectedDate ? formatDateLabel(selectedDate) : "Select date"}</span></div>
                  <div className="flex items-center justify-between py-3"><span className="text-slate-500">Slot</span><span className="font-medium text-slate-900">{selectedTime || "Select slot"}</span></div>
                  <div className="flex items-center justify-between py-3"><span className="text-slate-500">Pet</span><span className="max-w-[55%] truncate font-medium text-slate-900">{form.petName || "Enter pet name"}</span></div>
                </div>

                <div className="mt-4 rounded-2xl border border-slate-200 p-3 text-sm">
                  <div className="flex items-center justify-between py-1.5"><span className="text-slate-500">Consultation fee</span><span className="font-medium text-slate-900">{formatCurrency(IN_CLINIC_PRICING.originalAmount)}</span></div>
                  <div className="flex items-center justify-between py-1.5"><span className="text-slate-500">Discount</span><span className="font-medium text-emerald-700">- {formatCurrency(IN_CLINIC_PRICING.discountAmount)}</span></div>
                  <div className="flex items-center justify-between py-1.5"><span className="text-slate-500">After discount</span><span className="font-medium text-slate-900">{formatCurrency(IN_CLINIC_PRICING.discountedAmount)}</span></div>
                  <div className="flex items-center justify-between py-1.5"><span className="text-slate-500">GST ({IN_CLINIC_PRICING.gstRate}%)</span><span className="font-medium text-slate-900">{formatCurrency(IN_CLINIC_PRICING.gstAmount)}</span></div>
                  <div className="mt-1 flex items-center justify-between border-t border-slate-200 pt-2"><span className="font-semibold text-slate-900">Total</span><span className="font-semibold text-slate-900">{formatCurrency(IN_CLINIC_PRICING.totalAmount)}</span></div>
                </div>

                <button
                  type="button"
                  onClick={handlePayNow}
                  disabled={paymentBusy}
                  className="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {paymentPhase === PAYMENT_PHASES.submitting
                    ? "Syncing booking..."
                    : paymentPhase === PAYMENT_PHASES.success_processing
                      ? "Opening Razorpay..."
                      : paymentPhase === PAYMENT_PHASES.redirecting
                        ? "Verifying payment..."
                        : "Continue to payment"}
                </button>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>
  );
}
