import React, { useEffect, useState, useMemo, useRef, useCallback } from "react";
import { X, ChevronLeft, ChevronRight, Search, Shield, CreditCard, CheckCircle, Users, Calendar, Clock, Loader2, Filter, Star, MapPin, Award, Check } from "lucide-react";
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
  const cleanPath = text.startsWith("/") ? text.slice(1) : text;
  return `https://snoutiq.com/backend/${cleanPath}`;
}

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

// LIVE Real-Time Pricing Evaluation (Day: 8:00 AM - 8:00 PM, Night: 8:01 PM - 7:59 AM)
function isDayTimeNow() {
  const now = new Date();
  const hour = now.getHours();
  // 8:00 AM (8) to 8:00 PM (19:59). 20:00 (8:00 PM) to 7:59 AM is night time.
  return hour >= 8 && hour < 20;
}

function getDoctorCurrentPrice(doc) {
  if (!doc) return 499;
  const isDay = isDayTimeNow();
  const dayRate = Number(doc.feeDay || doc.video_day_rate || doc.clinic_day_fee || 499);
  const nightRate = Number(doc.feeNight || doc.video_night_rate || doc.video_day_rate || doc.clinic_night_fee || 650);
  return isDay ? dayRate : nightRate;
}

function getClinicCurrentPrice(clinic) {
  if (!clinic) return "499";
  
  // Extract from clinic_services[0].price first as requested
  const servicePrice = clinic.clinic_services && clinic.clinic_services.length > 0 
    ? clinic.clinic_services[0]?.price 
    : null;

  if (servicePrice !== null && servicePrice !== undefined && servicePrice !== "" && !isNaN(Number(servicePrice)) && Number(servicePrice) > 0) {
    return String(Math.round(Number(servicePrice)));
  }

  // Fallback to static 499 if empty/null
  return "499";
}

function enrichDoctorObject(doc, clinicMap = new Map()) {
  if (!doc) return null;
  const regId = String(doc.vet_registeration_id || doc.clinic_id || doc.clinicId || "");
  const clinic = clinicMap.get(regId);
  const expYears = parseInt(doc.years_of_experience || doc.experience || 0);

  const rawRating = doc.google_rating ?? doc.clinic?.rating ?? clinic?.google_rating ?? clinic?.rating;
  const parsedRating = (rawRating !== null && rawRating !== undefined && rawRating !== "" && !isNaN(Number(rawRating)))
    ? Number(rawRating) 
    : 5.0;

  const reviewCount = doc.google_user_ratings_total ?? doc.clinic?.user_ratings_total ?? clinic?.google_user_ratings_total ?? clinic?.user_ratings_total ?? 50;

  const imgUrl = doc.doctor_image_blob_url || normalizeImage(doc.doctor_image || doc.doctor_blob_url || doc.doctor_image_url || doc.image);

  return {
    ...doc,
    id: doc.id || doc.doctor_id,
    doctor_id: doc.doctor_id || doc.id,
    name: doc.doctor_name || doc.name || "Doctor",
    image: imgUrl,
    degree: doc.degree || "BVSc",
    experience: expYears,
    years_of_experience: String(expYears),
    specialization: formatSpecialization(doc.specialization_select_all_that_apply || doc.specialization),
    feeDay: Number(doc.video_day_rate || doc.clinic_day_fee || 499),
    feeNight: Number(doc.video_night_rate || doc.video_day_rate || doc.clinic_night_fee || 650),
    bio: doc.bio || "",
    status: doc.doctor_status || "available",
    responseTimeDay: doc.response_time_for_online_consults_day || "0 To 15 Mins",
    responseTimeNight: doc.response_time_for_online_consults_night || "15 To 20 Mins",
    followUpPolicy: doc.do_you_offer_a_free_follow_up_within_3_days_after_a_consulta || "",
    googleRating: parsedRating,
    googleReviewCount: Number(reviewCount),
    clinicCity: doc.clinic?.city || doc.clinic_address || clinic?.city || "",
    clinicName: doc.clinic_name || doc.clinic?.name || clinic?.name || "",
    vet_registeration_id: regId
  };
}


