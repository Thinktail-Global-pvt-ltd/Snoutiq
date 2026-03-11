import React, {
  Suspense,
  lazy,
  memo,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import { Helmet } from "react-helmet-async";
import { useLocation, useNavigate } from "react-router-dom";
import { LPNavbar } from "../newflow/LPNavbar";
import { apiBaseUrl } from "../lib/api";
import {
  ArrowRight,
  ChevronDown,
  Star,
  Zap,
  PawPrint,
  AlertCircle,
  Globe,
} from "lucide-react";

// --- SEO constants -----------------------------------------------------------
const TITLE =
  "Pet Doctor Online India | 24/7 Online Vet Consultation";
const DESCRIPTION =
  "Need urgent pet advice? Connect with a licensed pet doctor online via Video call. Fast response. Transparent pricing. Available across India.";
const CANONICAL = "https://www.snoutiq.com/pet-doctor-online";
const KEYWORDS = [
  "online vet consultation",
  "talk to vet online",
  "online veterinarian",
  "consult vet online",
  "online pet consultation",
  "best online vet consultation india",
  "vet online",
  "consult vet online india",
  "talk to vet",
].join(", ");

const CONSULT_DETAILS_ROUTE = "/20+vetsonline?start=details";
const VIDEO_CONSULT_BASE_ROUTE = "/pet-doctor-online";
const DOCTOR_LIST_ENDPOINT = "/api/exported_from_excell_doctors";

const getAssetRoot = () => {
  const base = apiBaseUrl().replace(/\/+$/, "");
  if (base.endsWith("/backend")) return base.slice(0, -"/backend".length);
  return "https://snoutiq.com";
};

const normalizeDoctorImageUrl = (rawUrl, assetRoot) => {
  if (!rawUrl) return "";
  let url = String(rawUrl).trim();
  if (!url) return "";
  if (url.startsWith("http")) {
    return url.replace(
      "https://snoutiq.com/https://snoutiq.com",
      "https://snoutiq.com"
    );
  }
  if (url.startsWith("/")) url = url.slice(1);
  return `${assetRoot}/${url}`;
};

const resolveDoctorImage = (doctor, assetRoot) => {
  const blob = doctor?.doctor_image_blob_url;
  const preferred = doctor?.doctor_image_url || doctor?.doctor_image;
  return normalizeDoctorImageUrl(blob, assetRoot) || normalizeDoctorImageUrl(preferred, assetRoot);
};

const getInitials = (value) => {
  const text = String(value || "").trim();
  if (!text) return "DR";
  const parts = text.split(/\s+/).filter(Boolean);
  const letters = parts.slice(0, 2).map((p) => p[0]);
  return letters.join("").toUpperCase();
};

const normalizeNameKey = (value = "") =>
  String(value || "")
    .toLowerCase()
    .replace(/[^a-z]/g, "");

const isDrShashankVet = (value) => {
  const key = normalizeNameKey(value);
  if (!key) return false;
  return key.includes("shash") && key.includes("goyal");
};

// --- Helpers -----------------------------------------------------------------
const cn = (...v) => v.filter(Boolean).join(" ");

function getCurrentPrice() {
  const h = new Date().getHours();
  const isDay = h >= 8 && h < 22;
  return isDay
    ? { price: "₹499", label: "Day rate · 8AM-10PM", rateType: "day" }
    : { price: "₹599", label: "Night rate · 10PM-8AM", rateType: "night" };
}

const PAYMENT_AMOUNTS = {
  day: 499,
  night: 599,
};

const formatInr = (value) => {
  const n = Number(value);
  if (!Number.isFinite(n)) return "0";
  return n.toLocaleString("en-IN", {
    minimumFractionDigits: Number.isInteger(n) ? 0 : 2,
    maximumFractionDigits: 2,
  });
};

const normalizeDisplayText = (value) => {
  if (value === undefined || value === null) return "";
  const text = String(value).trim();
  if (!text) return "";
  const lower = text.toLowerCase();
  if (lower === "null" || lower === "undefined" || lower === "[]" || lower === "na" || lower === "n/a") return "";
  return text;
};

const listToDisplayText = (value) => {
  if (Array.isArray(value)) return value.map((i) => normalizeDisplayText(i)).filter(Boolean).join(", ");
  const text = normalizeDisplayText(value);
  if (!text) return "";
  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) return parsed.map((i) => normalizeDisplayText(i)).filter(Boolean).join(", ");
    } catch {
      return text.replace(/^\[|\]$/g, "").replace(/["']/g, "").trim();
    }
  }
  return text;
};

// --- Pet issue quick-select options --
// const PET_ISSUE_OPTIONS = [
//   { label: "Vomiting", emoji: "ðŸ¤¢" },
//   { label: "Not eating", emoji: "ðŸ½ï¸" },
//   { label: "Skin itching", emoji: "ðŸ¾" },
//   { label: "Injury", emoji: "ðŸ©¹" },
//   { label: "Diarrhea", emoji: "ðŸ’§" },
//   { label: "Other", emoji: "â“" },
// ];

const HERO_REVIEW_CARDS = [
  {
    name: "Priya M.",
    text: "I needed an actual doctor to look at my dog, not just Google. Got connected to a proper pet doctor online within 10 minutes. She knew exactly what was wrong.",
  },
  {
    name: "Rahul S.",
    text: "The pet doctor online was more thorough than some clinic visits I've had. Took his time, asked the right questions, and gave a clear plan. Very professional.",
  },
  {
    name: "Ananya K.",
    text: "₹399 to see a qualified dog doctor online is unbeatable. My Labrador had a skin issue — the vet assessed it on video and I knew what to do within 20 minutes.",
  },
];

const SOCIAL_PROOF_STATS = [
  { v: "200+", l: "Consultations done" },
  { v: "4.8 ★", l: "Average rating" },
  { v: "< 15 min", l: "Avg. wait time" },
];

