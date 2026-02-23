// src/screen/Landingscreen.jsx
import React, { useEffect, useMemo, useState } from "react";
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
  Zap,
} from "lucide-react";

// ✅ IMPORTANT: file name/casing same as your actual file.
// If file is src/screens/VetsScreen.jsx then use:
import { buildVetsFromApi, loadVetsWithFallback } from "./vetsData";

const TARGET_DOCTOR_NAME = "Dr Shashannk Goyal";

/** Fallback cards (used only if API not available) */
const FALLBACK_HERO_SLIDES = [
  {
    id: "fallback-1",
    name: "Dr. S. K. Mishra",
    qualification: "B.V.Sc, MBA, PGDCTM, PGCVH, CSAD",
    experience: 17,
    clinicName: "SnoutIQ Verified Network",
    rating: 4.6,
    reviews: 54,
    consultations: 120,
    specializationText: "Dogs, Cats, General Practice",
    bio:
      "Seasoned veterinarian with strong tele-triage experience. Focused on accurate assessment and next-step guidance.",
    image: doctorSlide1,
    priceDay: 500,
    priceNight: 650,
  },
  {
    id: "fallback-2",
    name: "Dr. Mohd Tosif",
    qualification: "B.V.Sc",
    experience: 5,
    clinicName: "SnoutIQ Verified Network",
    rating: 4.5,
    reviews: 38,
    consultations: 86,
    specializationText: "Dogs, Cats, Skin / Dermatology",
    bio:
      "Helps pet parents with common skin conditions, allergies, and day-to-day wellness support.",
    image: doctorSlide3,
    priceDay: 500,
    priceNight: 650,
  },
  {
    id: "fallback-3",
    name: "Dr. Pooja Tarar",
    qualification: "M.V.Sc",
    experience: 18,
    clinicName: "SnoutIQ Verified Network",
    rating: 4.7,
    reviews: 61,
    consultations: 145,
    specializationText: "General Practice, Nutrition",
    bio:
      "Experienced clinician focused on preventive care, nutrition, and practical home-care guidance.",
    image: doctorSlide2,
    priceDay: 500,
    priceNight: 650,
  },
  {
    id: "fallback-4",
    name: "Dr. Shashannk Goyal",
    qualification: "M.V.Sc (Surgery)",
    experience: 10,
    clinicName: "SnoutIQ Verified Network",
    rating: 4.8,
    reviews: 49,
    consultations: 122,
    specializationText: "Surgery, General Practice",
    bio:
      "Surgery and general practice background. Helps you understand urgency and whether clinic visit is needed.",
    image: doctorProfile4,
    priceDay: 500,
    priceNight: 650,
  },
];

const HERO_REVIEWS = [
  {
    id: "hero-review-1",
    name: "Aditi Sharma",
    pet: "Dog: Bruno (Labrador)",
    rating: 5,
    text:
      "Bruno suddenly started vomiting late at night and I panicked. Within 10 minutes I was connected to a vet on Snoutiq. The doctor calmly guided me step by step and told me what to monitor. It saved us an unnecessary emergency visit.",
  },
  {
    id: "hero-review-2",
    name: "Rohan Mehta",
    pet: "Dog: Coco (Shih Tzu)",
    rating: 5,
    text:
      "I was honestly skeptical about online consultation, but the vet was extremely professional. Coco had a skin allergy issue and we got clear guidance immediately. Very convenient and worth the price.",
  },
  {
    id: "hero-review-3",
    name: "Sneha Iyer",
    pet: "Dog: Simba (Golden Retriever)",
    rating: 5,
    text:
      "Simba was not eating and I was worried. The vet explained possible causes and gave proper advice on what to do next. The response time was fast and the whole process felt smooth and trustworthy.",
  },
  {
    id: "hero-review-4",
    name: "Arjun Verma",
    pet: "Dog: Tyson (German Shepherd)",
    rating: 5,
    text:
      "Booked a night consultation around 11 PM. I did not expect someone to respond so quickly. The doctor was experienced and practical. This is genuinely helpful when clinics are closed.",
  },
  {
    id: "hero-review-5",
    name: "Neha Kapoor",
    pet: "Dog: Bella (Indie Mix)",
    rating: 5,
    text:
      "Bella had mild diarrhea and I did not want to overreact. The vet helped me understand diet changes and warning signs. It gave me peace of mind without stepping out of home.",
  },
];

