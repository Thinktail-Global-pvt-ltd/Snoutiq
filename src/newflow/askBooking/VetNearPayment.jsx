import React, { useEffect, useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  BadgeCheck,
  CreditCard,
  Lock,
  PawPrint,
  Phone,
  ReceiptText,
  ShieldCheck,
  Sparkles,
} from "lucide-react";
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
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.16),_transparent_24%),linear-gradient(180deg,#f7faff_0%,#edf4ff_46%,#f5f8ff_100%)] pb-20 text-slate-900">
      <div className="sticky top-0 z-30 border-b border-white/70 bg-white/88 backdrop-blur-xl">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 md:px-6">
          <button
            type="button"
            onClick={handleBack}
            className="inline-flex items-center gap-2 rounded-full border border-[#d7e3ff] bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-[#b9cfff] hover:text-[#2457ff]"
          >
            <ArrowLeft size={16} />
            Back
          </button>
          <div className="inline-flex items-center gap-2 rounded-full border border-[#d7e3ff] bg-[#f6f9ff] px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#2457ff]">
            <Lock size={13} />
            Powered by Razorpay
          </div>
        </div>
      </div>

      <div className="mx-auto max-w-5xl px-4 py-6 md:px-6 md:py-8">
        <div className="overflow-hidden rounded-[32px] bg-[linear-gradient(135deg,#0f172a_0%,#2457ff_58%,#5b8cff_100%)] p-6 text-white shadow-[0_28px_80px_-36px_rgba(37,99,235,0.75)] md:p-8">
          <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-2xl">
              <div className="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/90">
                <Sparkles size={13} />
                Step 2 of 2
              </div>
              <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                Secure checkout for your booking
              </h1>
              <p className="mt-3 max-w-xl text-sm leading-6 text-white/78 md:text-[15px]">
                Review your saved booking details and complete payment securely through Razorpay.
              </p>
            </div>
            <div className="grid gap-3 sm:grid-cols-3">
              <div className="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                <div className="flex items-center gap-2 text-xs font-medium text-white/70">
                  <Phone size={14} className="text-emerald-300" />
                  Contact
                </div>
                <div className="mt-2 text-sm font-semibold">{displayValue(state.lead.phone)}</div>
              </div>
              <div className="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                <div className="flex items-center gap-2 text-xs font-medium text-white/70">
                  <PawPrint size={14} className="text-emerald-300" />
                  Pet
                </div>
                <div className="mt-2 text-sm font-semibold">{displayValue(state.pet.petName)}</div>
              </div>
              <div className="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                <div className="flex items-center gap-2 text-xs font-medium text-white/70">
                  <CreditCard size={14} className="text-emerald-300" />
                  Total
                </div>
                <div className="mt-2 text-lg font-semibold">Rs {formatCurrency(BOOKING_TOTAL_PRICE)}</div>
              </div>
            </div>
          </div>
        </div>

        <div
          className="mt-6 rounded-[30px] border border-[#d6e3ff] bg-white/95 p-5 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)] md:p-7
            [&_.step-back]:hidden
            [&_h2]:text-[28px] [&_h2]:font-semibold [&_h2]:tracking-tight [&_h2]:text-slate-950
            [&_.summary-card]:mt-6 [&_.summary-card]:rounded-[24px] [&_.summary-card]:border [&_.summary-card]:border-[#d6e3ff] [&_.summary-card]:bg-[linear-gradient(180deg,#fbfdff_0%,#f6f9ff_100%)] [&_.summary-card]:p-4
            [&_.sum-row]:flex [&_.sum-row]:items-start [&_.sum-row]:justify-between [&_.sum-row]:gap-4 [&_.sum-row]:border-b [&_.sum-row]:border-[#e8efff] [&_.sum-row]:py-3 [&_.sum-row:last-child]:border-b-0 [&_.sum-row:last-child]:pb-0 [&_.sum-row:first-child]:pt-0
            [&_.sum-label]:text-[11px] [&_.sum-label]:font-semibold [&_.sum-label]:uppercase [&_.sum-label]:tracking-[0.16em] [&_.sum-label]:text-slate-500
            [&_.sum-val]:max-w-[58%] [&_.sum-val]:text-right [&_.sum-val]:text-sm [&_.sum-val]:font-semibold [&_.sum-val]:text-slate-900
            [&_.pay-box]:mt-6 [&_.pay-box]:overflow-hidden [&_.pay-box]:rounded-[28px] [&_.pay-box]:border [&_.pay-box]:border-[#c8d9ff] [&_.pay-box]:bg-[#0f172a] [&_.pay-box]:p-5 [&_.pay-box]:text-white
            [&_.pay-line]:flex [&_.pay-line]:items-center [&_.pay-line]:justify-between [&_.pay-line]:gap-4 [&_.pay-line]:border-b [&_.pay-line]:border-white/10 [&_.pay-line]:py-3 [&_.pay-line]:text-sm [&_.pay-line:last-of-type]:border-b-0
            [&_.pay-line.discount]:text-emerald-300 [&_.pay-line.discount]:font-semibold
            [&_.pay-line.total]:mt-1 [&_.pay-line.total]:border-t [&_.pay-line.total]:border-white/15 [&_.pay-line.total]:pt-4 [&_.pay-line.total]:text-base [&_.pay-line.total]:font-semibold
            [&_.pay-includes]:mt-4 [&_.pay-includes]:rounded-[22px] [&_.pay-includes]:bg-white/10 [&_.pay-includes]:p-4 [&_.pay-includes]:text-sm [&_.pay-includes]:leading-6 [&_.pay-includes]:text-white/72
            [&_.refund-note]:mt-5 [&_.refund-note]:rounded-[22px] [&_.refund-note]:border [&_.refund-note]:border-emerald-200 [&_.refund-note]:bg-emerald-50 [&_.refund-note]:p-4 [&_.refund-note]:text-sm [&_.refund-note]:leading-6 [&_.refund-note]:text-emerald-800
            [&_.pay-status]:mt-5 [&_.pay-status]:rounded-[22px] [&_.pay-status]:border [&_.pay-status]:p-4 [&_.pay-status]:text-sm [&_.pay-status]:font-medium
            [&_.pay-status.info]:border-blue-200 [&_.pay-status.info]:bg-blue-50 [&_.pay-status.info]:text-blue-700
            [&_.pay-status.success]:border-emerald-200 [&_.pay-status.success]:bg-emerald-50 [&_.pay-status.success]:text-emerald-700
            [&_.pay-status.error]:border-red-200 [&_.pay-status.error]:bg-red-50 [&_.pay-status.error]:text-red-700
            [&_.cta]:mt-7 [&_.cta]:inline-flex [&_.cta]:w-full [&_.cta]:items-center [&_.cta]:justify-center [&_.cta]:rounded-2xl [&_.cta]:bg-[linear-gradient(135deg,#2457ff_0%,#1d4ed8_100%)] [&_.cta]:px-4 [&_.cta]:py-4 [&_.cta]:text-sm [&_.cta]:font-semibold [&_.cta]:text-white [&_.cta]:shadow-[0_18px_35px_-18px_rgba(37,99,235,0.75)] hover:[&_.cta]:translate-y-[-1px] disabled:[&_.cta]:cursor-not-allowed disabled:[&_.cta]:opacity-60
            [&_.cta-note]:mt-3 [&_.cta-note]:text-center [&_.cta-note]:text-xs [&_.cta-note]:text-slate-500"
        >
          <div className="mb-6 grid gap-4 rounded-[28px] border border-[#d6e3ff] bg-[linear-gradient(180deg,#fbfdff_0%,#f5f9ff_100%)] p-4 md:grid-cols-[1.1fr_0.9fr] md:p-5">
            <div>
              <div className="inline-flex items-center gap-2 rounded-full border border-[#d7e3ff] bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#2457ff]">
                <ReceiptText size={13} />
                Booking summary
              </div>
              <h2 className="mt-4 !mb-0">Confirm your booking</h2>
              <p className="mt-3 text-sm leading-6 text-slate-500">
                Your pet details are already saved. Review the summary below and open the secure checkout.
              </p>
            </div>
            <div className="rounded-[24px] bg-[#0f172a] p-4 text-white">
              <div className="flex items-center justify-between text-xs uppercase tracking-[0.16em] text-white/65">
                <span>Payment status</span>
                <span>{isPaid ? "Paid" : "Pending"}</span>
              </div>
              <div className="mt-4 space-y-3 text-sm">
                <div className="flex items-center justify-between">
                  <span className="text-white/70">Owner</span>
                  <span>{displayValue(state.lead.ownerName)}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-white/70">Area</span>
                  <span>{displayValue(state.lead.area, "Not selected")}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-white/70">Reference</span>
                  <span>{displayValue(state.booking.bookingReference || bookingReferenceFallback)}</span>
                </div>
              </div>
              <div className="mt-5 flex items-end justify-between">
                <div>
                  <div className="text-[11px] uppercase tracking-[0.16em] text-white/60">
                    Amount payable
                  </div>
                  <div className="mt-1 text-2xl font-semibold">Rs {formatCurrency(BOOKING_TOTAL_PRICE)}</div>
                </div>
                <div className="rounded-2xl bg-white/10 p-3 text-white/90">
                  <ShieldCheck size={18} />
                </div>
              </div>
            </div>
          </div>

          <div className="mb-5 flex items-center gap-3 rounded-[22px] border border-[#d7e3ff] bg-[#f6f9ff] px-4 py-3 text-sm text-slate-600">
            <BadgeCheck size={18} className="shrink-0 text-[#2457ff]" />
            Secure payment via Razorpay with UPI, cards and net banking.
          </div>

          <h2 style={{ marginBottom: 0 }}>Confirm your booking</h2>

          <div className="summary-card">
            <div className="sum-row">
              <span className="sum-label">Name</span>
              <span className="sum-val">
                {displayValue(state.lead.ownerName)}
              </span>
            </div>
            <div className="sum-row">
              <span className="sum-label">Phone</span>
              <span className="sum-val">
                {displayValue(state.lead.phone)}
              </span>
            </div>
            <div className="sum-row">
              <span className="sum-label">Area</span>
              <span className="sum-val">
                {displayValue(state.lead.area, "Not selected")}
              </span>
            </div>
            <div className="sum-row">
              <span className="sum-label">Pet</span>
              <span className="sum-val">
                {displayValue(petSummary, "Not selected")}
              </span>
            </div>
            <div className="sum-row">
              <span className="sum-label">Reason</span>
              <span className="sum-val">
                {displayValue(state.lead.reason, "Not selected")}
              </span>
            </div>
          </div>

          <div className="pay-box">
            <div className="pay-line">
              <span>Home vet visit in Gurgaon</span>
              <span>Rs {formatCurrency(BOOKING_PRICING.originalPrice)}</span>
            </div>
            <div className="pay-line discount">
              <span>20% off - limited period</span>
              <span>-Rs {formatCurrency(BOOKING_PRICING.discountAmount)}</span>
            </div>
            <div className="pay-line">
              <span>Amount after discount</span>
              <span>Rs {formatCurrency(BOOKING_PRICING.currentPrice)}</span>
            </div>
            <div className="pay-line">
              <span>{BOOKING_PRICING.gstRate}% GST</span>
              <span>+Rs {formatCurrency(BOOKING_GST_AMOUNT)}</span>
            </div>
            <div className="pay-line total">
              <span>Total payable</span>
              <span>Rs {formatCurrency(BOOKING_TOTAL_PRICE)}</span>
            </div>
            <div className="pay-includes">
              Includes up to Rs 200 of essential medicines / Written visit report /
              Pet record saved on Snoutiq
            </div>
          </div>

          <div className="refund-note">
            Secure booking with 100% refund if we cannot confirm a vet in your
            Gurgaon area after payment.
          </div>

          {paymentMessage.text ? (
            <div className={`pay-status ${paymentMessage.type || "info"}`}>
              {paymentMessage.text}
            </div>
          ) : null}

          {isPaid ? (
            <div className="summary-card" style={{ marginTop: 12 }}>
              <div className="sum-row">
                <span className="sum-label">Payment status</span>
                <span className="sum-val">
                  {displayValue(state.booking.paymentStatus, "paid")}
                </span>
              </div>
              <div className="sum-row">
                <span className="sum-label">Payment reference</span>
                <span className="sum-val">
                  {displayValue(state.booking.paymentReference)}
                </span>
              </div>
              <div className="sum-row">
                <span className="sum-label">Booking reference</span>
                <span className="sum-val">
                  {displayValue(
                    state.booking.bookingReference || bookingReferenceFallback
                  )}
                </span>
              </div>
            </div>
          ) : null}

          <button
            type="button"
            className="cta pay-cta"
            onClick={handlePayment}
            disabled={isSubmitting || isGatewayLoading || !isReady || isPaid}
          >
            {isPaid
              ? "Payment completed"
              : isSubmitting
                ? "Opening payment..."
                : isGatewayLoading
                  ? "Preparing payment..."
                  : `Pay Rs ${formatCurrency(BOOKING_TOTAL_PRICE)} securely ->`}
          </button>

          <p className="cta-note">
            Secure payment via Razorpay / UPI / card / net banking
          </p>
        </div>
      </div>
    </div>
  );
}
