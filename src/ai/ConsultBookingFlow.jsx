import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation } from "react-router-dom";
import { readAiAuthState } from "./AiAuth";

const API_BASE = "https://snoutiq.com/backend/api";
const ENDPOINTS = {
  list: "/exported_from_excell_doctors",
  createOrder: "/create-order",
  verifyPayment: "/rzp/verify",
  serviceBookings: "/chat/service-bookings",
};

const DAY_RATE_START_HOUR = 8;
const DAY_RATE_END_HOUR = 20;
const STATIC_CONSULTATION_AMOUNT = 599;
const STATIC_SERVICE_AMOUNT = 0;
const STATIC_DISCOUNT_AMOUNT = 100;
const GST_RATE = 0.18;

function normalizeText(value) {
  if (value === null || value === undefined) return "";
  if (Array.isArray(value)) {
    return value.map((item) => String(item || "").trim()).filter(Boolean).join(", ");
  }
  return String(value).trim();
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

function parseSpecialization(value) {
  if (Array.isArray(value)) {
    return value.map((entry) => normalizeText(entry)).filter(Boolean);
  }

  const text = normalizeText(value);
  if (!text) return [];

  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) {
        return parsed.map((entry) => normalizeText(entry)).filter(Boolean);
      }
    } catch (_) {
      // ignore and fallback
    }
  }

  return text
    .split(",")
    .map((entry) => entry.trim())
    .filter(Boolean);
}

function formatCurrency(value) {
  const amount = Number(value || 0);
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0,
  }).format(Number.isFinite(amount) ? amount : 0);
}

