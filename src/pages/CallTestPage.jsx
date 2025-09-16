// src/pages/CallPage.jsx
import React, { useEffect, useMemo, useRef, useState } from "react";
import { useParams, useSearchParams } from "react-router-dom";
import AgoraRTC from "agora-rtc-sdk-ng";

const APP_ID = "e20a4d60afd8494eab490563ad2e61d1"; // Agora App ID

export default function CallPage() {
  const { channelName } = useParams();
  const [qs] = useSearchParams();

  // sanitize channel name
  const safeChannel = useMemo(() => {
    return (channelName || "default_channel")
      .replace(/[^a-zA-Z0-9_]/g, "")
      .slice(0, 63);
  }, [channelName]);

  // unique uid
  const uid = useMemo(() => {
    const q = Number(qs.get("uid"));
    return Number.isFinite(q) ? q : Math.floor(Math.random() * 1e6);
  }, [qs]);

  const role = (qs.get("role") || "audience").toLowerCase();

  // refs
  const localRef = useRef(null);
  const remoteRef = useRef(null);

  const [client] = useState(() =>
    AgoraRTC.createClient({ mode: "rtc", codec: "vp8" })
  );
  const [localTracks, setLocalTracks] = useState([]);
  const [joined, setJoined] = useState(false);

  useEffect(() => {
    let mounted = true;

    async function joinChannel() {
      try {
        console.log(`Joining channel: ${safeChannel}, role=${role}, uid=${uid}`);

        await client.join(APP_ID, safeChannel, null, uid);

        // ✅ Only doctor publishes
        if (role === "host") {
          const mic = await AgoraRTC.createMicrophoneAudioTrack();
          const cam = await AgoraRTC.createCameraVideoTrack();
          if (!mounted) return;

          setLocalTracks([mic, cam]);
          cam.play(localRef.current);
          await client.publish([mic, cam]);
          console.log("✅ Doctor published tracks");
        }

        // Remote user handling
        client.on("user-published", async (user, mediaType) => {
          await client.subscribe(user, mediaType);
          console.log("📡 Subscribed to", user.uid, mediaType);

          if (mediaType === "video") {
            user.videoTrack.play(remoteRef.current);
          }
          if (mediaType === "audio") {
            user.audioTrack.play();
          }
        });

        client.on("user-unpublished", (user) => {
          console.log("❌ User unpublished:", user.uid);
          if (remoteRef.current) remoteRef.current.innerHTML = "";
        });

        setJoined(true);
      } catch (err) {
        console.error("❌ Agora join error:", err);
      }
    }

    joinChannel();

    return () => {
      mounted = false;
      localTracks.forEach((t) => t.close());
      client.leave();
    };
  }, [client, safeChannel, role, uid]);

  const handleMute = () => {
    if (localTracks[0]) {
      const mic = localTracks[0];
      mic.setEnabled(!mic.enabled);
      console.log(mic.enabled ? "🎤 Unmuted" : "🔇 Muted");
    }
  };

  const handleCamera = () => {
    if (localTracks[1]) {
      const cam = localTracks[1];
      cam.setEnabled(!cam.enabled);
      console.log(cam.enabled ? "📷 Camera On" : "📷 Camera Off");
    }
  };

  const handleLeave = async () => {
    localTracks.forEach((t) => t.close());
    await client.leave();
    setJoined(false);
    console.log("🚪 Left the channel");
  };

  return (
    <div style={{ padding: 20 }}>
      <h2>Agora Call</h2>
      <p>
        uid: <b>{uid}</b> · channel: <b>{safeChannel}</b> · role:{" "}
        <b>{role}</b>
      </p>

      <div style={{ display: "flex", gap: 20, marginTop: 20 }}>
        <div
          ref={localRef}
          style={{
            width: 320,
            height: 240,
            background: "#000",
            borderRadius: 8,
          }}
        />
        <div
          ref={remoteRef}
          style={{
            width: 320,
            height: 240,
            background: "#000",
            borderRadius: 8,
          }}
        />
      </div>

      {/* Controls only for doctor (host) */}
      {joined && role === "host" && (
        <div style={{ marginTop: 20 }}>
          <button onClick={handleMute} style={{ marginRight: 10 }}>
            🎤 Toggle Mute
          </button>
          <button onClick={handleCamera} style={{ marginRight: 10 }}>
            📷 Toggle Camera
          </button>
          <button onClick={handleLeave}>🚪 Leave</button>
        </div>
      )}
    </div>
  );
}
