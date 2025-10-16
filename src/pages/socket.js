import { io } from "socket.io-client";

// Development vs Production check
const isDevelopment =
  window.location.hostname === "localhost" ||
  window.location.hostname === "127.0.0.1" ||
  window.location.hostname === "0.0.0.0";

const SOCKET_URLS = {
  development: "http://localhost:4000",
  production: window.location.origin, // Apache/Nginx proxy karega
};

const socketUrl = isDevelopment ? SOCKET_URLS.development : SOCKET_URLS.production;

export const socket = io(socketUrl, {
  path: "/socket.io/",
  transports: ["websocket", "polling"],
  withCredentials: true,
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionAttempts: 10,
  timeout: 20000,
  forceNew: true,
});

// Debug logs
socket.on("connect", () => {
  console.log("✅ Connected to server:", socket.id);
  console.log("🌐 Connected to:", socketUrl);
});

socket.on("disconnect", (reason) => {
  console.log("❌ Disconnected from server. Reason:", reason);
});

socket.on("connect_error", (error) => {
  console.error("❌ Connection error:", error.message);
});

socket.on("reconnect", (attemptNumber) => {
  console.log("🔄 Reconnected after", attemptNumber, "attempts");
});

socket.on("reconnect_attempt", (attemptNumber) => {
  console.log("🔄 Reconnection attempt", attemptNumber);
});

socket.on("reconnect_error", (error) => {
  console.error("❌ Reconnection failed:", error.message);
});

socket.on("reconnect_failed", () => {
  console.error("❌ Failed to reconnect after maximum attempts");
});

// Notification listener
socket.on("receive_notification", (data) => {
  if (typeof window !== "undefined" && "Notification" in window) {
    if (Notification.permission === "granted") {
      new Notification(data.title, { body: data.message });
    } else {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          new Notification(data.title, { body: data.message });
        }
      });
    }
  }
});

// Helper to get connection info
export const getConnectionInfo = () => ({
  url: socketUrl,
  environment: isDevelopment ? "development" : "production",
  connected: socket.connected,
  id: socket.id,
});
