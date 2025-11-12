import React, {
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import { AuthContext } from "../auth/AuthContext";
import {
  IoVideocam,
  IoCalendar,
  IoTime,
  IoPricetag,
  IoSwapHorizontal,
  IoRefresh,
  IoDocumentText,
  IoBusiness,
  IoPerson,
  IoCamera,
} from "react-icons/io5";

const API_BASE = "https://snoutiq.com/backend/api";

// ---- Cache keys & helpers (localStorage) ----
const CACHE_KEYS = {
  APPOINTMENTS: "appointments_cache",
  PRESCRIPTIONS: "prescriptions_cache",
};
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

const cacheData = async (key, data) => {
  try {
    localStorage.setItem(
      key,
      JSON.stringify({ data, timestamp: Date.now() })
    );
  } catch (e) {
    console.error("Cache save error:", e);
  }
};

const getCachedData = async (key) => {
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return null;
    const { data, timestamp } = JSON.parse(raw);
    if (Date.now() - timestamp < CACHE_DURATION) return data;
  } catch (e) {
    console.error("Cache read error:", e);
  }
  return null;
};

// ---- Parsing & date helpers ----
const parseJsonResponse = async (response, label) => {
  const contentType =
    response?.headers?.get?.("content-type")?.toLowerCase?.() || "";
  const raw = await response.text();
  const clean = raw.trim().replace(/^\uFEFF/, "");
  try {
    return JSON.parse(clean);
  } catch {
    const snippet = clean.slice(0, 300);
    const isHtml =
      contentType.includes("text/html") || snippet.startsWith("<!DOCTYPE");
    const message = isHtml
      ? `${label}: Received HTML (possible auth redirect)`
      : `${label}: Invalid JSON`;
    console.warn(message, snippet);
    throw new Error(message);
  }
};

