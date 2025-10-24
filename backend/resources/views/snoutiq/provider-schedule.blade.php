{{-- resources/views/snoutiq/provider-schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Weekly Schedule')
@section('page_title','Weekly Schedule')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  @if(request()->get('onboarding')==='1')
    @include('layouts.partials.onboarding-steps', ['active' => (int) (request()->get('step', 3))])
  @endif

  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-xl font-semibold text-gray-900">Complete Your Profile</h1>
      <p class="text-sm text-gray-500">Step 3 of 3 · Set your in-clinic availability</p>
    </div>
    <div class="flex items-center gap-3 text-sm">
      <span class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-600 text-white font-semibold">1</span>
      <span class="h-px w-10 bg-indigo-200"></span>
      <span class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-600 text-white font-semibold">2</span>
      <span class="h-px w-10 bg-indigo-200"></span>
      <span class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-600 text-white font-semibold">3</span>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          @if(isset($doctors) && $doctors->count())
            @foreach($doctors as $doc)
              <option value="{{ $doc->id }}">{{ $doc->doctor_name }} (ID: {{ $doc->id }})</option>
            @endforeach
          @else
            <option value="">No doctors found for your account</option>
          @endif
        </select>
        <div class="text-xs text-gray-500 mt-1" id="docIdNote">
          @if(!empty($vetId))
            Vet session ID: {{ $vetId }}
          @endif
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Service Type</label>
        <select id="service_type" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 text-gray-700" disabled>
          <option value="in_clinic" selected>in_clinic</option>
        </select>
        <div class="mt-1 text-xs text-gray-500">Fixed to in_clinic for this page</div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Avg Consultation (mins)</label>
        <input type="number" id="avg_consultation_mins" value="20" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Max bookings / hour</label>
        <input type="number" id="max_bph" value="3" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-semibold text-gray-800">Weekly Clinic Schedule</h3>
      <span class="text-xs text-gray-500">Toggle a day off or adjust start/end & breaks.</span>
    </div>

    @php
      $days = [
        ['idx'=>0,'name'=>'Sunday'],
        ['idx'=>1,'name'=>'Monday'],
        ['idx'=>2,'name'=>'Tuesday'],
        ['idx'=>3,'name'=>'Wednesday'],
        ['idx'=>4,'name'=>'Thursday'],
        ['idx'=>5,'name'=>'Friday'],
        ['idx'=>6,'name'=>'Saturday'],
      ];
    @endphp

    <div class="space-y-4">
      @foreach($days as $d)
        <div class="border border-gray-200 rounded-xl p-4 js-day-card" data-dow="{{ $d['idx'] }}">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <div class="text-base font-semibold text-gray-900">{{ $d['name'] }}</div>
              <div class="text-xs text-gray-500">Set your clinic availability for {{ strtolower($d['name']) }}.</div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
              <input type="checkbox" class="active h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
              <span>Active</span>
            </label>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Start</label>
              <input type="time" class="start mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="09:00">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">End</label>
              <input type="time" class="end mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="18:00">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Break Start</label>
              <input type="time" class="break_start mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">Break End</label>
              <input type="time" class="break_end mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
          </div>

          <div class="mt-2 text-xs text-gray-500">
            Leave break fields empty if the doctor is available continuously between start and end.
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-3 text-xs text-gray-500" id="metaNote"></div>
    <div class="flex items-center justify-between mt-5">
      <div class="text-xs text-gray-500 max-w-md">
        Need split hours? Add break start/end to carve out mid-day downtime.
      </div>
      <button id="btnSave" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
        Save Weekly Availability
      </button>
    </div>
    <div id="saveOut" class="mt-2 text-sm"></div>
  </div>
</div>

<script>
  (function(){
    var s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'; document.head.appendChild(s);
  })();

  const ORIGIN   = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
  const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;

  const el  = (selector) => document.querySelector(selector);
  const els = (selector) => Array.from(document.querySelectorAll(selector));
  const toast = (msg, ok = true) => {
    if (window.Swal) {
      Swal.fire({ toast:true, position:'top', timer:1400, showConfirmButton:false, icon: ok ? 'success' : 'error', title:String(msg) });
    }
  };
  const toHM = (t) => (t && t.length >= 5 ? t.slice(0,5) : '');
  const toHMS = (t) => (t && t.length === 5 ? `${t}:00` : t);
  const fmt = (v) => { try { return typeof v === 'string' ? v : JSON.stringify(v, null, 2); } catch { return String(v); } };
  const out = (sel, payload, ok = true) => {
    const d = el(sel); if (!d) return;
    d.innerHTML = `<pre style="white-space:pre-wrap">${fmt(payload)}</pre>`;
    d.className = ok ? 'mt-2 text-sm text-green-700' : 'mt-2 text-sm text-red-700';
  };
  const getSelectedDoctorId = () => {
    const v = Number(el('#doctor_id')?.value);
    return Number.isFinite(v) && v > 0 ? v : null;
  };
  const timeLt = (a, b) => a && b && a < b;

  document.addEventListener('DOMContentLoaded', function(){
    try{
      const u = new URL(location.href);
      const isOnb = (u.searchParams.get('onboarding')||'') === '1';
      const step  = u.searchParams.get('step')||'';
      if (isOnb && step==='3' && localStorage.getItem('onboarding_v1_done') !== '1'){
        const show = ()=>{
          if (!window.Swal) { setTimeout(show, 150); return; }
          Swal.fire({
            icon:'info',
            title:'Step 3: Clinic schedule',
            html:'Set your weekly in-clinic hours. After saving, continue to emergency coverage to finish onboarding.',
            confirmButtonText:'Got it',
          });
        };
        show();
      }
    }catch(_){ }
  });

  async function loadExistingAvailability() {
    const doctorId = getSelectedDoctorId();
    if (!doctorId) {
      const note = el('#metaNote'); if (note) note.textContent = 'Select a doctor to load availability.';
      return;
    }
    const serviceType = el('#service_type')?.value || 'in_clinic';

    try {
      const url  = `${apiBase}/doctors/${doctorId}/availability` + (serviceType ? `?service_type=${encodeURIComponent(serviceType)}` : '');
      const res  = await fetch(url, { headers: { Accept: 'application/json' } });
      const text = await res.text();
      let json   = null; try { json = JSON.parse(text.replace(/^\uFEFF/, '')); } catch {}
      if (!res.ok) { out('#saveOut', text || 'Failed to load availability', false); return; }

      const list  = Array.isArray(json?.availability) ? json.availability : [];
      const byDow = new Map(list.map(r => [Number(r.day_of_week), r]));

      const note = el('#metaNote');
      if (note) note.textContent = `Loaded ${list.length} of 7 days from server for "${serviceType}".`;

      els('.js-day-card').forEach(card => {
        const dow = Number(card.getAttribute('data-dow'));
        const row = byDow.get(dow);
        const $active = card.querySelector('.active');
        const $start  = card.querySelector('.start');
        const $end    = card.querySelector('.end');
        const $bStart = card.querySelector('.break_start');
        const $bEnd   = card.querySelector('.break_end');

        if (row) {
          if ($active) $active.checked = true;
          if ($start)  $start.value    = toHM(row.start_time || '09:00:00');
          if ($end)    $end.value      = toHM(row.end_time   || '18:00:00');
          if ($bStart) $bStart.value   = toHM(row.break_start || '');
          if ($bEnd)   $bEnd.value     = toHM(row.break_end   || '');
        } else {
          if ($active) $active.checked = false;
          if ($start)  $start.value    = '09:00';
          if ($end)    $end.value      = '18:00';
          if ($bStart) $bStart.value   = '';
          if ($bEnd)   $bEnd.value     = '';
        }
      });

      const first = list[0] ?? null;
      if (first) {
        const avg = el('#avg_consultation_mins');
        const bph = el('#max_bph');
        if (avg && first.avg_consultation_mins != null) avg.value = Number(first.avg_consultation_mins);
        if (bph && first.max_bookings_per_hour != null) bph.value = Number(first.max_bookings_per_hour);
      }
    } catch (e) {
      out('#saveOut', `Load error: ${e?.message || e}`, false);
      console.error('[schedule] loadExistingAvailability error', e);
    }
  }

  function collectAvailability() {
    const serviceType = el('#service_type')?.value || 'in_clinic';
    const avgMins     = Number(el('#avg_consultation_mins').value || 20);
    const maxBph      = Number(el('#max_bph').value || 3);

    const availability = [];
    let validationError = null;

    els('.js-day-card').forEach(card => {
      const active = card.querySelector('.active')?.checked;
      if (!active) return;

      const dow       = Number(card.getAttribute('data-dow'));
      const start     = card.querySelector('.start')?.value;
      const end       = card.querySelector('.end')?.value;
      const break_s   = card.querySelector('.break_start')?.value || null;
      const break_e   = card.querySelector('.break_end')?.value || null;

      if (!start || !end) return;
      if (!timeLt(start, end)) { validationError = 'End time must be after start time.'; return; }
      if ((break_s && !break_e) || (!break_s && break_e)) { validationError = 'Provide both break start and break end, or leave both empty.'; return; }
      if (break_s && break_e && !timeLt(break_s, break_e)) { validationError = 'Break end must be after break start.'; return; }
      if (break_s && break_e && (!timeLt(start, break_s) || !timeLt(break_e, end))) { validationError = 'Break must lie within working hours.'; return; }

      availability.push({
        service_type: serviceType,
        day_of_week: dow,
        start_time:  toHMS(start),
        end_time:    toHMS(end),
        break_start: break_s ? toHMS(break_s) : null,
        break_end:   break_e ? toHMS(break_e) : null,
        avg_consultation_mins:  avgMins,
        max_bookings_per_hour:  maxBph
      });
    });

    return { availability, validationError };
  }

  async function saveAvailability() {
    const doctorId = getSelectedDoctorId();
    if (!doctorId) { alert('Select a doctor first'); return; }

    const { availability, validationError } = collectAvailability();
    if (validationError) { out('#saveOut', validationError, false); return; }
    if (!availability.length) { out('#saveOut', 'Select at least one active day with valid times', false); return; }

    const btn = el('#btnSave');
    btn.disabled = true; btn.textContent = 'Saving…';
    out('#saveOut', '', true);

    try {
      const res  = await fetch(`${apiBase}/doctors/${doctorId}/availability`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept':'application/json' },
        body: JSON.stringify({ availability })
      });
      const text = await res.text();
      let json   = null; try { json = JSON.parse(text); } catch {}

      if (res.ok) {
        const successMessage = (json && typeof json === 'object' && json.message)
          ? json.message
          : (typeof json === 'string' ? json : 'Clinic schedule saved');
        out('#saveOut', successMessage, true);
        toast(successMessage);
        await loadExistingAvailability();
        try{
          const u = new URL(location.href);
          const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
          if ((u.searchParams.get('onboarding')||'') === '1'){
            if (window.Swal){
              Swal.fire({
                icon:'success',
                title:'Clinic schedule saved',
                text:'Next: confirm your emergency coverage hours.',
                timer:1200,
                showConfirmButton:false,
              });
            }
            setTimeout(()=>{ window.location.href = `${window.location.origin}${PATH_PREFIX}/doctor/emergency-hours?onboarding=1&step=4`; }, 800);
          }
        }catch(_){ }
      } else {
        const failMessage = (json && typeof json === 'object' && json.error)
          ? json.error
          : (typeof json === 'string' ? json : 'Failed to save');
        out('#saveOut', failMessage, false);
        toast(failMessage, false);
      }
    } catch (err) {
      const failMessage = `Network error: ${err?.message || err}`;
      out('#saveOut', failMessage, false);
      toast(failMessage, false);
      console.error('[schedule] saveAvailability error', err);
    } finally {
      btn.disabled = false; btn.textContent = 'Save Weekly Availability';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const dd = el('#doctor_id');
    if (dd && dd.options.length && dd.value) loadExistingAvailability();

    dd?.addEventListener('change', loadExistingAvailability);
    el('#service_type')?.addEventListener('change', loadExistingAvailability);
    el('#btnSave')?.addEventListener('click', saveAvailability);
  });
</script>
@endsection
