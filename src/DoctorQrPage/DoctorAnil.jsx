import React, { useMemo } from "react";
import logo from '../assets/images/logo1.png'
import appqr from '../assets/appqr.png'
import doctorAnilImage from '../assets/doctor anil.jpeg'


const BenefitIcon = ({ type }) => {
  const common = {
    fill: "none",
    stroke: "#2AB7A3",
    strokeWidth: 1.8,
    strokeLinecap: "round",
    strokeLinejoin: "round",
  };

  if (type === "health") {
    return (
      <svg viewBox="0 0 24 24" className="h-6 w-6" {...common}>
        <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
      </svg>
    );
  }

  if (type === "reminder") {
    return (
      <svg viewBox="0 0 24 24" className="h-6 w-6" {...common}>
        <circle cx="12" cy="12" r="10" />
        <polyline points="12 6 12 12 16 14" />
      </svg>
    );
  }

  if (type === "report") {
    return (
      <svg viewBox="0 0 24 24" className="h-6 w-6" {...common}>
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" />
        <line x1="16" y1="13" x2="8" y2="13" />
        <line x1="16" y1="17" x2="8" y2="17" />
        <polyline points="10 9 9 9 8 9" />
      </svg>
    );
  }

  return (
    <svg viewBox="0 0 24 24" className="h-6 w-6" {...common}>
      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z" />
    </svg>
  );
};

const benefits = [
  {
    type: "health",
    label: "Track Health",
    desc: "Full medical history, weight, vaccinations",
  },
  {
    type: "reminder",
    label: "Get Reminders",
    desc: "Vaccines, deworm & follow-up alerts",
  },
  {
    type: "report",
    label: "Access Reports",
    desc: "Lab results & prescriptions anytime",
  },
  {
    type: "care",
    label: "Better Care",
    desc: "Personalised insights for your pet",
  },
];

