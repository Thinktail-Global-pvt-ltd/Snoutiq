import React, { useState } from "react";
import {
  Sparkles,
  MessageCircle,
  Bell,
  Clock,
  ToggleLeft,
  ToggleRight,
  CheckCircle2,
  Zap,
} from "lucide-react";

const AIAssistantView = () => {
  const [features, setFeatures] = useState({
    symptomChecker: false,
    smartReminders: true,
    predictivePush: false,
  });

  const toggleFeature = (key) => {
    setFeatures((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };

  return (
    <div className="space-y-8 animate-in fade-in duration-500 max-w-5xl">
      {/* Hero Section */}
      <div className="relative bg-gradient-to-r from-indigo-600 to-purple-700 rounded-3xl p-8 overflow-hidden text-white shadow-lg">
        <div className="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
        <div className="absolute bottom-0 left-0 w-48 h-48 bg-purple-400 opacity-20 rounded-full -ml-10 -mb-10 blur-2xl"></div>

        <div className="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
          <div className="max-w-xl">
            <div className="flex items-center gap-2 mb-3">
              <span className="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                <Sparkles size={12} className="text-yellow-300" /> Premium Suite
              </span>
            </div>
            <h1 className="text-3xl font-bold mb-2">Snoutiq AI Assistant</h1>
            <p className="text-indigo-100 leading-relaxed">
              Supercharge your clinic with artificial intelligence. Automate
              patient engagement, provide 24/7 triage support, and predict
              revenue opportunities without lifting a finger.
            </p>
          </div>
          <div className="hidden md:block">
            <div className="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/10">
              <div className="text-center">
                <div className="text-2xl font-bold">120+</div>
                <div className="text-xs text-indigo-200">Hours Saved / Mo</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Features Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Symptom Checker Card */}
        <div
          className={`
            rounded-2xl border p-6 transition-all duration-300 relative overflow-hidden group
            ${
              features.symptomChecker
                ? "bg-white border-indigo-200 shadow-md"
                : "bg-gray-50 border-gray-200"
            }
        `}
        >
          <div className="flex justify-between items-start mb-4">
            <div
              className={`p-3 rounded-xl ${
                features.symptomChecker
                  ? "bg-indigo-100 text-indigo-600"
                  : "bg-gray-200 text-gray-500"
              }`}
            >
              <MessageCircle size={24} />
            </div>
            <button
              onClick={() => toggleFeature("symptomChecker")}
              className="text-indigo-600 hover:scale-110 transition-transform"
            >
              {features.symptomChecker ? (
                <ToggleRight size={40} className="text-indigo-600" />
              ) : (
                <ToggleLeft size={40} className="text-gray-400" />
              )}
            </button>
          </div>

          <h3 className="text-lg font-bold text-gray-900 mb-2">
            AI Symptom Checker
          </h3>
          <p className="text-sm text-gray-500 mb-6 min-h-[60px]">
            Embed a smart chat widget on your website. It guides worried pet
            parents through symptoms and directs them to book appointments if
            needed.
          </p>

          <div className="space-y-3">
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> 24/7 Triage
              Support
            </div>
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> Reduces
              Unnecessary Calls
            </div>
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> Leads to
              Booking Engine
            </div>
          </div>

          {features.symptomChecker && (
            <div className="mt-6 pt-4 border-t border-indigo-50 animate-in slide-in-from-bottom-2">
              <button className="w-full py-2 bg-indigo-50 text-indigo-700 text-sm font-bold rounded-lg hover:bg-indigo-100 transition-colors">
                Get Embed Code
              </button>
            </div>
          )}
        </div>

        {/* Smart Reminders Card */}
        <div
          className={`
            rounded-2xl border p-6 transition-all duration-300 relative overflow-hidden group
            ${
              features.smartReminders
                ? "bg-white border-blue-200 shadow-md"
                : "bg-gray-50 border-gray-200"
            }
        `}
        >
          <div className="flex justify-between items-start mb-4">
            <div
              className={`p-3 rounded-xl ${
                features.smartReminders
                  ? "bg-blue-100 text-blue-600"
                  : "bg-gray-200 text-gray-500"
              }`}
            >
              <Clock size={24} />
            </div>
            <button
              onClick={() => toggleFeature("smartReminders")}
              className="text-blue-600 hover:scale-110 transition-transform"
            >
              {features.smartReminders ? (
                <ToggleRight size={40} className="text-blue-600" />
              ) : (
                <ToggleLeft size={40} className="text-gray-400" />
              )}
            </button>
          </div>

          <h3 className="text-lg font-bold text-gray-900 mb-2">
            Smart Automation
          </h3>
          <p className="text-sm text-gray-500 mb-6 min-h-[60px]">
            Automatically schedule reminders based on patient history. The AI
            reads medical notes to suggest follow-ups for vaccinations or
            chronic care.
          </p>

          <div className="space-y-3">
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> Reads EMR
              Notes
            </div>
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> Auto-SMS &
              Email
            </div>
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> +30%
              Retention Rate
            </div>
          </div>

          {features.smartReminders && (
            <div className="mt-6 pt-4 border-t border-blue-50 animate-in slide-in-from-bottom-2">
              <button className="w-full py-2 bg-blue-50 text-blue-700 text-sm font-bold rounded-lg hover:bg-blue-100 transition-colors">
                Configure Rules
              </button>
            </div>
          )}
        </div>

        {/* Push Notifications Card */}
        <div
          className={`
            rounded-2xl border p-6 transition-all duration-300 relative overflow-hidden group
            ${
              features.predictivePush
                ? "bg-white border-pink-200 shadow-md"
                : "bg-gray-50 border-gray-200"
            }
        `}
        >
          <div className="flex justify-between items-start mb-4">
            <div
              className={`p-3 rounded-xl ${
                features.predictivePush
                  ? "bg-pink-100 text-pink-600"
                  : "bg-gray-200 text-gray-500"
              }`}
            >
              <Bell size={24} />
            </div>
            <button
              onClick={() => toggleFeature("predictivePush")}
              className="text-pink-600 hover:scale-110 transition-transform"
            >
              {features.predictivePush ? (
                <ToggleRight size={40} className="text-pink-600" />
              ) : (
                <ToggleLeft size={40} className="text-gray-400" />
              )}
            </button>
          </div>

          <h3 className="text-lg font-bold text-gray-900 mb-2">
            Predictive Push
          </h3>
          <p className="text-sm text-gray-500 mb-6 min-h-[60px]">
            Send push notifications for repeat visits before the parent even
            realizes. AI predicts refill needs and seasonal checkups.
          </p>

          <div className="space-y-3">
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> App Push
              Notifications
            </div>
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> Smart
              Timing Algorithm
            </div>
            <div className="flex items-center gap-2 text-xs text-gray-600">
              <CheckCircle2 size={14} className="text-green-500" /> Boosts
              Repeat Visits
            </div>
          </div>

          {features.predictivePush && (
            <div className="mt-6 pt-4 border-t border-pink-50 animate-in slide-in-from-bottom-2">
              <button className="w-full py-2 bg-pink-50 text-pink-700 text-sm font-bold rounded-lg hover:bg-pink-100 transition-colors">
                View Predictions
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Integration Banner */}
      <div className="bg-gray-900 rounded-2xl p-6 text-gray-300 flex flex-col md:flex-row items-center justify-between gap-6">
        <div className="flex items-center gap-4">
          <div className="p-3 bg-white/10 rounded-full">
            <Zap className="text-yellow-400" size={24} />
          </div>
          <div>
            <h4 className="text-white font-bold text-lg">Snoutiq Cloud Sync</h4>
            <p className="text-sm">
              AI features require active cloud synchronization. Your data is
              encrypted.
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2 bg-green-500/20 text-green-400 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide">
          <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
          System Operational
        </div>
      </div>
    </div>
  );
};

export default AIAssistantView;
