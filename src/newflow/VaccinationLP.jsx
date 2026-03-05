import React, { useState } from "react";
import { Link } from "react-router-dom";
import { LPNavbar } from "./LPNavbar";
import {
  ShieldCheck,
  MapPin,
  CheckCircle2,
  ChevronDown,
  ArrowRight,
  Clock,
  Star,
} from "lucide-react";

const PUPPY_SCHEDULE = [
  { week: "6-8 weeks", vaccine: "DHPPi (1st dose)", note: "First protection starts" },
  {
    week: "10-12 weeks",
    vaccine: "DHPPi (2nd dose) + Leptospirosis",
    note: "Core booster dose",
  },
  {
    week: "14-16 weeks",
    vaccine: "DHPPi (3rd dose) + Anti-Rabies",
    note: "Rabies protection",
  },
  { week: "12-16 weeks", vaccine: "Deworming", note: "Internal parasite control" },
  { week: "6 months", vaccine: "Kennel Cough (optional)", note: "For social dogs" },
  { week: "1 year", vaccine: "Annual boosters", note: "Reminder included" },
];

const KITTEN_SCHEDULE = [
  { week: "6-8 weeks", vaccine: "FVRCP / Tricat (1st dose)", note: "First protection starts" },
  { week: "10-12 weeks", vaccine: "FVRCP (2nd dose)", note: "Core booster dose" },
  { week: "14-16 weeks", vaccine: "Anti-Rabies", note: "Essential protection" },
  { week: "12-16 weeks", vaccine: "Deworming", note: "Internal parasite control" },
  { week: "1 year", vaccine: "Annual boosters", note: "Reminder included" },
];

const AREAS = [
  "Delhi (North)",
  "Delhi (South)",
  "Delhi (East/West)",
  "Gurugram",
  "Noida",
  "Greater Noida",
  "Ghaziabad",
  "Faridabad",
];

const RISK_ITEMS = [
  {
    title: "Parvovirus can become critical in 48-72 hours",
    desc: "Unvaccinated puppies remain highly vulnerable. Prevention timing matters as much as the vaccine itself.",
    tag: "Puppies",
  },
  {
    title: "Distemper can cause long-term nerve damage",
    desc: "Even recovered pets may suffer lifelong neurological issues if vaccination is delayed.",
    tag: "Dogs and cats",
  },
  {
    title: "Wrong interval can reduce effectiveness",
    desc: "Dose intervals are clinical, not random. Late or early doses can weaken immunity build-up.",
    tag: "All pets",
  },
  {
    title: "Missed doses often require schedule adjustment",
    desc: "Skipping window targets can force the vet to change or restart schedule for reliable protection.",
    tag: "All pets",
  },
];

const REVIEWS = [
  {
    name: "Aryan T.",
    pet: "Puppy parent - Noida",
    stars: 5,
    text: "Reminder flow is excellent. We never missed a dose in the first year.",
  },
  {
    name: "Shreya P.",
    pet: "Kitten parent - Gurugram",
    stars: 5,
    text: "Clinic was very smooth and vet explained each vaccine clearly.",
  },
  {
    name: "Kabir S.",
    pet: "Puppy parent - Delhi",
    stars: 5,
    text: "Good package value and proper schedule tracking by the team.",
  },
];

function FaqItem({ q, a }) {
  const [open, setOpen] = useState(false);

  return (
    <div
      className={`overflow-hidden rounded-2xl border transition-all ${
        open ? "border-brand/30" : "border-slate-200"
      }`}
    >
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
      >
        <span className="text-sm font-semibold leading-snug text-slate-900">{q}</span>
        <ChevronDown
          className={`h-4 w-4 shrink-0 text-brand transition-transform ${open ? "rotate-180" : ""}`}
        />
      </button>
      {open ? (
        <p className="border-t border-slate-100 bg-brand-light/20 px-5 pb-4 pt-3 text-sm leading-relaxed text-slate-500">
          {a}
        </p>
      ) : null}
    </div>
  );
}

