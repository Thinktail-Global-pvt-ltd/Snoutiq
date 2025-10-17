{{-- resources/views/snoutiq/video-calling-night-edit.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Edit Night Video Slots (19:00–07:00 IST)';
  $doctorId   = (int) ($doctorId ?? request()->integer('doctor_id', auth()->id() ?? 0));
  $userId     = (int) ($userId   ?? request()->integer('user_id',   auth()->id() ?? 0));
  $qDate      = request()->get('date');
  $weekStart  = $qDate ?: now('Asia/Kolkata')->startOfWeek(\Carbon\CarbonInterface::MONDAY)->format('Y-m-d');
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('head')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

  <div class="flex items-center justify-between">
    <a href="javascript:history.back()" class="text-sm text-gray-600 hover:text-gray-800">← Back</a>
    <div class="text-[11px] text-gray-600 flex items-center gap-2">
      <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">Open</span>
      <span class="px-2 py-0.5 rounded bg-green-100 text-green-800">Committed</span>
      <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800">Done</span>
      <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800">Cancelled</span>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="grid grid-cols-1 md:grid-cols-9 gap-4 items-end">
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        @if(isset($doctors))
          <select id="doctor_id" class="mt-1 block w-full rounded-lg border-gray-300">
            @foreach($doctors as $doc)
              <option value="{{ $doc->id }}" @selected($doctorId === (int) $doc->id)>{{ $doc->name ?? ('Doctor #'.$doc->id) }}</option>
            @endforeach
          </select>
        @else
          <input id="doctor_id" type="number" value="{{ $doctorId }}" class="mt-1 block w-full rounded-lg border-gray-300" />
          <p class="text-[11px] text-gray-500 mt-1">Tip: pass ?doctor_id= in the URL.</p>
        @endif
      </div>

      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700">User ID (sent in URL)</label>
        <input id="user_id" type="number" value="{{ $userId }}" class="mt-1 block w-full rounded-lg border-gray-300" />
        <p class="text-[11px] text-gray-500 mt-1">Also appended as <code>user_id</code> in every API call.</p>
      </div>

      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700">Week start (IST)</label>
        <input id="week_start" type="date" value="{{ $weekStart }}" class="mt-1 block w-full rounded-lg border-gray-300">
        <p class="text-[11px] text-gray-500 mt-1">Shows Mon–Sun from this date (IST).</p>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-semibold text-gray-800">Edit Night Slots (19:00–07:00 IST)</h3>
      <div id="nightErrors" class="text-xs text-rose-600"></div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
        <thead>
          <tr class="bg-gray-50 text-left text-sm text-gray-700">
            <th class="p-3" style="width:120px">Day</th>
            <th class="p-3">Committed Night Slots</th>
          </tr>
        </thead>
        <tbody>
          @php $dows=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; @endphp
          @for($i=0;$i<7;$i++)
          <tr class="border-t" data-dow="{{ $i }}">
            <td class="p-3 text-sm text-gray-800">{{ $dows[$i] }}</td>
            <td class="p-3">
              <div class="night-slots grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <div class="text-xs text-gray-500">Loading…</div>
              </div>
            </td>
          </tr>
          @endfor
        </tbody>
      </table>
    </div>
  </div>

  {{-- Keep only the pincode helper --}}
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="flex items-center justify-between gap-2">
      <div class="text-sm font-semibold text-gray-800">Find (Pincode)</div>
      <div class="flex items-center gap-2">
        <input type="text" id="pin_code" maxlength="6" placeholder="e.g., 110001" class="rounded-lg border-gray-300 text-sm w-28">
        <input type="date" id="pin_date" class="rounded-lg border-gray-300 text-sm">
        <button id="btnNearFindPin" class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-900">Find</button>
      </div>
    </div>
    <div id="nearMeta" class="text-xs text-gray-600 mt-1"></div>
    <div id="nearSlots" class="mt-2 text-sm"></div>
  </div>

</div>

<style>
  .pill{display:inline-flex;align-items:center;gap:.375rem;font-size:11px;padding:.25rem .5rem;border-radius:9999px;border-width:1px}
</style>

<script>
(() => {
  // ---------- Utils ----------
  const el  = (s)=>document.querySelector(s);
  const els = (s)=>Array.from(document.querySelectorAll(s));
  const pad2= (n)=>String(n).padStart(2,'0');

  const STATUS_CLASS = {
    committed:'bg-green-50 text-green-700 border-green-200',
    in_progress:'bg-green-50 text-green-700 border-green-200',
    done:'bg-blue-50 text-blue-700 border-blue-200',
    open:'bg-yellow-50 text-yellow-700 border-yellow-200',
    cancelled:'bg-rose-50 text-rose-700 border-rose-200',
    default:'bg-gray-50 text-gray-600 border-gray-200',
  };

  // Night hours whitelist (IST)
  const NIGHT_HOURS = new Set([19,20,21,22,23,0,1,2,3,4,5,6]);

  // Try both API bases: /api and /backend/api
  const ORIGIN = window.location.origin;
  const API_BASES = [
    window.SN_API_BASE || `${ORIGIN}/api`,
    `${ORIGIN}/backend/api`
  ];

  function csrf(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; }
  function reqOpts(method='GET', body=null){
    const headers={'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'};
    const t=csrf(); if(t) headers['X-CSRF-TOKEN']=t;
    const o={method,headers,credentials:'include'};
    if(body!==null){ headers['Content-Type']='application/json'; o.body=JSON.stringify(body); }
    return o;
  }

  // fetch with base fallbacks
  async function apiFetch(path, opts={}) {
    let lastErr;
    for (const base of API_BASES) {
      try {
        const res = await fetch(`${base}${path}`, opts);
        if (res.ok) return res;
        lastErr = new Error(`HTTP ${res.status} @ ${base}${path}`);
      } catch (e) { lastErr = e; }
    }
    throw lastErr || new Error('API unreachable');
  }

  function todayIST(){
    return new Date(new Date().toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
  }
  function ymdIST(d){
    const t = new Date(d.toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
    return `${t.getFullYear()}-${pad2(t.getMonth()+1)}-${pad2(t.getDate())}`;
  }
  function parseISTYmd(ymd){
    const [y,m,d] = ymd.split('-').map(Number);
    const utcMs = Date.UTC(y,m-1,d) - (5.5*3600*1000);
    return new Date(utcMs);
  }
  function weekDatesFrom(weekStartYmd){
    const start = parseISTYmd(weekStartYmd);
    const arr=[]; for(let i=0;i<7;i++){ const d=new Date(start); d.setDate(start.getDate()+i); arr.push(d); }
    return arr.map(ymdIST);
  }
  function dayLabel(ymd){
    const dt = parseISTYmd(ymd);
    return dt.toLocaleDateString('en-IN',{weekday:'short',month:'short',day:'numeric'});
  }
  function statusClass(s){ return STATUS_CLASS[s] || STATUS_CLASS.default; }

  function getDoctorId(){ return Number(el('#doctor_id')?.value || 0); }
  function getUserId(){ return Number(el('#user_id')?.value || 0); }

  // ---------- Data ----------
  async function fetchDoctorDay(doctorId, ymd, userId){
    const qs = `doctor_id=${encodeURIComponent(doctorId)}&user_id=${encodeURIComponent(userId)}&date=${encodeURIComponent(ymd)}&tz=IST`;
    const res = await apiFetch(`/video/slots/doctor?${qs}`, {credentials:'include', headers:{Accept:'application/json'}});
    const txt = await res.text();
    let json=null; try{ json = JSON.parse(txt);}catch{}
    const arr = Array.isArray(json?.slots) ? json.slots : (Array.isArray(json) ? json : []);
    return (arr||[]).filter(s => NIGHT_HOURS.has(Number(s.hour_24)));
  }

  async function releaseSlot(slotId, doctorId, userId){
    // user_id in URL as requested
    await apiFetch(`/video/slots/${encodeURIComponent(slotId)}/release?user_id=${encodeURIComponent(userId)}`, reqOpts('DELETE', { doctor_id: Number(doctorId) }));
  }

  async function commitSlot(slotId, doctorId, userId){
    // user_id in URL; doctor_id in body to stay backward-compatible
    await apiFetch(`/video/slots/${encodeURIComponent(slotId)}/commit?user_id=${encodeURIComponent(userId)}`, reqOpts('POST', { doctor_id: Number(doctorId) }));
  }

  // ---------- Render ----------
  function renderGrid(byDow, errors){
    el('#nightErrors').innerHTML = errors.length ? errors.map(e=>`<div>${e}</div>`).join('') : '';
    const rows = els('tbody tr[data-dow]');
    rows.forEach(tr=>{
      const dow = Number(tr.getAttribute('data-dow'));
      const bucket = byDow[dow] || [];
      const host = tr.querySelector('.night-slots');
      host.innerHTML = '';
      if(!bucket.length){
        host.innerHTML = '<div class="text-xs text-gray-500">No night slots on this day.</div>';
        return;
      }
      bucket.forEach(entry=>{
        const wrap = document.createElement('div');
        wrap.className = 'rounded-lg border p-2 space-y-1';
        const head = document.createElement('div');
        head.className = 'text-xs font-semibold text-gray-800';
        head.textContent = entry.label;
        wrap.appendChild(head);

        entry.items
          .sort((a,b)=>a.hour-b.hour || (a.strip_id??0)-(b.strip_id??0))
          .forEach(it=>{
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-2';
            const left = document.createElement('div');
            left.className = 'text-[11px]';
            left.innerHTML = `<span class="pill border ${statusClass(it.status)}" title="Strip #${it.strip_id ?? '-'}">${pad2(it.hour)}:00 IST - ${it.role || '-'} - ${it.status}${it.strip_id!=null?` - strip ${it.strip_id}`:''}</span>`;
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'text-[11px] px-2 py-1 rounded bg-rose-50 text-rose-700 hover:bg-rose-100';
            del.textContent = 'x';
            del.dataset.slotId = String(it.id);
            row.appendChild(left);
            row.appendChild(del);
            wrap.appendChild(row);
          });

        host.appendChild(wrap);
      });
    });
  }

  // ---------- Load ----------
  async function loadAll(){
    const doctorId = getDoctorId();
    const userId   = getUserId();
    const weekStart = el('#week_start')?.value;
    if(!doctorId){ el('#nightErrors').textContent = 'Select/enter a doctor_id'; return; }
    if(!userId){   el('#nightErrors').textContent = 'Enter user_id'; return; }
    if(!weekStart){ el('#nightErrors').textContent = 'Pick week start date'; return; }

    const ymds = weekDatesFrom(weekStart);
    const byDow = {};
    const errors = [];

    await Promise.all(ymds.map(async ymd=>{
      try{
        const items = await fetchDoctorDay(doctorId, ymd, userId);
        const label = dayLabel(ymd);
        const norm = items.map(s=>{
          const hour = Number(s.hour_24 ?? s.hour ?? 0);
          const status = (s.status ?? s.state ?? 'committed').toString().toLowerCase();
          return {
            id: Number(s.id ?? s.slot_id ?? s.uuid),
            hour: (hour+24)%24,
            strip_id: (s.strip_id!=null ? Number(s.strip_id) : null),
            role: s.role ?? s.user_role ?? '-',
            status,
          };
        });
        const dow = parseISTYmd(ymd).getUTCDay(); // 0..6 Sun..Sat
        (byDow[dow] = byDow[dow] || []).push({ date: ymd, label, items: norm });
      }catch(e){ errors.push(`${ymd}: ${e?.message || 'Fetch failed'}`); }
    }));

    renderGrid(byDow, errors);
  }

  // ---------- Nearby pincode (user_id in URL) ----------
  function renderNear(openSlots){
    if(!openSlots.length) return '<div class="text-xs text-gray-500">No open night slots around this pincode.</div>';
    return openSlots.map(s=>{
      const h=(Number(s.hour_24)+24)%24;
      const cls = STATUS_CLASS[s.status ?? 'open'] || STATUS_CLASS.open;
      return `<div class="flex items-center justify-between gap-2 border rounded-lg p-2">
        <div class="text-[11px]"><span class="pill border ${cls}">${pad2(h)}:00 IST - strip ${s.strip_id ?? '-'}</span></div>
        <button class="text-[11px] px-2 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700" data-commit-id="${Number(s.id ?? s.slot_id)}">Commit</button>
      </div>`;
    }).join('');
  }

  async function findByPincode(){
    const code = (el('#pin_code')?.value || '').trim();
    const date = el('#pin_date')?.value || ymdIST(todayIST());
    const userId = getUserId();
    if(!/^\d{6}$/.test(code)){ alert('Enter 6-digit pincode'); return; }
    const qs = `date=${encodeURIComponent(date)}&code=${encodeURIComponent(code)}&user_id=${encodeURIComponent(userId)}`;
    const r = await apiFetch(`/video/slots/nearby/pincode?${qs}`, {credentials:'include'});
    const j = await r.json().catch(()=>({}));
    const allowed = new Set([19,20,21,22,23,0,1,2,3,4,5,6]);
    const filtered = (Array.isArray(j?.slots) ? j.slots : []).filter(s => allowed.has(Number(s.hour_24)));
    el('#nearSlots').innerHTML = renderNear(filtered);
    el('#nearMeta').innerHTML = `Nearest pincode: <b>${code}</b> - Strip: <b>${j?.strip_id||'?'}</b> - Found: <b>${filtered.length}</b> open slots`;
  }

  // ---------- Wire up ----------
  document.addEventListener('DOMContentLoaded', ()=>{
    el('#pin_date').value = ymdIST(todayIST());
    loadAll();

    el('#doctor_id')?.addEventListener('change', loadAll);
    el('#week_start')?.addEventListener('change', loadAll);
    el('#user_id')?.addEventListener('change', loadAll);

    document.addEventListener('click', async (e)=>{
      // delete (release)
      const del = e.target.closest('button[data-slot-id]');
      if(del){
        e.preventDefault();
        const id = Number(del.dataset.slotId);
        const userId = getUserId();
        const doctorId = getDoctorId();
        del.disabled = true; del.textContent='...';
        try{ await releaseSlot(id, doctorId, userId); await loadAll(); }
        catch(err){ alert(err?.message || 'Failed'); }
        finally{ del.disabled=false; del.textContent='x'; }
      }
      // commit from near
      const com = e.target.closest('button[data-commit-id]');
      if(com){
        e.preventDefault();
        const id = Number(com.dataset.commitId);
        const doctorId = getDoctorId();
        const userId   = getUserId();
        com.disabled = true; com.textContent='...';
        try{ await commitSlot(id, doctorId, userId); await loadAll(); await findByPincode(); }
        catch(err){ alert(err?.message || 'Commit failed'); }
        finally{ com.disabled=false; com.textContent='Commit'; }
      }
    });

    el('#btnNearFindPin')?.addEventListener('click', findByPincode);
  });
})();
</script>
@endsection


