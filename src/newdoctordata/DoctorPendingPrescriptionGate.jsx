import React, { useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useNewDoctorAuth } from "./NewDoctorAuth";
import CompletePetProfileModal from "./CompletePetProfileModal";
import {
  getDoctorPendingPrescription,
  getPendingPrescriptionMissingFields,
  hasDoctorPendingPrescriptionRouteState,
  setDoctorPendingPrescription,
  stripDoctorPendingPrescriptionRouteState,
  syncDoctorPendingPrescriptionFromRouteState,
} from "./doctorPendingPrescriptionService";
import { useDoctorPendingPrescription } from "./useDoctorPendingPrescription";

const DIGITAL_PRESCRIPTION_PATH = "/counsltflow/digital-prescription";
const SKIPPED_PATHS = ["/counsltflow/login", "/counsltflow/onboarding"];
const PETS_UPDATE_URL = "https://snoutiq.com/backend/api/pets";

const hasDoctorSession = (auth) =>
  Boolean(
    auth?.onboarding_completed || (auth?.phone_verified && auth?.phone_exists),
  );

const getDoctorStorageId = (auth) =>
  auth?.doctor_id || auth?.doctor?.id || auth?.doctor?.doctor_id || "";

const normalizeText = (value) => String(value ?? "").trim();

const buildValidationMessage = (
  responsePayload,
  fallback = "Unable to save pet profile.",
) => {
  const primary = normalizeText(responsePayload?.message || fallback) || fallback;
  const errorEntries =
    responsePayload?.errors && typeof responsePayload.errors === "object"
      ? Object.values(responsePayload.errors).flat()
      : [];
  const details = errorEntries.map(normalizeText).filter(Boolean);

  if (!details.length) {
    return primary;
  }

  const uniqueDetails = Array.from(
    new Set(details.filter((detail) => detail !== primary)),
  );

  return uniqueDetails.length ? [primary, ...uniqueDetails].join("\n") : primary;
};

const mergeSavedPatientData = (
  currentPatientData = {},
  updatedPatientData = {},
  responsePayload = {},
) => {
  const payload =
    responsePayload?.data && typeof responsePayload.data === "object"
      ? responsePayload.data
      : responsePayload;
  const userPayload =
    payload?.user && typeof payload.user === "object" ? payload.user : {};
  const petPayload =
    payload?.pet && typeof payload.pet === "object" ? payload.pet : payload;

  return {
    ...currentPatientData,
    ...updatedPatientData,
    parentName:
      normalizeText(updatedPatientData.parentName) ||
      normalizeText(userPayload?.name) ||
      normalizeText(petPayload?.pet_owner_name) ||
      normalizeText(currentPatientData.parentName),
    phone:
      normalizeText(updatedPatientData.phone) ||
      normalizeText(userPayload?.phone) ||
      normalizeText(currentPatientData.phone),
    petName:
      normalizeText(updatedPatientData.petName) ||
      normalizeText(petPayload?.pet_name) ||
      normalizeText(petPayload?.name) ||
      normalizeText(currentPatientData.petName),
    petType:
      normalizeText(updatedPatientData.petType) ||
      normalizeText(petPayload?.pet_type) ||
      normalizeText(petPayload?.type) ||
      normalizeText(currentPatientData.petType),
    breed:
      normalizeText(updatedPatientData.breed) ||
      normalizeText(petPayload?.breed) ||
      normalizeText(currentPatientData.breed),
    gender:
      normalizeText(updatedPatientData.gender) ||
      normalizeText(petPayload?.pet_gender) ||
      normalizeText(petPayload?.gender) ||
      normalizeText(currentPatientData.gender),
    age:
      normalizeText(updatedPatientData.age) ||
      normalizeText(petPayload?.pet_age) ||
      normalizeText(currentPatientData.age),
    weight:
      normalizeText(updatedPatientData.weight) ||
      normalizeText(petPayload?.pet_weight) ||
      normalizeText(petPayload?.weight) ||
      normalizeText(currentPatientData.weight),
  };
};

