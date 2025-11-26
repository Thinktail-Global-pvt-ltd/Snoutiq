{{-- resources/views/doctor/bookings.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','My Bookings')
@section('page_title','My Bookings')
@section('head')
  <style>
    #calendar {
      border-radius: 1.25rem;
      background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
      padding: 1.25rem;
      box-shadow: inset 0 1px 0 rgba(15, 23, 42, 0.04);
    }
    #calendar .weekday-row {
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .calendar-cell {
      min-height: 180px;
      border-radius: 1.25rem;
      border: 1px solid rgba(15,23,42,0.05);
      background: #fff;
      padding: 0.85rem;
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
      box-shadow: 0 12px 24px rgba(15,23,42,0.05);
      transition: border-color .2s ease, box-shadow .2s ease;
    }
    .calendar-cell--muted {
      background: #f8fafc;
      border-style: dashed;
      border-color: rgba(148,163,184,0.35);
      box-shadow: none;
    }
    .calendar-cell__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.75rem;
      color: #475569;
    }
    .calendar-cell__date {
      font-size: 1rem;
      font-weight: 600;
      color: #0f172a;
    }
    .calendar-cell--muted .calendar-cell__date {
      color: #94a3b8;
    }
    .calendar-cell__count {
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.65rem;
      letter-spacing: 0.08em;
    }
    .calendar-cell__notes {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      flex: 1;
    }
    .calendar-note {
      position: relative;
      display: block;
      width: 100%;
      text-align: left;
      border-radius: 1rem;
      padding: 0.55rem 0.75rem 0.75rem;
      background: linear-gradient(135deg, var(--note-start, #fef9c3), var(--note-end, #fde68a));
      box-shadow: 0 12px 18px var(--note-shadow, rgba(120,72,0,0.2));
      border: 1px solid rgba(15,23,42,0.05);
      transform: rotate(var(--note-tilt, 0deg));
      transition: transform .2s ease, box-shadow .2s ease;
      cursor: pointer;
    }
    .calendar-note::after {
      content: '';
      position: absolute;
      width: 22px;
      height: 22px;
      background: rgba(255,255,255,0.45);
      top: 10px;
      right: 18px;
      border-radius: 3px;
      transform: rotate(45deg);
      opacity: 0.7;
    }
    .calendar-note:hover {
      transform: rotate(var(--note-tilt, 0deg)) translateY(-3px);
      box-shadow: 0 16px 28px rgba(15,23,42,0.18);
    }
    .calendar-note__time {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: rgba(15,23,42,0.6);
    }
    .calendar-note__title {
      font-size: 0.95rem;
      font-weight: 600;
      color: #0f172a;
      margin-top: 0.15rem;
    }
    .calendar-note__pet {
      font-size: 0.8rem;
      color: #475569;
    }
    .calendar-note__summary {
      font-size: 0.75rem;
      color: #334155;
      margin-top: 0.15rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .calendar-note__meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.35rem;
      margin-top: 0.45rem;
      font-size: 0.7rem;
    }
    .calendar-note__service {
      font-weight: 600;
      text-transform: capitalize;
      color: rgba(15,23,42,0.75);
    }
    .status-pill {
      padding: 0.05rem 0.5rem;
      border-radius: 999px;
      border: 1px solid transparent;
      font-weight: 600;
      font-size: 0.65rem;
      text-transform: capitalize;
    }
    .status-pill--success {
      background: rgba(16,185,129,0.15);
      color: #047857;
      border-color: rgba(16,185,129,0.35);
    }
    .status-pill--info {
      background: rgba(59,130,246,0.1);
      color: #1d4ed8;
      border-color: rgba(59,130,246,0.3);
    }
    .status-pill--pending {
      background: rgba(250,204,21,0.18);
      color: #92400e;
      border-color: rgba(245,158,11,0.4);
    }
    .status-pill--danger {
      background: rgba(248,113,113,0.15);
      color: #b91c1c;
      border-color: rgba(248,113,113,0.4);
    }
    .status-pill--warning {
      background: rgba(251,191,36,0.18);
      color: #b45309;
      border-color: rgba(251,191,36,0.4);
    }
    @media (max-width: 1023px) {
      #calendar {
        overflow-x: auto;
      }
      #calRows {
        min-width: 900px;
      }
    }
  </style>
