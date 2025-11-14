// import React, { useEffect, useState } from "react";
// import { useNavigate } from "react-router-dom";
// import { io } from "socket.io-client";

// // ✅ Auto detect env
// const isLocal =
//   window.location.hostname === "localhost" ||
//   window.location.hostname === "127.0.0.1";

// const socket = io(isLocal ? "http://localhost:4000" : "https://snoutiq.com", {
//   path: "/socket.io",
//    transports: ["polling", "websocket"],  
// });

// export default function DoctorDashboard({ doctorId = 501 }) {
//   const [incomingCall, setIncomingCall] = useState(null);
//   const navigate = useNavigate();

//   useEffect(() => {
//     socket.emit("join-doctor", doctorId);

//     socket.on("call-requested", (e) => {
//       console.log("📞 Incoming call:", e);
//       setIncomingCall(e);
//     });

//     return () => {
//       socket.off("call-requested");
//     };
//   }, [doctorId]);

//   const handleAccept = () => {
//     if (incomingCall) {
//       navigate(`/call-page/${incomingCall.channel}?uid=${doctorId}&role=host`);
//     }
//   };

//   return (
//     <div style={{ padding: 20 }}>
//       <h2>Doctor Dashboard</h2>
//       {incomingCall ? (
//         <div style={{ background: "#fef3c7", padding: 16, borderRadius: 8 }}>
//           <h3>📞 Incoming Call</h3>
//           <p>
//             Patient <b>{incomingCall.patientId}</b> is calling on{" "}
//             <b>{incomingCall.channel}</b>
//           </p>
//           <button onClick={handleAccept}>✅ Accept</button>
//         </div>
//       ) : (
//         <p>No active calls.</p>
//       )}
//     </div>
//   );
// }

// Debug version of DoctorDashboard.jsx with enhanced logging
// import React, { useEffect, useState } from "react";
// import { useNavigate } from "react-router-dom";
// import { socket } from "./socket";

// export default function DoctorDashboard({ doctorId = 501 }) {
//   const [incomingCalls, setIncomingCalls] = useState([]);
//   const [isOnline, setIsOnline] = useState(false);
//   const [connectionStatus, setConnectionStatus] = useState("connecting");
//   const [debugLogs, setDebugLogs] = useState([]);
//   const navigate = useNavigate();

//   // Add debug log function
//   const addDebugLog = (message) => {
//     const timestamp = new Date().toLocaleTimeString();
//     const logEntry = `${timestamp}: ${message}`;
//     console.log(logEntry);
//     setDebugLogs(prev => [...prev.slice(-9), logEntry]); // Keep last 10 logs
//   };

//   useEffect(() => {
//     addDebugLog(`🏥 Doctor Dashboard mounting for doctorId: ${doctorId}`);

//     // Check if socket is already connected
//     if (socket.connected) {
//       addDebugLog("✅ Socket already connected, joining doctor room");
//       setConnectionStatus("connected");
//       joinDoctorRoom();
//     } else {
//       addDebugLog("🔄 Socket not connected, waiting for connection");
//       setConnectionStatus("connecting");
//     }

//     // Socket connection events
//     const handleConnect = () => {
//       addDebugLog("✅ Socket connected successfully");
//       setConnectionStatus("connected");
//       joinDoctorRoom();
//     };

//     const handleDisconnect = () => {
//       addDebugLog("❌ Socket disconnected");
//       setConnectionStatus("disconnected");
//       setIsOnline(false);
//     };

//     const handleConnectError = (error) => {
//       addDebugLog(`❌ Socket connection error: ${error.message}`);
//       setConnectionStatus("error");
//     };

//     // Doctor-specific events
//     const handleDoctorOnline = (data) => {
//       addDebugLog(`👨‍⚕️ Doctor online event received: ${JSON.stringify(data)}`);
//       if (data.doctorId === doctorId) {
//         setIsOnline(true);
//         setConnectionStatus("online");
//         addDebugLog(`✅ Doctor ${doctorId} is now ONLINE`);
//       }
//     };

//     const handleDoctorOffline = (data) => {
//       addDebugLog(`👨‍⚕️ Doctor offline event received: ${JSON.stringify(data)}`);
//       if (data.doctorId === doctorId) {
//         setIsOnline(false);
//         setConnectionStatus("offline");
//       }
//     };

//     const handleCallRequested = (callData) => {
//       addDebugLog(`📞 Incoming call received: ${JSON.stringify(callData)}`);
      
//       // Add to incoming calls list
//       setIncomingCalls(prev => {
//         const exists = prev.some(call => call.id === callData.callId);
//         if (exists) {
//           addDebugLog(`⚠️ Duplicate call ignored: ${callData.callId}`);
//           return prev;
//         }
        
//         return [...prev, { ...callData, id: callData.callId }];
//       });
//     };

//     // Generic event listener to catch ALL events
//     const handleAnyEvent = (eventName, ...args) => {
//       if (eventName !== 'ping' && eventName !== 'pong') {
//         addDebugLog(`📡 Event received: ${eventName} - ${JSON.stringify(args)}`);
//       }
//     };

//     // Join doctor room function
//     const joinDoctorRoom = () => {
//       addDebugLog(`🏥 Emitting join-doctor event for ID: ${doctorId}`);
//       setConnectionStatus("joining");
//       socket.emit("join-doctor", doctorId);
      
