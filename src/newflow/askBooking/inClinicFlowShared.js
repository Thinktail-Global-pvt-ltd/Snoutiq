export const IN_CLINIC_FLOW_STORAGE_KEY =
  "snoutiq-in-clinic-appointment-standalone";
export const IN_CLINIC_PET_DETAILS_ROUTE = "/in-clinic-pet-details";
export const IN_CLINIC_PAYMENT_ROUTE = "/in-clinic-payment";
export const ASK_ROUTE = "/ask";

export const IN_CLINIC_PRICING = Object.freeze({
  originalAmount: 450,
  discountAmount: 100,
  discountedAmount: 350,
  gstRate: 18,
  gstAmount: Math.round(350 * 0.18),
  totalAmount: Math.round(350 + 350 * 0.18),
  currency: "INR",
});

export const DEFAULT_IN_CLINIC_STATE = Object.freeze({
  userId: "",
  petId: "",
  patientName: "",
  patientPhone: "",
  patientEmail: "",
  petName: "",
  date: "",
  timeSlot: "",
  notes: "",
  paymentKey: "",
  paymentOrderId: "",
  paymentAmountPaise: null,
  paymentCurrency: "INR",
  paymentStatus: "pending",
  paymentReference: "",
  appointmentId: null,
});

export const IN_CLINIC_MISSING_CONTEXT_MESSAGE =
  "We could not prepare your booking details. Please start again from the consultation flow.";

export const pickText = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    const text = String(value).trim();
    if (text) return text;
  }
  return "";
};

export const pickNumber = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null || value === "") continue;
    const numberValue = Number(value);
    if (Number.isFinite(numberValue)) return numberValue;
  }
  return null;
};

export const normalizePhoneInput = (value) =>
  String(value || "")
    .replace(/\D/g, "")
    .slice(0, 10);

export const normalizeTimeInput = (value) => {
  const text = String(value || "").trim();
  if (!text) return "";
  if (/^\d{2}:\d{2}:\d{2}$/.test(text)) return text.slice(0, 5);
  return text.slice(0, 5);
};

export const isValidPhone = (value) => normalizePhoneInput(value).length === 10;

export const isValidEmail = (value) =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || "").trim());

export const formatInr = (value) =>
  Number(value || 0).toLocaleString("en-IN", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });

const asObject = (value) =>
  value && typeof value === "object" ? value : {};

