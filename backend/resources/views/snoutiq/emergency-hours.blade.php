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
            <h3 class="text-sm font-semibold text-gray-800">Doctors covering emergencies</h3>
            <span class="text-xs text-gray-500" id="doctorCount">0 selected</span>
          </div>
          <div id="doctorList" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
          <p id="doctorHint" class="text-xs text-rose-600 mt-1 hidden">Select at least one doctor.</p>
          @if($doctorList->isEmpty())
            <div class="mt-3 p-3 rounded-lg bg-amber-50 text-amber-700 text-sm">
              We could not find doctors linked to this clinic. Once your doctors are added, refresh this page to tag them for
              emergency coverage.
            </div>
          @endif
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-800 mb-2">Night emergency slots</h3>
          <p class="text-xs text-gray-500 mb-3">Choose the time blocks when you actively monitor emergency calls (IST).</p>
          <div id="slotList" class="grid grid-cols-2 md:grid-cols-3 gap-2"></div>
          <p id="slotHint" class="text-xs text-rose-600 mt-1 hidden">Select at least one slot.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="consultationPrice" class="block text-sm font-medium text-gray-700">Emergency consultation price (₹)</label>
            <input type="number" min="0" step="50" id="consultationPrice" class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="1200">
            <p id="priceHint" class="text-xs text-rose-600 mt-1 hidden">Enter a valid price.</p>
          </div>
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
    doctors: new Set(),
    slots: new Set(),
    price: null,
  };

  const el = (sel) => document.querySelector(sel);
  const els = (sel) => Array.from(document.querySelectorAll(sel));
  const fmtList = (items) => items && items.length ? items.join(', ') : 'Not set';
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

  function renderDoctors(){
    const wrap = el('#doctorList'); if (!wrap) return;
    wrap.innerHTML = '';
    DOCTORS.forEach(doc => {
      const isChecked = state.doctors.has(doc.id);
      const baseClasses = 'flex items-center gap-3 p-3 rounded-xl border transition';
      const activeClasses = 'border-rose-500 bg-rose-50 text-rose-900 shadow-sm';
      const inactiveClasses = 'border-gray-200 bg-white text-gray-800 hover:border-rose-300';
      const card = document.createElement('label');
      card.className = `${baseClasses} ${isChecked ? activeClasses : inactiveClasses}`;
      card.innerHTML = `
        <input type="checkbox" class="doctor-toggle h-4 w-4 text-rose-600 border-gray-300 rounded focus:ring-rose-500" data-doctor="${doc.id}" ${isChecked ? 'checked' : ''}>
        <span class="text-sm font-medium">${doc.name}</span>
      `;
      const input = card.querySelector('input');
      input.addEventListener('change', (e) => {
        const id = Number(e.target.dataset.doctor);
        if (e.target.checked) {
          state.doctors.add(id);
          card.className = `${baseClasses} ${activeClasses}`;
        } else {
          state.doctors.delete(id);
          card.className = `${baseClasses} ${inactiveClasses}`;
        }
        updateDoctorCount();
        updatePreview();
      });
      wrap.appendChild(card);
    });
    updateDoctorCount();
  }

  function renderSlots(){
    const wrap = el('#slotList'); if (!wrap) return;
    wrap.innerHTML = '';
    SLOT_OPTIONS.forEach(slot => {
      const isChecked = state.slots.has(slot);
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.dataset.slot = slot;
      btn.className = `px-3 py-2 rounded-xl border text-sm font-medium transition ${isChecked ? 'border-rose-500 bg-rose-500 text-white shadow' : 'border-gray-200 bg-white text-gray-700 hover:border-rose-300 hover:text-rose-700'}`;
      btn.textContent = slot;
      btn.addEventListener('click', () => {
        if (state.slots.has(slot)) {
          state.slots.delete(slot);
        } else {
          state.slots.add(slot);
        }
        renderSlots();
        updatePreview();
      });
      wrap.appendChild(btn);
    });
    updatePreview();
  }

  function updateDoctorCount(){
    const badge = el('#doctorCount');
    if (badge) badge.textContent = `${state.doctors.size} selected`;
  }

  function updatePreview(data){
    const preview = el('#preview');
    if (!preview) return;
    const doctors = Array.from(state.doctors)
      .map(id => (DOCTORS.find(doc => doc.id === id)?.name || `Doctor #${id}`));
    const slots = Array.from(state.slots);
    const price = typeof state.price === 'number' && !Number.isNaN(state.price)
      ? `₹${state.price.toFixed(0)}`
      : 'Not set';

    preview.innerHTML = `
      <div>
        <dt class="text-xs uppercase tracking-wide text-gray-500">Doctors</dt>
        <dd class="mt-0.5 text-gray-800">${fmtList(doctors)}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-gray-500">Night slots</dt>
        <dd class="mt-0.5 text-gray-800">${fmtList(slots)}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-gray-500">Consultation price</dt>
        <dd class="mt-0.5 text-gray-800">${price}</dd>
      </div>
    `;

    if (data?.updated_at) {
      const badge = el('#lastUpdated');
      if (badge) badge.textContent = data.updated_at;
    }
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
      state.doctors.clear();
      state.slots.clear();
      (Array.isArray(data.doctor_ids) ? data.doctor_ids : []).forEach(id => state.doctors.add(Number(id)));
      (Array.isArray(data.night_slots) ? data.night_slots : []).forEach(slot => state.slots.add(slot));
      if (data.consultation_price !== null && data.consultation_price !== undefined) {
        state.price = Number(data.consultation_price);
        const input = el('#consultationPrice');
        if (input) input.value = state.price;
      }
      updatePreview(data);
      renderDoctors();
      renderSlots();
    } catch (err) {
      console.error('loadExisting error', err);
    }
  }

  function bindEvents(){
    const priceInput = el('#consultationPrice');
    if (priceInput) {
      priceInput.addEventListener('input', (e) => {
        const value = Number(e.target.value);
        if (!Number.isNaN(value)) {
          state.price = value;
          updatePreview();
        }
      });
    }

    el('#btnSaveEmergency')?.addEventListener('click', saveEmergency);
  }

  function validateForm(){
    let valid = true;
    const doctorHint = el('#doctorHint');
    const slotHint = el('#slotHint');
    const priceHint = el('#priceHint');

    if (!state.doctors.size) { doctorHint?.classList.remove('hidden'); valid = false; }
    else { doctorHint?.classList.add('hidden'); }

    if (!state.slots.size) { slotHint?.classList.remove('hidden'); valid = false; }
    else { slotHint?.classList.add('hidden'); }

    if (typeof state.price !== 'number' || Number.isNaN(state.price) || state.price <= 0) {
      priceHint?.classList.remove('hidden');
      valid = false;
    } else {
      priceHint?.classList.add('hidden');
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
          doctor_ids: Array.from(state.doctors),
          night_slots: Array.from(state.slots),
          consultation_price: state.price,
        }),
      });
      const text = await res.text();
      let json = null; try { json = JSON.parse(text.replace(/^\uFEFF/, '')); } catch {}

      if (res.ok) {
        setStatus(json?.message || 'Emergency coverage saved.', true);
        toast(json?.message || 'Emergency coverage saved.');
        if (json?.data) {
          state.doctors.clear();
          state.slots.clear();
          (Array.isArray(json.data.doctor_ids) ? json.data.doctor_ids : []).forEach(id => state.doctors.add(Number(id)));
          (Array.isArray(json.data.night_slots) ? json.data.night_slots : []).forEach(slot => state.slots.add(slot));
          if (json.data.consultation_price !== undefined && json.data.consultation_price !== null) {
            state.price = Number(json.data.consultation_price);
            const input = el('#consultationPrice');
            if (input) input.value = state.price;
          }
          renderDoctors();
          renderSlots();
          updatePreview(json.data);
        } else {
          updatePreview();
        }
        const u = new URL(location.href);
        const isOnboarding = (u.searchParams.get('onboarding')||'') === '1';
        if (isOnboarding) {
          localStorage.setItem('onboarding_v1_done','1');
          const redirectToDashboard = () => {
            const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
            window.location.href = `${window.location.origin}${PATH_PREFIX}/doctor`;
          };
          if (window.Swal) {
            Swal.fire({
              icon: 'success',
              title: 'Emergency coverage saved',
              text: 'Great! Your onboarding is complete.',
              confirmButtonText: 'Go to dashboard',
            }).then(redirectToDashboard);
          } else {
            redirectToDashboard();
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
    renderDoctors();
    renderSlots();
    bindEvents();
    loadExisting();
  });
</script>
@endsection
