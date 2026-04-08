# `/ask` Flow Documentation

This file explains how the `/ask` page works in the current React app, which APIs it uses, and exactly when each API call happens.

## Route Entry

- Route registration: `src/AppRoutes.jsx`
- Route path: `/ask`
- Route component: `src/newflow/SymptomsHub.jsx`
- Final page component: `src/newflow/AskPage.jsx`

Flow:

1. User opens `/ask`
2. `AppRoutes` renders `SymptomsHub`
3. `SymptomsHub` lazy-loads `AskPage`
4. `AskPage` renders the symptom-checker UI

## Main Files Involved

- `src/AppRoutes.jsx`
  Route entry for `/ask`
- `src/newflow/SymptomsHub.jsx`
  Lazy loader wrapper for the page
- `src/newflow/AskPage.jsx`
  Main UI, state management, API calls, CTA navigation
- `src/newflow/AskPage.css`
  Styling for the page
- `src/lib/api.js`
  Shared API base URL and JSON POST helper

## API Base URL

Defined in `src/lib/api.js`.

- On `snoutiq.com` host:
  `https://<current-origin>/backend`
- Otherwise fallback:
  `https://snoutiq.com/backend`

That means `/ask` uses backend endpoints like:

- `https://snoutiq.com/backend/api/symptom-check`
- `https://snoutiq.com/backend/api/symptom-followup`
- `https://snoutiq.com/backend/api/symptom-answer`
- `https://snoutiq.com/backend/api/symptom-session/{session_id}`

## Local State Used On `/ask`

Inside `AskPage.jsx`, the page keeps:

- `species`
  Current selected species in idle screen
- `sessionId`
  Current symptom session id from backend
- `entries`
  Rendered chat timeline items
- `inputValue`
  Textarea value
- `loading`
  Whether request is in flight
- `errorMessage`
  UI error banner
- `toastMessage`
  Copy/reset toast
- `checksToday`
  Local daily free-check counter
- `followUpPending`
  Inline loading/selection state for `symptom-answer`

## Local Storage Used

The page also persists some state locally:

- `snoutiq-ask-state-v1`
  Stores:
  - `species`
  - `sessionId`
  - `entries`
- `snoutiq-ask-daily-usage-v1`
  Stores:
  - current date
  - local count of free checks used today

Important:

- `checksToday` is frontend-local only
- there is currently no backend usage-limit API in this flow

## High-Level User Flow

### 1. Initial page load

When `/ask` opens:

1. `AskPage` reads local storage
2. If previous state exists:
   - restores `species`
   - restores `sessionId`
   - restores `entries`
3. It reads local daily usage count
4. It renders:
   - idle screen if no chat entries exist
   - existing conversation if restored

### 2. Session restore fallback

If:

- `sessionId` exists
- `entries` are empty
- hydration is complete

then frontend calls:

- `GET /api/symptom-session/{session_id}`

This is a recovery path so that a saved session can still rebuild a minimal chat timeline.

### 3. First message

If user sends the very first message and no `sessionId` exists yet:

- frontend opens the intake modal first
- user enters pet parent + pet details
- if species is `dog`, frontend loads breeds from `GET /api/dog-breeds/all`
- if species is `cat`, frontend loads breeds from `GET /api/cat-breeds/with-indian`
- after modal submit, frontend calls `POST /api/symptom-check`

This can happen from:

- quick symptom buttons
- manual input send button
- Enter key in textarea

### 4. Follow-up message

If user already has a `sessionId` and sends another message:

- frontend calls `POST /api/symptom-followup`

### 5. Start over

If user clicks `Start over`:

- frontend confirms with `window.confirm`
- if `sessionId` exists, it calls `POST /api/symptom-session/{sessionId}/reset`
- then it clears local UI state even if the backend reset fails

### 6. Follow-up question answer

If backend sends a `follow_up_question` block and user taps one option:

- frontend calls `POST /api/symptom-answer`
- the current assessment card is updated in place
- frontend does not append a new chat bubble for that option

## Exact API Usage

### 1. `POST /api/symptom-check`

Used when:

- first symptom message is sent
- quick symptom cards are clicked before any session exists

Frontend call site:

- `handleSend()` in `src/newflow/AskPage.jsx`

Request body:

```json
{
  "message": "My dog has been vomiting repeatedly since this morning",
  "species": "dog",
  "type": "dog",
  "phone": "9876543210",
  "owner_name": "Rahul",
  "pet_name": "Bruno",
  "breed": "Labrador",
  "dob": "2021-06-01",
  "location": "Delhi NCR",
  "user": {
    "name": "Rahul",
    "phone": "9876543210"
  },
  "pets": {
    "pet_name": "Bruno",
    "name": "Bruno",
    "breed": "Labrador",
    "dob": "2021-06-01",
    "type": "dog",
    "species": "dog"
  }
}
```