export const normalizeInClinicState = (input) => {
  const raw = asObject(input);
  const order = asObject(raw.order);
  const patient = asObject(raw.patient);
  const pet = asObject(raw.pet);
  const payment = asObject(raw.payment);
  const paymentMeta = asObject(raw.paymentMeta);
  const lead = asObject(raw.lead);
  const state = asObject(raw.state);
  const stateUser = asObject(state.user);
  const statePatient = asObject(state.patient);
  const statePet = asObject(state.pet);
  const statePaymentMeta = asObject(state.paymentMeta);
  const data = asObject(raw.data);
  const nestedData = asObject(data.data);
  const observation = asObject(raw.observation);
  const observationResponse = asObject(raw.observationResponse);
  const observationResponseData = asObject(observationResponse.data);
  const observationResponseNested = asObject(observationResponseData.data);

  return {
    userId: pickText(
      raw.userId,
      raw.user_id,
      patient.userId,
      patient.user_id,
      patient.user?.id,
      state.userId,
      state.user_id,
      stateUser.id,
      statePatient.userId,
      statePatient.user_id,
      statePatient.user?.id,
      raw.user?.id,
      payment.userId,
      payment.user_id,
      paymentMeta.user_id,
      paymentMeta.userId,
      statePaymentMeta.user_id,
      statePaymentMeta.userId,
      data.user_id,
      data.userId,
      data.user?.id,
      nestedData.user_id,
      nestedData.userId,
      nestedData.user?.id,
      observation.user_id,
      observation.userId,
      observation.user?.id,
      observationResponse.user_id,
      observationResponse.userId,
      observationResponse.user?.id,
      observationResponseData.user_id,
      observationResponseData.userId,
      observationResponseData.user?.id,
      observationResponseNested.user_id,
      observationResponseNested.userId,
      observationResponseNested.user?.id,
    ),
    petId: pickText(
      raw.petId,
      raw.pet_id,
      pet.petId,
      pet.pet_id,
      pet.id,
      state.petId,
      state.pet_id,
      statePet.petId,
      statePet.pet_id,
      statePet.id,
      payment.petId,
      payment.pet_id,
      paymentMeta.pet_id,
      paymentMeta.petId,
      statePaymentMeta.pet_id,
      statePaymentMeta.petId,
      data.pet_id,
      data.petId,
      data.pet?.id,
      nestedData.pet_id,
      nestedData.petId,
      nestedData.pet?.id,
      observation.pet_id,
      observation.petId,
      observation.pet?.id,
      observationResponse.pet_id,
      observationResponse.petId,
      observationResponse.pet?.id,
      observationResponseData.pet_id,
      observationResponseData.petId,
      observationResponseData.pet?.id,
      observationResponseNested.pet_id,
      observationResponseNested.petId,
      observationResponseNested.pet?.id,
    ),
    patientName: pickText(
      raw.patientName,
      raw.patient_name,
      patient.name,
      statePatient.name,
      lead.ownerName,
      raw.ownerName,
    ),
    patientPhone: normalizePhoneInput(
      pickText(
        raw.patientPhone,
        raw.patient_phone,
        patient.phone,
        statePatient.phone,
        lead.phone,
        raw.phone,
        raw.ownerMobile,
      ),
    ),
    patientEmail: pickText(
      raw.patientEmail,
      raw.patient_email,
      patient.email,
      statePatient.email,
      raw.email,
    ),
    petName: pickText(
      raw.petName,
      raw.pet_name,
      pet.petName,
      pet.name,
      statePet.petName,
      statePet.pet_name,
      statePet.name,
      raw.name,
    ),
    date: pickText(
      raw.date,
      raw.appointmentDate,
      raw.date_of_visit,
      pet.dateOfVisit,
    ),
    timeSlot: normalizeTimeInput(
      pickText(
        raw.timeSlot,
        raw.time_slot,
        raw.time_of_visit,
        pet.timeOfVisit,
      ),
    ),
    notes: pickText(
      raw.notes,
      raw.reason,
      raw.issue_description,
      raw.problemText,
    ),
    paymentKey: pickText(raw.paymentKey, raw.key, payment.key),
    paymentOrderId: pickText(
      raw.paymentOrderId,
      raw.orderId,
      raw.order_id,
      raw.razorpayOrderId,
      raw.razorpay_order_id,
      payment.orderId,
      payment.order_id,
    ),
    paymentAmountPaise:
      pickNumber(
        raw.paymentAmountPaise,
        raw.amountPaise,
        raw.amount_paise,
        order.amount,
        payment.amountPaise,
        payment.amount_paise,
      ) ?? null,
    paymentCurrency:
      pickText(
        raw.paymentCurrency,
        raw.orderCurrency,
        raw.currency,
        order.currency,
        payment.currency,
      ) || "INR",
    paymentStatus:
      pickText(raw.paymentStatus, payment.status, payment.paymentStatus) ||
      "pending",
    paymentReference: pickText(
      raw.paymentReference,
      raw.razorpayPaymentId,
      raw.razorpay_payment_id,
      payment.paymentReference,
    ),
    appointmentId:
      pickNumber(raw.appointmentId, raw.appointment_id, payment.appointmentId) ??
      null,
  };
};

export const hasInClinicIdentifiers = (state) =>
  Boolean(pickText(state?.userId) && pickText(state?.petId));

export const resolveInClinicState = ({
  storedState,
  initialState,
  locationState,
  currentState,
} = {}) =>
  mergeInClinicStates(
    DEFAULT_IN_CLINIC_STATE,
    storedState,
    asObject(initialState).prefill,
    asObject(locationState).prefill,
    initialState,
    locationState,
    currentState,
  );

export const mergeInClinicStates = (...values) =>
  values
    .map((value) => normalizeInClinicState(value))
    .reduce(
      (current, next) => ({
        userId: pickText(next.userId, current.userId),
        petId: pickText(next.petId, current.petId),
        patientName: pickText(next.patientName, current.patientName),
        patientPhone: normalizePhoneInput(
          pickText(next.patientPhone, current.patientPhone),
        ),
        patientEmail: pickText(next.patientEmail, current.patientEmail),
        petName: pickText(next.petName, current.petName),
        date: pickText(next.date, current.date),
        timeSlot: pickText(next.timeSlot, current.timeSlot),
        notes: pickText(next.notes, current.notes),
        paymentKey: pickText(next.paymentKey, current.paymentKey),
        paymentOrderId: pickText(
          next.paymentOrderId,
          current.paymentOrderId,
        ),
        paymentAmountPaise:
          pickNumber(next.paymentAmountPaise, current.paymentAmountPaise) ??
          null,
        paymentCurrency:
          pickText(next.paymentCurrency, current.paymentCurrency) || "INR",
        paymentStatus:
          pickText(next.paymentStatus, current.paymentStatus) || "pending",
        paymentReference: pickText(
          next.paymentReference,
          current.paymentReference,
        ),
        appointmentId:
          pickNumber(next.appointmentId, current.appointmentId) ?? null,
      }),
      { ...DEFAULT_IN_CLINIC_STATE },
    );

export const readInClinicStoredState = () => {
  return { ...DEFAULT_IN_CLINIC_STATE };
};

export const writeInClinicStoredState = (state) => {
  void state;
};

export const clearInClinicStoredState = () => {
  return undefined;
};
