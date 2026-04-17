import React, { useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { useNewDoctorAuth } from "./NewDoctorAuth";
import CompletePetProfileModal from "./CompletePetProfileModal";
import {
  PENDING_PRESCRIPTION_PATIENT_FIELDS,
  getDoctorPendingPrescription,
  getPendingPrescriptionMissingFields,
  hasDoctorPendingPrescriptionRouteState,
  stripDoctorPendingPrescriptionRouteState,
  syncDoctorPendingPrescriptionFromRouteState,
  updateDoctorPendingPrescriptionData,
} from "./doctorPendingPrescriptionService";
import { useDoctorPendingPrescription } from "./useDoctorPendingPrescription";

const DIGITAL_PRESCRIPTION_PATH = "/counsltflow/digital-prescription";
const SKIPPED_PATHS = ["/counsltflow/login", "/counsltflow/onboarding"];

const PATIENT_CREATE_URL = "https://snoutiq.com/backend/api/users/pets";
const PETS_UPDATE_BASE_URL = "https://snoutiq.com/backend/api/pets";

const hasDoctorSession = (auth) =>
  Boolean(
    auth?.onboarding_completed || (auth?.phone_verified && auth?.phone_exists),
  );

const getDoctorStorageId = (auth) =>
  auth?.doctor_id || auth?.doctor?.id || auth?.doctor?.doctor_id || "";

const normalizeText = (value) => String(value ?? "").trim();

const normalizePatientDataInput = (patientData = {}) => ({
  parentName: normalizeText(patientData.parentName),
  phone: normalizeText(patientData.phone),
  petName: normalizeText(patientData.petName),
  petType: normalizeText(patientData.petType),
  breed: normalizeText(patientData.breed),
  gender: normalizeText(patientData.gender),
  age: normalizeText(patientData.age),
  weight: normalizeText(patientData.weight),
});

const appendFormDataIfPresent = (requestBody, key, value) => {
  const normalizedValue = normalizeText(value);
  if (normalizedValue) {
    requestBody.append(key, normalizedValue);
  }
};

const formatPetTypeForCreate = (value) => {
  const normalized = normalizeText(value).toLowerCase();
  if (!normalized) return "";
  return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const buildPetUpdateRequestBody = ({ userId, patientData }) => {
  const requestBody = new FormData();

  requestBody.append("_method", "PUT");
  requestBody.append("user_id", String(userId));

  appendFormDataIfPresent(requestBody, "pet_owner_name", patientData.parentName);
  appendFormDataIfPresent(requestBody, "pet_name", patientData.petName);
  appendFormDataIfPresent(requestBody, "name", patientData.petName);
  appendFormDataIfPresent(requestBody, "pet_type", patientData.petType);
  appendFormDataIfPresent(requestBody, "breed", patientData.breed);
  appendFormDataIfPresent(requestBody, "pet_gender", patientData.gender);
  appendFormDataIfPresent(requestBody, "pet_age", patientData.age);
  appendFormDataIfPresent(requestBody, "pet_weight", patientData.weight);
  appendFormDataIfPresent(requestBody, "weight", patientData.weight);

  return requestBody;
};

const buildPatientCreateRequestBody = ({ userId, patientData }) => {
  const requestBody = new FormData();

  requestBody.append("user_id", String(userId));
  appendFormDataIfPresent(requestBody, "name", patientData.parentName);
  appendFormDataIfPresent(requestBody, "pet_name", patientData.petName);
  appendFormDataIfPresent(
    requestBody,
    "pet_type",
    formatPetTypeForCreate(patientData.petType),
  );
  appendFormDataIfPresent(requestBody, "pet_breed", patientData.breed);
  appendFormDataIfPresent(requestBody, "pet_gender", patientData.gender);
  appendFormDataIfPresent(requestBody, "pet_age", patientData.age);
  appendFormDataIfPresent(requestBody, "weight", patientData.weight);

  return requestBody;
};

const resolveSavedPetId = (responsePayload = {}) => {
  const payload =
    responsePayload?.data && typeof responsePayload.data === "object"
      ? responsePayload.data
      : responsePayload;

  const nestedPayload =
    payload?.data && typeof payload.data === "object" ? payload.data : {};

  const candidates = [
    responsePayload?.pet?.id,
    responsePayload?.data?.pet?.id,
    responsePayload?.data?.id,
    responsePayload?.pet_id,
    responsePayload?.petId,
    payload?.pet?.id,
    payload?.pet_id,
    payload?.petId,
    nestedPayload?.pet?.id,
    nestedPayload?.pet_id,
    nestedPayload?.petId,
    payload?.id,
    nestedPayload?.id,
    responsePayload?.id,
  ];

  return candidates.map(normalizeText).find(Boolean) || "";
};

const resolveSavedUserId = (responsePayload = {}) => {
  const payload =
    responsePayload?.data && typeof responsePayload.data === "object"
      ? responsePayload.data
      : responsePayload;

  const nestedPayload =
    payload?.data && typeof payload.data === "object" ? payload.data : {};

  const userPayload =
    payload?.user && typeof payload.user === "object"
      ? payload.user
      : nestedPayload?.user && typeof nestedPayload.user === "object"
        ? nestedPayload.user
        : {};

  const candidates = [
    responsePayload?.user?.id,
    responsePayload?.data?.user?.id,
    responsePayload?.user_id,
    responsePayload?.userId,
    payload?.user_id,
    payload?.userId,
    nestedPayload?.user_id,
    nestedPayload?.userId,
    userPayload?.id,
  ];

  return candidates.map(normalizeText).find(Boolean) || "";
};

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

  const effectivePendingPrescription = storagePendingPrescription?.hasPending
    ? storagePendingPrescription
    : pendingPrescription.hasPending
      ? pendingPrescription
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
  const needsPetCreate = !normalizeText(effectivePendingPrescription.petId);

  const profileModalFields =
    effectivePendingPrescription.missingFields.length > 0
      ? effectivePendingPrescription.missingFields
      : needsPetCreate
        ? PENDING_PRESCRIPTION_PATIENT_FIELDS
        : [];

  const showProfileModal =
    isLocked &&
    isPrescriptionPage &&
    (needsPetCreate || effectivePendingPrescription.missingFields.length > 0);

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
    const latestPendingPrescription = doctorId
      ? getDoctorPendingPrescription(doctorId)
      : effectivePendingPrescription;

    const pendingPrescriptionForSave = latestPendingPrescription?.hasPending
      ? latestPendingPrescription
      : effectivePendingPrescription;

    const existingUserId = normalizeText(pendingPrescriptionForSave.userId);
    const existingPetId = normalizeText(pendingPrescriptionForSave.petId);

    if (!existingUserId) {
      throw new Error("Parent details are missing. Please restart the request.");
    }

    const normalizedPatientData = normalizePatientDataInput({
      ...pendingPrescriptionForSave.patientData,
      ...patientData,
    });

    const headers = {
      Accept: "application/json",
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
    };

    let response;
    let responsePayload = {};
    let resolvedUserId = existingUserId;
    let resolvedPetId = existingPetId;

    if (existingPetId) {
      const requestBody = buildPetUpdateRequestBody({
        userId: existingUserId,
        patientData: normalizedPatientData,
      });

      response = await fetch(
        `${PETS_UPDATE_BASE_URL}/${encodeURIComponent(existingPetId)}`,
        {
          method: "POST",
          headers,
          body: requestBody,
        },
      );

      responsePayload = await response.json().catch(() => ({}));

      if (!response.ok || responsePayload?.success === false) {
        throw new Error(buildValidationMessage(responsePayload));
      }

      resolvedPetId = existingPetId;
    } else {
      if (!normalizedPatientData.parentName) {
        throw new Error("Pet parent name is required.");
      }

      if (!normalizedPatientData.petName) {
        throw new Error("Pet name is required.");
      }

      const requestBody = buildPatientCreateRequestBody({
        userId: existingUserId,
        patientData: normalizedPatientData,
      });

      response = await fetch(PATIENT_CREATE_URL, {
        method: "POST",
        headers,
        body: requestBody,
      });

      responsePayload = await response.json().catch(() => ({}));

      if (!response.ok || responsePayload?.success === false) {
        throw new Error(buildValidationMessage(responsePayload));
      }

      resolvedUserId = resolveSavedUserId(responsePayload) || existingUserId;
      resolvedPetId = resolveSavedPetId(responsePayload);

      if (!resolvedPetId) {
        throw new Error(
          "Pet profile was created but no pet ID was returned. Please try again.",
        );
      }
    }

    const nextPatientData = mergeSavedPatientData(
      pendingPrescriptionForSave.patientData,
      normalizedPatientData,
      responsePayload,
    );

    updateDoctorPendingPrescriptionData(doctorId, {
      userId: resolvedUserId,
      petId: resolvedPetId,
      patientData: nextPatientData,
      lockUntilSubmit: true,
      hasPending: true,
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
        missingFields={profileModalFields}
        onSave={handleProfileSave}
      />
    </>
  );
}