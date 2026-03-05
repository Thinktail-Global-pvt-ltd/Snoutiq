// CatNeuteringDelhi.jsx
import React from "react";
import { Helmet } from "react-helmet-async";
import { Link } from "react-router-dom";

import { Navbar } from "./Navbar";
import { Footer } from "./NewFooter";
import { Button } from "./NewButton";

export default function CatNeuteringDelhi() {
  const title = "Cat Neutering & Spaying Delhi NCR — Safe & Painless | SnoutiQ";
  const description =
    "Expert cat neutering and spaying surgery in Delhi NCR. Includes pre-op blood test, surgery, and post-op consultation. Starting ₹4,999.";
  const keywords =
    "cat neutering Delhi, cat spaying Delhi NCR, cat neutering cost Delhi, safe cat neutering";
  const canonical = "https://snoutiq.com/services/cat-neutering-delhi";

  return (
    <div className="flex min-h-screen flex-col bg-white text-slate-900">
      <Helmet>
        <title>{title}</title>
        <meta name="description" content={description} />
        <meta name="keywords" content={keywords} />
        <link rel="canonical" href={canonical} />

        {/* Open Graph */}
        <meta property="og:title" content={title} />
        <meta property="og:description" content={description} />
        <meta property="og:url" content={canonical} />
      </Helmet>

      <Navbar />

      <main className="flex-1">
        {/* SECTION 1: HERO */}
        <section className="bg-gradient-to-br from-brand-light via-white to-white py-16 md:py-24 px-4">
          <div className="max-w-4xl mx-auto text-center">
            <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-6 leading-tight">
              Safe &amp; Painless Cat Neutering &amp; Spaying in Delhi NCR
            </h1>
            <p className="text-lg md:text-xl text-slate-600 mb-8 max-w-2xl mx-auto">
              Expert surgical care for your cat. Includes pre-op blood test, surgery, and post-op
              consultation by experienced surgeons.
            </p>

            <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mb-8">
              <div className="text-center sm:text-right">
                <p className="text-sm text-slate-500">Starting from</p>
                <p className="text-3xl font-bold text-brand">₹4,999</p>
              </div>

              <div className="hidden sm:block w-px h-12 bg-slate-200" />

              <Link to="/lp/neutering" className="w-full sm:w-auto">
                <Button variant="primary" size="lg" className="w-full">
                  Book Appointment Now
                </Button>
              </Link>
            </div>
          </div>
        </section>

        {/* SECTION 2: TRUST SIGNALS BAR */}
        <section className="bg-brand text-slate-900 py-8 px-4">
          <div className="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div>
              <div className="text-3xl font-bold mb-1">4.8/5</div>
              <div className="text-blue-200 text-sm">Average Rating</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-1">50+</div>
              <div className="text-blue-200 text-sm">Verified Clinics</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-1">5,000+</div>
              <div className="text-blue-200 text-sm">Happy Pets</div>
            </div>
            <div>
              <div className="text-3xl font-bold mb-1">100%</div>
              <div className="text-blue-200 text-sm">Verified Vets</div>
            </div>
          </div>
        </section>

        {/* SECTION 3: WHAT'S INCLUDED */}
        <section className="py-16 md:py-24 px-4 bg-slate-50">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-3xl font-bold text-center mb-12">
              What&apos;s Included in the Package
            </h2>

            <div className="prose prose-lg prose-slate max-w-none">
              <p>
                When you book with SnoutiQ, you are guaranteed a comprehensive and stress-free
                experience. Our packages are designed to cover everything your pet needs, with no
                hidden costs or surprises.
              </p>

              <div className="grid md:grid-cols-2 gap-8 mt-8">
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                  <h3 className="text-xl font-bold text-brand mb-4">Core Procedures</h3>
                  <ul className="space-y-3">
                    <li className="flex gap-3">
                      <span className="text-brand font-bold">✓</span> Comprehensive physical examination
                    </li>
                    <li className="flex gap-3">
                      <span className="text-brand font-bold">✓</span> All required medical supplies
                    </li>
                    <li className="flex gap-3">
                      <span className="text-brand font-bold">✓</span> Administration by experienced vets
                    </li>
                  </ul>
                </div>

                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                  <h3 className="text-xl font-bold text-brand mb-4">Post-Care &amp; Support</h3>
                  <ul className="space-y-3">
                    <li className="flex gap-3">
                      <span className="text-brand font-bold">✓</span> Digital health records
                    </li>
                    <li className="flex gap-3">
                      <span className="text-brand font-bold">✓</span> Post-procedure consultation
                    </li>
                    <li className="flex gap-3">
                      <span className="text-brand font-bold">✓</span> 24/7 WhatsApp support access
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* SECTION 4: VET SHOWCASE */}
        <section className="py-16 md:py-24 px-4 bg-white">
          <div className="max-w-5xl mx-auto">
            <h2 className="text-3xl font-bold text-center mb-12">Meet Our Expert Vets</h2>
            <div className="grid md:grid-cols-3 gap-6">
              {[1, 2, 3].map((i) => (
                <div
                  key={i}
                  className="rounded-2xl border border-slate-200 overflow-hidden shadow-sm"
                >
                  <div className="h-48 bg-brand-light flex items-center justify-center">
                    <div className="w-24 h-24 rounded-full bg-white border-4 border-brand/20 flex items-center justify-center text-2xl font-bold text-brand">
                      Dr
                    </div>
                  </div>
                  <div className="p-6 text-center">
                    <h3 className="font-bold text-lg mb-1">Verified Veterinarian</h3>
                    <p className="text-brand text-sm mb-3">7+ Years Experience</p>
                    <p className="text-slate-600 text-sm">
                      Expert in small animal medicine and surgery.
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* SECTION 5: HOW IT WORKS */}
        <section className="py-16 md:py-24 px-4 bg-slate-50">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-3xl font-bold text-center mb-12">How It Works</h2>
            <div className="grid md:grid-cols-3 gap-8 text-center">
              <div>
                <div className="w-16 h-16 mx-auto bg-brand text-slate-900 rounded-full flex items-center justify-center text-2xl font-bold mb-4">
                  1
                </div>
                <h3 className="font-bold text-xl mb-2">Book Online</h3>
                <p className="text-slate-600">
                  Fill out our simple form and choose your preferred time and location.
                </p>
              </div>
              <div>
                <div className="w-16 h-16 mx-auto bg-brand text-slate-900 rounded-full flex items-center justify-center text-2xl font-bold mb-4">
                  2
                </div>
                <h3 className="font-bold text-xl mb-2">Get Confirmation</h3>
                <p className="text-slate-600">
                  Our team will call you to confirm the details and answer any questions.
                </p>
              </div>
              <div>
                <div className="w-16 h-16 mx-auto bg-brand text-slate-900 rounded-full flex items-center justify-center text-2xl font-bold mb-4">
                  3
                </div>
                <h3 className="font-bold text-xl mb-2">Receive Care</h3>
                <p className="text-slate-600">
                  Visit the clinic or connect online for expert veterinary care.
                </p>
              </div>
            </div>
          </div>
        </section>

        {/* SECTION 8: WHY SNOUTIQ */}
        <section className="py-16 md:py-24 px-4 bg-white">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-3xl font-bold text-center mb-12">Why Choose SnoutiQ?</h2>

            <div className="space-y-6">
              {[
                {
                  title: "Verified Experts Only",
                  desc: "We strictly vet all our partner clinics and veterinarians. Minimum 5 years of clinical experience required.",
                },
                {
                  title: "Transparent Pricing",
                  desc: "No hidden fees, no surprise charges. You know exactly what you're paying for upfront.",
                },
                {
                  title: "Digital Records",
                  desc: "All your pet's medical history, prescriptions, and vaccination records are stored securely online.",
                },
                {
                  title: "Dedicated Support",
                  desc: "Our care team is available via WhatsApp to assist you before, during, and after your appointment.",
                },
                {
                  title: "Convenience",
                  desc: "Book appointments instantly without waiting on hold or dealing with busy clinic receptionists.",
                },
              ].map((item, i) => (
                <div
                  key={i}
                  className="flex gap-4 p-6 rounded-2xl bg-slate-50 border border-slate-100"
                >
                  <div className="text-brand text-2xl font-bold">✓</div>
                  <div>
                    <h3 className="font-bold text-lg mb-2">{item.title}</h3>
                    <p className="text-slate-600">{item.desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* SECTION 12: LONG FORM FAQ */}
        <section className="py-16 md:py-24 px-4 bg-slate-50">
          <div className="max-w-3xl mx-auto">
            <h2 className="text-3xl font-bold text-center mb-12">Frequently Asked Questions</h2>
            <div className="space-y-4">
              {[
                {
                  q: "Is the payment secure?",
                  a: "Yes, we use industry-standard encryption for all payments. You can pay via UPI, cards, or netbanking.",
                },
                {
                  q: "Can I reschedule my appointment?",
                  a: "Absolutely. You can reschedule up to 2 hours before your appointment time without any penalty.",
                },
                {
                  q: "What if I'm not satisfied with the service?",
                  a: "We have a strict quality control process. If you face any issues, our support team will resolve them or provide a refund.",
                },
                {
                  q: "Do you offer home services?",
                  a: "Currently, our surgical and vaccination packages are clinic-based to ensure the highest medical standards and hygiene.",
                },
              ].map((faq, i) => (
                <div key={i} className="bg-white p-6 rounded-xl border border-slate-200">
                  <h3 className="font-bold text-lg mb-2">{faq.q}</h3>
                  <p className="text-slate-600">{faq.a}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* SECTION 13: FINAL CTA */}
        <section className="py-16 md:py-24 px-4 bg-brand text-center text-slate-900">
          <div className="max-w-3xl mx-auto">
            <h2 className="text-3xl md:text-4xl font-bold mb-6">
              Ready to give your pet the best care?
            </h2>
            <p className="text-xl text-blue-100 mb-8">
              Book your appointment today and join thousands of happy pet parents.
            </p>

            <div className="flex flex-col sm:flex-row justify-center gap-4">
              <Link to="/lp/neutering">
                <Button
                  variant="primary"
                  size="lg"
                  className="w-full sm:w-auto bg-accent hover:bg-accent-hover text-slate-900"
                >
                  Book Appointment Now
                </Button>
              </Link>

              <a href="https://wa.me/919999999999" target="_blank" rel="noopener noreferrer">
                <Button
                  variant="outline"
                  size="lg"
                  className="w-full sm:w-auto border-white text-slate-900 hover:bg-slate-100"
                >
                  Chat on WhatsApp
                </Button>
              </a>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  );
}