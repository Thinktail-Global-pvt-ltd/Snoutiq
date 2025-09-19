export const AGORA_CONFIG = {
  APP_ID: process.env.REACT_APP_AGORA_APP_ID || "e20a4d60afd8494eab490563ad2e61d1",
  CHANNEL_PREFIX: "telemedicine_",
  VIDEO_PROFILE: "720p_1",
  AUDIO_PROFILE: "music_standard"
};

// Socket events
export const SOCKET_EVENTS = {
  // Doctor events
  JOIN_DOCTOR: "join-doctor",
  LEAVE_DOCTOR: "leave-doctor",
  DOCTOR_ONLINE: "doctor-online",
  
  // Call events
  CALL_REQUESTED: "call-requested",
  CALL_ACCEPTED: "call-accepted", 
  CALL_REJECTED: "call-rejected",
  CALL_ENDED: "call-ended",
  CALL_SENT: "call-sent",
  
  // Utility events
  GET_ACTIVE_DOCTORS: "get-active-doctors",
  ACTIVE_DOCTORS: "active-doctors"
};

// User roles
export const USER_ROLES = {
  DOCTOR: "host",
  PATIENT: "audience"
};

// Call status
export const CALL_STATUS = {
  PENDING: "pending",
  ACCEPTED: "accepted", 
  REJECTED: "rejected",
  CONNECTED: "connected",
  ENDED: "ended"
};