Purpose:

- creates a new symptom-check session
- persists/links pet parent + pet profile on backend when possible
- gets the first AI triage assessment
- returns `session_id`

Frontend behavior after success:

- stores `session_id` in state
- appends assessment card to chat
- increments local daily free-check count

### 2. `POST /api/symptom-followup`

Used when:

- a session already exists
- user asks another question or gives more details

Frontend call site:

- `handleSend()` in `src/newflow/AskPage.jsx`

Request body:

```json
{
  "session_id": "room_abc123",
  "message": "He is still vomiting and looks weak"
}
```

Purpose:

- continues the existing assessment
- refines routing and health score
- returns updated UI payload

Frontend behavior after success:

- keeps same session
- appends new assessment card
- does not increment the first-check counter

### 3. `POST /api/symptom-answer`

Used when:

- backend response contains `follow_up_question`
- user taps one of the suggested answer options

Frontend call site:

- `handleFollowUpAnswer()` in `src/newflow/AskPage.jsx`

Request body:

```json
{
  "session_id": "room_abc123",
  "question": "Did this start suddenly in the last 24 hours, or has it been building over a few days?",
  "answer": "Started suddenly in the last 24 hours"
}
```

Purpose:

- submits a structured answer to the guided follow-up question
- backend internally re-runs the symptom assessment for the same session
- response may include `revised_assessment: true`

Frontend behavior after success:

- keeps same `session_id`
- replaces the current assessment card payload instead of appending another one
- stores selected answer locally on that card
- shows revised-assessment state on the same card

### 4. `GET /api/symptom-session/{session_id}`

Used when:

- page reload/restoration happens
- session id exists in local state
- entries array is empty

Frontend call site:

- restore `useEffect()` in `src/newflow/AskPage.jsx`

Example:

```http
GET /api/symptom-session/room_abc123
Accept: application/json
```

Purpose:

- restores history from backend when frontend entries are missing

Frontend behavior after success:

- converts backend `state.history` into simple user/note entries
- repopulates chat timeline

Frontend behavior after failure:

- clears `sessionId`
- clears stored state

### 5. `POST /api/symptom-session/{session_id}/reset`

Used when:

- user clicks `Start over`

Frontend call site:

- `handleReset()` in `src/newflow/AskPage.jsx`

Example:

```http
POST /api/symptom-session/room_abc123/reset
Accept: application/json
```

Purpose:

- resets backend session history

Frontend behavior after success or failure:

- clears local entries
- clears `sessionId`
- clears input
- clears stored local state

## Response Fields Frontend Actually Uses

The page does not render the entire backend response blindly. It reads specific fields.

### Core fields

- `session_id`
- `routing`
- `health_score`
- `ui`
- `buttons`
- `response`
- `follow_up_question`
- `follow_up_history`
- `be_ready_to_tell_vet`
- `revised_assessment`
- `triage_detail`
- `vet_summary`

### `ui` fields used

- `ui.theme`
- `ui.banner.eyebrow`
- `ui.banner.title`
- `ui.banner.subtitle`
- `ui.banner.time_badge`
- `ui.health_score.value`
- `ui.health_score.color`
- `ui.health_score.label`
- `ui.health_score.subtitle`
- `ui.health_score.share.title`
- `ui.health_score.share.helper`
- `ui.health_score.share.whatsapp_text`
- `ui.service_cards[]`

### `buttons` fields used

- `buttons.primary.label`
- `buttons.primary.type`
- `buttons.primary.color`
- `buttons.primary.deeplink`
- `buttons.secondary.label`
- `buttons.secondary.type`
- `buttons.secondary.deeplink`

### `response` fields used

- `response.message`
- `response.what_we_think_is_happening`
- `response.do_now`
- `response.safe_to_do_while_waiting`
- `response.what_to_watch`
- `response.be_ready_to_tell_vet`
- `response.follow_up_question`
- `response.diagnosis_summary`

### `triage_detail` fields used

- `triage_detail.india_context`
- `triage_detail.image_observation`
- `triage_detail.possible_causes`
- `triage_detail.red_flags_found`

### Other fields used

- `follow_up_question`
- `follow_up_history`
- `be_ready_to_tell_vet`
- `revised_assessment`
- `vet_summary`

## How UI Is Built From Response

For every successful assessment response:

1. frontend creates a chat entry with `kind: "assessment"`
2. `AssessmentCard` renders the card
3. card sections are driven by backend payload:
   - banner
   - health score
   - CTA buttons
   - service cards
   - diagnosis/explanation
   - do-now block
   - India-specific note
   - safe-to-do-while-waiting list
   - follow-up question card
   - watch-for list
   - likely causes
   - red flags
   - be-ready-to-tell-vet section
   - vet summary

