import React, { useCallback, useContext, useEffect, useRef, useState } from "react";
import axios from "axios";
import { AuthContext } from "../auth/AuthContext";
import { socket } from "./socket";
import DoctorAppointmentModal from "./DoctorAppointmentModal";
import LiveDoctorSelectionModal from "./LiveDoctorSelectionModal";

// ------------------- DoctorSearchModal -------------------
const DoctorSearchModal = ({ visible, onClose, onFailure, searchTime = 30000 }) => {
  const [dots, setDots] = useState("");
  const [elapsedTime, setElapsedTime] = useState(0);
  const timeRef = useRef(null);

  useEffect(() => {
    if (visible) {
      setElapsedTime(0);

      // Start timer
      timeRef.current = setInterval(() => {
        setElapsedTime(prev => prev + 1);
      }, 1000);

      const interval = setInterval(() => {
        setDots((prev) => (prev.length >= 3 ? "" : prev + "."));
      }, 500);

      return () => {
        clearInterval(interval);
        if (timeRef.current) {
          clearInterval(timeRef.current);
        }
      };
    } else {
      setDots("");
      setElapsedTime(0);
    }
  }, [visible, searchTime]);

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
  };

  if (!visible) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
      <div className="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl relative overflow-hidden">
        {/* Ripple Effects */}
        <div className="absolute top-8 left-1/2 transform -translate-x-1/2 w-36 h-36 bg-purple-600 rounded-full animate-ping opacity-20"></div>
        <div className="absolute top-8 left-1/2 transform -translate-x-1/2 w-36 h-36 bg-pink-500 rounded-full animate-ping opacity-20 animation-delay-1000"></div>

        {/* Search Icon */}
        <div className="relative z-10 mb-5 flex justify-center">
          <div className="w-20 h-20 bg-gradient-to-r from-purple-500 to-purple-700 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30 animate-pulse">
            <svg className="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <div className="absolute -inset-2 border-2 border-purple-300 rounded-full animate-pulse"></div>
        </div>

        {/* Title */}
        <h2 className="text-2xl font-bold text-gray-900 text-center mb-2">
          Searching for Veterinarians{dots}
        </h2>
        <p className="text-gray-600 text-center mb-6">
          Finding the best available doctors near you
        </p>

        {/* Time Indicator */}
        <div className="flex items-center justify-center gap-2 mb-4 px-4 py-2 bg-purple-50 rounded-lg border border-purple-200">
          <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span className="text-sm font-semibold text-purple-600">
            {formatTime(elapsedTime)}
          </span>
        </div>

        {/* Progress Bar */}
        <div className="w-full h-2 bg-gray-200 rounded-full mb-8 overflow-hidden">
          <div 
            className="h-full bg-gradient-to-r from-purple-500 to-purple-700 rounded-full transition-all duration-300"
            style={{ 
              width: `${Math.min((elapsedTime / (searchTime / 1000)) * 100, 100)}%` 
            }}
          ></div>
        </div>

        {/* Search Indicators */}
        <div className="space-y-3 mb-6 max-h-40 overflow-y-auto">
          <div className="flex items-center p-3 bg-gray-50 rounded-xl border border-gray-200">
            <div className="w-9 h-9 bg-white rounded-full flex items-center justify-center mr-3">
              <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-gray-900">Location Services</p>
              <p className="text-xs text-gray-600">Scanning nearby clinics</p>
            </div>
            <div className="w-4 h-4 border-2 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
          </div>

          <div className="flex items-center p-3 bg-gray-50 rounded-xl border border-gray-200">
            <div className="w-9 h-9 bg-white rounded-full flex items-center justify-center mr-3">
              <svg className="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
              </svg>
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-gray-900">Network Status</p>
              <p className="text-xs text-gray-600">Connected to servers</p>
            </div>
            <svg className="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
            </svg>
          </div>

          <div className="flex items-center p-3 bg-gray-50 rounded-xl border border-gray-200">
            <div className="w-9 h-9 bg-white rounded-full flex items-center justify-center mr-3">
              <svg className="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
            </div>
            <div className="flex-1">
              <p className="text-sm font-semibold text-gray-900">Doctor Availability</p>
              <p className="text-xs text-gray-600">
                {elapsedTime > 10 ? "Expanding search radius" : "Checking schedules"}
              </p>
            </div>
            <span className="text-yellow-600 font-semibold animate-pulse">...</span>
          </div>

          {elapsedTime > 15 && (
            <div className="flex items-center p-3 bg-purple-50 rounded-xl border border-purple-200">
              <div className="w-9 h-9 bg-white rounded-full flex items-center justify-center mr-3">
                <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" />
                </svg>
              </div>
              <div className="flex-1">
                <p className="text-sm font-semibold text-gray-900">Extended Search</p>
                <p className="text-xs text-gray-600">
                  Looking for available veterinarians in wider area
                </p>
              </div>
              <div className="w-4 h-4 border-2 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
          )}
        </div>

        {/* Info Text */}
        <div className="flex items-center justify-center gap-2 mb-6 px-4 py-2 bg-yellow-50 rounded-lg">
          <svg className="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span className="text-xs font-medium text-yellow-800">
            Typically connects within 15-30 seconds
          </span>
        </div>

        {/* Buttons */}
        <div className="flex gap-3">
          <button
            onClick={() => {
              onClose();
              setElapsedTime(0);
            }}
            className="flex-1 py-3 px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={() => {
              onClose();
              setElapsedTime(0);
              onFailure?.();
            }}
            className="flex-1 py-3 px-6 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl transition-colors"
          >
            Try Alternative
          </button>
        </div>
      </div>
    </div>
  );
};

