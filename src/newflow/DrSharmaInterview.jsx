import React from "react";
import { Navbar } from "./Navbar";
import { Footer } from "./NewFooter";
import { Button } from "./NewButton";
import { Link } from "react-router-dom";
import { UserCircle } from "lucide-react";

export const metadata = {
  title: "Interview: Dr. Sharma on Managing Pet Emergencies at Home | SnoutiQ",
  description:
    "Expert advice from Dr. Sharma on how to handle pet emergencies at home and when to use a 24/7 online vet in India.",
};

export default function DrSharmaInterview() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: "Interview: Dr. Sharma on Managing Pet Emergencies at Home",
    author: {
      "@type": "Person",
      name: "Dr. A. Sharma",
      jobTitle: "Senior Veterinarian",
      worksFor: {
        "@type": "Organization",
        name: "SnoutiQ",
      },
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
          <div className="mb-10 border-b border-slate-200 pb-8">
            <h1 className="font-display text-4xl md:text-5xl font-bold mb-6 text-slate-900 leading-tight">
              Interview: Dr. Sharma on Managing Pet Emergencies at Home
            </h1>

            <div className="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
              <UserCircle className="w-12 h-12 text-brand" />
              <div>
                <p className="text-slate-900 font-bold m-0">
                  Dr. A. Sharma, BVSc & AH
                </p>
                <p className="text-sm text-slate-600 m-0">
                  Senior Veterinarian • 12+ Years Experience
                </p>
              </div>
            </div>
          </div>

          <p className="text-xl text-slate-700 mb-8 leading-relaxed">
            Pet emergencies rarely happen during convenient hours. We sat down
            with Dr. Sharma to discuss what pet parents should do when panic
            strikes at 2 AM, and how a{" "}
            <Link
              href="/video-consultation-india"
              className="text-brand hover:underline"
            >
              24/7 online vet India
            </Link>{" "}
            service can be a lifesaver.
          </p>

          <h2 className="text-2xl font-bold text-slate-900 mt-10 mb-4">
            Q: What are the most common late-night emergencies you see?
          </h2>
          <p className="text-slate-600 mb-4">
            <strong>Dr. Sharma:</strong>{" "}
            &quot;The vast majority of midnight calls involve sudden
            gastrointestinal issues—severe vomiting or diarrhea. We also see a
            lot of allergic reactions (like a swollen face from an insect bite),
            sudden limping, and ingestion of toxins like chocolate or human
            medications.&quot;
          </p>

          <h2 className="text-2xl font-bold text-slate-900 mt-10 mb-4">
            Q: How should a pet parent react when they notice these symptoms?
          </h2>
          <p className="text-slate-600 mb-4">
            <strong>Dr. Sharma:</strong>{" "}
            &quot;First, stay calm. Dogs and cats pick up on our anxiety.
            Second, do not administer human medications like Paracetamol or
            Ibuprofen—they are highly toxic to pets. Instead, get professional
            triage immediately. If you can&apos;t reach a local clinic, jump on
            a video call with a vet.&quot;
          </p>

          {/* HIGH CONVERSION CTA BLOCK */}
          <div className="not-prose bg-gradient-to-r from-slate-900-light to-slate-900 border border-brand/30 p-8 rounded-2xl text-center my-12 shadow-lg shadow-brand/5">
            <h3 className="text-2xl font-bold text-slate-900 mb-3">
              Facing a pet emergency right now?
            </h3>
            <p className="text-slate-700 mb-6">
              Connect with Dr. Sharma or another verified expert instantly.
            </p>
            <a
              href="/lp-video-consultation-india.html"
              className="inline-block w-full sm:w-auto"
            >
              <Button
                variant="primary"
                size="lg"
                className="w-full text-lg"
              >
                Start Video Consultation
              </Button>
            </a>
          </div>

          <h2 className="text-2xl font-bold text-slate-900 mt-10 mb-4">
            Q: How does telemedicine help in an emergency?
          </h2>
          <p className="text-slate-600 mb-6">
            <strong>Dr. Sharma:</strong>{" "}
            &quot;Telemedicine is about rapid triage. Through a video call, I
            can assess the animal&apos;s breathing rate, gum color, and overall
            demeanor. In about 60% of cases, the issue can be managed at home
            with strict instructions. For the other 40%, I can tell the owner
            exactly how to stabilize the pet while they rush to a 24-hour
            physical clinic. It removes the guesswork.&quot;
          </p>

          <p className="text-slate-600 mb-8">
            Read more about our{" "}
            <Link
              href="/video-consultation-india"
              className="text-brand hover:underline"
            >
              online vet consultation India
            </Link>{" "}
            services and how we ensure your pet is always protected.
          </p>
        </article>
      </main>

      <Footer />
    </div>
  );
}