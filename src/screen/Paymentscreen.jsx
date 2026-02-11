// import React, { useEffect, useMemo, useState } from "react";
// import { Button } from "../components/Button";
// import { Header, ProgressBar } from "../components/Sharedcomponents";
// import { apiPost } from "../lib/api";
// import {
//   ShieldCheck,
//   ArrowRight,
//   CheckCircle2,
//   MessageCircle,
//   Video,
// } from "lucide-react";

// const loadRazorpayScript = () =>
//   new Promise((resolve) => {
//     if (typeof window !== "undefined" && window.Razorpay) {
//       resolve(true);
//       return;
//     }
//     const script = document.createElement("script");
//     script.src = "https://checkout.razorpay.com/v1/checkout.js";
//     script.onload = () => resolve(true);
//     script.onerror = () => resolve(false);
//     document.body.appendChild(script);
//   });

// const pickValue = (...values) => {
//   for (const value of values) {
//     if (value === undefined || value === null) continue;
//     if (typeof value === "string") {
//       const trimmed = value.trim();
//       if (trimmed) return trimmed;
//       continue;
//     }
//     return value;
//   }
//   return undefined;
// };

// const toNumber = (value) => {
//   if (value === undefined || value === null || value === "") return undefined;
//   const n = Number(value);
//   return Number.isFinite(n) ? n : undefined;
// };

// const stripEmpty = (payload) =>
//   Object.fromEntries(
//     Object.entries(payload).filter(
//       ([, value]) => value !== undefined && value !== null && value !== ""
//     )
//   );

// export const PaymentScreen = ({ vet, petDetails, paymentMeta, onPay, onBack }) => {
//   const fee = Number(vet?.priceDay) || 0;
//   const service = 20;
//   const total = fee + service;
//   const [isPaying, setIsPaying] = useState(false);
//   const [gatewayReady, setGatewayReady] = useState(false);
//   const [statusType, setStatusType] = useState("");
//   const [statusMessage, setStatusMessage] = useState("");
//   const [showSuccessModal, setShowSuccessModal] = useState(false);

//   useEffect(() => {
//     let active = true;
//     loadRazorpayScript().then((ready) => {
//       if (active) setGatewayReady(ready);
//     });
//     return () => {
//       active = false;
//     };
//   }, []);

//   const paymentContext = useMemo(() => {
//     const orderType =
//       pickValue(
//         paymentMeta?.order_type,
//         paymentMeta?.orderType,
//         petDetails?.order_type,
//         petDetails?.orderType
//       ) || "excell_export_campaign";

//     const serviceId =
//       pickValue(
//         paymentMeta?.service_id,
//         paymentMeta?.serviceId,
//         vet?.service_id,
//         vet?.raw?.service_id,
//         petDetails?.service_id,
//         petDetails?.serviceId
//       ) || "consult_basic";

//     const vetSlug = pickValue(
//       paymentMeta?.vet_slug,
//       paymentMeta?.vetSlug,
//       vet?.slug,
//       vet?.raw?.slug,
//       vet?.raw?.vet_slug
//     );

//     const callSessionId = pickValue(
//       paymentMeta?.call_session_id,
//       paymentMeta?.callSessionId,
//       petDetails?.call_session_id,
//       petDetails?.callSessionId
//     );

//     const clinicId = toNumber(
//       pickValue(
//         paymentMeta?.clinic_id,
//         paymentMeta?.clinicId,
//         vet?.clinic_id,
//         vet?.raw?.clinic_id,
//         vet?.raw?.vet_registeration_id
//       )
//     );

//     const doctorId = toNumber(
//       pickValue(
//         paymentMeta?.doctor_id,
//         paymentMeta?.doctorId,
//         vet?.doctor_id,
//         vet?.id,
//         vet?.raw?.doctor_id,
//         vet?.raw?.id
//       )
//     );

//     const userId = toNumber(
//       pickValue(
//         paymentMeta?.user_id,
//         paymentMeta?.userId,
//         petDetails?.user_id,
//         petDetails?.userId,
//         petDetails?.data?.user_id,
//         petDetails?.data?.userId,
//         petDetails?.user?.id,
//         petDetails?.observation?.user_id,
//         petDetails?.observation?.userId,
//         petDetails?.observation?.user?.id,
//         petDetails?.observationResponse?.user_id,
//         petDetails?.observationResponse?.userId,
//         petDetails?.observationResponse?.user?.id,
//         petDetails?.observationResponse?.data?.user_id,
//         petDetails?.observationResponse?.data?.userId,
//         petDetails?.observationResponse?.data?.user?.id,
//         petDetails?.observationResponse?.data?.data?.user_id,
//         petDetails?.observationResponse?.data?.data?.userId,
//         petDetails?.observationResponse?.data?.data?.user?.id
//       )
//     );

