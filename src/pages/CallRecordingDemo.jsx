import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import AgoraRTC from "agora-rtc-sdk-ng";
import axios from "axios";

const CANVAS_WIDTH = 1280;
const CANVAS_HEIGHT = 720;

const CallRecordingDemo = () => {
  const patientVideoRef = useRef(null);
  const doctorVideoRef = useRef(null);
  const canvasRef = useRef(null);
  const recorderRef = useRef(null);
  const animationFrameRef = useRef(null);
  const recordedChunksRef = useRef([]);
  const compositeStreamRef = useRef(null);
  const audioContextRef = useRef(null);
  const clientRef = useRef(AgoraRTC.createClient({ mode: "rtc", codec: "vp8" }));
  const localMicRef = useRef(null);
  const localCamRef = useRef(null);
  const remoteVideoTrackRef = useRef(null);
  const remoteAudioTrackRef = useRef(null);

  const [initializing, setInitializing] = useState(true);
  const [error, setError] = useState("");
  const [permissionIssue, setPermissionIssue] = useState(false);
  const [isRecording, setIsRecording] = useState(false);
  const [recordingUrl, setRecordingUrl] = useState("");
  const [uploadStatus, setUploadStatus] = useState("idle");
  const [uploadResponse, setUploadResponse] = useState(null);
  const queryChannel = useMemo(() => new URLSearchParams(window.location.search).get("channel"), []);
  const [callId, setCallId] = useState(queryChannel || "demo-call");
  const [joinError, setJoinError] = useState("");
  const [localReady, setLocalReady] = useState(false);
  const [remoteReady, setRemoteReady] = useState(false);

  const stopStreams = useCallback(() => {
    [localMicRef.current, localCamRef.current].forEach((track) => {
      try {
        track?.stop();
        track?.close?.();
      } catch (err) {
        console.warn("Failed to stop Agora track", err);
      }
    });
    localMicRef.current = null;
    localCamRef.current = null;
  }, []);

  const cleanupRecordingResources = useCallback(() => {
    if (animationFrameRef.current) {
      cancelAnimationFrame(animationFrameRef.current);
      animationFrameRef.current = null;
    }
    compositeStreamRef.current?.getTracks().forEach((track) => track.stop());
    compositeStreamRef.current = null;
    const ctx = audioContextRef.current;
    if (ctx?.audioContext && ctx.audioContext.state !== "closed") {
      ctx.audioContext.close().catch(() => {});
    }
    audioContextRef.current = null;
  }, []);

  const leaveAgora = useCallback(async () => {
    stopRecording();
    const client = clientRef.current;
    try {
      remoteVideoTrackRef.current?.stop?.();
      remoteAudioTrackRef.current?.stop?.();
    } catch (err) {
      console.warn("Failed to stop remote tracks", err);
    }
    remoteVideoTrackRef.current = null;
    remoteAudioTrackRef.current = null;
    setRemoteReady(false);
    stopStreams();
    if (client && client.connectionState === "CONNECTED") {
      try {
        await client.leave();
      } catch (err) {
        console.warn("Failed to leave Agora", err);
      }
    }
    setLocalReady(false);
  }, [stopStreams]);

  const handleUserPublished = useCallback(async (user, mediaType) => {
    if (!clientRef.current) return;
    try {
      await clientRef.current.subscribe(user, mediaType);
      if (mediaType === "video" && user.videoTrack) {
        remoteVideoTrackRef.current = user.videoTrack;
        user.videoTrack.play(doctorVideoRef.current, { fit: "cover" });
        setRemoteReady(true);
      }
      if (mediaType === "audio" && user.audioTrack) {
        remoteAudioTrackRef.current = user.audioTrack;
        user.audioTrack.play();
      }
    } catch (err) {
      console.error("Error subscribing to remote user", err);
    }
  }, []);

  const handleUserUnpublished = useCallback((user, mediaType) => {
    if (mediaType === "video" && remoteVideoTrackRef.current) {
      remoteVideoTrackRef.current = null;
      setRemoteReady(false);
    }
    if (mediaType === "audio" && remoteAudioTrackRef.current) {
      remoteAudioTrackRef.current = null;
    }
  }, []);

  const handleUserLeft = useCallback((user) => {
    if (remoteVideoTrackRef.current) {
      remoteVideoTrackRef.current = null;
      setRemoteReady(false);
    }
    if (remoteAudioTrackRef.current) {
      remoteAudioTrackRef.current = null;
    }
  }, []);

  const setupAgora = useCallback(async () => {
    const channel = callId || "demo-call";
    const appId = import.meta.env.VITE_AGORA_APP_ID;
    if (!appId) {
      setError("Agora App ID is missing. Please configure VITE_AGORA_APP_ID.");
      return;
    }
    setError("");
    setJoinError("");
    setPermissionIssue(false);
    setLocalReady(false);
    setRemoteReady(false);
    setInitializing(true);
    try {
      stopStreams();
      const uid = Math.floor(Math.random() * 1e6);
      await clientRef.current.join(appId, channel, null, uid);
      const [mic, cam] = await AgoraRTC.createMicrophoneAndCameraTracks();
      localMicRef.current = mic;
      localCamRef.current = cam;
      mic.setEnabled(true);
      cam.setEnabled(true);
      await mic.play();
      await cam.play(patientVideoRef.current);
      setLocalReady(true);
      await clientRef.current.publish([mic, cam]);
      setInitializing(false);
    } catch (err) {
      console.error("Agora join failed", err);
      const blocked = err?.name === "NotAllowedError";
      setJoinError(blocked
        ? "Camera/microphone access blocked. Allow permissions from the browser and retry."
        : err.message || "Unable to join Agora call.");
      setPermissionIssue(blocked);
      setInitializing(false);
    }
  }, [callId, stopStreams]);

  useEffect(() => {
    setupAgora();
    const client = clientRef.current;
    client.on("user-published", handleUserPublished);
    client.on("user-unpublished", handleUserUnpublished);
    client.on("user-left", handleUserLeft);

    return () => {
      client.off("user-published", handleUserPublished);
      client.off("user-unpublished", handleUserUnpublished);
      client.off("user-left", handleUserLeft);
      leaveAgora();
    };
  }, [handleUserLeft, handleUserPublished, handleUserUnpublished, leaveAgora, setupAgora]);

  useEffect(() => {
    return () => {
      if (recordingUrl) {
        URL.revokeObjectURL(recordingUrl);
      }
    };
  }, [recordingUrl]);

  const drawFrame = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    ctx.fillStyle = "#020617";
    ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    const patientVideo = patientVideoRef.current;
    if (patientVideo && patientVideo.readyState >= 2) {
      ctx.drawImage(patientVideo, 0, 0, CANVAS_WIDTH / 2, CANVAS_HEIGHT);
    }
    const doctorVideo = doctorVideoRef.current;
    if (doctorVideo && doctorVideo.readyState >= 2) {
      ctx.drawImage(doctorVideo, CANVAS_WIDTH / 2, 0, CANVAS_WIDTH / 2, CANVAS_HEIGHT);
    }

    animationFrameRef.current = requestAnimationFrame(drawFrame);
  }, []);

  const startRecording = () => {
    if (isRecording) return;
    if (!localCamRef.current || !localMicRef.current) {
      setError("Local media is not ready yet.");
      return;
    }

    if (recordingUrl) {
      URL.revokeObjectURL(recordingUrl);
      setRecordingUrl("");
    }
    setUploadStatus("idle");
    setUploadResponse(null);
    recordedChunksRef.current = [];
    drawFrame();

    const canvasStream = canvasRef.current.captureStream(30);
    const mixedStream = new MediaStream();
    canvasStream.getVideoTracks().forEach((track) => mixedStream.addTrack(track));

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    const localAudioTrack = localMicRef.current?.getMediaStreamTrack();
    const remoteAudioTrack = remoteAudioTrackRef.current?.getMediaStreamTrack();
    if (AudioContextClass) {
      const audioContext = new AudioContextClass();
      const destination = audioContext.createMediaStreamDestination();
      const sources = [];
      [localAudioTrack, remoteAudioTrack].forEach((track) => {
        if (!track) return;
        try {
          const stream = new MediaStream([track]);
          const sourceNode = audioContext.createMediaStreamSource(stream);
          sourceNode.connect(destination);
          sources.push(sourceNode);
        } catch (error) {
          console.warn("Audio mixing failed", error);
        }
      });
      destination.stream.getAudioTracks().forEach((track) => mixedStream.addTrack(track));
      audioContextRef.current = { audioContext, destination, sources };
    } else if (localAudioTrack) {
      mixedStream.addTrack(localAudioTrack.clone());
    }

    const options = { mimeType: "video/webm;codecs=vp8,opus" };
    const recorder = new MediaRecorder(mixedStream, options);
    recorder.ondataavailable = (event) => {
      if (event.data && event.data.size > 0) {
        recordedChunksRef.current.push(event.data);
      }
    };
    recorder.onstop = () => {
      cleanupRecordingResources();
      const blob = new Blob(recordedChunksRef.current, { type: recorder.mimeType });
      recordedChunksRef.current = [];
      const url = URL.createObjectURL(blob);
      setRecordingUrl(url);
      setIsRecording(false);
      uploadRecording(blob);
    };
    recorder.start(1000);
    recorderRef.current = recorder;
    compositeStreamRef.current = mixedStream;
    setIsRecording(true);
  };

  const stopRecording = () => {
    if (!recorderRef.current) return;
    try {
      recorderRef.current.stop();
    } catch (err) {
      console.error("Error stopping recorder", err);
      setError("Failed to stop recording.");
    }
  };

  const uploadRecording = async (blob) => {
    setUploadStatus("uploading");
    try {
      const formData = new FormData();
      formData.append("recording", blob, `call-demo-${Date.now()}.webm`);
      if (callId) {
        formData.append("call_id", callId);
      }

      const response = await axios.post(
        "https://snoutiq.com/backend/api/call-recordings/upload",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );

      setUploadStatus("uploaded");
      setUploadResponse(response.data);
    } catch (err) {
      console.error("Failed to upload recording", err);
      setUploadStatus("error");
      setError("Failed to upload recording to backend.");
    }
  };

  const displayedError = joinError || error;

  return (
    <div
      style={{
        minHeight: "100vh",
        background: "linear-gradient(135deg, #020617 0%, #0f172a 45%, #111827 100%)",
        color: "white",
        padding: 32,
        fontFamily: "Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
      }}
    >
      <canvas ref={canvasRef} width={CANVAS_WIDTH} height={CANVAS_HEIGHT} style={{ display: "none" }} />

      <div style={{ maxWidth: 1280, margin: "0 auto" }}>
        <header style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 24 }}>
          <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
            <div
              style={{
                width: 52,
                height: 52,
                borderRadius: 18,
                background: "linear-gradient(135deg, #3b82f6, #8b5cf6)",
                display: "grid",
                placeItems: "center",
                fontSize: 24,
                boxShadow: "0 10px 30px rgba(67, 56, 202, 0.45)",
              }}
            >
              🏥
            </div>
            <div>
              <h1 style={{ margin: 0, fontSize: 20 }}>Dual Camera Recording Lab</h1>
              <p style={{ margin: "4px 0 0", color: "#94a3b8" }}>Patient & Doctor tiles render locally to verify recording UX.</p>
            </div>
          </div>

          <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
            <input
              type="text"
              value={callId}
              onChange={(event) => setCallId(event.target.value)}
              placeholder="Call identifier (optional)"
              style={{
                padding: "10px 14px",
                borderRadius: 999,
                border: "1px solid rgba(255,255,255,0.25)",
                background: "rgba(15,23,42,0.6)",
                color: "white",
                minWidth: 200,
              }}
            />
            <button
              onClick={isRecording ? stopRecording : startRecording}
              disabled={initializing}
              style={{
                padding: "12px 20px",
                borderRadius: 999,
                border: "none",
                fontWeight: 600,
                fontSize: 14,
                cursor: initializing ? "not-allowed" : "pointer",
                color: "white",
                background: isRecording
                  ? "linear-gradient(135deg, #ef4444, #b91c1c)"
                  : "linear-gradient(135deg, #16a34a, #22c55e)",
                boxShadow: isRecording
                  ? "0 12px 30px rgba(239,68,68,0.35)"
                  : "0 12px 30px rgba(16,185,129,0.35)",
                opacity: initializing ? 0.6 : 1,
              }}
            >
              {initializing ? "Preparing cameras..." : isRecording ? "Stop Recording" : "Start Recording"}
            </button>
          </div>
        </header>

        {displayedError && (
          <div style={{
            marginBottom: 20,
            padding: 14,
            borderRadius: 12,
            background: "rgba(239,68,68,0.12)",
            border: "1px solid rgba(248,113,113,0.4)",
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            gap: 16,
          }}>
            <span>⚠️ {displayedError}</span>
            <button
              type="button"
              onClick={setupAgora}
              style={{
                padding: "8px 14px",
                borderRadius: 999,
                border: "none",
                fontWeight: 600,
                cursor: "pointer",
                background: "linear-gradient(135deg,#3b82f6,#6366f1)",
                color: "white",
              }}
            >
              {permissionIssue ? "Open camera prompt" : "Retry"}
            </button>
          </div>
        )}

        <div
          style={{
            display: "grid",
            gridTemplateColumns: "1fr 1fr",
            gap: 20,
            marginBottom: 24,
          }}
        >
          <VideoTile title="Patient" streamRef={patientVideoRef} accent="rgba(16,185,129,0.4)" ready={!initializing && localReady} />
          <VideoTile title="Doctor" streamRef={doctorVideoRef} accent="rgba(59,130,246,0.4)" ready={!initializing && remoteReady} />
        </div>

        <section
          style={{
            background: "rgba(2,6,23,0.65)",
            borderRadius: 24,
            padding: 24,
            border: "1px solid rgba(255,255,255,0.08)",
            display: "flex",
            gap: 24,
            alignItems: "center",
          }}
        >
          <div style={{ flex: 1 }}>
            <h2 style={{ margin: "0 0 8px", fontSize: 18 }}>Recording Output</h2>
            <p style={{ margin: 0, color: "#94a3b8" }}>
              We composite both video panes on a hidden canvas to ensure the final file mirrors the live layout. Audio from
              both streams (if available) is mixed before uploading to the backend.
            </p>
            <div style={{ display: "flex", gap: 20, marginTop: 16 }}>
              <StatusBadge label={isRecording ? "Recording in progress" : "Idle"} tone={isRecording ? "red" : "slate"} />
              <StatusBadge label={`Upload: ${uploadStatus}`} tone={uploadStatus === "uploaded" ? "green" : uploadStatus === "error" ? "red" : "slate"} />
            </div>
          </div>

          <div style={{ width: 280 }}>
            {recordingUrl ? (
              <video controls style={{ width: "100%", borderRadius: 18, border: "1px solid rgba(255,255,255,0.1)" }} src={recordingUrl} />
            ) : (
              <div style={{
                width: "100%",
                height: 160,
                borderRadius: 18,
                border: "1px dashed rgba(255,255,255,0.2)",
                display: "grid",
                placeItems: "center",
                color: "#94a3b8",
              }}>
                Recording preview will appear here
              </div>
            )}
          </div>
        </section>

        {uploadResponse && (
          <section
            style={{
              marginTop: 24,
              padding: 18,
              borderRadius: 18,
              background: "rgba(22,163,74,0.08)",
              border: "1px solid rgba(34,197,94,0.3)",
              color: "#bbf7d0",
            }}
          >
            <p style={{ margin: 0 }}>
              ✅ Stored in backend: <strong>{uploadResponse?.path}</strong>
            </p>
            {uploadResponse?.url && (
              <a href={uploadResponse.url} target="_blank" rel="noreferrer" style={{ color: "#86efac" }}>
                Open recording
              </a>
            )}
          </section>
        )}
      </div>
    </div>
  );
};

