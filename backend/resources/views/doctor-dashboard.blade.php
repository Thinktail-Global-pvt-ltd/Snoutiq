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
    #client-logger{font-family:ui-monospace,Menlo,Consolas,monospace}
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

  // ⭐ FINAL id the frontend will use everywhere:
  let CURRENT_USER_ID = SESSION_USER_ID || fromServer || fromQuery || fromStorage || DEFAULT_DOCTOR_ID || null;

  console.log('[doctor-dashboard] RESOLVED user_id →', {
    SESSION_USER_ID, fromServer, fromQuery, fromStorage, CURRENT_USER_ID, PATH_PREFIX, API_BASE
  });
</script>

<div class="flex h-full">
  {{-- Sidebar --}}
  <aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
      <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    </div>

    <nav class="px-3 py-4 space-y-1">
      <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>

      <a href="{{ route('doctor.dashboard') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-medium">Video Consultation</span>
      </a>

      <a href="{{ route('groomer.services.index') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6v6H4V6zm0 8h6v6H4v-6zm8-8h6v6h-6V6zm0 8h6v6h-6v-6z"/>
        </svg>
        <span class="text-sm font-medium">Services</span>
      </a>


      <a href="{{ route('doctor.bookings') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2z"/></svg>
        <span class="text-sm font-medium">My Bookings</span>
      </a>

      <a href="{{ route('doctor.video.schedule.manage') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm font-medium">Video Calling Schedule</span>
      </a>

      <a href="{{ route('clinic.orders') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
        <span class="text-sm font-medium">Order History</span>
      </a>

      <a href="{{ route('doctor.schedule') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm font-medium">Clinic Schedule</span>
      </a>
    </nav>
  </aside>

  {{-- Main --}}
  <main class="flex-1 flex flex-col">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Doctor Dashboard</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connecting…"></span>
        <span id="status-pill" class="hidden px-3 py-1 rounded-full text-xs font-bold">…</span>
      </div>

      <div class="flex items-center gap-3">
        <button id="toggle-diag" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-800">Diagnostics</button>
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
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 order-1">
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

        <div class="lg:col-span-1 order-2">
          <div id="diagnostics" class="hidden space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
              <div class="text-sm text-gray-700 mb-2">Doctor ID: <strong id="doctor-id">…</strong></div>
              <div class="text-sm space-y-1">
                <div>Socket ID: <code id="socket-id" class="text-gray-600">Not connected</code></div>
                <div>Connection Status: <strong id="conn-status" class="text-gray-800">connecting</strong></div>
                <div>Socket Connected: <strong id="socket-connected" class="text-gray-800">No</strong></div>
                <div>Is Online: <strong id="is-online" class="text-gray-800">No</strong></div>
              </div>
              <div class="mt-4 grid grid-cols-3 gap-2">
                <button id="btn-rejoin" class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">🔄 Rejoin</button>
                <button id="btn-test" class="px-3 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-black">🧪 Test</button>
                <button id="btn-clear" class="px-3 py-2 rounded-lg bg-gray-200 text-gray-900 text-sm font-medium hover:bg-gray-300">🗑️ Clear</button>
              </div>
            </div>

            <div class="bg-black text-green-400 rounded-xl p-4 text-xs font-mono max-h-64 overflow-y-auto border border-gray-800">
              <div class="font-bold mb-2">Debug Logs</div>
              <div id="logs"></div>
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
      <div><span class="font-semibold">Patient:</span> <span id="m-patient"></span></div>
      <div><span class="font-semibold">Channel:</span> <span id="m-channel" class="break-all"></span></div>
      <div class="text-xs text-gray-500"><span class="font-semibold">Time:</span> <span id="m-time"></span></div>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-3">
      <button id="m-accept" class="py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow">✅ Accept</button>
      <button id="m-reject" class="py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold shadow">❌ Reject</button>
    </div>
  </div>
</div>

