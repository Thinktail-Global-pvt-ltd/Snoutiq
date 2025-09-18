import React, { lazy, Suspense } from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import { Toaster } from "react-hot-toast";
import ProtectedRoute from "./ProtectedRoute";
import { FaPaw } from "react-icons/fa";
import RegisterPetOwner from "./pages/RegisterPetOwner";

// Public pages
const Login = lazy(() => import("./pages/Login"));
const ForgotPassword = lazy(() => import("./pages/ForgotPassword"));
const Register = lazy(() => import("./pages/Register"));
const PrivacyPolicy = lazy(() => import("./policies/PrivacyPolicy"));
const TearmsCondition = lazy(() => import("./policies/TearmsCondition"));
const Cancelation = lazy(() => import("./policies/Cancelation"));
const CookiePolicy = lazy(() => import("./policies/CookiePolicy"));
const MedicalDataConsent = lazy(() => import("./policies/MedicalDataConsent"));
const ShippingPolicy = lazy(() => import("./policies/ShippingPolicy"));
import PatientDashboard from "./pages/PatientDashboard";
import DoctorDashboard from "./pages/DoctorDashboard";
import CallTestPage from "./pages/CallTestPage";
import RegisterPassword from "./pages/RegisterPetPassword";
import RegisterPetDetails from "./pages/RegisterPetOwner";
import { RegistrationProvider } from "./auth/RegistrationContext";
// Core app pages
const Home = lazy(() => import("./pages/Home"));
const Dashboard = lazy(() => import("./pages/Dashboard"));
const PetInfo = lazy(() => import("./PetDashboard/PetInfo"));
const EditPet = lazy(() => import("./PetDashboard/EditPet"));
const AddPet = lazy(() => import("./PetDashboard/AddPet"));
const Videocall = lazy(() => import("./pages/VideoCall"));
const SearchingDoctor = lazy(() => import("./pages/SearchingDoctor"));
const PaymentPage = lazy(() => import("./PetDashboard/Payment"));
const WaitingForDoctor = lazy(() => import("./PetDashboard/WaitingForDoctor"));
// const CallTestPage = lazy(() => import("./CallTestPage"));

