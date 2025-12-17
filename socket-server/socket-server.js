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

// -------------------- RATE LIMITING --------------------
// Rate limiter: patientId -> { timestamps: number[], window: number }
const callRequestRateLimiter = new Map();
const RATE_LIMIT_WINDOW_MS = 60000; // 1 minute
const RATE_LIMIT_MAX_REQUESTS = 5; // Max 5 calls per minute per patient

const checkRateLimit = (patientId) => {
  const now = Date.now();
  const limiter = callRequestRateLimiter.get(patientId) || { timestamps: [], window: RATE_LIMIT_WINDOW_MS };
  
  // Remove timestamps outside the window
  limiter.timestamps = limiter.timestamps.filter(ts => now - ts < RATE_LIMIT_WINDOW_MS);
  
  if (limiter.timestamps.length >= RATE_LIMIT_MAX_REQUESTS) {
    return { allowed: false, remaining: 0, resetAt: limiter.timestamps[0] + RATE_LIMIT_WINDOW_MS };
  }
  
  limiter.timestamps.push(now);
  callRequestRateLimiter.set(patientId, limiter);
  
  return { 
    allowed: true, 
    remaining: RATE_LIMIT_MAX_REQUESTS - limiter.timestamps.length,
    resetAt: limiter.timestamps[0] + RATE_LIMIT_WINDOW_MS
  };
};

// -------------------- PAYMENT IDEMPOTENCY LOCK --------------------
const PAYMENT_IDEMPOTENCY_TTL = 3600; // 1 hour (sufficient for payment processing)

// Atomically claim payment processing (prevents duplicate payment processing)
const claimPaymentProcessing = async (paymentId, callId) => {
  if (!paymentId || !callId) {
    console.warn("[payment:idempotency] Missing paymentId or callId");
    return { claimed: false, reason: "missing_params" };
  }

  if (!isRedisStateReady()) {
    console.warn("[payment:idempotency] Redis unavailable, allowing payment (degraded mode)");
    return { claimed: true, degraded: true };
  }

  const idempotencyKey = `payment:${callId}:${paymentId}`;
  try {
    // SET key value NX EX ttl - only sets if key doesn't exist (atomic operation)
    const result = await coreRedis.set(
      idempotencyKey,
      JSON.stringify({
        callId,
        paymentId,
        claimedAt: new Date().toISOString(),
      }),
      'EX',
      PAYMENT_IDEMPOTENCY_TTL,
      'NX' // Only set if not exists
    );

    if (result === 'OK') {
      // Successfully claimed payment processing
      console.log(`🔒 [payment:idempotency] Payment ${paymentId} locked for call ${callId}`);
      return { claimed: true, idempotencyKey };
    }

    // Payment already being processed or already processed
    const existingData = await coreRedis.get(idempotencyKey);
    console.log(`⚠️ [payment:idempotency] Payment ${paymentId} already processed for call ${callId}`);
    
    try {
      const parsed = existingData ? JSON.parse(existingData) : null;
      return {
        claimed: false,
        reason: "duplicate",
        existingData: parsed,
        idempotencyKey,
      };
    } catch (e) {
      return { claimed: false, reason: "duplicate", idempotencyKey };
    }
  } catch (error) {
    console.warn(`[payment:idempotency] Failed to claim payment ${paymentId}:`, error?.message || error);
    // On Redis error, allow payment but log warning (fail-open for availability)
    return { claimed: true, degraded: true, error: error.message };
  }
};

// Check if payment was already processed (without claiming)
const checkPaymentProcessed = async (paymentId, callId) => {
  if (!paymentId || !callId || !isRedisStateReady()) {
    return { processed: false };
  }

  const idempotencyKey = `payment:${callId}:${paymentId}`;
  try {
    const exists = await coreRedis.exists(idempotencyKey);
    if (exists) {
      const data = await coreRedis.get(idempotencyKey);
      return {
        processed: true,
        data: data ? JSON.parse(data) : null,
      };
    }
    return { processed: false };
  } catch (error) {
    console.warn(`[payment:idempotency] Failed to check payment ${paymentId}:`, error?.message || error);
    return { processed: false, error: error.message };
  }
};

// -------------------- ATOMIC DOCTOR BUSY LOCK --------------------
const DOCTOR_BUSY_LOCK_TTL = 300; // 5 minutes
const BUSY_LOCK_WATCHDOG_INTERVAL_MS = 10_000; // 10 seconds (reduced from 60s for faster cleanup)

// Atomically claim doctor for a call (prevents race conditions)
const claimDoctorForCall = async (doctorId, callId) => {
  if (!isRedisStateReady()) {
    // Fallback to in-memory check if Redis unavailable
    return !isDoctorBusy(doctorId);
  }
  
  const lockKey = `doctor:${doctorId}:busy_lock`;
  try {
    // SET key value NX EX ttl - only sets if key doesn't exist (atomic operation)
    const result = await coreRedis.set(
      lockKey,
      callId,
      "EX",
      DOCTOR_BUSY_LOCK_TTL,
      "NX" // Only set if not exists
    );
    
    if (result === "OK") {
      // Successfully claimed doctor
      console.log(`🔒 Doctor ${doctorId} locked for call ${callId}`);
      return true;
    }
    
    // Doctor is already busy (lock exists)
    const existingCallId = await coreRedis.get(lockKey);
    console.log(`⚠️ Doctor ${doctorId} is busy (locked by call ${existingCallId})`);
    return false;
  } catch (error) {
    console.warn(
      `[redis:lock] Failed to claim doctor ${doctorId}:`,
      error?.message || error,
    );
    // Fallback to in-memory check on Redis error
    return !isDoctorBusy(doctorId);
  }
};

// Release doctor lock (called when call ends or is rejected)
// ✅ FIX: Use Lua script for atomic check-and-delete operation
const releaseDoctorLock = async (doctorId, callId) => {
  if (!isRedisStateReady()) {
    console.log(
      `🔓 Redis not ready, clearing in-memory busy status for doctor ${doctorId}`,
    );
    return;
  }
  
  const lockKey = `doctor:${doctorId}:busy_lock`;
  
  try {
    // ✅ FIX: Use Lua script for atomic check-and-delete
    // This executes atomically on Redis server, preventing race conditions
    const luaScript = `
      if redis.call("GET", KEYS[1]) == ARGV[1] then
        return redis.call("DEL", KEYS[1])
      else
        return 0
      end
    `;
    
    const result = await coreRedis.eval(
      luaScript,
      1,           // number of keys
      lockKey,     // KEYS[1]
      callId       // ARGV[1]
    );
    
    if (result === 1) {
      console.log(`🔓 Doctor ${doctorId} lock released atomically for call ${callId}`);
      
      // ✅ Emit availability change event
      io.emit("doctors-availability-changed", {
        timestamp: new Date().toISOString(),
        reason: "lock_released",
        doctorId: doctorId,
      });
    } else {
      const existingCallId = await coreRedis.get(lockKey);
      if (existingCallId) {
        console.log(
          `⚠️ Lock for doctor ${doctorId} held by different call ${existingCallId}, not releasing`,
        );
      } else {
        console.log(
          `ℹ️ No lock found for doctor ${doctorId} (already released)`,
        );
      }
    }
  } catch (error) {
    console.warn(
      `[redis:lock] Failed to release doctor ${doctorId} lock:`,
      error?.message || error,
    );
  }
};

