export function normalizeText(value) {
  return String(value ?? "").trim();
}

export function normalizePhone(value) {
  return String(value || "").replace(/[^\d+]/g, "").trim();
}

export function stripEmpty(payload) {
  return Object.fromEntries(
    Object.entries(payload || {}).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  );
}

export function parsePositiveId(...values) {
  for (const value of values) {
    const parsed = Number.parseInt(normalizeText(value), 10);
    if (Number.isFinite(parsed) && parsed > 0) return parsed;
  }
  return 0;
}

export function getHeaders(token, extra = {}) {
  return {
    ...extra,
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
}

export async function readApiBody(response) {
  const text = await response.text();
  if (!text) return {};
  try {
    return JSON.parse(text);
  } catch {
    return { raw: text };
  }
}

export function loadRazorpayScript() {
  return new Promise((resolve) => {
    if (window.Razorpay) {
      resolve(true);
      return;
    }

    const existing = document.querySelector('script[data-razorpay="true"]');
    if (existing) {
      existing.addEventListener("load", () => resolve(true), { once: true });
      existing.addEventListener("error", () => resolve(false), { once: true });
      return;
    }

    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.dataset.razorpay = "true";
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
}

export async function openRazorpayCheckout({
  key,
  orderId,
  amountInPaise,
  description,
  prefill,
  currency = "INR",
}) {
  const loaded = await loadRazorpayScript();
  if (!loaded) throw new Error("Razorpay SDK load nahi hua.");

  return new Promise((resolve, reject) => {
    const checkout = new window.Razorpay({
      key,
      amount: String(amountInPaise),
      currency,
      order_id: orderId,
      name: "SnoutIQ",
      description,
      prefill,
      theme: { color: "#2563eb" },
      modal: { ondismiss: () => reject(new Error("Payment cancelled by user.")) },
      handler: resolve,
    });
    checkout.open();
  });
}
