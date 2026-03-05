import React from "react";
import { Navbar } from "./Navbar";
import { Footer } from "./NewFooter";
import { Link } from "react-router-dom";
import { BookOpen } from "lucide-react";

export const metadata = {
  title: "Vet Insights & Expert Articles | SnoutiQ",
  description:
    "Read interviews, case studies, and expert advice from verified veterinarians across India.",
};

export default function VetInsightsHub() {
  const articles = [
    {
      title: "Interview: Dr. Sharma on Managing Pet Emergencies at Home",
      slug: "interview-dr-sharma-emergency-care",
      type: "Interview",
      author: "Dr. A. Sharma, BVSc & AH",
    },
    {
      title: "Case Study: Early Detection of Parvovirus via Telemedicine",
      slug: "case-study-parvovirus-survival",
      type: "Case Study",
      author: "Dr. R. Patel, MVSc",
    },
    {
      title: "Expert Guide: Feline Nutrition and Hydration",
      slug: "interview-feline-nutrition-expert",
      type: "Expert Guide",
      author: "Dr. S. Reddy, Feline Specialist",
    },
  ];

  return (
    <div className="flex min-h-screen flex-col bg-white text-slate-900">
      <Navbar />
      <main className="flex-1 py-16">
        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h1 className="font-display text-4xl md:text-5xl font-bold mb-6">
              Vet Insights
            </h1>
            <p className="text-xl text-slate-600">
              Expert knowledge, case studies, and interviews directly from
              India&apos;s top verified veterinarians.
            </p>
          </div>

          <div className="grid gap-6">
            {articles.map((article) => (
              <Link key={article.slug} href={`/vet-insights/${article.slug}`}>
                <div className="bg-slate-50 border border-slate-200 p-6 rounded-2xl hover:border-brand/50 transition-colors">
                  <div className="flex items-center gap-3 mb-3">
                    <span className="text-xs font-bold uppercase tracking-wider text-brand bg-brand/10 px-2 py-1 rounded">
                      {article.type}
                    </span>
                    <span className="text-sm text-slate-600 flex items-center gap-1">
                      <BookOpen className="w-4 h-4" /> {article.author}
                    </span>
                  </div>
                  <h2 className="text-2xl font-bold text-slate-900 mb-2">
                    {article.title}
                  </h2>
                  <p className="text-brand text-sm font-medium mt-4">
                    Read Article →
                  </p>
                </div>
              </Link>
            ))}
          </div>

          <div className="mt-12 text-center">
            <p className="text-slate-600">
              Need to speak with an expert directly? Check out our{" "}
              <Link
                href="/video-consultation-india"
                className="text-brand hover:underline"
              >
                24/7 online vet India
              </Link>{" "}
              services.
            </p>
          </div>
        </div>
      </main>
      <Footer />
    </div>
  );
}