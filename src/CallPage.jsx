import React, { useEffect, useRef, useState } from "react";
import AgoraRTC from "agora-rtc-sdk-ng";
import axios from "axios";
// import "./CallPage.css"; // We'll create this CSS file

const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

export default function CallPage() {
  const [joined, setJoined] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [users, setUsers] = useState([]);
  const [audioEnabled, setAudioEnabled] = useState(true);
  const [videoEnabled, setVideoEnabled] = useState(true);
  const localTracksRef = useRef([]);

  useEffect(() => {
    const handleUserPublished = async (user, mediaType) => {
      await client.subscribe(user, mediaType);
      if (mediaType === "video" && user.videoTrack) {
        // Update users state to trigger re-render
        setUsers(prev => [...prev.filter(u => u.uid !== user.uid), user]);
        user.videoTrack.play(`remote-player-${user.uid}`);
      }
      if (mediaType === "audio" && user.audioTrack) {
        user.audioTrack.play();
      }
    };

    const handleUserUnpublished = (user, mediaType) => {
      if (mediaType === "video") {
        setUsers(prev => prev.filter(u => u.uid !== user.uid));
      }
    };

    const handleUserLeft = (user) => {
      setUsers(prev => prev.filter(u => u.uid !== user.uid));
    };

    client.on("user-published", handleUserPublished);
    client.on("user-unpublished", handleUserUnpublished);
    client.on("user-left", handleUserLeft);

    return () => {
      client.removeAllListeners();
      leaveChannel(); // Clean up on unmount
    };
  }, []);

  const joinChannel = async () => {
    try {
      setLoading(true);
      setError("");

      // ✅ Laravel API call
      const res = await axios.post("https://snoutiq.com/backend/api/agora/token");
      const { appId, token, channelName, uid } = res.data;

      // ✅ Agora join
      await client.join(appId, channelName, token, uid);

      // ✅ Mic + Camera
      const [mic, cam] = await AgoraRTC.createMicrophoneAndCameraTracks();
      localTracksRef.current = [mic, cam];

      cam.play("local-player");
      await client.publish(localTracksRef.current);

      setJoined(true);
    } catch (e) {
      console.error("Join Error:", e?.response?.data || e.message);
      setError("Failed to join the call. Please check your connection and try again.");
    } finally {
      setLoading(false);
    }
  };

  const leaveChannel = async () => {
    try {
      // Stop and close all local tracks
      localTracksRef.current.forEach(track => {
        try {
          track.stop();
          track.close();
        } catch (e) {
          console.error("Error closing track:", e);
        }
      });
      localTracksRef.current = [];

      await client.leave();
      setJoined(false);
      setUsers([]);
    } catch (e) {
      console.error("Leave Error:", e.message);
      setError("Error leaving the call");
    }
  };

  const toggleAudio = () => {
    if (localTracksRef.current[0]) {
      localTracksRef.current[0].setEnabled(!audioEnabled);
      setAudioEnabled(!audioEnabled);
    }
  };

  const toggleVideo = () => {
    if (localTracksRef.current[1]) {
      localTracksRef.current[1].setEnabled(!videoEnabled);
      setVideoEnabled(!videoEnabled);
    }
  };

  return (
    <div className="call-container">
      <div className="call-header">
        <h2>Video Call</h2>
        {error && <div className="error-message">{error}</div>}
      </div>

      <div className="video-container">
        <div className="local-video">
          <div id="local-player" className="video-box"></div>
          <div className="video-overlay">You</div>
        </div>
        
        <div className="remote-videos">
          {users.length === 0 ? (
            <div className="waiting-message">
              <p>Waiting for others to join...</p>
            </div>
          ) : (
            users.map(user => (
              <div key={user.uid} className="remote-video">
                <div id={`remote-player-${user.uid}`} className="video-box"></div>
                <div className="video-overlay">User {user.uid}</div>
              </div>
            ))
          )}
        </div>
      </div>

      <div className="controls">
        {!joined ? (
          <button 
            onClick={joinChannel} 
            disabled={loading}
            className={`join-btn ${loading ? 'loading' : ''}`}
          >
            {loading ? 'Joining...' : 'Join Call'}
          </button>
        ) : (
          <>
            <button 
              onClick={toggleAudio} 
              className={`control-btn ${!audioEnabled ? 'muted' : ''}`}
            >
              {audioEnabled ? 'Mute' : 'Unmute'}
            </button>
            <button 
              onClick={toggleVideo} 
              className={`control-btn ${!videoEnabled ? 'muted' : ''}`}
            >
              {videoEnabled ? 'Stop Video' : 'Start Video'}
            </button>
            <button onClick={leaveChannel} className="leave-btn">
              Leave Call
            </button>
          </>
        )}
      </div>
    </div>
  );
}