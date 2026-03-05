// VaccinationLP.jsx
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

// ─── Data ────────────────────────────────────────────────────────────────────
const PUPPY_SCHEDULE = [
  { week: "6–8 weeks", vaccine: "DHPPi (1st dose)", note: "First shield goes up" },
  {
    week: "10–12 weeks",
    vaccine: "DHPPi (2nd dose) + Leptospirosis",
    note: "Boosting the core protection",
  },
  {
    week: "14–16 weeks",
    vaccine: "DHPPi (3rd dose) + Anti-Rabies",
    note: "Rabies protection locked in",
  },
  { week: "12–16 weeks", vaccine: "Deworming", note: "Internal parasite clearance" },
  { week: "6 months", vaccine: "Kennel Cough (optional)", note: "If socialising or boarding" },
  { week: "1 year", vaccine: "Annual boosters", note: "Reminders sent by us" },
];

const KITTEN_SCHEDULE = [
  { week: "6–8 weeks", vaccine: "FVRCP / Tricat (1st dose)", note: "First protection layer" },
  { week: "10–12 weeks", vaccine: "FVRCP (2nd dose)", note: "Booster for full immunity" },
  { week: "14–16 weeks", vaccine: "Anti-Rabies", note: "Legal & essential" },
  { week: "12–16 weeks", vaccine: "Deworming", note: "Internal parasite clearance" },
  { week: "1 year", vaccine: "Annual boosters", note: "Reminders sent by us" },
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
    icon: "💀",
    title: "Parvovirus can kill in 48–72 hours",
    desc: "One of the deadliest puppy diseases. Unvaccinated puppies have a very low survival rate. There is no cure — only prevention.",
    tag: "Puppies especially",
  },
  {
    icon: "🧠",
    title: "Distemper causes permanent nerve damage",
    desc: "Even if a dog survives distemper, many have lifelong neurological issues — seizures, twitching, inability to walk properly.",
    tag: "Dogs & cats",
  },
  {
    icon: "⚠️",
    title: "Wrong interval = vaccine doesn't work",
    desc: "The 2nd dose of DHPPi must come 3–4 weeks after the first. Too early or too late and immunity doesn't form properly. Most pet parents don't know this.",
    tag: "All pets",
  },
  {
    icon: "📅",
    title: "Missing one dose restarts the schedule",
    desc: "If your puppy misses the 2nd dose window, the vet may need to restart from scratch. The partial immunity from dose 1 fades.",
    tag: "All pets",
  },
];

const REVIEWS = [
  {
    name: "Aryan T.",
    pet: "Puppy parent · Noida",
    stars: 5,
    text: "Got the package for my Lab pup. The app literally sends reminders before every visit so I've never missed a dose. Zero stress.",
  },
  {
    name: "Shreya P.",
    pet: "Kitten parent · Gurugram",
    stars: 5,
    text: "My kitten was terrified at clinics but the vet at the SnoutiQ-verified clinic was so patient. Done in 10 minutes. Highly recommend.",
  },
  {
    name: "Kabir S.",
    pet: "Puppy parent · Delhi",
    stars: 5,
    text: "The vet explained each vaccine and what it protects against. I finally understand what my dog actually needs. Worth every rupee.",
  },
];

// ─── Components ───────────────────────────────────────────────────────────────
function FaqItem({ q, a }) {
  const [open, setOpen] = useState(false);
  return (
    <div
      className={`rounded-2xl border overflow-hidden transition-all ${
        open ? "border-brand/30" : "border-slate-200"
      }`}
    >
      <button
        type="button"
        onClick={() => setOpen(!open)}
        className="w-full flex items-center justify-between gap-4 px-5 py-4 text-left"
      >
        <span className="font-semibold text-slate-900 text-sm leading-snug">{q}</span>
        <ChevronDown
          className={`h-4 w-4 text-brand shrink-0 transition-transform ${
            open ? "rotate-180" : ""
          }`}
        />
      </button>
      {open && (
        <p className="px-5 pb-4 text-sm text-slate-500 leading-relaxed border-t border-slate-100 pt-3 bg-brand-light/20">
          {a}
        </p>
      )}
    </div>
  );
}

