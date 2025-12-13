<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Snoutiq • FCM Web Test</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, Apple Color Emoji, Segoe UI Emoji; margin: 24px; }
        input, textarea { width: 100%; padding: 8px; margin: 6px 0 12px; }
        button { padding: 8px 14px; margin-right: 8px; cursor: pointer; }
        code { background: #f3f4f6; padding: 2px 4px; border-radius: 4px; }
        .row { margin-bottom: 14px; }
        .small { color: #6b7280; font-size: 12px; }
        pre { background: #0b1021; color: #e5e7eb; padding: 12px; border-radius: 6px; overflow-x: auto; }
    </style>
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js"></script>
</head>
<body>
<h1>Snoutiq – FCM Web Test</h1>
<p class="small">Served from Laravel. Paste Firebase Web config + VAPID key, register the service worker, then call push APIs directly.</p>

<div class="row">
    <label>Firebase Web Config JSON</label>
    <textarea id="config" rows="6" placeholder='{"apiKey":"...","authDomain":"...","projectId":"snoutiq-fcm","messagingSenderId":"...","appId":"..."}'></textarea>
</div>

<div class="row">
    <label>FCM VAPID Key (Web Push certificate)</label>
    <input id="vapid" placeholder="BK... (public VAPID key)" />
</div>

<div class="row">
    <button id="btn-sw">Register Service Worker</button>
    <button id="btn-perm">Ask Permission</button>
    <button id="btn-token">Get FCM Token</button>
</div>

<div class="row">
    <label>Device FCM Token</label>
    <textarea id="token" rows="4" placeholder="Generate a token above or paste any token to test push delivery"></textarea>
    @if(config('push.web_test_default_token'))
        <p class="small">Prefilled with <code>PUSH_WEB_TEST_DEFAULT_TOKEN</code> for quick testing.</p>
    @endif
</div>

<h2>Server API Tests</h2>
<div class="row">
    <label>User ID (optional, to bind FCM token to a user)</label>
    <input type="number" id="user_id" placeholder="Enter user ID (e.g., 1)" />
    <p class="small">Manually enter user ID to register token for that user</p>
</div>
<div class="row">
    <label>Bearer token (optional, alternative way to bind FCM token to a user)</label>
    <input id="bearer" placeholder="Paste API token here if available" />
</div>

<div class="row">
    <button id="btn-register">POST /api/push/register-token</button>
    <button id="btn-push">POST /api/push/test</button>
    <button id="btn-delete">DELETE /api/push/register-token</button>
</div>

<h3>Response</h3>
<pre id="out">(no output)</pre>

<script>
    const swUrl = '{{ asset('firebase-messaging-sw.js') }}';
    let swScope = new URL(swUrl, window.location.href).pathname;
    if (!swScope.endsWith('/')) {
        swScope = swScope.substring(0, swScope.lastIndexOf('/') + 1);
    }
    if (!swScope) {
        swScope = '/';
    }
    const swScopeUrl = new URL(swScope, window.location.origin).href;
    const apiBase = swScope === '/' ? '' : swScope.replace(/\/$/, '');
    const DEFAULT_CONFIG = {
        apiKey: "AIzaSyDBTE0IA1xtFdtnMmM-EX-o0LWdNGV5F4g",
        authDomain: "snoutiqapp.firebaseapp.com",
        databaseURL: "https://snoutiqapp-default-rtdb.firebaseio.com",
        projectId: "snoutiqapp",
        storageBucket: "snoutiqapp.firebasestorage.app",
        messagingSenderId: "325007826401",
        appId: "1:325007826401:android:0f9d3cf46b8c32f21786a0"
    };
    const DEFAULT_VAPID = 'BC4AxUdqZfC1OWofgs2e-NBlILpHdv4X0m-sd8Rwg8mDPTqdbCKW8MYpMpmUtKV1YG9tcfKpqwJiGFQPO2g1DDo';

    function display(obj) {
        const out = document.getElementById('out');
        try {
            out.textContent = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
        } catch (e) {
            out.textContent = String(obj);
        }
    }

    const configTextarea = document.getElementById('config');
    const vapidInput = document.getElementById('vapid');
    const tokenTextarea = document.getElementById('token');
    if (!configTextarea.value.trim()) {
        configTextarea.value = JSON.stringify(DEFAULT_CONFIG, null, 2);
    }
    if (!vapidInput.value.trim() && DEFAULT_VAPID) {
        vapidInput.value = DEFAULT_VAPID;
    }
    const DEFAULT_DEVICE_TOKEN = @json(config('push.web_test_default_token'));
    if (tokenTextarea && !tokenTextarea.value.trim() && DEFAULT_DEVICE_TOKEN) {
        tokenTextarea.value = DEFAULT_DEVICE_TOKEN;
    }

    function getConfig() {
        const raw = configTextarea.value.trim();
        if (!raw) throw new Error('Paste Firebase config JSON first');
        return JSON.parse(raw);
    }

    function getVapid() {
        const vapid = vapidInput.value.trim();
        if (!vapid) throw new Error('Paste VAPID key first');
        return vapid;
    }

    function getBearer() {
        return document.getElementById('bearer').value.trim();
    }

    function getUserId() {
        const userIdInput = document.getElementById('user_id').value.trim();
        return userIdInput ? parseInt(userIdInput, 10) : null;
    }

    let messagingInstance = null;
    let messageHandlerAttached = false;

    document.getElementById('btn-sw').onclick = async () => {
        try {
            const reg = await navigator.serviceWorker.register(swUrl, { scope: swScope });
            display({ ok: true, serviceWorker: 'registered', scope: reg.scope });
        } catch (e) {
            display({ error: String(e) });
        }
    };

    document.getElementById('btn-perm').onclick = async () => {
        try {
            const perm = await Notification.requestPermission();
            display({ permission: perm });
        } catch (e) {
            display({ error: String(e) });
        }
    };

    document.getElementById('btn-token').onclick = async () => {
        try {
            const app = firebase.initializeApp(getConfig());
            const messaging = firebase.messaging(app);
            messagingInstance = messaging;

            const registration = await navigator.serviceWorker.getRegistration(swScopeUrl) || await navigator.serviceWorker.register(swUrl, { scope: swScope });
            const token = await messaging.getToken({ vapidKey: getVapid(), serviceWorkerRegistration: registration });
            tokenTextarea.value = token || '';
            display({ token });

            if (!messageHandlerAttached) {
                messaging.onMessage((payload) => {
                    display({ incoming: payload });
                    const title = payload.notification?.title || 'Snoutiq Push';
                    const body = payload.notification?.body || JSON.stringify(payload.data || {}, null, 2);
                    alert(`[Foreground push]\n${title}\n${body}`);
                });
                messageHandlerAttached = true;
            }
        } catch (e) {
            display({ error: String(e) });
        }
    };

    async function callApi(path, method, body) {
        const headers = { 'Content-Type': 'application/json' };
        const bearer = getBearer();
        if (bearer) headers['Authorization'] = 'Bearer ' + bearer;
        const urlPath = path.startsWith('/') ? path : '/' + path;
        const url = apiBase + urlPath;
        const response = await fetch(url, {
            method,
            headers,
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await response.json().catch(() => ({ status: response.status, text: 'non-json' }));
        return json;
    }

    document.getElementById('btn-register').onclick = async () => {
        try {
            const token = tokenTextarea.value.trim();
            if (!token) throw new Error('Generate FCM token first or paste token manually');
            const userId = getUserId();
            const payload = {
                token,
                platform: 'web',
                device_id: 'web-local',
                meta: { app: 'snoutiq', env: 'local' },
            };
            if (userId) {
                payload.user_id = userId;
            }
            const res = await callApi('/api/push/register-token', 'POST', payload);
            display(res);
        } catch (e) {
            display({ error: String(e) });
        }
    };

    document.getElementById('btn-push').onclick = async () => {
        try {
            const token = tokenTextarea.value.trim();
            const payload = token
                ? { token, title: 'Snoutiq', body: 'Hello from web test' }
                : { title: 'Snoutiq', body: 'Hello to all your devices' };
            const res = await callApi('/api/push/test', 'POST', payload);
            display(res);
        } catch (e) {
            display({ error: String(e) });
        }
    };

    document.getElementById('btn-delete').onclick = async () => {
        try {
            const token = tokenTextarea.value.trim();
            if (!token) throw new Error('Generate FCM token first');
            const res = await callApi('/api/push/register-token', 'DELETE', { token });
            display(res);
        } catch (e) {
            display({ error: String(e) });
        }
    };
</script>
</body>
</html>
