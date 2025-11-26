import React, { useState } from "react";
import {
  Calendar,
  TrendingUp,
  Users,
  Activity,
  Search,
} from "lucide-react";

const VetDashboard = () => {
  const [activeTab, setActiveTab] = useState("add_a_client");

  const stats = [
    { label: "ACTIVE CLIENTS", value: "€45.2k", change: "+8 from yesterday", icon: Users },
    { label: "APPOINTMENTS", value: "12", change: "+3 new", icon: Calendar },
    { label: "PATIENT SATISFACTION", value: "68%", change: "+4%", icon: TrendingUp },
    { label: "ACTIVE HEALTH TOPICS", value: "5", change: "+2%", icon: Activity },
  ];

  const setupCards = [
    { title: "Add a Client", subtitle: "Lorem ipsum dolor sit amet consectetur", status: "active" },
    { title: "Video Calling Schedule", subtitle: "Lorem ipsum dolor sit amet consectetur", status: "inactive" },
    { title: "Click Schedule", subtitle: "Lorem ipsum dolor sit amet consectetur", status: "inactive" },
    { title: "Emergency Health Monitor Tool", subtitle: "Lorem ipsum dolor sit amet consectetur", status: "inactive" },
    { title: "Documents & Compliance", subtitle: "Lorem ipsum dolor sit amet consectetur", status: "inactive" },
  ];

  const appointments = [
    { name: "Milo", time: "Today 10:00 AM", status: "WAITING", avatar: "🐕" },
    { name: "Bella", time: "Today 11:30 AM", status: "UPCOMING", avatar: "🐈" },
    { name: "Charlie", time: "Today 2:00 PM", status: "UPCOMING", avatar: "🐕" },
  ];

  const chartData = Array.from({ length: 7 }, (_, i) => ({
    day: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"][i],
    revenue: Math.random() * 100 + 20,
    telemedicine: Math.random() * 80 + 30
  }));

  return (
    <div className="min-h-screen bg-gray-50">
      {/* HEADER */}
      <header className="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 ">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex flex-col sm:flex-row sm:items-center gap-4">
            <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
          </div>

          <div className="flex items-center gap-3">
            <div className="relative w-full sm:w-auto">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
              <input
                type="text"
                placeholder="Search"
                className="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg 
                focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <button className="bg-blue-600 whitespace-nowrap text-white px-4 py-2 rounded-lg hover:bg-blue-700">
              Book For now
            </button>
          </div>
        </div>
      </header>

      {/* MAIN CONTENT */}
      <div className="p-4 sm:p-6 max-w-7xl mx-auto">
        {/* CLINIC SETUP */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900">Clinic Setup</h2>
            <a href="#" className="text-blue-600 text-sm hover:underline">
              View Fund
            </a>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            {setupCards.map((card, index) => (
              <div
                key={index}
                className={`bg-white rounded-lg p-4 border-2 ${
                  card.status === "active" ? "border-blue-600" : "border-gray-200"
                } hover:shadow-md transition-shadow cursor-pointer`}
              >
                <div className="flex items-start gap-3">
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center ${
                      card.status === "active" ? "bg-blue-100" : "bg-gray-100"
                    }`}
                  >
                    <div
                      className={`w-5 h-5 rounded-full ${
                        card.status === "active" ? "bg-blue-600" : "bg-gray-400"
                      }`}
                    ></div>
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 text-md mb-1">{card.title}</h3>
                    <p className="text-xs text-gray-500">{card.subtitle}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* STATS GRID */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {stats.map((stat, index) => (
            <div key={index} className="bg-white rounded-lg p-6 shadow-sm">
              <div className="flex items-center justify-between mb-2">
                <stat.icon className="w-5 h-5 text-blue-600" />
                <span className="text-green-600 text-sm">{stat.change}</span>
              </div>
              <div className="text-2xl font-bold text-gray-900 mb-1">{stat.value}</div>
              <div className="text-xs text-gray-500 uppercase">{stat.label}</div>
            </div>
          ))}
        </div>

        {/* CHARTS SECTION */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

          {/* REVENUE */}
          <div className="bg-white rounded-lg p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">Total Revenue</h3>
            <div className="text-2xl font-bold text-gray-900 mb-4">₹ 82,400</div>
            <div className="h-32 flex items-end gap-2">
              {chartData.map((data, i) => (
                <div key={i} className="flex-1 flex flex-col items-center">
                  <div
                    className="w-full bg-blue-200 rounded-t"
                    style={{ height: `${data.revenue}%` }}
                  ></div>
                  <span className="text-xs text-gray-500 mt-2">{data.day}</span>
                </div>
              ))}
            </div>
          </div>

          {/* TELEMEDICINE */}
          <div className="bg-white rounded-lg p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">Telemedicine</h3>
            <div className="text-2xl font-bold text-gray-900 mb-4">6.2%</div>
            <div className="h-32 flex items-end gap-2">
              {chartData.map((data, i) => (
                <div key={i} className="flex-1 flex flex-col items-center">
                  <div
                    className="w-full bg-purple-200 rounded-t"
                    style={{ height: `${data.telemedicine}%` }}
                  ></div>
                  <span className="text-xs text-gray-500 mt-2">{data.day}</span>
                </div>
              ))}
            </div>
          </div>

          {/* ENGAGEMENT */}
          <div className="bg-white rounded-lg p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">Engagement Overview</h3>
            <div className="text-2xl font-bold text-gray-900 mb-4">48%</div>

            <div className="space-y-3">
              {[
                "Reached - 30 Days",
                "Email Sent to Reminder",
                "Verifiable Due",
                "Total Sent to Reminder",
              ].map((label) => (
                <div className="flex items-center justify-between text-sm" key={label}>
                  <span className="text-gray-600">{label}</span>
                  <button className="text-blue-600 text-xs">12 patients</button>
                </div>
              ))}
              <button className="text-blue-600 text-sm hover:underline mt-2">
                Send All Reminder
              </button>
            </div>
          </div>
        </div>

        {/* BOTTOM SECTION */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          {/* ACTIVE APPOINTMENTS */}
          <div className="bg-white rounded-lg p-6 shadow-sm">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-semibold text-gray-900">Active Appointments</h3>
              <a href="#" className="text-blue-600 text-sm hover:underline">View All</a>
            </div>

            <div className="space-y-4">
              {appointments.map((apt, index) => (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-xl">
                      {apt.avatar}
                    </div>
                    <div>
                      <div className="font-semibold text-gray-900">{apt.name}</div>
                      <div className="text-xs text-gray-500">{apt.time}</div>
                    </div>
                  </div>

                  <span
                    className={`px-3 py-1 rounded-full text-xs font-semibold ${
                      apt.status === "WAITING"
                        ? "bg-orange-100 text-orange-700"
                        : "bg-blue-100 text-blue-700"
                    }`}
                  >
                    {apt.status}
                  </span>
                </div>
              ))}
            </div>
          </div>

          {/* RETENTION */}
          <div className="bg-white rounded-lg p-6 shadow-sm flex items-center justify-center">
            <div className="text-center">
              <h3 className="text-sm font-semibold text-gray-900 mb-4">Retention</h3>
              <div className="relative w-32 h-32 mx-auto">
                <svg className="transform -rotate-90 w-full h-full">
                  <circle cx="64" cy="64" r="56" stroke="#e5e7eb" strokeWidth="12" fill="none" />
                  <circle
                    cx="64"
                    cy="64"
                    r="56"
                    stroke="#10b981"
                    strokeWidth="12"
                    fill="none"
                    strokeDasharray={`${2 * Math.PI * 56 * 0.7} ${2 * Math.PI * 56}`}
                    className="transition-all duration-1000"
                  />
                </svg>
                <div className="absolute inset-0 flex items-center justify-center">
                  <span className="text-3xl font-bold text-gray-900">70%</span>
                </div>
              </div>
            </div>
          </div>

          {/* CLINIC STATUS */}
          <div className="bg-blue-600 rounded-lg p-6 text-white shadow-sm">
            <h3 className="text-lg font-semibold mb-2">Clinic Status</h3>
            <div className="mb-4">
              <span className="inline-block bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                Operational
              </span>
            </div>
            <p className="text-sm mb-4 opacity-90">
              A timely well operating status. Emergency event is at 35% capacity
            </p>
            <button className="w-full bg-white text-blue-600 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
              View Medical Tests
            </button>
          </div>

        </div>
      </div>
    </div>
  );
};

export default VetDashboard;