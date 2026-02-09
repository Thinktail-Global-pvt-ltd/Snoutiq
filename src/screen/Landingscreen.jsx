import React from "react";
import { VETS } from "../../constants";
import { Button } from "../components/Button";
import { Clock, Award, FileText, Star, Heart, Stethoscope } from "lucide-react";
import { PieChart, Pie, Cell, ResponsiveContainer } from "recharts";

const LandingScreen = ({ onStart, onVetAccess }) => {
  const trustData = [
    { name: "Happy", value: 98 },
    { name: "Unhappy", value: 2 },
  ];
  const COLORS = ["#10b981", "#f3f4f6"];

  return (
    <div
      className="
        min-h-screen flex flex-col bg-calm-bg animate-fade-in relative
        pb-20
        md:pb-36 lg:pb-40
        md:bg-gradient-to-b md:from-calm-bg md:to-white
      "
    >
      {/* Vet Access Button */}
    <div className="absolute top-6 right-6 z-20">
  <button
    onClick={onVetAccess}
    className="
      text-xs font-bold
      bg-white/90 backdrop-blur px-3 py-1.5 rounded-full shadow-sm border border-blue-100
      hover:bg-blue-50 transition-colors flex items-center gap-1
      md:text-sm md:px-4 md:py-2 md:shadow-md
    "
  >
    {/* Gradient Icon (mobile) */}
    <span
      className="md:hidden inline-flex h-4 w-4 bg-gradient-to-r from-blue-600 to-blue-500"
      style={{
        WebkitMaskImage:
          "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M11 5a4 4 0 0 0-4 4v1H5a2 2 0 0 0-2 2v3a7 7 0 0 0 14 0v-3a2 2 0 0 0-2-2h-2V9a4 4 0 0 0-4-4Z'/%3E%3Cpath d='M8 10V9a4 4 0 0 1 8 0v1'/%3E%3C/svg%3E\")",
        WebkitMaskRepeat: "no-repeat",
        WebkitMaskPosition: "center",
        WebkitMaskSize: "contain",
        maskImage:
          "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M11 5a4 4 0 0 0-4 4v1H5a2 2 0 0 0-2 2v3a7 7 0 0 0 14 0v-3a2 2 0 0 0-2-2h-2V9a4 4 0 0 0-4-4Z'/%3E%3Cpath d='M8 10V9a4 4 0 0 1 8 0v1'/%3E%3C/svg%3E\")",
        maskRepeat: "no-repeat",
        maskPosition: "center",
        maskSize: "contain",
      }}
    />

    {/* Gradient Text */}
    <span className="bg-gradient-to-r from-blue-600 to-blue-500 bg-clip-text text-transparent">
      Vet Access
    </span>
  </button>
</div>


      {/* ===== Desktop: FULL-WIDTH (no max container). Mobile stays same. ===== */}
      <div className="w-full">
        {/* Top Section */}
        <div
          className="
            px-6 pt-8 pb-6 bg-white rounded-b-[2rem] shadow-sm z-10
            md:rounded-none md:shadow-none md:bg-transparent md:px-10 md:pt-16 md:pb-10
            lg:px-16 lg:pt-20 lg:pb-12
          "
        >
          {/* md+ : full width grid with comfortable gutters */}
          <div className="md:grid md:grid-cols-12 md:gap-10 lg:gap-14 md:items-center">
            {/* Left: Hero */}
            <div className="md:col-span-7 lg:col-span-7">
              <div className="flex justify-between items-start mb-6">
                <div
                  className="
                    bg-brand-50 text-brand-700 px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1
                    md:text-sm md:px-4 md:py-2
                  "
                >
                  <span className="relative flex h-2 w-2 md:h-2.5 md:w-2.5">
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-2 w-2 md:h-2.5 md:w-2.5 bg-brand-500"></span>
                  </span>
                  Online Now
                </div>
              </div>

              <h1 className="text-3xl font-bold text-stone-900 leading-tight mb-3 md:text-5xl lg:text-6xl md:leading-[1.05]">
                Talk to a verified vet in{" "}
                <span className="text-brand-600">15 minutes</span>
              </h1>

              <p className="text-stone-500 mb-8 font-medium md:text-lg lg:text-xl md:max-w-2xl">
                Video consultation • Prescription included • Trusted by pet
                parents
              </p>

              {/* Vet Cluster */}
              <div className="flex items-center gap-4 mb-2 md:mb-0">
                <div className="flex -space-x-3 md:-space-x-4">
                  {VETS.map((vet) => (
                    <img
                      key={vet.id}
                      src={vet.image}
                      alt={vet.name}
                      className="w-10 h-10 rounded-full border-2 border-white object-cover shadow-sm md:w-14 md:h-14 lg:w-16 lg:h-16"
                    />
                  ))}
                  <div className="w-10 h-10 rounded-full border-2 border-white bg-brand-50 flex items-center justify-center text-xs font-bold text-brand-600 md:w-14 md:h-14 md:text-sm lg:w-16 lg:h-16">
                    +30
                  </div>
                </div>
                <div className="text-sm font-semibold text-stone-700 md:text-lg">
                  Verified vets <br /> across India
                </div>
              </div>
            </div>

            {/* Right: Desktop trust highlight card (ONLY md+) */}
            <div className="hidden md:block md:col-span-5 lg:col-span-5">
              <div className="rounded-3xl border border-stone-200 bg-white/70 backdrop-blur p-8 shadow-sm">
                <div className="flex items-center justify-between mb-5">
                  <div className="text-base font-semibold text-stone-700">
                    Trust & Quality
                  </div>
                  <div className="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full">
                    98% happy
                  </div>
                </div>

                <div className="h-36 w-full rounded-2xl bg-white border border-stone-100 overflow-hidden relative">
                  <div className="absolute inset-0 opacity-20 pointer-events-none">
                    <ResponsiveContainer width="100%" height="100%">
                      <PieChart>
                        <Pie
                          data={trustData}
                          innerRadius={52}
                          outerRadius={70}
                          dataKey="value"
                        >
                          {trustData.map((entry, index) => (
                            <Cell
                              key={`cell-${index}`}
                              fill={COLORS[index % COLORS.length]}
                            />
                          ))}
                        </Pie>
                      </PieChart>
                    </ResponsiveContainer>
                  </div>

                  <div className="relative p-6">
                    <div className="flex items-center gap-2 mb-2">
                      <Star className="text-amber-500" size={20} />
                      <span className="text-base font-semibold text-stone-800">
                        4.9/5 average rating
                      </span>
                    </div>
                    <p className="text-sm text-stone-500 leading-relaxed">
                      Verified vets, digital prescription, and fast responses —
                      designed for peace of mind.
                    </p>
                  </div>
                </div>

                <div className="mt-5 grid grid-cols-2 gap-4">
                  <div className="rounded-2xl bg-white border border-stone-100 p-4">
                    <div className="text-sm text-stone-500">Avg response</div>
                    <div className="text-2xl font-bold text-stone-900">
                      ~15m
                    </div>
                  </div>
                  <div className="rounded-2xl bg-white border border-stone-100 p-4">
                    <div className="text-sm text-stone-500">Prescription</div>
                    <div className="text-2xl font-bold text-stone-900">
                      Included
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Trust Strip */}
        <div className="px-6 py-8 grid grid-cols-2 gap-4 md:px-10 md:py-10 lg:px-16 md:grid-cols-4 md:gap-6">
          <div className="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center text-center gap-2 md:py-8 md:rounded-3xl">
            <div className="bg-blue-50 p-2 rounded-full text-blue-600 md:p-3">
              <Clock size={20} className="md:hidden" />
              <Clock size={24} className="hidden md:block" />
            </div>
            <span className="text-sm font-semibold text-stone-700 md:text-lg">
              Responds in
              <br />
              ~15 mins
            </span>
          </div>

          <div className="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center text-center gap-2 md:py-8 md:rounded-3xl">
            <div className="bg-emerald-50 p-2 rounded-full text-emerald-600 md:p-3">
              <Award size={20} className="md:hidden" />
              <Award size={24} className="hidden md:block" />
            </div>
            <span className="text-sm font-semibold text-stone-700 md:text-lg">
              Certified
              <br />
              Vets
            </span>
          </div>

          <div className="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center text-center gap-2 md:py-8 md:rounded-3xl">
            <div className="bg-purple-50 p-2 rounded-full text-purple-600 md:p-3">
              <FileText size={20} className="md:hidden" />
              <FileText size={24} className="hidden md:block" />
            </div>
            <span className="text-sm font-semibold text-stone-700 md:text-lg">
              Digital
              <br />
              Prescription
            </span>
          </div>

          <div className="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center text-center gap-2 relative overflow-hidden md:py-8 md:rounded-3xl">
            <div className="absolute inset-0 opacity-10 pointer-events-none">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={trustData}
                    innerRadius={30}
                    outerRadius={40}
                    dataKey="value"
                  >
                    {trustData.map((entry, index) => (
                      <Cell
                        key={`cell-${index}`}
                        fill={COLORS[index % COLORS.length]}
                      />
                    ))}
                  </Pie>
                </PieChart>
              </ResponsiveContainer>
            </div>

            <div className="bg-amber-50 p-2 rounded-full text-amber-600 z-10 md:p-3">
              <Star size={20} className="md:hidden" />
              <Star size={24} className="hidden md:block" />
            </div>
            <span className="text-sm font-semibold text-stone-700 z-10 md:text-lg">
              4.9/5 Rated
              <br />
              by parents
            </span>
          </div>
        </div>

        {/* Emotional Reassurance */}
        <div className="px-6 pb-24 md:px-10 lg:px-16 md:pb-32 lg:pb-36">
          <div className="bg-stone-100 p-6 rounded-2xl border border-stone-200 md:p-10 md:rounded-3xl">
            <p className="text-stone-600 italic leading-relaxed text-sm mb-4 md:text-lg md:max-w-4xl">
              "We built this because we've been there — worried, googling,
              waiting. This is our way of making vets more accessible to every
              pet parent in India."
            </p>
            <div className="flex items-center gap-2 text-stone-400 text-xs font-semibold uppercase tracking-wider md:text-sm">
              <Heart
                size={12}
                className="text-red-400 fill-current md:hidden"
              />
              <Heart
                size={14}
                className="hidden md:block text-red-400 fill-current"
              />
              From fellow pet parents
            </div>
          </div>
        </div>
      </div>

      {/* Sticky CTA
          MOBILE: same (max-w-md centered)
          DESKTOP: full-width bar + bigger button/text (but balanced)
       */}
      <div
        className="
          fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb
          max-w-md mx-auto
          md:max-w-none md:mx-0 md:left-0 md:right-0 md:bottom-0
          md:bg-white/85 md:backdrop-blur md:border-t md:shadow-[0_-12px_40px_rgba(0,0,0,0.08)]
          md:px-10 lg:px-16 md:py-5
          z-50
        "
      >
        <div className="md:flex md:items-center md:justify-between md:gap-10">
          <div className="hidden md:block">
            <div className="text-lg font-semibold text-stone-900">
              Ready to talk to a vet?
            </div>
            <div className="text-sm text-stone-500">
              Start in minutes • Prescription included
            </div>
          </div>

          <div className="md:flex-1 md:flex md:justify-end">
            {/* MOBILE stays fullWidth via prop, DESKTOP becomes auto width */}
            <Button
              onClick={onStart}
              fullWidth
              className="
                text-lg shadow-brand-200/50
                md:w-auto md:text-lg lg:text-xl md:px-8 lg:px-10 md:py-3.5 lg:py-4 md:rounded-2xl
                whitespace-nowrap 
              bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600"
            >
              Get help for my pet
            </Button>
          </div>
        </div>

        <div className="text-center mt-3 md:mt-2">
          <button className="text-xs text-stone-400 font-medium hover:text-stone-600 md:text-sm">
            How it works
          </button>
        </div>
      </div>
    </div>
  );
};

export default LandingScreen;
