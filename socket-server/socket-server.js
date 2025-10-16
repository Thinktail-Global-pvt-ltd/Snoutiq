// // import { createServer } from "http";
// // import { Server } from "socket.io";

// // const httpServer = createServer();

// // const io = new Server(httpServer, {
// //   cors: {
// //     origin: [
// //       "http://localhost:3000", 
// //       "http://127.0.0.1:3000",
// //       "https://snoutiq.com"  // Add your live domain
// //     ],
// //     methods: ["GET", "POST"],
// //     credentials: true
// //   },
// //   path: "/socket.io/",
// // });

// // // Simple storage
// // const activeDoctors = new Map();

// // io.on("connection", (socket) => {
// //   console.log(`✅ Client connected: ${socket.id}`);

// //   // Doctor joins room
// //   socket.on("join-doctor", (doctorId) => {
// //     console.log(`🏥 join-doctor event received for doctorId: ${doctorId}`);
    
// //     const roomName = `doctor-${doctorId}`;
    
// //     // Join the room
// //     socket.join(roomName);
// //     console.log(`👨‍⚕️ Socket ${socket.id} joined room: ${roomName}`);
    
// //     // Store doctor info
// //     activeDoctors.set(doctorId, {
// //       socketId: socket.id,
// //       joinedAt: new Date()
// //     });
// //     console.log(`📝 Stored doctor ${doctorId} in activeDoctors map`);
    
// //     // IMMEDIATELY emit doctor-online event back to the client
// //     console.log(`📤 Emitting doctor-online event to socket ${socket.id}`);
// //     socket.emit("doctor-online", { 
// //       doctorId: doctorId, 
// //       status: "online",
// //       timestamp: new Date().toISOString(),
// //       socketId: socket.id
// //     });
    
// //     console.log(`✅ doctor-online event sent for doctorId: ${doctorId}`);
    
// //     // Also emit to the room (in case of multiple connections)
// //     console.log(`📤 Emitting doctor-online event to room: ${roomName}`);
// //     io.to(roomName).emit("doctor-online", { 
// //       doctorId: doctorId, 
// //       status: "online",
// //       timestamp: new Date().toISOString(),
// //       socketId: socket.id
// //     });
// //   });

// //   // Test server status
// //   socket.on("get-server-status", () => {
// //     console.log(`📊 get-server-status requested by ${socket.id}`);
// //     socket.emit("server-status", {
// //       connected: true,
// //       activeDoctors: activeDoctors.size,
// //       doctorsList: Array.from(activeDoctors.keys()),
// //       uptime: process.uptime(),
// //       timestamp: new Date().toISOString()
// //     });
// //   });

// //   // Get active doctors list
// //   socket.on("get-active-doctors", () => {
// //     console.log(`📋 get-active-doctors requested by ${socket.id}`);
// //     socket.emit("active-doctors", Array.from(activeDoctors.keys()));
// //   });

// //   // Ping-pong test
// //   socket.on("ping", (data) => {
// //     console.log(`🏓 ping received from ${socket.id}:`, data);
// //     socket.emit("pong", {
// //       ...data,
// //       serverTime: new Date().toISOString(),
// //       receivedAt: Date.now()
// //     });
// //   });

// //   // Patient requests call
// //   socket.on("call-requested", ({ doctorId, patientId, channel }) => {
// //     console.log(`📞 call-requested: Patient ${patientId} → Doctor ${doctorId}`);
    
// //     if (!activeDoctors.has(doctorId)) {
// //       console.log(`❌ Doctor ${doctorId} not found in active doctors`);
// //       socket.emit("call-failed", {
// //         error: "Doctor not available",
// //         doctorId,
// //         patientId
// //       });
// //       return;
// //     }
    
// //     const callId = `${patientId}-${doctorId}-${Date.now()}`;
// //     const roomName = `doctor-${doctorId}`;
    
