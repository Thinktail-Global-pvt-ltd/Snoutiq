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

  const { user } = useContext(AuthContext);
  const isVetDoctor = user && user.role === "vet";

  // 🔹 Initialize audio object only (no auto play)
  useEffect(() => {
    if (ringtone && isVetDoctor) {
      ringtoneRef.current = new Audio(ringtone);
      ringtoneRef.current.preload = "auto";
      ringtoneRef.current.volume = 0.8;
      ringtoneRef.current.loop = true;
      setAudioInitialized(true);
    }
  }, [isVetDoctor]);

  // 🔹 Polling for incoming calls
  useEffect(() => {
    if (!isVetDoctor) return;

    const interval = setInterval(async () => {
      try {
        const res = await axios.get("/api/call/incoming", {
          params: { doctor_id: user?.id },
        });

        if (res.data && res.data.call) {
          setIncomingCall(res.data.call);
        }
      } catch (err) {
        console.error("Polling error:", err);
      }
    }, 5000); // हर 5 सेकंड में चेक

    return () => clearInterval(interval);
  }, [isVetDoctor, user?.id]);

  // 🔹 Handle ringtone + timer only when incomingCall present
  useEffect(() => {
    if (!isVetDoctor) return;

    const playRingtone = async () => {
      if (incomingCall && ringtoneRef.current && !isMuted) {
        try {
          ringtoneRef.current.currentTime = 0;
          ringtoneRef.current.loop = true;
          await ringtoneRef.current.play();
          setRequiresUserInteraction(false);
        } catch {
          setRequiresUserInteraction(true);
        }
      }
    };

    if (incomingCall) {
      setCallDuration(0);

      callTimerRef.current = setInterval(() => {
        setCallDuration((prev) => prev + 1);
      }, 1000);

      playRingtone();

      const timeoutId = setTimeout(() => {
        handleCallTimeout();
      }, 30000);

      return () => {
        if (ringtoneRef.current) {
          ringtoneRef.current.pause();
          ringtoneRef.current.currentTime = 0;
        }
        if (callTimerRef.current) clearInterval(callTimerRef.current);
        clearTimeout(timeoutId);
      };
    }
  }, [incomingCall, isMuted, isVetDoctor]);

  // 🔹 Manual audio enable (browser policy)
  const enableAudio = async () => {
    if (ringtoneRef.current && isVetDoctor) {
      try {
        await ringtoneRef.current.play();
        ringtoneRef.current.pause();
        ringtoneRef.current.currentTime = 0;
        setAudioInitialized(true);
        setRequiresUserInteraction(false);

        if (incomingCall && !isMuted) {
          ringtoneRef.current.play().catch(() => {});
        }
      } catch (err) {
        console.error("Enable audio failed:", err);
      }
    }
  };

  // 🔹 Toggle mute
  const toggleMute = () => {
    if (!isVetDoctor) return;
    const newMuted = !isMuted;
    setIsMuted(newMuted);

    if (ringtoneRef.current) {
      if (newMuted) {
        ringtoneRef.current.pause();
      } else if (incomingCall && audioInitialized) {
        ringtoneRef.current.play().catch(() => {});
      }
    }
  };

  // 🔹 Timeout handler
  const handleCallTimeout = async () => {
    if (!incomingCall || !isVetDoctor) return;
    try {
      await axios.post(`/api/call/${incomingCall.callId}/timeout`, {
        doctor_id: user?.id,
      });
    } catch (error) {
      console.error("Timeout error:", error);
    } finally {
      setIncomingCall(null);
    }
  };

  // 🔹 Accept call
  const acceptCall = async () => {
    if (!incomingCall || !isVetDoctor) return;

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
      console.error("Accept error:", error);
    }
  };

  // 🔹 Reject call
  const rejectCall = async () => {
    if (!incomingCall || !isVetDoctor) return;

    if (ringtoneRef.current) {
      ringtoneRef.current.pause();
      ringtoneRef.current.currentTime = 0;
    }

    try {
      await axios.post(`/api/call/${incomingCall.callId}/reject`, {
        doctor_id: user?.id,
      });
    } catch (error) {
      console.error("Reject error:", error);
    } finally {
      setIncomingCall(null);
    }
  };

  // Format timer
  const formatDuration = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, "0")}:${secs
      .toString()
      .padStart(2, "0")}`;
  };

  if (!isVetDoctor) return null;

  return (
    <div>
      <Transition.Root show={!!incomingCall} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => {}}>
          {/* Overlay */}
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

          {/* Popup */}
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
                <div className="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 text-white flex items-center justify-between">
                  <div className="flex items-center space-x-2">
                    <PhoneIcon className="w-5 h-5 animate-bounce" />
                    <span className="text-sm font-medium">Incoming Call</span>
                  </div>
                  <div className="flex items-center space-x-3">
                    <div className="text-sm font-mono">
                      {formatDuration(callDuration)}
                    </div>
                    <button
                      onClick={toggleMute}
                      className="p-1 rounded-full hover:bg-white hover:bg-opacity-20"
                    >
                      {isMuted ? (
                        <SpeakerXMarkIcon className="w-5 h-5 text-red-300" />
                      ) : (
                        <SpeakerWaveIcon className="w-5 h-5 text-white" />
                      )}
                    </button>
                  </div>
                </div>

                {/* Caller Info */}
                <div className="px-6 py-8 text-center">
                  <div className="w-24 h-24 mx-auto mb-4 rounded-full bg-gray-200 flex items-center justify-center">
                    {incomingCall?.callerImage ? (
                      <img
                        src={incomingCall.callerImage}
                        alt={incomingCall.callerName}
                        className="w-full h-full rounded-full object-cover"
                      />
                    ) : (
                      <UserIcon className="w-12 h-12 text-gray-400" />
                    )}
                  </div>

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
                      <span className="font-medium">{incomingCall.petName}</span>
                    </p>
                  )}

                  <p className="text-xs text-gray-500 mt-2">
                    Auto-end in {30 - callDuration} seconds
                  </p>
                </div>

                {/* Action Buttons */}
                <div className="px-6 pb-6">
                  <div className="flex justify-center space-x-6">
                    <button
                      onClick={rejectCall}
                      className="flex items-center justify-center w-16 h-16 bg-red-500 hover:bg-red-600 rounded-full shadow-lg"
                    >
                      <PhoneXMarkIcon className="w-8 h-8 text-white" />
                    </button>
                    <button
                      onClick={acceptCall}
                      className="flex items-center justify-center w-16 h-16 bg-green-500 hover:bg-green-600 rounded-full shadow-lg animate-pulse"
                    >
                      <PhoneIcon className="w-8 h-8 text-white" />
                    </button>
                  </div>

                  {requiresUserInteraction && (
                    <div className="mt-4 text-center">
                      <button
                        onClick={enableAudio}
                        className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
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
