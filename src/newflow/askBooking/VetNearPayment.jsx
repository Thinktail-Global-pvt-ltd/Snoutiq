import React, { useEffect, useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
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

export default function VetNearPayment() {
  const navigate = useNavigate();
  const location = useLocation();
  const routeState = normalizeState(location.state);
  const storedState = readStandaloneVetNearMeState();
  const initialState = hasRequiredContext(routeState) ? routeState : storedState;
  const [state, setState] = useState(initialState);
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
      navigate(PET_DETAILS_ROUTE, { replace: true });
      return;
    }

    if (state.progress.paymentCompleted || state.booking.paymentStatus === "paid") {
      setPaymentMessage({
        type: "success",
        text: "Payment successful. Your booking is confirmed.",
      });
    }
  }, [navigate, state]);

  const petTypeSummary = state.lead.species === "Other" ? state.pet.otherPetType : state.lead.species;
  const petSummary = [state.pet.petName, petTypeSummary, state.pet.breed].filter(Boolean).join(" / ");
  const bookingReferenceFallback = useMemo(
    () => (state.booking.bookingId ? `SNQ-HS-${state.booking.bookingId}` : "SNQ-HS-PENDING"),
    [state.booking.bookingId]
  );
  const isReady = hasRequiredContext(state);
  const isPaid = state.progress.paymentCompleted || state.booking.paymentStatus === "paid";

  const setStatus = (type, text) => setPaymentMessage({ type, text });

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
  <div className="vet-near-me-page standalone-page">
    <div className="standalone-flow">
      <div className="form-card standalone-form-card">
        <button
          type="button"
          className="step-back"
          onClick={() => navigate(PET_DETAILS_ROUTE)}
        >
          &larr; Back
        </button>

        <h2 style={{ marginBottom: 16 }}>Confirm your booking</h2>

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
