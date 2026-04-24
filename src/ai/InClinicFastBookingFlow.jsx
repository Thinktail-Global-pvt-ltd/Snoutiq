import React, { useEffect, useMemo, useState } from "react";
import { useLocation } from "react-router-dom";
import { readAiAuthState } from "./AiAuth";
import { IN_CLINIC_PRICING } from "../newflow/askBooking/inClinicFlowShared";

const API_BASE = "https://snoutiq.com/backend/api";

const DEFAULT_ENDPOINTS = {
  clinics: "/nearby-plus-featured",
  clinicDoctors: null,
  clinicAvailability: "/clinics/:clinicId/doctor-availability?service_type=in_clinic&date=:date",
  serviceBookings: "/chat/service-bookings",
  createOrder: "/create-order",
  verifyPayment: "/rzp/verify",
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
  const [apiResponses, setApiResponses] = useState({
    reportedSymptom: null,
    serviceBooking: null,
    createOrder: null,
    verifyPayment: null,
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
    let active = true;

    async function fetchClinics() {
      setLoadingClinics(true);
      setClinicError("");
      setStatusMessage("");

      try {
        const clinicsBasePath = `${apiBase}${endpoints.clinics}`;
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
    endpoints.clinics,
    requestedClinicId,
    resolvedToken,
    resolvedUserId,
    suggestedClinicFromRoute,
  ]);

  useEffect(() => {
    if (!selectedClinic) return;

    async function fetchDoctorsIfNeeded() {
      if (!endpoints.clinicDoctors) {
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
        const url = `${apiBase}${endpoints.clinicDoctors.replace(":clinicId", clinicId)}`;
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
  }, [apiBase, endpoints.clinicDoctors, requestedDoctorId, resolvedToken, selectedClinic]);

  useEffect(() => {
    if (!selectedClinic || !selectedDate) return;
    const clinicId = resolveClinicId(selectedClinic);
    if (!clinicId) return;

    let active = true;

    async function fetchSlots() {
      setSlotsLoading(true);
      setSelectedTime("");
      try {
        const url = `${apiBase}${buildAvailabilityUrl(endpoints.clinicAvailability, clinicId, selectedDate)}`;
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

        const normalizedSlots = normalizeSlots(data);
        if (!active) return;
        setSlots(normalizedSlots);
      } catch (error) {
        if (!active) return;
        setLocalError(error?.message || "Slots load nahi hue.");
        setSlots([]);
      } finally {
        if (active) setSlotsLoading(false);
      }
    }

    fetchSlots();

    return () => {
      active = false;
    };
  }, [apiBase, endpoints.clinicAvailability, resolvedToken, selectedClinic, selectedDate]);

  const clinicSummary = useMemo(() => {
    if (!selectedClinic) return "No clinic selected";
    return `${selectedClinic.name}${selectedClinic.distance ? ` • ${selectedClinic.distance}` : ""}`;
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
    if (!resolvedUserId || !resolvedPetId) {
      return "User/Pet context missing hai. Ask flow se dubara open kijiye.";
    }
    if (!selectedClinic) return "Please select a clinic.";
    if (!selectedDoctor) return "Please select a doctor.";
    if (!selectedDate || !selectedTime) return "Please select date and slot.";
    if (!normalizeText(form.ownerName)) return "Owner name required hai.";
    if (normalizePhone(form.phone).replace(/\D/g, "").length < 10) {
      return "Valid phone number required hai.";
    }
    if (!normalizeText(form.petName)) return "Pet name required hai.";
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
    const validationError = validateBeforePayment();
    if (validationError) {
      setLocalError(validationError);
      setExpandedStep(4);
      return;
    }

    setLocalError("");
    setStatusMessage("");
    setApiResponses({
      reportedSymptom: null,
      serviceBooking: null,
      createOrder: null,
      verifyPayment: null,
    });
    setPaymentPhase(PAYMENT_PHASES.submitting);

    try {
      const userIdNumber = toInteger(resolvedUserId, 0);
      const petIdNumber = toInteger(resolvedPetId, 0);
      const clinicIdText = normalizeText(resolveClinicId(selectedClinic) || requestedClinicId);
      const doctorIdText = normalizeText(resolveDoctorId(selectedDoctor) || requestedDoctorId);
      const clinicIdNumber = toInteger(clinicIdText, 0);
      const doctorIdNumber = toInteger(doctorIdText, 0);
      const symptomText = normalizeText(form.symptoms);

      const symptomRes = await fetch(
        `${apiBase}/users/${encodeURIComponent(resolvedUserId)}/pets/${encodeURIComponent(resolvedPetId)}/reported-symptom`,
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
        pet_id: petIdNumber || resolvedPetId,
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
        `${apiBase}${endpoints.serviceBookings || DEFAULT_ENDPOINTS.serviceBookings}`,
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
          `${apiBase}${endpoints.serviceBookings || DEFAULT_ENDPOINTS.serviceBookings}`,
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
        setStatusMessage(
          normalizeText(
            serviceData?.message ||
              serviceData?.error ||
              "Service booking sync failed, but payment can continue.",
          ) || "Service booking sync failed, but payment can continue.",
        );
      } else {
        setStatusMessage("Service booking created. Opening payment...");
      }

      const createPayload = stripEmpty({
        amount: IN_CLINIC_PRICING.totalAmount,
        order_type: "appointment",
        user_id: userIdNumber || resolvedUserId,
        doctor_id: doctorIdNumber || doctorIdText,
        clinic_id: clinicIdNumber || clinicIdText,
        pet_id: petIdNumber || resolvedPetId,
      });

      const createRes = await fetch(`${apiBase}${endpoints.createOrder}`, {
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

      setPaymentPhase(PAYMENT_PHASES.redirecting);

      const verifyPayload = {
        razorpay_order_id: razorpayResponse?.razorpay_order_id || orderId,
        razorpay_payment_id: razorpayResponse?.razorpay_payment_id,
        razorpay_signature: razorpayResponse?.razorpay_signature,
        order_type: "appointment",
        user_id: userIdNumber || resolvedUserId,
        pet_id: petIdNumber || resolvedPetId,
        clinic_id: clinicIdNumber || clinicIdText,
        doctor_id: doctorIdNumber || doctorIdText,
        appointment_date: selectedDate,
        appointment_time: parseTimeForApi(selectedTime),
      };
      const finalVerifyPayload = stripEmpty(verifyPayload);

      const verifyRes = await fetch(`${apiBase}${endpoints.verifyPayment}`, {
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

      const successPayload = {
        bookingId: verifyData?.appointment?.id || verifyData?.id || createData?.appointment?.id || "N/A",
        clinicName: selectedClinic.name,
        doctorName: selectedDoctor.name,
        appointmentDate: selectedDate,
        appointmentTime: selectedTime,
        paymentId: razorpayResponse?.razorpay_payment_id,
        amount: payableAmount,
        responses: {
          reportedSymptom: symptomData,
          serviceBooking: serviceData,
          createOrder: createData,
          verifyPayment: verifyData,
        },
      };

      setSuccessState(successPayload);
      setPaymentPhase(PAYMENT_PHASES.idle);

      if (typeof onSuccess === "function") {
        onSuccess(successPayload);
      }
    } catch (error) {
      setPaymentPhase(PAYMENT_PHASES.idle);
      setLocalError(error?.message || "Payment failed. Please try again.");
    }
  }

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        <div className="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
          <div className="bg-gradient-to-r from-emerald-600 via-teal-500 to-cyan-500 px-5 py-6 text-white sm:px-8">
            <div className="max-w-3xl">
              <div className="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">
                IN-CLINIC BOOKING
              </div>
              <h1 className="mt-3 text-2xl font-bold sm:text-3xl">Fast clinic booking flow</h1>
              <p className="mt-2 text-sm text-emerald-50 sm:text-base">
                Same visit flow ko React JS web ke liye convert kiya gaya hai: clinic select, doctor choose, date/time slot,
                symptoms fill, media upload, consent aur payment.
              </p>
            </div>
          </div>

          <div className="grid gap-5 px-4 py-5 lg:grid-cols-[1.2fr_0.8fr] sm:px-6 lg:px-8">
            <div className="space-y-4">
              <StepCard
                step={1}
                title="Choose clinic"
                subtitle="Nearest clinics or available clinics list"
                open={expandedStep === 1}
                onEdit={() => setExpandedStep(1)}
                summary={clinicSummary}
              >
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
                              <div className="mt-1 text-sm text-slate-500">{clinic.address || "Clinic address"}</div>
                            </div>
                            {clinic.distance ? (
                              <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                {clinic.distance}
                              </span>
                            ) : null}
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
                subtitle="Select doctor from selected clinic"
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
                title="Choose date and slot"
                subtitle="Pick available appointment date and time"
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
                title="Symptoms and payment"
                subtitle="Share issue, upload reports, then pay"
                open={expandedStep === 4}
                onEdit={() => setExpandedStep(4)}
                summary={detailsSummary}
              >
                <div className="grid gap-4">
                  <div className="grid gap-4 sm:grid-cols-2">
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

                  <div className="grid gap-4 sm:grid-cols-2">
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

              {apiResponses.reportedSymptom ||
              apiResponses.serviceBooking ||
              apiResponses.createOrder ||
              apiResponses.verifyPayment ? (
                <details className="rounded-[24px] border border-slate-200 bg-slate-50 px-4 py-3">
                  <summary className="cursor-pointer text-sm font-semibold text-slate-800">
                    Full Backend Responses
                  </summary>
                  <pre className="mt-3 max-h-72 overflow-auto rounded-2xl bg-white p-3 text-xs text-slate-700">
                    {JSON.stringify(apiResponses, null, 2)}
                  </pre>
                </details>
              ) : null}


              {successState ? (
                <div className="rounded-[28px] border border-emerald-200 bg-emerald-50 p-5">
                  <div className="text-sm font-semibold uppercase tracking-wide text-emerald-700">Appointment booked</div>
                  <h2 className="mt-2 text-xl font-bold text-slate-900">Booking successful</h2>
                  <div className="mt-4 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                    <div><span className="font-semibold">Booking ID:</span> {successState.bookingId}</div>
                    <div><span className="font-semibold">Payment ID:</span> {successState.paymentId}</div>
                    <div><span className="font-semibold">Clinic:</span> {successState.clinicName}</div>
                    <div><span className="font-semibold">Doctor:</span> {successState.doctorName}</div>
                    <div><span className="font-semibold">Date:</span> {formatDateLabel(successState.appointmentDate)}</div>
                    <div><span className="font-semibold">Time:</span> {successState.appointmentTime}</div>
                  </div>
                  {successState?.responses ? (
                    <details className="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/40 p-3">
                      <summary className="cursor-pointer text-sm font-semibold text-emerald-800">
                        Full Backend Responses
                      </summary>
                      <pre className="mt-3 max-h-72 overflow-auto rounded-2xl bg-white p-3 text-xs text-slate-700">
                        {JSON.stringify(successState.responses, null, 2)}
                      </pre>
                    </details>
                  ) : null}
                </div>
              ) : null}
            </div>

            <aside className="lg:sticky lg:top-6 lg:h-fit">
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
                        : `Pay ${formatCurrency(payableAmount)}`}
                </button>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>
  );
}
