// import React, { useState, useEffect,memo } from "react";
// import { useNavigate } from "react-router-dom";
// import { socket } from "./socket";

// const onStart=()=>{
//    const [loading, setLoading] = useState(false);
//     const [callStatus, setCallStatus] = useState(null);
//     const [activeDoctors, setActiveDoctors] = useState([]);
//     const [selectedDoctor, setSelectedDoctor] = useState(501);
//     const navigate = useNavigate();

//     // Patient ID - in real app, this would come from auth
//     const patientId = 101;

//     useEffect(() => {
//       // Get list of active doctors
//       socket.emit("get-active-doctors");

//       // Listen for call responses
//       socket.on("call-sent", (data) => {
//         setCallStatus({ type: "sent", ...data });
//         setLoading(false);
//       });

//       socket.on("call-accepted", (data) => {
//         setCallStatus({ type: "accepted", ...data });

//         // Check if payment is required
//         if (data.requiresPayment) {
//           // Redirect to payment page with call details
//           setTimeout(() => {
//             navigate(`/payment/${data.callId}?doctorId=${data.doctorId}&channel=${data.channel}&patientId=${patientId}`);
//           }, 2000);
//         } else {
//           // Direct video call (fallback)
//           setTimeout(() => {
//             navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience`);
//           }, 2000);
//         }
//       });

//       socket.on("call-rejected", (data) => {
//         setCallStatus({ type: "rejected", ...data });
//         setLoading(false);
//       });

//       socket.on("active-doctors", (doctors) => {
//         setActiveDoctors(doctors);
//       });

//       // Listen for payment completion
//       socket.on("payment-completed", (data) => {
//         if (data.patientId === patientId) {
//           setCallStatus({ type: "payment-completed", ...data });
//           // Navigate to video call after payment
//           setTimeout(() => {
//             navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience&callId=${data.callId}`);
//           }, 1000);
//         }
//       });

//       return () => {
//         socket.off("call-sent");
//         socket.off("call-accepted");
//         socket.off("call-rejected");
//         socket.off("active-doctors");
//         socket.off("payment-completed");
//       };
//     }, [navigate, patientId]);

//     const startCall = () => {
//       if (!selectedDoctor) {
//         alert("Please select a doctor");
//         return;
//       }

//       setLoading(true);
//       setCallStatus(null);

//       const callData = {
//         doctorId: selectedDoctor,
//         patientId: patientId,
//         channel: `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`,
//       };

//       socket.emit("call-requested", callData);
//     };

//     const getStatusMessage = () => {
//       if (!callStatus) return null;

//       switch (callStatus.type) {
//         case "sent":
//           return "📤 Call request sent to doctor. Waiting for response...";
//         case "accepted":
//           return callStatus.requiresPayment
//             ? "✅ Doctor accepted your call! Redirecting to payment..."
//             : "✅ Doctor accepted your call! Connecting...";
//         case "rejected":
//           return "❌ Doctor is currently unavailable. Please try again later.";
//         case "payment-completed":
//           return "💳 Payment successful! Connecting to video call...";
//         default:
//           return null;
//       }
//     };
//     return(
//        <button
//         onClick={startCall}
//         disabled={loading || !selectedDoctor}
//         style={{
//           padding: "12px 24px",
//           borderRadius: 8,
//           background: loading ? "#ccc" : "#2563eb",
//           color: "#fff",
//           border: "none",
//           cursor: loading ? "not-allowed" : "pointer",
//           fontSize: 16,
//           fontWeight: "bold"
//         }}
//       >
//         {loading ? "📞 Requesting Call..." : "📞 Request Video Call"}
//       </button>
//     )

// }

// const EmergencyStatusBox = ({ emergencyStatus }) => {
//   if (!emergencyStatus) return null;

