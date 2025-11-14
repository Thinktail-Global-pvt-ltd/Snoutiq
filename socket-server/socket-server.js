// socket-server-4000.mjs (or .js with "type": "module" in package.json)

import { createServer } from "http";
import { Server } from "socket.io";
import {
  getWhatsAppConfig,
  isWhatsAppConfigured,
  sendWhatsAppTemplate,
  sendWhatsAppText,
} from "./whatsapp-client.js";


import admin from "firebase-admin";
import { readFileSync } from "fs";
import { fileURLToPath } from "url";
import { dirname, join } from "path";

// -------------------- ESM __dirname --------------------
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// -------------------- FIREBASE ADMIN SDK INITIALIZATION --------------------
try {
  let serviceAccount;
  const serviceAccountPath =
    process.env.SERVICE_ACCOUNT_PATH ||
    join(__dirname, "./snoutiqapp-9cacc4ece358.json");

  try {
    const raw = readFileSync(serviceAccountPath, "utf8");
    serviceAccount = JSON.parse(raw);
  } catch (error) {
    console.error("⚠️ Could not load service account key:", error.message);
    console.log(
      "💡 Please set SERVICE_ACCOUNT_PATH environment variable or place serviceAccountKey.json in the correct location"
    );
    serviceAccount = null;
  }

  if (serviceAccount) {
    if (!admin.apps.length) {
      admin.initializeApp({
        credential: admin.credential.cert(serviceAccount),
        databaseURL: "https://snoutiqapp-default-rtdb.firebaseio.com",
      });
    }
    console.log("✅ Firebase Admin SDK initialized successfully");
  } else {
    console.log(
      "⚠️ Firebase Admin SDK not initialized - push notifications will fail"
    );
  }
} catch (error) {
  console.error(
    "❌ Firebase Admin SDK initialization error:",
    error.message || error
  );
}

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
          count: available.length, // <- safe extra field
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
const activeDoctors = new Map(); // doctorId -> { socketId, joinedAt, lastSeen, connectionStatus, ... }
const activeCalls = new Map(); // callId -> session
const pendingCalls = new Map(); // doctorId -> [ { callId, timer, ... } ]

// 🔹 FCM push token + notification dedupe (from 5000 server)
const doctorPushTokens = new Map(); // doctorId -> FCM token
const sentCallNotifications = new Set(); // "callId:doctorId"

// -------------------- CONFIG / CONSTANTS --------------------
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

// -------------------- NOTIFICATION DEDUPE (5000 style) --------------------
const getNotificationKey = (callId, doctorId) =>
  `${String(callId)}:${String(doctorId)}`;
const hasNotificationBeenSent = (callId, doctorId) =>
  sentCallNotifications.has(getNotificationKey(callId, doctorId));
const markNotificationSent = (callId, doctorId) => {
  sentCallNotifications.add(getNotificationKey(callId, doctorId));
};
const clearNotificationSent = (callId, doctorId) => {
  sentCallNotifications.delete(getNotificationKey(callId, doctorId));
};

// -------------------- FCM / PUSH HELPERS (from 5000 server) --------------------
const storeDoctorPushToken = async (doctorId, token) => {
  try {
    if (!doctorId || !token) {
      console.warn("Invalid doctorId or token provided");
      return false;
    }

    doctorPushTokens.set(doctorId, token);
    console.log(`💾 Push token stored in memory for doctor ${doctorId}`);

    // TODO: persist in DB if needed
    return true;
  } catch (error) {
    console.error(`Error storing push token for doctor ${doctorId}:`, error);
    return false;
  }
};

const getDoctorPushToken = async (doctorId) => {
  try {
    let token = doctorPushTokens.get(doctorId);

    if (!token) {
      console.log(`⚠️ No cached token for doctor ${doctorId}`);

      // TODO: fetch from DB if needed

      if (token) {
        doctorPushTokens.set(doctorId, token);
        console.log(`✅ Token retrieved from database for doctor ${doctorId}`);
      }
    }

    if (!token) {
      console.log(`❌ No FCM token found for doctor ${doctorId}`);
      return null;
    }

    console.log(
      `✅ Found push token for doctor ${doctorId}: ${token.substring(0, 20)}...`
    );
    return token;
  } catch (error) {
    console.error(`Error getting push token for doctor ${doctorId}:`, error);
    return null;
  }
};

const invalidateDoctorToken = async (doctorId) => {
  try {
    // TODO: mark invalid in DB if needed
    console.log(`📊 Token invalidated for doctor ${doctorId}`);
  } catch (error) {
    console.error("Error invalidating token:", error);
  }
};

