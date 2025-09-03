import React, { useEffect, useState } from "react";
import axiosClient from "../axios";
import VetList from "./VetList";
import GroomerList from "./GroomerList";

export default function RightSidebar({ isMobile = false, onItemClick }) {
  const [location, setLocation] = useState(null);
  const [error, setError] = useState(null);
  const [data, setData] = useState({ vets: [], groomers: [] });

  const handleItemClick = () => {
    if (isMobile && onItemClick) {
      onItemClick();
    }
  };

  // Fetch location
  useEffect(() => {
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLocation({
            lat: position.coords.latitude,
            lng: position.coords.longitude,
          });
        },
        (err) => {
          setError(err.message);
        }
      );
    }
  }, []);

  // Fetch nearby vets/groomers
  useEffect(() => {
    const fetch = async () => {
      try {
        const res = await axiosClient.get(
          `/fetchNearbyPlaces?lat=${location.lat}&lng=${location.lng}`
        );
        setData(res.data);
      } catch (e) {
        console.error("Error fetching places", e);
      }
    };
    if (location !== null) fetch();
  }, [location]);

  // ---------------- MOBILE VERSION ----------------
  if (isMobile) {
    return (
      <div className="w-full space-y-6">
        {/* Special Offers */}
        <div className="bg-white rounded-2xl p-5 shadow-sm border border-gray-200">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Special Offers
          </h3>
          <div className="bg-gradient-to-r from-[#9B51E0] to-[#2761E8] rounded-xl p-4 text-white shadow-md">
            <h4 className="font-semibold text-base mb-1">
              First Vet Consultation
            </h4>
            <p className="text-sm mb-3">
              50% off your first virtual consultation
            </p>
            <span className="bg-white/20 text-white text-xs px-3 py-1 rounded-full font-semibold backdrop-blur-sm">
              FIRST50
            </span>
          </div>
        </div>

        {/* Veterinarians */}
        <VetList data={data} handleItemClick={handleItemClick} />

        {/* Groomers */}
        <GroomerList data={data} handleItemClick={handleItemClick} />
      </div>
    );
  }

  // ---------------- DESKTOP VERSION ----------------
  return (
    <div className="w-[25%] fixed right-0 top-[70px] h-[calc(100vh-70px)] bg-gray-50 border-l border-gray-200 overflow-y-auto px-5 py-6 space-y-6 custom-scroll">
      {/* Special Offers */}
      <div className="bg-white rounded-2xl p-5 shadow hover:shadow-md transition">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          Special Offers
        </h3>
        <div className="bg-gradient-to-r from-[#9B51E0] to-[#2761E8] rounded-xl p-5 text-white shadow">
          <h4 className="font-semibold text-base mb-1">
            First Vet Consultation
          </h4>
          <p className="text-sm mb-3">
            50% off your first virtual consultation
          </p>
          <span className="bg-white/20 text-white text-xs px-3 py-1 rounded-full font-semibold backdrop-blur-sm">
            FIRST50
          </span>
        </div>
      </div>

      {/* Veterinarians */}
      <VetList data={data} handleItemClick={handleItemClick} />

      {/* Groomers */}
      <GroomerList data={data} handleItemClick={handleItemClick} />
    </div>
  );
}
