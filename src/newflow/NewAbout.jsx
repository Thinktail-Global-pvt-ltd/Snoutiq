import React from "react";
import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter"; // ensure: export const Footer = NewFooter
import aboutImage from "../assets/images/about.jpg";

import { Heart, ShieldCheck, Stethoscope } from "lucide-react";

const DIRECT_CONSULT_PATH = "/20+vetsonline?start=details";

export default function NewAbout() {
  return (
    <div className="flex min-h-screen flex-col">
      <Navbar consultPath={DIRECT_CONSULT_PATH} />

      <main className="flex-1">
        <section className="relative flex min-h-[320px] items-center overflow-hidden border-b border-slate-200 sm:min-h-[380px]">
          <img
            src={aboutImage}
            alt="About SnoutiQ"
            className="absolute inset-0 h-full w-full object-cover object-[center_22%] sm:object-[center_28%]"
            width={1600}
            height={1067}
            sizes="100vw"
            loading="eager"
            decoding="async"
            fetchPriority="high"
          />
          <div className="absolute inset-0 bg-gradient-to-b from-slate-900/45 via-slate-900/40 to-slate-900/45" />

          <div className="relative mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-16 lg:px-8 lg:py-20">
            <h1 className="font-display text-4xl font-bold tracking-tight text-white sm:text-6xl mb-6">
              Our Story
            </h1>
            <p className="mx-auto mb-8 max-w-2xl text-lg text-slate-100">
              Making quality pet healthcare accessible across India.
            </p>
          </div>
        </section>

        <section
          className="bg-slate-50 py-16 sm:py-20"
          style={{ contentVisibility: "auto", containIntrinsicSize: "1px 600px" }}
        >
          <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div className="prose prose-invert prose-lg mx-auto">
              <p className="mb-6 text-xl leading-relaxed text-slate-700">
                SnoutiQ was founded with a simple mission: to ensure every pet in
                India has access to high-quality, affordable, and timely healthcare.
                We understand that pets are family, and their health is a top priority.
              </p>
              <p className="mb-6 text-xl leading-relaxed text-slate-700">
                Born and built in India, for India, we recognize the unique challenges
                pet parents face—from late-night emergencies to finding reliable local
                clinics for essential services like vaccinations and neutering.
              </p>
              <p className="text-xl leading-relaxed text-slate-700">
                Our platform bridges the gap between pet parents and experienced
                veterinarians, offering instant online consultations and seamless
                booking for local clinic services.
              </p>
            </div>
          </div>
        </section>

        <section
          className="py-16 sm:py-20"
          style={{ contentVisibility: "auto", containIntrinsicSize: "1px 700px" }}
        >
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-12 text-center">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                Our Core Values
              </h2>
            </div>

            <div className="grid gap-6 md:grid-cols-3">
              <div className="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center sm:p-7">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand/20 text-brand mb-6">
                  <ShieldCheck className="h-8 w-8" />
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Verified Experts Only
                </h3>
                <p className="text-slate-600">
                  We rigorously vet every veterinarian on our platform, ensuring they
                  have a minimum of 7 years of clinical experience.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center sm:p-7">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand/20 text-brand mb-6">
                  <Stethoscope className="h-8 w-8" />
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Triage-First Approach
                </h3>
                <p className="text-slate-600">
                  We believe in responsible telemedicine. Our online consults focus on
                  triage and advice, never prescribing restricted drugs without a
                  physical exam.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center sm:p-7">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand/20 text-brand mb-6">
                  <Heart className="h-8 w-8" />
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Compassionate Care
                </h3>
                <p className="text-slate-600">
                  Every interaction is driven by a genuine love for animals and a
                  commitment to their well-being.
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