const toYMD = (d) => {
  const dt = typeof d === "string" ? new Date(d) : d;
  const y = dt.getFullYear();
  const m = String(dt.getMonth() + 1).padStart(2, "0");
  const day = String(dt.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
};

const hhmm = (t) => (t && t.length >= 5 ? t.slice(0, 5) : t); // "17:20:00" -> "17:20"
const minutesOf = (tHHmm) => {
  const [h, m] = tHHmm.split(":").map((x) => parseInt(x, 10));
  return h * 60 + m;
};
const stripHtml = (html) =>
  html?.replace(/<[^>]*>/g, "").replace(/&nbsp;/g, " ").trim() || "";

// ---- Component ----
export default function HealthRecords() {
  const { user, token } = useContext(AuthContext);

  const [activeTab, setActiveTab] = useState("appointments");
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const [appointments, setAppointments] = useState([]);
  const [prescriptions, setPrescriptions] = useState([]);

  const [expandedId, setExpandedId] = useState(null);
  const [expandedType, setExpandedType] = useState(null);

  // Reschedule state
  const [reschedulingAppointment, setReschedulingAppointment] = useState(null);
  const [selectedDate, setSelectedDate] = useState(null); // YYYY-MM-DD
  const [selectedDoctor, setSelectedDoctor] = useState(null); // { id, name }
  const [availableSlots, setAvailableSlots] = useState([]); // ["HH:mm"]
  const [bookedSlots, setBookedSlots] = useState([]); // ["HH:mm"]
  const [selectedNewTime, setSelectedNewTime] = useState(null); // "HH:mm"
  const [rescheduleLoading, setRescheduleLoading] = useState(false);
  const serviceType = "in_clinic";

  // ---- API: Slots summary ----
  const fetchSlotsSummary = useCallback(
    async (doctorId, dateYMD) => {
      if (!doctorId || !dateYMD) return;
      try {
        const url = `${API_BASE}/doctors/${doctorId}/slots/summary?date=${dateYMD}&service_type=${serviceType}`;
        const response = await fetch(url, {
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
        });
        const data = await parseJsonResponse(response, "Slots summary");

        if (!response.ok || data.success === false) {
          console.warn("Slots API error:", data);
          alert(data.message || "Unable to fetch slots.");
          setAvailableSlots([]);
          setBookedSlots([]);
          return;
        }

        const free = Array.isArray(data.free_slots)
          ? data.free_slots.map(hhmm)
          : [];
        const booked = Array.isArray(data.booked_slots)
          ? data.booked_slots.map((b) => hhmm(b?.time || ""))
          : [];

        const uniqFree = [...new Set(free)].sort(
          (a, b) => minutesOf(a) - minutesOf(b)
        );
        const uniqBooked = [...new Set(booked)].sort(
          (a, b) => minutesOf(a) - minutesOf(b)
        );

        setAvailableSlots(uniqFree);
        setBookedSlots(uniqBooked);

        if (selectedNewTime && uniqBooked.includes(selectedNewTime)) {
          setSelectedNewTime(null);
        }
      } catch (e) {
        console.error("Slots error:", e);
        alert("Failed to load time slots. Please try again.");
        setAvailableSlots([]);
        setBookedSlots([]);
      }
    },
    [token, selectedNewTime]
  );

  useEffect(() => {
    if (reschedulingAppointment && selectedDoctor?.id && selectedDate) {
      fetchSlotsSummary(selectedDoctor.id, selectedDate);
    }
  }, [reschedulingAppointment, selectedDoctor, selectedDate, fetchSlotsSummary]);

  // ---- API: Appointments ----
  const fetchAppointments = useCallback(
    async (useCache = true) => {
      if (!user?.id) return;
      try {
        if (useCache) {
          const cached = await getCachedData(CACHE_KEYS.APPOINTMENTS);
          if (cached) {
            setAppointments(cached);
            return cached;
          }
        }

        const response = await fetch(
          `${API_BASE}/appointments/by-user/${user.id}`,
          {
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
            },
          }
        );
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const result = await parseJsonResponse(response, "Appointments fetch");
        const finalAppointments = Array.isArray(result?.data?.appointments)
          ? result.data.appointments.map((item) => ({
              id: item.id,
              clinicId: item.clinic?.id,
              clinicName: item.clinic?.name || "Clinic",
              doctorId: item.doctor?.id,
              doctorName: item.doctor?.name || "Doctor",
              patientName: item.patient?.name || "",
              patientPhone: item.patient?.phone || "",
              patientEmail: item.patient?.email || "",
              userId: item.patient?.user_id,
              date: item.date, // "YYYY-MM-DD"
              timeSlot: item.time_slot, // "HH:mm" or "HH:mm:ss"
              status: item.status,
              amount: item.amount,
              currency: item.currency || "INR",
            }))
          : [];

        setAppointments(finalAppointments);
        await cacheData(CACHE_KEYS.APPOINTMENTS, finalAppointments);
        return finalAppointments;
      } catch (e) {
        console.error("Appointments error:", e);
        const cached = await getCachedData(CACHE_KEYS.APPOINTMENTS);
        setAppointments(cached || []);
      }
    },
    [user?.id]
  );

  // ---- API: Prescriptions ----
  const fetchPrescriptions = useCallback(
    async (useCache = true) => {
      if (!user?.id) return;
      try {
        if (useCache) {
          const cached = await getCachedData(CACHE_KEYS.PRESCRIPTIONS);
          if (cached) {
            setPrescriptions(cached);
            return cached;
          }
        }

        const response = await fetch(
          `${API_BASE}/prescriptions?user_id=${user.id}`,
          {
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
            },
          }
        );
        const result = await parseJsonResponse(response, "Prescriptions fetch");
        const final = Array.isArray(result.data) ? result.data : [];
        setPrescriptions(final);
        await cacheData(CACHE_KEYS.PRESCRIPTIONS, final);
        return final;
      } catch (e) {
        console.error("Prescriptions error:", e);
        const cached = await getCachedData(CACHE_KEYS.PRESCRIPTIONS);
        setPrescriptions(cached || []);
      }
    },
    [user?.id]
  );

  const fetchAllData = useCallback(
    async (useCache = true) => {
      setLoading(true);
      await Promise.all([fetchAppointments(useCache), fetchPrescriptions(useCache)]);
      setLoading(false);
    },
    [fetchAppointments, fetchPrescriptions]
  );

  useEffect(() => {
    fetchAllData(true);
  }, [fetchAllData]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchAllData(false);
    setRefreshing(false);
  }, [fetchAllData]);

  // ---- Reschedule handlers ----
  const handleStartReschedule = (appointment) => {
    setReschedulingAppointment(appointment);
    const apptDate = toYMD(appointment.date || new Date());
    setSelectedDate(apptDate);
    setSelectedNewTime(hhmm(appointment.timeSlot));
    setSelectedDoctor({ id: appointment.doctorId, name: appointment.doctorName });
  };

  const handleCancelReschedule = () => {
    setReschedulingAppointment(null);
    setSelectedNewTime(null);
    setSelectedDate(null);
    setSelectedDoctor(null);
    setAvailableSlots([]);
    setBookedSlots([]);
  };

  const handleConfirmReschedule = useCallback(async () => {
    if (!reschedulingAppointment || !selectedNewTime) return;
    try {
      setRescheduleLoading(true);

      const payload = {
        patient_name: reschedulingAppointment.patientName,
        time_slot: selectedNewTime,
        status: "rescheduled",
        amount: reschedulingAppointment.amount,
        razorpay_payment_id: "pay_rescheduled",
      };
      if (
        selectedDate &&
        toYMD(reschedulingAppointment.date) !== toYMD(selectedDate)
      ) {
        payload.date = selectedDate;
      }

      const response = await fetch(
        `${API_BASE}/appointments/${reschedulingAppointment.id}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(payload),
        }
      );
      const result = await parseJsonResponse(response, "Reschedule appointment");
      if (!response.ok || result?.success === false) {
        throw new Error(result?.message || "Unable to reschedule");
      }

      await fetchAppointments(false);
      handleCancelReschedule();
    } catch (e) {
      console.error("Reschedule error:", e);
      alert(e.message || "Unable to reschedule.");
    } finally {
      setRescheduleLoading(false);
    }
  }, [reschedulingAppointment, selectedNewTime, selectedDate, fetchAppointments]);

  // ---- UI helpers ----
  const getStatusStyle = (status) => {
    const map = {
      confirmed: "bg-emerald-100 text-emerald-800 border-emerald-200",
      routing: "bg-blue-100 text-blue-800 border-blue-200",
      pending: "bg-amber-100 text-amber-800 border-amber-200",
      cancelled: "bg-rose-100 text-rose-800 border-rose-200",
      completed: "bg-gray-100 text-gray-700 border-gray-200",
      rescheduled: "bg-indigo-50 text-indigo-700 border-indigo-200",
    };
    return map[status] || map.pending;
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  };

  const formatTime = (timeStr) => {
    if (!timeStr) return "-";
    const [hours, minutes] = hhmm(timeStr).split(":");
    const hour = parseInt(hours, 10);
    if (Number.isNaN(hour)) return timeStr;
    const ampm = hour >= 12 ? "PM" : "AM";
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
  };

  const allSlotsForDay = useMemo(() => {
    const set = new Set([...availableSlots, ...bookedSlots]);
    const merged = Array.from(set);
    merged.sort((a, b) => minutesOf(a) - minutesOf(b));
    return merged;
  }, [availableSlots, bookedSlots]);

  const isBooked = (t) => bookedSlots.includes(t);
  const isSelected = (t) => selectedNewTime === t;

  const toggleExpand = (id, type) => {
    if (expandedId === id && expandedType === type) {
      setExpandedId(null);
      setExpandedType(null);
    } else {
      setExpandedId(id);
      setExpandedType(type);
    }
  };

  // ---- UI ----
  return (
    <div className="min-h-screen bg-indigo-50">
      {/* Header */}
      <div className="bg-gradient-to-r from-indigo-500 to-purple-500 px-5 py-5">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-white text-3xl font-extrabold tracking-tight">
              Health Records
            </h1>
            <p className="text-white/90 font-medium">
              Track appointments and prescriptions
            </p>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="mx-5 my-4 rounded-xl border border-gray-200 bg-white shadow-sm flex">
        <button
          onClick={() => setActiveTab("appointments")}
          className={`flex-1 py-3 text-sm font-semibold ${
            activeTab === "appointments"
              ? "bg-violet-600 text-white"
              : "text-gray-600"
          }`}
        >
          Appointments ({appointments.length})
        </button>
        <button
          onClick={() => setActiveTab("prescriptions")}
          className={`flex-1 py-3 text-sm font-semibold ${
            activeTab === "prescriptions"
              ? "bg-violet-600 text-white"
              : "text-gray-600"
          }`}
        >
          Prescriptions ({prescriptions.length})
        </button>
      </div>

      {/* Refresh */}
      <div className="mx-5 mb-3">
        <button
          onClick={onRefresh}
          disabled={refreshing}
          className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-violet-700 shadow-sm hover:bg-gray-50 disabled:opacity-60"
        >
          <IoRefresh />
          {refreshing ? "Refreshing..." : "Refresh"}
        </button>
      </div>

      {/* Content */}
      <div className="px-5 pb-10">
        {/* Loading skeleton */}
        {loading && (
          <div className="space-y-4">
            {[1, 2, 3].map((k) => (
              <div
                key={k}
                className="rounded-2xl border border-slate-100 bg-white p-5 shadow animate-pulse"
              >
                <div className="flex gap-4 items-start mb-4">
                  <div className="h-12 w-12 rounded-xl bg-gray-200" />
                  <div className="flex-1">
                    <div className="h-4 w-2/3 bg-gray-200 rounded mb-2" />
                    <div className="h-3 w-1/3 bg-gray-200 rounded" />
                  </div>
                </div>
                <div className="h-3 w-full bg-gray-200 rounded mb-2" />
                <div className="h-3 w-4/5 bg-gray-200 rounded" />
              </div>
            ))}
          </div>
        )}

        {/* Appointments */}
        {!loading && activeTab === "appointments" && (
          <>
            {appointments.length > 0 ? (
              <section className="space-y-4">
                <div className="flex items-center gap-3 mb-2">
                  <div className="h-9 w-9 rounded-lg bg-gradient-to-br from-violet-600 to-violet-800 grid place-items-center text-white">
                    <IoCalendar className="text-base" />
                  </div>
                  <h2 className="text-xl font-extrabold text-gray-800">
                    Appointments
                  </h2>
                  <span className="ml-auto rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">
                    {appointments.length}
                  </span>
                </div>

                {appointments.map((appointment) => {
                  const expanded =
                    expandedId === appointment.id &&
                    expandedType === "appointment";
                  const statusCls = getStatusStyle(appointment.status);
                  const canReschedule =
                    ["confirmed", "pending", "rescheduled"].includes(
                      appointment.status
                    );
                  const isRescheduling =
                    reschedulingAppointment?.id === appointment.id;
                  const displayAmount =
                    typeof appointment.amount === "number"
                      ? `₹${appointment.amount.toFixed(2)} ${
                          appointment.currency || "INR"
                        }`
                      : "N/A";

                  return (
                    <div
                      key={appointment.id}
                      className="rounded-2xl border border-slate-100 bg-white p-5 shadow"
                    >
                      {/* Header row */}
                      <button
                        onClick={() =>
                          toggleExpand(appointment.id, "appointment")
                        }
                        className="w-full text-left"
                      >
                        <div className="flex justify-between items-start gap-3 mb-4">
                          <div className="flex items-start gap-4 flex-1">
                            <div className="h-12 w-12 rounded-xl bg-gradient-to-br from-fuchsia-100 to-pink-100 grid place-items-center">
                              <IoVideocam className="text-violet-600 text-xl" />
                            </div>
                            <div className="flex-1">
                              <div className="flex items-center gap-2 mb-1">
                                <p className="font-bold text-gray-900 truncate">
                                  {appointment.doctorName}
                                </p>
                              </div>
                              <p className="text-sm font-medium text-gray-500 truncate">
                                {appointment.clinicName}
                              </p>
                            </div>
                          </div>

                          <div className="flex flex-col items-end gap-2">
                            <span
                              className={`inline-flex items-center gap-2 rounded-md border px-2.5 py-1 text-[11px] font-extrabold uppercase ${statusCls}`}
                            >
                              {appointment.status}
                            </span>
                            <span className="text-gray-400 text-lg">
                              {expanded ? "▴" : "▾"}
                            </span>
                          </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3 bg-slate-50 rounded-xl p-3">
                          <div className="flex items-center gap-2">
                            <IoCalendar className="text-violet-600" />
                            <span className="text-sm font-semibold text-gray-800">
                              {formatDate(appointment.date)}
                            </span>
                          </div>
                          <div className="flex items-center gap-2">
                            <IoTime className="text-violet-600" />
                            <span className="text-sm font-semibold text-gray-800">
                              {formatTime(appointment.timeSlot)}
                            </span>
                          </div>
                          <div className="flex items-center gap-2">
                            <IoPricetag className="text-emerald-600" />
                            <span className="text-sm font-semibold text-gray-800">
                              {displayAmount}
                            </span>
                          </div>
                        </div>
                      </button>

                      {/* Expanded */}
                      {expanded && (
                        <div className="mt-4">
                          <div className="h-px bg-slate-100 mb-4" />
                          {/* details */}
                          <div className="space-y-3 mb-3">
                            <div className="flex gap-3">
                              <div className="h-10 w-10 rounded-lg bg-violet-100 grid place-items-center">
                                <IoPerson className="text-violet-700" />
                              </div>
                              <div className="flex-1">
                                <div className="text-[11px] font-bold uppercase text-gray-500">
                                  Doctor
                                </div>
                                <div className="text-sm font-bold text-gray-900">
                                  {appointment.doctorName}
                                </div>
                              </div>
                            </div>
                            <div className="flex gap-3">
                              <div className="h-10 w-10 rounded-lg bg-emerald-100 grid place-items-center">
                                <IoBusiness className="text-emerald-700" />
                              </div>
                              <div className="flex-1">
                                <div className="text-[11px] font-bold uppercase text-gray-500">
                                  Clinic
                                </div>
                                <div className="text-sm font-bold text-gray-900">
                                  {appointment.clinicName}
                                </div>
                              </div>
                            </div>
                            <div className="flex gap-3">
                              <div className="h-10 w-10 rounded-lg bg-sky-100 grid place-items-center">
                                <IoTime className="text-sky-600" />
                              </div>
                              <div className="flex-1">
                                <div className="text-[11px] font-bold uppercase text-gray-500">
                                  Scheduled For
                                </div>
                                <div className="text-sm font-bold text-gray-900">
                                  {formatDate(appointment.date)} at{" "}
                                  {formatTime(appointment.timeSlot)}
                                </div>
                              </div>
                            </div>
                          </div>

                          {/* Reschedule */}
                          {canReschedule && (
                            <div className="border-t border-indigo-100 pt-3">
                              {isRescheduling ? (
                                <>
                                  {/* Date picker (calendar) */}
                                  <div className="mb-3">
                                    <div className="text-sm font-semibold text-indigo-700 mb-1">
                                      Pick a date
                                    </div>
                                    <input
                                      type="date"
                                      className="w-full sm:w-64 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                                      min={toYMD(new Date())}
                                      value={selectedDate || ""}
                                      onChange={(e) =>
                                        setSelectedDate(e.target.value)
                                      }
                                      onBlur={() => {
                                        if (
                                          selectedDoctor?.id &&
                                          selectedDate
                                        ) {
                                          fetchSlotsSummary(
                                            selectedDoctor.id,
                                            selectedDate
                                          );
                                        }
                                      }}
                                    />
                                  </div>

                                  {/* Slots */}
                                  <div className="flex items-center gap-2 mb-2">
                                    <IoTime className="text-indigo-600" />
                                    <div className="text-sm font-semibold text-indigo-700">
                                      Select a time
                                    </div>
                                  </div>

                                  <div className="flex flex-wrap gap-2 mb-2">
                                    {allSlotsForDay.length === 0 ? (
                                      <div className="text-gray-500 text-sm">
                                        No slots available.
                                      </div>
                                    ) : (
                                      allSlotsForDay.map((slot) => {
                                        const booked = isBooked(slot);
                                        const selected = isSelected(slot);
                                        return (
                                          <button
                                            key={slot}
                                            disabled={booked || rescheduleLoading}
                                            onClick={() =>
                                              setSelectedNewTime(slot)
                                            }
                                            className={[
                                              "px-3 py-1.5 rounded-full border text-xs font-semibold",
                                              booked
                                                ? "bg-rose-100 border-rose-500 text-rose-700 cursor-not-allowed"
                                                : selected
                                                ? "bg-violet-600 border-violet-600 text-white"
                                                : "bg-white border-slate-200 text-slate-700 hover:bg-slate-50",
                                            ].join(" ")}
                                          >
                                            {slot}
                                          </button>
                                        );
                                      })
                                    )}
                                  </div>

                                  {/* Legend */}
                                  <div className="flex items-center gap-4 mb-3">
                                    <div className="flex items-center gap-2">
                                      <span className="w-3 h-3 rounded-full bg-violet-600" />
                                      <span className="text-xs text-gray-600">
                                        Selected
                                      </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                      <span className="w-3 h-3 rounded-full bg-rose-100 ring-1 ring-rose-500" />
                                      <span className="text-xs text-gray-600">
                                        Booked
                                      </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                      <span className="w-3 h-3 rounded-full bg-gray-200" />
                                      <span className="text-xs text-gray-600">
                                        Free
                                      </span>
                                    </div>
                                  </div>

                                  <div className="flex justify-end gap-2">
                                    <button
                                      onClick={handleCancelReschedule}
                                      disabled={rescheduleLoading}
                                      className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-slate-50 disabled:opacity-60"
                                    >
                                      Cancel
                                    </button>
                                    <button
                                      onClick={handleConfirmReschedule}
                                      disabled={
                                        rescheduleLoading ||
                                        !selectedNewTime ||
                                        isBooked(selectedNewTime)
                                      }
                                      className="rounded-full bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-60"
                                    >
                                      {rescheduleLoading
                                        ? "Updating..."
                                        : "Confirm"}
                                    </button>
                                  </div>
                                </>
                              ) : (
                                <button
                                  onClick={() =>
                                    handleStartReschedule(appointment)
                                  }
                                  disabled={rescheduleLoading}
                                  className="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700"
                                >
                                  <IoSwapHorizontal />
                                  Reschedule
                                </button>
                              )}
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  );
                })}
              </section>
            ) : (
              <div className="grid place-items-center py-20 text-center">
                <div className="h-28 w-28 rounded-full bg-slate-100 grid place-items-center mb-5">
                  <span className="text-5xl text-gray-300">📂</span>
                </div>
                <h3 className="text-xl font-bold text-gray-800 mb-1">
                  No Records Found
                </h3>
                <p className="text-gray-500">
                  Your appointments will appear here once available
                </p>
              </div>
            )}
          </>
        )}

        {/* Prescriptions */}
        {!loading && activeTab === "prescriptions" && (
          <>
            {prescriptions.length > 0 ? (
              <section className="space-y-4">
                <div className="flex items-center gap-3 mb-2">
                  <div className="h-9 w-9 rounded-lg bg-gradient-to-br from-blue-600 to-blue-700 grid place-items-center text-white">
                    <IoDocumentText className="text-base" />
                  </div>
                  <h2 className="text-xl font-extrabold text-gray-800">
                    Prescriptions
                  </h2>
                  <span className="ml-auto rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">
                    {prescriptions.length}
                  </span>
                </div>

                {prescriptions.map((p) => {
                  const expanded =
                    expandedId === p.id && expandedType === "prescription";
                  const imgUrl =
                    typeof p.image_path === "string"
                      ? `https://snoutiq.com/backend/${p.image_path.replace(
                          "prescrriptions",
                          "prescriptions"
                        )}`
                      : null;

                  return (
                    <div
                      key={p.id}
                      className="rounded-2xl border border-slate-100 bg-white p-5 shadow"
                    >
                      <button
                        onClick={() => toggleExpand(p.id, "prescription")}
                        className="w-full text-left"
                      >
                        <div className="flex justify-between items-start gap-3 mb-3">
                          <div className="flex items-start gap-4 flex-1">
                            <div className="h-12 w-12 rounded-xl bg-gradient-to-br from-sky-100 to-sky-50 grid place-items-center">
                              <IoDocumentText className="text-sky-600 text-xl" />
                            </div>
                            <div>
                              <p className="font-bold text-gray-900">
                                Prescription #{p.id}
                              </p>
                              <p className="text-sm font-medium text-gray-500">
                                Issued:{" "}
                                {p.created_at
                                  ? new Date(p.created_at).toLocaleDateString(
                                      "en-US",
                                      {
                                        month: "short",
                                        day: "numeric",
                                        year: "numeric",
                                      }
                                    )
                                  : "-"}
                              </p>
                            </div>
                          </div>
                          <span className="text-gray-400 text-lg">
                            {expanded ? "▴" : "▾"}
                          </span>
                        </div>

                        <div className="rounded-xl bg-slate-50 p-3">
                          <p className="text-sm font-medium text-gray-700 line-clamp-3">
                            {stripHtml(p.content_html) ||
                              "No prescription details available"}
                          </p>
                        </div>
                      </button>

                      {expanded && (
                        <div className="mt-4 space-y-4">
                          <div className="border border-slate-200 rounded-xl p-4">
                            <div className="text-sm font-bold text-gray-700 mb-2">
                              Prescription Details
                            </div>
                            <div className="text-sm font-medium text-gray-700 leading-6">
                              {stripHtml(p.content_html) ||
                                "No prescription details available"}
                            </div>
                          </div>

                          {imgUrl && (
                            <div className="border border-slate-200 rounded-xl p-4">
                              <div className="flex items-center gap-2 mb-3">
                                <IoCamera className="text-gray-500" />
                                <div className="text-sm font-bold text-gray-700">
                                  Prescription Image
                                </div>
                              </div>
                              <div className="rounded-lg bg-slate-50 p-4 grid place-items-center">
                                <img
                                  src={imgUrl}
                                  alt="Prescription"
                                  className="max-h-72 w-full object-contain rounded-md"
                                  onError={(e) =>
                                    console.log("Image load error:", e)
                                  }
                                />
                              </div>
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  );
                })}
              </section>
            ) : (
              <div className="grid place-items-center py-20 text-center">
                <div className="h-28 w-28 rounded-full bg-slate-100 grid place-items-center mb-5">
                  <span className="text-5xl text-gray-300">📂</span>
                </div>
                <h3 className="text-xl font-bold text-gray-800 mb-1">
                  No Records Found
                </h3>
                <p className="text-gray-500">
                  Your prescriptions will appear here once available
                </p>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
