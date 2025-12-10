@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Emergency Coverage Hours';
  $clinicId   = $clinicId ?? null;
  $doctorList = isset($doctors) ? $doctors->map(function($doc){
    return [
      'id' => $doc->id,
      'name' => $doc->doctor_name ?? $doc->name ?? ('Doctor #'.$doc->id),
      'clinic_id' => $doc->vet_registeration_id ?? null,
    ];
  })->values() : collect();
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  @php $stepStatus = $stepStatus ?? []; @endphp
  @if(request()->get('onboarding')==='1')
    @include('layouts.partials.onboarding-steps', [
      'active' => (int) (request()->get('step', 4)),
      'stepStatus' => $stepStatus,
    ])
  @endif

  <div class="bg-white shadow-sm ring-1 ring-gray-200/60 rounded-2xl p-6 space-y-4">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Night Emergency Coverage</h2>
        <p class="text-sm text-gray-600 mt-1 max-w-2xl">
          Share which doctors cover late night emergencies, the slots they monitor, and the price you charge for these
          consultations. This helps us route urgent pet parents to the right expert faster.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="px-2 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">Emergency only</span>
        <span class="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">IST</span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <section class="lg:col-span-2 space-y-5">
        <div>
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-gray-800">Select doctor</h3>
            <span class="text-xs text-gray-500" id="doctorCount"></span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="md:col-span-2">
              <label for="doctorSelect" class="sr-only">Doctor</label>
              <select id="doctorSelect" class="w-full rounded-xl border-gray-300 bg-white text-sm font-medium text-gray-800 focus:border-rose-500 focus:ring-rose-500">
                <option value="">{{ $doctorList->isEmpty() ? 'No doctors found' : 'Select a doctor' }}</option>
              </select>
              <p class="mt-1 text-xs text-gray-500" id="doctorSelectNote">Pick a doctor to view their emergency slots.</p>
              <p id="doctorHint" class="text-xs text-rose-600 mt-1 hidden">Select a doctor to continue.</p>
            </div>
          </div>
          @if($doctorList->isEmpty())
            <div class="mt-3 p-3 rounded-lg bg-amber-50 text-amber-700 text-sm">
              We could not find doctors linked to this clinic. Once your doctors are added, refresh this page to tag them for
              emergency coverage.
            </div>
          @endif
        </div>

        <div>
          <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <h3 class="text-sm font-semibold text-gray-800 mb-1">Night emergency slots</h3>
              <p class="text-xs text-gray-500">Choose the time blocks when you actively monitor emergency calls (IST).</p>
            </div>
            <div class="md:text-right">
              <p id="slotDoctorNote" class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"></p>
            </div>
          </div>
          <div id="slotList" class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-2"></div>
          <p id="slotHint" class="text-xs text-rose-600 mt-1 hidden">Select at least one slot for the chosen doctor.</p>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <div class="text-xs text-gray-500 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg px-3 py-3">
            <div class="font-semibold text-indigo-800 mb-1">Tips for faster triage</div>
            <ul class="list-disc pl-4 space-y-1">
              <li>Tag only doctors who can join within 10 minutes of a request.</li>
              <li>Select overnight slots that you routinely cover.</li>
              <li>Keep pricing transparent to reduce patient drop-offs.</li>
            </ul>
          </div>
        </div>
      </section>

      <aside class="space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Saved configuration</h3>
            <span class="text-[11px] text-gray-500" id="lastUpdated">Never</span>
          </div>
          <dl class="mt-3 space-y-3 text-sm" id="preview"></dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
          <h3 class="text-sm font-semibold text-gray-800 mb-2">Need help?</h3>
          <p class="text-xs text-gray-600">
            Unsure about emergency coverage? Drop us a line at
            <a href="mailto:support@snoutiq.com" class="text-indigo-600 underline">support@snoutiq.com</a> and we will walk you
            through best practices.
          </p>
        </div>
      </aside>
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
      <p class="text-xs text-gray-500">Changes are saved for the whole clinic. Team members tagged here receive night alerts.</p>
      <button id="btnSaveEmergency" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-1">
        <span>Save emergency coverage</span>
      </button>
    </div>
    <div id="saveStatus" class="text-sm mt-2"></div>
  </div>
</div>

@php
  $slotOptions = [
    '18:00-20:00', '20:00-22:00', '22:00-00:00',
    '00:00-02:00', '02:00-04:00', '04:00-06:00',
  ];
@endphp

