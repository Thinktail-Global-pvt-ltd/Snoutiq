import React, { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  createHomeServiceOrder,
  verifyHomeServicePayment,
} from "./bookingFlowApi";
import {
  BOOKING_FLOW_ROUTES,
  BOOKING_GST_AMOUNT,
  BOOKING_PRICING,
  BOOKING_TOTAL_PRICE,
} from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

const RAZORPAY_CHECKOUT_SRC = "https://checkout.razorpay.com/v1/checkout.js";

const displayValue = (value, fallback = "-") => {
  const text = String(value || "").trim();
  return text || fallback;
};

const formatCurrency = (value) =>
  Number(value || 0).toLocaleString("en-IN", {
    maximumFractionDigits: 0,
  });

const loadRazorpayCheckout = () =>
  new Promise((resolve) => {
    if (typeof window === "undefined") {
      resolve(false);
      return;
    }

    if (window.Razorpay) {
      resolve(true);
      return;
    }

    const handleLoad = () => resolve(Boolean(window.Razorpay));
    const handleError = () => resolve(false);
    const existingScript = document.querySelector(
      `script[src="${RAZORPAY_CHECKOUT_SRC}"]`
    );

    if (existingScript) {
      existingScript.addEventListener("load", handleLoad, { once: true });
      existingScript.addEventListener("error", handleError, { once: true });
      return;
    }

    const script = document.createElement("script");
    script.src = RAZORPAY_CHECKOUT_SRC;
    script.async = true;
    script.onload = handleLoad;
    script.onerror = handleError;
    document.body.appendChild(script);
  });

