import { createServer } from "http";
import { Server } from "socket.io";

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

    // health probe
    if (req.method === "GET" && url.pathname === "/health") {
      res.writeHead(200, {
        "Content-Type": "application/json",
        "Access-Control-Allow-Origin": "*",
      });
      res.end(JSON.stringify({ status: "ok" }));
      return;
    }

    // public: list active (available) doctors
    if (req.method === "GET" && url.pathname === "/active-doctors") {
      const available = [];
      for (const [doctorId, info] of activeDoctors.entries()) {
        if (info?.manualOffline) continue;
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
        }),
      );
      return;
    }

    // default 404
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

// Socket.IO server
const io = new Server(httpServer, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
    credentials: false,
  },
  path: "/socket.io/",
});

// ===============================
// State
// ===============================

// doctorId -> {
//   socketId,
//   joinedAt,
//   lastSeen,
//   connectionStatus: 'connected' | 'grace',
//   location,
//   offlineTimer,
//   disconnectedAt
// }
const activeDoctors = new Map();

// callId -> {
//   callId,
//   doctorId,
//   patientId,
//   channel,
//   status: 'requested' | 'accepted' | 'payment_completed' | 'rejected' | 'ended' | 'disconnected',
//   createdAt,
//   acceptedAt,
//   paidAt,
//   endedAt,
//   rejectedAt,
//   disconnectedAt,
//   disconnectedBy,
//   patientSocketId,
//   doctorSocketId,
//   paymentId,
//   callIdentifier / etc...
// }
const activeCalls = new Map();

const DOCTOR_HEARTBEAT_EVENT = "doctor-heartbeat";
const DOCTOR_GRACE_EVENT = "doctor-grace";

const DOCTOR_HEARTBEAT_INTERVAL_MS = 30_000;
const DOCTOR_GRACE_PERIOD_MS = 5 * 60 * 1000; // 5 min "grace/hold" window

// ===============================
// Helpers
// ===============================

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

  if (values.manualOffline === undefined) {
    entry.manualOffline = existing.manualOffline || false;
  } else {
    entry.manualOffline = values.manualOffline;
  }

  if (entry.manualOffline) {
    entry.manualOfflineSince =
      values.manualOfflineSince || existing.manualOfflineSince || new Date();
  } else if (values.manualOffline === false) {
    entry.manualOfflineSince = null;
  }

  if (!entry.connectionStatus) {
    entry.connectionStatus = "connected";
  }

  activeDoctors.set(doctorId, entry);
  return entry;
};

