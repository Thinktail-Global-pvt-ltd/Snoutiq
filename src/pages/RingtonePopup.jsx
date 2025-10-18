// import { Dialog, Transition } from "@headlessui/react";
// import { Fragment, useRef, useState, useEffect, useContext } from "react";
// import {
//   UserIcon,
//   PhoneIcon,
//   PhoneXMarkIcon,
//   SpeakerWaveIcon,
//   SpeakerXMarkIcon,
// } from "@heroicons/react/24/outline";
// import ringtone from "../assets/ringtone.mp3";
// import axios from "axios";
// import { useNavigate } from "react-router-dom";
// import { AuthContext } from "../auth/AuthContext";

// const RingtonePopup = () => {
//   const [incomingCall, setIncomingCall] = useState(null);
//   const [callDuration, setCallDuration] = useState(0);
//   const [isMuted, setIsMuted] = useState(false);
//   const [audioInitialized, setAudioInitialized] = useState(false);
//   const [requiresUserInteraction, setRequiresUserInteraction] = useState(true);

//   const ringtoneRef = useRef(null);
//   const callTimerRef = useRef(null);
//   const navigate = useNavigate();

//   const { user } = useContext(AuthContext);
//   const isVetDoctor = user && user.role === "vet";

//   // 🔹 Initialize audio object only (no auto play)
//   useEffect(() => {
//     if (ringtone && isVetDoctor) {
//       ringtoneRef.current = new Audio(ringtone);
//       ringtoneRef.current.preload = "auto";
//       ringtoneRef.current.volume = 0.8;
//       ringtoneRef.current.loop = true;
//       setAudioInitialized(true);
//     }
//   }, [isVetDoctor]);

//   // 🔹 Polling for incoming calls
//   useEffect(() => {
//     if (!isVetDoctor) return;

//     const interval = setInterval(async () => {
//       try {
//         const res = await axios.get("/api/call/incoming", {
//           params: { doctor_id: user?.id },
//         });

//         if (res.data && res.data.call) {
//           setIncomingCall(res.data.call);
//         }
//       } catch (err) {
//         console.error("Polling error:", err);
//       }
//     }, 5000); // हर 5 सेकंड में चेक

//     return () => clearInterval(interval);
//   }, [isVetDoctor, user?.id]);

//   // 🔹 Handle ringtone + timer only when incomingCall present
//   useEffect(() => {
//     if (!isVetDoctor) return;

//     const playRingtone = async () => {
//       if (incomingCall && ringtoneRef.current && !isMuted) {
//         try {
//           ringtoneRef.current.currentTime = 0;
//           ringtoneRef.current.loop = true;
//           await ringtoneRef.current.play();
//           setRequiresUserInteraction(false);
//         } catch {
//           setRequiresUserInteraction(true);
//         }
//       }
//     };

//     if (incomingCall) {
//       setCallDuration(0);

//       callTimerRef.current = setInterval(() => {
//         setCallDuration((prev) => prev + 1);
//       }, 1000);

//       playRingtone();

//       const timeoutId = setTimeout(() => {
//         handleCallTimeout();
//       }, 30000);

//       return () => {
//         if (ringtoneRef.current) {
//           ringtoneRef.current.pause();
//           ringtoneRef.current.currentTime = 0;
//         }
//         if (callTimerRef.current) clearInterval(callTimerRef.current);
//         clearTimeout(timeoutId);
//       };
//     }
//   }, [incomingCall, isMuted, isVetDoctor]);

//   // 🔹 Manual audio enable (browser policy)
//   const enableAudio = async () => {
//     if (ringtoneRef.current && isVetDoctor) {
//       try {
//         await ringtoneRef.current.play();
//         ringtoneRef.current.pause();
//         ringtoneRef.current.currentTime = 0;
//         setAudioInitialized(true);
//         setRequiresUserInteraction(false);

//         if (incomingCall && !isMuted) {
//           ringtoneRef.current.play().catch(() => {});
//         }
//       } catch (err) {
//         console.error("Enable audio failed:", err);
//       }
//     }
//   };

//   // 🔹 Toggle mute
//   const toggleMute = () => {
//     if (!isVetDoctor) return;
//     const newMuted = !isMuted;
//     setIsMuted(newMuted);

//     if (ringtoneRef.current) {
//       if (newMuted) {
//         ringtoneRef.current.pause();
//       } else if (incomingCall && audioInitialized) {
//         ringtoneRef.current.play().catch(() => {});
//       }
//     }
//   };

//   // 🔹 Timeout handler
//   const handleCallTimeout = async () => {
//     if (!incomingCall || !isVetDoctor) return;
//     try {
//       await axios.post(`/api/call/${incomingCall.callId}/timeout`, {
//         doctor_id: user?.id,
//       });
//     } catch (error) {
//       console.error("Timeout error:", error);
//     } finally {
//       setIncomingCall(null);
//     }
//   };