function ScheduleRow({ week, vaccine, note, i }) {
  return (
    <div
      className={`flex gap-4 py-3.5 px-4 rounded-xl ${
        i % 2 === 0 ? "bg-slate-50" : "bg-white"
      } border border-slate-100`}
    >
      <div className="shrink-0 w-24 text-xs font-bold text-brand">{week}</div>
      <div className="flex-1">
        <p className="text-sm font-semibold text-slate-900">{vaccine}</p>
        <p className="text-xs text-slate-500 mt-0.5">{note}</p>
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
      <span className="absolute -top-3 left-4 bg-accent text-white text-[10px] font-extrabold px-3 py-1 rounded-full shadow">
        ₹1,000 OFF
      </span>

      <div className="flex items-start justify-between gap-2 mt-1">
        <div>
          <p className="font-extrabold text-slate-900 text-base mb-0.5">
            {type === "puppy" ? "🐶 Puppy Package" : "🐱 Kitten Package"}
          </p>
          <p className="text-xs text-slate-500">{tagline}</p>
        </div>
        <div className="text-right shrink-0">
          <p className="text-sm text-slate-400 line-through">{mrp}</p>
          <p className="text-2xl font-extrabold text-brand">{price}</p>
        </div>
      </div>

      {selected && (
        <div className="mt-3 pt-3 border-t border-brand/20 flex items-center gap-1.5">
          <CheckCircle2 className="h-4 w-4 text-brand shrink-0" />
          <p className="text-xs font-semibold text-brand">Selected — full schedule included</p>
        </div>
      )}
    </button>
  );
}

