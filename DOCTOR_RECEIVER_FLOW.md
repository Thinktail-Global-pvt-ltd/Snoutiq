# DoctorReceiver.jsx Flow (Hinglish)

## Purpose
DoctorReceiver ek lightweight React page hai jo doctor ke liye realtime incoming call events sunta hai aur accept/reject/end actions trigger karta hai. Yeh mostly dev/test UI hai.

## High-Level Flow
1) User doctor id aur (optional) bearer token enter karta hai.
2) "Connect & Listen" par click -> Laravel Reverb (Echo + Pusher) se WebSocket connect hota hai.
3) Channel `doctor.{doctorId}` subscribe hota hai.
4) `.CallRequested` event aate hi incoming state set hota hai.
5) `.CallStatusUpdated` event se incoming data update hota hai.
6) UI me incoming card aur logs render hote hain.
7) Accept/Reject/End buttons backend call actions ko hit karte hain.

## Env/Config (Hardcoded Switch)
- Prod detect: `window.location.hostname === "snoutiq.com"`
- API base:
  - Prod: `https://snoutiq.com/backend`
  - Local: `http://127.0.0.1:8000`
- Reverb:
  - key: `base64:yT9RzP3vXl9lJ2pB2g==`
  - host: prod `snoutiq.com`, local `127.0.0.1`
  - scheme: prod `https`, local `http`
  - port: prod `443`, local `8080`

## Auth / Headers
- Token optional.
- Agar token present hai to `Authorization: Bearer <token>` header add hota hai.
- Sab API calls me `Accept: application/json` + `Content-Type: application/json` set hota hai.

## Realtime Subscription
- Channel: `doctor.{doctorId}`
- Events:
  - `.CallRequested`
  - `.CallStatusUpdated`
- Event aate hi logs me entry add hoti hai aur `incoming` state update hota hai.

## Heartbeat
- Connect ke turant baad `POST /api/realtime/heartbeat` call hota hai.
- Payload: `{ doctor_id: <doctorId> }`
- Repeat: har 10 seconds (setInterval)

## Call Actions
Buttons hit karte hain:
- `POST /api/calls/{callId}/accept`
- `POST /api/calls/{callId}/reject`
- `POST /api/calls/{callId}/end`

## UI States
- `conn`: websocket status (`connected`, `disconnected`, `error`)
- `incoming`: last received call data
- `callId`: auto-fill from events (call_id / id)
- `logs`: latest 200 log lines (most recent first)

## Logs
- Har important event/time ke saath ISO timestamp add hota hai.
- Logs top par latest entries ke saath render hote hain.

## Cleanup
- Unmount par:
  - heartbeat interval clear
  - echo connection disconnect

## Notes
- Token optional hai, lekin protected endpoints ke liye required ho sakta hai.
- Doctor id change karke alag channel subscribe ho sakta hai.
- Reverb connection status chip me show hota hai.

File: `src/pages/DoctorReceiver.jsx`