//   // 🔹 Accept call
//   const acceptCall = async () => {
//     if (!incomingCall || !isVetDoctor) return;

//     if (ringtoneRef.current) {
//       ringtoneRef.current.pause();
//       ringtoneRef.current.currentTime = 0;
//     }

//     try {
//       await axios.post(`/api/call/${incomingCall.callId}/accept`, {
//         doctor_id: user?.id,
//       });
//       navigate(`/video-call/${incomingCall.callId}`);
//       setIncomingCall(null);
//     } catch (error) {
//       console.error("Accept error:", error);
//     }
//   };

//   // 🔹 Reject call
//   const rejectCall = async () => {
//     if (!incomingCall || !isVetDoctor) return;

//     if (ringtoneRef.current) {
//       ringtoneRef.current.pause();
//       ringtoneRef.current.currentTime = 0;
//     }

//     try {
//       await axios.post(`/api/call/${incomingCall.callId}/reject`, {
//         doctor_id: user?.id,
//       });
//     } catch (error) {
//       console.error("Reject error:", error);
//     } finally {
//       setIncomingCall(null);
//     }
//   };

//   // Format timer
//   const formatDuration = (seconds) => {
//     const mins = Math.floor(seconds / 60);
//     const secs = seconds % 60;
//     return `${mins.toString().padStart(2, "0")}:${secs
//       .toString()
//       .padStart(2, "0")}`;
//   };

//   if (!isVetDoctor) return null;

//   return (
//     <div>
//       <Transition.Root show={!!incomingCall} as={Fragment}>
//         <Dialog as="div" className="relative z-50" onClose={() => {}}>
//           {/* Overlay */}
//           <Transition.Child
//             as={Fragment}
//             enter="ease-out duration-300"
//             enterFrom="opacity-0"
//             enterTo="opacity-100"
//             leave="ease-in duration-200"
//             leaveFrom="opacity-100"
//             leaveTo="opacity-0"
//           >
//             <div className="fixed inset-0 bg-black bg-opacity-80 backdrop-blur-sm" />
//           </Transition.Child>

//           {/* Popup */}
//           <div className="fixed inset-0 flex items-center justify-center p-4">
//             <Transition.Child
//               as={Fragment}
//               enter="ease-out duration-300"
//               enterFrom="opacity-0 scale-95 translate-y-4"
//               enterTo="opacity-100 scale-100 translate-y-0"
//               leave="ease-in duration-200"
//               leaveFrom="opacity-100 scale-100 translate-y-0"
//               leaveTo="opacity-0 scale-95 translate-y-4"
//             >
//               <Dialog.Panel className="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
//                 {/* Header */}
//                 <div className="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 text-white flex items-center justify-between">
//                   <div className="flex items-center space-x-2">
//                     <PhoneIcon className="w-5 h-5 animate-bounce" />
//                     <span className="text-sm font-medium">Incoming Call</span>
//                   </div>
//                   <div className="flex items-center space-x-3">
//                     <div className="text-sm font-mono">
//                       {formatDuration(callDuration)}
//                     </div>
//                     <button
//                       onClick={toggleMute}
//                       className="p-1 rounded-full hover:bg-white hover:bg-opacity-20"
//                     >
//                       {isMuted ? (
//                         <SpeakerXMarkIcon className="w-5 h-5 text-red-300" />
//                       ) : (
//                         <SpeakerWaveIcon className="w-5 h-5 text-white" />
//                       )}
//                     </button>
//                   </div>
//                 </div>

//                 {/* Caller Info */}
//                 <div className="px-6 py-8 text-center">
//                   <div className="w-24 h-24 mx-auto mb-4 rounded-full bg-gray-200 flex items-center justify-center">
//                     {incomingCall?.callerImage ? (
//                       <img
//                         src={incomingCall.callerImage}
//                         alt={incomingCall.callerName}
//                         className="w-full h-full rounded-full object-cover"
//                       />
//                     ) : (
//                       <UserIcon className="w-12 h-12 text-gray-400" />
//                     )}
//                   </div>

//                   <h3 className="text-xl font-semibold text-gray-900">
//                     {incomingCall?.callerName}
//                   </h3>

//                   {incomingCall?.callType && (
//                     <p className="text-sm text-blue-600 font-medium bg-blue-50 px-3 py-1 rounded-full inline-block">
//                       {incomingCall.callType}
//                     </p>
//                   )}

//                   {incomingCall?.petName && (
//                     <p className="text-sm text-gray-600">
//                       Regarding:{" "}
//                       <span className="font-medium">{incomingCall.petName}</span>
//                     </p>
//                   )}

