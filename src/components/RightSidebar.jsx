import React, { memo, useCallback, useMemo, useState } from "react";
import playstore from "../assets/images/googlePlay.webp";
import DoctorAppointmentModal from "../pages/DoctorAppointmentModal";

const OfferCard = memo(({ onClick }) => (
  <div className="bg-white rounded-xl shadow-md p-5 border border-gray-200">
    <h3 className="text-sm font-semibold text-gray-900 mb-1">
      �o" Limited Time Offer
    </h3>
    <p className="text-xs text-gray-600 mb-3">�,1100 off on all video consults</p>
    <button
      onClick={onClick}
      className="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-2 rounded-lg text-sm font-semibold hover:from-blue-700 hover:to-blue-800 transition-all"
    >
      Claim Offer
    </button>
  </div>
));

const EmergencyCard = memo(({ onClick }) => (
  <div className="bg-white rounded-xl shadow-md p-5 border border-gray-200">
    <h3 className="text-sm font-semibold text-red-600 mb-1">dYs" Emergency</h3>
    <p className="text-xs text-gray-600 mb-3">Immediate veterinary care</p>
    <button
      onClick={onClick}
      className="w-full bg-red-600 text-white text-sm font-semibold py-2 rounded-lg hover:bg-red-700 transition-colors"
    >
      Find Emergency Clinic
    </button>
  </div>
));

const AppDownloadCard = memo(() => (
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
));

const RightSidebarBase = ({ isMobile = false, onItemClick }) => {
  const [showAppointmentModal, setShowAppointmentModal] = useState(false);
  const handleItemClick = useCallback(() => {
    if (isMobile && onItemClick) {
      onItemClick();
    }
  }, [isMobile, onItemClick]);

  const handleEmergencyClick = useCallback(() => {
    setShowAppointmentModal(true);
  }, []);

  const closeModal = useCallback(() => setShowAppointmentModal(false), []);

  const cards = useMemo(
    () => (
      <>
        <OfferCard onClick={handleItemClick} />
        <EmergencyCard onClick={handleEmergencyClick} />
        <AppDownloadCard />
      </>
    ),
    [handleEmergencyClick, handleItemClick]
  );

  if (isMobile) {
    return (
      <>
        <div className="w-full space-y-4 p-4 bg-gray-50">{cards}</div>
        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={closeModal}
          onBook={closeModal}
          isEmergency
        />
      </>
    );
  }

  return (
    <>
      <div className="fixed right-0 top-[70px] h-[calc(100vh-70px)] w-[260px] bg-white rounded-tl-xl shadow-md border-l border-gray-200 overflow-y-auto p-4 space-y-4">
        {cards}
      </div>

      <DoctorAppointmentModal
        visible={showAppointmentModal}
        onClose={closeModal}
        onBook={closeModal}
        isEmergency
      />
    </>
  );
};

export default memo(RightSidebarBase);
