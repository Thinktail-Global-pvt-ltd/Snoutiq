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


import React, { useEffect, useMemo, useRef, useState } from "react";
import { useParams, useSearchParams, useNavigate } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";
import { socket } from "./socket";

const APP_ID = "e20a4d60afd8494eab490563ad2e61d1"; // Replace with your Agora App ID

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
  const isHost = role === "host"; // Doctor is host

  // Refs for video elements
  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);

  // State
  const [client] = useState(() => 
    AgoraRTC.createClient({ mode: "rtc", codec: "vp8" })
  );
  const [localTracks, setLocalTracks] = useState([]);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("connecting");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);

  useEffect(() => {
    let mounted = true;

    async function joinChannel() {
      try {
        setCallStatus("connecting");
        console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);

        // Join the channel
        await client.join(APP_ID, safeChannel, null, uid);
        
        if (!mounted) return;
        setJoined(true);
        setCallStatus("connected");

        // Create and publish tracks for host (doctor)
        if (isHost) {
          try {
            const audioTrack = await AgoraRTC.createMicrophoneAudioTrack({
              encoderConfig: "music_standard",
            });
            const videoTrack = await AgoraRTC.createCameraVideoTrack({
              encoderConfig: "720p_1",
            });
            
            if (!mounted) return;

            setLocalTracks([audioTrack, videoTrack]);
            
            // Play local video
            if (localVideoRef.current) {
              videoTrack.play(localVideoRef.current);
            }
            
            // Publish tracks
            await client.publish([audioTrack, videoTrack]);
            console.log("✅ Published local tracks");
            
          } catch (error) {
            console.error("❌ Error creating local tracks:", error);
            setCallStatus("error");
          }
        }

        // Handle remote users
        client.on("user-published", async (user, mediaType) => {
          try {
            await client.subscribe(user, mediaType);
            console.log(`📡 Subscribed to user ${user.uid} ${mediaType}`);

            if (mediaType === "video" && remoteVideoRef.current) {
              user.videoTrack?.play(remoteVideoRef.current);
              setRemoteUsers(prev => {
                const updated = prev.filter(u => u.uid !== user.uid);
                return [...updated, user];
              });
            }
            
            if (mediaType === "audio") {
              user.audioTrack?.play();
            }
          } catch (error) {
            console.error("❌ Error subscribing to user:", error);
          }
        });

        client.on("user-unpublished", (user, mediaType) => {
          console.log(`📡 User ${user.uid} unpublished ${mediaType}`);
          if (mediaType === "video") {
            setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
          }
        });

        client.on("user-left", (user) => {
          console.log(`👋 User ${user.uid} left the channel`);
          setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
        });

      } catch (error) {
        console.error("❌ Join channel error:", error);
        setCallStatus("error");
      }
    }

    joinChannel();

    // Cleanup on unmount
    return () => {
      mounted = false;
      cleanup();
    };
  }, [client, safeChannel, role, uid, isHost]);

  const cleanup = async () => {
    try {
      // Close local tracks
      localTracks.forEach(track => {
        track.stop();
        track.close();
      });
      
      // Leave channel
      if (joined) {
        await client.leave();
        console.log("🚪 Left the channel");
      }
      
      setLocalTracks([]);
      setRemoteUsers([]);
      setJoined(false);
    } catch (error) {
      console.error("❌ Cleanup error:", error);
    }
  };

  const toggleMute = async () => {
    if (localTracks[0]) {
      const audioTrack = localTracks[0];
      await audioTrack.setEnabled(isMuted);
      setIsMuted(!isMuted);
      console.log(isMuted ? "🎤 Unmuted" : "🔇 Muted");
    }
  };

  const toggleCamera = async () => {
    if (localTracks[1]) {
      const videoTrack = localTracks[1];
      await videoTrack.setEnabled(isCameraOff);
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

  const getStatusColor = () => {
    switch (callStatus) {
      case "connected": return "#16a34a";
      case "connecting": return "#f59e0b";
      case "error": return "#dc2626";
      default: return "#6b7280";
    }
  };

  return (
    <div style={{ padding: 20, maxWidth: 1200, margin: "0 auto" }}>
      {/* Header */}
      <div style={{ marginBottom: 20 }}>
        <h2>Video Call</h2>
        <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
          <span>UID: <strong>{uid}</strong></span>
          <span>Channel: <strong>{safeChannel}</strong></span>
          <span>Role: <strong>{role}</strong></span>
          <span style={{
            padding: "4px 8px",
            borderRadius: 12,
            fontSize: 12,
            fontWeight: "bold",
            background: callStatus === "connected" ? "#dcfce7" : "#fef3c7",
            color: getStatusColor()
          }}>
            {callStatus.toUpperCase()}
          </span>
        </div>
      </div>

      {/* Video Grid */}
      <div style={{ 
        display: "grid", 
        gridTemplateColumns: remoteUsers.length > 0 ? "1fr 1fr" : "1fr",
        gap: 20, 
        marginBottom: 20 
      }}>
        {/* Local Video (Doctor only) */}
        {isHost && (
          <div style={{ position: "relative" }}>
            <div
              ref={localVideoRef}
              style={{
                width: "100%",
                height: 300,
                background: "#000",
                borderRadius: 12,
                overflow: "hidden"
              }}
            />
            <div style={{
              position: "absolute",
              bottom: 8,
              left: 8,
              background: "rgba(0,0,0,0.7)",
              color: "white",
              padding: "4px 8px",
              borderRadius: 4,
              fontSize: 12
            }}>
              You (Doctor)
            </div>
            {isCameraOff && (
              <div style={{
                position: "absolute",
                top: "50%",
                left: "50%",
                transform: "translate(-50%, -50%)",
                color: "white",
                fontSize: 18
              }}>
                📷 Camera Off
              </div>
            )}
          </div>
        )}

        {/* Remote Video */}
        <div style={{ position: "relative" }}>
          <div
            ref={remoteVideoRef}
            style={{
              width: "100%",
              height: 300,
              background: "#000",
              borderRadius: 12,
              overflow: "hidden"
            }}
          />
          <div style={{
            position: "absolute",
            bottom: 8,
            left: 8,
            background: "rgba(0,0,0,0.7)",
            color: "white",
            padding: "4px 8px",
            borderRadius: 4,
            fontSize: 12
          }}>
            {remoteUsers.length > 0 
              ? `${isHost ? "Patient" : "Doctor"} (${remoteUsers[0]?.uid})`
              : "Waiting for other participant..."
            }
          </div>
          {remoteUsers.length === 0 && (
            <div style={{
              position: "absolute",
              top: "50%",
              left: "50%",
              transform: "translate(-50%, -50%)",
              color: "white",
              textAlign: "center"
            }}>
              <div style={{ fontSize: 48, marginBottom: 8 }}>⏳</div>
              <div>Waiting for {isHost ? "patient" : "doctor"}...</div>
            </div>
          )}
        </div>
      </div>

      {/* Controls (Host/Doctor only) */}
      {joined && isHost && (
        <div style={{
          display: "flex",
          justifyContent: "center",
          gap: 12,
          padding: 16,
          background: "#f9fafb",
          borderRadius: 12
        }}>
          <button
            onClick={toggleMute}
            style={{
              padding: "12px 16px",
              borderRadius: 8,
              background: isMuted ? "#dc2626" : "#6b7280",
              color: "white",
              border: "none",
              cursor: "pointer",
              fontWeight: "bold",
              minWidth: 120
            }}
          >
            {isMuted ? "🔇 Unmute" : "🎤 Mute"}
          </button>
          
          <button
            onClick={toggleCamera}
            style={{
              padding: "12px 16px",
              borderRadius: 8,
              background: isCameraOff ? "#dc2626" : "#6b7280",
              color: "white",
              border: "none",
              cursor: "pointer",
              fontWeight: "bold",
              minWidth: 120
            }}
          >
            {isCameraOff ? "📷 Camera On" : "📹 Camera Off"}
          </button>
          
          <button
            onClick={handleEndCall}
            style={{
              marginTop: 12,
              padding: "8px 16px",
              borderRadius: 6,
              background: "#374151",
              color: "white",
              border: "none",
              cursor: "pointer"
            }}
          >
            Go Back
          </button>
        </div>
      )}
    </div>
  );
}
