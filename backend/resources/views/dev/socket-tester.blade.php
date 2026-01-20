<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="/backend">
    <title>Socket Listener</title>
    <style>
        body {
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
            background:#f4f6fb;
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:flex-start;
            justify-content:center;
            padding:2rem;
        }
        .panel {
            width:100%;
            max-width:960px;
            background:#fff;
            border-radius:14px;
            box-shadow:0 20px 48px rgba(0,0,0,.08);
            padding:2rem;
        }
        h1 {
            margin-top:0;
        }
        .grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
            gap:1rem;
        }
        label {
            font-weight:600;
            display:block;
            margin-bottom:.25rem;
        }
        input, select, button, textarea {
            width:100%;
            border-radius:10px;
            border:1px solid #d2d6dc;
            padding:.65rem;
            font-size:1rem;
        }
        textarea {
            resize:none;
            min-height:180px;
            font-family:inherit;
        }
        button {
            border:none;
            background:#2563eb;
            color:#fff;
            font-weight:600;
            cursor:pointer;
        }
        button.secondary {
            background:#6b7280;
        }
        .footer {
            margin-top:1.5rem;
            display:flex;
            flex-wrap:wrap;
            gap:.5rem;
        }
        .log {
            margin-top:1rem;
            background:#0f172a;
            color:#e0f2fe;
            padding:1rem;
            border-radius:12px;
            font-family:Consolas,ui-monospace,monospace;
            max-height:280px;
            overflow:auto;
        }
        .chip {
            display:inline-flex;
            align-items:center;
            padding:.35rem .75rem;
            border-radius:999px;
            background:#e0e7ff;
            gap:.25rem;
            font-size:.9rem;
        }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Socket Listener</h1>
        <p>Subscribe to the same `CallSessionUpdated` Reverb channels the scheduler writes to.</p>

        <div class="grid" aria-label="subscribe form">
            <div>
                <label for="channelType">Channel type</label>
                <select id="channelType">
                    <option value="doctor">doctor</option>
                    <option value="patient">patient</option>
                    <option value="call">call</option>
                </select>
            </div>
            <div>
                <label for="channelId">ID</label>
                <input id="channelId" type="text" placeholder="e.g. 1 or call_test">
            </div>
            <div style="align-self:flex-end">
                <button id="subscribe">Subscribe</button>
            </div>
        </div>

        <form id="dispatchForm" class="grid" style="margin-top:1.5rem;">
            <div>
                <label for="callSession">Call Session</label>
                <input id="callSession" type="text" value="call_test">
            </div>
            <div>
                <label for="doctorId">Doctor ID</label>
                <input id="doctorId" type="number" min="0" value="1">
            </div>
            <div>
                <label for="patientId">Patient ID</label>
                <input id="patientId" type="number" min="0" value="2">
            </div>
            <div>
                <label for="channelName">Channel</label>
                <input id="channelName" type="text" value="video">
            </div>
            <div style="align-self:flex-end;grid-column:1/-1">
                <button type="submit" class="secondary">Trigger POST /api/socket/call-sessions</button>
            </div>
        </form>

        <div class="chip" id="currentChannel">Not subscribed</div>
        <div class="log" id="eventLog"><p style="margin-top:0;margin-bottom:0;">Waiting for events…</p></div>
        <div class="footer">
            <button id="clearLog" class="secondary">Clear log</button>
        </div>
    </div>

    @vite('resources/js/echo.js')
    <script>
        const API_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
        const logEl = document.getElementById('eventLog');
        const currentChannelEl = document.getElementById('currentChannel');
        const subscribeBtn = document.getElementById('subscribe');
        const clearLogBtn = document.getElementById('clearLog');
        let echoChannel = null;

        function appendLog(message, detail = '') {
            const entry = document.createElement('div');
            entry.innerHTML = `<strong>${message}</strong><br><small>${detail}</small>`;
            logEl.appendChild(entry);
            logEl.scrollTop = logEl.scrollHeight;
        }

        function unsubscribe() {
            if (!echoChannel || !window.Echo) return;
            window.Echo.leave(echoChannel._channel);
            echoChannel = null;
        }

        subscribeBtn.addEventListener('click', (event) => {
            event.preventDefault();
            if (!window.Echo) {
                appendLog('Echo not ready yet.');
                return;
            }
            const type = document.getElementById('channelType').value;
            const id = document.getElementById('channelId').value.trim();
            if (!id) {
                appendLog('Enter an ID before subscribing.');
                return;
            }
            unsubscribe();
            const channelName = `${type}-${id}`;
            echoChannel = window.Echo.channel(channelName);
            currentChannelEl.textContent = `Listening on ${channelName}`;
            appendLog('subscribed', channelName);

            echoChannel.listen('.call-session.stored', (payload) => {
                appendLog('call-session.stored', JSON.stringify(payload));
            });
            echoChannel.listen('.call-session.expired', (payload) => {
                appendLog('call-session.expired', JSON.stringify(payload));
            });
            echoChannel.listen('.call-session.status_update', (payload) => {
                appendLog('call-session.status_update', JSON.stringify(payload));
            });
        });

        document.getElementById('dispatchForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = {
                call_session: document.getElementById('callSession').value || `call_${Date.now()}`,
                doctor_id: Number(document.getElementById('doctorId').value) || 0,
                patient_id: Number(document.getElementById('patientId').value) || 0,
                channel: document.getElementById('channelName').value || 'video',
            };

            try {
                const response = await fetch(`${API_BASE}/api/socket/call-sessions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const body = await response.json();
                appendLog('call-sessions POST', JSON.stringify(body));
            } catch (error) {
                appendLog('error', error.message);
            }
        });

        clearLogBtn.addEventListener('click', () => {
            logEl.innerHTML = '<p style="margin-top:0;margin-bottom:0;">Waiting for events…</p>';
        });
    </script>
</body>
</html>
