import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { Button } from "../../components/Button";
import { PET_FLOW_STEPS, ProgressBar } from "../../components/Sharedcomponents";
import { apiPost } from "../../lib/api";
import {
  ShieldCheck,
  ArrowRight,
  BadgeCheck,
  CheckCircle2,
  ChevronLeft,
  CreditCard,
  Lock,
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
  const paymentPetName =
    pickValue(petDetails?.name, petDetails?.pet_name, petDetails?.petName) ||
    "Your pet";
  const paymentPetType =
    pickValue(
      petDetails?.type,
      petDetails?.species,
      petDetails?.pet_type,
      petDetails?.petType
    ) || "Not selected";
  const paymentLocation =
    pickValue(petDetails?.city, petDetails?.location, petDetails?.area) ||
    "Not added";
  const paymentOwnerName =
    pickValue(
      petDetails?.ownerName,
      petDetails?.owner_name,
      petDetails?.owner?.name
    ) || "Pet parent";

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
    ? "border-[#bfd0ff] bg-[linear-gradient(135deg,#f7faff_0%,#eef4ff_100%)] shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)]"
    : "border-[#d6e3ff] bg-white hover:border-[#bfd0ff] hover:bg-[#f8fbff]";

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
            onClick={handleBack}
            className="mt-5 w-full rounded-2xl bg-[#1d4ed8] hover:bg-[#1e40af] text-white font-extrabold py-3 text-sm shadow-md shadow-blue-200 transition-all"
          >
            Start Consultation
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.14),_transparent_28%),linear-gradient(180deg,#f8fbff_0%,#eef4ff_100%)] flex flex-col">
      <div className="sticky top-0 z-40 border-b border-[#dbe5ff] bg-white/90 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6">
          <button
            type="button"
            onClick={handleBack}
            className="flex h-9 w-9 items-center justify-center rounded-full border border-[#d6e3ff] bg-white text-slate-600 transition hover:bg-[#f8fbff]"
            aria-label="Go back"
          >
            <ChevronLeft size={18} />
          </button>
          <div className="min-w-0 flex-1">
            <div className="text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-[#4f6bff] md:text-left">
              Snoutiq Checkout
            </div>
            <div className="text-center text-base font-semibold text-slate-900 md:text-left md:text-lg">
              Secure Razorpay payment
            </div>
          </div>
          <div className="hidden items-center gap-2 rounded-full border border-[#d6e3ff] bg-[#f8fbff] px-3 py-1.5 text-xs font-semibold text-[#2457ff] md:flex">
            <Lock size={14} />
            Powered by Razorpay
          </div>
        </div>
      </div>

      <div className="w-full">
        <div className="flex-1 overflow-y-auto px-4 pb-44 pt-4 md:px-6 md:pb-20 md:pt-8">
          <div className="mx-auto w-full max-w-6xl">
            <div className="md:flex md:items-center md:justify-between md:gap-6">
              <ProgressBar current={3} steps={PET_FLOW_STEPS} />
              <div className="hidden items-center gap-2 rounded-full border border-[#d6e3ff] bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-sm md:flex">
                <BadgeCheck size={14} className="text-[#2457ff]" />
                Final payment step
              </div>
            </div>

            <div className="mt-4 overflow-hidden rounded-[28px] border border-[#d6e3ff] bg-[linear-gradient(135deg,#072a9b_0%,#1457ff_50%,#6ba3ff_100%)] shadow-[0_24px_70px_-42px_rgba(20,87,255,0.8)]">
              <div className="grid gap-6 px-5 py-6 text-white md:grid-cols-[minmax(0,1fr)_250px] md:px-7 md:py-7">
                <div>
                  <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/90">
                    <Lock size={12} />
                    Step 2 of 2
                  </div>
                  <h1 className="mt-4 text-2xl font-semibold tracking-tight md:text-[30px]">
                    Review and pay on a Razorpay-style secure checkout
                  </h1>
                  <p className="mt-3 max-w-2xl text-sm leading-6 text-white/82 md:text-[15px]">
                    Your consultation request is saved. Complete payment to confirm
                    the booking and receive next updates on WhatsApp.
                  </p>
                  <div className="mt-5 flex flex-wrap items-center gap-2 text-xs text-white/88">
                    <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1">
                      UPI / Cards / Net Banking
                    </span>
                    <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1">
                      Instant confirmation
                    </span>
                    <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1">
                      GST invoice available
                    </span>
                  </div>
                </div>

                <div className="rounded-[24px] border border-white/15 bg-white/10 p-4 backdrop-blur">
                  <div className="text-xs font-semibold uppercase tracking-[0.16em] text-white/75">
                    Payable now
                  </div>
                  {discountAmount > 0 ? (
                    <div className="mt-2 text-xs text-white/65 line-through">
                      Rs {formatInr(toInt(totalBeforeDiscount))}
                    </div>
                  ) : null}
                  <div className="mt-1 text-3xl font-semibold text-white">
                    Rs {formatInr(createOrderAmountInr)}
                  </div>
                  <div className="mt-4 space-y-2 text-sm text-white/82">
                    <div className="flex items-center justify-between">
                      <span>Pet</span>
                      <span className="font-medium text-white">{paymentPetName}</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span>Parent</span>
                      <span className="font-medium text-white">{paymentOwnerName}</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span>Location</span>
                      <span className="font-medium text-white">{paymentLocation}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-6 grid gap-6 md:grid-cols-[minmax(0,1fr)_340px]">
              <div className="space-y-6">
                <div className="rounded-[28px] border border-[#d6e3ff] bg-white/95 p-5 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)] md:p-6">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <h3 className="text-sm font-semibold text-slate-900 md:text-base">
                        Payment summary
                      </h3>
                      <p className="mt-1 text-xs text-slate-500">
                        Same amount that opens on Razorpay
                      </p>
                    </div>
                    <span className="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                      Instant confirmation
                    </span>
                  </div>

                  <div className="mt-5 space-y-3 text-sm text-slate-600">
                    <div className="flex justify-between">
                      <span>Consultation charge</span>
                      <span>Rs {formatInr(consultationAmount)}</span>
                    </div>

                    {service > 0 ? (
                      <div className="flex justify-between">
                        <span>Service charge</span>
                        <span>Rs {formatInr(service)}</span>
                      </div>
                    ) : null}

                    {discountAmount > 0 ? (
                      <div className="flex justify-between font-semibold text-emerald-700">
                        <span>Special discount</span>
                        <span>- Rs {formatInr(discountAmount)}</span>
                      </div>
                    ) : null}

                    <div className="flex justify-between">
                      <span>Taxable amount</span>
                      <span>Rs {formatInr(taxableAmount)}</span>
                    </div>

                    <div className="flex justify-between">
                      <span>GST (18%)</span>
                      <span>Rs {formatInr(gstAmount)}</span>
                    </div>

                    <div className="flex items-end justify-between border-t border-[#e7efff] pt-4 font-semibold text-slate-900">
                      <span>Total to pay</span>
                      <div className="text-right">
                        {discountAmount > 0 ? (
                          <div className="text-[11px] font-medium text-slate-400 line-through">
                            Rs {formatInr(toInt(totalBeforeDiscount))}
                          </div>
                        ) : null}
                        <div className="text-xl font-bold text-slate-900">
                          Rs {formatInr(createOrderAmountInr)}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="rounded-[28px] border border-[#d6e3ff] bg-white/95 p-5 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)]">
                  <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#4f6bff]">
                    <CreditCard size={14} />
                    Invoice details
                  </div>
                  <label className="mt-4 block text-[11px] font-semibold text-slate-600">
                    GST Number (optional)
                  </label>
                  <input
                    type="text"
                    value={gstNumber}
                    onChange={(e) => setGstNumber(e.target.value)}
                    placeholder="07ABCDE1234F1Z5"
                    className="mt-2 w-full rounded-2xl border border-[#d6e3ff] bg-[#fbfdff] px-3 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#2457ff] focus:outline-none focus:ring-4 focus:ring-[#4f6bff]/12"
                  />
                  <p className="mt-2 text-[11px] text-slate-500">
                    Add your GST number if a tax invoice is required.
                  </p>
                </div>

                <label
                  className={`flex cursor-pointer items-start gap-3 rounded-[28px] border p-5 text-slate-600 transition-all ${acknowledgementCardClass}`}
                >
                  <input
                    type="checkbox"
                    checked={acknowledged}
                    onChange={(e) => setAcknowledged(e.target.checked)}
                    className="mt-1 h-4 w-4 shrink-0 accent-[#2457ff]"
                  />
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <div className="text-sm font-semibold text-slate-900">
                        I acknowledge and agree to proceed
                      </div>
                      <span className="rounded-full border border-[#bfd0ff] bg-white/90 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#2457ff]">
                        Required
                      </span>
                    </div>
                    <div className="mt-1.5 text-xs leading-5 text-slate-600">
                      I understand the limitations and conditions of this
                      consultation.
                    </div>
                    <div className="mt-3 flex items-center gap-1.5 text-[11px] font-medium text-[#2457ff]">
                      <ShieldCheck size={12} />
                      Please confirm before continuing to payment.
                    </div>
                  </div>
                </label>

                {statusMessage ? (
                  <div className="rounded-[24px] border border-[#d6e3ff] bg-white/95 px-4 py-3 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)]">
                    <p className={`text-xs text-center ${statusClassName}`}>
                      {statusMessage}
                    </p>
                  </div>
                ) : null}
              </div>

              <div className="space-y-4 md:sticky md:top-28">
                <div className="rounded-[28px] border border-[#d6e3ff] bg-white/95 p-5 shadow-[0_18px_45px_-30px_rgba(37,99,235,0.35)]">
                  <div className="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#4f6bff]">
                    Booking summary
                  </div>
                  <div className="mt-4 space-y-3 text-sm text-slate-600">
                    <div className="flex items-center justify-between gap-3">
                      <span>Pet parent</span>
                      <span className="text-right font-medium text-slate-900">
                        {paymentOwnerName}
                      </span>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                      <span>Pet</span>
                      <span className="text-right font-medium text-slate-900">
                        {paymentPetName}
                      </span>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                      <span>Pet type</span>
                      <span className="text-right font-medium text-slate-900">
                        {paymentPetType}
                      </span>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                      <span>Location</span>
                      <span className="text-right font-medium text-slate-900">
                        {paymentLocation}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="hidden overflow-hidden rounded-[28px] border border-[#d6e3ff] bg-[linear-gradient(135deg,#0b2fa6_0%,#1457ff_55%,#4f8cff_100%)] p-5 text-white shadow-[0_24px_70px_-42px_rgba(20,87,255,0.8)] md:block">
                  <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/90">
                    <Lock size={12} />
                    Pay securely
                  </div>
                  <div className="mt-3 text-2xl font-semibold">
                    Rs {formatInr(createOrderAmountInr)}
                  </div>
                  <p className="mt-2 text-sm leading-6 text-white/82">
                    Razorpay opens UPI, cards and net banking after you confirm
                    below.
                  </p>

                  <Button
                    onClick={handlePay}
                    disabled={isPaying || !acknowledged}
                    fullWidth
                    className={`mt-5 flex items-center justify-between rounded-2xl px-5 py-4 text-base font-semibold ${
                      isPaying || !acknowledged
                        ? "cursor-not-allowed bg-white/30 text-white opacity-50"
                        : "bg-white text-[#1457ff] shadow-[0_20px_40px_-22px_rgba(15,23,42,0.35)] hover:bg-[#f8fbff]"
                    }`}
                  >
                    <span>
                      {isPaying
                        ? "Processing..."
                        : `Pay Rs ${formatInr(createOrderAmountInr)}`}
                    </span>
                    <span className="flex items-center gap-2">
                      Proceed <ArrowRight size={18} />
                    </span>
                  </Button>

                  <div className="mt-4 flex flex-wrap items-center gap-2 text-[11px] text-white/82">
                    <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1">
                      Secure UPI / Card
                    </span>
                    <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1">
                      Powered by Razorpay
                    </span>
                  </div>

                  {isOfferApplied ? (
                    <p className="mt-4 text-[11px] font-medium text-emerald-200">
                      Special discount applied: Rs {formatInr(discountAmount)} OFF
                    </p>
                  ) : null}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="fixed inset-x-0 bottom-0 z-30 border-t border-[#d6e3ff] bg-white/95 backdrop-blur md:hidden">
        <div className="mx-auto w-full max-w-5xl px-4 pb-4 pt-3">
          <div className="mb-3 flex items-end justify-between">
            <div>
              <p className="text-[11px] font-medium text-slate-500">
                Total payable
              </p>
              <div className="flex items-center gap-2">
                {discountAmount > 0 ? (
                  <span className="text-xs text-slate-400 line-through">
                    Rs {formatInr(toInt(totalBeforeDiscount))}
                  </span>
                ) : null}
                <span className="text-lg font-bold text-slate-900">
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
                ? "cursor-not-allowed bg-slate-300 text-white opacity-50"
                : "bg-[linear-gradient(135deg,#1457ff_0%,#2563eb_55%,#5b8dff_100%)] text-white shadow-[0_20px_45px_-22px_rgba(20,87,255,0.7)]"
            }`}
          >
            <span>
              {isPaying
                ? "Processing..."
                : `Pay Rs ${formatInr(createOrderAmountInr)}`}
            </span>
            <span className="flex items-center gap-2 text-white/90">
              Proceed <ArrowRight size={16} />
            </span>
          </Button>

          <p className="mt-2 text-[10px] text-center text-slate-500 flex items-center justify-center gap-1">
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
