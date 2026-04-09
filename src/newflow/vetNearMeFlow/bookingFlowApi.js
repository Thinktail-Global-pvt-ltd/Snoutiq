import { apiBaseUrl } from "../../lib/api";

const acceptJsonHeaders = {
  Accept: "application/json",
};

const jsonHeaders = {
  ...acceptJsonHeaders,
  "Content-Type": "application/json",
};

const getDisplayBreedName = (value) =>
  String(value || "")
    .trim()
    .replace(/\b\w/g, (char) => char.toUpperCase());

const pickText = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    const text = String(value).trim();
    if (text) return text;
  }
  return "";
};

const normalizeTimeSlot = (value) => {
  const text = String(value || "").trim();
  if (!text) return null;
  return /^\d{2}:\d{2}$/.test(text) ? `${text}:00` : text;
};

const buildApiError = async (response) => {
  const data = await response.json().catch(() => null);
  const validationErrors =
    data?.errors && typeof data.errors === "object"
      ? Object.values(data.errors).flat().filter(Boolean)
      : [];

  const message =
    validationErrors[0] ||
    data?.message ||
    data?.error ||
    `HTTP ${response.status}`;

  throw new Error(message);
};

async function readJsonResponse(path, options = {}) {
  const response = await fetch(`${apiBaseUrl()}${path}`, {
    headers: acceptJsonHeaders,
    ...options,
  });

  if (!response.ok) {
    await buildApiError(response);
  }

  return response.json();
}

async function postBookingStep(path, body) {
  return readJsonResponse(path, {
    method: "POST",
    headers: jsonHeaders,
    body: JSON.stringify(body),
  });
}

export async function fetchBreedOptions(species) {
  if (species === "Dog") {
    const response = await readJsonResponse("/api/dog-breeds/all", {
      method: "GET",
    });

    const breedNames = Object.keys(response?.breeds || {});

    return breedNames
      .map(getDisplayBreedName)
      .filter(Boolean)
      .sort((left, right) => left.localeCompare(right));
  }

  if (species === "Cat") {
    const response = await readJsonResponse("/api/cat-breeds/with-indian", {
      method: "GET",
    });

    return (Array.isArray(response?.data) ? response.data : [])
      .map((item) => item?.name)
      .filter(Boolean)
      .sort((left, right) => left.localeCompare(right));
  }

  return [];
}

export async function submitLeadStep(leadData) {
  const response = await postBookingStep("/api/home-vet-bookings/step-1", {
    name: leadData.name,
    phone: leadData.phone,
    pet_type: leadData.species || null,
    area: leadData.area || null,
    reason_for_visit: leadData.reason || null,
  });

  return {
    ok: response?.status === "success",
    bookingId: response?.data?.booking_id ?? null,
    userId: response?.data?.user_id ?? null,
    latestCompletedStep: response?.data?.latest_completed_step ?? 1,
    raw: response,
  };
}

export async function submitPetDetailsStep({ bookingId, petData, species }) {
  const breedValue =
    species === "Other"
      ? petData.otherPetType || null
      : petData.breed || null;

  const response = await postBookingStep("/api/home-vet-bookings/step-2", {
    booking_id: bookingId,
    pet_name: petData.petName,
    breed: breedValue,
    pet_dob: petData.dob || null,
    pet_sex: petData.sex || null,
    date_of_visit: petData.dateOfVisit || null,
    time_of_visit: petData.timeOfVisit || null,
    issue_description: petData.issue || null,
    symptoms: petData.symptoms || [],
    vaccination_status: petData.vaccinationStatus || null,
    last_deworming: petData.deworming || null,
    past_illnesses_or_surgeries: petData.history || null,
    current_medications: petData.medications || null,
    known_allergies: petData.allergies || null,
    vet_notes: petData.notes || null,
  });

  return {
    ok: response?.status === "success",
    bookingId: response?.data?.booking_id ?? bookingId,
    userId: response?.data?.user_id ?? null,
    petId: response?.data?.pet_id ?? null,
    latestCompletedStep: response?.data?.latest_completed_step ?? 2,
    raw: response,
  };
}

export async function initiatePayment({
  bookingId,
  amountPayable,
  paymentReference,
}) {
  const response = await postBookingStep("/api/home-vet-bookings/step-3", {
    booking_id: bookingId,
    payment_status: "paid",
    amount_payable: amountPayable,
    amount_paid: amountPayable,
    payment_provider: "razorpay",
    payment_reference: paymentReference || `demo-payment-${Date.now()}`,
    confirm_booking: true,
  });

  return {
    ok: response?.status === "success",
    bookingId: response?.data?.booking_id ?? bookingId,
    bookingReference: response?.data?.booking_reference ?? "",
    latestCompletedStep: response?.data?.latest_completed_step ?? 3,
    paymentStatus: response?.data?.payment_status ?? "paid",
    raw: response,
  };
}

export async function createHomeServiceOrder({
  bookingId,
  userId,
  petId,
  amount,
}) {
  const normalizedAmount = Math.round(Number(amount) || 0);

  const response = await postBookingStep("/api/create-order", {
    amount: normalizedAmount,
    amount_paise: normalizedAmount * 100,
    home_service_booking_id: bookingId,
    user_id: userId,
    pet_id: petId,
    order_type: "home_service",
  });

  return {
    ok: Boolean(
      response?.success && (response?.order_id || response?.order?.id) && response?.key
    ),
    key: response?.key ?? "",
    orderId: response?.order_id ?? response?.order?.id ?? "",
    amountPaise: response?.order?.amount ?? normalizedAmount * 100,
    currency: response?.order?.currency ?? "INR",
    receipt: response?.order?.receipt ?? "",
    error: response?.error || response?.message || "",
    raw: response,
  };
}

