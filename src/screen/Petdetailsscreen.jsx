import React, { useState } from "react";
import { SYMPTOMS, SPECIALTY_ICONS } from "../../constants";
import { Button } from "../components/Button";
import { Header, ProgressBar } from "../components/SharedComponents";
import { CheckCircle2, Upload, Star } from "lucide-react";

const PetDetailsScreen = ({ onSubmit, onBack }) => {
  const [details, setDetails] = useState({
    name: "",
    type: null,
    age: 2,
    symptoms: [],
    observation: "",
    hasPhoto: false,
  });

  const toggleSymptom = (s) => {
    setDetails((prev) => ({
      ...prev,
      symptoms: prev.symptoms.includes(s)
        ? prev.symptoms.filter((i) => i !== s)
        : [...prev.symptoms, s],
    }));
  };

  const handlePhotoUpload = (e) => {
    if (e.target.files && e.target.files.length > 0) {
      setDetails((prev) => ({ ...prev, hasPhoto: true }));
    }
  };

  const isValid = details.name.length > 0 && details.type !== null;

  return (
    <div className="min-h-screen bg-calm-bg flex flex-col animate-slide-up md:bg-gradient-to-b md:from-calm-bg md:to-white">
      <Header onBack={onBack} title="About your pet" />

      {/* ✅ Desktop: FULL WIDTH (no max container). Mobile unchanged. */}
      <div className="w-full">
        <div className="flex-1 px-6 py-6 pb-32 overflow-y-auto no-scrollbar md:px-10 lg:px-16 md:py-12">
          <div className="md:max-w-none">
            <ProgressBar current={1} total={3} />

            {/* Layout: mobile stacked, desktop = 2-column but full width */}
            <div className="mt-6 md:grid md:grid-cols-12 md:gap-10 lg:gap-14">
              {/* LEFT */}
              <div className="md:col-span-7 lg:col-span-7">
                <div className="space-y-8">
                  {/* Section 1 */}
                  <section className="bg-white p-5 rounded-2xl shadow-sm space-y-4 md:p-8 md:rounded-3xl">
                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                        Pet&apos;s Name
                      </label>
                      <input
                        type="text"
                        value={details.name}
                        onChange={(e) =>
                          setDetails({ ...details, name: e.target.value })
                        }
                        placeholder="e.g. Buster"
                        className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 focus:outline-none focus:ring-2 focus:ring-brand-200 transition-all md:p-4 md:text-base md:rounded-2xl"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                        Pet Type
                      </label>

                      {/* ✅ Mobile same, Desktop bigger */}
                      <div className="grid grid-cols-3 gap-3 md:gap-4">
                        {["dog", "cat", "exotic"].map((type) => (
                          <button
                            key={type}
                            type="button"
                            onClick={() => setDetails({ ...details, type })}
                            className={`p-3 rounded-xl border flex flex-col items-center gap-2 transition-all
                              md:p-5 md:flex-row md:justify-center md:gap-3 md:rounded-2xl 
                              ${
                                details.type === type
                                  ? "bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white ring-1 ring-brand-500"
                                  : "border-stone-200 text-stone-500 hover:bg-stone-50"
                              }`}
                          >
                            <div
                              className={
                                details.type === type
                                  ? "text-brand-600"
                                  : "text-stone-400"
                              }
                            >
                              {SPECIALTY_ICONS[type] || <Star />}
                            </div>
                            <span className="capitalize text-sm font-medium md:text-base">
                              {type}
                            </span>
                          </button>
                        ))}
                      </div>
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                        Age: {details.age} years
                      </label>
                      <input
                        type="range"
                        min="0"
                        max="20"
                        step="0.5"
                        value={details.age}
                        onChange={(e) =>
                          setDetails({
                            ...details,
                            age: parseFloat(e.target.value),
                          })
                        }
                        className="w-full h-2 bg-stone-200 rounded-lg appearance-none cursor-pointer accent-brand-500"
                      />
                      <div className="flex justify-between text-xs text-stone-400 mt-1 md:text-sm">
                        <span>Baby</span>
                        <span>Adult</span>
                        <span>Senior</span>
                      </div>
                    </div>
                  </section>

                  {/* Section 2 */}
                  <section className="bg-white p-5 rounded-2xl shadow-sm space-y-4 md:p-8 md:rounded-3xl">
                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-3 md:text-base">
                        What are you noticing?
                      </label>

                      {/* Desktop bigger pills */}
                      <div className="flex flex-wrap gap-2 md:gap-3">
                        {SYMPTOMS.map((symptom) => (
                          <button
                            key={symptom}
                            type="button"
                            onClick={() => toggleSymptom(symptom)}
                            className={`px-4 py-2 rounded-full text-sm font-medium transition-all border
                              md:px-6 md:py-3 md:text-base
                              ${
                                details.symptoms.includes(symptom)
                                  ? "bg-red-50 border-red-200 text-red-700"
                                  : "bg-white border-stone-200 text-stone-600 hover:border-stone-300"
                              }`}
                          >
                            {symptom}
                          </button>
                        ))}
                      </div>

                      <p className="hidden md:block mt-3 text-sm text-stone-400">
                        Select all that apply — it helps us match the right vet.
                      </p>
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-stone-700 mb-2 md:text-base">
                        Anything else?{" "}
                        <span className="text-stone-400 font-normal">
                          (Optional)
                        </span>
                      </label>
                      <textarea
                        value={details.observation}
                        onChange={(e) =>
                          setDetails({ ...details, observation: e.target.value })
                        }
                        placeholder="He seems a bit lethargic since morning..."
                        className="w-full p-3 rounded-xl border border-stone-200 bg-stone-50 h-24 resize-none focus:outline-none focus:ring-2 focus:ring-brand-200 md:p-4 md:h-32 md:text-base md:rounded-2xl"
                      />
                    </div>
                  </section>

                  {/* Section 3 */}
                  <section className="bg-white p-5 rounded-2xl shadow-sm md:p-8 md:rounded-3xl">
                    <label className="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-brand-200 rounded-xl cursor-pointer bg-brand-50/30 hover:bg-brand-50 transition-colors md:h-44 md:rounded-2xl">
                      <div className="flex flex-col items-center justify-center pt-5 pb-6">
                        {details.hasPhoto ? (
                          <CheckCircle2 className="w-8 h-8 text-emerald-500 mb-2 md:w-10 md:h-10" />
                        ) : (
                          <Upload className="w-8 h-8 text-brand-400 mb-2 md:w-10 md:h-10" />
                        )}
                        <p className="mb-1 text-sm text-stone-600 font-medium md:text-base">
                          {details.hasPhoto
                            ? "Photo added"
                            : "Upload photo/video"}
                        </p>
                        <p className="text-xs text-stone-400 md:text-sm">
                          Helps the vet understand better
                        </p>
                      </div>
                      <input
                        type="file"
                        className="hidden"
                        onChange={handlePhotoUpload}
                        accept="image/*,video/*"
                      />
                    </label>

                    <p className="hidden md:block mt-3 text-sm text-stone-400">
                      Tip: A short 5–10 sec video works great for movement issues.
                    </p>
                  </section>
                </div>

                {/* Mobile spacer for bottom CTA */}
                <div className="h-24 md:hidden" />
              </div>

              {/* RIGHT */}
              <div className="hidden md:block md:col-span-5 lg:col-span-5">
                <div className="sticky top-24 space-y-5">
                  <div className="bg-white rounded-3xl border border-stone-100 shadow-sm p-8">
                    <div className="text-base font-bold text-stone-800 mb-1">
                      Quick summary
                    </div>
                    <p className="text-sm text-stone-500 mb-4">
                      We’ll use this to match the best vet.
                    </p>

                    <div className="space-y-3 text-base">
                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Name</span>
                        <span className="font-semibold text-stone-800">
                          {details.name || "—"}
                        </span>
                      </div>

                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Type</span>
                        <span className="font-semibold text-stone-800 capitalize">
                          {details.type || "—"}
                        </span>
                      </div>

                      <div className="flex items-center justify-between">
                        <span className="text-stone-500">Age</span>
                        <span className="font-semibold text-stone-800">
                          {details.age} yrs
                        </span>
                      </div>

                      <div className="pt-4 border-t border-stone-100">
                        <div className="text-stone-500 text-sm mb-2">
                          Selected symptoms
                        </div>
                        <div className="flex flex-wrap gap-2">
                          {details.symptoms.length ? (
                            details.symptoms.slice(0, 8).map((s) => (
                              <span
                                key={s}
                                className="text-sm bg-stone-50 border border-stone-200 px-3 py-1.5 rounded-full text-stone-600"
                              >
                                {s}
                              </span>
                            ))
                          ) : (
                            <span className="text-sm text-stone-400">
                              None selected
                            </span>
                          )}
                          {details.symptoms.length > 8 && (
                            <span className="text-sm text-stone-400">
                              +{details.symptoms.length - 8} more
                            </span>
                          )}
                        </div>
                      </div>

                      <div className="pt-4 border-t border-stone-100 flex items-center justify-between">
                        <span className="text-stone-500">Photo/Video</span>
                        <span
                          className={`text-sm font-bold px-3 py-1.5 rounded-full border ${
                            details.hasPhoto
                              ? "text-emerald-700 bg-emerald-50 border-emerald-100"
                              : "text-stone-500 bg-stone-50 border-stone-200"
                          }`}
                        >
                          {details.hasPhoto ? "Added" : "Not added"}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="bg-white rounded-3xl border border-stone-100 shadow-sm p-8">
                    <Button
                      onClick={() => onSubmit(details)}
                      disabled={!isValid}
                      className={`w-full md:text-xl md:py-4 md:rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600" ${
                        !isValid
                          ? "opacity-50 cursor-not-allowed bg-stone-300 shadow-none"
                          : ""
                      }`}
                    >
                      See available vets
                    </Button>
                    <p className="text-sm text-stone-400 mt-3">
                      Takes less than a minute.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* ✅ Desktop: extra bottom padding so fixed footer never overlaps */}
            <div className="hidden md:block h-28" />
          </div>
        </div>
      </div>

      {/* ✅ Mobile CTA (same layout as before) */}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-stone-100 safe-area-pb max-w-md mx-auto z-20 md:hidden">
        <Button
          onClick={() => onSubmit(details)}
          fullWidth
          disabled={!isValid}
          className={
            !isValid
              ? "opacity-50 cursor-not-allowed bg-stone-300 shadow-none"
              : "bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600"
          }
        >
          See available vets
        </Button>
      </div>
    </div>
  );
};

export default PetDetailsScreen;
