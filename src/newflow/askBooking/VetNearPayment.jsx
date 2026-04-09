import React, { useEffect, useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { ArrowLeft, Lock } from "lucide-react";
import {
  createHomeServiceOrder,
  verifyHomeServicePayment,
} from "../vetNearMeFlow/bookingFlowApi";
import {
  BOOKING_GST_AMOUNT,
  BOOKING_PRICING,
  BOOKING_TOTAL_PRICE,
} from "../vetNearMeFlow/bookingFlowData";
import "../vetNearMeFlow/VetNearMeBooking.css";

const STORAGE_KEY = "snoutiq-vet-near-me-standalone";
const PET_DETAILS_ROUTE = "/vet-near-me-pet-details";
const RAZORPAY_CHECKOUT_SRC = "https://checkout.razorpay.com/v1/checkout.js";
const DEFAULT_STATE = {
  lead: { ownerName: "", phone: "", species: "", area: "", reason: "" },
  pet: {
    petName: "",
    breed: "",
    otherPetType: "",
    dob: "",
    sex: "",
    issue: "",
    symptoms: [],
    vaccinationStatus: "",
    deworming: "",
    history: "",
    medications: "",
    allergies: "",
    notes: "",
  },
  booking: {
    bookingId: null,
    userId: null,
    petId: null,
    latestCompletedStep: 0,
    paymentStatus: "pending",
    paymentReference: "",
    bookingReference: "",
  },
  progress: { petDetailsSubmitted: false, paymentCompleted: false },
};

const pickText = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    const text = String(value).trim();
    if (text) return text;
  }
  return "";
};

const pickNumber = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null || value === "") continue;
    const numberValue = Number(value);
    if (Number.isFinite(numberValue)) return numberValue;
  }
  return null;
};

const normalizeSpecies = (value, otherPetType = "") => {
  const text = String(value || "").trim();
  const lower = text.toLowerCase();
  if (!text) return { species: "", otherPetType };
  if (lower === "dog") return { species: "Dog", otherPetType };
  if (lower === "cat") return { species: "Cat", otherPetType };
  if (lower === "other") return { species: "Other", otherPetType };
  return {
    species: "Other",
    otherPetType: otherPetType || text.charAt(0).toUpperCase() + text.slice(1),
  };
};

const normalizeState = (input) => {
  const source = input && typeof input === "object" ? input : {};
  const prefill = source.prefill && typeof source.prefill === "object" ? source.prefill : {};
  const raw = { ...source, ...prefill };
  const lead = raw.lead && typeof raw.lead === "object" ? raw.lead : {};
  const pet = raw.pet && typeof raw.pet === "object" ? raw.pet : {};
  const booking = raw.booking && typeof raw.booking === "object" ? raw.booking : {};
  const progress = raw.progress && typeof raw.progress === "object" ? raw.progress : {};
  const speciesResult = normalizeSpecies(
    pickText(lead.species, pet.species, pet.type, raw.species, raw.type),
    pickText(pet.otherPetType, raw.otherPetType, raw.exoticType)
  );
  const paymentStatus = pickText(booking.paymentStatus, raw.paymentStatus) || "pending";

  return {
    lead: {
      ownerName: pickText(lead.ownerName, raw.ownerName),
      phone: pickText(lead.phone, raw.phone, raw.ownerMobile),
      species: speciesResult.species,
      area: pickText(lead.area, raw.area, raw.location),
      reason: pickText(lead.reason, raw.reason, raw.problemText),
    },
    pet: {
      petName: pickText(pet.petName, raw.petName, raw.name),
      breed: pickText(pet.breed, raw.breed),
      otherPetType: speciesResult.otherPetType,
      dob: pickText(pet.dob, raw.dob, raw.petDob),
      sex: pickText(pet.sex, raw.sex),
      issue: pickText(pet.issue, raw.issue, raw.problemText),
      symptoms: Array.isArray(pet.symptoms ?? raw.symptoms)
        ? (pet.symptoms ?? raw.symptoms).map((item) => String(item || "").trim()).filter(Boolean)
        : [],
      vaccinationStatus: pickText(pet.vaccinationStatus, raw.vaccinationStatus),
      deworming: pickText(pet.deworming, raw.deworming),
      history: pickText(pet.history, raw.history),
      medications: pickText(pet.medications, raw.medications),
      allergies: pickText(pet.allergies, raw.allergies),
      notes: pickText(pet.notes, raw.notes),
    },
    booking: {
      bookingId: pickNumber(booking.bookingId, raw.bookingId),
      userId: pickNumber(booking.userId, raw.userId),
      petId: pickNumber(booking.petId, raw.petId),
      latestCompletedStep: pickNumber(booking.latestCompletedStep, raw.latestCompletedStep) || 0,
      paymentStatus,
      paymentReference: pickText(booking.paymentReference, raw.paymentReference),
      bookingReference: pickText(booking.bookingReference, raw.bookingReference),
    },
    progress: {
      petDetailsSubmitted: Boolean(progress.petDetailsSubmitted) || Boolean(booking.petId || raw.petId),
      paymentCompleted:
        Boolean(progress.paymentCompleted) || paymentStatus.toLowerCase() === "paid",
    },
  };
};

