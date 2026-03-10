export function registerSW() {
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
      const register = () =>
        navigator.serviceWorker.register("/service-worker.js").catch(() => {});

      if ("requestIdleCallback" in window) {
        window.requestIdleCallback(register, { timeout: 2500 });
        return;
      }

      setTimeout(register, 1200);
    });
  }
}
