{{-- resources/views/snoutiq/video-calling-schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Video Calling Schedule by Doctor';
  $readonly   = (bool) ($readonly ?? true);
  $isDebug    = request()->get('debug') === '1';
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  @if(request()->get('onboarding')==='1')
    @include('layouts.partials.onboarding-steps', ['active' => (int) (request()->get('step', 2))])
  @endif

  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-semibold tracking-tight">Doctor Weekly Availability (Video)</h2>
      <p class="text-sm text-gray-600">This view uses a separate storage and API. Existing flows remain unchanged.</p>
    </div>
    <div class="hidden md:flex items-center gap-2 text-xs">
      <span class="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">IST only</span>
      @if($readonly)
        <span class="px-2 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200">Read-only</span>
      @endif
    </div>
  </div>

  {{-- =================== Doctor & Settings =================== --}}
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          @if(isset($doctors) && $doctors->count())
            @foreach($doctors as $doc)
              <option value="{{ $doc->id }}">{{ $doc->doctor_name ?? $doc->name ?? ('Doctor #'.$doc->id) }}</option>
            @endforeach
          @else
            <option value="">No doctors found</option>
          @endif
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Service Type</label>
        <input type="text" value="video" disabled class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 text-gray-600">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Avg Consultation (mins)</label>
        <input type="number" id="avg_consultation_mins" value="20" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Max bookings / hour</label>
        <input type="number" id="max_bph" value="3" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif>
      </div>

      {{-- 24/7 toggle --}}
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Enable 24/7</label>
        <div id="enable247Wrap"
             class="flex items-center justify-between gap-3 rounded-xl border bg-white p-2.5 transition-all duration-200 @if($readonly) opacity-60 cursor-not-allowed @endif">
          <div class="flex items-center gap-2">
            <input type="checkbox" id="enable247" class="h-5 w-5 rounded border-gray-300 text-green-600 focus:ring-green-500" @if($readonly) disabled @endif>
            <span class="text-sm font-medium">All-day, all-week availability</span>
          </div>
          <span id="enable247Badge" class="text-xs px-2 py-0.5 rounded-full border border-gray-300 text-gray-700">OFF</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">When enabled, all 7 days will be set to 00:00-23:59 with no breaks.</p>
      </div>
    </div>
  </div>

  {{-- ====== CTA highlight styles (for the night-hours button) ====== --}}
  <style>
    .ctaGlow{position:absolute; inset:-8px; border-radius:14px;
      background:radial-gradient(ellipse at center, rgba(16,185,129,.45), rgba(16,185,129,0) 60%);
      filter:blur(8px); animation:ctaglow 1.8s ease-in-out infinite;}
    @keyframes ctaglow{0%,100%{opacity:.85}50%{opacity:.35}}
    .ctaPulse{position:absolute; inset:-6px; border-radius:12px; border:2px solid rgba(16,185,129,.65);
      animation:ctapulse 1.6s cubic-bezier(.4,0,.2,1) infinite;}
    @keyframes ctapulse{0%{transform:scale(1);opacity:1}70%{transform:scale(1.05);opacity:0}100%{transform:scale(1);opacity:0}}
    .ctaArrow{position:absolute; right:-54px; top:50%; transform:translateY(-50%); font-size:12px; color:#047857;
      animation:floaty 2.4s ease-in-out infinite;}
    @keyframes floaty{0%,100%{transform:translateY(-50%) translateX(0)}50%{transform:translateY(calc(-50% - 2px)) translateX(1px)}}
    .ctaBounce{display:inline-block; animation:bounce 1.2s infinite;}
    @keyframes bounce{0%,100%{transform:translateY(-10%); animation-timing-function:cubic-bezier(.8,0,1,1)}
                      50%{transform:translateY(0);   animation-timing-function:cubic-bezier(0,0,.2,1)}}
  </style>

  {{-- =================== Near Me (PINCODE only) -- Title & date hidden =================== --}}
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="flex items-center justify-end mb-2">
      {{-- Title intentionally hidden as requested --}}
      <div class="relative inline-block" id="nightCtaWrap">
        <span class="ctaGlow" id="nightGlow" aria-hidden="true"></span>
        <span class="ctaPulse" id="nightPulse" aria-hidden="true"></span>

        <button id="btnNearFindPin"
                class="relative z-10 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-lg focus:outline-none focus:ring-4 focus:ring-emerald-300 transition transform hover:scale-[1.02] active:scale-[0.99]">
          Are you available at night hours <span class="ctaBounce ml-1">🌙</span>
        </button>

        <span class="ctaArrow hidden md:block" id="nightArrow">click -></span>
      </div>
    </div>

    <div id="nearMeta" class="text-xs text-gray-600"></div>
    <div id="nearSlots" class="mt-2 text-sm"></div>

  </div>

  <script>
    (function(){
      const ORIGIN = window.location.origin;
      const apiBase = window.SN_API_BASE || (/(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname) ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`);
      const el = (s)=>document.querySelector(s);

      function getTodayIST(){
        const now = new Date();
        const ist = new Date(now.toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
        const p = n => String(n).padStart(2,'0');
        return `${ist.getFullYear()}-${p(ist.getMonth()+1)}-${p(ist.getDate())}`;
      }

      function chip(txt){ return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-gray-100 text-gray-700 border border-gray-200">${txt}</span>`; }
      function card(html){ return `<div class="border rounded-lg p-2 hover:shadow-sm transition">${html}</div>`; }

      const pad2 = (n)=>String(n).padStart(2,'0');

      function renderSlotsPin(list, strip){
        if (!list || !list.length) return '<div class="text-xs text-gray-500">No open slots.</div>';
        let html = '<div class="grid sm:grid-cols-2 gap-2">';
        list.forEach(s=>{
          const hh = pad2((Number(s.hour_24)+6)%24);
          const stripTxt = strip ? `Strip ${chip('#'+(strip.id ?? ''))} ${strip.name? chip(strip.name):''}` : '';
          html += card(`
            <div class="flex items-center justify-between gap-3">
              <div class="text-xs text-gray-600">
                <div>Hour (IST): <b>${hh}:00</b></div>
                <div class="mt-0.5 flex flex-wrap gap-1"> ${stripTxt} ${chip('Role: '+(s.role||'-'))}</div>
              </div>
              <div>
                <button class="px-2 py-1 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-xs" onclick="window._commitNearSlot(${s.id})">Commit</button>
              </div>
            </div>
          `);
        });
        html += '</div>';
        return html;
      }

      function refreshNightSchedule(){
        if (typeof window._loadNightScheduleTable === 'function'){
          window._loadNightScheduleTable();
        }
      }

      function focusNightSlotEditor(){
        const nightBtn = document.querySelector('#btnNearFindPin');
        if(!nightBtn){
          Swal?.fire({icon:'info',title:'Night slot editor unavailable',timer:1400,showConfirmButton:false});
          return;
        }
        nightBtn.scrollIntoView({behavior:'smooth', block:'center'});
        const panel = nightBtn.closest('.bg-white');
        if(panel){
          panel.classList.add('ring','ring-emerald-300','shadow-lg');
          setTimeout(()=>panel.classList.remove('ring','ring-emerald-300','shadow-lg'), 1500);
        }
        setTimeout(()=>nightBtn.focus({preventScroll:true}), 300);
      }

      async function findNearByPincode(){
        el('#nearSlots').innerHTML = '<div class="animate-pulse text-xs text-gray-500">Loading open slots...</div>';

        const date = getTodayIST();
        const p = await fetch(`${apiBase}/geo/nearest-pincode`, {credentials:'include'});
        if(!p.ok){
          el('#nearMeta').textContent = 'Nearest pincode not found (session).';
          el('#nearSlots').innerHTML = '';
          return;
        }
        const jp = await p.json();
        const code = jp?.pincode?.code || jp?.pincode?.pincode || jp?.pincode?.PIN || null;
        const label= jp?.pincode?.name || jp?.pincode?.label || '';

        if(!code){
          el('#nearMeta').textContent = 'Pincode code unavailable.';
          el('#nearSlots').innerHTML = '';
          return;
        }

        const r = await fetch(`${apiBase}/video/slots/nearby/pincode?date=${encodeURIComponent(date)}&code=${encodeURIComponent(code)}`, {credentials:'include'});
        const j = await r.json().catch(()=>({}));
        const allowedHours = new Set([19,20,21,22,23,0,1,2,3,4,5,6]);
        const filtered = (Array.isArray(j?.slots) ? j.slots : []).filter(s => allowedHours.has((Number(s.hour_24)+6)%24));
        el('#nearSlots').innerHTML = renderSlotsPin(filtered, j?.strip || {id:j?.strip_id, name:`Band-${j?.strip_id||''}`});
        el('#nearMeta').innerHTML = `Nearest pincode: <b>${code}</b> ${label? '('+label+')':''} - Band strip: <b>#${j?.strip_id||'?'}</b> - Found: <b>${filtered.length}</b> open slots`;
      }

      window._commitNearSlot = async function(slotId){
        try{
          await window.Csrf.ensure();
          const doctorId = Number(document.querySelector('#doctor_id')?.value || 0);
          if(!doctorId){ return Swal?.fire({icon:'error',title:'Select a doctor',timer:1200,showConfirmButton:false}); }
          const r = await fetch(`${apiBase}/video/slots/${slotId}/commit`, window.Csrf.opts('POST', { doctor_id: doctorId }));
          if (r.ok){
            Swal?.fire({icon:'success',title:'Committed!',timer:900,showConfirmButton:false});
            findNearByPincode();
            if (typeof window._loadNightCoverage === 'function'){ window._loadNightCoverage(); }
            refreshNightSchedule();
          }else{
            const t = await r.text();
            Swal?.fire({icon:'error',title:'Commit failed',text:t||String(r.status)});
          }
        }catch(e){
          Swal?.fire({icon:'error',title:'Commit error',text:String(e?.message||e)});
        }
      };

      document.addEventListener('DOMContentLoaded', ()=>{
        if (window.__nearPanelHooksAttached) return;
        window.__nearPanelHooksAttached = true;

        // Stop CTA highlight on user interaction or after 12s
        const stopCta = ()=>{ ['nightGlow','nightPulse','nightArrow'].forEach(id=>{ const n=document.getElementById(id); if(n) n.style.display='none'; }); };
        const btn = document.querySelector('#btnNearFindPin');
        btn?.addEventListener('click', ()=>{ stopCta(); findNearByPincode(); refreshNightSchedule(); });
        btn?.addEventListener('mouseenter', stopCta);
        setTimeout(stopCta, 12000);

        const doctorSelect = document.querySelector('#doctor_id');
        doctorSelect?.addEventListener('change', ()=>{
          setNightEditMode(false);
          refreshNightSchedule();
        });
        refreshNightSchedule(); // initial night table fill

        const editBtn = document.querySelector('#btnEditNightSlots');
        editBtn?.addEventListener('click', async ()=>{
          const next = !NIGHT_STATE.editMode;
          if(next){
            editBtn.disabled = true;
            try{
              await loadNightScheduleTable();
            }catch(e){
              console.error(e);
            }finally{
              setNightEditMode(true);
              editBtn.disabled = false;
              focusNightSlotEditor();
            }
          }else{
            setNightEditMode(false);
          }
        });

        document.addEventListener('click', (event)=>{
          const target = event.target.closest('[data-night-slot-remove]');
          if(!target) return;
          event.preventDefault();
          if(!NIGHT_STATE.editMode) return;
          handleNightSlotDelete(target);
        });
      });
    })();
  </script>

  {{-- =================== Weekly Schedule Form =================== --}}
  <fieldset class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <legend class="sr-only">Weekly Schedule</legend>
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-semibold text-gray-800">Weekly Schedule</h3>
      <button id="btnEditNightSlots"
              class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-300">
        Edit Night Slots
      </button>
    </div>
    <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
      <thead>
        <tr class="bg-gray-50 text-left text-sm text-gray-700">
          <th class="p-3" style="width:120px">Day</th>
          <th class="p-3">Active</th>
          <th class="p-3">Start</th>
          <th class="p-3">End</th>
          <th class="p-3">Break Start</th>
          <th class="p-3">Break End</th>
          <th class="p-3">Night Slots (IST)</th>
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
          <tr data-dow="{{ $d['idx'] }}" class="border-t hover:bg-gray-50/50">
            <td class="p-3 font-medium text-gray-800">{{ $d['name'] }}</td>
            <td class="p-3 text-center"><input type="checkbox" class="active rounded border-gray-300" checked @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="start w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="09:00" @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="end w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" value="18:00" @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="break_start w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif></td>
            <td class="p-3"><input type="time" class="break_end w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" @if($readonly) disabled @endif></td>
            <td class="p-3 align-top text-xs text-gray-600 night-slots"></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="mt-2 text-xs text-gray-500" id="metaNote"></div>
    @if(!$readonly)
      <button id="btnSave" class="mt-3 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Save Weekly Availability</button>
    @endif
    <div id="saveOut" class="mt-2 text-sm"></div>
  </fieldset>

  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" id="sched_date" value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded-lg border-gray-300">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Days (repeat)</label>
        <input type="number" id="sched_days" min="1" max="60" value="7" class="mt-1 w-full rounded-lg border-gray-300" title="How many days to preview using weekly pattern">
      </div>
      <div class="flex items-end">
        <button id="btnLoadSlots" class="w-full md:w-auto px-4 py-2 rounded-lg bg-gray-800 text-white hover:bg-gray-900">Load Free Slots</button>
      </div>
      <div class="text-sm text-gray-500 flex items-center">Shows free slots from new table</div>
    </div>
    <div id="slotOut" class="mt-3 text-sm"></div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- ===== Global base + CSRF (shared by all panels) ===== --}}
  <script>
    (function(){
      const ORIGIN   = window.location.origin;
      const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
      const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
      window.SN_API_BASE = apiBase;

      window.Csrf = {
        ready: false,
        async ensure() {
          if (this.ready) return;
          await fetch(`${ORIGIN}/sanctum/csrf-cookie`, { credentials: 'include' });
          this.ready = true;
        },
        token() {
          const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
          return m ? decodeURIComponent(m[1]) : '';
        },
        opts(method, bodyObj, extraHeaders={}) {
          return {
            method,
            credentials: 'include',
            headers: {
              'Accept':'application/json',
              'Content-Type':'application/json',
              'X-Requested-With':'XMLHttpRequest',
              'X-XSRF-TOKEN': this.token(),
              ...extraHeaders
            },
            body: bodyObj ? JSON.stringify(bodyObj) : undefined
          };
        }
      };

      const READONLY = Boolean(@json($readonly ?? true));

      // ===== utils for top schedule =====
      const el  = (s) => document.querySelector(s);
      const els = (s) => Array.from(document.querySelectorAll(s));
      const toast = (m, ok=true)=>{ if (window.Swal) Swal.fire({toast:true,position:'top',timer:1200,showConfirmButton:false,icon:ok?'success':'error',title:String(m)}); };
      function fmt(v){ try{ return typeof v==='string'? v : JSON.stringify(v,null,2);}catch{ return String(v);} }
      function out(sel, payload, ok=true){ const d = el(sel); if(d){ d.innerHTML = `<pre style="white-space:pre-wrap">${fmt(payload)}</pre>`; d.className = ok? 'mt-2 text-sm text-green-700':'mt-2 text-sm text-red-700'; } }
      function getDoctorId(){ const v = Number(el('#doctor_id')?.value); return Number.isFinite(v)&&v>0? v:null; }

      const NIGHT_HOURS = new Set([19,20,21,22,23,0,1,2,3,4,5,6]);
      const NIGHT_STATUS = new Set(['committed','in_progress','done']);
      const NIGHT_STATUS_CLASS = {
        committed: 'bg-green-50 text-green-700 border-green-200',
        in_progress: 'bg-green-50 text-green-700 border-green-200',
        done: 'bg-blue-50 text-blue-700 border-blue-200',
        open: 'bg-yellow-50 text-yellow-700 border-yellow-200',
        cancelled: 'bg-rose-50 text-rose-700 border-rose-200',
        default: 'bg-gray-50 text-gray-600 border-gray-200',
      };
      const NIGHT_STATE = {
        editMode: false,
        aggregated: [],
        slotsByDow: {},
        errors: [],
      };
      const pad2 = (n)=>String(n).padStart(2,'0');

      function setNightEditMode(on){
        NIGHT_STATE.editMode = !!on;
        const btn = document.querySelector('#btnEditNightSlots');
        if(btn){
          btn.textContent = NIGHT_STATE.editMode ? 'Done Editing Night Slots' : 'Edit Night Slots';
          btn.classList.toggle('bg-emerald-700', NIGHT_STATE.editMode);
          btn.classList.toggle('bg-emerald-600', !NIGHT_STATE.editMode);
        }
        renderNightSlotsCells();
      }

      function renderNightSlotsCells(){
        const rows = els('tbody tr[data-dow]');
        rows.forEach(tr=>{
          const cell = tr.querySelector('.night-slots');
          if(!cell) return;
          if(NIGHT_STATE.errors.length){
            cell.innerHTML = NIGHT_STATE.errors.map(msg=>`<div class="text-xs text-red-600">${msg}</div>`).join('');
            return;
          }
          if(NIGHT_STATE.editMode){
            const dow = tr.getAttribute('data-dow');
            const items = NIGHT_STATE.slotsByDow[dow] || [];
            if(!items.length){
              cell.innerHTML = '<span class="text-xs text-gray-400">No night slots to edit</span>';
              return;
            }
            let html = '<div class="space-y-1">';
            items.forEach(item=>{
              const meta = `${item.role || '-'} - ${item.status || '-'}`;
              const stripInfo = item.strip_id ? `Strip #${item.strip_id}` : 'Strip ?';
              html += `<div class="flex items-center justify-between gap-2 bg-emerald-50 border border-emerald-200 rounded px-2 py-1 text-xs">
                <div class="flex flex-col">
                  <span class="font-medium text-emerald-900">${item.date_label}</span>
                  <span class="text-emerald-800">${item.time_label}</span>
                  <span class="text-emerald-700">${meta} - ${stripInfo}</span>
                </div>
                <button type="button" class="night-slot-delete text-emerald-900 hover:text-emerald-600 font-semibold" title="Remove slot" data-night-slot-remove="${item.id}">x</button>
              </div>`;
            });
            html += '</div>';
            cell.innerHTML = html;
          } else {
            if(!NIGHT_STATE.aggregated.length){
              cell.innerHTML = '<span class="text-xs text-gray-400">No night slots configured</span>';
              return;
            }
            const chipsHtml = NIGHT_STATE.aggregated.join('');
            cell.innerHTML = `<div class="text-[11px] font-medium text-gray-700 mb-1">Repeats daily</div><div class="flex flex-wrap">${chipsHtml}</div>`;
          }
        });
      }

      async function handleNightSlotDelete(button){
        if(!button) return;
        const slotId = Number(button.getAttribute('data-night-slot-remove'));
        if(!slotId) return;
        const doctorId = getDoctorId();
        if(!doctorId){
          toast('Select a doctor first', false);
          return;
        }
        const confirm = await Swal.fire({
          title: 'Remove night slot?',
          text: 'This will release the slot back to the pool.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, remove',
          cancelButtonText: 'Cancel',
        });
        if(!(confirm.isConfirmed)) return;
        try{
          button.disabled = true;
          button.textContent = '...';
          await window.Csrf.ensure();
          const res = await fetch(`${apiBase}/video/slots/${slotId}/release`, window.Csrf.opts('DELETE', { doctor_id: doctorId }));
          if(!res.ok){
            const msg = await res.text();
            toast(msg || 'Failed to remove', false);
          }else{
            toast('Slot removed', true);
            await loadNightScheduleTable();
          }
        }catch(e){
          toast(e?.message || 'Remove failed', false);
        }finally{
          button.disabled = false;
          button.textContent = 'x';
        }
      }

      function isoAddDays(dateStr, offset){
        const parts = dateStr.split('-').map(Number);
        if (parts.length !== 3 || parts.some(Number.isNaN)) return dateStr;
        const [y,m,d] = parts;
        const base = Date.UTC(y, m-1, d);
        const next = new Date(base + offset * 86400000);
        const yy = next.getUTCFullYear();
        const mm = String(next.getUTCMonth()+1).padStart(2,'0');
        const dd = String(next.getUTCDate()).padStart(2,'0');
        return `${yy}-${mm}-${dd}`;
      }

      function formatDateLabel(dateStr){
        try{
          return new Date(`${dateStr}T00:00:00`).toLocaleDateString('en-IN',{ weekday:'short', day:'numeric', month:'short' });
        }catch(_){
          return dateStr;
        }
      }

      function slotIstHour(slot){
        if (slot && slot.ist_hour !== undefined && slot.ist_hour !== null){
          const val = Number(slot.ist_hour);
          if (!Number.isNaN(val)) return val;
        }
        if (slot && slot.hour_ist !== undefined && slot.hour_ist !== null){
          const val = Number(slot.hour_ist);
          if (!Number.isNaN(val)) return val;
        }
        const hour24 = Number(slot?.hour_24);
        if (Number.isFinite(hour24)){
          return (hour24 + 6 + 24) % 24;
        }
        const hour = Number(slot?.hour);
        if (Number.isFinite(hour)){
          return hour;
        }
        return null;
      }

      function nightSlotChip(slot){
        const hh = slotIstHour(slot);
        if (hh === null) return null;
        const normalized = ((hh % 24) + 24) % 24;
        if (!NIGHT_HOURS.has(normalized)) return null;
        const statusRaw = String(slot?.status ?? '').toLowerCase();
        if (statusRaw && !NIGHT_STATUS.has(statusRaw)) return null;
        const statusLabel = statusRaw ? statusRaw.replace(/_/g,' ') : 'unknown';
        const role = slot?.role ? String(slot.role) : '-';
        const strip = slot?.strip_id ?? '-';
        const cls = NIGHT_STATUS_CLASS[statusRaw] || NIGHT_STATUS_CLASS.default;
        const timeLabel = `${String(normalized).padStart(2,'0')}:00`;
        return `<span class="inline-flex items-center px-2 py-0.5 mr-1 mb-1 rounded border text-[11px] ${cls}" title="Strip #${strip}">${timeLabel} | ${role} | ${statusLabel}</span>`;
      }

      async function fetchNightSlotsFor(dateStr, doctorId){
        const url = `${apiBase}/video/slots/doctor?doctor_id=${doctorId}&date=${encodeURIComponent(dateStr)}&tz=IST`;
        try{
          const res = await fetch(url, { credentials:'include', headers:{Accept:'application/json'} });
          const txt = await res.text();
          let json=null; try{ json = JSON.parse(txt);}catch{}
          if(!res.ok){
            return { date: dateStr, error:true, message: json?.message || txt || String(res.status), slots: [] };
          }
          const list = Array.isArray(json?.slots) ? json.slots : [];
          return { date: dateStr, error:false, slots:list };
        }catch(e){
          return { date: dateStr, error:true, message: e?.message || String(e), slots: [] };
        }
      }

      async function loadNightScheduleTable(){
        const doctorId = getDoctorId();
        const baseDate = el('#sched_date')?.value || '';
        const daysInput = Math.max(1, Math.min(60, Number(el('#sched_days')?.value || 7)));

        if(!doctorId){
          NIGHT_STATE.errors = ['Select a doctor to view night slots'];
          NIGHT_STATE.aggregated = [];
          NIGHT_STATE.slotsByDow = {};
          renderNightSlotsCells();
          return;
        }
        if(!baseDate){
          NIGHT_STATE.errors = ['Pick a date to view night slots'];
          NIGHT_STATE.aggregated = [];
          NIGHT_STATE.slotsByDow = {};
          renderNightSlotsCells();
          return;
        }

        const dates = Array.from({length: daysInput}, (_,i)=> isoAddDays(baseDate, i));
        const responses = await Promise.all(dates.map(d => fetchNightSlotsFor(d, doctorId)));

        const bucket = new Map(); // date string -> { label, items:[], errors:[] }
        const ensureBucket = (dateStr) => {
          if(!bucket.has(dateStr)){
            bucket.set(dateStr, { label: formatDateLabel(dateStr), items: [], errors: [] });
          }
          return bucket.get(dateStr);
        };

        responses.forEach(res => {
          if(res.error){
            ensureBucket(res.date).errors.push(res.message || 'Failed to load');
            return;
          }
          (res.slots || []).forEach(slot => {
            const chip = nightSlotChip(slot);
            if(!chip) return;
            const hh = slotIstHour(slot);
            if (hh === null) return;
            const targetDate = hh >= 19 ? res.date : (hh < 6 ? isoAddDays(res.date, 1) : res.date);
            const statusRaw = String(slot?.status ?? '').toLowerCase() || 'unknown';
            const statusLabel = statusRaw.replace(/_/g,' ') || 'unknown';
            const normHour = ((hh % 24) + 24) % 24;
            const chipKey = [
              String(normHour).padStart(2,'0'),
              statusRaw,
              slot?.role ?? '',
              slot?.strip_id ?? ''
            ].join('|');
            ensureBucket(targetDate).items.push({
              id: Number(slot.id),
              chip,
              hour: normHour,
              key: chipKey,
              role: slot?.role ? String(slot.role) : '-',
              status: statusLabel,
              status_raw: statusRaw,
              strip_id: slot?.strip_id ?? null,
              time_label: `${pad2(normHour)}:00`,
              target_date: targetDate,
            });
          });
        });

        const mapByDow = new Map();
        bucket.forEach((entry, dateStr) => {
          const dt = new Date(`${dateStr}T00:00:00Z`);
          if(Number.isNaN(dt.getTime())) return;
          const dow = dt.getUTCDay();
          if(!mapByDow.has(dow)) mapByDow.set(dow, []);
          mapByDow.get(dow).push({
            date: dateStr,
            label: entry.label,
            items: entry.items,
            errors: entry.errors,
          });
        });

        const aggregatedErrors = [];
        const uniqueChipMap = new Map();
        mapByDow.forEach(entries => {
          entries.forEach(entry => {
            if(entry.errors && entry.errors.length){
              entry.errors.forEach(msg => aggregatedErrors.push(`${entry.label}: ${msg || 'Failed to load'}`));
            }
            (entry.items || []).forEach(item => {
              if(!uniqueChipMap.has(item.key)){
                uniqueChipMap.set(item.key, item);
              }
            });
          });
        });

        const uniqueChips = Array.from(uniqueChipMap.values()).sort((a,b)=> a.hour - b.hour || a.key.localeCompare(b.key));
        const aggregatedChips = uniqueChips.map(item => item.chip);

        const slotsByDow = {};
        mapByDow.forEach((entries, dow) => {
          const list = [];
          entries.forEach(entry => {
            (entry.items || []).forEach(item => {
              list.push({
                id: item.id,
                time_label: item.time_label,
                role: item.role,
                status: item.status,
                strip_id: item.strip_id,
                date_label: entry.label,
              });
            });
          });
          list.sort((a,b)=>{
            if(a.date_label === b.date_label){
              if(a.time_label === b.time_label){
                return (a.role || '').localeCompare(b.role || '');
              }
              return a.time_label.localeCompare(b.time_label);
            }
            return a.date_label.localeCompare(b.date_label);
          });
          slotsByDow[dow] = list;
        });
        Array.from({length:7}).forEach((_,idx)=>{
          if(!slotsByDow[idx]) slotsByDow[idx] = [];
        });

        NIGHT_STATE.errors = Array.from(new Set(aggregatedErrors));
        NIGHT_STATE.aggregated = aggregatedChips;
        NIGHT_STATE.slotsByDow = slotsByDow;
        renderNightSlotsCells();
      }

      window._loadNightScheduleTable = loadNightScheduleTable;

      function highlight247(){
        const wrap  = el('#enable247Wrap');
        const badge = el('#enable247Badge');
        const cb    = el('#enable247');
        if(!wrap || !cb) return;
        wrap.classList.remove('ring-2','ring-green-300','bg-green-50','border-green-400','shadow-sm','bg-white','border-gray-200');
        if(cb.checked){
          wrap.classList.add('ring-2','ring-green-300','bg-green-50','border-green-400','shadow-sm');
          if(badge){ badge.textContent='ON'; badge.className='text-xs px-2 py-0.5 rounded-full border border-green-500 text-green-700 bg-green-50'; }
        }else{
          wrap.classList.add('bg-white','border-gray-200');
          if(badge){ badge.textContent='OFF'; badge.className='text-xs px-2 py-0.5 rounded-full border border-gray-300 text-gray-700'; }
        }
      }
      function toggle247Inputs(disabled){
        els('tbody tr[data-dow] input[type="time"], tbody tr[data-dow] input[type="checkbox"]').forEach(inp=>{
          if(inp.classList.contains('active')){ inp.disabled=false; } else { inp.disabled=!!disabled; }
        });
      }
      function apply247(on){
        els('tbody tr[data-dow]').forEach(tr=>{
          const $active=tr.querySelector('.active'), $start=tr.querySelector('.start'), $end=tr.querySelector('.end'), $bStart=tr.querySelector('.break_start'), $bEnd=tr.querySelector('.break_end');
          if(on){ if($active) $active.checked=true; if($start) $start.value='00:00'; if($end) $end.value='23:59'; if($bStart) $bStart.value=''; if($bEnd) $bEnd.value=''; }
        });
        toggle247Inputs(on);
        const note=document.querySelector('#metaNote'); if(note && on){ note.textContent='24/7 enabled -- all days set to 00:00-23:59 with no breaks.'; }
      }

      async function loadExisting(){
        const id = getDoctorId();
        if(!id){ out('#saveOut','Select a doctor',false); await loadNightScheduleTable(); return; }
        try{
          const res = await fetch(`${apiBase}/video-schedule/doctors/${id}/availability`, { credentials:'include', headers:{Accept:'application/json'} });
          const text = await res.text(); let json=null; try{ json = JSON.parse(text.replace(/^\uFEFF/,'')); }catch{}
          if(!res.ok){ out('#saveOut', text || 'Failed to load', false); await loadNightScheduleTable(); return; }
          const list = Array.isArray(json?.availability)? json.availability: [];
          const byDow = new Map(list.map(r=>[Number(r.day_of_week), r]));
          const avg = document.querySelector('#avg_consultation_mins'); const bph = document.querySelector('#max_bph');
          if(list[0]){ if(avg) avg.value = Number(list[0].avg_consultation_mins||20); if(bph) bph.value = Number(list[0].max_bookings_per_hour||3); }
          els('tbody tr[data-dow]').forEach(tr=>{
            const dow = Number(tr.getAttribute('data-dow'));
            const row = byDow.get(dow);
            const $active=tr.querySelector('.active'), $start=tr.querySelector('.start'), $end=tr.querySelector('.end'), $bStart=tr.querySelector('.break_start'), $bEnd=tr.querySelector('.break_end');
            if(row){
              if($active) $active.checked = true;
              if($start)  $start.value = (row.start_time||'09:00:00').slice(0,5);
              if($end)    $end.value   = (row.end_time||'18:00:00').slice(0,5);
              if($bStart) $bStart.value= (row.break_start||'').slice(0,5);
              if($bEnd)   $bEnd.value  = (row.break_end||'').slice(0,5);
            } else {
              if($active) $active.checked = false;
              if($start)  $start.value = '09:00';
              if($end)    $end.value   = '18:00';
              if($bStart) $bStart.value= '';
              if($bEnd)   $bEnd.value  = '';
            }
          });

          try{
            const byDowFull = new Map(list.map(r => [Number(r.day_of_week), r]));
            let is247 = true;
            for(let d=0; d<7; d++){
              const r = byDowFull.get(d);
              if(!r){ is247=false; break; }
              const st = String(r.start_time||''); const en = String(r.end_time||'');
              const noBreak = (!r.break_start && !r.break_end);
              if(!(st.startsWith('00:00') && (en >= '23:59:00')) && !(st.startsWith('00:00') && en.startsWith('00:00'))){ is247=false; break; }
              if(!noBreak){ is247=false; break; }
            }
            const cb247 = document.querySelector('#enable247');
            if(cb247){ cb247.checked = !!is247; toggle247Inputs(!!is247); }
          }catch(_){ }
          highlight247();
          await loadNightScheduleTable();
        }catch(e){ out('#saveOut', `Load error: ${e?.message||e}`, false); await loadNightScheduleTable(); }
      }

      function collect(){
        const avgMins = Number(document.querySelector('#avg_consultation_mins').value || 20);
        const maxBph  = Number(document.querySelector('#max_bph').value || 3);
        const availability = [];
        let validationError = null;
        els('tbody tr[data-dow]').forEach(tr=>{
          const active = tr.querySelector('.active')?.checked; if(!active) return;
          const dow = Number(tr.getAttribute('data-dow'));
          const start = tr.querySelector('.start')?.value; const end = tr.querySelector('.end')?.value;
          const bs = tr.querySelector('.break_start')?.value || null; const be = tr.querySelector('.break_end')?.value || null;
          if(!start||!end) return;
          if(!(start < end)) { validationError = 'End time must be after start time.'; return; }
          if((bs&&!be)||(!bs&&be)) { validationError = 'Provide both break start and end or leave both empty.'; return; }
          if(bs&&be && !(bs < be)) { validationError = 'Break end must be after break start.'; return; }
          if(bs&&be && (!(start < bs) || !(be < end))) { validationError = 'Break must lie within working hours.'; return; }
          availability.push({
            day_of_week:dow,
            start_time:(start.length===5? start+':00':start),
            end_time:(end.length===5? end+':00':end),
            break_start: bs? (bs.length===5? bs+':00':bs):null,
            break_end:   be? (be.length===5? be+':00':be):null,
            avg_consultation_mins:avgMins,
            max_bookings_per_hour:maxBph
          });
        });
        return { availability, validationError };
      }

      async function save(){
        const id = getDoctorId(); if(!id){ alert('Select a doctor'); return; }
        const {availability, validationError} = collect();
        if(validationError){ out('#saveOut', validationError, false); return; }
        if(!availability.length){ out('#saveOut', 'Select at least one active day with valid times', false); return; }
        const btn = document.querySelector('#btnSave'); if(btn){ btn.disabled = true; btn.textContent = 'Saving...'; }
        try{
          await window.Csrf.ensure();
          const res = await fetch(`${apiBase}/video-schedule/doctors/${id}/availability`, window.Csrf.opts('PUT', { availability }));
          const text = await res.text(); let json=null; try{ json=JSON.parse(text);}catch{}
          if(res.ok){ out('#saveOut', json ?? text ?? 'Saved', true); await loadExisting(); }
          else { out('#saveOut', json ?? text ?? 'Failed to save', false); }
        }catch(e){ out('#saveOut', `Network error: ${e?.message||e}`, false); }
        finally{ if(btn){ btn.disabled=false; btn.textContent='Save Weekly Availability'; } }
      }

      async function loadSlots(){
        const id = getDoctorId(); if(!id){ out('#slotOut', 'Select a doctor', false); return; }
        const d = document.querySelector('#sched_date').value; if(!d){ out('#slotOut', 'Select a date', false); return; }
        const days = Math.max(1, Math.min(60, Number(document.querySelector('#sched_days')?.value || 1)));
        try{
          const url = `${apiBase}/video-schedule/doctors/${id}/free-slots?date=${encodeURIComponent(d)}&days=${days}`;
          const r = await fetch(url, { credentials:'include' });
          const t = await r.text(); let j=null; try{ j=JSON.parse(t);}catch{}
          if(!r.ok){ out('#slotOut', t || 'Failed to load', false); return; }

          // Render range if present
          if (j && j.free_slots_by_date && typeof j.free_slots_by_date === 'object'){
            let html = '<div class="space-y-2">';
            Object.keys(j.free_slots_by_date).sort().forEach(dt=>{
              const arr = j.free_slots_by_date[dt] || [];
              const chips = arr.length ? arr.map(s=>`<span class=\"inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 mr-1 mb-1\">${s.slice(0,5)}</span>`).join('') : '<span class="text-gray-500">No free slots</span>';
              html += `<div class=\"border rounded p-2\"><div class=\"text-xs text-gray-600 mb-1\"><b>${dt}</b></div><div>${chips}</div></div>`;
            });
            html += '</div>';
            el('#slotOut').innerHTML = html;
          } else {
            out('#slotOut', j?.free_slots || []);
          }
        }catch(e){ out('#slotOut', e?.message||String(e), false); }
      }

      document.addEventListener('DOMContentLoaded', ()=>{
        const dd = document.querySelector('#doctor_id');
        if(dd && dd.options.length && dd.value){
          loadExisting();
        }else{
          loadNightScheduleTable();
        }
        dd?.addEventListener('change', loadExisting);
        if(!READONLY) document.querySelector('#btnSave')?.addEventListener('click', save);
        document.querySelector('#btnLoadSlots')?.addEventListener('click', loadSlots);
        document.querySelector('#sched_date')?.addEventListener('change', loadNightScheduleTable);
        document.querySelector('#sched_days')?.addEventListener('change', loadNightScheduleTable);

        const cb247 = document.querySelector('#enable247'); highlight247();
        if(!READONLY && cb247){
          cb247.addEventListener('change', (e)=>{ const on=!!e.target.checked; apply247(on); highlight247(); Swal?.fire({toast:true,position:'top',icon:on?'success':'info',title:on?'24/7 enabled':'24/7 disabled',timer:1200,showConfirmButton:false}); });
        }
      });
    })();
  </script>

  {{-- =================== ADMIN/OPS PANELS -- visible only with ?debug=1 =================== --}}
  @if ($isDebug)
    {{-- =================== PINCODE Coverage Panel =================== --}}
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-base font-semibold text-gray-800">Pincode Coverage (19:00-07:00 IST)</h3>
        <div class="flex items-center gap-2">
          <input type="date" id="pin_date" class="rounded-lg border-gray-300 text-sm">
          <button id="btnPinLoad" class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-900">Load Pincodes</button>
        </div>
      </div>

      <p class="text-xs text-gray-600">Rows = Gurugram pincodes (banded to strip ids). Uses pincode-coverage API; original strips flow remains unchanged.</p>

      <div id="pinMatrixWrap" class="mt-3 overflow-x-auto"></div>
      <div id="pinSummary" class="mt-2 text-xs text-gray-700"></div>
    </div>

    <script>
      (function(){
        const ORIGIN = window.location.origin;
        const apiBase = window.SN_API_BASE || (/(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname) ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`);
        const hoursIST = [19,20,21,22,23,0,1,2,3,4,5,6];
        const el  = (s)=>document.querySelector(s);
        const h2  = (n)=>String(n).padStart(2,'0');

        function istToUtcParts(dateIst, hourIst){
          let dt = new Date(`${dateIst}T${h2(hourIst)}:00:00+05:30`);
          if ([0,1,2,3,4,5,6].includes(Number(hourIst))) {
            dt = new Date(dt.getTime() + 24*60*60*1000);
          }
          return { date: dt.toISOString().slice(0,10), hour: dt.getUTCHours() };
        }

        const allow = new Set(['committed','in_progress','done']);
        const short = (st)=>({committed:'C',in_progress:'I',done:'D'})[String(st||'').toLowerCase()] ?? '-';
        const pretty= (st)=>({committed:'committed',in_progress:'in progress',done:'done'})[String(st||'').toLowerCase()] ?? 'none';
        const color = (st)=> st==='committed'||st==='in_progress' ? 'bg-green-100 text-green-800'
                      : (st==='done' ? 'bg-blue-100 text-blue-800' : 'bg-gray-50 text-gray-500');
        const onlyFulfilled = (st)=>{ const s=String(st||'').toLowerCase(); return allow.has(s) ? s : null; };

        function renderPinMatrix(matrix, pinRows){
          if (!pinRows.length){
            el('#pinMatrixWrap').innerHTML = '<div class="text-xs text-red-600">No pincodes loaded. Check API.</div>';
            el('#pinSummary').textContent = '';
            return;
          }
          let totals = {committed:0,in_progress:0,done:0};
          let html = '<table class="min-w-full text-xs border border-gray-200 rounded-lg overflow-hidden"><thead><tr class="bg-gray-50">';
          html += '<th class="px-2 py-1 text-left">Pincode</th>';
          hoursIST.forEach(h=> html += `<th class="px-2 py-1 text-center">${h2(h)}</th>`);
          html += '</tr></thead><tbody>';

          pinRows.forEach(r=>{
            html += `<tr class="border-t hover:bg-gray-50/50"><td class="px-2 py-1 whitespace-nowrap font-medium">${r.code} - ${r.label||''}</td>`;
            hoursIST.forEach(h=>{
              const c = (matrix[r.code] && matrix[r.code][h]) || {primary:null,bench:null};
              const p = onlyFulfilled(c.primary), b = onlyFulfilled(c.bench);
              if (p) totals[String(p)] = (totals[String(p)]||0)+1;
              if (b) totals[String(b)] = (totals[String(b)]||0)+1;
              html += `<td class="px-2 py-1 text-center"><div class="inline-flex gap-1">
                        <span class="px-1 rounded ${color(p)}" title="Primary: ${pretty(p)}">P:${short(p)}</span>
                        <span class="px-1 rounded ${color(b)}" title="Bench: ${pretty(b)}">B:${short(b)}</span>
                      </div></td>`;
            });
            html += '</tr>';
          });

          html += '</tbody></table>';
          el('#pinMatrixWrap').innerHTML = html;

          const now = new Date();
          const ist = new Date(now.toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
          const pad=n=>String(n).padStart(2,'0');
          const stamp = `${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())} ${pad(ist.getHours())}:${pad(ist.getMinutes())}:${pad(ist.getSeconds())} IST`;
          el('#pinSummary').innerHTML = `Committed: <b>${totals.committed||0}</b> | In-progress: <b>${totals.in_progress||0}</b> | Done: <b>${totals.done||0}</b> <span class="text-gray-500">(Last updated ${stamp})</span>`;
        }

        async function loadPinCoverage(){
          const d = document.querySelector('#pin_date')?.value; if(!d){ return; }
          const first = istToUtcParts(d, hoursIST[0]);
          const r0 = await fetch(`${apiBase}/video/pincode-coverage?date=${first.date}&hour=${first.hour}`, { credentials:'include' });
          const j0 = await r0.json().catch(()=>({}));
          const pinRows = (j0.coverage||[]).map(r => ({ code: r.pincode, label: r.label }));

          const matrix = {};
          await Promise.all(hoursIST.map(async (h)=>{
            const {date, hour} = istToUtcParts(d, h);
            const res = await fetch(`${apiBase}/video/pincode-coverage?date=${date}&hour=${hour}`, { credentials:'include' });
            if(!res.ok) return;
            const j = await res.json().catch(()=>({}));
            (j.coverage||[]).forEach(row=>{
              matrix[row.pincode] ||= {}; matrix[row.pincode][h] = {primary: row.primary, bench: row.bench};
            });
          }));

          renderPinMatrix(matrix, pinRows);
        }

        document.addEventListener('DOMContentLoaded', ()=>{
          const t=new Date(); const ist = new Date(t.toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
          const pad=n=>String(n).padStart(2,'0');
          document.querySelector('#pin_date').value = `${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())}`;
          document.querySelector('#btnPinLoad').addEventListener('click', loadPinCoverage);
        });
      })();
    </script>

    {{-- =================== Night Coverage Panel =================== --}}
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-base font-semibold text-gray-800">Night Video Coverage (19:00-07:00 IST)</h3>
        <div class="flex flex-wrap items-center gap-2">
          <input type="date" id="night_date" class="rounded-lg border-gray-300 text-sm">
          <label class="inline-flex items-center gap-1 text-xs text-gray-600">
            <input type="checkbox" id="night_autoref" class="rounded border-gray-300">
            Auto-refresh (30s)
          </label>
          <button id="btnNightPublish" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Publish Tonight</button>
          <button id="btnNightReset" class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Reset Data</button>
          <button id="btnNightLoad" class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-900">Load Coverage</button>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-600 mb-2">
        <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">O = Open</span>
        <span class="px-2 py-0.5 rounded bg-green-100 text-green-800">C = Committed</span>
        <span class="px-2 py-0.5 rounded bg-green-100 text-green-800">I = In-progress</span>
        <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800">D = Done</span>
        <span class="px-2 py-0.5 rounded bg-red-100 text-red-800">X = Cancelled</span>
        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700">- = None</span>
      </div>

      <p class="text-xs text-gray-600">
        Rows = <code>geo_strips</code> (Gurugram). Coverage API: <code>/api/video/coverage</code> (IST->UTC per hour).<br>
        Each row shows all Gurugram pincodes mapped to that strip's longitude window (empty bands fall back to nearest pincode in tooltip).
      </p>

      <div id="nightMatrixWrap" class="mt-3 overflow-x-auto"></div>
      <div id="nightSummary" class="mt-2 text-xs text-gray-700"></div>

      @if (request()->query('debug') == '1')
        <div class="mt-6 border-t pt-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
              <label class="block text-sm font-medium text-gray-700">Latitude</label>
              <input type="number" step="0.000001" id="route_lat" class="mt-1 w-full rounded-lg border-gray-300" placeholder="28.4601">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Longitude</label>
              <input type="number" step="0.000001" id="route_lon" class="mt-1 w-full rounded-lg border-gray-300" placeholder="77.0305">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Timestamp (IST)</label>
              <input type="text" id="route_ts" class="mt-1 w-full rounded-lg border-gray-300" placeholder="YYYY-MM-DDTHH:MM:SS+05:30">
            </div>
          </div>
          <div class="mt-2 flex items-center gap-2">
            <button id="btnRouteTest" class="px-3 py-1.5 rounded-lg bg-teal-600 text-white text-sm hover:bg-teal-700">Try Routing</button>
            <div id="routeOut" class="text-sm text-gray-700"></div>
          </div>
        </div>
      @endif
    </div>

    {{-- SweetAlert2 (shared) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- GRID JS (original) --}}
    <script>
      (function(){
        const ORIGIN   = window.location.origin;
        const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
        const apiBase  = window.SN_API_BASE || (IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`);

        window.SN_API_BASE = apiBase;
        window.Csrf = window.Csrf || {
          ready:false,
          async ensure(){ if(this.ready) return; await fetch(`${ORIGIN}/sanctum/csrf-cookie`,{credentials:'include'}); this.ready=true; },
          token(){ const m=document.cookie.match(/XSRF-TOKEN=([^;]+)/); return m? decodeURIComponent(m[1]):''; },
          opts(method,body,extra={}){ return { method, credentials:'include', headers:{'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-XSRF-TOKEN':this.token(),...extra}, body: body? JSON.stringify(body):undefined }; }
        };

        const hoursIST = [19,20,21,22,23,0,1,2,3,4,5,6];
        const el=(s)=>document.querySelector(s), h2=(n)=>String(n).padStart(2,'0'), esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
        const toast=(m,ok=true)=>{ if(window.Swal) Swal.fire({toast:true,position:'top',timer:1000,showConfirmButton:false,icon:ok?'success':'error',title:String(m)}); };

        async function fetchGgnPincodes(){
          const r=await fetch(`${apiBase}/geo/pincodes?city=Gurugram&active=1&limit=500`,{credentials:'include'});
          const j=await r.json().catch(()=>({})); const rows=j.pincodes||j.rows||j.data||[];
          return rows.map(x=>({code:String(x.pincode||x.code),label:String(x.label||x.name||''),lon:Number(x.lon??x.lng??x.longitude)})).filter(p=>p.code&&Number.isFinite(p.lon));
        }
        async function fetchStrips(){
          const r=await fetch(`${apiBase}/geo/strips`,{credentials:'include'});
          const j=await r.json().catch(()=>({}));
          return (j.strips||[]).map(s=>({id:Number(s.id),name:String(s.name||`Gurugram-${String(s.id).padStart(2,'0')}`),min:Number(s.min_lon),max:Number(s.max_lon),ctr:(Number(s.min_lon)+Number(s.max_lon))/2})).sort((a,b)=>a.id-b.id);
        }
        function mapPinsToStrips(strips,pins){
          const P=pins.slice().sort((a,b)=>a.lon-b.lon); const out=new Map(); for(const s of strips) out.set(s.id,[]);
          const assigned=new Array(P.length).fill(false), EPS=1e-6;
          for(let i=0;i<P.length;i++){ const p=P[i]; const s=strips.find(st=>p.lon>=st.min-EPS&&p.lon<=st.max+EPS); if(s){ out.get(s.id).push(p); assigned[i]=true; } }
          for(let i=0;i<P.length;i++){ if(assigned[i]) continue; const p=P[i]; let best=null;
            for(const st of strips){ let score; if(p.lon<st.min) score=st.min-p.lon; else if(p.lon>st.max) score=p.lon-st.max; else score=Math.abs(p.lon-st.ctr); if(!best||score<best.score) best={strip:st,score}; }
            if(best) out.get(best.strip.id).push({...p,_nearest:true});
          }
          if(P.length){ for(const st of strips){ const arr=out.get(st.id); if(!arr.length){ let nearest=null; for(const p of P){ const d=Math.abs(p.lon-st.ctr); if(!nearest||d<nearest.dist) nearest={...p,dist:d,_nearest:true}; } if(nearest) out.set(st.id,[nearest]); } } }
          return out;
        }
        function istToUtcParts(dateIst,hourIst){ let dt=new Date(`${dateIst}T${h2(hourIst)}:00:00+05:30`); if([0,1,2,3,4,5,6].includes(Number(hourIst))) dt=new Date(dt.getTime()+86400000); return {date:dt.toISOString().slice(0,10),hour:dt.getUTCHours()}; }
        const allow=new Set(['committed','in_progress','done']);
        const short=(st)=>({committed:'C',in_progress:'I',done:'D'})[String(st||'').toLowerCase()]??'-';
        const pretty=(st)=>({committed:'committed',in_progress:'in progress',done:'done'})[String(st||'').toLowerCase()]??'none';
        const color=(st)=> st==='committed'||st==='in_progress' ? 'bg-green-100 text-green-800' : (st==='done' ? 'bg-blue-100 text-blue-800' : 'bg-gray-50 text-gray-500');
        const onlyFulfilled=(st)=>{ const s=String(st||'').toLowerCase(); return allow.has(s)? s:null; };

        function renderMatrix(matrix, stripRows){
          if(!stripRows.length){ el('#nightMatrixWrap').innerHTML='<div class="text-xs text-red-600">No strips loaded. Check API.</div>'; el('#nightSummary').textContent=''; return; }
          let totals={committed:0,in_progress:0,done:0};
          let html='<table class="min-w-full text-xs border border-gray-200 rounded-lg overflow-hidden"><thead><tr class="bg-gray-50">';
          html+='<th class="px-2 py-1 text-left">Strip</th>'; hoursIST.forEach(h=> html+=`<th class="px-2 py-1 text-center">${h2(h)}</th>`); html+='</tr></thead><tbody>';
          stripRows.forEach(r=>{
            const title=r.tooltip? ` title="${esc(r.tooltip)}"`:''; html+=`<tr class="border-t hover:bg-gray-50/50"><td class="px-2 py-1 whitespace-nowrap font-medium"${title}>#${r.id} - ${esc(r.name)}</td>`;
            hoursIST.forEach(h=>{ const c=(matrix[r.id]&&matrix[r.id][h])||{primary:null,bench:null}; const p=onlyFulfilled(c.primary), b=onlyFulfilled(c.bench);
              if(p) totals[p]=(totals[p]||0)+1; if(b) totals[b]=(totals[b]||0)+1;
              html+=`<td class="px-2 py-1 text-center"><div class="inline-flex gap-1">
                        <span class="px-1 rounded ${color(p)}" title="Primary: ${pretty(p)}">P:${short(p)}</span>
                        <span class="px-1 rounded ${color(b)}" title="Bench: ${pretty(b)}">B:${short(b)}</span>
                      </div></td>`;
          }); html+='</tr>';
        });
          html+='</tbody></table>'; el('#nightMatrixWrap').innerHTML=html;

          const now=new Date(); const ist=new Date(now.toLocaleString('en-US',{timeZone:'Asia/Kolkata'})); const pad=n=>String(n).padStart(2,'0');
          const stamp=`${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())} ${pad(ist.getHours())}:${pad(ist.getMinutes())}:${pad(ist.getSeconds())} IST`;
          el('#nightSummary').innerHTML=`Committed: <b>${totals.committed||0}</b> | In-progress: <b>${totals.in_progress||0}</b> | Done: <b>${totals.done||0}</b> <span class="text-gray-500">(Last updated ${stamp})</span>`;
        }

        async function loadNightCoverage(){
          const d=el('#night_date')?.value; if(!d){ toast('Pick date',false); return; }
          const [strips,pins]=await Promise.all([fetchStrips(),fetchGgnPincodes()]);
          const pinsByStrip=mapPinsToStrips(strips,pins);
          const stripRows=strips.map(s=>{
            const list=pinsByStrip.get(s.id)||[]; const codes=list.map(p=>p.code);
            const tooltip=list.length? list.map(p=>`${p.code} -- ${p.label||''}${p._nearest?' (nearest)':''}`).join('\n') : '';
            return {id:s.id, name: `${s.name}${codes.length?' -- '+codes.join(', '):''}`, tooltip};
          });

          const matrix={};
          for(const h of hoursIST){
            const {date,hour}=istToUtcParts(d,h);
            const res=await fetch(`${apiBase}/video/coverage?date=${date}&hour=${hour}`,{credentials:'include'}); if(!res.ok) continue;
            const j=await res.json().catch(()=>({})); const rows=(j.coverage||j.rows||[]);
            rows.forEach(row=>{ const sid=Number(row.strip_id??row.id); matrix[sid] ||= {}; matrix[sid][h]={primary:row.primary??null, bench:row.bench??null}; });
          }
          renderMatrix(matrix, stripRows);
        }

        async function publishTonight(){ const d=el('#night_date')?.value; if(!d){ toast('Pick date',false); return; }
          await window.Csrf.ensure(); const r=await fetch(`${apiBase}/video/admin/publish?date=${encodeURIComponent(d)}&tz=IST`, window.Csrf.opts('POST')); if(!r.ok) return toast('Publish failed',false); toast('Published'); loadNightCoverage(); }
        async function resetTonight(){
          const yes=await (window.Swal? Swal.fire({title:'Reset all slots?',text:'This clears video slots and commitments.',icon:'warning',showCancelButton:true,confirmButtonText:'Yes, reset'}): Promise.resolve({isConfirmed:confirm('Reset all slots?')})); 
          if(!(yes && (yes.isConfirmed||yes===true))) return;
          await window.Csrf.ensure(); const r=await fetch(`${apiBase}/video/admin/reset`, window.Csrf.opts('POST')); if(!r.ok) return toast('Reset failed',false); toast('Reset done'); loadNightCoverage();
        }
        async function tryRoute(){ const lat=parseFloat(el('#route_lat')?.value||''), lon=parseFloat(el('#route_lon')?.value||''), ts=String(el('#route_ts')?.value||'');
          if(!Number.isFinite(lat)||!Number.isFinite(lon)||!ts){ toast('Enter lat/lon/ts',false); return; }
          await window.Csrf.ensure(); const r=await fetch(`${apiBase}/video/route?tz=IST`, window.Csrf.opts('POST',{lat,lon,ts})); const j=await r.json(); el('#routeOut').textContent=`doctor_id: ${j?.doctor_id ?? 'null'}`;
        }
        let _timer=null; function setAuto(on){ if(_timer){clearInterval(_timer);_timer=null;} if(on){ _timer=setInterval(()=>{ if(document.visibilityState==='visible') loadNightCoverage(); },30000);} }
        window._loadNightCoverage = loadNightCoverage;

        document.addEventListener('DOMContentLoaded', ()=>{
          const t=new Date(); const ist=new Date(t.toLocaleString('en-US',{timeZone:'Asia/Kolkata'})); const pad=n=>String(n).padStart(2,'0');
          el('#night_date').value=`${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())}`;
          const _tsInput = el('#route_ts'); if(_tsInput){ _tsInput.value = `${el('#night_date').value}T19:30:00+05:30`; }
          el('#btnNightLoad').addEventListener('click', loadNightCoverage);
          el('#btnNightPublish').addEventListener('click', publishTonight);
          el('#btnNightReset').addEventListener('click', resetTonight);
          const _btnRoute = el('#btnRouteTest'); if(_btnRoute){ _btnRoute.addEventListener('click', tryRoute); }
          el('#night_autoref')?.addEventListener('change',e=>setAuto(!!e.target.checked));
        });
      })();
    </script>

    {{-- =================== Smart Night Opt-in =================== --}}
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-base font-semibold text-gray-800">Smart Night Opt-in (Doctor)</h3>
        <span class="text-[11px] text-gray-500">Claims open slots for tonight between 19:00-07:00 (IST)</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700">Date (IST)</label>
          <input type="date" id="optin_date" class="mt-1 w-full rounded-lg border-gray-300">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Max slots to claim</label>
          <input type="number" id="optin_max" min="1" max="12" value="2" class="mt-1 w-full rounded-lg border-gray-300">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Preference</label>
          <select id="optin_pref" class="mt-1 w-full rounded-lg border-gray-300">
            <option value="primary_first" selected>Primary first</option>
            <option value="bench_first">Bench first</option>
            <option value="primary_only">Primary only</option>
            <option value="bench_only">Bench only</option>
          </select>
        </div>
        <div class="flex items-end">
          <button id="btnOptin" class="w-full md:w-auto px-4 py-2 rounded-lg bg-teal-600 text-white hover:bg-teal-700">
            Auto-claim Tonight
          </button>
        </div>
      </div>

      <div id="optinOut" class="mt-3 text-xs text-gray-700 bg-gray-50 border rounded p-2" style="min-height: 64px;"></div>
    </div>

    <script>
      (function(){
        const apiBase = window.SN_API_BASE || `${window.location.origin}/api`;
        const hoursIST = [19,20,21,22,23,0,1,2,3,4,5,6];
        const el = (s)=>document.querySelector(s);
        const h2 = (n)=>String(n).padStart(2,'0');

        function logOpt(msg){
          const box = el('#optinOut'); if(!box) return;
          box.innerHTML += (box.innerHTML ? '<br>' : '') + msg;
          box.scrollTop = box.scrollHeight;
        }

        async function fetchNearestStrip(){
          try{
            const r = await fetch(`${apiBase}/video/nearest-strip`, {credentials:'include'});
            if (r.ok){
              const j = await r.json();
              if (j && j.strip) return j.strip;
            } else {
              const t = await r.text();
              logOpt(`[nearest-strip ${r.status}] ${t || 'no body'}`);
            }
          }catch(e){ logOpt(`[nearest-strip error] ${e?.message||e}`); }
          return null;
        }

        async function fetchOpenSlots(istDate){
          try{
            const url = `${apiBase}/video/slots/nearby?date=${encodeURIComponent(istDate)}&tz=IST`;
            const r = await fetch(url, { credentials:'include', headers:{Accept:'application/json'} });
            if (r.ok){
              const j = await r.json();
              const s = Array.isArray(j?.slots) ? j.slots : [];
              return s;
            }
          }catch(_){}
          return [];
        }

        async function commitSlot(slotId, doctorId){
          await window.Csrf.ensure();
          const r = await fetch(`${apiBase}/video/slots/${slotId}/commit`, window.Csrf.opts('POST', { doctor_id: doctorId }));
          if (r.status === 409) return {ok:false, reason:'conflict'};
          if (!r.ok)           return {ok:false, reason:String(r.status)};
          return {ok:true};
        }

        function pickTargets(slots, pref, max){
          const allowed = new Set(hoursIST);
          const pool = slots.filter(s => allowed.has(Number(s.hour_24)) && String(s.status)==='open');
          const prefer = {
            primary_first: ['primary','bench'],
            bench_first:   ['bench','primary'],
            primary_only:  ['primary'],
            bench_only:    ['bench'],
          }[pref] || ['primary','bench'];

          const maxCount = (Number(max) && Number(max) > 0) ? Number(max) : Number.MAX_SAFE_INTEGER;
          const ordered = [];

          for (const h of hoursIST){
            for (const role of prefer){
              const candidates = pool.filter(s => Number(s.hour_24)===h && String(s.role)===role);
              for (const c of candidates){
                ordered.push(c);
                if (ordered.length >= maxCount) return ordered;
              }
            }
            if (ordered.length >= maxCount) break;
          }
          return ordered;
        }

        async function autoClaimTonight(){
          const doctorSel = document.querySelector('#doctor_id');
          const doctorId  = Number(doctorSel?.value || 0);
          const istDate   = String(el('#optin_date')?.value || '');
          let max         = Number(el('#optin_max')?.value);
          if (!Number.isFinite(max) || max <= 0) { max = Number.MAX_SAFE_INTEGER; }
          const pref      = String(el('#optin_pref')?.value || 'primary_first');
          const btn       = el('#btnOptin');

          if(!doctorId){ Swal?.fire({icon:'error',title:'Select a doctor',timer:1200,showConfirmButton:false}); return; }
          if(!istDate){ Swal?.fire({icon:'error',title:'Pick date',timer:1200,showConfirmButton:false}); return; }

          try{
            btn && (btn.disabled=true, btn.textContent='Claiming...');
            el('#optinOut').innerHTML = '';
            logOpt(`Loading open slots for ${istDate} (IST)...`);
            const slots = await fetchOpenSlots(istDate);
            logOpt(`Open slots found: ${slots.length}`);

            const targets = pickTargets(slots, pref, max);
            if(!targets.length){ logOpt('No suitable open slots found.'); Swal?.fire({icon:'info',title:'No open slots',timer:1100,showConfirmButton:false}); return; }

            const nearestStrip = await fetchNearestStrip();

            let okCount=0, failCount=0;
            for (const s of targets){
              const pinTxt = nearestStrip ? `Strip #${nearestStrip.id} - ${nearestStrip.name}` : 'Strip ?';
              const hh = h2((Number(s.hour_24)+6)%24);
                            logOpt(`Trying ${istDate} ${hh}:00 IST (${s.role}) @ ${pinTxt}`);
              const res = await commitSlot(s.id, doctorId);
              if(res.ok){ okCount++; logOpt(`Committed slot #${s.id} @ ${istDate} ${hh}:00 IST (${pinTxt})`); }
              else { failCount++; logOpt(`Failed (#${s.id}): ${res.reason}`); }
              if (typeof window._loadNightCoverage === 'function'){
                try{ await window._loadNightCoverage(); }catch(_){ }
              }
            }

            Swal?.fire({icon: okCount? 'success':'warning', title:`Committed ${okCount}/${targets.length}`, timer:1300, showConfirmButton:false});
          }catch(e){
            console.error(e);
            Swal?.fire({icon:'error',title:'Auto-claim failed',text:String(e?.message||e)});
          }finally{
            btn && (btn.disabled=false, btn.textContent='Auto-claim Tonight');
          }
        }

        document.addEventListener('DOMContentLoaded', ()=>{
          const today = (function(){ const d=new Date(); const tz='Asia/Kolkata'; try{ return new Date(d.toLocaleString('en-US',{timeZone:tz})); }catch{ return d; }})();
          const y=today.getFullYear(), m=String(today.getMonth()+1).padStart(2,'0'), da=String(today.getDate()).padStart(2,'0');
          el('#optin_date').value = `${y}-${m}-${da}`;
          el('#btnOptin')?.addEventListener('click', autoClaimTonight);
        });
      })();
    </script>
  @endif
</div>
@endsection
