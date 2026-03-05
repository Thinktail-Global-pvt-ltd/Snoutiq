import React from "react";
import { Link } from "react-router-dom";
import { Button } from "./NewButton";
import {
  BadgeCheck,
  Clock,
  HeartPulse,
  MapPin,
  ShieldCheck,
  Star,
  Stethoscope,
  Video,
} from "lucide-react";

const belowFoldSectionStyle = {
  contentVisibility: "auto",
  containIntrinsicSize: "1px 900px",
};

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
    text: "Used SnoutIQ for my puppy who was not eating. The vet was very experienced and the video quality was great. Rs 399 is so worth it compared to clinic fees.",
    rating: 5,
  },
];

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

const faqItems = [
  {
    q: "How do I consult a veterinary doctor online in India?",
    a: "Simply click 'Start Instant Vet Consultation' on SnoutIQ, select your pet type, pay the consultation fee (Rs 399 day / Rs 549 night), and join a secure HD video call with a verified vet within minutes. No app download required.",
  },
  {
    q: "Is online vet consultation available 24/7 in India?",
    a: "Yes. SnoutIQ offers 24/7 online vet consultation in India including nights, weekends, and public holidays. Emergency online vet India support is always available, even at 3 AM.",
  },
  {
    q: "What is the cost of online vet consultation in India?",
    a: "Online vet consultation on SnoutIQ costs Rs 399 during the day (8 AM to 8 PM) and Rs 549 at night (8 PM to 8 AM). No hidden charges. Covers dogs and cats across all of India.",
  },
  {
    q: "Can an online vet prescribe medicine in India?",
    a: "Online vets on SnoutIQ can provide clinical advice, triage guidance, and written recommendations. For certain prescription medicines, a follow-up clinic visit may be required as per Indian veterinary regulations.",
  },
  {
    q: "Is SnoutIQ's online vet consultation safe and private?",
    a: "Yes. All video consultations on SnoutIQ are conducted over encrypted, secure connections. Your pet's health data is kept confidential and never shared without consent.",
  },
];

const supportCards = [
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
];

