import { createServer } from "http";
import { Server } from "socket.io";
import {
  getWhatsAppConfig,
  isWhatsAppConfigured,
  sendWhatsAppTemplate,
  sendWhatsAppText,
} from "./whatsapp-client.js";

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
const pendingCalls = new Map();

const DOCTOR_ALERT_ENDPOINT =
  process.env.DOCTOR_ALERT_ENDPOINT ||
  process.env.DOCTOR_NOTIFICATION_URL ||
  null;
const DOCTOR_ALERT_SECRET =
  process.env.DOCTOR_ALERT_SECRET ||
  process.env.DOCTOR_NOTIFICATION_SECRET ||
  null;
const DOCTOR_ALERT_TIMEOUT_MS = Number(
  process.env.DOCTOR_ALERT_TIMEOUT_MS || 3000
);

const parseDoctorWhatsAppMap = (raw) => {
  const map = new Map();
  if (!raw) return map;

  const pairs = raw.split(/[,;]/);
  for (const entry of pairs) {
    if (!entry) continue;
    const [id, number] = entry.split(/[:=]/).map((part) => part?.trim());
    if (!id || !number) continue;
    map.set(String(id), number);
  }

  return map;
};

const cloneJson = (value) => {
  if (value == null) return value;
  return JSON.parse(JSON.stringify(value));
};

const interpolatePlaceholders = (value, variables) => {
  if (typeof value === "string") {
    return value.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (_, key) => {
      const resolved = variables[key];
      return resolved === undefined || resolved === null ? "" : String(resolved);
    });
  }

  if (Array.isArray(value)) {
    return value.map((item) => interpolatePlaceholders(item, variables));
  }

  if (value && typeof value === "object") {
    const result = {};
    for (const [key, val] of Object.entries(value)) {
      result[key] = interpolatePlaceholders(val, variables);
    }
    return result;
  }

  return value;
};

const parseTemplateComponents = (raw) => {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch (error) {
    console.warn("⚠️ Failed to parse WhatsApp template components JSON:", error);
    return null;
  }
};

const DOCTOR_WHATSAPP_DEFAULT =
  process.env.DOCTOR_ALERT_DEFAULT_WHATSAPP ||
  process.env.WHATSAPP_ALERT_DEFAULT_TO ||
  null;
const DOCTOR_WHATSAPP_MAP = parseDoctorWhatsAppMap(
  process.env.DOCTOR_WHATSAPP_MAP ||
    process.env.DOCTOR_ALERT_WHATSAPP_MAP ||
    ""
);
const WHATSAPP_ALERT_TEMPLATE_COMPONENTS_CONFIG = parseTemplateComponents(
  process.env.WHATSAPP_ALERT_TEMPLATE_COMPONENTS ||
    process.env.WHATSAPP_TEMPLATE_COMPONENTS ||
    ""
);
const WHATSAPP_BRAND_NAME =
  process.env.WHATSAPP_BRAND_NAME || process.env.APP_NAME || "SnoutIQ";
const DEFAULT_WHATSAPP_TEXT_TEMPLATE = [
  "{{brandName}} call alert!",
  "Patient {{patientId}} is requesting a {{channel}} consult.",
  "Call ID: {{callId}}",
  "Sent at {{timestamp}}",
].join("\n");
const WHATSAPP_ALERT_TEXT_TEMPLATE =
  process.env.WHATSAPP_ALERT_TEXT_TEMPLATE || DEFAULT_WHATSAPP_TEXT_TEMPLATE;
const resolveWhatsAppAlertMode = () => {
  const mode = (process.env.WHATSAPP_ALERT_MODE || "").trim().toLowerCase();
  if (mode === "text" || mode === "template") {
    return mode;
  }
  if (
    isWhatsAppConfigured() &&
    (process.env.WHATSAPP_ALERT_TEMPLATE_NAME ||
      process.env.WHATSAPP_TEMPLATE_NAME ||
      process.env.WHATSAPP_TEMPLATE)
  ) {
    return "template";
  }
  return "text";
};
const WHATSAPP_ALERT_MODE = resolveWhatsAppAlertMode();
const WHATSAPP_ALERT_TEMPLATE_NAME =
  process.env.WHATSAPP_ALERT_TEMPLATE_NAME ||
  process.env.WHATSAPP_TEMPLATE_NAME ||
  process.env.WHATSAPP_TEMPLATE ||
  "hello_world";

