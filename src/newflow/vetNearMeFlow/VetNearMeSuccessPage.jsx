import React, { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { BOOKING_FLOW_ROUTES } from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

export default function VetNearMeSuccessPage() {
  const navigate = useNavigate();
  const { bookingState } = useVetNearMeBooking();

  useEffect(() => {
    if (!bookingState.progress.paymentCompleted) {
      navigate(BOOKING_FLOW_ROUTES.payment, { replace: true });
    }
  }, [bookingState.progress.paymentCompleted, navigate]);

  return (
    <div className="thankyou">
      <div className="ty-icon">🐾</div>
      <h3>Booking confirmed!</h3>
      <p className="ty-sub">
        Your Pet Parent Assistant has been assigned and will call you within 15
        minutes to confirm your vet and share arrival time.
      </p>

      <div className="ty-steps">
        <div className="ty-step">
          <div className="ty-num">1</div>
          <p>Assistant calls you to confirm vet details</p>
        </div>
        <div className="ty-step">
          <div className="ty-num">2</div>
          <p>You're notified when the vet starts moving towards you</p>
        </div>
        <div className="ty-step">
          <div className="ty-num">3</div>
          <p>Vet arrives at your home · Written report sent after visit</p>
        </div>
      </div>

      <p
        style={{
          fontSize: 13,
          color: "var(--ink3)",
          textAlign: "center",
          marginTop: 16,
        }}
      >
        Booking ref:{" "}
        <b style={{ color: "var(--ink)" }}>
          {bookingState.booking.bookingReference || "SNQ-XXXXX"}
        </b>
      </p>
    </div>
  );
}
