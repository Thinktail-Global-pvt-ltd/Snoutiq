import React, { useEffect, useId, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  ArrowLeft,
} from "lucide-react";
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
import { getAskProfile, saveAskProfile } from "./askProfileStorage";

const DEFAULT_STATE = {
  lead: { ownerName: "", phone: "", species: "", area: "", reason: "" },
  pet: {
    petName: "",
    breed: "",
    otherPetType: "",
    dob: "",
    sex: "",
    dateOfVisit: "",
    timeOfVisit: "",
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
      phone: normalizePhoneInput(pickText(lead.phone, raw.phone, raw.ownerMobile)),
      species: speciesResult.species,
      area: pickText(lead.area, raw.area, raw.location),
      reason: pickText(lead.reason, raw.reason, raw.problemText),
    },
    pet: {
      petName: pickText(pet.petName, raw.petName, raw.name),
      breed: pickText(pet.breed, raw.breed),
      otherPetType: speciesResult.otherPetType,
      dob: pickText(
        pet.dob,
        pet.petDob,
        pet.dateOfBirth,
        pet.birthDate,
        raw.dob,
        raw.petDob,
        raw.dateOfBirth,
        raw.birthDate
      ),
      sex: pickText(pet.sex, raw.sex),
      dateOfVisit: pickText(
        pet.dateOfVisit,
        pet.date_of_visit,
        raw.dateOfVisit,
        raw.date_of_visit
      ),
      timeOfVisit: pickText(
        pet.timeOfVisit,
        pet.time_of_visit,
        raw.timeOfVisit,
        raw.time_of_visit
      ),
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

const pickArray = (...values) => {
  for (const value of values) {
    if (!Array.isArray(value)) continue;
    const normalized = value.map((item) => String(item || "").trim()).filter(Boolean);
    if (normalized.length > 0) return normalized;
  }
  return [];
};

const mergeNormalizedStates = (...values) =>
  values
    .map((value) => normalizeState(value))
    .reduce((current, next) => {
      const speciesResult = normalizeSpecies(
        pickText(next.lead.species, current.lead.species),
        pickText(next.pet.otherPetType, current.pet.otherPetType)
      );
      const paymentStatus =
        pickText(next.booking.paymentStatus, current.booking.paymentStatus) || "pending";

      return {
        lead: {
          ownerName: pickText(next.lead.ownerName, current.lead.ownerName),
          phone: normalizePhoneInput(pickText(next.lead.phone, current.lead.phone)),
          species: speciesResult.species,
          area: pickText(next.lead.area, current.lead.area),
          reason: pickText(
            next.lead.reason,
            next.pet.issue,
            current.lead.reason,
            current.pet.issue
          ),
        },
        pet: {
          petName: pickText(next.pet.petName, current.pet.petName),
          breed: pickText(next.pet.breed, current.pet.breed),
          otherPetType: speciesResult.otherPetType,
          dob: pickText(next.pet.dob, current.pet.dob),
          sex: pickText(next.pet.sex, current.pet.sex),
          dateOfVisit: pickText(next.pet.dateOfVisit, current.pet.dateOfVisit),
          timeOfVisit: pickText(next.pet.timeOfVisit, current.pet.timeOfVisit),
          issue: pickText(
            next.pet.issue,
            next.lead.reason,
            current.pet.issue,
            current.lead.reason
          ),
          symptoms: pickArray(next.pet.symptoms, current.pet.symptoms),
          vaccinationStatus: pickText(
            next.pet.vaccinationStatus,
            current.pet.vaccinationStatus
          ),
          deworming: pickText(next.pet.deworming, current.pet.deworming),
          history: pickText(next.pet.history, current.pet.history),
          medications: pickText(next.pet.medications, current.pet.medications),
          allergies: pickText(next.pet.allergies, current.pet.allergies),
          notes: pickText(next.pet.notes, current.pet.notes),
        },
        booking: {
          bookingId: pickNumber(next.booking.bookingId, current.booking.bookingId),
          userId: pickNumber(next.booking.userId, current.booking.userId),
          petId: pickNumber(next.booking.petId, current.booking.petId),
          latestCompletedStep:
            pickNumber(
              next.booking.latestCompletedStep,
              current.booking.latestCompletedStep
            ) || 0,
          paymentStatus,
          paymentReference: pickText(
            next.booking.paymentReference,
            current.booking.paymentReference
          ),
          bookingReference: pickText(
            next.booking.bookingReference,
            current.booking.bookingReference
          ),
        },
        progress: {
          petDetailsSubmitted:
            Boolean(next.progress.petDetailsSubmitted) ||
            Boolean(current.progress.petDetailsSubmitted) ||
            Boolean(next.booking.petId) ||
            Boolean(current.booking.petId),
          paymentCompleted:
            Boolean(next.progress.paymentCompleted) ||
            Boolean(current.progress.paymentCompleted) ||
            paymentStatus.toLowerCase() === "paid",
        },
      };
    }, DEFAULT_STATE);

const buildHiddenPrefillFields = (source) => {
  const state = normalizeState(source);
  return {
    ownerName: Boolean(state.lead.ownerName),
    phone: Boolean(state.lead.phone),
    species: Boolean(state.lead.species),
    area: Boolean(state.lead.area),
    reason: Boolean(state.lead.reason),
    petName: Boolean(state.pet.petName),
    breed: Boolean(state.pet.breed),
    otherPetType: Boolean(state.pet.otherPetType),
    dob: Boolean(state.pet.dob),
    sex: Boolean(state.pet.sex),
    issue: Boolean(state.pet.issue),
    vaccinationStatus: Boolean(state.pet.vaccinationStatus),
    deworming: Boolean(state.pet.deworming),
    history: Boolean(state.pet.history),
    medications: Boolean(state.pet.medications),
    allergies: Boolean(state.pet.allergies),
    notes: Boolean(state.pet.notes),
  };
};

const buildProfilePrefillState = () => {
  const profile = getAskProfile();
  return normalizeState({
    userId: profile.userId || "",
    petId: profile.petId || "",
    ownerName: profile.ownerName || "",
    phone: profile.phone || "",
    species: profile.petType || "",
    type: profile.petType || "",
    petName: profile.petName || "",
    breed: profile.breed || "",
    dob: profile.dob || "",
    sex: profile.gender || "",
    vaccinationStatus: profile.vaccinatedYesNo || "",
    deworming: profile.dewormingYesNo || "",
    location: profile.location || "",
    lat: profile.lat || "",
    long: profile.long || "",
    reason: profile.lastProblemText || "",
    issue: profile.lastProblemText || "",
  });
};

const normalizePhoneInput = (value) =>
  String(value || "")
    .replace(/\D/g, "")
    .slice(0, 10);

const isValidPhone = (value) => normalizePhoneInput(value).length === 10;

export default function VetNearPetDetails({ initialState, onBack, onContinue }) {
  const navigate = useNavigate();
  const fieldIdPrefix = useId();
  const today = new Date().toISOString().slice(0, 10);
  const [formState, setFormState] = useState(() => buildProfilePrefillState());
  const [hiddenPrefillFields, setHiddenPrefillFields] = useState(() =>
    buildHiddenPrefillFields(buildProfilePrefillState())
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
  const isPrefilled = (field) => Boolean(hiddenPrefillFields[field]);
  const selectBreedOptions =
    formState.pet.breed && !breedOptions.includes(formState.pet.breed)
      ? [formState.pet.breed, ...breedOptions]
      : breedOptions;
  const showOwnerNameField = !isPrefilled("ownerName");
  const showPhoneField = !isPrefilled("phone");
  const showSpeciesField = !isPrefilled("species");
  const showAreaField = !isPrefilled("area");
  const showReasonField = !isPrefilled("reason");
  const showPetNameField = !isPrefilled("petName");
  const showBreedField = !showOtherPetTypeField && !isPrefilled("breed");
  const showOtherPetTypeInput = showOtherPetTypeField && !isPrefilled("otherPetType");
  const showDobField = !isPrefilled("dob");
  const showSexField = !isPrefilled("sex");
  const showIssueField = !isPrefilled("issue");
  const showVaccinationField = !isPrefilled("vaccinationStatus");
  const showDewormingField = !isPrefilled("deworming");
  const showHistoryField = !isPrefilled("history");
  const showMedicationsField = !isPrefilled("medications");
  const showAllergiesField = !isPrefilled("allergies");
  const showNotesField = !isPrefilled("notes");
  const showYourDetailsSection =
    showOwnerNameField ||
    showPhoneField ||
    showSpeciesField ||
    showAreaField ||
    showReasonField;
  const showPetProfileSection =
    showPetNameField ||
    showBreedField ||
    showOtherPetTypeInput ||
    showDobField ||
    showSexField;
  const showMedicalHistorySection =
    showVaccinationField ||
    showDewormingField ||
    showHistoryField ||
    showMedicationsField ||
    showAllergiesField ||
    showNotesField;
  const ownerReady = Boolean(
    formState.lead.ownerName.trim() && isValidPhone(formState.lead.phone)
  );
  const petReady = Boolean(formState.lead.species && formState.pet.petName.trim());
  const concernReady = Boolean(formState.pet.issue.trim());
  const summaryPetType = showOtherPetTypeField
    ? formState.pet.otherPetType || "Other"
    : formState.lead.species || "Not selected";
  const hiddenSavedCount = Object.values(hiddenPrefillFields).filter(Boolean).length;
  const hasHiddenSavedDetails = hiddenSavedCount > 0;
  const hiddenSavedSummaryRows = [
    {
      label: "Parent",
      value: [formState.lead.ownerName, formState.lead.phone].filter(Boolean).join(" • "),
    },
    {
      label: "Pet",
      value: [
        formState.pet.petName,
        summaryPetType,
        formState.pet.breed || formState.pet.otherPetType,
      ]
        .filter(Boolean)
        .join(" • "),
    },
    {
      label: "Location",
      value: formState.lead.area,
    },
  ].filter((item) => item.value);
  const completedCount = [ownerReady, petReady, concernReady].filter(Boolean).length;

  useEffect(() => {
    const savedAskProfile = getAskProfile();
    const normalizedInitialState = normalizeState(initialState);
    const profilePrefillState = buildProfilePrefillState();

    console.groupCollapsed("[VetNearPetDetails] incoming details");
    console.log("initialState prop:", initialState ?? null);
    console.log("saved ask profile:", savedAskProfile);
    console.log("normalized initialState:", normalizedInitialState);
    console.log("profile prefill state:", profilePrefillState);
    console.log(
      "hidden prefill fields from profile:",
      buildHiddenPrefillFields(profilePrefillState),
    );
    console.groupEnd();
  }, [initialState]);

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
    const nextValue = field === "phone" ? normalizePhoneInput(value) : value;
    setFormState((current) => ({ ...current, lead: { ...current.lead, [field]: nextValue } }));
    clearError(field);
  };

  const updatePet = (field, value) => {
    setFormState((current) => ({ ...current, pet: { ...current.pet, [field]: value } }));
    clearError(field);
  };

  const revealAllPrefilledFields = () => {
    setHiddenPrefillFields((current) =>
      Object.fromEntries(Object.keys(current).map((key) => [key, false]))
    );
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
    if (!formState.lead.species) nextErrors.species = "Please select your pet type.";
    if (!formState.pet.petName.trim()) nextErrors.petName = "Please enter your pet's name.";
    if (!formState.pet.dateOfVisit) nextErrors.dateOfVisit = "Please select the visit date.";
    if (!formState.pet.timeOfVisit) nextErrors.timeOfVisit = "Please select the visit time.";
    if (!formState.pet.issue.trim()) nextErrors.issue = "Please describe today's concern.";
    if (!formState.pet.vaccinationStatus) {
      nextErrors.vaccinationStatus = "Please select vaccination status.";
    }
    if (!formState.pet.deworming) {
      nextErrors.deworming = "Please select last deworming date.";
    }
    if (showOtherPetTypeField && !formState.pet.otherPetType.trim()) {
      nextErrors.otherPetType = "Please enter your pet type.";
    }
    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const handleContinue = async () => {
    const isValid = validate();

    console.groupCollapsed("[VetNearPetDetails] continue clicked");
    console.log("current formState:", formState);
    console.log("hiddenPrefillFields state:", hiddenPrefillFields);
    console.log("validation passed:", isValid);
    console.groupEnd();

    if (!isValid) return;

    try {
      setIsSubmitting(true);
      let bookingId = formState.booking.bookingId;
      let userId = formState.booking.userId;
      let latestCompletedStep = formState.booking.latestCompletedStep;

      if (!bookingId || !userId) {
        const leadReason = formState.lead.reason || formState.pet.issue;
        console.log("[VetNearPetDetails] submitLeadStep payload:", {
          name: formState.lead.ownerName,
          phone: formState.lead.phone,
          species: formState.lead.species,
          area: formState.lead.area,
          reason: leadReason,
        });
        const leadResponse = await submitLeadStep({
          name: formState.lead.ownerName,
          phone: formState.lead.phone,
          species: formState.lead.species,
          area: formState.lead.area,
          reason: leadReason,
        });
        console.log("[VetNearPetDetails] submitLeadStep response:", leadResponse);
        if (!leadResponse?.ok || !leadResponse.bookingId || !leadResponse.userId) {
          throw new Error("Booking could not be created.");
        }
        bookingId = leadResponse.bookingId;
        userId = leadResponse.userId;
        latestCompletedStep = leadResponse.latestCompletedStep ?? 1;
      }

      console.log("[VetNearPetDetails] submitPetDetailsStep payload:", {
        bookingId,
        petData: formState.pet,
        species: formState.lead.species,
      });
      const petResponse = await submitPetDetailsStep({
        bookingId,
        petData: formState.pet,
        species: formState.lead.species,
      });
      console.log("[VetNearPetDetails] submitPetDetailsStep response:", petResponse);
      if (!petResponse?.ok) throw new Error("Pet details could not be submitted.");

      const nextState = normalizeState({
        lead: {
          ...formState.lead,
          reason: formState.lead.reason || formState.pet.issue,
        },
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

      saveAskProfile({
        ownerName: formState.lead.ownerName,
        phone: formState.lead.phone,
        petName: formState.pet.petName,
        petType: formState.lead.species,
        breed: formState.pet.breed,
        dob: formState.pet.dob,
        gender: formState.pet.sex,
        location: formState.lead.area,
        lastProblemText: formState.pet.issue,
        ...(nextState.booking.userId ? { userId: nextState.booking.userId } : {}),
        ...(nextState.booking.petId ? { petId: nextState.booking.petId } : {}),
      });

      console.log("[VetNearPetDetails] next navigation state:", nextState);

      setFormState(nextState);
      if (onContinue) {
        onContinue(nextState);
        return;
      }
      navigate("/vet-near-me-payment", { state: nextState });
    } catch (error) {
      window.alert(error?.message || "Something went wrong. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleBack = () => {
    if (onBack) {
      onBack();
      return;
    }
    navigate("/ask", { replace: true });
  };

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.16),_transparent_24%),linear-gradient(180deg,#f7faff_0%,#edf4ff_46%,#f5f8ff_100%)] pb-20 text-slate-900">
      <div className="sticky top-0 z-30 border-b border-white/70 bg-white/88 backdrop-blur-xl">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 md:px-6">
          <button
            type="button"
            onClick={handleBack}
            className="inline-flex items-center gap-2 rounded-full border border-[#d7e3ff] bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-[#b9cfff] hover:text-[#2457ff]"
          >
            <ArrowLeft size={16} />
            Back
          </button>
        </div>
      </div>

      <div className="mx-auto max-w-5xl px-4 py-6 md:px-6 md:py-8">
  
        <div
          className={`mt-6 rounded-[30px] border border-[#d6e3ff] bg-white/95 p-5 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)] md:p-7
            [&_.step-back]:hidden
            [&_h2]:text-[28px] [&_h2]:font-semibold [&_h2]:tracking-tight [&_h2]:text-slate-950
            [&_.sdiv]:mb-4 [&_.sdiv]:mt-8 [&_.sdiv]:inline-flex [&_.sdiv]:items-center [&_.sdiv]:gap-2 [&_.sdiv]:rounded-full [&_.sdiv]:border [&_.sdiv]:border-[#d7e3ff] [&_.sdiv]:bg-[#f6f9ff] [&_.sdiv]:px-3 [&_.sdiv]:py-1 [&_.sdiv]:text-[11px] [&_.sdiv]:font-semibold [&_.sdiv]:uppercase [&_.sdiv]:tracking-[0.14em] [&_.sdiv]:text-[#2457ff]
            [&_.half]:grid [&_.half]:gap-5 md:[&_.half]:grid-cols-2
            [&_.field]:space-y-2
            [&_label]:block [&_label]:text-[11px] [&_label]:font-semibold [&_label]:uppercase [&_label]:tracking-[0.16em] [&_label]:text-slate-500
            [&_legend]:mb-2 [&_legend]:block [&_legend]:text-[11px] [&_legend]:font-semibold [&_legend]:uppercase [&_legend]:tracking-[0.16em] [&_legend]:text-slate-500
            [&_.required-mark]:text-red-500
            [&_input]:w-full [&_input]:rounded-2xl [&_input]:border [&_input]:border-[#d6e3ff] [&_input]:bg-[#fbfdff] [&_input]:px-4 [&_input]:py-3 [&_input]:text-sm [&_input]:text-slate-900 [&_input]:shadow-[0_1px_2px_rgba(15,23,42,0.03)] [&_input]:outline-none [&_input]:transition
            [&_select]:w-full [&_select]:appearance-none [&_select]:rounded-2xl [&_select]:border [&_select]:border-[#d6e3ff] [&_select]:bg-[#fbfdff] [&_select]:px-4 [&_select]:py-3 [&_select]:pr-10 [&_select]:text-sm [&_select]:text-slate-900 [&_select]:shadow-[0_1px_2px_rgba(15,23,42,0.03)] [&_select]:outline-none [&_select]:transition
            [&_textarea]:w-full [&_textarea]:min-h-[120px] [&_textarea]:resize-none [&_textarea]:rounded-2xl [&_textarea]:border [&_textarea]:border-[#d6e3ff] [&_textarea]:bg-[#fbfdff] [&_textarea]:px-4 [&_textarea]:py-3 [&_textarea]:text-sm [&_textarea]:text-slate-900 [&_textarea]:shadow-[0_1px_2px_rgba(15,23,42,0.03)] [&_textarea]:outline-none [&_textarea]:transition
            [&_input:focus]:border-[#2457ff] [&_input:focus]:ring-4 [&_input:focus]:ring-[#4f6bff]/12
            [&_select:focus]:border-[#2457ff] [&_select:focus]:ring-4 [&_select:focus]:ring-[#4f6bff]/12
            [&_textarea:focus]:border-[#2457ff] [&_textarea:focus]:ring-4 [&_textarea:focus]:ring-[#4f6bff]/12
            [&_.input-error]:border-red-300 [&_.input-error]:ring-4 [&_.input-error]:ring-red-100
            [&_.field-error]:text-xs [&_.field-error]:font-medium [&_.field-error]:text-red-600
            [&_.fhint]:text-xs [&_.fhint]:text-slate-500
            [&_.cbgroup]:grid [&_.cbgroup]:gap-3 md:[&_.cbgroup]:grid-cols-2
            [&_.cbitem]:flex [&_.cbitem]:cursor-pointer [&_.cbitem]:items-center [&_.cbitem]:gap-3 [&_.cbitem]:rounded-2xl [&_.cbitem]:border [&_.cbitem]:border-[#e2eafc] [&_.cbitem]:bg-white [&_.cbitem]:px-4 [&_.cbitem]:py-3 [&_.cbitem]:text-sm [&_.cbitem]:font-medium [&_.cbitem]:text-slate-700 [&_.cbitem]:transition hover:[&_.cbitem]:border-[#bfd0ff]
            [&_.cbitem>input]:h-4 [&_.cbitem>input]:w-4 [&_.cbitem>input]:rounded [&_.cbitem>input]:border-slate-300 [&_.cbitem>input]:text-[#2457ff] [&_.cbitem>input]:focus:ring-[#2457ff]
            [&_.cta]:mt-7 [&_.cta]:inline-flex [&_.cta]:w-full [&_.cta]:items-center [&_.cta]:justify-center [&_.cta]:rounded-2xl [&_.cta]:bg-[linear-gradient(135deg,#2457ff_0%,#1d4ed8_100%)] [&_.cta]:px-4 [&_.cta]:py-4 [&_.cta]:text-sm [&_.cta]:font-semibold [&_.cta]:text-white [&_.cta]:shadow-[0_18px_35px_-18px_rgba(37,99,235,0.75)] hover:[&_.cta]:translate-y-[-1px] disabled:[&_.cta]:cursor-not-allowed disabled:[&_.cta]:opacity-60
            [&_.cta-note]:mt-3 [&_.cta-note]:text-center [&_.cta-note]:text-xs [&_.cta-note]:text-slate-500`}
        >

      {showPetProfileSection ? <div className="sdiv">Pet profile</div> : null}

      {showDobField || showSexField ? (
        <div className="half">
          {showSexField ? (
            <div className="field">
              <label htmlFor={`${fieldIdPrefix}-sex`}>Gender</label>
              <select id={`${fieldIdPrefix}-sex`} value={formState.pet.sex} onChange={(event) => updatePet("sex", event.target.value)}>
                <option value="">Select gender</option>
                {SEX_OPTIONS.map((option) => (
                  <option key={option} value={option}>{option}</option>
                ))}
              </select>
            </div>
          ) : null}
        </div>
      ) : null}

      <div className="sdiv">Visit schedule</div>
      <div className="half">
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-dateOfVisit`}>Preferred visit date <span className="required-mark">*</span></label>
          <input
            id={`${fieldIdPrefix}-dateOfVisit`}
            type="date"
            min={today}
            className={errors.dateOfVisit ? "input-error" : ""}
            value={formState.pet.dateOfVisit}
            onChange={(event) => updatePet("dateOfVisit", event.target.value)}
          />
          {errors.dateOfVisit ? <div className="field-error">{errors.dateOfVisit}</div> : null}
        </div>
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-timeOfVisit`}>Preferred visit time <span className="required-mark">*</span></label>
          <input
            id={`${fieldIdPrefix}-timeOfVisit`}
            type="time"
            className={errors.timeOfVisit ? "input-error" : ""}
            value={formState.pet.timeOfVisit}
            onChange={(event) => updatePet("timeOfVisit", event.target.value)}
          />
          {errors.timeOfVisit ? <div className="field-error">{errors.timeOfVisit}</div> : null}
        </div>
      </div>

      {showMedicalHistorySection ? <div className="sdiv">Medical history</div> : null}
      {showVaccinationField || showDewormingField ? (
        <div className="half">
          {showVaccinationField ? (
            <div className="field">
              <label htmlFor={`${fieldIdPrefix}-vaccinationStatus`}>
                Vaccination status <span className="required-mark">*</span>
              </label>
              <select
                id={`${fieldIdPrefix}-vaccinationStatus`}
                className={errors.vaccinationStatus ? "input-error" : ""}
                value={formState.pet.vaccinationStatus}
                onChange={(event) => updatePet("vaccinationStatus", event.target.value)}
              >
                <option value="">Select vaccination status</option>
                {VACCINATION_OPTIONS.map((option) => (
                  <option key={option} value={option}>{option}</option>
                ))}
              </select>
              {errors.vaccinationStatus ? (
                <div className="field-error">{errors.vaccinationStatus}</div>
              ) : null}
            </div>
          ) : null}
          {showDewormingField ? (
            <div className="field">
              <label htmlFor={`${fieldIdPrefix}-deworming`}>
                Last deworming <span className="required-mark">*</span>
              </label>
              <input
                id={`${fieldIdPrefix}-deworming`}
                type="date"
                className={errors.deworming ? "input-error" : ""}
                value={formState.pet.deworming}
                onChange={(event) => updatePet("deworming", event.target.value)}
              />
              {errors.deworming ? (
                <div className="field-error">{errors.deworming}</div>
              ) : null}
            </div>
          ) : null}
        </div>
      ) : null}

      {showHistoryField ? (
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-history`}>Past illnesses or surgeries</label>
          <textarea id={`${fieldIdPrefix}-history`} style={{ minHeight: 64 }} value={formState.pet.history} onChange={(event) => updatePet("history", event.target.value)} placeholder="Enter past illnesses or surgeries" />
        </div>
      ) : null}
      {showMedicationsField ? (
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-medications`}>Current medications</label>
          <input id={`${fieldIdPrefix}-medications`} type="text" value={formState.pet.medications} onChange={(event) => updatePet("medications", event.target.value)} placeholder="Enter current medications" />
        </div>
      ) : null}
      {showAllergiesField ? (
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-allergies`}>Known allergies</label>
          <input id={`${fieldIdPrefix}-allergies`} type="text" value={formState.pet.allergies} onChange={(event) => updatePet("allergies", event.target.value)} placeholder="Enter known allergies" />
        </div>
      ) : null}
      {showNotesField ? (
        <div className="field">
          <label htmlFor={`${fieldIdPrefix}-notes`}>Anything else for the vet</label>
          <textarea id={`${fieldIdPrefix}-notes`} style={{ minHeight: 60 }} value={formState.pet.notes} onChange={(event) => updatePet("notes", event.target.value)} placeholder="Enter anything else for the vet" />
        </div>
      ) : null}

      <button type="button" className="cta" onClick={handleContinue} disabled={isSubmitting}>
        Review &amp; pay Rs {BOOKING_TOTAL_PRICE} &rarr;
      </button>
      <p className="cta-note">Almost done. Review your booking summary before paying.</p>
    </div>
    </div>
    </div>
  );
}