//     const petId = toNumber(
//       pickValue(
//         paymentMeta?.pet_id,
//         paymentMeta?.petId,
//         petDetails?.pet_id,
//         petDetails?.petId,
//         petDetails?.data?.pet_id,
//         petDetails?.data?.petId,
//         petDetails?.pet?.id,
//         petDetails?.observation?.pet_id,
//         petDetails?.observation?.petId,
//         petDetails?.observation?.pet?.id,
//         petDetails?.observationResponse?.pet_id,
//         petDetails?.observationResponse?.petId,
//         petDetails?.observationResponse?.pet?.id,
//         petDetails?.observationResponse?.data?.pet_id,
//         petDetails?.observationResponse?.data?.petId,
//         petDetails?.observationResponse?.data?.pet?.id,
//         petDetails?.observationResponse?.data?.data?.pet_id,
//         petDetails?.observationResponse?.data?.data?.petId,
//         petDetails?.observationResponse?.data?.data?.pet?.id
//       )
//     );

//     return stripEmpty({
//       order_type: orderType,
//       clinic_id: clinicId,
//       service_id: serviceId,
//       vet_slug: vetSlug,
//       call_session_id: callSessionId,
//       pet_id: petId,
//       doctor_id: doctorId,
//       user_id: userId,
//     });
//   }, [paymentMeta, petDetails, vet]);

//   const statusClassName = useMemo(() => {
//     if (statusType === "success") return "text-emerald-600";
//     if (statusType === "error") return "text-red-600";
//     if (statusType === "info") return "text-blue-600";
//     return "text-stone-400";
//   }, [statusType]);

//   const updateStatus = (type, message) => {
//     setStatusType(type);
//     setStatusMessage(message);
//   };

//   const handlePay = async () => {
//     if (isPaying) return;
//     if (!gatewayReady || typeof window === "undefined" || !window.Razorpay) {
//       updateStatus("error", "Payment gateway failed to load. Please refresh.");
//       return;
//     }

//     setIsPaying(true);
//     updateStatus("info", "Creating order...");

//     try {
//       const order = await apiPost("/api/create-order", {
//         amount: Math.round(total),
//         ...paymentContext,
//       });

//       const orderId = order?.order_id || order?.order?.id;
//       const key = order?.key;

//       if (!orderId || !key) {
//         throw new Error("Invalid order response");
//       }

//       const options = {
//         key,
//         order_id: orderId,
//         name: "Snoutiq Veterinary Consultation",
//         description: vet?.name
//           ? `Video consultation with ${vet.name}`
//           : "Video consultation",
//         handler: async (response) => {
//           updateStatus("info", "Verifying payment...");
//           try {
//             const verify = await apiPost("/api/rzp/verify", {
//               ...paymentContext,
//               razorpay_order_id: response?.razorpay_order_id,
//               razorpay_payment_id: response?.razorpay_payment_id,
//               razorpay_signature: response?.razorpay_signature,
//             });

//             if (!verify?.success) {
//               throw new Error(verify?.error || "Verification failed");
//             }

//             updateStatus("success", "Payment successful.");
//             setShowSuccessModal(true);
//             onPay?.(verify);
//           } catch (error) {
//             updateStatus(
//               "error",
//               error?.message || "Payment verification failed."
//             );
//           } finally {
//             setIsPaying(false);
//           }
//         },
//         modal: {
//           ondismiss: () => {
//             updateStatus("error", "Payment cancelled.");
//             setIsPaying(false);
//           },
//         },
//       };

//       const rzp = new window.Razorpay(options);
//       rzp.open();
//       updateStatus("info", "Opening secure payment...");
//     } catch (error) {
//       updateStatus("error", error?.message || "Payment failed. Please try again.");
//       setIsPaying(false);
//     }
//   };

//   return (
//     <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
//       <Header onBack={onBack} title="Secure Payment" />

//       {/* Desktop: FULL WIDTH (no max container). Mobile stays same. */}
//       <div className="w-full">
//         <div className="flex-1 px-4 py-6 overflow-y-auto md:px-10 md:py-12 lg:px-16">
//           <ProgressBar current={3} total={3} />

