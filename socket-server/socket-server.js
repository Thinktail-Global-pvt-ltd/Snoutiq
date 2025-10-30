import { createServer } from "http";
import { Server } from "socket.io";

// -------------------- HTTP SERVER (health + debug APIs) --------------------
const httpServer = createServer((req, res) => {
  try {
    const url = new URL(req.url, `http://${req.headers.host}`);

    // CORS preflight
    if (req.method === "OPTIONS") {
      res.writeHead(204, {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type",
      });
      res.end();
      return;
    }

    // health check
    if (req.method === "GET" && url.pathname === "/health") {
      res.writeHead(200, {
        "Content-Type": "application/json",
        "Access-Control-Allow-Origin": "*",
      });
      res.end(JSON.stringify({ status: "ok" }));
      return;
    }

    // list active doctors (not busy)
    if (req.method === "GET" && url.pathname === "/active-doctors") {
      const available = [];
      for (const [doctorId] of activeDoctors.entries()) {
        if (!isDoctorBusy(doctorId)) {
          available.push(doctorId);
        }
      }

      res.writeHead(200, {
        "Content-Type": "application/json",
        "Access-Control-Allow-Origin": "*",
      });
      res.end(
        JSON.stringify({
          activeDoctors: available,
          updatedAt: new Date().toISOString(),
        })
      );
      return;
    }

    res.writeHead(404, {
      "Content-Type": "application/json",
      "Access-Control-Allow-Origin": "*",
    });
    res.end(JSON.stringify({ error: "Not Found" }));
  } catch (error) {
    console.error("HTTP server error:", error);
    res.writeHead(500, {
      "Content-Type": "application/json",
      "Access-Control-Allow-Origin": "*",
    });
    res.end(JSON.stringify({ error: "Server error" }));
  }
});

// -------------------- SOCKET.IO SETUP --------------------
const io = new Server(httpServer, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
    credentials: false,
  },
  path: "/socket.io/",
});

// -------------------- IN-MEMORY STATE --------------------
// activeDoctors: doctorId -> {
//   socketId, joinedAt, lastSeen, connectionStatus, location?, offlineTimer?, disconnectedAt?
// }
const activeDoctors = new Map();

// activeCalls: callId -> {
//   callId, doctorId, patientId, channel,
//   status,
//   createdAt, acceptedAt?, rejectedAt?, endedAt?, paidAt?,
//   patientSocketId, doctorSocketId,
//   paymentId?, disconnectedAt?, ...
// }
const activeCalls = new Map();

// -------------------- CONSTANTS --------------------
const DOCTOR_HEARTBEAT_EVENT = "doctor-heartbeat";
const DOCTOR_GRACE_EVENT = "doctor-grace";
const DOCTOR_HEARTBEAT_INTERVAL_MS = 30_000;
const DOCTOR_GRACE_PERIOD_MS = 5 * 60 * 1000; // 5 min grace background

// -------------------- HELPERS --------------------
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

  // if doctor already reconnected on a new socket, don't kill the new one
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

// NOTE: doctor "busy" means there's already a call in requested/accepted/payment_completed with him.
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

// broadcast list of available doctors (who are not on a live/locked call)
const emitAvailableDoctors = () => {
  const available = [];
  for (const [doctorId, info] of activeDoctors.entries()) {
    const status = info.connectionStatus || "connected";
    if (!["connected", "grace"].includes(status)) continue;
    if (!isDoctorBusy(doctorId)) {
      available.push(doctorId);
    }
  }
  console.log(`📤 Broadcasting ${available.length} available doctors:`, available);
  io.emit("active-doctors", available);
};

