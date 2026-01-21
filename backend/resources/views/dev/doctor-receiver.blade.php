<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="/backend">
    <title>Doctor Receiver (Reverb)</title>
    @vite('resources/js/dev/doctor-receiver.js')
    <style>
        :root { --blue:#2563eb; --gray:#6b7280; --bg:#f7f7fb; --text:#0f172a; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--text); padding:2rem; }
        .card { background:#fff; border-radius:14px; padding:1.25rem; box-shadow:0 18px 40px rgba(0,0,0,.06); margin-bottom:1rem; }
        h1 { margin:0 0 .25rem; font-size:1.5rem; }
        h2 { margin:0 0 .5rem; }
        label { font-weight:600; display:block; margin-bottom:.35rem; }
        input, button, textarea { font-size:1rem; border-radius:10px; border:1px solid #d7dbe3; padding:.65rem .75rem; width:100%; }
        textarea { min-height:160px; resize:vertical; }
        button { background:var(--blue); color:#fff; border:none; font-weight:700; cursor:pointer; }
        button.secondary { background:var(--gray); }
        button:disabled { opacity:.6; cursor:not-allowed; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:1rem; }
        .row { display:flex; gap:.75rem; flex-wrap:wrap; align-items:center; }
        .chip { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .75rem; border-radius:999px; font-weight:700; }
        .chip.green { background:#dcfce7; color:#166534; }
        .chip.red { background:#fee2e2; color:#991b1b; }
        .status { font-size:1.1rem; font-weight:700; margin:0; }
        .call-panel { display:flex; flex-direction:column; gap:.35rem; }
        .muted { color:#6b7280; font-size:.95rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Doctor Receiver</h1>
        <p class="muted">Listens on Reverb private-doctor.{id}, shows incoming calls, and lets you accept/reject/end.</p>
        <div class="grid" style="margin-top:.75rem;">
            <div>
                <label for="doctorId">Doctor ID</label>
                <input id="doctorId" type="number" value="1">
            </div>
            <div>
                <label for="token">Bearer Token (optional)</label>
                <input id="token" type="text" placeholder="ey...">
            </div>
            <div>
                <label for="callId">Active Call ID (auto-fills on events)</label>
                <input id="callId" type="text" placeholder="waiting...">
            </div>
        </div>
        <div class="row" style="margin-top:1rem;">
            <button id="connect">Connect &amp; Listen</button>
            <button id="heartbeatBtn" class="secondary">Send Heartbeat Now</button>
            <div class="chip" id="echoStatus" aria-live="polite">Echo: unknown</div>
            <div class="chip" id="channelStatus" aria-live="polite">Channel: none</div>
        </div>
    </div>

    <div class="card call-panel">
        <h2>Incoming Call</h2>
        <p class="status" id="callStatus">Waiting for call…</p>
        <p class="muted" id="callMeta">—</p>
        <div class="row">
            <button id="acceptBtn">Accept</button>
            <button id="rejectBtn" class="secondary">Reject</button>
            <button id="endBtn" class="secondary">End</button>
        </div>
    </div>

    <div class="card">
        <h3>Event Log</h3>
        <textarea id="log" readonly>Ready. Click “Connect & Listen”.</textarea>
        <div class="row" style="margin-top:.5rem;">
            <button id="clearLog" class="secondary">Clear log</button>
        </div>
    </div>
</body>
</html>
