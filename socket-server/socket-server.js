// src/socket-server/index.js (example path)

import admin from "firebase-admin";
import { existsSync, readFileSync, readdirSync } from "fs";
import { createServer } from "http";
import { dirname, join } from "path";
import { Server } from "socket.io";
import { fileURLToPath } from "url";
import { ensureRedisConnected, redis as coreRedis } from "./redisClient.js";
import { heartbeatDoctor, setDoctorOnline } from "./doctorPresence.js";
import {
  getWhatsAppConfig,
  isWhatsAppConfigured,
  sendWhatsAppTemplate,
  sendWhatsAppText,
} from "./whatsapp-client.js";
import {
  publishRedis,
  subscribeRedis,
  isRedisEnabled,
} from "./redisClients.js";

// -------------------- PATH + CONSTANTS --------------------
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const DEFAULT_SERVICE_ACCOUNT_FILE = "snoutiqapp-9cacc4ece358.json";
const ACTIVE_DOCTOR_DEBOUNCE_MS = 5000;
const DISCONNECT_GRACE_MS = 30_000;
const PENDING_CALL_MAX_PER_DOCTOR = Number(
  process.env.PENDING_CALL_MAX_PER_DOCTOR || 3,
);
const REDIS_DOCTOR_PRESENCE_KEY = "ws:doctor-presence";
const REDIS_ACTIVE_CALLS_KEY = "ws:active-calls";
const REDIS_PENDING_QUEUE_KEY = "ws:pending-call-queues";
const REDIS_PRESENCE_TTL_SECONDS = Number(
  process.env.REDIS_PRESENCE_TTL_SECONDS || 70,
);
const REDIS_CALL_TTL_SECONDS = Number(
  process.env.REDIS_CALL_TTL_SECONDS || 24 * 60 * 60,
);
const REDIS_PENDING_TTL_SECONDS = Number(
  process.env.REDIS_PENDING_TTL_SECONDS || 600,
);
const REDIS_PUSH_TOKEN_KEY = "ws:doctor-push-tokens";
const REDIS_PUSH_TOKEN_TTL_SECONDS = Number(
  process.env.REDIS_PUSH_TOKEN_TTL_SECONDS || 30 * 24 * 60 * 60,
);
const REDIS_PRESCRIPTION_CHANNEL = "prescription-created";
const SERVER_INSTANCE_ID = `${process.pid}-${Date.now()}-${Math.random()
  .toString(36)
  .slice(2, 8)}`;

// Default ringtone for FCM notification (Android / iOS)
const DEFAULT_RINGTONE = (() => {
  if (typeof process.env.CALL_NOTIFICATION_SOUND === "string") {
    const trimmed = process.env.CALL_NOTIFICATION_SOUND.trim();
    if (trimmed.length) return trimmed;
  }
  return "default";
})();

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

const loadServiceAccountFromCommonFiles = () => {
  try {
    const root = process.cwd();
    const entries = readdirSync(root, { withFileTypes: true });
    const jsonFiles = entries
      .filter((entry) => entry.isFile() && entry.name.endsWith(".json"))
      .map((entry) => entry.name);

    const preferredNames = [
      "serviceAccountKey.json",
      "credentials.json",
      "firebase-adminsdk.json",
      "api-0000000000000000000-111111-aaaaaabbbbbb.json",
    ];

    const ordered = [
      ...preferredNames.filter((name) => jsonFiles.includes(name)),
      ...jsonFiles.filter((name) => !preferredNames.includes(name)),
    ];

    for (const fileName of ordered) {
      const fullPath = join(root, fileName);
      try {
        const parsed = JSON.parse(readFileSync(fullPath, "utf8"));
        if (parsed?.type === "service_account") {
          console.log(`✅ Loaded Firebase service account from ${fileName}`);
          return parsed;
        }
      } catch {
        /* ignore bad files */
      }
    }
  } catch {
    /* ignore scan errors */
  }
  return null;
};

const loadServiceAccountFromEnv = () => {
  const inlineJson =
    process.env.FIREBASE_SERVICE_ACCOUNT_JSON ||
    process.env.SERVICE_ACCOUNT_JSON ||
    null;
  if (inlineJson) {
    try {
      return JSON.parse(inlineJson);
    } catch (error) {
      console.error(
        "⚠️ Failed to parse FIREBASE_SERVICE_ACCOUNT_JSON:",
        error?.message || error,
      );
    }
  }

  const base64Json = process.env.FIREBASE_SERVICE_ACCOUNT_BASE64 || null;
  if (base64Json) {
    try {
      const decoded = Buffer.from(base64Json, "base64").toString("utf8");
      return JSON.parse(decoded);
    } catch (error) {
      console.error(
        "⚠️ Failed to decode FIREBASE_SERVICE_ACCOUNT_BASE64:",
        error?.message || error,
      );
    }
  }

  const gcloudPath = process.env.GOOGLE_APPLICATION_CREDENTIALS || null;
  if (gcloudPath && existsSync(gcloudPath)) {
    try {
      return JSON.parse(readFileSync(gcloudPath, "utf8"));
    } catch (error) {
      console.error(
        "⚠️ Could not load GOOGLE_APPLICATION_CREDENTIALS file:",
        error?.message || error,
      );
    }
  }

  return null;
};

