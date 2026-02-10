import React, { useState } from "react";
import { Button } from "./Button";
import { apiPost } from "../lib/api";
import {
  ChevronLeft,
  Camera,
  CheckCircle2,
  MapPin,
  Clock,
  DollarSign,
  TrendingUp,
  Star,
  AlertCircle,
  Moon,
  Sun,
  History,
  Lock,
  Zap,
} from "lucide-react";
import logo from "../assets/images/logo.png";

/**
 * Mobile UI SAME.
 * Desktop/Tablet (md+):
 * - Full width (no max container)
 * - Bigger typography + spacing
 * - Bigger buttons (md+ only)
 * - Register/Dashboard use roomy grid
 */

// --- Internal Helper Components ---

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

      {onBack ? <div className="w-10" /> : <div className="w-10" />}
    </div>
  </div>
);

// ✅ Full-width desktop wrapper (mobile unaffected)
const PageWrap = ({ children }) => (
  <div className="w-full md:px-10 lg:px-16">{children}</div>
);

// --- 1. Vet Login Screen ---

export const VetLoginScreen = ({ onLogin, onRegisterClick, onBack }) => {
  const [mobile, setMobile] = useState("");
  const [otp, setOtp] = useState("");
  const [step, setStep] = useState("mobile"); // 'mobile' | 'otp'

  const handleSendOtp = () => {
    if (mobile.length > 0) setStep("otp");
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
            <h2 className="text-2xl font-bold text-stone-800 md:text-4xl">
              Welcome, Doctor
            </h2>
            <p className="text-stone-500 mt-2 text-sm md:text-lg">
              Log in to manage your consultations.
            </p>
            <span className="inline-flex items-center gap-2 mt-2 bg-stone-100 text-stone-500 text-[10px] px-2 py-1 rounded md:text-xs md:px-3 md:py-1.5">
              <Zap size={12} className="text-stone-400" />
              DEMO MODE: Use any number
            </span>
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
                      onChange={(e) =>
                        setMobile(e.target.value.replace(/\D/g, "").slice(0, 10))
                      }
                      placeholder="98765 43210"
                      className="flex-1 py-3 bg-transparent outline-none font-medium text-stone-800 md:py-4 md:text-lg placeholder:text-stone-400"
                    />
                  </div>
                </div>

                {/* ✅ Bigger button only on md+ (mobile unchanged) */}
                <Button
                  onClick={handleSendOtp}
                  disabled={mobile.length < 10}
                  fullWidth
                  className="md:text-xl md:py-4 md:rounded-2xl bg-gradient-to-r from-[#3998de] to-[#3998de] hover:from-[#3998de] hover:to-[#3998de] focus:outline-none focus:ring-2 focus:ring-[#3998de]/30"
                >
                  Send OTP
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
                    onChange={(e) => setOtp(e.target.value.slice(0, 4))}
                    placeholder="Any 4 digits"
                    className="w-full py-3 px-4 text-center text-2xl tracking-widest border border-stone-200 rounded-xl bg-stone-50 focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] md:py-4 md:text-3xl md:rounded-2xl placeholder:text-stone-400"
                  />
                  <p className="text-xs text-center text-stone-400 mt-2 md:text-sm">
                    Sent to +91 {mobile}
                  </p>
                </div>

                <Button
                  onClick={onLogin}
                  disabled={otp.length < 4}
                  fullWidth
                  className="md:text-xl md:py-4 md:rounded-2xl bg-gradient-to-r from-[#3998de] to-[#3998de] hover:from-[#3998de] hover:to-[#3998de] focus:outline-none focus:ring-2 focus:ring-[#3998de]/30"
                >
                  Verify & Login
                </Button>

                <button
                  onClick={() => setStep("mobile")}
                  className="w-full text-xs text-[#3998de] font-medium py-2 md:text-sm hover:underline"
                >
                  Change Number
                </button>
              </>
            )}
          </div>

          <div className="mt-8 text-center md:mt-10">
            <p className="text-stone-500 text-sm md:text-base">New to Snoutiq?</p>
            <button
              onClick={onRegisterClick}
              className="text-[#3998de] font-bold text-sm hover:underline mt-1 md:text-lg"
            >
              Register as a Partner
            </button>
          </div>
        </div>
      </PageWrap>
    </div>
  );
};

