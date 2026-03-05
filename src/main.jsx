import React from 'react';
import ReactDOM from 'react-dom/client';
import { HelmetProvider } from 'react-helmet-async';
import App from './App';
import './index.css';
import { registerSW } from './pwaRegister';

const normalizePath = (pathname) => {
  if (!pathname) return "/";
  const trimmed = pathname.replace(/\/+$/, "");
  return trimmed || "/";
};

const prefetchRouteChunk = (pathname) => {
  const normalized = normalizePath(pathname);

  if (normalized === "/") {
    void import("./newflow/HomePage");
    return;
  }

  if (normalized === "/online-vet-consultation-india") {
    void import("./newflow/VideoConsultLP");
    return;
  }

  if (normalized === "/veterinary-doctor-online-india") {
    void import("./newflow/NewVideoConsultationLP");
  }
};

if (typeof window !== "undefined") {
  prefetchRouteChunk(window.location.pathname);
}

// React 18 root API
registerSW();
const root = ReactDOM.createRoot(document.getElementById('root'));

root.render(
  <React.StrictMode>
    <HelmetProvider>
      <App />
    </HelmetProvider>
  </React.StrictMode>
);
