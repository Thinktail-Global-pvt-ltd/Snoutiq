import React, { useEffect, useState, useMemo, useRef } from "react";
import { X, ChevronRight, Search, Shield, CreditCard, CheckCircle, Users, ChevronDown, ChevronUp, Calendar, Clock, Loader2 } from "lucide-react";
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

function getUpcomingDates(count = 7) {
  const dates = [];
  const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  
  for (let i = 0; i < count; i++) {
    const d = new Date();
    d.setDate(d.getDate() + i);
    
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    const dateStr = `${year}-${month}-${day}`;
    
    let label = `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]}`;
    if (i === 0) label = `Today (${d.getDate()} ${months[d.getMonth()]})`;
    if (i === 1) label = `Tomorrow (${d.getDate()} ${months[d.getMonth()]})`;
    
    dates.push({ dateStr, label, dayName: days[d.getDay()], dateNum: d.getDate(), monthName: months[d.getMonth()] });
  }
  return dates;
}

export default function ModernDoctorBooking({ onClose, symptomText, preSelectedPet, orderType = "video_consult" }) {
  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  
  const [flowStep, setFlowStep] = useState("list"); // "list" | "describe" | "checkout"
  const [issueText, setIssueText] = useState(symptomText || "");
  const [attachedImages, setAttachedImages] = useState([]);
  const [consentGiven, setConsentGiven] = useState(false);
  
  // Appointment Flow Specific States
  const [selectedDate, setSelectedDate] = useState("");
  const [selectedTimeSlot, setSelectedTimeSlot] = useState("");
  const [resolvedDoctorId, setResolvedDoctorId] = useState(null);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [loadingDateAvail, setLoadingDateAvail] = useState(false);
  const [dateAvailError, setDateAvailError] = useState("");
  const [lockId, setLockId] = useState(null);

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

  // Handle slot unlock helper
  const unlockCurrentSlot = async (lockIdToUnlock) => {
    const targetLockId = lockIdToUnlock || lockId;
    if (!targetLockId) return;

    try {
      await fetch(`${API_BASE}/doctors/slots/unlock`, {
        method: "POST",
        headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        body: JSON.stringify({ lock_id: targetLockId })
      });
    } catch (err) {
      console.warn("Slot unlock error (non-blocking):", err);
    } finally {
      setLockId(null);
    }
  };

  const handleModalClose = () => {
    if (lockId) {
      unlockCurrentSlot(lockId);
    }
    onClose?.();
  };

  useEffect(() => {
    async function fetchDoctors() {
      try {
        const userId = user.id || user.user_id || authState?.user_id || authState?.userId;
        let doctorsList = [];
        let lastVetClinicId = null;

        // 1. Fetch Last Visited Vet / Clinic
        if (userId) {
          try {
            const lastVetRes = await fetch(`${API_BASE}/users/last-vet-details?user_id=${userId}`, {
              headers: token ? { Authorization: `Bearer ${token}` } : {}
            });
            const lastVetData = await lastVetRes.json();
            
            const rawClinic = lastVetData?.data?.clinic || lastVetData?.clinic;
            const rawDoctors = lastVetData?.data?.doctors || lastVetData?.doctors || [];

            if (lastVetData?.success || rawClinic || (Array.isArray(rawDoctors) && rawDoctors.length > 0)) {
              lastVetClinicId = rawClinic?.id || rawClinic?.clinic_id || lastVetData?.data?.clinic_id;
              
              if (Array.isArray(rawDoctors) && rawDoctors.length > 0) {
                rawDoctors.forEach(doc => {
                  doctorsList.push({
                    ...doc,
                    clinicId: rawClinic?.id || doc.clinic_id,
                    clinicName: rawClinic?.clinic_name || rawClinic?.name || doc.clinic_name,
                    isLastVisited: true
                  });
                });
              } else if (orderType === "appointment" && rawClinic && (rawClinic.id || rawClinic.name || rawClinic.clinic_name)) {
                const c = rawClinic;
                doctorsList.push({
                  id: `clinic-vet-${c.id || c.clinic_id}`,
                  doctor_id: `clinic-vet-${c.id || c.clinic_id}`,
                  name: c.name || c.clinic_name || "Clinic Vet",
                  doctor_name: c.name || c.clinic_name || "Clinic Vet",
                  specialization: c.specialization || "In-Clinic Veterinary Practice",
                  years_of_experience: 5,
                  experience: 5,
                  video_day_rate: c.clinic_day_fee || "300.00",
                  video_night_rate: c.clinic_night_fee || "500.00",
                  clinicId: c.id || c.clinic_id,
                  clinicName: c.name || c.clinic_name,
                  clinic_day_fee: c.clinic_day_fee,
                  clinic_night_fee: c.clinic_night_fee,
                  is_available: true,
                  isFallbackClinic: true,
                  isLastVisited: true
                });
              }
            }
          } catch (err) {
            console.error("Failed to load last vet", err);
          }
        }

        // 2. Fetch Doctors / Clinics
        if (orderType === "appointment") {
          // In-Clinic Flow: GET /inclinic-lists-new-after-10th-may-registerations?user_id={userId}
          try {
            let inclinicRes;
            try {
              inclinicRes = await fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?user_id=${userId}`, {
                headers: token ? { Authorization: `Bearer ${token}` } : {}
              });
              if (!inclinicRes.ok) throw new Error("Inclinic auth fetch failed");
            } catch (e) {
              // Retry without Bearer token header if failed
              inclinicRes = await fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?user_id=${userId}`);
            }

            const inclinicData = await inclinicRes.json();
            const clinics = Array.isArray(inclinicData?.data?.data) 
              ? inclinicData.data.data 
              : (Array.isArray(inclinicData?.data) 
                ? inclinicData.data 
                : (Array.isArray(inclinicData?.clinics) ? inclinicData.clinics : []));

            clinics.forEach(clinic => {
              const cId = clinic.id || clinic.clinic_id;
              if (Array.isArray(clinic.doctors) && clinic.doctors.length > 0) {
                clinic.doctors.forEach(doc => {
                  doctorsList.push({
                    ...doc,
                    clinicId: cId,
                    clinicName: clinic.name || clinic.clinic_name,
                    clinic_day_fee: clinic.clinic_day_fee,
                    clinic_night_fee: clinic.clinic_night_fee
                  });
                });
              } else {
                // Clinic doctors array is empty - create fallback doctor entry using "clinic-vet-{clinicId}" pattern
                doctorsList.push({
                  id: `clinic-vet-${cId}`,
                  doctor_id: `clinic-vet-${cId}`,
                  name: clinic.name || clinic.clinic_name || "Clinic Vet",
                  doctor_name: clinic.name || clinic.clinic_name || "Clinic Vet",
                  specialization_select_all_that_apply: clinic.specialization || "In-Clinic Veterinary Practice",
                  specialization: clinic.specialization || "In-Clinic Veterinary Practice",
                  years_of_experience: 5,
                  experience: 5,
                  video_day_rate: clinic.clinic_day_fee || "300.00",
                  video_night_rate: clinic.clinic_night_fee || "500.00",
                  clinicId: cId,
                  clinicName: clinic.name || clinic.clinic_name,
                  clinic_day_fee: clinic.clinic_day_fee,
                  clinic_night_fee: clinic.clinic_night_fee,
                  doctor_status: "available",
                  is_available: true,
                  isFallbackClinic: true
                });
              }
            });
          } catch (err) {
            console.error("Failed to load inclinic list", err);
          }
        } else {
          // Video Consult Flow (Unchanged)
          try {
            const nearbyRes = await fetch(`${API_BASE}/nearby-vets?user_id=${userId}`, {
              headers: token ? { Authorization: `Bearer ${token}` } : {}
            });
            if (nearbyRes.ok) {
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
                }
              });
            }
          } catch (err) {
            console.error("Failed to load nearby vets", err);
          }

          try {
            const fallbackRes = await fetch(`${API_BASE}/exported_from_excell_doctors`, {
              headers: token ? { Authorization: `Bearer ${token}` } : {}
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

        // Process and Merge
        const processed = doctorsList.map(doc => {
          const docClinicId = doc.clinicId || doc.clinic_id;
          const isMatch = lastVetClinicId && String(docClinicId) === String(lastVetClinicId);
          return {
            id: doc.doctor_id || doc.id || doc.userId,
            name: doc.doctor_name || doc.name || doc.full_name || "Doctor",
            image: normalizeImage(doc.doctor_image_url || doc.doctor_image_blob_url || doc.image || doc.doctor_image),
            specialization: formatSpecialization(doc.specialization_select_all_that_apply || doc.specialization),
            experience: doc.years_of_experience || doc.experience || 0,
            feeDay: Number(doc.clinic_day_fee || doc.video_day_rate || doc.day_fee || doc.consultation_fee_day || doc.fee || doc.doctors_price || 300),
            feeNight: Number(doc.clinic_night_fee || doc.video_night_rate || doc.night_fee || doc.consultation_fee_night || doc.fee || doc.doctors_price || 500),
            available: resolveAvailability(doc).isAvailable,
            clinicId: docClinicId,
            clinicName: doc.clinicName || doc.clinic_name,
            isLastVisited: doc.isLastVisited || Boolean(isMatch),
            isFallbackClinic: doc.isFallbackClinic || false,
            availability: resolveAvailability(doc),
            responseTime: resolveResponseTime(doc, currentSlot())
          };
        });

        // Deduplicate
        const unique = [];
        const seen = new Set();
        processed.forEach(doc => {
          const uniqueKey = `${doc.clinicId}_${doc.id}`;
          if (!seen.has(uniqueKey)) {
            seen.add(uniqueKey);
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
  }, [token, orderType]);

  const filteredDoctors = doctors.filter(doc => 
    doc.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
    doc.specialization.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const slot = currentSlot();
  const fee = selectedDoctor ? resolveDoctorFee(selectedDoctor, slot) : 0;
  const GST_RATE = 0.18;
  const gstAmount = Math.round(fee * GST_RATE);
  const totalAmount = fee + gstAmount;

  const trustedDoctors = useMemo(() => filteredDoctors.filter(d => d.isLastVisited), [filteredDoctors]);
  const otherDoctors = useMemo(() => filteredDoctors.filter(d => !d.isLastVisited), [filteredDoctors]);

  const hasCompleteProfile = (u) => {
    const hasName = Boolean(u.name || u.owner_name || u.pet_owner_name);
    const rawPhone = String(u.phone || u.mobile || u.whatsapp_number || u.last_otp_verified_at || "");
    const cleanPhone = rawPhone.replace(/[^\d]/g, "");
    const hasPhone = (cleanPhone.length === 10) || (cleanPhone.length === 12 && cleanPhone.startsWith("91"));
    return hasName && hasPhone;
  };

  // Appointment Flow: Date availability & active slots fetcher
  const fetchDateAvailabilityAndSlots = async (dateStr, targetDoc) => {
    const docToUse = targetDoc || selectedDoctor;
    const clinicId = docToUse?.clinicId || docToUse?.id;
    if (!clinicId) return;

    setSelectedDate(dateStr);
    setSelectedTimeSlot("");
    setResolvedDoctorId(null);
    setAvailableSlots([]);
    setDateAvailError("");
    setLoadingSlots(true);

    let docIdToUse = docToUse?.id;

    try {
      // 1. Resolve Doctor ID from clinic availability
      const availRes = await fetch(
        `${API_BASE}/clinics/${clinicId}/doctor-availability?service_type=in_clinic&date=${dateStr}`,
        { headers: token ? { Authorization: `Bearer ${token}` } : {} }
      );
      const availData = await availRes.json();
      
      const docIds = availData?.doctor_ids || availData?.data?.doctor_ids || availData?.doctors || availData?.data?.doctors || [];
      const normalizedDocIds = Array.isArray(docIds) ? docIds : (docIds ? [docIds] : []);

      if (normalizedDocIds.length === 1) {
        docIdToUse = normalizedDocIds[0];
        setResolvedDoctorId(docIdToUse);
      } else if (normalizedDocIds.length > 1) {
        const match = normalizedDocIds.find(id => String(id) === String(docToUse?.id));
        docIdToUse = match || normalizedDocIds[0];
        setResolvedDoctorId(docIdToUse);
      } else {
        if (docToUse?.id && !String(docToUse.id).startsWith("clinic-vet-")) {
          docIdToUse = docToUse.id;
          setResolvedDoctorId(docIdToUse);
        }
      }

      // 2. Fetch Active Slots for resolved doctor
      if (docIdToUse) {
        const slotsRes = await fetch(
          `${API_BASE}/doctors/active-slots?doctor_id=${docIdToUse}&date=${dateStr}`,
          { headers: token ? { Authorization: `Bearer ${token}` } : {} }
        );
        const slotsData = await slotsRes.json();
        
        const activeHours = slotsData?.active_hours || slotsData?.data?.active_hours || [];
        let allSlots = [];

        if (Array.isArray(activeHours) && activeHours.length > 0) {
          activeHours.forEach(ah => {
            if (Array.isArray(ah.slots)) {
              ah.slots.forEach(s => {
                allSlots.push({
                  start: s.start || s.start_time || "",
                  end: s.end || s.end_time || "",
                  label: s.label || (s.start ? `${s.start} - ${s.end}` : String(s)),
                  isBooked: s.is_booked === true || s.booked === true
                });
              });
            }
          });
        }

        if (allSlots.length === 0) {
          const rawSlots = slotsData?.slots || slotsData?.data?.slots || slotsData?.data || (Array.isArray(slotsData) ? slotsData : []);
          (Array.isArray(rawSlots) ? rawSlots : []).forEach(s => {
            if (typeof s === "string") {
              allSlots.push({ start: s, end: "", label: s, isBooked: false });
            } else {
              allSlots.push({
                start: s.start || s.start_time || s.time || "",
                end: s.end || s.end_time || "",
                label: s.label || s.time || (s.start ? `${s.start} - ${s.end}` : ""),
                isBooked: s.is_booked === true || s.booked === true
              });
            }
          });
        }

        // Filter past slots if selectedDate is TODAY
        const todayStr = new Date().toISOString().split("T")[0];
        const isToday = dateStr === todayStr || slotsData?.is_today === true;
        
        if (isToday) {
          const now = new Date();
          const currentMinutes = now.getHours() * 60 + now.getMinutes();
          
          allSlots = allSlots.filter(s => {
            if (!s.start) return true;
            const [h, m] = s.start.split(":").map(Number);
            if (isNaN(h)) return true;
            const slotMinutes = h * 60 + (m || 0);
            return slotMinutes > currentMinutes;
          });
        }

        // Filter out booked slots and limit to ONLY 5 SLOTS!
        const unbookedSlots = allSlots.filter(s => !s.isBooked);
        const firstFiveSlots = unbookedSlots.slice(0, 5);

        if (firstFiveSlots.length === 0) {
          setDateAvailError("No active slots available for this date. Please select another date.");
        } else {
          setDateAvailError("");
        }

        setAvailableSlots(firstFiveSlots);
      } else {
        setDateAvailError("No doctor available on this date. Please choose another date.");
      }
    } catch (err) {
      console.error("Failed to fetch slots for date", err);
      setDateAvailError("Could not load slots for this date. Please try another date.");
    } finally {
      setLoadingSlots(false);
    }
  };

  const handleBookNowClick = (doc) => {
    setSelectedDoctor(doc);
    if (orderType === "appointment") {
      const todayStr = getUpcomingDates(7)[0].dateStr;
      fetchDateAvailabilityAndSlots(todayStr, doc);
    }
    if (hasCompleteProfile(user)) {
      setFlowStep("describe");
    } else {
      setPendingDoctor(doc);
      setShowUserDetailsModal(true);
    }
  };

  // Appointment Flow: Slot Lock & Checkout Handler
  const handleLockSlotAndCheckout = async () => {
    const docIdToUse = resolvedDoctorId || selectedDoctor?.id;
    if (!selectedDate || !selectedTimeSlot || !docIdToUse) return;

    setProcessing(true);
    setError("");

    try {
      const res = await fetch(`${API_BASE}/doctors/${docIdToUse}/slots/lock`, {
        method: "POST",
        headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        body: JSON.stringify({
          date: selectedDate,
          time_slot: selectedTimeSlot
        })
      });

      if (res.status === 404) {
        console.warn("Slot lock API returned 404. Proceeding to checkout directly.");
        setLockId(null);
        setFlowStep("checkout");
        return;
      }

      const data = await res.json();

      if (res.ok && data.success !== false) {
        const lId = data.lock_id || data.data?.lock_id || data.id;
        setLockId(lId);
      } else {
        console.warn("Slot lock non-200 response:", data);
        setLockId(null);
      }
      
      setFlowStep("checkout");
    } catch (err) {
      console.warn("Slot locking error (non-blocking fallback to checkout):", err);
      setLockId(null);
      setFlowStep("checkout");
    } finally {
      setProcessing(false);
    }
  };

  // Payment Execution
  const handlePayment = async () => {
    const userId = user.id || user.user_id || authState?.user_id || authState?.userId;
    const petId = pet?.id || pet?.pet_id || user?.pet_id || (user?.pets && user.pets[0] && (user.pets[0].id || user.pets[0].pet_id)) || 0;
    const docIdToUse = resolvedDoctorId || selectedDoctor?.id || selectedDoctor?.doctor_id;
    const clinicIdToUse = selectedDoctor?.clinicId || selectedDoctor?.clinic_id || docIdToUse;
    
    if (!userId || !selectedDoctor?.id) {
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
        doctor_id: docIdToUse,
        clinic_id: clinicIdToUse,
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
        rzp.on('payment.failed', () => {
          if (orderType === "appointment" && lockId) {
            unlockCurrentSlot(lockId);
          }
          reject(new Error("Payment failed or cancelled."));
        });
        rzp.open();
      });

      const verifyPayload = {
        razorpay_order_id: paymentResult.razorpay_order_id,
        razorpay_payment_id: paymentResult.razorpay_payment_id,
        razorpay_signature: paymentResult.razorpay_signature,
        user_id: userId,
        doctor_id: docIdToUse,
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

      // Final Appointment Submission for In-Clinic Flow
      if (orderType === "appointment") {
        try {
          const appointmentPayload = {
            user_id: userId,
            clinic_id: selectedDoctor.clinicId || selectedDoctor.id,
            doctor_id: docIdToUse,
            patient_name: user.name || user.owner_name || "Pet Parent",
            patient_phone: user.phone || user.mobile || user.whatsapp_number || "",
            pet_name: pet.name || pet.pet_name || "Pet",
            pet_id: petId,
            date: selectedDate,
            time_slot: selectedTimeSlot,
            amount: totalAmount,
            currency: "INR",
            razorpay_payment_id: paymentResult.razorpay_payment_id,
            razorpay_order_id: paymentResult.razorpay_order_id,
            razorpay_signature: paymentResult.razorpay_signature,
            lock_id: lockId
          };

          await fetch(`${API_BASE}/appointments/submit`, {
            method: "POST",
            headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
            body: JSON.stringify(appointmentPayload)
          });
        } catch (apptErr) {
          console.warn("Appointment submit call warning (non-blocking):", apptErr);
        }
      }

      // Non-blocking image upload
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
      if (orderType === "appointment" && lockId) {
        unlockCurrentSlot(lockId);
      }
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
          <p className="text-slate-600 mb-6 text-sm">
            Your {orderType === "appointment" ? "Clinic Appointment" : "Video Consultation"} with <span className="font-semibold text-slate-900">{selectedDoctor?.name}</span> has been successfully booked.
          </p>
          {orderType === "appointment" && selectedDate && (
            <div className="bg-slate-50 border border-slate-200 rounded-xl p-3 mb-6 text-xs text-slate-700">
              <p>📅 <span className="font-bold">{selectedDate}</span> at <span className="font-bold">{selectedTimeSlot}</span></p>
              <p className="text-slate-500 mt-1">🏥 {selectedDoctor?.clinicName || "Veterinary Clinic"}</p>
            </div>
          )}
          <button onClick={handleModalClose} className="w-full py-3 bg-black text-white font-bold rounded-xl text-sm hover:bg-slate-800 transition-colors shadow-md">
            Done
          </button>
        </div>
      </div>
    );
  }

  const isPhotoRequired = orderType !== "appointment";
  const canContinueToPayment = issueText.trim() && (!isPhotoRequired || attachedImages.length > 0) && consentGiven;

  const renderDoctorCard = (doc, featured = false) => {
    const isOnline = doc.availability?.isAvailable;
    return (
      <div key={`${doc.clinicId}_${doc.id}`} className="bg-white border border-slate-100 rounded-xl p-2.5 pl-11 pr-2.5 hover:shadow-sm transition-all flex flex-col relative ml-4 mt-1" style={{minHeight: '82px'}}>
        {/* Left overlapping avatar */}
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
            {doc.clinicName && (
              <p className="text-[10px] font-medium text-slate-500 truncate">🏥 {doc.clinicName}</p>
            )}
            <p className="text-[10px] font-semibold text-emerald-600">
              {isOnline ? "Online now · Consult available" : "Consult available"}
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
                {orderType === "appointment" ? "Book Visit" : "Talk to Vet"}
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
        @keyframes scaleInUp {
          from { opacity: 0; transform: scale(0.96) translateY(12px); }
          to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      `}</style>
      
      <div className="bg-white md:rounded-3xl shadow-xl flex-1 flex flex-col max-w-5xl mx-auto w-full overflow-hidden border border-slate-200 animate-[scaleInUp_0.25s_ease-out]">
        
        {/* Header */}
        <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
          <div className="flex items-center gap-3">
            {flowStep !== "list" && (
              <button 
                onClick={() => {
                  if (flowStep === "describe") setFlowStep("list");
                  else if (flowStep === "checkout") setFlowStep("describe");
                }} 
                className="text-slate-500 hover:text-black transition-colors"
              >
                <ChevronRight className="w-5 h-5 rotate-180" />
              </button>
            )}
            <div>
              <h2 className="text-base font-bold text-slate-900">
                {flowStep === "checkout" 
                  ? 'Secure Checkout' 
                  : flowStep === "describe" 
                    ? 'Describe & Select Appointment Details' 
                    : orderType === "appointment" ? 'Select Veterinary Clinic' : 'Select a Veterinarian'}
              </h2>
              <p className="text-xs text-slate-400">
                {flowStep === "checkout" 
                  ? 'Complete your payment' 
                  : flowStep === "describe" 
                    ? 'Tell us what is wrong and pick your visit date/time' 
                    : 'Choose a specialist for your pet'}
              </p>
            </div>
          </div>
          <button onClick={handleModalClose} className="p-1.5 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors text-slate-600">
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Content Body */}
        <div className="flex-1 overflow-y-auto bg-slate-50/50">
          {flowStep === "list" && (
            <div className="p-4">
              <div className="relative mb-4 max-w-md">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input 
                  type="text" 
                  placeholder="Search doctors, clinics or specializations..." 
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
            <div className="p-4 max-w-xl mx-auto space-y-3.5">
              {/* Issue Description Textarea */}
              <div>
                <h3 className="text-xs font-bold text-slate-900 mb-1.5">Describe the issue</h3>
                <textarea
                  value={issueText}
                  onChange={(e) => setIssueText(e.target.value)}
                  maxLength={500}
                  placeholder="Example: Vomiting since morning, not eating, low energy..."
                  className="w-full h-20 border border-slate-200 rounded-xl p-2.5 text-xs focus:border-black outline-none resize-none shadow-sm"
                />
                <p className="text-[10px] text-slate-400 text-right mt-0.5">{issueText.length}/500</p>
              </div>

              {/* Photo Upload */}
              <div>
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
                  className="text-xs border border-slate-200 p-1.5 rounded-lg w-full bg-white"
                />
                {attachedImages.length > 0 && (
                  <img src={attachedImages[0].uri} className="mt-2 w-20 h-20 rounded-xl object-cover border border-slate-200" alt="attached" />
                )}
              </div>

              {/* Appointment Specific Section: Date & Active Slot Select on the SAME Describe Page */}
              {orderType === "appointment" && (
                <div className="border-t border-slate-100 pt-3 space-y-3">
                  {/* Date Chips Row (Horizontal Scroll) */}
                  <div>
                    <h4 className="text-xs font-bold text-slate-900 mb-2 flex items-center gap-1.5">
                      <Calendar className="w-3.5 h-3.5 text-blue-600" /> Select Appointment Date
                    </h4>
                    
                    <div className="flex overflow-x-auto no-scrollbar gap-2 pb-1 scroll-smooth">
                      {getUpcomingDates(7).map((d) => {
                        const isSelected = selectedDate === d.dateStr;
                        return (
                          <button
                            key={d.dateStr}
                            type="button"
                            onClick={() => fetchDateAvailabilityAndSlots(d.dateStr)}
                            className={`flex-shrink-0 px-3 py-2 rounded-xl border text-center transition-all ${
                              isSelected
                                ? "border-blue-600 bg-blue-50/70 text-blue-700 font-bold shadow-sm"
                                : "border-slate-200 bg-white text-slate-700 hover:border-slate-300"
                            }`}
                          >
                            <p className="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">{d.dayName}</p>
                            <p className="text-xs font-extrabold whitespace-nowrap">{d.dateNum} {d.monthName}</p>
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  {/* Active Slots (Only 5 Slots shown after current time) */}
                  <div>
                    <h4 className="text-xs font-bold text-slate-900 mb-2 flex items-center gap-1.5">
                      <Clock className="w-3.5 h-3.5 text-blue-600" /> Select Time Slot (Next 5 Available)
                    </h4>

                    {loadingSlots || loadingDateAvail ? (
                      <div className="py-4 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
                        <Loader2 className="w-3.5 h-3.5 animate-spin text-blue-600" />
                        Loading available time slots...
                      </div>
                    ) : availableSlots.length === 0 ? (
                      <div className="p-3 text-center bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500">
                        {dateAvailError || "No active slots available for this date. Please select another date."}
                      </div>
                    ) : (
                      <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        {availableSlots.map((slotObj, idx) => {
                          const slotText = slotObj.label || slotObj.start;
                          const isSelected = selectedTimeSlot === slotText;
                          return (
                            <button
                              key={idx}
                              type="button"
                              onClick={() => setSelectedTimeSlot(slotText)}
                              className={`py-2 px-2.5 rounded-xl text-xs font-semibold text-center transition-all ${
                                isSelected
                                  ? "bg-blue-600 text-white shadow-md font-bold"
                                  : "bg-white text-slate-800 border border-slate-200 hover:border-slate-300"
                              }`}
                            >
                              {slotText}
                            </button>
                          );
                        })}
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* Consent Checkbox */}
              <label className="flex items-start gap-2 cursor-pointer bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                <input 
                  type="checkbox" 
                  checked={consentGiven} 
                  onChange={(e) => setConsentGiven(e.target.checked)}
                  className="w-4 h-4 rounded border-slate-300 text-black focus:ring-black mt-0.5"
                />
                <span className="text-[11px] text-slate-600">
                  I understand online/clinic consultation guidance. Emergency cases may need immediate hospital care.
                </span>
              </label>

              {error && (
                <p className="text-xs text-red-600 bg-red-50 border border-red-100 rounded-xl p-2.5">
                  {error}
                </p>
              )}

              {/* CTA Button */}
              <button
                disabled={
                  !canContinueToPayment || 
                  (orderType === "appointment" && (!selectedDate || !selectedTimeSlot || processing))
                }
                onClick={() => {
                  if (orderType === "appointment") {
                    handleLockSlotAndCheckout();
                  } else {
                    setFlowStep("checkout");
                  }
                }}
                className="w-full py-2.5 bg-black text-white rounded-xl text-xs font-bold disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 transition-colors shadow-md flex items-center justify-center gap-2"
              >
                {processing ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" /> Locking slot...
                  </>
                ) : (
                  "Proceed to Checkout →"
                )}
              </button>
            </div>
          )}

          {flowStep === "checkout" && (
            <div className="p-3 max-w-3xl mx-auto w-full flex flex-col md:flex-row gap-2">
              {/* Left Column: Summary */}
              <div className="flex-1">
                <h3 className="text-sm font-bold text-slate-900 mb-2">Patient Details</h3>
                <div className="bg-white border border-slate-200 rounded-2xl p-2.5 mb-4 shadow-sm text-xs">
                  <div className="flex justify-between py-1.5 border-b border-slate-100">
                    <span className="text-slate-500">Pet</span>
                    <span className="font-semibold text-slate-900">{pet.name || pet.pet_name}</span>
                  </div>
                  <div className="flex justify-between py-1.5 border-b border-slate-100">
                    <span className="text-slate-500">Owner</span>
                    <span className="font-semibold text-slate-900">{user.name || user.owner_name}</span>
                  </div>
                  <div className="flex justify-between py-1.5 border-b border-slate-100">
                    <span className="text-slate-500">Symptom</span>
                    <span className="font-semibold text-slate-900 max-w-[200px] text-right truncate" title={issueText || symptomText}>{issueText || symptomText}</span>
                  </div>
                  {orderType === "appointment" && selectedDate && (
                    <>
                      <div className="flex justify-between py-1.5 border-b border-slate-100">
                        <span className="text-slate-500">Visit Date</span>
                        <span className="font-semibold text-blue-700">{selectedDate}</span>
                      </div>
                      <div className="flex justify-between py-1.5 border-b border-slate-100">
                        <span className="text-slate-500">Time Slot</span>
                        <span className="font-semibold text-blue-700">{selectedTimeSlot}</span>
                      </div>
                    </>
                  )}
                </div>

                <h3 className="text-sm font-bold text-slate-900 mb-2">Doctor / Clinic Details</h3>
                <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-4">
                  {selectedDoctor?.image ? (
                    <img src={selectedDoctor.image} alt={selectedDoctor.name} className="w-12 h-12 rounded-xl object-cover" />
                  ) : (
                    <div className="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold">
                      {selectedDoctor?.name?.charAt(0)}
                    </div>
                  )}
                  <div>
                    <h4 className="font-bold text-slate-900 text-sm">{selectedDoctor?.name}</h4>
                    <p className="text-xs text-slate-500">{selectedDoctor?.specialization}</p>
                    {selectedDoctor?.clinicName && (
                      <p className="text-xs text-slate-500 font-medium mt-0.5">🏥 {selectedDoctor.clinicName}</p>
                    )}
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
                      <p className="text-xs font-semibold text-slate-900">Need GST invoice</p>
                      <p className="text-[11px] text-slate-500">Add GST details for business billing</p>
                    </div>
                  </label>
                  {gstInvoiceChecked && (
                    <input
                      type="text"
                      maxLength={15}
                      value={gstNumber}
                      onChange={(e) => setGstNumber(e.target.value.toUpperCase().replace(/\s+/g, ""))}
                      placeholder="Enter 15-digit GST number"
                      className="mt-3 w-full border border-slate-200 rounded-xl px-3 py-2 text-xs uppercase tracking-wide focus:border-black outline-none"
                    />
                  )}
                </div>

                <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xl">
                  <h3 className="text-sm font-bold text-slate-900 mb-4">Payment Summary</h3>
                  
                  <div className="space-y-2 text-xs mb-4">
                    <div className="flex justify-between">
                      <span className="text-slate-500">Consultation Fee</span>
                      <span className="font-semibold text-slate-900">{formatCurrency(fee)}</span>
                    </div>
                    
                    <div className="flex justify-between">
                      <span className="text-slate-500">GST (18%)</span>
                      <span className="font-semibold text-slate-900">{formatCurrency(gstAmount)}</span>
                    </div>

                    <div className="border-t border-slate-100 pt-2 flex justify-between font-bold text-sm text-slate-900">
                      <span>Total Amount</span>
                      <span className="text-blue-600">{formatCurrency(totalAmount)}</span>
                    </div>
                  </div>

                  {error && (
                    <p className="text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg p-2 mb-3">
                      {error}
                    </p>
                  )}

                  <button
                    onClick={handlePayment}
                    disabled={processing}
                    className="w-full py-2.5 bg-black text-white font-bold text-xs rounded-xl hover:bg-slate-800 transition-all shadow-md flex items-center justify-center gap-2"
                  >
                    {processing ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin" /> Processing...
                      </>
                    ) : (
                      <>
                        <CreditCard className="w-4 h-4" /> Pay {formatCurrency(totalAmount)}
                      </>
                    )}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
      
      {/* Doctor Profile Modal */}
      {viewProfileDoctor && (
        <div className="fixed inset-0 z-[200] flex items-end sm:items-center justify-center bg-black/50 animate-[fadeIn_0.15s_ease-out]" onClick={() => setViewProfileDoctor(null)}>
          <div
            className="bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl animate-[scaleInUp_0.2s_ease-out]"
            onClick={e => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
              <span className="text-sm font-bold text-slate-800">Doctor Profile</span>
              <button onClick={() => setViewProfileDoctor(null)} className="p-1.5 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors">
                <X className="w-4 h-4 text-slate-500" />
              </button>
            </div>

            {/* Doctor Info */}
            <div className="p-5">
              <div className="flex gap-4 mb-4">
                {viewProfileDoctor.image ? (
                  <img src={viewProfileDoctor.image} alt={viewProfileDoctor.name} className="w-20 h-20 rounded-2xl object-cover border border-slate-100 shadow-sm flex-shrink-0" />
                ) : (
                  <div className="w-20 h-20 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-2xl font-bold flex-shrink-0">
                    {viewProfileDoctor.name?.charAt(0)}
                  </div>
                )}
                <div>
                  <h3 className="font-bold text-slate-900 text-base leading-tight">{viewProfileDoctor.name}</h3>
                  <p className="text-xs text-slate-500 mt-1">{viewProfileDoctor.specialization}</p>
                  {viewProfileDoctor.experience && (
                    <span className="inline-block mt-1.5 text-xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md">
                      {viewProfileDoctor.experience}y experience
                    </span>
                  )}
                  <p className="text-xs font-semibold text-emerald-600 mt-1.5">
                    {viewProfileDoctor.availability?.isAvailable ? "🟢 Online now" : "Consult available"}
                  </p>
                </div>
              </div>

              {/* Divider row */}
              <div className="grid grid-cols-2 gap-3 mb-4">
                <div className="bg-slate-50 rounded-xl p-3 text-center">
                  <p className="text-[10px] text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Consultation Fee</p>
                  <p className="text-base font-extrabold text-slate-800">{formatCurrency(resolveDoctorFee(viewProfileDoctor, slot))}</p>
                </div>
                <div className="bg-slate-50 rounded-xl p-3 text-center">
                  <p className="text-[10px] text-slate-400 uppercase tracking-wide font-semibold mb-0.5">Experience</p>
                  <p className="text-base font-extrabold text-slate-800">{viewProfileDoctor.experience || "—"}y</p>
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
                {orderType === "appointment" ? "Book Visit" : "Talk to Vet"}
              </button>
            </div>
          </div>
        </div>
      )}

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
              if (orderType === "appointment") {
                const todayStr = getUpcomingDates(7)[0].dateStr;
                fetchDateAvailabilityAndSlots(todayStr, pendingDoctor);
              }
              setFlowStep("describe");
              setPendingDoctor(null);
            }
          }}
        />
      )}
    </div>
  );
}
