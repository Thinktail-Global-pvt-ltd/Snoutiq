import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { io } from "socket.io-client";

// yaha apne server ka URL use karo
const socket = io("http://localhost:4000");

export default function DoctorDashboard({ doctorId = 501 }) {
  const [incomingCall, setIncomingCall] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    // Doctor room join
    socket.emit("join-doctor", doctorId);

    // Call request suno
    socket.on("call-requested", (e) => {
      console.log("📞 Incoming call:", e);
      setIncomingCall(e);
    });

    return () => {
      socket.off("call-requested");
    };
  }, [doctorId]);

  const handleAccept = () => {
    if (incomingCall) {
      navigate(`/call-page/${incomingCall.channel}?uid=${doctorId}&role=host`);
    }
  };

  return (
    <div style={{ padding: 20 }}>
      <h2>Doctor Dashboard (Socket.IO)</h2>
      {incomingCall ? (
        <div style={{ background: "#fef3c7", padding: 16, borderRadius: 8 }}>
          <h3>📞 Incoming Call</h3>
          <p>
            Patient <b>{incomingCall.patientId}</b> is calling on{" "}
            <b>{incomingCall.channel}</b>
          </p>
          <button onClick={handleAccept}>✅ Accept</button>
        </div>
      ) : (
        <p>No active calls.</p>
      )}
    </div>
  );
}
