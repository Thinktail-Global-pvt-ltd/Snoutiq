import { lazy, Suspense } from "react";

const AskPage = lazy(() => import("./AskPage"));

export default function SymptomsHub() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center bg-slate-50 p-6 text-sm text-slate-500">
          Loading symptom checker...
        </div>
      }
    >
      <AskPage />
    </Suspense>
  );
}
