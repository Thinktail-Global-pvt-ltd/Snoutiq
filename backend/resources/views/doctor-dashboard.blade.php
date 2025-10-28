{{-- resources/views/doctor-dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Doctor Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    @keyframes ring{0%{transform:rotate(0)}10%{transform:rotate(15deg)}20%{transform:rotate(-15deg)}30%{transform:rotate(10deg)}40%{transform:rotate(-10deg)}50%{transform:rotate(5deg)}60%{transform:rotate(-5deg)}100%{transform:rotate(0)}}
    .ringing{animation:ring 1s infinite}
  </style>
</head>
<body class="h-screen bg-gray-50">

@php
  // ===== Env / paths =====
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/'); // e.g. "backend"
  $socketUrl  = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));

  // ===== Doctor identity (server-side first) =====
  $serverCandidate = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? optional(auth()->user())->id
        ?? request('doctorId');
  // Prefer explicit route-provided $doctorId when present
  $serverDoctorId = $serverCandidate ? (int)$serverCandidate : (isset($doctorId) ? (int)$doctorId : null);

  // For JS (pure session value; helpful but not required)
  $sessionUserId = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? optional(auth()->user())->id
        ?? null;

  // ===== Sidebar links =====
  $aiChatUrl   = ($pathPrefix ? "/$pathPrefix" : '') . '/backend/pet-dashboard';
  $thisPageUrl = ($pathPrefix ? "/$pathPrefix" : '') . '/backend/doctor' . ($serverDoctorId ? ('?doctorId=' . urlencode($serverDoctorId)) : '');
@endphp

<script>
  // ========= runtime env from server =========
  const PATH_PREFIX = @json($pathPrefix ? "/$pathPrefix" : ""); // "" locally, "/backend" in prod
  const RAW_SOCKET_URL = @json($socketUrl);
  const API_BASE    = (PATH_PREFIX || '') + '/api';
  const DEFAULT_DOCTOR_ID = Number(@json($serverDoctorId ?? ($doctorId ?? null))) || null;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const SOCKET_URL = (!IS_LOCAL && /localhost|127\.0\.0\.1/i.test(RAW_SOCKET_URL))
    ? window.location.origin
    : RAW_SOCKET_URL;

  // Sources
  const SESSION_USER_ID = Number(@json($sessionUserId ?? null)) || null; // server truth (optional)
  const fromServer      = Number(@json($serverDoctorId ?? null)) || null;
  const fromQuery       = (()=>{ const u=new URL(location.href); const v=u.searchParams.get('doctorId'); return v?Number(v):null; })();
  function readAuthFull(){ try{ const raw=sessionStorage.getItem('auth_full')||localStorage.getItem('auth_full'); return raw?JSON.parse(raw):null; }catch(_){ return null; } }
  const af              = readAuthFull();
  const fromStorage     = (()=>{ if(!af) return null; const id1=af?.user_id; const id2=af?.user?.id; return Number(id1||id2)||null; })();

  // â­ FINAL id the frontend will use everywhere:
  let CURRENT_USER_ID = SESSION_USER_ID || fromServer || fromQuery || fromStorage || DEFAULT_DOCTOR_ID || null;

  console.log('[doctor-dashboard] RESOLVED user_id â†’', {
    SESSION_USER_ID, fromServer, fromQuery, fromStorage, CURRENT_USER_ID, PATH_PREFIX, API_BASE
  });
</script>

<div class="flex h-full">
  {{-- Sidebar --}}
  {{-- Shared sidebar --}}
  @include('layouts.partials.sidebar')
  {{-- Main --}}
  <main class="flex-1 flex flex-col">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Doctor Dashboard</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connectingâ€¦"></span>
        <span id="status-pill" class="hidden px-3 py-1 rounded-full text-xs font-bold">â€¦</span>
      </div>

      <div class="flex items-center gap-3">
        <button id="btn-add-service" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">+ Add Service</button>
        <div class="text-right">
          <div class="text-sm font-medium text-gray-900">{{ auth()->user()->name ?? 'Doctor' }}</div>
          <div class="text-xs text-gray-500">{{ auth()->user()->role ?? 'doctor' }}</div>
        </div>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
          {{ strtoupper(substr(auth()->user()->name ?? 'D',0,1)) }}
        </div>
      </div>
    </header>

    <section class="flex-1 p-6">
      <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold text-gray-800">Incoming Calls (<span id="calls-count">0</span>)</h2>
            <div class="text-xs text-gray-500">Keep this page open to receive calls</div>
          </div>
          <div id="calls" class="space-y-3">
            <div id="no-calls" class="bg-gray-50 text-gray-600 rounded-lg p-6 text-center border border-dashed border-gray-200">
              <p>No incoming calls at the moment</p>
              <p class="text-xs mt-1" id="no-calls-sub">Connect to receive calls</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

