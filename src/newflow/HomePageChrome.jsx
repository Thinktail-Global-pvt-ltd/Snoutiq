import React, {
  forwardRef,
  useEffect,
  useMemo,
  useState,
} from "react";
import { Link, useNavigate } from "react-router-dom";

const HOME_LOGO_SRC = "/logo.webp";
const CERTIFICATION_PDF = "/Startup-certificate.pdf";
const DPIIT_LOGO_SRC = "/DPIIT.jpeg";
const MSME_LOGO_SRC = "/MSME.jpeg";

const cx = (...classes) => classes.filter(Boolean).join(" ");

const createIcon = (displayName, children, viewBox = "0 0 24 24") => {
  const Icon = forwardRef(({ className, ...props }, ref) => (
    <svg
      ref={ref}
      viewBox={viewBox}
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
      aria-hidden="true"
      {...props}
    >
      {children}
    </svg>
  ));

  Icon.displayName = displayName;
  return Icon;
};

export const ArrowRight = createIcon("ArrowRight", [
  <path key="line" d="M5 12h14" />,
  <path key="tip" d="m12 5 7 7-7 7" />,
]);

export const CalendarDays = createIcon("CalendarDays", [
  <path key="frame" d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" />,
  <path key="head" d="M16 2v4M8 2v4M4 10h16" />,
  <path key="day-1" d="M8 14h.01" />,
  <path key="day-2" d="M12 14h.01" />,
  <path key="day-3" d="M16 14h.01" />,
  <path key="day-4" d="M8 18h.01" />,
  <path key="day-5" d="M12 18h.01" />,
]);

export const ChevronDown = createIcon("ChevronDown", [
  <path key="line" d="m6 9 6 6 6-6" />,
]);

export const FileText = createIcon("FileText", [
  <path key="file" d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z" />,
  <path key="fold" d="M14 2v5h5" />,
  <path key="line-1" d="M9 13h6" />,
  <path key="line-2" d="M9 17h6" />,
  <path key="line-3" d="M9 9h1" />,
]);

export const HeartPulse = createIcon("HeartPulse", [
  <path key="heart" d="M19.5 12.6 12 20l-7.5-7.4a4.8 4.8 0 0 1 6.8-6.8L12 6.5l.7-.7a4.8 4.8 0 0 1 6.8 6.8Z" />,
  <path key="pulse" d="M3 12h3l2 3 3-6 2 3h8" />,
]);

export const MapPin = createIcon("MapPin", [
  <path key="pin" d="M12 21s-6-5.3-6-11a6 6 0 1 1 12 0c0 5.7-6 11-6 11Z" />,
  <circle key="dot" cx="12" cy="10" r="2.5" />,
]);

export const PawPrint = createIcon("PawPrint", [
  <ellipse key="toe-1" cx="8.5" cy="7" rx="1.6" ry="2.1" />,
  <ellipse key="toe-2" cx="15.5" cy="7" rx="1.6" ry="2.1" />,
  <ellipse key="toe-3" cx="6" cy="12" rx="1.7" ry="2.2" />,
  <ellipse key="toe-4" cx="18" cy="12" rx="1.7" ry="2.2" />,
  <path key="pad" d="M12 20c-2.8 0-5-1.7-5-3.9 0-2 1.9-3.7 4.5-4.2.3-.1.7-.1 1 0 2.6.5 4.5 2.2 4.5 4.2 0 2.2-2.2 3.9-5 3.9Z" />,
]);

export const Scissors = createIcon("Scissors", [
  <circle key="left-eye" cx="6" cy="6" r="2.5" />,
  <circle key="right-eye" cx="6" cy="18" r="2.5" />,
  <path key="blade-1" d="M20 4 8.1 15.9" />,
  <path key="blade-2" d="m8.1 8.1 11.8 11.8" />,
]);

export const ShieldCheck = createIcon("ShieldCheck", [
  <path key="shield" d="M12 3 5 6v5c0 5 3.4 9.6 7 10 3.6-.4 7-5 7-10V6Z" />,
  <path key="check" d="m9.5 12 1.7 1.7 3.3-3.7" />,
]);

export const Smartphone = createIcon("Smartphone", [
  <rect key="phone" x="7" y="2" width="10" height="20" rx="2" />,
  <path key="top" d="M11 5h2" />,
  <path key="bottom" d="M12 18h.01" />,
]);

export const Stethoscope = createIcon("Stethoscope", [
  <path key="tube-1" d="M6 3v7a4 4 0 1 0 8 0V3" />,
  <path key="tube-2" d="M10 14v2a4 4 0 0 0 8 0v-1" />,
  <circle key="chest" cx="18" cy="15" r="2" />,
]);