{{-- Frontend Logger (toggle with button or Ctrl+`) --}}
<div id="client-logger" class="hidden fixed bottom-20 right-4 z-[100] w-[420px] max-h-[70vh] bg-white border border-gray-200 rounded-xl shadow-2xl">
  <div class="flex items-center justify-between px-3 py-2 border-b">
    <div class="text-xs font-bold text-gray-700">Frontend Logger</div>
    <div class="flex items-center gap-2">
      <input id="log-token" placeholder="paste Bearer token…" class="px-2 py-1 rounded bg-gray-100 text-xs w-40">
      <button id="log-token-save" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs">Save</button>
      <button id="log-download" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">Download</button>
      <button id="log-clear" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">Clear</button>
      <button id="log-close" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">✕</button>
    </div>
  </div>
  <div id="log-body" class="text-[11px] leading-4 text-gray-800 px-3 py-2 overflow-y-auto whitespace-pre-wrap"></div>
</div>
<button id="log-toggle" class="fixed bottom-4 right-4 z-[90] px-3 py-2 rounded-full bg-black text-white text-xs shadow-lg">
  🪵 Logs (<span id="log-count">0</span>)
</button>

<!-- =============================
     Add Service Modal (hidden by default)
============================= -->
<div id="add-service-modal" class="hidden fixed inset-0 z-[70] bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[96%] max-w-4xl p-6 relative">
    <button type="button" id="svc-close"
            class="absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">✕</button>

    <div class="mb-2">
      <h3 class="text-xl font-semibold text-gray-800">Add New Service</h3>
      <p class="text-sm text-gray-500">Fill the details below to list a new grooming service</p>
    </div>

    <form id="svc-form" class="space-y-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Service Name</label>
            <input id="svc-name" type="text" placeholder="Enter Service Name, eg. Premium Grooming"
                   class="w-full rounded-lg bg-gray-100 border-0 focus:ring-2 focus:ring-blue-500 text-sm px-3 py-2" required>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Duration (mins)</label>
            <input id="svc-duration" type="number" min="1" step="1" placeholder="Enter Duration (In Mins)"
                   class="w-full rounded-lg bg-gray-100 border-0 focus:ring-2 focus:ring-blue-500 text-sm px-3 py-2" required>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Price (₹)</label>
            <input id="svc-price" type="number" min="0" step="0.01" placeholder="Enter Service price in ₹"
                   class="w-full rounded-lg bg-gray-100 border-0 focus:ring-2 focus:ring-blue-500 text-sm px-3 py-2" required>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Pet Type</label>
            <select id="svc-pet-type" class="w-full rounded-lg bg-gray-100 border-0 focus:ring-2 focus:ring-blue-500 text-sm px-3 py-2" required>
              <option value="" selected disabled>Select Pet type</option>
              <option value="dog">Dog</option>
              <option value="cat">Cat</option>
              <option value="bird">Bird</option>
              <option value="rabbit">Rabbit</option>
              <option value="hamster">Hamster</option>
              <option value="all">All Pets</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Service Category</label>
            <select id="svc-main" class="w-full rounded-lg bg-gray-100 border-0 focus:ring-2 focus:ring-blue-500 text-sm px-3 py-2" required>
              <option value="" selected disabled>Select category</option>
              <option value="grooming">Grooming</option>
              <option value="video_call">Video Call</option>
              <option value="vet">Vet Service</option>
              <option value="pet_walking">Pet Walking</option>
              <option value="sitter">Sitter</option>
            </select>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">
          Additional Notes <span class="font-normal text-gray-500">(Optional)</span>
        </label>
        <textarea id="svc-notes" rows="4" placeholder="Add more details about the service"
                  class="w-full rounded-lg bg-gray-100 border-0 focus:ring-2 focus:ring-blue-500 text-sm px-3 py-2"></textarea>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" id="svc-cancel" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold">Cancel</button>
        <button type="submit" id="svc-submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Add Service</button>
      </div>
    </form>
  </div>
</div>

<script>
/* =========================
   Logger (Ctrl+` to toggle)
========================= */
(function(){
  const ui={panel:document.getElementById('client-logger'),body:document.getElementById('log-body'),toggle:document.getElementById('log-toggle'),count:document.getElementById('log-count'),close:document.getElementById('log-close'),clear:document.getElementById('log-clear'),dl:document.getElementById('log-download'),tokenI:document.getElementById('log-token'),tokenS:document.getElementById('log-token-save'),};
  const MAX=500,buf=[];
  function trunc(s,n){ if(typeof s!=='string') try{s=String(s)}catch(_){return '<unserializable>'}; return s.length>n?s.slice(0,n)+'…':s; }
  function stamp(){return new Date().toISOString()}
  function push(level,msg,meta){ const row={t:stamp(),level,msg,meta}; buf.push(row); if(buf.length>MAX) buf.shift(); const div=document.createElement('div'); div.textContent=`[${row.t}] ${level.toUpperCase()} ${msg}${meta?' '+trunc(typeof meta==='string'?meta:JSON.stringify(meta),2000):''}`; ui.body.appendChild(div); ui.body.scrollTop=ui.body.scrollHeight; ui.count.textContent=String(buf.length); }
  const Log={info:(m,d)=>push('info',m,d),warn:(m,d)=>push('warn',m,d),error:(m,d)=>push('error',m,d),open:()=>ui.panel.classList.remove('hidden'),close:()=>ui.panel.classList.add('hidden'),clear:()=>{ui.body.innerHTML='';buf.length=0;ui.count.textContent='0'},dump:()=>({env:{PATH_PREFIX,API_BASE,SOCKET_URL,USER_ID:CURRENT_USER_ID,url:location.href,ua:navigator.userAgent,token_present:!!(localStorage.getItem('token')||sessionStorage.getItem('token')),auth_mode:window.__authMode||'unknown'},logs:buf})};
  window.ClientLog=Log;

  ui.toggle.addEventListener('click',Log.open);
  ui.close.addEventListener('click',Log.close);
  ui.clear.addEventListener('click',Log.clear);
  ui.dl.addEventListener('click',()=>{ const blob=new Blob([JSON.stringify(Log.dump(),null,2)],{type:'application/json'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='frontend-logs.json'; a.click(); URL.revokeObjectURL(a.href); });
  ui.tokenS.addEventListener('click',()=>{ const t=ui.tokenI.value.trim(); if(!t) return; localStorage.setItem('token',t); sessionStorage.setItem('token',t); Swal.fire({icon:'success',title:'Token saved',timer:1200,showConfirmButton:false}); });

  window.addEventListener('keydown',e=>{ if((e.ctrlKey||e.metaKey)&&e.key==='`'){ e.preventDefault(); ui.panel.classList.toggle('hidden'); }});

  // instrument fetch
  const origFetch=window.fetch.bind(window);
  window.fetch=async function(input,init={}){
    const url=(typeof input==='string')?input:input.url;
    const method=(init?.method||(typeof input==='object'&&input.method)||'GET').toUpperCase();
    const start=performance.now();
    Log.info('NET:REQUEST', {method,url,headers:init?.headers||{},cred:init?.credentials||'default',body:(init?.body instanceof FormData ? '[FormData]' : undefined)});
    try{
      const res=await origFetch(input,init);
      const ct=res.headers.get('content-type')||'';
      const ms=Math.round(performance.now()-start);
      Log.info('NET:RESPONSE', {method,url,status:res.status,ok:res.ok,duration_ms:ms,content_type:ct});
      return res;
    }catch(err){
      Log.error('NET:FAILED',{method,url,error:err?.message||String(err)});
      throw err;
    }
  };

  Log.info('env', { PATH_PREFIX, API_BASE, SOCKET_URL, DOCTOR_ID: CURRENT_USER_ID, token_present: !!(localStorage.getItem('token')||sessionStorage.getItem('token')) });
})();
</script>

<script>
/* =========================
   Add Service — sends user_id = CURRENT_USER_ID
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

      // ⭐ IMPORTANT: send CURRENT_USER_ID
      fd.append('user_id', String(CURRENT_USER_ID ?? ''));

      const headers = buildHeaders(auth);

      // Debug: see exactly what is going
      console.log('[createService] POST →', {
        endpoint: API_POST_SVC,
        CURRENT_USER_ID,
        SESSION_USER_ID,
        headers,
        body: Object.fromEntries([...fd.entries()])
      });

      const data = await fetchJSON(API_POST_SVC, { method:'POST', headers, body: fd });

      Swal.fire({icon:'success', title:'Service Created', text:'Your service has been created successfully.'});
      resetForm();
      ClientLog?.info('service.create.success', data);

    }catch(err){
      const msg = err?.body?.message
        || (typeof err?.body==='string' ? err.body : JSON.stringify(err?.body||err))
        || err?.hint
        || 'Error creating service';
      Swal.fire({icon:'error', title:'Create failed', text: msg});
      ClientLog?.error('service.create.failed', { err, CURRENT_USER_ID, SESSION_USER_ID });
      ClientLog?.open();
    }finally{
      loading(els.submit,false);
    }
  }

  // init
  document.addEventListener('DOMContentLoaded', ()=>{
    const label=document.getElementById('doctor-id');
    if(label) label.textContent=String(CURRENT_USER_ID ?? '—');

    // open modal via button
    document.getElementById('btn-add-service')?.addEventListener('click', ()=>show(els.modal));
  });

  els.form?.addEventListener('submit', createService);
  els.close?.addEventListener('click', ()=>hide(els.modal));
  els.cancel?.addEventListener('click', ()=>hide(els.modal));
})();
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
  const elLogs     = document.getElementById('logs');
  const elDiagWrap = document.getElementById('diagnostics');
  const elHeaderDot= document.getElementById('status-dot');
  const modal      = document.getElementById('incoming-modal');
  const elMPatient = document.getElementById('m-patient');
  const elMChannel = document.getElementById('m-channel');
  const elMTime    = document.getElementById('m-time');

  let joined = false;
  let lastCall = null;
  const url = new URL(window.location.href);
  const autoLive = (url.searchParams.get('live') === '1');
  const doctorId = Number(url.searchParams.get('doctorId') || window.CURRENT_USER_ID || DEFAULT_DOCTOR_ID || 0) || null;

  const socket = io(SOCKET_URL, {
    transports: ['websocket','polling'],
    withCredentials: false,
    path: '/socket.io/'
  });

  function addLog(msg){
    try { if (elLogs){ const t=new Date().toLocaleTimeString(); elLogs.textContent += `[${t}] ${msg}\n`; elLogs.scrollTop = elLogs.scrollHeight; } } catch(_){}
  }

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
      if (elMPatient) elMPatient.textContent = String(payload?.patientId ?? '');
      if (elMChannel) elMChannel.textContent = String(payload?.channel ?? '');
      if (elMTime)    elMTime.textContent    = new Date().toLocaleString();
      modal.classList.remove('hidden');
      addLog('Incoming call for doctor ' + doctorId + ' ch=' + (payload?.channel||''));
    }catch(e){ console.warn('[doctor] call-requested render failed', e); }
  });

  document.getElementById('m-accept')?.addEventListener('click', ()=>{
    try{
      modal?.classList.add('hidden');
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
      const callId = (lastCall?.callId || '').trim();
      if (callId) socket.emit('call-rejected', { callId, reason: 'rejected' });
    }catch(e){ /* no-op */ }
  });

  document.getElementById('btn-rejoin')?.addEventListener('click', ()=>{
    if (socket?.connected && doctorId){ socket.emit('join-doctor', Number(doctorId)); addLog('Manual rejoin ' + doctorId); }
  });
  document.getElementById('btn-test')?.addEventListener('click', ()=>{ try{ socket.emit('get-server-status'); addLog('emit get-server-status'); }catch(_){} });
  document.getElementById('btn-clear')?.addEventListener('click', ()=>{ try{ if(elLogs) elLogs.textContent=''; }catch(_){} });

  socket.on('server-status', (s)=>{ addLog('server-status: ' + JSON.stringify(s)); });

  // Auto-join like /doctor/live?doctorId=...&live=1
  if (autoLive && doctorId) {
    if (!socket.connected) try{ socket.connect(); }catch(_){ }
    // If already connected, emit immediately
    if (socket.connected) { socket.emit('join-doctor', Number(doctorId)); addLog('Auto join-doctor ' + doctorId); }
  }

  // Toggle diagnostics panel and optionally auto-open with ?diag=1
  document.getElementById('toggle-diag')?.addEventListener('click', ()=>{
    if (!elDiagWrap) return;
    elDiagWrap.classList.toggle('hidden');
  });
  if (new URL(location.href).searchParams.get('diag') === '1') {
    elDiagWrap?.classList.remove('hidden');
  }
})();
</script>

</body>
</html>