// //     console.log(`📤 Sending call-requested to room: ${roomName}`);
// //     io.to(roomName).emit("call-requested", {
// //       callId,
// //       doctorId,
// //       patientId,
// //       channel,
// //       timestamp: new Date().toISOString()
// //     });
    
// //     // Confirm to patient
// //     socket.emit("call-sent", {
// //       callId,
// //       doctorId,
// //       patientId,
// //       channel,
// //       status: "sent"
// //     });
// //   });

// //   // Call accepted
// //   socket.on("call-accepted", (data) => {
// //     console.log(`✅ call-accepted:`, data);
// //     io.emit("call-accepted", {
// //       ...data,
// //       timestamp: new Date().toISOString()
// //     });
// //   });

// //   // Call rejected
// //   socket.on("call-rejected", (data) => {
// //     console.log(`❌ call-rejected:`, data);
// //     io.emit("call-rejected", {
// //       ...data,
// //       timestamp: new Date().toISOString()
// //     });
// //   });

// //   // Doctor leaves
// //   socket.on("leave-doctor", (doctorId) => {
// //     console.log(`🚪 leave-doctor: ${doctorId}`);
// //     const roomName = `doctor-${doctorId}`;
// //     socket.leave(roomName);
// //     activeDoctors.delete(doctorId);
    
// //     socket.emit("doctor-offline", {
// //       doctorId,
// //       status: "offline",
// //       timestamp: new Date().toISOString()
// //     });
// //   });

// //   // Disconnect cleanup
// //   socket.on("disconnect", (reason) => {
// //     console.log(`❌ Client disconnected: ${socket.id}, reason: ${reason}`);
    
// //     // Find and remove doctor if they were registered
// //     for (const [doctorId, doctorInfo] of activeDoctors.entries()) {
// //       if (doctorInfo.socketId === socket.id) {
// //         console.log(`🧹 Removing disconnected doctor ${doctorId} from active list`);
// //         activeDoctors.delete(doctorId);
// //         break;
// //       }
// //     }
// //   });

// //   // Log all incoming events for debugging
// //   socket.onAny((eventName, ...args) => {
// //     if (eventName !== 'ping' && eventName !== 'pong') {
// //       console.log(`📡 Event received: ${eventName}`, args);
// //     }
// //   });
// // });

// // // Log server stats every 30 seconds
// // setInterval(() => {
// //   console.log(`📊 Server Stats - Connected: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}`);
// //   if (activeDoctors.size > 0) {
// //     console.log(`👨‍⚕️ Active Doctors:`, Array.from(activeDoctors.keys()));
// //   }
// // }, 30000);

// // const PORT = process.env.PORT || 4000;
// // httpServer.listen(PORT, () => {
// //   console.log(`🚀 Test Socket.IO server running on port ${PORT}`);
// //   console.log(`📡 Ready for doctor connections...`);
// // });

// import { createServer } from "http";
// import { Server } from "socket.io";

// const httpServer = createServer();

// const io = new Server(httpServer, {
//   cors: {
//     origin: [
//       "http://localhost:3000",
//       "http://127.0.0.1:3000",
//       "https://snoutiq.com"  // ← अपना live domain यहां डालें
//     ],
//     methods: ["GET", "POST"],
//     credentials: true,
//   },
//   path: "/socket.io/",
// });

// // Active doctors store (doctorId -> socket info)
// const activeDoctors = new Map();

// io.on("connection", (socket) => {
//   console.log(`⚡ Client connected: ${socket.id}`);

//   // -------------------- DOCTOR JOINS --------------------
//   socket.on("join-doctor", (doctorId) => {
//     const roomName = `doctor-${doctorId}`;
//     socket.join(roomName);

//     activeDoctors.set(doctorId, {
//       socketId: socket.id,
//       joinedAt: new Date(),
//     });

//     console.log(`✅ Doctor ${doctorId} joined (socket: ${socket.id})`);

//     // Confirm back to doctor
//     socket.emit("doctor-online", {
//       doctorId,
//       status: "online",
//       timestamp: new Date().toISOString(),
//     });

