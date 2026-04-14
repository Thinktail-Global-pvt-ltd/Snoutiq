'use client';

import { useNavigate } from "react-router-dom";
import logo from '../assets/images/logo.png'

export default function NewDoctorOnBoarding() {

  const navigate = useNavigate();

  return (
    <div className="min-h-screen px-6 py-6 bg-gray-50">

      {/* HEADER */}
      <div className="mb-8">
        
        {/* Logo Left */}
        <div className="flex items-center gap-3">
          <img src={logo} alt="logo" className="w-16 h-6 object-contain" />
        </div>

        {/* Center Heading */}
        <div className="text-center mt-6">
          <h1 className="text-2xl font-bold text-gray-900">
            ConsultFlow
          </h1>
          <p className="text-sm text-gray-500">
            Complete your profile
          </p>
        </div>
      </div>

      {/* FORM */}
      <div className="space-y-5">

        {/* NAME */}
        <div>
          <label className="text-sm text-gray-600">Full Name</label>
          <input
            type="text"
            placeholder="Enter your full name"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        {/* CLINIC */}
        <div>
          <label className="text-sm text-gray-600">Clinic Name</label>
          <input
            type="text"
            placeholder="Enter clinic name"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        {/* LICENSE */}
        <div>
          <label className="text-sm text-gray-600">License Number</label>
          <input
            type="text"
            placeholder="Enter license number"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        {/* RESPONSE TIME */}
        <div>
          <label className="text-sm text-gray-600">Response Time</label>
          <select className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400">
            <option value="">Select response time</option>
            <option value="10-15">10 - 15 mins</option>
            <option value="20-30">20 - 30 mins</option>
            <option value="30-40">30 - 40 mins</option>
            <option value="40-60">40 - 60 mins</option>
          </select>
        </div>

        {/* PAYOUT */}
        <div>
          <label className="text-sm text-gray-600">Payout</label>
          <div className="flex gap-3 mt-2">
            <button type="button" className="flex-1 py-3 border rounded-lg bg-green-50 text-green-600 font-semibold">
              Weekly
            </button>
            <button type="button" className="flex-1 py-3 border rounded-lg text-gray-600">
              Monthly
            </button>
          </div>
        </div>

        {/* GOOGLE LINK */}
        <div>
          <label className="text-sm text-gray-600">Google Review URL</label>
          <input
            type="url"
            placeholder="Enter Google review link (optional)"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

        {/* UPI */}
        <div>
          <label className="text-sm text-gray-600">UPI ID</label>
          <input
            type="text"
            placeholder="Enter UPI ID"
            className="w-full mt-1 p-3 border rounded-lg outline-none focus:ring-2 focus:ring-green-400"
          />
        </div>

      </div>

      {/* BUTTON */}
      <div className="mt-8">
        <button
          onClick={() => navigate("/doctor/dashboard")}
          className="w-full py-3 bg-green-500 text-white rounded-lg font-semibold"
        >
          Complete Setup
        </button>
      </div>

    </div>
  );
}