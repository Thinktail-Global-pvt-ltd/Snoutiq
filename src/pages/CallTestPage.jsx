// // src/pages/CallPage.jsx
// import React, { useEffect, useMemo, useRef, useState } from "react";
// import { useParams, useSearchParams } from "react-router-dom";
// import AgoraRTC from "agora-rtc-sdk-ng";

// const APP_ID = "e20a4d60afd8494eab490563ad2e61d1"; // Agora App ID

// export default function CallPage() {
//   const { channelName } = useParams();
//   const [qs] = useSearchParams();

//   // sanitize channel name
//   const safeChannel = useMemo(() => {
//     return (channelName || "default_channel")
//       .replace(/[^a-zA-Z0-9_]/g, "")
//       .slice(0, 63);
//   }, [channelName]);

//   // unique uid
//   const uid = useMemo(() => {
//     const q = Number(qs.get("uid"));
//     return Number.isFinite(q) ? q : Math.floor(Math.random() * 1e6);
//   }, [qs]);

//   const role = (qs.get("role") || "audience").toLowerCase();

//   // refs
//   const localRef = useRef(null);
//   const remoteRef = useRef(null);

//   const [client] = useState(() =>
//     AgoraRTC.createClient({ mode: "rtc", codec: "vp8" })
//   );
//   const [localTracks, setLocalTracks] = useState([]);
//   const [joined, setJoined] = useState(false);

//   useEffect(() => {
//     let mounted = true;

//     async function joinChannel() {
//       try {
//         console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);

//         await client.join(APP_ID, safeChannel, null, uid);

//         // ✅ Only doctor publishes
//         if (role === "host") {
//           const mic = await AgoraRTC.createMicrophoneAudioTrack();
//           const cam = await AgoraRTC.createCameraVideoTrack();
//           if (!mounted) return;

//           setLocalTracks([mic, cam]);
//           cam.play(localRef.current);
//           await client.publish([mic, cam]);
//           console.log("✅ Doctor published tracks");
//         }

//         // Remote user handling
//         client.on("user-published", async (user, mediaType) => {
//           await client.subscribe(user, mediaType);
//           console.log("📡 Subscribed to", user.uid, mediaType);

//           if (mediaType === "video") {
//             user.videoTrack.play(remoteRef.current);
//           }
//           if (mediaType === "audio") {
//             user.audioTrack.play();
//           }
//         });

//         client.on("user-unpublished", (user) => {
//           console.log("❌ User unpublished:", user.uid);
//           if (remoteRef.current) remoteRef.current.innerHTML = "";
//         });

//         setJoined(true);
//       } catch (err) {
//         console.error("❌ Agora join error:", err);
//       }
//     }

//     joinChannel();

//     return () => {
//       mounted = false;
//       localTracks.forEach((t) => t.close());
//       client.leave();
//     };
//   }, [client, safeChannel, role, uid]);

//   const handleMute = () => {
//     if (localTracks[0]) {
//       const mic = localTracks[0];
//       mic.setEnabled(!mic.enabled);
//       console.log(mic.enabled ? "🎤 Unmuted" : "🔇 Muted");
//     }
//   };

//   const handleCamera = () => {
//     if (localTracks[1]) {
//       const cam = localTracks[1];
//       cam.setEnabled(!cam.enabled);
//       console.log(cam.enabled ? "📷 Camera On" : "📷 Camera Off");
//     }
//   };

//   const handleLeave = async () => {
//     localTracks.forEach((t) => t.close());
//     await client.leave();
//     setJoined(false);
//     console.log("🚪 Left the channel");
//   };

//   return (
//     <div style={{ padding: 20 }}>
//       <h2>Agora Call</h2>
//       <p>
//         uid: <b>{uid}</b> · channel: <b>{safeChannel}</b> · role:{" "}
//         <b>{role}</b>
//       </p>

//       <div style={{ display: "flex", gap: 20, marginTop: 20 }}>
//         <div
//           ref={localRef}
//           style={{
//             width: 320,
//             height: 240,
//             background: "#000",
//             borderRadius: 8,
//           }}
//         />
//         <div
//           ref={remoteRef}
//           style={{
//             width: 320,
//             height: 240,
//             background: "#000",
//             borderRadius: 8,
//           }}
//         />
//       </div>

//       {/* Controls only for doctor (host) */}
//       {joined && role === "host" && (
//         <div style={{ marginTop: 20 }}>
//           <button onClick={handleMute} style={{ marginRight: 10 }}>
//             🎤 Toggle Mute
//           </button>
//           <button onClick={handleCamera} style={{ marginRight: 10 }}>
//             📷 Toggle Camera
//           </button>
//           <button onClick={handleLeave}>🚪 Leave</button>
//         </div>
//       )}
//     </div>
//   );
// }


// import React, { useEffect, useMemo, useRef, useState } from "react";
// import { useParams, useSearchParams, useNavigate } from "react-router-dom";
// import AgoraRTC from "agora-rtc-sdk-ng";
// import { socket } from "./socket";

// const APP_ID = "e20a4d60afd8494eab490563ad2e61d1"; // Replace with your Agora App ID

