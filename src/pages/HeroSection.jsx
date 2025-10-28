import React, { useState, useEffect } from "react";
import {
  ChevronLeft,
  ChevronRight,
  Clock,
  DollarSign,
  Users,
  CheckCircle,
} from "lucide-react";

import img1 from "../assets/images/doctor_1.jpeg";
import img2 from "../assets/images/doctor_2.jpeg";
import img3 from "../assets/images/doctor_3.jpeg";
import { useNavigate } from "react-router-dom";


const HeroSection = () => {
  const [currentSlide, setCurrentSlide] = useState(0);
  const navigate = useNavigate()

  const doctorImages = [
    { id: 1, image: img1 },
    { id: 2, image: img2 },
    { id: 3, image: img3 },
  ];

  const nextSlide = () => setCurrentSlide((prev) => (prev + 1) % doctorImages.length);
  const prevSlide = () =>
    setCurrentSlide((prev) => (prev - 1 + doctorImages.length) % doctorImages.length);

  useEffect(() => {
    const interval = setInterval(nextSlide, 5000);
    return () => clearInterval(interval);
  }, []);

   const handleSendMessage = (msg) => {

      navigate("/register")
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-600 to-purple-600 mt-20 text-white">
      <main className="max-w-7xl mx-auto px-4 md:px-8 py-10 md:py-20">
        <div className="grid md:grid-cols-2 gap-12 items-center">
          {/* LEFT SECTION */}
          <div className="space-y-8">
            <div className="space-y-6">
              <h1 className="text-5xl md:text-7xl font-extrabold leading-tight">
                <span className="text-[#FFD700]">Skip</span>{" "}
                <span className="text-white">the Clinic!</span>
              </h1>

              <p className="text-2xl md:text-3xl font-medium">
                Consult Vet Now in{" "}
                <span className="text-[#FFD700] font-bold">10 Minutes</span>
              </p>

              <p className="text-lg md:text-xl text-gray-200">
                Private video consultation with local verified vets — starting at{" "}
              </p>
            </div>

            {/* CTA BUTTON */}
            <div>
              <button className="bg-gradient-to-r from-[#FFD700] to-[#FFA500] text-gray-900 font-semibold text-xl px-12 py-4 rounded-full shadow-lg hover:scale-105 hover:shadow-2xl transition-all duration-300" onClick={handleSendMessage}>
                CONSULT NOW
              </button>
              <p className="mt-4 text-gray-200">
                Get a Veterinary Doctor in{" "}
                <span className="text-[#FFD700] font-bold">10 Minutes</span>
              </p>
            </div>

            {/* VETS ONLINE BADGE */}
            <div className="flex items-center space-x-3 mt-6">
              <div className="flex -space-x-3">
                {doctorImages.map((doc) => (
                  <img
                    key={doc.id}
                    src={doc.image}
                    alt={`Doctor ${doc.id}`}
                    className="w-12 h-12 rounded-full border-2 border-white object-contain"
                  />
                ))}
              </div>
              <div className="bg-white text-gray-800 px-3 py-1 rounded-full flex items-center space-x-1 shadow-md">
                <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span className="font-semibold text-sm">12+ Vets Online</span>
              </div>
            </div>
          </div>

          {/* RIGHT SECTION — IMAGE SLIDER */}
          <div className="relative hidden md:block">
            <div className="relative bg-[#F7F7F7] rounded-3xl overflow-hidden shadow-2xl p-1 border border-white border-opacity-20">
              <div className="relative h-[480px] overflow-hidden rounded-2xl">
                {doctorImages.map((doctor, index) => (
                  <img
                    key={doctor.id}
                    src={doctor.image}
                    alt={`Doctor ${doctor.id}`}
                    className={`absolute inset-0 w-full h-full object-contain transition-opacity duration-700 rounded-2xl ${
                      index === currentSlide ? "opacity-100" : "opacity-0"
                    }`}
                  />
                ))}
              </div>

            </div>
          </div>
        </div>

  
      </main>
    </div>
  );
};

export default HeroSection;
