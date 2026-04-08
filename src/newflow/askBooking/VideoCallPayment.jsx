import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { Button } from "../../components/Button";
import { PET_FLOW_STEPS, ProgressBar } from "../../components/Sharedcomponents";
import { apiPost } from "../../lib/api";
import {
  ShieldCheck,
  ArrowRight,
  CheckCircle2,
  ChevronLeft,
  MessageCircle,
  Video,
} from "lucide-react";

const FLOW_STORAGE_KEY = "snoutiq-video-call-copied-flow";
const PET_DETAILS_ROUTE = "/video-call-pet-details";
const STATIC_CONSULTATION_AMOUNT = 599;
const STATIC_SERVICE_AMOUNT = 0;
const STATIC_DISCOUNT_AMOUNT = 100;
const GST_RATE = 0.18;

const loadRazorpayScript = () =>
  new Promise((resolve) => {
    if (typeof window !== "undefined" && window.Razorpay) {
      resolve(true);
      return;
    }
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });

const pickValue = (...values) => {
  for (const value of values) {
    if (value === undefined || value === null) continue;
    if (typeof value === "string") {
      const trimmed = value.trim();
      if (trimmed) return trimmed;
      continue;
    }
    return value;
  }
  return undefined;
};

const toNumber = (value) => {
  if (value === undefined || value === null || value === "") return undefined;
  const n = Number(value);
  return Number.isFinite(n) ? n : undefined;
};

const formatInr = (value) => {
  const n = Number(value);
  if (!Number.isFinite(n)) return "0";
  return n.toLocaleString("en-IN", {
    minimumFractionDigits: Number.isInteger(n) ? 0 : 2,
    maximumFractionDigits: 2,
  });
};

const round2 = (n) => Number((Number(n) || 0).toFixed(2));
const toInt = (n) => Math.round(Number(n) || 0);

const stripEmpty = (payload) =>
  Object.fromEntries(
    Object.entries(payload).filter(
      ([, value]) => value !== undefined && value !== null && value !== ""
    )
  );

