'use client';

import logo from "../assets/images/logo.png";
import {
  Bell,
  IndianRupee,
  Plus,
  Search,
  Calendar,
  Upload,
} from "lucide-react";
import { useNavigate } from "react-router-dom"; 

export default function NewDoctorDashboardView() {
    const navigate = useNavigate();
  return (
    <div className="min-h-screen ">

      {/* HEADER (App Style) */}
      <div className="flex items-center justify-between px-5 py-4 bg-white ">
        <img src={logo} alt="logo" className="w-16 h-6 object-contain" />

        <button className="relative p-2" onClick={() => navigate("/doctor/notifications")}>
          <Bell size={20} className="text-gray-700" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>
      </div>

      {/* CONTENT */}
      <div className="px-5 py-4">

        {/* WELCOME */}
        <div className="mb-5">
          <h1 className="text-lg font-semibold text-gray-700">
            Welcome Back 👋
          </h1>
          <p className="text-xl font-bold text-gray-900">
            Dr. Ankit
          </p>
        </div>

        {/* STATS */}
        <div className="grid grid-cols-2 gap-4 mb-6">
          <div className="p-4 rounded-xl bg-green-100">
            <p className="text-xs text-gray-600">Revenue</p>
            <h2 className="text-xl font-bold text-green-700 flex items-center gap-1">
              <IndianRupee size={16} /> 12,500
            </h2>
          </div>

          <div className="p-4 rounded-xl bg-blue-100">
            <p className="text-xs text-gray-600">Follow-ups</p>
            <h2 className="text-xl font-bold text-blue-700">
              5 Active
            </h2>
          </div>
        </div>

        {/* QUICK ACTIONS */}
         <div className="grid grid-cols-2 gap-3">
            <button className="p-5 rounded-2xl bg-green-500 text-white flex flex-col items-center text-center" onClick={() => navigate("/doctor/new-request")}>
              <Plus size={22} />
              <p className="mt-2 text-sm font-semibold">New Request</p>
            </button>

            <button className="p-5 rounded-2xl bg-white flex flex-col items-center text-center shadow-sm" onClick={() => navigate("/doctor/search")}>
              <Search size={22} className="text-gray-700" />
              <p className="mt-2 text-sm font-semibold text-gray-700">
                Search Parent
              </p>
            </button>
          </div>
 

        {/* CSV UPLOAD (FIXED + CLEAN) */}
        <div className="mb-6 mt-5">
          <h2 className="text-sm font-semibold text-gray-700 mb-3">
            Import Data
          </h2>

          <div className="flex items-center justify-between p-4 border border-dashed rounded-xl bg-white">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-gray-100 rounded-lg">
                <Upload size={18} className="text-gray-600" />
              </div>
              <div>
                <p className="text-sm font-semibold text-gray-800">
                  Upload CSV
                </p>
                <p className="text-xs text-gray-500">
                  Import clients & history
                </p>
              </div>
            </div>

            <button className="text-sm font-semibold text-green-600">
              Choose File
            </button>
          </div>
        </div>

        {/* FOLLOW UPS */}
        <div>
          <h2 className="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <Calendar size={16} /> Upcoming Follow-ups
          </h2>

          <div className="space-y-3">
            <div className="p-4 bg-white border rounded-xl">
              <p className="font-semibold text-gray-900">Tommy</p>
              <p className="text-xs text-gray-500">Rahul Sharma</p>
              <p className="text-sm text-gray-600 mt-1">
                Today, 5:30 PM
              </p>
            </div>

            <div className="p-4 bg-white border rounded-xl">
              <p className="font-semibold text-gray-900">Bruno</p>
              <p className="text-xs text-gray-500">Amit Verma</p>
              <p className="text-sm text-gray-600 mt-1">
                Tomorrow, 11:15 AM
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
}