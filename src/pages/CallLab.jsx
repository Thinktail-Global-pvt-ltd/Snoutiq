import React, { useState } from "react";

export default function CallLab() {
  // ===== PROD/LOCAL SWITCH (NO .env) =====
  const isProd = window.location.hostname === "snoutiq.com";

  // If your Laravel backend is at https://snoutiq.com (root), set this to "".
  // If it's at https://snoutiq.com/backend, keep "/backend".
  const BACKEND_PREFIX = "/backend";

  const API_BASE = isProd
    ? `${window.location.origin}${BACKEND_PREFIX}`
    : "http://127.0.0.1:8000";
  // ======================================

  const [patientId, setPatientId] = useState(2);
  const [doctorId, setDoctorId] = useState(1);
  const [callId, setCallId] = useState("");
  const [channel, setChannel] = useState("video");
  const [token, setToken] = useState("");
  const [lastCall, setLastCall] = useState(null);
  const [logs, setLogs] = useState(["Ready…"]);

  const headers = () => {
    const t = token.trim();
    return {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(t ? { Authorization: `Bearer ${t}` } : {}),
    };
  };

  const log = (msg) => setLogs((p) => [msg, ...p].slice(0, 200));

  const post = async (path, body = {}) => {
    const url = `${API_BASE}${path}`;
    const res = await fetch(url, {
      method: "POST",
      headers: headers(),
      credentials: "include",
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    log(`${path} → ${res.status}\n${JSON.stringify(data)}`);
    return { res, data };
  };

  const heartbeat = () =>
    post("/api/realtime/heartbeat", { doctor_id: Number(doctorId) || 0 });

  const requestCall = async () => {
    const { res, data } = await post("/api/calls/request", {
      patient_id: Number(patientId) || 0,
      channel: channel.trim() || "video",
    });

    if (res.ok && data?.call_id) {
      setLastCall(data.call_id);
      setCallId(String(data.call_id));
    }
  };

  const action = (name) => {
    const id = (callId || "").trim();
    if (!id) return log("Call ID missing");
    return post(`/api/calls/${id}/${name}`, {});
  };

  return (
    <div style={styles.page}>
      <h2 style={{ marginTop: 0 }}>Call Lab (React)</h2>
      <p style={{ color: "#6b7280" }}>
        Local + Production compatible. Token optional.
      </p>

      <div style={{ marginBottom: 10, color: "#111827", fontWeight: 900 }}>
        API_BASE: <span style={{ color: "#2563eb" }}>{API_BASE}</span>
      </div>

      <div style={styles.grid}>
        <Field label="Patient ID">
          <input
            type="number"
            value={patientId}
            onChange={(e) => setPatientId(e.target.value)}
          />
        </Field>

        <Field label="Doctor ID (heartbeat)">
          <input
            type="number"
            value={doctorId}
            onChange={(e) => setDoctorId(e.target.value)}
          />
        </Field>

        <Field label="Call ID (accept/reject/end/cancel)">
          <input
            value={callId}
            onChange={(e) => setCallId(e.target.value)}
            placeholder="auto after request"
          />
        </Field>

        <Field label="Channel">
          <input value={channel} onChange={(e) => setChannel(e.target.value)} />
        </Field>

        <Field label="Bearer Token (optional)">
          <input
            value={token}
            onChange={(e) => setToken(e.target.value)}
            placeholder="ey..."
          />
        </Field>
      </div>

      <div style={styles.row}>
        <Btn onClick={heartbeat}>Doctor Heartbeat</Btn>
        <Btn onClick={requestCall}>Request Call</Btn>
        <Btn onClick={() => action("accept")}>Accept</Btn>
        <Btn gray onClick={() => action("reject")}>Reject</Btn>
        <Btn gray onClick={() => action("cancel")}>Cancel</Btn>
        <Btn gray onClick={() => action("end")}>End</Btn>
      </div>

      <div style={{ marginTop: 12, fontWeight: 900 }}>
        Last call: <span style={{ color: "#2563eb" }}>{lastCall ?? "none"}</span>
      </div>

      <h3 style={{ marginTop: 20 }}>Logs</h3>
      <pre style={styles.log}>{logs.join("\n\n")}</pre>
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

const styles = {
  page: { padding: 24, fontFamily: "system-ui", maxWidth: 980, margin: "0 auto" },
  grid: { display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(240px,1fr))", gap: 12 },
  row: { display: "flex", gap: 10, flexWrap: "wrap", marginTop: 14 },
  label: { fontWeight: 900, marginBottom: 6 },
  input: { width: "100%", padding: 10, borderRadius: 10, border: "1px solid #d1d5db" },
  btn: { padding: "10px 14px", borderRadius: 10, border: "none", cursor: "pointer", fontWeight: 900, color: "#fff" },
  log: { background: "#0f172a", color: "#e2e8f0", padding: 12, borderRadius: 10, whiteSpace: "pre-wrap" },
};
