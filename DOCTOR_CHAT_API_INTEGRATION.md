# Doctor Chat API Integration Guide

## Overview

Yeh API set `pet parent <-> doctor` one-to-one chat ke liye bana hai.

Current backend me yeh features available hain:

- chat room create ya reuse
- room list fetch
- message list fetch
- naya message send
- unread messages ko read mark karna
- recipient ko FCM push bhejna

Important:

- Current implementation me message ke liye websocket broadcast nahi hai.
- Isliye "live chat" abhi practical sense me `API + push notification + polling/refresh` se chalega.
- True instant live sync ke liye socket server me doctor chat events add karne honge.

## Base URL

`https://snoutiq.com/backend/api/doctor-chats`

## Auth / Actor Resolution

Backend actor ko do tarah se resolve karta hai:

1. `Authorization: Bearer <token>`
2. `actor_type + actor_id` ya `sender_type + sender_id`

Supported actor types:

- `user`
- `doctor`

Recommendation:

- Production app me `Bearer token` use karo.
- Sirf `actor_type` aur `actor_id` par depend mat karo, kyunki yeh secure app auth ka replacement nahi hai.

## Data Shape

### Room Object

```json
{
  "id": 12,
  "user": {
    "id": 1140,
    "name": "Mayank",
    "email": "mayank@example.com",
    "phone": "9999999999"
  },
  "doctor": {
    "id": 116,
    "name": "Dr Sharma",
    "email": "doctor@example.com",
    "mobile": "8888888888"
  },
  "last_message_at": "2026-03-16T10:10:10+00:00",
  "last_message": {
    "id": 55,
    "room_id": 12,
    "sender_type": "user",
    "sender_id": 1140,
    "message": "Hello doctor, my pet is not eating.",
    "read_at": null,
    "created_at": "2026-03-16T10:10:10+00:00",
    "updated_at": "2026-03-16T10:10:10+00:00"
  },
  "unread_count": 1,
  "created_at": "2026-03-16T10:00:00+00:00",
  "updated_at": "2026-03-16T10:10:10+00:00"
}
```

Notes:

- `unread_count` tabhi meaningful milega jab request me actor resolve ho jaye.
- `last_message` room me koi message na ho to `null` ho sakta hai.
- `last_message_at` bhi first message se pehle `null` ho sakta hai.

### Message Object

```json
{
  "id": 55,
  "room_id": 12,
  "sender_type": "user",
  "sender_id": 1140,
  "message": "Hello doctor, my pet is not eating.",
  "read_at": null,
  "created_at": "2026-03-16T10:10:10+00:00",
  "updated_at": "2026-03-16T10:10:10+00:00"
}
```

## APIs

### 1. Create Or Reuse Room

`POST /rooms`

Request:

```json
{
  "user_id": 1140,
  "doctor_id": 116
}
```

If room naya bana:

Status: `201`

```json
{
  "success": true,
  "message": "Chat room created.",
  "data": {
    "id": 12,
    "user": {
      "id": 1140,
      "name": "Mayank",
      "email": "mayank@example.com",
      "phone": "9999999999"
    },
    "doctor": {
      "id": 116,
      "name": "Dr Sharma",
      "email": "doctor@example.com",
      "mobile": "8888888888"
    },
    "last_message_at": null,
    "last_message": null,
    "unread_count": null,
    "created_at": "2026-03-16T10:00:00+00:00",
    "updated_at": "2026-03-16T10:00:00+00:00"
  }
}
```

If room already exist karta hai:

Status: `200`

```json
{
  "success": true,
  "message": "Chat room already exists.",
  "data": {
    "id": 12,
    "user": {
      "id": 1140,
      "name": "Mayank",
      "email": "mayank@example.com",
      "phone": "9999999999"
    },
    "doctor": {
      "id": 116,
      "name": "Dr Sharma",
      "email": "doctor@example.com",
      "mobile": "8888888888"
    },
    "last_message_at": "2026-03-16T10:10:10+00:00",
    "last_message": {
      "id": 55,
      "room_id": 12,
      "sender_type": "user",
      "sender_id": 1140,
      "message": "Hello doctor, my pet is not eating.",
      "read_at": null,
      "created_at": "2026-03-16T10:10:10+00:00",
      "updated_at": "2026-03-16T10:10:10+00:00"
    },
    "unread_count": null,
    "created_at": "2026-03-16T10:00:00+00:00",
    "updated_at": "2026-03-16T10:10:10+00:00"
  }
}
```