{{-- Incoming Call Modal --}}
<div id="incoming-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[92%] max-w-md p-6">
    <div class="flex items-center gap-3 mb-4">
      <svg class="w-9 h-9 text-rose-600 ringing" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
      <h3 class="text-xl font-semibold text-gray-800">Incoming Call</h3>
    </div>
    <div class="space-y-2 text-gray-700">
      <div><span class="font-semibold">Patient ID:</span> <span id="m-patient"></span></div>
      <div><span class="font-semibold">Channel:</span> <span id="m-channel" class="break-all"></span></div>
      <div class="text-xs text-gray-500"><span class="font-semibold">Time:</span> <span id="m-time"></span></div>
    </div>
    <div class="mt-4 border-t border-gray-200 pt-4">
      <div class="flex items-center justify-between">
        <span class="text-sm font-semibold text-gray-800">AI Chat Summary</span>
        <span class="text-[11px] uppercase tracking-wide text-gray-400">Last 5 chats</span>
      </div>
      <div class="mt-2 rounded-xl bg-gray-50 border border-gray-200 p-3 max-h-48 overflow-y-auto">
        <p id="m-summary-status" class="text-xs text-gray-500">AI summary will appear here when available.</p>
        <div id="m-summary" class="mt-2 space-y-2 text-sm text-gray-700 leading-relaxed"></div>
      </div>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-3">
      <button id="m-accept" class="py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow">âœ… Accept</button>
      <button id="m-reject" class="py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold shadow">âŒ Reject</button>
    </div>
  </div>
</div>

<!-- =============================
     Add Service Modal (hidden by default)
============================= -->
<div id="add-service-modal" class="hidden fixed inset-0 z-[70] bg-slate-900/70 backdrop-blur flex items-center justify-center px-4 py-6">
  <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
    <button type="button" id="svc-close" class="absolute top-4 right-4 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:scale-105 hover:text-slate-900">
      <span class="sr-only">Close</span>
      ✕
    </button>

    <div class="grid gap-0 md:grid-cols-[260px_1fr]">
      <aside class="hidden md:flex flex-col justify-between border-r border-slate-100 bg-gradient-to-br from-indigo-600 via-indigo-500 to-indigo-400 p-8 text-white">
        <div class="space-y-6">
          <div>
            <p class="text-xs uppercase tracking-[0.35em] text-indigo-100/80">SnoutIQ Clinic</p>
            <h3 class="mt-3 text-2xl font-semibold leading-tight">Launch a new in-clinic experience</h3>
          </div>
          <ul class="space-y-4 text-sm text-indigo-50/90">
            <li class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">1</span>
              Define a crisp name so pet parents instantly recognise the service.
            </li>
            <li class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">2</span>
              Share the duration and pricing to auto-calculate slot availability.
            </li>
            <li class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">3</span>
              Add optional notes to highlight inclusions or pre-visit requirements.
            </li>
          </ul>
        </div>
        <div class="rounded-2xl border border-white/20 bg-white/10 p-4 text-xs text-indigo-100/90">
          Pro-tip: keep the description action-oriented (e.g. “Full body grooming with nail trim”) so your reception team can pitch it in seconds.
        </div>
      </aside>

      <div class="p-6 md:p-10">
        <header class="space-y-3">
          <p class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">In-clinic · Service catalogue</p>
          <div>
            <h3 class="text-2xl font-semibold text-slate-900">Add New Service</h3>
            <p class="text-sm text-slate-500">Craft a polished card that shows up instantly on your clinic listings.</p>
          </div>
        </header>

        <form id="svc-form" class="mt-8 space-y-8">
          <section class="space-y-6">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Core details</h4>
            <div class="grid gap-5 lg:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700" for="svc-name">Service Name</label>
                <input id="svc-name" type="text" placeholder="Eg. Premium Grooming Session" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                <p class="text-xs text-slate-400">Displayed on the service catalogue and booking flow.</p>
              </div>

              <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-slate-700" for="svc-duration">Duration (mins)</label>
                  <input id="svc-duration" type="number" min="1" step="1" placeholder="45" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-slate-700" for="svc-price">Price (₹)</label>
                  <input id="svc-price" type="number" min="0" step="0.01" placeholder="1299" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                </div>
              </div>
            </div>
          </section>

          <section class="space-y-6">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Categorise the visit</h4>
            <div class="grid gap-5 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700" for="svc-pet-type">Pet Type</label>
                <select id="svc-pet-type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                  <option value="" selected disabled>Select pet category</option>
                  <option value="dog">Dog</option>
                  <option value="cat">Cat</option>
                  <option value="bird">Bird</option>
                  <option value="rabbit">Rabbit</option>
                  <option value="hamster">Hamster</option>
                  <option value="all">All Pets</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700" for="svc-main">Service Category</label>
                <select id="svc-main" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                  <option value="" selected disabled>Select category</option>
                  <option value="grooming">Grooming</option>
                  <option value="video_call">Video Call</option>
                  <option value="vet">Vet Service</option>
                  <option value="pet_walking">Pet Walking</option>
                  <option value="sitter">Sitter</option>
                </select>
              </div>
            </div>
          </section>

          <section class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Optional notes</h4>
              <span class="text-xs font-medium text-indigo-500">Use for add-ons or prep instructions</span>
            </div>
            <textarea id="svc-notes" rows="5" placeholder="Mention what's included, preparation guidelines or recovery notes." class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"></textarea>
          </section>

          <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-slate-500">
              New services go live instantly for your clinic once saved.
            </div>
            <div class="flex items-center gap-3">
              <button type="button" id="svc-cancel" class="inline-flex items-center justify-center rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
              <button type="submit" id="svc-submit" class="inline-flex items-center justify-center rounded-full bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700">Save Service</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
