"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  Check,
  ChevronDown,
  Copy,
  CreditCard,
  IndianRupee,
  Link2,
  Mail,
  MessageCircle,
  Phone,
  User,
  PawPrint,
  Search,
} from "lucide-react";
import Swal from "sweetalert2";
import { useNewDoctorAuth } from "./NewDoctorAuth";
import { startDoctorPendingPrescription } from "./doctorPendingPrescriptionService";
import { openWhatsAppLaunchUrl } from "./whatsAppLaunch";
import {
  clearStoredDoctorSelectedParent,
  isExistingParentFlowSearch,
  readStoredDoctorSelectedParent,
  writeStoredDoctorSelectedParent,
} from "./selectedParentStorage";

const TOTAL_STEPS = 2;
const DOG_BREEDS_URL = "https://snoutiq.com/backend/api/dog-breeds/all";
const CAT_BREEDS_URL = "https://snoutiq.com/backend/api/cat-breeds/with-indian";
const RECEPTIONIST_PATIENTS_URL =
  "https://snoutiq.com/backend/api/receptionist/patients";
const EXISTING_PAYMENT_LINK_URL =
  "https://snoutiq.com/backend/api/receptionist/patients/existing-payment-link";
const RECEPTIONIST_CONSULT_SESSIONS_URL =
  "https://snoutiq.com/backend/api/receptionist/consult-sessions";
const PAYMENT_STATUS_POLL_INTERVAL_MS = 5000;
const POLL_STATE_KEY = "newDoctor.pendingPaymentPoll";

const writePollingState = (doctorId, data) => {
  try {
    localStorage.setItem(
      `${POLL_STATE_KEY}:${doctorId}`,
      JSON.stringify({ ...data, savedAt: Date.now() }),
    );
  } catch {}
};

const readPollingState = (doctorId) => {
  try {
    const raw = localStorage.getItem(`${POLL_STATE_KEY}:${doctorId}`);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (Date.now() - (parsed.savedAt || 0) > 5 * 60 * 1000) {
      localStorage.removeItem(`${POLL_STATE_KEY}:${doctorId}`);
      return null;
    }
    return parsed;
  } catch {
    return null;
  }
};

const clearPollingState = (doctorId) => {
  try {
    localStorage.removeItem(`${POLL_STATE_KEY}:${doctorId}`);
  } catch {}
};

const fieldBase =
  "h-[50px] w-full rounded-2xl border border-[#d7dee8] bg-white px-4 text-[15px] text-[#1e293b] outline-none placeholder:text-[#94a3b8] shadow-sm focus:border-[#16a34a] focus:bg-[#fcfffd] focus:ring-0";

const sectionLabel =
  "mb-3 text-[12px] font-bold uppercase tracking-[0.08em] text-[#475467]";

const primaryBtn =
  "flex h-[54px] w-full items-center justify-center gap-2 rounded-2xl bg-[#2fd161] px-4 text-[17px] font-bold text-white shadow-[0_10px_22px_rgba(47,209,97,0.24)] active:scale-[0.99] transition-transform";

const formatBreedLabel = (value) =>
  String(value || "")
    .trim()
    .replace(/\s+/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase());

const normalizeOptionalId = (value) => {
  const normalized = String(value ?? "").trim();
  return normalized && normalized !== "null" && normalized !== "undefined"
    ? normalized
    : "";
};

const normalizeText = (value) => String(value ?? "").trim();
const appendFormDataIfPresent = (formData, key, value) => {
  const normalizedValue = normalizeText(value);
  if (normalizedValue) {
    formData.append(key, normalizedValue);
  }
};
const normalizePetTypeValue = (value) =>
  normalizeText(value).toLowerCase().replace(/\s+/g, " ");
const normalizeStatusText = (value) =>
  String(value ?? "")
    .trim()
    .toLowerCase();
const isPlaceholderBreedValue = (value) =>
  ["unknown", "na", "n/a", "not available"].includes(
    normalizeText(value).toLowerCase(),
  );

const isTruthyFlag = (value) => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;

  const normalized = normalizeStatusText(value);
  return normalized === "1" || normalized === "true" || normalized === "yes";
};

const getRequestResponsePayload = (requestResponse = {}) =>
  requestResponse?.data && typeof requestResponse.data === "object"
    ? requestResponse.data
    : requestResponse;

const getPendingPrescriptionMeta = (requestResponse = {}) => {
  const payload = getRequestResponsePayload(requestResponse);
  const userPayload =
    payload?.user && typeof payload.user === "object"
      ? payload.user
      : requestResponse?.user;
  const petPayload =
    payload?.pet && typeof payload.pet === "object"
      ? payload.pet
      : requestResponse?.pet;

  return {
    consultationId:
      requestResponse?.consultation_id ??
      requestResponse?.consultationId ??
      payload?.consultation_id ??
      payload?.consultationId ??
      null,
    userId: normalizeOptionalId(
      requestResponse?.user_id ??
        requestResponse?.userId ??
        payload?.user_id ??
        payload?.userId ??
        userPayload?.id,
    ),
    petId: normalizeOptionalId(
      requestResponse?.pet_id ??
        requestResponse?.petId ??
        payload?.pet_id ??
        payload?.petId ??
        petPayload?.id,
    ),
    channelName: normalizeText(
      requestResponse?.channel_name ??
        payload?.channel_name ??
        payload?.call_session ??
        payload?.channel ??
        "",
    ),
  };
};

