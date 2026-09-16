import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  Home,
  MapPin,
  Calendar,
  Clock,
  CheckCircle,
  X,
  FileText,
  Shield,
  Loader2,
  Navigation,
  Phone,
  HeartHandshake,
} from "lucide-react";
import { readAiAuthState } from "./AiAuth";
import PhoneVerifyGate from "./PhoneVerifyGate";
import { getGoogleCalendarUrl, downloadIcsFile } from "../utils/calendarHelpers";
import { confirmPaymentStart, showBookingError, showBookingWarning } from "./booking/bookingAlerts";
import { fetchPetOverview } from "./petOverviewService";

const API_BASE = "https://snoutiq.com/backend/api";
const BASE_AMOUNT = 999;
const GST_PERCENT = 18;
const GST_AMOUNT = 180;
const TOTAL_AMOUNT = 1179;
const TIME_SLOTS = ["10:00", "12:00", "14:00", "16:00", "18:30", "20:00"];

function normalizeText(value) {
  return String(value ?? "").trim();
}

function normalizePhone(value) {
  const digits = String(value || "").replace(/\D/g, "");
  return digits.slice(-10);
}

function pickFirst(...values) {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    const text = normalizeText(value);
    if (text) return value;
  }
  return "";
}

function stripEmpty(payload) {
  return Object.fromEntries(
    Object.entries(payload).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  );
}

function parsePositiveId(...values) {
  for (const value of values) {
    const parsed = Number.parseInt(normalizeText(value), 10);
    if (Number.isFinite(parsed) && parsed > 0) return parsed;
  }
  return 0;
}

