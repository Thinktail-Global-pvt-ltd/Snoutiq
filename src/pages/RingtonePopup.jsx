import { Dialog, Transition } from "@headlessui/react";
import { Fragment, useRef, useState, useEffect, useContext } from "react";
import {
  UserIcon,
  PhoneIcon,
  PhoneXMarkIcon,
  SpeakerWaveIcon,
  SpeakerXMarkIcon,
} from "@heroicons/react/24/outline";
import ringtone from "../assets/ringtone.mp3";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import { AuthContext } from "../auth/AuthContext";

const RingtonePopup = () => {
  const [incomingCall, setIncomingCall] = useState(null);
  const [callDuration, setCallDuration] = useState(0);
  const [isMuted, setIsMuted] = useState(false);
  const [audioInitialized, setAudioInitialized] = useState(false);
  const [requiresUserInteraction, setRequiresUserInteraction] = useState(true);

  const ringtoneRef = useRef(null);
  const callTimerRef = useRef(null);
  const navigate = useNavigate();

  const {user} =useContext(AuthContext);
  console.log(user);
  
  useEffect(() => {
    if (ringtone) {
      ringtoneRef.current = new Audio(ringtone);
      ringtoneRef.current.preload = "auto";
      ringtoneRef.current.volume = 0.8;
      ringtoneRef.current.loop = true;
      console.log("Audio initialized");

      ringtoneRef.current.play().then(() => {
        ringtoneRef.current.pause();
        ringtoneRef.current.currentTime = 0;
        setAudioInitialized(true);
        setRequiresUserInteraction(false);
        console.log("Audio pre-initialized successfully");
      }).catch(err => {
        console.log("Audio requires user interaction:", err);
        setRequiresUserInteraction(true);
      });
    }
  }, []);

  // Handle ringtone when call comes
  useEffect(() => {
    const playRingtone = async () => {
      if (incomingCall && ringtoneRef.current && !isMuted) {
        try {
          ringtoneRef.current.currentTime = 0;
          ringtoneRef.current.loop = true;
          await ringtoneRef.current.play();
          console.log("Ringtone started playing");
        } catch (error) {
          console.error("Failed to play ringtone:", error);
          setRequiresUserInteraction(true);
        }
      }
    };

    if (incomingCall) {
      // Start call timer
      setCallDuration(0);
      callTimerRef.current = setInterval(() => {
        setCallDuration((prev) => prev + 1);
      }, 1000);

      // Play ringtone
      playRingtone();

      // Auto-timeout after 30s
      const timeoutId = setTimeout(() => {
        handleCallTimeout();
      }, 30000);

      return () => {
        if (ringtoneRef.current) {
          ringtoneRef.current.pause();
          ringtoneRef.current.currentTime = 0;
        }
        if (callTimerRef.current) {
          clearInterval(callTimerRef.current);
        }
        clearTimeout(timeoutId);
      };
    }
  }, [incomingCall, isMuted]);

  // Manual audio enable function
  const enableAudio = async () => {
    if (ringtoneRef.current) {
      try {
        await ringtoneRef.current.play();
        ringtoneRef.current.pause();
        ringtoneRef.current.currentTime = 0;
        setAudioInitialized(true);
        setRequiresUserInteraction(false);
        console.log("Audio manually enabled");
        
        // Try to play again if call is active
        if (incomingCall && !isMuted) {
          ringtoneRef.current.play().catch(console.error);
        }
      } catch (err) {
        console.error("Manual audio enable failed:", err);
      }
    }
  };

  // Toggle mute/unmute
  const toggleMute = () => {
    const newMutedState = !isMuted;
    setIsMuted(newMutedState);

    if (ringtoneRef.current) {
      if (newMutedState) {
        // Mute - pause ringtone
        ringtoneRef.current.pause();
        console.log("Ringtone muted");
      } else {
        // Unmute - resume ringtone if call is active
        if (incomingCall && audioInitialized) {
          ringtoneRef.current.play().catch(console.error);
          console.log("Ringtone unmuted");
        }
      }
    }
  };

  // Format duration
  const formatDuration = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, "0")}:${secs
      .toString()
      .padStart(2, "0")}`;
  };

  // Handle call timeout
  const handleCallTimeout = async () => {
    if (!incomingCall) return;

    console.log("Call timed out");
    try {
      await axios.post(`/api/call/${incomingCall.callId}/timeout`, {
        doctor_id: user?.id,
      });
    } catch (error) {
      console.error("Error handling call timeout:", error);
    } finally {
      setIncomingCall(null);
    }
  };

  // Accept call
  const acceptCall = async () => {
    if (!incomingCall) return;

    // Stop ringtone
    if (ringtoneRef.current) {
      ringtoneRef.current.pause();
      ringtoneRef.current.currentTime = 0;
    }

    try {
      await axios.post(`/api/call/${incomingCall.callId}/accept`, {
        doctor_id: user?.id,
      });
      navigate(`/video-call/${incomingCall.callId}`);
      setIncomingCall(null);
    } catch (error) {
      console.error("Error accepting call:", error);
    }
  };

  // Reject call
  const rejectCall = async () => {
    if (!incomingCall) return;

    // Stop ringtone
    if (ringtoneRef.current) {
      ringtoneRef.current.pause();
      ringtoneRef.current.currentTime = 0;
    }

    try {
      await axios.post(`/api/call/${incomingCall.callId}/reject`, {
        doctor_id: user?.id,
      });
    } catch (error) {
      console.error("Error rejecting call:", error);
    } finally {
      setIncomingCall(null);
    }
  };

//   // Simulate incoming call (remove in production)
//   useEffect(() => {
//     const timer = setTimeout(() => {
//       console.log("Simulating incoming call...");
//       setIncomingCall({
//         callId: 1,
//         callerName: "Dr. John Smith",
//         callerImage: null,
//         callType: "Emergency Consultation",
//         petName: "Buddy",
//       });
//     }, 3000);

//     return () => clearTimeout(timer);
//   }, []);



  return (
    <div>
      <Transition.Root show={!!incomingCall} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => {}}>
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-300"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-200"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black bg-opacity-80 backdrop-blur-sm" />
          </Transition.Child>

          <div className="fixed inset-0 flex items-center justify-center p-4">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 scale-95 translate-y-4"
              enterTo="opacity-100 scale-100 translate-y-0"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 scale-100 translate-y-0"
              leaveTo="opacity-0 scale-95 translate-y-4"
            >
              <Dialog.Panel className="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                {/* Header */}
                <div className="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 text-white">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <div className="relative">
                        <PhoneIcon className="w-5 h-5 animate-bounce" />
                        <div className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                      </div>
                      <span className="text-sm font-medium">Incoming Call</span>
                    </div>
                    <div className="flex items-center space-x-3">
                      <div className="text-sm font-mono">
                        {formatDuration(callDuration)}
                      </div>
                      {/* Mute/Unmute Button */}
                      <button
                        onClick={toggleMute}
                        className="p-1 rounded-full hover:bg-white hover:bg-opacity-20 transition-colors"
                      >
                        {isMuted ? (
                          <SpeakerXMarkIcon className="w-5 h-5 text-red-300" />
                        ) : (
                          <SpeakerWaveIcon className="w-5 h-5 text-white" />
                        )}
                      </button>
                    </div>
                  </div>
                </div>

                {/* Caller Info */}
                <div className="px-6 py-8 text-center">
                  {/* Avatar */}
                  <div className="relative mx-auto mb-4">
                    <div className="w-24 h-24 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 p-1 animate-pulse">
                      <div className="w-full h-full rounded-full bg-gray-100 flex items-center justify-center">
                        {incomingCall?.callerImage ? (
                          <img
                            src={incomingCall.callerImage}
                            alt={incomingCall.callerName}
                            className="w-full h-full object-cover rounded-full"
                          />
                        ) : (
                          <UserIcon className="w-12 h-12 text-gray-400" />
                        )}
                      </div>
                    </div>
                    <div className="absolute inset-0 rounded-full border-4 border-blue-500 opacity-20 animate-ping"></div>
                    <div className="absolute inset-2 rounded-full border-2 border-purple-500 opacity-30 animate-ping"></div>
                  </div>

                  {/* Call Details */}
                  <div className="space-y-2">
                    <h3 className="text-xl font-semibold text-gray-900">
                      {incomingCall?.callerName}
                    </h3>

                    {incomingCall?.callType && (
                      <p className="text-sm text-blue-600 font-medium bg-blue-50 px-3 py-1 rounded-full inline-block">
                        {incomingCall.callType}
                      </p>
                    )}

                    {incomingCall?.petName && (
                      <p className="text-sm text-gray-600">
                        Regarding:{" "}
                        <span className="font-medium">
                          {incomingCall.petName}
                        </span>
                      </p>
                    )}

                    <p className="text-xs text-gray-500 mt-2">
                      Auto-end in {30 - callDuration} seconds
                    </p>

                    {isMuted && (
                      <p className="text-xs text-red-500 font-medium">
                        Sound is muted
                      </p>
                    )}
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="px-6 pb-6">
                  <div className="flex justify-center space-x-6">
                    <button
                      onClick={rejectCall}
                      className="flex items-center justify-center w-16 h-16 bg-red-500 hover:bg-red-600 rounded-full shadow-lg transform transition-all duration-200 hover:scale-110 active:scale-95"
                    >
                      <PhoneXMarkIcon className="w-8 h-8 text-white" />
                    </button>

                    <button
                      onClick={acceptCall}
                      className="flex items-center justify-center w-16 h-16 bg-green-500 hover:bg-green-600 rounded-full shadow-lg transform transition-all duration-200 hover:scale-110 active:scale-95 animate-pulse"
                    >
                      <PhoneIcon className="w-8 h-8 text-white" />
                    </button>
                  </div>

                  <div className="flex justify-center space-x-6 mt-3">
                    <span className="text-xs text-gray-500 w-16 text-center">
                      Decline
                    </span>
                    <span className="text-xs text-gray-500 w-16 text-center">
                      Accept
                    </span>
                  </div>

                  {/* Enable Audio Button */}
                  {requiresUserInteraction && (
                    <div className="mt-4 text-center">
                      <button 
                        onClick={enableAudio}
                        className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                      >
                        🔊 Enable Call Audio
                      </button>
                      <p className="text-xs text-gray-500 mt-2">
                        Click to enable ringtone sound
                      </p>
                    </div>
                  )}
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </Dialog>
      </Transition.Root>
    </div>
  );
};

export default RingtonePopup;