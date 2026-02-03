{{-- resources/views/snoutiq/provider-schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Weekly Schedule')
@section('page_title','Weekly Schedule')

@section('content')
<div class="max-w-6xl mx-auto space-y-10">
  @php $stepStatus = $stepStatus ?? []; @endphp
  @if(request()->get('onboarding')==='1')
    @include('layouts.partials.onboarding-steps', [
      'active' => (int) (request()->get('step', 3)),
      'stepStatus' => $stepStatus,
    ])
  @endif

  <section class="rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
    <div class="flex flex-col gap-3 border-b border-slate-100 p-6 md:flex-row md:items-center md:justify-between md:p-8">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Configure the doctor roster</h2>
        <p class="text-sm text-slate-500">Tie availability to the right doctor profile and set default slot logic.</p>
      </div>
      <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-1.5 text-xs font-semibold text-indigo-600">
        In-clinic services are pre-selected for this setup
      </div>
    </div>
    <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-2 md:p-8">
      <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5 shadow-sm">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="doctor_id">Doctor</label>
        <select id="doctor_id" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 shadow-inner shadow-white/40 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          @if(isset($doctors) && $doctors->count())
            @foreach($doctors as $doc)
              <option value="{{ $doc->id }}" data-price="{{ $doc->doctors_price ?? '' }}">
                {{ $doc->doctor_name ?? $doc->name ?? 'Doctor' }}
              </option>
            @endforeach
          @else
            <option value="">No doctors found for your account</option>
          @endif
        </select>
        <div class="mt-2 text-xs text-slate-500" id="docIdNote">
          @if(!empty($vetId))
            Vet session ID: {{ $vetId }}
          @endif
        </div>
      </div>

      <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="doctor_price">Doctor price</label>
            <p class="text-xs text-slate-500">Applies to the doctor selected on the left.</p>
          </div>
          <button
            type="button"
            id="btnSavePrice"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
          >
            Update price
          </button>
        </div>
        <div class="relative mt-3">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">₹</span>
          <input
            type="number"
            min="0"
            step="1"
            id="doctor_price"
            class="mt-0 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pl-9 text-sm font-medium text-slate-800 shadow-inner shadow-white/40 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            placeholder="e.g. 500"
          >
        </div>
        <div class="mt-2 text-xs text-slate-500" id="priceStatus">No price saved yet.</div>
      </div>
    </div>
  </section>

  <section class="rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
    <div class="flex flex-col gap-3 border-b border-slate-100 p-6 md:flex-row md:items-center md:justify-between md:p-8">
      <div>
        <h3 class="text-lg font-semibold text-slate-900">Weekly clinic schedule</h3>
        <p class="text-sm text-slate-500">Set your default hours once — we’ll mirror them to weekdays, keep weekends off and auto-save.</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 rounded-full bg-slate-100 px-4 py-1.5 text-xs font-semibold text-slate-600">
          <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
          Saving happens automatically
        </div>
        <button
          type="button"
          id="btnManualSave"
          class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1"
        >
          Save & Continue
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
          </svg>
        </button>
      </div>
    </div>

    @php
      $days = [
        ['idx'=>1,'name'=>'Monday','weekend'=>false],
        ['idx'=>2,'name'=>'Tuesday','weekend'=>false],
        ['idx'=>3,'name'=>'Wednesday','weekend'=>false],
        ['idx'=>4,'name'=>'Thursday','weekend'=>false],
        ['idx'=>5,'name'=>'Friday','weekend'=>false],
        ['idx'=>6,'name'=>'Saturday','weekend'=>true],
        ['idx'=>0,'name'=>'Sunday','weekend'=>true],
      ];
    @endphp

    <div class="p-6 md:p-8">
      <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h4 class="text-base font-semibold text-slate-900">Default clinic hours</h4>
            <p class="text-xs text-slate-500">Fill these once — after we auto-save, weekday cards will unlock for fine tuning.</p>
          </div>
          <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600">
            <input type="checkbox" id="allow_weekends" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span>Allow weekend bookings</span>
          </label>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-4">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="default_start">Start</label>
            <input type="time" id="default_start" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="default_end">End</label>
            <input type="time" id="default_end" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="default_break_start">Break start</label>
            <input type="time" id="default_break_start" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="default_break_end">Break end</label>
            <input type="time" id="default_break_end" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          </div>
        </div>
      </div>

      <div id="weeklyCards" class="mt-6 space-y-6 hidden">
        @foreach($days as $d)
          <div class="js-day-card group relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-5 shadow-sm transition hover:border-indigo-200 hover:bg-white" data-dow="{{ $d['idx'] }}" data-weekend="{{ $d['weekend'] ? '1' : '0' }}">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="text-base font-semibold text-slate-900">{{ $d['name'] }}</div>
                <div class="text-xs text-slate-500">Craft availability for {{ strtolower($d['name']) }} visitors.</div>
              </div>
              <div class="flex items-center gap-3">
                <button type="button" class="text-xs font-semibold text-indigo-500 underline-offset-2 hover:underline" data-reset-default>Use default</button>
                <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-medium text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600">
                  <input type="checkbox" class="active h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" {{ $d['weekend'] ? '' : 'checked' }}>
                  <span>Active</span>
                </label>
              </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-4">
              <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Start</label>
                <input type="time" class="start mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
              </div>
              <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">End</label>
                <input type="time" class="end mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
              </div>
              <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Break start</label>
                <input type="time" class="break_start mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
              </div>
              <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Break end</label>
                <input type="time" class="break_end mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-inner focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
              </div>
            </div>

            <p class="mt-4 text-xs text-slate-500">Leave the break window blank to indicate continuous availability.</p>
          </div>
        @endforeach
      </div>
    </div>

    <div class="border-t border-slate-100 bg-slate-50/60 p-6 md:flex md:items-center md:justify-between md:p-8">
      <div class="max-w-lg text-xs text-slate-500" id="metaNote"></div>
      <div class="mt-4 text-xs text-slate-500 md:mt-0">
        As soon as you tweak the defaults we’ll copy them across weekdays, save and move you ahead.
      </div>
    </div>
    <div id="saveOut" class="px-6 pb-6 text-sm md:px-8"></div>
  </section>
</div>

<script>
  (function(){
    var s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'; document.head.appendChild(s);
  })();

  const ORIGIN   = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
  const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  const DEFAULT_SERVICE_TYPE = 'in_clinic';
  const DEFAULT_AVG_CONSULTATION_MINS = 20;

  const el  = (selector) => document.querySelector(selector);
  const els = (selector) => Array.from(document.querySelectorAll(selector));
  const defaultFields = {
    start:      el('#default_start'),
    end:        el('#default_end'),
    breakStart: el('#default_break_start'),
    breakEnd:   el('#default_break_end'),
    weekends:   el('#allow_weekends')
  };
  const priceField   = el('#doctor_price');
  const priceStatus  = el('#priceStatus');
  const priceButton  = el('#btnSavePrice');
  let priceDirty     = false;
  const setPriceStatus = (msg, ok = true) => {
    if (!priceStatus) return;
    priceStatus.textContent = msg || '';
    priceStatus.className = ok
      ? 'mt-2 text-xs text-emerald-600'
      : 'mt-2 text-xs text-red-600';
  };
  const setSelectedOptionPrice = (price) => {
    const opt = el('#doctor_id')?.selectedOptions?.[0];
    if (opt) opt.dataset.price = price ?? '';
  };
  const hydratePriceFromOption = () => {
    const opt = el('#doctor_id')?.selectedOptions?.[0];
    const raw = opt ? (opt.dataset.price ?? '') : '';
    const val = raw === '' || raw === undefined ? '' : raw;
    return val;
  };
  const primePriceField = (val) => {
    if (!priceField) return;
    priceField.value = (val ?? '') === null ? '' : val;
    priceDirty = false;
  };
  const togglePriceButton = (state) => {
    if (!priceButton) return;
    priceButton.disabled = !!state;
    priceButton.textContent = state ? 'Saving…' : 'Update price';
  };
  let avgConsultationMins = DEFAULT_AVG_CONSULTATION_MINS;
  const weeklyContainer = el('#weeklyCards');
  let weeklyVisible = weeklyContainer ? !weeklyContainer.classList.contains('hidden') : false;
  const hideWeekly = () => {
    if (!weeklyContainer) return;
    weeklyContainer.classList.add('hidden');
    weeklyVisible = false;
  };
  const revealWeekly = () => {
    if (!weeklyContainer) return;
    if (weeklyVisible) return;
    weeklyContainer.classList.remove('hidden');
    weeklyVisible = true;
  };
  const metaNoteEl = el('#metaNote');
  const setMetaNote = (msg) => {
    if (!metaNoteEl) return;
    metaNoteEl.textContent = msg || '';
  };

  let isBootstrapping = true;
  let autoSaveTimer   = null;
  let isSaving        = false;
  let pendingAutoSave = false;
  const AUTO_SAVE_DELAY = 700;

  const getDefaultHours = () => ({
    start:      defaultFields.start?.value || '',
    end:        defaultFields.end?.value || '',
    breakStart: defaultFields.breakStart?.value || '',
    breakEnd:   defaultFields.breakEnd?.value || ''
  });
  const shouldAllowWeekends = () => !!defaultFields.weekends?.checked;

  const setManual = (card, manual) => {
    if (!card) return;
    if (manual) {
      card.dataset.manual = '1';
    } else {
      delete card.dataset.manual;
    }
  };

  const setCardHours = (card, hours = {}) => {
    if (!card) return;
    const { start, end, breakStart, breakEnd } = hours;
    const assign = (selector, value) => {
      const input = card.querySelector(selector);
      if (input && value !== undefined) input.value = value ?? '';
    };
    assign('.start', start ?? '');
    assign('.end', end ?? '');
    assign('.break_start', breakStart ?? '');
    assign('.break_end', breakEnd ?? '');
  };

  const setCardToDefault = (card) => {
    const defaults = getDefaultHours();
    setCardHours(card, defaults);
  };

  const updateWeekendAppearance = () => {
    const allow = shouldAllowWeekends();
    els('.js-day-card').forEach(card => {
      if (card.dataset.weekend === '1') {
        const active = card.querySelector('.active');
        card.classList.toggle('opacity-60', !allow && !(active?.checked));
      }
    });
  };

  const applyDefaultHours = (force = false) => {
    const defaults = getDefaultHours();
    const allowWeekends = shouldAllowWeekends();

    els('.js-day-card').forEach(card => {
      const isWeekend = card.dataset.weekend === '1';
      const active = card.querySelector('.active');
      const manual = card.dataset.manual === '1';

      if (force || !manual) {
        setCardHours(card, defaults);
        if (!isWeekend || allowWeekends) {
          if (active) active.checked = true;
        } else if (active) {
          active.checked = false;
        }
        if (force) setManual(card, false);
      }

      if (isWeekend && !allowWeekends) {
        if (active && !manual) active.checked = false;
      }
    });

    updateWeekendAppearance();
  };

  const queueAutoSave = () => {
    if (isBootstrapping) return;
    const defaults = getDefaultHours();
    if (!defaults.start || !defaults.end) return;
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
      saveAvailability(true);
    }, AUTO_SAVE_DELAY);
  };

  const wireDayCard = (card) => {
    if (!card || card.dataset.wired === '1') return;
    card.dataset.wired = '1';

    const markManual = () => {
      setManual(card, true);
      if (!isBootstrapping) queueAutoSave();
    };

    card.querySelectorAll('input[type="time"]').forEach(input => {
      input.addEventListener('input', markManual);
      input.addEventListener('change', markManual);
    });

    const active = card.querySelector('.active');
    active?.addEventListener('change', markManual);

    const resetBtn = card.querySelector('[data-reset-default]');
    resetBtn?.addEventListener('click', () => {
      setManual(card, false);
      setCardToDefault(card);
      const isWeekend = card.dataset.weekend === '1';
      const allow = shouldAllowWeekends();
      if (active) {
        if (!isWeekend || allow) {
          active.checked = true;
        } else {
          active.checked = false;
        }
      }
      updateWeekendAppearance();
      if (!isBootstrapping) queueAutoSave();
    });
  };

  const toast = (msg, ok = true) => {
    if (window.Swal) {
      Swal.fire({ toast:true, position:'top', timer:1400, showConfirmButton:false, icon: ok ? 'success' : 'error', title:String(msg) });
    }
  };
  const toggleManualSaveButton = (loading = false) => {
    const btn = el('#btnManualSave');
    if (!btn) return;
    if (loading) {
      if (!btn.dataset.oldLabel) btn.dataset.oldLabel = btn.innerHTML;
      btn.disabled = true;
      btn.classList.add('opacity-80','cursor-not-allowed');
      btn.innerHTML = '<span class="flex items-center gap-2 justify-center"><span>Saving…</span><svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"></circle><path d="M4 12a8 8 0 018-8" stroke-width="4" class="opacity-75" stroke-linecap="round"></path></svg></span>';
    } else {
      if (btn.dataset.oldLabel) {
        btn.innerHTML = btn.dataset.oldLabel;
        delete btn.dataset.oldLabel;
      }
      btn.disabled = false;
      btn.classList.remove('opacity-80','cursor-not-allowed');
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
            html:'Set your weekly in-clinic hours. Saving these details will take you to emergency hours.',
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
      setMetaNote('Select a doctor to load availability.');
      hideWeekly();
      isBootstrapping = false;
      return;
    }
    const serviceType = DEFAULT_SERVICE_TYPE;

    try {
      isBootstrapping = true;
      const url  = `${apiBase}/doctors/${doctorId}/availability` + (serviceType ? `?service_type=${encodeURIComponent(serviceType)}` : '');
      const res  = await fetch(url, { headers: { Accept: 'application/json' } });
      const text = await res.text();
      let json   = null; try { json = JSON.parse(text.replace(/^\uFEFF/, '')); } catch {}
      if (!res.ok) { out('#saveOut', text || 'Failed to load availability', false); return; }

      const doctorPrice = (json && typeof json === 'object') ? json.doctor_price : null;
      if (priceField) {
        const fallbackPrice = hydratePriceFromOption();
        const priceToUse = doctorPrice ?? (fallbackPrice === '' ? '' : fallbackPrice);
        primePriceField(priceToUse);
        setPriceStatus(
          priceToUse !== '' && priceToUse !== null
            ? `Current price: ₹${priceToUse}`
            : 'No price saved yet.'
        );
        setSelectedOptionPrice(priceToUse);
      }

      const list  = Array.isArray(json?.availability) ? json.availability : [];
      const byDow = new Map(list.map(r => [Number(r.day_of_week), r]));
      const hasWeekend = list.some(r => [0,6].includes(Number(r.day_of_week)));
      if (defaultFields.weekends) defaultFields.weekends.checked = hasWeekend;

      if (list.length) {
        revealWeekly();
        setMetaNote(`Loaded ${list.length} of 7 days from server for "${serviceType}".`);
      } else {
        hideWeekly();
        setMetaNote('Defaults not saved yet — fill the block above to unlock weekday cards.');
      }

      let defaults = getDefaultHours();
      const baseline = list.find(r => ![0,6].includes(Number(r.day_of_week))) || list[0] || null;
      if (baseline) {
        const baseHours = {
          start: toHM(baseline.start_time) || '',
          end: toHM(baseline.end_time) || '',
          breakStart: toHM(baseline.break_start) || '',
          breakEnd: toHM(baseline.break_end) || ''
        };
        if (defaultFields.start) defaultFields.start.value = baseHours.start;
        if (defaultFields.end) defaultFields.end.value = baseHours.end;
        if (defaultFields.breakStart) defaultFields.breakStart.value = baseHours.breakStart;
        if (defaultFields.breakEnd) defaultFields.breakEnd.value = baseHours.breakEnd;
        defaults = baseHours;
      }

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
          const hours = {
            start: toHM(row.start_time) || defaults.start,
            end: toHM(row.end_time) || defaults.end,
            breakStart: toHM(row.break_start) || defaults.breakStart,
            breakEnd: toHM(row.break_end) || defaults.breakEnd
          };
          if ($start)  $start.value  = hours.start ?? '';
          if ($end)    $end.value    = hours.end ?? '';
          if ($bStart) $bStart.value = hours.breakStart ?? '';
          if ($bEnd)   $bEnd.value   = hours.breakEnd ?? '';
          const matchesDefaults =
            (hours.start || '') === (defaults.start || '') &&
            (hours.end || '') === (defaults.end || '') &&
            (hours.breakStart || '') === (defaults.breakStart || '') &&
            (hours.breakEnd || '') === (defaults.breakEnd || '');
          setManual(card, matchesDefaults ? false : true);
        } else {
          setManual(card, false);
          setCardToDefault(card);
          const isWeekend = card.dataset.weekend === '1';
          const allow = shouldAllowWeekends();
          if ($active) {
            if (!isWeekend || allow) {
              $active.checked = true;
            } else {
              $active.checked = false;
            }
          }
        }
      });

      updateWeekendAppearance();

      const first = list[0] ?? null;
      if (first) {
        const bph = el('#max_bph');
        avgConsultationMins = Number(first.avg_consultation_mins ?? DEFAULT_AVG_CONSULTATION_MINS);
        if (!Number.isFinite(avgConsultationMins) || avgConsultationMins <= 0) {
          avgConsultationMins = DEFAULT_AVG_CONSULTATION_MINS;
        }
        if (bph && first.max_bookings_per_hour != null) bph.value = Number(first.max_bookings_per_hour);
      } else {
        avgConsultationMins = DEFAULT_AVG_CONSULTATION_MINS;
      }
      if (!list.length) {
        const defaults = getDefaultHours();
        if (!defaults.start) defaultFields.start?.focus();
      }
    } catch (e) {
      out('#saveOut', `Load error: ${e?.message || e}`, false);
      console.error('[schedule] loadExistingAvailability error', e);
    } finally {
      isBootstrapping = false;
    }
  }

  function collectAvailability() {
    const serviceType = DEFAULT_SERVICE_TYPE;
    const avgMins     = (avgConsultationMins && avgConsultationMins > 0)
      ? avgConsultationMins
      : DEFAULT_AVG_CONSULTATION_MINS;
    const maxBphInput = el('#max_bph');
    const maxBph      = Number((maxBphInput?.value || '').trim() || 3);

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

  async function saveDoctorPrice(autoTriggered = false) {
    const doctorId = getSelectedDoctorId();
    if (!doctorId) {
      setPriceStatus('Select a doctor first.', false);
      return;
    }
    if (!priceField) return;

    const raw = String(priceField.value ?? '').trim();
    const price = raw === '' ? null : Number(raw);
    if (raw !== '' && (!Number.isFinite(price) || price < 0)) {
      setPriceStatus('Enter a valid non-negative amount.', false);
      priceField.focus();
      return;
    }

    togglePriceButton(true);
    setPriceStatus('Saving price…', true);

    try {
      const res = await fetch(`${apiBase}/doctors/${doctorId}/price`, {
        method: 'PUT',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
        body: JSON.stringify({ doctor_price: price }),
      });
      const text = await res.text();
      let json = null; try { json = JSON.parse(text); } catch {}
      if (!res.ok) {
        const msg = json?.message || json?.error || text || 'Failed to save price';
        throw new Error(msg);
      }

      const savedPrice = json?.doctor_price ?? price ?? '';
      primePriceField(savedPrice === null ? '' : savedPrice);
      setSelectedOptionPrice(savedPrice);
      priceDirty = false;
      setPriceStatus('Price saved for this doctor.', true);
      if (!autoTriggered && typeof toast === 'function') toast('Doctor price updated');
    } catch (err) {
      const msg = err?.message || 'Could not save price';
      setPriceStatus(msg, false);
      if (typeof toast === 'function') toast(msg, false);
    } finally {
      togglePriceButton(false);
    }
  }

  async function saveAvailability(autoTriggered = false) {
    const doctorId = getSelectedDoctorId();
    if (!doctorId) {
      if (!autoTriggered) alert('Select a doctor first');
      return;
    }

    if (isSaving) {
      pendingAutoSave = true;
      return;
    }

    const { availability, validationError } = collectAvailability();
    if (validationError) { out('#saveOut', validationError, false); return; }
    if (!availability.length) { out('#saveOut', 'Select at least one active day with valid times', false); return; }

    isSaving = true;
    if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }
    out('#saveOut', autoTriggered ? 'Saving your clinic hours…' : '', true);

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
        if (!autoTriggered) toast(successMessage);
        revealWeekly();
        setMetaNote('Clinic schedule saved. You can fine-tune weekdays below.');
        try{
          const u = new URL(location.href);
          const isOnboarding = (u.searchParams.get('onboarding')||'') === '1';
          if (isOnboarding && !autoTriggered){
            const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
            const nextUrl = `${window.location.origin}${PATH_PREFIX}/doctor/emergency-hours?onboarding=1&step=4`;
            const goNext = ()=>{ window.location.href = nextUrl; };
            if (window.Swal){
              Swal.fire({
                icon:'success',
                title:'Clinic schedule saved',
                text:'Next: set your emergency coverage.',
                timer:1500,
                showConfirmButton:false,
              }).then(goNext);
            }else{
              setTimeout(goNext, 900);
            }
          } else {
            await loadExistingAvailability();
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
      isSaving = false;
      if (pendingAutoSave) {
        pendingAutoSave = false;
        queueAutoSave();
      }
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const dd = el('#doctor_id');
    if (dd && dd.options.length && dd.value) {
      const initialPrice = hydratePriceFromOption();
      primePriceField(initialPrice);
      setPriceStatus(
        initialPrice !== '' && initialPrice !== null
          ? `Current price: ₹${initialPrice}`
          : 'No price saved yet.'
      );
      loadExistingAvailability();
    }

    dd?.addEventListener('change', () => {
      const optPrice = hydratePriceFromOption();
      primePriceField(optPrice);
      setPriceStatus('Loading doctor schedule…', true);
      loadExistingAvailability();
    });

    els('.js-day-card').forEach(wireDayCard);
    Object.values(defaultFields).forEach(field => {
      field?.addEventListener('change', () => {
        applyDefaultHours(false);
        queueAutoSave();
      });
      field?.addEventListener('input', () => {
        applyDefaultHours(false);
        queueAutoSave();
      });
    });

    if (!weeklyVisible) {
      setMetaNote('Defaults not saved yet — fill the block above to unlock weekday cards.');
    }

    applyDefaultHours(true);
    isBootstrapping = false;

    priceField?.addEventListener('input', () => {
      priceDirty = true;
      setPriceStatus('Not saved yet.', false);
    });
    priceButton?.addEventListener('click', () => saveDoctorPrice(false));

    const manualSave = el('#btnManualSave');
    manualSave?.addEventListener('click', async ()=>{
      toggleManualSaveButton(true);
      try{
        await saveAvailability(false);
        if (priceDirty) {
          await saveDoctorPrice(true);
        }
      }finally{
        toggleManualSaveButton(false);
      }
    });
  });
</script>

<script>
  /* =========================
     Create in-clinic service inline
  ========================= */
  (function(){
    const form = document.getElementById('svc-form');
    if (!form) return;

    const fields = {
      name:     document.getElementById('svc-name'),
      duration: document.getElementById('svc-duration'),
      price:    document.getElementById('svc-price'),
      petType:  document.getElementById('svc-pet-type'),
      main:     document.getElementById('svc-main'),
      notes:    document.getElementById('svc-notes'),
      submit:   document.getElementById('svc-submit'),
      reset:    document.getElementById('svc-reset')
    };

    const API_POST_SVC = 'https://snoutiq.com/backend/api/groomer/service';
    const PATH_BASE    = (typeof window !== 'undefined' && typeof window.PATH_PREFIX === 'string' && window.PATH_PREFIX.length)
      ? window.PATH_PREFIX
      : (location.pathname.startsWith('/backend') ? '/backend' : '');
    const CSRF_URL     = `${PATH_BASE}/sanctum/csrf-cookie`;

    const getCookie = (name) => document.cookie.split('; ').find(r => r.startsWith(name + '='))?.split('=')[1] || '';
    const xsrfHeader = () => {
      const raw = getCookie('XSRF-TOKEN');
      return raw ? decodeURIComponent(raw) : '';
    };

    async function bootstrapAuth(){
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      if (token) return { mode:'bearer', token };
      try {
        await fetch(CSRF_URL, { credentials:'include' });
        const xsrf = xsrfHeader();
        if (xsrf) return { mode:'cookie', xsrf };
      } catch (err) {
        console.warn('csrf bootstrap failed', err);
      }
      return { mode:'none' };
    }

    const buildHeaders = (auth) => {
      const headers = {
        'Accept':'application/json',
        'X-Acting-User':  String(CURRENT_USER_ID ?? ''),
        'X-Session-User': String(SESSION_USER_ID ?? '')
      };
      if (auth.mode === 'bearer' && auth.token) {
        headers['Authorization'] = 'Bearer ' + auth.token;
      }
      if (auth.mode === 'cookie') {
        headers['X-Requested-With'] = 'XMLHttpRequest';
        const xsrf = xsrfHeader();
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
      }
      return headers;
    };

    const showAlert = (opts) => {
      if (window.Swal) {
        Swal.fire(opts);
      } else {
        alert(opts.title || opts.text || 'Something went wrong');
      }
    };

    const toggleLoading = (on) => {
      if (!fields.submit) return;
      fields.submit.disabled = !!on;
      if (on) {
        fields.submit.dataset.oldText = fields.submit.textContent;
        fields.submit.textContent = 'Saving…';
      } else if (fields.submit.dataset.oldText) {
        fields.submit.textContent = fields.submit.dataset.oldText;
        delete fields.submit.dataset.oldText;
      }
    };

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const payload = {
        name:     (fields.name?.value || '').trim(),
        duration: Number(fields.duration?.value || 0),
        price:    Number(fields.price?.value || 0),
        petType:  fields.petType?.value || '',
        main:     fields.main?.value || '',
        notes:    (fields.notes?.value || '').trim()
      };

      if (!payload.name || !payload.duration || !payload.price || !payload.petType || !payload.main) {
        showAlert({ icon:'warning', title:'Missing details', text:'Please fill all required service fields.' });
        return;
      }

      toggleLoading(true);

      try {
        const auth = await bootstrapAuth();
        if (auth.mode === 'none') {
          showAlert({ icon:'warning', title:'Authentication needed', text:'Log in again or paste a Bearer token in local storage.' });
          toggleLoading(false);
          return;
        }

        const fd = new FormData();
        fd.append('serviceName', payload.name);
        fd.append('description', payload.notes);
        fd.append('petType', payload.petType);
        fd.append('price', payload.price);
        fd.append('duration', payload.duration);
        fd.append('main_service', payload.main);
        fd.append('status', 'Active');
        fd.append('user_id', String(CURRENT_USER_ID ?? ''));

        const res = await fetch(API_POST_SVC, {
          method:'POST',
          headers: buildHeaders(auth),
          body: fd,
          credentials: 'include'
        });

        const text = await res.text();
        let json = null;
        try { json = JSON.parse(text); } catch (_){ }

        if (!res.ok) {
          const message = json?.message || json?.error || text || 'Failed to create service';
          throw new Error(message);
        }

        showAlert({ icon:'success', title:'Service saved', text:'Your in-clinic service is now live.' });
        form.reset();
      } catch (err) {
        showAlert({ icon:'error', title:'Could not save service', text: err?.message || 'Unexpected error' });
      } finally {
        toggleLoading(false);
      }
    });

    fields.reset?.addEventListener('click', () => {
      setTimeout(() => form.reset(), 0);
    });
  })();
</script>
@endsection
