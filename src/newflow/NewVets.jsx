import React from "react";
import { Link } from "react-router-dom";
import vetHeroImage from "../assets/images/forvet.jpg";

import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter"; // ensure: export const Footer = NewFooter
import { Button } from "../newflow/NewButton"; // ensure correct export (see note below)

import { Smartphone, Users, Zap } from "lucide-react";

const DIRECT_CONSULT_PATH = "/20+vetsonline?start=details";

export default function NewVets() {
  return (
    <div className="flex min-h-screen flex-col">
      <Navbar consultPath={DIRECT_CONSULT_PATH} />

      <main className="flex-1">
        <section className="border-b border-slate-200 py-14 sm:py-16 lg:py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid items-center gap-8 lg:grid-cols-[1.06fr_0.94fr] lg:gap-10">
              <div className="text-center lg:text-left">
                <span className="inline-block rounded-full bg-brand/20 px-4 py-1.5 text-sm font-medium text-brand mb-6">
                  For Veterinarians
                </span>

                <h1 className="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-6xl mb-6">
                  Join India&apos;s Premier
                  <br />
                  <span className="text-brand">Pet Telemedicine Network.</span>
                </h1>

                <p className="mx-auto mb-8 max-w-2xl text-lg text-slate-600 lg:mx-0">
                  Connect 1-on-1 with pet parents through our dedicated mobile
                  app. Expand your reach and provide expert care across India.
                </p>

                <Link to="/auth">
                  <Button size="lg" type="button">
                    Apply Now
                  </Button>
                </Link>
              </div>

              <div className="relative">
                <div className="pointer-events-none absolute -top-12 -right-10 h-48 w-48 rounded-full bg-brand/25 blur-3xl" />
                <div className="pointer-events-none absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-sky-400/15 blur-3xl" />
                <div className="relative overflow-hidden rounded-[2rem] border-4 border-brand/30 bg-white ring-1 ring-brand/20 shadow-[0_24px_60px_-26px_rgba(2,132,199,0.55)]">
                  <img
                    src={vetHeroImage}
                    alt="Veterinarian using SnoutiQ telemedicine platform"
                    className="h-[280px] w-full object-cover object-center sm:h-[360px] lg:h-[430px]"
                    loading="eager"
                    decoding="async"
                    fetchPriority="high"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        <section
          className="bg-slate-50 py-16 sm:py-20"
          style={{ contentVisibility: "auto", containIntrinsicSize: "1px 760px" }}
        >
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid gap-6 md:grid-cols-3">
              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7">
                <Smartphone className="h-10 w-10 text-brand mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Dedicated Mobile App
                </h3>
                <p className="text-slate-600">
                  Manage consultations and connect 1-on-1 with pet parents
                  seamlessly through our B2B infrastructure.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7">
                <Zap className="h-10 w-10 text-brand mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Rapid Response Network
                </h3>
                <p className="text-slate-600">
                  Receive direct consultation requests. Maintain high standards
                  by replying within 15 mins (day) and 30 mins (night).
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7">
                <Users className="h-10 w-10 text-brand mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Expand Your Reach
                </h3>
                <p className="text-slate-600">
                  Grow your practice digitally without the hassle of marketing.
                  We bring the pet parents to you.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section
          className="py-16 sm:py-20"
          style={{ contentVisibility: "auto", containIntrinsicSize: "1px 680px" }}
        >
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-12 text-center">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                How to Join
              </h2>
            </div>

            <div className="relative mx-auto grid max-w-4xl gap-8 md:grid-cols-3 lg:gap-10">
              <div className="hidden md:block absolute top-8 left-1/6 right-1/6 h-0.5 bg-gradient-to-r from-brand/0 via-brand/50 to-brand/0" />

              <div className="relative text-center">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white border-2 border-brand text-brand mb-6 z-10 relative">
                  <span className="font-display text-2xl font-bold">1</span>
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Apply Online
                </h3>
                <p className="text-slate-600">
                  Fill out the simple application form below with your details
                  and experience.
                </p>
              </div>

              <div className="relative text-center">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white border-2 border-brand text-brand mb-6 z-10 relative">
                  <span className="font-display text-2xl font-bold">2</span>
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Verified Onboarding
                </h3>
                <p className="text-slate-600">
                  Our team will verify your credentials and guide you through
                  the platform.
                </p>
              </div>

              <div className="relative text-center">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white border-2 border-brand text-brand mb-6 z-10 relative">
                  <span className="font-display text-2xl font-bold">3</span>
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Start Consulting
                </h3>
                <p className="text-slate-600">
                  Receive requests and provide timely care through our platform.
                </p>
              </div>
            </div>
          </div>
        </section>


        <section
          className="py-8 sm:py-10"
          style={{ contentVisibility: "auto", containIntrinsicSize: "1px 560px" }}
        >
          <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div className="mb-12 text-center">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                FAQ for Vets
              </h2>
            </div>

            <div className="space-y-4">
              <div className="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">
                  What are the eligibility criteria?
                </h3>
                <p className="text-slate-600">
                  You must be a registered veterinary practitioner in India with a
                  minimum of 7 years of clinical experience.
                </p>
              </div>

              <div className="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">
                  What is the expected response time?
                </h3>
                <p className="text-slate-600">
                  We maintain high standards for pet care. Vets are expected to respond
                  to daytime requests within 15 minutes and nighttime requests within
                  30 minutes.
                </p>
              </div>

              <div className="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">
                  Do I need to prescribe medicines?
                </h3>
                <p className="text-slate-600">
                  No. Our platform focuses on triage, advice, and over-the-counter
                  recommendations. We do not support the prescription of restricted drugs
                  via online consultation.
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  );
}
