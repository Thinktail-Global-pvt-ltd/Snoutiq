const STORAGE_PREFIX = "doctor_pending_prescription_v1";
const UPDATE_EVENT_NAME = "doctor-pending-prescription:updated";

export const PENDING_PRESCRIPTION_PATIENT_FIELDS = [
  "parentName",
  "petName",
  "petType",
  "breed",
  "gender",
  "age",
  "weight",
];

export const PENDING_PRESCRIPTION_FIELD_LABELS = {
  parentName: "Parent name",
  petName: "Pet name",
  petType: "Pet type",
  breed: "Breed",
  gender: "Gender",
  age: "Age",
  weight: "Weight",
};

export const EMPTY_PENDING_PATIENT_DATA = {
  parentName: "",
  phone: "",
  petName: "",
  petType: "",
  breed: "",
  gender: "",
  age: "",
  weight: "",
};

export const EMPTY_PENDING_PRESCRIPTION = {
  hasPending: false,
  consultationId: null,
  userId: "",
  petId: "",
  lockUntilSubmit: false,
  patientData: EMPTY_PENDING_PATIENT_DATA,
  missingFields: [],
  paymentStatus: "",
  prescriptionRequired: false,
  prescriptionStatus: "",
};

const canUseBrowserStorage = () =>
  typeof window !== "undefined" && typeof window.localStorage !== "undefined";

const normalizeText = (value) => String(value ?? "").trim();
const normalizeDoctorId = (doctorId) => normalizeText(doctorId);
const hasDoctorId = (doctorId) => Boolean(normalizeDoctorId(doctorId));

const normalizePatientData = (patientData = {}) => ({
  parentName: normalizeText(patientData.parentName),
  phone: normalizeText(patientData.phone),
  petName: normalizeText(patientData.petName),
  petType: normalizeText(patientData.petType),
  breed: normalizeText(patientData.breed),
  gender: normalizeText(patientData.gender),
  age: normalizeText(patientData.age),
  weight: normalizeText(patientData.weight),
});

export const getPendingPrescriptionStorageKey = (doctorId) =>
  hasDoctorId(doctorId)
    ? `${STORAGE_PREFIX}:${normalizeDoctorId(doctorId)}`
    : "";

export const hasDoctorPendingPrescriptionRouteState = (routeState) =>
  Boolean(routeState?.paymentCompleted && routeState?.lockUntilSubmit);

export const stripDoctorPendingPrescriptionRouteState = (routeState) => {
  if (!routeState || typeof routeState !== "object") {
    return null;
  }

  const nextRouteState = { ...routeState };
  delete nextRouteState.consultationId;
  delete nextRouteState.fromNewRequest;
  delete nextRouteState.lockUntilSubmit;
  delete nextRouteState.patientData;
  delete nextRouteState.paymentCompleted;
  delete nextRouteState.userId;
  delete nextRouteState.petId;
  delete nextRouteState.paymentStatus;
  delete nextRouteState.prescriptionRequired;
  delete nextRouteState.prescriptionStatus;

  return Object.keys(nextRouteState).length > 0 ? nextRouteState : null;
};

export const getPendingPrescriptionMissingFields = (patientData = {}) => {
  const normalized = normalizePatientData(patientData);
  return PENDING_PRESCRIPTION_PATIENT_FIELDS.filter(
    (field) => !normalizeText(normalized[field]),
  );
};

const normalizePendingPrescription = (value = {}) => {
  const patientData = normalizePatientData(value.patientData);
  const hasPending = Boolean(value.hasPending);

  return {
    hasPending,
    consultationId: value.consultationId ?? null,
    userId: normalizeText(value.userId),
    petId: normalizeText(value.petId),
    lockUntilSubmit: Boolean(value.lockUntilSubmit),
    patientData,
    missingFields: hasPending
      ? getPendingPrescriptionMissingFields(patientData)
      : [],
    paymentStatus: normalizeText(value.paymentStatus),
    prescriptionRequired: Boolean(value.prescriptionRequired),
    prescriptionStatus: normalizeText(value.prescriptionStatus),
  };
};

const emitPendingPrescriptionUpdate = (doctorId) => {
  if (!canUseBrowserStorage() || !hasDoctorId(doctorId)) return;

  window.dispatchEvent(
    new CustomEvent(UPDATE_EVENT_NAME, {
      detail: {
        doctorId: normalizeDoctorId(doctorId),
      },
    }),
  );
};

export function getDoctorPendingPrescription(doctorId) {
  if (!canUseBrowserStorage() || !hasDoctorId(doctorId)) {
    return EMPTY_PENDING_PRESCRIPTION;
  }

  const storageKey = getPendingPrescriptionStorageKey(doctorId);
  const storedValue = window.localStorage.getItem(storageKey);

  if (!storedValue) {
    return EMPTY_PENDING_PRESCRIPTION;
  }

  try {
    return normalizePendingPrescription(JSON.parse(storedValue));
  } catch (error) {
    console.error("Failed to parse pending prescription:", error);
    return EMPTY_PENDING_PRESCRIPTION;
  }
}

