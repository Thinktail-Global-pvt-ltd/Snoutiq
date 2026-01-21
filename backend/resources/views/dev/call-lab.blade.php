<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="/backend">
    <title>Call Lab (Reverb)</title>
    <style>
        body { font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:#f7f7fb; margin:0; padding:2rem; }
        .card { background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 18px 40px rgba(0,0,0,.06); margin-bottom:1rem; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; }
        label { font-weight:600; display:block; margin-bottom:.25rem; }
        input, textarea, button { width:100%; border:1px solid #d6d8dd; border-radius:10px; padding:.65rem; font-size:1rem; }
        button { background:#2563eb; color:#fff; border:none; cursor:pointer; font-weight:600; }
        button.secondary { background:#6b7280; }
        h2 { margin:0 0 .5rem; }
        .log { background:#0f172a; color:#e0f2fe; padding:1rem; border-radius:10px; min-height:160px; max-height:300px; overflow:auto; font-family:Consolas,ui-monospace,monospace; }
        .row { display:flex; gap:.75rem; flex-wrap:wrap; }
        .chip { display:inline-flex; gap:.35rem; padding:.35rem .7rem; border-radius:999px; background:#e0e7ff; color:#1d4ed8; font-weight:600; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Call Lab</h2>
        <p>Test Reverb call APIs without the Socket.IO server. Provide Bearer token (Sanctum) if auth is required.</p>
        <div class="grid">
            <div>
                <label for="patientId">Patient ID</label>
                <input id="patientId" type="number" value="2">
            </div>
            <div>
                <label for="doctorId">Doctor ID</label>
                <input id="doctorId" type="number" value="1">
            </div>
            <div>
                <label for="callId">Call ID (for accept/reject/end/cancel)</label>
                <input id="callId" type="text" placeholder="leave blank to request new call">
            </div>
            <div>
                <label for="channel">Channel</label>
                <input id="channel" type="text" value="video">
            </div>
            <div>
                <label for="token">Bearer Token (optional)</label>
                <input id="token" type="text" placeholder="ey...">
            </div>
        </div>
        <div class="row" style="margin-top:1rem;">
            <button id="heartbeat">Doctor Heartbeat</button>
            <button id="request">Request Call</button>
            <button id="accept">Accept</button>
            <button id="reject" class="secondary">Reject</button>
            <button id="cancel" class="secondary">Cancel</button>
            <button id="end" class="secondary">End</button>
        </div>
        <div class="chip" id="lastCall">Last call: none</div>
    </div>

    <div class="card">
        <h3>Logs</h3>
        <div class="log" id="log"><p style="margin:0;">Waiting…</p></div>
        <div class="row" style="margin-top:.5rem;">
            <button id="clearLog" class="secondary">Clear log</button>
        </div>
    </div>

    <script>
        const metaBase = (document.querySelector('meta[name="app-base"]')?.content || '').trim();
        // If the current path is not under the meta base, fall back to no prefix (helps local dev without /backend)
        const API_BASE = metaBase && window.location.pathname.startsWith(metaBase) ? metaBase : '';
        const logEl = document.getElementById('log');
        const lastCallEl = document.getElementById('lastCall');

        const headers = () => {
            const token = document.getElementById('token').value.trim();
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            };
        };

        const log = (msg, detail = '') => {
            const entry = document.createElement('div');
            entry.innerHTML = `<strong>${msg}</strong>${detail ? '<br><small>'+detail+'</small>' : ''}`;
            logEl.appendChild(entry);
            logEl.scrollTop = logEl.scrollHeight;
        };

        const setLastCall = (id) => {
            lastCallEl.textContent = `Last call: ${id || 'none'}`;
            if (id) document.getElementById('callId').value = id;
        };

        const getIds = () => ({
            patientId: Number(document.getElementById('patientId').value) || 0,
            doctorId: Number(document.getElementById('doctorId').value) || 0,
            callId: document.getElementById('callId').value.trim(),
            channel: document.getElementById('channel').value.trim() || 'video',
        });

        const post = async (path, body) => {
            const res = await fetch(`${API_BASE}${path}`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(body || {}),
            });
            const data = await res.json().catch(() => ({}));
            log(`${path} → ${res.status}`, JSON.stringify(data));
            return { res, data };
        };

        document.getElementById('heartbeat').onclick = async () => {
            const { doctorId } = getIds();
            const { res, data } = await post('/api/realtime/heartbeat', { doctor_id: doctorId });
            if (res.ok && data?.doctor_id) {
                log('Heartbeat ok', `doctor=${data.doctor_id}`);
            }
        };

        document.getElementById('request').onclick = async () => {
            const { patientId, channel } = getIds();
            const { res, data } = await post('/api/calls/request', { patient_id: patientId, channel });
            if (res.ok && data?.call_id) setLastCall(data.call_id);
        };

        document.getElementById('accept').onclick = async () => {
            const { callId } = getIds();
            if (!callId) return log('accept', 'callId required');
            await post(`/api/calls/${callId}/accept`, {});
        };

        document.getElementById('reject').onclick = async () => {
            const { callId } = getIds();
            if (!callId) return log('reject', 'callId required');
            await post(`/api/calls/${callId}/reject`, {});
        };

        document.getElementById('cancel').onclick = async () => {
            const { callId } = getIds();
            if (!callId) return log('cancel', 'callId required');
            await post(`/api/calls/${callId}/cancel`, {});
        };

        document.getElementById('end').onclick = async () => {
            const { callId } = getIds();
            if (!callId) return log('end', 'callId required');
            await post(`/api/calls/${callId}/end`, {});
        };

        document.getElementById('clearLog').onclick = () => {
            logEl.innerHTML = '<p style="margin:0;">Waiting…</p>';
        };
    </script>
</body>
</html>
