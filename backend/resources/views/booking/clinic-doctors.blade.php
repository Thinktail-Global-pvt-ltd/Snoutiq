@extends('layouts.snoutiq-dashboard')

@section('title','Select Doctor')
@section('page_title','Select Doctor')

@section('content')
<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <a href="{{ route('booking.clinics') }}" class="text-sm text-indigo-700 hover:underline">&larr; Back to Clinics</a>
    <div class="flex items-center gap-2">
      <input id="docSearch" type="text" placeholder="Search doctors…" class="w-56 rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"/>
      <select id="docSort" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
        <option value="name">Sort: Name</option>
        <option value="id">Sort: ID</option>
      </select>
    </div>
  </div>

  <div id="docGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6"></div>
  <div id="docEmpty" class="text-sm text-gray-500">Loading doctors…</div>
</div>
@endsection

@section('scripts')
<script>
  // ---- Smart base detection (localhost, production, with/without /backend) ----
  const ORIGIN   = window.location.origin;                   // http://127.0.0.1:8000, https://snoutiq.com
  const PATHNAME = window.location.pathname;                 // current path (e.g. /backend/booking/clinic/12/doctors)
  const ON_BACKEND_PATH = PATHNAME.startsWith('/backend');
  const IS_LOCAL   = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const IS_PROD    = /snoutiq\.com$/i.test(window.location.hostname);

  // App base for internal page links
  const appBasePath = ON_BACKEND_PATH ? '/backend' : '';

  // API base:
  // - Local:     {origin}/api
  // - Production: {origin}/backend/api
  // - If already under /backend in the URL, still use /backend/api
  const apiBase = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  // ---------------------------------------------------------------------------

  // Blade-provided clinic id
  const CLINIC_ID = Number(@json($clinicId));

  async function api(url){
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const t   = await res.text();
    let j = null; try { j = JSON.parse(t.replace(/^\uFEFF/, '')); } catch {}
    return { ok: res.ok, status: res.status, json: j, raw: t };
  }

  const grid    = document.getElementById('docGrid');
  const empty   = document.getElementById('docEmpty');
  const searchEl= document.getElementById('docSearch');
  const sortEl  = document.getElementById('docSort');
  let ALL = [];

  function skeleton(count=4){
    grid.innerHTML = Array.from({length:count}).map(()=>`
      <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-md animate-pulse">
        <div class="flex items-center gap-4 mb-4">
          <div class="h-12 w-12 rounded-full bg-gray-200"></div>
          <div class="flex-1">
            <div class="h-3.5 w-2/3 bg-gray-200 rounded mb-2"></div>
            <div class="h-3.5 w-1/3 bg-gray-100 rounded"></div>
          </div>
        </div>
        <div class="h-10 w-28 bg-gray-200 rounded ml-auto"></div>
      </div>
    `).join('');
    empty.style.display = 'none';
  }

  function card(d){
    const name = d.name || ('Doctor #' + d.id);
    const sub  = [d.email, d.phone].filter(Boolean).join(' • ');
    const initial = (name||'D').trim().charAt(0).toUpperCase();
    const href = `${appBasePath}/booking/clinic/${CLINIC_ID}/book?doctorId=${d.id}`;
    return `
      <a href="${href}" class="group block bg-white rounded-2xl border border-gray-200 p-6 shadow-md hover:shadow-xl transition transform hover:-translate-y-0.5">
        <div class="flex items-start gap-4">
          <div class="h-12 w-12 rounded-full bg-indigo-100 text-indigo-700 grid place-items-center font-semibold text-lg">${initial}</div>
          <div class="flex-1">
            <div class="text-lg font-semibold text-gray-900 group-hover:text-indigo-700">${name}</div>
            <div class="text-sm text-gray-500 truncate">${sub}</div>
          </div>
          <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">ID: ${d.id}</span>
        </div>
        <div class="mt-5 flex items-center justify-end">
          <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-base group-hover:bg-indigo-700">
            Select
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </span>
        </div>
      </a>`;
  }

  function render(list){
    if(!list.length){
      grid.innerHTML = '';
      empty.textContent = 'No doctors match your search';
      empty.style.display = 'block';
      return;
    }
    empty.style.display = 'none';
    grid.innerHTML = list.map(card).join('');
  }

  function apply(){
    let list = [...ALL];
    const q = (searchEl.value||'').toLowerCase().trim();
    if(q){
      list = list.filter(d =>
        String(d.name||'').toLowerCase().includes(q) ||
        String(d.email||'').toLowerCase().includes(q) ||
        String(d.phone||'').toLowerCase().includes(q)
      );
    }
    const sortBy = sortEl.value;
    list.sort((a,b)=>{
      if(sortBy==='id') return (a.id||0)-(b.id||0);
      return String(a.name||'').localeCompare(String(b.name||''));
    });
    render(list);
  }

  function extractDoctors(payload){
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.doctors)) return payload.doctors;
    if (Array.isArray(payload?.data))    return payload.data;
    if (payload && typeof payload === 'object'){
      for (const k of Object.keys(payload)){
        if (Array.isArray(payload[k])) return payload[k];
      }
    }
    return [];
  }

  async function loadDoctors(){
    skeleton();
    const url = `${apiBase}/clinics/${CLINIC_ID}/doctors`;
    const res = await api(url);
    console.log('[clinic-doctors] GET', url, '=>', res);
    if(!res.ok){
      empty.textContent = 'Failed to load doctors';
      empty.style.display = 'block';
      grid.innerHTML = '';
      return;
    }
    ALL = extractDoctors(res.json).map(d => ({
      id:    d.id ?? d.doctor_id ?? null,
      name:  d.name ?? d.doctor_name ?? null,
      email: d.email ?? d.doctor_email ?? '',
      phone: d.phone ?? d.doctor_mobile ?? ''
    }));
    apply();
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    searchEl.addEventListener('input', apply);
    sortEl.addEventListener('change', apply);
    loadDoctors();
  });
</script>
@endsection
