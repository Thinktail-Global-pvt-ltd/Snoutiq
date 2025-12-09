import Redis from "ioredis";

const {
  REDIS_URL,
  REDIS_CONNECTION_STRING,
  UPSTASH_REDIS_URL,
  REDIS_HOST = "127.0.0.1",
  REDIS_PORT = "6379",
  REDIS_PASSWORD,
} = process.env;

const PRIMARY_REDIS_URL =
  REDIS_URL || REDIS_CONNECTION_STRING || UPSTASH_REDIS_URL || null;

const baseOptions = {
  maxRetriesPerRequest: 2,
  enableOfflineQueue: false,
  lazyConnect: false,
};

const createClient = (label) => {
  const config = PRIMARY_REDIS_URL
    ? [PRIMARY_REDIS_URL, baseOptions]
    : [
        {
          host: REDIS_HOST,
          port: Number(REDIS_PORT),
          password: REDIS_PASSWORD || undefined,
          ...baseOptions,
        },
      ];

  try {
    const client = new Redis(...config);

    client.on("error", (err) => {
      console.warn(`[redis:${label}] error`, err?.message || err);
    });

    client.on("connect", () => {
      console.log(`[redis:${label}] connected`);
    });

    return client;
  } catch (error) {
    console.warn(
      `[redis:${label}] failed to create client`,
      error?.message || error,
    );
    return null;
  }
};

export const redisPub = createClient("pub");
export const redisSub = createClient("sub");

export const publishRedis = async (channel, payload) => {
  if (!redisPub || !channel) return false;
  try {
    const message =
      typeof payload === "string" ? payload : JSON.stringify(payload || {});
    await redisPub.publish(channel, message);
    return true;
  } catch (error) {
    console.warn(
      "[redis] publish failed",
      channel,
      error?.message || error,
    );
    return false;
  }
};

export const subscribeRedis = (channel, handler) => {
  if (!redisSub || !channel || typeof handler !== "function") {
    return () => {};
  }

  try {
    const wrapped = (incomingChannel, message) => {
      if (incomingChannel !== channel) return;
      handler(message);
    };
    redisSub.subscribe(channel);
    redisSub.on("message", wrapped);
    console.log(`[redis] subscribed to ${channel}`);
    return () => {
      try {
        redisSub.off("message", wrapped);
        redisSub.unsubscribe(channel);
      } catch (_) {}
    };
  } catch (error) {
    console.warn(
      "[redis] subscribe failed",
      channel,
      error?.message || error,
    );
    return () => {};
  }
};

export const isRedisEnabled = () => Boolean(redisPub && redisSub);

export default {
  redisPub,
  redisSub,
  publishRedis,
  subscribeRedis,
  isRedisEnabled,
};
