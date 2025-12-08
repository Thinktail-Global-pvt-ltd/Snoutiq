{{-- resources/views/snoutiq/video-calling-schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Video Calling Schedule by Doctor';
  $readonly   = (bool) ($readonly ?? true);
  $isDebug    = request()->get('debug') === '1';
  $userId     = (int) request()->integer('user_id', auth()->id() ?? 0);  // <— used in all API URLs
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
@php
  $isDebugView = request()->boolean('debug');
  $stepStatus = $stepStatus ?? [];
@endphp
<div class="max-w-5xl mx-auto space-y-6 px-4 sm:px-6 lg:px-0">
  @if(request()->get('onboarding')==='1')
    @include('layouts.partials.onboarding-steps', [
      'active' => (int) (request()->get('step', 2)),
      'stepStatus' => $stepStatus
    ])
  @endif

  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-lg font-semibold tracking-tight">Doctor Weekly Availability (Video)</h2>
      <p class="text-sm text-gray-600 mt-1">This view uses a separate storage and API. Existing flows remain unchanged.</p>
    </div>
    <div class="flex items-center gap-2 text-xs">
      <span class="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">IST only</span>
      @if($readonly)
        <span class="px-2 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200">Read-only</span>
      @endif
    </div>
  </div>

  {{-- =================== Doctor & Settings =================== --}}
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4 sm:p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
      <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Select Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          <option value="">-- Select a doctor --</option>
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

      {{-- 24/7 toggle (debug only) --}}
      @if(request()->get('debug')==='1')
        <div class="sm:col-span-2 lg:col-span-2">
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
      @endif
    </div>
  </div>

  {{-- ===== Video Consultation Availability Builder ===== --}}
  @php
    $videoDaySlots = [
      '06:00-08:00', '08:00-10:00', '10:00-12:00',
      '12:00-14:00', '14:00-16:00', '16:00-18:00',
    ];
    $videoNightSlots = [
      '18:00-20:00', '20:00-22:00', '22:00-00:00',
      '00:00-02:00', '02:00-04:00', '04:00-06:00',
    ];
    $videoDowLabels = [
      ['idx'=>0,'label'=>'Sun'],
      ['idx'=>1,'label'=>'Mon'],
      ['idx'=>2,'label'=>'Tue'],
      ['idx'=>3,'label'=>'Wed'],
      ['idx'=>4,'label'=>'Thu'],
      ['idx'=>5,'label'=>'Fri'],
      ['idx'=>6,'label'=>'Sat'],
    ];
  @endphp

  <div id="videoSlotBuilder"
       class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4 sm:p-6 space-y-6"
       data-readonly="{{ $readonly ? 'true' : 'false' }}">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4"
         @unless($isDebugView) style="display:none" @endunless>
      <div>
        <h3 class="text-xl font-semibold text-gray-900">Video Consultation Availability</h3>
        <p class="text-sm text-gray-600 mt-1">
          Select the consultation slots you want to offer. Each slot is 2 hours. We're pre-filling
          the weekly schedule below based on your selection.
        </p>
      </div>
      <div class="text-sm text-gray-600 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg px-3 py-2">
        <div class="font-medium text-indigo-800">At a glance</div>
        <div><span id="summaryDayHours" class="font-semibold text-indigo-900">0</span> hrs day</div>
        <div><span id="summaryNightHours" class="font-semibold text-indigo-900">0</span> hrs night</div>
        <div><span id="summaryActiveDays" class="font-semibold text-indigo-900">0</span> active days</div>
      </div>
    </div>

    {{-- Day hours --}}
    <section>
      <h4 class="text-lg font-semibold text-gray-900">Day Hours (6 AM - 6 PM)</h4>
      <p class="text-sm text-gray-500 mt-1 mb-4">Select at least 2 hours (each slot is 2 hours).</p>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2" id="daySlotsGrid">
        @foreach($videoDaySlots as $slot)
          <button type="button"
                  class="slot-chip"
                  data-slot-button
                  data-period="day"
                  data-slot="{{ $slot }}">
            {{ $slot }}
          </button>
        @endforeach
      </div>
      <div class="mt-2 text-sm text-gray-600">
        Selected: <span id="selectedDayHours">0</span> hours
        <span id="daySlotHint" class="text-rose-600 hidden">(minimum 2 hours required)</span>
      </div>
    </section>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
      <div>
        <label for="dayRate" class="block text-sm font-medium text-gray-700 mb-1">
          Day Consultation Rate (per session)
        </label>
        <input type="number"
               id="dayRate"
               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
               placeholder="500"
               {{ $readonly ? 'disabled' : '' }}>
      </div>
      <div>
        <label for="nightRate" class="block text-sm font-medium text-gray-700 mb-1">
          Night Consultation Rate (per session)
        </label>
        <input type="number"
               id="nightRate"
               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
               placeholder="750"
               {{ $readonly ? 'disabled' : '' }}>
      </div>
    </div>

    {{-- Night hours --}}
    <section>
      <h4 class="text-lg font-semibold text-gray-900">Night Hours (6 PM - 6 AM)</h4>
      <p class="text-sm text-gray-500 mt-1 mb-4">Select at least 2 hours (each slot is 2 hours).</p>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2" id="nightSlotsGrid">
        @foreach($videoNightSlots as $slot)
          <button type="button"
                  class="slot-chip"
                  data-slot-button
                  data-period="night"
                  data-slot="{{ $slot }}">
            {{ $slot }}
          </button>
        @endforeach
      </div>
      <div class="mt-2 text-sm text-gray-600">
        Selected: <span id="selectedNightHours">0</span> hours
        <span id="nightSlotHint" class="text-rose-600 hidden">(minimum 2 hours required)</span>
      </div>
    </section>

    {{-- Days --}}
    <section class="border-t border-gray-200 pt-4">
      <label class="flex items-center gap-3 cursor-pointer select-none">
        <input type="checkbox"
               id="applyScheduleAllDays"
               class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
               checked
               {{ $readonly ? 'disabled' : '' }}>
        <span class="text-sm font-medium text-gray-800">Apply this schedule to all days of the week</span>
      </label>
      <p class="text-xs text-gray-500 ml-7 mt-1">
        You can always fine-tune day level availability later from the dashboard.
      </p>

      <div class="mt-4 space-y-2">
        <div class="text-sm font-medium text-gray-700">Active Days</div>
        <div id="dayChipList" class="flex flex-wrap gap-2">
          @foreach($videoDowLabels as $dow)
            <button type="button"
                    class="day-chip"
                    data-day-chip="{{ $dow['idx'] }}">
              {{ $dow['label'] }}
            </button>
          @endforeach
        </div>
        <p id="dayChipHint" class="text-xs text-rose-600 hidden">Select at least one day.</p>
      </div>
    </section>

    <div id="slotValidation" class="text-sm font-medium text-rose-600 hidden"></div>
  </div>

  {{-- ====== CTA highlight styles (for the night-hours button) ====== --}}
  <style>
    .slot-chip{padding:.75rem; border:1px solid #d1d5db; border-radius:.75rem; font-size:.875rem; font-weight:600; color:#374151; background:#fff; transition:all .2s ease; box-shadow:0 1px 2px rgba(15,23,42,.05); cursor:pointer;}
    .slot-chip:hover{border-color:#4f46e5; color:#312e81;}
    .slot-chip.is-active{background:#2563eb; color:#fff; border-color:#2563eb; box-shadow:0 4px 10px rgba(37,99,235,.25);}
    .slot-chip.is-night{background:#4338ca; color:#fff; border-color:#4338ca;}
    .slot-chip.is-night:not(.is-active){background:#fff; color:#374151; border-color:#d1d5db;}
    .slot-chip.is-night:hover{border-color:#6366f1; color:#312e81;}
    .slot-chip[disabled]{opacity:.5; cursor:not-allowed;}
    .day-chip{padding:.5rem .95rem; border-radius:9999px; border:1px solid #d1d5db; font-size:.75rem; font-weight:600; color:#4b5563; background:#fff; transition:all .2s ease; cursor:pointer;}
    .day-chip.is-active{background:#059669; border-color:#059669; color:#fff; box-shadow:0 3px 8px rgba(5,150,105,.25);}
    .day-chip[disabled]{opacity:.45; cursor:not-allowed;}
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
    
    /* Responsive table styles */
    .schedule-table-container {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    
    @media (max-width: 1024px) {
      .schedule-table {
        min-width: 800px;
      }
    }
    
    @media (max-width: 640px) {
      .schedule-table {
        min-width: 700px;
      }
      
      .slot-chip {
        padding: 0.6rem 0.5rem;
        font-size: 0.8rem;
      }
      
      .day-chip {
        padding: 0.4rem 0.7rem;
        font-size: 0.7rem;
      }
    }
  </style>

  {{-- =================== Near Me (PINCODE only) -- Title & date hidden =================== --}}
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4 sm:p-6">
    <div class="flex items-center justify-end mb-2">
      <div class="relative inline-block" id="nightCtaWrap" @unless($isDebugView) style="display:none" @endunless>
        <span class="ctaGlow" id="nightGlow" aria-hidden="true"></span>
        <span class="ctaPulse" id="nightPulse" aria-hidden="true"></span>

        <button id="btnNearFindPin"
                class="relative z-10 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-lg focus:outline-none focus:ring-4 focus:ring-emerald-300 transition transform hover:scale-[1.02] active:scale-[0.99]">
          Are you available at night hours <span class="ctaBounce ml-1">🌙</span>
        </button>

        <span class="ctaArrow hidden md:block" id="nightArrow">click -></span>
      </div>
    </div>

    <div id="nearMeta" class="text-xs text-gray-600" @unless($isDebugView) style="display:none" @endunless></div>
    <div id="nearSlots" class="mt-2 text-sm" @unless($isDebugView) style="display:none" @endunless></div>
  </div>

  {{-- =================== Weekly Schedule Form =================== --}}
  <fieldset class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4 sm:p-6">
    <legend class="sr-only">Weekly Schedule</legend>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 gap-3">
      <h3 class="text-sm font-semibold text-gray-800">Weekly Schedule</h3>
      <button id="btnEditNightSlots"
              class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-300 w-full sm:w-auto">
        Edit Night Slots
      </button>
    </div>
    
    <div class="schedule-table-container">
      <table class="w-full border border-gray-200 rounded-lg overflow-hidden schedule-table">
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
    </div>

    <div class="mt-2 text-xs text-gray-500" id="metaNote"></div>
    @if(!$readonly)
      <button id="btnSave" class="mt-3 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 w-full sm:w-auto" disabled>Save Weekly Availability</button>
      <p id="saveHint" class="mt-2 text-xs text-gray-500">Select a doctor to enable saving</p>
    @endif
    <div id="saveOut" class="mt-2 text-sm"></div>
  </fieldset>

  {{-- Night slot modal --}}
  <div id="nightModal" class="hidden fixed inset-0 z-40">
    <div class="absolute inset-0 bg-gray-900/60" data-night-modal-dismiss></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl ring-1 ring-gray-200">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
          <div>
            <h3 class="text-base font-semibold text-gray-900">Manage Night Slots</h3>
            <p class="text-xs text-gray-500">Remove a recurring night slot by clicking the cross.</p>
          </div>
          <button type="button" class="text-gray-500 hover:text-gray-700 text-xl leading-none" data-night-modal-dismiss aria-label="Close">&times;</button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto">
          <div id="nightModalList" class="p-4 space-y-3 text-sm text-gray-700"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4 sm:p-6" @unless($isDebugView) style="display:none" @endunless>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" id="sched_date" value="{{ date('Y-m-d') }}" class="mt-1 w-full rounded-lg border-gray-300">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Days (repeat)</label>
        <input type="number" id="sched_days" min="1" max="60" value="7" class="mt-1 w-full rounded-lg border-gray-300" title="How many days to preview using weekly pattern">
      </div>
      <div class="flex items-end">
        <button id="btnLoadSlots" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-gray-800 text-white hover:bg-gray-900">Load Free Slots</button>
      </div>
      <div class="text-sm text-gray-500 flex items-center">Shows free slots from new table</div>
    </div>
    <div id="slotOut" class="mt-3 text-sm"></div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- ===== Global base + CSRF + USER_ID (shared by all panels) ===== --}}
  <script>
    (function(){
      const ORIGIN   = window.location.origin;
      const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
      const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;

      // make available globally
      window.SN_API_BASE = apiBase;
      window.SN_USER_ID  = Number(new URLSearchParams(location.search).get('user_id') || {{ $userId }}) || 0;
      window.SN_CLINIC_ID = Number(@json($clinicId ?? null)) || null;

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

      // ===== utils =====
      const el  = (s) => document.querySelector(s);
      const els = (s) => Array.from(document.querySelectorAll(s));
      const toast = (m, ok=true)=>{ if (window.Swal) Swal.fire({toast:true,position:'top',timer:1200,showConfirmButton:false,icon:ok?'success':'error',title:String(m)}); };
      function fmt(v){ try{ return typeof v==='string'? v : JSON.stringify(v,null,2);}catch{ return String(v);} }
      function out(sel, payload, ok=true){ const d = el(sel); if(d){ d.innerHTML = `<pre style="white-space:pre-wrap">${fmt(payload)}</pre>`; d.className = ok? 'mt-2 text-sm text-green-700':'mt-2 text-sm text-red-700'; } }
      function getDoctorId(){ 
        const v = el('#doctor_id')?.value;
        const num = Number(v);
        return Number.isFinite(num)&&num>0? num:null; 
      }
      function getClinicId(){ return window.SN_CLINIC_ID; }
      function getUserId(){ return Number(window.SN_USER_ID || 0); }

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
      const NIGHT_STATE = { aggregated: [], slotsByDow: {}, errors: [], total: 0, uniqueDetails: [], uniqueMap: new Map() };
      const pad2 = (n)=>String(n).padStart(2,'0');

      // ===== video slot builder state =====
      const DAY_SLOT_DEFS = [
        { id:'06:00-08:00', start:'06:00', end:'08:00' },
        { id:'08:00-10:00', start:'08:00', end:'10:00' },
        { id:'10:00-12:00', start:'10:00', end:'12:00' },
        { id:'12:00-14:00', start:'12:00', end:'14:00' },
        { id:'14:00-16:00', start:'14:00', end:'16:00' },
        { id:'16:00-18:00', start:'16:00', end:'18:00' },
      ];
      const NIGHT_SLOT_DEFS = [
        { id:'18:00-20:00', start:'18:00', end:'20:00' },
        { id:'20:00-22:00', start:'20:00', end:'22:00' },
        { id:'22:00-00:00', start:'22:00', end:'24:00' },
        { id:'00:00-02:00', start:'00:00', end:'02:00' },
        { id:'02:00-04:00', start:'02:00', end:'04:00' },
        { id:'04:00-06:00', start:'04:00', end:'06:00' },
      ];
      const SLOT_DEFS = { day: DAY_SLOT_DEFS, night: NIGHT_SLOT_DEFS };
      const SLOT_MAP = new Map();
      const SLOT_TIME_LOOKUP = new Map();
      Object.entries(SLOT_DEFS).forEach(([period, list])=>{
        list.forEach(def=>{
          def.period = period;
          def.display = def.id;
          let storageEnd = def.end === '24:00' ? '23:59' : def.end;
          const startMinInit = Number(def.start.slice(0,2)) * 60 + Number(def.start.slice(3,5));
          let endMinInit;
          if(storageEnd === '23:59'){
            endMinInit = 23*60 + 59;
          }else{
            endMinInit = Number(storageEnd.slice(0,2)) * 60 + Number(storageEnd.slice(3,5));
          }
          if(endMinInit <= startMinInit){
            storageEnd = '23:59';
          }
          def.storageEnd = storageEnd;
          SLOT_MAP.set(def.id, def);
          SLOT_TIME_LOOKUP.set(`${def.start}-${storageEnd}`, { period, id: def.id });
        });
      });
      const ALL_DAYS = [0,1,2,3,4,5,6];
      const DAY_LABELS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      const slotState = {
        day: new Set(),
        night: new Set(),
        selectedDays: new Set(ALL_DAYS),
        applyAll: true,
      };
      let slotControlsLocked = false;
      const slotEls = {
        builder: el('#videoSlotBuilder'),
        dayButtons: els('[data-slot-button][data-period="day"]'),
        nightButtons: els('[data-slot-button][data-period="night"]'),
        summaryDay: el('#summaryDayHours'),
        summaryNight: el('#summaryNightHours'),
        summaryDays: el('#summaryActiveDays'),
        selectedDayHours: el('#selectedDayHours'),
        selectedNightHours: el('#selectedNightHours'),
        dayHint: el('#daySlotHint'),
        nightHint: el('#nightSlotHint'),
        validation: el('#slotValidation'),
        applyAll: el('#applyScheduleAllDays'),
        dayChipHint: el('#dayChipHint'),
        dayChips: els('[data-day-chip]'),
        dayRate: el('#dayRate'),
        nightRate: el('#nightRate'),
        dayChipList: el('#dayChipList'),
        metaNote: el('#metaNote'),
      };
      const SLOT_BUTTON_ALL = [...slotEls.dayButtons, ...slotEls.nightButtons];

      function slotBuilderActive(){ return Boolean(slotEls.builder); }
      function timeToMinutes(str){
        if(!str) return 0;
        const [h,m] = str.split(':').map(Number);
        return (Number.isFinite(h)?h:0)*60 + (Number.isFinite(m)?m:0);
      }
      function minutesToTime(min){
        const clamped = Math.max(0, Math.min(1439, Number(min)||0));
        const h = Math.floor(clamped/60);
        const m = clamped % 60;
        return `${pad2(h)}:${pad2(m)}`;
      }
      function ensureSlotView(parent, key, extraClass=''){
        if(!parent) return null;
        let node = parent.querySelector(`[data-slot-view="${key}"]`);
        if(!node){
          node = document.createElement('div');
          node.dataset.slotView = key;
          node.className = extraClass || 'mt-2 flex flex-wrap gap-1 text-xs';
          parent.appendChild(node);
        }
        return node;
      }
      function slotEndMinutes(def){
        const startMin = timeToMinutes(def.start);
        let endMin;
        const rawEnd = (def.end === '24:00') ? '23:59' : (def.storageEnd || def.end);
        endMin = timeToMinutes(rawEnd);
        if(endMin <= startMin){
          endMin = Math.min(startMin + 120, 1439);
        }
        return endMin;
      }
      function slotEndString(def){
        return minutesToTime(slotEndMinutes(def));
      }
      function getSelectedDefs(){
        const list = [];
        slotState.day.forEach(id=>{ const def = SLOT_MAP.get(id); if(def) list.push(def); });
        slotState.night.forEach(id=>{ const def = SLOT_MAP.get(id); if(def) list.push(def); });
        return list.sort((a,b)=> timeToMinutes(a.start) - timeToMinutes(b.start));
      }
      function refreshSlotButtons(){
        if(!slotBuilderActive()) return;
        slotEls.dayButtons.forEach(btn=>{
          const id = btn.dataset.slot;
          const on = slotState.day.has(id);
          btn.classList.toggle('is-active', on);
          if(READONLY || slotControlsLocked) btn.setAttribute('disabled','disabled'); else btn.removeAttribute('disabled');
        });
        slotEls.nightButtons.forEach(btn=>{
          const id = btn.dataset.slot;
          const on = slotState.night.has(id);
          btn.classList.add('is-night');
          btn.classList.toggle('is-active', on);
          if(READONLY || slotControlsLocked) btn.setAttribute('disabled','disabled'); else btn.removeAttribute('disabled');
        });
      }
      function refreshDayChips(){
        if(!slotBuilderActive()) return;
        slotEls.dayChips.forEach(btn=>{
          const dow = Number(btn.dataset.dayChip);
          const active = slotState.selectedDays.has(dow);
          btn.classList.toggle('is-active', active);
          if(slotState.applyAll || READONLY || slotControlsLocked){
            btn.setAttribute('disabled','disabled');
          }else{
            btn.removeAttribute('disabled');
          }
        });
      }
      function refreshSummaries(){
        if(!slotBuilderActive()) return;
        const dayHours = slotState.day.size * 2;
        const nightHours = slotState.night.size * 2;
        const activeDays = slotState.selectedDays.size;
        if(slotEls.summaryDay) slotEls.summaryDay.textContent = dayHours;
        if(slotEls.summaryNight) slotEls.summaryNight.textContent = nightHours;
        if(slotEls.summaryDays) slotEls.summaryDays.textContent = activeDays;
        if(slotEls.selectedDayHours) slotEls.selectedDayHours.textContent = dayHours;
        if(slotEls.selectedNightHours) slotEls.selectedNightHours.textContent = nightHours;
      }
      function validateSlotState(){
        if(!slotBuilderActive()) return null;
        if(slotState.day.size < 1) return 'Select at least one day-hour slot (2 hours).';
        if(slotState.night.size < 1) return 'Select at least one night-hour slot (2 hours).';
        if(slotState.selectedDays.size < 1) return 'Select at least one active day.';
        return null;
      }
      function refreshValidation(){
        if(!slotBuilderActive()) return;
        const error = validateSlotState();
        if(slotEls.validation){
          if(error){
            slotEls.validation.textContent = error;
            slotEls.validation.classList.remove('hidden');
          }else{
            slotEls.validation.textContent = '';
            slotEls.validation.classList.add('hidden');
          }
        }
        if(slotEls.dayHint){
          slotEls.dayHint.classList.toggle('hidden', slotState.day.size >= 1);
        }
        if(slotEls.nightHint){
          slotEls.nightHint.classList.toggle('hidden', slotState.night.size >= 1);
        }
        if(slotEls.dayChipHint){
          slotEls.dayChipHint.classList.toggle('hidden', slotState.selectedDays.size >= 1);
        }
      }
      function getSelectedDefsByPeriod(period){
        const collection = [];
        const pool = slotState[period];
        if(!pool) return collection;
        pool.forEach(id=>{
          const def = SLOT_MAP.get(id);
          if(def && def.period === period){
            collection.push(def);
          }
        });
        return collection.sort((a,b)=> timeToMinutes(a.start) - timeToMinutes(b.start));
      }
      function syncTablePreview(){
        if(!slotBuilderActive()) return;
        const dayDefs = getSelectedDefsByPeriod('day');
        const nightDefs = getSelectedDefsByPeriod('night');
        const dayEarliest = dayDefs.length ? Math.min(...dayDefs.map(def=>timeToMinutes(def.start))) : null;
        const dayLatest = dayDefs.length ? Math.max(...dayDefs.map(def=>slotEndMinutes(def))) : null;
        const nightEarliest = nightDefs.length ? Math.min(...nightDefs.map(def=>timeToMinutes(def.start))) : null;
        const nightLatest = nightDefs.length ? Math.max(...nightDefs.map(def=>slotEndMinutes(def))) : null;
        const allNightLabels = nightDefs.map(def=>def.display);
        const rows = els('tbody tr[data-dow]');
        rows.forEach(tr=>{
          const dow = Number(tr.dataset.dow);
          const active = slotState.selectedDays.has(dow);
          const activeInput = tr.querySelector('.active');
          const startInput = tr.querySelector('.start');
          const endInput = tr.querySelector('.end');
          const breakStart = tr.querySelector('.break_start');
          const breakEnd = tr.querySelector('.break_end');
          const nightCell = tr.querySelector('.night-slots');
          const dayParent = startInput ? startInput.parentElement : null;
          const dayView = ensureSlotView(dayParent, 'day', 'mt-1 flex flex-wrap gap-1 text-xs');
          if(startInput){
            startInput.classList.add('hidden');
          }
          if(endInput){
            endInput.classList.add('hidden');
          }
          if(activeInput){
            activeInput.checked = active;
            activeInput.disabled = true;
          }
          [startInput,endInput,breakStart,breakEnd].forEach(inp=>{
            if(!inp) return;
            inp.value = '';
            inp.readOnly = true;
            inp.disabled = true;
          });
          if(dayView){
            dayView.innerHTML = active
              ? (dayDefs.length
                    ? `<div class="w-full text-[11px] text-gray-500 mb-1">Day slots</div>${dayDefs.map(def=>`<span class="inline-flex items-center px-2 py-0.5 rounded-full border border-blue-200 bg-blue-50 text-blue-700">${def.display}</span>`).join('')}`
                    : '<span class="text-xs text-gray-400">No day slots picked</span>')
              : '<span class="text-xs text-gray-300">Inactive</span>';
          }
          if(active){
            const startCandidates = [];
            if(dayEarliest !== null) startCandidates.push(dayEarliest);
            if(nightEarliest !== null) startCandidates.push(nightEarliest);
            const endCandidates = [];
            if(dayLatest !== null) endCandidates.push(dayLatest);
            if(nightLatest !== null) endCandidates.push(nightLatest);
            const startVal = startCandidates.length ? minutesToTime(Math.min(...startCandidates)) : '';
            const endVal = endCandidates.length ? minutesToTime(Math.max(...endCandidates)) : '';
            if(startInput) startInput.value = startVal;
            if(endInput)   endInput.value   = endVal;
          }
          if(nightCell){
            nightCell.innerHTML = active
              ? (nightDefs.length
                    ? `<div class="text-[11px] text-gray-500 mb-1">Night slots</div>${allNightLabels.map(label=>`<span class="inline-flex items-center px-2 py-0.5 rounded-full border border-indigo-200 bg-indigo-50 text-indigo-700 mr-1 mb-1">${label}</span>`).join('')}`
                    : '<span class="text-xs text-gray-400">No night slots picked</span>')
              : '<span class="text-xs text-gray-300">Inactive</span>';
          }
        });
        if(slotEls.metaNote){
          const baseText = slotState.applyAll
            ? 'Applying the same slot selection to all seven days.'
            : `Active days: ${Array.from(slotState.selectedDays).sort((a,b)=>a-b).map(idx=>DAY_LABELS[idx]).join(', ')}.`;
          const daySummary = dayDefs.length ? `${dayDefs.length} day slot${dayDefs.length>1?'s':''}` : 'no day slots';
          const nightSummary = nightDefs.length ? `${nightDefs.length} night slot${nightDefs.length>1?'s':''}` : 'no night slots';
          const dayLabels = dayDefs.length ? dayDefs.map(def=>def.display).join(', ') : '–';
          const nightLabels = nightDefs.length ? nightDefs.map(def=>def.display).join(', ') : '–';
          slotEls.metaNote.innerHTML = [
            baseText,
            `Selected ${daySummary} and ${nightSummary}.`,
            `<span class="text-gray-600">Day slots:</span> <span class="font-medium text-gray-800">${dayLabels}</span>`,
            `<span class="text-gray-600">Night slots:</span> <span class="font-medium text-gray-800">${nightLabels}</span>`
          ].join('<br>');
        }
      }
      function refreshSlotBuilder(){
        if(!slotBuilderActive()) return;
        refreshSlotButtons();
        refreshDayChips();
        if(slotEls.applyAll){
          slotEls.applyAll.checked = slotState.applyAll;
          if(READONLY || slotControlsLocked){
            slotEls.applyAll.setAttribute('disabled','disabled');
          }else{
            slotEls.applyAll.removeAttribute('disabled');
          }
        }
        refreshSummaries();
        refreshValidation();
        syncTablePreview();
      }
      function toggleSlot(period, slotId){
        if(READONLY || !slotBuilderActive()) return;
        const target = slotState[period];
        if(!target) return;
        if(target.has(slotId)){ target.delete(slotId); }
        else { target.add(slotId); }
        refreshSlotBuilder();
      }
      function setApplyAllDays(on){
        if(!slotBuilderActive()) return;
        slotState.applyAll = !!on;
        if(slotEls.applyAll){
          slotEls.applyAll.checked = !!on;
        }
        if(slotState.applyAll){
          slotState.selectedDays = new Set(ALL_DAYS);
        }else if(!slotState.selectedDays.size){
          slotState.selectedDays.add(0);
        }
        refreshSlotBuilder();
      }
      function toggleDaySelection(dow){
        if(READONLY || !slotBuilderActive() || slotState.applyAll) return;
        if(slotState.selectedDays.has(dow)){
          slotState.selectedDays.delete(dow);
        }else{
          slotState.selectedDays.add(dow);
        }
        if(!slotState.selectedDays.size){
          slotState.selectedDays.add(dow);
        }
        refreshSlotBuilder();
      }
      function setAllSlotsSelected(on){
        if(!slotBuilderActive()) return;
        Object.keys(SLOT_DEFS).forEach(period=>{
          const set = slotState[period];
          set.clear();
          if(on){
            SLOT_DEFS[period].forEach(def=> set.add(def.id));
          }
        });
        refreshSlotBuilder();
      }
      function is247State(){
        if(!slotBuilderActive()) return false;
        return slotState.day.size === DAY_SLOT_DEFS.length &&
               slotState.night.size === NIGHT_SLOT_DEFS.length &&
               slotState.selectedDays.size === ALL_DAYS.length;
      }
      function buildAvailabilityPayload(){
        if(!slotBuilderActive()) return { availability: [], validationError: null };
        const avgMins = Number(el('#avg_consultation_mins')?.value || 20);
        const maxBph  = Number(el('#max_bph')?.value || 3);
        const dayList = Array.from(slotState.selectedDays).sort((a,b)=>a-b);
        const availability = [];
        const makeRow = (def, dow)=>({
          day_of_week: dow,
          start_time: `${def.start}:00`,
          end_time: `${slotEndString(def)}:00`,
          break_start: null,
          break_end: null,
          avg_consultation_mins: avgMins,
          max_bookings_per_hour: maxBph
        });
        dayList.forEach(dow=>{
          SLOT_DEFS.day.forEach(def=>{ if(slotState.day.has(def.id)) availability.push(makeRow(def, dow)); });
          SLOT_DEFS.night.forEach(def=>{ if(slotState.night.has(def.id)) availability.push(makeRow(def, dow)); });
        });
        availability.sort((a,b)=>{
          if(a.day_of_week !== b.day_of_week) return a.day_of_week - b.day_of_week;
          return a.start_time.localeCompare(b.start_time);
        });
        return { availability, validationError: validateSlotState() };
      }
      function applyAvailabilityToState(list){
        if(!slotBuilderActive()) return;
        slotState.day.clear();
        slotState.night.clear();
        const daySet = new Set();
        list.forEach(row=>{ daySet.add(Number(row.day_of_week)); });
        if(daySet.size){
          slotState.selectedDays = new Set(daySet);
          slotState.applyAll = daySet.size === ALL_DAYS.length;
        }else{
          slotState.selectedDays = new Set(ALL_DAYS);
          slotState.applyAll = true;
        }
        const sampleDow = daySet.size ? Math.min(...daySet) : 0;
        list.filter(row=>Number(row.day_of_week) === sampleDow).forEach(row=>{
          const start = String(row.start_time||'').slice(0,5);
          let end = String(row.end_time||'').slice(0,5);
          if(end === '24:00'){ end = '23:59'; }
          const match = SLOT_TIME_LOOKUP.get(`${start}-${end}`);
          if(match){
            slotState[match.period].add(match.id);
          }
        });
        refreshSlotBuilder();
      }

      function updateEditButtonLabel(){
        const btn = document.querySelector('#btnEditNightSlots');
        if(!btn) return;
        const count = Number(NIGHT_STATE.total || 0);
        btn.textContent = `Manage Night Slots (${count})`;
        btn.disabled = count === 0;
        btn.classList.toggle('opacity-50', count === 0);
        btn.classList.toggle('cursor-not-allowed', count === 0);
      }

      function renderNightSlotsCells(){
        if(slotBuilderActive()){
          const nightDefs = getSelectedDefsByPeriod('night');
          const nightLabels = nightDefs.map(def=>def.display);
          const rows = els('tbody tr[data-dow]');
          rows.forEach(tr=>{
            const cell = tr.querySelector('.night-slots');
            if(!cell) return;
            const active = slotState.selectedDays.has(Number(tr.getAttribute('data-dow')));
            cell.innerHTML = active
              ? (nightLabels.length
                    ? `<div class="text-[11px] text-gray-500 mb-1">Night slots</div>${nightLabels.map(label=>`<span class="inline-flex items-center px-2 py-0.5 rounded-full border border-indigo-200 bg-indigo-50 text-indigo-700 mr-1 mb-1">${label}</span>`).join('')}`
                    : '<span class="text-xs text-gray-400">No night slots picked</span>')
              : '<span class="text-xs text-gray-300">Inactive</span>';
          });
          return;
        }
        const rows = els('tbody tr[data-dow]');
        rows.forEach(tr=>{
          const cell = tr.querySelector('.night-slots');
          if(!cell) return;
          if(NIGHT_STATE.errors.length){
            cell.innerHTML = NIGHT_STATE.errors.map(msg=>`<div class="text-xs text-red-600">${msg}</div>`).join('');
            return;
          }
          if(!NIGHT_STATE.aggregated.length){
            cell.innerHTML = '<span class="text-xs text-gray-400">No night slots configured</span>';
            return;
          }
          const chipsHtml = NIGHT_STATE.aggregated.join('');
          cell.innerHTML = `<div class="text-[11px] font-medium text-gray-700 mb-1">Repeats daily</div><div class="flex flex-wrap">${chipsHtml}</div>`;
        });
      }

      async function handleNightAggregateDelete(key, trigger){
        const entry = NIGHT_STATE.uniqueMap.get(key);
        if(!entry) return;
        if(READONLY){
          toast('Read-only mode: cannot modify slots', false);
          return;
        }
        const doctorId = getDoctorId();
        const userId   = getUserId();
        if(!doctorId){ toast('Select a doctor first', false); return; }
        if(!entry.ids || !entry.ids.length){
          toast('No slots found for this entry', false);
          return;
        }

        const ask = await Swal.fire({
          title: 'Remove recurring night slot?',
          text: `This will release ${entry.ids.length} occurrence(s).`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, remove',
          cancelButtonText: 'Cancel',
        });
        if(!ask.isConfirmed) return;

        trigger && (trigger.disabled = true, trigger.textContent = 'Removing...');
        let failure = null;
        try{
          await window.Csrf.ensure();
          const slotId = entry.ids[0];
          const res = await fetch(`${apiBase}/video/slots/${slotId}/release?user_id=${encodeURIComponent(userId)}`, window.Csrf.opts('DELETE', { doctor_id: doctorId }));
          if(!res.ok){
            failure = await res.text() || `Failed to remove slot #${slotId}`;
          }
        }catch(e){
          failure = e?.message || String(e);
        }
        trigger && (trigger.disabled = false, trigger.textContent = 'Remove');

        if(failure){
          toast(failure, false);
        }else{
          toast('Slot removed', true);
          await loadNightScheduleTable();
          populateNightModal();
          if(!NIGHT_STATE.total){
            closeNightModal();
          }
        }
      }

      function populateNightModal(){
        const list = el('#nightModalList');
        if(!list) return;
        if(NIGHT_STATE.errors.length){
          list.innerHTML = NIGHT_STATE.errors.map(msg=>`<div class="text-sm text-red-600 border border-red-200 bg-red-50 rounded-lg px-3 py-2">${msg}</div>`).join('');
          return;
        }
        if(!NIGHT_STATE.uniqueDetails.length){
          list.innerHTML = '<div class="text-sm text-gray-500 border border-dashed border-gray-300 rounded-lg px-3 py-6 text-center">No night slots configured.</div>';
          return;
        }

        const rows = NIGHT_STATE.uniqueDetails.map(item=>{
          const count = item.ids?.length || 0;
          const occurrenceLabel = count > 1 ? `${count} occurrences` : '1 occurrence';
          const datePreview = (item.occurrences || []).slice(0, 3).map(o=>o.date_label).join(', ');
          const extra = datePreview ? `<div class="text-[11px] text-gray-500 mt-1">Examples: ${datePreview}${count > 3 ? ', …' : ''}</div>` : '';
          const stripLabel = item.strip_id ? `Strip #${item.strip_id}` : 'Strip ?';
          const roleLabel = item.role || '-';
          const statusLabel = item.status || 'unknown';
          const disabledAttr = READONLY ? 'disabled' : '';
          const disabledCls = READONLY ? 'opacity-40 cursor-not-allowed' : 'hover:bg-rose-600';
          return `
            <div class="border border-gray-200 rounded-xl px-3 py-2 flex items-start justify-between gap-3">
              <div>
                <div class="text-base font-semibold text-gray-900">${item.time_label}</div>
                <div class="text-xs text-gray-600">${roleLabel} · ${statusLabel} · ${stripLabel}</div>
                <div class="text-[11px] text-gray-500 mt-1">${occurrenceLabel}</div>
                ${extra}
              </div>
              <button type="button" class="px-2 py-1 rounded-md bg-rose-500 text-white text-xs font-semibold ${disabledCls}" data-night-bulk-remove="${item.key}" ${disabledAttr}>Remove</button>
            </div>
          `;
        }).join('');
        list.innerHTML = rows;
      }

      function openNightModal(){
        populateNightModal();
        const modal = el('#nightModal');
        if(!modal) return;
        modal.classList.remove('hidden');
      }

      function closeNightModal(){
        const modal = el('#nightModal');
        if(!modal) return;
        modal.classList.add('hidden');
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
          return (hour24 + 6 + 24) % 24; // back-compat if server returns UTC-ish hour
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
        const userId = getUserId();
        const url = `${apiBase}/video/slots/doctor?doctor_id=${doctorId}&user_id=${encodeURIComponent(userId)}&date=${encodeURIComponent(dateStr)}&tz=IST`;
        try{
          const res = await fetch(url, { credentials:'include', headers:{Accept:'application/json'} });
          const txt = await res.text();
          let json=null; try{ json = JSON.parse(txt);}catch{}
          if(!res.ok){
            return { date: dateStr, error:true, message: json?.message || txt || String(res.status), slots: [] };
          }
          // support both {slots:[]} and [] responses
          const list = Array.isArray(json?.slots) ? json.slots : (Array.isArray(json) ? json : []);
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
          NIGHT_STATE.total = 0;
          NIGHT_STATE.uniqueDetails = [];
          NIGHT_STATE.uniqueMap = new Map();
          renderNightSlotsCells();
          updateEditButtonLabel();
          return;
        }
        if(!baseDate){
          NIGHT_STATE.errors = ['Pick a date to view night slots'];
          NIGHT_STATE.aggregated = [];
          NIGHT_STATE.slotsByDow = {};
          NIGHT_STATE.total = 0;
          NIGHT_STATE.uniqueDetails = [];
          NIGHT_STATE.uniqueMap = new Map();
          renderNightSlotsCells();
          updateEditButtonLabel();
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
            // IST-night spans across midnight; map early-hours (0..6) onto the "next" date
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
              id: Number(slot.id ?? slot.slot_id ?? slot.uuid),
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
              let agg = uniqueChipMap.get(item.key);
              if(!agg){
                agg = {
                  key: item.key,
                  chip: item.chip,
                  ids: [],
                  time_label: item.time_label,
                  role: item.role,
                  status: item.status,
                  status_raw: item.status_raw,
                  strip_id: item.strip_id,
                  occurrences: [],
                  hour: item.hour,
                };
                uniqueChipMap.set(item.key, agg);
              }
              if(Number.isFinite(item.id)){
                agg.ids.push(Number(item.id));
              }
              agg.occurrences.push({ id: item.id, date_label: entry.label });
            });
          });
        });

        const uniqueChips = Array.from(uniqueChipMap.values()).sort((a,b)=> a.hour - b.hour || a.key.localeCompare(b.key));
        const aggregatedChips = uniqueChips.map(item => item.chip);
        uniqueChips.forEach(item => {
          item.occurrences.sort((a,b)=> a.date_label.localeCompare(b.date_label));
        });

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
        Array.from({length:7}).forEach((_,idx)=>{ if(!slotsByDow[idx]) slotsByDow[idx] = []; });

        NIGHT_STATE.errors = Array.from(new Set(aggregatedErrors));
        NIGHT_STATE.aggregated = aggregatedChips;
        NIGHT_STATE.total = aggregatedChips.length;
        NIGHT_STATE.slotsByDow = slotsByDow;
        NIGHT_STATE.uniqueDetails = uniqueChips;
        NIGHT_STATE.uniqueMap = new Map(uniqueChips.map(item => [item.key, item]));
        renderNightSlotsCells();
        updateEditButtonLabel();
      }

      // public
      window._loadNightScheduleTable = loadNightScheduleTable;

      // ===== 24/7 helpers =====
      function highlight247(){
        const wrap  = el('#enable247Wrap');
        const badge = el('#enable247Badge');
        const cb    = el('#enable247');
        if(!wrap || !cb) return;
        if(slotBuilderActive()){
          const shouldCheck = is247State();
          if(cb.checked !== shouldCheck){
            cb.checked = shouldCheck;
          }
        }
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
        if(slotBuilderActive()){
          slotControlsLocked = !!disabled;
          refreshSlotBuilder();
          return;
        }
        els('tbody tr[data-dow] input[type="time"], tbody tr[data-dow] input[type="checkbox"]').forEach(inp=>{
          if(inp.classList.contains('active')){ inp.disabled=false; } else { inp.disabled=!!disabled; }
        });
      }
      function apply247(on){
        if(slotBuilderActive()){
          if(on){
            setAllSlotsSelected(true);
            setApplyAllDays(true);
          }
          toggle247Inputs(on);
          return;
        }else{
          toggle247Inputs(on);
        }
      }

      // ===== availability load/save =====
      async function loadExisting(){
        const id = getDoctorId();
        if(!id){ out('#saveOut','Select a doctor',false); await loadNightScheduleTable(); return; }
        try{
          const res = await fetch(`${apiBase}/video-schedule/doctors/${id}/availability`, { credentials:'include', headers:{Accept:'application/json'} });
          const text = await res.text(); let json=null; try{ json = JSON.parse(text.replace(/^\uFEFF/,'')); }catch{}
          if(!res.ok){ out('#saveOut', text || 'Failed to load', false); await loadNightScheduleTable(); return; }
          const list = Array.isArray(json?.availability)? json.availability: [];
          const avg = document.querySelector('#avg_consultation_mins'); const bph = document.querySelector('#max_bph');
          if(list[0]){ if(avg) avg.value = Number(list[0].avg_consultation_mins||20); if(bph) bph.value = Number(list[0].max_bookings_per_hour||3); }

          if(slotBuilderActive()){
            applyAvailabilityToState(list);
          }else{
            const byDow = new Map();
            list.forEach(row=>{
              const dow = Number(row.day_of_week);
              if(!byDow.has(dow)) byDow.set(dow, []);
              byDow.get(dow).push(row);
            });
            els('tbody tr[data-dow]').forEach(tr=>{
              const dow = Number(tr.getAttribute('data-dow'));
              const rowsForDay = byDow.get(dow) || [];
              const row = rowsForDay[0] || null;
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
              let is247 = true;
              for(let d=0; d<7; d++){
                const rowsForDay = byDow.get(d) || [];
                if(!rowsForDay.length){ is247=false; break; }
                const coversFullDay = rowsForDay.every(r=>{
                  const st = String(r.start_time||'');
                  const en = String(r.end_time||'');
                  const noBreak = (!r.break_start && !r.break_end);
                  return st.startsWith('00:00') && (en >= '23:59:00' || en.startsWith('00:00')) && noBreak;
                });
                if(!coversFullDay){ is247=false; break; }
              }
              const cb247Legacy = document.querySelector('#enable247');
              if(cb247Legacy){ cb247Legacy.checked = !!is247; }
            }catch(_){}
          }

          highlight247();
          const cb247 = document.querySelector('#enable247');
          if(cb247){ toggle247Inputs(cb247.checked); }
          await loadNightScheduleTable();
        }catch(e){ out('#saveOut', `Load error: ${e?.message||e}`, false); await loadNightScheduleTable(); }
      }

      function collect(){
        if(slotBuilderActive()){
          const { availability, validationError } = buildAvailabilityPayload();
          return { availability, validationError };
        }
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
        const id = getDoctorId(); 
        if(!id){ alert('Select a doctor'); return; }
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

      // ===== Near (pincode) panel =====
      function getTodayIST(){
        const now = new Date();
        const ist = new Date(now.toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
        const p = n => String(n).padStart(2,'0');
        return `${ist.getFullYear()}-${p(ist.getMonth()+1)}-${p(ist.getDate())}`;
      }
      function chip(txt){ return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-gray-100 text-gray-700 border border-gray-200">${txt}</span>`; }
      function card(html){ return `<div class="border rounded-lg p-2 hover:shadow-sm transition">${html}</div>`; }

      function renderSlotsPin(list, strip){
        if (!list || !list.length) return '<div class="text-xs text-gray-500">No open slots.</div>';
        const pad2 = (n)=>String(n).padStart(2,'0');
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
                <button class="px-2 py-1 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-xs" onclick="window._commitNearSlot(this, ${s.id})">Commit</button>
              </div>
            </div>
          `);
        });
        html += '</div>';
        return html;
      }

      async function findNearByPincode(){
        el('#nearSlots').innerHTML = '<div class="animate-pulse text-xs text-gray-500">Loading open slots...</div>';
        const date = getTodayIST();
        const p = await fetch(`${apiBase}/geo/nearest-pincode`, {credentials:'include'});
        const jp = await p.json().catch(()=>({}));
        const code = jp?.pincode?.code || jp?.pincode?.pincode || jp?.pincode?.PIN || null;
        const label= jp?.pincode?.name || jp?.pincode?.label || '';
        const userId = getUserId();

        if(!code){
          el('#nearMeta').textContent = 'Nearest pincode not found.';
          el('#nearSlots').innerHTML = '';
          return;
        }

        const r = await fetch(`${apiBase}/video/slots/nearby/pincode?date=${encodeURIComponent(date)}&code=${encodeURIComponent(code)}&user_id=${encodeURIComponent(userId)}`, {credentials:'include'});
        const j = await r.json().catch(()=>({}));
        const allowedHours = new Set([19,20,21,22,23,0,1,2,3,4,5,6]);
        const filtered = (Array.isArray(j?.slots) ? j.slots : []).filter(s => allowedHours.has((Number(s.hour_24)+6)%24));
        el('#nearSlots').innerHTML = renderSlotsPin(filtered, j?.strip || {id:j?.strip_id, name:`Band-${j?.strip_id||''}`});
        el('#nearMeta').innerHTML = `Nearest pincode: <b>${code}</b> ${label? '('+label+')':''} - Band strip: <b>#${j?.strip_id||'?'}</b> - Found: <b>${filtered.length}</b> open slots`;
      }

      window._commitNearSlot = async function(button, slotId){
        try{
          await window.Csrf.ensure();
          const doctorId = Number(document.querySelector('#doctor_id')?.value || 0);
          const userId   = getUserId();
          if(!doctorId){ return Swal?.fire({icon:'error',title:'Select a doctor',timer:1200,showConfirmButton:false}); }

          if(button){
            if(button.dataset.loading === '1') return;
            button.dataset.loading = '1';
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.classList.add('cursor-wait','opacity-80');
            button.innerHTML = `<span class="flex items-center gap-1 justify-center">
              <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
            </span>`;
          }

          const r = await fetch(`${apiBase}/video/slots/${slotId}/commit?user_id=${encodeURIComponent(userId)}`, window.Csrf.opts('POST', { doctor_id: doctorId }));
          if (r.ok){
            Swal?.fire({icon:'success',title:'Committed!',timer:900,showConfirmButton:false});
            findNearByPincode();
            if (typeof window._loadNightCoverage === 'function'){ window._loadNightCoverage(); }
            await loadNightScheduleTable();
          }else{
            const t = await r.text();
            Swal?.fire({icon:'error',title:'Commit failed',text:t||String(r.status)});
          }
        }catch(e){
          Swal?.fire({icon:'error',title:'Commit error',text:String(e?.message||e)});
        }finally{
          if(button){
            button.disabled = false;
            button.classList.remove('cursor-wait','opacity-80');
            if(button.dataset.originalText){
              button.innerHTML = button.dataset.originalText;
            }else{
              button.textContent = 'Commit';
            }
            delete button.dataset.originalText;
            delete button.dataset.loading;
          }
        }
      };

      // ===== Wire up =====
      document.addEventListener('DOMContentLoaded', ()=>{
        // CTA button
        const stopCta = ()=>{ ['nightGlow','nightPulse','nightArrow'].forEach(id=>{ const n=document.getElementById(id); if(n) n.style.display='none'; }); };
        const btn = document.querySelector('#btnNearFindPin');
        btn?.addEventListener('click', ()=>{ stopCta(); findNearByPincode(); loadNightScheduleTable(); });
        btn?.addEventListener('mouseenter', stopCta);
        setTimeout(stopCta, 12000);

        if(slotBuilderActive()){
          refreshSlotBuilder();
          SLOT_BUTTON_ALL.forEach(button=>{
            button.addEventListener('click', ()=>{
              const period = button.dataset.period;
              const slotId = button.dataset.slot;
              toggleSlot(period, slotId);
            });
          });
          slotEls.dayChips.forEach(chip=>{
            chip.addEventListener('click', ()=>{
              toggleDaySelection(Number(chip.dataset.dayChip));
            });
          });
          if(slotEls.applyAll){
            slotEls.applyAll.addEventListener('change', (event)=>{
              if(READONLY){
                event.preventDefault();
                event.target.checked = slotState.applyAll;
                return;
              }
              setApplyAllDays(event.target.checked);
            });
          }
        }

        const doctorSelect = document.querySelector('#doctor_id');
        const btnSave = document.querySelector('#btnSave');
        const saveHint = document.querySelector('#saveHint');
        
        function updateSaveButton(){
          const selectedId = getDoctorId();
          if(btnSave){
            if(selectedId){
              btnSave.disabled = false;
              if(saveHint) saveHint.classList.add('hidden');
            } else {
              btnSave.disabled = true;
              if(saveHint) saveHint.classList.remove('hidden');
            }
          }
        }
        
        doctorSelect?.addEventListener('change', ()=>{
          const selectedId = getDoctorId();
          updateSaveButton();
          if(selectedId) {
            loadExisting();
          } else {
            // Clear the form when nothing is selected
            const avg = document.querySelector('#avg_consultation_mins');
            const bph = document.querySelector('#max_bph');
            if(avg) avg.value = 20;
            if(bph) bph.value = 3;
            // Clear schedule table
            els('tbody tr[data-dow]').forEach(tr=>{
              const $active=tr.querySelector('.active'), $start=tr.querySelector('.start'), $end=tr.querySelector('.end'), $bStart=tr.querySelector('.break_start'), $bEnd=tr.querySelector('.break_end');
              if($active) $active.checked = false;
              if($start) $start.value = '09:00';
              if($end) $end.value = '18:00';
              if($bStart) $bStart.value = '';
              if($bEnd) $bEnd.value = '';
            });
            loadNightScheduleTable();
          }
        });
        
        // Initial load
        updateSaveButton();
        const dd = document.querySelector('#doctor_id');
        if(dd && dd.options.length && dd.value){ 
          loadExisting(); 
        } else { 
          loadNightScheduleTable(); 
        }
        updateEditButtonLabel();

        if(!READONLY) document.querySelector('#btnSave')?.addEventListener('click', save);
        document.querySelector('#btnLoadSlots')?.addEventListener('click', loadSlots);
        document.querySelector('#sched_date')?.addEventListener('change', loadNightScheduleTable);
        document.querySelector('#sched_days')?.addEventListener('change', loadNightScheduleTable);

        const cb247 = document.querySelector('#enable247'); highlight247();
        if(!READONLY && cb247){
          cb247.addEventListener('change', (e)=>{ const on=!!e.target.checked; apply247(on); highlight247(); Swal?.fire({toast:true,position:'top',icon:on?'success':'info',title:on?'24/7 enabled':'24/7 disabled',timer:1200,showConfirmButton:false}); });
        }

        // Edit toggle (strictly UI toggle + refresh)
        const editBtn = document.querySelector('#btnEditNightSlots');
        editBtn?.addEventListener('click', async (evt)=>{
          if(editBtn.dataset.processing === '1') return;
          if(editBtn.disabled) return;
          editBtn.dataset.processing = '1';
          editBtn.disabled = true;
          editBtn.textContent = 'Loading...';
          try{
            await loadNightScheduleTable();
            openNightModal();
          }catch(e){
            console.error(e);
            toast(e?.message || 'Failed to load slots', false);
          }finally{
            editBtn.disabled = false;
            delete editBtn.dataset.processing;
            updateEditButtonLabel();
          }
        });

        document.addEventListener('click', (event)=>{
          const dismiss = event.target.closest('[data-night-modal-dismiss]');
          if(dismiss){
            event.preventDefault();
            closeNightModal();
            return;
          }
          const removeBtn = event.target.closest('[data-night-bulk-remove]');
          if(removeBtn){
            event.preventDefault();
            const key = removeBtn.getAttribute('data-night-bulk-remove');
            if(key){
              handleNightAggregateDelete(key, removeBtn);
            }
          }
        });

        document.addEventListener('keydown', (event)=>{
          if(event.key === 'Escape'){
            closeNightModal();
          }
        });
      });
    })();
  </script>

  {{-- =================== ADMIN/OPS PANELS -- visible only with ?debug=1 =================== --}}
  @if ($isDebug)
    {{-- (unchanged coverage & routing panels; not shown here again for brevity) --}}
    {{-- NOTE: If you use the "Auto-claim Tonight" inside debug, user_id is also sent in the commit URL. --}}
    <script>
      (function(){
        const apiBase = window.SN_API_BASE || `${window.location.origin}/api`;
        const hoursIST = [19,20,21,22,23,0,1,2,3,4,5,6];
        const el = (s)=>document.querySelector(s);
        const h2 = (n)=>String(n).padStart(2,'0');
        const getUserId = ()=> Number(window.SN_USER_ID || 0);

        async function fetchOpenSlots(istDate){
          try{
            const url = `${apiBase}/video/slots/nearby?date=${encodeURIComponent(istDate)}&tz=IST`;
            const r = await fetch(url, { credentials:'include', headers:{Accept:'application/json'} });
            if (r.ok){ const j = await r.json(); return Array.isArray(j?.slots) ? j.slots : []; }
          }catch(_){}
          return [];
        }
        async function commitSlot(slotId, doctorId){
          const userId = getUserId();
          await window.Csrf.ensure();
          const r = await fetch(`${apiBase}/video/slots/${slotId}/commit?user_id=${encodeURIComponent(userId)}`, window.Csrf.opts('POST', { doctor_id: doctorId }));
          if (r.status === 409) return {ok:false, reason:'conflict'};
          if (!r.ok)           return {ok:false, reason:String(r.status)};
          return {ok:true};
        }

        function pickTargets(slots, pref, max){
          const allowed = new Set(hoursIST);
          const pool = slots.filter(s => allowed.has(Number(s.hour_24)) && String(s.status)==='open');
          const prefer = { primary_first:['primary','bench'], bench_first:['bench','primary'], primary_only:['primary'], bench_only:['bench'] }[pref] || ['primary','bench'];
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
          if(!doctorId || !istDate) return;

          try{
            btn && (btn.disabled=true, btn.textContent='Claiming...');
            const slots = await fetchOpenSlots(istDate);
            const targets = pickTargets(slots, pref, max);
            for (const s of targets){ await commitSlot(s.id, doctorId); }
            if (typeof window._loadNightCoverage === 'function'){ await window._loadNightCoverage(); }
          }finally{
            btn && (btn.disabled=false, btn.textContent='Auto-claim Tonight');
          }
        }

        document.addEventListener('DOMContentLoaded', ()=>{
          el('#btnOptin')?.addEventListener('click', autoClaimTonight);
        });
      })();
    </script>
  @endif
</div>
@endsection