This means the backend decides most of the assessment content and action hints.

## Quick Symptom Buttons

Idle screen has predefined quick-start prompts:

- Not Eating
- Vomiting
- Limping
- Diarrhea
- Skin / Itching
- Lethargy

When user taps one:

1. frontend sets species from the quick prompt
2. frontend immediately sends the predefined message
3. first request goes to `POST /api/symptom-check`

## Send Logic

All normal sending goes through `handleSend()`:

- if no `sessionId`:
  open intake modal, then call `POST /api/symptom-check`
- if `sessionId` exists:
  call `POST /api/symptom-followup`

This is the main branching logic of `/ask`.

## Guided Follow-up Answer Logic

Guided answer flow goes through `handleFollowUpAnswer()`:

- if card has `follow_up_question`:
  call `POST /api/symptom-answer`
- on success:
  replace that same assessment entry with the revised payload
- if backend returns `revised_assessment: true`:
  frontend shows the revised badge on that card

This keeps the UI stable and avoids duplicate assessment cards for tap-based answers.

## CTA Navigation From Response

Assessment buttons do not always navigate directly from backend deeplinks. The frontend maps them to app routes.

Current mapping in `AskPage.jsx`:

### Type-based mapping

- `video_consult` -> `/20+vetsonline?start=details`
- `clinic` -> `/vet-at-home-gurgaon/pet-details`
- `vet_at_home` -> `/vet-at-home-gurgaon/pet-details`
- `emergency` -> `/vet-at-home-gurgaon/pet-details`
- `govt` -> `/vet-at-home-gurgaon/pet-details`

### Deeplink mapping

- `snoutiq://video-consult` -> `/20+vetsonline?start=details`
- `snoutiq://vet-at-home` -> `/vet-at-home-gurgaon/pet-details`
- `snoutiq://clinic-booking` -> `/vet-at-home-gurgaon/pet-details`
- `snoutiq://find-clinic` -> `/vet-at-home-gurgaon/pet-details`
- `snoutiq://emergency` -> `/vet-at-home-gurgaon/pet-details`
- `snoutiq://govt-hospitals` -> `/vet-at-home-gurgaon/pet-details`

Special case:

- `type: "info"` does not navigate; it copies the assessment summary instead

Also:

- top header button `Consult ₹499` navigates to:
  `/20+vetsonline?start=details`

## Actions That Do Not Hit Backend

These interactions are frontend-only:

- species selection buttons
- copy link
- copy summary
- WhatsApp share
- textarea auto-resize
- local toast messages
- local “free checks left” badge

## Error Handling

If an API call fails:

- frontend extracts a readable error message
- common fetch/network errors are converted into a friendlier message
- error is shown in the chat area

For restore failure on `GET /api/symptom-session/{id}`:

- frontend clears bad session state and resets local storage

## Current Sequence Summary

### Fresh user flow

1. Open `/ask`
2. No session exists
3. User types symptom
4. `POST /api/symptom-check`
5. Backend returns first assessment + `session_id`
6. Frontend renders assessment card
7. User sends more detail
8. `POST /api/symptom-followup`
9. Frontend appends revised assessment
10. User clicks CTA
11. Frontend navigates to consult/booking route

### Reloaded user flow

1. Open `/ask`
2. Frontend restores local state
3. If `sessionId` exists but entries are missing:
4. `GET /api/symptom-session/{sessionId}`
5. Frontend rebuilds minimal history

### Reset flow

1. User clicks `Start over`
2. `POST /api/symptom-session/{sessionId}/reset`
3. Frontend clears local state
4. Idle screen appears again

## Practical Dev Notes

- `sessionId` is the switch between first-check and follow-up API
- first check increments the local free-check counter
- follow-up does not
- guided answer uses `symptom-answer`, not `symptom-followup`
- guided answer updates the same card in place
- restore only happens when `sessionId` exists but no entries are available
- CTA behavior is partly backend-driven and partly frontend-mapped
- `/ask` currently uses backend payload structure quite heavily, so response contract changes can affect rendering quickly

## Fast Reference

| Action | API | Method | Trigger |
|---|---|---|---|
| First symptom message | `/api/symptom-check` | `POST` | First send when no `sessionId` exists, after intake modal submit |
| Follow-up symptom message | `/api/symptom-followup` | `POST` | Any later send when `sessionId` exists |
| Guided follow-up answer | `/api/symptom-answer` | `POST` | User taps an option from `follow_up_question` |
| Restore missing session history | `/api/symptom-session/{sessionId}` | `GET` | On load when session exists but entries are empty |
| Reset conversation | `/api/symptom-session/{sessionId}/reset` | `POST` | On `Start over` |
