import React, { useEffect, useRef, useState } from "react";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

export default function DoctorReceiver() {
  // ===== PROD/LOCAL SWITCH (NO .env) =====
  const isProd = window.location.hostname === "snoutiq.com";

  // If Laravel is at https://snoutiq.com (root), set this to "".
  // If it's at https://snoutiq.com/backend, keep "/backend".
  const BACKEND_PREFIX = "/backend";

  const API_BASE = isProd
    ? `${window.location.origin}${BACKEND_PREFIX}`
    : "http://127.0.0.1:8000";

  // Reverb config
  const REVERB_APP_KEY = "base64:yT9RzP3vXl9lJ2pB2g==";
  const REVERB_HOST = isProd ? "snoutiq.com" : "127.0.0.1";
  const REVERB_SCHEME = isProd ? "https" : "http";
  const REVERB_PORT = isProd ? 443 : 8080;
  const REVERB_PATH = "";
  // ======================================

  const [doctorId, setDoctorId] = useState(1);
  const [token, setToken] = useState("");
  const [callId, setCallId] = useState("");
  const [incoming, setIncoming] = useState(null);
  const [logs, setLogs] = useState(["Ready. Click Connect & Listen."]);
  const [conn, setConn] = useState("not connected");

  const echoRef = useRef(null);
  const hbRef = useRef(null);

  const headers = () => {
    const t = token.trim();
    return {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(t ? { Authorization: `Bearer ${t}` } : {}),
    };
  };

  const push = (m) =>
    setLogs((p) => [new Date().toISOString() + " — " + m, ...p].slice(0, 200));

  const cleanup = () => {
    if (hbRef.current) clearInterval(hbRef.current);
    hbRef.current = null;

    if (echoRef.current) {
      echoRef.current.disconnect();
      echoRef.current = null;
    }
    setConn("not connected");
  };

  useEffect(() => cleanup, []);

  const connect = () => {
    cleanup();

    const echo = new Echo({
      broadcaster: "reverb",
      key: REVERB_APP_KEY,

      wsHost: REVERB_HOST,
      wsPort: REVERB_PORT,
      wssPort: REVERB_PORT,
      wsPath: REVERB_PATH || "",

      forceTLS: REVERB_SCHEME === "https",
      enabledTransports: ["ws", "wss"],

      authEndpoint: `${API_BASE}/broadcasting/auth`,
      auth: { headers: headers(), withCredentials: true },
    });

    echoRef.current = echo;

    const pusherConn = echo.connector?.pusher?.connection;
    pusherConn?.bind("connected", () => {
      setConn("connected");
      push(`WebSocket connected (${REVERB_SCHEME === "https" ? "wss" : "ws"}://${REVERB_HOST}:${REVERB_PORT})`);
    });
    pusherConn?.bind("disconnected", () => {
      setConn("disconnected");
      push("WebSocket disconnected");
    });
    pusherConn?.bind("error", (e) => {
      setConn("error");
      push("WebSocket error: " + JSON.stringify(e));
    });

    const dId = Number(doctorId) || 0;
    const ch = echo.channel(`doctor.${dId}`);

    ch.listen(".CallRequested", (e) => {
      push("CallRequested: " + JSON.stringify(e));
      setIncoming(e);
      setCallId(String(e.call_id || e.id || ""));
    });

    ch.listen(".CallStatusUpdated", (e) => {
      push("CallStatusUpdated: " + JSON.stringify(e));
      setIncoming((prev) => ({ ...(prev || {}), ...(e || {}) }));
      if (e.call_id || e.id) setCallId(String(e.call_id || e.id));
    });

    const sendHeartbeat = async () => {
      try {
        const res = await fetch(`${API_BASE}/api/realtime/heartbeat`, {
          method: "POST",
          headers: headers(),
          credentials: "include",
          body: JSON.stringify({ doctor_id: dId }),
        });
        const data = await res.json().catch(() => ({}));
        push(`Heartbeat ${res.status}: ${JSON.stringify(data)}`);
      } catch (err) {
        push("Heartbeat error: " + (err?.message || String(err)));
      }
    };

    sendHeartbeat();
    hbRef.current = setInterval(sendHeartbeat, 10_000);

    push(`Subscribed to doctor.${dId}`);
  };

  const action = async (name) => {
    const id = (callId || "").trim();
    if (!id) return push("Call ID missing");

    const res = await fetch(`${API_BASE}/api/calls/${id}/${name}`, {
      method: "POST",
      headers: headers(),
      credentials: "include",
      body: JSON.stringify({}),
    });
    const data = await res.json().catch(() => ({}));
    push(`${name} ${res.status}: ${JSON.stringify(data)}`);
  };

  return (
    <div style={styles.page}>
      <h2 style={{ marginTop: 0 }}>Doctor Receiver (React)</h2>
      <p style={{ color: "#6b7280" }}>
        Works in local + production (<b>snoutiq.com/app</b>). Token optional.
      </p>

      <div style={{ marginBottom: 10, fontWeight: 900 }}>
        API_BASE: <span style={{ color: "#2563eb" }}>{API_BASE}</span>
      </div>

      <div style={{ marginBottom: 10, fontWeight: 900 }}>
        WS:{" "}
        <span style={{ color: "#2563eb" }}>
          {(REVERB_SCHEME === "https" ? "wss" : "ws")}://{REVERB_HOST}:{REVERB_PORT}
        </span>
      </div>

      <div style={styles.grid}>
        <Field label="Doctor ID">
          <input
            type="number"
            value={doctorId}
            onChange={(e) => setDoctorId(e.target.value)}
          />
        </Field>

        <Field label="Bearer Token (optional)">
          <input
            value={token}
            onChange={(e) => setToken(e.target.value)}
            placeholder="ey..."
          />
        </Field>

        <Field label="Active Call ID (auto-fills)">
          <input value={callId} onChange={(e) => setCallId(e.target.value)} />
        </Field>
      </div>

      <div style={styles.row}>
        <Btn onClick={connect}>Connect & Listen</Btn>
        <Chip ok={conn === "connected"}>{conn}</Chip>
      </div>

      <h3 style={{ marginTop: 18 }}>Incoming</h3>
      <div style={styles.card}>
        <div style={{ fontWeight: 900, fontSize: 16 }}>
          Status: {incoming?.status || "waiting"}
        </div>
        <div style={{ color: "#6b7280", marginTop: 4 }}>
          Call {incoming?.call_id || incoming?.id || "-"} • patient{" "}
          {incoming?.patient_id ?? "-"} • channel {incoming?.channel ?? "-"}
        </div>
      </div>

      <div style={styles.row}>
        <Btn onClick={() => action("accept")}>Accept</Btn>
        <Btn gray onClick={() => action("reject")}>Reject</Btn>
        <Btn gray onClick={() => action("end")}>End</Btn>
      </div>

      <h3 style={{ marginTop: 18 }}>Logs</h3>
      <pre style={styles.log}>{logs.join("\n")}</pre>
    </div>
  );
}