//       // Set a timeout to detect if we don't receive doctor-online event
//       setTimeout(() => {
//         if (!isOnline && socket.connected) {
//           addDebugLog(`⚠️ TIMEOUT: No doctor-online event received after 3 seconds`);
//           addDebugLog(`🔄 Retrying join-doctor...`);
//           socket.emit("join-doctor", doctorId);
//         }
//       }, 3000);
//     };

//     // Add event listeners
//     socket.on("connect", handleConnect);
//     socket.on("disconnect", handleDisconnect);
//     socket.on("connect_error", handleConnectError);
//     socket.on("doctor-online", handleDoctorOnline);
//     socket.on("doctor-offline", handleDoctorOffline);
//     socket.on("call-requested", handleCallRequested);

//     // Listen to ALL socket events for debugging
//     socket.onAny(handleAnyEvent);

//     // Test server communication
//     addDebugLog("🧪 Testing server communication...");
//     socket.emit("get-server-status");
//     socket.on("server-status", (status) => {
//       addDebugLog(`📊 Server status received: ${JSON.stringify(status)}`);
//     });

//     // Cleanup function
//     return () => {
//       addDebugLog("🧹 Cleaning up doctor dashboard");
      
//       socket.off("connect", handleConnect);
//       socket.off("disconnect", handleDisconnect);
//       socket.off("connect_error", handleConnectError);
//       socket.off("doctor-online", handleDoctorOnline);
//       socket.off("doctor-offline", handleDoctorOffline);
//       socket.off("call-requested", handleCallRequested);
//       socket.off("server-status");
//       socket.offAny(handleAnyEvent);
      
//       if (socket.connected) {
//         addDebugLog(`🚪 Emitting leave-doctor for ID: ${doctorId}`);
//         socket.emit("leave-doctor", doctorId);
//       }
      
//       setIsOnline(false);
//       setConnectionStatus("disconnected");
//     };
//   }, [doctorId, isOnline]); // Added isOnline to dependencies

//   const handleAccept = (call) => {
//     addDebugLog(`✅ Accepting call: ${call.id}`);
    
//     setIncomingCalls(prev => prev.filter(c => c.id !== call.id));
    
//     socket.emit("call-accepted", {
//       callId: call.id,
//       doctorId: call.doctorId,
//       patientId: call.patientId,
//       channel: call.channel
//     });
    
//     navigate(`/call-page/${call.channel}?uid=${doctorId}&role=host`);
//   };

//   const handleReject = (call) => {
//     addDebugLog(`❌ Rejecting call: ${call.id}`);
    
//     setIncomingCalls(prev => prev.filter(c => c.id !== call.id));
    
//     socket.emit("call-rejected", {
//       callId: call.id,
//       doctorId: call.doctorId,
//       patientId: call.patientId
//     });
//   };

//   const manualRejoin = () => {
//     addDebugLog("🔄 Manual rejoin triggered");
//     setIsOnline(false);
//     setConnectionStatus("rejoining");
//     socket.emit("join-doctor", doctorId);
//   };

//   const testServerCommunication = () => {
//     addDebugLog("🧪 Testing server communication manually");
//     socket.emit("ping", { doctorId, timestamp: Date.now() });
//     socket.once("pong", (data) => {
//       addDebugLog(`🏓 Pong received: ${JSON.stringify(data)}`);
//     });
//   };

//   const getStatusColor = () => {
//     if (isOnline) return "#16a34a"; // green
//     if (connectionStatus === "connecting" || connectionStatus === "joining" || connectionStatus === "rejoining") return "#f59e0b"; // yellow
//     return "#dc2626"; // red
//   };

//   const getStatusText = () => {
//     if (isOnline) return "🟢 ONLINE";
//     if (connectionStatus === "connecting") return "🟡 CONNECTING";
//     if (connectionStatus === "joining") return "🟡 JOINING ROOM";
//     if (connectionStatus === "rejoining") return "🟡 REJOINING";
//     if (connectionStatus === "connected") return "🟡 CONNECTED (Not in room)";
//     return "🔴 OFFLINE";
//   };

//   return (
//     <div style={{ padding: 20, maxWidth: 800, margin: "0 auto" }}>
//       <div style={{ display: "flex", alignItems: "center", marginBottom: 20 }}>
//         <h2>Doctor Dashboard</h2>
//         <span style={{
//           marginLeft: 16,
//           padding: "4px 8px",
//           borderRadius: 12,
//           fontSize: 12,
//           fontWeight: "bold",
//           background: isOnline ? "#dcfce7" : "#fee2e2",
//           color: getStatusColor()
//         }}>
//           {getStatusText()}
//         </span>
//       </div>
      
//       <p>Doctor ID: <strong>{doctorId}</strong></p>
      
//       {/* Connection Status Details */}
//       <div style={{ 
//         marginBottom: 20, 
//         padding: 12, 
//         background: "#f9fafb", 
//         borderRadius: 8,
//         fontSize: 14
//       }}>
//         <div>Socket ID: <code>{socket.id || "Not connected"}</code></div>
//         <div>Connection Status: <strong>{connectionStatus}</strong></div>
//         <div>Socket Connected: <strong>{socket.connected ? "Yes" : "No"}</strong></div>
//         <div>Is Online: <strong>{isOnline ? "Yes" : "No"}</strong></div>
//       </div>

