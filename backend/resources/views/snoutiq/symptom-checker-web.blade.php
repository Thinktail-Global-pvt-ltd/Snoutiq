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
        <p class="side-sub">ChatGPT-style symptom workspace with DB-backed rooms from <code>chat_rooms</code>.</p>
      </div>
      <div class="side-actions">
        <button id="btnNewRoom" class="btn btn-primary" type="button">+ New Room</button>
      </div>
      <div id="roomsList" class="rooms"></div>
    </aside>

    <main class="main">
      <header class="main-head">
        <div>
          <h2 id="activeTitle">Symptom Chat</h2>
          <div class="muted" id="activeMeta">Room: -</div>
        </div>
        <div class="head-right">
          <button id="btnRefreshRooms" class="btn btn-soft" type="button">Refresh Rooms</button>
          <button id="btnReloadRoom" class="btn btn-soft" type="button">Reload Room</button>
          <button id="btnDeleteRoom" class="btn btn-danger" type="button">Delete Room</button>
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
        <h3>Room Controls</h3>
        <div class="panel-body">
          <div class="grid-1">
            <div>
              <label for="roomName">New Room Name (Optional)</label>
              <input id="roomName" type="text" placeholder="e.g. Vomiting Follow-up">
            </div>
            <div>
              <label for="activeRoomToken">Active Room Token</label>
              <input id="activeRoomToken" type="text" placeholder="Select a room from sidebar" readonly>
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

      const el = {
        roomsList: document.getElementById('roomsList'),
        activeTitle: document.getElementById('activeTitle'),
        activeMeta: document.getElementById('activeMeta'),
        chatStream: document.getElementById('chatStream'),
        messageInput: document.getElementById('messageInput'),
        status: document.getElementById('status'),
        chips: document.getElementById('chips'),

        apiToken: document.getElementById('apiToken'),
        roomName: document.getElementById('roomName'),
        activeRoomToken: document.getElementById('activeRoomToken'),
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
        btnNewRoom: document.getElementById('btnNewRoom'),
        btnRefreshRooms: document.getElementById('btnRefreshRooms'),
        btnReloadRoom: document.getElementById('btnReloadRoom'),
        btnDeleteRoom: document.getElementById('btnDeleteRoom')
      };

      const state = {
        rooms: [],
        activeRoomToken: null,
        roomChats: {},
        roomMeta: {},
        busy: false
      };

      function getUserId() {
        return Number(el.userId.value || 0);
      }

      function setStatus(message, isError) {
        el.status.textContent = String(message || '');
        el.status.classList.toggle('err', !!isError);
      }

      function getAuthHeader() {
        const token = (el.apiToken.value || '').trim();
        return token ? { Authorization: `Bearer ${token}` } : {};
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

      function compact(text, len) {
        const str = String(text || '').trim().replace(/\s+/g, ' ');
        if (!str) return '-';
        return str.length > len ? `${str.slice(0, len - 1)}...` : str;
      }

      function valOrNull(v) {
        const t = String(v || '').trim();
        return t ? t : null;
      }

      function formatTime(value) {
        if (!value) return '-';
        try {
          return new Date(value).toLocaleString();
        } catch (_) {
          return '-';
        }
      }

      function activeRoom() {
        return state.rooms.find((room) => room.chat_room_token === state.activeRoomToken) || null;
      }

      function activeChats() {
        return state.roomChats[state.activeRoomToken] || [];
      }

      function activeMeta() {
        return state.roomMeta[state.activeRoomToken] || null;
      }

      function countUserTurns(chats) {
        return (chats || []).reduce((acc, row) => {
          return acc + (String(row.question || '').trim() ? 1 : 0);
        }, 0);
      }

      function flattenChats(chats) {
        const output = [];
        (chats || []).forEach((row) => {
          if (String(row.question || '').trim()) {
            output.push({
              role: 'user',
              text: row.question,
              created_at: row.created_at,
              source: row
            });
          }
          if (String(row.answer || '').trim()) {
            output.push({
              role: 'assistant',
              text: row.answer,
              created_at: row.created_at,
              source: row
            });
          }
        });
        return output;
      }

      function inferMetaFromChats(chats) {
        const rows = chats || [];
        if (!rows.length) {
          return {
            routing: '-',
            severity: '-',
            score: '-',
            turn: 0,
            red_flag_bypass: false,
            triage_detail: {},
            buttons: {}
          };
        }

        const latest = rows[rows.length - 1];
        const emergency = String(latest.emergency_status || '').toUpperCase();
        const routing = emergency === 'EMERGENCY'
          ? 'emergency'
          : (emergency === 'IN_CLINIC' ? 'in_clinic' : '-');

        return {
          routing,
          severity: '-',
          score: '-',
          turn: countUserTurns(rows),
          red_flag_bypass: false,
          triage_detail: {},
          buttons: {}
        };
      }

      function extractMetaFromResponse(res, fallbackTurn) {
        const analysis = res.symptom_analysis || {};
        return {
          routing: res.routing || res.decision || analysis.routing || '-',
          severity: res.severity || analysis.severity || '-',
          score: res.score ?? '-',
          turn: res.turn ?? fallbackTurn ?? '-',
          red_flag_bypass: !!res.red_flag_bypass,
          triage_detail: res.triage_detail || {
            possible_causes: analysis.possible_causes || [],
            red_flags_found: analysis.red_flags_present || [],
            india_context: analysis.india_context_note || '',
            safe_to_wait_hours: 0,
            image_observation: ''
          },
          buttons: res.buttons || {}
        };
      }

      async function apiFetch(path, method, body, query) {
        const upper = String(method || 'GET').toUpperCase();
        const url = new URL(`${API_BASE}${path}`);

        if (query && typeof query === 'object') {
          Object.entries(query).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') return;
            url.searchParams.set(key, String(value));
          });
        }

        const headers = Object.assign({ Accept: 'application/json' }, getAuthHeader());
        if (upper !== 'GET') {
          headers['Content-Type'] = 'application/json';
        }

        const res = await fetch(url.toString(), {
          method: upper,
          headers,
          body: upper === 'GET' ? undefined : JSON.stringify(body || {})
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.status === 'error' || json.success === false) {
          const msg = json.message || json.error || `HTTP ${res.status}`;
          throw new Error(msg);
        }
        return json;
      }

      function renderRooms() {
        if (!state.rooms.length) {
          el.roomsList.innerHTML = '<div class="empty">No room found for this user. Click New Room.</div>';
          return;
        }

        el.roomsList.innerHTML = state.rooms.map((room) => {
          const token = room.chat_room_token;
          const isActive = token === state.activeRoomToken;
          const name = room.name || room.summary || `Room ${room.id}`;
          const status = room.last_emergency_status ? ` | ${room.last_emergency_status}` : '';

          return `
            <div class="room ${isActive ? 'active' : ''}" data-room-token="${escapeHtml(token)}">
              <div class="room-top">
                <div class="room-title">${escapeHtml(name)}</div>
                <button class="btn btn-soft room-delete" data-room-delete="${escapeHtml(token)}" type="button">x</button>
              </div>
              <div class="room-snippet">${escapeHtml(compact((room.summary || '').trim() || token, 72))}</div>
              <div class="room-meta">
                <span>${escapeHtml(compact(token, 18))}</span>
                <span>${escapeHtml(formatTime(room.updated_at))}${escapeHtml(status)}</span>
              </div>
            </div>
          `;
        }).join('');
      }

      function renderChat() {
        const room = activeRoom();
        const chats = activeChats();
        const messages = flattenChats(chats);

        if (!room) {
          el.activeTitle.textContent = 'Symptom Chat';
          el.activeMeta.textContent = 'Room: -';
          el.activeRoomToken.value = '';
          el.chatStream.innerHTML = '<div class="empty">Enter user_id and click Refresh Rooms or create a New Room.</div>';
          return;
        }

        el.activeTitle.textContent = room.name || room.summary || `Room ${room.id}`;
        el.activeMeta.textContent = `Room: ${room.chat_room_token} | Messages: ${messages.length}`;
        el.activeRoomToken.value = room.chat_room_token || '';

        if (!messages.length) {
          el.chatStream.innerHTML = '<div class="empty">No chats in this room yet. Send first symptom query.</div>';
          return;
        }

        el.chatStream.innerHTML = messages.map((msg) => {
          const roleLabel = msg.role === 'user' ? 'You' : 'Snoutiq';
          const cls = msg.role === 'user' ? 'user' : 'assistant';
          const emergency = msg.source?.emergency_status ? ` | ${msg.source.emergency_status}` : '';

          return `
            <div class="msg ${cls}">
              <div class="msg-meta">
                <span>${escapeHtml(roleLabel)}</span>
                <span>${escapeHtml(formatTime(msg.created_at))}${escapeHtml(emergency)}</span>
              </div>
              ${escapeHtml(msg.text)}
            </div>
          `;
        }).join('');

        el.chatStream.scrollTop = el.chatStream.scrollHeight;
      }

      function renderCtaLinks(meta) {
        const buttons = meta?.buttons || {};
        const links = [];

        ['primary', 'secondary'].forEach((key) => {
          const btn = buttons[key];
          if (!btn) return;
          const href = btn.deeplink || '#';
          const label = `${btn.label || key} (${btn.type || 'cta'})`;
          links.push(`<a class="api-link" href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer">${escapeHtml(label)}</a>`);
        });

        if (!links.length) {
          el.ctaLinks.innerHTML = '<div class="plain">No quick actions in latest response.</div>';
          return;
        }

        el.ctaLinks.innerHTML = links.join('');
      }

      function renderContextWindow(chats) {
        const messages = flattenChats(chats);
        if (!messages.length) {
          el.contextWindow.innerHTML = '<div class="ctx">No context available.</div>';
          return;
        }

        const recent = messages.slice(-12);
        el.contextWindow.innerHTML = recent.map((msg) => {
          const cls = msg.role === 'user' ? 'user' : 'assistant';
          const label = msg.role === 'user' ? 'You' : 'Snoutiq';
          return `<div class="ctx ${cls}"><b>${escapeHtml(label)}:</b> ${escapeHtml(compact(msg.text, 170))}</div>`;
        }).join('');
      }

      function renderDetail() {
        const meta = activeMeta();
        const chats = activeChats();
        const inferredTurns = countUserTurns(chats);

        if (!meta) {
          el.mRouting.textContent = '-';
          el.mSeverity.textContent = '-';
          el.mScore.textContent = '-';
          el.mTurn.textContent = String(inferredTurns || 0);
          el.mContextTurns.textContent = String(inferredTurns || 0);
          el.mBypass.textContent = '-';
          el.triageDetail.textContent = '-';
          el.chips.innerHTML = '<span class="chip">No triage metadata yet</span>';
          renderCtaLinks(null);
          renderContextWindow(chats);
          return;
        }

        const routing = String(meta.routing || '-').toLowerCase();
        const severity = String(meta.severity || '-').toLowerCase();
        const routingClass = routing === 'emergency' ? 'danger' : (routing === 'in_clinic' ? 'warn' : 'ok');
        const severityClass = severity === 'critical' ? 'danger' : (severity === 'moderate' ? 'warn' : 'ok');

        el.chips.innerHTML = `
          <span class="chip ${routingClass}">Routing: ${escapeHtml(meta.routing || '-')}</span>
          <span class="chip ${severityClass}">Severity: ${escapeHtml(meta.severity || '-')}</span>
          <span class="chip">Score: ${escapeHtml(String(meta.score ?? '-'))}</span>
          <span class="chip">Turn: ${escapeHtml(String(meta.turn ?? '-'))}</span>
        `;

        el.mRouting.textContent = stringify(meta.routing);
        el.mSeverity.textContent = stringify(meta.severity);
        el.mScore.textContent = stringify(meta.score);
        el.mTurn.textContent = stringify(meta.turn);
        el.mContextTurns.textContent = String(inferredTurns || 0);
        el.mBypass.textContent = meta.red_flag_bypass ? 'yes' : 'no';

        const detail = meta.triage_detail || {};
        el.triageDetail.textContent =
          `Possible causes: ${stringify(detail.possible_causes)}\n` +
          `Red flags: ${stringify(detail.red_flags_found)}\n` +
          `India context: ${stringify(detail.india_context)}\n` +
          `Safe wait (hours): ${stringify(detail.safe_to_wait_hours)}\n` +
          `Image observation: ${stringify(detail.image_observation)}`;

        renderCtaLinks(meta);
        renderContextWindow(chats);
      }

      function renderAll() {
        renderRooms();
        renderChat();
        renderDetail();

        const disabled = state.busy;
        el.btnSend.disabled = disabled;
        el.btnNewRoom.disabled = disabled;
        el.btnRefreshRooms.disabled = disabled;
        el.btnReloadRoom.disabled = disabled || !state.activeRoomToken;
        el.btnDeleteRoom.disabled = disabled || !state.activeRoomToken;
      }

      async function fetchRooms(options) {
        const opts = options || {};
        const preserveActive = opts.preserveActive !== false;
        const preferredToken = opts.preferredToken || null;
        const loadChats = opts.loadChats !== false;
        const silent = !!opts.silent;

        const userId = getUserId();
        if (!userId) {
          state.rooms = [];
          state.activeRoomToken = null;
          state.roomChats = {};
          state.roomMeta = {};
          renderAll();
          setStatus('user_id required to load rooms.', true);
          return;
        }

        if (!silent) setStatus('Loading rooms...');

        const res = await apiFetch('/chat/listRooms', 'GET', null, { user_id: userId });
        state.rooms = Array.isArray(res.rooms) ? res.rooms : [];

        if (!state.rooms.length) {
          state.activeRoomToken = null;
          renderAll();
          setStatus('No rooms found. Create New Room.', false);
          return;
        }

        if (preferredToken && state.rooms.some((r) => r.chat_room_token === preferredToken)) {
          state.activeRoomToken = preferredToken;
        } else if (!preserveActive || !state.activeRoomToken || !state.rooms.some((r) => r.chat_room_token === state.activeRoomToken)) {
          state.activeRoomToken = state.rooms[0].chat_room_token;
        }

        renderAll();

        if (loadChats && state.activeRoomToken) {
          await loadRoomChats(state.activeRoomToken, { silent: true });
        }

        if (!silent) setStatus(`Loaded ${state.rooms.length} room(s).`);
      }

      async function loadRoomChats(token, options) {
        const opts = options || {};
        const silent = !!opts.silent;
        if (!token) return;

        const userId = getUserId();
        if (!userId) {
          setStatus('user_id required.', true);
          return;
        }

        if (!silent) setStatus(`Loading room ${token} ...`);

        const res = await apiFetch(`/chat-rooms/${encodeURIComponent(token)}/chats`, 'GET', null, {
          user_id: userId,
          sort: 'asc'
        });

        const chats = Array.isArray(res.chats) ? res.chats : [];
        state.activeRoomToken = token;
        state.roomChats[token] = chats;

        const inferred = inferMetaFromChats(chats);
        const existing = state.roomMeta[token] || {};
        state.roomMeta[token] = {
          ...inferred,
          ...existing,
          turn: inferred.turn ?? existing.turn ?? 0,
          routing: (existing.routing && existing.routing !== '-') ? existing.routing : (inferred.routing ?? '-'),
          severity: (existing.severity && existing.severity !== '-') ? existing.severity : (inferred.severity ?? '-'),
          score: (existing.score !== undefined && existing.score !== null && existing.score !== '-')
            ? existing.score
            : (inferred.score ?? '-'),
          red_flag_bypass: existing.red_flag_bypass ?? inferred.red_flag_bypass ?? false,
          triage_detail: existing.triage_detail || inferred.triage_detail || {},
          buttons: existing.buttons || inferred.buttons || {}
        };

        renderAll();
        if (!silent) setStatus(`Loaded room ${token}.`);
      }

      async function createNewRoom() {
        if (state.busy) return;

        const userId = getUserId();
        if (!userId) {
          setStatus('user_id required to create room.', true);
          return;
        }

        const title = valOrNull(el.roomName.value) || `New chat - ${new Date().toLocaleString()}`;
        state.busy = true;
        renderAll();
        setStatus('Creating new room...');

        try {
          const res = await apiFetch('/chat-rooms/new', 'GET', null, {
            user_id: userId,
            title
          });

          if (!res.chat_room_token) {
            throw new Error('Room token not returned.');
          }

          el.roomName.value = '';
          await fetchRooms({
            preserveActive: false,
            preferredToken: res.chat_room_token,
            loadChats: true,
            silent: true
          });
          setStatus(`Room created in DB: ${res.chat_room_token}`);
        } catch (err) {
          setStatus(err.message || 'Failed to create room', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      }

      async function deleteRoom(token) {
        const targetToken = token || state.activeRoomToken;
        if (!targetToken) {
          setStatus('No room selected.', true);
          return;
        }

        const userId = getUserId();
        if (!userId) {
          setStatus('user_id required.', true);
          return;
        }

        const ok = window.confirm(`Delete room ${targetToken}? This will remove room chats too.`);
        if (!ok) return;

        state.busy = true;
        renderAll();
        setStatus(`Deleting room ${targetToken} ...`);

        try {
          await apiFetch(`/chat-rooms/${encodeURIComponent(targetToken)}`, 'DELETE', { user_id: userId });
          delete state.roomChats[targetToken];
          delete state.roomMeta[targetToken];

          await fetchRooms({
            preserveActive: false,
            loadChats: true,
            silent: true
          });
          setStatus(`Room deleted: ${targetToken}`);
        } catch (err) {
          setStatus(err.message || 'Failed to delete room', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      }

      async function sendMessage() {
        if (state.busy) return;

        const token = state.activeRoomToken;
        if (!token) {
          setStatus('Select or create a room first.', true);
          return;
        }

        const userId = getUserId();
        if (!userId) {
          setStatus('user_id is required.', true);
          return;
        }

        const message = String(el.messageInput.value || '').trim();
        if (!message) {
          setStatus('Please type a message.', true);
          return;
        }

        const payload = {
          user_id: userId,
          question: message,
          message: message,
          pet_id: Number(el.petId.value || 0),
          chat_room_token: token,
          context_token: token,
          pet_name: valOrNull(el.petName.value),
          species: valOrNull(el.species.value),
          pet_type: valOrNull(el.species.value),
          breed: valOrNull(el.breed.value),
          pet_breed: valOrNull(el.breed.value),
          pet_age: valOrNull(el.dob.value),
          sex: valOrNull(el.sex.value),
          neutered: valOrNull(el.neutered.value),
          pet_location: valOrNull(el.location.value),
          location: valOrNull(el.location.value)
        };

        state.busy = true;
        renderAll();
        setStatus('Sending message...');

        try {
          const res = await apiFetch('/chat/send', 'POST', payload);
          el.messageInput.value = '';

          const chats = state.roomChats[token] ? [...state.roomChats[token]] : [];
          chats.push({
            question: message,
            answer: res.response?.message || res.chat?.answer || '',
            emergency_status: res.emergency_status || null,
            created_at: new Date().toISOString()
          });
          state.roomChats[token] = chats;
          state.roomMeta[token] = extractMetaFromResponse(res, countUserTurns(chats));

          renderAll();

          await fetchRooms({
            preserveActive: true,
            preferredToken: token,
            loadChats: true,
            silent: true
          });
          setStatus('Message sent and room updated from DB.');
        } catch (err) {
          setStatus(err.message || 'Failed to send message', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      }

      el.roomsList.addEventListener('click', async function (event) {
        const deleteToken = event.target.getAttribute('data-room-delete');
        if (deleteToken) {
          event.stopPropagation();
          await deleteRoom(deleteToken);
          return;
        }

        const card = event.target.closest('[data-room-token]');
        if (!card) return;
        const token = card.getAttribute('data-room-token');
        if (!token || token === state.activeRoomToken) return;

        state.activeRoomToken = token;
        renderAll();
        try {
          await loadRoomChats(token, { silent: false });
        } catch (err) {
          setStatus(err.message || 'Failed to open room', true);
        }
      });

      el.btnSend.addEventListener('click', sendMessage);
      el.btnNewRoom.addEventListener('click', createNewRoom);
      el.btnRefreshRooms.addEventListener('click', async function () {
        try {
          state.busy = true;
          renderAll();
          await fetchRooms({ preserveActive: true, loadChats: true, silent: false });
        } catch (err) {
          setStatus(err.message || 'Failed to refresh rooms', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      });
      el.btnReloadRoom.addEventListener('click', async function () {
        if (!state.activeRoomToken) return;
        try {
          state.busy = true;
          renderAll();
          await loadRoomChats(state.activeRoomToken, { silent: false });
        } catch (err) {
          setStatus(err.message || 'Failed to reload room', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      });
      el.btnDeleteRoom.addEventListener('click', function () {
        deleteRoom(state.activeRoomToken);
      });

      el.userId.addEventListener('change', async function () {
        try {
          state.busy = true;
          state.rooms = [];
          state.activeRoomToken = null;
          state.roomChats = {};
          state.roomMeta = {};
          renderAll();
          await fetchRooms({ preserveActive: false, loadChats: true, silent: false });
        } catch (err) {
          setStatus(err.message || 'Failed to load rooms for user', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      });

      el.messageInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          sendMessage();
        }
      });

      (async function init() {
        renderAll();
        setStatus('Loading rooms from DB...');
        try {
          state.busy = true;
          renderAll();
          await fetchRooms({ preserveActive: false, loadChats: true, silent: true });
          setStatus('Ready. Rooms are loaded from chat_rooms table.');
        } catch (err) {
          setStatus(err.message || 'Failed to initialize rooms', true);
        } finally {
          state.busy = false;
          renderAll();
        }
      })();
    })();
  </script>
</body>
</html>
