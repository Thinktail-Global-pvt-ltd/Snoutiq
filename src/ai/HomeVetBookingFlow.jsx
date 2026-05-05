import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { readAiAuthState } from "./AiAuth";
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
  return String(value || "").replace(/[^\d+]/g, "").trim();
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
  const authState = useMemo(() => readAiAuthState(), []);
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
  const [locationLoading, setLocationLoading] = useState(false);
  const [paymentLoading, setPaymentLoading] = useState(false);
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
    if (!resolvedUserId || !effectivePetId) return "User/Pet context missing hai.";
    if (!normalizeText(form.dateOfVisit)) return "Visit date required hai.";
    if (!normalizeText(form.timeOfVisit)) return "Visit time required hai.";
    if (!normalizeText(form.notes)) return "Please describe symptoms.";
    if (!form.consentGiven) return "Please acknowledge consent before payment.";
    return "";
  }

  async function useCurrentLocation() {
    if (!navigator.geolocation || locationLoading) return;
    setLocationLoading(true);
    setError("");
    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 5 * 60 * 1000,
        });
      });
      updateForm("lat", Number(position.coords.latitude.toFixed(6)));
      updateForm("lng", Number(position.coords.longitude.toFixed(6)));
      setStatus("Current location added.");
    } catch (_) {
      setStatus("Location permission is off. You can enter address manually.");
    } finally {
      setLocationLoading(false);
    }
  }

  async function openRazorpay({ key, orderId, amountInPaise }) {
    const loaded = await loadRazorpayScript();
    if (!loaded) throw new Error("Razorpay SDK load nahi hua.");

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

  async function handlePayNow() {
    if (paymentInFlightRef.current) return;
    const validationError = validate();
    if (validationError) {
      setError(validationError);
      void showBookingError(validationError, "Check booking details");
      return;
    }

    const confirmation = await confirmPaymentStart({
      amount: TOTAL_AMOUNT,
      title: "Continue to payment",
      text: `Pay ${formatCurrency(TOTAL_AMOUNT)} for vet at home visit.`,
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
      if (!fromPetRes.ok) throw new Error(fromPetData?.message || "Home vet booking create nahi hui.");
      confirmedBookingId = resolveBookingId(fromPetData);
      if (!confirmedBookingId) throw new Error("Home vet booking id missing hai.");

      const step2Payload = stripEmpty({
        home_service_booking_id: confirmedBookingId,
        booking_id: confirmedBookingId,
        user_id: resolvedUserId,
        pet_id: effectivePetId,
        parent_name: form.ownerName,
        phone: normalizePhone(form.phone).replace(/\D/g, "").slice(-10),
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
      if (!step2Res.ok) throw new Error(step2Data?.message || "Home vet details save nahi hue.");
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
      if (!orderRes.ok) throw new Error(orderData?.message || orderData?.error || "Order create nahi hua.");
      const order = orderData?.order || orderData?.data?.order || orderData?.data || {};
      const key = normalizeText(orderData?.key || orderData?.data?.key);
      const orderId = normalizeText(order?.id || order?.order_id || orderData?.order_id || orderData?.data?.order_id);
      const amountInPaise = Number(order?.amount || TOTAL_AMOUNT * 100);
      if (!key) throw new Error("Razorpay key missing hai.");
      if (!orderId) throw new Error("Order ID missing hai.");

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
        throw new Error(verifyData?.message || verifyData?.error || "Payment verify nahi hua.");
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
        setError(message);
        void showBookingError(message);
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
          ? "fixed inset-0 z-50 flex items-end justify-center bg-slate-900/45 lg:items-center"
          : "min-h-screen bg-slate-50 pb-28 text-slate-900 lg:pb-0"
      }
    >
      {isModal ? (
        <button
          type="button"
          className="absolute inset-0 cursor-default"
          aria-label="Close vet at home booking"
          onClick={onClose}
        />
      ) : null}

      <div
        className={
          isModal
            ? "relative max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-t-3xl bg-slate-50 px-3 py-3 text-slate-900 shadow-2xl sm:px-5 lg:max-h-[88vh] lg:rounded-3xl lg:px-6"
            : "mx-auto max-w-5xl px-3 py-4 sm:px-5 lg:px-6"
        }
      >
        {isModal ? (
          <div className="mb-3 flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3">
            <div>
              <h1 className="text-lg font-bold text-slate-900">Book Vet at Home</h1>
              {/* <p className="text-sm text-slate-500">SnoutIQ team will assign a vet and confirm your visit.</p> */}
            </div>
            <button
              type="button"
              onClick={onClose}
              className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50"
              aria-label="Close"
            >
              x
            </button>
          </div>
        ) : null}
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div className={isModal ? "hidden" : "border-b border-slate-200 px-4 py-4 sm:px-6"}>
            <h1 className="text-xl font-bold sm:text-2xl">Book Vet at Home</h1>
            <p className="mt-1 text-sm text-slate-500">Vet at home visit for {form.petName || "your pet"}.</p>
          </div>

          <div className="grid gap-4 px-3 py-4 lg:grid-cols-[1.3fr_0.7fr] sm:px-5 lg:px-6">
            <div className="space-y-4">
              <section className="hidden">
                <h2 className="text-base font-semibold">Pet & parent details</h2>
                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                  <input className="rounded-2xl border border-slate-200 px-4 py-3" value={form.ownerName} onChange={(e) => updateForm("ownerName", e.target.value)} placeholder="Owner name" />
                  <input className="rounded-2xl border border-slate-200 px-4 py-3" value={form.phone} onChange={(e) => updateForm("phone", e.target.value)} placeholder="Phone" />
                  <input className="rounded-2xl border border-slate-200 px-4 py-3" value={form.email} onChange={(e) => updateForm("email", e.target.value)} placeholder="Email" />
                  <input className="rounded-2xl border border-slate-200 px-4 py-3" value={form.petName} onChange={(e) => updateForm("petName", e.target.value)} placeholder="Pet name" />
                </div>
              </section>

              <section className="hidden">
                <div className="flex items-center justify-between gap-3">
                  <h2 className="text-base font-semibold">Visit address</h2>
                  <button type="button" onClick={useCurrentLocation} disabled={locationLoading} className="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-60">
                    {locationLoading ? "Adding..." : "Use current location"}
                  </button>
                </div>
                <div className="mt-4 grid gap-3">
                  <textarea className="rounded-2xl border border-slate-200 px-4 py-3" rows={3} value={form.address} onChange={(e) => updateForm("address", e.target.value)} placeholder="Address" />
                  <div className="grid gap-3 sm:grid-cols-2">
                    <input className="rounded-2xl border border-slate-200 px-4 py-3" value={form.city} onChange={(e) => updateForm("city", e.target.value)} placeholder="City" />
                    <input className="rounded-2xl border border-slate-200 px-4 py-3" value={form.pincode} onChange={(e) => updateForm("pincode", e.target.value)} placeholder="Pincode" />
                  </div>
                </div>
              </section>

              <section className="rounded-2xl border border-slate-200 bg-white p-4">
                <h2 className="text-base font-semibold">Date & time slot</h2>
                <input className="mt-4 w-full rounded-2xl border border-slate-200 px-4 py-3" type="date" min={todayIso()} value={form.dateOfVisit} onChange={(e) => updateForm("dateOfVisit", e.target.value)} />
                <div className="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-6">
                  {TIME_SLOTS.map((slot) => (
                    <button key={slot} type="button" onClick={() => updateForm("timeOfVisit", slot)} className={`rounded-2xl border px-3 py-3 text-sm font-semibold ${form.timeOfVisit === slot ? "border-blue-600 bg-blue-600 text-white" : "border-slate-200 bg-white text-slate-700"}`}>
                      {slot}
                    </button>
                  ))}
                </div>
              </section>

              <section className="rounded-2xl border border-slate-200 bg-white p-4">
                <h2 className="text-base font-semibold">Describe symptoms</h2>
                <textarea className="mt-4 w-full rounded-2xl border border-slate-200 px-4 py-3" rows={4} value={form.notes} onChange={(e) => updateForm("notes", e.target.value)} placeholder="Symptoms or notes" />
                <label className="mt-4 block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">Upload image / report</span>
                  <input
                    type="file"
                    accept="image/*,.pdf,.doc,.docx"
                    onChange={(event) => setMediaFiles(Array.from(event.target.files || []))}
                    className="block w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                  />
                  {mediaFiles[0] ? (
                    <div className="mt-2 text-xs font-medium text-slate-500">{mediaFiles[0].name}</div>
                  ) : null}
                </label>
                <label className="mt-4 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <input type="checkbox" checked={form.consentGiven} onChange={(e) => updateForm("consentGiven", e.target.checked)} className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600" />
                  <span className="text-sm text-slate-700">I acknowledge and agree to proceed with this home visit booking.</span>
                </label>
              </section>

              {error ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
              {status ? <div className="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">{status}</div> : null}
              {successState ? (
                <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                  <h2 className="text-xl font-bold">Vet at home booked successfully</h2>
                  {/* <p className="mt-2 text-sm text-emerald-800">SnoutIQ team will assign a vet and confirm your visit.</p> */}
                </div>
              ) : null}
            </div>

            <aside
              className={
                isModal
                  ? "lg:sticky lg:top-6 lg:h-fit"
                  : "fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white p-3 shadow-[0_-12px_30px_rgba(15,23,42,0.12)] lg:sticky lg:inset-auto lg:top-6 lg:h-fit lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none"
              }
            >
              <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="text-sm font-semibold uppercase tracking-wide text-slate-500">Payment summary</div>
                <div className="mt-4 rounded-2xl bg-slate-900 p-4 text-white">
                  <div className="text-sm text-slate-300">Total payable</div>
                  <div className="mt-1 text-3xl font-bold">{formatCurrency(TOTAL_AMOUNT)}</div>
                </div>
                <div className="mt-4 divide-y divide-slate-100 text-sm">
                  <div className="flex justify-between py-2"><span className="text-slate-500">Visit fee</span><span>{formatCurrency(BASE_AMOUNT)}</span></div>
                  <div className="flex justify-between py-2"><span className="text-slate-500">GST ({GST_PERCENT}%)</span><span>{formatCurrency(GST_AMOUNT)}</span></div>
                  <div className="flex justify-between py-2 font-semibold"><span>Total</span><span>{formatCurrency(TOTAL_AMOUNT)}</span></div>
                </div>
                <button type="button" onClick={handlePayNow} disabled={paymentLoading} className="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                  {paymentLoading ? "Processing..." : "Continue to payment"}
                </button>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>
  );
}
