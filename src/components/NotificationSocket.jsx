import { useEffect } from "react";
import { socket } from "../pages/socket";

export default function NotificationSocket() {
  useEffect(() => {
    // Request browser notification permission on mount
    if (typeof window !== "undefined" && "Notification" in window) {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          console.log("Notification permission granted");
        }
      });
    }

    // Request browser notification permission
    Notification.requestPermission().then((permission) => {
      if (permission === "granted") {
        console.log("Notification permission granted");
      }
    });

    socket.on("connect", () => {
      console.log("Connected to socket server");
    });

    socket.on("receive_notification", (data) => {
      new Notification(data.title, { body: data.message });
    });

    return () => {
      socket.disconnect();
    };
  }, []);

  return null; 
}
