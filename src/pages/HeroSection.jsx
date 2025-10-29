import React, { useState, useEffect } from "react";
import {
  ChevronLeft,
  ChevronRight,
  Clock,
  Users,
  CheckCircle,
  Stethoscope,
} from "lucide-react";
import { useNavigate } from "react-router-dom";

import img1 from "../assets/images/doctor_1.png";
import img2 from "../assets/images/doctor_2.png";
import img3 from "../assets/images/doctor_3.png";

const HeroSection = () => {
  const [currentSlide, setCurrentSlide] = useState(0);
  const navigate = useNavigate();

  const doctorImages = [
    { id: 1, image: img1 },
    { id: 2, image: img2 },
    { id: 3, image: img3 },
  ];

  const nextSlide = () =>
    setCurrentSlide((prev) => (prev + 1) % doctorImages.length);
  const prevSlide = () =>
    setCurrentSlide((prev) => (prev - 1 + doctorImages.length) % doctorImages.length);

  useEffect(() => {
    const interval = setInterval(nextSlide, 5000);
    return () => clearInterval(interval);
  }, []);

  const handleSendMessage = () => {
    navigate("/register");
  };

  return (
    <section className="relative bg-blue-600 text-white overflow-hidden">
      {/* Overlay Glow */}
      <div className="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent pointer-events-none"></div>

      <main className="relative max-w-7xl mx-auto px-6 sm:px-10 py-16 md:py-28 flex flex-col md:flex-row items-center justify-between gap-10">
        {/* LEFT SIDE */}
        <div className="flex-1 text-center md:text-left space-y-8">
          <div className="space-y-5">
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight">
              <span className="text-[#FFD700]">Skip the Clinic,</span>
              <br />
              <span className="text-white">Consult Your Vet Online!</span>
            </h1>

            <p className="text-lg sm:text-xl text-gray-200 max-w-md mx-auto md:mx-0">
              Connect with certified vets in{" "}
              <span className="text-[#FFD700] font-semibold">under 10 minutes</span>.
              Quick, easy, and available <span className="font-semibold">24/7</span>.
            </p>
          </div>

          {/* CTA */}
          <div className="pt-4">
            <button
              onClick={handleSendMessage}
              className="group relative bg-gradient-to-r from-[#FFD700] to-[#FFA500] text-gray-900 font-semibold text-lg px-10 py-4 rounded-full shadow-[0_4px_14px_rgba(255,215,0,0.4)] hover:shadow-[0_6px_20px_rgba(255,165,0,0.5)] hover:scale-105 transition-all duration-300"
            >
              <span>CONSULT NOW</span>
              <span className="absolute right-6 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-all duration-300">
                <ChevronRight className="w-5 h-5 text-gray-900" />
              </span>
            </button>
            <p className="mt-3 text-gray-300 text-base">
              Get a vet in{" "}
              <span className="text-[#FFD700] font-semibold">10 minutes or less</span>
            </p>
          </div>

          {/* VETS ONLINE INDICATOR */}
          <div className="flex items-center justify-center md:justify-start gap-4 mt-10">
            <div className="flex -space-x-3">
              {doctorImages.map((doc) => (
                <img
                  key={doc.id}
                  src={doc.image}
                  alt={`Doctor ${doc.id}`}
                  className="w-12 h-12 rounded-full border-2 border-white object-cover"
                />
              ))}
            </div>
            <div className="bg-white text-gray-900 px-3 py-1.5 rounded-full flex items-center gap-2 shadow-md">
              <div className="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></div>
              <span className="font-medium text-sm sm:text-base">12+ Vets Online</span>
            </div>
          </div>

          {/* TRUST BADGES */}
          <div className="grid grid-cols-3 gap-4 mt-10 text-gray-200">
            <div className="flex flex-col items-center md:items-start space-y-2">
              <Clock className="w-6 h-6 text-[#FFD700]" />
              <span className="text-sm font-medium">10-Min Connect</span>
            </div>
            <div className="flex flex-col items-center md:items-start space-y-2">
              <Users className="w-6 h-6 text-[#FFD700]" />
              <span className="text-sm font-medium">50+ Certified Vets</span>
            </div>
            <div className="flex flex-col items-center md:items-start space-y-2">
              <CheckCircle className="w-6 h-6 text-[#FFD700]" />
              <span className="text-sm font-medium">Trusted by Pet Parents</span>
            </div>
          </div>
        </div>

        {/* RIGHT SIDE - SLIDER */}
        <div className="flex-1 relative hidden md:flex items-center justify-center">
          <div className="relative w-full max-w-md aspect-[4/5] rounded-3xl overflow-hidden ">
            {doctorImages.map((doctor, index) => (
              <img
                key={doctor.id}
                src={doctor.image}
                alt={`Doctor ${doctor.id}`}
                className={`absolute inset-0 w-full h-full object-contain transition-opacity duration-700 ease-in-out ${
                  index === currentSlide ? "opacity-100" : "opacity-0"
                }`}
              />
            ))}

  
          </div>
        </div>
      </main>
    </section>
  );
};

export default HeroSection;
