import React, { useId, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  AREA_OPTIONS,
  BOOKING_FLOW_ROUTES,
  PET_TYPE_OPTIONS,
} from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

const isValidPhone = (value) =>
  String(value || "")
    .replace(/\s/g, "")
    .replace(/[^\d+]/g, "").length >= 10;

export default function VetNearMeLeadPage() {
  const navigate = useNavigate();
  const fieldIdPrefix = useId();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const {
    bookingState,
    updateLead,
    updateBooking,
    updateProgress,
  } = useVetNearMeBooking();
  const nameInputId = `${fieldIdPrefix}-lead-name`;
  const phoneInputId = `${fieldIdPrefix}-lead-phone`;
  const speciesSelectId = `${fieldIdPrefix}-lead-species`;
  const areaSelectId = `${fieldIdPrefix}-lead-area`;
  const reasonTextareaId = `${fieldIdPrefix}-lead-reason`;

  const handleLeadChange = (field, value) => {
    updateLead({ [field]: value });

    setErrors((currentErrors) => {
      if (!currentErrors[field]) {
        return currentErrors;
      }

      const nextErrors = { ...currentErrors };
      delete nextErrors[field];
      return nextErrors;
    });
  };

  const validateLeadForm = () => {
    const nextErrors = {};

    if (!bookingState.lead.name.trim()) {
      nextErrors.name = "Please enter your name.";
    }

    if (!isValidPhone(bookingState.lead.phone)) {
      nextErrors.phone = "Please enter a valid phone number.";
    }

    if (!bookingState.lead.species) {
      nextErrors.species = "Please select your pet type.";
    }

    if (!bookingState.lead.area) {
      nextErrors.area = "Please select your area.";
    }

    if (!bookingState.lead.reason.trim()) {
      nextErrors.reason = "Please enter the reason for visit.";
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const handleContinue = async () => {
    if (!validateLeadForm()) {
      return;
    }

    try {
      setIsSubmitting(true);
      const { submitLeadStep } = await import("./bookingFlowApi");
      const response = await submitLeadStep(bookingState.lead);

      if (!response?.ok) {
        throw new Error("Lead step could not be submitted.");
      }

      updateBooking({
        bookingId: response.bookingId,
        userId: response.userId,
        petId: null,
        latestCompletedStep: response.latestCompletedStep,
        paymentStatus: "pending",
        paymentReference: "",
        bookingReference: "",
      });

      updateProgress({
        leadSubmitted: true,
        petDetailsSubmitted: false,
        paymentCompleted: false,
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
      <h2 style={{ marginBottom: 16 }}>Book a vet near you &mdash; at home</h2>
      <div className="field">
        <label htmlFor={nameInputId}>
          Your name <span className="required-mark">*</span>
        </label>
        <input
          id={nameInputId}
          type="text"
          className={errors.name ? "input-error" : ""}
          placeholder="Enter your name"
          autoComplete="name"
          aria-invalid={Boolean(errors.name)}
          value={bookingState.lead.name}
          onChange={(event) => handleLeadChange("name", event.target.value)}
        />
        {errors.name ? <div className="field-error">{errors.name}</div> : null}
      </div>
      <div className="field">
        <label htmlFor={phoneInputId}>
          Phone number <span className="required-mark">*</span>
        </label>
        <input
          id={phoneInputId}
          type="tel"
          className={errors.phone ? "input-error" : ""}
          placeholder="Enter your phone number"
          autoComplete="tel"
          aria-invalid={Boolean(errors.phone)}
          value={bookingState.lead.phone}
          onChange={(event) => handleLeadChange("phone", event.target.value)}
        />
        {errors.phone ? (
          <div className="field-error">{errors.phone}</div>
        ) : null}
      </div>
      <div className="half">
        <div className="field">
          <label htmlFor={speciesSelectId}>
            Pet type <span className="required-mark">*</span>
          </label>
          <select
            id={speciesSelectId}
            className={errors.species ? "input-error" : ""}
            aria-invalid={Boolean(errors.species)}
            value={bookingState.lead.species}
            onChange={(event) =>
              handleLeadChange("species", event.target.value)
            }
          >
            <option value="">Select pet type</option>
            {PET_TYPE_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
          {errors.species ? (
            <div className="field-error">{errors.species}</div>
          ) : null}
        </div>
        <div className="field">
          <label htmlFor={areaSelectId}>
            Your area <span className="required-mark">*</span>
          </label>
          <select
            id={areaSelectId}
            className={errors.area ? "input-error" : ""}
            aria-invalid={Boolean(errors.area)}
            value={bookingState.lead.area}
            onChange={(event) => handleLeadChange("area", event.target.value)}
          >
            <option value="">Select your area</option>
            {AREA_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
          {errors.area ? <div className="field-error">{errors.area}</div> : null}
        </div>
      </div>
      <div className="field">
        <label htmlFor={reasonTextareaId}>
          Reason for visit <span className="required-mark">*</span>
        </label>
        <textarea
          id={reasonTextareaId}
          className={errors.reason ? "input-error" : ""}
          placeholder="Enter reason for visit"
          aria-invalid={Boolean(errors.reason)}
          value={bookingState.lead.reason}
          onChange={(event) => handleLeadChange("reason", event.target.value)}
        />
        {errors.reason ? (
          <div className="field-error">{errors.reason}</div>
        ) : null}
      </div>
      <button
        id="lead-form-cta"
        type="button"
        className="cta"
        onClick={handleContinue}
        disabled={isSubmitting}
      >
        Continue &mdash; add pet details &rarr;
      </button>
      <p className="cta-note">
        Takes 2 more minutes &middot; Helps your vet prepare before arriving
      </p>
    </div>
  );
}
