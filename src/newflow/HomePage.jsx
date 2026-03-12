import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import * as k from "react";
import { Link as b } from "react-router-dom";
import {
  ArrowRight as v,
  Button as w,
  CalendarDays as X,
  ChevronDown as K,
  FileText as R,
  Footer as G,
  HeartPulse as $,
  MapPin as Y,
  Navbar as Q,
  PawPrint as U,
  Scissors as O,
  ServiceCard as A,
  ShieldCheck as H,
  Smartphone as P,
  Stethoscope as E,
  Syringe as W,
  Video as _,
} from "./HomePageChrome";
const e = { jsx: _jsx, jsxs: _jsxs };
const N =
    "https://play.google.com/store/apps/details?id=com.petai.snoutiq&hl=en_IN",
  C = "/20+vetsonline?start=details",
  q = "/backend/api/exported_from_excell_doctors",
  M = "https://snoutiq.com/backend/api/exported_from_excell_doctors",
  ee = "https://snoutiq.com",
  te = [
    {
      name: "Dr. Priya Menon",
      specialty: "Small Animal Care",
      exp: "9 Yrs",
      location: "Delhi",
      rating: "4.9",
      count: "1,200+",
    },
    {
      name: "Dr. Arjun Kapoor",
      specialty: "Feline Specialist",
      exp: "11 Yrs",
      location: "Mumbai",
      rating: "4.8",
      count: "2,100+",
    },
    {
      name: "Dr. Sneha Iyer",
      specialty: "Surgery & Ortho",
      exp: "8 Yrs",
      location: "Bangalore",
      rating: "5.0",
      count: "890+",
    },
    {
      name: "Dr. Rohit Sharma",
      specialty: "Exotic & Wildlife",
      exp: "12 Yrs",
      location: "Pune",
      rating: "4.9",
      count: "1,500+",
    },
    {
      name: "Dr. Kavya Nair",
      specialty: "Dermatology",
      exp: "7 Yrs",
      location: "Chennai",
      rating: "4.8",
      count: "760+",
    },
    {
      name: "Dr. Amit Bose",
      specialty: "Internal Care",
      exp: "14 Yrs",
      location: "Kolkata",
      rating: "4.9",
      count: "3,200+",
    },
  ],
  L = [
    ["from-blue-100 to-indigo-100", "text-indigo-600"],
    ["from-emerald-100 to-teal-100", "text-teal-600"],
    ["from-rose-100 to-pink-100", "text-rose-600"],
    ["from-amber-100 to-orange-100", "text-orange-600"],
    ["from-violet-100 to-purple-100", "text-violet-600"],
    ["from-cyan-100 to-sky-100", "text-sky-600"],
  ],
  se = "India",
  f = (a, t = "") => {
    if (a == null) return t;
    const s = String(a).trim();
    if (!s) return t;
    const r = s.toLowerCase();
    return r === "null" || r === "undefined" || r === "[]" ? t : s;
  },
  I = (a) => {
    if (!a) return [];
    if (Array.isArray(a)) return a.map((s) => f(s)).filter(Boolean);
    const t = f(a);
    if (!t) return [];
    if (t.startsWith("[") && t.endsWith("]"))
      try {
        const s = JSON.parse(t);
        if (Array.isArray(s)) return s.map((r) => f(r)).filter(Boolean);
      } catch {}
    return t
      .split(",")
      .map((s) => f(s))
      .filter(Boolean);
  },
  ae = (a) => {
    const t = f(a, "Vet").replace(/\s+/g, " ");
    return /^dr\.?\s/i.test(t) ? t.replace(/^dr\.?\s*/i, "Dr. ") : `Dr. ${t}`;
  },
  ne = (a) => {
    const t = Number(a);
    return !Number.isFinite(t) || t <= 0
      ? "N/A"
      : `${Number.isInteger(t) ? String(t) : t.toFixed(1)} Yrs`;
  },
  re = (a) => {
    const t = I(a);
    if (!t.length) return "General Practice";
    const s = new Set([
      "dogs",
      "cats",
      "exotic pet",
      "exotic pets",
      "livestock",
    ]);
    return (
      t.find((n) => !s.has(n.toLowerCase().trim())) ||
      t[0] ||
      "General Practice"
    );
  },
  ie = (a) =>
    a?.doctor_image_blob_url || a?.doctor_image_url || a?.doctor_image || "",
  le = (a) => {
    const t = f(a);
    return t
      ? t.includes("https://snoutiq.com/https://snoutiq.com/")
        ? t.replace(
            "https://snoutiq.com/https://snoutiq.com/",
            "https://snoutiq.com/",
          )
        : /^(https?:)?\/\//i.test(t) || t.startsWith("data:")
          ? t
          : `${ee}/${t.replace(/^\/+/, "")}`
      : "";
  },
  oe = () =>
    typeof window > "u"
      ? [M, `https://www.snoutiq.com${q}`]
      : Array.from(
          new Set([
            `${window.location.origin}${q}`,
            M,
            `https://www.snoutiq.com${q}`,
          ]),
        ),
  ce = (a = []) => {
    const t = [],
      s = new Set();
    return (
      a.forEach((r) => {
        (r?.doctors || []).forEach((n) => {
          const o = Number(n?.id);
          if (Number.isFinite(o) && s.has(o)) return;
          const x = Number(n?.video_day_rate),
            d = Number(n?.video_night_rate);
          if ((Number.isFinite(x) && x <= 2) || (Number.isFinite(d) && d <= 2))
            return;
          Number.isFinite(o) && s.add(o);
          const c = Number(n?.average_review_points),
            j = Math.max(0, Number(n?.reviews_count) || 0),
            y = I(n?.specialization_select_all_that_apply);
          t.push({
            name: ae(n?.doctor_name),
            specialty: re(n?.specialization_select_all_that_apply),
            specializationList: y,
            exp: ne(n?.years_of_experience),
            location: se,
            rating: Number.isFinite(c) && c > 0 ? c.toFixed(1) : "New",
            count: `${j}+`,
            image: le(ie(n)),
          });
        });
      }),
      t.slice(0, 12)
    );
  },
  F = (a) => {
    const t = f(a).toLowerCase();
    return t.includes("surgery") || t.includes("ortho")
      ? { icon: O, color: "text-rose-600", bg: "bg-rose-50 border-rose-100" }
      : t.includes("skin") || t.includes("dermatology")
        ? {
            icon: H,
            color: "text-purple-600",
            bg: "bg-purple-50 border-purple-100",
          }
        : t.includes("general practice")
          ? {
              icon: E,
              color: "text-blue-600",
              bg: "bg-blue-50 border-blue-100",
            }
          : t.includes("homeopathy")
            ? {
                icon: $,
                color: "text-pink-600",
                bg: "bg-pink-50 border-pink-100",
              }
            : t.includes("diet") || t.includes("nutrition")
              ? {
                  icon: R,
                  color: "text-amber-600",
                  bg: "bg-amber-50 border-amber-100",
                }
              : t.includes("dog") ||
                  t.includes("cat") ||
                  t.includes("exotic") ||
                  t.includes("livestock")
                ? {
                    icon: U,
                    color: "text-teal-600",
                    bg: "bg-teal-50 border-teal-100",
                  }
                : {
                    icon: E,
                    color: "text-slate-600",
                    bg: "bg-slate-50 border-slate-200",
                  };
  };