//                   <p className="text-xs text-gray-500 mt-2">
//                     Auto-end in {30 - callDuration} seconds
//                   </p>
//                 </div>

//                 {/* Action Buttons */}
//                 <div className="px-6 pb-6">
//                   <div className="flex justify-center space-x-6">
//                     <button
//                       onClick={rejectCall}
//                       className="flex items-center justify-center w-16 h-16 bg-red-500 hover:bg-red-600 rounded-full shadow-lg"
//                     >
//                       <PhoneXMarkIcon className="w-8 h-8 text-white" />
//                     </button>
//                     <button
//                       onClick={acceptCall}
//                       className="flex items-center justify-center w-16 h-16 bg-green-500 hover:bg-green-600 rounded-full shadow-lg animate-pulse"
//                     >
//                       <PhoneIcon className="w-8 h-8 text-white" />
//                     </button>
//                   </div>

//                   {requiresUserInteraction && (
//                     <div className="mt-4 text-center">
//                       <button
//                         onClick={enableAudio}
//                         className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
//                       >
//                         🔊 Enable Call Audio
//                       </button>
//                       <p className="text-xs text-gray-500 mt-2">
//                         Click to enable ringtone sound
//                       </p>
//                     </div>
//                   )}
//                 </div>
//               </Dialog.Panel>
//             </Transition.Child>
//           </div>
//         </Dialog>
//       </Transition.Root>
//     </div>
//   );
// };

// export default RingtonePopup;
// import React, { useEffect, useRef } from "react";
// import { socket } from "./socket";
// import { useNavigate } from "react-router-dom";

// export default function RingtonePopup({ call, doctorId, onClose }) {
//   const navigate = useNavigate();
//   const audioRef = useRef(null);

//   useEffect(() => {
//     const playRingtone = async () => {
//       try {
//         if (audioRef.current) await audioRef.current.play();
//       } catch (err) {
//         console.warn("⚠️ Ringtone autoplay blocked:", err);
//       }
//     };
//     playRingtone();

//     const timeout = setTimeout(() => handleReject(), 30000);

//     return () => {
//       clearTimeout(timeout);
//       if (audioRef.current) {
//         audioRef.current.pause();
//         audioRef.current.currentTime = 0;
//       }
//     };
//   }, []);

//   const handleAccept = () => {
//     if (audioRef.current) {
//       audioRef.current.pause();
//       audioRef.current.currentTime = 0;
//     }
//     socket.emit("call-accepted", { callId: call.id, doctorId, patientId: call.patientId, channel: call.channel });
//     onClose();
//     navigate(`/call-page/${call.channel}?uid=${doctorId}&role=host`);
//   };

//   const handleReject = () => {
//     if (audioRef.current) {
//       audioRef.current.pause();
//       audioRef.current.currentTime = 0;
//     }
//     socket.emit("call-rejected", { callId: call.id, doctorId, patientId: call.patientId });
//     onClose();
//   };

//   return (
//     <div style={{
//       position: "fixed",
//       top: "30%",
//       left: "50%",
//       transform: "translate(-50%, -30%)",
//       background: "#fff3cd",
//       border: "2px solid #f59e0b",
//       padding: 20,
//       borderRadius: 12,
//       boxShadow: "0 4px 10px rgba(0,0,0,0.2)",
//       zIndex: 1000,
//     }}>
//       <h3>📞 Incoming Call</h3>
//       <p><strong>Patient:</strong> {call.patientId}</p>
//       <p><strong>Channel:</strong> {call.channel}</p>
//       <audio ref={audioRef} src="/ringtone.mp3" loop preload="auto" />
//       <div style={{ marginTop: 12 }}>
//         <button onClick={handleAccept} style={{ padding: "8px 16px", marginRight: 8, borderRadius: 6, background: "green", color: "white", border: "none", fontWeight: "bold" }}>✅ Accept</button>
//         <button onClick={handleReject} style={{ padding: "8px 16px", borderRadius: 6, background: "red", color: "white", border: "none", fontWeight: "bold" }}>❌ Reject</button>
//       </div>
//     </div>
//   );
// }

// import React, { useEffect, useRef } from "react";
// import { socket } from "./socket";
// import { useNavigate } from "react-router-dom";

// export default function RingtonePopup({ call, doctorId, onClose }) {
//   const navigate = useNavigate();
//   const audioRef = useRef(null);

//   useEffect(() => {
//     const playRingtone = async () => {
//       try {
//         if (audioRef.current) await audioRef.current.play();
//       } catch (err) {
//         console.warn("⚠️ Ringtone autoplay blocked:", err);
//       }
//     };
//     playRingtone();

//     const timeout = setTimeout(() => handleReject(), 30000);

//     return () => {
//       clearTimeout(timeout);
//       if (audioRef.current) {
//         audioRef.current.pause();
//         audioRef.current.currentTime = 0;
//       }
//     };
//   }, []);

