import React, { useEffect, useId, useState } from "react";
import { useNavigate } from "react-router-dom";
import { fetchBreedOptions, submitPetDetailsStep } from "./bookingFlowApi";
import {
  BOOKING_FLOW_ROUTES,
  BOOKING_TOTAL_PRICE,
  SEX_OPTIONS,
  SYMPTOM_OPTIONS,
  VACCINATION_OPTIONS,
} from "./bookingFlowData";
import { useVetNearMeBooking } from "./VetNearMeBookingContext";

export default function VetNearMePetDetailsPage() {
  const navigate = useNavigate();
  const breedListId = useId();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isBreedsLoading, setIsBreedsLoading] = useState(false);
  const [breedOptions, setBreedOptions] = useState([]);
  const [breedLoadError, setBreedLoadError] = useState("");
  const [errors, setErrors] = useState({});
  const {
    bookingState,
    toggleSymptom,
    updatePet,
    updateBooking,
    updateProgress,
  } = useVetNearMeBooking();

  const selectedSpecies = bookingState.lead.species;
  const usesBreedApi =
    selectedSpecies === "Dog" || selectedSpecies === "Cat";
  const showOtherPetTypeField = selectedSpecies === "Other";
  const shouldShowBreedSelect = usesBreedApi && !breedLoadError;
  const selectBreedOptions = bookingState.pet.breed &&
    !breedOptions.includes(bookingState.pet.breed)
    ? [bookingState.pet.breed, ...breedOptions]
    : breedOptions;

  useEffect(() => {
    if (!bookingState.progress.leadSubmitted) {
      navigate(BOOKING_FLOW_ROUTES.lead, { replace: true });
    }
  }, [bookingState.progress.leadSubmitted, navigate]);

  useEffect(() => {
    const leadReason = bookingState.lead.reason.trim();
    const issueDescription = bookingState.pet.issue.trim();

    if (!leadReason || issueDescription) {
      return;
    }

    updatePet({ issue: bookingState.lead.reason });
  }, [bookingState.lead.reason, bookingState.pet.issue]);

  useEffect(() => {
    let isCancelled = false;

    if (!usesBreedApi) {
      setBreedOptions([]);
      setBreedLoadError("");
      setIsBreedsLoading(false);
      return undefined;
    }

    setIsBreedsLoading(true);
    setBreedLoadError("");

    fetchBreedOptions(selectedSpecies)
      .then((options) => {
        if (isCancelled) return;
        setBreedOptions(options);
      })
      .catch((error) => {
        if (isCancelled) return;
        setBreedOptions([]);
        setBreedLoadError(
          error?.message || "Could not load breeds right now."
        );
      })
      .finally(() => {
        if (isCancelled) return;
        setIsBreedsLoading(false);
      });

    return () => {
      isCancelled = true;
    };
  }, [selectedSpecies, usesBreedApi]);

  const handlePetChange = (field, value) => {
    updatePet({ [field]: value });

    setErrors((currentErrors) => {
      if (!currentErrors[field]) {
        return currentErrors;
      }

      const nextErrors = { ...currentErrors };
      delete nextErrors[field];
      return nextErrors;
    });
  };

  const validatePetForm = () => {
    const nextErrors = {};

    if (!bookingState.pet.petName.trim()) {
      nextErrors.petName = "Please enter your pet's name.";
    }

    if (showOtherPetTypeField && !bookingState.pet.otherPetType.trim()) {
      nextErrors.otherPetType = "Please enter your pet type.";
    }

    if (!bookingState.pet.issue.trim()) {
      nextErrors.issue = "Please describe today's concern.";
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const handleContinue = async () => {
    if (!bookingState.booking.bookingId) {
      window.alert("Please complete step 1 before continuing.");
      navigate(BOOKING_FLOW_ROUTES.lead, { replace: true });
      return;
    }

    if (!validatePetForm()) {
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await submitPetDetailsStep({
        bookingId: bookingState.booking.bookingId,
        petData: bookingState.pet,
        species: selectedSpecies,
      });

      if (!response?.ok) {
        throw new Error("Pet details could not be submitted.");
      }

      updateBooking({
        bookingId: response.bookingId,
        userId: response.userId ?? bookingState.booking.userId,
        petId: response.petId,
        latestCompletedStep: response.latestCompletedStep,
      });

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
        &larr; Back
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
        better prepared they&apos;ll be.
      </p>

      <div className="sdiv">Pet profile</div>
      <div className="half">
        <div className="field">
          <label>
            Pet&apos;s name <span className="required-mark">*</span>
          </label>
          <input
            type="text"
            className={errors.petName ? "input-error" : ""}
            placeholder="Enter your pet's name"
            aria-invalid={Boolean(errors.petName)}
            value={bookingState.pet.petName}
            onChange={(event) =>
              handlePetChange("petName", event.target.value)
            }
          />
          {errors.petName ? (
            <div className="field-error">{errors.petName}</div>
          ) : null}
        </div>

        {showOtherPetTypeField ? (
          <div className="field">
            <label>
              Pet type <span className="required-mark">*</span>
            </label>
            <input
              type="text"
              className={errors.otherPetType ? "input-error" : ""}
              placeholder="Enter your pet type"
              aria-invalid={Boolean(errors.otherPetType)}
              value={bookingState.pet.otherPetType}
              onChange={(event) =>
                handlePetChange("otherPetType", event.target.value)
              }
            />
            {errors.otherPetType ? (
              <div className="field-error">{errors.otherPetType}</div>
            ) : null}
          </div>
        ) : shouldShowBreedSelect ? (
          <div className="field">
            <label>Breed</label>
            <input
              type="text"
              list={breedListId}
              value={bookingState.pet.breed}
              onChange={(event) => handlePetChange("breed", event.target.value)}
              disabled={isBreedsLoading}
              placeholder={
                isBreedsLoading ? "Loading breeds..." : "Search or select breed"
              }
            />
            <datalist id={breedListId}>
              {selectBreedOptions.map((option) => (
                <option key={option} value={option}>
                  {option}
                </option>
              ))}
            </datalist>
          </div>
        ) : (
          <div className="field">
            <label>Breed</label>
            <input
              type="text"
              placeholder="Enter breed"
              value={bookingState.pet.breed}
              onChange={(event) => handlePetChange("breed", event.target.value)}
            />
            {usesBreedApi && breedLoadError ? (
              <div className="fhint">
                Couldn&apos;t load breeds right now. Enter breed manually.
              </div>
            ) : null}
          </div>
        )}
      </div>

      <div className="half">
        <div className="field">
          <label>Date of birth</label>
          <input
            type="date"
            value={bookingState.pet.dob}
            onChange={(event) => handlePetChange("dob", event.target.value)}
          />
          <div className="fhint">Approximate is fine</div>
        </div>
        <div className="field">
          <label>Gender</label>
          <select
            value={bookingState.pet.sex}
            onChange={(event) => handlePetChange("sex", event.target.value)}
          >
            <option value="">Select gender</option>
            {SEX_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="sdiv">Today&apos;s concern</div>
      <div className="field">
        <label>
          Describe what you&apos;ve noticed{" "}
          <span className="required-mark">*</span>
        </label>
        <textarea
          className={errors.issue ? "input-error" : ""}
          placeholder="Enter what you have noticed"
          aria-invalid={Boolean(errors.issue)}
          value={bookingState.pet.issue}
          onChange={(event) => handlePetChange("issue", event.target.value)}
        />
        {errors.issue ? (
          <div className="field-error">{errors.issue}</div>
        ) : null}
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
              handlePetChange("vaccinationStatus", event.target.value)
            }
          >
            <option value="">Select vaccination status</option>
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
            type="date"
            value={bookingState.pet.deworming}
            onChange={(event) =>
              handlePetChange("deworming", event.target.value)
            }
          />
        </div>
      </div>
      <div className="field">
        <label>Past illnesses or surgeries</label>
        <textarea
          placeholder="Enter past illnesses or surgeries"
          style={{ minHeight: 64 }}
          value={bookingState.pet.history}
          onChange={(event) => handlePetChange("history", event.target.value)}
        />
      </div>
      <div className="field">
        <label>Current medications</label>
        <input
          type="text"
          placeholder="Enter current medications"
          value={bookingState.pet.medications}
          onChange={(event) =>
            handlePetChange("medications", event.target.value)
          }
        />
      </div>
      <div className="field">
        <label>Known allergies</label>
        <input
          type="text"
          placeholder="Enter known allergies"
          value={bookingState.pet.allergies}
          onChange={(event) => handlePetChange("allergies", event.target.value)}
        />
      </div>
      <div className="field">
        <label>Anything else for the vet</label>
        <textarea
          placeholder="Enter anything else for the vet"
          style={{ minHeight: 60 }}
          value={bookingState.pet.notes}
          onChange={(event) => handlePetChange("notes", event.target.value)}
        />
      </div>

      <button
        type="button"
        className="cta"
        onClick={handleContinue}
        disabled={isSubmitting}
      >
        Review &amp; pay &#8377;{BOOKING_TOTAL_PRICE} &rarr;
      </button>
      <p className="cta-note">
        Almost done. Review your booking summary before paying.
      </p>
    </div>
  );
}
