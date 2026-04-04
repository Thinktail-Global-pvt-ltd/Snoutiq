const sleep = (ms = 150) =>
  new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });

const createBookingReference = () =>
  `SNQ-${Math.floor(10000 + Math.random() * 90000)}`;

// Replace these placeholders with the real backend integrations.
export async function submitLeadStep(leadData) {
  await sleep();

  return {
    ok: true,
    leadId: `lead-${Date.now()}`,
    leadData,
  };
}

export async function submitPetDetailsStep(payload) {
  await sleep();

  return {
    ok: true,
    draftBookingId: `draft-${Date.now()}`,
    payload,
  };
}

export async function initiatePayment(payload) {
  await sleep();

  return {
    ok: true,
    paymentId: `payment-${Date.now()}`,
    bookingReference: createBookingReference(),
    payload,
  };
}
