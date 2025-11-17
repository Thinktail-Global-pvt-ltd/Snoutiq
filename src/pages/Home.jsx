import { Suspense, lazy } from 'react';
import Hero from "./HeroSection";
import FeatureCard from "../components/FeatureCard";
import Benefits from "../components/Benefits";
import Header from "../components/Header";

const ClientLogos = lazy(() => import("../components/ClientLogos"));
const PainPoints = lazy(() => import("../components/PainPoints"));
const Workflow = lazy(() => import("../components/Workflow"));
const CTA = lazy(() => import("../components/CTA"));
const Footer = lazy(() => import("../components/Footer"));

import {
  Video,
  Brain,
  Calendar,
  MessageSquare,
  UserCheck,
  Smartphone,
  BarChart3,
  Shield,
  Zap,
  Users,
  Timer,
  CheckCircle,
} from "lucide-react";

const FEATURES = [
  {
    icon: Video,
    title: "HD Video Consultations",
    description:
      "Connect pet owners with veterinarians through crystal-clear video calls. Diagnose, prescribe, and follow-up remotely with ease.",
    link: "/video-consult",
  },
  {
    icon: Brain,
    title: "AI-Powered Triage",
    description:
      "Smart symptom analysis that prioritizes urgent cases and routes pets to the right specialist automatically.",
    link: "/ai-triage",
  },
  {
    icon: Calendar,
    title: "Smart Scheduling",
    description:
      "Automated appointment booking with intelligent reminders that reduce no-shows by up to 70%.",
  },
  {
    icon: MessageSquare,
    title: "Secure Messaging",
    description:
      "HIPAA-compliant chat system for follow-ups, medication reminders, and post-care instructions.",
  },
  {
    icon: BarChart3,
    title: "Analytics Dashboard",
    description:
      "Real-time insights into clinic performance, patient outcomes, and revenue optimization.",
  },
  {
    icon: Shield,
    title: "Medical Records",
    description:
      "Cloud-based EMR system with secure storage, instant access, and seamless sharing capabilities.",
  },
];

const BENEFITS = [
  {
    icon: Zap,
    title: "Streamline Patient Flow",
    description:
      "Reduce wait times and optimize clinic operations with intelligent scheduling",
  },
  {
    icon: Timer,
    title: "Save Admin Time",
    description:
      "Automate repetitive tasks and focus on what matters: patient care",
  },
  {
    icon: Users,
    title: "Boost Client Satisfaction",
    description:
      "Delight pet owners with convenient video consultations and quick responses",
  },
  {
    icon: CheckCircle,
    title: "Easy Onboarding",
    description:
      "Get started in minutes with our intuitive platform and dedicated support",
  },
];

const PAIN_POINTS = [
  {
    problem:
      "Long wait times frustrate pet owners and reduce clinic efficiency",
    solution:
      "Instant video consultations eliminate waiting rooms and streamline care delivery",
  },
  {
    problem:
      "Emergency cases get mixed with routine check-ups causing delays",
    solution:
      "AI triage automatically prioritizes urgent cases and routes to specialists",
  },
  {
    problem:
      "No-shows waste valuable appointment slots and reduce revenue",
    solution:
      "Smart reminders and easy rescheduling cut no-shows by 70%",
  },
  {
    problem:
      "Manual paperwork slows down operations and increases errors",
    solution:
      "Digital records and automated workflows save 10+ hours per week",
  },
];

const WORKFLOW_STEPS = [
  {
    number: 1,
    title: "Pet Owner Books",
    description:
      "Owner enters symptoms and books appointment through mobile app or website",
    icon: Smartphone,
  },
  {
    number: 2,
    title: "AI Analyzes",
    description:
      "Smart triage system evaluates urgency and recommends appropriate care level",
    icon: Brain,
  },
  {
    number: 3,
    title: "Vet Consults",
    description:
      "Veterinarian reviews case and conducts HD video consultation",
    icon: Video,
  },
  {
    number: 4,
    title: "Follow-Up Care",
    description:
      "Automated reminders, prescriptions, and progress tracking ensure recovery",
    icon: UserCheck,
  },
];

