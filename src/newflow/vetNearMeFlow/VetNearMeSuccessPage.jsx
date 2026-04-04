import React, { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { BOOKING_FLOW_ROUTES } from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

export default function VetNearMeSuccessPage() {
  const navigate = useNavigate();
  const { bookingState, resetBookingFlow } = useVetNearMeBooking();

  useEffect(() => {
    if (!bookingState.progress.paymentCompleted) {
      navigate(BOOKING_FLOW_ROUTES.payment, { replace: true });
    }
  }, [bookingState.progress.paymentCompleted, navigate]);

  const handleGoHome = () => {
    resetBookingFlow();
    navigate(BOOKING_FLOW_ROUTES.lead, { replace: true });
  };

  return (
    <div className="thankyou">
      <div className="ty-icon" aria-hidden="true">
        🐾
      </div>
      <h3>Booking confirmed!</h3>
      <p className="ty-sub">
        Your Pet Parent Assistant has been assigned and will call you within 15
        minutes to confirm your vet and share arrival time.
      </p>

      <p className="ty-ref">
        Booking ref:{" "}
        <b>{bookingState.booking.bookingReference || "SNQ-XXXXX"}</b>
      </p>

      <button type="button" className="cta ty-home-btn" onClick={handleGoHome}>
        Go to Home
      </button>
    </div>
  );
}
