// // socket-server.js
// import { createServer } from "http";
// import { Server } from "socket.io";

// const httpServer = createServer();

// const io = new Server(httpServer, {
//   cors: {
//     origin: "*",  // testing ke liye; baad me sirf snoutiq.com set karo
//     methods: ["GET", "POST"],
//   },
//   path: "/socket.io/",   // 👈 MUST MATCH Apache Proxy path
// });

// io.on("connection", (socket) => {
//   console.log("✅ Client connected:", socket.id);

//   socket.on("call:request", (data) => {
//     console.log("📞 Call request:", data);
//     io.emit("call:incoming", data);
//   });

//   socket.on("disconnect", () => {
//     console.log("❌ Client disconnected:", socket.id);
//   });
// });

// httpServer.listen(4000, () => {
//   console.log("🚀 Socket.IO server running on http://127.0.0.1:4000");
// });
// Fixed server.js
// Minimal server.js for testing doctor-online event
import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer();

const io = new Server(httpServer, {
  cors: {
    origin: [
      "http://localhost:3000", 
      "http://127.0.0.1:3000",
      "https://snoutiq.com"  // Add your live domain
    ],
    methods: ["GET", "POST"],
    credentials: true
  },
  path: "/socket.io/",
});

// Simple storage
const activeDoctors = new Map();

io.on("connection", (socket) => {
  console.log(`✅ Client connected: ${socket.id}`);

  // Doctor joins room
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
    console.log(`📝 Stored doctor ${doctorId} in activeDoctors map`);
    
    // IMMEDIATELY emit doctor-online event back to the client
    console.log(`📤 Emitting doctor-online event to socket ${socket.id}`);
    socket.emit("doctor-online", { 
      doctorId: doctorId, 
      status: "online",
      timestamp: new Date().toISOString(),
      socketId: socket.id
    });
    
    console.log(`✅ doctor-online event sent for doctorId: ${doctorId}`);
    
    // Also emit to the room (in case of multiple connections)
    console.log(`📤 Emitting doctor-online event to room: ${roomName}`);
    io.to(roomName).emit("doctor-online", { 
      doctorId: doctorId, 
      status: "online",
      timestamp: new Date().toISOString(),
      socketId: socket.id
    });
  });

  // Test server status
  socket.on("get-server-status", () => {
    console.log(`📊 get-server-status requested by ${socket.id}`);
    socket.emit("server-status", {
      connected: true,
      activeDoctors: activeDoctors.size,
      doctorsList: Array.from(activeDoctors.keys()),
      uptime: process.uptime(),
      timestamp: new Date().toISOString()
    });
  });

  // Get active doctors list
  socket.on("get-active-doctors", () => {
    console.log(`📋 get-active-doctors requested by ${socket.id}`);
    socket.emit("active-doctors", Array.from(activeDoctors.keys()));
  });

  // Ping-pong test
  socket.on("ping", (data) => {
    console.log(`🏓 ping received from ${socket.id}:`, data);
    socket.emit("pong", {
      ...data,
      serverTime: new Date().toISOString(),
      receivedAt: Date.now()
    });
  });

  // Patient requests call
  socket.on("call-requested", ({ doctorId, patientId, channel }) => {
    console.log(`📞 call-requested: Patient ${patientId} → Doctor ${doctorId}`);
    
    if (!activeDoctors.has(doctorId)) {
      console.log(`❌ Doctor ${doctorId} not found in active doctors`);
      socket.emit("call-failed", {
        error: "Doctor not available",
        doctorId,
        patientId
      });
      return;
    }
    
    const callId = `${patientId}-${doctorId}-${Date.now()}`;
    const roomName = `doctor-${doctorId}`;
    
    console.log(`📤 Sending call-requested to room: ${roomName}`);
    io.to(roomName).emit("call-requested", {
      callId,
      doctorId,
      patientId,
      channel,
      timestamp: new Date().toISOString()
    });
    
    // Confirm to patient
    socket.emit("call-sent", {
      callId,
      doctorId,
      patientId,
      channel,
      status: "sent"
    });
  });

  // Call accepted
  socket.on("call-accepted", (data) => {
    console.log(`✅ call-accepted:`, data);
    io.emit("call-accepted", {
      ...data,
      timestamp: new Date().toISOString()
    });
  });

  // Call rejected
  socket.on("call-rejected", (data) => {
    console.log(`❌ call-rejected:`, data);
    io.emit("call-rejected", {
      ...data,
      timestamp: new Date().toISOString()
    });
  });

  // Doctor leaves
  socket.on("leave-doctor", (doctorId) => {
    console.log(`🚪 leave-doctor: ${doctorId}`);
    const roomName = `doctor-${doctorId}`;
    socket.leave(roomName);
    activeDoctors.delete(doctorId);
    
    socket.emit("doctor-offline", {
      doctorId,
      status: "offline",
      timestamp: new Date().toISOString()
    });
  });

  // Disconnect cleanup
  socket.on("disconnect", (reason) => {
    console.log(`❌ Client disconnected: ${socket.id}, reason: ${reason}`);
    
    // Find and remove doctor if they were registered
    for (const [doctorId, doctorInfo] of activeDoctors.entries()) {
      if (doctorInfo.socketId === socket.id) {
        console.log(`🧹 Removing disconnected doctor ${doctorId} from active list`);
        activeDoctors.delete(doctorId);
        break;
      }
    }
  });

  // Log all incoming events for debugging
  socket.onAny((eventName, ...args) => {
    if (eventName !== 'ping' && eventName !== 'pong') {
      console.log(`📡 Event received: ${eventName}`, args);
    }
  });
});

// Log server stats every 30 seconds
setInterval(() => {
  console.log(`📊 Server Stats - Connected: ${io.engine.clientsCount}, Active Doctors: ${activeDoctors.size}`);
  if (activeDoctors.size > 0) {
    console.log(`👨‍⚕️ Active Doctors:`, Array.from(activeDoctors.keys()));
  }
}, 30000);

const PORT = process.env.PORT || 4000;
httpServer.listen(PORT, () => {
  console.log(`🚀 Test Socket.IO server running on port ${PORT}`);
  console.log(`📡 Ready for doctor connections...`);
});