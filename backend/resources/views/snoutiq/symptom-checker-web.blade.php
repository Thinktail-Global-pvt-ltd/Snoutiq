<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Snoutiq Symptom Checker Web</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-1: #f2f7f6;
      --bg-2: #e6f1ef;
      --ink: #123139;
      --muted: #486872;
      --card: #ffffff;
      --line: #d4e6e2;
      --primary: #0b7285;
      --primary-2: #0f9eb7;
      --danger: #c2410c;
      --ok: #166534;
      --warn: #b45309;
      --chip: #e4f4f7;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      color: var(--ink);
      font-family: "Manrope", sans-serif;
      background:
        radial-gradient(1000px 400px at -10% -20%, #c9ece5 0%, transparent 60%),
        radial-gradient(900px 420px at 110% -10%, #bfe8f4 0%, transparent 58%),
        linear-gradient(140deg, var(--bg-1) 0%, var(--bg-2) 100%);
    }

    .wrap {
      max-width: 1360px;
      margin: 0 auto;
      padding: 24px 18px 34px;
      display: grid;
      gap: 16px;
      grid-template-columns: 1.05fr 1.2fr;
    }

    @media (max-width: 1080px) {
      .wrap { grid-template-columns: 1fr; }
    }

    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 8px 28px rgba(18, 49, 57, 0.08);
      overflow: hidden;
    }

    .hd {
      padding: 16px 18px;
      border-bottom: 1px solid var(--line);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .hd h2 {
      margin: 0;
      font-family: "Space Grotesk", sans-serif;
      font-size: 20px;
      letter-spacing: .2px;
    }

    .sub {
      margin: 4px 0 0;
      color: var(--muted);
      font-size: 13px;
    }

    .bd { padding: 16px 18px 18px; }

    .grid {
      display: grid;
      gap: 12px;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    @media (max-width: 760px) {
      .grid { grid-template-columns: 1fr; }
    }

    label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: .3px;
    }

    input, textarea, select {
      width: 100%;
      border: 1px solid #c7d9d7;
      background: #fbfdfd;
      border-radius: 10px;
      padding: 10px 11px;
      font: inherit;
      font-size: 14px;
      color: var(--ink);
      outline: none;
      transition: border-color .16s ease, box-shadow .16s ease;
    }

    input:focus, textarea:focus, select:focus {
      border-color: var(--primary-2);
      box-shadow: 0 0 0 3px rgba(15, 158, 183, .14);
    }

    textarea { min-height: 94px; resize: vertical; }

    .row-span-2 { grid-column: span 2; }
    @media (max-width: 760px) { .row-span-2 { grid-column: span 1; } }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }

    button {
      border: 0;
      border-radius: 10px;
      padding: 10px 12px;
      font: inherit;
      font-weight: 700;
      cursor: pointer;
      transition: transform .05s ease, opacity .15s ease;
    }
    button:active { transform: translateY(1px); }
    .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-2)); color: #fff; }
    .btn-soft { background: #edf7f6; color: var(--ink); border: 1px solid var(--line); }
    .btn-warn { background: #fff5eb; color: var(--warn); border: 1px solid #ffd9b5; }
    .btn-danger { background: #fff1f0; color: var(--danger); border: 1px solid #ffd0cb; }

    .status {
      margin-top: 12px;
      padding: 10px 12px;
      border-radius: 10px;
      background: #edf8f7;
      border: 1px solid #cae9e5;
      color: #174f59;
      font-size: 13px;
      white-space: pre-wrap;
    }
    .status.err { background: #fff1f0; border-color: #ffd0cb; color: #9b1c1c; }

    .chips {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .chip {
      background: var(--chip);
      border: 1px solid #cde8ed;
      border-radius: 999px;
      padding: 6px 11px;
      font-size: 12px;
      font-weight: 700;
    }
    .chip.ok { background: #e9f9f1; border-color: #c8efd8; color: var(--ok); }
    .chip.warn { background: #fff7e9; border-color: #ffe5bf; color: #8a4b05; }
    .chip.danger { background: #fff1f0; border-color: #ffd0cb; color: var(--danger); }

    .summary-grid {
      margin-top: 12px;
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }
    @media (max-width: 860px) { .summary-grid { grid-template-columns: 1fr; } }
    .metric {
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 10px 12px;
      background: #fcfefe;
    }
    .metric b { display: block; font-size: 12px; color: var(--muted); margin-bottom: 4px; text-transform: uppercase; letter-spacing: .3px; }
    .metric span { font-size: 15px; font-weight: 700; }

    .outbox {
      margin-top: 14px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: #fbfefe;
      overflow: hidden;
    }
    .outbox h4 {
      margin: 0;
      padding: 10px 12px;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .4px;
      color: var(--muted);
      border-bottom: 1px solid var(--line);
    }
    .outbox .content { padding: 12px; font-size: 14px; line-height: 1.46; white-space: pre-wrap; }

    .history {
      max-height: 330px;
      overflow: auto;
      display: grid;
      gap: 10px;
      padding: 12px;
      background: #f7fbfb;
    }
    .bubble {
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 10px 12px;
      background: #fff;
      font-size: 13px;
    }
    .bubble.me { border-color: #d0e8ef; background: #f2fbff; }
    .bubble b { display: block; font-size: 11px; color: var(--muted); margin-bottom: 4px; text-transform: uppercase; letter-spacing: .4px; }

    .link-btns {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 8px;
    }

    .api-link {
      display: inline-flex;
      gap: 8px;
      align-items: center;
      text-decoration: none;
      border: 1px solid var(--line);
      background: #fff;
      border-radius: 10px;
      padding: 9px 11px;
      color: var(--ink);
      font-size: 13px;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <section class="card">
      <header class="hd">
        <div>
          <h2>Symptom Checker Web</h2>
          <p class="sub">Use optimized symptom APIs with full session controls.</p>
        </div>
      </header>
      <div class="bd">
        <div class="grid">
          <div>
            <label for="apiToken">Bearer Token (Optional)</label>
            <input id="apiToken" type="text" placeholder="Leave blank if not required">
          </div>
          <div>
            <label for="sessionId">Session ID</label>
            <input id="sessionId" type="text" placeholder="Auto created or set by New Room">
          </div>
          <div>
            <label for="userId">User ID</label>
            <input id="userId" type="number" value="1247" min="1">
          </div>
          <div>
            <label for="petId">Pet ID</label>
            <input id="petId" type="number" value="456" min="0">
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
            <label for="dob">DOB (YYYY-MM-DD)</label>
            <input id="dob" type="date">
          </div>
          <div>
            <label for="sex">Sex</label>
            <select id="sex">
              <option value="">unknown</option>
              <option value="male" selected>male</option>
              <option value="female">female</option>
            </select>
          </div>
          <div>
            <label for="neutered">Neutered</label>
            <select id="neutered">
              <option value="unknown">unknown</option>
              <option value="yes">yes</option>
              <option value="no" selected>no</option>
            </select>
          </div>
          <div class="row-span-2">
            <label for="location">Location</label>
            <input id="location" type="text" value="Gurgaon">
          </div>
          <div class="row-span-2">
            <label for="message">Message</label>
            <textarea id="message" placeholder="Describe symptoms..."></textarea>
          </div>
        </div>

        <div class="actions">
          <button id="btnCheck" class="btn-primary" type="button">Send (symptom-check)</button>
          <button id="btnFollowup" class="btn-soft" type="button">Followup</button>
          <button id="btnLoadSession" class="btn-soft" type="button">Load Session</button>
          <button id="btnRestart" class="btn-warn" type="button">Restart Session</button>
          <button id="btnNewRoom" class="btn-soft" type="button">New Room</button>
          <button id="btnResetAll" class="btn-danger" type="button">Reset Everything</button>
        </div>

        <div id="status" class="status">Ready.</div>
      </div>
    </section>

    <section class="card">
      <header class="hd">
        <div>
          <h2>Live Response</h2>
          <p class="sub">Routing, severity, triage details, deeplinks, and conversation timeline.</p>
        </div>
      </header>
      <div class="bd">
        <div class="chips" id="chips">
          <span class="chip">No response yet</span>
        </div>

        <div class="summary-grid">
          <div class="metric"><b>Routing</b><span id="mRouting">-</span></div>
          <div class="metric"><b>Severity</b><span id="mSeverity">-</span></div>
          <div class="metric"><b>Score</b><span id="mScore">-</span></div>
          <div class="metric"><b>Turn</b><span id="mTurn">-</span></div>
        </div>

        <div class="outbox">
          <h4>Main Message</h4>
          <div class="content" id="mainMessage">-</div>
        </div>

        <div class="outbox">
          <h4>Action Guidance</h4>
          <div class="content" id="actionMessage">-</div>
        </div>

        <div class="outbox">
          <h4>Triage Detail</h4>
          <div class="content" id="triageDetail">-</div>
        </div>

        <div class="outbox">
          <h4>CTA Buttons</h4>
          <div class="content">
            <div class="link-btns" id="ctaLinks"></div>
          </div>
        </div>

        <div class="outbox">
          <h4>Session History</h4>
          <div id="history" class="history">
            <div class="bubble"><b>System</b>No conversation yet.</div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <script>
    (function () {
      const backendPrefix = window.location.pathname.startsWith('/backend/') ? '/backend' : '';
      const API_BASE = `${window.location.origin}${backendPrefix}/api`;

      const el = {
        apiToken: document.getElementById('apiToken'),
        sessionId: document.getElementById('sessionId'),
        userId: document.getElementById('userId'),
        petId: document.getElementById('petId'),
        petName: document.getElementById('petName'),
        species: document.getElementById('species'),
        breed: document.getElementById('breed'),
        dob: document.getElementById('dob'),
        sex: document.getElementById('sex'),
        neutered: document.getElementById('neutered'),
        location: document.getElementById('location'),
        message: document.getElementById('message'),
        status: document.getElementById('status'),
        chips: document.getElementById('chips'),
        mRouting: document.getElementById('mRouting'),
        mSeverity: document.getElementById('mSeverity'),
        mScore: document.getElementById('mScore'),
        mTurn: document.getElementById('mTurn'),
        mainMessage: document.getElementById('mainMessage'),
        actionMessage: document.getElementById('actionMessage'),
        triageDetail: document.getElementById('triageDetail'),
        ctaLinks: document.getElementById('ctaLinks'),
        history: document.getElementById('history'),
        btnCheck: document.getElementById('btnCheck'),
        btnFollowup: document.getElementById('btnFollowup'),
        btnLoadSession: document.getElementById('btnLoadSession'),
        btnRestart: document.getElementById('btnRestart'),
        btnNewRoom: document.getElementById('btnNewRoom'),
        btnResetAll: document.getElementById('btnResetAll')
      };

      function setStatus(message, isError) {
        el.status.textContent = message;
        el.status.classList.toggle('err', !!isError);
      }

      function getAuthHeader() {
        const token = (el.apiToken.value || '').trim();
        return token ? { Authorization: `Bearer ${token}` } : {};
      }

      function roomId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
          return `room_${window.crypto.randomUUID()}`;
        }
        return `room_${Date.now()}_${Math.random().toString(16).slice(2, 8)}`;
      }

      async function apiFetch(path, method, body) {
        const headers = Object.assign(
          { Accept: 'application/json', 'Content-Type': 'application/json' },
          getAuthHeader()
        );

        const res = await fetch(`${API_BASE}${path}`, {
          method,
          headers,
          body: body ? JSON.stringify(body) : undefined
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
          const msg = json.message || json.error || `HTTP ${res.status}`;
          throw new Error(msg);
        }
        return json;
      }

      function stringify(value) {
        if (value === null || value === undefined || value === '') return '-';
        if (Array.isArray(value)) return value.join(', ');
        if (typeof value === 'object') return JSON.stringify(value, null, 2);
        return String(value);
      }

      function renderHistory(items) {
        if (!Array.isArray(items) || !items.length) {
          el.history.innerHTML = '<div class="bubble"><b>System</b>No conversation yet.</div>';
          return;
        }

        el.history.innerHTML = items.map((item) => {
          const userText = item.user || item.message || '-';
          const aiText = item.assistant || item.response || '-';
          return `
            <div class="bubble me"><b>Pet Parent</b>${escapeHtml(userText)}</div>
            <div class="bubble"><b>Snoutiq</b>${escapeHtml(aiText)}</div>
          `;
        }).join('');
        el.history.scrollTop = el.history.scrollHeight;
      }

      function renderResponse(data) {
        const routing = data.routing || data.decision || '-';
        const severity = data.severity || data.symptom_analysis?.severity || '-';
        const score = data.score ?? '-';
        const turn = data.turn ?? '-';
        const response = data.response || {};
        const detail = data.triage_detail || {};
        const buttons = data.buttons || {};

        el.mRouting.textContent = routing;
        el.mSeverity.textContent = severity;
        el.mScore.textContent = String(score);
        el.mTurn.textContent = String(turn);
        el.mainMessage.textContent = stringify(response.message || data.chat?.answer || '-');
        el.actionMessage.textContent =
          `Do now: ${stringify(response.do_now)}\n` +
          `Time sensitivity: ${stringify(response.time_sensitivity)}\n` +
          `Watch for: ${stringify(response.what_to_watch)}`;

        el.triageDetail.textContent =
          `Possible causes: ${stringify(detail.possible_causes)}\n` +
          `Red flags: ${stringify(detail.red_flags_found)}\n` +
          `India context: ${stringify(detail.india_context)}\n` +
          `Safe wait (hours): ${stringify(detail.safe_to_wait_hours)}\n` +
          `Image observation: ${stringify(detail.image_observation)}`;

        el.ctaLinks.innerHTML = '';
        ['primary', 'secondary'].forEach((key) => {
          const btn = buttons[key];
          if (!btn) return;
          const a = document.createElement('a');
          a.className = 'api-link';
          a.href = btn.deeplink || '#';
          a.target = '_blank';
          a.rel = 'noopener noreferrer';
          a.textContent = `${btn.label || key} (${btn.type || 'cta'})`;
          el.ctaLinks.appendChild(a);
        });

        const chips = [];
        chips.push(`<span class="chip ${routing === 'emergency' ? 'danger' : 'ok'}">Routing: ${escapeHtml(routing)}</span>`);
        chips.push(`<span class="chip ${severity === 'critical' ? 'danger' : 'warn'}">Severity: ${escapeHtml(severity)}</span>`);
        chips.push(`<span class="chip">Session: ${escapeHtml(el.sessionId.value || '-')}</span>`);
        chips.push(`<span class="chip">Bypass: ${data.red_flag_bypass ? 'yes' : 'no'}</span>`);
        el.chips.innerHTML = chips.join('');

        if (Array.isArray(data.state?.history)) {
          renderHistory(data.state.history);
        } else if (data.response?.message) {
          const existing = el.history.innerHTML.includes('No conversation yet.') ? [] : Array.from(el.history.querySelectorAll('.bubble')).map(b => b.textContent);
          const next = [];
          if (existing.length === 0) {
            const msg = (el.message.value || '').trim();
            if (msg) next.push({ user: msg, assistant: data.response.message });
          }
          renderHistory(next);
        }
      }

      function payloadForCheck() {
        return {
          user_id: Number(el.userId.value || 0),
          pet_id: Number(el.petId.value || 0),
          message: (el.message.value || '').trim(),
          session_id: (el.sessionId.value || '').trim() || null,
          pet_name: (el.petName.value || '').trim() || null,
          species: (el.species.value || '').trim() || null,
          breed: (el.breed.value || '').trim() || null,
          dob: (el.dob.value || '').trim() || null,
          sex: (el.sex.value || '').trim() || null,
          neutered: (el.neutered.value || '').trim() || null,
          location: (el.location.value || '').trim() || null
        };
      }

      async function runCheck() {
        try {
          setStatus('Calling /symptom-check ...');
          const body = payloadForCheck();
          if (!body.user_id || !body.message) {
            throw new Error('user_id and message are required');
          }
          const res = await apiFetch('/symptom-check', 'POST', body);
          if (res.session_id) el.sessionId.value = res.session_id;
          renderResponse(res);
          setStatus(`Success. Session: ${res.session_id || '-'}`);
        } catch (err) {
          setStatus(err.message, true);
        }
      }

      async function runFollowup() {
        try {
          const sid = (el.sessionId.value || '').trim();
          if (!sid) throw new Error('Session ID required for followup');
          const message = (el.message.value || '').trim();
          if (!message) throw new Error('Message is required');
          setStatus('Calling /symptom-followup ...');
          const res = await apiFetch('/symptom-followup', 'POST', {
            user_id: Number(el.userId.value || 0),
            session_id: sid,
            message
          });
          renderResponse(res);
          setStatus(`Followup success for ${sid}`);
        } catch (err) {
          setStatus(err.message, true);
        }
      }

      async function runLoadSession() {
        try {
          const sid = (el.sessionId.value || '').trim();
          if (!sid) throw new Error('Session ID required');
          setStatus('Loading /symptom-session ...');
          const res = await apiFetch(`/symptom-session/${encodeURIComponent(sid)}`, 'GET');
          renderResponse(res);
          renderHistory(res.state?.history || []);
          setStatus(`Session loaded: ${sid}`);
        } catch (err) {
          setStatus(err.message, true);
        }
      }

      async function runRestart() {
        try {
          const sid = (el.sessionId.value || '').trim();
          if (!sid) throw new Error('Session ID required for restart');
          setStatus('Resetting current session ...');
          await apiFetch(`/symptom-session/${encodeURIComponent(sid)}/reset`, 'POST');
          renderHistory([]);
          el.mainMessage.textContent = '-';
          el.actionMessage.textContent = '-';
          el.triageDetail.textContent = '-';
          el.chips.innerHTML = `<span class="chip">Session restarted: ${escapeHtml(sid)}</span>`;
          setStatus(`Session restarted: ${sid}`);
        } catch (err) {
          setStatus(err.message, true);
        }
      }

      function runNewRoom() {
        const sid = roomId();
        el.sessionId.value = sid;
        renderHistory([]);
        el.mainMessage.textContent = '-';
        el.actionMessage.textContent = '-';
        el.triageDetail.textContent = '-';
        el.chips.innerHTML = `<span class="chip ok">New room ready: ${escapeHtml(sid)}</span>`;
        setStatus(`New room created locally: ${sid}`);
      }

      async function runResetAll() {
        try {
          const sid = (el.sessionId.value || '').trim();
          if (sid) {
            await apiFetch(`/symptom-session/${encodeURIComponent(sid)}/reset`, 'POST');
          }
        } catch (_) {
        }

        el.sessionId.value = '';
        el.message.value = '';
        el.dob.value = '';
        renderHistory([]);
        el.mainMessage.textContent = '-';
        el.actionMessage.textContent = '-';
        el.triageDetail.textContent = '-';
        el.mRouting.textContent = '-';
        el.mSeverity.textContent = '-';
        el.mScore.textContent = '-';
        el.mTurn.textContent = '-';
        el.ctaLinks.innerHTML = '';
        el.chips.innerHTML = '<span class="chip">All cleared</span>';
        setStatus('Everything reset.');
      }

      function escapeHtml(str) {
        return String(str)
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }

      el.btnCheck.addEventListener('click', runCheck);
      el.btnFollowup.addEventListener('click', runFollowup);
      el.btnLoadSession.addEventListener('click', runLoadSession);
      el.btnRestart.addEventListener('click', runRestart);
      el.btnNewRoom.addEventListener('click', runNewRoom);
      el.btnResetAll.addEventListener('click', runResetAll);
    })();
  </script>
</body>
</html>

