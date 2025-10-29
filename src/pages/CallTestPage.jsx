import React, { useEffect, useRef, useState, useMemo } from "react";
import { useParams, useSearchParams, useNavigate } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";
import { socket } from "./socket";

const APP_ID = "e20a4d60afd8494eab490563ad2e61d1";

export default function CallPage() {
  const { channelName } = useParams();
  const [qs] = useSearchParams();
  const navigate = useNavigate();

  const safeChannel = useMemo(() => {
    return (channelName || "default_channel")
      .replace(/[^a-zA-Z0-9_]/g, "")
      .slice(0, 63);
  }, [channelName]);

  const uid = useMemo(() => {
    const q = Number(qs.get("uid"));
    return Number.isFinite(q) ? q : Math.floor(Math.random() * 1e6);
  }, [qs]);

  const role = qs.get("role") === "host" ? "host" : "audience";
  const isHost = role === "host";

  // Get additional parameters for navigation
  const doctorId = qs.get("doctorId");
  const patientId = qs.get("patientId");

  // Refs
  const clientRef = useRef(AgoraRTC.createClient({ mode: "rtc", codec: "vp8" }));
  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);

  // State
  const [localTracks, setLocalTracks] = useState([]);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("connecting");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);

  // Join channel effect
  useEffect(() => {
    let mounted = true;
    const client = clientRef.current;

    async function joinChannel() {
      try {
        if (client.connectionState === "CONNECTED" || client.connectionState === "CONNECTING") {
          console.log("Client already connected, leaving previous session...");
          await client.leave();
        }

        console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);
        await client.join(APP_ID, safeChannel, null, uid);

        setJoined(true);
        setCallStatus("connected");

        // Create tracks (host only)
        let audioTrack = null;
        let videoTrack = null;

        try {
          audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        } catch (err) {
          console.warn("Audio track error:", err);
        }

        try {
          videoTrack = await AgoraRTC.createCameraVideoTrack();
        } catch (err) {
          console.warn("Video track error:", err);
          setIsCameraOff(true);
        }

        // Publish whatever tracks are available
        const tracksToPublish = [];
        if (audioTrack) tracksToPublish.push(audioTrack);
        if (videoTrack) tracksToPublish.push(videoTrack);

        if (tracksToPublish.length) {
          await client.publish(tracksToPublish);
        }
        setLocalTracks(tracksToPublish);
        setCallStatus("connected");

        if (videoTrack && localVideoRef.current) {
          videoTrack.play(localVideoRef.current);
        }

        // Remote user handling
        client.on("user-published", async (user, mediaType) => {
          try {
            await client.subscribe(user, mediaType);
            console.log(`Subscribed to user ${user.uid} ${mediaType}`);

            if (mediaType === "video" && remoteVideoRef.current) {
              user.videoTrack?.play(remoteVideoRef.current);
            }
            if (mediaType === "audio") {
              user.audioTrack?.play();
            }

            setRemoteUsers(prev => {
              if (!prev.some(u => u.uid === user.uid)) {
                return [...prev, user];
              }
              return prev;
            });
          } catch (err) {
            console.error("Error subscribing to user:", err);
          }
        });

        client.on("user-unpublished", (user, mediaType) => {
          if (mediaType === "video") {
            setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
          }
        });

        client.on("user-left", (user) => {
          setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
        });

      } catch (error) {
        console.error("Join channel error:", error);
        setCallStatus("error");
      }
    }

    joinChannel();

    return () => {
      mounted = false;
      cleanup();
    };
  }, [safeChannel, role, uid]);

  const cleanup = async () => {
    const client = clientRef.current;
    try {
      localTracks.forEach(track => {
        track.stop();
        track.close();
      });

      if (client.connectionState === "CONNECTED") {
        await client.leave();
        console.log("Left the channel");
      }

      setLocalTracks([]);
      setRemoteUsers([]);
      setJoined(false);
    } catch (error) {
      console.error("Cleanup error:", error);
    }
  };

  const toggleMute = async () => {
    if (localTracks[0]) {
      const audioTrack = localTracks[0];
      await audioTrack.setEnabled(isMuted);
      setIsMuted(!isMuted);
    }
  };

  const toggleCamera = async () => {
    if (localTracks[1]) {
      const videoTrack = localTracks[1];
      await videoTrack.setEnabled(isCameraOff);
      setIsCameraOff(!isCameraOff);
    }
  };

  const handleEndCall = async () => {
    await cleanup();
    socket.emit("call-ended", { channel: safeChannel });
    
    // Navigate based on role
    if (isHost && doctorId && patientId) {
      // Doctor navigates to prescription page
      navigate(`/prescription/${doctorId}/${patientId}`);
    } else if (!isHost && doctorId && patientId) {
      // Patient navigates to rating page
      navigate(`/rating/${doctorId}/${patientId}`);
    } else {
      // Fallback navigation
      navigate(isHost ? "/doctor-dashboard" : "/patient-dashboard");
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

  const getStatusDisplay = () => {
    switch (callStatus) {
      case "connected": return "Connected";
      case "connecting": return "Connecting...";
      case "error": return "Connection Error";
      default: return "Disconnected";
    }
  };

  return (
    <div style={styles.container}>
      {/* Header */}
      <div style={styles.header}>
        <h1 style={styles.title}>Video Consultation</h1>
        <div style={styles.infoContainer}>
          <div style={styles.infoItem}>
            <span style={styles.infoLabel}>Your ID:</span>
            <strong style={styles.infoValue}>{uid}</strong>
          </div>
          <div style={styles.infoItem}>
            <span style={styles.infoLabel}>Room:</span>
            <strong style={styles.infoValue}>{safeChannel}</strong>
          </div>
          <div style={styles.infoItem}>
            <span style={styles.infoLabel}>Role:</span>
            <strong style={styles.infoValue}>{isHost ? "Doctor" : "Patient"}</strong>
          </div>
          <div style={{
            ...styles.statusBadge,
            background: callStatus === "connected" ? "#dcfce7" : 
                       callStatus === "connecting" ? "#fef3c7" : "#fee2e2",
            color: getStatusColor()
          }}>
            {getStatusDisplay()}
          </div>
        </div>
      </div>

      {/* Video Grid */}
      <div style={styles.videoGrid}>
        {/* Local Video */}
        <div style={styles.videoContainer}>
          <div 
            ref={localVideoRef} 
            style={{
              ...styles.videoElement,
              background: isCameraOff ? "#374151" : "#000"
            }} 
          />
          <div style={styles.videoLabel}>
            You ({isHost ? "Doctor" : "Patient"})
            {isCameraOff && " - Camera Off"}
          </div>
          {isCameraOff && (
            <div style={styles.cameraOffOverlay}>
              <div style={styles.cameraOffIcon}>📷</div>
              <div style={styles.cameraOffText}>Camera is off</div>
            </div>
          )}
        </div>

        {/* Remote Video */}
        <div style={styles.videoContainer}>
          <div 
            ref={remoteVideoRef} 
            style={{
              ...styles.videoElement,
              background: remoteUsers.length === 0 ? "#374151" : "#000"
            }} 
          />
          <div style={styles.videoLabel}>
            {remoteUsers.length > 0 
              ? `${isHost ? "Patient" : "Doctor"} (${remoteUsers[0]?.uid})`
              : "Waiting for participant..."
            }
          </div>
          {remoteUsers.length === 0 && (
            <div style={styles.waitingOverlay}>
              <div style={styles.waitingIcon}>⏳</div>
              <div style={styles.waitingText}>
                Waiting for {isHost ? "patient" : "doctor"} to join...
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Controls */}
      {joined && (
        <div style={styles.controlsContainer}>
          <button 
            onClick={toggleMute} 
            style={{
              ...styles.controlButton,
              background: isMuted ? "#dc2626" : "#4b5563"
            }}
          >
            <span style={styles.buttonIcon}>
              {isMuted ? "🔇" : "🎤"}
            </span>
            <span style={styles.buttonText}>
              {isMuted ? "Unmute" : "Mute"}
            </span>
          </button>
          
          <button 
            onClick={toggleCamera} 
            style={{
              ...styles.controlButton,
              background: isCameraOff ? "#dc2626" : "#4b5563"
            }}
          >
            <span style={styles.buttonIcon}>
              {isCameraOff ? "📷" : "📹"}
            </span>
            <span style={styles.buttonText}>
              {isCameraOff ? "Camera On" : "Camera Off"}
            </span>
          </button>
          
          <button 
            onClick={handleEndCall} 
            style={styles.endCallButton}
          >
            <span style={styles.buttonIcon}>📞</span>
            <span style={styles.buttonText}>End Call</span>
          </button>
        </div>
      )}
    </div>
  );
}

// Styles
const styles = {
  container: {
    padding: "20px",
    maxWidth: "1200px",
    margin: "0 auto",
    minHeight: "100vh",
    background: "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
  },
  header: {
    marginBottom: "30px",
    background: "rgba(255, 255, 255, 0.95)",
    padding: "20px",
    borderRadius: "16px",
    boxShadow: "0 4px 6px rgba(0, 0, 0, 0.1)",
  },
  title: {
    margin: "0 0 16px 0",
    color: "#1f2937",
    fontSize: "28px",
    fontWeight: "bold",
    textAlign: "center",
  },
  infoContainer: {
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    gap: "24px",
    flexWrap: "wrap",
  },
  infoItem: {
    display: "flex",
    alignItems: "center",
    gap: "8px",
  },
  infoLabel: {
    color: "#6b7280",
    fontSize: "14px",
  },
  infoValue: {
    color: "#1f2937",
    fontSize: "14px",
  },
  statusBadge: {
    padding: "6px 12px",
    borderRadius: "20px",
    fontSize: "12px",
    fontWeight: "bold",
    textTransform: "uppercase",
    letterSpacing: "0.5px",
  },
  videoGrid: {
    display: "grid",
    gridTemplateColumns: "1fr 1fr",
    gap: "24px",
    marginBottom: "30px",
  },
  videoContainer: {
    position: "relative",
    borderRadius: "16px",
    overflow: "hidden",
    boxShadow: "0 8px 25px rgba(0, 0, 0, 0.3)",
    background: "#000",
  },
  videoElement: {
    width: "100%",
    height: "400px",
    objectFit: "cover",
  },
  videoLabel: {
    position: "absolute",
    bottom: "12px",
    left: "12px",
    background: "rgba(0, 0, 0, 0.8)",
    color: "white",
    padding: "6px 12px",
    borderRadius: "8px",
    fontSize: "14px",
    fontWeight: "500",
  },
  cameraOffOverlay: {
    position: "absolute",
    top: "50%",
    left: "50%",
    transform: "translate(-50%, -50%)",
    color: "white",
    textAlign: "center",
    background: "rgba(0, 0, 0, 0.7)",
    padding: "20px",
    borderRadius: "12px",
  },
  cameraOffIcon: {
    fontSize: "48px",
    marginBottom: "8px",
  },
  cameraOffText: {
    fontSize: "16px",
    fontWeight: "500",
  },
  waitingOverlay: {
    position: "absolute",
    top: "50%",
    left: "50%",
    transform: "translate(-50%, -50%)",
    color: "white",
    textAlign: "center",
  },
  waitingIcon: {
    fontSize: "64px",
    marginBottom: "16px",
    opacity: 0.7,
  },
  waitingText: {
    fontSize: "18px",
    fontWeight: "500",
    opacity: 0.9,
  },
  controlsContainer: {
    display: "flex",
    justifyContent: "center",
    gap: "16px",
    padding: "24px",
    background: "rgba(255, 255, 255, 0.95)",
    borderRadius: "16px",
    boxShadow: "0 4px 6px rgba(0, 0, 0, 0.1)",
  },
  controlButton: {
    padding: "16px 24px",
    borderRadius: "12px",
    color: "white",
    border: "none",
    cursor: "pointer",
    fontWeight: "bold",
    minWidth: "140px",
    display: "flex",
    flexDirection: "column",
    alignItems: "center",
    gap: "8px",
    transition: "all 0.2s ease",
    fontSize: "14px",
  },
  endCallButton: {
    padding: "16px 24px",
    borderRadius: "12px",
    background: "#dc2626",
    color: "white",
    border: "none",
    cursor: "pointer",
    fontWeight: "bold",
    minWidth: "140px",
    display: "flex",
    flexDirection: "column",
    alignItems: "center",
    gap: "8px",
    transition: "all 0.2s ease",
    fontSize: "14px",
  },
  buttonIcon: {
    fontSize: "20px",
  },
  buttonText: {
    fontSize: "14px",
    fontWeight: "600",
  },
};

// Make buttons hoverable
styles.controlButton = {
  ...styles.controlButton,
  ':hover': {
    transform: "translateY(-2px)",
    boxShadow: "0 4px 12px rgba(0, 0, 0, 0.2)",
  }
};

styles.endCallButton = {
  ...styles.endCallButton,
  ':hover': {
    transform: "translateY(-2px)",
    boxShadow: "0 4px 12px rgba(220, 38, 38, 0.4)",
    background: "#b91c1c",
  }
};