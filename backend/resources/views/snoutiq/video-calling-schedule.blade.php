{{-- resources/views/snoutiq/video-calling-schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Video Calling Schedule by Doctor';
  $readonly   = (bool) ($readonly ?? true);
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-5xl mx-auto">
  <h2 class="text-lg font-semibold">Doctor Weekly Availability (Video)</h2>
  <p class="text-sm text-gray-600 mb-3">This view uses a separate storage and API. Existing flows remain unchanged.</p>

  <div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          @if(isset($doctors) && $doctors->count())
            @foreach($doctors as $doc)
              <option value="{{ $doc->id }}">{{ $doc->doctor_name }}</option>
            @endforeach
          @else
            <option value="">No doctors found</option>
          @endif
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Service Type</label>
        <input type="text" value="video" disabled class="mt-1 w-full rounded border-gray-300 bg-gray-50 text-gray-600">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Avg Consultation (mins)</label>
        <input type="number" id="avg_consultation_mins" value="20" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Max bookings / hour</label>
        <input type="number" id="max_bph" value="3" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif>
      </div>

      <div class="flex items-end">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 mb-1">
          <input type="checkbox" id="enable247" class="rounded border-gray-300" @if($readonly) disabled @endif>
          <span>Enable 24/7</span>
        </label>
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
            <td class="p-3 text-center"><input type="checkbox" class="active" checked @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="start w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="09:00" @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="end w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="18:00" @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="break_start w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="break_end w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="mt-2 text-xs text-gray-500" id="metaNote"></div>
    @if(!$readonly)
      <button id="btnSave" class="mt-3 px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Save Weekly Availability</button>
    @endif
    <div id="saveOut" class="mt-2 text-sm"></div>
  </fieldset>

  <div class="mt-6 bg-white rounded-xl shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" id="sched_date" value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded border-gray-300">
      </div>
      <div class="flex items-end">
        <button id="btnLoadSlots" class="w-full md:w-auto px-4 py-2 rounded bg-gray-800 text-white hover:bg-gray-900">Load Free Slots</button>
      </div>
      <div class="text-sm text-gray-500 flex items-center">Shows free slots from new table</div>
    </div>
    <div id="slotOut" class="mt-3 text-sm"></div>
  </div>

  <script>
    const ORIGIN   = window.location.origin;
    const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
    const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
    const READONLY = Boolean(@json($readonly ?? true));

    const el  = (s) => document.querySelector(s);
    const els = (s) => Array.from(document.querySelectorAll(s));
    const toHM = (t) => (t && t.length >= 5 ? t.slice(0,5) : '');
    const toHMS = (t) => (t && t.length === 5 ? `${t}:00` : t);
    function fmt(v){ try{ return typeof v==='string'? v : JSON.stringify(v,null,2);}catch{ return String(v);} }
    function out(sel, payload, ok=true){ const d = el(sel); if(d){ d.innerHTML = `<pre style="white-space:pre-wrap">${fmt(payload)}</pre>`; d.className = ok? 'mt-2 text-sm text-green-700':'mt-2 text-sm text-red-700'; } }

    function timeLt(a,b){ return a && b && a < b; }

    function getDoctorId(){
      const v = Number(el('#doctor_id')?.value);
      return Number.isFinite(v) && v>0 ? v : null;
    }

    async function loadExisting(){
      const id = getDoctorId(); if(!id){ out('#saveOut','Select a doctor', false); return; }
      try{
        const res = await fetch(`${apiBase}/video-schedule/doctors/${id}/availability`, { headers:{Accept:'application/json'} });
        const text = await res.text(); let json=null; try{ json = JSON.parse(text.replace(/^\uFEFF/,'')); }catch{}
        if(!res.ok){ out('#saveOut', text || 'Failed to load', false); return; }
        const list = Array.isArray(json?.availability)? json.availability: [];
        const byDow = new Map(list.map(r=>[Number(r.day_of_week), r]));
        const avg = el('#avg_consultation_mins'); const bph = el('#max_bph');
        if(list[0]){ if(avg) avg.value = Number(list[0].avg_consultation_mins||20); if(bph) bph.value = Number(list[0].max_bookings_per_hour||3); }
        els('tbody tr[data-dow]').forEach(tr=>{
          const dow = Number(tr.getAttribute('data-dow'));
          const row = byDow.get(dow);
          const $active = tr.querySelector('.active');
          const $start  = tr.querySelector('.start');
          const $end    = tr.querySelector('.end');
          const $bStart = tr.querySelector('.break_start');
          const $bEnd   = tr.querySelector('.break_end');
          if(row){
            if($active) $active.checked = true;
            if($start)  $start.value = toHM(row.start_time||'09:00:00');
            if($end)    $end.value   = toHM(row.end_time||'18:00:00');
            if($bStart) $bStart.value= toHM(row.break_start||'');
            if($bEnd)   $bEnd.value  = toHM(row.break_end||'');
          } else {
            if($active) $active.checked = false;
            if($start)  $start.value = '09:00';
            if($end)    $end.value   = '18:00';
            if($bStart) $bStart.value= '';
            if($bEnd)   $bEnd.value  = '';
          }
        });
        const note = el('#metaNote'); if(note) note.textContent = `Loaded ${list.length} of 7 days from new table.`;

        // Detect 24/7: all 7 days with 00:00 to >=23:59 and no breaks
        try{
          const byDowFull = new Map(list.map(r => [Number(r.day_of_week), r]));
          let is247 = true;
          for(let d=0; d<7; d++){
            const r = byDowFull.get(d);
            if(!r){ is247=false; break; }
            const st = String(r.start_time||'');
            const en = String(r.end_time||'');
            const noBreak = (!r.break_start && !r.break_end);
            if(!(st.startsWith('00:00') && (en >= '23:59:00')) && !(st.startsWith('00:00') && en.startsWith('00:00'))) { is247=false; break; }
            if(!noBreak) { is247=false; break; }
          }
          const cb247 = el('#enable247');
          if(cb247){ cb247.checked = !!is247; toggle247Inputs(!!is247); }
        }catch(_){ /* ignore */ }
      }catch(e){ out('#saveOut', `Load error: ${e?.message||e}`, false); }
    }

    function toggle247Inputs(disabled){
      els('tbody tr[data-dow] input[type="time"], tbody tr[data-dow] input[type="checkbox"]').forEach(inp => {
        if(inp.classList.contains('active')){ inp.disabled = false; }
        else { inp.disabled = !!disabled; }
      });
    }

    function apply247(on){
      const avgMins = Number(el('#avg_consultation_mins').value || 20);
      const maxBph  = Number(el('#max_bph').value || 3);
      els('tbody tr[data-dow]').forEach(tr=>{
        const $active = tr.querySelector('.active');
        const $start  = tr.querySelector('.start');
        const $end    = tr.querySelector('.end');
        const $bStart = tr.querySelector('.break_start');
        const $bEnd   = tr.querySelector('.break_end');
        if(on){
          if($active) $active.checked = true;
          if($start)  $start.value = '00:00';
          if($end)    $end.value   = '23:59';
          if($bStart) $bStart.value = '';
          if($bEnd)   $bEnd.value   = '';
        }
      });
      toggle247Inputs(on);
      const note = el('#metaNote'); if(note && on){ note.textContent = '24/7 enabled — all days set to 00:00–23:59 with no breaks.'; }
    }

    function collect(){
      const avgMins = Number(el('#avg_consultation_mins').value || 20);
      const maxBph  = Number(el('#max_bph').value || 3);
      const availability = [];
      let validationError = null;
      els('tbody tr[data-dow]').forEach(tr=>{
        const active = tr.querySelector('.active')?.checked; if(!active) return;
        const dow = Number(tr.getAttribute('data-dow'));
        const start = tr.querySelector('.start')?.value; const end = tr.querySelector('.end')?.value;
        const bs = tr.querySelector('.break_start')?.value || null; const be = tr.querySelector('.break_end')?.value || null;
        if(!start||!end) return;
        if(!(timeLt(start,end))) { validationError = 'End time must be after start time.'; return; }
        if((bs&&!be)||(!bs&&be)) { validationError = 'Provide both break start and end or leave both empty.'; return; }
        if(bs&&be && !(timeLt(bs,be))) { validationError = 'Break end must be after break start.'; return; }
        if(bs&&be && (!timeLt(start,bs) || !timeLt(be,end))) { validationError = 'Break must lie within working hours.'; return; }
        availability.push({ day_of_week:dow, start_time:toHMS(start), end_time:toHMS(end), break_start: bs?toHMS(bs):null, break_end: be?toHMS(be):null, avg_consultation_mins:avgMins, max_bookings_per_hour:maxBph });
      });
      return { availability, validationError };
    }

    async function save(){
      const id = getDoctorId(); if(!id){ alert('Select a doctor'); return; }
      const {availability, validationError} = collect();
      if(validationError){ out('#saveOut', validationError, false); return; }
      if(!availability.length){ out('#saveOut', 'Select at least one active day with valid times', false); return; }
      const btn = el('#btnSave'); if(!btn) return; btn.disabled = true; btn.textContent = 'Saving…';
      try{
        const res = await fetch(`${apiBase}/video-schedule/doctors/${id}/availability`, { method:'PUT', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({availability})});
        const text = await res.text(); let json=null; try{ json = JSON.parse(text);}catch{}
        if(res.ok){ out('#saveOut', json ?? text ?? 'Saved', true); await loadExisting(); }
        else { out('#saveOut', json ?? text ?? 'Failed to save', false); }
      }catch(e){ out('#saveOut', `Network error: ${e?.message||e}`, false);} finally { if(btn){ btn.disabled=false; btn.textContent='Save Weekly Availability'; } }
    }

    async function loadSlots(){
      const id = getDoctorId(); if(!id){ out('#slotOut', 'Select a doctor', false); return; }
      const d = el('#sched_date').value; if(!d){ out('#slotOut', 'Select a date', false); return; }
      try{
        const r = await fetch(`${apiBase}/video-schedule/doctors/${id}/free-slots?date=${encodeURIComponent(d)}`);
        const t = await r.text(); let j=null; try{ j=JSON.parse(t);}catch{}
        if(r.ok){ out('#slotOut', j?.free_slots || []); }
        else { out('#slotOut', t || 'Failed to load', false); }
      }catch(e){ out('#slotOut', e?.message||String(e), false); }
    }

    document.addEventListener('DOMContentLoaded', ()=>{
      const dd = el('#doctor_id'); if(dd && dd.options.length && dd.value) loadExisting();
      dd?.addEventListener('change', loadExisting);
      if(!READONLY) el('#btnSave')?.addEventListener('click', save);
      el('#btnLoadSlots')?.addEventListener('click', loadSlots);
      if(!READONLY){
        el('#enable247')?.addEventListener('change', (e)=> apply247(!!e.target.checked));
      }
    });
  </script>
</div>
@endsection
