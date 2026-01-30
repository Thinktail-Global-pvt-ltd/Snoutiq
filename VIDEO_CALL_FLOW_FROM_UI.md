# Video Call Flow (CallLab.jsx + DoctorReceiver.jsx)

## Scope
Yeh doc CallLab (patient-side test UI) aur DoctorReceiver (doctor-side realtime listener) ka end-to-end flow explain karta hai, plus ek sample `curl` for FCM incoming_call push.

---

## Components
- CallLab: `src/pages/CallLab.jsx`
- DoctorReceiver: `src/pages/DoctorReceiver.jsx`

---

## CallLab.jsx Flow (Patient/Test UI)
### 1) API Base Selection
- Prod (`snoutiq.com`) ? `${origin}/backend`
- Local ? `http://127.0.0.1:8000`

### 2) Inputs
- `patientId`
- `doctorId` (heartbeat)
- `callId`
- `channel` (video/audio)
- `bearerToken` (optional)
- `doctorFcmToken` (ringing push ke liye)

### 3) Heartbeat
- Button: **Doctor Heartbeat**
- Endpoint: `POST /api/realtime/heartbeat`
- Payload: `{ doctor_id: <doctorId> }`
- Result logs me print hota hai.

### 4) Call Request
- Button: **Request Call**
- Endpoint: `POST /api/calls/request`
- Payload: `{ patient_id, channel }`
- Success par `callId` set hota hai.

### 5) Ringing Push (FCM)
- `requestCall()` success ke baad:
  - `startRingingPush()` call hota hai
  - `POST /api/push/test` single-shot push send karta hai
- Payload me:
  - `type: incoming_call`
  - `call_id`, `doctor_id`, `channel`
- 30s ke baad local stop log hota hai.

### 6) Call Actions
- **Accept**: `POST /api/calls/{id}/accept`
- **Reject**: `POST /api/calls/{id}/reject`
- **Cancel**: `POST /api/calls/{id}/cancel`
- **End**: `POST /api/calls/{id}/end`

---

## DoctorReceiver.jsx Flow (Doctor Listener)
### 1) API Base + Reverb Config
- Prod: `https://snoutiq.com/backend`
- Local: `http://127.0.0.1:8000`
- Reverb host/port based on prod/local.

### 2) Connect & Listen
- Button: **Connect & Listen**
- Laravel Echo (Reverb) connect hota hai
- Channel subscribe: `doctor.{doctorId}`

### 3) Realtime Events
- `.CallRequested`
  - incoming call set hota hai
  - `callId` auto-fill hota hai
- `.CallStatusUpdated`
  - incoming data merge hota hai

### 4) Heartbeat
- On connect + every 10s:
  - `POST /api/realtime/heartbeat` with `{ doctor_id }`

### 5) Call Actions
- **Accept/Reject/End**
- Endpoint: `POST /api/calls/{id}/{action}`

---

## End-to-End Flow (Combined)
1) DoctorReceiver connects + heartbeat start
2) CallLab requests call
3) Backend creates call + broadcasts `.CallRequested` to `doctor.{doctorId}`
4) DoctorReceiver receives event ? incoming UI shows
5) CallLab sends FCM push (incoming_call)
6) Doctor can Accept/Reject/End ? backend updates call + broadcasts `.CallStatusUpdated`

---

## FCM Incoming Call (Sample curl)
```bash
curl -X POST https://api.your-prod-domain.com/api/push/ring \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_PROD_JWT" \
  -d '{
    "token": "PROD_FCM_TOKEN",
    "data": {
      "type": "incoming_call",
      "call_id": "12345",
      "doctor_id": "6789",
      "patient_id": "546",
      "channel": "video",
      "channel_name": "agora_channel_12345",
      "expires_at": "1769619315491",
      "data_only": "1"
    },
    "android": {
      "priority": "high",
      "ttl": "30s"
    },
    "apns": {
      "headers": {
        "apns-priority": "10"
      }
    }
  }'
```

---

## Notes
- Incoming call push flow depends on backend rules for `incoming_call`.
- DoctorReceiver realtime flow = Reverb events only (no FCM handling).
- CallLab me FCM token manual input hai (auto-fetch nahi).

File: `VIDEO_CALL_FLOW_FROM_UI.md`