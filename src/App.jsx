import React, { lazy, Suspense, useEffect, useRef } from "react";
import {
  BrowserRouter as Router,
  Navigate,
  Route,
  Routes,
  useLocation,
} from "react-router-dom";

const HOME_PATH = "/";
const VET_NEAR_ME_BASE_PATH = "/vet-at-home-gurgaon";
const VET_NEAR_ME_LEGACY_BASE_PATH = "/vet-near-me-delhi-ncr";
const NON_HOME_PRELOAD_EVENTS = ["pointerdown", "keydown", "touchstart"];
let homePagePromise;
let mainLayoutPromise;
let appRoutesPromise;
let vetNearMeBookingLayoutPromise;
let vetNearMeLeadPagePromise;
let vetNearMePetDetailsPagePromise;
let vetNearMePaymentPagePromise;
let vetNearMeSuccessPagePromise;

const ScrollToTopAndHash = () => {
  const { pathname, hash } = useLocation();
  const hasNavigatedRef = useRef(false);

  useEffect(() => {
    if (!hasNavigatedRef.current) {
      hasNavigatedRef.current = true;
      return;
    }

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
const loadMainLayout = () =>
  (mainLayoutPromise ??= import("./layouts/MainLayout"));
const loadAppRoutes = () => (appRoutesPromise ??= import("./AppRoutes"));
const loadVetNearMeBookingLayout = () =>
  (vetNearMeBookingLayoutPromise ??=
    import("./newflow/vetNearMeFlow/VetNearMeBookingLayout"));
const loadVetNearMeLeadPage = () =>
  (vetNearMeLeadPagePromise ??=
    import("./newflow/vetNearMeFlow/VetNearMeLeadPage"));
const loadVetNearMePetDetailsPage = () =>
  (vetNearMePetDetailsPagePromise ??=
    import("./newflow/vetNearMeFlow/VetNearMePetDetailsPage"));
const loadVetNearMePaymentPage = () =>
  (vetNearMePaymentPagePromise ??=
    import("./newflow/vetNearMeFlow/VetNearMePaymentPage"));
const loadVetNearMeSuccessPage = () =>
  (vetNearMeSuccessPagePromise ??=
    import("./newflow/vetNearMeFlow/VetNearMeSuccessPage"));

const normalizeVetNearMePath = (pathname = "") =>
  pathname.startsWith(VET_NEAR_ME_LEGACY_BASE_PATH)
    ? pathname.replace(VET_NEAR_ME_LEGACY_BASE_PATH, VET_NEAR_ME_BASE_PATH)
    : pathname;

const getVetNearMePageLoader = (pathname = "") => {
  if (pathname.startsWith(`${VET_NEAR_ME_BASE_PATH}/pet-details`)) {
    return loadVetNearMePetDetailsPage;
  }

  if (pathname.startsWith(`${VET_NEAR_ME_BASE_PATH}/payment`)) {
    return loadVetNearMePaymentPage;
  }

  if (pathname.startsWith(`${VET_NEAR_ME_BASE_PATH}/success`)) {
    return loadVetNearMeSuccessPage;
  }

  return loadVetNearMeLeadPage;
};

const lazyNamedExport = (loader, exportName = "default") =>
  lazy(() =>
    loader().then((module) => ({
      default: module[exportName] ?? module.default,
    }))
  );

if (typeof window !== "undefined") {
  const currentPath = window.location.pathname;

  if (currentPath === HOME_PATH) {
    void loadMainLayout();
    void loadHomePage();
  }

  if (
    currentPath.startsWith(VET_NEAR_ME_BASE_PATH) ||
    currentPath.startsWith(VET_NEAR_ME_LEGACY_BASE_PATH)
  ) {
    void loadVetNearMeBookingLayout();
    void getVetNearMePageLoader(normalizeVetNearMePath(currentPath))();
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
  } else if (
    !currentPath.startsWith(VET_NEAR_ME_BASE_PATH) &&
    !currentPath.startsWith(VET_NEAR_ME_LEGACY_BASE_PATH)
  ) {
    void loadMainLayout();
    preloadAppRoutes();
  }
}

const MainLayout = lazyNamedExport(loadMainLayout);
const HomePage = lazyNamedExport(loadHomePage);
const AppRoutes = lazyNamedExport(loadAppRoutes);
const VetNearMeBookingLayout = lazyNamedExport(loadVetNearMeBookingLayout);
const VetNearMeLeadPage = lazyNamedExport(loadVetNearMeLeadPage);
const VetNearMePetDetailsPage = lazyNamedExport(loadVetNearMePetDetailsPage);
const VetNearMePaymentPage = lazyNamedExport(loadVetNearMePaymentPage);
const VetNearMeSuccessPage = lazyNamedExport(loadVetNearMeSuccessPage);

function App() {
  return (
    <Router>
      <ScrollToTopAndHash />

      <Suspense fallback={<LoadingScreen />}>
        <Routes>
          <Route
            path={VET_NEAR_ME_LEGACY_BASE_PATH}
            element={<Navigate replace to={VET_NEAR_ME_BASE_PATH} />}
          />
          <Route
            path={`${VET_NEAR_ME_LEGACY_BASE_PATH}/pet-details`}
            element={
              <Navigate
                replace
                to={`${VET_NEAR_ME_BASE_PATH}/pet-details`}
              />
            }
          />
          <Route
            path={`${VET_NEAR_ME_LEGACY_BASE_PATH}/payment`}
            element={
              <Navigate replace to={`${VET_NEAR_ME_BASE_PATH}/payment`} />
            }
          />
          <Route
            path={`${VET_NEAR_ME_LEGACY_BASE_PATH}/success`}
            element={
              <Navigate replace to={`${VET_NEAR_ME_BASE_PATH}/success`} />
            }
          />

          <Route
            path={`${VET_NEAR_ME_BASE_PATH}`}
            element={<VetNearMeBookingLayout />}
          >
            <Route index element={<VetNearMeLeadPage />} />
            <Route
              path="pet-details"
              element={<VetNearMePetDetailsPage />}
            />
            <Route path="payment" element={<VetNearMePaymentPage />} />
            <Route path="success" element={<VetNearMeSuccessPage />} />
          </Route>

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
