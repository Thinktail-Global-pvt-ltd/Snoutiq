import React, { useEffect, useRef, useState } from 'react';
import { motion, useScroll, useTransform } from 'framer-motion';
import { Helmet } from 'react-helmet-async';
import playScreen1 from '../assets/google-play/1.png';
import playScreen2 from '../assets/google-play/2.png';
import playScreen3 from '../assets/google-play/3.png';
import playScreen4 from '../assets/google-play/4.png';
import playScreen5 from '../assets/google-play/5.png';
import playScreen6 from '../assets/google-play/6.png';
import logo from '../assets/images/logo.webp';
import appIcon from '../assets/snoutiq_app_icon.png';
import { 
  Stethoscope, 
  Database, 
  TrendingUp, 
  Users, 
  ShieldCheck, 
  ArrowRight, 
  MapPin, 
  Clock, 
  Award,
  Activity
} from 'lucide-react';

import dr from '../assets/doctor4.jpeg';

const team = [
  {
    name: "Tanul Bhatt — Co-Founder",
    desc: "Tanul leads platform infrastructure development and growth strategy. He works closely with the technology team building SnoutIQ's digital health platform and manages acquisition campaigns.",
    exp: "",
    icon: <Activity className="w-5 h-5" />,
    image: "https://picsum.photos/seed/tanul/400/400"
  },
  {
    name: "Nisha Khatri — Co-Founder",
    desc: "Nisha leads veterinary partnerships, pet parent relationships, operations, and distribution.",
    exp: "",
    icon: <Users className="w-5 h-5" />,
    image: "https://picsum.photos/seed/nisha/400/400"
  },
  {
    name: "Shiv Bhardwaj — Co-Founder",
    desc: "Shiv is a practicing Chartered Accountant who has worked with Fortune 500 companies on compliance and financial systems.",
    exp: "He supports SnoutIQ on company compliance, legal structuring, IP, fundraising readiness, and cost optimization.",
    icon: <ShieldCheck className="w-5 h-5" />,
    image: "https://picsum.photos/seed/shiv/400/400"
  }
];

const advisors = [
  {
    name: "Harshit Garia — Non-Executive Director",
    role: "Ex-VP Eggoz · Ex-BCG · IIT BHU",
    desc: "Harshit advises SnoutIQ on scalable growth systems and business strategy. He brings deep experience in building and scaling consumer businesses from his time at Eggoz and strategic problem-solving expertise from BCG.",
    icon: <TrendingUp className="w-4 h-4" />,
    image: "https://picsum.photos/seed/harshit/400/400"
  },
  {
    name: "Dr. Shashannk Goyal — Non-Executive Director",
    role: "MVSc (Surgery) · 10+ years experience · Founder, Blue Coat Veterinary Clinic",
    desc: "Dr. Shashannk advises SnoutIQ on veterinary workflows and vet network development. He brings extensive clinical expertise and firsthand understanding of veterinary practice operations in India.",
    icon: <Stethoscope className="w-4 h-4" />,
    image: dr
  }
];

const playStoreScreens = [
  { src: playScreen1, alt: 'SnoutIQ Google Play screenshot 1' },
  { src: playScreen2, alt: 'SnoutIQ Google Play screenshot 2' },
  { src: playScreen3, alt: 'SnoutIQ Google Play screenshot 3' },
  { src: playScreen4, alt: 'SnoutIQ Google Play screenshot 4' },
  { src: playScreen5, alt: 'SnoutIQ Google Play screenshot 5' },
  { src: playScreen6, alt: 'SnoutIQ Google Play screenshot 6' },
];

const imageProps = {
  loading: 'lazy',
  decoding: 'async',
  fetchPriority: 'low',
  referrerPolicy: 'no-referrer',
};

const initialInvestorForm = {
  fullName: '',
  email: '',
  phone: '',
  companyFund: '',
  ticketSize: '',
};

const Section = ({ children, className = "" }) => (
  <motion.section
    initial={{ opacity: 0, y: 40 }}
    whileInView={{ opacity: 1, y: 0 }}
    viewport={{ once: true, amount: 0.18 }}
    transition={{ duration: 0.8, ease: [0.22, 1, 0.36, 1] }}
    className={`py-6 sm:py-8 px-4 sm:px-6 max-w-7xl mx-auto ${className}`}
  >
    {children}
  </motion.section>
);

const InvestorInput = ({ label, name, value, onChange, type = 'text', error, autoComplete }) => (
  <label className="block">
    <span className="mb-2 block text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">
      {label}
    </span>
    <input
      type={type}
      name={name}
      value={value}
      onChange={onChange}
      autoComplete={autoComplete}
      className={`w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-300 ${
        error
          ? 'border-red-200 focus:border-red-300'
          : 'border-slate-200 focus:border-slate-300'
      }`}
    />
    {error ? <span className="mt-2 block text-xs text-red-500">{error}</span> : null}
  </label>
);