const HOW_IT_WORKS_STEPS = [
  {
    n: "01",
    title: "Describe your pet's symptoms",
    desc: "Select what's wrong. Takes 30 seconds.",
  },
  {
    n: "02",
    title: "Pay securely (₹399 / ₹499)",
    desc: "UPI, card or netbanking. No hidden charges.",
    extra: "No subscription. No recurring fee.",
  },
  {
    n: "03",
    title: "A pet doctor joins the video call",
    desc: "A licensed, qualified vet connects within 15 minutes to assess your pet properly.",
  },
  {
    n: "04",
    title: "Get a proper care plan",
    desc: "The pet doctor walks you through exactly what your pet needs. Follow-up included.",
  },
];

const INDIA_CITIES = [
  "Delhi NCR",
  "Mumbai",
  "Bangalore",
  "Hyderabad",
  "Chennai",
  "Pune",
  "Kolkata",
  "Jaipur",
  "Lucknow",
];

const FAQ_ITEMS = [
  {
    q: "Is an online pet doctor as qualified as a clinic vet?",
    a: "Yes — all our vets are licensed (BVSc / MVSc) with hands-on experience. For most non-emergency issues they can give you a thorough assessment on video. If your pet needs a physical examination, the vet will tell you and refer you to a clinic.",
  },
  {
    q: "How much does it cost?",
    a: "Day consultation starts at ₹399, and night emergency (10pm-8am) is ₹499. No hidden fees.",
  },
  {
    q: "How quickly will the vet connect?",
    a: "Usually within 15 minutes of booking. You'll get a WhatsApp confirmation immediately after payment with the vet's details.",
  },
  {
    q: "What happens after the consultation?",
    a: "The vet shares a complete care plan covering what to do next, what to watch out for, and follow-up if needed — all included in the ₹399 fee.",
  },
  {
    q: "What animals can I consult for?",
    a: "Dogs, cats, and most small animals. If you're unsure, book and the vet will let you know if they need to refer you.",
  },
];

const BELOW_THE_FOLD_SECTION_STYLE = {
  contentVisibility: "auto",
  containIntrinsicSize: "1px 720px",
};

const fieldBase =
  "w-full rounded-lg border border-gray-200 bg-white p-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand hover:border-gray-300 disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed md:rounded-xl md:p-3 md:text-sm";
const textareaBase = `${fieldBase} resize-none min-h-[100px]`;

const LazyPaymentScreen = lazy(() =>
  import("../screen/Paymentscreen").then((module) => ({
    default: module.PaymentScreen,
  })),
);

const LazyConfirmationScreen = lazy(() =>
  import("../screen/Paymentscreen").then((module) => ({
    default: module.ConfirmationScreen,
  })),
);

// â”€â”€ NEW: compact CTA label for "Get Started" button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function GetStartedCtaLabel({ amount }) {
  const originalAmount = Number(amount) || 0;
  const discounted = Math.max(originalAmount - 100, 0);

  return (
    <span className="inline-flex items-center justify-center gap-2 flex-wrap text-center leading-tight">
      <Zap className="h-4 w-4 shrink-0 text-current" />

      <span className="font-extrabold text-white">
         Consult a Pet Doctor Now 
      </span>

      <ArrowRight className="h-4 w-4 shrink-0 text-white/80" />

      {/* Main Price First */}
      <span className="whitespace-nowrap rounded-full bg-white px-3 py-0.5 text-sm font-black text-orange-600 shadow-sm">
       ₹{formatInr(discounted)}
      </span>

      {/* Original Price Cut */}
      <span className="whitespace-nowrap text-lg font-bold text-white/80 line-through">
        ₹{formatInr(originalAmount)}
      </span>
    </span>
  );
}