// Enhanced StartCallButton with better error handling and UX
const StartCallButton = ({ navigation, onShowLiveDoctors }) => {
  const [loading, setLoading] = useState(false);
  const [showSearchModal, setShowSearchModal] = useState(false);
  const [callStatus, setCallStatus] = useState(null);
  const { user, token, updateNearbyDoctors, liveDoctors } = useContext(AuthContext);
  const patientId = user?.id || "101";
  const timeoutRef = useRef(null);
  const { updateUser } = useContext(AuthContext);
  const [nearbyDoctors, setNearbyDoctors] = useState([]);
  const [showLiveDoctorsModal, setShowLiveDoctorsModal] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState('idle');
  const [showVideoPaymentModal, setShowVideoPaymentModal] = useState(false);
  const [selectedDoctorForPayment, setSelectedDoctorForPayment] = useState(null);

  // Enhanced doctor fetching with error handling
  const fetchNearbyDoctors = useCallback(async () => {
    if (!token || !user?.id) {
      console.warn("No token or user ID available");
      return;
    }

    try {
      setConnectionStatus('connecting');
      const response = await axios.get(
        `https://snoutiq.com/backend/api/nearby-vets?user_id=${user.id}`,
        { 
          headers: { Authorization: `Bearer ${token}` },
        }
      );

      if (response.data && Array.isArray(response.data.data)) {
        updateNearbyDoctors(response.data.data);
        setNearbyDoctors(response.data.data);
        setConnectionStatus(response.data.data.length > 0 ? 'connected' : 'no_doctors');
      } else {
        setConnectionStatus('no_doctors');
      }
    } catch (error) {
      console.error("Failed to fetch nearby doctors:", error);
      setConnectionStatus('failed');
      
      // Show user-friendly error
      if (error.code === 'NETWORK_ERROR') {
        alert(
          "Connection Error",
          "Unable to connect to the server. Please check your internet connection."
        );
      }
    }
  }, [token, user?.id, updateNearbyDoctors]);

  useEffect(() => {
    if (!token || !user?.id) return;

    const fetchData = async () => {
      await fetchNearbyDoctors();
    };

    fetchData();
    const interval = setInterval(fetchData, 2 * 60 * 1000);

    return () => clearInterval(interval);
  }, [token, user?.id, fetchNearbyDoctors]);

  const handleNoResponse = useCallback(() => {
    setLoading(false);
    setShowSearchModal(false);
    setConnectionStatus('failed');
    setCallStatus(null);

    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }

    if (window.confirm(
      "No Immediate Response\nAll veterinarians are currently busy. You can try again or book a clinic appointment for guaranteed care.\n\nClick OK to see available doctors, or Cancel to try other options."
    )) {
      setConnectionStatus('idle');
      setShowLiveDoctorsModal(true);
    }
  }, []);

  // Enhanced socket listeners with better error handling
  useEffect(() => {
    if (!socket.connected) {
      socket.connect();
    }

    socket.emit("get-active-doctors");

    const handleCallSent = (data) => {
      setCallStatus({ type: "sent", ...data });
      setConnectionStatus('connecting');
    };

    const handleCallAccepted = (data) => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      setCallStatus({ type: "accepted", ...data });
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus('connected');

      const doctor = (nearbyDoctors || []).find((d) => d.id == data.doctorId) ||
        (liveDoctors || []).find((d) => d.id == data.doctorId);

      const patientIdLocal = user?.id || "101";

      // Small delay for smooth UI transition
      setTimeout(() => {
        if (data.requiresPayment) {
          // Handle payment navigation
          console.log("Payment required for call");
        } else {
          // Handle video call navigation
          console.log("Starting video call with:", doctor);
        }
        setCallStatus(null);
      }, 600);
    };

    const handleCallRejected = (data) => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      setCallStatus({ type: "rejected", ...data });
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus('failed');

      if (window.confirm(
        "Call Not Available\nThe veterinarian is currently unavailable. Would you like to try another doctor?"
      )) {
        setCallStatus(null);
        setConnectionStatus('idle');
        setShowLiveDoctorsModal(true);
      }
    };

    const handleCallEnded = () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
      
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus('idle');
      setCallStatus(null);
    };

    const handleSocketError = (error) => {
      console.error("Socket error:", error);
      setConnectionStatus('failed');
      setLoading(false);
      setShowSearchModal(false);
      setCallStatus(null);
      
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
    };

    socket.on("call-sent", handleCallSent);
    socket.on("call-accepted", handleCallAccepted);
    socket.on("call-rejected", handleCallRejected);
    socket.on("call-ended", handleCallEnded);
    socket.on("call-cancelled", handleCallEnded);
    socket.on("error", handleSocketError);
    socket.on("connect_error", handleSocketError);
    socket.on("disconnect", () => {
      setConnectionStatus('failed');
      setLoading(false);
      setShowSearchModal(false);
    });

    return () => {
      socket.off("call-sent", handleCallSent);
      socket.off("call-accepted", handleCallAccepted);
      socket.off("call-rejected", handleCallRejected);
      socket.off("call-ended", handleCallEnded);
      socket.off("call-cancelled", handleCallEnded);
      socket.off("error", handleSocketError);
      socket.off("connect_error", handleSocketError);
      socket.off("disconnect");
      
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
    };
  }, [nearbyDoctors, liveDoctors, user]);

  const startCallWithDoctor = useCallback((doctor) => {
    const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    const channel = `channel_${callId}`;
    const patientIdLocal = user?.id || "101";

    setCallStatus(null);
    setLoading(true);
    setShowLiveDoctorsModal(false);
    setShowSearchModal(true);
    setConnectionStatus('connecting');

    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }

    socket.emit("call-requested", {
      doctorId: doctor.id,
      patientId: patientIdLocal,
      channel,
      callId,
      timestamp: new Date().toISOString(),
      requiresPayment: false,
    });

    timeoutRef.current = setTimeout(() => {
      if (loading || showSearchModal) {
        handleNoResponse();
      }
    }, 30000);
  }, [user?.id, loading, showSearchModal, handleNoResponse]);

  const handleCallDoctor = useCallback((doctor) => {
    setSelectedDoctorForPayment(doctor);
    setShowLiveDoctorsModal(false);
    setShowVideoPaymentModal(true);
  }, []);

  const startCall = useCallback(() => {
    const doctorsToCall = nearbyDoctors && nearbyDoctors.length ? nearbyDoctors : [];

    if (!doctorsToCall.length) {
      alert(
        "No Doctors Available",
        "There are no nearby veterinarians available at the moment. Please try again later or book a clinic appointment."
      );
      return;
    }

    setLoading(true);
    setShowSearchModal(true);
    setConnectionStatus('connecting');

    const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    const channel = `channel_${callId}`;

    try {
      doctorsToCall.forEach((doc) => {
        socket.emit("call-requested", {
          doctorId: doc.id,
          patientId,
          channel,
          callId,
          timestamp: new Date().toISOString(),
        });
      });
    } catch (error) {
      console.error("Error sending call requests:", error);
      handleNoResponse();
      return;
    }

    if (timeoutRef.current) clearTimeout(timeoutRef.current);
    timeoutRef.current = setTimeout(() => {
      if (loading && !callStatus) {
        handleNoResponse();
      }
    }, 30000);
  }, [nearbyDoctors, patientId, loading, callStatus, handleNoResponse]);

  const getButtonState = () => {
    if (loading) return 'loading';
    if (connectionStatus === 'no_doctors' || connectionStatus === 'failed') return 'unavailable';
    if (!nearbyDoctors?.length && !liveDoctors?.length) return 'unavailable';
    return 'available';
  };

  const buttonState = getButtonState();
  const buttonDisabled = buttonState === 'unavailable' || buttonState === 'loading';

  const getButtonText = () => {
    switch (buttonState) {
      case 'loading':
        return 'Searching for Doctors...';
      case 'unavailable':
        return 'No Doctors Available';
      default:
        return 'Start Video Consultation';
    }
  };

  const getButtonIcon = () => {
    switch (buttonState) {
      case 'loading':
        return (
          <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
        );
      case 'unavailable':
        return (
          <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        );
      default:
        return (
          <div className="w-7 h-7 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>
        );
    }
  };

  return (
    <>
      <div className="mb-4">
        <button
          className={`
            w-full relative overflow-hidden rounded-2xl transition-all duration-300 transform hover:scale-105
            ${buttonDisabled ? 'opacity-60 cursor-not-allowed' : 'hover:shadow-xl'}
            ${buttonState === 'loading' ? 'opacity-80' : ''}
          `}
          onClick={() => {
            setShowLiveDoctorsModal(true);
          }}
          disabled={buttonDisabled}
        >
          {!buttonDisabled && buttonState !== 'loading' && (
            <div className="absolute inset-0 bg-purple-600 rounded-2xl shadow-lg shadow-purple-500/50 animate-pulse"></div>
          )}

          <div className={`
            relative w-full py-4 px-6 rounded-2xl bg-gradient-to-r transition-all duration-300
            ${buttonState === 'loading' ? 'from-gray-500 to-gray-600' :
              buttonState === 'unavailable' ? 'from-gray-500 to-gray-600' : 
              'from-purple-600 to-pink-500 hover:from-purple-700 hover:to-pink-600'}
          `}>
            <div className="flex items-center justify-center gap-4">
              {getButtonIcon()}
              <span className="text-white font-bold text-lg tracking-wide">
                {getButtonText()}
              </span>
              {buttonState === 'available' && (
                <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              )}
            </div>
          </div>
        </button>

        {buttonState === 'available' && (
          <div className="flex items-center justify-center gap-2 mt-3">
            <svg className="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
            </svg>
            <span className="text-xs font-semibold text-green-600">
              Licensed veterinarians • Instant connection • Secure call
            </span>
          </div>
        )}

        {buttonState === 'unavailable' && connectionStatus !== 'failed' && (
          <div className="flex items-center justify-center gap-2 mt-3">
            <svg className="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <span className="text-xs font-semibold text-yellow-600">
              Check back soon or book a clinic appointment
            </span>
          </div>
        )}

        {connectionStatus === 'failed' && (
          <div className="flex items-center justify-center gap-2 mt-3">
            <svg className="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span className="text-xs font-semibold text-red-600">
              Connection issue • Click to retry
            </span>
          </div>
        )}
      </div>

      <LiveDoctorSelectionModal
        visible={showLiveDoctorsModal}
        onClose={() => setShowLiveDoctorsModal(false)}
        liveDoctors={liveDoctors}
        onCallDoctor={handleCallDoctor}
        loading={loading}
      />

      {/* Payment step before initiating the call */}
      {showVideoPaymentModal && selectedDoctorForPayment && (
        <VideoCallPaymentModal
          visible={showVideoPaymentModal}
          doctor={selectedDoctorForPayment}
          onClose={() => {
            setShowVideoPaymentModal(false);
            setSelectedDoctorForPayment(null);
          }}
          onSuccess={() => {
            setShowVideoPaymentModal(false);
            const doc = selectedDoctorForPayment;
            setSelectedDoctorForPayment(null);
            // After successful payment, initiate the call (Agora prep happens in call handlers)
            startCallWithDoctor(doc);
          }}
        />
      )}

      <DoctorSearchModal
        visible={showSearchModal}
        onClose={() => {
          setShowSearchModal(false);
          setLoading(false);
          setConnectionStatus('idle');
          if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
            timeoutRef.current = null;
          }
        }}
        onFailure={handleNoResponse}
      />
    </>
  );
};