//   const handleAccept = () => {
//     if (audioRef.current) {
//       audioRef.current.pause();
//       audioRef.current.currentTime = 0;
//     }

//     // Send call accepted with payment required flag
//     socket.emit("call-accepted", {
//       callId: call.id,
//       doctorId,
//       patientId: call.patientId,
//       channel: call.channel,
//       requiresPayment: true // New flag
//     });

//     onClose();

//     // Doctor goes to a waiting room instead of direct video call
//     navigate(`/doctor-waiting/${call.channel}?callId=${call.id}&patientId=${call.patientId}`);
//   };

//   const handleReject = () => {
//     if (audioRef.current) {
//       audioRef.current.pause();
//       audioRef.current.currentTime = 0;
//     }
//     socket.emit("call-rejected", {
//       callId: call.id,
//       doctorId,
//       patientId: call.patientId
//     });
//     onClose();
//   };

//   return (
//     <div style={{
//       position: "fixed",
//       top: "30%",
//       left: "50%",
//       transform: "translate(-50%, -30%)",
//       background: "#fff3cd",
//       border: "2px solid #f59e0b",
//       padding: 20,
//       borderRadius: 12,
//       boxShadow: "0 4px 10px rgba(0,0,0,0.2)",
//       zIndex: 1000,
//     }}>
//       <h3>📞 Incoming Call</h3>
//       <p><strong>Patient:</strong> {call.patientId}</p>
//       <p><strong>Channel:</strong> {call.channel}</p>
//       <p style={{ fontSize: 12, color: "#666", marginTop: 8 }}>
//         Patient will be redirected to payment before video call starts
//       </p>

//       <audio ref={audioRef} src="/ringtone.mp3" loop preload="auto" />

//       <div style={{ marginTop: 12 }}>
//         <button
//           onClick={handleAccept}
//           style={{
//             padding: "8px 16px",
//             marginRight: 8,
//             borderRadius: 6,
//             background: "green",
//             color: "white",
//             border: "none",
//             fontWeight: "bold"
//           }}
//         >
//           ✅ Accept & Request Payment
//         </button>
//         <button
//           onClick={handleReject}
//           style={{
//             padding: "8px 16px",
//             borderRadius: 6,
//             background: "red",
//             color: "white",
//             border: "none",
//             fontWeight: "bold"
//           }}
//         >
//           ❌ Reject
//         </button>
//       </div>
//     </div>
//   );
// }

// import React, { useEffect, useRef, useState } from "react";
// import { useNavigate } from "react-router-dom";

// export default function RingtonePopup({ call, doctorId, onClose }) {
//   const navigate = useNavigate();
//   const audioRef = useRef(null);
//   const [requiresInteraction, setRequiresInteraction] = useState(false);

//   useEffect(() => {
//     const playRingtone = async () => {
//       try {
//         if (audioRef.current) {
//           audioRef.current.volume = 0.8;
//           await audioRef.current.play();
//         }
//       } catch (err) {
//         console.warn("⚠️ Autoplay blocked by browser:", err);
//         setRequiresInteraction(true);
//       }
//     };

//     playRingtone();

//     return () => {
//       if (audioRef.current) {
//         audioRef.current.pause();
//         audioRef.current.currentTime = 0;
//       }
//     };
//   }, []);

//   const enableAudio = async () => {
//     try {
//       if (audioRef.current) {
//         await audioRef.current.play();
//         setRequiresInteraction(false);
//       }
//     } catch (err) {
//       console.error("Failed to enable ringtone:", err);
//     }
//   };

//   const handleAccept = () => {
//     if (audioRef.current) {
//       audioRef.current.pause();
//     }
//     navigate(`/dashboard/videocall/${call.token}`);
//     onClose?.();
//   };

//   const handleReject = () => {
//     if (audioRef.current) {
//       audioRef.current.pause();
//     }
//     onClose?.();
//   };

//   if (!call) return null;

//   return (
//     <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
//       <div className="bg-white rounded-xl shadow-lg p-6 w-96 text-center">
//         <h2 className="text-xl font-semibold mb-2">Incoming Call</h2>
//         <p className="text-gray-600 mb-4">Dr. {doctorId} is calling you</p>

//         <div className="flex justify-center gap-4">
//           <button
//             onClick={handleAccept}
//             className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600"
//           >
//             ✅ Accept
//           </button>
//           <button
//             onClick={handleReject}
//             className="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
//           >
//             ❌ Reject
//           </button>
//         </div>

//         {requiresInteraction && (
//           <div className="mt-4">
//             <button
//               onClick={enableAudio}
//               className="px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
//             >
//               🔊 Enable Ringtone
//             </button>
//             <p className="text-xs text-gray-500 mt-2">
//               Click to allow ringtone (browser blocked autoplay).
//             </p>
//           </div>
//         )}