function getCurrentPrice() {
  return {
    amount: 499,
    finalAmount: 399,
    label: "Online consult · Available Day & Night",
    rateType: "flat",
  };
}
const PAYMENT_AMOUNTS = {
    standard: 499,
    discounted: 399,
  },
  formatInr = (value) => {
    const n = Number(value);
    if (!Number.isFinite(n)) return "0";
    return n.toLocaleString("en-IN", {
      minimumFractionDigits: Number.isInteger(n) ? 0 : 2,
      maximumFractionDigits: 2,
    });
  },
  B = [
    {
      q: "Can a video call replace a clinic visit?",
      a: "Video consultations are excellent for triage, minor ailments, behavioural advice, and second opinions. For emergencies, physical exams, or surgery, a clinic visit is necessary - and our app helps you find the nearest verified clinic instantly.",
    },
    {
      q: "How do I pay for the consultation?",
      a: "Securely via UPI, Credit/Debit Cards, or Netbanking before the consultation. The standard consultation price is ₹499, and after ₹100 off you pay ₹399. The same pricing applies during day and night.",
    },
    {
      q: "Are the vets qualified?",
      a: "Every vet on SnoutIQ has a minimum of 7 years clinical experience and goes through a verification process before joining. You'll see their credentials and ratings before you book.",
    },
    {
      q: "How fast can I connect with a vet?",
      a: "Average wait is under 15 minutes. Vets are available 24/7 across India.",
    },
    {
      q: "Does the app work outside Delhi NCR?",
      a: "Online video consultations are available pan-India. Clinic bookings, vaccination packages, and neuter/spay services are currently Delhi NCR only - more cities coming soon.",
    },
  ],
  de = {
    "@context": "https://schema.org",
    "@type": "MedicalBusiness",
    name: "SnoutiQ",
    url: "https://www.snoutiq.com",
    logo: "https://www.snoutiq.com/logo.png",
    description:
      "Online vet consultation platform in India offering 24/7 video consultations, follow up, and clinic bookings.",
    areaServed: "India",
    availableService: {
      "@type": "MedicalService",
      name: "Online Veterinary Consultation",
      serviceType: "Online Veterinary Consultation",
      areaServed: "India",
    },
  },
  xe = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: B.map((a) => ({
      "@type": "Question",
      name: a.q,
      acceptedAnswer: { "@type": "Answer", text: a.a },
    })),
  };
const SEO_TITLE_BASE =
  "Trusted Online Vet Consultation India | Connect Within 15 Minutes";
const SEO_TITLE_DEFAULT = `${SEO_TITLE_BASE} | SnoutIQ`;
const SEO_TITLE_TEMPLATE = "%s | SnoutIQ";
const SEO_DESCRIPTION =
  "SnoutIQ is a trusted pet healthcare platform in India. Start an online vet consultation and connect with a certified veterinary doctor within 15 minutes for personalized care guidance and follow-up care guidance.";
const SEO_KEYWORDS = [
  "online vet consultation india",
  "pet healthcare platform india",
  "vet online india",
  "veterinary doctor india",
  "pet doctor online",
  "online veterinarian",
  "veterinary clinic india",
  "pet care india",
  "best veterinary doctor in india",
].join(", ");
const SEO_CANONICAL = "https://www.snoutiq.com/";
const SEO_OG_IMAGE = "https://www.snoutiq.com/og-image.jpg";
const SEO_OG_IMAGE_ALT = "SnoutIQ Online Vet Consultation India";
const SEO_OWNER_ATTR = "data-homepage-seo";

function DynamicConsultLabel({
  amount: a,
  finalAmount: t,
  prefixText: r = "Consult a Vet Now",
}) {
  return e.jsxs("span", {
    className:
      "inline-flex max-w-full flex-wrap items-center justify-center gap-x-2 gap-y-1.5 text-center align-middle leading-tight sm:flex-nowrap",
    title: "Flat online consultation pricing active",
    children: [
      e.jsx("span", { className: "text-base leading-none", children: "⚡" }),
      e.jsx("span", { className: "font-extrabold text-white", children: r }),
      e.jsxs("span", {
        className:
          "whitespace-nowrap line-through text-sm font-bold text-white/70",
        children: ["₹", formatInr(a)],
      }),
      e.jsx("span", {
        className:
          "whitespace-nowrap rounded-full bg-yellow-300 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-900",
        children: "₹100 OFF",
      }),
      e.jsxs("span", {
        className:
          "whitespace-nowrap rounded-full bg-white px-3 py-1 text-sm font-black text-orange-600 shadow-sm",
        children: ["Now ₹", formatInr(t)],
      }),
    ],
  });
}

