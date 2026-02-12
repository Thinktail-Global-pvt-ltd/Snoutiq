// src/screen/Landingscreen.jsx
import React, { useMemo, useState } from "react";
import logo from "../assets/images/logo.png";
import doctorHero from "../assets/images/doctor_1.png";
import blogVaccination from "../assets/images/vaccination_schedule.jpeg";
import blogTickFever from "../assets/images/tickfever.png";
import blogFirstAid from "../assets/images/first_aid_tips.jpeg";
import { ArrowRight } from "lucide-react";
import { Clock, ShieldCheck, Headphones } from "lucide-react";
import { InstallCTA, IosInstallHint } from "../components/PwaInstallCTA";

const LandingScreen = ({ onStart, onVetAccess }) => {
  const [openFaq, setOpenFaq] = useState(null);

  const faqs = useMemo(
    () => [
      {
        q: "What can telemedicine veterinarians help with?",
        a: "Our veterinarians can provide guidance on behavioral issues, skin conditions, dietary questions, minor injuries, general health concerns, and help determine if an in-person clinic visit is needed. They assess symptoms and provide professional recommendations.",
      },
      {
        q: "What happens during a video consultation?",
        a: "You'll have a live video call with a licensed veterinarian. They'll ask about your pet's symptoms, history, and may request to see your pet on camera. Based on this assessment, they'll provide guidance on care and next steps.",
      },
      {
        q: "When should I use telemedicine vs visiting a clinic?",
        a: "Telemedicine is ideal for initial assessment, follow-ups, behavioral questions, and determining urgency. Emergency situations like severe trauma, difficulty breathing, or suspected poisoning require immediate in-person veterinary care.",
      },
      {
        q: "Are your veterinarians licensed?",
        a: "Yes. All veterinarians on our platform are licensed professionals registered with the Veterinary Council of India. We verify credentials and conduct background checks before onboarding.",
      },
      {
        q: "How quickly can I connect with a veterinarian?",
        a: "Our average response time is around 15 minutes. Availability may vary based on time of day and current demand. We strive to connect you as quickly as possible.",
      },
      {
        q: "What information should I have ready?",
        a: "Have your pet's medical history, current medications or supplements, symptom duration, and any recent changes in behavior or diet. Photos or videos of symptoms can also be helpful.",
      },
      {
        q: "Will I receive documentation after the consultation?",
        a: "Yes. You'll receive a consultation summary with the veterinarian's assessment, recommendations, and any suggested follow-up actions. This can be shared with your regular veterinarian if needed.",
      },
    ],
    [],
  );

  const blogPosts = useMemo(
    () => [
      {
        title: "Vaccination Schedule for Pets in India",
        excerpt:
          "Complete, vet-approved vaccine timeline with essential boosters for dogs and cats.",
        link: "/blog/vaccination-schedule-for-pets-in-india",
        image: blogVaccination,
        category: "Pet Health",
        readTime: "5 min read",
      },
      {
        title: "Symptoms of Tick Fever in Dogs",
        excerpt:
          "Learn the early warning signs, prevention tips, and when to seek care.",
        link: "/blog/symptoms-of-tick-fever-in-dogs",
        image: blogTickFever,
        category: "Dog Care",
        readTime: "7 min read",
      },
      {
        title: "First Aid Tips Every Pet Parent Should Know",
        excerpt:
          "Practical first-aid steps for common pet emergencies before you reach a vet.",
        link: "/blog/first-aid-tips-every-pet-parent-should-know",
        image: blogFirstAid,
        category: "Emergency",
        readTime: "6 min read",
      },
    ],
    [],
  );

  const toggleFaq = (idx) => setOpenFaq((prev) => (prev === idx ? null : idx));

  return (
    <div className="min-h-screen bg-white text-slate-800">
      {/* Header */}
      <header className="sticky top-0 z-40 bg-white/90 backdrop-blur shadow-[0_2px_10px_rgba(0,0,0,0.05)]">
        <div className="mx-auto max-w-6xl px-5">
          <div className="flex items-center justify-between py-4 md:py-5">
            <button
              type="button"
              onClick={() => (typeof onStart === "function" ? onStart() : null)}
              className="flex items-center gap-3"
              aria-label="SnoutIQ Home"
            >
              {/* Use logo if you have it; fallback text matches the HTML */}
              <img
                src={logo}
                alt="SnoutIQ"
                className="h-6 w-auto object-contain drop-shadow-sm md:h-6 lg:h-6"
              />
            </button>

            <div className="flex items-center gap-3">
              <InstallCTA className="rounded-full px-4 py-1.5 text-sm" />
              <button
                type="button"
                onClick={() =>
                  typeof onVetAccess === "function" ? onVetAccess() : null
                }
                className="rounded-full border border-[#3998de]/30 px-4 py-1.5 text-sm font-semibold text-[#3998de] shadow-sm transition hover:bg-[#3998de]/10"
              >
                🩺 Vet Access
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#f4faff] via-white to-[#e8f2ff] py-16">
        <div className="pointer-events-none absolute -top-24 right-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-24 left-[-80px] h-72 w-72 rounded-full bg-[#3998de]/10 blur-3xl" />
        <div className="relative mx-auto max-w-6xl px-5">
          <div className="grid grid-cols-1 items-center gap-12 md:grid-cols-2">
            {/* Left */}
            <div>
              <div className="inline-block rounded-full bg-[#EAF4FF] px-4 py-2 text-sm font-semibold text-[#1D4E89] shadow-sm">
                ⭐ Trusted by 1000+ Pet Parents
              </div>

              <h1 className="mt-5 text-4xl font-extrabold leading-tight text-slate-900 md:text-5xl">
                Connect with a{" "}
                <span className="text-[#3998de]">verified veterinarian</span> in
                15 minutes
              </h1>

              <p className="mt-5 text-lg text-slate-500">
                Professional video consultations for your pet&apos;s health
                concerns. Get expert guidance from licensed veterinarians across
                India.
              </p>

              {/* Vets display */}
              {/* <div className="mt-6 flex items-center gap-4 rounded-2xl bg-white/70 px-4 py-3 shadow-sm ring-1 ring-white/60">
                <div className="flex items-center">
                  {["Dr", "Dr", "Dr", "Dr"].map((t, i) => (
                    <div
                      key={i}
                      className={`-ml-2 flex h-12 w-12 items-center justify-center rounded-full border-[3px] border-white text-sm font-bold text-white shadow-sm ${
                        i === 0 ? "ml-0" : ""
                      }`}
                      style={{
                        background: "linear-gradient(135deg, #2563eb, #7c3aed)",
                      }}
                    >
                      {t}
                    </div>
                  ))}
                  <div className="-ml-2 flex h-12 w-12 items-center justify-center rounded-full border-[3px] border-white bg-[#EAF4FF] text-sm font-bold text-[#1D4E89] shadow-sm">
                    +6
                  </div>
                </div>

                <div className="text-sm">
                  <div className="font-semibold text-slate-900">
                    10 Verified Veterinarians
                  </div>
                  <div className="text-slate-500">Ready to help your pet</div>
                </div>
              </div> */}

              {/* CTA */}
              <button
                type="button"
                onClick={() =>
                  typeof onStart === "function" ? onStart() : null
                }
                className="
    mt-7 
    inline-flex items-center justify-center gap-2
    rounded-xl 
    bg-[#3998de] 
    px-10 py-4 
    text-lg font-semibold text-white 
    shadow-lg shadow-[#3998de]/30 
    transition 
    hover:bg-[#2F7FC0]
    hover:gap-3
  "
              >
                Consult a Veterinarian
                <ArrowRight className="w-5 h-5 transition-transform duration-200 group-hover:translate-x-1" />
              </button>

              <IosInstallHint className="mt-4 max-w-md" />

              {/* Stats */}
              <div className="mt-8 grid gap-4 sm:grid-cols-3">
                {[
                  { n: "15min", l: "Average response time", icon: Clock },
                  { n: "100%", l: "Licensed and experienced vets", icon: ShieldCheck },
                  { n: "24/7", l: "Available", icon: Headphones },
                ].map((s, i) => (
                  <div
                    key={i}
                    className="rounded-xl border border-white/70 bg-white/90 px-5 py-5 shadow-sm hover:shadow-md transition"
                  >
                    {/* Icon */}
                    <div className="mb-3">
                      <div className="w-10 h-10 flex items-center justify-center rounded-full bg-[#3998de]/10">
                        <s.icon className="w-5 h-5 text-[#3998de]" />
                      </div>
                    </div>

                    {/* Number */}
                    <div className="text-3xl font-extrabold text-[#3998de]">
                      {s.n}
                    </div>

                    {/* Label */}
                    <div className="text-sm text-slate-500 mt-1">{s.l}</div>
                  </div>
                ))}
              </div>
            </div>

            {/* Right (Doctor Image) */}
            <div className="w-full">
              <div className="relative mx-auto w-full max-w-md">
                <div className="absolute -top-6 right-10 h-20 w-20 rounded-full bg-[#3998de]/15 blur-2xl" />
                <div className="absolute -bottom-8 left-6 h-24 w-24 rounded-full bg-[#3998de]/10 blur-2xl" />
                <div className="relative overflow-hidden rounded-3xl border border-white/70 bg-white/80 p-4 shadow-[0_25px_60px_rgba(15,118,110,0.08)] md:p-6">
                  <div className="absolute right-4 top-4 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold text-emerald-700">
                    Online
                  </div>
                  <img
                    src={doctorHero}
                    alt="Veterinarian"
                    className="h-auto w-full rounded-2xl object-cover"
                  />
                  <div className="mt-4 text-center text-sm font-semibold text-[#3998de]">
                    Video Consultation
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="bg-white py-20">
        <div className="mx-auto max-w-6xl px-5">
          <h2 className="text-center text-4xl font-bold text-slate-900">
            Why Choose Telemedicine for Your Pet?
          </h2>

          <div className="mt-12 grid grid-cols-1 gap-10 md:grid-cols-3">
            {[
              {
                icon: "⏱️",
                title: "Quick Response",
                desc: "Average connection time of 15 minutes. No more waiting rooms or long drives to the clinic.",
              },
              {
                icon: "🏥",
                title: "Expert Guidance",
                desc: "Licensed and experienced veterinarians provide professional assessment and advice for your pet's health concerns.",
              },
              {
                icon: "📱",
                title: "Convenient Access",
                desc: "Consult from home via video call. Perfect for initial assessments and follow-up guidance.",
              },
              {
                icon: "💰",
                title: "Cost Effective",
                desc: "Save on transportation and time. Get professional veterinary advice without clinic visit costs.",
              },
              {
                icon: "🛡️",
                title: "Verified Professionals",
                desc: "All veterinarians are licensed, certified, and background-verified for your peace of mind.",
              },
              {
                icon: "📋",
                title: "Digital Records",
                desc: "Consultation records saved securely. Easy to share with your regular vet or for future reference.",
              },
            ].map((f, i) => (
              <div
                key={i}
                className="rounded-2xl border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
              >
                <div className="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-200 text-4xl">
                  {f.icon}
                </div>
                <h3 className="text-xl font-semibold text-slate-900">
                  {f.title}
                </h3>
                <p className="mt-3 text-[15px] text-slate-500">{f.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How it works */}
      <section className="bg-slate-50 py-20">
        <div className="mx-auto max-w-6xl px-5">
          <h2 className="text-center text-4xl font-bold text-slate-900">
            How It Works
          </h2>

          <div className="relative mt-20">
            <div className="absolute left-1/2 top-0 bottom-0 hidden w-px -translate-x-1/2 bg-[#3998de]/20 md:hidden" />
            <div className="absolute left-1/2 top-6 hidden h-px w-[70%] -translate-x-1/2 bg-[#3998de]/20 md:block" />

            <div className="relative grid gap-12 md:grid-cols-3">
              {[
                {
                  n: "1",
                  title: "Describe Your Concern",
                  desc: "Tell us what's happening with your pet. Include symptoms, duration, and any relevant history.",
                },
                {
                  n: "2",
                  title: "Connect via Video",
                  desc: "Get matched with an available veterinarian. Join a secure video consultation within minutes.",
                },
                {
                  n: "3",
                  title: "Receive Guidance",
                  desc: "Get professional advice on next steps, home care recommendations, and when to visit a clinic.",
                },
              ].map((s, i) => (
                <div key={i} className="relative flex flex-col items-center">
                  <div className="relative z-10 flex h-12 w-12 items-center justify-center rounded-full bg-[#3998de] text-lg font-bold text-white shadow-lg">
                    {s.n}
                  </div>

                  <div className="mt-8 w-full max-w-sm rounded-2xl border border-slate-100 bg-white p-8 text-center shadow-md md:max-w-none">
                    <h3 className="text-xl font-semibold text-slate-900">
                      {s.title}
                    </h3>
                    <p className="mt-3 text-slate-500">{s.desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>



      {/* Blog */}
      <section className="bg-white py-20">
        <div className="mx-auto max-w-6xl px-5">
          <div className="text-center">
            <h2 className="text-4xl font-bold text-slate-900">
              Pet Care Resources
            </h2>
            <p className="mt-3 text-base text-slate-500">
              Expert guidance and practical tips for pet parents.
            </p>
          </div>

          <div className="mt-12 grid gap-6 md:grid-cols-3">
            {blogPosts.map((post) => (
              <a
                key={post.link}
                href={post.link}
                className="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
              >
                <div className="h-48 w-full overflow-hidden bg-slate-50">
                  <img
                    src={post.image}
                    alt={post.title}
                    className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                  />
                </div>
                <div className="flex flex-1 flex-col p-6">
                  <div className="flex items-center gap-3 text-xs text-slate-400">
                    <span className="rounded-full bg-[#3998de]/10 px-3 py-1 text-[#3998de]">
                      {post.category}
                    </span>
                    <span>{post.readTime}</span>
                  </div>
                  <h3 className="mt-4 text-lg font-semibold text-slate-900">
                    {post.title}
                  </h3>
                  <p className="mt-3 text-sm text-slate-500">{post.excerpt}</p>
                  <span className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#3998de]">
                    Read Article <ArrowRight className="h-4 w-4" />
                  </span>
                </div>
              </a>
            ))}
          </div>
        </div>
      </section>

      {/* FAQ */}
      <section className="bg-white py-20">
        <div className="mx-auto max-w-6xl px-5">
          <h2 className="text-center text-4xl font-bold text-slate-900">
            Common Questions
          </h2>

          <div className="mx-auto mt-12 max-w-3xl">
            {faqs.map((item, idx) => {
              const isOpen = openFaq === idx;
              return (
                <div
                  key={idx}
                  className="mb-5 overflow-hidden rounded-lg border border-slate-200"
                >
                  <button
                    type="button"
                    onClick={() => toggleFaq(idx)}
                    className="flex w-full items-center justify-between bg-slate-50 px-5 py-5 text-left font-semibold text-slate-900 hover:bg-slate-100"
                  >
                    <span className="pr-4">{item.q}</span>
                    <span className="text-xl font-bold text-[#3998de]">
                      {isOpen ? "−" : "+"}
                    </span>
                  </button>
                  {isOpen && (
                    <div className="px-5 py-5 text-slate-500">{item.a}</div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Promise */}
      <section className="bg-gradient-to-br from-blue-100 to-blue-200 py-16">
        <div className="mx-auto max-w-6xl px-5">
          <div className="mx-auto max-w-4xl text-center">
            <div className="text-5xl">💙</div>
            <h2 className="mt-4 text-3xl font-bold text-slate-900">
              Our Commitment to Pet Parents
            </h2>
            <p className="mt-4 text-base leading-8 text-slate-600">
              &quot;At SnoutIQ, we understand the anxiety when your pet
              isn&apos;t feeling well. That&apos;s why we&apos;ve built a
              platform where expert veterinary care is just minutes away. Every
              consultation is backed by professional expertise and genuine care
              for your furry family members.&quot;
            </p>
          </div>
        </div>
      </section>

      {/* Disclaimer */}
      <section className="border-t border-slate-200 bg-slate-50 py-10">
        <div className="mx-auto max-w-6xl px-5">
          <div className="mx-auto max-w-4xl text-center text-sm text-slate-500">
            <strong className="mb-2 block text-slate-900">
              Important Information
            </strong>
            <p>
              SnoutIQ provides professional veterinary teletriage and
              consultation services only. We do not dispense, sell, or
              facilitate the purchase of any medications. We do not provide
              online prescriptions. Our veterinarians offer guidance and
              recommendations; any medication needs must be addressed through an
              in-person clinic visit with a licensed veterinarian. In emergency
              situations, please visit your nearest veterinary clinic
              immediately.
            </p>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 py-12 text-white">
        <div className="mx-auto max-w-6xl px-5">
          <div className="mb-10 rounded-xl bg-white/5 p-6">
            <h4 className="text-lg font-semibold">Important Medical & Legal Disclaimer</h4>
            <p className="mt-3 text-sm text-slate-200">
              <strong>NO ONLINE PRESCRIPTIONS:</strong> SnoutIQ does not prescribe,
              dispense, or sell any medications. We do not provide online
              prescriptions under any circumstances. All medication needs must be
              addressed through in-person veterinary clinics with proper physical
              examination.
            </p>
            <p className="mt-3 text-sm text-slate-200">
              <strong>CONSULTATION ONLY:</strong> SnoutIQ provides professional
              veterinary teletriage and consultation services only. Our service is
              designed to help pet parents understand their pet&apos;s condition and
              determine appropriate next steps, including when to seek in-person
              veterinary care.
            </p>
            <p className="mt-3 text-sm text-slate-200">
              <strong>NOT FOR EMERGENCIES:</strong> This service is not suitable
              for veterinary emergencies. For emergencies, trauma, severe symptoms,
              or life-threatening conditions, please visit your nearest veterinary
              emergency clinic immediately.
            </p>
            <p className="mt-3 text-sm text-slate-200">
              <strong>LICENSED PROFESSIONALS:</strong> All veterinarians on SnoutIQ
              are licensed professionals registered with the Veterinary Council of
              India. However, video consultations cannot replace comprehensive
              physical examinations.
            </p>
          </div>

          <div className="grid gap-8 md:grid-cols-3">
            <div>
              <h4 className="text-lg font-semibold">SnoutIQ</h4>
              <p className="mt-3 text-sm text-slate-300">
                Professional veterinary teleconsultation and triage services across
                India.
              </p>
              <p className="mt-4 text-sm text-slate-300">
                <strong>Service Type:</strong> Consultation &amp; Triage Only
              </p>
              <p className="text-sm text-slate-300">
                <strong>No Prescriptions:</strong> Not a pharmacy service
              </p>
            </div>

            <div>
              <h4 className="text-lg font-semibold">Contact</h4>
              <p className="mt-3 text-sm text-slate-300">Email: hello@snoutiq.com</p>
              <p className="text-sm text-slate-300">Service Area: India</p>
              <p className="mt-4 text-sm font-semibold text-rose-200">
                For emergencies, visit your nearest veterinary clinic immediately.
              </p>
            </div>

            <div>
              <h4 className="text-lg font-semibold">Legal &amp; Compliance</h4>
              <div className="mt-3 space-y-2 text-sm text-slate-300">
                <a className="block hover:text-white" href="/terms-of-service">
                  Terms of Service
                </a>
                <a className="block hover:text-white" href="/privacy-policy">
                  Privacy Policy
                </a>
                <a className="block hover:text-white" href="/medical-data-consent">
                  Medical Disclaimer
                </a>
                <a className="block hover:text-white" href="/cookie-policy">
                  Cookie Policy
                </a>
                <a className="block hover:text-white" href="#">
                  Veterinary Council Registration
                </a>
                <a className="block hover:text-white" href="#">
                  Google Policy Compliance
                </a>
              </div>
            </div>
          </div>

          <div className="mt-10 border-t border-white/10 pt-6 text-center text-xs text-slate-400">
            <p>© 2025 SnoutIQ. Professional veterinary teleconsultation services.</p>
            <p className="mt-2">
              All veterinarians are licensed professionals registered with the
              Veterinary Council of India.
            </p>
            <p className="mt-2 font-semibold">
              We do not prescribe medications or provide online prescriptions.
              Consultation and triage services only.
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default LandingScreen;



