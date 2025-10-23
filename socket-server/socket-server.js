import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer();

const io = new Server(httpServer, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
    credentials: false,
  },
  path: "/socket.io/",
});

// Active doctors and call sessions
const activeDoctors = new Map(); // doctorId -> { socketId, joinedAt }
const activeCalls = new Map();   // callId -> { callId, doctorId, patientId, channel, status, ... }

const isDoctorBusy = (doctorId) => {
  for (const [, call] of activeCalls.entries()) {
    if (
      call.doctorId === doctorId &&
      call.status &&
      ["requested", "accepted", "payment_completed"].includes(call.status)
    ) {
      return true;
    }
  }
  return false;
};

const emitAvailableDoctors = () => {
  const available = [];
  for (const [doctorId] of activeDoctors.entries()) {
    if (!isDoctorBusy(doctorId)) available.push(doctorId);
  }
  console.log(`📤 Broadcasting ${available.length} available doctors:`, available);
  io.emit("active-doctors", available);
};

io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // Doctor joins
  socket.on("join-doctor", (doctorId) => {
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);
    
    // ✅ Check if doctor already exists
    if (activeDoctors.has(doctorId)) {
      console.log(`⚠️ Doctor ${doctorId} reconnecting (old socket: ${activeDoctors.get(doctorId).socketId})`);
    }
    
    activeDoctors.set(doctorId, { socketId: socket.id, joinedAt: new Date() });
    console.log(`✅ Doctor ${doctorId} joined (Total active: ${activeDoctors.size})`);
    
    socket.emit("doctor-online", { 
      doctorId, 
      status: "online", 
      timestamp: new Date().toISOString() 
    });
    
    emitAvailableDoctors();
  });

  // Get active doctors
  socket.on("get-active-doctors", () => {
    const available = [];
    const busy = [];
    
    for (const [doctorId] of activeDoctors.entries()) {
      if (isDoctorBusy(doctorId)) {
        busy.push(doctorId);
      } else {
        available.push(doctorId);
      }
    }
    
    console.log(`📊 Active doctors request: ${available.length} available, ${busy.length} busy`);
    socket.emit("active-doctors", available);
  });

  // Call request
  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    console.log(`📞 Call request: Patient ${patientId} → Doctor ${doctorId}`);
    
    if (!activeDoctors.has(doctorId)) {
      console.log(`❌ Doctor ${doctorId} not available`);
      socket.emit("call-failed", { error: "Doctor not available", doctorId, patientId });
      return;
    }
    if (isDoctorBusy(doctorId)) {
      console.log(`⏳ Doctor ${doctorId} is busy`);
      socket.emit("doctor-busy", { error: "Doctor is currently on another call", doctorId, patientId });
      return;
    }

    const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    const callSession = { 
      callId, 
      doctorId, 
      patientId, 
      channel, 
      status: 'requested', 
      createdAt: new Date(), 
      patientSocketId: socket.id, 
      doctorSocketId: null 
    };
    activeCalls.set(callId, callSession);

    io.to(`doctor-${doctorId}`).emit("call-requested", { 
      callId, 
      doctorId, 
      patientId, 
      channel, 
      timestamp: new Date().toISOString() 
    });
    
    socket.emit("call-sent", { callId, doctorId, patientId, channel, status: "sent" });
    emitAvailableDoctors();
  });

  // Call accepted
  socket.on("call-accepted", (data) => {
    const { callId, doctorId, patientId, channel } = data;
    console.log(`✅ Call ${callId} accepted by doctor ${doctorId}`);
    
    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    callSession.status = 'accepted';
    callSession.acceptedAt = new Date();
    callSession.doctorSocketId = socket.id;
    activeCalls.set(callId, callSession);

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-accepted", {
        callId, doctorId, patientId, channel,
        requiresPayment: true,
        message: "Doctor accepted your call. Please complete payment to proceed.",
        paymentAmount: 499,
        timestamp: new Date().toISOString()
      });
    }
    emitAvailableDoctors();
  });

  // Call rejected
  socket.on("call-rejected", (data) => {
    const { callId, reason = "rejected" } = data;
    console.log(`❌ Call ${callId} rejected: ${reason}`);
    
    const callSession = activeCalls.get(callId);
    if (!callSession) return;

    callSession.status = 'rejected';
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-rejected", { 
        callId, 
        reason, 
        message: reason === 'timeout' ? 'Doctor did not respond' : 'Doctor unavailable', 
        timestamp: new Date().toISOString() 
      });
    }

    setTimeout(() => {
      activeCalls.delete(callId);
      emitAvailableDoctors();
    }, 30000);
  });

  // Payment completed
  socket.on("payment-completed", (data) => {
    const { callId, patientId, doctorId, channel, paymentId } = data;
    console.log(`💰 Payment completed for call ${callId}`);
    
    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    callSession.status = 'payment_completed';
    callSession.paymentId = paymentId;
    callSession.paidAt = new Date();
    if (channel) callSession.channel = channel;
    activeCalls.set(callId, callSession);

    socket.emit("payment-verified", { 
      callId, 
      channel: callSession.channel, 
      patientId, 
      doctorId, 
      status: 'ready_to_connect', 
      message: 'Payment successful!', 
      videoUrl: `/call-page/${callSession.channel}?uid=${patientId}&role=audience&callId=${callId}` 
    });

    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", { 
        callId, 
        channel: callSession.channel, 
        patientId, 
        doctorId, 
        paymentId, 
        status: 'ready_to_connect', 
        message: 'Patient payment confirmed!', 
        videoUrl: `/call-page/${callSession.channel}?uid=${doctorId}&role=host&callId=${callId}&doctorId=${doctorId}&patientId=${patientId}` 
      });
    }
    emitAvailableDoctors();
  });

  // Call ended
  socket.on("call-ended", ({ callId, userId, role }) => {
    console.log(`🔚 Call ${callId} ended by ${userId} (${role})`);
    
    const callSession = activeCalls.get(callId);
    if (!callSession) return;

    callSession.status = 'ended';
    callSession.endedAt = new Date();
    callSession.endedBy = userId;

    const targetSocketId = role === 'host' ? callSession.patientSocketId : callSession.doctorSocketId;
    if (targetSocketId) {
      io.to(targetSocketId).emit("call-ended", { 
        callId, 
        endedBy: userId, 
        message: 'Call ended' 
      });
    }

    setTimeout(() => {
      activeCalls.delete(callId);
      emitAvailableDoctors();
    }, 10000);
  });

  // Doctor leaves
  socket.on("leave-doctor", (doctorId) => {
    console.log(`👋 Doctor ${doctorId} leaving`);
    
    socket.leave(`doctor-${doctorId}`);
    activeDoctors.delete(doctorId);
    
    socket.emit("doctor-offline", { 
      doctorId, 
      status: "offline", 
      timestamp: new Date().toISOString() 
    });
    
    emitAvailableDoctors();
  });

  // Disconnect handling
  socket.on("disconnect", (reason) => {
    console.log(`❌ Disconnected: ${socket.id}, reason: ${reason}`);

    // Clean up doctor
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        console.log(`🔌 Removing disconnected doctor ${doctorId}`);
        activeDoctors.delete(doctorId);
        emitAvailableDoctors();
      }
    }

    // Clean up active calls
    for (const [callId, callSession] of activeCalls.entries()) {
      if (callSession.patientSocketId === socket.id || callSession.doctorSocketId === socket.id) {
        console.log(`🔌 Handling disconnect for call ${callId}`);
        callSession.status = 'disconnected';
        callSession.disconnectedAt = new Date();
        const otherSocketId = callSession.patientSocketId === socket.id ? callSession.doctorSocketId : callSession.patientSocketId;
        if (otherSocketId) {
          io.to(otherSocketId).emit("other-party-disconnected", { 
            callId, 
            message: 'The other party disconnected unexpectedly' 
          });
        }
      }
    }
  });
});

// Periodic cleanup - 5 minute timeout
setInterval(() => {
  const now = new Date();
  const threshold = 5 * 60 * 1000;
  for (const [callId, callSession] of activeCalls.entries()) {
    const age = now - callSession.createdAt;
    if (age > threshold && !['payment_completed', 'ended'].includes(callSession.status)) {
      console.log(`⏰ Auto-ending call ${callId} after 5 minutes timeout`);
      
      if (callSession.patientSocketId) {
        io.to(callSession.patientSocketId).emit("call-timeout", { 
          callId, 
          message: 'Call timed out after 5 minutes' 
        });
      }
      if (callSession.doctorSocketId) {
        io.to(callSession.doctorSocketId).emit("call-timeout", { 
          callId, 
          message: 'Call timed out after 5 minutes' 
        });
      }
      
      activeCalls.delete(callId);
      emitAvailableDoctors();
    }
  }
}, 5 * 60 * 1000);

// Start server
const PORT = process.env.PORT || 4000;
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
});

// Periodic stats log
setInterval(() => {
  console.log(`📊 Stats: Connections: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}`);
  console.log(`👨‍⚕️ Active Doctor IDs:`, Array.from(activeDoctors.keys()));
}, 30000);