import React, { useState } from "react";
import { Button } from "./Button";
import {
  ChevronLeft,
  Stethoscope,
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
  <div className="sticky top-0 z-50 bg-white/90 backdrop-blur-md px-4 py-3 flex items-center shadow-sm border-b border-stone-100 md:px-10 lg:px-16 md:py-4">
    {onBack ? (
      <button
        onClick={onBack}
        className="p-2 -ml-2 text-stone-500 hover:bg-stone-100 rounded-full transition-colors"
        aria-label="Go back"
      >
        <ChevronLeft size={24} />
      </button>
    ) : null}

    <h1 className="flex-1 text-center font-bold text-lg text-stone-800 md:text-2xl">
      {title}
    </h1>

    {onBack ? <div className="w-10" /> : null}
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
            <div className="w-16 h-16 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-4 md:w-24 md:h-24">
              <img src={logo} alt="SnoutIQ Logo" className="w-8 md:w-16" />
            </div>
            <h2 className="text-2xl font-bold text-stone-800 md:text-4xl">
              Welcome, Doctor
            </h2>
            <p className="text-stone-500 mt-2 text-sm md:text-lg">
              Log in to manage your consultations.
            </p>
            <span className="inline-block mt-2 bg-stone-100 text-stone-500 text-[10px] px-2 py-1 rounded md:text-xs md:px-3 md:py-1.5">
              DEMO MODE: Use any number
            </span>
          </div>

          <div className="bg-white p-6 rounded-2xl shadow-sm border border-stone-100 space-y-6 md:p-10 md:rounded-3xl md:shadow-md">
            {step === "mobile" ? (
              <>
                <div>
                  <label className="block text-xs font-bold uppercase text-stone-400 mb-1 md:text-sm">
                    Mobile Number
                  </label>
                  <div className="flex items-center border border-stone-200 rounded-xl px-3 bg-stone-50 focus-within:ring-2 focus-within:ring-brand-200 md:px-4 md:py-1">
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
                      className="flex-1 py-3 bg-transparent outline-none font-medium text-stone-800 md:py-4 md:text-lg"
                    />
                  </div>
                </div>

                {/* ✅ Bigger button only on md+ (mobile unchanged) */}
                <Button
                  onClick={handleSendOtp}
                  disabled={mobile.length < 10}
                  fullWidth
                  className="md:text-xl md:py-4 md:rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600"
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
                    className="w-full py-3 px-4 text-center text-2xl tracking-widest border border-stone-200 rounded-xl bg-stone-50 focus:outline-none focus:ring-2 focus:ring-brand-200 md:py-4 md:text-3xl md:rounded-2xl"
                  />
                  <p className="text-xs text-center text-stone-400 mt-2 md:text-sm">
                    Sent to +91 {mobile}
                  </p>
                </div>

                <Button
                  onClick={onLogin}
                  disabled={otp.length < 4}
                  fullWidth
                  className="md:text-xl md:py-4 md:rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600"
                >
                  Verify & Login
                </Button>

                <button
                  onClick={() => setStep("mobile")}
                  className="w-full text-xs text-brand-600 font-medium py-2 md:text-sm"
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
              className="text-brand-600 font-bold text-sm hover:underline mt-1 md:text-lg "
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
  const [agreed, setAgreed] = useState(false);
  const [dayPrice, setDayPrice] = useState("");
  const [nightPrice, setNightPrice] = useState("");
  const [isNightShift, setIsNightShift] = useState(false);

  const calculateCommission = (priceStr) => {
    const price = parseFloat(priceStr);
    if (isNaN(price) || price === 0) return null;
    const commission = Math.max(price * 0.25, 99);
    const earning = price - commission;
    return { commission: Math.ceil(commission), earning: Math.floor(earning) };
  };

  const dayMath = calculateCommission(dayPrice);
  const nightMath = calculateCommission(nightPrice);

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <VetHeader onBack={onBack} title="Partner Registration" />

      <PageWrap>
        <div className="flex-1 px-4 py-6 pb-32 overflow-y-auto no-scrollbar md:px-0 md:py-12">
          <p className="text-sm text-stone-500 mb-6 px-2 md:px-0 md:text-lg">
            Join India&apos;s most trusted network of empathetic veterinarians.
          </p>

          <div className="space-y-6 md:space-y-0 md:grid md:grid-cols-12 md:gap-10 lg:gap-12">
            {/* LEFT */}
            <div className="md:col-span-7 lg:col-span-8 space-y-6">
              {/* Section 1 */}
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    1
                  </span>
                  Basic Details
                </h3>

                <input
                  type="text"
                  placeholder="Full Name (Dr. ...)"
                  className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl"
                />
                <input
                  type="text"
                  placeholder="Clinic Name"
                  className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl"
                />

                <div className="grid grid-cols-2 gap-3">
                  <input
                    type="text"
                    placeholder="City"
                    className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl"
                  />
                  <input
                    type="tel"
                    placeholder="WhatsApp Number"
                    className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl"
                  />
                </div>

                <p className="text-[10px] text-stone-400 flex items-center gap-1 md:text-xs">
                  <Lock size={10} /> Your number is kept private and never shared
                  directly with pet parents.
                </p>

                <input
                  type="email"
                  placeholder="Email Address"
                  className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl"
                />
              </section>

              {/* Section 2 */}
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    2
                  </span>
                  Professional Details
                </h3>

                <input
                  type="text"
                  placeholder="Vet Registration Number (Required)"
                  className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl"
                />

                <select className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm text-stone-600 md:text-base md:p-4 md:rounded-2xl">
                  <option>Select Qualification</option>
                  <option>BVSc & AH</option>
                  <option>MVSc</option>
                  <option>PhD</option>
                </select>

                <div>
                  <label className="block text-xs font-bold text-stone-400 mb-2 md:text-sm">
                    Specialization
                  </label>
                  <div className="flex flex-wrap gap-2">
                    {["Dogs", "Cats", "Birds", "Exotic"].map((spec) => (
                      <label
                        key={spec}
                        className="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg text-sm bg-stone-50 md:text-base md:px-4 md:py-3 md:rounded-xl"
                      >
                        <input type="checkbox" className="accent-brand-600" />
                        {spec}
                      </label>
                    ))}
                  </div>
                </div>

                <input
                  type="number"
                  placeholder="Years of Experience"
                  className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-4 md:rounded-2xl"
                />
              </section>

              {/* Section 3 */}
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4 md:p-8 md:rounded-3xl">
                <h3 className="font-bold text-stone-800 flex items-center gap-2 md:text-lg">
                  <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs md:w-7 md:h-7 md:text-sm">
                    3
                  </span>
                  Availability & Timing
                </h3>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-bold text-stone-400 mb-1 flex items-center gap-1 md:text-sm">
                      <Sun size={10} /> Online Start
                    </label>
                    <input
                      type="time"
                      defaultValue="09:00"
                      className="w-full p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-3 md:rounded-2xl"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-stone-400 mb-1 flex items-center gap-1 md:text-sm">
                      <Moon size={10} /> Online End
                    </label>
                    <input
                      type="time"
                      defaultValue="20:00"
                      className="w-full p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-3 md:rounded-2xl"
                    />
                  </div>
                </div>

                <div className="col-span-2">
                  <label className="block text-xs font-bold text-stone-400 mb-1 md:text-sm">
                    Do Not Disturb / Sleep Time
                  </label>
                  <div className="flex gap-2 items-center">
                    <input
                      type="time"
                      defaultValue="22:00"
                      className="flex-1 p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-3 md:rounded-2xl"
                    />
                    <span className="text-stone-400 text-xs md:text-sm">to</span>
                    <input
                      type="time"
                      defaultValue="07:00"
                      className="flex-1 p-2 rounded-xl border border-stone-200 bg-stone-50 text-sm md:text-base md:p-3 md:rounded-2xl"
                    />
                  </div>
                </div>

                <div
                  className={`p-4 rounded-xl border transition-all md:p-6 md:rounded-2xl ${
                    isNightShift
                      ? "bg-indigo-50 border-indigo-200"
                      : "bg-stone-50 border-stone-200"
                  }`}
                >
                  <div className="flex justify-between items-start">
                    <div>
                      <h4
                        className={`font-bold text-sm md:text-base ${
                          isNightShift ? "text-indigo-800" : "text-stone-700"
                        }`}
                      >
                        Available for Night Shift?
                      </h4>
                      <p className="text-xs text-stone-500 mt-1 md:text-sm">
                        Emergency consults (10PM - 6AM)
                      </p>
                    </div>
                    <input
                      type="checkbox"
                      checked={isNightShift}
                      onChange={(e) => setIsNightShift(e.target.checked)}
                      className="w-5 h-5 accent-indigo-600"
                    />
                  </div>

                  {isNightShift ? (
                    <div className="mt-3 bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded inline-flex items-center gap-1 md:text-xs md:px-3 md:py-2">
                      <TrendingUp size={10} /> High Revenue Potential
                    </div>
                  ) : null}
                </div>
              </section>
            </div>

            {/* RIGHT (desktop only sticky) */}
            <div className="hidden md:block md:col-span-5 lg:col-span-4">
              <div className="md:sticky md:top-28 space-y-4">
                <section className="bg-white p-6 rounded-3xl shadow-md border border-stone-100 space-y-4">
                  <h3 className="font-bold text-stone-800 flex items-center gap-2 text-lg">
                    <span className="bg-brand-100 text-brand-700 w-7 h-7 rounded-full flex items-center justify-center text-sm">
                      4
                    </span>
                    Pricing & Commission
                  </h3>

                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-bold text-stone-400 mb-1">
                        Day Consult Fee (₹)
                      </label>
                      <input
                        type="number"
                        value={dayPrice}
                        onChange={(e) => setDayPrice(e.target.value)}
                        placeholder="399"
                        className="w-full p-4 rounded-2xl border border-stone-200 bg-stone-50 text-base font-bold text-stone-800"
                      />
                      {dayMath ? (
                        <div className="mt-2 text-xs flex justify-between bg-green-50 text-green-800 px-3 py-2 rounded-xl">
                          <span>
                            You earn: <strong>₹{dayMath.earning}</strong>
                          </span>
                          <span className="text-green-600/70">
                            Snoutiq Fee: ₹{dayMath.commission}
                          </span>
                        </div>
                      ) : null}
                    </div>

                    <div>
                      <label className="block text-sm font-bold text-stone-400 mb-1">
                        Night Consult Fee (₹)
                      </label>
                      <input
                        type="number"
                        value={nightPrice}
                        onChange={(e) => setNightPrice(e.target.value)}
                        placeholder="599"
                        className="w-full p-4 rounded-2xl border border-stone-200 bg-stone-50 text-base font-bold text-stone-800"
                        disabled={!isNightShift}
                      />
                      {nightMath ? (
                        <div className="mt-2 text-xs flex justify-between bg-green-50 text-green-800 px-3 py-2 rounded-xl">
                          <span>
                            You earn: <strong>₹{nightMath.earning}</strong>
                          </span>
                          <span className="text-green-600/70">
                            Snoutiq Fee: ₹{nightMath.commission}
                          </span>
                        </div>
                      ) : null}
                      {!isNightShift ? (
                        <p className="mt-2 text-xs text-stone-400">
                          Enable night shift to set a night fee.
                        </p>
                      ) : null}
                    </div>
                  </div>

                  <div className="bg-amber-50 p-4 rounded-2xl border border-amber-100">
                    <h4 className="text-amber-800 font-bold text-xs uppercase mb-2 flex items-center gap-1">
                      <DollarSign size={12} /> Commission Structure
                    </h4>
                    <ul className="text-sm text-amber-900/80 space-y-1 list-disc pl-4">
                      <li>We charge 25% OR ₹99 per consultation (whichever is higher).</li>
                      <li>The remaining amount is yours.</li>
                      <li>No monthly subscription fees.</li>
                    </ul>
                  </div>

                  <label className="flex gap-3 items-start p-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={agreed}
                      onChange={(e) => setAgreed(e.target.checked)}
                      className="mt-1 accent-brand-600"
                    />
                    <span className="text-sm text-stone-600 leading-relaxed">
                      I understand and agree to the pricing & commission structure.
                    </span>
                  </label>

                  {/* ✅ Big desktop CTA */}
                  <Button
                    onClick={onSubmit}
                    fullWidth
                    disabled={!agreed}
                    className={`md:text-xl md:py-4 md:rounded-2xl ${!agreed ? "opacity-50" : ""}`}
                  >
                    Submit Application
                  </Button>

                  <p className="text-xs text-stone-400 flex items-center gap-1">
                    <Lock size={12} /> Patient contact details stay private.
                  </p>
                </section>
              </div>
            </div>

            {/* MOBILE Pricing section: unchanged */}
            <div className="md:hidden space-y-6">
              <section className="bg-white p-5 rounded-2xl shadow-sm border border-stone-100 space-y-4">
                <h3 className="font-bold text-stone-800 flex items-center gap-2">
                  <span className="bg-brand-100 text-brand-700 w-6 h-6 rounded-full flex items-center justify-center text-xs">
                    4
                  </span>
                  Pricing & Commission
                </h3>

                <div className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-stone-400 mb-1">
                      Day Consult Fee (₹)
                    </label>
                    <input
                      type="number"
                      value={dayPrice}
                      onChange={(e) => setDayPrice(e.target.value)}
                      placeholder="399"
                      className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm font-bold text-stone-800"
                    />
                    {dayMath ? (
                      <div className="mt-1 text-[10px] flex justify-between bg-green-50 text-green-800 px-2 py-1 rounded">
                        <span>
                          You earn: <strong>₹{dayMath.earning}</strong>
                        </span>
                        <span className="text-green-600/70">
                          Snoutiq Fee: ₹{dayMath.commission}
                        </span>
                      </div>
                    ) : null}
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-stone-400 mb-1">
                      Night Consult Fee (₹)
                    </label>
                    <input
                      type="number"
                      value={nightPrice}
                      onChange={(e) => setNightPrice(e.target.value)}
                      placeholder="599"
                      className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 text-sm font-bold text-stone-800"
                    />
                    {nightMath ? (
                      <div className="mt-1 text-[10px] flex justify-between bg-green-50 text-green-800 px-2 py-1 rounded">
                        <span>
                          You earn: <strong>₹{nightMath.earning}</strong>
                        </span>
                        <span className="text-green-600/70">
                          Snoutiq Fee: ₹{nightMath.commission}
                        </span>
                      </div>
                    ) : null}
                  </div>
                </div>

                <div className="bg-amber-50 p-4 rounded-xl border border-amber-100">
                  <h4 className="text-amber-800 font-bold text-xs uppercase mb-2 flex items-center gap-1">
                    <DollarSign size={12} /> Commission Structure
                  </h4>
                  <ul className="text-xs text-amber-900/80 space-y-1 list-disc pl-4">
                    <li>We charge 25% OR ₹99 per consultation (whichever is higher).</li>
                    <li>The remaining amount is yours.</li>
                    <li>No monthly subscription fees.</li>
                  </ul>
                </div>

                <label className="flex gap-3 items-start p-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={agreed}
                    onChange={(e) => setAgreed(e.target.checked)}
                    className="mt-1 accent-brand-600"
                  />
                  <span className="text-xs text-stone-600 leading-relaxed">
                    I understand and agree to the pricing & commission structure defined above.
                  </span>
                </label>
              </section>
            </div>
          </div>

          <div className="h-24 md:hidden" />
        </div>
      </PageWrap>

      {/* Mobile sticky CTA stays SAME */}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button
          onClick={onSubmit}
          fullWidth
          disabled={!agreed}
          className={!agreed ? "opacity-50" : ""}
        >
          Submit Application
        </Button>
      </div>
    </div>
  );
};

// --- 3. Pending Approval Screen ---

export const VetPendingScreen = ({ onHome }) => {
  return (
    <div className="min-h-screen bg-white flex flex-col items-center justify-center p-8 text-center animate-fade-in md:bg-gradient-to-b md:from-white md:to-calm-bg md:p-16 md:py-24 md:rounded-3xl md:shadow-lg">
      <PageWrap>
        <div className="w-full max-w-sm mx-auto md:max-w-2xl md:py-10">
          <div className="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mb-6 text-amber-600 mx-auto md:w-24 md:h-24">
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
  const card = "bg-white border border-stone-100 shadow-[0_8px_30px_rgba(0,0,0,0.06)]";
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
              className="text-xs text-white/70 hover:text-white font-semibold md:text-sm"
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
                md:px-7 md:py-3 md:text-sm
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
                    className={`px-4 py-4 flex justify-between items-center md:px-7 md:py-6 ${
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
                    className={`${cardSoft} p-4 rounded-2xl md:p-7 md:rounded-3xl`}
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
