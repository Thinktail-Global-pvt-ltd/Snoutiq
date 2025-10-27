import React, {
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
} from "react";
import axios from "axios";
import { AuthContext } from "../auth/AuthContext";
import { socket } from "./socket";
import DoctorAppointmentModal from "./DoctorAppointmentModal";
import LiveDoctorSelectionModal from "./LiveDoctorSelectionModal";
import toast from "react-hot-toast";

// ------------------- Helper function to parse and format AI response -------------------
const parseAIResponse = (text) => {
  const sections = {
    recommendation: null,
    whyAppropriate: [],
    howToPrepare: [],
    nextSteps: [],
    diagnosis: null,
  };

  // Extract diagnosis section
  const diagnosisMatch = text.match(/=== DIAGNOSIS ===([\s\S]*?)=== END ===/);
  if (diagnosisMatch) {
    sections.diagnosis = diagnosisMatch[1].trim();
  }

  // Extract main recommendation with bold support
  const recMatch = text.match(/\*\*([^*]+)\*\*/);
  if (recMatch) {
    const recText = recMatch[1].trim();
    const blockedHeadings = [
      "What you're seeing:",
      "What you're seeing",
      "Specific Observations:",
      "Specific Observations",
      "Observation Summary:",
      "Observation Summary",
    ];
    if (
      !blockedHeadings.some(
        (heading) => recText.toLowerCase() === heading.toLowerCase()
      )
    ) {
      sections.recommendation = recText; // Store raw text, bold will be handled in rendering
    }
  }

  // Extract and format WHY VIDEO IS APPROPRIATE with bold support
  const whyMatch = text.match(
    /\*\*WHY VIDEO IS APPROPRIATE:\*\*([\s\S]*?)(?=\*\*HOW TO PREPARE:|\*\*NEXT STEPS:|=== DIAGNOSIS|$)/
  );
  if (whyMatch) {
    const content = whyMatch[1].trim();
    const bullets = content.match(/• ([^\n]+)/g) || [];
    sections.whyAppropriate = bullets.map((b) => b.replace("• ", "").trim());
  }

  // Extract and format HOW TO PREPARE with bold support
  const prepMatch = text.match(
    /\*\*HOW TO PREPARE:\*\*([\s\S]*?)(?=\*\*NEXT STEPS:|=== DIAGNOSIS|$)/
  );
  if (prepMatch) {
    const content = prepMatch[1].trim();
    const bullets = content.match(/• ([^\n]+)/g) || [];
    sections.howToPrepare = bullets.map((b) => b.replace("• ", "").trim());
  }

  // Extract and format NEXT STEPS with bold support
  const stepsMatch = text.match(
    /\*\*NEXT STEPS:\*\*([\s\S]*?)(?==== DIAGNOSIS|$)/
  );
  if (stepsMatch) {
    const content = stepsMatch[1].trim();
    const bullets = content.match(/• ([^\n]+)/g) || [];
    sections.nextSteps = bullets.map((b) => b.replace("• ", "").trim());
  }

  return sections;
};