export default function NewVideoConsultationLPBelowFold({ consultPath }) {
  return (
    <>
      <section className="py-16" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-6">
            Why Choose Veterinary Doctor Online Consultation in India?
          </h2>
          <p className="text-slate-700 mb-8">
            With our <strong>online veterinary consultation</strong> platform, pet parents can connect with vet
            online India without clinic visits. Whether you need an emergency online vet India or general pet advice,
            our verified online vet doctors provide expert guidance.
          </p>

          <div className="grid md:grid-cols-3 gap-8 mb-12">
            <div className="bg-slate-50 p-6 rounded-2xl border">
              <Video className="w-10 h-10 mb-4 text-brand" />
              <h3 className="text-xl font-bold mb-2">Video Consultation with Vet India</h3>
              <p className="text-slate-600">Secure HD video consultation for dogs and cats across India.</p>
            </div>

            <div className="bg-slate-50 p-6 rounded-2xl border">
              <Clock className="w-10 h-10 mb-4 text-brand" />
              <h3 className="text-xl font-bold mb-2">Instant Vet Consultation India</h3>
              <p className="text-slate-600">Connect to a pet doctor online India within minutes.</p>
            </div>

            <div className="bg-slate-50 p-6 rounded-2xl border">
              <ShieldCheck className="w-10 h-10 mb-4 text-brand" />
              <h3 className="text-xl font-bold mb-2">Verified Online Vet Doctor</h3>
              <p className="text-slate-600">Experienced dog vet and cat specialists available 24/7.</p>
            </div>
          </div>
        </div>
      </section>

      <section className="py-16 bg-slate-50 border-y border-slate-200" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-4">How to Consult a Vet Online India - 3 Simple Steps</h2>
          <p className="text-slate-700 mb-10">
            Getting an <strong>online vet consultation India</strong> on SnoutIQ takes less than 15 minutes. Works on
            any phone or laptop.
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

      <section className="py-16" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-6">Common Symptoms We Treat Online</h2>
          <p className="text-slate-700 mb-6">
            Not sure if you need a clinic visit? A video consultation vet can help diagnose and triage many common
            issues. Explore our symptom guides to learn more:
          </p>

          <ul className="space-y-3 mb-12">
            <li>
              <Link to="/symptoms/dog-vomiting-treatment-india" className="text-brand hover:underline">
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
              <Link to="/symptoms/cat-watery-eyes-treatment" className="text-brand hover:underline">
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

      <section className="py-16 bg-slate-50 border-y border-slate-200" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-4">What Can an Online Vet Doctor in India Help With?</h2>
          <p className="text-slate-700 mb-8">
            Our <strong>online vet consultation India</strong> covers a wide range of pet health needs. From emergency
            triage to routine guidance, our online vet doctors are equipped to assist with:
          </p>

          <div className="grid md:grid-cols-2 gap-6 mb-8">
            {supportCards.map(({ icon, title, desc }) => (
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

      <section className="py-16" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-4">What Pet Parents Say About Online Vet Consultation India</h2>
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
                    {location} - {pet}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-6" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-4">Online Vet Consultation Available Across India</h2>
          <p className="text-slate-700 mb-8">
            SnoutIQ&apos;s <strong>veterinary doctor online</strong> service is available to pet parents in every city
            across India, no matter where you are, a verified vet is just a click away.
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

      <section className="py-16 bg-slate-50 border-y border-slate-200" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-6">About Veterinary Doctor Online India - SnoutIQ</h2>
          <p className="text-slate-700 mb-4">
            SnoutIQ is India&apos;s trusted <strong>online vet consultation</strong> platform, connecting dog and cat
            owners with licensed veterinarians through secure video calls. Every <strong>online vet doctor</strong> on
            SnoutIQ holds a BVSc &amp; AH degree and has verified clinical experience, ensuring your pet receives
            expert care at any hour.
          </p>
          <p className="text-slate-700 mb-4">
            Unlike generic teleconsultation platforms, SnoutIQ is built exclusively for pets. Our{" "}
            <strong>video consultation vet</strong> sessions are tailored to small animal medicine, covering dogs,
            cats, and more. The platform supports real-time visual examination, enabling vets to assess symptoms
            accurately over video.
          </p>
          <p className="text-slate-700 mb-4">
            Whether your pet shows signs of fever, vomiting, lethargy, or skin problems, our{" "}
            <strong>online dog doctor India</strong> and <strong>online cat doctor India</strong> specialists are
            available around the clock. With consultation fees starting at just Rs 399, quality pet healthcare has
            never been more accessible across India.
          </p>
          <p className="text-slate-700">
            SnoutIQ also provides supplementary resources such as an AI symptom checker, vaccination reminders, and vet
            insights, all designed to help Indian pet parents make informed decisions about their pet&apos;s health
            without unnecessary clinic visits.
          </p>
        </div>
      </section>

      <section className="py-16" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-3xl font-bold mb-10">Frequently Asked Questions - Vet Online India</h2>

          <div className="space-y-6">
            {faqItems.map(({ q, a }) => (
              <div key={q} className="bg-slate-50 rounded-2xl border p-6">
                <h3 className="font-bold text-lg mb-2">{q}</h3>
                <p className="text-slate-700">{a}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16 bg-slate-50 border-t border-slate-200" style={belowFoldSectionStyle}>
        <div className="mx-auto max-w-4xl px-4">
          <div className="bg-brand/10 p-8 rounded-2xl text-center border border-brand/30">
            <h3 className="text-2xl font-bold mb-4">Talk to Veterinary Doctor Online India Now</h3>
            <p className="text-slate-700 mb-6">
              Your pet&apos;s health can&apos;t wait. Get expert online vet advice in minutes, day or night.
            </p>
            <Link to={consultPath}>
              <Button size="lg">Consult Online Vet Doctor</Button>
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
