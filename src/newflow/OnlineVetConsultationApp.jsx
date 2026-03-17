import React, { useEffect, useRef, useState } from "react";
import { Helmet } from "react-helmet-async";
import { LPNavbar } from "./LPNavbar";
import { apiBaseUrl } from "../lib/api";
import mobileUiPreview from "../assets/mobile UI.jpeg";
import vetOnlinePhoto from "../assets/images/vet_online.jpeg";
import {
  ArrowRight,
  Download,
  Globe,
  Star,
} from "lucide-react";

// SEO
const TITLE = "Snoutiq | Online Vet Consultation App";
const DESCRIPTION =
  "Talk to an online veterinarian in minutes. Video consultation for dogs, cats, birds, and exotic pets. First call free. Download Snoutiq today!";
const CANONICAL = "https://www.snoutiq.com/online-vet-consultation-app";
const APP_DOWNLOAD_URL =
  "https://play.google.com/store/apps/details?id=com.petai.snoutiq";
const DOCTOR_LIST_ENDPOINT = "/api/exported_from_excell_doctors";

// Data
const TRUST_ITEMS = [
  "Verified Veterinarians",
  "Video Consultation",
  "Digital Prescriptions",
  "Works Anywhere in India",
];

const VETS = [
  {
    name: "Dr. Priya Sharma",
    role: "General Physician",
    avatar: "👩‍⚕️",
    badges: ["BVSc & AH", "MVSc Medicine"],
    experience: "8+ Years Experience",
    specialties: "Dogs, Cats, Small Mammals, Nutrition.",
  },
  {
    name: "Dr. Rohan Verma",
    role: "Avian & Exotic Specialist",
    avatar: "👨‍⚕️",
    badges: ["BVSc", "Avian Certification"],
    experience: "10+ Years Experience",
    specialties: "Birds, Parrots, Exotic Pets, Reptiles.",
  },
  {
    name: "Dr. Anjali Desai",
    role: "Veterinary Surgeon",
    avatar: "👩‍⚕️",
    badges: ["BVSc", "MVSc Surgery"],
    experience: "12+ Years Experience",
    specialties: "Rabbits, Guinea Pigs, Post-Op Care.",
  },
  {
    name: "Dr. Vikram Singh",
    role: "Emergency Vet",
    avatar: "👨‍⚕️",
    badges: ["BVSc & AH"],
    experience: "6+ Years Experience",
    specialties: "Dogs, Cats, Triage, Skin Issues.",
  },
];

const URGENT_PROBLEMS = [
  {
    icon: "🐶",
    title: "Dog not eating?",
    desc: "Lethargy, vomiting, diarrhea, or upset stomach.",
  },
  {
    icon: "🐱",
    title: "Cat vomiting?",
    desc: "Hiding, frequent hairballs, or urinary issues.",
  },
  {
    icon: "🦜",
    title: "Bird breathing problems?",
    desc: "Fluffed feathers, tail bobbing, or sitting at the cage bottom.",
  },
  {
    icon: "🐰",
    title: "Rabbit lethargic?",
    desc: "Not moving, not pooping (GI stasis), or hiding.",
  },
];

const STEPS = [
  {
    n: "01",
    title: "Download the Snoutiq App",
    desc: "Available on both Android and iOS devices. Installation takes just seconds.",
  },
  {
    n: "02",
    title: "Add Your Pet's Profile",
    desc: "Enter details about your pet so the doctor understands their history before the call.",
  },
  {
    n: "03",
    title: "Start Video Consultation",
    desc: "Connect instantly with a verified vet. Your first consultation is 100% free!",
  },
];

const cn = (...classes) => classes.filter(Boolean).join(" ");
const BELOW_THE_FOLD_SECTION_STYLE = {
  contentVisibility: "auto",
  containIntrinsicSize: "1px 240px",
};

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
  return (
    normalizeDoctorImageUrl(blob, assetRoot) ||
    normalizeDoctorImageUrl(preferred, assetRoot)
  );
};