// Periodic safety net: clear orphan busy locks that no longer have an active call session
// ✅ FIX: Aggressive watchdog with batch processing and immediate startup execution
const startBusyLockWatchdog = () => {
  // Guard against environments without Redis / coreRedis
  if (typeof setInterval !== "function") return;

  const runWatchdog = async () => {
    try {
      if (!isRedisStateReady()) return;

      let cursor = "0";
      do {
        const [nextCursor, keys] = await coreRedis.scan(
          cursor,
          "MATCH",
          "doctor:*:busy_lock",
          "COUNT",
          100,
        );

        cursor = nextCursor;

        if (Array.isArray(keys) && keys.length) {
          // ✅ FIX: Use pipeline for batch processing
          const pipeline = coreRedis.pipeline();
          
          for (const lockKey of keys) {
            // ✅ Get lock value (callId) and check active calls in one go
            pipeline.get(lockKey);
          }
          
          const results = await pipeline.exec();
          
          results.forEach(([err, callId], index) => {
            if (err || !callId) return;
            
            const lockKey = keys[index];
            const doctorId = lockKey.match(/doctor:(\d+):busy_lock/)?.[1];
            if (!doctorId) return;
            
            const normalizedCallId = normalizeCallId(callId);
            const callSession = normalizedCallId ? activeCalls.get(normalizedCallId) : null;

            const terminalStatuses = new Set([
              "ENDED",
              "MISSED",
              "REJECTED",
              "FAILED",
            ]);
            
            const isOrphan =
              !callSession ||
              !callSession.status ||
              terminalStatuses.has(String(callSession.status).toUpperCase());

            if (isOrphan) {
              // ✅ Force delete orphan lock
              coreRedis.del(lockKey).then(() => {
                console.log(
                  `[busy-lock-watchdog] Cleared orphan lock ${lockKey} (callId=${callId})`,
                );
                
                // ✅ Emit availability changed
                io.emit("doctors-availability-changed", {
                  timestamp: new Date().toISOString(),
                  reason: "watchdog_cleanup",
                  doctorId: doctorId,
                });
              }).catch((delError) => {
                console.warn(
                  "[busy-lock-watchdog] Failed to delete orphan lock",
                  lockKey,
                  delError?.message || delError,
                );
              });
            }
          });
        }
      } while (cursor !== "0");
    } catch (error) {
      console.warn(
        "[busy-lock-watchdog] Unexpected error",
        error?.message || error,
      );
    }
  };

  // ✅ Run IMMEDIATELY on startup (clears stale locks from crashes)
  runWatchdog();
  
  // ✅ Run every 10 seconds (not 60!) - fast enough for UX
  const timer = setInterval(runWatchdog, BUSY_LOCK_WATCHDOG_INTERVAL_MS);
  if (typeof timer.unref === "function") {
    timer.unref();
  }
};

// Start watchdog as part of server boot – safe no-op if Redis not ready
startBusyLockWatchdog();

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
  
  // ✅ FIX: Exclude timer objects and other non-serializable fields to prevent circular structure errors
  const {
    timeoutTimerId,        // Timer object - cannot be serialized
    timeoutExpiresAt,      // May contain timer reference
    timer,                 // Generic timer reference
    _idlePrev,             // Timer internal properties
    _idleNext,             // Timer internal properties
    ...serializableSession  // Everything else is safe to serialize
  } = callSession;
  
  const payload = {
    ...serializableSession,
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
    // Store timeout expiration as timestamp string instead of timer object
    timeoutExpiresAt: callSession.timeoutExpiresAt
      ? (typeof callSession.timeoutExpiresAt === 'number' 
          ? callSession.timeoutExpiresAt.toString()
          : new Date(callSession.timeoutExpiresAt).getTime().toString())
      : undefined,
  };

  try {
    await setHashWithTtl(
      REDIS_ACTIVE_CALLS_KEY,
      callSession.callId,
      payload,
      REDIS_CALL_TTL_SECONDS,
    );
  } catch (error) {
    // Log error but don't throw - Redis persistence is non-critical
    console.warn(
      `[redis:state] Failed to persist call state for ${callSession.callId}:`,
      error?.message || error
    );
  }
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

const getRazorpayAuthHeader = () => {
  const keyId = process.env.RAZORPAY_KEY_ID || process.env.RZP_KEY_ID;
  const keySecret =
    process.env.RAZORPAY_KEY_SECRET || process.env.RZP_KEY_SECRET;

  if (!keyId || !keySecret) {
    console.warn(
      "[payment] Razorpay credentials missing - cannot manage Razorpay orders",
    );
    return null;
  }

  const token = Buffer.from(`${keyId}:${keySecret}`).toString("base64");
  return `Basic ${token}`;
};

const cancelRazorpayOrder = async (orderId) => {
  if (!orderId) return false;
  const authHeader = getRazorpayAuthHeader();
  if (!authHeader) return false;

  try {
    const orderDetails = await fetch(
      `https://api.razorpay.com/v1/orders/${orderId}`,
      {
        method: "GET",
        headers: { Authorization: authHeader },
      },
    );

    if (!orderDetails.ok) {
      console.warn(
        `[payment] Unable to fetch Razorpay order ${orderId} for cancellation`,
        orderDetails.status,
      );
      return false;
    }

    const order = await orderDetails.json();
    const status = String(order?.status || "").toLowerCase();
    if (status === "paid" || status === "captured") {
      console.log(
        `[payment] Order ${orderId} already paid/captured, skipping cancellation`,
      );
      return false;
    }

    if (status === "cancelled") {
      console.log(`[payment] Order ${orderId} already cancelled (idempotent)`);
      return true;
    }

    const cancelResponse = await fetch(
      `https://api.razorpay.com/v1/orders/${orderId}/cancel`,
      {
        method: "POST",
        headers: { Authorization: authHeader },
      },
    );

    if (!cancelResponse.ok) {
      const text = await cancelResponse.text().catch(() => "");
      console.warn(
        `[payment] Razorpay order cancel failed for ${orderId}`,
        cancelResponse.status,
        text,
      );
      return false;
    }

    console.log(`[payment] Razorpay order ${orderId} cancelled successfully`);
    return true;
  } catch (error) {
    console.warn(
      `[payment] Failed to cancel Razorpay order ${orderId}`,
      error?.message || error,
    );
    return false;
  }
};

const sendMissedCallFCM = async (callSession) => {
  try {
    if (!callSession) return;
    const { callId, doctorId, patientId } = callSession;
    if (!callId || !doctorId) return;

    // If doctor is connected in foreground, rely on socket events
    if (callSession.doctorSocketId) {
      console.log(
        `[push] Skipping missed_call push for call ${callId} - doctor socket active`,
      );
      return;
    }

    const payload = {
      type: "missed_call",
      callId,
      patientId: patientId ?? "",
      patientName:
        callSession.patientName ||
        callSession.patientFullName ||
        callSession.patient ||
        (patientId ? `Patient ${patientId}` : "Patient"),
      petName:
        callSession.petName ||
        callSession.pet_name ||
        callSession.pet ||
        callSession.petNameFallback ||
        "",
      timestamp: new Date().toISOString(),
      dataOnly: true,
      message: "You missed a consultation request.",
      title: "Missed Consultation",
      body: "Patient missed consultation request",
      channel: callSession.channel,
      agoraToken: callSession.agoraToken || callSession.token || null,
    };

    await sendPushNotification(doctorId, payload);
    console.log(`[push] Missed call push sent to doctor for call ${callId}`);
  } catch (error) {
    console.warn(
      "[push] Missed call FCM send failed",
      error?.message || error,
    );
  }
};

