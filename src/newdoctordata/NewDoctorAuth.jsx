import React, { createContext, useContext, useEffect, useMemo, useState } from "react";

const STORAGE_KEY = "new_doctor_auth_v1";

const initialAuth = {
  phone: "",
  request_id: "",
  expires_in: 0,
  otp_requested_at: null,
  phone_verified: false,
  phone_exists: false,
  onboarding_completed: false,
  vet: null,
  doctor: null,
  raw_profile: null,
  extras: {},
};

const NewDoctorAuthContext = createContext(null);

export function NewDoctorAuthProvider({ children }) {
  const [auth, setAuth] = useState(initialAuth);
  const [hydrated, setHydrated] = useState(false);

  useEffect(() => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        setAuth({ ...initialAuth, ...JSON.parse(stored) });
      }
    } catch (error) {
      console.error("Failed to parse doctor auth:", error);
    } finally {
      setHydrated(true);
    }
  }, []);

  const mergeAuth = (patch) => {
    setAuth((prev) => {
      const next = { ...prev, ...patch };
      localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
      return next;
    });
  };

  const clearAuth = () => {
    localStorage.removeItem(STORAGE_KEY);
    setAuth(initialAuth);
  };

  const value = useMemo(
    () => ({
      auth,
      hydrated,
      mergeAuth,
      clearAuth,
    }),
    [auth, hydrated]
  );

  return (
    <NewDoctorAuthContext.Provider value={value}>
      {children}
    </NewDoctorAuthContext.Provider>
  );
}

export function useNewDoctorAuth() {
  const context = useContext(NewDoctorAuthContext);
  if (!context) {
    throw new Error("useNewDoctorAuth must be used inside NewDoctorAuthProvider");
  }
  return context;
}