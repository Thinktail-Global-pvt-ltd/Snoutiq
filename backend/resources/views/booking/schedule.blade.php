{{-- resources/views/booking/schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Book Appointment')
@section('page_title','Book Appointment')

@section('head')
  <script>
    // ---------- Smart base detection (works on localhost & production) ----------
    const ORIGIN          = window.location.origin;                 // http://127.0.0.1:8000, https://snoutiq.com
    const PATHNAME        = window.location.pathname;               // current path
    const ON_BACKEND_PATH = PATHNAME.startsWith('/backend');
    const IS_LOCAL        = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);

    // For page links, if your app runs under /backend, prefix links with it (not used much here, but exposed).
    const appBasePath = ON_BACKEND_PATH ? '/backend' : '';

    // API base:
    // - Local:      {origin}/api
    // - Production: {origin}/backend/api  (regardless of whether current path has /backend)
    window.apiBase = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
    // ---------------------------------------------------------------------------

    function fmt(obj){ try{return JSON.stringify(obj,null,2)}catch(e){return String(obj)} }
    // Page loader overlay
    let __loading = 0;
    const __loaderEl = () => document.getElementById('pageLoader');
    const __loaderMsgEl = () => document.getElementById('loaderMessage');
    function __showLoader(){ const el = __loaderEl(); if(el) el.style.display = 'flex'; }
    function __hideLoader(){ const el = __loaderEl(); if(el) el.style.display = 'none'; }
    function __inc(){ if((++__loading) === 1) __showLoader(); }
    function __dec(){ __loading = Math.max(0, __loading-1); if(__loading===0) __hideLoader(); }
    function setLoaderText(msg){ const m = __loaderMsgEl(); if(m){ m.textContent = msg || 'Loading…'; } }

    async function api(method, url, data){
      __inc();
      try {
        const opts = { method, headers: { 'Content-Type':'application/json', 'Accept':'application/json' } };
        if(method !== 'GET' && data!==undefined) opts.body = JSON.stringify(data);
        const res = await fetch(url, opts);
        // Read once to avoid stream reuse issues
        const text = await res.text();
        let j=null; try { j = JSON.parse(text.replace(/^\uFEFF/, '')); } catch(_){}
        return { ok: res.ok, status: res.status, json: j, raw: text };
      } finally { __dec(); }
    }
  </script>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

  <!-- Full-screen loader overlay (hidden by default) -->
  <div id="pageLoader" class="fixed inset-0 z-50 hidden items-center justify-center bg-white/70 backdrop-blur-sm">
    <div class="flex items-center gap-4 text-indigo-700">
      <div class="h-10 w-10 md:h-12 md:w-12 rounded-full border-4 border-indigo-300 border-t-indigo-600 animate-spin drop-shadow"></div>
      <div class="flex items-center">
        <span id="loaderMessage" class="text-base md:text-lg font-semibold animate-pulse">Loading…</span>
        <span class="flex gap-1 ml-2">
          <span class="h-2.5 w-2.5 bg-indigo-600 rounded-full animate-bounce" style="animation-delay:0ms"></span>
          <span class="h-2.5 w-2.5 bg-indigo-500 rounded-full animate-bounce" style="animation-delay:150ms"></span>
          <span class="h-2.5 w-2.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
        </span>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Clinic</label>
        <select id="clinic_id" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          <option value="">-- Select Clinic --</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          <option value="">-- Load Doctors --</option>
        </select>
      </div>
      <div class="flex items-end">
        <button id="btnLoadDoctors" class="hidden w-full md:w-auto px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Load Doctors</button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" id="sched_date" value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded border-gray-300">
      </div>
      <div class="flex items-end">
        <button id="btnLoadAvailability" class="hidden w-full md:w-auto px-4 py-2 rounded bg-gray-800 text-white hover:bg-gray-900">Load Availability</button>
      </div>
      <div class="text-sm text-gray-500 flex items-center">Select a time slot from any doctor below. Selected doctor will be prioritized.</div>
    </div>
  </div>

  <div id="availabilityPanel" class="grid gap-4"></div>

  @php $__debug = request()->query('debug') === '1'; @endphp
  <div id="bookingDetailsCard" class="bg-white rounded-xl shadow p-4 mt-6" style="{{ $__debug ? '' : 'display:none' }}">
    <h2 class="text-lg font-semibold mb-2">Booking Details</h2>
    <form id="formCreate" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="user_id" value="">
      <div class="md:col-span-2 text-xs text-indigo-700">
        <span id="userBadge" class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-200">User: detecting…</span>
      </div>
      <input type="hidden" name="pet_id" value="">
      <div>
        <label class="block text-sm font-medium text-gray-700">Pet</label>
        <select id="petSelect" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          <option value="">-- Select Pet --</option>
        </select>
        <div id="petNote" class="text-xs text-gray-500 mt-1">Pets are loaded from your account.</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Service Type</label>
        <select name="service_type" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          <option value="video">video</option>
          <option value="in_clinic">in_clinic</option>
          <option value="home_visit">home_visit</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Urgency</label>
        <select name="urgency" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          <option>low</option>
          <option selected>medium</option>
          <option>high</option>
          <option>emergency</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">AI Summary</label>
        <textarea name="ai_summary" rows="4" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Summary from chat history will appear here"></textarea>
        <div class="text-xs text-gray-500 mt-1">Auto-filled from your AI chat history. You can edit before submitting. <button id="btnRefetchAi" type="button" class="text-indigo-700 hover:underline">Refetch</button></div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">AI Urgency Score</label>
        <input type="number" name="ai_urgency_score" min="0" max="1" step="0.01" value="0.45" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Symptoms (comma separated)</label>
        <input type="text" name="symptoms" value="vomiting,lethargy" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus-border-indigo-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Latitude</label>
        <input type="number" step="0.000001" name="latitude" value="28.4949" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Longitude</label>
        <input type="number" step="0.000001" name="longitude" value="77.0868" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Address</label>
        <input type="text" name="address" value="Sector 28, Gurgaon" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>
      <div class="md:col-span-2 flex items-center gap-3">
        <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Create Booking</button>
        <div id="createResult" class="text-sm"></div>
      </div>
    </form>
  </div>
  @if(! $__debug)
    <div class="mt-4 flex items-center gap-3">
      <button id="btnCreateBookingExternal" type="button" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Create Booking</button>
      <div id="createResultExternal" class="text-sm"></div>
    </div>
    <div class="mt-2 text-xs text-gray-500">Booking fields are hidden. Add <code>?debug=1</code> to edit details.</div>
  @endif
@endsection

@section('scripts')
<script>
  const SESSION_USER_ID = Number(@json(session('user_id') ?? data_get(session('user'), 'id') ?? null)) || null;
  const PRESET_CLINIC = Number(@json($presetClinicId ?? null)) || null;
  const PRESET_DOCTOR = Number(@json($presetDoctorId ?? null)) || null;
  const PRESET_SERVICE = @json($presetServiceType ?? null);

  const el = s => document.querySelector(s);
  const panel = document.getElementById('availabilityPanel');

  // Resolve user id similar to chat page: prefer session, allow override from storage
  function resolveUserId(){
    let id = SESSION_USER_ID;
    try {
      const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full') || localStorage.getItem('sn_session_v1');
      if (raw) {
        const obj = JSON.parse(raw);
        const stored = Number(obj?.user?.id ?? obj?.user_id ?? obj?.user?.user_id ?? NaN);
        if (!Number.isNaN(stored) && stored) id = stored;
      }
    } catch(_){}
    return id;
  }

  function renderAvailability(data, selectedDoctorId){
    panel.innerHTML = '';
    const serviceType = document.querySelector('[name="service_type"]').value || 'video';
    (data.doctors||[]).forEach(async (doc)=>{
      const prefer = String(doc.id)===String(selectedDoctorId||'');
      const box = document.createElement('div');
      // group class enables CSS-only hover reveal of slot grid
      box.className = 'group bg-white rounded-xl shadow p-4 border ' + (prefer? 'border-indigo-600' : 'border-gray-200');
      box.innerHTML = `
        <div class="flex items-center justify-between mb-3">
          <div class="text-base font-semibold">${doc.name || ('Doctor #'+doc.id)}</div>
          ${prefer? '<span class="text-xs text-indigo-600 font-medium">Preferred</span>' : ''}
        </div>
        <label class="block text-sm text-gray-600 mb-1">Time Slot</label>
        <div class="text-[11px] text-gray-400 mb-2 hidden group-hover:hidden hover-hint">Hover to view today's available times</div>
        <div class="slot-grid grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
        <select class="slot-select hidden" data-doctor-id="${doc.id}">
          <option value=""></option>
        </select>
      `;
      panel.appendChild(box);
      // Fetch free slots for this doctor & date
      const d = el('#sched_date').value;
      try{
        const r = await api('GET', `${apiBase}/doctors/${doc.id}/free-slots?date=${encodeURIComponent(d)}&service_type=${encodeURIComponent(serviceType)}`);
        const sel = box.querySelector('.slot-select');
        const gridEl = box.querySelector('.slot-grid');
        if(r.ok && r.json){
          const slots = r.json.free_slots || [];
          sel.innerHTML = '<option value=""></option>' + slots.map(s=>`<option value="${s}">${s.slice(0,5)}</option>`).join('');
          if (gridEl){
            gridEl.innerHTML = slots.length
              ? slots.map(s => `
                  <button type="button" class="slot-btn px-2 py-1.5 text-sm rounded border border-gray-300 hover:bg-indigo-50" data-time="${s}" data-doctor-id="${doc.id}">${s.slice(0,5)}</button>
                `).join('')
              : '<div class="text-xs text-gray-500">No slots</div>';
            // Preselect first for preferred doctor
            if (prefer && slots.length){
              sel.value = slots[0];
              const firstBtn = gridEl.querySelector('.slot-btn');
              if(firstBtn){ firstBtn.classList.add('bg-indigo-600','text-white','border-indigo-600'); }
            }
            gridEl.addEventListener('click', (ev)=>{
              const btn = ev.target.closest('.slot-btn'); if(!btn) return;
              // clear previous selections
              document.querySelectorAll('.slot-btn.selected, .slot-btn.bg-indigo-600').forEach(b=>{
                b.classList.remove('selected','bg-indigo-600','text-white','border-indigo-600');
              });
              document.querySelectorAll('.slot-select').forEach(s=> s.value='');
              // set this selection
              btn.classList.add('selected','bg-indigo-600','text-white','border-indigo-600');
              sel.value = btn.getAttribute('data-time');
            });
          }
        } else { sel.innerHTML = '<option value="">No slots</option>'; }
      }catch(_){
        const sel = box.querySelector('.slot-select'); const gridEl = box.querySelector('.slot-grid');
        sel.innerHTML = '<option value="">No slots</option>';
        if(gridEl){ gridEl.innerHTML = '<div class="text-xs text-gray-500">No slots</div>'; }
      }
    });
    if(!panel.children.length){
      panel.innerHTML = '<div class="text-sm text-gray-500">No availability for the selected date.</div>'
    }
  }

  async function loadDoctorsForClinic(){
    const cid = el('#clinic_id').value; if(!cid) return;
    setLoaderText('fetching best and highest rated doctors that suits you bes');
    try{
      const res = await api('GET', `${apiBase}/clinics/${cid}/doctors`);
      console.log('[schedule] GET /clinics/'+cid+'/doctors =>', res);
      if(res.ok && res.json && Array.isArray(res.json.doctors)){
        const sel = el('#doctor_id');
        sel.innerHTML = '<option value="">-- Select Doctor --</option>' + res.json.doctors.map(d => `<option value="${d.id}">${d.name||('Doctor #'+d.id)}</option>`).join('');
        sel.disabled = false;
        if(res.json.doctors.length){ sel.value = res.json.doctors[0].id; await loadAvailabilityForSelection(); }
        el('#sched_date').disabled = false;
      }
    } finally { setLoaderText('Loading…'); }
  }

  async function loadAvailabilityForSelection(){
    const cid = el('#clinic_id').value; if(!cid) return;
    const selDoctor = el('#doctor_id').value;
    setLoaderText('fetching best and highest rated doctors that suits you bes');
    try{
      const res = await api('GET', `${apiBase}/clinics/${cid}/availability`);
      console.log('[schedule] GET /clinics/'+cid+'/availability =>', res);
      if(res.ok && res.json){ renderAvailability(res.json, selDoctor); }
    } finally { setLoaderText('Loading…'); }
  }

  // Pre-fill user_id from session/storage
  function __initSchedule(){
    const FINAL_UID = resolveUserId();
    if(FINAL_UID){
      const u=el('[name="user_id"]'); if(u){ u.value = FINAL_UID; }
      const badge = document.getElementById('userBadge');
      if (badge) badge.textContent = `User: ${FINAL_UID}`;
      // Load user's pets
      loadPets(FINAL_UID);
      // Load AI summary (server resolves session user)
      loadAiSummary();
      // If preset service type provided (e.g., in_clinic for clinic booking flow), set once
      if (PRESET_SERVICE){
        const svc = document.querySelector('[name="service_type"]');
        if (svc) svc.value = PRESET_SERVICE;
      }
    } else {
      const badge = document.getElementById('userBadge');
      if (badge) badge.textContent = `User: not set (use ${apiBase}/session/login?user_id=...)`;
    }
    // Load clinics and attach auto loaders
    loadClinics();
    el('#clinic_id').addEventListener('change', loadDoctorsForClinic);
    el('#doctor_id').addEventListener('change', loadAvailabilityForSelection);
    el('#sched_date').addEventListener('change', loadAvailabilityForSelection);
  }
  if (document.readyState === 'loading'){
    window.addEventListener('DOMContentLoaded', __initSchedule);
  } else {
    __initSchedule();
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
    const res = await api('GET', `${apiBase}/clinics`);
    console.log('[schedule] GET /clinics =>', res);
    if(res.ok && res.json){
      const sel = el('#clinic_id');
      const clinics = extractClinics(res.json).map(c => ({
        id: c.id ?? c.clinic_id ?? null,
        name: c.name ?? c.title ?? c.slug ?? (c.id ? `Clinic #${c.id}` : 'Clinic')
      }));
      console.log('[schedule] clinics normalized:', clinics);
      sel.innerHTML = '<option value="">-- Select Clinic --</option>' + clinics.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
      if (PRESET_CLINIC) {
        sel.value = PRESET_CLINIC;
        sel.disabled = true;
        await loadDoctorsForClinic();
        if (PRESET_DOCTOR) {
          const dsel = el('#doctor_id');
          dsel.value = PRESET_DOCTOR;
          await loadAvailabilityForSelection();
        }
      }
    }
  }

  async function loadPets(userId){
    const res = await api('GET', `${apiBase}/users/${userId}/pets`);
    console.log('[schedule] GET /users/'+userId+'/pets =>', res);
    if(res.ok && res.json){
      const pets = res.json.pets || [];
      console.log('[schedule] pets:', pets);
      const sel = el('#petSelect');
      if(sel){
        sel.innerHTML = '<option value="">-- Select Pet --</option>' + pets.map(p=>`<option value="${p.id}">${p.name} ${p.breed?('('+p.breed+')'):''}</option>`).join('');
        // Default to first pet
        if(pets.length){ sel.value = pets[0].id; const hidden = el('[name="pet_id"]'); if(hidden) hidden.value = pets[0].id; }
        sel.addEventListener('change', ()=>{ const hidden = el('[name="pet_id"]'); if(hidden) hidden.value = sel.value; });
      }
    }
  }

  async function loadAiSummary(){
    // Use Gemini 1.5 Flash to summarize chats for current session user
    const res = await api('GET', `${apiBase}/ai/summary?format=paragraph`);
    console.log('[schedule] GET /ai/summary =>', res);
    if(res.ok && res.json){
      const ta = el('[name="ai_summary"]');
      if(ta){
        const summaryRaw = [
          res.json.summary,
          res.json.data?.summary,
          res.json.data?.data?.summary,
          res.json.payload?.summary,
          res.json.result?.summary
        ].find(value => typeof value === 'string' && value.trim());
        ta.value = summaryRaw ? summaryRaw : '';
      }
    }
  }

  document.getElementById('btnRefetchAi')?.addEventListener('click', async ()=>{
    await loadAiSummary();
  });

  document.getElementById('formCreate').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target), data = Object.fromEntries(fd.entries());
    const uid = resolveUserId();
    if(uid){ data.user_id = uid; }
    const clinicId = el('#clinic_id').value; const selDoctor = el('#doctor_id').value;
    const selectedSlotEl = Array.from(document.querySelectorAll('.slot-select')).find(x=>x.value);
    if(clinicId) data.clinic_id = Number(clinicId);
    if(selectedSlotEl){ data.doctor_id = Number(selectedSlotEl.getAttribute('data-doctor-id')); data.scheduled_date = el('#sched_date').value; data.scheduled_time = selectedSlotEl.value; }
    else if(selDoctor){ data.doctor_id = Number(selDoctor); }
    if(data.symptoms){ data.symptoms = data.symptoms.split(',').map(s=>s.trim()).filter(Boolean); }
    if(data.ai_urgency_score!=='' && data.ai_urgency_score!=null) data.ai_urgency_score = Number(data.ai_urgency_score);
    ['user_id','pet_id'].forEach(k => data[k] = Number(data[k]));
    ;['latitude','longitude'].forEach(k => { if(data[k]!=='' && data[k]!=null) data[k] = Number(data[k]); });

    const res = await api('POST', `${apiBase}/bookings/create`, data);
    console.log('[schedule] POST /bookings/create payload:', data);
    console.log('[schedule] POST /bookings/create =>', res);

    const intRes = document.getElementById('createResult');
    const extRes = document.getElementById('createResultExternal');
    const setMsg = (msg, cls) => {
      if (intRes) { intRes.textContent = msg; intRes.className = cls; }
      if (extRes) { extRes.textContent = msg; extRes.className = cls; }
    };

    if(res.ok && res.json?.booking_id){
      const bookingId = res.json.booking_id;
      setMsg(`Booking #${bookingId} created. Opening payment...`, 'text-sm text-indigo-700');

      // If server provided payment (Razorpay) details, open checkout
      const pay = res.json.payment || {};
      if (pay && pay.key && pay.order_id && typeof Razorpay !== 'undefined'){
        const rzp = new Razorpay({
          key: pay.key,
          order_id: pay.order_id,
          name: 'SnoutIQ',
          description: 'Consultation Payment',
          theme: { color: '#4f46e5' },
          handler: async function (resp) {
            // Verify and save on server (into bookings table)
            const v = await api('POST', `${apiBase}/bookings/${bookingId}/verify-payment`, {
              razorpay_order_id: resp.razorpay_order_id,
              razorpay_payment_id: resp.razorpay_payment_id,
              razorpay_signature: resp.razorpay_signature,
            });
            if (v.ok){
              setMsg(`Payment successful for Booking #${bookingId}`, 'text-sm text-green-700');
            } else {
              setMsg(`Payment verification failed for Booking #${bookingId}`, 'text-sm text-red-700');
            }
          }
        });
        rzp.on('payment.failed', function(){ setMsg('Payment failed or cancelled', 'text-sm text-red-700'); });
        rzp.open();
      } else {
        setMsg(`Booking #${bookingId} created. Payment pending.`, 'text-sm text-yellow-700');
      }

      // Kick off AI summary generation and attach to booking for doctor
      const gen = await api('POST', `${apiBase}/ai/send-summary`, { booking_id: bookingId });
      if(gen.ok){ const ta = el('[name="ai_summary"]'); if(ta){ ta.value = gen.json?.ai_summary || ta.value; } }
      return;
    }
    setMsg('', 'text-sm ' + (res.ok ? 'text-green-700' : 'text-red-700'));
  });

  // External create button (visible when debug!=1)
  document.getElementById('btnCreateBookingExternal')?.addEventListener('click', ()=>{
    document.getElementById('formCreate')?.requestSubmit();
  });
</script>
@endsection