@endsection
@section('content')
  @php
    $debug = request()->query('debug') === '1';
    // Restrict doctors list to the logged-in vet (session user)
    // Fall back to existing clinic session keys if needed
    $sessionRole = session('role')
        ?? data_get(session('auth_full'), 'role')
        ?? data_get(session('user'), 'role');

    $clinicCandidates = [
        session('clinic_id'),
        session('vet_registerations_temp_id'),
        session('vet_registeration_id'),
        session('vet_id'),
        data_get(session('user'), 'clinic_id'),
        data_get(session('user'), 'vet_registeration_id'),
        data_get(session('auth_full'), 'clinic_id'),
        data_get(session('auth_full'), 'user.clinic_id'),
        data_get(session('auth_full'), 'user.vet_registeration_id'),
    ];

    if ($sessionRole !== 'doctor') {
        array_unshift(
            $clinicCandidates,
            session('user_id'),
            data_get(session('user'), 'id')
        );
    }

    $resolvedClinicId = null;
    foreach ($clinicCandidates as $candidate) {
        if ($candidate === null || $candidate === '') {
            continue;
        }
        $num = (int) $candidate;
        if ($num > 0) {
            $resolvedClinicId = $num;
            break;
        }
    }

    $resolvedClinic = $resolvedClinicId ? \App\Models\VetRegisterationTemp::find($resolvedClinicId) : null;
    $resolvedDoctorId = session('doctor_id')
        ?? data_get(session('auth_full'), 'doctor_id')
        ?? data_get(session('auth_full'), 'user.doctor_id')
        ?? data_get(session('user'), 'doctor_id')
        ?? ($sessionRole === 'doctor' ? (session('user_id') ?? data_get(session('user'), 'id')) : null);
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
      <div class="weekday-row grid grid-cols-7 text-xs font-semibold text-gray-600 mb-3">
        <div class="p-2">Sun</div><div class="p-2">Mon</div><div class="p-2">Tue</div><div class="p-2">Wed</div><div class="p-2">Thu</div><div class="p-2">Fri</div><div class="p-2">Sat</div>
      </div>
      <div id="calRows" class="grid grid-cols-7 gap-3"></div>
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
  const SESSION_DOCTOR_ID = Number(@json($resolvedDoctorId ?? null)) || null;
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

  const NOTE_PALETTES = [
    { start: '#fef4d7', end: '#fde68a', shadow: 'rgba(251,191,36,0.4)', rotate: '-1.4deg' },
    { start: '#e0f2fe', end: '#bae6fd', shadow: 'rgba(37,99,235,0.25)', rotate: '1.2deg' },
    { start: '#ede9fe', end: '#ddd6fe', shadow: 'rgba(109,40,217,0.25)', rotate: '-0.6deg' },
    { start: '#fce7f3', end: '#fbcfe8', shadow: 'rgba(236,72,153,0.3)', rotate: '0.8deg' },
    { start: '#dcfce7', end: '#bbf7d0', shadow: 'rgba(16,185,129,0.25)', rotate: '-1deg' },
    { start: '#fee2e2', end: '#fecaca', shadow: 'rgba(248,113,113,0.35)', rotate: '1deg' },
  ];

  function paletteForBooking(booking, idx){
    const key = (booking.status || booking.service_type || '') + idx;
    let hash = 0;
    for (let i = 0; i < key.length; i++){
      hash = (hash + key.charCodeAt(i)) % NOTE_PALETTES.length;
    }
    return NOTE_PALETTES[hash] || NOTE_PALETTES[0];
  }

  function statusClass(status){
    const key = (status || 'pending').toLowerCase();
    if (['completed','done'].includes(key)) return 'status-pill--success';
    if (['confirmed','accepted','active'].includes(key)) return 'status-pill--info';
    if (['cancelled','canceled','rejected','declined'].includes(key)) return 'status-pill--danger';
    if (['rescheduled','on_hold'].includes(key)) return 'status-pill--warning';
    return 'status-pill--pending';
  }

  function renderStickyNote(booking, idx){
    const palette = paletteForBooking(booking, idx);
    const schedule = booking.scheduled_for || booking.booking_created_at || '';
    const time = schedule ? schedule.slice(11,16) : '';
    const parent = booking.pet_parent_name || booking.pet_parent_phone || `Booking #${booking.id}`;
    const petLine = booking.pet_name
      ? `${booking.pet_name}${booking.pet_breed ? ` (${booking.pet_breed})` : ''}`
      : '';
    const summarySource = booking.user_summary || booking.ai_summary || '';
    const summary = summarySource ? String(summarySource).split('\n')[0] : '';
    const service = (booking.service_type || '').replace(/_/g,' ');
    const statusText = (booking.status || 'pending').replace(/_/g,' ');
    return `
      <button
        type="button"
        class="calendar-note"
        data-id="${booking.id}"
        style="--note-start:${palette.start};--note-end:${palette.end};--note-shadow:${palette.shadow};--note-tilt:${palette.rotate};"
      >
        <div class="calendar-note__time">${esc(time || '—')}</div>
        <div class="calendar-note__title">${esc(parent)}</div>
        ${petLine ? `<div class="calendar-note__pet">${esc(petLine)}</div>` : ''}
        ${summary ? `<div class="calendar-note__summary">${esc(summary)}</div>` : ''}
        <div class="calendar-note__meta">
          <span class="status-pill ${statusClass(booking.status)}">${esc(statusText)}</span>
          <span class="calendar-note__service">${esc(service || 'booking')}</span>
        </div>
      </button>
    `;
  }

  function monthStart(dateStr){ const d=new Date(dateStr); d.setDate(1); d.setHours(0,0,0,0); return d; }
  function fmtDate(d){ const y=d.getFullYear(); const m=String(d.getMonth()+1).padStart(2,'0'); const day=String(d.getDate()).padStart(2,'0'); return `${y}-${m}-${day}`; }
  function parseWhen(b){ const t=b.scheduled_for || b.booking_created_at; return t ? new Date(t.replace(' ','T')) : null; }

  function renderCalendar(rows){
    const byDay = new Map();
    rows.forEach(b=>{ const dt=parseWhen(b); if(!dt) return; const key=fmtDate(dt); if(!byDay.has(key)) byDay.set(key,[]); byDay.get(key).push(b); });
    for(const [dayKey, list] of byDay.entries()){
      list.sort((a,b)=>{
        const aw = (a.scheduled_for || a.booking_created_at || '').slice(11,16);
        const bw = (b.scheduled_for || b.booking_created_at || '').slice(11,16);
        return aw.localeCompare(bw);
      });
      byDay.set(dayKey, list);
    }
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
        <div class="calendar-cell ${inMonth?'':'calendar-cell--muted'}">
          <div class="calendar-cell__header">
            <div class="calendar-cell__date">${d.getDate()}</div>
            <div class="calendar-cell__count">${items.length ? `${items.length} booking${items.length>1?'s':''}` : ''}</div>
          </div>
          <div class="calendar-cell__notes">
            ${items.length ? items.map((b, idx)=>renderStickyNote(b, idx)).join('') : ''}
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
      const ev = e.target.closest('.calendar-note');
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
