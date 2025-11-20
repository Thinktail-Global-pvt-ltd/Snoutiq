import { createServer } from "http";
import { Server } from "socket.io";
import { existsSync, readFileSync } from "fs";
import { dirname, join } from "path";
import admin from "firebase-admin";
import { fileURLToPath } from "url";
import {
  getWhatsAppConfig,
  isWhatsAppConfigured,
  sendWhatsAppTemplate,
  sendWhatsAppText,
} from "./whatsapp-client.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const DEFAULT_SERVICE_ACCOUNT_FILE = "snoutiqapp-9cacc4ece358.json";
const DEFAULT_RINGTONE =
  process.env.CALL_NOTIFICATION_SOUND || "incoming_call";

const resolveServiceAccountPath = () => {
  const candidates = [
    process.env.SERVICE_ACCOUNT_PATH,
    join(__dirname, DEFAULT_SERVICE_ACCOUNT_FILE),
    join(__dirname, "../../", DEFAULT_SERVICE_ACCOUNT_FILE),
    join(process.cwd(), DEFAULT_SERVICE_ACCOUNT_FILE),
  ];

  for (const candidate of candidates) {
    if (candidate && existsSync(candidate)) {
      return candidate;
    }
  }
  return candidates[0] || null;
};

// ==================== FIREBASE ADMIN SDK INITIALIZATION ====================
try {
  let serviceAccount;
  const resolvedPath = resolveServiceAccountPath();

  if (resolvedPath) {
    try {
      serviceAccount = JSON.parse(readFileSync(resolvedPath, "utf8"));
    } catch (error) {
      console.error("⚠️ Could not load service account key:", error.message);
      console.log(
        "💡 Please set SERVICE_ACCOUNT_PATH or place the service account JSON in the project root or src/socket-server directory.",
      );
      serviceAccount = null;
    }
  } else {
    console.log("⚠️ No service account path resolved.");
    console.log(
      "💡 Please set SERVICE_ACCOUNT_PATH or place the service account JSON in the project root or src/socket-server directory.",
    );
    serviceAccount = null;
  }

  if (serviceAccount) {
    admin.initializeApp({
      credential: admin.credential.cert(serviceAccount),
      databaseURL: "https://snoutiqapp-default-rtdb.firebaseio.com",
    });
    console.log("✅ Firebase Admin SDK initialized successfully");
  } else {
    console.log(
      "⚠️ Firebase Admin SDK not initialized - push notifications will fail"
    );
  }
} catch (error) {
  console.error("❌ Firebase Admin SDK initialization error:", error.message);
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
      res.end(
        JSON.stringify({
          status: "ok",
          timestamp: new Date().toISOString(),
          firebase: admin.apps.length > 0 ? "enabled" : "disabled",
        })
      );
      return;
    }

    // list active doctors (not busy)
    if (req.method === "GET" && url.pathname === "/active-doctors") {
      const available = [];
      for (const [doctorId, info] of activeDoctors.entries()) {
        const status = info.connectionStatus || "disconnected";
        if (["connected", "grace"].includes(status) && !isDoctorBusy(doctorId)) {
          available.push({
            doctorId,
            status: info.connectionStatus,
            lastSeen: info.lastSeen,
          });
        }
      }

      res.writeHead(200, {
        "Content-Type": "application/json",
        "Access-Control-Allow-Origin": "*",
      });
      res.end(
        JSON.stringify({
          activeDoctors: available,
          count: available.length,
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
  pingTimeout: 25000,
  pingInterval: 10000,
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

// FCM Push Token Storage
const doctorPushTokens = new Map();
const sentCallNotifications = new Set();

// -------------------- NOTIFICATION HELPERS --------------------
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

/**
 * Store/update doctor's push token
 */
const storeDoctorPushToken = async (doctorId, token) => {
  try {
    if (!doctorId || !token) {
      console.warn("Invalid doctorId or token provided");
      return false;
    }

    doctorPushTokens.set(doctorId, token);
    console.log(`💾 Push token stored in memory for doctor ${doctorId}`);

    // TODO: Persist to database
    return true;
  } catch (error) {
    console.error(`Error storing push token for doctor ${doctorId}:`, error);
    return false;
  }
};

/**
 * Get doctor's push token
 */
const getDoctorPushToken = async (doctorId) => {
  try {
    let token = doctorPushTokens.get(doctorId);

    if (!token) {
      console.log(`⚠️ No cached token for doctor ${doctorId}`);
      // TODO: Fetch from database as fallback
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

/**
 * Send Push Notification using Firebase Admin SDK
 */
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

    const ringtoneSound = payload.sound || DEFAULT_RINGTONE;

    const stringifiedData = Object.entries({
      type: "pending_call",
      callId: payload.callId,
      channel: payload.channel,
      roomId: payload.channel,
      room: payload.channel,
      patientId: payload.patientId,
      doctorId: payload.doctorId,
      deepLink: payload.deepLink,
      timestamp: payload.timestamp,
      message: payload.message,
      title: payload.title || payload.message,
      body: payload.body || payload.message,
      callerName: payload.callerName,
      timeoutMs: payload.timeoutMs,
      click_action: "OPEN_PENDING_CALL",
      ringtone: ringtoneSound,
    }).reduce((acc, [key, value]) => {
      if (value === undefined || value === null) return acc;
      acc[key] = String(value);
      return acc;
    }, {});

    const notificationTitle =
      payload.title ||
      payload.message ||
      `Incoming call from ${payload.callerName || "SnoutIQ"}`;
    const notificationBody =
      payload.body ||
      payload.message ||
      `Patient ${payload.patientId || ""} is waiting for you.`;

    // Build the message with ALL STRING VALUES (FCM requirement)
    const message = {
      token: doctorPushToken,
      data: stringifiedData,
      notification: {
        title: notificationTitle,
        body: notificationBody,
      },

      android: {
        priority: "high",
        ttl: 3600000,
        restrictedPackageName: process.env.ANDROID_APP_ID,
        notification: {
          title: notificationTitle,
          body: notificationBody,
          channelId: "calls",
          priority: "max",
          visibility: "public",
          sound: ringtoneSound,
        },
        fcmOptions: {
          analyticsLabel: "pending_call",
        },
      },

      apns: {
        headers: {
          "apns-priority": "10",
          "apns-push-type": "alert",
        },
        payload: {
          aps: {
            sound: {
              critical: 1,
              name: ringtoneSound.endsWith(".caf")
                ? ringtoneSound
                : `${ringtoneSound}.caf`,
              volume: 1.0,
            },
            badge: 1,
            alert: {
              title: notificationTitle,
              body: notificationBody,
            },
            "content-available": 1,
            "mutable-content": 1,
            category: "INCOMING_CALL",
          },
        },
        fcm_options: {
          analytics_label: "pending_call",
        },
      },

      webpush: {
        notification: {
          title: notificationTitle,
          body: notificationBody,
          icon: "/icon-192x192.png",
          badge: "/badge-72x72.png",
          requireInteraction: true,
          actions: [
            { action: "join", title: "Join Call" },
            { action: "dismiss", title: "Dismiss" },
          ],
        },
        data: stringifiedData,
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

    return response;
  } catch (error) {
    console.error("❌ Push notification error:", error);
    console.error("📍 Error code:", error.code);

    if (
      error.code === "messaging/invalid-registration-token" ||
      error.code === "messaging/registration-token-not-registered"
    ) {
      console.log(
        `⚠️ Invalid token for doctor ${doctorId}, removing from cache`
      );
      doctorPushTokens.delete(doctorId);
    }

    throw error;
  }
};

// -------------------- WHATSAPP CONFIGURATION --------------------
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
const DOCTOR_HEARTBEAT_INTERVAL_MS = 15_000;
const DOCTOR_GRACE_PERIOD_MS = 2 * 60 * 1000; // 2 min grace background
const PENDING_CALL_TIMEOUT_MS = 45_000;

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

  activeCalls.delete(callId);
  clearNotificationSent(callId, doctorId);

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

/**
 * Notify doctor about pending call via FCM, WhatsApp, and Webhook
 */
const notifyDoctorPendingCall = async (callSession) => {
  const { callId, doctorId, patientId, channel } = callSession;

  if (hasNotificationBeenSent(callId, doctorId)) {
    console.log(
      `[notify] Duplicate notification suppressed for call ${callId} (doctor ${doctorId})`
    );
    return;
  }

  console.log(
    `[notify] Notifying doctor ${doctorId} about pending call ${callId}`
  );

  const tasks = [];

  // FCM Push Notification
  if (admin.apps.length > 0) {
    const callerDisplayName =
      callSession.patientName ||
      callSession.patientFullName ||
      callSession.patient ||
      `Patient ${patientId}`;
    const defaultMessage = `Patient ${patientId} is waiting for you. Join the call now!`;
    const notificationPayload = {
      callId,
      doctorId,
      patientId,
      channel,
      timestamp: new Date().toISOString(),
      type: "pending_call",
      message: callSession.message || defaultMessage,
      title: callSession.title || `Incoming call from ${callerDisplayName}`,
      body: callSession.body || callSession.message || defaultMessage,
      callerName: callerDisplayName,
      timeoutMs: callSession.timeoutMs || 120000,
      deepLink: `snoutiq://call/${callId}?channel=${channel}&patientId=${patientId}`,
    };

    tasks.push(
      sendPushNotification(doctorId, notificationPayload).catch((error) => {
        console.error("Push notification error:", error.message);
        return null;
      })
    );
  }

  // Webhook
  if (DOCTOR_ALERT_ENDPOINT) {
    if (typeof fetch !== "function") {
      console.warn(
        "⚠️ Global fetch unavailable; cannot send doctor notification webhook"
      );
    } else {
      tasks.push(sendDoctorPendingCallWebhook(callSession));
    }
  }

  // WhatsApp
  if (isWhatsAppConfigured()) {
    tasks.push(sendDoctorPendingCallWhatsApp(callSession));
  }

  if (!tasks.length) {
    return;
  }

  markNotificationSent(callId, doctorId);

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

// broadcast list of available doctors (who are not on a live/locked call)
const emitAvailableDoctors = () => {
  const available = [];
  const allOnline = [];
  
  for (const [doctorId, info] of activeDoctors.entries()) {
    const status = info.connectionStatus || "connected";
    if (["connected", "grace"].includes(status)) {
      allOnline.push(doctorId);
      if (!isDoctorBusy(doctorId)) {
        available.push(doctorId);
      }
    }
  }
  console.log(`📤 Broadcasting ${available.length} available doctors (${allOnline.length} online total):`, available);
  io.emit("active-doctors", available);
  io.emit("live-doctors", allOnline);
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

  // ========== REGISTER PUSH TOKEN (ENHANCED) ==========
  socket.on("register-push-token", async ({ doctorId, pushToken }) => {
    doctorId = Number(doctorId);

    if (!doctorId || !pushToken) {
      console.log(
        `⚠️ Invalid push token registration: doctorId=${doctorId}, token=${pushToken ? "present" : "missing"}`
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

      // Optional: Send test notification
      if (process.env.SEND_TEST_NOTIFICATION === "true") {
        console.log(`🧪 Sending test notification to doctor ${doctorId}`);
        await sendPushNotification(doctorId, {
          callId: "test_" + Date.now(),
          doctorId: doctorId,
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

    // Also send push notification in case app is backgrounded
    notifyDoctorPendingCall(callSession).catch(() => {});

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
    clearNotificationSent(callId, doctorId);

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
    clearNotificationSent(callId, doctorId);

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
      role: "audience",
      uid: Number(patientId),
      timestamp: new Date().toISOString(),
    });

    const patientPaidData = {
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
      role: "host",
      uid: Number(doctorId),
      timestamp: new Date().toISOString(),
    };

    // tell doctor: patient has paid, join NOW (host role)
    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", patientPaidData);
    } else {
      // doctor was offline → room emit (so when he rejoins we replay anyway)
      io.to(`doctor-${doctorId}`).emit("patient-paid", {
        ...patientPaidData,
        queued: true,
      });
    }

    emitAvailableDoctors();
  });

  // ========== CALL STARTED ==========
  socket.on("call-started", ({ callId, userId, role }) => {
    console.log(`📹 User ${userId} (${role}) joined call ${callId}`);

    const call = activeCalls.get(callId);
    if (call) {
      call.status = "active";
      if (role === "host") call.doctorJoinedAt = new Date();
      if (role === "audience") call.patientJoinedAt = new Date();
      activeCalls.set(callId, call);
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
        clearNotificationSent(callId, callSession.doctorId);
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

        // Only notify if call was actually active
        const wasCallActive =
          callSession.status === "active" ||
          (callSession.doctorJoinedAt && callSession.patientJoinedAt);

        callSession.status = "disconnected";
        callSession.disconnectedAt = new Date();
        callSession.disconnectedBy = isDoctor ? "doctor" : "patient";

        if (wasCallActive) {
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
        }

        // cleanup after 30s
        setTimeout(() => {
          activeCalls.delete(callId);
          emitAvailableDoctors();
          console.log(`🗑️ Cleaned up disconnected call ${callId}`);
        }, 30_000);
      }
    }
  });
});

// -------------------- PERIODIC CLEANUPS --------------------

// kill stale doctors that never heartbeat back after grace x2
setInterval(() => {
  const now = Date.now();
  let removedCount = 0;
  
  for (const [doctorId, info] of activeDoctors.entries()) {
    const lastSeen = info.lastSeen ? new Date(info.lastSeen).getTime() : 0;
    if (!lastSeen) continue;
    if (now - lastSeen > DOCTOR_GRACE_PERIOD_MS * 2) {
      console.log(
        `⏳ Removing stale doctor ${doctorId} after heartbeat timeout (last seen: ${new Date(lastSeen).toISOString()})`
      );
      activeDoctors.delete(doctorId);
      removedCount++;
    }
  }
  
  if (removedCount > 0) {
    console.log(`🧹 Cleanup: Removed ${removedCount} stale doctor(s)`);
    emitAvailableDoctors();
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
      !["payment_completed", "ended", "active"].includes(callSession.status)
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
    `📊 Stats: Connections: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}, Pending Calls: ${pendingCalls.size}`
  );
  console.log(`👨‍⚕️ Active Doctor IDs:`, Array.from(activeDoctors.keys()));
}, 30_000);

// -------------------- START SERVER --------------------
const PORT = Number(process.env.PORT || process.env.SOCKET_PORT || 4000);
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
  console.log(`📍 Health check: http://localhost:${PORT}/health`);
  console.log(`📍 Active doctors: http://localhost:${PORT}/active-doctors`);
  console.log(
    `🔥 Firebase Admin initialized: ${admin.apps.length > 0 ? "YES" : "NO"}`
  );
  console.log(
    `📱 Push notifications: ${admin.apps.length > 0 ? "ENABLED" : "DISABLED"}`
  );
  console.log(`⏰ Doctor grace period: ${DOCTOR_GRACE_PERIOD_MS / 1000}s`);
  console.log(`⏱️ Pending call timeout: ${PENDING_CALL_TIMEOUT_MS / 1000}s`);
  console.log(`🌐 Environment: ${process.env.NODE_ENV || "development"}`);
  if (isWhatsAppConfigured() && WHATSAPP_ALERT_HAS_RECIPIENTS) {
    console.log(`📲 WhatsApp alerts: ENABLED (${WHATSAPP_ALERT_MODE} mode)`);
  }
});


