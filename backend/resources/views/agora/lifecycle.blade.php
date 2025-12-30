<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agora Lifecycle Lab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.4/dist/axios.min.js"></script>
    <script data-agora-sdk src="https://cdn.jsdelivr.net/npm/agora-rtc-sdk-ng@4.20.2/AgoraRTC_N.min.js" defer></script>
</head>
@php
    $API_BASE = config('app.call_api_base') ?? env('CALL_API_BASE') ?? url('');
    $AGORA_APP_ID = config('services.agora.app_id') ?? env('AGORA_APP_ID');
@endphp
<body class="bg-slate-50 text-slate-900">
<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <header>
        <p class="text-xs uppercase tracking-widest text-indigo-600 font-semibold">SnoutIQ · Internal Tool</p>
        <h1 class="text-3xl font-bold text-slate-900 mt-1">Agora Lifecycle Lab</h1>
        <p class="text-sm text-slate-500 mt-2">
            Full control panel to create sessions, accept them, mark call start/end and test cloud recording from the browser.
            Keep this tab open while testing; use the inline simulator at the bottom if you want a host + participant instantly.
        </p>
    </header>

    <section class="bg-white shadow rounded-2xl border border-slate-200 p-6 space-y-4">
        <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
            🔧 Test Inputs
        </h2>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <label class="flex flex-col gap-1">
                <span class="text-slate-600">Patient ID</span>
                <input id="input-patient" type="number" value="101" class="input-control">
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-slate-600">Doctor ID</span>
                <input id="input-doctor" type="number" value="501" class="input-control">
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-slate-600">Recording UID</span>
                <input id="input-recording-uid" type="number" value="10000" class="input-control">
            </label>
        </div>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <label class="flex flex-col gap-1">
                <span class="text-slate-600">Agora App ID</span>
                <input readonly value="{{ $AGORA_APP_ID }}" class="input-control bg-slate-100">
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-slate-600">API Base</span>
                <input id="api-base-field" value="{{ $API_BASE }}" class="input-control">
                <span class="text-xs text-slate-400">Change only if backend runs elsewhere (e.g. http://127.0.0.1:8001).</span>
            </label>
        </div>
        <label class="flex items-center gap-3 text-sm text-slate-600">
            <input id="use-token-checkbox" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span>Use Agora token for simulator join (leave unchecked for tokenless quick join)</span>
        </label>
    </section>

    <section class="bg-white shadow rounded-2xl border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">⚡ Actions (top → bottom)</h2>
            <button type="button" id="clear-log" class="btn-ghost text-xs">Clear Log</button>
        </div>

        <div class="grid md:grid-cols-3 gap-3">
            <button type="button" class="btn-primary" data-action="create">1. Create Session</button>
            <button type="button" class="btn-primary" data-action="accept">2. Accept Session</button>
            <button type="button" class="btn-primary" data-action="start-call">3. Mark Call Started</button>
            <button type="button" class="btn-secondary" data-action="start-recording">4. Start Recording</button>
            <button type="button" class="btn-secondary" data-action="recording-status">5. Recording Status</button>
            <button type="button" class="btn-secondary" data-action="stop-recording">6. Stop Recording</button>
            <button type="button" class="btn-danger" data-action="end-call">7. Mark Call Ended</button>
            <button type="button" class="btn-ghost" data-action="refresh-session">Refresh Session</button>
            <button type="button" class="btn-ghost" data-action="queue-transcript">Request Transcript</button>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Session State</p>
                <pre id="session-state" class="panel">—</pre>
            </div>
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Last Response</p>
                <pre id="last-response" class="panel">—</pre>
            </div>
        </div>

        <div>
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold mb-2">Log</p>
            <pre id="log" class="panel h-52 overflow-auto bg-slate-900 text-lime-300 text-xs">Ready…</pre>
        </div>
    </section>

    <section class="bg-white shadow rounded-2xl border border-indigo-100 p-6 space-y-4">
        <div class="flex flex-wrap items-center gap-3 justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">🎥 Simulator – Join Same Channel Instantly</h2>
                <p class="text-sm text-slate-500">
                    These mini clients auto-join the session channel. Start both to test host/participant without leaving this page.
                </p>
                <p class="text-xs text-slate-500 mt-1">
                    <span class="font-semibold text-slate-600">Channel:</span>
                    <span id="active-channel-label" class="font-mono text-slate-800">—</span>
                    <span id="active-channel-source" class="ml-2 text-[10px] uppercase tracking-wide text-slate-400">Static fallback</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-primary text-xs" id="join-both">Join Both</button>
                <button type="button" class="btn-ghost text-xs" id="leave-all">Leave Both</button>
                <button type="button" class="btn-secondary text-xs" id="sim-start-recording">Start Recording</button>
                <button type="button" class="btn-secondary text-xs" id="sim-stop-recording">Stop Recording</button>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="sim-card" data-peer="caller">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-800">Caller (Host)</p>
                        <p class="text-xs text-slate-500">Publishes mic + camera</p>
                    </div>
                    <span class="badge" id="status-caller">Idle</span>
                </div>
                <div class="video-stack">
                    <div>
                        <p class="label">Local</p>
                        <div class="video-box" id="video-caller-local"></div>
                    </div>
                    <div>
                        <p class="label">Remote</p>
                        <div class="video-box" id="video-caller-remote"></div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-primary flex-1" data-peer-join="caller">Join</button>
                    <button type="button" class="btn-ghost flex-1" data-peer-leave="caller">Leave</button>
                </div>
            </div>

            <div class="sim-card" data-peer="receiver">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-800">Receiver (Participant)</p>
                        <p class="text-xs text-slate-500">Publishes mic + camera</p>
                    </div>
                    <span class="badge" id="status-receiver">Idle</span>
                </div>
                <div class="video-stack">
                    <div>
                        <p class="label">Local</p>
                        <div class="video-box" id="video-receiver-local"></div>
                    </div>
                    <div>
                        <p class="label">Remote</p>
                        <div class="video-box" id="video-receiver-remote"></div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-primary flex-1" data-peer-join="receiver">Join</button>
                    <button type="button" class="btn-ghost flex-1" data-peer-leave="receiver">Leave</button>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .input-control {
        border-radius: 0.5rem;
        border: 1px solid rgb(226 232 240);
        padding: 0.5rem 0.75rem;
        outline: none;
    }
    .input-control:focus {
        border-color: rgb(99 102 241);
        box-shadow: 0 0 0 2px rgb(199 210 254);
    }
    .panel {
        margin-top: 0.5rem;
        border-radius: 0.75rem;
        background: rgb(241 245 249);
        padding: 0.75rem;
        min-height: 120px;
        white-space: pre-wrap;
    }
    .btn-primary,
    .btn-secondary,
    .btn-danger,
    .btn-ghost {
        border-radius: 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.6rem 0.75rem;
        transition: background 0.2s ease, color 0.2s ease;
    }
    .btn-primary {
        background: rgb(79 70 229);
        color: #fff;
        border: none;
    }
    .btn-primary:hover {
        background: rgb(67 56 202);
    }
    .btn-secondary {
        background: rgb(224 242 254);
        color: rgb(14 116 144);
        border: none;
    }
    .btn-secondary:hover {
        background: rgb(186 230 253);
    }
    .btn-danger {
        background: rgb(239 68 68);
        color: #fff;
        border: none;
    }
    .btn-danger:hover {
        background: rgb(220 38 38);
    }
    .btn-ghost {
        background: rgb(241 245 249);
        color: rgb(71 85 105);
        border: none;
    }
    .btn-ghost:hover {
        background: rgb(226 232 240);
    }
    .sim-card {
        border: 1px solid rgb(226 232 240);
        border-radius: 1rem;
        padding: 1rem;
        background: rgb(248 250 252);
    }
    .video-box {
        position: relative;
        background: #000;
        border-radius: 0.75rem;
        padding-bottom: 56.25%;
        overflow: hidden;
    }
    .video-box video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .label {
        font-size: 0.7rem;
        font-weight: 600;
        color: rgb(100 116 139);
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        background: rgb(226 232 240);
        color: rgb(71 85 105);
    }
</style>

<script>
    const patientInput = document.getElementById('input-patient');
    const doctorInput = document.getElementById('input-doctor');
    const recordingUidInput = document.getElementById('input-recording-uid');
    const apiBaseField = document.getElementById('api-base-field');
    const useTokenCheckbox = document.getElementById('use-token-checkbox');
    const sessionEl = document.getElementById('session-state');
    const responseEl = document.getElementById('last-response');
    const logEl = document.getElementById('log');
    const clearLogBtn = document.getElementById('clear-log');
    const activeChannelLabelEl = document.getElementById('active-channel-label');
    const activeChannelSourceEl = document.getElementById('active-channel-source');

    const AGORA_APP_ID = @json($AGORA_APP_ID);
    const RAW_BASE = @json($API_BASE);
    const SIMULATOR_DEFAULTS = {
        channel: 'agora-lifecycle-lab',
        uids: {
            caller: 7001,
            receiver: 7002,
        },
    };

    const state = {
        session: null,
        peers: {}
    };

    const getActiveChannel = () => state.session?.channel_name || SIMULATOR_DEFAULTS.channel;
    const updateChannelDisplay = () => {
        const channelName = getActiveChannel();
        if (activeChannelLabelEl) {
            activeChannelLabelEl.textContent = channelName || '—';
        }
        if (activeChannelSourceEl) {
            activeChannelSourceEl.textContent = state.session?.channel_name
                ? 'Session channel'
                : 'Static fallback';
        }
    };
    updateChannelDisplay();

    const normalizeBase = (value) => {
        if (!value) return window.location.origin;
        let base = value.trim();
        if (!base.match(/^https?:\/\//i)) {
            base = `${window.location.protocol}//${base.replace(/^\/\//, '')}`;
        }
        return base.replace(/\/+$/, '');
    };

    const resolveApiBase = () => {
        const candidate = apiBaseField?.value || RAW_BASE || window.location.origin;
        const normalized = normalizeBase(candidate);
        if (apiBaseField && apiBaseField.value !== normalized) {
            apiBaseField.value = normalized;
        }
        return normalized;
    };

    let agoraLoader = null;
    const ensureAgoraSdk = () => {
        if (window.AgoraRTC) {
            return Promise.resolve(window.AgoraRTC);
        }
        if (!agoraLoader) {
            agoraLoader = new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-agora-sdk]');
                if (existing) {
                    existing.addEventListener('load', () => resolve(window.AgoraRTC));
                    existing.addEventListener('error', () => reject(new Error('Failed to load Agora SDK')));
                } else {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/agora-rtc-sdk-ng@4.20.2/AgoraRTC_N.min.js';
                    script.async = true;
                    script.dataset.agoraSdk = 'true';
                    script.onload = () => resolve(window.AgoraRTC);
                    script.onerror = () => reject(new Error('Failed to load Agora SDK'));
                    document.head.appendChild(script);
                }
            });
        }
        return agoraLoader;
    };

    const log = (message, data = null) => {
        const time = new Date().toLocaleTimeString();
        logEl.textContent += `\n[${time}] ${message}`;
        if (data) {
            logEl.textContent += `\n${JSON.stringify(data, null, 2)}`;
        }
        logEl.scrollTop = logEl.scrollHeight;
    };

    clearLogBtn.addEventListener('click', () => {
        logEl.textContent = 'Cleared…';
    });

    const normalizeSessionPayload = (payload) => {
        if (!payload) return null;
        if (payload.session) {
            const session = payload.session;
            const id = payload.session_id ?? session.id ?? session.session_id ?? payload.id;
            return { ...session, session_id: id };
        }
        const id = payload.session_id ?? payload.id ?? null;
        return { ...payload, session_id: id };
    };

    const setSession = (payload) => {
        state.session = payload;
        sessionEl.textContent = payload ? JSON.stringify(payload, null, 2) : '—';
        updateChannelDisplay();
    };

    const request = async (method, url, data = undefined) => {
        const fullUrl = `${resolveApiBase()}${url}`;
        log(`➡️ ${method.toUpperCase()} ${fullUrl}`, data);
        const response = await axios({ method, url: fullUrl, data });
        const normalized = normalizeSessionPayload(response.data);
        if (normalized) {
            setSession(normalized);
        }
        responseEl.textContent = JSON.stringify(response.data, null, 2);
        return response.data;
    };

    const ensureSessionId = () => {
        const id = state.session?.session_id ?? state.session?.id;
        if (!id) throw new Error('Session ID missing. Run Create Session first.');
        return id;
    };

    const retryableRecordingCall = async (runner, { label, retries = 5, delayMs = 1500 } = {}) => {
        for (let attempt = 1; attempt <= retries; attempt++) {
            try {
                const result = await runner();
                if (attempt > 1) {
                    log(`✅ ${label} succeeded after retry #${attempt}`);
                }
                return result;
            } catch (error) {
                const message = error.response?.data?.message || error.message || '';
                const shouldRetry = /worker not ready/i.test(message);
                if (!shouldRetry || attempt === retries) {
                    throw error;
                }
                log(`⏳ ${label}: waiting for recorder (attempt ${attempt}/${retries})`);
                await new Promise((resolve) => setTimeout(resolve, delayMs));
            }
        }
    };

    const actions = {
        async create() {
            const payload = {
                patient_id: Number(patientInput.value),
                doctor_id: doctorInput.value ? Number(doctorInput.value) : null
            };
            const res = await request('post', '/api/call/create', payload);
            log('✅ Session created', res);
        },
        async accept() {
            const sessionId = ensureSessionId();
            const res = await request('post', `/api/call/${sessionId}/accept`, {
                doctor_id: Number(doctorInput.value) || 0,
            });
            log('👨‍⚕️ Doctor accepted call', res);
        },
        async 'start-call'() {
            const sessionId = ensureSessionId();
            const res = await request('post', `/api/call/${sessionId}/start`);
            log('☎️ Call marked as started', res);
        },
        async 'start-recording'() {
            const sessionId = ensureSessionId();
            const res = await request('post', `/api/call/${sessionId}/recordings/start`, {
                uid: recordingUidInput.value || undefined,
            });
            log('🎬 Recording started', res);
        },
        async 'recording-status'() {
            const sessionId = ensureSessionId();
            const res = await retryableRecordingCall(
                () => request('get', `/api/call/${sessionId}/recordings/status`),
                { label: 'Recording status' }
            );
            log('ℹ️ Recording status', res);
        },
        async 'stop-recording'() {
            const sessionId = ensureSessionId();
            const res = await retryableRecordingCall(
                () => request('post', `/api/call/${sessionId}/recordings/stop`),
                { label: 'Stop recording' }
            );
            log('⏹ Recording stopped', res);
        },
        async 'end-call'() {
            const sessionId = ensureSessionId();
            const res = await request('post', `/api/call/${sessionId}/end`);
            log('🏁 Call marked ended', res);
        },
        async 'refresh-session'() {
            const sessionId = ensureSessionId();
            const res = await request('get', `/api/call/${sessionId}`);
            log('🔄 Session refreshed', res);
        },
        async 'queue-transcript'() {
            const sessionId = ensureSessionId();
            const res = await request('post', `/api/call/${sessionId}/recordings/transcript`, {});
            log('📝 Transcript queued', res);
        },
    };

    document.querySelectorAll('[data-action]').forEach((btn) => {
        btn.addEventListener('click', async (event) => {
            event.preventDefault();
            const action = btn.dataset.action;
            if (!action || !actions[action]) return;
            btn.disabled = true;
            try {
                await actions[action]();
            } catch (error) {
                console.error(error);
                const payload = error.response?.data ?? { message: error.message };
                responseEl.textContent = JSON.stringify(payload, null, 2);
                log(`⚠️ ${action} failed`, payload);
                alert(payload.message || 'Request failed');
            } finally {
                btn.disabled = false;
            }
        });
    });

    // --------- Simulator ----------
    const peerConfigs = {
        caller: {
            label: 'caller',
            localEl: document.getElementById('video-caller-local'),
            remoteEl: document.getElementById('video-caller-remote'),
            statusEl: document.getElementById('status-caller'),
        },
        receiver: {
            label: 'receiver',
            localEl: document.getElementById('video-receiver-local'),
            remoteEl: document.getElementById('video-receiver-remote'),
            statusEl: document.getElementById('status-receiver'),
        },
    };

    const setStatus = (key, text, tone = 'gray') => {
        const el = peerConfigs[key]?.statusEl;
        if (!el) return;
        el.className = 'badge';
        if (tone === 'green') el.classList.add('bg-emerald-100', 'text-emerald-800');
        else if (tone === 'yellow') el.classList.add('bg-amber-100', 'text-amber-800');
        else if (tone === 'red') el.classList.add('bg-rose-100', 'text-rose-800');
        else el.classList.add('bg-slate-200', 'text-slate-800');
        el.textContent = text;
    };

    const renderEmptyVideo = (el) => {
        if (!el) return;
        el.innerHTML = '<div class="absolute inset-0 flex items-center justify-center text-[11px] text-slate-400">No video</div>';
    };
    Object.values(peerConfigs).forEach(cfg => {
        renderEmptyVideo(cfg.localEl);
        renderEmptyVideo(cfg.remoteEl);
    });

    const joinPeer = async (key) => {
        const cfg = peerConfigs[key];
        if (!cfg) return;
        const channel = getActiveChannel();
        if (!channel) throw new Error('No channel available for simulator.');
        const usingFallbackChannel = !state.session?.channel_name;

        setStatus(key, 'Connecting…', 'yellow');
        const uid = usingFallbackChannel
            ? (SIMULATOR_DEFAULTS.uids[key] ?? SIMULATOR_DEFAULTS.uids.caller)
            : Math.floor(Math.random() * 1e6);

        try {
            await ensureAgoraSdk();
            const Agora = window.AgoraRTC;

            let token = null;
            if (usingFallbackChannel) {
                log(`ℹ️ ${key} using static simulator channel "${channel}" with uid ${uid}`);
            }
            if (useTokenCheckbox?.checked || usingFallbackChannel) {
                const tokenRes = await axios.post(`${resolveApiBase()}/api/agora/token`, {
                    channel_name: channel,
                    uid,
                });
                token = tokenRes.data?.token ?? null;
                log(`🔐 Token fetched for ${key}`, tokenRes.data);
            } else {
                log(`🚀 ${key} joining tokenless (dev only)`);
            }

            const client = Agora.createClient({ mode: 'rtc', codec: 'vp8' });
            state.peers[key] = { client, tracks: [] };

            await client.join(AGORA_APP_ID, channel, token, uid);
            const [mic, cam] = await Agora.createMicrophoneAndCameraTracks({
                encoderConfig: '480p_1'
            });
            state.peers[key].tracks = [mic, cam];
            await client.publish([mic, cam]);
            cam.play(cfg.localEl);
            setStatus(key, 'Live', 'green');

            client.on('user-published', async (user, mediaType) => {
                await client.subscribe(user, mediaType);
                if (mediaType === 'video') {
                    user.videoTrack?.play(cfg.remoteEl);
                }
                if (mediaType === 'audio') {
                    user.audioTrack?.play();
                }
            });

            log(`🎥 ${key} joined ${channel}`, { uid });
        } catch (error) {
            console.error('Simulator join failed', error);
            log(`❌ ${key} join failed`, { message: error?.message || error });
            setStatus(key, 'Error', 'red');
            renderEmptyVideo(cfg.localEl);
            renderEmptyVideo(cfg.remoteEl);
            delete state.peers[key];
            throw error;
        }
    };

    const leavePeer = async (key) => {
        const peer = state.peers[key];
        if (!peer) {
            setStatus(key, 'Idle');
            renderEmptyVideo(peerConfigs[key]?.localEl);
            renderEmptyVideo(peerConfigs[key]?.remoteEl);
            return;
        }
        try {
            const { client, tracks } = peer;
            client?.unpublish(tracks || []);
            tracks?.forEach(track => track.stop?.() && track.close?.());
            await client?.leave();
            delete state.peers[key];
            log(`🚪 ${key} left channel`);
        } finally {
            setStatus(key, 'Idle');
            renderEmptyVideo(peerConfigs[key]?.localEl);
            renderEmptyVideo(peerConfigs[key]?.remoteEl);
        }
    };

    document.querySelectorAll('[data-peer-join]').forEach(btn => {
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                await joinPeer(btn.dataset.peerJoin);
            } catch (error) {
                console.error(error);
                alert(error.message || 'Join failed');
                setStatus(btn.dataset.peerJoin, 'Error', 'red');
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.querySelectorAll('[data-peer-leave]').forEach(btn => {
        btn.addEventListener('click', () => leavePeer(btn.dataset.peerLeave));
    });

    document.getElementById('leave-all').addEventListener('click', () => {
        Object.keys(state.peers).forEach(key => leavePeer(key));
    });

    document.getElementById('join-both').addEventListener('click', async (event) => {
        const btn = event.currentTarget;
        btn.disabled = true;
        try {
            await joinPeer('caller');
            await joinPeer('receiver');
        } catch (error) {
            console.error(error);
            alert(error.message || 'Join failed');
        } finally {
            btn.disabled = false;
        }
    });

    const triggerAction = async (name) => {
        const action = actions[name];
        if (!action) return;
        try {
            await action();
        } catch (error) {
            console.error(error);
            const payload = error.response?.data ?? { message: error.message };
            responseEl.textContent = JSON.stringify(payload, null, 2);
            log(`⚠️ ${name} failed`, payload);
            alert(payload.message || 'Request failed');
        }
    };

    document.getElementById('sim-start-recording').addEventListener('click', () => triggerAction('start-recording'));
    document.getElementById('sim-stop-recording').addEventListener('click', () => triggerAction('stop-recording'));

    window.addEventListener('beforeunload', () => {
        Object.keys(state.peers).forEach(key => leavePeer(key));
    });
</script>
</body>
</html>
