import { lazy, Suspense } from "react";
import { Navbar } from "./Navbar";
import { Footer } from "./NewFooter";
import { Bot, Smartphone, Download } from "lucide-react";

const SymptomCheckerChat = lazy(() =>
  import("./SymptomCheckerChat").then((mod) => ({
    default: mod.SymptomCheckerChat,
  }))
);

export default function SymptomsHub() {
  return (
    <div className="flex min-h-screen flex-col bg-white text-slate-900">
      <Navbar />

      <main className="flex-1 py-16">
        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <span className="inline-flex items-center gap-2 rounded-full bg-brand/10 px-4 py-1.5 text-sm font-medium text-brand mb-6">
              <Bot className="w-4 h-4" /> Powered by SnoutIQ AI
            </span>

            <h1 className="font-display text-4xl md:text-5xl font-bold mb-6">
              AI Pet Symptom Checker
            </h1>

            <p className="text-xl text-slate-600">
              Describe your pet&apos;s symptoms to our AI assistant for immediate
              triage advice. Always consult a professional for accurate diagnosis.
            </p>
          </div>

          <div className="mb-16">
            <Suspense
              fallback={
                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                  Loading symptom checker...
                </div>
              }
            >
              <SymptomCheckerChat />
            </Suspense>
          </div>

          <div
            className="mt-16 rounded-2xl border border-brand/30 bg-gradient-to-br from-brand-light via-white to-slate-50 p-8 text-center shadow-lg shadow-brand/10"
            style={{ contentVisibility: "auto", containIntrinsicSize: "1px 560px" }}
          >
            <div className="flex justify-center mb-4">
              <div className="w-16 h-16 bg-brand/20 rounded-full flex items-center justify-center border border-brand/30">
                <Smartphone className="w-8 h-8 text-brand" />
              </div>
            </div>

            <h3 className="text-2xl font-bold text-slate-900 mb-4">
              Get the SnoutIQ App
            </h3>

            <p className="text-slate-600 mb-6 max-w-2xl mx-auto">
              For personalized recommendations, health insights, and to instantly
              consult with verified veterinarians, download the SnoutIQ app today.
            </p>

            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="https://play.google.com/store/apps/details?id=com.petai.snoutiq"
                target="_blank"
                rel="noreferrer"
                className="inline-flex min-w-[220px] items-center justify-center gap-2 rounded-xl bg-slate-900 px-8 py-4 font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-black"
              >
                <Download className="h-4 w-4" />
                Download for Android
              </a>

              <span
                className="inline-flex min-w-[220px] cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-8 py-4 font-bold text-slate-500"
                title="Coming soon"
              >
                <Download className="h-4 w-4" />
                Download for iOS
              </span>
            </div>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
