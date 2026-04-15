const STORAGE_PREFIX = "doctor_pending_prescription_v1";
const UPDATE_EVENT_NAME = "doctor-pending-prescription:updated";

export const PENDING_PRESCRIPTION_PATIENT_FIELDS = [
  "parentName",
  "phone",
  "petName",
  "petType",
  "breed",
  "gender",
  "age",
  "weight",
];

export const PENDING_PRESCRIPTION_FIELD_LABELS = {
  parentName: "Parent name",
  phone: "Phone",
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
  lockUntilSubmit: false,
  patientData: EMPTY_PENDING_PATIENT_DATA,
  missingFields: [],
};

const canUseBrowserStorage = () =>
  typeof window !== "undefined" && typeof window.localStorage !== "undefined";

const normalizeText = (value) => String(value ?? "").trim();

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
  `${STORAGE_PREFIX}:${normalizeText(doctorId) || "default"}`;

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
    lockUntilSubmit: Boolean(value.lockUntilSubmit),
    patientData,
    missingFields: hasPending
      ? getPendingPrescriptionMissingFields(patientData)
      : [],
  };
};

const emitPendingPrescriptionUpdate = (doctorId) => {
  if (!canUseBrowserStorage()) return;

  window.dispatchEvent(
    new CustomEvent(UPDATE_EVENT_NAME, {
      detail: {
        doctorId: normalizeText(doctorId) || "default",
      },
    }),
  );
};

export function getDoctorPendingPrescription(doctorId) {
  if (!canUseBrowserStorage()) {
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
  if (!canUseBrowserStorage()) {
    return EMPTY_PENDING_PRESCRIPTION;
  }

  const storageKey = getPendingPrescriptionStorageKey(doctorId);
  const normalizedValue = normalizePendingPrescription(value);

  if (!normalizedValue.hasPending) {
    window.localStorage.removeItem(storageKey);
    emitPendingPrescriptionUpdate(doctorId);
    return EMPTY_PENDING_PRESCRIPTION;
  }

  window.localStorage.setItem(storageKey, JSON.stringify(normalizedValue));
  emitPendingPrescriptionUpdate(doctorId);
  return normalizedValue;
}

export function startDoctorPendingPrescription(doctorId, value = {}) {
  return setDoctorPendingPrescription(doctorId, {
    consultationId: value.consultationId ?? null,
    lockUntilSubmit: value.lockUntilSubmit ?? true,
    patientData: value.patientData ?? {},
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

export function clearDoctorPendingPrescription(doctorId) {
  return setDoctorPendingPrescription(doctorId, EMPTY_PENDING_PRESCRIPTION);
}

export function syncDoctorPendingPrescriptionFromRouteState(
  doctorId,
  routeState,
) {
  if (!routeState?.paymentCompleted || !routeState?.lockUntilSubmit) {
    return getDoctorPendingPrescription(doctorId);
  }

  return startDoctorPendingPrescription(doctorId, {
    consultationId: routeState.consultationId ?? null,
    lockUntilSubmit: true,
    patientData: routeState.patientData ?? {},
  });
}

export function subscribeToDoctorPendingPrescription(doctorId, listener) {
  if (!canUseBrowserStorage() || typeof listener !== "function") {
    return () => {};
  }

  const normalizedDoctorId = normalizeText(doctorId) || "default";
  const storageKey = getPendingPrescriptionStorageKey(doctorId);

  const handleStorage = (event) => {
    if (!event.key || event.key === storageKey) {
      listener();
    }
  };

  const handleCustomEvent = (event) => {
    if (!event?.detail?.doctorId || event.detail.doctorId === normalizedDoctorId) {
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