//           {/* Layout: mobile = stacked, md+ = 2 columns */}
//           <div className="mt-6 md:grid md:grid-cols-12 md:gap-10 lg:gap-14">
//             {/* LEFT: Summary + reassurance */}
//             <div className="md:col-span-7 lg:col-span-7">
//               {/* Selected Vet Summary */}
//               <div className="bg-white p-4 rounded-2xl shadow-sm border border-brand-100 mb-6 flex gap-4 items-center md:p-7 md:rounded-3xl">
//                 <img
//                   src={vet?.image}
//                   alt={vet?.name}
//                   className="w-12 h-12 rounded-full object-cover md:w-16 md:h-16"
//                 />

//                 <div className="flex-1">
//                   <h3 className="font-bold text-stone-800 text-sm md:text-lg lg:text-xl">
//                     Consulting Dr. {vet?.name?.split(" ")[1] || vet?.name}
//                   </h3>
//                   <p className="text-xs text-stone-500 md:text-base">
//                     Video Consultation • 15 mins
//                   </p>
//                 </div>

//                 {/* Desktop badge */}
//                 <div className="hidden md:flex items-center gap-2 text-sm font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 px-4 py-2 rounded-full">
//                   <ShieldCheck size={16} />
//                   Secure
//                 </div>
//               </div>

//               {/* Reassurance */}
//               <div className="bg-brand-50 p-4 rounded-2xl border border-brand-100 flex gap-3 mb-8 md:p-7 md:rounded-3xl">
//                 <ShieldCheck className="text-brand-600 flex-shrink-0 md:mt-0.5" />
//                 <div className="text-xs text-brand-800 leading-relaxed md:text-base">
//                   <strong>Money Safe Guarantee:</strong> If the vet doesn&apos;t
//                   respond within 20 minutes, we will immediately reassign another
//                   senior vet or refund your money instantly.
//                 </div>
//               </div>

//               {/* Extra desktop-only tips card (no mobile change) */}
//               <div className="hidden md:block bg-white rounded-3xl border border-stone-100 shadow-sm p-8">
//                 <div className="text-base font-bold text-stone-800 mb-4">
//                   Before you pay
//                 </div>
//                 <ul className="text-base text-stone-600 space-y-3">
//                   <li className="flex items-start gap-3">
//                     <span className="mt-2 h-2 w-2 rounded-full bg-stone-300" />
//                     Keep your pet in a well-lit room for video.
//                   </li>
//                   <li className="flex items-start gap-3">
//                     <span className="mt-2 h-2 w-2 rounded-full bg-stone-300" />
//                     Keep previous reports / photos handy (if any).
//                   </li>
//                   <li className="flex items-start gap-3">
//                     <span className="mt-2 h-2 w-2 rounded-full bg-stone-300" />
//                     You&apos;ll receive a join link on WhatsApp after payment.
//                   </li>
//                 </ul>
//               </div>
//             </div>

//             {/* RIGHT: Bill + Pay (desktop) */}
//             <div className="md:col-span-5 lg:col-span-5">
//               {/* Bill Details */}
//               <div className="bg-white p-6 rounded-2xl shadow-sm space-y-4 mb-6 md:sticky md:top-24 md:border md:border-stone-100 md:p-8 md:rounded-3xl">
//                 <div className="flex items-center justify-between">
//                   <h4 className="font-bold text-stone-700 md:text-lg">
//                     Payment Summary
//                   </h4>
//                   <div className="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-1.5 rounded-full md:text-sm">
//                     Instant confirmation
//                   </div>
//                 </div>

//                 <div className="space-y-4">
//                   <div className="flex justify-between text-sm text-stone-600 md:text-base">
//                     <span>Consultation Fee</span>
//                     <span>₹{fee}</span>
//                   </div>

//                   <div className="flex justify-between text-sm text-stone-600 md:text-base">
//                     <span>Service Charge</span>
//                     <span>₹{service}</span>
//                   </div>

//                   <div className="flex justify-between text-sm md:text-base">
//                     <span className="text-stone-600">Digital Prescription</span>
//                     <span className="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-0.5 text-xs font-semibold md:text-sm">
//                       FREE
//                     </span>
//                   </div>

//                   <div className="border-t border-stone-100 pt-4 flex justify-between font-bold text-lg text-stone-800 md:text-2xl">
//                     <span>Total to pay</span>
//                     <span>₹{total}</span>
//                   </div>
//                 </div>