// ✅ Send FCM notification to patient when call is missed
// Note: This uses sendPushNotification which is designed for doctors
// Patient token support may need to be added separately
const sendPatientMissedCallFCM = async (callSession) => {
  try {
    if (!callSession) return;
    const { callId, doctorId, patientId } = callSession;
    if (!callId || !patientId) return;

    // If patient is connected in foreground, rely on socket events
    if (callSession.patientSocketId) {
      console.log(
        `[push] Skipping missed_call push for call ${callId} - patient socket active`,
      );
      return;
    }

    // Note: sendPushNotification is designed for doctors, but we'll try it
    // Patient push token support may need to be implemented separately
    const payload = {
      type: "missed_call",
      callId,
      doctorId: doctorId ?? "",
      doctorName:
        callSession.doctorName ||
        callSession.doctor?.name ||
        (doctorId ? `Doctor ${doctorId}` : "Doctor"),
      timestamp: new Date().toISOString(),
      dataOnly: true,
      message: "Call was missed. You can rejoin the call.",
      title: "Call Missed",
      body: "Doctor didn't respond. Tap to rejoin call.",
      channel: callSession.channel,
      agoraToken: callSession.agoraToken || callSession.token || null,
    };

    // Try to send notification (may fail if patient token system not implemented)
    try {
      await sendPushNotification(patientId, payload);
      console.log(`[push] Missed call push sent to patient for call ${callId}`);
    } catch (error) {
      // Patient notification may not work if token system is doctor-only
      console.log(`[push] Patient notification skipped (may need patient token support): ${error?.message || error}`);
    }
  } catch (error) {
    console.warn(
      "[push] Patient missed call FCM send failed",
      error?.message || error,
    );
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
const CALL_RINGING_TIMEOUT_MS = 30_000; // 30 seconds timeout for RINGING status
const ACTIVE_DOCTOR_LOG_INTERVAL_MS = 5_000;
const ACTIVE_DOCTOR_REQUEST_LOG_INTERVAL_MS = 5_000;
const CALL_RESUME_GRACE_MS = Number(
  process.env.CALL_RESUME_GRACE_MS || 5 * 60 * 1000,
);
const CALL_CLEANUP_GRACE_MS = Math.max(30_000, CALL_RESUME_GRACE_MS + 10_000);
const RESUMABLE_STATUSES = new Set([
  "payment_completed",
  "active",
  "disconnected",
  "awaiting_resume",
]);

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
// Helper to clear timeout timer from call session
const clearCallTimeoutTimer = async (callSession, callId = null) => {
  if (callSession?.timeoutTimerId) {
    clearTimeout(callSession.timeoutTimerId);
    callSession.timeoutTimerId = null;
  }
  
  // Clear timer expiration timestamp
  if (callSession?.timeoutExpiresAt) {
    callSession.timeoutExpiresAt = null;
  }
  
  // Clear timer from Redis if callId provided
  if (callId && isRedisStateReady()) {
    try {
      await coreRedis.del(`call:${callId}:timer`);
    } catch (error) {
      console.warn(`[redis:timer] Failed to clear timer for call ${callId}:`, error?.message || error);
    }
  }
};

const setActiveCallSession = (callId, callSession) => {
  const normalizedCallId = normalizeCallId(callId);
  if (!normalizedCallId || !callSession) return null;
  const session = { ...callSession, callId: normalizedCallId };

  const getAnchorMs = (value) => {
    if (value instanceof Date) return value.getTime();
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : Date.now();
  };

  const computedResumeUntil =
    session.resumableUntil ??
    new Date(
      getAnchorMs(
        session.paidAt ||
          session.acceptedAt ||
          session.createdAt ||
          session.disconnectedAt,
      ) + CALL_RESUME_GRACE_MS,
    );

  session.resumableUntil = computedResumeUntil;
  session.status = session.status || "requested";
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

const deliverNextPendingCall = async (doctorId) => {
  const queue = pendingCalls.get(doctorId);
  if (!queue || queue.length === 0) return;

  const doctorEntry = activeDoctors.get(doctorId);
  if (!doctorEntry || !doctorEntry.socketId) return;

  const status = doctorEntry.connectionStatus || "disconnected";
  if (status !== "connected") return;

  // Check both in-memory busy status and atomic lock
  const inMemoryBusy = isDoctorBusy(doctorId);
  const nextCall = queue[0];
  if (!nextCall) return;

  // Try to claim doctor atomically for the next pending call
  const claimed = await claimDoctorForCall(doctorId, nextCall.callId);
  
  if (!claimed || inMemoryBusy) {
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

  // Initialize timeout timer if not exists
  if (!session.timeoutTimerId) {
    session.timeoutTimerId = null;
  }
  if (!session.ringingStartedAt) {
    session.ringingStartedAt = null;
  }

  // Update status to RINGING when delivered to doctor
  session.status = "RINGING";
  session.ringingStartedAt = new Date();
  session.requestedAt = new Date();
  session.doctorSocketId = doctorEntry.socketId;
  session.timeoutExpiresAt = new Date(Date.now() + CALL_RINGING_TIMEOUT_MS);
  setActiveCallSession(session.callId, session);

  // Persist timeout expiration timestamp
  if (isRedisStateReady()) {
    try {
      await coreRedis.hset(
        `call:${entry.callId}:timer`,
        'expiresAt',
        session.timeoutExpiresAt.getTime().toString(),
        'callId',
        entry.callId,
        'doctorId',
        String(doctorId)
      );
      await coreRedis.expire(`call:${entry.callId}:timer`, Math.ceil(CALL_RINGING_TIMEOUT_MS / 1000) + 60);
    } catch (error) {
      console.warn(`[redis:timer] Failed to persist timer for call ${entry.callId}:`, error?.message || error);
    }
  }

  // Start 30-second timeout timer
  session.timeoutTimerId = setTimeout(async () => {
    const currentSession = activeCalls.get(entry.callId);
    if (!currentSession || currentSession.status !== "RINGING") {
      return; // Status changed, timer already cleared
    }

    console.log(`⏰ Call ${entry.callId} timed out after 30 seconds`);
    
    // Update status to MISSED
    currentSession.status = "MISSED";
    currentSession.missedAt = new Date();
    currentSession.timeoutTimerId = null;
    currentSession.timeoutExpiresAt = null;
    setActiveCallSession(entry.callId, currentSession);

    // Release doctor lock
    releaseDoctorLock(doctorId, entry.callId).catch(() => {});

    // Clear timer from Redis
    if (isRedisStateReady()) {
      try {
        await coreRedis.del(`call:${entry.callId}:timer`);
      } catch (error) {
        console.warn(`[redis:timer] Failed to clear timer for call ${entry.callId}:`, error?.message || error);
      }
    }

    const orderIdToCancel =
      currentSession.razorpayOrderId ||
      currentSession.orderId ||
      currentSession.order_id;
    if (orderIdToCancel) {
      cancelRazorpayOrder(orderIdToCancel).catch(() => {});
    }
    sendMissedCallFCM(currentSession).catch(() => {});

    // Emit call-missed event to both patient and doctor
    const missedPayload = {
      callId: entry.callId,
      doctorId,
      patientId: currentSession.patientId,
      status: "MISSED",
      previousStatus: "RINGING",
      reason: "timeout",
      message: "Doctor didn't respond in time",
      timestamp: new Date().toISOString(),
      channel: currentSession.channel,
      agoraToken: currentSession.agoraToken || currentSession.token || null,
      doctorName: currentSession.doctorName || currentSession.doctor?.name || null,
      patientName: currentSession.patientName || currentSession.patientFullName || null,
      petName: currentSession.petName || currentSession.pet_name || null,
    };

    if (currentSession.patientSocketId) {
      io.to(currentSession.patientSocketId).emit("call-missed", missedPayload);
    }
    io.to(`doctor-${doctorId}`).emit("call-missed", missedPayload);
    
    // ✅ Send FCM notification to patient if app is in background/killed
    if (currentSession.patientId) {
      sendPatientMissedCallFCM(currentSession).catch(() => {});
    }

    // Emit call-status-update
    io.emit("call-status-update", {
      callId: entry.callId,
      status: "MISSED",
      previousStatus: "RINGING",
      reason: "timeout",
      timestamp: new Date().toISOString(),
    });
  }, CALL_RINGING_TIMEOUT_MS);

  setActiveCallSession(session.callId, session);

  io.to(`doctor-${doctorId}`).emit("call-requested", {
    callId: session.callId,
    doctorId: session.doctorId,
    patientId: session.patientId,
    channel: session.channel,
    status: "RINGING",
    queued: true,
    timestamp: new Date().toISOString(),
  });

  if (session.patientSocketId) {
    io.to(session.patientSocketId).emit("call-queued", {
      callId: session.callId,
      doctorId: session.doctorId,
      status: "RINGING",
      message: "Doctor is now online. Alerting them to join your call.",
      timestamp: new Date().toISOString(),
    });

    // Emit status update to patient
    io.to(session.patientSocketId).emit("call-status-update", {
      callId: session.callId,
      status: "RINGING",
      previousStatus: session.status === "queued" ? "queued" : "INITIATED",
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
      if (!isDoctorBusy(doctorId)) {
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

const recoverCallTimers = async () => {
  if (!isRedisStateReady()) return;
  try {
    // Scan for all timer keys
    const timerKeys = [];
    let cursor = 0;
    do {
      const result = await coreRedis.scan(
        cursor,
        'MATCH',
        'call:*:timer',
        'COUNT',
        100
      );
      cursor = Number(result[0]);
      timerKeys.push(...result[1]);
    } while (cursor !== 0);

    for (const timerKey of timerKeys) {
      try {
        const timerData = await coreRedis.hgetall(timerKey);
        const callId = timerData.callId;
        const expiresAtStr = timerData.expiresAt;
        const doctorId = timerData.doctorId;

        if (!callId || !expiresAtStr) continue;

        const expiresAt = Number(expiresAtStr);
        const now = Date.now();
        const remaining = expiresAt - now;

        if (remaining <= 0) {
          // Timer already expired, handle timeout
          const callSession = activeCalls.get(callId);
          if (callSession && callSession.status === "RINGING") {
            console.log(`⏰ Recovered expired timer for call ${callId}, handling timeout`);
            // Trigger timeout handler (simplified - just update status)
            callSession.status = "MISSED";
            callSession.missedAt = new Date();
            setActiveCallSession(callId, callSession);
            releaseDoctorLock(Number(doctorId), callId).catch(() => {});
            await coreRedis.del(timerKey);
          }
        } else {
          // Restart timer
          const callSession = activeCalls.get(callId);
          if (callSession && callSession.status === "RINGING") {
            console.log(`⏰ Recovering timer for call ${callId}, ${Math.ceil(remaining / 1000)}s remaining`);
            callSession.timeoutExpiresAt = new Date(expiresAt);
            callSession.timeoutTimerId = setTimeout(async () => {
              const currentSession = activeCalls.get(callId);
              if (!currentSession || currentSession.status !== "RINGING") {
                return;
              }
              console.log(`⏰ Recovered timer expired for call ${callId}`);
              currentSession.status = "MISSED";
              currentSession.missedAt = new Date();
              currentSession.timeoutTimerId = null;
              currentSession.timeoutExpiresAt = null;
              setActiveCallSession(callId, currentSession);
              await releaseDoctorLock(Number(doctorId), callId);
              await coreRedis.del(timerKey);
              // Emit missed call events (same as normal timeout)
              const missedPayload = {
                callId,
                doctorId: Number(doctorId),
                patientId: currentSession.patientId,
                status: "MISSED",
                previousStatus: "RINGING",
                reason: "timeout",
                message: "Doctor didn't respond in time",
                timestamp: new Date().toISOString(),
              };
              if (currentSession.patientSocketId) {
                io.to(currentSession.patientSocketId).emit("call-missed", missedPayload);
              }
              io.to(`doctor-${doctorId}`).emit("call-missed", missedPayload);
              io.emit("call-status-update", {
                callId,
                status: "MISSED",
                previousStatus: "RINGING",
                reason: "timeout",
                timestamp: new Date().toISOString(),
              });
            }, remaining);
            setActiveCallSession(callId, callSession);
          }
        }
      } catch (error) {
        console.warn(`[redis:timer] Failed to recover timer ${timerKey}:`, error?.message || error);
      }
    }
  } catch (error) {
    console.warn(`[redis:timer] Timer recovery failed:`, error?.message || error);
  }
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

const sendJson = (res, status, payload) => {
  res.writeHead(status, {
    "Content-Type": "application/json",
    "Access-Control-Allow-Origin": "*",
  });
  res.end(JSON.stringify(payload));
};

const parseJsonBody = async (req) => {
  try {
    const chunks = [];
    for await (const chunk of req) {
      chunks.push(chunk);
    }
    const raw = Buffer.concat(chunks).toString("utf8");
    if (!raw) return {};
    return JSON.parse(raw);
  } catch (error) {
    console.warn("[http] Failed to parse JSON body", error?.message || error);
    return {};
  }
};

// -------------------- HTTP SERVER (health + debug APIs) --------------------
const httpServer = createServer(async (req, res) => {
  try {
    const url = new URL(req.url, `http://${req.headers.host}`);
    const pathname = (url.pathname || "/").replace(/\/+$/, "") || "/";

    // CORS preflight
    if (req.method === "OPTIONS") {
      res.writeHead(204, {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type, Authorization",
      });
      res.end();
      return;
    }

    const isCreateOrderPath =
      pathname === "/create-order" || pathname === "/backend/api/create-order";
    const isVerifyPaymentPath =
      pathname === "/rzp/verify" || pathname === "/backend/api/rzp/verify";

    if (req.method === "POST" && isCreateOrderPath) {
      const body = await parseJsonBody(req);
      const normalizedCallId = normalizeCallId(body?.callId);
      const callSession = normalizedCallId
        ? activeCalls.get(normalizedCallId)
        : null;

      if (!normalizedCallId || !callSession) {
        console.warn(
          `[payment] Rejecting create-order: call not found (${body?.callId})`,
        );
        sendJson(res, 400, {
          success: false,
          message: "Invalid or unknown callId",
        });
        return;
      }

      if (callSession.status !== "ACCEPTED") {
        console.warn(
          `[payment] Rejecting create-order for call ${normalizedCallId} - status=${callSession.status}`,
        );
        sendJson(res, 409, {
          success: false,
          message: "Call is not in ACCEPTED state",
        });
        return;
      }

      const amountNumber = Number(body?.amount);
      const amountPaise = Math.round(amountNumber * 100);
      if (!Number.isFinite(amountPaise) || amountPaise <= 0) {
        sendJson(res, 400, {
          success: false,
          message: "Invalid amount",
        });
        return;
      }

      const authHeader = getRazorpayAuthHeader();
      if (!authHeader) {
        sendJson(res, 500, {
          success: false,
          message: "Payment gateway not configured",
        });
        return;
      }

      try {
        const orderResponse = await fetch(
          "https://api.razorpay.com/v1/orders",
          {
            method: "POST",
            headers: {
              Authorization: authHeader,
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              amount: amountPaise,
              currency: "INR",
              receipt: normalizedCallId,
              notes: {
                callId: normalizedCallId,
                doctorId:
                  body?.doctorId ??
                  body?.doctor_id ??
                  callSession.doctorId ??
                  "",
                patientId:
                  body?.patientId ??
                  body?.patient_id ??
                  callSession.patientId ??
                  "",
                channel: body?.channel ?? callSession.channel ?? "",
              },
            }),
          },
        );

        if (!orderResponse.ok) {
          const text = await orderResponse.text().catch(() => "");
          console.warn(
            `[payment] Razorpay create-order failed for call ${normalizedCallId}`,
            orderResponse.status,
            text,
          );
          sendJson(res, 502, {
            success: false,
            message: "Failed to create order",
          });
          return;
        }

        const order = await orderResponse.json();
        callSession.razorpayOrderId = order?.id || order?.order_id;
        setActiveCallSession(normalizedCallId, callSession);

        sendJson(res, 200, {
          success: true,
          order_id: order?.id || order?.order_id,
          id: order?.id || order?.order_id,
          key: process.env.RAZORPAY_KEY_ID || process.env.RZP_KEY_ID || null,
          amount: order?.amount,
          currency: order?.currency || "INR",
        });
      } catch (error) {
        console.warn(
          `[payment] create-order exception for call ${normalizedCallId}`,
          error?.message || error,
        );
        sendJson(res, 500, {
          success: false,
          message: "Order creation failed",
        });
      }
      return;
    }

    if (req.method === "POST" && isVerifyPaymentPath) {
      const body = await parseJsonBody(req);
      const normalizedCallId = normalizeCallId(body?.callId);
      const callSession = normalizedCallId
        ? activeCalls.get(normalizedCallId)
        : null;

      if (!normalizedCallId || !callSession) {
        console.warn(
          `[payment] Rejecting verification: call not found (${body?.callId})`,
        );
        sendJson(res, 400, {
          success: false,
          message: "Invalid or unknown callId",
        });
        return;
      }

      // Check if call was missed/rejected/ended - reject payment
      if (["MISSED", "REJECTED", "ENDED"].includes(callSession.status)) {
        console.warn(
          `[payment] Rejecting verification for call ${normalizedCallId} - status=${callSession.status}`,
        );
        sendJson(res, 409, {
          success: false,
          message: `Call was ${callSession.status.toLowerCase()}. Payment cannot be processed.`,
          callStatus: callSession.status,
        });
        return;
      }

      if (callSession.status !== "ACCEPTED") {
        console.warn(
          `[payment] Rejecting verification for call ${normalizedCallId} - status=${callSession.status}`,
        );
        sendJson(res, 409, {
          success: false,
          message: "Call is not in ACCEPTED state",
          callStatus: callSession.status,
        });
        return;
      }

      const orderId =
        body?.razorpay_order_id || body?.orderId || callSession.razorpayOrderId;
      const paymentId = body?.razorpay_payment_id || body?.paymentId;
      const signature = body?.razorpay_signature || body?.signature;

      // ========== PAYMENT IDEMPOTENCY CHECK ==========
      if (paymentId) {
        const idempotencyCheck = await claimPaymentProcessing(paymentId, normalizedCallId);
        
        if (!idempotencyCheck.claimed && idempotencyCheck.reason === "duplicate") {
          console.log(
            `[payment] Payment ${paymentId} already processed for call ${normalizedCallId}. Returning success (idempotent).`
          );
          
          // If payment was already processed, return success (idempotent behavior)
          // Check if call status is already payment_completed or active
          if (["payment_completed", "active"].includes(callSession.status)) {
            sendJson(res, 200, {
              success: true,
              status: "already_processed",
              message: "Payment already processed",
              callId: normalizedCallId,
              paymentId,
              callStatus: callSession.status,
            });
            return;
          }
          
          // Payment was processed but call status not updated (edge case)
          // Still return success to avoid double charging
          sendJson(res, 200, {
            success: true,
            status: "duplicate_ignored",
            message: "Payment already processed",
            callId: normalizedCallId,
            paymentId,
          });
          return;
        }
        
        if (idempotencyCheck.degraded) {
          console.warn(
            `[payment] Payment idempotency check degraded (Redis unavailable). Processing payment anyway.`
          );
        }
      }

      const keySecret =
        process.env.RAZORPAY_KEY_SECRET || process.env.RZP_KEY_SECRET;
      if (!keySecret) {
        sendJson(res, 500, {
          success: false,
          message: "Payment gateway not configured",
        });
        return;
      }

      if (!orderId || !paymentId || !signature) {
        sendJson(res, 400, {
          success: false,
          message: "Missing payment parameters",
        });
        return;
      }

      const expectedSignature = crypto
        .createHmac("sha256", keySecret)
        .update(`${orderId}|${paymentId}`)
        .digest("hex");

      if (expectedSignature !== signature) {
        console.warn(
          `[payment] Invalid Razorpay signature for call ${normalizedCallId}`,
        );
        
        // Release idempotency lock on signature failure
        if (paymentId) {
          const idempotencyKey = `payment:${normalizedCallId}:${paymentId}`;
          await coreRedis.del(idempotencyKey).catch(() => {});
        }
        
        sendJson(res, 400, {
          success: false,
          message: "Invalid signature",
        });
        return;
      }

      // Re-check call status after signature verification (race condition protection)
      const currentCallSession = activeCalls.get(normalizedCallId);
      if (!currentCallSession) {
        console.warn(
          `[payment] Call session ${normalizedCallId} disappeared during payment verification`
        );
        sendJson(res, 404, {
          success: false,
          message: "Call session not found",
        });
        return;
      }

      if (["MISSED", "REJECTED", "ENDED"].includes(currentCallSession.status)) {
        console.warn(
          `[payment] Call ${normalizedCallId} status changed to ${currentCallSession.status} during payment verification`
        );
        
        // Release idempotency lock
        if (paymentId) {
          const idempotencyKey = `payment:${normalizedCallId}:${paymentId}`;
          await coreRedis.del(idempotencyKey).catch(() => {});
        }
        
        sendJson(res, 409, {
          success: false,
          message: `Call was ${currentCallSession.status.toLowerCase()}. Payment cannot be processed.`,
          callStatus: currentCallSession.status,
        });
        return;
      }

      // Update call session with payment info
      callSession.razorpayOrderId = orderId;
      callSession.paymentId = paymentId;
      callSession.paymentVerifiedAt = new Date();
      setActiveCallSession(normalizedCallId, callSession);

      // Idempotency key already set above, no need to set again
      console.log(
        `✅ [payment] Payment ${paymentId} verified successfully for call ${normalizedCallId}`
      );

      sendJson(res, 200, {
        success: true,
        status: "verified",
        callId: normalizedCallId,
        paymentId,
      });
      return;
    }

    // health check
    if (req.method === "GET" && pathname === "/health") {
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
    if (req.method === "GET" && pathname === "/active-doctors") {
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
await recoverCallTimers(); // Recover any pending timeout timers
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

    // ✅ NEW: Check and clear any stale locks for this doctor
    if (isRedisStateReady()) {
      try {
        const lockKey = `doctor:${doctorId}:busy_lock`;
        const existingLock = await coreRedis.get(lockKey);
        if (existingLock) {
          // Check if the locked call still exists
          const callExists = activeCalls.has(existingLock);
          if (!callExists) {
            // Stale lock - clear it
            await coreRedis.del(lockKey);
            console.log(`🧹 Cleared stale lock for doctor ${doctorId} on rejoin`);
          }
        }
      } catch (error) {
        console.warn(`Failed to check stale lock for doctor ${doctorId}:`, error?.message || error);
      }
    }

    // ✅ CRITICAL: Emit available doctors IMMEDIATELY after doctor joins
    emitAvailableDoctors();
    
    // ✅ NEW: Emit multiple times to ensure clients receive update
    setTimeout(() => emitAvailableDoctors(), 500);
    setTimeout(() => emitAvailableDoctors(), 1500);

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
    
    // ✅ FIX: Always emit on explicit requests, but throttle rapid duplicate requests
    // Only skip if this is the exact same list AND it was sent very recently (< 500ms ago)
    const lastEmitTime = socket._lastActiveDoctorsEmitTime || 0;
    const timeSinceLastEmit = now - lastEmitTime;
    const isDuplicate = socket._lastActiveDoctorsKey === availableKey;
    
    if (isDuplicate && timeSinceLastEmit < 500) {
      // Skip duplicate request within 500ms window
      return;
    }
    
    socket._lastActiveDoctorsKey = availableKey;
    socket._lastActiveDoctorsEmitTime = now;

    socket.emit("active-doctors", available);
  });

  // ========== PATIENT STARTS CALL ==========
  // IMPORTANT: We DO NOT block if doctor is offline/busy – we queue + notify.
  socket.on(
    "call-requested",
    async ({ doctorId, patientId, channel, callId: incomingCallId, timestamp }) => {
      console.log(`📞 Call request: Patient ${patientId} → Doctor ${doctorId}`);

      // ✅ FIX: Validate timestamp
      const now = Date.now();
      const callTime = timestamp ? new Date(timestamp).getTime() : now;
      const ageInSeconds = (now - callTime) / 1000;
      
      if (ageInSeconds > 60) {
        console.warn(`⚠️ Rejecting stale call request (age: ${ageInSeconds}s)`);
        
        io.to(`patient-${patientId}`).emit("call-rejected", {
          callId: incomingCallId || null,
          doctorId,
          patientId,
          reason: "stale_request",
          message: "Call request expired",
          timestamp: new Date().toISOString(),
        });
        
        return;
      }

      doctorId = Number(doctorId);
      patientId = Number(patientId);

      // ========== RATE LIMITING ==========
      const rateLimitResult = checkRateLimit(patientId);
      if (!rateLimitResult.allowed) {
        const resetIn = Math.ceil((rateLimitResult.resetAt - Date.now()) / 1000);
        console.log(`🚫 Rate limit exceeded for patient ${patientId}. Reset in ${resetIn}s`);
        socket.emit("call-status-update", {
          callId: null,
          doctorId,
          patientId,
          status: "rate_limited",
          message: `Too many call requests. Please wait ${resetIn} seconds before trying again.`,
          resetIn,
          timestamp: new Date().toISOString(),
        });
        socket.emit("call-rejected", {
          callId: null,
          doctorId,
          patientId,
          reason: "rate_limit",
          message: `Rate limit exceeded. Please wait ${resetIn} seconds.`,
          timestamp: new Date().toISOString(),
        });
        return;
      }

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
      
      // ========== ATOMIC DOCTOR BUSY CHECK ==========
      const doctorClaimed = await claimDoctorForCall(doctorId, callId);
      const doctorBusy = !doctorClaimed; // If not claimed, doctor is busy
      const shouldQueue = !doctorConnected || doctorBusy;
      const existingQueue = pendingCalls.get(doctorId) || [];

      const callSession = {
        callId,
        doctorId,
        patientId,
        channel,
        status: shouldQueue ? "queued" : "INITIATED",
        createdAt: new Date(),
        queuedAt: shouldQueue ? new Date() : null,
        patientSocketId: socket.id,
        doctorSocketId: doctorEntry?.socketId || null,
        timeoutTimerId: null,
        ringingStartedAt: null,
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
      // Update status to RINGING when delivered to doctor
      callSession.status = "RINGING";
      callSession.ringingStartedAt = new Date();
      callSession.timeoutExpiresAt = new Date(Date.now() + CALL_RINGING_TIMEOUT_MS);
      setActiveCallSession(callId, callSession);

      // Persist timeout expiration timestamp
      if (isRedisStateReady()) {
        try {
          await coreRedis.hset(
            `call:${callId}:timer`,
            'expiresAt',
            callSession.timeoutExpiresAt.getTime().toString(),
            'callId',
            callId,
            'doctorId',
            String(doctorId)
          );
          await coreRedis.expire(`call:${callId}:timer`, Math.ceil(CALL_RINGING_TIMEOUT_MS / 1000) + 60); // Extra 60s buffer
        } catch (error) {
          console.warn(`[redis:timer] Failed to persist timer for call ${callId}:`, error?.message || error);
        }
      }

      // Start 30-second timeout timer
      callSession.timeoutTimerId = setTimeout(async () => {
        const currentSession = activeCalls.get(callId);
        if (!currentSession || currentSession.status !== "RINGING") {
          return; // Status changed, timer already cleared
        }

        console.log(`⏰ Call ${callId} timed out after 30 seconds`);
        
        // Update status to MISSED
        currentSession.status = "MISSED";
        currentSession.missedAt = new Date();
        currentSession.timeoutTimerId = null;
        currentSession.timeoutExpiresAt = null;
        setActiveCallSession(callId, currentSession);

        // ✅ PHASE 3: Use finalizeCall for guaranteed cleanup
        await finalizeCall(callId, doctorId, "missed");

        const orderIdToCancel =
          currentSession.razorpayOrderId ||
          currentSession.orderId ||
          currentSession.order_id;
        if (orderIdToCancel) {
          cancelRazorpayOrder(orderIdToCancel).catch(() => {});
        }
        sendMissedCallFCM(currentSession).catch(() => {});

        // Emit call-missed event to both patient and doctor
        const missedPayload = {
          callId,
          doctorId,
          patientId: currentSession.patientId,
          status: "MISSED",
          previousStatus: "RINGING",
          reason: "timeout",
          message: "Doctor didn't respond in time",
          timestamp: new Date().toISOString(),
        };

        if (currentSession.patientSocketId) {
          io.to(currentSession.patientSocketId).emit("call-missed", missedPayload);
        }
        io.to(`doctor-${doctorId}`).emit("call-missed", missedPayload);

        // Emit call-status-update
        io.emit("call-status-update", {
          callId,
          status: "MISSED",
          previousStatus: "RINGING",
          reason: "timeout",
          timestamp: new Date().toISOString(),
        });
      }, CALL_RINGING_TIMEOUT_MS);

      setActiveCallSession(callId, callSession);

      io.to(`doctor-${doctorId}`).emit("call-requested", {
        callId,
        doctorId,
        patientId,
        channel,
        status: "RINGING",
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
        status: "RINGING",
        queued: false,
        message: "Ringing the doctor now…",
        timestamp: new Date().toISOString(),
      });

      // Emit status update to patient
      socket.emit("call-status-update", {
        callId,
        status: "RINGING",
        previousStatus: "INITIATED",
        timestamp: new Date().toISOString(),
      });

      emitAvailableDoctors();
    }
  );

  // ========== DOCTOR ACCEPTS CALL ==========
  socket.on("call-accepted", async (data) => {
    const { callId: rawCallId, doctorId, patientId, channel } = data;
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(`✅ Call ${normalizedCallId} accepted by doctor ${doctorId}`);
    clearNotificationSent(normalizedCallId, doctorId);

    const callSession = activeCalls.get(normalizedCallId);
    if (!callSession) {
      console.log(`❌ Call session ${normalizedCallId} not found`);
      return socket.emit("error", { message: "Call session not found" });
    }

    // Clear timeout timer if exists
    await clearCallTimeoutTimer(callSession, normalizedCallId);
    
    // NOTE: We do NOT release doctor lock here because call is now ACTIVE
    // Lock will be released when call ends

    const previousStatus = callSession.status;
    callSession.status = "ACCEPTED";
    callSession.acceptedAt = new Date();
    callSession.doctorSocketId = socket.id;
    setActiveCallSession(normalizedCallId, callSession);

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-accepted", {
        callId: normalizedCallId,
        doctorId,
        patientId,
        channel,
        status: "ACCEPTED",
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

    // Emit call-status-update
    io.emit("call-status-update", {
      callId: normalizedCallId,
      status: "ACCEPTED",
      previousStatus,
      timestamp: new Date().toISOString(),
    });

    emitAvailableDoctors();
  });

  // ========== DOCTOR REJECTS CALL ==========
  socket.on("call-rejected", async (data) => {
    const { callId: rawCallId, reason = "rejected", doctorId, patientId } = data;
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(
      `❌ Call ${normalizedCallId} rejected by doctor ${doctorId}: ${reason}`,
    );

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

    // ✅ PHASE 3: Use finalizeCall for guaranteed cleanup
    await finalizeCall(normalizedCallId, doctorId, "rejected");

    const previousStatus = callSession.status;
    callSession.status = "REJECTED";
    callSession.rejectedAt = new Date();
    callSession.rejectionReason = reason;

    // Ensure any queued instance for this doctor is removed immediately
    removePendingCallEntry(doctorId, normalizedCallId);

    if (callSession.patientSocketId) {
      io.to(callSession.patientSocketId).emit("call-rejected", {
        callId: normalizedCallId,
        doctorId: callSession.doctorId,
        patientId: callSession.patientId,
        status: "REJECTED",
        reason,
        message:
          reason === "timeout"
            ? "Doctor did not respond within 30 seconds"
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
        status: "REJECTED",
        reason,
        message:
          reason === "timeout"
            ? "Doctor did not respond within 30 seconds"
            : "Doctor is currently unavailable",
        timestamp: new Date().toISOString(),
      });
    }

    io.emit("call-status-update", {
      callId: normalizedCallId,
      status: "REJECTED",
      previousStatus,
      rejectedBy: "doctor",
      reason,
      timestamp: new Date().toISOString(),
    });

    // finalizeCall already removed active call and emitted available doctors
    deliverNextPendingCall(doctorId);
    console.log(`🗑️ Cleaned up rejected call ${normalizedCallId}`);
  });

  // ========== PATIENT PAYMENT COMPLETED ==========
  socket.on("payment-completed", async (data, ack) => {
    const { callId: rawCallId, patientId, doctorId, channel, paymentId } = data;
    const normalizedCallId = normalizeCallId(rawCallId);
    console.log(`💰 Payment completed for call ${normalizedCallId}, paymentId: ${paymentId}`);

    const callSession = activeCalls.get(normalizedCallId);
    if (!callSession) {
      console.log(`❌ Call session ${normalizedCallId} not found`);
      const errorResponse = { success: false, message: "Call session not found" };
      socket.emit("error", errorResponse);
      if (ack) ack(errorResponse);
      return;
    }

    // ========== PAYMENT IDEMPOTENCY CHECK ==========
    if (paymentId) {
      const idempotencyCheck = await claimPaymentProcessing(paymentId, normalizedCallId);
      
      if (!idempotencyCheck.claimed && idempotencyCheck.reason === "duplicate") {
        console.log(
          `[payment-completed] Payment ${paymentId} already processed for call ${normalizedCallId}. Ignoring duplicate.`
        );
        
        // Payment already processed - return success (idempotent behavior)
        const successResponse = {
          success: true,
          message: "Payment already processed",
          callId: normalizedCallId,
          paymentId,
          duplicate: true,
        };
        
        if (ack) ack(successResponse);
        
        // If call status is already payment_completed or active, emit current state
        if (["payment_completed", "active"].includes(callSession.status)) {
          socket.emit("payment-verified", {
            callId: normalizedCallId,
            channel: callSession.channel,
            patientId,
            doctorId,
            status: callSession.status,
            timestamp: new Date().toISOString(),
          });
        }
        return;
      }
    }

    // Check if call was missed/rejected/ended - reject payment
    if (["MISSED", "REJECTED", "ENDED"].includes(callSession.status)) {
      console.warn(
        `[payment-completed] Rejecting payment for call ${normalizedCallId} - status=${callSession.status}`
      );
      const errorResponse = {
        success: false,
        message: `Call was ${callSession.status.toLowerCase()}. Payment cannot be processed.`,
        callStatus: callSession.status,
      };
      socket.emit("error", errorResponse);
      if (ack) ack(errorResponse);
      
      // Release idempotency lock if set
      if (paymentId) {
        const idempotencyKey = `payment:${normalizedCallId}:${paymentId}`;
        await coreRedis.del(idempotencyKey).catch(() => {});
      }
      return;
    }

    // Validate call is in correct state for payment
    if (callSession.status !== "ACCEPTED" && callSession.status !== "payment_completed") {
      console.warn(
        `[payment-completed] Call ${normalizedCallId} status is ${callSession.status}, expected ACCEPTED`
      );
    }

    // Update call session
    const previousStatus = callSession.status;
    callSession.status = "payment_completed";
    callSession.paymentId = paymentId;
    callSession.paidAt = new Date();
    if (channel) callSession.channel = channel;
    setActiveCallSession(normalizedCallId, callSession);

    // Acknowledge payment received
    if (ack) {
      ack({
        success: true,
        message: "Payment received",
        callId: normalizedCallId,
        paymentId,
      });
    }

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

    // ✅ FIX 1: Get fresh doctor socket ID from activeDoctors map
    const doctorEntry = activeDoctors.get(doctorId);
    const currentDoctorSocketId = doctorEntry?.socketId || callSession.doctorSocketId;
    
    if (!currentDoctorSocketId) {
      console.warn(`⚠️ No socket ID for doctor ${doctorId}, using room emit only`);
    }
    
    // ✅ FIX 2: Emit to BOTH socket ID and room (triple redundancy)
    if (currentDoctorSocketId) {
      console.log(`📤 [payment-completed] Emitting patient-paid to doctor socket: ${currentDoctorSocketId}`);
      io.to(currentDoctorSocketId).emit("patient-paid", patientPaidData);
      
      // ✅ FIX 3: Also emit payment-verified as fallback
      io.to(currentDoctorSocketId).emit("payment-verified", {
        callId: normalizedCallId,
        channel: callSession.channel,
        doctorId,
        patientId,
        role: "host",
        uid: Number(doctorId),
        timestamp: new Date().toISOString(),
      });
    }
    
    // ✅ FIX 4: Emit to doctor room (always)
    console.log(`📤 [payment-completed] Emitting patient-paid to doctor room: doctor-${doctorId}`);
    io.to(`doctor-${doctorId}`).emit("patient-paid", patientPaidData);
    io.to(`doctor-${doctorId}`).emit("payment-verified", {
      callId: normalizedCallId,
      channel: callSession.channel,
      doctorId,
      patientId,
      role: "host",
      uid: Number(doctorId),
      timestamp: new Date().toISOString(),
    });
    
    // ✅ FIX 5: Emit call-status-update for additional visibility
    io.emit("call-status-update", {
      callId: normalizedCallId,
      status: "payment_completed",
      previousStatus: previousStatus,
      doctorId,
      patientId,
      paymentId,
      message: "Payment completed - doctor should join",
      timestamp: new Date().toISOString(),
    });
    
    // ✅ FIX 6: If doctor socket exists, verify receipt with ACK timeout
    if (currentDoctorSocketId) {
      const doctorSocket = io.sockets.sockets.get(currentDoctorSocketId);
      if (doctorSocket) {
        doctorSocket.timeout(5000).emit("patient-paid-ack", patientPaidData, (err) => {
          if (err) {
            console.warn(`⚠️ Doctor ${doctorId} did not acknowledge patient-paid within 5s`);
            // Retry emit one more time
            io.to(`doctor-${doctorId}`).emit("patient-paid", patientPaidData);
          } else {
            console.log(`✅ Doctor ${doctorId} acknowledged patient-paid`);
          }
        });
      }
    }

    emitAvailableDoctors();
  });

  // ========== CALL RESUME REQUEST ==========
  socket.on("call-resume", (payload = {}, ack) => {
    const normalizedCallId = normalizeCallId(payload.callId);
    const requesterRole = payload.requester || payload.role || "unknown";
    const requesterDoctorId = Number(payload.doctorId || payload.doctor_id);
    const requesterPatientId = Number(payload.patientId || payload.patient_id);
    const now = Date.now();

    if (!normalizedCallId) {
      const response = { ok: false, reason: "missing_call_id" };
      socket.emit("call-resume-denied", response);
      ack?.(response);
      return;
    }

    const callSession = activeCalls.get(normalizedCallId);
    if (!callSession) {
      const response = { ok: false, reason: "not_found", callId: normalizedCallId };
      socket.emit("call-resume-denied", response);
      ack?.(response);
      return;
    }

    const resumableUntil =
      callSession.resumableUntil instanceof Date
        ? callSession.resumableUntil.getTime()
        : Number(callSession.resumableUntil) || 0;
    const withinWindow =
      (RESUMABLE_STATUSES.has(callSession.status) || callSession.status === "active") &&
      (!resumableUntil || now <= resumableUntil);

    if (!withinWindow) {
      callSession.status = "ended";
      callSession.endedAt = new Date();
      setActiveCallSession(normalizedCallId, callSession);

      const response = {
        ok: false,
        reason: "expired",
        callId: normalizedCallId,
        resumableUntil: callSession.resumableUntil,
      };
      socket.emit("call-resume-denied", response);
      ack?.(response);

      if (callSession.patientSocketId) {
        io.to(callSession.patientSocketId).emit("call-resume-denied", response);
      }
      if (callSession.doctorSocketId) {
        io.to(callSession.doctorSocketId).emit("call-resume-denied", response);
      }
      return;
    }

    // Update socket bindings so we can notify both sides
    if (requesterRole === "doctor" || requesterDoctorId === callSession.doctorId) {
      callSession.doctorSocketId = socket.id;
    } else if (requesterRole === "patient" || requesterPatientId === callSession.patientId) {
      callSession.patientSocketId = socket.id;
    }
    if (payload.channel) callSession.channel = payload.channel;
    if (payload.agoraToken) {
      callSession.agoraToken = payload.agoraToken;
    }

    callSession.status = "awaiting_resume";
    callSession.disconnectedAt = callSession.disconnectedAt || new Date();
    setActiveCallSession(normalizedCallId, callSession);

    const resumePayload = {
      ok: true,
      callId: normalizedCallId,
      channel: callSession.channel,
      doctorId: callSession.doctorId,
      patientId: callSession.patientId,
      agoraToken:
        payload.agoraToken ||
        callSession.agoraToken ||
        callSession.token ||
        callSession.agora_token ||
        null,
      resumableUntil: callSession.resumableUntil,
      status: callSession.status,
    };

    socket.emit("call-resume-allowed", resumePayload);
    ack?.(resumePayload);

    if (callSession.patientSocketId && callSession.patientSocketId !== socket.id) {
      io.to(callSession.patientSocketId).emit("call-resume-allowed", resumePayload);
    } else if (callSession.patientId) {
      io.to(`patient-${callSession.patientId}`).emit("call-resume-allowed", resumePayload);
    }

    if (callSession.doctorSocketId && callSession.doctorSocketId !== socket.id) {
      io.to(callSession.doctorSocketId).emit("call-resume-allowed", resumePayload);
    } else if (callSession.doctorId) {
      io.to(`doctor-${callSession.doctorId}`).emit("call-resume-allowed", resumePayload);
    }
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

  // ✅ PHASE 3: Unified cleanup function for all call termination scenarios
  const finalizeCall = async (callId, doctorId, reason = "ended") => {
    const normalizedCallId = normalizeCallId(callId);
    const callSession = activeCalls.get(normalizedCallId);
    
    console.log(`🔚 Finalizing call ${normalizedCallId} (reason: ${reason})`);
    
    // ✅ STEP 1: Clear timeout timer (MUST be first)
    if (callSession) {
      await clearCallTimeoutTimer(callSession, normalizedCallId);
    }
    
    // ✅ STEP 2: Release doctor lock (atomic)
    if (doctorId) {
      await releaseDoctorLock(doctorId, normalizedCallId);
    }
    
    // ✅ STEP 3: Delete active call session (IMMEDIATE, not delayed)
    deleteActiveCallSession(normalizedCallId);
    
    // ✅ STEP 4: Clear notification sent tracking
    if (callSession) {
      clearNotificationSent(normalizedCallId, callSession.doctorId);
    }
    
    // ✅ STEP 5: Emit availability changed
    io.emit("doctors-availability-changed", {
      timestamp: new Date().toISOString(),
      reason: "call_finalized",
      callId: normalizedCallId,
      doctorId: doctorId,
    });
    
    // ✅ STEP 6: Emit availability update
    emitAvailableDoctors();
  };

  // ========== CALL ENDED ==========
  socket.on(
    "call-ended",
    async ({ callId: rawCallId, userId, role, doctorId, patientId, channel }) => {
      const normalizedCallId = normalizeCallId(rawCallId);
      console.log(`🔚 Call ${normalizedCallId} ended by ${userId} (${role})`);

      // ✅ PHASE 5: Acknowledge IMMEDIATELY (before any async work)
      // This prevents client from disconnecting before server processes
      socket.emit("call-ended-ack", {
        callId: normalizedCallId,
        received: true,
        timestamp: new Date().toISOString(),
      });

      // ✅ FIX: Define isPatientEnding and isDoctorEnding outside if block so they're always available
      const isDoctorEnding = role === "host";
      const isPatientEnding = role === "audience";

      const callSession = activeCalls.get(normalizedCallId);
      let previousStatusForUpdate = "UNKNOWN";

      // ✅ PHASE 3: Use finalizeCall for guaranteed cleanup
      const callDoctorId = doctorId ?? callSession?.doctorId;
      await finalizeCall(normalizedCallId, callDoctorId, "ended");

      if (callSession) {
        previousStatusForUpdate = callSession.status || "UNKNOWN";
        callSession.status = "ENDED";
        callSession.endedAt = new Date();
        callSession.endedBy = userId;

        // ✅ FIX: Variables already defined above, no need to redeclare
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
          // Send force-disconnect ONLY to cleanup Agora call, NOT to disconnect socket
          // Doctor should remain online and connected after call ends
          io.to(callSession.doctorSocketId).emit("force-disconnect", {
            callId: normalizedCallId,
            reason: "ended",
            endedBy: isDoctorEnding ? "doctor" : "patient",
            timestamp: new Date().toISOString(),
            // IMPORTANT: This is only for Agora cleanup, NOT socket disconnection
            keepSocketConnected: true,
          });
          
          // Ensure doctor remains in activeDoctors after call ends
          if (doctorId && activeDoctors.has(doctorId)) {
            const doctor = activeDoctors.get(doctorId);
            console.log(`✅ Doctor ${doctorId} will remain online after call end. Socket: ${doctor.socketId}`);
          }
        }
      }

      // ✅ Emit status update
      io.emit("call-status-update", {
        callId: normalizedCallId,
        status: "ENDED",
        previousStatus: previousStatusForUpdate,
        endedBy: role,
        endedByUserId: userId,
        message: isPatientEnding ? "Patient ended the call" : "Doctor ended the call",
        timestamp: new Date().toISOString(),
      });

      // ✅ NEW: Emit to all connected clients (broadcast) - finalizeCall already emitted, but emit again for redundancy
      io.emit("doctors-availability-changed", {
        timestamp: new Date().toISOString(),
        reason: "call_ended",
        callId: normalizedCallId,
      });

      // ✅ NEW FIX: Emit multiple times to ensure clients receive update (covers race conditions)
      setTimeout(() => {
        console.log("📢 Emitting available doctors (1s delay)");
        emitAvailableDoctors();
      }, 1000);
      
      setTimeout(() => {
        console.log("📢 Emitting available doctors (3s delay)");
        emitAvailableDoctors();
      }, 3000);
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

        setActiveCallSession(callId, callSession);

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
        }, CALL_CLEANUP_GRACE_MS);
      }
    }
  });
});

// ========== KEY FIX 3: Periodic cleanup of stale locks (every 30 seconds) ==========
setInterval(async () => {
  if (!isRedisStateReady()) return;
  
  try {
    // Scan for all doctor locks
    const lockKeys = [];
    let cursor = 0;
    do {
      const result = await coreRedis.scan(
        cursor,
        'MATCH',
        'doctor:*:busy_lock',
        'COUNT',
        100
      );
      cursor = Number(result[0]);
      lockKeys.push(...result[1]);
    } while (cursor !== 0);

    for (const lockKey of lockKeys) {
      try {
        const callId = await coreRedis.get(lockKey);
        if (!callId) continue;
        
        // Check if this call still exists
        const callExists = activeCalls.has(callId);
        if (!callExists) {
          // Call doesn't exist, release lock
          const doctorId = lockKey.match(/doctor:(\d+):busy_lock/)?.[1];
          if (doctorId) {
            await coreRedis.del(lockKey);
            console.log(`🧹 Cleaned up stale lock for doctor ${doctorId} (call ${callId} no longer exists)`);
            
            // Emit available doctors to update clients
            emitAvailableDoctors();
          }
        }
      } catch (error) {
        console.warn(`[redis:lock] Failed to check lock ${lockKey}:`, error?.message || error);
      }
    }
  } catch (error) {
    console.warn(`[redis:lock] Lock cleanup failed:`, error?.message || error);
  }
}, 30000); // Run every 30 seconds

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

// ✅ FIX: Periodic cleanup of expired calls (older than 2 minutes, not active)
setInterval(() => {
  const now = Date.now();
  const expiredThreshold = 2 * 60 * 1000; // 2 minutes
  
  for (const [callId, callSession] of activeCalls.entries()) {
    const createdAt = callSession.createdAt ? new Date(callSession.createdAt).getTime() : now;
    const age = now - createdAt;
    
    // ✅ FIX: Auto-expire calls older than 2 minutes that aren't active
    if (age > expiredThreshold && callSession.status !== "active" && callSession.status !== "payment_completed") {
      console.log(`🧹 Auto-expiring stale call ${callId} (age: ${Math.round(age / 1000)}s, status: ${callSession.status})`);
      
      // Clear timeout timer
      clearCallTimeoutTimer(callSession, callId).catch(() => {});
      
      // Delete call session
      deleteActiveCallSession(callId);
      
      // Release doctor lock
      if (callSession.doctorId) {
        releaseDoctorLock(callSession.doctorId, callId).catch(() => {});
      }
      
      // Notify parties
      if (callSession.patientSocketId) {
        io.to(callSession.patientSocketId).emit("call-timeout", {
          callId,
          message: "Call request expired",
          timestamp: new Date().toISOString(),
        });
      }
      
      if (callSession.doctorSocketId) {
        io.to(callSession.doctorSocketId).emit("call-timeout", {
          callId,
          message: "Call request expired",
          timestamp: new Date().toISOString(),
        });
      }
    }
  }
}, 60000); // Run every minute

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

