import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { Button } from "../../components/Button";
import { apiPost } from "../../lib/api";
import {
  CheckCircle2,
  ChevronLeft,
  MessageCircle,
  Video,
} from "lucide-react";

const FLOW_STORAGE_KEY = "snoutiq-video-call-copied-flow";
const PET_DETAILS_ROUTE = "/video-call-pet-details";
const ASK_HOME_ROUTE = "/ask";
const CONSULTATION_BOOKED_ROUTE = "/consultation-booked";
const STATIC_CONSULTATION_AMOUNT = 599;
const STATIC_SERVICE_AMOUNT = 0;
const STATIC_DISCOUNT_AMOUNT = 100;
const GST_RATE = 0.18;
const SUCCESS_REDIRECT_DELAY = 3000;
const DESCRIBE_CONSULT_POINTS = Object.freeze([
  "Not all issues can be solved via video consultation.",
  "Clinic visit may be required after the call.",
  "Information shared is for this consultation session only.",
]);

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
  onSuccessHome,
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
  const [successfulPayment, setSuccessfulPayment] = useState(null);
  const [acknowledged, setAcknowledged] = useState(false);
  const [gstNumber, setGstNumber] = useState(
    () => paymentMeta?.gst_number || ""
  );
  const shouldShowSuccessHomeButton =
    typeof onSuccessHome === "function" || !onPay;

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

  useEffect(() => {
    if (!successfulPayment) return undefined;

    setShowSuccessModal(true);
  }, [successfulPayment]);

  useEffect(() => {
    if (!successfulPayment || shouldShowSuccessHomeButton) return undefined;

    const timeoutId = window.setTimeout(() => {
      if (onPay) {
        onPay(successfulPayment);
        return;
      }

      navigate(CONSULTATION_BOOKED_ROUTE, {
        replace: true,
        state: {
          vet,
          verify: successfulPayment,
          skipConversion: true,
        },
      });
    }, SUCCESS_REDIRECT_DELAY);

    return () => window.clearTimeout(timeoutId);
  }, [navigate, onPay, shouldShowSuccessHomeButton, successfulPayment, vet]);

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
    if (statusType === "success") {
      return "border-emerald-200 bg-emerald-50 text-emerald-700";
    }
    if (statusType === "error") {
      return "border-red-200 bg-red-50 text-red-700";
    }
    if (statusType === "info") {
      return "border-blue-200 bg-blue-50 text-blue-700";
    }
    return "border-slate-200 bg-slate-50 text-slate-500";
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

  const handleSuccessHome = () => {
    if (!successfulPayment) return;
    setShowSuccessModal(false);

    if (typeof onSuccessHome === "function") {
      onSuccessHome(successfulPayment);
      return;
    }

    navigate(ASK_HOME_ROUTE, { replace: true });
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

            updateStatus(
              "success",
              shouldShowSuccessHomeButton
                ? "Payment successful."
                : "Payment successful. Redirecting in 3 seconds."
            );
            setSuccessfulPayment(verify);
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
    ? "border-[#bcd2ff] bg-[#eff5ff]"
    : "border-[#dbe7ff] bg-white";
  const payButtonHelperText = !acknowledged
    ? "Please accept the consultation confirmation to continue."
    : "";

  if (!petDetails || !hasPaymentContext) {
    return (
      <div className="min-h-screen bg-[#f8fbff] px-4 py-12">
        <div className="mx-auto w-full max-w-md rounded-[24px] border border-slate-200 bg-white p-6 text-center shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">
            Payment not ready
          </h2>
          <p className="mt-2 text-sm text-slate-600">
            Please start the consultation again.
          </p>
          <button
            type="button"
            onClick={handleBack}
            className="mt-5 w-full rounded-2xl bg-[#2563eb] py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]"
          >
            Start Consultation
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#f8fbff] text-slate-900">
      <div className="sticky top-0 z-40 border-b border-[#e3ecff] bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-lg items-center gap-3 px-4 py-3">
          <button
            type="button"
            onClick={handleBack}
            className="flex h-10 w-10 items-center justify-center rounded-full border border-[#dbe7ff] bg-white text-slate-600 transition hover:bg-[#f8fbff]"
            aria-label="Go back"
          >
            <ChevronLeft size={18} />
          </button>
          <div className="min-w-0 flex-1">
            <div className="text-base font-semibold text-slate-900">
              Complete payment
            </div>
            <div className="text-xs text-slate-500">
              Secure consultation checkout
            </div>
          </div>
        </div>
      </div>

      <div className="px-4 pb-36 pt-4 md:pb-40 md:pt-6">
        <div className="mx-auto max-w-lg space-y-4">
          <div className="rounded-[24px] border border-[#dbe7ff] bg-white p-5 shadow-[0_10px_30px_-24px_rgba(37,99,235,0.35)]">
            <div className="text-sm font-semibold text-slate-900">
              Consultation summary
            </div>
            <div className="mt-4 space-y-3 text-sm">
              <div className="flex items-center justify-between gap-4">
                <span className="text-slate-500">Pet name</span>
                <span className="text-right font-medium text-slate-900">
                  {paymentPetName}
                </span>
              </div>
              <div className="flex items-center justify-between gap-4">
                <span className="text-slate-500">Pet type</span>
                <span className="text-right font-medium text-slate-900">
                  {paymentPetType}
                </span>
              </div>
              <div className="flex items-center justify-between gap-4">
                <span className="text-slate-500">Pet Parent name</span>
                <span className="text-right font-medium text-slate-900">
                  {paymentOwnerName}
                </span>
              </div>
              <div className="flex items-center justify-between gap-4">
                <span className="text-slate-500">Location</span>
                <span className="text-right font-medium text-slate-900">
                  {paymentLocation}
                </span>
              </div>
            </div>
          </div>

          <div className="rounded-[24px] border border-[#dbe7ff] bg-white p-5 shadow-[0_10px_30px_-24px_rgba(37,99,235,0.35)]">
            <div className="text-sm font-semibold text-slate-900">
              Amount details
            </div>
            <div className="mt-4 space-y-3 text-sm text-slate-600">
              <div className="flex items-center justify-between gap-4">
                <span>Consultation fee</span>
                <span className="font-medium text-slate-900">
                  Rs {formatInr(consultationAmount)}
                </span>
              </div>

              {discountAmount > 0 ? (
                <div className="flex items-center justify-between gap-4 text-emerald-700">
                  <span>Discount</span>
                  <span className="font-medium">
                    - Rs {formatInr(discountAmount)}
                  </span>
                </div>
              ) : null}

              <div className="flex items-center justify-between gap-4">
                <span>GST (18%)</span>
                <span className="font-medium text-slate-900">
                  Rs {formatInr(gstAmount)}
                </span>
              </div>

              <div className="flex items-end justify-between gap-4 border-t border-[#e8efff] pt-4">
                <span className="font-semibold text-slate-900">Total payable</span>
                <div className="text-right">
                  {discountAmount > 0 ? (
                    <div className="text-[11px] text-slate-400 line-through">
                      Rs {formatInr(toInt(totalBeforeDiscount))}
                    </div>
                  ) : null}
                  <div className="text-xl font-semibold text-slate-900">
                    Rs {formatInr(createOrderAmountInr)}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-[24px] border border-[#dbe7ff] bg-white p-5 shadow-[0_10px_30px_-24px_rgba(37,99,235,0.35)]">
            <label className="block text-sm font-semibold text-slate-900">
              GST number
            </label>
            <input
              type="text"
              value={gstNumber}
              onChange={(e) => setGstNumber(e.target.value)}
              placeholder="07ABCDE1234F1Z5"
              className="mt-3 w-full rounded-2xl border border-[#dbe7ff] bg-[#fbfdff] px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#2563eb] focus:outline-none focus:ring-4 focus:ring-[#2563eb]/10"
            />
            <p className="mt-2 text-xs text-slate-500">
              Add GST number if needed.
            </p>
          </div>

          <div className="rounded-[24px] border border-red-200 bg-red-50 p-4">
            <div className="space-y-2">
              {DESCRIBE_CONSULT_POINTS.map((point) => (
                <div key={point} className="flex items-start gap-2 text-sm text-red-700">
                  <span className="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-red-500" />
                  <span>{point}</span>
                </div>
              ))}
            </div>
          </div>

          <label
            className={`flex cursor-pointer items-center gap-3 rounded-[24px] border p-4 text-sm text-slate-700 transition ${acknowledgementCardClass}`}
          >
            <input
              type="checkbox"
              checked={acknowledged}
              onChange={(e) => setAcknowledged(e.target.checked)}
              className="h-4 w-4 shrink-0 accent-[#2563eb]"
            />
            <span className="font-medium text-slate-900">
              I agree to continue with this consultation.
            </span>
          </label>

          {statusMessage ? (
            <div className={`rounded-[20px] border px-4 py-3 text-sm ${statusClassName}`}>
              {statusMessage}
            </div>
          ) : null}
        </div>
      </div>

      <div className="fixed inset-x-0 bottom-0 z-30 border-t border-[#dbe7ff] bg-white/95 backdrop-blur">
        <div className="mx-auto w-full max-w-lg px-4 pb-[calc(16px+env(safe-area-inset-bottom))] pt-3">
          <div className="mb-3 flex items-end justify-between gap-4">
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
          </div>

          {payButtonHelperText ? (
            <p className="mb-3 text-xs font-medium text-slate-500">
              {payButtonHelperText}
            </p>
          ) : null}

          <Button
            onClick={handlePay}
            disabled={isPaying || !acknowledged}
            fullWidth
            className={`rounded-2xl py-3.5 text-sm font-semibold ${
              isPaying || !acknowledged
                ? "cursor-not-allowed bg-slate-300 text-white opacity-50"
                : "bg-[#2563eb] text-white shadow-[0_18px_36px_-22px_rgba(37,99,235,0.7)] hover:bg-[#1d4ed8]"
            }`}
          >
            {isPaying
              ? "Processing..."
              : `Pay Rs ${formatInr(createOrderAmountInr)}`}
          </Button>
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
              {shouldShowSuccessHomeButton
                ? "Thank you. Your payment is confirmed."
                : "Payment successful. Redirecting in 3 seconds."}
            </p>
            {shouldShowSuccessHomeButton ? (
              <button
                type="button"
                onClick={handleSuccessHome}
                className="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-[#2563eb] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]"
              >
                Home
              </button>
            ) : null}
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