const WHATSAPP_ALERT_HAS_RECIPIENTS =
  DOCTOR_WHATSAPP_MAP.size > 0 || Boolean(DOCTOR_WHATSAPP_DEFAULT);

if (isWhatsAppConfigured()) {
  if (WHATSAPP_ALERT_HAS_RECIPIENTS) {
    const config = getWhatsAppConfig();
    console.log(
      `✅ WhatsApp alerts enabled (${WHATSAPP_ALERT_MODE}) via phone number ${
        config?.phoneNumberId || "[hidden]"
      }`
    );
  } else {
    console.log(
      "ℹ️ WhatsApp credentials detected but no recipients configured. Set DOCTOR_WHATSAPP_MAP or DOCTOR_ALERT_DEFAULT_WHATSAPP."
    );
  }
}

const getDoctorWhatsAppRecipient = (doctorId) => {
  if (!doctorId && DOCTOR_WHATSAPP_DEFAULT) {
    return DOCTOR_WHATSAPP_DEFAULT;
  }

  const key = String(doctorId ?? "");
  if (key && DOCTOR_WHATSAPP_MAP.has(key)) {
    return DOCTOR_WHATSAPP_MAP.get(key);
  }

  return DOCTOR_WHATSAPP_DEFAULT;
};

const buildWhatsAppTextMessage = (context) => {
  return interpolatePlaceholders(WHATSAPP_ALERT_TEXT_TEMPLATE, {
    ...context,
    brandName: WHATSAPP_BRAND_NAME,
  });
};

const buildTemplateComponents = (context) => {
  if (!WHATSAPP_ALERT_TEMPLATE_COMPONENTS_CONFIG) return undefined;
  const base = cloneJson(WHATSAPP_ALERT_TEMPLATE_COMPONENTS_CONFIG);
  return interpolatePlaceholders(base, {
    ...context,
    brandName: WHATSAPP_BRAND_NAME,
  });
};

// -------------------- CONSTANTS --------------------
const DOCTOR_HEARTBEAT_EVENT = "doctor-heartbeat";
const DOCTOR_GRACE_EVENT = "doctor-grace";
const DOCTOR_HEARTBEAT_INTERVAL_MS = 30_000;
const DOCTOR_GRACE_PERIOD_MS = 5 * 60 * 1000; // 5 min grace background
const PENDING_CALL_TIMEOUT_MS = 60_000;

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

const expirePendingCall = (doctorId, callId, reason = "timeout") => {
  const queue = pendingCalls.get(doctorId);
  if (queue) {
    const index = queue.findIndex((entry) => entry.callId === callId);
    if (index >= 0) {
      const [entry] = queue.splice(index, 1);
      if (entry?.timer) {
        clearTimeout(entry.timer);
      }
    }
    if (queue.length) {
      pendingCalls.set(doctorId, queue);
    } else {
      pendingCalls.delete(doctorId);
    }
  }

  const session = activeCalls.get(callId);
  if (!session) {
    return;
  }

  activeCalls.delete(callId);

  if (session.patientSocketId) {
    io.to(session.patientSocketId).emit("call-failed", {
      callId,
      doctorId,
      patientId: session.patientId,
      reason,
      message: "Doctor is unavailable right now. Please try another doctor.",
      timestamp: new Date().toISOString(),
    });
  }

  io.emit("call-status-update", {
    callId,
    doctorId,
    status: "expired",
    reason,
    timestamp: new Date().toISOString(),
  });

  emitAvailableDoctors();
  deliverNextPendingCall(doctorId);
};

