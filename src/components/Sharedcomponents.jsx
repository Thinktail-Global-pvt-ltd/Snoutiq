import React from "react";
import { CheckCircle2, ChevronLeft } from "lucide-react";

export const PET_FLOW_STEPS = ["Choose Vet", "Pet Details", "Payment"];

export const Header = ({ onBack, title }) => (
  <div
    className="
      sticky top-0 z-50
      bg-white/80 backdrop-blur-md
      px-4 py-3
      flex items-center
      shadow-sm border-b border-stone-100
      md:px-10 lg:px-16 md:py-4
    "
  >
    {/* Left */}
    <div className="w-10 flex items-center justify-start md:w-14">
      {onBack ? (
        <button
          onClick={onBack}
          className="
            p-2 -ml-2 text-stone-500 hover:bg-stone-100 rounded-full transition-colors
            md:p-3 md:-ml-1
          "
          aria-label="Go back"
        >
          <ChevronLeft size={24} className="md:hidden" />
          <ChevronLeft size={28} className="hidden md:block" />
        </button>
      ) : null}
    </div>

    {/* Center */}
    <h1 className="flex-1 text-center font-bold text-lg text-stone-800 md:text-2xl lg:text-3xl">
      {title || "PawComfort"}
    </h1>

    {/* Right spacer / actions slot */}
    <div className="w-10 md:w-14" />
  </div>
);

export const ProgressBar = ({ current = 1, total = 3, steps }) => {
  const stepLabels = Array.isArray(steps) && steps.length ? steps : null;
  const count = stepLabels ? stepLabels.length : total;
  const safeCurrent = Math.min(Math.max(current, 1), count);

  const bar = (
    <div className="flex gap-2 mb-6 px-1 md:px-0 md:mb-10 md:gap-3 md:hidden">
      {Array.from({ length: count }).map((_, i) => (
        <div
          key={i}
          className={`
            h-1.5 rounded-full flex-1 transition-all duration-500
            md:h-2
            ${
              i < safeCurrent
                ? "bg-gradient-to-r from-[#3998de] to-[#3998de]"
                : "bg-stone-200"
            }
          `}
        />
      ))}
    </div>
  );

  if (!stepLabels) {
    return (
      <div className="flex gap-2 mb-6 px-1 md:px-0 md:mb-10 md:gap-3">
        {Array.from({ length: count }).map((_, i) => (
          <div
            key={i}
            className={`
              h-1.5 rounded-full flex-1 transition-all duration-500
              md:h-2
              ${
                i < safeCurrent
                  ? "bg-gradient-to-r from-[#3998de] to-[#3998de]"
                  : "bg-stone-200"
              }
            `}
          />
        ))}
      </div>
    );
  }

  const circleClass = (complete, active) =>
    [
      "w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold",
      complete || active
        ? "bg-gradient-to-r from-[#3998de] to-[#3998de] text-white"
        : "bg-gray-200 text-gray-600",
    ].join(" ");

  const labelClass = (complete, active) =>
    complete || active ? "font-medium text-gray-700" : "font-medium text-gray-500";

  const lineClass = (isComplete) =>
    [
      "w-16 h-0.5",
      isComplete ? "bg-gradient-to-r from-[#3998de] to-[#3998de]" : "bg-gray-200",
    ].join(" ");

  return (
    <div className="w-full">
      {bar}
      <div className="hidden md:flex items-center justify-center mb-10">
        <div className="flex items-center gap-4">
          {stepLabels.map((label, index) => {
            const stepIndex = index + 1;
            const complete = stepIndex < safeCurrent;
            const active = stepIndex === safeCurrent;
            return (
              <React.Fragment key={`${label}-${stepIndex}`}>
                <div className="flex items-center gap-2">
                  <div className={circleClass(complete, active)}>
                    {complete ? <CheckCircle2 size={18} /> : stepIndex}
                  </div>
                  <span className={labelClass(complete, active)}>{label}</span>
                </div>
                {index < stepLabels.length - 1 ? (
                  <div className={lineClass(complete)} />
                ) : null}
              </React.Fragment>
            );
          })}
        </div>
      </div>
    </div>
  );
};
