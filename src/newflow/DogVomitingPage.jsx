import React from "react";
import { Navbar } from "./Navbar";
import { Footer } from "./NewFooter";
import { Button } from "./NewButton";
import { Link } from "react-router-dom";

export const metadata = {
  title: "Dog Vomiting Treatment India | Causes & When to Worry | SnoutiQ",
  description:
    "Is your dog throwing up yellow foam or food? Learn about dog vomiting treatment in India, common causes, and when to consult an online vet immediately.",
};

export default function DogVomitingPage() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: "Dog Vomiting Treatment in India: Causes and When to Worry",
    publisher: {
      "@type": "Organization",
      name: "SnoutiQ",
    },
  };

  return (
    <div className="flex min-h-screen flex-col bg-white text-slate-900">
      <Navbar />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />

      <main className="flex-1 py-16">
        <article className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 prose prose-invert prose-lg">
          <h1 className="font-display text-4xl md:text-5xl font-bold mb-6 text-slate-900">
            Dog Vomiting Treatment in India: Causes &amp; When to Worry
          </h1>

          <p className="text-xl text-slate-700 mb-8 leading-relaxed">
            Seeing your dog throw up can be alarming. Whether it&apos;s yellow
            foam, undigested food, or clear liquid, understanding the root cause
            is crucial for effective{" "}
            <strong>dog vomiting treatment in India</strong>.
          </p>

          <h2 className="text-2xl font-bold text-slate-900 mt-10 mb-4">
            Common Causes of Dog Vomiting
          </h2>
          <p className="text-slate-600 mb-4">
            Dogs vomit for a variety of reasons. Some are benign, while others
            require immediate medical attention. Common causes include:
          </p>
          <ul className="text-slate-600 space-y-2 mb-8 list-disc pl-6">
            <li>
              <strong>Dietary Indiscretion:</strong> Eating garbage, spoiled
              food, or table scraps.
            </li>
            <li>
              <strong>Sudden Diet Changes:</strong> Switching kibble brands too
              quickly without a transition period.
            </li>
            <li>
              <strong>Parasites:</strong> Intestinal worms common in tropical
              climates like India.
            </li>
            <li>
              <strong>Infections:</strong> Viral infections like Parvovirus
              (especially in unvaccinated puppies) or bacterial infections.
            </li>
            <li>
              <strong>Toxins:</strong> Ingesting household chemicals, toxic
              plants, or human medications.
            </li>
          </ul>

          {/* HIGH CONVERSION CTA BLOCK */}
          <div className="not-prose bg-gradient-to-r from-slate-900-light to-slate-900 border border-brand/30 p-8 rounded-2xl text-center my-12 shadow-lg shadow-brand/5">
            <h3 className="text-2xl font-bold text-slate-900 mb-3">
              Is your dog vomiting repeatedly?
            </h3>
            <p className="text-slate-700 mb-6">
              Don&apos;t wait and guess. Get professional triage from a verified
              vet in minutes.
            </p>
            <a
              href="/lp-video-consultation-india.html"
              className="inline-block w-full sm:w-auto"
            >
              <Button variant="primary" size="lg" className="w-full text-lg">
                Start Video Consultation Now
              </Button>
            </a>
          </div>

          <h2 className="text-2xl font-bold text-slate-900 mt-10 mb-4">
            When is Dog Vomiting an Emergency?
          </h2>
          <p className="text-slate-600 mb-4">
            While a single episode of vomiting might just be an upset stomach,
            you should seek immediate veterinary care if you notice:
          </p>
          <ul className="text-slate-600 space-y-2 mb-8 list-disc pl-6">
            <li>Vomiting multiple times in one day.</li>
            <li>
              Blood in the vomit (looks like red streaks or coffee grounds).
            </li>
            <li>
              Accompanying symptoms like severe diarrhea, lethargy, or fever.
            </li>
            <li>
              Unproductive retching (trying to vomit but nothing comes up) - this
              is a sign of Bloat (GDV), a life-threatening emergency.
            </li>
            <li>Suspected ingestion of a foreign object or toxin.</li>
          </ul>

          <h2 className="text-2xl font-bold text-slate-900 mt-10 mb-4">
            Home Care vs. Vet Care
          </h2>
          <p className="text-slate-600 mb-6">
            If your dog has vomited once but is otherwise acting completely
            normal (playing, drinking water), you might withhold food for 12
            hours to let their stomach rest. However, if symptoms persist,
            professional advice is mandatory.
          </p>

          <p className="text-slate-600 mb-8">
            For peace of mind, you can always use our{" "}
            <Link
              href="/video-consultation-india"
              className="text-brand hover:underline"
            >
              online vet consultation India
            </Link>{" "}
            service to speak with a doctor who can assess your dog&apos;s
            symptoms over video and recommend whether a clinic visit is
            necessary or if home management is safe.
          </p>
        </article>
      </main>

      <Footer />
    </div>
  );
}