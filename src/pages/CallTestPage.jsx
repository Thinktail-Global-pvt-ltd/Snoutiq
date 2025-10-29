import React, { useEffect, useRef, useState, useMemo } from "react";
import { useParams, useSearchParams, useNavigate } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";
import { socket } from "./socket";

// Icons
import { 
  FiMic, 
  FiMicOff, 
  FiVideo, 
  FiVideoOff, 
  FiPhone, 
  FiPhoneOff,
  FiUser,
  FiUsers,
  FiSettings,
  FiMessageSquare,
  FiArrowLeft
} from "react-icons/fi";
import { 
  MdScreenShare, 
  MdStopScreenShare,
  MdFlipCameraAndroid,
  MdClosedCaption
} from "react-icons/md";
import { 
  HiOutlineViewGrid, 
  HiOutlineViewList 
} from "react-icons/hi";

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
  const localTracksRef = useRef([]); // Additional ref for tracks

  // State
  const [localTracks, setLocalTracks] = useState([]);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("connecting");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);
  const [isScreenSharing, setIsScreenSharing] = useState(false);
  const [cameraType, setCameraType] = useState("front");
  const [videoLayout, setVideoLayout] = useState("grid");
  const [participants, setParticipants] = useState([]);
  const [availableCameras, setAvailableCameras] = useState([]);

  // Get available cameras
  useEffect(() => {
    const getCameras = async () => {
      try {
        const devices = await AgoraRTC.getCameras();
        setAvailableCameras(devices);
        console.log("Available cameras:", devices);
      } catch (error) {
        console.error("Error getting cameras:", error);
      }
    };
    getCameras();
  }, []);

  // Join channel effect - FIXED VERSION
  useEffect(() => {
    let mounted = true;
    const client = clientRef.current;

    async function joinChannel() {
      try {
        // Cleanup previous session
        if (client.connectionState === "CONNECTED" || client.connectionState === "CONNECTING") {
          console.log("Cleaning up previous session...");
          await cleanup();
        }

        console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);
        
        // Join the channel first
        await client.join(APP_ID, safeChannel, null, uid);
        console.log("Successfully joined channel");

        setJoined(true);
        setCallStatus("connected");

        // Create and publish tracks for host
        if (isHost) {
          await createAndPublishTracks();
        }

        // Setup remote user event handlers
        setupRemoteUserHandlers();

        // Socket events for call ending
        socket.on("call-ended", handleRemoteEndCall);

      } catch (error) {
        console.error("Join channel error:", error);
        setCallStatus("error");
      }
    }

    const createAndPublishTracks = async () => {
      try {
        const tracks = [];
        
        // Create audio track
        try {
          const audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
          tracks.push(audioTrack);
          console.log("Audio track created successfully");
        } catch (err) {
          console.warn("Could not create audio track:", err);
        }

        // Create video track
        try {
          let cameraConfig = {};
          
          // Use specific camera device if available
          if (availableCameras.length > 0) {
            const cameraDevice = cameraType === "front" 
              ? availableCameras.find(cam => cam.label.toLowerCase().includes('front')) || availableCameras[0]
              : availableCameras.find(cam => cam.label.toLowerCase().includes('back')) || availableCameras[availableCameras.length - 1];
            
            if (cameraDevice) {
              cameraConfig = {
                cameraId: cameraDevice.deviceId,
                encoderConfig: "720p_1"
              };
            }
          }

          const videoTrack = await AgoraRTC.createCameraVideoTrack(cameraConfig);
          tracks.push(videoTrack);
          console.log("Video track created successfully");

          // Play video track
          if (localVideoRef.current) {
            videoTrack.play(localVideoRef.current, { fit: "cover" });
            console.log("Video track playing on local video element");
          } else {
            console.warn("Local video ref not available");
          }

        } catch (err) {
          console.error("Video track creation failed:", err);
          setIsCameraOff(true);
        }

        // Publish tracks
        if (tracks.length > 0) {
          await clientRef.current.publish(tracks);
          console.log("Tracks published successfully");
        }

        // Update state and ref
        setLocalTracks(tracks);
        localTracksRef.current = tracks;

      } catch (error) {
        console.error("Error creating/publishing tracks:", error);
      }
    };

    const setupRemoteUserHandlers = () => {
      const client = clientRef.current;

      client.on("user-published", async (user, mediaType) => {
        try {
          await client.subscribe(user, mediaType);
          console.log(`Subscribed to user ${user.uid} ${mediaType}`);

          if (mediaType === "video") {
            if (remoteVideoRef.current) {
              user.videoTrack?.play(remoteVideoRef.current, { fit: "cover" });
            }
            
            setRemoteUsers(prev => {
              if (!prev.some(u => u.uid === user.uid)) {
                return [...prev, user];
              }
              return prev;
            });
          }
          
          if (mediaType === "audio") {
            user.audioTrack?.play();
          }

          // Update participants list
          setParticipants(prev => {
            if (!prev.some(p => p.uid === user.uid)) {
              return [...prev, {
                uid: user.uid,
                role: isHost ? "Patient" : "Doctor"
              }];
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
        setParticipants(prev => prev.filter(p => p.uid !== user.uid));
      });
    };

    joinChannel();

    return () => {
      mounted = false;
      socket.off("call-ended", handleRemoteEndCall);
    };
  }, [safeChannel, role, uid, cameraType, availableCameras]);

  // Improved cleanup function
  const cleanup = async () => {
    const client = clientRef.current;
    
    try {
      // Stop and close local tracks
      localTracksRef.current.forEach(track => {
        try {
          track.stop();
          track.close();
        } catch (err) {
          console.warn("Error closing track:", err);
        }
      });

      // Leave channel
      if (client.connectionState === "CONNECTED") {
        await client.leave();
        console.log("Left the channel");
      }

      // Clear states
      setLocalTracks([]);
      localTracksRef.current = [];
      setRemoteUsers([]);
      setJoined(false);
      
    } catch (error) {
      console.error("Cleanup error:", error);
    }
  };

  // Fixed toggle functions
  const toggleMute = async () => {
    if (localTracksRef.current.length > 0) {
      const audioTrack = localTracksRef.current.find(track => track.trackMediaType === 'audio');
      if (audioTrack) {
        await audioTrack.setEnabled(!isMuted);
        setIsMuted(!isMuted);
      }
    }
  };

  const toggleCamera = async () => {
    if (localTracksRef.current.length > 0) {
      const videoTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
      if (videoTrack) {
        await videoTrack.setEnabled(isCameraOff);
        setIsCameraOff(!isCameraOff);
      }
    }
  };

  const switchCamera = async () => {
    try {
      // Get current video track
      const currentVideoTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
      
      if (currentVideoTrack) {
        // Unpublish current video track
        await clientRef.current.unpublish(currentVideoTrack);
        
        // Stop and close current track
        currentVideoTrack.stop();
        currentVideoTrack.close();
        
        // Create new track with different camera
        const newCameraType = cameraType === "front" ? "back" : "front";
        let cameraConfig = {};
        
        if (availableCameras.length > 0) {
          const cameraDevice = newCameraType === "front" 
            ? availableCameras.find(cam => cam.label.toLowerCase().includes('front')) || availableCameras[0]
            : availableCameras.find(cam => cam.label.toLowerCase().includes('back')) || availableCameras[availableCameras.length - 1];
          
          if (cameraDevice) {
            cameraConfig = { cameraId: cameraDevice.deviceId };
          }
        }
        
        const newVideoTrack = await AgoraRTC.createCameraVideoTrack(cameraConfig);
        
        // Update tracks
        const newTracks = localTracksRef.current.filter(track => track.trackMediaType !== 'video');
        newTracks.push(newVideoTrack);
        
        // Publish new track
        await clientRef.current.publish(newVideoTrack);
        
        // Play new track
        if (localVideoRef.current) {
          newVideoTrack.play(localVideoRef.current, { fit: "cover" });
        }
        
        // Update state and ref
        setLocalTracks(newTracks);
        localTracksRef.current = newTracks;
        setCameraType(newCameraType);
        setIsCameraOff(false);
      }
    } catch (error) {
      console.error("Error switching camera:", error);
    }
  };

  const toggleScreenShare = async () => {
    if (!isScreenSharing) {
      try {
        const screenTrack = await AgoraRTC.createScreenVideoTrack({
          encoderConfig: "720p_1"
        });
        
        // Unpublish current video track
        const currentVideoTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
        if (currentVideoTrack) {
          await clientRef.current.unpublish(currentVideoTrack);
        }
        
        // Publish screen track
        await clientRef.current.publish(screenTrack);
        
        // Update tracks
        const newTracks = localTracksRef.current.filter(track => track.trackMediaType !== 'video');
        newTracks.push(screenTrack);
        
        // Play screen track
        if (localVideoRef.current) {
          screenTrack.play(localVideoRef.current, { fit: "cover" });
        }
        
        setLocalTracks(newTracks);
        localTracksRef.current = newTracks;
        setIsScreenSharing(true);
        
        // Handle screen share end
        if (screenTrack.on) {
          screenTrack.on("track-ended", () => {
            toggleScreenShare();
          });
        }
        
      } catch (error) {
        console.error("Error sharing screen:", error);
      }
    } else {
      try {
        // Stop screen share and switch back to camera
        const screenTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
        if (screenTrack) {
          await clientRef.current.unpublish(screenTrack);
          screenTrack.stop();
          screenTrack.close();
        }
        
        // Create camera track
        let cameraConfig = {};
        if (availableCameras.length > 0) {
          const cameraDevice = cameraType === "front" 
            ? availableCameras.find(cam => cam.label.toLowerCase().includes('front')) || availableCameras[0]
            : availableCameras.find(cam => cam.label.toLowerCase().includes('back')) || availableCameras[availableCameras.length - 1];
          
          if (cameraDevice) {
            cameraConfig = { cameraId: cameraDevice.deviceId };
          }
        }
        
        const videoTrack = await AgoraRTC.createCameraVideoTrack(cameraConfig);
        
        // Publish camera track
        await clientRef.current.publish(videoTrack);
        
        // Update tracks
        const newTracks = localTracksRef.current.filter(track => track.trackMediaType !== 'video');
        newTracks.push(videoTrack);
        
        // Play camera track
        if (localVideoRef.current) {
          videoTrack.play(localVideoRef.current, { fit: "cover" });
        }
        
        setLocalTracks(newTracks);
        localTracksRef.current = newTracks;
        setIsScreenSharing(false);
        
      } catch (error) {
        console.error("Error stopping screen share:", error);
      }
    }
  };

  const handleRemoteEndCall = () => {
    cleanup();
    navigateToPostCall();
  };

  const navigateToPostCall = () => {
    if (isHost && doctorId && patientId) {
      navigate(`/prescription/${doctorId}/${patientId}`);
    } else if (!isHost && doctorId && patientId) {
      navigate(`/rating/${doctorId}/${patientId}`);
    } else {
      navigate(isHost ? "/doctor-dashboard" : "/patient-dashboard");
    }
  };

  const handleEndCall = async () => {
    await cleanup();
    socket.emit("call-ended", { channel: safeChannel });
    navigateToPostCall();
  };

  const toggleLayout = () => {
    setVideoLayout(prev => prev === "grid" ? "focus" : "grid");
  };

  return (
    <div className="min-h-screen bg-gray-900 text-white">
      {/* Header */}
      <header className="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <button 
              onClick={() => navigate(-1)}
              className="p-2 hover:bg-gray-700 rounded-lg transition-colors"
            >
              <FiArrowLeft className="w-5 h-5" />
            </button>
            <div>
              <h1 className="text-xl font-semibold">Video Consultation</h1>
              <div className="flex items-center space-x-4 text-sm text-gray-300">
                <span>Room: <strong>{safeChannel}</strong></span>
                <span>•</span>
                <span>ID: <strong>{uid}</strong></span>
                <span>•</span>
                <span className={`px-2 py-1 rounded-full text-xs ${
                  callStatus === "connected" ? "bg-green-500" : 
                  callStatus === "connecting" ? "bg-yellow-500" : "bg-red-500"
                }`}>
                  {callStatus === "connected" ? "Connected" : 
                   callStatus === "connecting" ? "Connecting..." : "Error"}
                </span>
              </div>
            </div>
          </div>

          <div className="flex items-center space-x-4">
            <div className="flex items-center space-x-2 text-sm">
              <FiUsers className="w-4 h-4" />
              <span>{participants.length + 1} participants</span>
            </div>
            <div className="w-2 h-2 bg-green-500 rounded-full"></div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <div className="flex h-[calc(100vh-80px)]">
        {/* Video Area */}
        <div className={`flex-1 p-6 ${videoLayout === "grid" && remoteUsers.length > 0 ? "grid grid-cols-2 gap-4" : ""}`}>
          
          {/* Local Video - Always show */}
          <div className={`relative bg-black rounded-xl overflow-hidden ${
            videoLayout === "focus" && remoteUsers.length > 0 ? "h-1/3 mb-4" : "h-full"
          }`}>
            <div 
              ref={localVideoRef}
              className="w-full h-full bg-gray-800"
            />
            <div className="absolute bottom-4 left-4 bg-black bg-opacity-60 px-3 py-1 rounded-lg text-sm">
              You ({isHost ? "Doctor" : "Patient"}) 
              {isCameraOff && " • Camera Off"}
              {isScreenSharing && " • Screen Sharing"}
            </div>
            
            {(isCameraOff || localTracks.length === 0) && !isScreenSharing && (
              <div className="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div className="text-center">
                  <FiUser className="w-16 h-16 mx-auto mb-2 text-gray-400" />
                  <p className="text-gray-300">
                    {isCameraOff ? "Camera is off" : "Initializing camera..."}
                  </p>
                </div>
              </div>
            )}
          </div>

          {/* Remote Video */}
          {remoteUsers.length > 0 ? (
            <div className={`relative bg-black rounded-xl overflow-hidden ${
              videoLayout === "focus" ? "h-2/3" : "h-full"
            }`}>
              <div 
                ref={remoteVideoRef}
                className="w-full h-full bg-gray-800"
              />
              <div className="absolute bottom-4 left-4 bg-black bg-opacity-60 px-3 py-1 rounded-lg text-sm">
                {isHost ? "Patient" : "Doctor"} ({remoteUsers[0]?.uid})
              </div>
            </div>
          ) : (
            videoLayout === "grid" && (
              <div className="relative bg-gray-800 rounded-xl overflow-hidden h-full flex items-center justify-center">
                <div className="text-center">
                  <div className="w-20 h-20 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <FiUser className="w-10 h-10 text-gray-400" />
                  </div>
                  <h3 className="text-xl font-medium mb-2">Waiting for participant</h3>
                  <p className="text-gray-400">
                    Waiting for {isHost ? "patient" : "doctor"} to join...
                  </p>
                </div>
              </div>
            )
          )}
        </div>

        {/* Sidebar - Participants */}
        <div className="w-80 bg-gray-800 border-l border-gray-700 p-4">
          <h3 className="font-semibold mb-4 flex items-center">
            <FiUsers className="w-5 h-5 mr-2" />
            Participants ({participants.length + 1})
          </h3>
          <div className="space-y-2">
            {/* Local user */}
            <div className="flex items-center space-x-3 p-2 rounded-lg bg-gray-700">
              <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm font-medium">
                {isHost ? "D" : "P"}
              </div>
              <div className="flex-1">
                <p className="text-sm font-medium">You ({isHost ? "Doctor" : "Patient"})</p>
                <p className="text-xs text-gray-400">ID: {uid}</p>
              </div>
              <div className="w-2 h-2 bg-green-500 rounded-full"></div>
            </div>
            
            {/* Remote participants */}
            {participants.map((participant) => (
              <div key={participant.uid} className="flex items-center space-x-3 p-2 rounded-lg bg-gray-700">
                <div className="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-sm font-medium">
                  {participant.role.charAt(0)}
                </div>
                <div className="flex-1">
                  <p className="text-sm font-medium">{participant.role}</p>
                  <p className="text-xs text-gray-400">ID: {participant.uid}</p>
                </div>
                <div className="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Controls */}
      <div className="fixed bottom-6 left-1/2 transform -translate-x-1/2">
        <div className="flex items-center space-x-4 bg-gray-800 bg-opacity-90 px-6 py-3 rounded-2xl shadow-lg">
          {/* Mute/Unmute */}
          <button 
            onClick={toggleMute}
            className={`p-3 rounded-full transition-all ${
              isMuted ? "bg-red-500 hover:bg-red-600" : "bg-gray-600 hover:bg-gray-500"
            }`}
          >
            {isMuted ? (
              <FiMicOff className="w-6 h-6" />
            ) : (
              <FiMic className="w-6 h-6" />
            )}
          </button>

          {/* Camera On/Off */}
          <button 
            onClick={toggleCamera}
            className={`p-3 rounded-full transition-all ${
              isCameraOff ? "bg-red-500 hover:bg-red-600" : "bg-gray-600 hover:bg-gray-500"
            }`}
          >
            {isCameraOff ? (
              <FiVideoOff className="w-6 h-6" />
            ) : (
              <FiVideo className="w-6 h-6" />
            )}
          </button>

          {/* Switch Camera */}
          <button 
            onClick={switchCamera}
            disabled={availableCameras.length <= 1}
            className={`p-3 rounded-full transition-all ${
              availableCameras.length <= 1 ? "bg-gray-700 cursor-not-allowed" : "bg-gray-600 hover:bg-gray-500"
            }`}
            title={availableCameras.length <= 1 ? "Only one camera available" : "Switch Camera"}
          >
            <MdFlipCameraAndroid className="w-6 h-6" />
          </button>

          {/* Screen Share */}
          <button 
            onClick={toggleScreenShare}
            className={`p-3 rounded-full transition-all ${
              isScreenSharing ? "bg-blue-500 hover:bg-blue-600" : "bg-gray-600 hover:bg-gray-500"
            }`}
          >
            {isScreenSharing ? (
              <MdStopScreenShare className="w-6 h-6" />
            ) : (
              <MdScreenShare className="w-6 h-6" />
            )}
          </button>

          {/* Layout Toggle */}
          <button 
            onClick={toggleLayout}
            disabled={remoteUsers.length === 0}
            className={`p-3 rounded-full transition-all ${
              remoteUsers.length === 0 ? "bg-gray-700 cursor-not-allowed" : "bg-gray-600 hover:bg-gray-500"
            }`}
          >
            {videoLayout === "grid" ? (
              <HiOutlineViewList className="w-6 h-6" />
            ) : (
              <HiOutlineViewGrid className="w-6 h-6" />
            )}
          </button>

          {/* End Call */}
          <button 
            onClick={handleEndCall}
            className="p-3 rounded-full bg-red-500 hover:bg-red-600 transition-all"
          >
            <FiPhoneOff className="w-6 h-6" />
          </button>
        </div>
      </div>

      {/* Debug info - remove in production */}
      <div className="fixed top-20 left-6 bg-black bg-opacity-70 p-3 rounded-lg text-xs">
        <div>Local Tracks: {localTracks.length}</div>
        <div>Remote Users: {remoteUsers.length}</div>
        <div>Cameras: {availableCameras.length}</div>
        <div>Camera Type: {cameraType}</div>
      </div>
    </div>
  );
}