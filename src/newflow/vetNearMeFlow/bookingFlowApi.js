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
