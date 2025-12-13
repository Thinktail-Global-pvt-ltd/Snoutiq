import { redis } from "./redisClient.js";

// Extend TTL to match production-grade presence window and allow BG heartbeats to keep doctors online
const HB_TTL_SEC = 70;

export async function setDoctorOnline(doctorId, socketId, extra = {}) {
  try {
    // ✅ FIX: Check Redis connection status before operations
    if (redis.status !== "ready" && redis.status !== "connecting") {
      // Silently skip if Redis is not connected (enableOfflineQueue is false)
      return;
    }
    
    const key = `doctor:${doctorId}`;
    await redis
      .multi()
      .sadd("doctors:live", String(doctorId))
      .hset(key, {
        status: "online",
        socketId: socketId || "",
        lastSeen: Date.now().toString(),
        mode: extra.mode || "always_online",
        clinicId: extra.clinicId ? String(extra.clinicId) : "",
      })
      .set(`doctor:${doctorId}:hb`, "1", "EX", HB_TTL_SEC)
      .exec();
  } catch (e) {
    // ✅ FIX: Only log if it's not a connection/writeable error (expected when offline queue disabled)
    if (!e?.message?.includes("writeable") && !e?.message?.includes("Stream")) {
      console.warn("[Redis] setDoctorOnline failed:", e?.message || e);
    }
  }
}

export async function heartbeatDoctor(doctorId, source = "foreground") {
  try {
    // ✅ FIX: Check Redis connection status before operations
    if (redis.status !== "ready" && redis.status !== "connecting") {
      // Silently skip if Redis is not connected (enableOfflineQueue is false)
      return;
    }
    
    await redis
      .multi()
      .hset(`doctor:${doctorId}`, {
        status: "online",
        lastSeen: Date.now().toString(),
        hbSource: source,
      })
      .set(`doctor:${doctorId}:hb`, "1", "EX", HB_TTL_SEC)
      .exec();
  } catch (e) {
    // ✅ FIX: Only log if it's not a connection/writeable error (expected when offline queue disabled)
    if (!e?.message?.includes("writeable") && !e?.message?.includes("Stream")) {
      console.warn("[Redis] heartbeatDoctor failed:", e?.message || e);
    }
  }
}
