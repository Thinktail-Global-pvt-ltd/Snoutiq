import React, { useState } from "react";
import clinicImage from "../assets/images/clinic.png";

import { Navbar } from "../newflow/Navbar";
import { Footer } from "../newflow/NewFooter"; // ensure: export const Footer = NewFooter
import { Button } from "../newflow/NewButton"; // ensure: export const Button = NewButton (or export Button)

import {
  Smartphone,
  Bell,
  MessageSquare,
  Video,
  Users,
  Check,
} from "lucide-react";

const CLINIC_FORM_API_URL = "https://snoutiq.com/backend/api/demo-website-form";
const DIRECT_CONSULT_PATH = "/20+vetsonline?start=details";

export default function NewClinics() {
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const [formData, setFormData] = useState({
    clinicName: "",
    contactName: "",
    mobile: "",
    city: "",
  });

  const handleChange = (e) => {
    const { id, value } = e.target;
    setFormData((prev) => ({ ...prev, [id]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitError("");
    setIsSubmitting(true);

    const payload = {
      // Keeping `name` for backend compatibility as requested
      name: formData.contactName.trim(),
      clinic_name: formData.clinicName.trim(),
      contact_name: formData.contactName.trim(),
      mobile: formData.mobile.trim(),
      city: formData.city.trim(),
      source: "newflow_clinics_page",
    };

    try {
      const response = await fetch(CLINIC_FORM_API_URL, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const raw = await response.text();
      let data = null;
      try {
        data = raw ? JSON.parse(raw) : null;
      } catch {
        data = null;
      }

      if (!response.ok) {
        const message =
          data?.message ||
          (data?.errors && Object.values(data.errors).flat()[0]) ||
          "Unable to submit request right now. Please try again.";
        throw new Error(message);
      }

      setIsSubmitted(true);
      setFormData({
        clinicName: "",
        contactName: "",
        mobile: "",
        city: "",
      });
    } catch (error) {
      setSubmitError(
        error?.message || "Unable to submit request right now. Please try again."
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="flex min-h-screen flex-col">
      <Navbar consultPath={DIRECT_CONSULT_PATH} />

      <main className="flex-1">
        <section className="relative overflow-hidden border-b border-sky-200/70 bg-slate-50/40 py-14 sm:py-16 lg:py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid items-center gap-8 lg:grid-cols-[1.08fr_0.92fr] lg:gap-10">
              <div className="text-center lg:text-left">
                <span className="mb-6 inline-block rounded-full border border-sky-200 bg-sky-500/10 px-4 py-1.5 text-sm font-semibold text-sky-700">
                  For Pet Clinics
                </span>

                <h1 className="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl mb-6">
                  Give your clinic a
                  <br />
                  <span className="text-blue-600">digital backbone.</span>
                </h1>

                <p className="mx-auto max-w-2xl text-lg text-slate-600 mb-8 lg:mx-0">
                  A complete B2B app to manage your clinic, connect with pet
                  parents, and grow your practice digitally.
                </p>

                <div className="mb-8 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                  <span className="rounded-full border border-sky-200 bg-white/95 px-4 py-1.5 text-sm font-medium text-slate-700 shadow-sm">
                    Clinic Workflow Automation
                  </span>
                  <span className="rounded-full border border-sky-200 bg-white/95 px-4 py-1.5 text-sm font-medium text-slate-700 shadow-sm">
                    Better Client Retention
                  </span>
                  <span className="rounded-full border border-sky-200 bg-white/95 px-4 py-1.5 text-sm font-medium text-slate-700 shadow-sm">
                    Recurring Revenue Model
                  </span>
                </div>

                <a href="#clinic-onboarding-form">
                  <Button
                    size="lg"
                    className="bg-blue-600 text-white shadow-lg shadow-blue-600/25 hover:bg-blue-700"
                    type="button"
                  >
                    Schedule Platform Consultation
                  </Button>
                </a>
              </div>

              <div className="relative">
                <div className="pointer-events-none absolute -top-12 -right-10 h-48 w-48 rounded-full bg-sky-400/25 blur-3xl" />
                <div className="pointer-events-none absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-pink-400/15 blur-3xl" />
                <div className="relative overflow-hidden rounded-[2rem] border-4 border-sky-300/80 bg-white ring-1 ring-blue-200/80 shadow-[0_24px_60px_-26px_rgba(30,64,175,0.5)]">
                  <img
                    src={clinicImage}
                    alt="Veterinary clinic using SnoutiQ platform"
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
            <div className="mb-12 text-center">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                App Features
              </h2>
              <p className="text-slate-600 max-w-2xl mx-auto">
                Everything you need to run a modern veterinary clinic.
              </p>
            </div>

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7">
                <Bell className="h-10 w-10 text-blue-400 mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Autonomous Push Notifications
                </h3>
                <p className="text-slate-600">
                  Send automated reminders for vaccinations, appointments, and follow-ups
                  directly to pet parents&apos; phones.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7">
                <MessageSquare className="h-10 w-10 text-blue-400 mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  WhatsApp Integration
                </h3>
                <p className="text-slate-600">
                  Communicate seamlessly with clients via WhatsApp without sharing your
                  personal mobile number.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7">
                <Users className="h-10 w-10 text-blue-400 mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  1-on-1 Pet Parent Connection
                </h3>
                <p className="text-slate-600">
                  Build stronger relationships with a dedicated app interface for your
                  registered pet parents.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7">
                <Video className="h-10 w-10 text-blue-400 mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Video Consultation
                </h3>
                <p className="text-slate-600">
                  Conduct secure video follow-ups and consultations directly through the
                  app.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-6 sm:p-7 lg:col-span-2">
                <Smartphone className="h-10 w-10 text-blue-400 mb-6" />
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  Monthly Subscription Model
                </h3>
                <p className="text-slate-600">
                  Enjoy predictable, recurring revenue for your clinic while providing
                  premium digital services to your clients. Keep your existing clients
                  engaged and easily onboard new ones.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section
          className="py-16 sm:py-20"
          style={{ contentVisibility: "auto", containIntrinsicSize: "1px 840px" }}
        >
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid items-center gap-8 md:grid-cols-2 lg:gap-10">
              <div>
                <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-6">
                  Who is this for?
                </h2>

                <p className="mb-6 text-lg text-slate-600">
                  Designed specifically for established veterinary clinics in India
                  looking to modernize their operations, improve client retention, and
                  add new revenue streams without the hassle of building custom software.
                </p>

                <ul className="space-y-4">
                  <li className="flex items-start gap-3">
                    <span className="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-blue-500">
                      <Check className="h-3.5 w-3.5" />
                    </span>
                    <span className="text-slate-700">
                      Clinics managing 50+ active pet parents
                    </span>
                  </li>

                  <li className="flex items-start gap-3">
                    <span className="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-blue-500">
                      <Check className="h-3.5 w-3.5" />
                    </span>
                    <span className="text-slate-700">
                      Vets tired of using personal WhatsApp for work
                    </span>
                  </li>

                  <li className="flex items-start gap-3">
                    <span className="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-blue-500">
                      <Check className="h-3.5 w-3.5" />
                    </span>
                    <span className="text-slate-700">
                      Practices looking to offer premium digital memberships
                    </span>
                  </li>
                </ul>
              </div>

              <div
                id="clinic-onboarding-form"
                className="rounded-3xl border border-slate-200 bg-slate-50 p-6 md:p-8"
              >
                <div className="mb-6 text-center">
                  <h3 className="font-display text-2xl font-bold text-slate-900 mb-2">
                    Talk to the Product Team
                  </h3>
                  <p className="text-slate-600">
                    Share your details to get pricing, implementation plan, and a tailored platform walkthrough.
                  </p>
                </div>

                {isSubmitted ? (
                  <div className="text-center py-8">
                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-500/20 text-blue-500 mb-6">
                      <Check className="h-7 w-7" />
                    </div>
                    <h4 className="text-xl font-bold text-slate-900 mb-2">
                      Thank You! We&apos;ll Reach Out Shortly.
                    </h4>
                    <p className="text-slate-600">
                      Our team will contact you shortly to discuss onboarding for your clinic.
                    </p>
                  </div>
                ) : (
                  <form onSubmit={handleSubmit} className="space-y-5">
                    <div>
                      <label
                        htmlFor="clinicName"
                        className="block text-sm font-medium text-slate-700 mb-2"
                      >
                        Clinic Name
                      </label>
                      <input
                        type="text"
                        id="clinicName"
                        required
                        value={formData.clinicName}
                        onChange={handleChange}
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="e.g. City Pet Care Clinic"
                      />
                    </div>

                    <div>
                      <label
                        htmlFor="contactName"
                        className="block text-sm font-medium text-slate-700 mb-2"
                      >
                        Contact Person
                      </label>
                      <input
                        type="text"
                        id="contactName"
                        required
                        value={formData.contactName}
                        onChange={handleChange}
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="e.g. Dr. Aditi Sharma"
                      />
                    </div>

                    <div>
                      <label
                        htmlFor="mobile"
                        className="block text-sm font-medium text-slate-700 mb-2"
                      >
                        Mobile Number
                      </label>
                      <input
                        type="tel"
                        id="mobile"
                        required
                        value={formData.mobile}
                        onChange={handleChange}
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="+91 98765 43210"
                      />
                    </div>

                    <div>
                      <label
                        htmlFor="city"
                        className="block text-sm font-medium text-slate-700 mb-2"
                      >
                        City
                      </label>
                      <input
                        type="text"
                        id="city"
                        required
                        value={formData.city}
                        onChange={handleChange}
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="e.g. Bengaluru"
                      />
                    </div>

                    {submitError ? (
                      <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {submitError}
                      </p>
                    ) : null}

                    <Button
                      type="submit"
                      size="lg"
                      disabled={isSubmitting}
                      className="w-full mt-6 bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700"
                    >
                      {isSubmitting ? "Submitting..." : "Submit Request"}
                    </Button>
                  </form>
                )}
              </div>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  );
}