// export default function CallPage() {
//   const { channelName } = useParams();
//   const [qs] = useSearchParams();
//   const navigate = useNavigate();

//   // Sanitize channel name
//   const safeChannel = useMemo(() => {
//     return (channelName || "default_channel")
//       .replace(/[^a-zA-Z0-9_]/g, "")
//       .slice(0, 63);
//   }, [channelName]);

//   // Get UID and role from URL params
//   const uid = useMemo(() => {
//     const q = Number(qs.get("uid"));
//     return Number.isFinite(q) ? q : Math.floor(Math.random() * 1e6);
//   }, [qs]);

//   const role = (qs.get("role") || "audience").toLowerCase();
//   const isHost = role === "host"; // Doctor is host

//   // Refs for video elements
//   const localVideoRef = useRef(null);
//   const remoteVideoRef = useRef(null);

//   // State
//   const [client] = useState(() => 
//     AgoraRTC.createClient({ mode: "rtc", codec: "vp8" })
//   );
//   const [localTracks, setLocalTracks] = useState([]);
//   const [remoteUsers, setRemoteUsers] = useState([]);
//   const [joined, setJoined] = useState(false);
//   const [callStatus, setCallStatus] = useState("connecting");
//   const [isMuted, setIsMuted] = useState(false);
//   const [isCameraOff, setIsCameraOff] = useState(false);

//   useEffect(() => {
//     let mounted = true;

//     async function joinChannel() {
//       try {
//         setCallStatus("connecting");
//         console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);

//         // Join the channel
//         await client.join(APP_ID, safeChannel, null, uid);
        
//         if (!mounted) return;
//         setJoined(true);
//         setCallStatus("connected");

//         // Create and publish tracks for host (doctor)
//         if (isHost) {
//           try {
//             const audioTrack = await AgoraRTC.createMicrophoneAudioTrack({
//               encoderConfig: "music_standard",
//             });
//             const videoTrack = await AgoraRTC.createCameraVideoTrack({
//               encoderConfig: "720p_1",
//             });
            
//             if (!mounted) return;

//             setLocalTracks([audioTrack, videoTrack]);
            
//             // Play local video
//             if (localVideoRef.current) {
//               videoTrack.play(localVideoRef.current);
//             }
            
//             // Publish tracks
//             await client.publish([audioTrack, videoTrack]);
//             console.log("✅ Published local tracks");
            
//           } catch (error) {
//             console.error("❌ Error creating local tracks:", error);
//             setCallStatus("error");
//           }
//         }

//         // Handle remote users
//         client.on("user-published", async (user, mediaType) => {
//           try {
//             await client.subscribe(user, mediaType);
//             console.log(`📡 Subscribed to user ${user.uid} ${mediaType}`);

//             if (mediaType === "video" && remoteVideoRef.current) {
//               user.videoTrack?.play(remoteVideoRef.current);
//               setRemoteUsers(prev => {
//                 const updated = prev.filter(u => u.uid !== user.uid);
//                 return [...updated, user];
//               });
//             }
            
//             if (mediaType === "audio") {
//               user.audioTrack?.play();
//             }
//           } catch (error) {
//             console.error("❌ Error subscribing to user:", error);
//           }
//         });

//         client.on("user-unpublished", (user, mediaType) => {
//           console.log(`📡 User ${user.uid} unpublished ${mediaType}`);
//           if (mediaType === "video") {
//             setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
//           }
//         });

//         client.on("user-left", (user) => {
//           console.log(`👋 User ${user.uid} left the channel`);
//           setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
//         });

//       } catch (error) {
//         console.error("❌ Join channel error:", error);
//         setCallStatus("error");
//       }
//     }

//     joinChannel();

//     // Cleanup on unmount
//     return () => {
//       mounted = false;
//       cleanup();
//     };
//   }, [client, safeChannel, role, uid, isHost]);

//   const cleanup = async () => {
//     try {
//       // Close local tracks
//       localTracks.forEach(track => {
//         track.stop();
//         track.close();
//       });
      
//       // Leave channel
//       if (joined) {
//         await client.leave();
//         console.log("🚪 Left the channel");
//       }
      
//       setLocalTracks([]);
//       setRemoteUsers([]);
//       setJoined(false);
//     } catch (error) {
//       console.error("❌ Cleanup error:", error);
//     }
//   };

//   const toggleMute = async () => {
//     if (localTracks[0]) {
//       const audioTrack = localTracks[0];
//       await audioTrack.setEnabled(isMuted);
//       setIsMuted(!isMuted);
//       console.log(isMuted ? "🎤 Unmuted" : "🔇 Muted");
//     }
//   };

//   const toggleCamera = async () => {
//     if (localTracks[1]) {
//       const videoTrack = localTracks[1];
//       await videoTrack.setEnabled(isCameraOff);
//       setIsCameraOff(!isCameraOff);
//       console.log(isCameraOff ? "📷 Camera On" : "📷 Camera Off");
//     }
//   };

//   const handleEndCall = async () => {
//     await cleanup();
    
