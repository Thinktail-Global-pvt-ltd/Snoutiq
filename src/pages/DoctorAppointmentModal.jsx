import React, {
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import toast from "react-hot-toast";
import { AuthContext } from "../auth/AuthContext";

const API_BASE_URL = "https://snoutiq.com/backend/api";
const RAZORPAY_KEY_ID = "rzp_test_1nhE9190sR3rkP";
const MONTH_NAMES = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];
const DAY_LABELS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const STEP_LABELS = ["Clinic", "Doctor", "Date", "Time", "Pay"];

const formatReadableDate = (value) => {
  if (!value) return "";
  const [year, month, day] = value.split("-").map(Number);
  if (!year || !month || !day) return value;
  const date = new Date(year, month - 1, day);
  return date.toLocaleDateString("en-IN", {
    weekday: "short",
    month: "short",
    day: "numeric",
  });
};

const Spinner = ({ label = "Loading..." }) => (
  <div className="flex flex-col items-center justify-center py-8 text-center">
    <span className="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mb-3" />
    <p className="text-sm text-gray-600">{label}</p>
  </div>
);

const DoctorAppointmentModal = ({ visible, onClose, onBook }) => {
  const { nearbyDoctors, user, token } = useContext(AuthContext);
  const [selectedClinic, setSelectedClinic] = useState(null);
  const [clinicDoctors, setClinicDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [selectedDate, setSelectedDate] = useState(null);
  const [slotsData, setSlotsData] = useState(null);
  const [selectedTime, setSelectedTime] = useState(null);
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [razorpayReady, setRazorpayReady] = useState(false);

  const processedClinics = useMemo(() => {
    return (
      nearbyDoctors?.map((clinic) => {
        let photos = [];
        try {
          photos = clinic.photos ? JSON.parse(clinic.photos) : [];
        } catch (error) {
          photos = [];
        }

        return {
          id: clinic.id,
          name: clinic.vet_name || clinic.name || "Veterinary Clinic",
          rating: parseFloat(clinic.rating) || 4.8,
          address:
            clinic.vet_address || clinic.formatted_address || clinic.address,
          mobile: clinic.mobile,
          email: clinic.email,
          open_now: clinic.open_now,
          user_ratings_total: clinic.user_ratings_total || 0,
          photos,
        };
      }) || []
    );
  }, [nearbyDoctors]);

  const resetForm = useCallback(() => {
    setSelectedClinic(null);
    setClinicDoctors([]);
    setSelectedDoctor(null);
    setSelectedDate(null);
    setSelectedTime(null);
    setSlotsData(null);
    setStep(1);
    setLoading(false);
  }, []);

  useEffect(() => {
    if (!visible) {
      resetForm();
    }
  }, [visible, resetForm]);

  useEffect(() => {
    if (typeof window === "undefined" || typeof document === "undefined") {
      return;
    }

    if (window.Razorpay) {
      setRazorpayReady(true);
      return;
    }

    let script = document.querySelector("script[data-razorpay-script]");

    const markReady = () => setRazorpayReady(true);
    const markFailed = () => setRazorpayReady(false);

    if (script) {
      script.addEventListener("load", markReady);
      script.addEventListener("error", markFailed);
      return () => {
        script.removeEventListener("load", markReady);
        script.removeEventListener("error", markFailed);
      };
    }

    script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.defer = true;
    script.dataset.razorpayScript = "true";
    script.onload = markReady;
    script.onerror = markFailed;
    document.body.appendChild(script);

    return () => {
      script.onload = null;
      script.onerror = null;
    };
  }, []);

  const parseJsonResponse = useCallback(async (response, label) => {
    let rawText = "";
    const contentType =
      response?.headers?.get?.("content-type")?.toLowerCase?.() || "";
    try {
      rawText = await response.text();
      let cleaned =
        typeof rawText === "string"
          ? rawText.trim().replace(/^\uFEFF/, "")
          : "";
      const jsonMatch = cleaned.match(/({[\s\S]*}|\[[\s\S]*\])/);
      if (jsonMatch) cleaned = jsonMatch[0];
      if (!cleaned) {
        throw new Error("Empty response");
      }
      return JSON.parse(cleaned);
    } catch (error) {
      const snippet =
        typeof rawText === "string" ? rawText.slice(0, 400) : String(rawText);
      const responseUrl = response?.url || "";
      const looksLikeHtml =
        contentType.includes("text/html") || snippet.startsWith("<!DOCTYPE");

      if (responseUrl.includes("/admin/login") || looksLikeHtml) {
        console.warn(`${label} HTML response snippet:`, snippet);
        throw new Error(
          `${label}: Server returned HTML instead of JSON. Please try again.`
        );
      }

      console.warn(`${label} raw response (non-JSON):`, snippet);
      throw new Error(`${label}: Invalid response from server.`);
    }
  }, []);
  const handleClinicSelect = useCallback(
    async (clinic) => {
      if (!clinic?.id) return;
      if (!token) {
        toast.error("Please log in to continue.");
        return;
      }

      setSelectedClinic(clinic);
      setClinicDoctors([]);
      setSelectedDoctor(null);
      setSelectedDate(null);
      setSelectedTime(null);
      setSlotsData(null);
      setStep(1);
      setLoading(true);

      try {
        const response = await fetch(
          `${API_BASE_URL}/clinics/${clinic.id}/doctors`,
          {
            headers: { Authorization: `Bearer ${token}` },
          }
        );

        const data = await parseJsonResponse(response, "Clinic doctors");

        if (!response.ok || !Array.isArray(data.doctors)) {
          throw new Error("Unable to load doctors for this clinic.");
        }

        setClinicDoctors(data.doctors);
        setStep(2);
      } catch (error) {
        console.error("Error fetching clinic doctors", error);
        toast.error(error.message || "Failed to load doctors.");
      } finally {
        setLoading(false);
      }
    },
    [token, parseJsonResponse]
  );

  const handleDoctorSelect = useCallback((doctor) => {
    setSelectedDoctor(doctor);
    setSelectedDate(null);
    setSelectedTime(null);
    setSlotsData(null);
    setStep(3);
  }, []);

  const handleDateSelect = useCallback((dateString) => {
    setSelectedDate(dateString);
    setSelectedTime(null);
    setSlotsData(null);
    setStep(4);
  }, []);

  const fetchSlotsSummary = useCallback(async () => {
    if (!selectedDoctor || !selectedDate || !token) return;
    setLoading(true);
    try {
      const url = `${API_BASE_URL}/doctors/${selectedDoctor.id}/slots/summary?date=${selectedDate}&service_type=in_clinic`;
      const response = await fetch(url, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      });

      const data = await parseJsonResponse(response, "Slots summary");

      if (!response.ok || data.success === false) {
        throw new Error(data.message || "Unable to fetch slots.");
      }

      setSlotsData(data);
    } catch (error) {
      console.error("Error fetching slots", error);
      setSlotsData(null);
      toast.error(error.message || "Failed to load slots.");
    } finally {
      setLoading(false);
    }
  }, [selectedDoctor, selectedDate, token, parseJsonResponse]);

  useEffect(() => {
    if (step === 4 && selectedDoctor && selectedDate) {
      fetchSlotsSummary();
    }
  }, [step, selectedDoctor, selectedDate, fetchSlotsSummary]);

  const processedSlots = useMemo(() => {
    if (!slotsData) {
      return { available: [], booked: [], all: [] };
    }

    const buildSlot = (timeString, meta = {}) => {
      const [hours = "0", minutes = "0"] = timeString.split(":");
      const hour = Number(hours);
      const displayHour = hour % 12 || 12;
      const ampm = hour < 12 ? "AM" : "PM";
      return {
        value: timeString,
        display: `${displayHour.toString().padStart(2, "0")}:${minutes.padStart(
          2,
          "0"
        )} ${ampm}`,
        ...meta,
      };
    };

    const freeSlots = (slotsData.free_slots || []).map((timeString) =>
      buildSlot(timeString, { isBooked: false })
    );

    const bookedSlots = (slotsData.booked_slots || []).map((booking) =>
      buildSlot(booking.time, {
        isBooked: true,
        patientName: booking.patient_name,
        petName: booking.pet_name,
      })
    );

    return {
      available: freeSlots,
      booked: bookedSlots,
      all: [...freeSlots, ...bookedSlots].sort((a, b) => {
        const [aHour, aMin] = a.value.split(":").map(Number);
        const [bHour, bMin] = b.value.split(":").map(Number);
        return aHour * 60 + aMin - (bHour * 60 + bMin);
      }),
    };
  }, [slotsData]);

  const handleTimeSelect = useCallback((slot) => {
    if (slot.isBooked) {
      toast.error("This slot is already booked. Please choose another time.");
      return;
    }
    setSelectedTime(slot);
    setStep(5);
  }, []);

  const doctorPrice = useMemo(() => {
    return slotsData?.doctor?.price || 500;
  }, [slotsData]);

  const calculateTotalAmount = useCallback(() => {
    return Math.round(Number(doctorPrice) || 0);
  }, [doctorPrice]);

  const resolvePatientName = useCallback(() => {
    return (
      user?.name ||
      user?.full_name ||
      user?.first_name ||
      user?.profile?.name ||
      ""
    );
  }, [user]);

  const resolvePatientPhone = useCallback(() => {
    return (
      user?.phone ||
      user?.mobile ||
      user?.contact ||
      user?.phone_number ||
      user?.contact_number ||
      ""
    );
  }, [user]);

  const resolvePetName = useCallback(() => {
    return (
      user?.pet_name ||
      user?.petName ||
      user?.pet?.name ||
      user?.profile?.pet_name ||
      user?.pets?.[0]?.name ||
      ""
    );
  }, [user]);

  const resolveDoctorUserId = useCallback(() => {
    if (!selectedDoctor) return null;
    return (
      selectedDoctor.user_id ||
      selectedDoctor.userId ||
      selectedDoctor.doctor_user_id ||
      selectedDoctor.account_id ||
      selectedDoctor.accountId ||
      selectedDoctor.id ||
      null
    );
  }, [selectedDoctor]);

  const resolveNumericId = useCallback((value) => {
    if (value == null) return null;
    const num = Number(value);
    return Number.isFinite(num) && num > 0 ? num : null;
  }, []);

  const resolveAppointmentDoctorUserId = useCallback(
    (appointment, payload) => {
      const doctor = appointment?.doctor || {};
      const candidates = [
        doctor.user_id,
        doctor.userId,
        doctor.account_id,
        doctor.accountId,
        doctor.owner_id,
        doctor.ownerId,
        doctor.id,
        doctor.doctor_id,
        doctor?.user?.id,
        doctor?.user?.user_id,
        doctor?.profile?.user_id,
        payload?.doctor_user_id,
        payload?.doctor_id,
        selectedDoctor?.user_id,
        selectedDoctor?.userId,
        selectedDoctor?.account_id,
        selectedDoctor?.accountId,
        selectedDoctor?.id,
      ];

      for (const candidate of candidates) {
        const normalized = resolveNumericId(candidate);
        if (normalized) return normalized;
      }

      console.warn("Doctor user id candidates missing");
      return null;
    },
    [selectedDoctor, resolveNumericId]
  );

  const notifyDoctorOfAppointment = useCallback(
    async (appointmentPayload, appointment) => {
      try {
        if (!appointment) {
          console.warn("notifyDoctorOfAppointment: Missing appointment");
          return;
        }

        const doctorUserId = resolveAppointmentDoctorUserId(
          appointment,
          appointmentPayload
        );

        if (!doctorUserId) {
          console.warn("notifyDoctorOfAppointment: doctor user id missing");
          return;
        }

        const issueRes = await fetch(`${API_BASE_URL}/device-tokens/issue`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            user_id: doctorUserId,
            token,
            platform: "web",
            device_id: `doctor-${doctorUserId}-primary`,
            meta: {
              app: "snoutiq",
              env: import.meta.env?.MODE || "production",
              role: "doctor",
            },
          }),
        });

        const issueData = await parseJsonResponse(issueRes, "Device token");

        if (!issueRes.ok || issueData.success !== true) {
          console.warn("Device token issue failed", issueData);
          return;
        }

        const deviceToken = issueData?.data?.token;
        if (!deviceToken) {
          console.warn("Device token missing");
          return;
        }

        const clinicName =
          appointment?.clinic?.name || appointmentPayload?.clinic_name || "";
        const doctorName =
          appointment?.doctor?.name || appointmentPayload?.doctor_name || "";
        const patientName =
          appointment?.patient?.name || appointmentPayload?.patient_name || "";
        const petName = appointmentPayload?.pet_name || "";
        const date = appointment?.date || appointmentPayload?.date;
        const timeSlot =
          appointment?.time_slot || appointmentPayload?.time_slot;

        const bodyLines = [
          `Dear ${doctorName || "Doctor"},`,
          "",
          "You have a new appointment booking on SnoutIQ.",
          patientName && `Pet Parent: ${patientName}`,
          petName && `Pet: ${petName}`,
          (date || timeSlot) && `Schedule: ${date || ""} at ${timeSlot || ""}`,
          clinicName && `Clinic: ${clinicName}`,
          "",
          "Please review and be available at the scheduled time.",
        ]
          .filter(Boolean)
          .join("\n");

        const pushRes = await fetch(`${API_BASE_URL}/push/test`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            token: deviceToken,
            title: "New Appointment Received",
            body: bodyLines,
            data: {
              type: "new_appointment",
              appointment_id: appointment?.id ? String(appointment.id) : "",
              doctor_id: String(doctorUserId),
              clinic_id: appointmentPayload?.clinic_id,
            },
          }),
        });

        const pushData = await parseJsonResponse(pushRes, "Push notification");
        if (!pushRes.ok || pushData.success !== true) {
          console.warn("Push notification failed", pushData);
        }
      } catch (error) {
        console.warn("notifyDoctorOfAppointment error", error);
      }
    },
    [token, parseJsonResponse, resolveAppointmentDoctorUserId]
  );
  const handlePaymentFailure = useCallback((error) => {
    console.error("Payment failed/cancelled", error);
    const message =
      error?.description ||
      error?.error?.description ||
      (error?.code === 2
        ? "Network error during payment. Please retry."
        : "Payment was cancelled or failed. Please try again.");
    toast.error(message);
    setLoading(false);
  }, []);

  const handlePaymentSuccess = useCallback(
    async (paymentData, orderId) => {
      try {
        setLoading(true);

        if (
          !user ||
          !selectedClinic ||
          !selectedDoctor ||
          !selectedDate ||
          !selectedTime
        ) {
          throw new Error(
            "Missing booking details before creating appointment."
          );
        }

        const verifyRes = await fetch(`${API_BASE_URL}/rzp/verify`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            razorpay_order_id: paymentData.razorpay_order_id || orderId,
            razorpay_payment_id: paymentData.razorpay_payment_id,
            razorpay_signature: paymentData.razorpay_signature,
          }),
        });

        const verifyData = await parseJsonResponse(
          verifyRes,
          "Payment verification"
        );

        if (!verifyRes.ok || verifyData.success !== true) {
          throw new Error(verifyData.message || "Payment verification failed.");
        }

        const verifiedAmount = Number(verifyData.amount);
        const resolvedAmount = Number.isFinite(verifiedAmount)
          ? Math.round(verifiedAmount)
          : calculateTotalAmount();

        const patientName = resolvePatientName();
        const patientPhone = resolvePatientPhone();
        const petName = resolvePetName();
        const doctorUserId = resolveDoctorUserId();

        const appointmentPayload = {
          user_id: user.id,
          clinic_id: selectedClinic.id,
          doctor_id: selectedDoctor.id,
          doctor_user_id: doctorUserId || selectedDoctor?.user_id || null,
          patient_name: patientName || user.name || "",
          patient_phone: patientPhone || user.phone || user.mobile || "NA",
          pet_name: petName || "Pet",
          date: selectedDate,
          time_slot: selectedTime.value,
          amount: resolvedAmount,
          currency: verifyData.currency || "INR",
          razorpay_payment_id: paymentData.razorpay_payment_id,
          razorpay_order_id: paymentData.razorpay_order_id || orderId,
          razorpay_signature: paymentData.razorpay_signature,
        };

        const requiredFields = [
          "user_id",
          "clinic_id",
          "doctor_id",
          "patient_name",
          "patient_phone",
          "pet_name",
          "date",
          "time_slot",
          "amount",
          "currency",
          "razorpay_payment_id",
          "razorpay_order_id",
          "razorpay_signature",
        ];

        const missingFields = requiredFields.filter((field) => {
          const val = appointmentPayload[field];
          return val === undefined || val === null || val === "";
        });

        if (missingFields.length) {
          throw new Error(
            `Some required appointment data is missing: ${missingFields.join(
              ", "
            )}`
          );
        }

        const createRes = await fetch(`${API_BASE_URL}/appointments/submit`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify(appointmentPayload),
        });

        const createData = await parseJsonResponse(
          createRes,
          "Appointment creation"
        );

        if (
          !createRes.ok ||
          createData.success !== true ||
          !createData.data?.appointment
        ) {
          throw new Error(
            createData.message || "Unable to create appointment."
          );
        }

        const appointment = createData.data.appointment;
        try {
          await notifyDoctorOfAppointment(appointmentPayload, appointment);
        } catch (notifyError) {
          console.warn(
            "Doctor notification failed",
            notifyError?.message || notifyError
          );
        }

        toast.success("Appointment confirmed!");
        onBook?.(appointment);
        onClose?.();
      } catch (error) {
        console.error("Payment / Appointment error", error);
        toast.error(
          error.message ||
            "Payment captured but booking failed. Please contact support."
        );
        onBook?.();
        onClose?.();
      } finally {
        setLoading(false);
      }
    },
    [
      user,
      selectedClinic,
      selectedDoctor,
      selectedDate,
      selectedTime,
      token,
      parseJsonResponse,
      calculateTotalAmount,
      resolvePatientName,
      resolvePatientPhone,
      resolvePetName,
      resolveDoctorUserId,
      notifyDoctorOfAppointment,
      onBook,
      onClose,
    ]
  );

  const initiateRazorpayPayment = useCallback(async () => {
    if (!selectedClinic || !selectedDoctor || !selectedDate || !selectedTime) {
      toast.error(
        "Please select clinic, doctor, date and time slot before payment."
      );
      return;
    }

    if (!user || !token) {
      toast.error("Please log in to book an appointment.");
      return;
    }

    if (typeof window === "undefined" || !razorpayReady || !window.Razorpay) {
      toast.error("Payment gateway is not ready yet. Please try again.");
      return;
    }

    const amount = calculateTotalAmount();
    if (!amount) {
      toast.error("Invalid consultation amount.");
      return;
    }

    setLoading(true);

    try {
      const createOrderRes = await fetch(`${API_BASE_URL}/create-order`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ amount }),
      });

      const orderData = await parseJsonResponse(createOrderRes, "Create order");

      if (!createOrderRes.ok || orderData.success === false) {
        throw new Error(orderData.message || "Unable to start payment.");
      }

      const order = orderData.order || orderData;
      const razorpayOrderId = order.id || order.order_id;
      const orderAmount = order.amount || amount * 100;
      const currency = order.currency || "INR";
      const razorpayKey = orderData.key || RAZORPAY_KEY_ID;

      if (!razorpayOrderId || !orderAmount) {
        throw new Error("Payment initialization failed.");
      }

      const rzp = new window.Razorpay({
        key: razorpayKey,
        amount: orderAmount,
        currency,
        name: "SnoutIQ",
        description: `In-clinic consultation with ${selectedDoctor.name}`,
        order_id: razorpayOrderId,
        prefill: {
          name: resolvePatientName() || "Pet Parent",
          email: user?.email || "",
          contact: resolvePatientPhone() || "",
        },
        theme: { color: "#0EA5E9" },
        handler: (paymentData) => {
          handlePaymentSuccess(paymentData, razorpayOrderId);
        },
        modal: {
          ondismiss: () => setLoading(false),
          escape: true,
        },
      });

      rzp.on("payment.failed", handlePaymentFailure);
      rzp.open();

      setLoading(false);
    } catch (error) {
      console.error("Payment error", error);
      toast.error(
        error.message || "Unable to start payment. Please try again later."
      );
      setLoading(false);
    }
  }, [
    selectedClinic,
    selectedDoctor,
    selectedDate,
    selectedTime,
    user,
    token,
    razorpayReady,
    calculateTotalAmount,
    parseJsonResponse,
    resolvePatientName,
    resolvePatientPhone,
    handlePaymentSuccess,
    handlePaymentFailure,
  ]);
  const renderStepIndicator = () => (
    <div className="flex items-center justify-between gap-2">
      {[1, 2, 3, 4, 5].map((stepNumber) => {
        const isDone = step > stepNumber;
        const isActive = step === stepNumber;
        return (
          <div key={stepNumber} className="flex items-center gap-2 flex-1">
            <div
              className={`flex items-center justify-center w-9 h-9 rounded-2xl text-xs font-semibold transition-all duration-200 ${
                isActive
                  ? "bg-blue-600 text-white shadow-lg shadow-blue-500/30"
                  : isDone
                  ? "bg-emerald-500 text-white"
                  : "bg-white text-gray-400 border border-gray-200"
              }`}
            >
              {isDone ? "✓" : stepNumber}
            </div>
            {stepNumber < 5 && (
              <div
                className={`flex-1 h-1 rounded-full ${
                  step > stepNumber ? "bg-blue-500" : "bg-gray-200"
                }`}
              />
            )}
          </div>
        );
      })}
    </div>
  );

  const renderStepLabels = () => (
    <div className="flex justify-between mt-3 text-[11px] font-semibold text-gray-400">
      {STEP_LABELS.map((label, index) => (
        <span
          key={label}
          className={`flex-1 text-center ${
            step >= index + 1 ? "text-blue-600" : ""
          }`}
        >
          {label}
        </span>
      ))}
    </div>
  );

  const renderClinicSelection = () => (
    <div className="p-4 space-y-4">
      <div className="text-center">
        <h2 className="text-lg font-bold text-gray-900">Select Clinic</h2>
        <p className="text-sm text-gray-500">
          Choose your preferred veterinary clinic
        </p>
      </div>

      {processedClinics.length === 0 ? (
        <div className="bg-gray-50 rounded-xl p-6 text-center">
          <p className="text-sm font-medium text-gray-600">
            No clinics available nearby.
          </p>
          <p className="text-xs text-gray-400 mt-1">
            We could not find any clinics in your location.
          </p>
        </div>
      ) : (
        <div className="space-y-3 max-h-[28rem] overflow-y-auto pr-1">
          {processedClinics.map((clinic) => (
            <button
              type="button"
              key={clinic.id}
              className={`w-full text-left flex items-start gap-3 border rounded-xl p-3 transition ${
                selectedClinic?.id === clinic.id
                  ? "border-blue-500 bg-blue-50"
                  : "border-gray-200 hover:border-blue-300"
              } ${loading ? "opacity-60 cursor-not-allowed" : ""}`}
              onClick={() => !loading && handleClinicSelect(clinic)}
              disabled={loading}
            >
              <div className="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center font-semibold">
                {clinic.name
                  .split(" ")
                  .map((part) => part[0])
                  .join("")
                  .slice(0, 2)
                  .toUpperCase()}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-gray-900 line-clamp-2">
                  {clinic.name}
                </p>
                <p className="text-xs text-gray-500 mt-1">{clinic.address}</p>
                <div className="flex items-center text-xs text-gray-500 mt-2 gap-4">
                  <span>⭐ {clinic.rating}</span>
                  <span>{clinic.user_ratings_total} reviews</span>
                </div>
              </div>
            </button>
          ))}
        </div>
      )}
    </div>
  );

  const renderDoctorSelection = () => (
    <div className="p-4 space-y-4">
      <div className="text-center">
        <h2 className="text-lg font-bold text-gray-900">Select Doctor</h2>
        <p className="text-sm text-gray-500">
          Choose your preferred doctor at {selectedClinic?.name}
        </p>
      </div>

      {loading ? (
        <Spinner label="Loading doctors..." />
      ) : clinicDoctors.length === 0 ? (
        <div className="bg-gray-50 rounded-xl p-6 text-center">
          <p className="text-sm font-medium text-gray-600">
            No doctors available for this clinic.
          </p>
          <p className="text-xs text-gray-400 mt-1">
            Please select another clinic to continue.
          </p>
        </div>
      ) : (
        <div className="space-y-3 max-h-[28rem] overflow-y-auto pr-1">
          {clinicDoctors.map((doctor) => (
            <button
              type="button"
              key={doctor.id}
              className={`w-full flex items-center gap-3 border rounded-xl p-3 transition ${
                selectedDoctor?.id === doctor.id
                  ? "border-blue-500 bg-blue-50"
                  : "border-gray-200 hover:border-blue-300"
              }`}
              onClick={() => handleDoctorSelect(doctor)}
            >
              <div className="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center font-semibold">
                {doctor.name
                  .split(" ")
                  .map((part) => part[0])
                  .join("")
                  .slice(0, 2)
                  .toUpperCase()}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-gray-900 line-clamp-2">
                  {doctor.name}
                </p>
                <p className="text-xs text-blue-600 font-medium">
                  Veterinary Doctor
                </p>
                {doctor.email && (
                  <p className="text-xs text-gray-500 truncate">{doctor.email}</p>
                )}
              </div>
              <span className="text-gray-400 text-sm">›</span>
            </button>
          ))}
        </div>
      )}
    </div>
  );

  const renderDateSelection = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();

    const cells = [];
    for (let i = 0; i < firstDay; i += 1) {
      cells.push(null);
    }
    for (let day = 1; day <= daysInMonth; day += 1) {
      cells.push(day);
    }

    const isPast = (day) => {
      const date = new Date(year, month, day);
      const normalizedToday = new Date();
      normalizedToday.setHours(0, 0, 0, 0);
      date.setHours(0, 0, 0, 0);
      return date < normalizedToday;
    };

    const formatDate = (day) => {
      const monthStr = String(month + 1).padStart(2, "0");
      const dayStr = String(day).padStart(2, "0");
      return `${year}-${monthStr}-${dayStr}`;
    };

    return (
      <div className="p-4 space-y-4">
        <div className="bg-blue-50 rounded-xl p-3 flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-white flex items-center justify-center text-blue-600 font-semibold">
            {selectedDoctor?.name
              ?.split(" ")
              .map((part) => part[0])
              .join("")
              .slice(0, 2)
              .toUpperCase()}
          </div>
          <div>
            <p className="text-sm font-semibold text-gray-900">
              {selectedDoctor?.name}
            </p>
            <p className="text-xs text-gray-500">Select consultation date</p>
          </div>
        </div>

        <div className="border rounded-2xl overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 bg-white border-b">
            <div>
              <p className="text-sm font-semibold text-gray-900">
                {MONTH_NAMES[month]} {year}
              </p>
              <p className="text-xs text-gray-500">Pick a preferred date</p>
            </div>
          </div>
          <div className="grid grid-cols-7 gap-2 px-4 pt-3 text-center text-xs font-semibold text-gray-500">
            {DAY_LABELS.map((label) => (
              <span key={label}>{label}</span>
            ))}
          </div>
          <div className="grid grid-cols-7 gap-2 px-4 py-4">
            {cells.map((day, index) =>
              day === null ? (
                <span key={`empty-${index}`} />
              ) : (
                <button
                  type="button"
                  key={day}
                  className={`w-10 h-10 rounded-full text-sm font-semibold transition ${
                    selectedDate === formatDate(day)
                      ? "bg-blue-600 text-white"
                      : "text-gray-700 hover:bg-blue-50"
                  } ${isPast(day) ? "opacity-30 cursor-not-allowed" : ""}`}
                  disabled={isPast(day)}
                  onClick={() => handleDateSelect(formatDate(day))}
                >
                  {day}
                </button>
              )
            )}
          </div>
        </div>
      </div>
    );
  };

  const renderTimeSelection = () => (
    <div className="p-4 space-y-4">
      <div className="bg-blue-50 rounded-xl p-3 flex items-center gap-3">
        <div className="w-10 h-10 rounded-lg bg-white flex items-center justify-center text-blue-600 font-semibold">
          📅
        </div>
        <div>
          <p className="text-sm font-semibold text-gray-900">
            {formatReadableDate(selectedDate)}
          </p>
          <p className="text-xs text-gray-500">
            Choose a preferred time slot
          </p>
        </div>
      </div>

      {loading ? (
        <Spinner label="Loading available slots..." />
      ) : !processedSlots.all.length ? (
        <div className="bg-gray-50 rounded-xl p-6 text-center">
          <p className="text-sm font-medium text-gray-600">
            No slots available for this date.
          </p>
          <p className="text-xs text-gray-400 mt-1">
            Please select another date.
          </p>
        </div>
      ) : (
        <>
          <div className="flex justify-between text-xs text-gray-500 bg-gray-50 rounded-xl p-3">
            <span>
              Available Slots: <strong className="text-gray-900">{processedSlots.available.length}</strong>
            </span>
            <span>
              Booked Slots: <strong className="text-gray-900">{processedSlots.booked.length}</strong>
            </span>
          </div>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {processedSlots.all.map((slot) => (
              <button
                type="button"
                key={slot.value}
                className={`border rounded-xl p-3 text-sm font-semibold transition relative ${
                  slot.isBooked
                    ? "border-red-200 bg-red-50 text-red-700 cursor-not-allowed"
                    : selectedTime?.value === slot.value
                    ? "border-blue-500 bg-blue-600 text-white"
                    : "border-gray-200 hover:border-blue-500"
                }`}
                disabled={slot.isBooked}
                onClick={() => handleTimeSelect(slot)}
              >
                {slot.display}
                {slot.isBooked && (
                  <span className="absolute top-1 right-2 text-[10px] font-bold uppercase">
                    Booked
                  </span>
                )}
              </button>
            ))}
          </div>
        </>
      )}
    </div>
  );

  const renderPayment = () => {
    const totalAmount = doctorPrice;

    return (
      <div className="p-4 space-y-4">
        <div className="text-center">
          <h2 className="text-lg font-bold text-gray-900">Confirm Booking</h2>
          <p className="text-sm text-gray-500">
            Review your appointment before payment
          </p>
        </div>

        <div className="bg-gray-50 rounded-2xl p-4 space-y-4">
          <div>
            <p className="text-xs text-gray-500 uppercase tracking-wide">
              Clinic
            </p>
            <p className="text-sm font-semibold text-gray-900">
              {selectedClinic?.name}
            </p>
            <p className="text-xs text-gray-500">{selectedClinic?.address}</p>
          </div>
          <div className="border-t border-gray-200 pt-4">
            <p className="text-xs text-gray-500 uppercase tracking-wide">
              Doctor
            </p>
            <p className="text-sm font-semibold text-gray-900">
              {selectedDoctor?.name}
            </p>
          </div>
          <div className="grid grid-cols-2 gap-4 border-t border-gray-200 pt-4">
            <div>
              <p className="text-xs text-gray-500 uppercase tracking-wide">
                Date
              </p>
              <p className="text-sm font-semibold text-gray-900">
                {formatReadableDate(selectedDate)}
              </p>
            </div>
            <div>
              <p className="text-xs text-gray-500 uppercase tracking-wide">
                Time
              </p>
              <p className="text-sm font-semibold text-gray-900">
                {selectedTime?.display}
              </p>
            </div>
          </div>
          <div className="border-t border-gray-200 pt-4 flex items-center justify-between">
            <div>
              <p className="text-xs text-gray-500 uppercase tracking-wide">
                Consultation Fee
              </p>
              <p className="text-xs text-gray-500">Includes taxes & charges</p>
            </div>
            <p className="text-xl font-bold text-emerald-600">
              ₹{Number(totalAmount || 0).toLocaleString("en-IN")}
            </p>
          </div>
        </div>

        <div className="bg-white border rounded-2xl p-4 flex items-center justify-between">
          <div>
            <p className="text-sm font-semibold text-gray-900">Razorpay</p>
            <p className="text-xs text-gray-500">
              Pay securely via Card, UPI or Net Banking
            </p>
          </div>
          <span className="text-gray-400 text-sm">›</span>
        </div>

        <button
          type="button"
          className="w-full rounded-2xl bg-emerald-600 text-white py-3 font-semibold disabled:opacity-50"
          onClick={initiateRazorpayPayment}
          disabled={loading}
        >
          {loading ? "Processing..." : `Pay ₹${Number(totalAmount).toFixed(0)}`}
        </button>

        <button
          type="button"
          className="w-full rounded-2xl border border-gray-200 py-3 font-semibold text-gray-700"
          onClick={onClose}
        >
          Cancel Booking
        </button>
      </div>
    );
  };

  const canContinue = useMemo(() => {
    switch (step) {
      case 1:
        return Boolean(selectedClinic);
      case 2:
        return Boolean(selectedDoctor);
      case 3:
        return Boolean(selectedDate);
      case 4:
        return Boolean(selectedTime);
      default:
        return true;
    }
  }, [step, selectedClinic, selectedDoctor, selectedDate, selectedTime]);

  if (!visible) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/60">
      <div className="w-full max-w-md bg-white rounded-t-[32px] shadow-2xl overflow-hidden animate-in slide-in-from-bottom">
        <div className="bg-gradient-to-br from-blue-600/10 via-sky-100/40 to-white p-5 border-b border-blue-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-[11px] uppercase tracking-wide text-blue-500 font-semibold">
                SnoutIQ Clinics
              </p>
              <h2 className="text-xl font-bold text-slate-900">
                Schedule an Appointment
              </h2>
              <p className="text-sm text-slate-500 mt-1">
                Pick a clinic, doctor & confirm your slot
              </p>
            </div>
            <button
              type="button"
              onClick={onClose}
              className="p-2 rounded-full bg-white shadow hover:bg-slate-100 text-slate-600 transition"
            >
              ✕
            </button>
          </div>

          <div className="mt-5">{renderStepIndicator()}</div>
          {renderStepLabels()}
        </div>

        <div className="max-h-[65vh] overflow-y-auto px-5 py-4 space-y-4 bg-slate-50">
          {step === 1 && renderClinicSelection()}
          {step === 2 && renderDoctorSelection()}
          {step === 3 && renderDateSelection()}
          {step === 4 && renderTimeSelection()}
          {step === 5 && renderPayment()}
        </div>

        <div className="px-5 py-4 bg-white border-t border-slate-100 flex items-center justify-between gap-2">
          <button
            type="button"
            onClick={step > 1 ? () => setStep(step - 1) : onClose}
            className="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 transition"
          >
            {step > 1 ? "Back" : "Close"}
          </button>
          <div className="flex-1" />
          {step < 5 && (
            <button
              type="button"
              onClick={() => setStep((prev) => Math.min(prev + 1, 5))}
              disabled={!canContinue}
              className={`px-4 py-2 rounded-xl text-sm font-semibold shadow transition ${
                canContinue
                  ? "bg-blue-600 text-white hover:bg-blue-700"
                  : "bg-gray-200 text-gray-400 cursor-not-allowed shadow-none"
              }`}
            >
              Continue
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default DoctorAppointmentModal;