const scheduleDoctorRemoval = (doctorId, socketId) => {
  const entry = activeDoctors.get(doctorId);
  if (!entry) return;

  // if doctor has reconnected with different socket, don't kill the new one
  if (socketId && entry.socketId && entry.socketId !== socketId) {
    return;
  }

  clearDoctorTimer(entry);

  entry.connectionStatus = "grace";
  entry.disconnectedAt = new Date();

  entry.offlineTimer = setTimeout(() => {
    const current = activeDoctors.get(doctorId);
    if (!current) return;

    // same safety: don't delete if doctor came back with diff socket
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

// broadcast list of FREE doctors (not in call)
const emitAvailableDoctors = () => {
  const available = [];
  for (const [doctorId, info] of activeDoctors.entries()) {
    const status = info.connectionStatus || "connected";
    if (info.manualOffline) continue;
    if (!["connected", "grace"].includes(status)) continue;
    if (!isDoctorBusy(doctorId)) {
      available.push(doctorId);
    }
  }
  console.log(
    `📤 Broadcasting ${available.length} available doctors:`,
    available,
  );
  io.emit("active-doctors", available);
};

// when doctor connects/reconnects, deliver all their pending calls
const deliverPendingSessionsToDoctor = (doctorId, socket) => {
  const roomName = `doctor-${doctorId}`;

  for (const [callId, callSession] of activeCalls.entries()) {
    if (callSession.doctorId !== doctorId) continue;
    if (["ended", "rejected", "disconnected"].includes(callSession.status))
      continue;

    callSession.doctorSocketId = socket.id;
    activeCalls.set(callId, callSession);

    // still ringing?
    if (callSession.status === "requested") {
      io.to(roomName).emit("call-requested", {
        callId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        channel: callSession.channel,
        timestamp: new Date().toISOString(),
        queued: true, // means this was pending
      });
    }

    // already paid?
    if (callSession.status === "payment_completed") {
      io.to(roomName).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId: callSession.patientId,
        doctorId: callSession.doctorId,
        paymentId: callSession.paymentId,
        status: "ready_to_connect",
        message: "Patient payment confirmed!",
        videoUrl: `/call-page/${callSession.channel}?uid=${callSession.doctorId}&role=host&callId=${callId}&doctorId=${callSession.doctorId}&patientId=${callSession.patientId}`,
        queued: true,
      });
    }
  }
};

// ===============================
// Socket.IO events
// ===============================

io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // ---------------------------------
  // doctor joins / becomes online
  // ---------------------------------
  socket.on("join-doctor", (doctorId) => {
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);

    const existing = activeDoctors.get(doctorId);
    if (existing && existing.socketId !== socket.id) {
      console.log(
        `⚠️ Doctor ${doctorId} reconnecting (old socket: ${existing.socketId})`,
      );
    }

    upsertDoctorEntry(doctorId, {
      socketId: socket.id,
      joinedAt: new Date(),
      lastSeen: new Date(),
      connectionStatus: "connected",
      manualOffline: false,
      manualOfflineSince: null,
    });

    console.log(
      `✅ Doctor ${doctorId} joined (Total active: ${activeDoctors.size})`,
    );

    // tell THIS doctor "you are online"
    socket.emit("doctor-online", {
      doctorId,
      status: "online",
      timestamp: new Date().toISOString(),
    });

    // push new available doctors list to everyone
    emitAvailableDoctors();

    // replay queued calls/payments to this doctor
    deliverPendingSessionsToDoctor(doctorId, socket);
  });

  // ---------------------------------
  // heartbeat so we keep doctor "green"
  // ---------------------------------
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
      // tell FE: you're fully reconnected
      socket.emit(DOCTOR_GRACE_EVENT, {
        doctorId,
        status: "connected",
        timestamp: new Date().toISOString(),
      });
    }
  });

  // ---------------------------------
  // active doctors list pull (on-demand)
  // ---------------------------------
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

    console.log(
      `📊 Active doctors request: ${available.length} available, ${busy.length} busy`,
    );

    socket.emit("active-doctors", available);
  });

  // ---------------------------------
  // PATIENT INITIATES CALL  ✅ UPDATED LOGIC
  // doctor can be online OR offline, we still create call + queue it
  // ---------------------------------
  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    console.log(`📞 Call request: Patient ${patientId} → Doctor ${doctorId}`);

    // OLD (removed):
    // if (isDoctorBusy(doctorId)) { ... return }

    // always create a call session
    const callId = `call_${Date.now()}_${Math.random()
      .toString(36)
      .substring(2, 8)}`;

    const doctorEntry = activeDoctors.get(doctorId);

    const callSession = {
      callId,
      doctorId,
      patientId,
      channel,
      status: "requested",
      createdAt: new Date(),
      patientSocketId: socket.id, // caller
      doctorSocketId: doctorEntry?.socketId || null, // null if doc offline
    };

    activeCalls.set(callId, callSession);

    // try to ring doctor right now (if connected / or even if grace)
    io.to(`doctor-${doctorId}`).emit("call-requested", {
      callId,
      doctorId,
      patientId,
      channel,
      timestamp: new Date().toISOString(),
      queued: !doctorEntry, // true means doctor was offline, so this is queued
    });

    // always tell patient "call sent"
    socket.emit("call-sent", {
      callId,
      doctorId,
      patientId,
      channel,
      status: "sent",
      queued: !doctorEntry ? true : false,
      message: doctorEntry
        ? "Ringing the doctor now…"
        : "Doctor is currently offline. We'll alert them as soon as they come online.",
    });

    emitAvailableDoctors();
  });

  // ---------------------------------
  // doctor accepts the call
  // ---------------------------------
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

  // ---------------------------------
  // doctor rejects / doesn't pick
  // ---------------------------------
  socket.on("call-rejected", (data) => {
    const { callId, reason = "rejected", doctorId, patientId } = data;
    console.log(
      `❌ Call ${callId} rejected by doctor ${doctorId}: ${reason}`,
    );

    const callSession = activeCalls.get(callId);
    if (!callSession) {
      console.log(`❌ Call session ${callId} not found for rejection`);
      return;
    }

    callSession.status = "rejected";
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    // Notify patient directly if we know who patient socket is
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
        `📤 Notified patient via socket: ${callSession.patientSocketId}`,
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

    // broadcast status
    io.emit("call-status-update", {
      callId,
      status: "rejected",
      rejectedBy: "doctor",
      reason,
      timestamp: new Date().toISOString(),
    });

    // cleanup the call after some delay
    setTimeout(() => {
      activeCalls.delete(callId);
      emitAvailableDoctors();
      console.log(`🗑️ Cleaned up rejected call ${callId}`);
    }, 30_000);
  });

  // ---------------------------------
  // payment done by patient
  // ---------------------------------
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

    // tell patient "ready to connect"
    socket.emit("payment-verified", {
      callId,
      channel: callSession.channel,
      patientId,
      doctorId,
      status: "ready_to_connect",
      message: "Payment successful!",
      videoUrl: `/call-page/${callSession.channel}?uid=${patientId}&role=audience&callId=${callId}`,
    });

    // tell doctor "patient paid, join now"
    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId,
        doctorId,
        paymentId,
        status: "ready_to_connect",
        message: "Patient payment confirmed!",
        videoUrl: `/call-page/${callSession.channel}?uid=${doctorId}&role=host&callId=${callId}&doctorId=${doctorId}&patientId=${patientId}`,
      });
    } else {
      // doctor offline / diff socket -> send to doctor room
      io.to(`doctor-${doctorId}`).emit("patient-paid", {
        callId,
        channel: callSession.channel,
        patientId,
        doctorId,
        paymentId,
        status: "ready_to_connect",
        message: "Patient payment confirmed!",
        videoUrl: `/call-page/${callSession.channel}?uid=${doctorId}&role=host&callId=${callId}&doctorId=${doctorId}&patientId=${patientId}`,
        queued: true,
      });
    }

    emitAvailableDoctors();
  });

  // ---------------------------------
  // call ended (doctor or patient pressed "end")
  // ---------------------------------
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

        // notify the other side via socketId
        if (isDoctorEnding && callSession.patientSocketId) {
          io.to(callSession.patientSocketId).emit("call-ended-by-other", {
            callId,
            endedBy: "doctor",
            reason: "ended",
            message: "Doctor has ended the call",
            timestamp: new Date().toISOString(),
          });
          console.log(
            `📤 Notified patient via socket: ${callSession.patientSocketId}`,
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
            `📤 Notified doctor via socket: ${callSession.doctorSocketId}`,
          );
        }

        // backup notify using rooms
        if (callSession.doctorId) {
          socket.to(`doctor-${callSession.doctorId}`).emit(
            "call-ended-by-other",
            {
              callId,
              endedBy: isDoctorEnding ? "doctor" : "patient",
              reason: "ended",
              message: isDoctorEnding
                ? "Doctor has ended the call"
                : "Patient has ended the call",
              timestamp: new Date().toISOString(),
            },
          );
        }

        if (callSession.patientId) {
          socket.to(`patient-${callSession.patientId}`).emit(
            "call-ended-by-other",
            {
              callId,
              endedBy: isDoctorEnding ? "doctor" : "patient",
              reason: "ended",
              message: isDoctorEnding
                ? "Doctor has ended the call"
                : "Patient has ended the call",
              timestamp: new Date().toISOString(),
            },
          );
        }
      }

      // broadcast status
      io.emit("call-status-update", {
        callId,
        status: "ended",
        endedBy: role,
        timestamp: new Date().toISOString(),
      });

      // cleanup after short delay
      setTimeout(() => {
        activeCalls.delete(callId);
        emitAvailableDoctors();
        console.log(`🗑️ Cleaned up ended call ${callId}`);
      }, 10_000);
    },
  );

  // ---------------------------------
  // doctor explicitly "goes offline"
  // ---------------------------------
  socket.on("leave-doctor", (doctorId) => {
    console.log(`👋 Doctor ${doctorId} leaving (manual offline)`);

    upsertDoctorEntry(doctorId, {
      manualOffline: true,
      manualOfflineSince: new Date(),
      connectionStatus: "manual_offline",
    });

    socket.emit("doctor-offline", {
      doctorId,
      status: "offline",
      timestamp: new Date().toISOString(),
    });

    emitAvailableDoctors();
  });

  // ---------------------------------
  // doctor updates location radius / GPS
  // ---------------------------------
  socket.on("update-doctor-location", ({ doctorId, latitude, longitude }) => {
    console.log(
      `📍 Updating location for doctor ${doctorId}: ${latitude}, ${longitude}`,
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

  // ---------------------------------
  // disconnect cleanup
  // ---------------------------------
  socket.on("disconnect", (reason) => {
    console.log(`❌ Disconnected: ${socket.id}, reason: ${reason}`);

    // mark doctor as "grace" instead of insta-offline
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        console.log(
          `🔌 Doctor ${doctorId} socket disconnected, entering grace period`,
        );
        scheduleDoctorRemoval(doctorId, socket.id);
        emitAvailableDoctors();
      }
    }

    // Mark any active calls as disconnected by this side and notify the other
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

        // notify the other party (direct socket)
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
            } (socket: ${otherSocketId})`,
          );
        }

        // backup notify via rooms
        if (callSession.doctorId) {
          socket.to(`doctor-${callSession.doctorId}`).emit(
            "call-ended-by-other",
            {
              callId,
              endedBy: isDoctor ? "doctor" : "patient",
              reason: "disconnect",
              message: "Call ended due to connection loss",
              timestamp: new Date().toISOString(),
            },
          );
        }

        if (callSession.patientId) {
          socket.to(`patient-${callSession.patientId}`).emit(
            "call-ended-by-other",
            {
              callId,
              endedBy: isDoctor ? "doctor" : "patient",
              reason: "disconnect",
              message: "Call ended due to connection loss",
              timestamp: new Date().toISOString(),
            },
          );
        }

        // global status broadcast
        io.emit("call-status-update", {
          callId,
          status: "disconnected",
          disconnectedBy: isDoctor ? "doctor" : "patient",
          timestamp: new Date().toISOString(),
        });

        // cleanup disconnected call after short delay
        setTimeout(() => {
          activeCalls.delete(callId);
          emitAvailableDoctors();
          console.log(`🗑️ Cleaned up disconnected call ${callId}`);
        }, 5_000);
      }
    }
  });
});

// ===============================
// Maintenance timers
// ===============================

// Kill doctors who fully vanished (no heartbeat for > grace*2)
setInterval(() => {
  const now = Date.now();
  for (const [doctorId, info] of activeDoctors.entries()) {
    const lastSeen = info.lastSeen ? new Date(info.lastSeen).getTime() : 0;
    if (!lastSeen) continue;
    if (now - lastSeen > DOCTOR_GRACE_PERIOD_MS * 2) {
      console.log(
        `⏳ Removing stale doctor ${doctorId} after heartbeat timeout`,
      );
      activeDoctors.delete(doctorId);
      emitAvailableDoctors();
    }
  }
}, DOCTOR_HEARTBEAT_INTERVAL_MS);

// Auto-timeout calls older than 5 min if not completed
setInterval(() => {
  const now = new Date();
  const threshold = 5 * 60 * 1000; // 5min

  for (const [callId, callSession] of activeCalls.entries()) {
    const age = now - callSession.createdAt;

    if (
      age > threshold &&
      !["payment_completed", "ended"].includes(callSession.status)
    ) {
      console.log(`⏰ Auto-ending call ${callId} after 5 minutes timeout`);

      // tell patient
      if (callSession.patientSocketId) {
        io.to(callSession.patientSocketId).emit("call-timeout", {
          callId,
          message: "Call timed out after 5 minutes",
        });
      }
      // tell doctor
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

// Stats log
setInterval(() => {
  console.log(
    `📊 Stats: Connections: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}`,
  );
  console.log(
    `👨‍⚕️ Active Doctor IDs:`,
    Array.from(activeDoctors.keys()),
  );
}, 30_000);

// ===============================
// Start server
// ===============================
const PORT = process.env.PORT || 4000;
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
});