//   if (emergencyStatus.includes("EMERGENCY")) {
//     return (
//       <div className="mt-4 p-4 bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 rounded-r-xl shadow-sm">
//         <div className="flex items-center mb-3">
//           <div className="flex-shrink-0 w-6 h-6 bg-red-100 rounded-full flex items-center justify-center mr-3">
//             <svg className="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//               <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
//             </svg>
//           </div>
//           <span className="text-sm font-bold text-red-800 uppercase tracking-wide">
//             🚨 Emergency Care Required
//           </span>
//         </div>
//         <p className="text-red-700 text-sm mb-4 font-medium">
//           Your pet needs immediate veterinary attention. Please seek emergency care right away.
//         </p>
//         <button
//           onClick={() => (window.location.href = "/book-clinic-visit")}
//           className="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
//         >
//           <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//             <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
//           </svg>
//           <span>Book Emergency Visit Now</span>
//         </button>
//       </div>
//     );
//   } else if (emergencyStatus.includes("ROUTINE")) {
//     return (
//       <div className="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 rounded-r-xl shadow-sm">
//         <div className="flex items-center mb-3">
//           <div className="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3">
//             <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//               <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
//             </svg>
//           </div>
//           <span className="text-sm font-bold text-blue-800 uppercase tracking-wide">
//             📅 Routine Consultation
//           </span>
//         </div>
//         <p className="text-blue-700 text-sm mb-4 font-medium">
//           Schedule a convenient video consultation with a veterinary professional.
//         </p>
//         <button
//           // onClick={() => (window.location.href = "/waiting-for-doctor")}
//           onClick={()=>onStart()}
//           className="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
//         >
//           <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//             <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
//           </svg>
//           <span>Start Video Consultation</span>
//         </button>
//       </div>
//     );
//   } else {
//     return (
//       <div className="mt-4 p-4 bg-gradient-to-r from-gray-50 to-slate-50 border-l-4 border-gray-400 rounded-r-xl shadow-sm">
//         <div className="flex items-center mb-3">
//           <div className="flex-shrink-0 w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center mr-3">
//             <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//               <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
//             </svg>
//           </div>
//           <span className="text-sm font-bold text-gray-800 uppercase tracking-wide">
//             💬 Consultation Options
//           </span>
//         </div>
//         <p className="text-gray-700 text-sm mb-4 font-medium">
//           Choose your preferred consultation method based on your pet's needs.
//         </p>
//         <div className="space-y-3">
//           <button
//             // onClick={() => (window.location.href = "/waiting-for-doctor")}
//                     onClick={()=>onStart()}
//             className="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
//           >
//             <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//               <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
//             </svg>
//             <span>Video Consultation</span>
//           </button>
//           <button
//             onClick={() => (window.location.href = "/book-clinic-visit")}
//             className="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
//           >
//             <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//               <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
//             </svg>
//             <span>In-Person Clinic Visit</span>
//           </button>
//         </div>
//       </div>
//     );
//   }
// };

// const MessageBubble = memo(
//   ({ msg, index, onFeedback }) => {
//     if (msg.type === "loading") {
//       return (
//         <div key={`loader-${index}`} className="flex justify-start mb-2">
//           <div className="bg-white/90 backdrop-blur-sm border border-gray-200 rounded-2xl px-6 py-4 shadow-lg flex items-center space-x-3">
//             <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
//               <svg className="w-5 h-5 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                 <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
//               </svg>
//             </div>
//             <div className="flex flex-col">
//               <span className="text-sm font-medium text-gray-700">AI is thinking...</span>
//               <div className="flex space-x-1 mt-1">
//                 <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></span>
//                 <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce delay-150"></span>
//                 <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce delay-300"></span>
//               </div>
//             </div>
//           </div>
//         </div>
//       );
//     }

//     return (
//       <div
//         key={msg.id || `msg-${index}`}
//         className={`flex ${
//           msg.sender === "user" ? "justify-end" : "justify-start"
//         } mb-6`}
//       >
//         <div className="flex max-w-[85%] lg:max-w-[75%]">
//           {/* AI Avatar */}
//           {msg.sender === "ai" && (
//             <div className="flex-shrink-0 mr-3">
//               <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
//                 <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                   <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
//                 </svg>
//               </div>
//             </div>
//           )}

//           {/* Message Content */}
//           <div
//             className={`rounded-2xl px-4 py-3 shadow-lg relative ${
//               msg.sender === "user"
//                 ? "bg-gradient-to-r from-blue-600 to-indigo-600 text-white ml-auto"
//                 : msg.isError
//                 ? "bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 text-red-800"
//                 : "bg-white/90 backdrop-blur-sm border border-gray-200 text-gray-800"
//             }`}
//           >
//             {/* Message Header for AI */}
//             {msg.sender === "ai" && !msg.isError && (
//               <div className="flex items-center mb-2 pb-2 border-b border-gray-100">
//                 <svg className="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                   <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
//                 </svg>
//                 <span className="text-sm font-semibold text-gray-700">Snoutiq AI Veterinary Assistant</span>
//                 <div className="ml-auto">
//                   <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
//                 </div>
//               </div>
//             )}

