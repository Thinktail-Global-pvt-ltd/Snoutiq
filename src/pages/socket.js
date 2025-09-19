import { io } from "socket.io-client";

const isLocal = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
export const socket = io(isLocal ? "http://localhost:4000" : "https://snoutiq.com", {
  path: "/socket.io/",
  transports: ["websocket", "polling"],
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionAttempts: 5,
  timeout: 20000,
});


// Connection event handlers
socket.on("connect", () => {
  console.log("✅ Connected to server:", socket.id);
});

socket.on("disconnect", () => {
  console.log("❌ Disconnected from server");
});

socket.on("connect_error", (error) => {
  console.error("❌ Connection error:", error.message);
});