// --- 2. Vet Registration Screen ---

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
    doctorImageData: "",
  });
  const [specializations, setSpecializations] = useState([]);
  const [specializationOther, setSpecializationOther] = useState("");
  const [breakInput, setBreakInput] = useState("");
  const [breakTimes, setBreakTimes] = useState([]);
  const [dayPrice, setDayPrice] = useState("");
  const [nightPrice, setNightPrice] = useState("");
  const [agreement1, setAgreement1] = useState(false);
  const [agreement2, setAgreement2] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [doctorImagePreview, setDoctorImagePreview] = useState("");
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [successPayload, setSuccessPayload] = useState(null);
  const [showErrors, setShowErrors] = useState(false);

  const agreed = agreement1 && agreement2;

  const updateForm = (key) => (e) =>
    setForm((prev) => ({ ...prev, [key]: e.target.value }));

  const specializationOptions = [
    "Dogs",
    "Cats",
    "Exotic Pet",
    "Livestock",
    "Surgery",
    "Skin / Dermatology",
    "General Practice",
    "Other",
  ];

  const degreeOptions = ["BVSc", "MVSc", "PHD", "Other"];

  const responseTimeDayOptions = [
    "0 to 15 mins",
    "15 to 20 mins",
    "20 to 30 mins",
  ];

  const responseTimeNightOptions = [
    "0 to 15 mins",
    "15 to 20 mins",
    "20 to 30 mins",
  ];

  const followUpOptions = [
    { value: "yes", label: "Yes - free follow-up within 3 days" },
    { value: "no", label: "No - follow-ups are paid consultations" },
  ];

  const payoutOptions = [
    { value: "upi", label: "UPI (recommended)" },
    { value: "other", label: "Other" },
  ];

  const calculateCommission = (priceStr) => {
    const price = parseFloat(priceStr);
    if (isNaN(price) || price === 0) return null;
    const commission = Math.max(price * 0.25, 99);
    const earning = price - commission;
    return { commission: Math.ceil(commission), earning: Math.floor(earning) };
  };

  const dayMath = calculateCommission(dayPrice);
  const nightMath = calculateCommission(nightPrice);

  const inputBase =
    "w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#3998de]/30 focus:border-[#3998de] placeholder:text-stone-400";

  const toggleSpecialization = (value) => {
    setSpecializations((prev) =>
      prev.includes(value)
        ? prev.filter((item) => item !== value)
        : [...prev, value]
    );
  };

  const addBreakTime = () => {
    const trimmed = breakInput.trim();
    if (!trimmed || breakTimes.includes(trimmed)) return;
    setBreakTimes((prev) => [...prev, trimmed]);
    setBreakInput("");
  };

  const removeBreakTime = (value) => {
    setBreakTimes((prev) => prev.filter((item) => item !== value));
  };

  const handleDoctorImageFile = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
      const result = typeof reader.result === "string" ? reader.result : "";
      setDoctorImagePreview(result);
      setForm((prev) => ({
        ...prev,
        doctorImageData: result,
      }));
    };
    reader.readAsDataURL(file);
  };

  const normalizedDegree =
    form.degree === "Other" ? form.degreeOther.trim() : form.degree;

  const selectedSpecs = specializations.filter((spec) => spec !== "Other");
  if (specializations.includes("Other") && specializationOther.trim()) {
    selectedSpecs.push(specializationOther.trim());
  }

  const payoutReady = form.payoutDetail.trim();
  const degreeReady = normalizedDegree.trim();
  const whatsappReady = form.whatsappNumber.trim().length >= 10;

  const canSubmit =
    agreed &&
    form.vetFullName.trim() &&
    form.clinicName.trim() &&
    whatsappReady &&
    form.email.trim() &&
    form.shortIntro.trim() &&
    form.vetCity.trim() &&
    form.doctorLicense.trim() &&
    form.doctorImageData &&
    degreeReady &&
    form.yearsOfExperience.trim() &&
    selectedSpecs.length > 0 &&
    form.responseTimeDay &&
    form.responseTimeNight &&
    breakTimes.length > 0 &&
    dayPrice &&
    nightPrice &&
    form.freeFollowUp &&
    payoutReady;

  const stripEmpty = (payload) =>
    Object.fromEntries(
      Object.entries(payload).filter(([, value]) => {
        if (Array.isArray(value)) return value.length > 0;
        return value !== undefined && value !== null && value !== "";
      })
    );

  const handleSubmit = async () => {
    if (submitting) return;
    if (!canSubmit) {
      setShowErrors(true);
      return;
    }

    setSubmitting(true);
    setSubmitError("");
    setShowErrors(false);

    const payload = stripEmpty({
      vet_name: form.clinicName.trim(),
      vet_email: form.email.trim(),
      vet_mobile: form.whatsappNumber.trim(),
      vet_city: form.vetCity.trim(),
      doctor_name: form.vetFullName.trim(),
      doctor_email: form.email.trim(),
      doctor_mobile: form.whatsappNumber.trim(),
      doctor_license: form.doctorLicense.trim(),
      doctor_image: form.doctorImageData,
      degree: degreeReady,
      years_of_experience: form.yearsOfExperience.trim(),
      specialization_select_all_that_apply: selectedSpecs,
      response_time_for_online_consults_day: form.responseTimeDay,
      response_time_for_online_consults_night: form.responseTimeNight,
      break_do_not_disturb_time_example_2_4_pm: breakTimes,
      do_you_offer_a_free_follow_up_within_3_days_after_a_consulta:
        form.freeFollowUp === "yes" ? "Yes" : "No",
      commission_and_agreement: agreed ? "Agreed" : "Not agreed",
      video_day_rate: dayPrice ? Number(dayPrice) : undefined,
      video_night_rate: nightPrice ? Number(nightPrice) : undefined,
      short_intro: form.shortIntro.trim(),
      preferred_payout_method: form.payoutMethod,
      preferred_payout_detail: payoutReady,
    });

    try {
      await apiPost("/api/excell-export/import", payload);
      setSuccessPayload(payload);
      setShowSuccessModal(true);
    } catch (error) {
      setSubmitError(error?.message || "Failed to submit application.");
    } finally {
      setSubmitting(false);
    }
  };

  const handleSuccessClose = () => {
    setShowSuccessModal(false);
    if (successPayload) {
      onSubmit?.(successPayload);
      setSuccessPayload(null);
    }
  };

  const imagePreview = doctorImagePreview;
  const PricingSection = () => (
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
            Video Consultation Price (Day time) (Rs.)
          </label>
          <input
            type="number"
            value={dayPrice}
            onChange={(e) => setDayPrice(e.target.value)}
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
            Video Consultation Price (Night time) (Rs.)
          </label>
          <input
            type="number"
            value={nightPrice}
            onChange={(e) => setNightPrice(e.target.value)}
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
          Do you offer a free follow-up within 3 days after a consultation?
        </div>
        <div className="space-y-2">
          {followUpOptions.map((option) => (
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
          Preferred Payout Method (UPI number to receive payment)
        </div>
        <div className="space-y-2">
          {payoutOptions.map((option) => (
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
            form.payoutMethod === "upi" ? "yourname@upi" : "Describe payout method"
          }
          required
          className={inputBase}
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
            I understand Snoutiq charges 25% or Rs. 99 per consultation
            (whichever is higher).
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
            I understand earnings will be settled weekly to my registered payout
            method.
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

      <p className="text-xs text-stone-400 flex items-center gap-1">
        <Lock size={12} /> Patient contact details stay private.
      </p>
    </section>
  );

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <VetHeader onBack={onBack} title="Partner Registration" />

      <PageWrap>
        <div className="flex-1 px-4 py-6 pb-32 overflow-y-auto no-scrollbar md:px-0 md:py-12">
          <p className="text-sm text-stone-500 mb-6 px-2 md:px-0 md:text-lg">
            Join India&apos;s most trusted network of empathetic veterinarians.
          </p>
          <p className="text-xs text-stone-400 mb-6 px-2 md:px-0 md:text-sm">
            All fields are required.
          </p>

          <div className="space-y-6 md:space-y-0 md:grid md:grid-cols-12 md:gap-10 lg:gap-12">
            {/* LEFT */}
            <div className="md:col-span-7 lg:col-span-8 space-y-6">
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
                        {imagePreview ? (
                          <img
                            src={imagePreview}
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
                      <h3 className="text-sm font-bold text-stone-800">
                        Profile Photo *
                      </h3>
                      <p className="text-xs text-stone-500">
                        Clear headshots look best.
                      </p>
                    </div>
                    <div className="flex items-center justify-center md:justify-start gap-2">
                      <label
                        htmlFor="doctorImageUpload"
                        className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-stone-200 shadow-sm text-xs text-stone-600 cursor-pointer hover:bg-stone-50 transition-colors"
                      >
                        Choose photo
                      </label>
                      {imagePreview ? (
                        <button
                          type="button"
                          onClick={() => {
                            setDoctorImagePreview("");
                            setForm((prev) => ({
                              ...prev,
                              doctorImageData: "",
                            }));
                          }}
                          className="text-xs text-stone-500 hover:text-stone-700"
                        >
                          Remove
                        </button>
                      ) : null}
                    </div>
                    {imagePreview ? (
                      <p className="text-xs text-stone-400">
                        Tap the circle to update your photo.
                      </p>
                    ) : (
                      <p className="text-xs text-stone-400">
                        JPG/PNG recommended. Square photos look best.
                      </p>
                    )}
                  </div>
                </div>
              </section>

              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl md:shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-[#3998de]/10 text-[#3998de] w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    1
                  </span>
                  Basic Details
                </h3>

                <input
                  type="text"
                  placeholder="Vet Full Name"
                  value={form.vetFullName}
                  onChange={updateForm("vetFullName")}
                  required
                  className={inputBase}
                />
                <input
                  type="text"
                  placeholder="Clinic Name"
                  value={form.clinicName}
                  onChange={updateForm("clinicName")}
                  required
                  className={inputBase}
                />
                <textarea
                  placeholder="Short Intro"
                  value={form.shortIntro}
                  onChange={updateForm("shortIntro")}
                  rows={3}
                  required
                  className={`${inputBase} resize-none`}
                />

                <div className="grid grid-cols-2 gap-3">
                  <input
                    type="text"
                    placeholder="City"
                    value={form.vetCity}
                    onChange={updateForm("vetCity")}
                    required
                    className={inputBase}
                  />
                  <input
                    type="tel"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    placeholder="WhatsApp Number (Active)"
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
                  <Lock size={10} /> Your number is kept private and never shared
                  directly with pet parents.
                </p>

                <input
                  type="email"
                  placeholder="Email Address"
                  value={form.email}
                  onChange={updateForm("email")}
                  required
                  className={inputBase}
                />
              </section>

              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl md:shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-[#3998de]/10 text-[#3998de] w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    2
                  </span>
                  Professional Details
                </h3>

                <input
                  type="text"
                  placeholder="Vet Registration Number (Required)"
                  value={form.doctorLicense}
                  onChange={updateForm("doctorLicense")}
                  required
                  className={inputBase}
                />

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Degree
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {degreeOptions.map((degree) => (
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
                      placeholder="Specify degree"
                      value={form.degreeOther}
                      onChange={updateForm("degreeOther")}
                      required
                      className={inputBase}
                    />
                  ) : null}
                </div>

                <input
                  type="number"
                  placeholder="Years of Experience"
                  value={form.yearsOfExperience}
                  onChange={updateForm("yearsOfExperience")}
                  required
                  className={inputBase}
                />

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Specialization (Select all that apply)
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {specializationOptions.map((spec) => (
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
                      placeholder="Other specialization"
                      value={specializationOther}
                      onChange={(e) => setSpecializationOther(e.target.value)}
                      required
                      className={inputBase}
                    />
                  ) : null}
                </div>

              </section>

              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl md:shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-[#3998de]/10 text-[#3998de] w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    3
                  </span>
                  Availability & Timing
                </h3>

                <div className="space-y-2">
                  <label className="block text-xs font-bold text-stone-400 md:text-sm">
                    Response Time for Online Consults (Day)
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {responseTimeDayOptions.map((option) => (
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
                    Response Time for Online Consults (Night)
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {responseTimeNightOptions.map((option) => (
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
                    Break / do-not-disturb time (example: 2-4 PM)
                  </label>
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={breakInput}
                      onChange={(e) => setBreakInput(e.target.value)}
                      placeholder="2-4 PM"
                      className={inputBase}
                    />
                    <Button
                      type="button"
                      onClick={addBreakTime}
                      className="px-4"
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

            <div className="hidden md:block md:col-span-5 lg:col-span-4">
              <div className="md:sticky md:top-28 space-y-4">
                <PricingSection />
              </div>
            </div>

            <div className="md:hidden space-y-6">
              <PricingSection />
            </div>
          </div>

          <div className="h-24 md:hidden" />
        </div>
      </PageWrap>

      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button
          onClick={handleSubmit}
          fullWidth
          disabled={!canSubmit || submitting}
          className={!canSubmit || submitting ? "opacity-50" : ""}
        >
          {submitting ? "Submitting..." : "Submit Application"}
        </Button>
      </div>
      {showSuccessModal ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="bg-white rounded-2xl p-6 w-full max-w-sm text-center shadow-xl">
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
              <CheckCircle2 size={28} />
            </div>
            <div className="text-lg font-bold text-stone-800">
              Application submitted
            </div>
            <p className="text-sm text-stone-500 mt-2">
              We will review your application and activate your profile within
              24-48 hours.
            </p>
            <Button onClick={handleSuccessClose} fullWidth className="mt-4">
              Continue
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
// --- 3. Pending Approval Screen ---

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
            Thanks, Doctor. Our team will verify your credentials and activate
            your profile within 24-48 hours.
          </p>

          <div className="w-full max-w-xs mx-auto space-y-3 md:max-w-sm">
            <Button
              onClick={onHome}
              variant="secondary"
              fullWidth
              className="md:text-lg md:py-4 md:rounded-2xl"
            >
              Back to Home
            </Button>
          </div>
        </div>
      </PageWrap>
    </div>
  );
};

// --- 4. Vet Dashboard Screen (Updated Theme Like Screenshot) ---

export const VetDashboardScreen = ({ onLogout }) => {
  const [isAvailable, setIsAvailable] = useState(true);

  const history = [
    { id: 1, petParent: "Rahul M.", pet: "Rocky (Dog)", time: "Today, 10:30 AM", fee: "₹399" },
    { id: 2, petParent: "Priya S.", pet: "Luna (Cat)", time: "Yesterday, 8:15 PM", fee: "₹599" },
    { id: 3, petParent: "Amit K.", pet: "Coco (Dog)", time: "21 Oct, 4:00 PM", fee: "₹399" },
  ];

  // Theme tokens (same everywhere)
  const headerBg = "bg-[#0B4D67]";
  const headerBg2 = "bg-[#0A425A]";
  const pageBg = "bg-[#F6F7FB]";
  const card =
    "bg-white border border-stone-100 shadow-[0_8px_30px_rgba(0,0,0,0.06)]";
  const cardSoft = "bg-white border border-stone-100 shadow-sm";

  return (
    <div className={`min-h-screen ${pageBg} flex flex-col animate-slide-up`}>
      <PageWrap>
        {/* Header */}
        <div
          className={`
            ${headerBg} text-white pt-8 pb-12 px-6 relative
            md:px-10 md:pt-12 md:pb-16 lg:px-16
            md:rounded-none rounded-b-[2rem]
            shadow-[0_18px_60px_rgba(11,77,103,0.35)]
          `}
        >
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center gap-3">
              <div
                className={`
                  w-12 h-12 rounded-full
                  bg-white/15 border border-white/20
                  flex items-center justify-center text-xl font-bold
                  md:w-16 md:h-16 md:text-2xl
                `}
              >
                DR
              </div>

              <div>
                <h1 className="font-bold text-lg md:text-2xl">Dr. Rajesh</h1>
                <div className="flex items-center gap-1 text-white/75 text-xs md:text-sm">
                  <MapPin size={12} />
                  Delhi, India
                </div>
              </div>
            </div>

            <button
              onClick={onLogout}
              className="text-xs text-white/70 hover:text-white font-semibold md:text-sm focus:outline-none focus:ring-2 focus:ring-white/30 rounded-lg px-2 py-1"
            >
              Logout
            </button>
          </div>

          {/* Availability Bar */}
          <div
            className={`
              ${headerBg2}
              border border-white/15 rounded-2xl
              px-5 py-4 flex items-center justify-between
              md:px-7 md:py-6
              shadow-[0_10px_30px_rgba(0,0,0,0.12)]
            `}
          >
            <div className="flex items-center gap-3">
              <div
                className={`w-2.5 h-2.5 rounded-full ${
                  isAvailable ? "bg-emerald-400 animate-pulse" : "bg-white/40"
                }`}
              />
              <span className="font-semibold text-sm md:text-lg">
                {isAvailable ? "You are Online" : "You are Offline"}
              </span>
            </div>

            <button
              onClick={() => setIsAvailable(!isAvailable)}
              className={`
                px-4 py-2 rounded-full text-xs font-bold transition-all
                md:px-7 md:py-3 md:text-sm focus:outline-none focus:ring-2 focus:ring-white/30
                ${
                  isAvailable
                    ? "bg-white text-[#0B4D67] hover:bg-white/90"
                    : "bg-white/10 text-white hover:bg-white/15 border border-white/20"
                }
              `}
            >
              {isAvailable ? "Go Offline" : "Go Online"}
            </button>
          </div>
        </div>

        {/* Stats Row */}
        <div className="px-4 mt-6 mb-8 grid grid-cols-2 gap-3 md:px-0 md:grid-cols-4 md:gap-5">
          <div className={`${card} p-4 rounded-2xl md:col-span-2 md:p-7`}>
            <p className="text-stone-400 text-xs uppercase font-bold mb-1 md:text-sm">
              Today&apos;s Earnings
            </p>
            <p className="text-2xl font-bold text-stone-900 md:text-4xl">₹1,240</p>
            <p className="text-[10px] text-emerald-600 flex items-center gap-1 mt-1 md:text-sm">
              <TrendingUp size={12} /> +12% vs yest
            </p>
          </div>

          <div className={`${card} p-4 rounded-2xl md:col-span-2 md:p-7`}>
            <p className="text-stone-400 text-xs uppercase font-bold mb-1 md:text-sm">
              Total Consults
            </p>
            <p className="text-2xl font-bold text-stone-900 md:text-4xl">42</p>
            <p className="text-[10px] text-stone-400 mt-1 md:text-sm">Lifetime</p>
          </div>
        </div>

        {/* Content */}
        <div className="flex-1 px-4 pb-20 overflow-y-auto no-scrollbar space-y-6 md:px-0 md:pb-24 md:grid md:grid-cols-12 md:gap-10 md:space-y-0">
          {/* LEFT */}
          <div className="md:col-span-7 lg:col-span-8 space-y-6">
            {/* Performance */}
            <section>
              <h3 className="font-bold text-stone-800 mb-3 text-sm md:text-lg">
                Performance
              </h3>

              <div className={`${cardSoft} rounded-2xl p-2 md:rounded-3xl`}>
                <div className="grid grid-cols-3 divide-x divide-stone-100">
                  <div className="p-4 text-center md:p-7">
                    <p className="text-stone-400 text-[10px] uppercase font-bold mb-1 md:text-xs">
                      Avg Rating
                    </p>
                    <div className="flex items-center justify-center gap-1 font-bold text-stone-900 md:text-xl">
                      4.9{" "}
                      <Star size={16} className="text-amber-400 fill-current" />
                    </div>
                  </div>

                  <div className="p-4 text-center md:p-7">
                    <p className="text-stone-400 text-[10px] uppercase font-bold mb-1 md:text-xs">
                      Response
                    </p>
                    <div className="font-bold text-stone-900 md:text-xl">8m</div>
                  </div>

                  <div className="p-4 text-center md:p-7">
                    <p className="text-stone-400 text-[10px] uppercase font-bold mb-1 md:text-xs">
                      Return Rate
                    </p>
                    <div className="font-bold text-stone-900 md:text-xl">24%</div>
                  </div>
                </div>
              </div>
            </section>

            {/* Recent Consultations */}
            <section>
              <h3 className="font-bold text-stone-800 mb-3 text-sm flex items-center gap-2 md:text-lg">
                <History size={16} />
                Recent Consultations
              </h3>

              <div className={`${cardSoft} rounded-2xl overflow-hidden md:rounded-3xl`}>
                {history.map((item, idx) => (
                  <div
                    key={item.id}
                    className={`px-4 py-4 flex justify-between items-center md:px-7 md:py-6 hover:bg-stone-50/60 transition-colors ${
                      idx !== history.length - 1 ? "border-b border-stone-100" : ""
                    }`}
                  >
                    <div>
                      <p className="font-bold text-stone-900 text-sm md:text-lg">
                        {item.petParent}
                      </p>
                      <p className="text-xs text-stone-500 md:text-base">{item.pet}</p>
                    </div>
                    <div className="text-right">
                      <p className="font-bold text-stone-900 text-sm md:text-lg">
                        {item.fee}
                      </p>
                      <p className="text-[10px] text-stone-400 md:text-sm">
                        {item.time}
                      </p>
                    </div>
                  </div>
                ))}
              </div>

              <p className="text-[10px] text-stone-400 mt-2 flex items-center gap-1 md:text-xs">
                <Lock size={12} />
                Patient contact details are hidden for privacy.
              </p>
            </section>
          </div>

          {/* RIGHT */}
          <div className="md:col-span-5 lg:col-span-4 space-y-6">
            <section>
              <h3 className="font-bold text-stone-800 mb-3 text-sm md:text-lg">
                Recent Feedback
              </h3>

              <div className="space-y-3">
                {[1, 2].map((i) => (
                  <div
                    key={i}
                    className={`${cardSoft} p-4 rounded-2xl md:p-7 md:rounded-3xl hover:shadow-md transition-shadow`}
                  >
                    <div className="flex justify-between items-start mb-2">
                      <div className="flex gap-1">
                        {[1, 2, 3, 4, 5].map((s) => (
                          <Star
                            key={s}
                            size={12}
                            className="text-amber-400 fill-current"
                          />
                        ))}
                      </div>
                      <span className="text-[10px] text-stone-400 md:text-sm">
                        2h ago
                      </span>
                    </div>

                    <p className="text-xs text-stone-600 italic md:text-base leading-relaxed">
                      "Doctor was very calm and explained everything clearly about my dog's
                      diet. Highly recommended!"
                    </p>

                    <p className="text-[10px] text-stone-400 font-bold mt-2 md:text-sm">
                      - Sneha P.
                    </p>
                  </div>
                ))}
              </div>
            </section>

            {/* Pro Tip */}
            <div className="bg-[#EAF3FF] p-4 rounded-2xl border border-[#CFE2FF] flex gap-3 md:p-7 md:rounded-3xl">
              <AlertCircle className="text-[#2563EB] flex-shrink-0" size={22} />
              <div className="text-xs text-[#1E3A8A] md:text-base">
                <strong>Pro Tip:</strong> Updating your availability accurately helps you
                get 3x more consultations.
              </div>
            </div>
          </div>
        </div>
      </PageWrap>
    </div>
  );
};
