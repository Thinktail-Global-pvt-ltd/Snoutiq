// import React, { useState } from "react";

// export default function CallLab() {
//   // ===== PROD/LOCAL SWITCH (NO .env) =====
//   const isProd = window.location.hostname === "snoutiq.com";

//   // If your Laravel backend is at https://snoutiq.com (root), set this to "".
//   // If it's at https://snoutiq.com/backend, keep "/backend".
//   const BACKEND_PREFIX = "/backend";

//   const API_BASE = isProd
//     ? `${window.location.origin}${BACKEND_PREFIX}`
//     : "http://127.0.0.1:8000";
//   // ======================================

//   const [patientId, setPatientId] = useState(2);
//   const [doctorId, setDoctorId] = useState(1);
//   const [callId, setCallId] = useState("");
//   const [channel, setChannel] = useState("video");
//   const [token, setToken] = useState("");
//   const [lastCall, setLastCall] = useState(null);
//   const [logs, setLogs] = useState(["Ready…"]);

//   const headers = () => {
//     const t = token.trim();
//     return {
//       Accept: "application/json",
//       "Content-Type": "application/json",
//       ...(t ? { Authorization: `Bearer ${t}` } : {}),
//     };
//   };

//   const log = (msg) => setLogs((p) => [msg, ...p].slice(0, 200));

//   const post = async (path, body = {}) => {
//     const url = `${API_BASE}${path}`;
//     const res = await fetch(url, {
//       method: "POST",
//       headers: headers(),
//       credentials: "include",
//       body: JSON.stringify(body),
//     });
//     const data = await res.json().catch(() => ({}));
//     log(`${path} → ${res.status}\n${JSON.stringify(data)}`);
//     return { res, data };
//   };

//   const heartbeat = () =>
//     post("/api/realtime/heartbeat", { doctor_id: Number(doctorId) || 0 });

//   const requestCall = async () => {
//     const { res, data } = await post("/api/calls/request", {
//       patient_id: Number(patientId) || 0,
//       channel: channel.trim() || "video",
//     });

//     if (res.ok && data?.call_id) {
//       setLastCall(data.call_id);
//       setCallId(String(data.call_id));
//     }
//   };

//   const action = (name) => {
//     const id = (callId || "").trim();
//     if (!id) return log("Call ID missing");
//     return post(`/api/calls/${id}/${name}`, {});
//   };

//   return (
//     <div style={styles.page}>
//       <h2 style={{ marginTop: 0 }}>Call Lab (React)</h2>
//       <p style={{ color: "#6b7280" }}>
//         Local + Production compatible. Token optional.
//       </p>

//       <div style={{ marginBottom: 10, color: "#111827", fontWeight: 900 }}>
//         API_BASE: <span style={{ color: "#2563eb" }}>{API_BASE}</span>
//       </div>

//       <div style={styles.grid}>
//         <Field label="Patient ID">
//           <input
//             type="number"
//             value={patientId}
//             onChange={(e) => setPatientId(e.target.value)}
//           />
//         </Field>

//         <Field label="Doctor ID (heartbeat)">
//           <input
//             type="number"
//             value={doctorId}
//             onChange={(e) => setDoctorId(e.target.value)}
//           />
//         </Field>

//         <Field label="Call ID (accept/reject/end/cancel)">
//           <input
//             value={callId}
//             onChange={(e) => setCallId(e.target.value)}
//             placeholder="auto after request"
//           />
//         </Field>

//         <Field label="Channel">
//           <input value={channel} onChange={(e) => setChannel(e.target.value)} />
//         </Field>

//         <Field label="Bearer Token (optional)">
//           <input
//             value={token}
//             onChange={(e) => setToken(e.target.value)}
//             placeholder="ey..."
//           />
//         </Field>
//       </div>

//       <div style={styles.row}>
//         <Btn onClick={heartbeat}>Doctor Heartbeat</Btn>
//         <Btn onClick={requestCall}>Request Call</Btn>
//         <Btn onClick={() => action("accept")}>Accept</Btn>
//         <Btn gray onClick={() => action("reject")}>Reject</Btn>
//         <Btn gray onClick={() => action("cancel")}>Cancel</Btn>
//         <Btn gray onClick={() => action("end")}>End</Btn>
//       </div>

//       <div style={{ marginTop: 12, fontWeight: 900 }}>
//         Last call: <span style={{ color: "#2563eb" }}>{lastCall ?? "none"}</span>
//       </div>

//       <h3 style={{ marginTop: 20 }}>Logs</h3>
//       <pre style={styles.log}>{logs.join("\n\n")}</pre>
//     </div>
//   );
// }

// function Field({ label, children }) {
//   return (
//     <div>
//       <div style={styles.label}>{label}</div>
//       {React.cloneElement(children, { style: styles.input })}
//     </div>
//   );
// }

// function Btn({ children, onClick, gray }) {
//   return (
//     <button
//       onClick={onClick}
//       style={{
//         ...styles.btn,
//         background: gray ? "#6b7280" : "#2563eb",
//       }}
//     >
//       {children}
//     </button>
//   );
// }

