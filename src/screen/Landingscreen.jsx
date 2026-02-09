import React from "react";
import { VETS } from "../../constants";
import { Button } from "../components/Button";
import { Clock, Award, FileText, Star, Heart, Stethoscope, Shield, CheckCircle } from "lucide-react";
import { PieChart, Pie, Cell, ResponsiveContainer } from "recharts";
import logo from "../assets/images/logo.png";

const LandingScreen = ({ onStart, onVetAccess }) => {
  const trustData = [
    { name: "Happy", value: 98 },
    { name: "Unhappy", value: 2 },
  ];
  const COLORS = ["#10b981", "#f3f4f6"];

  return (
    <div
      className="
        min-h-screen flex flex-col bg-gradient-to-b from-slate-50 to-white animate-fade-in relative
        pb-20
        md:pb-36 lg:pb-40
      "
    >
      {/* Header with SnoutIQ Logo */}
      <header className="px-6 pt-8 md:px-10 lg:px-16 md:pt-10">
        <div className="flex items-center justify-between">
          {/* SnoutIQ Logo */}
          <div className="flex items-center gap-2 md:gap-3">
            {/* <div>
              <h1 className="text-xl md:text-2xl font-bold text-slate-900">SnoutIQ</h1>
              <p className="text-xs md:text-sm text-slate-500">Veterinary Intelligence</p>
            </div> */}
            <img src={logo} alt="SnoutIQ Logo" className="w-24 md:w-32" />
          </div>

          {/* Vet Access Button */}
          <button
            onClick={onVetAccess}
            className="
              text-xs font-semibold
              bg-white backdrop-blur px-3 py-1.5 rounded-lg shadow border border-slate-200
              hover:bg-blue-50 transition-colors flex items-center gap-2
              md:text-sm md:px-4 md:py-2.5 md:rounded-xl
            "
          >
            <Shield className="w-3 h-3 md:w-4 md:h-4 text-blue-600" />
            <span className="bg-gradient-to-r from-blue-700 to-blue-600 bg-clip-text text-transparent">
              Vet Access
            </span>
          </button>
        </div>
      </header>

      {/* Main Content */}
      <div className="flex-1">
        {/* Hero Section */}
        <div className="px-6 pt-8 md:px-10 lg:px-16 md:pt-16 lg:pt-20">
          <div className="md:grid md:grid-cols-12 md:gap-12 lg:gap-16 md:items-start">
            {/* Left Column */}
            <div className="md:col-span-7 lg:col-span-7">
              {/* Online Badge */}
              <div className="inline-flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold mb-6 md:mb-8">
                <span className="relative flex h-2 w-2">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                </span>
                <span>Online Now</span>
                <CheckCircle className="w-4 h-4" />
              </div>

              {/* Main Heading */}
              <h1 className="text-3xl font-bold text-slate-900 leading-tight mb-4 md:text-5xl lg:text-6xl md:leading-[1.1] md:mb-6">
                Connect with a{" "}
                <span className="text-blue-700">verified veterinarian</span>{" "}
                in 15 minutes
              </h1>

              {/* Subheading */}
              <p className="text-slate-600 text-lg mb-8 md:text-xl lg:text-2xl md:mb-10 font-medium">
                Professional video consultations • Digital prescriptions • 
                Trusted by thousands of pet parents across India
              </p>

              {/* Vet Profiles */}
              <div className="flex items-center gap-6 mb-10 md:mb-12">
                <div className="flex -space-x-4">
                  {VETS.map((vet) => (
                    <div key={vet.id} className="relative">
                      <img
                        src={vet.image}
                        alt={vet.name}
                        className="w-12 h-12 rounded-full border-3 border-white object-cover shadow-md md:w-16 md:h-16"
                      />
                      <div className="absolute -bottom-1 -right-1 bg-blue-500 rounded-full p-0.5">
                        <Shield className="w-3 h-3 text-white" />
                      </div>
                    </div>
                  ))}
                  <div className="w-12 h-12 rounded-full border-3 border-white bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700 md:w-16 md:h-16 md:text-base">
                    +30
                  </div>
                </div>
                <div>
                  <div className="text-sm font-semibold text-slate-700 md:text-lg">
                    Verified & Certified Veterinarians
                  </div>
                  <div className="text-sm text-slate-500 md:text-base">
                    Across all major cities in India
                  </div>
                </div>
              </div>

              {/* Features Grid for Desktop */}
              <div className="hidden md:grid md:grid-cols-2 md:gap-6 lg:gap-8 md:mb-12">
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                  <div className="flex items-center gap-3 mb-3">
                    <div className="bg-blue-50 p-2 rounded-lg">
                      <Clock className="w-5 h-5 text-blue-600" />
                    </div>
                    <span className="font-semibold text-slate-800">Fast Response</span>
                  </div>
                  <p className="text-slate-600 text-sm">
                    Average response time of ~15 minutes
                  </p>
                </div>
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                  <div className="flex items-center gap-3 mb-3">
                    <div className="bg-emerald-50 p-2 rounded-lg">
                      <FileText className="w-5 h-5 text-emerald-600" />
                    </div>
                    <span className="font-semibold text-slate-800">Digital Prescription</span>
                  </div>
                  <p className="text-slate-600 text-sm">
                    Legally valid prescriptions included
                  </p>
                </div>
              </div>
            </div>

            {/* Right Column - Trust Card */}
            <div className="hidden md:block md:col-span-5 lg:col-span-5">
              <div className="bg-gradient-to-br from-blue-50 to-white rounded-3xl border border-blue-100 p-8 shadow-lg">
                <div className="flex items-center justify-between mb-6">
                  <div className="text-lg font-bold text-slate-900">Trust & Excellence</div>
                  <div className="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full">
                    98% Satisfaction
                  </div>
                </div>

                {/* Rating Display */}
                <div className="bg-white rounded-2xl border border-slate-100 p-6 mb-6">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="flex">
                      {[...Array(5)].map((_, i) => (
                        <Star key={i} className="w-5 h-5 text-amber-500 fill-amber-500" />
                      ))}
                    </div>
                    <span className="text-lg font-bold text-slate-900">4.9/5</span>
                  </div>
                  <p className="text-slate-600 text-sm">
                    Professional consultations with verified veterinarians
                  </p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-white rounded-xl border border-slate-100 p-4">
                    <div className="text-sm text-slate-500 mb-1">Response Time</div>
                    <div className="text-2xl font-bold text-slate-900">~15m</div>
                  </div>
                  <div className="bg-white rounded-xl border border-slate-100 p-4">
                    <div className="text-sm text-slate-500 mb-1">Prescription</div>
                    <div className="text-2xl font-bold text-slate-900">Included</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Trust Indicators (Mobile & Desktop) */}
        <div className="px-6 py-8 grid grid-cols-2 gap-4 md:px-10 lg:px-16 md:grid-cols-4 md:gap-6 md:py-12">
          {[
            { icon: Clock, color: "blue", label: "Fast Response", value: "~15 mins" },
            { icon: Award, color: "emerald", label: "Certified", value: "Vets" },
            { icon: FileText, color: "purple", label: "Digital", value: "Prescription" },
            { icon: Star, color: "amber", label: "Rated", value: "4.9/5" },
          ].map((item, index) => (
            <div key={index} className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center text-center gap-3 md:p-6 md:rounded-3xl">
              <div className={`bg-${item.color}-50 p-3 rounded-full`}>
                <item.icon className={`w-5 h-5 text-${item.color}-600 md:w-6 md:h-6`} />
              </div>
              <div>
                <div className="text-sm font-semibold text-slate-800 md:text-base">
                  {item.label}
                </div>
                <div className="text-lg font-bold text-slate-900 md:text-xl">
                  {item.value}
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Mission Statement */}
        <div className="px-6 pb-20 md:px-10 lg:px-16 md:pb-24">
          <div className="bg-gradient-to-r from-blue-50 to-slate-50 p-8 rounded-3xl border border-blue-100 md:p-12">
            <div className="flex items-start gap-4 md:gap-6">
              <Heart className="w-6 h-6 text-blue-600 flex-shrink-0 md:w-8 md:h-8" />
              <div>
                <h3 className="text-lg font-bold text-slate-900 mb-3 md:text-xl">
                  Our Promise to Pet Parents
                </h3>
                <p className="text-slate-700 leading-relaxed md:text-lg">
                  "At SnoutIQ, we understand the anxiety of a pet in distress. 
                  That's why we've created a platform where expert veterinary care 
                  is just minutes away. Every consultation is backed by professional 
                  expertise and genuine care for your furry family members."
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Fixed CTA */}
      <div className="
        fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-200 
        md:px-10 lg:px-16 md:py-6
        shadow-[0_-4px_20px_rgba(0,0,0,0.08)]
        md:shadow-[0_-8px_40px_rgba(0,0,0,0.1)]
      ">
        <div className="max-w-7xl mx-auto">
          <div className="md:flex md:items-center md:justify-between md:gap-8">
            <div className="hidden md:block">
              <div className="text-xl font-bold text-slate-900">
                Ready for Professional Veterinary Care?
              </div>
              <div className="text-slate-600">
                Connect with a certified vet in minutes • Digital prescription included
              </div>
            </div>
            
            <Button
              onClick={onStart}
              fullWidth
              className="
                text-lg font-semibold
                bg-gradient-to-r from-blue-700 to-blue-600 
                hover:from-blue-800 hover:to-blue-700
                text-white
                shadow-lg shadow-blue-500/25
                md:w-auto md:text-lg md:px-10 md:py-4 md:rounded-2xl
                transform transition-all hover:scale-[1.02]
              "
            >
              <Stethoscope className="w-5 h-5 mr-2 inline" />
              Consult a Veterinarian
            </Button>
          </div>
          

        </div>
      </div>
    </div>
  );
};

export default LandingScreen;