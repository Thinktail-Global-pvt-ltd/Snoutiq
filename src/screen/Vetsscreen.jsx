import React, { useMemo, useState } from "react";
import { VETS } from "../../constants";
import { Button } from "../components/Button";
import { Header, ProgressBar } from "../components/Sharedcomponents";
import { Clock, Award, Star, Sparkles, Zap, SlidersHorizontal } from "lucide-react";

const VetsScreen = ({ petDetails, onSelect, onBack }) => {
  // Desktop-only filter/sort state (mobile UI unchanged because controls are hidden on mobile)
  const [sortMode, setSortMode] = useState("recommended"); // recommended | priceLow | ratingHigh

  const sortedVets = useMemo(() => {
    if (!petDetails) return VETS;

    const base = [...VETS];

    const specialtyScore = (v) =>
      petDetails?.type && v.specialties.includes(petDetails.type) ? 1 : 0;

    if (sortMode === "priceLow") {
      return base.sort((a, b) => a.priceDay - b.priceDay);
    }

    if (sortMode === "ratingHigh") {
      return base.sort((a, b) => (b.rating || 0) - (a.rating || 0));
    }

    // recommended (default): specialty match first then price
    return base.sort((a, b) => {
      const aMatch = specialtyScore(a);
      const bMatch = specialtyScore(b);

      if (aMatch && !bMatch) return -1;
      if (!aMatch && bMatch) return 1;

      return a.priceDay - b.priceDay;
    });
  }, [petDetails, sortMode]);

  const bestValueId = useMemo(() => {
    if (sortedVets.length === 0) return null;

    const topMatches = sortedVets.filter(
      (v) => petDetails?.type && v.specialties.includes(petDetails.type)
    );

    const pool = topMatches.length > 0 ? topMatches : sortedVets;
    return pool[0]?.id;
  }, [sortedVets, petDetails]);

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <Header onBack={onBack} title="Available Vets" />

      {/* ✅ Desktop: FULL WIDTH (no max container). Mobile unchanged */}
      <div className="w-full">
        <div className="flex-1 px-4 py-6 pb-20 overflow-y-auto no-scrollbar md:px-10 lg:px-16 md:py-12">
          <ProgressBar current={2} total={3} />

          {/* Contextual Header */}
          <div className="mb-4 flex flex-col gap-2 mt-6 md:mt-10">
            <div className="flex items-start md:items-end justify-between gap-4">
              <div className="min-w-0">
                <h2 className="text-lg font-bold text-stone-800 md:text-4xl md:leading-[1.15]">
                  {petDetails?.name
                    ? `Best vets for ${petDetails.name}`
                    : "Recommended Vets"}
                </h2>
                <p className="hidden md:block text-base text-stone-500 mt-2 max-w-3xl">
                  Choose a vet based on specialty match, ratings, consult history, and price.
                </p>
              </div>

              <div className="flex items-center gap-2 shrink-0">
                <div className="flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg md:px-3 md:py-2 md:text-sm md:rounded-xl">
                  <Zap size={12} fill="currentColor" className="md:hidden" />
                  <Zap size={16} fill="currentColor" className="hidden md:block" />
                  <span>Fast response</span>
                </div>
              </div>
            </div>

            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-6">
              <div className="flex items-center gap-2 text-sm text-stone-500 bg-stone-100 p-2 rounded-lg px-3 md:text-base md:px-4 md:py-3 md:rounded-2xl">
                <Clock size={14} className="md:hidden" />
                <Clock size={18} className="hidden md:block" />
                <span>
                  Average response time: <strong>8 mins</strong>
                </span>
              </div>

              {/* Desktop-only sort controls (mobile no change) */}
              <div className="hidden md:flex items-center gap-3">
                <div className="flex items-center gap-2 text-sm font-semibold text-stone-600 bg-white border border-stone-200 px-4 py-3 rounded-2xl shadow-sm">
                  <SlidersHorizontal size={16} />
                  <span>Sort</span>
                </div>

                <div className="flex items-center bg-white border border-stone-200 rounded-2xl overflow-hidden shadow-sm">
                  <button
                    type="button"
                    onClick={() => setSortMode("recommended")}
                    className={`px-4 py-3 text-sm font-semibold transition-colors ${
                      sortMode === "recommended"
                        ? "bg-brand-50 text-brand-700"
                        : "text-stone-600 hover:bg-stone-50"
                    }`}
                  >
                    Recommended
                  </button>
                  <button
                    type="button"
                    onClick={() => setSortMode("priceLow")}
                    className={`px-4 py-3 text-sm font-semibold transition-colors border-l border-stone-200 ${
                      sortMode === "priceLow"
                        ? "bg-brand-50 text-brand-700"
                        : "text-stone-600 hover:bg-stone-50"
                    }`}
                  >
                    Price
                  </button>
                  <button
                    type="button"
                    onClick={() => setSortMode("ratingHigh")}
                    className={`px-4 py-3 text-sm font-semibold transition-colors border-l border-stone-200 ${
                      sortMode === "ratingHigh"
                        ? "bg-brand-50 text-brand-700"
                        : "text-stone-600 hover:bg-stone-50"
                    }`}
                  >
                    Rating
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Cards: mobile = list, md+ = grid */}
          <div className="space-y-4 md:space-y-0 md:grid md:grid-cols-2 md:gap-8 lg:grid-cols-3">
            {sortedVets.map((vet, index) => {
              const isRecommended = index === 0 && sortMode === "recommended";
              const isBestValue = vet.id === bestValueId && !isRecommended;
              const isSpecialist =
                petDetails?.type && vet.specialties.includes(petDetails.type);

              return (
                <div
                  key={vet.id}
                  className={`bg-white p-4 rounded-2xl shadow-sm flex flex-col gap-4 relative overflow-hidden transition-all
                    ${
                      isRecommended
                        ? "ring-2 ring-brand-200 shadow-md"
                        : "border border-stone-100 hover:shadow-md"
                    }
                    md:p-7 md:rounded-3xl`}
                >
                  {/* Recommended Badge */}
                  {isRecommended && (
                    <div className="bg-brand-100 text-brand-700 text-xs font-bold px-3 py-1 absolute top-0 left-0 rounded-br-xl flex items-center gap-1 md:text-sm md:px-4 md:py-2 md:rounded-br-2xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600">
                      <Sparkles size={12} className="md:hidden" />
                      <Sparkles size={16} className="hidden md:block text-white" />
                      <span className="text-white">Recommended for you</span>
                    </div>
                  )}

                  {/* Best Value Badge */}
                  {!isRecommended && isBestValue && (
                    <div className="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 absolute top-0 left-0 rounded-br-xl flex items-center gap-1 md:text-sm md:px-4 md:py-2 md:rounded-br-2xl">
                      <Award size={12} className="md:hidden" />
                      <Award size={16} className="hidden md:block" />
                      Best Value
                    </div>
                  )}

                  {/* Online Indicator */}
                  <div className="absolute top-4 right-4 bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-1 rounded-full flex items-center gap-1 md:top-6 md:right-6 md:text-xs md:px-3 md:py-1.5">
                    <div className="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                    ~15m
                  </div>

                  <div className="flex gap-4 mt-4 md:mt-10">
                    <img
                      src={vet.image}
                      alt={vet.name}
                      className="w-16 h-16 rounded-full object-cover border-2 border-stone-50 md:w-16 md:h-16 lg:w-18 lg:h-18"
                    />
                    <div className="flex-1 min-w-0">
                      <h3 className="font-bold text-stone-800 text-lg flex items-center gap-2 md:text-xl">
                        <span className="truncate">{vet.name}</span>
                        {isSpecialist && (
                          <span className="shrink-0 text-[10px] bg-stone-100 text-stone-500 px-1.5 py-0.5 rounded font-medium border border-stone-200 md:text-xs md:px-2 md:py-1 md:rounded-lg">
                            {petDetails?.type === "exotic"
                              ? "Exotic Specialist"
                              : `${petDetails?.type} Specialist`}
                          </span>
                        )}
                      </h3>

                      <p className="text-xs text-stone-500 mb-1 md:text-sm">
                        {vet.qualification} • {vet.experience} yrs exp
                      </p>

                      <div className="flex gap-2 items-center mb-2">
                        <div className="flex items-center gap-1 bg-amber-50 px-1.5 py-0.5 rounded text-amber-700 text-xs font-bold md:text-sm md:px-2 md:py-1 md:rounded-lg">
                          <Star size={10} fill="currentColor" className="md:hidden" />
                          <Star size={14} fill="currentColor" className="hidden md:block" />
                          {vet.rating}
                        </div>
                        <span className="text-xs text-stone-400 md:text-sm">
                          {vet.consultations}+ consults
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="border-t border-stone-100 pt-3 flex items-center justify-between md:pt-5">
                    <div className="flex flex-col">
                      <div className="flex items-baseline gap-2">
                        <span className="text-lg font-bold text-stone-900 md:text-2xl">
                          ₹{vet.priceDay}
                        </span>
                        {vet.priceDay < 450 && (
                          <span className="text-xs text-stone-400 line-through decoration-stone-300 md:text-sm">
                            ₹{vet.priceDay + 150}
                          </span>
                        )}
                      </div>
                      <span className="text-xs text-stone-400 md:text-sm">
                        video consult
                      </span>
                    </div>

                    {/* ✅ Desktop: bigger button (mobile stays same default look) */}
                    <Button
                      onClick={() => onSelect(vet)}
                      className={`py-2 px-6 text-sm md:py-3 md:px-8 md:text-base md:rounded-2xl  ${
                        isRecommended ? "bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600" : "bg-stone-800"
                      }`}
                    >
                      Consult Now
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Extra bottom space on desktop so it feels roomy */}
          <div className="hidden md:block h-10" />
        </div>
      </div>
    </div>
  );
};

export default VetsScreen;
