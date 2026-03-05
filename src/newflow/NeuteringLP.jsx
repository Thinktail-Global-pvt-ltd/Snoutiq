'use client';

import React, { useState } from 'react';
import { LPNavbar } from './LPNavbar';
import { Button } from './NewButton';
import { ShieldCheck, Scissors, HeartPulse, CheckCircle2 } from 'lucide-react';

export default function NeuteringLP() {
  const [isSubmitted, setIsSubmitted] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    setIsSubmitted(true);
  };

  return (
    <div className="flex min-h-screen flex-col bg-white text-slate-900">
      <LPNavbar />

      <main className="flex-1 pb-20 md:pb-0">
        {/* Hero Section */}
        <section className="relative overflow-hidden py-16 lg:py-24">
          <div className="absolute inset-0 bg-[url('https://picsum.photos/seed/catvet/1920/1080?blur=4')] bg-cover bg-center opacity-10"></div>

          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <span className="inline-block rounded-full bg-brand/20 px-4 py-1.5 text-sm font-medium text-brand mb-6">
                  Delhi NCR Only
                </span>
                <h1 className="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl mb-6">
                  Safe, Affordable Pet Neutering &amp; Spaying — Delhi NCR
                </h1>
                <p className="text-xl text-slate-700 mb-8">
                  Professional surgical services for dogs and cats. Experienced surgeons, proper facilities, and comprehensive post-op care guidance.
                </p>

                <div className="mb-8">
                  <p className="text-sm text-slate-600 mb-1">Starting from</p>
                  <p className="font-display text-4xl font-bold text-brand">₹3,999</p>
                  <p className="text-xs text-slate-600 mt-1">Prices vary by pet type, sex, and weight</p>
                </div>

                <ul className="space-y-3 text-slate-700 mb-8">
                  <li className="flex items-center gap-3">
                    <Scissors className="h-5 w-5 text-brand" />
                    <span>Dog neutering / Cat spaying / Dog spaying</span>
                  </li>
                  <li className="flex items-center gap-3">
                    <ShieldCheck className="h-5 w-5 text-brand" />
                    <span>Experienced surgeons &amp; proper clinical facility</span>
                  </li>
                  <li className="flex items-center gap-3">
                    <HeartPulse className="h-5 w-5 text-brand" />
                    <span>Health benefits &amp; behaviour improvement</span>
                  </li>
                </ul>
              </div>

              <div
                id="booking-form"
                className="rounded-3xl border border-slate-200 bg-slate-50 p-8 shadow-2xl"
              >
                <div className="text-center mb-6">
                  <h3 className="font-display text-2xl font-bold text-slate-900 mb-2">
                    Book Surgery Consultation
                  </h3>
                  <p className="text-slate-600">Schedule a pre-op checkup today</p>
                </div>

                {isSubmitted ? (
                  <div className="text-center py-8">
                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand/20 text-brand mb-6">
                      <span className="text-2xl">✓</span>
                    </div>
                    <h4 className="text-xl font-bold text-slate-900 mb-2">Request Received!</h4>
                    <p className="text-slate-600">
                      Our team will contact you shortly to discuss the procedure and schedule a pre-operative consultation.
                    </p>
                  </div>
                ) : (
                  <form onSubmit={handleSubmit} className="space-y-5">
                    {/* <!-- GA CONVERSION TAG HERE --> */}
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label htmlFor="petType" className="block text-sm font-medium text-slate-700 mb-2">
                          Pet Type
                        </label>
                        <select
                          id="petType"
                          required
                          className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                        >
                          <option value="">Select...</option>
                          <option value="dog">Dog</option>
                          <option value="cat">Cat</option>
                        </select>
                      </div>

                      <div>
                        <label htmlFor="petSex" className="block text-sm font-medium text-slate-700 mb-2">
                          Sex
                        </label>
                        <select
                          id="petSex"
                          required
                          className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                        >
                          <option value="">Select...</option>
                          <option value="male">Male (Neutering)</option>
                          <option value="female">Female (Spaying)</option>
                        </select>
                      </div>
                    </div>

                    <div>
                      <label htmlFor="breed" className="block text-sm font-medium text-slate-700 mb-2">
                        Breed (Approximate Weight)
                      </label>
                      <input
                        type="text"
                        id="breed"
                        required
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                        placeholder="e.g. Indie (15kg)"
                      />
                    </div>

                    <div>
                      <label htmlFor="mobile" className="block text-sm font-medium text-slate-700 mb-2">
                        Mobile Number
                      </label>
                      <input
                        type="tel"
                        id="mobile"
                        required
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                        placeholder="+91 98765 43210"
                      />
                    </div>

                    <div>
                      <label htmlFor="area" className="block text-sm font-medium text-slate-700 mb-2">
                        Area / Locality
                      </label>
                      <select
                        id="area"
                        required
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                      >
                        <option value="">Select Region...</option>
                        <option value="delhi">Delhi</option>
                        <option value="gurugram">Gurugram</option>
                        <option value="noida">Noida</option>
                        <option value="ghaziabad">Ghaziabad</option>
                        <option value="faridabad">Faridabad</option>
                      </select>
                    </div>

                    <Button type="submit" size="lg" className="w-full mt-4 h-14 text-lg">
                      Book Consultation
                    </Button>
                  </form>
                )}
              </div>
            </div>
          </div>
        </section>

        {/* Why Neuter */}
        <section className="py-20 bg-slate-50">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-16">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                Why Neuter or Spay?
              </h2>
              <p className="text-slate-600">
                It&apos;s one of the best decisions you can make for your pet&apos;s long-term health.
              </p>
            </div>

            <div className="grid md:grid-cols-3 gap-8">
              <div className="rounded-2xl border border-slate-200 bg-white p-8">
                <div className="flex items-center gap-4 mb-6">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/20 text-brand">
                    <HeartPulse className="h-6 w-6" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900">Health Benefits</h3>
                </div>
                <p className="text-slate-600">
                  Spaying females prevents uterine infections and breast tumors, which are malignant or cancerous in about 50% of dogs and 90% of cats. Neutering males prevents testicular cancer and some prostate problems.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-8">
                <div className="flex items-center gap-4 mb-6">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/20 text-brand">
                    <CheckCircle2 className="h-6 w-6" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900">Behavioral Improvement</h3>
                </div>
                <p className="text-slate-600">
                  Neutered males are less likely to roam away from home or mark their territory by spraying strong-smelling urine. Spayed females won&apos;t go into heat, reducing yowling and frequent urination.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-8">
                <div className="flex items-center gap-4 mb-6">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/20 text-brand">
                    <ShieldCheck className="h-6 w-6" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900">Population Control</h3>
                </div>
                <p className="text-slate-600">
                  By spaying or neutering your pet, you are actively helping to reduce the number of homeless animals on the streets and in shelters across India.
                </p>
              </div>
            </div>
          </div>
        </section>

        {/* FAQ */}
        <section className="py-20">
          <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-12">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                Frequently Asked Questions
              </h2>
            </div>

            <div className="space-y-4">
              <div className="border border-slate-200 rounded-xl p-6 bg-slate-50">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">
                  What age should I neuter/spay my pet?
                </h3>
                <p className="text-slate-600">
                  Generally, it&apos;s recommended between 6 to 9 months of age. However, healthy adults can also be safely operated on. The vet will advise the best time during the pre-op consultation based on breed and health.
                </p>
              </div>

              <div className="border border-slate-200 rounded-xl p-6 bg-slate-50">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">
                  What is the recovery time?
                </h3>
                <p className="text-slate-600">
                  Most pets recover within 10-14 days. You will need to restrict their activity (no running or jumping) and ensure they wear an E-collar (cone) to prevent licking the incision site.
                </p>
              </div>

              <div className="border border-slate-200 rounded-xl p-6 bg-slate-50">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">Is the surgery painful?</h3>
                <p className="text-slate-600">
                  The surgery is performed under general anesthesia, so your pet will not feel anything during the procedure. The vet will prescribe pain medication for the days following the surgery to ensure their comfort.
                </p>
              </div>

              <div className="border border-slate-200 rounded-xl p-6 bg-slate-50">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">What to do before surgery?</h3>
                <p className="text-slate-600">
                  Your pet will need to fast (no food or water) for 8-12 hours before the surgery to prevent complications from anesthesia. A pre-anesthetic blood test is also highly recommended to ensure their organs are functioning well.
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>

      {/* Sticky Mobile CTA */}
      <div className="md:hidden fixed bottom-0 left-0 right-0 p-4 bg-white/95 backdrop-blur-md border-t border-slate-200 z-50">
        <a href="#booking-form">
          <Button size="lg" className="w-full text-lg h-14 shadow-lg shadow-brand/20">
            Book Consultation
          </Button>
        </a>
      </div>

      <footer className="bg-white border-t border-slate-200 py-8 text-center text-sm text-gray-500 pb-24 md:pb-8">
        <p>&copy; {new Date().getFullYear()} SnoutiQ. All rights reserved.</p>
      </footer>
    </div>
  );
}