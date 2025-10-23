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
  pingTimeout: 60000,
  pingInterval: 25000,
});

// Active doctors and call sessions
const activeDoctors = new Map();
const activeCalls = new Map();
const callTimeouts = new Map(); // Track call timeouts to prevent duplicates

const isDoctorBusy = (doctorId) => {
  for (const [, call] of activeCalls.entries()) {
    if (
      call.doctorId === doctorId &&
      call.status &&
      ["requested", "accepted", "payment_completed", "in_progress"].includes(call.status)
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

const cleanupCall = (callId, reason = "cleanup") => {
  console.log(`🧹 Cleaning up call ${callId} - Reason: ${reason}`);
  
  // Clear any pending timeouts
  if (callTimeouts.has(callId)) {
    clearTimeout(callTimeouts.get(callId));
    callTimeouts.delete(callId);
  }
  
  activeCalls.delete(callId);
  emitAvailableDoctors();
};

io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // Doctor joins
  socket.on("join-doctor", (doctorId) => {
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);
    
    if (activeDoctors.has(doctorId)) {
      const oldSocketId = activeDoctors.get(doctorId).socketId;
      console.log(`⚠️ Doctor ${doctorId} reconnecting (old socket: ${oldSocketId})`);
      // Disconnect old socket
      io.sockets.sockets.get(oldSocketId)?.disconnect(true);
    }
    
    activeDoctors.set(doctorId, { 
      socketId: socket.id, 
      joinedAt: new Date(),
      isOnline: true 
    });
    
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
    
    if (!activeDoctors.has(doctorId) || !activeDoctors.get(doctorId).isOnline) {
      console.log(`❌ Doctor ${doctorId} not available`);
      socket.emit("call-failed", { 
        error: "Doctor not available", 
        doctorId, 
        patientId,
        canRetry: true,
        message: "Doctor is currently offline. Please try again or choose another doctor."
      });
      return;
    }
    
    if (isDoctorBusy(doctorId)) {
      console.log(`⏳ Doctor ${doctorId} is busy`);
      socket.emit("doctor-busy", { 
        error: "Doctor is currently on another call", 
        doctorId, 
        patientId,
        canRetry: true,
        message: "Doctor is currently on another call. Please wait or choose another doctor."
      });
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

    // Auto-reject after 30 seconds if doctor doesn't respond
    const timeoutId = setTimeout(() => {
      const call = activeCalls.get(callId);
      if (call && call.status === 'requested') {
        console.log(`⏰ Call ${callId} timeout - doctor didn't respond`);
        
        if (call.patientSocketId) {
          io.to(call.patientSocketId).emit("call-timeout", {
            callId,
            message: "Doctor didn't respond to the call request",
            canRetry: true
          });
        }
        
        cleanupCall(callId, "doctor_no_response");
      }
    }, 30000);
    
    callTimeouts.set(callId, timeoutId);

    io.to(`doctor-${doctorId}`).emit("call-requested", { 
      callId, 
      doctorId, 
      patientId, 
      channel, 
      timestamp: new Date().toISOString() 
    });
    
    socket.emit("call-sent", { 
      callId, 
      doctorId, 
      patientId, 
      channel, 
      status: "sent",
      message: "Calling doctor..." 
    });
    
    emitAvailableDoctors();
  });

  // Call accepted
  socket.on("call-accepted", (data) => {
    const { callId, doctorId, patientId, channel } = data;
    console.log(`✅ Call ${callId} accepted by doctor ${doctorId}`);
    
    // Clear timeout
    if (callTimeouts.has(callId)) {
      clearTimeout(callTimeouts.get(callId));
      callTimeouts.delete(callId);
    }
    
    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    callSession.status = 'accepted';
    callSession.acceptedAt = new Date();
    callSession.doctorSocketId = socket.id;
    activeCalls.set(callId, callSession);

    // Payment timeout - 2 minutes
    const paymentTimeoutId = setTimeout(() => {
      const call = activeCalls.get(callId);
      if (call && call.status === 'accepted') {
        console.log(`⏰ Payment timeout for call ${callId}`);
        
        if (call.patientSocketId) {
          io.to(call.patientSocketId).emit("payment-timeout", {
            callId,
            message: "Payment time expired"
          });
        }
        
        if (call.doctorSocketId) {
          io.to(call.doctorSocketId).emit("payment-cancelled", {
            callId,
            reason: "timeout",
            message: "Patient didn't complete payment in time"
          });
        }
        
        cleanupCall(callId, "payment_timeout");
      }
    }, 120000);
    
    callTimeouts.set(callId, paymentTimeoutId);

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
    }
    
    emitAvailableDoctors();
  });

  // Call rejected
  socket.on("call-rejected", (data) => {
    const { callId, reason = "rejected" } = data;
    console.log(`❌ Call ${callId} rejected: ${reason}`);
    
    // Clear timeout
    if (callTimeouts.has(callId)) {
      clearTimeout(callTimeouts.get(callId));
      callTimeouts.delete(callId);
    }
    
    const callSession = activeCalls.get(callId);
    if (!callSession) return;

    callSession.status = 'rejected';
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-rejected", { 
        callId, 
        reason, 
        canRetry: true,
        message: reason === 'timeout' 
          ? 'Doctor did not respond to your call' 
          : 'Doctor is currently unavailable', 
        timestamp: new Date().toISOString() 
      });
    }

    cleanupCall(callId, "rejected");
  });

  // Payment completed
  socket.on("payment-completed", (data) => {
    const { callId, patientId, doctorId, channel, paymentId } = data;
    console.log(`💰 Payment completed for call ${callId}`);
    
    // Clear payment timeout
    if (callTimeouts.has(callId)) {
      clearTimeout(callTimeouts.get(callId));
      callTimeouts.delete(callId);
    }
    
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
      message: 'Payment successful! Connecting to video call...', 
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
        message: 'Patient payment confirmed! Joining call...', 
        videoUrl: `/call-page/${callSession.channel}?uid=${doctorId}&role=host&callId=${callId}&doctorId=${doctorId}&patientId=${patientId}` 
      });
    }
    
    emitAvailableDoctors();
  });

  // Call started (when both parties join video)
  socket.on("call-started", ({ callId }) => {
    const callSession = activeCalls.get(callId);
    if (callSession) {
      callSession.status = 'in_progress';
      callSession.startedAt = new Date();
      activeCalls.set(callId, callSession);
      console.log(`🎥 Call ${callId} started`);
    }
  });

  // Call ended
  socket.on("call-ended", ({ callId, channel, doctorId, patientId }) => {
    console.log(`🔚 Call ${callId} ended`);
    
    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`⚠️ Call session ${callId} not found during end`);
      return;
    }

    callSession.status = 'ended';
    callSession.endedAt = new Date();

    // Notify both parties
    if (callSession.patientSocketId && callSession.patientSocketId !== socket.id) {
      io.to(callSession.patientSocketId).emit("call-ended-by-other", { 
        callId,
        message: 'Call ended by doctor'
      });
    }
    
    if (callSession.doctorSocketId && callSession.doctorSocketId !== socket.id) {
      io.to(callSession.doctorSocketId).emit("call-ended-by-other", { 
        callId,
        message: 'Call ended by patient'
      });
    }

    cleanupCall(callId, "normal_end");
  });

  // Payment cancelled
  socket.on("payment-cancelled", ({ callId, reason }) => {
    console.log(`💳 Payment cancelled for call ${callId}: ${reason}`);
    
    // Clear timeout
    if (callTimeouts.has(callId)) {
      clearTimeout(callTimeouts.get(callId));
      callTimeouts.delete(callId);
    }
    
    const callSession = activeCalls.get(callId);
    if (!callSession) return;

    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("payment-cancelled", {
        callId,
        reason: reason || "user_cancelled",
        message: "Patient cancelled the payment"
      });
    }

    cleanupCall(callId, "payment_cancelled");
  });

  // Doctor leaves
  socket.on("leave-doctor", (doctorId) => {
    console.log(`👋 Doctor ${doctorId} leaving`);
    
    socket.leave(`doctor-${doctorId}`);
    
    const doctorInfo = activeDoctors.get(doctorId);
    if (doctorInfo && doctorInfo.socketId === socket.id) {
      activeDoctors.delete(doctorId);
      
      socket.emit("doctor-offline", { 
        doctorId, 
        status: "offline", 
        timestamp: new Date().toISOString() 
      });
      
      emitAvailableDoctors();
    }
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

    // Handle active calls
    for (const [callId, callSession] of activeCalls.entries()) {
      if (callSession.patientSocketId === socket.id || callSession.doctorSocketId === socket.id) {
        console.log(`🔌 Handling disconnect for call ${callId}`);
        
        const isPatient = callSession.patientSocketId === socket.id;
        const otherSocketId = isPatient ? callSession.doctorSocketId : callSession.patientSocketId;
        const disconnectedParty = isPatient ? 'patient' : 'doctor';
        
        // Only notify if call was in progress
        if (['payment_completed', 'in_progress'].includes(callSession.status)) {
          if (otherSocketId) {
            io.to(otherSocketId).emit("other-party-disconnected", { 
              callId, 
              disconnectedParty,
              message: `The ${disconnectedParty} disconnected from the call`
            });
          }
        }
        
        cleanupCall(callId, `${disconnectedParty}_disconnect`);
      }
    }
  });
});

// Periodic cleanup - remove stale calls every minute
setInterval(() => {
  const now = new Date();
  const staleThreshold = 10 * 60 * 1000; // 10 minutes
  
  for (const [callId, callSession] of activeCalls.entries()) {
    const age = now - callSession.createdAt;
    
    if (age > staleThreshold) {
      console.log(`⏰ Auto-cleaning stale call ${callId} (age: ${Math.floor(age/1000)}s)`);
      cleanupCall(callId, "stale");
    }
  }
}, 60000);

// Start server
const PORT = process.env.PORT || 4000;
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
  console.log(`🔌 WebSocket endpoint: ws://0.0.0.0:${PORT}/socket.io/`);
});

// Periodic stats log
setInterval(() => {
  console.log(`📊 Stats: Connections: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}`);
  if (activeDoctors.size > 0) {
    console.log(`👨‍⚕️ Active Doctor IDs:`, Array.from(activeDoctors.keys()));
  }
}, 30000);