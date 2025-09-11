import React, { useEffect, useRef, useState } from "react";
import axios from "axios";
import AgoraRTC from "agora-rtc-sdk-ng";

const API_BASE = "http://127.0.0.1:8000";
const POLL_MS = 2000;
const AGORA_APP_ID = "b13636f3f07448e2bf6778f5bc2c506f";          // <-- from .env
const RAZORPAY_KEY_ID = "rzp_live_RGBIfjaGxq1Ma4";

export default function CallTestPage() {
  const [session, setSession] = useState(null);
  const [logs, setLogs] = useState([]);
  const [paymentSessionId, setPaymentSessionId] = useState(null);
  const [isPolling, setIsPolling] = useState(false);
  const [joined, setJoined] = useState(false);

  const pollRef = useRef(null);

  // Agora refs
  const clientRef = useRef(null);
  const localTracksRef = useRef({ mic: null, cam: null });
  const remoteUsersRef = useRef(new Map()); // uid -> { videoTrack }

  const log = (msg) =>
    setLogs((prev) => [...prev, `[${new Date().toLocaleTimeString()}] ${msg}`]);

  // ---------- Polling helpers ----------
  const stopPolling = () => {
    if (pollRef.current) {
      clearInterval(pollRef.current);
      pollRef.current = null;
      setIsPolling(false);
      log("⏹️ Polling stopped");
    }
  };

  const startPolling = (sid) => {
    stopPolling();
    setIsPolling(true);
    log(`🔁 Starting polling for session ${sid} every ${POLL_MS}ms`);
    pollRef.current = setInterval(async () => {
      try {
        const res = await axios.get(`${API_BASE}/api/call/${sid}`);
        const s = res.data?.session ?? res.data;
        log(`📥 Status: ${s.status}, Payment: ${s.payment_status}`);

        if (!joined && s.status === "accepted" && s.payment_status !== "paid") {
          setPaymentSessionId(s.id); // show Pay Now
          log("✅ Doctor accepted detected — showing Pay Now");
          // (choice) stop poll here; ya chalta rehne do
          // stopPolling();
        }

        if (s.payment_status === "paid" && !joined) {
          // in case backend marked paid (e.g., another tab flow)
          await joinAgora(s.id, s.channel_name);
        }
      } catch (e) {
        log("❌ Poll error: " + e.message);
      }
    }, POLL_MS);
  };

  useEffect(() => {
    return () => {
      stopPolling();
      leaveAgora();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // ---------- 1) Patient: create call ----------
  const createCall = async () => {
    try {
      const res = await axios.post(`${API_BASE}/api/call/create`, {
        patient_id: 101,
      });
      setSession(res.data);
      setPaymentSessionId(null);
      setJoined(false);
      log("🆕 Patient created call: " + JSON.stringify(res.data));
      startPolling(res.data.session_id);
    } catch (e) {
      log("Create call error: " + e.message);
    }
  };

  // ---------- 2) Doctor: accept (testing) ----------
  const acceptCall = async () => {
    if (!session) return alert("Create a call first!");
    try {
      const res = await axios.post(
        `${API_BASE}/api/call/${session.session_id}/accept`,
        { doctor_id: 501 }
      );
      log("👨‍⚕️ Doctor accepted API response: " + JSON.stringify(res.data));
    } catch (e) {
      log("Accept call error: " + e.message);
    }
  };

  // ---------- 3) Razorpay: open on user click, then join Agora ----------
  const openRazorpayPayment = async (sid) => {
    try {
      const orderRes = await axios.post(`${API_BASE}/api/create-order`);

      if (!window.Razorpay) {
        log(
          "⚠️ Razorpay script not loaded. Add <script src='https://checkout.razorpay.com/v1/checkout.js'></script> in public/index.html"
        );
        return;
      }

      const options = {
        key: RAZORPAY_KEY_ID,
        amount: orderRes.data.amount,
        currency: orderRes.data.currency,
        order_id: orderRes.data.id,
        name: "Snoutiq Consultation",
        description: "Doctor Video Consultation",
        handler: async (response) => {
          log("✅ Payment success: " + JSON.stringify(response));

          // notify backend
          await axios.post(`${API_BASE}/api/call/${sid}/payment-success`, {
            payment_id: response.razorpay_payment_id,
            order_id: response.razorpay_order_id,
            signature: response.razorpay_signature,
          });

          // fetch channel_name (if not in state)
          let channelName = session?.channel_name;
          if (!channelName) {
            const sRes = await axios.get(`${API_BASE}/api/call/${sid}`);
            const s = sRes.data?.session ?? sRes.data;
            channelName = s.channel_name;
          }

          // 👉 join agora now
          await joinAgora(sid, channelName);
        },
        prefill: {
          name: "Test Patient",
          email: "patient@example.com",
          contact: "9999999999",
        },
      };

      const rzp = new window.Razorpay(options);
      log("🪟 Opening Razorpay checkout…");
      rzp.open();
    } catch (e) {
      log("Payment error: " + e.message);
    }
  };

  // ---------- 4) Agora: join / leave ----------
  const joinAgora = async (sid, channelName) => {
    try {
      if (joined) {
        log("ℹ️ Already in Agora channel, skipping join");
        return;
      }
      if (!AGORA_APP_ID) {
        log("❌ Missing VITE_AGORA_APP_ID in frontend .env");
        return;
      }
      const uid = Math.floor(Math.random() * 1_000_000);

      // get token from backend
      // Expected API: POST /api/agora/token { channel, uid, role }
      const tRes = await axios.post(`${API_BASE}/api/agora/token`, {
        channel: channelName,
        uid,
        role: "publisher",
      });
      const token = tRes.data?.token;
      if (!token) {
        log("❌ No Agora token from backend");
        return;
      }

      // create client
      const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
      clientRef.current = client;

      // remote user handlers
      client.on("user-published", async (user, mediaType) => {
        await client.subscribe(user, mediaType);
        log(`📡 Remote published: uid=${user.uid}, media=${mediaType}`);

        if (mediaType === "video") {
          ensureRemoteVideoContainer(user.uid);
          user.videoTrack?.play(getRemoteDivId(user.uid));
        }
        if (mediaType === "audio") {
          user.audioTrack?.play();
        }
      });

      client.on("user-unpublished", (user, mediaType) => {
        log(`🧹 Remote unpublished: uid=${user.uid}, media=${mediaType}`);
        if (mediaType === "video") {
          removeRemoteVideoContainer(user.uid);
        }
      });

      client.on("user-left", (user) => {
        log(`👋 Remote left: uid=${user.uid}`);
        removeRemoteVideoContainer(user.uid);
      });

      // join
      await client.join(AGORA_APP_ID, channelName, token, uid);

      // create local tracks
      const [mic, cam] = await AgoraRTC.createMicrophoneAndCameraTracks();
      localTracksRef.current = { mic, cam };

      // play local video
      cam.play("local-player");

      // publish
      await client.publish([mic, cam]);
      setJoined(true);
      log(`🎥 Joined Agora: ch=${channelName}, uid=${uid}`);
    } catch (e) {
      log("Agora join error: " + (e?.message || String(e)));
    }
  };

  const leaveAgora = async () => {
    try {
      const client = clientRef.current;
      const { mic, cam } = localTracksRef.current;

      if (mic) {
        mic.stop();
        mic.close();
      }
      if (cam) {
        cam.stop();
        cam.close();
      }
      localTracksRef.current = { mic: null, cam: null };

      if (client) {
        await client.unpublish();
        await client.leave();
        client.removeAllListeners();
        clientRef.current = null;
      }
      clearRemoteContainer();
      setJoined(false);
      log("🚪 Left Agora");
    } catch (e) {
      log("Leave error: " + (e?.message || String(e)));
    }
  };

  // ---------- DOM helpers for remote video ----------
  const getRemoteDivId = (uid) => `remote-${uid}`;
  const ensureRemoteVideoContainer = (uid) => {
    if (document.getElementById(getRemoteDivId(uid))) return;
    const wrap = document.getElementById("remote-container");
    const el = document.createElement("div");
    el.id = getRemoteDivId(uid);
    el.style.width = "240px";
    el.style.height = "180px";
    el.style.background = "#000";
    el.style.margin = "8px";
    el.style.borderRadius = "6px";
    wrap.appendChild(el);
  };
  const removeRemoteVideoContainer = (uid) => {
    const el = document.getElementById(getRemoteDivId(uid));
    if (el && el.parentNode) el.parentNode.removeChild(el);
  };
  const clearRemoteContainer = () => {
    const wrap = document.getElementById("remote-container");
    if (wrap) wrap.innerHTML = "";
  };

  return (
    <div style={{ padding: 20 }}>
      <h2>Call Flow Test (Polling + Agora on Payment)</h2>

      <div style={{ marginBottom: 12 }}>
        <button onClick={createCall}>1. Create Call (Patient)</button>
        <button onClick={acceptCall} style={{ marginLeft: 10 }}>
          2. Accept Call (Doctor)
        </button>

        {isPolling ? (
          <button onClick={stopPolling} style={{ marginLeft: 10 }}>
            ⏹️ Stop Polling
          </button>
        ) : (
          session && (
            <button
              onClick={() => startPolling(session.session_id)}
              style={{ marginLeft: 10 }}
            >
              🔁 Start Polling
            </button>
          )
        )}

        {paymentSessionId && !joined && (
          <button
            onClick={() => openRazorpayPayment(paymentSessionId)}
            style={{
              marginLeft: 10,
              background: "green",
              color: "white",
              padding: "8px 16px",
              borderRadius: 6,
            }}
          >
            💳 Pay Now
          </button>
        )}

        {joined && (
          <button
            onClick={leaveAgora}
            style={{
              marginLeft: 10,
              background: "#b91c1c",
              color: "white",
              padding: "8px 16px",
              borderRadius: 6,
            }}
          >
            🚪 Leave Call
          </button>
        )}
      </div>

      {/* Simple video layout */}
      <div style={{ display: "flex", gap: 16, alignItems: "flex-start" }}>
        <div>
          <div
            id="local-player"
            style={{
              width: 320,
              height: 240,
              background: "#000",
              borderRadius: 8,
            }}
          />
          <div style={{ marginTop: 4, fontSize: 12, textAlign: "center" }}>
            You (local)
          </div>
        </div>

        <div>
          <div id="remote-container" style={{ display: "flex", flexWrap: "wrap" }} />
          <div style={{ marginTop: 4, fontSize: 12, textAlign: "center" }}>
            Remotes
          </div>
        </div>
      </div>

      <h3>Logs</h3>
      <pre
        style={{
          background: "#eee",
          padding: 10,
          maxHeight: 300,
          overflow: "auto",
          fontSize: 12,
        }}
      >
        {logs.join("\n")}
      </pre>
    </div>
  );
}