function readStandaloneVetNearMeState() {
  if (typeof window === "undefined") return DEFAULT_STATE;
  try {
    const raw = window.sessionStorage.getItem(STORAGE_KEY);
    return raw ? normalizeState(JSON.parse(raw)) : DEFAULT_STATE;
  } catch {
    return DEFAULT_STATE;
  }
}

function writeStandaloneVetNearMeState(value) {
  if (typeof window === "undefined") return;
  window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(normalizeState(value)));
}

function clearStandaloneVetNearMeState() {
  if (typeof window === "undefined") return;
  window.sessionStorage.removeItem(STORAGE_KEY);
}

const displayValue = (value, fallback = "-") => {
  const text = String(value || "").trim();
  return text || fallback;
};

const formatCurrency = (value) =>
  Number(value || 0).toLocaleString("en-IN", { maximumFractionDigits: 0 });

const loadRazorpayCheckout = () =>
  new Promise((resolve) => {
    if (typeof window === "undefined") return resolve(false);
    if (window.Razorpay) return resolve(true);

    const done = () => resolve(Boolean(window.Razorpay));
    const failed = () => resolve(false);
    const existing = document.querySelector(`script[src="${RAZORPAY_CHECKOUT_SRC}"]`);

    if (existing) {
      existing.addEventListener("load", done, { once: true });
      existing.addEventListener("error", failed, { once: true });
      return;
    }

    const script = document.createElement("script");
    script.src = RAZORPAY_CHECKOUT_SRC;
    script.async = true;
    script.onload = done;
    script.onerror = failed;
    document.body.appendChild(script);
  });

const hasRequiredContext = (state) => {
  const phoneDigits = String(state?.lead?.phone || "").replace(/\D/g, "");
  return Boolean(
    state?.booking?.bookingId &&
      state?.booking?.userId &&
      state?.booking?.petId &&
      String(state?.lead?.ownerName || "").trim() &&
      phoneDigits.length >= 10 &&
      String(state?.lead?.species || "").trim() &&
      String(state?.pet?.petName || "").trim() &&
      (state?.lead?.species !== "Other" || String(state?.pet?.otherPetType || "").trim())
  );
};