//     // Notify server about call end
//     socket.emit("call-ended", { channel: safeChannel });
    
//     // Navigate back
//     if (isHost) {
//       navigate("/doctor-dashboard");
//     } else {
//       navigate("/patient-dashboard");
//     }
//   };

//   const getStatusColor = () => {
//     switch (callStatus) {
//       case "connected": return "#16a34a";
//       case "connecting": return "#f59e0b";
//       case "error": return "#dc2626";
//       default: return "#6b7280";
//     }
//   };

//   return (
//     <div style={{ padding: 20, maxWidth: 1200, margin: "0 auto" }}>
//       {/* Header */}
//       <div style={{ marginBottom: 20 }}>
//         <h2>Video Call</h2>
//         <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
//           <span>UID: <strong>{uid}</strong></span>
//           <span>Channel: <strong>{safeChannel}</strong></span>
//           <span>Role: <strong>{role}</strong></span>
//           <span style={{
//             padding: "4px 8px",
//             borderRadius: 12,
//             fontSize: 12,
//             fontWeight: "bold",
//             background: callStatus === "connected" ? "#dcfce7" : "#fef3c7",
//             color: getStatusColor()
//           }}>
//             {callStatus.toUpperCase()}
//           </span>
//         </div>
//       </div>

//       {/* Video Grid */}
//       <div style={{ 
//         display: "grid", 
//         gridTemplateColumns: remoteUsers.length > 0 ? "1fr 1fr" : "1fr",
//         gap: 20, 
//         marginBottom: 20 
//       }}>
//         {/* Local Video (Doctor only) */}
//         {isHost && (
//           <div style={{ position: "relative" }}>
//             <div
//               ref={localVideoRef}
//               style={{
//                 width: "100%",
//                 height: 300,
//                 background: "#000",
//                 borderRadius: 12,
//                 overflow: "hidden"
//               }}
//             />
//             <div style={{
//               position: "absolute",
//               bottom: 8,
//               left: 8,
//               background: "rgba(0,0,0,0.7)",
//               color: "white",
//               padding: "4px 8px",
//               borderRadius: 4,
//               fontSize: 12
//             }}>
//               You (Doctor)
//             </div>
//             {isCameraOff && (
//               <div style={{
//                 position: "absolute",
//                 top: "50%",
//                 left: "50%",
//                 transform: "translate(-50%, -50%)",
//                 color: "white",
//                 fontSize: 18
//               }}>
//                 📷 Camera Off
//               </div>
//             )}
//           </div>
//         )}

//         {/* Remote Video */}
//         <div style={{ position: "relative" }}>
//           <div
//             ref={remoteVideoRef}
//             style={{
//               width: "100%",
//               height: 300,
//               background: "#000",
//               borderRadius: 12,
//               overflow: "hidden"
//             }}
//           />
//           <div style={{
//             position: "absolute",
//             bottom: 8,
//             left: 8,
//             background: "rgba(0,0,0,0.7)",
//             color: "white",
//             padding: "4px 8px",
//             borderRadius: 4,
//             fontSize: 12
//           }}>
//             {remoteUsers.length > 0 
//               ? `${isHost ? "Patient" : "Doctor"} (${remoteUsers[0]?.uid})`
//               : "Waiting for other participant..."
//             }
//           </div>
//           {remoteUsers.length === 0 && (
//             <div style={{
//               position: "absolute",
//               top: "50%",
//               left: "50%",
//               transform: "translate(-50%, -50%)",
//               color: "white",
//               textAlign: "center"
//             }}>
//               <div style={{ fontSize: 48, marginBottom: 8 }}>⏳</div>
//               <div>Waiting for {isHost ? "patient" : "doctor"}...</div>
//             </div>
//           )}
//         </div>
//       </div>

//       {/* Controls (Host/Doctor only) */}
//       {joined && isHost && (
//         <div style={{
//           display: "flex",
//           justifyContent: "center",
//           gap: 12,
//           padding: 16,
//           background: "#f9fafb",
//           borderRadius: 12
//         }}>
//           <button
//             onClick={toggleMute}
//             style={{
//               padding: "12px 16px",
//               borderRadius: 8,
//               background: isMuted ? "#dc2626" : "#6b7280",
//               color: "white",
//               border: "none",
//               cursor: "pointer",
//               fontWeight: "bold",
//               minWidth: 120
//             }}
//           >
//             {isMuted ? "🔇 Unmute" : "🎤 Mute"}
//           </button>
          
//           <button
//             onClick={toggleCamera}
//             style={{
//               padding: "12px 16px",
//               borderRadius: 8,
//               background: isCameraOff ? "#dc2626" : "#6b7280",
//               color: "white",
//               border: "none",
//               cursor: "pointer",
//               fontWeight: "bold",
//               minWidth: 120
//             }}
//           >
//             {isCameraOff ? "📷 Camera On" : "📹 Camera Off"}
//           </button>
          
