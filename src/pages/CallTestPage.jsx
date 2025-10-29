import React, { useEffect, useRef, useState, useMemo } from "react";
import AgoraRTC from "agora-rtc-sdk-ng";
import { 
  FiMic, 
  FiMicOff, 
  FiVideo, 
  FiVideoOff, 
  FiPhoneOff,
  FiUser,
  FiUsers,
  FiArrowLeft,
  FiMonitor
} from "react-icons/fi";
import { 
  MdScreenShare, 
  MdStopScreenShare,
  MdFlipCameraAndroid
} from "react-icons/md";
import { 
  HiOutlineViewGrid, 
  HiOutlineViewList 
} from "react-icons/hi";

const APP_ID = "e20a4d60afd8494eab490563ad2e61d1";

export default function CallPage() {
  // Mock params for demo
  const channelName = "demo-channel";
  const role = "host";
  const isHost = role === "host";

  const safeChannel = useMemo(() => {
    return (channelName || "default_channel")
      .replace(/[^a-zA-Z0-9_]/g, "")
      .slice(0, 63);
  }, [channelName]);

  const uid = useMemo(() => {
    return Math.floor(Math.random() * 1e6);
  }, []);

  // Refs
  const clientRef = useRef(null);
  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);
  const localTracksRef = useRef([]);

  // State
  const [localTracks, setLocalTracks] = useState([]);
  const [remoteUsers, setRemoteUsers] = useState([]);
  const [joined, setJoined] = useState(false);
  const [callStatus, setCallStatus] = useState("connecting");
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);
  const [isScreenSharing, setIsScreenSharing] = useState(false);
  const [currentCameraIndex, setCurrentCameraIndex] = useState(0);
  const [videoLayout, setVideoLayout] = useState("grid");
  const [participants, setParticipants] = useState([]);
  const [availableCameras, setAvailableCameras] = useState([]);
  const [showCameraList, setShowCameraList] = useState(false);

  // Initialize Agora client
  useEffect(() => {
    clientRef.current = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
  }, []);

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

  // Join channel
  useEffect(() => {
    if (!clientRef.current || availableCameras.length === 0) return;

    let mounted = true;
    const client = clientRef.current;

    async function joinChannel() {
      try {
        console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);
        
        await client.join(APP_ID, safeChannel, null, uid);
        console.log("Successfully joined channel");

        if (!mounted) return;
        setJoined(true);
        setCallStatus("connected");

        if (isHost) {
          await createAndPublishTracks();
        }

        setupRemoteUserHandlers();
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
          console.log("Audio track created");
        } catch (err) {
          console.warn("Could not create audio track:", err);
        }

        // Create video track with current camera
        try {
          const cameraDevice = availableCameras[currentCameraIndex];
          const videoTrack = await AgoraRTC.createCameraVideoTrack({
            cameraId: cameraDevice.deviceId,
            encoderConfig: "720p_1"
          });
          tracks.push(videoTrack);
          console.log("Video track created");

          if (localVideoRef.current) {
            videoTrack.play(localVideoRef.current, { fit: "cover" });
          }
        } catch (err) {
          console.error("Video track creation failed:", err);
          setIsCameraOff(true);
        }

        if (tracks.length > 0) {
          await client.publish(tracks);
          console.log("Tracks published");
        }

        setLocalTracks(tracks);
        localTracksRef.current = tracks;
      } catch (error) {
        console.error("Error creating/publishing tracks:", error);
      }
    };

    const setupRemoteUserHandlers = () => {
      client.on("user-published", async (user, mediaType) => {
        try {
          await client.subscribe(user, mediaType);
          console.log(`Subscribed to user ${user.uid} ${mediaType}`);

          if (mediaType === "video" && remoteVideoRef.current) {
            user.videoTrack?.play(remoteVideoRef.current, { fit: "cover" });
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
      cleanup();
    };
  }, [safeChannel, role, uid, availableCameras, currentCameraIndex]);

  const cleanup = async () => {
    const client = clientRef.current;
    if (!client) return;
    
    try {
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
      }

      setLocalTracks([]);
      localTracksRef.current = [];
      setRemoteUsers([]);
      setJoined(false);
    } catch (error) {
      console.error("Cleanup error:", error);
    }
  };

  const toggleMute = async () => {
    const audioTrack = localTracksRef.current.find(track => track.trackMediaType === 'audio');
    if (audioTrack) {
      await audioTrack.setEnabled(!isMuted);
      setIsMuted(!isMuted);
    }
  };

  const toggleCamera = async () => {
    const videoTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
    if (videoTrack) {
      await videoTrack.setEnabled(isCameraOff);
      setIsCameraOff(!isCameraOff);
    }
  };

  const switchToCamera = async (cameraIndex) => {
    if (cameraIndex === currentCameraIndex || availableCameras.length <= cameraIndex) return;

    try {
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
        
        const currentVideoTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
        if (currentVideoTrack) {
          await clientRef.current.unpublish(currentVideoTrack);
        }
        
        await clientRef.current.publish(screenTrack);
        
        const newTracks = localTracksRef.current.filter(track => track.trackMediaType !== 'video');
        newTracks.push(screenTrack);
        
        if (localVideoRef.current) {
          screenTrack.play(localVideoRef.current, { fit: "cover" });
        }
        
        setLocalTracks(newTracks);
        localTracksRef.current = newTracks;
        setIsScreenSharing(true);
        
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
        const screenTrack = localTracksRef.current.find(track => track.trackMediaType === 'video');
        if (screenTrack) {
          await clientRef.current.unpublish(screenTrack);
          screenTrack.stop();
          screenTrack.close();
        }
        
        const cameraDevice = availableCameras[currentCameraIndex];
        const videoTrack = await AgoraRTC.createCameraVideoTrack({
          cameraId: cameraDevice.deviceId,
          encoderConfig: "720p_1"
        });
        
        await clientRef.current.publish(videoTrack);
        
        const newTracks = localTracksRef.current.filter(track => track.trackMediaType !== 'video');
        newTracks.push(videoTrack);
        
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

  const handleEndCall = async () => {
    await cleanup();
    alert("Call ended");
  };

  const toggleLayout = () => {
    setVideoLayout(prev => prev === "grid" ? "focus" : "grid");
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white">
      {/* Header */}
      <header className="bg-gray-800 bg-opacity-80 backdrop-blur-lg border-b border-gray-700 px-6 py-4 shadow-xl">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <button 
              onClick={() => alert("Going back...")}
              className="p-2 hover:bg-gray-700 rounded-lg transition-colors"
            >
              <FiArrowLeft className="w-5 h-5" />
            </button>
            <div>
              <h1 className="text-xl font-semibold">Video Consultation</h1>
              <div className="flex items-center space-x-4 text-sm text-gray-300 mt-1">
                <span>Room: <strong className="text-blue-400">{safeChannel}</strong></span>
                <span>•</span>
                <span>ID: <strong className="text-blue-400">{uid}</strong></span>
                <span>•</span>
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                  callStatus === "connected" ? "bg-green-500 text-white" : 
                  callStatus === "connecting" ? "bg-yellow-500 text-black" : "bg-red-500 text-white"
                }`}>
                  {callStatus === "connected" ? "● Connected" : 
                   callStatus === "connecting" ? "○ Connecting..." : "✕ Error"}
                </span>
              </div>
            </div>
          </div>

          <div className="flex items-center space-x-4">
            <div className="flex items-center space-x-2 text-sm bg-gray-700 px-3 py-2 rounded-lg">
              <FiUsers className="w-4 h-4 text-blue-400" />
              <span>{participants.length + 1} participants</span>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <div className="flex h-[calc(100vh-80px)]">
        {/* Video Area */}
        <div className="flex-1 p-6">
          <div className={`h-full ${videoLayout === "grid" && remoteUsers.length > 0 ? "grid grid-cols-2 gap-4" : "flex flex-col gap-4"}`}>
            
            {/* Local Video */}
            <div className={`relative bg-black rounded-2xl overflow-hidden shadow-2xl border border-gray-700 ${
              videoLayout === "focus" && remoteUsers.length > 0 ? "h-1/3" : "flex-1"
            }`}>
              <div 
                ref={localVideoRef}
                className="w-full h-full bg-gradient-to-br from-gray-800 to-gray-900"
              />
              
              {/* Video Label */}
              <div className="absolute top-4 left-4 bg-black bg-opacity-70 backdrop-blur-sm px-4 py-2 rounded-lg text-sm font-medium border border-gray-600">
                <div className="flex items-center space-x-2">
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                  <span>You ({isHost ? "Doctor" : "Patient"})</span>
                </div>
                {(isCameraOff || isScreenSharing) && (
                  <div className="text-xs text-gray-400 mt-1">
                    {isCameraOff && "Camera Off"}
                    {isScreenSharing && "Screen Sharing"}
                  </div>
                )}
              </div>

              {/* Camera Selector */}
              {availableCameras.length > 1 && !isScreenSharing && (
                <div className="absolute top-4 right-4">
                  <button
                    onClick={() => setShowCameraList(!showCameraList)}
                    className="bg-black bg-opacity-70 backdrop-blur-sm p-2 rounded-lg hover:bg-opacity-90 transition-all border border-gray-600"
                    title="Switch Camera"
                  >
                    <MdFlipCameraAndroid className="w-5 h-5" />
                  </button>
                  
                  {showCameraList && (
                    <div className="absolute top-full right-0 mt-2 bg-gray-800 rounded-lg shadow-xl border border-gray-600 p-2 min-w-[250px] z-10">
                      <div className="text-xs text-gray-400 px-2 py-1 font-medium">Available Cameras</div>
                      {availableCameras.map((camera, index) => (
                        <button
                          key={camera.deviceId}
                          onClick={() => switchToCamera(index)}
                          className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                            currentCameraIndex === index
                              ? "bg-blue-600 text-white"
                              : "hover:bg-gray-700 text-gray-200"
                          }`}
                        >
                          <div className="flex items-center space-x-2">
                            <FiVideo className="w-4 h-4 flex-shrink-0" />
                            <span className="truncate">{camera.label || `Camera ${index + 1}`}</span>
                            {currentCameraIndex === index && (
                              <span className="ml-auto text-xs">●</span>
                            )}
                          </div>
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}
              
              {/* Camera Off Placeholder */}
              {(isCameraOff || localTracks.length === 0) && !isScreenSharing && (
                <div className="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
                  <div className="text-center">
                    <div className="w-20 h-20 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                      <FiUser className="w-10 h-10 text-gray-400" />
                    </div>
                    <p className="text-gray-300 font-medium">
                      {isCameraOff ? "Camera is off" : "Initializing camera..."}
                    </p>
                    <p className="text-xs text-gray-500 mt-2">
                      {availableCameras.length} camera(s) available
                    </p>
                  </div>
                </div>
              )}
            </div>

            {/* Remote Video */}
            {remoteUsers.length > 0 ? (
              <div className={`relative bg-black rounded-2xl overflow-hidden shadow-2xl border border-gray-700 ${
                videoLayout === "focus" ? "flex-1" : ""
              }`}>
                <div 
                  ref={remoteVideoRef}
                  className="w-full h-full bg-gradient-to-br from-gray-800 to-gray-900"
                />
                <div className="absolute top-4 left-4 bg-black bg-opacity-70 backdrop-blur-sm px-4 py-2 rounded-lg text-sm font-medium border border-gray-600">
                  <div className="flex items-center space-x-2">
                    <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span>{isHost ? "Patient" : "Doctor"} ({remoteUsers[0]?.uid})</span>
                  </div>
                </div>
              </div>
            ) : (
              videoLayout === "grid" && (
                <div className="relative bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden flex-1 flex items-center justify-center border border-gray-700 shadow-xl">
                  <div className="text-center">
                    <div className="w-24 h-24 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                      <FiUser className="w-12 h-12 text-gray-400" />
                    </div>
                    <h3 className="text-xl font-semibold mb-2">Waiting for participant</h3>
                    <p className="text-gray-400">
                      Waiting for {isHost ? "patient" : "doctor"} to join the call...
                    </p>
                    <div className="mt-4 flex items-center justify-center space-x-2">
                      <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{animationDelay: '0ms'}}></div>
                      <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{animationDelay: '150ms'}}></div>
                      <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style={{animationDelay: '300ms'}}></div>
                    </div>
                  </div>
                </div>
              )
            )}
          </div>
        </div>

        {/* Sidebar - Participants */}
        <div className="w-80 bg-gray-800 bg-opacity-80 backdrop-blur-lg border-l border-gray-700 p-4 shadow-xl">
          <h3 className="font-semibold mb-4 flex items-center text-lg">
            <FiUsers className="w-5 h-5 mr-2 text-blue-400" />
            Participants <span className="ml-auto text-sm text-gray-400">({participants.length + 1})</span>
          </h3>
          <div className="space-y-2">
            {/* Local user */}
            <div className="flex items-center space-x-3 p-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg">
              <div className="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-sm font-bold">
                {isHost ? "D" : "P"}
              </div>
              <div className="flex-1">
                <p className="text-sm font-semibold">You ({isHost ? "Doctor" : "Patient"})</p>
                <p className="text-xs text-blue-200">ID: {uid}</p>
              </div>
              <div className="w-2 h-2 bg-green-400 rounded-full shadow-lg shadow-green-400/50"></div>
            </div>
            
            {/* Remote participants */}
            {participants.map((participant) => (
              <div key={participant.uid} className="flex items-center space-x-3 p-3 rounded-xl bg-gray-700 hover:bg-gray-650 transition-colors">
                <div className="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-sm font-bold">
                  {participant.role.charAt(0)}
                </div>
                <div className="flex-1">
                  <p className="text-sm font-medium">{participant.role}</p>
                  <p className="text-xs text-gray-400">ID: {participant.uid}</p>
                </div>
                <div className="w-2 h-2 bg-green-500 rounded-full shadow-lg shadow-green-500/50"></div>
              </div>
            ))}
          </div>

          {/* Camera Info */}
          <div className="mt-6 p-4 bg-gray-700 rounded-xl">
            <h4 className="text-sm font-semibold mb-3 flex items-center">
              <FiMonitor className="w-4 h-4 mr-2 text-blue-400" />
              Camera Info
            </h4>
            <div className="space-y-2 text-xs text-gray-300">
              <div className="flex justify-between">
                <span>Total Cameras:</span>
                <span className="font-semibold">{availableCameras.length}</span>
              </div>
              <div className="flex justify-between">
                <span>Active Camera:</span>
                <span className="font-semibold">{currentCameraIndex + 1}</span>
              </div>
              {availableCameras[currentCameraIndex] && (
                <div className="mt-2 pt-2 border-t border-gray-600">
                  <p className="text-gray-400 mb-1">Current:</p>
                  <p className="font-mono text-xs truncate">
                    {availableCameras[currentCameraIndex].label || `Camera ${currentCameraIndex + 1}`}
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Controls */}
      <div className="fixed bottom-8 left-1/2 transform -translate-x-1/2 z-50">
        <div className="flex items-center space-x-3 bg-gray-800 bg-opacity-95 backdrop-blur-lg px-6 py-4 rounded-2xl shadow-2xl border border-gray-700">
          {/* Mute/Unmute */}
          <button 
            onClick={toggleMute}
            className={`p-4 rounded-xl transition-all transform hover:scale-105 ${
              isMuted 
                ? "bg-red-500 hover:bg-red-600 shadow-lg shadow-red-500/50" 
                : "bg-gray-600 hover:bg-gray-500"
            }`}
            title={isMuted ? "Unmute" : "Mute"}
          >
            {isMuted ? <FiMicOff className="w-6 h-6" /> : <FiMic className="w-6 h-6" />}
          </button>

          {/* Camera On/Off */}
          <button 
            onClick={toggleCamera}
            className={`p-4 rounded-xl transition-all transform hover:scale-105 ${
              isCameraOff 
                ? "bg-red-500 hover:bg-red-600 shadow-lg shadow-red-500/50" 
                : "bg-gray-600 hover:bg-gray-500"
            }`}
            title={isCameraOff ? "Turn On Camera" : "Turn Off Camera"}
          >
            {isCameraOff ? <FiVideoOff className="w-6 h-6" /> : <FiVideo className="w-6 h-6" />}
          </button>

          {/* Switch Camera */}
          <button 
            onClick={() => setShowCameraList(!showCameraList)}
            disabled={availableCameras.length <= 1}
            className={`p-4 rounded-xl transition-all transform hover:scale-105 ${
              availableCameras.length <= 1 
                ? "bg-gray-700 cursor-not-allowed opacity-50" 
                : "bg-gray-600 hover:bg-gray-500"
            }`}
            title={availableCameras.length <= 1 ? "Only one camera available" : "Switch Camera"}
          >
            <MdFlipCameraAndroid className="w-6 h-6" />
          </button>

          {/* Screen Share */}
          <button 
            onClick={toggleScreenShare}
            className={`p-4 rounded-xl transition-all transform hover:scale-105 ${
              isScreenSharing 
                ? "bg-blue-500 hover:bg-blue-600 shadow-lg shadow-blue-500/50" 
                : "bg-gray-600 hover:bg-gray-500"
            }`}
            title={isScreenSharing ? "Stop Sharing" : "Share Screen"}
          >
            {isScreenSharing ? <MdStopScreenShare className="w-6 h-6" /> : <MdScreenShare className="w-6 h-6" />}
          </button>

          {/* Layout Toggle */}
          <button 
            onClick={toggleLayout}
            disabled={remoteUsers.length === 0}
            className={`p-4 rounded-xl transition-all transform hover:scale-105 ${
              remoteUsers.length === 0 
                ? "bg-gray-700 cursor-not-allowed opacity-50" 
                : "bg-gray-600 hover:bg-gray-500"
            }`}
            title="Change Layout"
          >
            {videoLayout === "grid" ? <HiOutlineViewList className="w-6 h-6" /> : <HiOutlineViewGrid className="w-6 h-6" />}
          </button>

          {/* End Call */}
          <button 
            onClick={handleEndCall}
            className="p-4 rounded-xl bg-red-500 hover:bg-red-600 transition-all transform hover:scale-105 shadow-lg shadow-red-500/50"
            title="End Call"
          >
            <FiPhoneOff className="w-6 h-6" />
          </button>
        </div>
      </div>
    </div>
  );
}