//     // Broadcast updated doctors list to all patients
//     io.emit("active-doctors", Array.from(activeDoctors.keys()));
//   });

//   // -------------------- PATIENT REQUESTS DOCTORS --------------------
//   socket.on("get-active-doctors", () => {
//     socket.emit("active-doctors", Array.from(activeDoctors.keys()));
//   });

//   // -------------------- CALL REQUEST --------------------
//   socket.on("call-requested", ({ doctorId, patientId, channel }) => {
//     console.log(`📞 Call requested: Patient ${patientId} → Doctor ${doctorId}`);

//     if (!activeDoctors.has(doctorId)) {
//       socket.emit("call-failed", {
//         error: "Doctor not available",
//         doctorId,
//         patientId,
//       });
//       return;
//     }

//     const callId = `${patientId}-${doctorId}-${Date.now()}`;
//     const roomName = `doctor-${doctorId}`;

//     // Send call to doctor
//     io.to(roomName).emit("call-requested", {
//       callId,
//       doctorId,
//       patientId,
//       channel,
//       timestamp: new Date().toISOString(),
//     });

//     // Confirm back to patient
//     socket.emit("call-sent", {
//       callId,
//       doctorId,
//       patientId,
//       channel,
//       status: "sent",
//     });
//   });

//   // -------------------- CALL ACCEPTED --------------------
//   socket.on("call-accepted", (data) => {
//     console.log("✅ Call accepted:", data);
//     io.emit("call-accepted", {
//       ...data,
//       timestamp: new Date().toISOString(),
//     });
//   });

//   // -------------------- CALL REJECTED --------------------
//   socket.on("call-rejected", (data) => {
//     console.log("❌ Call rejected:", data);
//     io.emit("call-rejected", {
//       ...data,
//       timestamp: new Date().toISOString(),
//     });
//   });

//   // -------------------- DOCTOR LEAVES --------------------
//   socket.on("leave-doctor", (doctorId) => {
//     const roomName = `doctor-${doctorId}`;
//     socket.leave(roomName);
//     activeDoctors.delete(doctorId);

//     console.log(`🚪 Doctor ${doctorId} left`);

//     socket.emit("doctor-offline", {
//       doctorId,
//       status: "offline",
//       timestamp: new Date().toISOString(),
//     });

//     // Update patients with new doctors list
//     io.emit("active-doctors", Array.from(activeDoctors.keys()));
//   });

//   // -------------------- DISCONNECT --------------------
//   socket.on("disconnect", (reason) => {
//     console.log(`❌ Disconnected: ${socket.id}, reason: ${reason}`);

//     // Cleanup doctor if disconnected
//     for (const [doctorId, info] of activeDoctors.entries()) {
//       if (info.socketId === socket.id) {
//         activeDoctors.delete(doctorId);
//         console.log(`🧹 Removed doctor ${doctorId} from active list`);

//         io.emit("active-doctors", Array.from(activeDoctors.keys()));
//         break;
//       }
//     }
//   });

//   // Debug: log all custom events (except ping/pong)
//   socket.onAny((eventName, ...args) => {
//     if (!["ping", "pong"].includes(eventName)) {
//       console.log(`📡 Event: ${eventName}`, args);
//     }
//   });
// });

// // -------------------- SERVER START --------------------
// const PORT = process.env.PORT || 4000;
// httpServer.listen(PORT, () => {
//   console.log(`🚀 Socket.IO server running on port ${PORT}`);
// });


import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer();

const allowedOrigins = [
  "http://localhost:3000",
  "http://127.0.0.1:3000",
  "https://snoutiq.com" // production domain
];

const io = new Server(httpServer, {
  cors: {
    origin: (origin, callback) => {
      // Allow requests with no origin (like mobile apps or curl)
      if (!origin || allowedOrigins.includes(origin)) {
        callback(null, true);
      } else {
        callback(new Error(`CORS blocked for origin: ${origin}`));
      }
    },
    methods: ["GET", "POST"],
    credentials: true
  },
  path: "/socket.io/",
});