function ScheduleRow({ week, vaccine, note, i }) {
  return (
    <div
      className={`flex gap-4 rounded-xl border border-slate-100 px-4 py-3.5 ${
        i % 2 === 0 ? "bg-slate-50" : "bg-white"
      }`}
    >
      <div className="w-24 shrink-0 text-xs font-bold text-brand">{week}</div>
      <div className="flex-1">
        <p className="text-sm font-semibold text-slate-900">{vaccine}</p>
        <p className="mt-0.5 text-xs text-slate-500">{note}</p>
      </div>
    </div>
  );
}

function PriceCard({ type, mrp, price, tagline, selected, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`relative w-full rounded-2xl border-2 p-5 text-left transition-all ${
        selected
          ? "border-brand bg-brand-light shadow-lg shadow-brand/15"
          : "border-slate-200 bg-white hover:border-brand/40"
      }`}
    >
      <span className="absolute -top-3 left-4 rounded-full bg-accent px-3 py-1 text-[10px] font-extrabold text-white shadow">
        Rs 1,000 OFF
      </span>
      <div className="mt-1 flex items-start justify-between gap-2">
        <div>
          <p className="mb-0.5 text-base font-extrabold text-slate-900">
            {type === "puppy" ? "Puppy Package" : "Kitten Package"}
          </p>
          <p className="text-xs text-slate-500">{tagline}</p>
        </div>
        <div className="shrink-0 text-right">
          <p className="text-sm text-slate-400 line-through">{mrp}</p>
          <p className="text-2xl font-extrabold text-brand">{price}</p>
        </div>
      </div>
      {selected ? (
        <div className="mt-3 flex items-center gap-1.5 border-t border-brand/20 pt-3">
          <CheckCircle2 className="h-4 w-4 shrink-0 text-brand" />
          <p className="text-xs font-semibold text-brand">Selected - full schedule included</p>
        </div>
      ) : null}
    </button>
  );
}