// const styles = {
//   page: { padding: 24, fontFamily: "system-ui", maxWidth: 980, margin: "0 auto" },
//   grid: { display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(240px,1fr))", gap: 12 },
//   row: { display: "flex", gap: 10, flexWrap: "wrap", marginTop: 14 },
//   label: { fontWeight: 900, marginBottom: 6 },
//   input: { width: "100%", padding: 10, borderRadius: 10, border: "1px solid #d1d5db" },
//   btn: { padding: "10px 14px", borderRadius: 10, border: "none", cursor: "pointer", fontWeight: 900, color: "#fff" },
//   log: { background: "#0f172a", color: "#e2e8f0", padding: 12, borderRadius: 10, whiteSpace: "pre-wrap" },
// };

import React, { useMemo, useRef, useState } from "react";

export default function CallLab() {
  // ✅ Local + Prod compatible (no .env)
  const API_BASE = useMemo(() => {
    const host = window.location.hostname;
    const origin = window.location.origin;

    // prod: snoutiq.com backend is mounted at /backend
    if (host.includes("snoutiq.com")) return `${origin}/backend`;

    // local backend runs at 127.0.0.1:8000
    return "http://127.0.0.1:8000";
  }, []);

  const [patientId, setPatientId] = useState("2");
  const [doctorId, setDoctorId] = useState("1");
  const [callId, setCallId] = useState("");
  const [channel, setChannel] = useState("video");
  const [bearerToken, setBearerToken] = useState("");

  // ✅ NEW: Doctor FCM token (static input for now)
  const [doctorFcmToken, setDoctorFcmToken] = useState("");

  const [logs, setLogs] = useState("/api ready...\n");
  const appendLog = (line) => setLogs((p) => `${p}\n${line}`);

  // ring loop refs
  const ringIntervalRef = useRef(null);
  const ringStopTimeoutRef = useRef(null);

  const authHeaders = () => {
    const h = { "Content-Type": "application/json" };
    if (bearerToken?.trim()) h["Authorization"] = `Bearer ${bearerToken.trim()}`;
    return h;
  };

  async function postJSON(path, body) {
    const url = `${API_BASE}${path}`;
    const res = await fetch(url, {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify(body ?? {}),
    });
    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch {
      json = { raw: text };
    }
    return { ok: res.ok, status: res.status, json };
  }

  function stopRingingPush() {
    if (ringIntervalRef.current) {
      clearInterval(ringIntervalRef.current);
      ringIntervalRef.current = null;
    }
    if (ringStopTimeoutRef.current) {
      clearTimeout(ringStopTimeoutRef.current);
      ringStopTimeoutRef.current = null;
    }
    appendLog("🔇 Ringing push stopped");
  }

  async function sendIncomingCallPushOnce({ token, call_id, doctor_id, mode }) {
    // Uses your existing backend logic: /api/push/test
    return postJSON("/api/push/test", {
      token,
      title: "Snoutiq Incoming Call",
      body: `Incoming ${mode} call • Call ${call_id}`,
      data: {
        type: "incoming_call",
        call_id: String(call_id),
        doctor_id: String(doctor_id),
        channel: String(mode || "video"),
      },
    });
  }

  function startRingingPush({ token, call_id, doctor_id, mode }) {
    stopRingingPush(); // kill old loops

    if (!token?.trim()) {
      appendLog("⚠️ Doctor FCM token missing, skipping push ringing");
      return;
    }

    const RING_FOR_MS = 30_000;   // match backend ring timeout default (30s)
    const EVERY_MS = 5_000;       // push every 5s (6 pushes total)

    appendLog(`🔔 Starting ringing push: ${RING_FOR_MS / 1000}s, every ${EVERY_MS / 1000}s`);

    // fire immediately
    (async () => {
      const r = await sendIncomingCallPushOnce({ token, call_id, doctor_id, mode });
      appendLog(`push/test → ${r.status} ${JSON.stringify(r.json)}`);
    })();

    // then repeat
    ringIntervalRef.current = setInterval(async () => {
      const r = await sendIncomingCallPushOnce({ token, call_id, doctor_id, mode });
      appendLog(`push/test → ${r.status} ${JSON.stringify(r.json)}`);
    }, EVERY_MS);

    // hard stop after timeout
    ringStopTimeoutRef.current = setTimeout(() => {
      stopRingingPush();
    }, RING_FOR_MS);
  }

  // ---------------- actions ----------------

  async function doctorHeartbeat() {
    const r = await postJSON("/api/realtime/heartbeat", { doctor_id: Number(doctorId) });
    appendLog(`/api/realtime/heartbeat → ${r.status}\n${JSON.stringify(r.json)}`);
  }

  async function requestCall() {
    const r = await postJSON("/api/calls/request", {
      patient_id: Number(patientId),
      channel: channel || "video",
    });

    appendLog(`/api/calls/request → ${r.status}\n${JSON.stringify(r.json)}`);

    if (r.ok && r.json?.ok && r.json?.call_id) {
      const newCallId = String(r.json.call_id);
      setCallId(newCallId);

      // ✅ start ringing pushes to doctor token
      startRingingPush({
        token: doctorFcmToken.trim(),
        call_id: newCallId,
        doctor_id: r.json.doctor_id ?? doctorId,
        mode: channel || "video",
      });
    }
  }

  async function accept() {
    if (!callId) return appendLog("⚠️ callId missing");
    stopRingingPush();
    const r = await postJSON(`/api/calls/${callId}/accept`, {});
    appendLog(`/api/calls/${callId}/accept → ${r.status}\n${JSON.stringify(r.json)}`);
  }

  async function reject() {
    if (!callId) return appendLog("⚠️ callId missing");
    stopRingingPush();
    const r = await postJSON(`/api/calls/${callId}/reject`, {});
    appendLog(`/api/calls/${callId}/reject → ${r.status}\n${JSON.stringify(r.json)}`);
  }

  async function cancel() {
    if (!callId) return appendLog("⚠️ callId missing");
    stopRingingPush();
    const r = await postJSON(`/api/calls/${callId}/cancel`, {});
    appendLog(`/api/calls/${callId}/cancel → ${r.status}\n${JSON.stringify(r.json)}`);
  }

  async function end() {
    if (!callId) return appendLog("⚠️ callId missing");
    stopRingingPush();
    const r = await postJSON(`/api/calls/${callId}/end`, {});
    appendLog(`/api/calls/${callId}/end → ${r.status}\n${JSON.stringify(r.json)}`);
  }

  // ---------------- UI ----------------

  return (
    <div style={{ maxWidth: 980, margin: "24px auto", padding: 16, fontFamily: "system-ui" }}>
      <h2>Call Lab (React)</h2>
      <div style={{ opacity: 0.7, marginBottom: 8 }}>
        Local + Production compatible. Token optional.
      </div>

      <div style={{ marginBottom: 16 }}>
        <div><b>API_BASE:</b> <a href={API_BASE} target="_blank" rel="noreferrer">{API_BASE}</a></div>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
        <label>
          <div><b>Patient ID</b></div>
          <input value={patientId} onChange={(e) => setPatientId(e.target.value)} style={inputStyle} />
        </label>

        <label>
          <div><b>Doctor ID (heartbeat)</b></div>
          <input value={doctorId} onChange={(e) => setDoctorId(e.target.value)} style={inputStyle} />
        </label>

        <label>
          <div><b>Call ID (accept/reject/end/cancel)</b></div>
          <input value={callId} onChange={(e) => setCallId(e.target.value)} style={inputStyle} />
        </label>

        <label>
          <div><b>Channel</b></div>
          <input value={channel} onChange={(e) => setChannel(e.target.value)} style={inputStyle} />
        </label>

        <label style={{ gridColumn: "1 / -1" }}>
          <div><b>Bearer Token (optional)</b></div>
          <input value={bearerToken} onChange={(e) => setBearerToken(e.target.value)} style={inputStyle} placeholder="ey..." />
        </label>

        <label style={{ gridColumn: "1 / -1" }}>
          <div><b>Doctor FCM Token (for ringing push)</b></div>
          <input
            value={doctorFcmToken}
            onChange={(e) => setDoctorFcmToken(e.target.value)}
            style={inputStyle}
            placeholder="Paste doctor device FCM token here..."
          />
        </label>
      </div>

      <div style={{ display: "flex", gap: 10, flexWrap: "wrap", marginTop: 16 }}>
        <button onClick={doctorHeartbeat} style={btnBlue}>Doctor Heartbeat</button>
        <button onClick={requestCall} style={btnBlue}>Request Call</button>

        <button onClick={accept} style={btnBlue}>Accept</button>
        <button onClick={reject} style={btnGray}>Reject</button>
        <button onClick={cancel} style={btnGray}>Cancel</button>
        <button onClick={end} style={btnGray}>End</button>

        <button onClick={stopRingingPush} style={btnGray}>Stop Ring Push</button>
      </div>

      <div style={{ marginTop: 18 }}>
        <h3>Logs</h3>
        <pre style={logStyle}>{logs}</pre>
      </div>
    </div>
  );
}

const inputStyle = {
  width: "100%",
  padding: "12px 14px",
  borderRadius: 10,
  border: "1px solid #ddd",
  fontSize: 16,
};

const btnBlue = {
  padding: "12px 16px",
  borderRadius: 12,
  border: "none",
  color: "white",
  background: "#2563eb",
  fontWeight: 700,
  cursor: "pointer",
};

const btnGray = {
  padding: "12px 16px",
  borderRadius: 12,
  border: "none",
  color: "white",
  background: "#6b7280",
  fontWeight: 700,
  cursor: "pointer",
};

const logStyle = {
  background: "#0b1220",
  color: "#e5e7eb",
  padding: 14,
  borderRadius: 12,
  minHeight: 220,
  whiteSpace: "pre-wrap",
};

