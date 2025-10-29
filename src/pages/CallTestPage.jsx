import React, { useEffect, useRef, useState, useMemo } from "react";
import { useParams, useSearchParams, useNavigate, useLocation } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";
import { socket } from "./socket";
import logo from '../assets/images/logo.webp';
import toast from 'react-hot-toast';

const APP_ID = "e20a4d60afd8494eab490563ad2e61d1";

export default function CallPage() {
  const { channelName } = useParams();
  const [qs] = useSearchParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();

  // From navigation state
  const { doctorId: stateDoctorId, patientId: statePatientId, channel, callId } = location.state || {};

  // From query params (fallback)
  const doctorId = stateDoctorId || searchParams.get("doctorId");
  const patientId = statePatientId || searchParams.get("patientId");

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

  // Refs
  const clientRef = useRef(AgoraRTC.createClient({ mode: "rtc", codec: "vp8" }));
  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);
  const hasEndedRef = useRef(false);

  // State
  const [localTracks, setLocalTracks] = useState([]);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("connecting");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);
  const [cameras, setCameras] = useState([]);
  const [selectedCamera, setSelectedCamera] = useState("");
  const [showCameraModal, setShowCameraModal] = useState(false);
  const [showPermissionModal, setShowPermissionModal] = useState(true);
  const [permissions, setPermissions] = useState({
    camera: false,
    microphone: false
  });

  // Get available cameras
  const getCameras = async () => {
    try {
      const devices = await AgoraRTC.getDevices();
      const videoDevices = devices.filter(device => device.kind === 'videoinput');
      setCameras(videoDevices);
      
      if (videoDevices.length > 0) {
        setSelectedCamera(videoDevices[0].deviceId);
      }
      
      return videoDevices;
    } catch (error) {
      console.error("Error getting cameras:", error);
      return [];
    }
  };

  // Request permissions
  const requestPermissions = async () => {
    try {
      // Request camera permission
      const cameraStream = await navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
          setPermissions(prev => ({ ...prev, camera: true }));
          stream.getTracks().forEach(track => track.stop());
          return true;
        })
        .catch(() => false);

      // Request microphone permission
      const microphoneStream = await navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
          setPermissions(prev => ({ ...prev, microphone: true }));
          stream.getTracks().forEach(track => track.stop());
          return true;
        })
        .catch(() => false);

      if (cameraStream && microphoneStream) {
        setShowPermissionModal(false);
        toast.success("Permissions granted!");
      } else {
        toast.error("Please grant permissions to continue");
      }

      return { camera: cameraStream, microphone: microphoneStream };
    } catch (error) {
      console.error("Permission request error:", error);
      toast.error("Error requesting permissions");
      return { camera: false, microphone: false };
    }
  };

  // Switch camera
  const switchCamera = async (deviceId) => {
    if (!localTracks[1]) return;

    try {
      const videoTrack = localTracks[1];
      
      // Create new track with selected camera
      const newVideoTrack = await AgoraRTC.createCameraVideoTrack({ 
        cameraId: deviceId 
      });
      
      // Replace the old track with new one
      await clientRef.current.unpublish([videoTrack]);
      await clientRef.current.publish([newVideoTrack]);
      
      // Update local tracks
      const newLocalTracks = [localTracks[0], newVideoTrack];
      setLocalTracks(newLocalTracks);
      
      // Play new video track
      if (localVideoRef.current) {
        newVideoTrack.play(localVideoRef.current);
      }
      
      // Close old track
      videoTrack.close();
      
      setSelectedCamera(deviceId);
      setShowCameraModal(false);
      toast.success("Camera switched successfully");
    } catch (error) {
      console.error("Error switching camera:", error);
      toast.error("Failed to switch camera");
    }
  };

  // ✅ ENHANCED: Listen for call-ended-by-other and disconnect events
  useEffect(() => {
    const handleCallEndedByOther = (data) => {
      console.log('🔔 Call ended by other party:', data);
      
      if (hasEndedRef.current) {
        console.log('⚠️ Already ended, ignoring duplicate event');
        return;
      }

      hasEndedRef.current = true;
      
      // Professional notification based on reason
      const isDisconnect = data.reason === 'disconnect';
      const endedByText = data.endedBy === 'doctor' ? 'Doctor' : 'Patient';
      const message = isDisconnect 
        ? `${endedByText} lost connection. Call ended.`
        : `${endedByText} has ended the call.`;
      
      toast.custom((t) => (
        <div className="bg-white rounded-xl p-4 shadow-2xl border border-gray-200 max-w-sm mx-auto">
          <div className="flex items-center gap-3 mb-3">
            <div className={`w-12 h-12 rounded-full flex items-center justify-center ${
              isDisconnect ? 'bg-yellow-100' : 'bg-red-100'
            }`}>
              <span className="text-2xl">{isDisconnect ? '⚠️' : '📞'}</span>
            </div>
            <div>
              <h3 className="font-bold text-gray-900">
                {isDisconnect ? 'Connection Lost' : 'Call Ended'}
              </h3>
              <p className="text-gray-600 text-sm">{message}</p>
            </div>
          </div>
        </div>
      ), {
        duration: 4000,
        position: 'top-center',
      });

      // Cleanup and navigate
      setTimeout(async () => {
        await cleanup();
        navigate(isHost 
          ? `/prescription/${doctorId}/${patientId}`
          : `/rating/${doctorId}/${patientId}`
        );
      }, 1500);
    };

    const handleCallStatusUpdate = (data) => {
      console.log('📊 Call status update:', data);
      
      if (data.callId === callId && (data.status === 'ended' || data.status === 'disconnected')) {
        handleCallEndedByOther({
          callId: data.callId,
          endedBy: data.endedBy || data.disconnectedBy,
          reason: data.status === 'disconnected' ? 'disconnect' : 'ended'
        });
      }
    };

    socket.on("call-ended-by-other", handleCallEndedByOther);
    socket.on("other-party-disconnected", handleCallEndedByOther);
    socket.on("call-status-update", handleCallStatusUpdate);

    return () => {
      socket.off("call-ended-by-other", handleCallEndedByOther);
      socket.off("other-party-disconnected", handleCallEndedByOther);
      socket.off("call-status-update", handleCallStatusUpdate);
    };
  }, [navigate, isHost, doctorId, patientId, callId]);

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

        // Get available cameras
        await getCameras();

        // Create tracks
        let audioTrack = null;
        let videoTrack = null;

        try {
          audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        } catch (err) {
          console.warn("Audio track error:", err);
          toast.error("Microphone access denied");
        }

        try {
          videoTrack = await AgoraRTC.createCameraVideoTrack();
        } catch (err) {
          console.warn("Video track error:", err);
          setIsCameraOff(true);
          toast.error("Camera access denied");
        }

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
        toast.error("Failed to join call");
      }
    }

    if (!showPermissionModal) {
      joinChannel();
    }

    return () => {
      mounted = false;
      if (!hasEndedRef.current) {
        cleanup();
      }
    };
  }, [safeChannel, role, uid, showPermissionModal]);

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
      toast.success(isMuted ? "Microphone unmuted" : "Microphone muted");
    }
  };

  const toggleCamera = async () => {
    if (localTracks[1]) {
      const videoTrack = localTracks[1];
      await videoTrack.setEnabled(isCameraOff);
      setIsCameraOff(!isCameraOff);
      toast.success(isCameraOff ? "Camera turned on" : "Camera turned off");
    }
  };

  const handleEndCall = async () => {
    if (hasEndedRef.current) {
      console.log('⚠️ Call already ended, preventing duplicate');
      return;
    }

    hasEndedRef.current = true;

    await cleanup();

    // ✅ Emit call-ended with all required info
    socket.emit("call-ended", { 
      callId: callId,
      channel: safeChannel,
      userId: isHost ? doctorId : patientId,
      role: role,
      doctorId: doctorId,
      patientId: patientId,
      timestamp: new Date().toISOString()
    });

    // Navigate
    navigate(isHost 
      ? `/prescription/${doctorId}/${patientId}`
      : `/rating/${doctorId}/${patientId}`
    );
  };

  const getStatusColor = () => {
    switch (callStatus) {
      case "connected": return "#10b981";
      case "connecting": return "#f59e0b";
      case "error": return "#ef4444";
      default: return "#6b7280";
    }
  };

  // Permission Modal Component
  const PermissionModal = () => (
    <div style={{
      position: "fixed",
      top: 0,
      left: 0,
      width: "100vw",
      height: "100vh",
      backgroundColor: "rgba(0, 0, 0, 0.8)",
      display: "flex",
      alignItems: "center",
      justifyContent: "center",
      zIndex: 1000,
      backdropFilter: "blur(10px)"
    }}>
      <div style={{
        background: "linear-gradient(135deg, #1e293b 0%, #334155 100%)",
        padding: "2rem",
        borderRadius: "20px",
        maxWidth: "500px",
        width: "90%",
        textAlign: "center",
        border: "1px solid rgba(255,255,255,0.1)",
        boxShadow: "0 20px 40px rgba(0,0,0,0.3)"
      }}>
        <div style={{
          width: "80px",
          height: "80px",
          background: "linear-gradient(135deg, #3b82f6, #8b5cf6)",
          borderRadius: "50%",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          margin: "0 auto 1.5rem",
          fontSize: "2rem"
        }}>
          📹
        </div>
        
        <h2 style={{
          color: "white",
          fontSize: "1.5rem",
          fontWeight: "600",
          marginBottom: "1rem"
        }}>
          Camera & Microphone Access
        </h2>
        
        <p style={{
          color: "#d1d5db",
          fontSize: "1rem",
          lineHeight: "1.5",
          marginBottom: "2rem"
        }}>
          To start the video call, we need access to your camera and microphone. 
          Please allow permissions when prompted by your browser.
        </p>

        <div style={{
          display: "flex",
          gap: "1rem",
          justifyContent: "center",
          flexWrap: "wrap"
        }}>
          <div style={{
            display: "flex",
            alignItems: "center",
            gap: "0.5rem",
            background: permissions.camera ? "rgba(34, 197, 94, 0.2)" : "rgba(100, 116, 139, 0.3)",
            padding: "0.75rem 1rem",
            borderRadius: "10px",
            border: permissions.camera ? "1px solid #22c55e" : "1px solid #475569"
          }}>
            <span style={{ fontSize: "1.25rem" }}>📷</span>
            <span style={{ color: "white", fontSize: "0.9rem" }}>
              {permissions.camera ? "Camera Granted" : "Camera Access"}
            </span>
          </div>

          <div style={{
            display: "flex",
            alignItems: "center",
            gap: "0.5rem",
            background: permissions.microphone ? "rgba(34, 197, 94, 0.2)" : "rgba(100, 116, 139, 0.3)",
            padding: "0.75rem 1rem",
            borderRadius: "10px",
            border: permissions.microphone ? "1px solid #22c55e" : "1px solid #475569"
          }}>
            <span style={{ fontSize: "1.25rem" }}>🎤</span>
            <span style={{ color: "white", fontSize: "0.9rem" }}>
              {permissions.microphone ? "Microphone Granted" : "Microphone Access"}
            </span>
          </div>
        </div>

        <div style={{
          display: "flex",
          gap: "1rem",
          justifyContent: "center",
          marginTop: "2rem"
        }}>
          <button
            onClick={requestPermissions}
            style={{
              background: "linear-gradient(135deg, #3b82f6, #6366f1)",
              color: "white",
              border: "none",
              padding: "0.75rem 2rem",
              borderRadius: "10px",
              fontSize: "1rem",
              fontWeight: "600",
              cursor: "pointer",
              transition: "all 0.2s"
            }}
            onMouseOver={(e) => e.target.style.transform = "translateY(-2px)"}
            onMouseOut={(e) => e.target.style.transform = "translateY(0)"}
          >
            Allow Permissions
          </button>
        </div>
      </div>
    </div>
  );

  // Camera Selection Modal
  const CameraModal = () => (
    <div style={{
      position: "fixed",
      top: 0,
      left: 0,
      width: "100vw",
      height: "100vh",
      backgroundColor: "rgba(0, 0, 0, 0.8)",
      display: "flex",
      alignItems: "center",
      justifyContent: "center",
      zIndex: 1000,
      backdropFilter: "blur(10px)"
    }}>
      <div style={{
        background: "linear-gradient(135deg, #1e293b 0%, #334155 100%)",
        padding: "2rem",
        borderRadius: "20px",
        maxWidth: "400px",
        width: "90%",
        border: "1px solid rgba(255,255,255,0.1)",
        boxShadow: "0 20px 40px rgba(0,0,0,0.3)"
      }}>
        <h3 style={{
          color: "white",
          fontSize: "1.25rem",
          fontWeight: "600",
          marginBottom: "1.5rem",
          textAlign: "center"
        }}>
          Select Camera
        </h3>

        <div style={{
          display: "flex",
          flexDirection: "column",
          gap: "0.5rem",
          marginBottom: "2rem"
        }}>
          {cameras.map((camera, index) => (
            <button
              key={camera.deviceId}
              onClick={() => switchCamera(camera.deviceId)}
              style={{
                background: selectedCamera === camera.deviceId 
                  ? "rgba(59, 130, 246, 0.3)" 
                  : "rgba(100, 116, 139, 0.2)",
                color: "white",
                border: selectedCamera === camera.deviceId 
                  ? "1px solid #3b82f6" 
                  : "1px solid #475569",
                padding: "1rem",
                borderRadius: "10px",
                cursor: "pointer",
                textAlign: "left",
                transition: "all 0.2s"
              }}
              onMouseOver={(e) => {
                if (selectedCamera !== camera.deviceId) {
                  e.target.style.background = "rgba(100, 116, 139, 0.3)";
                }
              }}
              onMouseOut={(e) => {
                if (selectedCamera !== camera.deviceId) {
                  e.target.style.background = "rgba(100, 116, 139, 0.2)";
                }
              }}
            >
              <div style={{ display: "flex", alignItems: "center", gap: "0.75rem" }}>
                <span style={{ fontSize: "1.25rem" }}>
                  {camera.label.toLowerCase().includes("back") ? "📱" : "📷"}
                </span>
                <span style={{ fontSize: "0.9rem" }}>
                  {camera.label || `Camera ${index + 1}`}
                </span>
                {selectedCamera === camera.deviceId && (
                  <span style={{ 
                    marginLeft: "auto", 
                    color: "#10b981",
                    fontSize: "0.8rem"
                  }}>
                    ● Active
                  </span>
                )}
              </div>
            </button>
          ))}
        </div>

        <div style={{ display: "flex", gap: "1rem", justifyContent: "center" }}>
          <button
            onClick={() => setShowCameraModal(false)}
            style={{
              background: "rgba(100, 116, 139, 0.3)",
              color: "white",
              border: "1px solid #475569",
              padding: "0.75rem 1.5rem",
              borderRadius: "10px",
              cursor: "pointer",
              fontSize: "0.9rem",
              fontWeight: "500"
            }}
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  );

  return (
    <div style={{
      position: "relative",
      width: "100vw",
      height: "100vh",
      backgroundColor: "#0f172a",
      overflow: "hidden",
      color: "white",
      fontFamily: "Inter, -apple-system, BlinkMacSystemFont, sans-serif"
    }}>
      
      {/* Permission Modal */}
      {showPermissionModal && <PermissionModal />}

      {/* Camera Selection Modal */}
      {showCameraModal && <CameraModal />}
      
      {/* Logo */}
      <div style={{
        position: "absolute",
        top: 20,
        left: 24,
        display: "flex",
        alignItems: "center",
        gap: 12,
        zIndex: 50,
        background: "rgba(15, 23, 42, 0.8)",
        padding: "8px 16px",
        borderRadius: "12px",
        backdropFilter: "blur(10px)",
        border: "1px solid rgba(255,255,255,0.1)"
      }}>
        <img src={logo} alt="Snoutiq" style={{ height: 32 }} />
        <div style={{
          height: 20,
          width: 1,
          background: "rgba(255,255,255,0.2)"
        }} />
        <span style={{
          fontSize: 14,
          fontWeight: 600,
          background: "linear-gradient(135deg, #60a5fa, #a78bfa)",
          WebkitBackgroundClip: "text",
          WebkitTextFillColor: "transparent"
        }}>
          Video Consult
        </span>
      </div>

      {/* Remote Video */}
      <div ref={remoteVideoRef} style={{
        position: "absolute",
        top: 0,
        left: 0,
        width: "100%",
        height: "100%",
        background: "linear-gradient(135deg, #0f172a 0%, #1e293b 100%)",
        objectFit: "cover"
      }} />

      {/* Waiting state */}
      {remoteUsers.length === 0 && (
        <div style={{
          position: "absolute",
          top: "50%",
          left: "50%",
          transform: "translate(-50%, -50%)",
          textAlign: "center",
          color: "white",
          zIndex: 10
        }}>
          <div style={{
            width: 120,
            height: 120,
            background: "rgba(255,255,255,0.1)",
            borderRadius: "50%",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            margin: "0 auto 1.5rem",
            fontSize: 48,
            backdropFilter: "blur(10px)",
            border: "1px solid rgba(255,255,255,0.2)"
          }}>
            ⏳
          </div>
          <div style={{ 
            fontSize: 18, 
            fontWeight: 500,
            marginBottom: "0.5rem"
          }}>
            Waiting for {isHost ? "Patient" : "Doctor"}
          </div>
          <div style={{ 
            fontSize: 14, 
            opacity: 0.7,
            color: "#d1d5db"
          }}>
            They will join shortly...
          </div>
        </div>
      )}

      {/* Local Preview */}
      <div style={{
        position: "absolute",
        bottom: 120,
        right: 24,
        width: 240,
        height: 160,
        borderRadius: 16,
        overflow: "hidden",
        border: "2px solid rgba(255,255,255,0.3)",
        boxShadow: "0 8px 32px rgba(0,0,0,0.3)",
        background: "#000",
        zIndex: 20
      }}>
        <div ref={localVideoRef} style={{ 
          width: "100%", 
          height: "100%",
          objectFit: "cover"
        }} />
        
        {/* Camera Off Overlay */}
        {isCameraOff && (
          <div style={{
            position: "absolute",
            top: 0,
            left: 0,
            width: "100%",
            height: "100%",
            background: "rgba(0,0,0,0.8)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            flexDirection: "column",
            gap: 8
          }}>
            <div style={{ fontSize: 24 }}>📷</div>
            <div style={{ fontSize: 12, color: "white" }}>Camera Off</div>
          </div>
        )}
        
        {/* User Badge */}
        <div style={{
          position: "absolute",
          bottom: 8,
          left: 8,
          background: "rgba(0,0,0,0.7)",
          padding: "4px 12px",
          borderRadius: 20,
          fontSize: 12,
          fontWeight: 500,
          backdropFilter: "blur(10px)",
          border: "1px solid rgba(255,255,255,0.2)"
        }}>
          You • {isHost ? "Doctor" : "Patient"}
        </div>
      </div>

      {/* Status Bar */}
      <div style={{
        position: "absolute",
        top: 20,
        right: 24,
        background: "rgba(15, 23, 42, 0.8)",
        padding: "8px 16px",
        borderRadius: 12,
        fontSize: 13,
        fontWeight: 500,
        display: "flex",
        alignItems: "center",
        gap: 12,
        backdropFilter: "blur(10px)",
        border: "1px solid rgba(255,255,255,0.1)",
        zIndex: 50
      }}>
        <div style={{
          display: "flex",
          alignItems: "center",
          gap: 6
        }}>
          <span style={{ opacity: 0.7 }}>Status:</span>
          <span style={{ 
            color: getStatusColor(),
            fontWeight: 600,
            textTransform: "capitalize"
          }}>
            {callStatus}
          </span>
        </div>
        <div style={{
          width: 6,
          height: 6,
          borderRadius: "50%",
          background: getStatusColor()
        }} />
      </div>

      {/* Enhanced Controls */}
      {joined && (
        <div style={{
          position: "absolute",
          bottom: 24,
          left: "50%",
          transform: "translateX(-50%)",
          display: "flex",
          gap: 16,
          justifyContent: "center",
          background: "rgba(15, 23, 42, 0.8)",
          padding: "16px 32px",
          borderRadius: 24,
          backdropFilter: "blur(20px)",
          boxShadow: "0 8px 32px rgba(0,0,0,0.3)",
          border: "1px solid rgba(255,255,255,0.1)",
          zIndex: 30
        }}>
          {/* Mute/Unmute */}
          <button 
            onClick={toggleMute}
            style={{
              width: 56,
              height: 56,
              borderRadius: "50%",
              border: "none",
              background: isMuted 
                ? "linear-gradient(135deg, #ef4444, #dc2626)" 
                : "linear-gradient(135deg, #475569, #374151)",
              color: "white",
              fontSize: 20,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              transition: "all 0.2s",
              boxShadow: "0 4px 12px rgba(0,0,0,0.2)"
            }}
            onMouseOver={(e) => e.target.style.transform = "scale(1.1)"}
            onMouseOut={(e) => e.target.style.transform = "scale(1)"}
          >
            {isMuted ? "🔇" : "🎤"}
          </button>

          {/* Camera Toggle */}
          <button 
            onClick={toggleCamera}
            style={{
              width: 56,
              height: 56,
              borderRadius: "50%",
              border: "none",
              background: isCameraOff 
                ? "linear-gradient(135deg, #ef4444, #dc2626)" 
                : "linear-gradient(135deg, #475569, #374151)",
              color: "white",
              fontSize: 20,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              transition: "all 0.2s",
              boxShadow: "0 4px 12px rgba(0,0,0,0.2)"
            }}
            onMouseOver={(e) => e.target.style.transform = "scale(1.1)"}
            onMouseOut={(e) => e.target.style.transform = "scale(1)"}
          >
            {isCameraOff ? "📷" : "📹"}
          </button>

          {/* Switch Camera */}
          {cameras.length > 1 && (
            <button 
              onClick={() => setShowCameraModal(true)}
              style={{
                width: 56,
                height: 56,
                borderRadius: "50%",
                border: "none",
                background: "linear-gradient(135deg, #3b82f6, #6366f1)",
                color: "white",
                fontSize: 20,
                cursor: "pointer",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                transition: "all 0.2s",
                boxShadow: "0 4px 12px rgba(0,0,0,0.2)"
              }}
              onMouseOver={(e) => e.target.style.transform = "scale(1.1)"}
              onMouseOut={(e) => e.target.style.transform = "scale(1)"}
            >
              🔄
            </button>
          )}

          {/* End Call */}
          <button 
            onClick={handleEndCall}
            style={{
              width: 56,
              height: 56,
              borderRadius: "50%",
              border: "none",
              background: "linear-gradient(135deg, #ef4444, #dc2626)",
              color: "white",
              fontSize: 20,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              transition: "all 0.2s",
              boxShadow: "0 4px 12px rgba(0,0,0,0.2)"
            }}
            onMouseOver={(e) => e.target.style.transform = "scale(1.1)"}
            onMouseOut={(e) => e.target.style.transform = "scale(1)"}
          >
            📞
          </button>
        </div>
      )}

      {/* Connection Quality Indicator */}
      <div style={{
        position: "absolute",
        top: 70,
        right: 24,
        display: "flex",
        alignItems: "center",
        gap: 8,
        background: "rgba(15, 23, 42, 0.8)",
        padding: "6px 12px",
        borderRadius: 8,
        fontSize: 12,
        backdropFilter: "blur(10px)",
        border: "1px solid rgba(255,255,255,0.1)",
        zIndex: 50
      }}>
        <div style={{
          display: "flex",
          alignItems: "center",
          gap: 4
        }}>
          <span style={{ opacity: 0.7 }}>Connection:</span>
          <span style={{ 
            color: callStatus === "connected" ? "#10b981" : "#f59e0b",
            fontWeight: 500
          }}>
            {callStatus === "connected" ? "Excellent" : "Connecting..."}
          </span>
        </div>
      </div>
    </div>
  );
}