@extends('layouts.snoutiq-dashboard')

@section('title','Select Clinic')
@section('page_title','Select Clinic')

@section('content')
<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold">Choose a Clinic</h2>
    <div class="flex items-center gap-2">
      <input id="clinicSearch" type="text" placeholder="Search clinics…" class="w-56 rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"/>
      <select id="clinicSort" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
        <option value="name">Sort: Name</option>
        <option value="id">Sort: ID</option>
      </select>
    </div>
  </div>

  <div id="clinicGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"></div>
  <div id="clinicEmpty" class="text-sm text-gray-500">Loading clinics…</div>
</div>
@endsection

@section('scripts')
<script>
  // ---- Smart base detection (works on localhost & production) ----
  const ORIGIN   = window.location.origin;        // e.g., http://127.0.0.1:8000 or https://snoutiq.com
  const PATHNAME = window.location.pathname;      // current path
  const ON_BACKEND_PATH = PATHNAME.startsWith('/backend');
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const IS_PROD_HOST = /snoutiq\.com$/i.test(window.location.hostname);

  // Base path for app routes (so your link to /booking/... also works under /backend)
  const appBasePath = ON_BACKEND_PATH ? '/backend' : '';

  // API base:
  // - If running on snoutiq.com and URL path doesn't already include /backend, point API to /backend/api
  // - If path already under /backend, use /backend/api
  // - On localhost, use the app origin + '/api'
  const apiBase =
    IS_LOCAL
      ? `${ORIGIN}/api`
      : (ON_BACKEND_PATH || IS_PROD_HOST) ? `${ORIGIN}${ON_BACKEND_PATH ? '' : ''}/backend/api` : `${ORIGIN}/api`;

  // ----------------------------------------------------------------

  async function api(url){
    const res  = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const text = await res.text();
    let json = null;
    try { json = JSON.parse(text.replace(/^\uFEFF/, '')); } catch(_) {}
    return { ok: res.ok, status: res.status, json, raw: text };
  }

  const grid     = document.getElementById('clinicGrid');
  const empty    = document.getElementById('clinicEmpty');
  const searchEl = document.getElementById('clinicSearch');
  const sortEl   = document.getElementById('clinicSort');
  let ALL = [];

  function skeleton(n=6){
    grid.innerHTML = Array.from({length:n}).map(()=>`
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm animate-pulse">
        <div class="flex items-center gap-3 mb-3">
          <div class="h-9 w-9 rounded-full bg-gray-200"></div>
          <div class="flex-1">
            <div class="h-3 w-2/3 bg-gray-200 rounded mb-2"></div>
            <div class="h-3 w-1/3 bg-gray-100 rounded"></div>
          </div>
        </div>
        <div class="h-8 w-24 bg-gray-200 rounded ml-auto"></div>
      </div>
    `).join('');
    empty.style.display = 'none';
  }

  function card(c){
    const displayName = c.name ?? c.title ?? c.slug ?? `Clinic #${c.id ?? ''}`;
    const addr = c.address ?? '';
    const initial = (displayName || 'C').trim().charAt(0).toUpperCase();

    return `
      <a href="${appBasePath}/booking/clinic/${c.id}/doctors"
         class="group block bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-lg transition transform hover:-translate-y-0.5">
        <div class="flex items-start gap-3">
          <div class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-700 grid place-items-center font-semibold">${initial}</div>
          <div class="flex-1">
            <div class="text-base font-semibold text-gray-900 group-hover:text-indigo-700">${displayName}</div>
            <div class="text-xs text-gray-500 truncate">${addr}</div>
          </div>
          <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
            ID: ${c.id ?? '-'}
          </span>
        </div>
        <div class="mt-4 flex items-center justify-end">
          <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded bg-indigo-600 text-white text-sm group-hover:bg-indigo-700">
            Select
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </span>
        </div>
      </a>`;
  }

  function render(list){
    if(!list.length){
      grid.innerHTML = '';
      empty.textContent = 'No clinics match your search';
      empty.style.display = 'block';
      return;
    }
    empty.style.display = 'none';
    grid.innerHTML = list.map(card).join('');
  }

  function apply(){
    let list = [...ALL];
    const q = (searchEl.value || '').toLowerCase().trim();
    if(q){
      list = list.filter(c =>
        String(c.name ?? '').toLowerCase().includes(q) ||
        String(c.slug ?? '').toLowerCase().includes(q) ||
        String(c.address ?? '').toLowerCase().includes(q)
      );
    }
    const sortBy = sortEl.value;
    list.sort((a,b)=>{
      if (sortBy === 'id') return (a.id ?? 0) - (b.id ?? 0);
      const an = String(a.name ?? a.slug ?? ''), bn = String(b.name ?? b.slug ?? '');
      return an.localeCompare(bn);
    });
    render(list);
  }

  function extractClinics(payload){
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.clinics)) return payload.clinics;
    if (Array.isArray(payload?.data))    return payload.data;
    if (Array.isArray(payload?.items))   return payload.items;
    if (payload && typeof payload === 'object'){
      for (const k of Object.keys(payload)){
        if (Array.isArray(payload[k])) return payload[k];
      }
    }
    return [];
  }

  async function loadClinics(){
    skeleton();
    const res = await api(`${apiBase}/clinics`);
    console.log('[clinics] GET', `${apiBase}/clinics`, '=>', res);
    if(!res.ok){
      empty.textContent = 'Failed to load clinics';
      empty.style.display = 'block';
      grid.innerHTML = '';
      return;
    }
    ALL = extractClinics(res.json).map(c => ({
      id: c.id ?? c.clinic_id ?? null,
      name: c.name ?? null,
      slug: c.slug ?? null,
      address: c.address ?? c.location ?? ''
    }));
    apply();
  }

  function __initClinics(){
    searchEl?.addEventListener('input', apply);
    sortEl?.addEventListener('change', apply);
    loadClinics();
  }
  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', __initClinics);
  } else {
    __initClinics();
  }
</script>
@endsection