//           <button
//             onClick={handleEndCall}
//             style={{
//               marginTop: 12,
//               padding: "8px 16px",
//               borderRadius: 6,
//               background: "#374151",
//               color: "white",
//               border: "none",
//               cursor: "pointer"
//             }}
//           >
//             Go Back
//           </button>
//         </div>
//       )}
//     </div>
//   );
// }


// import React, { useEffect, useMemo, useRef, useState } from "react";
// import { useParams, useSearchParams, useNavigate } from "react-router-dom";
// import AgoraRTC from "agora-rtc-sdk-ng";
// import { socket } from "./socket";

// const APP_ID = "e20a4d60afd8494eab490563ad2e61d1";

// export default function CallPage() {
//   const { channelName } = useParams();
//   const [qs] = useSearchParams();
//   const navigate = useNavigate();

//   // Sanitize channel name
//   const safeChannel = useMemo(() => {
//     return (channelName || "default_channel")
//       .replace(/[^a-zA-Z0-9_]/g, "")
//       .slice(0, 63);
//   }, [channelName]);

//   // Get UID and role from URL params
//   const uid = useMemo(() => {
//     const q = Number(qs.get("uid"));
//     return Number.isFinite(q) ? q : Math.floor(Math.random() * 1e6);
//   }, [qs]);

//   const role = (qs.get("role") || "audience").toLowerCase();
//   const isHost = role === "host";

//   // Refs for video elements
//   const localVideoRef = useRef(null);
//   const remoteVideoRef = useRef(null);

//   // State
//   const [client] = useState(() => 
//     AgoraRTC.createClient({ mode: "rtc", codec: "vp8" })
//   );
//   const [localTracks, setLocalTracks] = useState([]);
//   const [remoteUsers, setRemoteUsers] = useState([]);
//   const [joined, setJoined] = useState(false);
//   const [callStatus, setCallStatus] = useState("connecting");
//   const [isMuted, setIsMuted] = useState(false);
//   const [isCameraOff, setIsCameraOff] = useState(false);

//   useEffect(() => {
//     let mounted = true;

//     async function joinChannel() {
//       try {
//         setCallStatus("connecting");
//         console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);

//         // Initialize client events first
//         setupClientEvents();

//         // Join the channel
//         await client.join(APP_ID, safeChannel, null, uid);
        
//         if (!mounted) return;
//         setJoined(true);
//         setCallStatus("connected");

//         // Create and publish tracks for ALL users (both doctor and patient)
//         try {
//           const audioTrack = await AgoraRTC.createMicrophoneAudioTrack({
//             encoderConfig: "music_standard",
//           });
//           const videoTrack = await AgoraRTC.createCameraVideoTrack({
//             encoderConfig: "720p_1",
//           });
          
//           if (!mounted) return;

//           setLocalTracks([audioTrack, videoTrack]);
          
//           // Play local video
//           if (localVideoRef.current) {
//             videoTrack.play(localVideoRef.current);
//           }
          
//           // Publish tracks for ALL users
//           await client.publish([audioTrack, videoTrack]);
//           console.log("✅ Published local tracks");
          
//         } catch (error) {
//           console.error("❌ Error creating local tracks:", error);
//           // If user denies camera/mic access, continue without tracks
//           setCallStatus("connected");
//         }

//       } catch (error) {
//         console.error("❌ Join channel error:", error);
//         setCallStatus("error");
//       }
//     }

//     function setupClientEvents() {
//       // Handle remote users - FIXED VERSION
//       client.on("user-published", async (user, mediaType) => {
//         try {
//           console.log(`📡 User ${user.uid} published ${mediaType}`);
          
//           await client.subscribe(user, mediaType);
//           console.log(`✅ Subscribed to user ${user.uid} ${mediaType}`);

//           if (mediaType === "video") {
//             // Create a new video container for each remote user
//             const remoteVideoContainer = document.createElement("div");
//             remoteVideoContainer.id = `remote-video-${user.uid}`;
//             remoteVideoContainer.style.width = "100%";
//             remoteVideoContainer.style.height = "100%";
            
//             if (remoteVideoRef.current) {
//               remoteVideoRef.current.innerHTML = "";
//               remoteVideoRef.current.appendChild(remoteVideoContainer);
              
//               user.videoTrack.play(remoteVideoContainer);
//               console.log(`🎥 Playing remote video for user ${user.uid}`);
//             }
            
//             setRemoteUsers(prev => {
//               const exists = prev.some(u => u.uid === user.uid);
//               if (!exists) {
//                 return [...prev, user];
//               }
//               return prev;
//             });
//           }
          
//           if (mediaType === "audio") {
//             user.audioTrack.play();
//             console.log(`🔊 Playing remote audio for user ${user.uid}`);
//           }
//         } catch (error) {
//           console.error("❌ Error subscribing to user:", error);
//         }
//       });

//       client.on("user-unpublished", (user, mediaType) => {
//         console.log(`📡 User ${user.uid} unpublished ${mediaType}`);
//         if (mediaType === "video") {
//           // Remove the video element
//           const videoElement = document.getElementById(`remote-video-${user.uid}`);
//           if (videoElement) {
//             videoElement.remove();
//           }
//           setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
//         }
//       });

