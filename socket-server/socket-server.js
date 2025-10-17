import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer();

const io = new Server(httpServer, {
  cors: {
    origin: "*", // ✅ sabhi origins allowed
    methods: ["GET", "POST"],
    credentials: false, // ❌ credentials true ke saath "*" allowed nahi hota
  },
  path: "/socket.io/",
});

// Active doctors and call sessions
const activeDoctors = new Map();
const activeCalls = new Map();

io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // Doctor joins
  socket.on("join-doctor", (doctorId) => {
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);
    activeDoctors.set(doctorId, { socketId: socket.id, joinedAt: new Date() });
    console.log(`✅ Doctor ${doctorId} joined`);
    socket.emit("doctor-online", { doctorId, status: "online", timestamp: new Date().toISOString() });
    io.emit("active-doctors", Array.from(activeDoctors.keys()));
  });

  // Get active doctors
  socket.on("get-active-doctors", () => {
    socket.emit("active-doctors", Array.from(activeDoctors.keys()));
  });

  // Call request
  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    if (!activeDoctors.has(doctorId)) {
      socket.emit("call-failed", { error: "Doctor not available", doctorId, patientId });
      return;
    }

    const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    const callSession = { callId, doctorId, patientId, channel, status: 'requested', createdAt: new Date(), patientSocketId: socket.id, doctorSocketId: null };
    activeCalls.set(callId, callSession);

    io.to(`doctor-${doctorId}`).emit("call-requested", { callId, doctorId, patientId, channel, timestamp: new Date().toISOString() });
    socket.emit("call-sent", { callId, doctorId, patientId, channel, status: "sent" });
  });

  // Call accepted
  socket.on("call-accepted", (data) => {
    const { callId, doctorId, patientId, channel } = data;
    const callSession = activeCalls.get(callId);
    if (!callSession) return socket.emit("error", { message: "Call session not found" });

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
  });

  // Call rejected
  socket.on("call-rejected", (data) => {
    const { callId, reason = "rejected" } = data;
    const callSession = activeCalls.get(callId);
    if (!callSession) return;

    callSession.status = 'rejected';
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-rejected", { callId, reason, message: reason === 'timeout' ? 'Doctor did not respond' : 'Doctor unavailable', timestamp: new Date().toISOString() });
    }

    setTimeout(() => activeCalls.delete(callId), 30000);
  });

  // Payment completed
  socket.on("payment-completed", (data) => {
    const { callId, patientId, doctorId, channel, paymentId } = data;
    const callSession = activeCalls.get(callId);
    if (!callSession) return socket.emit("error", { message: "Call session not found" });

    callSession.status = 'payment_completed';
    callSession.paymentId = paymentId;
    callSession.paidAt = new Date();
    activeCalls.set(callId, callSession);

    socket.emit("payment-verified", { callId, channel, patientId, doctorId, status: 'ready_to_connect', message: 'Payment successful!', videoUrl: `/call-page/${channel}?uid=${patientId}&role=audience&callId=${callId}` });

    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", { callId, channel, patientId, doctorId, paymentId, status: 'ready_to_connect', message: 'Patient payment confirmed!', videoUrl: `/call-page/${channel}?uid=${doctorId}&role=host&callId=${callId}` });
    }
  });

  // Call ended
  socket.on("call-ended", ({ callId, userId, role }) => {
    const callSession = activeCalls.get(callId);
    if (!callSession) return;

    callSession.status = 'ended';
    callSession.endedAt = new Date();
    callSession.endedBy = userId;

    const targetSocketId = role === 'host' ? callSession.patientSocketId : callSession.doctorSocketId;
    if (targetSocketId) {
      io.to(targetSocketId).emit("call-ended", { callId, endedBy: userId, message: 'Call ended' });
    }

    setTimeout(() => activeCalls.delete(callId), 10000);
  });

  // Doctor leaves
  socket.on("leave-doctor", (doctorId) => {
    socket.leave(`doctor-${doctorId}`);
    activeDoctors.delete(doctorId);
    socket.emit("doctor-offline", { doctorId, status: "offline", timestamp: new Date().toISOString() });
    io.emit("active-doctors", Array.from(activeDoctors.keys()));
  });

  // Disconnect handling
  socket.on("disconnect", (reason) => {
    console.log(`❌ Disconnected: ${socket.id}, reason: ${reason}`);

    // Clean up doctor
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        activeDoctors.delete(doctorId);
        io.emit("active-doctors", Array.from(activeDoctors.keys()));
      }
    }

    // Clean up active calls
    for (const [callId, callSession] of activeCalls.entries()) {
      if (callSession.patientSocketId === socket.id || callSession.doctorSocketId === socket.id) {
        callSession.status = 'disconnected';
        callSession.disconnectedAt = new Date();
        const otherSocketId = callSession.patientSocketId === socket.id ? callSession.doctorSocketId : callSession.patientSocketId;
        if (otherSocketId) {
          io.to(otherSocketId).emit("other-party-disconnected", { callId, message: 'The other party disconnected unexpectedly' });
        }
      }
    }
  });
});

// Periodic cleanup
setInterval(() => {
  const now = new Date();
  const threshold = 30 * 60 * 1000; // 30 min
  for (const [callId, callSession] of activeCalls.entries()) {
    const age = now - callSession.createdAt;
    if (age > threshold && !['payment_completed', 'ended'].includes(callSession.status)) {
      activeCalls.delete(callId);
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
  console.log(`📊 Connections: ${io.engine.clientsCount}, Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}`);
}, 30000);
