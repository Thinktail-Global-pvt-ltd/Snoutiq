import React, { useEffect, useState } from "react";
import { usePwaInstall } from "../usePwaInstall";
import { isIos, isInStandaloneMode } from "../iosPwa";

const INSTALL_FLAG_KEY = "snoutiq_pwa_installed";

const readInstallFlag = () => {
  if (typeof window === "undefined") return false;
  try {
    return window.localStorage.getItem(INSTALL_FLAG_KEY) === "1";
  } catch {
    return false;
  }
};

const writeInstallFlag = () => {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(INSTALL_FLAG_KEY, "1");
  } catch {
    // ignore storage errors
  }
};

const usePwaInstalled = () => {
  const [hasInstalled, setHasInstalled] = useState(() => {
    if (typeof window === "undefined") return false;
    return isInStandaloneMode() || readInstallFlag();
  });

  useEffect(() => {
    if (typeof window === "undefined") return;

    const syncInstalled = () => {
      const standalone = isInStandaloneMode();
      if (standalone) writeInstallFlag();
      setHasInstalled(standalone || readInstallFlag());
    };

    syncInstalled();

    const onAppInstalled = () => {
      writeInstallFlag();
      setHasInstalled(true);
    };

    window.addEventListener("appinstalled", onAppInstalled);

    const mql = window.matchMedia("(display-mode: standalone)");
    const onChange = () => syncInstalled();
    if (mql?.addEventListener) {
      mql.addEventListener("change", onChange);
    } else if (mql?.addListener) {
      mql.addListener(onChange);
    }

    return () => {
      window.removeEventListener("appinstalled", onAppInstalled);
      if (mql?.removeEventListener) {
        mql.removeEventListener("change", onChange);
      } else if (mql?.removeListener) {
        mql.removeListener(onChange);
      }
    };
  }, []);

  const markInstalled = () => {
    writeInstallFlag();
    setHasInstalled(true);
  };

  return { hasInstalled, markInstalled };
};

export function InstallCTA({ className = "" }) {
  const { canInstall, promptInstall } = usePwaInstall();
  const { hasInstalled, markInstalled } = usePwaInstalled();

  if (hasInstalled) return null;

  const onClick = async () => {
    const isIosDevice = typeof window !== "undefined" && isIos();
    const isStandalone = typeof window !== "undefined" && isInStandaloneMode();
    if (isIosDevice && !isStandalone) {
      if (typeof navigator !== "undefined" && typeof navigator.share === "function") {
        try {
          await navigator.share({
            title: document.title,
            url: window.location.href,
          });
        } catch {
          // ignore share dismissal
        }
      } else {
        alert("iPhone par install ke liye: Share > Add to Home Screen");
      }
      return;
    }
    if (!canInstall) {
      alert("Install option browser criteria ke baad enable hota hai.");
      return;
    }
    const accepted = await promptInstall();
    if (accepted) markInstalled();
  };

  return (
    <button
      type="button"
      onClick={onClick}
      className={[
        "px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold",
        className,
      ]
        .filter(Boolean)
        .join(" ")}
    >
      Install App
    </button>
  );
}

export function IosInstallHint({ className = "" }) {
  const { hasInstalled } = usePwaInstalled();

  if (typeof window === "undefined") return null;
  if (!isIos() || isInStandaloneMode() || hasInstalled) return null;

  return (
    <div
      className={[
        "p-3 rounded-2xl border bg-white text-sm text-slate-700",
        className,
      ]
        .filter(Boolean)
        .join(" ")}
    >
      iPhone par install ke liye: <b>Share</b> &gt;{" "}
      <b>Add to Home Screen</b>
    </div>
  );
}
