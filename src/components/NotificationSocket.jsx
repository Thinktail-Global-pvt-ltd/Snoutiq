import { useEffect } from "react";
import { io } from "socket.io-client";

export default function NotificationSocket() {
  useEffect(() => {
    // Socket server connect
    const socket = io("http://localhost:4000");

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
