import React, { useState, useEffect } from "react";
import LandingScreen from "../screen/Landingscreen";
import PetDetailsScreen from "../screen/Petdetailsscreen";
import VetsScreen from '../screen/Vetsscreen'
import { PaymentScreen, ConfirmationScreen } from "../screen/Paymentscreen";
import {
  VetLoginScreen,
  VetRegisterScreen,
  VetPendingScreen,
  VetDashboardScreen,
} from "../components/VetScreens";

const Home = () => {
  const [screen, setScreen] = useState("landing");
  const [petDetails, setPetDetails] = useState(null);
  const [selectedVet, setSelectedVet] = useState(null);

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [screen]);

  const goBack = () => {
    if (screen === "details") setScreen("landing");
    if (screen === "vets") setScreen("details");
    if (screen === "payment") setScreen("vets");
    if (screen === "vet-login") setScreen("landing");
    if (screen === "vet-register") setScreen("vet-login");
    if (screen === "vet-pending") setScreen("vet-register");
    if (screen === "vet-dashboard") setScreen("vet-login");
  };

  return (
    <div className="min-h-screen bg-stone-100 font-sans">
      {/* Background (mobile same, desktop optional gradient ok) */}
      <div className="min-h-screen md:bg-gradient-to-b md:from-stone-100 md:to-stone-200">
        {/* ✅ Full width on desktop/tablet */}
        <div className="min-h-screen">
          {/* App Surface */}
          <div
            className={[
              // Base
              "bg-white min-h-screen relative overflow-x-hidden",
              // Mobile: same full width
              "w-full",
              // ✅ Desktop/tablet: FULL screen width (no max, no centered card)
              "md:w-full md:rounded-none md:shadow-none md:border-0",
            ].join(" ")}
          >
            {screen === "landing" && (
              <LandingScreen
                onStart={() => setScreen("details")}
                onVetAccess={() => setScreen("vet-login")}
              />
            )}

            {screen === "details" && (
              <PetDetailsScreen
                onBack={goBack}
                onSubmit={(details) => {
                  setPetDetails(details);
                  setScreen("vets");
                }}
              />
            )}

            {screen === "vets" && (
              <VetsScreen
                petDetails={petDetails}
                onBack={goBack}
                onSelect={(vet) => {
                  setSelectedVet(vet);
                  setScreen("payment");
                }}
              />
            )}

            {screen === "payment" && selectedVet && (
              <PaymentScreen
                vet={selectedVet}
                onBack={goBack}
                onPay={() => {
                  setTimeout(() => setScreen("confirmation"), 1500);
                }}
              />
            )}

            {screen === "confirmation" && selectedVet && (
              <ConfirmationScreen vet={selectedVet} />
            )}

            {/* Vet Flows */}
            {screen === "vet-login" && (
              <VetLoginScreen
                onLogin={() => setScreen("vet-dashboard")}
                onRegisterClick={() => setScreen("vet-register")}
                onBack={goBack}
              />
            )}

            {screen === "vet-register" && (
              <VetRegisterScreen
                onSubmit={() => setScreen("vet-pending")}
                onBack={goBack}
              />
            )}

            {screen === "vet-pending" && (
              <VetPendingScreen onHome={() => setScreen("landing")} />
            )}

            {screen === "vet-dashboard" && (
              <VetDashboardScreen onLogout={() => setScreen("landing")} />
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Home;