export default function VaccinationLP() {
  const [petType, setPetType] = useState("");
  const [step, setStep] = useState(1);
  const [petName, setPetName] = useState("");
  const [petAge, setPetAge] = useState("");
  const [area, setArea] = useState("");
  const [ownerName, setOwnerName] = useState("");
  const [phone, setPhone] = useState("");
  const [submitted, setSubmitted] = useState(false);

  const schedule =
    petType === "puppy" ? PUPPY_SCHEDULE : petType === "kitten" ? KITTEN_SCHEDULE : PUPPY_SCHEDULE;

  const packagePrice = petType === "kitten" ? "Rs 4,500" : "Rs 7,000";
  const packageMrp = petType === "kitten" ? "Rs 5,500" : "Rs 8,000";

  const step1Valid = Boolean(petType && petName.trim() && petAge && area);
  const step2Valid = Boolean(ownerName.trim() && phone.trim().length >= 10);

  const resetToStepOne = () => {
    setStep(1);
    setSubmitted(false);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <div className="flex min-h-screen flex-col bg-white">
      <LPNavbar />

      <div className="bg-accent px-4 py-2 text-center text-white">
        <p className="text-xs font-bold">Limited offer: Rs 1,000 off on vaccination packages in Delhi NCR</p>
      </div>

      <main className="flex-1 pb-24 md:pb-0">
        <section className="bg-gradient-to-br from-brand-light/50 via-white to-white px-4 pb-12 pt-8">
          <div className="mx-auto max-w-7xl">
            <div className="grid gap-8 md:grid-cols-[1.05fr_0.95fr] md:items-start">
              <div className="text-center md:pt-4 md:text-left">
                <span className="mb-4 inline-flex items-center gap-1.5 rounded-full border border-brand/20 bg-brand-light px-3 py-1 text-xs font-bold text-brand">
                  <MapPin className="h-3 w-3" /> Delhi NCR - 50+ verified clinics
                </span>
                <h1 className="mb-3 text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl lg:text-5xl">
                  Protect your pet with the right vaccine at the right time
                </h1>
                <p className="mx-auto max-w-sm text-base leading-relaxed text-slate-500 md:mx-0 md:max-w-xl">
                  Wrong timing and missed doses reduce protection. We handle the full first-year
                  vaccine plan with reminders and clinic coordination.
                </p>

                <div className="mt-6 hidden flex-wrap gap-3 md:flex">
                  <span className="rounded-full border border-brand/20 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                    Full first-year tracking
                  </span>
                  <span className="rounded-full border border-brand/20 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                    Reminder before every dose
                  </span>
                  <span className="rounded-full border border-brand/20 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                    Verified partner clinics
                  </span>
                </div>

                <button
                  type="button"
                  onClick={() =>
                    document.getElementById("booking-form")?.scrollIntoView({ behavior: "smooth", block: "start" })
                  }
                  className="mt-8 hidden items-center gap-2 rounded-2xl bg-accent px-6 py-3 text-sm font-extrabold text-white hover:bg-accent-hover md:inline-flex"
                >
                  Book package now <ArrowRight className="h-4 w-4" />
                </button>
              </div>

              {!submitted ? (
                <div
                  id="booking-form"
                  className="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50 md:sticky md:top-24"
                >
                  {step === 1 ? (
                    <div className="space-y-5">
                      <p className="text-center text-sm font-extrabold text-slate-900">Start with your pet</p>

                      <div className="space-y-3">
                        <PriceCard
                          type="puppy"
                          mrp="Rs 8,000"
                          price="Rs 7,000"
                          tagline="Full year - DHPPi + Rabies + Deworming"
                          selected={petType === "puppy"}
                          onClick={() => setPetType("puppy")}
                        />
                        <PriceCard
                          type="kitten"
                          mrp="Rs 5,500"
                          price="Rs 4,500"
                          tagline="Full year - FVRCP + Rabies + Deworming"
                          selected={petType === "kitten"}
                          onClick={() => setPetType("kitten")}
                        />
                      </div>

                      {petType ? (
                        <>
                          <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-900">
                              {petType === "puppy" ? "Puppy name" : "Kitten name"}
                            </label>
                            <input
                              type="text"
                              value={petName}
                              onChange={(e) => setPetName(e.target.value)}
                              placeholder={petType === "puppy" ? "e.g. Bruno" : "e.g. Luna"}
                              className="w-full rounded-xl border border-slate-200 px-4 py-3 text-base text-slate-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                            />
                          </div>

                          <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-900">
                              Pet age
                            </label>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                              {[
                                "Under 6 weeks",
                                "6-8 weeks",
                                "2-4 months",
                                "4-6 months",
                                "6-12 months",
                                "Over 1 year",
                              ].map((age) => (
                                <button
                                  key={age}
                                  type="button"
                                  onClick={() => setPetAge(age)}
                                  className={`rounded-xl border py-2.5 text-xs font-semibold transition-all ${
                                    petAge === age
                                      ? "border-brand bg-brand-light text-brand"
                                      : "border-slate-200 text-slate-600 hover:border-brand/40"
                                  }`}
                                >
                                  {age}
                                </button>
                              ))}
                            </div>
                          </div>

                          <div>
                            <label className="mb-2 block text-sm font-semibold text-slate-900">
                              Area in Delhi NCR
                            </label>
                            <select
                              value={area}
                              onChange={(e) => setArea(e.target.value)}
                              className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                            >
                              <option value="">Select area...</option>
                              {AREAS.map((item) => (
                                <option key={item} value={item}>
                                  {item}
                                </option>
                              ))}
                            </select>
                          </div>

                          <button
                            type="button"
                            disabled={!step1Valid}
                            onClick={() => setStep(2)}
                            className="flex w-full items-center justify-center gap-2 rounded-2xl bg-accent py-4 text-base font-extrabold text-white shadow-md shadow-orange-100 transition-colors hover:bg-accent-hover disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                          >
                            Continue booking <ArrowRight className="h-4 w-4" />
                          </button>
                        </>
                      ) : (
                        <p className="py-2 text-center text-xs text-slate-400">
                          Select a package to continue
                        </p>
                      )}
                    </div>
                  ) : (
                    <div className="space-y-5">
                      <div className="space-y-1.5 rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm">
                        <p className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                          Booking summary
                        </p>
                        <div className="flex justify-between">
                          <span className="text-slate-500">Pet</span>
                          <span className="font-semibold text-slate-900">
                            {petName} - {petType} - {petAge}
                          </span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-slate-500">Area</span>
                          <span className="font-semibold text-slate-900">{area}</span>
                        </div>
                        <div className="mt-1 flex justify-between border-t border-slate-200 pt-1">
                          <span className="text-slate-500">Package price</span>
                          <div className="text-right">
                            <div className="flex items-center gap-2">
                              <span className="text-sm text-slate-400 line-through">{packageMrp}</span>
                              <span className="text-lg font-extrabold text-brand">{packagePrice}</span>
                            </div>
                            <p className="text-[10px] font-bold text-accent">Rs 1,000 discount applied</p>
                          </div>
                        </div>
                      </div>

                      <div>
                        <label className="mb-2 block text-sm font-semibold text-slate-900">Your name</label>
                        <input
                          type="text"
                          value={ownerName}
                          onChange={(e) => setOwnerName(e.target.value)}
                          placeholder="Full name"
                          className="w-full rounded-xl border border-slate-200 px-4 py-3 text-base text-slate-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                        />
                      </div>

                      <div>
                        <label className="mb-2 block text-sm font-semibold text-slate-900">Mobile number</label>
                        <div className="relative">
                          <span className="absolute left-4 top-1/2 -translate-y-1/2 text-base font-medium text-slate-500">
                            +91
                          </span>
                          <input
                            type="tel"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value.replace(/\D/g, "").slice(0, 10))}
                            placeholder="98765 43210"
                            className="w-full rounded-xl border border-slate-200 py-3 pl-14 pr-4 text-base text-slate-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                          />
                        </div>
                      </div>

                      <div className="flex gap-3">
                        <button
                          type="button"
                          onClick={() => setStep(1)}
                          className="rounded-2xl border border-slate-200 px-5 py-3.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        >
                          Back
                        </button>
                        <button
                          type="button"
                          disabled={!step2Valid}
                          onClick={() => setSubmitted(true)}
                          className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-accent py-3.5 text-base font-extrabold text-white transition-colors hover:bg-accent-hover disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                        >
                          Confirm booking <ArrowRight className="h-4 w-4" />
                        </button>
                      </div>

                      <p className="text-center text-xs text-slate-400">
                        No payment now. Team confirms clinic and first visit date.
                      </p>
                    </div>
                  )}
                </div>
              ) : (
                <div className="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-xl md:sticky md:top-24">
                  <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                    <CheckCircle2 className="h-8 w-8 text-green-600" />
                  </div>
                  <h3 className="mb-2 text-2xl font-extrabold text-slate-900">Booking received</h3>
                  <p className="mb-6 text-sm text-slate-500">
                    Our team will call +91 {phone} to confirm nearest clinic and first visit date.
                  </p>

                  <div className="mb-5 rounded-2xl bg-brand-light p-4 text-left">
                    <p className="mb-2 text-xs font-bold text-brand">What happens next:</p>
                    {[
                      "Team calls to confirm clinic",
                      "First visit scheduled as per your slot",
                      "WhatsApp reminders before every dose",
                      "Digital record maintained",
                    ].map((item, i) => (
                      <p key={i} className="mb-1.5 flex items-start gap-2 text-xs text-slate-700">
                        <span className="mt-0.5 font-bold text-brand">OK</span>
                        {item}
                      </p>
                    ))}
                  </div>

                  <a
                    href="https://wa.me/919999999999"
                    className="block text-sm font-semibold text-brand hover:underline"
                  >
                    Need help? Chat on WhatsApp
                  </a>
                </div>
              )}
            </div>

            <div className="mt-6 flex flex-wrap items-center justify-center gap-4 text-xs text-slate-400 sm:gap-6">
              <span className="flex items-center gap-1">
                <ShieldCheck className="h-3.5 w-3.5 text-brand" /> Verified clinics
              </span>
              <span className="flex items-center gap-1">
                <Star className="h-3.5 w-3.5 text-brand" /> 4.8 rated
              </span>
              <span className="flex items-center gap-1">
                <Clock className="h-3.5 w-3.5 text-brand" /> Reminder included
              </span>
            </div>
          </div>
        </section>

        <section className="bg-brand px-4 py-8">
          <div className="mx-auto grid max-w-6xl grid-cols-2 gap-5 text-center sm:grid-cols-4">
            {[
              { v: "5,000+", l: "Pets vaccinated" },
              { v: "50+", l: "Verified clinics" },
              { v: "4.8/5", l: "Average rating" },
              { v: "100%", l: "Schedule tracked" },
            ].map((stat) => (
              <div key={stat.l}>
                <p className="text-2xl font-extrabold text-white">{stat.v}</p>
                <p className="mt-0.5 text-xs text-blue-200">{stat.l}</p>
              </div>
            ))}
          </div>
        </section>

        <section className="bg-white px-4 py-14">
          <div className="mx-auto max-w-6xl">
            <div className="mb-8 text-center">
              <span className="mb-3 inline-block rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-700">
                Read this first
              </span>
              <h2 className="text-2xl font-extrabold text-slate-900 sm:text-3xl">
                What happens when vaccination timing goes wrong
              </h2>
              <p className="mx-auto mt-2 max-w-2xl text-sm text-slate-500">
                Correct schedule and dose intervals are critical for effective immunity in the first year.
              </p>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              {RISK_ITEMS.map((item) => (
                <div key={item.title} className="rounded-2xl border border-red-100 bg-red-50 p-5">
                  <div className="mb-1 flex items-center gap-2">
                    <p className="text-sm font-extrabold text-slate-900">{item.title}</p>
                    <span className="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">
                      {item.tag}
                    </span>
                  </div>
                  <p className="text-xs leading-relaxed text-slate-600">{item.desc}</p>
                </div>
              ))}
            </div>

            <div className="mx-auto mt-6 max-w-3xl rounded-2xl border border-brand/20 bg-brand-light p-5 text-center">
              <p className="mb-1 text-sm font-extrabold text-brand">This is why schedule tracking matters.</p>
              <p className="text-xs text-slate-600">
                You get reminders before each dose so there is no missed window.
              </p>
            </div>
          </div>
        </section>

        <section className="bg-white px-4 py-14">
          <div className="mx-auto max-w-5xl">
            <h2 className="mb-2 text-center text-2xl font-extrabold text-slate-900">
              Full vaccination schedule
            </h2>
            <p className="mb-6 text-center text-sm text-slate-500">
              Current active plan for{" "}
              <span className="font-semibold text-slate-700">
                {petType === "puppy" ? "your puppy" : petType === "kitten" ? "your kitten" : "your pet"}
              </span>
              .
            </p>

            <div className="mb-5 flex gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
              {["puppy", "kitten"].map((type) => (
                <button
                  key={type}
                  type="button"
                  onClick={() => setPetType(type)}
                  className={`flex-1 rounded-lg py-2 text-sm font-semibold transition-all ${
                    petType === type
                      ? "border border-slate-200 bg-white text-brand shadow-sm"
                      : "text-slate-500 hover:text-slate-700"
                  }`}
                >
                  {type === "puppy" ? "Puppy" : "Kitten"}
                </button>
              ))}
            </div>

            <div className="space-y-2">
              {schedule.map((row, i) => (
                <ScheduleRow key={`${row.week}-${row.vaccine}`} {...row} i={i} />
              ))}
            </div>
          </div>
        </section>

        <section className="relative overflow-hidden bg-slate-900 px-4 py-14">
          <div className="pointer-events-none absolute inset-0">
            <div className="absolute left-0 top-0 h-48 w-48 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand/10 blur-3xl" />
          </div>

          <div className="relative mx-auto max-w-6xl">
            <p className="mb-2 text-center text-xs font-bold tracking-widest text-blue-400">REAL PET PARENTS</p>
            <h2 className="mb-8 text-center text-2xl font-extrabold text-white">
              Trusted by first-time pet parents
            </h2>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {REVIEWS.map((review) => (
                <div key={review.name} className="rounded-2xl border border-white/10 bg-white/5 p-5">
                  <div className="mb-3 flex gap-0.5">
                    {Array.from({ length: review.stars }).map((_, idx) => (
                      <span key={idx} className="text-sm text-yellow-400">
                        *
                      </span>
                    ))}
                  </div>
                  <p className="mb-4 text-sm leading-relaxed text-slate-300">&quot;{review.text}&quot;</p>
                  <div>
                    <p className="text-xs font-bold text-white">{review.name}</p>
                    <p className="text-xs text-slate-500">{review.pet}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="bg-white px-4 py-14">
          <div className="mx-auto max-w-4xl">
            <h2 className="mb-8 text-center text-2xl font-extrabold text-slate-900">Got questions?</h2>
            <div className="space-y-3">
              {[
                {
                  q: "What if my puppy already took some doses elsewhere?",
                  a: "Share prior records and the vet will continue from valid schedule stage.",
                },
                {
                  q: "Is package amount all-inclusive?",
                  a: "Yes, package covers first-year planned vaccine schedule with reminders.",
                },
                {
                  q: "Do you offer home vaccination?",
                  a: "No. Vaccines must be administered with proper cold-chain handling in clinic settings.",
                },
                {
                  q: "How do reminders work?",
                  a: "You receive WhatsApp reminder before each due dose and follow-up coordination.",
                },
              ].map((item) => (
                <FaqItem key={item.q} q={item.q} a={item.a} />
              ))}
            </div>
          </div>
        </section>

        <section className="relative overflow-hidden bg-brand px-4 py-14 text-center">
          <div className="pointer-events-none absolute inset-0">
            <div className="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/5 blur-2xl" />
          </div>
          <div className="relative mx-auto max-w-3xl">
            <p className="mb-2 text-xs font-bold tracking-wider text-white/80">LIMITED OFFER</p>
            <h2 className="mb-2 text-2xl font-extrabold text-white">
              Start protection plan today
            </h2>
            <p className="mb-2 text-sm text-blue-200">Rs 1,000 off - Delhi NCR only - no payment now</p>
            <div className="mb-7 flex items-center justify-center gap-3">
              <span className="text-lg text-white/60 line-through">{packageMrp}</span>
              <span className="text-3xl font-extrabold text-white">{packagePrice}</span>
              <span className="rounded-full bg-accent px-2 py-1 text-xs font-bold text-white">SAVE Rs 1,000</span>
            </div>
            <button
              type="button"
              onClick={resetToStepOne}
              className="w-full rounded-2xl bg-accent py-4 text-lg font-extrabold text-white shadow-lg shadow-orange-900/30 transition-colors hover:bg-accent-hover"
            >
              Book package - free callback
            </button>
          </div>
        </section>
      </main>

      <footer className="border-t border-slate-100 bg-white px-4 py-5 pb-28 md:pb-5">
        <p className="text-center text-xs text-slate-400">
          {new Date().getFullYear()} SnoutiQ. All rights reserved.{" "}
          <Link to="/privacy-policy" className="hover:text-brand">
            Privacy Policy
          </Link>
        </p>
      </footer>

      <div className="fixed bottom-0 left-0 right-0 z-50 border-t border-slate-200 bg-white/98 px-4 py-3 shadow-2xl backdrop-blur-sm md:hidden">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() =>
              document.getElementById("booking-form")?.scrollIntoView({ behavior: "smooth", block: "start" })
            }
            className="flex-1 rounded-xl bg-accent py-3 text-sm font-extrabold text-white hover:bg-accent-hover"
          >
            Book now - {packagePrice}
          </button>
        </div>
      </div>
    </div>
  );
}
