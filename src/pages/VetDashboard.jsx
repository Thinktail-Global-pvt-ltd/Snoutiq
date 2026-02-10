import React, { lazy, Suspense, useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { loadVetAuth, saveVetAuth } from "../lib/vetAuth";

const VetDashboardScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetDashboardScreen,
  }))
);

const LoadingScreen = () => (
  <div className="min-h-screen w-full flex items-center justify-center bg-white">
    <div className="flex flex-col items-center gap-3">
      <div className="w-10 h-10 rounded-full border-4 border-stone-200 border-t-blue-600 animate-spin" />
      <p className="text-sm text-stone-500 font-medium">Loading...</p>
    </div>
  </div>
);

const VetDashboard = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const incomingAuth = location.state?.auth;
  const [auth, setAuth] = useState(() => incomingAuth || loadVetAuth());

  useEffect(() => {
    if (incomingAuth) {
      saveVetAuth(incomingAuth);
      setAuth(incomingAuth);
    }
  }, [incomingAuth]);

  useEffect(() => {
    if (!auth) {
      navigate("/auth", { replace: true });
    }
  }, [auth, navigate]);

  if (!auth) {
    return <LoadingScreen />;
  }

  return (
    <Suspense fallback={<LoadingScreen />}>
      <VetDashboardScreen
        auth={auth}
        onLogout={() => navigate("/auth", { replace: true })}
      />
    </Suspense>
  );
};

export default VetDashboard;