export async function createAppointmentOrder({ amount, userId, petId }) {
  const normalizedAmount = Math.round(Number(amount) || 0);

  const response = await postBookingStep("/api/create-order", {
    amount: normalizedAmount,
    order_type: "appointment",
    user_id: userId,
    pet_id: petId,
  });

  return {
    ok: Boolean(
      response?.success && (response?.order_id || response?.order?.id) && response?.key,
    ),
    key: response?.key ?? "",
    orderId: response?.order_id ?? response?.order?.id ?? "",
    amountPaise: response?.order?.amount ?? normalizedAmount * 100,
    currency: response?.order?.currency ?? "INR",
    error: response?.error || response?.message || "",
    raw: response,
  };
}

export async function verifyHomeServicePayment({
  bookingId,
  userId,
  petId,
  razorpayOrderId,
  razorpayPaymentId,
  razorpaySignature,
}) {
  const response = await postBookingStep("/api/rzp/verify", {
    razorpay_order_id: razorpayOrderId,
    razorpay_payment_id: razorpayPaymentId,
    razorpay_signature: razorpaySignature,
    home_service_booking_id: bookingId,
    user_id: userId,
    pet_id: petId,
  });

  return {
    ok: Boolean(response?.success),
    bookingId:
      response?.home_service_booking_id ??
      response?.data?.home_service_booking_id ??
      bookingId,
    userId: response?.user_id ?? response?.data?.user_id ?? userId,
    petId: response?.pet_id ?? response?.data?.pet_id ?? petId,
    latestCompletedStep: response?.data?.latest_completed_step ?? 3,
    paymentStatus:
      response?.payment_status ?? response?.data?.payment_status ?? "paid",
    bookingReference:
      response?.booking_reference ??
      response?.data?.booking_reference ??
      response?.data?.booking?.booking_reference ??
      "",
    paymentReference: razorpayPaymentId || "",
    error: response?.error || response?.message || "",
    raw: response,
  };
}

export async function verifyAppointmentPayment({
  userId,
  petId,
  razorpayOrderId,
  razorpayPaymentId,
  razorpaySignature,
}) {
  const response = await postBookingStep("/api/rzp/verify", {
    razorpay_order_id: razorpayOrderId,
    razorpay_payment_id: razorpayPaymentId,
    razorpay_signature: razorpaySignature,
    order_type: "appointment",
    user_id: userId,
    pet_id: petId,
  });

  return {
    ok: Boolean(response?.success),
    paymentReference: razorpayPaymentId || "",
    error: response?.error || response?.message || "",
    raw: response,
  };
}

export async function submitInClinicAppointment(appointmentData = {}) {
  const response = await postBookingStep("/api/appointments/submit", {
    user_id: pickText(
      appointmentData.userId,
      appointmentData.user_id,
      appointmentData.patient?.userId,
      appointmentData.paymentMeta?.user_id
    ),
    patient_name: pickText(
      appointmentData.patientName,
      appointmentData.patient_name,
      appointmentData.ownerName
    ),
    patient_phone: pickText(
      appointmentData.patientPhone,
      appointmentData.patient_phone,
      appointmentData.phone,
      appointmentData.ownerMobile
    )
      .replace(/\D/g, "")
      .slice(-10),
    patient_email: pickText(
      appointmentData.patientEmail,
      appointmentData.patient_email,
      appointmentData.email
    ),
    pet_name: pickText(
      appointmentData.petName,
      appointmentData.pet_name,
      appointmentData.name
    ),
    date: pickText(
      appointmentData.date,
      appointmentData.appointmentDate,
      appointmentData.date_of_visit
    ),
    time_slot: normalizeTimeSlot(
      pickText(
        appointmentData.timeSlot,
        appointmentData.time_slot,
        appointmentData.time_of_visit
      )
    ),
    amount: Number(appointmentData.amount) || 0,
    currency: pickText(appointmentData.currency, "INR").toUpperCase(),
    razorpay_order_id: pickText(
      appointmentData.razorpayOrderId,
      appointmentData.razorpay_order_id,
      appointmentData.orderId
    ),
    razorpay_payment_id: pickText(
      appointmentData.razorpayPaymentId,
      appointmentData.razorpay_payment_id,
      appointmentData.paymentId
    ),
    razorpay_signature: pickText(
      appointmentData.razorpaySignature,
      appointmentData.razorpay_signature,
      appointmentData.signature
    ),
    notes: pickText(
      appointmentData.notes,
      appointmentData.reason,
      appointmentData.issue_description,
      appointmentData.problemText
    ),
  });

  return {
    ok: Boolean(response?.success || response?.status === "success"),
    appointmentId:
      response?.data?.appointment?.id ??
      response?.data?.appointment?.appointment_table?.id ??
      response?.data?.appointment_id ??
      response?.data?.id ??
      response?.appointment_id ??
      response?.id ??
      null,
    message:
      response?.message ||
      response?.data?.message ||
      (response?.status === "success" ? "Appointment submitted successfully." : ""),
    raw: response,
  };
}
