import React, { useCallback, useContext, useEffect, useRef, useState } from "react";
import axios from "axios";
import { AuthContext } from "../auth/AuthContext";
import { socket } from "./socket";
import DoctorAppointmentModal from "./DoctorAppointmentModal";
import LiveDoctorSelectionModal from "./LiveDoctorSelectionModal";
import toast from "react-hot-toast";


const parseAIResponse = (text) => {
  const sections = {
    recommendation: null,
    whyAppropriate: [],
    howToPrepare: [],
    nextSteps: [],
    diagnosis: null,
    emergencyActions: [],
    whileTraveling: [],
    immediateActions: []
  };

  // Check if this is an emergency response
  const isEmergency = text.includes("EMERGENCY") || text.includes("IMMEDIATE ACTIONS");

  if (isEmergency) {
    // Extract main emergency recommendation
    const emergencyMatch = text.match(/🚨 EMERGENCY RECOMMENDATION:\s*\*\*([^*]+)\*\*/);
    if (emergencyMatch) {
      sections.recommendation = emergencyMatch[1].trim();
    } else {
      // Fallback: try to extract any bold text as recommendation
      const boldMatch = text.match(/\*\*([^*]+)\*\*/);
      if (boldMatch) {
        sections.recommendation = boldMatch[1].trim();
      }
    }

    // Extract IMMEDIATE ACTIONS
    const immediateMatch = text.match(/\*\*IMMEDIATE ACTIONS:\*\*([\s\S]*?)(?=\*\*WHILE TRAVELING:|\*\*NO DELAYS|$)/i);
    if (immediateMatch) {
      const bullets = immediateMatch[1].match(/• ([^\n]+)/g);
      if (bullets) {
        sections.immediateActions = bullets.map(b => b.replace('• ', '').trim());
      }
    }

    // Extract WHILE TRAVELING
    const travelingMatch = text.match(/\*\*WHILE TRAVELING:\*\*([\s\S]*?)(?=\*\*NO DELAYS|$)/i);
    if (travelingMatch) {
      const bullets = travelingMatch[1].match(/• ([^\n]+)/g);
      if (bullets) {
        sections.whileTraveling = bullets.map(b => b.replace('• ', '').trim());
      }
    }

    // Extract NO DELAYS warning
    const noDelaysMatch = text.match(/\*\*NO DELAYS([^*]*)\*\*/i);
    if (noDelaysMatch) {
      sections.nextSteps = [noDelaysMatch[1].trim() + " - NO DELAYS"];
    }

    return sections;
  }

  // Original non-emergency parsing logic
  // Extract diagnosis section
  const diagnosisMatch = text.match(/=== DIAGNOSIS ===([\s\S]*?)=== END ===/);
  if (diagnosisMatch) {
    sections.diagnosis = diagnosisMatch[1].trim();
  }

  // Extract main recommendation
  const recMatch = text.match(/\*\*([^*]+)\*\*/);
  if (recMatch) {
    sections.recommendation = recMatch[1];
  }

  // Extract WHY VIDEO IS APPROPRIATE
  const whyMatch = text.match(/\*\*WHY VIDEO IS APPROPRIATE:\*\*([\s\S]*?)(?=\*\*HOW TO PREPARE:|\*\*NEXT STEPS:|=== DIAGNOSIS|$)/);
  if (whyMatch) {
    const bullets = whyMatch[1].match(/• ([^\n]+)/g);
    if (bullets) {
      sections.whyAppropriate = bullets.map(b => b.replace('• ', '').trim());
    }
  }

  // Extract HOW TO PREPARE
  const prepMatch = text.match(/\*\*HOW TO PREPARE:\*\*([\s\S]*?)(?=\*\*NEXT STEPS:|=== DIAGNOSIS|$)/);
  if (prepMatch) {
    const bullets = prepMatch[1].match(/• ([^\n]+)/g);
    if (bullets) {
      sections.howToPrepare = bullets.map(b => b.replace('• ', '').trim());
    }
  }

  // Extract NEXT STEPS
  const stepsMatch = text.match(/\*\*NEXT STEPS:\*\*([\s\S]*?)(?==== DIAGNOSIS|$)/);
  if (stepsMatch) {
    const bullets = stepsMatch[1].match(/• ([^\n]+)/g);
    if (bullets) {
      sections.nextSteps = bullets.map(b => b.replace('• ', '').trim());
    }
  }

  return sections;
};

