import React, { useState } from "react";
import { io } from "socket.io-client";

const isLocal =
  window.location.hostname === "localhost" ||
  window.location.hostname === "127.0.0.1";

const socket = io(isLocal ? "http://localhost:4000" : "https://snoutiq.com", {
  path: "/socket.io",
  transports: ["websocket"],
});

export default function PatientDashboard() {
  const [loading, setLoading] = useState(false);
  const [response, setResponse] = useState(null);

  const startCall = () => {
    setLoading(true);
    const callData = {
      doctorId: 501,
      patientId: 101,
      channel: `call_${Math.random().toString(36).substring(2, 8)}`,
    };

    socket.emit("call-requested", callData);
    setResponse({ success: true, ...callData });
    setLoading(false);
  };

  return (
    <div style={{ padding: 20 }}>
      <h2>Patient Dashboard</h2>
      <button
        onClick={startCall}
        disabled={loading}
        style={{
          padding: "10px 20px",
          borderRadius: 6,
          background: "#2563eb",
          color: "#fff",
          border: "none",
          cursor: "pointer",
        }}
      >
        {loading ? "Starting Call..." : "📞 Request Call"}
      </button>

      {response && (
        <pre style={{ marginTop: 20, background: "#eee", padding: 10 }}>
          {JSON.stringify(response, null, 2)}
        </pre>
      )}
    </div>
  );
}
