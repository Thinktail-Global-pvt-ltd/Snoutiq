<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="/backend">
    <title>{{ ucfirst($role) }} Socket</title>
    <style>
        body {
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
            background:#f5f7fb;
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:flex-start;
            justify-content:center;
            padding:2rem;
        }
        .panel {
            width:100%;
            max-width:1000px;
            background:#fff;
            border-radius:14px;
            box-shadow:0 20px 48px rgba(0,0,0,.08);
            padding:2rem;
        }
        h1 { margin:0 0 .5rem; }
        .subtitle { margin:0 0 1.5rem; color:#4b5563; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:1rem; }
        label { font-weight:600; display:block; margin-bottom:.25rem; }
        input, button { width:100%; border-radius:10px; border:1px solid #d2d6dc; padding:.65rem; font-size:1rem; }
        button { border:none; background:#2563eb; color:#fff; font-weight:600; cursor:pointer; }
        button.secondary { background:#6b7280; }
        button.ghost { background:#e5e7eb; color:#111827; border:1px solid #d1d5db; }
        .chips { display:flex; gap:.5rem; flex-wrap:wrap; margin:1rem 0; }
        .chip { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .75rem; border-radius:999px; font-weight:600; }
        .chip.primary { background:#e0e7ff; color:#1d4ed8; }
        .chip.gray { background:#f3f4f6; color:#111827; }
        .log { margin-top:1rem; background:#0f172a; color:#e0f2fe; padding:1rem; border-radius:12px; font-family:Consolas,ui-monospace,monospace; min-height:160px; max-height:300px; overflow:auto; }
        .footer { margin-top:1rem; display:flex; gap:.75rem; flex-wrap:wrap; }
        .modal-backdrop {
            position:fixed; inset:0;
            background:rgba(0,0,0,.35);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:30;
        }
        .modal {
            background:#fff;
            padding:1.5rem;
            border-radius:14px;
            box-shadow:0 30px 60px rgba(0,0,0,.18);
            max-width:420px;
            width:90%;
        }
        .modal h3 { margin:0 0 .35rem; }
        .modal p { margin:.15rem 0; color:#374151; }
        .modal .actions { margin-top:1rem; display:flex; gap:.75rem; flex-wrap:wrap; }
    </style>
</head>
<body data-role="{{ $role }}">
    <div class="panel">
        <h1>{{ ucfirst($role) }} Socket</h1>
        <p class="subtitle">
            Two tabs: one opens <code>/dev/socket-doctor</code>, the other <code>/dev/socket-patient</code>.
            Click “Call peer” to emit a call-session update; both pages are auto-subscribed to their Reverb channels.
        </p>

        <div class="chips">
            <span class="chip primary">You: {{ $role }} #{{ $selfId }}</span>
            <span class="chip gray">Peer: {{ $role === 'doctor' ? 'patient' : 'doctor' }} #{{ $peerId }}</span>
            <span class="chip gray">Call session: {{ $callSession }}</span>
        </div>

        <div class="grid">
            <div>
                <label for="selfId">Your ID</label>
                <input id="selfId" type="number" min="0" value="{{ $selfId }}">
            </div>
            <div>
                <label for="peerId">Peer ID</label>
                <input id="peerId" type="number" min="0" value="{{ $peerId }}">
            </div>
            <div>
                <label for="callSession">Call session</label>
                <input id="callSession" type="text" value="{{ $callSession }}">
            </div>
            <div>
                <label for="channelName">Channel</label>
                <input id="channelName" type="text" value="{{ $channel }}">
            </div>
        </div>

        <div class="footer" style="margin-top:1.25rem;">
            <button id="subscribeSelf">Subscribe to my channel</button>
            <button id="subscribeCall" class="ghost">Subscribe to call channel</button>
            <button id="sendCall" class="secondary">Call peer (POST /api/socket/call-sessions)</button>
            <button id="clearLog" class="ghost">Clear log</button>
        </div>

        <div class="log" id="log"><p style="margin:0;">Waiting for events…</p></div>
    </div>

    <div class="modal-backdrop" id="callModal">
        <div class="modal">
            <h3>Incoming call</h3>
            <p id="callMeta"></p>
            <div class="actions">
                <button id="acceptCall">OK, got it</button>
                <button id="dismissCall" class="ghost">Dismiss</button>
            </div>
        </div>
    </div>

    @vite('resources/js/echo.js')
    <script>
        const role = document.body.dataset.role;
        const API_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
        const selfIdInput = document.getElementById('selfId');
        const peerIdInput = document.getElementById('peerId');
        const callSessionInput = document.getElementById('callSession');
        const channelInput = document.getElementById('channelName');
        const logEl = document.getElementById('log');
        const channels = new Map();
        const callModal = document.getElementById('callModal');
        const callMeta = document.getElementById('callMeta');
        const dismissCall = document.getElementById('dismissCall');
        const acceptCall = document.getElementById('acceptCall');
        let lastPollHash = null;
        let pollTimer = null;

        function log(message, detail = '') {
            const line = document.createElement('div');
            line.innerHTML = `<strong>${message}</strong>${detail ? '<br><small>'+detail+'</small>' : ''}`;
            logEl.appendChild(line);
            logEl.scrollTop = logEl.scrollHeight;
        }

        function showCallPopup(payload, channelName) {
            const otherParty = role === 'doctor' ? (payload.patientId ?? '') : (payload.doctorId ?? '');
            callMeta.textContent = `Call ${payload.callId || ''} on ${channelName}. Peer ID: ${otherParty}`;
            callModal.style.display = 'flex';
        }

        function hideCallPopup() {
            callModal.style.display = 'none';
        }

        function joinChannel(name) {
            if (!window.Echo) {
                log('Echo not ready yet.');
                return;
            }
            if (channels.has(name)) {
                window.Echo.leave(name);
                channels.delete(name);
            }
            const ch = window.Echo.channel(name);
            ch.listen('.call-session.stored', (payload) => {
                log(`${name} → stored`, JSON.stringify(payload));
                showCallPopup(payload, name);
            });
            ch.listen('.call-session.expired', (payload) => log(`${name} → expired`, JSON.stringify(payload)));
            ch.listen('.call-session.status_update', (payload) => log(`${name} → status`, JSON.stringify(payload)));
            channels.set(name, ch);
            log(`Listening on ${name}`);
        }

        function subscribeSelf() {
            const id = selfIdInput.value.trim();
            if (!id) { log('Enter your ID before subscribing.'); return; }
            joinChannel(`${role}-${id}`);
        }

        function subscribeCall() {
            const callId = callSessionInput.value.trim();
            if (!callId) { log('Enter a call session first.'); return; }
            joinChannel(`call-${callId}`);
        }

        async function sendCall() {
            const callId = callSessionInput.value.trim() || `call_${Date.now()}`;
            const selfId = Number(selfIdInput.value) || 0;
            const peerId = Number(peerIdInput.value) || 0;
            const doctorId = role === 'doctor' ? selfId : peerId;
            const patientId = role === 'patient' ? selfId : peerId;
            const payload = {
                call_session: callId,
                doctor_id: doctorId,
                patient_id: patientId,
                channel: channelInput.value || 'video',
            };

            try {
                const res = await fetch(`${API_BASE}/api/socket/call-sessions`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const body = await res.json();
                log('call-sessions POST', JSON.stringify(body));
            } catch (error) {
                log('error', error.message);
            }
        }

        document.getElementById('subscribeSelf').addEventListener('click', (e) => {
            e.preventDefault();
            subscribeSelf();
        });
        document.getElementById('subscribeCall').addEventListener('click', (e) => {
            e.preventDefault();
            subscribeCall();
        });
        document.getElementById('sendCall').addEventListener('click', (e) => {
            e.preventDefault();
            sendCall();
        });
        document.getElementById('clearLog').addEventListener('click', (e) => {
            e.preventDefault();
            logEl.innerHTML = '<p style="margin:0;">Waiting for events…</p>';
        });

        dismissCall.addEventListener('click', hideCallPopup);
        acceptCall.addEventListener('click', hideCallPopup);

        function isWsConnected() {
            const conn = window.Echo?.connector?.pusher?.connection;
            return conn && conn.state === 'connected';
        }

        function waitForEchoAndAutoSubscribe(attempt = 0) {
            if (window.Echo) {
                subscribeSelf();
                subscribeCall();
                return;
            }
            if (attempt < 6) {
                log('Echo not ready yet.');
                setTimeout(() => waitForEchoAndAutoSubscribe(attempt + 1), 600);
            }
        }

        waitForEchoAndAutoSubscribe();

        async function pollCallSession() {
            const callId = callSessionInput.value.trim();
            if (!callId || isWsConnected()) return;
            try {
                const res = await fetch(`${API_BASE}/api/socket/call-sessions?call_id=${encodeURIComponent(callId)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const body = await res.json();
                const normalized = { ...body };
                delete normalized.timestamp;
                const hash = JSON.stringify(normalized);
                if (body && hash !== lastPollHash) {
                    lastPollHash = hash;
                    showCallPopup(body, `call-${callId}`);
                    log('polled call update', JSON.stringify(body));
                    // stop polling after first hit
                    clearInterval(pollTimer);
                }
            } catch (err) {
                // ignore polling errors locally
            }
        }

        // Poll as a fallback in case websockets are blocked (stops after first hit)
        pollTimer = setInterval(pollCallSession, 5000);
    </script>
</body>
</html>
