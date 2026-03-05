import React, { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { CheckCircle2, X } from "lucide-react";
import { Button } from "./NewButton";

const getPetSelectionConfig = (title = "", href = "") => {
  const safeTitle = String(title).toLowerCase();
  const safeHref = String(href).toLowerCase();

  const isNeuteringCard =
    safeTitle.includes("neuter") ||
    safeTitle.includes("spay") ||
    safeHref.includes("neuter") ||
    safeHref.includes("spay");

  if (isNeuteringCard) {
    return {
      heading: "Choose your pet for neutering",
      subheading: "Select pet type to continue with the right surgery page.",
      options: [
        {
          key: "dog",
          label: "Dog",
          desc: "Continue to dog neutering flow",
          emoji: "DOG",
          route: "/dog-neutering-delhi",
        },
        {
          key: "kitten",
          label: "Kitten / Cat",
          desc: "Continue to cat spaying flow",
          emoji: "CAT",
          route: "/cat-neutering-delhi",
        },
      ],
    };
  }

  const isVaccinationCard =
    safeTitle.includes("vaccination") ||
    safeHref.includes("vaccination") ||
    safeHref.includes("puppy-vaccination") ||
    safeHref.includes("kitten-vaccination") ||
    safeHref === "/delhi-ncr";

  if (isVaccinationCard) {
    return {
      heading: "Choose your pet for vaccination",
      subheading: "Select pet type to open the correct vaccination package page.",
      options: [
        {
          key: "dog",
          label: "Dog / Puppy",
          desc: "Open puppy vaccination package",
          emoji: "DOG",
          route: "/puppy-vaccination-delhi",
        },
        {
          key: "kitten",
          label: "Kitten",
          desc: "Open kitten vaccination package",
          emoji: "CAT",
          route: "/kitten-vaccination-delhi",
        },
      ],
    };
  }

  return null;
};

export function ServiceCard({
  title,
  description,
  icon: Icon,
  features,
  price,
  badge,
  href,
  ctaText = "Book Now",
}) {
  const navigate = useNavigate();
  const selectionConfig = useMemo(
    () => getPetSelectionConfig(title, href),
    [title, href],
  );
  const [isSelectorOpen, setIsSelectorOpen] = useState(false);
  const [selectedPet, setSelectedPet] = useState("");

  const closeSelector = () => {
    setIsSelectorOpen(false);
    setSelectedPet("");
  };

  useEffect(() => {
    if (!isSelectorOpen) return undefined;

    const previousOverflow = document.body.style.overflow;
    const handleEscape = (event) => {
      if (event.key === "Escape") closeSelector();
    };

    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", handleEscape);

    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", handleEscape);
    };
  }, [isSelectorOpen]);

  const handleContinue = () => {
    if (!selectionConfig || !selectedPet) return;

    const option = selectionConfig.options.find((item) => item.key === selectedPet);
    if (!option?.route) return;

    closeSelector();
    navigate(option.route);
  };

  return (
    <>
      <div className="group flex flex-col rounded-2xl border border-slate-200 bg-slate-50 p-6 transition-all hover:border-brand/50 hover:shadow-lg hover:shadow-brand/5">
        <div className="mb-4 flex items-center justify-between">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand/10 text-brand">
            {Icon ? <Icon className="h-6 w-6" /> : null}
          </div>

          {badge ? (
            <span className="rounded-full bg-brand/20 px-3 py-1 text-xs font-medium text-brand">
              {badge}
            </span>
          ) : null}
        </div>

        <h3 className="mb-2 font-display text-xl font-semibold text-slate-900">
          {title}
        </h3>
        <p className="mb-6 flex-grow text-sm text-slate-600">{description}</p>

        <ul className="mb-6 space-y-2 text-sm text-slate-700">
          {(features || []).map((feature, i) => (
            <li key={`${feature}-${i}`} className="flex items-start gap-2">
              <span className="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-brand/20 text-[10px] text-brand">
                <CheckCircle2 className="h-3 w-3" />
              </span>
              <span>{feature}</span>
            </li>
          ))}
        </ul>

        {price ? (
          <div className="mb-6 border-t border-slate-200 pt-4">
            <p className="text-sm text-slate-600">Starting from</p>
            <p className="font-display text-2xl font-bold text-slate-900">
              {price}
            </p>
          </div>
        ) : null}

        {selectionConfig ? (
          <Button
            type="button"
            variant="outline"
            onClick={() => setIsSelectorOpen(true)}
            className="mt-auto w-full transition-colors group-hover:bg-brand group-hover:text-slate-900"
          >
            {ctaText}
          </Button>
        ) : (
          <Link to={href} className="mt-auto">
            <Button
              variant="outline"
              className="w-full transition-colors group-hover:bg-brand group-hover:text-slate-900"
            >
              {ctaText}
            </Button>
          </Link>
        )}
      </div>

      {isSelectorOpen && selectionConfig ? (
        <div
          className="fixed inset-0 z-[80] flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-[2px] sm:items-center"
          onClick={closeSelector}
        >
          <div
            className="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/30"
            onClick={(event) => event.stopPropagation()}
          >
            <div className="border-b border-slate-100 bg-gradient-to-r from-[#f5f9ff] to-white px-5 py-4">
              <div className="mb-2 flex items-start justify-between gap-3">
                <div>
                  <p className="text-[11px] font-extrabold tracking-[0.2em] text-[#2b84ea]">
                    BOOKING
                  </p>
                  <h3 className="mt-1 text-xl font-extrabold text-slate-900">
                    {selectionConfig.heading}
                  </h3>
                </div>
                <button
                  type="button"
                  onClick={closeSelector}
                  aria-label="Close selection popup"
                  className="rounded-full border border-slate-200 p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>
              <p className="text-sm text-slate-600">{selectionConfig.subheading}</p>
            </div>

            <div className="space-y-3 p-5">
              {selectionConfig.options.map((option) => {
                const isSelected = selectedPet === option.key;

                return (
                  <button
                    key={option.key}
                    type="button"
                    onClick={() => setSelectedPet(option.key)}
                    className={`flex w-full items-center gap-3 rounded-2xl border px-4 py-3 text-left transition-all ${
                      isSelected
                        ? "border-[#2b84ea] bg-[#edf5ff] shadow-sm"
                        : "border-slate-200 bg-white hover:border-slate-300"
                    }`}
                  >
                    <div className="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-[11px] font-extrabold text-slate-700">
                      {option.emoji}
                    </div>

                    <div className="flex-1">
                      <p className="text-sm font-bold text-slate-900">{option.label}</p>
                      <p className="text-xs text-slate-500">{option.desc}</p>
                    </div>

                    {isSelected ? (
                      <CheckCircle2 className="h-5 w-5 text-[#2b84ea]" />
                    ) : (
                      <span className="text-[11px] font-semibold text-slate-400">Select</span>
                    )}
                  </button>
                );
              })}

              <button
                type="button"
                onClick={handleContinue}
                disabled={!selectedPet}
                className="mt-1 w-full rounded-xl bg-[#2b84ea] px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-[#1f74d9] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
              >
                Continue
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}
