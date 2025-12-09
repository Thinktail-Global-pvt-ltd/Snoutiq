import Redis from "ioredis";

const {
  REDIS_URL,
  REDIS_HOST = "127.0.0.1",
  REDIS_PORT = "6379",
  REDIS_PASSWORD,
  NODE_ENV,
} = process.env;

function buildRedis() {
  // Prefer URL if provided
  if (REDIS_URL) {
    return new Redis(REDIS_URL, {
      lazyConnect: true,
      maxRetriesPerRequest: 1,
      enableOfflineQueue: false,
    });
  }

  return new Redis({
    host: REDIS_HOST,
    port: Number(REDIS_PORT),
    password: REDIS_PASSWORD || undefined,
    lazyConnect: true,
    maxRetriesPerRequest: 1,
    enableOfflineQueue: false,
  });
}

export const redis = buildRedis();

redis.on("connect", () => {
  console.log(`[Redis] connected (${NODE_ENV || "dev"})`);
});

redis.on("error", (err) => {
  // Don't crash the server for Redis errors in Phase-0 dual-write
  console.warn("[Redis] error:", err?.message || err);
});

export async function ensureRedisConnected() {
  try {
    if (redis.status !== "connecting" && redis.status !== "ready") {
      await redis.connect();
    }
    return true;
  } catch (e) {
    console.warn("[Redis] connect failed, continuing without Redis.", e?.message || e);
    return false;
  }
}