const logNotificationSuccess = async (doctorId, callId, response) => {
  try {
    console.log(
      `📊 Notification success logged: doctor=${doctorId}, call=${callId}, messageId=${response}`
    );
  } catch (error) {
    console.error("Error logging notification success:", error);
  }
};

const logNotificationFailure = async (doctorId, callId, error) => {
  try {
    console.log(
      `📊 Notification failure logged: doctor=${doctorId}, call=${callId}, error=${error.code}`
    );
  } catch (err) {
    console.error("Error logging notification failure:", err);
  }
};

const sendPushNotification = async (doctorId, payload) => {
  try {
    const doctorPushToken = await getDoctorPushToken(doctorId);

    if (!doctorPushToken) {
      console.log(`⚠️ No push token found for doctor ${doctorId}`);
      return null;
    }

    if (!admin.apps.length) {
      console.log(
        "⚠️ Firebase Admin not initialized, cannot send push notification"
      );
      return null;
    }

    console.log(`📤 Sending FCM notification to doctor ${doctorId}`);
    console.log(`📦 Notification payload:`, JSON.stringify(payload, null, 2));

    const message = {
      notification: {
        title: "📞 Pending Video Call",
        body: payload.message,
      },
      data: {
        type: String(payload.type || "pending_call"),
        callId: String(payload.callId),
        channel: String(payload.channel),
        patientId: String(payload.patientId),
        doctorId: String(payload.doctorId),
        deepLink: String(payload.deepLink),
        timestamp: String(payload.timestamp),
        click_action: "OPEN_PENDING_CALL",
      },
      token: doctorPushToken,
      android: {
        priority: "high",
        ttl: 3600000,
        notification: {
          channelId: "calls",
          sound: "default",
          priority: "high",
          visibility: "public",
          defaultSound: true,
          defaultVibrateTimings: true,
          icon: "notification_icon",
          color: "#4F46E5",
          tag: `call_${payload.callId}`,
          sticky: true,
          clickAction: "OPEN_PENDING_CALL",
        },
      },
      apns: {
        headers: {
          "apns-priority": "10",
          "apns-push-type": "alert",
        },
        payload: {
          aps: {
            sound: "default",
            badge: 1,
            alert: {
              title: "📞 Pending Video Call",
              body: payload.message,
            },
            "content-available": 1,
            "mutable-content": 1,
          },
        },
      },
      webpush: {
        notification: {
          title: "📞 Pending Video Call",
          body: payload.message,
          icon: "/icon-192x192.png",
          badge: "/badge-72x72.png",
          requireInteraction: true,
          actions: [
            { action: "join", title: "Join Call" },
            { action: "dismiss", title: "Dismiss" },
          ],
        },
        fcmOptions: {
          link: payload.deepLink,
        },
      },
    };

    const response = await admin.messaging().send(message);
    console.log(
      `✅ FCM notification sent successfully to doctor ${doctorId}:`,
      response
    );

    await logNotificationSuccess(doctorId, payload.callId, response);
    return response;
  } catch (error) {
    console.error("❌ Push notification error:", error);
    console.error("📍 Error code:", error.code);
    console.error("📍 Error details:", error.message);

    if (
      error.code === "messaging/invalid-registration-token" ||
      error.code === "messaging/registration-token-not-registered"
    ) {
      console.log(
        `⚠️ Invalid token for doctor ${doctorId}, removing from cache`
      );
      doctorPushTokens.delete(doctorId);
      await invalidateDoctorToken(doctorId);
    }

    await logNotificationFailure(doctorId, payload.callId, error);
    throw error;
  }
};

// -------------------- OTHER CONSTANTS --------------------
const DOCTOR_HEARTBEAT_EVENT = "doctor-heartbeat";
const DOCTOR_GRACE_EVENT = "doctor-grace";
const DOCTOR_HEARTBEAT_INTERVAL_MS = 30_000;
const DOCTOR_GRACE_PERIOD_MS = 5 * 60 * 1000; // 5 min
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

