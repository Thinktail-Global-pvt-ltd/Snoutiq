const axios = require("axios");
const { createServer } = require("http");
const { Server } = require("socket.io");
const admin = require("firebase-admin");
const { readFileSync } = require("fs");
const { join } = require("path");

// ==================== FIREBASE ADMIN SDK INITIALIZATION ====================
try {
  let serviceAccount;
  const serviceAccountPath =
    process.env.SERVICE_ACCOUNT_PATH ||
    join(__dirname, "../../snoutiqapp-9cacc4ece358.json");

  try {
    serviceAccount = JSON.parse(readFileSync(serviceAccountPath, "utf8"));
  } catch (error) {
    console.error("⚠️ Could not load service account key:", error.message);
    console.log(
      "💡 Please set SERVICE_ACCOUNT_PATH environment variable or place serviceAccountKey.json in the correct location",
    );
    serviceAccount = null;
  }

  if (serviceAccount) {
    admin.initializeApp({
      credential: admin.credential.cert(serviceAccount),
      databaseURL: "https://snoutiqapp-default-rtdb.firebaseio.com",
    });
    console.log("✅ Firebase Admin SDK initialized successfully");
  } else {
    console.log(
      "⚠️ Firebase Admin SDK not initialized - push notifications will fail",
    );
  }
} catch (error) {
  console.error("❌ Firebase Admin SDK initialization error:", error.message);
}

// ==================== HTTP SERVER ====================
const httpServer = createServer((req, res) => {
  try {
    const url = new URL(req.url, `http://${req.headers.host}`);

    if (req.method === "OPTIONS") {
      res.writeHead(204, {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type",
      });
      res.end();
      return;
    }

    if (req.method === "GET" && url.pathname === "/health") {
      res.writeHead(200, {
        "Content-Type": "application/json",
        "Access-Control-Allow-Origin": "*",
      });
      res.end(
        JSON.stringify({ status: "ok", timestamp: new Date().toISOString() }),
      );
      return;
    }

    if (req.method === "GET" && url.pathname === "/active-doctors") {
      const available = [];
      for (const [doctorId, info] of activeDoctors.entries()) {
        if (info.connectionStatus === "online" && !isDoctorBusy(doctorId)) {
          available.push({
            doctorId,
            status: "online",
            lastSeen: info.lastSeen,
          });
        }
      }

      res.writeHead(200, {
        "Content-Type": "application/json",
        "Access-Control-Allow-Origin": "*",
      });
      res.end(
        JSON.stringify({
          activeDoctors: available,
          count: available.length,
          updatedAt: new Date().toISOString(),
        }),
      );
      return;
    }

    res.writeHead(404, {
      "Content-Type": "application/json",
      "Access-Control-Allow-Origin": "*",
    });
    res.end(JSON.stringify({ error: "Not Found" }));
  } catch (error) {
    console.error("HTTP server error:", error);
    res.writeHead(500, {
      "Content-Type": "application/json",
      "Access-Control-Allow-Origin": "*",
    });
    res.end(JSON.stringify({ error: "Server error" }));
  }
});

// ==================== SOCKET.IO SETUP ====================
const io = new Server(httpServer, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
    credentials: false,
  },
  path: "/socket.io/",
  pingTimeout: 60000,
  pingInterval: 25000,
});

// ==================== IN-MEMORY STATE ====================
const activeDoctors = new Map();
const activeCalls = new Map();
const doctorPushTokens = new Map();
const sentCallNotifications = new Set();
const DOCTOR_GRACE_PERIOD_MS = 5 * 60 * 1000;

const getNotificationKey = (callId, doctorId) =>
  `${String(callId)}:${String(doctorId)}`;
const hasNotificationBeenSent = (callId, doctorId) =>
  sentCallNotifications.has(getNotificationKey(callId, doctorId));
const markNotificationSent = (callId, doctorId) => {
  sentCallNotifications.add(getNotificationKey(callId, doctorId));
};
const clearNotificationSent = (callId, doctorId) => {
  sentCallNotifications.delete(getNotificationKey(callId, doctorId));
};

// ==================== NOTIFICATION SERVICE ====================

/**
 * Enhanced: Store/update doctor's push token
 */