function Field({ label, children }) {
  return (
    <div>
      <div style={styles.label}>{label}</div>
      {React.cloneElement(children, { style: styles.input })}
    </div>
  );
}

function Btn({ children, onClick, gray }) {
  return (
    <button
      onClick={onClick}
      style={{
        ...styles.btn,
        background: gray ? "#6b7280" : "#2563eb",
      }}
    >
      {children}
    </button>
  );
}

function Chip({ children, ok }) {
  return (
    <span
      style={{
        ...styles.chip,
        background: ok ? "#dcfce7" : "#fee2e2",
        color: ok ? "#166534" : "#991b1b",
      }}
    >
      {children}
    </span>
  );
}

const styles = {
  page: { padding: 24, fontFamily: "system-ui", maxWidth: 980, margin: "0 auto" },
  grid: { display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(240px,1fr))", gap: 12 },
  row: { display: "flex", gap: 10, flexWrap: "wrap", marginTop: 14, alignItems: "center" },
  label: { fontWeight: 900, marginBottom: 6 },
  input: { width: "100%", padding: 10, borderRadius: 10, border: "1px solid #d1d5db" },
  btn: { padding: "10px 14px", borderRadius: 10, border: "none", cursor: "pointer", fontWeight: 900, color: "#fff" },
  chip: { padding: "8px 12px", borderRadius: 999, fontWeight: 900 },
  card: { background: "#f3f4f6", padding: 12, borderRadius: 10 },
  log: { background: "#0f172a", color: "#e2e8f0", padding: 12, borderRadius: 10, whiteSpace: "pre-wrap" },
};
