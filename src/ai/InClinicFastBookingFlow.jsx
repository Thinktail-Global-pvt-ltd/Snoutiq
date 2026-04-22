import React, { useEffect, useMemo, useState } from "react";

const API_BASE = "https://snoutiq.com/backend/api";

const DEFAULT_ENDPOINTS = {
  clinics: "/nearby-plus-featured?user_id=1394",
  clinicDoctors: null,
  clinicAvailability: "/clinics/:clinicId/doctor-availability?service_type=in_clinic&date=:date",
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
  const [successState, setSuccessState] = useState(null);

  const [form, setForm] = useState({
    ownerName: normalizeText(user?.name || ""),
    phone: normalizePhone(user?.phone || user?.mobile || ""),
    email: normalizeText(user?.email || ""),
    petName: normalizeText(user?.pet?.name || user?.selectedPet?.name || user?.pet_name || ""),
    petType: normalizeText(user?.pet?.pet_type || user?.selectedPet?.pet_type || "dog") || "dog",
    symptoms: "",
    consentGiven: false,
  });
  const [mediaFiles, setMediaFiles] = useState([]);

  const payableAmount = useMemo(() => Number(selectedDoctor?.price || 0), [selectedDoctor]);
  const paymentBusy = paymentPhase !== PAYMENT_PHASES.idle;

  useEffect(() => {
    let active = true;

    async function fetchClinics() {
      setLoadingClinics(true);
      setClinicError("");

      try {
        const response = await fetch(`${apiBase}${endpoints.clinics}`, {
          method: "GET",
          headers: getHeaders(token),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          throw new Error(data?.message || "Clinics load nahi hue.");
        }

        const normalized = flattenClinics(data);
        if (!active) return;
        setClinics(normalized);
        if (normalized.length) {
          const firstClinic = normalized[0];
          setSelectedClinic(firstClinic);
          setDoctors(flattenDoctorsFromClinic(firstClinic));
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
  }, [apiBase, endpoints.clinics, token]);

  useEffect(() => {
    if (!selectedClinic) return;

    async function fetchDoctorsIfNeeded() {
      if (!endpoints.clinicDoctors) {
        setDoctors(flattenDoctorsFromClinic(selectedClinic));
        return;
      }

      try {
        const clinicId = resolveClinicId(selectedClinic);
        const url = `${apiBase}${endpoints.clinicDoctors.replace(":clinicId", clinicId)}`;
        const response = await fetch(url, { headers: getHeaders(token) });
        const data = await response.json().catch(() => ({}));
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
      } catch (error) {
        setLocalError(error?.message || "Doctors load nahi hue.");
      }
    }

    setSelectedDoctor(null);
    setSelectedTime("");
    setSlots([]);
    fetchDoctorsIfNeeded();
  }, [apiBase, endpoints.clinicDoctors, selectedClinic, token]);

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
          headers: getHeaders(token),
        });
        const data = await response.json().catch(() => ({}));

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
  }, [apiBase, endpoints.clinicAvailability, selectedClinic, selectedDate, token]);

  const clinicSummary = useMemo(() => {
    if (!selectedClinic) return "No clinic selected";
    return `${selectedClinic.name}${selectedClinic.distance ? ` • ${selectedClinic.distance}` : ""}`;
  }, [selectedClinic]);

  const doctorSummary = useMemo(() => {
    if (!selectedDoctor) return "No doctor selected";
    return `${selectedDoctor.name} • ${formatCurrency(selectedDoctor.price)}`;
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
  }

  function handleDoctorSelect(doctor) {
    setSelectedDoctor(doctor);
    setExpandedStep(3);
    setLocalError("");
  }

  function handleMediaChange(event) {
    const files = Array.from(event.target.files || []);
    setMediaFiles((current) => [...current, ...files]);
  }

  function removeFile(targetFile) {
    setMediaFiles((current) => current.filter((file) => file !== targetFile));
  }

  function validateBeforePayment() {
    if (!selectedClinic) return "Please select a clinic.";
    if (!selectedDoctor) return "Please select a doctor.";
    if (!selectedDate || !selectedTime) return "Please select date and slot.";
    if (!normalizeText(form.ownerName)) return "Owner name required hai.";
    if (!normalizePhone(form.phone)) return "Phone number required hai.";
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
    setPaymentPhase(PAYMENT_PHASES.submitting);

    try {
      const formData = new FormData();
      formData.append("user_id", user?.id || user?.user_id || "");
      formData.append("appointment_type", "in_clinic");
      formData.append("clinic_id", selectedClinic.id);
      formData.append("doctor_id", selectedDoctor.id);
      formData.append("appointment_date", selectedDate);
      formData.append("appointment_time", selectedTime);
      formData.append("amount", String(Math.round(payableAmount)));
      formData.append("pet_name", form.petName);
      formData.append("pet_type", form.petType);
      formData.append("symptoms", form.symptoms);
      formData.append("description", form.symptoms);
      formData.append("owner_name", form.ownerName);
      formData.append("mobile", form.phone);
      formData.append("consent", form.consentGiven ? "1" : "0");

      mediaFiles.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file);
      });

      const createRes = await fetch(`${apiBase}${endpoints.createOrder}`, {
        method: "POST",
        headers: getHeaders(token),
        body: formData,
      });
      const createData = await createRes.json().catch(() => ({}));

      if (!createRes.ok) {
        throw new Error(createData?.message || createData?.error || "Order create nahi hua.");
      }

      const order = createData?.order || createData?.data?.order || createData?.data || {};
      const razorpayKey = normalizeText(createData?.key || createData?.data?.key || "");
      const orderId = normalizeText(order?.id || order?.order_id || createData?.order_id || createData?.data?.order_id);
      const amountInPaise = Number(order?.amount || Math.round(payableAmount * 100));

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
        appointment_type: "in_clinic",
        clinic_id: selectedClinic.id,
        doctor_id: selectedDoctor.id,
        appointment_date: selectedDate,
        appointment_time: selectedTime,
      };

      const verifyRes = await fetch(`${apiBase}${endpoints.verifyPayment}`, {
        method: "POST",
        headers: {
          ...getHeaders(token),
          "Content-Type": "application/json",
        },
        body: JSON.stringify(verifyPayload),
      });
      const verifyData = await verifyRes.json().catch(() => ({}));

      if (!verifyRes.ok && !verifyData?.success) {
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

                <button
                  type="button"
                  onClick={handlePayNow}
                  disabled={paymentBusy}
                  className="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {paymentPhase === PAYMENT_PHASES.submitting
                    ? "Creating order..."
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
