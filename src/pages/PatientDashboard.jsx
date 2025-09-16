import React, { useState } from "react";

export default function PatientDashboard() {
  const [loading, setLoading] = useState(false);
  const [response, setResponse] = useState(null);

  const startCall = async () => {
    setLoading(true);
    try {
      const res = await fetch("http://127.0.0.1:8000/api/call/request", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          doctor_id: 501, // dynamically doctor id
          patient_id: 101, // dynamically patient id
        }),
      });
      const data = await res.json();
      setResponse(data);
    } catch (err) {
      console.error("Error starting call:", err);
    } finally {
      setLoading(false);
    }
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