export function setDoctorPendingPrescription(doctorId, value) {
  if (!canUseBrowserStorage() || !hasDoctorId(doctorId)) {
    return EMPTY_PENDING_PRESCRIPTION;
  }

  const storageKey = getPendingPrescriptionStorageKey(doctorId);
  const currentStoredValue = window.localStorage.getItem(storageKey);
  const normalizedValue = normalizePendingPrescription(value);

  if (!normalizedValue.hasPending) {
    if (!currentStoredValue) {
      return EMPTY_PENDING_PRESCRIPTION;
    }

    window.localStorage.removeItem(storageKey);
    emitPendingPrescriptionUpdate(doctorId);
    return EMPTY_PENDING_PRESCRIPTION;
  }

  const serializedValue = JSON.stringify(normalizedValue);
  if (currentStoredValue === serializedValue) {
    return normalizedValue;
  }

  window.localStorage.setItem(storageKey, serializedValue);
  emitPendingPrescriptionUpdate(doctorId);
  return normalizedValue;
}

export function startDoctorPendingPrescription(doctorId, value = {}) {
  return setDoctorPendingPrescription(doctorId, {
    consultationId: value.consultationId ?? null,
    userId: value.userId ?? "",
    petId: value.petId ?? "",
    lockUntilSubmit: value.lockUntilSubmit ?? true,
    patientData: value.patientData ?? {},
    paymentStatus: value.paymentStatus ?? "",
    prescriptionRequired: value.prescriptionRequired ?? false,
    prescriptionStatus: value.prescriptionStatus ?? "",
    hasPending: true,
  });
}

export function updateDoctorPendingPrescriptionPatientData(
  doctorId,
  patientData,
) {
  const currentValue = getDoctorPendingPrescription(doctorId);

  if (!currentValue.hasPending) {
    return currentValue;
  }

  return setDoctorPendingPrescription(doctorId, {
    ...currentValue,
    patientData: {
      ...currentValue.patientData,
      ...patientData,
    },
    hasPending: true,
  });
}

export function updateDoctorPendingPrescriptionData(doctorId, patch = {}) {
  const currentValue = getDoctorPendingPrescription(doctorId);

  if (!currentValue.hasPending) {
    return currentValue;
  }

  const hasPatientDataPatch =
    patch?.patientData && typeof patch.patientData === "object";

  return setDoctorPendingPrescription(doctorId, {
    ...currentValue,
    ...patch,
    patientData: hasPatientDataPatch
      ? {
          ...currentValue.patientData,
          ...patch.patientData,
        }
      : currentValue.patientData,
    hasPending: true,
  });
}

export function clearDoctorPendingPrescription(doctorId) {
  return setDoctorPendingPrescription(doctorId, EMPTY_PENDING_PRESCRIPTION);
}

export function syncDoctorPendingPrescriptionFromRouteState(
  doctorId,
  routeState,
) {
  const existingPendingPrescription = getDoctorPendingPrescription(doctorId);

  if (
    existingPendingPrescription.hasPending ||
    !hasDoctorPendingPrescriptionRouteState(routeState)
  ) {
    return existingPendingPrescription;
  }

  return startDoctorPendingPrescription(doctorId, {
    consultationId: routeState.consultationId ?? null,
    userId: routeState.userId ?? "",
    petId: routeState.petId ?? "",
    lockUntilSubmit: true,
    patientData: routeState.patientData ?? {},
    paymentStatus: routeState.paymentStatus ?? "",
    prescriptionRequired: routeState.prescriptionRequired ?? false,
    prescriptionStatus: routeState.prescriptionStatus ?? "",
  });
}

export function subscribeToDoctorPendingPrescription(doctorId, listener) {
  if (
    !canUseBrowserStorage() ||
    !hasDoctorId(doctorId) ||
    typeof listener !== "function"
  ) {
    return () => {};
  }

  const normalizedDoctorId = normalizeDoctorId(doctorId);
  const storageKey = getPendingPrescriptionStorageKey(doctorId);

  const handleStorage = (event) => {
    if (!event.key || event.key === storageKey) {
      listener();
    }
  };

  const handleCustomEvent = (event) => {
    if (event?.detail?.doctorId === normalizedDoctorId) {
      listener();
    }
  };

  window.addEventListener("storage", handleStorage);
  window.addEventListener(UPDATE_EVENT_NAME, handleCustomEvent);

  return () => {
    window.removeEventListener("storage", handleStorage);
    window.removeEventListener(UPDATE_EVENT_NAME, handleCustomEvent);
  };
}
