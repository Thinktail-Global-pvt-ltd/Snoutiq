import React, { useState } from "react";
import { Phone, X, Loader2, ShieldCheck } from "lucide-react";
import { updateAiUserData } from "./AiAuth";

const API_BASE = "https://snoutiq.com/backend/api";

const toApiPhone = (v) => `91${String(v).replace(/\D/g, "").slice(-10)}`;

export default function PhoneVerifyGate({ onVerified, onClose }) {
  const [phone, setPhone] = useState("");
  const [token, setToken] = useState(null);
  const [otp, setOtp] = useState("");
  const [sent, setSent] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const sendOtp = async () => {
    setError("");
    const cleanDigits = phone.replace(/\D/g, "").slice(-10);
    if (!/^\d{10}$/.test(cleanDigits)) {
      setError("Please enter a valid 10-digit mobile number.");
      return;
    }
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/send-otp`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ value: toApiPhone(cleanDigits), role: "pet" }),
      });
      const data = await res.json();
      if (!res.ok || data?.success === false) {
        throw new Error(data?.message || "Failed to send OTP. Please try again.");
      }
      if (!data.token) {
        throw new Error("Token not received from server.");
      }
      setToken(data.token);
      setSent(true);
    } catch (e) {
      setError(e.message || "Failed to send OTP.");
    } finally {
      setLoading(false);
    }
  };

  const verifyOtp = async () => {
    setError("");
    const cleanDigits = phone.replace(/\D/g, "").slice(-10);
    if (otp.trim().length !== 4) {
      setError("Please enter the 4-digit OTP.");
      return;
    }
    setLoading(true);
    try {
      const referralClinicId =
        localStorage.getItem("referral_clinic_id") ||
        sessionStorage.getItem("referral_clinic_id");

      const verifyPayload = {
        token,
        otp: otp.trim(),
        phone: toApiPhone(cleanDigits),
        role: "pet",
        ...(referralClinicId ? { referral_clinic_id: Number(referralClinicId) || referralClinicId } : {}),
      };

      const res = await fetch(`${API_BASE}/verify-otp`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(verifyPayload),
      });
      const data = await res.json();
      if (!res.ok || data?.success === false) {
        throw new Error(data?.message || "Invalid OTP. Please try again.");
      }

      const verifiedPhone = data.user?.phone || toApiPhone(cleanDigits);
      const cleanPhone = cleanDigits;

      const userData = data.user || data.data?.user || {};
      const rawUserId =
        data.user_id ||
        data.data?.user_id ||
        userData?.user_id ||
        userData?.id ||
        null;
      const authToken =
        data.token ||
        data.access_token ||
        data.jwt ||
        data.data?.token ||
        null;
      const latestChat = data.latest_chat || data.data?.latest_chat || null;
      const latestCallSession = data.latest_call_session || data.data?.latest_call_session || null;

      // Extract pets from response if present
      let rawPets = Array.isArray(data.pets)
        ? data.pets
        : (Array.isArray(data.data?.pets)
          ? data.data.pets
          : (Array.isArray(userData?.pets)
            ? userData.pets
            : []));

      // If user ID exists and pets array is empty, fetch full pets list from backend
      if (rawUserId && rawPets.length === 0) {
        try {
          const petsRes = await fetch(`${API_BASE}/users/${encodeURIComponent(rawUserId)}/pets`, {
            headers: authToken ? { Authorization: `Bearer ${authToken}` } : {},
          });
          if (petsRes.ok) {
            const pData = await petsRes.json();
            const fetchedPets = Array.isArray(pData?.data)
              ? pData.data
              : (Array.isArray(pData?.pets)
                ? pData.pets
                : (Array.isArray(pData)
                  ? pData
                  : []));
            if (fetchedPets && fetchedPets.length > 0) {
              rawPets = fetchedPets;
            }
          }
        } catch (err) {
          console.warn("Could not fetch additional pets for user:", err);
        }
      }

      // Helper to normalize pet object
      const normalizePet = (p) => ({
        id: p.id || p.pet_id,
        pet_id: p.pet_id || p.id,
        name: p.name || p.pet_name || "",
        pet_name: p.pet_name || p.name || "",
        breed: p.breed || p.pet_breed || "",
        pet_breed: p.breed || p.pet_breed || "",
        pet_gender: p.pet_gender || p.gender || "",
        gender: p.gender || p.pet_gender || "",
        pet_age: p.pet_age ?? p.age ?? "",
        pet_type: p.pet_type || p.species || p.type || "Dog",
        species: p.species || p.pet_type || p.type || "Dog",
        pet_dob: p.pet_dob || p.dob || "",
        pet_doc1: p.pet_doc1 || p.image || p.pet_image_url || p.profile_image || "",
        pet_image_url: p.pet_image_url || p.image_url || p.image || p.pet_doc1 || "",
        avatar: p.avatar || p.pet_image_url || p.image || p.pet_doc1 || "",
        weight: p.weight || p.pet_weight || "",
        is_neutered: p.is_neutered || p.is_nuetered || false,
      });

      const normalizedPets = rawPets.filter(Boolean).map(normalizePet);
      const primaryPet = normalizedPets.length > 0 ? normalizedPets[0] : null;

      // Section A: Direct Storage Keys
      try {
        localStorage.setItem("phone_number", verifiedPhone);
        localStorage.setItem("otp_verified", "true");
        localStorage.setItem("user_identifier", `user_${verifiedPhone}`);
        if (rawUserId) {
          localStorage.setItem("current_user_id", String(rawUserId));
          localStorage.setItem("user_id", String(rawUserId));
        }
        if (authToken) {
          localStorage.setItem("auth_token", authToken);
        }
        if (latestChat) {
          localStorage.setItem("latest_chat", JSON.stringify(latestChat));
          if (latestChat.question) {
            localStorage.setItem("symptom_description", latestChat.question);
          }
          if (latestChat.chat_room_token) {
            localStorage.setItem("chat_room_token", latestChat.chat_room_token);
          }
        }
        if (latestCallSession) {
          localStorage.setItem("latest_call_session", JSON.stringify(latestCallSession));
        }
        if (primaryPet) {
          localStorage.setItem("selected_pet_data", JSON.stringify(primaryPet));
          localStorage.setItem("current_pet", JSON.stringify(primaryPet));
          localStorage.setItem("pet_name", primaryPet.name || primaryPet.pet_name || "");
          localStorage.setItem("pet_breed", primaryPet.breed || "");
        }
        localStorage.setItem("user_mobile", cleanPhone);
        if (userData?.name || userData?.owner_name) {
          localStorage.setItem("user_name", userData.name || userData.owner_name);
        }
      } catch (e) {
        console.warn("Storage write error:", e);
      }

      // Section B: Global Auth State Data (finalUserData)
      const userPatch = {
        ...(userData || {}),
        phone: verifiedPhone,
        mobileNumber: verifiedPhone,
        mobile: cleanPhone,
        ...(rawUserId ? { id: rawUserId, user_id: rawUserId } : {}),
        ...(normalizedPets.length > 0 ? {
          pets: normalizedPets,
          ...(primaryPet ? {
            pet: primaryPet,
            pet_id: primaryPet.id,
            pet_name: primaryPet.name,
            pet_gender: primaryPet.pet_gender,
            breed: primaryPet.breed,
            pet_age: primaryPet.pet_age,
            pet_type: primaryPet.pet_type,
            pet_doc1: primaryPet.pet_doc1,
            pet_image_url: primaryPet.pet_image_url,
          } : {}),
        } : {}),
        latest_chat: latestChat,
        latest_call_session: latestCallSession,
        ...(latestChat?.chat_room_token ? { chat_room_token: latestChat.chat_room_token } : {}),
      };

      const nextState = updateAiUserData(userPatch, {
        token: authToken,
        latestChat,
        latestCallSession,
      });

      if (typeof onVerified === "function") {
        onVerified(cleanPhone, nextState, normalizedPets);
      }
    } catch (e) {
      setError(e.message || "Invalid OTP. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[400] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-[fadeIn_0.15s_ease-out]">
      <div className="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl relative border border-slate-100 animate-[scaleInUp_0.2s_ease-out]">
        <button
          onClick={onClose}
          className="absolute top-3.5 right-3.5 p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors cursor-pointer"
          aria-label="Close"
        >
          <X size={18} />
        </button>

        <div className="flex items-center gap-2 mb-2">
          <div className="w-8 h-8 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-[#309BD8] shrink-0">
            <Phone size={16} />
          </div>
          <div>
            <h3 className="text-sm font-bold text-slate-900 leading-tight">WhatsApp Mobile Number</h3>
            <p className="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
              <ShieldCheck size={11} className="text-emerald-500" />
              <span>100% Secure & Private</span>
            </p>
          </div>
        </div>

        <p className="text-xs text-slate-500 mb-3.5 leading-relaxed">
          Please enter your WhatsApp mobile number. OTP and booking confirmation will be sent directly to your WhatsApp.
        </p>

        {!sent ? (
          <div className="space-y-2.5">
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500">
                +91
              </div>
              <input
                type="tel"
                maxLength={10}
                className="w-full border border-slate-200 focus:border-[#309BD8] focus:ring-2 focus:ring-blue-100 rounded-xl pl-12 pr-3 py-2.5 text-sm font-semibold outline-none transition-all"
                placeholder="10-digit WhatsApp number"
                value={phone}
                onChange={(e) => setPhone(e.target.value.replace(/\D/g, "").slice(0, 10))}
                onKeyDown={(e) => e.key === "Enter" && sendOtp()}
                autoFocus
              />
            </div>
            <p className="text-[11px] text-slate-500 flex items-center gap-1 px-1">
              <span>💬</span>
              <span>OTP will arrive on WhatsApp</span>
            </p>
            <button
              type="button"
              onClick={sendOtp}
              disabled={loading || phone.replace(/\D/g, "").length !== 10}
              className="w-full py-2.5 bg-[#309BD8] hover:bg-[#2887bc] text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:shadow-none transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            >
              {loading ? (
                <>
                  <Loader2 size={14} className="animate-spin" />
                  <span>Sending OTP...</span>
                </>
              ) : (
                <span>Send WhatsApp OTP →</span>
              )}
            </button>
          </div>
        ) : (
          <div className="space-y-2.5">
            <div className="flex items-center justify-between text-[11px] text-slate-500 px-0.5">
              <span>WhatsApp OTP sent to <strong>+91 {phone.replace(/\D/g, "").slice(-10)}</strong></span>
              <button
                type="button"
                onClick={() => { setSent(false); setOtp(""); setError(""); }}
                className="text-[#309BD8] font-bold hover:underline cursor-pointer"
              >
                Change
              </button>
            </div>
            <input
              type="tel"
              maxLength={4}
              className="w-full border border-slate-200 focus:border-[#309BD8] focus:ring-2 focus:ring-blue-100 rounded-xl px-3 py-2.5 text-center text-lg font-extrabold tracking-widest outline-none transition-all"
              placeholder="••••"
              value={otp}
              onChange={(e) => setOtp(e.target.value.replace(/\D/g, "").slice(0, 4))}
              onKeyDown={(e) => e.key === "Enter" && otp.length === 4 && verifyOtp()}
              autoFocus
            />
            <button
              type="button"
              onClick={verifyOtp}
              disabled={loading || otp.length !== 4}
              className="w-full py-2.5 bg-[#309BD8] hover:bg-[#2887bc] text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:shadow-none transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            >
              {loading ? (
                <>
                  <Loader2 size={14} className="animate-spin" />
                  <span>Verifying...</span>
                </>
              ) : (
                <span>Verify & Continue →</span>
              )}
            </button>
            <div className="text-center pt-1">
              <button
                type="button"
                onClick={sendOtp}
                disabled={loading}
                className="text-[11px] font-semibold text-slate-500 hover:text-[#309BD8] transition-colors cursor-pointer"
              >
                Didn't receive WhatsApp OTP? Resend
              </button>
            </div>
          </div>
        )}

        {error && (
          <div className="p-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-[11px] font-medium mt-2.5">
            {error}
          </div>
        )}
      </div>
    </div>
  );
}