const getConsultSessionPayload = (requestResponse = {}) => {
  const payload = getRequestResponsePayload(requestResponse);
  const consultSession =
    payload?.consult_session && typeof payload.consult_session === "object"
      ? payload.consult_session
      : requestResponse?.consult_session;

  return consultSession && typeof consultSession === "object"
    ? consultSession
    : null;
};

const copyTextToClipboard = async (text) => {
  const normalized = normalizeText(text);
  if (!normalized) return false;

  if (navigator?.clipboard?.writeText) {
    await navigator.clipboard.writeText(normalized);
    return true;
  }

  return false;
};

const buildPendingPrescriptionPatientData = (form, requestResponse = {}) => {
  const payload = getRequestResponsePayload(requestResponse);
  const userPayload =
    payload?.user && typeof payload.user === "object" ? payload.user : {};
  const petPayload =
    payload?.pet && typeof payload.pet === "object" ? payload.pet : {};
  const formPetType = normalizePetTypeValue(form.petType);
  const responsePetType =
    normalizePetTypeValue(petPayload?.type) ||
    normalizePetTypeValue(petPayload?.pet_type);
  const formBreed = normalizeText(form.breed);
  const responseBreed = normalizeText(petPayload?.breed);

  return {
    parentName:
      normalizeText(form.parentName) || normalizeText(userPayload?.name),
    phone: normalizeText(form.phone) || normalizeText(userPayload?.phone),
    petName:
      normalizeText(form.petName) ||
      normalizeText(petPayload?.name) ||
      normalizeText(petPayload?.pet_name),
    petType: formPetType || (responsePetType === "dog" ? "" : responsePetType),
    breed:
      formBreed ||
      (isPlaceholderBreedValue(responseBreed) ? "" : responseBreed),
    gender:
      normalizeText(form.gender) ||
      normalizeText(petPayload?.pet_gender) ||
      normalizeText(petPayload?.gender),
    age: normalizeText(form.age),
    weight:
      normalizeText(form.weight) ||
      normalizeText(petPayload?.weight) ||
      normalizeText(petPayload?.pet_weight) ||
      normalizeText(petPayload?.weight_kg),
  };
};

const isPendingPrescriptionLockedStatus = (statusPayload = {}) =>
  normalizeStatusText(statusPayload?.payment_status) === "paid" &&
  isTruthyFlag(statusPayload?.prescription_required) &&
  normalizeStatusText(statusPayload?.prescription_status) === "pending" &&
  isTruthyFlag(statusPayload?.lock_until_submit);

