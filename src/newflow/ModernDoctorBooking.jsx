import React, { useEffect, useState, useMemo, useRef } from "react";
import { X, ChevronRight, Search, Shield, CreditCard, CheckCircle, Users, ChevronDown, ChevronUp } from "lucide-react";
import { readAiAuthState } from "../ai/AiAuth";
import UserDetailsOtpModal from "./UserDetailsOtpModal";
import snoutiq_app_icon from "../assets/snoutiq_app_icon.png";

const API_BASE = "https://snoutiq.com/backend/api";

function loadRazorpayScript() {
  return new Promise((resolve) => {
    if (window.Razorpay) {
      resolve(true);
      return;
    }
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0,
  }).format(amount || 0);
}

function normalizeImage(value) {
  const text = (value || "").trim();
  if (!text) return "";
  if (/^https?:\/\//i.test(text)) return text;
  if (text.startsWith("/")) return `https://snoutiq.com${text}`;
  return `https://snoutiq.com/${text}`;
}

function currentSlot() {
  const hour = new Date().getHours();
  return hour >= 8 && hour < 20 ? "day" : "night";
}

function resolveDoctorFee(doctor, slot = currentSlot()) {
  const preferred = slot === "night"
      ? doctor.feeNight || doctor.feeDay || doctor.fee
      : doctor.feeDay || doctor.feeNight || doctor.fee;
  return Number(preferred || 0) || 0;
}

const resolveAvailability = (doc) => {
  if (doc.is_available === true || String(doc.doctor_status||"").toLowerCase().includes("available")) 
    return { isAvailable: true, label: "Online now" };
  return { isAvailable: false, label: "Video consult available" };
};

const resolveResponseTime = (doc, slot) => {
  return slot === "night" 
    ? (doc.response_time_for_online_consults_night || doc.response_time_for_online_consults_day || "")
    : (doc.response_time_for_online_consults_day || doc.response_time_for_online_consults_night || "");
};

const formatSpecialization = (val) => {
  if (!val) return "General Vet";
  let parsed = val;
  if (typeof val === "string") {
    const trimmed = val.trim();
    if (trimmed.startsWith("[") && trimmed.endsWith("]")) {
      try {
        parsed = JSON.parse(trimmed);
      } catch (e) {
        parsed = trimmed.replace(/[\[\]\\"]/g, "").split(",").map(s => s.trim());
      }
    }
  }
  if (Array.isArray(parsed)) {
    const cleaned = parsed.map(s => String(s).replace(/[\[\]\\"]/g, "").trim()).filter(Boolean);
    return cleaned.length > 0 ? cleaned.join(", ") : "General Vet";
  }
  return String(val).replace(/[\[\]\\"]/g, "").trim() || "General Vet";
};

export default function ModernDoctorBooking({ onClose, symptomText, preSelectedPet, orderType = "video_consult" }) {
  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  
  const [flowStep, setFlowStep] = useState("list"); // "list" | "describe" | "checkout"
  const [issueText, setIssueText] = useState(symptomText || "");
  const [attachedImages, setAttachedImages] = useState([]);
  const [consentGiven, setConsentGiven] = useState(false);
  
  const [gstInvoiceChecked, setGstInvoiceChecked] = useState(false);
  const [gstNumber, setGstNumber] = useState("");
  const [showAllDoctors, setShowAllDoctors] = useState(false);
  
  const [showUserDetailsModal, setShowUserDetailsModal] = useState(false);
  const [pendingDoctor, setPendingDoctor] = useState(null);

  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);
  const [viewProfileDoctor, setViewProfileDoctor] = useState(null);

  const authState = readAiAuthState();
  const token = authState?.token;
  const user = authState?.user || {};
  const pet = preSelectedPet || (user.pets && user.pets[0]) || {};

  useEffect(() => {
    async function fetchDoctors() {
      try {
        const userId = user.id || user.user_id;
        if (!userId) return;

        let doctorsList = [];

        // 1. Fetch Last Visited Vet
        try {
          const lastVetRes = await fetch(`${API_BASE}/users/last-vet-details?user_id=${userId}`, {
            headers: { Authorization: `Bearer ${token}` }
          });
          const lastVetData = await lastVetRes.json();
          if (lastVetData?.success && lastVetData?.data?.doctors) {
            lastVetData.data.doctors.forEach(doc => {
              doctorsList.push({
                ...doc,
                clinicId: lastVetData.data.clinic?.id,
                clinicName: lastVetData.data.clinic?.clinic_name,
                isLastVisited: true
              });
            });
          }
        } catch (err) {
          console.error("Failed to load last vet", err);
        }

        // 2. Fetch Nearby Vets
        try {
          const nearbyRes = await fetch(`${API_BASE}/nearby-vets?user_id=${userId}`, {
            headers: { Authorization: `Bearer ${token}` }
          });
          if (!nearbyRes.ok) throw new Error("nearby-vets failed");
          const nearbyData = await nearbyRes.json();
          const clinics = Array.isArray(nearbyData?.data?.data) ? nearbyData.data.data : (Array.isArray(nearbyData?.data) ? nearbyData.data : []);
          
          clinics.forEach(clinic => {
            if (Array.isArray(clinic.doctors) && clinic.doctors.length > 0) {
              clinic.doctors.forEach(doc => {
                doctorsList.push({
                  ...doc,
                  clinicId: clinic.id || clinic.clinic_id,
                  clinicName: clinic.name || clinic.clinic_name
                });
              });
            } else if (orderType === "appointment") {
              doctorsList.push({
                id: clinic.id,
                doctor_name: clinic.name,
                years_of_experience: 5,
                specialization_select_all_that_apply: "Veterinary Clinic",
                video_day_rate: clinic.clinic_day_fee || "300.00",
                video_night_rate: clinic.clinic_night_fee || "500.00",
                clinicId: clinic.id,
                clinicName: clinic.name,
                doctor_status: "available",
                is_available: true
              });
            }
          });
        } catch (err) {
          console.error("Failed to load nearby vets", err);
        }

        // 3. Always fetch exported_from_excell_doctors to load all available doctors (only for video consultations)
        if (orderType !== "appointment") {
          try {
            const fallbackRes = await fetch(`${API_BASE}/exported_from_excell_doctors`, {
              headers: { Authorization: `Bearer ${token}` }
            });
            const fallbackData = await fallbackRes.json();
            
            const clinics = Array.isArray(fallbackData?.data?.data) ? fallbackData.data.data : [];
            clinics.forEach(clinic => {
              if (Array.isArray(clinic.doctors)) {
                clinic.doctors.forEach(doc => {
                  doctorsList.push({
                    ...doc,
                    clinicId: doc.clinic_id || clinic?.clinic_id || clinic?.id,
                    clinicName: doc.clinic_name || clinic?.clinic_name || clinic?.name
                  });
                });
              }
            });

            const directDoctors = Array.isArray(fallbackData?.doctors) 
              ? fallbackData.doctors 
              : (Array.isArray(fallbackData?.data?.doctors) 
                ? fallbackData.data.doctors 
                : (Array.isArray(fallbackData?.data) 
                  ? fallbackData.data 
                  : (Array.isArray(fallbackData) ? fallbackData : [])));
            
            directDoctors.forEach(doc => {
               doctorsList.push({
                  ...doc,
                  clinicId: doc.clinic_id || doc.vet_registeration_id || fallbackData?.clinic?.id,
                  clinicName: doc.clinic_name || fallbackData?.clinic?.clinic_name || "Clinic"
               });
            });
          } catch(e) {
            console.error("Failed to load fallback doctors", e);
          }
        }

        const processed = doctorsList.map(doc => {
          return {
            id: doc.doctor_id || doc.id || doc.userId,
            name: doc.doctor_name || doc.name || doc.full_name || "Doctor",
            image: normalizeImage(doc.doctor_image_url || doc.doctor_image_blob_url || doc.image || doc.doctor_image),
            specialization: formatSpecialization(doc.specialization_select_all_that_apply || doc.specialization),
            experience: doc.years_of_experience || doc.experience || 0,
            feeDay: Number(doc.video_day_rate || doc.day_fee || doc.consultation_fee_day || doc.fee || doc.doctors_price || 599),
            feeNight: Number(doc.video_night_rate || doc.night_fee || doc.consultation_fee_night || doc.fee || doc.doctors_price || 799),
            available: resolveAvailability(doc).isAvailable,
            clinicId: doc.clinicId,
            clinicName: doc.clinicName,
            isLastVisited: doc.isLastVisited || false,
            availability: resolveAvailability(doc),
            responseTime: resolveResponseTime(doc, currentSlot())
          };
        });

        // Deduplicate
        const unique = [];
        const seen = new Set();
        processed.forEach(doc => {
          if (!seen.has(doc.id)) {
            seen.add(doc.id);
            unique.push(doc);
          }
        });

        // Sort: Last Visited first
        unique.sort((a, b) => (b.isLastVisited ? 1 : 0) - (a.isLastVisited ? 1 : 0));
        
        setDoctors(unique);
      } catch (err) {
        console.error("Failed to process doctors", err);
      } finally {
        setLoading(false);
      }
    }
    if (token) fetchDoctors();
  }, [token]);

  const filteredDoctors = doctors.filter(doc => 
    doc.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
    doc.specialization.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const slot = currentSlot();
  const fee = selectedDoctor ? resolveDoctorFee(selectedDoctor, slot) : 0;
  const GST_RATE = 0.18;
  const gstAmount = Math.round(fee * GST_RATE);
  const totalAmount = fee + gstAmount;

  const handlePayment = async () => {
    const petId = pet.id || pet.pet_id;
    const userId = user.id || user.user_id;
    
    if (!petId || !userId || !selectedDoctor?.id) {
      setError("Missing booking details. Please refresh and try again.");
      return;
    }
    
    setProcessing(true);
    setError("");
    
    try {
      const orderPayload = {
        amount: totalAmount,
        order_type: orderType || "video_consult",
        user_id: userId,
        doctor_id: selectedDoctor.id,
        clinic_id: selectedDoctor.clinicId,
        pet_id: petId,
        gst_enabled: gstInvoiceChecked ? 1 : 0,
        gst_percent: 18,
        gst_amount: gstAmount,
        base_amount: fee,
        gst_number: gstInvoiceChecked ? gstNumber : "",
      };

      const orderRes = await fetch(`${API_BASE}/create-order`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify(orderPayload)
      });
      const orderData = await orderRes.json();
      
      if (!orderRes.ok) throw new Error(orderData.message || "Failed to create order");
      
      const razorpayKey = orderData?.key || orderData?.data?.key;
      const orderId = orderData?.order?.id || orderData?.order_id || orderData?.data?.order_id;
      
      const isLoaded = await loadRazorpayScript();
      if (!isLoaded) throw new Error("Could not load payment gateway.");

      const paymentResult = await new Promise((resolve, reject) => {
        const rzp = new window.Razorpay({
          key: razorpayKey,
          amount: totalAmount * 100,
          currency: "INR",
          name: "SnoutIQ",
          description: `${orderType === "appointment" ? "Clinic Visit" : "Video Consult"} with ${selectedDoctor.name}`,
          order_id: orderId,
          prefill: {
            name: user.name || user.owner_name,
            contact: user.mobile || user.phone,
          },
          theme: { color: "#000000" },
          handler: (response) => resolve(response),
        });
        rzp.on('payment.failed', () => reject(new Error("Payment failed or cancelled.")));
        rzp.open();
      });

      const verifyPayload = {
        razorpay_order_id: paymentResult.razorpay_order_id,
        razorpay_payment_id: paymentResult.razorpay_payment_id,
        razorpay_signature: paymentResult.razorpay_signature,
        user_id: userId,
        doctor_id: selectedDoctor.id,
        pet_id: petId,
        description: issueText || symptomText || (orderType === "appointment" ? "Clinic Visit Booking" : "Video Consult Booking"),
        order_type: orderType || "video_consult",
      };

      const verifyRes = await fetch(`${API_BASE}/rzp/verify`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify(verifyPayload)
      });
      const verifyData = await verifyRes.json();
      
      if (!verifyData.success) throw new Error("Payment verification failed");

      try {
        const firstImage = attachedImages[0];
        if (firstImage?.file) {
          const formData = new FormData();
          formData.append("_method", "PUT");
          formData.append("pet_id", String(petId));
          formData.append("user_id", String(userId));
          formData.append("question", issueText || symptomText || "");
          formData.append("video_calling_upload_file", firstImage.file);

          await fetch(`${API_BASE}/chat/dog-disease/question`, {
            method: "POST",
            headers: { Authorization: `Bearer ${token}` },
            body: formData,
          });
        }
      } catch (uploadErr) {
        console.warn("Symptom image upload failed (non-blocking):", uploadErr);
      }

      setSuccess(true);
    } catch (err) {
      console.error(err);
      setError(err.message || "An error occurred during payment.");
    } finally {
      setProcessing(false);
    }
  };

  if (success) {
    return (
      <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div className="bg-white rounded-2xl w-full max-w-sm p-4 text-center shadow-2xl">
          <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <CheckCircle className="text-green-600 w-10 h-10" />
          </div>
          <h2 className="text-lg font-bold text-slate-900 mb-1">Booking Confirmed!</h2>
          <p className="text-slate-600 mb-8">
            Your video consultation with <span className="font-semibold text-slate-900">{selectedDoctor?.name}</span> has been successfully booked.
          </p>
          <button onClick={onClose} className="w-full py-2 bg-black text-white rounded-xl font-semibold hover:bg-slate-800 transition-colors">
            Return to Dashboard
          </button>
        </div>
      </div>
    );
  }

  const hasCompleteProfile = () => {
    const currentAuth = readAiAuthState(); // fresh read
    const u = currentAuth?.user || {};
    const nameOk = String(u?.name || u?.owner_name || "").trim().length >= 2;
    const cleanPhone = String(u?.whatsapp_number || u?.phone || u?.mobile || "").replace(/[^\d]/g, "");
    const phoneOk = cleanPhone.length === 10 || (cleanPhone.length === 12 && cleanPhone.startsWith("91"));
    return nameOk && phoneOk;
  };

  const handleBookNowClick = (doc) => {
    if (!hasCompleteProfile()) {
      setPendingDoctor(doc);
      setShowUserDetailsModal(true);
      return;
    }
    setSelectedDoctor(doc);
    setFlowStep("describe");
  };

  const trustedDoctors = filteredDoctors.filter(d => d.isLastVisited);
  const otherDoctors = filteredDoctors.filter(d => !d.isLastVisited);

  const isPhotoRequired = orderType !== "appointment";
  const canContinueToPayment = issueText.trim() && (!isPhotoRequired || attachedImages.length > 0) && consentGiven;

  const renderDoctorCard = (doc, featured = false) => {
    const isOnline = doc.availability?.isAvailable;
    return (
      <div key={doc.id} className="bg-white border border-slate-100 rounded-xl p-2.5 pl-11 pr-2.5 hover:shadow-sm transition-all flex flex-col relative ml-4 mt-1" style={{minHeight: '82px'}}>
        {/* Left overlapping avatar - ultra compact */}
        <div className="absolute left-[-16px] top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-xl bg-white p-0.5 shadow-[0_2px_6px_rgba(0,0,0,0.1)] border border-slate-100 flex items-center justify-center">
          {doc.image ? (
            <img src={doc.image} alt={doc.name} className="w-full h-full rounded-lg object-cover" />
          ) : (
            <div className="w-full h-full rounded-lg bg-slate-800 text-white flex items-center justify-center text-sm font-bold">
              {doc.name.charAt(0)}
            </div>
          )}
          {isOnline && (
            <span className="absolute bottom-0 right-0 w-2 h-2 bg-emerald-500 border border-white rounded-full" />
          )}
        </div>

        {/* Right Details */}
        <div className="flex-1 flex flex-col justify-between">
          <div>
            {featured && (
              <span className="inline-block text-[8px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0 rounded mb-0.5 leading-4">
                Recommended for {pet.name || pet.pet_name}
              </span>
            )}
            <h3 className="font-bold text-slate-800 text-[13px] leading-tight">{doc.name}</h3>
            <p className="text-[10px] text-slate-400 leading-tight">{doc.specialization}</p>
            <p className="text-[10px] font-semibold text-emerald-600">
              {isOnline ? "Online now · Video consult available" : "Video consult available"}
            </p>
          </div>

          <div className="flex items-center justify-between border-t border-slate-50 pt-1 mt-1">
            <span className="text-[11px] font-extrabold text-slate-800">
              {formatCurrency(resolveDoctorFee(doc, slot))}/Consult
            </span>
            <div className="flex gap-1">
              <button 
                onClick={() => setViewProfileDoctor(doc)}
                className="px-2.5 py-0.5 border border-slate-200 text-blue-600 text-[10px] font-semibold rounded-full hover:bg-slate-50 transition-all"
              >
                View Profile
              </button>
              <button 
                onClick={() => handleBookNowClick(doc)}
                className="px-2.5 py-0.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold rounded-full transition-all"
              >
                {orderType === "appointment" ? "Book" : "Talk to Vet"}
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  };


  return (
    <div className="fixed inset-0 z-[100] flex flex-col bg-slate-50 md:p-6 lg:p-12 animate-[fadeIn_0.2s_ease-out]">
      <style>{`
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        @keyframes scaleInUp {
          from { transform: translateY(12px) scale(0.98); opacity: 0; }
          to { transform: translateY(0) scale(1); opacity: 1; }
        }
      `}</style>
      <div className="bg-white md:rounded-3xl shadow-xl flex-1 flex flex-col max-w-2xl mx-auto w-full overflow-hidden border border-slate-200 animate-[scaleInUp_0.25s_ease-out]">
        
        {/* Header */}
        <div className="flex items-center justify-between px-3 py-2 border-b border-slate-100">
  <div className="flex items-center gap-2">
    {flowStep !== "list" && (
      <button
        onClick={() =>
          setFlowStep(flowStep === "checkout" ? "describe" : "list")
        }
        className="text-slate-500 hover:text-black transition-colors"
      >
        <ChevronRight className="w-4 h-4 rotate-180" />
      </button>
    )}

    <img
      src={snoutiq_app_icon}
      alt="Snoutiq"
      className="h-5 w-5 rounded-lg"
    />

    {/* Title + Subtitle */}
    <div className="flex flex-col">
      <h2 className="text-sm font-bold text-slate-900 leading-tight">
        {flowStep === "checkout"
          ? "Secure Checkout"
          : flowStep === "describe"
          ? "Describe the issue"
          : "Select a Veterinarian"}
      </h2>

      <p className="text-[10px] text-slate-400 leading-tight">
        {flowStep === "checkout"
          ? "Complete your payment"
          : flowStep === "describe"
          ? "Tell us what is wrong"
          : "Choose a specialist for your pet"}
      </p>
    </div>
  </div>

  <button
    onClick={onClose}
    className="p-1 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors text-slate-600"
  >
    <X className="w-3.5 h-3.5" />
  </button>
</div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto bg-slate-50/50">
          {flowStep === "list" && (
            <div className="p-4">
              <div className="relative mb-4 max-w-sm">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input 
                  type="text" 
                  placeholder="Search doctors or specializations..." 
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded-xl py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-400 transition-colors shadow-sm"
                />
              </div>

              {loading ? (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {[1, 2, 3, 4, 5, 6].map(i => (
                    <div key={i} className="bg-white border border-slate-100 rounded-3xl p-5 animate-pulse">
                      <div className="flex gap-4 mb-4">
                        <div className="w-16 h-16 bg-slate-100 rounded-2xl"></div>
                        <div className="flex-1 space-y-2 py-2">
                          <div className="h-4 bg-slate-100 rounded w-3/4"></div>
                          <div className="h-3 bg-slate-100 rounded w-1/2"></div>
                        </div>
                      </div>
                      <div className="h-12 bg-slate-100 rounded-xl mt-4"></div>
                    </div>
                  ))}
                </div>
              ) : (
                <div>
                  {trustedDoctors.length > 0 && (
                    <div className="mb-2">
                      <h3 className="text-[10px] font-bold text-emerald-700 uppercase tracking-wide mb-1.5 flex items-center gap-1">
                        <CheckCircle size={11} /> Your Trusted Vet
                      </h3>
                      <div className="grid grid-cols-1 gap-1.5">
                        {trustedDoctors.map(doc => renderDoctorCard(doc, true))}
                      </div>
                    </div>
                  )}

                  {otherDoctors.length > 0 && (
                    <div className="mt-1">
                      {trustedDoctors.length > 0 ? (
                        <>
                          <div className="pt-2 pb-1">
                            <button
                              onClick={() => setShowAllDoctors(!showAllDoctors)}
                              className="w-full flex items-center justify-between py-2.5 px-3 bg-sky-50 border border-sky-100 rounded-xl text-[11px] font-semibold text-sky-600 transition-all"
                            >
                              <div className="flex items-center gap-1.5">
                                <Users className="w-3.5 h-3.5 text-sky-500" />
                                <span>{showAllDoctors ? "View less vets" : `View more vets for ${pet.name || pet.pet_name || "your pet"}`}</span>
                              </div>
                              {showAllDoctors ? (
                                <ChevronUp className="w-3.5 h-3.5 text-sky-500" />
                              ) : (
                                <ChevronDown className="w-3.5 h-3.5 text-sky-500" />
                              )}
                            </button>
                          </div>

                          {showAllDoctors && (
                            <div className="mt-2">
                              <h3 className="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5 border-t border-slate-100 pt-2 ml-4">Other Available Vets</h3>
                              <div className="grid grid-cols-1 gap-1.5">
                                {otherDoctors.map(doc => renderDoctorCard(doc, false))}
                              </div>
                            </div>
                          )}
                        </>
                      ) : (
                        <div className="grid grid-cols-1 gap-1.5">
                          {otherDoctors.map(doc => renderDoctorCard(doc, false))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>
          )}

          {flowStep === "describe" && (
            <div className="p-2 max-w-xl mx-auto">
              <h3 className="text-sm font-bold text-slate-900 mb-2">Describe the issue</h3>
              
              <textarea
                value={issueText}
                onChange={(e) => setIssueText(e.target.value)}
                maxLength={500}
                placeholder="Example: Vomiting since morning, not eating, low energy..."
                className="w-full h-20 border border-slate-200 rounded-2xl p-2 text-xs focus:border-black outline-none resize-none shadow-sm"
              />
              <p className="text-xs text-slate-400 text-right mt-1">{issueText.length}/500</p>

              <div className="mt-2">
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  {orderType === "appointment" ? "Add a photo (optional)" : "Add a photo (required)"}
                </label>
                <input 
                  type="file" 
                  accept="image/*" 
                  onChange={(e) => {
                    const file = e.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onloadend = () => setAttachedImages([{ uri: reader.result, name: file.name, file }]);
                    reader.readAsDataURL(file);
                  }}
                  className="text-xs border border-slate-200 p-1.5 rounded-lg w-full"
                />
                {attachedImages.length > 0 && (
                  <img src={attachedImages[0].uri} className="mt-3 w-24 h-24 rounded-xl object-cover border border-slate-200" alt="attached" />
                )}
              </div>

              <label className="mt-3 flex items-start gap-2 cursor-pointer bg-slate-50 border border-slate-200 rounded-xl p-2">
                <input 
                  type="checkbox" 
                  checked={consentGiven} 
                  onChange={(e) => setConsentGiven(e.target.checked)}
                  className=" w-4 h-4 rounded border-slate-300 text-black focus:ring-black"
                />
                <span className="text-xs text-slate-600">
                  I understand online consultation is for guidance. Emergency cases may need a clinic visit.
                </span>
              </label>

              <button
                disabled={!canContinueToPayment}
                onClick={() => setFlowStep("checkout")}
                className="mt-3 w-full py-2 bg-black text-white rounded-lg font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 transition-colors shadow-md"
              >
                Continue to payment
              </button>
            </div>
          )}

          {flowStep === "checkout" && (
            <div className="p-3 max-w-3xl mx-auto w-full flex flex-col md:flex-row gap-2">
              {/* Left Column: Summary */}
              <div className="flex-1">
                <h3 className="text-sm font-bold text-slate-900 mb-2">Patient Details</h3>
                <div className="bg-white border border-slate-200 rounded-2xl p-2.5 mb-6 shadow-sm">
                  <div className="flex justify-between py-2 border-b border-slate-100">
                    <span className="text-slate-500">Pet</span>
                    <span className="font-semibold text-slate-900">{pet.name || pet.pet_name}</span>
                  </div>
                  <div className="flex justify-between py-2 border-b border-slate-100">
                    <span className="text-slate-500">Owner</span>
                    <span className="font-semibold text-slate-900">{user.name || user.owner_name}</span>
                  </div>
                  <div className="flex justify-between py-2 border-b border-slate-100">
                    <span className="text-slate-500">Symptom</span>
                    <span className="font-semibold text-slate-900 max-w-[200px] text-right truncate" title={issueText || symptomText}>{issueText || symptomText}</span>
                  </div>
                </div>

                <h3 className="text-sm font-bold text-slate-900 mb-2">Doctor Details</h3>
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center gap-4">
                  {selectedDoctor?.image ? (
                    <img src={selectedDoctor.image} alt={selectedDoctor.name} className="w-12 h-12 rounded-xl object-cover" />
                  ) : (
                    <div className="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold">
                      {selectedDoctor?.name?.charAt(0)}
                    </div>
                  )}
                  <div>
                    <h4 className="font-bold text-slate-900">{selectedDoctor?.name}</h4>
                    <p className="text-sm text-slate-500">{selectedDoctor?.specialization}</p>
                    <p className="mt-1 text-xs font-semibold text-emerald-600">
                      {selectedDoctor?.availability?.isAvailable ? "Online now" : "Video consult available"}
                      {selectedDoctor?.responseTime ? ` · Connects in ${selectedDoctor?.responseTime}` : ""}
                    </p>
                  </div>
                </div>
              </div>

              {/* Right Column: Payment */}
              <div className="w-full md:w-80">
                <div className="bg-white border border-slate-200 rounded-xl p-2.5 mb-3 shadow-sm">
                  <label className="flex items-center gap-3 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={gstInvoiceChecked}
                      onChange={(e) => {
                        setGstInvoiceChecked(e.target.checked);
                        if (!e.target.checked) setGstNumber("");
                      }}
                      className="w-4 h-4 rounded border-slate-300 text-black focus:ring-black"
                    />
                    <div>
                      <p className="text-sm font-semibold text-slate-900">Need GST invoice</p>
                      <p className="text-xs text-slate-500">Add GST details for business billing</p>
                    </div>
                  </label>
                  {gstInvoiceChecked && (
                    <input
                      type="text"
                      maxLength={15}
                      value={gstNumber}
                      onChange={(e) => setGstNumber(e.target.value.toUpperCase().replace(/\s+/g, ""))}
                      placeholder="Enter 15-digit GST number"
                      className="mt-4 w-full border border-slate-200 rounded-xl px-3 py-2 text-sm uppercase tracking-wide focus:border-black outline-none"
                    />
                  )}
                </div>

                <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xl sticky top-6">
                  <h3 className="text-lg font-bold text-slate-900 mb-6">Payment Summary</h3>
                  
                  <div className="space-y-3 text-sm mb-6">
                    <div className="flex justify-between">
                      <span className="text-slate-500">Consultation Fee</span>
                      <span className="font-semibold text-slate-900">{formatCurrency(fee)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-500">Platform Fee</span>
                      <span className="font-semibold text-slate-900">₹0</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-500">GST (18%)</span>
                      <span className="font-semibold text-slate-900">{formatCurrency(gstAmount)}</span>
                    </div>
                  </div>
                  
                  <div className="pt-4 border-t border-slate-200 mb-8">
                    <div className="flex justify-between items-end">
                      <span className="text-slate-900 font-bold">Total Amount</span>
                      <span className="text-lg font-bold text-slate-900">{formatCurrency(totalAmount)}</span>
                    </div>
                  </div>

                  {error && (
                    <div className="bg-red-50 text-red-600 text-sm p-3 rounded-xl mb-6 border border-red-100">
                      {error}
                    </div>
                  )}

                  <button 
                    onClick={handlePayment} 
                    disabled={processing || (gstInvoiceChecked && gstNumber.length !== 15)}
                    className="w-full flex items-center justify-center gap-2 py-2 bg-black text-white rounded-xl font-bold hover:bg-slate-800 transition-colors disabled:opacity-70 disabled:cursor-not-allowed shadow-md"
                  >
                    {processing ? (
                      <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    ) : (
                      <>
                        <Shield className="w-4 h-4" />
                        Pay {formatCurrency(totalAmount)}
                      </>
                    )}
                  </button>
                  <p className="text-center text-xs text-slate-400 mt-4 flex items-center justify-center gap-1">
                    <CreditCard className="w-3 h-3" /> Secured by Razorpay
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
      
      {showUserDetailsModal && (
        <UserDetailsOtpModal
          onClose={() => {
            setShowUserDetailsModal(false);
            setPendingDoctor(null);
          }}
          onComplete={() => {
            setShowUserDetailsModal(false);
            if (pendingDoctor) {
              setSelectedDoctor(pendingDoctor);
              setFlowStep("describe");
              setPendingDoctor(null);
            }
          }}
        />
      )}
      {viewProfileDoctor && (
        <div className="fixed inset-0 z-[200] flex items-end sm:items-center justify-center bg-black/50 animate-[fadeIn_0.15s_ease-out]" onClick={() => setViewProfileDoctor(null)}>
          <div
            className="bg-white w-full sm:max-w-[340px] rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl animate-[scaleInUp_0.2s_ease-out]"
            onClick={e => e.stopPropagation()}
          >
            {/* Modal Header */}
           <div className="flex items-center justify-between px-3 py-2 border-b border-slate-100">
  <div className="flex items-center gap-2">
    <img
      src={snoutiq_app_icon}
      alt="SnoutIQ"
      className="w-5 h-5 rounded-lg"
    />
    <span className="text-xs font-bold text-slate-800">
      Doctor Profile
    </span>
  </div>

  <button
    onClick={() => setViewProfileDoctor(null)}
    className="p-1 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors"
  >
    <X className="w-3.5 h-3.5 text-slate-500" />
  </button>
</div>

            {/* Doctor Info */}
            <div className="p-5">
              <div className="flex gap-4 mb-4">
                {viewProfileDoctor.image ? (
                  <img src={viewProfileDoctor.image} alt={viewProfileDoctor.name} className="w-20 h-20 rounded-2xl object-cover border border-slate-100 shadow-sm flex-shrink-0" />
                ) : (
                  <div className="w-20 h-20 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-lg font-bold flex-shrink-0">
                    {viewProfileDoctor.name?.charAt(0)}
                  </div>
                )}
                <div>
                  <h3 className="font-bold text-slate-900 text-sm leading-tight">{viewProfileDoctor.name}</h3>
                  <p className="text-xs text-slate-500 mt-1">{viewProfileDoctor.specialization}</p>
                  {viewProfileDoctor.experience && (
                    <span className="inline-block mt-1.5 text-xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md">
                      {viewProfileDoctor.experience}y experience
                    </span>
                  )}
                  <p className="text-xs font-semibold text-emerald-600 mt-1.5">
                    {viewProfileDoctor.availability?.isAvailable ? "🟢 Online now" : "Video consult available"}
                  </p>
                </div>
              </div>

              {/* Divider row */}
              <div className="grid grid-cols-2 gap-3 mb-4">
                <div className="bg-slate-50 rounded-xl p-3 text-center">
                  <p className="text-[10px] text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Consultation Fee</p>
                  <p className="text-sm font-extrabold text-slate-800">{formatCurrency(resolveDoctorFee(viewProfileDoctor, slot))}</p>
                </div>
                <div className="bg-slate-50 rounded-xl p-3 text-center">
                  <p className="text-[10px] text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Experience</p>
                  <p className="text-sm font-extrabold text-slate-800">{viewProfileDoctor.experience || "—"}y</p>
                </div>
              </div>

              {viewProfileDoctor.clinicName && (
                <div className="flex items-center gap-2 bg-blue-50 px-3 py-2 rounded-xl mb-4">
                  <span className="text-blue-500 text-sm">🏥</span>
                  <p className="text-xs text-blue-700 font-medium">{viewProfileDoctor.clinicName}</p>
                </div>
              )}

              {/* CTA */}
              <button
                onClick={() => {
                  setViewProfileDoctor(null);
                  handleBookNowClick(viewProfileDoctor);
                }}
                className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-2xl transition-all shadow-md"
              >
                {orderType === "appointment" ? "Book Appointment" : "Talk to Vet"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