//             {/* Error Header */}
//             {msg.isError && (
//               <div className="flex items-center mb-2 pb-2 border-b border-red-200">
//                 <svg className="w-4 h-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                   <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
//                 </svg>
//                 <span className="text-sm font-semibold text-red-700">Connection Error</span>
//               </div>
//             )}

//             {/* Message Text */}
//             <div className="whitespace-pre-line leading-relaxed text-sm lg:text-base break-words">
//               <div
//                 className="prose prose-sm max-w-full"
//                 dangerouslySetInnerHTML={{
//                   __html:
//                     msg.displayedText !== undefined
//                       ? msg.displayedText
//                       : msg.text,
//                 }}
//               />
//             </div>

//             {/* Emergency Status Box */}
//             {msg.sender === "ai" && msg.emergency_status && (
//               <EmergencyStatusBox emergencyStatus={msg.emergency_status.trim()} />
//             )}

//             {/* Message Footer */}
//             <div className="flex items-center justify-between mt-3 pt-2">
//               <div
//                 className={`text-xs ${
//                   msg.sender === "user"
//                     ? "text-blue-200"
//                     : msg.isError
//                     ? "text-red-500"
//                     : "text-gray-500"
//                 }`}
//               >
//                 {msg.timestamp
//                   ? new Date(msg.timestamp).toLocaleTimeString([], {
//                       hour: "2-digit",
//                       minute: "2-digit",
//                     })
//                   : ""}
//               </div>

//               {/* Feedback Buttons for AI messages */}
//               {msg.sender === "ai" && !msg.isError && (
//                 <div className="flex items-center gap-2">
//                   <button
//                     className="flex items-center justify-center w-8 h-8 rounded-full bg-green-100 hover:bg-green-200 text-green-600 hover:text-green-800 transition-all duration-200 transform hover:scale-110"
//                     onClick={() => onFeedback(1, msg.timestamp)}
//                     aria-label="Helpful response"
//                   >
//                     <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                       <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
//                     </svg>
//                   </button>
//                   <button
//                     className="flex items-center justify-center w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 text-red-600 hover:text-red-800 transition-all duration-200 transform hover:scale-110"
//                     onClick={() => onFeedback(-1, msg.timestamp)}
//                     aria-label="Not helpful response"
//                   >
//                     <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                       <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2M17 4h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
//                     </svg>
//                   </button>
//                 </div>
//               )}
//             </div>

//             {/* Message Tail */}
//             <div
//               className={`absolute ${
//                 msg.sender === "user"
//                   ? "right-0 top-4 w-0 h-0 border-l-8 border-l-blue-600 border-t-8 border-t-transparent border-b-8 border-b-transparent transform translate-x-2"
//                   : "left-0 top-4 w-0 h-0 border-r-8 border-r-white border-t-8 border-t-transparent border-b-8 border-b-transparent transform -translate-x-2"
//               }`}
//             ></div>
//           </div>

//           {/* User Avatar */}
//           {msg.sender === "user" && (
//             <div className="flex-shrink-0 ml-3">
//               <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
//                 <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                   <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
//                 </svg>
//               </div>
//             </div>
//           )}
//         </div>
//       </div>
//     );
//   },
//   (prevProps, nextProps) => {
//     const prevMsg = prevProps.msg;
//     const nextMsg = nextProps.msg;

//     return (
//       prevMsg.id === nextMsg.id &&
//       prevMsg.displayedText === nextMsg.displayedText &&
//       prevMsg.text === nextMsg.text &&
//       prevMsg.type === nextMsg.type
//     );
//   }
// );

// export default MessageBubble;

import React, { useState, useEffect, memo } from "react";
import { useNavigate } from "react-router-dom";
import { socket } from "./socket";
import LoadingModal from "./LoadingModal";

