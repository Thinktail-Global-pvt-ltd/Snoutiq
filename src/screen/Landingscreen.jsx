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
import { ArrowRight } from "lucide-react";
import { Clock, ShieldCheck } from "lucide-react";

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

const LandingScreen = ({ onStart, onVetAccess }) => {
  const [openFaq, setOpenFaq] = useState(null);
  const [activeSlide, setActiveSlide] = useState(0);
  const activeDoctor = HERO_SLIDES[activeSlide] || HERO_SLIDES[0];

  useEffect(() => {
    const interval = window.setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % HERO_SLIDES.length);
    }, 3500);
    return () => window.clearInterval(interval);
  }, []);

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
              onClick={() => (typeof onStart === "function" ? onStart() : null)}
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
              <button
                type="button"
                onClick={() =>
                  typeof onVetAccess === "function" ? onVetAccess() : null
                }
                className="rounded-full border border-[#3998de]/30 px-3 py-1 text-xs font-semibold text-[#3998de] shadow-sm transition hover:bg-[#3998de]/10 whitespace-nowrap sm:px-4 sm:py-1.5 sm:text-sm"
              >
                🩺 Vet Access
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#f4faff] via-white to-[#e8f2ff] py-8 md:py-10">
        <div className="pointer-events-none absolute -top-24 right-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-24 left-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="relative mx-auto max-w-6xl px-5">
          <div className="grid grid-cols-1 items-center gap-6 md:grid-cols-2 md:gap-8 lg:gap-10">
            {/* Left */}
            <div>
              <div className="inline-block rounded-full bg-[#EAF4FF] px-3 py-1.5 text-xs font-semibold text-[#1D4E89] shadow-sm sm:text-sm">
                ⭐ Trusted by 100+ Pet Parents in India
              </div>

              <h1 className="mt-4 text-3xl font-extrabold leading-[1.4] text-slate-900 md:text-4xl lg:text-[44px]">
                Connect with a{" "}
                <span className="text-[#3998de]">Verified Veterinarian</span> in
                less than 15 minutes.
              </h1>

              <p className="mt-4 text-base leading-relaxed text-slate-500 md:text-lg">
                Professional video consultations for your pet&apos;s health
                concerns. Get expert guidance from licensed veterinarians across
                India.
              </p>
              {/* Pricing (Day/Night) */}
{(() => {
  const fee = getConsultFeeByTime();

  return (
    <div className="mt-4 flex flex-wrap items-center gap-2">
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
        <span className="font-medium text-slate-500">({fee.time})</span> : ₹{fee.price}
      </span>

      <span className="text-xs text-slate-400">(Taxes may apply)</span>
    </div>
  );
})()}


              {/* CTA */}
              <button
                type="button"
                onClick={() =>
                  typeof onStart === "function" ? onStart() : null
                }
                className="
    group mt-5 inline-flex items-center justify-center gap-2
    rounded-xl bg-[#3998de] px-7 py-3 text-base font-semibold text-white md:text-lg
    shadow-lg shadow-[#3998de]/30 transition
    hover:bg-[#2F7FC0]
  "
              >
                Consult a Veterinarian
                <ArrowRight className="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1" />
              </button>

              {/* Stats */}
              <div className="mt-5 grid grid-cols-2 gap-2 sm:gap-4">
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
                <div className="relative overflow-hidden rounded-3xl border border-white/70 bg-white/80 p-3 shadow-[0_25px_60px_rgba(15,118,110,0.08)] md:p-4">
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
                            className="h-56 w-full object-cover object-center sm:h-64 md:h-72"
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
      <section className="bg-white py-16 md:py-20">
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
      <section className="bg-slate-50 py-16 md:py-20">
        <div className="mx-auto max-w-6xl px-5">
          <h2 className="text-center text-3xl font-bold text-slate-900 md:text-4xl">
            How It Works
          </h2>

          <div className="relative mt-12 md:mt-16">
            <div className="absolute left-1/2 top-0 bottom-0 hidden w-px -translate-x-1/2 bg-[#3998de]/20 md:hidden" />
            <div className="absolute left-1/2 top-6 hidden h-px w-[70%] -translate-x-1/2 bg-[#3998de]/20 md:block" />

            <div className="relative grid gap-8 md:grid-cols-3 md:gap-10">
              {[
                {
                  n: "1",
                  title: "Describe Your Concern",
                  desc: "Tell us what's happening with your pet. Include symptoms, duration, and any relevant history.",
                },
                {
                  n: "2",
                  title: "Connect via Video",
                  desc: "Get matched with an available veterinarian. Join a secure video consultation within minutes.",
                },
                {
                  n: "3",
                  title: "Receive Guidance",
                  desc: "Get professional advice on next steps, home care recommendations, and when to visit a clinic.",
                },
              ].map((s, i) => (
                <div key={i} className="relative flex flex-col items-center">
                  <div className="relative z-10 flex h-12 w-12 items-center justify-center rounded-full bg-[#3998de] text-lg font-bold text-white shadow-lg">
                    {s.n}
                  </div>

                  <div className="mt-6 w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-6 text-center shadow-md md:max-w-none">
                    <h3 className="text-lg font-semibold text-slate-900 md:text-xl">
                      {s.title}
                    </h3>
                    <p className="mt-3 text-slate-500">{s.desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Blog */}
      <section className="bg-white py-16 md:py-10">
        <div className="mx-auto max-w-6xl px-5">
          <div className="text-center">
            <h2 className="text-3xl font-bold text-slate-900 md:text-4xl">
              Pet Care Resources
            </h2>
            <p className="mt-3 text-base text-slate-500">
              Expert guidance and practical tips for pet parents.
            </p>
          </div>

          <div className="mt-10 grid gap-6 md:grid-cols-3">
            {blogPosts.map((post) => (
              <a
                key={post.link}
                href={post.link}
                className="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
              >
                <div className="h-44 w-full overflow-hidden bg-slate-50">
                  <img
                    src={post.image}
                    alt={post.title}
                    className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                  />
                </div>
                <div className="flex flex-1 flex-col p-5">
                  <div className="flex items-center gap-3 text-xs text-slate-400">
                    <span className="rounded-full bg-[#3998de]/10 px-3 py-1 text-[#3998de]">
                      {post.category}
                    </span>
                    <span>{post.readTime}</span>
                  </div>
                  <h3 className="mt-4 text-lg font-semibold text-slate-900">
                    {post.title}
                  </h3>
                  <p className="mt-3 text-sm text-slate-500">{post.excerpt}</p>
                  <span className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#3998de]">
                    Read Article <ArrowRight className="h-4 w-4" />
                  </span>
                </div>
              </a>
            ))}
          </div>
        </div>
      </section>

      {/* FAQ */}
      <section className="bg-white py-10 md:py-10">
        <div className="mx-auto max-w-6xl px-5">
          <h2 className="text-center text-3xl font-bold text-slate-900 md:text-4xl">
            Common Questions
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
    </div>
  );
};

export default LandingScreen;