// ------------------- EmergencyStatusBox -------------------
const EmergencyStatusBox = ({ 
  decision, 
  nearbyDoctors, 
  navigation, 
  messageId, 
  isTypingComplete 
}) => {
  const [showAppointmentModal, setShowAppointmentModal] = useState(false);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    if (decision && isTypingComplete) {
      const timer = setTimeout(() => {
        setIsVisible(true);
      }, 300);

      return () => clearTimeout(timer);
    }
  }, [decision, isTypingComplete]);

  if (!decision || !isTypingComplete || !isVisible) return null;

  if (decision.includes("EMERGENCY")) {
    return (
      <>
        <div className="my-4 mx-6 max-w-[90%] animate-fade-in-up">
          <div className="bg-gradient-to-r from-red-50 to-red-100 rounded-2xl overflow-hidden border border-red-200">
            <div className="p-6">
              <div className="flex items-start gap-4 mb-5">
                <div className="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center shadow-lg shadow-red-500/30">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-2">
                    <div className="w-2 h-2 bg-red-600 rounded-full animate-pulse"></div>
                    <span className="text-xs font-black text-red-600 tracking-wider uppercase">
                      URGENT
                    </span>
                  </div>
                  <h3 className="text-xl font-bold text-red-900 mb-1">
                    Emergency Care Required
                  </h3>
                  <p className="text-red-700 font-medium">
                    Immediate attention needed
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-3 p-4 bg-white bg-opacity-70 rounded-xl border border-red-200 mb-5">
                <svg className="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p className="text-red-800 font-medium">
                  Your pet's symptoms require emergency care. Please contact a veterinarian immediately.
                </p>
              </div>

              <button
                onClick={() => setShowAppointmentModal(true)}
                className="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-3 transition-all transform hover:scale-105 shadow-lg shadow-red-500/30"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Find Emergency Clinic
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={() => setShowAppointmentModal(false)}
          nearbyDoctors={nearbyDoctors}
          onBook={(appointment) => {
            console.log("Appointment booked:", appointment);
            alert(
              "Success",
              `Appointment with ${appointment.doctor.name} on ${appointment.date} at ${appointment.time} booked!`
            );
            setShowAppointmentModal(false);
          }}
        />
      </>
    );
  }

  if (decision.includes("VIDEO_CONSULT")) {
    return (
      <div className="my-4 mx-6 max-w-[90%] animate-fade-in-up">
        <div className="bg-gradient-to-r from-purple-50 to-indigo-100 rounded-2xl overflow-hidden border border-purple-200">
          <div className="p-6">
            <div className="flex items-start gap-4 mb-5">
              <div className="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30">
                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <svg className="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span className="text-xs font-black text-purple-600 tracking-wider uppercase">
                    RECOMMENDED
                  </span>
                </div>
                <h3 className="text-xl font-bold text-purple-900 mb-1">
                  Video Consultation
                </h3>
                <p className="text-purple-700 font-medium">
                  Connect with a vet instantly
                </p>
              </div>
            </div>

            <div className="space-y-2 p-4 bg-white bg-opacity-70 rounded-xl border border-purple-200 mb-5">
              <div className="flex items-center gap-3">
                <svg className="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                </svg>
                <span className="text-purple-800 font-medium">Instant consultation</span>
              </div>
              <div className="flex items-center gap-3">
                <svg className="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                </svg>
                <span className="text-purple-800 font-medium">Professional advice</span>
              </div>
            </div>

            <StartCallButton
              nearbyDoctors={nearbyDoctors}
              navigation={navigation}
              onShowLiveDoctors={() => setShowLiveDoctorsModal(true)}
            />
          </div>
        </div>
      </div>
    );
  }

  if (decision.includes("IN_CLINIC")) {
    return (
      <>
        <div className="my-4 mx-6 max-w-[90%] animate-fade-in-up">
          <div className="bg-gradient-to-r from-gray-50 to-blue-50 rounded-2xl overflow-hidden border border-gray-200">
            <div className="p-6">
              <div className="flex items-start gap-4 mb-5">
                <div className="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </div>
                <div className="flex-1">
                  <h3 className="text-xl font-bold text-gray-900 mb-1">
                    Consultation Options
                  </h3>
                  <p className="text-gray-600 font-medium">
                    Choose video call or clinic visit
                  </p>
                </div>
              </div>

              <div className="space-y-4">
                <StartCallButton
                  nearbyDoctors={nearbyDoctors}
                  navigation={navigation}
                  onShowLiveDoctors={() => setShowLiveDoctorsModal(true)}
                />

                <div className="flex items-center my-4">
                  <div className="flex-1 h-px bg-gray-300"></div>
                  <span className="mx-4 text-xs font-semibold text-gray-500 uppercase">OR</span>
                  <div className="flex-1 h-px bg-gray-300"></div>
                </div>

                <button
                  onClick={() => setShowAppointmentModal(true)}
                  className="w-full bg-purple-50 hover:bg-purple-100 text-purple-700 py-4 rounded-xl font-semibold flex items-center justify-center gap-3 transition-all border border-purple-200 hover:border-purple-300"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  Book Clinic Visit
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={() => setShowAppointmentModal(false)}
          nearbyDoctors={nearbyDoctors}
          onBook={(appointment) => {
            console.log("Appointment booked:", appointment);
            alert(
              "Success",
              `Appointment with ${appointment.doctor.name} on ${appointment.date} at ${appointment.time} booked!`
            );
            setShowAppointmentModal(false);
          }}
        />
      </>
    );
  }

  return null;
};