//       client.on("user-left", (user) => {
//         console.log(`👋 User ${user.uid} left the channel`);
//         // Remove the video element
//         const videoElement = document.getElementById(`remote-video-${user.uid}`);
//         if (videoElement) {
//           videoElement.remove();
//         }
//         setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
//       });

//       client.on("user-joined", (user) => {
//         console.log(`🎉 User ${user.uid} joined the channel`);
//       });

//       client.on("user-failed", (user) => {
//         console.log(`❌ User ${user.uid} failed to join`);
//       });
//     }

//     joinChannel();

//     // Cleanup on unmount
//     return () => {
//       mounted = false;
//       cleanup();
//     };
//   }, [client, safeChannel, role, uid]);

//   const cleanup = async () => {
//     try {
//       // Close local tracks
//       localTracks.forEach(track => {
//         track.stop();
//         track.close();
//       });
      
//       // Leave channel
//       if (joined) {
//         await client.leave();
//         console.log("🚪 Left the channel");
//       }
      
//       setLocalTracks([]);
//       setRemoteUsers([]);
//       setJoined(false);
//     } catch (error) {
//       console.error("❌ Cleanup error:", error);
//     }
//   };

//   const toggleMute = async () => {
//     if (localTracks[0]) {
//       const audioTrack = localTracks[0];
//       await audioTrack.setEnabled(isMuted);
//       setIsMuted(!isMuted);
//       console.log(isMuted ? "🎤 Unmuted" : "🔇 Muted");
//     }
//   };

//   const toggleCamera = async () => {
//     if (localTracks[1]) {
//       const videoTrack = localTracks[1];
//       await videoTrack.setEnabled(isCameraOff);
//       setIsCameraOff(!isCameraOff);
//       console.log(isCameraOff ? "📷 Camera On" : "📷 Camera Off");
//     }
//   };

//   const handleEndCall = async () => {
//     await cleanup();
    
//     // Notify server about call end
//     socket.emit("call-ended", { channel: safeChannel });
    
//     // Navigate back
//     if (isHost) {
//       navigate("/doctor-dashboard");
//     } else {
//       navigate("/patient-dashboard");
//     }
//   };

//   const getStatusColor = () => {
//     switch (callStatus) {
//       case "connected": return "#16a34a";
//       case "connecting": return "#f59e0b";
//       case "error": return "#dc2626";
//       default: return "#6b7280";
//     }
//   };

//   return (
//     <div style={{ padding: 20, maxWidth: 1200, margin: "0 auto" }}>
//       {/* Header */}
//       <div style={{ marginBottom: 20 }}>
//         <h2>Video Call</h2>
//         <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
//           <span>UID: <strong>{uid}</strong></span>
//           <span>Channel: <strong>{safeChannel}</strong></span>
//           <span>Role: <strong>{role}</strong></span>
//           <span style={{
//             padding: "4px 8px",
//             borderRadius: 12,
//             fontSize: 12,
//             fontWeight: "bold",
//             background: callStatus === "connected" ? "#dcfce7" : "#fef3c7",
//             color: getStatusColor()
//           }}>
//             {callStatus.toUpperCase()}
//           </span>
//         </div>
//       </div>

//       {/* Video Grid */}
//       <div style={{ 
//         display: "grid", 
//         gridTemplateColumns: "1fr 1fr",
//         gap: 20, 
//         marginBottom: 20,
//         minHeight: 300
//       }}>
//         {/* Local Video */}
//         <div style={{ position: "relative", border: "2px solid #4ade80", borderRadius: 12 }}>
//           <div
//             ref={localVideoRef}
//             style={{
//               width: "100%",
//               height: 300,
//               background: "#000",
//               borderRadius: 10,
//               overflow: "hidden"
//             }}
//           />
//           <div style={{
//             position: "absolute",
//             bottom: 8,
//             left: 8,
//             background: "rgba(0,0,0,0.7)",
//             color: "white",
//             padding: "4px 8px",
//             borderRadius: 4,
//             fontSize: 12
//           }}>
//             You ({isHost ? "Doctor" : "Patient"})
//           </div>
//           {isCameraOff && (
//             <div style={{
//               position: "absolute",
//               top: "50%",
//               left: "50%",
//               transform: "translate(-50%, -50%)",
//               color: "white",
//               fontSize: 18,
//               background: "rgba(0,0,0,0.5)",
//               padding: 8,
//               borderRadius: 8
//             }}>
//               📷 Camera Off
//             </div>
//           )}
//         </div>

