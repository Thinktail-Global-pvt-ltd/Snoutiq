import React, { lazy, useEffect, useState } from "react";
import axiosClient from "../axios";
import playstore from '../assets/images/googlePlay.webp'

export default function RightSidebar({ isMobile = false, onItemClick }) {
  const [activeTab, setActiveTab] = useState('vets');

  const handleItemClick = () => {
    if (isMobile && onItemClick) {
      onItemClick();
    }
  };


 
  // ---------------- MOBILE VERSION ----------------
  if (isMobile) {
    return (
      <div className="w-full space-y-6 p-4 bg-[#EFF6FF]">
   
        {/* AI Assistance Stats */}
     

        {/* Service Providers Tabs */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-200">
          <div className="flex border-b border-gray-200">
            <button
              className={`flex-1 py-3 text-sm font-medium ${activeTab === 'vets' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'}`}
              onClick={() => setActiveTab('vets')}
            >
              Nearby Vets
            </button>
            <button
              className={`flex-1 py-3 text-sm font-medium ${activeTab === 'groomers' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'}`}
              onClick={() => setActiveTab('groomers')}
            >
              Groomers
            </button>
          </div>
          
          <div className="p-4 max-h-60 overflow-y-auto">
            {/* {activeTab === 'vets' ? (
              <VetList data={data} handleItemClick={handleItemClick} />
            ) : (
              <GroomerList data={data} handleItemClick={handleItemClick} />
            )} */}
          </div>
        </div>

        {/* Special Offer */}
        <div className="bg-gradient-to-r from-[#34D399] to-[#059669] rounded-xl p-5 text-white shadow">
          <h3 className="text-lg font-semibold mb-2">🎉 Special Offer</h3>
          <p className="text-sm mb-3">₹100 off on all video consults</p>
          <button className="bg-white text-green-600 text-sm font-semibold px-4 py-1.5 rounded-full hover:bg-gray-100 transition-colors">
            Claim Now
          </button>
        </div>

        {/* App Download */}
        <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-200 text-center">
          <h3 className="font-semibold text-gray-800 mb-2">Get Our App</h3>
          <p className="text-sm text-gray-600 mb-3">Better experience on mobile</p>
          <a
            href="https://play.google.com/store/apps/details?id=your.app.id"
            target="_blank"
            rel="noopener noreferrer"
          >
            <img
              src={playstore}
              alt="Get it on Google Play"
            />
          </a>
        </div>
      </div>
    );
  }

  // ---------------- DESKTOP VERSION ----------------
  return (
    <div className="relative w-62 bg-[#EFF6FF] border-l border-gray-200 overflow-y-auto px-4 py-6 space-y-5 custom-scroll">
   
  

      {/* Service Providers Tabs */}
      <div className="bg-white rounded-lg border border-gray-200">
        <div className="flex border-b border-gray-200">
          <button
            className={`flex-1 py-2 text-xs font-medium ${activeTab === 'vets' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'}`}
            onClick={() => setActiveTab('vets')}
          >
            Nearby Vets
          </button>
          <button
            className={`flex-1 py-2 text-xs font-medium ${activeTab === 'groomers' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'}`}
            onClick={() => setActiveTab('groomers')}
          >
            Groomers
          </button>
        </div>
        
        <div className="p-3 max-h-48 overflow-y-auto">
          {/* {activeTab === 'vets' ? (
            <VetList data={data} handleItemClick={handleItemClick} />
          ) : (
            <GroomerList data={data} handleItemClick={handleItemClick} />
          )} */}
        </div>
      </div>

      {/* Special Offer */}
      <div className="bg-gradient-to-r from-[#9B51E0] to-[#2761E8] rounded-xl p-4 text-white shadow">
        <h4 className="font-semibold mb-2 text-sm">✨ Limited Time Offer</h4>
        <p className="text-xs mb-3">₹100 off on all video consults</p>
        <button className="bg-white text-indigo-600 text-xs font-semibold px-3 py-1 rounded-full hover:bg-gray-100 transition-colors">
          Claim Offer
        </button>
      </div>

      {/* Emergency Contact */}
      <div className="bg-red-50 rounded-lg p-4 border border-red-200">
        <h3 className="font-semibold text-red-800 mb-2 text-sm">🚨 Emergency</h3>
        <p className="text-xs text-red-600 mb-3">Immediate veterinary care</p>
        <button className="w-full bg-red-600 text-white text-xs font-semibold py-2 rounded-lg hover:bg-red-700 transition-colors">
          Call Emergency Vet
        </button>
      </div>

      {/* App Download - Sticky at bottom */}
      <div className="sticky bottom-4 bg-white rounded-lg p-3 shadow-lg border border-gray-200">
        <p className="text-xs text-gray-600 mb-2 text-center">Better experience on our app</p>
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
            // className="h-8 mx-auto"
          />
        </a>
      </div>
    </div>
  );
}