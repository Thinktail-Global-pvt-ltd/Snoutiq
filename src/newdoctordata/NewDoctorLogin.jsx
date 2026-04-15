import React, { useEffect, useState } from "react";
import { ArrowRight, Loader2 } from "lucide-react";
import { useNavigate } from "react-router-dom";
import logo from "../assets/images/logo.png";
import { useNewDoctorAuth } from "./NewDoctorAuth";

const REQUEST_OTP_URL = "https://snoutiq.com/backend/api/doctor/otp/request-any";
const VERIFY_OTP_URL = "https://snoutiq.com/backend/api/doctor/otp/verify-any";

export default function NewDoctorLogin() {
  const [step, setStep] = useState(1);
  const [phone, setPhone] = useState("");
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const navigate = useNavigate();
  const { auth, hydrated, mergeAuth } = useNewDoctorAuth();

  useEffect(() => {
    if (!hydrated) return;

    if (auth.onboarding_completed) {
      navigate("/counsltflow/dashboard", { replace: true });
      return;
    }

    if (auth.phone_verified && auth.phone_exists) {
      navigate("/counsltflow/dashboard", { replace: true });
      return;
    }

    if (auth.phone_verified && !auth.phone_exists) {
      navigate("/counsltflow/onboarding", { replace: true });
      return;
    }

    if (auth.phone) {
      setPhone(auth.phone);
    }

    if (auth.request_id) {
      setStep(2);
    }
  }, [auth, hydrated, navigate]);

  const handlePhoneChange = (e) => {
    const value = e.target.value.replace(/\D/g, "");
    if (value.length <= 10) setPhone(value);
  };

  const handleOtpChange = (e) => {
    const value = e.target.value.replace(/\D/g, "");
    if (value.length <= 6) setOtp(value);
  };

  const handleSendOtp = async () => {
    if (phone.length !== 10) return;

    setLoading(true);
    setError("");

    try {
      const response = await fetch(REQUEST_OTP_URL, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ phone }),
      });

      const data = await response.json();
      console.log("doctor otp request response:", data);

      if (!response.ok || !data?.success) {
        throw new Error(data?.message || "OTP send failed");
      }

      mergeAuth({
        phone,
        request_id: data.request_id || "",
        expires_in: data.expires_in || 0,
        otp_requested_at: Date.now(),
        phone_verified: false,
        phone_exists: Boolean(data.phone_exists),
      });

      setStep(2);
    } catch (err) {
      console.error("doctor otp request error:", err);
      setError(err.message || "Unable to send OTP");
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async () => {
    if (otp.length !== 6) return;
    if (!auth.request_id) {
      setError("Request ID missing. Please resend OTP.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      const response = await fetch(VERIFY_OTP_URL, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          phone,
          otp,
          request_id: auth.request_id,
        }),
      });

      const data = await response.json();
      console.log("doctor otp verify response:", data);

      if (!response.ok || !data?.success) {
        throw new Error(data?.message || "OTP verification failed");
      }

      mergeAuth({
        phone,
        phone_verified: Boolean(data.phone_verified),
        phone_exists: Boolean(data.phone_exists),
      });

      if (data.phone_exists) {
        navigate("/counsltflow/dashboard", { replace: true });
      } else {
        navigate("/counsltflow/onboarding", { replace: true });
      }
    } catch (err) {
      console.error("doctor otp verify error:", err);
      setError(err.message || "Unable to verify OTP");
    } finally {
      setLoading(false);
    }
  };

  const handleChangeNumber = () => {
    setStep(1);
    setOtp("");
    setError("");
    mergeAuth({
      phone: "",
      request_id: "",
      expires_in: 0,
      otp_requested_at: null,
      phone_verified: false,
      phone_exists: false,
    });
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <div className="absolute top-5 left-6 flex items-center gap-2">
        <img src={logo} alt="logo" className="h-5" />
      </div>

      <div className="flex flex-1 items-center justify-center px-4">
        <div className="w-full max-w-sm text-center">
          <h1 className="text-2xl font-bold text-gray-900">ConsultFlow</h1>
          <p className="text-gray-500 text-sm mt-1 mb-8">
            WhatsApp-first Vet Consultations
          </p>

          <div className="overflow-hidden">
            <div
              className={`flex transition-transform duration-500 ${
                step === 2 ? "-translate-x-full" : "translate-x-0"
              }`}
            >
              <div className="min-w-full space-y-5 text-left">
                <div className="w-full">
                  <label className="text-sm font-medium text-gray-700">
                    WhatsApp Number
                  </label>

                  <div className="flex items-center border rounded-xl mt-1 bg-white w-full box-border overflow-hidden">
                    <span className="px-3 text-gray-500 whitespace-nowrap">+91</span>
                    <input
                      type="tel"
                      value={phone}
                      onChange={handlePhoneChange}
                      placeholder="Enter 10 digit number"
                      className="flex-1 p-3 outline-none min-w-0"
                    />
                  </div>
                </div>

                {error ? (
                  <p className="text-sm text-red-500">{error}</p>
                ) : null}

                <button
                  disabled={phone.length !== 10 || loading}
                  onClick={handleSendOtp}
                  className={`w-full py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition ${
                    phone.length === 10 && !loading
                      ? "bg-whatsapp text-white"
                      : "bg-gray-300 text-gray-500"
                  }`}
                >
                  {loading ? <Loader2 size={18} className="animate-spin" /> : null}
                  Send OTP {!loading && <ArrowRight size={18} />}
                </button>
              </div>

              <div className="min-w-full space-y-5 text-left">
                <div>
                  <label className="text-sm font-medium text-gray-700">
                    Enter OTP
                  </label>

                  <input
                    type="tel"
                    value={otp}
                    onChange={handleOtpChange}
                    placeholder="Enter 6 digit OTP"
                    className="w-full p-3 mt-1 border rounded-xl text-center tracking-widest text-lg focus:border-whatsapp outline-none"
                  />
                </div>

                {error ? (
                  <p className="text-sm text-red-500">{error}</p>
                ) : null}

                <button
                  disabled={otp.length !== 6 || loading}
                  onClick={handleVerifyOtp}
                  className={`w-full py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition ${
                    otp.length === 6 && !loading
                      ? "bg-whatsapp text-white"
                      : "bg-gray-300 text-gray-500"
                  }`}
                >
                  {loading ? <Loader2 size={18} className="animate-spin" /> : null}
                  Verify & Login {!loading && <ArrowRight size={18} />}
                </button>

                <button
                  onClick={handleChangeNumber}
                  className="w-full text-gray-500 text-sm text-center"
                  type="button"
                >
                  Change Number
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}