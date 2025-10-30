// socket-server.js
import { createServer } from "http";
import { Server } from "socket.io";

const DOCTOR_HEARTBEAT_EVENT = "doctor-heartbeat";
const DOCTOR_HEARTBEAT_GRACE_MS = 5 * 60 * 1000;

const httpServer = createServer();
const io = new Server(httpServer, {
  cors: {
    origin: "*", // apne frontend domain set karo
    methods: ["GET", "POST"],
  },
});

const activeDoctors = new Map(); // doctorId -> { socketId, joinedAt, lastHeartbeatAt }

const purgeInactiveDoctors = (reason = "periodic") => {
  const now = Date.now();
  for (const [doctorId, info] of activeDoctors.entries()) {
    const lastHeartbeat = info.lastHeartbeatAt ? new Date(info.lastHeartbeatAt).getTime() : 0;
    if (lastHeartbeat && now - lastHeartbeat > DOCTOR_HEARTBEAT_GRACE_MS) {
      console.log(`⏱️ Removing doctor ${doctorId} after heartbeat timeout (${reason})`);
      activeDoctors.delete(doctorId);
    }
  }
};

io.on("connection", (socket) => {
  console.log("✅ Client connected:", socket.id);

  socket.on("join-doctor", (doctorId) => {
    console.log(`🏥 join-doctor event received for doctorId: ${doctorId}`);

    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);
    console.log(`👨‍⚕️ Socket ${socket.id} joined room: ${roomName}`);

    const now = new Date();
    activeDoctors.set(doctorId, {
      socketId: socket.id,
      joinedAt: now,
      lastHeartbeatAt: now,
    });

    socket.emit("doctor-online", {
      doctorId,
      status: "online",
      timestamp: new Date().toISOString(),
      socketId: socket.id,
    });
  });

  socket.on(DOCTOR_HEARTBEAT_EVENT, (payload = {}) => {
    const doctorId = Number(payload?.doctorId ?? payload?.id);
    if (!doctorId || Number.isNaN(doctorId)) {
      console.warn("⚠️ Heartbeat received without valid doctorId", payload);
      return;
    }

    const now = typeof payload.at === "number" ? new Date(payload.at) : new Date();
    const existing = activeDoctors.get(doctorId) || {
      socketId: socket.id,
      joinedAt: now,
    };

    existing.socketId = socket.id;
    existing.lastHeartbeatAt = now;
    activeDoctors.set(doctorId, existing);
  });

  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    purgeInactiveDoctors("call-requested");
    if (!activeDoctors.has(doctorId)) {
      console.log(`❌ Doctor ${doctorId} not available (no heartbeat)`);
      socket.emit("call-failed", { doctorId, patientId, error: "Doctor not available" });
      return;
    }

    io.to(`doctor-${doctorId}`).emit("call-requested", {
      doctorId,
      patientId,
      channel,
    });
    console.log("📞 Call requested:", doctorId, patientId);
  });

  socket.on("disconnect", () => {
    console.log("❌ Client disconnected:", socket.id);
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        info.socketId = null;
        info.lastHeartbeatAt = new Date();
        activeDoctors.set(doctorId, info);
      }
    }
  });
});

setInterval(() => purgeInactiveDoctors("interval"), 60 * 1000);

httpServer.listen(4000, () => {
  console.log("🚀 Socket.IO server running on http://localhost:4000");
});