//         {/* Remote Video */}
//         <div style={{ position: "relative", border: "2px solid #f87171", borderRadius: 12 }}>
//           <div
//             ref={remoteVideoRef}
//             style={{
//               width: "100%",
//               height: 300,
//               background: "#000",
//               borderRadius: 10,
//               overflow: "hidden"
//             }}
//           />
//           <div style={{
//             position: "absolute",
//             bottom: 8,
//             left: 8,
//             background: "rgba(0,0,0,0.7)",
//             color: "white",
//             padding: "4px 8px",
//             borderRadius: 4,
//             fontSize: 12
//           }}>
//             {remoteUsers.length > 0 
//               ? `${isHost ? "Patient" : "Doctor"} (${remoteUsers[0]?.uid})`
//               : "Waiting for other participant..."
//             }
//           </div>
//           {remoteUsers.length === 0 && (
//             <div style={{
//               position: "absolute",
//               top: "50%",
//               left: "50%",
//               transform: "translate(-50%, -50%)",
//               color: "white",
//               textAlign: "center",
//               background: "rgba(0,0,0,0.5)",
//               padding: 16,
//               borderRadius: 8
//             }}>
//               <div style={{ fontSize: 48, marginBottom: 8 }}>⏳</div>
//               <div>Waiting for {isHost ? "patient" : "doctor"} to join...</div>
//             </div>
//           )}
//         </div>
//       </div>

//       {/* Debug Info */}
//       <div style={{ 
//         marginBottom: 20, 
//         padding: 12, 
//         background: "#f3f4f6", 
//         borderRadius: 8,
//         fontSize: 14 
//       }}>
//         <div><strong>Debug Info:</strong></div>
//         <div>Remote Users: {remoteUsers.length}</div>
//         <div>Local Tracks: {localTracks.length}</div>
//         <div>Joined: {joined ? "Yes" : "No"}</div>
//       </div>

//       {/* Controls */}
//       {joined && (
//         <div style={{
//           display: "flex",
//           justifyContent: "center",
//           gap: 12,
//           padding: 16,
//           background: "#f9fafb",
//           borderRadius: 12
//         }}>
//           <button
//             onClick={toggleMute}
//             style={{
//               padding: "12px 16px",
//               borderRadius: 8,
//               background: isMuted ? "#dc2626" : "#6b7280",
//               color: "white",
//               border: "none",
//               cursor: "pointer",
//               fontWeight: "bold",
//               minWidth: 120
//             }}
//           >
//             {isMuted ? "🔇 Unmute" : "🎤 Mute"}
//           </button>
          
//           <button
//             onClick={toggleCamera}
//             style={{
//               padding: "12px 16px",
//               borderRadius: 8,
//               background: isCameraOff ? "#dc2626" : "#6b7280",
//               color: "white",
//               border: "none",
//               cursor: "pointer",
//               fontWeight: "bold",
//               minWidth: 120
//             }}
//           >
//             {isCameraOff ? "📷 Camera On" : "📹 Camera Off"}
//           </button>
          
//           <button
//             onClick={handleEndCall}
//             style={{
//               padding: "12px 16px",
//               borderRadius: 8,
//               background: "#dc2626",
//               color: "white",
//               border: "none",
//               cursor: "pointer",
//               fontWeight: "bold",
//               minWidth: 120
//             }}
//           >
//             📞 End Call
//           </button>
//         </div>
//       )}
//     </div>
//   );
// }
import React, { useEffect, useMemo, useRef, useState } from "react";
import { useParams, useSearchParams, useNavigate } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";
import { socket } from "./socket";

const APP_ID = "e20a4d60afd8494eab490563ad2e61d1";

