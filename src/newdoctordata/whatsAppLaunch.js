const normalizeText = (value) => String(value ?? "").trim();

const isProbablyMobileDevice = () => {
  if (typeof navigator === "undefined") {
    return false;
  }

  const userAgent = navigator.userAgent || "";
  return /android|iphone|ipad|ipod|mobile/i.test(userAgent);
};

const buildWhatsAppLaunchTargets = (rawUrl) => {
  const webUrl = normalizeText(rawUrl);
  if (!webUrl) {
    return null;
  }

  try {
    const parsed = new URL(webUrl);
    const isWaMeHost = /(^|\.)wa\.me$/i.test(parsed.hostname);
    const phone = isWaMeHost
      ? parsed.pathname.replace(/^\/+/, "").split("/")[0] || ""
      : normalizeText(parsed.searchParams.get("phone"));
    const text = normalizeText(parsed.searchParams.get("text"));

    if (!phone && !text) {
      return { webUrl, appUrl: "" };
    }

    const params = new URLSearchParams();
    if (phone) {
      params.set("phone", phone);
    }
    if (text) {
      params.set("text", text);
    }

    return {
      webUrl,
      appUrl: `whatsapp://send?${params.toString()}`,
    };
  } catch {
    return { webUrl, appUrl: "" };
  }
};

export const openWhatsAppLaunchUrl = (rawUrl) => {
  const targets = buildWhatsAppLaunchTargets(rawUrl);
  if (!targets?.webUrl || typeof window === "undefined") {
    return false;
  }

  if (!targets.appUrl || !isProbablyMobileDevice() || typeof document === "undefined") {
    window.open(targets.webUrl, "_blank", "noopener,noreferrer");
    return true;
  }

  let fallbackTimerId = null;

  const cleanup = () => {
    if (fallbackTimerId !== null) {
      window.clearTimeout(fallbackTimerId);
      fallbackTimerId = null;
    }

    document.removeEventListener("visibilitychange", handleVisibilityChange);
    window.removeEventListener("pagehide", cleanup);
    window.removeEventListener("blur", cleanup);
  };

  const handleVisibilityChange = () => {
    if (document.visibilityState === "hidden") {
      cleanup();
    }
  };

  document.addEventListener("visibilitychange", handleVisibilityChange);
  window.addEventListener("pagehide", cleanup, { once: true });
  window.addEventListener("blur", cleanup, { once: true });

  fallbackTimerId = window.setTimeout(() => {
    if (document.visibilityState === "visible") {
      window.location.assign(targets.webUrl);
    }
    cleanup();
  }, 900);

  window.location.assign(targets.appUrl);
  return true;
};
