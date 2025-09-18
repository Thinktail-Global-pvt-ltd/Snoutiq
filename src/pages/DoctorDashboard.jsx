import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import Echo from "laravel-echo";

export default function DoctorDashboard({ doctorId = 501 }) {
  const [incomingCall, setIncomingCall] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    const echo = new Echo({
      broadcaster: "reverb",   // 👈 yeh line fix hai
      key: "base64:yT9RzP3vXl9lJ2pB2g==",
      wsHost: window.location.hostname,
      wsPort: 8080,            // local Reverb port
      forceTLS: false,
      enabledTransports: ["ws"],
    });

    echo.channel(`doctor.${doctorId}`).listen("CallRequested", (e) => {
      console.log("📞 Incoming call event:", e);
      setIncomingCall(e);
    });

    return () => {
      echo.disconnect();
    };
  }, [doctorId]);

  return (
    <div>
      <h2>Doctor Dashboard</h2>
      {incomingCall ? (
        <p>
          Patient {incomingCall.patientId} is calling on {incomingCall.channel}
        </p>
      ) : (
        <p>No active calls.</p>
      )}
    </div>
  );
}
