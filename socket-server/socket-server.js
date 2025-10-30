import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer((req, res) => {
  try {
    const url = new URL(req.url, `http://${req.headers.host}`);

    if (req.method === "OPTIONS") {
      res.writeHead(204, {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type",
      });
      res.end();
      return;
    }

    if (req.method === "GET" && url.pathname === "/health") {
      res.writeHead(200, { "Content-Type": "application/json", "Access-Control-Allow-Origin": "*" });
      res.end(JSON.stringify({ status: "ok" }));
      return;
    }

    if (req.method === "GET" && url.pathname === "/active-doctors") {
      const available = [];
      for (const [doctorId] of activeDoctors.entries()) {
        if (!isDoctorBusy(doctorId)) {
          available.push(doctorId);
        }
      }

      res.writeHead(200, { "Content-Type": "application/json", "Access-Control-Allow-Origin": "*" });
      res.end(JSON.stringify({
        activeDoctors: available,
        updatedAt: new Date().toISOString(),
      }));
      return;
    }

    res.writeHead(404, { "Content-Type": "application/json", "Access-Control-Allow-Origin": "*" });
    res.end(JSON.stringify({ error: "Not Found" }));
  } catch (error) {
    console.error("HTTP server error:", error);
    res.writeHead(500, { "Content-Type": "application/json", "Access-Control-Allow-Origin": "*" });
    res.end(JSON.stringify({ error: "Server error" }));
  }
});

const io = new Server(httpServer, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
    credentials: false,
  },
  path: "/socket.io/",
});

// Active doctors and call sessions
const activeDoctors = new Map(); // doctorId -> { socketId, joinedAt, lastSeen, connectionStatus, location, offlineTimer }
const activeCalls = new Map();   // callId -> { callId, doctorId, patientId, channel, status, ... }

const DOCTOR_HEARTBEAT_EVENT = "doctor-heartbeat";
const DOCTOR_GRACE_EVENT = "doctor-grace";
const DOCTOR_HEARTBEAT_INTERVAL_MS = 30_000;
const DOCTOR_GRACE_PERIOD_MS = 5 * 60 * 1000; // keep doctors available for up to 5 minutes while backgrounded

const clearDoctorTimer = (entry) => {
  if (!entry) return;
  if (entry.offlineTimer) {
    clearTimeout(entry.offlineTimer);
    entry.offlineTimer = null;
  }
};

const upsertDoctorEntry = (doctorId, values = {}) => {
  if (!doctorId) return null;
  const existing = activeDoctors.get(doctorId) || {};
  clearDoctorTimer(existing);
  const entry = {
    ...existing,
    ...values,
    lastSeen: values.lastSeen || existing.lastSeen || new Date(),
    joinedAt: values.joinedAt || existing.joinedAt || new Date(),
  };
  if (!entry.connectionStatus) {
    entry.connectionStatus = "connected";
  }
  activeDoctors.set(doctorId, entry);
  return entry;
};

const scheduleDoctorRemoval = (doctorId, socketId) => {
  const entry = activeDoctors.get(doctorId);
  if (!entry) return;
  if (socketId && entry.socketId && entry.socketId !== socketId) {
    return;
  }
  clearDoctorTimer(entry);
  entry.connectionStatus = "grace";
  entry.disconnectedAt = new Date();
  entry.offlineTimer = setTimeout(() => {
    const current = activeDoctors.get(doctorId);
    if (!current) return;
    if (socketId && current.socketId && current.socketId !== socketId) {
      return;
    }
    activeDoctors.delete(doctorId);
    emitAvailableDoctors();
  }, DOCTOR_GRACE_PERIOD_MS);
  activeDoctors.set(doctorId, entry);
};

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
  for (const [doctorId, info] of activeDoctors.entries()) {
    const status = info.connectionStatus || "connected";
    if (!["connected", "grace"].includes(status)) continue;
    if (!isDoctorBusy(doctorId)) available.push(doctorId);
  }
  console.log(`📤 Broadcasting ${available.length} available doctors:`, available);
  io.emit("active-doctors", available);
};