export default function VetNearMePaymentPage() {
  const navigate = useNavigate();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isGatewayLoading, setIsGatewayLoading] = useState(true);
  const [isGatewayReady, setIsGatewayReady] = useState(false);
  const [paymentMessage, setPaymentMessage] = useState({
    type: "",
    text: "",
  });
  const { bookingState, updateBooking, updateProgress } = useVetNearMeBooking();

  useEffect(() => {
    if (!bookingState.progress.leadSubmitted) {
      navigate(BOOKING_FLOW_ROUTES.lead, { replace: true });
      return;
    }

    if (
      !bookingState.progress.petDetailsSubmitted ||
      !bookingState.booking.bookingId ||
      !bookingState.booking.userId
    ) {
      navigate(BOOKING_FLOW_ROUTES.petDetails, { replace: true });
      return;
    }

    if (!bookingState.booking.petId) {
      navigate(BOOKING_FLOW_ROUTES.petDetails, { replace: true });
    }
  }, [
    bookingState.progress.leadSubmitted,
    bookingState.progress.petDetailsSubmitted,
    bookingState.booking.bookingId,
    bookingState.booking.userId,
    bookingState.booking.petId,
    navigate,
  ]);

  useEffect(() => {
    let isCancelled = false;

    loadRazorpayCheckout().then((ready) => {
      if (isCancelled) return;

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
      isCancelled = true;
    };
  }, []);

  const setStatus = (type, text) => {
    setPaymentMessage({ type, text });
  };

  const petTypeSummary =
    bookingState.lead.species === "Other"
      ? bookingState.pet.otherPetType.trim()
      : bookingState.lead.species.trim();

  const petSummary = [bookingState.pet.petName.trim(), petTypeSummary]
    .filter(Boolean)
    .join(" · ");

  const bookingReferenceFallback = useMemo(() => {
    if (!bookingState.booking.bookingId) return "SNQ-XXXXX";
    return `SNQ-HS-${bookingState.booking.bookingId}`;
  }, [bookingState.booking.bookingId]);

  const isPaymentContextReady = Boolean(
    bookingState.booking.bookingId &&
      bookingState.booking.userId &&
      bookingState.booking.petId
  );

  const handlePayment = async () => {
    if (isSubmitting) return;

    if (!isPaymentContextReady) {
      setStatus(
        "error",
        "Booking session is incomplete. Please go back and submit pet details again."
      );
      return;
    }

    if (isGatewayLoading) {
      setStatus("info", "Preparing secure payment...");
      return;
    }

    if (!isGatewayReady || typeof window === "undefined" || !window.Razorpay) {
      setStatus(
        "error",
        "Secure payment is unavailable right now. Please refresh and try again."
      );
      return;
    }

    setIsSubmitting(true);
    setStatus("info", "Creating secure Razorpay order...");

    try {
      const orderResponse = await createHomeServiceOrder({
        bookingId: bookingState.booking.bookingId,
        userId: bookingState.booking.userId,
        petId: bookingState.booking.petId,
        amount: BOOKING_TOTAL_PRICE,
      });

      if (!orderResponse.ok || !orderResponse.orderId || !orderResponse.key) {
        throw new Error(orderResponse.error || "Order could not be created.");
      }

      const checkout = new window.Razorpay({
        key: orderResponse.key,
        amount: orderResponse.amountPaise,
        currency: orderResponse.currency || "INR",
        order_id: orderResponse.orderId,
        name: "Snoutiq",
        description: "Vet at home in Delhi NCR",
        prefill: {
          name: bookingState.lead.name.trim(),
          contact: bookingState.lead.phone.trim(),
        },
        notes: {
          home_service_booking_id: String(bookingState.booking.bookingId),
          user_id: String(bookingState.booking.userId),
          pet_id: String(bookingState.booking.petId),
          order_type: "home_service",
        },
        theme: {
          color: "#1447e6",
        },
        modal: {
          ondismiss: () => {
            setStatus("error", "Payment cancelled. You can retry anytime.");
            setIsSubmitting(false);
          },
        },
        handler: async (paymentResponse) => {
          setStatus("info", "Verifying payment...");

          try {
            const verifyResponse = await verifyHomeServicePayment({
              bookingId: bookingState.booking.bookingId,
              userId: bookingState.booking.userId,
              petId: bookingState.booking.petId,
              razorpayOrderId: paymentResponse?.razorpay_order_id,
              razorpayPaymentId: paymentResponse?.razorpay_payment_id,
              razorpaySignature: paymentResponse?.razorpay_signature,
            });

            if (!verifyResponse.ok) {
              throw new Error(
                verifyResponse.error || "Payment verification failed."
              );
            }

            updateBooking({
              bookingId: verifyResponse.bookingId ?? bookingState.booking.bookingId,
              userId: verifyResponse.userId ?? bookingState.booking.userId,
              petId: verifyResponse.petId ?? bookingState.booking.petId,
              latestCompletedStep: verifyResponse.latestCompletedStep ?? 3,
              paymentStatus: verifyResponse.paymentStatus || "paid",
              paymentProvider: "razorpay",
              paymentReference:
                verifyResponse.paymentReference ||
                paymentResponse?.razorpay_payment_id ||
                "",
              bookingReference:
                verifyResponse.bookingReference ||
                bookingState.booking.bookingReference ||
                bookingReferenceFallback,
            });

            updateProgress({
              paymentCompleted: true,
            });

            setStatus("success", "Payment successful. Redirecting...");
            navigate(BOOKING_FLOW_ROUTES.success, { replace: true });
          } catch (error) {
            setStatus(
              "error",
              error?.message || "Payment verification failed. Please try again."
            );
            setIsSubmitting(false);
          }
        },
      });

      checkout.on("payment.failed", (event) => {
        const description =
          event?.error?.description ||
          event?.error?.reason ||
          "Payment failed. Please try again.";

        setStatus("error", description);
        setIsSubmitting(false);
      });

      checkout.open();
      setStatus("info", "Opening secure Razorpay checkout...");
    } catch (error) {
      setStatus(
        "error",
        error?.message || "Payment could not be started. Please try again."
      );
      setIsSubmitting(false);
    }
  };

  const payButtonLabel = isSubmitting
    ? "Opening payment..."
    : isGatewayLoading
      ? "Preparing payment..."
      : `Pay ₹${formatCurrency(BOOKING_TOTAL_PRICE)} securely →`;

  return (
    <div>
      <button
        type="button"
        className="step-back"
        onClick={() => navigate(BOOKING_FLOW_ROUTES.petDetails)}
      >
        &larr; Back
      </button>
      <h3 style={{ marginBottom: 16 }}>Confirm your booking</h3>

      <div className="summary-card">
        <div className="sum-row">
          <span className="sum-label">Name</span>
          <span className="sum-val">{displayValue(bookingState.lead.name)}</span>
        </div>
        <div className="sum-row">
          <span className="sum-label">Phone</span>
          <span className="sum-val">{displayValue(bookingState.lead.phone)}</span>
        </div>
        <div className="sum-row">
          <span className="sum-label">Area</span>
          <span className="sum-val">
            {displayValue(bookingState.lead.area, "Not selected")}
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
            {displayValue(bookingState.lead.reason, "Not selected")}
          </span>
        </div>
      </div>

      <div className="pay-box">
        <div className="pay-line">
          <span>Home vet visit</span>
          <span>₹{formatCurrency(BOOKING_PRICING.originalPrice)}</span>
        </div>
        <div className="pay-line discount">
          <span>20% off - limited period</span>
          <span>-₹{formatCurrency(BOOKING_PRICING.discountAmount)}</span>
        </div>
        <div className="pay-line">
          <span>Amount after discount</span>
          <span>₹{formatCurrency(BOOKING_PRICING.currentPrice)}</span>
        </div>
        <div className="pay-line">
          <span>{BOOKING_PRICING.gstRate}% GST</span>
          <span>+₹{formatCurrency(BOOKING_GST_AMOUNT)}</span>
        </div>
        <div className="pay-line total">
          <span>Total payable</span>
          <span>₹{formatCurrency(BOOKING_TOTAL_PRICE)}</span>
        </div>
        <div className="pay-includes">
          Includes up to ₹200 of essential medicines · Written visit report ·
          Pet record saved on Snoutiq
        </div>
      </div>

      <div className="refund-note">
        Secure booking with 100% refund if we cannot confirm a vet in your area
        after payment.
      </div>

      {paymentMessage.text ? (
        <div className={`pay-status ${paymentMessage.type || "info"}`}>
          {paymentMessage.text}
        </div>
      ) : null}

      <button
        type="button"
        className="cta pay-cta"
        onClick={handlePayment}
        disabled={isSubmitting || isGatewayLoading || !isPaymentContextReady}
      >
        {payButtonLabel}
      </button>
      <p className="cta-note">
        Secure payment via Razorpay · UPI / card / net banking
      </p>
    </div>
  );
}
