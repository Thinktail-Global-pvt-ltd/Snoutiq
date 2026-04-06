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
        {"\uD83D\uDC3E"}
      </div>
      <h2>Booking confirmed!</h2>
      <p className="ty-sub">
        Your Pet Parent Assistant has been assigned and will call you within 15
        minutes to confirm your vet and share arrival time.
      </p>

      <div className="ty-steps">
        <div className="ty-step">
          <div className="ty-num">1</div>
          <p>Assistant calls you to confirm vet details.</p>
        </div>
        <div className="ty-step">
          <div className="ty-num">2</div>
          <p>You're notified when the vet starts moving towards you.</p>
        </div>
        <div className="ty-step">
          <div className="ty-num">3</div>
          <p>
            Vet arrives at your Gurgaon home and your written report is sent
            after the visit.
          </p>
        </div>
      </div>

      <p className="ty-ref">
        Booking ref:{" "}
        <b>{bookingState.booking.bookingReference || "SNQ-XXXXX"}</b>
      </p>

      <button type="button" className="cta ty-home-btn" onClick={handleGoHome}>
        Book another Gurgaon visit
      </button>
    </div>
  );
}
