// import React, { useState, useEffect } from "react";
// import { useNavigate } from "react-router-dom";
// import { socket } from "./socket";

// export default function PatientDashboard() {
//   const [loading, setLoading] = useState(false);
//   const [callStatus, setCallStatus] = useState(null);
//   const [activeDoctors, setActiveDoctors] = useState([]);
//   const [selectedDoctor, setSelectedDoctor] = useState(501);
//   const navigate = useNavigate();

//   // Patient ID - in real app, this would come from auth
//   const patientId = 101;

//   useEffect(() => {
//     // Get list of active doctors
//     socket.emit("get-active-doctors");

//     // Listen for call responses
//     socket.on("call-sent", (data) => {
//       setCallStatus({ type: "sent", ...data });
//       setLoading(false);
//     });

//     socket.on("call-accepted", (data) => {
//       setCallStatus({ type: "accepted", ...data });
//       // Auto-navigate to call page
//       setTimeout(() => {
//         navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience`);
//       }, 2000);
//     });

//     socket.on("call-rejected", (data) => {
//       setCallStatus({ type: "rejected", ...data });
//       setLoading(false);
//     });

//     socket.on("active-doctors", (doctors) => {
//       setActiveDoctors(doctors);
//     });

//     return () => {
//       socket.off("call-sent");
//       socket.off("call-accepted");
//       socket.off("call-rejected");
//       socket.off("active-doctors");
//     };
//   }, [navigate, patientId]);

//   const startCall = () => {
//     if (!selectedDoctor) {
//       alert("Please select a doctor");
//       return;
//     }

//     setLoading(true);
//     setCallStatus(null);
    
//     const callData = {
//       doctorId: selectedDoctor,
//       patientId: patientId,
//       channel: `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`,
//     };

//     socket.emit("call-requested", callData);
//   };

//   const getStatusMessage = () => {
//     if (!callStatus) return null;
    
//     switch (callStatus.type) {
//       case "sent":
//         return "📤 Call request sent to doctor. Waiting for response...";
//       case "accepted":
//         return "✅ Doctor accepted your call! Connecting...";
//       case "rejected":
//         return "❌ Doctor is currently unavailable. Please try again later.";
//       default:
//         return null;
//     }
//   };

//   return (
//     <div style={{ padding: 20, maxWidth: 600, margin: "0 auto" }}>
//       <h2>Patient Dashboard</h2>
//       <p>Patient ID: <strong>{patientId}</strong></p>

//       <div style={{ marginBottom: 20 }}>
//         <label htmlFor="doctor-select">Select Doctor:</label>
//         <select
//           id="doctor-select"
//           value={selectedDoctor}
//           onChange={(e) => setSelectedDoctor(Number(e.target.value))}
//           style={{
//             marginLeft: 10,
//             padding: "5px 10px",
//             borderRadius: 4,
//             border: "1px solid #ccc"
//           }}
//         >
//           <option value="">Select a doctor</option>
//           {activeDoctors.map(doctorId => (
//             <option key={doctorId} value={doctorId}>
//               Dr. {doctorId} {activeDoctors.includes(doctorId) ? "🟢" : "🔴"}
//             </option>
//           ))}
//           {/* Fallback option for testing */}
//           <option value={501}>Dr. 501 (Test)</option>
//         </select>
//       </div>

//       <button
//         onClick={startCall}
//         disabled={loading || !selectedDoctor}
//         style={{
//           padding: "12px 24px",
//           borderRadius: 8,
//           background: loading ? "#ccc" : "#2563eb",
//           color: "#fff",
//           border: "none",
//           cursor: loading ? "not-allowed" : "pointer",
//           fontSize: 16,
//           fontWeight: "bold"
//         }}
//       >
//         {loading ? "📞 Requesting Call..." : "📞 Request Video Call"}
//       </button>

//       {callStatus && (
//         <div style={{
//           marginTop: 20,
//           padding: 16,
//           borderRadius: 8,
//           background: callStatus.type === "rejected" ? "#fee2e2" : "#dbeafe",
//           border: `1px solid ${callStatus.type === "rejected" ? "#fca5a5" : "#93c5fd"}`
//         }}>
//           <p style={{ margin: 0, fontWeight: "bold" }}>{getStatusMessage()}</p>
//           {callStatus.message && (
//             <p style={{ margin: "8px 0 0 0", fontSize: 14 }}>{callStatus.message}</p>
//           )}
//         </div>
//       )}

