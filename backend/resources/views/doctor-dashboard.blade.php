{{-- resources/views/doctor-dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Doctor Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @keyframes ring{0%{transform:rotate(0)}10%{transform:rotate(15deg)}20%{transform:rotate(-15deg)}30%{transform:rotate(10deg)}40%{transform:rotate(-10deg)}50%{transform:rotate(5deg)}60%{transform:rotate(-5deg)}100%{transform:rotate(0)}}
    .ringing{animation:ring 1s infinite}
  </style>
</head>
<body class="h-screen bg-gray-50">

@php
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
  $socketUrl  = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));

  $serverCandidate = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? optional(auth()->user())->id
        ?? request('doctorId');
  $serverDoctorId = $serverCandidate ? (int)$serverCandidate : null;

  $aiChatUrl   = ($pathPrefix ? "/$pathPrefix" : '') . '/pet-dashboard';
  $thisPageUrl = ($pathPrefix ? "/$pathPrefix" : '') . '/doctor' . ($serverDoctorId ? ('?doctorId=' . urlencode($serverDoctorId)) : '');
@endphp

<script>
  const PATH_PREFIX = @json($pathPrefix ? "/$pathPrefix" : "");
  const SOCKET_URL = @json($socketUrl);
  const fromServer   = Number(@json($serverDoctorId ?? null)) || null;

  const fromQuery = (()=> {
    const u = new URL(location.href);
    const v = u.searchParams.get('doctorId');
    return v ? Number(v) : null;
  })();

  function readAuthFull(){
    try{
      const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full');
      if(!raw) return null;
      return JSON.parse(raw);
    }catch(_){ return null; }
  }
  const af = readAuthFull();
  const fromStorage = (()=> {
    if(!af) return null;
    const id1 = af.user_id;
    const id2 = af.user && af.user.id;
    return Number(id1 || id2) || null;
  })();
  const DOCTOR_ID = fromServer || fromQuery || fromStorage || 501;
</script>

<div class="flex h-full">
  {{-- Sidebar --}}
  <aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
      <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    </div>

<nav class="px-3 py-4 space-y-1">
  <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>

  <a href="{{ $aiChatUrl }}"
     class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
    <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v7a2 2 0 01-2 2h-4l-5 4v-4z"/>
    </svg>
    <span class="text-sm font-medium">AI Chat</span>
  </a>

  <a href="{{ $thisPageUrl }}"
     class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
    <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
    </svg>
    <span class="text-sm font-medium">Video Consultation</span>
  </a>

  {{-- NEW: Services (hardcoded URL) --}}
  <a href="http://snoutiq.com/backend/dashboard/services"
     class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
    <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 6h6v6H4V6zm0 8h6v6H4v-6zm8-8h6v6h-6V6zm0 8h6v6h-6v-6z"/>
    </svg>
    <span class="text-sm font-medium">Services</span>
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
        <button id="toggle-diag" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-800">
          Diagnostics
        </button>

        <button id="btn-add-service" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">
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

{{-- Simple toast container (optional) --}}
<div id="toast-wrap" class="fixed top-4 right-4 z-[80] space-y-2 pointer-events-none"></div>