const storeDoctorPushToken = async (doctorId, token) => {
  try {
    if (!doctorId || !token) {
      console.warn("Invalid doctorId or token provided");
      return false;
    }

    doctorPushTokens.set(doctorId, token);
    console.log(`💾 Push token stored in memory for doctor ${doctorId}`);

    // TODO: Persist to database
    // await db.doctors.update({ id: doctorId }, {
    //   fcm_token: token,
    //   fcm_token_valid: true,
    //   fcm_token_updated_at: new Date()
    // });

    return true;
  } catch (error) {
    console.error(`Error storing push token for doctor ${doctorId}:`, error);
    return false;
  }
};

/**
 * Enhanced: Get doctor's push token with fallback
 */
const getDoctorPushToken = async (doctorId) => {
  try {
    let token = doctorPushTokens.get(doctorId);

    if (!token) {
      console.log(`⚠️ No cached token for doctor ${doctorId}`);

      // TODO: Fetch from database as fallback
      // token = await db.doctors.findById(doctorId).then(doc => doc?.fcm_token);

      if (token) {
        doctorPushTokens.set(doctorId, token);
        console.log(`✅ Token retrieved from database for doctor ${doctorId}`);
      }
    }

    if (!token) {
      console.log(`❌ No FCM token found for doctor ${doctorId}`);
      return null;
    }

    console.log(
      `✅ Found push token for doctor ${doctorId}: ${token.substring(0, 20)}...`,
    );
    return token;
  } catch (error) {
    console.error(`Error getting push token for doctor ${doctorId}:`, error);
    return null;
  }
};

/**
 * Helper: Mark token as invalid in database
 */
const invalidateDoctorToken = async (doctorId) => {
  try {
    // TODO: Implement database update
    // await db.doctors.update({ id: doctorId }, {
    //   fcm_token: null,
    //   fcm_token_valid: false
    // });
    console.log(`📊 Token invalidated for doctor ${doctorId}`);
  } catch (error) {
    console.error("Error invalidating token:", error);
  }
};

/**
 * Helper: Log notification success for analytics
 */
const logNotificationSuccess = async (doctorId, callId, response) => {
  try {
    // TODO: Implement database logging
    console.log(
      `📊 Notification success logged: doctor=${doctorId}, call=${callId}, messageId=${response}`,
    );
  } catch (error) {
    console.error("Error logging notification success:", error);
  }
};

/**
 * Helper: Log notification failure for debugging
 */
const logNotificationFailure = async (doctorId, callId, error) => {
  try {
    // TODO: Implement database logging
    console.log(
      `📊 Notification failure logged: doctor=${doctorId}, call=${callId}, error=${error.code}`,
    );
  } catch (err) {
    console.error("Error logging notification failure:", err);
  }
};

/**
 * Send Push Notification using Firebase Admin SDK
 * CRITICAL: All data values MUST be strings for FCM
 */
const sendPushNotification = async (doctorId, payload) => {
  try {
    const doctorPushToken = await getDoctorPushToken(doctorId);

    if (!doctorPushToken) {
      console.log(`⚠️ No push token found for doctor ${doctorId}`);
      return null;
    }

    if (!admin.apps.length) {
      console.log(
        "⚠️ Firebase Admin not initialized, cannot send push notification",
      );
      return null;
    }

    console.log(`📤 Sending FCM notification to doctor ${doctorId}`);
    console.log(`📦 Notification payload:`, JSON.stringify(payload, null, 2));

    // Build the message with ALL STRING VALUES (FCM requirement)
    const message = {
      notification: {
        title: "📞 Pending Video Call",
        body: payload.message,
      },
      data: {
        // CRITICAL: All values MUST be strings
        type: "pending_call",
        callId: String(payload.callId),
        channel: String(payload.channel),
        patientId: String(payload.patientId),
        doctorId: String(payload.doctorId),
        deepLink: String(payload.deepLink),
        timestamp: String(payload.timestamp),
        click_action: "OPEN_PENDING_CALL",
      },
      token: doctorPushToken,

      // Android-specific settings
      android: {
        priority: "high",
        ttl: 3600000, // 1 hour
        notification: {
          channelId: "calls",
          sound: "default",
          priority: "high",
          visibility: "public",
          defaultSound: true,
          defaultVibrateTimings: true,
          icon: "notification_icon",
          color: "#4F46E5",
          tag: `call_${payload.callId}`,
          sticky: true,
          clickAction: "OPEN_PENDING_CALL",
        },
      },

      // iOS-specific settings
      apns: {
        headers: {
          "apns-priority": "10",
          "apns-push-type": "alert",
        },
        payload: {
          aps: {
            sound: "default",
            badge: 1,
            alert: {
              title: "📞 Pending Video Call",
              body: payload.message,
            },
            "content-available": 1,
            "mutable-content": 1,
          },
        },
      },

      // Web push settings
      webpush: {
        notification: {
          title: "📞 Pending Video Call",
          body: payload.message,
          icon: "/icon-192x192.png",
          badge: "/badge-72x72.png",
          requireInteraction: true,
          actions: [
            { action: "join", title: "Join Call" },
            { action: "dismiss", title: "Dismiss" },
          ],
        },
        fcmOptions: {
          link: payload.deepLink,
        },
      },
    };

    const response = await admin.messaging().send(message);
    console.log(
      `✅ FCM notification sent successfully to doctor ${doctorId}:`,
      response,
    );

    await logNotificationSuccess(doctorId, payload.callId, response);

    return response;
  } catch (error) {
    console.error("❌ Push notification error:", error);
    console.error("📍 Error code:", error.code);
    console.error("📍 Error details:", error.message);

    if (
      error.code === "messaging/invalid-registration-token" ||
      error.code === "messaging/registration-token-not-registered"
    ) {
      console.log(
        `⚠️ Invalid token for doctor ${doctorId}, removing from cache`,
      );
      doctorPushTokens.delete(doctorId);
      await invalidateDoctorToken(doctorId);
    }

    await logNotificationFailure(doctorId, payload.callId, error);

    throw error;
  }
};

