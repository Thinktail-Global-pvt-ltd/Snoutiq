{{-- resources/views/orders/index.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','My Orders')
@section('page_title','My Orders')

@section('content')
<div class="max-w-5xl mx-auto">
  <div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="flex items-center justify-between">
      <div class="text-sm text-gray-600">Your recent bookings</div>
      <div class="flex items-center gap-2 text-sm">
        <button id="tabUpcoming"  class="px-3 py-1.5 rounded border border-gray-300 bg-gray-50">Upcoming</button>
        <button id="tabCompleted" class="px-3 py-1.5 rounded border border-gray-200">Completed</button>
      </div>
    </div>
  </div>

  <div id="list" class="space-y-3"></div>
  <div id="empty" class="hidden text-sm text-gray-500">No orders to display.</div>
</div>
@endsection

@section('scripts')
<script>
  // ---------- Smart apiBase (localhost vs production with /backend) ----------
  const ORIGIN   = window.location.origin; // http://127.0.0.1:8000 or https://snoutiq.com
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  // --------------------------------------------------------------------------

  // Session user (Laravel session)
  const SESSION_USER_ID = Number(@json(session('user_id') ?? data_get(session('user'), 'id') ?? null)) || null;

  // Try to resolve current user id (session first, else localStorage blobs used elsewhere in app)
  function resolveUserId(){
    if (SESSION_USER_ID) return SESSION_USER_ID;
    try {
      const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full') || localStorage.getItem('sn_session_v1');
      if (raw) {
        const obj = JSON.parse(raw);
        const stored = Number(obj?.user?.id ?? obj?.user_id ?? obj?.user?.user_id ?? NaN);
        if (!Number.isNaN(stored) && stored) return stored;
      }
    } catch(_){}
    // last fallback (some flows store just user_id)
    try {
      const uid = Number(localStorage.getItem('user_id') || sessionStorage.getItem('user_id') || NaN);
      if (!Number.isNaN(uid) && uid) return uid;
    } catch(_){}
    return null;
  }

  // read bearer token if available
  function readAuth(){
    try{
      const raw = localStorage.getItem('sn_session_v1') || sessionStorage.getItem('sn_session_v1');
      if(raw){ const s=JSON.parse(raw); return { token:s.token, type:s.token_type||'Bearer' }; }
    }catch(_){ }
    try{
      const token = localStorage.getItem('token');
      const token_type = localStorage.getItem('token_type') || 'Bearer';
      if(token) return { token, type: token_type };
    }catch(_){ }
    return { token:null, type:'Bearer' };
  }

  async function api(url, opts={}){
    const { token, type } = readAuth();
    const headers = Object.assign({ 'Accept':'application/json' }, opts.headers||{});
    if (token) headers['Authorization'] = `${type} ${token}`;
    if (!token && SESSION_USER_ID) headers['X-Session-User'] = String(SESSION_USER_ID);
    const res = await fetch(url, Object.assign({}, opts, { headers }));
    const text = await res.text();
    let j=null; try { j = JSON.parse(text.replace(/^\uFEFF/, '')); } catch(_) {}
    return { ok: res.ok, status: res.status, json: j, raw: text };
  }

  const list  = document.getElementById('list');
  const empty = document.getElementById('empty');
  const tabBtns = {
    Upcoming:  document.getElementById('tabUpcoming'),
    Completed: document.getElementById('tabCompleted'),
  };
  let ALL=[]; let CURRENT='Upcoming';

  function skeleton(n=6){
    list.innerHTML = Array.from({length:n}).map(()=>`
      <div class="bg-white rounded-xl border border-gray-200 p-4 animate-pulse">
        <div class="h-4 w-2/3 bg-gray-200 rounded mb-2"></div>
        <div class="h-3 w-1/3 bg-gray-100 rounded"></div>
      </div>
    `).join('');
    empty.classList.add('hidden');
  }

  function statusPillCls(status){
    if (String(status).toLowerCase()==='completed') return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    if (['failed','rejected'].includes(String(status).toLowerCase())) return 'text-red-700 bg-red-50 border-red-200';
    return 'text-indigo-700 bg-indigo-50 border-indigo-200';
  }

  function niceTime(t){ return t ? String(t).slice(0,5) : ''; }
  function titleCase(s){ return (s||'').replace(/_/g,' ').replace(/\b\w/g, c=>c.toUpperCase()); }

  function card(o){
    return `
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div class="font-semibold">${o.clinic || 'Clinic'} · ${o.doctorName || 'Doctor'}</div>
          <span class="text-xs px-2 py-0.5 rounded-full border ${statusPillCls(o.status)}">${titleCase(o.status||'pending')}</span>
        </div>
        <div class="mt-1 text-sm text-gray-600">${o.date || ''} · ${o.time || ''} · ${titleCase(o.serviceType || '')}</div>
        ${o.price!=null ? `<div class="mt-1 text-xs text-gray-500">Price: ₹${o.price}</div>` : ``}
      </div>`;
  }

  function render(){
    const rows = ALL.filter(x => x.tab===CURRENT);
    if(!rows.length){ list.innerHTML=''; empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');
    list.innerHTML = rows.map(card).join('');
  }

  function setTab(name){
    CURRENT = name;
    Object.keys(tabBtns).forEach(k=>{
      tabBtns[k].className = 'px-3 py-1.5 rounded border ' + (k===name ? 'border-gray-300 bg-gray-50' : 'border-gray-200');
    });
    render();
  }

  function mapOrder(row){
    const status = String(row.status||'pending').toLowerCase();
    let tab = 'Upcoming';
    // If scheduled time is in the past, show under Completed
    const when = row.scheduled_for || row.booking_created_at || null;
    let isPast = false;
    try { if (when) isPast = new Date(when.replace(' ', 'T')) < new Date(); } catch(_){ }
    if (status === 'completed' || isPast) tab = 'Completed';
    return {
      id: row.id,
      tab,
      status: row.status,
      clinic: row.clinic_name || `Clinic #${row.clinic_id ?? ''}`,
      doctorName: row.doctor_name || (row.doctor_id ? `Doctor #${row.doctor_id}` : '—'),
      date: row.scheduled_date || (row.scheduled_for ? String(row.scheduled_for).slice(0,10) : ''),
      time: row.scheduled_time ? niceTime(row.scheduled_time) : (row.scheduled_for ? niceTime(String(row.scheduled_for).slice(11,16)) : ''),
      serviceType: row.service_type,
      urgency: row.urgency,
      price: row.final_price,
    };
  }

  async function load(){
    skeleton();
    const userId = resolveUserId();
    if(!userId){
      list.innerHTML = '<div class="text-sm text-red-600">Login required (user not detected).</div>';
      return;
    }

    // You can add filters here (e.g., ?limit=100&since=2024-01-01)
    const url = `${apiBase}/users/${encodeURIComponent(userId)}/orders?limit=100`;
    const res = await api(url);
    console.log('[orders] GET', url, '=>', res);

    if(!res.ok){
      list.innerHTML = `<div class="text-sm text-red-600">Failed to load (${res.status}).</div>`;
      return;
    }

    const orders = Array.isArray(res.json?.orders) ? res.json.orders : [];
    ALL = orders.map(mapOrder);
    setTab('Upcoming');
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    Object.entries(tabBtns).forEach(([k,btn])=> btn.addEventListener('click', ()=> setTab(k)));
    load();
  });
</script>
@endsection
