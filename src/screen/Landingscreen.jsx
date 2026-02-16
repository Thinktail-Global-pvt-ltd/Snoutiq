// src/screen/Landingscreen.jsx
import React, { useEffect, useMemo, useRef, useState } from "react";
import logo from "../assets/images/logo.png";
import doctorSlide1 from "../assets/doctor1.jpeg";
import doctorSlide2 from "../assets/doctor2.jpeg";
import doctorSlide3 from "../assets/doctor3.jpeg";
import doctorProfile4 from "../assets/doctor4.jpeg";
import blogVaccination from "../assets/images/vaccination_schedule.jpeg";
import blogTickFever from "../assets/images/tickfever.png";
import blogFirstAid from "../assets/images/first_aid_tips.jpeg";
import appPhoneMock from "../assets/mobile UI.jpeg";
import { Button } from "../components/Button";
import {
  ArrowRight,
  BadgeCheck,
  ChevronRight,
  Clock,
  GraduationCap,
  ShieldCheck,
  Star,
  Stethoscope,
  Video,
  X,
  Zap,
} from "lucide-react";
import { buildVetsFromApi } from "./Vetsscreen";

const DOCTOR_PROFILES = [
  {
    name: "Dr. S. K. Mishra",
    degree: "B.V.Sc, MBA, PGDCTM, PGCVH, CSAD",
    experience: "17+ years of experience",
    image: doctorSlide1,
  },
  {
    name: "Dr. Mohd Tosif",
    degree: "B.V.Sc",
    experience: "5+ years of experience",
    image: doctorSlide3,
  },
  {
    name: "Dr. Pooja Tarar",
    degree: "M.V.Sc",
    experience: "18+ years of experience",

    image: doctorSlide2,
  },
  {
    name: "Dr. Shashannk Goyal",
    degree: "M.V.Sc (Surgery)",
    experience: "10+ years of experience",
    image: doctorProfile4,
  },
];

const HERO_SLIDES = DOCTOR_PROFILES;
const VETS_API_URL =
  "https://snoutiq.com/backend/api/exported_from_excell_doctors";
const REVIEWS = [
  {
    name: "Aditi Sharma - Dog: Bruno (Labrador)",
    city: "India",
    rating: 5,
    text: "Bruno suddenly started vomiting late at night and I panicked. Within 10 minutes I was connected to a vet on Snoutiq. The doctor calmly guided me step by step and told me what to monitor. It saved us an unnecessary emergency visit.",
  },
  {
    name: "Rohan Mehta - Dog: Coco (Shih Tzu)",
    city: "India",
    rating: 5,
    text: "I was honestly skeptical about online consultation, but the vet was extremely professional. Coco had a skin allergy issue and we got clear guidance immediately. Very convenient and worth the price.",
  },
  {
    name: "Sneha Iyer - Dog: Simba (Golden Retriever)",
    city: "India",
    rating: 5,
    text: "Simba wasn't eating and I was worried. The vet explained possible causes and gave proper advice on what to do next. The response time was fast and the whole process felt smooth and trustworthy.",
  },
  {
    name: "Arjun Verma - Dog: Tyson (German Shepherd)",
    city: "India",
    rating: 5,
    text: "Booked a night consultation around 11 PM. I didn't expect someone to respond so quickly. The doctor was experienced and practical. This is genuinely helpful when clinics are closed.",
  },
  {
    name: "Neha Kapoor - Dog: Bella (Indie Mix)",
    city: "India",
    rating: 5,
    text: "Bella had mild diarrhea and I didn't want to overreact. The vet helped me understand diet changes and warning signs. It gave me peace of mind without stepping out of home.",
  },
];

const isDayTime = (date = new Date()) => {
  const hour = date.getHours();
  return hour >= 8 && hour < 20;
};

const getInitials = (name = "") => {
  const trimmed = String(name || "").trim();
  if (!trimmed) return "V";
  const parts = trimmed.split(" ").filter(Boolean);
  if (parts.length === 1) return parts[0][0]?.toUpperCase() || "V";
  return `${parts[0][0] || ""}${parts[1][0] || ""}`.toUpperCase();
};

const hasDisplayValue = (value) => {
  if (value === undefined || value === null) return false;
  if (Array.isArray(value)) return value.length > 0;
  if (typeof value === "number") return Number.isFinite(value) && value > 0;
  const trimmed = String(value).trim();
  if (!trimmed) return false;
  const lower = trimmed.toLowerCase();
  if (lower === "null" || lower === "undefined" || lower === "[]") return false;
  if (["na", "n/a", "none"].includes(lower)) return false;
  return true;
};

const formatPrice = (value) => {
  const amount = Number(value);
  if (!Number.isFinite(amount) || amount <= 0) return "";
  return `INR ${amount}`;
};

const clipText = (text, max = 160) => {
  const trimmed = String(text || "")
    .replace(/\s+/g, " ")
    .trim();
  if (!trimmed) return "";
  if (trimmed.length <= max) return trimmed;
  return `${trimmed.slice(0, max).trim()}...`;
};

const InfoRow = ({ icon: Icon, label, value, subValue }) => {
  const showValue = hasDisplayValue(value);
  const showSubValue = hasDisplayValue(subValue);
  if (!showValue && !showSubValue) return null;

  return (
    <div className="flex items-start gap-3">
      <div className="mt-[2px] inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-[#EAF4FF] text-[#1D4E89] border border-[#3998de]/15">
        <Icon size={18} />
      </div>
      <div className="min-w-0">
        <div className="text-[11px] font-semibold text-slate-500">{label}</div>
        <div className="text-sm font-semibold text-slate-900 leading-5 break-words">
          {showValue ? value : null}
          {showValue && showSubValue ? (
            <span className="text-slate-400 font-semibold">
              {" "}
              {" - "} {subValue}
            </span>
          ) : null}
          {!showValue && showSubValue ? (
            <span className="text-slate-900 font-semibold">{subValue}</span>
          ) : null}
        </div>
      </div>
    </div>
  );
};

const SkeletonCard = () => (
  <div className="rounded-3xl border border-slate-200 bg-white shadow-sm p-5 animate-pulse">
    <div className="flex gap-4">
      <div className="h-16 w-16 rounded-2xl bg-slate-100" />
      <div className="flex-1 space-y-2">
        <div className="h-4 w-2/3 rounded bg-slate-100" />
        <div className="h-3 w-1/2 rounded bg-slate-100" />
        <div className="h-3 w-5/6 rounded bg-slate-100" />
      </div>
    </div>
    <div className="mt-4 h-12 rounded-2xl bg-slate-100" />
  </div>
);