//       <div>
//         <h3>Incoming Calls ({incomingCalls.length})</h3>
//         {incomingCalls.length > 0 ? (
//           incomingCalls.map(call => (
//             <div key={call.id} style={{
//               background: "#fef3c7",
//               padding: 16,
//               borderRadius: 8,
//               marginBottom: 12,
//               border: "2px solid #f59e0b"
//             }}>
//               <h4 style={{ margin: "0 0 8px 0" }}>📞 Incoming Call</h4>
//               <p style={{ margin: "4px 0" }}>
//                 <strong>Patient:</strong> {call.patientId}
//               </p>
//               <p style={{ margin: "4px 0" }}>
//                 <strong>Channel:</strong> {call.channel}
//               </p>
//               <p style={{ margin: "4px 0", fontSize: 12, color: "#666" }}>
//                 <strong>Time:</strong> {new Date(call.timestamp).toLocaleTimeString()}
//               </p>
              
//               <div style={{ marginTop: 12 }}>
//                 <button
//                   onClick={() => handleAccept(call)}
//                   style={{
//                     padding: "8px 16px",
//                     marginRight: 8,
//                     borderRadius: 6,
//                     background: "#16a34a",
//                     color: "white",
//                     border: "none",
//                     cursor: "pointer",
//                     fontWeight: "bold"
//                   }}
//                 >
//                   ✅ Accept Call
//                 </button>
//                 <button
//                   onClick={() => handleReject(call)}
//                   style={{
//                     padding: "8px 16px",
//                     borderRadius: 6,
//                     background: "#dc2626",
//                     color: "white",
//                     border: "none",
//                     cursor: "pointer",
//                     fontWeight: "bold"
//                   }}
//                 >
//                   ❌ Reject
//                 </button>
//               </div>
//             </div>
//           ))
//         ) : (
//           <div style={{
//             background: "#f3f4f6",
//             padding: 20,
//             borderRadius: 8,
//             textAlign: "center",
//             color: "#6b7280"
//           }}>
//             <p>No incoming calls at the moment</p>
//             <p style={{ fontSize: 14 }}>
//               {isOnline 
//                 ? "You'll be notified when patients request calls" 
//                 : "Connect to receive calls"
//               }
//             </p>
//           </div>
//         )}
//       </div>

//       {/* Enhanced Debug Panel */}
//       <div style={{ 
//         marginTop: 30, 
//         padding: 16, 
//         background: "#f0f0f0", 
//         borderRadius: 8,
//         fontSize: 12
//       }}>
//         <h4>Debug Panel</h4>
//         <div style={{ marginBottom: 12 }}>
//           <div>Socket Connected: {socket.connected.toString()}</div>
//           <div>Is Online: {isOnline.toString()}</div>
//           <div>Connection Status: {connectionStatus}</div>
//           <div>Incoming Calls: {incomingCalls.length}</div>
//         </div>
        
//         <div style={{ marginBottom: 12 }}>
//           <button 
//             onClick={manualRejoin}
//             style={{ marginRight: 8, padding: "4px 8px", fontSize: 12 }}
//           >
//             🔄 Rejoin Room
//           </button>
//           <button 
//             onClick={testServerCommunication}
//             style={{ marginRight: 8, padding: "4px 8px", fontSize: 12 }}
//           >
//             🧪 Test Server
//           </button>
//           <button 
//             onClick={() => setDebugLogs([])}
//             style={{ padding: "4px 8px", fontSize: 12 }}
//           >
//             🗑️ Clear Logs
//           </button>
//         </div>

//         <div style={{ 
//           maxHeight: 200, 
//           overflowY: 'auto', 
//           background: '#000', 
//           color: '#0f0', 
//           padding: 8, 
//           borderRadius: 4,
//           fontFamily: 'monospace'
//         }}>
//           <div><strong>Debug Logs:</strong></div>
//           {debugLogs.length === 0 ? (
//             <div>No logs yet...</div>
//           ) : (
//             debugLogs.map((log, index) => (
//               <div key={index}>{log}</div>
//             ))
//           )}
//         </div>
//       </div>
//     </div>
//   );
// }

// import React, { useEffect, useState } from "react";
// import { useNavigate } from "react-router-dom";
// import { socket } from "./socket";

// export default function DoctorDashboard({ doctorId = 501 }) {
//   const [incomingCalls, setIncomingCalls] = useState([]);
//   const [isOnline, setIsOnline] = useState(false);
//   const [connectionStatus, setConnectionStatus] = useState("connecting");
//   const [debugLogs, setDebugLogs] = useState([]);
//   const navigate = useNavigate();

//   // Add debug log function
//   const addDebugLog = (message) => {
//     const timestamp = new Date().toLocaleTimeString();
//     const logEntry = `${timestamp}: ${message}`;
//     console.log(logEntry);
//     setDebugLogs(prev => [...prev.slice(-9), logEntry]); // Keep last 10 logs
//   };

//   // Join doctor room function - MOVED BEFORE useEffect
//   const joinDoctorRoom = () => {
//     addDebugLog(`🏥 Emitting join-doctor event for ID: ${doctorId}`);
//     setConnectionStatus("joining");
//     socket.emit("join-doctor", doctorId);
    
//     // Set a timeout to detect if we don't receive doctor-online event
//     setTimeout(() => {
//       if (!isOnline && socket.connected) {
//         addDebugLog(`⚠️ TIMEOUT: No doctor-online event received after 3 seconds`);
//         addDebugLog(`🔄 Retrying join-doctor...`);
//         socket.emit("join-doctor", doctorId);
//       }
//     }, 3000);
//   };

//   useEffect(() => {
//     addDebugLog(`🏥 Doctor Dashboard mounting for doctorId: ${doctorId}`);

