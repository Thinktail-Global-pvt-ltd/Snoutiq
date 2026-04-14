import { useState } from "react";
import { ArrowRight } from "lucide-react";
import { useNavigate } from "react-router-dom";
import logo from "../assets/images/logo.png";

export default function NewDoctorLogin() {
  const [step, setStep] = useState(1);
  const [phone, setPhone] = useState("");
  const [otp, setOtp] = useState("");

  const navigate = useNavigate();

  const handlePhoneChange = (e) => {
    const value = e.target.value.replace(/\D/g, "");
    if (value.length <= 10) setPhone(value);
  };

  const handleOtpChange = (e) => {
    const value = e.target.value.replace(/\D/g, "");
    if (value.length <= 6) setOtp(value);
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">

      {/* 🔹 TOP LEFT LOGO */}
      <div className="absolute top-5 left-6 flex items-center gap-2">
        <img src={logo} alt="logo" className="h-5" />
      </div>

      {/* 🔹 CENTER CONTENT */}
      <div className="flex flex-1 items-center justify-center px-4">

        <div className="w-full max-w-sm text-center">

          {/* TITLE */}
          <h1 className="text-2xl font-bold text-gray-900">
            ConsultFlow
          </h1>
          <p className="text-gray-500 text-sm mt-1 mb-8">
            WhatsApp-first Vet Consultations
          </p>

          {/* SLIDER */}
          <div className="overflow-hidden">
            <div
              className={`flex transition-transform duration-500 ${
                step === 2 ? "-translate-x-full" : "translate-x-0"
              }`}
            >

              {/* PHONE STEP */}
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

                <button
                  disabled={phone.length !== 10}
                  onClick={() => setStep(2)}
                  className={`w-full py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition
                  ${
                    phone.length === 10
                      ? "bg-whatsapp text-white"
                      : "bg-gray-300 text-gray-500"
                  }`}
                >
                  Send OTP <ArrowRight size={18} />
                </button>
              </div>

              {/* OTP STEP */}
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

                <button
                  disabled={otp.length !== 6}
                  onClick={() => navigate("/doctor/onboarding")}
                  className={`w-full py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition
                  ${
                    otp.length === 6
                      ? "bg-whatsapp text-white"
                      : "bg-gray-300 text-gray-500"
                  }`}
                >
                  Verify & Login <ArrowRight size={18} />
                </button>

                <button
                  onClick={() => setStep(1)}
                  className="w-full text-gray-500 text-sm text-center"
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