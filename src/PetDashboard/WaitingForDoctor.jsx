import React, { useEffect, useState, useRef } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import {
  ClockIcon,
  UserGroupIcon,
  HeartIcon,
  ArrowPathIcon,
  PhoneIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
} from "@heroicons/react/24/outline";

const API_BASE = "https://snoutiq.com/backend";
const POLL_MS = 2000;

const WaitingForDoctor = () => {
  const navigate = useNavigate();
  const [sessionId, setSessionId] = useState(null);
  const [status, setStatus] = useState("searching");
  const [doctorsAvailable, setDoctorsAvailable] = useState(12);
  const [waitTime, setWaitTime] = useState("2-5");
  const [cancelModal, setCancelModal] = useState(false);
  const [connectionAttempts, setConnectionAttempts] = useState(0);
  const [loaderType, setLoaderType] = useState(1);
  const pollRef = useRef(null);
  // Auto accept after 10 seconds (for demo)
useEffect(() => {
  if (status === "searching" || status === "connecting") {
    const timer = setTimeout(() => {
      setStatus("found");
      stopPolling();
      setTimeout(() => navigate(`/payment/${sessionId || "demo-session"}`), 2500);
    }, 10000); // 10 seconds

    return () => clearTimeout(timer);
  }
}, [status, navigate, sessionId]);


  const doctorProfiles = [
    {
      image: "/images/doc1.png",
      name: "Dr. Sarah Johnson",
      specialty: "Veterinary Dermatology",
      rating: 4.9,
      experience: "12 years",
    },
    {
      image: "/images/doc2.png",
      name: "Dr. Michael Chen",
      specialty: "Animal Surgery",
      rating: 4.8,
      experience: "9 years",
    },
    {
      image: "/images/doc3.png",
      name: "Dr. Emily Rodriguez",
      specialty: "Feline Health",
      rating: 4.95,
      experience: "15 years",
    },
    {
      image: "/images/doc4.png",
      name: "Dr. James Wilson",
      specialty: "Canine Nutrition",
      rating: 4.7,
      experience: "7 years",
    },
  ];

  const [currentDoctor, setCurrentDoctor] = useState(0);

  // Rotate through different loader types
  useEffect(() => {
    const interval = setInterval(() => {
      setLoaderType((prev) => (prev % 4) + 1);
    }, 4000);
    return () => clearInterval(interval);
  }, []);

  // Slider effect for doctors
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentDoctor((prev) => (prev + 1) % doctorProfiles.length);
    }, 3000);
    return () => clearInterval(interval);
  }, [doctorProfiles.length]);

  // Simulate doctors available count
  useEffect(() => {
    const interval = setInterval(() => {
      setDoctorsAvailable((prev) => {
        const change = Math.floor(Math.random() * 3) - 1;
        return Math.max(5, Math.min(20, prev + change));
      });
    }, 5000);
    return () => clearInterval(interval);
  }, []);

  // Create call as soon as page loads
  useEffect(() => {
    initiateConnection();
  }, []);

  const initiateConnection = async () => {
    try {
      setStatus("searching");
      setConnectionAttempts((prev) => prev + 1);
      const res = await axios.post(`${API_BASE}/api/call/create`, {
        patient_id: 101,
      });
      setSessionId(res.data.session_id);

      setTimeout(() => setStatus("connecting"), 2000);
      startPolling(res.data.session_id);
    } catch (e) {
      console.error(e);
      setStatus("error");
    }
  };

  const startPolling = (sid) => {
    stopPolling();
    pollRef.current = setInterval(async () => {
      try {
        const res = await axios.get(`${API_BASE}/api/call/${sid}`);
        const s = res.data?.session ?? res.data;
        if (s.status === "accepted") {
          setStatus("found");
          stopPolling();
          setTimeout(() => navigate(`/payment/${sid}`), 2500);
        }
      } catch (e) {
        console.error("Polling error: ", e);
        if (connectionAttempts >= 3) {
          setStatus("retry");
          stopPolling();
        }
      }
    }, POLL_MS);
  };

  const stopPolling = () => {
    if (pollRef.current) clearInterval(pollRef.current);
  };

  const handleCancel = () => {
    stopPolling();
    navigate("/");
  };

  const handleRetry = () => {
    setStatus("searching");
    setTimeout(() => initiateConnection(), 1000);
  };

  const getStatusMessage = () => {
    switch (status) {
      case "searching":
        return "Searching for available veterinarians...";
      case "connecting":
        return "Connecting you with the best match...";
      case "found":
        return "Doctor found! Preparing your consultation...";
      case "error":
        return "Connection issue. Please try again.";
      case "retry":
        return "Having trouble connecting. Would you like to try again?";
      default:
        return "Searching for available veterinarians...";
    }
  };

  const getStatusIcon = () => {
    switch (status) {
      case "searching":
        return <ArrowPathIcon className="w-8 h-8 animate-spin" />;
      case "connecting":
        return <PhoneIcon className="w-8 h-8 animate-pulse" />;
      case "found":
        return <HeartIcon className="w-8 h-8 text-green-500" />;
      case "error":
      case "retry":
        return <ExclamationTriangleIcon className="w-8 h-8 text-amber-500" />;
      default:
        return <ArrowPathIcon className="w-8 h-8 animate-spin" />;
    }
  };

  const renderLoader = () => {
    switch (loaderType) {
      case 1:
        return <DotsLoader />;
      case 2:
        return <WaveLoader />;
      case 3:
        return <CircleLoader />;
      case 4:
        return <BarLoader />;
      default:
        return <DotsLoader />;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-2">
      <div className="max-w-2xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-indigo-600 to-purple-600 p-4 text-white text-center">
          <div className="flex items-center justify-center mb-3">
            <ShieldCheckIcon className="w-8 h-8 mr-2" />
            <h1 className="text-2xl font-bold">Snoutiq</h1>
          </div>
          <p className="opacity-90">Real-time video consultations with certified veterinarians</p>
        </div>

        {/* Main Content */}
        <div className="p-4">
          {/* Status Indicator */}
          <div className="text-center mb-8">
            <div className="flex justify-center mb-4">
              <div className="p-3 bg-indigo-100 rounded-full shadow-inner">
                {getStatusIcon()}
              </div>
            </div>
            <h2 className="text-xl font-semibold text-gray-800 mb-2">
              {getStatusMessage()}
            </h2>
            <p className="text-gray-600 text-sm">Your pet's health is our priority</p>
          </div>

          {/* Loader Animation */}
          <div className="mb-8 flex justify-center">
            {(status === "searching" || status === "connecting") && renderLoader()}
          </div>

          {/* Doctor Avatars Professional Slider */}
          {status !== "retry" && (
  <div className="relative mb-4">
    <div className="text-center mb-4">
      <p className="text-sm text-gray-600 font-medium">Available Veterinarians</p>
    </div>

    <div className="relative w-full overflow-hidden h-20 flex justify-center">
      <div className="flex items-center space-x-6 animate-avatar-scroll">
        {doctorProfiles.concat(doctorProfiles).concat(doctorProfiles).map((doc, idx) => (
          <div key={idx} className="flex flex-col items-center flex-shrink-0">
            <div className="w-12 h-12 rounded-full overflow-hidden border-2 border-white shadow-md bg-gray-100 relative">
              <img
                src={doc.image}
                alt={doc.name}
                className="w-full h-full object-cover"
              />
              <div className="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
            </div>
            <span className="text-xs text-gray-500 mt-1">Online</span>
          </div>
        ))}
      </div>

      {/* Gradient edges for professional look */}
      <div className="pointer-events-none absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-white"></div>
      <div className="pointer-events-none absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-white"></div>
    </div>

    <style>{`
      @keyframes avatar-scroll {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-33.33%); }
      }
      .animate-avatar-scroll {
        display: flex;
        width: max-content;
        animation: avatar-scroll 20s linear infinite;
      }
    `}</style>
  </div>
)}


          {/* Progress Bar */}
          {status !== "retry" && (
            <div className="mb-8">
              <div className="flex justify-between text-sm text-gray-600 mb-2">
                <span className={status === "searching" ? "font-semibold text-indigo-600" : ""}>Searching</span>
                <span className={status === "connecting" ? "font-semibold text-indigo-600" : ""}>Connecting</span>
                <span className={status === "found" ? "font-semibold text-indigo-600" : ""}>Connected</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2.5">
                <div
                  className={`h-2.5 rounded-full transition-all duration-1000 ease-out ${
                    status === "searching"
                      ? "bg-gradient-to-r from-blue-400 to-blue-600 w-1/3"
                      : status === "connecting"
                      ? "bg-gradient-to-r from-indigo-400 to-indigo-600 w-2/3"
                      : status === "found"
                      ? "bg-gradient-to-r from-green-400 to-green-600 w-full"
                      : "bg-gray-400 w-1/3"
                  }`}
                ></div>
              </div>
            </div>
          )}

          {/* Action Buttons */}
          <div className="flex space-x-4">
            {status === "retry" ? (
              <>
                <button
                  onClick={() => setCancelModal(true)}
                  className="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md"
                >
                  Cancel
                </button>
                <button
                  onClick={handleRetry}
                  className="flex-1 py-3 px-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center"
                >
                  <ArrowPathIcon className="w-5 h-5 mr-2" />
                  Try Again
                </button>
              </>
            ) : (
              <>
                <button
                  onClick={() => setCancelModal(true)}
                  className="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md"
                >
                  Cancel
                </button>
                <button
                  onClick={() => window.location.reload()}
                  className="flex-1 py-3 px-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center"
                >
                  <ArrowPathIcon className="w-5 h-5 mr-2" />
                  Refresh
                </button>
              </>
            )}
          </div>
        </div>

        {/* Footer Note */}
        <div className="bg-gray-50 p-4 border-t border-gray-200 text-center">
          <p className="text-sm text-gray-600">
            Having issues? Contact support at{" "}
            <span className="text-indigo-600 font-medium">help@petconnect.com</span>
          </p>
        </div>
      </div>

      {/* Cancel Confirmation Modal */}
      {cancelModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl max-w-md w-full p-6 mx-4">
            <div className="text-center mb-4">
              <XMarkIcon className="w-12 h-12 text-red-500 mx-auto mb-3" />
              <h3 className="text-lg font-semibold text-gray-800">Cancel Consultation?</h3>
            </div>
            <p className="text-gray-600 text-center mb-6">
              Are you sure you want to cancel your veterinary consultation? You
              will lose your place in the queue.
            </p>
            <div className="flex space-x-4">
              <button
                onClick={() => setCancelModal(false)}
                className="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors"
              >
                Continue Waiting
              </button>
              <button
                onClick={handleCancel}
                className="flex-1 py-3 px-4 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors"
              >
                Cancel Consultation
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Connection Success Animation */}
      {status === "found" && (
        <div className="fixed inset-0 bg-green-500 bg-opacity-95 flex items-center justify-center z-50">
          <div className="text-center text-white p-6">
            <div className="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-2xl">
              <svg
                className="w-12 h-12 text-green-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="M5 13l4 4L19 7"
                ></path>
              </svg>
            </div>
            <h2 className="text-2xl font-bold mb-2">Veterinarian Connected!</h2>
            <p className="text-lg opacity-90">Redirecting to payment...</p>
          </div>
        </div>
      )}

      <style>{`
        @keyframes avatar-scroll {
          0% { transform: translateX(0); }
          100% { transform: translateX(-50%); }
        }
        .animate-avatar-scroll {
          display: flex;
          width: max-content;
          animation: avatar-scroll 20s linear infinite;
        }
        
        @keyframes wave {
          0%, 100% { height: 4px; transform: translateY(0px); }
          50% { height: 16px; transform: translateY(-6px); }
        }
        
        @keyframes progress {
          0% { width: 0%; }
          50% { width: 70%; }
          100% { width: 100%; }
        }
      `}</style>
    </div>
  );
};