//     // Check if socket is already connected
//     if (socket.connected) {
//       addDebugLog("✅ Socket already connected, joining doctor room");
//       setConnectionStatus("connected");
//       joinDoctorRoom();
//     } else {
//       addDebugLog("🔄 Socket not connected, waiting for connection");
//       setConnectionStatus("connecting");
//     }

//     // Socket connection events
//     const handleConnect = () => {
//       addDebugLog("✅ Socket connected successfully");
//       setConnectionStatus("connected");
//       joinDoctorRoom();
//     };

//     const handleDisconnect = () => {
//       addDebugLog("❌ Socket disconnected");
//       setConnectionStatus("disconnected");
//       setIsOnline(false);
//     };

//     const handleConnectError = (error) => {
//       addDebugLog(`❌ Socket connection error: ${error.message}`);
//       setConnectionStatus("error");
//     };

//     // Doctor-specific events
//     const handleDoctorOnline = (data) => {
//       addDebugLog(`👨‍⚕️ Doctor online event received: ${JSON.stringify(data)}`);
//       if (data.doctorId === doctorId) {
//         setIsOnline(true);
//         setConnectionStatus("online");
//         addDebugLog(`✅ Doctor ${doctorId} is now ONLINE`);
//       }
//     };

//     const handleDoctorOffline = (data) => {
//       addDebugLog(`👨‍⚕️ Doctor offline event received: ${JSON.stringify(data)}`);
//       if (data.doctorId === doctorId) {
//         setIsOnline(false);
//         setConnectionStatus("offline");
//       }
//     };

//     const handleCallRequested = (callData) => {
//       addDebugLog(`📞 Incoming call received: ${JSON.stringify(callData)}`);
      
//       // Add to incoming calls list
//       setIncomingCalls(prev => {
//         const exists = prev.some(call => call.id === callData.callId);
//         if (exists) {
//           addDebugLog(`⚠️ Duplicate call ignored: ${callData.callId}`);
//           return prev;
//         }
        
//         return [...prev, { ...callData, id: callData.callId }];
//       });
//     };

//     // Error handling events
//     const handleJoinError = (error) => {
//       addDebugLog(`❌ Error joining doctor room: ${error.message}`);
//       setConnectionStatus("error");
//     };

//     // Generic event listener to catch ALL events
//     const handleAnyEvent = (eventName, ...args) => {
//       if (eventName !== 'ping' && eventName !== 'pong') {
//         addDebugLog(`📡 Event received: ${eventName} - ${JSON.stringify(args)}`);
//       }
//     };

//     // Add event listeners
//     socket.on("connect", handleConnect);
//     socket.on("disconnect", handleDisconnect);
//     socket.on("connect_error", handleConnectError);
//     socket.on("doctor-online", handleDoctorOnline);
//     socket.on("doctor-offline", handleDoctorOffline);
//     socket.on("call-requested", handleCallRequested);
//     socket.on("join-error", handleJoinError);

//     // Listen to ALL socket events for debugging
//     socket.onAny(handleAnyEvent);

//     // Test server communication
//     addDebugLog("🧪 Testing server communication...");
//     socket.emit("get-server-status");
//     socket.on("server-status", (status) => {
//       addDebugLog(`📊 Server status received: ${JSON.stringify(status)}`);
//     });

//     // Cleanup function
//     return () => {
//       addDebugLog("🧹 Cleaning up doctor dashboard");
      
//       socket.off("connect", handleConnect);
//       socket.off("disconnect", handleDisconnect);
//       socket.off("connect_error", handleConnectError);
//       socket.off("doctor-online", handleDoctorOnline);
//       socket.off("doctor-offline", handleDoctorOffline);
//       socket.off("call-requested", handleCallRequested);
//       socket.off("join-error", handleJoinError);
//       socket.off("server-status");
//       socket.offAny(handleAnyEvent);
      
//       if (socket.connected) {
//         addDebugLog(`🚪 Emitting leave-doctor for ID: ${doctorId}`);
//         socket.emit("leave-doctor", doctorId);
//       }
      
//       setIsOnline(false);
//       setConnectionStatus("disconnected");
//     };
//   }, [doctorId, isOnline]);

//   const handleAccept = (call) => {
//     addDebugLog(`✅ Accepting call: ${call.id}`);
    
//     setIncomingCalls(prev => prev.filter(c => c.id !== call.id));
    
//     socket.emit("call-accepted", {
//       callId: call.id,
//       doctorId: call.doctorId,
//       patientId: call.patientId,
//       channel: call.channel
//     });
    
//     navigate(`/call-page/${call.channel}?uid=${doctorId}&role=host`);
//   };

//   const handleReject = (call) => {
//     addDebugLog(`❌ Rejecting call: ${call.id}`);
    
//     setIncomingCalls(prev => prev.filter(c => c.id !== call.id));
    
//     socket.emit("call-rejected", {
//       callId: call.id,
//       doctorId: call.doctorId,
//       patientId: call.patientId
//     });
//   };

//   const manualRejoin = () => {
//     addDebugLog("🔄 Manual rejoin triggered");
//     setIsOnline(false);
//     setConnectionStatus("rejoining");
//     socket.emit("join-doctor", doctorId);
//   };

//   const testServerCommunication = () => {
//     addDebugLog("🧪 Testing server communication manually");
//     socket.emit("ping", { doctorId, timestamp: Date.now() });
//     socket.once("pong", (data) => {
//       addDebugLog(`🏓 Pong received: ${JSON.stringify(data)}`);
//     });
//   };

