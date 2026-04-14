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

const NewDoctorRoute = () => {
  return (
    <div className="mobile-container">
      <div className="doctor-safe-area">
        <Routes>
          <Route index element={<Navigate replace to="login" />} />
          <Route path="login" element={<NewDoctorLogin />} />
          <Route path="onboarding" element={<NewDoctorOnBoarding />} />
          <Route path="dashboard" element={<NewDoctorDashboardView />} />
          <Route path="new-request" element={<NewDoctorNewRequestView />} />
          <Route path="search" element={<NewDoctorSearchView />} />
          <Route path="whatsapp" element={<NewDoctorWhatsAppPopup />} />
          <Route
            path="notifications"
            element={<NewDoctorNotificationsView />}
          />
        </Routes>
      </div>
    </div>
  );
};

export default NewDoctorRoute;
