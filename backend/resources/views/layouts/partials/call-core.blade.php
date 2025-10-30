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

    let summaryFetchToken = 0;

    function injectCallStyles(){
      if (document.getElementById('snoutiq-call-styles')) return;
      try {
        const style = document.createElement('style');
        style.id = 'snoutiq-call-styles';
        style.textContent = `
          .snoutiq-incoming-call{border-radius:28px!important;padding:0!important;background:#fff!important;box-shadow:0 30px 70px -40px rgba(15,23,42,.55)!important;}
          .snoutiq-incoming-call .swal2-title{display:none!important;}
          .snoutiq-incoming-call .swal2-html-container{margin:0!important;padding:28px 28px 4px!important;}
          .snoutiq-incoming-call .swal2-actions{margin:0!important;padding:0 28px 24px!important;display:flex!important;gap:14px!important;}
          .snoutiq-incoming-call .snoutiq-btn{flex:1 1 0%;border-radius:14px;padding:14px 0;font-weight:600;font-size:15px;letter-spacing:.01em;transition:transform .2s ease, box-shadow .2s ease;}
          .snoutiq-incoming-call .snoutiq-btn:focus{outline:none!important;box-shadow:0 0 0 3px rgba(59,130,246,.35)!important;}
          .snoutiq-incoming-call .snoutiq-btn-accept{background:#16a34a;color:#fff;border:none;}
          .snoutiq-incoming-call .snoutiq-btn-accept:hover{transform:translateY(-1px);box-shadow:0 15px 25px -15px rgba(22,163,74,.9);}
          .snoutiq-incoming-call .snoutiq-btn-reject{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
          .snoutiq-incoming-call .snoutiq-btn-reject:hover{transform:translateY(-1px);box-shadow:0 12px 20px -18px rgba(248,113,113,.9);}
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
      let hasContent = false;
      const safeLines = Array.isArray(lines) ? lines : (typeof lines === 'string' ? lines.split(/\n+/) : []);
      safeLines.forEach(raw => {
        const trimmed = (raw || '').toString().trim();
        if (!trimmed) return;
        const p = document.createElement('p');
        p.style.margin = '0 0 6px';
        p.style.fontSize = fontSize;
        p.style.lineHeight = lineHeight;
        p.style.color = textColor;
        if (highlightLabels) {
          const idx = trimmed.indexOf(':');
          if (idx > 0 && idx < 40) {
            const label = trimmed.slice(0, idx).trim();
            const rest = trimmed.slice(idx + 1).trim();
            if (label && rest) {
              const strong = document.createElement('span');
              strong.style.fontWeight = '600';
              strong.style.color = labelColor;
              strong.textContent = `${label}: `;
              p.appendChild(strong);
              p.appendChild(document.createTextNode(rest));
              container.appendChild(p);
              hasContent = true;
              return;
            }
          }
        }
        p.textContent = trimmed;
        container.appendChild(p);
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
      const entries = [];
      let current = null;
      text.split(/\n+/).forEach(line => {
        const trimmed = line.trim();
        if (!trimmed) return;
        if (/^Q:/i.test(trimmed)) {
          current = { question: trimmed.replace(/^Q:\s*/i, '').trim(), diagnosis: [], inDiagnosis: false };
          entries.push(current);
          return;
        }
        if (/^===\s*DIAGNOSIS\s*===/i.test(trimmed)) {
          if (!current) {
            current = { question: '', diagnosis: [], inDiagnosis: true };
            entries.push(current);
          } else {
            current.inDiagnosis = true;
          }
          return;
        }
        if (/^===\s*END\s*===/i.test(trimmed)) {
          if (current) current.inDiagnosis = false;
          return;
        }
        if (current?.inDiagnosis) {
          current.diagnosis.push(trimmed.replace(/^A:\s*/i, '').trim());
          return;
        }
        if (!current) {
          current = { question: '', diagnosis: [], inDiagnosis: false };
          entries.push(current);
        }
        current.diagnosis.push(trimmed);
      });

      const primary = entries.find(entry => entry.question || entry.diagnosis.length);
      if (primary) {
        const lines = [];
        if (primary.question) lines.push(`Q: ${primary.question}`);
        if (primary.diagnosis.length) lines.push(`Diagnosis: ${primary.diagnosis[0]}`);
        if (lines.length) return lines;
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
        return date.toLocaleString(undefined, { hour: '2-digit', minute: '2-digit' });
      } catch (_) {
        return '';
      }
    }

    function populateSwalContent(container, payload){
      if (!container) return;
      const patientEl = container.querySelector('[data-role="call-patient"]');
      const timeEl = container.querySelector('[data-role="call-time"]');
      const channelEl = container.querySelector('[data-role="call-channel"]');
      const templateEl = container.querySelector('[data-role="template"]');

      const patientId = extractPatientId(payload);
      if (patientEl) {
        if (patientId) {
          patientEl.textContent = `Patient #${patientId}`;
          patientEl.style.fontSize = '14px';
          patientEl.style.fontWeight = '600';
          patientEl.style.color = '#1f2937';
        } else {
          patientEl.textContent = 'Incoming video consultation';
          patientEl.style.fontSize = '14px';
          patientEl.style.fontWeight = '600';
          patientEl.style.color = '#1f2937';
        }
      }

      if (timeEl) {
        const ts = payload?.timestamp || payload?.createdAt || payload?.created_at || Date.now();
        const formatted = formatCallTime(ts);
        timeEl.textContent = formatted ? `Requested at ${formatted}` : '';
        timeEl.style.color = '#6b7280';
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
          <div class="snoutiq-call-card" style="font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#111827;">
            <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:18px;">
              <div style="flex-shrink:0;width:52px;height:52px;border-radius:18px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#dc2626;box-shadow:inset 0 0 0 1px rgba(248,113,113,.35);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" style="width:28px;height:28px;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a 1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </div>
              <div style="flex:1;">
                <div style="font-size:18px;font-weight:700;color:#111827;">Incoming Call</div>
                <div data-role="call-patient" style="margin-top:4px;"></div>
                <div data-role="call-time" style="margin-top:4px;font-size:12px;color:#6b7280;"></div>
              </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;">
              <div style="border:1px solid #e5e7eb;border-radius:16px;padding:14px;background:#f9fafb;">
                <div style="font-size:12px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#6b7280;margin-bottom:6px;">Consultation Template</div>
                <div data-role="template" style="font-size:13px;line-height:1.6;color:#374151;"></div>
              </div>
              <div style="border:1px solid rgba(254,205,211,.8);border-radius:16px;padding:14px;background:#fff1f2;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                  <span style="font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#be123c;">AI Chat Summary</span>
                  <span style="font-size:10px;text-transform:uppercase;letter-spacing:0.18em;color:#f43f5e;">Latest chats</span>
                </div>
                <div data-role="summary-status" style="font-size:12px;color:#9f1239;">Fetching AI summary…</div>
                <div data-role="summary" style="margin-top:6px;font-size:13px;line-height:1.6;color:#7f1d1d;display:none;"></div>
              </div>
              <div style="border:1px dashed #e5e7eb;border-radius:14px;padding:10px 12px;background:#fff;display:flex;flex-direction:column;gap:4px;font-size:12px;color:#6b7280;">
                <div>Channel: <code data-role="call-channel" style="font-size:12px;color:#111827;background:#f3f4f6;padding:2px 6px;border-radius:6px;">—</code></div>
                <div style="color:#9ca3af;font-size:11px;">Keep this tab open to stay available.</div>
              </div>
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
