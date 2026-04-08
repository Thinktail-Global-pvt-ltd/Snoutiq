import React, { useEffect, useId, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  fetchBreedOptions,
  submitLeadStep,
  submitPetDetailsStep,
} from "../vetNearMeFlow/bookingFlowApi";
import {
  AREA_OPTIONS,
  BOOKING_TOTAL_PRICE,
  PET_TYPE_OPTIONS,
  REASON_OPTIONS,
  SEX_OPTIONS,
  SYMPTOM_OPTIONS,
  VACCINATION_OPTIONS,
} from "../vetNearMeFlow/bookingFlowData";
import "../vetNearMeFlow/VetNearMeBooking.css";

const STORAGE_KEY = "snoutiq-vet-near-me-standalone";
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

const normalizeSymptoms = (value) => {
  if (Array.isArray(value)) return value.map((item) => String(item || "").trim()).filter(Boolean);
  return [];
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
      issue: pickText(pet.issue, raw.issue, raw.problemText, lead.reason, raw.reason),
      symptoms: normalizeSymptoms(pet.symptoms ?? raw.symptoms),
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

const hasState = (value) => {
  const state = normalizeState(value);
  return Boolean(
    state.lead.ownerName ||
      state.lead.phone ||
      state.lead.species ||
      state.lead.area ||
      state.lead.reason ||
      state.pet.petName ||
      state.pet.issue ||
      state.booking.bookingId ||
      state.booking.userId ||
      state.booking.petId ||
      state.progress.petDetailsSubmitted ||
      state.progress.paymentCompleted
  );
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

const isValidPhone = (value) => String(value || "").replace(/\D/g, "").length >= 10;

export default function VetNearPetDetails() {
  const navigate = useNavigate();
  const location = useLocation();
  const fieldIdPrefix = useId();
  const [formState, setFormState] = useState(() =>
    hasState(location.state) ? normalizeState(location.state) : readStandaloneVetNearMeState()
  );
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isBreedsLoading, setIsBreedsLoading] = useState(false);
  const [breedOptions, setBreedOptions] = useState([]);
  const [breedLoadError, setBreedLoadError] = useState("");
  const [errors, setErrors] = useState({});

  const species = formState.lead.species;
  const usesBreedApi = species === "Dog" || species === "Cat";
  const showOtherPetTypeField = species === "Other";
  const breedListId = `${fieldIdPrefix}-breed-options`;
  const areaListId = `${fieldIdPrefix}-area-options`;
  const reasonListId = `${fieldIdPrefix}-reason-options`;
  const selectBreedOptions =
    formState.pet.breed && !breedOptions.includes(formState.pet.breed)
      ? [formState.pet.breed, ...breedOptions]
      : breedOptions;

  useEffect(() => {
    writeStandaloneVetNearMeState(formState);
  }, [formState]);

  useEffect(() => {
    let cancelled = false;
    if (!usesBreedApi) {
      setBreedOptions([]);
      setBreedLoadError("");
      setIsBreedsLoading(false);
      return undefined;
    }

    setIsBreedsLoading(true);
    setBreedLoadError("");
    fetchBreedOptions(species)
      .then((options) => {
        if (!cancelled) setBreedOptions(options);
      })
      .catch((error) => {
        if (cancelled) return;
        setBreedOptions([]);
        setBreedLoadError(error?.message || "Could not load breeds right now.");
      })
      .finally(() => {
        if (!cancelled) setIsBreedsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [species, usesBreedApi]);

  const clearError = (field) => {
    setErrors((current) => {
      if (!current[field]) return current;
      const next = { ...current };
      delete next[field];
      return next;
    });
  };

  const updateLead = (field, value) => {
    setFormState((current) => ({ ...current, lead: { ...current.lead, [field]: value } }));
    clearError(field);
  };

  const updatePet = (field, value) => {
    setFormState((current) => ({ ...current, pet: { ...current.pet, [field]: value } }));
    clearError(field);
  };

  const toggleSymptom = (value) => {
    setFormState((current) => {
      const symptoms = current.pet.symptoms.includes(value)
        ? current.pet.symptoms.filter((item) => item !== value)
        : [...current.pet.symptoms, value];
      return { ...current, pet: { ...current.pet, symptoms } };
    });
  };

  const handleSpeciesChange = (value) => {
    const next = normalizeSpecies(value, formState.pet.otherPetType);
    setFormState((current) => ({
      ...current,
      lead: { ...current.lead, species: next.species },
      pet: { ...current.pet, otherPetType: next.otherPetType },
    }));
    clearError("species");
    clearError("otherPetType");
  };

  const validate = () => {
    const nextErrors = {};
    if (!formState.lead.ownerName.trim()) nextErrors.ownerName = "Please enter your name.";
    if (!isValidPhone(formState.lead.phone)) nextErrors.phone = "Please enter a valid phone number.";
    if (!formState.lead.species) nextErrors.species = "Please select your pet type.";
    if (!formState.pet.petName.trim()) nextErrors.petName = "Please enter your pet's name.";
    if (!formState.pet.issue.trim()) nextErrors.issue = "Please describe today's concern.";
    if (showOtherPetTypeField && !formState.pet.otherPetType.trim()) {
      nextErrors.otherPetType = "Please enter your pet type.";
    }
    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const handleContinue = async () => {
    if (!validate()) return;

    try {
      setIsSubmitting(true);
      let bookingId = formState.booking.bookingId;
      let userId = formState.booking.userId;
      let latestCompletedStep = formState.booking.latestCompletedStep;

      if (!bookingId || !userId) {
        const leadResponse = await submitLeadStep({
          name: formState.lead.ownerName,
          phone: formState.lead.phone,
          species: formState.lead.species,
          area: formState.lead.area,
          reason: formState.lead.reason,
        });
        if (!leadResponse?.ok || !leadResponse.bookingId || !leadResponse.userId) {
          throw new Error("Booking could not be created.");
        }
        bookingId = leadResponse.bookingId;
        userId = leadResponse.userId;
        latestCompletedStep = leadResponse.latestCompletedStep ?? 1;
      }

      const petResponse = await submitPetDetailsStep({
        bookingId,
        petData: formState.pet,
        species: formState.lead.species,
      });
      if (!petResponse?.ok) throw new Error("Pet details could not be submitted.");

      const nextState = normalizeState({
        lead: formState.lead,
        pet: formState.pet,
        booking: {
          bookingId: petResponse.bookingId ?? bookingId,
          userId: petResponse.userId ?? userId,
          petId: petResponse.petId ?? formState.booking.petId,
          latestCompletedStep: petResponse.latestCompletedStep ?? latestCompletedStep ?? 2,
          paymentStatus: "pending",
          paymentReference: "",
          bookingReference: formState.booking.bookingReference,
        },
        progress: { petDetailsSubmitted: true, paymentCompleted: false },
      });

      setFormState(nextState);
      writeStandaloneVetNearMeState(nextState);
      navigate("/vet-near-me-payment", { state: nextState });
    } catch (error) {
      if (/booking session|session is incomplete|step 1/i.test(String(error?.message || ""))) {
        clearStandaloneVetNearMeState();
      }
      window.alert(error?.message || "Something went wrong. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleBack = () => {
    if (typeof window !== "undefined" && window.history.length > 1) navigate(-1);
  };

  return (
     <div className="vet-near-me-page standalone-page">
    <div className="standalone-flow">
      <div className="form-card standalone-form-card">
        <button type="button" className="step-back" onClick={handleBack}>
          &larr; Back
        </button>

        <h2 style={{ marginBottom: 4 }}>Book a vet near you</h2>
        <p
          style={{
            fontSize: 13,
            color: "var(--ink2)",
            marginBottom: 18,
            lineHeight: 1.5,
          }}
        >
          Share your details once. We will create the booking and send the full
          case to the vet before payment.
        </p>

      <div className="sdiv">Your details</div>
      <div className="half">
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-ownerName`}>Your name <span className="required-mark">*</span></label>
          <input
            id={`${fieldIdPrefix}-ownerName`}
            type="text"
            className={errors.ownerName ? "input-error" : ""}
            value={formState.lead.ownerName}
            onChange={(event) => updateLead("ownerName", event.target.value)}
            placeholder="Priya Sharma"
          />
          {errors.ownerName ? <div className="field-error">{errors.ownerName}</div> : null}
        </div>
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-phone`}>Phone number <span className="required-mark">*</span></label>
          <input
            id={`${fieldIdPrefix}-phone`}
            type="tel"
            className={errors.phone ? "input-error" : ""}
            value={formState.lead.phone}
            onChange={(event) => updateLead("phone", event.target.value)}
            placeholder="+91 98xxxxxxxx"
          />
          {errors.phone ? <div className="field-error">{errors.phone}</div> : null}
        </div>
      </div>

      <div className="half">
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-species`}>Pet type <span className="required-mark">*</span></label>
          <select
            id={`${fieldIdPrefix}-species`}
            className={errors.species ? "input-error" : ""}
            value={formState.lead.species}
            onChange={(event) => handleSpeciesChange(event.target.value)}
          >
            <option value="">Select pet type</option>
            {PET_TYPE_OPTIONS.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
          {errors.species ? <div className="field-error">{errors.species}</div> : null}
        </div>
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-area`}>Area</label>
          <input
            id={`${fieldIdPrefix}-area`}
            type="text"
            list={areaListId}
            value={formState.lead.area}
            onChange={(event) => updateLead("area", event.target.value)}
            placeholder="Enter your area"
          />
          <datalist id={areaListId}>
            {AREA_OPTIONS.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </datalist>
        </div>
      </div>

      <div className="field">
        <label htmlFor={`${fieldIdPrefix}-reason`}>Reason for visit</label>
        <input
          id={`${fieldIdPrefix}-reason`}
          type="text"
          list={reasonListId}
          value={formState.lead.reason}
          onChange={(event) => updateLead("reason", event.target.value)}
          placeholder="What does your pet need help with?"
        />
        <datalist id={reasonListId}>
          {REASON_OPTIONS.map((option) => (
            <option key={option} value={option}>{option}</option>
          ))}
        </datalist>
      </div>

      <div className="sdiv">Pet profile</div>
      <div className="half">
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-petName`}>Pet&apos;s name <span className="required-mark">*</span></label>
          <input
            id={`${fieldIdPrefix}-petName`}
            type="text"
            className={errors.petName ? "input-error" : ""}
            value={formState.pet.petName}
            onChange={(event) => updatePet("petName", event.target.value)}
            placeholder="Enter your pet's name"
          />
          {errors.petName ? <div className="field-error">{errors.petName}</div> : null}
        </div>

        {showOtherPetTypeField ? (
          <div className="field">
            <label htmlFor={`${fieldIdPrefix}-otherPetType`}>Pet type <span className="required-mark">*</span></label>
            <input
              id={`${fieldIdPrefix}-otherPetType`}
              type="text"
              className={errors.otherPetType ? "input-error" : ""}
              value={formState.pet.otherPetType}
              onChange={(event) => updatePet("otherPetType", event.target.value)}
              placeholder="Enter your pet type"
            />
            {errors.otherPetType ? <div className="field-error">{errors.otherPetType}</div> : null}
          </div>
        ) : usesBreedApi && !breedLoadError ? (
          <div className="field">
            <label htmlFor={`${fieldIdPrefix}-breed`}>Breed</label>
            <input
              id={`${fieldIdPrefix}-breed`}
              type="text"
              list={breedListId}
              value={formState.pet.breed}
              onChange={(event) => updatePet("breed", event.target.value)}
              disabled={isBreedsLoading}
              placeholder={isBreedsLoading ? "Loading breeds..." : "Search or select breed"}
            />
            <datalist id={breedListId}>
              {selectBreedOptions.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </datalist>
          </div>
        ) : (
          <div className="field">
            <label htmlFor={`${fieldIdPrefix}-breed`}>Breed</label>
            <input
              id={`${fieldIdPrefix}-breed`}
              type="text"
              value={formState.pet.breed}
              onChange={(event) => updatePet("breed", event.target.value)}
              placeholder="Enter breed"
            />
            {usesBreedApi && breedLoadError ? <div className="fhint">Couldn&apos;t load breeds right now. Enter breed manually.</div> : null}
          </div>
        )}
      </div>

      <div className="half">
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-dob`}>Date of birth</label>
          <input id={`${fieldIdPrefix}-dob`} type="date" value={formState.pet.dob} onChange={(event) => updatePet("dob", event.target.value)} />
          <div className="fhint">Approximate is fine</div>
        </div>
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-sex`}>Gender</label>
          <select id={`${fieldIdPrefix}-sex`} value={formState.pet.sex} onChange={(event) => updatePet("sex", event.target.value)}>
            <option value="">Select gender</option>
            {SEX_OPTIONS.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
        </div>
      </div>

      <div className="sdiv">Today&apos;s concern</div>
      <div className="field">
        <label htmlFor={`${fieldIdPrefix}-issue`}>Describe what you&apos;ve noticed <span className="required-mark">*</span></label>
        <textarea
          id={`${fieldIdPrefix}-issue`}
          className={errors.issue ? "input-error" : ""}
          value={formState.pet.issue}
          onChange={(event) => updatePet("issue", event.target.value)}
          placeholder="Enter what you have noticed"
        />
        {errors.issue ? <div className="field-error">{errors.issue}</div> : null}
      </div>

      <fieldset className="field">
        <legend>Symptoms (tick all that apply)</legend>
        <div className="cbgroup">
          {SYMPTOM_OPTIONS.map((option) => (
            <label className="cbitem" key={option.value}>
              <input
                type="checkbox"
                checked={formState.pet.symptoms.includes(option.value)}
                onChange={() => toggleSymptom(option.value)}
              />
              <span>{option.label}</span>
            </label>
          ))}
        </div>
      </fieldset>

      <div className="sdiv">Medical history</div>
      <div className="half">
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-vaccinationStatus`}>Vaccination status</label>
          <select
            id={`${fieldIdPrefix}-vaccinationStatus`}
            value={formState.pet.vaccinationStatus}
            onChange={(event) => updatePet("vaccinationStatus", event.target.value)}
          >
            <option value="">Select vaccination status</option>
            {VACCINATION_OPTIONS.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
        </div>
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-deworming`}>Last deworming</label>
          <input id={`${fieldIdPrefix}-deworming`} type="date" value={formState.pet.deworming} onChange={(event) => updatePet("deworming", event.target.value)} />
        </div>
      </div>

      <div className="field">
        <label htmlFor={`${fieldIdPrefix}-history`}>Past illnesses or surgeries</label>
        <textarea id={`${fieldIdPrefix}-history`} style={{ minHeight: 64 }} value={formState.pet.history} onChange={(event) => updatePet("history", event.target.value)} placeholder="Enter past illnesses or surgeries" />
      </div>
      <div className="field">
        <label htmlFor={`${fieldIdPrefix}-medications`}>Current medications</label>
        <input id={`${fieldIdPrefix}-medications`} type="text" value={formState.pet.medications} onChange={(event) => updatePet("medications", event.target.value)} placeholder="Enter current medications" />
      </div>
      <div className="field">
        <label htmlFor={`${fieldIdPrefix}-allergies`}>Known allergies</label>
        <input id={`${fieldIdPrefix}-allergies`} type="text" value={formState.pet.allergies} onChange={(event) => updatePet("allergies", event.target.value)} placeholder="Enter known allergies" />
      </div>
      <div className="field">
        <label htmlFor={`${fieldIdPrefix}-notes`}>Anything else for the vet</label>
        <textarea id={`${fieldIdPrefix}-notes`} style={{ minHeight: 60 }} value={formState.pet.notes} onChange={(event) => updatePet("notes", event.target.value)} placeholder="Enter anything else for the vet" />
      </div>

      <button type="button" className="cta" onClick={handleContinue} disabled={isSubmitting}>
        Review &amp; pay Rs {BOOKING_TOTAL_PRICE} &rarr;
      </button>
      <p className="cta-note">Almost done. Review your booking summary before paying.</p>
    </div>
    </div>
    </div>
  );
}
