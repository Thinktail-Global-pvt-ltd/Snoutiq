export function isIos() {
  return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
}

export function isInStandaloneMode() {
  return (
    window.matchMedia("(display-mode: standalone)").matches ||
    window.navigator.standalone
  );
}
