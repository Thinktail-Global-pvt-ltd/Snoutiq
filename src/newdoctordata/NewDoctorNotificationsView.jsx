'use client';

import { ArrowLeft, Bell } from "lucide-react";
import { useNavigate } from "react-router-dom";

export default function NewDoctorNotificationsView() {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-[#FCFCFC] flex flex-col">

      {/* HEADER */}
         <div className="flex items-center gap-3 px-5 h-[68px] bg-[#16a34a] text-white shadow-[0_2px_12px_rgba(0,0,0,0.08)]">
        <button onClick={() => navigate(-1)}>
          <ArrowLeft size={22} />
        </button>
        <h1 className="text-[18px] font-bold">
          Scheduled Notifications
        </h1>
      </div>

      {/* CONTENT (FULL HEIGHT CENTER) */}
      <div className="flex-1 flex flex-col items-center justify-center text-center px-4">
        
        <div className="p-4 bg-gray-200 rounded-full mb-4">
          <Bell size={28} className="text-gray-500" />
        </div>

        <p className="text-gray-500 text-sm">
          No notifications queued
        </p>

      </div>
    </div>
  );
}