//       <div style={{ marginTop: 30, fontSize: 14, color: "#666" }}>
//         <h3>Active Doctors ({activeDoctors.length})</h3>
//         {activeDoctors.length > 0 ? (
//           <ul>
//             {activeDoctors.map(doctorId => (
//               <li key={doctorId}>Dr. {doctorId} 🟢 Online</li>
//             ))}
//           </ul>
//         ) : (
//           <p>No doctors are currently online</p>
//         )}
//       </div>
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
  const patientId = 146;

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
      
      // Check if payment is required
      if (data.requiresPayment) {
        // Redirect to payment page with call details
        setTimeout(() => {
          navigate(`/payment/${data.callId}?doctorId=${data.doctorId}&channel=${data.channel}&patientId=${patientId}`);
        }, 2000);
      } else {
        // Direct video call (fallback)
        setTimeout(() => {
          navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience`);
        }, 2000);
      }
    });

    socket.on("call-rejected", (data) => {
      setCallStatus({ type: "rejected", ...data });
      setLoading(false);
    });

    socket.on("active-doctors", (doctors) => {
      setActiveDoctors(doctors);
    });

    // Listen for payment completion
    socket.on("payment-completed", (data) => {
      if (data.patientId === patientId) {
        setCallStatus({ type: "payment-completed", ...data });
        // Navigate to video call after payment
        setTimeout(() => {
          navigate(`/call-page/${data.channel}?uid=${patientId}&role=audience&callId=${data.callId}`);
        }, 1000);
      }
    });

    return () => {
      socket.off("call-sent");
      socket.off("call-accepted");
      socket.off("call-rejected");
      socket.off("active-doctors");
      socket.off("payment-completed");
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
        return callStatus.requiresPayment 
          ? "✅ Doctor accepted your call! Redirecting to payment..." 
          : "✅ Doctor accepted your call! Connecting...";
      case "rejected":
        return "❌ Doctor is currently unavailable. Please try again later.";
      case "payment-completed":
        return "💳 Payment successful! Connecting to video call...";
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
              Dr. {doctorId} 🟢
            </option>
          ))}
          {/* Fallback option for testing */}
          <option value={501}>Dr. 501 (Test)</option>
        </select>
      </div>

      <div style={{ 
        background: "#f0f9ff", 
        border: "1px solid #0ea5e9", 
        borderRadius: 8, 
        padding: 16, 
        marginBottom: 20 
      }}>
        <h3 style={{ margin: "0 0 8px 0", color: "#0369a1" }}>Consultation Fee</h3>
        <div style={{ fontSize: 24, fontWeight: "bold", color: "#0369a1" }}>₹499</div>
        <p style={{ margin: "4px 0 0 0", fontSize: 14, color: "#0369a1" }}>
          30 minutes video consultation with certified veterinarian
        </p>
        <p style={{ margin: "4px 0 0 0", fontSize: 12, color: "#64748b" }}>
          Payment required after doctor accepts the call
        </p>
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
          background: callStatus.type === "rejected" ? "#fee2e2" : 
                     callStatus.type === "payment-completed" ? "#dcfce7" :
                     "#dbeafe",
          border: `1px solid ${
            callStatus.type === "rejected" ? "#fca5a5" : 
            callStatus.type === "payment-completed" ? "#86efac" :
            "#93c5fd"
          }`
        }}>
          <p style={{ margin: 0, fontWeight: "bold" }}>{getStatusMessage()}</p>
          {callStatus.message && (
            <p style={{ margin: "8px 0 0 0", fontSize: 14 }}>{callStatus.message}</p>
          )}
          
          {callStatus.type === "accepted" && callStatus.requiresPayment && (
            <div style={{ marginTop: 12, fontSize: 12, color: "#666" }}>
              <p>Next steps:</p>
              <p>1. Complete payment (₹499)</p>
              <p>2. Join video consultation</p>
            </div>
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

      {/* Payment Flow Information */}
      <div style={{ 
        marginTop: 20, 
        padding: 12, 
        background: "#fffbeb", 
        border: "1px solid #f59e0b", 
        borderRadius: 6 
      }}>
        <h4 style={{ margin: "0 0 8px 0", color: "#92400e" }}>How it works:</h4>
        <ol style={{ margin: 0, paddingLeft: 20, fontSize: 13, color: "#92400e" }}>
          <li>Request call with available doctor</li>
          <li>Doctor accepts your request</li>
          <li>Complete secure payment (₹499)</li>
          <li>Join video consultation immediately</li>
        </ol>
      </div>
    </div>
  );
}