export default function DoctorAnil() {

  return (
    <main className="min-h-screen bg-slate-300 px-4 py-8 font-sans text-[#0B1D35] sm:px-6 print:bg-white print:p-0">
      <section className="relative mx-auto flex min-h-[1200px] w-full max-w-[900px] flex-col overflow-hidden bg-white shadow-[0_20px_60px_rgba(11,29,53,0.18)] print:min-h-screen print:max-w-none print:shadow-none">
        <div className="relative z-10 h-[3px] bg-gradient-to-r from-[#2AB7A3] to-transparent" />

        <header className="relative z-10 flex flex-col gap-5 bg-[#0B1D35] px-6 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-12">
          <div className="flex items-center gap-4">
            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-[14px] bg-[#2AB7A3]">
              <svg viewBox="0 0 28 28" className="h-7 w-7" fill="none" aria-hidden="true">
                <rect x="11" y="3" width="6" height="22" rx="3" fill="white" />
                <rect x="3" y="11" width="22" height="6" rx="3" fill="white" />
              </svg>
            </div>
            <div>
              <p className="font-serif text-lg uppercase tracking-[0.06em] text-white">Pet Vet Centre</p>
              <p className="mt-1 text-[11px] uppercase tracking-[0.12em] text-[#8A9BB8]">Advanced Veterinary Care</p>
            </div>
          </div>

          <div className="flex w-max items-center rounded-full ">
            <img
              src={logo}
              alt="SnoutIQ logo"
              className="block h-20 w-auto max-w-[120px] object-contain sm:h-20 sm:max-w-[120px]"
              loading="eager"
              decoding="async"
            />
          </div>

          <div className="absolute bottom-0 left-6 right-6 h-px bg-white/[0.07] sm:left-12 sm:right-12" />
        </header>

        <section className="relative z-10 flex flex-col gap-4 bg-[#1A3354] px-6 py-5 sm:flex-row sm:items-center sm:px-12">
          <div className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-[#2AB7A3] bg-[#122742]">
            <img
              src={doctorAnilImage}
              alt="Dr. Anil Kumar"
              className="h-full w-full object-cover object-center"
              loading="eager"
              decoding="async"
            />
          </div>

          <div className="flex-1">
            <p className="font-serif text-xl text-white">Dr. Anil Kumar</p>
            <p className="mt-1 text-xs tracking-[0.06em] text-[#3ECBB6]">B.V.Sc & A.H. · Veterinary Physician</p>
          </div>

          <div className="w-max rounded-full border border-[#2AB7A3]/30 bg-[#2AB7A3]/15 px-4 py-2 text-xs font-medium tracking-wide text-[#3ECBB6]">
            30+ Years Experience
          </div>
        </section>

        <section className="relative z-10 flex flex-1 flex-col items-center px-6 py-12 text-center sm:px-12 sm:py-16">
          <div className="pointer-events-none absolute left-1/2 top-28 h-[400px] w-[400px] -translate-x-1/2 rounded-full bg-[radial-gradient(circle,rgba(42,183,163,0.08)_0%,transparent_70%)]" />

          <p className="relative text-[11px] font-semibold uppercase tracking-[0.2em] text-[#2AB7A3]">
            Smart Pet Healthcare · SnoutIQ
          </p>

          <h1 className="relative mt-5 max-w-[560px] font-serif text-[2.4rem] leading-[1.08] text-[#0B1D35] sm:text-5xl md:text-[46px]">
            Your Pet&apos;s Health
            <br />
            Journey <em className="text-[#2AB7A3]">Starts Here</em>
          </h1>

          <p className="relative mt-4 max-w-[420px] text-[15px] font-light leading-6 text-[#4A5E7A] sm:text-base sm:leading-7">
            One scan. Complete health records, appointment reminders, and personalised care — all in one place.
          </p>

          <div className="relative mt-10 flex flex-col items-center sm:mt-12">
            <div className="relative flex h-[205px] w-[205px] items-center justify-center overflow-hidden rounded-[28px] bg-white shadow-[0_18px_50px_rgba(11,29,53,0.16),0_0_0_1px_rgba(11,29,53,0.07)] sm:h-[220px] sm:w-[220px]">
              <span className="absolute left-2.5 top-2.5 h-5 w-5 rounded-tl border-l-[3px] border-t-[3px] border-[#2AB7A3]" />
              <span className="absolute right-2.5 top-2.5 h-5 w-5 rounded-tr border-r-[3px] border-t-[3px] border-[#2AB7A3]" />
              <span className="absolute bottom-2.5 left-2.5 h-5 w-5 rounded-bl border-b-[3px] border-l-[3px] border-[#2AB7A3]" />
              <span className="absolute bottom-2.5 right-2.5 h-5 w-5 rounded-br border-b-[3px] border-r-[3px] border-[#2AB7A3]" />

              <div className="flex h-[180px] w-[180px] items-center justify-center opacity-90">
                <img
                  src={appqr}
                  alt="qr"
                  className="block h-[160px] w-[160px] object-contain sm:h-[172px] sm:w-[172px]"
                />
              </div>
            </div>

            <div className="mt-5 text-[13px] font-normal tracking-[0.06em] text-[#4A5E7A] sm:mt-6 sm:text-sm">
              <strong className="mb-1 block text-base font-semibold normal-case tracking-normal text-[#0B1D35] sm:text-lg">
                Scan to Create Your Pet Profile
              </strong>
              Free · Takes 60 seconds
            </div>
          </div>

          <div className="mx-auto mt-8 h-0.5 w-12 rounded-full bg-[#2AB7A3] sm:mt-10" />
        </section>

        <section className="relative z-10 border-t border-[#E8ECF2] bg-[#F5F7FA] px-6 py-10 sm:px-12">
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {benefits.map((benefit) => (
              <div
                key={benefit.label}
                className="flex flex-col items-center gap-3 rounded-[20px] border border-[#E8ECF2] bg-white px-4 py-6 text-center shadow-[0_2px_12px_rgba(11,29,53,0.08)]"
              >
                <div className="flex h-12 w-12 items-center justify-center rounded-[14px] bg-[#2AB7A3]/10">
                  <BenefitIcon type={benefit.type} />
                </div>
                <p className="text-[13px] font-bold leading-snug tracking-wide text-[#0B1D35]">{benefit.label}</p>
                <p className="text-[11px] font-light leading-5 text-[#8A9BB8]">{benefit.desc}</p>
              </div>
            ))}
          </div>
        </section>

        <footer className="relative z-10 flex flex-col gap-2 bg-[#0B1D35] px-6 py-5 text-[11px] tracking-[0.06em] text-[#8A9BB8] sm:flex-row sm:items-center sm:justify-between sm:px-12">
          <div>snoutiq.com · Free for Pet Parents</div>
          <div>
            Powered by <span className="font-medium text-[#3ECBB6]">SnoutIQ</span>
          </div>
        </footer>
      </section>
    </main>
  );
}
