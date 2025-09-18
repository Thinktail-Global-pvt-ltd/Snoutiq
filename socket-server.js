// socket-server.js
import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer();
const io = new Server(httpServer, {
  cors: {
    origin: "*", // apne frontend domain set karo
    methods: ["GET", "POST"],
  },
});

io.on("connection", (socket) => {
  console.log("✅ Client connected:", socket.id);

  // Doctor channel join
  socket.on("join-doctor", (doctorId) => {
    socket.join(`doctor-${doctorId}`);
    console.log(`Doctor ${doctorId} joined room`);
  });

  // Patient se call request
  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    io.to(`doctor-${doctorId}`).emit("call-requested", {
      doctorId,
      patientId,
      channel,
    });
    console.log("📞 Call requested:", doctorId, patientId);
  });

  socket.on("disconnect", () => {
    console.log("❌ Client disconnected:", socket.id);
  });
});

httpServer.listen(4000, () => {
  console.log("🚀 Socket.IO server running on http://localhost:4000");
});