function roundAmount(value) {
  const amount = Number(value || 0);
  if (!Number.isFinite(amount)) return 0;
  return Math.max(0, Math.round((amount + Number.EPSILON) * 100) / 100);
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

async function readApiBody(response) {
  const text = await response.text();
  if (!text) return {};
  try {
    return JSON.parse(text);
  } catch {
    return { raw: text };
  }
}

function currentSlot() {
  const hour = new Date().getHours();
  return hour >= DAY_RATE_START_HOUR && hour < DAY_RATE_END_HOUR ? "day" : "night";
}

function resolveDoctorFee(doctor, slot = currentSlot()) {
  const preferred =
    slot === "night"
      ? doctor.feeNight || doctor.feeDay || doctor.fee
      : doctor.feeDay || doctor.feeNight || doctor.fee;

  const value = Number(preferred || 0);
  return Number.isFinite(value) ? value : 0;
}

function flattenDoctors(payload) {
  const result = [];

  const addDoctor = (doctor, clinic = null) => {
    if (!doctor || typeof doctor !== "object") return;

    const id =
      doctor.doctor_id ||
      doctor.id ||
      doctor.doctorId ||
      doctor.user_id ||
      doctor.userId ||
      "";

    const name =
      doctor.doctor_name ||
      doctor.name ||
      doctor.full_name ||
      doctor.fullName ||
      "Doctor";

    const specializations = parseSpecialization(
      doctor.specialization_select_all_that_apply ||
        doctor.specialization ||
        doctor.speciality ||
        doctor.specialty
    );

    const feeDay = Number(
      doctor.day_fee ||
        doctor.consultation_fee_day ||
        doctor.online_consultation_fee_day ||
        doctor.video_day_rate ||
        doctor.fee_day ||
        doctor.price_day ||
        0
    );

    const feeNight = Number(
      doctor.night_fee ||
        doctor.consultation_fee_night ||
        doctor.online_consultation_fee_night ||
        doctor.video_night_rate ||
        doctor.fee_night ||
        doctor.price_night ||
        0
    );

    const fee = Number(
      doctor.fee ||
        doctor.consult_fee ||
        doctor.consultation_fee ||
        doctor.price ||
        doctor.doctors_price ||
        feeDay ||
        feeNight ||
        0
    );

    const rating = Number(
      doctor.average_review_points ||
        doctor.avg_review_points ||
        doctor.avg_rating ||
        doctor.rating ||
        0
    );

    const reviews = Number(
      doctor.reviews_count || doctor.review_count || (Array.isArray(doctor.reviews) ? doctor.reviews.length : 0)
    );

    const isAvailableRaw =
      doctor.is_available ?? doctor.available ?? doctor.open_now ?? null;

    const isAvailable =
      typeof isAvailableRaw === "boolean"
        ? isAvailableRaw
        : String(doctor.status || doctor.doctor_status || "")
            .toLowerCase()
            .includes("available");

    result.push({
      raw: doctor,
      id: String(id || name),
      doctorId: String(id || ""),
      name: normalizeText(name) || "Doctor",
      clinicName: normalizeText(
        doctor.clinic_name || doctor.clinicName || clinic?.name || clinic?.clinic_name || "Clinic"
      ),
      clinicId: String(
        doctor.clinic_id || doctor.vet_registeration_id || clinic?.id || clinic?.clinic_id || ""
      ),
      image: normalizeImage(
        doctor.doctor_image_url ||
          doctor.doctor_image_blob_url ||
          doctor.doctor_image ||
          doctor.image ||
          doctor.photo ||
          doctor.avatar
      ),
      email: normalizeText(doctor.email || doctor.doctor_email || clinic?.email || ""),
      mobile: normalizePhone(doctor.mobile || doctor.doctor_mobile || clinic?.mobile || doctor.phone || ""),
      specializations,
      experience: Number(doctor.years_of_experience || doctor.experience || doctor.exp_years || 0),
      rating: Number.isFinite(rating) ? rating : 0,
      reviews: Number.isFinite(reviews) ? reviews : 0,
      feeDay: Number.isFinite(feeDay) ? feeDay : 0,
      feeNight: Number.isFinite(feeNight) ? feeNight : 0,
      fee: Number.isFinite(fee) ? fee : 0,
      available: Boolean(isAvailable),
      responseTime:
        normalizeText(
          slot === "night"
            ? doctor.response_time_for_online_consults_night || doctor.response_time_for_online_consults_day
            : doctor.response_time_for_online_consults_day || doctor.response_time_for_online_consults_night
        ) || "Usually responds quickly",
    });
  };

  const slot = currentSlot();

  const clinicRows = Array.isArray(payload?.data?.data)
    ? payload.data.data
    : [];

  clinicRows.forEach((clinic) => {
    const doctors = Array.isArray(clinic?.doctors) ? clinic.doctors : [];
    doctors.forEach((doctor) => addDoctor(doctor, clinic));
  });

  const flatDoctors = Array.isArray(payload?.doctors)
    ? payload.doctors
    : Array.isArray(payload?.data?.doctors)
      ? payload.data.doctors
      : Array.isArray(payload?.data)
        ? payload.data
        : Array.isArray(payload)
          ? payload
          : [];

  flatDoctors.forEach((doctor) => addDoctor(doctor, payload?.clinic || payload?.data?.clinic || null));

  const unique = [];
  const seen = new Set();

  result.forEach((doctor) => {
    const key = doctor.doctorId || `${doctor.name}-${doctor.clinicName}`;
    if (!key || seen.has(key)) return;
    seen.add(key);
    unique.push(doctor);
  });

  return unique.sort((a, b) => {
    if (a.available !== b.available) return a.available ? -1 : 1;
    if (b.rating !== a.rating) return b.rating - a.rating;
    return resolveDoctorFee(a) - resolveDoctorFee(b);
  });
}

function getAuthHeaders(token, extra = {}) {
  const headers = {
    "Content-Type": "application/json",
    ...extra,
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  return headers;
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

function BottomSheet({ open, title, subtitle, onClose, children }) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50">
      <button
        type="button"
        aria-label="Close overlay"
        onClick={onClose}
        className="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]"
      />

      <div className="absolute inset-x-0 bottom-0 mx-auto w-full max-w-md rounded-t-[32px] bg-white shadow-2xl">
        <div className="mx-auto mt-3 h-1.5 w-14 rounded-full bg-slate-200" />
        <div className="flex items-start justify-between px-5 pb-4 pt-4">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
            {subtitle ? <p className="mt-1 text-sm text-slate-500">{subtitle}</p> : null}
          </div>
          <button
            type="button"
            onClick={onClose}
            className="rounded-full border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          >
            Close
          </button>
        </div>
        <div className="max-h-[78vh] overflow-y-auto px-5 pb-6">{children}</div>
      </div>
    </div>
  );
}

function StatPill({ children }) {
  return (
    <span className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
      {children}
    </span>
  );
}

function InfoRow({ label, value, strong = false }) {
  return (
    <div className="flex items-center justify-between gap-3 py-2 text-sm">
      <span className="text-slate-500">{label}</span>
      <span className={strong ? "font-semibold text-slate-900" : "text-slate-700"}>{value}</span>
    </div>
  );
}

export default function ConsultBookingFlow({
  token = "",
  user = {},
  apiBase = API_BASE,
  endpoints = ENDPOINTS,
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
  const initialDoctorSelectionAppliedRef = useRef(false);

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

  const resolvedClinicId = useMemo(
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

  const resolvedCallSessionId = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.callSessionId,
          routeState?.call_session_id,
          routeState?.latestCallSession?.call_identifier,
          routeState?.latestCallSession?.callIdentifier,
          authState?.latestCallSession?.call_identifier,
          authState?.latestCallSession?.callIdentifier,
        ),
      ),
    [
      authState?.latestCallSession?.call_identifier,
      authState?.latestCallSession?.callIdentifier,
      routeState?.callSessionId,
      routeState?.call_session_id,
      routeState?.latestCallSession?.call_identifier,
      routeState?.latestCallSession?.callIdentifier,
    ],
  );

  const resolvedChannelName = useMemo(
    () =>
      normalizeText(
        pickFirst(
          routeState?.channelName,
          routeState?.channel_name,
          routeState?.latestCallSession?.channel_name,
          routeState?.latestCallSession?.channelName,
          authState?.latestCallSession?.channel_name,
          authState?.latestCallSession?.channelName,
        ),
      ),
    [
      authState?.latestCallSession?.channel_name,
      authState?.latestCallSession?.channelName,
      routeState?.channelName,
      routeState?.channel_name,
      routeState?.latestCallSession?.channel_name,
      routeState?.latestCallSession?.channelName,
    ],
  );

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

  const requestedDoctorId = useMemo(
    () =>
      normalizeText(
        routeState?.doctorId ??
          routeState?.doctor_id ??
          routeState?.suggestedDoctor?.doctorId ??
          routeState?.suggestedDoctor?.doctor_id ??
          ""
      ),
    [routeState]
  );

  const requestedDoctorName = useMemo(
    () =>
      normalizeText(
        routeState?.doctorName ??
          routeState?.doctor_name ??
          routeState?.suggestedDoctor?.doctorName ??
          routeState?.suggestedDoctor?.doctor_name ??
          ""
      ).toLowerCase(),
    [routeState]
  );

  const [doctors, setDoctors] = useState([]);
  const [loadingDoctors, setLoadingDoctors] = useState(true);
  const [listError, setListError] = useState("");

  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [showDetailsSheet, setShowDetailsSheet] = useState(false);
  const [showPaymentSheet, setShowPaymentSheet] = useState(false);

  const [details, setDetails] = useState({
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
    petAge: "",
    issue: symptomPrefillText,
    notes: "",
  });

  const [couponCode, setCouponCode] = useState("");
  const [gstEnabled, setGstEnabled] = useState(false);
  const [gstNumber, setGstNumber] = useState("");

  const [createOrderLoading, setCreateOrderLoading] = useState(false);
  const [paymentError, setPaymentError] = useState("");
  const [reportedSymptomMessage, setReportedSymptomMessage] = useState("");
  const [couponMessage, setCouponMessage] = useState("");
  const [successState, setSuccessState] = useState(null);
  const [apiResponses, setApiResponses] = useState({
    reportedSymptom: null,
    createOrder: null,
    verifyPayment: null,
  });

  const slot = useMemo(() => currentSlot(), []);
  const consultationAmount = STATIC_CONSULTATION_AMOUNT;
  const serviceAmount = STATIC_SERVICE_AMOUNT;
  const subtotalAmount = useMemo(
    () => roundAmount(consultationAmount + serviceAmount),
    [consultationAmount, serviceAmount],
  );
  const discountAmount = useMemo(
    () => roundAmount(Math.min(STATIC_DISCOUNT_AMOUNT, subtotalAmount)),
    [subtotalAmount],
  );
  const taxableAmount = useMemo(
    () => roundAmount(Math.max(subtotalAmount - discountAmount, 0)),
    [discountAmount, subtotalAmount],
  );
  const gstAmount = useMemo(() => roundAmount(taxableAmount * GST_RATE), [taxableAmount]);
  const payableAmount = useMemo(
    () => roundAmount(taxableAmount + gstAmount),
    [gstAmount, taxableAmount],
  );
  const payableAmountInr = useMemo(() => toInteger(payableAmount), [payableAmount]);

  useEffect(() => {
    let active = true;

    async function loadDoctors() {
      setLoadingDoctors(true);
      setListError("");

      try {
        const response = await fetch(`${apiBase}${endpoints.list}`, {
          method: "GET",
          headers: getAuthHeaders(resolvedToken, {
            Accept: "application/json",
          }),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(data?.message || "Doctor list load nahi hui.");
        }

        const normalizedDoctors = flattenDoctors(data);
        if (!active) return;
        setDoctors(normalizedDoctors);
      } catch (error) {
        if (!active) return;
        setListError(error?.message || "Doctor list load nahi hui.");
        setDoctors([]);
      } finally {
        if (active) {
          setLoadingDoctors(false);
        }
      }
    }

    loadDoctors();

    return () => {
      active = false;
    };
  }, [apiBase, endpoints.list, resolvedToken]);

  useEffect(() => {
    if (initialDoctorSelectionAppliedRef.current || loadingDoctors) {
      return;
    }

    if (!requestedDoctorId && !requestedDoctorName) {
      initialDoctorSelectionAppliedRef.current = true;
      return;
    }

    const matchedDoctor = doctors.find((doctor) => {
      const doctorId = normalizeText(doctor?.doctorId || doctor?.id);
      const doctorName = normalizeText(doctor?.name).toLowerCase();

      if (requestedDoctorId && doctorId === requestedDoctorId) {
        return true;
      }

      if (requestedDoctorName && doctorName === requestedDoctorName) {
        return true;
      }

      return false;
    });

    initialDoctorSelectionAppliedRef.current = true;

    if (!matchedDoctor) {
      return;
    }

    setSelectedDoctor(matchedDoctor);
    setShowDetailsSheet(true);
  }, [doctors, loadingDoctors, requestedDoctorId, requestedDoctorName]);

  const handleSelectDoctor = (doctor) => {
    setSelectedDoctor(doctor);
    setPaymentError("");
    setCouponMessage("");
    setShowDetailsSheet(true);
  };

  const handleDetailChange = (key, value) => {
    setDetails((current) => ({ ...current, [key]: value }));
  };

  const recordApiResponse = (key, payload) => {
    setApiResponses((current) => ({
      ...current,
      [key]: payload,
    }));
  };

  const validateDetails = () => {
    if (!resolvedUserId || !resolvedPetId) {
      return "User/Pet context missing hai. Ask flow se dubara open kijiye.";
    }
    if (!selectedDoctor) return "Please choose a doctor first.";
    if (!normalizeText(details.ownerName)) return "Owner name required hai.";
    if (normalizePhone(details.phone).replace(/\D/g, "").length < 10) {
      return "Valid mobile number required hai.";
    }
    if (!normalizeText(details.petName)) return "Pet name required hai.";
    if (!normalizeText(details.issue)) return "Disease / issue details required hain.";
    if (gstEnabled && normalizeText(gstNumber).length !== 15) {
      return "GST number 15 characters ka hona chahiye.";
    }
    return "";
  };

  const goToPayment = () => {
    const error = validateDetails();
    if (error) {
      setPaymentError(error);
      return;
    }

    setPaymentError("");
    setShowDetailsSheet(false);
    setShowPaymentSheet(true);
  };

  const openRazorpay = async ({ orderId, amountInPaise, razorpayKey, serverData }) => {
    const loaded = await loadRazorpayScript();
    if (!loaded) {
      throw new Error("Razorpay SDK load nahi hua. Internet check karo aur dubara try karo.");
    }

    return new Promise((resolve, reject) => {
      const razorpay = new window.Razorpay({
        key: razorpayKey,
        amount: String(amountInPaise),
        currency: serverData?.order?.currency || "INR",
        name: "Snoutiq",
        description: `Consultation with ${selectedDoctor?.name || "Doctor"}`,
        order_id: orderId,
        prefill: {
          name: details.ownerName,
          email: details.email,
          contact: details.phone,
        },
        theme: {
          color: "#2563eb",
        },
        modal: {
          ondismiss: () => {
            reject(new Error("Payment cancelled by user."));
          },
        },
        handler: (response) => {
          resolve(response);
        },
      });

      razorpay.open();
    });
  };

  const handlePayNow = async () => {
    const error = validateDetails();
    if (error) {
      setPaymentError(error);
      return;
    }

    if (!selectedDoctor) {
      setPaymentError("Doctor select kijiye.");
      return;
    }

    setCreateOrderLoading(true);
    setPaymentError("");
    setCouponMessage("");
    setReportedSymptomMessage("");
    setApiResponses({
      reportedSymptom: null,
      createOrder: null,
      verifyPayment: null,
    });

    try {
      const doctorId = normalizeText(selectedDoctor.doctorId || selectedDoctor.id);
      const clinicId = normalizeText(selectedDoctor.clinicId || resolvedClinicId);
      const userIdNumber = toInteger(resolvedUserId, 0);
      const petIdNumber = toInteger(resolvedPetId, 0);
      const doctorIdNumber = toInteger(doctorId, 0);
      const clinicIdNumber = toInteger(clinicId, 0);
      const symptomText = normalizeText(details.issue);

      const reportedSymptomResponse = await fetch(
        `${apiBase}/users/${encodeURIComponent(resolvedUserId)}/pets/${encodeURIComponent(resolvedPetId)}/reported-symptom`,
        {
          method: "PUT",
          headers: getAuthHeaders(resolvedToken, {
            Accept: "application/json",
            "Content-Type": "application/json",
          }),
          body: JSON.stringify({
            reported_symptom: symptomText,
          }),
        },
      );
      const reportedSymptomData = await readApiBody(reportedSymptomResponse);
      recordApiResponse("reportedSymptom", reportedSymptomData);

      if (
        !reportedSymptomResponse.ok ||
        (reportedSymptomData &&
          typeof reportedSymptomData === "object" &&
          reportedSymptomData.success === false)
      ) {
        setReportedSymptomMessage(
          normalizeText(
            reportedSymptomData?.message ||
              reportedSymptomData?.error ||
              "Reported symptom update failed.",
          ) || "Reported symptom update failed.",
        );
      } else {
        setReportedSymptomMessage("Reported symptom updated successfully.");
      }

      const createPayload = {
        amount: payableAmountInr,
        order_type: "video_consult",
        user_id: userIdNumber || resolvedUserId,
        doctor_id: doctorIdNumber || doctorId,
        clinic_id: clinicId ? clinicIdNumber || clinicId : undefined,
        pet_id: petIdNumber || resolvedPetId,
        call_session_id: normalizeText(resolvedCallSessionId) || undefined,
        channel_name: normalizeText(resolvedChannelName) || undefined,
        coupon_code: normalizeText(couponCode).toUpperCase() || undefined,
        gst_invoice: gstEnabled ? 1 : undefined,
        gst_number: gstEnabled ? normalizeText(gstNumber).toUpperCase() : undefined,
      };
      const finalCreatePayload = Object.fromEntries(
        Object.entries(createPayload).filter(
          ([, value]) => value !== undefined && value !== null && value !== "",
        ),
      );

      const createRes = await fetch(`${apiBase}${endpoints.createOrder || ENDPOINTS.createOrder}`, {
        method: "POST",
        headers: getAuthHeaders(resolvedToken, {
          Accept: "application/json",
        }),
        body: JSON.stringify(finalCreatePayload),
      });

      const createData = await readApiBody(createRes);
      recordApiResponse("createOrder", createData);
      if (!createRes.ok) {
        throw new Error(createData?.message || createData?.error || "Order create nahi hua.");
      }

      const backendCoupon = createData?.coupon || createData?.data?.coupon || null;
      if (normalizeText(couponCode)) {
        if (backendCoupon?.code || backendCoupon?.coupon_code || backendCoupon?.couponCode) {
          setCouponMessage("Coupon applied successfully.");
        } else {
          setCouponMessage("Coupon server par verify hoga. Agar valid nahi hua to backend reject karega.");
        }
      }

      const order = createData?.order || createData?.data?.order || createData?.data || {};
      const razorpayKey =
        normalizeText(createData?.key) ||
        normalizeText(createData?.data?.key) ||
        normalizeText(window?.ENV?.RAZORPAY_KEY_ID || "");

      const orderId =
        normalizeText(order?.id) ||
        normalizeText(order?.order_id) ||
        normalizeText(createData?.order_id) ||
        normalizeText(createData?.data?.order_id);

      const amountInPaise = Number(order?.amount || payableAmountInr * 100);

      if (!razorpayKey) {
        throw new Error("Razorpay key missing hai.");
      }

      if (!orderId) {
        throw new Error("Server se order ID nahi mila.");
      }

      const razorpayResponse = await openRazorpay({
        orderId,
        amountInPaise,
        razorpayKey,
        serverData: createData,
      });

      const verifyPayload = {
        razorpay_order_id: razorpayResponse?.razorpay_order_id || orderId,
        razorpay_payment_id: razorpayResponse?.razorpay_payment_id,
        razorpay_signature: razorpayResponse?.razorpay_signature,
        user_id: userIdNumber || resolvedUserId,
        doctor_id: doctorIdNumber || doctorId,
        clinic_id: clinicId ? clinicIdNumber || clinicId : undefined,
        pet_id: petIdNumber || resolvedPetId,
        order_type: "video_consult",
        call_session_id: normalizeText(resolvedCallSessionId) || undefined,
        channel_name: normalizeText(resolvedChannelName) || undefined,
      };
      const finalVerifyPayload = Object.fromEntries(
        Object.entries(verifyPayload).filter(
          ([, value]) => value !== undefined && value !== null && value !== "",
        ),
      );

      const verifyRes = await fetch(`${apiBase}${endpoints.verifyPayment || ENDPOINTS.verifyPayment}`, {
        method: "POST",
        headers: getAuthHeaders(resolvedToken, {
          Accept: "application/json",
        }),
        body: JSON.stringify(finalVerifyPayload),
      });

      const verifyData = await readApiBody(verifyRes);
      recordApiResponse("verifyPayment", verifyData);
      if (!verifyRes.ok || !verifyData?.success) {
        throw new Error(verifyData?.message || verifyData?.error || "Payment verify nahi hua.");
      }

      const successPayload = {
        doctorName: selectedDoctor.name,
        paymentId: razorpayResponse?.razorpay_payment_id,
        orderId,
        amount: payableAmountInr,
        issue: details.issue,
        responses: {
          reportedSymptom: reportedSymptomData,
          createOrder: createData,
          verifyPayment: verifyData,
        },
      };

      setSuccessState(successPayload);
      setShowPaymentSheet(false);
      setShowDetailsSheet(false);

      if (typeof onSuccess === "function") {
        onSuccess(successPayload);
      }
    } catch (error) {
      setPaymentError(error?.message || "Payment failed. Please try again.");
    } finally {
      setCreateOrderLoading(false);
    }
  };

  const activeDoctorFee = selectedDoctor ? resolveDoctorFee(selectedDoctor, slot) : 0;

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        <div className="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
          <div className="bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-400 px-5 py-6 text-white sm:px-8">
            <div className="max-w-3xl">
              <div className="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">
                CONSULT FLOW
              </div>
              <h1 className="mt-3 text-2xl font-bold sm:text-3xl">Doctor list → Details sheet → Payment flow</h1>
              <p className="mt-2 text-sm text-blue-50 sm:text-base">
                User pehle list dekhega, doctor choose karega, phir Razorpay-style bottom sheet me disease/details fill karega,
                aur uske baad payment sheet se secure payment complete karega.
              </p>
            </div>
          </div>

          <div className="border-b border-slate-100 bg-slate-50 px-5 py-4 sm:px-8">
            <div className="flex flex-wrap items-center gap-2">
              <StatPill>{loadingDoctors ? "Loading doctors..." : `${doctors.length} doctors available`}</StatPill>
              <StatPill>{slot === "day" ? "Day pricing active" : "Night pricing active"}</StatPill>
              <StatPill>Mobile-first bottom-sheet UX</StatPill>
            </div>
          </div>

          <div className="px-5 py-6 sm:px-8">
            {loadingDoctors ? (
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {Array.from({ length: 6 }).map((_, index) => (
                  <div key={index} className="animate-pulse rounded-[28px] border border-slate-200 p-5">
                    <div className="h-16 w-16 rounded-2xl bg-slate-200" />
                    <div className="mt-4 h-4 w-32 rounded bg-slate-200" />
                    <div className="mt-3 h-3 w-48 rounded bg-slate-100" />
                    <div className="mt-2 h-3 w-24 rounded bg-slate-100" />
                    <div className="mt-5 h-10 rounded-2xl bg-slate-200" />
                  </div>
                ))}
              </div>
            ) : listError ? (
              <div className="rounded-[28px] border border-red-200 bg-red-50 p-5 text-red-700">
                <div className="text-base font-semibold">Doctor list load nahi hui</div>
                <div className="mt-1 text-sm">{listError}</div>
              </div>
            ) : doctors.length === 0 ? (
              <div className="rounded-[28px] border border-slate-200 bg-slate-50 p-5 text-slate-600">
                No doctors available at the moment. Please check back later.
              </div>
            ) : (
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {doctors.map((doctor) => {
                  const displayFee = resolveDoctorFee(doctor, slot);
                  return (
                    <div
                      key={doctor.id}
                      className="group rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
                    >
                      <div className="flex items-start gap-4">
                        {doctor.image ? (
                          <img
                            src={doctor.image}
                            alt={doctor.name}
                            className="h-16 w-16 rounded-2xl object-cover"
                          />
                        ) : (
                          <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-lg font-bold text-blue-700">
                            {doctor.name.charAt(0).toUpperCase()}
                          </div>
                        )}

                        <div className="min-w-0 flex-1">
                          <div className="flex items-start justify-between gap-3">
                            <div>
                              <h3 className="line-clamp-1 text-lg font-semibold text-slate-900">{doctor.name}</h3>
                              <p className="mt-1 line-clamp-1 text-sm text-slate-500">{doctor.clinicName || "Clinic"}</p>
                            </div>
                            <span
                              className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                doctor.available
                                  ? "bg-emerald-50 text-emerald-700"
                                  : "bg-slate-100 text-slate-600"
                              }`}
                            >
                              {doctor.available ? "Available" : "Profile Available"}
                            </span>
                          </div>

                          <div className="mt-3 flex flex-wrap gap-2">
                            <StatPill>{doctor.specializations[0] || "General Vet"}</StatPill>
                            {doctor.experience > 0 ? <StatPill>{doctor.experience}+ yrs</StatPill> : null}
                            {doctor.rating > 0 ? <StatPill>{doctor.rating.toFixed(1)} ★</StatPill> : null}
                          </div>
                        </div>
                      </div>

                      <div className="mt-4 rounded-2xl bg-slate-50 p-4">
                        <div className="flex items-center justify-between">
                          <span className="text-sm text-slate-500">Consultation fee</span>
                          <span className="text-lg font-bold text-slate-900">{formatCurrency(displayFee)}</span>
                        </div>
                        <div className="mt-1 text-xs text-slate-500">{doctor.responseTime || "Usually responds quickly"}</div>
                      </div>

                      <button
                        type="button"
                        onClick={() => handleSelectDoctor(doctor)}
                        className="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                      >
                        Choose doctor
                      </button>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {successState ? (
          <div className="mt-6 rounded-[32px] border border-emerald-200 bg-white p-6 shadow-sm">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div className="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                  PAYMENT SUCCESS
                </div>
                <h2 className="mt-3 text-2xl font-bold text-slate-900">Consultation booked successfully</h2>
                <p className="mt-2 text-sm text-slate-600">
                  {successState.doctorName} ke saath booking ho gayi hai. Payment ID: {successState.paymentId || "Generated"}
                </p>
              </div>

              <div className="rounded-[28px] bg-emerald-50 p-4 text-sm text-emerald-900 sm:min-w-[260px]">
                <InfoRow label="Doctor" value={successState.doctorName} />
                <InfoRow label="Amount" value={formatCurrency(successState.amount)} />
                <InfoRow label="Issue" value={successState.issue} />
              </div>
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

      <BottomSheet
        open={showDetailsSheet}
        onClose={() => setShowDetailsSheet(false)}
        title="Tell us the issue"
        subtitle={
          selectedDoctor
            ? `${selectedDoctor.name} • Pay ${formatCurrency(payableAmountInr)}`
            : "Fill details to continue"
        }
      >
        {selectedDoctor ? (
          <div className="space-y-4">
            <div className="rounded-[24px] bg-slate-50 p-4">
              <div className="flex items-center gap-3">
                {selectedDoctor.image ? (
                  <img src={selectedDoctor.image} alt={selectedDoctor.name} className="h-14 w-14 rounded-2xl object-cover" />
                ) : (
                  <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 font-bold text-blue-700">
                    {selectedDoctor.name.charAt(0).toUpperCase()}
                  </div>
                )}
                <div>
                  <div className="text-base font-semibold text-slate-900">{selectedDoctor.name}</div>
                  <div className="text-sm text-slate-500">{selectedDoctor.clinicName}</div>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 gap-4">
              <label className="block">
                <span className="mb-2 block text-sm font-medium text-slate-700">Owner name</span>
                <input
                  className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                  value={details.ownerName}
                  onChange={(e) => handleDetailChange("ownerName", e.target.value)}
                  placeholder="Enter owner name"
                />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-medium text-slate-700">Mobile number</span>
                <input
                  className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                  value={details.phone}
                  onChange={(e) => handleDetailChange("phone", e.target.value)}
                  placeholder="Enter mobile number"
                />
              </label>

              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">Pet name</span>
                  <input
                    className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                    value={details.petName}
                    onChange={(e) => handleDetailChange("petName", e.target.value)}
                    placeholder="Enter pet name"
                  />
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">Pet type</span>
                  <select
                    className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                    value={details.petType}
                    onChange={(e) => handleDetailChange("petType", e.target.value)}
                  >
                    <option value="dog">Dog</option>
                    <option value="cat">Cat</option>
                    <option value="other">Other</option>
                  </select>
                </label>
              </div>

              <label className="block">
                <span className="mb-2 block text-sm font-medium text-slate-700">Pet age</span>
                <input
                  className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                  value={details.petAge}
                  onChange={(e) => handleDetailChange("petAge", e.target.value)}
                  placeholder="Example: 2 years"
                />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-medium text-slate-700">Disease / issue</span>
                <textarea
                  rows={4}
                  className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                  value={details.issue}
                  onChange={(e) => handleDetailChange("issue", e.target.value)}
                  placeholder="Pet ko kya problem hai, clearly likhiye"
                />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-medium text-slate-700">Additional notes</span>
                <textarea
                  rows={3}
                  className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                  value={details.notes}
                  onChange={(e) => handleDetailChange("notes", e.target.value)}
                  placeholder="Optional notes"
                />
              </label>
            </div>

            {paymentError ? (
              <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{paymentError}</div>
            ) : null}

            <div className="sticky bottom-0 bg-white pt-2">
              <button
                type="button"
                onClick={goToPayment}
                className="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700"
              >
                Continue to payment
              </button>
            </div>
          </div>
        ) : null}
      </BottomSheet>

      <BottomSheet
        open={showPaymentSheet}
        onClose={() => setShowPaymentSheet(false)}
        title="Confirm and pay"
        subtitle={selectedDoctor ? `${selectedDoctor.name} • Secure Razorpay payment` : "Payment"}
      >
        {selectedDoctor ? (
          <div className="space-y-4">
            <div className="rounded-[24px] bg-gradient-to-r from-slate-900 to-slate-800 p-4 text-white">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="text-sm text-slate-300">Payable amount</div>
                  <div className="mt-1 text-3xl font-bold">{formatCurrency(payableAmountInr)}</div>
                  <div className="mt-2 text-sm text-slate-300">Doctor: {selectedDoctor.name}</div>
                </div>
                <div className="rounded-2xl bg-white/10 px-3 py-2 text-xs font-semibold text-white/90">
                  {slot === "day" ? "Day slot" : "Night slot"}
                </div>
              </div>
            </div>

            <div className="rounded-[24px] border border-slate-200 p-4">
              <div className="text-sm font-semibold text-slate-900">Booking summary</div>
              <div className="mt-3 divide-y divide-slate-100">
                <InfoRow label="Pet" value={`${details.petName} • ${details.petType}`} />
                <InfoRow label="Issue" value={details.issue} />
                <InfoRow label="Consultation fee" value={formatCurrency(consultationAmount)} />
                <InfoRow label="Service fee" value={formatCurrency(serviceAmount)} />
                <InfoRow label="Discount" value={`- ${formatCurrency(discountAmount)}`} />
                <InfoRow label={`GST (${Math.round(GST_RATE * 100)}%)`} value={formatCurrency(gstAmount)} />
                <InfoRow label="Total" value={formatCurrency(payableAmountInr)} strong />
              </div>
            </div>

            <div className="rounded-[24px] border border-slate-200 p-4">
              <label className="block">
                <span className="mb-2 block text-sm font-medium text-slate-700">Coupon code</span>
                <input
                  className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-blue-500"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                  placeholder="Enter coupon code"
                />
              </label>
              {couponMessage ? <div className="mt-2 text-sm text-emerald-700">{couponMessage}</div> : null}
            </div>

            <div className="rounded-[24px] border border-slate-200 p-4">
              <label className="flex cursor-pointer items-center justify-between gap-3">
                <div>
                  <div className="text-sm font-semibold text-slate-900">Need GST invoice?</div>
                  <div className="text-xs text-slate-500">Enable only if invoice required hai.</div>
                </div>
                <input
                  type="checkbox"
                  checked={gstEnabled}
                  onChange={(e) => setGstEnabled(e.target.checked)}
                  className="h-5 w-5 rounded border-slate-300 text-blue-600"
                />
              </label>

              {gstEnabled ? (
                <div className="mt-4">
                  <input
                    className="w-full rounded-2xl border border-slate-200 px-4 py-3 uppercase outline-none transition focus:border-blue-500"
                    value={gstNumber}
                    onChange={(e) => setGstNumber(e.target.value.toUpperCase().replace(/\s+/g, ""))}
                    placeholder="Enter GST number"
                    maxLength={15}
                  />
                </div>
              ) : null}
            </div>

            {paymentError ? (
              <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{paymentError}</div>
            ) : null}

            {reportedSymptomMessage ? (
              <div className="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                {reportedSymptomMessage}
              </div>
            ) : null}

            {apiResponses.reportedSymptom ||
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

            <div className="flex gap-3 pt-1">
              <button
                type="button"
                onClick={() => {
                  setShowPaymentSheet(false);
                  setShowDetailsSheet(true);
                }}
                className="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                Back
              </button>
              <button
                type="button"
                onClick={handlePayNow}
                disabled={createOrderLoading}
                className="inline-flex flex-[1.4] items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {createOrderLoading ? "Processing..." : `Pay ${formatCurrency(payableAmountInr)}`}
              </button>
            </div>
          </div>
        ) : null}
      </BottomSheet>
    </div>
  );
}
