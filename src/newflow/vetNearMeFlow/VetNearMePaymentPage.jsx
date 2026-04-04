import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { initiatePayment } from "./bookingFlowApi";
import { BOOKING_FLOW_ROUTES, BOOKING_PRICING } from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

const displayValue = (value, fallback = "-") => {
  const text = String(value || "").trim();
  return text || fallback;
};

export default function VetNearMePaymentPage() {
  const navigate = useNavigate();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { bookingState, updateBooking, updateProgress } = useVetNearMeBooking();

  useEffect(() => {
    if (!bookingState.progress.leadSubmitted) {
      navigate(BOOKING_FLOW_ROUTES.lead, { replace: true });
      return;
    }

    if (!bookingState.progress.petDetailsSubmitted) {
      navigate(BOOKING_FLOW_ROUTES.petDetails, { replace: true });
    }
  }, [
    bookingState.progress.leadSubmitted,
    bookingState.progress.petDetailsSubmitted,
    navigate,
  ]);

  const petTypeSummary =
    bookingState.lead.species === "Other"
      ? bookingState.pet.otherPetType.trim()
      : bookingState.lead.species.trim();

  const petSummary = [bookingState.pet.petName.trim(), petTypeSummary]
    .filter(Boolean)
    .join(" · ");

  const handlePayment = async () => {
    if (!bookingState.booking.bookingId) {
      window.alert("Please complete the booking form before payment.");
      navigate(BOOKING_FLOW_ROUTES.lead, { replace: true });
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await initiatePayment({
        bookingId: bookingState.booking.bookingId,
        amountPayable: BOOKING_PRICING.currentPrice,
        paymentReference: bookingState.booking.paymentReference,
      });

      if (!response?.ok) {
        throw new Error("Payment could not be started.");
      }

      updateBooking({
        bookingId: response.bookingId,
        latestCompletedStep: response.latestCompletedStep,
        paymentStatus: response.paymentStatus,
        paymentReference:
          bookingState.booking.paymentReference || `demo-payment-${Date.now()}`,
        bookingReference: response.bookingReference,
      });

      updateProgress({
        paymentCompleted: true,
      });

      navigate(BOOKING_FLOW_ROUTES.success, { replace: true });
    } catch (error) {
      window.alert(error?.message || "Something went wrong. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

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
          <span>&#8377;{BOOKING_PRICING.originalPrice}</span>
        </div>
        <div className="pay-line discount">
          <span>20% off &mdash; limited period</span>
          <span>-&#8377;{BOOKING_PRICING.discountAmount}</span>
        </div>
        <div className="pay-line total">
          <span>Total payable</span>
          <span>&#8377;{BOOKING_PRICING.currentPrice}</span>
        </div>
        <div className="pay-includes">
          Includes up to &#8377;200 of essential medicines · Written visit
          report · Pet record saved on Snoutiq
        </div>
      </div>

      <div className="refund-note">
        Secure booking with 100% refund if we can&apos;t confirm a vet in your
        area after payment.
      </div>

      <button
        type="button"
        className="cta pay-cta"
        onClick={handlePayment}
        disabled={isSubmitting}
      >
        Pay &#8377;{BOOKING_PRICING.currentPrice} securely &rarr;
      </button>
      <p className="cta-note">
        Secure payment via Razorpay · UPI / card / net banking
      </p>
    </div>
  );
}