function DynamicPriceStrip({ currentPrice, originalPrice, rateType }) {
  return (
    <div className="overflow-hidden rounded-[18px] border border-[#f06a2f] bg-[#ece1db] shadow-[0_10px_30px_rgba(15,23,42,0.08)]">
      <div className="flex items-start gap-3 px-4 py-3 sm:items-center sm:px-5">
        <div className="shrink-0 pr-3 sm:pr-4">
          <div className="text-[2rem] leading-none font-medium text-[#f06a2f] sm:text-[2.35rem]">
            ₹{formatInr(currentPrice)}
          </div>
        </div>

        <div className="mt-1 h-14 w-px shrink-0 bg-[#d6b8aa] sm:mt-0 sm:h-12" />

        <div className="min-w-0 flex-1">
          <div className="block">
            <strong className="block text-[15px] font-extrabold leading-[1.2] text-slate-900">
              Flat fee. Everything included.
            </strong>

            <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
              <span className="text-sm font-semibold text-slate-400 line-through">
                ₹{formatInr(originalPrice)}
              </span>

              <span
                className={cn(
                  "inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.12em]",
                  rateType === "night"
                    ? "bg-slate-900 text-white"
                    : "bg-orange-100 text-orange-700"
                )}
              >
                {rateType === "night" ? "Night" : "Day"}
              </span>
            </div>
          </div>

          <div className="mt-2 text-sm text-slate-700">
            <p className="leading-5 sm:hidden">
              <span className="block">Video call with a pet doctor </span>
              <span className="block">· Expert advice · Follow-up</span>
            </p>

            <p className="hidden leading-6 sm:block">
              Video call with a pet doctor · Expert advice · Follow-up
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

function ConsultCtaLabel({ amount, prefixText = "Consult Now" }) {
  const originalAmount = Number(amount) || 0;
  const discounted = Math.max(originalAmount - 100, 0);

  return (
    <span className="inline-flex items-center justify-center gap-2 flex-wrap text-center leading-tight">
      <Zap className="h-4 w-4 shrink-0 text-current" />

      <span className="font-extrabold text-white group-disabled:text-slate-400">
        {prefixText}
      </span>

      {/* Main price first */}
      <span className="whitespace-nowrap rounded-full bg-white px-3 py-1 text-sm font-black text-orange-600 shadow-sm">
        ₹{formatInr(discounted)}
      </span>

      {/* Original price cut */}
      <span className="whitespace-nowrap text-sm font-bold text-white/60 line-through">
        ₹{formatInr(originalAmount)}
      </span>
    </span>
  );
}

const PaymentRouteFallback = memo(function PaymentRouteFallback() {
  return (
    <div className="min-h-screen bg-white flex items-center justify-center px-4 py-12">
      <div className="h-12 w-12 rounded-full border-4 border-slate-200 border-t-brand animate-spin" />
    </div>
  );
});

const SocialProofSection = memo(function SocialProofSection() {
  return (
    <section
      className="bg-slate-900 py-7 px-4"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <div className="max-w-6xl mx-auto grid grid-cols-3 gap-4 text-center">
        {SOCIAL_PROOF_STATS.map((stat) => (
          <div key={stat.l}>
            <p className="text-xl sm:text-2xl font-extrabold text-white">
              {stat.v}
            </p>
            <p className="text-slate-400 text-xs mt-0.5">{stat.l}</p>
          </div>
        ))}
      </div>
    </section>
  );
});

const HowItWorksSection = memo(function HowItWorksSection() {
  return (
    <section
      id="how-it-works"
      className="py-14 px-4 bg-white scroll-mt-24"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <div className="max-w-4xl mx-auto">
        <p className="text-xs font-extrabold text-brand text-center tracking-widest mb-2">
          HOW IT WORKS
        </p>
        <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-8">
          Talking to a vet takes 2 minutes to set up
        </h2>
        <div className="relative space-y-3">
          <div className="absolute left-[22px] top-10 bottom-10 w-px bg-slate-100" />
          {HOW_IT_WORKS_STEPS.map((step) => (
            <div
              key={step.n}
              className="flex gap-4 bg-slate-50 rounded-2xl p-4 border border-slate-100 relative"
            >
              <div className="h-11 w-11 rounded-2xl bg-brand text-white flex items-center justify-center font-extrabold text-xs shrink-0 relative z-10">
                {step.n}
              </div>
              <div>
                <p className="font-extrabold text-slate-900 text-sm mb-1">
                  {step.title}
                </p>
                <p className="text-slate-500 text-xs leading-relaxed">
                  {step.desc}
                </p>
                {step.extra ? (
                  <p className="text-green-500 text-xs leading-relaxed">
                    {step.extra}
                  </p>
                ) : null}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
});

const FeaturedVetsSection = memo(function FeaturedVetsSection({
  consultAmount,
  featuredVets,
  sectionRef,
  showLoadingState,
  vetsError,
  onConsultClick,
}) {
  return (
    <section
      id="our-vets"
      ref={sectionRef}
      className="py-4 px-4 bg-white scroll-mt-24"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <div className="max-w-6xl mx-auto">
        <div className="flex items-end justify-between gap-4 flex-wrap mb-8">
          <div>
            <h2 className="text-2xl font-extrabold text-slate-900 mb-2 flex items-center gap-2">
              <PawPrint className="h-6 w-6 text-brand" />
              Our pet doctors
            </h2>
            <p className="text-slate-500 text-sm">
              Qualified vets for dogs, cats & pets
            </p>
          </div>
        </div>
        {showLoadingState ? (
          <div className="flex gap-4 overflow-x-auto pb-2 -mx-2 px-2 snap-x snap-mandatory md:grid md:grid-cols-2 lg:grid-cols-4 md:gap-5 md:overflow-visible md:mx-0 md:px-0">
            {Array.from({ length: 4 }).map((_, index) => (
              <div
                key={index}
                className="min-w-[240px] snap-start rounded-2xl border border-slate-200 overflow-hidden md:min-w-0"
              >
                <div className="h-40 bg-slate-100 animate-pulse" />
                <div className="p-4 space-y-2">
                  <div className="h-4 bg-slate-100 rounded animate-pulse" />
                  <div className="h-3 bg-slate-100 rounded w-4/5 animate-pulse" />
                </div>
              </div>
            ))}
          </div>
        ) : vetsError ? (
          <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            {vetsError}
          </div>
        ) : featuredVets.length === 0 ? (
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            No vets available right now. Please check back soon.
          </div>
        ) : (
          <div className="flex gap-4 overflow-x-auto pb-2 -mx-2 px-2 snap-x snap-mandatory md:grid md:grid-cols-2 lg:grid-cols-4 md:gap-5 md:overflow-visible md:mx-0 md:px-0">
            {featuredVets.map((vet) => (
              <div
                key={vet.id}
                className="min-w-[240px] snap-start rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm md:min-w-0"
              >
                <div className="h-40 bg-slate-100 relative">
                  {vet.image ? (
                    <img
                      src={vet.image}
                      alt={vet.name}
                      className="h-full w-full object-cover"
                      loading="lazy"
                      decoding="async"
                      fetchpriority="low"
                      width="320"
                      height="160"
                      sizes="(min-width: 1024px) 25vw, (min-width: 768px) 50vw, 240px"
                    />
                  ) : (
                    <div className="h-full w-full flex items-center justify-center bg-slate-200 text-slate-600 text-xl font-bold">
                      {getInitials(vet.name)}
                    </div>
                  )}
                  {vet.rating ? (
                    <span className="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-1 text-xs font-semibold text-slate-700 shadow">
                      <Star className="h-3.5 w-3.5 text-amber-400" />
                      {vet.rating}
                    </span>
                  ) : null}
                </div>
                <div className="p-4 space-y-2">
                  <div>
                    <p className="text-sm font-extrabold text-slate-900">
                      {vet.name}
                    </p>
                    <p className="text-xs text-slate-500">
                      {vet.degree || "Veterinary Doctor"}
                      {vet.experience ? ` · ${vet.experience} yrs` : ""}
                    </p>
                  </div>
                  {vet.specialization ? (
                    <p className="text-xs text-slate-600 line-clamp-2">
                      {listToDisplayText(vet.specialization)}
                    </p>
                  ) : null}
                  <div className="flex items-center justify-between text-xs text-slate-500">
                    <span>{vet.responseDay || "0-15 mins"}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
        <div className="mt-6 flex justify-center">
          <button
            type="button"
            onClick={onConsultClick}
            className="w-full max-w-2xl rounded-2xl bg-accent hover:bg-accent-hover text-white text-base font-extrabold py-4 shadow-lg shadow-orange-200/60 transition-all"
          >
            <GetStartedCtaLabel amount={consultAmount} />
          </button>
        </div>
      </div>
    </section>
  );
});

const ReviewsSection = memo(function ReviewsSection() {
  return (
    <section
      className="bg-slate-50 px-4 py-10"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <div className="mx-auto max-w-5xl">
        <h2 className="text-center text-2xl font-extrabold text-slate-900">
          Pet parent reviews
        </h2>
        <p className="mx-auto mt-2 max-w-2xl text-center text-sm text-slate-500">
          What pet parents say about talking to a vet online
        </p>
        <div className="mt-6 grid gap-4 md:grid-cols-3">
          {HERO_REVIEW_CARDS.map((review) => (
            <div
              key={review.name}
              className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
            >
              <div className="mb-3 flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-brand/10 text-sm font-black text-brand">
                  {review.name.charAt(0)}
                </div>
                <div>
                  <p className="text-sm font-bold text-slate-900">
                    {review.name}
                  </p>
                  <div className="flex items-center gap-0.5">
                    {Array.from({ length: 5 }).map((_, index) => (
                      <Star
                        key={index}
                        className="h-3.5 w-3.5 fill-amber-400 text-amber-400"
                      />
                    ))}
                  </div>
                </div>
              </div>
              <p className="text-sm leading-6 text-slate-600">{review.text}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
});

const AcrossIndiaSection = memo(function AcrossIndiaSection() {
  return (
    <section
      id="across-india"
      className="py-8 px-4 bg-slate-50 scroll-mt-24"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <div className="max-w-6xl mx-auto">
        <div className="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border border-emerald-100 p-6">
          <h2 className="text-xl font-extrabold text-slate-900 mb-1 flex items-center gap-2">
            <Globe size={20} className="text-brand" />
            Pet doctor online — available across India
          </h2>
          <div className="flex flex-wrap gap-2 mt-4">
            {INDIA_CITIES.map((city) => (
              <span
                key={city}
                className="inline-block border border-slate-300 rounded-full px-3 py-1 text-xs font-semibold text-slate-700 bg-white"
              >
                {city}
              </span>
            ))}
          </div>
          <p className="text-sm text-slate-700 mt-2">
            <strong>
              Our pet doctors are available pan-India via video call. Delhi,
              Mumbai, Bangalore, Hyderabad — wherever you are.
            </strong>{" "}
          </p>
        </div>
      </div>
    </section>
  );
});

const FaqSection = memo(function FaqSection() {
  return (
    <section
      id="faq"
      className="py-14 px-4 bg-white scroll-mt-24"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <div className="max-w-4xl mx-auto">
        <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-8">
          Frequently Asked Questions
        </h2>
        <div className="space-y-0 divide-y divide-slate-100 border border-slate-100 rounded-2xl overflow-hidden">
          {FAQ_ITEMS.map((item) => (
            <FaqItem key={item.q} q={item.q} a={item.a} />
          ))}
        </div>
      </div>
    </section>
  );
});

const FinalCtaSection = memo(function FinalCtaSection({
  consultAmount,
  onConsultClick,
}) {
  return (
    <section
      className="py-14 px-4 bg-slate-900 relative overflow-hidden"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <div className="pointer-events-none absolute inset-0">
        <div className="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-brand/10 blur-3xl" />
        <div className="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-accent/5 blur-3xl" />
      </div>
      <div className="relative max-w-2xl mx-auto text-center">
        <p className="text-3xl mb-3">🐾</p>
        <h2 className="text-2xl sm:text-3xl font-extrabold text-white mb-3 leading-tight">
          Your pet needs a doctor.
          <br />
          <span className="text-brand">One is ready now.</span>
        </h2>
        <p className="text-slate-400 text-sm mb-7">
          Fill in your pet's details, describe the issue, and a vet calls you on
          WhatsApp within 15 minutes after payment.
        </p>
        <button
          type="button"
          onClick={onConsultClick}
          className="w-full bg-accent hover:bg-accent-hover text-white font-extrabold text-lg py-4 rounded-2xl shadow-xl shadow-orange-900/30 transition-all"
        >
          <GetStartedCtaLabel amount={consultAmount} />
        </button>
      </div>
    </section>
  );
});

const PageFooter = memo(function PageFooter() {
  return (
    <footer
      className="bg-white border-t border-slate-100 py-4 px-4 pb-28 md:pb-4"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <p className="text-xs text-slate-400 text-center">
        © {new Date().getFullYear()} SnoutIQ ·{" "}
        <a href="/" className="hover:text-brand font-semibold">
          Home
        </a>{" "}
        ·{" "}
        <a href="/privacy-policy" className="hover:text-brand">
          Privacy Policy
        </a>
      </p>
    </footer>
  );
});

// --- Main --------------------------------------------------------------------
export default function PetDoctorOnline() {
  const navigate = useNavigate();
  const { rateType } = getCurrentPrice();
  const consultAmount = PAYMENT_AMOUNTS[rateType] || PAYMENT_AMOUNTS.day;
  const discountedConsultAmount = Math.max(consultAmount - 100, 0);

  // â”€â”€ SIMPLIFIED: single step form, no issue pre-select step â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  const [leadForm, setLeadForm] = useState({
    ownerName: "",
    petName: "",
    petType: "",
    breed: "",
    exoticType: "",
    description: "",
  });
  const [leadError, setLeadError] = useState("");

  const scrollToIdWithRetry = useCallback(
    (id, { offset = 0, retries = 10, delay = 120 } = {}) => {
      if (typeof window === "undefined") return;
      let attempts = 0;
      const attemptScroll = () => {
        const target = document.getElementById(id);
        if (target) {
          const targetTop = target.getBoundingClientRect().top + window.scrollY - offset;
          window.scrollTo({ top: Math.max(0, targetTop), behavior: "smooth" });
          return;
        }
        attempts += 1;
        if (attempts <= retries) window.setTimeout(attemptScroll, delay);
      };
      attemptScroll();
    },
    []
  );

  const scrollToConsultForm = useCallback(() => {
    scrollToIdWithRetry("consult-form", { offset: 80 });
  }, [scrollToIdWithRetry]);

  const getLeadError = () => {
    // if (!leadForm.ownerName.trim()) return "Please enter your name";
    // if (!leadForm.petName.trim()) return "Please enter your pet's name";
    // if (!leadForm.petType) return "Please select pet type";
    if (leadForm.description.trim().length <= 10) {
      return "Please describe the issue in detail (minimum 10 characters)";
    }
    return "";
  };

  // â”€â”€ Issue chip select: just updates description, no step change â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // const handleLeadIssueSelect = useCallback((issue) => {
  //   setSelectedIssue(issue);
  //   setLeadForm((prev) => ({
  //     ...prev,
  //     description: prev.description.trim() ? prev.description : `Issue: ${issue}. `,
  //   }));
  //   setLeadError("");
  // },
  //  []);

  const goToPetDetailsScreen = useCallback(() => {
    const error = getLeadError();
    if (error) {
      setLeadError(error);
      return;
    }
    setLeadError("");
    navigate(CONSULT_DETAILS_ROUTE, {
      state: {
        prefill: {
          ownerName: leadForm.ownerName.trim(),
          name: leadForm.petName.trim(),
          type: leadForm.petType || null,
          breed: leadForm.petType === "exotic" ? "" : leadForm.breed || "",
          exoticType: leadForm.petType === "exotic" ? leadForm.exoticType.trim() : "",
          problemText: leadForm.description.trim(),
          selectedIssue: "",
          source: "video_consult_lp",
        },
      },
    });
  }, [leadForm, navigate]);

  // --- JSON-LD Schemas ------------------------------------------------------
  const serviceSchema = useMemo(() => ({
    "@context": "https://schema.org",
    "@type": "MedicalService",
    name: "Online Vet Consultation India",
    serviceType: "Online Veterinary Consultation",
    provider: { "@type": "Organization", name: "SnoutiQ", url: "https://www.snoutiq.com" },
    areaServed: { "@type": "Country", name: "India" },
    availableChannel: {
      "@type": "ServiceChannel",
      serviceLocation: { "@type": "VirtualLocation", url: CANONICAL },
    },
    offers: {
      "@type": "Offer",
      priceCurrency: "INR",
      price: "399",
      availability: "https://schema.org/InStock",
      description: "Day ₹399 (8AM-10PM), Night ₹499 (10PM-8AM)",
    },
    aggregateRating: {
      "@type": "AggregateRating",
      ratingValue: "4.8",
      bestRating: "5",
      worstRating: "1",
      ratingCount: "214",
      reviewCount: "214",
    },
  }), []);

  const faqSchema = useMemo(() => ({
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: [
      {
        "@type": "Question",
        name: "How does online vet consultation work?",
        acceptedAnswer: {
          "@type": "Answer",
          text: "You receive a WhatsApp video call within 15 minutes after payment. The vet reviews your pet details before calling.",
        },
      },
      {
        "@type": "Question",
        name: "Is online vet consultation available across India?",
        acceptedAnswer: {
          "@type": "Answer",
          text: "Yes, SnoutIQ provides 24/7 online vet consultation across India.",
        },
      },
      {
        "@type": "Question",
        name: "What do I receive after an online vet consultation?",
        acceptedAnswer: {
          "@type": "Answer",
          text: "You receive expert veterinary guidance, personalized care guidance, a consultation summary, and follow-up care instructions based on your pet's needs.",
        },
      },
    ],
  }), []);

  const reviewSchema = useMemo(() => ({
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: "SnoutiQ - Online Vet Consultation India",
    url: "https://www.snoutiq.com",
    image: "https://www.snoutiq.com/logo.png",
    telephone: "+91-9999999999",
    address: { "@type": "PostalAddress", addressCountry: "IN" },
    aggregateRating: {
      "@type": "AggregateRating",
      ratingValue: "4.8",
      bestRating: "5",
      worstRating: "1",
      ratingCount: "214",
      reviewCount: "214",
    },
    review: [
      {
        "@type": "Review",
        author: { "@type": "Person", name: "Priya M." },
        datePublished: "2025-12-10",
        reviewRating: { "@type": "Rating", ratingValue: "5", bestRating: "5" },
        reviewBody: "My dog started vomiting at midnight. SnoutiQ connected me to a vet in under 5 minutes.",
        name: "Excellent midnight emergency support",
      },
      {
        "@type": "Review",
        author: { "@type": "Person", name: "Rahul S." },
        datePublished: "2025-11-22",
        reviewRating: { "@type": "Rating", ratingValue: "5", bestRating: "5" },
        reviewBody: "My cat had watery eyes and I panicked at night. The vet explained everything clearly.",
        name: "Quick and professional online vet consultation",
      },
    ],
  }), []);

  const productSchema = useMemo(() => ({
    "@context": "https://schema.org",
    "@type": "Product",
    name: "Online Vet Consultation - SnoutiQ",
    description: "Talk to a licensed veterinarian online in India via WhatsApp video call within 15 minutes.",
    url: CANONICAL,
    brand: { "@type": "Brand", name: "SnoutiQ" },
    offers: [
      { "@type": "Offer", name: "Day Consultation", priceCurrency: "INR", price: "399", availability: "https://schema.org/InStock", url: CANONICAL },
      { "@type": "Offer", name: "Night Consultation", priceCurrency: "INR", price: "499", availability: "https://schema.org/InStock", url: CANONICAL },
    ],
    aggregateRating: {
      "@type": "AggregateRating",
      ratingValue: "4.8",
      bestRating: "5",
      worstRating: "1",
      ratingCount: "214",
      reviewCount: "214",
    },
  }), []);

  // --- State ----------------------------------------------------------------
  const [featuredVets, setFeaturedVets] = useState([]);
  const [vetsLoading, setVetsLoading] = useState(false);
  const [vetsError, setVetsError] = useState("");
  const [showStickyCta, setShowStickyCta] = useState(false);
  const [shouldLoadFeaturedVets, setShouldLoadFeaturedVets] = useState(false);
  const consultFormRef = useRef(null);
  const vetsSectionRef = useRef(null);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setShowStickyCta(false);
        } else {
          setShowStickyCta(true);
        }
      },
      {
        threshold: 0.2,
      },
    );

    if (consultFormRef.current) {
      observer.observe(consultFormRef.current);
    }

    return () => {
      if (consultFormRef.current) {
        observer.unobserve(consultFormRef.current);
      }
    };
  }, []);

  useEffect(() => {
    let idleHandle = null;
    if (shouldLoadFeaturedVets) return undefined;

    const enableFeaturedVets = () => {
      setShouldLoadFeaturedVets(true);
    };

    const observer =
      typeof IntersectionObserver === "function"
        ? new IntersectionObserver(
            ([entry]) => {
              if (!entry.isIntersecting) return;
              enableFeaturedVets();
            },
            { rootMargin: "320px 0px" },
          )
        : null;

    if (observer && vetsSectionRef.current) {
      observer.observe(vetsSectionRef.current);
    }

    if (typeof window !== "undefined" && "requestIdleCallback" in window) {
      idleHandle = window.requestIdleCallback(enableFeaturedVets, {
        timeout: 1800,
      });
    } else {
      idleHandle = window.setTimeout(enableFeaturedVets, 1200);
    }

    return () => {
      observer?.disconnect();
      if (typeof window !== "undefined") {
        if ("cancelIdleCallback" in window && idleHandle !== null) {
          window.cancelIdleCallback(idleHandle);
        } else if (idleHandle !== null) {
          window.clearTimeout(idleHandle);
        }
      }
    };
  }, [shouldLoadFeaturedVets]);

  useEffect(() => {
    if (!shouldLoadFeaturedVets) return undefined;

    let active = true;
    const controller = new AbortController();
    const loadFeaturedVets = async () => {
      setVetsLoading(true);
      setVetsError("");
      try {
        const base = apiBaseUrl().replace(/\/+$/, "");
        const res = await fetch(`${base}${DOCTOR_LIST_ENDPOINT}`, {
          method: "GET",
          signal: controller.signal,
        });
        const data = await res.json();
        if (!active) return;
        const assetRoot = getAssetRoot();
        const flattened = [];
        (data?.data || []).forEach((entry) => {
          (entry?.doctors || []).forEach((doctor) => {
            if (!doctor) return;
            flattened.push({ ...doctor, clinic_name: entry?.name || "" });
          });
        });
        const cleaned = flattened.filter(
          (doc) => doc?.doctor_name || doc?.doctor_email || doc?.doctor_mobile,
        );
        const prioritized = [...cleaned].sort((a, b) => {
          const aIsShashank = isDrShashankVet(a?.doctor_name);
          const bIsShashank = isDrShashankVet(b?.doctor_name);
          if (aIsShashank && !bIsShashank) return -1;
          if (!aIsShashank && bIsShashank) return 1;
          return 0;
        });
        const topFour = prioritized.slice(0, 4).map((doc) => ({
          id: doc?.id,
          doctor_id: doc?.doctor_id || doc?.id,
          clinic_id: doc?.clinic_id || doc?.vet_registeration_id || undefined,
          service_id: doc?.service_id || "consult_basic",
          vet_slug: doc?.vet_slug || doc?.slug || "",
          name: doc?.doctor_name || "Veterinarian",
          degree: doc?.degree || "",
          experience: doc?.years_of_experience || "",
          specialization: doc?.specialization_select_all_that_apply || "",
          dayRate: doc?.video_day_rate || doc?.doctors_price || "",
          nightRate: doc?.video_night_rate || "",
          responseDay: doc?.response_time_for_online_consults_day || "",
          responseNight: doc?.response_time_for_online_consults_night || "",
          reviewsCount: doc?.reviews_count || 0,
          rating: doc?.average_review_points || "",
          clinic: doc?.clinic_name || "",
          image: resolveDoctorImage(doc, assetRoot),
          raw: doc,
        }));
        setFeaturedVets(topFour);
      } catch (error) {
        if (error?.name !== "AbortError") {
          setVetsError("Could not load doctors right now.");
        }
      } finally {
        if (active) setVetsLoading(false);
      }
    };
    void loadFeaturedVets();
    return () => {
      active = false;
      controller.abort();
    };
  }, [shouldLoadFeaturedVets]);

  const showVetLoadingState = !shouldLoadFeaturedVets || vetsLoading;

  return (
    <div className="min-h-screen bg-white flex flex-col">
      <Helmet>
        <title>{TITLE}</title>
        <meta name="description" content={DESCRIPTION} />
        <meta name="keywords" content={KEYWORDS} />
        <meta name="robots" content="noindex, follow" />
        <link rel="canonical" href={CANONICAL} />
        <meta property="og:type" content="website" />
        <meta property="og:title" content={TITLE} />
        <meta property="og:description" content={DESCRIPTION} />
        <meta property="og:url" content={CANONICAL} />
        <meta property="og:site_name" content="SnoutiQ" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content={TITLE} />
        <meta name="twitter:description" content={DESCRIPTION} />
        <script type="application/ld+json">{JSON.stringify(serviceSchema)}</script>
        <script type="application/ld+json">{JSON.stringify(faqSchema)}</script>
        <script type="application/ld+json">{JSON.stringify(reviewSchema)}</script>
        <script type="application/ld+json">{JSON.stringify(productSchema)}</script>
      </Helmet>

      <LPNavbar consultPath={CONSULT_DETAILS_ROUTE} onConsultClick={scrollToConsultForm} />


{/* Top green trust strip */}
<section className="bg-[#067a5f]">
  <div className="mx-auto max-w-6xl px-4 py-2">
    <div className="flex flex-nowrap items-center justify-center gap-3 overflow-x-auto whitespace-nowrap text-[11px] font-semibold text-white sm:text-sm [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
      <span className="inline-flex items-center gap-1.5 shrink-0">
        <span>Qualified Pet Doctors</span>
      </span>
      <span className="shrink-0 text-white/70">·</span>

      <span className="inline-flex items-center gap-1.5 shrink-0">
        <span>Dogs & Cats</span>
      </span>

      <span className="shrink-0 text-white/70">·</span>

      <span className="inline-flex items-center gap-1.5 shrink-0">
        <span>Video Consult in 15 Min</span>
      </span>

      <span className="shrink-0 text-white/70">·</span>

      <span className="inline-flex items-center gap-1.5 shrink-0">
        <span>Pan India</span>
      </span>
    </div>
  </div>
</section>
      <main className="flex-1 pb-24 md:pb-0">

        {/* â”€â”€ HERO + FORM: form is ALWAYS visible, no step 1 gate â”€â”€ */}
        <section className="relative overflow-hidden bg-gradient-to-b from-[#f0f7ff] to-white px-4 pt-6 pb-10 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
    <span className="inline-flex items-center gap-2 rounded-md bg-[#dfe5e0] px-3.5 py-2 text-[12px] font-extrabold uppercase tracking-[0.16em] text-[#0b7a63]">
      <span className="h-2 w-2 rounded-full bg-[#0b7a63]" />
      Pet Doctor Online
    </span>
  </div>
          <div className="pointer-events-none absolute inset-0 overflow-hidden">
            <div className="absolute top-0 right-0 h-72 w-72 rounded-full bg-brand/5 blur-3xl -translate-y-1/3 translate-x-1/4" />
            <div className="absolute bottom-0 left-0 h-48 w-48 rounded-full bg-orange-100/40 blur-2xl" />
          </div>

          <div className="relative max-w-6xl mx-auto">

            <h1 className="text-[1.85rem] sm:text-[2.6rem] lg:text-[3.2rem] font-bold text-slate-800 leading-[1.1] text-center mb-3">
              {/* Talk to a Vet Online in 15 Minutes â€“ Video Consultation */}
              Consult a Pet Doctor <span className="text-accent">Online – for Your</span> Dog, Cat or Pet
            </h1>
            
            <p className="text-slate-500 text-center text-base mb-7 max-w-3xl mx-auto leading-relaxed">
               Connect with a qualified pet doctor online via video call. Get expert advice for your dog, cat or pet — from a licensed veterinarian, in 15 minutes.
                {/* {slotLabel.toLowerCase()} is
               {" "}â‚¹{formatInr(discountedConsultAmount)} after the â‚¹100 offer. */}
            </p>
            <section className="bg-white px-4 pb-4 sm:px-6 lg:px-8">
  <div className="mx-auto max-w-6xl">
    <DynamicPriceStrip
      currentPrice={discountedConsultAmount}
      originalPrice={consultAmount}
      rateType={rateType}
    />
  </div>
</section>
            {/* â”€â”€ Trust bar ABOVE headline â”€â”€ */}
            {/* <div className="mb-4 flex flex-wrap items-center justify-center gap-2">
              <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm">
                <ShieldCheck className="h-3.5 w-3.5 text-emerald-500" />Licensed vets
              </span>
              <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm">
                <Clock className="h-3.5 w-3.5 text-emerald-500" />Under 15 min
              </span>
              <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm">
                <Star className="h-3.5 w-3.5 text-amber-400" />4.8â˜… Â· 200+ pet parents
              </span>
              <span className="inline-flex items-center gap-1.5 rounded-full border border-brand/20 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm">
                <Globe className="h-3.5 w-3.5 text-brand" />All India Â· 24/7
              </span>
            </div> */}

            {/* â”€â”€ DIRECT FORM â€” always shown, no issue-gate step â”€â”€ */}
            <div
  id="consult-form"
  ref={consultFormRef}
  className="scroll-mt-20"
>
              <div className="mx-auto max-w-2xl overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-[0_24px_80px_-40px_rgba(15,23,42,0.35)]">
                {/* Form header */}
                <div className="bg-gradient-to-r from-brand/10 to-brand/5 border-b border-brand/10 px-5 py-3.5 flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-brand text-white">
                    <PawPrint className="h-4 w-4" />
                  </div>
                  <div>
                    <p className="text-sm font-extrabold text-slate-900">Describe the issue?</p>
                    {/* <p className="text-xs text-slate-500">Takes 60 seconds Â· Vet calls within 15 min</p> */}
                  </div>
                  <div className="ml-auto flex items-center gap-1.5 text-xs font-semibold text-emerald-600">
                    <span className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                    7+ Vets online
                  </div>
                </div>

                <div className="p-4 sm:p-5 space-y-4">
              
                  {/* Description */}
                  <div className="space-y-1.5">
                    <label className="block text-xs font-semibold text-gray-600 uppercase tracking-wide">
                      {/* Describe the issue <span className="text-red-500">*</span> */}
                    </label>
                    <textarea
                      value={leadForm.description}
                      onChange={(e) => { setLeadForm((p) => ({ ...p, description: e.target.value })); setLeadError(""); }}
                      placeholder="Example: My dog has been vomiting since morning and is not eating..."
                      rows={3}
                      className={textareaBase}
                    />
                    <div className="flex justify-between text-xs">
                      <span className="text-gray-400">Min 10 characters</span>
                      <span className={leadForm.description.trim().length > 10 ? "font-semibold text-emerald-600" : "text-gray-400"}>
                        {leadForm.description.trim().length}/10+
                      </span>
                    </div>
                  </div>

                  {/* Error */}
                  {leadError && (
                    <div className="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3 text-red-700">
                      <AlertCircle size={16} className="mt-0.5 flex-shrink-0" />
                      <p className="text-sm">{leadError}</p>
                    </div>
                  )}

                  {/* â”€â”€ GET STARTED CTA â”€â”€ */}
               <button
  type="button"
  onClick={goToPetDetailsScreen}
  className="group flex w-full items-center justify-center gap-2 rounded-2xl bg-accent py-4 text-base font-extrabold text-white shadow-lg shadow-orange-200/60 transition-all hover:bg-accent-hover active:scale-[0.99]"
>
  <span> Consult a Pet Doctor Now </span>
  <ArrowRight className="h-4 w-4 shrink-0 text-white/80 transition-transform group-hover:translate-x-0.5" />
</button>

                  {/* Micro trust row */}
                     <div className="pt-0.5">
                    <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[11px] text-slate-400">
                      <span className="flex items-center gap-1 whitespace-nowrap">
                        <span>🔒</span>
                        <span>Secure · Razorpay</span>
                      </span>

                      <span className="flex items-center gap-1 whitespace-nowrap">
                        <span>💳</span>
                        <span>UPI · Card · Net banking</span>
                      </span>
                    </div>

                    <div className="mt-2 text-center text-[12px] font-semibold text-emerald-600">
                      ✓ WhatsApp confirmation sent instantly after payment
                    </div>

                    <div className="mt-1 text-center text-[12px] text-slate-400">
                      Not satisfied? We&apos;ll make it right. Full support
                      guaranteed.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
        <SocialProofSection />
        <HowItWorksSection />
        <FeaturedVetsSection
          consultAmount={consultAmount}
          featuredVets={featuredVets}
          sectionRef={vetsSectionRef}
          showLoadingState={showVetLoadingState}
          vetsError={vetsError}
          onConsultClick={scrollToConsultForm}
        />
        <ReviewsSection />
        <section
          className="px-4 py-6 bg-white"
          style={BELOW_THE_FOLD_SECTION_STYLE}
        >
          <div className="max-w-2xl mx-auto">
            <button
              type="button"
              onClick={scrollToConsultForm}
              className="w-full flex items-center justify-center gap-2 bg-accent hover:bg-accent-hover text-white font-extrabold text-base py-4 rounded-2xl shadow-lg shadow-orange-200/50 transition-all"
            >
              <GetStartedCtaLabel amount={consultAmount} />
            </button>
          </div>
        </section>
        <AcrossIndiaSection />
        <FaqSection />
        <FinalCtaSection
          consultAmount={consultAmount}
          onConsultClick={scrollToConsultForm}
        />
      </main>

      <PageFooter />
{/* â”€â”€ Sticky Mobile CTA â€” bigger tap target, clearer label â”€â”€ */}
      {showStickyCta && (
  <div className="fixed bottom-0 inset-x-0 z-50 md:hidden bg-white/98 backdrop-blur-sm border-t border-slate-200 px-4 py-3 shadow-2xl">
    <button
      type="button"
      onClick={scrollToConsultForm}
      className="w-full bg-accent hover:bg-accent-hover text-white font-extrabold py-4 rounded-xl text-base transition-colors shadow-lg shadow-orange-200/60"
    >
      <GetStartedCtaLabel amount={consultAmount} />
    </button>
    <p className="text-center text-[10px] text-slate-400 mt-1.5">
      Vet calls you within 15 min · All India · 24/7
    </p>
  </div>
)}
    </div>
  );
}

// --- FAQ item -----------------------------------------------------------------
function FaqItem({ q, a }) {
  const [open, setOpen] = useState(false);
  return (
    <div className={cn("bg-white transition-all", open ? "bg-brand-light/10" : "")}>
      <button type="button" onClick={() => setOpen(!open)} className="w-full flex items-center justify-between gap-4 px-5 py-4 text-left">
        <span className="font-semibold text-slate-900 text-sm leading-snug">{q}</span>
        <ChevronDown className={cn("h-4 w-4 text-brand shrink-0 transition-transform", open ? "rotate-180" : "")} />
      </button>
      {open && <p className="px-5 pb-4 text-sm text-slate-500 leading-relaxed border-t border-slate-100 pt-3">{a}</p>}
    </div>
  );
}

export const VideoConsultPaymentPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const conversionFiredRef = useRef(false);
  const state = location.state || {};
  const petDetails = state?.petDetails;
  const paymentMeta = state?.paymentMeta;
  const vet = state?.vet;

  const fireConversion = useCallback(() => {
    if (conversionFiredRef.current) return;
    conversionFiredRef.current = true;
    if (typeof window !== "undefined" && typeof window.gtag === "function") {
      window.gtag("event", "ads_conversion_PURCHASE_1");
    }
  }, []);

  if (!petDetails || !paymentMeta || !vet) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center px-4 py-12">
        <div className="w-full max-w-md text-center rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
          <h2 className="text-lg font-extrabold text-slate-900">Payment link not ready</h2>
          <p className="mt-2 text-sm text-slate-600">Please start the consultation form again so we can generate your payment.</p>
          <button
            type="button"
            onClick={() => navigate(`${VIDEO_CONSULT_BASE_ROUTE}/owner`, { replace: true })}
            className="mt-5 w-full rounded-2xl bg-accent hover:bg-accent-hover text-white font-extrabold py-3 text-sm shadow-md shadow-orange-200/60 transition-all"
          >
            Start Consultation
          </button>
        </div>
      </div>
    );
  }

  return (
    <Suspense fallback={<PaymentRouteFallback />}>
      <LazyPaymentScreen
        vet={vet}
        petDetails={petDetails}
        paymentMeta={paymentMeta}
        onBack={() => navigate(`${VIDEO_CONSULT_BASE_ROUTE}/problem`)}
        onPay={(verify) => {
          fireConversion();
          navigate("/consultation-booked", {
            replace: true,
            state: { vet, verify, skipConversion: true },
          });
        }}
      />
    </Suspense>
  );
};

export const VideoConsultThankYou = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const vet = location.state?.vet;

  if (!vet) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center px-4 py-12">
        <div className="w-full max-w-md text-center rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
          <h2 className="text-lg font-extrabold text-slate-900">Booking details missing</h2>
          <p className="mt-2 text-sm text-slate-600">Please return to the consultation form to continue.</p>
          <div className="mt-5 flex flex-col gap-3">
            <button
              type="button"
              onClick={() => navigate(`${VIDEO_CONSULT_BASE_ROUTE}/owner`, { replace: true })}
              className="w-full rounded-2xl bg-accent hover:bg-accent-hover text-white font-extrabold py-3 text-sm shadow-md shadow-orange-200/60 transition-all"
            >
              Start Consultation
            </button>
            <button
              type="button"
              onClick={() => navigate("/", { replace: true })}
              className="w-full rounded-2xl border border-slate-200 bg-white text-slate-600 font-semibold py-3 text-sm hover:bg-slate-50 transition-all"
            >
              Go to Home
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <Suspense fallback={<PaymentRouteFallback />}>
      <LazyConfirmationScreen vet={vet} />
    </Suspense>
  );
};

