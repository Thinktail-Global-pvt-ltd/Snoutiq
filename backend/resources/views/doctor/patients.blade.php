{{-- resources/views/doctor/patients.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Patient Medical Records')
@section('page_title','Patient Records')

@php
  $sessionRole = session('role')
      ?? data_get(session('auth_full'), 'role')
      ?? data_get(session('user'), 'role');

  $clinicCandidates = [
      session('clinic_id'),
      session('vet_registerations_temp_id'),
      session('vet_registeration_id'),
      session('vet_id'),
      data_get(session('user'), 'clinic_id'),
      data_get(session('user'), 'vet_registeration_id'),
      data_get(session('auth_full'), 'clinic_id'),
      data_get(session('auth_full'), 'user.clinic_id'),
      data_get(session('auth_full'), 'user.vet_registeration_id'),
  ];

  if ($sessionRole !== 'doctor') {
      array_unshift(
          $clinicCandidates,
          session('user_id'),
          data_get(session('user'), 'id')
      );
  }

  $resolvedClinicId = null;
  foreach ($clinicCandidates as $candidate) {
      if ($candidate === null || $candidate === '') {
          continue;
      }
      $num = (int) $candidate;
      if ($num > 0) {
          $resolvedClinicId = $num;
          break;
      }
  }

  $sessionDoctorId = session('doctor_id')
      ?? data_get(session('doctor'), 'id')
      ?? data_get(session('auth_full'), 'doctor_id')
      ?? data_get(session('auth_full'), 'user.doctor_id')
      ?? ($sessionRole === 'doctor' ? (session('user_id') ?? data_get(session('user'), 'id')) : null);
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
  <div class="bg-white rounded-2xl shadow border border-gray-200 p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Manage Patient Records</h2>
        <p class="text-sm text-gray-500 mt-1">Upload prescriptions, lab results, or notes for every patient tied to your clinic.</p>
      </div>
      <div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2">
        Clinic ID: <span class="font-semibold text-gray-900">{{ $resolvedClinicId ?: 'Not detected' }}</span>
      </div>
    </div>
    <ul class="mt-4 text-sm text-gray-600 list-disc pl-5 space-y-1">
      <li>Only patients who last visited this clinic (matched via <code class="bg-gray-100 px-1 rounded">users.last_vet_id</code>) appear below.</li>
      <li>All records are stored securely in the new <code class="bg-gray-100 px-1 rounded">medical_records</code> table.</li>
    </ul>
  </div>

  @if(!$resolvedClinicId)
    <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl p-4 text-sm">
      We could not detect a clinic ID in your session. Please open this page from your doctor or clinic dashboard where a clinic is selected.
    </div>
  @endif

  <div class="bg-white rounded-2xl shadow border border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-5 border-b border-gray-100">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Patients linked to this clinic</h3>
        <p class="text-sm text-gray-500">Doctors see only users mapped via <code class="bg-gray-100 px-1 rounded">vet_registerations_temp.id = users.last_vet_id</code>.</p>
      </div>
      <div class="flex items-center gap-3 text-sm text-gray-500">
        <span id="patients-count">0 patients</span>
        <button id="refresh-btn" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-gray-300 text-gray-700">
          <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h5M16 16v-5h-5M5.5 14.5a6 6 0 018.486-.014M14.5 5.5a6 6 0 00-8.486.014"/></svg>
          Refresh
        </button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600 uppercase text-[11px] tracking-wider">
          <tr>
            <th class="px-5 py-3 font-semibold">Patient</th>
            <th class="px-5 py-3 font-semibold">Pet</th>
            <th class="px-5 py-3 font-semibold">Records</th>
            <th class="px-5 py-3 font-semibold">Last Activity</th>
            <th class="px-5 py-3 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="patients-body" class="divide-y">
          <tr id="patients-loading">
            <td colspan="5" class="px-5 py-6 text-center text-gray-500">Loading patients…</td>
          </tr>
        </tbody>
      </table>
      <div id="patients-empty" class="hidden p-8 text-center text-gray-500 text-sm">No patients found for this clinic yet.</div>
    </div>
  </div>
</div>

<!-- Upload modal -->
<div id="record-modal" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 relative">
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800" data-role="close-record-modal">&times;</button>
    <div>
      <h3 class="text-xl font-semibold text-gray-900">Upload Medical Record</h3>
      <p id="record-modal-patient" class="text-sm text-gray-500 mt-1">Patient • —</p>
    </div>
    <form id="record-form" class="mt-6 space-y-4">
      <input type="hidden" name="user_id" id="record-user-id">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1" for="doctor-select">Doctor</label>
        <select name="doctor_id" id="doctor-select" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">Select doctor</option>
        </select>
        <p class="text-xs text-gray-400 mt-1">Default selection uses the logged-in doctor when available.</p>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1" for="record-notes">Notes</label>
        <textarea id="record-notes" name="notes" rows="3" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Follow-up required in 2 weeks"></textarea>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1" for="record-file">Medical file</label>
        <input id="record-file" name="record_file" type="file" class="w-full text-sm" required>
        <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG, DOC. 10 MB max.</p>
      </div>
      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-200 hover:bg-gray-50" data-role="close-record-modal">Cancel</button>
        <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Upload</button>
      </div>
    </form>
  </div>
</div>

<!-- Records viewer -->
<div id="records-panel" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
    <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Medical Records</h3>
        <p id="records-patient" class="text-sm text-gray-500">Patient • —</p>
      </div>
      <button type="button" class="text-gray-400 hover:text-gray-700" data-role="close-records">&times;</button>
    </div>
    <div class="flex-1 overflow-y-auto">
      <div id="records-loading" class="p-5 text-sm text-gray-500">Loading records…</div>
      <div id="records-empty" class="hidden p-8 text-center text-sm text-gray-400">No medical records uploaded yet.</div>
      <div id="records-list" class="divide-y"></div>
    </div>
  </div>
</div>

<script>
(() => {
  const ORIGIN = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
  const API_BASE = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  const CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;
  const DEFAULT_DOCTOR_ID = Number(@json($sessionDoctorId ?? null)) || null;

  const patientsBody = document.getElementById('patients-body');
  const loadingRow = document.getElementById('patients-loading');
  const emptyState = document.getElementById('patients-empty');
  const countEl = document.getElementById('patients-count');
  const refreshBtn = document.getElementById('refresh-btn');
  const modal = document.getElementById('record-modal');
  const modalPatient = document.getElementById('record-modal-patient');
  const modalUserInput = document.getElementById('record-user-id');
  const recordForm = document.getElementById('record-form');
  const doctorSelect = document.getElementById('doctor-select');
  const recordsPanel = document.getElementById('records-panel');
  const recordsPatient = document.getElementById('records-patient');
  const recordsLoading = document.getElementById('records-loading');
  const recordsEmpty = document.getElementById('records-empty');
  const recordsList = document.getElementById('records-list');
  let currentRecordsPatientId = null;
  const PATIENTS = new Map();

  function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date);
  }

  async function request(url, options = {}) {
    const headers = Object.assign({ 'Accept': 'application/json' }, options.headers || {});
    const res = await fetch(url, { ...options, headers });
    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (err) { data = null; }
    if (!res.ok) {
      const message = data?.error || data?.message || text || 'Request failed';
      throw new Error(message);
    }
    return data;
  }

  function clearPatientRows() {
    patientsBody.querySelectorAll('tr').forEach((row) => {
      if (row.id !== 'patients-loading') {
        row.remove();
      }
    });
  }

  function renderPatients(patients) {
    PATIENTS.clear();
    clearPatientRows();
    emptyState.classList.toggle('hidden', patients.length !== 0);
    countEl.textContent = `${patients.length} patient${patients.length === 1 ? '' : 's'}`;

    if (!patients.length) {
      return;
    }

    patients.forEach((patient) => {
      PATIENTS.set(patient.id, patient);
      const tr = document.createElement('tr');
      tr.className = 'border-b last:border-b-0';
      tr.innerHTML = `
        <td class="px-5 py-4 align-top">
          <div class="font-semibold text-gray-900">${escapeHtml(patient.name || 'Unnamed')} <span class="text-xs text-gray-400">#${patient.id}</span></div>
          <div class="text-xs text-gray-500 mt-1 break-all">${escapeHtml(patient.email || '—')}</div>
          <div class="text-xs text-gray-500">${escapeHtml(patient.phone || '—')}</div>
        </td>
        <td class="px-5 py-4 align-top">
          <div class="font-medium text-gray-800">${escapeHtml(patient.pet_name || '—')}</div>
          <div class="text-xs text-gray-500">${escapeHtml(patient.breed || '')}</div>
          <div class="text-xs text-gray-400">Age: ${patient.pet_age ?? '—'} · ${patient.pet_gender ? patient.pet_gender : '—'}</div>
        </td>
        <td class="px-5 py-4 align-top">
          <div class="text-sm font-semibold text-gray-900">${patient.records_count || 0} file${patient.records_count === 1 ? '' : 's'}</div>
          <div class="text-xs text-gray-500">Last upload: ${formatDate(patient.last_record_at)}</div>
        </td>
        <td class="px-5 py-4 align-top">
          <div class="text-sm text-gray-800">Updated ${formatDate(patient.updated_at)}</div>
        </td>
        <td class="px-5 py-4 align-top text-right">
          <div class="flex flex-col sm:flex-row justify-end gap-2">
            <button type="button" class="inline-flex justify-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700" data-action="upload" data-patient-id="${patient.id}">Upload</button>
            <button type="button" class="inline-flex justify-center px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 text-xs font-semibold hover:bg-gray-50" data-action="view" data-patient-id="${patient.id}">View</button>
          </div>
        </td>`;

      patientsBody.appendChild(tr);
    });
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    })[char]);
  }

  async function loadPatients() {
    if (!CLINIC_ID) {
      clearPatientRows();
      const tr = document.createElement('tr');
      tr.innerHTML = '<td colspan="5" class="px-5 py-6 text-center text-rose-600">Clinic ID missing. Cannot load patients.</td>';
      patientsBody.appendChild(tr);
      return;
    }
    if (loadingRow) {
      if (!loadingRow.isConnected) {
        patientsBody.prepend(loadingRow);
      }
      loadingRow.classList.remove('hidden');
    }
    emptyState.classList.add('hidden');
    try {
      const data = await request(`${API_BASE}/clinics/${CLINIC_ID}/patients`);
      const patients = Array.isArray(data?.patients) ? data.patients : [];
      renderPatients(patients);
    } catch (error) {
      clearPatientRows();
      const tr = document.createElement('tr');
      tr.innerHTML = `<td colspan="5" class="px-5 py-6 text-center text-rose-600">${escapeHtml(error.message)}</td>`;
      patientsBody.appendChild(tr);
      countEl.textContent = '0 patients';
    } finally {
      loadingRow?.classList.add('hidden');
    }
  }

  async function loadDoctors() {
    if (!CLINIC_ID || !doctorSelect) return;
    try {
      const data = await request(`${API_BASE}/clinics/${CLINIC_ID}/doctors`);
      const doctors = Array.isArray(data?.doctors) ? data.doctors : [];
      doctorSelect.innerHTML = '<option value="">Select doctor</option>';
      doctors.forEach((doc) => {
        const option = document.createElement('option');
        option.value = doc.id;
        option.textContent = doc.name || doc.doctor_name || `Doctor #${doc.id}`;
        doctorSelect.appendChild(option);
      });
      if (DEFAULT_DOCTOR_ID) {
        doctorSelect.value = DEFAULT_DOCTOR_ID;
      }
    } catch (error) {
      console.error('Failed to load doctors', error);
    }
  }

  function openModalForPatient(patientId) {
    const patient = PATIENTS.get(Number(patientId));
    if (!patient) return;
    modalPatient.textContent = `${patient.name || 'Patient'} • #${patient.id}`;
    modalUserInput.value = patient.id;
    modal.classList.remove('hidden');
  }

  function closeModal() {
    modal.classList.add('hidden');
    recordForm.reset();
    if (doctorSelect) {
      doctorSelect.value = DEFAULT_DOCTOR_ID || '';
    }
  }

  function openRecordsPanel(patientId) {
    const patient = PATIENTS.get(Number(patientId));
    if (!patient) return;
    recordsPatient.textContent = `${patient.name || 'Patient'} • #${patient.id}`;
    recordsLoading.classList.remove('hidden');
    recordsEmpty.classList.add('hidden');
    recordsList.innerHTML = '';
    recordsPanel.classList.remove('hidden');
    currentRecordsPatientId = patient.id;
    loadRecords(patient.id);
  }

  function closeRecordsPanel() {
    recordsPanel.classList.add('hidden');
    currentRecordsPatientId = null;
  }

  async function loadRecords(patientId) {
    if (!CLINIC_ID) return;
    try {
      const data = await request(`${API_BASE}/users/${patientId}/medical-records?clinic_id=${CLINIC_ID}`);
      const records = Array.isArray(data?.data?.records) ? data.data.records : [];
      recordsLoading.classList.add('hidden');
      if (!records.length) {
        recordsEmpty.classList.remove('hidden');
        return;
      }
      recordsEmpty.classList.add('hidden');
      recordsList.innerHTML = '';
      records.forEach((rec) => {
        const div = document.createElement('div');
        div.className = 'px-5 py-4 flex flex-col gap-1';
        div.innerHTML = `
          <div class="flex items-center justify-between gap-3">
            <div class="font-semibold text-gray-900">${escapeHtml(rec.file_name || 'Medical file')}</div>
            <a href="${rec.url}" target="_blank" rel="noopener" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Download</a>
          </div>
          <div class="text-xs text-gray-500">Uploaded ${formatDate(rec.uploaded_at)}${rec.doctor_id ? ` · Doctor #${rec.doctor_id}` : ''}</div>
          <div class="text-sm text-gray-700">${escapeHtml(rec.notes || 'No notes')}</div>`;
        recordsList.appendChild(div);
      });
    } catch (error) {
      recordsLoading.classList.add('hidden');
      recordsList.innerHTML = `<div class="px-5 py-4 text-rose-600 text-sm">${escapeHtml(error.message)}</div>`;
    }
  }

  recordForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!CLINIC_ID) {
      Swal.fire({ icon: 'error', title: 'Clinic missing', text: 'Clinic ID not detected. Reload dashboard.' });
      return;
    }
    const patientId = modalUserInput.value;
    if (!patientId) {
      Swal.fire({ icon: 'error', title: 'Patient missing', text: 'Select a patient before uploading.' });
      return;
    }
    const formData = new FormData(recordForm);
    formData.append('clinic_id', CLINIC_ID);
    if (!formData.get('doctor_id')) {
      formData.delete('doctor_id');
    }
    try {
      await request(`${API_BASE}/medical-records`, {
        method: 'POST',
        body: formData,
      });
      Swal.fire({ icon: 'success', title: 'Uploaded', timer: 1500, showConfirmButton: false });
      closeModal();
      loadPatients();
      if (!recordsPanel.classList.contains('hidden') && currentRecordsPatientId === Number(patientId)) {
        loadRecords(patientId);
      }
    } catch (error) {
      Swal.fire({ icon: 'error', title: 'Upload failed', text: error.message || 'Could not upload file' });
    }
  });

  refreshBtn?.addEventListener('click', () => loadPatients());
  document.querySelectorAll('[data-role="close-record-modal"]').forEach((btn) => btn.addEventListener('click', closeModal));
  document.querySelector('[data-role="close-records"]').addEventListener('click', closeRecordsPanel);

  patientsBody.addEventListener('click', (event) => {
    const uploadBtn = event.target.closest('[data-action="upload"]');
    if (uploadBtn) {
      openModalForPatient(uploadBtn.dataset.patientId);
      return;
    }
    const viewBtn = event.target.closest('[data-action="view"]');
    if (viewBtn) {
      openRecordsPanel(viewBtn.dataset.patientId);
    }
  });

  if (CLINIC_ID) {
    loadPatients();
    loadDoctors();
  } else {
    loadingRow.classList.add('hidden');
  }
})();
</script>
@endsection