// When a doctor connects (join-doctor), replay any open calls for them so they get the popup.
const deliverPendingSessionsToDoctor = (doctorId, socket) => {
  const roomName = `doctor-${doctorId}`;

  for (const [callId, callSession] of activeCalls.entries()) {
    if (callSession.doctorId !== doctorId) continue;
    if (["ended", "rejected", "disconnected"].includes(callSession.status)) continue;

    // update this session with latest doctor socket
    callSession.doctorSocketId = socket.id;
    activeCalls.set(callId, callSession);

    // re-emit anything relevant so doctor dashboard rings
    if (callSession.status === "requested") {
      io.to(roomName).emit("call-requested", {
        callId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        channel: callSession.channel,
        timestamp: new Date().toISOString(),
        queued: true, // means this call was waiting while you were away
      });
    }

    if (callSession.status === "payment_completed") {
      io.to(roomName).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId: callSession.patientId,
        doctorId: callSession.doctorId,
        paymentId: callSession.paymentId,
        status: "ready_to_connect",
        message: "Patient payment confirmed!",
        videoUrl:
          `/call-page/${callSession.channel}` +
          `?uid=${callSession.doctorId}` +
          `&role=host` +
          `&callId=${callId}` +
          `&doctorId=${callSession.doctorId}` +
          `&patientId=${callSession.patientId}`,
        queued: true,
      });
    }
  }
};