const StartCallButton = ({ nearbyDoctors }) => {
  const [loading, setLoading] = useState(false);
  const [callStatus, setCallStatus] = useState(null);
  const [activeDoctors, setActiveDoctors] = useState([]);
  // const [selectedDoctor, setSelectedDoctor] = useState(501);
  const [selectedDoctors, setSelectedDoctors] = useState(
    nearbyDoctors ? nearbyDoctors.map((doc) => doc.id) : []
  );
  const [callDataMap, setCallDataMap] = useState({}); 
  const navigate = useNavigate();
  const [showModal, setShowModal] = useState(false);
  console.log(nearbyDoctors, "cdfgdfgs");

  const patientId = 101;

  useEffect(() => {
    // Get list of active doctors
    socket.emit("get-active-doctors");

    // Listen for call responses
    socket.on("call-sent", (data) => {
      setCallStatus({ type: "sent", ...data });
      setLoading(false);
    });

    socket.on("call-accepted", (data) => {
      setCallStatus({ type: "accepted", ...data });
      setShowModal(false);

      const doctor = nearbyDoctors.find((d) => d.id === data.doctorId);

      // Check if payment is required
      if (data.requiresPayment) {
           const callData = callDataMap[data.doctorId];
        // Redirect to payment page with call details
        setTimeout(() => {
          navigate(`/payment/${data.callId}`, {
            state: {
              doctor, // full doctor object
              channel: data.channel,
              patientId,
               callId: data.callId, 
            },
          });
          // navigate(`/payment/${data.callId}?doctorId=${data.doctorId}&channel=${data.channel}&patientId=${patientId}`);
        }, 2000);
      } else {
        // Direct video call (fallback)
        setTimeout(() => {
          // navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience`);
            navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience&callId=${data.callId}`);
        }, 2000);
      }
    });

    socket.on("call-rejected", (data) => {
      setCallStatus({ type: "rejected", ...data });
      setLoading(false);
      setShowModal(false);
    });

    socket.on("active-doctors", (doctors) => {
      setActiveDoctors(doctors);
      setShowModal(false);
    });

    // Listen for payment completion
    socket.on("payment-completed", (data) => {
      if (data.patientId === patientId) {
        setCallStatus({ type: "payment-completed", ...data });
        setTimeout(() => {
          navigate(
            `/call-page/${data.channel}?uid=${patientId}&role=audience&callId=${data.callId}`
          );
        }, 1000);
      }
    });

    return () => {
      socket.off("call-sent");
      socket.off("call-accepted");
      socket.off("call-rejected");
      socket.off("active-doctors");
      socket.off("payment-completed");
    };
  }, [navigate, patientId]);

  // const startCall = () => {
  //   console.log(selectedDoctor,"anselectedDoctor");

  //   if (!selectedDoctor) {
  //     alert("Please select a doctor");
  //     return;
  //   }

  //   setLoading(true);
  //     setShowModal(true);
  //   setCallStatus(null);

  //   const callData = {
  //     doctorId: selectedDoctor,
  //     patientId: patientId,
  //     channel: `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`,
  //   };

  //   socket.emit("call-requested", callData);
  // };
  // const startCall = () => {
  //   if (!selectedDoctors.length) {
  //     alert("Please select at least one doctor");
  //     return;
  //   }

  //   setLoading(true);
  //   setShowModal(true);
  //   setCallStatus(null);

  //   selectedDoctors.forEach((doctorId) => {
  //     const callData = {
  //       doctorId,
  //       patientId,
  //       channel: `call_${Date.now()}_${Math.random()
  //         .toString(36)
  //         .substring(2, 8)}`,
  //     };

  //     socket.emit("call-requested", callData);
  //   });
  // };

  
  const startCall = () => {
    if (!selectedDoctors.length) {
      alert("Please select at least one doctor");
      return;
    }

    setLoading(true);
    setShowModal(true);
    setCallStatus(null);

    const newCallDataMap = {};

    selectedDoctors.forEach((doctorId) => {
      const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
      const channel = `channel_${callId}`;
      
      const callData = {
        doctorId,
        patientId,
        channel,
        callId, // Include callId in the request
      };

      newCallDataMap[doctorId] = callData;
      
      socket.emit("call-requested", callData);
    });

    setCallDataMap(newCallDataMap);
  };

  const getStatusMessage = () => {
    if (!callStatus) return null;

    switch (callStatus.type) {
      case "sent":
        return "📤 Call request sent to doctor. Waiting for response...";
      case "accepted":
        return callStatus.requiresPayment
          ? "✅ Doctor accepted your call! Redirecting to payment..."
          : "✅ Doctor accepted your call! Connecting...";
      case "rejected":
        return "❌ Doctor is currently unavailable. Please try again later.";
      case "payment-completed":
        return "💳 Payment successful! Connecting to video call...";
      default:
        return null;
    }
  };

  return (
    <>
      {/* <button
        onClick={startCall}
        disabled={loading || !selectedDoctor}
        style={{
          padding: "12px 24px",
          borderRadius: 8,
          background: loading ? "#ccc" : "#2563eb",
          color: "#fff",
          border: "none",
          cursor: loading ? "not-allowed" : "pointer",
          fontSize: 16,
          fontWeight: "bold"
        }}
      >
        {loading ? "📞 Requesting Call..." : "📞 Request Video Call"}
      </button> */}
      <p className="text-blue-700 text-sm mb-4 font-medium">
        Schedule a convenient video consultation with a veterinary professional.
      </p>
      <button
        //  style={{ cursor: loading ? "not-allowed" : "pointer",}}
        style={{
          cursor:
            loading || selectedDoctors.length === 0 ? "not-allowed" : "pointer",
        }}
        onClick={startCall}
        // disabled={loading || !selectedDoctor}
        disabled={loading || selectedDoctors.length === 0}
        className="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
      >
        <svg
          className="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
          />
        </svg>
        {loading ? "📞 Requesting Call..." : "📞 Start Video Consultation"}
      </button>
      <LoadingModal open={showModal} onClose={() => setShowModal(false)} />
      {callStatus && (
        <div style={{ marginTop: "10px" }}>{getStatusMessage()}</div>
      )}
    </>
  );
};

const EmergencyStatusBox = ({ decision, nearbyDoctors }) => {
  const navigate = useNavigate();

  if (!decision) return null;

  if (decision.includes("EMERGENCY")) {
    return (
      <div className="mt-4 p-4 bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 rounded-r-xl shadow-sm">
        <div className="flex items-center mb-3">
          <div className="flex-shrink-0 w-6 h-6 bg-red-100 rounded-full flex items-center justify-center mr-3">
            <svg
              className="w-4 h-4 text-red-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>
          <span className="text-sm font-bold text-red-800 uppercase tracking-wide">
            🚨 Emergency Care Required
          </span>
        </div>
        <p className="text-red-700 text-sm mb-4 font-medium">
          Your pet needs immediate veterinary attention. Please seek emergency
          care right away.
        </p>
        <button
          onClick={() => navigate("/book-clinic-visit")}
          className="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
        >
          <svg
            className="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
            />
          </svg>
          <span>Book Emergency Visit Now</span>
        </button>
      </div>
    );
  } else if (decision.includes("VIDEO_CONSULT")) {
    return (
      <div className="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 rounded-r-xl shadow-sm">
        <div className="flex items-center mb-3">
          <div className="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3">
            <svg
              className="w-4 h-4 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
          <span className="text-sm font-bold text-blue-800 uppercase tracking-wide">
            📅 Routine Consultation
          </span>
        </div>
        <StartCallButton nearbyDoctors={nearbyDoctors} />
      </div>
    );
  } else {
    return (
      <div className="mt-4 p-4 bg-gradient-to-r from-gray-50 to-slate-50 border-l-4 border-gray-400 rounded-r-xl shadow-sm">
        <div className="flex items-center mb-3">
          <div className="flex-shrink-0 w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center mr-3">
            <svg
              className="w-4 h-4 text-gray-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
              />
            </svg>
          </div>
          <span className="text-sm font-bold text-gray-800 uppercase tracking-wide">
            💬 Consultation Options
          </span>
        </div>
        <p className="text-gray-700 text-sm mb-4 font-medium">
          Choose your preferred consultation method based on your pet's needs.
        </p>
        <div className="space-y-3">
          <StartCallButton nearbyDoctors={nearbyDoctors} />

          <button
            onClick={() => navigate("/book-clinic-visit")}
            className="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
          >
            <svg
              className="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
              />
            </svg>
            <span>In-Person Clinic Visit</span>
          </button>
        </div>
      </div>
    );
  }
};

const MessageBubble = memo(
  ({ msg, index, onFeedback, nearbyDoctors }) => {
    if (msg.type === "loading") {
      return (
        <div key={`loader-${index}`} className="flex justify-start mb-2">
          <div className="bg-white/90 backdrop-blur-sm border border-gray-200 rounded-2xl px-6 py-4 shadow-lg flex items-center space-x-3">
            <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
              <svg
                className="w-5 h-5 text-white animate-pulse"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                />
              </svg>
            </div>
            <div className="flex flex-col">
              <span className="text-sm font-medium text-gray-700">
                AI is thinking...
              </span>
              <div className="flex space-x-1 mt-1">
                <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></span>
                <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce delay-150"></span>
                <span className="w-2 h-2 bg-blue-400 rounded-full animate-bounce delay-300"></span>
              </div>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div
        key={msg.id || `msg-${index}`}
        className={`flex ${
          msg.sender === "user" ? "justify-end" : "justify-start"
        } mb-6`}
      >
        <div className="flex max-w-[85%] lg:max-w-[75%]">
          {/* AI Avatar */}
          {msg.sender === "ai" && (
            <div className="flex-shrink-0 mr-3">
              <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
                <svg
                  className="w-6 h-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                  />
                </svg>
              </div>
            </div>
          )}

          {/* Message Content */}
          <div
            className={`rounded-2xl px-4 py-3 shadow-lg relative ${
              msg.sender === "user"
                ? "bg-gradient-to-r from-blue-600 to-indigo-600 text-white ml-auto"
                : msg.isError
                ? "bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 text-red-800"
                : "bg-white/90 backdrop-blur-sm border border-gray-200 text-gray-800"
            }`}
          >
            {/* Message Header for AI */}
            {msg.sender === "ai" && !msg.isError && (
              <div className="flex items-center mb-2 pb-2 border-b border-gray-100">
                <svg
                  className="w-4 h-4 text-green-600 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                  />
                </svg>
                <span className="text-sm font-semibold text-gray-700">
                  Snoutiq AI Veterinary Assistant
                </span>
                <div className="ml-auto">
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
              </div>
            )}

            {/* Error Header */}
            {msg.isError && (
              <div className="flex items-center mb-2 pb-2 border-b border-red-200">
                <svg
                  className="w-4 h-4 text-red-600 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <span className="text-sm font-semibold text-red-700">
                  Connection Error
                </span>
              </div>
            )}

            {/* Message Text */}
            <div className="whitespace-pre-line leading-relaxed text-sm lg:text-base break-words">
              <div
                className="prose prose-sm max-w-full"
                dangerouslySetInnerHTML={{
                  __html:
                    msg.displayedText !== undefined
                      ? msg.displayedText
                      : msg.text,
                }}
              />
            </div>

            {/* Emergency Status Box */}
            {msg.sender === "ai" && msg.decision && (
              <EmergencyStatusBox
                decision={msg.decision.trim()}
                nearbyDoctors={nearbyDoctors}
              />
            )}

            {/* Message Footer */}
            <div className="flex items-center justify-between mt-3 pt-2">
              <div
                className={`text-xs ${
                  msg.sender === "user"
                    ? "text-blue-200"
                    : msg.isError
                    ? "text-red-500"
                    : "text-gray-500"
                }`}
              >
                {msg.timestamp
                  ? new Date(msg.timestamp).toLocaleTimeString([], {
                      hour: "2-digit",
                      minute: "2-digit",
                    })
                  : ""}
              </div>

              {/* Feedback Buttons for AI messages */}
              {msg.sender === "ai" && !msg.isError && (
                <div className="flex items-center gap-2">
                  <button
                    className="flex items-center justify-center w-8 h-8 rounded-full bg-green-100 hover:bg-green-200 text-green-600 hover:text-green-800 transition-all duration-200 transform hover:scale-110"
                    onClick={() => onFeedback(1, msg.timestamp)}
                    aria-label="Helpful response"
                  >
                    <svg
                      className="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"
                      />
                    </svg>
                  </button>
                  <button
                    className="flex items-center justify-center w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 text-red-600 hover:text-red-800 transition-all duration-200 transform hover:scale-110"
                    onClick={() => onFeedback(-1, msg.timestamp)}
                    aria-label="Not helpful response"
                  >
                    <svg
                      className="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2M17 4h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"
                      />
                    </svg>
                  </button>
                </div>
              )}
            </div>

            {/* Message Tail */}
            <div
              className={`absolute ${
                msg.sender === "user"
                  ? "right-0 top-4 w-0 h-0 border-l-8 border-l-blue-600 border-t-8 border-t-transparent border-b-8 border-b-transparent transform translate-x-2"
                  : "left-0 top-4 w-0 h-0 border-r-8 border-r-white border-t-8 border-t-transparent border-b-8 border-b-transparent transform -translate-x-2"
              }`}
            ></div>
          </div>

          {/* User Avatar */}
          {msg.sender === "user" && (
            <div className="flex-shrink-0 ml-3">
              <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                <svg
                  className="w-6 h-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
              </div>
            </div>
          )}
        </div>
      </div>
    );
  },
  (prevProps, nextProps) => {
    const prevMsg = prevProps.msg;
    const nextMsg = nextProps.msg;

    return (
      prevMsg.id === nextMsg.id &&
      prevMsg.displayedText === nextMsg.displayedText &&
      prevMsg.text === nextMsg.text &&
      prevMsg.type === nextMsg.type
    );
  }
);

export default MessageBubble;