function isSlotAfterCurrentTime(slotTimeStr, selectedDateStr) {
  if (!slotTimeStr) return false;
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  const todayStr = `${year}-${month}-${day}`;
  
  if (selectedDateStr && selectedDateStr > todayStr) {
    return true;
  }
  if (selectedDateStr && selectedDateStr < todayStr) {
    return false;
  }

  const currentMinutes = now.getHours() * 60 + now.getMinutes();

  const match = String(slotTimeStr).trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/i);
  if (!match) return true;

  let hours = parseInt(match[1], 10);
  const mins = parseInt(match[2], 10);
  const ampm = match[3] ? match[3].toUpperCase() : null;

  if (ampm === "PM" && hours < 12) hours += 12;
  if (ampm === "AM" && hours === 12) hours = 0;

  const slotMinutes = hours * 60 + mins;
  return slotMinutes > currentMinutes;
}

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
  // Doctor States
  const [lastVetDoctors, setLastVetDoctors] = useState([]);
  const [hasLastVet, setHasLastVet] = useState(false);
  const [showAllVets, setShowAllVets] = useState(false);
  const [allVetsLoading, setAllVetsLoading] = useState(false);
  const [allVetsLoaded, setAllVetsLoaded] = useState(false);
  const [otherDoctors, setOtherDoctors] = useState([]);

  // Clinic States (Book Visit Flow)
  const [lastVetClinics, setLastVetClinics] = useState([]);
  const [hasLastClinic, setHasLastClinic] = useState(false);
  const [showAllClinics, setShowAllClinics] = useState(false);
  const [allClinicsLoading, setAllClinicsLoading] = useState(false);
  const [allClinicsLoaded, setAllClinicsLoaded] = useState(false);
  const [otherClinics, setOtherClinics] = useState([]);

  const [selectedClinic, setSelectedClinic] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  
  const [selectedExpFilter, setSelectedExpFilter] = useState("any"); // "any" | "1" | "3" | "5" | "10"
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [viewProfileDoctor, setViewProfileDoctor] = useState(null);

  const [flowStep, setFlowStep] = useState("list"); // "list" | "describe" | "checkout"
  const [issueText, setIssueText] = useState(() => symptomText || localStorage.getItem("symptom_description") || "");
  const [attachedImages, setAttachedImages] = useState([]);
  const [consentGiven, setConsentGiven] = useState(true);
  const [disclaimerAccepted, setDisclaimerAccepted] = useState(false);

  const handleImageUpload = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = (event) => {
        setAttachedImages(prev => [...prev, { id: Date.now() + Math.random(), src: event.target.result, file }]);
      };
      reader.readAsDataURL(file);
    });
  };

  const removeAttachedImage = (idToRemove) => {
    setAttachedImages(prev => prev.filter(img => img.id !== idToRemove));
  };

  const scrollContainerRef = useRef(null);

  // ALWAYS scroll container to top when screen opens, step changes, or profile modal opens
  useEffect(() => {
    if (scrollContainerRef.current) {
      scrollContainerRef.current.scrollTop = 0;
    }
    window.scrollTo(0, 0);
  }, [flowStep, viewProfileDoctor]);

  // Sync symptomText dynamically when passed down asynchronously
  useEffect(() => {
    if (symptomText) {
      setIssueText(symptomText);
    } else if (!issueText) {
      const stored = localStorage.getItem("symptom_description");
      if (stored) setIssueText(stored);
    }
  }, [symptomText]);
  
  // Appointment Flow Specific States
  const [selectedDate, setSelectedDate] = useState("");
  const [selectedTimeSlot, setSelectedTimeSlot] = useState("");
  const [resolvedDoctorId, setResolvedDoctorId] = useState(null);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [loadingSlots, setLoadingSlots] = useState(false);
  const [dateAvailError, setDateAvailError] = useState("");
  const [lockId, setLockId] = useState(null);

  const [paymentPreference, setPaymentPreference] = useState("pay_online"); // "pay_online" | "pay_at_clinic"
  const [gstInvoiceChecked, setGstInvoiceChecked] = useState(false);
  const [gstNumber, setGstNumber] = useState("");

  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);

  const authState = useMemo(() => readAiAuthState(), []);
  const token = authState?.token;
  const user = authState?.user || {};
  const userId = user.id || user.user_id || authState?.user_id || authState?.userId || 1179;

  let rawPet = preSelectedPet || (user.pets && user.pets[0]) || authState?.pet || {};
  if (!rawPet || Object.keys(rawPet).length === 0) {
    try {
      const storedPet = localStorage.getItem("selected_pet_data") || localStorage.getItem("current_pet");
      if (storedPet) rawPet = JSON.parse(storedPet);
    } catch (e) {}
  }
  const pet = rawPet;

  const displayUserName = user.name || user.owner_name || user.first_name || user.user_name || user.full_name || localStorage.getItem("user_name") || "Pet Parent";
  const displayUserMobile = user.mobile || user.phone || user.phone_number || user.user_mobile || user.contact || localStorage.getItem("user_mobile") || "N/A";
  
  const displayPetName = pet.name || pet.pet_name || pet.title || localStorage.getItem("pet_name") || "Pet";
  const displayPetBreed = pet.breed || pet.pet_breed || pet.species || pet.pet_species || pet.pet_type || pet.type || pet.category || localStorage.getItem("pet_breed") || "Dog/Cat";

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
      console.warn("Slot unlock error:", err);
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

  // Helper to fetch all doctors
  const fetchAllDoctors = useCallback(async () => {
    setAllVetsLoading(true);

    try {
      const [docRes, clinicRes] = await Promise.all([
        fetch(`${API_BASE}/exported_from_excell_doctors`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        }).then(r => r.ok ? r.json() : null).catch(() => null),
        fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?user_id=${userId}`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        }).then(r => r.ok ? r.json() : null).catch(() => null)
      ]);

      const rawDocs = Array.isArray(docRes?.doctors) 
        ? docRes.doctors 
        : (Array.isArray(docRes?.data?.doctors) 
          ? docRes.data.doctors 
          : (Array.isArray(docRes?.data) ? docRes.data : (Array.isArray(docRes) ? docRes : [])));

      const rawClinics = Array.isArray(clinicRes?.data?.data) 
        ? clinicRes.data.data 
        : (Array.isArray(clinicRes?.data) ? clinicRes.data : []);

      const clinicMap = new Map();
      rawClinics.forEach(c => {
        const key = String(c.id || c.clinic_id || "");
        if (key) clinicMap.set(key, c);
      });

      const enrichedDoctors = rawDocs.map(doc => enrichDoctorObject(doc, clinicMap)).filter(Boolean);
      setOtherDoctors(enrichedDoctors);
      setAllVetsLoaded(true);
    } catch (err) {
      console.error("Failed to load all doctors", err);
    } finally {
      setAllVetsLoading(false);
      setLoading(false);
    }
  }, [token, userId]);

  // Helper to fetch all clinics (Book Visit Flow)
  const fetchAllClinics = useCallback(async () => {
    setAllClinicsLoading(true);
    try {
      let inclinicRes;
      try {
        inclinicRes = await fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?user_id=${userId}`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        });
        if (!inclinicRes.ok) throw new Error("Inclinic auth fetch failed");
      } catch (e) {
        inclinicRes = await fetch(`${API_BASE}/inclinic-lists-new-after-10th-may-registerations?user_id=${userId}`);
      }
      const inclinicData = await inclinicRes.json();
      const rawClinicsData = Array.isArray(inclinicData?.data?.data) 
        ? inclinicData.data.data 
        : (Array.isArray(inclinicData?.data) ? inclinicData.data : (Array.isArray(inclinicData?.clinics) ? inclinicData.clinics : []));
      setOtherClinics(rawClinicsData);
      setAllClinicsLoaded(true);
    } catch (e) {
      console.error("Failed to load inclinic list:", e);
    } finally {
      setAllClinicsLoading(false);
      setLoading(false);
    }
  }, [token, userId]);

  // STEP 1 — Initial Data Fetching for both Talk to Vet & Book Visit
  useEffect(() => {
    async function loadInitialData() {
      setLoading(true);

      if (orderType === "appointment") {
        // Book Visit Flow: STEP 1 Check last-vet-details FIRST
        try {
          const res = await fetch(`${API_BASE}/users/last-vet-details?user_id=${userId}`, {
            headers: token ? { Authorization: `Bearer ${token}` } : {}
          });
          const data = await res.json();
          
          const rawLastClinics = data?.data?.clinics || data?.clinics || (Array.isArray(data?.data) ? data.data.filter(x => x.clinic_name || x.name || x.clinic) : []);
          const docs = data?.data?.doctors || data?.doctors || [];
          let extractedClinics = [...rawLastClinics];

          if (extractedClinics.length === 0 && docs.length > 0) {
            docs.forEach(d => {
              if (d.clinic) {
                extractedClinics.push(d.clinic);
              } else if (d.clinic_name || d.clinic_id) {
                extractedClinics.push({
                  id: d.clinic_id || d.id,
                  name: d.clinic_name || "Veterinary Clinic",
                  city: d.clinic_address || d.city || "Gurugram",
                  address: d.clinic_address || "",
                  google_rating: d.google_rating || 5.0,
                  google_user_ratings_total: d.google_user_ratings_total || 50,
                  doctors: [d]
                });
              }
            });
          }

          const uniqueLastClinics = [];
          const seenIds = new Set();
          extractedClinics.forEach(c => {
            const cId = String(c.id || c.clinic_id || c.name);
            if (cId && !seenIds.has(cId)) {
              seenIds.add(cId);
              uniqueLastClinics.push(c);
            }
          });

          const hasClinics = data?.success === true && uniqueLastClinics.length > 0;

          if (hasClinics) {
            setLastVetClinics(uniqueLastClinics);
            setHasLastClinic(true);
            setLoading(false);
          } else {
            setHasLastClinic(false);
            await fetchAllClinics();
          }
        } catch (err) {
          console.warn("last-vet-details clinic check failed, loading all clinics as fallback:", err);
          setHasLastClinic(false);
          await fetchAllClinics();
        }
        return;
      }

      // Talk to Vet Flow: STEP 1 Check last-vet-details FIRST
      try {
        const res = await fetch(`${API_BASE}/users/last-vet-details?user_id=${userId}`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        });
        const data = await res.json();
        const docs = data?.data?.doctors || data?.doctors || (Array.isArray(data?.data) ? data.data : []);

        const hasVet = data?.success === true && Array.isArray(docs) && docs.length > 0;

        if (hasVet) {
          const enrichedLastVet = docs.map(d => enrichDoctorObject(d)).filter(Boolean);
          setLastVetDoctors(enrichedLastVet);
          setHasLastVet(true);
          setLoading(false);
        } else {
          setHasLastVet(false);
          await fetchAllDoctors();
        }
      } catch (err) {
        console.warn("last-vet-details check failed, loading all doctors as fallback:", err);
        setHasLastVet(false);
        await fetchAllDoctors();
      }
    }

    loadInitialData();
  }, [token, orderType, userId, fetchAllDoctors, fetchAllClinics]);

  const handleViewMoreClick = () => {
    setShowAllVets(true);
    if (!allVetsLoaded) {
      fetchAllDoctors();
    }
  };

  const handleViewMoreClinicsClick = () => {
    setShowAllClinics(true);
    if (!allClinicsLoaded) {
      fetchAllClinics();
    }
  };

  // Filter Last Vet Doctors by Search & Experience
  const filteredLastVetDoctors = useMemo(() => {
    const minYears = parseInt(selectedExpFilter) || 0;
    return lastVetDoctors.filter(doc => {
      const matchesSearch = (doc.name || "").toLowerCase().includes(searchQuery.toLowerCase()) || 
                            (doc.specialization || "").toLowerCase().includes(searchQuery.toLowerCase()) || 
                            (doc.clinicCity || "").toLowerCase().includes(searchQuery.toLowerCase());
      const matchesExp = (doc.experience || 0) >= minYears;
      return matchesSearch && matchesExp;
    });
  }, [lastVetDoctors, searchQuery, selectedExpFilter]);

  // Filter Other Doctors by Search & Experience, EXCLUDING duplicates from lastVetDoctors
  const filteredOtherDoctors = useMemo(() => {
    const minYears = parseInt(selectedExpFilter) || 0;
    const deduplicated = otherDoctors.filter(
      doc => !lastVetDoctors.some(lv => String(lv.doctor_id || lv.id) === String(doc.id || doc.doctor_id))
    );

    return deduplicated.filter(doc => {
      const matchesSearch = (doc.name || "").toLowerCase().includes(searchQuery.toLowerCase()) || 
                            (doc.specialization || "").toLowerCase().includes(searchQuery.toLowerCase()) || 
                            (doc.clinicCity || "").toLowerCase().includes(searchQuery.toLowerCase());
      const matchesExp = (doc.experience || 0) >= minYears;
      return matchesSearch && matchesExp;
    });
  }, [otherDoctors, lastVetDoctors, searchQuery, selectedExpFilter]);

  // Filter Last Vet Clinics by Search
  const filteredLastVetClinics = useMemo(() => {
    return lastVetClinics.filter(c => 
      (c.name || "").toLowerCase().includes(searchQuery.toLowerCase()) || 
      (c.city || "").toLowerCase().includes(searchQuery.toLowerCase()) ||
      (c.address || "").toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [lastVetClinics, searchQuery]);

  // Filter Other Clinics by Search, EXCLUDING duplicates from lastVetClinics
  const filteredOtherClinics = useMemo(() => {
    const deduplicated = otherClinics.filter(
      c => !lastVetClinics.some(lc => String(lc.id || lc.clinic_id) === String(c.id || c.clinic_id))
    );

    return deduplicated.filter(c => 
      (c.name || "").toLowerCase().includes(searchQuery.toLowerCase()) || 
      (c.city || "").toLowerCase().includes(searchQuery.toLowerCase()) ||
      (c.address || "").toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [otherClinics, lastVetClinics, searchQuery]);


  // Live Current Fee calculation
  const currentFee = selectedDoctor ? getDoctorCurrentPrice(selectedDoctor) : (selectedClinic ? Number(getClinicCurrentPrice(selectedClinic)) : 499);
  const GST_RATE = 0.18;
  const gstAmount = Math.round(currentFee * GST_RATE);
  const totalAmount = currentFee + gstAmount;

  // Fetch slots for In-Clinic Appointment
  const fetchDateAvailabilityAndSlots = async (dateStr, targetDoc, targetClinic) => {
    const docToUse = targetDoc || selectedDoctor;
    const clinicObj = targetClinic || selectedClinic;
    const clinicId = clinicObj?.id || clinicObj?.clinic_id || docToUse?.clinicId || docToUse?.vet_registeration_id || docToUse?.id;

    if (!clinicId) return;

    setSelectedDate(dateStr);
    setSelectedTimeSlot("");
    setResolvedDoctorId(null);
    setAvailableSlots([]);
    setDateAvailError("");
    setLoadingSlots(true);

    try {
      let docIdToUse = docToUse?.id;
      try {
        const availRes = await fetch(`${API_BASE}/clinics/${clinicId}/doctor-availability?service_type=in_clinic&date=${dateStr}`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        });
        if (availRes.ok) {
          const availData = await availRes.json();
          if (availData.available_doctor_id || availData.doctor_id) {
            docIdToUse = availData.available_doctor_id || availData.doctor_id;
          } else if (Array.isArray(availData.doctor_ids) && availData.doctor_ids.length > 0) {
            docIdToUse = availData.doctor_ids[0];
          }
        }
      } catch (e) {}

      setResolvedDoctorId(docIdToUse);

      let slotsData = null;
      try {
        const slotsRes = await fetch(`${API_BASE}/doctors/${docIdToUse}/slots/summary?date=${dateStr}&service_type=in_clinic`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        });
        if (slotsRes.ok) slotsData = await slotsRes.json();
      } catch (e) {}

      if (!slotsData || !slotsData.success || !Array.isArray(slotsData.slots) || slotsData.slots.length === 0) {
        try {
          const altRes = await fetch(`${API_BASE}/doctors/active-slots?doctor_id=${docIdToUse}&date=${dateStr}`, {
            headers: token ? { Authorization: `Bearer ${token}` } : {}
          });
          if (altRes.ok) {
            const altData = await altRes.json();
            if (altData.success && Array.isArray(altData.active_hours)) {
              const extracted = [];
              altData.active_hours.forEach(ah => {
                if (Array.isArray(ah.slots)) {
                  ah.slots.forEach(s => extracted.push(typeof s === "string" ? { start: s, label: s, isBooked: false } : s));
                }
              });
              if (extracted.length > 0) slotsData = { success: true, slots: extracted };
            }
          }
        } catch (e) {}
      }

      const rawSlotsList = slotsData?.slots || slotsData?.data?.slots || [];
      if (Array.isArray(rawSlotsList) && rawSlotsList.length > 0) {
        let allSlots = rawSlotsList.map(s => {
          if (typeof s === "string") return { start: s, label: s, isBooked: false };
          return { start: s.start || s.time || "", label: s.label || s.time || s.start || "", isBooked: s.is_booked === true || s.booked === true };
        }).filter(s => s.start);

        const unbooked = allSlots.filter(s => !s.isBooked && isSlotAfterCurrentTime(s.start || s.label, dateStr));
        const upcoming6Slots = unbooked.slice(0, 6);
        if (upcoming6Slots.length > 0) {
          setAvailableSlots(upcoming6Slots);
        } else {
          setDateAvailError("No upcoming slots left for today. Please select another date.");
        }
      } else {
        setDateAvailError("No active slots found for this date.");
      }
    } catch (err) {
      setDateAvailError("Error loading slots.");
    } finally {
      setLoadingSlots(false);
    }
  };

  const handleSelectClinic = (clinic) => {
    setSelectedClinic(clinic);
    const clinicPrice = Number(getClinicCurrentPrice(clinic));
    const clinicDocs = Array.isArray(clinic.doctors) && clinic.doctors.length > 0 ? clinic.doctors : [];
    const firstDoc = clinicDocs[0] ? {
      id: clinicDocs[0].id || clinicDocs[0].doctor_id,
      name: clinicDocs[0].doctor_name || clinicDocs[0].name || "Doctor",
      image: normalizeImage(clinicDocs[0].doctor_blob_url || clinicDocs[0].doctor_image_blob_url || clinicDocs[0].doctor_image),
      specialization: formatSpecialization(clinicDocs[0].specialization_select_all_that_apply),
      degree: clinicDocs[0].degree || "BVSc",
      experience: clinicDocs[0].years_of_experience || 5,
      feeDay: clinicPrice,
      feeNight: clinicPrice,
      clinicId: clinic.id || clinic.clinic_id,
      clinicName: clinic.name
    } : {
      id: `clinic-vet-${clinic.id}`,
      name: clinic.name || "Clinic Vet",
      degree: "BVSc",
      experience: 5,
      clinicId: clinic.id || clinic.clinic_id,
      clinicName: clinic.name,
      feeDay: clinicPrice,
      feeNight: clinicPrice
    };

    setSelectedDoctor(firstDoc);
    const todayStr = getUpcomingDates(7)[0].dateStr;
    fetchDateAvailabilityAndSlots(todayStr, firstDoc, clinic);
    setFlowStep("describe");
  };

  const handleBookNowClick = (doc) => {
    setSelectedDoctor(doc);
    if (orderType === "appointment") {
      fetchDateAvailabilityAndSlots(getUpcomingDates(7)[0].dateStr, doc);
    }
    setFlowStep("describe");
  };

  const handleLockSlotAndCheckout = async () => {
    const docIdToUse = resolvedDoctorId || selectedDoctor?.id;
    if (orderType === "appointment" && (!selectedDate || !selectedTimeSlot || !docIdToUse)) return;

    // Talk to Vet requires at least 1 photo attachment and disclaimer acceptance
    if (orderType !== "appointment") {
      if (attachedImages.length === 0) {
        setError("At least one image is required to continue.");
        return;
      }
      if (!disclaimerAccepted) {
        setError("Please check the agreement box before continuing to payment.");
        return;
      }
    }

    setProcessing(true);
    setError("");

    try {
      if (orderType === "appointment") {
        const res = await fetch(`${API_BASE}/doctors/${docIdToUse}/slots/lock`, {
          method: "POST",
          headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
          body: JSON.stringify({ date: selectedDate, time_slot: selectedTimeSlot })
        });
        if (res.ok) {
          const data = await res.json();
          setLockId(data.lockId || data.data?.lockId || data.lock_id);
        }
      }
      setFlowStep("checkout");
    } catch (err) {
      setFlowStep("checkout");
    } finally {
      setProcessing(false);
    }
  };

  const handlePayment = async () => {
    const userId = user.id || user.user_id || authState?.user_id || authState?.userId || 1179;
    const petId = pet?.id || pet?.pet_id || 0;
    const docIdToUse = resolvedDoctorId || selectedDoctor?.id;
    const clinicIdToUse = selectedClinic?.id || selectedDoctor?.clinicId || docIdToUse;
    
    // Live Time-based Price calculation at payment instant
    const liveFee = selectedDoctor ? getDoctorCurrentPrice(selectedDoctor) : (selectedClinic ? Number(getClinicCurrentPrice(selectedClinic)) : 499);
    const liveGst = Math.round(liveFee * GST_RATE);
    const liveTotal = liveFee + liveGst;

    if (orderType === "appointment" && (!selectedDate || !selectedTimeSlot)) {
      setError("Please select date and time slot first.");
      return;
    }

    setProcessing(true);
    setError("");

    if (orderType === "appointment" && paymentPreference === "pay_at_clinic") {
      try {
        await fetch(`${API_BASE}/appointments/submit`, {
          method: "POST",
          headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
          body: JSON.stringify({ user_id: userId, clinic_id: clinicIdToUse, doctor_id: docIdToUse, pet_id: petId, date: selectedDate, time_slot: selectedTimeSlot, amount: liveTotal, payment_method: "pay_at_clinic", lock_id: lockId })
        });
        if (lockId) unlockCurrentSlot(lockId);
        setSuccess(true);
        alert("Visit Confirmed! You can pay ₹" + liveTotal + " at the clinic reception.");
        onClose?.();
      } catch (err) {
        setError("Booking failed");
      } finally {
        setProcessing(false);
      }
      return;
    }

    try {
      const orderPayload = {
        amount: liveTotal,
        order_type: orderType || "video_consult",
        user_id: userId,
        doctor_id: docIdToUse,
        clinic_id: clinicIdToUse,
        pet_id: petId,
        gst_enabled: gstInvoiceChecked ? 1 : 0,
        gst_amount: liveGst,
        base_amount: liveFee,
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

      const paymentResult = await new Promise((resolve) => {
        const rzp = new window.Razorpay({
          key: razorpayKey,
          amount: liveTotal * 100,
          currency: "INR",
          name: "SnoutIQ",
          description: `${orderType === "appointment" ? "Clinic Visit" : "Video Consult"} with ${selectedDoctor.name}`,
          order_id: orderId,
          prefill: { name: user.name || user.owner_name, contact: user.mobile || user.phone },
          theme: { color: "#0052FF" },
          handler: (response) => resolve(response),
        });
        rzp.open();
      });

      await fetch(`${API_BASE}/rzp/verify`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify({ ...paymentResult, user_id: userId, doctor_id: docIdToUse, pet_id: petId, order_type: orderType || "video_consult" })
      });

      if (orderType === "appointment") {
        await fetch(`${API_BASE}/appointments/submit`, {
          method: "POST",
          headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
          body: JSON.stringify({ user_id: userId, clinic_id: clinicIdToUse, doctor_id: docIdToUse, pet_id: petId, date: selectedDate, time_slot: selectedTimeSlot, amount: liveTotal, ...paymentResult, lock_id: lockId })
        });
      }

      if (lockId) unlockCurrentSlot(lockId);
      setSuccess(true);
      alert("Payment successful & consultation confirmed!");
      onClose?.();
    } catch (err) {
      console.error("Payment error", err);
      setError(err.message || "Payment failed");
    } finally {
      setProcessing(false);
    }
  };

  const renderDoctorCard = (doc, isTrusted = false) => {
    const isOnline = doc.status === "available" || doc.available;
    const displayPrice = getDoctorCurrentPrice(doc);

    return (
      <div key={doc.id || doc.doctor_id} className={`bg-white border rounded-2xl p-3 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-2 relative ${isTrusted ? 'border-emerald-300 ring-1 ring-emerald-400/40 bg-emerald-50/10' : 'border-slate-200/90'}`}>
        <div className="flex items-start gap-2.5">
          {/* Left Doctor Avatar */}
          <div className="relative w-14 h-14 rounded-2xl bg-[#e8f2fe] flex-shrink-0 border border-slate-200/80">
            {doc.image ? (
              <img src={doc.image} alt={doc.name} className="w-full h-full object-cover rounded-2xl" />
            ) : (
              <div className="w-full h-full bg-[#e8f2fe] text-[#0066cc] font-extrabold flex items-center justify-center text-xs rounded-2xl">
                DR
              </div>
            )}
            {isOnline && (
              <span className="absolute bottom-0 right-0 w-3 h-3 bg-[#00c853] rounded-full border-2 border-white" title="Online now" />
            )}
          </div>

          {/* Right Details */}
          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between gap-1 flex-wrap">
              <div>
                <div className="flex items-center gap-1.5">
                  <h3 className="font-bold text-slate-900 text-xs leading-tight truncate">{doc.name}</h3>
                  {isTrusted && (
                    <span className="bg-emerald-100 text-emerald-800 text-[9px] font-extrabold px-1.5 py-0.2 rounded-full border border-emerald-200">
                      Trusted
                    </span>
                  )}
                </div>
                
                {/* Experience Badge */}
                <p className="text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-md inline-block mt-0.5">
                  {doc.degree || "BVSc"} · {doc.experience || 5} yrs exp
                </p>
              </div>
              
              {/* Amber Google Rating Badge */}
              <span className="bg-amber-50 text-amber-900 font-bold text-[10px] px-1.5 py-0.5 rounded-md border border-amber-200/70 flex items-center gap-0.5 flex-shrink-0">
                ⭐ {doc.googleRating || 5.0} <span className="text-amber-700 font-medium">({doc.googleReviewCount || 50})</span>
              </span>
            </div>

            {/* Specialization Line */}
            <p className="text-[10px] text-slate-500 line-clamp-1 mt-0.5">
              {doc.specialization}
            </p>

            {/* Online Status Line */}
            <p className="text-[10px] font-medium text-emerald-600 mt-0.5 flex items-center gap-1">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
              <span>{isOnline ? "Online now" : "Available"} - Connects in {doc.responseTimeDay || "0 To 15 Mins"}</span>
            </p>
          </div>
        </div>

        {/* Bottom Row */}
        <div className="flex items-center justify-between border-t border-slate-100 pt-2">
          <div className="text-slate-900 font-extrabold text-xs">
            ₹{displayPrice}<span className="text-[10px] font-normal text-slate-400">/Consult</span>
          </div>

          <div className="flex items-center gap-1.5">
            <button
              onClick={() => setViewProfileDoctor(doc)}
              className="px-3 py-1 border border-blue-200 text-blue-600 hover:bg-blue-50 text-[11px] font-bold rounded-full transition-all"
            >
              View Profile
            </button>
            <button
              onClick={() => handleBookNowClick(doc)}
              className="px-3.5 py-1 bg-[#0052FF] hover:bg-[#0046DB] text-white text-[11px] font-bold rounded-full transition-all shadow-xs"
            >
              Talk to Vet
            </button>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="fixed inset-0 z-[100] flex flex-col bg-slate-100 w-full min-h-screen overflow-hidden animate-[slideInRight_0.25s_cubic-bezier(0.16,1,0.3,1)]">
      
      {/* Full Page Mobile / App Style Top Header Bar */}
      <div className="sticky top-0 z-30 flex items-center justify-between px-3.5 py-2.5 bg-white border-b border-slate-200 shadow-xs">
        <div className="flex items-center gap-2.5">
          <button 
            onClick={() => {
              if (flowStep === "describe") setFlowStep("list");
              else if (flowStep === "checkout") setFlowStep("describe");
              else handleModalClose();
            }} 
            className="p-1.5 -ml-1 text-slate-700 hover:text-black bg-slate-100 hover:bg-slate-200 rounded-full transition-colors"
          >
            <ChevronLeft className="w-4 h-4" />
          </button>
          <div>
            <h1 className="text-sm font-bold text-slate-900 leading-tight">
              {flowStep === "checkout" 
                ? 'Confirm Consultation' 
                : flowStep === "describe" 
                  ? (orderType === "appointment" ? 'Clinic Visit Details' : 'Describe Pet Symptoms')
                  : (orderType === "appointment" ? 'Trusted Veterinary Clinics' : 'Talk to Verified Vets')}
            </h1>
            <p className="text-[10px] text-slate-500 font-medium">
              {orderType === "appointment" ? "In-clinic appointment booking" : "Online video consultation"}
            </p>
          </div>
        </div>

        <button onClick={handleModalClose} className="p-1.5 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors text-slate-600">
          <X className="w-4 h-4" />
        </button>
      </div>

      {/* Main Content View Body */}
      <div ref={scrollContainerRef} className="flex-1 overflow-y-auto bg-slate-50 p-3 md:p-4 max-w-4xl mx-auto w-full space-y-3">
        
        {/* STEP 0: List View */}
        {flowStep === "list" && (
          <div className="space-y-3">
            
            {/* Search Bar + Experience Filter */}
            <div className="flex items-center gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                <input 
                  type="text" 
                  placeholder={orderType === "appointment" ? "Search clinics by name, city..." : "Search doctors by name, specialization..."}
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded-xl py-2 pl-9 pr-3 text-[11px] outline-none focus:border-blue-600 transition-colors shadow-xs"
                />
              </div>

              {orderType !== "appointment" && (
                <button
                  onClick={() => setShowFilterModal(true)}
                  className={`px-3 py-2 rounded-xl border text-[11px] font-bold flex items-center gap-1.5 transition-all shadow-xs ${
                    selectedExpFilter !== "any" 
                      ? "bg-blue-600 text-white border-blue-600" 
                      : "bg-white text-slate-700 border-slate-200 hover:bg-slate-50"
                  }`}
                >
                  <Filter className="w-3.5 h-3.5" />
                  <span>{selectedExpFilter !== "any" ? `${selectedExpFilter}+ Yrs` : "Filter"}</span>
                </button>
              )}
            </div>

            {/* Content List: 2-COLUMN GRID ON WEBSITE VIEW */}
            {loading ? (
              <div className="py-12 text-center text-xs text-slate-500 flex flex-col items-center justify-center gap-2">
                <Loader2 className="w-4 h-4 animate-spin text-blue-600" />
                <span>Loading verified vets & clinics...</span>
              </div>
            ) : orderType === "appointment" ? (
              /* PART 2: CLINICS LIST WITH LAST-VET AND LAZY LOADING */
              (() => {
                const renderClinicCard = (clinic, isTrustedClinic = false) => {
                  const feeVal = getClinicCurrentPrice(clinic);
                  const imgUrl = clinic.clinic_image_url || clinic.clinic_image || "https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=600&q=80";
                  const doctorsCount = clinic.doctors_count || (Array.isArray(clinic.doctors) ? clinic.doctors.length : 1);
                  const isTrusted = isTrustedClinic || (clinic.google_rating || 5.0) >= 4.5;

                  return (
                    <div key={clinic.id || clinic.name} className={`bg-white border rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col justify-between ${isTrustedClinic ? 'border-emerald-300 ring-1 ring-emerald-400/40' : 'border-slate-200/90'}`}>
                      <div className="relative h-32 w-full bg-slate-800">
                        <img src={imgUrl} alt={clinic.name} className="w-full h-full object-cover" />
                        {isTrusted && (
                          <span className="absolute top-2.5 right-2.5 bg-white/90 backdrop-blur-md px-2.5 py-0.5 rounded-full text-[10px] font-bold text-slate-800 shadow-sm flex items-center gap-1">
                            ★ Trust
                          </span>
                        )}
                        {isTrustedClinic && (
                          <span className="absolute top-2.5 left-2.5 bg-emerald-500 text-white text-[9px] font-extrabold px-2 py-0.5 rounded-full shadow-sm">
                            Your Clinic
                          </span>
                        )}
                      </div>
                      
                      <div className="p-3 flex-1 flex flex-col justify-between space-y-2">
                        <div>
                          <div className="flex items-start justify-between gap-1.5">
                            <div>
                              <h3 className="font-bold text-slate-900 text-xs leading-snug line-clamp-1">{clinic.name}</h3>
                              <p className="text-[11px] text-slate-500 mt-0.5 line-clamp-1">
                                📍 {clinic.address || clinic.city || "Gurugram"}{clinic.pincode ? `, ${clinic.pincode}` : ""}
                              </p>
                            </div>
                            <span className="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-full text-[11px] font-bold flex-shrink-0">
                              ₹{feeVal}
                            </span>
                          </div>

                          <div className="flex items-center gap-1.5 mt-2 text-[10px] flex-wrap">
                            <span className="bg-amber-50 text-amber-900 border border-amber-200/80 font-bold px-2 py-0.5 rounded-md flex items-center gap-1">
                              ⭐ {clinic.google_rating || 5.0} <span className="text-amber-700 font-medium">({clinic.google_user_ratings_total || 50})</span>
                            </span>
                            <span className="bg-blue-50 text-blue-700 font-semibold px-2 py-0.5 rounded-md">
                              👤 {doctorsCount} Vet{doctorsCount > 1 ? "s" : ""}
                            </span>
                          </div>
                        </div>

                        <div className="pt-2 flex items-center justify-between border-t border-slate-100">
                          <span className="text-[10px] text-slate-400 font-medium">In-clinic visit</span>
                          <button 
                            onClick={() => handleSelectClinic(clinic)}
                            className="bg-[#0052FF] hover:bg-[#0046DB] text-white font-bold text-[11px] px-3.5 py-1.5 rounded-full transition-all shadow-xs flex items-center gap-1"
                          >
                            Book Visit →
                          </button>
                        </div>
                      </div>
                    </div>
                  );
                };

                return hasLastClinic ? (
                  <div className="space-y-3">
                    {/* ⭐ Your Trusted Clinic Section */}
                    <div className="space-y-2">
                      <div className="flex items-center justify-between px-0.5">
                        <h3 className="text-xs font-extrabold text-slate-900 flex items-center gap-1.5">
                          <span>⭐ Your Trusted Clinic</span>
                          <span className="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">
                            Recommended for {displayPetName}
                          </span>
                        </h3>
                      </div>

                      {filteredLastVetClinics.length === 0 ? (
                        <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                          No trusted clinics match your search.
                        </div>
                      ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          {filteredLastVetClinics.map(clinic => renderClinicCard(clinic, true))}
                        </div>
                      )}
                    </div>

                    {/* View More Clinics Button */}
                    {!showAllClinics && (
                      <div className="pt-1 text-center">
                        <button
                          onClick={handleViewMoreClinicsClick}
                          disabled={allClinicsLoading}
                          className="w-full py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs rounded-xl border border-blue-200 transition-all flex items-center justify-center gap-2 shadow-xs"
                        >
                          {allClinicsLoading ? (
                            <>
                              <Loader2 className="w-3.5 h-3.5 animate-spin text-blue-600" />
                              <span>Loading more clinics for {displayPetName}...</span>
                            </>
                          ) : (
                            <span>View more clinics for {displayPetName} ↓</span>
                          )}
                        </button>
                      </div>
                    )}

                    {/* Other Available Clinics Section */}
                    {showAllClinics && (
                      <div className="space-y-2 pt-2 border-t border-slate-200/80">
                        <h3 className="text-xs font-bold text-slate-900 px-0.5">Other Nearby Clinics</h3>
                        {allClinicsLoading ? (
                          <div className="py-8 text-center text-xs text-slate-500 flex flex-col items-center justify-center gap-2">
                            <Loader2 className="w-4 h-4 animate-spin text-blue-600" />
                            <span>Loading available clinics...</span>
                          </div>
                        ) : filteredOtherClinics.length === 0 ? (
                          <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                            No other clinics found matching your search.
                          </div>
                        ) : (
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {filteredOtherClinics.map(clinic => renderClinicCard(clinic, false))}
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                ) : (
                  /* CASE B: hasLastClinic === false -> DIRECT FULL CLINIC LIST */
                  filteredOtherClinics.length === 0 ? (
                    <div className="p-5 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">No clinics found matching your search.</div>
                  ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                      {filteredOtherClinics.map(clinic => renderClinicCard(clinic, false))}
                    </div>
                  )
                );
              })()
            ) : (
              /* PART 1: DOCTORS LIST WITH LAST-VET AND LAZY LOADING */
              hasLastVet ? (
                <div className="space-y-3">
                  {/* ⭐ Your Trusted Vet Section */}
                  <div className="space-y-2">
                    <div className="flex items-center justify-between px-0.5">
                      <h3 className="text-xs font-extrabold text-slate-900 flex items-center gap-1.5">
                        <span>⭐ Your Trusted Vet</span>
                        <span className="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">
                          Recommended for {displayPetName}
                        </span>
                      </h3>
                    </div>

                    {filteredLastVetDoctors.length === 0 ? (
                      <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                        No trusted vets match your search query.
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                        {filteredLastVetDoctors.map(doc => renderDoctorCard(doc, true))}
                      </div>
                    )}
                  </div>

                  {/* View More Vets Button */}
                  {!showAllVets && (
                    <div className="pt-1 text-center">
                      <button
                        onClick={handleViewMoreClick}
                        disabled={allVetsLoading}
                        className="w-full py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs rounded-xl border border-blue-200 transition-all flex items-center justify-center gap-2 shadow-xs"
                      >
                        {allVetsLoading ? (
                          <>
                            <Loader2 className="w-3.5 h-3.5 animate-spin text-blue-600" />
                            <span>Loading more vets for {displayPetName}...</span>
                          </>
                        ) : (
                          <span>View more vets for {displayPetName} ↓</span>
                        )}
                      </button>
                    </div>
                  )}

                  {/* Other Available Vets Section */}
                  {showAllVets && (
                    <div className="space-y-2 pt-2 border-t border-slate-200/80">
                      <h3 className="text-xs font-bold text-slate-900 px-0.5">Other Available Vets</h3>
                      {allVetsLoading ? (
                        <div className="py-8 text-center text-xs text-slate-500 flex flex-col items-center justify-center gap-2">
                          <Loader2 className="w-4 h-4 animate-spin text-blue-600" />
                          <span>Loading available vets...</span>
                        </div>
                      ) : filteredOtherDoctors.length === 0 ? (
                        <div className="p-4 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">
                          No other doctors found matching filters.
                        </div>
                      ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                          {filteredOtherDoctors.map(doc => renderDoctorCard(doc, false))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              ) : (
                /* CASE B: hasLastVet === false -> DIRECT NORMAL DOCTOR LIST */
                filteredOtherDoctors.length === 0 ? (
                  <div className="p-5 text-center bg-white rounded-xl border border-slate-200 text-slate-500 text-xs">No doctors found matching filters.</div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    {filteredOtherDoctors.map(doc => renderDoctorCard(doc, false))}
                  </div>
                )
              )
            )}
          </div>
        )}

        {/* STEP 1: Describe Issue / Date Slots */}
        {flowStep === "describe" && (
          <div className="space-y-2.5 max-w-xl mx-auto">
            
            {/* Header info */}
            {orderType === "appointment" ? (
              <div className="bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between shadow-xs">
                <div>
                  <h3 className="font-bold text-slate-900 text-xs">{selectedClinic?.name || selectedDoctor?.clinicName || "Clinic"}</h3>
                  <p className="text-[11px] text-slate-500 mt-0.5">{selectedClinic?.city || "Gurugram"}{selectedClinic?.pincode ? `, ${selectedClinic.pincode}` : ""}</p>
                  <span className="inline-block mt-1 bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px] px-2 py-0.5 rounded-md">
                    ★ {selectedClinic?.google_rating || 5.0} ({selectedClinic?.google_user_ratings_total || 78})
                  </span>
                </div>
                <div className="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 text-lg font-bold flex-shrink-0">
                  🏥
                </div>
              </div>
            ) : (
              <div className="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3 shadow-xs">
                {selectedDoctor?.image ? (
                  <img src={selectedDoctor.image} alt={selectedDoctor.name} className="w-12 h-12 rounded-xl object-cover border border-slate-200 flex-shrink-0" />
                ) : (
                  <div className="w-12 h-12 rounded-xl bg-slate-900 text-white font-bold flex items-center justify-center text-sm flex-shrink-0">
                    {selectedDoctor?.name?.charAt(0)}
                  </div>
                )}
                <div>
                  <h3 className="font-bold text-slate-900 text-xs">{selectedDoctor?.name}</h3>
                  <p className="text-[11px] font-semibold text-blue-600 mt-0.5">{selectedDoctor?.degree} · {selectedDoctor?.experience} Yrs Exp</p>
                  <p className="text-[10px] text-slate-500 mt-0.5">{selectedDoctor?.specialization}</p>
                </div>
              </div>
            )}

            {/* Symptom Input Textarea Card */}
            <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-2 shadow-xs">
              <div className="flex items-center justify-between">
                <label className="block text-[11px] font-bold text-slate-900">What issue is your pet facing?</label>
                <span className="text-[10px] text-slate-400 font-medium">Keep it short and clear</span>
              </div>

              <textarea
                value={issueText}
                onChange={(e) => setIssueText(e.target.value)}
                maxLength={500}
                placeholder="Example: Vomiting since morning, not eating, low energy..."
                className="w-full h-20 bg-slate-50/40 border border-slate-200 rounded-xl p-2.5 text-[11px] outline-none focus:border-blue-600 shadow-xs resize-none"
              />
              <p className="text-[10px] text-slate-400 text-left">{issueText.length}/500</p>
            </div>

            {/* Photo Upload Attachment Card */}
            <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-2 shadow-xs">
              <div className="flex items-center justify-between gap-2">
                <div>
                  <h4 className="text-[11px] font-bold text-slate-900">Add a photo</h4>
                  <p className="text-[10px] text-slate-400">Upload a clear image of the issue.</p>
                </div>
                
                <label className="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] px-3 py-1.5 rounded-lg shadow-xs transition-all inline-flex items-center gap-1 flex-shrink-0">
                  <span className="text-xs">☁</span> Add Photo
                  <input 
                    type="file" 
                    accept="image/*" 
                    multiple
                    onChange={handleImageUpload} 
                    className="hidden" 
                  />
                </label>
              </div>

              <p className="text-[10px] text-slate-400">
                {orderType !== "appointment" ? "At least one image is required to continue." : "Photo attachment is optional for clinic visit."}
              </p>

              {attachedImages.length > 0 && (
                <div className="flex gap-2 overflow-x-auto no-scrollbar pt-1">
                  {attachedImages.map(img => (
                    <div key={img.id} className="relative w-12 h-12 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0 group">
                      <img src={img.src} alt="Preview" className="w-full h-full object-cover" />
                      <button
                        type="button"
                        onClick={() => removeAttachedImage(img.id)}
                        className="absolute top-0.5 right-0.5 bg-black/70 hover:bg-black text-white p-0.5 rounded-full transition-colors"
                      >
                        <X size={10} />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Red Light Guidance Alert Box (Only for Talk to Vet) */}
            {orderType !== "appointment" && (
              <div className="bg-red-50/80 border border-red-100 rounded-xl p-3 space-y-1.5 text-[11px] text-red-800 shadow-xs">
                <div className="flex items-start gap-2">
                  <span className="text-red-500 font-bold text-xs leading-none mt-0.5">•</span>
                  <span>Share clear symptoms and at least one photo for faster review.</span>
                </div>
                <div className="flex items-start gap-2">
                  <span className="text-red-500 font-bold text-xs leading-none mt-0.5">•</span>
                  <span>Online consultation is for guidance. Emergency cases may still need a clinic visit.</span>
                </div>
                <div className="flex items-start gap-2">
                  <span className="text-red-500 font-bold text-xs leading-none mt-0.5">•</span>
                  <span>Consultation starts after payment confirmation and doctor assignment.</span>
                </div>
              </div>
            )}

            {/* Agreement Checkbox Container (Only for Talk to Vet) */}
            {orderType !== "appointment" && (
              <label className="flex items-start gap-2.5 bg-white border border-slate-200 rounded-xl p-3 cursor-pointer hover:border-slate-300 transition-all shadow-xs">
                <input 
                  type="checkbox"
                  checked={disclaimerAccepted}
                  onChange={(e) => setDisclaimerAccepted(e.target.checked)}
                  className="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 mt-0.5 flex-shrink-0"
                />
                <span className="text-[11px] text-slate-700 font-medium leading-tight">
                  I understand online consultation is for guidance. Emergency cases may need a clinic visit.
                </span>
              </label>
            )}

            {/* APPOINTMENT DATE/SLOTS */}
            {orderType === "appointment" && (
              <>
                {/* STATIONED MEDICAL STAFF */}
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">STATIONED MEDICAL STAFF</span>
                  <div className="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                    {(selectedClinic?.doctors && selectedClinic.doctors.length > 0 ? selectedClinic.doctors : [selectedDoctor]).map(doc => {
                      const dName = doc.doctor_name || doc.name || "Doctor";
                      const isSel = selectedDoctor?.id === doc.id || selectedDoctor?.id === doc.doctor_id;
                      const avatarUrl = normalizeImage(doc.doctor_blob_url || doc.doctor_image_blob_url || doc.doctor_image_url || doc.image);

                      return (
                        <div 
                          key={doc.id || doc.doctor_id} 
                          onClick={() => {
                            const formattedDoc = {
                              id: doc.id || doc.doctor_id,
                              name: dName,
                              degree: doc.degree || "BVSc",
                              experience: doc.years_of_experience || 5,
                              clinicId: selectedClinic?.id,
                              clinicName: selectedClinic?.name
                            };
                            setSelectedDoctor(formattedDoc);
                            fetchDateAvailabilityAndSlots(selectedDate || getUpcomingDates(7)[0].dateStr, formattedDoc, selectedClinic);
                          }}
                          className={`p-2 rounded-xl border text-center cursor-pointer transition-all flex flex-col items-center justify-center min-w-[96px] ${
                            isSel ? "border-blue-600 bg-blue-50/60 shadow-xs" : "border-slate-200 bg-white hover:border-slate-300"
                          }`}
                        >
                          {avatarUrl ? (
                            <img src={avatarUrl} alt={dName} className="w-10 h-10 rounded-full object-cover mb-1 border border-slate-200" />
                          ) : (
                            <div className="w-10 h-10 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center mb-1">
                              {dName.charAt(0)}
                            </div>
                          )}
                          <p className="text-[11px] font-bold text-slate-900 leading-tight truncate max-w-[85px]">{dName}</p>
                          <p className="text-[9px] text-slate-400 mt-0.5">Doctor</p>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* VISIT DATE */}
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">VISIT DATE</span>
                  <div className="flex gap-2 overflow-x-auto no-scrollbar pb-1 snap-x snap-mandatory">
                    {getUpcomingDates(30).slice(0, 5).map(d => {
                      const isSel = selectedDate === d.dateStr;
                      const today = new Date(); today.setHours(0,0,0,0);
                      const dDate = new Date(d.dateStr); dDate.setHours(0,0,0,0);
                      const isPast = dDate < today;
                      return (
                        <button
                          key={d.dateStr}
                          disabled={isPast}
                          onClick={() => !isPast && fetchDateAvailabilityAndSlots(d.dateStr, selectedDoctor, selectedClinic)}
                          className={`flex-shrink-0 snap-start w-14 py-2 px-1.5 rounded-xl border text-center transition-all ${
                            isPast
                              ? "border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed opacity-50"
                              : isSel
                                ? "border-blue-600 bg-blue-600 text-white font-bold shadow-xs"
                                : "border-slate-200 bg-white text-slate-700 hover:border-blue-300"
                          }`}
                        >
                          <p className={`text-[9px] uppercase font-semibold ${isSel ? "text-blue-100" : isPast ? "text-slate-300" : "text-slate-400"}`}>{d.dayName}</p>
                          <p className="text-xs font-bold mt-0.5">{d.dateNum}</p>
                          <p className={`text-[9px] ${isSel ? "text-blue-100" : isPast ? "text-slate-300" : "text-slate-400"}`}>{d.monthName}</p>
                        </button>
                      );
                    })}

                    {/* More Dates Button */}
                    <button
                      onClick={() => {
                        const picker = document.getElementById("hidden-date-picker");
                        if (picker) picker.showPicker ? picker.showPicker() : picker.click();
                      }}
                      className="flex-shrink-0 w-14 py-2 px-1 rounded-xl border border-dashed border-blue-300 bg-blue-50 text-blue-600 text-center hover:bg-blue-100 transition-all snap-start relative"
                    >
                      <p className="text-[9px] font-bold leading-tight">More</p>
                      <p className="text-[9px] font-bold">Dates</p>
                      <p className="text-[10px] mt-0.5">📅</p>
                      <input
                        id="hidden-date-picker"
                        type="date"
                        min={new Date().toISOString().split("T")[0]}
                        value={selectedDate && !getUpcomingDates(5).some(d => d.dateStr === selectedDate) ? selectedDate : ""}
                        onChange={(e) => {
                          const val = e.target.value;
                          if (val) fetchDateAvailabilityAndSlots(val, selectedDoctor, selectedClinic);
                        }}
                        className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                      />
                    </button>
                  </div>

                  {/* Show selected date if it's beyond first 5 */}
                  {selectedDate && !getUpcomingDates(5).some(d => d.dateStr === selectedDate) && (
                    <div className="mt-1.5 flex items-center gap-1.5">
                      <span className="text-[10px] text-slate-500">Selected:</span>
                      <span className="text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md">
                        {new Date(selectedDate).toLocaleDateString("en-IN", { weekday: "short", day: "numeric", month: "short" })}
                      </span>
                      <button onClick={() => { setSelectedDate(""); setSelectedTimeSlot(""); setAvailableSlots([]); }} className="text-[10px] text-red-400 hover:text-red-600">✕</button>
                    </div>
                  )}
                </div>

                {/* AVAILABLE SLOTS */}
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">AVAILABLE SLOTS</span>
                  {loadingSlots ? (
                    <div className="py-4 text-center text-xs text-slate-500 flex items-center justify-center gap-1.5">
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-blue-600" />
                      Loading slots...
                    </div>
                  ) : availableSlots.length === 0 ? (
                    <div className="p-3 text-center bg-white border border-slate-200 rounded-xl text-xs text-slate-500">
                      {dateAvailError || "No active slots available for this date. Please select another date."}
                    </div>
                  ) : (
                    <div className="grid grid-cols-3 gap-1.5 max-h-40 overflow-y-auto pr-1">
                      {availableSlots.map((s, idx) => {
                        const slotLabel = s.label || s.start;
                        const isSel = selectedTimeSlot === slotLabel;
                        return (
                          <button
                            key={idx}
                            onClick={() => setSelectedTimeSlot(slotLabel)}
                            className={`py-1.5 px-2 rounded-lg text-[11px] font-semibold border transition-all ${
                              isSel ? "border-blue-600 bg-blue-50 text-blue-700 font-bold ring-1 ring-blue-600" : "border-slate-200 bg-white text-slate-700 hover:border-slate-300"
                            }`}
                          >
                            {slotLabel}
                          </button>
                        );
                      })}
                    </div>
                  )}
                </div>
              </>
            )}

            {error && (
              <div className="p-2.5 bg-red-50 border border-red-200 text-red-600 text-xs font-semibold rounded-xl flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{error}</span>
              </div>
            )}

            {/* STEP 1 NEXT BUTTON */}
            <button
              disabled={
                processing || 
                (orderType === "appointment" && (!selectedDate || !selectedTimeSlot)) ||
                (orderType !== "appointment" && (!disclaimerAccepted || attachedImages.length === 0))
              }
              onClick={handleLockSlotAndCheckout}
              className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl disabled:opacity-40 disabled:bg-slate-200 disabled:text-slate-400 shadow-sm transition-all flex items-center justify-center gap-1.5 mt-3"
            >
              {processing ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : "Continue to payment >"}
            </button>

          </div>
        )}

        {/* STEP 2: Checkout */}
        {flowStep === "checkout" && (
          <div className="space-y-2.5 max-w-xl mx-auto">

            {/* COMPACT POINT-WISE SUMMARY CARD */}
            <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xs space-y-2 text-xs">
              <div className="flex items-center justify-between border-b border-slate-100 pb-1.5">
                <h3 className="font-extrabold text-slate-900 text-[11px] uppercase tracking-wider text-blue-600">Booking Summary</h3>
                <span className="bg-blue-50 text-blue-700 font-bold px-2 py-0.5 rounded-full text-[10px] border border-blue-100">
                  {orderType === "appointment" ? "In-Clinic Visit" : "Video Consultation"}
                </span>
              </div>

              {/* Compact 2-Column Point Grid (Less Space, No Heavy Scrolling) */}
              <div className="grid grid-cols-2 gap-2">
                {/* Doctor Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <p className="text-[9px] uppercase font-bold text-slate-400">Doctor</p>
                  <p className="font-extrabold text-slate-900 text-xs truncate">{selectedDoctor?.name || "Vet Doctor"}</p>
                  <p className="text-[10px] text-slate-500 truncate">{selectedDoctor?.degree || "BVSc"}</p>
                </div>

                {/* Pet Parent (User) Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <p className="text-[9px] uppercase font-bold text-slate-400">Pet Parent</p>
                  <p className="font-extrabold text-slate-900 text-xs truncate">{displayUserName}</p>
                  <p className="text-[10px] text-slate-500 truncate">📞 {displayUserMobile}</p>
                </div>

                {/* Pet Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <p className="text-[9px] uppercase font-bold text-slate-400">Pet</p>
                  <p className="font-extrabold text-slate-900 text-xs truncate">🐾 {displayPetName}</p>
                  <p className="text-[10px] text-slate-500 truncate">{displayPetBreed}</p>
                </div>

                {/* Schedule / Consult Mode Info */}
                <div className="bg-slate-50/70 p-2 rounded-lg border border-slate-100">
                  <p className="text-[9px] uppercase font-bold text-slate-400">
                    {orderType === "appointment" ? "Visit Schedule" : "Consult Mode"}
                  </p>
                  {orderType === "appointment" ? (
                    <>
                      <p className="font-extrabold text-slate-900 text-xs truncate">📅 {selectedDate}</p>
                      <p className="text-[10px] text-blue-700 font-bold truncate">⏰ {selectedTimeSlot}</p>
                    </>
                  ) : (
                    <>
                      <p className="font-extrabold text-slate-900 text-xs truncate">⚡ Instant Video</p>
                      <p className="text-[10px] text-emerald-700 font-bold truncate">Connects in 0-15m</p>
                    </>
                  )}
                </div>
              </div>
            </div>

            {/* SECURE CHECKOUT */}
            <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-1.5 shadow-xs text-xs">
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">SECURE CHECKOUT</span>
              <div className="flex justify-between py-0.5 text-slate-700">
                <span>Consultation Fee ({isDayTimeNow() ? "Day Rate" : "Night Rate"})</span>
                <span className="font-bold text-slate-900">₹{currentFee}</span>
              </div>
              <div className="flex justify-between py-0.5 text-slate-700">
                <span>Taxes & GST (18%)</span>
                <span className="font-bold text-slate-900">₹{gstAmount}</span>
              </div>
              <div className="flex justify-between py-1.5 border-t border-slate-100 font-extrabold text-xs text-slate-900">
                <span>Total payable</span>
                <span className="text-blue-700 text-sm">₹{totalAmount}</span>
              </div>
            </div>

            {/* GST Invoice */}
            <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs">
              <label className="flex items-start gap-2 cursor-pointer">
                <input 
                  type="checkbox" 
                  checked={gstInvoiceChecked}
                  onChange={(e) => setGstInvoiceChecked(e.target.checked)}
                  className="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 mt-0.5" 
                />
                <div>
                  <p className="text-xs font-bold text-slate-800">GST Invoice</p>
                  <p className="text-[10px] text-slate-400">Need GST invoice - Add GST details for business billing</p>
                </div>
              </label>
              {gstInvoiceChecked && (
                <input
                  type="text"
                  maxLength={15}
                  value={gstNumber}
                  onChange={(e) => setGstNumber(e.target.value.toUpperCase().replace(/\s+/g, ""))}
                  placeholder="Enter 15-digit GST number"
                  className="mt-2 w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs uppercase focus:border-blue-500 outline-none"
                />
              )}
            </div>

            {/* PAYMENT PREFERENCE (Only for In-Clinic Flow) */}
            {orderType === "appointment" && (
              <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xs space-y-2">
                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">PAYMENT PREFERENCE</span>
                
                <label className={`flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition-all ${
                  paymentPreference === "pay_online" ? "border-blue-600 bg-blue-50/50" : "border-slate-200 hover:border-slate-300"
                }`}>
                  <input 
                    type="radio" 
                    name="pay_pref" 
                    checked={paymentPreference === "pay_online"}
                    onChange={() => setPaymentPreference("pay_online")}
                    className="mt-0.5 text-blue-600 focus:ring-blue-500" 
                  />
                  <div>
                    <p className="text-xs font-bold text-slate-900">Pay Online</p>
                    <p className="text-[10px] text-slate-500">Secure UPI, card or netbanking. Instant booking confirmation.</p>
                  </div>
                </label>

                <label className={`flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition-all ${
                  paymentPreference === "pay_at_clinic" ? "border-blue-600 bg-blue-50/50" : "border-slate-200 hover:border-slate-300"
                }`}>
                  <input 
                    type="radio" 
                    name="pay_pref" 
                    checked={paymentPreference === "pay_at_clinic"}
                    onChange={() => setPaymentPreference("pay_at_clinic")}
                    className="mt-0.5 text-blue-600 focus:ring-blue-500" 
                  />
                  <div>
                    <p className="text-xs font-bold text-slate-900">Pay at Clinic</p>
                    <p className="text-[10px] text-slate-500">Confirm your visit now and pay directly at the clinic reception.</p>
                  </div>
                </label>
              </div>
            )}

            {error && (
              <div className="p-2.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600">
                {error}
              </div>
            )}

            {/* FINAL ACTION BUTTON */}
            <button
              disabled={processing}
              onClick={handlePayment}
              className="w-full py-3 bg-gradient-to-r from-sky-600 to-cyan-500 text-white font-extrabold text-xs rounded-xl shadow-md hover:opacity-95 transition-all flex items-center justify-center gap-1.5"
            >
              {processing ? (
                <Loader2 className="w-3.5 h-3.5 animate-spin" />
              ) : (orderType === "appointment" && paymentPreference === "pay_at_clinic") ? (
                `Confirm Visit ₹${totalAmount} →`
              ) : (
                `Pay ₹${totalAmount} & Book →`
              )}
            </button>

          </div>
        )}

      </div>

      {/* FILTER MODAL */}
      {showFilterModal && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 backdrop-blur-xs p-3 animate-[fadeIn_0.15s_ease-out]" onClick={() => setShowFilterModal(false)}>
          <div className="bg-white w-full max-w-xs rounded-2xl p-4 shadow-2xl space-y-3 animate-[scaleInUp_0.2s_ease-out]" onClick={e => e.stopPropagation()}>
            <div className="flex items-center justify-between border-b border-slate-100 pb-2">
              <h3 className="text-xs font-bold text-slate-900">Filter Vets by Experience</h3>
              <button onClick={() => setShowFilterModal(false)} className="p-1 bg-slate-100 hover:bg-slate-200 rounded-full text-slate-600">
                <X size={14} />
              </button>
            </div>

            <div className="space-y-1.5">
              {[
                { label: "Any Experience", val: "any" },
                { label: "1+ Years", val: "1" },
                { label: "3+ Years", val: "3" },
                { label: "5+ Years", val: "5" },
                { label: "10+ Years", val: "10" }
              ].map(opt => (
                <button
                  key={opt.val}
                  onClick={() => {
                    setSelectedExpFilter(opt.val);
                    setShowFilterModal(false);
                  }}
                  className={`w-full text-left px-3 py-2 rounded-lg text-xs font-semibold transition-all border ${
                    selectedExpFilter === opt.val
                      ? "border-blue-600 bg-blue-50 text-blue-700 font-bold"
                      : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                  }`}
                >
                  {opt.label}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* VIEW PROFILE MODAL — RAZORPAY UI STYLE */}
      {viewProfileDoctor && (
        <div className="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-3 animate-[fadeIn_0.15s_ease-out]" onClick={() => setViewProfileDoctor(null)}>
          <div className="bg-white w-full max-w-sm rounded-2xl overflow-hidden shadow-2xl border border-slate-200 animate-[scaleInUp_0.2s_ease-out]" onClick={e => e.stopPropagation()}>
            
            {/* Razorpay Signature Style Navy Top Banner Header */}
            <div className="bg-[#0c2340] text-white px-4 py-3 flex items-center justify-between">
              <div className="flex items-center gap-2">
                <div className="w-6 h-6 rounded-md bg-blue-500/20 border border-blue-400/40 flex items-center justify-center">
                  <Shield className="w-3.5 h-3.5 text-blue-400" />
                </div>
                <div>
                  <h3 className="text-[10px] font-extrabold uppercase tracking-wider text-slate-300">Verified Doctor Profile</h3>
                  <p className="text-xs font-bold text-white leading-tight">{viewProfileDoctor.name}</p>
                </div>
              </div>

              <button onClick={() => setViewProfileDoctor(null)} className="p-1 bg-white/10 hover:bg-white/20 rounded-full transition-colors text-white">
                <X className="w-3.5 h-3.5" />
              </button>
            </div>

            {/* Body Info in Razorpay Card Layout */}
            <div className="p-3 max-h-[70vh] overflow-y-auto space-y-2.5 bg-slate-50/50">
              
              {/* Doctor Avatar Card */}
              <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xs flex items-center gap-3">
                <div className="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                  {viewProfileDoctor.image ? (
                    <img src={viewProfileDoctor.image} alt={viewProfileDoctor.name} className="w-full h-full object-cover" />
                  ) : (
                    <div className="w-full h-full bg-slate-900 text-white font-bold flex items-center justify-center text-sm">
                      {viewProfileDoctor.name.charAt(0)}
                    </div>
                  )}
                </div>
                <div>
                  <div className="flex items-center gap-1">
                    <h4 className="font-extrabold text-slate-900 text-xs">{viewProfileDoctor.name}</h4>
                    <span className="bg-blue-50 text-blue-700 text-[9px] font-extrabold px-1.5 py-0.5 rounded-full flex items-center gap-0.5 border border-blue-200">
                      <Check size={8} /> Verified
                    </span>
                  </div>
                  <p className="text-[11px] font-semibold text-blue-600 mt-0.5">
                    {viewProfileDoctor.degree} · {viewProfileDoctor.experience} Yrs Exp
                  </p>
                  {viewProfileDoctor.googleRating !== null && (
                    <span className="inline-flex items-center gap-1 mt-0.5 bg-amber-50 text-amber-900 border border-amber-200 px-1.5 py-0.5 rounded-md text-[10px] font-bold">
                      ⭐ {viewProfileDoctor.googleRating} ({viewProfileDoctor.googleReviewCount})
                    </span>
                  )}
                </div>
              </div>

              {/* LIVE PRICING BREAKDOWN CARD */}
              <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs space-y-1 text-xs">
                <div className="flex items-center justify-between">
                  <span className="text-slate-500 font-medium text-[11px]">Consultation Mode</span>
                  <span className="font-bold text-slate-900 text-[11px]">Video Consultation</span>
                </div>
                <div className="flex items-center justify-between border-t border-slate-100 pt-1">
                  <span className="text-slate-500 font-medium text-[10px]">
                    Current Rate ({isDayTimeNow() ? "Day Rate" : "Night Rate"})
                  </span>
                  <span className="font-extrabold text-emerald-700 text-xs">
                    ₹{getDoctorCurrentPrice(viewProfileDoctor)}
                  </span>
                </div>
              </div>

              {/* Specialization */}
              <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs space-y-1.5">
                <h5 className="text-[9px] font-bold uppercase tracking-wider text-slate-400">SPECIALIZATIONS</h5>
                <div className="flex flex-wrap gap-1">
                  {viewProfileDoctor.specialization.split(",").map((s, idx) => (
                    <span key={idx} className="bg-slate-100 text-slate-700 text-[10px] font-medium px-2 py-0.5 rounded-md">
                      {s.trim()}
                    </span>
                  ))}
                </div>
              </div>

              {/* Doctor Bio */}
              {viewProfileDoctor.bio && (
                <div className="bg-white border border-slate-200 rounded-xl p-2.5 shadow-xs space-y-1">
                  <h5 className="text-[9px] font-bold uppercase tracking-wider text-slate-400">ABOUT DOCTOR</h5>
                  <p className="text-[11px] text-slate-700 leading-snug whitespace-pre-line">
                    {viewProfileDoctor.bio}
                  </p>
                </div>
              )}

              {/* Response Time & Follow-up */}
              <div className="grid grid-cols-2 gap-2 text-xs">
                <div className="bg-emerald-50/70 border border-emerald-200/70 rounded-lg p-2">
                  <p className="text-[9px] font-bold text-emerald-800 uppercase tracking-wider">Day Response</p>
                  <p className="font-extrabold text-emerald-900 mt-0.5 text-[11px]">{viewProfileDoctor.responseTimeDay || "0 To 15 Mins"}</p>
                </div>
                <div className="bg-blue-50/70 border border-blue-200/70 rounded-lg p-2">
                  <p className="text-[9px] font-bold text-blue-800 uppercase tracking-wider">Night Response</p>
                  <p className="font-extrabold text-blue-900 mt-0.5 text-[11px]">{viewProfileDoctor.responseTimeNight || "15 To 20 Mins"}</p>
                </div>
              </div>

              {viewProfileDoctor.followUpPolicy && (
                <div className="bg-purple-50/70 border border-purple-200/70 rounded-lg p-2 text-[11px] text-purple-900 font-semibold flex items-center gap-1.5">
                  <Shield className="w-3.5 h-3.5 text-purple-600 flex-shrink-0" />
                  <span>{viewProfileDoctor.followUpPolicy}</span>
                </div>
              )}

            </div>

            {/* Razorpay Signature Bottom CTA Button */}
            <div className="p-3 bg-white border-t border-slate-200">
              <button
                onClick={() => {
                  const doc = viewProfileDoctor;
                  setViewProfileDoctor(null);
                  handleBookNowClick(doc);
                }}
                className="w-full py-2.5 bg-[#0052FF] hover:bg-[#0046DB] text-white font-bold text-xs rounded-xl transition-all shadow-sm flex items-center justify-center gap-1.5"
              >
                Proceed to Book (₹{getDoctorCurrentPrice(viewProfileDoctor)}) →
              </button>
            </div>

          </div>
        </div>
      )}

    </div>
  );
}