const formatPetTypeForRequest = (value) => {
  const normalized = String(value || "")
    .trim()
    .toLowerCase();
  if (!normalized) return "";
  return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const buildRequestErrorMessage = (
  responsePayload,
  fallback = "Failed to send payment link.",
) => {
  const primary = String(responsePayload?.message || fallback).trim();
  const errorEntries =
    responsePayload?.errors && typeof responsePayload.errors === "object"
      ? Object.values(responsePayload.errors).flat()
      : [];
  const details = errorEntries
    .map((item) => String(item || "").trim())
    .filter(Boolean);

  if (!details.length) {
    return primary;
  }

  const uniqueDetails = Array.from(
    new Set(details.filter((item) => item !== primary)),
  );

  return uniqueDetails.length
    ? [primary, ...uniqueDetails].join("\n")
    : primary;
};

function normalizeBreedOptions(payload) {
  const collected = [];

  // DOG API special shape: { status: "success", breeds: { "affenpinscher": [], ... } }
  if (
    payload?.breeds &&
    typeof payload.breeds === "object" &&
    !Array.isArray(payload.breeds)
  ) {
    Object.keys(payload.breeds).forEach((breedKey) => {
      const label = formatBreedLabel(breedKey);
      if (label) {
        collected.push({
          id: breedKey,
          label,
        });
      }
    });
  }

  // Generic walker for other possible shapes, including cat API
  const walk = (node) => {
    if (!node) return;

    if (Array.isArray(node)) {
      node.forEach(walk);
      return;
    }

    if (typeof node === "string") {
      const label = formatBreedLabel(node);
      if (label) {
        collected.push({ id: label, label });
      }
      return;
    }

    if (typeof node !== "object") return;

    const directLabel =
      node.name ||
      node.breed_name ||
      node.breed ||
      node.title ||
      node.label ||
      node.pet_breed ||
      "";

    if (typeof directLabel === "string" && directLabel.trim()) {
      const label = formatBreedLabel(directLabel);
      const id = String(node.id ?? node.value ?? label);
      collected.push({ id, label });
    }

    Object.values(node).forEach((value) => {
      if (Array.isArray(value) || (value && typeof value === "object")) {
        walk(value);
      }
    });
  };

  walk(payload);

  const uniqueMap = new Map();
  collected.forEach((item) => {
    const key = item.label.toLowerCase();
    if (!uniqueMap.has(key)) {
      uniqueMap.set(key, item);
    }
  });

  return Array.from(uniqueMap.values()).sort((a, b) =>
    a.label.localeCompare(b.label),
  );
}

export default function NewDoctorNewRequestView() {
  const location = useLocation();
  const navigate = useNavigate();
  const breedMenuRef = useRef(null);
  const hasHandledPaymentRef = useRef(false);
  const isPaymentPollingRef = useRef(false);
  const { auth } = useNewDoctorAuth();

  const doctorId =
    auth?.doctor_id || auth?.doctor?.id || auth?.doctor?.doctor_id;
  const authToken = auth?.token || auth?.access_token || "";
  const hasExistingParentQuery = isExistingParentFlowSearch(location.search);
  const selectedParentFromState = location.state?.parent || null;
  const selectedParent = useMemo(() => {
    if (selectedParentFromState) {
      return selectedParentFromState;
    }

    if (!hasExistingParentQuery) {
      return null;
    }

    return readStoredDoctorSelectedParent();
  }, [hasExistingParentQuery, selectedParentFromState]);
  const existingUserId = normalizeOptionalId(selectedParent?.userId);
  const existingPetId =
    normalizeOptionalId(selectedParent?.petId) ||
    normalizeOptionalId(selectedParent?.selectedPet?.id) ||
    normalizeOptionalId(selectedParent?.pets?.[0]?.id);
  const isExistingParentFlow = Boolean(existingUserId && existingPetId);

  const [step, setStep] = useState(0);
  const [form, setForm] = useState({
    phone: "",
    email: "",
    amount: "",
    parentName: "",
    petName: "",
    petType: "",
    breed: "",
    gender: "",
    age: "",
    weight: "",
  });

  const [breedOptions, setBreedOptions] = useState([]);
  const [breedsLoading, setBreedsLoading] = useState(false);
  const [breedQuery, setBreedQuery] = useState("");
  const [isBreedMenuOpen, setIsBreedMenuOpen] = useState(false);
  const [requestResponse, setRequestResponse] = useState(null);
  const [consultSessionStatus, setConsultSessionStatus] = useState(null);
  const [isSubmittingRequest, setIsSubmittingRequest] = useState(false);
  const [requestError, setRequestError] = useState("");
  const [restoredPollingDoctorId, setRestoredPollingDoctorId] = useState("");

  useEffect(() => {
    if (!doctorId || restoredPollingDoctorId !== doctorId) return;
    if (step === 1 && requestResponse) {
      writePollingState(doctorId, { step, requestResponse });
    } else {
      clearPollingState(doctorId);
    }
  }, [doctorId, requestResponse, restoredPollingDoctorId, step]);

  useEffect(() => {
    if (!doctorId) return;
    const saved = readPollingState(doctorId);
    if (saved?.step === 1 && saved.requestResponse) {
      setStep(saved.step);
      setRequestResponse(saved.requestResponse);
    } else {
      setStep(0);
      setRequestResponse(null);
    }
    setRestoredPollingDoctorId(doctorId);
  }, [doctorId]);

  useEffect(() => {
    hasHandledPaymentRef.current = false;
    isPaymentPollingRef.current = false;
    setConsultSessionStatus(null);
  }, [requestResponse, step]);

  useEffect(() => {
    if (selectedParent) {
      writeStoredDoctorSelectedParent(selectedParent);
      return;
    }

    if (!hasExistingParentQuery && !selectedParentFromState) {
      clearStoredDoctorSelectedParent();
    }
  }, [hasExistingParentQuery, selectedParent, selectedParentFromState]);

  useEffect(() => {
    if (!selectedParent) {
      return;
    }

    setForm((prev) => ({
      ...prev,
      phone: normalizeText(selectedParent.phone) || prev.phone,
      email: normalizeText(selectedParent.email) || prev.email,
      parentName: normalizeText(selectedParent.name) || prev.parentName,
      petName:
        normalizeText(selectedParent.petName) ||
        normalizeText(selectedParent.selectedPet?.name) ||
        prev.petName,
      petType:
        normalizePetTypeValue(selectedParent.petType) ||
        normalizePetTypeValue(selectedParent.selectedPet?.pet_type) ||
        prev.petType,
      breed:
        normalizeText(selectedParent.breed) ||
        normalizeText(selectedParent.selectedPet?.breed) ||
        prev.breed,
      gender:
        normalizeText(selectedParent.petGender) ||
        normalizeText(selectedParent.selectedPet?.pet_gender) ||
        prev.gender,
      age:
        normalizeText(
          selectedParent.petAge ??
            selectedParent.selectedPet?.pet_age ??
            selectedParent.selectedPet?.pet_age_months,
        ) || prev.age,
      weight:
        normalizeText(
          selectedParent.petWeight ??
            selectedParent.selectedPet?.weight ??
            selectedParent.selectedPet?.pet_weight ??
            selectedParent.selectedPet?.weight_kg,
        ) || prev.weight,
    }));
  }, [selectedParent]);

  const consultSession = useMemo(
    () => getConsultSessionPayload(requestResponse),
    [requestResponse],
  );
  const consultSessionToken = normalizeText(consultSession?.session_id);
  const consultSessionPayload = consultSessionStatus || consultSession || null;
  const consultSessionLandingUrl = normalizeText(
    consultSessionPayload?.landing_url,
  );
  const consultSessionShareMessage = normalizeText(
    consultSessionPayload?.share_message,
  );
  const consultSessionShareWhatsAppUrl = normalizeText(
    consultSessionPayload?.share_whatsapp_url,
  );
  const consultSessionCurrentStatus = normalizeStatusText(
    consultSessionPayload?.status || "pending",
  );
  const isConsultationInitiated = consultSessionCurrentStatus === "initiated";

  const isPhoneLocked =
    isExistingParentFlow && Boolean(normalizeText(form.phone));
  const isEmailLocked =
    isExistingParentFlow && Boolean(normalizeText(form.email));
  const isParentNameLocked =
    isExistingParentFlow && Boolean(normalizeText(form.parentName));
  const isPetNameLocked =
    isExistingParentFlow && Boolean(normalizeText(form.petName));
  const isPetTypeLocked =
    isExistingParentFlow && Boolean(normalizeText(form.petType));
  const isBreedLocked =
    isExistingParentFlow && Boolean(normalizeText(form.breed));
  const isGenderLocked =
    isExistingParentFlow && Boolean(normalizeText(form.gender));
  const isAgeLocked = isExistingParentFlow && Boolean(normalizeText(form.age));
  const lockedFieldClassName = "bg-[#f8fafc] text-[#475467]";

  const filteredBreedOptions = useMemo(() => {
    const query = breedQuery.trim().toLowerCase();
    if (!query) return breedOptions;

    return breedOptions.filter((item) =>
      item.label.toLowerCase().includes(query),
    );
  }, [breedOptions, breedQuery]);

  const goNext = () => setStep((prev) => Math.min(prev + 1, TOTAL_STEPS - 1));

  const handleBack = async () => {
    if (step > 0) {
      const result = await Swal.fire({
        icon: "warning",
        title: "Go back to the form?",
        text: "If you go back now, the payment waiting screen will close.",
        showCancelButton: true,
        confirmButtonText: "Yes, go back",
        cancelButtonText: "Stay here",
        confirmButtonColor: "#16a34a",
        cancelButtonColor: "#94a3b8",
        reverseButtons: true,
      });

      if (!result.isConfirmed) {
        return;
      }

      clearPollingState(doctorId);
      setStep((prev) => Math.max(prev - 1, 0));
      return;
    }

    if (window.history.length > 1) {
      navigate(-1);
    } else {
      navigate("/counsltflow/dashboard", { replace: true });
    }
  };

  const updateField = (key, value) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  const handlePetTypeChange = (value) => {
    setForm((prev) => ({
      ...prev,
      petType: value,
      breed: "",
    }));
    setBreedQuery("");
    setBreedOptions([]);
    setIsBreedMenuOpen(false);
  };

  const handleBreedSelect = (breedLabel) => {
    setForm((prev) => ({
      ...prev,
      breed: breedLabel,
    }));
    setBreedQuery("");
    setIsBreedMenuOpen(false);
  };

  const handleSendPaymentLink = async () => {
    if (isSubmittingRequest) {
      return;
    }

    setRequestError("");

    if (!form.phone.trim()) {
      setRequestError("Parent WhatsApp Number is required.");
      return;
    }

    if (!form.amount.trim()) {
      setRequestError("Amount is required.");
      return;
    }

    const amountRupees = Number(form.amount);
    if (!Number.isFinite(amountRupees) || amountRupees <= 0) {
      setRequestError("Amount must be greater than 0.");
      return;
    }

    try {
      setIsSubmittingRequest(true);
      clearPollingState(doctorId);

      const clinicId =
        auth?.clinic_id ||
        auth?.doctor?.clinic_id ||
        auth?.doctor?.vet_registeration_id;
      const amountPaise = String(Math.round(amountRupees * 100));
      const requestBody = new FormData();

      let requestUrl = RECEPTIONIST_PATIENTS_URL;

      requestBody.append("clinic_id", String(clinicId));
      if (isExistingParentFlow) {
        requestUrl = EXISTING_PAYMENT_LINK_URL;
        requestBody.append("user_id", existingUserId);
        requestBody.append("pet_id", existingPetId);
      } else {
        appendFormDataIfPresent(requestBody, "name", form.parentName);
        requestBody.append("phone", form.phone.trim());
        if (form.email.trim()) {
          requestBody.append("email", form.email.trim());
        }
        appendFormDataIfPresent(requestBody, "pet_name", form.petName);
        if (form.petType.trim()) {
          requestBody.append("pet_type", formatPetTypeForRequest(form.petType));
        }
        if (form.breed.trim()) {
          requestBody.append("pet_breed", form.breed.trim());
        }
        appendFormDataIfPresent(requestBody, "pet_gender", form.gender);
      }
      requestBody.append("amount_paise", amountPaise);
      requestBody.append("response_time_minutes", "10");
      if (doctorId) {
        requestBody.append("doctor_id", String(doctorId));
      }

      const headers = {
        Accept: "application/json",
      };
      if (authToken) {
        headers.Authorization = `Bearer ${authToken}`;
      }

      const response = await fetch(requestUrl, {
        method: "POST",
        headers,
        body: requestBody,
      });
      const responsePayload = await response.json().catch(() => ({}));
      const consultSessionPayload = getConsultSessionPayload(responsePayload);

      if (
        !response.ok ||
        responsePayload?.success === false ||
        !consultSessionPayload?.session_id
      ) {
        throw new Error(buildRequestErrorMessage(responsePayload));
      }

      setRequestResponse(responsePayload);
      setConsultSessionStatus(consultSessionPayload);
      goNext();
    } catch (error) {
      setRequestResponse(null);
      setConsultSessionStatus(null);
      const message =
        error?.message || "Failed to create consultation link.";
      const normalizedMessage = message.toLowerCase();

      if (
        normalizedMessage.includes("already") ||
        normalizedMessage.includes("exists") ||
        normalizedMessage.includes("registered") ||
        normalizedMessage.includes("phone")
      ) {
        await Swal.fire({
          icon: "error",
          title: "Number has been register",
          text: "This phone number is already registered. Please use another number.",
          confirmButtonText: "OK",
          confirmButtonColor: "#16a34a",
        });
        setRequestError("");
        return;
      }

      await Swal.fire({
        icon: "error",
        title: "Failed",
        text: message,
        confirmButtonText: "OK",
        confirmButtonColor: "#16a34a",
      });
      setRequestError(message);
    } finally {
      setIsSubmittingRequest(false);
    }
  };

  const handlePaymentReceived = useCallback(
    async (requestResponse = {}, statusPayload = {}) => {
      if (hasHandledPaymentRef.current) {
        return;
      }
      const pendingPrescriptionMeta = getPendingPrescriptionMeta(requestResponse);
      const resolvedChannelName =
        normalizeText(
          statusPayload?.channel_name ?? statusPayload?.call_session ?? "",
        ) ||
        normalizeText(pendingPrescriptionMeta.channelName) ||
        normalizeText(requestResponse?.channel_name);

      hasHandledPaymentRef.current = true;
      const resolvedUserId = pendingPrescriptionMeta.userId || existingUserId;
      const resolvedPetId = pendingPrescriptionMeta.petId || existingPetId;

      if (!resolvedUserId) {
        hasHandledPaymentRef.current = false;
        await Swal.fire({
          icon: "error",
          title: "Missing parent details",
          text: "Parent details were not created correctly. Please send the payment link again.",
          confirmButtonText: "OK",
          confirmButtonColor: "#16a34a",
        });
        return;
      }

      const patientData = buildPendingPrescriptionPatientData(
        form,
        requestResponse,
      );

      startDoctorPendingPrescription(doctorId, {
        consultationId: pendingPrescriptionMeta.consultationId,
        userId: resolvedUserId,
        petId: resolvedPetId || "",
        lockUntilSubmit: true,
        patientData,
        paymentStatus: "paid",
        prescriptionRequired: true,
        prescriptionStatus: "pending",
        channelName: resolvedChannelName,
      });

      clearPollingState(doctorId);
      navigate("/counsltflow/digital-prescription", {
        replace: true,
        state: {
          consultationId: pendingPrescriptionMeta.consultationId,
          userId: resolvedUserId,
          petId: resolvedPetId || "",
          paymentCompleted: true,
          lockUntilSubmit: true,
          fromNewRequest: true,
          patientData,
          paymentStatus: "paid",
          prescriptionRequired: true,
          prescriptionStatus: "pending",
          channelName: resolvedChannelName,
        },
      });
    },
    [doctorId, existingPetId, existingUserId, form, navigate],
  );

  const handleShareViaWhatsApp = useCallback(() => {
    if (!consultSessionShareWhatsAppUrl) {
      void Swal.fire({
        icon: "error",
        title: "Link unavailable",
        text: "Consultation share link is not ready yet.",
        confirmButtonColor: "#16a34a",
      });
      return;
    }

    openWhatsAppLaunchUrl(consultSessionShareWhatsAppUrl);
  }, [consultSessionShareWhatsAppUrl]);

  const handleCopyConsultationLink = useCallback(async () => {
    if (!consultSessionLandingUrl) {
      await Swal.fire({
        icon: "error",
        title: "Link unavailable",
        text: "Consultation link is not ready yet.",
        confirmButtonColor: "#16a34a",
      });
      return;
    }

    const copied = await copyTextToClipboard(consultSessionLandingUrl).catch(
      () => false,
    );

    if (copied) {
      await Swal.fire({
        icon: "success",
        title: "Link copied",
        text: "Consultation link copied to clipboard.",
        confirmButtonColor: "#16a34a",
      });
      return;
    }

    await Swal.fire({
      icon: "info",
      title: "Copy this link",
      text: consultSessionLandingUrl,
      confirmButtonColor: "#16a34a",
    });
  }, [consultSessionLandingUrl]);

  useEffect(() => {
    if (
      step !== 1 ||
      !consultSessionToken ||
      !requestResponse ||
      hasHandledPaymentRef.current
    ) {
      return undefined;
    }

    let cancelled = false;

    const pollPaymentStatus = async () => {
      if (
        cancelled ||
        hasHandledPaymentRef.current ||
        isPaymentPollingRef.current
      ) {
        return;
      }

      isPaymentPollingRef.current = true;

      try {
        const response = await fetch(
          `${RECEPTIONIST_CONSULT_SESSIONS_URL}/${encodeURIComponent(
            consultSessionToken,
          )}`,
          {
          headers: {
            Accept: "application/json",
            ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
          },
          },
        );

        if (!response.ok) {
          return;
        }

        const responseData = await response.json().catch(() => ({}));
        const statusPayload =
          responseData?.data && typeof responseData.data === "object"
            ? responseData.data
            : responseData;

        if (!cancelled) {
          setConsultSessionStatus(statusPayload);
        }

        if (!cancelled && normalizeStatusText(statusPayload?.status) === "paid") {
          await handlePaymentReceived(requestResponse, statusPayload);
        }
      } catch {
        // Keep awaiting-payment UI silent and retry on the next tick.
      } finally {
        isPaymentPollingRef.current = false;
      }
    };

    pollPaymentStatus();
    const intervalId = window.setInterval(
      pollPaymentStatus,
      PAYMENT_STATUS_POLL_INTERVAL_MS,
    );

    return () => {
      cancelled = true;
      isPaymentPollingRef.current = false;
      window.clearInterval(intervalId);
    };
  }, [authToken, consultSessionToken, handlePaymentReceived, requestResponse, step]);

  useEffect(() => {
    const handleOutsideClick = (event) => {
      if (!breedMenuRef.current?.contains(event.target)) {
        setIsBreedMenuOpen(false);
      }
    };

    document.addEventListener("mousedown", handleOutsideClick);
    return () => document.removeEventListener("mousedown", handleOutsideClick);
  }, []);

  useEffect(() => {
    let active = true;
    const controller = new AbortController();

    const fetchBreeds = async () => {
      if (!form.petType) {
        setBreedOptions([]);
        setBreedsLoading(false);
        return;
      }

      if (isExistingParentFlow && form.breed.trim()) {
        setBreedOptions([]);
        setBreedsLoading(false);
        return;
      }

      try {
        setBreedsLoading(true);

        const url = form.petType === "dog" ? DOG_BREEDS_URL : CAT_BREEDS_URL;

        const response = await fetch(url, {
          signal: controller.signal,
          headers: {
            Accept: "application/json",
          },
        });

        const data = await response.json().catch(() => ({}));
        if (!active) return;

        const normalized = normalizeBreedOptions(data);
        setBreedOptions(normalized);
      } catch (error) {
        if (error?.name !== "AbortError" && active) {
          setBreedOptions([]);
        }
      } finally {
        if (active) setBreedsLoading(false);
      }
    };

    fetchBreeds();

    return () => {
      active = false;
      controller.abort();
    };
  }, [form.breed, form.petType, isExistingParentFlow]);

  return (
    <div className="min-h-screen bg-[#F8F8F8] flex flex-col">
      <div className="mx-auto w-full min-h-screen bg-[#FCFCFC]">
        {step === 0 && (
          <div className="flex flex-col min-h-screen">
            <Header title="New Consultation" onBack={handleBack} />

            <div className="flex-1 px-5 pt-6 pb-7">
              <div className="space-y-5">
                <div>
                  <p className={sectionLabel}>
                    {isExistingParentFlow
                      ? "Repeat Appointment"
                      : "Mandatory Info"}
                  </p>

                  <div className="space-y-4">
                    <div className="relative">
                      <Phone
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="tel"
                        value={form.phone}
                        onChange={(e) => updateField("phone", e.target.value)}
                        placeholder="Parent WhatsApp Number"
                        maxLength={10}
                        readOnly={isPhoneLocked}
                        className={`${fieldBase} pl-11 ${
                          isPhoneLocked ? lockedFieldClassName : ""
                        }`}
                      />
                    </div>

                    <div className="relative">
                      <IndianRupee
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="number"
                        value={form.amount}
                        onChange={(e) => updateField("amount", e.target.value)}
                        placeholder="Enter Amount (INR)"
                        min="1"
                        className={`${fieldBase} pl-11`}
                      />
                    </div>
                  </div>
                </div>

                <div>
                  <p className={sectionLabel}>
                    {isExistingParentFlow ? "Patient Summary" : "Pet & Parent"}
                  </p>

                  <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                      <div className="relative">
                        <User
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                        />
                        <input
                          type="text"
                          value={form.parentName}
                          onChange={(e) =>
                            updateField("parentName", e.target.value)
                          }
                          placeholder="Pet Parent Name"
                          readOnly={isParentNameLocked}
                          className={`${fieldBase} pl-11 ${
                            isParentNameLocked ? lockedFieldClassName : ""
                          }`}
                        />
                      </div>

                      <div className="relative">
                        <PawPrint
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                        />
                        <input
                          type="text"
                          value={form.petName}
                          onChange={(e) =>
                            updateField("petName", e.target.value)
                          }
                          placeholder="Pet Name"
                          readOnly={isPetNameLocked}
                          className={`${fieldBase} pl-11 ${
                            isPetNameLocked ? lockedFieldClassName : ""
                          }`}
                        />
                      </div>
                    </div>
                    <div className="relative">
                      <Mail
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="email"
                        value={form.email}
                        onChange={(e) => updateField("email", e.target.value)}
                        placeholder="Parent Email"
                        readOnly={isEmailLocked}
                        className={`${fieldBase} pl-11 ${
                          isEmailLocked ? lockedFieldClassName : ""
                        }`}
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      {/* Pet Type full width for better UX */}
                      <div className="relative">
                        <select
                          value={form.petType}
                          onChange={(e) => handlePetTypeChange(e.target.value)}
                          disabled={isPetTypeLocked}
                          className={`${fieldBase} appearance-none pr-9 ${
                            isPetTypeLocked ? lockedFieldClassName : ""
                          }`}
                        >
                          <option value="">Select Pet Type</option>
                          <option value="dog">Dog</option>
                          <option value="cat">Cat</option>
                        </select>

                        <ChevronDown
                          size={16}
                          className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-700"
                        />
                      </div>

                      {/* Breed full width for cleaner dropdown UX */}
                      <div className="relative" ref={breedMenuRef}>
                        <button
                          type="button"
                          disabled={!form.petType || isBreedLocked}
                          onClick={() => {
                            if (!form.petType || isBreedLocked) return;
                            setIsBreedMenuOpen((prev) => !prev);
                          }}
                          className={`${fieldBase} flex items-center justify-between text-left ${
                            !form.petType
                              ? "text-[#8a94a6]"
                              : isBreedLocked
                                ? lockedFieldClassName
                                : "text-slate-700"
                          }`}
                        >
                          <span className="truncate">
                            {form.breed
                              ? form.breed
                              : !form.petType
                                ? "Select Pet Type First"
                                : breedsLoading
                                  ? "Loading breeds..."
                                  : "Select Breed"}
                          </span>

                          <ChevronDown
                            size={16}
                            className={`shrink-0 text-slate-700 transition-transform ${
                              isBreedMenuOpen ? "rotate-180" : ""
                            }`}
                          />
                        </button>

                        {isBreedMenuOpen && form.petType && !isBreedLocked ? (
                          <div className="absolute left-0 right-0 top-[calc(100%+10px)] z-30 overflow-hidden rounded-2xl border border-[#e8eaee] bg-white shadow-[0_18px_40px_rgba(15,23,42,0.12)]">
                            <div className="border-b border-slate-100 p-3">
                              <div className="relative">
                                <Search
                                  size={16}
                                  className="absolute left-3 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                                />
                                <input
                                  type="text"
                                  value={breedQuery}
                                  onChange={(e) =>
                                    setBreedQuery(e.target.value)
                                  }
                                  placeholder="Search breed"
                                  autoFocus
                                  className="h-[42px] w-full rounded-xl border border-[#e8eaee] bg-[#f4f5f7] pl-10 pr-3 text-[14px] text-slate-700 outline-none"
                                />
                              </div>
                            </div>

                            <div className="max-h-52 overflow-y-auto p-2">
                              {filteredBreedOptions.length > 0 ? (
                                filteredBreedOptions.map((item) => (
                                  <button
                                    key={item.id}
                                    type="button"
                                    onClick={() =>
                                      handleBreedSelect(item.label)
                                    }
                                    className={`flex w-full items-center rounded-xl px-3 py-2.5 text-left text-[14px] transition ${
                                      form.breed === item.label
                                        ? "bg-green-50 text-green-700"
                                        : "text-slate-700 hover:bg-slate-50"
                                    }`}
                                  >
                                    {item.label}
                                  </button>
                                ))
                              ) : (
                                <div className="px-3 py-3 text-sm text-[#98a2b3]">
                                  {breedsLoading
                                    ? "Loading breeds..."
                                    : "No breeds found"}
                                </div>
                              )}
                            </div>
                          </div>
                        ) : null}
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div className="relative">
                        <select
                          value={form.gender}
                          onChange={(e) =>
                            updateField("gender", e.target.value)
                          }
                          disabled={isGenderLocked}
                          className={`${fieldBase} appearance-none pr-9 ${
                            isGenderLocked ? lockedFieldClassName : ""
                          }`}
                        >
                          <option value="">Select Gender</option>
                          <option value="Male">Male</option>
                          <option value="Female">Female</option>
                        </select>

                        <ChevronDown
                          size={16}
                          className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-700"
                        />
                      </div>

                      <input
                        type="text"
                        value={form.age}
                        onChange={(e) => updateField("age", e.target.value)}
                        placeholder="Age (e.g. 2 years)"
                        readOnly={isAgeLocked}
                        className={`${fieldBase} ${
                          isAgeLocked ? lockedFieldClassName : ""
                        }`}
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-auto pt-[5rem]">
                {requestError ? (
                  <p className="mb-3 whitespace-pre-line text-sm text-red-600">
                    {requestError}
                  </p>
                ) : null}

                <button
                  type="button"
                  onClick={handleSendPaymentLink}
                  disabled={isSubmittingRequest}
                  className={`${primaryBtn} disabled:cursor-not-allowed disabled:opacity-70`}
                >
                  {isSubmittingRequest
                    ? "Creating..."
                    : "Share Consultation Link"}
                  <CreditCard size={18} />
                </button>
              </div>
            </div>
          </div>
        )}

        {step === 1 && (
          <div className="flex flex-col min-h-screen">
            <Header title="Payment Status" onBack={handleBack} />

            <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
              <div className="relative mb-7">
                <div className="w-[96px] h-[96px] rounded-full border-[4px] border-[#f4c691] flex items-center justify-center bg-transparent">
                  <IndianRupee size={38} className="text-[#f97316]" />
                </div>
                <div className="absolute inset-0 rounded-full border-[4px] border-transparent border-t-[#f97316] rotate-[20deg]" />
              </div>

              <h2 className="text-[20px] font-bold text-[#0f2749] mb-3">
                {isConsultationInitiated
                  ? "Awaiting Payment"
                  : "Share Consultation Link"}
              </h2>

              <p className="text-[15px] leading-7 text-[#667085] max-w-[290px]">
                {isConsultationInitiated
                  ? "Parent started the consultation on WhatsApp. Payment link sent. Waiting for confirmation..."
                  : "Share this consultation link with the parent. Once they send the WhatsApp message, the payment link will be sent automatically."}
              </p>

              <div className="mt-8 w-full max-w-[320px] rounded-3xl border border-[#e5e7eb] bg-white p-4 text-left shadow-[0_12px_32px_rgba(15,23,42,0.06)]">
                <div className="mb-2 flex items-center gap-2 text-[13px] font-semibold uppercase tracking-[0.08em] text-[#16a34a]">
                  <Link2 size={14} />
                  Status
                </div>
                <p className="text-[15px] font-semibold text-[#0f2749]">
                  {isConsultationInitiated
                    ? "Parent initiated. Payment link sent."
                    : "Waiting for parent to start on WhatsApp."}
                </p>
              </div>

              <div className="mt-6 flex w-full max-w-[320px] flex-col gap-3">
                <button
                  type="button"
                  onClick={handleShareViaWhatsApp}
                  disabled={!consultSessionShareWhatsAppUrl}
                  className="flex h-[50px] items-center justify-center gap-2 rounded-full bg-[#2fd161] px-6 text-[15px] font-bold text-white shadow-[0_10px_22px_rgba(47,209,97,0.24)] disabled:cursor-not-allowed disabled:opacity-60"
                >
                  <MessageCircle size={16} />
                  Share via WhatsApp
                </button>

                <button
                  type="button"
                  onClick={handleCopyConsultationLink}
                  disabled={!consultSessionLandingUrl}
                  className="flex h-[50px] items-center justify-center gap-2 rounded-full border border-[#d7dee8] bg-white px-6 text-[15px] font-bold text-[#0f2749] disabled:cursor-not-allowed disabled:opacity-60"
                >
                  <Copy size={16} />
                  Copy Link
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function Header({ title, onBack }) {
  return (
    <div className="flex items-center gap-3 px-5 h-[68px] bg-[#16a34a] text-white shadow-[0_2px_12px_rgba(0,0,0,0.08)]">
      <button
        type="button"
        onClick={onBack}
        className="flex h-9 w-9 items-center justify-center rounded-full active:scale-95 transition"
      >
        <ArrowLeft size={22} />
      </button>

      <h1 className="text-[18px] font-bold">{title}</h1>
    </div>
  );
}