// busy if doctor has call in requested/accepted/payment_completed/active
const isDoctorBusy = (doctorId) => {
  for (const [, call] of activeCalls.entries()) {
    if (
      call.doctorId === doctorId &&
      call.status &&
      ["requested", "accepted", "payment_completed", "active"].includes(
        call.status
      )
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

  clearNotificationSent(callId, doctorId);
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
  if (!DOCTOR_ALERT_ENDPOINT) return;

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
  if (!isWhatsAppConfigured()) return;

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

// 🔹 UNIFIED notify: FCM + Webhook + WhatsApp (5000 + 4000)
const notifyDoctorPendingCall = async (callSession) => {
  const { callId, doctorId, patientId, channel } = callSession || {};
  if (!doctorId || !callId) return;

  if (hasNotificationBeenSent(callId, doctorId)) {
    console.log(
      `[notify] Duplicate notification suppressed for call ${callId} (doctor ${doctorId})`
    );
    return;
  }

  markNotificationSent(callId, doctorId);

  const tasks = [];

  // FCM push
  const pushPayload = {
    callId,
    doctorId,
    patientId,
    channel,
    timestamp: new Date().toISOString(),
    type: "pending_call",
    message: `Patient ${patientId ?? ""} is waiting for you. Join the call now!`,
    deepLink: `snoutiq://call/${callId}?channel=${channel}&patientId=${patientId}`,
  };

  tasks.push(
    sendPushNotification(doctorId, pushPayload).catch((error) => {
      console.error("Push notification error:", error?.message || error);
      return null;
    })
  );

  // Webhook
  if (DOCTOR_ALERT_ENDPOINT) {
    if (typeof fetch !== "function") {
      console.warn(
        "⚠️ Global fetch unavailable; cannot send doctor notification webhook"
      );
    } else {
      tasks.push(
        sendDoctorPendingCallWebhook(callSession).catch((error) => {
          console.warn(
            "⚠️ Doctor notification webhook error",
            error?.message || error
          );
          return null;
        })
      );
    }
  }

  // WhatsApp
  if (isWhatsAppConfigured()) {
    tasks.push(
      sendDoctorPendingCallWhatsApp(callSession).catch((error) => {
        console.warn("⚠️ WhatsApp alert failed", error?.message || error);
        return null;
      })
    );
  }

  if (!tasks.length) return;

  const results = await Promise.allSettled(tasks);
  const anySuccess = results.some(
    (result) => result.status === "fulfilled" && result.value
  );

  if (!anySuccess) {
    clearNotificationSent(callId, doctorId);
  }

  console.log(
    `[notify] Notification attempts completed for doctor ${doctorId}`
  );
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

// broadcast list of available + all online (to match 5000)
const emitAvailableDoctors = () => {
  const available = [];
  const allOnline = [];

  for (const [doctorId, info] of activeDoctors.entries()) {
    const status = info.connectionStatus || "connected";
    if (!["connected", "grace"].includes(status)) continue;

    allOnline.push(doctorId);
    if (!isDoctorBusy(doctorId)) {
      available.push(doctorId);
    }
  }

  console.log(
    `📤 Broadcasting ${available.length} available doctors (${allOnline.length} online total):`,
    available
  );

  io.emit("active-doctors", available); // used by web + app
  io.emit("live-doctors", allOnline); // used by app (5000 flow)
};

// When a doctor connects, replay any open calls for them so they get the popup.
const deliverPendingSessionsToDoctor = (doctorId, socket) => {
  const roomName = `doctor-${doctorId}`;

  for (const [callId, callSession] of activeCalls.entries()) {
    if (callSession.doctorId !== doctorId) continue;
    if (["ended", "rejected", "disconnected"].includes(callSession.status))
      continue;

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
        role: "host",
        uid: Number(callSession.doctorId),
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

    deliverPendingSessionsToDoctor(doctorId, socket);
  });

  // ========== REGISTER PUSH TOKEN (5000 flow) ==========
  socket.on("register-push-token", async ({ doctorId, pushToken }) => {
    doctorId = Number(doctorId);

    if (!doctorId || !pushToken) {
      console.log(
        `⚠️ Invalid push token registration: doctorId=${doctorId}, token=${
          pushToken ? "present" : "missing"
        }`
      );
      socket.emit("push-token-registered", {
        success: false,
        doctorId,
        message: "Invalid doctorId or token",
      });
      return;
    }

    console.log(`📱 Registering push token for doctor ${doctorId}`);
    console.log(`🔑 Token: ${pushToken.substring(0, 30)}...`);

    const stored = await storeDoctorPushToken(doctorId, pushToken);

    if (stored) {
      console.log(
        `✅ Push token registered successfully for doctor ${doctorId}`
      );

      socket.emit("push-token-registered", {
        success: true,
        doctorId,
        message: "Push token registered successfully",
        timestamp: new Date().toISOString(),
      });

      if (process.env.SEND_TEST_NOTIFICATION === "true") {
        console.log(`🧪 Sending test notification to doctor ${doctorId}`);
        await sendPushNotification(doctorId, {
          callId: "test_" + Date.now(),
          doctorId,
          patientId: "test",
          channel: "test_channel",
          timestamp: new Date().toISOString(),
          type: "test_notification",
          message: "Test notification - your FCM token is working!",
          deepLink: `snoutiq://test`,
        }).catch((err) => console.error("Test notification failed:", err));
      }
    } else {
      console.error(`❌ Failed to register push token for doctor ${doctorId}`);

      socket.emit("push-token-registered", {
        success: false,
        doctorId,
        message: "Failed to store push token",
      });
    }
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
  // Supports optional callId like 5000 server; keeps 4000 queue logic
  socket.on("call-requested", ({ doctorId, patientId, channel, callId }) => {
    doctorId = Number(doctorId);
    console.log(`📞 Call request: Patient ${patientId} → Doctor ${doctorId}`);

    const finalCallId =
      callId ||
      `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;

    const doctorEntry = activeDoctors.get(doctorId) || null;
    const connectionStatus = doctorEntry?.connectionStatus || "disconnected";
    const doctorHasSocket = Boolean(doctorEntry?.socketId);
    const doctorConnected =
      doctorHasSocket && ["connected", "grace"].includes(connectionStatus);
    const doctorBusy = isDoctorBusy(doctorId);
    const shouldQueue = !doctorConnected || doctorBusy;

    const callSession = {
      callId: finalCallId,
      doctorId,
      patientId,
      channel,
      status: shouldQueue ? "queued" : "requested",
      createdAt: new Date(),
      queuedAt: shouldQueue ? new Date() : null,
      patientSocketId: socket.id,
      doctorSocketId: doctorEntry?.socketId || null,
    };

    activeCalls.set(finalCallId, callSession);

    if (shouldQueue) {
      enqueuePendingCall(callSession);

      socket.emit("call-status-update", {
        callId: finalCallId,
        doctorId,
        patientId,
        status: "pending",
        queued: true,
        timestamp: new Date().toISOString(),
      });

      socket.emit("call-sent", {
        callId: finalCallId,
        doctorId,
        patientId,
        channel,
        status: "pending", // 4000 web
        queued: true,
        // 5000-style message variants
        message: doctorBusy && doctorConnected
          ? "Doctor is finishing another call. We've queued yours and will alert them immediately."
          : "Doctor is currently offline. We've queued your call and will alert them as soon as they come online.",
      });

      emitAvailableDoctors();
      return;
    }

    io.to(`doctor-${doctorId}`).emit("call-requested", {
      callId: finalCallId,
      doctorId,
      patientId,
      channel,
      timestamp: new Date().toISOString(),
      queued: false,
    });

    socket.emit("call-sent", {
      callId: finalCallId,
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

    clearNotificationSent(callId, doctorId || callSession.doctorId);

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
        status: "accepted",
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

    clearNotificationSent(callId, doctorId || callSession.doctorId);

    callSession.status = "rejected";
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

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

    io.emit("call-status-update", {
      callId,
      status: "rejected",
      rejectedBy: "doctor",
      reason,
      timestamp: new Date().toISOString(),
    });

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

    // patient
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
      role: "audience", // 5000-style
      uid: Number(patientId),
      timestamp: new Date().toISOString(),
    });

    // doctor
    const doctorPayload = {
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
      queued: !callSession.doctorSocketId,
      role: "host",
      uid: Number(doctorId),
      timestamp: new Date().toISOString(),
    };

    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", doctorPayload);
    } else {
      io.to(`doctor-${doctorId}`).emit("patient-paid", doctorPayload);
    }

    emitAvailableDoctors();
  });

  // ========== CALL STARTED (5000 flow) ==========
  socket.on("call-started", ({ callId, userId, role }) => {
    console.log(`📹 User ${userId} (${role}) joined call ${callId}`);

    const callSession = activeCalls.get(callId);
    if (callSession) {
      callSession.status = "active";
      if (role === "host") callSession.doctorJoinedAt = new Date();
      if (role === "audience") callSession.patientJoinedAt = new Date();
      activeCalls.set(callId, callSession);
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
        clearNotificationSent(callId, doctorId || callSession.doctorId);

        callSession.status = "ended";
        callSession.endedAt = new Date();
        callSession.endedBy = userId;

        const isDoctorEnding = role === "host";
        const isPatientEnding = role === "audience";

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

      io.emit("call-status-update", {
        callId,
        status: "ended",
        endedBy: role,
        timestamp: new Date().toISOString(),
      });

      setTimeout(() => {
        const cs = activeCalls.get(callId);
        if (cs) clearNotificationSent(callId, cs.doctorId);
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

    // handle calls where this socket was in the middle
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

        io.emit("call-status-update", {
          callId,
          status: "disconnected",
          disconnectedBy: isDoctor ? "doctor" : "patient",
          timestamp: new Date().toISOString(),
        });

        setTimeout(() => {
          const cs = activeCalls.get(callId);
          if (cs) clearNotificationSent(callId, cs.doctorId);
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

      clearNotificationSent(callId, callSession.doctorId);

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
  console.log(
    `🔥 Firebase Admin initialized: ${admin.apps.length > 0 ? "YES" : "NO"}`
  );
});
