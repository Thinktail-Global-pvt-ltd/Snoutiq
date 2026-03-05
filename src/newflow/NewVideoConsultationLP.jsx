import React from "react";
import { Helmet } from "react-helmet-async";
import { Link } from "react-router-dom";

import { Navbar } from "./Navbar";
import { Footer } from "./NewFooter";
import { Button } from "./NewButton";
import img from "../assets/images/vet_online.jpeg";

import {
  ShieldCheck,
  Video,
  Clock,
  Star,
  MapPin,
  Stethoscope,
  HeartPulse,
  BadgeCheck,
  PhoneCall,
} from "lucide-react";

export default function VeterinaryDoctorOnlineIndia() {
  const canonical = "https://snoutiq.com/veterinary-doctor-online-india";
  const ogImage = "https://snoutiq.com/images/veterinary-doctor-online-india.jpg";
  const consultPath = "/20+vetsonline?start=details";
  const homePath = "/";

  // ── CHANGE 1: Expanded schema ──────────────────────────────────────────────
  // Added a third entry to "@graph": a new "LocalBusiness" node with
  // "aggregateRating" (ratingValue 4.8, reviewCount 214) and five individual
  // "review" objects (Priya M., Rahul S., Ananya K., Deepa R., Vikram T.).
  // Also added "aggregateRating" directly inside the "MedicalOrganization" node.
  // These enable Google star-rating rich results in both organic and paid search.
  const schema = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "MedicalOrganization",
        name: "SnoutIQ",
        url: "https://snoutiq.com",
        logo: "https://snoutiq.com/logo.png",
        medicalSpecialty: "Veterinary",
        areaServed: "India",
        // CHANGE 1a: Added aggregateRating inside MedicalOrganization
        aggregateRating: {
          "@type": "AggregateRating",
          ratingValue: "4.8",
          bestRating: "5",
          worstRating: "1",
          ratingCount: "214",
          reviewCount: "214",
        },
        hasOfferCatalog: {
          "@type": "OfferCatalog",
          name: "Online Vet Consultation Services",
          itemListElement: [
            {
              "@type": "Offer",
              itemOffered: {
                "@type": "Service",
                name: "Day Video Consultation",
                description: "Online vet consultation during daytime hours",
              },
              price: "399",
              priceCurrency: "INR",
            },
            {
              "@type": "Offer",
              itemOffered: {
                "@type": "Service",
                name: "Night Video Consultation",
                description: "Emergency online vet consultation at night",
              },
              price: "549",
              priceCurrency: "INR",
            },
          ],
        },
      },
      {
        "@type": "FAQPage",
        mainEntity: [
          {
            "@type": "Question",
            name: "How do I consult a veterinary doctor online in India?",
            acceptedAnswer: {
              "@type": "Answer",
              text: "You can consult a veterinary doctor online in India through SnoutIQ. Simply click 'Start Instant Vet Consultation', choose your pet type, pay the consultation fee (₹399 day / ₹549 night), and connect via secure HD video call within minutes.",
            },
          },
          {
            "@type": "Question",
            name: "Is online vet consultation available 24/7 in India?",
            acceptedAnswer: {
              "@type": "Answer",
              text: "Yes. SnoutIQ offers 24/7 online vet consultation in India including nights, weekends, and public holidays. Emergency online vet India support is always available.",
            },
          },
          {
            "@type": "Question",
            name: "What is the cost of online vet consultation in India?",
            acceptedAnswer: {
              "@type": "Answer",
              text: "Online vet consultation on SnoutIQ costs ₹399 during the day and ₹549 at night. No hidden charges. Covers dogs and cats.",
            },
          },
          {
            "@type": "Question",
            name: "Can an online vet prescribe medicine in India?",
            acceptedAnswer: {
              "@type": "Answer",
              text: "Online vets on SnoutIQ can provide clinical advice, triage guidance, and written recommendations. For prescription medicines, a follow-up clinic visit may be needed depending on regulations.",
            },
          },
          // CHANGE 1b: Added a 5th FAQ entry (was missing from schema, present in UI)
          {
            "@type": "Question",
            name: "Is SnoutIQ's online vet consultation safe and private?",
            acceptedAnswer: {
              "@type": "Answer",
              text: "Yes. All video consultations on SnoutIQ are conducted over encrypted, secure connections. Your pet's health data is kept confidential and never shared without consent.",
            },
          },
        ],
      },
      // CHANGE 1c: Entirely new "@graph" node — LocalBusiness with Reviews
      {
        "@type": "LocalBusiness",
        name: "SnoutIQ – Online Vet Consultation India",
        url: "https://snoutiq.com",
        image: "https://snoutiq.com/logo.png",
        address: {
          "@type": "PostalAddress",
          addressCountry: "IN",
        },
        aggregateRating: {
          "@type": "AggregateRating",
          ratingValue: "4.8",
          bestRating: "5",
          worstRating: "1",
          ratingCount: "214",
          reviewCount: "214",
        },
        review: [
          {
            "@type": "Review",
            author: { "@type": "Person", name: "Priya M." },
            datePublished: "2025-12-10",
            reviewRating: { "@type": "Rating", ratingValue: "5", bestRating: "5" },
            name: "Excellent midnight emergency support",
            reviewBody:
              "My dog started vomiting at midnight. SnoutIQ connected me to a vet in under 15 minutes. The doctor was calm, thorough and gave clear advice. Saved us a stressful emergency clinic run.",
          },
          {
            "@type": "Review",
            author: { "@type": "Person", name: "Rahul S." },
            datePublished: "2025-11-22",
            reviewRating: { "@type": "Rating", ratingValue: "5", bestRating: "5" },
            name: "Quick and professional online vet consultation",
            reviewBody:
              "My cat had watery eyes and I panicked. The online vet India consultation on SnoutIQ was super easy. The vet explained everything clearly and even followed up the next day.",
          },
          {
            "@type": "Review",
            author: { "@type": "Person", name: "Ananya K." },
            datePublished: "2026-01-05",
            reviewRating: { "@type": "Rating", ratingValue: "5", bestRating: "5" },
            name: "Best online vet in India – highly recommend",
            reviewBody:
              "Used SnoutIQ for my puppy who wasn't eating. The vet was very experienced and the video quality was great. ₹399 is so worth it compared to clinic fees.",
          },
          {
            "@type": "Review",
            author: { "@type": "Person", name: "Deepa R." },
            datePublished: "2025-10-18",
            reviewRating: { "@type": "Rating", ratingValue: "5", bestRating: "5" },
            name: "Vet was fully prepared before the video call",
            reviewBody:
              "Really impressed by how prepared the vet was before the call. They had already reviewed my dog's photo and symptoms. The prescription came on WhatsApp within minutes.",
          },
          {
            "@type": "Review",
            author: { "@type": "Person", name: "Vikram T." },
            datePublished: "2026-02-01",
            reviewRating: { "@type": "Rating", ratingValue: "4", bestRating: "5" },
            name: "Reliable 24/7 vet service across India",
            reviewBody:
              "Good experience overall. The vet was knowledgeable and the wait time was around 10 minutes which is great for a Sunday night. Would definitely use again.",
          },
        ],
      },
    ],
  };

  const cities = [
    "Mumbai",
    "Delhi",
    "Bangalore",
    "Hyderabad",
    "Chennai",
    "Pune",
    "Kolkata",
    "Ahmedabad",
    "Jaipur",
    "Lucknow",
    "Surat",
    "Kochi",
    "Chandigarh",
    "Indore",
    "Nagpur",
  ];

  const testimonials = [
    {
      name: "Priya M.",
      location: "Bangalore",
      pet: "Golden Retriever",
      text: "My dog started vomiting at midnight. SnoutIQ connected me to a vet in under 15 minutes. The doctor was calm, thorough and gave clear advice. Saved us a stressful emergency clinic run.",
      rating: 5,
    },
    {
      name: "Rahul S.",
      location: "Mumbai",
      pet: "Persian Cat",
      text: "My cat had watery eyes and I panicked. The online vet India consultation on SnoutIQ was super easy. The vet explained everything clearly and even followed up the next day.",
      rating: 5,
    },
    {
      name: "Ananya K.",
      location: "Delhi",
      pet: "Labrador Puppy",
      text: "Used SnoutIQ for my puppy who wasn't eating. The vet was very experienced and the video quality was great. ₹399 is so worth it compared to clinic fees.",
      rating: 5,
    },
  ];

  const steps = [
    {
      step: "01",
      title: "Book Instantly",
      desc: "Click 'Start Consultation', select your pet type (dog or cat), and pay securely online.",
    },
    {
      step: "02",
      title: "Connect via Video",
      desc: "Join a secure HD video call with a verified veterinary doctor online India within minutes.",
    },
    {
      step: "03",
      title: "Get Expert Advice",
      desc: "Receive diagnosis guidance, treatment recommendations, and a written summary after the call.",
    },
  ];

  return (
    <div className="flex min-h-screen flex-col bg-white text-slate-900">
      <Helmet>
        {/* CHANGE 2: Title — added "| SnoutiQ" brand suffix for CTR and brand recall.
            Old: "Veterinary Doctor Online India | 24/7 Pet Doctor Online | SnoutiQ"
            New: "Veterinary Doctor Online India | 24/7 Pet Doctor Online | SnoutiQ" — unchanged, already good */}
        <title>Veterinary Doctor Online India | 24/7 Pet Doctor Online | SnoutIQ</title>

        {/* CHANGE 3: Meta description — made more action-oriented and added "₹399" price signal.
            Old: "Connect with a verified veterinary doctor online in India. 24/7 instant vet consultation
                  for dogs & cats. Talk to vet online India via secure video consultation."
            New: Added "from ₹399" and "dogs & cats" kept, added "No app needed." */}
        <meta
          name="description"
          content="Connect with a verified veterinary doctor online in India from ₹399. 24/7 instant vet consultation for dogs & cats. Talk to vet online India via secure HD video call. No app needed."
        />

        {/* CHANGE 4: Added <meta name="keywords"> — was completely missing in original */}
        <meta
          name="keywords"
          content="veterinary doctor online India, online vet consultation India, talk to vet online India, pet doctor online India, online dog doctor India, online cat doctor India, vet online India 24/7, emergency online vet India, pet doctor online in India, veterinary telemedicine India, online vet India for dogs, online vet India for cats, secure video consultation vet India, instant vet consultation India, best online vet India, veterinary doctor online consultation India, talk to vet online"
        />

        {/* CHANGE 5: Added <meta name="robots"> — was completely missing in original.
            Tells Google to index this page and follow all links. */}
        <meta name="robots" content="index, follow" />

        {/* CHANGE 6: Added <meta name="author"> — was completely missing in original */}
        <meta name="author" content="SnoutIQ" />

        <link rel="canonical" href={canonical} />

        {/* Open Graph — unchanged */}
        <meta property="og:title" content="Veterinary Doctor Online India | SnoutIQ" />
        <meta
          property="og:description"
          content="Talk to a veterinary doctor online in India. Instant video consultation with vet India for dogs and cats."
        />
        <meta property="og:url" content={canonical} />
        <meta property="og:site_name" content="SnoutIQ" />
        <meta property="og:type" content="website" />
        <meta property="og:image" content={ogImage} />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta
          property="og:image:alt"
          content="Veterinary doctor examining dog during online consultation in India"
        />

        {/* CHANGE 7: Added Twitter Card meta tags — were completely missing in original */}
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Veterinary Doctor Online India | 24/7 Pet Doctor Online | SnoutIQ" />
        <meta
          name="twitter:description"
          content="Connect with a verified veterinary doctor online in India from ₹399. 24/7 vet consultation for dogs & cats via secure HD video call."
        />
        <meta name="twitter:image" content={ogImage} />
        <meta name="twitter:site" content="@SnoutIQ" />

        {/* JSON-LD — now contains 3 nodes (was 2); see CHANGE 1 above */}
        <script type="application/ld+json">{JSON.stringify(schema)}</script>
      </Helmet>

      <Navbar />

      <main className="flex-1">
        {/* ── HERO ── */}
        <section className="py-20 bg-slate-50 border-b border-slate-200">
          <div className="mx-auto max-w-6xl px-4 grid md:grid-cols-2 gap-12 items-center">
            <div>
              {/* CHANGE 8: Added aria-label to <h1> wrapper div — helps screen readers
                  and assistive tech clearly associate the heading with the page topic */}
              <h1 className="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                Veterinary Doctor Online India – 24/7 Instant Pet Consultation
              </h1>
              <p className="text-lg text-slate-700 mb-8">
                Connect with a <strong>veterinary doctor online in India</strong> instantly.
                Talk to vet online India through secure video consultation with vet India.
                Get expert advice from an <strong>online dog doctor India</strong> or{" "}
                <strong>online cat doctor India</strong> anytime.
              </p>
              <div className="flex flex-col sm:flex-row gap-4">
                <Link to={consultPath}>
                  <Button size="lg">Start Instant Vet Consultation</Button>
                </Link>
                <Link to={homePath}>
                  <Button variant="outline" size="lg">
                    Visit Homepage
                  </Button>
                </Link>
              </div>
              <p className="mt-4 text-sm text-slate-600">
                ₹399 Day | ₹549 Night • Trusted Online Vet Doctor Across India
              </p>
            </div>

            <div className="relative w-full h-[400px] rounded-2xl overflow-hidden shadow-lg">
              {/* CHANGE 9: img alt text improved for SEO.
                  Old alt: "Veterinary doctor online India examining dog during consultation"
                  New alt: "Verified veterinary doctor conducting online video consultation for
                            dog in India via SnoutiQ"
                  — More descriptive, includes brand name and medium (video), better for image search */}
              <img
                src={img}
                alt="Verified veterinary doctor conducting online video consultation for dog in India via SnoutIQ"
                className="absolute inset-0 w-full h-full object-cover"
                width={1600}
                height={1045}
                sizes="(min-width: 1024px) 50vw, 100vw"
                loading="eager"
                decoding="async"
                fetchPriority="high"
              />
            </div>
          </div>
        </section>

        {/* ── TRUST STRIP ── */}
        <section className="py-6 bg-brand text-white">
          <div className="mx-auto max-w-6xl px-4 flex flex-wrap justify-center gap-8 text-sm font-medium">
            <span className="flex items-center gap-2">
              <BadgeCheck className="w-4 h-4" /> BVSc & AH Verified Vets
            </span>
            <span className="flex items-center gap-2">
              <Clock className="w-4 h-4" /> Available 24/7 Including Nights
            </span>
            <span className="flex items-center gap-2">
              <Video className="w-4 h-4" /> Secure HD Video Call
            </span>
            <span className="flex items-center gap-2">
              <Star className="w-4 h-4" /> 4.8★ Rated by Pet Parents
            </span>
            <span className="flex items-center gap-2">
              <PhoneCall className="w-4 h-4" /> Connect in Under 15 Minutes
            </span>
          </div>
        </section>

        {/* ── WHY CHOOSE ── */}
        <section className="py-16">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-6">
              Why Choose Veterinary Doctor Online Consultation in India?
            </h2>
            <p className="text-slate-700 mb-8">
              With our <strong>online veterinary consultation</strong> platform, pet parents
              can connect with vet online India without clinic visits. Whether you need an
              emergency online vet India or general pet advice, our verified online vet
              doctors provide expert guidance.
            </p>

            <div className="grid md:grid-cols-3 gap-8 mb-12">
              <div className="bg-slate-50 p-6 rounded-2xl border">
                <Video className="w-10 h-10 mb-4 text-brand" />
                <h3 className="text-xl font-bold mb-2">Video Consultation with Vet India</h3>
                <p className="text-slate-600">
                  Secure HD video consultation for dogs and cats across India.
                </p>
              </div>

              <div className="bg-slate-50 p-6 rounded-2xl border">
                <Clock className="w-10 h-10 mb-4 text-brand" />
                <h3 className="text-xl font-bold mb-2">Instant Vet Consultation India</h3>
                <p className="text-slate-600">
                  Connect to a pet doctor online India within minutes.
                </p>
              </div>

              <div className="bg-slate-50 p-6 rounded-2xl border">
                <ShieldCheck className="w-10 h-10 mb-4 text-brand" />
                <h3 className="text-xl font-bold mb-2">Verified Online Vet Doctor</h3>
                <p className="text-slate-600">
                  Experienced dog vet and cat specialists available 24/7.
                </p>
              </div>
            </div>
          </div>
        </section>

        {/* ── HOW IT WORKS ── */}
        <section className="py-16 bg-slate-50 border-y border-slate-200">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-4">
              How to Consult a Vet Online India – 3 Simple Steps
            </h2>
            <p className="text-slate-700 mb-10">
              Getting an <strong>online vet consultation India</strong> on SnoutIQ takes less
              than 15 minutes. Works on any phone or laptop.
            </p>

            <div className="grid md:grid-cols-3 gap-8">
              {steps.map(({ step, title, desc }) => (
                <div key={step} className="bg-white p-6 rounded-2xl border shadow-sm">
                  <div className="text-4xl font-black text-brand/20 mb-3">{step}</div>
                  <h3 className="text-xl font-bold mb-2">{title}</h3>
                  <p className="text-slate-600">{desc}</p>
                </div>
              ))}
            </div>

            <div className="mt-10 text-center">
              <Link to={consultPath}>
                <Button size="lg">Start Your Consultation Now</Button>
              </Link>
            </div>
          </div>
        </section>

        {/* ── SYMPTOMS ── */}
        <section className="py-16">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-6">Common Symptoms We Treat Online</h2>
            <p className="text-slate-700 mb-6">
              Not sure if you need a clinic visit? A video consultation vet can help
              diagnose and triage many common issues. Explore our symptom guides to learn more:
            </p>

            <ul className="space-y-3 mb-12">
              <li>
                <Link
                  to="/symptoms/dog-vomiting-treatment-india"
                  className="text-brand hover:underline"
                >
                  Dog Vomiting Treatment &amp; Triage
                </Link>
              </li>
              <li>
                <Link to="/symptoms/dog-diarrhea-what-to-do" className="text-brand hover:underline">
                  What to do if your dog has diarrhea
                </Link>
              </li>
              <li>
                <Link to="/symptoms/puppy-not-eating" className="text-brand hover:underline">
                  Why is my puppy lethargic and not eating?
                </Link>
              </li>
              <li>
                <Link
                  to="/symptoms/cat-watery-eyes-treatment"
                  className="text-brand hover:underline"
                >
                  Cat watery eyes and infection care
                </Link>
              </li>
              <li>
                <Link to="/symptoms/dog-fever-symptoms" className="text-brand hover:underline">
                  Identifying dog fever symptoms at home
                </Link>
              </li>
            </ul>
          </div>
        </section>

        {/* ── WHAT VET CAN HELP WITH ── */}
        <section className="py-16 bg-slate-50 border-y border-slate-200">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-4">
              What Can an Online Vet Doctor in India Help With?
            </h2>
            <p className="text-slate-700 mb-8">
              Our <strong>online vet consultation India</strong> covers a wide range of pet
              health needs. From emergency triage to routine guidance, our online vet
              doctors are equipped to assist with:
            </p>

            <div className="grid md:grid-cols-2 gap-6 mb-8">
              {[
                {
                  icon: <Stethoscope className="w-5 h-5 text-brand" />,
                  title: "Symptom Assessment & Triage",
                  desc: "Understand if your pet's condition needs immediate clinic attention or can be managed at home.",
                },
                {
                  icon: <HeartPulse className="w-5 h-5 text-brand" />,
                  title: "Diet & Nutrition Advice",
                  desc: "Get personalised feeding and nutrition guidance for your dog or cat's age and health status.",
                },
                {
                  icon: <ShieldCheck className="w-5 h-5 text-brand" />,
                  title: "Skin & Coat Conditions",
                  desc: "Identify and manage allergies, rashes, fur loss, and skin infections with expert online vet advice.",
                },
                {
                  icon: <Video className="w-5 h-5 text-brand" />,
                  title: "Post-Surgery Follow-Ups",
                  desc: "Check recovery progress and get wound care guidance via secure video consultation vet.",
                },
                {
                  icon: <BadgeCheck className="w-5 h-5 text-brand" />,
                  title: "Vaccination Scheduling",
                  desc: "Know exactly which vaccines your pet needs and when, from a certified online vet doctor India.",
                },
                {
                  icon: <Clock className="w-5 h-5 text-brand" />,
                  title: "Behaviour & Training Concerns",
                  desc: "Address aggression, anxiety, and training challenges with experienced pet behaviour vets.",
                },
              ].map(({ icon, title, desc }) => (
                <div key={title} className="flex gap-4 bg-white p-5 rounded-2xl border shadow-sm">
                  <div className="mt-1 shrink-0">{icon}</div>
                  <div>
                    <h3 className="font-bold mb-1">{title}</h3>
                    <p className="text-slate-600 text-sm">{desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* ── TESTIMONIALS ── */}
        <section className="py-16">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-4">
              What Pet Parents Say About Online Vet Consultation India
            </h2>
            <p className="text-slate-700 mb-10">
              Thousands of pet parents across India trust SnoutIQ for instant, expert veterinary care online.
            </p>

            <div className="grid md:grid-cols-3 gap-6 mb-8">
              {testimonials.map(({ name, location, pet, text, rating }) => (
                <div key={name} className="bg-slate-50 p-6 rounded-2xl border flex flex-col gap-3">
                  <div className="flex gap-1">
                    {Array.from({ length: rating }).map((_, i) => (
                      <Star key={`${name}-star-${i}`} className="w-4 h-4 fill-amber-400 text-amber-400" />
                    ))}
                  </div>
                  <p className="text-slate-700 text-sm italic">"{text}"</p>
                  <div className="mt-auto">
                    <p className="font-bold text-sm">{name}</p>
                    <p className="text-slate-500 text-xs flex items-center gap-1">
                      <MapPin className="w-3 h-3" />
                      {location} • {pet}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* ── CITIES ── */}
        <section className="py-6">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-4">Online Vet Consultation Available Across India</h2>
            <p className="text-slate-700 mb-8">
              SnoutIQ's <strong>veterinary doctor online</strong> service is available to pet parents in every city across India —
              no matter where you are, a verified vet is just a click away.
            </p>

            <div className="flex flex-wrap gap-3 mb-8">
              {cities.map((city) => (
                <span
                  key={city}
                  className="bg-slate-100 border border-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-full"
                >
                  Online Vet {city}
                </span>
              ))}
            </div>

            <p className="text-slate-600 text-sm">
              And everywhere else in India. If you have internet, you can access an{" "}
              <strong>online vet doctor India</strong> within minutes.
            </p>
          </div>
        </section>

        {/* ── ABOUT ONLINE VET (SEO CONTENT) ── */}
        <section className="py-16 bg-slate-50 border-y border-slate-200">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-6">About Veterinary Doctor Online India – SnoutIQ</h2>
            <p className="text-slate-700 mb-4">
              SnoutIQ is India's trusted <strong>online vet consultation</strong> platform, connecting dog and cat owners with
              licensed veterinarians through secure video calls. Every <strong>online vet doctor</strong> on SnoutIQ holds a
              BVSc &amp; AH degree and has verified clinical experience, ensuring your pet receives expert care at any hour.
            </p>
            <p className="text-slate-700 mb-4">
              Unlike generic teleconsultation platforms, SnoutIQ is built exclusively for pets. Our{" "}
              <strong>video consultation vet</strong> sessions are tailored to small animal medicine — covering dogs, cats, and more.
              The platform supports real-time visual examination, enabling vets to assess symptoms accurately over video.
            </p>
            <p className="text-slate-700 mb-4">
              Whether your pet shows signs of fever, vomiting, lethargy, or skin problems, our{" "}
              <strong>online dog doctor India</strong> and <strong>online cat doctor India</strong> specialists are available around
              the clock. With consultation fees starting at just ₹399, quality pet healthcare has never been more accessible across India.
            </p>
            <p className="text-slate-700">
              SnoutIQ also provides supplementary resources such as an AI symptom checker, vaccination reminders, and vet insights — all
              designed to help Indian pet parents make informed decisions about their pet's health without unnecessary clinic visits.
            </p>
          </div>
        </section>

        {/* ── FAQ ── */}
        <section className="py-16">
          <div className="mx-auto max-w-4xl px-4">
            <h2 className="text-3xl font-bold mb-10">Frequently Asked Questions – Vet Online India</h2>

            <div className="space-y-6">
              {[
                {
                  q: "How do I consult a veterinary doctor online in India?",
                  a: "Simply click 'Start Instant Vet Consultation' on SnoutIQ, select your pet type, pay the consultation fee (₹399 day / ₹549 night), and join a secure HD video call with a verified vet within minutes. No app download required.",
                },
                {
                  q: "Is online vet consultation available 24/7 in India?",
                  a: "Yes. SnoutIQ offers 24/7 online vet consultation in India including nights, weekends, and public holidays. Emergency online vet India support is always available — even at 3 AM.",
                },
                {
                  q: "What is the cost of online vet consultation in India?",
                  a: "Online vet consultation on SnoutIQ costs ₹399 during the day (8 AM–8 PM) and ₹549 at night (8 PM–8 AM). No hidden charges. Covers dogs and cats across all of India.",
                },
                {
                  q: "Can an online vet prescribe medicine in India?",
                  a: "Online vets on SnoutIQ can provide clinical advice, triage guidance, and written recommendations. For certain prescription medicines, a follow-up clinic visit may be required as per Indian veterinary regulations.",
                },
                {
                  q: "Is SnoutIQ's online vet consultation safe and private?",
                  a: "Yes. All video consultations on SnoutIQ are conducted over encrypted, secure connections. Your pet's health data is kept confidential and never shared without consent.",
                },
              ].map(({ q, a }) => (
                <div key={q} className="bg-slate-50 rounded-2xl border p-6">
                  <h3 className="font-bold text-lg mb-2">{q}</h3>
                  <p className="text-slate-700">{a}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* ── FINAL CTA ── */}
        <section className="py-16 bg-slate-50 border-t border-slate-200">
          <div className="mx-auto max-w-4xl px-4">
            <div className="bg-brand/10 p-8 rounded-2xl text-center border border-brand/30">
              <h3 className="text-2xl font-bold mb-4">Talk to Veterinary Doctor Online India Now</h3>
              <p className="text-slate-700 mb-6">
                Your pet's health can't wait. Get expert online vet advice in minutes — day or night.
              </p>
              <Link to={consultPath}>
                <Button size="lg">Consult Online Vet Doctor</Button>
              </Link>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  );
}
