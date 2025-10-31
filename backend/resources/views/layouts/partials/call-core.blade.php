@php
  $coreSocketUrl = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));
  $corePathPrefix = $pathPrefix ?? rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
  $coreSessionUser = $sessionUser ?? session('user');
  $coreSessionAuth = $sessionAuth ?? session('auth_full');
  $coreSessionDoctor = $sessionDoctor ?? session('doctor');
  $coreSessionUserId = session('user_id')
    ?? data_get($coreSessionUser, 'id')
    ?? optional(auth()->user())->id;
  $coreServerCandidate = session('doctor_id')
    ?? data_get($coreSessionDoctor, 'id')
    ?? $coreSessionUserId
    ?? data_get($coreSessionUser, 'doctor_id')
    ?? data_get($coreSessionAuth, 'user.doctor_id')
    ?? optional(auth()->user())->doctor_id
    ?? request()->input('doctorId');
@endphp

<script>
  document.addEventListener('DOMContentLoaded', function(){
    const toggle      = document.getElementById('visibility-toggle');
    const label       = document.getElementById('visibility-label');
    const statusDot   = document.getElementById('status-dot');
    const statusPill  = document.getElementById('status-pill');

    window.__SNOUTIQ_LAYOUT_HANDLES_VISIBILITY = true;

    const RAW_SOCKET_URL     = @json($coreSocketUrl);
    const PATH_PREFIX_RAW    = @json($corePathPrefix);
    const PATH_PREFIX        = PATH_PREFIX_RAW ? `/${PATH_PREFIX_RAW}`.replace(/\/+/, '/') : '';
    const PATH_PREFIX_GUESS  = (() => {
      try {
        const path = window.location?.pathname || '';
        const knownPrefixes = ['backend','petparent','admin'];
        const match = knownPrefixes.find(prefix => path === `/${prefix}` || path.startsWith(`/${prefix}/`));
        return match ? `/${match}` : '';
      } catch (_) {
        return '';
      }
    })();
    const API_PREFIX = PATH_PREFIX || PATH_PREFIX_GUESS || '';
    const API_BASE   = `${API_PREFIX || ''}/api`;
    const API_HEADERS = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    const sessionUser = @json($coreSessionUser);
    const sessionAuth = @json($coreSessionAuth);
    const sessionDoctor = @json($coreSessionDoctor);
    const sessionUserId = Number(@json($coreSessionUserId ?? null)) || null;
    let currentDoctorId = Number(@json($coreServerCandidate ?? null)) || null;
    if (!currentDoctorId) {
      const fallbackCandidates = [
        sessionDoctor?.id,
        sessionUserId,
        sessionUser?.doctor_id,
        sessionAuth?.user?.doctor_id,
      ];
      for (const value of fallbackCandidates) {
        const num = Number(value);
        if (!Number.isNaN(num) && num) { currentDoctorId = num; break; }
      }
    }

    let socket = null;
    let joined = false;
    let ackTimer = null;
    let shouldHoldOnline = true;
    let reconnectTimer = null;
    const RECONNECT_INTERVAL = 8000;
    let globalCall = null;
    let globalAlertOpen = false;
    let ringAudio = null;
    let ringResumePending = false;
    let ringResumeListener = null;
    const callSessionCache = new Map();

    const HEARTBEAT_EVENT = 'doctor-heartbeat';
    const HEARTBEAT_INTERVAL = 25000;
    const BACKGROUND_HOLD_MS = 5 * 60 * 1000; // keep UI online for up to 5 minutes while suspended
    const VISIBILITY_EVENT = 'doctor-visibility';

    let heartbeatTimer = null;
    let backgroundHoldTimer = null;
    let backgroundHoldActive = false;
    let clinicVisible = true;
    let notificationsEnabled = typeof Notification !== 'undefined' && Notification.permission === 'granted';
    const shownCallNotifications = new Set();

    function isDocumentHidden(){
      try {
        return typeof document !== 'undefined' && document.hidden;
      } catch (_) {
        return false;
      }
    }

    function clearReconnectTimer(){
      if (reconnectTimer) {
        clearInterval(reconnectTimer);
        reconnectTimer = null;
      }
    }

    function scheduleReconnect(opts = {}){
      if (!shouldHoldOnline) return;
      if (!socket || socket.connected) {
        clearReconnectTimer();
        return;
      }
      if (!reconnectTimer) {
        reconnectTimer = setInterval(()=>{
          if (!shouldHoldOnline) {
            clearReconnectTimer();
            return;
          }
          if (!socket || socket.connected) {
            clearReconnectTimer();
            return;
          }
          try {
            socket.connect();
          } catch (err) {
            console.warn('[snoutiq-call] reconnect attempt failed', err);
          }
        }, RECONNECT_INTERVAL);
      }
      if (opts.immediate) {
        try {
          socket.connect();
        } catch (err) {
          console.warn('[snoutiq-call] reconnect failed', err);
        }
      }
    }

    function sendHeartbeat(immediate){
      if (!currentDoctorId) return;
      const sock = socket && socket.connected ? socket : null;
      if (!sock) return;
      try {
        sock.emit(HEARTBEAT_EVENT, {
          doctorId: Number(currentDoctorId),
          at: Date.now(),
          visible: clinicVisible,
          immediate: immediate ? 1 : 0,
        });
      } catch (err) {
        console.warn('[snoutiq-call] heartbeat send failed', err);
      }
    }

    function startHeartbeat(){
      if (!shouldHoldOnline) return;
      stopHeartbeat();
      sendHeartbeat(true);
      heartbeatTimer = setInterval(()=>{
        if (!shouldHoldOnline) return;
        sendHeartbeat(false);
      }, HEARTBEAT_INTERVAL);
    }

    function stopHeartbeat(){
      if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
      }
    }

    function enterBackgroundHold(reason){
      if (!shouldHoldOnline) return;
      backgroundHoldActive = true;
      if (backgroundHoldTimer) {
        clearTimeout(backgroundHoldTimer);
        backgroundHoldTimer = null;
      }
      backgroundHoldTimer = setTimeout(()=>{
        backgroundHoldActive = false;
        if (!socket || !socket.connected) {
          setHeaderStatus('connecting');
        }
      }, BACKGROUND_HOLD_MS);
      if (reason) {
        try { console.debug('[snoutiq-call] entering background hold due to', reason); } catch(_){ }
      }
      setHeaderStatus('online');
    }

    function exitBackgroundHold(){
      backgroundHoldActive = false;
      if (backgroundHoldTimer) {
        clearTimeout(backgroundHoldTimer);
        backgroundHoldTimer = null;
      }
    }

    function readAuthFull(){
      try{
        const raw = sessionStorage.getItem('auth_full')
          || localStorage.getItem('auth_full')
          || localStorage.getItem('sn_session_v1');
        return raw ? JSON.parse(raw) : null;
      }catch(_){
        return null;
      }
    }

    function extractDoctorId(payload){
      if (!payload || typeof payload !== 'object') return null;
      const candidates = [
        payload.doctor_id,
        payload.user_id,
        payload.id,
        payload?.user?.doctor_id,
        payload?.user?.id,
        payload?.user?.doctor?.id,
        payload?.doctor?.id,
      ];
      for (const value of candidates) {
        const num = Number(value);
        if (!Number.isNaN(num) && num) return num;
      }
      const collections = [
        payload.doctors,
        payload?.user?.doctors,
        payload?.user?.clinic_doctors,
      ];
      for (const arr of collections){
        if (Array.isArray(arr)) {
          for (const entry of arr) {
            const num = Number(entry?.id);
            if (!Number.isNaN(num) && num) return num;
          }
        }
      }
      return null;
    }

    function queryDoctorId(){
      try{
        const url = new URL(window.location.href);
        const val = url.searchParams.get('doctorId');
        return val ? Number(val) : null;
      }catch(_){
        return null;
      }
    }

    function readStoredDoctorId(){
      const keys = [
        'snoutiq_current_doctor_id',
        'snoutiq_doctor_id',
        'currentDoctorId',
        'doctorId',
      ];
      for (const storage of [localStorage, sessionStorage]) {
        if (!storage) continue;
        for (const key of keys) {
          let val = null;
          try {
            val = storage.getItem(key);
          } catch (_) {
            val = null;
          }
          if (val == null || val === '') continue;
          const num = Number(val);
          if (!Number.isNaN(num) && num) return num;
        }
      }
      return null;
    }

    const authFull        = readAuthFull();
    const storageDoctorId = extractDoctorId(authFull) || readStoredDoctorId();
    const queryId         = queryDoctorId();
    if (!currentDoctorId) {
        currentDoctorId = queryId || storageDoctorId || sessionUserId || currentDoctorId;
    }
    window.CURRENT_DOCTOR_ID = currentDoctorId;

    const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
    const SOCKET_URL = (() => {
      if (!RAW_SOCKET_URL) return window.location.origin;
      if (!IS_LOCAL && /localhost|127\.0\.0\.1/i.test(RAW_SOCKET_URL)) {
        return window.location.origin;
      }
      return RAW_SOCKET_URL;
    })();

    function detectFrontendBase(){
      const clean = value => (value || '').toString().trim().replace(/\/+$/, '');
      const meta = document.querySelector('meta[name="snoutiq-frontend-base"]');
      if (meta?.content) return clean(meta.content);
      try{
        const stored = localStorage.getItem('snoutiq_frontend_base') || sessionStorage.getItem('snoutiq_frontend_base');
        if (stored) return clean(stored);
      }catch(_){}

      const origin = clean(window.location.origin);
      const host = (window.location.hostname || '').toLowerCase();
      const port = window.location.port;

      const isLocalHost = /localhost|127\.0\.0\.1|0\.0\.0\.0/.test(host);
      if (isLocalHost && port === '8000') return 'http://localhost:5173';

      // backend might be on /backend, but FE should still be root origin
      return origin;
    }

    const FRONTEND_BASE = detectFrontendBase();
    window.__SNOUTIQ_FRONTEND_BASE = FRONTEND_BASE;

    function resolveFrontendAsset(path){
      const cleanPath = `/${String(path || '').replace(/^\/+/, '')}`;
      const base = (FRONTEND_BASE || '').toString().trim();
      if (!base) return cleanPath;
      return `${base.replace(/\/+$/, '')}${cleanPath}`;
    }

    function ensureRingAudio(){
      if (ringAudio) return ringAudio;
      try{
        const audio = new Audio(resolveFrontendAsset('ringtone.mp3'));
        audio.loop = true;
        audio.preload = 'auto';
        ringAudio = audio;
      }catch(err){
        ringAudio = null;
        console.warn('[snoutiq-call] unable to initialise ringtone audio', err);
      }
      return ringAudio;
    }

    function cancelRingResume(){
      if (!ringResumeListener) {
        ringResumePending = false;
        return;
      }
      document.removeEventListener('click', ringResumeListener);
      document.removeEventListener('touchstart', ringResumeListener);
      ringResumeListener = null;
      ringResumePending = false;
    }

    function scheduleRingResume(){
      if (ringResumePending) return;
      ringResumePending = true;
      ringResumeListener = () => {
        cancelRingResume();
        startGlobalTone();
      };
      document.addEventListener('click', ringResumeListener, { once: true });
      document.addEventListener('touchstart', ringResumeListener, { once: true });
    }

    function startGlobalTone(){
      try{
        cancelRingResume();
        const audio = ensureRingAudio();
        if (!audio) return;
        stopGlobalTone(true);
        try { audio.currentTime = 0; }catch(_){ }
        const playPromise = audio.play();
        if (playPromise && typeof playPromise.catch === 'function') {
          playPromise.catch(err => {
            console.warn('[snoutiq-call] unable to autoplay ringtone', err);
            scheduleRingResume();
          });
        }
      }catch(err){
        console.warn('[snoutiq-call] unable to start ringtone', err);
      }
    }

    function stopGlobalTone(skipReset){
      try{
        cancelRingResume();
        if (ringAudio) {
          ringAudio.pause();
          if (!skipReset) {
            try { ringAudio.currentTime = 0; }catch(_){ }
          }
        }
      }catch(err){
        console.warn('[snoutiq-call] unable to stop ringtone', err);
      }
    }

    function emitCallEvent(name, detail){
      try{
        window.dispatchEvent(new CustomEvent(name, { detail }));
      }catch(_){}
    }

    function updateDoctorId(id){
      const num = Number(id || 0);
      if (Number.isNaN(num) || !num) return currentDoctorId;
      if (currentDoctorId === num) return currentDoctorId;
      currentDoctorId = num;
      window.CURRENT_DOCTOR_ID = num;
      try{
        localStorage.setItem('snoutiq_current_doctor_id', String(num));
        sessionStorage.setItem('snoutiq_current_doctor_id', String(num));
      }catch(_){}
      if (socket && socket.connected) {
        try{
          socket.emit('join-doctor', num);
          joined = true;
          setHeaderStatus(clinicVisible ? 'online' : 'offline');
          sendHeartbeat(true);
          syncDoctorVisibility();
        }catch(err){
          console.warn('[snoutiq-call] failed to refresh doctor id', err);
        }
      }
      return currentDoctorId;
    }

    function callUrlFromPayload(payload){
      const channel = (payload?.channel || '').trim();
      if (!channel) return null;
      const base = FRONTEND_BASE || '';
      const doctorValue = String(currentDoctorId || payload?.doctorId || '');
      const search = new URLSearchParams({
        uid: doctorValue,
        doctorId: doctorValue,
        role: 'host',
        pip: '1',
      });
      if (payload?.callId) search.append('callId', String(payload.callId));
      if (payload?.patientId) search.append('patientId', String(payload.patientId));
      return `${base}/call-page/${encodeURIComponent(channel)}?${search.toString()}`;
    }

    function paymentUrlFromPayload(payload){
      try {
        const callId = (payload?.callId || payload?.call_id || payload?.callIdentifier || '').toString().trim();
        const channel = (payload?.channel || '').toString().trim();
        const doctorValueRaw = payload?.doctorId ?? payload?.doctor_id ?? currentDoctorId ?? null;
        const patientValueRaw = payload?.patientId ?? payload?.patient_id ?? null;
        const doctorValue = Number(doctorValueRaw);
        const patientValue = Number(patientValueRaw);
        if (!callId || !channel || Number.isNaN(doctorValue) || Number.isNaN(patientValue) || !doctorValue || !patientValue) {
          return null;
        }
        const baseCandidate = (FRONTEND_BASE || '').toString().trim();
        const base = baseCandidate || (window.location?.origin || '');
        if (!base) return null;
        const params = new URLSearchParams({
          callId,
          doctorId: String(doctorValue),
          channel,
          patientId: String(patientValue),
        });
        return `${base.replace(/\/+$/, '')}/payment/${encodeURIComponent(callId)}?${params.toString()}`;
      } catch (_err) {
        return null;
      }
    }

    function resolveDoctorJoinUrl(payload){
      const directCandidates = [
        payload?.doctorJoinUrl,
        payload?.doctor_join_url,
        payload?.videoUrl,
        payload?.video_url,
      ];
      for (const candidate of directCandidates) {
        if (typeof candidate === 'string' && candidate.trim()) {
          return candidate.trim();
        }
      }
      return callUrlFromPayload(payload);
    }

    function resolvePaymentUrl(payload){
      const directCandidates = [
        payload?.patientPaymentUrl,
        payload?.patient_payment_url,
        payload?.paymentUrl,
        payload?.payment_url,
      ];
      for (const candidate of directCandidates) {
        if (typeof candidate === 'string' && candidate.trim()) {
          return candidate.trim();
        }
      }
      return paymentUrlFromPayload(payload);
    }

    function formatLinkDisplay(url){
      if (!url) return '';
      try {
        const parsed = new URL(url, window.location?.origin || undefined);
        const displayValue = `${parsed.pathname}${parsed.search}`;
        if (displayValue.length > 58) {
          return `${displayValue.slice(0, 55)}…`;
        }
        return displayValue || parsed.href;
      } catch (_err) {
        return url.length > 58 ? `${url.slice(0,55)}…` : url;
      }
    }

    function bindCopyButton(button){
      if (!button || button.dataset.copyBound === '1') return;
      button.addEventListener('click', async (event) => {
        event.preventDefault();
        const value = button.dataset.copyValue;
        if (!value) return;
        try {
          if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);
          } else {
            const tmp = document.createElement('textarea');
            tmp.value = value;
            tmp.setAttribute('readonly', '');
            tmp.style.position = 'absolute';
            tmp.style.left = '-9999px';
            document.body.appendChild(tmp);
            tmp.select();
            document.execCommand('copy');
            document.body.removeChild(tmp);
          }
          button.classList.add('is-copied');
          button.textContent = 'Copied!';
          setTimeout(() => {
            button.classList.remove('is-copied');
            button.textContent = 'Copy';
          }, 1500);
        } catch (err) {
          console.warn('[snoutiq-call] copy failed', err);
        }
      });
      button.dataset.copyBound = '1';
    }

    function populateLinkRow(rowEl, url, label){
      if (!rowEl) return;
      try {
        if (label) {
          const labelEl = rowEl.querySelector('[data-role="link-label"]');
          if (labelEl) labelEl.textContent = label;
        }
        const valueEl = rowEl.querySelector('[data-role="link-value"]');
        const openEl = rowEl.querySelector('[data-role="link-open"]');
        const copyEl = rowEl.querySelector('[data-role="link-copy"]');

        if (!url) {
          rowEl.classList.add('is-disabled');
          if (valueEl) {
            valueEl.textContent = 'Not available yet';
            valueEl.removeAttribute('title');
          }
          if (openEl) {
            openEl.removeAttribute('href');
            openEl.setAttribute('aria-disabled','true');
          }
          if (copyEl) {
            copyEl.disabled = true;
            copyEl.removeAttribute('data-copy-value');
            copyEl.classList.remove('is-copied');
            copyEl.textContent = 'Copy';
          }
          return;
        }

        rowEl.classList.remove('is-disabled');
        if (valueEl) {
          valueEl.textContent = formatLinkDisplay(url);
          valueEl.title = url;
        }
        if (openEl) {
          openEl.href = url;
          openEl.setAttribute('aria-disabled','false');
        }
        if (copyEl) {
          copyEl.disabled = false;
          copyEl.dataset.copyValue = url;
          copyEl.classList.remove('is-copied');
          copyEl.textContent = 'Copy';
          bindCopyButton(copyEl);
        }
      } catch (err) {
        console.warn('[snoutiq-call] failed to populate link row', err);
      }
    }

    function normaliseCallId(payload){
      return (payload?.callId || payload?.call_id || payload?.callIdentifier || '').toString().trim();
    }

    function sanitiseCallPayload(payload){
      if (!payload || typeof payload !== 'object') return {};
      const safe = {};
      const map = {
        callId: ['callId','call_id','callIdentifier'],
        channel: ['channel','channel_name'],
        doctorId: ['doctorId','doctor_id'],
        patientId: ['patientId','patient_id'],
        doctorJoinUrl: ['doctorJoinUrl','doctor_join_url'],
        patientPaymentUrl: ['patientPaymentUrl','patient_payment_url'],
        timestamp: ['timestamp','createdAt','created_at'],
      };
      Object.entries(map).forEach(([key, keys]) => {
        for (const candidate of keys) {
          if (payload[candidate] != null) {
            safe[key] = payload[candidate];
            break;
          }
        }
      });
      return safe;
    }

    async function showCallNotification(payload){
      try {
        if (!navigator.serviceWorker || typeof Notification === 'undefined') return;
        if (!isDocumentHidden()) return;

        const realCallId = normaliseCallId(payload);
        const dedupeKey = realCallId || `call_${Date.now()}`;
        if (shownCallNotifications.has(dedupeKey)) return;

        if (!notificationsEnabled) {
          const granted = await ensureNotificationPermission(false);
          if (!granted) return;
        }

        const registration = await navigator.serviceWorker.ready;
        const callUrl = resolveDoctorJoinUrl(payload) || window.location.href;
        const patientName = extractPatientName(payload) || '';
        const patientId = extractPatientId(payload);
        const bodyParts = [];
        if (patientName) bodyParts.push(patientName);
        else if (patientId) bodyParts.push(`Patient #${patientId}`);
        bodyParts.push('is calling for a video consult');
        const body = bodyParts.join(' ');

        const icon = resolveFrontendAsset('custom-doctor-icon-192.png');
        const tag = `snoutiq-call-${dedupeKey}`;
        const safePayload = sanitiseCallPayload(payload);
        if (!safePayload.callId) safePayload.callId = realCallId || dedupeKey;
        const data = {
          type: 'call-requested',
          callId: realCallId || dedupeKey,
          url: callUrl,
          call: safePayload,
        };

        await registration.showNotification('Incoming Video Consultation', {
          body,
          icon,
          badge: icon,
          requireInteraction: true,
          tag,
          renotify: true,
          data,
          actions: [
            { action: 'accept', title: 'Accept' },
            { action: 'dismiss', title: 'Dismiss' },
          ],
        });

        shownCallNotifications.add(dedupeKey);
      } catch (err) {
        console.warn('[snoutiq-call] failed to show notification', err);
      }
    }

    function applySessionInfoToPayload(payload, info){
      if (!payload || !info) return;
      if (info.sessionId) payload.sessionId = info.sessionId;
      if (info.callIdentifier) payload.callIdentifier = info.callIdentifier;
      if (info.doctorJoinUrl) payload.doctorJoinUrl = info.doctorJoinUrl;
      if (info.patientPaymentUrl) payload.patientPaymentUrl = info.patientPaymentUrl;
      if (info.doctorId && !payload.doctorId) payload.doctorId = info.doctorId;
      if (info.patientId && !payload.patientId) payload.patientId = info.patientId;
      if (info.channelName && !payload.channel) payload.channel = info.channelName;

      if (globalCall) {
        const globalId = normaliseCallId(globalCall);
        if (globalId && globalId === info.callId) {
          if (info.sessionId) globalCall.sessionId = info.sessionId;
          if (info.callIdentifier) globalCall.callIdentifier = info.callIdentifier;
          if (info.doctorJoinUrl) globalCall.doctorJoinUrl = info.doctorJoinUrl;
          if (info.patientPaymentUrl) globalCall.patientPaymentUrl = info.patientPaymentUrl;
          if (info.doctorId && !globalCall.doctorId) globalCall.doctorId = info.doctorId;
          if (info.patientId && !globalCall.patientId) globalCall.patientId = info.patientId;
          if (info.channelName && !globalCall.channel) globalCall.channel = info.channelName;
        }
      }
    }

    function updateActiveCallLinks(info){
      if (!info) return;
      try {
        if (!globalAlertOpen || !window.Swal) return;
        const currentCallId = normaliseCallId(globalCall || {});
        if (!currentCallId || currentCallId !== info.callId) return;
        const container = window.Swal.getHtmlContainer?.();
        if (!container) return;
        try {
          populateSwalContent(container, globalCall);
        } catch (_err) {
          /* swallow render issues */
        }
        const doctorRow = container.querySelector('[data-role="doctor-link"]');
        const paymentRow = container.querySelector('[data-role="payment-link"]');
        populateLinkRow(doctorRow, info.doctorJoinUrl || resolveDoctorJoinUrl(globalCall), 'Doctor Join Link');
        populateLinkRow(paymentRow, info.patientPaymentUrl || resolvePaymentUrl(globalCall), 'Payment Page');
      } catch (err) {
        console.warn('[snoutiq-call] failed to update active call links', err);
      }
    }

    async function persistCallSession(payload){
      const callId = normaliseCallId(payload);
      const channel = (payload?.channel || payload?.channel_name || '').toString().trim();
      const doctorIdRaw = payload?.doctorId ?? payload?.doctor_id ?? currentDoctorId ?? null;
      const patientIdRaw = payload?.patientId ?? payload?.patient_id ?? null;
      const doctorId = Number(doctorIdRaw);
      const patientId = Number(patientIdRaw);

      if (!callId || !channel || Number.isNaN(patientId) || !patientId) {
        return null;
      }

      if (callSessionCache.has(callId)) {
        const cached = callSessionCache.get(callId);
        applySessionInfoToPayload(payload, cached);
        updateActiveCallLinks(cached);
        return cached;
      }

      try {
        const body = {
          call_id: callId,
          channel_name: channel,
          patient_id: patientId,
        };
        if (!Number.isNaN(doctorId) && doctorId) {
          body.doctor_id = doctorId;
        }
        const res = await fetch(`${API_BASE}/call/create`, {
          method: 'POST',
          headers: API_HEADERS,
          credentials: 'include',
          body: JSON.stringify(body),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        const session = data?.session ?? {};
        const info = {
          callId,
          sessionId: data?.session_id ?? session?.id ?? null,
          callIdentifier: data?.call_identifier ?? session?.call_identifier ?? callId,
          doctorJoinUrl: data?.doctor_join_url ?? session?.doctor_join_url ?? null,
          patientPaymentUrl: data?.patient_payment_url ?? session?.patient_payment_url ?? null,
          doctorId: data?.doctor_id ?? session?.doctor_id ?? (!Number.isNaN(doctorId) ? doctorId : null),
          patientId: data?.patient_id ?? session?.patient_id ?? patientId,
          channelName: data?.channel_name ?? session?.channel_name ?? channel,
          accepted: (data?.status ?? session?.status) === 'accepted',
        };
        callSessionCache.set(callId, info);
        applySessionInfoToPayload(payload, info);
        updateActiveCallLinks(info);
        emitCallEvent('snoutiq:call-session-synced', { callId, info });
        return info;
      } catch (err) {
        console.warn('[snoutiq-call] persist session failed', err);
        return null;
      }
    }

    async function markSessionAccepted(payload){
      const callId = normaliseCallId(payload);
      if (!callId) return null;
      const doctorIdRaw = payload?.doctorId ?? payload?.doctor_id ?? currentDoctorId ?? null;
      const doctorId = Number(doctorIdRaw);
      let info = callSessionCache.get(callId);
      if (!info) {
        info = await persistCallSession(payload);
      }
      if (!info) return null;
      if (info.accepted) {
        applySessionInfoToPayload(payload, info);
        updateActiveCallLinks(info);
        return info;
      }
      if (!info.sessionId || Number.isNaN(doctorId) || !doctorId) {
        applySessionInfoToPayload(payload, info);
        return info;
      }
      try {
        const res = await fetch(`${API_BASE}/call/${encodeURIComponent(info.sessionId)}/accept`, {
          method: 'POST',
          headers: API_HEADERS,
          credentials: 'include',
          body: JSON.stringify({ doctor_id: doctorId }),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        const session = data?.session ?? {};
        info.accepted = true;
        info.doctorId = session?.doctor_id ?? data?.doctor_id ?? doctorId ?? info.doctorId ?? null;
        info.doctorJoinUrl = data?.doctor_join_url ?? session?.doctor_join_url ?? info.doctorJoinUrl ?? null;
        info.patientPaymentUrl = data?.patient_payment_url ?? session?.patient_payment_url ?? info.patientPaymentUrl ?? null;
        info.callIdentifier = data?.call_identifier ?? session?.call_identifier ?? info.callIdentifier ?? callId;
        info.channelName = session?.channel_name ?? info.channelName;
        callSessionCache.set(callId, info);
        applySessionInfoToPayload(payload, info);
        updateActiveCallLinks(info);
        emitCallEvent('snoutiq:call-session-accepted', { callId, info });
      } catch (err) {
        console.warn('[snoutiq-call] accept session sync failed', err);
      }
      return info;
    }

    async function acceptGlobalCall(payload){
      if (!payload) return;
      const callId = normaliseCallId(payload);
      const channel = (payload.channel || '').toString();
      try {
        await markSessionAccepted(payload);
      } catch (err) {
        console.warn('[snoutiq-call] mark session accepted failed', err);
      }
      if (callId) {
        shownCallNotifications.delete(callId);
      }
      if (socket && callId) {
        socket.emit('call-accepted', {
          callId,
          doctorId: Number(currentDoctorId || payload.doctorId || payload.doctor_id || 0),
          patientId: Number(payload.patientId || payload.patient_id || 0),
          channel,
        });
      }
      const target = resolveDoctorJoinUrl(payload);
      if (target) window.location.href = target;
      globalCall = null;
    }

    function rejectGlobalCall(payload, reason = 'rejected'){
      if (!payload) return;
      const callId = (payload.callId || '').toString();
      if (socket && callId) {
        socket.emit('call-rejected', { callId, reason });
      }
      if (callId) {
        shownCallNotifications.delete(callId);
      }
      globalCall = null;
    }

    function dismissGlobalCall(reason){
      try{
        if (globalAlertOpen && window.Swal) Swal.close();
      }catch(_){}
      stopGlobalTone();
      globalAlertOpen = false;
      const callId = normaliseCallId(globalCall);
      if (reason && globalCall) {
        rejectGlobalCall(globalCall, reason);
      }
      if (globalCall && reason) {
        emitCallEvent('snoutiq:call-overlay-dismissed', { reason, payload: globalCall });
      }
      if (callId) {
        shownCallNotifications.delete(callId);
      }
      globalCall = null;
    }

    let summaryFetchToken = 0;

    // UPDATED: dark glass / dropdown styling
    function injectCallStyles(){
      if (document.getElementById('snoutiq-call-styles')) return;
      try {
        const style = document.createElement('style');
        style.id = 'snoutiq-call-styles';
        style.textContent = `
          .snoutiq-incoming-call{
            border-radius:24px!important;
            padding:0!important;
            background:#0f172a!important;
            box-shadow:0 40px 120px -20px rgba(0,0,0,.9),0 2px 80px 0 rgba(15,23,42,.8)!important;
            font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif!important;
            color:#f8fafc!important;
            border:1px solid rgba(148,163,184,.2);
            overflow:hidden;
          }
          .snoutiq-incoming-call .swal2-title{display:none!important;}
          .snoutiq-incoming-call .swal2-html-container{
            margin:0!important;
            padding:0!important;
          }
          .snoutiq-incoming-call .swal2-actions{
            margin:0!important;
            padding:16px 20px 20px!important;
            display:flex!important;
            gap:12px!important;
            background:rgba(15,23,42,.6);
            border-top:1px solid rgba(148,163,184,.15);
            backdrop-filter:blur(12px);
          }

          .snoutiq-call-card{
            display:flex;
            flex-direction:column;
            background:
              radial-gradient(circle at -20% -10%,rgba(99,102,241,.35) 0%,transparent 60%),
              radial-gradient(circle at 120% 20%,rgba(16,185,129,.25) 0%,transparent 60%),
              radial-gradient(circle at 50% 120%,rgba(244,63,94,.2) 0%,transparent 60%),
              #0f172a;
            padding:20px 20px 12px;
            gap:16px;
          }

          .snoutiq-call-top{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:16px;
          }
          .snoutiq-call-left{
            display:flex;
            align-items:flex-start;
            gap:14px;
            min-width:0;
          }
          .snoutiq-avatar{
            width:52px;
            height:52px;
            border-radius:14px;
            background:radial-gradient(circle at 30% 30%,#1e293b 0%,#0f172a 60%);
            border:1px solid rgba(226,232,240,.08);
            box-shadow:0 20px 40px -10px rgba(0,0,0,.9),0 0 20px rgba(16,185,129,.4) inset;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#10b981;
          }
          .snoutiq-avatar svg{
            width:26px;
            height:26px;
            stroke-width:1.4;
          }

          .snoutiq-call-idblock{
            display:flex;
            flex-direction:column;
            min-width:0;
          }
          .snoutiq-call-title-row{
            display:flex;
            align-items:center;
            flex-wrap:wrap;
            gap:8px;
          }
          .snoutiq-call-incoming-label{
            font-size:11px;
            line-height:1.2;
            font-weight:600;
            color:#10b981;
            background:rgba(16,185,129,.12);
            border:1px solid rgba(16,185,129,.4);
            border-radius:9999px;
            padding:4px 8px;
            text-transform:uppercase;
            letter-spacing:.08em;
          }
          .snoutiq-call-patient{
            font-size:16px;
            line-height:1.3;
            font-weight:600;
            color:#f8fafc;
            letter-spacing:-.03em;
            min-width:0;
          }
          .snoutiq-call-patient-sub{
            font-size:12px;
            font-weight:500;
            color:#94a3b8;
          }
          .snoutiq-call-patient-sub.is-hidden{display:none;}

          .snoutiq-call-meta{
            margin-left:auto;
            text-align:right;
            flex-shrink:0;
            display:flex;
            flex-direction:column;
            gap:6px;
            min-width:140px;
          }
          .snoutiq-call-meta-label{
            font-size:10px;
            font-weight:600;
            line-height:1.2;
            color:#64748b;
            text-transform:uppercase;
            letter-spacing:.16em;
          }
          .snoutiq-call-channel{
            font-family:'JetBrains Mono','Fira Mono',ui-monospace,monospace;
            font-size:12px;
            line-height:1.4;
            font-weight:500;
            color:#f8fafc;
            background:rgba(30,41,59,.6);
            border:1px solid rgba(148,163,184,.28);
            box-shadow:0 12px 32px -8px rgba(0,0,0,.9);
            padding:6px 10px;
            border-radius:10px;
            word-break:break-all;
          }
          .snoutiq-call-meta-time{
            font-size:11px;
            line-height:1.4;
            color:#94a3b8;
            white-space:nowrap;
          }

          .snoutiq-sections{
            display:flex;
            flex-direction:column;
            gap:12px;
            width:100%;
          }

          .snoutiq-section-card{
            background:rgba(15,23,42,.6);
            border:1px solid rgba(148,163,184,.18);
            border-radius:14px;
            padding:12px 14px 10px;
            box-shadow:0 30px 80px -20px rgba(0,0,0,.8),0 0 120px rgba(96,165,250,.15) inset;
            backdrop-filter:blur(10px);
          }
          .snoutiq-section-head{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            margin-bottom:8px;
          }
          .snoutiq-section-left{
            display:flex;
            flex-direction:column;
            gap:4px;
          }
          .snoutiq-section-title{
            font-size:11px;
            line-height:1.2;
            font-weight:600;
            color:#94a3b8;
            text-transform:uppercase;
            letter-spacing:.14em;
          }
          .snoutiq-section-subtag{
            font-size:10px;
            line-height:1.2;
            font-weight:500;
            color:#38bdf8;
            text-transform:uppercase;
            letter-spacing:.12em;
          }
          .snoutiq-section-status{
            font-size:12px;
            line-height:1.5;
            color:#fda4af;
            font-weight:500;
          }

          .snoutiq-lines{
            display:flex;
            flex-direction:column;
            gap:6px;
            font-size:13px;
            line-height:1.55;
            color:#e2e8f0;
          }
          .snoutiq-line{
            position:relative;
            padding-left:14px;
            font-weight:400;
          }
          .snoutiq-line::before{
            content:'';
            position:absolute;
            top:7px;
            left:3.5px;
            width:4px;
            height:4px;
            border-radius:9999px;
            background:#475569;
          }
          .snoutiq-strong{
            font-weight:600;
            color:#fff;
          }
          .snoutiq-lines-empty{
            font-size:12px;
            font-weight:400;
            color:#64748b;
          }

          .snoutiq-links-wrap{
            display:flex;
            flex-direction:column;
            gap:10px;
          }

          .snoutiq-link-row{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            background:rgba(15,23,42,.4);
            border:1px solid rgba(148,163,184,.22);
            border-radius:12px;
            padding:10px 12px;
            transition:all .18s ease;
          }
          .snoutiq-link-row:not(.is-disabled):hover{
            border-color:rgba(129,140,248,.6);
            box-shadow:0 26px 60px -22px rgba(59,130,246,.8);
            background:rgba(30,41,59,.6);
          }
          .snoutiq-link-row.is-disabled{
            opacity:.5;
          }

          .snoutiq-link-main{
            display:flex;
            flex-direction:column;
            gap:3px;
          }
          .snoutiq-link-label{
            font-size:10px;
            font-weight:600;
            color:#f8fafc;
            text-transform:uppercase;
            letter-spacing:.12em;
          }
          .snoutiq-link-value{
            font-size:12px;
            line-height:1.4;
            color:#94a3b8;
            font-family:'JetBrains Mono','Fira Mono',ui-monospace,monospace;
            word-break:break-all;
            max-width:220px;
          }
          .snoutiq-link-actions{
            display:flex;
            flex-direction:column;
            align-items:flex-end;
            gap:6px;
            flex-shrink:0;
          }
          .snoutiq-link-open{
            text-decoration:none;
            font-size:12px;
            font-weight:600;
            line-height:1.2;
            color:#38bdf8;
            background:rgba(8,51,68,.6);
            border:1px solid rgba(56,189,248,.4);
            border-radius:8px;
            padding:6px 10px;
            min-width:60px;
            text-align:center;
          }
          .snoutiq-link-open[aria-disabled="true"]{
            opacity:.4;
            pointer-events:none;
          }
          .snoutiq-link-copy{
            font-size:11px;
            font-weight:500;
            line-height:1.2;
            color:#1e293b;
            background:#f8fafc;
            border:1px solid #cbd5e1;
            border-radius:8px;
            padding:5px 8px;
            cursor:pointer;
            min-width:60px;
            text-align:center;
            transition:all .15s ease;
          }
          .snoutiq-link-copy:hover{
            background:#e2e8f0;
          }
          .snoutiq-link-copy.is-copied{
            background:#ecfdf5;
            border-color:#86efac;
            color:#065f46;
          }

          .snoutiq-footer-note{
            font-size:11px;
            line-height:1.4;
            color:#475569;
            text-align:center;
            margin-top:6px;
          }

          .snoutiq-btn{
            flex:1 1 0%;
            border-radius:14px;
            padding:14px 0;
            font-weight:600;
            font-size:15px;
            line-height:1.2;
            letter-spacing:.01em;
            transition:transform .18s ease, box-shadow .18s ease, background .18s ease;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            border:none;
          }
          .snoutiq-btn:focus{
            outline:none!important;
            box-shadow:0 0 0 3px rgba(59,130,246,.35)!important;
          }
          .snoutiq-btn-accept{
            background:radial-gradient(circle at 0% 0%,#10b981 0%,#047857 70%);
            color:#fff;
            box-shadow:0 30px 60px -15px rgba(16,185,129,.8),0 0 60px rgba(16,185,129,.6);
          }
          .snoutiq-btn-accept:hover{
            transform:translateY(-1px);
            box-shadow:0 40px 70px -10px rgba(16,185,129,.9),0 0 80px rgba(16,185,129,.7);
          }
          .snoutiq-btn-reject{
            background:radial-gradient(circle at 0% 0%,#ef4444 0%,#7f1d1d 70%);
            color:#fff;
            box-shadow:0 30px 60px -15px rgba(239,68,68,.8),0 0 60px rgba(239,68,68,.45);
          }
          .snoutiq-btn-reject:hover{
            transform:translateY(-1px);
            box-shadow:0 40px 70px -10px rgba(239,68,68,.9),0 0 80px rgba(239,68,68,.6);
          }
          .snoutiq-btn svg{
            width:18px;
            height:18px;
            stroke-width:1.6;
          }
        `;
        document.head.appendChild(style);
      } catch (_) {
        /* ignore styling errors */
      }
    }

    function extractPatientId(payload){
      if (!payload || typeof payload !== 'object') return null;
      const candidates = [
        payload.patientId,
        payload.patient_id,
        payload?.patient?.id,
        payload?.user_id,
        payload?.user?.id,
        payload?.booking?.patient_id,
      ];
      for (const value of candidates) {
        const num = Number(value);
        if (!Number.isNaN(num) && num) return num;
      }
      return null;
    }

    function extractPatientName(payload){
      if (!payload || typeof payload !== 'object') return '';
      const candidates = [
        payload.patientName,
        payload.patient_name,
        payload?.patient?.name,
        payload?.patient?.full_name,
        payload?.patient?.display_name,
        payload?.booking?.patient_name,
        payload?.user?.name,
        payload?.user?.full_name,
        payload?.user?.display_name,
        payload?.meta?.patient_name,
      ];
      for (const value of candidates) {
        if (typeof value === 'string' && value.trim()) {
          return value.trim();
        }
      }
      return '';
    }

    function extractCallTemplate(payload){
      if (!payload || typeof payload !== 'object') return '';
      const segments = [];
      const rawCandidates = [
        payload.template,
        payload.callTemplate,
        payload.call_template,
        payload.consultationTemplate,
        payload.consultation_template,
        payload?.context?.template,
        payload?.context?.consultation_template,
        payload?.booking?.template,
        payload?.booking?.consultation_template,
        payload?.meta?.template,
        payload?.meta?.consultation_template,
        payload?.details?.template,
        payload?.payload?.template,
        payload?.payload?.consultation_template,
      ];
      rawCandidates.forEach(entry => {
        if (!entry) return;
        if (typeof entry === 'string' && entry.trim()) {
          segments.push(entry.trim());
          return;
        }
        if (Array.isArray(entry)) {
          const merged = entry.map(value => {
            if (typeof value === 'string') return value.trim();
            if (value && typeof value === 'object') {
              try {
                const text = Object.values(value).map(v => String(v ?? '').trim()).filter(Boolean).join(': ');
                return text.trim();
              } catch (_) {
                return '';
              }
            }
            return '';
          }).filter(Boolean).join('\n');
          if (merged.trim()) segments.push(merged.trim());
        }
        if (entry && typeof entry === 'object' && !Array.isArray(entry)) {
          try {
            const text = Object.entries(entry)
              .map(([key,val]) => {
                const value = Array.isArray(val) ? val.join(', ') : (val ?? '');
                const str = String(value).trim();
                if (!str) return '';
                return `${key.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}: ${str}`;
              })
              .filter(Boolean)
              .join('\n');
            if (text.trim()) segments.push(text.trim());
          } catch (_) {}
        }
      });

      const labelled = [
        ['Symptoms', payload.symptoms || payload.symptom || payload?.details?.symptoms],
        ['Concern', payload.concern || payload.issue || payload.reason],
        ['Notes', payload.notes || payload.note || payload?.details?.notes],
        ['Previous Diagnosis', payload.previousDiagnosis || payload.previous_diagnosis],
      ];
      labelled.forEach(([label, value]) => {
        if (typeof value === 'string' && value.trim()) {
          segments.push(`${label}: ${value.trim()}`);
        }
      });

      return segments.join('\n').trim();
    }

    // UPDATED renderLines(): matches dark theme classes
    function renderLines(container, lines, opts = {}){
      if (!container) return;
      const {
        emptyMessage    = 'No information available.',
        emptyColor      = '#64748b',
        fontSize        = '13px',
        lineHeight      = '1.55',
        textColor       = '#e2e8f0',
        labelColor      = '#fff',
        highlightLabels = true,
      } = opts;

      container.innerHTML = '';
      try { container.classList.add('snoutiq-lines'); } catch (_) {}
      let hasContent = false;

      const safeLines = Array.isArray(lines)
        ? lines
        : (typeof lines === 'string' ? lines.split(/\n+/) : []);

      safeLines.forEach(raw => {
        const trimmed = (raw || '').toString().trim();
        if (!trimmed) return;

        const line = document.createElement('div');
        line.className = 'snoutiq-line';
        line.style.fontSize   = fontSize;
        line.style.lineHeight = lineHeight;
        line.style.color      = textColor;

        if (highlightLabels) {
          const idx = trimmed.indexOf(':');
          if (idx > 0 && idx < 40) {
            const label = trimmed.slice(0, idx).trim();
            const value = trimmed.slice(idx + 1).trim();
            if (label && value) {
              const strong = document.createElement('span');
              strong.className = 'snoutiq-strong';
              strong.style.color = labelColor;
              strong.style.fontWeight = '600';
              strong.textContent = `${label}:`;
              line.appendChild(strong);
              line.appendChild(document.createTextNode(` ${value}`));
            } else {
              line.textContent = trimmed;
            }
          } else {
            line.textContent = trimmed;
          }
        } else {
          line.textContent = trimmed;
        }

        container.appendChild(line);
        hasContent = true;
      });

      if (!container.childElementCount) {
        const span = document.createElement('span');
        span.className = 'snoutiq-lines-empty';
        span.style.color = emptyColor;
        span.textContent = emptyMessage;
        container.appendChild(span);
      }

      return hasContent;
    }

    function parseAiSummary(summaryText){
      const text = (summaryText || '').trim();
      if (!text) return [];

      const diagnosisLines = [];
      let inDiagnosis = false;
      let seenDiagnosisSection = false;

      text.split(/\n+/).forEach(line => {
        const trimmed = line.trim();
        if (!trimmed) return;

        if (/^===\s*DIAGNOSIS\s*===/i.test(trimmed)) {
          inDiagnosis = true;
          seenDiagnosisSection = true;
          return;
        }

        if (/^===/i.test(trimmed)) {
          if (inDiagnosis) {
            inDiagnosis = false;
          }
          return;
        }

        if (!inDiagnosis) {
          return;
        }

        if (/^Q:/i.test(trimmed)) {
          diagnosisLines.push(`Q: ${trimmed.replace(/^Q:\s*/i, '').trim()}`);
          return;
        }

        if (/^A:/i.test(trimmed)) {
          const value = trimmed.replace(/^A:\s*/i, '').trim();
          diagnosisLines.push(`Diagnosis: ${value}`);
          return;
        }

        diagnosisLines.push(trimmed);
      });

      if (diagnosisLines.length) {
        return diagnosisLines;
      }

      if (seenDiagnosisSection) {
        return [];
      }

      const fallback = [];
      text.split(/\n+/).forEach(line => {
        const trimmed = line.trim();
        if (!trimmed) return;
        if (/^Q:/i.test(trimmed)) {
          fallback.push(`Q: ${trimmed.replace(/^Q:\s*/i, '').trim()}`);
          return;
        }
        if (/^A:/i.test(trimmed)) {
          const value = trimmed.replace(/^A:\s*/i, '').trim();
          fallback.push(`A: ${value}`);
          return;
        }
        fallback.push(trimmed);
      });

      if (fallback.length) {
        return fallback;
      }

      return text.split(/\n+/).map(l => l.trim()).filter(Boolean);
    }

    async function loadSwalSummary(container, patientId){
      const summaryEl = container?.querySelector('[data-role="summary"]');
      const statusEl = container?.querySelector('[data-role="summary-status"]');
      const token = ++summaryFetchToken;

      if (summaryEl) {
        summaryEl.innerHTML = '';
        summaryEl.style.display = 'none';
      }
      if (statusEl) {
        statusEl.style.display = 'block';
        statusEl.style.fontSize = '12px';
        statusEl.style.color = '#fda4af';
        statusEl.textContent = patientId ? 'Fetching AI summary…' : 'Patient ID missing.';
      }

      if (!patientId) {
        return;
      }

      try {
        const url = `${API_BASE}/ai/summary?user_id=${encodeURIComponent(patientId)}&limit=1&summarize=1`;
        const res = await fetch(url, { credentials: 'include' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        if (summaryFetchToken !== token) return;
        const summaryRaw = [
          data?.summary,
          data?.data?.summary,
          data?.data?.data?.summary,
          data?.payload?.summary,
          data?.result?.summary,
        ].find(value => typeof value === 'string' && value.trim());
        const summaryText = (summaryRaw || '').trim();

        if (summaryText && summaryEl) {
          const lines = parseAiSummary(summaryText);
          const hasSummary = renderLines(summaryEl, lines, {
            emptyMessage: 'No recent AI chats found.',
            emptyColor:   '#64748b',
            fontSize:     '13px',
            lineHeight:   '1.6',
            textColor:    '#e2e8f0',
            labelColor:   '#fff',
          });
          summaryEl.style.display = 'block';
          if (statusEl) {
            statusEl.style.display = hasSummary ? 'none' : 'block';
            if (!hasSummary) {
              statusEl.textContent = 'No recent AI chats found.';
            }
          }
        } else if (statusEl) {
          statusEl.textContent = 'No recent AI chats found.';
        }
      } catch (err) {
        if (summaryFetchToken !== token) return;
        console.warn('[snoutiq-call] failed to load AI summary', err);
        if (statusEl) {
          statusEl.style.display = 'block';
          statusEl.textContent = 'Unable to load AI chat summary.';
        }
      }
    }

    function formatCallTime(value){
      try {
        if (!value) return '';
        const date = value instanceof Date ? value : new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleString(undefined, {
          day: '2-digit',
          month: 'short',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        });
      } catch (_) {
        return '';
      }
    }

    function populateSwalContent(container, payload){
      if (!container) return;
      const patientEl = container.querySelector('[data-role="call-patient"]');
      const patientSubEl = container.querySelector('[data-role="call-patient-sub"]');
      const timeEl = container.querySelector('[data-role="call-time"]');
      const channelEl = container.querySelector('[data-role="call-channel"]');
      const templateEl = container.querySelector('[data-role="template"]');

      const patientId = extractPatientId(payload);
      const patientName = extractPatientName(payload);
      if (patientEl) {
        const displayName = patientName || (patientId ? `Patient ID #${patientId}` : 'Incoming video consultation');
        patientEl.textContent = displayName;
      }
      if (patientSubEl) {
        if (patientName && patientId) {
          patientSubEl.textContent = `Patient ID #${patientId}`;
          patientSubEl.classList.remove('is-hidden');
        } else {
          patientSubEl.textContent = '';
          patientSubEl.classList.add('is-hidden');
        }
      }

      if (timeEl) {
        const ts = payload?.timestamp || payload?.createdAt || payload?.created_at || Date.now();
        const formatted = formatCallTime(ts);
        if (formatted) {
          timeEl.textContent = `Since ${formatted}`;
          timeEl.style.display = '';
        } else {
          timeEl.textContent = '';
          timeEl.style.display = 'none';
        }
      }

      const channelCandidates = [
        payload?.channel,
        payload?.channelName,
        payload?.channel_name,
        payload?.call?.channel,
        payload?.call?.channel_name,
        payload?.context?.channel,
        payload?.meta?.channel,
      ];
      let channel = '';
      for (const value of channelCandidates) {
        if (value == null) continue;
        const str = String(value).trim();
        if (str) { channel = str; break; }
      }
      if (channelEl) {
        channelEl.textContent = channel || '—';
        if (channel) {
          channelEl.title = channel;
        } else {
          channelEl.removeAttribute('title');
        }
      }

      if (templateEl) {
        const templateText = extractCallTemplate(payload);
        renderLines(templateEl, templateText, {
          emptyMessage: 'No consultation template shared yet.',
          emptyColor:   '#64748b',
          textColor:    '#e2e8f0',
          labelColor:   '#fff',
        });
      }

      const doctorLinkRow = container.querySelector('[data-role="doctor-link"]');
      const paymentLinkRow = container.querySelector('[data-role="payment-link"]');
      populateLinkRow(doctorLinkRow, resolveDoctorJoinUrl(payload), 'Doctor Join Link');
      populateLinkRow(paymentLinkRow, resolvePaymentUrl(payload), 'Payment Page');

      return patientId;
    }

    // UPDATED modal HTML (dark dropdown style)
    function renderGlobalCallAlert(payload){
      if (window.DOCTOR_PAGE_HANDLE_CALLS) return;
      globalCall = payload;
      emitCallEvent('snoutiq:call-overlay-open', payload);
      startGlobalTone();
      if (globalAlertOpen) return;
      if (window.Swal) {
        injectCallStyles();
        globalAlertOpen = true;

        const html = `
          <div class="snoutiq-call-card">

            <div class="snoutiq-call-top">
              <div class="snoutiq-call-left">
                <div class="snoutiq-avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                  </svg>
                </div>
                <div class="snoutiq-call-idblock">
                  <div class="snoutiq-call-title-row">
                    <div class="snoutiq-call-incoming-label">Incoming Call</div>
                    <div class="snoutiq-call-patient" data-role="call-patient">Patient…</div>
                  </div>
                  <div class="snoutiq-call-patient-sub is-hidden" data-role="call-patient-sub"></div>
                </div>
              </div>

              <div class="snoutiq-call-meta">
                <span class="snoutiq-call-meta-label">Channel</span>
                <span class="snoutiq-call-channel" data-role="call-channel">—</span>
                <span class="snoutiq-call-meta-time" data-role="call-time"></span>
              </div>
            </div>

            <div class="snoutiq-sections">
              <div class="snoutiq-section-card">
                <div class="snoutiq-section-head">
                  <div class="snoutiq-section-left">
                    <div class="snoutiq-section-title">Consultation Details</div>
                    <div class="snoutiq-section-subtag">Live intake</div>
                  </div>
                </div>
                <div class="snoutiq-lines" data-role="template">
                  <div class="snoutiq-lines-empty">No consultation template shared yet.</div>
                </div>
              </div>

              <div class="snoutiq-section-card">
                <div class="snoutiq-section-head">
                  <div class="snoutiq-section-left">
                    <div class="snoutiq-section-title">AI Chat Summary</div>
                    <div class="snoutiq-section-subtag">Latest Chats</div>
                  </div>
                  <div class="snoutiq-section-status" data-role="summary-status">Fetching AI summary…</div>
                </div>
                <div class="snoutiq-lines" data-role="summary" style="display:none;"></div>
              </div>

              <div class="snoutiq-section-card">
                <div class="snoutiq-section-head">
                  <div class="snoutiq-section-left">
                    <div class="snoutiq-section-title">Links</div>
                    <div class="snoutiq-section-subtag">Session Access</div>
                  </div>
                </div>

                <div class="snoutiq-links-wrap">

                  <div class="snoutiq-link-row is-disabled" data-role="doctor-link">
                    <div class="snoutiq-link-main">
                      <span class="snoutiq-link-label" data-role="link-label">Doctor Join Link</span>
                      <span class="snoutiq-link-value" data-role="link-value">Not available yet</span>
                    </div>
                    <div class="snoutiq-link-actions">
                      <a class="snoutiq-link-open"
                         data-role="link-open"
                         href="#"
                         target="_blank"
                         rel="noopener noreferrer"
                         aria-disabled="true">Open</a>
                      <button type="button"
                              class="snoutiq-link-copy"
                              data-role="link-copy"
                              disabled>Copy</button>
                    </div>
                  </div>

                  <div class="snoutiq-link-row is-disabled" data-role="payment-link">
                    <div class="snoutiq-link-main">
                      <span class="snoutiq-link-label" data-role="link-label">Payment Page</span>
                      <span class="snoutiq-link-value" data-role="link-value">Not available yet</span>
                    </div>
                    <div class="snoutiq-link-actions">
                      <a class="snoutiq-link-open"
                         data-role="link-open"
                         href="#"
                         target="_blank"
                         rel="noopener noreferrer"
                         aria-disabled="true">Open</a>
                      <button type="button"
                              class="snoutiq-link-copy"
                              data-role="link-copy"
                              disabled>Copy</button>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <div class="snoutiq-footer-note">
              Keep this dashboard open to stay available for video consults.
            </div>
          </div>
        `;

        Swal.fire({
          title: '',
          html,
          width: 460,
          icon: undefined,
          confirmButtonText: 'Accept',
          showDenyButton: true,
          denyButtonText: 'Reject',
          buttonsStyling: false,
          customClass: {
            popup: 'snoutiq-incoming-call',
            confirmButton: 'snoutiq-btn snoutiq-btn-accept',
            denyButton: 'snoutiq-btn snoutiq-btn-reject'
          },
          allowOutsideClick: false,
          didOpen: (popupEl) => {
            startGlobalTone();
            try {
              const patientId = populateSwalContent(popupEl, payload);
              loadSwalSummary(popupEl, patientId);
            } catch (err) {
              console.warn('[snoutiq-call] failed to populate call alert', err);
            }
          },
          willClose: () => {
            stopGlobalTone();
            globalAlertOpen = false;
          }
        }).then(result => {
          globalAlertOpen = false;
          stopGlobalTone();
          if (result.isConfirmed) {
            acceptGlobalCall(payload);
          } else if (result.isDenied) {
            rejectGlobalCall(payload, 'denied');
          }
        });
        return;
      }

      const accept = window.confirm('Incoming call - join now?');
      stopGlobalTone();
      if (accept) acceptGlobalCall(payload);
      else rejectGlobalCall(payload, 'dismissed');
    }

    function setHeaderStatus(state){
      if (statusDot) {
        statusDot.classList.remove('bg-yellow-400','bg-green-500','bg-red-500');
      }
      if (statusPill) {
        statusPill.classList.remove('hidden','bg-green-100','text-green-700','bg-red-100','text-red-700','bg-yellow-100','text-yellow-700');
      }

      switch (state) {
        case 'online':
          if (statusDot) statusDot.classList.add('bg-green-500');
          if (statusPill) {
            statusPill.textContent = 'Online';
            statusPill.classList.add('bg-green-100','text-green-700');
          }
          break;
        case 'offline':
          if (statusDot) statusDot.classList.add('bg-red-500');
          if (statusPill) {
            statusPill.textContent = 'Offline';
            statusPill.classList.add('bg-red-100','text-red-700');
          }
          break;
        case 'error':
          if (statusDot) statusDot.classList.add('bg-red-500');
          if (statusPill) {
            statusPill.textContent = 'Connection Error';
            statusPill.classList.add('bg-red-100','text-red-700');
          }
          break;
        default:
          if (statusDot) statusDot.classList.add('bg-yellow-400');
          if (statusPill) {
            statusPill.textContent = 'Connecting...';
            statusPill.classList.add('bg-yellow-100','text-yellow-700');
          }
          break;
      }

      if (statusPill) statusPill.classList.remove('hidden');
      emitCallEvent('snoutiq:socket-status', { state, doctorId: currentDoctorId });
    }

    function applyVisibility(on){
      clinicVisible = !!on;
      localStorage.setItem('clinic_visible', on ? 'on' : 'off');
      if (label) label.textContent = on ? 'Visible' : 'Hidden';
    }

    async function ensureNotificationPermission(interactive){
      if (typeof Notification === 'undefined' || !navigator.serviceWorker) {
        notificationsEnabled = false;
        return false;
      }
      if (Notification.permission === 'granted') {
        notificationsEnabled = true;
        return true;
      }
      if (Notification.permission === 'denied') {
        notificationsEnabled = false;
        return false;
      }
      if (!interactive) {
        return false;
      }
      try {
        const result = await Notification.requestPermission();
        notificationsEnabled = result === 'granted';
        return notificationsEnabled;
      } catch (_err) {
        notificationsEnabled = false;
        return false;
      }
    }

    function syncDoctorVisibility(){
      if (!socket || !socket.connected) return;
      if (!currentDoctorId) return;
      try{
        socket.emit(VISIBILITY_EVENT, {
          doctorId: Number(currentDoctorId),
          visible: clinicVisible,
        });
      }catch(err){
        console.warn('[snoutiq-call] failed to sync doctor visibility', err);
      }
    }

    function ensureSocket(){
      if (socket) return socket;
      if (typeof window !== 'undefined' && window.__SNOUTIQ_SOCKET) {
        socket = window.__SNOUTIQ_SOCKET;
        return socket;
      }
      if (typeof io === 'undefined') return socket;
      socket = io(SOCKET_URL, {
        transports: ['websocket','polling'],
        withCredentials: false,
        path: '/socket.io/',
        autoConnect: false,
        reconnection: true,
      });
      window.__SNOUTIQ_SOCKET = socket;

      socket.on('connect', ()=>{
        setHeaderStatus('connecting');
        joined = false;
        clearReconnectTimer();
        if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
        exitBackgroundHold();
        if (currentDoctorId) {
          socket.emit('join-doctor', Number(currentDoctorId));
        }
        syncDoctorVisibility();
        startHeartbeat();
        ackTimer = setTimeout(()=>{
          if (!joined) setHeaderStatus('online');
        }, 1500);
      });

      socket.on('doctor-online', (payload)=>{
        if (!payload) return;
        const match = Number(payload.doctorId);
        if (currentDoctorId && match === Number(currentDoctorId)) {
          joined = true;
          exitBackgroundHold();
          if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
          setHeaderStatus(clinicVisible ? 'online' : 'offline');
        }
      });

      socket.on('disconnect', (reason)=>{
        joined = false;
        stopHeartbeat();
        if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
        dismissGlobalCall();
        if (shouldHoldOnline) {
          if (isDocumentHidden()) {
            enterBackgroundHold(reason || 'hidden');
          } else if (backgroundHoldActive) {
            enterBackgroundHold(reason || 'hold');
          } else {
            setHeaderStatus('offline');
          }
          scheduleReconnect({ immediate: !isDocumentHidden() });
        } else {
          clearReconnectTimer();
          setHeaderStatus('offline');
        }
      });

      const manager = socket?.io;
      if (manager && !manager.__SNOUTIQ_RECONNECT_BOUND) {
        manager.__SNOUTIQ_RECONNECT_BOUND = true;
        manager.on('reconnect_attempt', ()=>{
          if (!shouldHoldOnline) return;
          setHeaderStatus('connecting');
        });
        manager.on('reconnect', ()=>{
          exitBackgroundHold();
          setHeaderStatus(clinicVisible ? 'online' : 'offline');
          startHeartbeat();
        });
        manager.on('reconnect_error', (err)=>{
          console.warn('[snoutiq-call] reconnect_error', err?.message || err);
        });
      }

      socket.on('doctor-grace', (payload)=>{
        const match = Number(payload?.doctorId);
        if (!match || match !== Number(currentDoctorId)) return;
        if (payload?.status === 'background' && shouldHoldOnline && (isDocumentHidden() || backgroundHoldActive)) {
          enterBackgroundHold('server-grace');
        }
      });

      socket.on('connect_error', (err)=>{
        console.warn('[snoutiq-call] socket connect_error', err?.message || err);
        dismissGlobalCall();
        setHeaderStatus('error');
        scheduleReconnect({ immediate: false });
      });

      socket.on('call-requested', (payload)=>{
        if (payload?.doctorId) updateDoctorId(payload.doctorId);
        persistCallSession(payload);
        const eventPayload = { ...payload, __source: 'socket' };
        emitCallEvent('snoutiq:call-requested', eventPayload);
        showCallNotification(payload);
        if (!window.DOCTOR_PAGE_HANDLE_CALLS) {
          renderGlobalCallAlert(eventPayload);
        }
      });

      const cancelEvents = ['call-cancelled','call-ended','call-timeout','call-failed'];
      cancelEvents.forEach(eventName=>{
        socket.on(eventName, (payload)=>{
          emitCallEvent(`snoutiq:${eventName}`, payload);
          const callId = normaliseCallId(payload);
          if (callId) {
            shownCallNotifications.delete(callId);
          }
          if (!window.DOCTOR_PAGE_HANDLE_CALLS) {
            dismissGlobalCall();
          }
        });
      });

      return socket;
    }

    function goOnline(opts = {}){
      applyVisibility(true);
      shouldHoldOnline = true;
      clearReconnectTimer();
      if (opts.userTriggered) {
        ensureNotificationPermission(true);
      }
      if (opts.showAlert && window.Swal) {
        Swal.fire({
          icon: 'success',
          title: 'Online',
          text: 'Your clinic is currently visible to patients within 10 km.'
        });
      }
      const sock = ensureSocket();
      if (!sock) {
        setHeaderStatus('error');
        return;
      }
      try{
        sock.io.opts.reconnection = true;
        if (!sock.connected) {
          setHeaderStatus('connecting');
          sock.connect();
        } else if (currentDoctorId && !joined) {
          sock.emit('join-doctor', Number(currentDoctorId));
          startHeartbeat();
        } else {
          setHeaderStatus(clinicVisible ? 'online' : 'offline');
          startHeartbeat();
        }
        syncDoctorVisibility();
        scheduleReconnect({ immediate: false });
      }catch(err){
        console.warn('[snoutiq-call] failed to connect socket', err);
        setHeaderStatus('error');
      }
    }

    function goOffline(opts = {}){
      if (opts.userTriggered) {
        ensureNotificationPermission(true);
      }
      shouldHoldOnline = true;
      applyVisibility(false);
      dismissGlobalCall();
      clearReconnectTimer();
      const sock = ensureSocket();
      if (sock) {
        try{
          sock.io.opts.reconnection = true;
        }catch(_){ }
        if (!sock.connected) {
          setHeaderStatus('connecting');
          try { sock.connect(); } catch(_){ }
        }
      }
      if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
      exitBackgroundHold();
      setHeaderStatus('offline');
      syncDoctorVisibility();
      startHeartbeat();
      sendHeartbeat(true);
      if (opts.showAlert && window.Swal) {
        Swal.fire({
          icon: 'info',
          title: 'Offline',
          text: 'You will not be receiving calls. Turn on this button to receive video consultation calls.'
        });
      }
    }

    const savedVisible = (localStorage.getItem('clinic_visible') ?? 'on') !== 'off';
    shouldHoldOnline = savedVisible;
    if (toggle) toggle.checked = savedVisible;
    applyVisibility(savedVisible);

    document.addEventListener('visibilitychange', ()=>{
      if (!shouldHoldOnline) return;
      if (!isDocumentHidden()) {
        clearReconnectTimer();
        const sock = ensureSocket();
        exitBackgroundHold();
        startHeartbeat();
        sendHeartbeat(true);
        if (sock && !sock.connected) {
          setHeaderStatus('connecting');
          scheduleReconnect({ immediate: true });
        } else if (sock && joined) {
          setHeaderStatus('online');
        }
      } else {
        enterBackgroundHold('visibilitychange');
        scheduleReconnect({ immediate: false });
      }
    });

    window.addEventListener('focus', ()=>{
      if (!shouldHoldOnline) return;
      const sock = ensureSocket();
      exitBackgroundHold();
      startHeartbeat();
      sendHeartbeat(true);
      if (sock && !sock.connected) {
        setHeaderStatus('connecting');
        scheduleReconnect({ immediate: true });
      }
    });

    function hydrateNotificationPayload(data){
      if (!data || typeof data !== 'object') return null;
      const payload = { ...data };
      if (!payload.callId && payload.call_id) payload.callId = payload.call_id;
      if (!payload.channel && payload.channel_name) payload.channel = payload.channel_name;
      if (!payload.doctorId && payload.doctor_id) payload.doctorId = payload.doctor_id;
      if (!payload.patientId && payload.patient_id) payload.patientId = payload.patient_id;
      return payload;
    }

    function handleServiceWorkerMessage(event){
      const data = event?.data || {};
      const type = typeof data?.type === 'string' ? data.type : null;
      if (!type) return;

      if (type === 'snoutiq-incoming-call') {
        const payload = hydrateNotificationPayload(data.call);
        if (!payload) return;
        if (typeof persistCallSession === 'function') {
          persistCallSession(payload).catch(err => {
            console.warn('[snoutiq-call] failed to persist push session', err);
          });
        }
        const eventPayload = {
          ...payload,
          __source: 'push',
        };
        if (data.sentAt && !eventPayload.__broadcastAt) {
          eventPayload.__broadcastAt = data.sentAt;
        }
        if (data.ringtoneUrl && !eventPayload.ringtoneUrl) {
          eventPayload.ringtoneUrl = data.ringtoneUrl;
        }
        emitCallEvent('snoutiq:call-requested', eventPayload);
        if (!window.DOCTOR_PAGE_HANDLE_CALLS) {
          try {
            renderGlobalCallAlert(eventPayload);
          } catch (err) {
            console.warn('[snoutiq-call] failed to render push call alert', err);
          }
        }
        return;
      }

      if (type !== 'snoutiq-notification') return;

      const action = data.action || 'default';
      const payload = hydrateNotificationPayload(data.call);
      if (action === 'accept' && payload) {
        acceptGlobalCall(payload);
        return;
      }
      if (action === 'dismiss') {
        if (payload) {
          rejectGlobalCall(payload, 'dismissed');
        }
        dismissGlobalCall();
        return;
      }
      if (payload) {
        // focus window on default click
        emitCallEvent('snoutiq:notification-click', payload);
      }
    }

    if (navigator.serviceWorker && typeof navigator.serviceWorker.addEventListener === 'function') {
      navigator.serviceWorker.addEventListener('message', handleServiceWorkerMessage);
    }

    const api = {
      ensureSocket,
      goOnline,
      goOffline,
      accept: acceptGlobalCall,
      reject: rejectGlobalCall,
      dismiss: dismissGlobalCall,
      setStatus: setHeaderStatus,
      updateDoctorId,
      get doctorId(){ return currentDoctorId; },
      get currentCall(){ return globalCall; },
      get socket(){ return ensureSocket(); },
      on(event, handler){
        if (typeof handler !== 'function') return () => {};
        window.addEventListener(event, handler);
        return () => window.removeEventListener(event, handler);
      },
      off(event, handler){
        window.removeEventListener(event, handler);
      },
      isVisible(){ return !!(toggle && toggle.checked); },
    };
    window.snoutiqCall = api;
    emitCallEvent('snoutiq:call-api-ready', api);

    if (savedVisible) {
      goOnline({ showAlert: false, userTriggered: false });
    } else {
      goOffline({ showAlert: false, userTriggered: false });
    }

    if (toggle) {
      toggle.addEventListener('change', function(){
        const on = !!this.checked;
        if (on) {
          goOnline({ showAlert: true, userTriggered: true });
        } else {
          goOffline({ showAlert: true, userTriggered: true });
        }
      });
    }
  });
</script>
