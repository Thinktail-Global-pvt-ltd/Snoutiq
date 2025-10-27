import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight, Video, Clock, DollarSign, Users, CheckCircle } from 'lucide-react';

const HeroSection = () => {
  const [heatmapVisible, setHeatmapVisible] = useState(true);
  const [currentSlide, setCurrentSlide] = useState(0);

  const doctorImages = [
    {
      id: 1,
      image: "👨‍⚕️",
      name: "Dr. Sarah Johnson",
      specialty: "Veterinary Surgeon",
      experience: "12+ years"
    },
    {
      id: 2,
      image: "👩‍⚕️",
      name: "Dr. Mike Chen",
      specialty: "Pet Dermatology",
      experience: "8+ years"
    },
    {
      id: 3,
      image: "👨‍⚕️",
      name: "Dr. Emily Davis",
      specialty: "Animal Behavior",
      experience: "10+ years"
    },
    {
      id: 4,
      image: "👩‍⚕️",
      name: "Dr. Robert Wilson",
      specialty: "Emergency Care",
      experience: "15+ years"
    }
  ];

  const nextSlide = () => {
    setCurrentSlide((prev) => (prev + 1) % doctorImages.length);
  };

  const prevSlide = () => {
    setCurrentSlide((prev) => (prev - 1 + doctorImages.length) % doctorImages.length);
  };

  useEffect(() => {
    const interval = setInterval(nextSlide, 5000);
    return () => clearInterval(interval);
  }, []);

  return (
    <div className="min-h-screen bg-gradient-to-r from-blue-600 to-purple-600 mt-20">
      {/* Main Hero Section */}
      <main className="max-w-7xl mx-auto px-4 md:px-8 py-8 md:py-16">
        <div className="grid md:grid-cols-2 gap-12 items-center">
          
          {/* Left Column - Main CTA */}
          <div className="space-y-8">
            <div className="space-y-6">
              <h2 className="text-5xl md:text-7xl font-bold leading-tight">
                <span className="text-yellow-400">Skip</span>
                <span className="text-white"> the Clinic!</span>
              </h2>

              <p className="text-2xl md:text-3xl text-white">
                Consult Vet Now In <span className="text-yellow-400 font-bold">10 Minutes</span>
              </p>

              <p className="text-lg md:text-xl text-gray-300">
                Private Video consultation | Local Verified Doctors | Starts at just <span className="font-bold text-white">₹299</span>
              </p>
            </div>

            {/* Doctor Profiles - Small circles for mobile */}
            <div className="md:hidden relative">
              <div className="flex -space-x-4 justify-center">
                {doctorImages.slice(0, 3).map((doctor) => (
                  <div 
                    key={doctor.id}
                    className="w-16 h-16 rounded-full border-4 border-gray-900 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center overflow-hidden"
                  >
                    <span className="text-2xl">{doctor.image}</span>
                  </div>
                ))}
                {doctorImages.length > 3 && (
                  <div className="w-16 h-16 rounded-full border-4 border-gray-900 bg-gray-700 flex items-center justify-center text-white text-sm font-bold">
                    +{doctorImages.length - 3}
                  </div>
                )}
              </div>

              {/* Online Status Badge */}
              <div className="absolute -bottom-2 right-4 bg-white rounded-lg shadow-xl p-3 border-2 border-green-500">
                <div className="text-sm font-semibold text-gray-700">12+</div>
                <div className="text-xs text-gray-600">Vets Online</div>
                <div className="flex items-center space-x-1 text-green-500 mt-1">
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                  <span className="text-xs font-bold">Live</span>
                </div>
              </div>
            </div>

            {/* CTA Buttons */}
            <div className="space-y-4 relative">
              {heatmapVisible && (
                <div className="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold absolute -left-4 -top-4 md:relative md:left-0 md:top-0">
                  4
                </div>
              )}
              
              <button className="w-full md:w-auto bg-gradient-to-r from-yellow-400 to-orange-500 text-gray-900 font-bold text-xl px-12 py-4 rounded-full hover:shadow-2xl transform hover:scale-105 transition-all duration-300 shadow-lg">
                CONSULT NOW
              </button>

              <p className="text-lg text-gray-300 text-center md:text-left">
                Get Veterinary Doctor In <span className="text-yellow-400 font-bold">10 Minutes</span>
              </p>
            </div>
          </div>

          {/* Right Column - Doctor Slider for Desktop */}
          <div className="hidden md:block relative">
            <div className="relative bg-white rounded-2xl p-6 shadow-2xl">
              {/* Slider Container */}
              <div className="relative h-96 overflow-hidden rounded-xl">
                {doctorImages.map((doctor, index) => (
                  <div
                    key={doctor.id}
                    className={`absolute inset-0 transition-opacity duration-500 ${
                      index === currentSlide ? 'opacity-100' : 'opacity-0'
                    }`}
                  >
                    <div className="h-full flex flex-col items-center justify-center">
                      <div className="w-48 h-48 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-6xl mb-6 shadow-lg">
                        {doctor.image}
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Slider Controls */}
              <button
                onClick={prevSlide}
                className="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all duration-300"
              >
                <ChevronLeft className="w-6 h-6 text-gray-700" />
              </button>
              <button
                onClick={nextSlide}
                className="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all duration-300"
              >
                <ChevronRight className="w-6 h-6 text-gray-700" />
              </button>

              {/* Slider Dots */}
              <div className="flex justify-center space-x-2 mt-6">
                {doctorImages.map((_, index) => (
                  <button
                    key={index}
                    onClick={() => setCurrentSlide(index)}
                    className={`w-3 h-3 rounded-full transition-all duration-300 ${
                      index === currentSlide ? 'bg-blue-600' : 'bg-gray-300'
                    }`}
                  />
                ))}
              </div>

              {/* Online Status */}
              <div className="absolute -top-3 -right-3 bg-green-500 text-white px-4 py-2 rounded-full shadow-lg">
                <div className="flex items-center space-x-2">
                  <div className="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                  <span className="text-sm font-bold">Online Now</span>
                </div>
              </div>
            </div>

          </div>
        </div>

        {/* Features Grid for Mobile */}
        <div className="md:hidden mt-12">
          <div className="grid grid-cols-2 gap-4">
            <div className="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-4 border border-white border-opacity-20">
              <Clock className="text-yellow-400 mb-2 mx-auto" size={24} />
              <div className="text-white font-bold text-sm text-center">10 Min</div>
              <div className="text-gray-300 text-xs text-center">Quick Response</div>
            </div>

            <div className="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-4 border border-white border-opacity-20">
              <DollarSign className="text-green-400 mb-2 mx-auto" size={24} />
              <div className="text-white font-bold text-sm text-center">From ₹299</div>
              <div className="text-gray-300 text-xs text-center">Affordable</div>
            </div>

            <div className="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-4 border border-white border-opacity-20">
              <Users className="text-blue-400 mb-2 mx-auto" size={24} />
              <div className="text-white font-bold text-sm text-center">12+ Vets</div>
              <div className="text-gray-300 text-xs text-center">Always Online</div>
            </div>

            <div className="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-4 border border-white border-opacity-20">
              <CheckCircle className="text-purple-400 mb-2 mx-auto" size={24} />
              <div className="text-white font-bold text-sm text-center">Verified</div>
              <div className="text-gray-300 text-xs text-center">Licensed</div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default HeroSection;