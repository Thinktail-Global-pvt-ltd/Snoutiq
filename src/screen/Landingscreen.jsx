// src/screen/Landingscreen.jsx
import React, { useEffect, useMemo, useRef, useState } from "react";
import logo from "../assets/images/logo.png";
import doctorSlide1 from "../assets/doctor1.jpeg";
import doctorSlide2 from "../assets/doctor2.jpeg";
import doctorSlide3 from "../assets/doctor3.jpeg";
import doctorProfile4 from "../assets/doctor4.jpeg";
import appPhoneMock from "../assets/mobile UI.jpeg";
import logo1 from "../assets/images/dark bg.webp";
import { Button } from "../components/Button";
import {
  ArrowRight,
  BadgeCheck,
  ChevronRight,
  Clock,
  ShieldCheck,
  X,
} from "lucide-react";

// IMPORTANT: file name/casing same as your actual file.
// If file is src/screens/VetsScreen.jsx then use:
import { buildVetsFromApi, loadVetsWithFallback } from "./Vetsscreen";

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

const LANDING_REVIEWS = [
  {
    id: "r1",
    author: "Megha G.",
    petInfo: "Labrador parent, Bangalore",
    text: "My dog suddenly stopped eating at 11 PM. I was on a video call with a vet in under 15 minutes and got clear guidance.",
  },
  {
    id: "r2",
    author: "Rahul K.",
    petInfo: "Cat parent, Mumbai",
    text: "I was worried about my cat's rash and couldn't get a clinic slot quickly. SnoutIQ connected me fast and the vet was thorough.",
  },
  {
    id: "r3",
    author: "Priya S.",
    petInfo: "Beagle parent, Delhi",
    text: "The fixed pricing is transparent. The consultation was professional and practical for routine concerns.",
  },
];

const SOCIAL_PROOF_STATS = [
  { icon: "🩺", value: "100+", label: "Verified Online Veterinarians" },
  { icon: "⭐", value: "4.8 / 5", label: "Highly Rated by Pet Parents" },
  { icon: "🏅", value: "7+ Yrs", label: "Specialist Vets Experience" },
  { icon: "🕐", value: "24 / 7", label: "Day & Night Availability" },
  { icon: "🐾", value: "500+", label: "Satisfied Pet Parents" },
];

const WHY_CONSULT_ONLINE = [
  {
    icon: "⚡",
    title: "Instant Access",
    desc: "No booking required. Connect with a verified vet in minutes, day or night.",
  },
  {
    icon: "💰",
    title: "Fixed Pricing",
    desc: "₹399 day consult & ₹549 night consult. 🔥 Get ₹100 OFF on your first consultation. No hidden charges.",
  },
  {
    icon: "🏠",
    title: "Consult From Home",
    desc: "Your pet stays comfortable while you get real-time veterinary guidance.",
  },
  {
    icon: "🩺",
    title: "Qualified Veterinarians",
    desc: "Every vet is licensed, verified, and experienced in handling common concerns.",
  },
  {
    icon: "📋",
    title: "Digital Records",
    desc: "Consultation details are saved for easy future reference.",
  },
  {
    icon: "🌙",
    title: "Night Support",
    desc: "Consultations are available beyond regular hours when you need help most.",
  },
];

const HOW_IT_WORKS_STEPS = [
  {
    n: "1",
    icon: "📋",
    title: "Describe the Issue",
    desc: "Share your pet's concern, age, and basic history in a few quick steps.",
  },
  {
    n: "2",
    icon: "🔍",
    title: "We Assign a Verified Vet",
    desc: "Our system matches your case with the best available veterinarian.",
  },
  {
    n: "3",
    icon: "📹",
    title: "Video Consultation",
    desc: "Join a secure video call and discuss symptoms with the assigned vet.",
  },
  {
    n: "4",
    icon: "📄",
    title: "Guidance & Follow-up",
    desc: "Get clear next steps and advice on home care or clinic visit if needed.",
  },
];

const LANDING_SEO_TITLE =
  "Talk to a Vet Online | Online Vet Consultation India | SnoutiQ";
const LANDING_SEO_DESCRIPTION =
  "Talk to a vet online in 15 minutes. First-time users get ₹100 OFF. ₹399 online vet consultation for dogs, cats & exotic animals. Fast, reliable online veterinarian support across India. Book your consultation now.";

const normalizeNameKey = (value = "") =>
  String(value || "")
    .toLowerCase()
    .replace(/[^a-z]/g, "");