const sendDoctorPendingCallWebhook = async (callSession) => {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), DOCTOR_ALERT_TIMEOUT_MS);

  try {
    const response = await fetch(DOCTOR_ALERT_ENDPOINT, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        ...(DOCTOR_ALERT_SECRET
          ? { "X-Notification-Key": DOCTOR_ALERT_SECRET }
          : {}),
      },
      body: JSON.stringify({
        doctor_id: callSession.doctorId,
        patient_id: callSession.patientId,
        call_id: callSession.callId,
        channel: callSession.channel,
      }),
      signal: controller.signal,
    });

    if (!response.ok) {
      const body = await response.text().catch(() => "");
      console.warn(
        "⚠️ Doctor notification webhook failed",
        response.status,
        body
      );
    } else {
      console.log(
        `✅ Doctor webhook alert sent for call ${callSession.callId} → doctor ${callSession.doctorId}`
      );
    }
  } catch (error) {
    if (error?.name === "AbortError") {
      console.warn("⚠️ Doctor notification webhook timed out");
    } else {
      console.warn(
        "⚠️ Doctor notification webhook error",
        error?.message || error
      );
    }
  } finally {
    clearTimeout(timer);
  }
};

const sendDoctorPendingCallWhatsApp = async (callSession) => {
  const recipient = getDoctorWhatsAppRecipient(callSession.doctorId);
  if (!recipient) {
    console.warn(
      "⚠️ No WhatsApp recipient configured for doctor",
      callSession.doctorId
    );
    return;
  }

  const context = {
    doctorId: callSession.doctorId,
    patientId: callSession.patientId ?? "a patient",
    callId: callSession.callId,
    channel: callSession.channel || "video consult",
    timestamp: new Date().toISOString(),
  };

  try {
    if (WHATSAPP_ALERT_MODE === "template") {
      const components = buildTemplateComponents(context);
      await sendWhatsAppTemplate(recipient, WHATSAPP_ALERT_TEMPLATE_NAME, {
        components,
      });
      console.log(
        `✅ WhatsApp template alert sent for call ${callSession.callId} → doctor ${callSession.doctorId}`
      );
    } else {
      const message = buildWhatsAppTextMessage(context);
      await sendWhatsAppText(recipient, message);
      console.log(
        `✅ WhatsApp text alert sent for call ${callSession.callId} → doctor ${callSession.doctorId}`
      );
    }
  } catch (error) {
    const errorDetails =
      error?.details ||
      error?.message ||
      (typeof error === "string" ? error : "unknown error");
    console.warn("⚠️ WhatsApp alert failed", {
      doctorId: callSession.doctorId,
      callId: callSession.callId,
      error: errorDetails,
    });
  }
};

const notifyDoctorPendingCall = async (callSession) => {
  const tasks = [];

  if (DOCTOR_ALERT_ENDPOINT) {
    if (typeof fetch !== "function") {
      console.warn(
        "⚠️ Global fetch unavailable; cannot send doctor notification webhook"
      );
    } else {
      tasks.push(sendDoctorPendingCallWebhook(callSession));
    }
  }

  if (isWhatsAppConfigured()) {
    tasks.push(sendDoctorPendingCallWhatsApp(callSession));
  }

  if (!tasks.length) {
    return;
  }

  await Promise.allSettled(tasks);
};

const enqueuePendingCall = (callSession) => {
  const doctorId = callSession.doctorId;
  if (!doctorId) return;

  const queue = pendingCalls.get(doctorId) || [];
  if (queue.some((entry) => entry.callId === callSession.callId)) {
    console.log(
      `ℹ️ Call ${callSession.callId} already queued for doctor ${doctorId}, skipping duplicate`
    );
    return;
  }

  const entry = {
    callId: callSession.callId,
    doctorId,
    patientSocketId: callSession.patientSocketId,
    queuedAt: Date.now(),
    timer: setTimeout(() => {
      console.log(
        `⌛ Pending call ${callSession.callId} for doctor ${doctorId} expired`
      );
      expirePendingCall(doctorId, callSession.callId, "timeout");
    }, PENDING_CALL_TIMEOUT_MS),
  };

  queue.push(entry);
  pendingCalls.set(doctorId, queue);

  console.log(
    `🕒 Queued call ${callSession.callId} for doctor ${doctorId}. Pending count: ${queue.length}`
  );

  notifyDoctorPendingCall(callSession).catch((error) => {
    console.warn(
      "⚠️ Failed to send doctor notification",
      error?.message || error
    );
  });
};

