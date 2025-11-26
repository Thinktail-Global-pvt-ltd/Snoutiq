import React from "react";
import { Send, Edit2, Users, FileText } from "lucide-react";

const VetFollowUp = () => {
  const templates = [
    { id: 1, title: "Checkup Reminder", body: "Hi {name}, it's time for a checkup. Book now to ensure...", type: "General" },
    { id: 2, title: "Vaccination Due", body: "Hi {name}, your pet's vaccination is due next week...", type: "Health" },
    { id: 3, title: "Refill Reminder", body: "Hi {name}, refill for {medicine} is available at the clinic...", type: "Medication" },
  ];

  const audienceSegments = [
    { id: 1, title: "Inactive > 30 Days", count: 48, description: "Patients who haven't visited in a month", color: "bg-gray-100 text-gray-700" },
    { id: 2, title: "Vaccination Due", count: 27, description: "Due for core vaccines in next 7 days", color: "bg-red-50 text-red-700" },
    { id: 3, title: "Refill Reminders", count: 14, description: "Ongoing prescription refills needed", color: "bg-blue-50 text-blue-700" },
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900">Engagement</h2>
        <p className="text-gray-500 text-sm">Send targeted campaigns using templates and smart segments.</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Templates Section */}
        <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm h-fit">
          <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <FileText size={18} className="text-blue-600" /> Templates
          </h3>

          <div className="space-y-4">
            {templates.map((t) => (
              <div key={t.id} className="p-4 border border-gray-100 rounded-xl hover:border-blue-200 transition-colors bg-gray-50/50">
                <div className="font-semibold text-gray-800 text-sm mb-1">{t.title}</div>

                <p className="text-xs text-gray-500 line-clamp-2 mb-3 font-mono leading-relaxed bg-white p-2 rounded border border-gray-100">
                  {t.body}
                </p>

                <div className="flex gap-2">
                  <button className="flex-1 bg-blue-600 text-white py-1.5 rounded-lg text-xs font-medium hover:bg-blue-700 transition-colors">
                    Send
                  </button>
                  <button className="px-3 bg-white border border-gray-200 text-gray-600 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-50">
                    Edit
                  </button>
                </div>
              </div>
            ))}

            <button className="w-full py-2 border border-dashed border-gray-300 rounded-xl text-xs font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors">
              + Create New Template
            </button>
          </div>
        </div>

        {/* Audience Section */}
        <div className="lg:col-span-2 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
          <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <Users size={18} className="text-blue-600" /> Audience
          </h3>

          <p className="text-sm text-gray-500 mb-6">Filter customers by inactivity, vaccination due date, or refill needs.</p>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {audienceSegments.map((seg) => (
              <div key={seg.id} className="p-5 border border-gray-100 rounded-xl hover:shadow-sm transition-shadow">
                <div className="flex justify-between items-start mb-2">
                  <div className="font-bold text-gray-800">{seg.title}</div>
                  <span className={`px-2 py-0.5 rounded text-xs font-bold ${seg.color}`}>
                    Count: {seg.count}
                  </span>
                </div>

                <p className="text-xs text-gray-500 mb-4 h-8">{seg.description}</p>

                <button className="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                  <Send size={14} /> Send Reminder
                </button>
              </div>
            ))}

            {/* Custom Segment Card */}
            <div className="p-5 border border-gray-100 rounded-xl hover:shadow-sm transition-shadow flex flex-col justify-center items-center text-center bg-gray-50/50">
              <div className="font-bold text-gray-800 mb-1">Custom Segment</div>
              <p className="text-xs text-gray-500 mb-4">Create custom audience based on tags</p>
              <button className="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                Create
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
};

export default VetFollowUp;