const AutoPreviewSlider = ({ screens, activeIndex }) => {
  const containerRef = useRef(null);

  const { scrollYProgress } = useScroll({
    target: containerRef,
    offset: ['start end', 'end start'],
  });

  const yLeft = useTransform(scrollYProgress, [0, 1], [60, -60]);
  const yRight = useTransform(scrollYProgress, [0, 1], [-40, 40]);
  const rotateLeft = useTransform(scrollYProgress, [0, 1], [-4, 4]);
  const rotateRight = useTransform(scrollYProgress, [0, 1], [4, -4]);

  const leftScreen = screens[activeIndex];
  const rightScreen = screens[(activeIndex + 1) % screens.length];

  return (
    <div ref={containerRef} className="relative">
      <div className="grid grid-cols-2 gap-4 sm:gap-6">
        <motion.div
          style={{ y: yLeft, rotate: rotateLeft }}
          transition={{ type: 'spring', stiffness: 80, damping: 18 }}
          className="aspect-[9/19] bg-slate-200 rounded-[2rem] border-4 border-white shadow-2xl overflow-hidden"
        >
          <motion.img
            key={leftScreen.src}
            src={leftScreen.src}
            alt={leftScreen.alt}
            initial={{ opacity: 0.4, scale: 1.04 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0.4, scale: 0.98 }}
            transition={{ duration: 0.7, ease: 'easeOut' }}
            className="w-full h-full object-cover object-top"
            width="432"
            height="768"
            loading="lazy"
            decoding="async"
          />
        </motion.div>

        <motion.div
          style={{ y: yRight, rotate: rotateRight }}
          transition={{ type: 'spring', stiffness: 80, damping: 18 }}
          className="aspect-[9/19] bg-slate-200 rounded-[2rem] border-4 border-white shadow-2xl mt-8 overflow-hidden"
        >
          <motion.img
            key={rightScreen.src}
            src={rightScreen.src}
            alt={rightScreen.alt}
            initial={{ opacity: 0.4, scale: 1.04 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0.4, scale: 0.98 }}
            transition={{ duration: 0.7, ease: 'easeOut' }}
            className="w-full h-full object-cover object-top"
            width="432"
            height="768"
            loading="lazy"
            decoding="async"
          />
        </motion.div>
      </div>
    </div>
  );
};