const StatusBadge = ({ label, tone }) => {
  const palette = {
    red: { bg: "rgba(239,68,68,0.15)", color: "#f87171" },
    green: { bg: "rgba(34,197,94,0.15)", color: "#86efac" },
    slate: { bg: "rgba(148,163,184,0.15)", color: "#cbd5f5" },
  };
  const colors = palette[tone] ?? palette.slate;
  return (
    <span style={{ padding: "8px 14px", borderRadius: 999, background: colors.bg, color: colors.color, fontSize: 13, fontWeight: 600 }}>
      {label}
    </span>
  );
};

const VideoTile = ({ title, streamRef, accent, ready }) => (
  <div
    style={{
      position: "relative",
      borderRadius: 28,
      border: `2px solid ${accent}`,
      overflow: "hidden",
      background: "rgba(2,6,23,0.6)",
      minHeight: 420,
      boxShadow: "0 25px 60px rgba(0,0,0,0.45)",
    }}
  >
    <video
      ref={streamRef}
      autoPlay
      playsInline
      muted
      style={{
        width: "100%",
        height: "100%",
        objectFit: "cover",
        filter: ready ? "none" : "blur(4px)",
        opacity: ready ? 1 : 0.7,
        transition: "filter 0.3s, opacity 0.3s",
      }}
    />
    <div
      style={{
        position: "absolute",
        bottom: 18,
        left: 18,
        padding: "8px 16px",
        borderRadius: 18,
        background: "rgba(15,23,42,0.75)",
        border: "1px solid rgba(255,255,255,0.12)",
        fontWeight: 600,
      }}
    >
      {title}
    </div>
    {!ready && (
      <div
        style={{
          position: "absolute",
          inset: 0,
          display: "grid",
          placeItems: "center",
          color: "#94a3b8",
          fontSize: 14,
          background: "rgba(2,6,23,0.75)",
        }}
      >
        Initializing camera…
      </div>
    )}
  </div>
);

export default CallRecordingDemo;