//         {/* Hidden ringtone element */}
//         <audio ref={audioRef} src="/ringtone.mp3" loop preload="auto" />
//       </div>
//     </div>
//   );
// }

// import React, { useEffect, useRef, useState } from "react";

// export default function RingtonePopup({ call, doctorId, onClose }) {
//   const audioRef = useRef(null);
//   const [requiresInteraction, setRequiresInteraction] = useState(false);
//   const [isProcessing, setIsProcessing] = useState(false);
//   const [callDuration, setCallDuration] = useState(0);

//   // Mock socket for demo
//   const socket = {
//     emit: (event, data) => {
//       console.log(`Socket emit: ${event}`, data);
//       alert(`Socket event: ${event} - ${JSON.stringify(data)}`);
//     }
//   };

//   // Mock navigation
//   const navigate = (path) => {
//     console.log(`Navigate to: ${path}`);
//     alert(`Would navigate to: ${path}`);
//   };

//   useEffect(() => {
//     const playRingtone = async () => {
//       try {
//         if (audioRef.current) {
//           audioRef.current.volume = 0.8;
//           await audioRef.current.play();
//         }
//       } catch (err) {
//         console.warn("⚠️ Autoplay blocked by browser:", err);
//         setRequiresInteraction(true);
//       }
//     };

//     playRingtone();

//     // Start call duration timer
//     const timer = setInterval(() => {
//       setCallDuration(prev => prev + 1);
//     }, 1000);

//     // Auto-reject after 30 seconds
//     const autoRejectTimer = setTimeout(() => {
//       handleReject("timeout");
//     }, 30000);

//     return () => {
//       if (audioRef.current) {
//         audioRef.current.pause();
//         audioRef.current.currentTime = 0;
//       }
//       clearInterval(timer);
//       clearTimeout(autoRejectTimer);
//     };
//   }, []);

//   const enableAudio = async () => {
//     try {
//       if (audioRef.current) {
//         await audioRef.current.play();
//         setRequiresInteraction(false);
//       }
//     } catch (err) {
//       console.error("Failed to enable ringtone:", err);
//     }
//   };

//   const handleAccept = () => {
//     setIsProcessing(true);

//     if (audioRef.current) {
//       audioRef.current.pause();
//     }

//     // Notify patient that call is accepted
//     socket.emit("call-accepted", {
//       callId: call.id,
//       doctorId: doctorId,
//       patientId: call.patientId,
//       channel: call.channel,
//       requiresPayment: true, // Always require payment
//       message: "Doctor accepted your call. Please complete payment to proceed."
//     });

//     // Navigate to doctor waiting room
//     navigate(`/doctor-waiting-room/${call.channel}?callId=${call.id}&patientId=${call.patientId}`);
//       //  navigate(`/call-page/${call.channel}?uid=${doctorId}&role=host`);

//     onClose?.();
//   };

//   const handleReject = (reason = "rejected") => {
//     setIsProcessing(true);

//     if (audioRef.current) {
//       audioRef.current.pause();
//     }

//     // Notify patient that call is rejected
//     socket.emit("call-rejected", {
//       callId: call.id,
//       doctorId: doctorId,
//       patientId: call.patientId,
//       reason: reason,
//       message: reason === "timeout"
//         ? "Doctor did not respond within 30 seconds"
//         : "Doctor is currently unavailable"
//     });

//     onClose?.();
//   };

//   const formatTime = (seconds) => {
//     return `${Math.floor(seconds / 60)}:${(seconds % 60).toString().padStart(2, '0')}`;
//   };

//   if (!call) return null;

//   return (
//     <div className="fixed inset-0 bg-black/70 flex items-center justify-center z-50 backdrop-blur-sm">
//       <div className="bg-white rounded-2xl shadow-2xl p-8 w-96 text-center transform animate-pulse-ring">
//         {/* Call Animation */}
//         <div className="relative mb-6">
//           <div className="w-24 h-24 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
//             <svg className="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
//               <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
//             </svg>
//           </div>
//           <div className="absolute -inset-4 rounded-full border-4 border-blue-200 animate-ping opacity-30"></div>
//         </div>

//         <h2 className="text-2xl font-bold text-gray-800 mb-2">Incoming Video Call</h2>
//         <p className="text-gray-600 mb-2">Patient ID: {call.patientId}</p>
//         <p className="text-sm text-gray-500 mb-4">Call ID: {call.id}</p>

//         {/* Call Duration */}
//         <div className="bg-gray-100 rounded-lg p-2 mb-6">
//           <p className="text-sm text-gray-600">Ringing: {formatTime(callDuration)}</p>
//         </div>