Validation error example:

Status: `422`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "user_id": [
      "The selected user id is invalid."
    ]
  }
}
```

### 2. List Rooms For User Or Doctor

`GET /rooms?actor_type=user&actor_id=1140`

Ya:

`GET /rooms?actor_type=doctor&actor_id=116`

Success response:

Status: `200`

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "user": {
        "id": 1140,
        "name": "Mayank",
        "email": "mayank@example.com",
        "phone": "9999999999"
      },
      "doctor": {
        "id": 116,
        "name": "Dr Sharma",
        "email": "doctor@example.com",
        "mobile": "8888888888"
      },
      "last_message_at": "2026-03-16T10:10:10+00:00",
      "last_message": {
        "id": 55,
        "room_id": 12,
        "sender_type": "user",
        "sender_id": 1140,
        "message": "Hello doctor, my pet is not eating.",
        "read_at": null,
        "created_at": "2026-03-16T10:10:10+00:00",
        "updated_at": "2026-03-16T10:10:10+00:00"
      },
      "unread_count": 1,
      "created_at": "2026-03-16T10:00:00+00:00",
      "updated_at": "2026-03-16T10:10:10+00:00"
    }
  ]
}
```

Agar koi filter hi na do:

Status: `422`

```json
{
  "success": false,
  "message": "Provide actor token or at least one filter (user_id/doctor_id)."
}
```

Useful query params:

- `actor_type`
- `actor_id`
- `user_id`
- `doctor_id`
- `limit` default `20`, max `100`

### 3. Get Messages Of A Room

`GET /rooms/{ROOM_ID}/messages?actor_type=user&actor_id=1140`

Ya:

`GET /rooms/{ROOM_ID}/messages?actor_type=doctor&actor_id=116`

Success response:

Status: `200`

```json
{
  "success": true,
  "room_id": 12,
  "data": [
    {
      "id": 55,
      "room_id": 12,
      "sender_type": "user",
      "sender_id": 1140,
      "message": "Hello doctor, my pet is not eating.",
      "read_at": null,
      "created_at": "2026-03-16T10:10:10+00:00",
      "updated_at": "2026-03-16T10:10:10+00:00"
    },
    {
      "id": 56,
      "room_id": 12,
      "sender_type": "doctor",
      "sender_id": 116,
      "message": "Please share temperature and vomiting details.",
      "read_at": null,
      "created_at": "2026-03-16T10:12:10+00:00",
      "updated_at": "2026-03-16T10:12:10+00:00"
    }
  ]
}
```

Actor missing:

Status: `401`

```json
{
  "success": false,
  "message": "Actor is required. Pass Bearer token or actor_type + actor_id."
}
```

Room actor ka nahi hai:

Status: `403`

```json
{
  "success": false,
  "message": "Not allowed to access this room."
}
```

Useful query params:

- `actor_type`
- `actor_id`
- `limit` default `50`, max `200`
- `before_id` older pagination ke liye

### 4. Send Message

`POST /rooms/{ROOM_ID}/messages`

Request from pet parent:

```json
{
  "message": "Hello doctor, my pet is not eating.",
  "actor_type": "user",
  "actor_id": 1140
}
```

Request from doctor:

```json
{
  "message": "Please share temperature and vomiting details.",
  "actor_type": "doctor",
  "actor_id": 116
}
```

Success response:

Status: `201`