export default function Invester() {
  const [activeScreenIndex, setActiveScreenIndex] = useState(0);
  const [investorForm, setInvestorForm] = useState(initialInvestorForm);
  const [formErrors, setFormErrors] = useState({});
  const [isSubmittingInvestorForm, setIsSubmittingInvestorForm] = useState(false);
  const [investorFormError, setInvestorFormError] = useState('');
  const [isInvestorAccessGranted, setIsInvestorAccessGranted] = useState(false);

  useEffect(() => {
    const intervalId = window.setInterval(() => {
      setActiveScreenIndex((current) => (current + 1) % playStoreScreens.length);
    }, 3500);

    return () => window.clearInterval(intervalId);
  }, []);

  const handleInvestorInputChange = (event) => {
    const { name, value } = event.target;

    setInvestorForm((current) => ({
      ...current,
      [name]: value,
    }));

    setFormErrors((current) => {
      if (!current[name]) {
        return current;
      }

      const nextErrors = { ...current };
      delete nextErrors[name];
      return nextErrors;
    });

    if (investorFormError) {
      setInvestorFormError('');
    }
  };

  const handleInvestorFormSubmit = async (event) => {
    event.preventDefault();

    const nextErrors = {};
    const trimmedName = investorForm.fullName.trim();
    const trimmedEmail = investorForm.email.trim();
    const trimmedPhone = investorForm.phone.trim();

    if (!trimmedName) {
      nextErrors.fullName = 'Full Name is required.';
    }

    if (!trimmedEmail) {
      nextErrors.email = 'Email Address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
      nextErrors.email = 'Enter a valid email address.';
    }

    if (!trimmedPhone) {
      nextErrors.phone = 'Phone Number is required.';
    }

    if (Object.keys(nextErrors).length > 0) {
      setFormErrors(nextErrors);
      return;
    }

    setFormErrors({});
    setInvestorFormError('');
    setIsSubmittingInvestorForm(true);

    try {
      const response = await fetch('https://snoutiq.com/backend/api/invester-form', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          full_name: trimmedName,
          email: trimmedEmail,
          phone: trimmedPhone,
          company_or_fund: investorForm.companyFund.trim(),
          expected_ticket_size: investorForm.ticketSize.trim(),
        }),
      });

      const contentType = response.headers.get('content-type') || '';
      let responseBody = null;

      if (contentType.includes('application/json')) {
        responseBody = await response.json();
      } else {
        const responseText = await response.text();
        responseBody = responseText ? { message: responseText } : null;
      }

      if (!response.ok) {
        throw new Error(
          responseBody?.message || responseBody?.error || 'Unable to submit the form right now.'
        );
      }

      setIsInvestorAccessGranted(true);
      setInvestorForm(initialInvestorForm);
    } catch (error) {
      setInvestorFormError(
        error instanceof Error ? error.message : 'Unable to submit the form right now.'
      );
    } finally {
      setIsSubmittingInvestorForm(false);
    }
  };

  return (
    <div className="min-h-screen font-sans selection:bg-slate-900 selection:text-white">
      <Helmet>
        <meta name="robots" content="noindex, nofollow" />
        <meta name="googlebot" content="noindex, nofollow" />
      </Helmet>

      {/* Slide 1: Title */}
      <section className="relative overflow-hidden bg-[#fdfcfb]">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_50%_35%,rgba(15,23,42,0.03),transparent_70%)]" />
        <div className="relative z-20 px-4 pt-4 sm:px-6 sm:pt-6">
          <div className="mx-auto flex max-w-7xl items-center justify-between rounded-[1.25rem] border border-slate-200/70 bg-white/90 px-4 py-3 shadow-md shadow-slate-200/40 backdrop-blur-sm sm:px-5 sm:py-3.5">
            <img
              src={logo}
              alt="SnoutIQ logo"
              className="h-5 w-auto sm:h-5 md:h-5"
              width="80"
              height="32"
              decoding="async"
              fetchPriority="high"
            />
            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 shadow-md shadow-slate-300/40 sm:h-12 sm:w-12">
              <img
                src={appIcon}
                alt="SnoutIQ app icon"
                className="h-6 w-6 rounded-xl object-cover sm:h-7 sm:w-7"
                width="64"
                height="64"
                decoding="async"
                fetchPriority="high"
              />
            </div>
          </div>
        </div>

        <div className="relative z-10 flex min-h-[calc(60vh-72px)] items-center justify-center px-6 py-14 sm:py-16 md:min-h-[70vh]">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
            className="text-center"
          >
            <h1 className="text-[15vw] sm:text-[12vw] md:text-[10vw] lg:text-[8rem] font-serif font-light tracking-tight mb-3 text-slate-900 leading-[0.82] uppercase">
              SnoutIQ
            </h1>
            <p className="text-base sm:text-xl md:text-2xl text-slate-800 font-medium mb-6 max-w-3xl mx-auto tracking-tight px-4">
              Digital Health Infrastructure for Pet Healthcare
            </p>
            <div className="w-16 h-px bg-slate-300 mx-auto mb-6" />
            <div className="text-sm md:text-base text-slate-500 font-light mb-10 tracking-wide">
              PMF Bridge Investment Brief · March 2026
            </div>
            
            <div className="flex flex-col items-center gap-4">
              <div className="text-[10px] text-slate-400 font-mono tracking-widest uppercase">
                ThinkTail Global Pvt. Ltd. · snoutiq.com
              </div>
              <div className="flex items-center gap-2 text-slate-300">
                <div className="w-1 h-1 rounded-full bg-slate-300" />
                <div className="w-1 h-1 rounded-full bg-slate-300" />
                <div className="w-1 h-1 rounded-full bg-slate-300" />
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Slide 2: The Problem */}
      <Section className="grid lg:grid-cols-12 gap-10 items-start">
        <div className="lg:col-span-7">
          <span className="section-label">01. The Problem</span>
          <h2 className="text-4xl sm:text-5xl md:text-7xl font-serif italic mb-6 text-slate-900 leading-tight">
            A fragmented <br />
            <span className="not-italic font-normal">ecosystem.</span>
          </h2>
          <p className="text-xl sm:text-2xl text-slate-500 mb-8 leading-relaxed font-light max-w-2xl">
            India has 45M+ companion animals growing 15-20% annually, yet veterinary access remains a challenge for millions of pet parents.
          </p>
          <div className="grid sm:grid-cols-2 gap-8">
            {[
              { title: "Geographic Gap", desc: "Less than 10% of vets practice outside Tier-1 cities." },
              { title: "Livestock Focus", desc: "Most rural vets focus on livestock, not companion animals." },
              { title: "Data Silos", desc: "Medical records are fragmented across physical clinics." },
              { title: "Specialist Scarcity", desc: "Exotic pet owners struggle to find qualified specialists." }
            ].map((item, i) => (
              <div key={i} className="group">
                <h4 className="text-sm font-bold uppercase tracking-wider text-slate-900 mb-2">{item.title}</h4>
                <p className="text-slate-500 leading-relaxed font-light">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
        <div className="lg:col-span-5">
          <div className="bg-white p-6 sm:p-8 rounded-[2rem] sm:rounded-[2.5rem] border border-slate-100 shadow-2xl shadow-slate-200/40 relative overflow-hidden">
            <div className="absolute top-0 right-0 p-4 sm:p-8 opacity-[0.03]">
              <Activity className="w-32 h-32 sm:w-48 sm:h-48 text-slate-900" />
            </div>
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-50 text-slate-400 text-[8px] sm:text-[10px] font-bold uppercase tracking-widest mb-6 sm:mb-10">
              <Clock className="w-3 h-3" /> 12th March 2026 · 01:31 AM · Sambalpur, Odisha
            </div>
            <h3 className="text-2xl sm:text-3xl font-serif italic mb-6 sm:mb-8 text-slate-900">The Husky Case</h3>
            <p className="text-slate-500 leading-relaxed mb-8 sm:mb-10 text-base sm:text-lg font-light italic">
              "A 2-month-old Husky vomiting blood. Nearest vet unavailable. The parent was desperate."
            </p>
            <div className="bg-slate-900 p-6 sm:p-8 rounded-2xl text-white">
              <p className="leading-relaxed font-light text-base sm:text-lg">
                SnoutIQ connected them with a specialist in minutes. <span className="text-slate-400">Digital access saved a life that night.</span>
              </p>
            </div>
          </div>
        </div>
      </Section>

      {/* Slide 4: Why We're Building This */}
      <Section>
        <div className="bg-slate-50 p-8 sm:p-10 rounded-[3rem] relative overflow-hidden">
          <div className="relative z-10 max-w-4xl">
            <span className="section-label">02. Our Story</span>
            <h2 className="text-4xl sm:text-5xl md:text-6xl font-serif italic mb-6 text-slate-900">Why We're <span className="not-italic font-normal">Building This.</span></h2>
            
            <div className="space-y-6">
              <p className="text-lg sm:text-xl leading-relaxed font-light text-slate-600">
                Tanul & Nisha are pet parents of 4 rescued pets. Their elder cat Shadow (3.5 years) has struggled with chronic UTI for two years—from gall bladder stones to behavioral issues to wrong procedures during neutering. Every new vet meant starting from zero. No medical history, no continuity of care.
              </p>
              
              <p className="text-xl sm:text-2xl leading-relaxed font-light text-slate-600">
                Before SnoutIQ, they ran a digital marketing agency for 5+ years—building websites, managing ads, and generating leads for veterinary clinics and pet businesses across India. They saw the same operational fragmentation in every single vet client they worked with.
              </p>

              <div className="h-px w-24 bg-slate-200" />
              
              <p className="text-xl sm:text-2xl leading-relaxed font-medium text-slate-900 italic">
                SnoutIQ was born from living this problem as both pet parents and agency founders serving the pet healthcare industry.
              </p>
            </div>
          </div>
        </div>
      </Section>

      {/* Slide 5: The Solution */}
      <Section>
        <div className="text-center mb-8">
          <span className="section-label mx-auto">03. The Solution</span>
          <h2 className="text-4xl sm:text-5xl font-serif italic mb-4 text-slate-900">The Unified <span className="not-italic font-normal">Platform.</span></h2>
          <p className="text-slate-500 max-w-3xl mx-auto text-lg leading-relaxed font-light px-4">
            SnoutIQ combines on-demand expertise with permanent digital health records.
          </p>
        </div>
        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
          {[
            { title: "Video Consultations", desc: "On-demand access to qualified vets.", icon: <Stethoscope /> },
            { title: "Health Records", desc: "Permanent, portable medical history.", icon: <Database /> },
            { title: "Treatment History", desc: "Track every prescription and lab.", icon: <Award /> },
            { title: "Preventive Care", desc: "Smart reminders for vaccinations.", icon: <Clock /> }
          ].map((item, i) => (
            <div key={i} className="bg-white p-8 rounded-[2rem] border border-slate-100 hover:border-slate-200 transition-all group">
              <div className="w-10 h-10 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center mb-6 group-hover:bg-slate-900 group-hover:text-white transition-all duration-500">
                {React.cloneElement(item.icon, { className: "w-4 h-4" })}
              </div>
              <h3 className="text-lg font-bold mb-2 text-slate-900">{item.title}</h3>
              <p className="text-slate-400 text-xs leading-relaxed font-light">{item.desc}</p>
            </div>
          ))}
        </div>

        {/* App Preview Section */}
        <div className="bg-slate-50 rounded-[2.5rem] p-6 sm:p-10 overflow-hidden relative">
          <div className="grid lg:grid-cols-2 gap-8 items-center">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-widest mb-4">
                <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" /> Live on Play Store
              </div>
              <h3 className="text-2xl sm:text-3xl font-serif italic mb-4 text-slate-900">v1.0 is <span className="not-italic font-normal">Live.</span></h3>
              <p className="text-slate-500 text-base font-light leading-relaxed mb-6">
                The first version of SnoutIQ is already serving pet parents. We've built a lightweight, high-performance Android application to validate our core features in real-world conditions.
              </p>
              <div className="flex flex-wrap gap-4">
                <a
                  href="https://play.google.com/store/apps/details?id=com.petai.snoutiq&hl=en-IN"
                  target="_blank"
                  rel="noreferrer"
                  className="px-6 py-3 bg-slate-900 text-white rounded-xl font-bold text-sm flex items-center gap-2 hover:bg-slate-800 transition-colors"
                >
                  <Activity className="w-4 h-4" /> Get it on Play Store
                </a>
              </div>
            </div>
            <div className="relative">
              <AutoPreviewSlider
                screens={playStoreScreens}
                activeIndex={activeScreenIndex}
              />
              <div className="flex items-center justify-center gap-2 mt-6">
                {playStoreScreens.map((_, index) => (
                  <button
                    key={index}
                    onClick={() => setActiveScreenIndex(index)}
                    className={`h-2 rounded-full transition-all duration-300 ${
                      index === activeScreenIndex ? 'w-6 bg-slate-900' : 'w-2 bg-slate-300'
                    }`}
                    aria-label={`Go to screen ${index + 1}`}
                  />
                ))}
              </div>
            </div>
          </div>
        </div>
      </Section>

      <Section>
        <div className="overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white shadow-xl shadow-slate-200/30">
          <div className="grid lg:grid-cols-[0.9fr,1.1fr]">
            <div className="border-b border-slate-100 bg-slate-50 p-6 sm:p-8 md:p-10 lg:border-b-0 lg:border-r">
              <span className="section-label">Investor Access</span>
              <h2 className="mb-4 text-3xl font-serif italic text-slate-900 sm:text-4xl">
                Unlock the <span className="not-italic font-normal">Full Investment Brief.</span>
              </h2>
              <p className="max-w-xl text-base font-light leading-relaxed text-slate-500 sm:text-lg">
                Share your details to continue reading the rest of the deck.
              </p>
            </div>

            <div className="p-6 sm:p-8 md:p-10">
              {isInvestorAccessGranted ? (
                <div className="rounded-[2rem] border border-emerald-100 bg-emerald-50/70 p-6 sm:p-8">
                  <div className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-600">
                    <div className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                    Access Granted
                  </div>
                  <h3 className="mt-4 text-2xl font-serif italic text-slate-900">Thank you.</h3>
                  <p className="mt-3 max-w-2xl text-base font-light leading-relaxed text-slate-500">
                    The full investment brief is now unlocked.
                  </p>
                </div>
              ) : (
                <form onSubmit={handleInvestorFormSubmit} className="space-y-4">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <InvestorInput
                      label="Full Name"
                      name="fullName"
                      value={investorForm.fullName}
                      onChange={handleInvestorInputChange}
                      error={formErrors.fullName}
                      autoComplete="name"
                    />
                    <InvestorInput
                      label="Email Address"
                      name="email"
                      type="email"
                      value={investorForm.email}
                      onChange={handleInvestorInputChange}
                      error={formErrors.email}
                      autoComplete="email"
                    />
                    <InvestorInput
                      label="Phone Number"
                      name="phone"
                      type="tel"
                      value={investorForm.phone}
                      onChange={handleInvestorInputChange}
                      error={formErrors.phone}
                      autoComplete="tel"
                    />
                    <InvestorInput
                      label="Company / Fund"
                      name="companyFund"
                      value={investorForm.companyFund}
                      onChange={handleInvestorInputChange}
                      autoComplete="organization"
                    />
                    <div className="sm:col-span-2">
                      <InvestorInput
                        label="Expected Ticket Size"
                        name="ticketSize"
                        value={investorForm.ticketSize}
                        onChange={handleInvestorInputChange}
                      />
                    </div>
                  </div>

                  {investorFormError ? (
                    <div className="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-500">
                      {investorFormError}
                    </div>
                  ) : null}

                  <div className="pt-2">
                    <button
                      type="submit"
                      disabled={isSubmittingInvestorForm}
                      className="inline-flex items-center justify-center rounded-xl bg-slate-900 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                    >
                      {isSubmittingInvestorForm ? 'Submitting...' : 'Unlock Access'}
                    </button>
                  </div>
                </form>
              )}
            </div>
          </div>
        </div>
      </Section>

      <div className="relative">
        {!isInvestorAccessGranted ? (
          <div className="pointer-events-none absolute inset-0 z-20">
            <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(253,252,251,0.72)_0%,rgba(255,255,255,0.28)_38%,rgba(253,252,251,0.8)_100%)]" />
            <div className="absolute inset-x-0 top-6 px-4 sm:px-6">
              <div className="mx-auto max-w-xl rounded-[1.75rem] border border-white/80 bg-white/85 px-5 py-4 text-center shadow-xl shadow-slate-200/40 backdrop-blur">
                <div className="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Investor Access</div>
                <p className="mt-2 text-sm font-light text-slate-500">
                  Submit the form above to unlock the rest of the brief.
                </p>
              </div>
            </div>
          </div>
        ) : null}

        <div
          aria-hidden={!isInvestorAccessGranted}
          className={isInvestorAccessGranted ? '' : 'pointer-events-none select-none blur-[10px] opacity-70 transition duration-300'}
        >
      {/* Slide 6: Market Opportunity */}
      <Section className="bg-white">
        <div className="grid lg:grid-cols-12 gap-12 items-center">
          <div className="lg:col-span-6">
            <span className="section-label">04. Market Opportunity</span>
            <h2 className="text-4xl sm:text-6xl font-serif italic mb-8 text-slate-900 leading-tight">A massive <br /><span className="not-italic font-normal">white space.</span></h2>
            <p className="text-lg sm:text-xl text-slate-500 mb-10 font-light leading-relaxed">
              With 45M+ pets and rising spending, the digital veterinary market is ripe for infrastructure-level disruption.
            </p>
            <div className="grid grid-cols-1 gap-4">
              <div className="mb-2">
                <p className="text-slate-400 text-sm font-light italic">If just 10% of pet owners seek digital veterinary consultations annually:</p>
              </div>
              <div className="group flex items-center justify-between p-6 sm:p-8 bg-slate-50 rounded-[2rem] transition-all hover:bg-slate-100">
                <div className="text-slate-400 text-[10px] uppercase font-bold tracking-widest">Potential Consultations</div>
                <div className="text-3xl sm:text-4xl font-serif italic text-slate-900">4.5M</div>
              </div>
              <div className="group flex items-center justify-between p-8 bg-slate-900 rounded-[2rem] text-white shadow-2xl shadow-slate-200 transition-all hover:scale-[1.02]">
                <div className="text-slate-400 text-[10px] uppercase font-bold tracking-widest">Annual Opportunity</div>
                <div className="text-4xl font-serif italic">₹2400Cr+</div>
              </div>
            </div>
          </div>
          <div className="lg:col-span-6">
            <div className="bg-[#fdfcfb] p-10 rounded-[2.5rem] border border-slate-100 shadow-xl relative overflow-hidden">
              <div className="absolute -top-24 -right-24 w-64 h-64 bg-slate-100 rounded-full blur-3xl opacity-50" />
              <h3 className="text-xl font-serif italic mb-8 text-slate-900 flex items-center gap-3">
                <TrendingUp className="w-6 h-6 text-slate-400" /> Early Validation
              </h3>
              <div className="grid grid-cols-3 gap-8 mb-10 relative z-10">
                <div className="text-center">
                  <div className="text-4xl font-serif italic text-slate-900 mb-1">25</div>
                  <div className="text-[10px] uppercase text-slate-400 font-bold tracking-widest">Paid Consults</div>
                </div>
                <div className="text-center">
                  <div className="text-4xl font-serif italic text-slate-900 mb-1">9+</div>
                  <div className="text-[10px] uppercase text-slate-400 font-bold tracking-widest">Cities</div>
                </div>
                <div className="text-center">
                  <div className="text-4xl font-serif italic text-slate-900 mb-1">30%</div>
                  <div className="text-[10px] uppercase text-slate-400 font-bold tracking-widest">App DL</div>
                </div>
              </div>
              <div className="pt-8 border-t border-slate-100 relative z-10">
                <div className="text-[10px] text-slate-400 uppercase font-bold mb-4 flex items-center gap-2 tracking-widest">
                  <MapPin className="w-3 h-3" /> Geographic Reach
                </div>
                <p className="text-slate-500 leading-relaxed font-light italic text-base">
                  Sambalpur (Odisha), Dhubri, Tinsukia, Nashik, Kolkata, Surat, Hyderabad
                </p>
              </div>
            </div>
          </div>
        </div>
      </Section>

      {/* Slide 8 & 9: Insights & Defensibility */}
      <Section className="grid md:grid-cols-2 gap-6">
        <div className="bg-white p-10 rounded-[2rem] border border-slate-100">
          <span className="section-label">Insights</span>
          <h3 className="text-3xl font-serif italic mb-4 text-slate-900">Traction <span className="not-italic font-normal">Learnings.</span></h3>
          <ul className="space-y-4">
            {[
              "Pet parents in Tier-2/3 India pay for access",
              "Demand across geographically diverse regions",
              "Exotic pet owners severely underserved",
              "Late-night consultations reveal unmet demand"
            ].map((item, i) => (
              <li key={i} className="flex items-start gap-4 group">
                <div className="w-1.5 h-1.5 rounded-full bg-slate-300 mt-2.5 group-hover:bg-slate-900 transition-colors" />
                <span className="text-base text-slate-500 font-light leading-snug">{item}</span>
              </li>
            ))}
          </ul>
        </div>
        <div className="bg-slate-900 p-10 rounded-[2rem] text-white">
          <span className="section-label text-slate-500">Defensibility</span>
          <h3 className="text-3xl font-serif italic mb-4 text-white">The <span className="not-italic font-normal">Moat.</span></h3>
          <ul className="space-y-4">
            {[
              "Longitudinal health records create switching costs",
              "Vet workflow infrastructure enables structured data",
              "34 qualified vets with specialist expertise onboarded",
              "Compounding retention via reminders and follow-ups"
            ].map((item, i) => (
              <li key={i} className="flex items-start gap-4 group">
                <ShieldCheck className="w-5 h-5 text-slate-600 shrink-0 mt-0.5 group-hover:text-white transition-colors" />
                <span className="text-base text-slate-400 font-light leading-snug group-hover:text-slate-200 transition-colors">{item}</span>
              </li>
            ))}
          </ul>
        </div>
      </Section>

      {/* Slide 10 & 11: Economics & Metrics */}
      <Section>
        <div className="text-center mb-8">
          <span className="section-label mx-auto">05. Economics</span>
          <h2 className="text-4xl sm:text-5xl font-serif italic mb-4 text-slate-900">Unit <span className="not-italic font-normal">Economics.</span></h2>
        </div>
        <div className="grid md:grid-cols-2 gap-6">
          <div className="bg-white p-8 rounded-[2rem] border border-slate-100">
            <div className="grid grid-cols-2 gap-6 mb-6">
              <div>
                <div className="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Avg Consultation</div>
                <div className="text-4xl sm:text-5xl font-serif italic text-slate-900">₹540</div>
              </div>
              <div>
                <div className="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Platform Revenue</div>
                <div className="text-4xl sm:text-5xl font-serif italic text-slate-900">₹190</div>
              </div>
            </div>
            <div className="h-px w-full bg-slate-50 mb-6" />
            <p className="text-slate-500 text-lg font-light italic">
              Pets require 3-5 consultations annually → <span className="text-slate-900 font-normal not-italic">₹950 annual revenue per pet</span>
            </p>
          </div>
          <div className="bg-slate-50 p-8 rounded-[2rem]">
            <div className="grid grid-cols-3 gap-4 mb-6">
              <div>
                <div className="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">MRR</div>
                <div className="text-2xl font-serif italic text-slate-900">₹10K</div>
              </div>
              <div>
                <div className="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">CAC</div>
                <div className="text-2xl font-serif italic text-slate-900">₹500</div>
              </div>
              <div>
                <div className="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">LTV Target</div>
                <div className="text-2xl font-serif italic text-slate-900">₹950</div>
              </div>
            </div>
            <div className="h-px w-full bg-slate-200/50 mb-6" />
            <p className="text-slate-500 text-lg font-light italic">
              Targeting <span className="text-slate-900 font-normal not-italic">3x LTV/CAC</span> ratio through repeat consultations.
            </p>
          </div>
        </div>
      </Section>

      {/* Slide 12 & 13: Competition Quadrant */}
      <Section className="bg-white">
        <div className="text-center mb-8">
          <span className="section-label mx-auto">06. Competition</span>
          <h2 className="text-4xl sm:text-5xl font-serif italic mb-4 text-slate-900">Market <span className="not-italic font-normal">Landscape.</span></h2>
        </div>
        
        <div className="relative max-w-4xl mx-auto aspect-square md:aspect-video bg-slate-50 rounded-[2.5rem] border border-slate-100 p-6 sm:p-10 overflow-hidden">
          {/* Grid Lines */}
          <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div className="w-full h-px bg-slate-200" />
            <div className="h-full w-px bg-slate-200" />
          </div>

          {/* Axis Labels */}
          <div className="absolute top-2 left-1/2 -translate-x-1/2 text-[8px] sm:text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Digital</div>
          <div className="absolute bottom-2 left-1/2 -translate-x-1/2 text-[8px] sm:text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Physical</div>
          <div className="absolute left-2 top-1/2 -translate-y-1/2 -rotate-90 text-[8px] sm:text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Transactional</div>
          <div className="absolute right-2 top-1/2 -translate-y-1/2 rotate-90 text-[8px] sm:text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">Infrastructure</div>

          {/* Competitors */}
          <div className="relative w-full h-full">
            {/* Supertails: Digital, Transactional */}
            <div className="absolute top-[25%] left-[25%] -translate-x-1/2 -translate-y-1/2 text-center group">
              <div className="w-2 h-2 sm:w-3 sm:h-3 bg-slate-300 rounded-full mx-auto mb-1 sm:mb-2 group-hover:scale-150 transition-transform" />
              <span className="text-[10px] sm:text-sm font-bold text-slate-400">Supertails</span>
            </div>

            {/* Vetic: Physical, Infrastructure */}
            <div className="absolute bottom-[25%] right-[25%] -translate-x-1/2 -translate-y-1/2 text-center group">
              <div className="w-2 h-2 sm:w-3 sm:h-3 bg-slate-300 rounded-full mx-auto mb-1 sm:mb-2 group-hover:scale-150 transition-transform" />
              <span className="text-[10px] sm:text-sm font-bold text-slate-400">Vetic</span>
            </div>

            {/* HUFT: Physical, Transactional */}
            <div className="absolute bottom-[25%] left-[25%] -translate-x-1/2 -translate-y-1/2 text-center group">
              <div className="w-2 h-2 sm:w-3 sm:h-3 bg-slate-300 rounded-full mx-auto mb-1 sm:mb-2 group-hover:scale-150 transition-transform" />
              <span className="text-[10px] sm:text-sm font-bold text-slate-400">HUFT</span>
            </div>

            {/* SnoutIQ: Digital, Infrastructure */}
            <div className="absolute top-[20%] right-[20%] -translate-x-1/2 -translate-y-1/2 text-center group">
              <div className="w-8 h-8 sm:w-12 sm:h-12 bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-1 sm:mb-2 shadow-xl shadow-slate-200 group-hover:scale-110 transition-transform">
                <div className="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full animate-pulse" />
              </div>
              <span className="text-sm sm:text-lg font-serif italic font-bold text-slate-900">SnoutIQ</span>
              <div className="text-[6px] sm:text-[8px] uppercase tracking-widest text-slate-400 mt-0.5 sm:mt-1">The Winner</div>
            </div>
          </div>
        </div>

        <div className="mt-8 grid sm:grid-cols-2 gap-6 max-w-4xl mx-auto">
          <div className="bg-white p-6 rounded-[1.5rem] border border-slate-100">
            <h4 className="text-sm font-bold uppercase tracking-widest text-slate-900 mb-3">The Transactional Trap</h4>
            <p className="text-sm text-slate-500 font-light leading-relaxed">Competitors focus on one-off sales of food and accessories, ignoring the longitudinal health journey.</p>
          </div>
          <div className="bg-slate-900 p-6 rounded-[1.5rem] text-white">
            <h4 className="text-sm font-bold uppercase tracking-widest text-slate-400 mb-3">The Infrastructure Edge</h4>
            <p className="text-sm text-slate-300 font-light leading-relaxed">SnoutIQ builds the permanent system of record, creating high switching costs and deep medical moats.</p>
          </div>
        </div>
      </Section>

      {/* Team Sections */}
      <Section>
        <div className="text-center mb-6">
          <span className="section-label mx-auto">07. Team</span>
          <h2 className="text-4xl sm:text-5xl font-serif italic mb-4 text-slate-900">The <span className="not-italic font-normal">Founders.</span></h2>
        </div>
        <div className="grid md:grid-cols-3 gap-3 mb-6">
          {team.map((member, i) => (
            <div key={i} className="bg-white p-5 rounded-[1.5rem] border border-slate-100 hover:shadow-xl transition-all duration-500 group">
              <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div className="w-full sm:w-[30%] sm:max-w-[110px]">
                  <div className="aspect-square rounded-xl bg-slate-50 overflow-hidden group-hover:scale-105 transition-transform duration-500">
                    {member.image ? (
                      <img src={member.image} alt={member.name} className="w-full h-full object-cover" width="400" height="400" {...imageProps} />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-slate-300 group-hover:bg-slate-900 group-hover:text-white transition-colors">
                        {React.cloneElement(member.icon, { className: "w-6 h-6" })}
                      </div>
                    )}
                  </div>
                </div>
                <div className="w-full sm:w-[70%]">
                  <h3 className="text-xl font-serif italic mb-2 text-slate-900 leading-tight">{member.name}</h3>
                  <p className="text-slate-500 mb-4 leading-relaxed font-light text-sm">{member.desc}</p>
                  {member.exp && (
                    <p className="text-[10px] text-slate-300 uppercase tracking-widest font-bold border-t border-slate-50 pt-4">{member.exp}</p>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
        <div className="grid md:grid-cols-2 gap-3">
          {advisors.map((advisor, i) => (
            <div key={i} className="bg-slate-50 p-6 rounded-[1.5rem]">
              <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div className="w-full sm:w-[30%] sm:max-w-[120px]">
                  <div className="aspect-square rounded-xl bg-white overflow-hidden shadow-sm">
                    {advisor.image ? (
                      <img src={advisor.image} alt={advisor.name} className="w-full h-full object-cover" width="400" height="400" {...imageProps} />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-slate-400">
                        {React.cloneElement(advisor.icon, { className: "w-5 h-5" })}
                      </div>
                    )}
                  </div>
                </div>
                <div className="w-full sm:w-[70%]">
                  <h3 className="text-xl font-serif italic text-slate-900">{advisor.name}</h3>
                  <div className="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">{advisor.role}</div>
                  <p className="text-slate-500 leading-relaxed font-light italic mt-4">{advisor.desc}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </Section>

      {/* Slide 20, 21, 22: The Ask & Milestones */}
      <Section className="relative">
        <div className="bg-[#fdfcfb] p-6 sm:p-8 rounded-[2.5rem] border border-slate-100 text-center relative overflow-hidden">
          <div className="absolute top-0 left-1/2 -translate-x-1/2 w-px h-10 bg-slate-900" />
          
          <span className="section-label mx-auto mt-4">08. The Ask</span>
          <h2 className="text-4xl sm:text-5xl font-serif italic mb-4 text-slate-900">PMF Bridge <span className="not-italic font-normal">Round.</span></h2>
          <p className="text-lg sm:text-xl text-slate-500 mb-6 max-w-4xl mx-auto leading-relaxed font-light italic px-4">
            Raising <span className="text-slate-900 font-normal not-italic">₹15 Lakhs</span> to validate product-market fit through user acquisition and repeat behavior testing.
          </p>
          
          <div className="grid md:grid-cols-2 gap-4 mb-6 text-left">
            <div className="bg-white p-6 sm:p-8 rounded-[2rem] border border-slate-100">
              <h3 className="text-xl sm:text-2xl font-serif italic mb-4 text-slate-900">Use of Funds</h3>
              <ul className="space-y-2 sm:space-y-3">
                {[
                  "500+ new pet parents acquired",
                  "500+ pets with digital health records",
                  "Repeat consultation behavior validated",
                  "Geo clusters identified for scaling"
                ].map((item, i) => (
                  <li key={i} className="flex items-center gap-3 group">
                    <div className="w-1 h-1 rounded-full bg-slate-300 group-hover:bg-slate-900 transition-colors" />
                    <span className="text-sm sm:text-base text-slate-500 font-light italic">{item}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div className="bg-slate-900 p-6 sm:p-8 rounded-[2rem] text-white">
              <h3 className="text-xl sm:text-2xl font-serif italic mb-4 text-white">90-Day Milestones</h3>
              <div className="space-y-4 sm:space-y-6">
                {[
                  { label: "Target Users", val: "500+" },
                  { label: "Target MRR", val: "₹50K" },
                  { label: "Target LTV/CAC", val: "3x" }
                ].map((item, i) => (
                  <div key={i} className="flex justify-between items-end border-b border-slate-800 pb-2">
                    <span className="text-slate-500 font-bold uppercase text-[8px] sm:text-[10px] tracking-widest">{item.label}</span>
                    <span className="text-xl sm:text-2xl font-serif italic text-white">{item.val}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>

          <div className="bg-white p-6 sm:p-8 rounded-[2rem] border border-slate-100 shadow-2xl shadow-slate-200/50">
            <div className="flex flex-col md:flex-row items-center justify-between gap-4 sm:gap-6">
              <div className="text-center md:text-left">
                <div className="text-slate-900 text-lg sm:text-xl font-serif italic mb-1">Welcoming ₹1L+ tickets</div>
                <div className="text-slate-400 text-xs sm:text-sm font-light italic">Target close: End of April 2026</div>
              </div>
              <button className="w-full md:w-auto px-8 sm:px-10 py-3 sm:py-4 bg-slate-900 text-white font-bold text-sm rounded-xl hover:bg-slate-800 transition-all shadow-2xl shadow-slate-200">
                Contact Founders
              </button>
            </div>
          </div>
        </div>
      </Section>

      {/* Slide 23 & 24: Critical Capital & Vision */}
      <Section className="grid md:grid-cols-2 gap-6">
        <div className="bg-slate-900 p-10 rounded-[2rem] text-white">
          <h3 className="text-2xl font-serif italic mb-6 text-white">Critical Capital.</h3>
          <p className="text-base text-slate-400 mb-6 leading-relaxed font-light italic">Current ₹10K MRR constrained by lack of marketing budget. This bridge capital directly fuels:</p>
          <ul className="space-y-4">
            {[
              "High-intent veterinary search campaigns",
              "Tier-2/3 cluster testing for lower CAC",
              "Repeat consultation validation"
            ].map((item, i) => (
              <li key={i} className="flex items-center gap-4 group">
                <ArrowRight className="w-4 h-4 text-slate-700 group-hover:text-white transition-colors" />
                <span className="text-slate-300 font-light text-sm">{item}</span>
              </li>
            ))}
          </ul>
        </div>
        <div className="bg-slate-900 p-10 rounded-[2rem] text-white">
          <h3 className="text-2xl font-serif italic mb-6 text-white">Long-Term Vision.</h3>
          <p className="text-base text-slate-400 mb-6 leading-relaxed font-light italic">SnoutIQ aims to become the system of record for pet healthcare in India.</p>
          <ul className="space-y-4">
            {[
              "Connect pet parents, vets, diagnostics, insurance",
              "Unified digital health infrastructure",
              "Expand into chronic disease management"
            ].map((item, i) => (
              <li key={i} className="flex items-center gap-4 group">
                <TrendingUp className="w-4 h-4 text-slate-700 group-hover:text-white transition-colors" />
                <span className="text-slate-300 font-light text-sm">{item}</span>
              </li>
            ))}
          </ul>
        </div>
      </Section>

      {/* Slide 25: Thank You */}
      <footer className="py-8 px-6 bg-[#fdfcfb] text-center relative overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(15,23,42,0.02),transparent_70%)]" />
        <div className="relative z-10">
          <span className="section-label mx-auto">Thank You</span>
          <h2 className="text-4xl sm:text-5xl md:text-7xl font-serif italic mb-6 text-slate-900 tracking-tight">
            Join us in shaping <br /><span className="not-italic font-normal">The future of pet care.</span>
          </h2>
          <div className="w-12 h-px bg-slate-900 mx-auto mb-6" />
          <p className="text-xl sm:text-2xl font-serif italic mb-8 text-slate-900 px-4">Team Snoutiq</p>
          <div className="text-[8px] sm:text-[10px] text-slate-300 font-mono tracking-[0.3em] uppercase px-4">© 2026 ThinkTail Global Pvt. Ltd. · snoutiq.com</div>
        </div>
      </footer>
        </div>
      </div>
    </div>
  );
}

