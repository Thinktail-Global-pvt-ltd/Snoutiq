# Backend: FCM Incoming Call Contract (Patient App)

This doc explains the exact FCM payload the backend must send for **incoming calls** so that:
- Notification appears (even when app is background/killed)
- Ringtone loops like a call
- Incoming modal opens when user opens the app directly (without tapping notification)

## Key Rule (Very Important)
**For incoming calls, send data-only push.**

Reason:
- App needs the background handler to run so it can save call state and show the modal even when the user opens the app directly.
- If you include a `notification` block, Android may *not* deliver the message to the background handler, so the app cannot restore the incoming call unless the user taps the notification.

The app will create its own Notifee notification with looping ringtone.

---

## REQUIRED Payload (data-only)

```json
{
  "token": "<FCM_DEVICE_TOKEN>",
  "data": {
    "type": "incoming_call",
    "call_id": "123",
    "doctor_id": "77",
    "patient_id": "864",
    "channel": "video",
    "channel_name": "agora_channel_123",
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
}
```

### Notes
- **All values must be strings** in `data`.
- `expires_at` is epoch milliseconds (now + 30s).
- `channel` must be `video` or `audio`.

---

## What the app does with this payload
- Background handler stores the call in AsyncStorage (`ACTIVE_CALL`).
- App shows a Notifee call notification (full-screen + looping ringtone).
- If user opens the app directly, MainStack restores the saved call and opens IncomingCallModal.

---

## If you *must* include a notification block (NOT recommended for calls)
If you send `notification` along with `data`, Android may show a system notification **but background handler won’t run**.
That means:
- Incoming modal won’t open when user opens app directly.
- App only knows about the call after notification tap.

So, **use notification block only for non-call notifications**.

---

## Backend checklist
- Send **data-only** for `type=incoming_call`.
- Ensure `priority=high` and short `ttl` (<= 30s).
- Ensure all data values are strings.
- Avoid `notification` block for calls to prevent losing background handler.

---

## Quick sanity test
1) Kill app
2) Send incoming_call data-only push
3) Expect: Notifee notification + ringtone
4) Open app directly (no tap) -> IncomingCallModal should show

If step 3 fails, backend is not sending data-only or device token is wrong.
If step 4 fails, background handler did not run or call state wasn’t saved.