'use client';

import { useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  Check,
  ChevronDown,
  CreditCard,
  IndianRupee,
  Phone,
  User,
  PawPrint,
} from "lucide-react";

const TOTAL_STEPS = 2;

const fieldBase =
  "h-[50px] w-full rounded-2xl border border-[#e8eaee] bg-[#f4f5f7] px-4 text-[15px] text-slate-700 outline-none placeholder:text-[#8a94a6] focus:border-[#16a34a] focus:ring-0";
const sectionLabel =
  "text-[12px] font-bold uppercase tracking-[0.08em] text-[#98a2b3] mb-3";
const primaryBtn =
  "flex h-[54px] w-full items-center justify-center gap-2 rounded-2xl bg-[#2fd161] px-4 text-[17px] font-bold text-white shadow-[0_10px_22px_rgba(47,209,97,0.24)] active:scale-[0.99] transition-transform";

export default function NewDoctorNewRequestView() {
  const navigate = useNavigate();
  const [step, setStep] = useState(0);
  const [form, setForm] = useState({
    phone: "",
    amount: "499",
    parentName: "",
    petName: "",
    breed: "",
    gender: "Male",
    age: "",
  });

  const parentPhonePreview = useMemo(() => {
    return form.phone?.trim() || "2342342342";
  }, [form.phone]);

  const goNext = () => setStep((prev) => Math.min(prev + 1, TOTAL_STEPS - 1));

  const handleBack = () => {
    if (step > 0) {
      setStep((prev) => Math.max(prev - 1, 0));
      return;
    }

    if (window.history.length > 1) {
      navigate(-1);
    } else {
      navigate("/counsltflow/dashboard", { replace: true });
    }
  };

  const updateField = (key, value) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  const handlePaymentReceived = () => {
    navigate("/counsltflow/digital-prescription", {
      state: {
        consultationId: null,
        paymentCompleted: true,
        lockUntilSubmit: true,
        fromNewRequest: true,
        patientData: {
          phone: form.phone,
          amount: form.amount,
          parentName: form.parentName,
          petName: form.petName,
          breed: form.breed,
          gender: form.gender,
          age: form.age,
        },
      },
    });
  };

  return (
    <div className="min-h-screen bg-[#f5f6f8]">
      <div className="mx-auto w-full max-w-[430px] min-h-screen bg-[#f5f6f8]">
        {step === 0 && (
          <div className="flex flex-col min-h-screen">
            <Header title="New Consultation" onBack={handleBack} />

            <div className="flex-1 px-5 pt-6 pb-7">
              <div className="space-y-5">
                <div>
                  <p className={sectionLabel}>Mandatory Info</p>

                  <div className="space-y-4">
                    <div className="relative">
                      <Phone
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="tel"
                        value={form.phone}
                        onChange={(e) => updateField("phone", e.target.value)}
                        placeholder="Parent WhatsApp Number"
                        className={`${fieldBase} pl-11`}
                      />
                    </div>

                    <div className="relative">
                      <IndianRupee
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="number"
                        value={form.amount}
                        onChange={(e) => updateField("amount", e.target.value)}
                        placeholder="499"
                        className={`${fieldBase} pl-11`}
                      />
                    </div>
                  </div>
                </div>

                <div>
                  <p className={sectionLabel}>Pet & Parent (Optional)</p>

                  <div className="space-y-4">
                    <div className="relative">
                      <User
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="text"
                        value={form.parentName}
                        onChange={(e) => updateField("parentName", e.target.value)}
                        placeholder="Pet Parent Name"
                        className={`${fieldBase} pl-11`}
                      />
                    </div>

                    <div className="relative">
                      <PawPrint
                        size={18}
                        className="absolute left-4 top-1/2 -translate-y-1/2 text-[#98a2b3]"
                      />
                      <input
                        type="text"
                        value={form.petName}
                        onChange={(e) => updateField("petName", e.target.value)}
                        placeholder="Pet Name"
                        className={`${fieldBase} pl-11`}
                      />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                      <input
                        type="text"
                        value={form.breed}
                        onChange={(e) => updateField("breed", e.target.value)}
                        placeholder="Breed"
                        className={fieldBase}
                      />

                      <div className="relative">
                        <select
                          value={form.gender}
                          onChange={(e) => updateField("gender", e.target.value)}
                          className={`${fieldBase} appearance-none pr-9`}
                        >
                          <option>Male</option>
                          <option>Female</option>
                        </select>

                        <ChevronDown
                          size={16}
                          className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-700"
                        />
                      </div>
                    </div>

                    <input
                      type="text"
                      value={form.age}
                      onChange={(e) => updateField("age", e.target.value)}
                      placeholder="Age (e.g. 2 years)"
                      className={fieldBase}
                    />
                  </div>
                </div>
              </div>

              <div className="pt-6">
                <button type="button" onClick={goNext} className={primaryBtn}>
                  Send Payment Link
                  <CreditCard size={18} />
                </button>
              </div>
            </div>
          </div>
        )}

        {step === 1 && (
          <div className="flex flex-col min-h-screen">
            <Header title="Payment Status" onBack={handleBack} />

            <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
              <div className="relative mb-7">
                <div className="w-[96px] h-[96px] rounded-full border-[4px] border-[#f4c691] flex items-center justify-center bg-transparent">
                  <IndianRupee size={38} className="text-[#f97316]" />
                </div>
                <div className="absolute inset-0 rounded-full border-[4px] border-transparent border-t-[#f97316] rotate-[20deg]" />
              </div>

              <h2 className="text-[20px] font-bold text-[#0f2749] mb-3">
                Awaiting Payment
              </h2>

              <p className="text-[15px] leading-7 text-[#667085] max-w-[290px]">
                Payment link sent to {parentPhonePreview}.
                <br />
                Waiting for confirmation...
              </p>

              <button
                type="button"
                onClick={handlePaymentReceived}
                className="mt-12 h-[50px] px-8 rounded-full bg-[#2fd161] text-white text-[15px] font-bold flex items-center gap-2 shadow-[0_10px_22px_rgba(47,209,97,0.24)] active:scale-[0.98] transition-transform"
              >
                Simulate Payment Received
                <span className="flex h-5 w-5 items-center justify-center rounded-full border border-white/70">
                  <Check size={12} />
                </span>
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function Header({ title, onBack }) {
  return (
    <div className="flex items-center gap-3 px-5 h-[68px] bg-[#16a34a] text-white shadow-[0_2px_12px_rgba(0,0,0,0.08)]">
      <button
        type="button"
        onClick={onBack}
        className="flex h-9 w-9 items-center justify-center rounded-full active:scale-95 transition"
      >
        <ArrowLeft size={22} />
      </button>

      <h1 className="text-[18px] font-bold">{title}</h1>
    </div>
  );
}