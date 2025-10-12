{{-- resources/views/groomer/services/index.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Services</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  <style>
    #client-logger{font-family:ui-monospace,Menlo,Consolas,monospace}
  </style>
</head>
<body class="h-screen bg-gray-50">

<div class="flex h-full">
  <!-- Sidebar -->
  <aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
      <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    </div>

    <nav class="px-3 py-4 space-y-1">
      <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>

      <a href="{{ route('doctor.dashboard') }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-medium">Video Consultation</span>
      </a>
      <a href="{{ route('groomer.services.index') }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h6v6H4V6zm0 8h6v6H4v-6zm8-8h6v6h-6V6zm0 8h6v6h-6v-6z"/>
        </svg>
        <span class="text-sm font-medium">Services</span>
      </a>

      <a href="{{ route('booking.clinics') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 19h14a2 2 0 002-2v-6H3v6a2 2 0 002 2z"/></svg>
        <span class="text-sm font-medium">Book Appointment</span>
      </a>

      <a href="{{ route('doctor.bookings') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2z"/></svg>
        <span class="text-sm font-medium">My Bookings</span>
      </a>

      <a href="{{ route('doctor.schedule') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm font-medium">Weekly Schedule</span>
      </a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="flex-1 flex flex-col">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Services</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connecting…"></span>
        <span id="status-pill" class="hidden px-3 py-1 rounded-full text-xs font-bold">…</span>
      </div>

      <div class="flex items-center gap-3">
        <button id="btn-auth"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">
          🔐 Auth
        </button>

        <button id="toggle-diag"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-800">
          Diagnostics
        </button>

        <button id="btn-open-create"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">
          + Add Service
        </button>

        <div class="text-right">
          <div class="text-sm font-medium text-gray-900">{{ auth()->user()->name ?? 'Doctor' }}</div>
          <div class="text-xs text-gray-500">{{ auth()->user()->role ?? 'doctor' }}</div>
        </div>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
          {{ strtoupper(substr(auth()->user()->name ?? 'D',0,1)) }}
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <section class="flex-1 p-6">
      <div class="max-w-6xl mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
          <div class="p-3 border-b">
            <input id="search" type="text" placeholder="Search by name..."
                   class="w-full md:w-80 bg-gray-100 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 border-0">
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-100 text-gray-700">
                <tr>
                  <th class="text-left px-4 py-3">Name</th>
                  <th class="text-left px-4 py-3">Pet</th>
                  <th class="text-left px-4 py-3">Price (₹)</th>
                  <th class="text-left px-4 py-3">Duration (m)</th>
                  <th class="text-left px-4 py-3">Category</th>
                  <th class="text-left px-4 py-3">Status</th>
                  <th class="text-left px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody id="rows"></tbody>
            </table>
          </div>
          <div id="empty" class="hidden p-8 text-center text-gray-500">No services found.</div>
        </div>
      </div>
    </section>
  </main>
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[96%] max-w-3xl p-6 relative">
    <button type="button" class="btn-close absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">✕</button>
    <h3 class="text-xl font-semibold text-gray-800 mb-1">Add New Service</h3>
    <p class="text-sm text-gray-500 mb-4">Fill details to create service</p>

    <form id="create-form" class="space-y-4">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Service Name</label>
          <input name="serviceName" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Price (₹)</label>
          <input name="price" type="number" min="0" step="0.01" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Duration (mins)</label>
          <input name="duration" type="number" min="1" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Pet Type</label>
          <select name="petType" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="">Select Pet</option>
            <option value="dog">Dog</option>
            <option value="cat">Cat</option>
            <option value="bird">Bird</option>
            <option value="rabbit">Rabbit</option>
            <option value="hamster">Hamster</option>
            <option value="all">All</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Service Category</label>
          <select name="main_service" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="">Select</option>
            <option value="grooming">Grooming</option>
            <option value="video_call">Video Call</option>
            <option value="vet">Vet Service</option>
            <option value="pet_walking">Pet Walking</option>
            <option value="sitter">Sitter</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Status</label>
          <select name="status" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Notes (optional)</label>
        <textarea name="description" rows="3" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" class="btn-close px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[96%] max-w-3xl p-6 relative">
    <button type="button" class="btn-close absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">✕</button>
    <h3 class="text-xl font-semibold text-gray-800 mb-1">Edit Service</h3>
    <p class="text-sm text-gray-500 mb-4">Update details</p>

    <form id="edit-form" class="space-y-4">
      <input type="hidden" name="id">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Service Name</label>
          <input name="serviceName" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Price (₹)</label>
          <input name="price" type="number" min="0" step="0.01" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Duration (mins)</label>
          <input name="duration" type="number" min="1" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Pet Type</label>
          <select name="petType" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="">Select Pet</option>
            <option value="dog">Dog</option>
            <option value="cat">Cat</option>
            <option value="bird">Bird</option>
            <option value="rabbit">Rabbit</option>
            <option value="hamster">Hamster</option>
            <option value="all">All</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Service Category</label>
          <select name="main_service" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="grooming">Grooming</option>
            <option value="video_call">Video Call</option>
            <option value="vet">Vet Service</option>
            <option value="pet_walking">Pet Walking</option>
            <option value="sitter">Sitter</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Status</label>
          <select name="status" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Notes (optional)</label>
        <textarea name="description" rows="3" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" class="btn-close px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Frontend Logger + Auth Panel ===== -->
<div id="client-logger" class="hidden fixed bottom-20 right-4 z-[100] w-[460px] max-h-[72vh] bg-white border border-gray-200 rounded-xl shadow-2xl">
  <div class="flex items-center justify-between px-3 py-2 border-b">
    <div class="text-xs font-bold text-gray-700">Frontend Logger</div>
    <div class="flex items-center gap-2">
      <input id="log-token" placeholder="paste Bearer token…" class="px-2 py-1 rounded bg-gray-100 text-xs w-44">
      <button id="log-token-save" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs">Save</button>
      <button id="log-dump" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">Download</button>
      <button id="log-clear" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">Clear</button>
      <button id="log-close" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">✕</button>
    </div>
  </div>
  <div id="log-body" class="text-[11px] leading-4 text-gray-800 px-3 py-2 overflow-y-auto whitespace-pre-wrap"></div>
</div>
<button id="log-toggle" class="fixed bottom-4 right-4 z-[90] px-3 py-2 rounded-full bg-black text-white text-xs shadow-lg">
  🪵 Logs (<span id="log-count">0</span>)
</button>

<script>
  const SESSION_LOGIN_URL = @json(url('/api/session/login'));
  const SERVER_USER_ID = (() => {
    const raw = @json(auth()->id() ?? session('user_id'));
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();

  // ===== CURRENT_USER_ID strictly from frontend =====
  const CURRENT_USER_ID = (() => {
    try {
      const url = new URL(location.href);
      const qRaw = url.searchParams.get('userId') ?? url.searchParams.get('doctorId');
      const qid = Number(qRaw);
      const authFull = JSON.parse(localStorage.getItem('auth_full') || sessionStorage.getItem('auth_full') || 'null');
      const stg = Number(localStorage.getItem('user_id') || sessionStorage.getItem('user_id'));
      const fromAuth = Number(authFull?.user?.id ?? authFull?.user_id);
      const candidates = [qid, SERVER_USER_ID, fromAuth, stg];
      for (const value of candidates){
        if (Number.isFinite(value) && value > 0){
          return Number(value);
        }
      }
      return null;
    } catch (_) { return null; }
  })();
  console.log('[services] CURRENT_USER_ID:', CURRENT_USER_ID);

  // ===== CONFIG (backend endpoints) =====
  const CONFIG = {
    API_BASE: @json(url('/api')),
    CSRF_URL: @json(url('/sanctum/csrf-cookie')),
    LOGIN_API: @json(url('/api/login')),
    SESSION_LOGIN: SESSION_LOGIN_URL,
  };
  const API = {
    list:   (uid) => `${CONFIG.API_BASE}/groomer/services?user_id=${encodeURIComponent(uid ?? '')}`,
    create: `${CONFIG.API_BASE}/groomer/service`,
    show:   (id,uid) => `${CONFIG.API_BASE}/groomer/service/${id}?user_id=${encodeURIComponent(uid ?? '')}`,
    update: (id,uid) => `${CONFIG.API_BASE}/groomer/service/${id}/update?user_id=${encodeURIComponent(uid ?? '')}`,
    delete: (id,uid) => `${CONFIG.API_BASE}/groomer/service/${id}?user_id=${encodeURIComponent(uid ?? '')}`,
  };

  // ===== Logger =====
  (function(){
    const ui={panel:document.getElementById('client-logger'),body:document.getElementById('log-body'),toggle:document.getElementById('log-toggle'),count:document.getElementById('log-count'),close:document.getElementById('log-close'),clear:document.getElementById('log-clear'),dump:document.getElementById('log-dump'),tokenI:document.getElementById('log-token'),tokenS:document.getElementById('log-token-save')};
    const MAX=600,buf=[];
    const trunc=(s,n)=>{ if(typeof s!=='string'){ try{s=JSON.stringify(s)}catch(_){s=String(s)} } return s.length>n?s.slice(0,n)+'…':s; };
    const stamp=()=>new Date().toISOString();
    function push(level,msg,meta){ const row={t:stamp(),level,msg,meta}; buf.push(row); if(buf.length>MAX) buf.shift(); const div=document.createElement('div'); div.textContent=`[${row.t}] ${level.toUpperCase()} ${msg}${meta?' '+trunc(meta,1800):''}`; ui.body.appendChild(div); ui.body.scrollTop=ui.body.scrollHeight; ui.count.textContent=String(buf.length); }
    const Log={info:(m,d)=>push('info',m,d? (typeof d==='string'?d:JSON.stringify(d)):''),warn:(m,d)=>push('warn',m,d? (typeof d==='string'?d:JSON.stringify(d)):''),error:(m,d)=>push('error',m,d? (typeof d==='string'?d:JSON.stringify(d)):''),open:()=>ui.panel.classList.remove('hidden'),close:()=>ui.panel.classList.add('hidden'),clear:()=>{ui.body.innerHTML='';buf.length=0;ui.count.textContent='0'},dump:()=>({env:{api:API,login_api:CONFIG.LOGIN_API,token_present:!!(localStorage.getItem('token')||sessionStorage.getItem('token'))},logs:buf})};
    window.ClientLog=Log;

    ui.toggle.addEventListener('click',Log.open);
    ui.close.addEventListener('click',Log.close);
    ui.clear.addEventListener('click',Log.clear);
    ui.dump.addEventListener('click',()=>{ const blob=new Blob([JSON.stringify(Log.dump(),null,2)],{type:'application/json'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='frontend-logs.json'; a.click(); URL.revokeObjectURL(a.href); });
    ui.tokenS.addEventListener('click',()=>{ const t=ui.tokenI.value.trim(); if(!t) return; localStorage.setItem('token',t); sessionStorage.setItem('token',t); Swal.fire({icon:'success',title:'Token saved',timer:1200,showConfirmButton:false}); });

    // instrument fetch
    const origFetch=window.fetch.bind(window);
    window.fetch=async function(input,init={}){
      const url=(typeof input==='string')?input:input.url;
      const method=(init?.method||(typeof input==='object'&&input.method)||'GET').toUpperCase();
      const start=performance.now();
      Log.info('NET:REQUEST', JSON.stringify({method,url,headers:init?.headers||{},cred:init?.credentials||'default'}));
      try{
        const res=await origFetch(input,init);
        const ct=res.headers.get('content-type')||'';
        const ms=Math.round(performance.now()-start);
        Log.info('NET:RESPONSE', JSON.stringify({method,url,status:res.status,ok:res.ok,duration_ms:ms,content_type:ct}));
        return res;
      }catch(err){
        Log.error('NET:FAILED', JSON.stringify({method,url,error:err?.message||String(err)}));
        throw err;
      }
    };
  })();

  // ===== Auth helper (Bearer OR Sanctum cookie) =====
  const Auth = {
    mode: 'unknown', // 'bearer' | 'cookie' | 'none'
    async bootstrap(){
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      if (token){ this.mode='bearer'; return {mode:'bearer'}; }
      try{
        await fetch(CONFIG.CSRF_URL, {credentials:'include'});
        const xsrf = getCookie('XSRF-TOKEN');
        if (xsrf){ this.mode='cookie'; return {mode:'cookie', xsrf}; }
        this.mode='none'; return {mode:'none'};
      }catch{ this.mode='none'; return {mode:'none'}; }
    },
    headers(base={}){
      const h={ 'Accept':'application/json', ...base };
      if (CURRENT_USER_ID){
        h['X-User-Id'] = String(CURRENT_USER_ID);
      }
      if (this.mode==='bearer'){
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        if (token) h['Authorization']='Bearer '+token;
      } else if (this.mode==='cookie'){
        h['X-Requested-With']='XMLHttpRequest';
        const xsrf = decodeURIComponent(getCookie('XSRF-TOKEN')||'');
        if (xsrf) h['X-XSRF-TOKEN']=xsrf;
      }
      return h;
    },
  };

  function getCookie(name){
    return document.cookie.split('; ').find(r=>r.startsWith(name+'='))?.split('=')[1] || '';
  }

  async function apiFetch(url, opts={}, expectJSON=true){
    const res = await fetch(url, { credentials:'include', ...opts });
    const ct  = res.headers.get('content-type')||'';
    let body;

    if (expectJSON && ct.includes('application/json')){
      const text = await res.text();
      const cleaned = text.replace(/^\uFEFF/, '');
      try {
        body = JSON.parse(cleaned || 'null');
      } catch (parseErr) {
        const err = new Error(`Invalid JSON response: ${parseErr?.message || parseErr}`);
        err.status = res.status;
        err.body = cleaned;
        err.cause = parseErr;
        throw err;
      }
    } else {
      body = await res.text();
    }

    if (!res.ok){
      const msg = (body && body.message) ? body.message : `HTTP ${res.status}`;
      const err = new Error(msg); err.status=res.status; err.body=body; throw err;
    }
    return body;
  }

  // ===== UI helpers =====
  const $ = (s, r=document)=> r.querySelector(s);
  const $$ = (s, r=document)=> Array.from(r.querySelectorAll(s));
  const rows = $('#rows');
  const empty = $('#empty');
  const search = $('#search');
  const createModal = $('#create-modal');
  const editModal   = $('#edit-modal');
  const open = el => el.classList.remove('hidden');
  const close = el => el.classList.add('hidden');
  function esc(s){ return (''+(s??'')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

  // ===== List + Render =====
  let ALL = [];
  async function fetchServices(){
    if (!CURRENT_USER_ID){
      const helpUrl = `${CONFIG.SESSION_LOGIN}?user_id=YOUR_ID`;
      rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-rose-600" colspan="7">user_id missing (add ?userId=... in URL or visit <a class="text-blue-600 underline" target="_blank" rel="noreferrer" href="${esc(helpUrl)}">${esc(CONFIG.SESSION_LOGIN)}?user_id=YOUR_ID</a> then reload)</td></tr>`;
      return;
    }
    rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-gray-500" colspan="7">Loading…</td></tr>`;
    try{
      await Auth.bootstrap();
      const res = await apiFetch(API.list(CURRENT_USER_ID), {
        headers: Auth.headers()
      });
      const items = Array.isArray(res) ? res : Array.isArray(res?.data) ? res.data : [];
      ALL = items;
      render(ALL);
    }catch(e){
      rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-rose-600" colspan="7">Failed to load (${esc(e.message||e)})</td></tr>`;
      ClientLog?.error('services.load.failed', e.message||String(e));
      ClientLog?.open();
    }
  }

  function render(list){
    rows.innerHTML = '';
    if(!list.length){ empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');

    for(const it of list){
      const tr = document.createElement('tr');
      tr.className = 'border-t';
      tr.innerHTML = `
        <td class="px-4 py-3 font-medium">${esc(it.name)}</td>
        <td class="px-4 py-3">${esc(it.pet_type || it.petType || '')}</td>
        <td class="px-4 py-3">${Number(it.price).toFixed(2)}</td>
        <td class="px-4 py-3">${it.duration}</td>
        <td class="px-4 py-3">${esc(it.main_service || '')}</td>
        <td class="px-4 py-3">
          <span class="px-2 py-0.5 rounded-full text-xs ${it.status==='Active'?'bg-emerald-100 text-emerald-700':'bg-gray-200 text-gray-700'}">
            ${esc(it.status || '')}
          </span>
        </td>
        <td class="px-4 py-3">
          <button class="mr-2 text-blue-600 hover:underline" data-act="edit" data-id="${it.id}">Edit</button>
          <button class="text-rose-600 hover:underline" data-act="delete" data-id="${it.id}">Delete</button>
        </td>
      `;
      rows.appendChild(tr);
    }
  }

  // Search
  search.addEventListener('input', e=>{
    const q = e.target.value.toLowerCase().trim();
    const filtered = !q ? ALL : ALL.filter(x => (x.name||'').toLowerCase().includes(q));
    render(filtered);
  });

  // ===== Create =====
  document.getElementById('btn-open-create').addEventListener('click', ()=> open(createModal));
  $$('.btn-close', createModal).forEach(b=> b.addEventListener('click', ()=> close(createModal)));

  document.getElementById('create-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    if (!CURRENT_USER_ID){ Swal.fire({icon:'warning',title:'user_id missing'}); return; }

    const fd = new FormData(e.target);
    const payload = new FormData();
    payload.append('serviceName',  fd.get('serviceName'));
    payload.append('description',  fd.get('description') || '');
    payload.append('petType',      fd.get('petType'));
    payload.append('price',        fd.get('price'));
    payload.append('duration',     fd.get('duration'));
    payload.append('main_service', fd.get('main_service'));
    payload.append('status',       fd.get('status'));
    // ⭐ send user_id from frontend
    payload.append('user_id',      String(CURRENT_USER_ID));

    try{
      await Auth.bootstrap();
      const res = await apiFetch(API.create, {
        method:'POST',
        headers: Auth.headers(),
        body: payload
      });
      Swal.fire({icon:'success', title:'Service Created', text:'Service was created successfully', timer:1500, showConfirmButton:false});
      close(createModal);
      await fetchServices();
      ClientLog?.info('service.create.success', JSON.stringify(res).slice(0,800));
    }catch(err){
      Swal.fire({icon:'error', title:'Create failed', text: err.message || 'Error'});
      ClientLog?.error('service.create.failed', err.message||String(err));
      ClientLog?.open();
    }
  });

  // ===== Edit/Delete actions =====
  rows.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-act]');
    if(!btn) return;
    const {act, id} = btn.dataset;

    if(act==='edit'){
      try{
        await Auth.bootstrap();
        const data = await apiFetch(API.show(id, CURRENT_USER_ID), { headers: Auth.headers() });
        const s = data?.data || data;
        fillEdit(s);
        open(editModal);
      }catch(err){
        Swal.fire({icon:'error', title:'Failed to load service', text: err.message||'Error'});
        ClientLog?.error('service.show.failed', err.message||String(err));
      }
    }

    if(act==='delete'){
      const ok = await Swal.fire({
        icon:'warning',
        title:'Delete this service?',
        text:'This action cannot be undone.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete',
        cancelButtonText:'Cancel'
      });
      if(!ok.isConfirmed) return;

      try{
        await Auth.bootstrap();
        // DELETE with header + query ?user_id=
        await apiFetch(API.delete(id, CURRENT_USER_ID), {
          method:'DELETE',
          headers: Auth.headers()
        }, true);
        Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
        await fetchServices();
      }catch(err){
        // Fallback: POST override if server blocks DELETE
        try{
          const payload = new FormData();
          payload.append('user_id', String(CURRENT_USER_ID));
          await apiFetch(API.delete(id, CURRENT_USER_ID), {
            method:'POST',
            headers: Auth.headers({'X-HTTP-Method-Override':'DELETE'}),
            body: payload
          }, true);
          Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
          await fetchServices();
        }catch(err2){
          Swal.fire({icon:'error', title:'Delete failed', text: err2.message || 'Error'});
          ClientLog?.error('service.delete.failed', err2.message||String(err2));
          ClientLog?.open();
        }
      }
    }
  });

  function fillEdit(s){
    const f = document.getElementById('edit-form');
    f.elements['id'].value = s.id;
    f.elements['serviceName'].value = s.name || '';
    f.elements['description'].value = s.description || '';
    f.elements['petType'].value = s.pet_type || s.petType || '';
    f.elements['price'].value = s.price || 0;
    f.elements['duration'].value = s.duration || 0;
    f.elements['main_service'].value = s.main_service || '';
    f.elements['status'].value = s.status || 'Active';
  }

  $$('.btn-close', editModal).forEach(b=> b.addEventListener('click', ()=> close(editModal)));

  document.getElementById('edit-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    if (!CURRENT_USER_ID){ Swal.fire({icon:'warning',title:'user_id missing'}); return; }

    const f = e.target;
    const id = f.elements['id'].value;
    const payload = new FormData();
    payload.append('serviceName',  f.elements['serviceName'].value);
    payload.append('description',  f.elements['description'].value || '');
    payload.append('petType',      f.elements['petType'].value);
    payload.append('price',        f.elements['price'].value);
    payload.append('duration',     f.elements['duration'].value);
    payload.append('main_service', f.elements['main_service'].value);
    payload.append('status',       f.elements['status'].value);
    // ⭐ send user_id from frontend
    payload.append('user_id',      String(CURRENT_USER_ID));

    try{
      await Auth.bootstrap();
      const res = await apiFetch(API.update(id, CURRENT_USER_ID), {
        method:'POST',
        headers: Auth.headers(),
        body: payload
      });
      Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false});
      close(editModal);
      await fetchServices();
      ClientLog?.info('service.update.success', JSON.stringify(res).slice(0,800));
    }catch(err){
      // Fallback: PUT w/ override
      try{
        const res2 = await apiFetch(`${CONFIG.API_BASE}/groomer/services/${id}?user_id=${encodeURIComponent(CURRENT_USER_ID)}`, {
          method:'POST',
          headers: Auth.headers({'X-HTTP-Method-Override':'PUT'}),
          body: payload
        });
        Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false});
        close(editModal);
        await fetchServices();
        ClientLog?.info('service.update.success(fallback)', JSON.stringify(res2).slice(0,800));
      }catch(err2){
        Swal.fire({icon:'error', title:'Update failed', text: err2.message || err.message || 'Error'});
        ClientLog?.error('service.update.failed', err2.message||String(err2));
        ClientLog?.open();
      }
    }
  });

  // ===== Init =====
  document.addEventListener('DOMContentLoaded', async ()=>{
    await fetchServices();
  });

  // ===== Logger hotkey =====
  (function(){
    const uiToggle=document.getElementById('log-toggle');
    const uiPanel=document.getElementById('client-logger');
    const uiClose=document.getElementById('log-close');
    uiToggle.addEventListener('click', ()=> uiPanel.classList.remove('hidden'));
    uiClose.addEventListener('click', ()=> uiPanel.classList.add('hidden'));
    window.addEventListener('keydown',e=>{ if((e.ctrlKey||e.metaKey)&&e.key==='`'){ e.preventDefault(); uiPanel.classList.toggle('hidden'); }});
  })();
</script>

</body>
</html>