//                 {/* Desktop pay button inside card (mobile uses bottom sticky) */}
//                 <div className="hidden md:block pt-2">
//                   <Button
//                     onClick={handlePay}
//                     disabled={isPaying}
//                     fullWidth
//                     className="
//                       flex justify-between items-center group
//                       md:text-xl md:px-8 md:py-4 md:rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600
//                     "
//                   >
//                     <span>{isPaying ? "Processing..." : `Pay ₹${total}`}</span>
//                     <span className="flex items-center gap-2 text-brand-100 group-hover:text-white transition-colors bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600">
//                       Start <ArrowRight size={20} />
//                     </span>
//                   </Button>

//                   <p className="text-sm text-center text-stone-400 mt-3 flex items-center justify-center gap-2">
//                     <ShieldCheck size={16} /> Secure UPI / Card Payment
//                   </p>
//                   {statusMessage ? (
//                     <p className={`text-sm text-center mt-2 ${statusClassName}`}>
//                       {statusMessage}
//                     </p>
//                   ) : null}
//                 </div>
//               </div>

//               {/* Mobile spacing so sticky bar doesn’t cover content */}
//               <div className="h-24 md:hidden" />
//             </div>
//           </div>
//         </div>
//       </div>

//       {/* Sticky CTA (mobile same, md+ hidden because button is in right card) */}
//       <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20 md:hidden bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600">
//         <Button
//           onClick={handlePay}
//           disabled={isPaying}
//           fullWidth
//           className="flex justify-between items-center group"
//         >
//           <span>{isPaying ? "Processing..." : `Pay ₹${total}`}</span>
//           <span className="flex items-center gap-1 text-brand-100 group-hover:text-white transition-colors">
//             Start <ArrowRight size={18} />
//           </span>
//         </Button>
//         <p className="text-[10px] text-center text-stone-400 mt-2 flex items-center justify-center gap-1">
//           <ShieldCheck size={10} /> Secure UPI / Card Payment
//         </p>
//         {statusMessage ? (
//           <p className={`text-xs text-center mt-2 ${statusClassName}`}>
//             {statusMessage}
//           </p>
//         ) : null}
//       </div>
//       {showSuccessModal ? (
//         <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
//           <div className="bg-white rounded-2xl p-6 w-full max-w-sm text-center shadow-xl">
//             <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
//               <CheckCircle2 size={28} />
//             </div>
//             <div className="text-lg font-bold text-stone-800">
//               Payment confirmed
//             </div>
//             <p className="text-sm text-stone-500 mt-2">
//               Your booking is confirmed. Connecting you to the vet...
//             </p>
//           </div>
//         </div>
//       ) : null}
//     </div>
//   );
// };

// export const ConfirmationScreen = ({ vet }) => {
//   return (
//     <div className="min-h-screen bg-white flex flex-col items-center justify-center p-8 text-center animate-fade-in md:bg-gradient-to-b md:from-white md:to-calm-bg">
//       {/* Desktop: FULL WIDTH content area (but centered text), mobile unchanged */}
//       <div className="w-full md:px-10 lg:px-16">
//         <div className="md:max-w-4xl md:mx-auto">
//           <div className="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-pulse-slow text-green-600 mx-auto md:w-28 md:h-28">
//             <CheckCircle2 size={48} className="md:hidden" />
//             <CheckCircle2 size={56} className="hidden md:block" />
//           </div>

//           <h2 className="text-2xl font-bold text-stone-800 mb-2 md:text-4xl">
//             Thank you! Consultation booked.
//           </h2>

//           <p className="text-stone-500 mb-8 max-w-[250px] mx-auto md:max-w-2xl md:text-lg">
//             <strong className="text-stone-700">
//               {vet?.name || "Your vet"}
//             </strong>{" "}
//             has been notified and will respond in about 10-15 minutes.
//           </p>

//           <div className="bg-stone-50 rounded-2xl p-6 w-full max-w-sm border border-stone-100 mb-8 mx-auto md:max-w-4xl md:p-10 md:rounded-3xl">
//             {/* Desktop: 2 columns, mobile: stacked (same as before) */}
//             <div className="md:grid md:grid-cols-2 md:gap-10">
//               <div className="flex items-center gap-3 mb-4 text-left md:mb-0">
//                 <div className="bg-green-100 p-2 rounded-full text-green-700 md:p-3">
//                   <MessageCircle size={20} className="md:hidden" />
//                   <MessageCircle size={24} className="hidden md:block" />
//                 </div>
//                 <div>
//                   <p className="text-sm font-bold text-stone-700 md:text-lg">
//                     Check WhatsApp
//                   </p>
//                   <p className="text-xs text-stone-500 md:text-base">
//                     We sent you a link to join the video call.
//                   </p>
//                 </div>
//               </div>

