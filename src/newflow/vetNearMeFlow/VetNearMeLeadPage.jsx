import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { submitLeadStep } from "./bookingFlowApi";
import {
  AREA_OPTIONS,
  BOOKING_FLOW_ROUTES,
  PET_TYPE_OPTIONS,
  REASON_OPTIONS,
} from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

const isValidPhone = (value) =>
  String(value || "")
    .replace(/\s/g, "")
    .replace(/[^\d+]/g, "").length >= 10;

export default function VetNearMeLeadPage() {
  const navigate = useNavigate();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { bookingState, updateLead, updateProgress } = useVetNearMeBooking();

  const handleContinue = async () => {
    if (!bookingState.lead.name.trim()) {
      window.alert("Please enter your name.");
      return;
    }

    if (!isValidPhone(bookingState.lead.phone)) {
      window.alert("Please enter a valid phone number.");
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await submitLeadStep(bookingState.lead);

      if (!response?.ok) {
        throw new Error("Lead step could not be submitted.");
      }

      updateProgress({
        leadSubmitted: true,
      });

      navigate(BOOKING_FLOW_ROUTES.petDetails);
    } catch (error) {
      window.alert(error?.message || "Something went wrong. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div>
      <h3 style={{ marginBottom: 16 }}>Book a vet near you — at home</h3>
      <div className="field">
        <label>Your name</label>
        <input
          type="text"
          placeholder="Priya Sharma"
          autoComplete="name"
          value={bookingState.lead.name}
          onChange={(event) => updateLead({ name: event.target.value })}
        />
      </div>
      <div className="field">
        <label>Phone number</label>
        <input
          type="tel"
          placeholder="+91 98xxxxxxxx"
          autoComplete="tel"
          value={bookingState.lead.phone}
          onChange={(event) => updateLead({ phone: event.target.value })}
        />
      </div>
      <div className="half">
        <div className="field">
          <label>Pet type</label>
          <select
            value={bookingState.lead.species}
            onChange={(event) => updateLead({ species: event.target.value })}
          >
            <option value="">Select</option>
            {PET_TYPE_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>
        <div className="field">
          <label>Your area</label>
          <select
            value={bookingState.lead.area}
            onChange={(event) => updateLead({ area: event.target.value })}
          >
            <option value="">Select</option>
            {AREA_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>
      </div>
      <div className="field">
        <label>Reason for visit</label>
        <select
          value={bookingState.lead.reason}
          onChange={(event) => updateLead({ reason: event.target.value })}
        >
          <option value="">What does your pet need?</option>
          {REASON_OPTIONS.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      </div>
      <button
        type="button"
        className="cta"
        onClick={handleContinue}
        disabled={isSubmitting}
      >
        Continue — add pet details →
      </button>
      <p className="cta-note">
        Takes 2 more minutes · Helps your vet prepare before arriving
      </p>
    </div>
  );
}
