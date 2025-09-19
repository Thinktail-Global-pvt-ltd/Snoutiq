import { io } from "socket.io-client";

// Determine if we're in development or production
const isDevelopment = window.location.hostname === "localhost" || 
                     window.location.hostname === "127.0.0.1" ||
                     window.location.hostname === "0.0.0.0";

// Socket server URLs
const SOCKET_URLS = {
  development: "http://localhost:4000",
  production: "https://your-socket-server.railway.app" // Replace with your deployed server URL
};

const socketUrl = isDevelopment ? SOCKET_URLS.development : SOCKET_URLS.production;

export const socket = io(socketUrl, {
  path: "/socket.io/",
  transports: ["websocket", "polling"],
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionAttempts: 10,
  timeout: 20000,
  forceNew: true
});

// Enhanced connection event handlers with better error handling
socket.on("connect", () => {
  console.log("✅ Connected to server:", socket.id);
  console.log("🌐 Connected to:", socketUrl);
});

socket.on("disconnect", (reason) => {
  console.log("❌ Disconnected from server. Reason:", reason);
});

socket.on("connect_error", (error) => {
  console.error("❌ Connection error:", error.message);
  console.error("🔍 Trying to connect to:", socketUrl);
  console.error("🛠️ Environment:", isDevelopment ? "Development" : "Production");
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

// Export connection info for debugging
export const getConnectionInfo = () => ({
  url: socketUrl,
  environment: isDevelopment ? "development" : "production",
  connected: socket.connected,
  id: socket.id
});