// -------------------- CORE SOCKET LOGIC --------------------
io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // ========== DOCTOR JOINS THEIR "ROOM" ==========
  socket.on("join-doctor", (doctorIdRaw) => {
    const doctorId = Number(doctorIdRaw);
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);

    const existing = activeDoctors.get(doctorId);
    if (existing && existing.socketId !== socket.id) {
      console.log(
        `⚠️ Doctor ${doctorId} reconnecting (old socket: ${existing.socketId})`
      );
    }

    upsertDoctorEntry(doctorId, {
      socketId: socket.id,
      joinedAt: new Date(),
      lastSeen: new Date(),
      connectionStatus: "connected",
    });

    console.log(
      `✅ Doctor ${doctorId} joined (Total active: ${activeDoctors.size})`
    );

    socket.emit("doctor-online", {
      doctorId,
      status: "online",
      timestamp: new Date().toISOString(),
    });

    emitAvailableDoctors();

    // if there were queued calls for this doc, notify them now
    deliverPendingSessionsToDoctor(doctorId, socket);
  });

  // ========== HEARTBEAT FROM DOCTOR FRONTEND ==========
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
      // tell FE "you were in grace but now I see you again"
      socket.emit(DOCTOR_GRACE_EVENT, {
        doctorId,
        status: "connected",
        timestamp: new Date().toISOString(),
      });
    }
  });

  // ========== PATIENT ASKS: WHO'S ACTIVE? ==========
  socket.on("get-active-doctors", () => {
    const available = [];
    const busy = [];

    for (const [doctorId] of activeDoctors.entries()) {
      if (isDoctorBusy(doctorId)) busy.push(doctorId);
      else available.push(doctorId);
    }

    console.log(
      `📊 Active doctors request: ${available.length} available, ${busy.length} busy`
    );

    socket.emit("active-doctors", available);
  });

  // ========== PATIENT STARTS CALL ==========
  // IMPORTANT: We DO NOT block if doctor is offline/busy.
  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    console.log(`📞 Call request: Patient ${patientId} → Doctor ${doctorId}`);

    // make a unique callId
    const callId = `call_${Date.now()}_${Math.random()
      .toString(36)
      .substring(2, 8)}`;

    // see if doc is currently online
    const doctorEntry = activeDoctors.get(doctorId) || null;

    // create an in-memory call session
    const callSession = {
      callId,
      doctorId,
      patientId,
      channel,
      status: "requested",
      createdAt: new Date(),
      patientSocketId: socket.id,
      doctorSocketId: doctorEntry?.socketId || null,
    };
    activeCalls.set(callId, callSession);

    // ping doctor room. if doc offline, room may be empty; it's fine.
    io.to(`doctor-${doctorId}`).emit("call-requested", {
      callId,
      doctorId,
      patientId,
      channel,
      timestamp: new Date().toISOString(),
      queued: doctorEntry ? false : true, // true => doctor wasn't actively connected
    });

    // tell patient we registered the call no matter what
    socket.emit("call-sent", {
      callId,
      doctorId,
      patientId,
      channel,
      status: "sent",
      queued: doctorEntry ? false : true,
      message: doctorEntry
        ? "Ringing the doctor now…"
        : "Doctor is currently offline. We've queued your call and will alert them when they come online.",
    });

    emitAvailableDoctors();
  });

  // ========== DOCTOR ACCEPTS CALL ==========
  socket.on("call-accepted", (data) => {
    const { callId, doctorId, patientId, channel } = data;
    console.log(`✅ Call ${callId} accepted by doctor ${doctorId}`);

    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    callSession.status = "accepted";
    callSession.acceptedAt = new Date();
    callSession.doctorSocketId = socket.id;
    activeCalls.set(callId, callSession);

    // notify patient they must pay
    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-accepted", {
        callId,
        doctorId,
        patientId,
        channel,
        requiresPayment: true,
        message:
          "Doctor accepted your call. Please complete payment to proceed.",
        paymentAmount: 499,
        timestamp: new Date().toISOString(),
      });
    }

    emitAvailableDoctors();
  });

  // ========== DOCTOR REJECTS CALL ==========
  socket.on("call-rejected", (data) => {
    const { callId, reason = "rejected", doctorId, patientId } = data;
    console.log(`❌ Call ${callId} rejected by doctor ${doctorId}: ${reason}`);

    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found for rejection`);
      return;
    }

    callSession.status = "rejected";
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    // notify patient (best effort)
    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-rejected", {
        callId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        reason,
        message:
          reason === "timeout"
            ? "Doctor did not respond within 5 minutes"
            : "Doctor is currently unavailable",
        timestamp: new Date().toISOString(),
      });
      console.log(
        `📤 Notified patient via socket: ${callSession.patientSocketId}`
      );
    }

    // backup notify via rooms
    if (callSession.patientId) {
      io.to(`patient-${callSession.patientId}`).emit("call-rejected", {
        callId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        reason,
        message:
          reason === "timeout"
            ? "Doctor did not respond within 5 minutes"
            : "Doctor is currently unavailable",
        timestamp: new Date().toISOString(),
      });
    }

    // broadcast status update
    io.emit("call-status-update", {
      callId,
      status: "rejected",
      rejectedBy: "doctor",
      reason,
      timestamp: new Date().toISOString(),
    });

    // cleanup that call after 30s
    setTimeout(() => {
      activeCalls.delete(callId);
      emitAvailableDoctors();
      console.log(`🗑️ Cleaned up rejected call ${callId}`);
    }, 30_000);
  });

  // ========== PATIENT PAYMENT COMPLETED ==========
  socket.on("payment-completed", (data) => {
    const { callId, patientId, doctorId, channel, paymentId } = data;
    console.log(`💰 Payment completed for call ${callId}`);

    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    callSession.status = "payment_completed";
    callSession.paymentId = paymentId;
    callSession.paidAt = new Date();
    if (channel) callSession.channel = channel;
    activeCalls.set(callId, callSession);

    // tell patient: you're good, here's your join link (audience role)
    socket.emit("payment-verified", {
      callId,
      channel: callSession.channel,
      patientId,
      doctorId,
      status: "ready_to_connect",
      message: "Payment successful!",
      videoUrl:
        `/call-page/${callSession.channel}` +
        `?uid=${patientId}` +
        `&role=audience` +
        `&callId=${callId}`,
    });

    // tell doctor: patient has paid, join NOW (host role)
    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId,
        doctorId,
        paymentId,
        status: "ready_to_connect",
        message: "Patient payment confirmed!",
        videoUrl:
          `/call-page/${callSession.channel}` +
          `?uid=${doctorId}` +
          `&role=host` +
          `&callId=${callId}` +
          `&doctorId=${doctorId}` +
          `&patientId=${patientId}`,
      });
    } else {
      // doctor was offline → room emit (so when he rejoins we replay anyway)
      io.to(`doctor-${doctorId}`).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId,
        doctorId,
        paymentId,
        status: "ready_to_connect",
        message: "Patient payment confirmed!",
        videoUrl:
          `/call-page/${callSession.channel}` +
          `?uid=${doctorId}` +
          `&role=host` +
          `&callId=${callId}` +
          `&doctorId=${doctorId}` +
          `&patientId=${patientId}`,
        queued: true,
      });
    }

    emitAvailableDoctors();
  });

  // ========== CALL ENDED ==========
  socket.on(
    "call-ended",
    ({ callId, userId, role, doctorId, patientId, channel }) => {
      console.log(`🔚 Call ${callId} ended by ${userId} (${role})`);

      const callSession = activeCalls.get(callId);

      if (callSession) {
        callSession.status = "ended";
        callSession.endedAt = new Date();
        callSession.endedBy = userId;

        const isDoctorEnding = role === "host";
        const isPatientEnding = role === "audience";

        // notify the other party directly if we know their socket
        if (isDoctorEnding && callSession.patientSocketId) {
          io.to(callSession.patientSocketId).emit("call-ended-by-other", {
            callId,
            endedBy: "doctor",
            reason: "ended",
            message: "Doctor has ended the call",
            timestamp: new Date().toISOString(),
          });
          console.log(
            `📤 Notified patient via socket: ${callSession.patientSocketId}`
          );
        }

        if (isPatientEnding && callSession.doctorSocketId) {
          io.to(callSession.doctorSocketId).emit("call-ended-by-other", {
            callId,
            endedBy: "patient",
            reason: "ended",
            message: "Patient has ended the call",
            timestamp: new Date().toISOString(),
          });
          console.log(
            `📤 Notified doctor via socket: ${callSession.doctorSocketId}`
          );
        }

        // backup: notify via rooms
        if (callSession.doctorId) {
          io.to(`doctor-${callSession.doctorId}`).emit(
            "call-ended-by-other",
            {
              callId,
              endedBy: isDoctorEnding ? "doctor" : "patient",
              reason: "ended",
              message: isDoctorEnding
                ? "Doctor has ended the call"
                : "Patient has ended the call",
              timestamp: new Date().toISOString(),
            }
          );
        }

        if (callSession.patientId) {
          io.to(`patient-${callSession.patientId}`).emit(
            "call-ended-by-other",
            {
              callId,
              endedBy: isDoctorEnding ? "doctor" : "patient",
              reason: "ended",
              message: isDoctorEnding
                ? "Doctor has ended the call"
                : "Patient has ended the call",
              timestamp: new Date().toISOString(),
            }
          );
        }
      }

      // broadcast status update
      io.emit("call-status-update", {
        callId,
        status: "ended",
        endedBy: role,
        timestamp: new Date().toISOString(),
      });

      // cleanup call memory shortly after
      setTimeout(() => {
        activeCalls.delete(callId);
        emitAvailableDoctors();
        console.log(`🗑️ Cleaned up ended call ${callId}`);
      }, 10_000);
    }
  );

  // ========== DOCTOR MANUALLY LEAVES ==========
  socket.on("leave-doctor", (doctorIdRaw) => {
    const doctorId = Number(doctorIdRaw);
    console.log(`👋 Doctor ${doctorId} leaving`);

    socket.leave(`doctor-${doctorId}`);
    activeDoctors.delete(doctorId);

    socket.emit("doctor-offline", {
      doctorId,
      status: "offline",
      timestamp: new Date().toISOString(),
    });

    emitAvailableDoctors();
  });

  // ========== DOCTOR LOCATION UPDATE (OPTIONAL FEATURE) ==========
  socket.on("update-doctor-location", ({ doctorId, latitude, longitude }) => {
    console.log(
      `📍 Updating location for doctor ${doctorId}: ${latitude}, ${longitude}`
    );

    const doctor = activeDoctors.get(doctorId);
    if (doctor) {
      doctor.location = {
        latitude,
        longitude,
        updatedAt: new Date(),
      };
      activeDoctors.set(doctorId, doctor);

      socket.emit("location-updated", {
        doctorId,
        latitude,
        longitude,
        timestamp: new Date().toISOString(),
      });

      emitAvailableDoctors();
    }
  });

  // ========== SOCKET DISCONNECT ==========
  socket.on("disconnect", (reason) => {
    console.log(`❌ Disconnected: ${socket.id}, reason: ${reason}`);

    // mark any doctor with this socket as "grace"
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        console.log(
          `🔌 Doctor ${doctorId} socket disconnected, entering grace period`
        );
        scheduleDoctorRemoval(doctorId, socket.id);
        emitAvailableDoctors();
      }
    }

    // also handle calls where this socket was in the middle
    for (const [callId, callSession] of activeCalls.entries()) {
      if (
        callSession.patientSocketId === socket.id ||
        callSession.doctorSocketId === socket.id
      ) {
        console.log(`🔌 Handling disconnect for call ${callId}`);

        const isDoctor = callSession.doctorSocketId === socket.id;
        const isPatient = callSession.patientSocketId === socket.id;

        callSession.status = "disconnected";
        callSession.disconnectedAt = new Date();
        callSession.disconnectedBy = isDoctor ? "doctor" : "patient";

        const otherSocketId = isDoctor
          ? callSession.patientSocketId
          : callSession.doctorSocketId;

        // tell the other side
        if (otherSocketId) {
          io.to(otherSocketId).emit("other-party-disconnected", {
            callId,
            disconnectedBy: isDoctor ? "doctor" : "patient",
            message: `The ${isDoctor ? "doctor" : "patient"} disconnected unexpectedly`,
            timestamp: new Date().toISOString(),
          });

          console.log(
            `📤 Sent disconnect notification to ${
              isDoctor ? "patient" : "doctor"
            } (socket: ${otherSocketId})`
          );
        }

        // backup notify via rooms
        if (callSession.doctorId) {
          socket
            .to(`doctor-${callSession.doctorId}`)
            .emit("call-ended-by-other", {
              callId,
              endedBy: isDoctor ? "doctor" : "patient",
              reason: "disconnect",
              message: "Call ended due to connection loss",
              timestamp: new Date().toISOString(),
            });
        }

        if (callSession.patientId) {
          socket
            .to(`patient-${callSession.patientId}`)
            .emit("call-ended-by-other", {
              callId,
              endedBy: isDoctor ? "doctor" : "patient",
              reason: "disconnect",
              message: "Call ended due to connection loss",
              timestamp: new Date().toISOString(),
            });
        }

        // global status broadcast
        io.emit("call-status-update", {
          callId,
          status: "disconnected",
          disconnectedBy: isDoctor ? "doctor" : "patient",
          timestamp: new Date().toISOString(),
        });

        // cleanup after 5s
        setTimeout(() => {
          activeCalls.delete(callId);
          emitAvailableDoctors();
          console.log(`🗑️ Cleaned up disconnected call ${callId}`);
        }, 5_000);
      }
    }
  });
});

// -------------------- PERIODIC CLEANUPS --------------------

// kill stale doctors that never heartbeat back after grace x2
setInterval(() => {
  const now = Date.now();
  for (const [doctorId, info] of activeDoctors.entries()) {
    const lastSeen = info.lastSeen ? new Date(info.lastSeen).getTime() : 0;
    if (!lastSeen) continue;
    if (now - lastSeen > DOCTOR_GRACE_PERIOD_MS * 2) {
      console.log(
        `⏳ Removing stale doctor ${doctorId} after heartbeat timeout`
      );
      activeDoctors.delete(doctorId);
      emitAvailableDoctors();
    }
  }
}, DOCTOR_HEARTBEAT_INTERVAL_MS);

// auto time-out calls older than 5 minutes that haven't finished
setInterval(() => {
  const now = new Date();
  const threshold = 5 * 60 * 1000;
  for (const [callId, callSession] of activeCalls.entries()) {
    const age = now - callSession.createdAt;
    if (
      age > threshold &&
      !["payment_completed", "ended"].includes(callSession.status)
    ) {
      console.log(
        `⏰ Auto-ending call ${callId} after 5 minutes timeout (no payment / not ended)`
      );

      if (callSession.patientSocketId) {
        io.to(callSession.patientSocketId).emit("call-timeout", {
          callId,
          message: "Call timed out after 5 minutes",
        });
      }
      if (callSession.doctorSocketId) {
        io.to(callSession.doctorSocketId).emit("call-timeout", {
          callId,
          message: "Call timed out after 5 minutes",
        });
      }

      activeCalls.delete(callId);
      emitAvailableDoctors();
    }
  }
}, 5 * 60 * 1000);

// periodic log of server state
setInterval(() => {
  console.log(
    `📊 Stats: Connections: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}`
  );
  console.log(`👨‍⚕️ Active Doctor IDs:`, Array.from(activeDoctors.keys()));
}, 30_000);

// -------------------- START SERVER --------------------
const PORT = process.env.PORT || 4000;
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
});
