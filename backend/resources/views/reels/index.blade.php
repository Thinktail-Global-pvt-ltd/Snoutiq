<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Clinic Reels | SnoutIQ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>#client-logger{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
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
      <a href="/backend/pet-dashboard" class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10">
        <span class="w-5 h-5">💬</span><span class="text-sm font-medium">AI Chat</span>
      </a>
      <a href="/backend/dashboard/services" class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10">
        <span class="w-5 h-5">🧩</span><span class="text-sm font-medium">Services</span>
      </a>
      <a href="/backend/dashboard/reels" class="group flex items-center gap-3 px-3 py-2 rounded-lg bg-white/10">
        <span class="w-5 h-5">🎞️</span><span class="text-sm font-medium">Clinic Reels</span>
      </a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="flex-1 flex flex-col">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Clinic Reels</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connecting…"></span>
      </div>
      <div class="flex items-center gap-3">
        <button id="btn-auth" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">🔐 Auth</button>
        <button id="btn-open-create" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">+ Add Reel</button>
      </div>
    </header>

    <!-- Content -->
    <section class="flex-1 p-6">
      <div class="max-w-6xl mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
          <div class="p-3 border-b flex items-center gap-3">
            <input id="search" type="text" placeholder="Search title..." class="w-full md:w-96 bg-gray-100 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 border-0">
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-100 text-gray-700">
                <tr>
                  <th class="text-left px-4 py-3">Title</th>
                  <th class="text-left px-4 py-3">Thumb</th>
                  <th class="text-left px-4 py-3">Video/URL</th>
                  <th class="text-left px-4 py-3">Order</th>
                  <th class="text-left px-4 py-3">Status</th>
                  <th class="text-left px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody id="rows"></tbody>
            </table>
          </div>
          <div id="empty" class="hidden p-8 text-center text-gray-500">No reels found.</div>
        </div>
      </div>
    </section>
  </main>
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[96%] max-w-3xl p-6 relative">
    <button type="button" class="btn-close absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">✕</button>
    <h3 class="text-xl font-semibold text-gray-800 mb-1">Add New Reel</h3>
    <p class="text-sm text-gray-500 mb-4">Upload a reel or paste a URL</p>

    <form id="create-form" class="space-y-4">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Title</label>
          <input name="title" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Status</label>
          <select name="status" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Order</label>
          <input name="order_index" type="number" min="0" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" value="0">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Reel URL (optional)</label>
          <input name="reel_url" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" placeholder="https://…">
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Thumbnail (optional)</label>
          <input name="thumb" type="file" accept="image/*" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Video file (optional)</label>
          <input name="video" type="file" accept="video/*" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Description (optional)</label>
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
    <h3 class="text-xl font-semibold text-gray-800 mb-1">Edit Reel</h3>
    <p class="text-sm text-gray-500 mb-4">Update details</p>

    <form id="edit-form" class="space-y-4">
      <input type="hidden" name="id">
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Title</label>
          <input name="title" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Status</label>
          <select name="status" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Order</label>
          <input name="order_index" type="number" min="0" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Reel URL (optional)</label>
          <input name="reel_url" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" placeholder="https://…">
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Thumbnail (replace)</label>
          <input name="thumb" type="file" accept="image/*" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Video file (replace)</label>
          <input name="video" type="file" accept="video/*" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Description (optional)</label>
        <textarea name="description" rows="3" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" class="btn-close px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Tiny logger + Auth (reused from Services) ===== -->
<div id="client-logger" class="hidden fixed bottom-20 right-4 z-[100] w-[460px] max-h-[72vh] bg-white border border-gray-200 rounded-xl shadow-2xl">
  <div class="flex items-center justify-between px-3 py-2 border-b">
    <div class="text-xs font-bold text-gray-700">Frontend Logger</div>
    <div class="flex items-center gap-2">
      <input id="log-token" placeholder="paste Bearer token…" class="px-2 py-1 rounded bg-gray-100 text-xs w-44">
      <button id="log-token-save" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs">Save</button>
      <button id="log-close" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">✕</button>
    </div>
  </div>
  <div id="log-body" class="text-[11px] leading-4 text-gray-800 px-3 py-2 overflow-y-auto whitespace-pre-wrap"></div>
</div>
<button id="log-toggle" class="fixed bottom-4 right-4 z-[90] px-3 py-2 rounded-full bg-black text-white text-xs shadow-lg">
  🪵 Logs
</button>

