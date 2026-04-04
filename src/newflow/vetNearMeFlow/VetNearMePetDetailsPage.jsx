import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { submitPetDetailsStep } from "./bookingFlowApi";
import {
  BOOKING_FLOW_ROUTES,
  BOOKING_PRICING,
  SEX_OPTIONS,
  SYMPTOM_OPTIONS,
  VACCINATION_OPTIONS,
} from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

export default function VetNearMePetDetailsPage() {
  const navigate = useNavigate();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const {
    bookingState,
    toggleSymptom,
    updatePet,
    updateProgress,
  } = useVetNearMeBooking();

  useEffect(() => {
    if (!bookingState.progress.leadSubmitted) {
      navigate(BOOKING_FLOW_ROUTES.lead, { replace: true });
    }
  }, [bookingState.progress.leadSubmitted, navigate]);

  const handleContinue = async () => {
    try {
      setIsSubmitting(true);
      const response = await submitPetDetailsStep({
        lead: bookingState.lead,
        pet: bookingState.pet,
      });

      if (!response?.ok) {
        throw new Error("Pet details could not be submitted.");
      }

      updateProgress({
        petDetailsSubmitted: true,
      });

      navigate(BOOKING_FLOW_ROUTES.payment);
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
        onClick={() => navigate(BOOKING_FLOW_ROUTES.lead)}
      >
        ← Back
      </button>
      <h3 style={{ marginBottom: 4 }}>About your pet</h3>
      <p
        style={{
          fontSize: 13,
          color: "var(--ink2)",
          marginBottom: 18,
          lineHeight: 1.5,
        }}
      >
        This goes directly to your vet before they arrive. The more detail, the
        better prepared they'll be.
      </p>

      <div className="sdiv">Pet profile</div>
      <div className="half">
        <div className="field">
          <label>Pet's name</label>
          <input
            type="text"
            placeholder="e.g. Bruno"
            value={bookingState.pet.petName}
            onChange={(event) => updatePet({ petName: event.target.value })}
          />
        </div>
        <div className="field">
          <label>Breed</label>
          <input
            type="text"
            placeholder="e.g. Labrador / Indie"
            value={bookingState.pet.breed}
            onChange={(event) => updatePet({ breed: event.target.value })}
          />
        </div>
      </div>
      <div className="half">
        <div className="field">
          <label>Date of birth</label>
          <input
            type="date"
            value={bookingState.pet.dob}
            onChange={(event) => updatePet({ dob: event.target.value })}
          />
          <div className="fhint">Approximate is fine</div>
        </div>
        <div className="field">
          <label>Sex</label>
          <select
            value={bookingState.pet.sex}
            onChange={(event) => updatePet({ sex: event.target.value })}
          >
            <option value="">Select</option>
            {SEX_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="sdiv">Today's concern</div>
      <div className="field">
        <label>Describe what you've noticed</label>
        <textarea
          placeholder="When did it start, how severe, any changes in behaviour, eating, energy..."
          value={bookingState.pet.issue}
          onChange={(event) => updatePet({ issue: event.target.value })}
        />
      </div>
      <div className="field">
        <label>Symptoms (tick all that apply)</label>
        <div className="cbgroup">
          {SYMPTOM_OPTIONS.map((option) => (
            <label className="cbitem" key={option.value}>
              <input
                type="checkbox"
                value={option.value}
                checked={bookingState.pet.symptoms.includes(option.value)}
                onChange={() => toggleSymptom(option.value)}
              />
              <span>{option.label}</span>
            </label>
          ))}
        </div>
      </div>

      <div className="sdiv">Medical history</div>
      <div className="half">
        <div className="field">
          <label>Vaccination status</label>
          <select
            value={bookingState.pet.vaccinationStatus}
            onChange={(event) =>
              updatePet({ vaccinationStatus: event.target.value })
            }
          >
            <option value="">Select</option>
            {VACCINATION_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>
        <div className="field">
          <label>Last deworming</label>
          <input
            type="text"
            placeholder="e.g. 3 months ago"
            value={bookingState.pet.deworming}
            onChange={(event) => updatePet({ deworming: event.target.value })}
          />
        </div>
      </div>
      <div className="field">
        <label>Past illnesses or surgeries</label>
        <textarea
          placeholder="e.g. Had parvovirus 2 yrs ago, recovered. No surgeries."
          style={{ minHeight: 64 }}
          value={bookingState.pet.history}
          onChange={(event) => updatePet({ history: event.target.value })}
        />
      </div>
      <div className="field">
        <label>Current medications</label>
        <input
          type="text"
          placeholder="e.g. Apoquel 5mg daily / none"
          value={bookingState.pet.medications}
          onChange={(event) => updatePet({ medications: event.target.value })}
        />
      </div>
      <div className="field">
        <label>Known allergies</label>
        <input
          type="text"
          placeholder="e.g. Allergic to penicillin / none known"
          value={bookingState.pet.allergies}
          onChange={(event) => updatePet({ allergies: event.target.value })}
        />
      </div>
      <div className="field">
        <label>Anything else for the vet</label>
        <textarea
          placeholder="e.g. Gets anxious with strangers — please approach slowly."
          style={{ minHeight: 60 }}
          value={bookingState.pet.notes}
          onChange={(event) => updatePet({ notes: event.target.value })}
        />
      </div>

      <button
        type="button"
        className="cta"
        onClick={handleContinue}
        disabled={isSubmitting}
      >
        Review & pay ₹{BOOKING_PRICING.currentPrice} →
      </button>
      <p className="cta-note">
        Almost done. Review your booking summary before paying.
      </p>
    </div>
  );
}
