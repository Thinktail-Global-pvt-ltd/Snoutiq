'use client';

import { ArrowLeft, Bell } from "lucide-react";
import { useNavigate } from "react-router-dom";

export default function NewDoctorNotificationsView() {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-gray-100 flex flex-col">

      {/* HEADER */}
      <div className="flex items-center gap-3 px-4 py-4 bg-green-600 text-white">
        <button onClick={() => navigate(-1)}>
          <ArrowLeft size={20} />
        </button>
        <h1 className="text-base font-semibold">
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