const FormattedAIResponse = ({ text }) => {
  const sections = parseAIResponse(text);
  const isEmergency = text.includes("EMERGENCY") || text.includes("IMMEDIATE ACTIONS");

  if (isEmergency) {
    return (
      <div className="bg-gradient-to-br from-red-50 via-white to-orange-50 rounded-lg p-2 border-2 border-red-300 shadow-md max-w-full">
        {/* Emergency Header - Compact */}
        <div className="mb-2">
          <div className="flex items-start gap-2 mb-2">
            <div className="w-6 h-6 bg-gradient-to-r from-red-500 to-red-700 rounded-full flex items-center justify-center shadow-md flex-shrink-0">
              <span className="text-sm">🚨</span>
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-1 mb-1">
                <div className="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse"></div>
                <span className="text-[10px] font-black text-red-600 uppercase tracking-wide">
                  EMERGENCY
                </span>
              </div>
              <h3 className="font-bold text-red-900 text-sm leading-tight break-words">
                {sections.recommendation || "Emergency veterinary care required"}
              </h3>
            </div>
          </div>
        </div>

        {/* Immediate Actions - Compact */}
        {sections.immediateActions.length > 0 && (
          <div className="mb-2">
            <h4 className="font-bold text-red-800 mb-1 flex items-center gap-1 text-xs">
              <span className="text-red-600 flex-shrink-0">🆘</span>
              <span className="break-words">IMMEDIATE ACTIONS:</span>
            </h4>
            <ul className="space-y-1 ml-3">
              {sections.immediateActions.map((item, idx) => (
                <li key={idx} className="flex items-start gap-1 bg-red-100 p-1 rounded border border-red-200">
                  <span className="text-red-600 text-xs mt-0.5 flex-shrink-0">•</span>
                  <span className="text-red-800 font-semibold text-xs leading-relaxed break-words flex-1">
                    {item}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* While Traveling - Compact */}
        {sections.whileTraveling.length > 0 && (
          <div className="mb-2">
            <h4 className="font-bold text-orange-800 mb-1 flex items-center gap-1 text-xs">
              <span className="text-orange-600 flex-shrink-0">🚗</span>
              <span className="break-words">WHILE TRAVELING:</span>
            </h4>
            <ul className="space-y-1 ml-3">
              {sections.whileTraveling.map((item, idx) => (
                <li key={idx} className="flex items-start gap-1 bg-orange-50 p-1 rounded border border-orange-200">
                  <span className="text-orange-600 text-xs mt-0.5 flex-shrink-0">•</span>
                  <span className="text-orange-800 font-medium text-xs leading-relaxed break-words flex-1">
                    {item}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* No Delays Warning - Compact */}
        <div className="bg-red-200 border border-red-400 rounded p-1">
          <div className="flex items-center gap-1 mb-1">
            <span className="text-red-700 text-sm">⏰</span>
            <h4 className="font-black text-red-800 text-xs uppercase">
              ACT IMMEDIATELY
            </h4>
          </div>
          <p className="text-red-700 font-bold text-xs leading-relaxed">
            Every minute counts. Do not wait - seek veterinary care immediately.
          </p>
        </div>
      </div>
    );
  }

  // Compact non-emergency UI
  return (
    <div className="bg-gradient-to-br from-purple-50 via-white to-indigo-50 rounded-lg p-2 border border-purple-200 shadow-md max-w-full">
      {/* Header with Main Recommendation - Compact */}
      {sections.recommendation && (
        <div className="mb-2">
          <div className="flex items-start gap-2 mb-1">
            <div className="w-6 h-6 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-md flex-shrink-0">
              <span className="text-sm">💡</span>
            </div>
            <div className="flex-1 min-w-0">
              <h3 className="font-bold text-purple-900 text-sm leading-tight break-words">
                {sections.recommendation}
              </h3>
            </div>
          </div>
        </div>
      )}

      {/* Content Sections - Compact */}
      <div className="space-y-2">
        {/* Why Video is Appropriate */}
        {sections.whyAppropriate.length > 0 && (
          <div>
            <h4 className="font-bold text-gray-900 mb-1 flex items-center gap-1 text-xs">
              <span className="text-green-500 flex-shrink-0">✅</span>
              <span className="break-words">Why Video Works:</span>
            </h4>
            <ul className="space-y-0.5 ml-3">
              {sections.whyAppropriate.map((item, idx) => (
                <li key={idx} className="flex items-start gap-1">
                  <span className="text-green-500 text-xs mt-0.5 flex-shrink-0">●</span>
                  <span className="text-gray-700 text-xs leading-relaxed break-words flex-1">{item}</span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* How to Prepare */}
        {sections.howToPrepare.length > 0 && (
          <div>
            <h4 className="font-bold text-gray-900 mb-1 flex items-center gap-1 text-xs">
              <span className="text-purple-500 flex-shrink-0">📋</span>
              <span className="break-words">How to Prepare:</span>
            </h4>
            <ul className="space-y-0.5 ml-3">
              {sections.howToPrepare.map((item, idx) => (
                <li key={idx} className="flex items-start gap-1">
                  <span className="text-purple-500 text-xs mt-0.5 flex-shrink-0">●</span>
                  <span className="text-gray-700 text-xs leading-relaxed break-words flex-1">{item}</span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Next Steps */}
        {sections.nextSteps.length > 0 && (
          <div>
            <h4 className="font-bold text-gray-900 mb-1 flex items-center gap-1 text-xs">
              <span className="text-indigo-500 flex-shrink-0">🎯</span>
              <span className="break-words">Next Steps:</span>
            </h4>
            <ul className="space-y-0.5 ml-3">
              {sections.nextSteps.map((item, idx) => (
                <li key={idx} className="flex items-start gap-1">
                  <span className="text-indigo-500 text-xs mt-0.5 flex-shrink-0">●</span>
                  <span className="text-gray-700 text-xs leading-relaxed break-words flex-1">{item}</span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Diagnosis - Compact */}
        {sections.diagnosis && (
          <div className="bg-blue-50 rounded p-2 border border-blue-200 mt-2">
            <h4 className="font-bold text-blue-900 mb-1 flex items-center gap-1 text-xs ">
              <span className="flex-shrink-0">🩺</span>
              <span className="break-words">Initial Assessment:</span>
            </h4>
            <p className="text-blue-800 text-xs leading-relaxed break-words">{sections.diagnosis}</p>
          </div>
        )}
      </div>
    </div>
  );
};

// ------------------- MessageBubble - Compact Design -------------------
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
      <div className={`my-1 max-w-[90%] animate-fade-in-up ${isVisible ? 'opacity-100' : 'opacity-0'}`}>
        <div className="flex items-start gap-2">
          <div className="w-6 h-6 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-md flex-shrink-0">
            <span className="text-white text-xs">🐾</span>
          </div>

          <div className="bg-white rounded-lg rounded-tl-sm px-3 py-1.5 border border-gray-200 shadow-sm">
            <div className="mb-1">
              <span className="text-[10px] font-semibold text-purple-600">AI analyzing</span>
            </div>
            <div className="flex gap-1">
              <div className="w-1.5 h-1.5 bg-purple-600 rounded-full opacity-60 animate-bounce"></div>
              <div className="w-1.5 h-1.5 bg-purple-600 rounded-full opacity-80 animate-bounce" style={{ animationDelay: '0.1s' }}></div>
              <div className="w-1.5 h-1.5 bg-purple-600 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const isUser = msg.sender === "user";
  const hasStructuredContent = !isUser && msg.text && (
    msg.text.includes('**') || 
    msg.text.includes('=== DIAGNOSIS ===') ||
    msg.text.includes('WHY VIDEO IS APPROPRIATE')
  );

  return (
    <>
      <div className={`my-1 max-w-[90%] animate-fade-in-up ${isVisible ? 'opacity-100' : 'opacity-0'} ${isUser ? 'ml-auto' : ''}`}>
        <div className={`flex items-start gap-2 ${isUser ? 'flex-row-reverse' : ''}`}>
          {!isUser && (
            <div className="w-6 h-6 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-md flex-shrink-0">
              <span className="text-white text-xs">🐕</span>
            </div>
          )}

          <div className={`
            rounded-lg px-3 py-1.5 shadow-sm max-w-full overflow-hidden
            ${isUser 
              ? 'bg-purple-600 text-white rounded-br-sm' 
              : hasStructuredContent 
                ? 'bg-transparent border-0 p-0' 
                : 'bg-white border border-gray-200 rounded-bl-sm'
            }
          `}>
            {hasStructuredContent && isTypingComplete ? (
              <FormattedAIResponse text={msg.displayedText || msg.text} />
            ) : (
              <p className={`break-words text-xs ${isUser ? 'text-white' : 'text-gray-900'}`}>
                {msg.displayedText || msg.text}
              </p>
            )}
          </div>
        </div>

        {!isUser && !hasStructuredContent && (
          <div className="text-[10px] text-gray-500 mt-0.5 ml-8">
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


// ------------------- StartCallButton - Professional Compact Design -------------------
const StartCallButton = ({ navigation, onShowLiveDoctors }) => {
  const [loading, setLoading] = useState(false);
  const [showSearchModal, setShowSearchModal] = useState(false);
  const [callStatus, setCallStatus] = useState(null);
  const [showLiveDoctorsModal, setShowLiveDoctorsModal] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState("idle");
  const [errorToastShown, setErrorToastShown] = useState(false);

  const { user, nearbyDoctors, liveDoctors } = useContext(AuthContext);
  const patientId = user?.id;
  const timeoutRef = useRef(null);

  // ---------------------------------------------------
  // 🔔 COMMON FAILURE HANDLER (used by multiple events)
  // ---------------------------------------------------
  const showSingleToast = useCallback((message, icon = "😔") => {
    if (!errorToastShown) {
      setErrorToastShown(true);
      toast.error(message, {
        id: "vet-alert", // ensures only one toast at a time
        icon,
        duration: 4000,
      });
      setTimeout(() => setErrorToastShown(false), 5000);
    }
  }, [errorToastShown]);

  const handleNoResponse = useCallback(() => {
    setLoading(false);
    setShowSearchModal(false);
    setConnectionStatus("failed");
    setCallStatus(null);
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }

    showSingleToast("No immediate response. Please try again later.", "🐕");
  }, [showSingleToast]);

  // ---------------------------------------------------
  // ✅ Call accepted — navigate to call or payment
  // ---------------------------------------------------
  const handleCallAccepted = useCallback(
    (data) => {
      console.log("🔔 Call accepted - Starting navigation process", data);

      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      setCallStatus({ type: "accepted", ...data });
      setLoading(false);
      setShowSearchModal(false);
      setConnectionStatus("connected");

      const doctor =
        (nearbyDoctors || []).find((d) => d.id == data.doctorId) ||
        (liveDoctors || []).find((d) => d.id == data.doctorId);

      const patientIdLocal = user?.id;

      toast.success(`Connected with veterinarian!`, {
        duration: 2000,
        icon: "🐾",
      });

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
                  doctor,
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
          toast.error("Failed to redirect. Please try again.");
        }
      }, 600);
    },
    [navigation, nearbyDoctors, liveDoctors, user?.id]
  );

  // ---------------------------------------------------
  // 🎧 Socket Event Handlers
  // ---------------------------------------------------
  useEffect(() => {
    if (!socket.connected) socket.connect();

    socket.emit("get-active-doctors");

    const handleCallSent = (data) => {
      setCallStatus({ type: "sent", ...data });
      setConnectionStatus("connecting");
      toast.loading("Calling veterinarian...", { id: "call-sent" });
    };

    const handleCallRejected = (data) => {
      toast.dismiss("call-sent");
      setLoading(false);
      setConnectionStatus("failed");
      showSingleToast("Veterinarian rejected the call.", "🚫");
    };

    const handleDoctorBusy = (data) => {
      toast.dismiss("call-sent");
      setLoading(false);
      setConnectionStatus("failed");
      showSingleToast("Veterinarian is busy right now.", "📞");
    };

    const handleCallFailed = (data) => {
      toast.dismiss("call-sent");
      setLoading(false);
      setConnectionStatus("failed");
      showSingleToast("Veterinarian not available.", "😔");
    };

    const handleCallEnded = () => {
      toast.dismiss("call-sent");
      setLoading(false);
      setConnectionStatus("idle");
      setCallStatus(null);
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
    };

    socket.on("call-sent", handleCallSent);
    socket.on("call-accepted", handleCallAccepted);
    socket.on("call-rejected", handleCallRejected);
    socket.on("doctor-busy", handleDoctorBusy);
    socket.on("call-failed", handleCallFailed);
    socket.on("call-ended", handleCallEnded);
    socket.on("call-cancelled", handleCallEnded);

    return () => {
      socket.off("call-sent", handleCallSent);
      socket.off("call-accepted", handleCallAccepted);
      socket.off("call-rejected", handleCallRejected);
      socket.off("doctor-busy", handleDoctorBusy);
      socket.off("call-failed", handleCallFailed);
      socket.off("call-ended", handleCallEnded);
      socket.off("call-cancelled", handleCallEnded);

      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
    };
  }, [handleCallAccepted, showSingleToast]);

  // ---------------------------------------------------
  // 📞 Call initiation
  // ---------------------------------------------------
  const handleCallDoctor = useCallback(
    (doctor) => {
      const callId = `call_${Date.now()}_${Math.random()
        .toString(36)
        .substring(2, 8)}`;
      const channel = `channel_${callId}`;

      setCallStatus(null);
      setLoading(true);
      setShowLiveDoctorsModal(false);
      setShowSearchModal(true);
      setConnectionStatus("connecting");

      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }

      socket.emit("call-requested", {
        doctorId: doctor.id,
        patientId,
        channel,
        callId,
        timestamp: new Date().toISOString(),
      });

      timeoutRef.current = setTimeout(() => {
        if (loading || showSearchModal) handleNoResponse();
      }, 5 * 60 * 1000);
    },
    [patientId, loading, showSearchModal, handleNoResponse]
  );

  const startCall = useCallback(() => {
    const doctorsToCall = nearbyDoctors?.length ? nearbyDoctors : [];

    if (!doctorsToCall.length) {
      showSingleToast("No veterinarians available nearby.", "😔");
      return;
    }

    setLoading(true);
    setShowSearchModal(true);
    setConnectionStatus("connecting");

    const callId = `call_${Date.now()}_${Math.random()
      .toString(36)
      .substring(2, 8)}`;
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
      if (loading && !callStatus) handleNoResponse();
    }, 5 * 60 * 1000);
  }, [nearbyDoctors, patientId, loading, callStatus, handleNoResponse, showSingleToast]);

  // ---------------------------------------------------
  // 🧭 Button state & UI
  // ---------------------------------------------------
  const getButtonState = () => {
    if (loading) return "loading";
    if (connectionStatus === "no_doctors" || connectionStatus === "failed")
      return "unavailable";
    if (!nearbyDoctors?.length && !liveDoctors?.length)
      return "unavailable";
    return "available";
  };

  const buttonState = getButtonState();
  const buttonDisabled =
    buttonState === "unavailable" || buttonState === "loading";

  const getButtonText = () => {
    switch (buttonState) {
      case "loading":
        return "Searching...";
      case "unavailable":
        return "No Vets Available";
      default:
        return "Video Consultation";
    }
  };

  const getButtonIcon = () => {
    switch (buttonState) {
      case "loading":
        return (
          <div className="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
        );
      case "unavailable":
        return <span className="text-white text-xs">🐾</span>;
      default:
        return (
          <div className="w-4 h-4 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <span className="text-white text-xs">📹</span>
          </div>
        );
    }
  };

  return (
    <>
      <div className="mb-2">
        <button
          className={`w-full relative overflow-hidden rounded-lg transition-all duration-200 ${
            buttonDisabled ? "opacity-50 cursor-not-allowed" : "hover:shadow-md"
          } ${buttonState === "loading" ? "opacity-70" : ""}`}
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
            <div className="absolute inset-0 bg-purple-600 rounded-lg shadow-md"></div>
          )}

          <div
            className={`relative w-full py-2 px-3 rounded-lg bg-gradient-to-r transition-all duration-200 ${
              buttonState === "loading"
                ? "from-gray-400 to-gray-500"
                : buttonState === "unavailable"
                ? "from-gray-400 to-gray-500"
                : "from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700"
            }`}
          >
            <div className="flex items-center justify-center gap-2">
              {getButtonIcon()}
              <span className="text-white font-semibold text-xs tracking-wide break-words">
                {getButtonText()}
              </span>
            </div>
          </div>
        </button>

        {buttonState === "available" && (
          <div className="flex items-center justify-center gap-1 mt-1">
            <span className="text-green-500 text-[10px]">✓</span>
            <span className="text-[9px] font-medium text-green-600 break-words text-center">
              Licensed vets • Secure • Instant
            </span>
          </div>
        )}

        {connectionStatus === "failed" && (
          <div className="flex flex-col items-center justify-center gap-1 mt-2 text-center">
            <div className="text-red-600 text-xs font-medium">
              No veterinarians responded
            </div>
            <button
              onClick={() => {
                setConnectionStatus("idle");
                startCall();
              }}
              className="text-[10px] text-purple-600 hover:underline"
            >
              Retry
            </button>
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
          toast.dismiss("call-sent");
        }}
        onFailure={handleNoResponse}
      />
    </>
  );
};
// ------------------- EmergencyStatusBox - Professional Compact -------------------
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
      }, 200);
      return () => clearTimeout(timer);
    }
  }, [decision, isTypingComplete]);

  if (!decision || !isTypingComplete || !isVisible) return null;

  // Professional compact container
  const Container = ({ children, gradientFrom, gradientTo, borderColor }) => (
    <div className="my-1 mx-0 max-w-full animate-fade-in-up">
      <div className={`bg-gradient-to-r ${gradientFrom} ${gradientTo} rounded-lg overflow-hidden border ${borderColor} shadow-sm`}>
        <div className="p-2">
          {children}
        </div>
      </div>
    </div>
  );

  if (decision.includes("EMERGENCY")) {
    return (
      <>
        <Container
          gradientFrom="from-red-50"
          gradientTo="to-red-100"
          borderColor="border-red-200"
        >
          <div className="flex items-start gap-1.5 mb-1.5">
            <div className="w-5 h-5 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center shadow-sm flex-shrink-0">
              <span className="text-white text-[10px]">🚨</span>
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-1 mb-0.5">
                <div className="w-1 h-1 bg-red-600 rounded-full animate-pulse"></div>
                <span className="text-[7px] font-black text-red-600 uppercase tracking-wide">
                  URGENT
                </span>
              </div>
              <h3 className="text-xs font-bold text-red-900 mb-0.5 break-words">
                Emergency Care
              </h3>
            </div>
          </div>

          <div className="flex items-start gap-1 p-1 bg-white bg-opacity-70 rounded border border-red-200 mb-1.5">
            <span className="text-red-600 flex-shrink-0 text-[10px]">⚠</span>
            <p className="text-red-800 font-medium text-[10px] break-words flex-1 leading-relaxed">
              Immediate veterinary attention needed.
            </p>
          </div>

          <button
            onClick={() => setShowAppointmentModal(true)}
            className="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-1 rounded font-bold flex items-center justify-center gap-1 transition-all text-[10px]"
          >
            <span className="text-[10px]">🏥</span>
            <span className="break-words">Find Emergency Clinic</span>
          </button>
        </Container>

        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={() => setShowAppointmentModal(false)}
          nearbyDoctors={nearbyDoctors}
          onBook={(appointment) => {
            console.log("Appointment booked:", appointment);
            toast.success(
              `Appointment with ${appointment.doctor.name} booked!`,
              { duration: 2000, icon: '🐾' }
            );
            setShowAppointmentModal(false);
          }}
        />
      </>
    );
  }

  if (decision.includes("VIDEO_CONSULT")) {
    return (
      <Container
        gradientFrom="from-purple-50"
        gradientTo="to-indigo-100"
        borderColor="border-purple-200"
      >
        <div className="flex items-start gap-1 mb-1">
          <div className="w-5 h-5 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-sm flex-shrink-0">
            <span className="text-white text-[10px]">📹</span>
          </div>
          <div className="flex-1 min-w-0">
            <h3 className="text-xs font-bold text-purple-900 mb-0.5 leading-tight">
              Video Consultation
            </h3>
            <p className="text-[9px] text-purple-700 font-medium leading-tight">
              Connect instantly
            </p>
          </div>
        </div>
  
        <div className="space-y-0.5 p-1 bg-white bg-opacity-70 rounded border border-purple-200 mb-1">
          <div className="flex items-center gap-1">
            <span className="text-green-500 flex-shrink-0 text-[9px]">✓</span>
            <span className="text-purple-800 font-medium text-[9px] leading-tight">
              Instant consultation
            </span>
          </div>
          <div className="flex items-center gap-1">
            <span className="text-green-500 flex-shrink-0 text-[9px]">✓</span>
            <span className="text-purple-800 font-medium text-[9px] leading-tight">
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

  if (decision.includes("IN_CLINIC")) {
    return (
      <>
        <Container
          gradientFrom="from-gray-50"
          gradientTo="to-blue-50"
          borderColor="border-gray-200"
        >
          <div className="flex items-start gap-1.5 mb-1.5">
            <div className="w-5 h-5 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-sm flex-shrink-0">
              <span className="text-white text-[10px]">🏥</span>
            </div>
            <div className="flex-1 min-w-0">
              <h3 className="text-xs font-bold text-gray-900 mb-0.5 break-words">
                Consultation Options
              </h3>
            </div>
          </div>

          <div className="space-y-1">
            <StartCallButton
              nearbyDoctors={nearbyDoctors}
              navigation={navigation}
            />

            <div className="flex items-center my-1">
              <div className="flex-1 h-px bg-gray-300"></div>
              <span className="mx-1 text-[7px] font-semibold text-gray-500 uppercase">OR</span>
              <div className="flex-1 h-px bg-gray-300"></div>
            </div>

            <button
              onClick={() => setShowAppointmentModal(true)}
              className="w-full bg-purple-50 hover:bg-purple-100 text-purple-700 py-1 rounded font-semibold flex items-center justify-center gap-1 transition-all border border-purple-200 hover:border-purple-300 text-[10px]"
            >
              <span className="text-[10px]">🏥</span>
              <span className="break-words">Book Clinic Visit</span>
            </button>
          </div>
        </Container>

        <DoctorAppointmentModal
          visible={showAppointmentModal}
          onClose={() => setShowAppointmentModal(false)}
          nearbyDoctors={nearbyDoctors}
          onBook={(appointment) => {
            console.log("Appointment booked:", appointment);
            toast.success(
              `Appointment with ${appointment.doctor.name} booked!`,
              { duration: 2000, icon: '🐾' }
            );
            setShowAppointmentModal(false);
          }}
        />
      </>
    );
  }

  return null;
};

// ------------------- DoctorSearchModal - Professional Compact -------------------
const DoctorSearchModal = ({ visible, onClose, onFailure, searchTime = 5 * 60 * 1000 }) => {
  const [dots, setDots] = useState("");
  const [elapsedTime, setElapsedTime] = useState(0);
  const timeRef = useRef(null);

  useEffect(() => {
    if (visible) {
      setElapsedTime(0);
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
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-2">
      <div className="bg-white rounded-xl p-4 w-full max-w-xs shadow-xl relative overflow-hidden">
        {/* Ripple Effects */}
        <div className="absolute top-4 left-1/2 transform -translate-x-1/2 w-20 h-20 bg-purple-600 rounded-full animate-ping opacity-20"></div>

        {/* Search Icon */}
        <div className="relative z-10 mb-3 flex justify-center">
          <div className="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-700 rounded-full flex items-center justify-center shadow-md shadow-purple-500/30 animate-pulse">
            <span className="text-lg text-white">🐾</span>
          </div>
        </div>

        {/* Title */}
        <h2 className="text-sm font-bold text-gray-900 text-center mb-1">
          Finding Veterinarians{dots}
        </h2>
        <p className="text-xs text-gray-600 text-center mb-3">
          Searching available doctors
        </p>

        {/* Time Indicator */}
        <div className="flex items-center justify-center gap-1 mb-2 px-2 py-1 bg-purple-50 rounded border border-purple-200">
          <span className="text-purple-600 text-xs">⏱</span>
          <span className="text-xs font-semibold text-purple-600">
            {formatTime(elapsedTime)}
          </span>
        </div>

        {/* Progress Bar */}
        <div className="w-full h-1 bg-gray-200 rounded-full mb-4 overflow-hidden">
          <div 
            className="h-full bg-gradient-to-r from-purple-500 to-purple-700 rounded-full transition-all duration-300"
            style={{ width: `${Math.min((elapsedTime / (searchTime / 1000)) * 100, 100)}%` }}
          ></div>
        </div>

        {/* Buttons */}
        <div className="flex gap-2">
          <button
            onClick={() => {
              onClose();
              setElapsedTime(0);
            }}
            className="flex-1 py-1.5 px-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded text-xs transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={() => {
              onClose();
              setElapsedTime(0);
              onFailure?.();
            }}
            className="flex-1 py-1.5 px-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded text-xs transition-colors"
          >
            Try Alternative
          </button>
        </div>
      </div>
    </div>
  );
};

export { EmergencyStatusBox, MessageBubble, StartCallButton };