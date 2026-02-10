// VetScreens.jsx
// Notes:
// - Compress images before upload.
// - Upload image to get a URL (backend upload endpoint).
// - Send doctor_image as a URL string.

import React, { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import imageCompression from "browser-image-compression";
import { Button } from "./Button";
import { apiBaseUrl, apiPost } from "../lib/api";
import { clearVetAuth, loadVetAuth, saveVetAuth } from "../lib/vetAuth";
import {
  ChevronLeft,
  Camera,
  CheckCircle2,
  Clock,
  DollarSign,
  AlertCircle,
  History,
  Lock,
  FileText,
  User,
  Pill,
  X,
  Upload,
} from "lucide-react";
import logo from "../assets/images/logo.png";

/**
 * Notes:
 * - Upload image to get a URL.
 * - Send doctor_image as a URL string.
 */

// ---------------- UI Helpers ----------------

const VetHeader = ({ onBack, title }) => (
  <div className="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-stone-100">
    <div className="px-4 py-3 flex items-center md:px-10 lg:px-16 md:py-4">
      {onBack ? (
        <button
          onClick={onBack}
          className="p-2 -ml-2 text-stone-500 hover:text-stone-700 hover:bg-stone-100 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[#3998de]/30"
          aria-label="Go back"
        >
          <ChevronLeft size={24} />
        </button>
      ) : (
        <div className="w-10" />
      )}

      <h1 className="flex-1 text-center font-bold text-lg text-stone-800 md:text-2xl">
        {title}
      </h1>

      <div className="w-10" />
    </div>
  </div>
);

const PageWrap = ({ children }) => (
  <div className="w-full md:px-10 lg:px-16">{children}</div>
);

const INPUT_BASE_CLASS =
  "w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] placeholder:text-stone-400";

const SPECIALIZATION_OPTIONS = [
  "Dogs",
  "Cats",
  "Exotic Pet",
  "Livestock",
  "Surgery",
  "Skin / Dermatology",
  "General Practice",
  "Other",
];

const DEGREE_OPTIONS = ["BVSc", "MVSc", "PHD", "Other"];

const RESPONSE_TIME_DAY_OPTIONS = ["0 to 15 mins", "15 to 20 mins", "20 to 30 mins"];
const RESPONSE_TIME_NIGHT_OPTIONS = ["0 to 15 mins", "15 to 20 mins", "20 to 30 mins"];

const FOLLOW_UP_OPTIONS = [
  { value: "yes", label: "Yes - free follow-up within 3 days" },
  { value: "no", label: "No - follow-ups are paid consultations" },
];

const PAYOUT_OPTIONS = [
  { value: "upi", label: "UPI (recommended)" },
  { value: "other", label: "Other" },
];

const IMAGE_URL_LIMIT = 500;

const normalizeId = (value) => {
  const num = Number(value);
  if (!Number.isFinite(num) || num <= 0) return "";
  return String(num);
};

const fetchClinicIdForDoctor = async (doctorId, authToken = "", signal) => {
  if (!doctorId) return "";
  try {
    const headers = authToken
      ? { Authorization: `Bearer ${authToken}`, Accept: "application/json" }
      : { Accept: "application/json" };
    const res = await fetch(
      `${apiBaseUrl()}/api/doctor/profile?doctor_id=${encodeURIComponent(doctorId)}`,
      { headers, credentials: "include", signal }
    );
    if (!res.ok) return "";
    const data = await res.json().catch(() => ({}));
    return normalizeId(
      data?.data?.vet_registeration_id || data?.data?.clinic?.id || data?.clinic?.id
    );
  } catch (error) {
    if (error?.name === "AbortError") return "";
    return "";
  }
};

const ensureDashboardLoaded = () => {
  if (typeof window === "undefined") return;
  window.setTimeout(() => {
    if (window.location.pathname !== "/vet-dashboard") return;
    const stored = loadVetAuth();
    if (!stored) return;
    const dashboardRoot = document.querySelector("[data-vet-dashboard='true']");
    if (!dashboardRoot) {
      window.location.assign("/vet-dashboard");
    }
  }, 300);
};

const blockNumberInput = (e) => {
  if (["e", "E", "+", "-"].includes(e.key)) e.preventDefault();
};

const handleNumberWheel = (e) => {
  e.currentTarget.blur();
};

// -------------- Image Compression (File based) --------------

/**
 * Compress a selected image into a small JPEG file.
 * Adjust maxSizeMB/maxWidthOrHeight according to your needs.
 */
const compressToFile = async (file) => {
  if (!file || !file.type?.startsWith("image/")) {
    throw new Error("Please upload a valid image file.");
  }

  const options = {
    maxSizeMB: 0.2, // 200 KB target
    maxWidthOrHeight: 720,
    useWebWorker: true,
    fileType: "image/jpeg",
    initialQuality: 0.75,
  };

  const compressedBlob = await imageCompression(file, options);

  return new File([compressedBlob], "doctor.jpg", {
    type: "image/jpeg",
    lastModified: Date.now(),
  });
};

const uploadDoctorImageAndGetUrl = async (file) => {
  const fd = new FormData();
  fd.append("file", file);

  const res = await fetch(`${apiBaseUrl()}/api/vet-photo/upload`, {
    method: "POST",
    body: fd,
  });

  const data = await res.json().catch(() => ({}));
  const url =
    data?.data?.public_url ||
    data?.data?.url ||
    data?.public_url ||
    data?.url ||
    "";

  if (!res.ok || !url) {
    throw new Error(data?.message || "Image upload failed.");
  }

  return url;
};

// ---------------- Pricing Section ----------------

const PricingSection = ({
  dayPrice,
  nightPrice,
  setDayPrice,
  setNightPrice,
  dayMath,
  nightMath,
  form,
  updateForm,
  agreement1,
  setAgreement1,
  agreement2,
  setAgreement2,
  submitError,
  showErrors,
  canSubmit,
  submitting,
  handleSubmit,
}) => (
  <section className="bg-white p-6 rounded-3xl shadow-md border border-stone-100 space-y-4">
    <h3 className="font-bold text-stone-800 flex items-center gap-2 text-lg">
      <span className="bg-[#3998de]/10 text-[#3998de] w-7 h-7 rounded-full flex items-center justify-center text-sm">
        4
      </span>
      Pricing & Commission
    </h3>

    <div className="space-y-4">
      <div>
        <label className="block text-sm font-bold text-stone-400 mb-1">
          Video Consultation Price (Day time) (Rs.) *
        </label>
        <input
          type="number"
          value={dayPrice}
          onChange={(e) => setDayPrice(e.target.value)}
          onKeyDown={blockNumberInput}
          onWheel={handleNumberWheel}
          min="0"
          placeholder="399"
          required
          className="w-full p-4 rounded-2xl border border-stone-200 bg-stone-50 text-base font-bold text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] placeholder:text-stone-400"
        />
        {dayMath ? (
          <div className="mt-2 text-xs flex justify-between bg-green-50 text-green-800 px-3 py-2 rounded-xl border border-green-100">
            <span>
              You earn: <strong>Rs. {dayMath.earning}</strong>
            </span>
            <span className="text-green-600/70">
              Snoutiq Fee: Rs. {dayMath.commission}
            </span>
          </div>
        ) : null}
      </div>

      <div>
        <label className="block text-sm font-bold text-stone-400 mb-1">
          Video Consultation Price (Night time) (Rs.) *
        </label>
        <input
          type="number"
          value={nightPrice}
          onChange={(e) => setNightPrice(e.target.value)}
          onKeyDown={blockNumberInput}
          onWheel={handleNumberWheel}
          min="0"
          placeholder="599"
          required
          className="w-full p-4 rounded-2xl border border-stone-200 bg-stone-50 text-base font-bold text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] placeholder:text-stone-400"
        />
        {nightMath ? (
          <div className="mt-2 text-xs flex justify-between bg-green-50 text-green-800 px-3 py-2 rounded-xl border border-green-100">
            <span>
              You earn: <strong>Rs. {nightMath.earning}</strong>
            </span>
            <span className="text-green-600/70">
              Snoutiq Fee: Rs. {nightMath.commission}
            </span>
          </div>
        ) : null}
      </div>
    </div>

    <div className="space-y-3">
      <div className="text-sm font-bold text-stone-400">
        Do you offer a free follow-up within 3 days after a consultation? *
      </div>
      <div className="space-y-2">
        {FOLLOW_UP_OPTIONS.map((option) => (
          <label
            key={option.value}
            className="flex items-center gap-2 text-sm text-stone-700"
          >
            <input
              type="radio"
              name="freeFollowUp"
              value={option.value}
              checked={form.freeFollowUp === option.value}
              onChange={updateForm("freeFollowUp")}
              required
              className="accent-[#3998de]"
            />
            {option.label}
          </label>
        ))}
      </div>
    </div>

    <div className="space-y-3">
      <div className="text-sm font-bold text-stone-400">
        Preferred Payout Method (UPI number to receive payment) *
      </div>
      <div className="space-y-2">
        {PAYOUT_OPTIONS.map((option) => (
          <label
            key={option.value}
            className="flex items-center gap-2 text-sm text-stone-700"
          >
            <input
              type="radio"
              name="payoutMethod"
              value={option.value}
              checked={form.payoutMethod === option.value}
              onChange={updateForm("payoutMethod")}
              required
              className="accent-[#3998de]"
            />
            {option.label}
          </label>
        ))}
      </div>
      <input
        type="text"
        value={form.payoutDetail}
        onChange={updateForm("payoutDetail")}
        placeholder={
          form.payoutMethod === "upi" ? "yourname@upi *" : "Describe payout method *"
        }
        required
        className={INPUT_BASE_CLASS}
      />
    </div>

    <div className="bg-amber-50 p-4 rounded-2xl border border-amber-100">
      <h4 className="text-amber-800 font-bold text-xs uppercase mb-2 flex items-center gap-1">
        <DollarSign size={12} /> Commission Structure
      </h4>
      <ul className="text-sm text-amber-900/80 space-y-1 list-disc pl-4">
        <li>We charge 25% OR Rs. 99 per consultation (whichever is higher).</li>
        <li>The remaining amount is yours.</li>
        <li>No monthly subscription fees.</li>
      </ul>
    </div>

    <div className="space-y-2">
      <label className="flex gap-3 items-start p-2 cursor-pointer rounded-xl hover:bg-stone-50 transition-colors">
        <input
          type="checkbox"
          checked={agreement1}
          onChange={(e) => setAgreement1(e.target.checked)}
          className="mt-1 accent-[#3998de]"
        />
        <span className="text-sm text-stone-600 leading-relaxed">
          I understand Snoutiq charges 25% or Rs. 99 per consultation (whichever is higher).
        </span>
      </label>
      <label className="flex gap-3 items-start p-2 cursor-pointer rounded-xl hover:bg-stone-50 transition-colors">
        <input
          type="checkbox"
          checked={agreement2}
          onChange={(e) => setAgreement2(e.target.checked)}
          className="mt-1 accent-[#3998de]"
        />
        <span className="text-sm text-stone-600 leading-relaxed">
          I understand earnings will be settled weekly to my registered payout method.
        </span>
      </label>
    </div>

    {submitError ? (
      <div className="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-100 p-3 rounded-xl">
        <AlertCircle size={16} />
        <span>{submitError}</span>
      </div>
    ) : null}

    {showErrors && !canSubmit ? (
      <div className="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-100 p-3 rounded-xl">
        <AlertCircle size={16} />
        <span>Please complete all required fields.</span>
      </div>
    ) : null}

    <div className="hidden md:block">
      <Button
        onClick={handleSubmit}
        fullWidth
        disabled={!canSubmit || submitting}
        className={`md:text-xl md:py-4 md:rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 ${
          !canSubmit || submitting ? "opacity-50" : ""
        }`}
      >
        {submitting ? "Submitting..." : "Submit Application"}
      </Button>
    </div>

    <p className="text-xs text-stone-400 flex items-center gap-1">
      <Lock size={12} /> Patient contact details stay private.
    </p>
  </section>
);

// ---------------- 1) Vet Login Screen ----------------

export const VetLoginScreen = ({ onLogin, onRegisterClick, onBack }) => {
  const navigate = useNavigate();
  const [mobile, setMobile] = useState("");
  const [otp, setOtp] = useState("");
  const [step, setStep] = useState("mobile");
  const [requestId, setRequestId] = useState("");
  const [errorMessage, setErrorMessage] = useState("");
  const [isSending, setIsSending] = useState(false);
  const [isVerifying, setIsVerifying] = useState(false);

  useEffect(() => {
    const storedAuth = loadVetAuth();
    if (
      storedAuth &&
      (storedAuth?.doctor_id ||
        storedAuth?.clinic_id ||
        storedAuth?.token ||
        storedAuth?.phone)
    ) {
      if (onLogin) {
        onLogin(storedAuth);
      } else {
        navigate("/vet-dashboard", { replace: true, state: { auth: storedAuth } });
      }
    }
  }, [navigate, onLogin]);

  const handleSendOtp = async () => {
    if (mobile.length < 10 || isSending) return;
    setIsSending(true);
    setErrorMessage("");
    try {
      const res = await apiPost("/api/doctor/otp/request", { phone: mobile });
      const nextRequestId =
        res?.request_id ||
        res?.requestId ||
        res?.data?.request_id ||
        res?.data?.requestId ||
        "";
      if (!nextRequestId) throw new Error("Request ID not received. Please try again.");
      setRequestId(nextRequestId);
      setStep("otp");
    } catch (error) {
      setErrorMessage(error?.message || "Failed to send OTP. Please try again.");
    } finally {
      setIsSending(false);
    }
  };

  const handleVerifyOtp = async () => {
    if (!requestId || otp.length < 6 || isVerifying) return;
    setIsVerifying(true);
    setErrorMessage("");
    try {
      const res = await apiPost("/api/doctor/otp/verify", {
        phone: mobile,
        otp,
        request_id: requestId,
      });
      if (res?.success === false) throw new Error(res?.message || "OTP verification failed.");

      const doctor = res?.doctor || res?.data?.doctor || null;
      const doctorId =
        res?.doctor_id ||
        res?.doctorId ||
        res?.data?.doctor_id ||
        res?.data?.doctorId ||
        doctor?.id ||
        doctor?.doctor_id ||
        doctor?.doctorId ||
        res?.data?.doctor?.id ||
        res?.data?.doctor?.doctor_id ||
        res?.data?.doctor?.doctorId ||
        "";
      const clinicId =
        res?.clinic_id ||
        res?.clinicId ||
        res?.data?.clinic_id ||
        res?.data?.clinicId ||
        doctor?.clinic_id ||
        doctor?.clinicId ||
        doctor?.vet_registeration_id ||
        doctor?.vet_registration_id ||
        doctor?.vet_id ||
        res?.data?.doctor?.clinic_id ||
        res?.data?.doctor?.clinicId ||
        res?.data?.doctor?.vet_registeration_id ||
        res?.data?.doctor?.vet_registration_id ||
        res?.data?.doctor?.vet_id ||
        res?.vet_id ||
        res?.data?.vet_id ||
        "";

      const normalizedDoctorId = normalizeId(doctorId);
      let normalizedClinicId = normalizeId(clinicId);
      if (!normalizedClinicId && normalizedDoctorId) {
        normalizedClinicId = await fetchClinicIdForDoctor(normalizedDoctorId);
      }

      const doctorProfile =
        doctor || {
          doctor_name: res?.doctor_name || res?.name || res?.data?.doctor_name || "",
          doctor_email: res?.doctor_email || res?.email || res?.data?.doctor_email || "",
          doctor_mobile: res?.doctor_mobile || mobile,
        };

      const authPayload = {
        phone: mobile,
        request_id: requestId,
        doctor_id: normalizedDoctorId || doctorId,
        clinic_id: normalizedClinicId || clinicId,
        token: res?.token || res?.access_token || res?.data?.token || res?.data?.access_token || "",
        doctor: {
          ...doctorProfile,
          ...(normalizedDoctorId
            ? { id: Number(normalizedDoctorId), doctor_id: Number(normalizedDoctorId) }
            : {}),
          ...(normalizedClinicId
            ? {
                clinic_id: Number(normalizedClinicId),
                vet_registeration_id: Number(normalizedClinicId),
              }
            : {}),
        },
      };

      saveVetAuth(authPayload);
      onLogin?.(authPayload);
      navigate("/vet-dashboard", { replace: true, state: { auth: authPayload } });
      ensureDashboardLoaded();
    } catch (error) {
      setErrorMessage(error?.message || "OTP verification failed.");
    } finally {
      setIsVerifying(false);
    }
  };

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <VetHeader onBack={onBack} title="Vet Partner Login" />

      <PageWrap>
        <div className="flex-1 px-6 py-8 flex flex-col justify-center max-w-sm mx-auto w-full md:max-w-2xl md:px-0 md:py-16">
          <div className="text-center mb-8 md:mb-10">
            <div className="w-16 h-16 bg-[#3998de]/10 text-[#3998de] rounded-full flex items-center justify-center mx-auto mb-4 md:w-24 md:h-24 ring-1 ring-[#3998de]/20">
              <img src={logo} alt="SnoutIQ Logo" className="w-8 md:w-16" />
            </div>
            <h2 className="text-2xl font-bold text-stone-800 md:text-4xl">Welcome, Doctor</h2>
            <p className="text-stone-500 mt-2 text-sm md:text-lg">Log in to manage your consultations.</p>
          </div>

          <div className="bg-white p-6 rounded-2xl shadow-sm border border-stone-100 space-y-6 md:p-10 md:rounded-3xl md:shadow-md md:shadow-stone-200/40">
            {step === "mobile" ? (
              <>
                <div>
                  <label className="block text-xs font-bold uppercase text-stone-400 mb-1 md:text-sm">
                    Mobile Number
                  </label>

                  <div className="flex items-center border border-stone-200 rounded-xl px-3 bg-stone-50 focus-within:ring-2 focus-within:ring-[#3998de]/30 focus-within:border-[#3998de] md:px-4 md:py-1">
                    <span className="text-stone-500 font-medium border-r border-stone-200 pr-3 mr-3 md:text-lg">
                      +91
                    </span>
                    <input
                      type="tel"
                      value={mobile}
                      onChange={(e) => setMobile(e.target.value.replace(/\D/g, "").slice(0, 10))}
                      placeholder="98765 43210"
                      className="flex-1 py-3 bg-transparent outline-none font-medium text-stone-800 md:py-4 md:text-lg placeholder:text-stone-400"
                    />
                  </div>
                </div>

                <Button
                  onClick={handleSendOtp}
                  disabled={mobile.length < 10 || isSending}
                  fullWidth
                  className="md:text-xl md:py-4 md:rounded-2xl bg-gradient-to-r from-[#3998de] to-[#3998de] hover:from-[#3998de] hover:to-[#3998de] focus:outline-none focus:ring-2 focus:ring-[#3998de]/30"
                >
                  {isSending ? "Sending..." : "Send OTP"}
                </Button>
              </>
            ) : (
              <>
                <div>
                  <label className="block text-xs font-bold uppercase text-stone-400 mb-1 md:text-sm">
                    Enter OTP
                  </label>
                  <input
                    type="text"
                    value={otp}
                    onChange={(e) => setOtp(e.target.value.replace(/\D/g, "").slice(0, 6))}
                    placeholder="Enter 6 digits"
                    className="w-full py-3 px-4 text-center text-2xl tracking-widest border border-stone-200 rounded-xl bg-stone-50 focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] md:py-4 md:text-3xl md:rounded-2xl placeholder:text-stone-400"
                  />
                  <p className="text-xs text-center text-stone-400 mt-2 md:text-sm">
                    Sent to +91 {mobile}
                  </p>
                </div>

                <Button
                  onClick={handleVerifyOtp}
                  disabled={otp.length < 6 || isVerifying}
                  fullWidth
                  className="md:text-xl md:py-4 md:rounded-2xl bg-gradient-to-r from-[#3998de] to-[#3998de] hover:from-[#3998de] hover:to-[#3998de] focus:outline-none focus:ring-2 focus:ring-[#3998de]/30"
                >
                  {isVerifying ? "Verifying..." : "Verify & Login"}
                </Button>

                <button
                  onClick={() => {
                    setStep("mobile");
                    setOtp("");
                    setRequestId("");
                    setErrorMessage("");
                  }}
                  className="w-full text-xs text-[#3998de] font-medium py-2 md:text-sm hover:underline"
                >
                  Change Number
                </button>
              </>
            )}

            {errorMessage ? (
              <div className="flex items-start gap-2 text-xs text-red-600 bg-red-50 border border-red-100 p-3 rounded-xl">
                <AlertCircle size={14} />
                <span>{errorMessage}</span>
              </div>
            ) : null}
          </div>

          <div className="mt-8 text-center md:mt-10">
            <p className="text-stone-500 text-sm md:text-base">New to Snoutiq?</p>
            <button onClick={onRegisterClick} className="text-[#3998de] font-bold text-sm hover:underline mt-1 md:text-lg">
              Register as a Partner
            </button>
          </div>
        </div>
      </PageWrap>
    </div>
  );
};

