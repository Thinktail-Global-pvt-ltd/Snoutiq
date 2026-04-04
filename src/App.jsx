import React, { lazy, Suspense, useEffect } from "react";
import { HelmetProvider } from "react-helmet-async";
import {
  BrowserRouter as Router,
  Route,
  Routes,
  useLocation,
} from "react-router-dom";
import MainLayout from "./layouts/MainLayout";

const HOME_PATH = "/";
const VET_NEAR_ME_BASE_PATH = "/vet-near-me-delhi-ncr";
const NON_HOME_PRELOAD_EVENTS = ["pointerdown", "keydown", "touchstart"];
let homePagePromise;
let appRoutesPromise;
let vetNearMeLayoutPromise;
let vetNearMeLeadPagePromise;
let vetNearMePetDetailsPagePromise;
let vetNearMePaymentPagePromise;
let vetNearMeSuccessPagePromise;

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

const loadHomePage = () =>
  (homePagePromise ??= import("./newflow/HomePage"));
const loadAppRoutes = () => (appRoutesPromise ??= import("./AppRoutes"));
const loadVetNearMeLayout = () =>
  (vetNearMeLayoutPromise ??= import("./newflow/vetNearMeFlow/VetNearMeBookingLayout"));
const loadVetNearMeLeadPage = () =>
  (vetNearMeLeadPagePromise ??= import("./newflow/vetNearMeFlow/VetNearMeLeadPage"));
const loadVetNearMePetDetailsPage = () =>
  (vetNearMePetDetailsPagePromise ??= import("./newflow/vetNearMeFlow/VetNearMePetDetailsPage"));
const loadVetNearMePaymentPage = () =>
  (vetNearMePaymentPagePromise ??= import("./newflow/vetNearMeFlow/VetNearMePaymentPage"));
const loadVetNearMeSuccessPage = () =>
  (vetNearMeSuccessPagePromise ??= import("./newflow/vetNearMeFlow/VetNearMeSuccessPage"));

if (typeof window !== "undefined") {
  const currentPath = window.location.pathname;

  if (currentPath === HOME_PATH) {
    void loadHomePage();
  }

  if (currentPath.startsWith(VET_NEAR_ME_BASE_PATH)) {
    void loadVetNearMeLayout();

    if (
      currentPath === VET_NEAR_ME_BASE_PATH ||
      currentPath === `${VET_NEAR_ME_BASE_PATH}/`
    ) {
      void loadVetNearMeLeadPage();
    } else if (currentPath === `${VET_NEAR_ME_BASE_PATH}/pet-details`) {
      void loadVetNearMePetDetailsPage();
    } else if (currentPath === `${VET_NEAR_ME_BASE_PATH}/payment`) {
      void loadVetNearMePaymentPage();
    } else if (currentPath === `${VET_NEAR_ME_BASE_PATH}/success`) {
      void loadVetNearMeSuccessPage();
    }
  }

  const preloadAppRoutes = () => {
    NON_HOME_PRELOAD_EVENTS.forEach((eventName) => {
      window.removeEventListener(eventName, preloadAppRoutes);
    });
    void loadAppRoutes();
  };

  if (currentPath === HOME_PATH) {
    NON_HOME_PRELOAD_EVENTS.forEach((eventName) => {
      window.addEventListener(eventName, preloadAppRoutes, {
        once: true,
        passive: true,
      });
    });
  } else if (!currentPath.startsWith(VET_NEAR_ME_BASE_PATH)) {
    preloadAppRoutes();
  }
}

const BookingFlowShell = ({ children }) => (
  <HelmetProvider>{children}</HelmetProvider>
);

const HomePage = lazy(loadHomePage);
const AppRoutes = lazy(loadAppRoutes);
const VetNearMeBookingLayout = lazy(loadVetNearMeLayout);
const VetNearMeLeadPage = lazy(loadVetNearMeLeadPage);
const VetNearMePetDetailsPage = lazy(loadVetNearMePetDetailsPage);
const VetNearMePaymentPage = lazy(loadVetNearMePaymentPage);
const VetNearMeSuccessPage = lazy(loadVetNearMeSuccessPage);

function App() {
  return (
    <Router>
      <ScrollToTopAndHash />

      <Suspense fallback={<LoadingScreen />}>
        <Routes>
          <Route element={<MainLayout />}>
            <Route path={HOME_PATH} element={<HomePage />} />
            <Route
              path={`${VET_NEAR_ME_BASE_PATH}`}
              element={
                <BookingFlowShell>
                  <VetNearMeBookingLayout />
                </BookingFlowShell>
              }
            >
              <Route index element={<VetNearMeLeadPage />} />
              <Route
                path="pet-details"
                element={<VetNearMePetDetailsPage />}
              />
              <Route path="payment" element={<VetNearMePaymentPage />} />
              <Route path="success" element={<VetNearMeSuccessPage />} />
            </Route>
            <Route path="*" element={<AppRoutes />} />
          </Route>
        </Routes>
      </Suspense>
    </Router>
  );
}

export default App;