const isDrShashankVet = (vet) => {
  const key = normalizeNameKey(vet?.name);
  if (!key) return false;
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

const DESKTOP_MEDIA_QUERY = "(min-width: 768px)";

const isDesktopViewportNow = () =>
  typeof window !== "undefined" &&
  typeof window.matchMedia === "function" &&
  window.matchMedia(DESKTOP_MEDIA_QUERY).matches;

const LandingScreen = ({ onStart, onVetAccess, onSelectVet }) => {
  const [openFaq, setOpenFaq] = useState(null);
  const [activeSlide, setActiveSlide] = useState(0);
  const activeDoctor = HERO_SLIDES[activeSlide] || HERO_SLIDES[0];
  const vetSectionRef = useRef(null);
  const heroSectionRef = useRef(null);

  const [vets, setVets] = useState([]);
  const [vetsLoading, setVetsLoading] = useState(false);
  const [vetsError, setVetsError] = useState("");
  const [brokenImages, setBrokenImages] = useState(() => new Set());
  const [activeBioVet, setActiveBioVet] = useState(null);
  const [isStartingConsult, setIsStartingConsult] = useState(false);
  const [isDesktopViewport, setIsDesktopViewport] = useState(
    isDesktopViewportNow,
  );
  const [shouldLoadVets, setShouldLoadVets] = useState(false);
  const [showMobileStickyCta, setShowMobileStickyCta] = useState(false);

  useEffect(() => {
    document.title = LANDING_SEO_TITLE;

    const metaDescription = document.querySelector('meta[name="description"]');
    if (metaDescription) {
      metaDescription.setAttribute("content", LANDING_SEO_DESCRIPTION);
    } else {
      const meta = document.createElement("meta");
      meta.setAttribute("name", "description");
      meta.setAttribute("content", LANDING_SEO_DESCRIPTION);
      document.head.appendChild(meta);
    }
  }, []);

  useEffect(() => {
    if (typeof window === "undefined" || typeof window.matchMedia !== "function")
      return undefined;

    const mediaQuery = window.matchMedia(DESKTOP_MEDIA_QUERY);
    const updateViewport = () => setIsDesktopViewport(mediaQuery.matches);
    updateViewport();

    if (typeof mediaQuery.addEventListener === "function") {
      mediaQuery.addEventListener("change", updateViewport);
      return () => mediaQuery.removeEventListener("change", updateViewport);
    }

    mediaQuery.addListener(updateViewport);
    return () => mediaQuery.removeListener(updateViewport);
  }, []);

  useEffect(() => {
    if (!isDesktopViewport) return undefined;
    const interval = window.setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % HERO_SLIDES.length);
    }, 3500);
    return () => window.clearInterval(interval);
  }, [isDesktopViewport]);

  useEffect(() => {
    if (isDesktopViewport) {
      setShowMobileStickyCta(false);
      return undefined;
    }

    const updateStickyCta = () => {
      const heroEl = heroSectionRef.current;
      if (!heroEl) return;
      const heroBottom = heroEl.getBoundingClientRect().bottom;
      setShowMobileStickyCta(heroBottom <= 0);
    };

    updateStickyCta();
    window.addEventListener("scroll", updateStickyCta, { passive: true });
    window.addEventListener("resize", updateStickyCta);

    return () => {
      window.removeEventListener("scroll", updateStickyCta);
      window.removeEventListener("resize", updateStickyCta);
    };
  }, [isDesktopViewport]);

  useEffect(() => {
    if (shouldLoadVets) return;

    if (
      typeof window === "undefined" ||
      typeof window.IntersectionObserver === "undefined"
    ) {
      setShouldLoadVets(true);
      return;
    }

    const sectionEl = vetSectionRef.current;
    if (!sectionEl) return;

    const observer = new window.IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          setShouldLoadVets(true);
          observer.disconnect();
        }
      },
      {
        rootMargin: "320px 0px",
        threshold: 0.01,
      },
    );

    observer.observe(sectionEl);
    return () => observer.disconnect();
  }, [shouldLoadVets]);

  useEffect(() => {
    if (!shouldLoadVets) return;

    let ignore = false;

    const loadVets = async () => {
      setVetsLoading(true);
      setVetsError("");
      try {
        const vetList = await loadVetsWithFallback(); // returns array directly
        if (!ignore) {
          setVets(buildVetsFromApi(vetList));
        }
      } catch (error) {
        if (!ignore) {
          setVets([]);
          setVetsError(error?.message || "Network error while loading vets.");
        }
      } finally {
        if (!ignore) setVetsLoading(false);
      }
    };

    loadVets();
    return () => {
      ignore = true;
    };
  }, [shouldLoadVets]);

  const handleLogoClick = () => {
    if (typeof onStart === "function") {
      onStart();
      return;
    }
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const handleStart = async () => {
    if (isStartingConsult) return;
    setShouldLoadVets(true);

    if (typeof onSelectVet === "function") {
      const currentTarget = sortedVets.find((vet) => isDrShashankVet(vet));
      if (currentTarget) {
        handleSelectVet(currentTarget);
        return;
      }

      setIsStartingConsult(true);
      try {
        const vetList = await loadVetsWithFallback();
        const parsedVets = buildVetsFromApi(vetList);
        if (parsedVets.length) setVets(parsedVets);
        const fetchedTarget = parsedVets.find((vet) => isDrShashankVet(vet));
        if (fetchedTarget) {
          handleSelectVet(fetchedTarget);
          return;
        }
      } catch (error) {
        console.error("[LandingScreen] Failed to load Dr Shashank:", error);
      } finally {
        setIsStartingConsult(false);
      }
    }

    if (typeof onStart === "function") {
      onStart();
      return;
    }
    vetSectionRef.current?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
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
  const originalConsultPrice = isDay ? 499 : 649;
  const discountedConsultPrice = isDay ? 399 : 549;
  const consultDiscountAmount = originalConsultPrice - discountedConsultPrice;
  const mobileTopTickerItems = [
    "India's Trusted Online Vet Platform",
    "Talk to a Vet Online in 15 Minutes",
    "24/7 Vet Consultation",
    `Pay ₹${discountedConsultPrice} · Save ₹${consultDiscountAmount}`,
  ];
  const landingRouteLinks = [
    { label: "How It Works", href: "#how-it-works" },
    { label: "Verified Vets", href: "#verified-vets" },
    { label: "Benefits", href: "#benefits" },
    { label: "FAQs", href: "#faq" },
    { label: "App Download", href: "#app-download" },
  ];

  const sortedVets = useMemo(() => {
    const list = [...vets];
    return list.sort((a, b) => {
      const aPrice = isDay ? a.priceDay : a.priceNight;
      const bPrice = isDay ? b.priceDay : b.priceNight;
      return (aPrice || 0) - (bPrice || 0);
    });
  }, [vets, isDay]);

  const handleSelectVet = (vet, rateType) => {
    if (typeof onSelectVet !== "function") return;
    const isDaySlot = rateType ? rateType === "day" : isDayTime();
    const bookingRateType = isDaySlot ? "day" : "night";
    const bookingPrice = isDaySlot ? vet?.priceDay : vet?.priceNight;

    onSelectVet({
      ...vet,
      bookingRateType,
      bookingPrice,
    });
  };

  const FeaturedVetCard = ({ vet, idx, className = "" }) => {
    const vetKey = vet.id || `${vet.name}-${idx}`;
    const showImage = Boolean(vet.image) && !brokenImages.has(vet.id);
    const qualification = hasDisplayValue(vet.qualification)
      ? vet.qualification
      : "BVSc & AH";
    const specialization = hasDisplayValue(vet.specializationText)
      ? vet.specializationText
      : "General Practice";
    const ratingValue = Number(vet.rating || 4.8).toFixed(1);
    const reviewCount = Number(vet.reviews || 0);
    const yearsValue = Number(vet.experience);
    const experienceChip =
      Number.isFinite(yearsValue) && yearsValue > 0
        ? `${Math.round(yearsValue)} yrs exp`
        : "7+ yrs exp";
    const specializationChips = String(specialization)
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean)
      .slice(0, 2);
    const responseLabelRaw = isDay ? vet.responseDay : vet.responseNight;
    const responseLabel = hasDisplayValue(responseLabelRaw)
      ? String(responseLabelRaw).trim()
      : "0 To 15 Mins";

    return (
      <article
        className={`rounded-2xl border border-[#3998de]/20 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:shadow-[#3998de]/15 ${className}`.trim()}
      >
        <div className="flex items-start gap-3">
          {showImage ? (
            <img
              src={vet.image}
              alt={vet.name}
              loading="lazy"
              decoding="async"
              crossOrigin="anonymous"
              onError={() => markImageBroken(vet.id)}
              className="h-12 w-12 rounded-full object-cover border border-[#3998de]/20 bg-slate-50"
            />
          ) : (
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-[#EAF4FF] text-sm font-extrabold text-[#1D4E89]">
              {getInitials(vet.name)}
            </div>
          )}

          <div className="min-w-0 flex-1">
            <h3 className="line-clamp-2 text-base font-extrabold leading-tight text-slate-900">
              {vet.name}
            </h3>
            <p className="mt-1 line-clamp-1 text-[11px] font-semibold text-[#1D4E89]">
              {qualification}
            </p>
          </div>
        </div>

        <div className="mt-3 flex flex-wrap items-center gap-2">
          {(specializationChips.length
            ? specializationChips
            : [specialization]
          ).map((chip, chipIdx) => (
            <span
              key={`${vetKey}-${chip}-${chipIdx}`}
              className="max-w-[150px] truncate rounded-full border border-[#3998de]/20 bg-[#f8fbff] px-3 py-1 text-xs font-medium text-[#1D4E89]"
            >
              {clipText(chip, 24)}
            </span>
          ))}
          <span className="rounded-full border border-[#3998de]/20 bg-[#EAF4FF] px-3 py-1 text-xs font-bold text-[#1D4E89]">
            {experienceChip}
          </span>
        </div>

        <div className="mt-3 inline-flex items-center gap-1.5 rounded-full border border-[#3998de]/20 bg-[#f8fbff] px-2.5 py-1 text-[11px] font-medium text-[#1D4E89]">
          <Clock size={12} className="text-[#3998de]" />
          Response: {responseLabel}
        </div>

        <div className="mt-3 flex items-center justify-between">
          <div className="flex items-center gap-1 text-xs font-semibold text-slate-700">
            <span className="tracking-[1px] text-amber-500">
              {"\u2605".repeat(5)}
            </span>
            <span>{ratingValue}</span>
            <span className="text-slate-500">({reviewCount})</span>
          </div>
          <span className="inline-flex items-center gap-1 rounded-full border border-[#3998de]/20 bg-[#EAF4FF] px-3 py-1 text-xs font-bold text-[#1D4E89]">
            <BadgeCheck size={12} />
            Verified
          </span>
        </div>

        {/* <button
          type="button"
          onClick={() => setActiveBioVet(vet)}
          className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-teal-700 transition hover:text-teal-800"
        >
          View details <ChevronRight size={14} />
        </button> */}
      </article>
    );
  };

  const AppDownloadButtons = ({ className = "" }) => (
    <div
      className={`mt-5 flex flex-row items-center gap-3 ${className}`.trim()}
    >
      <a
        href="https://play.google.com/store/apps/details?id=com.petai.snoutiq"
        target="_blank"
        rel="noreferrer"
        className="
          btn-highlight-anim group inline-flex min-w-0 flex-1 items-center justify-center gap-3 rounded-2xl
          bg-slate-900 px-4 py-2.5 text-white shadow-lg shadow-slate-900/25
          transition hover:-translate-y-0.5 hover:bg-black
        "
      >
        <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10">
          <svg viewBox="0 0 512 512" className="h-5 w-5" aria-hidden="true">
            <path d="M96 64l256 192-256 192V64z" fill="#34A853" />
            <path d="M96 64l160 120-48 48L96 64z" fill="#FBBC04" />
            <path d="M256 328l-48-48 48-48 160 120L256 328z" fill="#4285F4" />
            <path d="M208 232l48-48 48 48-48 48-48-48z" fill="#EA4335" />
          </svg>
        </span>
        <span className="text-left leading-tight">
          <span className="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/60">
            Get it on
          </span>
          <span className="block text-[15px] font-extrabold">Google Play</span>
        </span>
      </a>

      <div
        className="
          inline-flex min-w-0 flex-1 cursor-not-allowed items-center justify-center gap-3 rounded-2xl
          border border-slate-200 bg-slate-100 px-4 py-2.5 text-slate-400
        "
        title="Coming soon"
      >
        <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-white">
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
          <span className="block text-[15px] font-extrabold">App Store</span>
        </span>
      </div>
    </div>
  );

  const faqs = useMemo(
    () => [
      {
        q: "Can I talk to a vet online right now?",
        a: "Yes. SnoutIQ connects you with a verified online veterinarian within minutes, day and night. Share your concern and we assign the best available vet.",
      },
      {
        q: "What can an online vet consultation help with?",
        a: "Online consultations can help with behavior or appetite changes, vomiting, diarrhea, skin issues, eye and ear concerns, wellness questions, and follow-up guidance.",
      },
      {
        q: "Can I choose a specific vet for my consultation?",
        a: "SnoutIQ assigns the best available verified vet based on your pet's concern and the doctor's expertise to ensure faster and better matching.",
      },
      {
        q: "How much does an online vet consultation cost?",
        a: "Day consultation (6 AM - 10 PM) is ₹399 and night consultation (10 PM - 6 AM) is ₹549. Pricing is fixed and transparent.",
      },
      {
        q: "Will I receive guidance after consultation?",
        a: "Yes. After consultation, the veterinarian's guidance is available in your records for reference and follow-up.",
      },
      {
        q: "How long does a consultation last?",
        a: "A standard consultation is a 15-minute video session with a verified veterinarian.",
      },
      {
        q: "Are the veterinarians qualified?",
        a: "Yes. All veterinarians are licensed professionals and their credentials are verified before onboarding.",
      },
      {
        q: "Is SnoutIQ available for emergencies?",
        a: "For severe emergencies, visit the nearest in-person veterinary emergency clinic immediately. Online consultation is best for non-emergency and semi-urgent concerns.",
      },
      {
        q: "What is your refund policy?",
        a: "If a vet is not assigned within the committed time window, you are eligible for a refund as per policy terms.",
      },
      {
        q: "What device is needed for video consultation?",
        a: "Any smartphone, tablet, or computer with a camera and stable internet connection is sufficient.",
      },
      {
        q: "Is my pet's information private?",
        a: "Yes. Consultation and health details are treated as confidential in line with privacy policy.",
      },
    ],
    [],
  );

  const toggleFaq = (idx) => setOpenFaq((prev) => (prev === idx ? null : idx));

  return (
    <div
      className={`min-h-screen bg-white text-slate-800 ${
        showMobileStickyCta ? "pb-24" : ""
      }`}
    >
      {/* Header */}
      <header className="sticky top-0 z-40 bg-white/90 backdrop-blur shadow-[0_2px_10px_rgba(0,0,0,0.05)]">
        <div className="md:hidden border-b border-[#2F7FC0]/40 bg-gradient-to-r from-[#1D4E89] via-[#2F7FC0] to-[#3998de] text-white">
          <div className="overflow-hidden py-1.5">
            <div
              className="flex min-w-max items-center gap-4 whitespace-nowrap px-4 will-change-transform"
              style={{ animation: "mobileHeaderTicker 18s linear infinite" }}
            >
              {[...mobileTopTickerItems, ...mobileTopTickerItems].map(
                (item, idx) => (
                  <span
                    key={`mobile-top-ticker-${idx}`}
                    className="inline-flex items-center gap-3 text-[11px] font-semibold tracking-[0.02em]"
                  >
                    <span>{item}</span>
                    <span className="h-1 w-1 rounded-full bg-white/75" />
                  </span>
                ),
              )}
            </div>
          </div>
        </div>
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="flex items-center justify-between py-2 md:py-3">
            <button
              type="button"
              onClick={handleLogoClick}
              className="btn-highlight-anim flex items-center gap-3 rounded-lg px-1.5 py-1"
              aria-label="SnoutIQ Home"
            >
              <img
                src={logo}
                alt="SnoutIQ"
                className="h-6 w-auto object-contain drop-shadow-sm md:h-6 lg:h-6"
                decoding="async"
              />
            </button>

            <div className="flex items-center gap-2 sm:gap-3">
              <button
                type="button"
                onClick={() =>
                  window.open("https://snoutiq.com/blog", "_blank")
                }
                className="btn-highlight-anim rounded-full border border-[#3998de]/30 px-3 py-1 text-xs font-semibold text-[#3998de] shadow-sm transition hover:bg-[#3998de]/10 whitespace-nowrap sm:px-4 sm:py-1.5 sm:text-sm"
              >
                Blog
              </button>
              <button
                type="button"
                onClick={() =>
                  typeof onVetAccess === "function" ? onVetAccess() : null
                }
                className="btn-highlight-anim rounded-full border border-[#3998de]/30 px-3 py-1 text-xs font-semibold text-[#3998de] shadow-sm transition hover:bg-[#3998de]/10 whitespace-nowrap sm:px-4 sm:py-1.5 sm:text-sm"
              >
                Vet Access
              </button>
            </div>
          </div>

          <nav className="flex items-center gap-2 overflow-x-auto pb-2 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden md:flex-wrap md:pb-3">
            {landingRouteLinks.map((route) => (
              <a
                key={route.href}
                href={route.href}
                className="btn-highlight-anim inline-flex shrink-0 items-center rounded-full border border-[#3998de]/30 bg-white/90 px-3 py-1 text-[11px] font-semibold text-[#1D4E89] shadow-sm transition hover:bg-[#EAF4FF] sm:text-xs"
              >
                {route.label}
              </a>
            ))}
          </nav>
        </div>
      </header>

      {/* Hero */}
      <section
        ref={heroSectionRef}
        className="landing-hero relative overflow-hidden bg-gradient-to-br from-[#f4faff] via-white to-[#e8f2ff] py-4 md:py-5 lg:min-h-[calc(100vh-64px)] lg:py-3"
      >
        <div className="pointer-events-none absolute -top-24 right-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-24 left-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="relative mx-auto max-w-5xl px-4 sm:px-5 lg:flex lg:min-h-[calc(100vh-88px)] lg:items-center">
          <div className="grid w-full grid-cols-1 items-center gap-3 md:grid-cols-2 md:gap-5 lg:gap-6">
            {/* Left */}
            <div className="landing-hero-copy">
              <div className="inline-block rounded-full bg-[#EAF4FF] px-3 py-1.5 text-xs font-semibold text-[#1D4E89] shadow-sm sm:text-sm">
                India's Online Vet Platform
              </div>

              <h1 className="mt-2.5 text-[28px] font-extrabold leading-[1.25] text-slate-900 md:text-[34px] lg:text-[34px]">
                Talk to a{" "}
                <span className="text-[#3998de]">Verified Vet Online</span> in
                15 Minutes
              </h1>

              <p className="mt-2.5 text-sm leading-relaxed text-slate-500 md:text-[15px]">
                Connect with experienced, verified veterinarians from the comfort of your home. No waiting rooms, no travel — real medical guidance for your pet, anytime.
              </p>

              <div className="mt-2 flex flex-wrap items-center gap-2">
                <span className="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                  ⭐ 4.8/5 Rating
                </span>
                <span className="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                  ✅ 100+ Verified Vets
                </span>
                <span className="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                  ⏱ 15 Min Response
                </span>
                <span className="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                  🔒 Secure & Private
                </span>
              </div>

              <div className="mt-2 flex flex-wrap items-center gap-2">
                <span className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-xs font-semibold text-slate-800 shadow-sm">
                  <span
                    className={`h-2 w-2 rounded-full ${isDay ? "bg-amber-400" : "bg-indigo-400"}`}
                  />

                  <span>{isDay ? "Day Consult" : "Night Consult"}</span>

                  <span className="font-medium text-slate-500">
                    {isDay ? "(8 AM - 8 PM)" : "(8 PM - 8 AM)"}
                  </span>

                  <span className="inline-flex items-center gap-1.5">
                    <span className="text-slate-400">:</span>

                    <span className="text-xs font-semibold text-slate-500 line-through">
                      ₹{isDay ? 499 : 649}
                    </span>

                    <span className="text-sm font-extrabold text-emerald-700">
                      ₹{isDay ? 399 : 549}
                    </span>

                    <span className="text-xs font-bold text-red-600">
                      ₹100 OFF
                    </span>
                  </span>
                </span>
                {/* <span className="text-xs text-slate-400">(No hidden charges)</span> */}
              </div>

              <p className="mt-2 text-xs text-slate-500 sm:text-sm">
                No vet selection needed. We assign the best available vet based
                on your pet&apos;s issue.
              </p>

              {/* Stats */}
              <div className="landing-hero-stats mt-2.5 grid grid-cols-2 gap-2 sm:gap-2.5">
                {[
                  { n: "15 min", l: "Average response time", icon: Clock },
                  {
                    n: "7+ yrs",
                    l: "Experienced veterinarians",
                    icon: ShieldCheck,
                  },
                ].map((s, i) => (
                  <div
                    key={i}
                    className="rounded-xl border border-white/70 bg-white/90 px-3 py-2 shadow-sm transition hover:shadow-md sm:px-3 sm:py-2.5"
                  >
                    <div className="mb-2 sm:mb-2.5">
                      <div className="w-8 h-8 sm:w-8 sm:h-8 flex items-center justify-center rounded-full bg-[#3998de]/10">
                        <s.icon className="w-4 h-4 sm:w-4 sm:h-4 text-[#3998de]" />
                      </div>
                    </div>

                    <div className="text-lg sm:text-xl font-extrabold text-[#3998de] leading-tight">
                      {s.n}
                    </div>

                    <div className="text-[11px] sm:text-xs text-slate-500 mt-1 leading-snug">
                      {s.l}
                    </div>
                  </div>
                ))}
              </div>

              {/* CTA */}
              <div className="mt-3 flex flex-wrap items-center gap-2.5">
                <button
                  type="button"
                  onClick={handleStart}
                  disabled={isStartingConsult}
                  className="
                    btn-highlight-anim group inline-flex items-center justify-center gap-2
                    rounded-lg bg-[#3998de] px-7 py-3.5 text-center text-base font-extrabold leading-none tracking-[0.01em] text-white md:text-lg
                    shadow-lg shadow-[#3998de]/30 transition
                    hover:bg-[#2F7FC0]
                    disabled:cursor-not-allowed disabled:opacity-80
                  "
                >
                  {isStartingConsult
                    ? "Connecting Dr Shashank..."
                    : "Start Online Vet Consultation"}
                  <ArrowRight className="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1" />
                </button>
              </div>
            </div>
            {/* Right (Doctor Image) */}
            {isDesktopViewport ? (
              <div className="landing-hero-visual w-full">
                <div className="relative mx-auto w-full max-w-xs sm:max-w-sm md:max-w-[19.5rem]">
                  <div className="absolute -top-6 right-10 h-20 w-20 rounded-full bg-[#3998de]/15 blur-2xl" />
                  <div className="absolute -bottom-8 left-6 h-24 w-24 rounded-full bg-[#3998de]/10 blur-2xl" />
                  <div className="relative overflow-hidden rounded-3xl border border-white/70 bg-white/80 p-3 shadow-[0_25px_60px_rgba(15,118,110,0.08)] md:p-3.5">
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
                              className="landing-hero-image h-44 w-full object-cover object-center sm:h-52 md:h-52"
                              loading={index === 0 ? "eager" : "lazy"}
                              fetchPriority={index === 0 ? "high" : "low"}
                              decoding="async"
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
            ) : null}
          </div>
        </div>
      </section>

      {/* Social Proof */}
      <section className="bg-slate-900 py-6">
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="grid grid-cols-2 gap-3 md:grid-cols-5 md:gap-0">
            {SOCIAL_PROOF_STATS.map((item, idx) => (
              <div
                key={item.label}
                className={`flex flex-col items-center justify-center rounded-xl border border-white/10 px-3 py-3 text-center md:rounded-none md:border-y-0 md:border-l-0 md:border-r md:px-4 ${
                  idx === SOCIAL_PROOF_STATS.length - 1 ? "md:border-r-0" : ""
                }`}
              >
                <div className="text-lg">{item.icon}</div>
                <div className="mt-1 text-lg font-extrabold text-white">
                  {item.value}
                </div>
                <div className="mt-0.5 text-[11px] font-medium leading-4 text-slate-300">
                  {item.label}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Our Verified Vets */}
      <section
        ref={vetSectionRef}
        id="verified-vets"
        className="bg-gradient-to-b from-[#edf7ff] to-white py-10 md:py-12"
      >
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="inline-flex items-center gap-2 rounded-full bg-[#3998de] px-4 py-1.5 text-xs font-bold uppercase tracking-[0.12em] text-white">
            🩺 100+ Verified Online Veterinarians
          </div>
          <h2 className="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 md:text-3xl">
            Talk to a Vet Online - Real Doctors, Real Experience
          </h2>
          <p className="mt-2 max-w-3xl text-sm text-slate-500 md:text-[15px]">
            Every veterinarian on SnoutIQ is licensed and background-verified.
            The list below is populated from live backend data.
          </p>

          {!shouldLoadVets ? (
            <div className="mt-8 rounded-2xl border border-[#3998de]/20 bg-[#f8fbff] p-4 text-sm text-slate-600">
              Vet profiles will load as soon as this section is in view.
            </div>
          ) : vetsLoading ? (
            <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {Array.from({ length: 8 }).map((_, idx) => (
                <div
                  key={`featured-vet-skel-${idx}`}
                  className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm animate-pulse"
                >
                  <div className="h-12 w-12 rounded-full bg-slate-100" />
                  <div className="mt-3 h-3 w-2/3 rounded bg-slate-100" />
                  <div className="mt-2 h-3 w-1/2 rounded bg-slate-100" />
                  <div className="mt-3 h-2 w-4/5 rounded bg-slate-100" />
                </div>
              ))}
            </div>
          ) : vetsError ? (
            <div className="mt-8 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
              {vetsError}
            </div>
          ) : sortedVets.length === 0 ? (
            <div className="mt-8 rounded-2xl border border-[#3998de]/20 bg-[#f8fbff] p-4 text-sm text-slate-600">
              Vets list unavailable right now. Please try again shortly.
            </div>
          ) : (
            <>
              <div className="mt-6 -mx-4 px-4 md:hidden">
                <div className="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                  {sortedVets.map((vet, idx) => {
                    const vetKey = vet.id || `${vet.name}-${idx}`;
                    return (
                      <FeaturedVetCard
                        key={`featured-vet-mobile-${vetKey}`}
                        vet={vet}
                        idx={idx}
                        className="min-w-[86%] snap-center"
                      />
                    );
                  })}
                </div>
              </div>
              <div className="mt-6 hidden grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 md:grid">
                {sortedVets.map((vet, idx) => {
                  const vetKey = vet.id || `${vet.name}-${idx}`;
                  return (
                    <FeaturedVetCard
                      key={`featured-vet-${vetKey}`}
                      vet={vet}
                      idx={idx}
                    />
                  );
                })}
              </div>
            </>
          )}
          <div className="mt-5 flex flex-col items-start justify-between gap-4 rounded-2xl bg-slate-900 px-5 py-5 md:flex-row md:items-center">
            <p className="max-w-3xl text-sm text-slate-200">
              <span className="block font-bold text-white">
                Want to Talk to a Vet Online Right Now?
              </span>
              Can't find a vet? We'll assign the best available one matching your pet's needs — usually within 2 minutes.
            </p>
            <button
              type="button"
              onClick={handleStart}
              disabled={isStartingConsult}
              className="btn-highlight-anim inline-flex items-center justify-center gap-2 rounded-full bg-[#3998de] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#3998de]/30 transition hover:bg-[#2F7FC0] disabled:cursor-not-allowed disabled:opacity-80"
            >
              {isStartingConsult
                ? "Connecting Dr Shashank..."
                : `Consult a Vet Online - ₹${isDay ? 399 : 549} (₹100 OFF)`}
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </section>

      {/* Talk to Vet Strip */}
      <section className="bg-[#3998de] py-7">
        <div className="mx-auto flex max-w-5xl flex-col items-start justify-between gap-4 px-4 sm:px-5 md:flex-row md:items-center">
          <div>
            <h2 className="text-xl font-extrabold text-white md:text-2xl">
              Want to Talk to a Vet Online Right Now?
            </h2>
            <p className="mt-1 text-sm text-blue-50">
              Describe your pet&apos;s issue. We connect you with an experienced
              online veterinarian in minutes.
            </p>
          </div>
          <button
            type="button"
            onClick={handleStart}
            disabled={isStartingConsult}
            className="btn-highlight-anim inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-[#1D4E89] shadow-md transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-80"
          >
            {isStartingConsult ? "Connecting..." : "Talk to a Vet Online"}
            <ArrowRight className="h-4 w-4" />
          </button>
        </div>
      </section>

      {/* Reviews */}
      <section className="bg-gradient-to-b from-[#f8fbff] to-white py-10 md:py-12">
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="text-center">
            <div className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#1D4E89]">
              <span className="h-[2px] w-6 bg-[#3998de]" />
              TESTIMONIALS
              <span className="h-[2px] w-6 bg-[#3998de]" />
            </div>
            <h2 className="mt-2.5 text-2xl font-extrabold tracking-tight text-slate-900 md:text-3xl">
              Pet Parents Trust SnoutIQ
            </h2>
            <div className="mt-3 flex items-center justify-center gap-3">
              <div className="text-3xl font-extrabold text-slate-900">4.8</div>
              <div>
                <div className="text-base leading-none text-amber-500">
                  {"\u2605".repeat(5)}
                </div>
                <div className="text-xs text-slate-500">
                  Based on 1,200+ reviews
                </div>
              </div>
            </div>
          </div>

          <div className="mt-6 grid gap-3 md:grid-cols-3">
            {LANDING_REVIEWS.map((review) => (
              <article
                key={review.id}
                className="rounded-2xl border border-[#3998de]/20 bg-white p-4 shadow-sm"
              >
                <div className="text-sm text-amber-500">
                  {"\u2605".repeat(5)}
                </div>
                <p className="mt-2 text-sm leading-6 text-slate-600">
                  &quot;{review.text}&quot;
                </p>
                <div className="mt-4 flex items-center gap-2">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[#EAF4FF] text-xs font-bold text-[#1D4E89]">
                    {getInitials(review.author)}
                  </div>
                  <div>
                    <div className="text-sm font-semibold text-slate-900">
                      {review.author}
                    </div>
                    {review.petInfo ? (
                      <div className="text-xs text-slate-500">
                        {review.petInfo}
                      </div>
                    ) : null}
                  </div>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* Available Vets */}
      {/* <section
        ref={vetSectionRef}
        id="available-vets"
        className="bg-white py-14 md:py-16"
      >
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
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
                const showImage = Boolean(vet.image) && !brokenImages.has(vet.id);
                const initials = getInitials(vet.name);

                const bioPreview = clipText(vet.bio, 170);
                const experienceLabel = hasDisplayValue(vet.experience)
                  ? `${vet.experience} years exp.`
                  : "";
                const specializationValue = hasDisplayValue(vet.specializationText)
                  ? vet.specializationText
                  : Array.isArray(vet.specializationList) && vet.specializationList.length
                  ? vet.specializationList.join(", ")
                  : "";

                return (
                  <div
                    key={`${vet.id || vet.name}-${index}`}
                    className="rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-lg transition-all overflow-hidden"
                  >
                    <div className="h-1 bg-gradient-to-r from-teal-500 to-emerald-500" />

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
                          <div className="h-16 w-16 rounded-2xl bg-amber-400 text-white flex items-center justify-center text-xl font-extrabold shadow-sm">
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
                          className="text-sm font-semibold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1"
                        >
                          View full profile <ChevronRight size={16} />
                        </button>

                        <Button
                          onClick={() =>
                            handleSelectVet(vet, showDayPrice ? "day" : "night")
                          }
                          className="h-11 px-5 rounded-2xl bg-teal-600 hover:bg-teal-700 shadow-sm text-sm inline-flex items-center gap-3"
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
      </section> */}

      {/* Vet Profile Modal */}
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
                className="btn-highlight-anim rounded-2xl border border-slate-200 bg-slate-50 p-2 text-slate-700 hover:bg-slate-100"
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
                        decoding="async"
                        crossOrigin="anonymous"
                        onError={() => markImageBroken(activeBioVet.id)}
                        className="h-36 w-36 md:h-44 md:w-44 rounded-3xl object-cover border border-slate-200 bg-white shadow-sm"
                      />
                    ) : (
                      <div className="h-36 w-36 md:h-44 md:w-44 rounded-3xl bg-amber-400 text-white flex items-center justify-center text-4xl font-extrabold shadow-sm">
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
                      </div>

                      <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="text-[11px] uppercase tracking-wide text-slate-400">
                          Night consult (8 PM - 8 AM)
                        </div>
                        <div className="mt-1 text-lg font-extrabold text-slate-900">
                          {formatPrice(activeBioVet.priceNight) ||
                            "Price on request"}
                        </div>
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
                  <Button
                    onClick={() => setActiveBioVet(null)}
                    className="btn-highlight-anim px-6"
                  >
                    Close
                  </Button>

                  <Button
                    onClick={() => {
                      const vet = activeBioVet;
                      setActiveBioVet(null);
                      handleSelectVet(vet);
                    }}
                    className="btn-highlight-anim px-6 bg-[#3998de] hover:bg-[#2F7FC0] inline-flex items-center gap-2"
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
      <section
        id="app-download"
        className="bg-gradient-to-b from-white via-white to-[#f5f9ff] py-10 md:py-12"
      >
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="grid items-center gap-8 md:grid-cols-2 md:gap-10">
            {/* Left: Phone Mock */}
            {isDesktopViewport ? (
              <div className="order-2 md:order-1">
                <div className="relative mx-auto max-w-[300px] md:max-w-[320px]">
                  <div className="absolute -inset-6 rounded-[30px] bg-[#3998de]/10 blur-2xl" />
                  <div className="relative rounded-[28px] bg-gradient-to-b from-slate-900 to-slate-800 p-2.5 shadow-[0_24px_56px_rgba(15,23,42,0.16)]">
                    <div className="rounded-[24px] bg-white p-2">
                      <div className="overflow-hidden rounded-[20px] border border-slate-100 bg-white">
                        <img
                          src={appPhoneMock}
                          alt="SnoutIQ App Preview"
                          className="h-auto w-full object-contain"
                          loading="lazy"
                          decoding="async"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}
            <AppDownloadButtons className="order-3 md:hidden" />

            {/* Right: Copy + Buttons */}
            <div className="order-1 md:order-2">
              <div className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#1D4E89]">
                <span className="h-[2px] w-6 bg-[#3998de]" />
                MOBILE APP
              </div>
              <h2 className="mt-3 text-2xl font-extrabold text-slate-900 md:text-3xl">
                SnoutIQ App for Faster Care
              </h2>

              <p className="mt-3 text-sm leading-relaxed text-slate-500 md:text-base">
                Access veterinary care anytime, anywhere. Download our app for
                instant consultations with verified vets.
              </p>

              <AppDownloadButtons className="hidden md:flex" />

              <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                {[
                  { title: "Quick Nearby Clinic Access", icon: "\u{1F4CD}" },
                  {
                    title: "Vet Available",
                    icon: "\u{1F468}\u200D\u2695\uFE0F",
                  },
                  { title: "Digital Records", icon: "\u{1F4C1}" },
                  { title: "Reminders", icon: "\u23F0" },
                ].map((item) => (
                  <div
                    key={item.title}
                    className="rounded-2xl border border-slate-100 bg-white p-3.5 text-center shadow-sm"
                  >
                    <div className="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-[#3998de]/10 text-base">
                      {item.icon}
                    </div>
                    <div className="mt-2.5 text-xs font-semibold text-slate-700">
                      {item.title}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Why Consult Online */}
      <section id="benefits" className="bg-slate-900 py-12 md:py-14">
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#79BCED]">
            <span className="h-[2px] w-6 bg-[#3998de]" />
            BENEFITS
          </div>
          <h2 className="mt-3 text-2xl font-extrabold text-white md:text-3xl">
            Why Choose Online Vet Consultation?
          </h2>
          <p className="mt-2 max-w-3xl text-sm text-slate-300 md:text-[15px]">
            For many pet health concerns, online consultation provides fast
            clarity without travel or waiting.
          </p>

          <div className="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            {WHY_CONSULT_ONLINE.map((item) => (
              <article
                key={item.title}
                className="rounded-2xl border border-white/15 bg-white/5 p-5"
              >
                <div className="text-2xl">{item.icon}</div>
                <h3 className="mt-3 text-base font-bold text-white">
                  {item.title}
                </h3>
                <p className="mt-2 text-sm leading-6 text-slate-300">
                  {item.desc}
                </p>
              </article>
            ))}
          </div>

          <div className="mt-5">
            <button
              type="button"
              onClick={handleStart}
              disabled={isStartingConsult}
              className="btn-highlight-anim inline-flex items-center gap-1.5 rounded-xl bg-[#3998de] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#2F7FC0] disabled:cursor-not-allowed disabled:opacity-80"
            >
              {isStartingConsult ? "Starting..." : "Talk to a Vet Online"}
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </section>

      {/* How it works */}
      <section id="how-it-works" className="bg-white py-12 md:py-14">
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="inline-flex w-full items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#1D4E89]">
            <span className="h-[2px] w-6 bg-[#3998de]" />
            SIMPLE PROCESS
            <span className="h-[2px] w-6 bg-[#3998de]" />
          </div>
          <h2 className="mt-3 text-center text-2xl font-bold text-slate-900 md:text-3xl">
            How Your Online Pet Consultation Works
          </h2>
          <p className="mx-auto mt-2 max-w-2xl text-center text-sm text-slate-500 md:text-[15px]">
            Four simple steps to connect with a verified vet and get guidance.
          </p>

          <div className="relative mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {HOW_IT_WORKS_STEPS.map((step) => (
              <div
                key={step.n}
                className="rounded-2xl border border-slate-100 bg-slate-50 p-5 text-center shadow-sm"
              >
                <div className="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-[#3998de] text-white">
                  {step.icon}
                </div>
                <div className="mt-3 text-sm font-bold text-[#1D4E89]">
                  Step {step.n}
                </div>
                <h3 className="mt-1 text-base font-bold text-slate-900">
                  {step.title}
                </h3>
                <p className="mt-2 text-sm leading-6 text-slate-500">
                  {step.desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FAQ */}
      <section id="faq" className="bg-white py-9 md:py-10">
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="inline-flex w-full items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#1D4E89]">
            <span className="h-[2px] w-6 bg-[#3998de]" />
            FAQS
            <span className="h-[2px] w-6 bg-[#3998de]" />
          </div>
          <h2 className="mt-3 text-center text-2xl font-bold text-slate-900 md:text-3xl">
            Online Vet Consultation - Questions Pet Parents Ask
          </h2>

          <div className="mx-auto mt-8 max-w-3xl">
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
                    className="btn-highlight-anim flex w-full items-center justify-between bg-slate-50 px-4 py-4 text-left font-semibold text-slate-900 hover:bg-slate-100"
                  >
                    <span className="pr-4">{item.q}</span>
                    <span className="text-xl font-bold text-[#3998de]">
                      {isOpen ? "-" : "+"}
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

      {/* Medical Disclaimer */}
      <section className="border-t-2 border-amber-300 bg-amber-50 py-9 md:py-10">
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="mx-auto max-w-4xl rounded-2xl border border-amber-200 bg-amber-100/50 p-5 text-sm text-amber-900">
            <h3 className="inline-flex items-center gap-2 text-base font-bold">
              ⚠️ Important Medical & Legal Disclaimer
            </h3>
            <p className="mt-3">
              SnoutIQ connects pet owners with licensed veterinarians for remote
              consultations. Online consultations supplement but do not replace
              in-person veterinary care.
            </p>
            <p className="mt-2">
              Online guidance may not be suitable for cases requiring physical
              examination, diagnostic testing, surgery, or emergency treatment.
              In acute emergencies, visit the nearest veterinary clinic
              immediately.
            </p>
            <p className="mt-2">
              Advice is based on information shared during consultation. A
              complete diagnosis may require physical examination and in-clinic
              evaluation.
            </p>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 py-10 text-white">
        <div className="mx-auto max-w-5xl px-4 sm:px-5">
          <div className="grid gap-8 md:grid-cols-4">
            <div>
              <img
                src={logo1}
                alt="SnoutIQ"
                className="h-6 w-auto object-contain drop-shadow-sm md:h-6 lg:h-6"
                loading="lazy"
                decoding="async"
              />
              <p className="mt-3 text-sm leading-6 text-slate-300">
                India&apos;s online vet consultation platform. Talk to a
                verified veterinarian from anywhere, anytime.
              </p>
            </div>

            <div>
              <h4 className="text-sm font-bold uppercase tracking-[0.14em] text-white">
                Connect
              </h4>
              <div className="mt-3 space-y-2 text-sm text-slate-300">
                <a className="block hover:text-white" href="#">
                  Contact Us
                </a>
                <a className="block hover:text-white" href="#">
                  Support
                </a>
                <a className="block hover:text-white" href="#">
                  Feedback
                </a>
              </div>
            </div>

            <div>
              <h4 className="text-sm font-bold uppercase tracking-[0.14em] text-white">
                Legal &amp; Compliance
              </h4>
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

            <div>
              <h4 className="text-sm font-bold uppercase tracking-[0.14em] text-white">
                Platform
              </h4>
              <div className="mt-3 space-y-2 text-sm text-slate-300">
                <a className="block hover:text-white" href="#how-it-works">
                  How It Works
                </a>
                <button
                  type="button"
                  onClick={() =>
                    typeof onVetAccess === "function" ? onVetAccess() : null
                  }
                  className="btn-highlight-anim block rounded-md px-1 py-0.5 text-left hover:text-white"
                >
                  For Veterinarians
                </button>
                <a className="block hover:text-white" href="#faq">
                  FAQs
                </a>
                <a className="block hover:text-white" href="#app-download">
                  App Download
                </a>
              </div>
            </div>
          </div>

          <div className="mt-8 border-t border-white/10 pt-5 text-center text-xs text-slate-400">
            <p>{"\u00A9"} 2026 SnoutIQ. All rights reserved.</p>
            <p className="mt-2">
              Platform for educational and advisory veterinary guidance only.
            </p>
          </div>
        </div>
      </footer>

      {showMobileStickyCta && !activeBioVet ? (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-3 py-3 shadow-[0_-8px_20px_rgba(15,23,42,0.08)] backdrop-blur md:hidden">
          <div className="mx-auto flex max-w-5xl items-center justify-between gap-3">
            <div className="min-w-0">
              <p className="truncate text-sm font-extrabold text-slate-900">
                Talk to a Vet Online Now
              </p>
              <p className="mt-1 flex items-center gap-1 overflow-hidden whitespace-nowrap text-[11px] text-slate-500">
                <span className="font-medium">15 min</span>
                <span className="text-slate-300">·</span>
                <span className="text-[11px] font-semibold text-slate-500 line-through">
                  ₹{originalConsultPrice}
                </span>
                <span className="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-100 px-2 py-0.5 text-emerald-800 shadow-sm">
                  <span className="text-[10px] font-semibold">Pay</span>
                  <span className="ml-1 text-sm font-extrabold leading-none">
                    ₹{discountedConsultPrice}
                  </span>
                </span>
                <span className="text-[11px] font-bold text-rose-700">
                  Save ₹{consultDiscountAmount}
                </span>
              </p>
            </div>
            <button
              type="button"
              onClick={handleStart}
              disabled={isStartingConsult}
              className="btn-highlight-anim inline-flex shrink-0 items-center gap-1 rounded-xl bg-[#3998de] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#2F7FC0] disabled:cursor-not-allowed disabled:opacity-80"
            >
              {isStartingConsult ? "Starting..." : "Talk to a Vet Online"}
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      ) : null}

      <style>
        {`
          @keyframes mobileHeaderTicker {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-50%, 0, 0); }
          }

          .btn-highlight-anim {
            position: relative;
            overflow: hidden;
            transition:
              transform 220ms ease,
              box-shadow 260ms ease,
              filter 260ms ease;
          }

          .btn-highlight-anim::after {
            content: "";
            position: absolute;
            top: 0;
            left: -140%;
            width: 55%;
            height: 100%;
            pointer-events: none;
            background: linear-gradient(
              110deg,
              rgba(255, 255, 255, 0) 0%,
              rgba(255, 255, 255, 0.42) 50%,
              rgba(255, 255, 255, 0) 100%
            );
            transform: skewX(-20deg);
            transition: left 520ms ease;
          }

          .btn-highlight-anim:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(57, 152, 222, 0.26);
            filter: saturate(1.06);
          }

          .btn-highlight-anim:hover::after {
            left: 140%;
          }

          .btn-highlight-anim:active {
            transform: translateY(0) scale(0.98);
          }
        `}
      </style>
    </div>
  );
};

export default LandingScreen;