const deliverNextPendingCall = (doctorId) => {
  const queue = pendingCalls.get(doctorId);
  if (!queue || queue.length === 0) {
    return;
  }

  const doctorEntry = activeDoctors.get(doctorId);
  if (!doctorEntry || !doctorEntry.socketId) {
    return;
  }
  const status = doctorEntry.connectionStatus || "disconnected";
  if (!["connected", "grace"].includes(status)) {
    return;
  }
  if (isDoctorBusy(doctorId)) {
    console.log(
      `⛔ Doctor ${doctorId} still busy. Pending calls remain queued.`
    );
    return;
  }

  const entry = queue.shift();
  if (entry?.timer) {
    clearTimeout(entry.timer);
  }

  if (queue.length) {
    pendingCalls.set(doctorId, queue);
  } else {
    pendingCalls.delete(doctorId);
  }

  if (!entry) return;

  const session = activeCalls.get(entry.callId);
  if (!session) {
    console.log(
      `⚠️ Pending call ${entry.callId} missing from activeCalls. Skipping.`
    );
    deliverNextPendingCall(doctorId);
    return;
  }

  session.status = "requested";
  session.requestedAt = new Date();
  session.doctorSocketId = doctorEntry.socketId;
  activeCalls.set(session.callId, session);

  io.to(`doctor-${doctorId}`).emit("call-requested", {
    callId: session.callId,
    doctorId: session.doctorId,
    patientId: session.patientId,
    channel: session.channel,
    queued: true,
    timestamp: new Date().toISOString(),
  });

  if (session.patientSocketId) {
    io.to(session.patientSocketId).emit("call-queued", {
      callId: session.callId,
      doctorId: session.doctorId,
      status: "delivered",
      message: "Doctor is now online. Alerting them to join your call.",
      timestamp: new Date().toISOString(),
    });
  }

  console.log(
    `📨 Delivered pending call ${session.callId} to doctor ${doctorId}. Remaining queue length: ${queue.length}`
  );
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

  deliverNextPendingCall(doctorId);
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

    const doctorEntry = activeDoctors.get(doctorId) || null;
    const connectionStatus = doctorEntry?.connectionStatus || "disconnected";
    const doctorHasSocket = Boolean(doctorEntry?.socketId);
    const doctorConnected =
      doctorHasSocket && ["connected", "grace"].includes(connectionStatus);
    const doctorBusy = isDoctorBusy(doctorId);
    const shouldQueue = !doctorConnected || doctorBusy;

    const callSession = {
      callId,
      doctorId,
      patientId,
      channel,
      status: shouldQueue ? "queued" : "requested",
      createdAt: new Date(),
      queuedAt: shouldQueue ? new Date() : null,
      patientSocketId: socket.id,
      doctorSocketId: doctorEntry?.socketId || null,
    };

    activeCalls.set(callId, callSession);

    if (shouldQueue) {
      enqueuePendingCall(callSession);

      socket.emit("call-status-update", {
        callId,
        doctorId,
        patientId,
        status: "pending",
        queued: true,
        timestamp: new Date().toISOString(),
      });

      socket.emit("call-sent", {
        callId,
        doctorId,
        patientId,
        channel,
        status: "pending",
        queued: true,
        message:
          doctorBusy && doctorConnected
            ? "Doctor is finishing another call. We've queued yours and will alert them immediately."
            : "Doctor is currently offline. We've queued your call and will alert them as soon as they come online.",
      });

      emitAvailableDoctors();
      return;
    }

    io.to(`doctor-${doctorId}`).emit("call-requested", {
      callId,
      doctorId,
      patientId,
      channel,
      timestamp: new Date().toISOString(),
      queued: false,
    });

    socket.emit("call-sent", {
      callId,
      doctorId,
      patientId,
      channel,
      status: "sent",
      queued: false,
      message: "Ringing the doctor now…",
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
