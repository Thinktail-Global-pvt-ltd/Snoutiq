import React, { useEffect, useRef, useState } from "react";
import AgoraRTC from "agora-rtc-sdk-ng";
import axios from "axios";
// import "./CallPage.css"; // We'll create this CSS file

const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
const BACKEND_BASE_URL = import.meta.env.VITE_BACKEND_BASE_URL || "https://snoutiq.com/backend";

export default function CallPage() {
  const [joined, setJoined] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [users, setUsers] = useState([]);
  const [audioEnabled, setAudioEnabled] = useState(true);
  const [videoEnabled, setVideoEnabled] = useState(true);
  const [isRecording, setIsRecording] = useState(false);
  const [recordingUrl, setRecordingUrl] = useState("");
  const [recordingError, setRecordingError] = useState("");
  const [uploadStatus, setUploadStatus] = useState("");
  const [isUploading, setIsUploading] = useState(false);
  const [callIdentifier, setCallIdentifier] = useState("");
  const localTracksRef = useRef([]);
  const remoteTracksRef = useRef({ audio: null, video: null });
  const mediaRecorderRef = useRef(null);
  const recordedChunksRef = useRef([]);

  useEffect(() => {
    const handleUserPublished = async (user, mediaType) => {
      await client.subscribe(user, mediaType);
      if (mediaType === "video" && user.videoTrack) {
        remoteTracksRef.current.video = { track: user.videoTrack, uid: user.uid };
        // Update users state to trigger re-render
        setUsers(prev => [...prev.filter(u => u.uid !== user.uid), user]);
        user.videoTrack.play(`remote-player-${user.uid}`);
      }
      if (mediaType === "audio" && user.audioTrack) {
        remoteTracksRef.current.audio = { track: user.audioTrack, uid: user.uid };
        user.audioTrack.play();
      }
    };

    const handleUserUnpublished = (user, mediaType) => {
      if (mediaType === "video") {
        if (remoteTracksRef.current.video && remoteTracksRef.current.video.uid === user.uid) {
          remoteTracksRef.current.video = null;
        }
        setUsers(prev => prev.filter(u => u.uid !== user.uid));
      }
      if (mediaType === "audio" && remoteTracksRef.current.audio && remoteTracksRef.current.audio.uid === user.uid) {
        remoteTracksRef.current.audio = null;
      }
    };

    const handleUserLeft = (user) => {
      setUsers(prev => prev.filter(u => u.uid !== user.uid));
      if (remoteTracksRef.current.video && remoteTracksRef.current.video.uid === user.uid) {
        remoteTracksRef.current.video = null;
      }
      if (remoteTracksRef.current.audio && remoteTracksRef.current.audio.uid === user.uid) {
        remoteTracksRef.current.audio = null;
      }
    };

    client.on("user-published", handleUserPublished);
    client.on("user-unpublished", handleUserUnpublished);
    client.on("user-left", handleUserLeft);

    return () => {
      client.removeAllListeners();
      leaveChannel(); // Clean up on unmount
    };
  }, []);

  useEffect(() => {
    return () => {
      if (recordingUrl) {
        URL.revokeObjectURL(recordingUrl);
      }
    };
  }, [recordingUrl]);

  const stopRecording = () => {
    const recorder = mediaRecorderRef.current;
    if (!recorder || recorder.state === "inactive") {
      return;
    }
    recorder.stop();
  };

  const buildRecordingStream = () => {
    const recordingStream = new MediaStream();
    const localMic = localTracksRef.current[0];
    const localCam = localTracksRef.current[1];

    if (localMic) {
      const audioTrack = localMic.getMediaStreamTrack();
      if (audioTrack) {
        recordingStream.addTrack(audioTrack);
      }
    }

    if (remoteTracksRef.current.audio) {
      const audioTrack = remoteTracksRef.current.audio.track.getMediaStreamTrack();
      if (audioTrack) {
        recordingStream.addTrack(audioTrack);
      }
    }

    const preferredVideoTrack = remoteTracksRef.current.video?.track || localCam;
    if (preferredVideoTrack) {
      const videoTrack = preferredVideoTrack.getMediaStreamTrack();
      if (videoTrack) {
        recordingStream.addTrack(videoTrack);
      }
    }

    if (recordingStream.getTracks().length === 0) {
      return null;
    }

    return recordingStream;
  };

  const uploadRecording = async (blob) => {
    if (!blob) {
      return;
    }
    setIsUploading(true);
    setUploadStatus("Uploading...");
    try {
      const formData = new FormData();
      formData.append("recording", blob, `snoutiq-call-${Date.now()}.webm`);
      if (callIdentifier) {
        formData.append("call_identifier", callIdentifier);
        formData.append("channel_name", callIdentifier);
      }

      const response = await axios.post(
        `${BACKEND_BASE_URL}/api/call-recordings/upload`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      if (response.data?.path) {
        setUploadStatus(`Saved to ${response.data.path}`);
      } else {
        setUploadStatus("Uploaded to S3");
      }
      if (response.data?.url) {
        setRecordingUrl(response.data.url);
      }
    } catch (err) {
      console.error("Upload error", err);
      setRecordingError("Upload failed. Check console for details.");
      setUploadStatus("Upload failed");
    } finally {
      setIsUploading(false);
    }
  };

  const startRecording = () => {
    if (typeof window === "undefined" || typeof window.MediaRecorder === "undefined") {
      setRecordingError("Recording is not supported in this browser.");
      return;
    }

    if (!joined) {
      setRecordingError("Join the call before starting a recording.");
      return;
    }

    const recordingStream = buildRecordingStream();
    if (!recordingStream) {
      setRecordingError("No media tracks available yet. Try again in a moment.");
      return;
    }

    if (recordingUrl) {
      URL.revokeObjectURL(recordingUrl);
      setRecordingUrl("");
    }

    setRecordingError("");
    setUploadStatus("");
    recordedChunksRef.current = [];

    try {
      const recorder = new MediaRecorder(recordingStream, {
        mimeType: "video/webm;codecs=vp8,opus",
      });

      recorder.addEventListener("dataavailable", (event) => {
        if (event.data && event.data.size > 0) {
          recordedChunksRef.current.push(event.data);
        }
      });

      recorder.addEventListener("stop", () => {
        if (!recordedChunksRef.current.length) {
          setIsRecording(false);
          return;
        }
        const blob = new Blob(recordedChunksRef.current, { type: "video/webm" });
        const url = URL.createObjectURL(blob);
        setRecordingUrl(url);
        setIsRecording(false);
        mediaRecorderRef.current = null;
        uploadRecording(blob);
      });

      recorder.addEventListener("error", (event) => {
        console.error("Recorder error", event.error || event);
        setRecordingError("Recording failed. Please try again.");
        setIsRecording(false);
        mediaRecorderRef.current = null;
      });

      recorder.start();
      mediaRecorderRef.current = recorder;
      setIsRecording(true);
    } catch (err) {
      console.error("Failed to start recorder", err);
      setRecordingError("Unable to start recording on this device.");
      setIsRecording(false);
      mediaRecorderRef.current = null;
    }
  };

  const joinChannel = async () => {
    try {
      setLoading(true);
      setError("");

      // ✅ Laravel API call
      const res = await axios.post(`${BACKEND_BASE_URL}/api/agora/token`);
      const { appId, token, channelName, uid } = res.data;

      // ✅ Agora join
      await client.join(appId, channelName, token, uid);

      // ✅ Mic + Camera
      const [mic, cam] = await AgoraRTC.createMicrophoneAndCameraTracks();
      localTracksRef.current = [mic, cam];

      cam.play("local-player");
      await client.publish(localTracksRef.current);

      setCallIdentifier(channelName);
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

      stopRecording();
      await client.leave();
      setJoined(false);
      setUsers([]);
      remoteTracksRef.current = { audio: null, video: null };
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

      <div className="recording-controls">
        <button
          onClick={isRecording ? stopRecording : startRecording}
          disabled={!joined || loading}
          className={`control-btn recording-btn ${isRecording ? 'recording' : ''}`}
        >
          {isRecording ? 'Stop Recording' : 'Start Recording'}
        </button>
        {uploadStatus && (
          <p className="upload-status">
            {uploadStatus}
            {isUploading && "..."}
          </p>
        )}
        {recordingError && <p className="recording-error">{recordingError}</p>}
        {recordingUrl && (
          <a
            className="download-link"
            href={recordingUrl}
            download={`snoutiq-call-${Date.now()}.webm`}
          >
            Download Recording
          </a>
        )}
      </div>
    </div>
  );
}
