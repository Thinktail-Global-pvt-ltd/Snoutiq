import React from "react";
import { Link } from "react-router-dom";
import contactImage from "../assets/images/contact.jpg";

import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter"; // ensure: export const Footer = NewFooter
import { ServiceCard } from "../newflow/ServiceCard";

import {
  Video,
  Syringe,
  Scissors,
  ShieldCheck,
  Bot,
  ArrowRight,
} from "lucide-react";

export default function NewCounsult() {
  return (
    <div className="flex min-h-screen flex-col">
      <Navbar />

      <main className="flex-1">
        <section className="relative overflow-hidden border-b border-slate-200">
          <img
            src={contactImage}
            alt="Pet consultation support"
            className="absolute inset-0 h-full w-full object-cover object-center"
            width={1600}
            height={988}
            sizes="100vw"
            loading="eager"
            decoding="async"
            fetchPriority="high"
          />
          <div className="absolute inset-0 bg-slate-900/55" />

          <div className="relative mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-16 lg:px-8 lg:py-20">
            <h1 className="font-display text-4xl font-bold tracking-tight text-white sm:text-5xl mb-6">
              Expert Care for Your Pet
            </h1>

            <p className="mx-auto mb-8 max-w-2xl text-lg text-slate-100">
              From instant online consultations to essential local services, we
              provide everything your pet needs to stay healthy and happy.
            </p>

            <div className="flex flex-wrap justify-center gap-3 text-sm font-medium text-slate-800">
              <div className="flex items-center gap-2 rounded-full bg-white/90 px-4 py-2">
                <ShieldCheck className="h-5 w-5 text-brand" />
                <span>Verified Vets</span>
              </div>
              <div className="flex items-center gap-2 rounded-full bg-white/90 px-4 py-2">
                <ShieldCheck className="h-5 w-5 text-brand" />
                <span>Transparent Pricing</span>
              </div>
              <div className="flex items-center gap-2 rounded-full bg-white/90 px-4 py-2">
                <ShieldCheck className="h-5 w-5 text-brand" />
                <span>Quality Care</span>
              </div>
            </div>
          </div>
        </section>

        {/* AI Symptom Checker Banner */}
        <section className="border-b border-slate-200 bg-slate-50 py-14 sm:py-16">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="flex flex-col items-center justify-between gap-6 rounded-3xl border border-brand/30 bg-gradient-to-r from-brand/20 to-slate-900 p-6 shadow-lg shadow-brand/5 md:flex-row md:p-8">
              <div className="flex-1">
                <div className="mb-5 inline-flex items-center gap-2 rounded-full bg-brand/10 px-4 py-1.5 text-sm font-medium text-brand">
                  <Bot className="w-4 h-4" /> New Feature
                </div>

                <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
                  AI Symptom Checker
                </h2>

                <p className="text-lg text-slate-700 mb-6">
                  Not sure if it&apos;s an emergency? Chat with our AI triage
                  assistant to get immediate next steps. It&apos;s fast, free,
                  and available 24/7.
                </p>

                <Link
                  to="/symptoms"
                  className="inline-flex items-center gap-2 bg-brand text-slate-900 font-bold px-6 py-3 rounded-xl hover:bg-brand-hover transition-colors"
                >
                  Try AI Symptom Checker <ArrowRight className="w-5 h-5" />
                </Link>
              </div>

              <div className="w-full md:w-1/3 flex justify-center">
                <div className="w-32 h-32 bg-brand/20 rounded-full flex items-center justify-center border border-brand/30">
                  <Bot className="w-16 h-16 text-brand" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16 sm:py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid gap-6 lg:grid-cols-3">
              <ServiceCard
                title="Online Video Consultation"
                description="Connect with a verified vet instantly from the comfort of your home. Perfect for triage, minor ailments, and behavioral advice."
                icon={Video}
                badge="All India"
                price="₹399 (Day) / ₹549 (Night)"
                href="/online-vet-consultation-india"
                ctaText="Consult Now"
                features={[
                  "Available 24/7 across India",
                  "15-minute video call",
                  "Verified vets with 7+ years experience",
                  "Auto-assigned vet for instant match",
                  "No appointment needed",
                ]}
              />

              <ServiceCard
                title="Vaccination Packages"
                description="Complete first-year protection for your new puppy or kitten. Administered by experienced vets at our partner clinics."
                icon={Syringe}
                badge="Delhi NCR Only"
                href="/lp-puppy-vaccination-delhi.html"
                ctaText="Book Package"
                features={[
                  "Full year puppy and kitten packages",
                  "Local clinic, experienced vets",
                  "Structured schedule",
                  "Reminders included",
                ]}
              />

              <ServiceCard
                title="Neutering & Spaying"
                description="Safe, professional, and affordable surgical services for dogs and cats at our verified partner clinics."
                icon={Scissors}
                badge="Delhi NCR Only"
                href="/lp-dog-neutering-delhi.html"
                ctaText="Book Surgery"
                features={[
                  "Local clinic service",
                  "Experienced surgeons",
                  "Affordable, safe, professional",
                  "Post-op care guidance",
                ]}
              />
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  );
}