// ------------------- FormattedAIResponse Component - Responsive -------------------
const FormattedAIResponse = ({ text }) => {
  const sections = parseAIResponse(text);

  // Function to render text with bold formatting
  const renderWithBold = (text) => {
    if (!text) return null;
    const parts = text.split(/(\*\*[^*]+\*\*)/g); // Split by bold markers
    return parts.map((part, index) => {
      if (part.startsWith("**") && part.endsWith("**")) {
        return (
          <span key={index} className="font-bold">
            {part.replace(/^\*\*|\*\*$/g, '')}
          </span>
        );
      }
      return <span key={index}>{part}</span>;
    });
  };

  return (
    <div className="bg-gradient-to-br from-purple-50 via-white to-indigo-50 rounded-xl lg:rounded-2xl p-4 sm:p-5 lg:p-6 border-2 border-purple-200 shadow-lg max-w-full">
      {/* Header with Main Recommendation */}
      {sections.recommendation && (
        <div className="mb-4 lg:mb-5">
          <div className="flex items-start gap-2 sm:gap-3 mb-3 lg:mb-4">
            <div className="w-8 h-8 sm:w-9 sm:h-9 lg:w-10 lg:h-10 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg flex-shrink-0">
              <span className="text-base sm:text-lg lg:text-xl">💡</span>
            </div>
            <div className="flex-1 min-w-0">
              <h3 className="font-bold text-purple-900 text-base sm:text-lg lg:text-xl leading-tight break-words">
                {renderWithBold(sections.recommendation)}
              </h3>
            </div>
          </div>
        </div>
      )}

      {/* Divider */}
      {sections.recommendation &&
        (sections.whyAppropriate.length > 0 ||
          sections.howToPrepare.length > 0 ||
          sections.nextSteps.length > 0 ||
          sections.diagnosis) && (
          <div className="h-px bg-gradient-to-r from-transparent via-purple-300 to-transparent mb-4 lg:mb-5"></div>
        )}

      {/* Content Sections */}
      <div className="space-y-3 sm:space-y-4">
        {/* Why Video is Appropriate */}
        {sections.whyAppropriate.length > 0 && (
          <div>
            <h4 className="font-bold text-gray-900 mb-2 flex items-center gap-2 text-xs sm:text-sm">
              <span className="text-green-500 flex-shrink-0">✅</span>
              <span className="break-words">Why Video Consultation Works:</span>
            </h4>
            <ul className="space-y-1.5 ml-4 sm:ml-5 lg:ml-6">
              {sections.whyAppropriate.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2">
                  <span className="text-green-500 text-xs mt-0.5 sm:mt-1 flex-shrink-0">
                    ●
                  </span>
                  <span className="text-gray-700 text-xs sm:text-sm leading-relaxed break-words flex-1">
                    {renderWithBold(item)}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* How to Prepare */}
        {sections.howToPrepare.length > 0 && (
          <div>
            <h4 className="font-bold text-gray-900 mb-2 flex items-center gap-2 text-xs sm:text-sm">
              <span className="text-purple-500 flex-shrink-0">📋</span>
              <span className="break-words">How to Prepare:</span>
            </h4>
            <ul className="space-y-1.5 ml-4 sm:ml-5 lg:ml-6">
              {sections.howToPrepare.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2">
                  <span className="text-purple-500 text-xs mt-0.5 sm:mt-1 flex-shrink-0">
                    ●
                  </span>
                  <span className="text-gray-700 text-xs sm:text-sm leading-relaxed break-words flex-1">
                    {renderWithBold(item)}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Next Steps */}
        {sections.nextSteps.length > 0 && (
          <div>
            <h4 className="font-bold text-gray-900 mb-2 flex items-center gap-2 text-xs sm:text-sm">
              <span className="text-indigo-500 flex-shrink-0">🎯</span>
              <span className="break-words">Next Steps:</span>
            </h4>
            <ul className="space-y-1.5 ml-4 sm:ml-5 lg:ml-6">
              {sections.nextSteps.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2">
                  <span className="text-indigo-500 text-xs mt-0.5 sm:mt-1 flex-shrink-0">
                    ●
                  </span>
                  <span className="text-gray-700 text-xs sm:text-sm leading-relaxed break-words flex-1">
                    {renderWithBold(item)}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Diagnosis */}
        {sections.diagnosis && (
          <div className="bg-blue-50 rounded-lg lg:rounded-xl p-3 sm:p-4 border border-blue-200 mt-3 sm:mt-4">
            <h4 className="font-bold text-blue-900 mb-2 flex items-center gap-2 text-xs sm:text-sm">
              <span className="flex-shrink-0">🩺</span>
              <span className="break-words">Initial Assessment:</span>
            </h4>
            <p className="text-blue-800 text-xs sm:text-sm leading-relaxed break-words">
              {renderWithBold(sections.diagnosis)}
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

// ------------------- DoctorSearchModal - Responsive -------------------
const DoctorSearchModal = ({
  visible,
  onClose,
  onFailure,
  searchTime = 5 * 60 * 1000,
}) => {
  const [dots, setDots] = useState("");
  const [elapsedTime, setElapsedTime] = useState(0);
  const timeRef = useRef(null);

  useEffect(() => {
    if (visible) {
      setElapsedTime(0);
      timeRef.current = setInterval(() => {
        setElapsedTime((prev) => prev + 1);
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
    return `${mins}:${secs < 10 ? "0" : ""}${secs}`;
  };

  if (!visible) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-3 sm:p-4">
      <div className="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-6 lg:p-8 w-full max-w-sm sm:max-w-md shadow-2xl relative overflow-hidden">
        {/* Ripple Effects */}
        <div className="absolute top-8 left-1/2 transform -translate-x-1/2 w-28 h-28 sm:w-36 sm:h-36 bg-purple-600 rounded-full animate-ping opacity-20"></div>

        {/* Search Icon */}
        <div className="relative z-10 mb-4 sm:mb-5 flex justify-center">
          <div className="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-r from-purple-500 to-purple-700 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30 animate-pulse">
            <span className="text-2xl sm:text-3xl text-white">🐾</span>
          </div>
        </div>

        {/* Title */}
        <h2 className="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 text-center mb-2">
          Searching for Veterinarians{dots}
        </h2>
        <p className="text-sm sm:text-base text-gray-600 text-center mb-4 sm:mb-6">
          Finding the best available doctors near you
        </p>

        {/* Time Indicator */}
        <div className="flex items-center justify-center gap-2 mb-3 sm:mb-4 px-3 sm:px-4 py-2 bg-purple-50 rounded-lg border border-purple-200">
          <span className="text-purple-600">⏱️</span>
          <span className="text-xs sm:text-sm font-semibold text-purple-600">
            {formatTime(elapsedTime)}
          </span>
        </div>

        {/* Progress Bar */}
        <div className="w-full h-2 bg-gray-200 rounded-full mb-6 sm:mb-8 overflow-hidden">
          <div
            className="h-full bg-gradient-to-r from-purple-500 to-purple-700 rounded-full transition-all duration-300"
            style={{
              width: `${Math.min(
                (elapsedTime / (searchTime / 1000)) * 100,
                100
              )}%`,
            }}
          ></div>
        </div>

        {/* Buttons */}
        <div className="flex gap-2 sm:gap-3">
          <button
            onClick={() => {
              onClose();
              setElapsedTime(0);
            }}
            className="flex-1 py-2.5 sm:py-3 px-4 sm:px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg sm:rounded-xl transition-colors text-sm sm:text-base"
          >
            Cancel
          </button>
          <button
            onClick={() => {
              onClose();
              setElapsedTime(0);
              onFailure?.();
            }}
            className="flex-1 py-2.5 sm:py-3 px-4 sm:px-6 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg sm:rounded-xl transition-colors text-sm sm:text-base"
          >
            Try Alternative
          </button>
        </div>
      </div>
    </div>
  );
};

// ------------------- StartCallButton Component -------------------
const StartCallButton = ({ navigation, onShowLiveDoctors }) => {
  const [loading, setLoading] = useState(false);
  const [showSearchModal, setShowSearchModal] = useState(false);
  const [callStatus, setCallStatus] = useState(null);

  // Safe context access with defaults
  const authContext = useContext(AuthContext);
  const {
    user = {},
    token = null,
    nearbyDoctors = [],
    liveDoctors = [],
  } = authContext || {};

  const patientId = user?.id || "temp_user";
  const timeoutRef = useRef(null);
  const [showLiveDoctorsModal, setShowLiveDoctorsModal] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState("idle");
  
  // Track active toasts to prevent duplicates
  const activeToastIds = useRef(new Set());

  const clearAllToasts = useCallback(() => {
    activeToastIds.current.forEach(id => {
      toast.dismiss(id);
    });
    activeToastIds.current.clear();
  }, []);

  const showToast = useCallback((message, options = {}) => {
    const toastId = toast(message, options);
    activeToastIds.current.add(toastId);
    return toastId;
  }, []);

  const dismissToast = useCallback((toastId) => {
    toast.dismiss(toastId);
    activeToastIds.current.delete(toastId);
  }, []);

  const handleNoResponse = useCallback(() => {
    setLoading(false);
    setShowSearchModal(false);
    setConnectionStatus("failed");
    setCallStatus(null);

    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }

    // Clear previous toasts first
    clearAllToasts();

    const toastId = toast.custom(
      (t) => (
        <div className="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-2xl border border-gray-200 max-w-xs sm:max-w-sm mx-auto">
          <div className="flex items-center gap-3 mb-3 sm:mb-4">
            <div className="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
              <span className="text-xl sm:text-2xl">🐕</span>
            </div>
            <div className="flex-1 min-w-0">
              <h3 className="font-bold text-gray-900 text-sm sm:text-base lg:text-lg break-words">
                No Immediate Response
              </h3>
              <p className="text-gray-600 text-xs sm:text-sm break-words">
                All veterinarians are currently busy
              </p>
            </div>
          </div>

          <p className="text-gray-700 mb-3 sm:mb-4 text-xs sm:text-sm break-words">
            You can try again or book a clinic appointment for guaranteed care.
          </p>

          <div className="flex gap-2 sm:gap-3">
            <button
              onClick={() => {
                dismissToast(t.id);
                setConnectionStatus("idle");
              }}
              className="flex-1 py-2 px-3 sm:px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-xs sm:text-sm"
            >
              Cancel
            </button>
            <button
              onClick={() => {
                dismissToast(t.id);
                setConnectionStatus("idle");
                setShowLiveDoctorsModal(true);
              }}
              className="flex-1 py-2 px-3 sm:px-4 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors text-xs sm:text-sm"
            >
              See Doctors
            </button>
          </div>
        </div>
      ),
      {
        duration: 10000,
        position: "top-center",
      }
    );
    
    activeToastIds.current.add(toastId);
  }, [clearAllToasts, dismissToast]);

  const handleCallAccepted = useCallback(
    (data) => {
      console.log("🔔 Call accepted - Starting navigation process", data);

      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      // Clear all previous toasts
      clearAllToasts();

      setCallStatus({ type: "accepted", ...data });
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus("connected");

      const doctor =
        (nearbyDoctors || []).find((d) => d.id == data.doctorId) ||
        (liveDoctors || []).find((d) => d.id == data.doctorId);

      const patientIdLocal = user?.id || "temp_user";

      const toastId = toast.success(`🎉 Call connected with veterinarian! Redirecting...`, {
        duration: 3000,
        icon: "🐾",
      });
      activeToastIds.current.add(toastId);

      setTimeout(() => {
        try {
          if (data.requiresPayment) {
            const query = new URLSearchParams({
              callId: String(data.callId || ""),
              doctorId: String(doctor?.id || data.doctorId || ""),
              channel: String(data.channel || ""),
              patientId: String(patientIdLocal || ""),
            }).toString();

            if (typeof navigation === "function") {
              navigation(`/payment/${data.callId}?${query}`, {
                state: {
                  doctor: doctor,
                  channel: data.channel,
                  patientId: patientIdLocal,
                  callId: data.callId,
                },
              });
            } else {
              window.location.href = `/payment/${data.callId}?${query}`;
            }
          } else {
            const query = new URLSearchParams({
              uid: String(patientIdLocal || ""),
              role: "audience",
              callId: String(data.callId || ""),
              doctorId: String(doctor?.id || data.doctorId || ""),
              patientId: String(patientIdLocal || ""),
            }).toString();

            if (typeof navigation === "function") {
              navigation(`/call-page/${data.channel}?${query}`);
            } else {
              window.location.href = `/call-page/${data.channel}?${query}`;
            }
          }
        } catch (error) {
          console.error("❌ Navigation failed:", error);
          const errorToastId = toast.error("Failed to redirect. Please try again.");
          activeToastIds.current.add(errorToastId);
        }
      }, 800);
    },
    [navigation, nearbyDoctors, liveDoctors, user?.id, clearAllToasts]
  );

  useEffect(() => {
    if (!socket) {
      console.error("Socket not available");
      return;
    }

    if (!socket.connected) {
      socket.connect();
    }

    socket.emit("get-active-doctors");

    const handleCallSent = (data) => {
      setCallStatus({ type: "sent", ...data });
      setConnectionStatus("connecting");
      // Clear previous loading toasts
      toast.dismiss("call-sent");
      const toastId = toast.loading("📞 Calling veterinarian...", { id: "call-sent" });
      activeToastIds.current.add(toastId);
    };

    const handleCallRejected = (data) => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      // Clear previous toasts
      clearAllToasts();

      setCallStatus({ type: "rejected", ...data });
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus("failed");

      // Professional error message based on rejection reason
      const errorMessage =
        data.reason === "timeout"
          ? "⏰ No veterinarian responded within 5 minutes. Please try again or book a clinic appointment."
          : "❌ Veterinarian is currently unavailable. Please try again later.";

      const toastId = toast.custom(
        (t) => (
          <div className="bg-white rounded-xl p-4 shadow-2xl border border-gray-200 max-w-sm mx-auto">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <span className="text-2xl">🐕</span>
              </div>
              <div>
                <h3 className="font-bold text-gray-900">Call Not Available</h3>
                <p className="text-gray-600 text-sm">{errorMessage}</p>
              </div>
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => {
                  dismissToast(t.id);
                  setConnectionStatus("idle");
                }}
                className="flex-1 py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-sm"
              >
                Cancel
              </button>
              <button
                onClick={() => {
                  dismissToast(t.id);
                  setConnectionStatus("idle");
                  setShowLiveDoctorsModal(true);
                }}
                className="flex-1 py-2 px-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors text-sm"
              >
                Try Again
              </button>
            </div>
          </div>
        ),
        {
          duration: 8000,
          position: "top-center",
        }
      );
      activeToastIds.current.add(toastId);
    };

    const handleDoctorBusy = (data) => {
      setCallStatus({ type: "busy", ...data });
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus("failed");

      clearAllToasts();
      const toastId = toast.error("🐕 Veterinarian is on another call", { duration: 4000 });
      activeToastIds.current.add(toastId);
    };

    const handleCallFailed = (data) => {
      setCallStatus({ type: "failed", ...data });
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus("failed");
    
      // ✅ Dismiss any existing toasts before showing a new one
      toast.dismiss();
      toast.error("❌ Veterinarian not available", { duration: 4000 });
    };
    

    const handleCallEnded = () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus("idle");
      setCallStatus(null);
      clearAllToasts();
    };

    // Add event listeners
    socket.on("call-sent", handleCallSent);
    socket.on("call-accepted", handleCallAccepted);
    socket.on("call-rejected", handleCallRejected);
    socket.on("call-ended", handleCallEnded);
    socket.on("call-cancelled", handleCallEnded);
    socket.on("doctor-busy", handleDoctorBusy);
    socket.on("call-failed", handleCallFailed);

    return () => {
      // Remove event listeners
      socket.off("call-sent", handleCallSent);
      socket.off("call-accepted", handleCallAccepted);
      socket.off("call-rejected", handleCallRejected);
      socket.off("call-ended", handleCallEnded);
      socket.off("call-cancelled", handleCallEnded);
      socket.off("doctor-busy", handleDoctorBusy);
      socket.off("call-failed", handleCallFailed);

      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
      
      clearAllToasts();
    };
  }, [handleCallAccepted, clearAllToasts, dismissToast]);

  const handleCallDoctor = useCallback(
    (doctor) => {
      const callId = `call_${Date.now()}_${Math.random()
        .toString(36)
        .substring(2, 8)}`;
      const channel = `channel_${callId}`;

      // Clear previous toasts
      clearAllToasts();

      setCallStatus(null);
      setLoading(true);
      setShowLiveDoctorsModal(false);
      setShowSearchModal(true);
      setConnectionStatus("connecting");

      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      if (socket) {
        socket.emit("call-requested", {
          doctorId: doctor.id,
          patientId,
          channel,
          callId,
          timestamp: new Date().toISOString(),
        });

        timeoutRef.current = setTimeout(() => {
          if (loading || showSearchModal) {
            handleNoResponse();
          }
        }, 5 * 60 * 1000);
      } else {
        console.error("Socket not available");
        handleNoResponse();
      }
    },
    [patientId, loading, showSearchModal, handleNoResponse, clearAllToasts]
  );

  const startCall = useCallback(() => {
    const doctorsToCall =
      nearbyDoctors && nearbyDoctors.length ? nearbyDoctors : [];

    if (!doctorsToCall.length) {
      // Clear previous toasts first
      clearAllToasts();
      
      const toastId = toast.error(
        "🐾 No veterinarians available nearby. Please try again later.",
        {
          duration: 5000,
          icon: "😔",
        }
      );
      activeToastIds.current.add(toastId);
      return;
    }

    // Clear previous toasts
    clearAllToasts();

    setLoading(true);
    setShowSearchModal(true);
    setConnectionStatus("connecting");

    const callId = `call_${Date.now()}_${Math.random()
      .toString(36)
      .substring(2, 8)}`;
    const channel = `channel_${callId}`;

    try {
      if (socket) {
        doctorsToCall.forEach((doc) => {
          socket.emit("call-requested", {
            doctorId: doc.id,
            patientId,
            channel,
            callId,
            timestamp: new Date().toISOString(),
          });
        });
      } else {
        console.error("Socket not available");
        handleNoResponse();
        return;
      }
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
    }, 5 * 60 * 1000);
  }, [nearbyDoctors, patientId, loading, callStatus, handleNoResponse, clearAllToasts]);

  const getButtonState = () => {
    if (loading) return "loading";
    if (connectionStatus === "no_doctors" || connectionStatus === "failed")
      return "unavailable";
    if (!nearbyDoctors?.length && !liveDoctors?.length) return "unavailable";
    return "available";
  };

  const buttonState = getButtonState();
  const buttonDisabled =
    buttonState === "unavailable" || buttonState === "loading";

  const getButtonText = () => {
    switch (buttonState) {
      case "loading":
        return "Searching for Veterinarians...";
      case "unavailable":
        return "No Veterinarians Available";
      default:
        return "Start Video Consultation";
    }
  };

  const getButtonIcon = () => {
    switch (buttonState) {
      case "loading":
        return (
          <div className="w-4 h-4 sm:w-5 sm:h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
        );
      case "unavailable":
        return <span className="text-white text-sm sm:text-base">🐾</span>;
      default:
        return (
          <div className="w-6 h-6 sm:w-7 sm:h-7 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <span className="text-white text-sm sm:text-base">📹</span>
          </div>
        );
    }
  };

  return (
    <>
      <div className="mb-3 sm:mb-4">
        <button
          className={`
            w-full relative overflow-hidden rounded-xl sm:rounded-2xl transition-all duration-300 transform hover:scale-105
            ${
              buttonDisabled
                ? "opacity-60 cursor-not-allowed"
                : "hover:shadow-xl"
            }
            ${buttonState === "loading" ? "opacity-80" : ""}
          `}
          onClick={() => {
            if (liveDoctors && liveDoctors.length) {
              setShowLiveDoctorsModal(true);
            } else {
              startCall();
            }
          }}
          disabled={buttonDisabled}
        >
          {!buttonDisabled && buttonState !== "loading" && (
            <div className="absolute inset-0 bg-purple-600 rounded-xl sm:rounded-2xl shadow-lg shadow-purple-500/50 animate-pulse"></div>
          )}

          <div
            className={`
            relative w-full py-3 sm:py-4 px-4 sm:px-6 rounded-xl sm:rounded-2xl bg-gradient-to-r transition-all duration-300
            ${
              buttonState === "loading"
                ? "from-gray-500 to-gray-600"
                : buttonState === "unavailable"
                ? "from-gray-500 to-gray-600"
                : "from-purple-600 to-pink-500 hover:from-purple-700 hover:to-pink-600"
            }
          `}
          >
            <div className="flex items-center justify-center gap-2 sm:gap-3 lg:gap-4">
              {getButtonIcon()}
              <span className="text-white font-bold text-sm sm:text-base lg:text-lg tracking-wide break-words">
                {getButtonText()}
              </span>
              {buttonState === "available" && (
                <span className="text-white text-sm sm:text-base">➡️</span>
              )}
            </div>
          </div>
        </button>

        {buttonState === "available" && (
          <div className="flex items-center justify-center gap-2 mt-2 sm:mt-3">
            <span className="text-green-500 text-xs sm:text-sm">✅</span>
            <span className="text-[10px] sm:text-xs font-semibold text-green-600 break-words text-center">
              Licensed veterinarians • Instant connection • Secure call
            </span>
          </div>
        )}

        {buttonState === "unavailable" && connectionStatus !== "failed" && (
          <div className="flex items-center justify-center gap-2 mt-2 sm:mt-3">
            <span className="text-yellow-500 text-xs sm:text-sm">⚠️</span>
            <span className="text-[10px] sm:text-xs font-semibold text-yellow-600 break-words text-center">
              Check back soon or book a clinic appointment
            </span>
          </div>
        )}

        {connectionStatus === "failed" && (
          <div
            onClick={() => {
              if (liveDoctors && liveDoctors.length) {
                setShowLiveDoctorsModal(true);
              } else {
                startCall();
              }
            }}
            className="flex items-center justify-center gap-2 mt-2 sm:mt-3 
               cursor-pointer hover:bg-red-50 active:scale-95 transition-all 
               px-3 py-2 rounded-lg border border-red-200 select-none"
          >
            <span className="text-red-500 text-xs sm:text-sm">❌</span>
            <span className="text-[10px] sm:text-xs font-semibold text-red-600 text-center">
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

      <DoctorSearchModal
        visible={showSearchModal}
        searchTime={5 * 60 * 1000}
        onClose={() => {
          setShowSearchModal(false);
          setLoading(false);
          setConnectionStatus("idle");
          if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
            timeoutRef.current = null;
          }
          clearAllToasts();
        }}
        onFailure={handleNoResponse}
      />
    </>
  );
};

// ------------------- EmergencyStatusBox - Responsive -------------------
const EmergencyStatusBox = ({
  decision,
  nearbyDoctors,
  navigation,
  messageId,
  isTypingComplete,
}) => {
  const [showAppointmentModal, setShowAppointmentModal] = useState(false);
  const [isVisible, setIsVisible] = useState(false);
  
  // Track if we've already shown the toast for this message
  const hasShownToast = useRef(false);

  useEffect(() => {
    if (decision && isTypingComplete) {
      const timer = setTimeout(() => {
        setIsVisible(true);
      }, 300);

      return () => clearTimeout(timer);
    }
  }, [decision, isTypingComplete]);

  if (!decision || !isTypingComplete || !isVisible) return null;

  // Common container styles for all emergency boxes
  const Container = ({
    children,
    gradientFrom,
    gradientTo,
    borderColor,
    textColor,
  }) => (
    <div className="my-2 sm:my-3 mx-1 sm:mx-2 max-w-full animate-fade-in-up">
      <div
        className={`bg-gradient-to-r ${gradientFrom} ${gradientTo} rounded-lg sm:rounded-xl overflow-hidden border ${borderColor} shadow-sm`}
      >
        <div className="p-3 sm:p-4">{children}</div>
      </div>
    </div>
  );

  const decisionUpper = decision.toUpperCase();

  if (decisionUpper.includes("EMERGENCY")) {
    return (
      <>
        <Container
          gradientFrom="from-red-50"
          gradientTo="to-red-100"
          borderColor="border-red-200"
          textColor="text-red-900"
        >
          <div className="flex items-start gap-2 sm:gap-3 mb-3">
            <div className="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center shadow-lg shadow-red-500/30 flex-shrink-0">
              <span className="text-white text-sm sm:text-base">🚨</span>
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-1.5 mb-1">
                <div className="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse"></div>
                <span className="text-[9px] sm:text-xs font-black text-red-600 tracking-wider uppercase">
                  URGENT
                </span>
              </div>
              <h3 className="text-sm sm:text-base font-bold text-red-900 mb-0.5 break-words">
                Emergency Care Required
              </h3>
              <p className="text-xs sm:text-sm text-red-700 font-medium break-words">
                Immediate attention needed
              </p>
            </div>
          </div>

          <div className="flex items-start gap-1.5 sm:gap-2 p-2 sm:p-3 bg-white bg-opacity-70 rounded-lg border border-red-200 mb-3">
            <span className="text-red-600 flex-shrink-0 text-xs sm:text-sm">
              ⚠️
            </span>
            <p className="text-red-800 font-medium text-xs break-words flex-1 leading-relaxed">
              Your pet's symptoms require emergency care. Please contact a
              veterinarian immediately.
            </p>
          </div>

          <button
            onClick={() => setShowAppointmentModal(true)}
            className="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-2 sm:py-2.5 rounded-lg font-bold flex items-center justify-center gap-2 transition-all transform hover:scale-105 shadow-lg shadow-red-500/30 text-xs sm:text-sm"
          >
            <span className="text-xs sm:text-sm">🏥</span>
            <span className="break-words">Find Emergency Clinic</span>
            <span className="text-xs sm:text-sm">➡️</span>
          </button>
        </Container>

        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={() => setShowAppointmentModal(false)}
          nearbyDoctors={nearbyDoctors}
          onBook={(appointment) => {
            console.log("Appointment booked:", appointment);
            // Only show success toast once
            if (!hasShownToast.current) {
              toast.success(
                `🎉 Appointment with ${appointment.doctor.name} on ${appointment.date} at ${appointment.time} booked!`,
                {
                  duration: 5000,
                  icon: "🐾",
                }
              );
              hasShownToast.current = true;
            }
            setShowAppointmentModal(false);
          }}
        />
      </>
    );
  }

  if (decisionUpper.includes("VIDEO_CONSULT")) {
    return (
      <Container
        gradientFrom="from-purple-50"
        gradientTo="to-indigo-100"
        borderColor="border-purple-200"
        textColor="text-purple-900"
      >
        <div className="flex items-start gap-2 mb-2">
          <div className="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30 flex-shrink-0">
            <span className="text-white text-sm">📹</span>
          </div>
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-1 mb-0.5">
              <span className="text-purple-600 text-[10px]">⭐</span>
              <span className="text-[8px] font-black text-purple-600 tracking-wider uppercase">
                RECOMMENDED
              </span>
            </div>
            <h3 className="text-xs font-bold text-purple-900 mb-0.5 leading-tight">
              Video Consultation
            </h3>
            <p className="text-[10px] text-purple-700 font-medium leading-tight">
              Connect with a vet instantly
            </p>
          </div>
        </div>

        <div className="space-y-1 p-2 bg-white bg-opacity-70 rounded-lg border border-purple-200 mb-2">
          <div className="flex items-center gap-1.5">
            <span className="text-green-500 flex-shrink-0 text-[10px]">✅</span>
            <span className="text-purple-800 font-medium text-[10px] leading-tight">
              Instant consultation
            </span>
          </div>
          <div className="flex items-center gap-1.5">
            <span className="text-green-500 flex-shrink-0 text-[10px]">✅</span>
            <span className="text-purple-800 font-medium text-[10px] leading-tight">
              Professional advice
            </span>
          </div>
        </div>

        <StartCallButton
          nearbyDoctors={nearbyDoctors}
          navigation={navigation}
        />
      </Container>
    );
  }

  if (decisionUpper.includes("IN_CLINIC")) {
    return (
      <>
        <Container
          gradientFrom="from-gray-50"
          gradientTo="to-blue-50"
          borderColor="border-gray-200"
          textColor="text-gray-900"
        >
          <div className="flex items-start gap-2 sm:gap-3 mb-3">
            <div className="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30 flex-shrink-0">
              <span className="text-white text-sm sm:text-base">🏥</span>
            </div>
            <div className="flex-1 min-w-0">
              <h3 className="text-sm sm:text-base font-bold text-gray-900 mb-0.5 break-words">
                Consultation Options
              </h3>
              <p className="text-xs sm:text-sm text-gray-600 font-medium break-words">
                Choose video call or clinic visit
              </p>
            </div>
          </div>

          <div className="space-y-2 sm:space-y-3">
            <StartCallButton
              nearbyDoctors={nearbyDoctors}
              navigation={navigation}
            />

            <div className="flex items-center my-2">
              <div className="flex-1 h-px bg-gray-300"></div>
              <span className="mx-2 sm:mx-3 text-[9px] sm:text-xs font-semibold text-gray-500 uppercase">
                OR
              </span>
              <div className="flex-1 h-px bg-gray-300"></div>
            </div>

            <button
              onClick={() => setShowAppointmentModal(true)}
              className="w-full bg-purple-50 hover:bg-purple-100 text-purple-700 py-2 sm:py-2.5 rounded-lg font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition-all border border-purple-200 hover:border-purple-300 text-xs sm:text-sm"
            >
              <span className="text-xs sm:text-sm">🏥</span>
              <span className="break-words">Book Clinic Visit</span>
              <span className="text-xs sm:text-sm">➡️</span>
            </button>
          </div>
        </Container>

        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={() => setShowAppointmentModal(false)}
          nearbyDoctors={nearbyDoctors}
          onBook={(appointment) => {
            console.log("Appointment booked:", appointment);
            // Only show success toast once
            if (!hasShownToast.current) {
              toast.success(
                `🎉 Appointment with ${appointment.doctor.name} on ${appointment.date} at ${appointment.time} booked!`,
                {
                  duration: 5000,
                  icon: "🐾",
                }
              );
              hasShownToast.current = true;
            }
            setShowAppointmentModal(false);
          }}
        />
      </>
    );
  }

  return null;
};

// ------------------- MessageBubble - Fully Responsive -------------------
const MessageBubble = ({ msg, index, nearbyDoctors, navigation }) => {
  const [isTypingComplete, setIsTypingComplete] = useState(false);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    setIsVisible(true);
  }, []);

  useEffect(() => {
    if (msg.sender === "ai" && msg.text && msg.displayedText) {
      if (msg.displayedText.length >= msg.text.length) {
        setIsTypingComplete(true);
      }
    }
  }, [msg.displayedText, msg.text, msg.sender]);

  if (msg.type === "loading") {
    return (
      <div
        className={`my-2 max-w-[90%] sm:max-w-[85%] animate-fade-in-up ${
          isVisible ? "opacity-100" : "opacity-0"
        }`}
      >
        <div className="flex items-start gap-2 sm:gap-3">
          <div className="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30 flex-shrink-0">
            <span className="text-white text-sm sm:text-base">🐾</span>
          </div>

          <div className="bg-white rounded-xl sm:rounded-2xl rounded-tl-sm px-4 sm:px-5 py-2.5 sm:py-3 border border-gray-200 shadow-sm">
            <div className="mb-2">
              <span className="text-[10px] sm:text-xs font-semibold text-purple-600">
                AI analyzing
              </span>
            </div>
            <div className="flex gap-1.5">
              <div className="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-purple-600 rounded-full opacity-60 animate-bounce"></div>
              <div
                className="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-purple-600 rounded-full opacity-80 animate-bounce"
                style={{ animationDelay: "0.1s" }}
              ></div>
              <div
                className="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-purple-600 rounded-full animate-bounce"
                style={{ animationDelay: "0.2s" }}
              ></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const isUser = msg.sender === "user";
  const decisionTag =
    typeof msg.decision === "string" ? msg.decision.toUpperCase() : "";
  const hasStructuredDecision =
    decisionTag.includes("VIDEO_CONSULT") ||
    decisionTag.includes("IN_CLINIC") ||
    decisionTag.includes("EMERGENCY");
  const hasStructuredMarkers =
    msg.text &&
    (msg.text.includes("**") ||
      msg.text.includes("=== DIAGNOSIS ===") ||
      msg.text.includes("WHY VIDEO IS APPROPRIATE"));
  const hasStructuredContent =
    !isUser && hasStructuredDecision && hasStructuredMarkers;

  return (
    <>
      <div
        className={`my-2 max-w-[90%] sm:max-w-[85%] animate-fade-in-up ${
          isVisible ? "opacity-100" : "opacity-0"
        } ${isUser ? "ml-auto" : ""}`}
      >
        <div
          className={`flex items-start gap-2 sm:gap-3 ${
            isUser ? "flex-row-reverse" : ""
          }`}
        >
          {!isUser && (
            <div className="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-purple-500/30 flex-shrink-0">
              <span className="text-white text-sm sm:text-base">🐕</span>
            </div>
          )}

          <div
            className={`
            rounded-xl sm:rounded-2xl px-4 sm:px-5 py-2.5 sm:py-3 shadow-sm max-w-full overflow-hidden
            ${
              isUser
                ? "bg-purple-600 text-white rounded-br-sm"
                : hasStructuredContent
                ? "bg-transparent border-0 p-0"
                : "bg-white border border-gray-200 rounded-bl-sm"
            }
          `}
          >
            {hasStructuredContent && isTypingComplete ? (
              <FormattedAIResponse text={msg.displayedText || msg.text} />
            ) : (
              <p
                className={`break-words text-xs sm:text-sm lg:text-base ${
                  isUser ? "text-white" : "text-gray-900"
                }`}
              >
                {msg.displayedText || msg.text}
              </p>
            )}
          </div>
        </div>

        {!isUser && !hasStructuredContent && (
          <div className="text-[10px] sm:text-xs text-gray-500 mt-1 ml-9 sm:ml-11">
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