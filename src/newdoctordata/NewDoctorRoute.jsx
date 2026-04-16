import React from "react";
import { Navigate, Route, Routes } from "react-router-dom";
import "./NewDoctor.css";
import NewDoctorDashboardView from "./NewDoctorDashboardView";
import NewDoctorLogin from "./NewDoctorLogin";
import NewDoctorNewRequestView from "./NewDoctorNewRequestView";
import NewDoctorNotificationsView from "./NewDoctorNotificationsView";
import NewDoctorOnBoarding from "./NewDoctorOnBoarding";
import NewDoctorSearchView from "./NewDoctorSearchView";
import NewDoctorWhatsAppPopup from "./NewDoctorWhatsAppPopup";
import NewDoctorDigitalPrescription from "./NewDoctorDigitalPrescription";
import DoctorPendingPrescriptionGate from "./DoctorPendingPrescriptionGate";
import NewDoctorParentProfileView from "./NewDoctorParentProfileView";
import { NewDoctorAuthProvider, useNewDoctorAuth } from "./NewDoctorAuth";

function DoctorEntryRedirect() {
  const { auth, hydrated } = useNewDoctorAuth();

  if (!hydrated) return null;

  if (auth.onboarding_completed) {
    return <Navigate replace to="dashboard" />;
  }

  if (auth.phone_verified && !auth.phone_exists) {
    return <Navigate replace to="onboarding" />;
  }

  if (auth.phone_verified && auth.phone_exists) {
    return <Navigate replace to="dashboard" />;
  }

  return <Navigate replace to="login" />;
}

function RequireVerifiedPhone({ children }) {
  const { auth, hydrated } = useNewDoctorAuth();

  if (!hydrated) return null;
  if (!auth.phone_verified) {
    return <Navigate replace to="/counsltflow/login" />;
  }

  return children;
}

function RequireDoctorSession({ children }) {
  const { auth, hydrated } = useNewDoctorAuth();

  if (!hydrated) return null;

  if (auth.onboarding_completed) return children;
  if (auth.phone_verified && auth.phone_exists) return children;

  return <Navigate replace to="/counsltflow/login" />;
}

const NewDoctorRouteContent = () => {
  return (
    <div className="mobile-container">
      <div className="doctor-safe-area">
        <DoctorPendingPrescriptionGate />
        <Routes>
          <Route index element={<DoctorEntryRedirect />} />
          <Route path="login" element={<NewDoctorLogin />} />
          <Route
            path="onboarding"
            element={
              <RequireVerifiedPhone>
                <NewDoctorOnBoarding />
              </RequireVerifiedPhone>
            }
          />
          <Route
            path="dashboard"
            element={
              <RequireDoctorSession>
                <NewDoctorDashboardView />
              </RequireDoctorSession>
            }
          />
          <Route
            path="new-request"
            element={
              <RequireDoctorSession>
                <NewDoctorNewRequestView />
              </RequireDoctorSession>
            }
          />
          <Route
            path="search"
            element={
              <RequireDoctorSession>
                <NewDoctorSearchView />
              </RequireDoctorSession>
            }
          />
          <Route
            path="whatsapp"
            element={
              <RequireDoctorSession>
                <NewDoctorWhatsAppPopup />
              </RequireDoctorSession>
            }
          />
          <Route
            path="notifications"
            element={
              <RequireDoctorSession>
                <NewDoctorNotificationsView />
              </RequireDoctorSession>
            }
          />
          <Route
            path="digital-prescription"
            element={
              <RequireDoctorSession>
                <NewDoctorDigitalPrescription />
              </RequireDoctorSession>
            }
          />
          <Route
            path="parent-profile/:id"
            element={
              <RequireDoctorSession>
                <NewDoctorParentProfileView />
              </RequireDoctorSession>
            }
          />
        </Routes>
      </div>
    </div>
  );
};

const NewDoctorRoute = () => {
  return (
    <NewDoctorAuthProvider>
      <NewDoctorRouteContent />
    </NewDoctorAuthProvider>
  );
};

export default NewDoctorRoute;