//         {/* Patient Info Card */}
//         <div className="bg-blue-50 rounded-lg p-4 mb-6 border border-blue-200">
//           <h3 className="font-semibold text-blue-800 mb-2">Consultation Request</h3>
//           <div className="text-sm text-blue-700 space-y-1">
//             <p>• 30-minute video consultation</p>
//             <p>• Payment: ₹499 (after acceptance)</p>
//             <p>• Emergency veterinary care</p>
//           </div>
//         </div>

//         {/* Action Buttons */}
//         <div className="flex justify-center gap-6 mb-4">
//           <button
//             onClick={handleAccept}
//             disabled={isProcessing}
//             className="flex items-center px-6 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50"
//           >
//             {isProcessing ? (
//               <>
//                 <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
//                 Accepting...
//               </>
//             ) : (
//               <>
//                 <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
//                   <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd"/>
//                 </svg>
//                 Accept Call
//               </>
//             )}
//           </button>

//           <button
//             onClick={() => handleReject("rejected")}
//             disabled={isProcessing}
//             className="flex items-center px-6 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50"
//           >
//             <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
//               <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd"/>
//             </svg>
//             Decline
//           </button>
//         </div>

//         {/* Audio Enable Section */}
//         {requiresInteraction && (
//           <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
//             <button
//               onClick={enableAudio}
//               className="flex items-center justify-center w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
//             >
//               <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
//                 <path fillRule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.785L4.382 14H2a1 1 0 01-1-1V7a1 1 0 011-1h2.382l4.001-2.785zm5.834 1.92a1 1 0 011.414 0 11.952 11.952 0 010 16.908 1 1 0 01-1.414-1.414A9.952 9.952 0 0019.07 12a9.952 9.952 0 00-2.853-7.072 1 1 0 010-1.414zm-2.1 2.828a1 1 0 011.414 0 7.956 7.956 0 010 11.314 1 1 0 01-1.414-1.414 5.956 5.956 0 000-8.486 1 1 0 010-1.414z" clipRule="evenodd"/>
//               </svg>
//               Enable Ringtone
//             </button>
//             <p className="text-xs text-yellow-700 mt-2">
//               Browser blocked autoplay. Click to enable ringtone.
//             </p>
//           </div>
//         )}

//         {/* Auto-reject warning */}
//         <div className="text-xs text-gray-500">
//           Call will auto-decline in {30 - callDuration} seconds
//         </div>

//         {/* Hidden ringtone element */}
//         <audio
//           ref={audioRef}
//           loop
//           preload="auto"
//         >
//           <source src="/ringtone.mp3" type="audio/mpeg" />
//           <source src="/ringtone.ogg" type="audio/ogg" />
//           <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DwwG4gBTV2y/LQeisFJHfH8N2QQAoUXrTp66hVFAo=" />
//         </audio>

//         <style jsx>{`
//           @keyframes pulse-ring {
//             0% { transform: scale(0.9) }
//             50% { transform: scale(1.05) }
//             100% { transform: scale(0.9) }
//           }
//           .animate-pulse-ring {
//             animation: pulse-ring 2s ease-in-out infinite;
//           }
//         `}</style>
//       </div>
//     </div>
//   );
// }