const deliverPendingSessionsToDoctor = (doctorId, socket) => {
  const roomName = `doctor-${doctorId}`;
  for (const [callId, callSession] of activeCalls.entries()) {
    if (callSession.doctorId !== doctorId) continue;
    if (["ended", "rejected", "disconnected"].includes(callSession.status)) continue;

    callSession.doctorSocketId = socket.id;
    activeCalls.set(callId, callSession);

    if (callSession.status === "requested") {
      io.to(roomName).emit("call-requested", {
        callId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        channel: callSession.channel,
        timestamp: new Date().toISOString(),
        queued: true,
      });
    }

    if (callSession.status === "payment_completed") {
      io.to(roomName).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId: callSession.patientId,
        doctorId: callSession.doctorId,
        paymentId: callSession.paymentId,
        status: 'ready_to_connect',
        message: 'Patient payment confirmed!',
        videoUrl: `/call-page/${callSession.channel}?uid=${callSession.doctorId}&role=host&callId=${callId}&doctorId=${callSession.doctorId}&patientId=${callSession.patientId}`,
        queued: true,
      });
    }
  }
};

io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // Doctor joins
  socket.on("join-doctor", (doctorId) => {
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);

    const existing = activeDoctors.get(doctorId);
    if (existing && existing.socketId !== socket.id) {
      console.log(`⚠️ Doctor ${doctorId} reconnecting (old socket: ${existing.socketId})`);
    }

    upsertDoctorEntry(doctorId, {
      socketId: socket.id,
      joinedAt: new Date(),
      lastSeen: new Date(),
      connectionStatus: "connected",
    });

    console.log(`✅ Doctor ${doctorId} joined (Total active: ${activeDoctors.size})`);

    socket.emit("doctor-online", { 
      doctorId, 
      status: "online", 
      timestamp: new Date().toISOString() 
    });

    emitAvailableDoctors();

    deliverPendingSessionsToDoctor(doctorId, socket);
  });

  socket.on(DOCTOR_HEARTBEAT_EVENT, (payload = {}) => {
    const doctorId = Number(payload.doctorId || payload.id);
    if (!doctorId) return;

    const existing = activeDoctors.get(doctorId);
    const wasGrace = existing?.connectionStatus === "grace";
    const entry = upsertDoctorEntry(doctorId, {
      socketId: socket.id,
      lastSeen: new Date(),
      connectionStatus: "connected",
    });

    if (entry && wasGrace) {
      socket.emit(DOCTOR_GRACE_EVENT, {
        doctorId,
        status: "connected",
        timestamp: new Date().toISOString(),
      });
    }
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

    if (isDoctorBusy(doctorId)) {
      console.log(`⏳ Doctor ${doctorId} is busy`);
      socket.emit("doctor-busy", { error: "Doctor is currently on another call", doctorId, patientId });
      return;
    }


    const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    const doctorEntry = activeDoctors.get(doctorId);
    const callSession = { 
      callId, 
      doctorId, 
      patientId, 
      channel, 
      status: 'requested', 
      createdAt: new Date(), 
      patientSocketId: socket.id, 
      doctorSocketId: doctorEntry?.socketId || null, 
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
    const { callId, reason = "rejected", doctorId, patientId } = data;
    console.log(`❌ Call ${callId} rejected by doctor ${doctorId}: ${reason}`);
    
    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found for rejection`);
      return;
    }

    callSession.status = 'rejected';
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    // Method 1: Notify patient via stored socket ID (most reliable)
    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-rejected", { 
        callId, 
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        reason, 
        message: reason === 'timeout' ? 'Doctor did not respond within 5 minutes' : 'Doctor is currently unavailable', 
        timestamp: new Date().toISOString() 
      });
      console.log(`📤 Notified patient via socket: ${callSession.patientSocketId}`);
    }

    // Method 2: Notify via rooms (backup)
    if (callSession.patientId) {
      io.to(`patient-${callSession.patientId}`).emit("call-rejected", {
        callId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        reason,
        message: reason === 'timeout' ? 'Doctor did not respond within 5 minutes' : 'Doctor is currently unavailable',
        timestamp: new Date().toISOString()
      });
    }

    // Method 3: Broadcast status update (additional backup)
    io.emit("call-status-update", {
      callId,
      status: 'rejected',
      rejectedBy: 'doctor',
      reason,
      timestamp: new Date().toISOString()
    });

    // Clean up after delay
    setTimeout(() => {
      activeCalls.delete(callId);
      emitAvailableDoctors();
      console.log(`🗑️ Cleaned up rejected call ${callId}`);
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
    } else {
      io.to(`doctor-${doctorId}`).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId,
        doctorId,
        paymentId,
        status: 'ready_to_connect',
        message: 'Patient payment confirmed!',
        videoUrl: `/call-page/${callSession.channel}?uid=${doctorId}&role=host&callId=${callId}&doctorId=${doctorId}&patientId=${patientId}`,
        queued: true,
      });
    }
    emitAvailableDoctors();
  });

  // ✅ ENHANCED: Call ended - notify both parties with multiple methods
  socket.on("call-ended", ({ callId, userId, role, doctorId, patientId, channel }) => {
    console.log(`🔚 Call ${callId} ended by ${userId} (${role})`);
    
    const callSession = activeCalls.get(callId);
    
    if (callSession) {
      callSession.status = 'ended';
      callSession.endedAt = new Date();
      callSession.endedBy = userId;

      // Determine who to notify based on role
      const isDoctorEnding = role === 'host';
      const isPatientEnding = role === 'audience';

      // Method 1: Notify via stored socket IDs (most reliable)
      if (isDoctorEnding && callSession.patientSocketId) {
        io.to(callSession.patientSocketId).emit("call-ended-by-other", { 
          callId, 
          endedBy: 'doctor',
          reason: 'ended',
          message: 'Doctor has ended the call',
          timestamp: new Date().toISOString()
        });
        console.log(`📤 Notified patient via socket: ${callSession.patientSocketId}`);
      }
      
      if (isPatientEnding && callSession.doctorSocketId) {
        io.to(callSession.doctorSocketId).emit("call-ended-by-other", { 
          callId, 
          endedBy: 'patient',
          reason: 'ended',
          message: 'Patient has ended the call',
          timestamp: new Date().toISOString()
        });
        console.log(`📤 Notified doctor via socket: ${callSession.doctorSocketId}`);
      }

      // Method 2: Notify via rooms (backup for reliability)
      if (callSession.doctorId) {
        io.to(`doctor-${callSession.doctorId}`).emit("call-ended-by-other", {
          callId,
          endedBy: isDoctorEnding ? 'doctor' : 'patient',
          reason: 'ended',
          message: isDoctorEnding ? 'Doctor has ended the call' : 'Patient has ended the call',
          timestamp: new Date().toISOString()
        });
      }
      
      if (callSession.patientId) {
        io.to(`patient-${callSession.patientId}`).emit("call-ended-by-other", {
          callId,
          endedBy: isDoctorEnding ? 'doctor' : 'patient',
          reason: 'ended',
          message: isDoctorEnding ? 'Doctor has ended the call' : 'Patient has ended the call',
          timestamp: new Date().toISOString()
        });
      }
    }

    // Method 3: Broadcast to all with this callId (additional backup)
    io.emit("call-status-update", {
      callId,
      status: 'ended',
      endedBy: role,
      timestamp: new Date().toISOString()
    });

    // Clean up after delay
    setTimeout(() => {
      activeCalls.delete(callId);
      emitAvailableDoctors();
      console.log(`🗑️ Cleaned up ended call ${callId}`);
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

  // Update doctor location
  socket.on("update-doctor-location", ({ doctorId, latitude, longitude }) => {
    console.log(`📍 Updating location for doctor ${doctorId}: ${latitude}, ${longitude}`);
    
    const doctor = activeDoctors.get(doctorId);
    if (doctor) {
      doctor.location = { latitude, longitude, updatedAt: new Date() };
      activeDoctors.set(doctorId, doctor);
      
      socket.emit("location-updated", {
        doctorId,
        latitude,
        longitude,
        timestamp: new Date().toISOString()
      });
      
      emitAvailableDoctors();
    }
  });

  // ✅ ENHANCED: Disconnect handling with proper notification to other party
  socket.on("disconnect", (reason) => {
    console.log(`❌ Disconnected: ${socket.id}, reason: ${reason}`);

    // Clean up doctor
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        console.log(`🔌 Doctor ${doctorId} socket disconnected, entering grace period`);
        scheduleDoctorRemoval(doctorId, socket.id);
        emitAvailableDoctors();
      }
    }

    // ✅ Clean up active calls and notify other party
    for (const [callId, callSession] of activeCalls.entries()) {
      // Check if disconnected socket is part of this call
      if (callSession.patientSocketId === socket.id || callSession.doctorSocketId === socket.id) {
        console.log(`🔌 Handling disconnect for call ${callId}`);
        
        const isDoctor = callSession.doctorSocketId === socket.id;
        const isPatient = callSession.patientSocketId === socket.id;
        
        // Mark call as disconnected
        callSession.status = 'disconnected';
        callSession.disconnectedAt = new Date();
        callSession.disconnectedBy = isDoctor ? 'doctor' : 'patient';
        
        // Get the OTHER party's socket ID
        const otherSocketId = isDoctor 
          ? callSession.patientSocketId 
          : callSession.doctorSocketId;
        
        // Method 1: Notify via socket ID
        if (otherSocketId) {
          io.to(otherSocketId).emit("other-party-disconnected", { 
            callId,
            disconnectedBy: isDoctor ? 'doctor' : 'patient',
            message: `The ${isDoctor ? 'doctor' : 'patient'} disconnected unexpectedly`,
            timestamp: new Date().toISOString()
          });
          
          console.log(`📤 Sent disconnect notification to ${isDoctor ? 'patient' : 'doctor'} (socket: ${otherSocketId})`);
        }
        
        // Method 2: Notify via rooms (backup)
        if (callSession.doctorId) {
          socket.to(`doctor-${callSession.doctorId}`).emit("call-ended-by-other", {
            callId,
            endedBy: isDoctor ? 'doctor' : 'patient',
            reason: 'disconnect',
            message: 'Call ended due to connection loss',
            timestamp: new Date().toISOString()
          });
        }
        
        if (callSession.patientId) {
          socket.to(`patient-${callSession.patientId}`).emit("call-ended-by-other", {
            callId,
            endedBy: isDoctor ? 'doctor' : 'patient',
            reason: 'disconnect',
            message: 'Call ended due to connection loss',
            timestamp: new Date().toISOString()
          });
        }

        // Method 3: Broadcast to all (additional backup)
        io.emit("call-status-update", {
          callId,
          status: 'disconnected',
          disconnectedBy: isDoctor ? 'doctor' : 'patient',
          timestamp: new Date().toISOString()
        });
        
        // Clean up the call after a delay
        setTimeout(() => {
          activeCalls.delete(callId);
          emitAvailableDoctors();
          console.log(`🗑️ Cleaned up disconnected call ${callId}`);
        }, 5000);
      }
    }
  });
});

setInterval(() => {
  const now = Date.now();
  for (const [doctorId, info] of activeDoctors.entries()) {
    const lastSeen = info.lastSeen ? new Date(info.lastSeen).getTime() : 0;
    if (!lastSeen) continue;
    if (now - lastSeen > DOCTOR_GRACE_PERIOD_MS * 2) {
      console.log(`⏳ Removing stale doctor ${doctorId} after heartbeat timeout`);
      activeDoctors.delete(doctorId);
      emitAvailableDoctors();
    }
  }
}, DOCTOR_HEARTBEAT_INTERVAL_MS);

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