// Loader Components
const DotsLoader = () => (
  <div className="flex space-x-2">
    {[0, 1, 2].map((i) => (
      <div
        key={i}
        className="w-3 h-3 bg-gradient-to-r from-indigo-400 to-purple-500 rounded-full animate-bounce"
        style={{ animationDelay: `${i * 0.15}s` }}
      ></div>
    ))}
  </div>
);

const WaveLoader = () => (
  <div className="flex space-x-1">
    {[0, 1, 2, 3, 4].map((i) => (
      <div
        key={i}
        className="w-2 h-4 bg-gradient-to-r from-indigo-400 to-purple-500 rounded-full"
        style={{
          animation: "wave 1.2s ease-in-out infinite",
          animationDelay: `${i * 0.1}s`,
        }}
      ></div>
    ))}
  </div>
);

const CircleLoader = () => (
  <div className="w-10 h-10 border-3 border-indigo-100 border-t-indigo-500 rounded-full animate-spin"></div>
);


const BarLoader = () => (
  <div className="w-32 h-1.5 bg-gray-200 rounded-full overflow-hidden">
    <div className="h-full bg-indigo-600 rounded-full animate-progress"></div>
    <style>{`
      @keyframes progress {
        0% { width: 0%; }
        50% { width: 70%; }
        100% { width: 100%; }
      }
      .animate-progress {
        animation: progress 2s ease-in-out infinite;
      }
    `}</style>
  </div>
);

export default WaitingForDoctor;