const readStoredFlow = () => {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.sessionStorage.getItem(FLOW_STORAGE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
};

const writeStoredFlow = (value) => {
  if (typeof window === "undefined") return;
  window.sessionStorage.setItem(FLOW_STORAGE_KEY, JSON.stringify(value));
};

const extractPaymentMeta = (petDetails, paymentMeta) => {
  const userId = toNumber(
    pickValue(
      paymentMeta?.user_id,
      paymentMeta?.userId,
      petDetails?.user_id,
      petDetails?.userId,
      petDetails?.data?.user_id,
      petDetails?.data?.userId,
      petDetails?.user?.id,
      petDetails?.observation?.user_id,
      petDetails?.observation?.userId,
      petDetails?.observation?.user?.id,
      petDetails?.observationResponse?.user_id,
      petDetails?.observationResponse?.userId,
      petDetails?.observationResponse?.user?.id,
      petDetails?.observationResponse?.data?.user_id,
      petDetails?.observationResponse?.data?.userId,
      petDetails?.observationResponse?.data?.user?.id,
      petDetails?.observationResponse?.data?.data?.user_id,
      petDetails?.observationResponse?.data?.data?.userId,
      petDetails?.observationResponse?.data?.data?.user?.id
    )
  );

  const petId = toNumber(
    pickValue(
      paymentMeta?.pet_id,
      paymentMeta?.petId,
      petDetails?.pet_id,
      petDetails?.petId,
      petDetails?.data?.pet_id,
      petDetails?.data?.petId,
      petDetails?.pet?.id,
      petDetails?.observation?.pet_id,
      petDetails?.observation?.petId,
      petDetails?.observation?.pet?.id,
      petDetails?.observationResponse?.pet_id,
      petDetails?.observationResponse?.petId,
      petDetails?.observationResponse?.pet?.id,
      petDetails?.observationResponse?.data?.pet_id,
      petDetails?.observationResponse?.data?.petId,
      petDetails?.observationResponse?.data?.pet?.id,
      petDetails?.observationResponse?.data?.data?.pet_id,
      petDetails?.observationResponse?.data?.data?.petId,
      petDetails?.observationResponse?.data?.data?.pet?.id
    )
  );

  return stripEmpty({
    user_id: userId,
    pet_id: petId,
    order_type:
      pickValue(
        paymentMeta?.order_type,
        paymentMeta?.orderType,
        petDetails?.order_type,
        petDetails?.orderType,
        petDetails?.observation?.order_type,
        petDetails?.observation?.orderType,
        petDetails?.observationResponse?.order_type,
        petDetails?.observationResponse?.orderType,
        petDetails?.observationResponse?.data?.order_type,
        petDetails?.observationResponse?.data?.orderType
      ) || "excell_export_campaign",
    call_session_id: pickValue(
      paymentMeta?.call_session_id,
      paymentMeta?.callSessionId,
      petDetails?.call_session_id,
      petDetails?.callSessionId,
      petDetails?.observation?.call_session_id,
      petDetails?.observation?.callSessionId,
      petDetails?.observationResponse?.call_session_id,
      petDetails?.observationResponse?.callSessionId,
      petDetails?.observationResponse?.data?.call_session_id,
      petDetails?.observationResponse?.data?.callSessionId
    ),
    gst_number: pickValue(
      paymentMeta?.gst_number,
      paymentMeta?.gstNumber,
      petDetails?.gst_number,
      petDetails?.gstNumber
    ),
  });
};

export const VideoCallPayment = ({
  vet,
  petDetails: petDetailsProp,
  paymentMeta: paymentMetaProp,
  onPay,
  onBack,
}) => {
  const navigate = useNavigate();
  const location = useLocation();
  const storedFlow = readStoredFlow();
  const routeState =
    location.state && typeof location.state === "object" ? location.state : {};

  const petDetails =
    petDetailsProp || routeState?.petDetails || storedFlow?.petDetails || null;
  const paymentMeta = useMemo(
    () =>
      extractPaymentMeta(
        petDetails,
        paymentMetaProp || routeState?.paymentMeta || storedFlow?.paymentMeta
      ),
    [paymentMetaProp, petDetails, routeState?.paymentMeta, storedFlow?.paymentMeta]
  );

  const consultationAmount = STATIC_CONSULTATION_AMOUNT;
  const service = STATIC_SERVICE_AMOUNT;
  const gstRate = GST_RATE;
  const taxableAmountBeforeDiscount = round2(consultationAmount + service);
  const gstAmountBeforeDiscount = round2(taxableAmountBeforeDiscount * gstRate);
  const totalBeforeDiscount = round2(
    taxableAmountBeforeDiscount + gstAmountBeforeDiscount
  );
  const discountAmount = round2(
    Math.min(STATIC_DISCOUNT_AMOUNT, taxableAmountBeforeDiscount)
  );
  const isOfferApplied = discountAmount > 0;
  const taxableAmount = round2(
    Math.max(taxableAmountBeforeDiscount - discountAmount, 0)
  );
  const gstAmount = round2(taxableAmount * gstRate);
  const total = round2(taxableAmount + gstAmount);
  const createOrderAmountInr = toInt(total);
  const createOrderAmountPaise = createOrderAmountInr * 100;

  const [isPaying, setIsPaying] = useState(false);
  const [gatewayReady, setGatewayReady] = useState(false);
  const [statusType, setStatusType] = useState("");
  const [statusMessage, setStatusMessage] = useState("");
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [acknowledged, setAcknowledged] = useState(false);
  const [gstNumber, setGstNumber] = useState(
    () => paymentMeta?.gst_number || ""
  );

  useEffect(() => {
    writeStoredFlow({
      petDetails,
      paymentMeta: {
        ...paymentMeta,
        gst_number: gstNumber || paymentMeta?.gst_number || "",
      },
    });
  }, [gstNumber, paymentMeta, petDetails]);

  useEffect(() => {
    if (!gstNumber && paymentMeta?.gst_number) {
      setGstNumber(String(paymentMeta.gst_number).trim());
    }
  }, [gstNumber, paymentMeta?.gst_number]);

  useEffect(() => {
    let active = true;
    loadRazorpayScript().then((ready) => {
      if (active) setGatewayReady(ready);
    });
    return () => {
      active = false;
    };
  }, []);

  const paymentContext = useMemo(
    () =>
      stripEmpty({
        order_type: paymentMeta?.order_type || "excell_export_campaign",
        call_session_id: paymentMeta?.call_session_id,
        pet_id: paymentMeta?.pet_id,
        user_id: paymentMeta?.user_id,
        gst_number: gstNumber ? gstNumber.trim() : undefined,
        gst_number_given: gstNumber.trim() ? 1 : undefined,
        amount_includes_gst: 0,
        gst_rate_percent: 18,
        consultation_amount_inr: toInt(consultationAmount),
        taxable_amount_inr: toInt(taxableAmount),
        gst_amount_inr: toInt(gstAmount),
        taxable_amount_before_discount_inr: toInt(taxableAmountBeforeDiscount),
        gst_amount_before_discount_inr: toInt(gstAmountBeforeDiscount),
        service_charge_inr: toInt(service),
        first_user_offer_applied: discountAmount > 0 ? 1 : 0,
        offer_discount_inr: toInt(discountAmount),
        original_amount_inr: toInt(totalBeforeDiscount),
        final_amount_inr: toInt(total),
      }),
    [
      consultationAmount,
      discountAmount,
      gstAmount,
      gstAmountBeforeDiscount,
      gstNumber,
      paymentMeta?.call_session_id,
      paymentMeta?.order_type,
      paymentMeta?.pet_id,
      paymentMeta?.user_id,
      service,
      taxableAmount,
      taxableAmountBeforeDiscount,
      total,
      totalBeforeDiscount,
    ]
  );

  const statusClassName = useMemo(() => {
    if (statusType === "success") return "text-emerald-600";
    if (statusType === "error") return "text-red-600";
    if (statusType === "info") return "text-blue-600";
    return "text-stone-400";
  }, [statusType]);

  const hasPaymentContext = Boolean(
    petDetails && paymentMeta?.user_id && paymentMeta?.pet_id
  );

  const updateStatus = (type, message) => {
    setStatusType(type);
    setStatusMessage(message);
  };

  const handleBack = () => {
    if (onBack) {
      onBack();
      return;
    }

    navigate(PET_DETAILS_ROUTE, {
      replace: true,
      state: {
        petDetails,
        paymentMeta: {
          ...paymentMeta,
          gst_number: gstNumber || paymentMeta?.gst_number || "",
        },
      },
    });
  };

  const handlePay = async () => {
    if (isPaying) return;

    if (!acknowledged) {
      updateStatus(
        "error",
        "Please acknowledge the consultation notice to proceed."
      );
      return;
    }

    if (!hasPaymentContext) {
      updateStatus(
        "error",
        "Payment details are missing. Please submit the form again."
      );
      return;
    }

    if (!gatewayReady || typeof window === "undefined" || !window.Razorpay) {
      updateStatus("error", "Payment gateway failed to load. Please refresh.");
      return;
    }

    if (!createOrderAmountPaise || createOrderAmountPaise <= 0) {
      updateStatus("error", "Invalid consultation amount.");
      return;
    }

    setIsPaying(true);
    updateStatus("info", "Creating order...");

    try {
      const order = await apiPost("/api/create-order", {
        amount: createOrderAmountInr,
        amount_paise: createOrderAmountPaise,
        ...paymentContext,
      });

      const orderId = order?.order_id || order?.order?.id;
      const key = order?.key;

      if (!orderId || !key) {
        throw new Error("Invalid order response");
      }

      const options = {
        key,
        order_id: orderId,
        name: "Snoutiq Veterinary Consultation",
        description: "Secure consultation payment",
        handler: async (response) => {
          updateStatus("info", "Verifying payment...");
          try {
            const verify = await apiPost("/api/rzp/verify", {
              ...paymentContext,
              razorpay_order_id: response?.razorpay_order_id,
              razorpay_payment_id: response?.razorpay_payment_id,
              razorpay_signature: response?.razorpay_signature,
            });

            if (!verify?.success) {
              throw new Error(verify?.error || "Verification failed");
            }

            updateStatus("success", "Payment successful.");
            setShowSuccessModal(true);
            onPay?.(verify);
          } catch (error) {
            updateStatus(
              "error",
              error?.message || "Payment verification failed."
            );
          } finally {
            setIsPaying(false);
          }
        },
        modal: {
          ondismiss: () => {
            updateStatus("error", "Payment cancelled.");
            setIsPaying(false);
          },
        },
      };

      const rzp = new window.Razorpay(options);
      rzp.open();
      updateStatus("info", "Opening secure payment...");
    } catch (error) {
      updateStatus(
        "error",
        error?.message || "Payment failed. Please try again."
      );
      setIsPaying(false);
    }
  };

  const acknowledgementCardClass = acknowledged
    ? "border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm"
    : "border-gray-200 bg-white hover:border-blue-200 hover:bg-blue-50/40";

  if (!petDetails || !hasPaymentContext) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center px-4 py-12">
        <div className="w-full max-w-md text-center rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
          <h2 className="text-lg font-extrabold text-slate-900">
            Payment link not ready
          </h2>
          <p className="mt-2 text-sm text-slate-600">
            Please start the consultation form again so we can generate your
            payment.
          </p>
          <button
            type="button"
            onClick={() => navigate(PET_DETAILS_ROUTE, { replace: true })}
            className="mt-5 w-full rounded-2xl bg-[#1d4ed8] hover:bg-[#1e40af] text-white font-extrabold py-3 text-sm shadow-md shadow-blue-200 transition-all"
          >
            Start Consultation
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#f0f4f8] flex flex-col">
      <div className="sticky top-0 z-40 border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-5xl items-center gap-4 px-4 py-3 md:px-6">
          <button
            type="button"
            onClick={handleBack}
            className="h-8 w-8 rounded-full border border-gray-200 text-gray-600 flex items-center justify-center transition hover:bg-gray-50"
            aria-label="Go back"
          >
            <ChevronLeft size={18} />
          </button>
          <div className="flex-1 text-center text-base font-semibold text-gray-900 md:text-lg">
            Secure Payment
          </div>
          <div className="h-8 w-8" />
        </div>
      </div>

      <div className="w-full">
        <div className="flex-1 px-4 pb-44 pt-4 overflow-y-auto md:px-6 md:pb-20 md:pt-8">
          <div className="mx-auto w-full max-w-5xl">
            <ProgressBar current={3} steps={PET_FLOW_STEPS} />

            <div className="mt-6 grid gap-6 md:grid-cols-[minmax(0,1fr)_320px]">
              <div className="space-y-6">
                <div className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                  <div className="flex items-center justify-between">
                    <h3 className="text-sm font-semibold text-gray-900 md:text-base">
                      Payment Summary
                    </h3>
                    <span className="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                      Instant confirmation
                    </span>
                  </div>

                  <div className="mt-4 space-y-3 text-sm text-gray-600">
                    <div className="flex justify-between">
                      <span>Consultation Charge</span>
                      <span>Rs {formatInr(consultationAmount)}</span>
                    </div>

                    {service > 0 ? (
                      <div className="flex justify-between">
                        <span>Service Charge</span>
                        <span>Rs {formatInr(service)}</span>
                      </div>
                    ) : null}

                    {discountAmount > 0 ? (
                      <div className="flex justify-between font-semibold text-emerald-700">
                        <span>Special Discount</span>
                        <span>- Rs {formatInr(discountAmount)}</span>
                      </div>
                    ) : null}

                    <div className="flex justify-between">
                      <span>Taxable Amount</span>
                      <span>Rs {formatInr(taxableAmount)}</span>
                    </div>

                    <div className="flex justify-between">
                      <span>GST (18%)</span>
                      <span>Rs {formatInr(gstAmount)}</span>
                    </div>

                    <div className="border-t border-gray-100 pt-4 flex justify-between items-end font-semibold text-gray-900">
                      <span>Total to pay</span>
                      <div className="text-right">
                        {discountAmount > 0 ? (
                          <div className="text-[11px] font-medium text-gray-400 line-through">
                            Rs {formatInr(toInt(totalBeforeDiscount))}
                          </div>
                        ) : null}
                        <div className="text-lg md:text-base font-bold text-gray-900">
                          Rs {formatInr(createOrderAmountInr)}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                  <label className="block text-[11px] font-semibold text-gray-600">
                    GST Number (optional)
                  </label>
                  <input
                    type="text"
                    value={gstNumber}
                    onChange={(e) => setGstNumber(e.target.value)}
                    placeholder="07ABCDE1234F1Z5"
                    className="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-700 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                  />
                  <p className="mt-2 text-[11px] text-gray-500">
                    Add your GST number if a tax invoice is required.
                  </p>
                </div>

                <label
                  className={`flex cursor-pointer items-start gap-3 rounded-2xl border p-4 text-gray-600 transition-all ${acknowledgementCardClass}`}
                >
                  <input
                    type="checkbox"
                    checked={acknowledged}
                    onChange={(e) => setAcknowledged(e.target.checked)}
                    className="mt-1 h-4 w-4 shrink-0 accent-[#3998de]"
                  />
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <div className="text-sm font-semibold text-gray-900">
                        I acknowledge and agree to proceed
                      </div>
                      <span className="rounded-full border border-blue-200 bg-white/80 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700">
                        Required
                      </span>
                    </div>
                    <div className="mt-1.5 text-xs leading-5 text-gray-600">
                      I understand the limitations and conditions of this
                      consultation.
                    </div>
                    <div className="mt-2 flex items-center gap-1.5 text-[11px] font-medium text-blue-700">
                      <ShieldCheck size={12} />
                      Please confirm before continuing to payment.
                    </div>
                  </div>
                </label>

                {statusMessage ? (
                  <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                    <p className={`text-xs text-center ${statusClassName}`}>
                      {statusMessage}
                    </p>
                  </div>
                ) : null}
              </div>

              <div className="space-y-6 md:sticky md:top-24">
                <div className="hidden md:block space-y-3">
                  <Button
                    onClick={handlePay}
                    disabled={isPaying || !acknowledged}
                    fullWidth
                    className={`flex items-center justify-between md:rounded-xl md:py-4 md:px-6 text-base font-semibold ${
                      isPaying || !acknowledged
                        ? "opacity-50 cursor-not-allowed bg-gray-300"
                        : "bg-[#1d4ed8] hover:bg-[#1e40af] text-white shadow-lg shadow-blue-200"
                    }`}
                  >
                    <span>
                      {isPaying
                        ? "Processing..."
                        : `Pay Rs ${formatInr(createOrderAmountInr)}`}
                    </span>
                    <span className="flex items-center gap-2 text-white/80">
                      Proceed <ArrowRight size={18} />
                    </span>
                  </Button>

                  <p className="text-xs text-center text-gray-500 flex items-center justify-center gap-2">
                    <ShieldCheck size={14} className="text-emerald-600" />
                    Secure UPI / Card Payment
                  </p>

                  {isOfferApplied ? (
                    <p className="text-[11px] text-center font-medium text-emerald-700">
                      Special discount applied: Rs {formatInr(discountAmount)} OFF
                    </p>
                  ) : null}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur md:hidden">
        <div className="mx-auto w-full max-w-5xl px-4 pb-4 pt-3">
          <div className="mb-3 flex items-end justify-between">
            <div>
              <p className="text-[11px] font-medium text-gray-500">
                Total payable
              </p>
              <div className="flex items-center gap-2">
                {discountAmount > 0 ? (
                  <span className="text-xs text-gray-400 line-through">
                    Rs {formatInr(toInt(totalBeforeDiscount))}
                  </span>
                ) : null}
                <span className="text-lg font-bold text-gray-900">
                  Rs {formatInr(createOrderAmountInr)}
                </span>
              </div>
            </div>

            {discountAmount > 0 ? (
              <span className="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                Rs {formatInr(discountAmount)} OFF
              </span>
            ) : null}
          </div>

          <Button
            onClick={handlePay}
            disabled={isPaying || !acknowledged}
            fullWidth
            className={`flex items-center justify-between rounded-2xl py-3.5 text-sm font-semibold ${
              isPaying || !acknowledged
                ? "cursor-not-allowed bg-gray-300 opacity-50"
                : "bg-[#1d4ed8] text-white shadow-lg shadow-blue-200 hover:bg-[#1e40af]"
            }`}
          >
            <span>
              {isPaying
                ? "Processing..."
                : `Pay Rs ${formatInr(createOrderAmountInr)}`}
            </span>
            <span className="flex items-center gap-2 text-white/80">
              Proceed <ArrowRight size={16} />
            </span>
          </Button>

          <p className="mt-2 text-[10px] text-center text-gray-500 flex items-center justify-center gap-1">
            <ShieldCheck size={10} className="text-emerald-600" />
            Secure UPI / Card Payment
          </p>
        </div>
      </div>

      {showSuccessModal ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="bg-white rounded-2xl p-6 w-full max-w-sm text-center shadow-xl">
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
              <CheckCircle2 size={28} />
            </div>
            <div className="text-lg font-bold text-stone-800">
              Payment confirmed
            </div>
            <p className="text-sm text-stone-500 mt-2">
              Your payment is successful. Our team will review your request and
              contact you shortly.
            </p>
          </div>
        </div>
      ) : null}
    </div>
  );
};