/* =========================
   Add Service â€” sends user_id = CURRENT_USER_ID
========================= */
(function(){
  const $ = s => document.querySelector(s);

  // Endpoint (prod)
  const API_POST_SVC = 'https://snoutiq.com/backend/api/groomer/service';

  const els = {
    openBtn: $('#btn-add-service'),
    modal:   document.getElementById('add-service-modal'),
    close:   document.getElementById('svc-close'),
    cancel:  document.getElementById('svc-cancel'),
    form:    document.getElementById('svc-form'),
    submit:  document.getElementById('svc-submit'),
    name:    document.getElementById('svc-name'),
    duration:document.getElementById('svc-duration'),
    price:   document.getElementById('svc-price'),
    petType: document.getElementById('svc-pet-type'),
    main:    document.getElementById('svc-main'),
    notes:   document.getElementById('svc-notes'),
  };

  function show(el){ el && el.classList.remove('hidden'); }
  function hide(el){ el && el.classList.add('hidden'); }
  function loading(btn,on){ if(!btn) return; btn.disabled=!!on; btn.classList.toggle('opacity-60',!!on); if(on){btn.dataset.oldText=btn.textContent; btn.textContent='Saving...';} else if(btn.dataset.oldText){ btn.textContent=btn.dataset.oldText; delete btn.dataset.oldText; } }

  // Sanctum helpers
  const CSRF_URL = (PATH_PREFIX || '') + '/sanctum/csrf-cookie';
  function getCookie(n){ return document.cookie.split('; ').find(r=>r.startsWith(n+'='))?.split('=')[1] || ''; }
  function xsrfHeader(){ const raw=getCookie('XSRF-TOKEN'); return raw ? decodeURIComponent(raw) : ''; }

  async function bootstrapAuth(){
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token) { window.__authMode='bearer'; return { mode:'bearer' }; }
    try{
      await fetch(CSRF_URL, { credentials:'include' });
      const xsrf = xsrfHeader();
      if (xsrf) { window.__authMode='cookie'; return { mode:'cookie', xsrf }; }
      return { mode:'none' };
    }catch{ return { mode:'none' }; }
  }

  function buildHeaders(auth){
    const h = { 'Accept':'application/json',
                'X-Acting-User':  String(CURRENT_USER_ID ?? ''),
                'X-Session-User': String(SESSION_USER_ID ?? '') };
    if (auth.mode === 'bearer') {
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      h['Authorization'] = 'Bearer ' + token;
    } else if (auth.mode === 'cookie') {
      h['X-Requested-With'] = 'XMLHttpRequest';
      const xsrf = xsrfHeader();
      if (xsrf) h['X-XSRF-TOKEN'] = xsrf;
    }
    return h;
  }

  async function fetchJSON(url, opts={}){
    const res = await fetch(url, { credentials:'include', ...opts });
    const ct  = res.headers.get('content-type') || '';
    const isJSON = ct.includes('application/json');
    const body = isJSON ? await res.json() : await res.text();
    if (!isJSON) throw { status: res.status, body, hint: 'Non-JSON response' };
    if (!res.ok) throw { status: res.status, body };
    return body;
  }

  function resetForm(){ els.form?.reset(); if(els.petType) els.petType.value=''; if(els.main) els.main.value=''; }

  async function createService(e){
    e.preventDefault();

    // pull latest possible id from storage (optional)
    try{
      const raw=sessionStorage.getItem('auth_full')||localStorage.getItem('auth_full');
      if(raw){ const obj=JSON.parse(raw); const id = Number(obj?.user?.id ?? obj?.user_id ?? NaN); if(!Number.isNaN(id)&&id) CURRENT_USER_ID=id; }
    }catch{ /* ignore */ }

    const name     = (els.name?.value || '').trim();
    const duration = Number(els.duration?.value || 0);
    const price    = Number(els.price?.value || 0);
    const petType  = els.petType?.value || '';
    const main     = els.main?.value || '';
    const notes    = (els.notes?.value || '').trim();

    if(!name || !duration || !price || !petType || !main){
      Swal.fire({icon:'warning', title:'Missing fields', text:'Please fill all required fields.'});
      return;
    }

    loading(els.submit,true);
    try{
      const auth = await bootstrapAuth();
      if (auth.mode === 'none') {
        Swal.fire({icon:'warning', title:'Not authenticated', text:'Paste a Bearer token or enable Sanctum cookies.'});
        loading(els.submit,false);
        return;
      }

      // Build payload
      const fd = new FormData();
      fd.append('serviceName',  name);
      fd.append('description',  notes);
      fd.append('petType',      petType);
      fd.append('price',        price);
      fd.append('duration',     duration);
      fd.append('main_service', main);
      fd.append('status',       'Active');

      // â­ IMPORTANT: send CURRENT_USER_ID
      fd.append('user_id', String(CURRENT_USER_ID ?? ''));

      const headers = buildHeaders(auth);

      // Debug: see exactly what is going
      console.log('[createService] POST â†’', {
        endpoint: API_POST_SVC,
        CURRENT_USER_ID,
        SESSION_USER_ID,
        headers,
        body: Object.fromEntries([...fd.entries()])
      });

      const data = await fetchJSON(API_POST_SVC, { method:'POST', headers, body: fd });

      Swal.fire({icon:'success', title:'Service Created', text:'Your service has been created successfully.'});
      resetForm();
      console.log('[service.create] success', data);

    }catch(err){
      const msg = err?.body?.message
        || (typeof err?.body==='string' ? err.body : JSON.stringify(err?.body||err))
        || err?.hint
        || 'Error creating service';
      Swal.fire({icon:'error', title:'Create failed', text: msg});
      console.error('[service.create] failed', { err, CURRENT_USER_ID, SESSION_USER_ID });
    }finally{
      loading(els.submit,false);
    }
  }

  // init
  document.addEventListener('DOMContentLoaded', ()=>{
    // open modal via button
    document.getElementById('btn-add-service')?.addEventListener('click', ()=>show(els.modal));
  });

  els.form?.addEventListener('submit', createService);
  els.close?.addEventListener('click', ()=>hide(els.modal));
  els.cancel?.addEventListener('click', ()=>hide(els.modal));
})();
</script>

