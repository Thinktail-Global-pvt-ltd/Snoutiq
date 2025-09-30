{{-- resources/views/groomer/services/index.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Services</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  {{-- Doctor dashboard had socket.io too; keeping it for identical header layout (status pill/dot) --}}
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
</head>
<body class="h-screen bg-gray-50">

@php
  // ===== Same server vars used in doctor dashboard for nav links =====
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
  $socketUrl  = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));

  $serverCandidate = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? optional(auth()->user())->id
        ?? request('doctorId');
  $serverDoctorId = $serverCandidate ? (int)$serverCandidate : null;

  $aiChatUrl   = ($pathPrefix ? "/$pathPrefix" : '') . '/pet-dashboard';
  $thisPageUrl = ($pathPrefix ? "/$pathPrefix" : '') . '/doctor' . ($serverDoctorId ? ('?doctorId=' . urlencode($serverDoctorId)) : '');

  // current services page (for "You are here" if needed later)
  $servicesPageUrl = ($pathPrefix ? "/$pathPrefix" : '') . '/dashboard/services';
@endphp

<script>
  // ===== Keep same globals as doctor dashboard =====
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
  {{-- ===== Sidebar (exactly like doctor dashboard) ===== --}}
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

  {{-- ===== Main (header same structure as doctor dashboard) ===== --}}
  <main class="flex-1 flex flex-col">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Services</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connecting…"></span>
        <span id="status-pill" class="hidden px-3 py-1 rounded-full text-xs font-bold">…</span>
      </div>

      <div class="flex items-center gap-3">
        <button id="toggle-diag"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-800">
          Diagnostics
        </button>

        <button id="btn-open-create" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">
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

    {{-- ===== Page Content (YOUR LISTING UI) ===== --}}
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

{{-- ===== Create Modal ===== --}}
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

{{-- ===== Edit Modal ===== --}}
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

<script>
  // ===== Tiny header init (keep IDs consistent with doctor header) =====
  document.addEventListener('DOMContentLoaded', ()=>{
    const el = document.getElementById('doctor-id');
    if(el) el.textContent = String(DOCTOR_ID);
  });

  // ===== Services LICRUD (your existing script, unchanged) =====
  const API = {
    list:   'backend/api/groomer/services',
    create: 'backend/api/groomer/service',
    show:   id => `backend/api/groomer/service/${id}`,
    update: id => `backend/api/groomer/service/${id}/update`,
    delete: id => `backend/api/groomer/service/${id}`
  };
  const token = localStorage.getItem('token') || sessionStorage.getItem('token');
  const authHeaders = token ? { 'Authorization': 'Bearer ' + token } : {};
  const $ = (s, r=document)=> r.querySelector(s);
  const $$ = (s, r=document)=> Array.from(r.querySelectorAll(s));
  const rows = $('#rows');
  const empty = $('#empty');
  const search = $('#search');

  const createModal = $('#create-modal');
  const editModal   = $('#edit-modal');

  const open = el => el.classList.remove('hidden');
  const close = el => el.classList.add('hidden');

  // ===== List + Render =====
  let ALL = [];
  async function fetchServices(){
    rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-gray-500" colspan="7">Loading…</td></tr>`;
    try{
      const res = await fetch(API.list, { headers: { ...authHeaders, 'Accept':'application/json' }});
      const data = await res.json();
      const items = Array.isArray(data) ? data
                  : Array.isArray(data?.data) ? data.data
                  : [];
      ALL = items;
      render(ALL);
    }catch(e){
      rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-rose-600" colspan="7">Failed to load</td></tr>`;
    }
  }

  function render(list){
    rows.innerHTML = '';
    if(!list.length){
      empty.classList.remove('hidden');
      return;
    }
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

  function esc(s){ return (''+ (s ?? '')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

  // ===== Search =====
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
    const fd = new FormData(e.target);
    const payload = new FormData();
    payload.append('serviceName',  fd.get('serviceName'));
    payload.append('description',  fd.get('description') || '');
    payload.append('petType',      fd.get('petType'));
    payload.append('price',        fd.get('price'));
    payload.append('duration',     fd.get('duration'));
    payload.append('main_service', fd.get('main_service'));
    payload.append('status',       fd.get('status'));

    try{
      const res = await fetch(API.create, { method:'POST', headers:{ ...authHeaders }, body: payload });
      const data = await res.json();
      if(!res.ok) throw new Error(data?.message || 'Failed');
      sessionStorage.setItem('swalAfterRedirect', JSON.stringify({
        icon:'success', title:'Service Created', text:'Service was created successfully', timer:2000
      }));
      close(createModal);
      await fetchServices();
      showSwalFromSession();
    }catch(err){
      Swal.fire({icon:'error', title:'Create failed', text: err.message || 'Error'});
    }
  });

  // ===== Edit/Delete =====
  rows.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-act]');
    if(!btn) return;
    const {act, id} = btn.dataset;

    if(act==='edit'){
      try{
        const res = await fetch(API.show(id), { headers:{ ...authHeaders, 'Accept':'application/json' }});
        const data = await res.json();
        const s = data?.data || data;
        fillEdit(s);
        open(editModal);
      }catch(_){
        Swal.fire({icon:'error', title:'Failed to load service'});
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
        const res = await fetch(API.delete(id), { method:'DELETE', headers:{ ...authHeaders }});
        const data = await res.json();
        if(!res.ok) throw new Error(data?.message || 'Delete failed');
        Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
        await fetchServices();
      }catch(err){
        Swal.fire({icon:'error', title:'Delete failed', text: err.message || 'Error'});
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

    try{
      const res = await fetch(API.update(id), { method:'POST', headers:{ ...authHeaders }, body: payload });
      const data = await res.json();
      if(!res.ok) throw new Error(data?.message || 'Update failed');
      Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false});
      close(editModal);
      await fetchServices();
    }catch(err){
      Swal.fire({icon:'error', title:'Update failed', text: err.message || 'Error'});
    }
  });

  // ===== SweetAlert after redirect helper =====
  function showSwalFromSession(){
    const key = 'swalAfterRedirect';
    const raw = sessionStorage.getItem(key);
    if(raw){
      try{
        const d = JSON.parse(raw);
        Swal.fire({
          icon: d.icon || 'success',
          title: d.title || 'Success',
          text: d.text || '',
          timer: d.timer || 0,
          showConfirmButton: !(d.timer>0)
        });
      }catch(_){}
      sessionStorage.removeItem(key);
    }
  }

  // init
  document.addEventListener('DOMContentLoaded', ()=>{
    fetchServices();
    showSwalFromSession();
  });
</script>

</body>
</html>
