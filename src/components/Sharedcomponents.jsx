import React from "react";
import { ChevronLeft } from "lucide-react";

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

export const ProgressBar = ({ current = 1, total = 3 }) => (
  <div className="flex gap-2 mb-6 px-1 md:px-0 md:mb-10 md:gap-3">
    {Array.from({ length: total }).map((_, i) => (
      <div
        key={i}
        className={`
          h-1.5 rounded-full flex-1 transition-all duration-500
          md:h-2
          ${
            i < current
              ? "bg-gradient-to-r from-[#3998de] to-[#3998de]"
              : "bg-stone-200"
          }
        `}
      />
    ))}
  </div>
);
