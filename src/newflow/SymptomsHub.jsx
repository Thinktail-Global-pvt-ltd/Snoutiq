import { lazy, Suspense } from "react";
import { Navbar } from "./Navbar";

const SymptomCheckerChat = lazy(() =>
  import("./SymptomCheckerChat").then((mod) => ({
    default: mod.SymptomCheckerChat,
  }))
);

export default function SymptomsHub() {
  return (
    <div className="flex h-[100dvh] flex-col overflow-hidden bg-white text-slate-900">
      <Navbar />

      <main className="flex min-h-0 flex-1 flex-col overflow-hidden py-3 md:py-6">
        <div className="mx-auto flex h-full w-full max-w-4xl min-h-0 flex-1 flex-col px-3 sm:px-6 lg:px-8">
          <div className="flex-1 min-h-0">
            <Suspense
              fallback={
                <div className="flex h-full min-h-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                  Loading symptom checker...
                </div>
              }
            >
              <SymptomCheckerChat />
            </Suspense>
          </div>
        </div>
      </main>
    </div>
  );
}