//   const getStatusColor = () => {
//     if (isOnline) return "#16a34a"; // green
//     if (connectionStatus === "connecting" || connectionStatus === "joining" || connectionStatus === "rejoining") return "#f59e0b"; // yellow
//     return "#dc2626"; // red
//   };

//   const getStatusText = () => {
//     if (isOnline) return "🟢 ONLINE";
//     if (connectionStatus === "connecting") return "🟡 CONNECTING";
//     if (connectionStatus === "joining") return "🟡 JOINING ROOM";
//     if (connectionStatus === "rejoining") return "🟡 REJOINING";
//     if (connectionStatus === "connected") return "🟡 CONNECTED (Not in room)";
//     return "🔴 OFFLINE";
//   };

//   return (
//     <div style={{ padding: 20, maxWidth: 800, margin: "0 auto" }}>
//       <div style={{ display: "flex", alignItems: "center", marginBottom: 20 }}>
//         <h2>Doctor Dashboard</h2>
//         <span style={{
//           marginLeft: 16,
//           padding: "4px 8px",
//           borderRadius: 12,
//           fontSize: 12,
//           fontWeight: "bold",
//           background: isOnline ? "#dcfce7" : "#fee2e2",
//           color: getStatusColor()
//         }}>
//           {getStatusText()}
//         </span>
//       </div>
      
//       <p>Doctor ID: <strong>{doctorId}</strong></p>
      
//       {/* Connection Status Details */}
//       <div style={{ 
//         marginBottom: 20, 
//         padding: 12, 
//         background: "#f9fafb", 
//         borderRadius: 8,
//         fontSize: 14
//       }}>
//         <div>Socket ID: <code>{socket.id || "Not connected"}</code></div>
//         <div>Connection Status: <strong>{connectionStatus}</strong></div>
//         <div>Socket Connected: <strong>{socket.connected ? "Yes" : "No"}</strong></div>
//         <div>Is Online: <strong>{isOnline ? "Yes" : "No"}</strong></div>
//       </div>

//       <div>
//         <h3>Incoming Calls ({incomingCalls.length})</h3>
//         {incomingCalls.length > 0 ? (
//           incomingCalls.map(call => (
//             <div key={call.id} style={{
//               background: "#fef3c7",
//               padding: 16,
//               borderRadius: 8,
//               marginBottom: 12,
//               border: "2px solid #f59e0b"
//             }}>
//               <h4 style={{ margin: "0 0 8px 0" }}>📞 Incoming Call</h4>
//               <p style={{ margin: "4px 0" }}>
//                 <strong>Patient:</strong> {call.patientId}
//               </p>
//               <p style={{ margin: "4px 0" }}>
//                 <strong>Channel:</strong> {call.channel}
//               </p>
//               <p style={{ margin: "4px 0", fontSize: 12, color: "#666" }}>
//                 <strong>Time:</strong> {new Date(call.timestamp).toLocaleTimeString()}
//               </p>
              
//               <div style={{ marginTop: 12 }}>
//                 <button
//                   onClick={() => handleAccept(call)}
//                   style={{
//                     padding: "8px 16px",
//                     marginRight: 8,
//                     borderRadius: 6,
//                     background: "#16a34a",
//                     color: "white",
//                     border: "none",
//                     cursor: "pointer",
//                     fontWeight: "bold"
//                   }}
//                 >
//                   ✅ Accept Call
//                 </button>
//                 <button
//                   onClick={() => handleReject(call)}
//                   style={{
//                     padding: "8px 16px",
//                     borderRadius: 6,
//                     background: "#dc2626",
//                     color: "white",
//                     border: "none",
//                     cursor: "pointer",
//                     fontWeight: "bold"
//                   }}
//                 >
//                   ❌ Reject
//                 </button>
//               </div>
//             </div>
//           ))
//         ) : (
//           <div style={{
//             background: "#f3f4f6",
//             padding: 20,
//             borderRadius: 8,
//             textAlign: "center",
//             color: "#6b7280"
//           }}>
//             <p>No incoming calls at the moment</p>
//             <p style={{ fontSize: 14 }}>
//               {isOnline 
//                 ? "You'll be notified when patients request calls" 
//                 : "Connect to receive calls"
//               }
//             </p>
//           </div>
//         )}
//       </div>

//       {/* Enhanced Debug Panel */}
//       <div style={{ 
//         marginTop: 30, 
//         padding: 16, 
//         background: "#f0f0f0", 
//         borderRadius: 8,
//         fontSize: 12
//       }}>
//         <h4>Debug Panel</h4>
//         <div style={{ marginBottom: 12 }}>
//           <div>Socket Connected: {socket.connected.toString()}</div>
//           <div>Is Online: {isOnline.toString()}</div>
//           <div>Connection Status: {connectionStatus}</div>
//           <div>Incoming Calls: {incomingCalls.length}</div>
//         </div>
        
//         <div style={{ marginBottom: 12 }}>
//           <button 
//             onClick={manualRejoin}
//             style={{ marginRight: 8, padding: "4px 8px", fontSize: 12 }}
//           >
//             🔄 Rejoin Room
//           </button>
//           <button 
//             onClick={testServerCommunication}
//             style={{ marginRight: 8, padding: "4px 8px", fontSize: 12 }}
//           >
//             🧪 Test Server
//           </button>
//           <button 
//             onClick={() => setDebugLogs([])}
//             style={{ padding: "4px 8px", fontSize: 12 }}
//           >
//             🗑️ Clear Logs
//           </button>
//         </div>