const normalizeNameKey = (value = "") =>
  String(value || "")
    .toLowerCase()
    .replace(/[^a-z]/g, "");

const isTargetConsultDoctor = (vet) => {
  const key = normalizeNameKey(vet?.name);
  if (!key) return false;
  if (key.includes("shashannkgoyal") || key.includes("shashankgoyal")) {
    return true;
  }
  return key.includes("shash") && key.includes("goyal");
};

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

const clipText = (text, max = 160) => {
  const trimmed = String(text || "")
    .replace(/\s+/g, " ")
    .trim();
  if (!trimmed) return "";
  if (trimmed.length <= max) return trimmed;
  return `${trimmed.slice(0, max).trim()}...`;
};

/** Small helper for header links */
const HeaderLink = ({ href, children }) => (
  <a
    href={href}
    className="
      hidden md:inline-flex items-center rounded-full px-3 py-2 text-sm font-semibold
      text-slate-600 hover:text-slate-900 hover:bg-slate-100
      focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300
      transition
    "
  >
    {children}
  </a>
);

const InfoRow = ({ icon: Icon, label, value, subValue }) => {
  const showValue = hasDisplayValue(value);
  const showSubValue = hasDisplayValue(subValue);
  if (!showValue && !showSubValue) return null;

  return (
    <div className="flex items-start gap-3">
      <div
        className="mt-[2px] inline-flex h-9 w-9 items-center justify-center rounded-2xl
                   bg-[#3998de]/10 text-[#3998de] border border-[#3998de]/15"
      >
        <Icon size={18} />
      </div>

      <div className="min-w-0">
        <div className="text-[11px] font-semibold text-slate-500">{label}</div>
        <div className="text-sm font-semibold text-slate-900 leading-5 break-words">
          {showValue ? value : null}
          {showValue && showSubValue ? (
            <span className="text-slate-400 font-semibold"> {" - "} {subValue}</span>
          ) : null}
          {!showValue && showSubValue ? (
            <span className="text-slate-900 font-semibold">{subValue}</span>
          ) : null}
        </div>
      </div>
    </div>
  );
};

const LandingScreen = ({ onStart, onVetAccess, onSelectVet }) => {
  const [openFaq, setOpenFaq] = useState(null);
  const [activeSlide, setActiveSlide] = useState(0);
  const [activeReviewSlide, setActiveReviewSlide] = useState(0);
  const [reviewCardsPerView, setReviewCardsPerView] = useState(1);

  const [vets, setVets] = useState([]);
  const [isStartingConsult, setIsStartingConsult] = useState(false);
  const [brokenImages, setBrokenImages] = useState(() => new Set());

  // ✅ Mobile-safe load using loadVetsWithFallback()
  useEffect(() => {
    let ignore = false;

    const loadVets = async () => {
      try {
        const vetList = await loadVetsWithFallback(); // ✅ returns array directly
        if (!ignore) {
          setVets(buildVetsFromApi(vetList));
        }
      } catch (error) {
        if (!ignore) setVets([]);
      }
    };

    loadVets();
    return () => {
      ignore = true;
    };
  }, []);

  const handleLogoClick = () => {
    if (typeof onStart === "function") {
      onStart();
      return;
    }
    window.scrollTo({ top: 0, behavior: "smooth" });
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

  const heroDoctorSlides = useMemo(() => {
    const source = sortedVets.length ? [...sortedVets] : [...FALLBACK_HERO_SLIDES];
    const targetIndex = source.findIndex((vet) => isTargetConsultDoctor(vet));
    if (targetIndex > 0) {
      const [target] = source.splice(targetIndex, 1);
      if (target) source.unshift(target);
    }
    return source.slice(0, 8);
  }, [sortedVets]);

  const activeSlideIndex = heroDoctorSlides.length
    ? activeSlide % heroDoctorSlides.length
    : 0;
  const activeDoctor = heroDoctorSlides[activeSlideIndex] || FALLBACK_HERO_SLIDES[0];
  const activeDoctorExperience = hasDisplayValue(activeDoctor?.experience)
    ? `${activeDoctor.experience} years exp.`
    : "";

  const targetConsultVet = useMemo(
    () => sortedVets.find((vet) => isTargetConsultDoctor(vet)) || null,
    [sortedVets]
  );

  useEffect(() => {
    if (heroDoctorSlides.length <= 1) return undefined;
    const interval = window.setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % heroDoctorSlides.length);
    }, 3800);
    return () => window.clearInterval(interval);
  }, [heroDoctorSlides.length]);

  useEffect(() => {
    setActiveSlide((prev) => {
      if (!heroDoctorSlides.length) return 0;
      return prev % heroDoctorSlides.length;
    });
  }, [heroDoctorSlides.length]);

  const maxReviewSlideIndex = useMemo(
    () => Math.max(0, HERO_REVIEWS.length - reviewCardsPerView),
    [reviewCardsPerView]
  );

  useEffect(() => {
    const updateCardsPerView = () => {
      const width = window.innerWidth;
      if (width >= 1024) {
        setReviewCardsPerView(3);
        return;
      }
      if (width >= 768) {
        setReviewCardsPerView(2);
        return;
      }
      setReviewCardsPerView(1);
    };

    updateCardsPerView();
    window.addEventListener("resize", updateCardsPerView);
    return () => window.removeEventListener("resize", updateCardsPerView);
  }, []);

  useEffect(() => {
    setActiveReviewSlide((prev) => Math.min(prev, maxReviewSlideIndex));
  }, [maxReviewSlideIndex]);

  useEffect(() => {
    if (maxReviewSlideIndex <= 0) return undefined;
    const interval = window.setInterval(() => {
      setActiveReviewSlide((prev) =>
        prev >= maxReviewSlideIndex ? 0 : prev + 1
      );
    }, 4500);
    return () => window.clearInterval(interval);
  }, [maxReviewSlideIndex]);

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

  const handleStart = async () => {
    if (isStartingConsult) return;
    if (typeof onSelectVet === "function") {
      if (targetConsultVet) {
        handleSelectVet(targetConsultVet);
        return;
      }

      setIsStartingConsult(true);
      try {
        const vetList = await loadVetsWithFallback();
        const parsed = buildVetsFromApi(vetList);
        if (parsed.length) setVets(parsed);
        const liveTargetVet = parsed.find((vet) => isTargetConsultDoctor(vet));
        if (liveTargetVet) {
          handleSelectVet(liveTargetVet);
          return;
        }
      } catch {
        // fall through
      } finally {
        setIsStartingConsult(false);
      }
    }

    if (typeof onStart === "function") {
      onStart();
      return;
    }
    window.scrollTo({ top: 0, behavior: "smooth" });
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
    []
  );

  const getConsultFeeByTime = () => {
    const now = new Date();
    const hour = now.getHours(); // 0-23
    const day = hour >= 8 && hour < 20;
    return day
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
        readTime: "15 min read",
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
    []
  );

  const toggleFaq = (idx) => setOpenFaq((prev) => (prev === idx ? null : idx));

  const fee = getConsultFeeByTime();

  return (
    <div className="min-h-screen bg-slate-50 text-slate-800">
      {/* Header */}
      <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <div className="flex items-center justify-between py-3">
            <button
              type="button"
              onClick={handleLogoClick}
              className="flex items-center gap-3"
              aria-label="SnoutIQ Home"
            >
              <img
                src={logo}
                alt="SnoutIQ"
                className="h-6 w-auto object-contain"
              />
            </button>

            <nav className="flex items-center gap-1">
              <HeaderLink href="#why">Why Teleconsultation</HeaderLink>
              <HeaderLink href="#how">How It Works</HeaderLink>
              <HeaderLink href="#commitment">Our Commitment</HeaderLink>

              <button
                type="button"
                onClick={() => window.open("https://snoutiq.com/blog", "_blank")}
                className="
                  ml-1 rounded-full border border-[#3998de]/25 bg-white px-3 py-2
                  text-xs font-extrabold text-[#3998de] shadow-sm
                  hover:bg-[#3998de]/10 transition
                  sm:px-4 sm:text-sm
                "
              >
                Blog
              </button>

              <button
                type="button"
                onClick={() =>
                  typeof onVetAccess === "function" ? onVetAccess() : null
                }
                className="
                  rounded-full border border-[#3998de]/25 bg-white px-3 py-2
                  text-xs font-extrabold text-[#3998de] shadow-sm
                  hover:bg-[#3998de]/10 transition
                  sm:px-4 sm:text-sm
                "
              >
                🩺 Vet Access
              </button>
            </nav>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#f4faff] via-white to-[#e8f2ff] py-10 md:py-12">
        <div className="pointer-events-none absolute -top-24 right-[-90px] h-72 w-72 rounded-full bg-[#3998de]/15 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-24 left-[-90px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />

        <div className="relative mx-auto max-w-6xl px-4 sm:px-5">
          <div className="grid grid-cols-1 items-center gap-8 md:grid-cols-2 md:gap-10">
            {/* Left */}
            <div>
              <div className="inline-flex items-center gap-2 rounded-full bg-white/90 px-4 py-2 text-xs font-extrabold text-slate-700 shadow-sm ring-1 ring-slate-200">
                <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#3998de]/10 text-[#3998de]">
                  ⭐
                </span>
                Trusted by 100+ Pet Parents in India
              </div>

              <h1 className="mt-5 text-3xl font-black leading-[1.15] text-slate-900 md:text-4xl lg:text-[46px]">
                Talk to a{" "}
                <span className="text-[#3998de]">Verified Vet</span> in{" "}
                <span className="text-slate-900">15 minutes.</span>
              </h1>

              <p className="mt-3 text-sm font-semibold text-slate-500 md:text-base">
                INR 500 Day | INR 650 Night | 24/7 Video Consultation Across India
              </p>

              <p className="mt-4 text-base leading-relaxed text-slate-600 md:text-lg">
                Professional video consultations for your pet&apos;s health
                concerns. Get expert guidance from licensed veterinarians across
                India.
              </p>

              {/* Chips */}
              <div className="mt-5 flex flex-wrap items-center gap-2">
                <span className="inline-flex items-center rounded-full bg-white px-3 py-1.5 text-xs font-extrabold text-slate-700 ring-1 ring-slate-200">
                  Consultation Fee
                </span>

                <span className="inline-flex items-center rounded-full bg-[#EAF4FF] px-3 py-1.5 text-xs font-extrabold text-[#1D4E89] ring-1 ring-[#3998de]/15">
                  {activeDoctorExperience || "Experienced vet"}
                </span>

                <span className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-extrabold text-slate-800 ring-1 ring-slate-200">
                  <span
                    className={`h-2 w-2 rounded-full ${
                      fee.label === "Day" ? "bg-amber-400" : "bg-indigo-400"
                    }`}
                  />
                  {fee.label} Fee{" "}
                  <span className="font-semibold text-slate-500">
                    ({fee.time})
                  </span>{" "}
                  : ₹{fee.price}
                </span>

                <span className="text-xs text-slate-400">(Taxes may apply)</span>
              </div>

              {/* CTA */}
              <button
                type="button"
                onClick={handleStart}
                disabled={isStartingConsult}
                className="
                  group mt-6 inline-flex w-full items-center justify-center gap-2
                  rounded-2xl bg-[#3998de] px-7 py-3.5 text-base font-black text-white
                  shadow-lg shadow-[#3998de]/25 transition
                  hover:bg-[#2F7FC0]
                  disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-[#3998de]
                  sm:w-auto
                "
              >
                {isStartingConsult
                  ? "Opening consult..."
                  : `Start Consultation`}
                <ArrowRight className="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1" />
              </button>

              {/* Stats */}
              <div className="mt-6 grid grid-cols-2 gap-3 sm:gap-4">
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
                    className="
                      rounded-2xl bg-white/90 p-4 shadow-sm ring-1 ring-slate-200
                      hover:shadow-md transition
                    "
                  >
                    <div className="mb-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#3998de]/10">
                        <s.icon className="h-5 w-5 text-[#3998de]" />
                      </div>
                    </div>

                    <div className="text-2xl font-black text-[#3998de] leading-tight">
                      {s.n}
                    </div>
                    <div className="mt-1 text-xs font-semibold text-slate-500">
                      {s.l}
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Right: Doctor Profile Card (screenshot-style) */}
           <div className="w-full">
 <div className="relative rounded-3xl bg-white/80 p-4 shadow-[0_30px_80px_rgba(2,6,23,0.10)] ring-1 ring-white/60 backdrop-blur">
  {/* FIXED HEIGHT CARD */}
  <div className="rounded-3xl bg-white ring-1 ring-slate-200 overflow-hidden h-[560px] flex flex-col">
    
    {/* 1) TOP IMAGE (FULL WIDTH) */}
    <div className="relative h-56 w-full bg-slate-50 shrink-0 overflow-hidden">
      <div className="absolute left-4 top-4 z-10 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-extrabold text-emerald-700 shadow-sm">
        <span className="h-2 w-2 rounded-full bg-emerald-500" />
        Online
      </div>

      <div
        className="flex h-full transition-transform duration-700 ease-in-out"
        style={{ transform: `translateX(-${activeSlideIndex * 100}%)` }}
      >
        {heroDoctorSlides.map((slide, index) => {
          const showImage = Boolean(slide?.image) && !brokenImages.has(slide?.id);
          return (
            <div key={`${slide.id || slide.name}-${index}`} className="min-w-full h-full">
              {showImage ? (
                <img
                  src={slide.image}
                  alt={slide.name}
                  className="h-full w-full object-contain bg-gradient-to-br from-slate-50 to-slate-100"
                  loading={index === 0 ? "eager" : "lazy"}
                  onError={() => markImageBroken(slide?.id)}
                />
              ) : (
                <div className="h-full w-full bg-gradient-to-br from-[#3998de] to-[#2F7FC0] flex items-center justify-center text-4xl font-black text-white">
                  {getInitials(slide?.name)}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>

    {/* 2) NAME + RATING (FIXED, FULL WIDTH) */}
    <div className="px-5 pt-4 shrink-0">
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <div className="text-lg font-black text-slate-900 truncate">
            {activeDoctor?.name}
          </div>
          <div className="mt-0.5 text-xs font-semibold text-slate-500 truncate">
            {activeDoctor?.clinicName || "SnoutIQ Verified Network"}
          </div>
        </div>

        <div className="shrink-0 text-right">
          <div className="inline-flex items-center gap-1 text-slate-800">
            <Star size={16} className="text-amber-500" />
            <span className="text-sm font-black">
              {Number(activeDoctor?.rating || 4.6).toFixed(1)}
            </span>
          </div>
          <div className="text-[11px] font-semibold text-slate-500">
            ({activeDoctor?.reviews || 50} reviews)
          </div>
        </div>
      </div>

      {/* specialization chip (clamped) */}
      {activeDoctor?.specializationText ? (
        <div className="mt-3 inline-flex max-w-full rounded-2xl bg-[#3998de]/10 px-3 py-2 text-[11px] font-extrabold text-[#2F7FC0]">
          <span className="line-clamp-2">{activeDoctor.specializationText}</span>
        </div>
      ) : null}
    </div>

    {/* 3) SCROLL AREA (CARD BADA NAHI HOGA) */}
    <div className="flex-1 overflow-y-auto px-5 pb-4 pt-4">
      <div className="space-y-3">
        <InfoRow
          icon={GraduationCap}
          label="Education"
          value={activeDoctor?.qualification}
          subValue={activeDoctorExperience || null}
        />
        <InfoRow
          icon={Stethoscope}
          label="Specialization"
          value={activeDoctor?.specializationText}
        />
        <InfoRow
          icon={BadgeCheck}
          label="Successful consultations"
          value={
            Number.isFinite(Number(activeDoctor?.consultations))
              ? `${activeDoctor.consultations}+`
              : null
          }
        />

        {/* ABOUT (clamp + still inside scroll) */}
        <div className="mt-2 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
          <div className="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
            About
          </div>
          <p className="mt-1 text-sm font-semibold text-slate-700 leading-6 line-clamp-5">
            {activeDoctor?.bio || "—"}
          </p>
        </div>
      </div>
    </div>

    {/* 4) FOOTER FIXED */}
    <div className="shrink-0 border-t border-slate-100 px-5 py-3 flex items-center justify-between text-[11px] font-semibold text-slate-500">
      <span>Secure consultation</span>
      <span>Verified vets</span>
    </div>
  </div>
</div>
</div>
          </div>
        </div>
            {/* Reviews Slider */}
      <section className="bg-white py-10 md:py-12">
        <div className="mx-auto max-w-6xl px-4 sm:px-5">

          <div className="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-sm">
            <div
              className="flex transition-transform duration-700 ease-out"
              style={{
                transform: `translateX(-${
                  activeReviewSlide * (100 / reviewCardsPerView)
                }%)`,
              }}
            >
              {HERO_REVIEWS.map((review) => (
                <article
                  key={review.id}
                  className="p-3 md:p-4"
                  style={{ flex: `0 0 ${100 / reviewCardsPerView}%` }}
                >
                  <div className="h-full rounded-2xl bg-white p-5 ring-1 ring-slate-200 md:p-6">
                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                      <div>
                        <div className="text-lg font-black text-slate-900">{review.name}</div>
                        <div className="text-sm font-semibold text-[#3998de]">{review.pet}</div>
                      </div>
                      <div className="inline-flex items-center gap-1 text-amber-500">
                        {Array.from({ length: review.rating }).map((_, idx) => (
                          <Star
                            key={`${review.id}-star-${idx}`}
                            className="h-4 w-4 fill-current"
                          />
                        ))}
                      </div>
                    </div>

                    <p className="mt-4 text-sm font-semibold leading-7 text-slate-600 md:text-base md:leading-8">
                      "{review.text}"
                    </p>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </div>
      </section>
      </section>

  

      {/* App Download */}
      <section className="bg-gradient-to-b from-slate-50 via-white to-[#f5f9ff] py-12 md:py-16">
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <div className="grid items-center gap-10 md:grid-cols-2 md:gap-12">
            {/* Left: Phone Mock */}
            <div className="order-2 md:order-1">
              <div className="relative mx-auto max-w-[360px]">
                <div className="absolute -inset-8 rounded-[36px] bg-[#3998de]/12 blur-2xl" />
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
              <h2 className="text-3xl font-black text-slate-900 md:text-4xl">
                Get SnoutIQ on Your Phone
              </h2>

              <p className="mt-3 text-base leading-relaxed text-slate-600 md:text-lg">
                Access veterinary care anytime, anywhere. Download our app for
                instant consultations with verified vets.
              </p>

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
                    <svg viewBox="0 0 512 512" className="h-5 w-5" aria-hidden="true">
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
                    <span className="text-[10px] font-extrabold uppercase tracking-[0.18em] text-white/60">
                      Get it on
                    </span>
                    <span className="block text-base font-black">Google Play</span>
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
                    <svg viewBox="0 0 24 24" className="h-5 w-5 text-slate-400" aria-hidden="true">
                      <path
                        fill="currentColor"
                        d="M16.365 1.43c0 1.14-.52 2.29-1.36 3.03-.83.76-2.17 1.35-3.25 1.26-.14-1.08.4-2.22 1.16-2.98.83-.82 2.24-1.42 3.45-1.31zm5.32 16.65c-.28.64-.42.93-.78 1.5-.51.84-1.23 1.88-2.12 1.9-.79.02-1-.51-2.08-.51-1.08 0-1.33.49-2.12.53-.86.03-1.52-.95-2.03-1.79-1.43-2.34-1.58-5.08-.7-6.45.62-.97 1.6-1.54 2.52-1.54.94 0 1.54.52 2.32.52.76 0 1.22-.52 2.32-.52.82 0 1.7.45 2.32 1.22-2.04 1.12-1.71 4.05.35 5.14z"
                      />
                    </svg>
                  </span>
                  <span className="text-left leading-tight">
                    <span className="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">
                      Coming soon
                    </span>
                    <span className="block text-base font-black">App Store</span>
                  </span>
                </div>
              </div>

              <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                {[
                  { title: "Quick Nearby Clinic Access", icon: "📍" },
                  { title: "Vet Available", icon: "👨‍⚕️" },
                  { title: "Digital Records", icon: "📁" },
                  { title: "Reminders", icon: "⏰" },
                ].map((item) => (
                  <div
                    key={item.title}
                    className="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200"
                  >
                    <div className="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[#3998de]/10 text-lg">
                      {item.icon}
                    </div>
                    <div className="mt-3 text-xs font-extrabold text-slate-700">
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
      <section id="why" className="scroll-mt-24 bg-white py-16 md:py-20">
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <h2 className="text-center text-3xl font-black text-slate-900 md:text-4xl">
            Why Choose Telemedicine for Your Pet?
          </h2>

          <div className="mt-10 grid grid-cols-1 gap-6 md:grid-cols-3 md:gap-8">
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
                className="rounded-3xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-lg"
              >
                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#3998de]/10 text-3xl">
                  {f.icon}
                </div>
                <h3 className="text-lg font-black text-slate-900 md:text-xl">
                  {f.title}
                </h3>
                <p className="mt-2 text-sm text-slate-600 md:text-[15px] font-semibold leading-6">
                  {f.desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How it works */}
      <section id="how" className="scroll-mt-24 bg-slate-50 py-16 md:py-20">
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <h2 className="text-center text-3xl font-black text-slate-900 md:text-4xl">
            How It Works
          </h2>

          <div className="relative mt-12 md:mt-16">
            <div className="absolute left-1/2 top-6 hidden h-px w-[70%] -translate-x-1/2 bg-[#3998de]/20 md:block" />

            <div className="relative grid gap-8 md:grid-cols-3 md:gap-10">
              {[
                {
                  n: "1",
                  title: "Choose a Vet",
                  desc: "Browse nearby vets and select the right specialist for your pet.",
                },
                {
                  n: "2",
                  title: "Describe the Problem",
                  desc: "Share symptoms, duration, and any relevant history. Upload photos if needed.",
                },
                {
                  n: "3",
                  title: "Connect via Video Call",
                  desc: "Start a secure video consultation and get guidance within minutes.",
                },
              ].map((s, i) => (
                <div key={i} className="relative flex flex-col items-center">
                  <div className="relative z-10 flex h-12 w-12 items-center justify-center rounded-full bg-[#3998de] text-lg font-black text-white shadow-lg">
                    {s.n}
                  </div>

                  <div className="mt-6 w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-md ring-1 ring-slate-200 md:max-w-none">
                    <h3 className="text-lg font-black text-slate-900 md:text-xl">
                      {s.title}
                    </h3>
                    <p className="mt-3 text-slate-600 font-semibold leading-6">
                      {s.desc}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* FAQ */}
      <section className="bg-white py-10 md:py-10">
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <h2 className="text-center text-3xl font-black text-slate-900 md:text-4xl">
            Frequently Asked Questions
          </h2>

          <div className="mx-auto mt-10 max-w-3xl">
            {faqs.map((item, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className="mb-4 overflow-hidden rounded-2xl ring-1 ring-slate-200"
                >
                  <button
                    type="button"
                    onClick={() => toggleFaq(idx)}
                    className="flex w-full items-center justify-between bg-slate-50 px-4 py-4 text-left font-black text-slate-900 hover:bg-slate-100"
                  >
                    <span className="pr-4">{item.q}</span>
                    <span className="text-xl font-black text-[#3998de]">
                      {isOpen ? "−" : "+"}
                    </span>
                  </button>
                  {isOpen && (
                    <div className="px-4 py-4 text-slate-600 font-semibold leading-7">
                      {item.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Promise */}
      <section
        id="commitment"
        className="scroll-mt-24 bg-gradient-to-br from-blue-100 to-blue-200 py-14 md:py-16"
      >
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <div className="mx-auto max-w-4xl text-center">
            <div className="text-4xl">❤️</div>
            <h2 className="mt-4 text-2xl font-black text-slate-900 md:text-3xl">
              Our Commitment to Pet Parents
            </h2>
            <p className="mt-4 text-sm leading-7 text-slate-700 md:text-base md:leading-8 font-semibold">
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
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <div className="mx-auto max-w-4xl text-center text-sm text-slate-600 font-semibold leading-7">
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
        <div className="mx-auto max-w-6xl px-4 sm:px-5">
          <div className="mb-10 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
            <h4 className="text-lg font-black">
              Important Medical &amp; Legal Disclaimer
            </h4>
            <p className="mt-3 text-sm text-slate-200 font-semibold leading-7">
              <strong>NO ONLINE PRESCRIPTIONS:</strong> SnoutIQ does not
              prescribe, dispense, or sell any medications. We do not provide
              online prescriptions under any circumstances. All medication needs
              must be addressed through in-person veterinary clinics with proper
              physical examination.
            </p>
            <p className="mt-3 text-sm text-slate-200 font-semibold leading-7">
              <strong>CONSULTATION ONLY:</strong> SnoutIQ provides professional
              veterinary teletriage and consultation services only. Our service
              is designed to help pet parents understand their pet&apos;s
              condition and determine appropriate next steps, including when to
              seek in-person veterinary care.
            </p>
            <p className="mt-3 text-sm text-slate-200 font-semibold leading-7">
              <strong>NOT FOR EMERGENCIES:</strong> This service is not suitable
              for veterinary emergencies. For emergencies, trauma, severe
              symptoms, or life-threatening conditions, please visit your
              nearest veterinary emergency clinic immediately.
            </p>
            <p className="mt-3 text-sm text-slate-200 font-semibold leading-7">
              <strong>LICENSED PROFESSIONALS:</strong> All veterinarians on
              SnoutIQ are licensed professionals registered with the Veterinary
              Council of India. However, video consultations cannot replace
              comprehensive physical examinations.
            </p>
          </div>

          <div className="grid gap-8 md:grid-cols-3">
            <div>
              <h4 className="text-lg font-black">SnoutIQ</h4>
              <p className="mt-3 text-sm text-slate-300 font-semibold leading-7">
                Professional veterinary teleconsultation and triage services
                across India.
              </p>
              <p className="mt-4 text-sm text-slate-300 font-semibold">
                <strong>Service Type:</strong> Consultation &amp; Triage Only
              </p>
              <p className="text-sm text-slate-300 font-semibold">
                <strong>No Prescriptions:</strong> Not a pharmacy service
              </p>
            </div>

            <div>
              <h4 className="text-lg font-black">Contact</h4>
              <p className="mt-3 text-sm text-slate-300 font-semibold">
                Email: admin@snoutiq.com
              </p>
              <p className="text-sm text-slate-300 font-semibold">
                Mobile: +91 85880 07466
              </p>
              <p className="text-sm text-slate-300 font-semibold">
                Service Area: Pan India
              </p>
              <p className="mt-4 text-sm font-black text-rose-200">
                For emergencies, visit your nearest veterinary clinic
                immediately.
              </p>
            </div>

            <div>
              <h4 className="text-lg font-black">Legal &amp; Compliance</h4>
              <div className="mt-3 space-y-2 text-sm text-slate-300 font-semibold">
                <a className="block hover:text-white" href="/terms-of-service">
                  Terms of Service
                </a>
                <a className="block hover:text-white" href="/privacy-policy">
                  Privacy Policy
                </a>
                <a className="block hover:text-white" href="/medical-data-consent">
                  Medical Disclaimer
                </a>
                <a className="block hover:text-white" href="/cookie-policy">
                  Cookie Policy
                </a>
              </div>
            </div>
          </div>

          <div className="mt-10 border-t border-white/10 pt-6 text-center text-xs text-slate-400">
            <p>© 2026 SnoutIQ. Professional veterinary teleconsultation services.</p>
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
    </div>
  );
};

export default LandingScreen;
