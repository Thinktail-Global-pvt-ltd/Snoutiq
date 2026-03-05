import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Menu, X } from "lucide-react";
import { Button } from "./NewButton";
import logo from "../assets/images/logo.webp";

export function Navbar({ consultPath = "/20+vetsonline?start=details" }) {
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();

  const navLinks = [
    { name: "For Pet Parents", href: "/parents" },
    { name: "For Vets", href: "/vets" },
    { name: "For Clinics", href: "/clinics" },
    { name: "About Us", href: "/about" },
  ];

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
          to="/symptoms"
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
              aria-label="Open SnoutiQ website"
            >
              <img
                src={logo}
                alt="SnoutiQ"
                className="h-5 w-auto max-w-[130px] object-contain sm:h-6"
                loading="eager"
                draggable={false}
                onDragStart={(event) => event.preventDefault()}
                onContextMenu={(event) => event.preventDefault()}
              />
              <span className="sr-only">SnoutiQ</span>
            </a>
          </div>

          <div className="hidden md:block">
            <div className="ml-10 flex items-baseline space-x-8">
              {navLinks.map((link) => (
                <Link
                  key={link.name}
                  to={link.href}
                  className="text-sm font-medium text-slate-700 transition-colors hover:text-brand"
                >
                  {link.name}
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
                className="block rounded-md px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900"
                onClick={() => setIsOpen(false)}
              >
                {link.name}
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

// Backward-compatible alias used by blog pages.
export const Header = Navbar;