```json
{
  "success": true,
  "message": "Message sent.",
  "data": {
    "id": 56,
    "room_id": 12,
    "sender_type": "doctor",
    "sender_id": 116,
    "message": "Please share temperature and vomiting details.",
    "read_at": null,
    "created_at": "2026-03-16T10:12:10+00:00",
    "updated_at": "2026-03-16T10:12:10+00:00"
  },
  "push": {
    "sent": true,
    "recipient_type": "user",
    "recipient_id": 1140,
    "token_count": 1,
    "success_count": 1,
    "failure_count": 0,
    "errors": []
  }
}
```

If push token missing ho:

```json
{
  "success": true,
  "message": "Message sent.",
  "data": {
    "id": 56,
    "room_id": 12,
    "sender_type": "doctor",
    "sender_id": 116,
    "message": "Please share temperature and vomiting details.",
    "read_at": null,
    "created_at": "2026-03-16T10:12:10+00:00",
    "updated_at": "2026-03-16T10:12:10+00:00"
  },
  "push": {
    "sent": false,
    "reason": "token_missing",
    "recipient_type": "user",
    "recipient_id": 1140,
    "token_count": 0
  }
}
```

Sender missing:

Status: `422`

```json
{
  "success": false,
  "message": "Sender is required. Pass Bearer token or actor_type + actor_id."
}
```

Sender room ka part nahi hai:

Status: `403`

```json
{
  "success": false,
  "message": "Sender is not part of this room."
}
```

### Push Payload Sent To Recipient

Jab message send hota hai aur recipient ke paas valid FCM token hota hai, backend push data me yeh fields bhejta hai:

```json
{
  "type": "doctor_chat_message",
  "room_id": "12",
  "message_id": "56",
  "sender_type": "doctor",
  "sender_id": "116",
  "recipient_type": "user",
  "recipient_id": "1140",
  "created_at": "2026-03-16T10:12:10+00:00"
}
```

Pet parent app me push click/open par:

1. `room_id` read karo
2. same chat room par navigate karo
3. `GET /rooms/{roomId}/messages` call karo
4. screen open hone par `PATCH /rooms/{roomId}/read` call karo

### 5. Mark Messages As Read

`PATCH /rooms/{ROOM_ID}/read`

Request:

```json
{
  "actor_type": "doctor",
  "actor_id": 116
}
```

Success response:

Status: `200`

```json
{
  "success": true,
  "message": "Messages marked as read.",
  "updated_count": 3
}
```

Actor missing:

Status: `401`

```json
{
  "success": false,
  "message": "Actor is required. Pass Bearer token or actor_type + actor_id."
}
```

Not allowed:

Status: `403`

```json
{
  "success": false,
  "message": "Not allowed to modify read status for this room."
}
```

## Pet Parent Side Integration Flow

### Minimal Working Flow

1. Pet parent doctor profile ya appointment screen se `POST /rooms` hit kare.
2. Response se `room.id` le.
3. Chat screen open hote hi `GET /rooms/{roomId}/messages` call kare.
4. Message send karte waqt `POST /rooms/{roomId}/messages` hit kare.
5. Send success milte hi response wala message local list me append kare.
6. Har `2-5 sec` par messages refresh kare, ya screen focus/app resume par refresh kare.
7. Jab naya incoming message fetch ho jaye aur user ne room open kar liya ho, `PATCH /rooms/{roomId}/read` call kare.
8. Chat list screen me `GET /rooms?actor_type=user&actor_id=...` use karke last message aur unread count dikhao.

### Recommended Pet Parent UX

- Doctor ke saath first time chat start karte waqt room auto-create karo.
- Chat list me `doctor name`, `last_message`, `last_message_at`, `unread_count` dikhao.
- Chat screen me latest messages bottom par scroll karo.
- Send ke baad optimistic UI kar sakte ho, lekin final message object API response se hi sync karo.
- App background me ho to FCM push se user ko notify karo.
- Push open hone par directly same `roomId` par navigate karke messages reload karo.

### Polling Strategy For Current Backend

Current backend me message websocket event nahi hai, isliye pet parent side par yeh workable strategy hai:

