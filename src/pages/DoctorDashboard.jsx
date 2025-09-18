import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import Echo from "laravel-echo";

export default function DoctorDashboard({ doctorId = 501 }) {
  const [incomingCall, setIncomingCall] = useState(null);
  const navigate = useNavigate();

//   useEffect(() => {
//   const echo = new Echo({
//   broadcaster: "reverb",
//   key: "base64:yT9RzP3vXl9lJ2pB2g==", // tumhara REVERB_APP_KEY
//   wsHost: window.location.hostname,   // localhost
//   wsPort: 8080,                       // local port
//   forceTLS: false,                    // local http = false
//   enabledTransports: ["ws"],
// });

const echo = new Echo({
  broadcaster: "reverb",
  key: "base64:yT9RzP3vXl9lJ2pB2g==",
  wsHost: window.location.hostname,
  wsPath: "/reverb",
  wssPort: 443,
  forceTLS: true,
  enabledTransports: ["ws", "wss"],
});


    // Doctor ke liye channel join
    echo.channel(`doctor.${doctorId}`)
      .listen("CallRequested", (e) => {
        console.log("📞 Incoming call event:", e);
        setIncomingCall({
          doctorId: e.doctorId,
          patientId: e.patientId,
          channel: e.channel,   // ✅ channel include karo
        });
      });

    return () => {
      echo.disconnect();
    };
  }, [doctorId]);

  const handleAccept = () => {
    if (incomingCall) {
      navigate(
        `/call-page/${incomingCall.channel}?uid=${doctorId}&role=host`
      );
    }
  };

  const handleReject = () => setIncomingCall(null);

  return (
    <div style={{ padding: 20 }}>
      <h2>Doctor Dashboard</h2>
      {incomingCall ? (
        <div
          style={{
            background: "#fef3c7",
            padding: 16,
            borderRadius: 8,
            marginTop: 20,
          }}
        >
          <h3>📞 Incoming Call</h3>
          <p>
            Patient <b>{incomingCall.patientId}</b> is calling you on{" "}
            <b>{incomingCall.channel}</b>
          </p>
          <button onClick={handleAccept}>✅ Accept</button>
          <button onClick={handleReject}>❌ Reject</button>
        </div>
      ) : (
        <p>No active calls.</p>
      )}
    </div>
  );
}
