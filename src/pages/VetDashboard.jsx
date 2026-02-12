import React, { lazy, Suspense, useEffect, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { loadVetAuth, saveVetAuth } from "../lib/vetAuth";
import { registerDoctorPush } from "../lib/firebaseMessaging";

const VetDashboardScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetDashboardScreen,
  }))
);
const VetLoginScreen = lazy(() =>
  import("../components/VetScreens").then((m) => ({
    default: m.VetLoginScreen,
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
  const pushRegisteredRef = useRef(null);

  useEffect(() => {
    if (incomingAuth) {
      saveVetAuth(incomingAuth);
      setAuth(incomingAuth);
    }
  }, [incomingAuth]);

  useEffect(() => {
    if (auth) return;
    const stored = loadVetAuth();
    if (stored) {
      setAuth(stored);
    }
  }, [auth]);

  useEffect(() => {
    const doctorId =
      auth?.doctor_id ||
      auth?.doctor?.id ||
      auth?.doctor?.doctor_id ||
      auth?.doctor?.doctorId;
    if (!doctorId) return;

    if (pushRegisteredRef.current === doctorId) {
      return;
    }
    pushRegisteredRef.current = doctorId;

    const authToken =
      auth?.token ||
      auth?.access_token ||
      auth?.doctor?.token ||
      auth?.doctor?.access_token ||
      "";

    registerDoctorPush(doctorId, authToken).catch((error) => {
      console.warn("[FCM] Doctor push registration failed:", error);
    });
  }, [auth]);

  const handleLogin = (payload) => {
    const nextAuth = payload || loadVetAuth();
    if (!nextAuth) return;
    saveVetAuth(nextAuth);
    setAuth(nextAuth);
  };

  return (
    <Suspense fallback={<LoadingScreen />}>
      {auth ? (
        <VetDashboardScreen
          auth={auth}
          onLogout={() => navigate("/auth", { replace: true })}
        />
      ) : (
        <VetLoginScreen
          onLogin={handleLogin}
          onRegisterClick={() =>
            navigate("/auth", { state: { mode: "register" } })
          }
          onBack={() => navigate("/")}
        />
      )}
    </Suspense>
  );
};

export default VetDashboard;