//               <div className="flex items-center gap-3 text-left">
//                 <div className="bg-blue-100 p-2 rounded-full text-blue-700 md:p-3">
//                   <Video size={20} className="md:hidden" />
//                   <Video size={24} className="hidden md:block" />
//                 </div>
//                 <div>
//                   <p className="text-sm font-bold text-stone-700 md:text-lg">
//                     Prepare your pet
//                   </p>
//                   <p className="text-xs text-stone-500 md:text-base">
//                     Keep them in a well-lit room.
//                   </p>
//                 </div>
//               </div>
//             </div>

//             {/* Desktop-only mini note */}
//             <div className="hidden md:block mt-8 text-left text-base text-stone-500">
//               Tip: Keep your phone charged and place the pet near natural light
//               for a clearer video.
//             </div>
//           </div>

//           <p className="text-sm text-stone-400 italic md:text-base">
//             "You can relax - help is on the way."
//           </p>
//         </div>
//       </div>
//     </div>
//   );
// };


import React, { useEffect, useMemo, useState } from "react";
import { Button } from "../components/Button";
import { Header, ProgressBar } from "../components/Sharedcomponents";
import { apiPost } from "../lib/api";
import {
  ShieldCheck,
  ArrowRight,
  CheckCircle2,
  MessageCircle,
  Video,
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

export const PaymentScreen = ({ vet, petDetails, paymentMeta, onPay, onBack }) => {
  const fee = Number(vet?.priceDay) || 0;

  // ✅ TESTING: remove service charge for now
  const service = 0;
  const total = fee; // (or fee + service, but service is 0)

  const [isPaying, setIsPaying] = useState(false);
  const [gatewayReady, setGatewayReady] = useState(false);
  const [statusType, setStatusType] = useState("");
  const [statusMessage, setStatusMessage] = useState("");
  const [showSuccessModal, setShowSuccessModal] = useState(false);

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

    return stripEmpty({
      order_type: orderType,
      clinic_id: clinicId,
      service_id: serviceId,
      vet_slug: vetSlug,
      call_session_id: callSessionId,
      pet_id: petId,
      doctor_id: doctorId,
      user_id: userId,
    });
  }, [paymentMeta, petDetails, vet]);

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
    if (!gatewayReady || typeof window === "undefined" || !window.Razorpay) {
      updateStatus("error", "Payment gateway failed to load. Please refresh.");
      return;
    }

    setIsPaying(true);
    updateStatus("info", "Creating order...");

    try {
      // ✅ amount now ONLY actual fee (no +20)
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
          ? `Video consultation with ${vet.name}`
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

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <Header onBack={onBack} title="Secure Payment" />

      <div className="w-full">
        <div className="flex-1 px-4 py-6 overflow-y-auto md:px-10 md:py-12 lg:px-16">
          <ProgressBar current={3} total={3} />

          <div className="mt-6 md:grid md:grid-cols-12 md:gap-10 lg:gap-14">
            <div className="md:col-span-7 lg:col-span-7">
              <div className="bg-white p-4 rounded-2xl shadow-sm border border-brand-100 mb-6 flex gap-4 items-center md:p-7 md:rounded-3xl">
                <img
                  src={vet?.image}
                  alt={vet?.name}
                  className="w-12 h-12 rounded-full object-cover md:w-16 md:h-16"
                />

                <div className="flex-1">
                  <h3 className="font-bold text-stone-800 text-sm md:text-lg lg:text-xl">
                    Consulting Dr. {vet?.name?.split(" ")[1] || vet?.name}
                  </h3>
                  <p className="text-xs text-stone-500 md:text-base">
                    Video Consultation • 15 mins
                  </p>
                </div>

                <div className="hidden md:flex items-center gap-2 text-sm font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 px-4 py-2 rounded-full">
                  <ShieldCheck size={16} />
                  Secure
                </div>
              </div>

              <div className="bg-brand-50 p-4 rounded-2xl border border-brand-100 flex gap-3 mb-8 md:p-7 md:rounded-3xl">
                <ShieldCheck className="text-brand-600 flex-shrink-0 md:mt-0.5" />
                <div className="text-xs text-brand-800 leading-relaxed md:text-base">
                  <strong>Money Safe Guarantee:</strong> If the vet doesn&apos;t
                  respond within 20 minutes, we will immediately reassign another
                  senior vet or refund your money instantly.
                </div>
              </div>

              <div className="hidden md:block bg-white rounded-3xl border border-stone-100 shadow-sm p-8">
                <div className="text-base font-bold text-stone-800 mb-4">
                  Before you pay
                </div>
                <ul className="text-base text-stone-600 space-y-3">
                  <li className="flex items-start gap-3">
                    <span className="mt-2 h-2 w-2 rounded-full bg-stone-300" />
                    Keep your pet in a well-lit room for video.
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="mt-2 h-2 w-2 rounded-full bg-stone-300" />
                    Keep previous reports / photos handy (if any).
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="mt-2 h-2 w-2 rounded-full bg-stone-300" />
                    You&apos;ll receive a join link on WhatsApp after payment.
                  </li>
                </ul>
              </div>
            </div>

            <div className="md:col-span-5 lg:col-span-5">
              <div className="bg-white p-6 rounded-2xl shadow-sm space-y-4 mb-6 md:sticky md:top-24 md:border md:border-stone-100 md:p-8 md:rounded-3xl">
                <div className="flex items-center justify-between">
                  <h4 className="font-bold text-stone-700 md:text-lg">
                    Payment Summary
                  </h4>
                  <div className="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-1.5 rounded-full md:text-sm">
                    Instant confirmation
                  </div>
                </div>

                <div className="space-y-4">
                  <div className="flex justify-between text-sm text-stone-600 md:text-base">
                    <span>Consultation Fee</span>
                    <span>₹{fee}</span>
                  </div>

                  {/* ✅ TESTING: hide service charge row */}
                  {service > 0 ? (
                    <div className="flex justify-between text-sm text-stone-600 md:text-base">
                      <span>Service Charge</span>
                      <span>₹{service}</span>
                    </div>
                  ) : null}

                  <div className="flex justify-between text-sm md:text-base">
                    <span className="text-stone-600">Digital Prescription</span>
                    <span className="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-0.5 text-xs font-semibold md:text-sm">
                      FREE
                    </span>
                  </div>

                  <div className="border-t border-stone-100 pt-4 flex justify-between font-bold text-lg text-stone-800 md:text-2xl">
                    <span>Total to pay</span>
                    <span>₹{total}</span>
                  </div>
                </div>

                <div className="hidden md:block pt-2">
                  <Button
                    onClick={handlePay}
                    disabled={isPaying}
                    fullWidth
                    className="
                      flex justify-between items-center group
                      md:text-xl md:px-8 md:py-4 md:rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600
                    "
                  >
                    <span>{isPaying ? "Processing..." : `Pay ₹${total}`}</span>
                    <span className="flex items-center gap-2 text-brand-100 group-hover:text-white transition-colors">
                      Start <ArrowRight size={20} />
                    </span>
                  </Button>

                  <p className="text-sm text-center text-stone-400 mt-3 flex items-center justify-center gap-2">
                    <ShieldCheck size={16} /> Secure UPI / Card Payment
                  </p>
                  {statusMessage ? (
                    <p className={`text-sm text-center mt-2 ${statusClassName}`}>
                      {statusMessage}
                    </p>
                  ) : null}
                </div>
              </div>

              <div className="h-24 md:hidden" />
            </div>
          </div>
        </div>
      </div>

      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button
          onClick={handlePay}
          disabled={isPaying}
          fullWidth
          className="flex justify-between items-center group"
        >
          <span>{isPaying ? "Processing..." : `Pay ₹${total}`}</span>
          <span className="flex items-center gap-1 text-brand-100 group-hover:text-white transition-colors">
            Start <ArrowRight size={18} />
          </span>
        </Button>
        <p className="text-[10px] text-center text-stone-400 mt-2 flex items-center justify-center gap-1">
          <ShieldCheck size={10} /> Secure UPI / Card Payment
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
            <strong className="text-stone-700">
              {vet?.name || "Your vet"}
            </strong>{" "}
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

          <p className="text-sm text-stone-400 italic md:text-base">
            "You can relax - help is on the way."
          </p>
        </div>
      </div>
    </div>
  );
};
