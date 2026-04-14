'use client';

import { useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  Check,
  ChevronDown,
  CircleCheckBig,
  CreditCard,
  FileText,
  IndianRupee,
  Phone,
  User,
  PawPrint,
  History,
} from "lucide-react";

const TOTAL_STEPS = 4;

const fieldBase =
  "h-14 w-full rounded-2xl border border-slate-100 bg-[#f8fbfa] px-4 text-[15px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#0d9a8d] focus:ring-0";
const sectionLabel =
  "text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400 mb-3";
const primaryBtn =
  "flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-[#29d264] px-4 text-[16px] font-semibold text-white active:scale-[0.99] transition-transform";

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
    prescription: "",
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
      navigate("/vet-dashboard");
    }
  };

  const updateField = (key, value) =>
    setForm((prev) => ({ ...prev, [key]: value }));

  return (
    <div className="min-h-screen bg-slate-100 flex items-center justify-center p-2 sm:p-4">
      <div className="w-full max-w-[390px] rounded-[2rem] overflow-hidden shadow-2xl bg-[#f3f5f4]">
        {step === 0 && (
          <div className="flex flex-col min-h-[640px]">
            <Header title="New Consultation" onBack={handleBack} />
            <div className="flex-1 px-4 sm:px-5 py-6 space-y-5 overflow-y-auto">
              <div>
                <p className={sectionLabel}>Mandatory Info</p>
                <div className="space-y-3">
                  <div className="relative">
                    <Phone
                      size={16}
                      className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
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
                      size={16}
                      className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
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
                <div className="space-y-3">
                  <div className="relative">
                    <User
                      size={16}
                      className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
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
                      size={16}
                      className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
                    />
                    <input
                      type="text"
                      value={form.petName}
                      onChange={(e) => updateField("petName", e.target.value)}
                      placeholder="Pet Name"
                      className={`${fieldBase} pl-11`}
                    />
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-[1fr_120px] gap-3">
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
                        size={15}
                        className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
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

              <button type="button" onClick={goNext} className={primaryBtn}>
                <CreditCard size={17} />
                Send Payment Link
              </button>
            </div>
          </div>
        )}

        {step === 1 && (
          <div className="flex flex-col min-h-[640px]">
            <Header title="Payment Status" onBack={handleBack} />
            <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
              <div className="w-24 h-24 rounded-full bg-[#fff7ed] border-4 border-[#fed7aa] flex items-center justify-center mb-6">
                <IndianRupee size={34} className="text-[#ef6c00]" />
              </div>
              <h2 className="text-[21px] font-semibold text-[#0f2749] mb-3">
                Awaiting Payment
              </h2>
              <p className="text-[15px] text-slate-500 leading-relaxed max-w-[260px]">
                Payment link sent to{" "}
                <span className="font-semibold text-slate-700">
                  {parentPhonePreview}
                </span>
                .
                <br />
                Waiting for confirmation...
              </p>
              <button
                type="button"
                onClick={goNext}
                className="mt-12 h-14 px-8 rounded-full bg-[#29d264] text-white text-[15px] font-semibold flex items-center gap-2 active:scale-[0.98] transition-transform"
              >
                Simulate Payment Received
                <span className="flex h-6 w-6 items-center justify-center rounded-full border border-white/60">
                  <Check size={13} />
                </span>
              </button>
            </div>
          </div>
        )}

        {step === 2 && (
          <div className="flex flex-col min-h-[640px]">
            <Header title="Consultation Done" onBack={handleBack} />
            <div className="flex-1 px-4 pt-5 pb-6 space-y-4 overflow-y-auto">
              <div className="rounded-[1.8rem] bg-[#e6f3ea] px-5 py-8 text-center">
                <div className="mx-auto w-[72px] h-[72px] rounded-full bg-[#12c94f] flex items-center justify-center mb-5">
                  <CircleCheckBig size={30} className="text-white" />
                </div>
                <h2 className="text-[20px] font-semibold text-[#185b3e] mb-1">
                  Consultation Paid
                </h2>
                <p className="text-[13px] text-[#49a374] max-w-[220px] mx-auto">
                  You can now proceed with the call or prescription
                </p>
              </div>

              <button
                type="button"
                onClick={goNext}
                className="w-full flex items-center gap-4 rounded-[1.5rem] bg-[#29d264] px-5 py-4 text-left active:scale-[0.99] transition-transform"
              >
                <div className="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center flex-shrink-0">
                  <FileText size={22} className="text-white" />
                </div>
                <div>
                  <p className="text-[19px] font-semibold text-white leading-tight">
                    Send Prescription
                  </p>
                  <p className="text-[13px] text-white/75 mt-1">
                    Digital Rx & Reminders
                  </p>
                </div>
              </button>

              <div className="flex items-center gap-4 rounded-[1.5rem] bg-white border border-slate-100 px-5 py-4">
                <div className="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                  <History size={22} className="text-slate-500" />
                </div>
                <div>
                  <p className="text-[19px] font-semibold text-slate-800 leading-tight">
                    View History
                  </p>
                  <p className="text-[13px] text-slate-500 mt-1">
                    Back to Dashboard
                  </p>
                </div>
              </div>
            </div>
          </div>
        )}

        {step === 3 && (
          <div className="flex flex-col min-h-[640px]">
            <Header title="Send Prescription" onBack={handleBack} />
            <div className="flex-1 px-4 pt-5 pb-6 overflow-y-auto">
              <div className="rounded-[1.8rem] bg-white border border-slate-100 p-5">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-12 h-12 rounded-2xl bg-[#eaf8ef] flex items-center justify-center flex-shrink-0">
                    <FileText size={20} className="text-[#16b84a]" />
                  </div>
                  <div>
                    <p className="text-[16px] font-semibold text-slate-900">
                      Prescription Notes
                    </p>
                    <p className="text-[13px] text-slate-500">
                      Add diagnosis, medicine and advice
                    </p>
                  </div>
                </div>
                <textarea
                  value={form.prescription}
                  onChange={(e) => updateField("prescription", e.target.value)}
                  placeholder="Diagnosis, medicines, dosage, and review notes..."
                  className="w-full min-h-[200px] resize-none rounded-[1.2rem] border border-slate-100 bg-[#f8fbfa] p-4 text-[15px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#0d9a8d] leading-relaxed"
                />
                <button type="button" className={`${primaryBtn} mt-4`}>
                  Submit Prescription
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function Header({ title, onBack }) {
  return (
    <div className="flex items-center gap-3 px-4 py-4 bg-green-600 text-white flex-shrink-0">
      <button
        type="button"
        onClick={onBack}
        className="flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/10"
      >
        <ArrowLeft size={20} />
      </button>

      <h1 className="text-base font-semibold">
        {title}
      </h1>
    </div>
  );
}