/**
 * Send WhatsApp notification (placeholder)
 */
const sendWhatsAppNotification = async (doctorId, payload) => {
  const WHATSAPP_API_URL = process.env.WHATSAPP_API_URL;

  if (!WHATSAPP_API_URL) {
    console.log(`⚠️ WhatsApp API not configured, skipping`);
    return null;
  }

  try {
    const doctorPhoneNumber = await getDoctorPhoneNumber(doctorId);

    if (!doctorPhoneNumber) {
      console.log(`⚠️ No phone number found for doctor ${doctorId}`);
      return null;
    }

    const response = await axios.post(
      WHATSAPP_API_URL,
      {
        to: doctorPhoneNumber,
        message: `📞 *Pending Video Call*\n\nPatient ${payload.patientId} is waiting for you.\n\nCall ID: ${payload.callId}\nChannel: ${payload.channel}\n\nOpen app to join: ${payload.deepLink}`,
      },
      {
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${process.env.WHATSAPP_API_TOKEN}`,
        },
        timeout: 5000,
      },
    );

    console.log(`✅ WhatsApp notification sent to doctor ${doctorId}`);
    return response.data;
  } catch (error) {
    console.error("WhatsApp error:", error.response?.data || error.message);
    throw error;
  }
};

/**
 * Send webhook notification (placeholder)
 */
const sendWebhookNotification = async (doctorId, payload) => {
  const WEBHOOK_URL = process.env.WEBHOOK_URL;

  if (!WEBHOOK_URL) {
    console.log(`⚠️ Webhook URL not configured, skipping`);
    return null;
  }

  try {
    const response = await axios.post(
      WEBHOOK_URL,
      {
        event: "doctor_pending_call",
        doctorId,
        data: payload,
      },
      {
        headers: {
          "Content-Type": "application/json",
          "X-Webhook-Secret": process.env.WEBHOOK_SECRET,
        },
        timeout: 5000,
      },
    );

    console.log(`✅ Webhook notification sent for doctor ${doctorId}`);
    return response.data;
  } catch (error) {
    console.error("Webhook error:", error.response?.data || error.message);
    throw error;
  }
};

/**
 * Helper: Get doctor's phone number (placeholder)
 */
const getDoctorPhoneNumber = async (doctorId) => {
  // TODO: Implement actual database call
  // const doctor = await db.doctors.findById(doctorId);
  // return doctor?.phoneNumber;

  console.log(`⚠️ getDoctorPhoneNumber not implemented for doctor ${doctorId}`);
  return null;
};

/**
 * Notify doctor about pending call via FCM, WhatsApp, and Webhook
 */
const notifyDoctorPendingCall = async (callData) => {
  const { callId, doctorId, patientId, channel } = callData;

  if (hasNotificationBeenSent(callId, doctorId)) {
    console.log(
      `[notify] Duplicate notification suppressed for call ${callId} (doctor ${doctorId})`,
    );
    return;
  }

  console.log(
    `[notify] Notifying doctor ${doctorId} about pending call ${callId}`,
  );

  const notificationPayload = {
    callId,
    doctorId,
    patientId,
    channel,
    timestamp: new Date().toISOString(),
    type: "pending_call",
    message: `Patient ${patientId} is waiting for you. Join the call now!`,
    deepLink: `snoutiq://call/${callId}?channel=${channel}&patientId=${patientId}`,
  };

  markNotificationSent(callId, doctorId);

  const promises = [];

  promises.push(
    sendPushNotification(doctorId, notificationPayload).catch((error) => {
      console.error("Push notification error:", error.message);
      return null;
    }),
  );

  promises.push(
    sendWhatsAppNotification(doctorId, notificationPayload).catch((error) => {
      console.error("WhatsApp notification error:", error.message);
      return null;
    }),
  );

  promises.push(
    sendWebhookNotification(doctorId, notificationPayload).catch((error) => {
      console.error("Webhook notification error:", error.message);
      return null;
    }),
  );

  const results = await Promise.allSettled(promises);
  const anySuccess = results.some(
    (result) => result.status === "fulfilled" && result.value,
  );

  if (!anySuccess) {
    clearNotificationSent(callId, doctorId);
  }

  console.log(
    `[notify] Notification attempts completed for doctor ${doctorId}`,
  );
};

// ==================== HELPERS ====================
const isDoctorBusy = (doctorId) => {
  for (const [, call] of activeCalls.entries()) {
    if (
      call.doctorId === doctorId &&
      ["requested", "accepted", "payment_completed", "active"].includes(
        call.status,
      )
    ) {
      return true;
    }
  }
  return false;
};

const emitActiveDoctors = () => {
  const available = [];
  const allOnline = [];

  for (const [doctorId, info] of activeDoctors.entries()) {
    if (info.connectionStatus === "online") {
      allOnline.push(doctorId);
      if (!isDoctorBusy(doctorId)) {
        available.push(doctorId);
      }
    }
  }

  console.log(
    `📤 Broadcasting ${available.length} available doctors (${allOnline.length} online total)`,
  );
  io.emit("active-doctors", available);
  io.emit("live-doctors", allOnline);
};

// ==================== SOCKET.IO EVENT HANDLERS ====================
io.on("connection", (socket) => {
  console.log(`⚡ Client connected: ${socket.id}`);

  // ========== DOCTOR JOINS ==========
  socket.on("join-doctor", (doctorId) => {
    doctorId = Number(doctorId);
    const roomName = `doctor-${doctorId}`;
    socket.join(roomName);

    activeDoctors.set(doctorId, {
      socketId: socket.id,
      joinedAt: new Date(),
      lastSeen: new Date(),
      connectionStatus: "online",
    });

    console.log(`✅ Doctor ${doctorId} is now ONLINE (socket: ${socket.id})`);

    socket.emit("doctor-online", {
      doctorId,
      status: "online",
      timestamp: new Date().toISOString(),
    });

    emitActiveDoctors();

    // Check for pending calls
    for (const [callId, call] of activeCalls.entries()) {
      if (call.doctorId === doctorId && call.status === "waiting") {
        console.log(
          `📨 Delivering pending call ${callId} to doctor ${doctorId}`,
        );

        call.status = "requested";
        call.doctorSocketId = socket.id;
        activeCalls.set(callId, call);

        socket.emit("call-requested", {
          callId: call.callId,
          doctorId: call.doctorId,
          patientId: call.patientId,
          channel: call.channel,
          timestamp: new Date().toISOString(),
        });
      }
    }
  });

  // ========== DOCTOR HEARTBEAT ==========
  socket.on("doctor-heartbeat", (payload) => {
    const doctorId = Number(payload.doctorId || payload.id);
    if (!doctorId) return;

    const doctor = activeDoctors.get(doctorId);
    if (doctor) {
      doctor.lastSeen = new Date();
      doctor.connectionStatus = "online";
      activeDoctors.set(doctorId, doctor);
    }
  });

  // ========== REGISTER PUSH TOKEN (ENHANCED) ==========
  socket.on("register-push-token", async ({ doctorId, pushToken }) => {
    doctorId = Number(doctorId);

    if (!doctorId || !pushToken) {
      console.log(
        `⚠️ Invalid push token registration: doctorId=${doctorId}, token=${pushToken ? "present" : "missing"}`,
      );
      socket.emit("push-token-registered", {
        success: false,
        doctorId,
        message: "Invalid doctorId or token",
      });
      return;
    }

    console.log(`📱 Registering push token for doctor ${doctorId}`);
    console.log(`🔑 Token: ${pushToken.substring(0, 30)}...`);

    const stored = await storeDoctorPushToken(doctorId, pushToken);

    if (stored) {
      console.log(
        `✅ Push token registered successfully for doctor ${doctorId}`,
      );

      socket.emit("push-token-registered", {
        success: true,
        doctorId,
        message: "Push token registered successfully",
        timestamp: new Date().toISOString(),
      });

      // Optional: Send test notification
      if (process.env.SEND_TEST_NOTIFICATION === "true") {
        console.log(`🧪 Sending test notification to doctor ${doctorId}`);
        await sendPushNotification(doctorId, {
          callId: "test_" + Date.now(),
          doctorId: doctorId,
          patientId: "test",
          channel: "test_channel",
          timestamp: new Date().toISOString(),
          type: "test_notification",
          message: "Test notification - your FCM token is working!",
          deepLink: `snoutiq://test`,
        }).catch((err) => console.error("Test notification failed:", err));
      }
    } else {
      console.error(`❌ Failed to register push token for doctor ${doctorId}`);

      socket.emit("push-token-registered", {
        success: false,
        doctorId,
        message: "Failed to store push token",
      });
    }
  });

  // ========== GET ACTIVE DOCTORS ==========
  socket.on("get-active-doctors", () => {
    const available = [];
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.connectionStatus === "online" && !isDoctorBusy(doctorId)) {
        available.push(doctorId);
      }
    }
    console.log(`📊 Sending ${available.length} active doctors to client`);
    socket.emit("active-doctors", available);
  });

  // ========== PATIENT INITIATES CALL ==========
  socket.on("call-requested", ({ doctorId, patientId, channel, callId }) => {
    doctorId = Number(doctorId);

    if (!callId) {
      callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    }

    console.log(`📞 Call Request: Patient ${patientId} → Doctor ${doctorId}`);
    console.log(`   Channel: ${channel}, CallID: ${callId}`);

    const doctor = activeDoctors.get(doctorId);

    const callSession = {
      callId,
      doctorId,
      patientId,
      channel,
      status:
        doctor && doctor.connectionStatus === "online"
          ? "requested"
          : "waiting",
      createdAt: new Date(),
      patientSocketId: socket.id,
      doctorSocketId: doctor?.socketId || null,
    };

    activeCalls.set(callId, callSession);

    if (doctor && doctor.connectionStatus === "online") {
      console.log(
        `✅ Doctor ${doctorId} is online - sending call request (socket + push)`,
      );

      io.to(`doctor-${doctorId}`).emit("call-requested", {
        callId,
        doctorId,
        patientId,
        channel,
        timestamp: new Date().toISOString(),
      });

      // Also send a push notification so the doctor gets notified if app is backgrounded
      notifyDoctorPendingCall({ callId, doctorId, patientId, channel }).catch(
        () => {},
      );

      socket.emit("call-sent", {
        callId,
        doctorId,
        patientId,
        channel,
        status: "sent",
        message: "Doctor notified - waiting for response",
        timestamp: new Date().toISOString(),
      });
    } else {
      console.log(
        `⏳ Doctor ${doctorId} is offline - queueing call and sending FCM`,
      );

      notifyDoctorPendingCall({
        callId,
        doctorId,
        patientId,
        channel,
      });

      socket.emit("call-sent", {
        callId,
        doctorId,
        patientId,
        channel,
        status: "waiting",
        message:
          "Doctor is offline. They will be notified via push notification.",
        timestamp: new Date().toISOString(),
      });
    }

    emitActiveDoctors();
  });

  // ========== DOCTOR ACCEPTS CALL ==========
  socket.on("call-accepted", (data) => {
    const { callId, doctorId, patientId, channel } = data;
    console.log(`✅ Call ${callId} accepted by doctor ${doctorId}`);
    clearNotificationSent(callId, doctorId);

    const call = activeCalls.get(callId);
    if (!call) {
      console.log(`❌ Call ${callId} not found`);
      return;
    }

    call.doctorSocketId = socket.id;
    call.status = "accepted";
    call.acceptedAt = new Date();
    activeCalls.set(callId, call);

    console.log(
      `📝 Updated call ${callId} with doctor socket ID: ${socket.id}`,
    );

    if (call.patientSocketId) {
      io.to(call.patientSocketId).emit("call-accepted", {
        callId,
        doctorId,
        patientId,
        channel,
        requiresPayment: true,
        message: "Doctor accepted! Please complete payment.",
        timestamp: new Date().toISOString(),
      });
    }

    emitActiveDoctors();
  });

  // ========== DOCTOR REJECTS CALL ==========
  socket.on("call-rejected", (data) => {
    const { callId, doctorId, reason = "rejected" } = data;
    console.log(`? Call ${callId} rejected by doctor ${doctorId}`);
    clearNotificationSent(callId, doctorId);

    const call = activeCalls.get(callId);
    if (!call) return;

    call.status = "rejected";
    call.rejectedAt = new Date();

    if (call.patientSocketId) {
      io.to(call.patientSocketId).emit("call-rejected", {
        callId,
        doctorId,
        patientId: call.patientId,
        reason,
        message: "Doctor is unavailable. Please try another doctor.",
        timestamp: new Date().toISOString(),
      });
    }

    setTimeout(() => {
      activeCalls.delete(callId);
      emitActiveDoctors();
    }, 10000);
  });

  // ========== PAYMENT COMPLETED ==========
  socket.on("payment-completed", (data) => {
    const { callId, patientId, doctorId, channel, paymentId } = data;
    console.log(
      `💰 Payment completed for call ${callId} by patient ${patientId}`,
    );

    const call = activeCalls.get(callId);
    if (!call) {
      console.log(`❌ Call ${callId} not found in activeCalls`);
      return;
    }

    call.status = "payment_completed";
    call.paymentId = paymentId;
    call.paidAt = new Date();
    activeCalls.set(callId, call);

    socket.emit("payment-verified", {
      callId,
      channel,
      patientId,
      doctorId,
      status: "ready_to_connect",
      message: "Payment successful! Connecting to video call...",
      role: "audience",
      uid: Number(patientId),
      timestamp: new Date().toISOString(),
    });

    const patientPaidData = {
      callId,
      channel,
      patientId,
      doctorId,
      paymentId,
      status: "ready_to_connect",
      message: "Patient paid! Join the video call now.",
      role: "host",
      uid: Number(doctorId),
      timestamp: new Date().toISOString(),
    };

    if (call.doctorSocketId) {
      console.log(
        `📤 Sending patient-paid to doctor socket: ${call.doctorSocketId}`,
      );
      io.to(call.doctorSocketId).emit("patient-paid", patientPaidData);
    } else {
      console.log(
        `📤 Doctor socket ID not found, using room: doctor-${doctorId}`,
      );
      io.to(`doctor-${doctorId}`).emit("patient-paid", patientPaidData);
    }

    emitActiveDoctors();
  });

  // ========== CALL STARTED ==========
  socket.on("call-started", ({ callId, userId, role }) => {
    console.log(`📹 User ${userId} (${role}) joined call ${callId}`);

    const call = activeCalls.get(callId);
    if (call) {
      call.status = "active";
      if (role === "host") call.doctorJoinedAt = new Date();
      if (role === "audience") call.patientJoinedAt = new Date();
      activeCalls.set(callId, call);
    }

    emitActiveDoctors();
  });

  // ========== CALL ENDED ==========
  socket.on("call-ended", ({ callId, userId, role }) => {
    console.log("call-ended event received");
    console.log(`Call ${callId} ended by ${userId} (${role})`);

    const call = activeCalls.get(callId);
    if (!call) return;

    clearNotificationSent(callId, call.doctorId);

    call.status = "ended";
    call.endedAt = new Date();
    call.endedBy = userId;

    if (role === "host" && call.patientSocketId) {
      io.to(call.patientSocketId).emit("call-ended-by-other", {
        callId,
        endedBy: "doctor",
        message: "Doctor ended the call",
        timestamp: new Date().toISOString(),
      });
    } else if (role === "audience" && call.doctorSocketId) {
      io.to(call.doctorSocketId).emit("call-ended-by-other", {
        callId,
        endedBy: "patient",
        message: "Patient ended the call",
        timestamp: new Date().toISOString(),
      });
    }

    setTimeout(() => {
      activeCalls.delete(callId);
      emitActiveDoctors();
    }, 10000);
  });

  // ========== DOCTOR LEAVES ==========
  socket.on("leave-doctor", (doctorId) => {
    doctorId = Number(doctorId);
    console.log(`👋 Doctor ${doctorId} going offline`);

    activeDoctors.delete(doctorId);
    socket.leave(`doctor-${doctorId}`);

    socket.emit("doctor-offline", {
      doctorId,
      status: "offline",
      timestamp: new Date().toISOString(),
    });

    emitActiveDoctors();
  });

  // ========== DISCONNECT ==========
  socket.on("disconnect", (reason) => {
    console.log(`❌ Client disconnected: ${socket.id}, reason: ${reason}`);

    // Check if this was a doctor
    for (const [doctorId, info] of activeDoctors.entries()) {
      if (info.socketId === socket.id) {
        console.log(
          `🔌 Doctor ${doctorId} disconnected - entering grace period`,
        );

        info.connectionStatus = "grace";
        info.disconnectedAt = new Date();

        // Remove after grace period if not reconnected
        setTimeout(() => {
          const current = activeDoctors.get(doctorId);
          if (
            current &&
            current.socketId === socket.id &&
            current.connectionStatus === "grace"
          ) {
            activeDoctors.delete(doctorId);
            console.log(`⏰ Doctor ${doctorId} grace period expired - removed`);
            emitActiveDoctors();
          }
        }, DOCTOR_GRACE_PERIOD_MS);

        emitActiveDoctors();
        break;
      }
    }

    // Handle active calls
    for (const [callId, call] of activeCalls.entries()) {
      if (
        call.patientSocketId === socket.id ||
        call.doctorSocketId === socket.id
      ) {
        const isDoctor = call.doctorSocketId === socket.id;

        // Only notify if call was actually active
        const wasCallActive =
          call.status === "active" ||
          (call.doctorJoinedAt && call.patientJoinedAt);

        call.status = "disconnected";
        call.disconnectedAt = new Date();

        if (wasCallActive) {
          const otherSocketId = isDoctor
            ? call.patientSocketId
            : call.doctorSocketId;
          if (otherSocketId) {
            console.log(
              `🔌 Notifying ${isDoctor ? "patient" : "doctor"} about disconnection`,
            );
            io.to(otherSocketId).emit("other-party-disconnected", {
              callId,
              disconnectedBy: isDoctor ? "doctor" : "patient",
              message: `${isDoctor ? "Doctor" : "Patient"} disconnected`,
              timestamp: new Date().toISOString(),
            });
          }
        }

        // Cleanup after 30 seconds
        setTimeout(() => {
          activeCalls.delete(callId);
          emitActiveDoctors();
        }, 30000);
      }
    }
  });
});