const buildReviewText = (vet) => {
  const rating = Number(vet?.rating);
  const reviewCount = Number(vet?.reviews);
  const specialties = hasDisplayValue(vet?.specializationText)
    ? vet.specializationText
    : Array.isArray(vet?.specializationList) && vet.specializationList.length
    ? vet.specializationList.slice(0, 3).join(", ")
    : "";
  const base =
    Number.isFinite(rating) && rating > 0 && Number.isFinite(reviewCount)
      ? `Rated ${rating.toFixed(1)} by ${reviewCount} pet parents`
      : "Highly rated by pet parents";
  const suffix = specialties ? ` for ${specialties} care.` : ".";
  return clipText(`${base}${suffix}`, 120);
};

const LandingScreen = ({ onStart, onVetAccess, onSelectVet }) => {
  const [openFaq, setOpenFaq] = useState(null);
  const [activeSlide, setActiveSlide] = useState(0);
  const activeDoctor = HERO_SLIDES[activeSlide] || HERO_SLIDES[0];
  const vetSectionRef = useRef(null);
  const whyChooseRef = useRef(null);
  const howWeWorkRef = useRef(null);
  const activeRouteRef = useRef("");
  const [vets, setVets] = useState([]);
  const [vetsLoading, setVetsLoading] = useState(true);
  const [vetsError, setVetsError] = useState("");
  const [brokenImages, setBrokenImages] = useState(() => new Set());
  const [activeBioVet, setActiveBioVet] = useState(null);

  useEffect(() => {
    const interval = window.setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % HERO_SLIDES.length);
    }, 3500);
    return () => window.clearInterval(interval);
  }, []);

  useEffect(() => {
    let ignore = false;

    const loadVets = async () => {
      setVetsLoading(true);
      setVetsError("");
      try {
        const res = await fetch(VETS_API_URL);
        const data = await res.json();
        if (!ignore) {
          if (data?.success && Array.isArray(data?.data)) {
            setVets(buildVetsFromApi(data.data));
          } else {
            setVets([]);
            setVetsError("Could not load vets right now.");
          }
        }
      } catch (error) {
        if (!ignore) {
          setVets([]);
          setVetsError("Network error while loading vets.");
        }
      } finally {
        if (!ignore) setVetsLoading(false);
      }
    };

    loadVets();
    return () => {
      ignore = true;
    };
  }, []);

  useEffect(() => {
    activeRouteRef.current = window.location.pathname || "/";

    const routeMap = {
      "/20+vetsonline": vetSectionRef,
      "/whychooseteleconsult": whyChooseRef,
      "/howwework": howWeWorkRef,
    };

    const targetRef = routeMap[window.location.pathname];
    if (targetRef?.current) {
      window.setTimeout(() => {
        targetRef.current?.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }, 100);
    }
  }, []);

  useEffect(() => {
    const sections = [
      { ref: vetSectionRef, path: "/20+vetsonline" },
      { ref: whyChooseRef, path: "/whychooseteleconsult" },
      { ref: howWeWorkRef, path: "/howwework" },
    ];

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((entry) => entry.isIntersecting)
          .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

        if (!visible.length) return;

        const match = sections.find(
          (section) => section.ref.current === visible[0].target
        );

        if (match?.path && activeRouteRef.current !== match.path) {
          activeRouteRef.current = match.path;
          const url = new URL(window.location.href);
          url.pathname = match.path;
          window.history.replaceState(window.history.state, "", url);
        }
      },
      {
        rootMargin: "-20% 0px -55% 0px",
        threshold: [0.3, 0.55, 0.8],
      }
    );

    sections.forEach((section) => {
      if (section.ref.current) observer.observe(section.ref.current);
    });

    return () => observer.disconnect();
  }, []);

  const updateRoute = (path) => {
    if (!path) return;
    if (activeRouteRef.current === path) return;
    activeRouteRef.current = path;
    const url = new URL(window.location.href);
    url.pathname = path;
    window.history.replaceState(window.history.state, "", url);
  };

  const scrollToSection = (ref, path) => {
    ref?.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    updateRoute(path);
  };

  const handleLogoClick = () => {
    if (typeof onStart === "function") {
      onStart();
      return;
    }
    updateRoute("/");
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const handleStart = () => {
    if (typeof onStart === "function") {
      onStart();
      return;
    }
    scrollToSection(vetSectionRef, "/20+vetsonline");
  };

  const markImageBroken = (id) => {
    if (!id) return;
    setBrokenImages((prev) => {
      const next = new Set(prev);
      next.add(id);
      return next;
    });
  };

  const isDay = isDayTime();

  const sortedVets = useMemo(() => {
    const list = [...vets];
    return list.sort((a, b) => {
      const aPrice = isDay ? a.priceDay : a.priceNight;
      const bPrice = isDay ? b.priceDay : b.priceNight;
      return (aPrice || 0) - (bPrice || 0);
    });
  }, [vets, isDay]);

  const reviewItems = useMemo(() => {
    if (sortedVets.length) {
      return sortedVets.slice(0, 10).map((vet) => ({
        name: vet.name,
        city: vet.clinicName || "India",
        rating: Number(vet.rating) || 4.8,
        text: buildReviewText(vet),
      }));
    }
    return REVIEWS;
  }, [sortedVets]);

  const averageRating = useMemo(() => {
    if (!reviewItems.length) return "4.8";
    const total = reviewItems.reduce(
      (sum, item) => sum + (Number(item.rating) || 0),
      0
    );
    const avg = total / reviewItems.length;
    return Number.isFinite(avg) && avg > 0 ? avg.toFixed(1) : "4.8";
  }, [reviewItems]);

  const handleSelectVet = (vet, rateType) => {
    if (typeof onSelectVet !== "function") return;
    if (!vet) return;
    const isDaySlot = rateType ? rateType === "day" : isDayTime();
    const bookingRateType = isDaySlot ? "day" : "night";
    const bookingPrice = isDaySlot ? vet?.priceDay : vet?.priceNight;
    onSelectVet({
      ...vet,
      bookingRateType,
      bookingPrice,
    });
  };

  const faqs = useMemo(
    () => [
      {
        q: "What can telemedicine veterinarians help with?",
        a: "Our veterinarians can provide guidance on behavioral issues, skin conditions, dietary questions, minor injuries, general health concerns, and help determine if an in-person clinic visit is needed. They assess symptoms and provide professional recommendations.",
      },
      {
        q: "What happens during a video consultation?",
        a: "You'll have a live video call with a licensed veterinarian. They'll ask about your pet's symptoms, history, and may request to see your pet on camera. Based on this assessment, they'll provide guidance on care and next steps.",
      },
      {
        q: "When should I use telemedicine vs visiting a clinic?",
        a: "Telemedicine is ideal for initial assessment, follow-ups, behavioral questions, and determining urgency. Emergency situations like severe trauma, difficulty breathing, or suspected poisoning require immediate in-person veterinary care.",
      },
      {
        q: "Are your veterinarians licensed?",
        a: "Yes. All veterinarians on our platform are licensed professionals registered with the Veterinary Council of India. We verify credentials and conduct background checks before onboarding.",
      },
      {
        q: "How quickly can I connect with a veterinarian?",
        a: "Our average response time is around 15 minutes. Availability may vary based on time of day and current demand. We strive to connect you as quickly as possible.",
      },
      {
        q: "What information should I have ready?",
        a: "Have your pet's medical history, current medications or supplements, symptom duration, and any recent changes in behavior or diet. Photos or videos of symptoms can also be helpful.",
      },
      {
        q: "Will I receive documentation after the consultation?",
        a: "Yes. You'll receive a consultation summary with the veterinarian's assessment, recommendations, and any suggested follow-up actions. This can be shared with your regular veterinarian if needed.",
      },
      {
        q: "When is the payment charged?",
        a: "Payment is required at the time of booking to confirm your online video consultation with the selected doctor.",
      },
      {
        q: "Is the consultation fee refundable?",
        a: "Consultation fees are non-refundable once the session has started or the doctor has connected. If the doctor does not return your video call within 30 minutes of the scheduled time, you may contact support to review your case for a refund.",
      },
      {
        q: "What if the doctor does not call me?",
        a: "Please wait up to 30 minutes from the scheduled consultation time. If the doctor has not connected within this period, you can reach out to SnoutIQ support for assistance.",
      },
      {
        q: "What if I miss the doctor’s call?",
        a: "If you do not answer the doctor’s call at the scheduled time, the consultation will be considered completed and the fee will not be refunded.",
      },
      {
        q: "How will the consultation take place?",
        a: "The consultation will be conducted via video call through the doctor’s registered WhatsApp number. Please ensure your phone is reachable and your internet connection is stable at the scheduled time.",
      },
      {
        q: "Is my payment secure?",
        a: "Yes. All payments are processed through secure and encrypted payment gateways.",
      },
    ],
    [],
  );

  const getConsultFeeByTime = () => {
    const now = new Date();
    const hour = now.getHours(); // 0-23

    // Day: 8 AM (08) to 8 PM (20)
    const isDay = hour >= 8 && hour < 20;

    return isDay
      ? { label: "Day", time: "8 AM – 8 PM", price: 500 }
      : { label: "Night", time: "8 PM – 8 AM", price: 650 };
  };

  const blogPosts = useMemo(
    () => [
      {
        title: "Vaccination Schedule for Pets in India",
        excerpt:
          "Complete, vet-approved vaccine timeline with essential boosters for dogs and cats.",
        link: "/blog/vaccination-schedule-for-pets-in-india",
        image: blogVaccination,
        category: "Pet Health",
        readTime: "5 min read",
      },
      {
        title: "Symptoms of Tick Fever in Dogs",
        excerpt:
          "Learn the early warning signs, prevention tips, and when to seek care.",
        link: "/blog/symptoms-of-tick-fever-in-dogs",
        image: blogTickFever,
        category: "Dog Care",
        readTime: "7 min read",
      },
      {
        title: "First Aid Tips Every Pet Parent Should Know",
        excerpt:
          "Practical first-aid steps for common pet emergencies before you reach a vet.",
        link: "/blog/first-aid-tips-every-pet-parent-should-know",
        image: blogFirstAid,
        category: "Emergency",
        readTime: "6 min read",
      },
    ],
    [],
  );

  const toggleFaq = (idx) => setOpenFaq((prev) => (prev === idx ? null : idx));

  return (
    <div className="min-h-screen bg-white text-slate-800">
      {/* Header */}
      <header className="sticky top-0 z-40 bg-white/90 backdrop-blur shadow-[0_2px_10px_rgba(0,0,0,0.05)]">
        <div className="mx-auto max-w-6xl px-5">
          <div className="flex items-center justify-between py-2 md:py-3">
            <button
              type="button"
              onClick={handleLogoClick}
              className="flex items-center gap-3"
              aria-label="SnoutIQ Home"
            >
              {/* Use logo if you have it; fallback text matches the HTML */}
              <img
                src={logo}
                alt="SnoutIQ"
                className="h-6 w-auto object-contain drop-shadow-sm md:h-6 lg:h-6"
              />
            </button>

            <div className="flex items-center gap-2 sm:gap-3">
              <nav className="hidden md:flex items-center gap-4 text-xs font-semibold text-slate-500">
                <button
                  type="button"
                  onClick={() =>
                    scrollToSection(vetSectionRef, "/20+vetsonline")
                  }
                  className="transition hover:text-slate-900"
                >
                  20+ Verified Vets
                </button>
                <button
                  type="button"
                  onClick={() =>
                    scrollToSection(whyChooseRef, "/whychooseteleconsult")
                  }
                  className="transition hover:text-slate-900"
                >
                  Why Teleconsultation
                </button>
                <button
                  type="button"
                  onClick={() => scrollToSection(howWeWorkRef, "/howwework")}
                  className="transition hover:text-slate-900"
                >
                  How It Works
                </button>
              </nav>
              <button
                type="button"
                onClick={() => {
                  window.location.href = "/blog";
                }}
                className="rounded-full border border-[#3998de]/30 px-3 py-1 text-xs font-semibold text-[#3998de] shadow-sm transition hover:bg-[#3998de]/10 whitespace-nowrap sm:px-4 sm:py-1.5 sm:text-sm focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#3998de]/20"
              >
                Blog
              </button>
              <button
                type="button"
                onClick={() =>
                  typeof onVetAccess === "function" ? onVetAccess() : null
                }
                className="rounded-full border border-[#3998de]/30 px-3 py-1 text-xs font-semibold text-[#3998de] shadow-sm transition hover:bg-[#3998de]/10 whitespace-nowrap sm:px-4 sm:py-1.5 sm:text-sm focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#3998de]/20"
              >
                🩺 Vet Access
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#f4faff] via-white to-[#e8f2ff] py-4 md:py-6">
        <div className="pointer-events-none absolute -top-24 right-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-24 left-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="relative mx-auto max-w-6xl px-5">
          <div className="grid grid-cols-1 items-center gap-6 md:grid-cols-2 md:gap-8 lg:gap-10">
            {/* Left */}
            <div>
              <div className="inline-block rounded-full bg-[#EAF4FF] px-3 py-1.5 text-xs font-semibold text-[#1D4E89] shadow-sm sm:text-sm">
                ⭐ Trusted by 100+ Pet Parents in India
              </div>

              <h1 className="mt-3 text-3xl font-extrabold leading-[1.25] text-slate-900 md:text-[34px] lg:text-[36px]">
                Talk to a{" "}
                <span className="text-[#3998de]">Verified Vet</span> in 15
                minutes.
              </h1>

              <p className="mt-3 text-base leading-relaxed text-slate-500 md:text-lg">
                INR 500 Day | INR 650 Night | 24/7 Video Consultation Across
                India
              </p>
              {/* Pricing (Day/Night) */}
              {(() => {
                const fee = getConsultFeeByTime();

                return (
                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    <span className="inline-flex items-center rounded-full bg-white/90 border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                      Consultation Fee
                    </span>

                    <span className="inline-flex items-center rounded-full bg-[#EAF4FF] border border-[#3998de]/20 px-3 py-1 text-xs font-semibold text-[#1D4E89] shadow-sm">
                      {activeDoctor?.experience}
                    </span>

                    <span className="inline-flex items-center gap-2 rounded-full bg-white/90 border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-800 shadow-sm">
                      <span
                        className={`h-2 w-2 rounded-full ${
                          fee.label === "Day" ? "bg-amber-400" : "bg-indigo-400"
                        }`}
                      />
                      {fee.label} Fee{" "}
                      <span className="font-medium text-slate-500">
                        ({fee.time})
                      </span>{" "}
                      : ₹{fee.price}
                    </span>

                    <span className="text-xs text-slate-400">
                      (Taxes may apply)
                    </span>
                  </div>
                );
              })()}

              {/* CTA */}
              <button
                type="button"
                onClick={handleStart}
                className="
    group mt-3 inline-flex items-center justify-center gap-2
    rounded-xl bg-[#3998de] px-6 py-2.5 text-base font-semibold text-white md:text-lg
    shadow-lg shadow-[#3998de]/30 transition
    hover:bg-[#2F7FC0]
    focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#3998de]/25
  "
              >
                Consult a Vet Now
                <ArrowRight className="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1" />
              </button>

              {/* Stats */}
              <div className="mt-3 grid grid-cols-2 gap-2 sm:gap-3">
                {[
                  { n: "15min", l: "Average response time", icon: Clock },
                  {
                    n: "100%",
                    l: "Licensed and experienced vets",
                    icon: ShieldCheck,
                  },
                ].map((s, i) => (
                  <div
                    key={i}
                    className="rounded-xl border border-white/70 bg-white/90 px-3 py-2 shadow-sm hover:shadow-md transition sm:px-4 sm:py-4"
                  >
                    {/* Icon */}
                    <div className="mb-2 sm:mb-3">
                      <div className="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-full bg-[#3998de]/10">
                        <s.icon className="w-4 h-4 sm:w-5 sm:h-5 text-[#3998de]" />
                      </div>
                    </div>

                    {/* Number */}
                    <div className="text-lg sm:text-2xl font-extrabold text-[#3998de] leading-tight">
                      {s.n}
                    </div>

                    {/* Label */}
                    <div className="text-[11px] sm:text-xs text-slate-500 mt-1 leading-snug">
                      {s.l}
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Right (Doctor Image) */}
            <div className="w-full">
              <div className="relative mx-auto w-full max-w-xs sm:max-w-sm md:max-w-md">
                <div className="absolute -top-6 right-10 h-20 w-20 rounded-full bg-[#3998de]/15 blur-2xl" />
                <div className="absolute -bottom-8 left-6 h-24 w-24 rounded-full bg-[#3998de]/10 blur-2xl" />
                <div className="relative overflow-hidden rounded-3xl border border-white/70 bg-white/80 p-2 shadow-[0_25px_60px_rgba(15,118,110,0.08)] md:p-3">
                  <div className="relative overflow-hidden rounded-2xl">
                    <div className="absolute right-3 top-3 z-10 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold text-emerald-700 shadow-sm">
                      <span className="h-2 w-2 rounded-full bg-emerald-500" />
                      Online
                    </div>
                    <div
                      className="flex transition-transform duration-700 ease-in-out"
                      style={{
                        transform: `translateX(-${activeSlide * 100}%)`,
                      }}
                    >
                      {HERO_SLIDES.map((slide, index) => (
                        <div key={slide.name} className="min-w-full">
                          <img
                            src={slide.image}
                            alt={slide.name}
                            className="h-44 w-full object-cover object-center sm:h-52 md:h-60"
                            loading={index === 0 ? "eager" : "lazy"}
                          />
                        </div>
                      ))}
                    </div>
                  </div>
                  <div className="mt-3 text-center">
                    <div className="text-sm font-semibold text-slate-900 md:text-base">
                      {activeDoctor?.name}
                    </div>
                    <div className="mt-1 text-[11px] text-slate-500 md:text-xs">
                      {activeDoctor?.degree}
                    </div>
                    <div className="mt-2 flex flex-wrap items-center justify-center gap-2">
                      <span className="inline-flex items-center rounded-full bg-[#EAF4FF] px-3 py-1 text-[11px] font-semibold text-[#1D4E89]">
                        {activeDoctor?.experience}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Reviews Slider */}
      <section className="bg-gradient-to-r from-[#f4faff] to-[#e8f2ff] py-4 md:py-6">
        <div className="mx-auto max-w-6xl px-5">
          <div className="flex items-center justify-between">
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
              Reviews
            </p>
            <span className="text-xs font-semibold text-slate-500">
              {averageRating} average rating
            </span>
          </div>
        </div>
        <div className="mt-3 review-scroll">
          <div className="review-track">
            {[...reviewItems, ...reviewItems].map((review, idx) => (
              <div
                key={`${review.name}-${idx}`}
                className="review-card bg-white rounded-xl px-4 py-3 shadow-sm border border-slate-200 mx-2"
              >
                <div className="flex items-center gap-2 mb-1.5">
                  <div className="flex">
                    {[...Array(5)].map((_, i) => (
                      <Star
                        key={i}
                        className={`h-3.5 w-3.5 ${
                          i < Math.floor(review.rating)
                            ? "fill-amber-400 text-amber-400"
                            : "text-slate-200"
                        }`}
                      />
                    ))}
                  </div>
                  <span className="text-xs font-semibold text-slate-700">
                    {review.rating}
                  </span>
                </div>
                <p className="text-xs text-slate-600 leading-relaxed">
                  "{review.text}"
                </p>
                <div className="mt-2 text-[11px] text-slate-400">
                  <span className="font-semibold text-slate-500">
                    {review.name}
                  </span>{" "}
                  • {review.city}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Available Vets */}
      <section
        ref={vetSectionRef}
        id="20+vetsonline"
        className="bg-white py-14 md:py-16"
      >
        <div className="mx-auto max-w-6xl px-5">
          <div className="mt-2 md:mt-4">
            <div className="flex items-start justify-between gap-4">
              <div className="min-w-0">
                <h2 className="text-2xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                  Available Vets
                </h2>
                <p className="mt-2 text-sm md:text-base text-slate-500 max-w-3xl">
                  Choose a vet based on experience, specialization, and consult
                  price.
                </p>
              </div>

              <div className="shrink-0 flex flex-col items-end gap-2">
                <div className="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-emerald-700 text-xs md:text-sm font-semibold border border-emerald-100">
                  <Zap size={16} fill="currentColor" />
                  <span>Fast response</span>
                </div>
                <div className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                  <span
                    className={`h-2 w-2 rounded-full ${
                      isDay ? "bg-amber-400" : "bg-indigo-400"
                    }`}
                  />
                  {isDay ? "Day slot pricing" : "Night slot pricing"}
                </div>
              </div>
            </div>

            <div className="mt-4 inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-3 border border-slate-200 shadow-sm text-slate-600">
              <Clock size={18} />
              <span className="text-sm md:text-base">
                Average response time:{" "}
                <strong className="text-slate-900">8 mins</strong>
              </span>
            </div>
          </div>

          {vetsLoading ? (
            <div className="mt-8 grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
              {Array.from({ length: 6 }).map((_, idx) => (
                <SkeletonCard key={`vet-skel-${idx}`} />
              ))}
            </div>
          ) : vetsError ? (
            <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
              <div className="text-red-600 font-bold">{vetsError}</div>
              <div className="text-slate-500 text-sm mt-2">
                Try again or check network.
              </div>
            </div>
          ) : sortedVets.length === 0 ? (
            <div className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
              <div className="text-slate-900 font-bold">No vets found.</div>
              <div className="text-slate-500 text-sm mt-2">
                Please try again later.
              </div>
            </div>
          ) : (
            <div className="mt-8 grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
              {sortedVets.map((vet, index) => {
                const showDayPrice = isDayTime();
                const showImage =
                  Boolean(vet.image) && !brokenImages.has(vet.id);
                const initials = getInitials(vet.name);

                const bioPreview = clipText(vet.bio, 170);
                const experienceLabel = hasDisplayValue(vet.experience)
                  ? `${vet.experience} years exp.`
                  : "";
                const specializationValue = hasDisplayValue(
                  vet.specializationText
                )
                  ? vet.specializationText
                  : Array.isArray(vet.specializationList) &&
                    vet.specializationList.length
                  ? vet.specializationList.join(", ")
                  : "";

                return (
                  <div
                    key={`${vet.id || vet.name}-${index}`}
                    className="rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-lg transition-all overflow-hidden"
                  >
                    <div className="h-1 bg-gradient-to-r from-[#3998de] to-[#2F7FC0]" />

                    <div className="p-5">
                      <div className="flex gap-4">
                        {showImage ? (
                          <img
                            src={vet.image}
                            alt={vet.name}
                            loading="lazy"
                            crossOrigin="anonymous"
                            onError={() => markImageBroken(vet.id)}
                            className="h-16 w-16 rounded-2xl object-cover border border-slate-200 bg-slate-50"
                          />
                        ) : (
                          <div className="h-16 w-16 rounded-2xl bg-[#3998de] text-white flex items-center justify-center text-xl font-extrabold shadow-sm">
                            {initials}
                          </div>
                        )}

                        <div className="flex-1 min-w-0">
                          <div className="flex items-start justify-between gap-2">
                            <div className="min-w-0">
                              <h3 className="truncate text-base md:text-lg font-extrabold text-slate-900">
                                {vet.name}
                              </h3>

                              {hasDisplayValue(vet.clinicName) ? (
                                <p className="mt-0.5 text-xs md:text-sm text-slate-500 truncate">
                                  {vet.clinicName}
                                </p>
                              ) : null}
                            </div>

                            <div className="shrink-0 text-right">
                              <div className="inline-flex items-center gap-1 text-slate-700">
                                <Star size={16} className="text-amber-500" />
                                <span className="text-sm font-extrabold">
                                  {Number(vet.rating).toFixed(1)}
                                </span>
                              </div>
                              <div className="text-[11px] text-slate-500">
                                ({vet.reviews} reviews)
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div className="mt-4 space-y-3">
                        <InfoRow
                          icon={GraduationCap}
                          label="Education"
                          value={vet.qualification}
                          subValue={experienceLabel}
                        />

                        <InfoRow
                          icon={Stethoscope}
                          label="Specialization"
                          value={specializationValue}
                        />

                        <InfoRow
                          icon={BadgeCheck}
                          label="Successful consultations"
                          value={`${vet.consultations}+`}
                        />
                      </div>

                      {bioPreview ? (
                        <div className="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                          <div className="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">
                            About
                          </div>
                          <p className="mt-1 text-sm text-slate-700 leading-6 line-clamp-3">
                            {bioPreview}
                          </p>
                        </div>
                      ) : null}

                      <div className="mt-4 flex items-center justify-between gap-3">
                        <button
                          type="button"
                          onClick={() => setActiveBioVet(vet)}
                          className="text-sm font-semibold text-[#3998de] hover:text-[#2F7FC0] inline-flex items-center gap-1"
                        >
                          View full profile <ChevronRight size={16} />
                        </button>

                        <Button
                          onClick={() =>
                            handleSelectVet(vet, showDayPrice ? "day" : "night")
                          }
                          className="h-11 px-5 rounded-2xl bg-[#3998de] hover:bg-[#2F7FC0] shadow-sm text-sm inline-flex items-center gap-3"
                        >
                          <span className="font-semibold">Consult Now</span>
                        </Button>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </section>

      {activeBioVet ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
          <div className="w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-200">
            <div className="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur px-5 py-4 md:px-7">
              <div className="min-w-0">
                <p className="text-[11px] uppercase tracking-wider text-slate-400">
                  Vet Profile
                </p>
                <h3 className="truncate text-lg font-extrabold text-slate-900 md:text-xl">
                  {activeBioVet.name}
                </h3>
                {hasDisplayValue(activeBioVet.clinicName) ? (
                  <p className="mt-0.5 truncate text-xs text-slate-500">
                    {activeBioVet.clinicName}
                  </p>
                ) : null}
              </div>

              <button
                type="button"
                onClick={() => setActiveBioVet(null)}
                className="rounded-2xl border border-slate-200 bg-slate-50 p-2 text-slate-700 hover:bg-slate-100"
                aria-label="Close"
              >
                <X size={18} />
              </button>
            </div>

            <div className="max-h-[82vh] overflow-y-auto p-5 md:p-7">
              <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5 md:p-7">
                <div className="flex flex-col gap-5 md:flex-row md:items-start md:gap-8">
                  <div className="shrink-0">
                    {activeBioVet?.image &&
                    !brokenImages.has(activeBioVet.id) ? (
                      <img
                        src={activeBioVet.image}
                        alt={activeBioVet.name}
                        loading="lazy"
                        crossOrigin="anonymous"
                        onError={() => markImageBroken(activeBioVet.id)}
                        className="h-36 w-36 md:h-44 md:w-44 rounded-3xl object-cover border border-slate-200 bg-white shadow-sm"
                      />
                    ) : (
                      <div className="h-36 w-36 md:h-44 md:w-44 rounded-3xl bg-[#3998de] text-white flex items-center justify-center text-4xl font-extrabold shadow-sm">
                        {getInitials(activeBioVet?.name)}
                      </div>
                    )}
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      {hasDisplayValue(activeBioVet.qualification) ? (
                        <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                          {activeBioVet.qualification}
                        </span>
                      ) : null}
                      {hasDisplayValue(activeBioVet.experience) ? (
                        <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                          {activeBioVet.experience} yrs exp
                        </span>
                      ) : null}
                      {hasDisplayValue(activeBioVet.raw?.doctor_license) ? (
                        <span className="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">
                          License: {activeBioVet.raw?.doctor_license}
                        </span>
                      ) : null}
                    </div>

                    {hasDisplayValue(activeBioVet.followUp) ||
                    activeBioVet.breakTimes?.length ? (
                      <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        {hasDisplayValue(activeBioVet.followUp) ? (
                          <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <div className="text-[11px] uppercase tracking-wide text-slate-400">
                              Follow-up
                            </div>
                            <div className="mt-1 text-sm font-semibold text-slate-800">
                              {activeBioVet.followUp}
                            </div>
                          </div>
                        ) : null}

                        {activeBioVet.breakTimes?.length ? (
                          <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <div className="text-[11px] uppercase tracking-wide text-slate-400">
                              Break time
                            </div>
                            <div className="mt-1 text-sm font-semibold text-slate-800">
                              {activeBioVet.breakTimes.join(", ")}
                            </div>
                          </div>
                        ) : null}
                      </div>
                    ) : null}

                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Day consult (8 AM - 8 PM)
                        </div>
                        <div className="mt-1 text-lg font-extrabold text-slate-900">
                          {formatPrice(activeBioVet.priceDay) ||
                            "Price on request"}
                        </div>
                        {hasDisplayValue(activeBioVet.responseDay) ? (
                          <div className="mt-1 text-xs text-slate-500">
                            Response:{" "}
                            <span className="font-semibold text-slate-800">
                              {activeBioVet.responseDay}
                            </span>
                          </div>
                        ) : null}
                      </div>

                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Night consult (8 PM - 8 AM)
                        </div>
                        <div className="mt-1 text-lg font-extrabold text-slate-900">
                          {formatPrice(activeBioVet.priceNight) ||
                            "Price on request"}
                        </div>
                        {hasDisplayValue(activeBioVet.responseNight) ? (
                          <div className="mt-1 text-xs text-slate-500">
                            Response:{" "}
                            <span className="font-semibold text-slate-800">
                              {activeBioVet.responseNight}
                            </span>
                          </div>
                        ) : null}
                      </div>
                    </div>
                  </div>
                </div>

                <div className="mt-5 grid gap-4 md:grid-cols-5">
                  {hasDisplayValue(activeBioVet.bio) ? (
                    <div className="md:col-span-3 rounded-3xl border border-slate-200 bg-white p-5 md:p-6">
                      <div className="text-[11px] uppercase tracking-wider text-slate-400">
                        About
                      </div>
                      <div className="mt-1 text-base font-extrabold text-slate-900">
                        Doctor Bio
                      </div>

                      <div className="mt-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                        {activeBioVet.bio.trim()}
                      </div>
                    </div>
                  ) : null}

                  {(Array.isArray(activeBioVet.specializationList) &&
                    activeBioVet.specializationList.length > 0) ||
                  hasDisplayValue(activeBioVet.specializationText) ? (
                    <div className="md:col-span-2 rounded-3xl border border-slate-200 bg-white p-5 md:p-6">
                      <div className="text-[11px] uppercase tracking-wider text-slate-400">
                        Expertise
                      </div>
                      <div className="mt-1 text-base font-extrabold text-slate-900">
                        Specializations
                      </div>

                      <div className="mt-4 text-sm text-slate-700">
                        {Array.isArray(activeBioVet.specializationList) &&
                        activeBioVet.specializationList.length ? (
                          <div className="flex flex-wrap gap-2">
                            {activeBioVet.specializationList.map((s, idx) => (
                              <span
                                key={`${s}-${idx}`}
                                className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700"
                              >
                                {String(s)}
                              </span>
                            ))}
                          </div>
                        ) : (
                          <div className="text-slate-700">
                            {activeBioVet.specializationText}
                          </div>
                        )}
                      </div>
                    </div>
                  ) : null}
                </div>

                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                  <Button onClick={() => setActiveBioVet(null)} className="px-6">
                    Close
                  </Button>

                  <Button
                    onClick={() => {
                      const vet = activeBioVet;
                      setActiveBioVet(null);
                      handleSelectVet(vet);
                    }}
                    className="px-6 bg-[#3998de] hover:bg-[#2F7FC0] inline-flex items-center gap-2"
                  >
                    Proceed to Consult <ChevronRight size={18} />
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {/* App Download */}
      <section className="bg-gradient-to-b from-white via-white to-[#f5f9ff] py-12 md:py-16">
        <div className="mx-auto max-w-6xl px-5">
          <div className="grid items-center gap-10 md:grid-cols-2 md:gap-12">
            {/* Left: Phone Mock */}
            <div className="order-2 md:order-1">
              <div className="relative mx-auto max-w-[360px]">
                <div className="absolute -inset-8 rounded-[36px] bg-[#3998de]/10 blur-2xl" />
                <div className="relative rounded-[32px] bg-gradient-to-b from-slate-900 to-slate-800 p-3 shadow-[0_30px_70px_rgba(15,23,42,0.18)]">
                  <div className="rounded-[28px] bg-white p-2">
                    <div className="overflow-hidden rounded-[24px] border border-slate-100 bg-white">
                      <img
                        src={appPhoneMock}
                        alt="SnoutIQ App Preview"
                        className="h-auto w-full object-contain"
                        loading="lazy"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Right: Copy + Buttons */}
            <div className="order-1 md:order-2">
              <h2 className="text-3xl font-extrabold text-slate-900 md:text-4xl">
                Get SnoutIQ on Your Phone
              </h2>

              <p className="mt-3 text-base leading-relaxed text-slate-500 md:text-lg">
                Access veterinary care anytime, anywhere. Download our app for
                instant consultations with verified vets.
              </p>

              {/* Store Buttons */}
              <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <a
                  href="https://play.google.com/store/apps/details?id=com.petai.snoutiq"
                  target="_blank"
                  rel="noreferrer"
                  className="
                    group inline-flex items-center justify-center gap-3 rounded-2xl
                    bg-slate-900 px-5 py-3 text-white shadow-lg shadow-slate-900/25
                    transition hover:-translate-y-0.5 hover:bg-black
                  "
                >
                  <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10">
                    <svg
                      viewBox="0 0 512 512"
                      className="h-5 w-5"
                      aria-hidden="true"
                    >
                      <path d="M96 64l256 192-256 192V64z" fill="#34A853" />
                      <path d="M96 64l160 120-48 48L96 64z" fill="#FBBC04" />
                      <path
                        d="M256 328l-48-48 48-48 160 120L256 328z"
                        fill="#4285F4"
                      />
                      <path
                        d="M208 232l48-48 48 48-48 48-48-48z"
                        fill="#EA4335"
                      />
                    </svg>
                  </span>
                  <span className="text-left leading-tight">
                    <span className="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/60">
                      Get it on
                    </span>
                    <span className="block text-base font-extrabold">
                      Google Play
                    </span>
                  </span>
                </a>

                <div
                  className="
                    inline-flex cursor-not-allowed items-center justify-center gap-3 rounded-2xl
                    border border-slate-200 bg-slate-100 px-5 py-3 text-slate-400
                  "
                  title="Coming soon"
                >
                  <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white">
                    <svg
                      viewBox="0 0 24 24"
                      className="h-5 w-5 text-slate-400"
                      aria-hidden="true"
                    >
                      <path
                        fill="currentColor"
                        d="M16.365 1.43c0 1.14-.52 2.29-1.36 3.03-.83.76-2.17 1.35-3.25 1.26-.14-1.08.4-2.22 1.16-2.98.83-.82 2.24-1.42 3.45-1.31zm5.32 16.65c-.28.64-.42.93-.78 1.5-.51.84-1.23 1.88-2.12 1.9-.79.02-1-.51-2.08-.51-1.08 0-1.33.49-2.12.53-.86.03-1.52-.95-2.03-1.79-1.43-2.34-1.58-5.08-.7-6.45.62-.97 1.6-1.54 2.52-1.54.94 0 1.54.52 2.32.52.76 0 1.22-.52 2.32-.52.82 0 1.7.45 2.32 1.22-2.04 1.12-1.71 4.05.35 5.14z"
                      />
                    </svg>
                  </span>
                  <span className="text-left leading-tight">
                    <span className="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                      Coming soon
                    </span>
                    <span className="block text-base font-extrabold">
                      App Store
                    </span>
                  </span>
                </div>
              </div>

              {/* Mini Features */}
              <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                {[
                  { title: "Quick Nearby Clinic Access", icon: "📍" },
                  { title: "Vet Available", icon: "👨‍⚕️" },
                  { title: "Digital Records", icon: "📁" },
                  { title: "Reminders", icon: "⏰" },
                ].map((item) => (
                  <div
                    key={item.title}
                    className="rounded-2xl border border-slate-100 bg-white p-4 text-center shadow-sm"
                  >
                    <div className="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[#3998de]/10 text-lg">
                      {item.icon}
                    </div>
                    <div className="mt-3 text-xs font-semibold text-slate-700">
                      {item.title}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section
        ref={whyChooseRef}
        id="whychooseteleconsult"
        className="bg-white py-16 md:py-20"
      >
        <div className="mx-auto max-w-6xl px-5">
          <h2 className="text-center text-3xl font-bold text-slate-900 md:text-4xl">
            Why Choose Telemedicine for Your Pet?
          </h2>

          <div className="mt-10 grid grid-cols-1 gap-8 md:grid-cols-3">
            {[
              {
                icon: "⏱️",
                title: "Quick Response",
                desc: "Average connection time of 15 minutes. No more waiting rooms or long drives to the clinic.",
              },
              {
                icon: "🏥",
                title: "Expert Guidance",
                desc: "Licensed and experienced veterinarians provide professional assessment and advice for your pet's health concerns.",
              },
              {
                icon: "📱",
                title: "Convenient Access",
                desc: "Consult from home via video call. Perfect for initial assessments and follow-up guidance.",
              },
              {
                icon: "💰",
                title: "Cost Effective",
                desc: "Save on transportation and time. Get professional veterinary advice without clinic visit costs.",
              },
              {
                icon: "🛡️",
                title: "Verified Professionals",
                desc: "All veterinarians are licensed, certified, and background-verified for your peace of mind.",
              },
              {
                icon: "📋",
                title: "Digital Records",
                desc: "Consultation records saved securely. Easy to share with your regular vet or for future reference.",
              },
            ].map((f, i) => (
              <div
                key={i}
                className="rounded-2xl border border-slate-100 bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
              >
                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-200 text-3xl">
                  {f.icon}
                </div>
                <h3 className="text-lg font-semibold text-slate-900 md:text-xl">
                  {f.title}
                </h3>
                <p className="mt-2 text-sm text-slate-500 md:text-[15px]">
                  {f.desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How it works */}
      <section
        ref={howWeWorkRef}
        id="howwework"
        className="bg-slate-50 py-16 md:py-20"
      >
        <div className="mx-auto max-w-6xl px-5">
          <div className="mx-auto max-w-3xl text-center">
            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">
              How It Works
            </h2>
            <p className="mt-3 text-sm md:text-base text-slate-500">
              Simple 3-step flow to connect with a verified veterinarian.
            </p>
          </div>

          <div className="relative mt-12 md:mt-16">
            <div className="absolute left-1/2 top-0 bottom-0 w-px -translate-x-1/2 bg-[#3998de]/15 md:hidden" />
            <div className="absolute left-1/2 top-6 hidden h-px w-[72%] -translate-x-1/2 bg-[#3998de]/15 md:block" />

            <div className="relative grid gap-8 md:grid-cols-3 md:gap-10">
              {[
                {
                  n: "1",
                  icon: Stethoscope,
                  title: "Choose a Vet",
                  desc: "Browse verified vets, compare experience & consultation fee, then pick the right one.",
                },
                {
                  n: "2",
                  icon: BadgeCheck,
                  title: "Describe the Problem",
                  desc: "Share symptoms, duration, and history. Upload photos/videos to help the vet assess better.",
                },
                {
                  n: "3",
                  icon: Video,
                  title: "Connect via Video Call",
                  desc: "Start a secure video consultation and get professional guidance within minutes.",
                },
              ].map((s, i) => {
                const Icon = s.icon;
                return (
                  <div key={i} className="relative flex flex-col items-center">
                    <div className="relative z-10 flex h-12 w-12 items-center justify-center rounded-full bg-[#3998de] text-lg font-extrabold text-white shadow-lg ring-8 ring-slate-50">
                      {s.n}
                    </div>

                    <div className="mt-6 w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-7 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg md:max-w-none">
                      <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF4FF] text-[#1D4E89] border border-[#3998de]/15">
                        <Icon size={22} />
                      </div>

                      <h3 className="mt-4 text-lg font-extrabold text-slate-900 md:text-xl">
                        {s.title}
                      </h3>
                      <p className="mt-3 text-sm leading-6 text-slate-500">
                        {s.desc}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      </section>


      {/* FAQ */}
      <section className="bg-white py-10 md:py-10">
        <div className="mx-auto max-w-6xl px-5">
          <h2 className="text-center text-3xl font-bold text-slate-900 md:text-4xl">
            Frequently Asked Questions
          </h2>

          <div className="mx-auto mt-10 max-w-3xl">
            {faqs.map((item, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className="mb-5 overflow-hidden rounded-lg border border-slate-200"
                >
                  <button
                    type="button"
                    onClick={() => toggleFaq(idx)}
                    className="flex w-full items-center justify-between bg-slate-50 px-4 py-4 text-left font-semibold text-slate-900 hover:bg-slate-100"
                  >
                    <span className="pr-4">{item.q}</span>
                    <span className="text-xl font-bold text-[#3998de]">
                      {isOpen ? "−" : "+"}
                    </span>
                  </button>
                  {isOpen && (
                    <div className="px-4 py-4 text-slate-500">{item.a}</div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Promise */}
      <section className="bg-gradient-to-br from-blue-100 to-blue-200 py-14 md:py-16">
        <div className="mx-auto max-w-6xl px-5">
          <div className="mx-auto max-w-4xl text-center">
            <div className="text-4xl">❤️</div>
            <h2 className="mt-4 text-2xl font-bold text-slate-900 md:text-3xl">
              Our Commitment to Pet Parents
            </h2>
            <p className="mt-4 text-sm leading-7 text-slate-600 md:text-base md:leading-8">
              &quot;At SnoutIQ, we understand the anxiety when your pet
              isn&apos;t feeling well. That&apos;s why we&apos;ve built a
              platform where expert veterinary care is just minutes away. Every
              consultation is backed by professional expertise and genuine care
              for your furry family members.&quot;
            </p>
          </div>
        </div>
      </section>

      {/* Disclaimer */}
      <section className="border-t border-slate-200 bg-slate-50 py-8 md:py-10">
        <div className="mx-auto max-w-6xl px-5">
          <div className="mx-auto max-w-4xl text-center text-sm text-slate-500">
            <strong className="mb-2 block text-slate-900">
              Important Information
            </strong>
            <p>
              SnoutIQ provides professional veterinary teletriage and
              consultation services only. We do not dispense, sell, or
              facilitate the purchase of any medications. We do not provide
              online prescriptions. Our veterinarians offer guidance and
              recommendations; any medication needs must be addressed through an
              in-person clinic visit with a licensed veterinarian. In emergency
              situations, please visit your nearest veterinary clinic
              immediately.
            </p>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 py-10 text-white">
        <div className="mx-auto max-w-6xl px-5">
          <div className="mb-10 rounded-xl bg-white/5 p-5">
            <h4 className="text-lg font-semibold">
              Important Medical & Legal Disclaimer
            </h4>
            <p className="mt-3 text-sm text-slate-200">
              <strong>NO ONLINE PRESCRIPTIONS:</strong> SnoutIQ does not
              prescribe, dispense, or sell any medications. We do not provide
              online prescriptions under any circumstances. All medication needs
              must be addressed through in-person veterinary clinics with proper
              physical examination.
            </p>
            <p className="mt-3 text-sm text-slate-200">
              <strong>CONSULTATION ONLY:</strong> SnoutIQ provides professional
              veterinary teletriage and consultation services only. Our service
              is designed to help pet parents understand their pet&apos;s
              condition and determine appropriate next steps, including when to
              seek in-person veterinary care.
            </p>
            <p className="mt-3 text-sm text-slate-200">
              <strong>NOT FOR EMERGENCIES:</strong> This service is not suitable
              for veterinary emergencies. For emergencies, trauma, severe
              symptoms, or life-threatening conditions, please visit your
              nearest veterinary emergency clinic immediately.
            </p>
            <p className="mt-3 text-sm text-slate-200">
              <strong>LICENSED PROFESSIONALS:</strong> All veterinarians on
              SnoutIQ are licensed professionals registered with the Veterinary
              Council of India. However, video consultations cannot replace
              comprehensive physical examinations.
            </p>
          </div>

          <div className="grid gap-8 md:grid-cols-3">
            <div>
              <h4 className="text-lg font-semibold">SnoutIQ</h4>
              <p className="mt-3 text-sm text-slate-300">
                Professional veterinary teleconsultation and triage services
                across India.
              </p>
              <p className="mt-4 text-sm text-slate-300">
                <strong>Service Type:</strong> Consultation &amp; Triage Only
              </p>
              <p className="text-sm text-slate-300">
                <strong>No Prescriptions:</strong> Not a pharmacy service
              </p>
            </div>

            <div>
              <h4 className="text-lg font-semibold">Contact</h4>
              <p className="mt-3 text-sm text-slate-300">
                Email: admin@snoutiq.com
              </p>
              <p className="text-sm text-slate-300">Mobile: +91 85880 07466</p>
              <p className="text-sm text-slate-300">Service Area: Pan India</p>
              <p className="mt-4 text-sm font-semibold text-rose-200">
                For emergencies, visit your nearest veterinary clinic
                immediately.
              </p>
            </div>

            <div>
              <h4 className="text-lg font-semibold">Legal &amp; Compliance</h4>
              <div className="mt-3 space-y-2 text-sm text-slate-300">
                <a className="block hover:text-white" href="/terms-of-service">
                  Terms of Service
                </a>
                <a className="block hover:text-white" href="/privacy-policy">
                  Privacy Policy
                </a>
                <a
                  className="block hover:text-white"
                  href="/medical-data-consent"
                >
                  Medical Disclaimer
                </a>
                <a className="block hover:text-white" href="/cookie-policy">
                  Cookie Policy
                </a>
              </div>
            </div>
          </div>

          <div className="mt-10 border-t border-white/10 pt-6 text-center text-xs text-slate-400">
            <p>
              © 2026 SnoutIQ. Professional veterinary teleconsultation services.
            </p>
            <p className="mt-2">
              All veterinarians are licensed professionals registered with the
              Veterinary Council of India or state veterinary councils.
            </p>
            <p className="mt-2 font-semibold">
              We do not prescribe medications or provide online prescriptions.
              Consultation and triage services only.
            </p>
          </div>
        </div>
      </footer>

      <style>{`
        .review-scroll {
          position: relative;
          width: 100%;
          overflow: hidden;
        }

        .review-track {
          display: flex;
          width: max-content;
          animation: reviewScroll 90s linear infinite;
        }

        .review-card {
          min-width: 240px;
        }

        .review-scroll:hover .review-track {
          animation-play-state: paused;
        }

        @keyframes reviewScroll {
          0% {
            transform: translateX(0);
          }
          100% {
            transform: translateX(-50%);
          }
        }
      `}</style>
    </div>
  );
};

export default LandingScreen;