// ---------------- 2) Vet Registration Screen ----------------

export const VetRegisterScreen = ({ onSubmit, onBack }) => {
  const [form, setForm] = useState({
    vetFullName: "",
    clinicName: "",
    shortIntro: "",
    whatsappNumber: "",
    email: "",
    vetCity: "",
    degree: "",
    degreeOther: "",
    yearsOfExperience: "",
    doctorLicense: "",
    responseTimeDay: "",
    responseTimeNight: "",
    freeFollowUp: "",
    payoutMethod: "upi",
    payoutDetail: "",
    doctorImageUrl: "", // optional (if you want URL mode)
  });

  const [specializations, setSpecializations] = useState([]);
  const [specializationOther, setSpecializationOther] = useState("");
  const [breakStart, setBreakStart] = useState("");
  const [breakEnd, setBreakEnd] = useState("");
  const [breakTimes, setBreakTimes] = useState([]);
  const [dayPrice, setDayPrice] = useState("");
  const [nightPrice, setNightPrice] = useState("");
  const [agreement1, setAgreement1] = useState(false);
  const [agreement2, setAgreement2] = useState(false);

  const [doctorImageFile, setDoctorImageFile] = useState(null);
  const [doctorImagePreview, setDoctorImagePreview] = useState("");
  const [isImageProcessing, setIsImageProcessing] = useState(false);
  const [imageError, setImageError] = useState("");
  const [showImageUrl, setShowImageUrl] = useState(false); // keep if you want URL option

  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [showErrors, setShowErrors] = useState(false);
  const breakStartRef = useRef(null);
  const breakEndRef = useRef(null);

  const agreed = agreement1 && agreement2;
  const updateForm = (key) => (e) => setForm((prev) => ({ ...prev, [key]: e.target.value }));

  useEffect(() => {
    if (doctorImageFile) {
      const previewUrl = URL.createObjectURL(doctorImageFile);
      setDoctorImagePreview(previewUrl);
      return () => URL.revokeObjectURL(previewUrl);
    }
    const trimmedUrl = form.doctorImageUrl.trim();
    if (trimmedUrl) {
      setDoctorImagePreview(trimmedUrl);
      return undefined;
    }
    setDoctorImagePreview("");
    return undefined;
  }, [doctorImageFile, form.doctorImageUrl]);

  const calculateCommission = (priceStr) => {
    const price = parseFloat(priceStr);
    if (isNaN(price) || price === 0) return null;
    const commission = Math.max(price * 0.25, 99);
    const earning = price - commission;
    return { commission: Math.ceil(commission), earning: Math.floor(earning) };
  };

  const dayMath = calculateCommission(dayPrice);
  const nightMath = calculateCommission(nightPrice);

  const inputBase = INPUT_BASE_CLASS;

  const toggleSpecialization = (value) => {
    setSpecializations((prev) =>
      prev.includes(value) ? prev.filter((item) => item !== value) : [...prev, value]
    );
  };

  const formatTimeLabel = (value) => {
    if (!value) return "";
    const [hours, minutes] = value.split(":").map((part) => Number(part));
    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return value;
    const hour12 = ((hours + 11) % 12) + 1;
    const period = hours >= 12 ? "PM" : "AM";
    return `${hour12}:${String(minutes).padStart(2, "0")} ${period}`;
  };

  const addBreakTime = () => {
    if (!breakStart || !breakEnd) return;
    const startLabel = formatTimeLabel(breakStart);
    const endLabel = formatTimeLabel(breakEnd);
    const label = startLabel && endLabel ? `${startLabel} - ${endLabel}` : "";
    if (!label || breakTimes.includes(label)) return;
    setBreakTimes((prev) => [...prev, label]);
    setBreakStart("");
    setBreakEnd("");
  };

  const removeBreakTime = (value) => {
    setBreakTimes((prev) => prev.filter((item) => item !== value));
  };

  const handleDoctorImageFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setImageError("");
    setIsImageProcessing(true);

    try {
      const compressedFile = await compressToFile(file);
      setDoctorImageFile(compressedFile);
      setShowImageUrl(false);
      setForm((prev) => ({ ...prev, doctorImageUrl: "" }));
    } catch (err) {
      setDoctorImageFile(null);
      setImageError(
        err?.message || "Image compress failed. Try another image or use a URL."
      );
      setShowImageUrl(true);
    } finally {
      setIsImageProcessing(false);
    }
  };

  const handleDoctorImageUrlChange = (e) => {
    const nextValue = e.target.value;
    setForm((prev) => ({ ...prev, doctorImageUrl: nextValue }));
    setDoctorImageFile(null);
    if (nextValue.trim().length > IMAGE_URL_LIMIT) {
      setImageError(`Image URL must be ${IMAGE_URL_LIMIT} characters or less.`);
    } else {
      setImageError("");
    }
  };

  const handleUseImageUrl = () => {
    setShowImageUrl(true);
    setDoctorImageFile(null);
    setImageError("");
  };

  const handleUseImageUpload = () => {
    setShowImageUrl(false);
    setForm((prev) => ({ ...prev, doctorImageUrl: "" }));
    setImageError("");
  };

  const clearImage = () => {
    setDoctorImageFile(null);
    setForm((prev) => ({ ...prev, doctorImageUrl: "" }));
    setImageError("");
  };

  const normalizedDegree = form.degree === "Other" ? form.degreeOther.trim() : form.degree;

  const selectedSpecs = specializations.filter((spec) => spec !== "Other");
  if (specializations.includes("Other") && specializationOther.trim()) {
    selectedSpecs.push(specializationOther.trim());
  }

  const payoutReady = form.payoutDetail.trim();
  const degreeReady = normalizedDegree.trim();
  const whatsappReady = form.whatsappNumber.trim().length >= 10;

  const trimmedImageUrl = form.doctorImageUrl.trim();
  const urlReady =
    trimmedImageUrl.length > 0 && trimmedImageUrl.length <= IMAGE_URL_LIMIT;
  const imageReady = Boolean(doctorImageFile) || urlReady;

  const canSubmit =
    agreed &&
    form.vetFullName.trim() &&
    form.clinicName.trim() &&
    whatsappReady &&
    form.email.trim() &&
    form.shortIntro.trim() &&
    form.vetCity.trim() &&
    form.doctorLicense.trim() &&
    imageReady &&
    degreeReady &&
    form.yearsOfExperience.trim() &&
    selectedSpecs.length > 0 &&
    form.responseTimeDay &&
    form.responseTimeNight &&
    breakTimes.length > 0 &&
    dayPrice &&
    nightPrice &&
    form.freeFollowUp &&
    payoutReady &&
    !imageError &&
    !isImageProcessing;

  const handleSubmit = async () => {
    if (submitting) return;
    if (!canSubmit) {
      setShowErrors(true);
      if (!imageReady) {
        setImageError((prev) => prev || "Please upload a photo or add a valid URL.");
      }
      return;
    }

    setSubmitting(true);
    setSubmitError("");
    setShowErrors(false);

    try {
      const payload = {
        vet_name: form.clinicName.trim(),
        vet_email: form.email.trim(),
        vet_mobile: form.whatsappNumber.trim(),
        vet_city: form.vetCity.trim(),
        doctor_name: form.vetFullName.trim(),
        doctor_email: form.email.trim(),
        doctor_mobile: form.whatsappNumber.trim(),
        doctor_license: form.doctorLicense.trim(),
        doctor_image: trimmedImageUrl || undefined,
        degree: degreeReady,
        years_of_experience: form.yearsOfExperience.trim(),
        specialization_select_all_that_apply: selectedSpecs,
        response_time_for_online_consults_day: form.responseTimeDay,
        response_time_for_online_consults_night: form.responseTimeNight,
        break_do_not_disturb_time_example_2_4_pm: breakTimes,
        do_you_offer_a_free_follow_up_within_3_days_after_a_consulta:
          form.freeFollowUp === "yes" ? "Yes" : "No",
        commission_and_agreement: agreed ? "Agreed" : "Not agreed",
        video_day_rate: Number(dayPrice),
        video_night_rate: Number(nightPrice),
        short_intro: form.shortIntro.trim(),
        preferred_payout_method: form.payoutMethod,
        preferred_payout_detail: payoutReady,
      };

      let data = null;
      if (doctorImageFile) {
        const fd = new FormData();
        Object.entries(payload).forEach(([key, value]) => {
          if (Array.isArray(value)) {
            value.forEach((item) => fd.append(`${key}[]`, item));
            return;
          }
          if (value !== undefined && value !== null && value !== "") {
            fd.append(key, String(value));
          }
        });
        fd.append("doctor_image_file", doctorImageFile);

        const res = await fetch(`${apiBaseUrl()}/api/excell-export/import`, {
          method: "POST",
          body: fd,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json?.success === false) {
          throw new Error(json?.message || "Image upload failed.");
        }
        data = json;
      } else {
        if (trimmedImageUrl && trimmedImageUrl.length > IMAGE_URL_LIMIT) {
          throw new Error(
            `Image URL must be ${IMAGE_URL_LIMIT} characters or less.`
          );
        }
        data = await apiPost("/api/excell-export/import", payload);
      }

      onSubmit?.(data);
    } catch (error) {
      const message = error?.message || "Failed to submit application.";
      setSubmitError(message);
      if (
        message.toLowerCase().includes("image") ||
        message.toLowerCase().includes("upload") ||
        doctorImageFile
      ) {
        setImageError(message);
        setShowImageUrl(true);
      }
    } finally {
      setSubmitting(false);
    }
  };

  const pricingProps = {
    dayPrice,
    nightPrice,
    setDayPrice,
    setNightPrice,
    dayMath,
    nightMath,
    form,
    updateForm,
    agreement1,
    setAgreement1,
    agreement2,
    setAgreement2,
    submitError,
    showErrors,
    canSubmit,
    submitting,
    handleSubmit,
  };

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <VetHeader onBack={onBack} title="Partner Registration" />

      <PageWrap>
        <div className="flex-1 px-4 py-6 pb-32 overflow-y-auto no-scrollbar md:px-0 md:py-12">
          <p className="text-sm text-stone-500 mb-6 px-2 md:px-0 md:text-lg">
            Join India&apos;s most trusted network of empathetic veterinarians.
          </p>
          <p className="text-xs text-stone-400 mb-6 px-2 md:px-0 md:text-sm">
            All fields marked * are required.
          </p>

          <div className="space-y-6 md:space-y-0 md:grid md:grid-cols-12 md:gap-10 lg:gap-12">
            {/* LEFT */}
            <div className="md:col-span-7 lg:col-span-8 space-y-6">
              {/* Profile Image */}
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl md:shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                <div className="flex flex-col items-center gap-5 md:flex-row md:items-center md:gap-6">
                  <div className="relative flex flex-col items-center gap-2">
                    <input
                      id="doctorImageUpload"
                      type="file"
                      accept="image/*"
                      required
                      className="hidden"
                      onChange={handleDoctorImageFile}
                    />
                    <label htmlFor="doctorImageUpload" className="cursor-pointer">
                      <div className="h-24 w-24 md:h-32 md:w-32 rounded-full border-2 border-[#3998de]/20 bg-[#3998de]/10 flex items-center justify-center overflow-hidden shadow-sm transition-shadow hover:shadow-md">
                        {doctorImagePreview ? (
                          <img
                            src={doctorImagePreview}
                            alt="Doctor preview"
                            className="h-full w-full object-cover"
                          />
                        ) : (
                          <div className="flex flex-col items-center text-[#3998de]">
                            <Camera size={26} />
                            <span className="text-[10px] font-semibold uppercase tracking-wide mt-1">
                              Upload
                            </span>
                          </div>
                        )}
                      </div>
                    </label>
                  </div>

                  <div className="w-full text-center md:text-left space-y-2">
                    <div>
                      <h3 className="text-sm font-bold text-stone-800">Profile Photo *</h3>
                      <p className="text-xs text-stone-500">
                        We compress and upload your photo, then send the URL.
                      </p>
                    </div>

                    <div className="flex items-center justify-center md:justify-start gap-2">
                      <label
                        htmlFor="doctorImageUpload"
                        className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-stone-200 shadow-sm text-xs text-stone-600 cursor-pointer hover:bg-stone-50 transition-colors"
                      >
                        Choose photo
                      </label>

                      {(doctorImageFile || form.doctorImageUrl.trim()) ? (
                        <button
                          type="button"
                          onClick={clearImage}
                          className="text-xs text-stone-500 hover:text-stone-700"
                        >
                          Remove
                        </button>
                      ) : null}
                    </div>

                    {isImageProcessing ? (
                      <p className="text-xs text-stone-400">Optimizing image...</p>
                    ) : imageError ? (
                      <p className="text-xs text-red-600">{imageError}</p>
                    ) : doctorImageFile ? (
                      <p className="text-xs text-stone-400">
                        Photo ready - it will upload on submit.
                      </p>
                    ) : (
                      <p className="text-xs text-stone-400">
                        JPG/PNG recommended. Square photos look best.
                      </p>
                    )}
                  </div>
                </div>
              </section>

              {/* Basic Details */}
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl md:shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-[#3998de]/10 text-[#3998de] w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    1
                  </span>
                  Basic Details
                </h3>

                <input
                  type="text"
                  placeholder="Vet Full Name *"
                  value={form.vetFullName}
                  onChange={updateForm("vetFullName")}
                  required
                  className={inputBase}
                />
                <input
                  type="text"
                  placeholder="Clinic Name *"
                  value={form.clinicName}
                  onChange={updateForm("clinicName")}
                  required
                  className={inputBase}
                />
                <textarea
                  placeholder="Short Intro *"
                  value={form.shortIntro}
                  onChange={updateForm("shortIntro")}
                  rows={3}
                  required
                  className={`${inputBase} resize-none`}
                />

                <div className="grid grid-cols-2 gap-3">
                  <input
                    type="text"
                    placeholder="City *"
                    value={form.vetCity}
                    onChange={updateForm("vetCity")}
                    required
                    className={inputBase}
                  />
                  <input
                    type="tel"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    placeholder="WhatsApp Number (Active) *"
                    value={form.whatsappNumber}
                    onChange={(e) =>
                      setForm((prev) => ({
                        ...prev,
                        whatsappNumber: e.target.value.replace(/\D/g, ""),
                      }))
                    }
                    required
                    className={inputBase}
                  />
                </div>

                <p className="text-[10px] text-stone-400 flex items-center gap-1 md:text-xs">
                  <Lock size={10} /> Your number is kept private and never shared directly with pet parents.
                </p>

                <input
                  type="email"
                  placeholder="Email Address *"
                  value={form.email}
                  onChange={updateForm("email")}
                  required
                  className={inputBase}
                />
              </section>

              {/* Professional Details */}
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl md:shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-[#3998de]/10 text-[#3998de] w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    2
                  </span>
                  Professional Details
                </h3>

                <input
                  type="text"
                  placeholder="Vet Registration Number *"
                  value={form.doctorLicense}
                  onChange={updateForm("doctorLicense")}
                  required
                  className={inputBase}
                />

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Degree *
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {DEGREE_OPTIONS.map((degree) => (
                      <label
                        key={degree}
                        className="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg text-sm bg-stone-50 md:text-base md:px-4 md:py-3 md:rounded-xl hover:bg-stone-100 transition-colors"
                      >
                        <input
                          type="radio"
                          name="degree"
                          value={degree}
                          checked={form.degree === degree}
                          onChange={updateForm("degree")}
                          required
                          className="accent-[#3998de]"
                        />
                        {degree}
                      </label>
                    ))}
                  </div>

                  {form.degree === "Other" ? (
                    <input
                      type="text"
                      placeholder="Specify degree *"
                      value={form.degreeOther}
                      onChange={updateForm("degreeOther")}
                      required
                      className={inputBase}
                    />
                  ) : null}
                </div>

                <input
                  type="number"
                  placeholder="Years of Experience *"
                  value={form.yearsOfExperience}
                  onChange={updateForm("yearsOfExperience")}
                  onKeyDown={blockNumberInput}
                  onWheel={handleNumberWheel}
                  min="0"
                  required
                  className={inputBase}
                />

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Specialization (Select all that apply) *
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {SPECIALIZATION_OPTIONS.map((spec) => (
                      <label
                        key={spec}
                        className="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg text-sm bg-stone-50 md:text-base md:px-4 md:py-3 md:rounded-xl hover:bg-stone-100 transition-colors"
                      >
                        <input
                          type="checkbox"
                          className="accent-[#3998de]"
                          checked={specializations.includes(spec)}
                          onChange={() => toggleSpecialization(spec)}
                        />
                        {spec}
                      </label>
                    ))}
                  </div>

                  {specializations.includes("Other") ? (
                    <input
                      type="text"
                      placeholder="Other specialization *"
                      value={specializationOther}
                      onChange={(e) => setSpecializationOther(e.target.value)}
                      required
                      className={inputBase}
                    />
                  ) : null}
                </div>
              </section>

              {/* Availability & Timing */}
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl md:shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-[#3998de]/10 text-[#3998de] w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    3
                  </span>
                  Availability & Timing
                </h3>

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Response Time for Online Consults (Day) *
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {RESPONSE_TIME_DAY_OPTIONS.map((option) => (
                      <label
                        key={option}
                        className="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg text-sm bg-stone-50 md:text-base md:px-4 md:py-3 md:rounded-xl hover:bg-stone-100 transition-colors"
                      >
                        <input
                          type="radio"
                          name="responseTimeDay"
                          value={option}
                          checked={form.responseTimeDay === option}
                          onChange={updateForm("responseTimeDay")}
                          required
                          className="accent-[#3998de]"
                        />
                        {option}
                      </label>
                    ))}
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Response Time for Online Consults (Night) *
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {RESPONSE_TIME_NIGHT_OPTIONS.map((option) => (
                      <label
                        key={option}
                        className="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg text-sm bg-stone-50 md:text-base md:px-4 md:py-3 md:rounded-xl hover:bg-stone-100 transition-colors"
                      >
                        <input
                          type="radio"
                          name="responseTimeNight"
                          value={option}
                          checked={form.responseTimeNight === option}
                          onChange={updateForm("responseTimeNight")}
                          required
                          className="accent-[#3998de]"
                        />
                        {option}
                      </label>
                    ))}
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Break / do-not-disturb time (example: 2-4 PM) *
                  </label>
                  <div className="grid gap-2 sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                    <div className="flex items-center gap-2 rounded-xl border border-stone-200 bg-stone-50 px-3 md:px-4 md:rounded-2xl focus-within:ring-2 focus-within:ring-[#3998de]/30 focus-within:border-[#3998de]">
                      <button
                        type="button"
                        onClick={() => {
                          const el = breakStartRef.current;
                          if (!el) return;
                          if (typeof el.showPicker === "function") {
                            el.showPicker();
                          } else {
                            el.focus();
                          }
                        }}
                        className="text-[#3998de] shrink-0 p-1"
                        aria-label="Choose break start time"
                      >
                        <Clock size={16} />
                      </button>
                      <input
                        ref={breakStartRef}
                        type="time"
                        value={breakStart}
                        onChange={(e) => setBreakStart(e.target.value)}
                        className={`flex-1 bg-transparent py-3 text-sm font-medium outline-none md:py-4 md:text-base ${
                          breakStart ? "text-stone-800" : "text-transparent"
                        }`}
                      />
                    </div>
                    <div className="flex items-center gap-2 rounded-xl border border-stone-200 bg-stone-50 px-3 md:px-4 md:rounded-2xl focus-within:ring-2 focus-within:ring-[#3998de]/30 focus-within:border-[#3998de]">
                      <button
                        type="button"
                        onClick={() => {
                          const el = breakEndRef.current;
                          if (!el) return;
                          if (typeof el.showPicker === "function") {
                            el.showPicker();
                          } else {
                            el.focus();
                          }
                        }}
                        className="text-[#3998de] shrink-0 p-1"
                        aria-label="Choose break end time"
                      >
                        <Clock size={16} />
                      </button>
                      <input
                        ref={breakEndRef}
                        type="time"
                        value={breakEnd}
                        onChange={(e) => setBreakEnd(e.target.value)}
                        className={`flex-1 bg-transparent py-3 text-sm font-medium outline-none md:py-4 md:text-base ${
                          breakEnd ? "text-stone-800" : "text-transparent"
                        }`}
                      />
                    </div>
                    <Button
                      type="button"
                      onClick={addBreakTime}
                      className="w-full sm:w-auto px-4"
                    >
                      Add
                    </Button>
                  </div>

                  {breakTimes.length ? (
                    <div className="flex flex-wrap gap-2">
                      {breakTimes.map((time) => (
                        <span
                          key={time}
                          className="inline-flex items-center gap-2 rounded-full bg-stone-100 text-stone-600 text-xs px-3 py-1"
                        >
                          {time}
                          <button
                            type="button"
                            onClick={() => removeBreakTime(time)}
                            className="text-stone-400 hover:text-stone-600"
                          >
                            x
                          </button>
                        </span>
                      ))}
                    </div>
                  ) : null}
                </div>
              </section>
            </div>

            <div className="md:col-span-5 lg:col-span-4 space-y-6">
              <div className="md:sticky md:top-28 space-y-4">
                <PricingSection {...pricingProps} />
              </div>
            </div>
          </div>

          <div className="h-24 md:hidden" />
        </div>
      </PageWrap>

      {/* Mobile bottom button */}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button onClick={handleSubmit} fullWidth disabled={!canSubmit || submitting} className={!canSubmit || submitting ? "opacity-50" : ""}>
          {submitting ? "Submitting..." : "Submit Application"}
        </Button>
      </div>

    </div>
  );
};

// ---------------- 3) Pending Approval Screen ----------------

export const VetPendingScreen = ({ onHome }) => {
  return (
    <div className="min-h-screen bg-white flex flex-col items-center justify-center p-8 text-center animate-fade-in md:bg-gradient-to-b md:from-white md:to-calm-bg md:p-16 md:py-24 md:rounded-3xl md:shadow-lg">
      <PageWrap>
        <div className="w-full max-w-sm mx-auto md:max-w-2xl md:py-10">
          <div className="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mb-6 text-amber-600 mx-auto md:w-24 md:h-24 ring-1 ring-amber-200/60">
            <Clock size={40} className="md:hidden" />
            <Clock size={48} className="hidden md:block" />
          </div>

          <h2 className="text-2xl font-bold text-stone-800 mb-2 md:text-4xl">
            Application Received
          </h2>
          <p className="text-stone-500 mb-8 max-w-[280px] mx-auto leading-relaxed md:max-w-2xl md:text-lg">
            Thanks, Doctor. Our team will verify your credentials and activate your profile within 24-48 hours.
          </p>

          <div className="w-full max-w-xs mx-auto space-y-3 md:max-w-sm">
            <Button onClick={onHome} variant="secondary" fullWidth className="md:text-lg md:py-4 md:rounded-2xl">
              Back to Home
            </Button>
          </div>
        </div>
      </PageWrap>
    </div>
  );
};

// ---------------- 4) Vet Dashboard Screen (unchanged from your version) ----------------
// Keep your existing VetDashboardScreen here as-is.
// (No image upload needed in dashboard, so no changes required)

export const VetDashboardScreen = ({ onLogout, auth: authFromProps }) => {
  const [auth, setAuth] = useState(() => authFromProps || loadVetAuth());
  const [dashboardData, setDashboardData] = useState(null);
  const [transactions, setTransactions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState("");
  const [activeTransaction, setActiveTransaction] = useState(null);
  const [showPatientModal, setShowPatientModal] = useState(false);
  const [showPrescriptionModal, setShowPrescriptionModal] = useState(false);
  const [prescriptionSubmitting, setPrescriptionSubmitting] = useState(false);
  const [prescriptionError, setPrescriptionError] = useState("");
  const [prescriptionSuccess, setPrescriptionSuccess] = useState(false);
  const [prescriptionForm, setPrescriptionForm] = useState({
    visitCategory: "Follow-up",
    caseSeverity: "general",
    notes: "",
    temperature: "",
    weight: "",
    heartRate: "",
    medications: [{ name: "", dosage: "", frequency: "", duration: "" }],
    recordFile: null,
  });

  useEffect(() => {
    if (authFromProps) {
      setAuth(authFromProps);
      return;
    }
    const storedAuth = loadVetAuth();
    if (storedAuth) {
      setAuth(storedAuth);
    }
  }, [authFromProps]);

  useEffect(() => {
    if (auth) {
      saveVetAuth(auth);
    }
  }, [auth]);

  const formatAmount = (value) => {
    const num = Number(value);
    if (!Number.isFinite(num)) return "Rs. 0";
    return `Rs. ${num.toLocaleString("en-IN")}`;
  };

  const formatDate = (value) => {
    if (!value) return "NA";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString("en-IN", {
      day: "2-digit",
      month: "short",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const statusClass = (status) => {
    const key = (status || "").toLowerCase();
    if (key === "pending") return "bg-amber-50 text-amber-700";
    if (["paid", "success", "captured", "completed"].includes(key)) {
      return "bg-emerald-50 text-emerald-700";
    }
    if (["failed", "cancelled", "canceled", "refunded"].includes(key)) {
      return "bg-rose-50 text-rose-700";
    }
    return "bg-slate-100 text-slate-600";
  };

  const statusLabel = (status) => (status ? status.replace(/_/g, " ") : "unknown");

  const doctorId = normalizeId(
    auth?.doctor_id ||
      auth?.doctor?.id ||
      auth?.doctor?.doctor_id ||
      auth?.doctor?.doctorId
  );

  const clinicIdRaw = normalizeId(
    auth?.clinic_id ||
      auth?.doctor?.clinic_id ||
      auth?.doctor?.vet_registeration_id ||
      auth?.doctor?.vet_registration_id ||
      auth?.doctor?.clinicId
  );

  const authToken =
    auth?.token ||
    auth?.access_token ||
    auth?.doctor?.token ||
    auth?.doctor?.access_token ||
    "";

  const resolveTransactionIds = (transaction) => {
    const metadata = transaction?.metadata || {};
    const notes = metadata?.notes || {};
    return {
      userId: transaction?.user?.id || metadata?.user_id || notes?.user_id || "",
      petId: transaction?.pet?.id || metadata?.pet_id || notes?.pet_id || "",
      doctorId:
        doctorId ||
        normalizeId(transaction?.doctor?.id || metadata?.doctor_id || notes?.doctor_id),
      clinicId:
        clinicIdRaw || normalizeId(metadata?.clinic_id || notes?.clinic_id),
    };
  };

  const resetPrescriptionForm = () => {
    setPrescriptionForm({
      visitCategory: "Follow-up",
      caseSeverity: "general",
      notes: "",
      temperature: "",
      weight: "",
      heartRate: "",
      medications: [{ name: "", dosage: "", frequency: "", duration: "" }],
      recordFile: null,
    });
    setPrescriptionError("");
    setPrescriptionSuccess(false);
  };

  const openPatientModal = (transaction) => {
    setActiveTransaction(transaction);
    setShowPatientModal(true);
  };

  const openPrescriptionModal = (transaction) => {
    setActiveTransaction(transaction);
    resetPrescriptionForm();
    setShowPrescriptionModal(true);
  };

  const closePatientModal = () => {
    setShowPatientModal(false);
    setActiveTransaction(null);
  };

  const closePrescriptionModal = () => {
    setShowPrescriptionModal(false);
    setActiveTransaction(null);
    setPrescriptionSubmitting(false);
  };

  const updatePrescriptionField = (key) => (event) => {
    setPrescriptionForm((prev) => ({ ...prev, [key]: event.target.value }));
  };

  const updateMedication = (index, key) => (event) => {
    const value = event.target.value;
    setPrescriptionForm((prev) => ({
      ...prev,
      medications: prev.medications.map((med, idx) =>
        idx === index ? { ...med, [key]: value } : med
      ),
    }));
  };

  const addMedication = () => {
    setPrescriptionForm((prev) => ({
      ...prev,
      medications: [...prev.medications, { name: "", dosage: "", frequency: "", duration: "" }],
    }));
  };

  const removeMedication = (index) => {
    setPrescriptionForm((prev) => ({
      ...prev,
      medications:
        prev.medications.length > 1
          ? prev.medications.filter((_, idx) => idx !== index)
          : prev.medications,
    }));
  };

  const handleRecordFile = (event) => {
    const file = event.target.files?.[0] || null;
    setPrescriptionForm((prev) => ({ ...prev, recordFile: file }));
  };

  const handlePrescriptionSubmit = async (event) => {
    event.preventDefault();
    if (!activeTransaction || prescriptionSubmitting) return;

    setPrescriptionSubmitting(true);
    setPrescriptionError("");
    setPrescriptionSuccess(false);

    const { userId, petId, doctorId, clinicId } = resolveTransactionIds(activeTransaction);
    if (!userId || !clinicId) {
      setPrescriptionError("Missing patient or clinic data. Please refresh and try again.");
      setPrescriptionSubmitting(false);
      return;
    }

    const medsPayload = prescriptionForm.medications
      .map((med) => ({
        name: med.name.trim(),
        dosage: med.dosage.trim(),
        frequency: med.frequency.trim(),
        duration: med.duration.trim(),
      }))
      .filter((med) => med.name || med.dosage || med.frequency || med.duration);

    const fd = new FormData();
    fd.append("user_id", String(userId));
    fd.append("clinic_id", String(clinicId));
    if (doctorId) fd.append("doctor_id", String(doctorId));
    if (petId) fd.append("pet_id", String(petId));
    fd.append("visit_category", prescriptionForm.visitCategory);
    fd.append("case_severity", prescriptionForm.caseSeverity);
    fd.append("notes", prescriptionForm.notes);
    fd.append("temperature", String(prescriptionForm.temperature));
    fd.append("weight", String(prescriptionForm.weight));
    fd.append("heart_rate", String(prescriptionForm.heartRate));
    fd.append("medications_json", JSON.stringify(medsPayload));
    if (prescriptionForm.recordFile) {
      fd.append("record_file", prescriptionForm.recordFile);
    }

    try {
      const token =
        auth?.token ||
        auth?.access_token ||
        auth?.doctor?.token ||
        auth?.doctor?.access_token ||
        "";
      const headers = token
        ? { Authorization: `Bearer ${token}`, Accept: "application/json" }
        : { Accept: "application/json" };
      const res = await fetch(`${apiBaseUrl()}/api/medical-records`, {
        method: "POST",
        headers,
        body: fd,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data?.success === false) {
        throw new Error(data?.message || "Failed to save prescription.");
      }
      setPrescriptionSuccess(true);
    } catch (error) {
      setPrescriptionError(error?.message || "Failed to save prescription.");
    } finally {
      setPrescriptionSubmitting(false);
    }
  };

  useEffect(() => {
    if (!auth) return;
    if (!doctorId) {
      setLoadError("Missing doctor ID. Please log in again.");
      setIsLoading(false);
      return;
    }

    const controller = new AbortController();

    const fetchTransactions = async () => {
      setIsLoading(true);
      setLoadError("");
      try {
        let clinicId = clinicIdRaw;
        if (!clinicId) {
          const resolvedClinicId = await fetchClinicIdForDoctor(
            doctorId,
            authToken,
            controller.signal
          );
          if (resolvedClinicId && resolvedClinicId !== clinicIdRaw) {
            setAuth((prev) => ({
              ...(prev || {}),
              clinic_id: resolvedClinicId,
              doctor: {
                ...(prev?.doctor || {}),
                clinic_id: resolvedClinicId,
                vet_registeration_id: resolvedClinicId,
              },
            }));
            clinicId = resolvedClinicId;
          }
        }

        if (!clinicId) {
          throw new Error("Missing clinic ID. Please log in again.");
        }

        const url = `${apiBaseUrl()}/api/excell-export/transactions?doctor_id=${encodeURIComponent(
          doctorId
        )}&clinic_id=${encodeURIComponent(clinicId)}`;
        const headers = authToken
          ? { Authorization: `Bearer ${authToken}`, Accept: "application/json" }
          : { Accept: "application/json" };
        const res = await fetch(url, {
          signal: controller.signal,
          headers,
          credentials: "include",
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        if (!data?.success) {
          throw new Error(data?.message || "Failed to load transactions.");
        }

        if (import.meta?.env?.DEV) {
          console.log("Vet dashboard data:", data);
        }
        setDashboardData(data);
        setTransactions(Array.isArray(data?.transactions) ? data.transactions : []);
      } catch (error) {
        if (error?.name !== "AbortError") {
          setLoadError(error?.message || "Failed to load transactions.");
        }
      } finally {
        setIsLoading(false);
      }
    };

    fetchTransactions();
    return () => controller.abort();
  }, [auth, authToken, clinicIdRaw, doctorId]);

  const handleLogout = () => {
    clearVetAuth();
    onLogout?.();
  };

  const doctorName = auth?.doctor?.doctor_name || auth?.doctor_name || auth?.doctor?.name || "Doctor";
  const doctorEmail = auth?.doctor?.doctor_email || auth?.doctor_email || auth?.email || "";
  const doctorPhone = auth?.doctor?.doctor_mobile || auth?.doctor_mobile || auth?.phone || "";

  const initials =
    doctorName
      .split(" ")
      .filter(Boolean)
      .map((part) => part[0])
      .slice(0, 2)
      .join("")
      .toUpperCase() || "DR";

  const { totalAmount, totalTransactions, pendingCount, completedCount, failedCount, latestTransactions, lastUpdated } =
    useMemo(() => {
      const totalAmountValue = dashboardData?.total_amount_inr ?? 0;
      const totalTransactionsValue = dashboardData?.total_transactions ?? transactions.length;
      const pendingValue = transactions.filter((item) => (item?.status || "").toLowerCase() === "pending").length;
      const completedValue = transactions.filter((item) =>
        ["paid", "success", "captured", "completed"].includes((item?.status || "").toLowerCase())
      ).length;
      const failedValue = transactions.filter((item) =>
        ["failed", "cancelled", "canceled", "refunded"].includes((item?.status || "").toLowerCase())
      ).length;

      const latest = transactions
        .slice()
        .sort((a, b) => new Date(b?.created_at || 0).getTime() - new Date(a?.created_at || 0).getTime())
        .slice(0, 6);

      const lastUpdatedValue = latest[0]?.updated_at || latest[0]?.created_at || "";

      return {
        totalAmount: totalAmountValue,
        totalTransactions: totalTransactionsValue,
        pendingCount: pendingValue,
        completedCount: completedValue,
        failedCount: failedValue,
        latestTransactions: latest,
        lastUpdated: lastUpdatedValue,
      };
    }, [dashboardData, transactions]);

  const headerBg = "bg-[#0B4D67]";
  const pageBg = "bg-[#F6F7FB]";
  const card = "bg-white border border-stone-100 shadow-[0_8px_30px_rgba(0,0,0,0.06)]";
  const cardSoft = "bg-white border border-stone-100 shadow-sm";
  const actionButton =
    "inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-semibold transition-colors";

  return (
    <div
      className={`min-h-screen ${pageBg} flex flex-col animate-slide-up`}
      data-vet-dashboard="true"
    >
      <div
        className={`
          ${headerBg} text-white pt-8 pb-12 px-6 relative
          w-screen max-w-none left-1/2 right-1/2 -mx-[50vw]
          md:px-10 md:pt-12 md:pb-16 lg:px-16
          md:rounded-none rounded-b-[2rem]
          shadow-[0_18px_60px_rgba(11,77,103,0.35)]
        `}
      >
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between md:gap-6">
          <div className="flex items-center gap-3">
            <div
              className={`
                w-12 h-12 rounded-full
                bg-white/15 border border-white/20
                flex items-center justify-center text-xl font-bold
                md:w-16 md:h-16 md:text-2xl
              `}
            >
              {initials}
            </div>

            <div>
              <h1 className="font-bold text-lg md:text-2xl">{doctorName}</h1>
              <div className="mt-2 text-xs text-white/75 md:text-sm">
                Verified veterinary partner
              </div>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <span className="hidden md:inline text-xs text-white/70">
              Last updated: {isLoading ? "Loading..." : formatDate(lastUpdated)}
            </span>
            <button
              onClick={handleLogout}
              className="text-xs text-white/80 hover:text-white font-semibold md:text-sm focus:outline-none focus:ring-2 focus:ring-white/30 rounded-lg px-3 py-2 bg-white/10 hover:bg-white/15 transition-colors"
            >
              Logout
            </button>
          </div>
        </div>

        <div className="mt-6 flex flex-wrap gap-2 text-xs text-white/80 md:text-sm">
          <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1">
            Earnings: {isLoading ? "Loading..." : formatAmount(totalAmount)}
          </span>
          <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1">
            Transactions: {isLoading ? "Loading..." : totalTransactions}
          </span>
          <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1">
            Pending: {isLoading ? "Loading..." : pendingCount}
          </span>
        </div>
      </div>

      <PageWrap>
        <div className="px-4 mt-6 mb-8 grid grid-cols-2 gap-3 md:px-0 md:grid-cols-3 md:gap-5">
          <div className={`${card} p-4 rounded-2xl md:p-7`}>
            <p className="text-stone-400 text-xs uppercase font-bold mb-1 md:text-sm">Total Amount</p>
            <p className="text-2xl font-bold text-stone-900 md:text-4xl">{isLoading ? "Loading..." : formatAmount(totalAmount)}</p>
            <p className="text-[10px] text-stone-400 mt-1 md:text-sm">All time</p>
          </div>

          <div className={`${card} p-4 rounded-2xl md:p-7`}>
            <p className="text-stone-400 text-xs uppercase font-bold mb-1 md:text-sm">Total Transactions</p>
            <p className="text-2xl font-bold text-stone-900 md:text-4xl">{isLoading ? "Loading..." : totalTransactions}</p>
            <p className="text-[10px] text-stone-400 mt-1 md:text-sm">All time</p>
          </div>

          <div className={`${card} p-4 rounded-2xl md:p-7`}>
            <p className="text-stone-400 text-xs uppercase font-bold mb-1 md:text-sm">Pending</p>
            <p className="text-2xl font-bold text-stone-900 md:text-4xl">{isLoading ? "Loading..." : pendingCount}</p>
            <p className="text-[10px] text-stone-400 mt-1 md:text-sm">Awaiting</p>
          </div>
        </div>

        <div className="flex-1 px-4 pb-20 overflow-y-auto no-scrollbar space-y-6 md:px-0 md:pb-24 md:grid md:grid-cols-12 md:gap-10 md:space-y-0">
          <div className="md:col-span-7 lg:col-span-8 space-y-6">
            {loadError ? (
              <div className="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-100 p-3 rounded-xl">
                <AlertCircle size={16} />
                <span>{loadError}</span>
              </div>
            ) : null}

            <section>
              <h3 className="font-bold text-stone-800 mb-3 text-sm md:text-lg">Transaction Summary</h3>

              <div className={`${cardSoft} rounded-2xl p-2 md:rounded-3xl`}>
                <div className="grid grid-cols-3 divide-x divide-stone-100">
                  <div className="p-4 text-center md:p-7">
                    <p className="text-stone-400 text-[10px] uppercase font-bold mb-1 md:text-xs">Completed</p>
                    <div className="font-bold text-stone-900 md:text-xl">{isLoading ? "-" : completedCount}</div>
                  </div>

                  <div className="p-4 text-center md:p-7">
                    <p className="text-stone-400 text-[10px] uppercase font-bold mb-1 md:text-xs">Pending</p>
                    <div className="font-bold text-stone-900 md:text-xl">{isLoading ? "-" : pendingCount}</div>
                  </div>

                  <div className="p-4 text-center md:p-7">
                    <p className="text-stone-400 text-[10px] uppercase font-bold mb-1 md:text-xs">Failed</p>
                    <div className="font-bold text-stone-900 md:text-xl">{isLoading ? "-" : failedCount}</div>
                  </div>
                </div>
              </div>
            </section>

            <section>
              <h3 className="font-bold text-stone-800 mb-3 text-sm flex items-center gap-2 md:text-lg">
                <History size={16} />
                Recent Transactions
              </h3>

              <div className={`${cardSoft} rounded-2xl overflow-hidden md:rounded-3xl`}>
                {isLoading ? (
                  <div className="px-4 py-6 text-sm text-stone-500 md:px-7">Loading transactions...</div>
                ) : latestTransactions.length ? (
                  latestTransactions.map((item, idx) => {
                    const amountInr = item?.amount_inr ?? (item?.amount_paise ? item.amount_paise / 100 : 0);
                    const petName = item?.pet?.name || "Pet";
                    const petMeta = item?.pet?.breed || item?.pet?.pet_type || "Details";
                    const reference = item?.reference || item?.metadata?.order_id || "NA";

                    const { userId, petId, clinicId } = resolveTransactionIds(item);
                    const canOpenPrescription = Boolean(userId && clinicId);
                    const canView = Boolean(item?.user || item?.pet);

                    return (
                      <div
                        key={item?.id || item?.reference || idx}
                        className={`px-4 py-4 flex justify-between items-start md:px-7 md:py-6 hover:bg-stone-50/60 transition-colors ${
                          idx !== latestTransactions.length - 1 ? "border-b border-stone-100" : ""
                        }`}
                      >
                        <div>
                          <p className="font-bold text-stone-900 text-sm md:text-lg">{item?.user?.name || "Pet Parent"}</p>
                          <p className="text-xs text-stone-500 md:text-base">
                            {petName} ({petMeta})
                          </p>
                          <p className="text-[10px] text-stone-400 md:text-sm">
                            Ref: {reference} | {formatDate(item?.created_at)}
                          </p>
                        </div>

                        <div className="text-right flex flex-col items-end gap-2">
                          <p className="font-bold text-stone-900 text-sm md:text-lg">{formatAmount(amountInr)}</p>
                          <span className={`inline-flex items-center px-2 py-1 rounded-full text-[10px] font-semibold ${statusClass(item?.status)}`}>
                            {statusLabel(item?.status)}
                          </span>
                          <div className="flex flex-wrap gap-2 justify-end">
                            <button
                              type="button"
                              onClick={() => openPatientModal(item)}
                              disabled={!canView}
                              className={`${actionButton} border border-stone-200 text-stone-600 hover:bg-stone-50 ${
                                !canView ? "opacity-50 cursor-not-allowed" : ""
                              }`}
                            >
                              View
                            </button>
                            <button
                              type="button"
                              onClick={() => openPrescriptionModal(item)}
                              disabled={!canOpenPrescription}
                              className={`${actionButton} bg-[#3998de] text-white hover:bg-[#2F7FC0] ${
                                !canOpenPrescription ? "opacity-50 cursor-not-allowed" : ""
                              }`}
                            >
                              Prescription
                            </button>
                          </div>
                        </div>
                      </div>
                    );
                  })
                ) : (
                  <div className="px-4 py-6 text-sm text-stone-500 md:px-7">No transactions yet.</div>
                )}
              </div>

              <p className="text-[10px] text-stone-400 mt-2 flex items-center gap-1 md:text-xs">
                <Lock size={12} />
                Patient contact details are hidden for privacy.
              </p>
            </section>
          </div>

          <div className="md:col-span-5 lg:col-span-4 space-y-6">
            <section>
              <h3 className="font-bold text-stone-800 mb-3 text-sm md:text-lg">Account Details</h3>

              <div className={`${cardSoft} p-4 rounded-2xl md:p-7 md:rounded-3xl`}>
                <div className="space-y-4 text-sm">
                  <div>
                    <p className="text-stone-400 text-[10px] uppercase font-bold">Doctor</p>
                    <p className="text-stone-800 font-semibold">{doctorName}</p>
                  </div>
                  {doctorEmail ? (
                    <div>
                      <p className="text-stone-400 text-[10px] uppercase font-bold">Email</p>
                      <p className="text-stone-700">{doctorEmail}</p>
                    </div>
                  ) : null}
                  {doctorPhone ? (
                    <div>
                      <p className="text-stone-400 text-[10px] uppercase font-bold">Phone</p>
                      <p className="text-stone-700">{doctorPhone}</p>
                    </div>
                  ) : null}
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <p className="text-stone-400 text-[10px] uppercase font-bold">Last Updated</p>
                      <p className="text-stone-700">
                        {isLoading ? "Loading..." : formatDate(lastUpdated)}
                      </p>
                    </div>
                  </div>
                  {/* <div>
                    <p className="text-stone-400 text-[10px] uppercase font-bold">Last Updated</p>
                    <p className="text-stone-700">{isLoading ? "Loading..." : formatDate(lastUpdated)}</p>
                  </div> */}
                </div>
              </div>
            </section>

            <div className="bg-[#EAF3FF] p-4 rounded-2xl border border-[#CFE2FF] flex gap-3 md:p-7 md:rounded-3xl">
              <AlertCircle className="text-[#2563EB] flex-shrink-0" size={22} />
              <div className="text-xs text-[#1E3A8A] md:text-base">
                <strong>Pro Tip:</strong> Updating your availability accurately helps you get 3x more consultations.
              </div>
            </div>
          </div>
        </div>
      </PageWrap>

      {showPatientModal ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div className="flex items-center justify-between bg-gradient-to-r from-[#0B4D67] to-[#0E5F7B] px-6 py-4 text-white">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/15">
                  <User size={20} />
                </div>
                <div>
                  <p className="text-xs text-white/70">Patient Details</p>
                  <h3 className="text-lg font-semibold">Consult Overview</h3>
                </div>
              </div>
              <button
                type="button"
                onClick={closePatientModal}
                className="rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
              >
                <X size={16} />
              </button>
            </div>

            <div className="space-y-4 bg-[#F8FAFC] p-6">
              <div className="rounded-2xl border border-stone-100 bg-white p-4">
                <div className="text-xs font-semibold uppercase text-stone-400">Pet Parent</div>
                <div className="mt-2 space-y-1 text-sm text-stone-700">
                  <p className="font-semibold text-stone-900">
                    {activeTransaction?.user?.name || "Not available"}
                  </p>
                  {activeTransaction?.user?.phone ? (
                    <p>Whatsapp Phone: {activeTransaction.user.phone}</p>
                  ) : null}
                  {/* {activeTransaction?.user?.email ? (
                    <p>Email: {activeTransaction.user.email}</p>
                  ) : null} */}
                </div>
              </div>

              <div className="rounded-2xl border border-stone-100 bg-white p-4">
                <div className="text-xs font-semibold uppercase text-stone-400">Pet Details</div>
                <div className="mt-2 grid gap-3 text-sm text-stone-700 md:grid-cols-2">
                  <div>
                    <span className="text-stone-400">Name</span>
                    <p className="font-semibold text-stone-900">
                      {activeTransaction?.pet?.name || "Not available"}
                    </p>
                  </div>
                  <div>
                    <span className="text-stone-400">Breed</span>
                    <p className="font-semibold text-stone-900">
                      {activeTransaction?.pet?.breed || "Not available"}
                    </p>
                  </div>
                  <div>
                    <span className="text-stone-400">Type</span>
                    <p className="font-semibold text-stone-900">
                      {activeTransaction?.pet?.pet_type || "Not available"}
                    </p>
                  </div>
                  <div>
                    <span className="text-stone-400">DOB</span>
                    <p className="font-semibold text-stone-900">
                      {activeTransaction?.pet?.pet_dob || "Not available"}
                    </p>
                  </div>
                  {activeTransaction?.pet?.reported_symptom ? (
                    <div className="md:col-span-2">
                      <span className="text-stone-400">Reported Symptom</span>
                      <p className="font-semibold text-stone-900">
                        {activeTransaction.pet.reported_symptom}
                      </p>
                    </div>
                  ) : null}
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  type="button"
                  onClick={closePatientModal}
                  className="rounded-full bg-[#3998de] px-5 py-2 text-sm font-semibold text-white hover:bg-[#2F7FC0]"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {showPrescriptionModal ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-5xl max-h-[92vh] overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div className="flex items-center justify-between bg-gradient-to-r from-[#0B4D67] to-[#0E5F7B] px-6 py-4 text-white">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/15">
                  <FileText size={20} />
                </div>
                <div>
                  <p className="text-xs text-white/70">Prescription</p>
                  <h3 className="text-lg font-semibold">Create Medical Record</h3>
                </div>
              </div>
              <button
                type="button"
                onClick={closePrescriptionModal}
                className="rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
              >
                <X size={16} />
              </button>
            </div>

            <div className="bg-[#F4F7FB] px-6 pb-6 pt-4 overflow-y-auto max-h-[calc(92vh-76px)]">
              <form onSubmit={handlePrescriptionSubmit} className="space-y-6">
                <div className="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
                  <div className="space-y-6">
                    <div className="rounded-2xl border border-stone-100 bg-white p-4 shadow-sm">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="text-xs uppercase text-stone-400">Patient</p>
                          <p className="text-sm font-semibold text-stone-900">
                            {activeTransaction?.user?.name || "Pet Parent"}
                          </p>
                          <p className="text-xs text-stone-500">
                            {activeTransaction?.pet?.name || "Pet"} -{" "}
                            {activeTransaction?.pet?.breed || "Breed"}
                          </p>
                        </div>
                        <span
                          className={`inline-flex items-center rounded-full px-2 py-1 text-[10px] font-semibold ${statusClass(
                            activeTransaction?.status
                          )}`}
                        >
                          {statusLabel(activeTransaction?.status)}
                        </span>
                      </div>
                    </div>

                    <div className="rounded-2xl border border-stone-100 bg-white p-4 space-y-3 shadow-sm">
                      <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                        <FileText size={16} /> Consultation Basics
                      </div>
                      <div className="space-y-2">
                        <label className="text-xs font-semibold text-stone-400">Visit Category</label>
                        <select
                          value={prescriptionForm.visitCategory}
                          onChange={updatePrescriptionField("visitCategory")}
                          required
                          className={INPUT_BASE_CLASS}
                        >
                          <option value="Follow-up">Follow-up</option>
                          <option value="New Consultation">New Consultation</option>
                          <option value="Emergency">Emergency</option>
                        </select>
                      </div>
                      <div className="space-y-2">
                        <label className="text-xs font-semibold text-stone-400">Case Severity</label>
                        <select
                          value={prescriptionForm.caseSeverity}
                          onChange={updatePrescriptionField("caseSeverity")}
                          required
                          className={INPUT_BASE_CLASS}
                        >
                          <option value="general">General</option>
                          <option value="moderate">Moderate</option>
                          <option value="critical">Critical</option>
                        </select>
                      </div>
                      <div className="space-y-2">
                        <label className="text-xs font-semibold text-stone-400">Notes</label>
                        <textarea
                          value={prescriptionForm.notes}
                          onChange={updatePrescriptionField("notes")}
                          rows={3}
                          required
                          placeholder="Add key observations, diagnosis, and advice."
                          className={`${INPUT_BASE_CLASS} resize-none`}
                        />
                      </div>
                    </div>

                    <div className="rounded-2xl border border-stone-100 bg-white p-4 space-y-3 shadow-sm">
                      <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                        <Pill size={16} /> Vitals
                      </div>
                      <div className="grid gap-3 sm:grid-cols-2">
                        <div>
                          <label className="text-xs font-semibold text-stone-400">Temperature (C)</label>
                          <input
                            type="number"
                            min="0"
                            step="0.1"
                            value={prescriptionForm.temperature}
                            onChange={updatePrescriptionField("temperature")}
                            onKeyDown={blockNumberInput}
                            onWheel={handleNumberWheel}
                            required
                            className={INPUT_BASE_CLASS}
                          />
                        </div>
                        <div>
                          <label className="text-xs font-semibold text-stone-400">Weight (kg)</label>
                          <input
                            type="number"
                            min="0"
                            step="0.1"
                            value={prescriptionForm.weight}
                            onChange={updatePrescriptionField("weight")}
                            onKeyDown={blockNumberInput}
                            onWheel={handleNumberWheel}
                            required
                            className={INPUT_BASE_CLASS}
                          />
                        </div>
                        <div className="sm:col-span-2">
                          <label className="text-xs font-semibold text-stone-400">Heart Rate (bpm)</label>
                          <input
                            type="number"
                            min="0"
                            value={prescriptionForm.heartRate}
                            onChange={updatePrescriptionField("heartRate")}
                            onKeyDown={blockNumberInput}
                            onWheel={handleNumberWheel}
                            required
                            className={INPUT_BASE_CLASS}
                          />
                        </div>
                      </div>
                    </div>

                    <div className="rounded-2xl border border-stone-100 bg-white p-4 space-y-3 shadow-sm">
                      <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                        <Pill size={16} /> Medications
                      </div>
                      <div className="space-y-3">
                        {prescriptionForm.medications.map((medication, index) => (
                          <div
                            key={`med-${index}`}
                            className="grid gap-2 sm:grid-cols-2 lg:grid-cols-[1.2fr_0.9fr_0.9fr_0.8fr_auto] items-center"
                          >
                            <input
                              type="text"
                              value={medication.name}
                              onChange={updateMedication(index, "name")}
                              placeholder="Medicine name"
                              className={INPUT_BASE_CLASS}
                            />
                            <input
                              type="text"
                              value={medication.dosage}
                              onChange={updateMedication(index, "dosage")}
                              placeholder="Dosage"
                              className={INPUT_BASE_CLASS}
                            />
                            <input
                              type="text"
                              value={medication.frequency}
                              onChange={updateMedication(index, "frequency")}
                              placeholder="Frequency"
                              className={INPUT_BASE_CLASS}
                            />
                            <input
                              type="text"
                              value={medication.duration}
                              onChange={updateMedication(index, "duration")}
                              placeholder="Duration"
                              className={INPUT_BASE_CLASS}
                            />
                            <button
                              type="button"
                              onClick={() => removeMedication(index)}
                              className="rounded-full border border-stone-200 px-3 py-2 text-xs text-stone-500 hover:bg-stone-50 sm:col-span-2 lg:col-span-1 w-full lg:w-auto lg:justify-self-end"
                            >
                              Remove
                            </button>
                          </div>
                        ))}
                      </div>
                      <button
                        type="button"
                        onClick={addMedication}
                        className="rounded-full border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-50 w-full sm:w-auto"
                      >
                        + Add medication
                      </button>
                    </div>

                    <div className="rounded-2xl border border-stone-100 bg-white p-4 space-y-3 shadow-sm">
                      <div className="flex items-center gap-2 text-sm font-semibold text-stone-700">
                        <Upload size={16} /> Attach Record (optional)
                      </div>
                      <label className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-6 text-center text-xs text-stone-500 hover:border-[#3998de] hover:text-[#3998de]">
                        <input
                          type="file"
                          accept=".pdf,.png,.jpg,.jpeg"
                          onChange={handleRecordFile}
                          className="hidden"
                        />
                        <span className="font-semibold">Upload report file</span>
                        <span className="text-[10px] text-stone-400">
                          {prescriptionForm.recordFile?.name || "PDF, PNG, JPG supported"}
                        </span>
                      </label>
                    </div>
                  </div>

                  <aside className="space-y-4 lg:sticky lg:top-4 self-start">
                    <div className="rounded-2xl border border-stone-100 bg-white p-4 shadow-sm">
                      <div className="text-xs uppercase text-stone-400">Consult Summary</div>
                      <div className="mt-3 space-y-2 text-sm text-stone-700">
                        <div className="flex items-center justify-between">
                          <span>Reference</span>
                          <span className="max-w-[140px] truncate font-semibold text-stone-900">
                            {activeTransaction?.reference ||
                              activeTransaction?.metadata?.order_id ||
                              "NA"}
                          </span>
                        </div>
                        <div className="flex items-center justify-between">
                          <span>Amount</span>
                          <span className="font-semibold text-stone-900">
                            {formatAmount(
                              activeTransaction?.amount_inr ??
                                (activeTransaction?.amount_paise ? activeTransaction.amount_paise / 100 : 0)
                            )}
                          </span>
                        </div>
                        <div className="flex items-center justify-between">
                          <span>Date</span>
                          <span className="font-semibold text-stone-900">
                            {formatDate(activeTransaction?.created_at)}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="rounded-2xl border border-stone-100 bg-white p-4 shadow-sm">
                      <div className="text-xs uppercase text-stone-400">Submission</div>
                      <p className="mt-2 text-xs text-stone-500">
                        Please ensure all fields are complete before saving the prescription.
                      </p>
                    </div>

                    {prescriptionError ? (
                      <div className="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600">
                        <AlertCircle size={16} />
                        <span>{prescriptionError}</span>
                      </div>
                    ) : null}

                    {prescriptionSuccess ? (
                      <div className="flex items-start gap-2 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-700">
                        <CheckCircle2 size={16} />
                        <span>Prescription saved successfully.</span>
                      </div>
                    ) : null}

                    <div className="flex flex-col gap-3">
                      <button
                        type="button"
                        onClick={closePrescriptionModal}
                        className="rounded-full border border-stone-200 px-5 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-50"
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        disabled={prescriptionSubmitting}
                        className={`rounded-full bg-[#3998de] px-6 py-2 text-sm font-semibold text-white hover:bg-[#2F7FC0] ${
                          prescriptionSubmitting ? "opacity-60 cursor-not-allowed" : ""
                        }`}
                      >
                        {prescriptionSubmitting ? "Saving..." : "Save Prescription"}
                      </button>
                    </div>
                  </aside>
                </div>
              </form>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
};