// ------------------- MessageBubble -------------------
const MessageBubble = ({ msg, index, nearbyDoctors, navigation }) => {
  const [isTypingComplete, setIsTypingComplete] = useState(false);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    setIsVisible(true);
  }, []);

  // Check if typing is complete
  useEffect(() => {
    if (msg.sender === "ai" && msg.text && msg.displayedText) {
      if (msg.displayedText.length >= msg.text.length) {
        setIsTypingComplete(true);
      }
    }
  }, [msg.displayedText, msg.text, msg.sender]);

  if (msg.type === "loading") {
    return (
      <div className={`my-2 max-w-[85%] animate-fade-in-up ${isVisible ? 'opacity-100' : 'opacity-0'}`}>
        <div className="flex items-start gap-3">
          <div className="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30">
            <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>

          <div className="bg-white rounded-2xl rounded-tl-sm px-5 py-3 border border-gray-200 shadow-sm">
            <div className="mb-2">
              <span className="text-xs font-semibold text-purple-600">AI analyzing</span>
            </div>
            <div className="flex gap-1.5">
              <div className="w-2 h-2 bg-purple-600 rounded-full opacity-60 animate-bounce"></div>
              <div className="w-2 h-2 bg-purple-600 rounded-full opacity-80 animate-bounce" style={{ animationDelay: '0.1s' }}></div>
              <div className="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const isUser = msg.sender === "user";

  return (
    <>
      <div className={`my-2 max-w-[85%] animate-fade-in-up ${isVisible ? 'opacity-100' : 'opacity-0'} ${isUser ? 'ml-auto' : ''}`}>
        <div className={`flex items-start gap-3 ${isUser ? 'flex-row-reverse' : ''}`}>
          {!isUser && (
            <div className="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30">
              <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
          )}

          <div className={`
            rounded-2xl px-5 py-3 shadow-sm max-w-full
            ${isUser 
              ? 'bg-purple-600 text-white rounded-br-sm' 
              : 'bg-white border border-gray-200 rounded-bl-sm'
            }
          `}>
            <p className={`break-words ${isUser ? 'text-white' : 'text-gray-900'}`}>
              {msg.displayedText || msg.text}
            </p>
          </div>
        </div>

        {!isUser && (
          <div className="text-xs text-gray-500 mt-1 ml-11">
            {new Date(msg.timestamp).toLocaleTimeString([], {
              hour: "2-digit",
              minute: "2-digit",
            })}
          </div>
        )}
      </div>

      {!isUser && msg.decision && (
        <EmergencyStatusBox
          decision={msg.decision}
          nearbyDoctors={nearbyDoctors}
          navigation={navigation}
          messageId={msg.id}
          isTypingComplete={isTypingComplete}
        />
      )}
    </>
  );
};

export { EmergencyStatusBox, MessageBubble, StartCallButton };

// ------------------- VideoCallPaymentModal -------------------
const VideoCallPaymentModal = ({ visible, doctor, onClose, onSuccess }) => {
  const [processing, setProcessing] = useState(false);

  if (!visible) return null;

  const price = doctor?.chat_price || 500;

  const handlePay = async () => {
    try {
      setProcessing(true);
      // TODO: Integrate real payment SDK/API here
      await new Promise((r) => setTimeout(r, 1200));
      onSuccess?.();
    } catch (e) {
      console.error("Payment error", e);
      onClose?.();
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-xl font-bold text-gray-900">Confirm Video Call</h3>
          <button onClick={onClose} className="p-2 hover:bg-gray-100 rounded-lg">
            <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div className="flex items-center gap-3 mb-4">
          <div className="w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold">
            {(doctor?.business_status || doctor?.name || 'DR').substring(0,2).toUpperCase()}
          </div>
          <div>
            <p className="font-semibold text-gray-900">Dr. {doctor?.business_status || doctor?.name || 'Veterinarian'}</p>
            <p className="text-sm text-gray-600">Video consultation</p>
          </div>
        </div>

        <div className="bg-gray-50 rounded-xl p-4 mb-4">
          <div className="flex items-center justify-between">
            <span className="text-gray-600">Consultation Fee</span>
            <span className="text-lg font-bold text-green-600">₹{price}</span>
          </div>
        </div>

        <button
          onClick={handlePay}
          disabled={processing}
          className={`w-full py-3 rounded-xl text-white font-bold transition-all ${processing ? 'bg-gray-400' : 'bg-purple-600 hover:bg-purple-700'}`}
        >
          {processing ? 'Processing...' : 'Pay & Connect'}
        </button>

        <button
          onClick={onClose}
          disabled={processing}
          className="w-full mt-2 py-3 rounded-xl text-gray-700 font-semibold hover:bg-gray-100"
        >
          Cancel
        </button>
      </div>
    </div>
  );
};