- chat room open ho to `GET /rooms/{roomId}/messages` every `3 sec`
- app foreground me aaye to ek immediate refresh
- message send hone ke turant baad ek refresh
- push notification receive hone par room/messages refresh
- room list screen ke liye `GET /rooms` every `10-20 sec` ya pull-to-refresh

Yeh "near real-time" experience dega.

## Doctor Side Flow

Doctor side par bhi same pattern use hoga:

1. `GET /rooms?actor_type=doctor&actor_id=116`
2. Kisi room ko open karke `GET /rooms/{roomId}/messages?actor_type=doctor&actor_id=116`
3. Reply send with `POST /rooms/{roomId}/messages`
4. Room open karte hi `PATCH /rooms/{roomId}/read`
5. Push receive hone par messages refresh

## Kya Yeh Current API Se "Live Chat" Ban Jayega?

### Haan, practical level par

Agar aap:

- push notification setup kar do
- chat screen par polling chala do
- room list refresh kar do

to user aur doctor dono ko almost live chat experience milega.

### Nahi, true realtime websocket level par

Current implementation me:

- doctor chat ke liye socket event emit nahi ho raha
- message send hone par recipient ko direct websocket broadcast nahi ja raha
- sirf DB save + FCM push ho raha hai

Isliye exact instant sync without polling abhi available nahi hai.

## True Live Chat Ke Liye Kya Add Karna Hoga

Existing `socket-server` already project me present hai. Doctor chat ko truly live banane ke liye yeh add karo:

1. User aur doctor ko socket rooms join karvao
   - `user-{id}`
   - `doctor-{id}`
   - optionally `doctor-chat-room-{roomId}`
2. Jab `POST /rooms/{roomId}/messages` success ho, socket event emit karo:
   - `doctor-chat:new-message`
3. Payload me same formatted message object bhejo
4. Receiver side par event milte hi UI list update karo
5. `PATCH /read` ke baad optional `doctor-chat:read` event emit karo
6. Room list ke liye optional `doctor-chat:room-updated` emit karo

Suggested socket event payload:

```json
{
  "event": "doctor-chat:new-message",
  "room": {
    "id": 12
  },
  "message": {
    "id": 56,
    "room_id": 12,
    "sender_type": "doctor",
    "sender_id": 116,
    "message": "Please share temperature and vomiting details.",
    "read_at": null,
    "created_at": "2026-03-16T10:12:10+00:00",
    "updated_at": "2026-03-16T10:12:10+00:00"
  }
}
```

## Recommended Frontend Sequence

### Pet Parent Opens Chat

1. `POST /rooms`
2. `GET /rooms/{roomId}/messages`
3. start polling
4. on each incoming refresh, render messages
5. if unread incoming messages exist, `PATCH /read`

### Pet Parent Sends Message

1. submit input
2. `POST /rooms/{roomId}/messages`
3. append returned `data`
4. optionally refresh messages once
5. room list refresh

### Doctor Replies

1. doctor screen refreshes via polling or push
2. doctor sends message
3. pet parent gets push
4. pet parent app opens room and refreshes messages

## Edge Cases

- Same user-doctor pair ke liye duplicate room nahi banega.
- Message max length `2000` chars hai.
- Room access sirf same user ya same doctor ko allowed hai, but app-side auth phir bhi properly enforce karo.
- `updated_count` `0` ho sakta hai agar unread message na ho.
- Pagination ke liye `before_id` available hai.

## Short Conclusion

Pet parent side se is API ko aise use karo:

- room create/reuse karo
- messages fetch karo
- message send karo
- read mark karo
- push + polling use karo

Aaj ki date par yeh API `chat backend` ke liye enough hai, lekin `true websocket live chat` ke liye ek extra socket layer add karni padegi.

## Source Files

- `backend/routes/api.php`
- `backend/app/Http/Controllers/Api/DoctorChatController.php`
- `backend/app/Models/DoctorChatRoom.php`
- `backend/app/Models/DoctorChatMessage.php`
- `backend/database/migrations/2026_03_16_000011_create_doctor_chat_rooms_table.php`
- `backend/database/migrations/2026_03_16_000012_create_doctor_chat_messages_table.php`