function S({ icon: a, title: t, desc: s, tag: r }) {
  return e.jsxs("div", {
    className:
      "group relative flex gap-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm transition-all duration-300 hover:border-brand/30 hover:shadow-md sm:p-5",
    children: [
      e.jsx("div", {
        className:
          "shrink-0 h-12 w-12 rounded-2xl bg-brand-light flex items-center justify-center group-hover:bg-brand/20 transition-colors",
        children: e.jsx(a, { className: "h-6 w-6 text-brand" }),
      }),
      e.jsxs("div", {
        children: [
          e.jsxs("div", {
            className: "flex items-center gap-2 mb-1",
            children: [
              e.jsx("h3", {
                className: "font-bold text-slate-900 text-base",
                children: t,
              }),
              r &&
                e.jsx("span", {
                  className:
                    "text-[10px] font-bold bg-brand-light text-brand px-2 py-0.5 rounded-full",
                  children: r,
                }),
            ],
          }),
          e.jsx("p", {
            className: "text-slate-500 text-sm leading-relaxed",
            children: s,
          }),
        ],
      }),
    ],
  });
}
function me({ vet: a, idx: t }) {
  const [s, r] = L[t % L.length],
    [n, o] = k.useState(!1),
    [x, d] = k.useState(!1),
    c = k.useRef(null),
    j = !!a.image && !n && x,
    y =
      Array.isArray(a.specializationList) && a.specializationList.length
        ? a.specializationList
        : I(a.specialty),
    i = F(a.specialty),
    l = i.icon,
    m = a.name
      .replace(/^Dr\.?\s*/i, "")
      .split(" ")
      .slice(0, 2)
      .map((p) => p[0])
      .join("")
      .toUpperCase();
  k.useEffect(() => {
    if (!a.image || x) return;
    if (typeof window > "u") return;

    const p = c.current;
    if (!p) return;

    if (!("IntersectionObserver" in window)) {
      d(!0);
      return;
    }

    const h = new IntersectionObserver(
      (u, g) => {
        if (u.some((v) => v.isIntersecting)) {
          d(!0);
          g.disconnect();
        }
      },
      { rootMargin: "160px 0px" },
    );
    h.observe(p);

    return () => h.disconnect();
  }, [a.image, x]);
  return e.jsxs("div", {
    ref: c,
    className:
      "snap-start shrink-0 w-[220px] sm:w-56 rounded-3xl overflow-hidden border border-slate-100 bg-white shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer",
    children: [
      e.jsxs("div", {
        className: `relative h-52 bg-gradient-to-br ${s} flex flex-col items-center justify-center`,
        children: [
          e.jsx("div", {
            className:
              "h-24 w-24 rounded-full bg-white/70 backdrop-blur border-4 border-white shadow-lg flex items-center justify-center mb-2",
            children: j
              ? e.jsx("img", {
                  src: a.image,
                  alt: a.name,
                  loading: "lazy",
                  decoding: "async",
                  fetchpriority: "low",
                  width: 96,
                  height: 96,
                  onError: () => o(!0),
                  className: "h-full w-full rounded-full object-cover",
                })
              : e.jsx("span", {
                  className: `text-3xl font-black ${r}`,
                  children: m || "V",
                }),
          }),
          e.jsxs("div", {
            className:
              "absolute top-3 right-3 bg-white rounded-full px-2.5 py-1 flex items-center gap-1 shadow text-xs font-bold text-slate-800",
            children: [
              e.jsx("span", { className: "text-yellow-400", children: "★" }),
              a.rating,
            ],
          }),
          e.jsxs("span", {
            className:
              "text-[11px] font-semibold text-slate-500 bg-white/80 rounded-full px-2.5 py-0.5",
            children: [a.count, " consults"],
          }),
        ],
      }),
      e.jsxs("div", {
        className: "p-4",
        children: [
          e.jsx("p", {
            className: "font-bold text-slate-900 text-sm leading-tight",
            children: a.name,
          }),
          e.jsxs("p", {
            className: `inline-flex items-center gap-1 text-xs font-semibold mt-0.5 ${r}`,
            children: [
              e.jsx(l, { className: `h-3.5 w-3.5 ${i.color}` }),
              a.specialty,
            ],
          }),
          e.jsx("div", {
            className: "mt-2 flex flex-wrap gap-1.5",
            children: y.map((p, h) => {
              const { icon: u, color: g, bg: v } = F(p);
              return e.jsx(
                "span",
                {
                  title: p,
                  className: `inline-flex h-6 w-6 items-center justify-center rounded-full border ${v}`,
                  children: e.jsx(u, { className: `h-3.5 w-3.5 ${g}` }),
                },
                `${p}-${h}`,
              );
            }),
          }),
          e.jsxs("div", {
            className: "flex gap-2 mt-3",
            children: [
              e.jsx("span", {
                className:
                  "text-[11px] bg-slate-100 text-slate-500 rounded-full px-2 py-0.5",
                children: a.exp,
              }),
              e.jsxs("span", {
                className:
                  "text-[11px] bg-slate-100 text-slate-500 rounded-full px-2 py-0.5",
                children: ["Location: ", a.location],
              }),
            ],
          }),
        ],
      }),
    ],
  });
}
function pe() {
  return e.jsx("section", {
    "data-stats-section": !0,
    className: "bg-brand py-12 sm:py-14",
    children: e.jsx("div", {
      className: "mx-auto max-w-5xl px-4 sm:px-6 lg:px-8",
      children: e.jsxs("div", {
        className: "grid grid-cols-2 md:grid-cols-4 gap-6 text-center",
        children: [
          e.jsxs("div", {
            children: [
              e.jsx("p", {
                className:
                  "text-3xl sm:text-4xl font-extrabold text-white tracking-tight",
                "data-counter": !0,
                "data-target": "200",
                "data-duration": "1600",
                "data-suffix": "+",
                children: "0",
              }),
              e.jsx("p", {
                className: "text-blue-200 text-sm mt-1 font-medium",
                children: "Happy Pet Parents",
              }),
            ],
          }),
          e.jsxs("div", {
            children: [
              e.jsx("p", {
                className:
                  "text-3xl sm:text-4xl font-extrabold text-white tracking-tight",
                "data-counter": !0,
                "data-target": "100",
                "data-duration": "1400",
                "data-suffix": "+",
                children: "0",
              }),
              e.jsx("p", {
                className: "text-blue-200 text-sm mt-1 font-medium",
                children: "Verified Vets",
              }),
            ],
          }),
          e.jsxs("div", {
            children: [
              e.jsx("p", {
                className:
                  "text-3xl sm:text-4xl font-extrabold text-white tracking-tight",
                children: "4.8★",
              }),
              e.jsx("p", {
                className: "text-blue-200 text-sm mt-1 font-medium",
                children: "Average Rating",
              }),
            ],
          }),
          e.jsxs("div", {
            children: [
              e.jsx("p", {
                className:
                  "text-3xl sm:text-4xl font-extrabold text-white tracking-tight",
                "data-counter": !0,
                "data-target": "20",
                "data-duration": "1200",
                "data-suffix": "+",
                children: "0",
              }),
              e.jsx("p", {
                className: "text-blue-200 text-sm mt-1 font-medium",
                children: "Clinics on SnoutIQ",
              }),
            ],
          }),
        ],
      }),
    }),
  });
}
function Ie() {
  const [a, t] = k.useState(te),
    [showDeferredSections, setShowDeferredSections] = k.useState(!1),
    [showHeroPanel, setShowHeroPanel] = k.useState(() =>
      typeof window === "undefined"
        ? !0
        : window.matchMedia("(min-width: 1024px)").matches,
    ),
    { amount, finalAmount, label } = getCurrentPrice(),
    consultPriceText = `₹${formatInr(finalAmount)}`,
    originalPriceText = `₹${formatInr(amount)}`,
    flatConsultPriceText = `₹${formatInr(PAYMENT_AMOUNTS.discounted)}`;
  return (
    k.useEffect(() => {
      const head = document.head;
      const titleWithTemplate = SEO_TITLE_TEMPLATE.replace(
        "%s",
        SEO_TITLE_BASE,
      );
      document.title = titleWithTemplate;
      document.documentElement.lang = "en-IN";

      const upsertMetaTag = (attrName, attrValue, content) => {
        let tag = head.querySelector(`meta[${attrName}="${attrValue}"]`);
        if (!tag) {
          tag = document.createElement("meta");
          tag.setAttribute(attrName, attrValue);
          head.appendChild(tag);
        }
        tag.setAttribute("content", content);
        tag.setAttribute(SEO_OWNER_ATTR, "true");
      };

      const upsertCanonicalTag = (href) => {
        let tag = head.querySelector('link[rel="canonical"]');
        if (!tag) {
          tag = document.createElement("link");
          tag.setAttribute("rel", "canonical");
          head.appendChild(tag);
        }
        tag.setAttribute("href", href);
        tag.setAttribute(SEO_OWNER_ATTR, "true");
      };

      const upsertJsonLdTag = (id, payload) => {
        let tag = head.querySelector(`script#${id}`);
        if (!tag) {
          tag = document.createElement("script");
          tag.setAttribute("type", "application/ld+json");
          tag.setAttribute("id", id);
          head.appendChild(tag);
        }
        tag.text = JSON.stringify(payload);
        tag.setAttribute(SEO_OWNER_ATTR, "true");
      };

      upsertMetaTag("name", "description", SEO_DESCRIPTION);
      upsertMetaTag("name", "keywords", SEO_KEYWORDS);
      upsertMetaTag("name", "robots", "index, follow");
      upsertMetaTag("name", "googlebot", "index, follow");

      upsertCanonicalTag(SEO_CANONICAL);

      upsertMetaTag("property", "og:title", SEO_TITLE_DEFAULT);
      upsertMetaTag("property", "og:description", SEO_DESCRIPTION);
      upsertMetaTag("property", "og:url", SEO_CANONICAL);
      upsertMetaTag("property", "og:site_name", "SnoutIQ");
      upsertMetaTag("property", "og:type", "website");
      upsertMetaTag("property", "og:locale", "en_IN");
      upsertMetaTag("property", "og:image", SEO_OG_IMAGE);
      upsertMetaTag("property", "og:image:width", "1200");
      upsertMetaTag("property", "og:image:height", "630");
      upsertMetaTag("property", "og:image:alt", SEO_OG_IMAGE_ALT);

      upsertMetaTag("name", "twitter:card", "summary_large_image");
      upsertMetaTag("name", "twitter:title", SEO_TITLE_DEFAULT);
      upsertMetaTag("name", "twitter:description", SEO_DESCRIPTION);
      upsertMetaTag("name", "twitter:image", SEO_OG_IMAGE);

      upsertJsonLdTag("business-schema", de);
      upsertJsonLdTag("faq-schema", xe);
    }, []),
    k.useEffect(() => {
      if (!showDeferredSections) return;
      let s = !1;
      let r = null;

      const n = async () => {
        const o = oe();
        for (const x of o)
          try {
            const d = await fetch(x, {
              method: "GET",
              headers: { Accept: "application/json" },
              cache: "no-store",
            });
            if (!d.ok) continue;
            const c = await d.json();
            if (!c?.success || !Array.isArray(c?.data)) continue;
            const j = ce(c.data);
            if (!j.length) continue;
            if (!s) t(j);
            return;
          } catch {}
      };

      const o = () => {
        void n();
      };

      if (typeof window !== "undefined" && "requestIdleCallback" in window) {
        r = window.requestIdleCallback(o, { timeout: 1400 });
      } else {
        r = window.setTimeout(o, 350);
      }

      return () => {
        s = !0;
        if (typeof window !== "undefined") {
          if ("cancelIdleCallback" in window && r !== null) {
            window.cancelIdleCallback(r);
          } else if (r !== null) {
            window.clearTimeout(r);
          }
        }
      };
    }, [showDeferredSections]),
    k.useEffect(() => {
      if (showHeroPanel && showDeferredSections) return;
      if (typeof window === "undefined") return;

      let s = !1;
      let r = null;
      let n = null;
      const o = ["scroll", "touchstart", "pointerdown"];

      const x = () => {
        o.forEach((i) => {
          window.removeEventListener(i, d);
        });
      };

      const d = () => {
        if (s) return;
        s = !0;
        x();

        if ("cancelIdleCallback" in window && r !== null) {
          window.cancelIdleCallback(r);
        } else if (n !== null) {
          window.clearTimeout(n);
        }

        k.startTransition(() => {
          setShowHeroPanel(!0);
          setShowDeferredSections(!0);
        });
      };

      o.forEach((i) => {
        window.addEventListener(i, d, {
          once: !0,
          passive: !0,
        });
      });

      if ("requestIdleCallback" in window) {
        r = window.requestIdleCallback(d, { timeout: 900 });
      } else {
        n = window.setTimeout(d, 450);
      }

      return () => {
        x();
        if ("cancelIdleCallback" in window && r !== null) {
          window.cancelIdleCallback(r);
        } else if (n !== null) {
          window.clearTimeout(n);
        }
      };
    }, [showDeferredSections, showHeroPanel]),
    k.useEffect(() => {
      if (!showDeferredSections) return;
      const s = document.querySelector("[data-stats-section]");
      let r = !1,
        n;
      function o(i) {
        return 1 - Math.pow(1 - i, 3);
      }
      function x(i) {
        const l = Number(i.getAttribute("data-target") || "0"),
          m = Number(i.getAttribute("data-duration") || "1500"),
          p = i.getAttribute("data-suffix") || "",
          h = new Intl.NumberFormat("en-IN");
        let u = null;
        function g(D) {
          u === null && (u = D);
          const V = Math.min((D - u) / m, 1),
            z = Math.floor(o(V) * l);
          ((i.textContent = h.format(z) + p),
            V < 1 && requestAnimationFrame(g));
        }
        requestAnimationFrame(g);
      }
      s &&
        ((n = new IntersectionObserver(
          (i) => {
            const l = i[0];
            !l ||
              !l.isIntersecting ||
              r ||
              ((r = !0),
              document.querySelectorAll("[data-counter]").forEach(x),
              n.disconnect());
          },
          { threshold: 0.3 },
        )),
        n.observe(s));
      const d = document.querySelector("[data-faq-root]");
      if (!d)
        return () => {
          n && n.disconnect();
        };
      const c = Array.from(d.querySelectorAll("[data-faq-item]")),
        j = [];
      function y(i = -1) {
        c.forEach((l, m) => {
          const p = l.querySelector("[data-faq-btn]"),
            h = l.querySelector("[data-faq-panel]"),
            u = l.querySelector("[data-faq-icon]"),
            g = m === i;
          (l.classList.remove("border-brand/40", "shadow-md", "shadow-brand/5"),
            l.classList.add("border-slate-200"),
            l.classList.add("hover:border-slate-300"),
            p && p.setAttribute("aria-expanded", String(g)),
            u && u.classList.toggle("rotate-180", g),
            h && (h.hidden = !g),
            g &&
              (l.classList.add(
                "border-brand/40",
                "shadow-md",
                "shadow-brand/5",
              ),
              l.classList.remove("border-slate-200")));
        });
      }
      return (
        y(-1),
        c.forEach((i, l) => {
          const m = i.querySelector("[data-faq-btn]");
          if (!m) return;
          const p = () => {
            const h = i.querySelector("[data-faq-panel]"),
              u = h && !h.hidden;
            y(u ? -1 : l);
          };
          (m.addEventListener("click", p), j.push({ btn: m, handler: p }));
        }),
        () => {
          (n && n.disconnect(),
            j.forEach(({ btn: i, handler: l }) => {
              i.removeEventListener("click", l);
            }));
        }
      );
    }, [showDeferredSections]),
    e.jsxs("div", {
      className: "flex min-h-screen flex-col bg-white",
      children: [
        e.jsx(Q, { consultPath: "/20+vetsonline?start=details" }),
        e.jsxs("main", {
          "data-home-page": !0,
          className: "flex-1",
          children: [
            e.jsx("section", {
              className:
                "relative overflow-hidden pt-6 pb-14 sm:pt-8 sm:pb-20 lg:pt-10",
              children: e.jsx("div", {
                className: "relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
                children: e.jsxs("div", {
                  className: "grid items-center gap-8 lg:grid-cols-2 lg:gap-10",
                  children: [
                    e.jsxs("div", {
                      children: [
                        e.jsxs("div", {
                          className:
                            "inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-1.5 mb-6",
                          children: [
                            e.jsx("span", {
                              className:
                                "h-2 w-2 rounded-full bg-blue-600 animate-pulse",
                            }),
                            e.jsx("span", {
                              className:
                                "text-xs font-semibold text-blue-700 tracking-wide",
                              children: "INDIA'S PET HEALTHCARE APP",
                            }),
                          ],
                        }),
                        e.jsxs("h1", {
                          className:
                            "text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-[1.1] tracking-tight mb-6",
                          children: [
                            "Best Online Vet Consultation India ",
                            e.jsx("br", {}),
                            e.jsx("span", {
                              className: "text-blue-600",
                              children: "Talk to a Veterinary Doctor",
                            }),
                            " ",
                            "in 15 Minutes",
                          ],
                        }),
                        e.jsxs("p", {
                          className:
                            "text-slate-600 text-base leading-relaxed mb-8 max-w-lg",
                          children: [
                            "SnoutIQ is a trusted ",
                            e.jsx("strong", {
                              children: "pet healthcare platform India",
                            }),
                            " ",
                            "connecting pet parents with verified",
                            " ",
                            e.jsx("strong", { children: "veterinary doctors" }),
                            ". Start a 15-minute",
                            " ",
                            e.jsx("strong", {
                              children: "online vet consultation",
                            }),
                            " for ",
                            e.jsx("strong", { children: consultPriceText }),
                            " after ₹100 off on the flat consultation price (",
                            originalPriceText,
                            " base), with the same pricing available during day and night. Receive expert diagnosis, guidance, and follow-up.",
                          ],
                        }),
                        e.jsxs("div", {
                          className: "mb-8 flex flex-col gap-4 sm:flex-row",
                          children: [
                            e.jsx(b, {
                              to: C,
                              title: "Online Vet Consultation India",
                              children: e.jsx("button", {
                                className:
                                  "bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-4 rounded-full transition shadow-lg shadow-orange-200/80",
                                children: e.jsx(DynamicConsultLabel, {
                                  amount: amount,
                                  finalAmount: finalAmount,
                                  prefixText: "Consult a Vet Now",
                                }),
                              }),
                            }),
                            e.jsx(b, {
                              to: N,
                              target: "_blank",
                              rel: "noopener noreferrer",
                              title: "Download Pet Care App",
                              children: e.jsx("button", {
                                className:
                                  "border border-blue-600 text-blue-600 hover:bg-blue-50 font-semibold px-8 py-4 rounded-full transition",
                                children: "Download App",
                              }),
                            }),
                          ],
                        }),
                        e.jsxs("div", {
                          className:
                            "mb-8 flex flex-wrap items-center gap-2 rounded-2xl border border-orange-100 bg-white/90 p-3 text-sm text-slate-700 shadow-sm shadow-orange-100/60",
                          children: [
                            e.jsx("span", {
                              className:
                                "rounded-full bg-slate-900 px-3 py-1 font-semibold text-white",
                              children:
                                "Online Consultation · Available Day & Night",
                            }),
                            e.jsxs("span", {
                              className:
                                "rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700",
                              children: [originalPriceText, " original"],
                            }),
                            e.jsx("span", {
                              className:
                                "rounded-full bg-yellow-300 px-3 py-1 font-black text-slate-900",
                              children: "₹100 OFF",
                            }),
                            e.jsxs("span", {
                              className:
                                "rounded-full bg-orange-500 px-3 py-1 font-black text-white",
                              children: ["Pay ", consultPriceText],
                            }),
                            e.jsx("span", {
                              className:
                                "rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700",
                              children: "24/7 available",
                            }),
                          ],
                        }),
                        e.jsxs("div", {
                          className:
                            "flex flex-wrap gap-3 text-sm text-slate-600",
                          children: [
                            e.jsx("span", {
                              className:
                                "bg-slate-100 px-3 py-1.5 rounded-full",
                              children: "✓ 15 Minute Video Consultation",
                            }),
                            e.jsx("span", {
                              className:
                                "bg-slate-100 px-3 py-1.5 rounded-full",
                              children: "✓ 24/7 Available",
                            }),
                            e.jsx("span", {
                              className:
                                "bg-slate-100 px-3 py-1.5 rounded-full",
                              children: "✓ Follow up",
                            }),
                            e.jsx("span", {
                              className:
                                "bg-slate-100 px-3 py-1.5 rounded-full",
                              children: "✓ Verified Veterinary Doctors",
                            }),
                          ],
                        }),
                      ],
                    }),
                    showHeroPanel
                      ? e.jsxs("div", {
                          className: "relative",
                          children: [
                            e.jsx("div", {
                              className:
                                "absolute inset-0 rounded-3xl bg-gradient-to-br from-brand/10 to-transparent blur-2xl scale-95",
                            }),
                            e.jsxs("div", {
                              className:
                                "relative space-y-4 rounded-3xl border border-slate-100 bg-white/80 p-5 shadow-2xl shadow-slate-200/60 backdrop-blur sm:p-6",
                              children: [
                                e.jsxs("div", {
                                  className:
                                    "flex items-center gap-3 pb-4 border-b border-slate-100",
                                  children: [
                                    e.jsx("div", {
                                      className:
                                        "h-10 w-10 rounded-2xl bg-brand flex items-center justify-center",
                                      children: e.jsx("span", {
                                        className:
                                          "text-white font-black text-sm",
                                        children: "S",
                                      }),
                                    }),
                                    e.jsxs("div", {
                                      children: [
                                        e.jsx("p", {
                                          className:
                                            "font-bold text-slate-900 text-sm",
                                          children: "SnoutIQ Pet App",
                                        }),
                                        e.jsx("p", {
                                          className: "text-xs text-slate-400",
                                          children:
                                            "All your pet's healthcare, one place",
                                        }),
                                      ],
                                    }),
                                    e.jsxs("div", {
                                      className: "ml-auto flex gap-1",
                                      children: [
                                        e.jsx("span", {
                                          className:
                                            "h-2.5 w-2.5 rounded-full bg-red-400",
                                        }),
                                        e.jsx("span", {
                                          className:
                                            "h-2.5 w-2.5 rounded-full bg-yellow-400",
                                        }),
                                        e.jsx("span", {
                                          className:
                                            "h-2.5 w-2.5 rounded-full bg-green-400",
                                        }),
                                      ],
                                    }),
                                  ],
                                }),
                                e.jsx(S, {
                                  icon: _,
                                  title: "Instant Tele-Consult",
                                  desc: "Video call a qualified vet anywhere in India. Under 5 min wait, 24/7.",
                                  tag: "All India",
                                }),
                                e.jsx(S, {
                                  icon: Y,
                                  title: "Find Clinics Near You",
                                  desc: "GPS-powered search for verified vet clinics across Delhi NCR.",
                                  tag: "Delhi NCR",
                                }),
                                e.jsx(S, {
                                  icon: X,
                                  title: "Online Appointments",
                                  desc: "Book, reschedule, and get reminders - no phone tag with reception.",
                                }),
                                e.jsx(S, {
                                  icon: R,
                                  title: "Digital Health Records",
                                  desc: "Vaccination history, reminders, and reports - always accessible.",
                                }),
                              ],
                            }),
                          ],
                        })
                      : null,
                  ],
                }),
              }),
            }),
            showDeferredSections
              ? e.jsxs(k.Fragment, {
                  children: [
                    e.jsx(pe, {}),
                    e.jsx("section", {
                      className: "bg-slate-50 py-16 sm:py-20",
                      children: e.jsxs("div", {
                        className: "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
                        children: [
                          e.jsxs("div", {
                            className: "mb-10 text-center",
                            children: [
                              e.jsx("span", {
                                className:
                                  "inline-block rounded-full bg-brand-light px-4 py-1.5 text-xs font-semibold text-brand mb-4 tracking-wide",
                                children: "HOW IT WORKS",
                              }),
                              e.jsx("h2", {
                                className:
                                  "text-3xl sm:text-4xl font-extrabold text-slate-900",
                                children: "Healthcare in 3 simple steps",
                              }),
                              e.jsx("p", {
                                className:
                                  "text-slate-500 mt-3 max-w-xl mx-auto",
                                children:
                                  "From symptom to solution - no queues, no stress.",
                              }),
                            ],
                          }),
                          e.jsxs("div", {
                            className:
                              "relative grid gap-4 sm:grid-cols-3 sm:gap-6",
                            children: [
                              e.jsx("div", {
                                className:
                                  "hidden sm:block absolute top-10 left-[22%] right-[22%] h-px bg-gradient-to-r from-transparent via-brand/40 to-transparent",
                              }),
                              [
                                {
                                  n: "01",
                                  icon: P,
                                  title: "Open the App",
                                  desc: "Download SnoutIQ. Tell us about your pet in 30 seconds.",
                                },
                                {
                                  n: "02",
                                  icon: _,
                                  title: "Connect or Book",
                                  desc: "Start a video consult instantly, or book a clinic slot near you.",
                                },
                                {
                                  n: "03",
                                  icon: H,
                                  title: "Get Expert Care",
                                  desc: "Diagnosis, consultation, records - all done. Follow-up reminders included.",
                                },
                              ].map((s) =>
                                e.jsxs(
                                  "div",
                                  {
                                    className:
                                      "relative flex flex-col items-center rounded-3xl border border-slate-100 bg-white p-6 text-center shadow-sm transition-all hover:border-brand/20 hover:shadow-md sm:p-7",
                                    children: [
                                      e.jsx("div", {
                                        className:
                                          "absolute -top-4 left-1/2 -translate-x-1/2 h-8 w-8 rounded-full bg-brand text-white text-xs font-black flex items-center justify-center shadow-md",
                                        children: s.n,
                                      }),
                                      e.jsx("div", {
                                        className:
                                          "mt-4 h-14 w-14 rounded-2xl bg-brand-light flex items-center justify-center mb-5",
                                        children: e.jsx(s.icon, {
                                          className: "h-7 w-7 text-brand",
                                        }),
                                      }),
                                      e.jsx("h3", {
                                        className:
                                          "font-bold text-slate-900 text-lg mb-2",
                                        children: s.title,
                                      }),
                                      e.jsx("p", {
                                        className:
                                          "text-slate-500 text-sm leading-relaxed",
                                        children: s.desc,
                                      }),
                                    ],
                                  },
                                  s.n,
                                ),
                              ),
                            ],
                          }),
                        ],
                      }),
                    }),
                    e.jsxs("section", {
                      className: "overflow-hidden bg-white py-16 sm:py-20",
                      children: [
                        e.jsx("div", {
                          className:
                            "mx-auto mb-8 max-w-7xl px-4 sm:px-6 lg:px-8",
                          children: e.jsxs("div", {
                            className: "flex items-end justify-between gap-4",
                            children: [
                              e.jsxs("div", {
                                children: [
                                  e.jsx("span", {
                                    className:
                                      "inline-block rounded-full bg-brand-light px-4 py-1.5 text-xs font-semibold text-brand mb-3 tracking-wide",
                                    children: "OUR VETS",
                                  }),
                                  e.jsx("h2", {
                                    className:
                                      "text-3xl sm:text-4xl font-extrabold text-slate-900",
                                    children: "The doctors behind SnoutIQ",
                                  }),
                                  e.jsx("p", {
                                    className: "text-slate-500 mt-2 max-w-md",
                                    children:
                                      "Every vet carries a minimum of 7 years of clinical experience. Verified. Rated. Ready.",
                                  }),
                                ],
                              }),
                              e.jsxs(b, {
                                to: N,
                                target: "_blank",
                                rel: "noopener noreferrer",
                                className:
                                  "hidden sm:flex items-center gap-1 text-brand font-semibold text-sm shrink-0 hover:underline",
                                children: [
                                  "View all ",
                                  e.jsx(v, { className: "h-4 w-4" }),
                                ],
                              }),
                            ],
                          }),
                        }),
                        e.jsx("div", {
                          className:
                            "flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 sm:px-6 lg:px-8",
                          style: {
                            scrollbarWidth: "none",
                            msOverflowStyle: "none",
                          },
                          children: a.map((s, r) =>
                            e.jsx(me, { vet: s, idx: r }, r),
                          ),
                        }),
                        e.jsx("p", {
                          className:
                            "mt-3 text-center text-xs text-slate-400 sm:hidden",
                          children: "← swipe to explore →",
                        }),
                      ],
                    }),
                    e.jsx("section", {
                      className: "bg-slate-50 py-16 sm:py-20",
                      children: e.jsxs("div", {
                        className: "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
                        children: [
                          e.jsxs("div", {
                            className:
                              "mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end",
                            children: [
                              e.jsxs("div", {
                                children: [
                                  e.jsx("span", {
                                    className:
                                      "inline-block rounded-full bg-brand-light px-4 py-1.5 text-xs font-semibold text-brand mb-3 tracking-wide",
                                    children: "SERVICES",
                                  }),
                                  e.jsx("h2", {
                                    className:
                                      "text-3xl sm:text-4xl font-extrabold text-slate-900",
                                    children: "What we offer",
                                  }),
                                  e.jsx("p", {
                                    className: "text-slate-500 mt-2",
                                    children:
                                      "Everything your pet needs - online and in your city.",
                                  }),
                                ],
                              }),
                              e.jsx(b, {
                                to: N,
                                target: "_blank",
                                rel: "noopener noreferrer",
                                children: e.jsx(w, {
                                  variant: "outline",
                                  className: "shrink-0",
                                  children: "View All Services",
                                }),
                              }),
                            ],
                          }),
                          e.jsxs("div", {
                            className:
                              "grid gap-5 sm:grid-cols-2 lg:grid-cols-3",
                            children: [
                              e.jsx(A, {
                                title: "Online Video Consultation",
                                description: "Connect with a verified vet in under 15 minutes from wherever you are. Flat consultation pricing is available day and night.",
                                icon: _,
                                badge: "All India",
                                price: consultPriceText,
                                href: C,
                                features: [
                                  "Available 24/7",
                                  "15-min video call",
                                  `Pay ${flatConsultPriceText} after ₹100 off`,
                                  "Same pricing day & night",
                                ],
                              }),
                              e.jsx(A, {
                                title: "Vaccination Packages",
                                description:
                                  "Full first-year protection for your puppy or kitten - all vaccines, one package.",
                                icon: W,
                                badge: "Delhi NCR",
                                price: "₹3200",
                                href: "/delhi-ncr",
                                features: [
                                  "Puppy & kitten plans",
                                  "DHPPi + Rabies included",
                                  "50+ verified clinics",
                                  "SMS reminders",
                                ],
                              }),
                              e.jsx(A, {
                                title: "Neuter & Spay",
                                description:
                                  "Safe, affordable surgical procedures by experienced surgeons.",
                                icon: O,
                                badge: "Delhi NCR",
                                price: "₹8000",
                                href: "/delhi-ncr/dog-neutering",
                                features: [
                                  "Dogs & cats",
                                  "Pre-op check included",
                                  "Post-op care guide",
                                  "Transparent pricing",
                                ],
                              }),
                            ],
                          }),
                        ],
                      }),
                    }),
                    e.jsx("section", {
                      className: "bg-white py-16 sm:py-20",
                      children: e.jsxs("div", {
                        className: "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
                        children: [
                          e.jsxs("div", {
                            className: "mb-10 text-center",
                            children: [
                              e.jsx("span", {
                                className:
                                  "inline-block rounded-full bg-brand-light px-4 py-1.5 text-xs font-semibold text-brand mb-3 tracking-wide",
                                children: "FOR PROFESSIONALS",
                              }),
                              e.jsx("h2", {
                                className:
                                  "text-3xl sm:text-4xl font-extrabold text-slate-900",
                                children: "Are you a vet or clinic?",
                              }),
                            ],
                          }),
                          e.jsxs("div", {
                            className: "grid gap-5 sm:grid-cols-2",
                            children: [
                              e.jsxs("div", {
                                className:
                                  "group relative overflow-hidden rounded-3xl border border-brand/20 bg-gradient-to-br from-brand-light via-white to-blue-50 p-6 transition-all duration-300 hover:shadow-xl hover:shadow-brand/10 sm:p-8",
                                children: [
                                  e.jsx("div", {
                                    className:
                                      "absolute top-0 right-0 h-48 w-48 rounded-full bg-brand/5 -translate-y-16 translate-x-16",
                                  }),
                                  e.jsx($, {
                                    className:
                                      "h-11 w-11 text-brand mb-6 relative",
                                  }),
                                  e.jsx("h3", {
                                    className:
                                      "text-2xl font-extrabold text-slate-900 mb-3",
                                    children: "For Veterinarians",
                                  }),
                                  e.jsx("p", {
                                    className:
                                      "text-slate-600 mb-6 leading-relaxed",
                                    children:
                                      "Join India's growing pet consultation network. A dedicated app connects you 1-on-1 with pet parents - consult from anywhere, grow your practice.",
                                  }),
                                  e.jsx("ul", {
                                    className:
                                      "mb-6 space-y-2.5 text-sm text-slate-700",
                                    children: [
                                      "Dedicated mobile app",
                                      "1-on-1 consultations",
                                      "Flexible schedule",
                                      "Expand your patient base",
                                    ].map((s) =>
                                      e.jsxs(
                                        "li",
                                        {
                                          className: "flex items-center gap-2",
                                          children: [
                                            e.jsx("span", {
                                              className:
                                                "h-5 w-5 rounded-full bg-brand text-white text-[10px] font-bold flex items-center justify-center",
                                              children: "✓",
                                            }),
                                            s,
                                          ],
                                        },
                                        s,
                                      ),
                                    ),
                                  }),
                                  e.jsx(b, {
                                    to: "/vets",
                                    children: e.jsxs(w, {
                                      variant: "brand",
                                      className: "gap-2",
                                      children: [
                                        "Apply as a Vet ",
                                        e.jsx(v, { className: "h-4 w-4" }),
                                      ],
                                    }),
                                  }),
                                ],
                              }),
                              e.jsxs("div", {
                                className:
                                  "group relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50/50 p-6 transition-all duration-300 hover:shadow-xl hover:shadow-slate-200/60 sm:p-8",
                                children: [
                                  e.jsx("div", {
                                    className:
                                      "absolute top-0 right-0 h-48 w-48 rounded-full bg-slate-100/50 -translate-y-16 translate-x-16",
                                  }),
                                  e.jsx(P, {
                                    className:
                                      "h-11 w-11 text-slate-700 mb-6 relative",
                                  }),
                                  e.jsx("h3", {
                                    className:
                                      "text-2xl font-extrabold text-slate-900 mb-3",
                                    children: "For Pet Clinics",
                                  }),
                                  e.jsx("p", {
                                    className:
                                      "text-slate-600 mb-6 leading-relaxed",
                                    children:
                                      "Give your clinic a digital front-end. Manage appointments, send reminders, and connect with pet parents - without sharing personal numbers.",
                                  }),
                                  e.jsx("ul", {
                                    className:
                                      "mb-6 space-y-2.5 text-sm text-slate-700",
                                    children: [
                                      "Appointment management",
                                      "WhatsApp notifications",
                                      "No personal number sharing",
                                      "Recurring revenue model",
                                    ].map((s) =>
                                      e.jsxs(
                                        "li",
                                        {
                                          className: "flex items-center gap-2",
                                          children: [
                                            e.jsx("span", {
                                              className:
                                                "h-5 w-5 rounded-full bg-slate-800 text-white text-[10px] font-bold flex items-center justify-center",
                                              children: "✓",
                                            }),
                                            s,
                                          ],
                                        },
                                        s,
                                      ),
                                    ),
                                  }),
                                  e.jsx(b, {
                                    to: "/clinics",
                                    children: e.jsxs(w, {
                                      variant: "secondary",
                                      className:
                                        "gap-2 border border-slate-300",
                                      children: [
                                        "Apply as a Clinic ",
                                        e.jsx(v, { className: "h-4 w-4" }),
                                      ],
                                    }),
                                  }),
                                ],
                              }),
                            ],
                          }),
                        ],
                      }),
                    }),
                    e.jsxs("section", {
                      className:
                        "relative overflow-hidden bg-slate-900 py-16 sm:py-20",
                      children: [
                        e.jsxs("div", {
                          className: "pointer-events-none absolute inset-0",
                          children: [
                            e.jsx("div", {
                              className:
                                "absolute top-0 left-0 h-64 w-64 rounded-full bg-brand/10 -translate-x-1/2 -translate-y-1/2 blur-3xl",
                            }),
                            e.jsx("div", {
                              className:
                                "absolute bottom-0 right-0 h-64 w-64 rounded-full bg-blue-600/10 translate-x-1/2 translate-y-1/2 blur-3xl",
                            }),
                          ],
                        }),
                        e.jsxs("div", {
                          className:
                            "relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
                          children: [
                            e.jsxs("div", {
                              className: "mb-10 text-center",
                              children: [
                                e.jsx("span", {
                                  className:
                                    "inline-block rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold text-blue-300 mb-4 tracking-wide",
                                  children: "REVIEWS",
                                }),
                                e.jsx("h2", {
                                  className:
                                    "text-3xl sm:text-4xl font-extrabold text-white",
                                  children: "Pet parents love SnoutIQ",
                                }),
                                e.jsx("p", {
                                  className: "text-slate-400 mt-3",
                                  children:
                                    "Real stories from real pet parents.",
                                }),
                              ],
                            }),
                            e.jsx("div", {
                              className: "grid gap-4 sm:grid-cols-3 sm:gap-5",
                              children: [
                                {
                                  name: "Priya Sharma",
                                  role: "Dog Parent · Mumbai",
                                  stars: 5,
                                  text: "My Golden was vomiting at 2 AM. Connected to a vet in 2 minutes. The doctor was so calm and guided me perfectly. Genuinely could not have managed without this app.",
                                },
                                {
                                  name: "Rahul Verma",
                                  role: "Cat Parent · Delhi NCR",
                                  stars: 5,
                                  text: "Got the kitten vaccination package through SnoutIQ. Clinic was verified, vet was experienced, and the reminders in the app made sure we never missed a dose.",
                                },
                                {
                                  name: "Ananya Desai",
                                  role: "Dog Parent · Bangalore",
                                  stars: 5,
                                  text: "My indie dog gets anxious at clinics. Video consults have been a game-changer for us. Affordable, quick, and the vet actually took time to understand her.",
                                },
                              ].map((s, r) =>
                                e.jsxs(
                                  "div",
                                  {
                                    className:
                                      "rounded-3xl border border-white/10 bg-white/5 p-6 transition-colors hover:bg-white/10",
                                    children: [
                                      e.jsx("div", {
                                        className: "flex gap-0.5 mb-5",
                                        children: Array(s.stars)
                                          .fill(0)
                                          .map((n, o) =>
                                            e.jsx(
                                              "span",
                                              {
                                                className:
                                                  "text-yellow-400 text-base",
                                                children: "★",
                                              },
                                              o,
                                            ),
                                          ),
                                      }),
                                      e.jsxs("p", {
                                        className:
                                          "text-slate-300 text-sm leading-relaxed mb-6",
                                        children: ['"', s.text, '"'],
                                      }),
                                      e.jsxs("div", {
                                        className: "flex items-center gap-3",
                                        children: [
                                          e.jsx("div", {
                                            className:
                                              "h-9 w-9 rounded-full bg-brand/30 flex items-center justify-center text-brand font-bold text-sm",
                                            children: s.name
                                              .split(" ")
                                              .map((n) => n[0])
                                              .join(""),
                                          }),
                                          e.jsxs("div", {
                                            children: [
                                              e.jsx("p", {
                                                className:
                                                  "font-bold text-white text-sm",
                                                children: s.name,
                                              }),
                                              e.jsx("p", {
                                                className:
                                                  "text-slate-500 text-xs",
                                                children: s.role,
                                              }),
                                            ],
                                          }),
                                        ],
                                      }),
                                    ],
                                  },
                                  r,
                                ),
                              ),
                            }),
                          ],
                        }),
                      ],
                    }),
                    e.jsx("section", {
                      className:
                        "border-y border-brand/10 bg-[#f0f7ff] py-14 sm:py-16",
                      children: e.jsxs("div", {
                        className: "mx-auto max-w-3xl px-4 text-center",
                        children: [
                          e.jsx("p", {
                            className: "text-brand text-sm font-semibold mb-3",
                            children: "GET THE APP",
                          }),
                          e.jsx("h2", {
                            className:
                              "text-2xl sm:text-3xl font-extrabold text-slate-900 mb-3",
                            children: "Need a quick answer for your pet?",
                          }),
                          e.jsx("p", {
                            className: "mx-auto mb-6 max-w-lg text-slate-500",
                            children:
                              "Download the SnoutIQ app for instant vet consultation, booking help, and complete pet care support in one place.",
                          }),
                          e.jsxs("a", {
                            href: N,
                            target: "_blank",
                            rel: "noopener noreferrer",
                            className:
                              "inline-flex items-center gap-2 rounded-2xl bg-brand px-8 py-4 text-base font-bold text-white transition-colors hover:bg-brand-hover shadow-lg shadow-brand/20",
                            children: [
                              "Download App",
                              e.jsx(v, { className: "h-5 w-5 shrink-0" }),
                            ],
                          }),
                        ],
                      }),
                    }),
                    e.jsx("section", {
                      className: "bg-white py-16 sm:py-20",
                      children: e.jsxs("div", {
                        className: "mx-auto max-w-2xl px-4 sm:px-6 lg:px-8",
                        children: [
                          e.jsxs("div", {
                            className: "mb-10 text-center",
                            children: [
                              e.jsx("span", {
                                className:
                                  "inline-block rounded-full bg-brand-light px-4 py-1.5 text-xs font-semibold text-brand mb-4 tracking-wide",
                                children: "FAQ",
                              }),
                              e.jsx("h2", {
                                className:
                                  "text-3xl sm:text-4xl font-extrabold text-slate-900",
                                children: "Common questions",
                              }),
                            ],
                          }),
                          e.jsx("div", {
                            className: "space-y-3",
                            "data-faq-root": !0,
                            children: B.map((s, r) =>
                              e.jsxs(
                                "div",
                                {
                                  "data-faq-item": !0,
                                  className:
                                    "rounded-2xl border transition-all duration-200 overflow-hidden border-slate-200 hover:border-slate-300",
                                  children: [
                                    e.jsxs("button", {
                                      "data-faq-btn": !0,
                                      "aria-expanded": "false",
                                      className:
                                        "w-full text-left px-6 py-4 flex items-center justify-between gap-4",
                                      children: [
                                        e.jsx("span", {
                                          className:
                                            "font-semibold text-slate-900 text-sm sm:text-base leading-snug",
                                          children: s.q,
                                        }),
                                        e.jsx(K, {
                                          "data-faq-icon": !0,
                                          className:
                                            "h-5 w-5 text-brand shrink-0 transition-transform duration-200",
                                        }),
                                      ],
                                    }),
                                    e.jsx("div", {
                                      "data-faq-panel": !0,
                                      hidden: !0,
                                      className:
                                        "px-6 pb-5 text-slate-500 text-sm leading-relaxed border-t border-brand/10 pt-4 bg-brand-light/30",
                                      children: s.a,
                                    }),
                                  ],
                                },
                                r,
                              ),
                            ),
                          }),
                        ],
                      }),
                    }),
                    e.jsxs("section", {
                      className:
                        "relative overflow-hidden bg-brand py-14 sm:py-16",
                      children: [
                        e.jsxs("div", {
                          className: "pointer-events-none absolute inset-0",
                          children: [
                            e.jsx("div", {
                              className:
                                "absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/5 blur-3xl",
                            }),
                            e.jsx("div", {
                              className:
                                "absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-white/5 blur-3xl",
                            }),
                          ],
                        }),
                        e.jsxs("div", {
                          className:
                            "relative mx-auto max-w-3xl px-4 text-center",
                          children: [
                            e.jsx("h2", {
                              className:
                                "text-3xl sm:text-4xl font-extrabold text-white mb-4",
                              children: "Ready to give your pet the best care?",
                            }),
                            e.jsx("p", {
                              className:
                                "mx-auto mb-6 max-w-xl text-lg text-blue-200",
                              children:
                                "Download the SnoutIQ app or start a video consult right now. No appointment needed.",
                            }),
                            e.jsxs("div", {
                              className:
                                "flex flex-col sm:flex-row gap-4 justify-center",
                              children: [
                                e.jsx(b, {
                                  to: C,
                                  children: e.jsx(w, {
                                    variant: "primary",
                                    size: "lg",
                                    className:
                                      "w-full sm:w-auto bg-orange-500 text-white hover:bg-orange-600 shadow-xl shadow-orange-900/30 px-4 sm:px-6",
                                    children: e.jsx(DynamicConsultLabel, {
                                      amount: amount,
                                      finalAmount: finalAmount,
                                      prefixText: "Consult a Vet Now",
                                    }),
                                  }),
                                }),
                                e.jsx(b, {
                                  to: N,
                                  target: "_blank",
                                  rel: "noopener noreferrer",
                                  children: e.jsx(w, {
                                    size: "lg",
                                    className:
                                      "w-full sm:w-auto bg-white text-brand hover:bg-blue-50 font-bold",
                                    children: "Download Free App",
                                  }),
                                }),
                              ],
                            }),
                          ],
                        }),
                      ],
                    }),
                  ],
                })
              : null,
          ],
        }),
        showDeferredSections ? e.jsx(G, {}) : null,
      ],
    })
  );
}
export { Ie as default };