export const ConfirmationScreen = ({ vet, skipConversion = false }) => {
  const navigate = useNavigate();
  const conversionFiredRef = useRef(false);

  useEffect(() => {
    if (skipConversion) return;
    if (conversionFiredRef.current) return;
    conversionFiredRef.current = true;
    if (typeof window !== "undefined" && typeof window.gtag === "function") {
      window.gtag("event", "ads_conversion_PURCHASE_1");
    }
  }, [skipConversion]);

  return (
    <div className="min-h-screen bg-white flex flex-col items-center justify-center p-8 text-center animate-fade-in md:bg-gradient-to-b md:from-white md:to-calm-bg">
      <div className="w-full md:px-10 lg:px-16">
        <div className="md:max-w-4xl md:mx-auto">
          <div className="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-pulse-slow text-green-600 mx-auto md:w-28 md:h-28">
            <CheckCircle2 size={48} className="md:hidden" />
            <CheckCircle2 size={56} className="hidden md:block" />
          </div>

          <h2 className="text-2xl font-bold text-stone-800 mb-2 md:text-4xl">
            Thank you! Consultation booked.
          </h2>

          <p className="text-stone-500 mb-8 max-w-[250px] mx-auto md:max-w-2xl md:text-lg">
            Your payment is confirmed. A Snoutiq team member will review your
            request and contact you shortly.
          </p>

          <div className="bg-stone-50 rounded-2xl p-6 w-full max-w-sm border border-stone-100 mb-8 mx-auto md:max-w-4xl md:p-10 md:rounded-3xl">
            <div className="md:grid md:grid-cols-2 md:gap-10">
              <div className="flex items-center gap-3 mb-4 text-left md:mb-0">
                <div className="bg-green-100 p-2 rounded-full text-green-700 md:p-3">
                  <MessageCircle size={20} className="md:hidden" />
                  <MessageCircle size={24} className="hidden md:block" />
                </div>
                <div>
                  <p className="text-sm font-bold text-stone-700 md:text-lg">
                    Check WhatsApp
                  </p>
                  <p className="text-xs text-stone-500 md:text-base">
                    We will send booking updates on your registered number.
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-3 text-left">
                <div className="bg-blue-100 p-2 rounded-full text-blue-700 md:p-3">
                  <Video size={20} className="md:hidden" />
                  <Video size={24} className="hidden md:block" />
                </div>
                <div>
                  <p className="text-sm font-bold text-stone-700 md:text-lg">
                    Keep your pet ready
                  </p>
                  <p className="text-xs text-stone-500 md:text-base">
                    Keep your phone charged and your pet in a calm, well-lit
                    space.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div className="mb-6 flex justify-center">
            <Button
              onClick={() => navigate("/")}
              fullWidth
              className="max-w-xs md:max-w-sm md:text-lg md:py-4 md:rounded-2xl"
            >
              Go to Home
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};
