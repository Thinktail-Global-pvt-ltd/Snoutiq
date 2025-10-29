import React, { useEffect, useRef, useState, useMemo } from "react";
import { useParams, useSearchParams, useNavigate } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";
import { socket } from "./socket";

// Icons (you can use react-icons or any icon library)
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
  FiMonitor,
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

  // State
  const [localTracks, setLocalTracks] = useState([]);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("connecting");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);
  const [isScreenSharing, setIsScreenSharing] = useState(false);
  const [cameraType, setCameraType] = useState("front"); // 'front' or 'back'
  const [videoLayout, setVideoLayout] = useState("grid"); // 'grid' or 'focus'
  const [participants, setParticipants] = useState([]);

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
          videoTrack = await AgoraRTC.createCameraVideoTrack({
            cameraId: cameraType === "front" ? "default" : "back"
          });
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

            // Update participants list
            setParticipants(prev => [...prev, {
              uid: user.uid,
              role: user.uid === uid ? (isHost ? "Doctor" : "Patient") : (isHost ? "Patient" : "Doctor")
            }]);
          } catch (err) {
            console.error("Error subscribing to user:", err);
          }
        });

        client.on("user-unpublished", (user, mediaType) => {
          if (mediaType === "video") {
            setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
            setParticipants(prev => prev.filter(p => p.uid !== user.uid));
          }
        });

        client.on("user-left", (user) => {
          setRemoteUsers(prev => prev.filter(u => u.uid !== user.uid));
          setParticipants(prev => prev.filter(p => p.uid !== user.uid));
        });

        // Socket events for call ending
        socket.on("call-ended", handleRemoteEndCall);

      } catch (error) {
        console.error("Join channel error:", error);
        setCallStatus("error");
      }
    }

    joinChannel();

    return () => {
      mounted = false;
      socket.off("call-ended", handleRemoteEndCall);
      cleanup();
    };
  }, [safeChannel, role, uid, cameraType]);

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

  const switchCamera = async () => {
    if (localTracks[1]) {
      const videoTrack = localTracks[1];
      await videoTrack.stop();
      await videoTrack.close();
      
      try {
        const newCameraType = cameraType === "front" ? "back" : "front";
        const newVideoTrack = await AgoraRTC.createCameraVideoTrack({
          cameraId: newCameraType === "front" ? "default" : "back"
        });
        
        // Replace the video track in localTracks
        const newLocalTracks = [localTracks[0], newVideoTrack];
        setLocalTracks(newLocalTracks);
        
        // Publish new track
        await clientRef.current.unpublish(localTracks[1]);
        await clientRef.current.publish(newVideoTrack);
        
        // Play new track
        if (localVideoRef.current) {
          newVideoTrack.play(localVideoRef.current);
        }
        
        setCameraType(newCameraType);
      } catch (error) {
        console.error("Error switching camera:", error);
      }
    }
  };

  const toggleScreenShare = async () => {
    if (!isScreenSharing) {
      try {
        const screenTrack = await AgoraRTC.createScreenVideoTrack();
        await clientRef.current.unpublish(localTracks[1]);
        await clientRef.current.publish(screenTrack);
        
        const newLocalTracks = [localTracks[0], screenTrack];
        setLocalTracks(newLocalTracks);
        
        if (localVideoRef.current) {
          screenTrack.play(localVideoRef.current);
        }
        
        setIsScreenSharing(true);
        
        screenTrack.on("track-ended", () => {
          toggleScreenShare();
        });
      } catch (error) {
        console.error("Error sharing screen:", error);
      }
    } else {
      try {
        const videoTrack = await AgoraRTC.createCameraVideoTrack({
          cameraId: cameraType === "front" ? "default" : "back"
        });
        
        await clientRef.current.unpublish(localTracks[1]);
        await clientRef.current.publish(videoTrack);
        
        const newLocalTracks = [localTracks[0], videoTrack];
        setLocalTracks(newLocalTracks);
        
        if (localVideoRef.current) {
          videoTrack.play(localVideoRef.current);
        }
        
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
              <span>{participants.length} participants</span>
            </div>
            <div className="w-2 h-2 bg-green-500 rounded-full"></div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <div className="flex h-[calc(100vh-80px)]">
        {/* Video Area */}
        <div className={`flex-1 p-6 ${videoLayout === "grid" ? "grid grid-cols-2 gap-4" : ""}`}>
          {/* Local Video */}
          <div className={`relative bg-black rounded-xl overflow-hidden ${
            videoLayout === "focus" && remoteUsers.length > 0 ? "h-1/3" : "h-full"
          }`}>
            <div 
              ref={localVideoRef} 
              className="w-full h-full object-cover"
            />
            <div className="absolute bottom-4 left-4 bg-black bg-opacity-60 px-3 py-1 rounded-lg text-sm">
              You ({isHost ? "Doctor" : "Patient"}) {isCameraOff && "• Camera Off"}
            </div>
            
            {isCameraOff && (
              <div className="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div className="text-center">
                  <FiUser className="w-16 h-16 mx-auto mb-2 text-gray-400" />
                  <p className="text-gray-300">Camera is off</p>
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
                className="w-full h-full object-cover"
              />
              <div className="absolute bottom-4 left-4 bg-black bg-opacity-60 px-3 py-1 rounded-lg text-sm">
                {isHost ? "Patient" : "Doctor"} ({remoteUsers[0]?.uid})
              </div>
            </div>
          ) : (
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
          )}
        </div>

        {/* Sidebar - Participants */}
        {participants.length > 0 && (
          <div className="w-80 bg-gray-800 border-l border-gray-700 p-4">
            <h3 className="font-semibold mb-4 flex items-center">
              <FiUsers className="w-5 h-5 mr-2" />
              Participants ({participants.length})
            </h3>
            <div className="space-y-2">
              {participants.map((participant) => (
                <div key={participant.uid} className="flex items-center space-x-3 p-2 rounded-lg bg-gray-700">
                  <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-sm font-medium">
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
        )}
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
            className="p-3 rounded-full bg-gray-600 hover:bg-gray-500 transition-all"
            title="Switch Camera"
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
            className="p-3 rounded-full bg-gray-600 hover:bg-gray-500 transition-all"
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

      {/* Additional Controls */}
      <div className="fixed top-1/2 right-6 transform -translate-y-1/2 space-y-4">
        <button className="p-3 rounded-full bg-gray-700 hover:bg-gray-600 transition-all">
          <FiMessageSquare className="w-5 h-5" />
        </button>
        <button className="p-3 rounded-full bg-gray-700 hover:bg-gray-600 transition-all">
          <MdClosedCaption className="w-5 h-5" />
        </button>
        <button className="p-3 rounded-full bg-gray-700 hover:bg-gray-600 transition-all">
          <FiSettings className="w-5 h-5" />
        </button>
      </div>
    </div>
  );
}