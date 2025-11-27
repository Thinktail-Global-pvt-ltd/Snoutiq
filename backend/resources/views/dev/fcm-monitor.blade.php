<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Snoutiq • FCM Token Monitor</title>
    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            margin: 24px;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.55;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
            max-width: 840px;
        }
        textarea {
            width: 100%;
            min-height: 120px;
            border-radius: 10px;
            border: 1px solid #cbd5f5;
            padding: 12px;
            font-size: 14px;
            resize: vertical;
        }
        button {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 15px;
            cursor: pointer;
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 14px;
        }
        th {
            background: #eff6ff;
            font-weight: 600;
        }
        .status-ok {
            color: #166534;
            font-weight: 600;
        }
        .status-failed {
            color: #b91c1c;
            font-weight: 600;
        }
        .muted {
            color: #475569;
            font-size: 13px;
        }
        .alert-box {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 10px;
            border-left: 4px solid #f97316;
            background: #fffbeb;
            color: #9a3412;
        }
        code {
            background: #e2e8f0;
            padding: 2px 4px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>FCM Token Monitor</h1>
        <p class="muted">
            Paste any device FCM token to watch for backend delivery attempts. Whenever Snoutiq logs a successful push to that token,
            this page will raise an alert and show the recent runs. Use this to verify that the 5-minute marketing job is hitting a specific device.
        </p>

        <form id="monitor-form">
            @csrf
            <label for="token">Device FCM Token</label>
            <textarea id="token" name="token" placeholder="Paste the token here..." required></textarea>
            <div style="margin-top: 14px;">
                <button type="submit" id="start-btn">Start Monitoring</button>
                <span id="polling-status" class="muted" style="margin-left: 10px;"></span>
            </div>
        </form>

        <div id="feedback" class="alert-box" style="display:none;"></div>

        <div id="device-info" style="display:none; margin-top: 18px;">
            <h3>Device info</h3>
            <p class="muted" id="device-meta"></p>
        </div>

        <div id="delivery-log" style="display:none; margin-top: 16px;">
            <h3>Recent delivery attempts</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Run</th>
                        <th>Started</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody id="delivery-body">
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const form = document.getElementById('monitor-form');
        const tokenInput = document.getElementById('token');
        const feedbackBox = document.getElementById('feedback');
        const deviceInfo = document.getElementById('device-info');
        const deviceMeta = document.getElementById('device-meta');
        const deliveryLog = document.getElementById('delivery-log');
        const deliveryBody = document.getElementById('delivery-body');
        const pollingStatus = document.getElementById('polling-status');
        const statusUrl = "{{ route('dev.fcm-monitor.status') }}";

        let pollId = null;
        let lastAlertedDelivery = null;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            startMonitoring();
        });

        function startMonitoring() {
            const token = tokenInput.value.trim();
            if (!token) {
                showFeedback('Please paste an FCM token first.', true);
                tokenInput.focus();
                return;
            }

            showFeedback('Monitoring...', false);
            fetchAndRender(token);
            if (pollId) window.clearInterval(pollId);
            pollId = window.setInterval(() => fetchAndRender(token), 4000);
        }

        async function fetchAndRender(token) {
            try {
                pollingStatus.textContent = 'Polling...';
                const response = await fetch(`${statusUrl}?token=${encodeURIComponent(token)}&t=${Date.now()}`);
                const data = await response.json();
                pollingStatus.textContent = 'Last check: ' + new Date().toLocaleTimeString();
                renderData(data);
            } catch (error) {
                showFeedback('Failed to check token: ' + error, true);
            }
        }

        function renderData(payload) {
            if (payload.status === 'error') {
                showFeedback(payload.message || 'Missing token.', true);
                return;
            }

            if (payload.status === 'not_found') {
                showFeedback('No device found for this token. Make sure it is current.', true);
                deviceInfo.style.display = 'none';
                deliveryLog.style.display = 'none';
                return;
            }

            showFeedback('Listening for new deliveries...', false);
            deviceInfo.style.display = 'block';
            deviceMeta.textContent = [
                `Device label: ${payload.device.label}`,
                payload.device.platform ? `Platform: ${payload.device.platform}` : null,
                payload.device.user_id ? `User #${payload.device.user_id}` : null,
                payload.device.last_seen_at ? `Last seen: ${new Date(payload.device.last_seen_at).toLocaleString()}` : null,
            ].filter(Boolean).join(' • ');

            deliveryLog.style.display = 'block';
            deliveryBody.innerHTML = '';

            if (!payload.deliveries || payload.deliveries.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 5;
                cell.textContent = 'No deliveries have been logged for this token yet.';
                row.appendChild(cell);
                deliveryBody.appendChild(row);
                return;
            }

            payload.deliveries.forEach((delivery, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${delivery.id}</td>
                    <td class="${delivery.status === 'success' ? 'status-ok' : 'status-failed'}">${delivery.status}</td>
                    <td>${delivery.run ? `${delivery.run.title || delivery.run.id} (${delivery.run.trigger})` : 'n/a'}</td>
                    <td>${delivery.run && delivery.run.started_at ? new Date(delivery.run.started_at).toLocaleString() : 'n/a'}</td>
                    <td>${delivery.error_message || delivery.error_code || '—'}</td>
                `;
                deliveryBody.appendChild(row);

                if (index === 0 && delivery.status === 'success') {
                    maybeAlert(delivery);
                }
            });
        }

        function maybeAlert(delivery) {
            if (lastAlertedDelivery && lastAlertedDelivery === delivery.id) {
                return;
            }
            lastAlertedDelivery = delivery.id;
            alert(`✅ Delivery logged!\nRun: ${delivery.run ? delivery.run.title : delivery.id}\nStatus: ${delivery.status}`);
        }

        function showFeedback(text, isError) {
            if (!text) {
                feedbackBox.style.display = 'none';
                return;
            }
            feedbackBox.style.display = 'block';
            feedbackBox.style.background = isError ? '#fef2f2' : '#ecfccb';
            feedbackBox.style.color = isError ? '#b91c1c' : '#3f6212';
            feedbackBox.style.borderLeftColor = isError ? '#dc2626' : '#84cc16';
            feedbackBox.textContent = text;
        }
    </script>
</body>
</html>
