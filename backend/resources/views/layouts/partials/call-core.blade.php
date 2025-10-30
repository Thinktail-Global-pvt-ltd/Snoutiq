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
    let ringAudio = null;
    let ringResumePending = false;
    let ringResumeListener = null;

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
            openEl.setAttribute('aria-disabled', 'true');
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
          openEl.setAttribute('aria-disabled', 'false');
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

    let summaryFetchToken = 0;

    function injectCallStyles(){
      if (document.getElementById('snoutiq-call-styles')) return;
      try {
        const style = document.createElement('style');
        style.id = 'snoutiq-call-styles';
        style.textContent = `
          .snoutiq-incoming-call{border-radius:28px!important;padding:0!important;background:#fff!important;box-shadow:0 28px 90px -45px rgba(15,23,42,.65)!important;font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif!important;color:#111827!important;overflow:hidden;}
          .snoutiq-incoming-call .swal2-title{display:none!important;}
          .snoutiq-incoming-call .swal2-html-container{margin:0!important;padding:32px 34px 10px!important;}
          .snoutiq-incoming-call .swal2-actions{margin:0!important;padding:0 34px 30px!important;display:flex!important;gap:16px!important;}
          .snoutiq-incoming-call .snoutiq-btn{flex:1 1 0%;border-radius:14px;padding:15px 0;font-weight:600;font-size:15px;letter-spacing:.01em;transition:transform .2s ease, box-shadow .2s ease, background .2s ease;}
          .snoutiq-incoming-call .snoutiq-btn:focus{outline:none!important;box-shadow:0 0 0 3px rgba(59,130,246,.35)!important;}
          .snoutiq-incoming-call .snoutiq-btn-accept{background:#22c55e;color:#fff;border:none;}
          .snoutiq-incoming-call .snoutiq-btn-accept:hover{transform:translateY(-1px);box-shadow:0 20px 32px -20px rgba(34,197,94,.9);}
          .snoutiq-incoming-call .snoutiq-btn-reject{background:#ef4444;color:#fff;border:none;}
          .snoutiq-incoming-call .snoutiq-btn-reject:hover{transform:translateY(-1px);box-shadow:0 20px 32px -20px rgba(239,68,68,.92);}
          .snoutiq-call-card{display:flex;flex-direction:column;gap:24px;}
          .snoutiq-call-header{display:flex;align-items:flex-start;gap:18px;}
          .snoutiq-call-header-icon{flex-shrink:0;width:60px;height:60px;border-radius:22px;background:linear-gradient(135deg,#fee2e2 0%,#fecaca 100%);display:flex;align-items:center;justify-content:center;color:#dc2626;box-shadow:inset 0 0 0 1px rgba(254,205,211,.9);}
          .snoutiq-call-header-icon svg{width:30px;height:30px;}
          .snoutiq-call-header-main{flex:1 1 auto;display:flex;flex-direction:column;gap:6px;min-width:0;}
          .snoutiq-call-title{font-size:22px;font-weight:800;color:#111827;letter-spacing:-.015em;white-space:nowrap;}
          .snoutiq-call-patient{font-size:15px;font-weight:600;color:#1f2937;}
          .snoutiq-call-patient-sub{font-size:13px;color:#6b7280;}
          .snoutiq-call-patient-sub.is-hidden{display:none;}
          .snoutiq-call-meta{margin-left:auto;text-align:right;display:flex;flex-direction:column;gap:6px;font-size:12px;color:#6b7280;align-items:flex-end;min-width:130px;}
          .snoutiq-call-meta-channel{display:flex;align-items:center;gap:8px;}
          .snoutiq-call-meta-label{font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#9ca3af;}
          .snoutiq-call-meta .snoutiq-call-channel{font-family:'JetBrains Mono','Fira Mono',ui-monospace,monospace;font-size:12px;color:#111827;background:#f3f4f6;padding:5px 12px;border-radius:10px;display:inline-flex;align-items:center;justify-content:flex-end;box-shadow:inset 0 -1px 0 rgba(148,163,184,.35);}
          .snoutiq-call-meta-time{font-size:12px;color:#6b7280;white-space:nowrap;}
          .snoutiq-call-section{position:relative;border:1px solid #e5e7eb;border-radius:20px;padding:20px;background:linear-gradient(180deg,#f9fafb 0%,#f3f4f6 100%);display:flex;flex-direction:column;gap:10px;box-shadow:inset 0 1px 0 rgba(255,255,255,.8);}
          .snoutiq-call-section-title{font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#6b7280;}
          .snoutiq-call-lines{display:flex;flex-direction:column;gap:8px;font-size:13px;line-height:1.65;color:#374151;}
          .snoutiq-call-line{position:relative;padding-left:18px;}
          .snoutiq-call-line::before{content:'';position:absolute;top:8.5px;left:6px;width:5px;height:5px;border-radius:9999px;background:#9ca3af;}
          .snoutiq-call-line-strong{font-weight:600;color:#111827;}
          .snoutiq-call-summary{border:1px solid rgba(248,113,113,.55);background:linear-gradient(180deg,#fff5f5 0%,#fee2e2 100%);box-shadow:0 18px 38px -26px rgba(248,113,113,.8);}
          .snoutiq-call-summary-head{display:flex;align-items:center;justify-content:space-between;gap:12px;}
          .snoutiq-call-summary-label{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.18em;color:#b91c1c;}
          .snoutiq-call-summary-tag{font-size:10px;text-transform:uppercase;letter-spacing:.2em;color:#f43f5e;}
          .snoutiq-call-summary-status{font-size:13px;color:#b91c1c;}
          .snoutiq-call-summary-body{font-size:13px;line-height:1.65;color:#7f1d1d;}
          .snoutiq-call-links{display:flex;flex-direction:column;gap:10px;margin-top:4px;}
          .snoutiq-call-link-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 14px;border-radius:14px;background:#f8fafc;border:1px solid #e5e7eb;transition:box-shadow .2s ease;}
          .snoutiq-call-link-row:not(.is-disabled):hover{box-shadow:0 12px 24px -18px rgba(30,64,175,.45);}
          .snoutiq-call-link-row.is-disabled{opacity:.55;}
          .snoutiq-call-link-row.is-disabled .snoutiq-call-link-open,
          .snoutiq-call-link-row.is-disabled .snoutiq-call-link-copy{pointer-events:none;cursor:not-allowed;}
          .snoutiq-call-link-main{display:flex;flex-direction:column;gap:4px;}
          .snoutiq-call-link-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#1f2937;}
          .snoutiq-call-link-value{font-size:12px;color:#4b5563;font-family:'JetBrains Mono','Fira Mono',ui-monospace,monospace;word-break:break-all;}
          .snoutiq-call-link-actions{display:flex;gap:8px;align-items:center;}
          .snoutiq-call-link-open{display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;color:#fff;background:#2563eb;text-decoration:none;transition:background .2s ease,color .2s ease;}
          .snoutiq-call-link-open:focus{outline:none;box-shadow:0 0 0 3px rgba(37,99,235,.28);}
          .snoutiq-call-link-copy{padding:6px 12px;border-radius:10px;border:none;background:#e0e7ff;color:#312e81;font-size:12px;font-weight:600;cursor:pointer;transition:background .2s ease,color .2s ease;}
          .snoutiq-call-link-copy:focus{outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.28);}
          .snoutiq-call-link-copy.is-copied{background:#d1fae5;color:#065f46;}
          .snoutiq-call-footer{display:flex;flex-direction:column;gap:6px;font-size:12px;color:#6b7280;}
          .snoutiq-call-footer-note{font-size:11px;color:#9ca3af;}
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
          } catch (_) {
            /* ignore */
          }
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

    function renderLines(container, lines, opts = {}){
      if (!container) return;
      const {
        emptyMessage = 'No information available.',
        emptyColor = '#9ca3af',
        fontSize = '13px',
        lineHeight = '1.6',
        textColor = '#374151',
        labelColor = '#111827',
        highlightLabels = true,
      } = opts;

      container.innerHTML = '';
      try { container.classList.add('snoutiq-call-lines'); } catch (_) {}
      let hasContent = false;
      const safeLines = Array.isArray(lines) ? lines : (typeof lines === 'string' ? lines.split(/\n+/) : []);
      safeLines.forEach(raw => {
        const trimmed = (raw || '').toString().trim();
        if (!trimmed) return;
        const line = document.createElement('div');
        line.className = 'snoutiq-call-line';
        line.style.fontSize = fontSize;
        line.style.lineHeight = lineHeight;
        line.style.color = textColor;
        if (highlightLabels) {
          const idx = trimmed.indexOf(':');
          if (idx > 0 && idx < 40) {
            const label = trimmed.slice(0, idx).trim();
            const value = trimmed.slice(idx + 1).trim();
            if (label && value) {
              const strong = document.createElement('span');
              strong.className = 'snoutiq-call-line-strong';
              if (labelColor) {
                strong.style.color = labelColor;
              }
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
        span.style.display = 'block';
        span.style.fontSize = '12px';
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
        statusEl.style.color = '#9f1239';
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
            emptyColor: '#9f1239',
            fontSize: '13px',
            lineHeight: '1.6',
            textColor: '#7f1d1d',
            labelColor: '#be123c',
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

      if (channelEl) {
        const channel = (payload?.channel || '').toString().trim();
        channelEl.textContent = channel || '—';
      }

      if (templateEl) {
        const templateText = extractCallTemplate(payload);
        renderLines(templateEl, templateText, {
          emptyMessage: 'No consultation template shared yet.',
          emptyColor: '#9ca3af',
          textColor: '#374151',
          labelColor: '#1f2937',
        });
      }

      const doctorLinkRow = container.querySelector('[data-role="doctor-link"]');
      const paymentLinkRow = container.querySelector('[data-role="payment-link"]');
      populateLinkRow(doctorLinkRow, resolveDoctorJoinUrl(payload), 'Doctor Join Link');
      populateLinkRow(paymentLinkRow, resolvePaymentUrl(payload), 'Payment Page');

      return patientId;
    }

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
            <div class="snoutiq-call-header">
              <div class="snoutiq-call-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="snoutiq-call-header-main">
                <div class="snoutiq-call-title">Incoming Call</div>
                <div class="snoutiq-call-patient" data-role="call-patient"></div>
                <div class="snoutiq-call-patient-sub is-hidden" data-role="call-patient-sub"></div>
              </div>
              <div class="snoutiq-call-meta">
                <div class="snoutiq-call-meta-channel">
                  <span class="snoutiq-call-meta-label">Channel</span>
                  <span class="snoutiq-call-channel" data-role="call-channel">—</span>
                </div>
                <span class="snoutiq-call-meta-time" data-role="call-time"></span>
              </div>
            </div>
            <div class="snoutiq-call-section">
              <div class="snoutiq-call-section-title">Consultation Template</div>
              <div data-role="template"></div>
            </div>
            <div class="snoutiq-call-section snoutiq-call-summary">
              <div class="snoutiq-call-summary-head">
                <span class="snoutiq-call-summary-label">AI Chat Summary</span>
                <span class="snoutiq-call-summary-tag">Latest Chats</span>
              </div>
              <div class="snoutiq-call-summary-status" data-role="summary-status">Fetching AI summary…</div>
              <div class="snoutiq-call-summary-body" data-role="summary" style="display:none;"></div>
            </div>
            <div class="snoutiq-call-section snoutiq-call-links">
              <div class="snoutiq-call-link-row is-disabled" data-role="doctor-link">
                <div class="snoutiq-call-link-main">
                  <span class="snoutiq-call-link-label" data-role="link-label">Doctor Join Link</span>
                  <span class="snoutiq-call-link-value" data-role="link-value">Not available yet</span>
                </div>
                <div class="snoutiq-call-link-actions">
                  <a class="snoutiq-call-link-open" data-role="link-open" href="#" target="_blank" rel="noopener noreferrer">Open</a>
                  <button type="button" class="snoutiq-call-link-copy" data-role="link-copy" disabled>Copy</button>
                </div>
              </div>
              <div class="snoutiq-call-link-row is-disabled" data-role="payment-link">
                <div class="snoutiq-call-link-main">
                  <span class="snoutiq-call-link-label" data-role="link-label">Payment Page</span>
                  <span class="snoutiq-call-link-value" data-role="link-value">Not available yet</span>
                </div>
                <div class="snoutiq-call-link-actions">
                  <a class="snoutiq-call-link-open" data-role="link-open" href="#" target="_blank" rel="noopener noreferrer">Open</a>
                  <button type="button" class="snoutiq-call-link-copy" data-role="link-copy" disabled>Copy</button>
                </div>
              </div>
            </div>
            <div class="snoutiq-call-footer">
              <div class="snoutiq-call-footer-note">Keep this tab open to stay available for video consultations.</div>
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
