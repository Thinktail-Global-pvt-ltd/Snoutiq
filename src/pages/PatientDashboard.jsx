import React, { useState } from "react";
import { io } from "socket.io-client";

// Socket.IO client init
const socket = io("http://localhost:4000"); // 🚨 prod pe apna domain daalna

export default function PatientDashboard() {
  const [loading, setLoading] = useState(false);
  const [response, setResponse] = useState(null);

  const startCall = async () => {
    setLoading(true);
    try {
      const callData = {
        doctorId: 501,   // yahan doctor id dynamic kar sakte ho
        patientId: 101,  // yahan patient id dynamic kar sakte ho
        channel: `call_${Math.random().toString(36).substring(2, 8)}`, // random channel
      };

      // socket.io emit
      socket.emit("call-requested", callData);

      // locally response set karo (simulate backend response)
      setResponse({
        success: true,
        message: "Call request sent via socket.io",
        ...callData,
      });
    } catch (err) {
      console.error("Error starting call:", err);
      setResponse({ success: false, message: "Failed to request call" });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ padding: 20 }}>
      <h2>Patient Dashboard (Socket.IO)</h2>
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
