// src/pages/AgoraOnlyPage.jsx
import { useMemo } from "react";
import { useParams, useSearchParams } from "react-router-dom";
import AgoraUIKit from "agora-react-uikit";

// 👉 App ID (isi project ka jisme RTC enabled ho)
const APP_ID = "88a602d093ed47d6b77a29726aa6c35e";

// ❗ Tokenless mode: App Certificate MUST be DISABLED for this project.
// Agar certificate ON hai, to yahan token string paste karo (e.g. "007eJx...").
// const TOKEN = "007eJx...";  // <- optional (only if certificate is ON)
const TOKEN = null; // tokenless (certificate disabled)

export default function AgoraOnlyPage() {
  const { id } = useParams();               // /agora/123  => id = "123"
  const [qs] = useSearchParams();

  // uid: ?uid=501 pass kar sakte ho, warna random
  const uid = useMemo(() => {
    const q = Number(qs.get("uid"));
    return Number.isFinite(q) ? q : Math.floor(Math.random() * 1e6);
  }, [qs]);

  // role: host (publish) | audience (subscribe-only)
  const role = (qs.get("role") || "host").toLowerCase();

  const channel = useMemo(() => `booking${id}`, [id]);

  const rtcProps = {
    appId: APP_ID,
    channel,
    uid,
    role,          // host/audience
    token: TOKEN,  // null for tokenless
  };

  return (
    <div style={{ height: "100vh" }}>
      <AgoraUIKit rtcProps={rtcProps} />
    </div>
  );
}
