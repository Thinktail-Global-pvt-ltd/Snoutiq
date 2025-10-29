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
    let globalCall = null;
    let globalAlertOpen = false;
    let ringCtx = null;
    let ringOsc = null;
    let ringGain = null;

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

    function ensureAudioContext(){
      if (ringCtx) return ringCtx;
      const Ctor = window.AudioContext || window.webkitAudioContext;
      if (!Ctor) return null;
      ringCtx = new Ctor();
      return ringCtx;
    }

    function startGlobalTone(){
      try{
        const ctx = ensureAudioContext();
        if (!ctx) return;
        if (ctx.state === 'suspended') ctx.resume().catch(()=>{});
        stopGlobalTone(true);
        ringOsc = ctx.createOscillator();
        ringGain = ctx.createGain();
        ringOsc.type = 'triangle';
        ringOsc.frequency.setValueAtTime(660, ctx.currentTime);
        ringGain.gain.setValueAtTime(0, ctx.currentTime);
        ringOsc.connect(ringGain).connect(ctx.destination);
        ringOsc.start();
        ringGain.gain.linearRampToValueAtTime(0.22, ctx.currentTime + 0.05);
        ringGain.gain.setValueAtTime(0.22, ctx.currentTime + 0.25);
        ringGain.gain.linearRampToValueAtTime(0.02, ctx.currentTime + 0.55);
        ringGain.gain.linearRampToValueAtTime(0.22, ctx.currentTime + 0.8);
        setTimeout(()=>{
          if (ringOsc) {
            ringOsc.frequency.setValueAtTime(770, ctx.currentTime);
          }
        }, 400);
      }catch(err){
        console.warn('[snoutiq-call] unable to start ringtone', err);
      }
    }

    function stopGlobalTone(skipFade){
      try{
        if (ringGain) {
          const ctx = ringGain.context;
          if (ctx) {
            const now = ctx.currentTime;
            ringGain.gain.cancelScheduledValues(now);
            if (skipFade) {
              ringGain.gain.setValueAtTime(0, now);
            } else {
              ringGain.gain.linearRampToValueAtTime(0, now + 0.1);
            }
          }
          ringGain.disconnect();
        }
        if (ringOsc) {
          try { ringOsc.stop(); } catch(_){}
          ringOsc.disconnect();
        }
      }catch(err){
        console.warn('[snoutiq-call] unable to stop ringtone', err);
      }
      ringOsc = null;
      ringGain = null;
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
          setHeaderStatus('online');
        }catch(err){
          console.warn('[snoutiq-call] failed to refresh doctor id', err);
        }
      }
      return currentDoctorId;
    }

    function detectFrontendBase(){
      const clean = value => (value || '').toString().trim().replace(/\/+$/, '');
      const meta = document.querySelector('meta[name=\"snoutiq-frontend-base\"]');
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

      // For cases where backend is hosted under /backend, we still want the root origin.
      return origin;
    }

    const FRONTEND_BASE = detectFrontendBase();
    window.__SNOUTIQ_FRONTEND_BASE = FRONTEND_BASE;

    function callUrlFromPayload(payload){
      const channel = (payload?.channel || '').trim();
      if (!channel) return null;
      const base = FRONTEND_BASE || '';
      const search = new URLSearchParams({
        uid: String(currentDoctorId || payload?.doctorId || ''),
        role: 'host',
        pip: '1',
      });
      if (payload?.callId) search.append('callId', String(payload.callId));
      if (payload?.patientId) search.append('patientId', String(payload.patientId));
      return `${base}/call-page/${encodeURIComponent(channel)}?${search.toString()}`;
    }

    function acceptGlobalCall(payload){
      if (!payload) return;
      const callId = (payload.callId || '').toString();
      const channel = (payload.channel || '').toString();
      if (socket && callId) {
        socket.emit('call-accepted', {
          callId,
          doctorId: Number(currentDoctorId || payload.doctorId || 0),
          patientId: Number(payload.patientId || 0),
          channel,
        });
      }
      const target = callUrlFromPayload(payload);
      if (target) window.location.href = target;
      globalCall = null;
    }

    function rejectGlobalCall(payload, reason = 'rejected'){
      if (!payload) return;
      const callId = (payload.callId || '').toString();
      if (socket && callId) {
        socket.emit('call-rejected', { callId, reason });
      }
      globalCall = null;
    }

    function dismissGlobalCall(reason){
      try{
        if (globalAlertOpen && window.Swal) Swal.close();
      }catch(_){}
      stopGlobalTone();
      globalAlertOpen = false;
      if (reason && globalCall) {
        rejectGlobalCall(globalCall, reason);
      }
      if (globalCall && reason) {
        emitCallEvent('snoutiq:call-overlay-dismissed', { reason, payload: globalCall });
      }
      globalCall = null;
    }

    function renderGlobalCallAlert(payload){
      if (window.DOCTOR_PAGE_HANDLE_CALLS) return;
      globalCall = payload;
      emitCallEvent('snoutiq:call-overlay-open', payload);
      startGlobalTone();
      if (globalAlertOpen) return;
      if (window.Swal) {
        globalAlertOpen = true;
        const patient = payload?.patientId ? `Patient #${payload.patientId}` : 'Incoming call';
        const channel = payload?.channel ? `<code>${payload.channel}</code>` : '';
        Swal.fire({
          icon: 'info',
          title: patient,
          html: `
            <div style="text-align:left;font-size:14px">
              <div style="margin-bottom:6px;"><strong>Incoming video consultation</strong></div>
              ${channel ? `<div style="color:#555;margin-bottom:6px;">Channel: ${channel}</div>` : ''}
              <div style="color:#777;">Keep this tab open to receive calls.</div>
            </div>`,
          confirmButtonText: 'Join Call',
          showDenyButton: true,
          denyButtonText: 'Dismiss',
          buttonsStyling: false,
          customClass: {
            popup: 'snoutiq-incoming-call',
            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold',
            denyButton: 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg font-medium'
          },
          allowOutsideClick: false,
          didOpen: () => startGlobalTone(),
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
      localStorage.setItem('clinic_visible', on ? 'on' : 'off');
      if (label) label.textContent = on ? 'Visible' : 'Hidden';
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
        if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
        if (currentDoctorId) {
          socket.emit('join-doctor', Number(currentDoctorId));
        }
        ackTimer = setTimeout(()=>{
          if (!joined) setHeaderStatus('online');
        }, 1500);
      });

      socket.on('doctor-online', (payload)=>{
        if (!payload) return;
        const match = Number(payload.doctorId);
        if (currentDoctorId && match === Number(currentDoctorId)) {
          joined = true;
          if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
          setHeaderStatus('online');
        }
      });

      socket.on('disconnect', ()=>{
        joined = false;
        if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
        dismissGlobalCall();
        setHeaderStatus('offline');
      });

      socket.on('connect_error', (err)=>{
        console.warn('[snoutiq-call] socket connect_error', err?.message || err);
        dismissGlobalCall();
        setHeaderStatus('error');
      });

      socket.on('call-requested', (payload)=>{
        if (payload?.doctorId) updateDoctorId(payload.doctorId);
        emitCallEvent('snoutiq:call-requested', payload);
        if (!window.DOCTOR_PAGE_HANDLE_CALLS) {
          renderGlobalCallAlert(payload);
        }
      });

      const cancelEvents = ['call-cancelled','call-ended','call-timeout','call-failed'];
      cancelEvents.forEach(eventName=>{
        socket.on(eventName, (payload)=>{
          emitCallEvent(`snoutiq:${eventName}`, payload);
          if (!window.DOCTOR_PAGE_HANDLE_CALLS) {
            dismissGlobalCall();
          }
        });
      });

      return socket;
    }

    function goOnline(opts = {}){
      applyVisibility(true);
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
        } else {
          setHeaderStatus('online');
        }
      }catch(err){
        console.warn('[snoutiq-call] failed to connect socket', err);
        setHeaderStatus('error');
      }
    }

    function goOffline(opts = {}){
      applyVisibility(false);
      dismissGlobalCall();
      if (socket) {
        try{
          socket.io.opts.reconnection = false;
          socket.disconnect();
        }catch(_){}
      }
      joined = false;
      if (ackTimer) { clearTimeout(ackTimer); ackTimer = null; }
      setHeaderStatus('offline');
      if (opts.showAlert && window.Swal) {
        Swal.fire({
          icon: 'info',
          title: 'Offline',
          text: 'You will not be receiving calls. Turn on this button to receive video consultation calls.'
        });
      }
    }

    const savedVisible = (localStorage.getItem('clinic_visible') ?? 'on') !== 'off';
    if (toggle) toggle.checked = savedVisible;
    applyVisibility(savedVisible);

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
      goOnline({ showAlert: false });
    } else {
      goOffline({ showAlert: false });
    }

    if (toggle) {
      toggle.addEventListener('change', function(){
        const on = !!this.checked;
        if (on) {
          goOnline({ showAlert: true });
        } else {
          goOffline({ showAlert: true });
        }
      });
    }
  });
</script>