// Vet/Admin pages
const DoctorRegistration = lazy(() =>
  import("./VetDashboard/DoctorRegistration")
);
const VetHome = lazy(() => import("./VetDashboard/VetHome"));
const HeaderWithSidebar = lazy(() => import("./VetDashboard/Sidebar"));
const VetOnwerProfile = lazy(() => import("./VetDashboard/VetOnwerProfile"));
const VetPayment = lazy(() => import("./VetDashboard/VetPayment"));
const VetDocument = lazy(() => import("./VetDashboard/VetDocument"));
const Ratings = lazy(() => import("./VetDashboard/Rating"));
const Support = lazy(() => import("./pages/Support"));
const VetOwner = lazy(() => import("./admin/VetOwner"));
const PetOwner = lazy(() => import("./admin/PetOwner"));
const DoctorEmergencySearch = lazy(() =>
  import("./VetDashboard/DoctorEmergencySearch")
);
const VaccinationTracker = lazy(() =>
  import("./PetDashboard/VaccinationTracker")
);
const PetHealth = lazy(() => import("./PetDashboard/PetHealth"));
const PetDailyCare = lazy(() => import("./PetDashboard/PetDailyCare"));
const PetWeightMonitoring = lazy(() =>
  import("./PetDashboard/PetWeightMonitoring")
);
const PetMedicationTracker = lazy(() =>
  import("./PetDashboard/PetMedicationTracker")
);
const CallPage = lazy(() => import("./CallPage"));
import NotificationSocket from "./components/NotificationSocket";
function App() {
  return (
    <Router>
      {/* <Toaster position="top-right" reverseOrder={false} /> */}
      <Toaster
  position="top-center"
  containerStyle={{
    top: 80,   // 👈 top se distance (px)
    bottom: 80 // 👈 bottom se bhi adjust kar sakte ho
  }}
  toastOptions={{
    duration: 4000,
    style: { fontSize: "14px", borderRadius: "8px" }
  }}
/>

      <NotificationSocket />
      <div className="bg-white text-black">
        <Suspense
          fallback={
            <div className="flex items-center justify-center h-screen">
              <div className="animate-spin">
                <FaPaw className="w-16 h-16 text-blue-500" />
              </div>
            </div>
          }
        >
          <Routes>
            {/* Home page */}
            <Route
              path="/"
              element={
                <ProtectedRoute>
                  <Home />
                </ProtectedRoute>
              }
            />
            {/* Public Routes */}.{/* Patient ka route */}
            <Route path="/patient" element={<PatientDashboard />} />
            <Route
              path="/register"
              element={
                <RegistrationProvider>
                  <Register />
                </RegistrationProvider>
              }
            />
            <Route
              path="/register-pet-details"
              element={
                <RegistrationProvider>
                  <RegisterPetDetails />
                </RegistrationProvider>
              }
            />
            <Route
              path="/register-password"
              element={
                <RegistrationProvider>
                  <RegisterPassword />
                </RegistrationProvider>
              }
            />
            {/* Doctor ka route */}
            <Route
              path="/doctor"
              element={<DoctorDashboard doctorId={501} />}
            />
            {/* Common call page (doctor + patient dono yaha connect honge) */}
            <Route path="/call-page/:channel" element={<CallTestPage />} />
            <Route path="/login" element={<Login />} />
            <Route path="/forgot-password" element={<ForgotPassword />} />
            <Route path="/vet-register" element={<DoctorRegistration />} />
            <Route path="/pet-data-register" element={<RegisterPetOwner />} />
            {/* privacy policy */}
            <Route path="/privacy-policy" element={<PrivacyPolicy />} />
            <Route path="/terms-of-service" element={<TearmsCondition />} />
            <Route path="/cancellation-policy" element={<Cancelation />} />
            <Route path="/cookie-policy" element={<CookiePolicy />} />
            <Route
              path="/medical-data-consent"
              element={<MedicalDataConsent />}
            />
            <Route path="/shipping-policy" element={<ShippingPolicy />} />
            <Route
              path="/user/pets"
              element={
                <ProtectedRoute>
                  <PetInfo />
                </ProtectedRoute>
              }
            />
            <Route
              path="/user/pets/edit/:id"
              element={
                <ProtectedRoute>
                  <EditPet />
                </ProtectedRoute>
              }
            />
            {/* main page */}
            <Route
              path="/dashboard"
              element={
                <ProtectedRoute>
                  <Dashboard />
                </ProtectedRoute>
              }
            />
            <Route
              path="/chat/:chat_room_token"
              element={
                <ProtectedRoute>
                  <Dashboard />
                </ProtectedRoute>
              }
            />
            <Route
              path="/video-call"
              element={
                <ProtectedRoute>
                  <Videocall />
                </ProtectedRoute>
              }
            />
            <Route
              path="/searching-doctor"
              element={
                <ProtectedRoute>
                  <SearchingDoctor />
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/*"
              element={
                <ProtectedRoute>
                  <VetHome />
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/pet-info"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <PetInfo />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/add-pet"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <AddPet />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/vet-profile"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <VetOnwerProfile />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/vet-document"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <VetDocument />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/vet-payment"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <VetPayment />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/rating"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <Ratings />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/support"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <Support />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/pet-owner"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <PetOwner />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/vet-owner"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <VetOwner />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/doctor-emergency-search"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <DoctorEmergencySearch />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/vaccination-tracker"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <VaccinationTracker />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/pet-health"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <PetHealth />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/pet-daily-care"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <PetDailyCare />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/weight-monitoring"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <PetWeightMonitoring />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/user-dashboard/medical-tracker"
              element={
                <ProtectedRoute>
                  <HeaderWithSidebar>
                    <PetMedicationTracker />
                  </HeaderWithSidebar>
                </ProtectedRoute>
              }
            />
            <Route
              path="/doctor-testing"
              element={
                <ProtectedRoute>
                  <CallTestPage />
                </ProtectedRoute>
              }
            />
            <Route
              path="/waiting-for-doctor"
              element={
                <ProtectedRoute>
                  <WaitingForDoctor />
                </ProtectedRoute>
              }
            />
            <Route
              path="/payment/:sid"
              element={
                <ProtectedRoute>
                  <PaymentPage />
                </ProtectedRoute>
              }
            />
            <Route path="/call" element={<CallPage />} />
          </Routes>
        </Suspense>
      </div>
    </Router>
  );
}

export default App;
