<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Snoutiq Symptom Checker</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar: #0f172a;
      --sidebar-2: #111c35;
      --main-bg: #f6f8fb;
      --pane: #ffffff;
      --line: #e2e8f0;
      --text: #0f172a;
      --muted: #64748b;
      --brand: #0ea5e9;
      --brand-2: #0284c7;
      --ok: #166534;
      --warn: #a16207;
      --danger: #b91c1c;
      --chip: #eef4ff;
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      font-family: "Manrope", sans-serif;
      color: var(--text);
      background: radial-gradient(1000px 380px at 0% 0%, #e8f2ff 0%, transparent 58%),
                  radial-gradient(980px 360px at 100% 0%, #e7faf7 0%, transparent 56%),
                  var(--main-bg);
    }

    .app {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 280px 1fr 360px;
    }

    .sidebar {
      background: linear-gradient(180deg, var(--sidebar), var(--sidebar-2));
      color: #dbe7ff;
      border-right: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      position: sticky;
      top: 0;
      max-height: 100vh;
    }

    .side-head {
      padding: 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: "Space Grotesk", sans-serif;
      font-weight: 700;
      letter-spacing: 0.3px;
      font-size: 18px;
    }

    .dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #22d3ee;
      box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.15);
    }

    .side-sub {
      margin: 6px 0 0;
      color: #95a7cc;
      font-size: 12px;
      line-height: 1.45;
    }

    .side-actions {
      padding: 14px;
      display: grid;
      gap: 8px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .rooms {
      padding: 10px;
      overflow: auto;
      display: grid;
      gap: 8px;
      align-content: start;
    }

    .room {
      padding: 10px;
      border-radius: 12px;
      border: 1px solid rgba(148, 163, 184, 0.22);
      background: rgba(15, 23, 42, 0.35);
      cursor: pointer;
      transition: all .14s ease;
    }

    .room:hover {
      border-color: rgba(56, 189, 248, 0.55);
      background: rgba(15, 23, 42, 0.58);
    }

    .room.active {
      border-color: rgba(56, 189, 248, 0.9);
      background: rgba(2, 132, 199, 0.25);
    }

    .room-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .room-title {
      font-size: 13px;
      font-weight: 700;
      color: #ecf4ff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .room-snippet {
      margin-top: 4px;
      font-size: 11px;
      color: #9db2d8;
      line-height: 1.4;
      max-height: 32px;
      overflow: hidden;
    }

    .room-meta {
      margin-top: 5px;
      font-size: 10px;
      color: #8ea6cf;
      display: flex;
      justify-content: space-between;
      gap: 6px;
    }

    .btn {
      border: 0;
      border-radius: 10px;
      padding: 9px 11px;
      font: inherit;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: transform .05s ease, opacity .15s ease, background .15s ease;
    }
    .btn:active { transform: translateY(1px); }
    .btn:disabled { cursor: not-allowed; opacity: .55; }

    .btn-primary {
      color: #fff;
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
    }

    .btn-soft {
      background: #edf2fb;
      color: #12263f;
      border: 1px solid #d8e1ef;
    }

    .btn-danger {
      background: #fff1f2;
      color: #be123c;
      border: 1px solid #fecdd3;
    }

    .main {
      min-height: 100vh;
      display: grid;
      grid-template-rows: auto 1fr auto;
      border-right: 1px solid var(--line);
      background: #f9fbff;
    }

    .main-head {
      padding: 14px 18px;
      border-bottom: 1px solid var(--line);
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(6px);
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }

    .main-head h2 {
      margin: 0;
      font-family: "Space Grotesk", sans-serif;
      font-size: 20px;
    }

    .muted {
      color: var(--muted);
      font-size: 12px;
    }

    .head-right {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }

    .chat-stream {
      overflow: auto;
      padding: 22px 18px 26px;
      display: grid;
      gap: 12px;
      align-content: start;
    }

    .msg {
      max-width: min(90%, 760px);
      border-radius: 14px;
      padding: 11px 13px;
      border: 1px solid #e2e8f0;
      background: #fff;
      line-height: 1.45;
      font-size: 14px;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .msg.user {
      margin-left: auto;
      border-color: #cae6ff;
      background: linear-gradient(180deg, #f0f8ff 0%, #e7f2ff 100%);
    }

    .msg.assistant {
      margin-right: auto;
      border-color: #e2e8f0;
      background: #ffffff;
    }

    .msg.system {
      margin: 0 auto;
      border-style: dashed;
      color: #475569;
      font-size: 13px;
      background: #f8fafc;
    }

    .msg-meta {
      margin-bottom: 6px;
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: .35px;
      font-weight: 700;
      display: flex;
      justify-content: space-between;
      gap: 8px;
    }

    .composer {
      border-top: 1px solid var(--line);
      background: #fff;
      padding: 12px 16px 14px;
    }

    .composer-box {
      border: 1px solid #d7e2f1;
      border-radius: 14px;
      background: #fff;
      padding: 10px;
      display: grid;
      gap: 10px;
    }

    textarea {
      width: 100%;
      border: 0;
      outline: none;
      resize: none;
      min-height: 72px;
      font: inherit;
      font-size: 14px;
      color: #0f172a;
      background: transparent;
    }

    .compose-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .hint { font-size: 12px; color: #64748b; }

    .context {
      background: #fff;
      min-height: 100vh;
      max-height: 100vh;
      overflow: auto;
      padding: 14px;
      display: grid;
      gap: 12px;
      align-content: start;
    }

    .panel {
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      overflow: hidden;
    }

    .panel h3 {
      margin: 0;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .35px;
      color: #475569;
      background: #f8fafc;
      border-bottom: 1px solid var(--line);
      padding: 10px 12px;
    }

    .panel-body {
      padding: 11px 12px;
      display: grid;
      gap: 10px;
    }

    .grid-2 {
      display: grid;
      gap: 9px;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-1 { display: grid; gap: 9px; }

    label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: .3px;
      margin-bottom: 5px;
    }

    input, select {
      width: 100%;
      border: 1px solid #d6deeb;
      border-radius: 9px;
      padding: 8px 9px;
      font: inherit;
      font-size: 13px;
      color: #0f172a;
      background: #fff;
      outline: none;
    }

    input:focus, select:focus {
      border-color: #38bdf8;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, .16);
    }

    .chips {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .chip {
      padding: 5px 9px;
      border-radius: 999px;
      border: 1px solid #d8e4f5;
      background: var(--chip);
      font-size: 12px;
      font-weight: 700;
      color: #1d4ed8;
    }

    .chip.ok { color: var(--ok); background: #ecfdf3; border-color: #bbf7d0; }
    .chip.warn { color: var(--warn); background: #fefce8; border-color: #fde68a; }
    .chip.danger { color: var(--danger); background: #fef2f2; border-color: #fecaca; }

    .plain {
      margin: 0;
      font-size: 13px;
      line-height: 1.45;
      color: #0f172a;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .status {
      font-size: 12px;
      color: #334155;
      border: 1px solid #dbe6f3;
      background: #f8fbff;
      border-radius: 9px;
      padding: 8px 10px;
      white-space: pre-wrap;
    }

    .status.err {
      border-color: #fecaca;
      background: #fef2f2;
      color: #991b1b;
    }

    .context-lines {
      display: grid;
      gap: 6px;
      max-height: 220px;
      overflow: auto;
    }

    .ctx {
      border: 1px solid #e2e8f0;
      border-radius: 9px;
      padding: 7px 9px;
      background: #fff;
      font-size: 12px;
      line-height: 1.35;
      word-break: break-word;
    }

    .ctx.user { border-color: #bfdbfe; background: #eff6ff; }
    .ctx.assistant { border-color: #e2e8f0; background: #f8fafc; }

    .links {
      display: grid;
      gap: 8px;
    }

    .api-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      font-size: 12px;
      font-weight: 700;
      border: 1px solid #d8e1ef;
      border-radius: 9px;
      background: #fff;
      color: #0f172a;
      padding: 8px 9px;
    }

    .empty {
      border: 1px dashed #cbd5e1;
      color: #64748b;
      padding: 16px;
      border-radius: 12px;
      text-align: center;
      font-size: 13px;
      background: #fff;
    }

    @media (max-width: 1280px) {
      .app {
        grid-template-columns: 250px 1fr 320px;
      }
    }

    @media (max-width: 1040px) {
      .app {
        grid-template-columns: 1fr;
      }
      .sidebar, .context {
        min-height: auto;
        max-height: none;
        position: static;
      }
      .main {
        border-right: 0;
      }
      .grid-2 { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <div class="side-head">
        <div class="brand"><span class="dot"></span> Snoutiq AI Triage</div>
        <p class="side-sub">ChatGPT-style symptom workspace with persistent rooms and session context.</p>
      </div>
      <div class="side-actions">
        <button id="btnNewChat" class="btn btn-primary" type="button">+ New Chat</button>
        <button id="btnNewRoom" class="btn btn-soft" type="button">+ New Room</button>
      </div>
      <div id="roomsList" class="rooms"></div>
    </aside>

    <main class="main">
      <header class="main-head">
        <div>
          <h2 id="activeTitle">Symptom Chat</h2>
          <div class="muted" id="activeMeta">Session: -</div>
        </div>
        <div class="head-right">
          <button id="btnLoadSession" class="btn btn-soft" type="button">Load Session</button>
          <button id="btnRestart" class="btn btn-soft" type="button">Restart Session</button>
          <button id="btnResetAll" class="btn btn-danger" type="button">Reset All Local</button>
        </div>
      </header>

      <section id="chatStream" class="chat-stream"></section>

      <footer class="composer">
        <div class="composer-box">
          <textarea id="messageInput" placeholder="Describe symptoms in detail and press Enter..."></textarea>
          <div class="compose-row">
            <span class="hint">Enter = send, Shift+Enter = new line</span>
            <button id="btnSend" class="btn btn-primary" type="button">Send</button>
          </div>
        </div>
      </footer>
    </main>

    <aside class="context">
      <div class="panel">
        <h3>Status</h3>
        <div class="panel-body">
          <div id="status" class="status">Ready.</div>
          <div class="chips" id="chips"></div>
        </div>
      </div>

      <div class="panel">
        <h3>Session Controls</h3>
        <div class="panel-body">
          <div class="grid-1">
            <div>
              <label for="sessionInput">Session ID</label>
              <input id="sessionInput" type="text" placeholder="room_xxx or existing session">
            </div>
            <div>
              <label for="apiToken">Bearer Token (Optional)</label>
              <input id="apiToken" type="text" placeholder="Leave blank if not required">
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <h3>Pet + User Context</h3>
        <div class="panel-body">
          <div class="grid-2">
            <div>
              <label for="userId">User ID</label>
              <input id="userId" type="number" min="1" value="1247">
            </div>
            <div>
              <label for="petId">Pet ID</label>
              <input id="petId" type="number" min="0" value="456">
            </div>
            <div>
              <label for="petName">Pet Name</label>
              <input id="petName" type="text" value="Bruno">
            </div>
            <div>
              <label for="species">Species</label>
              <select id="species">
                <option value="dog" selected>dog</option>
                <option value="cat">cat</option>
                <option value="rabbit">rabbit</option>
                <option value="bird">bird</option>
                <option value="reptile">reptile</option>
              </select>
            </div>
            <div>
              <label for="breed">Breed</label>
              <input id="breed" type="text" value="Labrador">
            </div>
            <div>
              <label for="dob">DOB</label>
              <input id="dob" type="date">
            </div>
            <div>
              <label for="sex">Sex</label>
              <select id="sex">
                <option value="" selected>unknown</option>
                <option value="male">male</option>
                <option value="female">female</option>
              </select>
            </div>
            <div>
              <label for="neutered">Neutered</label>
              <select id="neutered">
                <option value="unknown" selected>unknown</option>
                <option value="yes">yes</option>
                <option value="no">no</option>
              </select>
            </div>
            <div style="grid-column: 1 / -1;">
              <label for="location">Location</label>
              <input id="location" type="text" value="Gurgaon">
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <h3>Decision Snapshot</h3>
        <div class="panel-body">
          <div class="grid-2">
            <div><label>Routing</label><p id="mRouting" class="plain">-</p></div>
            <div><label>Severity</label><p id="mSeverity" class="plain">-</p></div>
            <div><label>Score</label><p id="mScore" class="plain">-</p></div>
            <div><label>Turn</label><p id="mTurn" class="plain">-</p></div>
            <div><label>Context Turns</label><p id="mContextTurns" class="plain">0</p></div>
            <div><label>Bypass</label><p id="mBypass" class="plain">-</p></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <h3>Triage Detail</h3>
        <div class="panel-body">
          <pre id="triageDetail" class="plain">-</pre>
        </div>
      </div>

      <div class="panel">
        <h3>Quick Actions</h3>
        <div id="ctaLinks" class="panel-body links"></div>
      </div>

      <div class="panel">
        <h3>Context Window</h3>
        <div class="panel-body">
          <div id="contextWindow" class="context-lines"></div>
        </div>
      </div>
    </aside>
  </div>

  <script>
    (function () {
      const backendPrefix = window.location.pathname.startsWith('/backend/') ? '/backend' : '';
      const API_BASE = `${window.location.origin}${backendPrefix}/api`;
      const STORAGE_ROOMS = 'snoutiq_symptom_web_rooms_v2';
      const STORAGE_ACTIVE = 'snoutiq_symptom_web_active_room_v2';

      const el = {
        roomsList: document.getElementById('roomsList'),
        activeTitle: document.getElementById('activeTitle'),
        activeMeta: document.getElementById('activeMeta'),
        chatStream: document.getElementById('chatStream'),
        messageInput: document.getElementById('messageInput'),
        status: document.getElementById('status'),
        chips: document.getElementById('chips'),

        apiToken: document.getElementById('apiToken'),
        sessionInput: document.getElementById('sessionInput'),
        userId: document.getElementById('userId'),
        petId: document.getElementById('petId'),
        petName: document.getElementById('petName'),
        species: document.getElementById('species'),
        breed: document.getElementById('breed'),
        dob: document.getElementById('dob'),
        sex: document.getElementById('sex'),
        neutered: document.getElementById('neutered'),
        location: document.getElementById('location'),

        mRouting: document.getElementById('mRouting'),
        mSeverity: document.getElementById('mSeverity'),
        mScore: document.getElementById('mScore'),
        mTurn: document.getElementById('mTurn'),
        mContextTurns: document.getElementById('mContextTurns'),
        mBypass: document.getElementById('mBypass'),
        triageDetail: document.getElementById('triageDetail'),
        ctaLinks: document.getElementById('ctaLinks'),
        contextWindow: document.getElementById('contextWindow'),

        btnSend: document.getElementById('btnSend'),
        btnNewChat: document.getElementById('btnNewChat'),
        btnNewRoom: document.getElementById('btnNewRoom'),
        btnLoadSession: document.getElementById('btnLoadSession'),
        btnRestart: document.getElementById('btnRestart'),
        btnResetAll: document.getElementById('btnResetAll')
      };

      const state = {
        rooms: [],
        activeRoomId: null,
        busy: false
      };

      function uid(prefix) {
        return `${prefix}_${Date.now()}_${Math.random().toString(16).slice(2, 8)}`;
      }

      function generateSessionId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
          return `room_${window.crypto.randomUUID()}`;
        }
        return uid('room');
      }

      function now() {
        return Date.now();
      }

      function formatTime(ts) {
        if (!ts) return '-';
        try {
          return new Date(ts).toLocaleString();
        } catch (_) {
          return '-';
        }
      }

      function compact(text, len) {
        const str = String(text || '').trim().replace(/\s+/g, ' ');
        if (!str) return '-';
        return str.length > len ? `${str.slice(0, len - 1)}...` : str;
      }

      function escapeHtml(str) {
        return String(str)
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }

      function stringify(value) {
        if (value === null || value === undefined || value === '') return '-';
        if (Array.isArray(value)) return value.length ? value.join(', ') : '-';
        if (typeof value === 'object') return JSON.stringify(value, null, 2);
        return String(value);
      }

      function setStatus(message, isError) {
        el.status.textContent = message;
        el.status.classList.toggle('err', !!isError);
      }

      function getAuthHeader() {
        const token = (el.apiToken.value || '').trim();
        return token ? { Authorization: `Bearer ${token}` } : {};
      }

      function saveState() {
        localStorage.setItem(STORAGE_ROOMS, JSON.stringify(state.rooms));
        if (state.activeRoomId) {
          localStorage.setItem(STORAGE_ACTIVE, state.activeRoomId);
        } else {
          localStorage.removeItem(STORAGE_ACTIVE);
        }
      }

      function normalizeRoom(raw, idx) {
        const room = raw || {};
        return {
          id: room.id || uid('local'),
          title: room.title || `Chat ${idx + 1}`,
          session_id: room.session_id || generateSessionId(),
          messages: Array.isArray(room.messages) ? room.messages : [],
          context: room.context || null,
          created_at: room.created_at || now(),
          updated_at: room.updated_at || now(),
          manualTitle: !!room.manualTitle
        };
      }

      function loadState() {
        const rawRooms = localStorage.getItem(STORAGE_ROOMS);
        let parsed = [];
        try {
          parsed = rawRooms ? JSON.parse(rawRooms) : [];
        } catch (_) {
          parsed = [];
        }

        state.rooms = Array.isArray(parsed)
          ? parsed.map((r, i) => normalizeRoom(r, i))
          : [];

        if (!state.rooms.length) {
          const first = createRoom({ title: 'New Chat 1' }, false);
          state.activeRoomId = first.id;
          saveState();
          return;
        }

        const storedActive = localStorage.getItem(STORAGE_ACTIVE);
        const exists = state.rooms.find((r) => r.id === storedActive);
        state.activeRoomId = exists ? exists.id : state.rooms[0].id;
      }

      function createRoom(opts, persist) {
        const options = opts || {};
        const room = {
          id: uid('local_room'),
          title: options.title || `New Chat ${state.rooms.length + 1}`,
          session_id: options.session_id || generateSessionId(),
          messages: [],
          context: null,
          created_at: now(),
          updated_at: now(),
          manualTitle: !!options.manualTitle
        };

        state.rooms.unshift(room);
        state.activeRoomId = room.id;

        if (persist !== false) saveState();
        return room;
      }

      function activeRoom() {
        return state.rooms.find((r) => r.id === state.activeRoomId) || null;
      }

      function setActiveRoom(roomId) {
        const target = state.rooms.find((r) => r.id === roomId);
        if (!target) return;
        state.activeRoomId = target.id;
        el.sessionInput.value = target.session_id || '';
        saveState();
        renderAll();
      }

      function removeRoom(roomId) {
        const idx = state.rooms.findIndex((r) => r.id === roomId);
        if (idx === -1) return;

        state.rooms.splice(idx, 1);

        if (!state.rooms.length) {
          const fresh = createRoom({ title: 'New Chat 1' }, false);
          state.activeRoomId = fresh.id;
        } else if (state.activeRoomId === roomId) {
          state.activeRoomId = state.rooms[0].id;
        }

        saveState();
        renderAll();
      }

      function appendMessage(room, role, text, meta) {
        if (!room) return;
        const payload = {
          id: uid('msg'),
          role,
          text: String(text || ''),
          ts: now(),
          meta: meta || null
        };
        room.messages.push(payload);
        if (room.messages.length > 200) {
          room.messages = room.messages.slice(-200);
        }
        room.updated_at = now();
      }

      function autoTitleFromMessage(message) {
        const plain = String(message || '').trim();
        if (!plain) return null;
        const title = plain.length > 34 ? `${plain.slice(0, 34)}...` : plain;
        return title;
      }

      function extractContext(res) {
        return {
          routing: res.routing || res.decision || '-',
          severity: res.severity || res.symptom_analysis?.severity || '-',
          score: (res.score ?? '-'),
          turn: (res.turn ?? '-'),
          red_flag_bypass: !!res.red_flag_bypass,
          response: res.response || {},
          triage_detail: res.triage_detail || {},
          buttons: res.buttons || {},
          vet_summary: res.vet_summary || '',
          updated_at: now()
        };
      }

      function getContextTurns(room) {
        if (!room) return 0;
        const fromContext = Number(room.context?.turn || 0);
        if (fromContext > 0) return fromContext;
        const userTurns = room.messages.filter((m) => m.role === 'user').length;
        return userTurns;
      }

      function renderRooms() {
        if (!state.rooms.length) {
          el.roomsList.innerHTML = '<div class="empty">No rooms</div>';
          return;
        }

        el.roomsList.innerHTML = state.rooms.map((room) => {
          const isActive = room.id === state.activeRoomId;
          const last = room.messages.length ? room.messages[room.messages.length - 1].text : '';
          return `
            <div class="room ${isActive ? 'active' : ''}" data-room-id="${escapeHtml(room.id)}">
              <div class="room-top">
                <div class="room-title">${escapeHtml(room.title)}</div>
                <button class="btn btn-soft room-delete" data-room-delete="${escapeHtml(room.id)}" type="button">x</button>
              </div>
              <div class="room-snippet">${escapeHtml(compact(last || room.session_id, 64))}</div>
              <div class="room-meta">
                <span>${escapeHtml(compact(room.session_id, 18))}</span>
                <span>${escapeHtml(formatTime(room.updated_at))}</span>
              </div>
            </div>
          `;
        }).join('');
      }

      function renderChat() {
        const room = activeRoom();

        if (!room) {
          el.activeTitle.textContent = 'Symptom Chat';
          el.activeMeta.textContent = 'Session: -';
          el.chatStream.innerHTML = '<div class="empty">Create a room to start chatting.</div>';
          return;
        }

        el.activeTitle.textContent = room.title;
        el.activeMeta.textContent = `Session: ${room.session_id}`;
        el.sessionInput.value = room.session_id || '';

        if (!room.messages.length) {
          el.chatStream.innerHTML = '<div class="empty">Start with symptom details. First message calls /symptom-check, next messages continue context with /symptom-followup.</div>';
          return;
        }

        el.chatStream.innerHTML = room.messages.map((m) => {
          const roleLabel = m.role === 'user' ? 'You' : (m.role === 'assistant' ? 'Snoutiq' : 'System');
          const cls = m.role === 'user' ? 'user' : (m.role === 'assistant' ? 'assistant' : 'system');
          const sub = m.meta
            ? ` | ${compact(`routing:${m.meta.routing || '-'} severity:${m.meta.severity || '-'} turn:${m.meta.turn || '-'}`, 44)}`
            : '';

          return `
            <div class="msg ${cls}">
              <div class="msg-meta">
                <span>${escapeHtml(roleLabel)}</span>
                <span>${escapeHtml(formatTime(m.ts))}${escapeHtml(sub)}</span>
              </div>
              ${escapeHtml(m.text)}
            </div>
          `;
        }).join('');

        el.chatStream.scrollTop = el.chatStream.scrollHeight;
      }

      function renderChips(room) {
        if (!room || !room.context) {
          el.chips.innerHTML = '<span class="chip">Waiting for first response</span>';
          return;
        }

        const ctx = room.context;
        const routing = String(ctx.routing || '-').toLowerCase();
        const severity = String(ctx.severity || '-').toLowerCase();

        const routingClass = routing === 'emergency'
          ? 'danger'
          : (routing === 'in_clinic' ? 'warn' : 'ok');

        const severityClass = severity === 'critical'
          ? 'danger'
          : (severity === 'moderate' ? 'warn' : 'ok');

        el.chips.innerHTML = `
          <span class="chip ${routingClass}">Routing: ${escapeHtml(ctx.routing || '-')}</span>
          <span class="chip ${severityClass}">Severity: ${escapeHtml(ctx.severity || '-')}</span>
          <span class="chip">Score: ${escapeHtml(String(ctx.score ?? '-'))}</span>
          <span class="chip">Turn: ${escapeHtml(String(ctx.turn ?? '-'))}</span>
          <span class="chip">Bypass: ${ctx.red_flag_bypass ? 'yes' : 'no'}</span>
        `;
      }

      function renderContextWindow(room) {
        if (!room || !room.messages.length) {
          el.contextWindow.innerHTML = '<div class="ctx">No context yet.</div>';
          return;
        }

        const items = room.messages.slice(-12);
        el.contextWindow.innerHTML = items.map((m) => {
          const cls = m.role === 'user' ? 'user' : 'assistant';
          const label = m.role === 'user' ? 'You' : (m.role === 'assistant' ? 'Snoutiq' : 'System');
          return `<div class="ctx ${cls}"><b>${escapeHtml(label)}:</b> ${escapeHtml(compact(m.text, 150))}</div>`;
        }).join('');
      }

      function renderCtaLinks(room) {
        const buttons = room?.context?.buttons || {};
        const keys = ['primary', 'secondary'];
        const links = [];

        keys.forEach((k) => {
          const btn = buttons[k];
          if (!btn) return;
          const label = `${btn.label || k} (${btn.type || 'cta'})`;
          const href = btn.deeplink || '#';
          links.push(`<a class="api-link" href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer">${escapeHtml(label)}</a>`);
        });

        if (!links.length) {
          el.ctaLinks.innerHTML = '<div class="plain">No quick actions yet.</div>';
          return;
        }

        el.ctaLinks.innerHTML = links.join('');
      }

      function renderDetail(room) {
        if (!room || !room.context) {
          el.mRouting.textContent = '-';
          el.mSeverity.textContent = '-';
          el.mScore.textContent = '-';
          el.mTurn.textContent = '-';
          el.mBypass.textContent = '-';
          el.mContextTurns.textContent = String(getContextTurns(room));
          el.triageDetail.textContent = '-';
          renderCtaLinks(room);
          renderChips(room);
          renderContextWindow(room);
          return;
        }

        const ctx = room.context;
        el.mRouting.textContent = stringify(ctx.routing);
        el.mSeverity.textContent = stringify(ctx.severity);
        el.mScore.textContent = stringify(ctx.score);
        el.mTurn.textContent = stringify(ctx.turn);
        el.mBypass.textContent = ctx.red_flag_bypass ? 'yes' : 'no';
        el.mContextTurns.textContent = String(getContextTurns(room));

        const detail = ctx.triage_detail || {};
        el.triageDetail.textContent =
          `Possible causes: ${stringify(detail.possible_causes)}\n` +
          `Red flags: ${stringify(detail.red_flags_found)}\n` +
          `India context: ${stringify(detail.india_context)}\n` +
          `Safe wait (hours): ${stringify(detail.safe_to_wait_hours)}\n` +
          `Image observation: ${stringify(detail.image_observation)}`;

        renderCtaLinks(room);
        renderChips(room);
        renderContextWindow(room);
      }

      function renderAll() {
        renderRooms();
        renderChat();
        renderDetail(activeRoom());
        el.btnSend.disabled = state.busy;
      }

      function valOrNull(v) {
        const t = String(v || '').trim();
        return t ? t : null;
      }

      function payloadForCheck(message, room) {
        return {
          user_id: Number(el.userId.value || 0),
          pet_id: Number(el.petId.value || 0),
          message,
          session_id: room.session_id || null,
          pet_name: valOrNull(el.petName.value),
          species: valOrNull(el.species.value),
          breed: valOrNull(el.breed.value),
          dob: valOrNull(el.dob.value),
          sex: valOrNull(el.sex.value),
          neutered: valOrNull(el.neutered.value),
          location: valOrNull(el.location.value)
        };
      }

      async function apiFetch(path, method, body) {
        const upper = String(method || 'GET').toUpperCase();
        const headers = Object.assign({ Accept: 'application/json' }, getAuthHeader());
        if (upper !== 'GET') {
          headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(`${API_BASE}${path}`, {
          method: upper,
          headers,
          body: upper === 'GET' ? undefined : JSON.stringify(body || {})
        });

        const json = await response.json().catch(() => ({}));
        if (!response.ok || json.success === false) {
          const msg = json.message || json.error || `HTTP ${response.status}`;
          throw new Error(msg);
        }
        return json;
      }

      async function sendMessage() {
        if (state.busy) return;

        const room = activeRoom();
        if (!room) {
          setStatus('No active room available.', true);
          return;
        }

        const message = String(el.messageInput.value || '').trim();
        if (!message) {
          setStatus('Please type a message.', true);
          return;
        }

        const userId = Number(el.userId.value || 0);
        if (!userId) {
          setStatus('user_id is required.', true);
          return;
        }

        if (!room.session_id) {
          room.session_id = generateSessionId();
        }

        appendMessage(room, 'user', message);
        if (!room.manualTitle && room.messages.length <= 2) {
          const auto = autoTitleFromMessage(message);
          if (auto) room.title = auto;
        }

        el.messageInput.value = '';
        state.busy = true;
        saveState();
        renderAll();
        setStatus('Sending...');

        try {
          const hasAssistantHistory = room.messages.some((m) => m.role === 'assistant');
          let res;

          if (!hasAssistantHistory) {
            const body = payloadForCheck(message, room);
            if (!body.pet_id) {
              throw new Error('pet_id is required for first message in new chat.');
            }
            res = await apiFetch('/symptom-check', 'POST', body);
          } else {
            res = await apiFetch('/symptom-followup', 'POST', {
              user_id: userId,
              session_id: room.session_id,
              message
            });
          }

          if (res.session_id) {
            room.session_id = res.session_id;
          }

          const aiText = res.response?.message || res.chat?.answer || 'No assistant message returned.';
          appendMessage(room, 'assistant', aiText, {
            routing: res.routing,
            severity: res.severity,
            turn: res.turn
          });

          room.context = extractContext(res);
          room.updated_at = now();

          saveState();
          renderAll();
          setStatus(`Done. Session: ${room.session_id}`);
        } catch (err) {
          appendMessage(room, 'system', `Request failed: ${err.message || 'Unknown error'}`);
          saveState();
          renderAll();
          setStatus(err.message || 'Request failed', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      }

      async function loadSession() {
        const room = activeRoom();
        if (!room) return;

        const sessionId = valOrNull(el.sessionInput.value) || room.session_id;
        if (!sessionId) {
          setStatus('Session ID required.', true);
          return;
        }

        state.busy = true;
        renderAll();
        setStatus(`Loading session ${sessionId} ...`);

        try {
          const res = await apiFetch(`/symptom-session/${encodeURIComponent(sessionId)}`, 'GET');
          const history = Array.isArray(res.state?.history) ? res.state.history : [];

          room.session_id = sessionId;
          room.messages = [];

          history.forEach((h) => {
            if (h.user || h.message) {
              appendMessage(room, 'user', h.user || h.message);
            }
            if (h.assistant || h.response) {
              appendMessage(room, 'assistant', h.assistant || h.response, {
                routing: h.routing || '-',
                severity: '-',
                turn: '-'
              });
            }
          });

          room.context = {
            routing: history.length ? (history[history.length - 1].routing || '-') : '-',
            severity: '-',
            score: res.state?.score ?? '-',
            turn: history.length,
            red_flag_bypass: false,
            response: {},
            triage_detail: {},
            buttons: {},
            vet_summary: '',
            updated_at: now()
          };

          room.updated_at = now();
          saveState();
          renderAll();
          setStatus(`Session loaded: ${sessionId}`);
        } catch (err) {
          setStatus(err.message || 'Failed to load session', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      }

      async function restartSession() {
        const room = activeRoom();
        if (!room || !room.session_id) {
          setStatus('No active session to restart.', true);
          return;
        }

        state.busy = true;
        renderAll();
        setStatus(`Restarting ${room.session_id} ...`);

        try {
          await apiFetch(`/symptom-session/${encodeURIComponent(room.session_id)}/reset`, 'POST', {});
          room.messages = [];
          room.context = null;
          room.updated_at = now();
          saveState();
          renderAll();
          setStatus(`Session restarted: ${room.session_id}`);
        } catch (err) {
          setStatus(err.message || 'Failed to restart session', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      }

      function newChat() {
        createRoom({ title: `New Chat ${state.rooms.length + 1}` });
        saveState();
        renderAll();
        setStatus('New chat created.');
      }

      function newRoom() {
        const custom = (window.prompt('Optional custom session_id. Leave blank for auto-generated room id.') || '').trim();
        if (custom) {
          const existing = state.rooms.find((r) => r.session_id === custom);
          if (existing) {
            setActiveRoom(existing.id);
            setStatus(`Opened existing local room for ${custom}`);
            return;
          }
        }

        const created = createRoom({
          title: `Room ${state.rooms.length + 1}`,
          session_id: custom || generateSessionId(),
          manualTitle: true
        });

        saveState();
        renderAll();
        setStatus(`New room created: ${created.session_id}`);
      }

      async function resetAllLocal() {
        const ok = window.confirm('Clear all local rooms and UI state?');
        if (!ok) return;

        state.rooms = [];
        state.activeRoomId = null;
        localStorage.removeItem(STORAGE_ROOMS);
        localStorage.removeItem(STORAGE_ACTIVE);

        const first = createRoom({ title: 'New Chat 1' }, false);
        state.activeRoomId = first.id;

        saveState();
        renderAll();
        setStatus('All local data cleared.');
      }

      el.roomsList.addEventListener('click', function (event) {
        const deleteId = event.target.getAttribute('data-room-delete');
        if (deleteId) {
          event.stopPropagation();
          removeRoom(deleteId);
          return;
        }

        const card = event.target.closest('[data-room-id]');
        if (card) {
          setActiveRoom(card.getAttribute('data-room-id'));
        }
      });

      el.btnSend.addEventListener('click', sendMessage);
      el.btnNewChat.addEventListener('click', newChat);
      el.btnNewRoom.addEventListener('click', newRoom);
      el.btnLoadSession.addEventListener('click', loadSession);
      el.btnRestart.addEventListener('click', restartSession);
      el.btnResetAll.addEventListener('click', resetAllLocal);

      el.messageInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          sendMessage();
        }
      });

      loadState();
      renderAll();
      setStatus('Ready. Pick a room and start chatting.');
    })();
  </script>
</body>
</html>