const getInitials = (value) => {
  const text = String(value || "").trim();
  if (!text) return "DR";
  const parts = text.split(/\s+/).filter(Boolean);
  const letters = parts.slice(0, 2).map((part) => part[0]);
  return letters.join("").toUpperCase();
};

const normalizeDisplayText = (value) => {
  if (value === undefined || value === null) return "";
  const text = String(value).trim();
  if (!text) return "";
  const lower = text.toLowerCase();
  if (
    lower === "null" ||
    lower === "undefined" ||
    lower === "[]" ||
    lower === "na" ||
    lower === "n/a"
  ) {
    return "";
  }
  return text;
};

const listToDisplayText = (value) => {
  if (Array.isArray(value)) {
    return value
      .map((item) => normalizeDisplayText(item))
      .filter(Boolean)
      .join(", ");
  }

  const text = normalizeDisplayText(value);
  if (!text) return "";

  if (text.startsWith("[") && text.endsWith("]")) {
    try {
      const parsed = JSON.parse(text);
      if (Array.isArray(parsed)) {
        return parsed
          .map((item) => normalizeDisplayText(item))
          .filter(Boolean)
          .join(", ");
      }
    } catch {
      return text.replace(/^\[|\]$/g, "").replace(/["']/g, "").trim();
    }
  }

  return text;
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

function PrimaryButton({
  children,
  href = APP_DOWNLOAD_URL,
  onClick,
  className = "",
  light = false,
}) {
  return href ? (
    <a
      href={href}
      className={cn(
        "inline-flex w-full max-w-[380px] items-center justify-center gap-2 rounded-2xl px-6 py-4 text-base font-extrabold shadow-lg transition-all",
        light
          ? "bg-white text-brand shadow-black/10 hover:-translate-y-0.5"
          : "bg-accent text-white shadow-orange-200/60 hover:bg-accent-hover hover:-translate-y-0.5",
        className
      )}
    >
      {children}
    </a>
  ) : (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "inline-flex w-full max-w-[380px] items-center justify-center gap-2 rounded-2xl px-6 py-4 text-base font-extrabold shadow-lg transition-all",
        light
          ? "bg-white text-brand shadow-black/10 hover:-translate-y-0.5"
          : "bg-accent text-white shadow-orange-200/60 hover:bg-accent-hover hover:-translate-y-0.5",
        className
      )}
    >
      {children}
    </button>
  );
}

function VetCard({ vet }) {
  return (
    <article className="min-w-[240px] snap-start overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:min-w-0">
      <div className="relative h-40 bg-slate-100">
        {vet.image ? (
          <img
            src={vet.image}
            alt={vet.name}
            className="h-full w-full bg-white p-2 object-contain object-center"
            loading="lazy"
            decoding="async"
            fetchpriority="low"
            width="320"
            height="160"
            sizes="(min-width: 1024px) 25vw, (min-width: 768px) 50vw, 240px"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center bg-slate-200 text-xl font-bold text-slate-600">
            {getInitials(vet.name)}
          </div>
        )}
        {vet.rating ? (
          <span className="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-1 text-xs font-semibold text-slate-700 shadow">
            <Star className="h-3.5 w-3.5 text-amber-400" />
            {vet.rating}
          </span>
        ) : null}
      </div>

      <div className="space-y-2 p-4">
        <div>
          <p className="text-sm font-extrabold text-slate-900">{vet.name}</p>
          <p className="text-xs text-slate-500">
            {vet.degree || "Veterinary Doctor"}
            {vet.experience ? ` · ${vet.experience} yrs` : ""}
          </p>
        </div>
        {vet.specialization ? (
          <p className="line-clamp-2 text-xs text-slate-600">
            {listToDisplayText(vet.specialization)}
          </p>
        ) : null}
        <div className="flex items-center justify-between text-xs text-slate-500">
          <span>{vet.responseDay || "0-15 mins"}</span>
        </div>
      </div>
    </article>
  );
}

function HeroShowcase() {
  return (
    <div className="relative mx-auto max-w-[560px]">
      <div className="absolute -left-8 top-10 h-32 w-32 rounded-full bg-sky-200/50 blur-3xl" />
      <div className="absolute -right-6 bottom-10 h-36 w-36 rounded-full bg-orange-200/40 blur-3xl" />

      <div className="relative overflow-hidden rounded-[30px] border border-white/70 bg-white/80 p-4 shadow-[0_28px_80px_-36px_rgba(37,99,235,0.45)] backdrop-blur">
        <div className="grid gap-4 sm:grid-cols-[1.1fr_0.9fr]">
          <div className="relative overflow-hidden rounded-[24px] bg-slate-100 shadow-inner">
            <img
              src={vetOnlinePhoto}
              alt="Veterinarian examining a dog"
              className="h-[260px] w-full object-cover object-center sm:h-full"
              loading="eager"
              decoding="async"
            />

            <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/75 via-slate-950/10 to-transparent p-5 text-white">
              <p className="text-[11px] font-bold uppercase tracking-[0.18em] text-sky-200">
                Trusted Consults
              </p>
              <p className="mt-1 text-lg font-extrabold">Real vets. Real video help.</p>
              <p className="mt-1 text-sm text-white/80">
                Talk to an experienced doctor without the clinic wait.
              </p>
            </div>
          </div>

          <div className="flex flex-col gap-4">
            <div className="rounded-[24px] bg-gradient-to-br from-brand to-[#355bc3] p-5 text-white shadow-lg shadow-blue-900/20">
              <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-white/75">
                Snoutiq App
              </p>
              <h3 className="mt-2 text-2xl font-extrabold leading-tight">
                Book, consult, and follow up from one place
              </h3>

              <div className="mt-4 grid grid-cols-2 gap-2 text-sm font-semibold">
                <div className="rounded-2xl bg-white/12 px-3 py-3">Video call</div>
                <div className="rounded-2xl bg-white/12 px-3 py-3">Fast access</div>
                <div className="rounded-2xl bg-white/12 px-3 py-3">Doctor notes</div>
                <div className="rounded-2xl bg-white/12 px-3 py-3">Pan India</div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  );
}

function PageFooter() {
  return (
    <footer
      className="border-t border-slate-100 bg-white px-4 py-4 pb-28 md:pb-4"
      style={BELOW_THE_FOLD_SECTION_STYLE}
    >
      <p className="text-center text-xs text-slate-400">
        © {new Date().getFullYear()} SnoutIQ ·{" "}
        <a href="/" className="font-semibold hover:text-brand">
          Home
        </a>{" "}
        ·{" "}
        <a href="/privacy-policy" className="hover:text-brand">
          Privacy Policy
        </a>
      </p>
    </footer>
  );
}

export default function OnlineVetConsultationApp() {
  const heroRef = useRef(null);
  const vetsSectionRef = useRef(null);
  const [showStickyCta, setShowStickyCta] = useState(false);
  const [featuredVets, setFeaturedVets] = useState([]);
  const [vetsLoading, setVetsLoading] = useState(false);
  const [vetsError, setVetsError] = useState("");
  const [shouldLoadFeaturedVets, setShouldLoadFeaturedVets] = useState(false);
  const goToAppDownload = () => {
    window.location.assign(APP_DOWNLOAD_URL);
  };

  useEffect(() => {
    const key = "snoutiq_web_first_open_v1";
    const alreadyTracked = localStorage.getItem(key);

    if (!alreadyTracked) {
      if (typeof window.gtag === "function") {
        window.gtag("event", "web_first_open", {
          page_name: "online_vet_consultation_app",
          platform: "web",
        });
      }
      localStorage.setItem(key, "1");
    }
  }, []);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        setShowStickyCta(!entry.isIntersecting);
      },
      { threshold: 0.18 }
    );

    if (heroRef.current) observer.observe(heroRef.current);

    return () => {
      if (heroRef.current) observer.unobserve(heroRef.current);
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
            { rootMargin: "320px 0px" }
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
          (doc) => doc?.doctor_name || doc?.doctor_email || doc?.doctor_mobile
        );

        const prioritized = [...cleaned].sort((a, b) => {
          const aIsShashank = isDrShashankVet(a?.doctor_name);
          const bIsShashank = isDrShashankVet(b?.doctor_name);
          if (aIsShashank && !bIsShashank) return -1;
          if (!aIsShashank && bIsShashank) return 1;
          return 0;
        });

        const topFour = prioritized.slice(0, 4).map((doc) => ({
          id: doc?.id || doc?.doctor_id || doc?.doctor_mobile,
          name: doc?.doctor_name || "Veterinarian",
          degree: doc?.degree || "",
          experience: doc?.years_of_experience || "",
          specialization: doc?.specialization_select_all_that_apply || "",
          responseDay: doc?.response_time_for_online_consults_day || "",
          rating: doc?.average_review_points || "",
          image: resolveDoctorImage(doc, assetRoot),
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
    <div className="min-h-screen bg-white text-slate-900">
      <Helmet>
        <title>{TITLE}</title>
        <meta name="description" content={DESCRIPTION} />
        <meta name="robots" content="noindex, nofollow" />
        <link rel="canonical" href={CANONICAL} />
      </Helmet>

      <LPNavbar onConsultClick={goToAppDownload} />
            {/* Trust Strip */}
      <section className="bg-[#067a5f]">
        <div className="mx-auto max-w-6xl px-4 py-2">
          <div className="flex flex-nowrap items-center justify-center gap-3 overflow-x-auto whitespace-nowrap text-[11px] font-semibold text-white sm:text-sm [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            {TRUST_ITEMS.map((item, index) => (
              <React.Fragment key={item}>
                {index > 0 ? (
                  <span className="shrink-0 text-white/70">·</span>
                ) : null}
                <span className="inline-flex shrink-0 items-center gap-1.5">
                  <span>{item}</span>
                </span>
              </React.Fragment>
            ))}
          </div>
        </div>
      </section>

      {/* Header */}
      <header className="hidden sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
          <a
            href="/"
            className="flex items-center gap-2 text-xl font-extrabold text-brand"
          >
            <span className="text-2xl">🐾</span>
            <span>Snoutiq</span>
          </a>

          <a
            href={APP_DOWNLOAD_URL}
            className="hidden rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white transition hover:opacity-95 md:inline-flex"
          >
            Download App
          </a>
        </div>
      </header>

      {/* Hero */}
      <section
        ref={heroRef}
        className="relative overflow-hidden bg-gradient-to-b from-[#f0f7ff] to-white px-4 py-10 sm:py-14"
      >
        <div className="pointer-events-none absolute inset-0 overflow-hidden">
          <div className="absolute right-0 top-0 h-72 w-72 translate-x-1/4 -translate-y-1/3 rounded-full bg-brand/5 blur-3xl" />
          <div className="absolute bottom-0 left-0 h-48 w-48 rounded-full bg-orange-100/50 blur-2xl" />
        </div>

        <div className="relative mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-2 lg:gap-10">
          <div className="text-center lg:text-left">
            <span className="mb-4 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.14em] text-amber-700">
              🎁 First Video Consultation Free
            </span>

            <h1 className="text-[2.1rem] font-extrabold leading-tight text-slate-900 sm:text-[2.7rem] lg:text-[3.2rem]">
              Online Vet Consultation in{" "}
              <span className="text-accent">Minutes</span>
            </h1>

            <p className="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-500 lg:mx-0 lg:text-lg">
              Talk to a pet doctor instantly. Expert video consultations for{" "}
              <strong className="text-slate-700">
                dogs, cats, birds, rabbits, and exotic pets
              </strong>{" "}
              straight from your phone.
            </p>

            <div className="mt-7 flex justify-center lg:justify-start">
              <PrimaryButton>
                <Download className="h-5 w-5" />
                <span>Download App</span>
                <ArrowRight className="h-4 w-4" />
              </PrimaryButton>
            </div>

            <div className="mt-5 flex flex-wrap items-center justify-center gap-2 text-sm font-semibold text-slate-500 lg:justify-start">
              <span className="text-emerald-600">✓ Free</span>
              <span>First Consultation</span>
              <span className="text-slate-300">•</span>
              <span>Follow-ups from ₹399</span>
            </div>
          </div>

          <HeroShowcase />
          <div className="hidden">
            <div className="overflow-hidden rounded-[24px] border border-white bg-white shadow-[0_24px_80px_-40px_rgba(15,23,42,0.35)]">
              <div className="flex min-h-[360px] flex-col items-center justify-center gap-5 rounded-[24px] bg-gradient-to-br from-brand to-[#312e81] px-8 py-10 text-center text-white">
                <div className="text-6xl">🩺📱</div>
                <h3 className="text-2xl font-extrabold">
                  Live Pet Doctor Online
                </h3>
                <p className="max-w-md text-sm leading-7 text-white/85 sm:text-base">
                  Skip the clinic waiting room. Connect face-to-face with top
                  veterinarians instantly.
                </p>

                <div className="mt-2 grid w-full max-w-md grid-cols-1 gap-3 sm:grid-cols-3">
                  <div className="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold">
                    Video Call
                  </div>
                  <div className="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold">
                    Fast Access
                  </div>
                  <div className="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold">
                    India Wide
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>



      {/* Vet Profiles */}
      <section
        ref={vetsSectionRef}
        className="bg-slate-50 px-4 py-14"
        style={BELOW_THE_FOLD_SECTION_STYLE}
      >
        <div className="mx-auto max-w-6xl">
          <div className="mx-auto mb-8 max-w-4xl text-center">
            <h2 className="text-2xl font-extrabold text-slate-900 sm:text-4xl">
              Online Vet Consultation for Dogs, Cats & Exotic Pets
            </h2>
            <p className="mt-3 text-base leading-7 text-slate-500 sm:text-lg">
              Looking for an <strong>online veterinarian</strong> or want to{" "}
              <strong>talk to a vet online</strong>? Snoutiq lets you consult a
              vet online in India through a quick, secure video call. Get
              professional medical advice from the comfort of your home.
            </p>
          </div>

          {showVetLoadingState ? (
            <div className="flex gap-4 overflow-x-auto pb-2 -mx-2 px-2 snap-x snap-mandatory md:grid md:grid-cols-2 lg:grid-cols-4 md:gap-5 md:overflow-visible md:mx-0 md:px-0">
              {Array.from({ length: 4 }).map((_, index) => (
                <div
                  key={index}
                  className="min-w-[240px] snap-start overflow-hidden rounded-2xl border border-slate-200 md:min-w-0"
                >
                  <div className="h-40 animate-pulse bg-slate-100" />
                  <div className="space-y-2 p-4">
                    <div className="h-4 animate-pulse rounded bg-slate-100" />
                    <div className="h-3 w-4/5 animate-pulse rounded bg-slate-100" />
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
                <VetCard key={vet.id} vet={vet} />
              ))}
            </div>
          )}

          <div className="mt-8 flex justify-center">
            <PrimaryButton>
              <Download className="h-5 w-5" />
              <span>Download App to Consult</span>
            </PrimaryButton>
          </div>
        </div>
      </section>

      {/* Urgent Problems */}
      <section className="bg-white px-4 py-14">
        <div className="mx-auto max-w-6xl">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-2xl font-extrabold text-slate-900 sm:text-4xl">
              Is your pet suddenly feeling sick?
            </h2>
            <p className="mt-3 text-base leading-7 text-slate-500 sm:text-lg">
              Don&apos;t panic. Get immediate answers from an experienced pet
              doctor online.
            </p>
          </div>

          <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {URGENT_PROBLEMS.map((item) => (
              <div
                key={item.title}
                className="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm"
              >
                <div className="mb-3 text-4xl">{item.icon}</div>
                <h3 className="mb-2 text-lg font-extrabold text-slate-900">
                  {item.title}
                </h3>
                <p className="text-sm leading-6 text-slate-500">{item.desc}</p>
              </div>
            ))}
          </div>

          <div className="mt-10 rounded-2xl border-2 border-dashed border-orange-300 bg-orange-50 px-5 py-8 text-center">
            <h3 className="mb-5 text-2xl font-extrabold text-slate-900">
              Talk to a Vet Now
            </h3>
            <div className="flex justify-center">
              <PrimaryButton>
                <Download className="h-5 w-5" />
                <span>Download App</span>
              </PrimaryButton>
            </div>
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="bg-slate-50 px-4 py-14">
        <div className="mx-auto max-w-5xl">
          <div className="text-center">
            <p className="mb-2 text-xs font-extrabold uppercase tracking-[0.16em] text-brand">
              HOW IT WORKS
            </p>
            <h2 className="text-2xl font-extrabold text-slate-900 sm:text-4xl">
              How Snoutiq Works
            </h2>
            <p className="mt-3 text-base text-slate-500 sm:text-lg">
              3 simple steps to get your pet the care they deserve.
            </p>
          </div>

          <div className="mx-auto mt-10 max-w-4xl space-y-4">
            {STEPS.map((step) => (
              <div
                key={step.n}
                className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center"
              >
                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand text-sm font-extrabold text-white">
                  {step.n}
                </div>

                <div>
                  <h3 className="text-lg font-extrabold text-slate-900">
                    {step.title}
                  </h3>
                  <p className="mt-1 text-sm leading-6 text-slate-500">
                    {step.desc}
                  </p>
                </div>
              </div>
            ))}
          </div>

          <div className="mt-8 flex justify-center">
            <PrimaryButton>
              <Download className="h-5 w-5" />
              <span>Download App</span>
            </PrimaryButton>
          </div>
        </div>
      </section>

      {/* Access Section */}
      <section className="bg-white px-4 py-6">
        <div className="mx-auto max-w-6xl">
          <div className="overflow-hidden rounded-[28px] bg-gradient-to-br from-[#312e81] to-brand px-6 py-12 text-center text-white shadow-[0_24px_80px_-40px_rgba(15,23,42,0.5)] sm:px-10">
            <div className="mx-auto max-w-3xl">
              <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.16em] text-white/90">
                <Globe className="h-4 w-4" />
                Pan India Access
              </div>

              <h2 className="text-3xl font-extrabold text-white sm:text-4xl">
                No Experienced Vet Nearby?
              </h2>

              <p className="mx-auto mt-4 max-w-2xl text-base leading-7 text-white/85 sm:text-lg">
                Many places in India lack veterinary specialists, especially for
                birds and exotic pets. Snoutiq allows you to{" "}
                <strong>consult a vet online anywhere in India</strong>. No
                travel required, zero waiting room stress, and immediate access
                to top doctors.
              </p>

              <div className="mt-8 flex justify-center">
                <PrimaryButton light>
                  <Download className="h-5 w-5" />
                  <span>Download App</span>
                </PrimaryButton>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Final CTA */}
      <section id="download" className="bg-white px-4 py-14">
        <div className="mx-auto max-w-4xl text-center">
          <span className="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.14em] text-brand">
            Your First Call is Free
          </span>

          <h2 className="mt-5 text-3xl font-extrabold text-slate-900 sm:text-5xl">
            Your Pet Needs Help Now
          </h2>

          <p className="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-500 sm:text-xl">
            Don&apos;t wait. Speak to a verified online veterinarian today.
          </p>

          <div className="mt-8 flex justify-center">
            <PrimaryButton className="scale-[1.03]">
              <Download className="h-5 w-5" />
              <span>Download App</span>
            </PrimaryButton>
          </div>

          <p className="mt-6 text-sm font-semibold text-slate-500">
            Available for iOS and Android.
          </p>
        </div>
      </section>

      <PageFooter />

      {/* Sticky mobile CTA */}
      {showStickyCta && (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-2xl backdrop-blur md:hidden">
          <a
            href={APP_DOWNLOAD_URL}
            className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-4 text-base font-extrabold text-white shadow-lg shadow-orange-200/60 transition hover:bg-accent-hover"
          >
            <Download className="h-5 w-5" />
            <span>Download App (Free Call)</span>
          </a>
        </div>
      )}
    </div>
  );
}
