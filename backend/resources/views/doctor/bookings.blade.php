{{-- resources/views/doctor/bookings.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','My Bookings')
@section('page_title','My Bookings')
@section('content')
  @php
    $debug = request()->query('debug') === '1';
    // Restrict doctors list to the logged-in vet (session user)
    // Fall back to existing clinic session keys if needed
    $resolvedClinicId = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? session('vet_registerations_temp_id')
        ?? session('vet_registeration_id')
        ?? session('vet_id');
    $resolvedClinic = $resolvedClinicId ? \App\Models\VetRegisterationTemp::find($resolvedClinicId) : null;
  @endphp
  <div class="max-w-5xl mx-auto bg-white rounded-xl shadow p-4">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 flex-1">
        <div id="rowDoctorId" style="{{ $debug ? '' : 'display:none' }}">
          <label class="block text-sm font-medium text-gray-700">Doctor ID</label>
          <input id="doctor_id" type="number" class="mt-1 w-full rounded border-gray-300" placeholder="e.g. 1">
        </div>
        <div class="md:col-span-{{ $debug ? '1' : '2' }}">
          <label class="block text-sm font-medium text-gray-700">Doctor</label>
          <select id="doctor_select" class="mt-1 w-full rounded border-gray-300">
            <option value="">-- Select Doctor --</option>
          </select>
          <div id="doctor_meta" class="text-xs text-gray-500 mt-1">Select a doctor to view their bookings.</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Since</label>
          <input id="since" type="date" value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded border-gray-300">
        </div>
        <div class="flex items-end">
          <button id="btnLoad" class="px-4 py-2 rounded bg-indigo-600 text-white">Load</button>
        </div>
      </div>
      <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 text-xs text-indigo-700 md:max-w-xs">
        @if($resolvedClinic)
          <div class="font-semibold text-indigo-900">Clinic</div>
          <div>{{ $resolvedClinic->name ?? 'Clinic' }} <span class="text-indigo-500">·</span> <span class="font-mono">#{{ $resolvedClinicId }}</span></div>
          <div class="mt-1">Doctors shown below belong to this clinic.</div>
        @elseif($resolvedClinicId)
          <div class="font-semibold text-indigo-900">Clinic ID</div>
          <div class="font-mono">#{{ $resolvedClinicId }}</div>
          <div class="mt-1">Doctors shown below belong to this clinic.</div>
        @else
          <div class="font-semibold text-amber-700">Clinic not detected</div>
          <div class="text-amber-600">Sign in as a clinic to load its doctors.</div>
        @endif
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
      <div class="md:col-span-4 flex items-center justify-between">
        <div id="doctor_badge" class="text-sm text-gray-600">No doctor selected.</div>
        <div class="text-xs text-gray-500">Clinic ID: <span id="clinic_badge">{{ $resolvedClinicId ? '#'.$resolvedClinicId : '—' }}</span></div>
      </div>
    </div>
    <div id="calendar" class="mb-4">
      <div class="grid grid-cols-7 text-xs font-semibold text-gray-600">
        <div class="p-2">Sun</div><div class="p-2">Mon</div><div class="p-2">Tue</div><div class="p-2">Wed</div><div class="p-2">Thu</div><div class="p-2">Fri</div><div class="p-2">Sat</div>
      </div>
      <div id="calRows" class="grid grid-cols-7 gap-px bg-gray-200 rounded overflow-hidden"></div>
    </div>
    <div id="list" class="divide-y hidden"></div>
  </div>
@endsection

@section('scripts')
<script>
  // ---------- Smart base detection (localhost & production, with/without /backend) ----------
  const ORIGIN          = window.location.origin;                 // http://127.0.0.1:8000, https://snoutiq.com
  const PATHNAME        = window.location.pathname;               // current path
  const ON_BACKEND_PATH = PATHNAME.startsWith('/backend');
  const IS_LOCAL        = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);

  // Prefix internal page links when app is served under /backend
  const appBasePath = ON_BACKEND_PATH ? '/backend' : '';

  // API base:
  // - Local:      {origin}/api
  // - Production: {origin}/backend/api
  const apiBase = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  // ------------------------------------------------------------------------------------------

  // tiny fetch helper (same as other pages)
  async function api(url){
    const res  = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const text = await res.text();
    let j = null; try { j = JSON.parse(text.replace(/^\uFEFF/, '')); } catch {}
    return { ok: res.ok, status: res.status, json: j, raw: text };
  }

  // Session-derived IDs
  const SESSION_DOCTOR_ID = Number(@json(session('user_id') ?? data_get(session('user'), 'id') ?? null)) || null;
  const SESSION_USER_ID   = Number(@json(session('user_id') ?? data_get(session('user'), 'id') ?? null)) || null;
  const SESSION_CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;
  const calendarEl = document.getElementById('calendar');
  const calRows = document.getElementById('calRows');
  // panel used for legacy list/details rendering
  const list = document.getElementById('list');
  const doctorSelect = document.getElementById('doctor_select');
  const doctorMeta = document.getElementById('doctor_meta');
  const doctorBadge = document.getElementById('doctor_badge');
  const debugEnabled = new URLSearchParams(location.search).get('debug') === '1';
  const doctorInput = document.getElementById('doctor_id');

  const esc = (value) => String(value ?? '').replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
  const formatSymptoms = (value) => {
    if (!value) return '';
    if (Array.isArray(value)) return value.join(', ');
    if (typeof value === 'string') {
      try { const parsed = JSON.parse(value); if (Array.isArray(parsed)) return parsed.join(', '); } catch (_) {}
      return value;
    }
    return String(value);
  };

  // Final vet id resolver (prefer clinic id, fallback to session user id, then storage, then /api/session/get)
  let FINAL_VET_ID = SESSION_CLINIC_ID || SESSION_USER_ID || null;

  async function bootResolveVetId(){
    if (FINAL_VET_ID) return FINAL_VET_ID;
    try {
      const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full') || localStorage.getItem('sn_session_v1');
      if (raw) {
        const obj = JSON.parse(raw);
        const candidate = Number(obj?.user?.id ?? obj?.user_id ?? obj?.vet_id ?? obj?.clinic_id ?? NaN);
        if (!Number.isNaN(candidate) && candidate) {
          FINAL_VET_ID = candidate;
        }
      }
    } catch(_){}
    if (!FINAL_VET_ID) {
      try {
        const r = await api(`${apiBase}/session/get`);
        const id = Number(r?.json?.user_id ?? r?.json?.user?.id ?? NaN);
        if (!Number.isNaN(id) && id) FINAL_VET_ID = id;
      } catch(_){}
    }
    return FINAL_VET_ID;
  }

  function doctorApiUrl(){
    const params = new URLSearchParams();
    if (FINAL_VET_ID) {
      params.set('vet_id', FINAL_VET_ID);
    }
    const query = params.toString();
    return `${apiBase}/doctors${query ? `?${query}` : ''}`;
  }

  function updateDoctorBadge(){
    if(!doctorBadge) return;
    const selected = doctorSelect?.selectedOptions?.[0];
    if(selected && selected.value){
      const label = selected.textContent.trim();
      doctorBadge.innerHTML = `<span class="text-gray-800 font-medium">${esc(label)}</span> <span class="text-gray-500">(ID: <span class="font-mono">#${esc(selected.value)}</span>)</span>`;
    } else {
      doctorBadge.textContent = 'No doctor selected.';
    }
  }

  async function loadDoctorDropdown(){
    try{
      if(doctorMeta){
        doctorMeta.textContent = FINAL_VET_ID
          ? 'Loading doctors for this clinic…'
          : 'Loading available doctors…';
      }
      const res = await api(doctorApiUrl());
      console.log('[doctor-bookings] GET /doctors =>', res);
      if(!res.ok){
        if(doctorMeta){ doctorMeta.textContent = 'Unable to load doctors.'; }
        return;
      }
      const doctors = Array.isArray(res.json?.doctors) ? res.json.doctors : [];
      if(!doctors.length){
        if(doctorMeta){
          doctorMeta.textContent = FINAL_VET_ID
            ? 'No doctors found for this clinic.'
            : 'No doctors found.';
        }
        if(doctorSelect){
          doctorSelect.innerHTML = '<option value="">-- No doctors available --</option>';
        }
        if(doctorInput){ doctorInput.value = ''; }
        updateDoctorBadge();
        return;
      }
      const options = doctors
        .map(d => ({ id: String(d.id), label: d.doctor_name ? `${d.doctor_name} (#${d.id})` : `Doctor #${d.id}` }))
        .sort((a,b) => a.label.localeCompare(b.label, undefined, { sensitivity: 'base' }));
      console.log('[doctor-bookings] doctors options:', options);
      if(doctorSelect){
        doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>' + options.map(d => `<option value="${d.id}">${d.label}</option>`).join('');
        // Keep input/select in a valid state: if SESSION_DOCTOR_ID doesn't exist, fall back to first
        const match = doctorInput?.value ? options.find(opt => opt.id === String(doctorInput.value)) : null;
        if(match){
          doctorSelect.value = match.id;
        } else if(options.length){
          doctorSelect.value = options[0].id;
          if(doctorInput) doctorInput.value = options[0].id;
        }
        updateDoctorBadge();
      }
      // Auto-load after dropdown is populated and a valid doctor is selected
      if(doctorInput?.value){
        await load();
      }
      if(doctorMeta){ doctorMeta.textContent = 'Select a doctor to view their bookings.'; }
    }catch(_){ }
  }

  function monthStart(dateStr){ const d=new Date(dateStr); d.setDate(1); d.setHours(0,0,0,0); return d; }
  function fmtDate(d){ const y=d.getFullYear(); const m=String(d.getMonth()+1).padStart(2,'0'); const day=String(d.getDate()).padStart(2,'0'); return `${y}-${m}-${day}`; }
  function parseWhen(b){ const t=b.scheduled_for || b.booking_created_at; return t ? new Date(t.replace(' ','T')) : null; }

  function renderCalendar(rows){
    const byDay = new Map();
    rows.forEach(b=>{ const dt=parseWhen(b); if(!dt) return; const key=fmtDate(dt); if(!byDay.has(key)) byDay.set(key,[]); byDay.get(key).push(b); });
    const since = document.getElementById('since').value || new Date().toISOString().slice(0,10);
    const first = monthStart(since);
    const startCell = new Date(first); startCell.setDate(first.getDate() - first.getDay());
    const totalCells = 42;
    let html='';
    for(let i=0;i<totalCells;i++){
      const d=new Date(startCell); d.setDate(startCell.getDate()+i);
      const key=fmtDate(d);
      const inMonth = d.getMonth()===first.getMonth();
      const items = (byDay.get(key)||[]);
      html += `
        <div class="bg-white ${inMonth?'':'bg-gray-50'} p-2 min-h-[120px]">
          <div class="text-xs ${inMonth?'text-gray-900':'text-gray-400'} font-semibold mb-1">${d.getDate()}</div>
          <div class="space-y-1">
            ${items.map(b=>`
              <button type="button" data-id="${b.id}" class="cal-ev w-full text-left px-2 py-1 rounded border ${b.status==='completed'?'border-emerald-300 bg-emerald-50 text-emerald-800':'border-indigo-300 bg-indigo-50 text-indigo-800'} hover:shadow">
                <div class="text-[11px] font-semibold">#${b.id} · ${(b.service_type||'').replace('_',' ')}</div>
                <div class="text-[10px] opacity-80">${(b.scheduled_for||'').slice(11,16) || (b.booking_created_at||'').slice(11,16)}</div>
              </button>
            `).join('')}
          </div>
        </div>`;
    }
    document.getElementById('calRows').innerHTML = html;
  }

  async function load(){
    if(!doctorInput || !doctorInput.value){ alert('Doctor ID missing'); return; }
    const since = document.getElementById('since').value;
    const url = `${apiBase}/doctors/${encodeURIComponent(doctorInput.value)}/bookings` + (since? `?since=${encodeURIComponent(since)}` : '');
    console.log('[doctor-bookings] fetching:', url);
    const res = await api(url);
    console.log('[doctor-bookings] bookings response:', res);
    if(!res.ok){
      if(list){ list.innerHTML = '<div class="text-sm text-red-600">Failed to load</div>'; list.classList.remove('hidden'); }
      return;
    }
    const rows = Array.isArray(res.json?.bookings) ? res.json.bookings : [];
    console.log('[doctor-bookings] normalized bookings:', rows);
    renderCalendar(rows);
  }

  document.getElementById('btnLoad').addEventListener('click', load);

  // Month navigation (if you add prev/next buttons with ids btnPrev/btnNext)
  document.getElementById('btnPrev')?.addEventListener('click', ()=>{
    const since = document.getElementById('since');
    const d = new Date(since.value || new Date()); d.setMonth(d.getMonth()-1); d.setDate(1); since.value=d.toISOString().slice(0,10); load();
  });
  document.getElementById('btnNext')?.addEventListener('click', ()=>{
    const since = document.getElementById('since');
    const d = new Date(since.value || new Date()); d.setMonth(d.getMonth()+1); d.setDate(1); since.value=d.toISOString().slice(0,10); load();
  });

  document.addEventListener('DOMContentLoaded', async ()=>{
    // Prefill doctor from session if present (may not be a real doctor id)
    if(SESSION_DOCTOR_ID && doctorInput){ doctorInput.value = SESSION_DOCTOR_ID; }
    // Resolve vet/clinic id and reflect in badge
    await bootResolveVetId();
    const clinicBadge = document.getElementById('clinic_badge');
    if (clinicBadge) clinicBadge.textContent = FINAL_VET_ID ? `#${FINAL_VET_ID}` : '—';
    // Populate dropdown and auto-load a valid doctor selection
    loadDoctorDropdown();
    // Keep input/select in sync on user interaction
    if(doctorSelect){
      doctorSelect.addEventListener('change', ()=>{
        if(doctorInput) doctorInput.value = doctorSelect.value;
        updateDoctorBadge();
        if(doctorInput?.value){ load(); }
      });
    }
    if(doctorInput && doctorSelect){
      doctorInput.addEventListener('input', ()=>{
        const val = doctorInput.value;
        const opt = Array.from(doctorSelect.options).find(o => o.value === val);
        doctorSelect.value = opt ? opt.value : '';
        updateDoctorBadge();
      });
    }

    // Calendar click -> navigate to dedicated detail page (respect /backend)
    calendarEl.addEventListener('click', (e)=>{
      const ev = e.target.closest('.cal-ev');
      if(!ev) return;
      const id = ev.getAttribute('data-id');
      if(id) window.location.href = `${appBasePath}/doctor/booking/${encodeURIComponent(id)}`;
    });

    // Legacy list “View Details” handler (if any)
    list.addEventListener('click', async (e)=>{
      const btn = e.target.closest('.view-btn'); if(!btn) return;
      const id = btn.getAttribute('data-id');
      const panel = document.getElementById(`det-${id}`);
      if(!panel) return;
      const wasHidden = panel.classList.contains('hidden');
      panel.classList.toggle('hidden');
      if (wasHidden && !panel.getAttribute('data-loaded')) {
        const res = await api(`${apiBase}/bookings/details/${encodeURIComponent(id)}`);
        if(res.ok && res.json?.booking){
          const b = res.json.booking;
          const pretty = (v)=> typeof v === 'object' ? JSON.stringify(v, null, 2) : String(v ?? '');
          panel.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
              <div><div class="text-gray-500">IDs</div><div>Booking #${esc(b.id)} · User #${esc(b.user_id)} · Pet #${esc(b.pet_id)}</div></div>
              <div><div class="text-gray-500">Service</div><div>${esc(b.service_type)} · Urgency: ${esc(b.urgency)}</div></div>
              <div><div class="text-gray-500">Status</div><div>${esc(b.status)}</div></div>
              <div><div class="text-gray-500">Scheduled For</div><div>${esc(b.scheduled_for ?? '')}</div></div>
              <div><div class="text-gray-500">Location</div><div>${esc(b.user_address ?? '')}</div></div>
              <div><div class="text-gray-500">Coords</div><div>${esc(b.user_latitude ?? '')}, ${esc(b.user_longitude ?? '')}</div></div>
              <div class="md:col-span-2"><div class="text-gray-500">Symptoms</div><pre class="whitespace-pre-wrap bg-white border rounded p-2">${pretty(b.symptoms)}</pre></div>
              <div class="md:col-span-2"><div class="text-gray-500">AI Summary</div><pre class="whitespace-pre-wrap bg-white border rounded p-2">${pretty(b.ai_summary)}</pre></div>
            </div>`;
          panel.setAttribute('data-loaded','1');
        } else {
          panel.innerHTML = '<div class="text-xs text-red-600">Failed to fetch details.</div>';
        }
      }
    });
  });
</script>
@endsection
