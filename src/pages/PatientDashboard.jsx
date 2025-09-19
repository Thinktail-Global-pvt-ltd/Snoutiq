// import React, { useState } from "react";
// import { io } from "socket.io-client";

// const isLocal =
//   window.location.hostname === "localhost" ||
//   window.location.hostname === "127.0.0.1";

// const socket = io(isLocal ? "http://localhost:4000" : "https://snoutiq.com", {
//   path: "/socket.io",
//   transports: ["websocket"],
// });

// export default function PatientDashboard() {
//   const [loading, setLoading] = useState(false);
//   const [response, setResponse] = useState(null);

//   const startCall = () => {
//     setLoading(true);
//     const callData = {
//       doctorId: 501,
//       patientId: 101,
//       channel: `call_${Math.random().toString(36).substring(2, 8)}`,
//     };

//     socket.emit("call-requested", callData);
//     setResponse({ success: true, ...callData });
//     setLoading(false);
//   };

//   return (
//     <div style={{ padding: 20 }}>
//       <h2>Patient Dashboard</h2>
//       <button
//         onClick={startCall}
//         disabled={loading}
//         style={{
//           padding: "10px 20px",
//           borderRadius: 6,
//           background: "#2563eb",
//           color: "#fff",
//           border: "none",
//           cursor: "pointer",
//         }}
//       >
//         {loading ? "Starting Call..." : "📞 Request Call"}
//       </button>

//       {response && (
//         <pre style={{ marginTop: 20, background: "#eee", padding: 10 }}>
//           {JSON.stringify(response, null, 2)}
//         </pre>
//       )}
//     </div>
//   );
// }
import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { socket } from "./socket";

export default function PatientDashboard() {
  const [loading, setLoading] = useState(false);
  const [callStatus, setCallStatus] = useState(null);
  const [activeDoctors, setActiveDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(501);
  const navigate = useNavigate();

  // Patient ID - in real app, this would come from auth
  const patientId = 101;

  useEffect(() => {
    // Get list of active doctors
    socket.emit("get-active-doctors");

    // Listen for call responses
    socket.on("call-sent", (data) => {
      setCallStatus({ type: "sent", ...data });
      setLoading(false);
    });

    socket.on("call-accepted", (data) => {
      setCallStatus({ type: "accepted", ...data });
      // Auto-navigate to call page
      setTimeout(() => {
        navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience`);
      }, 2000);
    });

    socket.on("call-rejected", (data) => {
      setCallStatus({ type: "rejected", ...data });
      setLoading(false);
    });

    socket.on("active-doctors", (doctors) => {
      setActiveDoctors(doctors);
    });

    return () => {
      socket.off("call-sent");
      socket.off("call-accepted");
      socket.off("call-rejected");
      socket.off("active-doctors");
    };
  }, [navigate, patientId]);

  const startCall = () => {
    if (!selectedDoctor) {
      alert("Please select a doctor");
      return;
    }

    setLoading(true);
    setCallStatus(null);
    
    const callData = {
      doctorId: selectedDoctor,
      patientId: patientId,
      channel: `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`,
    };

    socket.emit("call-requested", callData);
  };

  const getStatusMessage = () => {
    if (!callStatus) return null;
    
    switch (callStatus.type) {
      case "sent":
        return "📤 Call request sent to doctor. Waiting for response...";
      case "accepted":
        return "✅ Doctor accepted your call! Connecting...";
      case "rejected":
        return "❌ Doctor is currently unavailable. Please try again later.";
      default:
        return null;
    }
  };

  return (
    <div style={{ padding: 20, maxWidth: 600, margin: "0 auto" }}>
      <h2>Patient Dashboard</h2>
      <p>Patient ID: <strong>{patientId}</strong></p>

      <div style={{ marginBottom: 20 }}>
        <label htmlFor="doctor-select">Select Doctor:</label>
        <select
          id="doctor-select"
          value={selectedDoctor}
          onChange={(e) => setSelectedDoctor(Number(e.target.value))}
          style={{
            marginLeft: 10,
            padding: "5px 10px",
            borderRadius: 4,
            border: "1px solid #ccc"
          }}
        >
          <option value="">Select a doctor</option>
          {activeDoctors.map(doctorId => (
            <option key={doctorId} value={doctorId}>
              Dr. {doctorId} {activeDoctors.includes(doctorId) ? "🟢" : "🔴"}
            </option>
          ))}
          {/* Fallback option for testing */}
          <option value={501}>Dr. 501 (Test)</option>
        </select>
      </div>

      <button
        onClick={startCall}
        disabled={loading || !selectedDoctor}
        style={{
          padding: "12px 24px",
          borderRadius: 8,
          background: loading ? "#ccc" : "#2563eb",
          color: "#fff",
          border: "none",
          cursor: loading ? "not-allowed" : "pointer",
          fontSize: 16,
          fontWeight: "bold"
        }}
      >
        {loading ? "📞 Requesting Call..." : "📞 Request Video Call"}
      </button>

      {callStatus && (
        <div style={{
          marginTop: 20,
          padding: 16,
          borderRadius: 8,
          background: callStatus.type === "rejected" ? "#fee2e2" : "#dbeafe",
          border: `1px solid ${callStatus.type === "rejected" ? "#fca5a5" : "#93c5fd"}`
        }}>
          <p style={{ margin: 0, fontWeight: "bold" }}>{getStatusMessage()}</p>
          {callStatus.message && (
            <p style={{ margin: "8px 0 0 0", fontSize: 14 }}>{callStatus.message}</p>
          )}
        </div>
      )}

      <div style={{ marginTop: 30, fontSize: 14, color: "#666" }}>
        <h3>Active Doctors ({activeDoctors.length})</h3>
        {activeDoctors.length > 0 ? (
          <ul>
            {activeDoctors.map(doctorId => (
              <li key={doctorId}>Dr. {doctorId} 🟢 Online</li>
            ))}
          </ul>
        ) : (
          <p>No doctors are currently online</p>
        )}
      </div>
    </div>
  );
}