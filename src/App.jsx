import React, { lazy, Suspense, useEffect } from "react";
import {
  BrowserRouter as Router,
  Route,
  Routes,
  useLocation,
} from "react-router-dom";
import MainLayout from "./layouts/MainLayout";

const HOME_PATH = "/";
const NON_HOME_PRELOAD_EVENTS = ["pointerdown", "keydown", "touchstart"];

const ScrollToTopAndHash = () => {
  const { pathname, hash } = useLocation();

  useEffect(() => {
    if (!hash) {
      window.scrollTo({ top: 0, behavior: "auto" });
      return;
    }

    const elementId = hash.replace("#", "");
    const scrollIntoView = () => {
      const target = document.getElementById(elementId);
      if (!target) return;
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    };

    requestAnimationFrame(scrollIntoView);
  }, [pathname, hash]);

  return null;
};

const LoadingScreen = () => (
  <div className="flex h-screen items-center justify-center">
    <div className="h-14 w-14 animate-spin rounded-full border-4 border-slate-200 border-t-brand" />
  </div>
);

const loadHomePage = () => import("./newflow/HomePage");
const loadAppRoutes = () => import("./AppRoutes");

if (typeof window !== "undefined") {
  const preloadAppRoutes = () => {
    NON_HOME_PRELOAD_EVENTS.forEach((eventName) => {
      window.removeEventListener(eventName, preloadAppRoutes);
    });
    void loadAppRoutes();
  };

  if (window.location.pathname === HOME_PATH) {
    NON_HOME_PRELOAD_EVENTS.forEach((eventName) => {
      window.addEventListener(eventName, preloadAppRoutes, {
        once: true,
        passive: true,
      });
    });
  } else {
    preloadAppRoutes();
  }
}

const HomePage = lazy(loadHomePage);
const AppRoutes = lazy(loadAppRoutes);

function App() {
  return (
    <Router>
      <ScrollToTopAndHash />

      <Suspense fallback={<LoadingScreen />}>
        <Routes>
          <Route element={<MainLayout />}>
            <Route path={HOME_PATH} element={<HomePage />} />
            <Route path="*" element={<AppRoutes />} />
          </Route>
        </Routes>
      </Suspense>
    </Router>
  );
}

export default App;
