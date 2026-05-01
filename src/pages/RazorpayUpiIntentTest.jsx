import React, { useEffect, useMemo, useState } from "react";
import { CheckCircle2, ExternalLink, Loader2, Smartphone } from "lucide-react";
import { apiPost } from "../lib/api";

const RAZORPAY_CHECKOUT_SRC = "https://checkout.razorpay.com/v1/checkout.js";

const loadRazorpayCheckout = () =>
  new Promise((resolve) => {
    if (typeof window === "undefined") {
      resolve(false);
      return;
    }

    if (window.Razorpay) {
      resolve(true);
      return;
    }

    const existing = document.querySelector(
      `script[src="${RAZORPAY_CHECKOUT_SRC}"]`,
    );
    if (existing) {
      existing.addEventListener("load", () => resolve(Boolean(window.Razorpay)), {
        once: true,
      });
      existing.addEventListener("error", () => resolve(false), { once: true });
      return;
    }

    const script = document.createElement("script");
    script.src = RAZORPAY_CHECKOUT_SRC;
    script.async = true;
    script.onload = () => resolve(Boolean(window.Razorpay));
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });

const digitsOnly = (value) => String(value || "").replace(/\D/g, "");

const formatJson = (value) => {
  if (!value) return "";
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
};

export default function RazorpayUpiIntentTest() {
  const [amount, setAmount] = useState("1");
  const [name, setName] = useState("Snoutiq Test User");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState("");
  const [gatewayReady, setGatewayReady] = useState(false);
  const [loadingGateway, setLoadingGateway] = useState(true);
  const [isPaying, setIsPaying] = useState(false);
  const [status, setStatus] = useState({
    type: "info",
    text: "Open this page on an Android phone to test PhonePe or Google Pay handoff.",
  });
  const [lastOrder, setLastOrder] = useState(null);
  const [lastPayment, setLastPayment] = useState(null);
  const [lastVerify, setLastVerify] = useState(null);

  useEffect(() => {
    let cancelled = false;

    loadRazorpayCheckout().then((ready) => {
      if (cancelled) return;
      setGatewayReady(ready);
      setLoadingGateway(false);
      if (!ready) {
        setStatus({
          type: "error",
          text: "Razorpay checkout script could not be loaded. Refresh and try again.",
        });
      }
    });

    return () => {
      cancelled = true;
    };
  }, []);

  const amountInRupees = useMemo(() => {
    const parsed = Number(amount);
    if (!Number.isFinite(parsed)) return 0;
    return Math.max(0, Math.round(parsed));
  }, [amount]);

  const statusClassName =
    status.type === "success"
      ? "border-emerald-200 bg-emerald-50 text-emerald-800"
      : status.type === "error"
        ? "border-red-200 bg-red-50 text-red-800"
        : "border-blue-200 bg-blue-50 text-blue-800";

  const openPayment = async () => {
    if (isPaying) return;

    if (!gatewayReady || typeof window === "undefined" || !window.Razorpay) {
      setStatus({
        type: "error",
        text: "Razorpay checkout is not ready yet. Refresh and try again.",
      });
      return;
    }

    if (amountInRupees < 1) {
      setStatus({ type: "error", text: "Enter an amount of at least Rs 1." });
      return;
    }

    const normalizedPhone = digitsOnly(phone).slice(-10);

    setIsPaying(true);
    setLastOrder(null);
    setLastPayment(null);
    setLastVerify(null);
    setStatus({ type: "info", text: "Creating Razorpay order..." });

    try {
      const order = await apiPost("/api/create-order", {
        amount: amountInRupees,
        amount_paise: amountInRupees * 100,
        order_type: "upi_intent_test",
        test_page: "razorpay_upi_intent_test",
      });

      const orderId = order?.order_id || order?.order?.id;
      const key = order?.key || order?.razorpay_key;

      setLastOrder(order);

      if (!order?.success || !orderId || !key) {
        throw new Error(order?.error || "Invalid create-order response.");
      }

      const checkout = new window.Razorpay({
        key,
        amount: amountInRupees * 100,
        currency: order?.order?.currency || "INR",
        order_id: orderId,
        name: "Snoutiq",
        description: "UPI intent test payment",
        prefill: {
          name: name.trim(),
          contact: normalizedPhone,
          email: email.trim(),
        },
        notes: {
          order_type: "upi_intent_test",
          test_page: "razorpay_upi_intent_test",
        },
        config: {
          display: {
            blocks: {
              upi_apps: {
                name: "Pay using UPI apps",
                instruments: [{ method: "upi" }],
              },
            },
            sequence: ["block.upi_apps"],
            preferences: {
              show_default_blocks: true,
            },
          },
        },
        theme: { color: "#1447e6" },
        modal: {
          ondismiss: () => {
            setStatus({
              type: "error",
              text: "Payment popup was closed. You can retry.",
            });
            setIsPaying(false);
          },
        },
        handler: async (paymentResponse) => {
          setLastPayment(paymentResponse);
          setStatus({ type: "info", text: "Payment returned. Verifying..." });

          try {
            const verify = await apiPost("/api/rzp/verify", {
              razorpay_order_id: paymentResponse?.razorpay_order_id,
              razorpay_payment_id: paymentResponse?.razorpay_payment_id,
              razorpay_signature: paymentResponse?.razorpay_signature,
            });

            setLastVerify(verify);

            if (!verify?.success) {
              throw new Error(verify?.error || "Verification failed.");
            }

            setStatus({
              type: "success",
              text: "Payment verified successfully.",
            });
          } catch (error) {
            setStatus({
              type: "error",
              text: error?.message || "Payment verification failed.",
            });
          } finally {
            setIsPaying(false);
          }
        },
      });

      checkout.on("payment.failed", (event) => {
        setLastPayment(event);
        setStatus({
          type: "error",
          text:
            event?.error?.description ||
            event?.error?.reason ||
            "Payment failed. Please retry.",
        });
        setIsPaying(false);
      });

      checkout.open();
      setStatus({
        type: "info",
        text: "Razorpay checkout opened. Choose a UPI app to test intent handoff.",
      });
    } catch (error) {
      setStatus({
        type: "error",
        text: error?.message || "Unable to start Razorpay checkout.",
      });
      setIsPaying(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#f8fbff] px-4 py-8 text-slate-900">
      <main className="mx-auto grid w-full max-w-5xl gap-6 lg:grid-cols-[minmax(0,420px)_1fr]">
        <section className="rounded-[24px] border border-[#dbe7ff] bg-white p-6 shadow-[0_18px_50px_-34px_rgba(37,99,235,0.45)]">
          <div className="flex items-start gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#eff5ff] text-[#1447e6]">
              <Smartphone size={22} />
            </div>
            <div>
              <h1 className="text-xl font-semibold text-slate-950">
                Razorpay UPI Intent Test
              </h1>
              <p className="mt-1 text-sm text-slate-500">
                Test PhonePe, Google Pay, Paytm, or QR through Razorpay checkout.
              </p>
            </div>
          </div>

          <div className="mt-6 space-y-4">
            <label className="block">
              <span className="text-sm font-medium text-slate-700">
                Amount in Rs
              </span>
              <input
                type="number"
                min="1"
                inputMode="numeric"
                value={amount}
                onChange={(event) => setAmount(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-[#dbe7ff] bg-[#fbfdff] px-4 py-3 text-sm outline-none transition focus:border-[#1447e6] focus:ring-4 focus:ring-[#1447e6]/10"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium text-slate-700">Name</span>
              <input
                type="text"
                value={name}
                onChange={(event) => setName(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-[#dbe7ff] bg-[#fbfdff] px-4 py-3 text-sm outline-none transition focus:border-[#1447e6] focus:ring-4 focus:ring-[#1447e6]/10"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium text-slate-700">
                Phone
              </span>
              <input
                type="tel"
                inputMode="tel"
                placeholder="10 digit mobile number"
                value={phone}
                onChange={(event) => setPhone(digitsOnly(event.target.value).slice(0, 10))}
                className="mt-2 w-full rounded-2xl border border-[#dbe7ff] bg-[#fbfdff] px-4 py-3 text-sm outline-none transition focus:border-[#1447e6] focus:ring-4 focus:ring-[#1447e6]/10"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium text-slate-700">
                Email
              </span>
              <input
                type="email"
                placeholder="optional"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-[#dbe7ff] bg-[#fbfdff] px-4 py-3 text-sm outline-none transition focus:border-[#1447e6] focus:ring-4 focus:ring-[#1447e6]/10"
              />
            </label>
          </div>

          <div className={`mt-5 rounded-2xl border px-4 py-3 text-sm ${statusClassName}`}>
            {status.text}
          </div>

          <button
            type="button"
            onClick={openPayment}
            disabled={loadingGateway || isPaying || amountInRupees < 1}
            className={`mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3.5 text-sm font-semibold transition ${
              loadingGateway || isPaying || amountInRupees < 1
                ? "cursor-not-allowed bg-slate-300 text-white"
                : "bg-[#1447e6] text-white shadow-[0_18px_36px_-22px_rgba(20,71,230,0.8)] hover:bg-[#0f3bc0]"
            }`}
          >
            {isPaying || loadingGateway ? (
              <Loader2 className="animate-spin" size={17} />
            ) : (
              <ExternalLink size={17} />
            )}
            {loadingGateway
              ? "Preparing checkout..."
              : isPaying
                ? "Payment in progress..."
                : `Pay Rs ${amountInRupees || 0} with UPI`}
          </button>

          <div className="mt-5 rounded-2xl border border-[#e5edff] bg-[#fbfdff] p-4 text-sm text-slate-600">
            <div className="flex items-start gap-2">
              <CheckCircle2 className="mt-0.5 shrink-0 text-emerald-600" size={16} />
              <p>
                On Android, Razorpay should show UPI apps and open the selected
                app with the amount prefilled. On desktop, expect QR fallback.
              </p>
            </div>
          </div>
        </section>

        <section className="rounded-[24px] border border-[#dbe7ff] bg-white p-6 shadow-[0_18px_50px_-34px_rgba(37,99,235,0.35)]">
          <h2 className="text-base font-semibold text-slate-950">
            Debug response
          </h2>
          <div className="mt-4 space-y-4">
            <DebugBlock title="Create order" value={lastOrder} />
            <DebugBlock title="Payment callback / failure" value={lastPayment} />
            <DebugBlock title="Verify response" value={lastVerify} />
          </div>
        </section>
      </main>
    </div>
  );
}

function DebugBlock({ title, value }) {
  return (
    <div className="rounded-2xl border border-[#e5edff] bg-[#fbfdff]">
      <div className="border-b border-[#e5edff] px-4 py-3 text-sm font-semibold text-slate-800">
        {title}
      </div>
      <pre className="max-h-64 overflow-auto p-4 text-xs leading-5 text-slate-700">
        {formatJson(value) || "No response yet."}
      </pre>
    </div>
  );
}
