{{-- resources/views/snoutiq/provider-schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Weekly Schedule')
@section('page_title','Weekly Schedule')

@section('content')
<div class="max-w-5xl mx-auto">
  <h2 class="text-lg font-semibold">Doctor Weekly Availability</h2>
  <p class="text-sm text-gray-600 mb-3">
    Select a doctor below. Availability will load for the chosen doctor & service type.
  </p>

  <div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
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
        <select id="service_type" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 text-gray-700" disabled>
          <option value="in_clinic" selected>in_clinic</option>
        </select>
        <div class="mt-1 text-xs text-gray-500">Fixed to in_clinic for this page</div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Avg Consultation (mins)</label>
        <input type="number" id="avg_consultation_mins" value="20" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Max bookings / hour</label>
        <input type="number" id="max_bph" value="3" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>
    </div>
  </div>

  <fieldset>
    <legend class="sr-only">Weekly Schedule</legend>
    <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
      <thead>
        <tr class="bg-gray-50 text-left text-sm text-gray-700">
          <th class="p-3" style="width:120px">Day</th>
          <th class="p-3">Active</th>
          <th class="p-3">Start</th>
          <th class="p-3">End</th>
          <th class="p-3">Break Start</th>
          <th class="p-3">Break End</th>
        </tr>
      </thead>
      <tbody class="text-sm">
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
        @foreach($days as $d)
          <tr data-dow="{{ $d['idx'] }}" class="border-t">
            <td class="p-3 font-medium text-gray-800">{{ $d['name'] }}</td>
            <td class="p-3 text-center"><input type="checkbox" class="active" checked></td>
            <td class="p-3"><input type="time" class="start w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="09:00"></td>
            <td class="p-3"><input type="time" class="end w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="18:00"></td>
            <td class="p-3"><input type="time" class="break_start w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"></td>
            <td class="p-3"><input type="time" class="break_end w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="mt-2 text-xs text-gray-500" id="metaNote"></div>
    <button id="btnSave" class="mt-3 px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Save Weekly Availability</button>
    <div id="saveOut" class="mt-2 text-sm"></div>
  </fieldset>

  <script>
    // Load SweetAlert2 for onboarding prompts
    (function(){
      var s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'; document.head.appendChild(s);
    })();
    // ---------- env ----------
    const ORIGIN   = window.location.origin; // http://127.0.0.1:8000 or https://snoutiq.com
    const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
    const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;

    // ---------- tiny utils ----------
    const el  = (s) => document.querySelector(s);
    const els = (s) => Array.from(document.querySelectorAll(s));
    const toHM = (t) => (t && t.length >= 5 ? t.slice(0,5) : '');
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
    const toHMS = (t) => (t && t.length === 5 ? `${t}:00` : t);

    // Simple in-page validation
    function timeLt(a, b) { return a && b && a < b; }

    // ----- Onboarding Step 3: Clinic Schedule -----
    document.addEventListener('DOMContentLoaded', function(){
      try{
        const u = new URL(location.href);
        const isOnb = (u.searchParams.get('onboarding')||'') === '1';
        const step  = u.searchParams.get('step')||'';
        const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
        if (isOnb && step==='3' && localStorage.getItem('onboarding_v1_done') !== '1'){
          const show = ()=>{
            if (!window.Swal) { setTimeout(show, 150); return; }
            Swal.fire({
              icon:'info',
              title:'Step 3 of 3: Set Clinic Schedule',
              html:'Configure your in-clinic hours. Patients can book in-person visits during these times.',
              showCancelButton:true,
              confirmButtonText:'Finish Setup',
              cancelButtonText:'I will update this first'
            }).then(r=>{
              if (r.isConfirmed){
                localStorage.setItem('onboarding_v1_done','1');
                Swal.fire({icon:'success', title:'All set!', timer:1200, showConfirmButton:false});
                setTimeout(()=>{ window.location.href = `${window.location.origin}${PATH_PREFIX}/doctor`; }, 900);
              }
            });
          };
          show();
        }
      }catch(_){ }
    });

    // ---------- load existing availability for selected doctor ----------
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
        if (note) note.textContent = `Loaded ${list.length} of 7 days from server for "${serviceType}". Missing days show defaults and are unchecked.`;

        els('tbody tr[data-dow]').forEach(tr => {
          const dow = Number(tr.getAttribute('data-dow'));
          const row = byDow.get(dow);

          const $active = tr.querySelector('.active');
          const $start  = tr.querySelector('.start');
          const $end    = tr.querySelector('.end');
          const $bStart = tr.querySelector('.break_start');
          const $bEnd   = tr.querySelector('.break_end');

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

        // pull common knobs from first row if present
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

    // ---------- collect rows ----------
    function collectAvailability() {
      const serviceType = el('#service_type')?.value || 'in_clinic';
      const avgMins     = Number(el('#avg_consultation_mins').value || 20);
      const maxBph      = Number(el('#max_bph').value || 3);

      const availability = [];
      let validationError = null;

      els('tbody tr[data-dow]').forEach(tr => {
        const active = tr.querySelector('.active')?.checked;
        if (!active) return;

        const dow       = Number(tr.getAttribute('data-dow'));
        const start     = tr.querySelector('.start')?.value;
        const end       = tr.querySelector('.end')?.value;
        const break_s   = tr.querySelector('.break_start')?.value || null;
        const break_e   = tr.querySelector('.break_end')?.value || null;

        if (!start || !end) return;
        if (!(timeLt(start, end))) { validationError = 'End time must be after start time.'; return; }
        if ((break_s && !break_e) || (!break_s && break_e)) { validationError = 'Provide both break start and break end, or leave both empty.'; return; }
        if (break_s && break_e && !(timeLt(break_s, break_e))) { validationError = 'Break end must be after break start.'; return; }
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

    // ---------- save ----------
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
          out('#saveOut', json ?? text ?? 'Saved', true);
          await loadExistingAvailability(); // refresh UI with server truth
          // Onboarding: finish after saving clinic schedule
          try{
            const u = new URL(location.href);
            const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
            if ((u.searchParams.get('onboarding')||'') === '1'){
              localStorage.setItem('onboarding_v1_done','1');
              if (window.Swal){ Swal.fire({icon:'success', title:'Clinic schedule saved', timer:900, showConfirmButton:false}); }
              setTimeout(()=>{ window.location.href = `${window.location.origin}${PATH_PREFIX}/doctor`; }, 600);
            }
          }catch(_){ }
        } else {
          out('#saveOut', json ?? text ?? 'Failed to save', false);
        }
      } catch (err) {
        out('#saveOut', `Network error: ${err?.message || err}`, false);
        console.error('[schedule] saveAvailability error', err);
      } finally {
        btn.disabled = false; btn.textContent = 'Save Weekly Availability';
      }
    }

    // ---------- wire up ----------
    document.addEventListener('DOMContentLoaded', () => {
      const dd = el('#doctor_id');
      if (dd && dd.options.length && dd.value) loadExistingAvailability();

      dd?.addEventListener('change', loadExistingAvailability);
      el('#service_type')?.addEventListener('change', loadExistingAvailability);
      el('#btnSave')?.addEventListener('click', saveAvailability);
    });
  </script>
</div>
@endsection