import React, { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { socket } from "./socket";
import axios from "axios";

export default function RingtonePopup({
  call,
  doctorId,
  onClose,
  patientId,
}) {
  const audioRef = useRef(null);
  const [requiresInteraction, setRequiresInteraction] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [callDuration, setCallDuration] = useState(0);
  const [waitingStatus, setWaitingStatus] = useState("");
  const navigate = useNavigate();
  const [summary, setSummary] = useState(null);
  console.log(patientId, "paads");

  useEffect(() => {
    const fetchSummary = async () => {
      try {
        const response = await axios.post(
          "https://snoutiq.com/backend/api/summary",
          {
            user_id: patientId,
          }
        );
        console.log(response, "ankit");

        setSummary(response.data);
      } catch (error) {
        console.error("Error fetching summary:", error);
      }
    };

    fetchSummary();
  }, [patientId]);
  console.log(summary, "ankit2");

  useEffect(() => {
    const playRingtone = async () => {
      try {
        if (audioRef.current) {
          audioRef.current.volume = 0.8;
          await audioRef.current.play();
        }
      } catch (err) {
        console.warn("Autoplay blocked by browser:", err);
        setRequiresInteraction(true);
      }
    };

    playRingtone();

    // Start call duration timer
    const timer = setInterval(() => {
      setCallDuration((prev) => prev + 1);
    }, 1000);

    // Auto-reject after 30 seconds (only if not processing)
    const autoRejectTimer = setTimeout(() => {
      if (!isProcessing) {
        handleReject("timeout");
      }
    }, 5 * 60 * 1000); // ✅ 5 minutes (300 seconds)

    return () => {
      if (audioRef.current) {
        audioRef.current.pause();
        audioRef.current.currentTime = 0;
      }
      clearInterval(timer);
      clearTimeout(autoRejectTimer);
    };
  }, [isProcessing]);

  // Cleanup socket listeners on unmount
  useEffect(() => {
    return () => {
      socket.off("patient-paid");
      socket.off("payment-cancelled");
      socket.off("payment-verified");
    };
  }, []);

  const enableAudio = async () => {
    try {
      if (audioRef.current) {
        await audioRef.current.play();
        setRequiresInteraction(false);
      }
    } catch (err) {
      console.error("Failed to enable ringtone:", err);
    }
  };

  const handleAccept = () => {
    setIsProcessing(true);
    setWaitingStatus("Accepting call...");

    if (audioRef.current) {
      audioRef.current.pause();
    }

    console.log("Doctor accepting call:", call.id);

    // Emit call-accepted event with correct data structure
    socket.emit("call-accepted", {
      callId: call.id,
      doctorId: doctorId,
      patientId: call.patientId,
      channel: call.channel,
      requiresPayment: true,
      message: "Doctor accepted your call. Please complete payment to proceed.",
    });

    setWaitingStatus("Waiting for patient payment...");

    // Listen for patient payment confirmation
    const handlePatientPaid = (data) => {
      if (data.callId === call.id) {
        console.log("Patient payment confirmed, navigating to video call");
        setWaitingStatus("Payment confirmed! Joining video call...");

        // Small delay to show confirmation message
        // setTimeout(() => {
        //   navigate(
        //     `/call-page/${data.channel}?uid=${doctorId}&role=host&callId=${data.callId}`
        //   );
        setTimeout(() => {
  navigate(
    `/call-page/${data.channel}?uid=${doctorId}&role=host&callId=${data.callId}&doctorId=${doctorId}&patientId=${data.patientId}`,
    {
      state: {
        doctorId,
        patientId: data.patientId,
        channel: data.channel,
        callId: data.callId
      }
    }
  );

  // Cleanup listeners
  socket.off("patient-paid", handlePatientPaid);
  socket.off("payment-cancelled", handlePaymentCancelled);
  onClose?.();
}, 1500);

        //   socket.off("patient-paid", handlePatientPaid);
        //   socket.off("payment-cancelled", handlePaymentCancelled);
        //   onClose?.();
        // }, 1500);
      }
    };

    const handlePaymentCancelled = (data) => {
      if (data.callId === call.id) {
        console.log("Patient cancelled payment:", data.reason);

        const message =
          data.reason === "timeout"
            ? "Payment timed out. Call ended."
            : "Patient cancelled payment. Call ended.";

        setWaitingStatus(message);

        // Close after showing message
        setTimeout(() => {
          socket.off("payment-cancelled", handlePaymentCancelled);
          socket.off("patient-paid", handlePatientPaid);
          onClose?.();
        }, 3000);
      }
    };

    // Set up listeners for payment events
    socket.on("patient-paid", handlePatientPaid);
    socket.on("payment-cancelled", handlePaymentCancelled);
  };

  const handleReject = (reason = "rejected") => {
    setIsProcessing(true);

    if (audioRef.current) {
      audioRef.current.pause();
    }

    console.log("Doctor rejecting call:", call.id, "reason:", reason);

    // Notify patient that call is rejected
    socket.emit("call-rejected", {
      callId: call.id,
      doctorId: doctorId,
      patientId: call.patientId,
      reason: reason,
      message:
        reason === "timeout"
          ? "Doctor did not respond within 30 seconds"
          : "Doctor is currently unavailable",
    });

    // Close popup immediately
    onClose?.();
  };

  const formatTime = (seconds) => {
    return `${Math.floor(seconds / 60)}:${(seconds % 60)
      .toString()
      .padStart(2, "0")}`;
  };

  if (!call) return null;

  return (
    <div className="fixed inset-0 bg-black/70 flex items-center justify-center z-50 backdrop-blur-sm">
      <div className="bg-white rounded-2xl shadow-2xl p-8 w-96 text-center transform animate-pulse-ring">
        {/* Call Animation */}
        <div className="relative mb-6">
          <div
            className={`w-24 h-24 bg-gradient-to-r ${
              isProcessing
                ? "from-blue-500 to-green-500"
                : "from-blue-500 to-purple-500"
            } rounded-full flex items-center justify-center mx-auto mb-4 ${
              isProcessing ? "animate-pulse" : "animate-bounce"
            }`}
          >
            <svg
              className="w-12 h-12 text-white"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
            </svg>
          </div>
          <div className="absolute -inset-4 rounded-full border-4 border-blue-200 animate-ping opacity-30"></div>
        </div>

        <h2 className="text-2xl font-bold text-gray-800 mb-2">
          {isProcessing ? "Call Accepted" : "Incoming Video Call"}
        </h2>
        <p className="text-gray-600 mb-2">Patient ID: {call.patientId}</p>
        <p className="text-sm text-gray-500 mb-4">Call ID: {call.id}</p>

        {/* Call Duration or Status */}
        <div className="bg-gray-100 rounded-lg p-2 mb-6">
          {isProcessing ? (
            <p className="text-sm text-blue-600 font-medium">{waitingStatus}</p>
          ) : (
            <p className="text-sm text-gray-600">
              Ringing: {formatTime(callDuration)}
            </p>
          )}
        </div>

        {/* Patient Info Card */}
        <div className="bg-blue-50 rounded-lg p-4 mb-6 border border-blue-200">
          <h3 className="font-semibold text-blue-800 mb-2">
            Consultation Request
          </h3>
          <div className="text-sm text-blue-700 space-y-1">
            {/* <p>• 30-minute video consultation</p>
            <p>• Payment: ₹499 (after acceptance)</p>
            <p>• Emergency veterinary care</p> */}
            <p className="text-sm text-blue-700 whitespace-pre-line">
              {/* {summary ? summary.summary : "Loading summary..."} */}
            </p>
          </div>
        </div>

        {/* Action Buttons or Waiting State */}
        {isProcessing ? (
          <div className="mb-4">
            <div className="bg-green-50 border border-green-200 rounded-xl p-6 mb-4">
              <div className="flex items-center justify-center mb-3">
                <div className="w-8 h-8 border-4 border-green-500 border-t-transparent rounded-full animate-spin mr-3"></div>
                <h3 className="text-lg font-semibold text-green-800">
                  Processing...
                </h3>
              </div>
              <p className="text-green-700 text-center text-sm">
                {waitingStatus}
              </p>
              {waitingStatus.includes("Waiting for payment") && (
                <div className="mt-3 text-xs text-green-600 text-center">
                  You will be automatically redirected to the video call once
                  payment is confirmed.
                </div>
              )}
            </div>

            {waitingStatus.includes("Waiting for payment") && (
              <button
                onClick={() => handleReject("doctor-cancelled")}
                className="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm"
              >
                Cancel Call
              </button>
            )}
          </div>
        ) : (
          <div className="flex justify-center gap-6 mb-4">
            <button
              onClick={handleAccept}
              className="flex items-center px-6 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition-all duration-200 shadow-lg hover:shadow-xl"
            >
              <svg
                className="w-5 h-5 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clipRule="evenodd"
                />
              </svg>
              Accept Call
            </button>

            <button
              onClick={() => handleReject("rejected")}
              className="flex items-center px-6 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all duration-200 shadow-lg hover:shadow-xl"
            >
              <svg
                className="w-5 h-5 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clipRule="evenodd"
                />
              </svg>
              Decline
            </button>
          </div>
        )}

        {/* Audio Enable Section */}
        {requiresInteraction && !isProcessing && (
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <button
              onClick={enableAudio}
              className="flex items-center justify-center w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
            >
              <svg
                className="w-4 h-4 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.785L4.382 14H2a1 1 0 01-1-1V7a1 1 0 011-1h2.382l4.001-2.785zm5.834 1.92a1 1 0 011.414 0 11.952 11.952 0 010 16.908 1 1 0 01-1.414-1.414A9.952 9.952 0 0019.07 12a9.952 9.952 0 00-2.853-7.072 1 1 0 010-1.414zm-2.1 2.828a1 1 0 011.414 0 7.956 7.956 0 010 11.314 1 1 0 01-1.414-1.414 5.956 5.956 0 000-8.486 1 1 0 010-1.414z"
                  clipRule="evenodd"
                />
              </svg>
              Enable Ringtone
            </button>
            <p className="text-xs text-yellow-700 mt-2">
              Browser blocked autoplay. Click to enable ringtone.
            </p>
          </div>
        )}

        {/* Auto-reject warning */}
        {!isProcessing && (
          <div className="text-xs text-gray-500">
            Call will auto-decline in {30 - callDuration} seconds
          </div>
        )}

        {/* Hidden ringtone element */}
        <audio ref={audioRef} loop preload="auto">
          <source src="/ringtone.mp3" type="audio/mpeg" />
          <source src="/ringtone.ogg" type="audio/ogg" />
          <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DwwG4gBTV2y/LQeisFJHfH8N2QQAoUXrTp66hVFAo=" />
        </audio>

        <style jsx>{`
          @keyframes pulse-ring {
            0% {
              transform: scale(0.9);
            }
            50% {
              transform: scale(1.05);
            }
            100% {
              transform: scale(0.9);
            }
          }
          .animate-pulse-ring {
            animation: pulse-ring 2s ease-in-out infinite;
          }
        `}</style>
      </div>
    </div>
  );
}