//         <div style={{ 
//           maxHeight: 200, 
//           overflowY: 'auto', 
//           background: '#000', 
//           color: '#0f0', 
//           padding: 8, 
//           borderRadius: 4,
//           fontFamily: 'monospace'
//         }}>
//           <div><strong>Debug Logs:</strong></div>
//           {debugLogs.length === 0 ? (
//             <div>No logs yet...</div>
//           ) : (
//             debugLogs.map((log, index) => (
//               <div key={index}>{log}</div>
//             ))
//           )}
//         </div>
//       </div>


//     </div>
//   );
// }

import React, { useEffect, useState, useRef, useCallback } from "react";
import { useNavigate } from "react-router-dom";
import { socket } from "./socket";
import {
  requestFcmToken,
  subscribeToForegroundMessages,
} from "../lib/firebaseMessaging";

export default function DoctorDashboard({ doctorId = 501 }) {
  const [incomingCalls, setIncomingCalls] = useState([]);
  const [isOnline, setIsOnline] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState("connecting");
  const [debugLogs, setDebugLogs] = useState([]);
  const [pushStatus, setPushStatus] = useState("idle");
  const [pushError, setPushError] = useState(null);
  const navigate = useNavigate();
  const hasSetUpListeners = useRef(false);
  const pushTokenRef = useRef(null);

  // Add debug log function
  const addDebugLog = useCallback((message) => {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = `${timestamp}: ${message}`;
    console.log(logEntry);
    setDebugLogs((prev) => [...prev.slice(-9), logEntry]); // Keep last 10 logs
  }, []);

  const registerPushTokenWithServer = useCallback(
    (token) => {
      if (!token || !doctorId) {
        return;
      }

      if (!socket.connected) {
        addDebugLog(
          "Socket offline, will send push token after reconnecting to server"
        );
        return;
      }

      addDebugLog("Sending push token to socket server");
      socket.emit("register-push-token", { doctorId, pushToken: token });
      setPushStatus("token-sent");
    },
    [addDebugLog, doctorId]
  );

  // Join doctor room function
  const joinDoctorRoom = () => {
    // Prevent multiple join attempts if already online
    if (isOnline) {
      addDebugLog(`⚠️ Already online, skipping join-doctor`);
      return;
    }
    
    addDebugLog(`🏥 Emitting join-doctor event for ID: ${doctorId}`);
    setConnectionStatus("joining");
    socket.emit("join-doctor", doctorId);
    
    // Set a timeout to detect if we don't receive doctor-online event
    setTimeout(() => {
      if (!isOnline && socket.connected) {
        addDebugLog(`⚠️ TIMEOUT: No doctor-online event received after 3 seconds`);
        addDebugLog(`🔄 Retrying join-doctor...`);
        socket.emit("join-doctor", doctorId);
      }
    }, 3000);
  };

  useEffect(() => {
    let cancelled = false;

    const fetchPushToken = async () => {
      if (!doctorId) {
        return;
      }

      setPushStatus("requesting");

      try {
        const token = await requestFcmToken();
        if (!token || cancelled) {
          return;
        }

        pushTokenRef.current = token;
        setPushError(null);
        setPushStatus("token-generated");
        addDebugLog(`[FCM] Token generated ${token.substring(0, 10)}...`);
        registerPushTokenWithServer(token);
      } catch (error) {
        if (cancelled) {
          return;
        }

        const message =
          error?.message || "Unable to generate Firebase Cloud Messaging token";
        addDebugLog(`[FCM] Token error: ${message}`);
        setPushError(message);
        setPushStatus(
          message.toLowerCase().includes("permission")
            ? "permission-denied"
            : "error"
        );
      }
    };

    fetchPushToken();

    return () => {
      cancelled = true;
    };
  }, [addDebugLog, doctorId, registerPushTokenWithServer]);

  useEffect(() => {
    let unsubscribe = () => {};

    subscribeToForegroundMessages((payload) => {
      addDebugLog(`[FCM] Foreground notification: ${JSON.stringify(payload)}`);
    })
      .then((unsub) => {
        unsubscribe = unsub;
      })
      .catch((error) => {
        addDebugLog(`[FCM] Failed to attach listener: ${error.message}`);
      });

    return () => {
      if (typeof unsubscribe === "function") {
        unsubscribe();
      }
    };
  }, [addDebugLog]);

  useEffect(() => {
    if (hasSetUpListeners.current) {
      addDebugLog("⚠️ Listeners already set up, skipping");
      return;
    }
    
    hasSetUpListeners.current = true;
    addDebugLog(`🏥 Doctor Dashboard mounting for doctorId: ${doctorId}`);

    // Check if socket is already connected
    if (socket.connected) {
      addDebugLog("✅ Socket already connected, joining doctor room");
      setConnectionStatus("connected");
      joinDoctorRoom();
    } else {
      addDebugLog("🔄 Socket not connected, waiting for connection");
      setConnectionStatus("connecting");
    }

    // Socket connection events
    const handleConnect = () => {
      addDebugLog("✅ Socket connected successfully");
      setConnectionStatus("connected");
      joinDoctorRoom();
      if (pushTokenRef.current) {
        registerPushTokenWithServer(pushTokenRef.current);
      }
    };

    const handleDisconnect = () => {
      addDebugLog("❌ Socket disconnected");
      setConnectionStatus("disconnected");
      setIsOnline(false);
    };

    const handleConnectError = (error) => {
      addDebugLog(`❌ Socket connection error: ${error.message}`);
      setConnectionStatus("error");
    };

    // Doctor-specific events
    const handleDoctorOnline = (data) => {
      addDebugLog(`👨‍⚕️ Doctor online event received: ${JSON.stringify(data)}`);
      if (data.doctorId === doctorId) {
        setIsOnline(true);
        setConnectionStatus("online");
        addDebugLog(`✅ Doctor ${doctorId} is now ONLINE`);
      }
    };

    const handleDoctorOffline = (data) => {
      addDebugLog(`👨‍⚕️ Doctor offline event received: ${JSON.stringify(data)}`);
      if (data.doctorId === doctorId) {
        setIsOnline(false);
        setConnectionStatus("offline");
      }
    };

    const handleCallRequested = (callData) => {
      addDebugLog(`📞 Incoming call received: ${JSON.stringify(callData)}`);
      
      // Add to incoming calls list
      setIncomingCalls(prev => {
        const exists = prev.some(call => call.id === callData.callId);
        if (exists) {
          addDebugLog(`⚠️ Duplicate call ignored: ${callData.callId}`);
          return prev;
        }
        
        return [...prev, { ...callData, id: callData.callId }];
      });
    };

    const handlePushTokenRegistered = (payload) => {
      if (Number(payload?.doctorId) !== Number(doctorId)) {
        return;
      }

      if (payload?.success) {
        addDebugLog("[FCM] Push token registered on server");
        setPushStatus("registered");
        setPushError(null);
      } else {
        const message = payload?.message || "Failed to register push token";
        addDebugLog(` [FCM] Push token registration failed: ${message}`);
        setPushStatus("error");
        setPushError(message);
      }
    };


    // Error handling events
    const handleJoinError = (error) => {
      addDebugLog(`❌ Error joining doctor room: ${error.message}`);
      setConnectionStatus("error");
    };

    // Generic event listener to catch ALL events
    const handleAnyEvent = (eventName, ...args) => {
      if (eventName !== 'ping' && eventName !== 'pong') {
        addDebugLog(`📡 Event received: ${eventName} - ${JSON.stringify(args)}`);
      }
    };

    // Add event listeners
    socket.on("connect", handleConnect);
    socket.on("disconnect", handleDisconnect);
    socket.on("connect_error", handleConnectError);
    socket.on("doctor-online", handleDoctorOnline);
    socket.on("doctor-offline", handleDoctorOffline);
    socket.on("call-requested", handleCallRequested);
    socket.on("join-error", handleJoinError);
    socket.on("push-token-registered", handlePushTokenRegistered);

    // Listen to ALL socket events for debugging
    socket.onAny(handleAnyEvent);

    // Test server communication
    addDebugLog("🧪 Testing server communication...");
    socket.emit("get-server-status");
    socket.on("server-status", (status) => {
      addDebugLog(`📊 Server status received: ${JSON.stringify(status)}`);
    });

    // Cleanup function
    return () => {
      addDebugLog("🧹 Cleaning up doctor dashboard");
      
      socket.off("connect", handleConnect);
      socket.off("disconnect", handleDisconnect);
      socket.off("connect_error", handleConnectError);
      socket.off("doctor-online", handleDoctorOnline);
      socket.off("doctor-offline", handleDoctorOffline);
      socket.off("call-requested", handleCallRequested);
      socket.off("join-error", handleJoinError);
      socket.off("push-token-registered", handlePushTokenRegistered);
      socket.off("server-status");
      socket.offAny(handleAnyEvent);
      
      if (socket.connected) {
        addDebugLog(`🚪 Emitting leave-doctor for ID: ${doctorId}`);
        socket.emit("leave-doctor", doctorId);
      }
      
      setIsOnline(false);
      setConnectionStatus("disconnected");
      hasSetUpListeners.current = false;
    };
  }, [doctorId]); // Only doctorId in dependencies - REMOVED isOnline

  const handleAccept = (call) => {
    addDebugLog(`✅ Accepting call: ${call.id}`);
    
    setIncomingCalls(prev => prev.filter(c => c.id !== call.id));
    
    socket.emit("call-accepted", {
      callId: call.id,
      doctorId: call.doctorId,
      patientId: call.patientId,
      channel: call.channel
    });
    
    navigate(`/call-page/${call.channel}?uid=${doctorId}&role=host`);
  };

  const handleReject = (call) => {
    addDebugLog(`❌ Rejecting call: ${call.id}`);
    
    setIncomingCalls(prev => prev.filter(c => c.id !== call.id));
    
    socket.emit("call-rejected", {
      callId: call.id,
      doctorId: call.doctorId,
      patientId: call.patientId
    });
  };

  const manualRejoin = () => {
    addDebugLog("🔄 Manual rejoin triggered");
    setIsOnline(false);
    setConnectionStatus("rejoining");
    socket.emit("join-doctor", doctorId);
  };

  const testServerCommunication = () => {
    addDebugLog("🧪 Testing server communication manually");
    socket.emit("ping", { doctorId, timestamp: Date.now() });
    socket.once("pong", (data) => {
      addDebugLog(`🏓 Pong received: ${JSON.stringify(data)}`);
    });
  };

  const getStatusColor = () => {
    if (isOnline) return "#16a34a"; // green
    if (connectionStatus === "connecting" || connectionStatus === "joining" || connectionStatus === "rejoining") return "#f59e0b"; // yellow
    return "#dc2626"; // red
  };

  const getStatusText = () => {
    if (isOnline) return "🟢 ONLINE";
    if (connectionStatus === "connecting") return "🟡 CONNECTING";
    if (connectionStatus === "joining") return "🟡 JOINING ROOM";
    if (connectionStatus === "rejoining") return "🟡 REJOINING";
    if (connectionStatus === "connected") return "🟡 CONNECTED (Not in room)";
    return "🔴 OFFLINE";
  };

  return (
    <div style={{ padding: 20, maxWidth: 800, margin: "0 auto" }}>
      <div style={{ display: "flex", alignItems: "center", marginBottom: 20 }}>
        <h2>Doctor Dashboard</h2>
        <span style={{
          marginLeft: 16,
          padding: "4px 8px",
          borderRadius: 12,
          fontSize: 12,
          fontWeight: "bold",
          background: isOnline ? "#dcfce7" : "#fee2e2",
          color: getStatusColor()
        }}>
          {getStatusText()}
        </span>
      </div>
      
      <p>Doctor ID: <strong>{doctorId}</strong></p>
      
      {/* Connection Status Details */}
      <div style={{ 
        marginBottom: 20, 
        padding: 12, 
        background: "#f9fafb", 
        borderRadius: 8,
        fontSize: 14
      }}>
        <div>Socket ID: <code>{socket.id || "Not connected"}</code></div>
        <div>Connection Status: <strong>{connectionStatus}</strong></div>
        <div>Socket Connected: <strong>{socket.connected ? "Yes" : "No"}</strong></div>
        <div>Is Online: <strong>{isOnline ? "Yes" : "No"}</strong></div>
      </div>

      <div>
        <h3>Incoming Calls ({incomingCalls.length})</h3>
        {incomingCalls.length > 0 ? (
          incomingCalls.map(call => (
            <div key={call.id} style={{
              background: "#fef3c7",
              padding: 16,
              borderRadius: 8,
              marginBottom: 12,
              border: "2px solid #f59e0b"
            }}>
              <h4 style={{ margin: "0 0 8px 0" }}>📞 Incoming Call</h4>
              <p style={{ margin: "4px 0" }}>
                <strong>Patient:</strong> {call.patientId}
              </p>
              <p style={{ margin: "4px 0" }}>
                <strong>Channel:</strong> {call.channel}
              </p>
              <p style={{ margin: "4px 0", fontSize: 12, color: "#666" }}>
                <strong>Time:</strong> {new Date(call.timestamp).toLocaleTimeString()}
              </p>
              
              <div style={{ marginTop: 12 }}>
                <button
                  onClick={() => handleAccept(call)}
                  style={{
                    padding: "8px 16px",
                    marginRight: 8,
                    borderRadius: 6,
                    background: "#16a34a",
                    color: "white",
                    border: "none",
                    cursor: "pointer",
                    fontWeight: "bold"
                  }}
                >
                  ✅ Accept Call
                </button>
                <button
                  onClick={() => handleReject(call)}
                  style={{
                    padding: "8px 16px",
                    borderRadius: 6,
                    background: "#dc2626",
                    color: "white",
                    border: "none",
                    cursor: "pointer",
                    fontWeight: "bold"
                  }}
                >
                  ❌ Reject
                </button>
              </div>
            </div>
          ))
        ) : (
          <div style={{
            background: "#f3f4f6",
            padding: 20,
            borderRadius: 8,
            textAlign: "center",
            color: "#6b7280"
          }}>
            <p>No incoming calls at the moment</p>
            <p style={{ fontSize: 14 }}>
              {isOnline 
                ? "You'll be notified when patients request calls" 
                : "Connect to receive calls"
              }
            </p>
          </div>
        )}
      </div>

      {/* Enhanced Debug Panel */}
      <div style={{ 
        marginTop: 30, 
        padding: 16, 
        background: "#f0f0f0", 
        borderRadius: 8,
        fontSize: 12
      }}>
        <h4>Debug Panel</h4>
        <div style={{ marginBottom: 12 }}>
          <div>Socket Connected: {socket.connected.toString()}</div>
          <div>Is Online: {isOnline.toString()}</div>
          <div>Connection Status: {connectionStatus}</div>
          <div>Incoming Calls: {incomingCalls.length}</div>
          <div>Push Token Status: {pushStatus}</div>
          {pushError ? (
            <div style={{ color: "#dc2626", fontSize: 12 }}>
              Last Push Error: {pushError}
            </div>
          ) : null}
        </div>
        
        <div style={{ marginBottom: 12 }}>
          <button 
            onClick={manualRejoin}
            style={{ marginRight: 8, padding: "4px 8px", fontSize: 12 }}
          >
            🔄 Rejoin Room
          </button>
          <button 
            onClick={testServerCommunication}
            style={{ marginRight: 8, padding: "4px 8px", fontSize: 12 }}
          >
            🧪 Test Server
          </button>
          <button 
            onClick={() => setDebugLogs([])}
            style={{ padding: "4px 8px", fontSize: 12 }}
          >
            🗑️ Clear Logs
          </button>
        </div>

        <div style={{ 
          maxHeight: 200, 
          overflowY: 'auto', 
          background: '#000', 
          color: '#0f0', 
          padding: 8, 
          borderRadius: 4,
          fontFamily: 'monospace'
        }}>
          <div><strong>Debug Logs:</strong></div>
          {debugLogs.length === 0 ? (
            <div>No logs yet...</div>
          ) : (
            debugLogs.map((log, index) => (
              <div key={index}>{log}</div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