// Storage for active doctors and call sessions
const activeDoctors = new Map();
const activeCalls = new Map(); // This is crucial for payment flow

io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // -------------------- DOCTOR JOINS --------------------
  socket.on("join-doctor", (doctorId) => {
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);
    
    // Store doctor with socket info
    activeDoctors.set(doctorId, {
      socketId: socket.id,
      joinedAt: new Date(),
    });

    console.log(`✅ Doctor ${doctorId} joined (socket: ${socket.id})`);

    // Confirm back to doctor
    socket.emit("doctor-online", {
      doctorId,
      status: "online",
      timestamp: new Date().toISOString(),
    });

    // Broadcast updated doctors list to all clients
    io.emit("active-doctors", Array.from(activeDoctors.keys()));
  });

  // -------------------- GET ACTIVE DOCTORS --------------------
  socket.on("get-active-doctors", () => {
    socket.emit("active-doctors", Array.from(activeDoctors.keys()));
  });

  // -------------------- CALL REQUEST --------------------
  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    console.log(`📞 Call requested: Patient ${patientId} → Doctor ${doctorId}`);

    if (!activeDoctors.has(doctorId)) {
      socket.emit("call-failed", {
        error: "Doctor not available",
        
        doctorId,
        patientId,
      });
      return;
    }

    // Create unique call ID and session
    const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    
    // Store complete call session - THIS IS KEY for payment flow
    const callSession = {
      callId,
      doctorId,
      patientId,
      channel,
      status: 'requested',
      createdAt: new Date(),
      patientSocketId: socket.id, // Store patient socket for later communication
      doctorSocketId: null // Will be set when doctor accepts
    };
    
    activeCalls.set(callId, callSession);
    console.log(`💾 Stored call session: ${callId}`);

    const roomName = `doctor-${doctorId}`;

    // Send call to doctor with full session info
    io.to(roomName).emit("call-requested", {
      callId,
      doctorId,
      patientId,
      channel,
      timestamp: new Date().toISOString(),
    });

    // Confirm to patient
    socket.emit("call-sent", {
      callId,
      doctorId,
      patientId,
      channel,
      status: "sent",
    });
  });

  // -------------------- CALL ACCEPTED --------------------
  socket.on("call-accepted", (data) => {
    const { callId, doctorId, patientId, channel } = data;
    console.log(`✅ Call accepted: ${callId} by Doctor ${doctorId}`);
    
    // Get call session
    const callSession = activeCalls.get(callId);
    
    if (!callSession) {
      console.log(`❌ Call session not found: ${callId}`);
      socket.emit("error", { message: "Call session not found" });
      return;
    }
    
    // Update call session
    callSession.status = 'accepted';
    callSession.acceptedAt = new Date();
    callSession.doctorSocketId = socket.id; // Store doctor socket
    activeCalls.set(callId, callSession);
    
    console.log(`💾 Updated call session: ${callId} - status: accepted`);

    // Send acceptance to patient with payment requirement
    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-accepted", {
        callId,
        doctorId,
        patientId,
        channel,
        requiresPayment: true,
        message: "Doctor accepted your call. Please complete payment to proceed.",
        paymentAmount: 499,
        timestamp: new Date().toISOString()
      });
      
      console.log(`📤 Sent call-accepted to patient (socket: ${callSession.patientSocketId})`);
    }
  });

  // -------------------- CALL REJECTED --------------------
  socket.on("call-rejected", (data) => {
    const { callId, reason = "rejected", doctorId, patientId } = data;
    console.log(`❌ Call rejected: ${callId} - reason: ${reason}`);
    
    const callSession = activeCalls.get(callId);
    
    if (callSession) {
      // Update session
      callSession.status = 'rejected';
      callSession.rejectedAt = new Date();
      callSession.rejectionReason = reason;
      
      // Notify patient
      if (callSession.patientSocketId) {
        io.to(callSession.patientSocketId).emit("call-rejected", {
          callId,
          doctorId,
          patientId,
          reason,
          message: reason === 'timeout' 
            ? 'Doctor did not respond within 30 seconds' 
            : 'Doctor is currently unavailable',
          timestamp: new Date().toISOString()
        });
      }
      
      // Clean up session after delay
      setTimeout(() => {
        activeCalls.delete(callId);
        console.log(`🧹 Cleaned up rejected call: ${callId}`);
      }, 30000);
    }
  });

  // -------------------- PAYMENT COMPLETED (NEW) --------------------
  socket.on("payment-completed", (data) => {
    const { callId, patientId, doctorId, channel, paymentId } = data;
    console.log(`💳 Payment completed: ${callId} - paymentId: ${paymentId}`);
    
    const callSession = activeCalls.get(callId);
    
    if (!callSession) {
      console.log(`❌ Call session not found for payment: ${callId}`);
      socket.emit("error", { message: "Call session not found" });
      return;
    }
    
    // Update call session
    callSession.status = 'payment_completed';
    callSession.paymentId = paymentId;
    callSession.paidAt = new Date();
    activeCalls.set(callId, callSession);
    
    console.log(`💾 Updated call session: ${callId} - status: payment_completed`);
    
    // Notify patient that payment is verified
    socket.emit("payment-verified", {
      callId,
      channel,
      patientId,
      doctorId,
      status: 'ready_to_connect',
      message: 'Payment successful! Connecting to video call...',
      videoUrl: `/call-page/${channel}?uid=${patientId}&role=audience&callId=${callId}`
    });
    
    // Notify doctor that patient has paid
    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", {
        callId,
        channel,
        patientId,
        doctorId,
        paymentId,
        status: 'ready_to_connect',
        message: 'Patient payment confirmed! Ready to start video call.',
        videoUrl: `/call-page/${channel}?uid=${doctorId}&role=host&callId=${callId}`
      });
      
      console.log(`📤 Sent patient-paid to doctor (socket: ${callSession.doctorSocketId})`);
    }
  });

  // -------------------- PAYMENT CANCELLED (NEW) --------------------
  socket.on("payment-cancelled", (data) => {
    const { callId, patientId, doctorId, reason } = data;
    console.log(`💸 Payment cancelled: ${callId} - reason: ${reason}`);
    
    const callSession = activeCalls.get(callId);
    
    if (callSession) {
      callSession.status = 'payment_cancelled';
      callSession.cancelledAt = new Date();
      callSession.cancellationReason = reason;
      
      // Notify doctor
      if (callSession.doctorSocketId) {
        io.to(callSession.doctorSocketId).emit("payment-cancelled", {
          callId,
          patientId,
          doctorId,
          reason,
          message: reason === 'timeout' 
            ? 'Payment window expired' 
            : 'Patient cancelled payment'
        });
      }
      
      // Clean up after delay
      setTimeout(() => {
        activeCalls.delete(callId);
        console.log(`🧹 Cleaned up cancelled payment call: ${callId}`);
      }, 60000);
    }
  });

  // -------------------- CALL ENDED (NEW) --------------------
  socket.on("call-ended", (data) => {
    const { callId, channel, userId, role } = data;
    console.log(`📞 Call ended: ${callId} by user ${userId} (${role})`);
    
    const callSession = activeCalls.get(callId);
    
    if (callSession) {
      callSession.status = 'ended';
      callSession.endedAt = new Date();
      callSession.endedBy = userId;
      
      // Notify the other party
      const targetSocketId = role === 'host' 
        ? callSession.patientSocketId 
        : callSession.doctorSocketId;
        
      if (targetSocketId) {
        io.to(targetSocketId).emit("call-ended", {
          callId,
          channel,
          endedBy: userId,
          message: 'The other party ended the call'
        });
        
        console.log(`📤 Notified other party of call end`);
      }
      
      // Clean up session
      setTimeout(() => {
        activeCalls.delete(callId);
        console.log(`🧹 Cleaned up ended call: ${callId}`);
      }, 10000);
    }
  });

  // -------------------- DOCTOR LEAVES --------------------
  socket.on("leave-doctor", (doctorId) => {
    const roomName = `doctor-${doctorId}`;
    socket.leave(roomName);
    activeDoctors.delete(doctorId);

    console.log(`🚪 Doctor ${doctorId} left`);

    socket.emit("doctor-offline", {
      doctorId,
      status: "offline",
      timestamp: new Date().toISOString(),
    });

    // Update all clients with new doctors list
    io.emit("active-doctors", Array.from(activeDoctors.keys()));
  });

  // -------------------- DISCONNECT --------------------
  socket.on("disconnect", (reason) => {
    console.log(`❌ Disconnected: ${socket.id}, reason: ${reason}`);

    // Cleanup doctor if disconnected
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        activeDoctors.delete(doctorId);
        console.log(`🧹 Removed doctor ${doctorId} from active list`);
        io.emit("active-doctors", Array.from(activeDoctors.keys()));
        break;
      }
    }
    
    // Handle active calls cleanup
    for (const [callId, callSession] of activeCalls.entries()) {
      if (callSession.patientSocketId === socket.id || callSession.doctorSocketId === socket.id) {
        callSession.status = 'disconnected';
        callSession.disconnectedAt = new Date();
        
        // Notify the other party
        const otherSocketId = callSession.patientSocketId === socket.id 
          ? callSession.doctorSocketId 
          : callSession.patientSocketId;
          
        if (otherSocketId) {
          io.to(otherSocketId).emit("other-party-disconnected", {
            callId,
            message: 'The other party disconnected unexpectedly'
          });
        }
        
        console.log(`🧹 Handling disconnection for call: ${callId}`);
      }
    }
  });

  // -------------------- DEBUG EVENTS --------------------
  socket.on("ping", (data) => {
    socket.emit("pong", {
      ...data,
      serverTime: new Date().toISOString(),
      receivedAt: Date.now()
    });
  });

  socket.on("get-server-status", () => {
    socket.emit("server-status", {
      connected: true,
      activeDoctors: activeDoctors.size,
      activeCalls: activeCalls.size,
      doctorsList: Array.from(activeDoctors.keys()),
      callsList: Array.from(activeCalls.keys()),
      uptime: process.uptime(),
      timestamp: new Date().toISOString()
    });
  });

  // Log all custom events (except ping/pong)
  socket.onAny((eventName, ...args) => {
    if (!["ping", "pong"].includes(eventName)) {
      console.log(`📡 Event: ${eventName}`, args);
    }
  });
});

