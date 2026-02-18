// VetScreens.jsx
// Notes:
// - Compress images before upload.
// - Upload image to get a URL (backend upload endpoint).
// - Send doctor_image as a URL string.

import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import imageCompression from "browser-image-compression";
import { Button } from "./Button";
import { apiBaseUrl, apiPost } from "../lib/api";
import { clearVetAuth, loadVetAuth, saveVetAuth } from "../lib/vetAuth";
import { InstallCTA } from "./PwaInstallCTA";
import {
  ChevronLeft,
  Camera,
  CheckCircle2,
  Clock,
  AlertCircle,
  History,
  Lock,
  FileText,
  Download,
  ExternalLink,
  ZoomIn,
  ZoomOut,
  RotateCcw,
  User,
  Pill,
  X,
  Upload,
  PawPrint,
  Stethoscope,
  Award,
  Calendar,
  MessageCircle,
  Mail,
  MapPin,
  Briefcase,
  Star,
  Shield,
  LogOut,
  TrendingUp,
} from "lucide-react";
import logo from "../assets/images/logo.png";

/* ---------------- UI Helpers ---------------- */

const VetHeader = ({
  onBack,
  title,
  subtitle,
  logoSrc,
  logoAlt = "SnoutIQ",
  actions,
}) => (
  <div className="sticky top-0 z-50 bg-white/95 backdrop-blur-lg border-b border-gray-100 shadow-sm">
    <div className="px-6 py-3 md:px-12 lg:px-20 md:py-4">
      <div className="grid grid-cols-[auto_1fr_auto] items-center gap-3">
        <div className="flex items-center">
          {onBack ? (
            <button
              onClick={onBack}
              className="p-2 -ml-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#0B4D67]/30"
              aria-label="Go back"
            >
              <ChevronLeft size={24} />
            </button>
          ) : (
            <div className="w-10" />
          )}
        </div>

        <div className="flex items-center justify-center gap-3">
          {/* logoSrc reserved (if you want to show logo) */}
          <div className="text-center">
            <h1 className="font-bold text-lg text-gray-900 md:text-xl">
              {title}
            </h1>
            {subtitle && (
              <p className="text-xs text-gray-500 mt-1 md:text-sm">
                {subtitle}
              </p>
            )}
          </div>
        </div>

        <div className="flex items-center justify-end">
          {actions || <div className="w-10" />}
        </div>
      </div>
    </div>
  </div>
);

const PageWrap = ({ children }) => (
  <div className="w-full md:px-12 lg:px-20">{children}</div>
);

const INPUT_BASE_CLASS =
  "w-full px-4 py-3.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 md:text-base md:px-5 md:py-4 md:rounded-2xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#0B4D67]/20 focus:border-[#0B4D67] focus:bg-white placeholder:text-gray-400 hover:border-gray-300";

const CARD_CLASS =
  "bg-white rounded-2xl border border-gray-100 shadow-[0_8px_30px_rgba(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgba(11,77,103,0.08)] transition-all duration-300";

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

const LANGUAGE_OPTIONS = [
  "English",
  "Hindi",
  "Hinglish",
  "Punjabi",
  "Marathi",
  "Bengali",
  "Tamil",
  "Telugu",
  "Kannada",
  "Malayalam",
  "Gujarati",
  "Urdu",
  "Other",
];

const DEGREE_OPTIONS = ["BVSc", "MVSc", "PhD", "Other"];

const RESPONSE_TIME_DAY_OPTIONS = [
  "0 to 15 mins",
  "15 to 20 mins",
  "20 to 30 mins",
];
const RESPONSE_TIME_NIGHT_OPTIONS = [
  "0 to 15 mins",
  "15 to 20 mins",
  "20 to 30 mins",
];

const FOLLOW_UP_OPTIONS = [
  { value: "yes", label: "Yes - free follow-up within 3 days" },
  { value: "no", label: "No - follow-ups are paid consultations" },
];

const PAYOUT_OPTIONS = [
  { value: "upi", label: "UPI (recommended)" },
  { value: "other", label: "Other" },
];

const IMAGE_URL_LIMIT = 500;
const DOC_ZOOM_MIN = 1;
const DOC_ZOOM_MAX = 4;
const DOC_ZOOM_STEP = 0.25;

const normalizeId = (value) => {
  const num = Number(value);
  if (!Number.isFinite(num) || num <= 0) return "";
  return String(num);
};

const normalizeDoctorName = (value) => {
  const trimmed = String(value || "").trim();
  if (!trimmed) return "";
  const withoutPrefix = trimmed.replace(/^dr\.?\s*/i, "").trim();
  if (!withoutPrefix) return "";
  return `Dr. ${withoutPrefix}`;
};

