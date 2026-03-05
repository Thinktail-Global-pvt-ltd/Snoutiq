import React, { useEffect } from "react";
import { Outlet } from "react-router-dom";

const DEFAULT_TITLE = "SnoutiQ - Pet Healthcare Platform India";
const DEFAULT_DESCRIPTION =
  "India's trusted pet healthcare platform. Online vet consultations, puppy & kitten vaccination packages, and neuter/spay services in Delhi NCR.";
const DEFAULT_KEYWORDS =
  "online vet consultation India, pet healthcare India, puppy vaccination Delhi, dog neutering Delhi NCR";
const DEFAULT_URL = "https://snoutiq.com";
const GOOGLE_VERIFICATION = "ADD_GOOGLE_SEARCH_CONSOLE_CODE_HERE";

const upsertMetaTag = (attribute, key, content) => {
  let tag = document.querySelector(`meta[${attribute}="${key}"]`);

  if (!tag) {
    tag = document.createElement("meta");
    tag.setAttribute(attribute, key);
    document.head.appendChild(tag);
  }

  tag.setAttribute("content", content);
};

export default function MainLayout({ children }) {
  useEffect(() => {
    document.documentElement.lang = "en";

    if (!document.title || document.title === "Vite + React") {
      document.title = DEFAULT_TITLE;
    }

    upsertMetaTag("name", "description", DEFAULT_DESCRIPTION);
    upsertMetaTag("name", "keywords", DEFAULT_KEYWORDS);
    upsertMetaTag("property", "og:type", "website");
    upsertMetaTag("property", "og:locale", "en_IN");
    upsertMetaTag("property", "og:url", DEFAULT_URL);
    upsertMetaTag("property", "og:site_name", "SnoutiQ");
    upsertMetaTag("name", "twitter:card", "summary_large_image");
    upsertMetaTag("name", "google-site-verification", GOOGLE_VERIFICATION);

    let canonical = document.querySelector("link[rel='canonical']");
    if (!canonical) {
      canonical = document.createElement("link");
      canonical.setAttribute("rel", "canonical");
      document.head.appendChild(canonical);
    }
    canonical.setAttribute("href", DEFAULT_URL);
  }, []);

  useEffect(() => {
    const gtmId = import.meta.env.VITE_GTM_ID;
    if (!gtmId) return;

    const hasGtmScript = document.querySelector(
      `script[src*="googletagmanager.com/gtm.js?id=${gtmId}"]`
    );
    if (hasGtmScript) return;

    const firstInteractionEvents = ["pointerdown", "keydown", "touchstart"];
    let loaded = false;

    const loadGtm = () => {
      if (loaded) return;
      loaded = true;

      const alreadyLoaded = document.querySelector(
        `script[src*="googletagmanager.com/gtm.js?id=${gtmId}"]`
      );
      if (alreadyLoaded) return;

      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        "gtm.start": new Date().getTime(),
        event: "gtm.js",
      });

      const script = document.createElement("script");
      script.async = true;
      script.src = `https://www.googletagmanager.com/gtm.js?id=${gtmId}`;
      script.setAttribute("data-gtm-id", gtmId);
      document.head.appendChild(script);
    };

    firstInteractionEvents.forEach((eventName) => {
      window.addEventListener(eventName, loadGtm, {
        once: true,
        passive: true,
      });
    });

    return () => {
      firstInteractionEvents.forEach((eventName) => {
        window.removeEventListener(eventName, loadGtm);
      });
    };
  }, []);

  return (
    <div className="min-h-screen bg-white text-slate-900 font-sans antialiased">
      {children ?? <Outlet />}
    </div>
  );
}

