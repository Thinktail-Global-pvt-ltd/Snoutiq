{{-- resources/views/doctor/schedule.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Weekly Schedule')
@section('page_title','Doctor Weekly Availability')

@php
  $sessionClinicId = $sessionClinicId ?? null;
  $preloadedDoctors = collect($preloadedDoctors ?? []);
  $resolvedClinicId = $sessionClinicId
      ?? session('vet_registerations_temp_id')
      ?? session('vet_registeration_id')
      ?? session('vet_id')
      ?? data_get(session('auth_full'), 'vet_registerations_temp_id')
      ?? data_get(session('auth_full'), 'vet_registeration_id')
      ?? data_get(session('auth_full'), 'vet_id')
      ?? data_get(session('user'), 'vet_registerations_temp_id')
      ?? data_get(session('user'), 'vet_registeration_id')
      ?? session('clinic_id')
      ?? session('user_id')
      ?? data_get(session('user'), 'id');
  $presetDoctorId = request()->query('doctor_id') ?? request()->query('doctorId');
  $presetDoctorId = $presetDoctorId !== null && $presetDoctorId !== '' ? (int) $presetDoctorId : null;
  $presetServiceType = request()->query('service_type');
  $explicitClinicId = request()->query('clinic_id');
  $explicitClinicId = $explicitClinicId !== null && $explicitClinicId !== '' ? (int) $explicitClinicId : null;
  $resolvedClinicId = $resolvedClinicId !== null ? (int) $resolvedClinicId : null;
  if (!$resolvedClinicId && $explicitClinicId) {
      $resolvedClinicId = $explicitClinicId;
  }
  $clinicIdForJs = $resolvedClinicId;
  $displayClinicId = $clinicIdForJs;
  $resolvedClinic = $clinicIdForJs ? \App\Models\VetRegisterationTemp::find($clinicIdForJs) : null;
  $preloadedDoctorsForJs = $preloadedDoctors
      ->map(fn ($doc) => [
          'id' => $doc->id,
          'doctor_name' => $doc->doctor_name,
          'vet_registeration_id' => $doc->vet_registeration_id,
      ])
      ->values();
@endphp

@section('head')
  <style>
    .schedule-table input[type="time"],
    .schedule-table input[type="number"] {
      width: 100%;
      border-radius: 0.375rem; /* rounded */
      border: 1px solid rgb(209 213 219); /* gray-300 */
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem; /* text-sm */
    }
    .schedule-table input[type="time"]:disabled,
    .schedule-table input[type="number"]:disabled {
      background-color: rgb(243 244 246); /* gray-100 */
      color: rgb(107 114 128); /* gray-500 */
    }
    .schedule-table input[type="checkbox"] {
      width: 1rem;
      height: 1rem;
    }
  </style>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  <div class="bg-white rounded-xl shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_select" class="mt-1 w-full rounded border-gray-300">
          <option value="">-- Select Doctor --</option>
        </select>
        <div id="doctor_meta" class="text-xs text-gray-500 mt-1">Select a doctor to manage availability.</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Service Type</label>
        <select id="service_type" class="mt-1 w-full rounded border-gray-300">
          <option value="video">video</option>
          <option value="in_clinic">in_clinic</option>
          <option value="home_visit">home_visit</option>
        </select>
      </div>
      <div class="flex items-end">
        <button id="btn_reload" class="px-4 py-2 rounded bg-indigo-600 text-white">Reload</button>
      </div>
    </div>
    <div class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-sm text-gray-600">
      <div id="doctor_badge">No doctor selected.</div>
      <div>
        Clinic ID: <span class="font-mono" id="clinic_badge">{{ $clinicIdForJs ? '#'.$clinicIdForJs : '—' }}</span>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
      <h2 class="text-base font-semibold text-gray-800">Weekly Slots</h2>
      <div id="status_note" class="text-xs text-gray-500">Pick a doctor to load saved availability.</div>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm schedule-table">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-left">Day</th>
            <th class="px-4 py-2 text-left">Active</th>
            <th class="px-4 py-2 text-left">Start</th>
            <th class="px-4 py-2 text-left">End</th>
            <th class="px-4 py-2 text-left">Break Start</th>
            <th class="px-4 py-2 text-left">Break End</th>
            <th class="px-4 py-2 text-left">Avg Consultation (mins)</th>
            <th class="px-4 py-2 text-left">Max bookings / hour</th>
          </tr>
        </thead>
        <tbody id="schedule_rows" class="divide-y">
          @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $index => $label)
            <tr data-day="{{ $index }}" class="bg-white">
              <td class="px-4 py-2 font-medium text-gray-700">{{ $label }}</td>
              <td class="px-4 py-2">
                <input type="checkbox" class="toggle-active">
              </td>
              <td class="px-4 py-2"><input type="time" class="input-start" value="09:00"></td>
              <td class="px-4 py-2"><input type="time" class="input-end" value="17:00"></td>
              <td class="px-4 py-2"><input type="time" class="input-break-start"></td>
              <td class="px-4 py-2"><input type="time" class="input-break-end"></td>
              <td class="px-4 py-2"><input type="number" min="5" step="5" class="input-avg" value="20"></td>
              <td class="px-4 py-2"><input type="number" min="1" step="1" class="input-max" value="3"></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="px-4 py-3 border-t flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div id="save_result" class="text-sm text-gray-600">Changes are not saved yet.</div>
      <button id="btn_save" class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700">Save Weekly Availability</button>
    </div>
  </div>

  <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-700">
    @if($resolvedClinic)
      <div class="font-semibold text-indigo-900">Clinic</div>
      <div>{{ $resolvedClinic->name ?? 'Clinic' }} <span class="text-indigo-500">·</span> <span class="font-mono">#{{ $displayClinicId }}</span></div>
      <div class="mt-1">Showing doctors linked with this clinic.</div>
    @elseif($displayClinicId)
      <div class="font-semibold text-indigo-900">Clinic ID</div>
      <div class="font-mono">#{{ $displayClinicId }}</div>
      <div class="mt-1">Showing doctors linked with this clinic.</div>
    @else
      <div class="font-semibold text-amber-700">Clinic not detected</div>
      <div class="text-amber-600">Set the clinic session using <code>/api/session/login?user_id=…</code>.</div>
    @endif
  </div>
</div>
@endsection

@section('scripts')
<script>
  const ORIGIN          = window.location.origin;
  const PATHNAME        = window.location.pathname;
  const ON_BACKEND_PATH = PATHNAME.startsWith('/backend');
  const IS_LOCAL        = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const apiBase         = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;

  async function api(url, options={}){
    const res  = await fetch(url, Object.assign({ headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' } }, options));
    const text = await res.text();
    let json = null;
    try { json = JSON.parse(text.replace(/^\uFEFF/, '')); } catch(_){}
    return { ok: res.ok, status: res.status, json, raw: text };
  }

  const SESSION_CLINIC_ID = @json($clinicIdForJs);
  const PRELOADED_DOCTORS = @json($preloadedDoctorsForJs);
  const PRESET_DOCTOR_ID = @json($presetDoctorId ? (string) $presetDoctorId : null);
  const PRESET_SERVICE_TYPE = @json($presetServiceType ?? null);
  const doctorSelect = document.getElementById('doctor_select');
  const doctorMeta = document.getElementById('doctor_meta');
  const doctorBadge = document.getElementById('doctor_badge');
  const clinicBadge = document.getElementById('clinic_badge');
  const serviceTypeSelect = document.getElementById('service_type');
  const btnReload = document.getElementById('btn_reload');
  const btnSave = document.getElementById('btn_save');
  const statusNote = document.getElementById('status_note');
  const saveResult = document.getElementById('save_result');
  const scheduleRows = Array.from(document.querySelectorAll('#schedule_rows tr[data-day]'));

  if (clinicBadge) {
    clinicBadge.textContent = SESSION_CLINIC_ID ? `#${SESSION_CLINIC_ID}` : '—';
  }

  if (serviceTypeSelect && PRESET_SERVICE_TYPE) {
    serviceTypeSelect.value = PRESET_SERVICE_TYPE;
  }

  function doctorApiUrl(){
    if (!SESSION_CLINIC_ID) {
      return `${apiBase}/doctors`;
    }
    const params = new URLSearchParams({ vet_id: SESSION_CLINIC_ID });
    return `${apiBase}/doctors?${params.toString()}`;
  }

  function setRowEnabled(row, enabled){
    row.querySelectorAll('input[type="time"], input[type="number"]').forEach(input => {
      input.disabled = !enabled;
      input.classList.toggle('opacity-50', !enabled);
    });
  }

  function resetRows(){
    scheduleRows.forEach(row => {
      row.querySelector('.toggle-active').checked = false;
      row.querySelector('.input-start').value = '09:00';
      row.querySelector('.input-end').value = '17:00';
      row.querySelector('.input-break-start').value = '';
      row.querySelector('.input-break-end').value = '';
      row.querySelector('.input-avg').value = 20;
      row.querySelector('.input-max').value = 3;
      setRowEnabled(row, false);
    });
  }

  function updateDoctorBadge(){
    const option = doctorSelect?.selectedOptions?.[0];
    if (option && option.value) {
      const label = option.textContent.trim();
      doctorBadge.innerHTML = `<span class="text-gray-800 font-medium">${label}</span> <span class="text-xs text-gray-500">Doctor ID: <span class="font-mono text-gray-700">#${option.value}</span></span>`;
      if (doctorMeta) {
        doctorMeta.textContent = `Doctor ID: #${option.value}`;
      }
    } else {
      doctorBadge.textContent = 'No doctor selected.';
      if (doctorMeta && doctorSelect?.options?.length > 1) {
        doctorMeta.textContent = 'Select a doctor to manage availability.';
      }
    }
  }

  function applyAvailability(rows){
    resetRows();
    if (!Array.isArray(rows) || !rows.length) {
      statusNote.textContent = 'No availability found. Configure the weekly hours and press save.';
      return;
    }
    const byDay = new Map();
    rows.forEach(item => {
      const day = Number(item.day_of_week);
      if (!Number.isNaN(day)) {
        byDay.set(day, item);
      }
    });
    scheduleRows.forEach(row => {
      const day = Number(row.dataset.day);
      const data = byDay.get(day);
      if (data) {
        const start = (data.start_time || '').slice(0,5) || '09:00';
        const end = (data.end_time || '').slice(0,5) || '17:00';
        const breakStart = data.break_start ? String(data.break_start).slice(0,5) : '';
        const breakEnd = data.break_end ? String(data.break_end).slice(0,5) : '';
        row.querySelector('.toggle-active').checked = true;
        row.querySelector('.input-start').value = start;
        row.querySelector('.input-end').value = end;
        row.querySelector('.input-break-start').value = breakStart;
        row.querySelector('.input-break-end').value = breakEnd;
        row.querySelector('.input-avg').value = data.avg_consultation_mins ?? 20;
        row.querySelector('.input-max').value = data.max_bookings_per_hour ?? 3;
        setRowEnabled(row, true);
      }
    });
    statusNote.textContent = 'Loaded availability from the server.';
  }

  let cachedDoctors = Array.isArray(PRELOADED_DOCTORS) ? [...PRELOADED_DOCTORS] : [];

  function populateDoctorOptions(doctors){
    if (!doctorSelect) return;

    if (!Array.isArray(doctors) || !doctors.length) {
      doctorSelect.innerHTML = '<option value="">-- No doctors found --</option>';
      doctorSelect.disabled = true;
      if (doctorMeta) {
        doctorMeta.textContent = SESSION_CLINIC_ID ? 'No doctors found for this clinic.' : 'Set the clinic session to load doctors.';
      }
      updateDoctorBadge();
      resetRows();
      return;
    }

    const options = doctors.map(d => ({
      id: String(d.id),
      label: d.doctor_name ? `${d.doctor_name} (#${d.id})` : `Doctor #${d.id}`
    })).sort((a, b) => a.label.localeCompare(b.label, undefined, { sensitivity: 'base' }));

    const preset = PRESET_DOCTOR_ID ? String(PRESET_DOCTOR_ID) : null;
    doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>' + options.map(opt => `<option value="${opt.id}">${opt.label}</option>`).join('');
    doctorSelect.disabled = false;

    if (preset && options.some(opt => opt.id === preset)) {
      doctorSelect.value = preset;
    } else {
      doctorSelect.value = options[0].id;
    }

    if (doctorMeta) {
      doctorMeta.textContent = `Choose a doctor to edit availability for clinic #${SESSION_CLINIC_ID}`;
    }

    updateDoctorBadge();
    loadAvailability();
  }

  async function loadDoctors(force = false){
    if (!doctorSelect) return;

    if (!SESSION_CLINIC_ID) {
      doctorSelect.innerHTML = '<option value="">-- Clinic session required --</option>';
      doctorSelect.disabled = true;
      if (doctorMeta) {
        doctorMeta.textContent = 'Clinic session not found. Set it via /api/session/login?user_id=…';
      }
      resetRows();
      return;
    }

    if (!force && cachedDoctors.length) {
      populateDoctorOptions(cachedDoctors);
      return;
    }

    doctorSelect.innerHTML = '<option value="">-- Loading doctors --</option>';
    doctorSelect.disabled = true;
    if (doctorMeta) {
      doctorMeta.textContent = `Loading doctors for clinic #${SESSION_CLINIC_ID}…`;
    }

    const res = await api(doctorApiUrl());
    if (!res.ok) {
      doctorSelect.innerHTML = '<option value="">-- Unable to load doctors --</option>';
      doctorMeta.textContent = 'Unable to fetch doctors. Check API logs.';
      resetRows();
      return;
    }

    cachedDoctors = Array.isArray(res.json?.doctors) ? res.json.doctors : [];
    populateDoctorOptions(cachedDoctors);
  }

  async function loadAvailability(){
    const doctorId = doctorSelect?.value;
    if (!doctorId) {
      resetRows();
      statusNote.textContent = 'Select a doctor to load availability.';
      return;
    }
    const serviceType = serviceTypeSelect?.value || '';
    statusNote.textContent = 'Loading availability…';
    const query = serviceType ? `?service_type=${encodeURIComponent(serviceType)}` : '';
    const res = await api(`${apiBase}/doctors/${doctorId}/availability${query}`);
    if (!res.ok) {
      statusNote.textContent = 'Unable to load availability. Please try again.';
      resetRows();
      return;
    }
    applyAvailability(res.json?.availability || []);
  }

  function gatherPayload(){
    const doctorId = doctorSelect?.value;
    if (!doctorId) return null;
    const serviceType = serviceTypeSelect?.value || 'video';
    const availability = [];
    scheduleRows.forEach(row => {
      const active = row.querySelector('.toggle-active').checked;
      const start = row.querySelector('.input-start').value;
      const end = row.querySelector('.input-end').value;
      if (!active || !start || !end) return;
      availability.push({
        service_type: serviceType,
        day_of_week: Number(row.dataset.day),
        start_time: start,
        end_time: end,
        break_start: row.querySelector('.input-break-start').value || null,
        break_end: row.querySelector('.input-break-end').value || null,
        avg_consultation_mins: Number(row.querySelector('.input-avg').value) || 20,
        max_bookings_per_hour: Number(row.querySelector('.input-max').value) || 3,
      });
    });
    return { doctorId, payload: { availability } };
  }

  async function saveAvailability(){
    const data = gatherPayload();
    if (!data) {
      saveResult.textContent = 'Select a doctor before saving.';
      saveResult.className = 'text-sm text-amber-600';
      return;
    }
    if (!data.payload.availability.length) {
      saveResult.textContent = 'Add at least one active slot before saving.';
      saveResult.className = 'text-sm text-amber-600';
      return;
    }
    saveResult.textContent = 'Saving availability…';
    saveResult.className = 'text-sm text-gray-600';
    const res = await api(`${apiBase}/doctors/${data.doctorId}/availability`, {
      method: 'PUT',
      body: JSON.stringify(data.payload)
    });
    if (res.ok) {
      saveResult.textContent = 'Availability saved successfully.';
      saveResult.className = 'text-sm text-emerald-600';
      await loadAvailability();
    } else {
      const message = res.json?.error || res.json?.message || 'Failed to save availability.';
      saveResult.textContent = message;
      saveResult.className = 'text-sm text-red-600';
    }
  }

  scheduleRows.forEach(row => {
    const toggle = row.querySelector('.toggle-active');
    toggle.addEventListener('change', () => {
      const enabled = toggle.checked;
      setRowEnabled(row, enabled);
      if (enabled) {
        const startInput = row.querySelector('.input-start');
        const endInput = row.querySelector('.input-end');
        if (!startInput.value) startInput.value = '09:00';
        if (!endInput.value) endInput.value = '17:00';
      }
    });
    setRowEnabled(row, false);
  });

  doctorSelect?.addEventListener('change', () => {
    updateDoctorBadge();
    loadAvailability();
  });
  serviceTypeSelect?.addEventListener('change', loadAvailability);
  btnReload?.addEventListener('click', e => {
    e.preventDefault();
    cachedDoctors = [];
    loadDoctors(true);
  });
  btnSave?.addEventListener('click', e => { e.preventDefault(); saveAvailability(); });

  document.addEventListener('DOMContentLoaded', () => {
    updateDoctorBadge();
    if (SESSION_CLINIC_ID) {
      doctorMeta.textContent = `Loading doctors for clinic #${SESSION_CLINIC_ID}…`;
    } else if (doctorMeta) {
      doctorMeta.textContent = 'Clinic session not found. Set it via /api/session/login?user_id=…';
    }
    resetRows();
    if (cachedDoctors.length) {
      populateDoctorOptions(cachedDoctors);
    } else {
      loadDoctors();
    }
  });
</script>
@endsection