// Simple loading component
const SectionLoader = () => (
  <div className="py-12 flex justify-center items-center">
    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
  </div>
);

const LazySection = ({ children }) => (
  <Suspense fallback={<SectionLoader />}>{children}</Suspense>
);

function Home() {
  return (
    <>
      <Header />
      <main id="main-content" className="bg-white">
        {/* Above the fold - load immediately */}
        <Hero
          badge="Trusted by Veterinary Professionals"
          title="Transform Your Veterinary Practice with AI-Powered Care"
          subtitle="Streamline consultations, reduce no-shows, and deliver exceptional pet care with SnoutIQ's intelligent platform."
          ctaPrimary={{ text: "Start Free Trial", href: "/register?utm_source=header&utm_medium=cta&utm_campaign=vet_landing" }}
          ctaSecondary={{ text: "Book a Live Demo", href: "https://docs.google.com/forms/d/e/1FAIpQLSdLBk7Yv8ODnzUV_0KrCotH1Kc91d1VpeUHWyovxXO_GYC4yw/viewform?usp=sharing&ouid=100613985134578372936" }}
        />

        <Benefits
          benefits={BENEFITS}
          variant="default"
          eyebrow="Outcomes that matter"
          title="From first symptom to lasting loyalty"
          description="SnoutIQ removes the chaos between triage, consultation, and follow-up so your team stays focused on care."
          id="benefits"
        />

        {/* Below the fold - lazy load */}
        <LazySection>
          <ClientLogos
            eyebrow="Trusted by leading hospitals"
            title="Partnering with forward-thinking veterinary clinics"
            subtitle="High-growth independents, ER groups, and regional consolidators rely on SnoutIQ to deliver concierge-level client experiences."
          />
        </LazySection>

        <section
          id="features"
          className="py-12 md:py-16 lg:py-20 bg-white"
          aria-labelledby="features-heading"
        >
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-8 md:mb-12 lg:mb-14">
              <p className="text-sm font-semibold uppercase tracking-[0.3em] text-blue-600 mb-3">
                Platform overview
              </p>
              <h2
                id="features-heading"
                className="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4 md:mb-6 leading-tight"
              >
                Everything you need to{" "}
                <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
                  modernize your practice
                </span>
              </h2>
              <p className="text-lg sm:text-xl md:text-2xl text-slate-600 max-w-4xl mx-auto leading-relaxed">
                A complete platform designed for modern veterinary clinics
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
              {FEATURES.map((feature, index) => (
                <FeatureCard
                  key={feature.title}
                  icon={feature.icon}
                  title={feature.title}
                  description={feature.description}
                  link={feature.link}
                  index={index}
                  variant="gradient"
                />
              ))}
            </div>
          </div>
        </section>

        <LazySection>
          <PainPoints
            id="pain-points"
            eyebrow="Before and after SnoutIQ"
            title="The Problems Facing Modern Vet Clinics"
            subtitle="Traditional veterinary practices struggle with inefficiencies. SnoutIQ provides modern solutions."
            painPoints={PAIN_POINTS}
          />
        </LazySection>

        <LazySection>
          <Workflow
            id="workflow"
            eyebrow="How it works"
            title="A simple, repeatable workflow from booking to follow-up"
            subtitle="Give your clients a smooth digital experience while your team gets a clear, trackable process."
            steps={WORKFLOW_STEPS}
          />
        </LazySection>

        <LazySection>
          <section
            className="bg-gradient-to-br from-slate-50 to-blue-50"
            aria-labelledby="cta-heading"
          >
            <CTA
              id="cta"
              eyebrow="Ready to modernize?"
              title="Ready to Transform Your Veterinary Practice?"
              subtitle="Join 500+ clinics already providing better care with SnoutIQ"
              primaryButton={{ text: "Start Free Trial", href: "/pricing" }}
              secondaryButton={{ text: "Talk to Our Team", href: "/contact" }}
              variant="gradient"
              bullets={[
                "Launch in under two weeks",
                "White-glove onboarding",
                "Dedicated success manager",
              ]}
            />
          </section>
        </LazySection>
      </main>

      <LazySection>
        <Footer />
      </LazySection>
    </>
  );
}

export default Home;
