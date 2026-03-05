import React, { useState } from "react";
import { LPNavbar } from "../newflow/LPNavbar";
import { NewButton as Button } from "../newflow/NewButton";

import {
  ShieldCheck,
  Star,
  Clock,
  Users,
  Video,
  Phone,
} from "lucide-react";

export default function NewVideoConsultationLP() {
  const [isSubmitted, setIsSubmitted] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    setIsSubmitted(true);
  };

  const vets = [
    {
      name: "Dr. Sharma",
      spec: "Small Animals, Dogs",
      exp: "12 Years",
      city: "Delhi",
      img: "https://picsum.photos/seed/vet1/400/400",
    },
    {
      name: "Dr. Priya",
      spec: "Cats, Exotic Pets",
      exp: "8 Years",
      city: "Mumbai",
      img: "https://picsum.photos/seed/vet2/400/400",
    },
    {
      name: "Dr. Reddy",
      spec: "Dogs, Cats",
      exp: "14 Years",
      city: "Bangalore",
      img: "https://picsum.photos/seed/vet3/400/400",
    },
    {
      name: "Dr. Patel",
      spec: "Small Animals",
      exp: "9 Years",
      city: "Ahmedabad",
      img: "https://picsum.photos/seed/vet4/400/400",
    },
    {
      name: "Dr. Singh",
      spec: "Dogs, Behavior",
      exp: "11 Years",
      city: "Chandigarh",
      img: "https://picsum.photos/seed/vet5/400/400",
    },
    {
      name: "Dr. Iyer",
      spec: "Cats, Nutrition",
      exp: "7 Years",
      city: "Chennai",
      img: "https://picsum.photos/seed/vet6/400/400",
    },
    {
      name: "Dr. Gupta",
      spec: "Small Animals",
      exp: "10 Years",
      city: "Pune",
      img: "https://picsum.photos/seed/vet7/400/400",
    },
    {
      name: "Dr. Verma",
      spec: "Dogs, Cats",
      exp: "15 Years",
      city: "Lucknow",
      img: "https://picsum.photos/seed/vet8/400/400",
    },
  ];

  return (
    <div className="flex min-h-screen flex-col bg-white text-slate-900">
      <LPNavbar />

      <main className="flex-1 pb-20 md:pb-0">
        {/* Hero Section */}
        <section className="relative overflow-hidden py-16 lg:py-24">
          <div className="absolute inset-0 bg-[url('https://picsum.photos/seed/dogvet/1920/1080?blur=4')] bg-cover bg-center opacity-10" />

          <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <span className="inline-block rounded-full bg-brand/20 px-4 py-1.5 text-sm font-medium text-brand mb-6">
                  Online Vet Consultation India
                </span>

                <h1 className="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl mb-6">
                  Talk to a Vet Online — 200+ Verified Online Veterinarians
                </h1>

                <p className="text-xl text-slate-700 mb-8">
                  Get instant expert advice for your pet. 15-minute video call with
                  vets having 7+ years of experience.
                </p>

                <div className="flex flex-col sm:flex-row gap-6 mb-8">
                  <div className="rounded-xl border border-brand/30 bg-brand/10 p-4">
                    <p className="text-sm text-slate-600 mb-1">Day Consultation</p>
                    <p className="font-display text-3xl font-bold text-brand">₹499</p>
                    <p className="text-xs text-slate-600 mt-1">8 AM - 10 PM</p>
                  </div>

                  <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p className="text-sm text-slate-600 mb-1">Night Consultation</p>
                    <p className="font-display text-3xl font-bold text-slate-900">
                      ₹549
                    </p>
                    <p className="text-xs text-slate-600 mt-1">8 PM - 8 AM</p>
                  </div>
                </div>

                <ul className="space-y-3 text-slate-700 mb-8">
                  <li className="flex items-center gap-3">
                    <ShieldCheck className="h-5 w-5 text-brand" />
                    <span>Verified vets with 7+ years experience</span>
                  </li>
                  <li className="flex items-center gap-3">
                    <Video className="h-5 w-5 text-brand" />
                    <span>15-minute secure video call</span>
                  </li>
                  <li className="flex items-center gap-3">
                    <Clock className="h-5 w-5 text-brand" />
                    <span>Available 24/7 across India</span>
                  </li>
                </ul>
              </div>

              <div
                id="booking-form"
                className="rounded-3xl border border-slate-200 bg-slate-50 p-8 shadow-2xl"
              >
                <div className="text-center mb-6">
                  <h3 className="font-display text-2xl font-bold text-slate-900 mb-2">
                    Consult Vet Online
                  </h3>
                  <p className="text-slate-600">
                    Enter your number to connect instantly
                  </p>
                </div>

                {isSubmitted ? (
                  <div className="text-center py-8">
                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand/20 text-brand mb-6">
                      <span className="text-2xl">✓</span>
                    </div>
                    <h4 className="text-xl font-bold text-slate-900 mb-2">
                      Connecting...
                    </h4>
                    <p className="text-slate-600">
                      We are assigning the best available vet for you. Please keep
                      your phone handy.
                    </p>
                  </div>
                ) : (
                  <form onSubmit={handleSubmit} className="space-y-5">
                    {/* <!-- GA CONVERSION TAG HERE --> */}
                    <div>
                      <label
                        htmlFor="mobile"
                        className="block text-sm font-medium text-slate-700 mb-2"
                      >
                        Mobile Number
                      </label>

                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                          <Phone className="h-5 w-5 text-gray-500" />
                        </div>

                        <input
                          type="tel"
                          id="mobile"
                          required
                          className="w-full rounded-xl border border-slate-200 bg-white pl-12 pr-4 py-4 text-slate-900 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand text-lg"
                          placeholder="+91 98765 43210"
                        />
                      </div>
                    </div>

                    <Button type="submit" size="lg" className="w-full mt-4 h-14 text-lg">
                      Connect with a Vet Now
                    </Button>

                    <p className="text-xs text-center text-gray-500 mt-4">
                      By proceeding, you agree to our terms of service.
                    </p>
                  </form>
                )}
              </div>
            </div>
          </div>
        </section>

        {/* Trust Bar */}
        <section className="border-y border-slate-200 bg-slate-50 py-8">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-2 md:grid-cols-5 gap-6 text-center">
              <div className="flex flex-col items-center justify-center">
                <span className="text-2xl font-bold text-slate-900">200+</span>
                <span className="text-xs text-slate-600 uppercase tracking-wider mt-1">
                  Verified Vets
                </span>
              </div>

              <div className="flex flex-col items-center justify-center">
                <div className="flex text-brand mb-1">
                  {Array(5)
                    .fill(0)
                    .map((_, i) => (
                      <Star key={i} className="h-4 w-4" />
                    ))}
                </div>
                <span className="text-2xl font-bold text-slate-900">4.8</span>
                <span className="text-xs text-slate-600 uppercase tracking-wider mt-1">
                  Average Rating
                </span>
              </div>

              <div className="flex flex-col items-center justify-center">
                <span className="text-2xl font-bold text-slate-900">7+ Yrs</span>
                <span className="text-xs text-slate-600 uppercase tracking-wider mt-1">
                  Min. Experience
                </span>
              </div>

              <div className="flex flex-col items-center justify-center">
                <span className="text-2xl font-bold text-slate-900">24/7</span>
                <span className="text-xs text-slate-600 uppercase tracking-wider mt-1">
                  Availability
                </span>
              </div>

              <div className="flex flex-col items-center justify-center col-span-2 md:col-span-1">
                <span className="text-2xl font-bold text-slate-900">10,000+</span>
                <span className="text-xs text-slate-600 uppercase tracking-wider mt-1">
                  Pet Parents
                </span>
              </div>
            </div>
          </div>
        </section>

        {/* How it works */}
        <section className="py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-16">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                How Online Vet Consultation Works
              </h2>
            </div>

            <div className="grid md:grid-cols-3 gap-10">
              <div className="text-center">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 border border-brand/30 text-brand mb-6">
                  <Phone className="h-8 w-8" />
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  1. Enter Details
                </h3>
                <p className="text-slate-600">
                  Provide your mobile number and pay the fixed consultation fee
                  securely.
                </p>
              </div>

              <div className="text-center">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 border border-brand/30 text-brand mb-6">
                  <Users className="h-8 w-8" />
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  2. Instant Match
                </h3>
                <p className="text-slate-600">
                  We automatically assign the best available verified vet for your
                  pet&apos;s needs.
                </p>
              </div>

              <div className="text-center">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 border border-brand/30 text-brand mb-6">
                  <Video className="h-8 w-8" />
                </div>
                <h3 className="text-xl font-semibold text-slate-900 mb-3">
                  3. Video Call
                </h3>
                <p className="text-slate-600">
                  Connect on a 15-minute secure video call and get expert advice
                  and triage.
                </p>
              </div>
            </div>
          </div>
        </section>

        {/* Vets Grid */}
        <section className="py-20 bg-slate-50">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-16">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                Meet Our Verified Vets
              </h2>
              <p className="text-slate-600">
                Over 200+ experienced veterinarians ready to help your pet.
              </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {vets.map((vet, i) => (
                <div
                  key={i}
                  className="rounded-2xl border border-slate-200 bg-white overflow-hidden"
                >
                  <div className="relative h-48 w-full bg-gray-800">
                    <img
                      src={vet.img}
                      alt={vet.name}
                      className="absolute inset-0 h-full w-full object-cover"
                      loading="lazy"
                      referrerPolicy="no-referrer"
                    />
                  </div>

                  <div className="p-5">
                    <h4 className="font-bold text-lg text-slate-900 mb-1">
                      {vet.name}
                    </h4>
                    <p className="text-brand text-sm mb-3">{vet.spec}</p>

                    <div className="flex justify-between text-xs text-slate-600">
                      <span className="flex items-center gap-1">
                        <Clock className="h-3 w-3" /> {vet.exp}
                      </span>
                      <span className="flex items-center gap-1">
                        <ShieldCheck className="h-3 w-3" /> {vet.city}
                      </span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Reviews */}
        <section className="py-20">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-16">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                What Pet Parents Say
              </h2>
            </div>

            <div className="grid md:grid-cols-3 gap-8">
              {[
                {
                  text:
                    'Talk to a vet online was so easy. Dr. Sharma was very patient and guided me on what to do when my dog ate chocolate late at night.',
                  name: "Rohan K.",
                },
                {
                  text:
                    "The online vet consultation India service is a lifesaver. Fixed price of ₹399 is very reasonable for the quality of advice received.",
                  name: "Sneha M.",
                },
                {
                  text:
                    "I always consult vet online here before panicking. The 15-minute call is enough to understand if a clinic visit is actually needed.",
                  name: "Vikram S.",
                },
              ].map((r, i) => (
                <div key={i} className="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                  <div className="flex text-brand mb-4">
                    {Array(5)
                      .fill(0)
                      .map((_, j) => (
                        <Star key={j} className="h-4 w-4" />
                      ))}
                  </div>
                  <p className="text-slate-700 italic mb-6">
                    &quot;{r.text}&quot;
                  </p>
                  <p className="font-semibold text-slate-900">{r.name}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* FAQ */}
        <section className="py-20 bg-slate-50">
          <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-12">
              <h2 className="font-display text-3xl font-bold text-slate-900 sm:text-4xl mb-4">
                Frequently Asked Questions
              </h2>
            </div>

            <div className="space-y-4">
              {[
                {
                  q: "Can I talk to a vet online right now?",
                  a: "Yes, our service is available 24/7. Once you enter your details and complete the payment, you will be connected to a vet almost instantly.",
                },
                {
                  q: "Is ₹399 the fixed price?",
                  a: "Yes, ₹399 is the fixed price for a 15-minute daytime consultation (8 AM - 10 PM). There are no hidden charges.",
                },
                {
                  q: "What happens at night?",
                  a: "Night consultations (8 PM - 8 AM) are priced at ₹549. Availability depends on the vets online at that hour, but we strive to maintain 24/7 coverage.",
                },
                {
                  q: "Can a video call replace a clinic visit?",
                  a: "Online consultations are for triage, minor issues, and advice. They cannot replace a physical examination for serious emergencies or when diagnostic tests are needed.",
                },
              ].map((f, i) => (
                <div key={i} className="border border-slate-200 rounded-xl p-6 bg-white">
                  <h3 className="text-lg font-semibold text-slate-900 mb-2">
                    {f.q}
                  </h3>
                  <p className="text-slate-600">{f.a}</p>
                </div>
              ))}
            </div>
          </div>
        </section>
      </main>

      {/* Sticky Mobile CTA */}
      <div className="md:hidden fixed bottom-0 left-0 right-0 p-4 bg-white/95 backdrop-blur-md border-t border-slate-200 z-50">
        <a href="#booking-form">
          <Button size="lg" className="w-full text-lg h-14 shadow-lg shadow-brand/20" type="button">
            Connect with a Vet Now
          </Button>
        </a>
      </div>

      {/* LP Footer (same as your original) */}
      <footer className="bg-white border-t border-slate-200 py-8 text-center text-sm text-gray-500 pb-24 md:pb-8">
        <p>&copy; {new Date().getFullYear()} SnoutiQ. All rights reserved.</p>
      </footer>
    </div>
  );
}