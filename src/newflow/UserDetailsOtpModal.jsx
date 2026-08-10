import React, { useState, useRef, useEffect } from "react";
import { X, User, Phone, ShieldCheck, Info, Loader2 } from "lucide-react";
import { readAiAuthState, persistAiAuthState } from "../ai/AiAuth";

const API_BASE = "https://snoutiq.com/backend/api";

// ⚠️ NOTE: Ye endpoint names abhi assumption hain (backend team se confirm karo).
// Agar backend mein alag naam hai to sirf ye 2 URL change karne honge.
const SEND_OTP_URL = `${API_BASE}/send-otp`;
const VERIFY_OTP_URL = `${API_BASE}/google-merge-user`;

function normalizePhone(value) {
  return String(value || "").replace(/[^\d]/g, "").slice(-10);
}

export default function UserDetailsOtpModal({ onClose, onComplete }) {
  const authState = readAiAuthState();
  const token = authState?.token;
  const existingUser = authState?.user || {};

  const [step, setStep] = useState("form"); // "form" | "otp"
  const [name, setName] = useState(existingUser?.name || "");
  const [whatsappNumber, setWhatsappNumber] = useState("");
  const [otp, setOtp] = useState("");
  const [sendingOtp, setSendingOtp] = useState(false);
  const [verifying, setVerifying] = useState(false);
  const [error, setError] = useState("");
  const [resendTimer, setResendTimer] = useState(0);
  const otpInputRef = useRef(null);

  useEffect(() => {
    if (step === "otp") {
      otpInputRef.current?.focus();
    }
  }, [step]);

  useEffect(() => {
    if (resendTimer <= 0) return;
    const timer = setInterval(() => setResendTimer((t) => Math.max(0, t - 1)), 1000);
    return () => clearInterval(timer);
  }, [resendTimer]);

  useEffect(() => {
    const handleEsc = (e) => e.key === "Escape" && onClose?.();
    window.addEventListener("keydown", handleEsc);
    return () => window.removeEventListener("keydown", handleEsc);
  }, [onClose]);

  const isValidName = name.trim().length >= 2;
  const isValidPhone = normalizePhone(whatsappNumber).length === 10;

  const handleSendOtp = async () => {
    if (!isValidName || !isValidPhone) {
      setError("Please enter a valid name and 10-digit WhatsApp number.");
      return;
    }
    setError("");
    setSendingOtp(true);
    try {
      const res = await fetch(SEND_OTP_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify({
          value: normalizePhone(whatsappNumber),
          type: "whatsapp",
        }),
      });
      const data = await res.json();
      if (!res.ok || data?.success === false) {
        throw new Error(data?.message || "Could not send OTP. Please try again.");
      }
      setStep("otp");
      setResendTimer(30);
    } catch (err) {
      setError(err.message || "Could not send OTP. Please try again.");
    } finally {
      setSendingOtp(false);
    }
  };

  const handleResendOtp = async () => {
    if (resendTimer > 0) return;
    await handleSendOtp();
  };

  const handleVerifyOtp = async () => {
    if (otp.trim().length !== 4) return;
    setError("");
    setVerifying(true);
    try {
      const res = await fetch(VERIFY_OTP_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify({
          phone: normalizePhone(whatsappNumber),
          name: name.trim(),
          otp: otp.trim(),
          token: existingUser?.google_token || token,
          email: existingUser?.email || "",
        }),
      });
      const data = await res.json();
      console.log("📱 OTP Verify API Response:", data);
      if (!res.ok || data?.success === false) {
        throw new Error(data?.message || "Invalid OTP. Please try again.");
      }

      // Auth state update — naya name/number save karo taaki dobara na maange
      const userData = data.user || data.data?.user || {};
      const existingPet = existingUser?.pet || (Array.isArray(existingUser?.pets) && existingUser.pets.length > 0 ? existingUser.pets[0] : null);
      const returnedPetId = data.pet_id || data.pet?.id || data.data?.pet_id || data.data?.pet?.id || userData.pet_id || (userData.pets && userData.pets[0] && (userData.pets[0].id || userData.pets[0].pet_id));
      
      const apiPets = userData.pets || data.pets || data.data?.pets;
      const finalPets = (Array.isArray(apiPets) && apiPets.length > 0)
        ? apiPets
        : (existingUser?.pets?.length > 0 ? existingUser.pets : (existingPet ? [existingPet] : []));
      const finalPet = (Array.isArray(finalPets) && finalPets.length > 0)
        ? finalPets[0]
        : existingPet;

      console.log("🔑 [OTP Verification Response]:", data);
      console.log("🐶 [OTP Pet Preservation]:", { existingPet, apiPets, finalPet, finalPets, returnedPetId });

      const updatedUser = {
        ...existingUser,
        ...userData,
        ...(finalPet ? { pet: finalPet } : {}),
        ...(finalPets.length > 0 ? { pets: finalPets } : {}),
        name: name.trim(),
        owner_name: name.trim(),
        phone: normalizePhone(whatsappNumber),
        mobile: normalizePhone(whatsappNumber),
        whatsapp_number: normalizePhone(whatsappNumber),
        whatsapp_verified: true,
        pet_name: finalPet?.name || finalPet?.pet_name || existingUser?.pet_name || "",
        pet_id: returnedPetId || finalPet?.id || finalPet?.pet_id || existingUser?.pet_id || "",
      };

      const nextToken = data.token || data.jwt || data.access_token || data.data?.token || token;

      console.log("👤 [Saved User Data (OTP Success)]:", updatedUser);
      persistAiAuthState({
        user: updatedUser,
        token: nextToken,
      });

      onComplete?.(updatedUser);
    } catch (err) {
      setError(err.message || "Invalid OTP. Please try again.");
    } finally {
      setVerifying(false);
    }
  };

  const canVerify = otp.trim().length === 4 && !verifying;

  return (
    <div className="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div className="w-full max-w-md rounded-3xl bg-white shadow-2xl relative overflow-hidden">
        <button
          onClick={onClose}
          className="absolute top-4 right-4 z-10 p-2 bg-slate-100 hover:bg-slate-200 rounded-full text-slate-500 transition-colors"
        >
          <X size={18} />
        </button>

        <div className="p-7">
          <div className="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center mb-4">
            {step === "form" ? (
              <User className="text-emerald-600" size={22} />
            ) : (
              <ShieldCheck className="text-emerald-600" size={22} />
            )}
          </div>

          {step === "form" ? (
            <>
              <h2 className="text-xl font-bold text-slate-900 mb-1">Complete your details</h2>
              <p className="text-sm text-slate-500 mb-6">
                We need a few details before you can book a consultation.
              </p>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1.5">Full Name</label>
                  <input
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="Enter your full name"
                    className="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:border-black outline-none"
                  />
                </div>

                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1.5">WhatsApp Number</label>
                  <div className="flex items-center border border-slate-200 rounded-xl overflow-hidden focus-within:border-black">
                    <span className="px-3 py-3 text-sm text-slate-500 bg-slate-50 border-r border-slate-200">
                      +91
                    </span>
                    <input
                      type="tel"
                      value={whatsappNumber}
                      onChange={(e) => setWhatsappNumber(e.target.value.replace(/[^\d]/g, "").slice(0, 10))}
                      placeholder="10-digit WhatsApp number"
                      className="flex-1 px-3 py-3 text-sm outline-none"
                    />
                  </div>

                  {/* 👇 Yehi wo explanation text hai jo aapne maanga tha */}
                  <div className="mt-2.5 flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-xl p-3">
                    <Info size={14} className="text-blue-500 mt-0.5 shrink-0" />
                    <p className="text-xs text-blue-700 leading-relaxed">
                      We use your WhatsApp number to send appointment reminders, doctor's
                      prescriptions, and important consultation updates.
                    </p>
                  </div>
                </div>

                {error && (
                  <p className="text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                    {error}
                  </p>
                )}

                <button
                  onClick={handleSendOtp}
                  disabled={!isValidName || !isValidPhone || sendingOtp}
                  className="w-full py-3.5 bg-black text-white rounded-xl font-semibold text-sm disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 transition-colors flex items-center justify-center gap-2"
                >
                  {sendingOtp ? (
                    <>
                      <Loader2 size={16} className="animate-spin" /> Sending OTP...
                    </>
                  ) : (
                    "Send OTP"
                  )}
                </button>
              </div>
            </>
          ) : (
            <>
              <h2 className="text-xl font-bold text-slate-900 mb-1">Verify your number</h2>
              <p className="text-sm text-slate-500 mb-6">
                Enter the OTP sent to <span className="font-semibold text-slate-700">+91 {whatsappNumber}</span> on WhatsApp.
              </p>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1.5">Enter OTP</label>
                  <input
                    ref={otpInputRef}
                    type="text"
                    inputMode="numeric"
                    value={otp}
                    onChange={(e) => setOtp(e.target.value.replace(/[^\d]/g, "").slice(0, 4))}
                    placeholder="Enter 4-digit OTP"
                    className="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm tracking-[0.3em] text-center font-semibold focus:border-black outline-none"
                  />
                </div>

                {error && (
                  <p className="text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                    {error}
                  </p>
                )}

                {/* 👇 Submit button - OTP fill hone tak DISABLED rahega */}
                <button
                  onClick={handleVerifyOtp}
                  disabled={!canVerify}
                  className="w-full py-3.5 bg-black text-white rounded-xl font-semibold text-sm disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 transition-colors flex items-center justify-center gap-2"
                >
                  {verifying ? (
                    <>
                      <Loader2 size={16} className="animate-spin" /> Verifying...
                    </>
                  ) : (
                    "Verify & Continue"
                  )}
                </button>

                <div className="flex items-center justify-between text-xs">
                  <button
                    onClick={() => { setStep("form"); setOtp(""); setError(""); }}
                    className="text-slate-500 hover:text-slate-800 font-medium"
                  >
                    ← Change number
                  </button>
                  <button
                    onClick={handleResendOtp}
                    disabled={resendTimer > 0}
                    className="text-blue-600 hover:text-blue-800 font-medium disabled:text-slate-400 disabled:cursor-not-allowed"
                  >
                    {resendTimer > 0 ? `Resend in ${resendTimer}s` : "Resend OTP"}
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}