<script>
  const CONFIG = {
    API_BASE: 'https://snoutiq.com/backend/api',
    CSRF_URL: 'https://snoutiq.com/backend/sanctum/csrf-cookie'
  };
  const API = {
    list:   `${CONFIG.API_BASE}/groomer/reels`,
    create: `${CONFIG.API_BASE}/groomer/reel`,
    show:   id => `${CONFIG.API_BASE}/groomer/reel/${id}`,
    update: id => `${CONFIG.API_BASE}/groomer/reel/${id}/update`,
    delete: id => `${CONFIG.API_BASE}/groomer/reel/${id}`
  };

  // ---- minimal logger
  const Log = {
    el: document.getElementById('log-body'),
    open(){ document.getElementById('client-logger').classList.remove('hidden'); },
    info(m){ const d=document.createElement('div'); d.textContent=m; this.el.appendChild(d); this.el.scrollTop=this.el.scrollHeight; }
  };
  document.getElementById('log-toggle').onclick = ()=> Log.open();
  document.getElementById('log-close').onclick  = ()=> document.getElementById('client-logger').classList.add('hidden');
  document.getElementById('log-token-save').onclick = ()=>{
    const t = document.getElementById('log-token').value.trim();
    if (t){ localStorage.setItem('token', t); sessionStorage.setItem('token', t); Swal.fire({icon:'success',title:'Token saved',timer:900,showConfirmButton:false}); }
  };

  // ---- Auth helper
  const Auth = {
    async bootstrap(){
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      if (token) return { mode:'bearer' };
      await fetch(CONFIG.CSRF_URL, { credentials:'include' });
      return { mode:'cookie' };
    },
    headers(extra={}){
      const h = { 'Accept':'application/json', ...extra };
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      if (token) h['Authorization'] = 'Bearer ' + token;
      return h;
    }
  };
  document.getElementById('btn-auth').onclick = async ()=>{
    await Auth.bootstrap();
    Swal.fire({icon:'success', title:'Auth ready', timer:900, showConfirmButton:false});
  };

  // ---- Utilities
  const $ = (s, r=document)=> r.querySelector(s);
  const $$ = (s, r=document)=> Array.from(r.querySelectorAll(s));
  const rows = $('#rows'), empty=$('#empty');
  const createModal = $('#create-modal'), editModal=$('#edit-modal');
  const open = el => el.classList.remove('hidden');
  const close= el => el.classList.add('hidden');
  const esc = s => (''+(s??'')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));

  function resolveUserId(){
    // Prefer locally stored auth bundle like your services page
    try{
      const full = JSON.parse(localStorage.getItem('auth_full') || sessionStorage.getItem('auth_full') || 'null');
      const id = full?.user?.id ?? full?.user_id;
      if (id) return String(id);
    }catch(_){}
    // Fallback to your server-provided session id (if any) could be embedded into page too
    return '';
  }
  const USER_ID = resolveUserId();

  async function apiFetch(url, opts={}, expectJSON=true){
    const res = await fetch(url, { credentials:'include', ...opts });
    const ct = res.headers.get('content-type') || '';
    const body = (expectJSON && ct.includes('application/json')) ? await res.json() : await res.text();
    if (!res.ok){
      const msg = (body && body.message) ? body.message : `HTTP ${res.status}`;
      const err = new Error(msg); err.status=res.status; err.body=body; throw err;
    }
    return body;
  }

  // ---- List & Render
  let ALL=[];
  async function fetchReels(){
    rows.innerHTML = `<tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Loading…</td></tr>`;
    try{
      await Auth.bootstrap();
      const url = new URL(API.list);
      if (USER_ID) url.searchParams.set('user_id', USER_ID);
      const res = await apiFetch(url.toString(), { headers: Auth.headers() });
      const items = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : []);
      ALL = items;
      render(ALL);
    }catch(e){
      rows.innerHTML = `<tr><td colspan="6" class="px-4 py-6 text-center text-rose-600">Failed to load (${esc(e.message||e)})</td></tr>`;
      Log.info('load.failed '+(e.message||e));
    }
  }

  function render(list){
    rows.innerHTML='';
    if(!list.length){ empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');

    for (const it of list){
      const tr=document.createElement('tr');
      tr.className='border-t';
      const media = it.video_path ? `<a class="text-blue-600 underline" href="/${esc(it.video_path)}" target="_blank">video</a>`
                 : (it.reel_url ? `<a class="text-blue-600 underline" href="${esc(it.reel_url)}" target="_blank">link</a>` : '—');
      tr.innerHTML = `
        <td class="px-4 py-3 font-medium">${esc(it.title)}</td>
        <td class="px-4 py-3">${it.thumb_path ? `<img class="h-10 w-16 rounded object-cover border" src="/${esc(it.thumb_path)}">` : '—'}</td>
        <td class="px-4 py-3">${media}</td>
        <td class="px-4 py-3">${it.order_index ?? 0}</td>
        <td class="px-4 py-3">
          <span class="px-2 py-0.5 rounded-full text-xs ${it.status==='Active'?'bg-emerald-100 text-emerald-700':'bg-gray-200 text-gray-700'}">
            ${esc(it.status||'')}
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

  // search
  $('#search').addEventListener('input', e=>{
    const q = e.target.value.toLowerCase().trim();
    const filtered = !q ? ALL : ALL.filter(x => (x.title||'').toLowerCase().includes(q));
    render(filtered);
  });

  // open create
  document.getElementById('btn-open-create').onclick = ()=> open(createModal);
  $$('.btn-close', createModal).forEach(b => b.onclick = ()=> close(createModal));
  $$('.btn-close', editModal).forEach(b => b.onclick = ()=> close(editModal));

  // create submit
  document.getElementById('create-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const f=e.target;
    const payload=new FormData(f);
    if (USER_ID) payload.append('user_id', USER_ID);

    try{
      await Auth.bootstrap();
      await apiFetch(API.create, { method:'POST', headers:Auth.headers(), body:payload });
      Swal.fire({icon:'success', title:'Reel Created', timer:1200, showConfirmButton:false});
      close(createModal); f.reset();
      fetchReels();
    }catch(err){
      Swal.fire({icon:'error', title:'Create failed', text: err.message||'Error'});
    }
  });

  // edit/delete actions
  rows.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-act]');
    if(!btn) return;
    const {act,id} = btn.dataset;

    if (act==='edit'){
      try{
        await Auth.bootstrap();
        const url=new URL(API.show(id)); if (USER_ID) url.searchParams.set('user_id', USER_ID);
        const data = await apiFetch(url.toString(), { headers: Auth.headers() });
        fillEdit(data?.data || data);
        open(editModal);
      }catch(err){
        Swal.fire({icon:'error', title:'Load failed', text: err.message||'Error'});
      }
    }

    if (act==='delete'){
      const ok = await Swal.fire({icon:'warning', title:'Delete this reel?', showCancelButton:true, confirmButtonText:'Yes, delete'});
      if (!ok.isConfirmed) return;
      try{
        await Auth.bootstrap();
        const url=new URL(API.delete(id)); if (USER_ID) url.searchParams.set('user_id', USER_ID);
        await apiFetch(url.toString(), { method:'DELETE', headers: Auth.headers() });
        Swal.fire({icon:'success', title:'Deleted', timer:1000, showConfirmButton:false});
        fetchReels();
      }catch(err){
        // fallback X-HTTP-Method-Override if needed
        try{
          const url=new URL(API.delete(id)); if (USER_ID) url.searchParams.set('user_id', USER_ID);
          await apiFetch(url.toString(), { method:'POST', headers: Auth.headers({'X-HTTP-Method-Override':'DELETE'}) });
          Swal.fire({icon:'success', title:'Deleted', timer:1000, showConfirmButton:false});
          fetchReels();
        }catch(err2){
          Swal.fire({icon:'error', title:'Delete failed', text: err2.message||'Error'});
        }
      }
    }
  });

  function fillEdit(r){
    const f = document.getElementById('edit-form');
    f.elements['id'].value = r.id;
    f.elements['title'].value = r.title || '';
    f.elements['status'].value = r.status || 'Active';
    f.elements['order_index'].value = r.order_index ?? 0;
    f.elements['reel_url'].value = r.reel_url || '';
    f.elements['description'].value = r.description || '';
  }

  document.getElementById('edit-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const f=e.target;
    const id=f.elements['id'].value;
    const payload=new FormData(f);
    if (USER_ID) payload.append('user_id', USER_ID);

    try{
      await Auth.bootstrap();
      await apiFetch(API.update(id), { method:'POST', headers:Auth.headers(), body:payload });
      Swal.fire({icon:'success', title:'Updated', timer:1000, showConfirmButton:false});
      close(editModal);
      fetchReels();
    }catch(err){
      Swal.fire({icon:'error', title:'Update failed', text: err.message||'Error'});
    }
  });

  // init
  document.addEventListener('DOMContentLoaded', fetchReels);
</script>

</body>
</html>
