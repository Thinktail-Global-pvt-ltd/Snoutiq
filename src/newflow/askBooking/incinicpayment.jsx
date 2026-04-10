import React, { useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { ArrowLeft, CheckCircle2, Lock } from "lucide-react";
import {
  createAppointmentOrder,
  submitInClinicAppointment,
  verifyAppointmentPayment,
} from "../vetNearMeFlow/bookingFlowApi";
import "../vetNearMeFlow/VetNearMeBooking.css";
import {
  ASK_ROUTE,
  clearInClinicStoredState,
  formatInr,
  hasInClinicIdentifiers,
  IN_CLINIC_MISSING_CONTEXT_MESSAGE,
  IN_CLINIC_PET_DETAILS_ROUTE,
  IN_CLINIC_PRICING,
  isValidEmail,
  isValidPhone,
  mergeInClinicStates,
  resolveInClinicState,
} from "./inClinicFlowShared";

const RAZORPAY_CHECKOUT_SRC = "https://checkout.razorpay.com/v1/checkout.js";

const loadRazorpayCheckout = () =>
  new Promise((resolve) => {
    if (typeof window === "undefined") return resolve(false);
    if (window.Razorpay) return resolve(true);

    const done = () => resolve(Boolean(window.Razorpay));
    const failed = () => resolve(false);
    const existing = document.querySelector(`script[src="${RAZORPAY_CHECKOUT_SRC}"]`);

    if (existing) {
      existing.addEventListener("load", done, { once: true });
      existing.addEventListener("error", failed, { once: true });
      return;
    }

    const script = document.createElement("script");
    script.src = RAZORPAY_CHECKOUT_SRC;
    script.async = true;
    script.onload = done;
    script.onerror = failed;
    document.body.appendChild(script);
  });

const hasRequiredVisibleAppointmentDetails = (state) =>
  Boolean(
    String(state?.patientName || "").trim() &&
      isValidPhone(state?.patientPhone) &&
      isValidEmail(state?.patientEmail) &&
      String(state?.petName || "").trim() &&
      String(state?.date || "").trim() &&
      String(state?.timeSlot || "").trim(),
  );

export default function InClinicPayment({ initialState, onBack, onPay }) {
  const navigate = useNavigate();
  const location = useLocation();
  const propState =
    initialState && typeof initialState === "object" ? initialState : null;
  const locationState =
    location.state && typeof location.state === "object" ? location.state : null;

  const [appointmentState, setAppointmentState] = useState(() =>
    resolveInClinicState({
      initialState: propState,
      locationState,
    }),
  );
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isGatewayLoading, setIsGatewayLoading] = useState(true);
  const [isGatewayReady, setIsGatewayReady] = useState(false);
  const [paymentMessage, setPaymentMessage] = useState({ type: "", text: "" });
  const [successfulAppointment, setSuccessfulAppointment] = useState(null);

  const hasResolvedIds = hasInClinicIdentifiers(appointmentState);
  const hasVisibleDetails =
    hasRequiredVisibleAppointmentDetails(appointmentState);
  const isReady = hasResolvedIds && hasVisibleDetails;
  const isPaid = appointmentState.paymentStatus === "paid";

  useEffect(() => {
    let cancelled = false;
    loadRazorpayCheckout().then((ready) => {
      if (cancelled) return;
      setIsGatewayReady(ready);
      setIsGatewayLoading(false);
      if (!ready) {
        setPaymentMessage({
          type: "error",
          text: "Secure payment could not be loaded. Refresh and try again.",
        });
      }
    });
    return () => {
      cancelled = true;
    };
  }, []);

  const setStatus = (type, text) => setPaymentMessage({ type, text });

  const handleBack = () => {
    if (onBack) {
      onBack();
      return;
    }
    navigate(IN_CLINIC_PET_DETAILS_ROUTE, {
      replace: true,
      state: appointmentState,
    });
  };

  const handleGoToAsk = () => {
    navigate(ASK_ROUTE, { replace: true });
  };

  const handleBookAnother = () => {
    clearInClinicStoredState();
    setSuccessfulAppointment(null);
    setPaymentMessage({ type: "", text: "" });
    navigate(IN_CLINIC_PET_DETAILS_ROUTE, { replace: true });
  };

  const handlePayment = async () => {
    if (isSubmitting || isPaid) return;
    const preparedState = resolveInClinicState({
      initialState: propState,
      locationState,
      currentState: appointmentState,
    });

    setAppointmentState(preparedState);

    if (!hasInClinicIdentifiers(preparedState)) {
      setStatus("error", IN_CLINIC_MISSING_CONTEXT_MESSAGE);
      return;
    }
    if (!hasRequiredVisibleAppointmentDetails(preparedState)) {
      setStatus(
        "error",
        "Appointment details are incomplete. Please go back and fill the form.",
      );
      return;
    }
    if (isGatewayLoading) {
      setStatus("info", "Preparing secure payment...");
      return;
    }
    if (!isGatewayReady || typeof window === "undefined" || !window.Razorpay) {
      setStatus("error", "Secure payment is unavailable right now. Please refresh and try again.");
      return;
    }

    setIsSubmitting(true);
    setStatus("info", "Preparing secure Razorpay checkout...");

    try {
      let paymentState = preparedState;
      let order = {
        ok: Boolean(preparedState.paymentKey && preparedState.paymentOrderId),
        key: preparedState.paymentKey,
        orderId: preparedState.paymentOrderId,
        amountPaise:
          preparedState.paymentAmountPaise ||
          IN_CLINIC_PRICING.totalAmount * 100,
        currency:
          preparedState.paymentCurrency || IN_CLINIC_PRICING.currency,
      };

      if (!order.ok) {
        setStatus("info", "Creating secure Razorpay order...");
        order = await createAppointmentOrder({
          amount: IN_CLINIC_PRICING.totalAmount,
          userId: preparedState.userId,
          petId: preparedState.petId,
        });

        if (!order.ok || !order.orderId || !order.key) {
          throw new Error(order.error || "Order could not be created.");
        }

        paymentState = mergeInClinicStates(preparedState, {
          paymentStatus: "pending",
          paymentKey: order.key,
          paymentOrderId: order.orderId,
          paymentAmountPaise: order.amountPaise,
          paymentCurrency: order.currency || IN_CLINIC_PRICING.currency,
        });
        setAppointmentState(paymentState);
      }

      setStatus("info", "Opening secure Razorpay checkout...");

      const checkout = new window.Razorpay({
        key: order.key,
        amount: order.amountPaise,
        currency: order.currency || IN_CLINIC_PRICING.currency,
        order_id: order.orderId,
        name: "Snoutiq",
        description: "In-clinic appointment payment",
        prefill: {
          name: paymentState.patientName,
          contact: paymentState.patientPhone,
          email: paymentState.patientEmail,
        },
        notes: {
          order_type: "appointment",
          user_id: String(paymentState.userId),
          pet_id: String(paymentState.petId),
          pet_name: paymentState.petName,
          appointment_date: paymentState.date,
          appointment_time: paymentState.timeSlot,
        },
        theme: { color: "#1447e6" },
        modal: {
          ondismiss: () => {
            setStatus("error", "Payment cancelled. You can retry anytime.");
            setIsSubmitting(false);
          },
        },
        handler: async (paymentResponse) => {
          setStatus("info", "Verifying payment...");

          try {
            const verify = await verifyAppointmentPayment({
              userId: paymentState.userId,
              petId: paymentState.petId,
              razorpayOrderId: paymentResponse?.razorpay_order_id,
              razorpayPaymentId: paymentResponse?.razorpay_payment_id,
              razorpaySignature: paymentResponse?.razorpay_signature,
            });

            if (!verify.ok) {
              throw new Error(verify.error || "Payment verification failed.");
            }

            const submit = await submitInClinicAppointment({
              ...paymentState,
              amount: IN_CLINIC_PRICING.totalAmount,
              currency: IN_CLINIC_PRICING.currency,
              razorpayOrderId: paymentResponse?.razorpay_order_id,
              razorpayPaymentId: paymentResponse?.razorpay_payment_id,
              razorpaySignature: paymentResponse?.razorpay_signature,
            });

            if (!submit.ok) {
              throw new Error(
                submit.message || "Appointment could not be submitted after payment.",
              );
            }

            const nextState = mergeInClinicStates(paymentState, {
              paymentStatus: "paid",
              paymentReference: paymentResponse?.razorpay_payment_id,
              appointmentId: submit.appointmentId,
            });

            setAppointmentState(nextState);
            setSuccessfulAppointment({
              appointmentId: submit.appointmentId,
              paymentReference: paymentResponse?.razorpay_payment_id || "",
              state: nextState,
            });
            setStatus("success", submit.message || "Payment successful. Appointment confirmed.");
            if (onPay) {
              onPay({
                appointmentId: submit.appointmentId,
                paymentReference: paymentResponse?.razorpay_payment_id || "",
                state: nextState,
              });
            }
            setIsSubmitting(false);
          } catch (error) {
            setStatus(
              "error",
              error?.message || "Payment verification failed. Please try again.",
            );
            setIsSubmitting(false);
          }
        },
      });

      checkout.on("payment.failed", (event) => {
        const description =
          event?.error?.description || event?.error?.reason || "Payment failed. Please try again.";
        setStatus("error", description);
        setIsSubmitting(false);
      });

      checkout.open();
    } catch (error) {
      setStatus("error", error?.message || "Payment could not be started. Please try again.");
      setIsSubmitting(false);
    }
  };

  const summaryRows = [
    { label: "Pet Parent Name", value: appointmentState.patientName || "-" },
    { label: "WhatsApp Number", value: appointmentState.patientPhone || "-" },
    { label: "Email Address", value: appointmentState.patientEmail || "-" },
    { label: "Pet Name", value: appointmentState.petName || "-" },
    {
      label: "Appointment Slot",
      value: [appointmentState.date, appointmentState.timeSlot]
        .filter(Boolean)
        .join(" / ") || "-",
    },
    { label: "Description", value: appointmentState.notes || "Not added" },
  ];

  const amountRows = [
    {
      label: "Consultation fee",
      value: `Rs ${formatInr(IN_CLINIC_PRICING.originalAmount)}`,
    },
    {
      label: "Discount",
      value: `-Rs ${formatInr(IN_CLINIC_PRICING.discountAmount)}`,
      tone: "success",
    },
    {
      label: "After discount",
      value: `Rs ${formatInr(IN_CLINIC_PRICING.discountedAmount)}`,
    },
    {
      label: `GST (${IN_CLINIC_PRICING.gstRate}%)`,
      value: `Rs ${formatInr(IN_CLINIC_PRICING.gstAmount)}`,
    },
  ];

  const paymentStatusClassName =
    paymentMessage.type === "success"
      ? "border-emerald-200 bg-emerald-50 text-emerald-700"
      : paymentMessage.type === "error"
        ? "border-red-200 bg-red-50 text-red-700"
        : "border-blue-200 bg-blue-50 text-blue-700";

  const payButtonHelperText = isPaid
    ? "Payment already completed for this appointment."
    : !hasResolvedIds
      ? IN_CLINIC_MISSING_CONTEXT_MESSAGE
      : !hasVisibleDetails
      ? "Appointment details are incomplete. Please review the previous step."
      : isGatewayLoading
        ? "Preparing secure payment checkout..."
        : "";

  if (!isReady && !successfulAppointment) {
    const needsFreshStart = !hasResolvedIds;
    return (
      <div className="min-h-screen bg-[#f8fbff] px-4 py-12">
        <div className="mx-auto w-full max-w-md rounded-[24px] border border-slate-200 bg-white p-6 text-center shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">
            Payment not ready
          </h2>
          <p className="mt-2 text-sm text-slate-600">
            {needsFreshStart
              ? IN_CLINIC_MISSING_CONTEXT_MESSAGE
              : "Complete the in-clinic appointment details first."}
          </p>
          <button
            type="button"
            onClick={() =>
              needsFreshStart
                ? handleGoToAsk()
                : navigate(IN_CLINIC_PET_DETAILS_ROUTE, { replace: true })
            }
            className="mt-5 w-full rounded-2xl bg-[#2563eb] py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]"
          >
            {needsFreshStart ? "Start again" : "Go to details"}
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#f8fbff] text-slate-900">
      <div className="sticky top-0 z-30 border-b border-[#e3ecff] bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-lg items-center gap-3 px-4 py-3">
          <button
            type="button"
            onClick={handleBack}
            className="flex h-10 w-10 items-center justify-center rounded-full border border-[#dbe7ff] bg-white text-slate-600 transition hover:bg-[#f8fbff]"
            aria-label="Go back"
          >
            <ArrowLeft size={18} />
          </button>
          <div className="min-w-0 flex-1">
            <div className="text-base font-semibold text-slate-900">
              In-clinic payment
            </div>
            <div className="text-xs text-slate-500">Review amount and complete payment</div>
          </div>
        </div>
      </div>

      <div className="px-4 pb-36 pt-4 md:pb-40 md:pt-6">
        <div className="mx-auto max-w-lg space-y-4">
          <div className="rounded-[24px] border border-[#dbe7ff] bg-white p-5 shadow-[0_10px_30px_-24px_rgba(37,99,235,0.25)]">
            <div className="text-sm font-semibold text-slate-900">Appointment Summary</div>
            <div className="mt-4 space-y-3 text-sm">
              {summaryRows.map((item) => (
                <div
                  key={item.label}
                  className="flex items-start justify-between gap-4 border-b border-[#e8efff] pb-3 last:border-b-0 last:pb-0"
                >
                  <span className="text-slate-500">{item.label}</span>
                  <span className="max-w-[62%] text-right font-medium text-slate-900">
                    {item.value}
                  </span>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-[24px] border border-[#dbe7ff] bg-white p-5 shadow-[0_10px_30px_-24px_rgba(37,99,235,0.25)]">
            <div className="text-sm font-semibold text-slate-900">Amount Details</div>
            <div className="mt-4 space-y-3 text-sm">
              {amountRows.map((item) => (
                <div
                  key={item.label}
                  className={`flex items-center justify-between gap-4 ${
                    item.tone === "success" ? "text-emerald-700" : "text-slate-600"
                  }`}
                >
                  <span>{item.label}</span>
                  <span className="font-medium text-slate-900">{item.value}</span>
                </div>
              ))}

              <div className="flex items-center justify-between gap-4 border-t border-[#e8efff] pt-4">
                <span className="font-semibold text-slate-900">Total Payable</span>
                <span className="text-xl font-semibold text-slate-900">
                  Rs {formatInr(IN_CLINIC_PRICING.totalAmount)}
                </span>
              </div>
            </div>
          </div>

          {paymentMessage.text ? (
            <div
              className={`rounded-[20px] border px-4 py-3 text-sm font-medium ${paymentStatusClassName}`}
            >
              {paymentMessage.text}
            </div>
          ) : null}
        </div>
      </div>

      <div className="fixed inset-x-0 bottom-0 z-30 border-t border-[#dbe7ff] bg-white/95 backdrop-blur">
        <div className="mx-auto w-full max-w-lg px-4 pb-[calc(16px+env(safe-area-inset-bottom))] pt-3">
          <div className="mb-3 flex items-center justify-between gap-4">
            <div>
              <p className="text-[11px] font-medium text-slate-500">Total Payable</p>
              <div className="text-lg font-bold text-slate-900">
                Rs {formatInr(IN_CLINIC_PRICING.totalAmount)}
              </div>
            </div>
            <div className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
              <Lock size={12} />
              Secure
            </div>
          </div>

          {payButtonHelperText ? (
            <p className="mb-3 text-xs font-medium text-slate-500">
              {payButtonHelperText}
            </p>
          ) : null}

          <button
            type="button"
            onClick={handlePayment}
            disabled={isSubmitting || isGatewayLoading || !isReady || isPaid}
            className={`inline-flex w-full items-center justify-center rounded-2xl px-4 py-3.5 text-sm font-semibold transition ${
              isSubmitting || isGatewayLoading || !isReady || isPaid
                ? "cursor-not-allowed bg-slate-300 text-white opacity-60"
                : "bg-[#2563eb] text-white shadow-[0_18px_36px_-22px_rgba(37,99,235,0.7)] hover:bg-[#1d4ed8]"
            }`}
          >
            {isPaid
              ? "Payment completed"
              : isSubmitting
                ? "Opening payment..."
                : isGatewayLoading
                  ? "Preparing payment..."
                  : `Pay Rs ${formatInr(IN_CLINIC_PRICING.totalAmount)}`}
          </button>
        </div>
      </div>

      {successfulAppointment ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4">
          <div className="w-full max-w-sm rounded-[28px] bg-white p-6 text-center shadow-2xl">
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
              <CheckCircle2 size={28} />
            </div>
            <div className="text-lg font-bold text-slate-900">
              Appointment confirmed
            </div>
            <p className="mt-2 text-sm text-slate-500">
              Payment successful and your in-clinic appointment has been submitted.
            </p>
            <div className="mt-5 rounded-2xl border border-[#e5edff] bg-[#f8fbff] p-4 text-left text-sm text-slate-700">
            </div>
            <div className="mt-5 grid gap-3">
              <button
                type="button"
                onClick={handleGoToAsk}
                className="inline-flex w-full items-center justify-center rounded-2xl bg-[#2563eb] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]"
              >
                Go to Ask
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
