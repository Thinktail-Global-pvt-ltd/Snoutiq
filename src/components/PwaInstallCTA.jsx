import React from "react";
import { usePwaInstall } from "../usePwaInstall";
import { isIos, isInStandaloneMode } from "../iosPwa";

export function InstallCTA({ className = "" }) {
  const { canInstall, promptInstall } = usePwaInstall();
  const isIosDevice = typeof window !== "undefined" && isIos();
  const isStandalone = typeof window !== "undefined" && isInStandaloneMode();

  const onClick = async () => {
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
    await promptInstall();
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
  if (typeof window === "undefined") return null;
  if (!isIos() || isInStandaloneMode()) return null;

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
