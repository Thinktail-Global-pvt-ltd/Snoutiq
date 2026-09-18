import React, { useState, useEffect, useCallback, useMemo, useRef } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import {
  CalendarDays,
  Video,
  Building2,
  Clock,
  User,
  MapPin,
  FileText,
  ChevronRight,
  ChevronDown,
  RefreshCw,
  AlertCircle,
  CheckCircle2,
  XCircle,
  ArrowLeft,
  Calendar,
  Pill,
  X,
  Stethoscope,
  Receipt,
  Search,
  Filter,
  RotateCcw,
  Sparkles,
  Dog,
  Cat,
  Check,
  Loader2,
} from "lucide-react";
import { readAiAuthState } from "../ai/AiAuth";

const API_BASE = "https://snoutiq.com/backend/api";

const QUICK_DATE_RANGES = [
  { key: "all", label: "All Dates" },
  { key: "today", label: "Today" },
  { key: "this_week", label: "This Week" },
  { key: "this_month", label: "This Month" },
  { key: "upcoming", label: "Upcoming" },
  { key: "past", label: "Past" },
];

export default function MyAppointmentsPage() {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();

  // Primary Toggle: 'video_call' or 'in_clinic'
  const initialTab = useMemo(() => {
    const rawTab = (searchParams.get("tab") || searchParams.get("type") || "").toLowerCase();
    if (rawTab === "in_clinic" || rawTab === "appointment" || rawTab === "clinic" || rawTab === "inclinic") {
      return "in_clinic";
    }
    return "video_call";
  }, [searchParams]);

  const [activeTab, setActiveTab] = useState(initialTab);

  // Sync activeTab if URL query params change
  useEffect(() => {
    const rawTab = (searchParams.get("tab") || searchParams.get("type") || "").toLowerCase();
    if (rawTab === "in_clinic" || rawTab === "appointment" || rawTab === "clinic" || rawTab === "inclinic") {
      setActiveTab("in_clinic");
    } else if (rawTab === "video_call" || rawTab === "video_consult" || rawTab === "videocall" || rawTab === "video") {
      setActiveTab("video_call");
    }
  }, [searchParams]);

  // Auth State
  const [authState, setAuthState] = useState(() => readAiAuthState());

  useEffect(() => {
    const handleAuthChange = () => {
      const fresh = readAiAuthState();
      setAuthState(fresh);
    };
    window.addEventListener("snoutiq_auth_changed", handleAuthChange);
    window.addEventListener("snoutiq_pet_changed", handleAuthChange);
    window.addEventListener("storage", handleAuthChange);
    return () => {
      window.removeEventListener("snoutiq_auth_changed", handleAuthChange);
      window.removeEventListener("snoutiq_pet_changed", handleAuthChange);
      window.removeEventListener("storage", handleAuthChange);
    };
  }, []);

  // Pet selection initialized with the active pet from SymptomCheckerFlow / AiAuth
  const [selectedPetId, setSelectedPetId] = useState(() => {
    const currentAuth = readAiAuthState();
    const curUser = currentAuth?.user || {};
    const directId = curUser.pet_id || curUser.pet?.id || curUser.pet?.pet_id;
    if (directId) return String(directId);
    if (Array.isArray(curUser.pets) && curUser.pets.length > 0) {
      return String(curUser.pets[0].id || curUser.pets[0].pet_id || "");
    }
    return "";
  });
  const [isPetModalOpen, setIsPetModalOpen] = useState(false);

  // Filter modal state
  const [isFilterModalOpen, setIsFilterModalOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [filterStatus, setFilterStatus] = useState("all"); // 'all' | 'upcoming' | 'completed' | 'pending' | 'cancelled'
  const [dateRangeKey, setDateRangeKey] = useState("all");
  const [customDate, setCustomDate] = useState(""); // YYYY-MM-DD

  // Data states
  const [inClinicList, setInClinicList] = useState([]);
  const [videoConsultList, setVideoConsultList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [downloadingInvoiceId, setDownloadingInvoiceId] = useState(null);

  // Prescription modal state
  const [selectedPrescription, setSelectedPrescription] = useState(null);

  // Cancel & Reschedule Modal states
  const [cancelModalItem, setCancelModalItem] = useState(null);
  const [cancelReason, setCancelReason] = useState("Change of plans / Schedule conflict");
  const [cancellingLoading, setCancellingLoading] = useState(false);
  const [cancelError, setCancelError] = useState("");

  const [rescheduleModalItem, setRescheduleModalItem] = useState(null);
  const [rescheduleDate, setRescheduleDate] = useState("");
  const [rescheduleTimeSlot, setRescheduleTimeSlot] = useState("10:00 AM");
  const [reschedulingLoading, setReschedulingLoading] = useState(false);
  const [rescheduleError, setRescheduleError] = useState("");

  const [actionToast, setActionToast] = useState("");

  useEffect(() => {
    if (actionToast) {
      const timer = setTimeout(() => setActionToast(""), 4000);
      return () => clearTimeout(timer);
    }
  }, [actionToast]);

  // Check if an appointment is eligible for Cancel / Reschedule (>= 2h cutoff)
  const canCancelOrReschedule = useCallback((appointment) => {
    if (!appointment) return false;
    const status = String(appointment.status || "").toLowerCase().trim();
    if (["completed", "cancelled", "canceled", "rejected", "failed", "refunded"].includes(status)) {
      return false;
    }
    const appDate = appointment.date || appointment.appointment_date || appointment.created_at;
    const appTime = appointment.time_slot || appointment.time || "10:00:00";
    if (!appDate) return true;

    try {
      const rawDateStr = String(appDate).split("T")[0];
      const firstPart = String(appTime).split("-")[0]?.trim() || "10:00:00";
      const amPmMatch = firstPart.match(/^(\d{1,2})(?::(\d{2}))?\s*(AM|PM)$/i);
      let hours = 10;
      let minutes = 0;
      if (amPmMatch) {
        hours = Number(amPmMatch[1]);
        minutes = Number(amPmMatch[2] || 0);
        if (amPmMatch[3].toUpperCase() === "PM" && hours !== 12) hours += 12;
        if (amPmMatch[3].toUpperCase() === "AM" && hours === 12) hours = 0;
      } else if (firstPart.includes(":")) {
        const parts = firstPart.split(":").map(Number);
        hours = parts[0] || 10;
        minutes = parts[1] || 0;
      }
      const ymd = rawDateStr.split("-").map(Number);
      if (ymd.length === 3) {
        const appDateTime = new Date(ymd[0], ymd[1] - 1, ymd[2], hours, minutes);
        const diffHours = (appDateTime.getTime() - Date.now()) / (1000 * 60 * 60);
        return diffHours >= 2;
      }
    } catch (_) {}
    return true;
  }, []);

  // Cancel Appointment Action Handler
  const handleConfirmCancel = async () => {
    if (!cancelModalItem) return;
    setCancellingLoading(true);
    setCancelError("");
    const apptId = cancelModalItem.id || cancelModalItem.appointment_id || cancelModalItem.transaction_id;
    try {
      const headers = {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      };
      const payload = {
        user_id: userId ? Number(userId) : undefined,
        reason: cancelReason,
        comments: cancelReason,
        cancelled_at: new Date().toISOString(),
      };

      let res = await fetch(`${API_BASE}/appointments/${encodeURIComponent(apptId)}/cancel`, {
        method: "POST",
        headers,
        body: JSON.stringify(payload),
      }).catch(() => null);

      let data = null;
      if (res) {
        data = await res.json().catch(() => null);
      }

      if (!res || !res.ok) {
        if (!data || res?.status === 404) {
          const fallbackRes = await fetch(`${API_BASE}/bookings/${encodeURIComponent(apptId)}/cancel`, {
            method: "POST",
            headers,
            body: JSON.stringify(payload),
          }).catch(() => null);

          if (fallbackRes) {
            const fallbackData = await fallbackRes.json().catch(() => null);
            if (fallbackRes.ok && fallbackData?.success !== false) {
              res = fallbackRes;
              data = fallbackData;
            } else if (fallbackData?.message) {
              throw new Error(fallbackData.message);
            }
          }
        }

        if (!res || !res.ok) {
          throw new Error(data?.message || data?.error || "Failed to cancel appointment. Please try again.");
        }
      }

      if (data?.success === false) {
        throw new Error(data?.message || "Cancellation was not confirmed by the server.");
      }

      // Optimistically update local lists
      setInClinicList((prev) =>
        prev.map((it) =>
          String(it.id) === String(apptId) || String(it.transaction_id) === String(apptId)
            ? { ...it, status: "cancelled" }
            : it
        )
      );
      setVideoConsultList((prev) =>
        prev.map((it) =>
          String(it.id) === String(apptId) || String(it.transaction_id) === String(apptId)
            ? { ...it, status: "cancelled" }
            : it
        )
      );

      setCancelModalItem(null);
      setActionToast("Appointment cancelled successfully.");
    } catch (err) {
      setCancelError(err?.message || "Failed to cancel appointment. Please try again.");
    } finally {
      setCancellingLoading(false);
    }
  };

  // Reschedule Appointment Action Handler
  const handleConfirmReschedule = async () => {
    if (!rescheduleModalItem) return;
    if (!rescheduleDate || !rescheduleTimeSlot) {
      setRescheduleError("Please select a date and time slot.");
      return;
    }
    setReschedulingLoading(true);
    setRescheduleError("");
    const apptId = rescheduleModalItem.id || rescheduleModalItem.appointment_id || rescheduleModalItem.transaction_id;
    try {
      const headers = {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      };
      const payload = {
        user_id: userId ? Number(userId) : undefined,
        new_date: rescheduleDate,
        new_time_slot: rescheduleTimeSlot,
        date: rescheduleDate,
        time_slot: rescheduleTimeSlot,
        reason: "User requested new time slot",
      };

      let res = await fetch(`${API_BASE}/appointments/${encodeURIComponent(apptId)}/reschedule`, {
        method: "POST",
        headers,
        body: JSON.stringify(payload),
      }).catch(() => null);

      let data = null;
      if (res) {
        data = await res.json().catch(() => null);
      }

      if (!res || !res.ok) {
        if (!data || res?.status === 404) {
          const fallbackRes = await fetch(`${API_BASE}/bookings/${encodeURIComponent(apptId)}/reschedule`, {
            method: "POST",
            headers,
            body: JSON.stringify(payload),
          }).catch(() => null);

          if (fallbackRes) {
            const fallbackData = await fallbackRes.json().catch(() => null);
            if (fallbackRes.ok && fallbackData?.success !== false) {
              res = fallbackRes;
              data = fallbackData;
            } else if (fallbackData?.message) {
              throw new Error(fallbackData.message);
            }
          }
        }

        if (!res || !res.ok) {
          throw new Error(data?.message || data?.error || "Failed to reschedule appointment. Please try again.");
        }
      }

      if (data?.success === false) {
        throw new Error(data?.message || "Rescheduling was not confirmed by the server.");
      }

      // Optimistically update local state
      setInClinicList((prev) =>
        prev.map((it) =>
          String(it.id) === String(apptId) || String(it.transaction_id) === String(apptId)
            ? { ...it, date: rescheduleDate, appointment_date: rescheduleDate, time_slot: rescheduleTimeSlot, time: rescheduleTimeSlot }
            : it
        )
      );

      setRescheduleModalItem(null);
      setActionToast("Appointment rescheduled successfully.");
    } catch (err) {
      setRescheduleError(err?.message || "Failed to reschedule appointment. Please try again.");
    } finally {
      setReschedulingLoading(false);
    }
  };

  const token =
    authState?.token ||
    localStorage.getItem("ai_auth_token") ||
    localStorage.getItem("token") ||
    "";
  const userId =
    authState?.user?.id ||
    authState?.user?.userId ||
    authState?.user?.user_id ||
    localStorage.getItem("ai_auth_user_id") ||
    localStorage.getItem("user_id") ||
    "";

  // Normalize pets list for Pet Filter
  const user = authState?.user || {};
  const petsList = useMemo(() => {
    const rawPets = Array.isArray(user.pets) && user.pets.length > 0
      ? user.pets
      : user.pet
      ? [user.pet]
      : [];

    const map = new Map();
    rawPets.forEach((p) => {
      if (!p) return;
      const key = String(p.id || p.pet_id || p.name || p.pet_name || "");
      if (key && !map.has(key)) {
        map.set(key, p);
      }
    });
    return Array.from(map.values()).filter((p) => p && (p.name || p.pet_name));
  }, [user]);

  // Synchronize default selected pet with SymptomCheckerFlow / AiAuth active pet
  useEffect(() => {
    if (petsList.length > 0) {
      const activePetId = user.pet_id || user.pet?.id || user.pet?.pet_id;
      if (activePetId && petsList.some((p) => String(p.id || p.pet_id) === String(activePetId))) {
        setSelectedPetId((prev) => {
          if (!prev || (!petsList.some((p) => String(p.id || p.pet_id) === String(prev)) && prev !== "all")) {
            return String(activePetId);
          }
          return prev;
        });
      } else {
        setSelectedPetId((prev) => {
          if (!prev || (!petsList.some((p) => String(p.id || p.pet_id) === String(prev)) && prev !== "all")) {
            return String(petsList[0]?.id || petsList[0]?.pet_id || "");
          }
          return prev;
        });
      }
    }
  }, [petsList, user]);

  // Handler for booking flow modal trigger (same as sidebar)
  const handleOpenBookingFlow = (orderType) => {
    navigate(`/doctor-booking?type=${orderType}`);
  };

  // Download Invoice Handler
  const handleDownloadInvoice = async (item) => {
    const transactionId = item.id || item.transaction_id;
    if (!transactionId) return;

    setDownloadingInvoiceId(transactionId);
    try {
      const url = `https://snoutiq.com/backend/captured-transactions/${encodeURIComponent(transactionId)}/invoice?download=1`;
      const headers = {
        Accept: "application/pdf",
      };
      if (token) {
        headers["Authorization"] = `Bearer ${token}`;
      }
      const res = await fetch(url, { headers });
      if (!res.ok) {
        throw new Error(`Failed to download invoice (${res.status})`);
      }
      const blob = await res.blob();
      const blobUrl = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = blobUrl;
      link.download = `invoice_tx_${transactionId}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(blobUrl);
    } catch (err) {
      console.warn("Direct blob invoice download failed, opening in new tab:", err);
      window.open(
        `https://snoutiq.com/backend/captured-transactions/${encodeURIComponent(transactionId)}/invoice?download=1`,
        "_blank"
      );
    } finally {
      setDownloadingInvoiceId(null);
    }
  };

  // Fetch In-Clinic Appointments
  const fetchInClinicAppointments = useCallback(async (uid, authToken) => {
    if (!uid) return [];
    try {
      const headers = {
        "Content-Type": "application/json",
        Accept: "application/json",
      };
      if (authToken) {
        headers["Authorization"] = `Bearer ${authToken}`;
      }

      const res = await fetch(`${API_BASE}/appointments/by-user/${encodeURIComponent(uid)}`, {
        headers,
      });
      if (!res.ok) throw new Error("Failed to fetch clinic appointments");
      const data = await res.json();
      const list = Array.isArray(data)
        ? data
        : Array.isArray(data?.appointments)
        ? data.appointments
        : Array.isArray(data?.data)
        ? data.data
        : [];
      return list;
    } catch (err) {
      console.warn("Error fetching in-clinic appointments:", err);
      return [];
    }
  }, []);

  // Fetch Video Call Consultations
  const fetchVideoConsultations = useCallback(async (uid, petId, authToken) => {
    if (!uid) return [];
    try {
      const headers = {
        Accept: "application/json",
      };
      if (authToken) {
        headers["Authorization"] = `Bearer ${authToken}`;
      }

      let url = `${API_BASE}/transactions/by-user?user_id=${encodeURIComponent(uid)}&type=video_consult&limit=50`;
      if (petId && petId !== "all") {
        url = `${API_BASE}/transactions/video-consult/by-pet-user?pet_id=${encodeURIComponent(petId)}&user_id=${encodeURIComponent(uid)}`;
      }

      const res = await fetch(url, { headers });
      if (!res.ok) throw new Error("Failed to fetch video call history");
      const data = await res.json();
      const list = Array.isArray(data)
        ? data
        : Array.isArray(data?.transactions)
        ? data.transactions
        : Array.isArray(data?.data)
        ? data.data
        : [];
      return list;
    } catch (err) {
      console.warn("Error fetching video consultations:", err);
      return [];
    }
  }, []);

  const loadAllData = useCallback(
    async (isRefresh = false) => {
      if (!userId) {
        setLoading(false);
        return;
      }
      if (isRefresh) setRefreshing(true);
      else setLoading(true);
      setError(null);

      try {
        const [clinicData, videoData] = await Promise.all([
          fetchInClinicAppointments(userId, token),
          fetchVideoConsultations(userId, null, token),
        ]);
        setInClinicList(clinicData);
        setVideoConsultList(videoData);
      } catch (err) {
        setError(err.message || "Failed to load booking history");
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [userId, token, fetchInClinicAppointments, fetchVideoConsultations]
  );

  useEffect(() => {
    loadAllData();
  }, [loadAllData]);

  // Format date helper
  const formatDate = (dateStr) => {
    if (!dateStr) return "Date not specified";
    try {
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return String(dateStr);
      return d.toLocaleDateString("en-IN", {
        weekday: "short",
        day: "numeric",
        month: "short",
        year: "numeric",
      });
    } catch {
      return String(dateStr);
    }
  };

  const formatTime = (timeStr) => {
    if (!timeStr) return "";
    try {
      if (/AM|PM/i.test(timeStr)) return timeStr;
      if (timeStr.includes(":")) {
        const [hh, mm] = timeStr.split(":");
        const hour = parseInt(hh, 10);
        const ampm = hour >= 12 ? "PM" : "AM";
        const formattedHour = hour % 12 || 12;
        return `${formattedHour}:${mm.slice(0, 2)} ${ampm}`;
      }
      return timeStr;
    } catch {
      return timeStr;
    }
  };

  // Status Normalizers
  const normalizeClinicStatus = (status) => {
    const s = String(status || "").toLowerCase().trim();
    if (s.includes("confirm") || s === "booked" || s === "accepted") {
      return {
        label: "Confirmed",
        color: "bg-emerald-50 text-emerald-700 border-emerald-200",
        icon: CheckCircle2,
      };
    }
    if (s.includes("complete") || s === "done") {
      return {
        label: "Completed",
        color: "bg-blue-50 text-blue-700 border-blue-200",
        icon: CheckCircle2,
      };
    }
    if (s.includes("cancel") || s.includes("reject")) {
      return {
        label: "Cancelled",
        color: "bg-red-50 text-red-700 border-red-200",
        icon: XCircle,
      };
    }
    return {
      label: "Pending",
      color: "bg-amber-50 text-amber-700 border-amber-200",
      icon: Clock,
    };
  };

  const normalizeVideoStatus = (status) => {
    const s = String(status || "").toLowerCase().trim();
    if (s === "captured" || s === "paid" || s === "success" || s === "completed") {
      return {
        label: "Confirmed & Paid",
        color: "bg-emerald-50 text-emerald-700 border-emerald-200",
        icon: CheckCircle2,
      };
    }
    if (s === "pending" || s === "created" || s === "authorized") {
      return {
        label: "Pending Payment",
        color: "bg-amber-50 text-amber-700 border-amber-200",
        icon: Clock,
      };
    }
    if (s.includes("fail") || s.includes("cancel") || s.includes("refund")) {
      return {
        label: "Failed / Cancelled",
        color: "bg-red-50 text-red-700 border-red-200",
        icon: XCircle,
      };
    }
    return {
      label: "Confirmed",
      color: "bg-emerald-50 text-emerald-700 border-emerald-200",
      icon: CheckCircle2,
    };
  };

  // Helper date range matching
  const matchesDateFilter = (rawDateStr) => {
    if (!rawDateStr) return true;
    const itemDate = new Date(rawDateStr);
    if (isNaN(itemDate.getTime())) return true;

    if (customDate) {
      const selected = new Date(customDate);
      return (
        itemDate.getFullYear() === selected.getFullYear() &&
        itemDate.getMonth() === selected.getMonth() &&
        itemDate.getDate() === selected.getDate()
      );
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const itemDay = new Date(itemDate);
    itemDay.setHours(0, 0, 0, 0);

    if (dateRangeKey === "today") {
      return itemDay.getTime() === today.getTime();
    }
    if (dateRangeKey === "upcoming") {
      return itemDay.getTime() >= today.getTime();
    }
    if (dateRangeKey === "past") {
      return itemDay.getTime() < today.getTime();
    }
    if (dateRangeKey === "this_week") {
      const firstDayOfWeek = new Date(today);
      firstDayOfWeek.setDate(today.getDate() - today.getDay());
      const lastDayOfWeek = new Date(firstDayOfWeek);
      lastDayOfWeek.setDate(firstDayOfWeek.getDate() + 6);
      lastDayOfWeek.setHours(23, 59, 59, 999);
      return itemDate >= firstDayOfWeek && itemDate <= lastDayOfWeek;
    }
    if (dateRangeKey === "this_month") {
      return (
        itemDate.getFullYear() === today.getFullYear() &&
        itemDate.getMonth() === today.getMonth()
      );
    }

    return true;
  };

  // Helper pet matching
  const matchesPetFilter = (item) => {
    if (selectedPetId === "all" || !selectedPetId) return true;
    const targetPet = petsList.find((p) => String(p.id || p.pet_id) === String(selectedPetId));
    const targetPetName = (targetPet?.name || targetPet?.pet_name || "").toLowerCase().trim();

    const itemPetId = String(item.pet_id || item.pet?.id || "").trim();
    const itemPetName = String(item.pet_name || item.pet?.name || "").toLowerCase().trim();

    if (itemPetId && String(itemPetId) === String(selectedPetId)) return true;
    if (targetPetName && itemPetName && itemPetName === targetPetName) return true;
    return false;
  };

  // Filtered in-clinic appointments
  const filteredClinicList = useMemo(() => {
    return inClinicList.filter((item) => {
      if (!matchesPetFilter(item)) return false;

      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase().trim();
        const docName = String(item.doctor?.name || item.doctor_name || "").toLowerCase();
        const clinicName = String(item.clinic?.name || item.clinic_name || "").toLowerCase();
        const petName = String(item.pet?.name || item.pet_name || "").toLowerCase();
        const address = String(item.clinic?.address || item.clinic_address || "").toLowerCase();
        if (
          !docName.includes(q) &&
          !clinicName.includes(q) &&
          !petName.includes(q) &&
          !address.includes(q)
        ) {
          return false;
        }
      }

      const rawDate = item.date || item.appointment_date || item.created_at;
      if (!matchesDateFilter(rawDate)) return false;

      if (filterStatus !== "all") {
        const s = String(item.status || "").toLowerCase();
        if (filterStatus === "upcoming") {
          return s.includes("confirm") || s === "booked" || !s;
        }
        if (filterStatus === "completed") {
          return s.includes("complete") || s === "done";
        }
        if (filterStatus === "pending") {
          return s === "pending" || s === "created";
        }
        if (filterStatus === "cancelled") {
          return s.includes("cancel") || s.includes("reject");
        }
      }

      return true;
    });
  }, [inClinicList, searchQuery, filterStatus, dateRangeKey, customDate, selectedPetId, petsList]);

  // Filtered video consultations
  const filteredVideoList = useMemo(() => {
    return videoConsultList.filter((item) => {
      if (!matchesPetFilter(item)) return false;

      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase().trim();
        const docName = String(
          item.doctor_name || item.doctor?.name || item.care_agent_name || ""
        ).toLowerCase();
        const petName = String(item.pet_name || item.pet?.name || "").toLowerCase();
        const notes = String(item.notes || item.symptoms || "").toLowerCase();
        if (!docName.includes(q) && !petName.includes(q) && !notes.includes(q)) {
          return false;
        }
      }

      const rawDate = item.created_at || item.appointment_date || item.date;
      if (!matchesDateFilter(rawDate)) return false;

      if (filterStatus !== "all") {
        const s = String(item.status || "").toLowerCase();
        if (filterStatus === "upcoming") {
          return s === "captured" || s === "paid" || s === "confirmed";
        }
        if (filterStatus === "completed") {
          return s === "completed" || (Array.isArray(item.prescriptions) && item.prescriptions.length > 0);
        }
        if (filterStatus === "pending") {
          return s === "pending" || s === "created" || s === "authorized";
        }
        if (filterStatus === "cancelled") {
          return s.includes("fail") || s.includes("cancel") || s.includes("refund");
        }
      }

      return true;
    });
  }, [videoConsultList, searchQuery, filterStatus, dateRangeKey, customDate, selectedPetId, petsList]);

  // Reset filters
  const resetFilters = () => {
    setSearchQuery("");
    setFilterStatus("all");
    setDateRangeKey("all");
    setCustomDate("");
  };

  const isAnyFilterActive =
    searchQuery.trim() !== "" ||
    filterStatus !== "all" ||
    dateRangeKey !== "all" ||
    customDate !== "";

  // Selected Pet Name helper
  const selectedPetObj =
    selectedPetId === "all"
      ? null
      : petsList.find((p) => String(p.id || p.pet_id) === String(selectedPetId)) ||
        petsList.find((p) => String(p.id || p.pet_id) === String(user.pet_id || user.pet?.id || user.pet?.pet_id)) ||
        petsList[0];

  const selectedPetDisplayName =
    selectedPetId === "all"
      ? "All Pets"
      : selectedPetObj
      ? selectedPetObj.name || selectedPetObj.pet_name
      : "Select Pet";

  return (
    <div className="min-h-screen bg-[#F4F7FB] flex flex-col text-slate-800 antialiased font-sans">
      {/* 1. APP-STYLE COMPACT SCREEN HEADER */}
      <header className="sticky top-0 z-40 bg-white border-b border-slate-200/80 shadow-2xs">
        <div className="max-w-xl mx-auto px-2.5 h-11 sm:h-12 flex items-center justify-between gap-2">
          {/* Left: Back & Heading */}
          <div className="flex items-center gap-2 min-w-0">
            <button
              onClick={() => navigate(-1)}
              className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-colors focus:outline-none shrink-0 cursor-pointer"
              aria-label="Go Back"
            >
              <ArrowLeft className="w-3.5 h-3.5 text-slate-800" />
            </button>
            <h1 className="text-xs sm:text-sm font-bold text-slate-900 tracking-tight truncate">
              Appointments
            </h1>
          </div>

          {/* Right: Pet Filter & Filter Icon */}
          <div className="flex items-center gap-1.5 shrink-0">
            {/* Pet Filter Button */}
            {petsList.length > 0 && (
              <button
                type="button"
                onClick={() => setIsPetModalOpen(true)}
                className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-slate-200 bg-slate-50 hover:bg-slate-100 text-[10px] font-semibold text-slate-800 transition-colors cursor-pointer"
                title="Filter by Pet"
              >
                <span className="w-3 h-3 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[8px]">
                  {selectedPetId === "all" ? (
                    <Sparkles className="w-2 h-2 text-emerald-600" />
                  ) : String(selectedPetObj?.pet_type || selectedPetObj?.species || "").toLowerCase() === "cat" ? (
                    <Cat className="w-2 h-2" />
                  ) : (
                    <Dog className="w-2 h-2" />
                  )}
                </span>
                <span className="max-w-[65px] sm:max-w-[95px] truncate font-medium text-[10px]">
                  {selectedPetDisplayName}
                </span>
                <ChevronDown className="w-2 h-2 text-slate-400" />
              </button>
            )}

            {/* Filter Button */}
            <button
              type="button"
              onClick={() => setIsFilterModalOpen(true)}
              className={`relative p-1 rounded-lg border transition-all ${
                isAnyFilterActive
                  ? "bg-[#309BD8]/10 text-[#309BD8] border-[#309BD8]/30 shadow-2xs"
                  : "bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100"
              }`}
              title="Filter"
            >
              <Filter className="w-3.5 h-3.5" />
              {isAnyFilterActive && (
                <span className="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 rounded-full bg-[#309BD8] ring-1 ring-white" />
              )}
            </button>

            {/* Refresh Button */}
            <button
              type="button"
              onClick={() => loadAllData(true)}
              disabled={refreshing || loading}
              className="p-1 rounded-lg bg-slate-50 border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50"
              title="Refresh"
            >
              <RefreshCw
                className={`w-3.5 h-3.5 text-slate-600 ${refreshing ? "animate-spin" : ""}`}
              />
            </button>
          </div>
        </div>
      </header>

      {/* MAIN CONTENT CONTAINER */}
      <main className="flex-1 max-w-xl w-full mx-auto px-2.5 py-2 space-y-2">
        {/* Not Logged In Notice */}
        {!userId && !loading && (
          <div className="bg-white rounded-xl p-4 border border-amber-200 text-center shadow-2xs max-w-xs mx-auto my-4">
            <div className="w-9 h-9 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-1.5 border border-amber-200">
              <User className="w-4 h-4" />
            </div>
            <h3 className="text-xs sm:text-sm font-bold text-slate-900 mb-0.5">
              Sign In to View Appointments
            </h3>
            <p className="text-slate-600 text-[10px] mb-3">
              Please sign in with your phone number to access your clinic visits and video calls.
            </p>
            <Link
              to="/auth"
              className="inline-flex items-center justify-center px-3.5 py-1.5 rounded-lg bg-[#309BD8] text-white font-semibold text-[11px] hover:bg-[#2887bc] shadow-sm transition-all"
            >
              Sign In / Register
            </Link>
          </div>
        )}

        {userId && (
          <>
            {/* Action Feedback Toast */}
            {actionToast && (
              <div className="bg-emerald-50 border border-emerald-300 text-emerald-900 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in duration-150">
                <span className="flex items-center gap-1.5">
                  <CheckCircle2 size={13} className="text-emerald-600 shrink-0" />
                  <span>{actionToast}</span>
                </span>
                <button
                  type="button"
                  onClick={() => setActionToast("")}
                  className="text-emerald-700 hover:text-emerald-950 p-0.5"
                >
                  <X size={12} />
                </button>
              </div>
            )}

            {/* 2. DUAL TOGGLE: VIDEOCALL vs IN CLINIC */}
            <div className="bg-white p-0.5 rounded-xl shadow-2xs border border-slate-200/90">
              <div className="grid grid-cols-2 gap-1">
                <button
                  type="button"
                  onClick={() => setActiveTab("video_call")}
                  className={`flex items-center justify-center gap-1 py-1 px-2 rounded-lg font-bold text-[11px] transition-all cursor-pointer ${
                    activeTab === "video_call"
                      ? "bg-[#309BD8] text-white shadow-xs"
                      : "text-slate-600 hover:text-slate-900 hover:bg-slate-50"
                  }`}
                >
                  <Video className="w-3 h-3" />
                  <span>Videocall</span>
                  <span
                    className={`text-[9px] px-1 py-0.2 rounded-full font-bold ${
                      activeTab === "video_call"
                        ? "bg-white/25 text-white"
                        : "bg-slate-100 text-slate-600"
                    }`}
                  >
                    {videoConsultList.length}
                  </span>
                </button>

                <button
                  type="button"
                  onClick={() => setActiveTab("in_clinic")}
                  className={`flex items-center justify-center gap-1 py-1 px-2 rounded-lg font-bold text-[11px] transition-all cursor-pointer ${
                    activeTab === "in_clinic"
                      ? "bg-[#309BD8] text-white shadow-xs"
                      : "text-slate-600 hover:text-slate-900 hover:bg-slate-50"
                  }`}
                >
                  <Building2 className="w-3 h-3" />
                  <span>In-Clinic</span>
                  <span
                    className={`text-[9px] px-1 py-0.2 rounded-full font-bold ${
                      activeTab === "in_clinic"
                        ? "bg-white/25 text-white"
                        : "bg-slate-100 text-slate-600"
                    }`}
                  >
                    {inClinicList.length}
                  </span>
                </button>
              </div>
            </div>

            {/* Active Filters Pill Bar */}
            {isAnyFilterActive && (
              <div className="flex items-center gap-1.5 overflow-x-auto pb-0.5 text-[10px] scrollbar-none">
                <span className="text-slate-400 font-medium shrink-0">Active:</span>
                {filterStatus !== "all" && (
                  <span className="inline-flex items-center gap-1 bg-[#309BD8]/10 text-[#309BD8] border border-[#309BD8]/30 px-1.5 py-0.5 rounded-md font-semibold shrink-0">
                    Status: {filterStatus}
                    <X
                      className="w-2.5 h-2.5 cursor-pointer hover:opacity-75"
                      onClick={() => setFilterStatus("all")}
                    />
                  </span>
                )}
                {dateRangeKey !== "all" && (
                  <span className="inline-flex items-center gap-1 bg-[#309BD8]/10 text-[#309BD8] border border-[#309BD8]/30 px-1.5 py-0.5 rounded-md font-semibold shrink-0">
                    {dateRangeKey}
                    <X
                      className="w-2.5 h-2.5 cursor-pointer hover:opacity-75"
                      onClick={() => setDateRangeKey("all")}
                    />
                  </span>
                )}
                {customDate && (
                  <span className="inline-flex items-center gap-1 bg-[#309BD8]/10 text-[#309BD8] border border-[#309BD8]/30 px-1.5 py-0.5 rounded-md font-semibold shrink-0">
                    {customDate}
                    <X
                      className="w-2.5 h-2.5 cursor-pointer hover:opacity-75"
                      onClick={() => setCustomDate("")}
                    />
                  </span>
                )}
                {searchQuery.trim() && (
                  <span className="inline-flex items-center gap-1 bg-slate-100 text-slate-700 border border-slate-200 px-1.5 py-0.5 rounded-md font-semibold shrink-0">
                    "{searchQuery}"
                    <X
                      className="w-2.5 h-2.5 cursor-pointer hover:opacity-75"
                      onClick={() => setSearchQuery("")}
                    />
                  </span>
                )}
                <button
                  type="button"
                  onClick={resetFilters}
                  className="text-red-600 hover:text-red-700 font-bold ml-auto pl-1 underline shrink-0 text-[10px]"
                >
                  Clear
                </button>
              </div>
            )}

            {/* Error Message */}
            {error && (
              <div className="bg-red-50 border border-red-200 rounded-xl p-2.5 flex items-start gap-2">
                <AlertCircle className="w-3.5 h-3.5 text-red-500 shrink-0 mt-0.5" />
                <div className="flex-1">
                  <p className="text-[11px] font-semibold text-red-800">
                    Failed to sync latest appointments
                  </p>
                  <p className="text-[10px] text-red-600">{error}</p>
                </div>
                <button
                  onClick={() => loadAllData(true)}
                  className="text-[11px] font-bold text-red-700 underline shrink-0"
                >
                  Retry
                </button>
              </div>
            )}

            {/* Loading Skeletons */}
            {loading && (
              <div className="space-y-2.5">
                {[1, 2, 3].map((n) => (
                  <div
                    key={n}
                    className="bg-white rounded-xl p-3 border border-slate-200/80 shadow-2xs animate-pulse space-y-2"
                  >
                    <div className="flex gap-2.5">
                      <div className="w-9 h-9 rounded-lg bg-slate-200 shrink-0" />
                      <div className="space-y-1.5 flex-1">
                        <div className="w-32 h-3 bg-slate-200 rounded-md" />
                        <div className="w-20 h-2.5 bg-slate-200 rounded-md" />
                      </div>
                    </div>
                    <div className="w-full h-6 bg-slate-100 rounded-lg" />
                  </div>
                ))}
              </div>
            )}

            {/* 3. LIST VIEW: VIDEOCALL */}
            {!loading && activeTab === "video_call" && (
              <div className="space-y-2.5 animate-in fade-in duration-150">
                {filteredVideoList.length === 0 ? (
                  <div className="bg-white rounded-xl p-6 border border-slate-200/80 text-center shadow-2xs">
                    <div className="w-10 h-10 rounded-full bg-[#309BD8]/10 text-[#309BD8] flex items-center justify-center mx-auto mb-2 border border-[#309BD8]/20">
                      <Video className="w-5 h-5" />
                    </div>
                    <h3 className="text-xs sm:text-sm font-bold text-slate-900 mb-2.5">
                      {isAnyFilterActive
                        ? "No Video Consultations Found"
                        : "No Video Consultations Yet"}
                    </h3>
                    {isAnyFilterActive && (
                      <p className="text-[11px] text-slate-500 max-w-xs mx-auto mb-3">
                        Try resetting your search or filters to see other appointments.
                      </p>
                    )}
                    {isAnyFilterActive ? (
                      <button
                        onClick={resetFilters}
                        className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#081037] text-white font-semibold text-[11px] hover:bg-[#0c1b50] active:scale-95 transition-all duration-150 shadow-2xs"
                      >
                        <RotateCcw className="w-3 h-3" />
                        <span>Clear Filters</span>
                      </button>
                    ) : (
                      <button
                        type="button"
                        onClick={() => handleOpenBookingFlow("video_consult")}
                        className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-[#309BD8] text-white font-semibold text-[11px] hover:bg-[#2887bc] active:scale-95 shadow-sm transition-all duration-150 cursor-pointer"
                      >
                        <Video className="w-3.5 h-3.5" />
                        <span>Talk to Vet</span>
                      </button>
                    )}
                  </div>
                ) : (
                  filteredVideoList.map((item, idx) => {
                    const statusObj = normalizeVideoStatus(item.status);
                    const StatusIcon = statusObj.icon;
                    const doctorName =
                      item.doctor_name ||
                      item.doctor?.name ||
                      item.care_agent_name ||
                      "Licensed Veterinary Doctor";
                    const petName = item.pet_name || item.pet?.name || "Pet";
                    const dateDisplay = formatDate(
                      item.created_at || item.appointment_date || item.date
                    );
                    const amount =
                      item.amount_paise != null
                        ? `₹${Math.round(item.amount_paise / 100)}`
                        : item.amount != null
                        ? `₹${item.amount}`
                        : "₹499";
                    const transactionId = item.id || item.transaction_id;
                    const invoiceUrl = transactionId
                      ? `https://snoutiq.com/backend/captured-transactions/${transactionId}/invoice?download=1`
                      : null;
                    const hasPrescription =
                      Array.isArray(item.prescriptions) &&
                      item.prescriptions.length > 0;

                    return (
                      <div
                        key={item.id || idx}
                        className="bg-white rounded-xl p-3 border border-slate-200/90 shadow-2xs hover:shadow-xs transition-all duration-200 active:scale-[0.99] space-y-2"
                      >
                        {/* Card Header row */}
                        <div className="flex items-start justify-between gap-2">
                          <div className="flex items-start gap-2.5 min-w-0">
                            <div className="relative shrink-0">
                              <div className="w-9 h-9 rounded-lg bg-[#309BD8]/10 text-[#309BD8] flex items-center justify-center border border-[#309BD8]/20">
                                <Video className="w-4 h-4" />
                              </div>
                            </div>

                            <div className="space-y-0.5 min-w-0">
                              <h3 className="text-xs sm:text-[13px] font-bold text-slate-900 truncate">
                                Video Consultation
                              </h3>
                              <p className="text-[11px] font-semibold text-slate-700 flex items-center gap-1 truncate">
                                <Stethoscope className="w-3 h-3 text-[#309BD8] shrink-0" />
                                <span className="truncate">{doctorName}</span>
                              </p>
                              <p className="text-[10px] text-slate-500">
                                Pet: <strong className="text-slate-800 font-semibold">{petName}</strong>
                              </p>
                            </div>
                          </div>

                          <div className="flex flex-col items-end gap-0.5 shrink-0">
                            <span
                              className={`inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold border ${statusObj.color}`}
                            >
                              <StatusIcon className="w-2 h-2" />
                              {statusObj.label}
                            </span>
                            <span className="text-[11px] sm:text-xs font-extrabold text-[#309BD8]">
                              {amount}
                            </span>
                          </div>
                        </div>

                        {/* Date & Ref Row */}
                        <div className="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-100 font-medium">
                          <span className="flex items-center gap-1 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded-md">
                            <CalendarDays className="w-2.5 h-2.5 text-[#309BD8]" />
                            {dateDisplay}
                          </span>
                          {transactionId && (
                            <span className="text-slate-400 text-[9px]">
                              Ref: #{String(transactionId).slice(-6)}
                            </span>
                          )}
                        </div>

                        {/* Card Action Buttons */}
                        <div className="flex items-center gap-1.5 pt-0.5 flex-wrap justify-end">
                          {hasPrescription && (
                            <button
                              type="button"
                              onClick={() => setSelectedPrescription(item.prescriptions[0])}
                              className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#309BD8]/10 text-[#309BD8] border border-[#309BD8]/30 text-[11px] font-bold hover:bg-[#309BD8]/20 active:scale-95 transition-all duration-150 shadow-2xs cursor-pointer"
                            >
                              <FileText className="w-2.5 h-2.5 text-[#309BD8]" />
                              <span>View Prescription</span>
                            </button>
                          )}

                          {transactionId && (
                            <button
                              type="button"
                              onClick={() => handleDownloadInvoice(item)}
                              disabled={downloadingInvoiceId === transactionId}
                              className="inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 active:scale-95 transition-all duration-150 disabled:opacity-50 cursor-pointer"
                              title="Download Invoice"
                            >
                              {downloadingInvoiceId === transactionId ? (
                                <RefreshCw className="w-2.5 h-2.5 text-[#309BD8] animate-spin" />
                              ) : (
                                <Receipt className="w-2.5 h-2.5 text-slate-500" />
                              )}
                              <span>
                                {downloadingInvoiceId === transactionId
                                  ? "Downloading..."
                                  : "Invoice"}
                              </span>
                            </button>
                          )}

                          <button
                            type="button"
                            onClick={() => handleOpenBookingFlow("video_consult")}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#309BD8] text-white text-[11px] font-bold hover:bg-[#2887bc] active:scale-95 transition-all duration-150 shadow-2xs cursor-pointer ml-auto sm:ml-0"
                          >
                            <span>Talk to Vet</span>
                            <ChevronRight className="w-2.5 h-2.5" />
                          </button>
                        </div>

                        {/* Cancel Action if eligible */}
                        {canCancelOrReschedule(item) && (
                          <div className="flex items-center justify-end pt-1 border-t border-slate-100">
                            <button
                              type="button"
                              onClick={() => {
                                setCancelError("");
                                setCancelReason("Change of plans / Schedule conflict");
                                setCancelModalItem(item);
                              }}
                              className="py-1 px-2.5 text-[10px] font-bold text-red-600 hover:bg-red-50 rounded-lg border border-red-200 flex items-center justify-center gap-1 transition-all cursor-pointer"
                            >
                              <X size={11} />
                              <span>Cancel Consultation</span>
                            </button>
                          </div>
                        )}
                      </div>
                    );
                  })
                )}
              </div>
            )}

            {/* 4. LIST VIEW: IN CLINIC */}
            {!loading && activeTab === "in_clinic" && (
              <div className="space-y-2.5 animate-in fade-in duration-150">
                {filteredClinicList.length === 0 ? (
                  <div className="bg-white rounded-xl p-6 border border-slate-200/80 text-center shadow-2xs">
                    <div className="w-10 h-10 rounded-full bg-[#309BD8]/10 text-[#309BD8] flex items-center justify-center mx-auto mb-2 border border-[#309BD8]/20">
                      <Building2 className="w-5 h-5" />
                    </div>
                    <h3 className="text-xs sm:text-sm font-bold text-slate-900 mb-2.5">
                      {isAnyFilterActive
                        ? "No Clinic Visits Found"
                        : "No In-Clinic Visits Yet"}
                    </h3>
                    {isAnyFilterActive && (
                      <p className="text-[11px] text-slate-500 max-w-xs mx-auto mb-3">
                        Try resetting your search or filters to see other bookings.
                      </p>
                    )}
                    {isAnyFilterActive ? (
                      <button
                        onClick={resetFilters}
                        className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#081037] text-white font-semibold text-[11px] hover:bg-[#0c1b50] active:scale-95 transition-all duration-150 shadow-2xs"
                      >
                        <RotateCcw className="w-3 h-3" />
                        <span>Clear Filters</span>
                      </button>
                    ) : (
                      <button
                        type="button"
                        onClick={() => handleOpenBookingFlow("appointment")}
                        className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-[#309BD8] text-white font-semibold text-[11px] hover:bg-[#2887bc] active:scale-95 shadow-sm transition-all duration-150 cursor-pointer"
                      >
                        <Building2 className="w-3.5 h-3.5" />
                        <span>Book Visit</span>
                      </button>
                    )}
                  </div>
                ) : (
                  filteredClinicList.map((item, idx) => {
                    const statusObj = normalizeClinicStatus(item.status);
                    const StatusIcon = statusObj.icon;
                    const doctorName =
                      item.doctor?.name || item.doctor_name || "Veterinary Doctor";
                    const clinicName =
                      item.clinic?.name || item.clinic_name || "SnoutIQ Partner Clinic";
                    const clinicAddress =
                      item.clinic?.address ||
                      item.clinic_address ||
                      item.address ||
                      "";
                    const petName = item.pet?.name || item.pet_name || "Pet";
                    const dateDisplay = formatDate(
                      item.date || item.appointment_date || item.created_at
                    );
                    const timeDisplay = formatTime(item.time_slot || item.time || item.slot);
                    const doctorImg =
                      item.doctor?.doctor_image ||
                      item.doctor_image ||
                      item.clinic?.logo_url;

                    return (
                      <div
                        key={item.id || idx}
                        className="bg-white rounded-xl p-3 border border-slate-200/90 shadow-2xs hover:shadow-xs transition-all duration-200 active:scale-[0.99] space-y-2"
                      >
                        {/* Card Header row */}
                        <div className="flex items-start justify-between gap-2">
                          <div className="flex items-start gap-2.5 min-w-0">
                            <div className="relative shrink-0">
                              {doctorImg ? (
                                <img
                                  src={doctorImg}
                                  alt={doctorName}
                                  loading="lazy"
                                  decoding="async"
                                  className="w-9 h-9 rounded-lg object-cover border border-slate-200"
                                  onError={(e) => {
                                    e.target.style.display = "none";
                                    e.target.nextSibling.style.display = "flex";
                                  }}
                                />
                              ) : null}
                              <div
                                className={`w-9 h-9 rounded-lg bg-[#309BD8]/10 text-[#309BD8] flex items-center justify-center border border-[#309BD8]/20 ${
                                  doctorImg ? "hidden" : "flex"
                                }`}
                              >
                                <Building2 className="w-4 h-4" />
                              </div>
                            </div>

                            <div className="space-y-0.5 min-w-0">
                              <h3 className="text-xs sm:text-[13px] font-bold text-slate-900 truncate">
                                {clinicName}
                              </h3>
                              <p className="text-[11px] font-semibold text-slate-700 flex items-center gap-1 truncate">
                                <Stethoscope className="w-3 h-3 text-[#309BD8] shrink-0" />
                                <span className="truncate">{doctorName}</span>
                              </p>
                              <p className="text-[10px] text-slate-500">
                                Pet: <strong className="text-slate-800 font-semibold">{petName}</strong>
                              </p>
                            </div>
                          </div>

                          <div className="shrink-0">
                            <span
                              className={`inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold border ${statusObj.color}`}
                            >
                              <StatusIcon className="w-2 h-2" />
                              {statusObj.label}
                            </span>
                          </div>
                        </div>

                        {/* Clinic address */}
                        {clinicAddress && (
                          <p className="text-[10px] text-slate-500 flex items-center gap-1 truncate">
                            <MapPin className="w-2.5 h-2.5 text-slate-400 shrink-0" />
                            <span className="truncate">{clinicAddress}</span>
                          </p>
                        )}

                        {/* Date & Time Row */}
                        <div className="flex items-center gap-2 text-[10px] text-slate-600 pt-1 border-t border-slate-100 font-medium flex-wrap">
                          <span className="flex items-center gap-1 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded-md">
                            <CalendarDays className="w-2.5 h-2.5 text-[#309BD8]" />
                            {dateDisplay}
                          </span>
                          {timeDisplay && (
                            <span className="flex items-center gap-1 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded-md">
                              <Clock className="w-2.5 h-2.5 text-[#309BD8]" />
                              {timeDisplay}
                            </span>
                          )}
                        </div>

                        {/* Card Actions */}
                        <div className="flex items-center gap-1.5 pt-0.5 justify-end">
                          {clinicAddress && (
                            <a
                              href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
                                clinicName + " " + clinicAddress
                              )}`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#309BD8] active:scale-95 transition-all duration-150"
                            >
                              <MapPin className="w-2.5 h-2.5 text-[#309BD8]" />
                              <span>Directions</span>
                            </a>
                          )}

                          <button
                            type="button"
                            onClick={() => handleOpenBookingFlow("appointment")}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#081037] text-white text-[11px] font-bold hover:bg-[#0c1b50] active:scale-95 transition-all duration-150 shadow-2xs cursor-pointer"
                          >
                            <span>Book Visit</span>
                            <ChevronRight className="w-2.5 h-2.5" />
                          </button>
                        </div>

                        {/* Cancel & Reschedule Action Bar if eligible */}
                        {canCancelOrReschedule(item) && (
                          <div className="flex items-center gap-2 pt-1 border-t border-slate-100">
                            <button
                              type="button"
                              onClick={() => {
                                const rawDate = item.date || item.appointment_date || new Date().toISOString().split("T")[0];
                                setRescheduleDate(rawDate);
                                setRescheduleTimeSlot(item.time_slot || item.time || "10:00 AM");
                                setRescheduleError("");
                                setRescheduleModalItem(item);
                              }}
                              className="flex-1 py-1.5 px-2 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center gap-1 transition-all cursor-pointer"
                            >
                              <Clock size={12} className="text-slate-500" />
                              <span>Reschedule</span>
                            </button>

                            <button
                              type="button"
                              onClick={() => {
                                setCancelError("");
                                setCancelReason("Change of plans / Schedule conflict");
                                setCancelModalItem(item);
                              }}
                              className="py-1.5 px-3 text-[11px] font-bold text-red-600 hover:bg-red-50 rounded-lg border border-red-200 flex items-center justify-center gap-1 transition-all cursor-pointer"
                            >
                              <X size={12} />
                              <span>Cancel</span>
                            </button>
                          </div>
                        )}
                      </div>
                    );
                  })
                )}
              </div>
            )}
          </>
        )}
      </main>

      {/* PET FILTER BOTTOM SHEET / MODAL */}
      {isPetModalOpen && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-2xs animate-in fade-in duration-100">
          <div className="bg-white rounded-t-2xl sm:rounded-2xl max-w-xs w-full overflow-hidden shadow-xl border border-slate-200 flex flex-col max-h-[75vh]">
            <div className="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
              <div className="flex items-center gap-1.5">
                <Filter className="w-3.5 h-3.5 text-emerald-600" />
                <h3 className="text-xs font-bold text-slate-900">
                  Filter by Pet
                </h3>
              </div>
              <button
                onClick={() => setIsPetModalOpen(false)}
                className="w-6 h-6 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
              >
                <X className="w-3 h-3" />
              </button>
            </div>

            <div className="p-2 space-y-1 overflow-y-auto">
              {/* All Pets option */}
              <button
                type="button"
                onClick={() => {
                  setSelectedPetId("all");
                  setIsPetModalOpen(false);
                }}
                className={`w-full flex items-center justify-between p-2 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer ${
                  selectedPetId === "all"
                    ? "bg-emerald-50 text-emerald-800 border border-emerald-200"
                    : "text-slate-700 hover:bg-slate-50"
                }`}
              >
                <span className="flex items-center gap-2 truncate">
                  <span className="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <Sparkles className="w-3 h-3 text-emerald-600" />
                  </span>
                  <span className="text-left truncate">
                    <span className="block font-bold text-slate-900 truncate text-[11px]">All Pets</span>
                    <span className="text-[9px] text-slate-500 font-normal truncate">Show appointments for all pets</span>
                  </span>
                </span>
                {selectedPetId === "all" && <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />}
              </button>

              {/* Pets list */}
              {petsList.map((pet) => {
                const petId = String(pet.id || pet.pet_id);
                const isSelected = selectedPetId === petId;
                const isCat = String(pet.pet_type || pet.species || "").toLowerCase() === "cat";
                const petName = pet.name || pet.pet_name;
                const petBreed = pet.breed || (isCat ? "Cat" : "Dog");

                return (
                  <button
                    key={petId}
                    type="button"
                    onClick={() => {
                      setSelectedPetId(petId);
                      setIsPetModalOpen(false);
                    }}
                    className={`w-full flex items-center justify-between p-2 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer ${
                      isSelected
                        ? "bg-emerald-50 text-emerald-800 border border-emerald-200"
                        : "text-slate-700 hover:bg-slate-50"
                    }`}
                  >
                    <span className="flex items-center gap-2 truncate">
                      <span className="w-6 h-6 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        {isCat ? <Cat className="w-3 h-3" /> : <Dog className="w-3 h-3" />}
                      </span>
                      <span className="text-left truncate">
                        <span className="block font-bold text-slate-900 truncate text-[11px]">{petName}</span>
                        <span className="text-[9px] text-slate-500 font-normal truncate">{petBreed}</span>
                      </span>
                    </span>
                    {isSelected && <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />}
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* FILTER BOTTOM SHEET / MODAL */}
      {isFilterModalOpen && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-2xs animate-in fade-in duration-100">
          <div className="bg-white rounded-t-2xl sm:rounded-2xl max-w-xs w-full overflow-hidden shadow-xl border border-slate-200 flex flex-col max-h-[80vh]">
            {/* Modal Header */}
            <div className="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
              <div className="flex items-center gap-1.5">
                <Filter className="w-3.5 h-3.5 text-[#309BD8]" />
                <h3 className="text-xs font-bold text-slate-900">
                  Filter Appointments
                </h3>
              </div>
              <button
                onClick={() => setIsFilterModalOpen(false)}
                className="w-6 h-6 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors"
              >
                <X className="w-3 h-3" />
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-3 space-y-3 overflow-y-auto text-[11px]">
              {/* Search */}
              <div className="space-y-1">
                <label className="font-bold text-slate-700 text-[10px] uppercase tracking-wider">Search</label>
                <div className="relative">
                  <Search className="w-3 h-3 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search doctor, clinic, notes..."
                    className="w-full pl-8 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[11px] focus:outline-none focus:ring-1 focus:ring-[#309BD8]"
                  />
                  {searchQuery && (
                    <button
                      onClick={() => setSearchQuery("")}
                      className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                      <X className="w-3 h-3" />
                    </button>
                  )}
                </div>
              </div>

              {/* Status Filter */}
              <div className="space-y-1">
                <label className="font-bold text-slate-700 text-[10px] uppercase tracking-wider">Status</label>
                <div className="grid grid-cols-2 gap-1">
                  {[
                    { id: "all", label: "All" },
                    { id: "upcoming", label: "Confirmed / Active" },
                    { id: "completed", label: "Completed" },
                    { id: "pending", label: "Pending" },
                    { id: "cancelled", label: "Cancelled / Failed" },
                  ].map((tab) => (
                    <button
                      key={tab.id}
                      type="button"
                      onClick={() => setFilterStatus(tab.id)}
                      className={`px-2 py-1.5 rounded-lg font-semibold text-left transition-colors border text-[10px] ${
                        filterStatus === tab.id
                          ? "bg-[#309BD8] text-white border-[#309BD8] shadow-2xs"
                          : "bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100"
                      }`}
                    >
                      {tab.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Date Filter */}
              <div className="space-y-1">
                <label className="font-bold text-slate-700 text-[10px] uppercase tracking-wider flex items-center justify-between">
                  <span>Date Range</span>
                  {customDate && (
                    <button
                      type="button"
                      onClick={() => setCustomDate("")}
                      className="text-[9px] text-red-600 font-normal underline"
                    >
                      Clear custom
                    </button>
                  )}
                </label>
                <div className="grid grid-cols-3 gap-1">
                  {QUICK_DATE_RANGES.map((d) => (
                    <button
                      key={d.key}
                      type="button"
                      onClick={() => {
                        setDateRangeKey(d.key);
                        setCustomDate("");
                      }}
                      className={`px-1.5 py-1 rounded-lg font-semibold text-center transition-colors border text-[10px] ${
                        dateRangeKey === d.key && !customDate
                          ? "bg-[#309BD8] text-white border-[#309BD8] shadow-2xs"
                          : "bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100"
                      }`}
                    >
                      {d.label}
                    </button>
                  ))}
                </div>

                <div className="pt-1">
                  <input
                    type="date"
                    value={customDate}
                    onChange={(e) => setCustomDate(e.target.value)}
                    className="w-full px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-[10px] font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-[#309BD8]"
                  />
                </div>
              </div>
            </div>

            {/* Modal Footer */}
            <div className="p-2.5 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
              <button
                type="button"
                onClick={resetFilters}
                className="text-[10px] font-bold text-red-600 hover:text-red-700 underline"
              >
                Reset All
              </button>
              <button
                type="button"
                onClick={() => setIsFilterModalOpen(false)}
                className="px-4 py-1.5 rounded-lg bg-[#309BD8] text-white text-[11px] font-bold hover:bg-[#2887bc] transition-colors shadow-2xs"
              >
                Apply
              </button>
            </div>
          </div>
        </div>
      )}

      {/* PRESCRIPTION VIEW MODAL */}
      {selectedPrescription && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/50 backdrop-blur-2xs animate-in fade-in duration-100">
          <div className="bg-white rounded-2xl max-w-sm w-full max-h-[85vh] overflow-hidden shadow-xl border border-slate-200 flex flex-col">
            {/* Modal Header */}
            <div className="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
              <div className="flex items-center gap-1.5">
                <div className="w-6 h-6 rounded-md bg-[#309BD8]/15 text-[#309BD8] flex items-center justify-center font-bold">
                  <FileText className="w-3.5 h-3.5" />
                </div>
                <div>
                  <h3 className="text-xs font-bold text-slate-900">
                    Digital Prescription
                  </h3>
                  <p className="text-[9px] text-slate-500">
                    Issued by licensed veterinary doctor
                  </p>
                </div>
              </div>
              <button
                onClick={() => setSelectedPrescription(null)}
                className="w-6 h-6 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors"
              >
                <X className="w-3 h-3" />
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-3 overflow-y-auto space-y-2.5 text-[11px]">
              {/* Doctor / Date Info */}
              <div className="p-2.5 rounded-xl bg-[#309BD8]/5 border border-[#309BD8]/20 space-y-1">
                <div className="flex justify-between items-start">
                  <div>
                    <p className="text-[9px] text-slate-500 font-medium">Doctor Name</p>
                    <p className="font-bold text-slate-900 text-xs">
                      {selectedPrescription.doctor_name || "Licensed Doctor"}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-[9px] text-slate-500 font-medium">Date</p>
                    <p className="font-semibold text-slate-700 text-[10px]">
                      {formatDate(selectedPrescription.created_at)}
                    </p>
                  </div>
                </div>

                {selectedPrescription.follow_up_date && (
                  <div className="pt-1 border-t border-[#309BD8]/20 flex items-center gap-1 text-[10px] font-semibold text-[#081037]">
                    <Calendar className="w-3 h-3 text-[#309BD8]" />
                    <span>Follow-up: {formatDate(selectedPrescription.follow_up_date)}</span>
                  </div>
                )}
              </div>

              {/* Diagnosis / Notes */}
              {(selectedPrescription.visit_notes || selectedPrescription.diagnosis || selectedPrescription.treatment_plan) && (
                <div className="space-y-0.5">
                  <h4 className="text-[9px] font-bold text-slate-500 uppercase tracking-wider">
                    Clinical Notes & Diagnosis
                  </h4>
                  <p className="text-slate-800 bg-slate-50 p-2 rounded-lg border border-slate-200 whitespace-pre-line leading-relaxed text-[10px]">
                    {selectedPrescription.visit_notes ||
                      selectedPrescription.diagnosis ||
                      selectedPrescription.treatment_plan}
                  </p>
                </div>
              )}

              {/* Medications List */}
              <div className="space-y-1">
                <h4 className="text-[9px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                  <Pill className="w-2.5 h-2.5 text-emerald-600" />
                  Prescribed Medications
                </h4>

                {(() => {
                  let meds = [];
                  if (Array.isArray(selectedPrescription.medications)) {
                    meds = selectedPrescription.medications;
                  } else if (typeof selectedPrescription.medications_json === "string") {
                    try {
                      meds = JSON.parse(selectedPrescription.medications_json);
                    } catch {
                      meds = [];
                    }
                  } else if (Array.isArray(selectedPrescription.medications_json)) {
                    meds = selectedPrescription.medications_json;
                  }

                  if (!meds || meds.length === 0) {
                    return (
                      <p className="text-slate-500 italic p-2 bg-slate-50 rounded-lg text-[10px]">
                        No specific medication list attached. Follow the doctor's visit notes above.
                      </p>
                    );
                  }

                  return (
                    <div className="space-y-1">
                      {meds.map((m, i) => (
                        <div
                          key={i}
                          className="p-2 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-0.5"
                        >
                          <p className="font-bold text-slate-900 text-[11px]">
                            {m.name || m.medicine_name || m.title || `Medicine #${i + 1}`}
                          </p>
                          <p className="text-[10px] text-slate-600">
                            {m.dosage && `Dosage: ${m.dosage} `}
                            {m.frequency && `• Frequency: ${m.frequency} `}
                            {m.duration && `• Duration: ${m.duration}`}
                          </p>
                          {m.notes && (
                            <p className="text-[9px] text-slate-500 italic">
                              Note: {m.notes}
                            </p>
                          )}
                        </div>
                      ))}
                    </div>
                  );
                })()}
              </div>

              {/* Prescription Image if available */}
              {selectedPrescription.image_path && (
                <div className="space-y-1">
                  <h4 className="text-[9px] font-bold text-slate-500 uppercase tracking-wider">
                    Attached Prescription Copy
                  </h4>
                  <img
                    src={
                      selectedPrescription.image_path.startsWith("http")
                        ? selectedPrescription.image_path
                        : `https://snoutiq.com/${selectedPrescription.image_path.replace(/^\/+/, "")}`
                    }
                    alt="Prescription Document"
                    className="w-full max-h-44 object-contain rounded-lg border border-slate-200 bg-slate-50 p-1"
                  />
                </div>
              )}
            </div>

            {/* Modal Footer */}
            <div className="p-2.5 border-t border-slate-100 bg-slate-50 flex justify-end">
              <button
                type="button"
                onClick={() => setSelectedPrescription(null)}
                className="px-3.5 py-1 rounded-lg bg-[#081037] text-white text-[10px] font-semibold hover:bg-[#0c1b50] transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}

      {/* CANCEL APPOINTMENT CONFIRMATION MODAL */}
      {cancelModalItem && (
        <div className="fixed inset-0 z-[300] flex items-center justify-center bg-black/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
          <div className="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl space-y-3.5 border border-slate-200 animate-in zoom-in-95 duration-150">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-red-600">
                <div className="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                  <AlertCircle size={18} className="text-red-600" />
                </div>
                <h3 className="font-bold text-sm text-slate-900">Cancel Appointment?</h3>
              </div>
              <button
                type="button"
                onClick={() => {
                  if (!cancellingLoading) setCancelModalItem(null);
                }}
                className="p-1 text-slate-400 hover:text-slate-600 rounded-full"
              >
                <X size={16} />
              </button>
            </div>

            <p className="text-xs text-slate-600 leading-relaxed">
              Are you sure you want to cancel the booking for{" "}
              <strong className="text-slate-900 font-bold">
                {cancelModalItem.pet_name || cancelModalItem.pet?.name || "your pet"}
              </strong>
              ?
            </p>

            {cancelError && (
              <div className="bg-red-50 border border-red-200 text-red-700 text-xs p-2 rounded-xl">
                {cancelError}
              </div>
            )}

            <div className="space-y-1.5">
              <label className="text-[11px] font-bold text-slate-600 block">
                Reason for cancellation
              </label>
              <select
                value={cancelReason}
                onChange={(e) => setCancelReason(e.target.value)}
                disabled={cancellingLoading}
                className="w-full text-xs border border-slate-200 rounded-xl p-2.5 outline-none bg-slate-50 focus:bg-white focus:border-[#309BD8] transition-all"
              >
                <option value="Change of plans / Schedule conflict">Change of plans / Schedule conflict</option>
                <option value="Pet has recovered / feels better">Pet has recovered / feels better</option>
                <option value="Booked by mistake">Booked by mistake</option>
                <option value="Found alternative vet">Found alternative vet</option>
                <option value="Other reason">Other reason</option>
              </select>
            </div>

            <div className="flex gap-2 pt-2">
              <button
                type="button"
                onClick={() => setCancelModalItem(null)}
                disabled={cancellingLoading}
                className="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors"
              >
                Keep Booking
              </button>
              <button
                type="button"
                onClick={handleConfirmCancel}
                disabled={cancellingLoading}
                className="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-xs font-bold text-white shadow-xs transition-all flex items-center justify-center gap-1.5 disabled:opacity-60 cursor-pointer"
              >
                {cancellingLoading ? (
                  <>
                    <Loader2 size={13} className="animate-spin" />
                    <span>Cancelling...</span>
                  </>
                ) : (
                  <span>Yes, Cancel</span>
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* RESCHEDULE APPOINTMENT MODAL */}
      {rescheduleModalItem && (
        <div className="fixed inset-0 z-[300] flex items-center justify-center bg-black/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
          <div className="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl space-y-3.5 border border-slate-200 animate-in zoom-in-95 duration-150">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2 text-[#309BD8]">
                <div className="w-8 h-8 rounded-full bg-[#309BD8]/10 flex items-center justify-center">
                  <Clock size={18} className="text-[#309BD8]" />
                </div>
                <h3 className="font-bold text-sm text-slate-900">Reschedule Appointment</h3>
              </div>
              <button
                type="button"
                onClick={() => {
                  if (!reschedulingLoading) setRescheduleModalItem(null);
                }}
                className="p-1 text-slate-400 hover:text-slate-600 rounded-full"
              >
                <X size={16} />
              </button>
            </div>

            <p className="text-xs text-slate-600">
              Select a new date and time for{" "}
              <strong className="text-slate-900 font-bold">
                {rescheduleModalItem.pet_name || rescheduleModalItem.pet?.name || "your pet"}
              </strong>
              .
            </p>

            {rescheduleError && (
              <div className="bg-red-50 border border-red-200 text-red-700 text-xs p-2 rounded-xl">
                {rescheduleError}
              </div>
            )}

            {/* Date Input */}
            <div className="space-y-1">
              <label className="text-[11px] font-bold text-slate-600 block">
                Select New Date
              </label>
              <input
                type="date"
                min={new Date().toISOString().split("T")[0]}
                value={rescheduleDate}
                onChange={(e) => setRescheduleDate(e.target.value)}
                disabled={reschedulingLoading}
                className="w-full text-xs border border-slate-200 rounded-xl p-2.5 outline-none bg-slate-50 focus:bg-white focus:border-[#309BD8] transition-all font-semibold text-slate-800"
              />
            </div>

            {/* Time Slot Selector */}
            <div className="space-y-1.5">
              <label className="text-[11px] font-bold text-slate-600 block">
                Select Preferred Time Slot
              </label>
              <div className="grid grid-cols-3 gap-1.5">
                {[
                  "10:00 AM",
                  "11:30 AM",
                  "01:00 PM",
                  "03:00 PM",
                  "05:00 PM",
                  "06:30 PM",
                ].map((slot) => (
                  <button
                    key={slot}
                    type="button"
                    onClick={() => setRescheduleTimeSlot(slot)}
                    disabled={reschedulingLoading}
                    className={`py-1.5 px-2 text-[10px] font-bold rounded-lg border transition-all cursor-pointer ${
                      rescheduleTimeSlot === slot
                        ? "bg-[#309BD8] text-white border-[#309BD8] shadow-xs"
                        : "bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200"
                    }`}
                  >
                    {slot}
                  </button>
                ))}
              </div>
            </div>

            <div className="flex gap-2 pt-2">
              <button
                type="button"
                onClick={() => setRescheduleModalItem(null)}
                disabled={reschedulingLoading}
                className="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors"
              >
                Back
              </button>
              <button
                type="button"
                onClick={handleConfirmReschedule}
                disabled={reschedulingLoading || !rescheduleDate || !rescheduleTimeSlot}
                className="flex-1 py-2.5 rounded-xl bg-[#309BD8] hover:bg-[#2887bc] text-xs font-bold text-white shadow-xs transition-all flex items-center justify-center gap-1.5 disabled:opacity-60 cursor-pointer"
              >
                {reschedulingLoading ? (
                  <>
                    <Loader2 size={13} className="animate-spin" />
                    <span>Updating...</span>
                  </>
                ) : (
                  <span>Confirm Slot</span>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