export default function DoctorPendingPrescriptionGate() {
  const navigate = useNavigate();
  const location = useLocation();
  const { auth, hydrated } = useNewDoctorAuth();

  const doctorId = getDoctorStorageId(auth);
  const authToken = auth?.token || auth?.access_token || "";
  const shouldRun =
    hydrated &&
    Boolean(doctorId) &&
    hasDoctorSession(auth) &&
    !SKIPPED_PATHS.includes(location.pathname);

  const { pendingPrescription, refresh } = useDoctorPendingPrescription({
    doctorId,
    enabled: shouldRun,
  });
  const storagePendingPrescription =
    shouldRun && doctorId
      ? getDoctorPendingPrescription(doctorId)
      : pendingPrescription;

  const isPrescriptionPage = location.pathname === DIGITAL_PRESCRIPTION_PATH;
  const hasPendingRouteState =
    shouldRun &&
    isPrescriptionPage &&
    hasDoctorPendingPrescriptionRouteState(location.state);
  const routeStatePatientData =
    location.state?.patientData && typeof location.state.patientData === "object"
      ? location.state.patientData
      : null;
  const routeStateMissingFields = routeStatePatientData
    ? getPendingPrescriptionMissingFields(routeStatePatientData)
    : [];
  const effectivePendingPrescription =
    pendingPrescription.hasPending
      ? pendingPrescription
      : storagePendingPrescription?.hasPending
        ? storagePendingPrescription
        : hasPendingRouteState
        ? {
            ...pendingPrescription,
            hasPending: true,
            lockUntilSubmit: true,
            patientData: routeStatePatientData || pendingPrescription.patientData,
            missingFields: routeStateMissingFields,
            userId:
              normalizeText(location.state?.userId) || pendingPrescription.userId,
            petId:
              normalizeText(location.state?.petId) || pendingPrescription.petId,
            consultationId:
              location.state?.consultationId ?? pendingPrescription.consultationId,
            paymentStatus:
              normalizeText(location.state?.paymentStatus) ||
              pendingPrescription.paymentStatus,
            prescriptionRequired:
              location.state?.prescriptionRequired ??
              pendingPrescription.prescriptionRequired,
            prescriptionStatus:
              normalizeText(location.state?.prescriptionStatus) ||
              pendingPrescription.prescriptionStatus,
          }
        : pendingPrescription;
  const isLocked =
    shouldRun &&
    effectivePendingPrescription.hasPending &&
    effectivePendingPrescription.lockUntilSubmit;
  const shouldRedirectToPrescription = isLocked && !isPrescriptionPage;
  const showProfileModal =
    isLocked &&
    isPrescriptionPage &&
    effectivePendingPrescription.missingFields.length > 0;

  useEffect(() => {
    if (!shouldRun || !isPrescriptionPage) {
      return;
    }

    if (!hasDoctorPendingPrescriptionRouteState(location.state)) {
      return;
    }

    syncDoctorPendingPrescriptionFromRouteState(doctorId, location.state);
    refresh();
    navigate(
      {
        pathname: location.pathname,
        search: location.search,
        hash: location.hash,
      },
      {
        replace: true,
        state: stripDoctorPendingPrescriptionRouteState(location.state),
      },
    );
  }, [
    doctorId,
    isPrescriptionPage,
    location.hash,
    location.pathname,
    location.search,
    location.state,
    navigate,
    refresh,
    shouldRun,
  ]);

  useEffect(() => {
    if (!shouldRedirectToPrescription) {
      return;
    }

    navigate(DIGITAL_PRESCRIPTION_PATH, { replace: true });
  }, [navigate, shouldRedirectToPrescription]);

  const handleProfileSave = async (patientData) => {
    if (!effectivePendingPrescription.petId) {
      throw new Error("Pet details are missing. Please restart the request.");
    }

    if (!effectivePendingPrescription.userId) {
      throw new Error("Parent details are missing. Please restart the request.");
    }

    const requestBody = new FormData();
    requestBody.append("_method", "PUT");
    requestBody.append("user_id", String(effectivePendingPrescription.userId));
    requestBody.append("pet_owner_name", normalizeText(patientData.parentName));
    requestBody.append("pet_name", normalizeText(patientData.petName));
    requestBody.append("name", normalizeText(patientData.petName));
    requestBody.append("breed", normalizeText(patientData.breed));
    requestBody.append("pet_type", normalizeText(patientData.petType));
    requestBody.append("pet_age", normalizeText(patientData.age));
    requestBody.append("pet_gender", normalizeText(patientData.gender));
    requestBody.append("pet_weight", normalizeText(patientData.weight));
    requestBody.append("weight", normalizeText(patientData.weight));

    const headers = {
      Accept: "application/json",
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
    };

    const response = await fetch(
      `${PETS_UPDATE_URL}/${encodeURIComponent(effectivePendingPrescription.petId)}`,
      {
        method: "POST",
        headers,
        body: requestBody,
      },
    );
    const responsePayload = await response.json().catch(() => ({}));

    if (!response.ok || responsePayload?.success === false) {
      throw new Error(buildValidationMessage(responsePayload));
    }

    const nextPatientData = mergeSavedPatientData(
      effectivePendingPrescription.patientData,
      patientData,
      responsePayload,
    );

    setDoctorPendingPrescription(doctorId, {
      ...effectivePendingPrescription,
      hasPending: true,
      lockUntilSubmit: true,
      patientData: nextPatientData,
    });

    await refresh();
    navigate(
      {
        pathname: location.pathname,
        search: location.search,
        hash: location.hash,
      },
      {
        replace: true,
        state: stripDoctorPendingPrescriptionRouteState(location.state),
      },
    );
  };

  return (
    <>
      <CompletePetProfileModal
        isOpen={showProfileModal}
        patientData={effectivePendingPrescription.patientData}
        missingFields={effectivePendingPrescription.missingFields}
        onSave={handleProfileSave}
      />
    </>
  );
}
