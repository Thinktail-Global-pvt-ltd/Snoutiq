import React, { useEffect, useRef, useState, useMemo } from "react";
import { useNavigate, useParams, useLocation, useSearchParams } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";

const APP_ID = "e20a4d60afd8494eab490563ad2e61d1";

export default function CallPage() {
  const navigate = useNavigate();
  const { channelParam } = useParams();
  const location = useLocation();
  const [searchParams] = useSearchParams();

  // Get params from query or state
  const doctorId = searchParams.get('doctorId') || location.state?.doctorId;
  const patientId = searchParams.get('patientId') || location.state?.patientId;
  const callId = searchParams.get('callId') || location.state?.callId;
  const role = searchParams.get('role') || location.state?.role || "host";
  
  console.log("✅ Extracted params:", { doctorId, patientId, callId, role });
  
  const channelName = channelParam || location.state?.channelName || searchParams.get('channel') || "demo-channel";
  const isHost = role === "host";
  
  const safeChannel = useMemo(() => {
    return (channelName || "default_channel")
      .replace(/[^a-zA-Z0-9_]/g, "")
      .slice(0, 63);
  }, [channelName]);

  const uid = useMemo(() => {
    const uidParam = searchParams.get('uid');
    return uidParam ? parseInt(uidParam) : Math.floor(Math.random() * 1e6);
  }, [searchParams]);

  // Refs
  const clientRef = useRef(null);
  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);
  const localTracksRef = useRef([]);
  const remoteTracksRef = useRef({ audio: null, video: null });
  const mediaRecorderRef = useRef(null);
  const recordedChunksRef = useRef([]);
  const audioMixingRef = useRef({ context: null, sources: [], streams: [] });
  const hasJoinedRef = useRef(false);

  // State
  const [localTracks, setLocalTracks] = useState([]);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("initializing");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);
  const [currentCameraIndex, setCurrentCameraIndex] = useState(0);
  const [availableCameras, setAvailableCameras] = useState([]);
  const [showCameraList, setShowCameraList] = useState(false);
  const [showPermissionModal, setShowPermissionModal] = useState(true);
  const [permissions, setPermissions] = useState({
    camera: false,
    microphone: false
  });
  const [isRecording, setIsRecording] = useState(false);
  const [recordingUrl, setRecordingUrl] = useState("");
  const [recordingError, setRecordingError] = useState("");

  // Initialize Agora client
  useEffect(() => {
    clientRef.current = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
    console.log("✅ Agora client initialized");
  }, []);

  // Request permissions and get cameras
  const requestPermissions = async () => {
    try {
      console.log("📹 Requesting permissions...");
      
      const stream = await navigator.mediaDevices.getUserMedia({ 
        video: true, 
        audio: true 
      });
      
      console.log("✅ Permissions granted");
      setPermissions({ camera: true, microphone: true });
      
      const devices = await navigator.mediaDevices.enumerateDevices();
      const videoDevices = devices.filter(device => device.kind === 'videoinput');
      console.log("📷 Found cameras:", videoDevices.length);
      setAvailableCameras(videoDevices);
      
      stream.getTracks().forEach(track => track.stop());
      
      setShowPermissionModal(false);
      return true;
    } catch (error) {
      console.error("❌ Permission error:", error);
      alert("Camera and microphone access is required. Please allow access in your browser settings and refresh the page.");
      return false;
    }
  };

  useEffect(() => {
    requestPermissions();
  }, []);

  useEffect(() => {
    return () => {
      if (recordingUrl) {
        URL.revokeObjectURL(recordingUrl);
      }
    };
  }, [recordingUrl]);

  // Join channel and create tracks
  useEffect(() => {
    if (showPermissionModal || !clientRef.current || hasJoinedRef.current) return;

    let mounted = true;
    const client = clientRef.current;

    async function joinChannel() {
      try {
        // Check again to prevent race condition
        if (hasJoinedRef.current || client.connectionState === "CONNECTED" || client.connectionState === "CONNECTING") {
          console.log("⚠️ Already connected/connecting, skipping join");
          return;
        }
        
        setCallStatus("connecting");
        console.log(`🔗 Joining channel: ${safeChannel}, uid: ${uid}, role: ${role}`);
        
        await client.join(APP_ID, safeChannel, null, uid);
        hasJoinedRef.current = true;
        
        console.log("✅ Joined channel successfully");
        setJoined(true);
        setCallStatus("connected");

        await createAndPublishTracks();
        setupRemoteUserHandlers();
      } catch (error) {
        console.error("❌ Join channel error:", error);
        setCallStatus("error");
        if (error.code !== "INVALID_OPERATION") {
          alert("Failed to join call: " + error.message);
        }
      }
    }

    const createAndPublishTracks = async () => {
      try {
        const tracks = [];
        
        console.log("🎤 Creating audio track...");
        try {
          const audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
          tracks.push(audioTrack);
          console.log("✅ Audio track created");
        } catch (err) {
          console.warn("⚠️ Audio track failed:", err);
        }

        console.log("📹 Creating video track...");
        try {
          const cameraDevice = availableCameras[currentCameraIndex];
          const videoTrack = await AgoraRTC.createCameraVideoTrack({
            cameraId: cameraDevice?.deviceId,
            encoderConfig: "720p_1"
          });
          tracks.push(videoTrack);
          console.log("✅ Video track created");

          if (localVideoRef.current) {
            videoTrack.play(localVideoRef.current, { fit: "cover" });
            console.log("✅ Local video playing");
          }
        } catch (err) {
          console.error("❌ Video track failed:", err);
          setIsCameraOff(true);
        }

        if (tracks.length > 0) {
          await client.publish(tracks);
          console.log("✅ Tracks published:", tracks.length);
        }

        setLocalTracks(tracks);
        localTracksRef.current = tracks;
      } catch (error) {
        console.error("❌ Error creating/publishing tracks:", error);
      }
    };

    const setupRemoteUserHandlers = () => {
      client.on("user-published", async (user, mediaType) => {
        try {
          await client.subscribe(user, mediaType);
          console.log(`✅ Subscribed to user ${user.uid} ${mediaType}`);

          if (mediaType === "video" && user.videoTrack) {
            remoteTracksRef.current.video = { track: user.videoTrack, uid: user.uid };
            if (remoteVideoRef.current) {
              user.videoTrack.play(remoteVideoRef.current, { fit: "cover" });
            }
            setRemoteUsers(prev => {
              if (!prev.some(u => u.uid === user.uid)) {
                return [...prev, user];
              }
              return prev;
            });
          }
          
          if (mediaType === "audio" && user.audioTrack) {
            remoteTracksRef.current.audio = { track: user.audioTrack, uid: user.uid };
            user.audioTrack.play();
          }
        } catch (err) {
          console.error("❌ Error subscribing:", err);
        }
      });

      client.on("user-unpublished", (user, mediaType) => {
        console.log(`User ${user.uid} unpublished ${mediaType}`);
        if (mediaType === "video") {
          if (remoteTracksRef.current.video?.uid === user.uid) {
            remoteTracksRef.current.video = null;
          }
          setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
        }
        if (mediaType === "audio" && remoteTracksRef.current.audio?.uid === user.uid) {
          remoteTracksRef.current.audio = null;
        }
      });

      client.on("user-left", (user) => {
        console.log(`User ${user.uid} left`);
        setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
        if (remoteTracksRef.current.video?.uid === user.uid) {
          remoteTracksRef.current.video = null;
        }
        if (remoteTracksRef.current.audio?.uid === user.uid) {
          remoteTracksRef.current.audio = null;
        }
      });
    };

    joinChannel();

    return () => {
      mounted = false;
      if (hasJoinedRef.current) {
        cleanup();
      }
    };
  }, [showPermissionModal, safeChannel, uid, role]);

  const cleanup = async () => {
    const client = clientRef.current;
    if (!client) return;
    
    try {
      console.log("🧹 Cleaning up...");
      stopRecording();
      
      localTracksRef.current.forEach(track => {
        try {
          track.stop();
          track.close();
        } catch (err) {
          console.warn("Error closing track:", err);
        }
      });

      if (client.connectionState === "CONNECTED") {
        await client.leave();
        console.log("✅ Left channel");
      }

      setLocalTracks([]);
      localTracksRef.current = [];
      setRemoteUsers([]);
      setJoined(false);
      remoteTracksRef.current = { audio: null, video: null };
      hasJoinedRef.current = false;
    } catch (error) {
      console.error("❌ Cleanup error:", error);
    }
  };

  const disposeAudioResources = (resources) => {
    if (!resources) return;
    resources.sources?.forEach((source) => {
      try {
        source.disconnect();
      } catch (error) {
        console.warn("⚠️ Failed to disconnect audio source", error);
      }
    });
    resources.streams = [];
    if (resources.context) {
      resources.context.close().catch(() => {});
    }
  };

  const releaseAudioResources = () => {
    disposeAudioResources(audioMixingRef.current);
    audioMixingRef.current = { context: null, sources: [], streams: [] };
  };

  const stopRecording = () => {
    const recorder = mediaRecorderRef.current;
    if (!recorder || recorder.state === "inactive") {
      return;
    }
    try {
      recorder.stop();
    } catch (error) {
      console.error("❌ Error stopping recording:", error);
    }
  };

  const buildRecordingStream = () => {
    if (typeof window === "undefined") {
      return null;
    }

    const recordingStream = new MediaStream();
    const audioResources = { context: null, sources: [], streams: [] };
    const localAudioTrack = localTracksRef.current.find(
      (track) => track.trackMediaType === "audio"
    );
    const remoteAudioTrack = remoteTracksRef.current.audio?.track;
    const audioTracks = [];

    if (localAudioTrack?.getMediaStreamTrack) {
      const track = localAudioTrack.getMediaStreamTrack();
      if (track) {
        audioTracks.push(track);
      }
    }

    if (remoteAudioTrack?.getMediaStreamTrack) {
      const track = remoteAudioTrack.getMediaStreamTrack();
      if (track) {
        audioTracks.push(track);
      }
    }

    if (audioTracks.length === 1) {
      recordingStream.addTrack(audioTracks[0]);
    } else if (audioTracks.length > 1) {
      const AudioContextClass = window.AudioContext || window.webkitAudioContext;
      if (!AudioContextClass) {
        recordingStream.addTrack(audioTracks[0]);
      } else {
        const audioContext = new AudioContextClass();
        const destination = audioContext.createMediaStreamDestination();
        audioTracks.forEach((track) => {
          const stream = new MediaStream([track]);
          const sourceNode = audioContext.createMediaStreamSource(stream);
          sourceNode.connect(destination);
          audioResources.streams.push(stream);
          audioResources.sources.push(sourceNode);
        });
        audioResources.context = audioContext;
        const mixedTrack = destination.stream.getAudioTracks()[0];
        if (mixedTrack) {
          recordingStream.addTrack(mixedTrack);
        }
      }
    }

    const localVideoTrack = localTracksRef.current.find(
      (track) => track.trackMediaType === "video"
    );
    const preferredVideoTrack = remoteTracksRef.current.video?.track || localVideoTrack;
    if (preferredVideoTrack?.getMediaStreamTrack) {
      const videoTrack = preferredVideoTrack.getMediaStreamTrack();
      if (videoTrack) {
        recordingStream.addTrack(videoTrack);
      }
    }

    if (!recordingStream.getTracks().length) {
      disposeAudioResources(audioResources);
      return null;
    }

    return { stream: recordingStream, audioResources };
  };

  const startRecording = () => {
    if (
      typeof window === "undefined" ||
      typeof window.MediaRecorder === "undefined"
    ) {
      setRecordingError("Recording is not supported in this browser.");
      return;
    }

    if (!joined) {
      setRecordingError("Join the call before starting a recording.");
      return;
    }

    const setup = buildRecordingStream();
    if (!setup) {
      setRecordingError("Media tracks are not ready yet. Try again in a moment.");
      return;
    }

    if (recordingUrl) {
      URL.revokeObjectURL(recordingUrl);
      setRecordingUrl("");
    }

    recordedChunksRef.current = [];
    setRecordingError("");

    try {
      const preferredMimeType = "video/webm;codecs=vp8,opus";
      const options = {};
      if (
        window.MediaRecorder.isTypeSupported?.(preferredMimeType)
      ) {
        options.mimeType = preferredMimeType;
      }

      const recorder = new MediaRecorder(setup.stream, options);
      audioMixingRef.current = setup.audioResources;

      recorder.addEventListener("dataavailable", (event) => {
        if (event.data && event.data.size > 0) {
          recordedChunksRef.current.push(event.data);
        }
      });

      recorder.addEventListener("stop", () => {
        if (recordedChunksRef.current.length) {
          const blob = new Blob(recordedChunksRef.current, {
            type: recorder.mimeType || "video/webm",
          });
          const url = URL.createObjectURL(blob);
          setRecordingUrl(url);
        }
        recordedChunksRef.current = [];
        setIsRecording(false);
        mediaRecorderRef.current = null;
        releaseAudioResources();
      });

      recorder.addEventListener("error", (event) => {
        console.error("Recorder error:", event.error || event);
        setRecordingError("Recording failed. Please try again.");
        recordedChunksRef.current = [];
        setIsRecording(false);
        mediaRecorderRef.current = null;
        releaseAudioResources();
      });

      recorder.start();
      mediaRecorderRef.current = recorder;
      setIsRecording(true);
    } catch (error) {
      console.error("Failed to start recording:", error);
      setRecordingError("Unable to start recording on this device.");
      recordedChunksRef.current = [];
      mediaRecorderRef.current = null;
      setIsRecording(false);
      disposeAudioResources(setup.audioResources);
    }
  };

  const toggleMute = async () => {
    const audioTrack = localTracksRef.current.find(track => track.trackMediaType === 'audio');
    if (audioTrack) {
      await audioTrack.setEnabled(isMuted);
      setIsMuted(!isMuted);
      console.log(!isMuted ? "🔇 Muted" : "🎤 Unmuted");
    }
  };

  const toggleCamera = async () => {
    const videoTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
    if (videoTrack) {
      await videoTrack.setEnabled(isCameraOff);
      setIsCameraOff(!isCameraOff);
      console.log(isCameraOff ? "📹 Camera on" : "📷 Camera off");
    }
  };

  const switchToCamera = async (cameraIndex) => {
    if (cameraIndex === currentCameraIndex || availableCameras.length <= cameraIndex) return;

    try {
      console.log(`🔄 Switching to camera ${cameraIndex}`);
      const currentVideoTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
      
      if (currentVideoTrack && clientRef.current) {
        await clientRef.current.unpublish(currentVideoTrack);
        currentVideoTrack.stop();
        currentVideoTrack.close();
        
        const cameraDevice = availableCameras[cameraIndex];
        const newVideoTrack = await AgoraRTC.createCameraVideoTrack({
          cameraId: cameraDevice.deviceId,
          encoderConfig: "720p_1"
        });
        
        const newTracks = localTracksRef.current.filter(track => track.trackMediaType !== 'video');
        newTracks.push(newVideoTrack);
        
        await clientRef.current.publish(newVideoTrack);
        
        if (localVideoRef.current) {
          newVideoTrack.play(localVideoRef.current, { fit: "cover" });
        }
        
        setLocalTracks(newTracks);
        localTracksRef.current = newTracks;
        setCurrentCameraIndex(cameraIndex);
        setIsCameraOff(false);
        setShowCameraList(false);
        console.log("✅ Camera switched");
      }
    } catch (error) {
      console.error("❌ Error switching camera:", error);
    }
  };

 const handleEndCall = async () => {
  console.log("📞 Ending call...");
  await cleanup();

  if (doctorId && patientId) {
    console.log(`✅ Navigating with doctorId: ${doctorId}, patientId: ${patientId}`);
    navigate(
      isHost 
        ? `/prescription/${doctorId}/${patientId}`
        : `/rating/${doctorId}/${patientId}`,
      {
        state: {
          doctorId,
          patientId,
          callId,
          fromCall: true
        }
      }
    );
  } else {
    console.warn("⚠️ Missing doctorId or patientId, redirecting...");
    alert("Call ended");

    // Try to go 2 pages back, or fallback to home if not possible
    try {
      if (window.history.length > 2) {
        navigate(-2);
      } else {
        navigate("/");
      }
    } catch (error) {
      console.error("❌ Navigation failed:", error);
      navigate("/");
    }
  }
};


  const PermissionModal = () => (
    <div style={{
      position: "fixed",
      inset: 0,
      backgroundColor: "rgba(0, 0, 0, 0.9)",
      display: "flex",
      alignItems: "center",
      justifyContent: "center",
      zIndex: 1000,
      backdropFilter: "blur(10px)"
    }}>
      <div style={{
        background: "linear-gradient(135deg, #1e293b 0%, #334155 100%)",
        padding: "2.5rem",
        borderRadius: "24px",
        maxWidth: "500px",
        width: "90%",
        textAlign: "center",
        border: "1px solid rgba(255,255,255,0.1)",
        boxShadow: "0 20px 60px rgba(0,0,0,0.5)"
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
          fontSize: "2.5rem",
          boxShadow: "0 8px 24px rgba(59, 130, 246, 0.3)"
        }}>
          📹
        </div>
        
        <h2 style={{
          color: "white",
          fontSize: "1.75rem",
          fontWeight: "700",
          marginBottom: "1rem"
        }}>
          Camera & Microphone Access
        </h2>
        
        <p style={{
          color: "#cbd5e1",
          fontSize: "1.05rem",
          lineHeight: "1.6",
          marginBottom: "2rem"
        }}>
          To start the video consultation, we need access to your camera and microphone. 
          Click the button below to grant permissions.
        </p>

        <div style={{
          display: "flex",
          gap: "1rem",
          justifyContent: "center",
          flexWrap: "wrap",
          marginBottom: "2rem"
        }}>
          <div style={{
            display: "flex",
            alignItems: "center",
            gap: "0.75rem",
            background: permissions.camera ? "rgba(34, 197, 94, 0.2)" : "rgba(100, 116, 139, 0.2)",
            padding: "0.875rem 1.25rem",
            borderRadius: "12px",
            border: permissions.camera ? "1px solid #22c55e" : "1px solid #475569"
          }}>
            <span style={{ fontSize: "1.5rem" }}>📷</span>
            <span style={{ color: "white", fontSize: "0.95rem", fontWeight: "500" }}>
              {permissions.camera ? "✓ Camera" : "Camera"}
            </span>
          </div>

          <div style={{
            display: "flex",
            alignItems: "center",
            gap: "0.75rem",
            background: permissions.microphone ? "rgba(34, 197, 94, 0.2)" : "rgba(100, 116, 139, 0.2)",
            padding: "0.875rem 1.25rem",
            borderRadius: "12px",
            border: permissions.microphone ? "1px solid #22c55e" : "1px solid #475569"
          }}>
            <span style={{ fontSize: "1.5rem" }}>🎤</span>
            <span style={{ color: "white", fontSize: "0.95rem", fontWeight: "500" }}>
              {permissions.microphone ? "✓ Microphone" : "Microphone"}
            </span>
          </div>
        </div>

        <button
          onClick={requestPermissions}
          style={{
            background: "linear-gradient(135deg, #3b82f6, #6366f1)",
            color: "white",
            border: "none",
            padding: "1rem 2.5rem",
            borderRadius: "12px",
            fontSize: "1.1rem",
            fontWeight: "600",
            cursor: "pointer",
            transition: "all 0.2s",
            boxShadow: "0 4px 16px rgba(59, 130, 246, 0.3)"
          }}
          onMouseOver={(e) => {
            e.target.style.transform = "translateY(-2px)";
            e.target.style.boxShadow = "0 6px 20px rgba(59, 130, 246, 0.4)";
          }}
          onMouseOut={(e) => {
            e.target.style.transform = "translateY(0)";
            e.target.style.boxShadow = "0 4px 16px rgba(59, 130, 246, 0.3)";
          }}
        >
          Allow Access
        </button>

        <p style={{
          color: "#94a3b8",
          fontSize: "0.85rem",
          marginTop: "1.5rem",
          lineHeight: "1.4"
        }}>
          Your privacy is important. You can disable access anytime from your browser settings.
        </p>
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
      fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    }}>
      
      {showPermissionModal && <PermissionModal />}

      {/* Header */}
      <div style={{
        position: "absolute",
        top: 20,
        left: 24,
        right: 24,
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        zIndex: 50
      }}>
        <div style={{
          display: "flex",
          alignItems: "center",
          gap: 12,
          background: "rgba(15, 23, 42, 0.8)",
          padding: "10px 20px",
          borderRadius: "12px",
          backdropFilter: "blur(10px)",
          border: "1px solid rgba(255,255,255,0.1)"
        }}>
          <div style={{
            width: 32,
            height: 32,
            background: "linear-gradient(135deg, #3b82f6, #8b5cf6)",
            borderRadius: "8px",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            fontSize: "1.2rem"
          }}>
            🏥
          </div>
          <span style={{
            fontSize: 16,
            fontWeight: 600,
            background: "linear-gradient(135deg, #60a5fa, #a78bfa)",
            WebkitBackgroundClip: "text",
            WebkitTextFillColor: "transparent"
          }}>
            Video Consult
          </span>
        </div>

        <div style={{
          display: "flex",
          flexDirection: "column",
          alignItems: "flex-end",
          gap: 8
        }}>
            <div style={{
              display: "flex",
              alignItems: "center",
              gap: 12
            }}>
            <div style={{
              background: "rgba(15, 23, 42, 0.8)",
              padding: "10px 20px",
              borderRadius: "12px",
              fontSize: 14,
              fontWeight: 600,
              display: "flex",
              alignItems: "center",
              gap: 10,
              backdropFilter: "blur(10px)",
              border: "1px solid rgba(255,255,255,0.1)"
            }}>
              <div style={{
                width: 8,
                height: 8,
                borderRadius: "50%",
                background: callStatus === "connected" ? "#10b981" : 
                           callStatus === "connecting" ? "#f59e0b" : "#ef4444",
                boxShadow: `0 0 10px ${callStatus === "connected" ? "#10b981" : 
                                       callStatus === "connecting" ? "#f59e0b" : "#ef4444"}`
              }} />
              <span style={{
                color: callStatus === "connected" ? "#10b981" : 
                       callStatus === "connecting" ? "#f59e0b" : "#ef4444"
              }}>
                {callStatus === "connected" ? "Connected" : 
                 callStatus === "connecting" ? "Connecting..." : 
                 callStatus === "error" ? "Error" : "Initializing"}
              </span>
            </div>

            {isRecording && (
              <div style={{
                padding: "8px 16px",
                borderRadius: "999px",
                background: "rgba(220, 38, 38, 0.15)",
                border: "1px solid rgba(248,113,113,0.4)",
                color: "#f87171",
                fontWeight: 600,
                fontSize: 13,
                display: "flex",
                alignItems: "center",
                gap: 8,
                backdropFilter: "blur(8px)"
              }}>
                <span style={{ fontSize: 10 }}>●</span>
                Recording...
              </div>
            )}

            {recordingUrl && (
              <a
                href={recordingUrl}
                download={`snoutiq-call-${callId || safeChannel}.webm`}
                style={{
                  padding: "9px 16px",
                  borderRadius: "10px",
                  border: "1px solid rgba(96,165,250,0.6)",
                  color: "#bfdbfe",
                  fontSize: 13,
                  fontWeight: 600,
                  textDecoration: "none",
                  background: "rgba(59,130,246,0.15)",
                  transition: "all 0.2s"
                }}
                onMouseOver={(e) => {
                  e.currentTarget.style.background = "rgba(59,130,246,0.3)";
                }}
                onMouseOut={(e) => {
                  e.currentTarget.style.background = "rgba(59,130,246,0.15)";
                }}
              >
                ⬇ Download Recording
              </a>
            )}

            <button
              type="button"
              onClick={isRecording ? stopRecording : startRecording}
              disabled={!joined}
              style={{
                padding: "10px 18px",
                borderRadius: "999px",
                border: "none",
                fontWeight: 600,
                fontSize: 13,
                cursor: joined ? "pointer" : "not-allowed",
                color: "white",
                background: isRecording
                  ? "linear-gradient(135deg, #ef4444, #b91c1c)"
                  : "linear-gradient(135deg, #f97316, #fb923c)",
                boxShadow: joined
                  ? "0 6px 16px rgba(0,0,0,0.25)"
                  : "none",
                opacity: joined ? 1 : 0.6,
                transition: "opacity 0.2s, transform 0.2s"
              }}
              onMouseOver={(e) => {
                if (joined) e.currentTarget.style.transform = "scale(1.03)";
              }}
              onMouseOut={(e) => {
                e.currentTarget.style.transform = "scale(1)";
              }}
            >
              {isRecording ? "Stop Recording" : "Start Recording"}
            </button>
          </div>

          {recordingError && (
            <div style={{
              fontSize: 12,
              color: "#f87171",
              fontWeight: 500,
              textAlign: "right"
            }}>
              {recordingError}
            </div>
          )}
        </div>
      </div>

      {/* Remote Video (Full Screen) */}
      <div 
        ref={remoteVideoRef}
        style={{
          position: "absolute",
          top: 0,
          left: 0,
          width: "100%",
          height: "100%",
          background: "linear-gradient(135deg, #1e293b 0%, #0f172a 100%)",
          objectFit: "cover"
        }}
      />

      {/* Waiting State */}
      {remoteUsers.length === 0 && joined && (
        <div style={{
          position: "absolute",
          top: "50%",
          left: "50%",
          transform: "translate(-50%, -50%)",
          textAlign: "center",
          zIndex: 10
        }}>
          <div style={{
            width: 120,
            height: 120,
            background: "rgba(59, 130, 246, 0.1)",
            borderRadius: "50%",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            margin: "0 auto 1.5rem",
            fontSize: 60,
            border: "2px solid rgba(59, 130, 246, 0.3)",
            animation: "pulse 2s ease-in-out infinite"
          }}>
            ⏳
          </div>
          <h3 style={{
            fontSize: 22,
            fontWeight: 600,
            marginBottom: "0.5rem",
            color: "white"
          }}>
            Waiting for {isHost ? "Patient" : "Doctor"}
          </h3>
          <p style={{
            fontSize: 15,
            color: "#94a3b8"
          }}>
            They will join shortly...
          </p>
        </div>
      )}

      {/* Local Video Preview (Picture-in-Picture) */}
      <div style={{
        position: "absolute",
        bottom: 120,
        right: 24,
        width: 280,
        height: 200,
        borderRadius: 20,
        overflow: "hidden",
        border: "3px solid rgba(59, 130, 246, 0.5)",
        boxShadow: "0 12px 48px rgba(0,0,0,0.5)",
        background: "#000",
        zIndex: 20
      }}>
        <div 
          ref={localVideoRef}
          style={{
            width: "100%",
            height: "100%",
            objectFit: "cover"
          }}
        />
        
        {(isCameraOff || localTracks.length === 0) && (
          <div style={{
            position: "absolute",
            inset: 0,
            background: "rgba(0,0,0,0.9)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            flexDirection: "column",
            gap: 12
          }}>
            <div style={{
              width: 60,
              height: 60,
              background: "rgba(59, 130, 246, 0.2)",
              borderRadius: "50%",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontSize: 28
            }}>
              📷
            </div>
            <div style={{ fontSize: 13, color: "#94a3b8", fontWeight: 500 }}>
              {localTracks.length === 0 ? "Starting camera..." : "Camera Off"}
            </div>
          </div>
        )}
        
        <div style={{
          position: "absolute",
          bottom: 12,
          left: 12,
          background: "rgba(0,0,0,0.8)",
          padding: "6px 14px",
          borderRadius: 20,
          fontSize: 13,
          fontWeight: 600,
          backdropFilter: "blur(10px)",
          border: "1px solid rgba(255,255,255,0.2)"
        }}>
          You • {isHost ? "Doctor" : "Patient"}
        </div>

        {availableCameras.length > 1 && !isCameraOff && (
          <button
            onClick={() => setShowCameraList(!showCameraList)}
            style={{
              position: "absolute",
              top: 12,
              right: 12,
              width: 36,
              height: 36,
              borderRadius: "50%",
              border: "none",
              background: "rgba(0,0,0,0.7)",
              color: "white",
              fontSize: 18,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              backdropFilter: "blur(10px)",
              transition: "all 0.2s"
            }}
            onMouseOver={(e) => e.target.style.background = "rgba(59, 130, 246, 0.8)"}
            onMouseOut={(e) => e.target.style.background = "rgba(0,0,0,0.7)"}
          >
            🔄
          </button>
        )}

        {showCameraList && availableCameras.length > 1 && (
          <div style={{
            position: "absolute",
            top: 60,
            right: 12,
            background: "rgba(15, 23, 42, 0.95)",
            borderRadius: "12px",
            padding: "8px",
            minWidth: "200px",
            backdropFilter: "blur(20px)",
            border: "1px solid rgba(255,255,255,0.1)",
            boxShadow: "0 8px 32px rgba(0,0,0,0.5)",
            zIndex: 30
          }}>
            {availableCameras.map((camera, index) => (
              <button
                key={camera.deviceId}
                onClick={() => switchToCamera(index)}
                style={{
                  width: "100%",
                  padding: "10px 12px",
                  marginBottom: index < availableCameras.length - 1 ? "4px" : 0,
                  background: currentCameraIndex === index ? "rgba(59, 130, 246, 0.3)" : "transparent",
                  border: "1px solid",
                  borderColor: currentCameraIndex === index ? "#3b82f6" : "transparent",
                  borderRadius: "8px",
                  color: "white",
                  fontSize: "13px",
                  cursor: "pointer",
                  textAlign: "left",
                  transition: "all 0.2s",
                  display: "flex",
                  alignItems: "center",
                  gap: "8px"
                }}
                onMouseOver={(e) => {
                  if (currentCameraIndex !== index) {
                    e.target.style.background = "rgba(100, 116, 139, 0.3)";
                  }
                }}
                onMouseOut={(e) => {
                  if (currentCameraIndex !== index) {
                    e.target.style.background = "transparent";
                  }
                }}
              >
                <span style={{ fontSize: "16px" }}>
                  {camera.label.toLowerCase().includes("front") ? "🤳" : 
                   camera.label.toLowerCase().includes("back") ? "📱" : "📷"}
                </span>
                <span style={{
                  flex: 1,
                  overflow: "hidden",
                  textOverflow: "ellipsis",
                  whiteSpace: "nowrap"
                }}>
                  {camera.label || `Camera ${index + 1}`}
                </span>
                {currentCameraIndex === index && (
                  <span style={{ color: "#10b981", fontSize: "12px" }}>●</span>
                )}
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Control Panel */}
      {joined && (
        <div style={{
          position: "absolute",
          bottom: 28,
          left: "50%",
          transform: "translateX(-50%)",
          display: "flex",
          gap: 20,
          background: "rgba(15, 23, 42, 0.9)",
          padding: "20px 40px",
          borderRadius: "28px",
          backdropFilter: "blur(20px)",
          boxShadow: "0 12px 48px rgba(0,0,0,0.5)",
          border: "1px solid rgba(255,255,255,0.1)",
          zIndex: 30
        }}>
          <button 
            onClick={toggleMute}
            style={{
              width: 64,
              height: 64,
              borderRadius: "50%",
              border: "none",
              background: isMuted 
                ? "linear-gradient(135deg, #ef4444, #dc2626)" 
                : "linear-gradient(135deg, #475569, #334155)",
              color: "white",
              fontSize: 26,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              transition: "all 0.2s",
              boxShadow: isMuted ? "0 8px 24px rgba(239, 68, 68, 0.4)" : "0 4px 12px rgba(0,0,0,0.3)"
            }}
            onMouseOver={(e) => e.target.style.transform = "scale(1.1)"}
            onMouseOut={(e) => e.target.style.transform = "scale(1)"}
            title={isMuted ? "Unmute" : "Mute"}
          >
            {isMuted ? "🔇" : "🎤"}
          </button>

          <button 
            onClick={toggleCamera}
            style={{
              width: 64,
              height: 64,
              borderRadius: "50%",
              border: "none",
              background: isCameraOff 
                ? "linear-gradient(135deg, #ef4444, #dc2626)" 
                : "linear-gradient(135deg, #475569, #334155)",
              color: "white",
              fontSize: 26,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              transition: "all 0.2s",
              boxShadow: isCameraOff ? "0 8px 24px rgba(239, 68, 68, 0.4)" : "0 4px 12px rgba(0,0,0,0.3)"
            }}
            onMouseOver={(e) => e.target.style.transform = "scale(1.1)"}
            onMouseOut={(e) => e.target.style.transform = "scale(1)"}
            title={isCameraOff ? "Turn Camera On" : "Turn Camera Off"}
          >
            {isCameraOff ? "📷" : "📹"}
          </button>

          <button
            onClick={isRecording ? stopRecording : startRecording}
            style={{
              width: 64,
              height: 64,
              borderRadius: "50%",
              border: "none",
              background: isRecording
                ? "linear-gradient(135deg, #ef4444, #b91c1c)"
                : "linear-gradient(135deg, #f59e0b, #f97316)",
              color: "white",
              fontSize: 26,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              transition: "all 0.2s",
              boxShadow: isRecording
                ? "0 8px 24px rgba(239, 68, 68, 0.5)"
                : "0 4px 14px rgba(245, 158, 11, 0.35)"
            }}
            onMouseOver={(e) => e.target.style.transform = "scale(1.1)"}
            onMouseOut={(e) => e.target.style.transform = "scale(1)"}
            title={isRecording ? "Stop Recording" : "Start Recording"}
          >
            {isRecording ? "⏹" : "⏺"}
          </button>

          <button 
            onClick={handleEndCall}
            style={{
              width: 64,
              height: 64,
              borderRadius: "50%",
              border: "none",
              background: "linear-gradient(135deg, #ef4444, #dc2626)",
              color: "white",
              fontSize: 26,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              transition: "all 0.2s",
              boxShadow: "0 8px 24px rgba(239, 68, 68, 0.4)"
            }}
            onMouseOver={(e) => {
              e.target.style.transform = "scale(1.1)";
              e.target.style.boxShadow = "0 12px 32px rgba(239, 68, 68, 0.6)";
            }}
            onMouseOut={(e) => {
              e.target.style.transform = "scale(1)";
              e.target.style.boxShadow = "0 8px 24px rgba(239, 68, 68, 0.4)";
            }}
            title="End Call"
          >
            📞
          </button>
        </div>
      )}

      {/* CSS Animations */}
      <style>{`
        @keyframes pulse {
          0%, 100% {
            opacity: 1;
            transform: scale(1);
          }
          50% {
            opacity: 0.8;
            transform: scale(1.05);
          }
        }
      `}</style>
    </div>
  );
}
