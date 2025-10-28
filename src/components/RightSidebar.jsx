import React, { useState } from "react";
import playstore from '../assets/images/googlePlay.webp'
import DoctorAppointmentModal from "../pages/DoctorAppointmentModal";
import { AuthContext } from "../auth/AuthContext";

export default function RightSidebar({ isMobile = false, onItemClick }) {
  const [showAppointmentModal, setShowAppointmentModal] = useState(false);
  
  const authContext = React.useContext(AuthContext);
  const { nearbyDoctors = [] } = authContext || {};

  const handleItemClick = () => {
    if (isMobile && onItemClick) {
      onItemClick();
    }
  };

  const handleEmergencyClick = () => {
    setShowAppointmentModal(true);
  };

  // ---------------- MOBILE VERSION ----------------
  if (isMobile) {
    return (
      <>
        <div className="w-full space-y-4 p-4 bg-gray-50">
          {/* Emergency Contact */}
          <div className="bg-white rounded-xl shadow-md p-5">
            <h3 className="text-sm font-semibold text-red-600 mb-1">🚨 Emergency</h3>
            <p className="text-xs text-gray-600 mb-3">Immediate veterinary care</p>
            <button 
              onClick={handleEmergencyClick}
              className="w-full bg-red-600 text-white text-sm font-semibold py-2 rounded-lg hover:bg-red-700 transition-colors"
            >
              Find Emergency Clinic
            </button>
          </div>

          {/* Special Offer */}
          <div className="bg-white rounded-xl shadow-md p-5">
            <h3 className="text-sm font-semibold text-gray-900 mb-1">✨ Limited Time Offer</h3>
            <p className="text-xs text-gray-600 mb-3">₹100 off on all video consults</p>
            <button className="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-2 rounded-lg text-sm font-semibold hover:from-blue-700 hover:to-blue-800 transition-all">
              Claim Offer
            </button>
          </div>

          {/* App Download */}
          <div className="bg-white rounded-xl p-5 shadow-md text-center">
            <h3 className="text-sm font-semibold text-gray-900 mb-3">Get the App</h3>
            <p className="text-xs text-gray-600 mb-2">Coming Soon...</p>
            <a
              href="https://play.google.com/store/apps/details?id=your.app.id"
              target="_blank"
              rel="noopener noreferrer"
            >
              <img
                src={playstore}
                alt="Get it on Google Play"
                className="mx-auto"
              />
            </a>
          </div>
        </div>

        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={() => setShowAppointmentModal(false)}
          nearbyDoctors={nearbyDoctors}
          onBook={(appointment) => {
            console.log("Emergency appointment booked:", appointment);
            setShowAppointmentModal(false);
          }}
          isEmergency={true}
        />
      </>
    );
  }

  // ---------------- DESKTOP VERSION ----------------
  return (
    <>
      <div className="fixed right-0 top-[70px] h-[calc(100vh-70px)] w-[260px] bg-white rounded-tl-xl shadow-md border-l border-gray-200 overflow-y-auto p-4 space-y-4">
        
        {/* Limited Time Offer */}
        <div className="bg-white rounded-xl shadow-md p-5 border border-gray-200">
          <h3 className="text-sm font-semibold text-gray-900 mb-1">✨ Limited Time Offer</h3>
          <p className="text-xs text-gray-600 mb-3">₹100 off on all video consults</p>
          <button className="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-2 rounded-lg text-sm font-semibold hover:from-blue-700 hover:to-blue-800 transition-all">
            Claim Offer
          </button>
        </div>

        {/* Emergency */}
        <div className="bg-white rounded-xl shadow-md p-5 border border-gray-200">
          <h3 className="text-sm font-semibold text-red-600 mb-1">🚨 Emergency</h3>
          <p className="text-xs text-gray-600 mb-3">Immediate veterinary care</p>
          <button 
            onClick={handleEmergencyClick}
            className="w-full bg-red-600 text-white text-sm font-semibold py-2 rounded-lg hover:bg-red-700 transition-colors"
          >
            Find Emergency Clinic
          </button>
        </div>

        {/* App Download */}
        <div className="bg-white rounded-xl shadow-md p-5 border border-gray-200 text-center">
          <h3 className="text-sm font-semibold text-gray-900 mb-3">Get the App</h3>
          <p className="text-xs text-gray-600 mb-2">Coming Soon...</p>
          <a
            href="https://play.google.com/store/apps/details?id=your.app.id"
            target="_blank"
            rel="noopener noreferrer"
            className="block"
          >
            <img
              src={playstore}
              loading="lazy"
              alt="Get it on Google Play"
              className="mx-auto w-full h-16 object-contain bg-gradient-to-r from-gray-200 to-gray-300 rounded-lg"
            />
          </a>
        </div>
      </div>

      <DoctorAppointmentModal
        visible={showAppointmentModal}
        onClose={() => setShowAppointmentModal(false)}
        nearbyDoctors={nearbyDoctors}
        onBook={(appointment) => {
          console.log("Emergency appointment booked:", appointment);
          setShowAppointmentModal(false);
        }}
        isEmergency={true}
      />
    </>
  );
}