// ==================== FIREBASE ADMIN SDK INITIALIZATION ====================
try {
  let serviceAccount =
    loadServiceAccountFromEnv() || loadServiceAccountFromCommonFiles();
  const resolvedPath = resolveServiceAccountPath();

  if (!serviceAccount && resolvedPath) {
    try {
      serviceAccount = JSON.parse(readFileSync(resolvedPath, "utf8"));
      console.log(`✅ Loaded Firebase service account from ${resolvedPath}`);
    } catch (error) {
      console.error("⚠️ Could not load service account key:", error.message);
      console.log(
        "💡 Please set SERVICE_ACCOUNT_PATH or place the service account JSON in the project root or src/socket-server directory."
      );
      serviceAccount = null;
    }
  } else if (!serviceAccount) {
    console.log("⚠️ No service account path resolved.");
    console.log(
      "💡 Please set SERVICE_ACCOUNT_PATH or place the service account JSON in the project root or src/socket-server directory."
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

const isFirebaseReady = () => Boolean(admin?.apps?.length);
if (!isFirebaseReady()) {
  console.warn("[push] Firebase Admin SDK not initialized - push notifications will be skipped");
} else {
  console.log("[push] Firebase Admin SDK ready - push notifications enabled");
}

// -------------------- REDIS STATE HELPERS --------------------
const isRedisStateReady = () => Boolean(coreRedis) && coreRedis.status === "ready";

const setHashWithTtl = async (key, field, value, ttlSeconds) => {
  if (!isRedisStateReady()) return;
  try {
    await coreRedis.hset(key, String(field), JSON.stringify(value));
    if (ttlSeconds) {
      await coreRedis.expire(key, ttlSeconds);
    }
  } catch (error) {
    console.warn(
      `[redis:state] hset ${key}:${field} failed`,
      error?.message || error,
    );
  }
};

const removeHashField = async (key, field) => {
  if (!isRedisStateReady()) return;
  try {
    await coreRedis.hdel(key, String(field));
  } catch (error) {
    console.warn(
      `[redis:state] hdel ${key}:${field} failed`,
      error?.message || error,
    );
  }
};

// -------------------- IN-MEMORY STATE --------------------
// activeDoctors: doctorId -> { socketId, joinedAt, lastSeen, connectionStatus, ... }
const activeDoctors = new Map();
// activeCalls: callId -> call session metadata
const activeCalls = new Map();
// pendingCalls: doctorId -> queue of call requests waiting for doctor
const pendingCalls = new Map();
// cached push tokens per doctor
const doctorPushTokens = new Map();
// grace timers per doctor
const doctorDisconnectTimers = new Map();

// Debounce/emit trackers
let lastActiveDoctorsEmit = 0;
let activeDoctorsEmitTimer = null;
let pendingActiveDoctorsEmit = false;
let lastActiveDoctorSignature = { available: "", all: "" };
let redisPrescriptionUnsub = null;

// simple dedupe set: `${callId}:${doctorId}`
const sentCallNotifications = new Set();

// -------------------- LOGGING / NOTIFICATION HELPERS --------------------
const logFlow = (stage, payload = {}) => {
  const {
    callId,
    doctorId,
    patientId,
    channel,
    role,
    userId,
    reason,
    paymentId,
    doctorSocketId,
    patientSocketId,
    socketId,
    status,
    extra,
  } = payload;

  const parts = [`[FLOW] ${stage}`];
  if (callId !== undefined) parts.push(`callId=${callId}`);
  if (doctorId !== undefined) parts.push(`doctorId=${doctorId}`);
  if (patientId !== undefined) parts.push(`patientId=${patientId}`);
  if (channel !== undefined) parts.push(`channel=${channel}`);
  if (role !== undefined) parts.push(`role=${role}`);
  if (userId !== undefined) parts.push(`userId=${userId}`);
  if (paymentId !== undefined) parts.push(`paymentId=${paymentId}`);
  if (doctorSocketId !== undefined) parts.push(`doctorSocketId=${doctorSocketId}`);
  if (patientSocketId !== undefined) parts.push(`patientSocketId=${patientSocketId}`);
  if (socketId !== undefined) parts.push(`socketId=${socketId}`);
  if (status !== undefined) parts.push(`status=${status}`);
  if (reason !== undefined) parts.push(`reason=${reason}`);
  if (extra) parts.push(`extra=${extra}`);

  console.log(parts.join(" | "));
};

const normalizeCallId = (rawId) => {
  if (!rawId) return "";
  const str = String(rawId).trim();
  if (!str) return "";

  const cleaned = str.replace(/^call_+/i, "call_").replace(/_{2,}/g, "_");
  const parts = cleaned.split("_").filter(Boolean);
  if (!parts.length) return "";
  const body = parts[0].toLowerCase() === "call" ? parts.slice(1) : parts;
  return body.length ? `call_${body.join("_")}` : "call_";
};

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

// -------------------- REDIS PERSIST HELPERS --------------------
const persistDoctorPushToken = async (doctorId, token) => {
  if (!doctorId || !token || !isRedisStateReady()) return;
  try {
    await setHashWithTtl(
      REDIS_PUSH_TOKEN_KEY,
      doctorId,
      {
        token,
        updatedAt: new Date().toISOString(),
      },
      REDIS_PUSH_TOKEN_TTL_SECONDS,
    );
  } catch (error) {
    console.error(`Error storing push token for doctor ${doctorId}:`, error);
  }
};

const fetchDoctorPushTokenFromRedis = async (doctorId) => {
  if (!doctorId || !isRedisStateReady()) return null;
  try {
    const raw = await coreRedis.hget(REDIS_PUSH_TOKEN_KEY, String(doctorId));
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed?.token || null;
  } catch (error) {
    console.warn(
      `[redis:state] failed to fetch push token for doctor ${doctorId}`,
      error?.message || error,
    );
    return null;
  }
};

const persistDoctorPresence = async (doctorId) => {
  if (!doctorId) return;
  const entry = activeDoctors.get(doctorId);
  if (!entry) {
    await removeHashField(REDIS_DOCTOR_PRESENCE_KEY, doctorId);
    return;
  }

  const payload = {
    doctorId,
    socketId: entry.socketId || null,
    lastSeen: entry.lastSeen ? new Date(entry.lastSeen).toISOString() : null,
    joinedAt: entry.joinedAt ? new Date(entry.joinedAt).toISOString() : null,
    connectionStatus: entry.connectionStatus || "disconnected",
    disconnectedAt: entry.disconnectedAt
      ? new Date(entry.disconnectedAt).toISOString()
      : null,
    timestamp: new Date().toISOString(),
  };

  await setHashWithTtl(
    REDIS_DOCTOR_PRESENCE_KEY,
    doctorId,
    payload,
    REDIS_PRESENCE_TTL_SECONDS,
  );
};

const removeDoctorPresence = async (doctorId) => {
  if (!doctorId) return;
  await removeHashField(REDIS_DOCTOR_PRESENCE_KEY, doctorId);
};

const persistActiveCallState = async (callSession) => {
  if (!callSession?.callId) return;
  const payload = {
    ...callSession,
    callId: callSession.callId,
    createdAt: callSession.createdAt
      ? new Date(callSession.createdAt).toISOString()
      : undefined,
    acceptedAt: callSession.acceptedAt
      ? new Date(callSession.acceptedAt).toISOString()
      : undefined,
    rejectedAt: callSession.rejectedAt
      ? new Date(callSession.rejectedAt).toISOString()
      : undefined,
    endedAt: callSession.endedAt
      ? new Date(callSession.endedAt).toISOString()
      : undefined,
    paidAt: callSession.paidAt
      ? new Date(callSession.paidAt).toISOString()
      : undefined,
    disconnectedAt: callSession.disconnectedAt
      ? new Date(callSession.disconnectedAt).toISOString()
      : undefined,
  };

  await setHashWithTtl(
    REDIS_ACTIVE_CALLS_KEY,
    callSession.callId,
    payload,
    REDIS_CALL_TTL_SECONDS,
  );
};

const removeActiveCallState = async (callId) => {
  if (!callId) return;
  await removeHashField(REDIS_ACTIVE_CALLS_KEY, callId);
};

const persistPendingQueueState = async (doctorId) => {
  if (!doctorId) return;
  const queue = pendingCalls.get(doctorId) || [];

  if (!queue.length) {
    await removeHashField(REDIS_PENDING_QUEUE_KEY, doctorId);
    return;
  }

  const serialized = queue.map((entry) => ({
    callId: entry.callId,
    doctorId: entry.doctorId,
    patientSocketId: entry.patientSocketId || null,
    queuedAt: entry.queuedAt || Date.now(),
  }));

  await setHashWithTtl(
    REDIS_PENDING_QUEUE_KEY,
    doctorId,
    serialized,
    REDIS_PENDING_TTL_SECONDS,
  );
};

const storeDoctorPushToken = async (doctorId, token) => {
  try {
    if (!doctorId || !token) {
      console.warn("Invalid doctorId or token provided");
      return false;
    }

    const normalizedId = Number(doctorId);
    const cached = doctorPushTokens.get(normalizedId);
    if (cached && cached === token) {
      console.log(
        `ℹ️ Push token for doctor ${normalizedId} unchanged, skipping re-store`,
      );
      return true;
    }

    doctorPushTokens.set(normalizedId, token);
    await persistDoctorPushToken(normalizedId, token);
    console.log(`✅ Push token cached for doctor ${normalizedId}`);

    return true;
  } catch (error) {
    console.error(`Error storing push token for doctor ${doctorId}:`, error);
    return false;
  }
};

const getDoctorPushToken = async (doctorId) => {
  try {
    const normalizedId = Number(doctorId);
    let token = doctorPushTokens.get(normalizedId);

    if (!token) {
      token = await fetchDoctorPushTokenFromRedis(normalizedId);
      if (token) {
        doctorPushTokens.set(normalizedId, token);
      }
    }

    if (!token) {
      console.log(`⚠️ No push token found for doctor ${normalizedId}`);
      return null;
    }

    console.log(
      `✅ Found push token for doctor ${normalizedId}: ${token.substring(0, 20)}...`
    );
    return token;
  } catch (error) {
    console.error(`Error getting push token for doctor ${doctorId}:`, error);
    return null;
  }
};

// -------------------- FCM NOTIFICATION SENDER --------------------
const sendPushNotification = async (doctorId, payload) => {
  try {
    const doctorPushToken = await getDoctorPushToken(doctorId);

    if (!doctorPushToken) {
      console.log(`⚠️ No push token found for doctor ${doctorId}`);
      return null;
    }

    if (!isFirebaseReady()) {
      console.warn("[push] Firebase Admin not initialized, cannot send push notification");
      return null;
    }

    console.log(`📨 Sending FCM notification to doctor ${doctorId}`);

    const ringtoneSound = payload.sound || DEFAULT_RINGTONE;
    const dataOnly = payload?.dataOnly !== false;

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

    const message = {
      token: doctorPushToken,
      data: stringifiedData,
      notification: dataOnly
        ? undefined
        : {
            title: notificationTitle,
            body: notificationBody,
          },

      android: {
        priority: "high",
        ttl: 3600000,
        restrictedPackageName: process.env.ANDROID_APP_ID,
        notification: dataOnly
          ? undefined
          : {
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
          "apns-priority": dataOnly ? "5" : "10",
          "apns-push-type": dataOnly ? "background" : "alert",
        },
        payload: {
          aps: {
            ...(dataOnly
              ? {
                  "content-available": 1,
                }
              : {
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
                }),
          },
        },
        fcm_options: {
          analytics_label: "pending_call",
        },
      },

      webpush: {
        notification: dataOnly
          ? undefined
          : {
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
      await deleteDoctorPushToken(doctorId);
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
const DOCTOR_HEARTBEAT_INTERVAL_MS = 15_000;

// Short stale timeout safety fallback (prevents ghost doctors)
const DOCTOR_STALE_TIMEOUT_MS = 70 * 1000;

const PENDING_CALL_TIMEOUT_MS = 45_000;
const ACTIVE_DOCTOR_LOG_INTERVAL_MS = 5_000;
const ACTIVE_DOCTOR_REQUEST_LOG_INTERVAL_MS = 5_000;

let lastAvailableDoctorLog = {
  timestamp: 0,
  available: 0,
  online: 0,
};
let lastActiveDoctorRequestLog = {
  timestamp: 0,
  available: 0,
  busy: 0,
};

// -------------------- HELPERS --------------------
const setActiveCallSession = (callId, callSession) => {
  const normalizedCallId = normalizeCallId(callId);
  if (!normalizedCallId || !callSession) return null;
  const session = { ...callSession, callId: normalizedCallId };
  activeCalls.set(normalizedCallId, session);
  persistActiveCallState(session).catch(() => {});
  return session;
};

const deleteActiveCallSession = (callId) => {
  const normalizedCallId = normalizeCallId(callId);
  if (!normalizedCallId) return false;
  const removed = activeCalls.delete(normalizedCallId);
  removeActiveCallState(normalizedCallId).catch(() => {});
  return removed;
};

// Reconnect grace: mark reconnecting immediately, drop after grace if no return.
const markDoctorDisconnected = (doctorId, socketId) => {
  const entry = activeDoctors.get(doctorId);
  if (!entry) return;

  // If doctor already reconnected on a new socket, don't touch new session
  if (socketId && entry.socketId && entry.socketId !== socketId) {
    console.log(`Doctor ${doctorId} already reconnected, ignoring old socket ${socketId}`);
    return;
  }

  entry.connectionStatus = "reconnecting";
  entry.disconnectedAt = new Date();
  entry.socketId = null; // clear active socket but KEEP doctor entry during grace

  activeDoctors.set(doctorId, entry);
  persistDoctorPresence(doctorId).catch(() => {});
  console.log(`Doctor ${doctorId} marked as reconnecting (grace window)`);
};

const scheduleDoctorDisconnect = (doctorId, socketId) => {
  if (!doctorId) return;
  const existingTimer = doctorDisconnectTimers.get(doctorId);
  if (existingTimer) clearTimeout(existingTimer);

  // Immediately mark reconnecting and emit
  markDoctorDisconnected(doctorId, socketId);
  emitAvailableDoctors();

  const timer = setTimeout(() => {
    doctorDisconnectTimers.delete(doctorId);
    const current = activeDoctors.get(doctorId);
    if (current && current.connectionStatus === "reconnecting") {
      activeDoctors.delete(doctorId);
      removeDoctorPresence(doctorId).catch(() => {});
      console.log(
        `Removing stale doctor ${doctorId} after reconnect grace elapsed`,
      );
    }
    emitAvailableDoctors();
  }, DISCONNECT_GRACE_MS);

  doctorDisconnectTimers.set(doctorId, timer);
};

const cancelDoctorDisconnect = (doctorId) => {
  const timer = doctorDisconnectTimers.get(doctorId);
  if (timer) {
    clearTimeout(timer);
    doctorDisconnectTimers.delete(doctorId);
  }
};

// Upsert helper for activeDoctors map
const upsertDoctorEntry = (doctorId, fields = {}) => {
  if (!doctorId) return null;
  const current = activeDoctors.get(doctorId) || {};
  const updated = {
    ...current,
    ...fields,
  };
  if (!updated.joinedAt) updated.joinedAt = new Date();
  if (!updated.lastSeen) updated.lastSeen = new Date();
  activeDoctors.set(doctorId, updated);
  persistDoctorPresence(doctorId).catch(() => {});
  return updated;
};

// NOTE: doctor "busy" means there's already a call in requested/accepted/payment_completed/active with him.
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

const removePendingCallEntry = (doctorId, callId) => {
  const queue = pendingCalls.get(doctorId);
  if (!queue) return false;

  const normalizedCallId = normalizeCallId(callId);
  const index = queue.findIndex((entry) => entry.callId === normalizedCallId);
  if (index < 0) return false;

  const [entry] = queue.splice(index, 1);
  if (entry?.timer) clearTimeout(entry.timer);

  if (queue.length) {
    pendingCalls.set(doctorId, queue);
  } else {
    pendingCalls.delete(doctorId);
  }
  persistPendingQueueState(doctorId).catch(() => {});

  return true;
};

const expirePendingCall = (doctorId, callId, reason = "timeout") => {
  const normalizedCallId = normalizeCallId(callId);
  removePendingCallEntry(doctorId, normalizedCallId);

  const session = activeCalls.get(normalizedCallId);
  if (!session) return;

  deleteActiveCallSession(normalizedCallId);
  clearNotificationSent(normalizedCallId, doctorId);

  if (session.patientSocketId) {
    io.to(session.patientSocketId).emit("call-failed", {
      callId: normalizedCallId,
      doctorId,
      patientId: session.patientId,
      reason,
      message: "Doctor is unavailable right now. Please try another doctor.",
      timestamp: new Date().toISOString(),
    });
  }

  io.emit("call-status-update", {
    callId: normalizedCallId,
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

const persistCallRequested = async (callSession) => {
  try {
    const createdAt =
      callSession.createdAt instanceof Date
        ? callSession.createdAt
        : new Date(callSession.createdAt || Date.now());
    await coreRedis
      .multi()
      .hset(`call:${callSession.callId}`, {
        doctorId: String(callSession.doctorId ?? ""),
        patientId: String(callSession.patientId ?? ""),
        channel: callSession.channel || "",
        status: callSession.status || "",
        createdAt: createdAt.toISOString(),
        requestedAt: new Date().toISOString(),
        serverId: SERVER_INSTANCE_ID,
      })
      .expire(`call:${callSession.callId}`, 600)
      .exec();
  } catch (error) {
    console.warn(
      "[Redis] call-requested persist failed:",
      error?.message || error,
    );
  }
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
  persistPendingQueueState(doctorId).catch(() => {});

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
  if (!queue || queue.length === 0) return;

  const doctorEntry = activeDoctors.get(doctorId);
  if (!doctorEntry || !doctorEntry.socketId) return;

  const status = doctorEntry.connectionStatus || "disconnected";
  if (status !== "connected") return;

  if (isDoctorBusy(doctorId)) {
    console.log(
      `⛔ Doctor ${doctorId} still busy. Pending calls remain queued.`
    );
    return;
  }

  const entry = queue.shift();
  if (entry?.timer) clearTimeout(entry.timer);

  if (queue.length) {
    pendingCalls.set(doctorId, queue);
  } else {
    pendingCalls.delete(doctorId);
  }
  persistPendingQueueState(doctorId).catch(() => {});

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
  setActiveCallSession(session.callId, session);

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

const buildActiveDoctorSnapshot = () => {
  const available = [];
  const allOnline = [];

  for (const [doctorId, info] of activeDoctors.entries()) {
    const status = info.connectionStatus || "connected";
    const isConnected = status === "connected";
    const isReconnecting = status === "reconnecting";
    if (isConnected || isReconnecting) {
      allOnline.push(doctorId);
      if (isConnected && !isDoctorBusy(doctorId)) {
        available.push(doctorId);
      }
    }
  }

  return {
    available,
    allOnline,
    availableKey: available.slice().sort((a, b) => a - b).join(","),
    onlineKey: allOnline.slice().sort((a, b) => a - b).join(","),
  };
};

// Broadcast list of available doctors (not busy) + "live" doctors (connected/reconnecting)
const emitAvailableDoctorsInternal = (snapshot = buildActiveDoctorSnapshot()) => {
  const { available, allOnline, availableKey, onlineKey } = snapshot;
  const hasChanged =
    availableKey !== lastActiveDoctorSignature.available ||
    onlineKey !== lastActiveDoctorSignature.all;

  const now = Date.now();
  const shouldLog =
    now - lastAvailableDoctorLog.timestamp > ACTIVE_DOCTOR_LOG_INTERVAL_MS ||
    available.length !== lastAvailableDoctorLog.available ||
    allOnline.length !== lastAvailableDoctorLog.online;

  if (shouldLog) {
    console.log(
      `Broadcasting ${available.length} available doctors (${allOnline.length} online total)`
    );
    lastAvailableDoctorLog = {
      timestamp: now,
      available: available.length,
      online: allOnline.length,
    };
  }

  if (!hasChanged) {
    return;
  }

  lastActiveDoctorSignature = { available: availableKey, all: onlineKey };
  io.emit("active-doctors", available);
  io.emit("live-doctors", allOnline);
};

const emitAvailableDoctors = () => {
  const snapshot = buildActiveDoctorSnapshot();
  const hasChanged =
    snapshot.availableKey !== lastActiveDoctorSignature.available ||
    snapshot.onlineKey !== lastActiveDoctorSignature.all;

  const now = Date.now();
  const elapsed = now - lastActiveDoctorsEmit;
  if (!hasChanged && !pendingActiveDoctorsEmit) {
    return;
  }

  const triggerEmit = () => {
    pendingActiveDoctorsEmit = false;
    lastActiveDoctorsEmit = Date.now();
    emitAvailableDoctorsInternal();
  };

  if (elapsed >= ACTIVE_DOCTOR_DEBOUNCE_MS && !pendingActiveDoctorsEmit) {
    triggerEmit();
    return;
  }

  pendingActiveDoctorsEmit = true;
  const delay = Math.max(ACTIVE_DOCTOR_DEBOUNCE_MS - elapsed, 0);
  if (activeDoctorsEmitTimer) clearTimeout(activeDoctorsEmitTimer);
  activeDoctorsEmitTimer = setTimeout(triggerEmit, delay);
};


const broadcastPrescriptionToUser = (data = {}, { publish = false } = {}) => {
  const rawUserId =
    data.userId ?? data.user_id ?? data.patientId ?? data.patient_id;
  const userId = Number(rawUserId);
  const doctorId = Number(data.doctorId ?? data.doctor_id);
  const prescription = data.prescription || data;
  if (!Number.isFinite(userId)) {
    console.warn(
      "prescription-created missing userId. Payload:",
      JSON.stringify(data),
    );
    return;
  }

  const payload = {
    prescription,
    doctorId: Number.isFinite(doctorId) ? doctorId : undefined,
    timestamp: data.timestamp || new Date().toISOString(),
    userId,
  };

  io.to(`user_${userId}`).emit("prescription-created", payload);

  if (publish && isRedisEnabled()) {
    publishRedis(REDIS_PRESCRIPTION_CHANNEL, {
      ...payload,
      sourceId: SERVER_INSTANCE_ID,
    }).catch(() => {});
  }
};

// When a doctor connects (join-doctor), replay any open calls for them so they get the popup.
const deliverPendingSessionsToDoctor = (doctorId, socket) => {
  const roomName = `doctor-${doctorId}`;

  for (const [callId, callSession] of activeCalls.entries()) {
    if (callSession.doctorId !== doctorId) continue;
    if (["ended", "rejected", "disconnected"].includes(callSession.status))
      continue;

    // update this session with latest doctor socket
    callSession.doctorSocketId = socket.id;
    setActiveCallSession(callId, callSession);

    const { patientId, channel } = callSession;

    logFlow("call-accepted", {
      callId,
      doctorId,
      patientId,
      channel,
      doctorSocketId: socket.id,
      status: callSession?.status,
    });

    if (callSession.status === "requested") {
      io.to(roomName).emit("call-requested", {
        callId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        channel: callSession.channel,
        timestamp: new Date().toISOString(),
        queued: true, // was waiting while you were away
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

const rehydrateStateFromRedis = async () => {
  if (!isRedisStateReady()) return;
  try {
    const [doctorRaw, callRaw, queueRaw] = await Promise.all([
      coreRedis.hgetall(REDIS_DOCTOR_PRESENCE_KEY),
      coreRedis.hgetall(REDIS_ACTIVE_CALLS_KEY),
      coreRedis.hgetall(REDIS_PENDING_QUEUE_KEY),
    ]);

    const now = Date.now();
    if (doctorRaw && Object.keys(doctorRaw).length) {
      for (const [doctorIdKey, raw] of Object.entries(doctorRaw)) {
        try {
          const parsed = JSON.parse(raw || "{}");
          const doctorId = Number(doctorIdKey);
          const lastSeen = parsed.lastSeen ? new Date(parsed.lastSeen) : null;
          if (
            lastSeen &&
            now - lastSeen.getTime() > DOCTOR_STALE_TIMEOUT_MS
          ) {
            continue;
          }
          activeDoctors.set(doctorId, {
            ...parsed,
            doctorId,
            connectionStatus: parsed.connectionStatus || "reconnecting",
            lastSeen: lastSeen || new Date(),
          });
          persistDoctorPresence(doctorId).catch(() => {});
        } catch (error) {
          console.warn(
            "[redis:state] failed to parse doctor presence",
            error?.message || error,
          );
        }
      }
    }

    if (callRaw && Object.keys(callRaw).length) {
      for (const [callIdKey, raw] of Object.entries(callRaw)) {
        try {
          const parsed = JSON.parse(raw || "{}");
          const callId = parsed.callId || callIdKey;
          const session = {
            ...parsed,
            callId,
            doctorId: Number(parsed.doctorId ?? parsed.doctor_id ?? 0) || null,
            patientId:
              Number(parsed.patientId ?? parsed.patient_id ?? 0) || null,
            createdAt: parsed.createdAt ? new Date(parsed.createdAt) : new Date(),
            acceptedAt: parsed.acceptedAt
              ? new Date(parsed.acceptedAt)
              : undefined,
            rejectedAt: parsed.rejectedAt
              ? new Date(parsed.rejectedAt)
              : undefined,
            endedAt: parsed.endedAt ? new Date(parsed.endedAt) : undefined,
            paidAt: parsed.paidAt ? new Date(parsed.paidAt) : undefined,
            disconnectedAt: parsed.disconnectedAt
              ? new Date(parsed.disconnectedAt)
              : undefined,
          };
          setActiveCallSession(callId, session);
        } catch (error) {
          console.warn(
            "[redis:state] failed to parse active call",
            error?.message || error,
          );
        }
      }
    }

    if (queueRaw && Object.keys(queueRaw).length) {
      for (const [doctorIdKey, raw] of Object.entries(queueRaw)) {
        try {
          const parsed = JSON.parse(raw || "[]");
          const doctorId = Number(doctorIdKey);
          const rebuiltQueue = [];
          for (const entry of parsed) {
            const queuedAt = Number(entry.queuedAt) || Date.now();
            const remaining =
              PENDING_CALL_TIMEOUT_MS - Math.max(0, now - queuedAt);
            if (remaining <= 0) {
              setTimeout(
                () => expirePendingCall(doctorId, entry.callId, "timeout"),
                0,
              );
              continue;
            }
            const timer = setTimeout(
              () => expirePendingCall(doctorId, entry.callId, "timeout"),
              remaining,
            );
            rebuiltQueue.push({
              callId: entry.callId,
              doctorId,
              patientSocketId: entry.patientSocketId || null,
              queuedAt,
              timer,
            });
          }
          if (rebuiltQueue.length) {
            pendingCalls.set(doctorId, rebuiltQueue);
            persistPendingQueueState(doctorId).catch(() => {});
          }
        } catch (error) {
          console.warn(
            "[redis:state] failed to parse pending queue",
            error?.message || error,
          );
        }
      }
    }
  } catch (error) {
    console.warn("[redis:state] rehydrate failed", error?.message || error);
  }
};

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

    // list active doctors (not busy) – counts both connected + disconnected (always-online mode)
    if (req.method === "GET" && url.pathname === "/active-doctors") {
      const available = [];
      for (const [doctorId, info] of activeDoctors.entries()) {
        const status = info.connectionStatus || "disconnected";
        if (
          ["connected", "disconnected"].includes(status) &&
          !isDoctorBusy(doctorId)
        ) {
          available.push({
            doctorId,
            status,
            lastSeen: info.lastSeen,
            socketId: info.socketId || null,
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

await rehydrateStateFromRedis();
emitAvailableDoctors();

if (isRedisEnabled() && isRedisStateReady()) {
  try {
    redisPrescriptionUnsub = subscribeRedis(
      REDIS_PRESCRIPTION_CHANNEL,
      (message) => {
        try {
          const parsed =
            typeof message === "string" ? JSON.parse(message || "{}") : message;
          if (parsed?.sourceId && parsed.sourceId === SERVER_INSTANCE_ID) {
            return;
          }
          broadcastPrescriptionToUser(parsed || {}, { publish: false });
        } catch (error) {
          console.warn(
            "[redis] prescription channel handler failed",
            error?.message || error,
          );
        }
      },
    );
  } catch (error) {
    console.warn("[redis] subscription failed", error?.message || error);
  }
}

// -------------------- CORE SOCKET LOGIC --------------------
io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // ========== PATIENT JOINS THEIR "USER" ROOM (for prescriptions/alerts) ==========
  socket.on("join-user", (userIdRaw) => {
    const userId = Number(userIdRaw);
    if (!Number.isFinite(userId)) {
      console.warn("Invalid userId for join-user:", userIdRaw);
      return;
    }
    const roomName = `user_${userId}`;
    socket.join(roomName);
    console.log(`User ${userId} joined room ${roomName} for notifications`);
  });

  // ========== PRESCRIPTION CREATED (doctor -> patient) ==========
  socket.on("prescription-created", (data = {}) => {
    try {
      broadcastPrescriptionToUser(
        { ...data, timestamp: data.timestamp || new Date().toISOString() },
        { publish: true },
      );
    } catch (error) {
      console.error(
        "prescription-created handler failed",
        error?.message || error
      );
    }
  });
  // ========== DOCTOR JOINS THEIR "ROOM" ==========
  socket.on("join-doctor", async (doctorIdRaw) => {
    const doctorId = Number(doctorIdRaw);
    if (!Number.isFinite(doctorId)) {
      console.warn("Invalid doctorId for join-doctor:", doctorIdRaw);
      return;
    }
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);

    const existing = activeDoctors.get(doctorId);
    cancelDoctorDisconnect(doctorId);
    if (existing && existing.socketId && existing.socketId !== socket.id) {
      console.log(
        `⚠️ Doctor ${doctorId} reconnecting (old socket: ${existing.socketId})`
      );
    }

    const updated = upsertDoctorEntry(doctorId, {
      socketId: socket.id,
      joinedAt: new Date(),
      lastSeen: new Date(),
      connectionStatus: "connected",
    });
    await setDoctorOnline(doctorId, socket.id, { mode: "always_online" });


    console.log(
      `✅ Doctor ${doctorId} joined (Total active: ${activeDoctors.size})`
    );

    if (!existing || existing.connectionStatus !== updated.connectionStatus) {
      socket.emit("doctor-online", {
        doctorId,
        status: "online",
        timestamp: new Date().toISOString(),
      });
    }

    emitAvailableDoctors();

    // If there were queued / in-progress calls for this doctor, replay them
    deliverPendingSessionsToDoctor(doctorId, socket);
  });

  // Doctor explicitly sets ONLINE (header toggle / app opened)
  socket.on("doctor-online", (payload = {}) => {
    const doctorId = Number(payload?.doctorId ?? payload?.id);
    if (!doctorId) return;

    upsertDoctorEntry(doctorId, {
      socketId: socket.id,
      lastSeen: new Date(),
      connectionStatus: "connected",
    });

    emitAvailableDoctors();
  });

  // Doctor explicitly goes OFFLINE – ONLY manual button should call this
  socket.on("doctor-offline", (payload = {}) => {
    const doctorId = Number(payload?.doctorId ?? payload?.id);
    if (!doctorId) return;

    console.log(`👋 Doctor ${doctorId} manually set to offline`);

    activeDoctors.delete(doctorId);
    removeDoctorPresence(doctorId).catch(() => {});
    removeDoctorPresence(doctorId).catch(() => {});
    socket.leave(`doctor-${doctorId}`);

    // Let this doctor know
    socket.emit("doctor-offline", {
      doctorId,
      status: "offline",
      timestamp: new Date().toISOString(),
    });

    emitAvailableDoctors();
  });

  // ========== HEARTBEAT FROM DOCTOR FRONTEND ==========
  socket.on(DOCTOR_HEARTBEAT_EVENT, async (payload = {}) => {
    const doctorId = Number(payload.doctorId || payload.id);
    if (!doctorId) return;

    upsertDoctorEntry(doctorId, {
      socketId: socket.id,
      lastSeen: new Date(),
      connectionStatus: "connected",
    });

    await heartbeatDoctor(doctorId, payload?.source || "foreground");
  });

  // ========== REGISTER PUSH TOKEN ==========
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

    const cachedToken = doctorPushTokens.get(doctorId);
    if (cachedToken && cachedToken === pushToken) {
      socket.emit("push-token-registered", {
        success: true,
        doctorId,
        message: "Push token already registered",
        timestamp: new Date().toISOString(),
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

      // Optional: send test notification
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

  // ========== PATIENT ASKS: WHO'S ACTIVE? ==========
  socket.on("get-active-doctors", () => {
    const available = [];
    const busy = [];

    for (const [doctorId] of activeDoctors.entries()) {
      if (isDoctorBusy(doctorId)) busy.push(doctorId);
      else available.push(doctorId);
    }

    const now = Date.now();
    const shouldLog =
      now - lastActiveDoctorRequestLog.timestamp >
        ACTIVE_DOCTOR_REQUEST_LOG_INTERVAL_MS ||
      available.length !== lastActiveDoctorRequestLog.available ||
      busy.length !== lastActiveDoctorRequestLog.busy;

    if (shouldLog) {
      console.log(
        `📊 Active doctors request: ${available.length} available, ${busy.length} busy`
      );
      lastActiveDoctorRequestLog = {
        timestamp: now,
        available: available.length,
        busy: busy.length,
      };
    }

    const availableKey = available.slice().sort((a, b) => a - b).join(",");
    if (socket._lastActiveDoctorsKey === availableKey) {
      return;
    }
    socket._lastActiveDoctorsKey = availableKey;

    socket.emit("active-doctors", available);
  });

  // ========== PATIENT STARTS CALL ==========
  // IMPORTANT: We DO NOT block if doctor is offline/busy – we queue + notify.
  socket.on(
    "call-requested",
    async ({ doctorId, patientId, channel, callId: incomingCallId }) => {
      console.log(`📞 Call request: Patient ${patientId} → Doctor ${doctorId}`);

      doctorId = Number(doctorId);

      const generatedCallId = `call_${Date.now()}_${Math.random()
        .toString(36)
        .substring(2, 8)}`;
      const callId = normalizeCallId(incomingCallId) || generatedCallId;

      if (activeCalls.has(callId)) {
        console.log(`⚠️ Call ${callId} already exists, ignoring duplicate`);
        socket.emit("call-status-update", {
          callId,
          status: "duplicate",
          message: "Call already in progress",
        });
        return;
      }

      const doctorEntry = activeDoctors.get(doctorId) || null;
      const connectionStatus = doctorEntry?.connectionStatus || "disconnected";
      const doctorHasSocket = Boolean(doctorEntry?.socketId);
      const doctorConnected =
        doctorHasSocket && connectionStatus === "connected";
      const doctorBusy = isDoctorBusy(doctorId);
      const shouldQueue = !doctorConnected || doctorBusy;
      const existingQueue = pendingCalls.get(doctorId) || [];

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

      logFlow("call-requested", {
        callId,
        doctorId,
        patientId,
        channel,
        socketId: socket.id,
        extra: `doctorConnected=${doctorConnected}, doctorBusy=${doctorBusy}`,
      });

      if (shouldQueue && existingQueue.length >= PENDING_CALL_MAX_PER_DOCTOR) {
        const message =
          "Doctor queue is full right now. Please try another doctor or retry in a minute.";
        socket.emit("call-status-update", {
          callId,
          doctorId,
          patientId,
          status: "rejected",
          queued: true,
          reason: "queue_full",
          message,
          timestamp: new Date().toISOString(),
        });
        socket.emit("call-rejected", {
          callId,
          doctorId,
          patientId,
          reason: "queue_full",
          message,
          timestamp: new Date().toISOString(),
        });
        emitAvailableDoctors();
        return;
      }

      setActiveCallSession(callId, callSession);
      await persistCallRequested(callSession);

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
              : "Doctor is currently offline/away. We've queued your call and will alert them as soon as they come online.",
        });

        emitAvailableDoctors();
        return;
      }

      // Direct ring to connected doctor
      io.to(`doctor-${doctorId}`).emit("call-requested", {
        callId,
        doctorId,
        patientId,
        channel,
        timestamp: new Date().toISOString(),
        queued: false,
      });

      // Also send push notification in case app is backgrounded / killed
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
    }
  );

  // ========== DOCTOR ACCEPTS CALL ==========
  socket.on("call-accepted", (data) => {
    const { callId: rawCallId, doctorId, patientId, channel } = data;
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(`✅ Call ${normalizedCallId} accepted by doctor ${doctorId}`);
    clearNotificationSent(normalizedCallId, doctorId);

    const callSession = activeCalls.get(normalizedCallId);
    if (!callSession) {
      console.log(`❌ Call session ${normalizedCallId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    callSession.status = "accepted";
    callSession.acceptedAt = new Date();
    callSession.doctorSocketId = socket.id;
    setActiveCallSession(normalizedCallId, callSession);

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-accepted", {
        callId: normalizedCallId,
        doctorId,
        patientId,
        channel,
        agoraToken:
          callSession.agoraToken ||
          callSession.token ||
          callSession.agora_token ||
          null,
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
    const { callId: rawCallId, reason = "rejected", doctorId, patientId } = data;
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(
      `❌ Call ${normalizedCallId} rejected by doctor ${doctorId}: ${reason}`,
    );
    clearNotificationSent(normalizedCallId, doctorId);

    const pendingRemoved = removePendingCallEntry(doctorId, normalizedCallId);
    const callSession = activeCalls.get(normalizedCallId);
    if (!callSession) {
      console.log(`❌ Call session ${normalizedCallId} not found for rejection`);
      if (pendingRemoved) {
        deliverNextPendingCall(doctorId);
        emitAvailableDoctors();
      }
      return;
    }

    callSession.status = "rejected";
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    // Ensure any queued instance for this doctor is removed immediately
    removePendingCallEntry(doctorId, normalizedCallId);

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-rejected", {
        callId: normalizedCallId,
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
        callId: normalizedCallId,
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
      callId: normalizedCallId,
      status: "rejected",
      rejectedBy: "doctor",
      reason,
      timestamp: new Date().toISOString(),
    });

    // Remove active call immediately so it can't be re-delivered on reconnect
    deleteActiveCallSession(normalizedCallId);
    emitAvailableDoctors();
    deliverNextPendingCall(doctorId);
    console.log(`🗑️ Cleaned up rejected call ${normalizedCallId}`);
  });

  // ========== PATIENT PAYMENT COMPLETED ==========
  socket.on("payment-completed", (data) => {
    const { callId: rawCallId, patientId, doctorId, channel, paymentId } = data;
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(`💰 Payment completed for call ${normalizedCallId}`);

    const callSession = activeCalls.get(normalizedCallId);
    if (!callSession) {
      console.log(`❌ Call session ${normalizedCallId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    callSession.status = "payment_completed";
    callSession.paymentId = paymentId;
    callSession.paidAt = new Date();
    if (channel) callSession.channel = channel;
    setActiveCallSession(normalizedCallId, callSession);

    logFlow("payment-completed", {
      callId: normalizedCallId,
      doctorId,
      patientId,
      channel: callSession.channel,
      paymentId,
      patientSocketId: socket.id,
    });

    // Tell patient: you're good, here's your join link (audience)
    socket.emit("payment-verified", {
      callId: normalizedCallId,
      channel: callSession.channel,
      patientId,
      doctorId,
      status: "ready_to_connect",
      message: "Payment successful!",
      videoUrl:
        `/call-page/${callSession.channel}` +
        `?uid=${patientId}` +
        `&role=audience` +
        `&callId=${normalizedCallId}`,
      role: "audience",
      uid: Number(patientId),
      timestamp: new Date().toISOString(),
    });

    const patientPaidData = {
      callId: normalizedCallId,
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
        `&callId=${normalizedCallId}` +
        `&doctorId=${doctorId}` +
        `&patientId=${patientId}`,
      role: "host",
      uid: Number(doctorId),
      timestamp: new Date().toISOString(),
    };

    // Tell doctor: join now (host)
    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("patient-paid", patientPaidData);
    } else {
      io.to(`doctor-${doctorId}`).emit("patient-paid", {
        ...patientPaidData,
        queued: true,
      });
    }

    emitAvailableDoctors();
  });

  // ========== PATIENT PAYMENT CANCELLED ==========
  socket.on("payment-cancelled", (data) => {
    const { callId: rawCallId, patientId, doctorId, channel, reason } = data;
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(
      `Payment cancelled for call ${normalizedCallId} by patient ${patientId}`
    );

    const callSession = activeCalls.get(normalizedCallId);
    if (!callSession) {
      console.log(`Call session ${normalizedCallId} not found for cancellation`);
      return;
    }

    callSession.status = "payment_cancelled";
    callSession.cancelledAt = new Date();
    callSession.cancellationReason =
      reason || "patient_cancelled_payment";

    clearNotificationSent(normalizedCallId, doctorId);
    removePendingCallEntry(doctorId, normalizedCallId);

    logFlow("payment-cancelled", {
      callId: normalizedCallId,
      doctorId,
      patientId,
      channel,
      reason,
      patientSocketId: socket.id,
      doctorSocketId: callSession.doctorSocketId,
    });

    if (callSession.doctorSocketId) {
      io.to(callSession.doctorSocketId).emit("payment-cancelled", {
        callId: normalizedCallId,
        doctorId,
        patientId,
        reason: reason || "patient_cancelled_payment",
        message: "Patient cancelled the payment",
        timestamp: new Date().toISOString(),
      });
    }

    io.to(`doctor-${doctorId}`).emit("payment-cancelled", {
      callId: normalizedCallId,
      doctorId,
      patientId,
      reason: reason || "patient_cancelled_payment",
      message: "Patient cancelled the payment",
      timestamp: new Date().toISOString(),
    });

    io.emit("call-status-update", {
      callId: normalizedCallId,
      status: "payment_cancelled",
      cancelledBy: "patient",
      reason,
      timestamp: new Date().toISOString(),
    });

    setTimeout(() => {
      deleteActiveCallSession(normalizedCallId);
      emitAvailableDoctors();
      deliverNextPendingCall(doctorId);
      console.log(`Cleaned up cancelled call ${normalizedCallId}`);
    }, 2000);
  });

  // ========== CALL STARTED ==========
  socket.on("call-started", ({ callId: rawCallId, userId, role }) => {
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(`📹 User ${userId} (${role}) joined call ${normalizedCallId}`);

    const call = activeCalls.get(normalizedCallId);
    if (call) {
      call.status = "active";
      if (role === "host") call.doctorJoinedAt = new Date();
      if (role === "audience") call.patientJoinedAt = new Date();
      setActiveCallSession(normalizedCallId, call);
    }

    logFlow("call-started", {
      callId: normalizedCallId,
      userId,
      role,
      doctorId: call?.doctorId,
      patientId: call?.patientId,
      channel: call?.channel,
    });

    emitAvailableDoctors();
  });

  // ========== CALL ENDED ==========
  socket.on(
    "call-ended",
    ({ callId: rawCallId, userId, role, doctorId, patientId, channel }) => {
      const normalizedCallId = normalizeCallId(rawCallId);
      console.log(`🔚 Call ${normalizedCallId} ended by ${userId} (${role})`);

      const callSession = activeCalls.get(normalizedCallId);

      if (callSession) {
        clearNotificationSent(normalizedCallId, callSession.doctorId);
        callSession.status = "ended";
        callSession.endedAt = new Date();
        callSession.endedBy = userId;

        const isDoctorEnding = role === "host";
        const isPatientEnding = role === "audience";

        logFlow("call-ended", {
          callId: normalizedCallId,
          doctorId: doctorId ?? callSession.doctorId,
          patientId: patientId ?? callSession.patientId,
          channel: channel ?? callSession.channel,
          userId,
          role,
          reason: "ended",
          doctorSocketId: callSession.doctorSocketId,
          patientSocketId: callSession.patientSocketId,
        });

        if (isDoctorEnding && callSession.patientSocketId) {
          io.to(callSession.patientSocketId).emit("call-ended-by-other", {
            callId: normalizedCallId,
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
            callId: normalizedCallId,
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
          io.to(`doctor-${callSession.doctorId}`).emit("call-ended-by-other", {
            callId: normalizedCallId,
            endedBy: isDoctorEnding ? "doctor" : "patient",
            reason: "ended",
            message: isDoctorEnding
              ? "Doctor has ended the call"
              : "Patient has ended the call",
            timestamp: new Date().toISOString(),
          });
        }

        if (callSession.patientId) {
          io.to(`patient-${callSession.patientId}`).emit("call-ended-by-other", {
            callId: normalizedCallId,
            endedBy: isDoctorEnding ? "doctor" : "patient",
            reason: "ended",
            message: isDoctorEnding
              ? "Doctor has ended the call"
              : "Patient has ended the call",
            timestamp: new Date().toISOString(),
          });
        }

        if (callSession.patientSocketId) {
          io.to(callSession.patientSocketId).emit("force-disconnect", {
            callId: normalizedCallId,
            reason: "ended",
            endedBy: isDoctorEnding ? "doctor" : "patient",
            timestamp: new Date().toISOString(),
          });
        }

        if (callSession.doctorSocketId) {
          io.to(callSession.doctorSocketId).emit("force-disconnect", {
            callId: normalizedCallId,
            reason: "ended",
            endedBy: isDoctorEnding ? "doctor" : "patient",
            timestamp: new Date().toISOString(),
          });
        }
      }

      io.emit("call-status-update", {
        callId: normalizedCallId,
        status: "ended",
        endedBy: role,
        timestamp: new Date().toISOString(),
      });

      setTimeout(() => {
        deleteActiveCallSession(normalizedCallId);
        emitAvailableDoctors();
        console.log(`🗑️ Cleaned up ended call ${normalizedCallId}`);
      }, 10_000);
    }
  );

  // ========== DOCTOR MANUALLY LEAVES (similar to doctor-offline) ==========
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

    // Mark any doctor with this socket as "disconnected" but keep them in map
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        console.log(
          `🔌 Doctor ${doctorId} socket disconnected, marking as disconnected (always-online mode)`
        );
        scheduleDoctorDisconnect(doctorId, socket.id);
      }
    }

    emitAvailableDoctors();

    // Handle calls where this socket was in the middle
    for (const [callId, callSession] of activeCalls.entries()) {
      if (
        callSession.patientSocketId === socket.id ||
        callSession.doctorSocketId === socket.id
      ) {
        console.log(`🔌 Handling disconnect for call ${callId}`);

        const isDoctor = callSession.doctorSocketId === socket.id;
        const isPatient = callSession.patientSocketId === socket.id;

        const wasCallActive =
          callSession.status === "active" ||
          (callSession.doctorJoinedAt && callSession.patientJoinedAt);

        callSession.status = "disconnected";
        callSession.disconnectedAt = new Date();
        callSession.disconnectedBy = isDoctor ? "doctor" : "patient";

        logFlow("socket-disconnect", {
          callId,
          doctorId: callSession.doctorId,
          patientId: callSession.patientId,
          channel: callSession.channel,
          userId: isDoctor ? callSession.doctorId : callSession.patientId,
          role: isDoctor ? "doctor" : "patient",
          reason: "disconnect",
          doctorSocketId: callSession.doctorSocketId,
          patientSocketId: callSession.patientSocketId,
          status: callSession.status,
          socketId: socket.id,
        });

        if (wasCallActive) {
          const otherSocketId = isDoctor
            ? callSession.patientSocketId
            : callSession.doctorSocketId;

          if (otherSocketId) {
            io.to(otherSocketId).emit("other-party-disconnected", {
              callId,
              disconnectedBy: isDoctor ? "doctor" : "patient",
              message: `The ${
                isDoctor ? "doctor" : "patient"
              } disconnected unexpectedly`,
              timestamp: new Date().toISOString(),
            });

            console.log(
              `📤 Sent disconnect notification to ${
                isDoctor ? "patient" : "doctor"
              } (socket: ${otherSocketId})`
            );

            io.to(otherSocketId).emit("force-disconnect", {
              callId,
              reason: "peer_disconnected",
              disconnectedBy: isDoctor ? "doctor" : "patient",
              timestamp: new Date().toISOString(),
            });

            const otherSocket = io.sockets.sockets.get(otherSocketId);
            if (otherSocket) {
              otherSocket.disconnect(true);
              logFlow("force-disconnect-counterpart", {
                callId,
                doctorId: callSession.doctorId,
                patientId: callSession.patientId,
                channel: callSession.channel,
                socketId: otherSocketId,
                reason: "peer_disconnected",
              });
            }
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
        }

        setTimeout(() => {
          deleteActiveCallSession(callId);
          emitAvailableDoctors();
          console.log(`🗑️ Cleaned up disconnected call ${callId}`);
        }, 30_000);
      }
    }
  });
});

// Stale cleanup: remove doctors after inactivity/reconnect grace
setInterval(() => {
  const now = Date.now();
  let removedCount = 0;

  for (const [doctorId, info] of activeDoctors.entries()) {
    const lastSeen = info.lastSeen ? new Date(info.lastSeen).getTime() : 0;
    const disconnectedAt = info.disconnectedAt
      ? new Date(info.disconnectedAt).getTime()
      : 0;
    const stale = lastSeen && now - lastSeen > DOCTOR_STALE_TIMEOUT_MS;
    const reconnectExpired =
      info.connectionStatus === "reconnecting" &&
      disconnectedAt &&
      now - disconnectedAt > DISCONNECT_GRACE_MS;
    if (stale || reconnectExpired) {
      activeDoctors.delete(doctorId);
      removeDoctorPresence(doctorId).catch(() => {});
      removedCount++;
    }
  }

  if (removedCount > 0) {
    console.log(`Cleanup: Removed ${removedCount} stale doctor(s)`);
    emitAvailableDoctors();
  }
}, 30_000);


// Auto time-out calls older than 5 minutes that haven't finished
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

      deleteActiveCallSession(callId);
      emitAvailableDoctors();
    }
  }
}, 5 * 60 * 1000);

// Periodic log of server state
setInterval(() => {
  console.log(
    `📊 Stats: Connections: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}, Active Calls: ${activeCalls.size}, Pending Doctor Queues: ${pendingCalls.size}`
  );
  console.log(`👨‍⚕️ Active Doctor IDs:`, Array.from(activeDoctors.keys()));
}, 30_000);

// -------------------- START SERVER --------------------
const PORT = Number(process.env.PORT || process.env.SOCKET_PORT || 4000);
httpServer.on("error", (error) => {
  if (error?.code === "EADDRINUSE") {
    console.error(
      `[server] Port ${PORT} is already in use. Stop the other instance or set SOCKET_PORT/PORT to a free port.`
    );
    process.exit(1);
  } else {
    console.error("[server] HTTP server error:", error);
  }
});
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
  console.log(`📍 Health check: http://localhost:${PORT}/health`);
  console.log(`📍 Active doctors: http://localhost:${PORT}/active-doctors`);
  console.log(
    `🔥 Firebase Admin initialized: ${
      admin.apps.length > 0 ? "YES" : "NO"
    }`
  );
  console.log(
    `📱 Push notifications: ${
      admin.apps.length > 0 ? "ENABLED" : "DISABLED"
    }`
  );
  console.log(`⏰ Doctor stale timeout: ${DOCTOR_STALE_TIMEOUT_MS / 1000}s`);
  console.log(`⏱️ Pending call timeout: ${PENDING_CALL_TIMEOUT_MS / 1000}s`);
  console.log(`🌐 Environment: ${process.env.NODE_ENV || "development"}`);
  if (isWhatsAppConfigured() && WHATSAPP_ALERT_HAS_RECIPIENTS) {
    console.log(`📲 WhatsApp alerts: ENABLED (${WHATSAPP_ALERT_MODE} mode)`);
  }
});

