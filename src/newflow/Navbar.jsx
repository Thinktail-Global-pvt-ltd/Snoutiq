import React, { useState } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { Menu, X, Smartphone, Download } from "lucide-react";
import { Button } from "./NewButton";
import logo from "../assets/images/logo.webp";

export function Navbar({ consultPath = "/app-links" }) {
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  const handleAppDownload = (e) => {
    if (e) e.stopPropagation();
    const userAgent =
      navigator.userAgent || navigator.vendor || window.opera || "";
    const isIOS =
      /iPad|iPhone|iPod/i.test(userAgent) ||
      (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);

    const appStoreUrl =
      "https://apps.apple.com/in/app/snoutiq-pet-parent/id6761260254";
    const playStoreUrl =
      "https://play.google.com/store/apps/details?id=com.petai.snoutiq&hl=en_IN";

    const targetUrl = isIOS ? appStoreUrl : playStoreUrl;
    window.open(targetUrl, "_blank", "noopener,noreferrer");
  };

  const navLinks = [
    { name: "AI Symptom Checker", href: "/", isNew: true },
    // { name: "For Pet Parents", href: "/parents" },
    { name: "For Vets", href: "/vets" },
    { name: "For Clinics", href: "/clinics" },
    { name: "Blog", href: "/blog" },
    { name: "About Us", href: "/about" },
    //  { name: "ConsultFlow", href: "/counsltflow" },
  ];

  // Helper function to check if link is active
  const isActive = (href) => {
    if (href === "/") {
      return location.pathname === "/";
    }
    return location.pathname.startsWith(href);
  };

  const go = (to) => {
    if (/^https?:\/\//i.test(to)) {
      window.open(to, "_blank", "noopener,noreferrer");
      setIsOpen(false);
      return;
    }
    const target = String(to || "").trim();
    if (!target) return;

    // Normalize accidental relative paths to app-root paths.
    const normalizedTarget =
      target.startsWith("/") || target.startsWith("#") ? target : `/${target}`;

    // Force a full navigation for consult flows to avoid route-state glitches.
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
          to="/"
          className="flex items-center justify-center gap-2 hover:underline"
        >
          <span>🚀 New: AI Symptom Checker for Pet Parents - Try it now!</span>
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
                src={logo}
                alt="SnoutIQ"
                className="h-4 w-auto max-w-[110px] object-contain sm:h-6"
                width={110}
                height={20}
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

          {/* Desktop Navigation Links */}
          <div className="hidden md:block">
            <div className="ml-10 flex items-center space-x-8">
              {navLinks.map((link) => {
                const active = isActive(link.href);
                return (
                  <Link
                    key={link.name}
                    to={link.href}
                    className={`relative inline-flex h-10 items-center justify-center text-sm transition-all ${
                      active
                        ? "font-bold text-brand border-b-2 border-brand"
                        : "font-medium text-slate-700 hover:text-brand"
                    }`}
                  >
                    <span>{link.name}</span>
                    {link.isNew ? (
                      <span className="absolute -right-10 top-0 -translate-x-1/2 -translate-y-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-extrabold uppercase leading-none tracking-[0.12em] text-white shadow-sm">
                        New
                      </span>
                    ) : null}
                  </Link>
                );
              })}
            </div>
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

      {/* Mobile Navigation Drawer */}
      {isOpen && (
        <div className="border-b border-slate-200 bg-slate-50 md:hidden">
          <div className="space-y-1 px-2 pb-3 pt-2 sm:px-3">
            {navLinks.map((link) => {
              const active = isActive(link.href);
              return (
                <Link
                  key={link.name}
                  to={link.href}
                  className={`flex min-h-[3rem] items-center rounded-md px-3 py-1 text-base transition-all ${
                    active
                      ? "bg-brand/10 font-bold text-brand"
                      : "font-medium text-slate-700 hover:bg-slate-100 hover:text-brand"
                  }`}
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
              );
            })}
          </div>
        </div>
      )}

      {/* App Download Banner Bar */}
      <div
        onClick={handleAppDownload}
        className="cursor-pointer border-t border-slate-200/80 bg-slate-900 text-white transition-all hover:bg-slate-800"
      >
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 text-xs sm:px-6 sm:py-2.5 sm:text-sm lg:px-8">
          <div className="flex items-center gap-2 overflow-hidden">
            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
              <Smartphone className="h-3.5 w-3.5" />
            </span>
            <p className="truncate font-medium text-slate-200">
              <span className="font-bold text-white">Download SnoutIQ App:</span> 24/7 Expert Vet Guidance & Instant Pet Care on your phone
            </p>
          </div>
          <button
            type="button"
            onClick={handleAppDownload}
            className="group ml-3 inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-slate-950 shadow-sm transition-all hover:bg-emerald-400 hover:shadow-md"
          >
            <span>Download App</span>
            <Download className="h-3.5 w-3.5 transition-transform group-hover:translate-y-0.5" />
          </button>
        </div>
      </div>
    </nav>
  );
}

// Backward-compatible alias used by blog pages.
export const Header = Navbar;