// ==================== PERIODIC CLEANUP ====================
setInterval(() => {
  const now = Date.now();
  let removedCount = 0;

  for (const [doctorId, info] of activeDoctors.entries()) {
    const lastSeen = info.lastSeen ? new Date(info.lastSeen).getTime() : 0;
    if (now - lastSeen > DOCTOR_GRACE_PERIOD_MS * 2) {
      console.log(
        `⏰ Removing stale doctor ${doctorId} (last seen: ${new Date(lastSeen).toISOString()})`,
      );
      activeDoctors.delete(doctorId);
      removedCount++;
    }
  }

  if (removedCount > 0) {
    console.log(`🧹 Cleanup: Removed ${removedCount} stale doctor(s)`);
    emitActiveDoctors();
  }
}, 60000); // Run every minute

// ==================== START SERVER ====================
const PORT = process.env.PORT || 5000;
httpServer.listen(PORT, "0.0.0.0", () => {
  console.log(`🚀 Socket.IO server running on port ${PORT}`);
  console.log(`📍 Health check: http://localhost:${PORT}/health`);
  console.log(`📍 Active doctors: http://localhost:${PORT}/active-doctors`);
  console.log(
    `🔥 Firebase Admin initialized: ${admin.apps.length > 0 ? "YES" : "NO"}`,
  );
  console.log(
    `📱 Push notifications: ${admin.apps.length > 0 ? "ENABLED" : "DISABLED"}`,
  );
  console.log(`⏰ Doctor grace period: ${DOCTOR_GRACE_PERIOD_MS / 1000}s`);
  console.log(`🌐 Environment: ${process.env.NODE_ENV || "development"}`);
});