// -------------------- PERIODIC CLEANUP --------------------
// Clean up old call sessions every 5 minutes
setInterval(() => {
  const now = new Date();
  const cleanupThreshold = 30 * 60 * 1000; // 30 minutes
  
  for (const [callId, callSession] of activeCalls.entries()) {
    const age = now - callSession.createdAt;
    
    if (age > cleanupThreshold && 
        !['payment_completed', 'ended'].includes(callSession.status)) {
      console.log(`🧹 Cleaning up old call session: ${callId} (age: ${Math.round(age/60000)} min)`);
      activeCalls.delete(callId);
    }
  }
}, 5 * 60 * 1000);

// -------------------- SERVER START --------------------
// const PORT = process.env.PORT || 4000;
// httpServer.listen(PORT, () => {
//   console.log(`🚀 Socket.IO server running on port ${PORT}`);
//   console.log(`📊 Features: Doctor rooms, Call sessions, Payment flow, Video call integration`);
// });

const PORT = process.env.PORT || 4000;
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
});


// Log server stats periodically
setInterval(() => {
  console.log(`📊 Stats - Connections: ${io.engine.clientsCount}, Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}`);
  if (activeCalls.size > 0) {
    console.log(`📞 Active Calls:`, Array.from(activeCalls.keys()));
  }
}, 30000);