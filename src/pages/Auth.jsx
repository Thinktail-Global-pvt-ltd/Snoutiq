import React, { lazy, Suspense, useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { loadVetAuth, vetAuthKey } from "../lib/vetAuth";

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

const LoadingScreen = () => (
  <div className="min-h-screen w-full flex items-center justify-center bg-white">
    <div className="flex flex-col items-center gap-3">
      <div className="w-10 h-10 rounded-full border-4 border-stone-200 border-t-blue-600 animate-spin" />
      <p className="text-sm text-stone-500 font-medium">Loading...</p>
    </div>
  </div>
);

const Auth = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const initialMode = location.state?.mode;

  const [screen, setScreen] = useState(() =>
    initialMode === "register" ? "register" : "login"
  );
  const [auth, setAuth] = useState(() => loadVetAuth());

  useEffect(() => {
    if (auth && (auth?.doctor_id || auth?.clinic_id || auth?.token || auth?.phone)) {
      navigate("/vet-dashboard", { replace: true, state: { auth } });
    }
  }, [auth, navigate]);

  useEffect(() => {
    const handleStorage = (event) => {
      if (event.key !== vetAuthKey) return;
      setAuth(loadVetAuth());
    };

    window.addEventListener("storage", handleStorage);
    return () => window.removeEventListener("storage", handleStorage);
  }, []);

  const handleLogin = (payload) => {
    const nextAuth = payload || loadVetAuth();
    setAuth(nextAuth);
  };

  return (
    <Suspense fallback={<LoadingScreen />}>
      {screen === "register" ? (
        <VetRegisterScreen
          onSubmit={() => setScreen("pending")}
          onBack={() => setScreen("login")}
        />
      ) : screen === "pending" ? (
        <VetPendingScreen onHome={() => navigate("/")} />
      ) : (
        <VetLoginScreen
          onLogin={handleLogin}
          onRegisterClick={() => setScreen("register")}
          onBack={() => navigate("/")}
        />
      )}
    </Suspense>
  );
};

export default Auth;
