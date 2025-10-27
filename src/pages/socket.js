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

// ✅ ENHANCED: Notification listener with better error handling
socket.on("receive_notification", (data) => {
  if (typeof window !== "undefined" && "Notification" in window) {
    if (Notification.permission === "granted") {
      try {
        new Notification(data.title, { 
          body: data.message,
          icon: '/favicon.ico',
          tag: data.id || 'snoutiq-notification'
        });
      } catch (error) {
        console.error("Failed to show notification:", error);
      }
    } else {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          try {
            new Notification(data.title, { 
              body: data.message,
              icon: '/favicon.ico',
              tag: data.id || 'snoutiq-notification'
            });
          } catch (error) {
            console.error("Failed to show notification:", error);
          }
        }
      }).catch(error => {
        console.error("Failed to request notification permission:", error);
      });
    }
  }
});

// ✅ ENHANCED: Call status update listener
socket.on("call-status-update", (data) => {
  console.log("📊 Call status update:", data);
  
  // Handle different call statuses
  switch (data.status) {
    case 'ended':
      console.log(`🔚 Call ${data.callId} ended by ${data.endedBy}`);
      break;
    case 'disconnected':
      console.log(`🔌 Call ${data.callId} disconnected by ${data.disconnectedBy}`);
      break;
    case 'rejected':
      console.log(`❌ Call ${data.callId} rejected by ${data.rejectedBy}`);
      break;
    default:
      console.log(`📊 Call ${data.callId} status: ${data.status}`);
  }
});

// Helper to get connection info
export const getConnectionInfo = () => ({
  url: socketUrl,
  environment: isDevelopment ? "development" : "production",
  connected: socket.connected,
  id: socket.id,
});
