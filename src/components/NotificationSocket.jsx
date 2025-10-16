import { useEffect } from "react";
import { io } from "socket.io-client";

export default function NotificationSocket() {
  useEffect(() => {
    // -------------------- SOCKET.IO --------------------
    const socket = io("http://localhost:4000", {
      withCredentials: true, // important for credentials
    });

    socket.on("connect", () => {
      console.log("✅ Connected to socket server:", socket.id);
    });

    socket.on("receive_notification", (data) => {
      // Safely show notification
      if ("Notification" in window) {
        if (Notification.permission === "granted") {
          new Notification(data.title, { body: data.message });
        } else if (Notification.permission !== "denied") {
          Notification.requestPermission().then((permission) => {
            if (permission === "granted") {
              new Notification(data.title, { body: data.message });
            }
          });
        }
      } else {
        console.warn("⚠️ Notifications not supported in this browser.");
      }
    });

    // Cleanup on unmount
    return () => {
      socket.disconnect();
    };
  }, []);

  return null;
}