function getHeaders(token, extra = {}) {
  return {
    ...extra,
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
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

function loadRazorpayScript() {
  return new Promise((resolve) => {
    if (window.Razorpay) return resolve(true);
    const existing = document.querySelector('script[data-razorpay="true"]');
    if (existing) {
      existing.addEventListener("load", () => resolve(true), { once: true });
      existing.addEventListener("error", () => resolve(false), { once: true });
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

function formatCurrency(value) {
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function todayIso() {
  return new Date().toISOString().slice(0, 10);
}

function resolveBookingId(payload) {
  return parsePositiveId(
    payload?.id,
    payload?.booking_id,
    payload?.home_service_booking_id,
    payload?.data?.id,
    payload?.data?.booking_id,
    payload?.data?.home_service_booking_id,
    payload?.data?.booking?.id,
    payload?.data?.booking?.booking_id,
    payload?.data?.booking?.home_service_booking_id,
  );
}

export default function HomeVetBookingFlow({
  apiBase = API_BASE,
  onSuccess,
  onClose,
  mode = "page",
  routeStateOverride = null,
}) {
  const location = useLocation();
  const navigate = useNavigate();
  const routeState =
    routeStateOverride && typeof routeStateOverride === "object"
      ? routeStateOverride
      : location?.state && typeof location.state === "object"
        ? location.state
        : {};
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

  const authUser = authState?.user && typeof authState.user === "object" ? authState.user : {};
  const paymentInFlightRef = useRef(false);

  const resolvedUserId = normalizeText(
    pickFirst(routeState.userId, routeState.user_id, authUser.id, authUser.user_id),
  );
  const initialPetId = normalizeText(
    pickFirst(routeState.petId, routeState.pet_id, authUser.pet_id, authUser.pet?.id, authUser.pet?.pet_id),
  );
  const token = normalizeText(pickFirst(routeState.token, authState.token));
  const symptomText = normalizeText(pickFirst(routeState.symptomText, routeState.symptom_text));

  const [fallbackPet, setFallbackPet] = useState(null);
  const effectivePetId = normalizeText(pickFirst(initialPetId, fallbackPet?.id, fallbackPet?.pet_id));
  const [form, setForm] = useState({
    ownerName: normalizeText(pickFirst(authUser.pet_owner_name, authUser.owner_name, authUser.name)),
    phone: normalizePhone(pickFirst(authUser.phone, authUser.mobile, authUser.mobileNumber)),
    email: normalizeText(pickFirst(authUser.email)),
    petName: normalizeText(pickFirst(routeState.petName, routeState.pet_name, authUser.pet_name, authUser.pet?.name, authUser.pet?.pet_name)),
    petType: normalizeText(pickFirst(routeState.petType, routeState.pet_type, authUser.pet_type, authUser.pet?.pet_type)) || "dog",
    address: normalizeText(pickFirst(authUser.address, authUser.location)),
    city: normalizeText(pickFirst(authUser.city)),
    pincode: normalizeText(pickFirst(authUser.pincode)),
    lat: "",
    lng: "",
    dateOfVisit: todayIso(),
    timeOfVisit: TIME_SLOTS[0],
    notes: symptomText,
    consentGiven: false,
  });

  useEffect(() => {
    if (!authUser || Object.keys(authUser).length === 0) return;
    setForm((current) => ({
      ...current,
      ownerName: current.ownerName || normalizeText(pickFirst(authUser.pet_owner_name, authUser.owner_name, authUser.name)),
      phone: current.phone || normalizePhone(pickFirst(authUser.phone, authUser.mobile, authUser.mobileNumber)),
      email: current.email || normalizeText(pickFirst(authUser.email)),
      address: current.address || normalizeText(pickFirst(authUser.address, authUser.location)),
      city: current.city || normalizeText(pickFirst(authUser.city)),
      pincode: current.pincode || normalizeText(pickFirst(authUser.pincode)),
      petName: current.petName || normalizeText(pickFirst(authUser.pet_name, authUser.pet?.name, authUser.pet?.pet_name)),
      petType: current.petType || normalizeText(pickFirst(authUser.pet_type, authUser.pet?.pet_type)) || "dog",
    }));
  }, [authUser]);
  const [locationLoading, setLocationLoading] = useState(false);
  const [paymentLoading, setPaymentLoading] = useState(false);
  const [showPhoneGate, setShowPhoneGate] = useState(false);
  const [showLocationHelpModal, setShowLocationHelpModal] = useState(false);
  const [error, setError] = useState("");
  const [status, setStatus] = useState("");
  const [successState, setSuccessState] = useState(null);
  const [mediaFiles, setMediaFiles] = useState([]);

  useEffect(() => {
    if (!resolvedUserId || effectivePetId) return;
    let active = true;
    async function fetchPets() {
      try {
        const response = await fetch(`${apiBase}/users/${encodeURIComponent(resolvedUserId)}/pets`, {
          headers: getHeaders(token, { Accept: "application/json" }),
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
          petName: current.petName || normalizeText(pickFirst(pet.name, pet.pet_name)),
          petType: current.petType || normalizeText(pickFirst(pet.pet_type, pet.species, pet.type)) || "dog",
        }));
      } catch (_) {
        // Validation handles missing context.
      }
    }
    fetchPets();
    return () => {
      active = false;
    };
  }, [apiBase, effectivePetId, resolvedUserId, token]);

  function updateForm(key, value) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  function validate() {
    if (!resolvedUserId || !effectivePetId) return "User / Pet profile not found. Please log in.";
    if (!normalizeText(form.address)) return "Please enter your home address for the visit.";
    if (!normalizeText(form.dateOfVisit)) return "Visit date is required.";
    if (!normalizeText(form.timeOfVisit)) return "Visit time slot is required.";
    if (!normalizeText(form.notes)) return "Please describe pet symptoms or reason for visit.";
    if (!form.consentGiven) return "Please accept the consent checkbox to continue.";
    return "";
  }

  async function useCurrentLocation() {
    if (!navigator.geolocation || locationLoading) return;
    setLocationLoading(true);
    setError("");

    // Agar permission pehle se hi denied hai, browser popup nahi dikhayega —
    // isliye seedha guide-modal dikha do
    if (navigator.permissions?.query) {
      try {
        const permStatus = await navigator.permissions.query({ name: "geolocation" });
        if (permStatus.state === "denied") {
          setLocationLoading(false);
          setShowLocationHelpModal(true);
          return;
        }
      } catch (_) {
        // Permissions API unsupported ho to normal flow chalne do
      }
    }

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
      updateForm("lat", lat);
      updateForm("lng", lng);

      // Attempt reverse geocoding via OpenStreetMap Nominatim
      try {
        const geoRes = await fetch(
          `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
        );
        if (geoRes.ok) {
          const geoData = await geoRes.json();
          const addr = geoData.address || {};
          const detectedCity = addr.city || addr.town || addr.village || addr.suburb || addr.county || "";
          const detectedPostcode = addr.postcode || "";
          const fullStreet = geoData.display_name || "";

          setForm((prev) => ({
            ...prev,
            lat,
            lng,
            city: detectedCity || prev.city,
            pincode: detectedPostcode || prev.pincode,
            address: fullStreet || prev.address,
          }));
          setStatus("Location fetched successfully.");
        } else {
          setStatus("Current GPS coordinates detected.");
        }
      } catch {
        setStatus("Current GPS coordinates detected.");
      }
    } catch (err) {
      if (err.code === 1) {
        // PERMISSION_DENIED — real prompt reopen nahi ho sakta, guide dikhao
        setShowLocationHelpModal(true);
      } else {
        setStatus("Could not fetch location. Please enter address manually.");
      }
    } finally {
      setLocationLoading(false);
    }
  }



  async function openRazorpay({ key, orderId, amountInPaise }) {
    const loaded = await loadRazorpayScript();
    if (!loaded) throw new Error("Unable to load Razorpay SDK.");

    return new Promise((resolve, reject) => {
      const checkout = new window.Razorpay({
        key,
        amount: String(amountInPaise),
        currency: "INR",
        order_id: orderId,
        name: "SnoutIQ",
        description: `Vet at home visit for ${form.petName || "pet"}`,
        prefill: {
          name: form.ownerName,
          email: form.email,
          contact: form.phone,
        },
        theme: { color: "#2563eb" },
        modal: { ondismiss: () => reject(new Error("Payment cancelled by user.")) },
        handler: resolve,
      });
      checkout.open();
    });
  }

  async function handlePayNow(phoneOverride = null) {
    if (paymentInFlightRef.current) return;
    const effectivePhone = (phoneOverride && normalizePhone(phoneOverride)) || normalizePhone(form.phone) || normalizePhone(authUser.phone) || normalizePhone(authUser.mobile);
    if (!effectivePhone) {
      setShowPhoneGate(true);
      return;
    }
    const validationError = validate();
    if (validationError) {
      setError(validationError);
      void showBookingError(validationError, "Check booking details");
      return;
    }

    const confirmation = await confirmPaymentStart({
      amount: TOTAL_AMOUNT,
      title: "Confirm Home Visit Booking",
      text: `Pay ${formatCurrency(TOTAL_AMOUNT)} (incl. 18% GST) for Vet at Home visit.`,
    });
    if (!confirmation.isConfirmed) return;

    paymentInFlightRef.current = true;
    setPaymentLoading(true);
    setError("");
    setStatus("");
    let paidResponse = null;
    let paidOrderId = "";
    let confirmedBookingId = 0;

    try {
      const fromPetRes = await fetch(`${apiBase}/home-vet-bookings/from-pet`, {
        method: "POST",
        headers: getHeaders(token, { Accept: "application/json", "Content-Type": "application/json" }),
        body: JSON.stringify({
          user_id: resolvedUserId,
          pet_id: effectivePetId,
          date_of_visit: form.dateOfVisit,
          time_of_visit: form.timeOfVisit,
        }),
      });
      const fromPetData = await readApiBody(fromPetRes);
      if (!fromPetRes.ok) throw new Error(fromPetData?.message || "Could not create home vet booking.");
      confirmedBookingId = resolveBookingId(fromPetData);
      if (!confirmedBookingId) throw new Error("Home vet booking id missing.");

      const step2Payload = stripEmpty({
        home_service_booking_id: confirmedBookingId,
        booking_id: confirmedBookingId,
        user_id: resolvedUserId,
        pet_id: effectivePetId,
        parent_name: form.ownerName,
        phone: effectivePhone,
        email: form.email,
        pet_name: form.petName,
        pet_type: form.petType,
        address: form.address,
        city: form.city,
        pincode: form.pincode,
        lat: form.lat,
        lng: form.lng,
        date_of_visit: form.dateOfVisit,
        time_of_visit: form.timeOfVisit,
        notes: form.notes,
      });
      const step2Res = await fetch(`${apiBase}/home-vet-bookings/step-2`, {
        method: "POST",
        headers: getHeaders(token, { Accept: "application/json", "Content-Type": "application/json" }),
        body: JSON.stringify(step2Payload),
      });
      const step2Data = await readApiBody(step2Res);
      if (!step2Res.ok) throw new Error(step2Data?.message || "Could not save home visit details.");
      confirmedBookingId = resolveBookingId(step2Data) || confirmedBookingId;

      const orderPayload = {
        amount: TOTAL_AMOUNT,
        amount_paise: TOTAL_AMOUNT * 100,
        base_amount: BASE_AMOUNT,
        gst_amount: GST_AMOUNT,
        gst_percent: GST_PERCENT,
        home_service_booking_id: confirmedBookingId,
        user_id: resolvedUserId,
        pet_id: effectivePetId,
        order_type: "home_service",
      };
      const orderRes = await fetch(`${apiBase}/create-order`, {
        method: "POST",
        headers: getHeaders(token, { Accept: "application/json", "Content-Type": "application/json" }),
        body: JSON.stringify(orderPayload),
      });
      const orderData = await readApiBody(orderRes);
      if (!orderRes.ok) throw new Error(orderData?.message || orderData?.error || "Could not create payment order.");
      const order = orderData?.order || orderData?.data?.order || orderData?.data || {};
      const key = normalizeText(orderData?.key || orderData?.data?.key);
      const orderId = normalizeText(order?.id || order?.order_id || orderData?.order_id || orderData?.data?.order_id);
      const amountInPaise = Number(order?.amount || TOTAL_AMOUNT * 100);
      if (!key) throw new Error("Payment gateway key missing.");
      if (!orderId) throw new Error("Order ID missing.");

      const razorpayResponse = await openRazorpay({ key, orderId, amountInPaise });
      paidResponse = razorpayResponse;
      paidOrderId = orderId;

      const verifyPayload = {
        razorpay_order_id: razorpayResponse?.razorpay_order_id || orderId,
        razorpay_payment_id: razorpayResponse?.razorpay_payment_id,
        razorpay_signature: razorpayResponse?.razorpay_signature,
        order_type: "home_service",
        home_service_booking_id: confirmedBookingId,
        user_id: resolvedUserId,
        pet_id: effectivePetId,
      };
      const verifyRes = await fetch(`${apiBase}/rzp/verify`, {
        method: "POST",
        headers: getHeaders(token, { Accept: "application/json", "Content-Type": "application/json" }),
        body: JSON.stringify(verifyPayload),
      });
      const verifyData = await readApiBody(verifyRes);
      if (!verifyRes.ok || !verifyData?.success) {
        throw new Error(verifyData?.message || verifyData?.error || "Payment verification failed.");
      }

      const successPayload = {
        bookingType: "home_service",
        bookingId: confirmedBookingId,
        paymentId: razorpayResponse?.razorpay_payment_id,
        amount: TOTAL_AMOUNT,
        date: form.dateOfVisit,
        time: form.timeOfVisit,
        petId: effectivePetId,
        petName: form.petName,
      };
      setSuccessState(successPayload);
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
      } else {
        navigate("/appointment-thank-you", { replace: true, state: successPayload });
      }
    } catch (paymentError) {
      if (paidResponse?.razorpay_payment_id) {
        try {
          window.localStorage.setItem(
            "snoutiq.pendingHomeVetPayment",
            JSON.stringify({
              user_id: resolvedUserId,
              pet_id: effectivePetId,
              home_service_booking_id: confirmedBookingId,
              order_id: paidResponse?.razorpay_order_id || paidOrderId,
              payment_id: paidResponse?.razorpay_payment_id,
              amount: TOTAL_AMOUNT,
              timestamp: new Date().toISOString(),
            }),
          );
        } catch (_) {
          // User-facing error includes payment id.
        }
        const message = `Payment successful, booking verification pending. Payment ID: ${paidResponse.razorpay_payment_id}.`;
        setError(message);
        void showBookingWarning(message);
      } else {
        const message = paymentError?.message || "Payment failed. Please try again.";
        const isCancelled = message.toLowerCase().includes("cancelled") || message.toLowerCase().includes("dismiss");
        if (!isCancelled) {
          setError(message);
          void showBookingError(message);
        } else {
          setError("");
        }
      }
    } finally {
      paymentInFlightRef.current = false;
      setPaymentLoading(false);
    }
  }

  const isModal = mode === "modal";

  return (
    <div
      className={
        isModal
          ? "fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-900/60 p-0 sm:p-4 backdrop-blur-sm animate-[fadeIn_0.2s_ease-out]"
          : "min-h-screen bg-slate-50 pb-20 text-slate-900"
      }
    >
      {isModal ? (
        <button
          type="button"
          className="absolute inset-0 cursor-default bg-transparent"
          aria-label="Close vet at home booking"
          onClick={onClose}
        />
      ) : null}

      <div
        className={
          isModal
            ? "relative max-h-[92vh] sm:max-h-[88vh] lg:max-h-[85vh] w-full max-w-full sm:max-w-2xl lg:max-w-3xl overflow-y-auto rounded-t-3xl sm:rounded-3xl bg-slate-50 p-2.5 sm:p-3.5 text-slate-900 shadow-2xl animate-[scaleIn_0.2s_ease-out]"
            : "mx-auto max-w-4xl px-3 py-4 sm:px-5"
        }
      >
        {/* Compact Header */}
        <div className="mb-2.5 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-xs">
          <div className="flex items-center gap-2">
            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-2 ring-blue-100 shrink-0">
              <Home size={14} />
            </div>
            <div>
              <h1 className="text-xs sm:text-sm font-bold text-slate-900 leading-tight">Book Vet at Home</h1>
              <p className="text-[10px] text-slate-500">Doorstep physical checkup for {form.petName || "pet"}</p>
            </div>
          </div>
          {isModal && (
            <button
              type="button"
              onClick={onClose}
              className="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-200 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors shrink-0"
              aria-label="Close"
            >
              <X size={13} />
            </button>
          )}
        </div>

        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="grid gap-3.5 p-3 sm:p-4 lg:grid-cols-[1.25fr_0.75fr]">
            <div className="space-y-3">
              {/* Pet & Parent Details (Non-editable summary card) */}
              <div className="rounded-xl border border-slate-200/90 bg-slate-50/80 p-2.5 sm:p-3">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Parent & Pet Details
                  </span>
                  <span className="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full flex items-center gap-1">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Verified Profile
                  </span>
                </div>
                <div className="grid grid-cols-2 gap-2">
                  <div className="bg-white p-2 rounded-lg border border-slate-200/60 shadow-2xs">
                    <p className="text-[9px] uppercase font-bold text-slate-400">Pet Parent</p>
                    <p className="font-extrabold text-[#081037] text-xs truncate">
                      {form.ownerName || authUser.pet_owner_name || authUser.owner_name || authUser.name || "Pet Parent"}
                    </p>
                    <p className="text-[10px] text-slate-500 truncate flex items-center gap-1">
                      <Phone size={10} className="text-slate-400 shrink-0" />
                      <span>{form.phone ? `+91 ${form.phone}` : (authUser.phone || authUser.mobile || "N/A")}</span>
                    </p>
                  </div>
                  <div className="bg-white p-2 rounded-lg border border-slate-200/60 shadow-2xs">
                    <p className="text-[9px] uppercase font-bold text-slate-400">Pet Info</p>
                    <p className="font-extrabold text-[#081037] text-xs truncate flex items-center gap-1">
                      <HeartHandshake size={11} className="text-blue-500 shrink-0" />
                      <span>{form.petName || fallbackPet?.name || authUser.pet_name || "Pet"}</span>
                    </p>
                    <p className="text-[10px] text-slate-500 truncate capitalize">
                      {form.petType || "Dog"}{form.email ? ` • ${form.email}` : ""}
                    </p>
                  </div>
                </div>
              </div>

              {/* Visit Address */}
              <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Visit Address
                  </span>
                  <button
                    type="button"
                    onClick={useCurrentLocation}
                    disabled={locationLoading}
                    className="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 hover:bg-blue-100 transition-colors disabled:opacity-60"
                  >
                    {locationLoading ? <Loader2 size={11} className="animate-spin" /> : <MapPin size={11} />}
                    <span>{locationLoading ? "Detecting..." : "Use current GPS"}</span>
                  </button>
                </div>
                <div className="space-y-2">
                  <textarea
                    className="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs sm:text-sm focus:border-blue-500 focus:outline-none"
                    rows={2}
                    value={form.address}
                    onChange={(e) => updateForm("address", e.target.value)}
                    placeholder="House/Flat No., Building, Street, Landmark"
                  />
                  <div className="grid grid-cols-2 gap-2">
                    <input
                      className="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs sm:text-sm focus:border-blue-500 focus:outline-none"
                      value={form.city}
                      onChange={(e) => updateForm("city", e.target.value)}
                      placeholder="City"
                    />
                    <input
                      className="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs sm:text-sm focus:border-blue-500 focus:outline-none"
                      value={form.pincode}
                      maxLength={6}
                      onChange={(e) => updateForm("pincode", e.target.value.replace(/\D/g, "").slice(0, 6))}
                      placeholder="Pincode"
                    />
                  </div>
                </div>
              </div>

              {/* Date & Time Slot */}
              <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                <div className="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                  Date & Slot
                </div>
                <div className="grid gap-2 sm:grid-cols-[1fr_2fr] items-center">
                  <input
                    className="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs sm:text-sm focus:border-blue-500 focus:outline-none"
                    type="date"
                    min={todayIso()}
                    value={form.dateOfVisit}
                    onChange={(e) => updateForm("dateOfVisit", e.target.value)}
                  />
                  <div className="grid grid-cols-3 sm:grid-cols-6 gap-1">
                    {TIME_SLOTS.map((slot) => (
                      <button
                        key={slot}
                        type="button"
                        onClick={() => updateForm("timeOfVisit", slot)}
                        className={`rounded-lg py-1.5 px-1 text-[11px] font-bold transition-all ${
                          form.timeOfVisit === slot
                            ? "bg-blue-600 text-white shadow-xs"
                            : "bg-white border border-slate-200 text-slate-700 hover:border-slate-300"
                        }`}
                      >
                        {slot}
                      </button>
                    ))}
                  </div>
                </div>
              </div>

              {/* Symptoms / Notes & Consent */}
              <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                <div className="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                  Symptoms & Consent
                </div>
                <textarea
                  className="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs sm:text-sm focus:border-blue-500 focus:outline-none"
                  rows={2}
                  value={form.notes}
                  onChange={(e) => updateForm("notes", e.target.value)}
                  placeholder="Describe your pet's symptoms or reason for visit..."
                />
                <label className="mt-2 flex items-start gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={form.consentGiven}
                    onChange={(e) => updateForm("consentGiven", e.target.checked)}
                    className="mt-0.5 h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                  />
                  <span className="text-[10px] sm:text-[11px] text-slate-600 leading-tight">
                    I confirm details are accurate and agree to book this home vet visit.
                  </span>
                </label>
              </div>

              {error && (
                <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700">
                  {error}
                </div>
              )}
              {status && (
                <div className="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700">
                  {status}
                </div>
              )}
              {successState && (
                <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 space-y-2">
                  <div>
                    <h2 className="text-xs font-bold text-emerald-900">Vet at Home Booked Successfully!</h2>
                    <p className="text-[11px] text-emerald-700">A vet will visit on {form.dateOfVisit} at {form.timeOfVisit}.</p>
                    <p className="text-[11px] text-emerald-600">🐾 Our team will assign a verified vet near you — you will receive a confirmation via call or WhatsApp shortly.</p>
                  </div>

                  {/* Add to Calendar */}
                  <div className="flex items-center gap-2 pt-1 border-t border-emerald-200/70">
                    <a
                      href={getGoogleCalendarUrl({
                        title: `Vet at Home - ${form.petName || "Pet"}`,
                        description: `Booking ID: ${successState.bookingId || "N/A"}\nService: Vet at Home\nAddress: ${form.address || "Home Visit"}\nPlatform: SnoutIQ`,
                        location: form.address || "Home Visit",
                        date: form.dateOfVisit,
                        time: form.timeOfVisit,
                      })}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex-1 py-1.5 px-2 bg-white hover:bg-emerald-100/70 text-emerald-900 font-bold text-[11px] rounded-lg transition-all flex items-center justify-center gap-1 border border-emerald-300 shadow-xs"
                    >
                      <Calendar size={12} className="text-emerald-700" />
                      <span>Google Calendar</span>
                    </a>

                    <button
                      type="button"
                      onClick={() =>
                        downloadIcsFile({
                          title: `Vet at Home - ${form.petName || "Pet"}`,
                          description: `Booking ID: ${successState.bookingId || "N/A"}\nService: Vet at Home\nAddress: ${form.address || "Home Visit"}\nPlatform: SnoutIQ`,
                          location: form.address || "Home Visit",
                          date: form.dateOfVisit,
                          time: form.timeOfVisit,
                          filename: `snoutiq-home-vet-${form.dateOfVisit || "booking"}.ics`,
                        })
                      }
                      className="flex-1 py-1.5 px-2 bg-white hover:bg-emerald-100/70 text-emerald-900 font-bold text-[11px] rounded-lg transition-all flex items-center justify-center gap-1 border border-emerald-300 shadow-xs cursor-pointer"
                    >
                      <Calendar size={12} className="text-slate-700" />
                      <span>Apple / iCal</span>
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Payment Summary Sidebar */}
            <aside className="lg:sticky lg:top-2 lg:h-fit">
              <div className="rounded-xl border border-slate-200 bg-slate-50/90 p-3.5 shadow-xs">
                <div className="flex items-center justify-between">
                  <span className="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500">Summary</span>
                  <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                    <Shield size={10} /> Verified
                  </span>
                </div>

                <div className="mt-2.5 rounded-xl bg-slate-900 p-3 text-white">
                  <div className="text-[10px] text-slate-300">Total Payable</div>
                  <div className="text-lg sm:text-xl font-black text-white">{formatCurrency(TOTAL_AMOUNT)}</div>
                  <div className="text-[10px] text-emerald-400">Includes 18% GST</div>
                </div>

                <div className="mt-2.5 divide-y divide-slate-200 text-xs">
                  <div className="flex justify-between py-1.5 text-slate-600">
                    <span>Visit Fee</span>
                    <span className="font-semibold text-slate-800">{formatCurrency(BASE_AMOUNT)}</span>
                  </div>
                  <div className="flex justify-between py-1.5 text-slate-600">
                    <span>GST ({GST_PERCENT}%)</span>
                    <span className="font-semibold text-slate-800">{formatCurrency(GST_AMOUNT)}</span>
                  </div>
                  <div className="flex justify-between py-1.5 font-bold text-slate-900">
                    <span>Total</span>
                    <span>{formatCurrency(TOTAL_AMOUNT)}</span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handlePayNow}
                  disabled={paymentLoading}
                  className="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-600 py-2.5 text-xs sm:text-sm font-bold text-white shadow-xs transition-all hover:bg-blue-700 disabled:opacity-60"
                >
                  {paymentLoading ? (
                    <>
                      <Loader2 size={13} className="animate-spin" />
                      <span>Processing...</span>
                    </>
                  ) : (
                    <span>Pay {formatCurrency(TOTAL_AMOUNT)} & Confirm</span>
                  )}
                </button>

                <p className="mt-2 text-center text-[10px] text-slate-400 flex items-center justify-center gap-1">
                  <Shield size={11} className="text-emerald-500 shrink-0" />
                  <span>100% Secure via Razorpay</span>
                </p>
              </div>
            </aside>
          </div>
        </div>
      </div>

      {showLocationHelpModal && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4">
          <div className="w-full max-w-sm rounded-2xl bg-white p-4 shadow-2xl">
            <div className="mb-2 flex items-center gap-2">
              <MapPin size={16} className="text-blue-600" />
              <h3 className="text-sm font-bold text-slate-900">Location access blocked hai</h3>
            </div>
            <p className="mb-3 text-xs text-slate-600">
              Pehle location deny kiya gaya tha, isliye browser dobara popup nahi dikhata.
              Manually enable karne ke liye:
            </p>
            <ol className="mb-3 list-decimal space-y-1 pl-4 text-xs text-slate-600">
              <li>Address bar mein site name ke paas lock/info icon par click karein</li>
              <li>"Location" permission dhoond kar "Allow" select karein</li>
              <li>Page reload karke "Use current GPS" dobara try karein</li>
            </ol>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => setShowLocationHelpModal(false)}
                className="flex-1 rounded-lg border border-slate-200 py-1.5 text-xs font-semibold text-slate-600"
              >
                Manually Enter
              </button>
              <button
                type="button"
                onClick={() => {
                  setShowLocationHelpModal(false);
                  useCurrentLocation();
                }}
                className="flex-1 rounded-lg bg-blue-600 py-1.5 text-xs font-semibold text-white"
              >
                Try Again
              </button>
            </div>
          </div>
        </div>
      )}

      {showPhoneGate && (

        <PhoneVerifyGate
          onVerified={(verifiedPhone, nextState, petsList) => {
            const cleanPhone = normalizePhone(verifiedPhone);
            setShowPhoneGate(false);
            const userObj = nextState?.user || {};
            const firstPet = (petsList && petsList[0]) || userObj.pet || (userObj.pets && userObj.pets[0]);
            setForm((prev) => ({
              ...prev,
              phone: cleanPhone,
              ownerName: prev.ownerName || normalizeText(userObj.name || userObj.owner_name),
              email: prev.email || normalizeText(userObj.email),
              address: prev.address || normalizeText(userObj.address || userObj.location),
              city: prev.city || normalizeText(userObj.city),
              pincode: prev.pincode || normalizeText(userObj.pincode),
              petName: prev.petName || (firstPet ? normalizeText(firstPet.name || firstPet.pet_name) : prev.petName),
              petType: prev.petType || (firstPet ? normalizeText(firstPet.pet_type || firstPet.species) : prev.petType) || "dog",
            }));
            setTimeout(() => {
              handlePayNow(cleanPhone);
            }, 50);
          }}
          onClose={() => setShowPhoneGate(false)}
        />
      )}
    </div>
  );
}


