import React, { useEffect, useRef, useState } from "react";
import AgoraRTC from "agora-rtc-sdk-ng";
import axios from "axios";

const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

export default function CallPage() {
  const [joined, setJoined] = useState(false);
  const localTracksRef = useRef([]);

  const styles = {
    page: { textAlign: "center", marginTop: 20 },
    row: { display: "flex", justifyContent: "center", gap: 20 },
    box: { width: 400, height: 300, background: "black" },
    btn: { marginTop: 20, padding: "10px 20px" },
  };

  useEffect(() => {
    const handleUserPublished = async (user, mediaType) => {
      await client.subscribe(user, mediaType);
      if (mediaType === "video" && user.videoTrack) {
        user.videoTrack.play("remote-player");
      }
      if (mediaType === "audio" && user.audioTrack) {
        user.audioTrack.play();
      }
    };

    const clearRemote = () => {
      const el = document.getElementById("remote-player");
      if (el) el.innerHTML = "";
    };

    client.on("user-published", handleUserPublished);
    client.on("user-unpublished", clearRemote);
    client.on("user-left", clearRemote);

    return () => {
      client.removeAllListeners();
    };
  }, []);

  const joinChannel = async () => {
    try {
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
    }
  };

  const leaveChannel = async () => {
    try {
      await client.unpublish(localTracksRef.current);
      localTracksRef.current.forEach((t) => {
        try {
          t.stop();
          t.close();
        } catch {}
      });
      localTracksRef.current = [];

      await client.leave();
      setJoined(false);

      const remote = document.getElementById("remote-player");
      if (remote) remote.innerHTML = "";
    } catch (e) {
      console.error("Leave Error:", e.message);
    }
  };

  return (
    <div style={styles.page}>
      <h2>Agora Video Call</h2>

      <div style={styles.row}>
        <div id="local-player" style={styles.box}></div>
        <div id="remote-player" style={styles.box}></div>
      </div>

      {!joined ? (
        <button onClick={joinChannel} style={styles.btn}>
          Join Call
        </button>
      ) : (
        <button onClick={leaveChannel} style={styles.btn}>
          Leave Call
        </button>
      )}
    </div>
  );
}