export default function VetNearPayment({ initialState, onBack }) {
  const navigate = useNavigate();
  const location = useLocation();
  const routeState = hasRequiredContext(normalizeState(initialState))
    ? normalizeState(initialState)
    : normalizeState(location.state);
  const storedState = readStandaloneVetNearMeState();
  const resolvedInitialState = hasRequiredContext(routeState) ? routeState : storedState;
  const [state, setState] = useState(resolvedInitialState);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isGatewayLoading, setIsGatewayLoading] = useState(true);
  const [isGatewayReady, setIsGatewayReady] = useState(false);
  const [paymentMessage, setPaymentMessage] = useState({ type: "", text: "" });

  useEffect(() => {
    writeStandaloneVetNearMeState(state);
  }, [state]);

  useEffect(() => {
    let cancelled = false;
    loadRazorpayCheckout().then((ready) => {
      if (cancelled) return;
      setIsGatewayReady(ready);
      setIsGatewayLoading(false);
      if (!ready) {
        setPaymentMessage({
          type: "error",
          text: "Secure payment could not be loaded. Refresh and try again.",
        });
      }
    });
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (!hasRequiredContext(state)) {
      clearStandaloneVetNearMeState();
      if (onBack) {
        onBack();
        return;
      }
      navigate(PET_DETAILS_ROUTE, { replace: true });
      return;
    }

    if (state.progress.paymentCompleted || state.booking.paymentStatus === "paid") {
      setPaymentMessage({
        type: "success",
        text: "Payment successful. Your booking is confirmed.",
      });
    }
  }, [navigate, onBack, state]);

  const petTypeSummary = state.lead.species === "Other" ? state.pet.otherPetType : state.lead.species;
  const petSummary = [state.pet.petName, petTypeSummary, state.pet.breed].filter(Boolean).join(" / ");
  const bookingReferenceFallback = useMemo(
    () => (state.booking.bookingId ? `SNQ-HS-${state.booking.bookingId}` : "SNQ-HS-PENDING"),
    [state.booking.bookingId]
  );
  const isReady = hasRequiredContext(state);
  const isPaid = state.progress.paymentCompleted || state.booking.paymentStatus === "paid";
  const surfaceCardClassName =
    "rounded-[24px] border border-[#dbe7ff] bg-white p-5 shadow-[0_10px_30px_-24px_rgba(37,99,235,0.25)]";
  const summaryRows = [
    { label: "Pet", value: displayValue(petSummary, "Not selected") },
    { label: "Parent name", value: displayValue(state.lead.ownerName) },
    { label: "Phone", value: displayValue(state.lead.phone) },
    { label: "Location", value: displayValue(state.lead.area, "Not selected") },
    { label: "Reason", value: displayValue(state.lead.reason, "Not selected") },
  ];
  const amountRows = [
    {
      label: "Home vet visit",
      value: `Rs ${formatCurrency(BOOKING_PRICING.originalPrice)}`,
    },
    {
      label: "Discount",
      value: `-Rs ${formatCurrency(BOOKING_PRICING.discountAmount)}`,
      tone: "success",
    },
    {
      label: "After discount",
      value: `Rs ${formatCurrency(BOOKING_PRICING.currentPrice)}`,
    },
    {
      label: `${BOOKING_PRICING.gstRate}% GST`,
      value: `Rs ${formatCurrency(BOOKING_GST_AMOUNT)}`,
    },
  ];
  const paymentDetailRows = [
    { label: "Payment status", value: displayValue(state.booking.paymentStatus, "paid") },
    { label: "Payment reference", value: displayValue(state.booking.paymentReference) },
    {
      label: "Booking reference",
      value: displayValue(state.booking.bookingReference || bookingReferenceFallback),
    },
  ];
  const paymentStatusClassName =
    paymentMessage.type === "success"
      ? "border-emerald-200 bg-emerald-50 text-emerald-700"
      : paymentMessage.type === "error"
        ? "border-red-200 bg-red-50 text-red-700"
        : "border-blue-200 bg-blue-50 text-blue-700";

  const setStatus = (type, text) => setPaymentMessage({ type, text });

  const handleBack = () => {
    if (onBack) {
      onBack();
      return;
    }
    navigate(PET_DETAILS_ROUTE);
  };

  const handlePayment = async () => {
    if (isSubmitting || isPaid) return;
    if (!isReady) {
      setStatus("error", "Booking session is incomplete. Please go back and submit pet details again.");
      return;
    }
    if (isGatewayLoading) {
      setStatus("info", "Preparing secure payment...");
      return;
    }
    if (!isGatewayReady || typeof window === "undefined" || !window.Razorpay) {
      setStatus("error", "Secure payment is unavailable right now. Please refresh and try again.");
      return;
    }

    setIsSubmitting(true);
    setStatus("info", "Creating secure Razorpay order...");

    try {
      const order = await createHomeServiceOrder({
        bookingId: state.booking.bookingId,
        userId: state.booking.userId,
        petId: state.booking.petId,
        amount: BOOKING_TOTAL_PRICE,
      });
      if (!order.ok || !order.orderId || !order.key) {
        throw new Error(order.error || "Order could not be created.");
      }

      const checkout = new window.Razorpay({
        key: order.key,
        amount: order.amountPaise,
        currency: order.currency || "INR",
        order_id: order.orderId,
        name: "Snoutiq",
        description: "Vet near you booking",
        prefill: {
          name: state.lead.ownerName,
          contact: state.lead.phone,
        },
        notes: {
          home_service_booking_id: String(state.booking.bookingId),
          user_id: String(state.booking.userId),
          pet_id: String(state.booking.petId),
          order_type: "home_service",
        },
        theme: { color: "#1447e6" },
        modal: {
          ondismiss: () => {
            setStatus("error", "Payment cancelled. You can retry anytime.");
            setIsSubmitting(false);
          },
        },
        handler: async (paymentResponse) => {
          setStatus("info", "Verifying payment...");
          try {
            const verify = await verifyHomeServicePayment({
              bookingId: state.booking.bookingId,
              userId: state.booking.userId,
              petId: state.booking.petId,
              razorpayOrderId: paymentResponse?.razorpay_order_id,
              razorpayPaymentId: paymentResponse?.razorpay_payment_id,
              razorpaySignature: paymentResponse?.razorpay_signature,
            });
            if (!verify.ok) {
              throw new Error(verify.error || "Payment verification failed.");
            }

            const nextState = normalizeState({
              ...state,
              booking: {
                ...state.booking,
                bookingId: verify.bookingId ?? state.booking.bookingId,
                userId: verify.userId ?? state.booking.userId,
                petId: verify.petId ?? state.booking.petId,
                latestCompletedStep: verify.latestCompletedStep ?? 3,
                paymentStatus: verify.paymentStatus || "paid",
                paymentReference:
                  verify.paymentReference || paymentResponse?.razorpay_payment_id || "",
                bookingReference:
                  verify.bookingReference || state.booking.bookingReference || bookingReferenceFallback,
              },
              progress: {
                ...state.progress,
                petDetailsSubmitted: true,
                paymentCompleted: true,
              },
            });

            setState(nextState);
            writeStandaloneVetNearMeState(nextState);
            setStatus("success", "Payment successful. Your booking is confirmed.");
            setIsSubmitting(false);
          } catch (error) {
            setStatus("error", error?.message || "Payment verification failed. Please try again.");
            setIsSubmitting(false);
          }
        },
      });

      checkout.on("payment.failed", (event) => {
        const description =
          event?.error?.description || event?.error?.reason || "Payment failed. Please try again.";
        setStatus("error", description);
        setIsSubmitting(false);
      });

      checkout.open();
      setStatus("info", "Opening secure Razorpay checkout...");
    } catch (error) {
      setStatus("error", error?.message || "Payment could not be started. Please try again.");
      setIsSubmitting(false);
    }
  };

  if (!isReady) return null;

  return (
    <div className="min-h-screen bg-[#f8fbff] text-slate-900">
      <div className="sticky top-0 z-30 border-b border-[#e3ecff] bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-lg items-center gap-3 px-4 py-3">
          <button
            type="button"
            onClick={handleBack}
            className="flex h-10 w-10 items-center justify-center rounded-full border border-[#dbe7ff] bg-white text-slate-600 transition hover:bg-[#f8fbff]"
            aria-label="Go back"
          >
            <ArrowLeft size={18} />
          </button>
          <div className="min-w-0 flex-1">
            <div className="text-base font-semibold text-slate-900">
              Complete payment
            </div>
            <div className="text-xs text-slate-500">Secure booking checkout</div>
          </div>
        </div>
      </div>

      <div className="px-4 pb-36 pt-4 md:pb-40 md:pt-6">
        <div className="mx-auto max-w-lg space-y-4">
          <div className={surfaceCardClassName}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <div className="text-sm font-semibold text-slate-900">
                  Booking summary
                </div>
                <p className="mt-1 text-xs text-slate-500">
                  Review details before payment.
                </p>
              </div>
              <div
                className={`rounded-full px-3 py-1 text-[11px] font-semibold ${
                  isPaid
                    ? "bg-emerald-50 text-emerald-700"
                    : "bg-[#eff5ff] text-[#2457ff]"
                }`}
              >
                {isPaid ? "Paid" : "Pending"}
              </div>
            </div>

            <div className="mt-4 space-y-3 text-sm">
              {summaryRows.map((item) => (
                <div
                  key={item.label}
                  className="flex items-start justify-between gap-4 border-b border-[#e8efff] pb-3 last:border-b-0 last:pb-0"
                >
                  <span className="text-slate-500">{item.label}</span>
                  <span className="max-w-[62%] text-right font-medium text-slate-900">
                    {item.value}
                  </span>
                </div>
              ))}
            </div>
          </div>

          <div className={surfaceCardClassName}>
            <div className="text-sm font-semibold text-slate-900">
              Amount details
            </div>

            <div className="mt-4 space-y-3 text-sm">
              {amountRows.map((item) => (
                <div
                  key={item.label}
                  className={`flex items-center justify-between gap-4 ${
                    item.tone === "success" ? "text-emerald-700" : "text-slate-600"
                  }`}
                >
                  <span>{item.label}</span>
                  <span className="font-medium text-slate-900">{item.value}</span>
                </div>
              ))}

              <div className="flex items-center justify-between gap-4 border-t border-[#e8efff] pt-4">
                <span className="font-semibold text-slate-900">Total payable</span>
                <span className="text-xl font-semibold text-slate-900">
                  Rs {formatCurrency(BOOKING_TOTAL_PRICE)}
                </span>
              </div>
            </div>
          </div>

          {paymentMessage.text ? (
            <div
              className={`rounded-[20px] border px-4 py-3 text-sm font-medium ${paymentStatusClassName}`}
            >
              {paymentMessage.text}
            </div>
          ) : null}

          {isPaid ? (
            <div className={surfaceCardClassName}>
              <div className="text-sm font-semibold text-slate-900">
                Payment details
              </div>
              <div className="mt-4 space-y-3 text-sm">
                {paymentDetailRows.map((item) => (
                  <div
                    key={item.label}
                    className="flex items-start justify-between gap-4 border-b border-[#e8efff] pb-3 last:border-b-0 last:pb-0"
                  >
                    <span className="text-slate-500">{item.label}</span>
                    <span className="max-w-[62%] text-right font-medium text-slate-900">
                      {item.value}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          ) : null}
        </div>
      </div>

      <div className="fixed inset-x-0 bottom-0 z-30 border-t border-[#dbe7ff] bg-white/95 backdrop-blur">
        <div className="mx-auto w-full max-w-lg px-4 pb-[calc(16px+env(safe-area-inset-bottom))] pt-3">
          <div className="mb-3 flex items-center justify-between gap-4">
            <div>
              <p className="text-[11px] font-medium text-slate-500">
                Total payable
              </p>
              <div className="text-lg font-bold text-slate-900">
                Rs {formatCurrency(BOOKING_TOTAL_PRICE)}
              </div>
            </div>
            <div className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
              <Lock size={12} />
              Secure
            </div>
          </div>

          <button
            type="button"
            onClick={handlePayment}
            disabled={isSubmitting || isGatewayLoading || !isReady || isPaid}
            className={`inline-flex w-full items-center justify-center rounded-2xl px-4 py-3.5 text-sm font-semibold transition ${
              isSubmitting || isGatewayLoading || !isReady || isPaid
                ? "cursor-not-allowed bg-slate-300 text-white opacity-60"
                : "bg-[#2563eb] text-white shadow-[0_18px_36px_-22px_rgba(37,99,235,0.7)] hover:bg-[#1d4ed8]"
            }`}
          >
            {isPaid
              ? "Payment completed"
              : isSubmitting
                ? "Opening payment..."
                : isGatewayLoading
                  ? "Preparing payment..."
                  : `Pay Rs ${formatCurrency(BOOKING_TOTAL_PRICE)}`}
          </button>
        </div>
      </div>
    </div>
  );
}