<script>
  (function(){
    var s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'; document.head.appendChild(s);
  })();

  const DOCTORS = @json($doctorList);
  const SLOT_OPTIONS = @json($slotOptions);
  const CLINIC_ID = Number(@json($clinicId ?? 0));
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
  const API_BASE = `${window.location.origin}${PATH_PREFIX}/api/clinic/emergency-hours`;

  const state = {
    doctorSlots: new Map(),  // doctorId -> Set of slots
    activeDoctor: null,      // Doctor currently being edited
    price: 0,
  };

  const el = (sel) => document.querySelector(sel);
  const els = (sel) => Array.from(document.querySelectorAll(sel));
  const fmtList = (items) => items && items.length ? items.join(', ') : 'Not set';
  const escapeHtml = (str) => String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
  const toast = (msg, ok = true) => {
    if (window.Swal) {
      Swal.fire({ toast:true, position:'top', timer:1400, showConfirmButton:false, icon: ok ? 'success' : 'error', title: String(msg) });
    }
  };
  const setStatus = (msg, ok = true) => {
    const box = el('#saveStatus'); if (!box) return;
    box.textContent = msg || '';
    box.className = ok ? 'text-sm mt-2 text-emerald-600' : 'text-sm mt-2 text-rose-600';
  };

  const getDoctorName = (id) => {
    const match = DOCTORS.find(doc => Number(doc.id) === Number(id));
    return match?.name || `Doctor #${id}`;
  };

  const getActiveDoctor = () => {
    if (!state.activeDoctor) return null;
    const match = DOCTORS.find(doc => Number(doc.id) === Number(state.activeDoctor));
    return match ? { ...match, id: Number(match.id) } : { id: Number(state.activeDoctor), name: `Doctor #${state.activeDoctor}` };
  };

  const getDoctorsWithSlots = () => {
    const list = [];
    state.doctorSlots.forEach((slots, id) => {
      if (!slots?.size) return;
      const doc = DOCTORS.find(d => Number(d.id) === Number(id));
      list.push({
        id: Number(id),
        name: doc?.name || `Doctor #${id}`,
      });
    });
    return list;
  };

  const ensureDoctorSlotSet = (doctorId) => {
    const id = Number(doctorId);
    if (!state.doctorSlots.has(id)) {
      state.doctorSlots.set(id, new Set());
    }
    return state.doctorSlots.get(id);
  };

  const syncActiveDoctor = () => {
    if (state.activeDoctor && DOCTORS.some(doc => Number(doc.id) === Number(state.activeDoctor))) {
      return;
    }
    const firstWithSlots = getDoctorsWithSlots()[0];
    if (firstWithSlots) {
      state.activeDoctor = Number(firstWithSlots.id);
      return;
    }
    const firstDoctor = DOCTORS[0];
    state.activeDoctor = firstDoctor ? Number(firstDoctor.id) : null;
  };

  function renderDoctorDropdown(){
    const select = el('#doctorSelect'); if (!select) return;
    select.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = DOCTORS.length ? 'Select a doctor' : 'No doctors found';
    select.appendChild(placeholder);

    DOCTORS.forEach(doc => {
      const opt = document.createElement('option');
      opt.value = doc.id;
      opt.textContent = doc.name || `Doctor #${doc.id}`;
      select.appendChild(opt);
    });

    if (state.activeDoctor && !DOCTORS.some(doc => Number(doc.id) === Number(state.activeDoctor))) {
      const opt = document.createElement('option');
      opt.value = state.activeDoctor;
      opt.textContent = getDoctorName(state.activeDoctor);
      select.appendChild(opt);
    }

    syncActiveDoctor();
    if (state.activeDoctor) {
      select.value = String(state.activeDoctor);
    }

    const badge = el('#doctorCount');
    if (badge) {
      badge.textContent = state.activeDoctor ? `Editing ${getDoctorName(state.activeDoctor)}` : '';
    }

    const note = el('#doctorSelectNote');
    if (note) {
      if (!DOCTORS.length) {
        note.textContent = 'No doctors found for this clinic.';
      } else {
        note.textContent = state.activeDoctor
          ? `Showing emergency slots for ${getDoctorName(state.activeDoctor)}.`
          : 'Pick a doctor to view their emergency slots.';
      }
    }
  }

  function renderSlots(){
    const wrap = el('#slotList'); if (!wrap) return;
    const note = el('#slotDoctorNote');
    wrap.innerHTML = '';

    if (!DOCTORS.length) {
      wrap.innerHTML = `<div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600">No doctors found for this clinic. Add doctors to set night emergency slots.</div>`;
      if (note) note.textContent = '';
      updatePreview();
      return;
    }

    syncActiveDoctor();
    if (!state.activeDoctor) {
      wrap.innerHTML = `<div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600">Select a doctor above to set night emergency slots.</div>`;
      if (note) note.textContent = '';
      updatePreview();
      return;
    }

    const slotSet = ensureDoctorSlotSet(state.activeDoctor);
    SLOT_OPTIONS.forEach(slot => {
      const isChecked = slotSet.has(slot);
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.dataset.slot = slot;
      btn.className = `px-3 py-2 rounded-xl border text-sm font-medium transition ${isChecked ? 'border-rose-500 bg-rose-500 text-white shadow' : 'border-gray-200 bg-white text-gray-700 hover:border-rose-300 hover:text-rose-700'}`;
      btn.textContent = slot;
      btn.addEventListener('click', () => {
        if (slotSet.has(slot)) {
          slotSet.delete(slot);
        } else {
          slotSet.add(slot);
        }
        renderSlots();
        updatePreview();
      });
      wrap.appendChild(btn);
    });

    if (note) note.textContent = `Editing slots for ${getDoctorName(state.activeDoctor)}`;
    updatePreview();
  }

  function updatePreview(data){
    const preview = el('#preview');
    if (!preview) return;

    const activeDoctor = getActiveDoctor();
    const slotsForDoctor = activeDoctor ? Array.from(ensureDoctorSlotSet(activeDoctor.id)) : [];

    preview.innerHTML = `
      <div>
        <dt class="text-xs uppercase tracking-wide text-gray-500">Doctor</dt>
        <dd class="mt-0.5 text-gray-800">${activeDoctor ? escapeHtml(activeDoctor.name || `Doctor #${activeDoctor.id}`) : 'Not selected'}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-gray-500">Night slots</dt>
        <dd class="mt-0.5 text-gray-800">${slotsForDoctor.length ? fmtList(slotsForDoctor.map(escapeHtml)) : 'Not set'}</dd>
      </div>
    `;

    if (data?.updated_at) {
      const badge = el('#lastUpdated');
      if (badge) badge.textContent = data.updated_at;
    }
  }

  function hydrateFromData(data){
    state.doctorSlots = new Map();
    state.activeDoctor = null;

    const doctorIds = Array.isArray(data?.doctor_ids) ? data.doctor_ids.map(Number) : [];
    doctorIds.forEach(id => ensureDoctorSlotSet(id));

    const schedules = Array.isArray(data?.doctor_schedules) ? data.doctor_schedules : [];
    if (schedules.length) {
      schedules.forEach(item => {
        const id = Number(item.doctor_id);
        if (!Number.isFinite(id)) return;
        const slots = Array.isArray(item.night_slots) ? item.night_slots : [];
        const set = ensureDoctorSlotSet(id);
        slots.forEach(slot => set.add(slot));
      });
    } else if (data?.doctor_slot_map && typeof data.doctor_slot_map === 'object') {
      Object.entries(data.doctor_slot_map).forEach(([id, slots]) => {
        const docId = Number(id);
        if (!Number.isFinite(docId)) return;
        const set = ensureDoctorSlotSet(docId);
        (Array.isArray(slots) ? slots : []).forEach(slot => set.add(slot));
      });
    } else if (Array.isArray(data?.night_slots) && doctorIds.length) {
      doctorIds.forEach(id => {
        const set = ensureDoctorSlotSet(id);
        data.night_slots.forEach(slot => set.add(slot));
      });
    }

    const firstWithSlots = getDoctorsWithSlots()[0];
    state.activeDoctor = firstWithSlots ? Number(firstWithSlots.id) : (doctorIds[0] ?? null);
    syncActiveDoctor();
  }

  async function loadExisting(){
    if (!CLINIC_ID) return;
    try {
      const res = await fetch(`${API_BASE}?clinic_id=${CLINIC_ID}`, { headers: { 'Accept': 'application/json' } });
      const text = await res.text();
      let json = null; try { json = JSON.parse(text.replace(/^\uFEFF/, '')); } catch {}
      if (!res.ok) {
        console.warn('Failed to load emergency coverage', text);
        return;
      }
      const data = json?.data || {};
      hydrateFromData(data);
      if (data.consultation_price !== null && data.consultation_price !== undefined) {
        state.price = Number(data.consultation_price);
      } else {
        state.price = 0;
      }
      renderDoctorDropdown();
      renderSlots();
      updatePreview(data);
    } catch (err) {
      console.error('loadExisting error', err);
    }
  }

  function bindEvents(){
    const doctorSelect = el('#doctorSelect');
    if (doctorSelect) {
      doctorSelect.addEventListener('change', (e) => {
        const raw = e.target.value;
        const id = raw === '' ? null : Number(raw);
        state.activeDoctor = id === null || Number.isNaN(id) ? null : id;
        if (state.activeDoctor !== null) {
          ensureDoctorSlotSet(state.activeDoctor);
        }
        renderDoctorDropdown();
        renderSlots();
        updatePreview();
      });
    }

    el('#btnSaveEmergency')?.addEventListener('click', saveEmergency);
  }

  function validateForm(){
    let valid = true;
    const doctorHint = el('#doctorHint');
    const slotHint = el('#slotHint');

    if (!state.activeDoctor) { doctorHint?.classList.remove('hidden'); valid = false; }
    else { doctorHint?.classList.add('hidden'); }

    const activeSlots = state.activeDoctor ? ensureDoctorSlotSet(state.activeDoctor) : new Set();
    if (!state.activeDoctor || !activeSlots.size) {
      if (slotHint) {
        const name = state.activeDoctor ? getDoctorName(state.activeDoctor) : 'the selected doctor';
        slotHint.textContent = `Add at least one slot for ${name}.`;
        slotHint.classList.remove('hidden');
      }
      valid = false;
    } else {
      if (slotHint) {
        slotHint.textContent = 'Select at least one slot for the chosen doctor.';
        slotHint.classList.add('hidden');
      }
    }

    return valid;
  }

  async function saveEmergency(){
    if (!validateForm()) {
      setStatus('Please complete the highlighted fields.', false);
      return;
    }
    if (!CLINIC_ID) {
      setStatus('Missing clinic context. Please reload.', false);
      return;
    }

    const btn = el('#btnSaveEmergency');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
    setStatus('');

    try {
      const doctorsToSave = getDoctorsWithSlots();
      const doctorSchedules = doctorsToSave.map(doc => ({
        doctor_id: doc.id,
        night_slots: Array.from(ensureDoctorSlotSet(doc.id)),
      }));
      const doctorIds = doctorSchedules.map(s => s.doctor_id);
      const nightSlots = Array.from(new Set(doctorSchedules.flatMap(s => s.night_slots)));
      const priceToSave = (typeof state.price === 'number' && !Number.isNaN(state.price)) ? state.price : 0;

      if (!doctorSchedules.length) {
        setStatus('Add at least one slot for the selected doctor.', false);
        toast('Add at least one slot for the selected doctor.', false);
        return;
      }

      const res = await fetch(API_BASE, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          clinic_id: CLINIC_ID,
          doctor_ids: doctorIds,
          doctor_schedules: doctorSchedules,
          night_slots: nightSlots,
          consultation_price: priceToSave,
        }),
      });
      const text = await res.text();
      let json = null; try { json = JSON.parse(text.replace(/^\uFEFF/, '')); } catch {}

      if (res.ok) {
        setStatus(json?.message || 'Emergency coverage saved.', true);
        toast(json?.message || 'Emergency coverage saved.');
        if (json?.data) {
          hydrateFromData(json.data);
          if (json.data.consultation_price !== undefined && json.data.consultation_price !== null) {
            state.price = Number(json.data.consultation_price);
          } else {
            state.price = 0;
          }
          renderDoctorDropdown();
          renderSlots();
          updatePreview(json.data);
        } else {
          updatePreview();
        }
        const u = new URL(location.href);
        const isOnboarding = (u.searchParams.get('onboarding')||'') === '1';
        if (isOnboarding) {
          const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
          const nextUrl = `${window.location.origin}${PATH_PREFIX}/doctor/documents?onboarding=1&step=5`;
          const redirectToDocuments = () => { window.location.href = nextUrl; };
          if (window.Swal) {
            Swal.fire({
              icon: 'success',
              title: 'Emergency coverage saved',
              text: 'Next: upload your documents & compliance files.',
              timer: 1500,
              showConfirmButton: false,
            }).then(redirectToDocuments);
          } else {
            setTimeout(redirectToDocuments, 900);
          }
        }
      } else {
        const message = json?.error || json?.message || text || 'Failed to save emergency coverage.';
        setStatus(message, false);
        toast(message, false);
      }
    } catch (err) {
      console.error('saveEmergency error', err);
      setStatus(err?.message || 'Network error', false);
      toast('Network error while saving', false);
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Save emergency coverage'; }
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    renderDoctorDropdown();
    renderSlots();
    bindEvents();
    loadExisting();
  });
</script>
@endsection