export default function CallPage() {
  const { channelName } = useParams();
  const [qs] = useSearchParams();
  const navigate = useNavigate();

  // Sanitize channel name
  const safeChannel = useMemo(() => {
    return (channelName || "default_channel")
      .replace(/[^a-zA-Z0-9_]/g, "")
      .slice(0, 63);
  }, [channelName]);

  // Get UID and role from URL params
  const uid = useMemo(() => {
    const q = Number(qs.get("uid"));
    return Number.isFinite(q) ? q : Math.floor(Math.random() * 1e6);
  }, [qs]);

  const role = (qs.get("role") || "audience").toLowerCase();
  const isHost = role === "host";

  // Refs for video elements
  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);

  // State
  const [client] = useState(() => 
    AgoraRTC.createClient({ mode: "rtc", codec: "vp8" })
  );
  const [localTracks, setLocalTracks] = useState({});
  const [remoteUsers, setRemoteUsers] = useState(new Map());
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("connecting");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);
  const [isInitializing, setIsInitializing] = useState(true);

  useEffect(() => {
    let mounted = true;
    
    const initializeCall = async () => {
      try {
        console.log(`🚀 Initializing call - Channel: ${safeChannel}, Role: ${role}, UID: ${uid}`);
        
        // Setup client events first
        setupClientEvents();
        
        // Join the channel
        await client.join(APP_ID, safeChannel, null, uid);
        
        if (!mounted) return;
        
        setJoined(true);
        setCallStatus("connected");
        
        // Create local tracks for ALL users (both host and audience)
        await createAndPublishTracks();
        
        setIsInitializing(false);
        
      } catch (error) {
        console.error("❌ Failed to initialize call:", error);
        setCallStatus("error");
        setIsInitializing(false);
      }
    };

    const createAndPublishTracks = async () => {
      try {
        console.log("📹 Creating local tracks...");
        
        // Create audio and video tracks
        const [audioTrack, videoTrack] = await Promise.all([
          AgoraRTC.createMicrophoneAudioTrack({
            encoderConfig: "music_standard"
          }),
          AgoraRTC.createCameraVideoTrack({
            encoderConfig: "720p_1"
          })
        ]);
        
        if (!mounted) {
          audioTrack.close();
          videoTrack.close();
          return;
        }
        
        setLocalTracks({ audio: audioTrack, video: videoTrack });
        
        // Play local video immediately
        if (localVideoRef.current) {
          videoTrack.play(localVideoRef.current);
          console.log("🎥 Local video playing");
        }
        
        // Publish tracks - IMPORTANT: Both host and audience must publish
        await client.publish([audioTrack, videoTrack]);
        console.log("✅ Published local tracks successfully");
        
      } catch (error) {
        console.error("❌ Error creating/publishing tracks:", error);
        setCallStatus("error");
        
        // Try to continue without media (graceful degradation)
        try {
          await client.publish([]);
        } catch (publishError) {
          console.error("❌ Failed to publish empty tracks:", publishError);
        }
      }
    };

    const setupClientEvents = () => {
      // Handle when a remote user publishes media
      client.on("user-published", async (user, mediaType) => {
        try {
          console.log(`📡 User ${user.uid} published ${mediaType}`);
          
          // Subscribe to the user's media
          await client.subscribe(user, mediaType);
          console.log(`✅ Subscribed to user ${user.uid} ${mediaType}`);
          
          if (mediaType === "video") {
            // Update remote users state
            setRemoteUsers(prev => {
              const newMap = new Map(prev);
              newMap.set(user.uid, { ...user, hasVideo: true });
              return newMap;
            });
            
            // Play the remote video
            if (remoteVideoRef.current && user.videoTrack) {
              // Clear previous content
              remoteVideoRef.current.innerHTML = '';
              user.videoTrack.play(remoteVideoRef.current);
              console.log(`🎥 Playing remote video for user ${user.uid}`);
            }
          }
          
          if (mediaType === "audio" && user.audioTrack) {
            user.audioTrack.play();
            console.log(`🔊 Playing remote audio for user ${user.uid}`);
            
            // Update remote users state
            setRemoteUsers(prev => {
              const newMap = new Map(prev);
              const existingUser = newMap.get(user.uid) || {};
              newMap.set(user.uid, { ...existingUser, ...user, hasAudio: true });
              return newMap;
            });
          }
          
        } catch (error) {
          console.error(`❌ Failed to subscribe to user ${user.uid}:`, error);
        }
      });

      // Handle when a remote user unpublishes media
      client.on("user-unpublished", (user, mediaType) => {
        console.log(`📡 User ${user.uid} unpublished ${mediaType}`);
        
        if (mediaType === "video") {
          setRemoteUsers(prev => {
            const newMap = new Map(prev);
            const existingUser = newMap.get(user.uid);
            if (existingUser) {
              newMap.set(user.uid, { ...existingUser, hasVideo: false });
            }
            return newMap;
          });
          
          // Clear remote video
          if (remoteVideoRef.current) {
            remoteVideoRef.current.innerHTML = '';
          }
        }
      });

      // Handle when a remote user leaves
      client.on("user-left", (user) => {
        console.log(`👋 User ${user.uid} left the channel`);
        setRemoteUsers(prev => {
          const newMap = new Map(prev);
          newMap.delete(user.uid);
          return newMap;
        });
        
        // Clear remote video if this was the active user
        if (remoteVideoRef.current) {
          remoteVideoRef.current.innerHTML = '';
        }
      });

      // Handle when a remote user joins (but hasn't published yet)
      client.on("user-joined", (user) => {
        console.log(`🎉 User ${user.uid} joined the channel`);
        setRemoteUsers(prev => {
          const newMap = new Map(prev);
          newMap.set(user.uid, { uid: user.uid, hasVideo: false, hasAudio: false });
          return newMap;
        });
      });
    };

    initializeCall();

    // Cleanup function
    return () => {
      mounted = false;
      cleanup();
    };
  }, [client, safeChannel, uid]);

  const cleanup = async () => {
    try {
      console.log("🧹 Cleaning up call resources...");
      
      // Close local tracks
      if (localTracks.audio) {
        localTracks.audio.stop();
        localTracks.audio.close();
      }
      if (localTracks.video) {
        localTracks.video.stop();
        localTracks.video.close();
      }
      
      // Leave channel
      if (joined && client) {
        await client.leave();
        console.log("🚪 Left the channel");
      }
      
      setLocalTracks({});
      setRemoteUsers(new Map());
      setJoined(false);
      
    } catch (error) {
      console.error("❌ Cleanup error:", error);
    }
  };

  const toggleMute = async () => {
    if (localTracks.audio) {
      await localTracks.audio.setEnabled(isMuted);
      setIsMuted(!isMuted);
      console.log(isMuted ? "🎤 Unmuted" : "🔇 Muted");
    }
  };

  const toggleCamera = async () => {
    if (localTracks.video) {
      await localTracks.video.setEnabled(isCameraOff);
      setIsCameraOff(!isCameraOff);
      console.log(isCameraOff ? "📷 Camera On" : "📷 Camera Off");
    }
  };

  const handleEndCall = async () => {
    await cleanup();
    
    // Notify server about call end
    socket.emit("call-ended", { channel: safeChannel });
    
    // Navigate back
    if (isHost) {
      navigate("/doctor-dashboard");
    } else {
      navigate("/patient-dashboard");
    }
  };

  const remoteUsersArray = Array.from(remoteUsers.values());
  const hasRemoteUser = remoteUsersArray.length > 0 && remoteUsersArray.some(user => user.hasVideo);

  if (isInitializing) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <h2 className="text-xl font-semibold text-gray-900">Initializing Call...</h2>
          <p className="text-gray-600">Please wait while we set up your video call</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-4 mb-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Video Call</h1>
              <div className="flex items-center gap-4 mt-2 text-sm text-gray-600">
                <span>UID: <span className="font-semibold">{uid}</span></span>
                <span>Channel: <span className="font-semibold">{safeChannel}</span></span>
                <span>Role: <span className="font-semibold capitalize">{role}</span></span>
              </div>
            </div>
            <div className={`px-3 py-1 rounded-full text-sm font-semibold ${
              callStatus === "connected" 
                ? "bg-green-100 text-green-800" 
                : callStatus === "connecting"
                ? "bg-yellow-100 text-yellow-800"
                : "bg-red-100 text-red-800"
            }`}>
              {callStatus.toUpperCase()}
            </div>
          </div>
        </div>

        {/* Video Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          {/* Local Video */}
          <div className="relative bg-black rounded-xl overflow-hidden shadow-lg border-4 border-green-400">
            <div
              ref={localVideoRef}
              className="w-full aspect-video bg-black"
              style={{ minHeight: '300px' }}
            />
            <div className="absolute bottom-3 left-3 bg-black bg-opacity-70 text-white px-3 py-1 rounded-lg text-sm font-medium">
              You ({isHost ? "Doctor" : "Patient"})
            </div>
            {isCameraOff && (
              <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div className="text-white text-center">
                  <div className="text-4xl mb-2">📷</div>
                  <div className="text-lg font-medium">Camera Off</div>
                </div>
              </div>
            )}
          </div>

          {/* Remote Video */}
          <div className="relative bg-black rounded-xl overflow-hidden shadow-lg border-4 border-blue-400">
            <div
              ref={remoteVideoRef}
              className="w-full aspect-video bg-black"
              style={{ minHeight: '300px' }}
            />
            <div className="absolute bottom-3 left-3 bg-black bg-opacity-70 text-white px-3 py-1 rounded-lg text-sm font-medium">
              {hasRemoteUser 
                ? `${isHost ? "Patient" : "Doctor"} (${remoteUsersArray[0]?.uid})`
                : "Waiting for participant..."
              }
            </div>
            {!hasRemoteUser && (
              <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div className="text-white text-center">
                  <div className="animate-pulse text-6xl mb-4">⏳</div>
                  <div className="text-xl font-medium mb-2">
                    Waiting for {isHost ? "patient" : "doctor"}
                  </div>
                  <div className="text-gray-300">
                    They will appear here once they join
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Debug Info */}
        <div className="bg-white rounded-lg shadow-sm p-4 mb-6">
          <h3 className="font-semibold text-gray-900 mb-2">Debug Information</h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <span className="text-gray-600">Remote Users:</span>
              <span className="ml-2 font-semibold">{remoteUsersArray.length}</span>
            </div>
            <div>
              <span className="text-gray-600">Local Tracks:</span>
              <span className="ml-2 font-semibold">{Object.keys(localTracks).length}</span>
            </div>
            <div>
              <span className="text-gray-600">Joined:</span>
              <span className="ml-2 font-semibold">{joined ? "Yes" : "No"}</span>
            </div>
            <div>
              <span className="text-gray-600">Has Remote Video:</span>
              <span className="ml-2 font-semibold">{hasRemoteUser ? "Yes" : "No"}</span>
            </div>
          </div>
        </div>

        {/* Controls */}
        {joined && (
          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-center gap-4">
              <button
                onClick={toggleMute}
                className={`px-6 py-3 rounded-xl font-semibold text-white transition-colors min-w-[140px] ${
                  isMuted 
                    ? "bg-red-500 hover:bg-red-600" 
                    : "bg-gray-600 hover:bg-gray-700"
                }`}
              >
                {isMuted ? "🔇 Unmute" : "🎤 Mute"}
              </button>
              
              <button
                onClick={toggleCamera}
                className={`px-6 py-3 rounded-xl font-semibold text-white transition-colors min-w-[140px] ${
                  isCameraOff 
                    ? "bg-red-500 hover:bg-red-600" 
                    : "bg-gray-600 hover:bg-gray-700"
                }`}
              >
                {isCameraOff ? "📷 Camera On" : "📹 Camera Off"}
              </button>
              
              <button
                onClick={handleEndCall}
                className="px-6 py-3 rounded-xl bg-red-500 hover:bg-red-600 text-white font-semibold transition-colors min-w-[140px]"
              >
                📞 End Call
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}