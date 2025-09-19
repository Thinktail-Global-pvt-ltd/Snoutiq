// socket-server.js
import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer();

const io = new Server(httpServer, {
  cors: {
    origin: "*",  // testing ke liye; baad me sirf snoutiq.com set karo
    methods: ["GET", "POST"],
  },
  path: "/socket.io/",   // 👈 MUST MATCH Apache Proxy path
   transports: ["polling", "websocket"],
});

io.on("connection", (socket) => {
  console.log("✅ Client connected:", socket.id);

  socket.on("call:request", (data) => {
    console.log("📞 Call request:", data);
    io.emit("call:incoming", data);
  });

  socket.on("disconnect", () => {
    console.log("❌ Client disconnected:", socket.id);
  });
});

httpServer.listen(4000, () => {
  console.log("🚀 Socket.IO server running on http://127.0.0.1:4000");
});