const fetchClinicIdForDoctor = async (doctorId, authToken = "", signal) => {
  if (!doctorId) return "";
  try {
    const headers = authToken
      ? { Authorization: `Bearer ${authToken}`, Accept: "application/json" }
      : { Accept: "application/json" };
    const res = await fetch(
      `${apiBaseUrl()}/api/doctor/profile?doctor_id=${encodeURIComponent(doctorId)}`,
      { headers, credentials: "include", signal },
    );
    if (!res.ok) return "";
    const data = await res.json().catch(() => ({}));
    return normalizeId(
      data?.data?.vet_registeration_id ||
        data?.data?.clinic?.id ||
        data?.clinic?.id,
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

const clampDocZoom = (value) =>
  Math.min(DOC_ZOOM_MAX, Math.max(DOC_ZOOM_MIN, value));

/* -------------- Image Compression + Upload -------------- */

const compressToFile = async (file) => {
  if (!file || !file.type?.startsWith("image/")) {
    throw new Error("Please upload a valid image file.");
  }

  const options = {
    maxSizeMB: 0.2,
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

/* ---------------- Pricing Section ---------------- */

const PricingSection = ({
  dayPrice,
  nightPrice,
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
  <section className={`${CARD_CLASS} p-6 space-y-6 md:p-8`}>
    <div className="flex items-start gap-3">
      <div className="w-10 h-10 rounded-full bg-[#0B4D67]/10 flex items-center justify-center shrink-0">
        <span className="text-[#0B4D67] font-extrabold text-lg leading-none">
          ₹
        </span>
      </div>

      <div className="min-w-0">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
          <h3 className="font-semibold text-gray-900 text-lg leading-tight">
            Pricing & Commission
          </h3>

          <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600">
            Standard payout policy
          </span>
        </div>

        <p className="mt-1 text-xs text-gray-500 leading-relaxed">
          The SnoutIQ platform follows a standardized payout structure aligned
          with prevailing market practices to ensure fair compensation, platform
          sustainability, and continued service quality.
        </p>

        <p className="mt-2 text-xs font-medium text-gray-600">
          Current consultation pricing and payouts are as follows:
        </p>
      </div>
    </div>

    <div className="space-y-5">
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700">
          Day Consultation Price (6 AM - 8 PM) *
        </label>
        <div className="relative">
          <span className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">
            ₹
          </span>
          <input
            type="number"
            value={dayPrice}
            disabled
            readOnly
            onKeyDown={blockNumberInput}
            onWheel={handleNumberWheel}
            className="w-full pl-8 pr-4 py-3.5 rounded-xl border border-gray-200 bg-gray-100 text-base font-medium text-gray-900 cursor-not-allowed opacity-80"
          />
        </div>

        {dayMath && (
          <div className="mt-2 p-3 bg-emerald-50 rounded-xl border border-emerald-100">
            <div className="flex justify-between text-xs">
              <span className="text-emerald-700 font-medium">
                Your earnings: ₹{dayMath.earning}
              </span>
              <span className="text-emerald-600/70">
                Platform fee: ₹{dayMath.commission}
              </span>
            </div>

            <p className="mt-2 text-[11px] text-emerald-700/80 leading-relaxed">
              A flat platform fee of ₹{dayMath.commission} is charged per
              consultation. You receive ₹{dayMath.earning} and SnoutIQ receives
              ₹{dayMath.commission}.
            </p>
          </div>
        )}
      </div>

      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700">
          Night Consultation Price (8 PM - 6 AM) *
        </label>
        <div className="relative">
          <span className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">
            ₹
          </span>
          <input
            type="number"
            value={nightPrice}
            disabled
            readOnly
            onKeyDown={blockNumberInput}
            onWheel={handleNumberWheel}
            className="w-full pl-8 pr-4 py-3.5 rounded-xl border border-gray-200 bg-gray-100 text-base font-medium text-gray-900 cursor-not-allowed opacity-80"
          />
        </div>

        {nightMath && (
          <div className="mt-2 p-3 bg-emerald-50 rounded-xl border border-emerald-100">
            <div className="flex justify-between text-xs">
              <span className="text-emerald-700 font-medium">
                Your earnings: ₹{nightMath.earning}
              </span>
              <span className="text-emerald-600/70">
                Platform fee: ₹{nightMath.commission}
              </span>
            </div>

            {/* FIX: was wrongly using dayMath */}
            <p className="mt-2 text-[11px] text-emerald-700/80 leading-relaxed">
              A flat platform fee of ₹{nightMath.commission} is charged per
              consultation. You receive ₹{nightMath.earning} and SnoutIQ
              receives ₹{nightMath.commission}.
            </p>
          </div>
        )}
      </div>
    </div>

    <div className="space-y-3">
      <label className="block text-sm font-medium text-gray-700">
        Free Follow-up Consultation *
      </label>
      <div className="space-y-2">
        {FOLLOW_UP_OPTIONS.map((option) => (
          <label
            key={option.value}
            className="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-[#0B4D67]/30 hover:bg-gray-50/50 transition-all cursor-pointer"
          >
            <input
              type="radio"
              name="freeFollowUp"
              value={option.value}
              checked={form.freeFollowUp === option.value}
              onChange={updateForm("freeFollowUp")}
              required
              className="w-4 h-4 text-[#0B4D67] border-gray-300 focus:ring-[#0B4D67]/30"
            />
            <span className="text-sm text-gray-700">{option.label}</span>
          </label>
        ))}
      </div>
    </div>

    <div className="space-y-3">
      <label className="block text-sm font-medium text-gray-700">
        Preferred Payout Method *
      </label>
      <div className="space-y-2">
        {PAYOUT_OPTIONS.map((option) => (
          <label
            key={option.value}
            className="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-[#0B4D67]/30 hover:bg-gray-50/50 transition-all cursor-pointer"
          >
            <input
              type="radio"
              name="payoutMethod"
              value={option.value}
              checked={form.payoutMethod === option.value}
              onChange={updateForm("payoutMethod")}
              required
              className="w-4 h-4 text-[#0B4D67] border-gray-300 focus:ring-[#0B4D67]/30"
            />
            <span className="text-sm text-gray-700">{option.label}</span>
          </label>
        ))}
      </div>

      <input
        type="text"
        value={form.payoutDetail}
        onChange={updateForm("payoutDetail")}
        placeholder={
          form.payoutMethod === "upi"
            ? "Enter your UPI ID (e.g., doctor@okhdfcbank)"
            : "Enter payout details"
        }
        required
        className={INPUT_BASE_CLASS}
      />
    </div>

    <div className="p-4 bg-amber-50/50 rounded-xl border border-amber-100">
      <div className="flex items-center gap-2 mb-2">
        <Shield size={16} className="text-amber-600" />
        <h4 className="text-xs font-semibold uppercase text-amber-800">
          Commission Structure
        </h4>
      </div>
      <ul className="text-xs text-amber-800/80 space-y-1.5 pl-4">
        <li className="flex items-start gap-2">
          <span className="text-amber-600">•</span>
          <span>
            These charges and payout amounts are determined based on industry
            benchmarks, operational costs, and platform service provisions.
            SnoutIQ reserves the right to review, revise, or update the
            consultation pricing and payout structure from time to time, at its
            sole discretion, in response to market conditions, business
            requirements, and to ensure maximum long-term benefits for doctors
            using the platform. By continuing to provide services on SnoutIQ,
            doctors acknowledge and agree to the applicable payout structure and
            any future revisions.
          </span>
        </li>
        <li className="flex items-start gap-2">
          <span className="text-amber-600">•</span>
          <span>Remaining amount is transferred to your account</span>
        </li>
        <li className="flex items-start gap-2">
          <span className="text-amber-600">•</span>
          <span>No monthly subscription fees, ever</span>
        </li>
      </ul>
    </div>

    <div className="space-y-3 pt-2">
      <label className="flex gap-3 items-start p-2 cursor-pointer rounded-xl hover:bg-gray-50 transition-colors">
        <input
          type="checkbox"
          checked={agreement1}
          onChange={(e) => setAgreement1(e.target.checked)}
          className="mt-1 w-4 h-4 text-[#0B4D67] border-gray-300 rounded focus:ring-[#0B4D67]/30"
        />
        <span className="text-sm text-gray-600 leading-relaxed">
          These charges and payout amounts are determined based on industry
            benchmarks, operational costs, and platform service provisions.
            SnoutIQ reserves the right to review, revise, or update the
            consultation pricing and payout structure from time to time, at its
            sole discretion, in response to market conditions, business
            requirements, and to ensure maximum long-term benefits for doctors
            using the platform. By continuing to provide services on SnoutIQ,
            doctors acknowledge and agree to the applicable payout structure and
            any future revisions.
        </span>
      </label>

      <label className="flex gap-3 items-start p-2 cursor-pointer rounded-xl hover:bg-gray-50 transition-colors">
        <input
          type="checkbox"
          checked={agreement2}
          onChange={(e) => setAgreement2(e.target.checked)}
          className="mt-1 w-4 h-4 text-[#0B4D67] border-gray-300 rounded focus:ring-[#0B4D67]/30"
        />
        <span className="text-sm text-gray-600 leading-relaxed">
          I understand earnings will be settled weekly to my registered payout
          method
        </span>
      </label>
    </div>

    {submitError && (
      <div className="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-100 p-4 rounded-xl">
        <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
        <span>{submitError}</span>
      </div>
    )}

    {showErrors && !canSubmit && (
      <div className="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-100 p-4 rounded-xl">
        <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
        <span>Please complete all required fields before submitting</span>
      </div>
    )}

    <div className="hidden md:block">
      <Button
        onClick={handleSubmit}
        fullWidth
        disabled={!canSubmit}
        className={`md:text-lg md:py-4 md:rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 transition-all duration-300 ${
          !canSubmit
            ? "opacity-50 cursor-not-allowed"
            : "shadow-lg shadow-blue-500/30"
        }`}
      >
        Submit Application
      </Button>
    </div>

    <p className="text-xs text-gray-400 flex items-center gap-1.5">
      <Lock size={12} />
      Patient contact details remain private and secure
    </p>
  </section>
);

/* ---------------- 1) Vet Login Screen ---------------- */

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
        navigate("/vet-dashboard", {
          replace: true,
          state: { auth: storedAuth },
        });
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
      if (!nextRequestId)
        throw new Error("Request ID not received. Please try again.");
      setRequestId(nextRequestId);
      setStep("otp");
    } catch (error) {
      setErrorMessage(
        error?.message || "Failed to send OTP. Please try again.",
      );
    } finally {
      setIsSending(false);
    }
  };

  const handleResendOtp = async () => {
    if (isSending || mobile.length < 10) return;
    setOtp("");
    await handleSendOtp();
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
      if (res?.success === false)
        throw new Error(res?.message || "OTP verification failed.");

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

      const doctorProfile = doctor || {
        doctor_name:
          res?.doctor_name || res?.name || res?.data?.doctor_name || "",
        doctor_email:
          res?.doctor_email || res?.email || res?.data?.doctor_email || "",
        doctor_mobile: res?.doctor_mobile || mobile,
      };

      const authPayload = {
        phone: mobile,
        request_id: requestId,
        doctor_id: normalizedDoctorId || doctorId,
        clinic_id: normalizedClinicId || clinicId,
        token:
          res?.token ||
          res?.access_token ||
          res?.data?.token ||
          res?.data?.access_token ||
          "",
        doctor: {
          ...doctorProfile,
          ...(normalizedDoctorId
            ? {
                id: Number(normalizedDoctorId),
                doctor_id: Number(normalizedDoctorId),
              }
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
      navigate("/vet-dashboard", {
        replace: true,
        state: { auth: authPayload },
      });
      ensureDashboardLoaded();
    } catch (error) {
      setErrorMessage(error?.message || "OTP verification failed.");
    } finally {
      setIsVerifying(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-white flex flex-col">
      <VetHeader
        onBack={onBack}
        title="Vet Partner Login"
        subtitle="Welcome back to SnoutIQ"
        logoSrc={logo}
        actions={
          <InstallCTA className="rounded-full px-4 py-2 text-xs whitespace-nowrap sm:text-sm" />
        }
      />

      <PageWrap>
        <div className="flex-1 px-6 py-6 flex flex-col justify-start max-w-md mx-auto w-full md:max-w-lg md:px-0 md:py-10 md:justify-center">
          <div className="text-center mb-4 md:mb-6">
            <h2 className="text-xl md:text-2xl font-bold text-gray-900 mb-1 tracking-tight">
              Welcome back, Doctor
            </h2>
            <p className="text-xs text-gray-500 md:text-sm">
              Securely access your practice dashboard
            </p>
          </div>

          <div className={`${CARD_CLASS} p-5 md:p-6 space-y-5`}>
            {step === "mobile" ? (
              <>
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-700">
                    Mobile Number
                  </label>
                  <div className="flex items-center border border-gray-200 rounded-xl bg-gray-50 focus-within:ring-2 focus-within:ring-[#0B4D67]/20 focus-within:border-[#0B4D67] focus-within:bg-white transition-all">
                    <span className="text-gray-500 font-medium px-4 border-r border-gray-200 py-3">
                      +91
                    </span>
                    <input
                      type="tel"
                      value={mobile}
                      onChange={(e) =>
                        setMobile(
                          e.target.value.replace(/\D/g, "").slice(0, 10),
                        )
                      }
                      placeholder="Enter 10-digit mobile number"
                      className="flex-1 px-4 py-3 bg-transparent outline-none font-medium text-gray-900 placeholder:text-gray-400"
                    />
                  </div>
                  <p className="text-xs text-gray-500 mt-1">
                    We'll send a 6-digit OTP for verification
                  </p>
                </div>

                <Button
                  onClick={handleSendOtp}
                  disabled={mobile.length < 10 || isSending}
                  fullWidth
                  className="md:text-base md:py-3.5 md:rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 transition-all duration-300 shadow-lg shadow-blue-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isSending ? (
                    <span className="flex items-center justify-center gap-2">
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      Sending OTP...
                    </span>
                  ) : (
                    "Send OTP"
                  )}
                </Button>
              </>
            ) : (
              <>
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-gray-700">
                    Enter OTP
                  </label>
                  <input
                    type="text"
                    value={otp}
                    onChange={(e) =>
                      setOtp(e.target.value.replace(/\D/g, "").slice(0, 6))
                    }
                    placeholder="Enter 6-digit OTP"
                    className="w-full px-4 py-3 text-center text-xl md:text-2xl tracking-[0.5em] border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#0B4D67]/20 focus:border-[#0B4D67] focus:bg-white placeholder:text-gray-400 placeholder:tracking-normal"
                  />
                  <p className="text-xs text-center text-gray-500 mt-2">
                    OTP sent to +91 {mobile}
                  </p>
                </div>

                <Button
                  onClick={handleVerifyOtp}
                  disabled={otp.length < 6 || isVerifying}
                  fullWidth
                  className="md:text-base md:py-3.5 md:rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 transition-all duration-300 shadow-lg shadow-blue-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isVerifying ? (
                    <span className="flex items-center justify-center gap-2">
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      Verifying...
                    </span>
                  ) : (
                    "Verify & Login"
                  )}
                </Button>

                <div className="flex items-center justify-between pt-2">
                  <button
                    onClick={handleResendOtp}
                    disabled={isSending || mobile.length < 10}
                    className="text-sm text-[#0B4D67] font-medium hover:underline disabled:text-gray-400 disabled:hover:no-underline"
                  >
                    {isSending ? "Sending..." : "Resend OTP"}
                  </button>
                  <button
                    onClick={() => {
                      setStep("mobile");
                      setOtp("");
                      setRequestId("");
                      setErrorMessage("");
                    }}
                    className="text-sm text-gray-500 font-medium hover:text-gray-700"
                  >
                    Change Number
                  </button>
                </div>
              </>
            )}

            {errorMessage && (
              <div className="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-100 p-4 rounded-xl">
                <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
                <span>{errorMessage}</span>
              </div>
            )}
          </div>

          <div className="mt-5 text-center">
            <p className="text-gray-500 text-sm md:text-base">
              New to SnoutIQ?
            </p>
            <button
              onClick={onRegisterClick}
              className="text-[#0B4D67] font-semibold text-sm hover:text-[#1A6F8F] hover:underline mt-1 md:text-lg transition-colors"
            >
              Register as a Partner →
            </button>
          </div>
        </div>
      </PageWrap>
    </div>
  );
};

/* ---------------- 2) Vet Registration Screen ---------------- */

export const VetRegisterScreen = ({ onSubmit, onBack }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const [form, setForm] = useState({
    vetFullName: "Dr. ",
    clinicName: "",
    shortIntro: "",
    whatsappNumber: "",
    email: "",
    vetCity: "",
    degree: [],
    degreeOther: "",
    yearsOfExperience: "",
    doctorLicense: "",
    responseTimeDay: "",
    responseTimeNight: "",
    freeFollowUp: "",
    payoutMethod: "upi",
    payoutDetail: "",
    doctorImageUrl: "",
  });

  const [specializations, setSpecializations] = useState([]);
  const [specializationOther, setSpecializationOther] = useState("");
  const [languages, setLanguages] = useState([]);
  const [languageOther, setLanguageOther] = useState("");
  const [breakStart, setBreakStart] = useState("");
  const [breakEnd, setBreakEnd] = useState("");
  const [breakTimes, setBreakTimes] = useState([]);
  const [noBreakTime, setNoBreakTime] = useState(false);

  // locked pricing (as you had)
  const [dayPrice] = useState("500");
  const [nightPrice] = useState("650");

  const [agreement1, setAgreement1] = useState(false);
  const [agreement2, setAgreement2] = useState(false);

  const [doctorImageFile, setDoctorImageFile] = useState(null);
  const [doctorImagePreview, setDoctorImagePreview] = useState("");
  const [isImageProcessing, setIsImageProcessing] = useState(false);
  const [imageError, setImageError] = useState("");

  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [showErrors, setShowErrors] = useState(false);
  const [showRegisterSuccessModal, setShowRegisterSuccessModal] =
    useState(false);

  const breakStartRef = useRef(null);
  const breakEndRef = useRef(null);

  const agreed = agreement1 && agreement2;
  const updateForm = (key) => (e) =>
    setForm((prev) => ({ ...prev, [key]: e.target.value }));

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

  // Pricing logic: fixed platform fee (day 150, night 200)
  const PLATFORM_FEE_DAY = 150;
  const PLATFORM_FEE_NIGHT = 200;

  const calculateCommission = (priceStr, platformFee) => {
    const price = Number(priceStr);
    if (!Number.isFinite(price) || price <= 0) return null;

    const commission = Math.min(platformFee, price); // safety
    const earning = price - commission;

    return { commission, earning };
  };

  const dayMath = calculateCommission(dayPrice, PLATFORM_FEE_DAY);
  const nightMath = calculateCommission(nightPrice, PLATFORM_FEE_NIGHT);

  const toggleSpecialization = (value) => {
    setSpecializations((prev) =>
      prev.includes(value)
        ? prev.filter((item) => item !== value)
        : [...prev, value],
    );
  };

  const toggleDegree = (value) => {
    setForm((prev) => {
      const current = Array.isArray(prev.degree) ? prev.degree : [];
      const isSelected = current.includes(value);
      const next = isSelected
        ? current.filter((item) => item !== value)
        : [...current, value];

      return {
        ...prev,
        degree: next,
        degreeOther: value === "Other" && isSelected ? "" : prev.degreeOther,
      };
    });
  };

  const formatTimeLabel = (value) => {
    if (!value) return "";
    const [hours, minutes] = value.split(":").map((part) => Number(part));
    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return value;
    const hour12 = ((hours + 11) % 12) + 1;
    const period = hours >= 12 ? "PM" : "AM";
    return `${hour12}:${String(minutes).padStart(2, "0")} ${period}`;
  };

  const openTimePicker = (inputRef) => {
    if (noBreakTime) return;
    const input = inputRef?.current;
    if (!input) return;
    if (typeof input.showPicker === "function") {
      input.showPicker();
      return;
    }
    input.focus();
  };

  const handleTimePickerKey = (inputRef) => (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      openTimePicker(inputRef);
    }
  };

  const addBreakTime = () => {
    if (noBreakTime) return;
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

  const handleNoBreakToggle = (event) => {
    const nextValue = event.target.checked;
    setNoBreakTime(nextValue);
    if (nextValue) {
      setBreakTimes([]);
      setBreakStart("");
      setBreakEnd("");
    }
  };

  const handleDoctorImageFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setImageError("");
    setIsImageProcessing(true);

    try {
      const compressedFile = await compressToFile(file);
      setDoctorImageFile(compressedFile);
      // clear URL if using file
      setForm((prev) => ({ ...prev, doctorImageUrl: "" }));
    } catch (err) {
      setDoctorImageFile(null);
      setImageError(
        err?.message || "Image compression failed. Please try another image.",
      );
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

  const clearImage = () => {
    setDoctorImageFile(null);
    setForm((prev) => ({ ...prev, doctorImageUrl: "" }));
    setImageError("");
  };

  const degreeSelections = Array.isArray(form.degree)
    ? form.degree
    : form.degree
      ? [form.degree]
      : [];

  const normalizedDegrees = degreeSelections
    .filter((degree) => degree !== "Other")
    .map((degree) => degree.trim())
    .filter(Boolean);

  if (degreeSelections.includes("Other") && form.degreeOther.trim()) {
    normalizedDegrees.push(form.degreeOther.trim());
  }

  const degreePayload = Array.from(new Set(normalizedDegrees));
  const doctorNameReady = normalizeDoctorName(form.vetFullName);

  useEffect(() => {
    const storedAuth = loadVetAuth();
    const stateAuth =
      location?.state?.auth ||
      location?.state?.doctor ||
      location?.state?.prefill;
    const source = stateAuth || storedAuth;
    if (!source) return;

    const doctor = source?.doctor || source;
    const rawName =
      doctor?.doctor_name ||
      doctor?.name ||
      source?.doctor_name ||
      source?.name ||
      "";
    const rawEmail =
      doctor?.doctor_email ||
      doctor?.email ||
      source?.doctor_email ||
      source?.email ||
      "";
    const rawPhone =
      doctor?.doctor_mobile ||
      doctor?.phone ||
      source?.doctor_mobile ||
      source?.phone ||
      "";
    const rawClinic =
      source?.clinic_name ||
      source?.vet_name ||
      doctor?.clinic_name ||
      doctor?.vet_name ||
      "";
    const rawCity = source?.vet_city || doctor?.vet_city || "";

    const normalizedName = normalizeDoctorName(rawName);
    const digits = String(rawPhone || "").replace(/\D/g, "");

    if (!normalizedName && !rawEmail && !digits && !rawClinic && !rawCity)
      return;

    setForm((prev) => {
      let updated = false;
      const next = { ...prev };
      const prevName = prev.vetFullName.trim();
      const prevNameLower = prevName.toLowerCase();

      if (
        normalizedName &&
        (!prevName || prevNameLower === "dr." || prevNameLower === "dr")
      ) {
        next.vetFullName = normalizedName;
        updated = true;
      }
      if (rawClinic && !prev.clinicName.trim()) {
        next.clinicName = rawClinic;
        updated = true;
      }
      if (rawEmail && !prev.email.trim()) {
        next.email = rawEmail;
        updated = true;
      }
      if (rawCity && !prev.vetCity.trim()) {
        next.vetCity = rawCity;
        updated = true;
      }
      if (digits && !prev.whatsappNumber.trim()) {
        next.whatsappNumber = digits;
        updated = true;
      }
      return updated ? next : prev;
    });
  }, [location.state]);

  const selectedSpecs = specializations.filter((spec) => spec !== "Other");
  if (specializations.includes("Other") && specializationOther.trim()) {
    selectedSpecs.push(specializationOther.trim());
  }
  const selectedLanguages = languages.filter((lang) => lang !== "Other");
  if (languages.includes("Other") && languageOther.trim()) {
    selectedLanguages.push(languageOther.trim());
  }

  const payoutReady = form.payoutDetail.trim();
  const degreeReady = degreePayload.length > 0;
  const whatsappReady = form.whatsappNumber.trim().length >= 10;

  const trimmedImageUrl = form.doctorImageUrl.trim();
  const urlReady =
    trimmedImageUrl.length > 0 && trimmedImageUrl.length <= IMAGE_URL_LIMIT;
  const imageReady = Boolean(doctorImageFile) || urlReady;
  const imageValid = !isImageProcessing && (!imageReady || !imageError);

  const breakReady = noBreakTime || breakTimes.length > 0;

  const basicComplete = Boolean(
    doctorNameReady &&
    whatsappReady &&
    form.email.trim() &&
    form.shortIntro.trim() &&
    form.vetCity.trim() &&
    imageValid,
  );

  const professionalComplete = Boolean(
    form.doctorLicense.trim() &&
    degreeReady &&
    form.yearsOfExperience.trim() &&
    selectedSpecs.length > 0 &&
    selectedLanguages.length > 0,
  );

  const availabilityComplete = Boolean(
    form.responseTimeDay && form.responseTimeNight && breakReady,
  );

  const pricingComplete = Boolean(
    dayPrice &&
    nightPrice &&
    form.freeFollowUp &&
    payoutReady &&
    agreement1 &&
    agreement2,
  );

  const progressSteps = useMemo(() => {
    const steps = [
      { id: 1, label: "Basic Details", complete: basicComplete },
      { id: 2, label: "Professional", complete: professionalComplete },
      { id: 3, label: "Availability", complete: availabilityComplete },
      { id: 4, label: "Pricing", complete: pricingComplete },
    ];
    const firstIncompleteIndex = steps.findIndex((step) => !step.complete);
    const activeStepId =
      firstIncompleteIndex === -1
        ? steps[steps.length - 1].id
        : steps[firstIncompleteIndex].id;

    return steps.map((step) => ({
      ...step,
      active: step.id === activeStepId && !step.complete,
    }));
  }, [
    basicComplete,
    professionalComplete,
    availabilityComplete,
    pricingComplete,
  ]);

  const stepCircleClass = (step) =>
    [
      "w-10 h-10 rounded-full flex items-center justify-center font-semibold",
      step.complete || step.active
        ? "bg-gradient-to-r from-blue-600 to-blue-500 text-white"
        : "bg-gray-200 text-gray-600",
    ].join(" ");

  const stepLabelClass = (step) =>
    step.complete || step.active
      ? "font-medium text-gray-700"
      : "font-medium text-gray-500";

  const stepLineClass = (isComplete) =>
    [
      "w-16 h-0.5",
      isComplete ? "bg-gradient-to-r from-blue-600 to-blue-500" : "bg-gray-200",
    ].join(" ");

  const canSubmit =
    basicComplete &&
    professionalComplete &&
    availabilityComplete &&
    pricingComplete &&
    agreed;

  const handleRegisterSuccessLogin = () => {
    setShowRegisterSuccessModal(false);
    onSubmit?.();
    navigate("/auth", { replace: true, state: { mode: "login" } });
  };

  const toggleLanguage = (value) => {
    setLanguages((prev) =>
      prev.includes(value)
        ? prev.filter((item) => item !== value)
        : [...prev, value],
    );
  };

  const handleSubmit = async () => {
    if (submitting) return;

    if (!canSubmit) {
      setShowErrors(true);
      return;
    }

    setSubmitting(true);
    setSubmitError("");
    setShowErrors(false);

    try {
      const breakPayload = noBreakTime ? ["No"] : breakTimes;
      const clinicNameValue = form.clinicName.trim();
      const vetNamePayload = clinicNameValue ? clinicNameValue : null;

      // FIX: doctor_image MUST be a string URL.
      // If user selected a file, upload it first to get URL.
      let finalDoctorImageUrl = trimmedImageUrl || "";
      if (doctorImageFile) {
        finalDoctorImageUrl = await uploadDoctorImageAndGetUrl(doctorImageFile);
      }

      if (
        finalDoctorImageUrl &&
        finalDoctorImageUrl.length > IMAGE_URL_LIMIT
      ) {
        throw new Error(
          `Image URL must be ${IMAGE_URL_LIMIT} characters or less.`,
        );
      }

      const payload = {
        vet_name: vetNamePayload,
        vet_email: form.email.trim(),
        vet_mobile: form.whatsappNumber.trim(),
        vet_city: form.vetCity.trim(),
        doctor_name: doctorNameReady,
        doctor_email: form.email.trim(),
        doctor_mobile: form.whatsappNumber.trim(),
        doctor_license: form.doctorLicense.trim(),
        doctor_image: finalDoctorImageUrl, // ✅ ALWAYS STRING
        bio: form.shortIntro.trim(),
        degree: degreePayload,
        years_of_experience: form.yearsOfExperience.trim(),
        specialization_select_all_that_apply: selectedSpecs,
        languages_spoken: selectedLanguages,
        response_time_for_online_consults_day: form.responseTimeDay,
        response_time_for_online_consults_night: form.responseTimeNight,
        break_do_not_disturb_time_example_2_4_pm: breakPayload,
        do_you_offer_a_free_follow_up_within_3_days_after_a_consulta:
          form.freeFollowUp === "yes" ? "Yes" : "No",
        commission_and_agreement: agreed ? "Agreed" : "Not agreed",
        video_day_rate: 500,
        video_night_rate: 650,
        short_intro: form.shortIntro.trim(),
        preferred_payout_method: form.payoutMethod,
        preferred_payout_detail: payoutReady,
      };

      const data = await apiPost("/api/excell-export/import", payload);
      if (data?.success === false) {
        throw new Error(
          data?.message || "Registration failed. Please try again.",
        );
      }

      setShowRegisterSuccessModal(true);
    } catch (error) {
      const message = error?.message || "Failed to submit application.";
      setSubmitError(message);
      if (
        message.toLowerCase().includes("image") ||
        message.toLowerCase().includes("upload")
      ) {
        setImageError(message);
      }
    } finally {
      setSubmitting(false);
    }
  };

  const pricingProps = {
    dayPrice,
    nightPrice,
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
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white flex flex-col">
      <VetHeader
        onBack={onBack}
        title="Partner Registration"
        subtitle="Join India's trusted veterinary network"
        logoSrc={logo}
        actions={
          <InstallCTA className="rounded-full px-4 py-2 text-xs whitespace-nowrap sm:text-sm" />
        }
      />

      <PageWrap>
        <div className="flex-1 px-4 py-8 pb-32 overflow-y-auto no-scrollbar md:px-0 md:py-12">
          {/* Progress Steps */}
          <div className="hidden md:flex items-center justify-center mb-12">
            <div className="flex items-center gap-4">
              {progressSteps.map((step, index) => (
                <React.Fragment key={step.id}>
                  <div className="flex items-center gap-2">
                    <div className={stepCircleClass(step)}>
                      {step.complete ? <CheckCircle2 size={18} /> : step.id}
                    </div>
                    <span className={stepLabelClass(step)}>{step.label}</span>
                  </div>
                  {index < progressSteps.length - 1 && (
                    <div
                      className={stepLineClass(progressSteps[index].complete)}
                    />
                  )}
                </React.Fragment>
              ))}
            </div>
          </div>

          <div className="space-y-8 md:space-y-0 md:grid md:grid-cols-12 md:gap-8 lg:gap-12">
            {/* LEFT COLUMN */}
            <div className="md:col-span-7 lg:col-span-8 space-y-8">
              {/* Profile Image Section */}
              <section className={`${CARD_CLASS} p-6 md:p-8`}>
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-10 h-10 rounded-full bg-[#0B4D67]/10 flex items-center justify-center">
                    <Camera size={20} className="text-[#0B4D67]" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 text-lg">
                      Profile Photo
                    </h3>
                    <p className="text-xs text-gray-500">
                      Upload a professional photo for your profile
                    </p>
                  </div>
                </div>

                <div className="flex flex-col md:flex-row items-center gap-8">
                  <div className="relative flex flex-col items-center gap-3">
                    <div className="h-32 w-32 md:h-40 md:w-40 rounded-2xl border-3 border-[#0B4D67]/20 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center overflow-hidden shadow-lg">
                      {doctorImagePreview ? (
                        <img
                          src={doctorImagePreview}
                          alt="Doctor preview"
                          className="h-full w-full object-cover"
                        />
                      ) : (
                        <div className="text-center">
                          <User
                            size={48}
                            className="mx-auto text-gray-400 mb-2"
                          />
                          <span className="text-xs font-medium text-gray-500">
                            No photo
                          </span>
                        </div>
                      )}
                    </div>

                    <div className="flex items-center gap-2">
                      <input
                        id="doctorImageCamera"
                        type="file"
                        accept="image/*"
                        capture="environment"
                        className="hidden"
                        onChange={handleDoctorImageFile}
                      />
                      <label
                        htmlFor="doctorImageCamera"
                        className="inline-flex items-center gap-2 rounded-full border border-[#0B4D67]/20 bg-white px-3 py-1.5 text-xs font-semibold text-[#0B4D67] shadow-sm transition hover:border-[#0B4D67]/40"
                      >
                        <Camera size={14} />
                        Camera
                      </label>

                      <input
                        id="doctorImageGallery"
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={handleDoctorImageFile}
                      />
                      <label
                        htmlFor="doctorImageGallery"
                        className="inline-flex items-center gap-2 rounded-full border border-[#0B4D67]/20 bg-white px-3 py-1.5 text-xs font-semibold text-[#0B4D67] shadow-sm transition hover:border-[#0B4D67]/40"
                      >
                        <Upload size={14} />
                        Gallery
                      </label>
                    </div>

                    {/* Optional: URL input (keep if you want) */}
                    {/* <input
                      type="text"
                      value={form.doctorImageUrl}
                      onChange={handleDoctorImageUrlChange}
                      placeholder="Or paste image URL"
                      className={`${INPUT_BASE_CLASS} mt-3`}
                    /> */}
                  </div>

                  <div className="flex-1 text-center md:text-left space-y-3">
                    <div>
                      <p className="text-sm font-medium text-gray-700">
                        Professional photo guidelines
                      </p>
                      <ul className="text-xs text-gray-500 mt-2 space-y-1">
                        <li className="flex items-center gap-2">
                          <CheckCircle2 size={12} className="text-green-500" />
                          Clear, front-facing photo
                        </li>
                        <li className="flex items-center gap-2">
                          <CheckCircle2 size={12} className="text-green-500" />
                          Professional attire recommended
                        </li>
                        <li className="flex items-center gap-2">
                          <CheckCircle2 size={12} className="text-green-500" />
                          JPG or PNG format
                        </li>
                      </ul>
                    </div>

                    {(doctorImageFile || form.doctorImageUrl.trim()) && (
                      <button
                        type="button"
                        onClick={clearImage}
                        className="text-sm text-red-600 hover:text-red-700 font-medium"
                      >
                        Remove photo
                      </button>
                    )}

                    {isImageProcessing && (
                      <div className="flex items-center gap-2 text-sm text-gray-600">
                        <div className="w-4 h-4 border-2 border-[#0B4D67] border-t-transparent rounded-full animate-spin" />
                        Optimizing image...
                      </div>
                    )}

                    {imageError && (
                      <p className="text-sm text-red-600 bg-red-50 p-3 rounded-xl">
                        {imageError}
                      </p>
                    )}
                  </div>
                </div>
              </section>

              {/* Basic Details */}
              <section className={`${CARD_CLASS} p-6 md:p-8 space-y-5`}>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-[#0B4D67]/10 flex items-center justify-center">
                    <User size={20} className="text-[#0B4D67]" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 text-lg">
                      Basic Information
                    </h3>
                    <p className="text-xs text-gray-500">
                      Your personal and clinic details
                    </p>
                  </div>
                </div>

                <div className="space-y-4">
                  <div className="relative">
                    <User
                      size={18}
                      className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                    />
                    <input
                      type="text"
                      placeholder="Enter your full name"
                      value={form.vetFullName}
                      onChange={updateForm("vetFullName")}
                      required
                      className={`${INPUT_BASE_CLASS} pl-12 md:pl-12`}
                    />
                  </div>

                  <div className="relative">
                    <Briefcase
                      size={18}
                      className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                    />
                    <input
                      type="text"
                      placeholder="Enter clinic/hospital name (optional)"
                      value={form.clinicName}
                      onChange={updateForm("clinicName")}
                      className={`${INPUT_BASE_CLASS} pl-12 md:pl-12`}
                    />
                  </div>

                  <div className="relative">
                    <FileText
                      size={18}
                      className="absolute left-4 top-4 text-gray-400 pointer-events-none"
                    />
                    <textarea
                      placeholder="Write a short introduction about yourself and your practice"
                      value={form.shortIntro}
                      onChange={updateForm("shortIntro")}
                      rows={4}
                      required
                      className={`${INPUT_BASE_CLASS} pl-12 md:pl-12 resize-none`}
                    />
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="relative">
                      <MapPin
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                      />
                      <input
                        type="text"
                        placeholder="Enter your city"
                        value={form.vetCity}
                        onChange={updateForm("vetCity")}
                        required
                        className={`${INPUT_BASE_CLASS} pl-12 md:pl-12`}
                      />
                    </div>

                    <div className="flex flex-col gap-1">
                      <div className="relative">
                        <MessageCircle
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500 pointer-events-none"
                        />
                        <input
                          type="tel"
                          inputMode="numeric"
                          pattern="[0-9]*"
                          placeholder="Enter WhatsApp number"
                          value={form.whatsappNumber}
                          onChange={(e) =>
                            setForm((prev) => ({
                              ...prev,
                              whatsappNumber: e.target.value.replace(/\D/g, ""),
                            }))
                          }
                          required
                          className={`${INPUT_BASE_CLASS} pl-12 md:pl-12`}
                        />
                      </div>
                      <p className="pl-12 text-xs text-gray-500">
                        This number will be used for all communication purposes.
                      </p>
                    </div>
                  </div>

                  <div className="relative">
                    <Mail
                      size={18}
                      className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                    />
                    <input
                      type="email"
                      placeholder="Enter your email address"
                      value={form.email}
                      onChange={updateForm("email")}
                      required
                      className={`${INPUT_BASE_CLASS} pl-12 md:pl-12`}
                    />
                  </div>

                  <p className="text-xs text-gray-500 flex items-center gap-1.5 bg-gray-50 p-3 rounded-xl">
                    <Lock size={12} className="text-[#0B4D67]" />
                    Your contact details are kept private and never shared
                    directly
                  </p>
                </div>
              </section>

              {/* Professional Details */}
              <section className={`${CARD_CLASS} p-6 md:p-8 space-y-6`}>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-[#0B4D67]/10 flex items-center justify-center">
                    <Stethoscope size={20} className="text-[#0B4D67]" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 text-lg">
                      Professional Credentials
                    </h3>
                    <p className="text-xs text-gray-500">
                      Your qualifications and expertise
                    </p>
                  </div>
                </div>

                <div className="relative">
                  <Award
                    size={18}
                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                  />
                  <input
                    type="text"
                    placeholder="Enter veterinary registration number"
                    value={form.doctorLicense}
                    onChange={updateForm("doctorLicense")}
                    required
                    className={`${INPUT_BASE_CLASS} pl-12 md:pl-12`}
                  />
                </div>

                <div className="space-y-3">
                  <label className="block text-sm font-medium text-gray-700">
                    Degree / Qualification (Select all that apply) *
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {DEGREE_OPTIONS.map((degree) => (
                      <label
                        key={degree}
                        className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm cursor-pointer transition-all ${
                          degreeSelections.includes(degree)
                            ? "border-[#0B4D67] bg-[#0B4D67]/5 text-[#0B4D67]"
                            : "border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300"
                        }`}
                      >
                        <input
                          type="checkbox"
                          value={degree}
                          checked={degreeSelections.includes(degree)}
                          onChange={() => toggleDegree(degree)}
                          className="hidden"
                        />
                        {degree}
                      </label>
                    ))}
                  </div>

                  {degreeSelections.includes("Other") && (
                    <input
                      type="text"
                      placeholder="Specify your degree"
                      value={form.degreeOther}
                      onChange={updateForm("degreeOther")}
                      required
                      className={INPUT_BASE_CLASS}
                    />
                  )}
                </div>

                <div className="relative">
                  <Calendar
                    size={18}
                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
                  />
                  <input
                    type="number"
                    placeholder="Enter years of professional experience"
                    value={form.yearsOfExperience}
                    onChange={updateForm("yearsOfExperience")}
                    onKeyDown={blockNumberInput}
                    onWheel={handleNumberWheel}
                    min="0"
                    required
                    className={`${INPUT_BASE_CLASS} pl-12 md:pl-12`}
                  />
                </div>

                <div className="space-y-3">
                  <label className="block text-sm font-medium text-gray-700">
                    Specializations (Select all that apply) *
                  </label>
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                    {SPECIALIZATION_OPTIONS.map((spec) => (
                      <label
                        key={spec}
                        className={`flex items-center gap-2 px-3 py-2.5 rounded-xl border text-sm cursor-pointer transition-all ${
                          specializations.includes(spec)
                            ? "border-[#0B4D67] bg-[#0B4D67]/5 text-[#0B4D67]"
                            : "border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300"
                        }`}
                      >
                        <input
                          type="checkbox"
                          className="hidden"
                          checked={specializations.includes(spec)}
                          onChange={() => toggleSpecialization(spec)}
                        />
                        {spec}
                      </label>
                    ))}
                  </div>

                  {specializations.includes("Other") && (
                    <input
                      type="text"
                      placeholder="Specify other specialization"
                      value={specializationOther}
                      onChange={(e) => setSpecializationOther(e.target.value)}
                      required
                      className={INPUT_BASE_CLASS}
                    />
                  )}
                </div>

                <div className="space-y-3">
                  <label className="block text-sm font-medium text-gray-700">
                    Languages Spoken (Select all that apply) *
                  </label>
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                    {LANGUAGE_OPTIONS.map((lang) => (
                      <label
                        key={lang}
                        className={`flex items-center gap-2 px-3 py-2.5 rounded-xl border text-sm cursor-pointer transition-all ${
                          languages.includes(lang)
                            ? "border-[#0B4D67] bg-[#0B4D67]/5 text-[#0B4D67]"
                            : "border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300"
                        }`}
                      >
                        <input
                          type="checkbox"
                          className="hidden"
                          checked={languages.includes(lang)}
                          onChange={() => toggleLanguage(lang)}
                        />
                        {lang}
                      </label>
                    ))}
                  </div>

                  {languages.includes("Other") && (
                    <input
                      type="text"
                      placeholder="Specify other language"
                      value={languageOther}
                      onChange={(e) => setLanguageOther(e.target.value)}
                      required
                      className={INPUT_BASE_CLASS}
                    />
                  )}
                </div>
              </section>

              {/* Availability & Timing */}
              <section className={`${CARD_CLASS} p-6 md:p-8 space-y-6`}>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-[#0B4D67]/10 flex items-center justify-center">
                    <Clock size={20} className="text-[#0B4D67]" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 text-lg">
                      Availability & Timing
                    </h3>
                    <p className="text-xs text-gray-500">
                      Set your consultation response times
                    </p>
                  </div>
                </div>

                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Day Response Time (6 AM - 8 PM) *
                    </label>
                    <div className="flex flex-wrap gap-2">
                      {RESPONSE_TIME_DAY_OPTIONS.map((option) => (
                        <label
                          key={option}
                          className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm cursor-pointer transition-all ${
                            form.responseTimeDay === option
                              ? "border-[#0B4D67] bg-[#0B4D67]/5 text-[#0B4D67]"
                              : "border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300"
                          }`}
                        >
                          <input
                            type="radio"
                            name="responseTimeDay"
                            value={option}
                            checked={form.responseTimeDay === option}
                            onChange={updateForm("responseTimeDay")}
                            required
                            className="hidden"
                          />
                          {option}
                        </label>
                      ))}
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Night Response Time (8 PM - 6 AM) *
                    </label>
                    <div className="flex flex-wrap gap-2">
                      {RESPONSE_TIME_NIGHT_OPTIONS.map((option) => (
                        <label
                          key={option}
                          className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm cursor-pointer transition-all ${
                            form.responseTimeNight === option
                              ? "border-[#0B4D67] bg-[#0B4D67]/5 text-[#0B4D67]"
                              : "border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300"
                          }`}
                        >
                          <input
                            type="radio"
                            name="responseTimeNight"
                            value={option}
                            checked={form.responseTimeNight === option}
                            onChange={updateForm("responseTimeNight")}
                            required
                            className="hidden"
                          />
                          {option}
                        </label>
                      ))}
                    </div>
                  </div>

                  <div className="space-y-3 pt-2">
                    <div className="flex items-center justify-between">
                      <label className="block text-sm font-medium text-gray-700">
                        Break / Do-Not-Disturb Time *
                      </label>
                      <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input
                          type="checkbox"
                          checked={noBreakTime}
                          onChange={handleNoBreakToggle}
                          className="w-4 h-4 text-[#0B4D67] border-gray-300 rounded focus:ring-[#0B4D67]/30"
                        />
                        No break time
                      </label>
                    </div>

                    <div className="grid gap-3 md:grid-cols-[1fr,1fr,auto] items-center">
                      <div
                        className="relative"
                        role="button"
                        tabIndex={noBreakTime ? -1 : 0}
                        aria-disabled={noBreakTime}
                        onClick={() => openTimePicker(breakStartRef)}
                        onKeyDown={handleTimePickerKey(breakStartRef)}
                      >
                        <Clock
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 z-10 pointer-events-none"
                        />
                        <div className="relative">
                          <span className="block w-full px-4 py-3.5 pl-12 rounded-xl border border-gray-200 bg-gray-50 text-sm">
                            {breakStart
                              ? formatTimeLabel(breakStart)
                              : "Select start time"}
                          </span>
                          <input
                            ref={breakStartRef}
                            type="time"
                            value={breakStart}
                            onChange={(e) => setBreakStart(e.target.value)}
                            disabled={noBreakTime}
                            aria-label="Break start time"
                            className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                          />
                        </div>
                      </div>

                      <div
                        className="relative"
                        role="button"
                        tabIndex={noBreakTime ? -1 : 0}
                        aria-disabled={noBreakTime}
                        onClick={() => openTimePicker(breakEndRef)}
                        onKeyDown={handleTimePickerKey(breakEndRef)}
                      >
                        <Clock
                          size={18}
                          className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 z-10 pointer-events-none"
                        />
                        <div className="relative">
                          <span className="block w-full px-4 py-3.5 pl-12 rounded-xl border border-gray-200 bg-gray-50 text-sm">
                            {breakEnd
                              ? formatTimeLabel(breakEnd)
                              : "Select end time"}
                          </span>
                          <input
                            ref={breakEndRef}
                            type="time"
                            value={breakEnd}
                            onChange={(e) => setBreakEnd(e.target.value)}
                            disabled={noBreakTime}
                            aria-label="Break end time"
                            className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                          />
                        </div>
                      </div>

                      <Button
                        type="button"
                        onClick={addBreakTime}
                        disabled={noBreakTime}
                        className="w-full md:w-auto px-6 py-3.5 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Add Break
                      </Button>
                    </div>

                    {breakTimes.length > 0 && (
                      <div className="flex flex-wrap gap-2 mt-3">
                        {breakTimes.map((time) => (
                          <span
                            key={time}
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#0B4D67]/10 text-[#0B4D67] text-sm"
                          >
                            {time}
                            <button
                              type="button"
                              onClick={() => removeBreakTime(time)}
                              className="hover:text-[#0B4D67]/70"
                            >
                              <X size={14} />
                            </button>
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </section>
            </div>

            {/* RIGHT COLUMN */}
            <div className="md:col-span-5 lg:col-span-4 space-y-6">
              <div className="md:sticky md:top-28 space-y-6">
                <PricingSection {...pricingProps} />
              </div>
            </div>
          </div>

          <div className="h-24 md:hidden" />
        </div>
      </PageWrap>

      {/* Mobile bottom button */}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button
          onClick={handleSubmit}
          fullWidth
          disabled={!canSubmit || submitting}
          className={`bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white py-4 rounded-xl font-semibold ${
            !canSubmit || submitting ? "opacity-50" : ""
          }`}
        >
          {submitting ? "Submitting..." : "Submit Application"}
        </Button>
      </div>

      {/* Success Modal */}
      {showRegisterSuccessModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-2xl transform animate-scale-in">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
              <CheckCircle2 size={32} />
            </div>
            <h3 className="text-2xl font-bold text-gray-900 mb-2">
              Application Submitted!
            </h3>
            <p className="text-gray-600 mb-6">
              Thank you for joining SnoutIQ. Our team will review your
              application and activate your profile within 24-48 hours.
            </p>
            <div className="bg-emerald-50 rounded-xl p-4 mb-6">
              <p className="text-sm text-emerald-800">
                <strong>Next steps:</strong> You'll receive an email
                confirmation once your profile is verified.
              </p>
            </div>
            <Button
              onClick={handleRegisterSuccessLogin}
              fullWidth
              className="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white py-4 rounded-xl font-semibold hover:shadow-lg transition-all"
            >
              Go to Login
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};

/* ---------------- 3) Pending Approval Screen ---------------- */

export const VetPendingScreen = ({ onHome }) => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-white flex flex-col items-center justify-center p-6">
      <PageWrap>
        <div className="w-full max-w-2xl mx-auto text-center">
          <div className="mb-8">
            <div className="w-24 h-24 bg-amber-100 rounded-3xl flex items-center justify-center mx-auto mb-6 transform rotate-3">
              <Clock size={48} className="text-amber-600" />
            </div>
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
              Application Received
            </h2>
            <p className="text-lg text-gray-600 mb-2">
              Thanks for submitting your application, Doctor!
            </p>
            <p className="text-gray-500">
              We're reviewing your credentials and will activate your profile
              within 24-48 hours.
            </p>
          </div>

          <div className={`${CARD_CLASS} p-8 mb-8`}>
            <div className="flex items-center gap-4 mb-6">
              <div className="w-12 h-12 rounded-full bg-[#0B4D67]/10 flex items-center justify-center">
                <Shield size={24} className="text-[#0B4D67]" />
              </div>
              <div className="text-left">
                <h3 className="font-semibold text-gray-900">
                  Verification in progress
                </h3>
                <p className="text-sm text-gray-500">
                  We'll notify you via SMS and email
                </p>
              </div>
            </div>

            <div className="space-y-3">
              <div className="flex items-center gap-3 text-sm">
                <CheckCircle2 size={16} className="text-emerald-500" />
                <span className="text-gray-600">Application received</span>
              </div>
              <div className="flex items-center gap-3 text-sm">
                <div className="w-4 h-4 border-2 border-amber-500 border-t-transparent rounded-full animate-spin" />
                <span className="text-gray-600">Credentials verification</span>
              </div>
              <div className="flex items-center gap-3 text-sm">
                <div className="w-4 h-4 rounded-full border-2 border-gray-200" />
                <span className="text-gray-400">Profile activation</span>
              </div>
            </div>
          </div>

          <Button
            onClick={onHome}
            variant="secondary"
            className="px-8 py-4 bg-white border-2 border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-semibold transition-all"
          >
            Back to Home
          </Button>
        </div>
      </PageWrap>
    </div>
  );
};

/* ---------------- 4) Vet Dashboard Screen ---------------- */

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
  const [showPrescriptionSuccessModal, setShowPrescriptionSuccessModal] =
    useState(false);
  const prescriptionSuccessTimer = useRef(null);
  const [docPreviewUrl, setDocPreviewUrl] = useState("");
  const [docZoom, setDocZoom] = useState(DOC_ZOOM_MIN);
  const [observationImages, setObservationImages] = useState([]);
  const [observationLoading, setObservationLoading] = useState(false);
  const [observationError, setObservationError] = useState("");
  const refreshTimerRef = useRef(null);
  const isFirstLoadRef = useRef(true);
  const [prescriptionForm, setPrescriptionForm] = useState({
    visitCategory: "Follow-up",
    caseSeverity: "general",
    notes: "",
    doctorTreatment: "",
    diagnosis: "",
    diagnosisStatus: "",
    treatmentPlan: "",
    homeCare: "",
    followUpDate: "",
    followUpType: "",
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
    if (!Number.isFinite(num)) return "₹0";
    return `₹${num.toLocaleString("en-IN")}`;
  };

  const formatDate = (value) => {
    if (!value) return "N/A";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString("en-IN", {
      day: "2-digit",
      month: "short",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const formatDob = (value) => {
    if (!value) return "Not available";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString("en-IN", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  };

  const formatPhone = (value) => {
    if (!value) return "";
    const digits = String(value).replace(/\D/g, "");
    if (!digits) return "";
    if (digits.length > 10 && digits.startsWith("91")) {
      return digits.slice(-10);
    }
    return digits.length > 10 ? digits.slice(-10) : digits;
  };

  const formatPetText = (value) => {
    if (!value) return "";
    const cleaned = String(value)
      .replace(/_+/g, " ")
      .replace(/\s+/g, " ")
      .trim();
    if (!cleaned) return "";
    return cleaned.replace(/\b\w/g, (char) => char.toUpperCase());
  };

  const resolvePetName = (transaction) => {
    const name = transaction?.pet?.name?.trim();
    if (name) {
      const match = name.match(/^(.*)'s\s+pet$/i);
      if (match && match[1]) {
        return formatPetText(match[1].trim()) || name;
      }
    }
    const fallback = formatPetText(
      transaction?.pet?.breed || transaction?.pet?.pet_type,
    );
    if (name) {
      return name;
    }
    const metaName =
      transaction?.metadata?.notes?.pet_name ||
      transaction?.metadata?.pet_name ||
      "";
    return metaName ? String(metaName) : fallback || "Not available";
  };

  const toDocUrl = (docValue) => {
    if (!docValue) return "";
    const raw = String(docValue).trim();
    if (!raw) return "";
    if (/^https?:\/\//i.test(raw)) return raw;
    const base = apiBaseUrl().replace(/\/+$/, "");
    const cleanPath = raw.replace(/^\/+/, "");
    return `${base}/${cleanPath}`;
  };

  const getDocFilename = (url) => {
    if (!url) return "document";
    try {
      const parsed = new URL(url);
      const name = parsed.pathname.split("/").filter(Boolean).pop();
      return name || "document";
    } catch {
      const parts = String(url).split("/").filter(Boolean);
      return parts[parts.length - 1] || "document";
    }
  };

  const isImageUrl = (url) =>
    /^data:image\//i.test(url || "") ||
    /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(url || "");

  const isImageMime = (value) =>
    String(value || "")
      .toLowerCase()
      .startsWith("image/");

  const isBackendUrl = (url) => {
    if (!url) return false;
    const base = apiBaseUrl().replace(/\/+$/, "");
    return String(url).startsWith(base);
  };

  const getObservationImageUrl = (observation) => {
    const raw = observation?.image_blob_url || observation?.image_url || "";
    return toDocUrl(raw);
  };

  const statusClass = (status) => {
    const key = (status || "").toLowerCase();
    if (key === "pending") return "bg-amber-50 text-amber-700 border-amber-200";
    if (["paid", "success", "captured", "completed"].includes(key)) {
      return "bg-emerald-50 text-emerald-700 border-emerald-200";
    }
    if (["failed", "cancelled", "canceled", "refunded"].includes(key)) {
      return "bg-rose-50 text-rose-700 border-rose-200";
    }
    return "bg-gray-100 text-gray-600 border-gray-200";
  };

  const statusLabel = (status) => {
    if (!status) return "unknown";
    const key = status.toLowerCase();
    if (key === "captured") return "paid";
    return status.replace(/_/g, " ");
  };

  const doctorId = normalizeId(
    auth?.doctor_id ||
      auth?.doctor?.id ||
      auth?.doctor?.doctor_id ||
      auth?.doctor?.doctorId,
  );

  const clinicIdRaw = normalizeId(
    auth?.clinic_id ||
      auth?.doctor?.clinic_id ||
      auth?.doctor?.vet_registeration_id ||
      auth?.doctor?.vet_registration_id ||
      auth?.doctor?.clinicId,
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
      userId:
        transaction?.user?.id || metadata?.user_id || notes?.user_id || "",
      petId: transaction?.pet?.id || metadata?.pet_id || notes?.pet_id || "",
      doctorId:
        doctorId ||
        normalizeId(
          transaction?.doctor?.id || metadata?.doctor_id || notes?.doctor_id,
        ),
      clinicId:
        clinicIdRaw || normalizeId(metadata?.clinic_id || notes?.clinic_id),
    };
  };

  useEffect(() => {
    if (!showPatientModal || !activeTransaction) {
      setObservationImages([]);
      setObservationLoading(false);
      setObservationError("");
      return;
    }

    const { userId, petId } = resolveTransactionIds(activeTransaction);
    if (!userId || !petId) {
      setObservationImages([]);
      setObservationLoading(false);
      setObservationError("");
      return;
    }

    const controller = new AbortController();
    const url = `${apiBaseUrl()}/api/user-per-observationss?pet_id=${encodeURIComponent(
      petId,
    )}&user_id=${encodeURIComponent(userId)}&limit=20`;

    setObservationLoading(true);
    setObservationError("");

    fetch(url, { signal: controller.signal })
      .then((res) => res.json())
      .then((data) => {
        const observations =
          data?.data?.observations || data?.observations || [];
        const images = observations
          .map((obs) => {
            const imageUrl = getObservationImageUrl(obs);
            if (!imageUrl || !isBackendUrl(imageUrl)) return null;
            return {
              id: obs?.id || imageUrl,
              url: imageUrl,
              name: obs?.image_name || "",
              mime: obs?.image_mime || "",
              timestamp: obs?.timestamp || obs?.created_at || "",
              notes: obs?.notes || "",
            };
          })
          .filter(Boolean);
        setObservationImages(images);
      })
      .catch((error) => {
        if (error?.name === "AbortError") return;
        setObservationError("Unable to load observation images.");
      })
      .finally(() => setObservationLoading(false));

    return () => controller.abort();
  }, [showPatientModal, activeTransaction]);

  const resetPrescriptionForm = () => {
    setPrescriptionForm({
      visitCategory: "Follow-up",
      caseSeverity: "general",
      notes: "",
      doctorTreatment: "",
      diagnosis: "",
      diagnosisStatus: "",
      treatmentPlan: "",
      homeCare: "",
      followUpDate: "",
      followUpType: "",
      medications: [{ name: "", dosage: "", frequency: "", duration: "" }],
      recordFile: null,
    });
    setPrescriptionError("");
    setPrescriptionSuccess(false);
  };

  const openPatientModal = (transaction) => {
    console.log("Consultation Overview API data:", transaction);
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
    setDocPreviewUrl("");
  };

  const closePrescriptionModal = () => {
    setShowPrescriptionModal(false);
    setActiveTransaction(null);
    setPrescriptionSubmitting(false);
    setPrescriptionSuccess(false);
    setShowPrescriptionSuccessModal(false);
    setDocPreviewUrl("");
    if (prescriptionSuccessTimer.current) {
      window.clearTimeout(prescriptionSuccessTimer.current);
      prescriptionSuccessTimer.current = null;
    }
  };

  const updatePrescriptionField = (key) => (event) => {
    setPrescriptionForm((prev) => ({ ...prev, [key]: event.target.value }));
  };

  const updateMedication = (index, key) => (event) => {
    const value = event.target.value;
    setPrescriptionForm((prev) => ({
      ...prev,
      medications: prev.medications.map((med, idx) =>
        idx === index ? { ...med, [key]: value } : med,
      ),
    }));
  };

  const addMedication = () => {
    setPrescriptionForm((prev) => ({
      ...prev,
      medications: [
        ...prev.medications,
        { name: "", dosage: "", frequency: "", duration: "" },
      ],
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

    const { userId, petId, doctorId, clinicId } =
      resolveTransactionIds(activeTransaction);
    if (!userId || !clinicId) {
      setPrescriptionError(
        "Missing patient or clinic data. Please refresh and try again.",
      );
      setPrescriptionSubmitting(false);
      return;
    }

    const medsPayload = prescriptionForm.medications
      .map((med) => ({
        name: med.name.trim(),
        dose: med.dosage.trim(),
        frequency: med.frequency.trim(),
        duration: med.duration.trim(),
      }))
      .filter((med) => med.name || med.dose || med.frequency || med.duration);

    const fd = new FormData();
    fd.append("user_id", String(userId));
    fd.append("clinic_id", String(clinicId));
    if (doctorId) fd.append("doctor_id", String(doctorId));
    if (petId) fd.append("pet_id", String(petId));
    fd.append("visit_category", prescriptionForm.visitCategory);
    fd.append("case_severity", prescriptionForm.caseSeverity);
    fd.append("notes", prescriptionForm.notes);
    if (prescriptionForm.doctorTreatment.trim()) {
      fd.append("doctor_treatment", prescriptionForm.doctorTreatment.trim());
    }
    if (prescriptionForm.diagnosis.trim()) {
      fd.append("diagnosis", prescriptionForm.diagnosis.trim());
    }
    if (prescriptionForm.diagnosisStatus) {
      fd.append("diagnosis_status", prescriptionForm.diagnosisStatus);
    }
    if (prescriptionForm.treatmentPlan.trim()) {
      fd.append("treatment_plan", prescriptionForm.treatmentPlan.trim());
    }
    if (prescriptionForm.homeCare.trim()) {
      fd.append("home_care", prescriptionForm.homeCare.trim());
    }
    if (prescriptionForm.followUpDate) {
      fd.append("follow_up_date", prescriptionForm.followUpDate);
    }
    if (prescriptionForm.followUpType.trim()) {
      fd.append("follow_up_type", prescriptionForm.followUpType.trim());
    }
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
      setShowPrescriptionSuccessModal(true);
    } catch (error) {
      setPrescriptionError(error?.message || "Failed to save prescription.");
    } finally {
      setPrescriptionSubmitting(false);
    }
  };

  const closePrescriptionSuccessModal = () => {
    setShowPrescriptionSuccessModal(false);
    setPrescriptionSuccess(false);
    if (prescriptionSuccessTimer.current) {
      window.clearTimeout(prescriptionSuccessTimer.current);
      prescriptionSuccessTimer.current = null;
    }
    closePrescriptionModal();
    resetPrescriptionForm();
  };

  const handleDocPreview = (url, mime) => {
    if (!url) return;
    if (isImageMime(mime) || isImageUrl(url)) {
      setDocZoom(DOC_ZOOM_MIN);
      setDocPreviewUrl(url);
    } else {
      window.open(url, "_blank", "noopener,noreferrer");
    }
  };

  const closeDocPreview = () => {
    setDocPreviewUrl("");
  };

  const zoomInDoc = () =>
    setDocZoom((prev) => clampDocZoom(prev + DOC_ZOOM_STEP));

  const zoomOutDoc = () =>
    setDocZoom((prev) => clampDocZoom(prev - DOC_ZOOM_STEP));

  const resetDocZoom = () => setDocZoom(DOC_ZOOM_MIN);

  const openDocInNewTab = () => {
    if (!docPreviewUrl) return;
    window.open(docPreviewUrl, "_blank", "noopener,noreferrer");
  };

  const docFilename = useMemo(
    () => getDocFilename(docPreviewUrl),
    [docPreviewUrl],
  );

  useEffect(() => {
    if (!showPrescriptionSuccessModal) return undefined;
    prescriptionSuccessTimer.current = window.setTimeout(() => {
      closePrescriptionSuccessModal();
    }, 2000);
    return () => {
      if (prescriptionSuccessTimer.current) {
        window.clearTimeout(prescriptionSuccessTimer.current);
        prescriptionSuccessTimer.current = null;
      }
    };
  }, [showPrescriptionSuccessModal]);

  useEffect(() => {
    if (!auth) return undefined;
    if (!doctorId) {
      setLoadError("Missing doctor ID. Please log in again.");
      setIsLoading(false);
      return undefined;
    }

    let active = true;
    let controller = null;

    const fetchTransactions = async () => {
      if (!active) return;
      if (controller) controller.abort();
      controller = new AbortController();

      const showLoading = isFirstLoadRef.current;
      if (showLoading) {
        setIsLoading(true);
      }
      setLoadError("");

      try {
        let clinicId = clinicIdRaw;
        if (!clinicId) {
          const resolvedClinicId = await fetchClinicIdForDoctor(
            doctorId,
            authToken,
            controller.signal,
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
          doctorId,
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
        setTransactions(
          Array.isArray(data?.transactions) ? data.transactions : [],
        );
      } catch (error) {
        if (error?.name !== "AbortError") {
          setLoadError(error?.message || "Failed to load transactions.");
        }
      } finally {
        if (showLoading) {
          setIsLoading(false);
          isFirstLoadRef.current = false;
        }
      }
    };

    fetchTransactions();

    if (refreshTimerRef.current) {
      window.clearInterval(refreshTimerRef.current);
    }
    refreshTimerRef.current = window.setInterval(fetchTransactions, 30000);

    const handleVisibility = () => {
      if (document.visibilityState === "visible") {
        fetchTransactions();
      }
    };
    document.addEventListener("visibilitychange", handleVisibility);

    return () => {
      active = false;
      if (controller) controller.abort();
      if (refreshTimerRef.current) {
        window.clearInterval(refreshTimerRef.current);
        refreshTimerRef.current = null;
      }
      document.removeEventListener("visibilitychange", handleVisibility);
    };
  }, [auth, authToken, clinicIdRaw, doctorId]);

  const handleLogout = () => {
    clearVetAuth();
    onLogout?.();
  };

  const doctorName =
    auth?.doctor?.doctor_name ||
    auth?.doctor_name ||
    auth?.doctor?.name ||
    "Doctor";
  const doctorEmail =
    auth?.doctor?.doctor_email || auth?.doctor_email || auth?.email || "";
  const doctorPhone =
    auth?.doctor?.doctor_mobile || auth?.doctor_mobile || auth?.phone || "";

  const initials =
    doctorName
      .split(" ")
      .filter(Boolean)
      .map((part) => part[0])
      .slice(0, 2)
      .join("")
      .toUpperCase() || "DR";

  const {
    totalAmount,
    totalTransactions,
    pendingCount,
    completedCount,
    failedCount,
    latestTransactions,
    lastUpdated,
  } = useMemo(() => {
    const totalAmountValue = dashboardData?.total_amount_inr ?? 0;
    const totalTransactionsValue =
      dashboardData?.total_transactions ?? transactions.length;
    const pendingValue = transactions.filter(
      (item) => (item?.status || "").toLowerCase() === "pending",
    ).length;
    const completedValue = transactions.filter((item) =>
      ["paid", "success", "captured", "completed"].includes(
        (item?.status || "").toLowerCase(),
      ),
    ).length;
    const failedValue = transactions.filter((item) =>
      ["failed", "cancelled", "canceled", "refunded"].includes(
        (item?.status || "").toLowerCase(),
      ),
    ).length;

    const latest = transactions
      .slice()
      .sort(
        (a, b) =>
          new Date(b?.created_at || 0).getTime() -
          new Date(a?.created_at || 0).getTime(),
      )
      .slice(0, 6);

    const lastUpdatedValue =
      latest[0]?.updated_at || latest[0]?.created_at || "";

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

  return (
    <div
      className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col animate-fade-in"
      data-vet-dashboard="true"
    >
      {/* Header with Gradient */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-500 text-white">
        <div className="px-6 py-6 md:px-12 lg:px-20 md:py-8">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center shadow-xl">
                <span className="text-2xl md:text-3xl font-bold">
                  {initials}
                </span>
              </div>
              <div>
                <h1 className="text-2xl md:text-3xl font-bold mb-1">
                  {doctorName}
                </h1>
                <div className="flex items-center gap-2 text-white/80 text-sm">
                  <Shield size={16} />
                  <span>Verified Veterinary Partner</span>
                </div>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="hidden md:flex items-center gap-2 text-white/70 text-sm bg-white/10 px-4 py-2 rounded-xl">
                <Clock size={16} />
                <span>
                  Last updated:{" "}
                  {isLoading ? "Loading..." : formatDate(lastUpdated)}
                </span>
              </div>
              <button
                onClick={handleLogout}
                className="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium"
              >
                <LogOut size={16} />
                Logout
              </button>
            </div>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">
            <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/20">
              <p className="text-white/70 text-xs uppercase mb-1">
                Total Earnings
              </p>
              <p className="text-2xl md:text-3xl font-bold">
                {isLoading ? "..." : formatAmount(totalAmount)}
              </p>
              <p className="text-white/60 text-xs mt-1">All time</p>
            </div>
            <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/20">
              <p className="text-white/70 text-xs uppercase mb-1">
                Consultations
              </p>
              <p className="text-2xl md:text-3xl font-bold">
                {isLoading ? "..." : totalTransactions}
              </p>
              <p className="text-white/60 text-xs mt-1">Total transactions</p>
            </div>
            <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/20">
              <p className="text-white/70 text-xs uppercase mb-1">Pending</p>
              <p className="text-2xl md:text-3xl font-bold">
                {isLoading ? "..." : pendingCount}
              </p>
              <p className="text-white/60 text-xs mt-1">Awaiting action</p>
            </div>
          </div>
        </div>
      </div>

      <PageWrap>
        <div className="px-6 py-8 md:px-0 md:py-10">
          {loadError ? (
            <div className="flex items-start gap-3 text-red-600 bg-red-50 border border-red-200 p-5 rounded-2xl mb-6">
              <AlertCircle size={20} className="flex-shrink-0" />
              <div>
                <p className="font-medium">Error loading dashboard</p>
                <p className="text-sm text-red-600/80 mt-1">{loadError}</p>
              </div>
            </div>
          ) : null}

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
            {/* Main Content - Transactions */}
            <div className="lg:col-span-8 space-y-6">
              <div className="flex items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                  <History size={20} className="text-[#0B4D67]" />
                  Recent Consultations
                </h2>
                <span className="text-xs text-gray-500 bg-white px-3 py-1.5 rounded-full border border-gray-200">
                  Auto-refreshes every 30s
                </span>
              </div>

              <div className={`${CARD_CLASS} overflow-hidden`}>
                {isLoading ? (
                  <div className="p-8 text-center">
                    <div className="inline-block w-8 h-8 border-3 border-[#0B4D67] border-t-transparent rounded-full animate-spin mb-3"></div>
                    <p className="text-gray-500">Loading transactions...</p>
                  </div>
                ) : latestTransactions.length > 0 ? (
                  <div className="divide-y divide-gray-100">
                    {latestTransactions.map((item, idx) => {
                      const amountInr =
                        item?.amount_inr ??
                        (item?.amount_paise ? item.amount_paise / 100 : 0);
                      const petName = resolvePetName(item);
                      const { userId, clinicId } = resolveTransactionIds(item);
                      const canOpenPrescription = Boolean(userId && clinicId);
                      const canView = Boolean(item?.user || item?.pet);

                      return (
                        <div
                          key={item?.id || idx}
                          className="p-5 hover:bg-gray-50/50 transition-colors"
                        >
                          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div className="flex-1">
                              <div className="flex items-center gap-2 mb-1">
                                <span className="font-semibold text-gray-900">
                                  {item?.user?.name || "Pet Parent"}
                                </span>
                                <span
                                  className={`px-2 py-1 rounded-full text-xs font-medium border ${statusClass(item?.status)}`}
                                >
                                  {statusLabel(item?.status)}
                                </span>
                              </div>
                              <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                <PawPrint size={14} />
                                <span>{petName}</span>
                                <span className="text-gray-400">•</span>
                                <span>{item?.pet?.breed || "Pet"}</span>
                              </div>
                              <div className="flex items-center gap-2 text-xs text-gray-400">
                                <span>
                                  Ref:{" "}
                                  {item?.reference ||
                                    item?.metadata?.order_id ||
                                    "N/A"}
                                </span>
                                <span>•</span>
                                <span>{formatDate(item?.created_at)}</span>
                              </div>
                            </div>

                            <div className="flex items-center gap-3">
                              <span className="font-bold text-gray-900">
                                {formatAmount(amountInr)}
                              </span>
                              <div className="flex gap-2">
                                <button
                                  onClick={() => openPatientModal(item)}
                                  disabled={!canView}
                                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                  View
                                </button>
                                <button
                                  onClick={() => openPrescriptionModal(item)}
                                  disabled={!canOpenPrescription}
                                  className="px-4 py-2 text-sm font-medium text-white rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                  Prescribe
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <div className="p-12 text-center">
                    <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <History size={24} className="text-gray-400" />
                    </div>
                    <p className="text-gray-600 font-medium">
                      No consultations yet
                    </p>
                    <p className="text-sm text-gray-400 mt-1">
                      Your consultations will appear here
                    </p>
                  </div>
                )}
              </div>

              <p className="text-xs text-gray-400 flex items-center gap-1.5 bg-white p-3 rounded-xl border border-gray-100">
                <Lock size={12} className="text-[#0B4D67]" />
                Patient contact details are encrypted and remain private
              </p>
            </div>

            {/* Sidebar - Account Info & Tips */}
            <div className="lg:col-span-4 space-y-6">
              <div className={`${CARD_CLASS} p-6`}>
                <h3 className="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <User size={18} className="text-[#0B4D67]" />
                  Account Information
                </h3>
                <div className="space-y-4">
                  <div>
                    <p className="text-xs text-gray-400 uppercase mb-1">
                      Doctor Name
                    </p>
                    <p className="font-medium text-gray-900">{doctorName}</p>
                  </div>
                  {doctorEmail && (
                    <div>
                      <p className="text-xs text-gray-400 uppercase mb-1">
                        Email
                      </p>
                      <p className="text-sm text-gray-700">{doctorEmail}</p>
                    </div>
                  )}
                  {doctorPhone && (
                    <div>
                      <p className="text-xs text-gray-400 uppercase mb-1">
                        Phone
                      </p>
                      <p className="text-sm text-gray-700">{doctorPhone}</p>
                    </div>
                  )}
                  <div className="pt-2 border-t border-gray-100">
                    <p className="text-xs text-gray-400 uppercase mb-1">
                      Account Status
                    </p>
                    <div className="flex items-center gap-2">
                      <span className="w-2 h-2 bg-emerald-500 rounded-full"></span>
                      <span className="text-sm font-medium text-emerald-700">
                        Active
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-6 border border-amber-100">
                <div className="flex items-start gap-3">
                  <div className="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <Star size={20} className="text-amber-600" />
                  </div>
                  <div>
                    <h4 className="font-semibold text-amber-800 mb-1">
                      Pro Tip
                    </h4>
                    <ul className="mt-2 space-y-2 text-xs text-amber-700">
                      {[
                        "Respond to consultations promptly",
                        "Update prescriptions after every session",
                        "Use your registered WhatsApp number for video calls",
                        "Only the registered doctor should conduct video consultations",
                      ].map((tip) => (
                        <li key={tip} className="flex items-start gap-2">
                          <CheckCircle2
                            size={14}
                            className="mt-0.5 text-amber-500"
                          />
                          <span className="leading-relaxed">{tip}</span>
                        </li>
                      ))}
                    </ul>
                    <p className="mt-3 text-xs text-amber-700 leading-relaxed">
                      Following these steps helps avoid cancellations and
                      ensures a smooth experience.
                    </p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-2xl p-6 border border-gray-100">
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-8 h-8 rounded-full bg-[#0B4D67]/10 flex items-center justify-center">
                    <TrendingUp size={16} className="text-[#0B4D67]" />
                  </div>
                  <h4 className="font-semibold text-gray-900">Quick Stats</h4>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="bg-gray-50 rounded-xl p-3">
                    <p className="text-xs text-gray-500">Completed</p>
                    <p className="text-xl font-bold text-gray-900">
                      {completedCount}
                    </p>
                  </div>
                  <div className="bg-gray-50 rounded-xl p-3">
                    <p className="text-xs text-gray-500">Failed</p>
                    <p className="text-xl font-bold text-gray-900">
                      {failedCount}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </PageWrap>

      {/* Patient Details Modal */}
      {showPatientModal && activeTransaction && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
          <div className="w-full max-w-3xl bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div className="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                  <User size={20} className="text-white" />
                </div>
                <div>
                  <p className="text-white/70 text-xs">Patient Details</p>
                  <h3 className="text-white font-semibold">
                    Consultation Overview
                  </h3>
                </div>
              </div>
              <button
                onClick={closePatientModal}
                className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
              >
                <X size={16} className="text-white" />
              </button>
            </div>

            <div className="p-6 max-h-[70vh] overflow-y-auto">
              <div className="grid md:grid-cols-2 gap-4 mb-4">
                <div className="bg-gray-50 rounded-xl p-4">
                  <h4 className="text-xs font-semibold text-gray-400 uppercase mb-3">
                    Pet Parent
                  </h4>
                  <p className="font-semibold text-gray-900">
                    {activeTransaction?.user?.name || "Not available"}
                  </p>
                  {activeTransaction?.user?.phone && (
                    <p className="text-sm text-gray-600 mt-1">
                      📱 {formatPhone(activeTransaction.user.phone)}
                    </p>
                  )}
                </div>

                <div className="bg-gray-50 rounded-xl p-4">
                  <h4 className="text-xs font-semibold text-gray-400 uppercase mb-3">
                    Pet Details
                  </h4>
                  <div className="space-y-2">
                    <p className="text-sm">
                      <span className="text-gray-500">Name:</span>{" "}
                      <span className="font-medium">
                        {resolvePetName(activeTransaction)}
                      </span>
                    </p>
                    <p className="text-sm">
                      <span className="text-gray-500">Breed:</span>{" "}
                      {formatPetText(activeTransaction?.pet?.breed) ||
                        "Not available"}
                    </p>
                    <p className="text-sm">
                      <span className="text-gray-500">Type:</span>{" "}
                      {formatPetText(activeTransaction?.pet?.pet_type) ||
                        "Not available"}
                    </p>
                    {activeTransaction?.pet?.pet_dob && (
                      <p className="text-sm">
                        <span className="text-gray-500">DOB:</span>{" "}
                        {formatDob(activeTransaction.pet.pet_dob)}
                      </p>
                    )}
                  </div>
                </div>
              </div>

              {activeTransaction?.pet?.reported_symptom && (
                <div className="bg-gray-50 rounded-xl p-4 mb-4">
                  <h4 className="text-xs font-semibold text-gray-400 uppercase mb-2">
                    Reported Symptoms
                  </h4>
                  <p className="text-gray-700">
                    {activeTransaction.pet.reported_symptom}
                  </p>
                </div>
              )}

              {(observationLoading ||
                observationError ||
                observationImages.length > 0) && (
                <div className="bg-gray-50 rounded-xl p-4 mb-4">
                  <h4 className="text-xs font-semibold text-gray-400 uppercase mb-2">
                    Observation Images
                  </h4>
                  {observationLoading ? (
                    <p className="text-sm text-gray-500">
                      Loading observation images...
                    </p>
                  ) : observationError ? (
                    <p className="text-sm text-rose-600">{observationError}</p>
                  ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                      {observationImages.map((image) => (
                        <button
                          key={image.id}
                          type="button"
                          onClick={() =>
                            handleDocPreview(image.url, image.mime)
                          }
                          className="text-left rounded-2xl border border-gray-200 bg-white p-3 transition hover:border-[#0B4D67]/40 hover:shadow-sm"
                        >
                          <div className="overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                            <img
                              src={image.url}
                              alt={image.name || "Observation image"}
                              className="h-40 w-full object-cover"
                              loading="lazy"
                            />
                          </div>
                          {image.timestamp ? (
                            <p className="mt-2 text-xs text-gray-500">
                              {formatDate(image.timestamp)}
                            </p>
                          ) : null}
                          {image.notes ? (
                            <p className="mt-1 text-sm text-gray-700 line-clamp-2">
                              {image.notes}
                            </p>
                          ) : null}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}

              <div className="bg-gray-50 rounded-xl p-4 mb-4">
                <h4 className="text-xs font-semibold text-gray-400 uppercase mb-2">
                  Pet Document
                </h4>
                {(() => {
                  const docUrl = toDocUrl(activeTransaction?.pet?.pet_doc2);
                  const hasImage = docUrl && isImageUrl(docUrl);
                  return docUrl ? (
                    <button
                      type="button"
                      onClick={() => handleDocPreview(docUrl)}
                      className="w-full text-left rounded-2xl border border-gray-200 bg-white p-4 transition hover:border-[#0B4D67]/40 hover:shadow-sm"
                    >
                      <div className="flex items-start gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0B4D67]/10">
                          <FileText size={20} className="text-[#0B4D67]" />
                        </div>
                        <div className="flex-1">
                          <p className="text-sm font-semibold text-gray-900">
                            Document available
                          </p>
                          <p className="text-xs text-gray-500">
                            Tap to preview, zoom, or download.
                          </p>
                        </div>
                        <span className="text-xs font-semibold text-[#0B4D67]">
                          Open
                        </span>
                      </div>
                      {hasImage ? (
                        <div className="mt-3 overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                          <img
                            src={docUrl}
                            alt="Pet document preview"
                            className="h-40 w-full object-cover"
                          />
                        </div>
                      ) : null}
                    </button>
                  ) : (
                    <p className="text-sm text-gray-500">
                      No document uploaded
                    </p>
                  );
                })()}
              </div>

              <div className="flex justify-end">
                <button
                  onClick={closePatientModal}
                  className="px-6 py-2.5 text-white rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 transition-colors font-medium"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Document Preview Modal */}
      {docPreviewUrl && (
        <div className="fixed inset-0 z-[55] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
          <div className="w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div className="flex items-center justify-between bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4 text-white">
              <div className="flex items-center gap-2 text-sm font-semibold">
                <FileText size={18} />
                Document Preview
              </div>
              <button
                onClick={closeDocPreview}
                className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                aria-label="Close document preview"
              >
                <X size={16} />
              </button>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-white px-6 py-3 text-xs text-gray-500">
              <div className="font-medium">{docFilename}</div>
              <div className="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  onClick={zoomOutDoc}
                  disabled={docZoom <= DOC_ZOOM_MIN}
                  className="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1 text-gray-600 transition hover:bg-gray-50 disabled:opacity-50"
                >
                  <ZoomOut size={14} />
                  Zoom out
                </button>
                <button
                  type="button"
                  onClick={zoomInDoc}
                  disabled={docZoom >= DOC_ZOOM_MAX}
                  className="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1 text-gray-600 transition hover:bg-gray-50 disabled:opacity-50"
                >
                  <ZoomIn size={14} />
                  Zoom in
                </button>
                <button
                  type="button"
                  onClick={resetDocZoom}
                  className="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1 text-gray-600 transition hover:bg-gray-50"
                >
                  <RotateCcw size={14} />
                  Reset
                </button>
                <button
                  type="button"
                  onClick={openDocInNewTab}
                  className="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1 text-gray-600 transition hover:bg-gray-50"
                >
                  <ExternalLink size={14} />
                  Open in new tab
                </button>
                <a
                  href={docPreviewUrl}
                  download={docFilename}
                  className="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1 text-gray-600 transition hover:bg-gray-50"
                >
                  <Download size={14} />
                  Download
                </a>
              </div>
            </div>
            <div className="max-h-[75vh] overflow-auto bg-black/5 p-4">
              <img
                src={docPreviewUrl}
                alt="Uploaded document"
                className="w-full rounded-2xl bg-white"
                style={{
                  transform: `scale(${docZoom})`,
                  transformOrigin: "center center",
                }}
              />
            </div>
          </div>
        </div>
      )}

      {/* Prescription Modal */}
      {showPrescriptionModal && activeTransaction && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
          <div className="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div className="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                  <FileText size={20} className="text-white" />
                </div>
                <div>
                  <p className="text-white/70 text-xs">Create Prescription</p>
                  <h3 className="text-white font-semibold">Medical Record</h3>
                </div>
              </div>
              <button
                onClick={closePrescriptionModal}
                className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
              >
                <X size={16} className="text-white" />
              </button>
            </div>

            <form
              onSubmit={handlePrescriptionSubmit}
              className="p-6 max-h-[70vh] overflow-y-auto"
            >
              <div className="grid lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                  {/* Patient Info Summary */}
                  <div className="bg-gray-50 rounded-xl p-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-xs text-gray-400">Patient</p>
                        <p className="font-semibold text-gray-900">
                          {activeTransaction?.user?.name || "Pet Parent"}
                        </p>
                        <p className="text-sm text-gray-600">
                          {resolvePetName(activeTransaction)}
                        </p>
                      </div>
                      <span
                        className={`px-3 py-1.5 rounded-full text-xs font-medium border ${statusClass(activeTransaction?.status)}`}
                      >
                        {statusLabel(activeTransaction?.status)}
                      </span>
                    </div>
                  </div>

                  {/* Consultation Basics */}
                  <div className="bg-white border border-gray-200 rounded-xl p-4 space-y-4">
                    <h4 className="font-medium text-gray-900 flex items-center gap-2">
                      <FileText size={16} className="text-[#0B4D67]" />
                      Consultation Details
                    </h4>
                    <div className="grid sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">
                          Visit Category
                        </label>
                        <select
                          value={prescriptionForm.visitCategory}
                          onChange={updatePrescriptionField("visitCategory")}
                          className={INPUT_BASE_CLASS}
                        >
                          <option value="Follow-up">Follow-up</option>
                          <option value="New Consultation">
                            New Consultation
                          </option>
                          <option value="Emergency">Emergency</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">
                          Case Severity
                        </label>
                        <select
                          value={prescriptionForm.caseSeverity}
                          onChange={updatePrescriptionField("caseSeverity")}
                          className={INPUT_BASE_CLASS}
                        >
                          <option value="general">General</option>
                          <option value="moderate">Moderate</option>
                          <option value="critical">Critical</option>
                        </select>
                      </div>
                    </div>
                    <div className="grid sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">
                          Diagnosis
                        </label>
                        <input
                          type="text"
                          value={prescriptionForm.diagnosis}
                          onChange={updatePrescriptionField("diagnosis")}
                          placeholder="Diagnosis summary"
                          className={INPUT_BASE_CLASS}
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">
                          Diagnosis Status
                        </label>
                        <select
                          value={prescriptionForm.diagnosisStatus}
                          onChange={updatePrescriptionField("diagnosisStatus")}
                          className={INPUT_BASE_CLASS}
                        >
                          <option value="">Select status</option>
                          <option value="ongoing">Ongoing</option>
                          <option value="resolved">Resolved</option>
                          <option value="chronic">Chronic</option>
                        </select>
                      </div>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Clinical Notes
                      </label>
                      <textarea
                        value={prescriptionForm.notes}
                        onChange={updatePrescriptionField("notes")}
                        rows={4}
                        placeholder="Enter diagnosis, observations, and treatment plan..."
                        className={`${INPUT_BASE_CLASS} resize-none`}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Doctor Treatment
                      </label>
                      <textarea
                        value={prescriptionForm.doctorTreatment}
                        onChange={updatePrescriptionField("doctorTreatment")}
                        rows={3}
                        placeholder="Doctor treatment instructions"
                        className={`${INPUT_BASE_CLASS} resize-none`}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Treatment Plan
                      </label>
                      <textarea
                        value={prescriptionForm.treatmentPlan}
                        onChange={updatePrescriptionField("treatmentPlan")}
                        rows={3}
                        placeholder="Treatment plan"
                        className={`${INPUT_BASE_CLASS} resize-none`}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 mb-1">
                        Home Care
                      </label>
                      <textarea
                        value={prescriptionForm.homeCare}
                        onChange={updatePrescriptionField("homeCare")}
                        rows={3}
                        placeholder="Home care guidance"
                        className={`${INPUT_BASE_CLASS} resize-none`}
                      />
                    </div>
                    <div className="grid sm:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">
                          Follow-up Date
                        </label>
                        <input
                          type="date"
                          value={prescriptionForm.followUpDate}
                          onChange={updatePrescriptionField("followUpDate")}
                          className={INPUT_BASE_CLASS}
                        />
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">
                          Follow-up Type
                        </label>
                        <input
                          type="text"
                          value={prescriptionForm.followUpType}
                          onChange={updatePrescriptionField("followUpType")}
                          placeholder="clinic / video / chat"
                          className={INPUT_BASE_CLASS}
                        />
                      </div>
                    </div>
                  </div>

                  {/* Medications */}
                  <div className="bg-white border border-gray-200 rounded-xl p-4 space-y-4">
                    <h4 className="font-medium text-gray-900 flex items-center gap-2">
                      <Pill size={16} className="text-[#0B4D67]" />
                      Medications
                    </h4>
                    <div className="space-y-3">
                      {prescriptionForm.medications.map((medication, index) => (
                        <div
                          key={index}
                          className="grid sm:grid-cols-5 gap-2 items-start"
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
                        {prescriptionForm.recordFile?.name ||
                          "PDF, PNG, JPG supported"}
                      </span>
                    </label>
                  </div>
                </div>

                <aside className="space-y-4 lg:sticky lg:top-4 self-start">
                  <div className="rounded-2xl border border-stone-100 bg-white p-4 shadow-sm">
                    <div className="text-xs uppercase text-stone-400">
                      Consult Summary
                    </div>
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
                              (activeTransaction?.amount_paise
                                ? activeTransaction.amount_paise / 100
                                : 0),
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
                    <div className="text-xs uppercase text-stone-400">
                      Submission
                    </div>
                    <p className="mt-2 text-xs text-stone-500">
                      Please ensure all fields are complete before saving the
                      prescription.
                    </p>
                  </div>

                  {prescriptionError ? (
                    <div className="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600">
                      <AlertCircle size={16} />
                      <span>{prescriptionError}</span>
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
                      className={`rounded-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 px-6 py-2 text-sm font-semibold text-white ${
                        prescriptionSubmitting
                          ? "opacity-60 cursor-not-allowed"
                          : ""
                      }`}
                    >
                      {prescriptionSubmitting
                        ? "Saving..."
                        : "Save Prescription"}
                    </button>
                  </div>
                </aside>
              </div>
            </form>
          </div>
        </div>
      )}

      {showPrescriptionSuccessModal ? (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
              <CheckCircle2 size={28} />
            </div>
            <div className="text-lg font-bold text-stone-800">
              Prescription saved
            </div>
            <p className="mt-2 text-sm text-stone-500">
              Medical record has been sent successfully.
            </p>
            <Button
              onClick={closePrescriptionSuccessModal}
              fullWidth
              className="mt-4 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600"
            >
              Done
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
