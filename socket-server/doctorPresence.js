import { redis } from "./redisClient.js";

const HB_TTL_SEC = 30;

export async function setDoctorOnline(doctorId, socketId, extra = {}) {
  try {
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
    console.warn("[Redis] setDoctorOnline failed:", e?.message || e);
  }
}

export async function heartbeatDoctor(doctorId, source = "foreground") {
  try {
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
    console.warn("[Redis] heartbeatDoctor failed:", e?.message || e);
  }
}
