import React, { useEffect, useMemo, useState, lazy, Suspense } from "react";
import { useNavigate } from "react-router-dom";

// Lazy screens (default exports)
const LandingScreen = lazy(() => import("../screen/Landingscreen"));
const PetDetailsScreen = lazy(() => import("../screen/Petdetailsscreen"));

// Payment has named exports
const PaymentScreen = lazy(() =>
  import("../screen/Paymentscreen").then((m) => ({ default: m.PaymentScreen }))
);
const ConfirmationScreen = lazy(() =>
  import("../screen/Paymentscreen").then((m) => ({
    default: m.ConfirmationScreen,
  }))
);

// Vet screens are named exports from ../components/VetScreens
const VetLoginScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({ default: m.VetLoginScreen }))
);
const VetRegisterScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetRegisterScreen,
  }))
);
const VetPendingScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetPendingScreen,
  }))
);
const VetDashboardScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetDashboardScreen,
  }))
);

// Small professional loader
const LoadingScreen = () => (
  <div className="min-h-screen w-full flex items-center justify-center bg-white">
    <div className="flex flex-col items-center gap-3">
      <div className="w-10 h-10 rounded-full border-4 border-stone-200 border-t-blue-600 animate-spin" />
      <p className="text-sm text-stone-500 font-medium">Loading...</p>
    </div>
  </div>
);

const Home = () => {
  const navigate = useNavigate();
  const [screen, setScreen] = useState("landing");
  const [petDetails, setPetDetails] = useState(null);
  const [selectedVet, setSelectedVet] = useState(null);

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [screen]);

  // One goBack map instead of many ifs
  const backMap = useMemo(
    () => ({
      details: "landing",
      payment: "details",
      confirmation: "payment",

      "vet-login": "landing",
      "vet-register": "vet-login",
      "vet-pending": "vet-register",
      "vet-dashboard": "vet-login",
    }),
    []
  );

  const goBack = () => {
    const prev = backMap[screen];
    if (prev) setScreen(prev);
  };

  // (Optional) cleanup state when jumping back
  useEffect(() => {
    if (screen === "landing") {
      setPetDetails(null);
      setSelectedVet(null);
    }
  }, [screen]);

  // Render routes in a clean map
  const content = useMemo(() => {
    switch (screen) {
      case "landing":
        return (
          <LandingScreen
            onVetAccess={() => navigate("/auth")}
            onSelectVet={(vet) => {
              setSelectedVet(vet);
              setScreen("details");
            }}
          />
        );

      case "details":
        return (
          <PetDetailsScreen
            onBack={goBack}
            onSubmit={(details) => {
              setPetDetails(details);
              setScreen("payment");
            }}
          />
        );

      case "payment":
        return selectedVet ? (
          <PaymentScreen
            vet={selectedVet}
            petDetails={petDetails}
            onBack={goBack}
            onPay={() => {
              setTimeout(() => {
                navigate("/consultation-booked", { state: { vet: selectedVet } });
              }, 1200);
            }}
          />
        ) : (
          <LoadingScreen />
        );

      case "confirmation":
        return selectedVet ? (
          <ConfirmationScreen vet={selectedVet} />
        ) : (
          <LoadingScreen />
        );

      // Vet flows
      case "vet-login":
        return (
          <VetLoginScreen
            onLogin={(payload) => {
              setScreen("vet-dashboard");
              navigate("/vet-dashboard", { state: { auth: payload } });
            }}
            onRegisterClick={() => setScreen("vet-register")}
            onBack={goBack}
          />
        );

      case "vet-register":
        return (
          <VetRegisterScreen onSubmit={() => setScreen("vet-login")} onBack={goBack} />
        );

      case "vet-pending":
        return <VetPendingScreen onHome={() => setScreen("landing")} />;

      case "vet-dashboard":
        return <VetDashboardScreen onLogout={() => setScreen("landing")} />;

      default:
        return (
          <LandingScreen
            onVetAccess={() => navigate("/auth")}
            onSelectVet={(vet) => {
              setSelectedVet(vet);
              setScreen("details");
            }}
          />
        );
    }
  }, [screen, petDetails, selectedVet, backMap, navigate]); // backMap safe (memo)

  return (
    <div className="min-h-screen bg-stone-100 font-sans">
      <div className="min-h-screen md:bg-gradient-to-b md:from-stone-100 md:to-stone-200">
        <div className="min-h-screen">
          <div
            className={[
              "bg-white min-h-screen relative overflow-x-hidden",
              "w-full",
              "md:w-full md:rounded-none md:shadow-none md:border-0",
            ].join(" ")}
          >
            {/* Lazy loading boundary */}
            <Suspense fallback={<LoadingScreen />}>{content}</Suspense>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Home;
