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
  console.log(`🏥 join-doctor event received for doctorId: ${doctorId}`);
  
  const roomName = `doctor-${doctorId}`;
  
  // Join the room
  socket.join(roomName);
  console.log(`👨‍⚕️ Socket ${socket.id} joined room: ${roomName}`);
  
  // Store doctor info
  activeDoctors.set(doctorId, {
    socketId: socket.id,
    joinedAt: new Date()
  });
  
  // ONLY emit to the specific socket that joined
  console.log(`📤 Emitting doctor-online event to socket ${socket.id}`);
  socket.emit("doctor-online", { 
    doctorId: doctorId, 
    status: "online",
    timestamp: new Date().toISOString(),
    socketId: socket.id
  });
  
  console.log(`✅ doctor-online event sent for doctorId: ${doctorId}`);
  
  // Remove the room broadcast as it's not needed
  // io.to(roomName).emit("doctor-online", { ... });
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
