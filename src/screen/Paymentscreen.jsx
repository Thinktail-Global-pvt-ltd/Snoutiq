import React, { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Button } from "../components/Button";
import { PET_FLOW_STEPS, ProgressBar } from "../components/Sharedcomponents";
import { apiPost } from "../lib/api";
import {
  ShieldCheck,
  ArrowRight,
  CheckCircle2,
  MessageCircle,
  Video,
  ChevronLeft,
} from "lucide-react";

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

const stripEmpty = (payload) =>
  Object.fromEntries(
    Object.entries(payload).filter(
      ([, value]) => value !== undefined && value !== null && value !== ""
    )
  );

const isDayTime = (date = new Date()) => {
  const hour = date.getHours();
  return hour >= 8 && hour < 20;
};

export const PaymentScreen = ({
  vet,
  petDetails,
  paymentMeta,
  onPay,
  onBack,
}) => {
  // ✅ slot decide: priority -> bookingRateType (from selection)
  // fallback -> current time (if parent didn't pass)
  const rateType = useMemo(() => {
    if (vet?.bookingRateType === "day" || vet?.bookingRateType === "night") {
      return vet.bookingRateType;
    }
    return isDayTime() ? "day" : "night";
  }, [vet?.bookingRateType]);

  // ✅ fee: priority -> bookingPrice (from selection), fallback -> rateType price
  const fee = useMemo(() => {
    const booking = Number(vet?.bookingPrice);
    if (Number.isFinite(booking) && booking > 0) return booking;

    const fallback = Number(rateType === "night" ? vet?.priceNight : vet?.priceDay);
    if (Number.isFinite(fallback) && fallback > 0) return fallback;

    return 0;
  }, [vet?.bookingPrice, vet?.priceDay, vet?.priceNight, rateType]);

  const slotLabel =
    rateType === "night" ? "Night (8 PM - 8 AM)" : "Day (8 AM - 8 PM)";

  // ✅ TESTING: remove service charge for now
  const service = 0;
  const gstRate = 0.18;
  const baseAmount = fee + service;
  const gstAmount = Math.round(baseAmount * gstRate);
  const total = baseAmount + gstAmount;

  const [isPaying, setIsPaying] = useState(false);
  const [gatewayReady, setGatewayReady] = useState(false);
  const [statusType, setStatusType] = useState("");
  const [statusMessage, setStatusMessage] = useState("");
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [acknowledged, setAcknowledged] = useState(false);
  const [gstNumber, setGstNumber] = useState(() =>
    pickValue(
      paymentMeta?.gst_number,
      paymentMeta?.gstNumber,
      petDetails?.gst_number,
      petDetails?.gstNumber
    ) || ""
  );

  useEffect(() => {
    const nextGst = pickValue(
      paymentMeta?.gst_number,
      paymentMeta?.gstNumber,
      petDetails?.gst_number,
      petDetails?.gstNumber
    );
    if (typeof nextGst === "string" && nextGst.trim() && !gstNumber) {
      setGstNumber(nextGst.trim());
    }
  }, [paymentMeta, petDetails, gstNumber]);

  useEffect(() => {
    let active = true;
    loadRazorpayScript().then((ready) => {
      if (active) setGatewayReady(ready);
    });
    return () => {
      active = false;
    };
  }, []);

  const paymentContext = useMemo(() => {
    const orderType =
      pickValue(
        paymentMeta?.order_type,
        paymentMeta?.orderType,
        petDetails?.order_type,
        petDetails?.orderType
      ) || "excell_export_campaign";

    const serviceId =
      pickValue(
        paymentMeta?.service_id,
        paymentMeta?.serviceId,
        vet?.service_id,
        vet?.raw?.service_id,
        petDetails?.service_id,
        petDetails?.serviceId
      ) || "consult_basic";

    const vetSlug = pickValue(
      paymentMeta?.vet_slug,
      paymentMeta?.vetSlug,
      vet?.slug,
      vet?.raw?.slug,
      vet?.raw?.vet_slug
    );

    const callSessionId = pickValue(
      paymentMeta?.call_session_id,
      paymentMeta?.callSessionId,
      petDetails?.call_session_id,
      petDetails?.callSessionId
    );

    const clinicId = toNumber(
      pickValue(
        paymentMeta?.clinic_id,
        paymentMeta?.clinicId,
        vet?.clinic_id,
        vet?.raw?.clinic_id,
        vet?.raw?.vet_registeration_id
      )
    );

    const doctorId = toNumber(
      pickValue(
        paymentMeta?.doctor_id,
        paymentMeta?.doctorId,
        vet?.doctor_id,
        vet?.id,
        vet?.raw?.doctor_id,
        vet?.raw?.id
      )
    );

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

    const gstNumberValue = pickValue(
      gstNumber,
      paymentMeta?.gst_number,
      paymentMeta?.gstNumber,
      petDetails?.gst_number,
      petDetails?.gstNumber
    );
    const gstNumberCleaned =
      typeof gstNumberValue === "string" ? gstNumberValue.trim() : gstNumberValue;
    const hasGstNumber = Boolean(gstNumberCleaned);

    // ✅ optional: pass rateType to backend if you want (only if backend supports)
    // booking_rate_type: rateType,

    return stripEmpty({
      order_type: orderType,
      clinic_id: clinicId,
      service_id: serviceId,
      vet_slug: vetSlug,
      call_session_id: callSessionId,
      pet_id: petId,
      doctor_id: doctorId,
      user_id: userId,
      gst_number: hasGstNumber ? gstNumberCleaned : undefined,
      gst_number_given: hasGstNumber ? 1 : undefined,
    });
  }, [paymentMeta, petDetails, vet, gstNumber]);

  const statusClassName = useMemo(() => {
    if (statusType === "success") return "text-emerald-600";
    if (statusType === "error") return "text-red-600";
    if (statusType === "info") return "text-blue-600";
    return "text-stone-400";
  }, [statusType]);

  const updateStatus = (type, message) => {
    setStatusType(type);
    setStatusMessage(message);
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

    if (!gatewayReady || typeof window === "undefined" || !window.Razorpay) {
      updateStatus("error", "Payment gateway failed to load. Please refresh.");
      return;
    }

    if (!total || total <= 0) {
      updateStatus("error", "Invalid consultation amount.");
      return;
    }

    setIsPaying(true);
    updateStatus("info", "Creating order...");

    try {
      // ✅ amount = total (keep same as your current backend expectation)
      const order = await apiPost("/api/create-order", {
        amount: Math.round(total),
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
        description: vet?.name
          ? `Video consultation with ${vet.name} (${rateType.toUpperCase()} slot)`
          : "Video consultation",
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
      updateStatus("error", error?.message || "Payment failed. Please try again.");
      setIsPaying(false);
    }
  };

  const doctorDisplayName = vet?.name?.split(" ")[1] || vet?.name || "Vet";
  const imageSrc = vet?.image || "";

  return (
    <div className="min-h-screen bg-[#f0f4f8] flex flex-col">
      <div className="sticky top-0 z-40 border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-5xl items-center gap-4 px-4 py-3 md:px-6">
          <button
            type="button"
            onClick={onBack}
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
        <div className="flex-1 px-4 pb-28 pt-4 overflow-y-auto md:px-6 md:pb-20 md:pt-8">
          <div className="mx-auto w-full max-w-5xl">
            <ProgressBar current={3} steps={PET_FLOW_STEPS} />

            <div className="mt-6 grid gap-6 md:grid-cols-[minmax(0,1fr)_320px]">
              <div className="space-y-6">
                <div className="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4">
                  {imageSrc ? (
                    <img
                      src={imageSrc}
                      alt={vet?.name || "Vet"}
                      className="w-12 h-12 rounded-full object-cover md:w-16 md:h-16"
                      loading="lazy"
                    />
                  ) : (
                    <div className="w-12 h-12 md:w-16 md:h-16 rounded-full bg-stone-200" />
                  )}

                  <div className="flex-1">
                    <h3 className="text-sm font-semibold text-gray-900 md:text-base">
                      Consulting Dr. {doctorDisplayName}
                    </h3>
                    <p className="text-xs text-gray-500 md:text-sm">
                      Video Consultation - 15 mins -{" "}
                      <span className="font-semibold">{slotLabel}</span>
                    </p>
                  </div>

                  <div className="hidden md:flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                    <ShieldCheck size={14} />
                    Secure
                  </div>
                </div>

                <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex gap-3">
                  <ShieldCheck className="text-emerald-600 flex-shrink-0 mt-0.5" />
                  <div className="text-xs text-emerald-900 leading-relaxed">
                    <strong>Money Safe Guarantee:</strong> If no vet connects
                    within 30 minutes, your refund is initiated automatically.
                  </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-5">
                  <div className="text-xs font-semibold text-gray-800 mb-3">
                    Before you pay
                  </div>
                  <ul className="space-y-3 text-xs text-gray-600">
                    <li className="flex items-start gap-3">
                      <span className="mt-1 h-2 w-2 rounded-full bg-gray-300" />
                      Keep your pet in a well-lit room for video.
                    </li>
                    <li className="flex items-start gap-3">
                      <span className="mt-1 h-2 w-2 rounded-full bg-gray-300" />
                      Keep previous reports or photos handy if available.
                    </li>
                    <li className="flex items-start gap-3">
                      <span className="mt-1 h-2 w-2 rounded-full bg-gray-300" />
                      You will receive a join link on WhatsApp after payment.
                    </li>
                  </ul>
                </div>

                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4">
                  <div className="text-xs font-semibold text-blue-700 mb-3">
                    After you pay - here is what happens
                  </div>
                  <div className="grid grid-cols-2 gap-4 text-xs text-gray-600 md:grid-cols-4">
                    <div className="flex flex-col items-center text-center gap-2">
                      <MessageCircle size={16} className="text-blue-600" />
                      <div className="font-semibold text-gray-800">Vet notified</div>
                      <div>Instantly sees your case</div>
                    </div>
                    <div className="flex flex-col items-center text-center gap-2">
                      <Video size={16} className="text-blue-600" />
                      <div className="font-semibold text-gray-800">Vet calls you</div>
                      <div>Usually within 8 to 15 minutes</div>
                    </div>
                    <div className="flex flex-col items-center text-center gap-2">
                      <ShieldCheck size={16} className="text-blue-600" />
                      <div className="font-semibold text-gray-800">Secure line</div>
                      <div>Call on your WhatsApp number</div>
                    </div>
                    <div className="flex flex-col items-center text-center gap-2">
                      <CheckCircle2 size={16} className="text-blue-600" />
                      <div className="font-semibold text-gray-800">Prescription</div>
                      <div>Shared after the call</div>
                    </div>
                  </div>
                </div>

                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                  <div className="text-xs font-semibold text-amber-800 mb-2">
                    Important Information
                  </div>
                  <ul className="text-xs text-amber-800/90 list-disc pl-4 space-y-1">
                    <li>Not all issues can be solved via video consultation.</li>
                    <li>Clinic visit may be required after the call.</li>
                    <li>Information shared is for this consultation session only.</li>
                  </ul>
                </div>

                <label className="flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 text-xs text-gray-600">
                  <input
                    type="checkbox"
                    checked={acknowledged}
                    onChange={(e) => setAcknowledged(e.target.checked)}
                    className="mt-1 h-4 w-4 accent-[#3998de]"
                  />
                  <div>
                    <div className="font-semibold text-gray-700">
                      I acknowledge and agree to proceed
                    </div>
                    <div className="text-gray-500">
                      I understand the limitations and conditions of this consultation.
                    </div>
                  </div>
                </label>
              </div>

              <div className="space-y-6 md:sticky md:top-24">
                  <div className="rounded-xl border border-gray-200 bg-white p-5">
                    <div className="flex items-center justify-between">
                      <h4 className="text-sm font-semibold text-gray-800">Payment Summary</h4>
                      <div className="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                        Instant confirmation
                      </div>
                    </div>

                    <div className="mt-4 space-y-3 text-sm text-gray-600">
                      <div className="flex justify-between">
                        <span>Consultation Fee</span>
                        <span>Rs {fee}</span>
                      </div>

                      {service > 0 ? (
                        <div className="flex justify-between">
                          <span>Service Charge</span>
                          <span>Rs {service}</span>
                        </div>
                      ) : null}

                      <div className="flex justify-between">
                        <span>GST (18%)</span>
                        <span>Rs {gstAmount}</span>
                      </div>

                      <div className="flex justify-between">
                        <span>Digital Prescription</span>
                        <span className="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold">
                          FREE
                        </span>
                      </div>

                      <div className="border-t border-gray-100 pt-4 flex justify-between font-semibold text-gray-900">
                        <span>Total to pay</span>
                        <span>Rs {total}</span>
                      </div>
                    </div>

                    <div className="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                      <label className="block text-[11px] font-semibold text-gray-600">
                        GST Number (optional)
                      </label>
                      <input
                        type="text"
                        value={gstNumber}
                        onChange={(e) => setGstNumber(e.target.value)}
                        placeholder="07ABCDE1234F1Z5"
                        className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-700 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                      />
                      <p className="mt-2 text-[11px] text-gray-500">
                        Add your GST number if a tax invoice is required.
                      </p>
                    </div>
                  </div>

                  <div className="rounded-xl border border-gray-200 bg-white p-4 text-xs text-gray-600">
                    <div className="flex items-center gap-2 text-sm font-semibold text-gray-800">
                      <ShieldCheck size={14} className="text-emerald-600" />
                      100% Money-Back Promise
                    </div>
                    <p className="mt-2">
                      If no vet connects within 30 minutes, your refund is initiated
                      automatically.
                    </p>
                  </div>

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
                      <span>{isPaying ? "Processing..." : `Pay Rs ${total}`}</span>
                      <span className="flex items-center gap-2 text-white/80">
                        Proceed <ArrowRight size={18} />
                      </span>
                    </Button>

                    <p className="text-xs text-center text-gray-500 flex items-center justify-center gap-2">
                      <ShieldCheck size={14} className="text-emerald-600" />
                      Secure UPI / Card Payment
                    </p>

                    {statusMessage ? (
                      <p className={`text-xs text-center ${statusClassName}`}>
                        {statusMessage}
                      </p>
                    ) : null}
                  </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button
          onClick={handlePay}
          disabled={isPaying || !acknowledged}
          fullWidth
          className={`flex items-center justify-between text-sm font-semibold ${
            isPaying || !acknowledged
              ? "opacity-50 cursor-not-allowed bg-gray-300"
              : "bg-[#1d4ed8] hover:bg-[#1e40af] text-white shadow-md"
          }`}
        >
          <span>{isPaying ? "Processing..." : `Pay Rs ${total}`}</span>
          <span className="flex items-center gap-2 text-white/80">
            Proceed <ArrowRight size={16} />
          </span>
        </Button>

        <p className="text-[10px] text-center text-gray-500 mt-2 flex items-center justify-center gap-1">
          <ShieldCheck size={10} className="text-emerald-600" /> Secure UPI / Card Payment
        </p>

        {statusMessage ? (
          <p className={`text-xs text-center mt-2 ${statusClassName}`}>
            {statusMessage}
          </p>
        ) : null}
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
              Your booking is confirmed. Connecting you to the vet...
            </p>
          </div>
        </div>
      ) : null}
    </div>
  );
};

export const ConfirmationScreen = ({ vet }) => {
  const navigate = useNavigate();
  const conversionFiredRef = useRef(false);

  useEffect(() => {
    if (conversionFiredRef.current) return;
    conversionFiredRef.current = true;
    if (typeof window !== "undefined" && typeof window.gtag === "function") {
      window.gtag("event", "ads_conversion_PURCHASE_1");
    }
  }, []);

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
            <strong className="text-stone-700">{vet?.name || "Your vet"}</strong>{" "}
            has been notified and will respond in about 10-15 minutes.
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
                    We sent you a link to join the video call.
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
                    Prepare your pet
                  </p>
                  <p className="text-xs text-stone-500 md:text-base">
                    Keep them in a well-lit room.
                  </p>
                </div>
              </div>
            </div>

            <div className="hidden md:block mt-8 text-left text-base text-stone-500">
              Tip: Keep your phone charged and place the pet near natural light
              for a clearer video.
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

          <p className="text-sm text-stone-400 italic md:text-base">
            "You can relax - help is on the way."
          </p>
        </div>
      </div>
    </div>
  );
};

