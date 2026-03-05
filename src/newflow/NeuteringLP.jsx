import React, { useState } from 'react';
import { LPNavbar } from './LPNavbar';
import { Button } from './NewButton';
import { ShieldCheck, Scissors, HeartPulse, CheckCircle2 } from 'lucide-react';

const NCR_AREAS = [
  'Delhi',
  'Gurugram',
  'Noida',
  'Ghaziabad',
  'Faridabad',
  'Greater Noida',
];

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
        <section className="relative overflow-hidden py-14 md:py-20 lg:py-24">
          <div className="absolute inset-0 bg-[url('https://picsum.photos/seed/catvet/1920/1080?blur=4')] bg-cover bg-center opacity-10" />

          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid gap-8 md:grid-cols-[1.05fr_0.95fr] md:items-start lg:gap-12">
              <div className="text-center md:pt-3 md:text-left">
                <span className="mb-6 inline-block rounded-full bg-brand/20 px-4 py-1.5 text-sm font-medium text-brand">
                  Delhi NCR Only
                </span>
                <h1 className="mb-5 font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                  Safe, Affordable Pet Neutering and Spaying - Delhi NCR
                </h1>
                <p className="mx-auto mb-8 max-w-xl text-base text-slate-700 sm:text-lg md:mx-0 md:text-xl">
                  Professional surgical services for dogs and cats. Experienced surgeons, proper facilities, and
                  clear post-op care guidance.
                </p>

                <div className="mb-8 md:mb-10">
                  <p className="mb-1 text-sm text-slate-600">Starting from</p>
                  <p className="font-display text-4xl font-bold text-brand">Rs 3,999</p>
                  <p className="mt-1 text-xs text-slate-600">Prices vary by pet type, sex, and weight</p>
                </div>

                <ul className="mx-auto mb-8 max-w-xl space-y-3 text-left text-slate-700 md:mx-0">
                  <li className="flex items-start gap-3">
                    <Scissors className="mt-0.5 h-5 w-5 shrink-0 text-brand" />
                    <span>Dog neutering, cat spaying, and dog spaying</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-brand" />
                    <span>Experienced surgeons and proper clinical facility</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <HeartPulse className="mt-0.5 h-5 w-5 shrink-0 text-brand" />
                    <span>Health benefits and behavior improvement</span>
                  </li>
                </ul>
              </div>

              <div
                id="booking-form"
                className="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-2xl md:sticky md:top-24 md:p-8"
              >
                <div className="mb-6 text-center">
                  <h3 className="mb-2 font-display text-2xl font-bold text-slate-900">Book Surgery Consultation</h3>
                  <p className="text-slate-600">Schedule a pre-op checkup today</p>
                </div>

                {isSubmitted ? (
                  <div className="py-8 text-center">
                    <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-brand/20 text-brand">
                      <CheckCircle2 className="h-8 w-8" />
                    </div>
                    <h4 className="mb-2 text-xl font-bold text-slate-900">Request Received!</h4>
                    <p className="text-slate-600">
                      Our team will contact you shortly to discuss the procedure and schedule a pre-operative
                      consultation.
                    </p>
                  </div>
                ) : (
                  <form onSubmit={handleSubmit} className="space-y-5">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      <div>
                        <label htmlFor="petType" className="mb-2 block text-sm font-medium text-slate-700">
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
                        <label htmlFor="petSex" className="mb-2 block text-sm font-medium text-slate-700">
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
                      <label htmlFor="breed" className="mb-2 block text-sm font-medium text-slate-700">
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
                      <label htmlFor="mobile" className="mb-2 block text-sm font-medium text-slate-700">
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
                      <label htmlFor="area" className="mb-2 block text-sm font-medium text-slate-700">
                        Area / Locality
                      </label>
                      <select
                        id="area"
                        required
                        className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                      >
                        <option value="">Select Region...</option>
                        {NCR_AREAS.map((area) => (
                          <option key={area} value={area.toLowerCase().replace(/\s+/g, '-')}>
                            {area}
                          </option>
                        ))}
                      </select>
                    </div>

                    <Button type="submit" size="lg" className="mt-4 h-14 w-full text-lg">
                      Book Consultation
                    </Button>
                  </form>
                )}
              </div>
            </div>
          </div>
        </section>

        <section className="bg-slate-50 py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-16 text-center">
              <h2 className="mb-4 font-display text-3xl font-bold text-slate-900 sm:text-4xl">Why Neuter or Spay?</h2>
              <p className="text-slate-600">
                It is one of the best decisions you can make for your pet's long-term health.
              </p>
            </div>

            <div className="grid gap-6 md:grid-cols-3 md:gap-8">
              <div className="rounded-2xl border border-slate-200 bg-white p-8">
                <div className="mb-6 flex items-center gap-4">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/20 text-brand">
                    <HeartPulse className="h-6 w-6" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900">Health Benefits</h3>
                </div>
                <p className="text-slate-600">
                  Spaying females helps prevent uterine infections and reduces risk of mammary tumors. Neutering males
                  helps prevent testicular cancer and some prostate conditions.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-8">
                <div className="mb-6 flex items-center gap-4">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/20 text-brand">
                    <CheckCircle2 className="h-6 w-6" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900">Behavioral Improvement</h3>
                </div>
                <p className="text-slate-600">
                  Neutered males are usually less likely to roam or mark territory. Spayed females do not go into heat,
                  which can reduce stress-related behavior at home.
                </p>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-8">
                <div className="mb-6 flex items-center gap-4">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/20 text-brand">
                    <ShieldCheck className="h-6 w-6" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900">Population Control</h3>
                </div>
                <p className="text-slate-600">
                  Spaying and neutering helps reduce unplanned litters and supports long-term animal welfare in urban
                  communities.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="py-20">
          <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div className="mb-12 text-center">
              <h2 className="mb-4 font-display text-3xl font-bold text-slate-900 sm:text-4xl">Frequently Asked Questions</h2>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h3 className="mb-2 text-lg font-semibold text-slate-900">What age should I neuter or spay my pet?</h3>
                <p className="text-slate-600">
                  Usually between 6 to 9 months, but healthy adults can also be operated safely. Final timing should be
                  decided after a vet consultation.
                </p>
              </div>

              <div className="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h3 className="mb-2 text-lg font-semibold text-slate-900">What is the recovery time?</h3>
                <p className="text-slate-600">
                  Most pets recover in around 10 to 14 days with restricted activity and proper wound care.
                </p>
              </div>

              <div className="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h3 className="mb-2 text-lg font-semibold text-slate-900">Is the surgery painful?</h3>
                <p className="text-slate-600">
                  Surgery is performed under anesthesia. Post-op pain medicines are provided to keep your pet comfortable.
                </p>
              </div>

              <div className="rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h3 className="mb-2 text-lg font-semibold text-slate-900">What should I do before surgery?</h3>
                <p className="text-slate-600">
                  Your vet will guide fasting duration before surgery and may recommend blood tests before anesthesia.
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>

      <div className="fixed bottom-0 left-0 right-0 z-50 border-t border-slate-200 bg-white/95 p-4 backdrop-blur-md md:hidden">
        <a href="#booking-form">
          <Button size="lg" className="h-14 w-full text-lg shadow-lg shadow-brand/20">
            Book Consultation
          </Button>
        </a>
      </div>

      <footer className="border-t border-slate-200 bg-white py-8 pb-24 text-center text-sm text-gray-500 md:pb-8">
        <p>&copy; {new Date().getFullYear()} SnoutiQ. All rights reserved.</p>
      </footer>
    </div>
  );
}