// ─── Main ─────────────────────────────────────────────────────────────────────
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

  const price = petType === "kitten" ? "₹4,500" : "₹7,000";
  const mrp = petType === "kitten" ? "₹5,500" : "₹8,000";

  const step1Valid = Boolean(petType && petName.trim() && petAge && area);
  const step2Valid = Boolean(ownerName.trim() && phone.trim().length >= 10);

  return (
    <div className="min-h-screen bg-white flex flex-col">
      <LPNavbar />

      {/* ── URGENCY STRIP ─────────────────────────────────────────────────── */}
      <div className="bg-accent text-white text-center py-2 px-4">
        <p className="text-xs font-bold flex items-center justify-center gap-2">
          <span>🎉</span> Limited time: ₹1,000 off on all vaccination packages · Delhi NCR only{" "}
          <span>🎉</span>
        </p>
      </div>

      <main className="flex-1 pb-24 md:pb-0">
        {/* ── HERO ─────────────────────────────────────────────────────────── */}
        <section className="bg-gradient-to-br from-brand-light/50 via-white to-white px-4 pt-8 pb-12">
          <div className="max-w-xl mx-auto">
            <div className="text-center mb-8">
              <span className="inline-flex items-center gap-1.5 text-xs font-bold text-brand bg-brand-light border border-brand/20 px-3 py-1 rounded-full mb-4">
                <MapPin className="h-3 w-3" /> Delhi NCR · 50+ Verified Clinics
              </span>
              <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight mb-3">
                Your new pet needs the <span className="text-brand">right vaccines</span>, at the{" "}
                <span className="text-brand">right time</span>
              </h1>
              <p className="text-slate-500 text-base leading-relaxed max-w-sm mx-auto">
                Miss a dose, get the timing wrong, or skip a vaccine — and the protection doesn&apos;t
                work. We handle the entire first-year schedule so you don&apos;t have to guess.
              </p>
            </div>

            {/* ── BOOKING FORM ─────────────────────────────────────────────── */}
            {!submitted ? (
              <div
                id="booking-form"
                className="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/50 p-6"
              >
                {step === 1 && (
                  <div className="space-y-5">
                    <p className="text-sm font-extrabold text-slate-900 text-center">
                      Start with your pet 👇
                    </p>

                    <div className="space-y-3">
                      <PriceCard
                        type="puppy"
                        mrp="₹8,000"
                        price="₹7,000"
                        tagline="Full year · All visits · DHPPi + Rabies + Deworming"
                        selected={petType === "puppy"}
                        onClick={() => setPetType("puppy")}
                      />
                      <PriceCard
                        type="kitten"
                        mrp="₹5,500"
                        price="₹4,500"
                        tagline="Full year · All visits · FVRCP + Rabies + Deworming"
                        selected={petType === "kitten"}
                        onClick={() => setPetType("kitten")}
                      />
                    </div>

                    {petType ? (
                      <>
                        <div>
                          <label className="block text-sm font-semibold text-slate-900 mb-2">
                            {petType === "puppy" ? "Puppy's" : "Kitten's"} name
                          </label>
                          <input
                            type="text"
                            value={petName}
                            onChange={(e) => setPetName(e.target.value)}
                            placeholder={`e.g. ${petType === "puppy" ? "Bruno" : "Luna"}`}
                            className="w-full border border-slate-200 rounded-xl px-4 py-3 text-base text-slate-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                          />
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-900 mb-2">
                            How old is{" "}
                            {petName || (petType === "puppy" ? "your pup" : "your kitten")}?
                          </label>
                          <div className="grid grid-cols-3 gap-2">
                            {[
                              "Under 6 weeks",
                              "6–8 weeks",
                              "2–4 months",
                              "4–6 months",
                              "6–12 months",
                              "Over 1 year",
                            ].map((a) => (
                              <button
                                key={a}
                                type="button"
                                onClick={() => setPetAge(a)}
                                className={`py-2.5 rounded-xl text-xs font-semibold border transition-all ${
                                  petAge === a
                                    ? "border-brand bg-brand-light text-brand"
                                    : "border-slate-200 text-slate-600 hover:border-brand/40"
                                }`}
                              >
                                {a}
                              </button>
                            ))}
                          </div>

                          {petAge === "Under 6 weeks" && (
                            <p className="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                              ⚠️ Too young for vaccines yet — most start at 6–8 weeks. Book now and
                              we&apos;ll schedule the first visit when {petName || "your pet"} is ready.
                            </p>
                          )}
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-900 mb-2">
                            Your area in Delhi NCR
                          </label>
                          <select
                            value={area}
                            onChange={(e) => setArea(e.target.value)}
                            className="w-full border border-slate-200 rounded-xl px-4 py-3 text-base text-slate-900 bg-white focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                          >
                            <option value="">Select your area...</option>
                            {AREAS.map((a) => (
                              <option key={a} value={a}>
                                {a}
                              </option>
                            ))}
                          </select>
                        </div>

                        <button
                          type="button"
                          disabled={!step1Valid}
                          onClick={() => setStep(2)}
                          className="w-full flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-extrabold text-base py-4 rounded-2xl transition-colors shadow-md shadow-orange-100"
                        >
                          Book Now — Save ₹1,000 <ArrowRight className="h-4 w-4" />
                        </button>

                        <p className="text-xs text-slate-400 text-center">
                          Free callback · No payment now · We confirm the clinic &amp; date
                        </p>
                      </>
                    ) : (
                      <p className="text-xs text-slate-400 text-center py-2">
                        👆 Select a package above to continue
                      </p>
                    )}
                  </div>
                )}

                {step === 2 && (
                  <div className="space-y-5">
                    <div className="bg-slate-50 rounded-2xl p-4 text-sm space-y-1.5 border border-slate-100">
                      <p className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Booking summary
                      </p>
                      <div className="flex justify-between">
                        <span className="text-slate-500">Pet</span>
                        <span className="font-semibold text-slate-900">
                          {petName} · {petType} · {petAge}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-slate-500">Area</span>
                        <span className="font-semibold text-slate-900">{area}</span>
                      </div>

                      <div className="flex justify-between pt-1 border-t border-slate-200 mt-1">
                        <span className="text-slate-500">Package price</span>
                        <div className="text-right">
                          <div className="flex items-center gap-2">
                            <span className="text-slate-400 line-through text-sm">{mrp}</span>
                            <span className="font-extrabold text-brand text-lg">{price}</span>
                          </div>
                          <p className="text-[10px] text-accent font-bold">
                            ₹1,000 discount applied ✓
                          </p>
                        </div>
                      </div>
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-slate-900 mb-2">
                        Your name
                      </label>
                      <input
                        type="text"
                        value={ownerName}
                        onChange={(e) => setOwnerName(e.target.value)}
                        placeholder="Full name"
                        className="w-full border border-slate-200 rounded-xl px-4 py-3 text-base text-slate-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-slate-900 mb-2">
                        Mobile number
                      </label>
                      <div className="relative">
                        <span className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-base font-medium">
                          +91
                        </span>
                        <input
                          type="tel"
                          value={phone}
                          onChange={(e) =>
                            setPhone(e.target.value.replace(/\D/g, "").slice(0, 10))
                          }
                          placeholder="98765 43210"
                          className="w-full border border-slate-200 rounded-xl pl-14 pr-4 py-3 text-base text-slate-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
                        />
                      </div>
                    </div>

                    <div className="flex gap-3">
                      <button
                        type="button"
                        onClick={() => setStep(1)}
                        className="px-5 py-3.5 rounded-2xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50"
                      >
                        ← Back
                      </button>

                      <button
                        type="button"
                        disabled={!step2Valid}
                        onClick={() => setSubmitted(true)}
                        className="flex-1 flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-extrabold text-base py-3.5 rounded-2xl transition-colors"
                      >
                        Confirm Booking <ArrowRight className="h-4 w-4" />
                      </button>
                    </div>

                    <p className="text-xs text-slate-400 text-center">
                      🔒 No payment now. Our team calls to confirm clinic &amp; first visit date.
                    </p>
                  </div>
                )}
              </div>
            ) : (
              <div className="bg-white rounded-3xl border border-slate-200 shadow-xl p-8 text-center">
                <div className="h-16 w-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
                  <CheckCircle2 className="h-8 w-8 text-green-600" />
                </div>
                <h3 className="text-2xl font-extrabold text-slate-900 mb-2">
                  Booking received! 🎉
                </h3>
                <p className="text-slate-500 text-sm mb-6">
                  Our team will call +91 {phone} within 2 hours to confirm the nearest clinic and
                  first visit date for {petName}.
                </p>

                <div className="bg-brand-light rounded-2xl p-4 text-left mb-5">
                  <p className="text-xs font-bold text-brand mb-2">What happens next:</p>
                  {[
                    "Team calls to confirm clinic near you",
                    "First visit scheduled within your availability",
                    "WhatsApp reminders before every subsequent visit",
                    "Digital vaccination record maintained in app",
                  ].map((s, i) => (
                    <p key={i} className="text-xs text-slate-700 flex items-start gap-2 mb-1.5">
                      <span className="text-brand font-bold mt-0.5">✓</span>
                      {s}
                    </p>
                  ))}
                </div>

                <a
                  href="https://wa.me/919999999999"
                  className="block text-sm text-brand font-semibold hover:underline"
                >
                  Or WhatsApp us if you need anything
                </a>
              </div>
            )}

            <div className="flex items-center justify-center gap-6 mt-5 text-xs text-slate-400">
              <span className="flex items-center gap-1">
                <ShieldCheck className="h-3.5 w-3.5 text-brand" /> Verified clinics
              </span>
              <span className="flex items-center gap-1">
                <Star className="h-3.5 w-3.5 text-brand" /> 4.8★ rated
              </span>
              <span className="flex items-center gap-1">
                <Clock className="h-3.5 w-3.5 text-brand" /> Reminders included
              </span>
            </div>
          </div>
        </section>

        {/* ── TRUST BAR ─────────────────────────────────────────────────────── */}
        <section className="bg-brand py-8 px-4">
          <div className="max-w-xl mx-auto grid grid-cols-2 sm:grid-cols-4 gap-5 text-center">
            {[
              { v: "5,000+", l: "Pets Vaccinated" },
              { v: "50+", l: "Verified Clinics" },
              { v: "4.8/5", l: "Average Rating" },
              { v: "100%", l: "Schedule Tracked" },
            ].map((s) => (
              <div key={s.l}>
                <p className="text-2xl font-extrabold text-white">{s.v}</p>
                <p className="text-blue-200 text-xs mt-0.5">{s.l}</p>
              </div>
            ))}
          </div>
        </section>

        {/* ── THE RISK SECTION ──────────────────────────────────────────────── */}
        <section className="py-14 px-4 bg-white">
          <div className="max-w-xl mx-auto">
            <div className="text-center mb-8">
              <span className="inline-block bg-red-50 border border-red-200 text-red-700 text-xs font-bold px-3 py-1 rounded-full mb-3">
                ⚠️ Read this first
              </span>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900">
                What happens when vaccination goes wrong
              </h2>
              <p className="text-slate-500 text-sm mt-2 max-w-sm mx-auto">
                Most pet parents think any vaccine = protection. It doesn&apos;t. The schedule and
                timing matter as much as the vaccine itself.
              </p>
            </div>

            <div className="space-y-4">
              {RISK_ITEMS.map((item, i) => (
                <div key={i} className="flex gap-4 p-5 rounded-2xl bg-red-50 border border-red-100">
                  <span className="text-2xl shrink-0">{item.icon}</span>
                  <div>
                    <div className="flex items-center gap-2 mb-1 flex-wrap">
                      <p className="font-extrabold text-slate-900 text-sm">{item.title}</p>
                      <span className="text-[10px] bg-red-100 text-red-700 font-bold px-2 py-0.5 rounded-full">
                        {item.tag}
                      </span>
                    </div>
                    <p className="text-slate-600 text-xs leading-relaxed">{item.desc}</p>
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-6 p-5 rounded-2xl bg-brand-light border border-brand/20 text-center">
              <p className="text-brand font-extrabold text-sm mb-1">
                This is why we track the entire schedule for you.
              </p>
              <p className="text-slate-600 text-xs">
                You&apos;ll get reminders before every visit. No missed doses. No guessing.
              </p>
            </div>
          </div>
        </section>

        {/* ── SCHEDULE ──────────────────────────────────────────────────────── */}
        <section className="py-14 px-4 bg-white">
          <div className="max-w-xl mx-auto">
            <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-2">
              The full vaccination schedule
            </h2>

            <p className="text-slate-500 text-sm text-center mb-6">
              This is what{" "}
              <span className="font-semibold text-slate-700">
                {petType === "puppy" ? "your puppy" : petType === "kitten" ? "your kitten" : "your pet"}
              </span>{" "}
              needs over the first year. We track every step.
            </p>

            <div className="flex rounded-xl border border-slate-200 p-1 bg-slate-50 mb-5 gap-1">
              {["puppy", "kitten"].map((t) => (
                <button
                  key={t}
                  type="button"
                  onClick={() => setPetType(t)}
                  className={`flex-1 py-2 rounded-lg text-sm font-semibold transition-all ${
                    petType === t
                      ? "bg-white text-brand shadow-sm border border-slate-200"
                      : "text-slate-500 hover:text-slate-700"
                  }`}
                >
                  {t === "puppy" ? "🐶 Puppy" : "🐱 Kitten"}
                </button>
              ))}
            </div>

            <div className="space-y-2">
              {schedule.map((row, i) => (
                <ScheduleRow key={i} {...row} i={i} />
              ))}
            </div>

            <p className="text-xs text-slate-400 mt-4 text-center">
              Exact schedule finalised by the vet at first visit based on{" "}
              {petType === "kitten" ? "kitten's" : "puppy's"} current age and health.
            </p>
          </div>
        </section>

        {/* ── REVIEWS ───────────────────────────────────────────────────────── */}
        <section className="py-14 px-4 bg-slate-900 relative overflow-hidden">
          <div className="pointer-events-none absolute inset-0">
            <div className="absolute top-0 left-0 h-48 w-48 rounded-full bg-brand/10 -translate-x-1/2 -translate-y-1/2 blur-3xl" />
          </div>

          <div className="relative max-w-xl mx-auto">
            <p className="text-xs font-bold text-blue-400 text-center mb-2 tracking-widest">
              REAL PET PARENTS
            </p>
            <h2 className="text-2xl font-extrabold text-white text-center mb-8">
              They trusted us with their pet&apos;s first year
            </h2>

            <div className="space-y-4">
              {REVIEWS.map((r, i) => (
                <div key={i} className="bg-white/5 border border-white/10 rounded-2xl p-5">
                  <div className="flex gap-0.5 mb-3">
                    {Array(r.stars)
                      .fill(0)
                      .map((_, j) => (
                        <span key={j} className="text-yellow-400 text-sm">
                          ★
                        </span>
                      ))}
                  </div>

                  <p className="text-slate-300 text-sm leading-relaxed mb-4">&quot;{r.text}&quot;</p>

                  <div className="flex items-center gap-2.5">
                    <div className="h-8 w-8 rounded-full bg-brand/30 flex items-center justify-center text-brand font-bold text-xs">
                      {r.name
                        .split(" ")
                        .map((n) => n[0])
                        .join("")}
                    </div>
                    <div>
                      <p className="font-bold text-white text-xs">{r.name}</p>
                      <p className="text-slate-500 text-xs">{r.pet}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* ── FAQ ───────────────────────────────────────────────────────────── */}
        <section className="py-14 px-4 bg-white">
          <div className="max-w-xl mx-auto">
            <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-8">
              Got questions?
            </h2>
            <div className="space-y-3">
              {[
                {
                  q: "What if my puppy has already had some vaccines elsewhere?",
                  a: "No problem. Tell us the vaccines already given and we'll pick up from where the current schedule stands — no need to restart from scratch.",
                },
                {
                  q: "Is the price all-inclusive or per visit?",
                  a: "The full package price (₹7,000 for puppies, ₹4,500 for kittens) covers the entire first-year schedule — all visits, all vaccines, all reminders. No per-visit charges.",
                },
                {
                  q: "Do you come home or is it clinic-only?",
                  a: "Clinic-only. Vaccines need to be stored in cold-chain conditions and administered in a clinical setting. Home vaccination compromises the vaccine quality — any service offering it should be a red flag.",
                },
                {
                  q: "My kitten is older than 4 months — is the package still valid?",
                  a: "Yes. The vet will assess the current vaccination status and adjust the schedule. Older kittens may need fewer doses, and the price stays the same.",
                },
                {
                  q: "How do the appointment reminders work?",
                  a: "After each visit, we'll send you a WhatsApp message with the date of the next visit. No app download needed. Simple SMS/WhatsApp reminders.",
                },
                {
                  q: "What if my pet reacts to a vaccine?",
                  a: "Mild reactions like lethargy or low appetite for 24 hours are normal. Our vets guide you on what to watch for after each visit. If there's a concerning reaction, our support team is reachable directly via WhatsApp.",
                },
              ].map((f, i) => (
                <FaqItem key={i} q={f.q} a={f.a} />
              ))}
            </div>
          </div>
        </section>

        {/* ── FINAL CTA ─────────────────────────────────────────────────────── */}
        <section className="py-14 px-4 bg-brand relative overflow-hidden text-center">
          <div className="pointer-events-none absolute inset-0">
            <div className="absolute -top-10 -right-10 h-48 w-48 rounded-full bg-white/5 blur-2xl" />
          </div>

          <div className="relative max-w-sm mx-auto">
            <p className="text-white/80 text-xs font-bold mb-2 tracking-wider">⏳ LIMITED OFFER</p>
            <h2 className="text-2xl font-extrabold text-white mb-2">
              Start {petName ? `${petName}'s` : "your pet's"} protection today
            </h2>
            <p className="text-blue-200 text-sm mb-2">₹1,000 off — Delhi NCR only · No payment now</p>

            <div className="flex items-center justify-center gap-3 mb-7">
              <span className="text-white/60 line-through text-lg">{mrp}</span>
              <span className="text-3xl font-extrabold text-white">{price}</span>
              <span className="bg-accent text-white text-xs font-bold px-2 py-1 rounded-full">
                SAVE ₹1,000
              </span>
            </div>

            <button
              type="button"
              onClick={() => {
                window.scrollTo({ top: 0, behavior: "smooth" });
                setStep(1);
                setSubmitted(false);
              }}
              className="w-full bg-accent hover:bg-accent-hover text-white font-extrabold text-lg py-4 rounded-2xl shadow-lg shadow-orange-900/30 transition-colors"
            >
              Book Package — Free Callback
            </button>
            <p className="text-blue-300 text-xs mt-3">
              We call to confirm clinic + first visit date. No spam.
            </p>
          </div>
        </section>
      </main>

      {/* ── FOOTER ─────────────────────────────────────────────────────────── */}
      <footer className="bg-white border-t border-slate-100 py-5 px-4 pb-28 md:pb-5">
        <p className="text-xs text-slate-400 text-center">
          © {new Date().getFullYear()} SnoutiQ. All rights reserved. ·{" "}
          <Link to="/privacy-policy" className="hover:text-brand">
            Privacy Policy
          </Link>
        </p>
      </footer>

      {/* ── STICKY MOBILE BAR ─────────────────────────────────────────────── */}
      <div className="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white/98 backdrop-blur-sm border-t border-slate-200 px-4 py-3 shadow-2xl">
        <div className="flex gap-3 items-center">
          <a
            href="https://wa.me/919999999999?text=Hi%2C%20I%20want%20to%20book%20a%20vaccination%20package%20for%20my%20pet"
            className="flex items-center justify-center gap-1.5 border-2 border-[#25D366] text-[#25D366] font-bold py-3 px-3.5 rounded-xl text-xs shrink-0"
          >
            <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
            Chat
          </a>

          <button
            type="button"
            onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
            className="flex-1 bg-accent hover:bg-accent-hover text-white font-extrabold py-3 rounded-xl text-sm transition-colors"
          >
            Book Now — Save ₹1,000 · {price}
          </button>
        </div>
      </div>
    </div>
  );
}