<!-- ============================= -->
<!-- Add Service Modal (Category removed) -->
<!-- ============================= -->
<div id="add-service-modal" class="fixed inset-0 z-[70] bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[96%] max-w-4xl p-6 relative">
    <button type="button" id="svc-close"
            class="absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">
      ✕
    </button>

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
  // ===== Socket small UI (unchanged essentials) =====
  let incomingCalls=[]; let activeModalCall=null; let isOnline=false; let connectionStatus='connecting'; let debugLogs=[];
  const elSocketId=document.getElementById('socket-id');
  const elConnStatus=document.getElementById('conn-status');
  const elSockConnected=document.getElementById('socket-connected');
  const elIsOnline=document.getElementById('is-online');
  const elStatusPill=document.getElementById('status-pill');
  const elStatusDot=document.getElementById('status-dot');
  const elCallsWrap=document.getElementById('calls');
  const elCallsCount=document.getElementById('calls-count');
  const elNoCalls=document.getElementById('no-calls');
  const elNoCallsSub=document.getElementById('no-calls-sub');
  const elLogs=document.getElementById('logs');
  const btnRejoin=document.getElementById('btn-rejoin');
  const btnTest=document.getElementById('btn-test');
  const btnClear=document.getElementById('btn-clear');
  const btnToggleDiag=document.getElementById('toggle-diag');
  const diagPanel=document.getElementById('diagnostics');
  const modal=document.getElementById('incoming-modal');
  const mPatient=document.getElementById('m-patient');
  const mChannel=document.getElementById('m-channel');
  const mTime=document.getElementById('m-time');
  const mAccept=document.getElementById('m-accept');
  const mReject=document.getElementById('m-reject');
  document.getElementById('doctor-id').textContent = String(DOCTOR_ID);

  btnToggleDiag.addEventListener('click',()=>diagPanel.classList.toggle('hidden'));
  function addLog(m){ const ts=new Date().toLocaleTimeString(); const line=`${ts}: ${m}`; console.log(line); debugLogs=[...debugLogs.slice(-99),line]; if(elLogs){ elLogs.innerHTML=debugLogs.map(l=>`<div>${l}</div>`).join(''); elLogs.parentElement.scrollTop=elLogs.parentElement.scrollHeight; } }

  // ===== Add Service form logic =====
  (function(){
    const $ = s => document.querySelector(s);
    const API_POST_SVC = 'backend/api/groomer/service'; // API prefix used

    const els = {
      openBtn: $('#btn-add-service'),
      modal:   $('#add-service-modal'),
      close:   $('#svc-close'),
      cancel:  $('#svc-cancel'),
      form:    $('#svc-form'),
      submit:  $('#svc-submit'),
      name:    $('#svc-name'),
      duration:$('#svc-duration'),
      price:   $('#svc-price'),
      petType: $('#svc-pet-type'),
      main:    $('#svc-main'),
      notes:   $('#svc-notes'),
      toastWrap: $('#toast-wrap'),
    };

    function show(el){ el.classList.remove('hidden'); }
    function hide(el){ el.classList.add('hidden'); }
    function loading(btn, on){
      if(!btn) return;
      btn.disabled = !!on;
      btn.classList.toggle('opacity-60', !!on);
      if(on){ btn.dataset.oldText = btn.textContent; btn.textContent = 'Saving...'; }
      else if(btn.dataset.oldText){ btn.textContent = btn.dataset.oldText; delete btn.dataset.oldText; }
    }
    function bearer(){
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      return token ? { 'Authorization': 'Bearer ' + token } : {};
    }
    async function fetchJSON(url, opts={}){
      const res = await fetch(url, opts);
      const ct = res.headers.get('content-type') || '';
      const body = ct.includes('application/json') ? await res.json() : await res.text();
      if(!res.ok) throw {status: res.status, body};
      return body;
    }

    async function createService(e){
      e.preventDefault();
      const name     = els.name.value.trim();
      const duration = Number(els.duration.value);
      const price    = Number(els.price.value);
      const petType  = els.petType.value;
      const main     = els.main.value;
      const notes    = els.notes.value.trim();

      if(!name || !duration || !price || !petType || !main){
        Swal.fire({icon:'warning', title:'Missing fields', text:'Please fill all required fields.'});
        return;
      }

      loading(els.submit, true);
      try{
        const fd = new FormData();
        fd.append('serviceName', name);
        fd.append('description', notes);
        fd.append('petType', petType);
        fd.append('price', price);
        fd.append('duration', duration);
        fd.append('main_service', main);
        fd.append('status', 'Active');

        await fetchJSON(API_POST_SVC, { method:'POST', headers:{ ...bearer() }, body: fd });

        // Put SweetAlert payload for the NEXT page
        sessionStorage.setItem('swalAfterRedirect', JSON.stringify({
          icon: 'success',
          title: 'Service Created',
          text: 'Your service has been created successfully.',
          timer: 2200
        }));

        // Optional: small instant success toast here too
        // Swal.fire({icon:'success', title:'Saving...', timer:700, showConfirmButton:false});

        // Redirect
        const to = (PATH_PREFIX || '') + '/backend/doctor';
        window.location.href = to;

      }catch(err){
        const msg = (err && err.body && err.body.message) ? err.body.message
                  : (err && err.body) ? (typeof err.body === 'string' ? err.body : JSON.stringify(err.body))
                  : 'Error creating service';
        Swal.fire({icon:'error', title:'Failed to create', text: msg});
      }finally{
        loading(els.submit, false);
      }
    }

    // Open on load
    document.addEventListener('DOMContentLoaded', ()=> { show(els.modal); });

    // Bindings
    els.form.addEventListener('submit', createService);
    els.close.addEventListener('click', ()=>hide(els.modal));
    els.cancel.addEventListener('click', ()=>hide(els.modal));
    els.openBtn && els.openBtn.addEventListener('click', ()=>show(els.modal));
  })();

  // ===== Optional: show SweetAlert if something is stored for this page too (global) =====
  document.addEventListener('DOMContentLoaded', () => {
    const key = 'swalAfterRedirect';
    const raw = sessionStorage.getItem(key);
    if(raw){
      try{
        const data = JSON.parse(raw);
        Swal.fire({
          icon: data.icon || 'success',
          title: data.title || 'Success',
          text: data.text || '',
          timer: data.timer || 0,
          showConfirmButton: !(data.timer>0)
        });
      }catch(_){}
      finally{
        sessionStorage.removeItem(key);
      }
    }
  });
</script>

</body>
</html>
