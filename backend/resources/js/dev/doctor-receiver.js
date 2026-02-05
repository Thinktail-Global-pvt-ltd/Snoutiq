import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const metaBase = (document.querySelector('meta[name="app-base"]')?.content || '').trim();
const API_BASE = metaBase && window.location.pathname.startsWith(metaBase) ? metaBase : '';
const authEndpoint = `${API_BASE}/broadcasting/auth`;
const defaultScheme = (import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '')).replace(':', '');
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || (defaultScheme === 'https' ? 443 : 80));
const forceTLS = defaultScheme === 'https';
const wsPath = import.meta.env.VITE_REVERB_PATH || '';

const doctorInput = document.getElementById('doctorId');
const tokenInput = document.getElementById('token');
const callIdInput = document.getElementById('callId');
const callStatusEl = document.getElementById('callStatus');
const callMetaEl = document.getElementById('callMeta');
const echoStatusEl = document.getElementById('echoStatus');
const channelStatusEl = document.getElementById('channelStatus');
const logEl = document.getElementById('log');

let echo = null;
let channel = null;
let heartbeatTimer = null;
let currentCall = null;

const log = (msg, detail = '') => {
    const now = new Date().toISOString();
    const text = detail ? `${now} — ${msg} ${detail}` : `${now} — ${msg}`;
    logEl.value = `${text}\n${logEl.value}`.slice(0, 8000);
};

const setEchoStatus = (label, ok = false) => {
    echoStatusEl.textContent = `Echo: ${label}`;
    echoStatusEl.className = `chip ${ok ? 'green' : 'red'}`;
};

const setChannelStatus = (label, ok = false) => {
    channelStatusEl.textContent = `Channel: ${label}`;
    channelStatusEl.className = `chip ${ok ? 'green' : 'red'}`;
};

const headers = () => {
    const token = tokenInput.value.trim();
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };
};

const getDoctorId = () => Number(doctorInput.value) || 0;

const updateCallUi = (payload = null) => {
    if (!payload) {
        callStatusEl.textContent = 'Waiting for call…';
        callMetaEl.textContent = '—';
        callIdInput.value = '';
        return;
    }
    currentCall = { ...(currentCall || {}), ...payload };
    callIdInput.value = currentCall.call_id || currentCall.id || '';
    callStatusEl.textContent = `Status: ${currentCall.status || 'unknown'}`;
    callMetaEl.textContent = `Call ${currentCall.call_id || currentCall.id} • patient ${currentCall.patient_id ?? '–'} • channel ${currentCall.channel ?? '–'}`;
};

const cleanupEcho = () => {
    if (channel) {
        channel.stopListening('.CallRequested');
        channel.stopListening('.CallStatusUpdated');
        channel = null;
    }
    if (echo) {
        echo.disconnect();
        echo = null;
    }
};

const initEcho = () => {
    cleanupEcho();
    const token = tokenInput.value.trim();
    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        wsPath,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        authEndpoint,
        // Include both bearer header (if provided) and cookies for session auth
        auth: { headers: headers(), withCredentials: true },
    });

    const conn = echo.connector?.pusher?.connection;
    if (conn?.bind) {
        conn.bind('connected', () => setEchoStatus('connected', true));
        conn.bind('disconnected', () => setEchoStatus('disconnected', false));
        conn.bind('error', (err) => {
            setEchoStatus('error', false);
            log('Echo error', JSON.stringify(err));
        });
    }
};

const subscribeDoctorChannel = () => {
    const doctorId = getDoctorId();
    if (!doctorId) {
        setChannelStatus('missing doctor_id', false);
        return;
    }
    if (!echo) initEcho();
    if (channel) {
        channel.stopListening('.CallRequested');
        channel.stopListening('.CallStatusUpdated');
    }

channel = echo.channel(`doctor.${doctorId}`)
    .listen('.CallRequested', (e) => {
        log('CallRequested', JSON.stringify(e));
        updateCallUi({ ...e, status: e.status || 'ringing' });
    })
    .listen('.CallStatusUpdated', (e) => {
        log('CallStatusUpdated', JSON.stringify(e));
        updateCallUi(e);

        // Auto-disconnect on terminal states to mirror React page behavior
        const terminalStatuses = ['ended', 'cancelled', 'missed', 'rejected'];
        if (terminalStatuses.includes((e.status || '').toLowerCase())) {
            log(`Call status ${e.status}; disconnecting listener`);
            cleanupEcho();
        }
    })
    .error((err) => {
        setChannelStatus('error', false);
        log('Channel error', JSON.stringify(err));
    });

    setChannelStatus(`doctor.${doctorId}`, true);
    log('Subscribed', `doctor.${doctorId}`);
};

const sendHeartbeat = async () => {
    const doctorId = getDoctorId();
    if (!doctorId) return;
    try {
        const res = await fetch(`${API_BASE}/api/realtime/heartbeat`, {
            method: 'POST',
            headers: headers(),
            credentials: 'include',
            body: JSON.stringify({ doctor_id: doctorId }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok) {
            log('Heartbeat ok', JSON.stringify(data));
        } else {
            log('Heartbeat failed', JSON.stringify(data));
        }
    } catch (error) {
        log('Heartbeat error', error?.message || String(error));
    }
};

const startHeartbeat = () => {
    if (heartbeatTimer) clearInterval(heartbeatTimer);
    sendHeartbeat();
    heartbeatTimer = setInterval(sendHeartbeat, 10_000);
};

const postCallAction = async (path) => {
    const callId = callIdInput.value.trim();
    if (!callId) {
        log(path, 'callId missing');
        return;
    }
    try {
        const res = await fetch(`${API_BASE}${path.replace('{id}', callId)}`, {
            method: 'POST',
            headers: headers(),
            credentials: 'include',
            body: JSON.stringify({}),
        });
        const data = await res.json().catch(() => ({}));
        log(`${path} → ${res.status}`, JSON.stringify(data));
    } catch (error) {
        log(`${path} error`, error?.message || String(error));
    }
};

document.getElementById('connect')?.addEventListener('click', () => {
    initEcho();
    subscribeDoctorChannel();
    startHeartbeat();
});

document.getElementById('heartbeatBtn')?.addEventListener('click', sendHeartbeat);
document.getElementById('acceptBtn')?.addEventListener('click', () => postCallAction('/api/calls/{id}/accept'));
document.getElementById('rejectBtn')?.addEventListener('click', () => postCallAction('/api/calls/{id}/reject'));
document.getElementById('endBtn')?.addEventListener('click', () => postCallAction('/api/calls/{id}/end'));
document.getElementById('clearLog')?.addEventListener('click', () => {
    logEl.value = 'Log cleared.';
});

// Prime UI
setEchoStatus('not connected', false);
setChannelStatus('not subscribed', false);
updateCallUi(null);