<!-- Inject Logout link next to "+ Add Service" on header -->
<script>
  document.addEventListener('DOMContentLoaded', function(){
    try{
      var rightGroup = document.querySelector('header .flex.items-center.gap-3:last-child');
      if (rightGroup) {
        if (!rightGroup.querySelector('a[data-role="logout-link"]')) {
          var a = document.createElement('a');
          a.href = '{{ route('logout') }}';
          a.setAttribute('data-role','logout-link');
          a.className = 'px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50 text-gray-700';
          a.textContent = 'Logout';
          rightGroup.appendChild(a);
        }
      }
    }catch(_){ /* noop */ }
  });
</script>

<script>
// =========================
// Socket.IO: come online and receive calls
// =========================
(function(){
  if (typeof io === 'undefined') { console.warn('[doctor] Socket.IO not loaded'); return; }
  const $ = s => document.querySelector(s);
  const elSocketId = $('#socket-id');
  const elConn     = $('#conn-status');
  const elConnYes  = $('#socket-connected');
  const elIsOnline = $('#is-online');
  const elHeaderDot= document.getElementById('status-dot');
  const elNoCallsSub = document.getElementById('no-calls-sub');
  const modal      = document.getElementById('incoming-modal');
  const elMPatient = document.getElementById('m-patient');
  const elMChannel = document.getElementById('m-channel');
  const elMTime    = document.getElementById('m-time');
  const elMSummary = document.getElementById('m-summary');
  const elMSummaryStatus = document.getElementById('m-summary-status');

  let audioCtx = null;
  let ringtoneOsc = null;
  let ringtoneGain = null;

  function ensureAudioContext(){
    if (audioCtx) return audioCtx;
    const Ctor = window.AudioContext || window.webkitAudioContext;
    if (!Ctor) return null;
    audioCtx = new Ctor();
    return audioCtx;
  }

  function startIncomingTone(){
    const ctx = ensureAudioContext();
    if (!ctx) return;
    if (ctx.state === 'suspended') {
      ctx.resume().catch(()=>{});
    }
    stopIncomingTone();
    ringtoneOsc = ctx.createOscillator();
    ringtoneGain = ctx.createGain();
    ringtoneOsc.type = 'sine';
    ringtoneOsc.frequency.setValueAtTime(660, ctx.currentTime);
    ringtoneGain.gain.setValueAtTime(0, ctx.currentTime);
    ringtoneOsc.connect(ringtoneGain).connect(ctx.destination);
    ringtoneOsc.start();
    ringtoneGain.gain.linearRampToValueAtTime(0.18, ctx.currentTime + 0.05);
  }

  function stopIncomingTone(){
    try {
      if (ringtoneGain) {
        const ctx = ringtoneGain.context;
        const stopTime = ctx?.currentTime ?? 0;
        try {
          ringtoneGain.gain.cancelScheduledValues(0);
          ringtoneGain.gain.setValueAtTime(0, stopTime);
        } catch (_) {}
        ringtoneGain.disconnect();
      }
      if (ringtoneOsc) {
        ringtoneOsc.stop();
        ringtoneOsc.disconnect();
      }
    } catch (_) { /* ignore */ }
    ringtoneOsc = null;
    ringtoneGain = null;
  }

  let summaryFetchToken = 0;
  async function loadAiSummary(patientId){
    const hasSummaryTargets = !!(elMSummary || elMSummaryStatus);
    if (!hasSummaryTargets) return;

    const token = ++summaryFetchToken;

    if (elMSummary) {
      elMSummary.innerHTML = '';
    }
    if (elMSummaryStatus) {
      elMSummaryStatus.textContent = patientId ? 'Fetching AI summary…' : 'Patient ID missing.';
      elMSummaryStatus.classList.remove('hidden');
    }

    if (!patientId) {
      return;
    }

    try {
      const url = `${API_BASE}/ai/summary?user_id=${encodeURIComponent(patientId)}&limit=5&summarize=1`;
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }
      const data = await res.json();
      if (summaryFetchToken !== token) return; // stale
      const summaryText = (data?.summary || '').trim();

      if (summaryText) {
        if (elMSummaryStatus) {
          elMSummaryStatus.classList.add('hidden');
        }
        if (elMSummary) {
          elMSummary.innerHTML = '';
          summaryText.split(/\n+/).forEach(line => {
            const trimmed = line.trim();
            if (!trimmed) return;
            const p = document.createElement('p');
            p.className = 'text-sm text-gray-700 leading-relaxed';
            if (/^(Q|A):/i.test(trimmed)) {
              const label = trimmed.slice(0, 2).toUpperCase();
              const value = trimmed.slice(2).trim();
              const strong = document.createElement('span');
              strong.className = 'font-semibold text-gray-800 pr-1';
              strong.textContent = label;
              p.appendChild(strong);
              p.appendChild(document.createTextNode(value));
            } else {
              p.textContent = trimmed;
            }
            elMSummary.appendChild(p);
          });
        }
        if (elMSummary && elMSummary.childElementCount === 0 && elMSummaryStatus) {
          elMSummaryStatus.textContent = 'No recent AI chats found.';
          elMSummaryStatus.classList.remove('hidden');
        }
      } else if (elMSummaryStatus) {
        elMSummaryStatus.textContent = 'No recent AI chats found.';
        elMSummaryStatus.classList.remove('hidden');
      }
    } catch (err) {
      if (summaryFetchToken !== token) return; // stale
      console.warn('[doctor] failed to load AI summary', err);
      if (elMSummaryStatus) {
        elMSummaryStatus.textContent = 'Unable to load AI chat summary.';
        elMSummaryStatus.classList.remove('hidden');
      }
    }
  }

  let joined = false;
  let lastCall = null;
  const url = new URL(window.location.href);
  const autoLive = (url.searchParams.get('live') === '1');
  const doctorId = Number(url.searchParams.get('doctorId') || window.CURRENT_USER_ID || DEFAULT_DOCTOR_ID || 0) || null;

  // ===== Visibility state (persisted) =====
  function isVisible(){ return (localStorage.getItem('clinic_visible') ?? 'on') !== 'off'; }
  function setVisible(v){ localStorage.setItem('clinic_visible', v ? 'on' : 'off'); }
  function updateNoCalls(on){ if (elNoCallsSub) elNoCallsSub.textContent = on ? 'Connect to receive calls' : 'You will not be receiving calls'; }
  function ensureToggle(){
    try{
      const groups = document.querySelectorAll('header .flex.items-center.gap-3');
      const left = groups && groups.length ? groups[0] : null;
      if (!left) return null;
      if (document.getElementById('visibility-toggle')) return document.getElementById('visibility-toggle');
      const label = document.createElement('label');
      label.className = 'inline-flex items-center gap-2 cursor-pointer select-none';
      label.title = 'Clinic Visibility';
      label.innerHTML = `
        <input id="visibility-toggle" type="checkbox" class="sr-only peer">
        <span class="relative w-10 h-5 bg-gray-300 rounded-full transition peer-checked:bg-green-500">
          <span class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-5"></span>
        </span>
        <span id="visibility-label" class="text-sm text-gray-700">Visible</span>
      `;
      left.appendChild(label);
      return label.querySelector('#visibility-toggle');
    }catch(_){ return null; }
  }
  const toggle = ensureToggle();
  const toggleLabel = document.getElementById('visibility-label');
  const initialVisible = isVisible();
  if (toggle) {
    toggle.checked = initialVisible;
    if (toggleLabel) toggleLabel.textContent = initialVisible ? 'Visible' : 'Hidden';
  }
  updateNoCalls(initialVisible);

  const socket = io(SOCKET_URL, {
    transports: ['websocket','polling'],
    withCredentials: false,
    path: '/socket.io/',
    autoConnect: initialVisible,
    reconnection: true
  });

  function addLog(msg){ console.log('[doctor][log]', msg); }

  function setConn(state){
    if (elConn) elConn.textContent = state ? 'connected' : 'disconnected';
    if (elConnYes) elConnYes.textContent = state ? 'Yes' : 'No';
  }

  function setHeaderStatus(status){
    if (!elHeaderDot) return;
    elHeaderDot.classList.remove('bg-yellow-400','bg-green-500','bg-red-500');
    if (status==='online') elHeaderDot.classList.add('bg-green-500');
    else if (status==='error' || status==='offline') elHeaderDot.classList.add('bg-red-500');
    else elHeaderDot.classList.add('bg-yellow-400');
  }

  // Initial alert on load based on visibility
  try{
    if (initialVisible) {
      setHeaderStatus('connecting');
      if (window.Swal) Swal.fire({icon:'success', title:'Online', text:'Your clinic is currently visible to patients within 10 km.'});
    } else {
      setHeaderStatus('offline');
      if (window.Swal) Swal.fire({icon:'info', title:'Offline', text:'You will not be receiving calls. Turn on this button to receive video consultation calls.'});
    }
  }catch(_){ }

  socket.on('connect', ()=>{
    if (elSocketId) elSocketId.textContent = socket.id;
    setConn(true);
    addLog('Socket connected: ' + socket.id);
    setHeaderStatus('connecting');
    console.log('[doctor] socket connected', { id: socket.id, url: SOCKET_URL });
    if (doctorId && !joined) {
      socket.emit('join-doctor', Number(doctorId));
      addLog('emit join-doctor ' + doctorId);
      console.log('[doctor] emit join-doctor', doctorId);
    }
  });

  socket.on('connect_error', (err)=>{
    setConn(false);
    addLog('connect_error: ' + (err && err.message));
    setHeaderStatus('error');
    console.warn('[doctor] socket connect_error:', err?.message||err);
  });

  socket.on('disconnect', ()=>{
    setConn(false);
    joined = false;
    addLog('Socket disconnected');
    setHeaderStatus('offline');
  });

  socket.on('doctor-online', (data)=>{
    try {
      if (Number(data?.doctorId) === Number(doctorId)) {
        joined = true;
        if (elIsOnline) elIsOnline.textContent = 'Yes';
        addLog('Doctor online ack for ' + doctorId);
        setHeaderStatus('online');
        console.log('[doctor] online ack for', doctorId);
      }
    } catch {}
  });

  socket.on('call-requested', (payload)=>{
    lastCall = payload || null;
    try{
      if (!modal) return;
      const patientId = payload?.patientId ?? '';
      if (elMPatient) {
        const numericId = Number(patientId);
        const displayId = Number.isFinite(numericId) && numericId > 0 ? `#${numericId}` : String(patientId || 'Unknown');
        elMPatient.textContent = displayId;
      }
      if (elMChannel) elMChannel.textContent = String(payload?.channel ?? '');
      if (elMTime)    elMTime.textContent    = new Date().toLocaleString();
      if (elMSummaryStatus) {
        elMSummaryStatus.textContent = 'Fetching AI summary…';
        elMSummaryStatus.classList.remove('hidden');
      }
      if (elMSummary) {
        elMSummary.innerHTML = '';
      }
      stopIncomingTone();
      startIncomingTone();
      loadAiSummary(patientId);
      modal.classList.remove('hidden');
      addLog('Incoming call for doctor ' + doctorId + ' ch=' + (payload?.channel||''));
    }catch(e){ console.warn('[doctor] call-requested render failed', e); }
  });

  document.getElementById('m-accept')?.addEventListener('click', ()=>{
    try{
      modal?.classList.add('hidden');
      stopIncomingTone();
      const ch = (lastCall?.channel || elMChannel?.textContent || '').trim();
      const callId = (lastCall?.callId || '').trim();
      if (callId) {
        socket.emit('call-accepted', {
          callId,
          doctorId: Number(doctorId||0),
          patientId: Number(lastCall?.patientId||0),
          channel: ch,
        });
      }
      const callUrl = (PATH_PREFIX || '') + '/call-page/' + encodeURIComponent(ch) + '?uid=' + encodeURIComponent(doctorId||'') + '&role=host' + (callId ? ('&callId=' + encodeURIComponent(callId)) : '') + '&pip=1';
      window.location.href = callUrl;
    }catch(e){ console.warn('[doctor] accept failed', e); }
  });

  document.getElementById('m-reject')?.addEventListener('click', ()=>{
    try{
      modal?.classList.add('hidden');
      stopIncomingTone();
      const callId = (lastCall?.callId || '').trim();
      if (callId) socket.emit('call-rejected', { callId, reason: 'rejected' });
    }catch(e){ /* no-op */ }
  });


  // Auto-join like /doctor/live?doctorId=...&live=1
  if (autoLive && doctorId && isVisible()) {
    if (!socket.connected) try{ socket.connect(); }catch(_){ }
    // If already connected, emit immediately
    if (socket.connected) { socket.emit('join-doctor', Number(doctorId)); addLog('Auto join-doctor ' + doctorId); }
  }


  // Toggle behaviour: connect/disconnect and SweetAlerts
  if (toggle) {
    toggle.addEventListener('change', ()=>{
      const on = !!toggle.checked;
      setVisible(on);
      if (toggleLabel) toggleLabel.textContent = on ? 'Visible' : 'Hidden';
      updateNoCalls(on);
      if (on) {
        setHeaderStatus('connecting');
        try{ socket.io.opts.reconnection = true; socket.connect(); }catch(_){ }
        if (window.Swal) Swal.fire({icon:'success', title:'Online', text:'Your clinic is currently visible to patients within 10 km.'});
      } else {
        try{ socket.io.opts.reconnection = false; socket.disconnect(); }catch(_){ }
        setHeaderStatus('offline');
        if (window.Swal) Swal.fire({icon:'info', title:'Offline', text:'You will not be receiving calls. Turn on this button to receive video consultation calls.'});
      }
    });
  }
})();
</script>

</body>
</html>