export const Syringe = createIcon("Syringe", [
  <path key="body" d="m14 4 6 6" />,
  <path key="shaft" d="m6 18 8-8 4 4-8 8Z" />,
  <path key="needle" d="m2 22 4-4" />,
  <path key="plunger" d="m15 3 3-3" />,
  <path key="marks" d="m9 15 4 4" />,
]);

export const Video = createIcon("Video", [
  <rect key="screen" x="3" y="6" width="13" height="12" rx="2" />,
  <path key="cam" d="m16 10 5-3v10l-5-3Z" />,
]);

const Menu = createIcon("Menu", [
  <path key="line-1" d="M4 6h16" />,
  <path key="line-2" d="M4 12h16" />,
  <path key="line-3" d="M4 18h16" />,
]);

const X = createIcon("X", [
  <path key="line-1" d="M18 6 6 18" />,
  <path key="line-2" d="m6 6 12 12" />,
]);

const CheckCircle2 = createIcon("CheckCircle2", [
  <circle key="circle" cx="12" cy="12" r="9" />,
  <path key="check" d="m9 12 2 2 4-4" />,
]);

export const Button = forwardRef(function HomePageButton(
  { className, variant = "primary", size = "md", ...props },
  ref,
) {
  const baseStyles =
    "inline-flex items-center justify-center rounded-full font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand disabled:pointer-events-none disabled:opacity-50";

  const variants = {
    primary: "bg-accent text-slate-900 hover:bg-accent-hover",
    brand: "bg-brand text-slate-900 hover:bg-brand-dark",
    secondary: "bg-slate-50 text-slate-900 hover:bg-slate-100",
    outline: "border border-brand text-brand hover:bg-brand-light",
    ghost: "text-slate-900 hover:bg-slate-100",
  };

  const sizes = {
    sm: "h-12 px-4 text-sm md:h-10",
    md: "h-12 px-6 text-base",
    lg: "h-14 px-8 text-lg",
  };

  return (
    <button
      ref={ref}
      className={cx(baseStyles, variants[variant], sizes[size], className)}
      {...props}
    />
  );
});

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
          {(features || []).map((feature, index) => (
            <li key={`${feature}-${index}`} className="flex items-start gap-2">
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
                    className={cx(
                      "flex w-full items-center gap-3 rounded-2xl border px-4 py-3 text-left transition-all",
                      isSelected
                        ? "border-[#2b84ea] bg-[#edf5ff] shadow-sm"
                        : "border-slate-200 bg-white hover:border-slate-300",
                    )}
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
                      <span className="text-[11px] font-semibold text-slate-400">
                        Select
                      </span>
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

export function Navbar({ consultPath = "/20+vetsonline?start=details" }) {
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();

  const navLinks = [
    { name: "For Pet Parents", href: "/parents" },
    { name: "For Vets", href: "/vets" },
    { name: "For Clinics", href: "/clinics" },
    { name: "About Us", href: "/about" },
    { name: "AI Symptom Checker", href: "/ask", isNew: true },
  ];

  const go = (to) => {
    if (/^https?:\/\//i.test(to)) {
      window.open(to, "_blank", "noopener,noreferrer");
      setIsOpen(false);
      return;
    }

    const target = String(to || "").trim();
    if (!target) return;

    const normalizedTarget =
      target.startsWith("/") || target.startsWith("#") ? target : `/${target}`;

    if (normalizedTarget.includes("start=details")) {
      window.location.assign(normalizedTarget);
      setIsOpen(false);
      return;
    }

    if (normalizedTarget.startsWith("#")) {
      navigate(normalizedTarget);
      setIsOpen(false);
      return;
    }

    const [pathnamePart, queryPart = ""] = normalizedTarget.split("?");
    navigate({
      pathname: pathnamePart || "/",
      search: queryPart ? `?${queryPart}` : "",
    });
    setIsOpen(false);
  };

  return (
    <nav className="sticky top-0 z-50 w-full border-b border-brand/20 bg-white/80 backdrop-blur-md">
      <div className="bg-brand px-4 py-2 text-center text-sm font-bold text-slate-900">
        <Link
          to="/ask"
          className="flex items-center justify-center gap-2 hover:underline"
        >
          <span>New: AI Symptom Checker for Pet Parents - Try it now!</span>
        </Link>
      </div>

      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-16 items-center justify-between">
          <div className="flex items-center">
            <a
              href="https://www.snoutiq.com"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2"
              aria-label="Open SnoutIQ website"
            >
              <img
                src={HOME_LOGO_SRC}
                alt="SnoutIQ"
                className="h-5 w-auto max-w-[130px] object-contain sm:h-6"
                width={130}
                height={24}
                loading="eager"
                decoding="async"
                fetchpriority="high"
                draggable={false}
                onDragStart={(event) => event.preventDefault()}
                onContextMenu={(event) => event.preventDefault()}
              />
              <span className="sr-only">SnoutIQ</span>
            </a>
          </div>

          <div className="hidden md:block">
            <div className="ml-10 flex items-center space-x-8">
              {navLinks.map((link) => (
                <Link
                  key={link.name}
                  to={link.href}
                  className="relative inline-flex h-10 items-center justify-center text-sm font-medium text-slate-700 transition-colors hover:text-brand"
                >
                  <span>{link.name}</span>
                  {link.isNew ? (
                    <span className="absolute -right-10 top-0 -translate-x-1/2 -translate-y-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-extrabold uppercase leading-none tracking-[0.12em] text-white shadow-sm">
                      New
                    </span>
                  ) : null}
                </Link>
              ))}
            </div>
          </div>

          <div className="hidden md:block">
            <Button
              variant="primary"
              size="sm"
              onClick={() => go(consultPath)}
              type="button"
            >
              Consult Now
            </Button>
          </div>

          <div className="-mr-2 flex md:hidden">
            <button
              type="button"
              onClick={() => setIsOpen((value) => !value)}
              className="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none"
              aria-label={isOpen ? "Close main menu" : "Open main menu"}
              aria-expanded={isOpen}
            >
              {isOpen ? (
                <X className="block h-6 w-6" />
              ) : (
                <Menu className="block h-6 w-6" />
              )}
            </button>
          </div>
        </div>
      </div>

      {isOpen && (
        <div className="border-b border-slate-200 bg-slate-50 md:hidden">
          <div className="space-y-1 px-2 pb-3 pt-2 sm:px-3">
            {navLinks.map((link) => (
              <Link
                key={link.name}
                to={link.href}
                className="flex min-h-[3rem] items-center rounded-md px-3 py-1 text-base font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900"
                onClick={() => setIsOpen(false)}
              >
                <span className="relative inline-flex h-10 items-center">
                  <span>{link.name}</span>
                  {link.isNew ? (
                    <span className="absolute -right-10 top-0 -translate-x-1/2 -translate-y-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-extrabold uppercase leading-none tracking-[0.12em] text-white shadow-sm">
                      New
                    </span>
                  ) : null}
                </span>
              </Link>
            ))}

            <div className="px-3 py-2">
              <Button
                variant="primary"
                className="w-full"
                onClick={() => go(consultPath)}
                type="button"
              >
                Consult Now
              </Button>
            </div>
          </div>
        </div>
      )}
    </nav>
  );
}



export function Footer() {
  return (
    <footer className="border-t border-slate-200 bg-white py-10 text-slate-600 sm:py-12">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-8 md:grid-cols-5 md:items-start">
          {/* 1. Brand */}
          <div>
            <a
              href="https://www.snoutiq.com"
              target="_blank"
              rel="noopener noreferrer"
              className="mb-4 flex items-center gap-2"
              aria-label="Open SnoutIQ website"
            >
              <img
                src={HOME_LOGO_SRC}
                alt="SnoutIQ"
                className="h-5 w-auto max-w-[130px] object-contain sm:h-6"
                width={130}
                height={24}
                loading="lazy"
                decoding="async"
                draggable={false}
                onDragStart={(event) => event.preventDefault()}
                onContextMenu={(event) => event.preventDefault()}
              />
              <span className="sr-only">SnoutIQ</span>
            </a>

            <p className="mb-4 text-sm">
              India&apos;s trusted pet healthcare platform.
            </p>

            <div className="flex gap-4">
              <a
                href="#"
                className="text-slate-400 transition-colors hover:text-brand"
                aria-label="Facebook"
              >
                <span className="sr-only">Facebook</span>
                <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22 12c0-5.5-4.5-10-10-10S2 6.5 2 12c0 5 3.7 9.1 8.4 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7C18.3 21.1 22 17 22 12Z" />
                </svg>
              </a>

              <a
                href="#"
                className="text-slate-400 transition-colors hover:text-brand"
                aria-label="Instagram"
              >
                <span className="sr-only">Instagram</span>
                <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3Zm5 2.8A5.2 5.2 0 1 1 6.8 12 5.2 5.2 0 0 1 12 6.8Zm0 2A3.2 3.2 0 1 0 15.2 12 3.2 3.2 0 0 0 12 8.8Zm5.5-3.4a1.1 1.1 0 1 1-1.1 1.1 1.1 1.1 0 0 1 1.1-1.1Z" />
                </svg>
              </a>
            </div>
          </div>

          {/* 2. Services */}
          <div>
            <h3 className="mb-4 font-semibold text-slate-900">Services</h3>
            <ul className="space-y-2 text-sm">
              <li>
                <Link
                  to="/veterinary-doctor-online-india"
                  className="transition-colors hover:text-brand"
                >
                  Video Consultation
                </Link>
              </li>
              <li>
                <Link
                  to="/puppy-vaccination-delhi"
                  className="transition-colors hover:text-brand"
                >
                  Puppy Vaccination
                </Link>
              </li>
              <li>
                <Link
                  to="/kitten-vaccination-delhi"
                  className="transition-colors hover:text-brand"
                >
                  Kitten Vaccination
                </Link>
              </li>
              <li>
                <Link
                  to="/dog-neutering-delhi"
                  className="transition-colors hover:text-brand"
                >
                  Dog Neutering
                </Link>
              </li>
              <li>
                <Link
                  to="/cat-neutering-delhi"
                  className="transition-colors hover:text-brand"
                >
                  Cat Neutering
                </Link>
              </li>
            </ul>
          </div>

          {/* 3. Resources */}
          <div>
            <h3 className="mb-4 font-semibold text-slate-900">Resources</h3>
            <ul className="space-y-2 text-sm">
              <li>
                <Link to="/ask" className="transition-colors hover:text-brand">
                  Symptom Checker
                </Link>
              </li>
              <li>
                <Link
                  to="/vet-insights"
                  className="transition-colors hover:text-brand"
                >
                  Vet Insights
                </Link>
              </li>
              <li>
                <Link to="/blog" className="transition-colors hover:text-brand">
                  Blog
                </Link>
              </li>
              <li>
                <Link
                  to="/vet-insights/interview-dr-sharma-emergency-care"
                  className="transition-colors hover:text-brand"
                >
                  Emergency Care Guide
                </Link>
              </li>
              <li>
                <Link
                  to="/dog-vomiting-treatment-india"
                  className="transition-colors hover:text-brand"
                >
                  Dog Vomiting Guide
                </Link>
              </li>
            </ul>
          </div>

          {/* 4. Company */}
          <div>
            <h3 className="mb-4 font-semibold text-slate-900">Company</h3>
            <ul className="space-y-2 text-sm">
              <li>
                <Link to="/about" className="transition-colors hover:text-brand">
                  About Us
                </Link>
              </li>
              <li>
                <Link
                  to="/privacy-policy"
                  className="transition-colors hover:text-brand"
                >
                  Privacy Policy
                </Link>
              </li>
              <li>
                <Link
                  to="/terms-of-service"
                  className="transition-colors hover:text-brand"
                >
                  Terms
                </Link>
              </li>
              <li className="pt-2 leading-relaxed">
                Email:{" "}
                <a
                  href="mailto:snoutiq@gmail.com"
                  className="text-blue-600 underline hover:text-blue-800"
                >
                  snoutiq@gmail.com
                </a>{" "}
                or{" "}
                <a
                  href="mailto:info@snoutiq.com"
                  className="text-blue-600 underline hover:text-blue-800"
                >
                  info@snoutiq.com
                </a>
              </li>
            </ul>
          </div>

          {/* 5. Certifications */}
          <div>
            <div className="rounded-2xl bg-[#2450D6] p-5 text-white shadow-sm">
              <h3 className="mb-5 text-center text-[18px] font-semibold leading-tight">
                We Are Certified By
              </h3>

              <div className="grid grid-cols-2 gap-4">
                <a
                  href={CERTIFICATION_PDF}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex flex-col items-center text-center transition-transform hover:-translate-y-0.5"
                  aria-label="Open Startup India certificate PDF"
                >
                  <div className="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm">
                    <img
                      src={DPIIT_LOGO_SRC}
                      alt="Startup India"
                      className="h-10 w-10 object-contain"
                      loading="lazy"
                      draggable={false}
                      onDragStart={(event) => event.preventDefault()}
                      onContextMenu={(event) => event.preventDefault()}
                    />
                  </div>
                  <span className="mt-3 text-sm font-semibold leading-5">
                    Startup India
                  </span>
                </a>

                <a
                  href={CERTIFICATION_PDF}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex flex-col items-center text-center transition-transform hover:-translate-y-0.5"
                  aria-label="Open MSME certificate PDF"
                >
                  <div className="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm">
                    <img
                      src={MSME_LOGO_SRC}
                      alt="MSME"
                      className="h-10 w-10 object-contain"
                      loading="lazy"
                      draggable={false}
                      onDragStart={(event) => event.preventDefault()}
                      onContextMenu={(event) => event.preventDefault()}
                    />
                  </div>
                  <span className="mt-3 text-sm font-semibold leading-5">
                    MSME
                  </span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div className="mt-10 flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-6 text-center text-sm md:flex-row">
          <p>&copy; {new Date().getFullYear()} SnoutIQ. All rights reserved.</p>
          <p>Made with care for pet parents across India</p>
        </div>
      </div>
    </footer>
  );
}
